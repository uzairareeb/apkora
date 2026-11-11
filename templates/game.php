<?php
$shots = array_values(array_filter(array_map('trim', preg_split('/\R/', (string)$g['screenshots']))));
?>
<div class="wrap">
  <nav class="crumbs" aria-label="Breadcrumb">
    <a href="<?= e(url('')) ?>">Home</a> ›
    <?php if (!empty($g['cat_name'])): ?><a href="<?= e(url('category/' . $g['cat_slug'])) ?>"><?= e($g['cat_name']) ?></a> › <?php endif; ?>
    <span><?= e($g['title']) ?></span>
  </nav>
  <?php if ($g['status'] !== 'published'): ?><div class="notice">Draft preview – visible to admin only.</div><?php endif; ?>
  <article class="game">
    <header class="game-head">
      <img src="<?= e(img_url($g['icon'])) ?>" alt="<?= e($g['title']) ?> icon" width="132" height="132">
      <div class="game-title">
        <h1><?= e($g['title']) ?></h1>
        <?php if ($g['developer'] !== ''): ?><p class="dev"><?= e($g['developer']) ?></p><?php endif; ?>
        <div class="badges">
          <span class="badge type"><?= e(type_label($g)) ?></span>
          <?php if (!empty($g['cat_name'])): ?><a class="badge" href="<?= e(url('category/' . $g['cat_slug'])) ?>"><?= e($g['cat_name']) ?></a><?php endif; ?>
          <?php if ((float)$g['rating'] > 0): ?><span class="badge">★ <?= number_format((float)$g['rating'], 1) ?></span><?php endif; ?>
          <?php if ($g['size'] !== ''): ?><span class="badge"><?= e($g['size']) ?></span><?php endif; ?>
        </div>
        <?php if ($g['download_url'] !== ''): ?>
          <a class="btn dl" rel="nofollow noopener" href="<?= e(url('go/' . $g['slug'])) ?>">⬇ Download Now</a>
        <?php else: ?><span class="btn dl disabled">Download unavailable</span><?php endif; ?>
      </div>
    </header>

    <dl class="facts">
      <?php if ($g['size'] !== ''): ?><div><dt>Size</dt><dd><?= e($g['size']) ?></dd></div><?php endif; ?>
      <?php if ($g['version'] !== ''): ?><div><dt>Version</dt><dd><?= e($g['version']) ?></dd></div><?php endif; ?>
      <?php if ($g['developer'] !== ''): ?><div><dt>Developer</dt><dd><?= e($g['developer']) ?></dd></div><?php endif; ?>
      <?php if ($g['installs'] !== ''): ?><div><dt>Installs</dt><dd><?= e($g['installs']) ?></dd></div><?php endif; ?>
      <div><dt>Downloads</dt><dd><?= number_format((int)$g['downloads']) ?></dd></div>
      <div><dt>Views</dt><dd><?= number_format((int)$g['views']) ?></dd></div>
      <div><dt>Updated</dt><dd><?= e(date('M j, Y', strtotime($g['updated_at']))) ?></dd></div>
    </dl>

    <?php if ($shots): ?>
      <h2>Screenshots</h2>
      <div class="shots"><?php foreach ($shots as $s): ?><img loading="lazy" src="<?= e(img_url($s)) ?>" alt="<?= e($g['title']) ?> screenshot"><?php endforeach; ?></div>
    <?php endif; ?>

    <h2>About <?= e($g['title']) ?></h2>
    <div class="desc" id="desc"><div class="prose"><?= format_description((string)$g['description']) ?></div></div>
    <button type="button" class="readmore" id="readmore" hidden>Read more</button>
    <?php if (trim((string)$g['extra_content']) !== ''): ?><div class="prose extra"><?= $g['extra_content'] ?></div><?php endif; ?>

    <?php if ($g['download_url'] !== ''): ?>
      <p class="center"><a class="btn dl" rel="nofollow noopener" href="<?= e(url('go/' . $g['slug'])) ?>">⬇ Download <?= e($g['title']) ?></a></p>
    <?php endif; ?>
  </article>

  <?php if ($related): ?>
    <section class="box">
      <div class="box-head"><div><h2>You may also like</h2></div></div>
      <div class="grid"><?php foreach ($related as $r) echo game_card($r); ?></div>
    </section>
  <?php endif; ?>
</div>
