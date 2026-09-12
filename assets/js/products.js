(function ($) {
  const PER_PAGE = 24;

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

  function catalogLink(cat, page) {
    const params = new URLSearchParams();
    if (cat) params.set("cat", cat);
    if (page > 1) params.set("page", String(page));
    const query = params.toString();
    return "products.html" + (query ? "?" + query : "");
  }

  function renderPagination(cat, page, pages) {
    if (pages <= 1) {
      $("#productPagination").empty();
      return;
    }
    let html = '<nav class="pagination" aria-label="Product pagination"><ul>';
    html += `<li><a class="pagination-btn${page <= 1 ? " is-disabled" : ""}" href="${page <= 1 ? "#" : catalogLink(cat, page - 1)}"${page <= 1 ? ' aria-disabled="true"' : ""}>Prev</a></li>`;
    for (let i = 1; i <= pages; i++) {
      html += `<li><a class="pagination-btn${i === page ? " is-active" : ""}" href="${catalogLink(cat, i)}">${i}</a></li>`;
    }
    html += `<li><a class="pagination-btn${page >= pages ? " is-disabled" : ""}" href="${page >= pages ? "#" : catalogLink(cat, page + 1)}"${page >= pages ? ' aria-disabled="true"' : ""}>Next</a></li>`;
    html += "</ul></nav>";
    $("#productPagination").html(html);
  }

  function render() {
    const CFG = window.SITE_CONFIG;
    if (!CFG) return;
    const params = new URLSearchParams(location.search);
    const cat = params.get("cat") || "";
    const all = CFG.products || [];
    const products = cat ? all.filter(function (p) { return p.category === cat; }) : all;
    const pages = Math.max(1, Math.ceil(products.length / PER_PAGE));
    let page = parseInt(params.get("page") || "1", 10);
    if (isNaN(page) || page < 1) page = 1;
    if (page > pages) page = pages;
    const start = (page - 1) * PER_PAGE;
    const slice = products.slice(start, start + PER_PAGE);

    $("#productFilters").html(
      [{ id: "", name: "All products" }].concat(CFG.categories || []).map(function (item) {
        const active = (item.id || "") === cat ? " is-active" : "";
        return `<a class="filter-chip${active}" href="${catalogLink(item.id || "", 1)}">${item.name}</a>`;
      }).join("")
    );

    $("#productCount").text(
      products.length
        ? "Showing " + (start + 1) + "–" + Math.min(start + PER_PAGE, products.length) + " of " + products.length + " products"
        : "No products in this category yet."
    );
    $("#productCatalog").html(slice.map(productCard).join(""));
    renderPagination(cat, page, pages);
    $(document).trigger("config:rendered");
  }

  window.SITE_SHELL.whenReady(render);
})(jQuery);
