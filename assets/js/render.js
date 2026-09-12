(function ($) {
  const CFG = window.SITE_CONFIG;
  if (!CFG) return;

  const esc = window.SITE_SHELL.esc;

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
    const parsed = new Date(date);
    if (isNaN(parsed.getTime())) return "";
    return parsed.toLocaleDateString("en-GB", opts);
  }

  function fill(selector, html) {
    const $mount = $(selector);
    if ($mount.length) $mount.html(html);
  }

  const categoryHref = window.SITE_SHELL.categoryUrl;
  const postHref = window.SITE_SHELL.postUrl;
  const siteUrl = window.SITE_SHELL.url;

  function buildNavCategories() {
    fill(
      "[data-render='nav-categories']",
      (CFG.categories || [])
        .map(function (c) {
          return `<li><a class="dropdown-item" href="${categoryHref(c.id)}">${esc(c.name)}</a></li>`;
        })
        .join("")
    );
  }

  function buildFooterCategories() {
    fill(
      "[data-render='footer-categories']",
      (CFG.categories || [])
        .slice(0, 8)
        .map(function (c) {
          return `<li><a href="${categoryHref(c.id)}">${esc(c.name)}</a></li>`;
        })
        .join("")
    );
  }

  function buildStats() {
    fill(
      "[data-render='stats']",
      (CFG.stats || [])
        .map(function (s) {
          const suffix = esc(s.suffix);
          return `<div class="stat-plate">
            <div class="value" data-count="${esc(s.value)}" data-suffix="${suffix}">0${suffix}</div>
            <div class="label">${esc(s.label)}</div>
          </div>`;
        })
        .join("")
    );
  }

  function buildProducts(limit) {
    const $mount = $("[data-render='products']");
    if (!$mount.length) return;
    const all = CFG.products || [];
    const list = limit ? all.slice(0, Number(limit)) : all;
    if (!list.length) {
      $mount.html('<p class="empty-note">Our catalog is being updated. Please check back shortly.</p>');
      return;
    }
    $mount.html(list.map(window.SITE_SHELL.productCard).join(""));
  }

  function categoryCardImage(c, index) {
    if (c.image) return siteUrl(c.image);
    if (c.ogImage) return siteUrl(c.ogImage);
    const product = (CFG.products || []).find(function (p) {
      return p.category === c.id && p.images && p.images.length;
    });
    if (product) return siteUrl(product.images[0]);
    const fallbacks = [
      "assets/img/hero.jpg",
      "assets/img/products.jpg",
      "assets/img/about.jpg",
      "assets/img/blog-1.jpg",
      "assets/img/blog-4.jpg",
      "assets/img/blog-2.jpg",
      "assets/img/blog-3.jpg",
      "assets/img/og-cover.jpg"
    ];
    return siteUrl(fallbacks[index % fallbacks.length]);
  }

  function buildCategories(limit) {
    const all = CFG.categories || [];
    const list = limit ? all.slice(0, Number(limit)) : all;
    fill(
      "[data-render='categories']",
      list
        .map(function (c, index) {
          const image = categoryCardImage(c, index).replace(/'/g, "%27");
          return `<a class="tile tile--photo" id="${esc(c.id)}" href="${categoryHref(c.id)}" data-reveal>
            <span class="tile-media" style="background-image:url('${esc(image)}')"></span>
            <span class="tile-copy">
              <span class="icon-wrap">${icon(c.icon)}</span>
              <h3>${esc(c.name)}</h3>
              <p>${esc(c.desc)}</p>
              <span class="tile-link">View Range ${icon("arrow")}</span>
            </span>
          </a>`;
        })
        .join("")
    );
  }

  function buildFaqs() {
    const $mount = $("[data-render='faqs']");
    if (!$mount.length) return;
    fill(
      "[data-render='faqs']",
      (CFG.faqs || [])
        .map(function (f) {
          return `<details class="faq-item"><summary>${esc(f.q)}</summary><p>${esc(f.a)}</p></details>`;
        })
        .join("")
    );
  }

  function buildServices() {
    const $tabs = $("[data-render='service-tabs']");
    const $panels = $("[data-render='service-panels']");
    if (!$tabs.length || !$panels.length) return;
    const services = CFG.services || [];
    if (!services.length) {
      $("#services").hide();
      return;
    }
    $tabs.html(
      services
        .map(function (s, i) {
          const active = i === 0;
          return `<button type="button" role="tab" id="tab-${esc(s.id)}" aria-controls="panel-${esc(s.id)}" aria-selected="${active}" tabindex="${active ? 0 : -1}" class="service-tab-btn${active ? " is-active" : ""}" data-tab-target="panel-${esc(s.id)}">${esc(s.name)}</button>`;
        })
        .join("")
    );
    $panels.html(
      services
        .map(function (s, i) {
          return `<div class="service-panel${i === 0 ? " is-active" : ""}" id="panel-${esc(s.id)}" role="tabpanel" aria-labelledby="tab-${esc(s.id)}">
            <div class="service-visual">
              <div class="icon-wrap">${icon(s.icon)}</div>
              <h3>${esc(s.name)}</h3>
              <p>${esc(s.summary)}</p>
            </div>
            <div class="service-panel-body">
              <span class="eyebrow">Scope of Work</span>
              <ul class="service-panel-points">${(s.points || []).map(function (p) { return `<li>${esc(p)}</li>`; }).join("")}</ul>
              <a href="${siteUrl("contact")}" class="btn btn-outline">Request This Service ${icon("arrow")}</a>
            </div>
          </div>`;
        })
        .join("")
    );
    $tabs.attr("role", "tablist");
  }

  function buildTestimonials() {
    fill(
      "[data-render='testimonials']",
      (CFG.testimonials || [])
        .map(function (t) {
          return `<div class="testimonial-card" data-reveal>
            <p class="testimonial-quote">${esc(t.quote)}</p>
            <div class="testimonial-author">
              <span class="name">${esc(t.name)}</span>
              <span class="role">${esc(t.role)}</span>
            </div>
          </div>`;
        })
        .join("")
    );
  }

  function initials(name) {
    return String(name || "")
      .split(/\s+/)
      .filter(Boolean)
      .slice(0, 2)
      .map(function (word) {
        return word.charAt(0);
      })
      .join("")
      .toUpperCase();
  }

  function buildLeadership() {
    const $mount = $("[data-render='leadership']");
    if (!$mount.length || $mount.children().length) return;
    fill(
      "[data-render='leadership']",
      (CFG.leadership || [])
        .map(function (person) {
          const name = esc(person.name);
          const designation = esc(person.designation || person.role || "");
          const background = esc(person.background || person.bio || "");
          const photo = person.image
            ? `<img src="${esc(siteUrl(person.image))}" alt="${name}" width="480" height="360" loading="lazy" data-fallback>`
            : `<span class="leader-fallback" aria-hidden="true">${esc(initials(person.name))}</span>`;
          const linkedin = person.linkedin
            ? `<a class="leader-in" href="${esc(person.linkedin)}" target="_blank" rel="noopener" aria-label="LinkedIn profile for ${name}">${icon("linkedin")}</a>`
            : "";
          const experience = person.experience
            ? `<div class="leader-meta"><span>Experience</span><p>${esc(person.experience)}</p></div>`
            : "";
          const expertise = person.expertise
            ? `<div class="leader-meta"><span>Area of expertise</span><p>${esc(person.expertise)}</p></div>`
            : "";
          return `<article class="leader-card" data-reveal>
            <div class="leader-photo">${photo}</div>
            <div class="leader-body">
              <h3>${name}</h3>
              <p class="leader-role">${designation}</p>
              ${experience}
              ${expertise}
              <p class="leader-background">${background}</p>
              ${linkedin}
            </div>
          </article>`;
        })
        .join("")
    );
  }

  function buildBlog(limit) {
    const all = CFG.blog || [];
    const list = limit ? all.slice(0, Number(limit)) : all;
    fill(
      "[data-render='blog']",
      list
        .map(function (b) {
          const href = postHref(b.slug);
          const title = esc(b.title);
          return `<article class="blog-card" data-reveal>
            <a class="blog-card-media" href="${href}">
              <span class="blog-card-date">${esc(formatDate(b.date, { day: "2-digit", month: "short" }))}</span>
              <img src="${esc(siteUrl(b.image))}" alt="${title}" width="600" height="400" loading="lazy" data-fallback>
            </a>
            <div class="blog-card-body">
              <h3><a href="${href}">${title}</a></h3>
              <p>${esc(b.excerpt)}</p>
              <a class="blog-read-more" href="${href}">Read More ${icon("arrow")}</a>
            </div>
          </article>`;
        })
        .join("")
    );
  }

  function buildClients() {
    const clients = CFG.clients || [];
    fill(
      "[data-render='clients']",
      clients
        .concat(clients)
        .map(function (c) {
          return `<span>${esc(c)}</span>`;
        })
        .join("")
    );
  }

  function buildFooterContact() {
    const contact = CFG.contact || {};
    const phones = (contact.phones || [])
      .map(function (p) {
        return `<li><a href="${esc(phoneHref(p.number))}">${esc(p.number)}</a></li>`;
      })
      .join("");
    const emails = (contact.emails || [])
      .map(function (e) {
        return `<li><a href="mailto:${esc(e.address)}">${esc(e.address)}</a></li>`;
      })
      .join("");
    const addr = contact.address || {};
    const address = `<li>${esc([addr.line1, addr.line2].filter(Boolean).join(", "))}</li>`;
    fill("[data-render='footer-contact']", phones + emails + address);
  }

  function buildContactCards() {
    const contact = CFG.contact || {};
    const addr = contact.address || {};
    const cards = [
      { icon: "phone", title: "Call Us", body: (contact.phones || []).map(function (p) { return p.number; }).join(" / ") },
      { icon: "mail", title: "Email Us", body: (contact.emails || []).map(function (e) { return e.address; }).join(" / ") },
      { icon: "pin", title: "Visit Us", body: [addr.line1, addr.line2].filter(Boolean).join(", ") },
      { icon: "clock", title: "Working Hours", body: contact.workingHours || "Mon – Sat, 9:00 AM – 7:00 PM" }
    ];
    fill(
      "[data-render='contact-cards']",
      cards
        .map(function (c) {
          return `<div class="contact-card">
            <div class="icon-wrap">${icon(c.icon)}</div>
            <div><h4>${esc(c.title)}</h4><p>${esc(c.body)}</p></div>
          </div>`;
        })
        .join("")
    );
  }

  function setMeta() {
    const $html = $("html");
    const seo = CFG.seo || {};
    const title = $html.attr("data-page-title");
    const desc = $html.attr("data-page-desc") || seo.defaultDescription;
    document.title = title ? title + " | " + CFG.company.name : CFG.company.name + " | " + seo.defaultTitle;
    $("meta[name='description']").attr("content", desc);
    $("meta[name='keywords']").attr("content", seo.keywords);
    const pageFile = location.pathname.split("/").pop() || "";
    $("link[rel='canonical']").attr("href", seo.domain + "/" + pageFile);
    $("meta[property='og:title']").attr("content", document.title);
    $("meta[property='og:description']").attr("content", desc);
    if (!$("meta[property='og:image']").length) {
      $("head").append('<meta property="og:image">');
    }
    $("meta[property='og:image']").attr("content", seo.domain + "/assets/img/og-cover.jpg");
  }

  function injectSchema() {
    const contact = CFG.contact || {};
    const addr = contact.address || {};
    const phone = (contact.phones || [])[0];
    const email = (contact.emails || [])[0];
    const schema = {
      "@context": "https://schema.org",
      "@type": "LocalBusiness",
      name: CFG.company.name,
      description: CFG.seo.defaultDescription,
      url: CFG.seo.domain,
      telephone: phone ? phone.number : undefined,
      email: email ? email.address : undefined,
      image: CFG.seo.domain + "/assets/img/og-cover.jpg",
      address: {
        "@type": "PostalAddress",
        streetAddress: addr.line1,
        addressLocality: "Berhampur",
        addressRegion: "Odisha",
        postalCode: "760010",
        addressCountry: "IN"
      },
      sameAs: Object.values(CFG.social || {}).filter(Boolean)
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
    buildProducts($("body").attr("data-products-limit"));
    buildServices();
    buildFaqs();
    buildTestimonials();
    buildLeadership();
    buildBlog($("body").attr("data-blog-limit"));
    buildClients();
    buildContactCards();
    $(document).trigger("config:rendered");
  }

  window.SITE_SHELL.whenReady(init);
})(jQuery);
