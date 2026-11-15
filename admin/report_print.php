<?php
require __DIR__ . '/../inc/bootstrap.php';
require_login();

$type = (string)($_GET['type'] ?? 'daily');
if (!in_array($type, ['daily', 'weekly', 'monthly', 'yearly'], true)) {
    $type = 'daily';
}
$ref = date_from_request($_GET, 'ref') ?? time();
[$from, $to, $title] = period_bounds($type, $ref);
$d = report_data($from, $to, 20);
$sum = $d['sum'];

// ڕیزەکانی جیاکردنەوەی خول
$rows = [];
if ($type === 'yearly') {
    $months = ku_months();
    foreach ($d['byDay'] as $row) {
        $jm = (int)date('n', $row['ts']);
        $rows[$jm]['label'] = $months[$jm];
        $rows[$jm]['count'] = ($rows[$jm]['count'] ?? 0) + $row['count'];
        $rows[$jm]['total'] = ($rows[$jm]['total'] ?? 0) + $row['total'];
    }
    ksort($rows);
    $breakTitle = 'فرۆشتنی مانگانە';
    $breakCol = 'مانگ';
} elseif ($type !== 'daily') {
    foreach ($d['byDay'] as $k => $row) {
        $rows[$k] = ['label' => kdate($row['ts'], 'l Y/m/d'), 'count' => $row['count'], 'total' => $row['total']];
    }
    $breakTitle = 'فرۆشتنی ڕۆژانە';
    $breakCol = 'بەروار';
}

$invoices = [];
if ($type === 'daily') {
    $st = db()->prepare('SELECT * FROM sales WHERE created_at BETWEEN ? AND ? ORDER BY created_at, id');
    $st->execute([$from, $to]);
    $invoices = $st->fetchAll();
}
$extraPay = array_diff_key($d['byPay'], pay_labels());
?><!doctype html>
<html lang="ckb" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> | <?= e(setting('cafe_name')) ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/vazirmatn@33.0.3/Vazirmatn-font-face.css">
<style>
  *{box-sizing:border-box}
  body{margin:0;background:#eee;color:#222;font-family:'Vazirmatn','Noto Sans Arabic','Noto Naskh Arabic',Tahoma,sans-serif;font-size:14px;line-height:1.7}
  .page{max-width:820px;margin:20px auto;background:#fff;padding:32px 36px}
  header{text-align:center;border-bottom:2px solid #222;padding-bottom:12px;margin-bottom:18px}
  header .brand{font-weight:800;letter-spacing:.12em;font-size:18px}
  header h1{margin:4px 0;font-size:22px}
  header p{margin:0;color:#555}
  h2{font-size:15px;margin:22px 0 8px}
  table{width:100%;border-collapse:collapse}
  th,td{padding:6px 10px;border:1px solid #ccc;text-align:right}
  th{background:#f3f3f3;font-weight:600}
  td.num,th.num{text-align:left;font-variant-numeric:tabular-nums}
  .two{display:grid;grid-template-columns:1fr 1fr;gap:18px}
  tr.total td{font-weight:800;background:#f8f8f8}
  footer{margin-top:24px;font-size:12px;color:#777;display:flex;justify-content:space-between}
  .bar{text-align:center;margin:14px 0}
  .bar button,.bar a{font:inherit;padding:7px 18px;border:0;border-radius:8px;background:#3b1d12;color:#fff;cursor:pointer;text-decoration:none;margin:0 4px}
  tr{break-inside:avoid}
  @media print{body{background:#fff}.page{margin:0;padding:0;max-width:none}.bar{display:none}}
</style>
</head>
<body>
<div class="bar"><button onclick="window.print()">چاپکردن</button><a href="reports.php" onclick="window.close();return false">داخستن</a></div>
<div class="page">
  <header>
    <div class="brand"><?= e(setting('cafe_name')) ?></div>
    <h1><?= e($title) ?></h1>
    <p>
      <?php if ($type === 'daily'): ?>
        <?= e(kdate($from, 'l j F Y')) ?>
      <?php elseif ($type === 'yearly'): ?>
        ساڵی <?= e(kdate($from, 'Y')) ?>
      <?php else: ?>
        لە <?= e(kdate($from, 'l Y/m/d')) ?> تا <?= e(kdate($to, 'l Y/m/d')) ?>
      <?php endif; ?>
    </p>
  </header>

  <h2>کورتەی فرۆشتن</h2>
  <table>
    <tr><td>ژمارەی پسوولە</td><td class="num"><?= ku_num($sum['count']) ?></td></tr>
    <tr><td>فرۆشتنی گشتی (پێش داشکاندن)</td><td class="num"><?= money($sum['subtotal']) ?></td></tr>
    <tr><td>کۆی داشکاندنەکان</td><td class="num"><?= money($sum['discount']) ?></td></tr>
    <tr><td>تێکڕای هەر پسوولەیەک</td><td class="num"><?= money($sum['avg']) ?></td></tr>
    <tr class="total"><td>فرۆشتنی خاوێن</td><td class="num"><?= money($sum['total']) ?></td></tr>
    <tr><td>قەرزی وەرنەگیراو (بەشێکە لە فرۆشتنی خاوێن)</td><td class="num"><?= money($d['credit']) ?></td></tr>
    <tr><td>کۆی خەرجییەکان</td><td class="num"><?= money($d['exp']['total']) ?></td></tr>
    <tr class="total"><td>ماوە (فرۆشتنی خاوێن − خەرجییەکان)</td><td class="num"><?= money_html($d['balance']) ?></td></tr>
  </table>

  <div class="two">
    <div>
      <h2>جیاکردنەوەی شێوازی پارەدان</h2>
      <table>
        <?php foreach (pay_labels() as $k => $l): ?>
          <tr><td><?= e($l) ?></td><td class="num"><?= money($d['byPay'][$k] ?? 0) ?></td></tr>
        <?php endforeach; ?>
        <?php foreach ($extraPay as $k => $v): ?>
          <tr><td><?= e(pay_label((string)$k)) ?></td><td class="num"><?= money($v) ?></td></tr>
        <?php endforeach; ?>
      </table>
    </div>
    <div>
      <h2>پڕفرۆشترین بەرهەمەکان</h2>
      <table>
        <tr><th>بەرهەم</th><th class="num">ژمارە</th></tr>
        <?php foreach (array_slice($d['top'], 0, 10) as $t): ?>
          <tr><td><?= e($t['name']) ?></td><td class="num"><?= ku_num($t['q']) ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$d['top']): ?><tr><td colspan="2">—</td></tr><?php endif; ?>
      </table>
    </div>
  </div>

  <?php if ($type === 'daily'): ?>
    <h2>لیستی پسوولەکان</h2>
    <table>
      <tr><th>ژمارە</th><th>کات</th><th>پارەدان</th><th class="num">بڕ</th></tr>
      <?php foreach ($invoices as $i): ?>
        <tr><td>#<?= ku_num($i['id']) ?></td><td><?= e(kdate((int)$i['created_at'], 'H:i')) ?></td><td><?= e(pay_label($i['payment'])) ?></td><td class="num"><?= money($i['total']) ?></td></tr>
      <?php endforeach; ?>
      <?php if (!$invoices): ?><tr><td colspan="4">هیچ فرۆشتنێک تۆمار نەکراوە.</td></tr><?php endif; ?>
    </table>
  <?php else: ?>
    <h2><?= e($breakTitle) ?></h2>
    <table>
      <tr><th><?= e($breakCol) ?></th><th>ژمارەی پسوولە</th><th class="num">فرۆشتن</th></tr>
      <?php foreach ($rows as $row): ?>
        <tr><td><?= e($row['label']) ?></td><td><?= ku_num($row['count']) ?></td><td class="num"><?= money($row['total']) ?></td></tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="3">لەم خولەدا هیچ فرۆشتنێک تۆمار نەکراوە.</td></tr><?php endif; ?>
    </table>
  <?php endif; ?>

  <?php if ($d['exp']['byCat']): ?>
    <h2>خەرجییەکان بەپێی جۆر</h2>
    <table>
      <tr><th>جۆری خەرجی</th><th class="num">بڕ</th></tr>
      <?php foreach ($d['exp']['byCat'] as $k => $v): ?>
        <tr><td><?= e(expense_label((string)$k)) ?></td><td class="num"><?= money($v) ?></td></tr>
      <?php endforeach; ?>
      <tr class="total"><td>کۆ</td><td class="num"><?= money($d['exp']['total']) ?></td></tr>
    </table>
  <?php endif; ?>

  <footer>
    <span>کاتی چاپکردن: <?= e(kdate(time(), 'l Y/m/d - H:i')) ?></span>
    <span><?= e(setting('cafe_name')) ?></span>
  </footer>
</div>
<script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 400); });</script>
</body>
</html>
