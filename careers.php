<?php
declare(strict_types=1);
require_once __DIR__ . '/admin/bootstrap.php';

$copy = page_copy('careers');
$jobs = open_jobs();
usort($jobs, static function (array $a, array $b): int {
    $byDate = strcmp((string) ($b['posted'] ?? ''), (string) ($a['posted'] ?? ''));
    return $byDate !== 0 ? $byDate : strcmp((string) $a['title'], (string) $b['title']);
});
$jobSlug = isset($_GET['job']) ? slugify((string) $_GET['job']) : '';
$sent = isset($_GET['sent']);
$sendError = isset($_GET['error']);
$sprite = url_for('assets/img/sprite.svg');

if ($jobSlug !== '') {
    $job = job_by_slug($jobSlug);
    if (!$job) {
        render_not_found([
            'title' => 'Role not found',
            'description' => 'That role is no longer open. See current openings at ' . brand_name() . '.',
            'canonical' => 'careers/' . rawurlencode($jobSlug),
            'heading' => "We couldn't find that role.",
            'body' => 'It may have been filled. Browse the roles that are still open.',
            'actions' => [['label' => 'All jobs', 'href' => 'careers', 'primary' => true]],
        ]);
    }

    $crumbs = ['Home' => '/', 'Careers' => 'careers', $job['title'] => null];
    $postedLabel = job_date_label($job['posted']);
    page_head([
        'title' => $job['title'] . ' | Careers',
        'description' => $job['summary'] !== '' ? $job['summary'] : 'Apply for ' . $job['title'] . ' at ' . brand_name() . '.',
        'canonical' => 'careers/' . rawurlencode($job['slug']),
        'breadcrumbs' => $crumbs,
        'schema' => array_filter([schema_job($job)]),
    ]);
    render_page_banner($copy, $crumbs, 'photo', $job['title']);
    ?>
    <section class="section jobs-page">
      <div class="container jobs-detail">
        <a class="jobs-back" href="<?= e(url_for('careers')) ?>">All jobs</a>
        <h2><?= e($job['title']) ?></h2>
        <p class="jobs-card-count"><?= e(job_openings_label((int) $job['openings'])) ?></p>
        <?php if ($job['location'] !== ''): ?>
          <p class="jobs-card-meta">
            <svg width="16" height="16" aria-hidden="true"><use href="<?= e($sprite) ?>#icon-pin"></use></svg>
            <?= e($job['location']) ?>
          </p>
        <?php endif; ?>
        <?php if ($postedLabel !== ''): ?>
          <p class="jobs-card-meta">
            <svg width="16" height="16" aria-hidden="true"><use href="<?= e($sprite) ?>#icon-clock"></use></svg>
            <?= e($postedLabel) ?>
          </p>
        <?php endif; ?>
        <?php if ($job['type'] !== '' || $job['department'] !== ''): ?>
          <p class="jobs-card-meta"><?= e(implode(' · ', array_values(array_filter([$job['type'], $job['department']])))) ?></p>
        <?php endif; ?>
        <?php if ($job['summary'] !== ''): ?>
          <p class="jobs-detail-summary"><?= e($job['summary']) ?></p>
        <?php endif; ?>
        <?php if ($job['duties']): ?>
          <ul class="role-duties">
            <?php foreach ($job['duties'] as $duty): ?>
              <li>
                <svg width="16" height="16" aria-hidden="true"><use href="<?= e($sprite) ?>#icon-check"></use></svg>
                <?= e($duty) ?>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>

        <div class="tile tile--static jobs-apply" id="apply">
          <span class="eyebrow"><?= e(page_text($copy, 'applyEyebrow')) ?></span>
          <h3><?= e(page_text($copy, 'applyTitle')) ?></h3>
          <?php if (page_text($copy, 'applyIntro') !== ''): ?>
            <p class="jobs-apply-intro"><?= e(page_text($copy, 'applyIntro')) ?></p>
          <?php endif; ?>
          <?php if ($sent): ?>
            <p class="form-feedback is-success" role="status">Application received. Our team will reply within one business day.</p>
          <?php elseif ($sendError): ?>
            <p class="form-feedback is-error" role="alert">We could not send that just now. Please try again or call us.</p>
          <?php endif; ?>
          <form method="post" action="<?= e(url_for('contact-submit.php')) ?>" enctype="multipart/form-data" data-validate novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="source" value="career">
            <input type="hidden" name="return" value="careers/<?= e($job['slug']) ?>">
            <input type="hidden" name="category" value="Career — <?= e($job['title']) ?>">
            <div class="hp" aria-hidden="true">
              <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
            </div>
            <div class="form-grid">
              <div>
                <label class="form-label" for="careerName">Full name</label>
                <input class="form-control" type="text" id="careerName" name="fullName" required>
              </div>
              <div>
                <label class="form-label" for="careerPhone">Phone</label>
                <input class="form-control" type="tel" id="careerPhone" name="phone" required>
              </div>
              <div class="full">
                <label class="form-label" for="careerEmail">Email</label>
                <input class="form-control" type="email" id="careerEmail" name="email" required>
              </div>
              <div class="full">
                <label class="form-label" for="careerMessage">Cover note</label>
                <textarea class="form-control" id="careerMessage" name="message" rows="5" required placeholder="Sites you have worked, and when you can join."></textarea>
              </div>
              <div class="full resume-field">
                <label class="form-label" for="careerResume">Resume</label>
                <input type="file" id="careerResume" name="resume" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document">
                <span class="form-hint">PDF or Word, up to 5 MB.</span>
              </div>
            </div>
            <button class="btn btn-primary mt-4" type="submit"><?= e(page_text($copy, 'applyButton')) ?></button>
          </form>
        </div>
      </div>
    </section>
    <?php
    page_foot();
    return;
}

$locations = [];
$types = [];
foreach ($jobs as $job) {
    if ($job['location'] !== '') {
        $locations[$job['location']] = $job['location'];
    }
    if ($job['type'] !== '') {
        $types[$job['type']] = $job['type'];
    }
}
natcasesort($locations);
natcasesort($types);

$pickedLocations = array_values(array_filter((array) ($_GET['location'] ?? []), static fn ($value): bool => is_string($value) && isset($locations[$value])));
$pickedTypes = array_values(array_filter((array) ($_GET['type'] ?? []), static fn ($value): bool => is_string($value) && isset($types[$value])));
$visible = array_values(array_filter($jobs, static function (array $job) use ($pickedLocations, $pickedTypes): bool {
    if ($pickedLocations && !in_array($job['location'], $pickedLocations, true)) {
        return false;
    }
    if ($pickedTypes && !in_array($job['type'], $pickedTypes, true)) {
        return false;
    }
    return true;
}));
$filtering = $pickedLocations || $pickedTypes;

$crumbs = ['Home' => '/', 'Careers' => null];
$jobSchema = [];
foreach ($jobs as $job) {
    $node = schema_job($job);
    if ($node) {
        $jobSchema[] = $node;
    }
}

page_head([
    'title' => 'Careers',
    'description' => 'Open roles at ' . brand_name() . '. Apply for field, sales, warehouse, and service jobs.',
    'canonical' => 'careers',
    'breadcrumbs' => $crumbs,
    'schema' => $jobSchema,
]);
render_page_banner($copy, $crumbs);
?>
<section class="section jobs-page">
  <div class="container<?= $jobs ? ' jobs-board' : '' ?>">
    <?php if ($jobs): ?>
      <form class="jobs-filters" method="get" action="<?= e(url_for('careers')) ?>">
        <?php if ($locations): ?>
          <fieldset class="jobs-filter-group">
            <legend>Job location</legend>
            <?php foreach ($locations as $location): ?>
              <label>
                <input type="checkbox" name="location[]" value="<?= e($location) ?>" <?= in_array($location, $pickedLocations, true) ? 'checked' : '' ?> onchange="this.form.requestSubmit()">
                <span><?= e($location) ?></span>
              </label>
            <?php endforeach; ?>
          </fieldset>
        <?php endif; ?>
        <?php if ($types): ?>
          <fieldset class="jobs-filter-group">
            <legend>Employment type</legend>
            <?php foreach ($types as $type): ?>
              <label>
                <input type="checkbox" name="type[]" value="<?= e($type) ?>" <?= in_array($type, $pickedTypes, true) ? 'checked' : '' ?> onchange="this.form.requestSubmit()">
                <span><?= e($type) ?></span>
              </label>
            <?php endforeach; ?>
          </fieldset>
        <?php endif; ?>
        <noscript><button class="btn btn-primary" type="submit">Filter</button></noscript>
      </form>
    <?php endif; ?>

    <div class="jobs-list">
      <?php if (!$jobs): ?>
        <p class="jobs-empty">No openings right now. Check back soon.</p>
      <?php elseif (!$visible): ?>
        <p class="jobs-empty">No jobs match those filters. <a href="<?= e(url_for('careers')) ?>">Clear filters</a></p>
      <?php else: ?>
        <?php foreach ($visible as $job): ?>
          <?php $postedLabel = job_date_label($job['posted']); ?>
          <a class="jobs-card" href="<?= e(url_for('careers/' . rawurlencode($job['slug']))) ?>">
            <h2><?= e($job['title']) ?></h2>
            <p class="jobs-card-count"><?= e(job_openings_label((int) $job['openings'])) ?></p>
            <?php if ($job['location'] !== ''): ?>
              <p class="jobs-card-meta">
                <svg width="16" height="16" aria-hidden="true"><use href="<?= e($sprite) ?>#icon-pin"></use></svg>
                <?= e($job['location']) ?>
              </p>
            <?php endif; ?>
            <?php if ($postedLabel !== ''): ?>
              <p class="jobs-card-meta">
                <svg width="16" height="16" aria-hidden="true"><use href="<?= e($sprite) ?>#icon-clock"></use></svg>
                <?= e($postedLabel) ?>
              </p>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>
        <?php if ($filtering): ?>
          <p class="jobs-clear"><a href="<?= e(url_for('careers')) ?>">Clear filters</a></p>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php page_foot(); ?>
