<?php
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/admin_layout.php';
require_login();

$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $a = $_POST['action'] ?? '';

    if ($a === 'info') {
        foreach (['cafe_name', 'subtitle', 'address', 'phone', 'phone2', 'instagram'] as $k) {
            save_setting($k, trim((string)($_POST[$k] ?? '')));
        }
        $cur = (string)($_POST['currency'] ?? 'toman');
        save_setting('currency', isset(currencies()[$cur]) ? $cur : 'toman');
        $cur = (string)($_POST['currency'] ?? 'toman');
        save_setting('currency', isset(currencies()[$cur]) ? $cur : 'toman');
        foreach (['working_hours', 'features'] as $k) {
            $lines = array_filter(array_map('trim', explode("\n", str_replace("\r", '', (string)($_POST[$k] ?? '')))));
            save_setting($k, implode("\n", $lines));
        }
        $old = setting('hero_image');
        if (!empty($_POST['remove_hero']) && $old) {
            if (strpos($old, 'uploads/') === 0) {
                @unlink(BASE_DIR . '/' . $old);
            }
            save_setting('hero_image', '');
        }
        $new = save_image($_FILES['hero'] ?? []);
        if ($new) {
            if ($old && strpos($old, 'uploads/') === 0) {
                @unlink(BASE_DIR . '/' . $old);
            }
            save_setting('hero_image', $new);
        }
        flash('زانیارییەکانی کافێ پاشەکەوت کران.');
    } elseif ($a === 'password') {
        $cur = (string)($_POST['current'] ?? '');
        $new = (string)($_POST['new'] ?? '');
        $rep = (string)($_POST['repeat'] ?? '');
        $st = $db->prepare('SELECT * FROM users WHERE id=?');
        $st->execute([$_SESSION['uid']]);
        $u = $st->fetch();
        if (!$u || !password_verify($cur, $u['password_hash'])) {
            flash('وشەی نهێنیی ئێستا هەڵەیە.', 'err');
        } elseif (strlen($new) < 6) {
            flash('وشەی نهێنیی نوێ دەبێت لانی کەم ٦ پیت یان ژمارە بێت.', 'err');
        } elseif ($new !== $rep) {
            flash('دووبارەکراوەی وشەی نهێنیی نوێ جیاوازە.', 'err');
        } else {
            $db->prepare('UPDATE users SET password_hash=? WHERE id=?')
               ->execute([password_hash($new, PASSWORD_DEFAULT), $u['id']]);
            flash('وشەی نهێنی گۆڕدرا.');
        }
    }
    redirect('settings.php');
}

admin_header('زانیاری و ئیمکاناتی کافێ', 'settings');
$hero = setting('hero_image');
?>
<form method="post" enctype="multipart/form-data" class="settings-dashboard">
  <section class="card settings-card settings-card-main">
    <div class="settings-card-head"><span class="settings-icon">☕</span><div><h2>تایبەتمەندییەکانی مینیو</h2><p>ناو و ناونیشانێک کە لە مینیوی کافێدا پیشان دەدرێت.</p></div></div>
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="info">
  <div class="fields">
    <div><label>ناوی کافێ</label><input type="text" name="cafe_name" value="<?= e(setting('cafe_name')) ?>"></div>
    <div><label>ناونیشانی سەرووی «مینیو»</label><input type="text" name="subtitle" value="<?= e(setting('subtitle')) ?>"></div>
    <div class="full">
      <label>وێنەی سەرووی مینیو (JPG / PNG / WebP، زیاترین ٥ مێگابایت)</label>
      <?php if ($hero): ?>
        <p><img src="../<?= e($hero) ?>" alt="" style="height:110px;border-radius:55px 55px 0 0"></p>
        <label class="check"><input type="checkbox" name="remove_hero"> سڕینەوەی وێنەی ئێستا</label>
      <?php endif; ?>
      <input type="file" name="hero" accept="image/jpeg,image/png,image/webp">
    </div>
  </div>
  </section>

  <section class="card settings-card">
    <div class="settings-card-head"><span class="settings-icon">☎</span><div><h2>زانیاری پەیوەندی</h2><p>ئەو زانیارییانەی کە لە کۆتایی مینیودا پیشان دەدرێن.</p></div></div>
  <div class="fields">
    <div class="full"><label>کاتەکانی کار (هەر دێڕێک یەک بابەت)</label><textarea name="working_hours"><?= e(setting('working_hours')) ?></textarea></div>
    <div class="full"><label>ناونیشانی کافێ</label><input type="text" name="address" value="<?= e(setting('address')) ?>"></div>
    <div><label>ژمارەی تەلەفۆنی یەکەم</label><input type="text" name="phone" class="ltr" value="<?= e(setting('phone')) ?>" placeholder="٠٧٥٠-١٢٣٤٥٦٧"></div>
    <div><label>ژمارەی تەلەفۆنی دووەم (ئارەزووەکی)</label><input type="text" name="phone2" class="ltr" value="<?= e(setting('phone2')) ?>" placeholder="٠٧٧٠-٠٠٠٠٠٠٠"></div>
    <div class="full"><label>ئینستاگرام (ناوی بەکارهێنەر یان لینکی تەواو)</label><input type="text" name="instagram" class="ltr" value="<?= e(setting('instagram')) ?>" placeholder="borcelle.cafe"></div>
  </div>
  </section>

  <section class="card settings-card">
    <div class="settings-card-head"><span class="settings-icon">¤</span><div><h2>یەکەی دراو</h2><p>یەکەی دراوی نرخەکان لە مینیو، فرۆشتن و ڕاپۆرتەکان.</p></div></div>
  <div class="fields">
    <div class="full">
      <label>دراو (دۆلار، یۆرۆ، دینار، ڕیاڵ، تمەن، فرانک، پاوەند، یەن، …)</label>
      <select name="currency">
        <?php foreach (currencies() as $ck => [$cn, $cc]): ?>
          <option value="<?= e($ck) ?>" <?= setting('currency', 'toman') === $ck ? 'selected' : '' ?>><?= e($cn) ?> — <?= e($cc) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  </section>

  <section class="card settings-card">
    <div class="settings-card-head"><span class="settings-icon">✦</span><div><h2>ئیمکاناتی کافێ</h2><p>هەر ئیمکانێک لە دێڕێکدا بنووسە.</p></div></div>
  <div class="fields">
    <div class="full"><label>هەر دێڕێک یەک ئیمکان (بۆ نموونە وایفایی خۆڕایی)</label><textarea name="features"><?= e(setting('features')) ?></textarea></div>
  </div>
  </section>
  <div class="settings-save"><button class="btn">پاشەکەوتکردنی زانیاری کافێ</button></div>
</form>

<form method="post" class="card settings-password">
  <div class="settings-card-head"><span class="settings-icon">🔒</span><div><h2>گۆڕینی وشەی نهێنیی بەڕێوەبەر</h2><p>بۆ پاراستنی هەژماری بەڕێوەبردن، وشەی نهێنیی خۆت نوێ بکەرەوە.</p></div></div>
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="password">
  <div class="fields">
    <div class="full"><label>وشەی نهێنیی ئێستا</label><input type="password" name="current" class="ltr" required></div>
    <div><label>وشەی نهێنیی نوێ</label><input type="password" name="new" class="ltr" required></div>
    <div><label>دووبارەکردنەوەی وشەی نهێنیی نوێ</label><input type="password" name="repeat" class="ltr" required></div>
  </div>
  <button class="btn">گۆڕینی وشەی نهێنی</button>
</form>
<?php admin_footer();
