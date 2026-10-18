(function ($) {
  const esc = window.SITE_SHELL.esc;
  const icon = window.SITE_SHELL.icon;

  function selectThumb($thumb) {
    if (!$thumb.length) return;
    const src = $thumb.attr("data-src");
    const $img = $("#prodMainImage");
    if ($img.attr("src") !== src) {
      $img.css("opacity", "0.35");
      window.setTimeout(function () {
        $img.attr("src", src).css("opacity", "1");
      }, 80);
    }
    const $all = $(".product-thumb");
    $all.removeClass("is-active").attr("aria-pressed", "false");
    $thumb.addClass("is-active").attr("aria-pressed", "true");
    const $count = $("#prodImageCount");
    if ($count.length) {
      $count.text(($all.index($thumb) + 1) + " / " + $all.length);
    }
  }

  function buildShare() {
    const $mount = $("#prodShare");
    if (!$mount.length) return;

    const url = $mount.attr("data-share-url") || location.href;
    const title = $mount.attr("data-share-title") || document.title;
    const text = $mount.attr("data-share-text") || "";
    const encodedUrl = encodeURIComponent(url);

    const links = [
      {
        label: "WhatsApp",
        net: "whatsapp",
        href: "https://api.whatsapp.com/send?text=" + encodeURIComponent(title + "\n" + text + "\n" + url)
      },
      {
        label: "Instagram",
        net: "instagram",
        href: $mount.attr("data-share-instagram") || "https://www.instagram.com/"
      },
      {
        label: "Facebook",
        net: "facebook",
        href: "https://www.facebook.com/sharer/sharer.php?u=" + encodedUrl
      },
      {
        label: "LinkedIn",
        net: "linkedin",
        href: "https://www.linkedin.com/sharing/share-offsite/?url=" + encodedUrl
      }
    ];

    $mount.addClass("social-row social-row--product");
    $mount.html(
      links
        .map(function (item) {
          return `<a class="social-${item.net}" href="${esc(item.href)}" target="_blank" rel="noopener" aria-label="Share on ${item.label}">${icon(item.net, 16)}</a>`;
        })
        .join("")
    );
  }

  function syncWhatsappFab() {
    const $enquire = $("#prodWhatsapp");
    if ($enquire.length) {
      $(".whatsapp-fab").attr("href", $enquire.attr("href"));
    }
  }

  function closeZoom() {
    $(".product-zoom").remove();
    $("body").removeClass("quote-locked");
  }

  $(document).on("click", "#prodMainImage", function () {
    const src = this.getAttribute("src");
    if (!src) return;
    const alt = this.getAttribute("alt") || "";
    $("body").append(
      '<div class="product-zoom" role="dialog" aria-modal="true">' +
        '<button type="button" class="product-zoom-close" aria-label="Close">&times;</button>' +
        '<img src="' + esc(src) + '" alt="' + esc(alt) + '">' +
      "</div>"
    );
    $("body").addClass("quote-locked");
  });

  $(document).on("click", ".product-zoom", function (e) {
    if (e.target === this || $(e.target).closest(".product-zoom-close").length) {
      closeZoom();
    }
  });

  $(document).on("keydown", function (e) {
    if (e.key === "Escape") closeZoom();
  });

  $(document).on("click", ".product-thumb", function () {
    selectThumb($(this));
  });

  $(document).on("keydown", ".product-thumb", function (e) {
    const step = { ArrowRight: 1, ArrowDown: 1, ArrowLeft: -1, ArrowUp: -1 }[e.key];
    if (!step) return;
    e.preventDefault();
    const $all = $(".product-thumb");
    const next = ($all.index(this) + step + $all.length) % $all.length;
    const $target = $all.eq(next);
    selectThumb($target);
    $target.trigger("focus");
  });

  window.SITE_SHELL.whenReady(function () {
    buildShare();
    syncWhatsappFab();
  });
})(jQuery);
