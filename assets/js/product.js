(function ($) {
  const esc = window.SITE_SHELL.esc;
  const icon = window.SITE_SHELL.icon;

  function selectThumb($thumb) {
    if (!$thumb.length) return;
    $("#prodMainImage").attr("src", $thumb.attr("data-src"));
    $(".product-thumb").removeClass("is-active").attr("aria-pressed", "false");
    $thumb.addClass("is-active").attr("aria-pressed", "true");
  }

  function buildShare() {
    const $mount = $("#prodShare");
    if (!$mount.length) return;

    const url = $mount.attr("data-share-url") || location.href;
    const title = $mount.attr("data-share-title") || document.title;
    const text = $mount.attr("data-share-text") || "";
    const encodedUrl = encodeURIComponent(url);
    const encodedTitle = encodeURIComponent(title);

    const links = [
      {
        label: "WhatsApp",
        href: "https://api.whatsapp.com/send?text=" + encodeURIComponent(title + "\n" + text + "\n" + url),
        icon: "whatsapp",
        className: "share-btn share-btn--wa"
      },
      {
        label: "Facebook",
        href: "https://www.facebook.com/sharer/sharer.php?u=" + encodedUrl,
        icon: "facebook",
        className: "share-btn share-btn--fb"
      },
      {
        label: "LinkedIn",
        href: "https://www.linkedin.com/sharing/share-offsite/?url=" + encodedUrl,
        icon: "linkedin",
        className: "share-btn share-btn--li"
      },
      {
        label: "Email",
        href: "mailto:?subject=" + encodedTitle + "&body=" + encodeURIComponent(text + "\n\n" + url),
        icon: "mail",
        className: "share-btn share-btn--mail"
      }
    ];

    $mount.html(
      links
        .map(function (item) {
          return `<a class="${item.className}" href="${esc(item.href)}" target="_blank" rel="noopener" aria-label="Share on ${item.label}">${icon(item.icon, 18)}<span>${item.label}</span></a>`;
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
