<?php
require __DIR__ . '/../config/db.php';
$pdo = db();
$u = current_user();
if (!$u) respond(['ok' => false, 'error' => 'Not logged in.'], 401);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $b = body();
  $name = trim($b['name'] ?? '');
  $phone = trim($b['phone'] ?? '');
  $address = trim($b['address'] ?? '');
  $lat = trim($b['latitude'] ?? '');
  $lng = trim($b['longitude'] ?? '');
  if ($name === '') respond(['ok' => false, 'error' => 'Full name is required.'], 422);
  $st = $pdo->prepare('UPDATE users SET name = ?, phone = ?, address = ?, latitude = ?, longitude = ? WHERE id = ?');
  $st->execute([$name, $phone, $address, $lat ?: null, $lng ?: null, $u['id']]);
  $_SESSION['user']['name'] = $name;
  audit_log($pdo, (int)$u['id'], 'Updated profile');
  respond(['ok' => true]);
}

$st = $pdo->prepare('SELECT id, name, email, phone, address, city, latitude, longitude, role, org_type, verified, created_at FROM users WHERE id = ?');
$st->execute([$u['id']]);
$p = $st->fetch();
if (!$p) respond(['ok' => false, 'error' => 'User not found.'], 404);

$p['license_no'] = null;
$p['registration_no'] = null;
if ($p['role'] === 'distributor') {
  $st = $pdo->prepare('SELECT license_no FROM distributors WHERE user_id = ?');
  $st->execute([$u['id']]);
  $p['license_no'] = $st->fetchColumn() ?: null;
} elseif ($p['role'] === 'org') {
  $st = $pdo->prepare('SELECT registration_no FROM ngos WHERE user_id = ?');
  $st->execute([$u['id']]);
  $p['registration_no'] = $st->fetchColumn() ?: null;
}

if ($p['role'] === 'org') {
  $st = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE org_user_id = ?');
  $st->execute([$u['id']]);
} elseif ($p['role'] === 'distributor') {
  $st = $pdo->prepare('SELECT COUNT(*) FROM orders o JOIN distributors d ON d.id = o.distributor_id WHERE d.user_id = ?');
  $st->execute([$u['id']]);
} else {
  $st = $pdo->query('SELECT COUNT(*) FROM orders');
}
$p['orders_count'] = (int)$st->fetchColumn();

respond(['ok' => true, 'profile' => $p]);
