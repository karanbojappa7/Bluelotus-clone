(function () {
  "use strict";

  function ready(fn) {
    if (document.readyState !== "loading") fn();
    else document.addEventListener("DOMContentLoaded", fn);
  }

  function slugify(text) {
    return String(text)
      .toLowerCase()
      .trim()
      .replace(/[^a-z0-9]+/g, "-")
      .replace(/^-+|-+$/g, "");
  }

  function initConfirms() {
    document.addEventListener("submit", function (e) {
      const form = e.target;
      if (!(form instanceof HTMLFormElement)) return;
      const message = form.getAttribute("data-confirm");
      if (message && !window.confirm(message)) {
        e.preventDefault();
        return;
      }
      form.setAttribute("data-submitting", "1");
      const submit = form.querySelector("button[type='submit']");
      if (submit && !submit.disabled) {
        submit.dataset.label = submit.textContent;
        submit.textContent = "Working…";
        window.setTimeout(function () {
          submit.disabled = true;
        }, 0);
      }
    });
  }

  function initAutoSubmit() {
    document.querySelectorAll("[data-autosubmit]").forEach(function (el) {
      el.addEventListener("change", function () {
        if (el.form) el.form.submit();
      });
    });
  }

  function initFlash() {
    document.querySelectorAll(".flash").forEach(function (flash) {
      const close = flash.querySelector(".flash-close");
      if (close) {
        close.addEventListener("click", function () {
          flash.remove();
        });
      }
      if (flash.hasAttribute("data-autodismiss") && !flash.classList.contains("flash--error")) {
        window.setTimeout(function () {
          flash.classList.add("is-fading");
          window.setTimeout(function () {
            flash.remove();
          }, 400);
        }, 6000);
      }
    });
  }

  function initNav() {
    const burger = document.querySelector(".nav-burger");
    const nav = document.getElementById("adminNav");
    if (!burger || !nav) return;
    burger.addEventListener("click", function () {
      const open = nav.classList.toggle("is-open");
      burger.setAttribute("aria-expanded", String(open));
    });
    nav.addEventListener("click", function (e) {
      if (e.target.closest("a") && window.matchMedia("(max-width: 900px)").matches) {
        nav.classList.remove("is-open");
        burger.setAttribute("aria-expanded", "false");
      }
    });
  }

  function initSlug() {
    const panel = document.querySelector("form.form-panel");
    if (!panel) return;
    const slug = panel.querySelector("input[name='slug'], input[name='id']");
    const name = panel.querySelector("input[name='name'], input[name='title']");
    if (!slug || !name || slug.dataset.lockSlug === "1") return;

    const preset = slug.value.trim();
    let touched = preset !== "";

    slug.addEventListener("input", function () {
      touched = slug.value.trim() !== "";
    });

    name.addEventListener("input", function () {
      if (touched) return;
      slug.value = slugify(name.value);
      updateSlugPreview(slug);
    });

    slug.addEventListener("blur", function () {
      if (slug.value.trim() === "") return;
      slug.value = slugify(slug.value);
      updateSlugPreview(slug);
    });

    updateSlugPreview(slug);
  }

  function updateSlugPreview(slug) {
    let preview = slug.parentElement.querySelector(".slug-preview");
    const base = slug.dataset.previewBase;
    if (!base) return;
    if (!preview) {
      preview = document.createElement("span");
      preview.className = "hint slug-preview";
      slug.parentElement.appendChild(preview);
    }
    const value = slugify(slug.value) || "…";
    preview.textContent = base + value;
  }

  function initImagePreview() {
    document.querySelectorAll("form.form-panel input[type='file']").forEach(function (input) {
      if (!/image\//.test(input.getAttribute("accept") || "image/")) return;

      const box = document.createElement("div");
      box.className = "upload-preview";
      if (input.dataset.preview === "wide") {
        box.classList.add("upload-preview--wide");
      }
      input.parentElement.appendChild(box);

      const label = input.closest("label");
      const saved =
        (label && label.nextElementSibling && label.nextElementSibling.classList.contains("file-preview")
          ? label.nextElementSibling
          : label && label.querySelector(".file-preview")) || null;

      input.addEventListener("change", function () {
        box.innerHTML = "";
        const files = Array.prototype.slice.call(input.files || []);
        if (saved) saved.hidden = files.length > 0;

        const tooBig = files.filter(function (f) {
          return f.size > 5 * 1024 * 1024;
        });

        files.forEach(function (file) {
          if (!file.type.startsWith("image/") && !/\.(jpe?g|png|webp|gif)$/i.test(file.name)) return;
          const figure = document.createElement("figure");
          figure.className = "upload-thumb";
          const img = document.createElement("img");
          img.alt = file.name || "Selected image";
          const cap = document.createElement("figcaption");
          cap.textContent = file.name + " · " + (file.size / 1024 / 1024).toFixed(1) + " MB";
          if (file.size > 5 * 1024 * 1024) figure.classList.add("is-oversize");
          figure.appendChild(img);
          figure.appendChild(cap);
          box.appendChild(figure);
          const reader = new FileReader();
          reader.onload = function () {
            img.src = String(reader.result || "");
          };
          reader.readAsDataURL(file);
        });

        if (tooBig.length) {
          const warn = document.createElement("p");
          warn.className = "upload-warning";
          warn.textContent =
            tooBig.length === 1
              ? "This image is over the 5 MB limit and will be rejected."
              : tooBig.length + " images are over the 5 MB limit and will be rejected.";
          box.appendChild(warn);
        }
      });
    });
  }

  function initDirtyGuard() {
    const form = document.querySelector("form.form-panel");
    if (!form) return;
    let dirty = false;

    form.addEventListener("input", function () {
      dirty = true;
    });
    form.addEventListener("change", function () {
      dirty = true;
    });
    form.addEventListener("submit", function () {
      dirty = false;
    });

    window.addEventListener("beforeunload", function (e) {
      if (!dirty) return;
      e.preventDefault();
      e.returnValue = "";
    });

    form.querySelectorAll("a").forEach(function (link) {
      link.addEventListener("click", function (e) {
        if (!dirty) return;
        if (!window.confirm("You have unsaved changes. Leave this page?")) {
          e.preventDefault();
          return;
        }
        dirty = false;
      });
    });
  }

  function initCounters() {
    document.querySelectorAll("[data-maxlen]").forEach(function (field) {
      const max = parseInt(field.getAttribute("data-maxlen"), 10);
      if (!max) return;
      const note = document.createElement("span");
      note.className = "hint char-count";
      field.parentElement.appendChild(note);
      const update = function () {
        const len = field.value.length;
        note.textContent = len + " / " + max;
        note.classList.toggle("is-over", len > max);
      };
      field.addEventListener("input", update);
      update();
    });
  }

  function initSerpPreview() {
    const box = document.querySelector("[data-serp]");
    if (!box) return;

    const panel = box.closest("[data-seo-panel]") || document;
    const titleInput = panel.querySelector("[data-serp-input='title']");
    const descInput = panel.querySelector("[data-serp-input='desc']");
    const slugInput = document.querySelector("form.form-panel input[name='slug'], form.form-panel input[name='id']");
    const nameInput = document.querySelector("form.form-panel input[name='name'], form.form-panel input[name='title']");
    const summary = document.querySelector("form.form-panel textarea[name='short'], form.form-panel textarea[name='excerpt'], form.form-panel textarea[name='intro']");

    const base = box.getAttribute("data-base") || "";
    const brand = box.getAttribute("data-brand") || "";
    const urlOut = box.querySelector("[data-serp-url]");
    const titleOut = box.querySelector("[data-serp-title]");
    const descOut = box.querySelector("[data-serp-desc]");

    function clip(text, max) {
      return text.length > max ? text.slice(0, max - 1).trimEnd() + "…" : text;
    }

    function update() {
      const fallbackTitle =
        (nameInput && nameInput.value.trim()) || box.getAttribute("data-fallback-title") || "Untitled";
      const fallbackDesc =
        (summary && summary.value.trim()) || box.getAttribute("data-fallback-desc") || "";

      const slug = slugify((slugInput && slugInput.value) || box.getAttribute("data-slug") || "") || "…";
      const rawTitle = ((titleInput && titleInput.value.trim()) || fallbackTitle) + " | " + brand;
      const rawDesc = (descInput && descInput.value.trim()) || fallbackDesc;

      urlOut.textContent = (base + slug).replace(/([^:]\/)\/+/g, "$1");
      titleOut.textContent = clip(rawTitle, 60);
      descOut.textContent = rawDesc ? clip(rawDesc, 158) : "No description yet — add a summary above.";
      titleOut.classList.toggle("is-long", rawTitle.length > 60);
      descOut.classList.toggle("is-long", rawDesc.length > 158);
    }

    [titleInput, descInput, slugInput, nameInput, summary].forEach(function (el) {
      if (el) el.addEventListener("input", update);
    });
    update();
    box.dataset.serpReady = "1";
  }

  function initTableFilter() {
    const input = document.querySelector("[data-table-filter]");
    if (!input) return;
    const table = document.querySelector(".table-wrap tbody");
    if (!table) return;
    const empty = document.querySelector("[data-filter-empty]");

    input.addEventListener("input", function () {
      const term = input.value.trim().toLowerCase();
      let shown = 0;
      table.querySelectorAll("tr[data-row]").forEach(function (row) {
        const hit = !term || row.getAttribute("data-row").indexOf(term) !== -1;
        row.hidden = !hit;
        if (hit) shown++;
      });
      if (empty) empty.hidden = shown !== 0;
    });
  }

  function initBlogLinkInsert() {
    const button = document.querySelector("[data-insert-blog-link]");
    const select = document.getElementById("blogLinkTarget");
    const textarea = document.getElementById("blogBody");
    if (!button || !select || !textarea) return;

    button.addEventListener("click", function () {
      const option = select.options[select.selectedIndex];
      const value = option ? option.value : "";
      if (!value) {
        select.focus();
        return;
      }
      const selected = textarea.value.slice(textarea.selectionStart, textarea.selectionEnd);
      const label = selected.trim() !== "" ? selected : (option.getAttribute("data-label") || option.textContent || "read more");
      const snippet = "[" + label + "](" + value + ")";
      const start = textarea.selectionStart;
      const end = textarea.selectionEnd;
      textarea.setRangeText(snippet, start, end, "end");
      textarea.focus();
      textarea.dispatchEvent(new Event("input", { bubbles: true }));
    });
  }

  ready(function () {
    initConfirms();
    initAutoSubmit();
    initFlash();
    initNav();
    initSlug();
    initImagePreview();
    initDirtyGuard();
    initCounters();
    initSerpPreview();
    initTableFilter();
    initBlogLinkInsert();
  });
})();
