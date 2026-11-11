<?php
$siteName = setting('site_name', 'Apkora');
$sections = [
  ['Popular', 'Most viewed games and apps right now', 'popular', $popular],
  ['Games', 'Discover the best games', 'games', $games],
  ['Apps', 'Useful and trending apps', 'apps', $apps],
];
?>
<section class="hero">
  <div class="wrap">
    <h1><?= e($siteName) ?></h1>
    <p><?= e(setting('tagline', 'Discover the best games and apps')) ?></p>
    <form class="search big" action="<?= e(url('search')) ?>" method="get" role="search">
      <input type="search" name="q" placeholder="Search games and apps…" aria-label="Search games and apps" required>
      <button type="submit">Search</button>
    </form>
    <?php if ($cats): ?>
    <div class="chips" aria-label="Categories">
      <?php foreach (array_slice($cats, 0, 10) as $c): ?><a href="<?= e(url('category/' . $c['slug'])) ?>"><?= e($c['name']) ?></a><?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>
<div class="wrap">
<?php $any = false; foreach ($sections as $s): if (!$s[3]) continue; $any = true; ?>
  <section class="box" id="<?= e($s[2]) ?>">
    <div class="box-head"><div><h2><?= e($s[0]) ?></h2><p><?= e($s[1]) ?></p></div><a class="more" href="<?= e(url('browse/' . $s[2])) ?>">View all →</a></div>
    <div class="grid"><?php foreach ($s[3] as $g) echo game_card($g); ?></div>
  </section>
<?php endforeach; ?>
<?php if (!$any): ?><section class="box"><p class="empty">Nothing published yet. Add your first game or app from the admin panel.</p></section><?php endif; ?>

<?php if ($faqs): ?>
  <section class="box">
    <div class="box-head"><div><h2>Frequently Asked Questions</h2><p>Quick answers to common questions</p></div></div>
    <div class="faq">
      <?php foreach ($faqs as $f): ?>
        <details><summary><?= e($f['question']) ?></summary><div><?= nl2br(e($f['answer'])) ?></div></details>
      <?php endforeach; ?>
    </div>
  </section>
<?php endif; ?>
</div>
