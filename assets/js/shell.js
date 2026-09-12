(function ($) {
  const BASE = (window.SITE_BASE || "").replace(/\/+$/, "") + "/";

  const NAV = [
    { key: "home", href: "", label: "Home" },
    { key: "about", href: "about", label: "About Us" },
    { key: "products", href: "products", label: "Products", dropdown: true },
    { key: "faq", href: "faq", label: "FAQ" },
    { key: "blog", href: "blog", label: "Blog" },
    { key: "contact", href: "contact", label: "Contact Us" }
  ];

  const ESCAPE_MAP = { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" };

  function esc(value) {
    if (value === null || value === undefined) return "";
    return String(value).replace(/[&<>"']/g, function (ch) {
      return ESCAPE_MAP[ch];
    });
  }

  function url(path) {
    return BASE + String(path || "").replace(/^\/+/, "");
  }

  function icon(name, size) {
    const s = size || 18;
    return `<svg width="${s}" height="${s}" aria-hidden="true"><use href="${url("assets/img/sprite.svg")}#icon-${name}"></use></svg>`;
  }

  const FALLBACK_IMAGE = "assets/img/products.jpg";

  function productUrl(slug) {
    return url("product/" + encodeURIComponent(slug));
  }

  function categoryUrl(id) {
    return url("products/" + encodeURIComponent(id));
  }

  function postUrl(slug) {
    return url("blog/" + encodeURIComponent(slug));
  }

  function productCard(p) {
    const img = (p.images && p.images[0]) || FALLBACK_IMAGE;
    return `<a class="product-card" href="${productUrl(p.slug)}" data-reveal>
      <span class="product-card-media">
        <img src="${esc(url(img))}" alt="${esc(p.name)}" width="480" height="320" loading="lazy" data-fallback>
      </span>
      <span class="product-card-body">
        <strong>${esc(p.name)}</strong>
        <em>${esc(p.short)}</em>
        <span class="tile-link">View Product ${icon("arrow")}</span>
      </span>
    </a>`;
  }

  function currentSection() {
    let path = location.pathname;
    if (BASE !== "/" && path.indexOf(BASE) === 0) {
      path = "/" + path.slice(BASE.length);
    }
    path = path.replace(/^\/+/, "").replace(/\.(html|php)$/, "");
    const first = path.split("/")[0].toLowerCase();

    if (first === "" || first === "index") return "home";
    if (first === "product" || first === "products" || first === "category") return "products";
    if (first === "blog" || first === "blog-single") return "blog";
    return first;
  }

  function navItems() {
    const section = currentSection();
    return NAV.map(function (item) {
      const active = item.key === section ? " is-active" : "";
      const href = url(item.href);
      if (item.dropdown) {
        return `<li class="has-dropdown">
          <a href="${href}" class="nav-link${active}"${active ? ' aria-current="page"' : ""}>${item.label}</a>
          <button type="button" class="dropdown-toggle" aria-label="Show ${item.label} categories" aria-expanded="false">${icon("arrow-up", 16)}</button>
          <ul class="dropdown-panel" data-render="nav-categories"></ul>
        </li>`;
      }
      return `<li><a href="${href}" class="nav-link${active}"${active ? ' aria-current="page"' : ""}>${item.label}</a></li>`;
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
    const cfg = window.SITE_CONFIG;
    const mark = (cfg && cfg.company && cfg.company.logoMark) || "assets/img/logo-mark.png";
    const name = (cfg && cfg.company && cfg.company.name) || "Bluelotus Infrasafety";
    return `<a class="brand" href="${url("")}" aria-label="${esc(name)}">
      <span class="brand-mark-wrap">
        <img class="brand-mark" src="${esc(url(mark))}" alt="" width="48" height="48">
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
      <a href="${url("contact")}" class="btn btn-primary nav-cta">Get a Quote</a>
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
      <a href="${url("")}">Back to Home</a>
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
        <h2 class="footer-heading">Company</h2>
        <ul class="footer-links">
          <li><a href="${url("about")}">About Us</a></li>
          <li><a href="${url("products")}">Products</a></li>
          <li><a href="${url("faq")}">FAQ</a></li>
          <li><a href="${url("blog")}">Blog</a></li>
          <li><a href="${url("contact")}">Contact</a></li>
        </ul>
      </div>
      <div>
        <h2 class="footer-heading">Categories</h2>
        <ul class="footer-links" data-render="footer-categories"></ul>
      </div>
      <div>
        <h2 class="footer-heading">Get In Touch</h2>
        <ul class="footer-links" data-render="footer-contact"></ul>
      </div>
    </div>
    <div class="footer-bottom">
      <span>© <span data-current-year></span> <span data-config="company.legalName"></span>. All rights reserved.</span>
      <div class="footer-legal">
        <a href="${url("privacy-policy")}">Privacy Policy</a>
        <a href="${url("sitemap.xml")}">Sitemap</a>
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
    esc: esc,
    url: url,
    productUrl: productUrl,
    categoryUrl: categoryUrl,
    postUrl: postUrl,
    productCard: productCard,
    fallbackImage: FALLBACK_IMAGE,
    ready: false,
    whenReady: function (cb) {
      if (this.ready) cb();
      else $(document).one("shell:ready", cb);
    }
  };

  $(inject);
})(jQuery);
