<?php
/* گۆڕینی قەرز بۆ یەکێک لە شێوازەکانی پارەدان (بە داخستنی پەنجەرەی پسوولە بانگ دەکرێت) */
require __DIR__ . '/../inc/bootstrap.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false]);
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$pay = (string)($_POST['payment'] ?? '');
if (!$id || !isset(pay_methods()[$pay])) {
    http_response_code(422);
    echo json_encode(['ok' => false]);
    exit;
}

// تەنیا پسوولەی قەرز دەتوانرێت پاک بکرێتەوە
$st = db()->prepare("UPDATE sales SET payment = ?, paid_at = ? WHERE id = ? AND payment = 'credit'");
$st->execute([$pay, time(), $id]);
$ok = $st->rowCount() > 0;
if ($ok) {
    flash('قەرزی پسوولەی ژمارە ' . ku_num($id) . ' بە شێوازی «' . pay_label($pay) . '» پاک کرایەوە.');
}
echo json_encode(['ok' => $ok]);
