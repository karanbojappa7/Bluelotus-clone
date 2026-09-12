(function ($) {
  function initHeader() {
    const $header = $(".site-header");
    const $btn = $(".nav-toggle");
    const $nav = $("#mainNav");
    if (!$header.length || !$btn.length || !$nav.length) return;

    function pastHeroCarousel() {
      const $carousel = $("#heroCarousel");
      if (!$carousel.length) return $(window).scrollTop() > 24;
      const end = $carousel.offset().top + $carousel.outerHeight() - window.innerHeight;
      return $(window).scrollTop() > end - 8;
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
    const page = (location.pathname.split("/").pop() || "").toLowerCase();
    if (page === "product.html") return;
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
    if (!root) return;

    const slides = root.querySelectorAll(".hero-slide");
    const navButtons = root.querySelectorAll("[data-hero-goto]");
    const total = slides.length;
    if (!total) return;

    const reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    let current = 0;
    let locked = false;
    let wheelAcc = 0;
    let touchY = 0;

    function heroLocked() {
      const rect = root.getBoundingClientRect();
      return rect.top > -24 && rect.top < 24 && rect.bottom > window.innerHeight - 24;
    }

    function setSlide(index) {
      const next = Math.max(0, Math.min(total - 1, index));
      current = next;
      slides.forEach(function (slide, i) {
        slide.style.opacity = "";
        slide.style.transform = "";
        slide.style.zIndex = "";
        slide.style.pointerEvents = "";
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

    function go(step) {
      const next = current + step;
      if (next < 0) return;
      if (next >= total) {
        leaveHero();
        return;
      }
      setSlide(next);
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
      });
    });

    const nextBtn = root.querySelector("[data-hero-next]");
    if (nextBtn) {
      nextBtn.addEventListener("click", function () {
        go(1);
      });
    }

    document.body.classList.add("is-in-hero");
    window.addEventListener(
      "scroll",
      function () {
        document.body.classList.toggle("is-in-hero", heroLocked() || root.getBoundingClientRect().top >= 0);
      },
      { passive: true }
    );
    setSlide(0);
  }

  function onShell() {
    initHeader();
    initBackToTop();
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
  $(initForms);
})(jQuery);
