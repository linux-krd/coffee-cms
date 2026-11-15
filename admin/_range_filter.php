<?php
/* فلتەری بازەی کات بە لیستە کەوانەکانی بەروار؛ پێویستی بە گۆڕاوی $r ی report_range() هەیە */
$ranges = ['today' => 'ئەمڕۆ', 'week' => 'ئەم هەفتەیە', 'month' => 'ئەم مانگە', 'all' => 'هەموو'];
$self = basename($_SERVER['SCRIPT_NAME']);
$payKeep = isset($_GET['pay']) && isset(pay_labels()[$_GET['pay']]) ? (string)$_GET['pay'] : '';
?>
<div class="quick no-print">
  <?php foreach ($ranges as $k => $l): ?>
    <a href="<?= e($self) ?>?range=<?= e($k) ?><?= $payKeep ? '&pay=' . e($payKeep) : '' ?>" class="<?= $r['range'] === $k ? 'on' : '' ?>"><?= e($l) ?></a>
  <?php endforeach; ?>
</div>
<form method="get" class="filters no-print">
  <?php if ($payKeep): ?><input type="hidden" name="pay" value="<?= e($payKeep) ?>"><?php endif; ?>
  <div class="f"><label>لە بەرواری</label><?= date_select('from', $r['from_show']) ?></div>
  <div class="f"><label>تا بەرواری</label><?= date_select('to', $r['to_show']) ?></div>
  <button class="btn">جێبەجێکردن</button>
</form>
