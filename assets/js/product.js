(function ($) {
  function productsInCategory(catId) {
    return (window.SITE_CONFIG.products || []).filter(function (p) {
      return p.category === catId;
    });
  }

  function productCard(p) {
    const img = (p.images && p.images[0]) || "assets/img/products.jpg";
    return `<a class="product-card" href="product.html?slug=${p.slug}" data-reveal>
      <span class="product-card-media">
        <img src="${img}" alt="${p.name}" width="480" height="320" loading="lazy">
      </span>
      <span class="product-card-body">
        <strong>${p.name}</strong>
        <em>${p.short}</em>
        <span class="tile-link">View Product ${window.SITE_SHELL.icon("arrow")}</span>
      </span>
    </a>`;
  }

  function whatsappUrl(text) {
    const number = window.SITE_CONFIG.contact.whatsapp.replace("+", "");
    return "https://wa.me/" + number + "?text=" + encodeURIComponent(text);
  }

  function productUrl(slug) {
    const domain = (window.SITE_CONFIG.seo && window.SITE_CONFIG.seo.domain) || window.location.origin;
    return domain.replace(/\/$/, "") + "/product.html?slug=" + encodeURIComponent(slug);
  }

  function productMessage(product, url) {
    return (
      "Hello, I would like to enquire about *" +
      product.name +
      "*.\n\n" +
      product.short +
      "\n\nProduct link: " +
      url
    );
  }

  function shareLinks(product, url) {
    const title = product.name + " | " + window.SITE_CONFIG.company.name;
    const text = product.short || product.name;
    const encodedUrl = encodeURIComponent(url);
    const encodedTitle = encodeURIComponent(title);
    const icon = window.SITE_SHELL.icon;
    const waShare =
      "https://api.whatsapp.com/send?text=" + encodeURIComponent(title + "\n" + text + "\n" + url);

    return [
      { label: "WhatsApp", href: waShare, icon: "whatsapp", className: "share-btn share-btn--wa" },
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
    ]
      .map(function (item) {
        return `<a class="${item.className}" href="${item.href}" target="_blank" rel="noopener" aria-label="Share on ${item.label}">${icon(item.icon, 18)}<span>${item.label}</span></a>`;
      })
      .join("");
  }

  function setGallery(images, name) {
    const list = images && images.length ? images : ["assets/img/products.jpg"];
    $("#prodMainImage").attr({ src: list[0], alt: name });
    $("#prodThumbs").html(
      list
        .map(function (src, i) {
          return `<button type="button" class="product-thumb${i === 0 ? " is-active" : ""}" data-src="${src}" aria-label="View image ${i + 1}">
            <img src="${src}" alt="" width="120" height="90" loading="lazy">
          </button>`;
        })
        .join("")
    );
  }

  function render() {
    const CFG = window.SITE_CONFIG;
    if (!CFG) return;
    const slug = new URLSearchParams(location.search).get("slug") || "spring-post";
    const product = CFG.products.find(function (p) { return p.slug === slug; }) || CFG.products[0];
    const cat = CFG.categories.find(function (c) { return c.id === product.category; });
    const url = productUrl(product.slug);
    const waText = productMessage(product, url);

    document.title = product.name + " | " + CFG.company.name;
    $("meta[name='description']").attr("content", product.short);
    $("link[rel='canonical']").attr("href", url);
    $("meta[property='og:title']").attr("content", product.name + " | " + CFG.company.name);
    $("meta[property='og:description']").attr("content", product.short);
    if (!$("meta[property='og:url']").length) {
      $("head").append('<meta property="og:url">');
    }
    $("meta[property='og:url']").attr("content", url);

    $("#prodName").text(product.name);
    $("#prodShort").text(product.short);
    $("#prodDescription").text(product.description);
    $("#prodCategoryLabel").text(cat ? cat.name : "Products");
    $("#prodCategoryLink").attr("href", "category.html?cat=" + product.category).text(cat ? cat.name : product.category);
    $("#prodBreadcrumb").html(
      `<a href="index.html">Home</a> / <a href="products.html">Products</a> / <a href="category.html?cat=${product.category}">${cat ? cat.name : product.category}</a> / <span>${product.name}</span>`
    );
    $("#prodTags").html(
      (product.tags || [])
        .map(function (t) {
          return `<span class="tag">${t}</span>`;
        })
        .join("")
    );
    $("#prodFeatures").html(
      (product.features || [])
        .map(function (f) {
          return `<li>${f}</li>`;
        })
        .join("")
    );

    setGallery(product.images, product.name);

    $("#prodWhatsapp").attr("href", whatsappUrl(waText));
    $(".whatsapp-fab").attr("href", whatsappUrl(waText));
    $("#prodShare").html(shareLinks(product, url));
    $("#prodBack").attr("href", "category.html?cat=" + product.category);
    $("#prodViewAll").attr("href", "category.html?cat=" + product.category);
    $("#prodPhone").attr("href", "tel:" + CFG.contact.phones[0].number.replace(/[^\d+]/g, "")).text(CFG.contact.phones[0].number);
    $("#prodEmail").attr("href", "mailto:" + CFG.contact.emails[0].address).text(CFG.contact.emails[0].address);

    const related = productsInCategory(product.category)
      .filter(function (p) { return p.slug !== product.slug; })
      .slice(0, 4);
    $("#prodRelated").html(related.map(productCard).join(""));

    $(document).trigger("config:rendered");
  }

  $(document).on("click", ".product-thumb", function () {
    const src = $(this).attr("data-src");
    $("#prodMainImage").attr("src", src);
    $(".product-thumb").removeClass("is-active");
    $(this).addClass("is-active");
  });

  window.SITE_SHELL.whenReady(render);
})(jQuery);
