<?php
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/admin_layout.php';
require_login();

$r = report_range();
$d = report_data($r['from'], $r['to'], 10);
$sum = $d['sum'];
$byPay = $d['byPay'];
$byDay = array_reverse($d['byDay'], true);
$maxDay = $byDay ? max(array_column($byDay, 'total')) : 0;
$extraPay = array_diff_key($byPay, pay_labels());

admin_header('ڕاپۆرت و ژمێریاریی فرۆشتن', isset($_GET['print']) ? 'reports_print' : 'reports');
?>
<div class="no-print" style="margin-bottom:14px">
  <button type="button" class="btn" data-modal="tpl-print">چاپکردنی ڕاپۆرتی خولی</button>
</div>

<?php require __DIR__ . '/_range_filter.php'; ?>

<div class="grid">
  <div class="stat"><small>فرۆشتنی خاوێن</small><b><?= money($sum['total']) ?></b></div>
  <div class="stat"><small>ژمارەی پسوولە</small><b><?= ku_num($sum['count']) ?></b></div>
  <div class="stat"><small>تێکڕای هەر پسوولەیەک</small><b><?= money($sum['avg']) ?></b></div>
  <div class="stat"><small>کۆی داشکاندنەکان</small><b><?= money($sum['discount']) ?></b></div>
  <div class="stat"><small>فرۆشتنی گشتی (پێش داشکاندن)</small><b><?= money($sum['subtotal']) ?></b></div>
  <div class="stat warn"><small>قەرزی وەرنەگیراو</small><b><?= money($d['credit']) ?></b></div>
  <div class="stat bad"><small>کۆی خەرجییەکان</small><b><?= money($d['exp']['total']) ?></b></div>
  <div class="stat <?= $d['balance'] < 0 ? 'bad' : 'good' ?>"><small>ماوە (فرۆشتنی خاوێن − خەرجییەکان)</small><b><?= money_html($d['balance']) ?></b></div>
</div>

<div class="grid" style="grid-template-columns:1fr 1fr">
  <div>
    <h2 style="font-size:17px">جیاکردنەوەی شێوازی پارەدان</h2>
    <div class="table-wrap"><table>
      <?php foreach (pay_labels() as $k => $l): ?>
        <tr><td><?= e($l) ?><?= $k === 'credit' ? ' <span class="sub-note">وەرنەگیراو</span>' : '' ?></td><td class="num"><?= money($byPay[$k] ?? 0) ?></td></tr>
      <?php endforeach; ?>
      <?php foreach ($extraPay as $k => $v): ?>
        <tr><td><?= e(pay_label((string)$k)) ?></td><td class="num"><?= money($v) ?></td></tr>
      <?php endforeach; ?>
    </table></div>
  </div>
  <div>
    <h2 style="font-size:17px">خەرجییەکان بەپێی جۆر</h2>
    <div class="table-wrap"><table>
      <?php foreach ($d['exp']['byCat'] as $k => $v): ?>
        <tr><td><?= e(expense_label((string)$k)) ?></td><td class="num"><?= money($v) ?></td></tr>
      <?php endforeach; ?>
      <?php if (!$d['exp']['byCat']): ?><tr><td style="color:var(--muted)">لەم بازەیەدا هیچ خەرجییەک تۆمار نەکراوە.</td></tr><?php endif; ?>
    </table></div>
  </div>
</div>

<h2 style="font-size:17px">١٠ بەرهەمی پڕفرۆش</h2>
<div class="table-wrap"><table>
  <tr><th>بەرهەم</th><th class="num">ژمارە</th><th class="num">بڕ</th></tr>
  <?php foreach ($d['top'] as $t): ?>
    <tr><td><?= e($t['name']) ?></td><td class="num"><?= ku_num($t['q']) ?></td><td class="num"><?= money($t['amount']) ?></td></tr>
  <?php endforeach; ?>
  <?php if (!$d['top']): ?><tr><td colspan="3" style="color:var(--muted)">هیچ داتایەک نییە.</td></tr><?php endif; ?>
</table></div>

<h2 style="font-size:17px">فرۆشتنی ڕۆژانە</h2>
<div class="table-wrap">
  <table>
    <tr><th>بەروار</th><th>پسوولە</th><th class="num">فرۆشتن</th><th style="width:30%"></th></tr>
    <?php foreach ($byDay as $row): ?>
      <tr>
        <td><?= e(kdate($row['ts'], 'l Y/m/d')) ?></td>
        <td><?= ku_num($row['count']) ?></td>
        <td class="num"><?= money($row['total']) ?></td>
        <td><div class="bar"><i style="width:<?= $maxDay ? round($row['total'] / $maxDay * 100) : 0 ?>%"></i></div></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$byDay): ?><tr><td colspan="4" style="color:var(--muted)">لەم بازەیەدا هیچ فرۆشتنێک تۆمار نەکراوە.</td></tr><?php endif; ?>
  </table>
</div>

<!-- پەنجەرەی چاپکردنی ڕاپۆرتی خولی (مۆداڵ) -->
<template id="tpl-print">
  <form method="get" action="report_print.php" target="_blank" class="print-modal">
    <h2>چاپکردنی ڕاپۆرتی خولی</h2>
    <label>بەرواری سەرچاوە (ئەو خولەی ئەم ڕۆژەی تێدایە)</label>
    <?= date_select('ref', time()) ?>
    <div class="btns">
      <button class="btn" name="type" value="daily">ڕاپۆرتی ڕۆژانە</button>
      <button class="btn" name="type" value="weekly">ڕاپۆرتی هەفتانە</button>
      <button class="btn" name="type" value="monthly">ڕاپۆرتی مانگانە</button>
      <button class="btn" name="type" value="yearly">ڕاپۆرتی ساڵانە</button>
    </div>
  </form>
</template>
<?php admin_footer();
