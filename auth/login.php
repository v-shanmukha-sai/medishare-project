<?php
require __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$d = body();
$email = strtolower(trim($d['email'] ?? ''));
$password = $d['password'] ?? '';

$st = db()->prepare('SELECT id, name, email, password, role, org_type, verified, address FROM users WHERE email = ?');
$st->execute([$email]);
$user = $st->fetch();

if (!$user || !password_verify($password, $user['password'])) {
  respond(['ok' => false, 'error' => 'Invalid email or password.'], 401);
}
if (!$user['verified']) {
  respond(['ok' => false, 'error' => 'Account pending admin verification.'], 403);
}

session_regenerate_id(true);
$_SESSION['user'] = [
  'id' => (int)$user['id'],
  'name' => $user['name'],
  'email' => $user['email'],
  'role' => $user['role'],
  'org_type' => $user['org_type'],
  'address' => $user['address'],
];
audit_log(db(), (int)$user['id'], 'Logged in');
respond(['ok' => true, 'user' => $_SESSION['user']]);
