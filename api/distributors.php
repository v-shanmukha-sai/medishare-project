<?php
require __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  require_role('admin');
  $pdo = db();
  $d = body();
  $userId = (int)($d['user_id'] ?? 0);
  $action = $d['action'] ?? '';
  if ($action !== 'remove' || !$userId) respond(['ok' => false, 'error' => 'Invalid request.'], 422);

  $st = $pdo->prepare('SELECT d.id, (SELECT COUNT(*) FROM medicines m WHERE m.distributor_id = d.id) AS meds,
                       (SELECT COUNT(*) FROM orders o WHERE o.distributor_id = d.id) AS orders
                       FROM distributors d WHERE d.user_id = ?');
  $st->execute([$userId]);
  $dist = $st->fetch();
  if (!$dist) respond(['ok' => false, 'error' => 'Distributor not found.'], 404);
  if ($dist['meds'] > 0 || $dist['orders'] > 0) {
    respond(['ok' => false, 'error' => "Cannot remove: distributor has {$dist['meds']} medicine(s) and {$dist['orders']} order(s). Delete those first."], 409);
  }
  $st = $pdo->prepare('SELECT name FROM users WHERE id = ?');
  $st->execute([$userId]);
  $name = $st->fetchColumn();
  $pdo->prepare('DELETE FROM audit_logs WHERE user_id = ?')->execute([$userId]);
  $pdo->prepare('DELETE FROM notifications WHERE user_id = ?')->execute([$userId]);
  $pdo->prepare('DELETE FROM distributors WHERE user_id = ?')->execute([$userId]);
  $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$userId]);
  audit_log($pdo, $_SESSION['user']['id'], "Removed distributor: $name");
  respond(['ok' => true]);
}

/* GET: public profile (?id=N) or admin list (no params) */
if (isset($_GET['id'])) {
  $pdo = db();
  $st = $pdo->prepare('SELECT u.id, u.name, u.phone, u.address, u.email, u.verified, d.license_no, d.id AS dist_id, u.created_at
                       FROM users u JOIN distributors d ON d.user_id = u.id WHERE u.id = ?');
  $st->execute([(int)$_GET['id']]);
  $prof = $st->fetch();
  if (!$prof) respond(['ok' => false, 'error' => 'Distributor not found.'], 404);
  $st = $pdo->prepare('SELECT id, name, category, price, mrp, stock, batch_no, expiry FROM medicines WHERE distributor_id = ?
                       ORDER BY expiry');
  $st->execute([$prof['dist_id']]);
  $prof['medicines'] = $st->fetchAll();
  respond(['ok' => true, 'profile' => $prof]);
}

/* GET: public list of verified distributors (store filter) */
if (isset($_GET['list'])) {
  $cached = cache_get('distributors:list', 60);
  if ($cached !== null) respond($cached);
  $st = db()->query('SELECT u.id, u.name, u.city FROM distributors d JOIN users u ON u.id = d.user_id
                     WHERE d.verified = 1 AND u.verified = 1 ORDER BY u.name');
  $out = ['ok' => true, 'distributors' => $st->fetchAll()];
  cache_put('distributors:list', $out);
  respond($out);
}

require_role('admin');
$cached = cache_get('distributors:admin', 30);
if ($cached !== null) respond($cached);
$st = db()->query('SELECT u.id, u.name, u.email, u.phone, u.verified, u.created_at, d.license_no,
                   (SELECT COUNT(*) FROM medicines m WHERE m.distributor_id = d.id) AS meds,
                   (SELECT COUNT(*) FROM orders o WHERE o.distributor_id = d.id) AS orders
                   FROM distributors d JOIN users u ON u.id = d.user_id ORDER BY u.id');
$out = ['ok' => true, 'distributors' => $st->fetchAll()];
cache_put('distributors:admin', $out);
respond($out);
