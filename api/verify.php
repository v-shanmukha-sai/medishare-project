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
  $st = $pdo->prepare("SELECT u.id, u.name, u.role, u.verified,
                              COALESCE(d.license_image, n.license_image) AS license_image
                       FROM users u
                       LEFT JOIN distributors d ON d.user_id = u.id
                       LEFT JOIN ngos n ON n.user_id = u.id
                       WHERE u.id = ? AND u.role <> 'admin'");
  $st->execute([$userId]);
  $account = $st->fetch();
  if (!$account) respond(['ok' => false, 'error' => 'Account not found.'], 404);
  if ((int)$account['verified'] !== 0) respond(['ok' => false, 'error' => 'This account is already verified.'], 409);

  $pdo->beginTransaction();
  try {
    if ($action === 'approve') {
      $pdo->prepare('UPDATE users SET verified = 1 WHERE id = ?')->execute([$userId]);
      $pdo->prepare('UPDATE distributors SET verified = 1 WHERE user_id = ?')->execute([$userId]);
      $pdo->prepare('UPDATE ngos SET verified = 1 WHERE user_id = ?')->execute([$userId]);
      notify($pdo, $userId, 'verify', 'Your account was approved. You can now log in.');
    } else {
      /* reject = remove the pending registration completely, so it leaves the
         verification list and the email can be used to register again. */
      $pdo->prepare('DELETE FROM notifications WHERE user_id = ?')->execute([$userId]);
      $pdo->prepare('DELETE FROM audit_logs WHERE user_id = ?')->execute([$userId]);
      $pdo->prepare('DELETE FROM distributors WHERE user_id = ?')->execute([$userId]);
      $pdo->prepare('DELETE FROM ngos WHERE user_id = ?')->execute([$userId]);
      $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$userId]);
    }
    audit_log($pdo, (int)$_SESSION['user']['id'], ucfirst($action) . 'd account: ' . $account['name']);
    $pdo->commit();
    if ($action === 'reject') storage_delete_upload($account['license_image'], $account['role']);
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
$pending = $st->fetchAll();
foreach ($pending as &$p) {
  $p['license_image'] = storage_document_url($p['license_image'], $p['role']);
}
unset($p);
$out = ['ok' => true, 'pending' => $pending];
cache_put('verify:pending', $out);
respond($out);
