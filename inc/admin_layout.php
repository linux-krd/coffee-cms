<?php
/* قاڵبی هاوبەشی پانێڵی بەڕێوەبردن — مینیوی تەنیشت بە شێوازی وۆردپرێس */

/**
 * پێکهاتەی مینیو. هەر بابەتێک یان بەستەری ڕاستەوخۆیە (href) یان گرووپە لەگەڵ ژێرمینیو (sub).
 * ژێرمینیو: [کلیل، ناونیشان، ناونیشانی لینک]
 */
function admin_menu(): array
{
    return [
        ['key' => 'dashboard', 'label' => 'داشبۆرد', 'icon' => 'home', 'href' => 'index.php', 'keys' => ['index']],
        ['key' => 'sales', 'label' => 'فرۆشتن', 'icon' => 'cart', 'sub' => [
            ['sale_new', 'تۆمارکردنی فرۆشتنی نوێ', 'sale_new.php'],
            ['sales', 'لیستی فرۆشتنەکان', 'sales.php'],
            ['credit', 'قەرزە پاکنەکراوەکان', 'sales.php?pay=credit'],
        ]],
        ['key' => 'menu', 'label' => 'مینیوی کافێ', 'icon' => 'coffee', 'sub' => [
            ['products', 'بەرهەمەکان', 'products.php'],
            ['categories', 'پۆلەکان', 'categories.php'],
        ]],
        ['key' => 'reports', 'label' => 'ڕاپۆرت و ژمێریاری', 'icon' => 'chart', 'sub' => [
            ['reports', 'ڕاپۆرتی فرۆشتن و ژمێریاری', 'reports.php'],
            ['reports_print', 'چاپکردنی ڕاپۆرتی خولی', 'reports.php?print=1'],
        ]],
        ['key' => 'expenses', 'label' => 'تۆمارکردنی خەرجییەکان', 'icon' => 'wallet', 'href' => 'expenses.php', 'keys' => ['expenses']],
        ['key' => 'settings', 'label' => 'زانیاری کافێ', 'icon' => 'gear', 'sub' => [
            ['settings', 'تایبەتمەندی و پەیوەندی', 'settings.php'],
            ['qr', 'کۆدی QR ی مینیو', 'qr.php'],
        ]],
    ];
}

function admin_header(string $title, string $active = ''): void
{
    $menu = admin_menu();
    ?><!doctype html>
<html lang="ckb" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> | بەڕێوەبردنی <?= e(setting('cafe_name')) ?></title>
<link rel="preconnect" href="https://cdn.jsdelivr.net">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/vazirmatn@33.0.3/Vazirmatn-font-face.css">
<link rel="stylesheet" href="../assets/css/admin.css">
<script>try{if(localStorage.getItem('admin-folded')==='1')document.documentElement.classList.add('folded');}catch(e){}</script>
</head>
<body>
<div class="adminbar">
  <button type="button" class="ab-item ab-toggle" id="menu-toggle" aria-label="مینیو"><?= icon('bars', 20) ?></button>
  <a class="ab-item" href="index.php"><?= icon('coffee', 18) ?><span><?= e(setting('cafe_name')) ?></span></a>
  <a class="ab-item" href="../index.php" target="_blank" rel="noopener"><?= icon('external', 16) ?><span>بینینی مینیو</span></a>
  <span class="ab-grow"></span>
  <span class="ab-item ab-user"><?= icon('user', 18) ?><span>سڵاو، بەڕێوەبەر</span></span>
  <a class="ab-item" href="logout.php"><?= icon('logout', 18) ?><span>چوونەدەرەوە</span></a>
</div>
<div class="layout">
  <aside class="side" id="side">
    <ul class="wpmenu">
      <?php foreach ($menu as $m):
          $isGroup = isset($m['sub']);
          $current = false;
          if ($isGroup) {
              foreach ($m['sub'] as $sub) {
                  if ($sub[0] === $active) { $current = true; }
              }
              $href = $m['sub'][0][2];
          } else {
              $current = in_array($active, $m['keys'] ?? [], true);
              $href = $m['href'];
          }
          ?>
        <li class="menu-top<?= $current ? ' current' : '' ?><?= $isGroup ? ' has-sub' : '' ?>">
          <a class="top" href="<?= e($href) ?>">
            <?= icon($m['icon'], 20) ?><span class="lbl"><?= e($m['label']) ?></span>
          </a>
          <?php if ($isGroup): ?>
            <ul class="sub">
              <li class="sub-title"><?= e($m['label']) ?></li>
              <?php foreach ($m['sub'] as [$k, $label, $url]): ?>
                <li><a href="<?= e($url) ?>" class="<?= $k === $active ? 'on' : '' ?>"><?= e($label) ?></a></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
    <button type="button" class="fold-btn" id="fold-btn"><?= icon('fold', 20) ?><span class="lbl">کۆکردنەوەی مینیو</span></button>
  </aside>
  <main class="main">
    <h1 class="page-title"><?= e($title) ?></h1>
    <?php
    foreach ($_SESSION['flash'] ?? [] as [$type, $msg]) {
        echo '<div class="flash ' . e($type) . '">' . e($msg) . '</div>';
    }
    unset($_SESSION['flash']);
}

function admin_footer(): void
{
    ?>
  </main>
</div>

<div class="modal-overlay" id="modal" hidden>
  <div class="modal-box" role="dialog" aria-modal="true">
    <button type="button" class="modal-x" data-close aria-label="داخستن">×</button>
    <div id="modal-body"></div>
  </div>
</div>
<script src="../assets/js/admin.js"></script>
</body>
</html>
<?php
}
