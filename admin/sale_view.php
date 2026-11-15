<?php
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/admin_layout.php';
require_login();

$db = db();
$modal = isset($_GET['modal']);
$st = $db->prepare('SELECT * FROM sales WHERE id=?');
$st->execute([(int)($_GET['id'] ?? 0)]);
$s = $st->fetch();
if (!$s) {
    if ($modal) {
        http_response_code(404);
        exit('پسوولە نەدۆزرایەوە.');
    }
    flash('پسوولە نەدۆزرایەوە.', 'err');
    redirect('sales.php');
}
$it = $db->prepare('SELECT * FROM sale_items WHERE sale_id=? ORDER BY id');
$it->execute([$s['id']]);
$items = $it->fetchAll();
$isCredit = $s['payment'] === 'credit';

$invoice = function () use ($s, $items, $isCredit) { ?>
<div class="card invoice">
  <div style="text-align:center;margin-bottom:12px">
    <strong style="font-size:18px;letter-spacing:.1em"><?= e(setting('cafe_name')) ?></strong><br>
    <span>پسوولەی ژمارە <?= ku_num($s['id']) ?></span><br>
    <small style="color:var(--muted)"><?= e(kdate((int)$s['created_at'], 'l j F Y - H:i')) ?></small>
  </div>
  <table>
    <tr><th>بەرهەم</th><th class="num">ژمارە</th><th class="num">نرخی یەکە</th><th class="num">بڕ</th></tr>
    <?php foreach ($items as $i): ?>
      <tr>
        <td><?= e($i['name']) ?></td>
        <td class="num"><?= ku_num($i['qty']) ?></td>
        <td class="num"><?= money($i['price']) ?></td>
        <td class="num"><?= money($i['price'] * $i['qty']) ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
  <div style="margin-top:12px">
    <div class="sum-row"><span>کۆ</span><span><?= money($s['subtotal']) ?></span></div>
    <div class="sum-row"><span>داشکاندن</span><span><?= money($s['discount']) ?></span></div>
    <div class="sum-row total"><span>بڕی پێویست بۆ پارەدان</span><span><?= money($s['total']) ?></span></div>
    <div class="sum-row"><span>شێوازی پارەدان</span><span><?= $isCredit ? 'قەرز (پارە نەدراوە)' : e(pay_label($s['payment'])) ?></span></div>
    <?php if ($s['customer'] !== ''): ?><div class="sum-row"><span>قەرزدار</span><span><?= e($s['customer']) ?></span></div><?php endif; ?>
    <?php if (!empty($s['paid_at'])): ?><div class="sum-row"><span>بەرواری پاککردنەوەی قەرز</span><span><?= e(kdate((int)$s['paid_at'], 'Y/m/d - H:i')) ?></span></div><?php endif; ?>
    <?php if ($s['note'] !== ''): ?><div class="sum-row"><span>تێبینی</span><span><?= e($s['note']) ?></span></div><?php endif; ?>
  </div>
</div>
<?php };

if ($modal) {
    $invoice();
    // تەنیا بۆ قەرز: گۆڕین بۆ یەکێک لە شێوازەکانی پارەدان؛ بە داخستنی پەنجەرە خۆکارانە تۆمار دەکرێت
    if ($isCredit): ?>
<div class="settle no-print" data-settle data-id="<?= (int)$s['id'] ?>" data-csrf="<?= e(csrf_token()) ?>">
  <label for="settle-pay">پاککردنەوەی قەرز — گۆڕین بۆ شێوازی پارەدان:</label>
  <select id="settle-pay">
    <option value="credit" selected>هێشتا قەرزە (پارە نەدراوە)</option>
    <?php foreach (pay_methods() as $k => $l): ?><option value="<?= e($k) ?>"><?= e($l) ?></option><?php endforeach; ?>
  </select>
  <small>شێوازی پارەدان هەڵبژێرە؛ بە داخستنی ئەم پەنجەرەیە، گۆڕانکارییەکە خۆکارانە تۆمار دەکرێت.</small>
</div>
<?php endif; ?>
<div class="actions no-print">
  <button type="button" class="btn" data-print>چاپکردن</button>
  <button type="button" class="btn light" data-close>داخستن</button>
</div>
<?php
    exit;
}

admin_header('پسوولەی ژمارە ' . ku_num($s['id']), 'sales');
$invoice();
?>
<div class="actions no-print">
  <button class="btn" onclick="window.print()">چاپکردن</button>
  <a class="btn light" href="sale_new.php">تۆمارکردنی فرۆشتنی نوێ</a>
  <a class="btn light" href="sales.php">لیستی فرۆشتنەکان</a>
</div>
<?php admin_footer();
