(function ($) {
  function render() {
    const CFG = window.SITE_CONFIG;
    if (!CFG) return;
    const slug = new URLSearchParams(location.search).get("post");
    const post = CFG.blog.find(function (b) {
      return b.slug === slug;
    }) || CFG.blog[0];

    $("#articleTitle").text(post.title);
    $("#articleCrumb").text(post.title);
    $("#articleDate").text(
      new Date(post.date).toLocaleDateString("en-GB", {
        day: "2-digit",
        month: "long",
        year: "numeric"
      })
    );
    $("#articleExcerpt").text(post.excerpt);
    $("#articleMedia").attr({ src: post.image, alt: post.title });
    $("#articleBody").html(
      "<p>Sites we work on ask the same question first: where does exposure actually sit today, and what's the fastest compliant fix. This article walks through the practical checklist our engineers run before recommending any product line.</p>" +
        "<p>Every recommendation in our catalog is checked against IS and ISO standards before it reaches a project spec, and installation follows a documented commissioning process so nothing is left as a guess once our crew leaves site.</p>" +
        "<p>If you'd like this assessed for your own site, our consultancy team can scope a visit within a week.</p>"
    );
    document.title = post.title + " | " + CFG.company.name;

    $("#relatedList").html(
      CFG.blog
        .filter(function (b) {
          return b.slug !== post.slug;
        })
        .slice(0, 3)
        .map(function (b) {
          return `<a href="blog-single.html?post=${b.slug}" class="related-link">
            <img src="${b.image}" alt="" width="72" height="54" loading="lazy">
            <strong>${b.title}</strong>
          </a>`;
        })
        .join("")
    );
  }

  $(document).on("config:rendered", render);
})(jQuery);
