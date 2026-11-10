<?php
require __DIR__ . '/../config/db.php';

$name = trim($_POST['name'] ?? '');
$email = strtolower(trim($_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';
$role = strtolower(trim($_POST['role'] ?? ''));
if ($role === 'ngo') $role = 'org';
$orgType = $_POST['orgType'] ?? null;
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');
$city = trim($_POST['city'] ?? '');
$credential = trim($_POST['credential_no'] ?? '');
if (!in_array($orgType, ['NGO', 'Medical Shop', 'Old-Age Home', 'Orphanage', 'Charitable Hospital'], true)) $orgType = 'NGO';

if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
  respond(['ok' => false, 'error' => 'Invalid name, email, or password (min 6 chars).'], 422);
}
if (!in_array($role, ['distributor', 'org'], true)) {
  respond(['ok' => false, 'error' => 'Invalid account type.'], 422);
}
if ($phone === '' || $city === '') {
  respond(['ok' => false, 'error' => 'Contact phone and city are required.'], 422);
}
if ($credential === '' || strlen($credential) > 60) {
  respond(['ok' => false, 'error' => 'A valid license / registration number is required (max 60 characters).'], 422);
}

$pdo = db();
$st = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$st->execute([$email]);
if ($st->fetch()) {
  respond(['ok' => false, 'error' => 'Email already registered.'], 409);
}

/* license/registration document image (required for both roles) */
$image = null;
if (!empty($_FILES['license_image']['name'])) {
  $f = $_FILES['license_image'];
  [$ext, $mime, $err] = upload_validate($f, ['jpg', 'jpeg', 'png', 'webp'], 2 * 1024 * 1024);
  if ($err) respond(['ok' => false, 'error' => $err], 422);
  $stored = storage_store_document($f['tmp_name'], $ext, $mime, $role);
  if ($stored['error']) respond(['ok' => false, 'error' => $stored['error']], 500);
  $image = $stored['path'];
} else {
  respond(['ok' => false, 'error' => 'License / registration document image is required.'], 422);
}

$pdo->beginTransaction();
try {
  $st = $pdo->prepare('INSERT INTO users (name, email, password, phone, address, city, latitude, longitude, role, org_type, verified)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)');
  $st->execute([
    $name, $email, password_hash($password, PASSWORD_DEFAULT),
    $phone, $address, $city,
    $_POST['latitude'] ?? null,
    $_POST['longitude'] ?? null,
    $role, $role === 'org' ? ($orgType ?: 'NGO') : null
  ]);
  $userId = (int)$pdo->lastInsertId();
  if ($role === 'distributor') {
    $pdo->prepare('INSERT INTO distributors (user_id, license_no, license_image, verified) VALUES (?, ?, ?, 0)')
        ->execute([$userId, $credential, $image]);
  } else {
    $pdo->prepare('INSERT INTO ngos (user_id, org_type, registration_no, license_image, verified) VALUES (?, ?, ?, ?, 0)')
        ->execute([$userId, $orgType ?: 'NGO', $credential, $image]);
  }
  audit_log($pdo, $userId, 'Registered account (pending verification)');
  $pdo->commit();
} catch (Exception $e) {
  $pdo->rollBack();
  if ($image) storage_delete_upload($image, $role);
  respond(['ok' => false, 'error' => 'Registration failed.'], 500);
}

respond(['ok' => true, 'message' => 'Registration submitted. Await admin verification before login.']);
