<?php
require __DIR__ . '/../config/db.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $pdo = db();
  $d = body();
  $userId = (int)($d['user_id'] ?? 0);
  $action = $d['action'] ?? '';
  if (!in_array($action, ['approve', 'reject'], true) || !$userId) {
    respond(['ok' => false, 'error' => 'Invalid request.'], 422);
  }
  $verified = $action === 'approve' ? 1 : 0;
  $pdo->beginTransaction();
  try {
    $pdo->prepare('UPDATE users SET verified = ? WHERE id = ?')->execute([$verified, $userId]);
    $pdo->prepare('UPDATE distributors SET verified = ? WHERE user_id = ?')->execute([$verified, $userId]);
    $pdo->prepare('UPDATE ngos SET verified = ? WHERE user_id = ?')->execute([$verified, $userId]);
    $msg = $action === 'approve' ? 'Your account was approved. You can now log in.' : 'Your account was rejected. Contact admin.';
    notify($pdo, $userId, 'verify', $msg);
    $st = $pdo->prepare('SELECT name FROM users WHERE id = ?');
    $st->execute([$userId]);
    audit_log($pdo, $_SESSION['user']['id'], ucfirst($action) . 'd account: ' . $st->fetchColumn());
    $pdo->commit();
    respond(['ok' => true]);
  } catch (Exception $e) {
    $pdo->rollBack();
    respond(['ok' => false, 'error' => 'Update failed.'], 500);
  }
}

$cached = cache_get('verify:pending', 10);
if ($cached !== null) respond($cached);
$pdo = db();
$st = $pdo->query("SELECT u.id, u.name, u.role, u.org_type, u.created_at, u.email, u.phone, u.city,
                          d.license_no, n.registration_no,
                          COALESCE(d.license_image, n.license_image) AS license_image
                   FROM users u
                   LEFT JOIN distributors d ON d.user_id = u.id
                   LEFT JOIN ngos n ON n.user_id = u.id
                   WHERE u.verified = 0 AND u.role <> 'admin' ORDER BY u.id");
$out = ['ok' => true, 'pending' => $st->fetchAll()];
cache_put('verify:pending', $out);
respond($out);
