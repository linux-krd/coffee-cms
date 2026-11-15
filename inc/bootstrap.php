<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}
date_default_timezone_set('Asia/Tehran');
define('BASE_DIR', dirname(__DIR__));

require_once __DIR__ . '/kdate.php';
require_once __DIR__ . '/icons.php';

/* ------------------------------------------------------------------ */
/* ئامرازە گشتییەکان                                                    */
/* ------------------------------------------------------------------ */

function e($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function flash(string $msg, string $type = 'ok'): void
{
    $_SESSION['flash'][] = [$type, $msg];
}

function to_int($v): int
{
    $s = en_digits((string)$v);
    $s = preg_replace('~[^\d]~', '', $s) ?? '';
    return $s === '' ? 0 : (int)$s;
}

/** دراوە بەردەستەکان: کلیل => [ناوی کوردی، کۆدی نێودەوڵەتی] */
function currencies(): array
{
    return [
        'toman' => ['تمەن', 'IRT'],
        'rial'  => ['ڕیاڵ', 'IRR'],
        'iqd'   => ['دینار', 'IQD'],
        'usd'   => ['دۆلار', 'USD'],
        'eur'   => ['یۆرۆ', 'EUR'],
        'gbp'   => ['پاوەند', 'GBP'],
        'chf'   => ['فرانک', 'CHF'],
        'jpy'   => ['یەن', 'JPY'],
        'try'   => ['لیرەی تورکی', 'TRY'],
        'cny'   => ['یوانی چین', 'CNY'],
        'aed'   => ['دەرهەمی ئیمارات', 'AED'],
        'sar'   => ['ڕیاڵی سعودی', 'SAR'],
        'kwd'   => ['دیناری کوەیت', 'KWD'],
        'cad'   => ['دۆلاری کەنەدا', 'CAD'],
        'aud'   => ['دۆلاری ئوسترالیا', 'AUD'],
        'rub'   => ['ڕووبڵ', 'RUB'],
        'inr'   => ['ڕۆپیەی هیندی', 'INR'],
    ];
}

/** ناوی یەکەی دراوی هەڵبژێردراو (لە ڕێکخستنەکان) */
function currency_name(): string
{
    $c = currencies();
    $k = setting('currency', 'toman');
    return ($c[$k] ?? $c['toman'])[0];
}

/** ژمارە + یەکەی دراو، بۆ نموونە «٨٥٬٠٠٠ تمەن» */
function money($n): string
{
    return ku_num($n) . ' ' . currency_name();
}

function pay_labels(): array
{
    return ['pos' => 'کارتخوێن', 'cash' => 'نەقد', 'transfer' => 'کارت بۆ کارت', 'online' => 'ئۆنلاین', 'credit' => 'قەرز'];
}

/** شێوازە ڕاستەقینەکانی پارەدان (بەبێ قەرز) — بۆ پاکتاوکردنی قەرز */
function pay_methods(): array
{
    $m = pay_labels();
    unset($m['credit']);
    return $m;
}

/** بڕ لەگەڵ نیشانەی کەم (−)ی دروست لە دەقی ڕاست‌بەرەوچەپ (دەرچووی HTML ی پارێزراو) */
function money_html($n): string
{
    return '<span dir="ltr">' . ($n < 0 ? '−' : '') . ku_num(abs((float)$n)) . '</span> ' . e(currency_name());
}

function pay_label(string $k): string
{
    return pay_labels()[$k] ?? 'تر';
}

function expense_categories(): array
{
    return [
        'water'       => 'ئاو',
        'electricity' => 'کارەبا',
        'phone'       => 'تەلەفۆن',
        'gas'         => 'گاز',
        'tax'         => 'باج',
        'municipal'   => 'ڕەسمی شارەوانی',
        'rent'        => 'کرێ',
        'salary'      => 'مووچەی کارمەندان',
        'repair'      => 'چاککردنەوە و خزمەتگوزاریی کافێ',
        'other'       => 'خەرجییە جۆراوجۆرەکانی تر',
    ];
}

function expense_label(string $k): string
{
    return expense_categories()[$k] ?? 'خەرجییە جۆراوجۆرەکانی تر';
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST'
        && !hash_equals(csrf_token(), (string)($_POST['_csrf'] ?? ''))) {
        http_response_code(400);
        exit('داواکارییەکە نادروستە. لاپەڕەکە دووبارە بکەرەوە.');
    }
}

function require_login(): void
{
    if (empty($_SESSION['uid'])) {
        redirect('login.php');
    }
    csrf_check();
}

/* ------------------------------------------------------------------ */
/* بنکەی دراوە (SQLite)                                                */
/* ------------------------------------------------------------------ */

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $dir = BASE_DIR . '/data';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    if (!file_exists($dir . '/.htaccess')) {
        file_put_contents($dir . '/.htaccess',
            "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n");
    }
    $pdo = new PDO('sqlite:' . $dir . '/cafe.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');
    init_db($pdo);
    return $pdo;
}

function init_db(PDO $db): void
{
    $db->exec("
        CREATE TABLE IF NOT EXISTS users(
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL
        );
        CREATE TABLE IF NOT EXISTS settings(
            k TEXT PRIMARY KEY,
            v TEXT NOT NULL DEFAULT ''
        );
        CREATE TABLE IF NOT EXISTS categories(
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            icon TEXT NOT NULL DEFAULT 'coffee',
            sort_order INTEGER NOT NULL DEFAULT 0,
            active INTEGER NOT NULL DEFAULT 1
        );
        CREATE TABLE IF NOT EXISTS products(
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            category_id INTEGER NOT NULL REFERENCES categories(id) ON DELETE CASCADE,
            name TEXT NOT NULL,
            description TEXT NOT NULL DEFAULT '',
            price INTEGER NOT NULL DEFAULT 0,
            sort_order INTEGER NOT NULL DEFAULT 0,
            active INTEGER NOT NULL DEFAULT 1
        );
        CREATE TABLE IF NOT EXISTS sales(
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            created_at INTEGER NOT NULL,
            subtotal INTEGER NOT NULL,
            discount INTEGER NOT NULL DEFAULT 0,
            total INTEGER NOT NULL,
            payment TEXT NOT NULL DEFAULT 'cash',
            note TEXT NOT NULL DEFAULT ''
        );
        CREATE TABLE IF NOT EXISTS sale_items(
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            sale_id INTEGER NOT NULL REFERENCES sales(id) ON DELETE CASCADE,
            product_id INTEGER REFERENCES products(id) ON DELETE SET NULL,
            name TEXT NOT NULL,
            price INTEGER NOT NULL,
            qty INTEGER NOT NULL
        );
        CREATE TABLE IF NOT EXISTS expenses(
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            created_at INTEGER NOT NULL,
            category TEXT NOT NULL,
            amount INTEGER NOT NULL,
            note TEXT NOT NULL DEFAULT ''
        );
        CREATE INDEX IF NOT EXISTS idx_exp_created ON expenses(created_at);
        CREATE INDEX IF NOT EXISTS idx_sales_created ON sales(created_at);
        CREATE INDEX IF NOT EXISTS idx_items_sale ON sale_items(sale_id);
    ");

    // ستوونەکانی قەرز (بۆ بنکەی دراوەی دروستکراو بە وەشانە کۆنەکان)
    $cols = array_column($db->query('PRAGMA table_info(sales)')->fetchAll(), 'name');
    if (!in_array('customer', $cols, true)) {
        $db->exec("ALTER TABLE sales ADD COLUMN customer TEXT NOT NULL DEFAULT ''");
    }
    if (!in_array('paid_at', $cols, true)) {
        $db->exec('ALTER TABLE sales ADD COLUMN paid_at INTEGER');
    }

    // گواستنەوەی شێوازە کۆنەکانی پارەدان بۆ ناوە نوێیەکان
    $db->exec("UPDATE sales SET payment='pos' WHERE payment='card'");

    // ڕێکخستنە بنەڕەتییەکان (تەنیا ئەگەر نەبن)
    $defaults = [
        'cafe_name'     => 'BORCELLE',
        'subtitle'      => 'مینیوی کافێ',
        'currency'      => 'toman',
        'hero_image'    => '',
        'working_hours' => "شەممە تا پێنجشەممە: ٨ی بەیانی تا ١١ی شەو\nهەینییەکان: ١٠ی بەیانی تا ١٢ی شەو",
        'address'       => 'هەولێر، شەقامی نموونە، پلاکی ١٢',
        'phone'         => '٠٧٥٠-١٢٣٤٥٦٧',
        'phone2'        => '',
        'instagram'     => 'borcelle.cafe',
        'site_url'      => '',
        'qr_enabled'    => '1',
        'qr_caption'    => 'بۆ بینینی مینیو سکان بکە',
        'features'      => "وایفایی خۆڕایی\nشوێنی کراوە\nقاوەی تازە\nگونجاو بۆ کار و خوێندن",
    ];
    $ins = $db->prepare('INSERT OR IGNORE INTO settings(k,v) VALUES(?,?)');
    foreach ($defaults as $k => $v) {
        $ins->execute([$k, $v]);
    }

    // بەکارهێنەری بەڕێوەبەری بنەڕەتی
    if ((int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0) {
        $db->prepare('INSERT INTO users(username,password_hash) VALUES(?,?)')
           ->execute(['admin', password_hash('admin123', PASSWORD_DEFAULT)]);
    }

    // داتای نموونە بەپێی دیزاینی مینیو
    if ((int)$db->query('SELECT COUNT(*) FROM categories')->fetchColumn() === 0) {
        $seed = [
            ['قاوە', 'coffee', ['ئەسپرێسۆ', 'دەبڵ ئەسپرێسۆ', 'لاتێ', 'ئەمریکانۆ', 'ماکیاتۆ', 'فلات وایت', 'کاپوچینۆ']],
            ['چا', 'tea', ['چای لیمۆ', 'چای مانگۆ', 'چای یاسەمەن', 'چای سەوز', 'چای نەعنا']],
            ['خواردنەوەی بێ قاوە', 'cup', ['هۆت چۆکلێت', 'میلک‌شەیک', 'سموزی', 'لیمۆناد', 'میلک‌شەیکی ڤانیلا']],
            ['شیرینی', 'dessert', ['وافڵی تووتفەرەنگی', 'ڕۆڵی دارچین', 'لیمۆ پای', 'کرواسان', 'وافڵی چۆکلێتی', 'براونی', 'چیزکێک', 'مافنی چۆکلێتی']],
        ];
        $c = $db->prepare('INSERT INTO categories(name,icon,sort_order) VALUES(?,?,?)');
        $p = $db->prepare('INSERT INTO products(category_id,name,price,sort_order) VALUES(?,?,?,?)');
        foreach ($seed as $i => [$name, $icon, $items]) {
            $c->execute([$name, $icon, $i + 1]);
            $cid = (int)$db->lastInsertId();
            foreach ($items as $j => $item) {
                $p->execute([$cid, $item, 85000, $j + 1]);
            }
        }
    }
}

/* ------------------------------------------------------------------ */
/* ڕێکخستنەکانی کافێ                                                   */
/* ------------------------------------------------------------------ */

function settings(): array
{
    static $s = null;
    if ($s === null) {
        $s = [];
        foreach (db()->query('SELECT k,v FROM settings') as $r) {
            $s[$r['k']] = $r['v'];
        }
    }
    return $s;
}

function setting(string $k, string $d = ''): string
{
    return settings()[$k] ?? $d;
}

function save_setting(string $k, string $v): void
{
    db()->prepare('INSERT OR REPLACE INTO settings(k,v) VALUES(?,?)')->execute([$k, $v]);
}

function instagram_url(string $v): string
{
    $v = trim($v);
    if ($v === '') {
        return '';
    }
    if (preg_match('~^https?://~i', $v)) {
        return $v;
    }
    return 'https://instagram.com/' . rawurlencode(ltrim($v, '@/'));
}

/* ------------------------------------------------------------------ */
/* بارکردنی وێنە                                                        */
/* ------------------------------------------------------------------ */

function save_image(array $f): ?string
{
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($f['error'] !== UPLOAD_ERR_OK || $f['size'] > 5 * 1024 * 1024) {
        flash('بارکردنی وێنە سەرکەوتوو نەبوو یان قەبارەکەی لە ٥ مێگابایت زیاترە.', 'err');
        return null;
    }
    $info = @getimagesize($f['tmp_name']);
    $types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    $ext = $types[$info[2] ?? 0] ?? null;
    if (!$ext) {
        flash('تەنیا وێنەی JPG، PNG یان WebP ڕێگەپێدراوە.', 'err');
        return null;
    }
    $dir = BASE_DIR . '/uploads';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $name = 'hero_' . bin2hex(random_bytes(6)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) {
        flash('پاشەکەوتکردنی وێنە سەرکەوتوو نەبوو.', 'err');
        return null;
    }
    return 'uploads/' . $name;
}

/* ------------------------------------------------------------------ */
/* ڕاپۆرتەکان                                                           */
/* ------------------------------------------------------------------ */

/** timestamp لە پێکهاتەکانی بەروار؛ ڕۆژی زیادە دەگەڕێتەوە بۆ کۆتایی مانگ */
function date_parts_ts(int $y, int $m, int $d, bool $end = false): ?int
{
    if ($y < 1970 || $y > 2100 || $m < 1 || $m > 12 || $d < 1) {
        return null;
    }
    $d = min($d, (int)date('t', mktime(0, 0, 0, $m, 1, $y)));
    return $end ? mktime(23, 59, 59, $m, $d, $y) : mktime(0, 0, 0, $m, $d, $y);
}

function year_now(): int
{
    return (int)date('Y');
}

/** سێ لیستی کەوانە (ڕۆژ / مانگ / ساڵ)ی میلادی؛ بەهای بنەڕەتی لە timestamp */
function date_select(string $prefix, int $ts): string
{
    $sy = (int)date('Y', $ts);
    $sm = (int)date('n', $ts);
    $sd = (int)date('j', $ts);
    $nowY = year_now();
    $y0 = min($nowY - 5, $sy);
    $y1 = max($nowY + 1, $sy);
    $months = ku_months();

    $h = '<span class="jdate">';
    $h .= '<select name="' . e($prefix) . '_d" aria-label="ڕۆژ">';
    for ($d = 1; $d <= 31; $d++) {
        $h .= '<option value="' . $d . '"' . ($d === $sd ? ' selected' : '') . '>' . ku_digits($d) . '</option>';
    }
    $h .= '</select><select name="' . e($prefix) . '_m" aria-label="مانگ">';
    for ($m = 1; $m <= 12; $m++) {
        $h .= '<option value="' . $m . '"' . ($m === $sm ? ' selected' : '') . '>' . e($months[$m]) . '</option>';
    }
    $h .= '</select><select name="' . e($prefix) . '_y" aria-label="ساڵ">';
    for ($y = $y1; $y >= $y0; $y--) {
        $h .= '<option value="' . $y . '"' . ($y === $sy ? ' selected' : '') . '>' . ku_digits($y) . '</option>';
    }
    $h .= '</select></span>';
    return $h;
}

/** خوێندنەوەی بەروار لە لیستە نێردراوەکان (prefix_y / prefix_m / prefix_d) */
function date_from_request(array $src, string $prefix, bool $end = false): ?int
{
    if (!isset($src[$prefix . '_y'], $src[$prefix . '_m'], $src[$prefix . '_d'])) {
        return null;
    }
    return date_parts_ts((int)$src[$prefix . '_y'], (int)$src[$prefix . '_m'], (int)$src[$prefix . '_d'], $end);
}

/** بازەی کات لە پارامەتەرەکانی GET: range=today|week|month|all یان from_* / to_* (لیستە کەوانەکانی بەروار) */
function report_range(): array
{
    $today = mktime(0, 0, 0);
    $range = (string)($_GET['range'] ?? '');
    $from = $to = null;

    if ($range === 'today') {
        $from = $today;
        $to = $today + 86399;
    } elseif ($range === 'week') {
        $off = ((int)date('w', $today) + 1) % 7; // دەستپێکی هەفتە: شەممە
        $from = strtotime("-{$off} days", $today);
        $to = $today + 86399;
    } elseif ($range === 'all') {
        $from = 0;
        $to = PHP_INT_MAX;
    } elseif (isset($_GET['from_y'])) {
        $from = date_from_request($_GET, 'from');
        $to = date_from_request($_GET, 'to', true);
        $range = 'custom';
    }
    if ($from === null || $to === null) {
        $range = 'month';
        $from = month_start();
        $to = $today + 86399;
    }
    if ($from > $to) {
        [$from, $to] = [mktime(0, 0, 0, (int)date('n', $to), (int)date('j', $to), (int)date('Y', $to)),
                        mktime(23, 59, 59, (int)date('n', $from), (int)date('j', $from), (int)date('Y', $from))];
    }
    return [
        'from' => $from,
        'to' => $to,
        // بۆ پیشاندان لە لیستە کەوانەکان (لە دۆخی «هەموو» لە سەرەتای مانگی ئێستا تا ئەمڕۆ)
        'from_show' => $from > 0 ? $from : month_start(),
        'to_show' => $to < PHP_INT_MAX ? $to : $today,
        'range' => $range,
    ];
}

function sales_summary(int $from, int $to, ?string $pay = null): array
{
    $sql = 'SELECT COUNT(*) c, COALESCE(SUM(total),0) t, COALESCE(SUM(discount),0) d
            FROM sales WHERE created_at BETWEEN ? AND ?';
    $args = [$from, $to];
    if ($pay !== null) {
        $sql .= ' AND payment = ?';
        $args[] = $pay;
    }
    $st = db()->prepare($sql);
    $st->execute($args);
    $r = $st->fetch();
    return ['count' => (int)$r['c'], 'total' => (int)$r['t'], 'discount' => (int)$r['d']];
}

/** کۆی قەرزە پاکنەکراوەکان (هەموو کاتەکان) */
function credit_outstanding(): array
{
    $r = db()->query("SELECT COUNT(*) c, COALESCE(SUM(total),0) t FROM sales WHERE payment='credit'")->fetch();
    return ['count' => (int)$r['c'], 'total' => (int)$r['t']];
}

/** ئامارە تەواوەکانی فرۆشتنی بازەیەک: کۆکان، جیاکردنەوەی پارەدان، فرۆشی ڕۆژانە و پڕفرۆشەکان */
function report_data(int $from, int $to, int $topLimit = 10): array
{
    $db = db();
    $st = $db->prepare('SELECT created_at, subtotal, discount, total, payment FROM sales WHERE created_at BETWEEN ? AND ?');
    $st->execute([$from, $to]);

    $sum = ['count' => 0, 'subtotal' => 0, 'discount' => 0, 'total' => 0, 'avg' => 0];
    $byPay = [];
    $byDay = [];
    foreach ($st as $s) {
        $sum['count']++;
        $sum['subtotal'] += (int)$s['subtotal'];
        $sum['discount'] += (int)$s['discount'];
        $sum['total'] += (int)$s['total'];
        $byPay[$s['payment']] = ($byPay[$s['payment']] ?? 0) + (int)$s['total'];
        $day = date('Y-m-d', (int)$s['created_at']);
        $byDay[$day]['ts'] = (int)$s['created_at'];
        $byDay[$day]['count'] = ($byDay[$day]['count'] ?? 0) + 1;
        $byDay[$day]['total'] = ($byDay[$day]['total'] ?? 0) + (int)$s['total'];
    }
    ksort($byDay);
    $sum['avg'] = $sum['count'] ? intdiv($sum['total'], $sum['count']) : 0;

    $tp = $db->prepare('SELECT si.name, SUM(si.qty) q, SUM(si.qty*si.price) amount
                        FROM sale_items si JOIN sales s ON s.id=si.sale_id
                        WHERE s.created_at BETWEEN ? AND ? GROUP BY si.name ORDER BY q DESC, amount DESC LIMIT ' . (int)$topLimit);
    $tp->execute([$from, $to]);

    $ex = $db->prepare('SELECT category, SUM(amount) t FROM expenses WHERE created_at BETWEEN ? AND ? GROUP BY category ORDER BY t DESC');
    $ex->execute([$from, $to]);
    $expByCat = [];
    $expTotal = 0;
    foreach ($ex as $row) {
        $expByCat[$row['category']] = (int)$row['t'];
        $expTotal += (int)$row['t'];
    }

    return [
        'sum' => $sum,
        'byPay' => $byPay,
        'byDay' => $byDay,
        'top' => $tp->fetchAll(),
        'credit' => $byPay['credit'] ?? 0,
        'exp' => ['total' => $expTotal, 'byCat' => $expByCat],
        'balance' => $sum['total'] - $expTotal,
    ];
}

/** بازەی ڕاپۆرتی خولی (ڕۆژانە/هەفتانە/مانگانە/ساڵانە) کە بەرواری سەرچاوەی تێدایە */
function period_bounds(string $type, int $refTs): array
{
    $day = mktime(0, 0, 0, (int)date('n', $refTs), (int)date('j', $refTs), (int)date('Y', $refTs));
    $jy = (int)date('Y', $day);
    $jm = (int)date('n', $day);

    switch ($type) {
        case 'weekly':
            $off = ((int)date('w', $day) + 1) % 7;
            $from = strtotime("-{$off} days", $day);
            $to = strtotime('+6 days', $from) + 86399;
            return [$from, $to, 'ڕاپۆرتی فرۆشتنی هەفتانە'];
        case 'monthly':
            $from = date_parts_ts($jy, $jm, 1);
            $to = ($jm === 12 ? date_parts_ts($jy + 1, 1, 1) : date_parts_ts($jy, $jm + 1, 1)) - 1;
            return [$from, $to, 'ڕاپۆرتی فرۆشتنی مانگانە'];
        case 'yearly':
            $from = date_parts_ts($jy, 1, 1);
            $to = date_parts_ts($jy + 1, 1, 1) - 1;
            return [$from, $to, 'ڕاپۆرتی فرۆشتنی ساڵانە'];
        default:
            return [$day, $day + 86399, 'ڕاپۆرتی فرۆشتنی ڕۆژانە'];
    }
}

/* ------------------------------------------------------------------ */
/* ناونیشانی ماڵپەڕ (بۆ QR و بڵاوکردنەوە)                               */
/* ------------------------------------------------------------------ */

function site_url(): string
{
    $saved = trim(setting('site_url'));
    if ($saved !== '') {
        return $saved;
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') === '443');
    $host = preg_replace('~[^A-Za-z0-9.\-:\[\]]~', '', (string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    if (substr($dir, -6) === '/admin') {
        $dir = substr($dir, 0, -6);
    }
    return ($https ? 'https' : 'http') . '://' . $host . $dir . '/';
}
