(function ($) {
  const PER_PAGE = 8;

  function pageLink(page) {
    return "blog.html" + (page > 1 ? "?page=" + page : "");
  }

  function renderPagination(page, pages) {
    if (pages <= 1) {
      $("#blogPagination").empty();
      return;
    }
    let html = '<nav class="pagination" aria-label="Blog pagination"><ul>';
    html += `<li><a class="pagination-btn${page <= 1 ? " is-disabled" : ""}" href="${page <= 1 ? "#" : pageLink(page - 1)}"${page <= 1 ? ' aria-disabled="true"' : ""}>Prev</a></li>`;
    for (let i = 1; i <= pages; i++) {
      html += `<li><a class="pagination-btn${i === page ? " is-active" : ""}" href="${pageLink(i)}">${i}</a></li>`;
    }
    html += `<li><a class="pagination-btn${page >= pages ? " is-disabled" : ""}" href="${page >= pages ? "#" : pageLink(page + 1)}"${page >= pages ? ' aria-disabled="true"' : ""}>Next</a></li>`;
    html += "</ul></nav>";
    $("#blogPagination").html(html);
  }

  function formatDate(iso) {
    try {
      return new Date(iso).toLocaleDateString("en-IN", { day: "2-digit", month: "short", year: "numeric" });
    } catch (e) {
      return iso;
    }
  }

  function render() {
    const CFG = window.SITE_CONFIG;
    if (!CFG || !CFG.blog) return;
    const posts = CFG.blog.slice();
    const pages = Math.max(1, Math.ceil(posts.length / PER_PAGE));
    let page = parseInt(new URLSearchParams(location.search).get("page") || "1", 10);
    if (isNaN(page) || page < 1) page = 1;
    if (page > pages) page = pages;
    const start = (page - 1) * PER_PAGE;
    const slice = posts.slice(start, start + PER_PAGE);
    const icon = window.SITE_SHELL.icon;

    $("#blogGrid").html(
      slice
        .map(function (b) {
          return `<article class="blog-card" data-reveal>
            <a class="blog-card-media" href="blog-single.html?post=${b.slug}">
              <span class="blog-card-date">${formatDate(b.date)}</span>
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
    renderPagination(page, pages);
    $(document).trigger("config:rendered");
  }

  window.SITE_SHELL.whenReady(render);
})(jQuery);
