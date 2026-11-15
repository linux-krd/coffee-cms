<?php
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/admin_layout.php';
require_login();

$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $items = $_POST['items'] ?? [];
    $lines = [];
    $sub = 0;
    $find = $db->prepare('SELECT * FROM products WHERE id=? AND active=1');
    if (is_array($items)) {
        foreach ($items as $pid => $qty) {
            $qty = (int)$qty;
            if ($qty <= 0 || $qty > 999) {
                continue;
            }
            $find->execute([(int)$pid]);
            $p = $find->fetch();
            if (!$p) {
                continue;
            }
            $lines[] = [$p, $qty];
            $sub += (int)$p['price'] * $qty;
        }
    }
    if (!$lines) {
        flash('هیچ بەرهەمێک هەڵنەبژێردراوە.', 'err');
        redirect('sale_new.php');
    }
    $disc = min(to_int($_POST['discount'] ?? 0), $sub);
    $pay = (string)($_POST['payment'] ?? 'cash');
    if (!isset(pay_labels()[$pay])) {
        $pay = 'cash';
    }
    $note = trim((string)($_POST['note'] ?? ''));
    $customer = $pay === 'credit' ? trim((string)($_POST['customer'] ?? '')) : '';

    $db->beginTransaction();
    try {
        $db->prepare('INSERT INTO sales(created_at,subtotal,discount,total,payment,note,customer) VALUES(?,?,?,?,?,?,?)')
           ->execute([time(), $sub, $disc, $sub - $disc, $pay, $note, $customer]);
        $sid = (int)$db->lastInsertId();
        $ins = $db->prepare('INSERT INTO sale_items(sale_id,product_id,name,price,qty) VALUES(?,?,?,?,?)');
        foreach ($lines as [$p, $qty]) {
            $ins->execute([$sid, $p['id'], $p['name'], $p['price'], $qty]);
        }
        $db->commit();
    } catch (Throwable $ex) {
        $db->rollBack();
        flash('تۆمارکردنی فرۆشتن سەرکەوتوو نەبوو.', 'err');
        redirect('sale_new.php');
    }
    flash('فرۆشتن بە سەرکەوتوویی تۆمار کرا.');
    redirect('sale_view.php?id=' . $sid);
}

$cats = $db->query('SELECT * FROM categories WHERE active=1 ORDER BY sort_order, id')->fetchAll();
$st = $db->prepare('SELECT * FROM products WHERE category_id=? AND active=1 ORDER BY sort_order, id');

admin_header('تۆمارکردنی فرۆشتن', 'sale_new');
?>
<form method="post" id="pos-form" data-cur="<?= e(currency_name()) ?>">
  <?= csrf_field() ?>
  <div class="pos">
    <div>
      <div class="card" style="padding:10px 14px">
        <input type="text" id="pos-search" placeholder="گەڕان بەدوای بەرهەم...">
      </div>
      <?php foreach ($cats as $c):
          $st->execute([$c['id']]);
          $ps = $st->fetchAll();
          if (!$ps) continue; ?>
        <div class="pos-cat">
          <h3><?= icon($c['icon'], 18) ?> <?= e($c['name']) ?></h3>
          <div class="pos-list">
            <?php foreach ($ps as $p): ?>
              <div class="pos-item" data-price="<?= (int)$p['price'] ?>" data-name="<?= e($p['name']) ?>">
                <div class="n"><span><?= e($p['name']) ?></span><small><?= money($p['price']) ?></small></div>
                <div class="qty-box">
                  <button type="button" class="minus">−</button>
                  <span class="q">٠</span>
                  <button type="button" class="plus">+</button>
                  <input type="hidden" name="items[<?= (int)$p['id'] ?>]" value="0">
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (!$cats): ?><p style="color:var(--muted)">سەرەتا بەرهەمەکانی مینیو دیاری بکە.</p><?php endif; ?>
    </div>

    <div class="card cart">
      <h2>پسوولە</h2>
      <ul id="cart-list"><li style="color:var(--muted)">هێشتا هیچ بەرهەمێک هەڵنەبژێردراوە.</li></ul>
      <div class="fields">
        <div><label>داشکاندن (<?= e(currency_name()) ?>)</label><input type="text" inputmode="numeric" name="discount" id="discount" value="٠"></div>
        <div>
          <label>شێوازی پارەدان</label>
          <select name="payment" id="payment">
            <?php foreach (pay_labels() as $k => $l): ?><option value="<?= e($k) ?>"><?= e($l) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="full" id="cust-wrap" hidden><label>ناوی قەرزدار (بۆ قەرز)</label><input type="text" name="customer" id="customer" placeholder="بۆ نموونە کاک ئەحمەدی"></div>
        <div class="full"><label>تێبینی (ئارەزووەکی)</label><input type="text" name="note"></div>
      </div>
      <div class="sum-row"><span>کۆ</span><span><span id="sub">٠</span></span></div>
      <div class="sum-row"><span>داشکاندن</span><span><span id="disc-show">٠</span></span></div>
      <div class="sum-row total"><span>بڕی پێویست بۆ پارەدان</span><span><span id="total">٠</span></span></div>
      <button class="btn" style="width:100%;margin-top:14px">تۆمارکردنی فرۆشتن</button>
    </div>
  </div>
</form>
<script src="../assets/js/pos.js"></script>
<?php admin_footer();
