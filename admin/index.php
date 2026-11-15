<?php
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/admin_layout.php';
require_login();

$db = db();
$todayFrom = mktime(0, 0, 0);
$todayTo = $todayFrom + 86399;
$today = sales_summary($todayFrom, $todayTo);
$month = sales_summary(month_start(), $todayTo);
$credit = credit_outstanding();
$monthExp = report_data(month_start(), $todayTo, 1)['exp']['total'];
$productCount = (int)$db->query('SELECT COUNT(*) FROM products WHERE active=1')->fetchColumn();

$top = $db->prepare('SELECT si.name, SUM(si.qty) q FROM sale_items si JOIN sales s ON s.id=si.sale_id
                     WHERE s.created_at BETWEEN ? AND ? GROUP BY si.name ORDER BY q DESC LIMIT 5');
$top->execute([$todayFrom, $todayTo]);
$topRows = $top->fetchAll();

$last = $db->query('SELECT * FROM sales ORDER BY id DESC LIMIT 8')->fetchAll();

$hash = (string)$db->query("SELECT password_hash FROM users WHERE username='admin'")->fetchColumn();

admin_header('داشبۆرد', 'index');
if ($hash !== '' && password_verify('admin123', $hash)): ?>
  <div class="flash warn">وشەی نهێنیی بنەڕەتی (admin123) هێشتا نەگۆڕدراوە. تکایە لە بەشی «زانیاری کافێ» بیگۆڕە.</div>
<?php endif; ?>

<div class="grid">
  <div class="stat"><small>فرۆشتنی ئەمڕۆ</small><b><?= money($today['total']) ?></b></div>
  <div class="stat"><small>ژمارەی پسولەی ئەمڕۆ</small><b><?= ku_num($today['count']) ?></b></div>
  <div class="stat"><small>فرۆشتنی ئەم مانگە (<?= e(kdate(time(), 'F')) ?>)</small><b><?= money($month['total']) ?></b></div>
  <div class="stat"><small>بەرهەمە چالاکەکانی مینیو</small><b><?= ku_num($productCount) ?></b></div>
  <div class="stat warn"><small>قەرزە پاکنەکراوەکان (<?= ku_num($credit['count']) ?> پسوولە)</small><b><?= money($credit['total']) ?></b></div>
  <div class="stat bad"><small>خەرجییەکانی ئەم مانگە</small><b><?= money($monthExp) ?></b></div>
</div>

<div style="display:flex;gap:10px;margin-bottom:20px" class="no-print">
  <a class="btn" href="sale_new.php">تۆمارکردنی فرۆشتنی نوێ</a>
  <a class="btn light" href="reports.php">ڕاپۆرتی ژمێریاری</a>
  <a class="btn light" href="expenses.php">تۆمارکردنی خەرجی</a>
</div>

<div class="grid" style="grid-template-columns:2fr 1fr">
  <div>
    <h2 style="font-size:17px">دوایین فرۆشتنەکان</h2>
    <div class="table-wrap">
      <table>
        <tr><th>ژمارە</th><th>بەروار</th><th>پارەدان</th><th class="num">بڕ</th></tr>
        <?php foreach ($last as $s): ?>
          <tr>
            <td><a href="sale_view.php?id=<?= (int)$s['id'] ?>">#<?= ku_num($s['id']) ?></a></td>
            <td><?= e(kdate((int)$s['created_at'], 'Y/m/d - H:i')) ?></td>
            <td><?= $s['payment'] === 'credit' ? '<span class="badge credit">قەرز</span>' : e(pay_label($s['payment'])) ?></td>
            <td class="num"><?= money($s['total']) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$last): ?><tr><td colspan="4" style="color:var(--muted)">هێشتا هیچ فرۆشتنێک تۆمار نەکراوە.</td></tr><?php endif; ?>
      </table>
    </div>
  </div>
  <div>
    <h2 style="font-size:17px">پڕفرۆشەکانی ئەمڕۆ</h2>
    <div class="table-wrap">
      <table>
        <?php foreach ($topRows as $r): ?>
          <tr><td><?= e($r['name']) ?></td><td class="num"><?= ku_num($r['q']) ?> دانە</td></tr>
        <?php endforeach; ?>
        <?php if (!$topRows): ?><tr><td style="color:var(--muted)">هیچ داتایەک نییە.</td></tr><?php endif; ?>
      </table>
    </div>
  </div>
</div>
<?php admin_footer();
