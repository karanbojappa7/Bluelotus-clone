(function ($) {
  const NAV = [
    { href: "index.html", label: "Home" },
    { href: "about.html", label: "About Us" },
    { href: "products.html", label: "Products", dropdown: true },
    { href: "services.html", label: "Services" },
    { href: "blog.html", label: "Blog" },
    { href: "contact.html", label: "Contact Us" }
  ];

  function icon(name, size) {
    const s = size || 18;
    return `<svg width="${s}" height="${s}" aria-hidden="true"><use href="assets/img/sprite.svg#icon-${name}"></use></svg>`;
  }

  function currentPage() {
    return location.pathname.split("/").pop() || "index.html";
  }

  function isActive(href, page) {
    if (href === "blog.html") return page === "blog.html" || page === "blog-single.html";
    return page === href;
  }

  function navItems() {
    const page = currentPage();
    return NAV.map(function (item) {
      const active = isActive(item.href, page) ? " is-active" : "";
      if (item.dropdown) {
        return `<li class="has-dropdown">
          <a href="${item.href}" class="nav-link${active}">${item.label}</a>
          <ul class="dropdown-panel" data-render="nav-categories"></ul>
        </li>`;
      }
      return `<li><a href="${item.href}" class="nav-link${active}">${item.label}</a></li>`;
    }).join("");
  }

  function socialLinks() {
    return ["facebook", "linkedin", "instagram", "youtube"]
      .map(function (net) {
        return `<a data-config-href="social.${net}" target="_blank" rel="noopener" aria-label="${net}">${icon(net, 16)}</a>`;
      })
      .join("");
  }

  function brandHtml() {
    const mark = (window.SITE_CONFIG && window.SITE_CONFIG.company.logoMark) || "assets/img/logo-mark.png";
    return `<a class="brand" href="index.html" aria-label="Bluelotus Infrasafety">
      <span class="brand-mark-wrap">
        <img class="brand-mark" src="${mark}" alt="" width="48" height="48">
      </span>
      <span class="brand-copy">
        <span class="brand-name">BLUE LOTUS</span>
        <span class="brand-sub">INFRASAFETY</span>
      </span>
    </a>`;
  }

  function headerHtml() {
    return `<header class="site-header">
  <div class="container navbar-inner">
    ${brandHtml()}
    <nav class="site-nav" id="mainNav" aria-label="Primary">
      <ul class="nav-links">${navItems()}</ul>
    </nav>
    <div class="nav-actions">
      <a href="contact.html" class="btn btn-primary nav-cta">Get a Quote</a>
      <button class="nav-toggle" type="button" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">${icon("menu", 26)}</button>
    </div>
  </div>
</header>`;
  }

  function footerHtml(compact) {
    if (compact) {
      return `<footer class="site-footer">
  <div class="container">
    <div class="hazard-rule mb-5"></div>
    <div class="footer-bottom footer-bottom--solo">
      <span>© <span data-current-year></span> <span data-config="company.legalName"></span>. All rights reserved.</span>
      <a href="index.html">Back to Home</a>
    </div>
  </div>
</footer>`;
    }
    return `<footer class="site-footer">
  <div class="container">
    <div class="hazard-rule mb-5"></div>
    <div class="footer-grid">
      <div>
        ${brandHtml()}
        <p class="footer-blurb">Certified safety equipment supply, installation, and maintenance for industries, contractors, and government agencies across India.</p>
        <div class="social-row mt-4">${socialLinks()}</div>
      </div>
      <div>
        <h5 class="footer-heading">Company</h5>
        <ul class="footer-links">
          <li><a href="about.html">About Us</a></li>
          <li><a href="products.html">Products</a></li>
          <li><a href="services.html">Services</a></li>
          <li><a href="blog.html">Blog</a></li>
          <li><a href="contact.html">Contact</a></li>
        </ul>
      </div>
      <div>
        <h5 class="footer-heading">Categories</h5>
        <ul class="footer-links" data-render="footer-categories"></ul>
      </div>
      <div>
        <h5 class="footer-heading">Get In Touch</h5>
        <ul class="footer-links" data-render="footer-contact"></ul>
      </div>
    </div>
    <div class="footer-bottom">
      <span>© <span data-current-year></span> <span data-config="company.legalName"></span>. All rights reserved.</span>
      <div class="footer-legal">
        <a href="privacy-policy.html">Privacy Policy</a>
        <a href="sitemap.xml">Sitemap</a>
      </div>
    </div>
  </div>
</footer>`;
  }

  function fabsHtml() {
    return `<a class="whatsapp-fab" href="#" target="_blank" rel="noopener" aria-label="Chat on WhatsApp">${icon("whatsapp", 28)}</a>
<button class="back-to-top" type="button" aria-label="Back to top">${icon("arrow-up", 20)}</button>`;
  }

  function inject() {
    $("[data-site='header']").replaceWith(headerHtml());
    $("[data-site='footer']").each(function () {
      $(this).replaceWith(footerHtml($(this).is("[data-compact]")));
    });
    $("[data-site='fabs']").replaceWith(fabsHtml());
    window.SITE_SHELL.ready = true;
    $(document).trigger("shell:ready");
  }

  window.SITE_SHELL = {
    icon: icon,
    ready: false,
    whenReady: function (cb) {
      if (this.ready) cb();
      else $(document).one("shell:ready", cb);
    }
  };

  $(inject);
})(jQuery);
