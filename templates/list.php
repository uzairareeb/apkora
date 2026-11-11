<div class="wrap">
  <nav class="crumbs" aria-label="Breadcrumb"><a href="<?= e(url('')) ?>">Home</a> › <span><?= e($heading) ?></span></nav>
  <section class="box">
    <div class="box-head"><div><h1 class="h2"><?= e($heading) ?></h1><?php if (!empty($intro)): ?><p><?= e($intro) ?></p><?php endif; ?></div></div>
    <?php if ($games): ?>
      <div class="grid"><?php foreach ($games as $g) echo game_card($g); ?></div>
      <?= $pager ?>
    <?php else: ?><p class="empty">No games found.</p><?php endif; ?>
  </section>
</div>
