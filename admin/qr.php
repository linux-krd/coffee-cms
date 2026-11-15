<?php
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/admin_layout.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $url = trim((string)($_POST['site_url'] ?? ''));
    if ($url !== '' && !preg_match('~^https?://~i', $url)) {
        $url = 'https://' . $url;
    }
    if ($url !== '' && !filter_var($url, FILTER_VALIDATE_URL)) {
        flash('ناونیشانی نووسراو دروست نییە.', 'err');
    } else {
        save_setting('site_url', $url);
        save_setting('qr_caption', trim((string)($_POST['qr_caption'] ?? '')));
        save_setting('qr_enabled', isset($_POST['qr_enabled']) ? '1' : '0');
        flash('ڕێکخستنەکانی QR پاشەکەوت کران.');
    }
    redirect('qr.php');
}

$url = site_url();
$caption = setting('qr_caption');

admin_header('کۆدی QR ی مینیو', 'qr');
?>
<div class="grid" style="grid-template-columns:1fr auto;align-items:start">
  <form method="post" class="card">
    <h2>ڕێکخستنەکانی QR</h2>
    <?= csrf_field() ?>
    <div class="fields">
      <div class="full">
        <label>ناونیشانی مینیوی ئۆنلاین (QR بۆ ئێرە دەتبات)</label>
        <input type="text" name="site_url" class="ltr" value="<?= e(setting('site_url')) ?>" placeholder="https://example.com/">
        <small style="color:var(--muted)">ئەگەر بەتاڵی بهێڵیتەوە، ناونیشانی ئێستای ماڵپەڕ خۆکارانە بەکاردێت: <span class="ltr"><?= e($url) ?></span></small>
      </div>
      <div class="full"><label>دەقی ژێر QR</label><input type="text" name="qr_caption" value="<?= e($caption) ?>"></div>
      <div class="full"><label class="check"><input type="checkbox" name="qr_enabled" <?= setting('qr_enabled', '1') === '1' ? 'checked' : '' ?>> پیشاندانی دوگمەی «کۆدی QR» لە لاپەڕەی مینیودا</label></div>
    </div>
    <button class="btn">پاشەکەوتکردن</button>
  </form>

  <div class="qr-print-area">
    <div class="qr-card" id="qr-card">
      <strong><?= e(setting('cafe_name')) ?></strong>
      <canvas id="qr-canvas" width="260" height="260"></canvas>
      <?php if ($caption !== ''): ?><div class="cap"><?= e($caption) ?></div><?php endif; ?>
      <div class="url"><?= e($url) ?></div>
    </div>
    <div class="actions no-print" style="margin-top:12px;justify-content:center">
      <button type="button" class="btn" id="qr-dl">داگرتنی PNG</button>
      <button type="button" class="btn light" id="qr-print">چاپکردن</button>
    </div>
  </div>
</div>

<script src="../assets/js/qrcode.js"></script>
<script src="../assets/js/qr-render.js"></script>
<script>
(function () {
  var url = <?= json_encode($url, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
  var canvas = document.getElementById('qr-canvas');
  try { QRRender.draw(canvas, url, 780); }
  catch (e) { canvas.insertAdjacentText('afterend', 'دروستکردنی QR سەرکەوتوو نەبوو.'); }
  document.getElementById('qr-dl').onclick = function () { QRRender.download(canvas, 'menu-qr.png'); };
  document.getElementById('qr-print').onclick = function () {
    document.body.classList.add('qr-print');
    window.print();
    document.body.classList.remove('qr-print');
  };
})();
</script>
<?php admin_footer();
