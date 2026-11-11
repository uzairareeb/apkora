<?php
require __DIR__ . '/_init.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $opts = ['type' => (string)($_POST['type'] ?? 'auto'), 'category_id' => (int)($_POST['category_id'] ?? 0), 'new_category' => (string)($_POST['new_category'] ?? '')];
    if (!empty($_POST['ajax'])) {
        header('Content-Type: application/json; charset=utf-8');
        try { $r = import_playstore(trim((string)($_POST['link'] ?? '')), $opts); }
        catch (Throwable $ex) { $r = ['ok' => false, 'msg' => 'Error: ' . $ex->getMessage()]; }
        if (!empty($r['ok'])) regenerate_sitemap();
        echo json_encode($r);
        exit;
    }
    // no-JS fallback: import the first link and open it
    $first = trim((string)strtok((string)($_POST['links'] ?? ''), "\n"));
    $r = import_playstore($first, $opts);
    if (!empty($r['ok'])) { regenerate_sitemap(); flash('Imported and published.'); redirect(url('admin/game_edit.php?id=' . $r['id'])); }
    flash($r['msg'] ?? 'Import failed.', 'err');
    redirect(url('admin/import.php'));
}
$cats = rows('SELECT id, name FROM categories ORDER BY sort_order, name');
admin_head('Import from Google Play', 'import'); ?>
<div class="panel narrow">
<form method="post" id="impform"><?= csrf_field() ?>
<label>Google Play link(s) <small>– paste one link, or many links (one per line)</small></label>
<textarea name="links" id="links" rows="5" class="mono" placeholder="https://play.google.com/store/apps/details?id=com.example.game" required></textarea>
<div class="row2">
  <div><label>Type</label><select name="type"><option value="auto">Auto-detect (Game or App)</option><option value="game">Game</option><option value="app">App</option><option value="both">Both (Game &amp; App)</option></select></div>
  <div><label>Category</label><select name="category_id"><option value="0">Auto (use the Play Store category)</option><?php foreach ($cats as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
</div>
<label>Or create a new category <small>(optional – overrides the choice above)</small></label><input type="text" name="new_category" placeholder="e.g. Racing">
<div class="actions"><button class="btn" type="submit" id="impbtn">Fetch &amp; Publish</button><button class="btn alt" type="button" id="starter">Load starter pack (18 popular games &amp; apps)</button></div>
</form>
<div id="implog" class="implog"></div>
<p class="hint">Name, logo, screenshots, description, rating, developer, installs and category are fetched and the item is <strong>published immediately</strong>. Size and version are usually not on the public Play Store page – add them in the edit screen if you want. The download button points to the Play Store link (change it in the edit screen). Large lists import one by one, so keep this page open until it finishes.</p>
</div>
<script>
(function () {
  var EDIT = <?= json_encode(url('admin/game_edit.php?id=')) ?>;
  var base = 'https://play.google.com/store/apps/details?id=';
  var starter = ['com.supercell.clashofclans','com.supercell.brawlstars','com.kiloo.subwaysurf','com.mojang.minecraftpe','com.king.candycrushsaga','com.innersloth.spacemafia','com.roblox.client','com.imangi.templerun2','com.supercell.clashroyale','com.activision.callofduty.shooter','com.whatsapp','com.spotify.music','com.google.android.apps.maps','com.snapchat.android','org.telegram.messenger','com.canva.editor','com.instagram.android','com.netflix.mediaclient'];
  var f = document.getElementById('impform'), ta = document.getElementById('links'), log = document.getElementById('implog'), btn = document.getElementById('impbtn');
  document.getElementById('starter').addEventListener('click', function () { ta.value = starter.map(function (i) { return base + i; }).join('\n'); });
  f.addEventListener('submit', async function (ev) {
    ev.preventDefault();
    var links = ta.value.split(/\r?\n/).map(function (s) { return s.trim(); }).filter(Boolean);
    if (!links.length) return;
    btn.disabled = true; log.innerHTML = '';
    var ok = 0, bad = 0;
    for (var i = 0; i < links.length; i++) {
      var row = document.createElement('div'); row.className = 'logline';
      row.textContent = (i + 1) + '/' + links.length + ' – fetching…'; log.appendChild(row);
      var fd = new FormData(f); fd.delete('links'); fd.set('ajax', '1'); fd.set('link', links[i]);
      try {
        var r = await fetch(location.href, { method: 'POST', body: fd, credentials: 'same-origin' });
        var j = await r.json();
        if (j.ok) { ok++; row.className = 'logline good'; row.innerHTML = '✔ ' + (i + 1) + '/' + links.length + ' – ' + esc(j.title) + ' published · <a href="' + EDIT + j.id + '">edit</a>'; }
        else { bad++; row.className = 'logline bad'; row.textContent = '✖ ' + (i + 1) + '/' + links.length + ' – ' + (j.title ? j.title + ': ' : '') + (j.msg || 'failed'); }
      } catch (e) { bad++; row.className = 'logline bad'; row.textContent = '✖ ' + (i + 1) + '/' + links.length + ' – request failed or timed out'; }
    }
    var s = document.createElement('div'); s.className = 'logsum'; s.innerHTML = 'Done: <strong>' + ok + '</strong> imported, <strong>' + bad + '</strong> failed/skipped. <a href="games.php">Open Games &amp; Apps →</a>';
    log.appendChild(s); btn.disabled = false;
  });
  function esc(t) { var d = document.createElement('div'); d.textContent = t; return d.innerHTML; }
})();
</script>
<?php admin_foot();
