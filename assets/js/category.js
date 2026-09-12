(function ($) {
  const PER_PAGE = 12;

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

  function pageLink(catId, page) {
    return "category.html?cat=" + encodeURIComponent(catId) + "&page=" + page;
  }

  function renderPagination(catId, page, pages) {
    if (pages <= 1) {
      $("#catPagination").empty();
      return;
    }

    let html = '<nav class="pagination" aria-label="Product pagination"><ul>';
    html += `<li><a class="pagination-btn${page <= 1 ? " is-disabled" : ""}" href="${page <= 1 ? "#" : pageLink(catId, page - 1)}"${page <= 1 ? ' aria-disabled="true"' : ""}>Prev</a></li>`;

    for (let i = 1; i <= pages; i++) {
      if (pages > 9 && Math.abs(i - page) > 2 && i !== 1 && i !== pages) {
        if (i === 2 || i === pages - 1) {
          html += '<li><span class="pagination-ellipsis">…</span></li>';
        }
        continue;
      }
      html += `<li><a class="pagination-btn${i === page ? " is-active" : ""}" href="${pageLink(catId, i)}">${i}</a></li>`;
    }

    html += `<li><a class="pagination-btn${page >= pages ? " is-disabled" : ""}" href="${page >= pages ? "#" : pageLink(catId, page + 1)}"${page >= pages ? ' aria-disabled="true"' : ""}>Next</a></li>`;
    html += "</ul></nav>";
    $("#catPagination").html(html);
  }

  function render() {
    const CFG = window.SITE_CONFIG;
    if (!CFG) return;
    const params = new URLSearchParams(location.search);
    const id = params.get("cat") || "road-traffic-safety";
    const cat = CFG.categories.find(function (c) { return c.id === id; }) || CFG.categories[0];
    const products = productsInCategory(cat.id);
    const pages = Math.max(1, Math.ceil(products.length / PER_PAGE));
    let page = parseInt(params.get("page") || "1", 10);
    if (isNaN(page) || page < 1) page = 1;
    if (page > pages) page = pages;
    const start = (page - 1) * PER_PAGE;
    const slice = products.slice(start, start + PER_PAGE);

    document.title = cat.name + " | " + CFG.company.name;
    $("meta[name='description']").attr("content", cat.intro || cat.desc);
    $("link[rel='canonical']").attr("href", CFG.seo.domain + "/category.html?cat=" + cat.id + (page > 1 ? "&page=" + page : ""));

    $("#catEyebrow").text("Product Category");
    $("#catTitle").text(cat.name);
    $("#catBreadcrumb").html(
      `<a href="index.html">Home</a> / <a href="products.html">Products</a> / <span>${cat.name}</span>`
    );
    $("#catHeadline").text(cat.headline || cat.name);
    $("#catIntro").text(cat.intro || cat.desc);
    $("#catBuyers").text(cat.buyers || "");
    $("#catGridTitle").text(cat.name + " products");
    $("#catCount").text(
      products.length
        ? `Showing ${start + 1}–${Math.min(start + PER_PAGE, products.length)} of ${products.length} products`
        : "0 products available"
    );

    $("#catUseCases").html(
      (cat.useCases || [])
        .map(function (u, i) {
          return `<div class="use-case">
            <span class="feature-num">${String(i + 1).padStart(2, "0")}</span>
            <div>
              <h3>${u.title}</h3>
              <p>${u.text}</p>
            </div>
          </div>`;
        })
        .join("")
    );

    $("#catOtherList").html(
      CFG.categories
        .filter(function (c) { return c.id !== cat.id; })
        .map(function (c) {
          return `<li><a href="category.html?cat=${c.id}">${c.name}</a></li>`;
        })
        .join("")
    );

    $("#catProductGrid").html(slice.map(productCard).join(""));
    renderPagination(cat.id, page, pages);

    $("#catFaqs").html(
      (cat.faqs || [])
        .map(function (f) {
          return `<details class="faq-item">
            <summary>${f.q}</summary>
            <p>${f.a}</p>
          </details>`;
        })
        .join("")
    );

    $("#catWhatsapp").attr(
      "href",
      whatsappUrl("Hello, I would like product details for the " + cat.name + " category.")
    );

    $(document).trigger("config:rendered");
  }

  window.SITE_SHELL.whenReady(render);
})(jQuery);
