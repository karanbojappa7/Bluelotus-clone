(function ($) {
  const CFG = window.SITE_CONFIG;
  if (!CFG) return;

  function icon(name) {
    return window.SITE_SHELL.icon(name, 18);
  }

  function getPath(obj, path) {
    return path.split(".").reduce(function (acc, key) {
      return acc == null ? acc : acc[key];
    }, obj);
  }

  function applyTextBindings() {
    $("[data-config]").each(function () {
      const value = getPath(CFG, $(this).attr("data-config"));
      if (value !== undefined && value !== null) $(this).text(value);
    });
  }

  function applyAttrBindings(attr, target, prefix) {
    $("[data-config-" + attr + "]").each(function () {
      const value = getPath(CFG, $(this).attr("data-config-" + attr));
      if (value !== undefined && value !== null) {
        $(this).attr(target, prefix ? prefix + value : value);
      }
    });
  }

  function phoneHref(number) {
    return "tel:" + String(number).replace(/[^\d+]/g, "");
  }

  function formatDate(date, opts) {
    return new Date(date).toLocaleDateString("en-GB", opts);
  }

  function fill(selector, html) {
    const $mount = $(selector);
    if ($mount.length) $mount.html(html);
  }

  function buildNavCategories() {
    fill(
      "[data-render='nav-categories']",
      CFG.categories
        .map(function (c) {
          return `<li><a class="dropdown-item" href="products.html#${c.id}">${c.name}</a></li>`;
        })
        .join("")
    );
  }

  function buildFooterCategories() {
    fill(
      "[data-render='footer-categories']",
      CFG.categories
        .slice(0, 8)
        .map(function (c) {
          return `<li><a href="products.html#${c.id}">${c.name}</a></li>`;
        })
        .join("")
    );
  }

  function buildStats() {
    fill(
      "[data-render='stats']",
      CFG.stats
        .map(function (s) {
          return `<div class="stat-plate">
            <div class="value" data-count="${s.value}" data-suffix="${s.suffix}">0${s.suffix}</div>
            <div class="label">${s.label}</div>
          </div>`;
        })
        .join("")
    );
  }

  function buildCategories(limit) {
    const list = limit ? CFG.categories.slice(0, Number(limit)) : CFG.categories;
    fill(
      "[data-render='categories']",
      list
        .map(function (c) {
          return `<a class="tile" id="${c.id}" href="products.html#${c.id}" data-reveal>
            <div class="icon-wrap">${icon(c.icon)}</div>
            <h3>${c.name}</h3>
            <p>${c.desc}</p>
            <span class="tile-link">View Range ${icon("arrow")}</span>
          </a>`;
        })
        .join("")
    );
  }

  function buildServices() {
    const $tabs = $("[data-render='service-tabs']");
    const $panels = $("[data-render='service-panels']");
    if (!$tabs.length || !$panels.length) return;
    $tabs.html(
      CFG.services
        .map(function (s, i) {
          return `<button type="button" class="service-tab-btn${i === 0 ? " is-active" : ""}" data-tab-target="panel-${s.id}">${s.name}</button>`;
        })
        .join("")
    );
    $panels.html(
      CFG.services
        .map(function (s, i) {
          return `<div class="service-panel${i === 0 ? " is-active" : ""}" id="panel-${s.id}">
            <div class="service-visual">
              <div class="icon-wrap">${icon(s.icon)}</div>
              <h3>${s.name}</h3>
              <p>${s.summary}</p>
            </div>
            <div class="service-panel-body">
              <span class="eyebrow">Scope of Work</span>
              <ul class="service-panel-points">${s.points.map(function (p) { return `<li>${p}</li>`; }).join("")}</ul>
              <a href="contact.html" class="btn btn-outline">Request This Service ${icon("arrow")}</a>
            </div>
          </div>`;
        })
        .join("")
    );
  }

  function buildTestimonials() {
    fill(
      "[data-render='testimonials']",
      CFG.testimonials
        .map(function (t) {
          return `<div class="testimonial-card" data-reveal>
            <p class="testimonial-quote">${t.quote}</p>
            <div class="testimonial-author">
              <span class="name">${t.name}</span>
              <span class="role">${t.role}</span>
            </div>
          </div>`;
        })
        .join("")
    );
  }

  function buildBlog(limit) {
    const list = limit ? CFG.blog.slice(0, Number(limit)) : CFG.blog;
    fill(
      "[data-render='blog']",
      list
        .map(function (b) {
          return `<article class="blog-card" data-reveal>
            <a class="blog-card-media" href="blog-single.html?post=${b.slug}">
              <span class="blog-card-date">${formatDate(b.date, { day: "2-digit", month: "short" })}</span>
              <img src="${b.image}" alt="${b.title}" width="600" height="400" loading="lazy">
            </a>
            <div class="blog-card-body">
              <h3><a href="blog-single.html?post=${b.slug}">${b.title}</a></h3>
              <p>${b.excerpt}</p>
              <a class="blog-read-more" href="blog-single.html?post=${b.slug}">Read More ${icon("arrow")}</a>
            </div>
          </article>`;
        })
        .join("")
    );
  }

  function buildClients() {
    const doubled = CFG.clients.concat(CFG.clients);
    fill(
      "[data-render='clients']",
      doubled
        .map(function (c) {
          return `<span>${c}</span>`;
        })
        .join("")
    );
  }

  function buildFooterContact() {
    const phones = CFG.contact.phones
      .map(function (p) {
        return `<li><a href="${phoneHref(p.number)}">${p.number}</a></li>`;
      })
      .join("");
    const emails = CFG.contact.emails
      .map(function (e) {
        return `<li><a href="mailto:${e.address}">${e.address}</a></li>`;
      })
      .join("");
    const address = `<li>${CFG.contact.address.line1}, ${CFG.contact.address.line2}</li>`;
    fill("[data-render='footer-contact']", phones + emails + address);
  }

  function buildContactCards() {
    const cards = [
      { icon: "phone", title: "Call Us", body: CFG.contact.phones.map(function (p) { return p.number; }).join(" / ") },
      { icon: "mail", title: "Email Us", body: CFG.contact.emails.map(function (e) { return e.address; }).join(" / ") },
      { icon: "pin", title: "Visit Us", body: CFG.contact.address.line1 + ", " + CFG.contact.address.line2 },
      { icon: "clock", title: "Working Hours", body: "Mon – Sat, 9:00 AM – 7:00 PM" }
    ];
    fill(
      "[data-render='contact-cards']",
      cards
        .map(function (c) {
          return `<div class="contact-card">
            <div class="icon-wrap">${icon(c.icon)}</div>
            <div><h4>${c.title}</h4><p>${c.body}</p></div>
          </div>`;
        })
        .join("")
    );
  }

  function setMeta() {
    const $html = $("html");
    const title = $html.attr("data-page-title");
    const desc = $html.attr("data-page-desc") || CFG.seo.defaultDescription;
    document.title = title ? title + " | " + CFG.company.name : CFG.company.name + " | " + CFG.seo.defaultTitle;
    $("meta[name='description']").attr("content", desc);
    $("meta[name='keywords']").attr("content", CFG.seo.keywords);
    const pageFile = location.pathname.split("/").pop() || "";
    $("link[rel='canonical']").attr("href", pageFile ? CFG.seo.domain + "/" + pageFile : CFG.seo.domain + "/");
    $("meta[property='og:title']").attr("content", document.title);
    $("meta[property='og:description']").attr("content", desc);
    if (!$("meta[property='og:image']").length) {
      $("head").append('<meta property="og:image">');
    }
    $("meta[property='og:image']").attr("content", CFG.seo.domain + "/assets/img/og-cover.jpg");
  }

  function injectSchema() {
    const schema = {
      "@context": "https://schema.org",
      "@type": "LocalBusiness",
      name: CFG.company.name,
      description: CFG.seo.defaultDescription,
      url: CFG.seo.domain,
      telephone: CFG.contact.phones[0].number,
      email: CFG.contact.emails[0].address,
      image: CFG.seo.domain + "/assets/img/og-cover.jpg",
      address: {
        "@type": "PostalAddress",
        streetAddress: CFG.contact.address.line1,
        addressLocality: "Berhampur",
        addressRegion: "Odisha",
        postalCode: "760010",
        addressCountry: "IN"
      },
      sameAs: Object.values(CFG.social)
    };
    $("head").append($("<script>", { type: "application/ld+json" }).text(JSON.stringify(schema)));
  }

  function init() {
    setMeta();
    injectSchema();
    applyTextBindings();
    applyAttrBindings("href", "href");
    applyAttrBindings("src", "src");
    applyAttrBindings("tel", "href", "tel:");
    applyAttrBindings("mailto", "href", "mailto:");
    buildNavCategories();
    buildFooterCategories();
    buildFooterContact();
    buildStats();
    buildCategories($("body").attr("data-categories-limit"));
    buildServices();
    buildTestimonials();
    buildBlog($("body").attr("data-blog-limit"));
    buildClients();
    buildContactCards();
    $(document).trigger("config:rendered");
  }

  window.SITE_SHELL.whenReady(init);
})(jQuery);
