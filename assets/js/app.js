(function ($) {
  function scrollY() {
    return window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
  }

  function initHeader() {
    const $header = $(".site-header");
    const $btn = $(".nav-toggle");
    const $nav = $("#mainNav");
    if (!$header.length || !$btn.length || !$nav.length) return;

    function pastHeroCarousel() {
      return scrollY() > 12;
    }

    let headerTick = false;
    function onScroll() {
      if (headerTick) return;
      headerTick = true;
      window.requestAnimationFrame(function () {
        $header.toggleClass("is-scrolled", pastHeroCarousel() || $header.hasClass("is-nav-open"));
        headerTick = false;
      });
    }

    function setOpen(open) {
      $nav.toggleClass("is-open", open);
      $header.toggleClass("is-nav-open", open);
      $header.toggleClass("is-scrolled", open || pastHeroCarousel());
      $btn.attr("aria-expanded", String(open));
      $btn.html(window.SITE_SHELL.icon(open ? "close" : "menu", 26));
      $("body").toggleClass("nav-locked", open);
    }

    onScroll();
    window.addEventListener("scroll", onScroll, { passive: true });
    document.addEventListener("scroll", onScroll, { passive: true });
    $btn.on("click", function () {
      setOpen(!$nav.hasClass("is-open"));
    });
    $nav.on("click", ".dropdown-toggle", function (e) {
      e.preventDefault();
      const $item = $(this).closest(".has-dropdown");
      const open = !$item.hasClass("is-open");
      $item.toggleClass("is-open", open);
      $(this).attr("aria-expanded", String(open));
      $item.children(".nav-link").attr("aria-expanded", String(open));
    });
    $nav.on("click", "a", function () {
      if (window.matchMedia("(max-width: 991px)").matches) setOpen(false);
    });
    $(document).on("keydown", function (e) {
      if (e.key === "Escape" && $nav.hasClass("is-open")) {
        setOpen(false);
        $btn.trigger("focus");
      }
    });
    $(window).on("resize", function () {
      if (window.matchMedia("(min-width: 992px)").matches) {
        setOpen(false);
        $(".has-dropdown").removeClass("is-open");
      }
    });
  }

  function initBackToTop() {
    const $btn = $(".back-to-top");
    if (!$btn.length) return;
    let tick = false;
    $(window).on(
      "scroll",
      function () {
        if (tick) return;
        tick = true;
        window.requestAnimationFrame(function () {
          $btn.toggleClass("is-visible", $(window).scrollTop() > 500);
          tick = false;
        });
      },
      { passive: true }
    );
    $btn.on("click", function () {
      const reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
      if (reduce) {
        window.scrollTo(0, 0);
        return;
      }
      $("html, body").animate({ scrollTop: 0 }, 400);
    });
  }

  function initImageFallback() {
    const fallback = window.SITE_SHELL.fallbackImage;
    document.addEventListener(
      "error",
      function (e) {
        const img = e.target;
        if (!(img instanceof HTMLImageElement) || !img.hasAttribute("data-fallback")) return;
        if (img.dataset.fallbackApplied === "1") return;
        img.dataset.fallbackApplied = "1";
        img.classList.add("is-broken");
        img.src = fallback;
      },
      true
    );
  }

  function initWhatsapp() {
    const $fab = $(".whatsapp-fab");
    if (!$fab.length || !window.SITE_CONFIG) return;
    if (document.getElementById("prodWhatsapp")) return;
    const number = window.SITE_CONFIG.contact.whatsapp.replace("+", "");
    $fab.attr(
      "href",
      "https://wa.me/" + number + "?text=" + encodeURIComponent("Hello, I would like to know more about your safety products.")
    );
  }

  function initYear() {
    $("[data-current-year]").text(new Date().getFullYear());
  }

  function initMap() {
    const root = document.querySelector("[data-map-picker]");
    if (!root) return;
    const tabs = root.querySelectorAll("[data-map-tab]");
    const frames = root.querySelectorAll(".map-frame");
    if (!tabs.length) return;

    function showMap(index) {
      tabs.forEach(function (tab, i) {
        const on = i === index;
        tab.classList.toggle("is-active", on);
        tab.setAttribute("aria-selected", on ? "true" : "false");
      });
      frames.forEach(function (frame, i) {
        frame.classList.toggle("is-active", i === index);
      });
    }

    tabs.forEach(function (tab, i) {
      tab.addEventListener("click", function () {
        showMap(i);
      });
    });
  }

  function fieldError($field) {
    const value = $.trim($field.val());
    const type = ($field.attr("type") || "").toLowerCase();

    if ($field.is("[required]") && value === "") {
      return "This field is required.";
    }
    if (value === "") return "";
    if (type === "email" && !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(value)) {
      return "Enter a valid email address.";
    }
    if (type === "tel" && value.replace(/[^\d]/g, "").length < 10) {
      return "Enter a phone number with at least 10 digits.";
    }
    if ($field.is("textarea") && $field.is("[required]") && value.length < 10) {
      return "Tell us a little more — at least 10 characters.";
    }
    return "";
  }

  function showFieldError($field, message) {
    const id = $field.attr("id");
    const errorId = id ? id + "-error" : null;
    let $msg = $field.siblings(".field-error-msg");

    $field.toggleClass("is-invalid", Boolean(message));
    $field.attr("aria-invalid", message ? "true" : null);

    if (!message) {
      $msg.remove();
      $field.removeAttr("aria-describedby");
      return;
    }
    if (!$msg.length) {
      $msg = $('<span class="field-error-msg" role="alert"></span>');
      if (errorId) $msg.attr("id", errorId);
      $field.after($msg);
    }
    $msg.text(message);
    if (errorId) $field.attr("aria-describedby", errorId);
  }

  function initForms() {
    $(document).on("blur", "form[data-validate] [required], form[data-validate] [type='email'], form[data-validate] [type='tel']", function () {
      showFieldError($(this), fieldError($(this)));
    });

    $(document).on("input change", "form[data-validate] .is-invalid", function () {
      const message = fieldError($(this));
      if (!message) showFieldError($(this), "");
    });

    $(document).on("submit", "form[data-validate]", function (e) {
      e.preventDefault();
      const $form = $(this);
      const $feedback = $form.find("[data-form-feedback]");
      let $firstBad = null;

      $form.find("input, textarea, select").each(function () {
        const $field = $(this);
        const message = fieldError($field);
        showFieldError($field, message);
        if (message && !$firstBad) $firstBad = $field;
      });

      if ($firstBad) {
        $feedback
          .text("Please correct the highlighted fields.")
          .attr("class", "form-feedback is-error")
          .attr("role", "alert");
        $firstBad.trigger("focus");
        return;
      }

      const $submit = $form.find("[type='submit']");
      $submit.prop("disabled", true).attr("data-label", $submit.text()).text("Sending…");
      $feedback.text("").attr("class", "form-feedback");

      const endpoint = $form.attr("action") || window.SITE_SHELL.url("contact-submit.php");
      fetch(endpoint, {
        method: "POST",
        body: new FormData($form[0]),
        headers: {
          Accept: "application/json",
          "X-Requested-With": "fetch"
        }
      })
        .then(function (res) {
          return res.json().then(function (data) {
            return { ok: res.ok && data && data.ok, message: (data && data.message) || "" };
          });
        })
        .then(function (result) {
          if (!result.ok) {
            $feedback
              .text(result.message || "We could not send that just now. Please try again.")
              .attr("class", "form-feedback is-error")
              .attr("role", "alert");
            $submit.prop("disabled", false).text($submit.attr("data-label") || "Submit Request");
            return;
          }
          $feedback
            .text(result.message || "Thank you. Our team will get back to you within one business day.")
            .attr("class", "form-feedback is-success")
            .attr("role", "status");
          $form[0].reset();
          $form.find(".is-invalid").removeClass("is-invalid");
          $form.find(".field-error-msg").remove();
          $submit.prop("disabled", false).text($submit.attr("data-label") || "Submit Request");
          if ($form.is("[data-quote-form]")) {
            trackQuoteLead($form);
            const $modal = $form.closest("[data-quote-modal]");
            $form.attr("hidden", true);
            $modal.find("[data-quote-success]").removeAttr("hidden");
            try { sessionStorage.setItem("bluelotusQuoteLead", "done"); } catch (err) {}
          }
        })
        .catch(function () {
          $feedback
            .text("We could not send that just now. Please try again.")
            .attr("class", "form-feedback is-error")
            .attr("role", "alert");
          $submit.prop("disabled", false).text($submit.attr("data-label") || "Submit Request");
        });
    });
  }

  function animateCount($el) {
    const target = parseFloat($el.attr("data-count"));
    const suffix = $el.attr("data-suffix") || "";
    $({ n: 0 }).animate(
      { n: target },
      {
        duration: 1400,
        easing: "swing",
        step: function (now) {
          $el.text(Math.floor(now) + suffix);
        },
        complete: function () {
          $el.text(target + suffix);
        }
      }
    );
  }

  function initCounters() {
    const $nodes = $(".value[data-count]");
    if (!$nodes.length) return;
    const observer = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (!entry.isIntersecting) return;
          animateCount($(entry.target));
          observer.unobserve(entry.target);
        });
      },
      { threshold: 0.4 }
    );
    $nodes.each(function () {
      observer.observe(this);
    });
  }

  function initTabs() {
    const $tabs = $("[data-render='service-tabs']");
    const $panels = $("[data-render='service-panels']");
    if (!$tabs.length || !$panels.length) return;

    function activate($btn) {
      const $all = $tabs.find(".service-tab-btn");
      $all.removeClass("is-active").attr({ "aria-selected": "false", tabindex: "-1" });
      $btn.addClass("is-active").attr({ "aria-selected": "true", tabindex: "0" });
      const id = $btn.attr("data-tab-target");
      $panels.find(".service-panel").each(function () {
        $(this).toggleClass("is-active", this.id === id);
      });
    }

    $tabs.on("click", ".service-tab-btn", function () {
      activate($(this));
    });

    $tabs.on("keydown", ".service-tab-btn", function (e) {
      const keys = { ArrowRight: 1, ArrowDown: 1, ArrowLeft: -1, ArrowUp: -1 };
      const $all = $tabs.find(".service-tab-btn");
      const index = $all.index(this);
      let next = null;

      if (keys[e.key]) next = (index + keys[e.key] + $all.length) % $all.length;
      else if (e.key === "Home") next = 0;
      else if (e.key === "End") next = $all.length - 1;
      if (next === null) return;

      e.preventDefault();
      const $target = $all.eq(next);
      activate($target);
      $target.trigger("focus");
    });
  }

  function initReveal() {
    if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
      $("[data-reveal]").addClass("is-in");
      return;
    }
    const $nodes = $("[data-reveal]").not(".is-in");
    if (!$nodes.length) return;
    const observer = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (!entry.isIntersecting) return;
          $(entry.target).addClass("is-in");
          observer.unobserve(entry.target);
        });
      },
      { threshold: 0.15, rootMargin: "0px 0px -40px 0px" }
    );
    $nodes.each(function () {
      observer.observe(this);
    });
  }

  function initHeroCarousel() {
    const root = document.getElementById("heroCarousel");
    if (!root || root.getAttribute("data-hero-bound") === "1") return;
    root.setAttribute("data-hero-bound", "1");

    const slides = root.querySelectorAll(".hero-slide");
    const navButtons = root.querySelectorAll("[data-hero-goto]");
    const total = slides.length;
    if (total < 2) return;

    const reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    const AUTO_MS = 4000;
    let current = 0;
    let locked = false;
    let wheelAcc = 0;
    let touchY = 0;
    let timer = 0;

    function heroLocked() {
      const rect = root.getBoundingClientRect();
      return rect.top > -24 && rect.top < 24 && rect.bottom > window.innerHeight - 24;
    }

    function setSlide(index) {
      current = ((index % total) + total) % total;
      slides.forEach(function (slide, i) {
        slide.classList.toggle("is-active", i === current);
      });
      navButtons.forEach(function (btn) {
        btn.classList.toggle("is-active", Number(btn.getAttribute("data-hero-goto")) === current);
      });
    }

    function leaveHero() {
      window.scrollTo({
        top: window.scrollY + root.getBoundingClientRect().bottom,
        behavior: reduce ? "auto" : "smooth"
      });
    }

    function stopAuto() {
      window.clearTimeout(timer);
      timer = 0;
    }

    function startAuto() {
      stopAuto();
      timer = window.setTimeout(function tick() {
        if (!document.hidden) {
          setSlide(current + 1);
        }
        timer = window.setTimeout(tick, AUTO_MS);
      }, AUTO_MS);
    }

    function go(step) {
      setSlide(current + step);
      startAuto();
    }

    function cooldown() {
      locked = true;
      window.setTimeout(function () {
        locked = false;
        wheelAcc = 0;
      }, reduce ? 80 : 700);
    }

    window.addEventListener(
      "wheel",
      function (e) {
        if (!heroLocked()) return;
        const down = e.deltaY > 0;
        if ((current === 0 && !down) || (current === total - 1 && down)) return;
        e.preventDefault();
        if (locked) return;
        wheelAcc += e.deltaY;
        if (Math.abs(wheelAcc) < 28) return;
        go(wheelAcc > 0 ? 1 : -1);
        cooldown();
      },
      { passive: false }
    );

    root.addEventListener(
      "touchstart",
      function (e) {
        touchY = e.touches[0].clientY;
      },
      { passive: true }
    );

    root.addEventListener(
      "touchend",
      function (e) {
        if (!heroLocked() || locked) return;
        const dy = touchY - e.changedTouches[0].clientY;
        if (Math.abs(dy) < 32) return;
        if ((current === 0 && dy < 0) || (current === total - 1 && dy > 0)) return;
        go(dy > 0 ? 1 : -1);
        cooldown();
      },
      { passive: true }
    );

    navButtons.forEach(function (btn) {
      btn.addEventListener("click", function () {
        setSlide(Number(btn.getAttribute("data-hero-goto")));
        startAuto();
      });
    });

    const nextBtn = root.querySelector("[data-hero-next]");
    if (nextBtn) {
      nextBtn.addEventListener("click", leaveHero);
    }

    document.addEventListener("visibilitychange", function () {
      if (document.hidden) stopAuto();
      else startAuto();
    });

    document.body.classList.add("is-in-hero");
    window.addEventListener(
      "scroll",
      function () {
        const inView = heroLocked() || root.getBoundingClientRect().top >= 0;
        document.body.classList.toggle("is-in-hero", inView);
      },
      { passive: true }
    );

    setSlide(0);
    startAuto();
  }

  function trackQuoteLead($form) {
    const sendTo = (($form.closest("[data-quote-modal]").attr("data-ads-send-to") || "")).trim();
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({ event: "generate_lead", lead_source: "quote_popup" });
    if (typeof window.gtag === "function") {
      window.gtag("event", "generate_lead", { method: "quote_popup" });
      if (sendTo) {
        window.gtag("event", "conversion", { send_to: sendTo });
      }
    }
    if (typeof window.fbq === "function") {
      window.fbq("track", "Lead");
    }
  }

  function initQuoteModal() {
    const root = document.querySelector("[data-quote-modal]");
    if (!root) return;

    try {
      if (sessionStorage.getItem("bluelotusQuoteLead") === "done") return;
    } catch (err) {}

    const qEl = root.querySelector("[data-quote-captcha-q]");
    const refreshBtn = root.querySelector("[data-quote-captcha-refresh]");
    const captchaInput = root.querySelector("#adsCaptcha");

    function open() {
      root.hidden = false;
      document.body.classList.add("quote-locked");
      const first = root.querySelector("input:not([type=hidden])");
      if (first) first.focus();
    }

    function close() {
      root.hidden = true;
      document.body.classList.remove("quote-locked");
      try { sessionStorage.setItem("bluelotusQuoteLead", "done"); } catch (err) {}
    }

    root.querySelectorAll("[data-quote-close]").forEach(function (el) {
      el.addEventListener("click", close);
    });

    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && !root.hidden) close();
    });

    if (refreshBtn) {
      refreshBtn.addEventListener("click", function () {
        const endpoint = (window.SITE_SHELL && window.SITE_SHELL.url)
          ? window.SITE_SHELL.url("contact-submit.php?captcha=1")
          : "contact-submit.php?captcha=1";
        fetch(endpoint, { headers: { Accept: "application/json" }, cache: "no-store" })
          .then(function (res) { return res.json(); })
          .then(function (data) {
            if (!data || data.a == null || data.b == null) return;
            if (qEl) qEl.textContent = data.a + " + " + data.b + " =";
            if (captchaInput) captchaInput.value = "";
          })
          .catch(function () {});
      });
    }

    open();
  }

  function onShell() {
    initHeader();
    initBackToTop();
    initImageFallback();
    initHeroCarousel();
  }

  function onConfig() {
    initWhatsapp();
    initYear();
    initMap();
    initCounters();
    initTabs();
    initReveal();
  }

  window.SITE_SHELL.whenReady(onShell);
  $(document).on("config:rendered", onConfig);
  $(function () {
    initForms();
    initQuoteModal();
    initHeroCarousel();
  });
})(jQuery);
