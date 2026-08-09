(function ($) {
  function initHeader() {
    const $header = $(".site-header");
    const $btn = $(".nav-toggle");
    const $nav = $("#mainNav");
    if (!$header.length || !$btn.length || !$nav.length) return;

    function onScroll() {
      $header.toggleClass("is-scrolled", $(window).scrollTop() > 24 || $header.hasClass("is-nav-open"));
    }

    function setOpen(open) {
      $nav.toggleClass("is-open", open);
      $header.toggleClass("is-nav-open", open);
      $header.toggleClass("is-scrolled", open || $(window).scrollTop() > 24);
      $btn.attr("aria-expanded", String(open));
      $btn.html(window.SITE_SHELL.icon(open ? "close" : "menu", 26));
      $("body").toggleClass("nav-locked", open);
    }

    onScroll();
    $(window).on("scroll", onScroll);
    $btn.on("click", function () {
      setOpen(!$nav.hasClass("is-open"));
    });
    $nav.on("click", "a", function () {
      if (window.matchMedia("(max-width: 991px)").matches) setOpen(false);
    });
    $(window).on("resize", function () {
      if (window.matchMedia("(min-width: 992px)").matches) setOpen(false);
    });
  }

  function initBackToTop() {
    const $btn = $(".back-to-top");
    if (!$btn.length) return;
    $(window).on("scroll", function () {
      $btn.toggleClass("is-visible", $(window).scrollTop() > 500);
    });
    $btn.on("click", function () {
      $("html, body").animate({ scrollTop: 0 }, 400);
    });
  }

  function initWhatsapp() {
    const $fab = $(".whatsapp-fab");
    if (!$fab.length || !window.SITE_CONFIG) return;
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
    const $frame = $("[data-map-embed]");
    if ($frame.length && window.SITE_CONFIG) {
      $frame.attr("src", window.SITE_CONFIG.contact.mapEmbedUrl);
    }
  }

  function initForms() {
    $(document).on("submit", "form[data-validate]", function (e) {
      e.preventDefault();
      const $form = $(this);
      let valid = true;
      $form.find("[required]").each(function () {
        const ok = $.trim($(this).val()).length > 0;
        $(this).toggleClass("is-invalid", !ok);
        if (!ok) valid = false;
      });
      const $feedback = $form.find("[data-form-feedback]");
      if (!$feedback.length) return;
      if (valid) {
        $feedback.text("Thank you. Our team will get back to you within one business day.").attr("class", "form-feedback is-success");
        $form[0].reset();
      } else {
        $feedback.text("Please fill in all required fields before submitting.").attr("class", "form-feedback is-error");
      }
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
    const $nodes = $("[data-count]");
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
    $tabs.on("click", ".service-tab-btn", function () {
      const $btn = $(this);
      $tabs.find(".service-tab-btn").removeClass("is-active");
      $btn.addClass("is-active");
      const id = $btn.attr("data-tab-target");
      $panels.find(".service-panel").each(function () {
        $(this).toggleClass("is-active", this.id === id);
      });
    });
  }

  function initReveal() {
    const $nodes = $("[data-reveal]");
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

  function onShell() {
    initHeader();
    initBackToTop();
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
  $(initForms);
})(jQuery);
