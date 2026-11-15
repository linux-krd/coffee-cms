<?php
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/admin_layout.php';
require_login();

$db = db();
$cats = $db->query('SELECT * FROM categories ORDER BY sort_order, id')->fetchAll();
$catIds = array_map(fn($c) => (int)$c['id'], $cats);
$filter = (int)($_GET['cat'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $a = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($a === 'save') {
        $name = trim((string)($_POST['name'] ?? ''));
        $cid = (int)($_POST['category_id'] ?? 0);
        $price = to_int($_POST['price'] ?? 0);
        $desc = trim((string)($_POST['description'] ?? ''));
        $sort = to_int($_POST['sort_order'] ?? 0);
        $active = isset($_POST['active']) ? 1 : 0;
        if ($name === '' || !in_array($cid, $catIds, true)) {
            flash('ناوی بەرهەم و پۆل پێویستن.', 'err');
        } elseif ($id) {
            $db->prepare('UPDATE products SET category_id=?, name=?, description=?, price=?, sort_order=?, active=? WHERE id=?')
               ->execute([$cid, $name, $desc, $price, $sort, $active, $id]);
            flash('بەرهەم پاشەکەوت کرا.');
        } else {
            $db->prepare('INSERT INTO products(category_id,name,description,price,sort_order,active) VALUES(?,?,?,?,?,?)')
               ->execute([$cid, $name, $desc, $price, $sort, $active]);
            flash('بەرهەمی نوێ زیاد کرا.');
        }
    } elseif ($a === 'toggle' && $id) {
        $db->prepare('UPDATE products SET active = 1 - active WHERE id=?')->execute([$id]);
    } elseif ($a === 'delete' && $id) {
        $db->prepare('DELETE FROM products WHERE id=?')->execute([$id]);
        flash('بەرهەم سڕایەوە (تۆمارەکانی فرۆشتنی پێشوو دەمێننەوە).');
    }
    redirect('products.php' . ($filter ? '?cat=' . $filter : ''));
}

$edit = null;
if (isset($_GET['edit'])) {
    $st = $db->prepare('SELECT * FROM products WHERE id=?');
    $st->execute([(int)$_GET['edit']]);
    $edit = $st->fetch() ?: null;
}

$sql = 'SELECT p.*, c.name cat_name FROM products p JOIN categories c ON c.id=p.category_id';
$params = [];
if ($filter) {
    $sql .= ' WHERE p.category_id=?';
    $params[] = $filter;
}
$sql .= ' ORDER BY c.sort_order, c.id, p.sort_order, p.id';
$st = $db->prepare($sql);
$st->execute($params);
$products = $st->fetchAll();

admin_header('بەرهەمەکانی مینیو', 'products');
if (!$cats): ?>
  <div class="flash warn">سەرەتا لە بەشی «پۆلەکان» پۆلێک دروست بکە.</div>
<?php endif; ?>

<div class="card">
  <h2><?= $edit ? 'دەستکاریکردنی بەرهەم' : 'زیادکردنی بەرهەم' ?></h2>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
    <div class="fields">
      <div><label>ناوی بەرهەم</label><input type="text" name="name" value="<?= e($edit['name'] ?? '') ?>" required></div>
      <div>
        <label>پۆل</label>
        <select name="category_id">
          <?php foreach ($cats as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= (int)($edit['category_id'] ?? $filter) === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div><label>نرخ (<?= e(currency_name()) ?>)</label><input type="text" inputmode="numeric" name="price" value="<?= e(ku_digits($edit['price'] ?? '')) ?>" required></div>
      <div><label>ڕیزبەندیی پیشاندان</label><input type="text" inputmode="numeric" name="sort_order" value="<?= e(ku_digits($edit['sort_order'] ?? 0)) ?>"></div>
      <div class="full"><label>وەسف (کوردی، ئارەزووەکی)</label><input type="text" name="description" value="<?= e($edit['description'] ?? '') ?>"></div>
      <div class="full"><label class="check"><input type="checkbox" name="active" <?= ($edit['active'] ?? 1) ? 'checked' : '' ?>> پیشاندان لە مینیو و فرۆشتندا</label></div>
    </div>
    <button class="btn"><?= $edit ? 'پاشەکەوتکردنی گۆڕانکارییەکان' : 'زیادکردن' ?></button>
    <?php if ($edit): ?><a class="btn light" href="products.php">پاشگەزبوونەوە</a><?php endif; ?>
  </form>
</div>

<div class="quick">
  <a href="products.php" class="<?= $filter ? '' : 'on' ?>">هەموو</a>
  <?php foreach ($cats as $c): ?>
    <a href="products.php?cat=<?= (int)$c['id'] ?>" class="<?= $filter === (int)$c['id'] ? 'on' : '' ?>"><?= e($c['name']) ?></a>
  <?php endforeach; ?>
</div>

<div class="table-wrap">
  <table>
    <tr><th>ناو</th><th>پۆل</th><th class="num">نرخ</th><th>دۆخ</th><th></th></tr>
    <?php foreach ($products as $p): ?>
      <tr>
        <td><?= e($p['name']) ?><?php if ($p['description'] !== ''): ?><br><small style="color:var(--muted)"><?= e($p['description']) ?></small><?php endif; ?></td>
        <td><?= e($p['cat_name']) ?></td>
        <td class="num"><?= money($p['price']) ?></td>
        <td><span class="badge <?= $p['active'] ? '' : 'off' ?>"><?= $p['active'] ? 'چالاک' : 'شاراوە' ?></span></td>
        <td>
          <div class="actions">
            <a class="btn sm light" href="products.php?edit=<?= (int)$p['id'] ?><?= $filter ? '&cat=' . $filter : '' ?>">دەستکاری</a>
            <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button class="btn sm light"><?= $p['active'] ? 'شاردنەوە' : 'چالاککردن' ?></button></form>
            <form method="post" class="inline" onsubmit="return confirm('ئەم بەرهەمە بسڕدرێتەوە؟')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button class="btn sm danger">سڕینەوە</button></form>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$products): ?><tr><td colspan="5" style="color:var(--muted)">هیچ بەرهەمێک تۆمار نەکراوە.</td></tr><?php endif; ?>
  </table>
</div>
<?php admin_footer();
