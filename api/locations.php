<?php
/* MEDRED location API - returns verified orgs/distributors with lat/lng for the delivery map. */
require __DIR__ . '/../config/db.php';
current_user() ?? respond(['ok' => false, 'error' => 'Not logged in.'], 401);

/* show verified orgs (delivery destinations); admin/distributor can see all */
$role = current_user()['role'];
$cacheKey = 'locations:' . current_user()['id'];
$cached = cache_get($cacheKey, 30);
if ($cached !== null) respond($cached);
$pdo = db();
$sql = 'SELECT u.id, u.name, u.role, u.org_type, u.city, u.latitude, u.longitude, u.address,
               d.license_no, n.registration_no
        FROM users u
        LEFT JOIN distributors d ON d.user_id = u.id
        LEFT JOIN ngos n ON n.user_id = u.id
        WHERE u.verified = 1 AND u.latitude IS NOT NULL AND u.longitude IS NOT NULL';
$args = [];
if ($role === 'distributor') {
  $st = $pdo->prepare('SELECT id FROM distributors WHERE user_id = ?');
  $st->execute([current_user()['id']]);
  $myDistId = (int)$st->fetchColumn();
  $sql .= ' AND u.role <> ?';
  $args[] = 'distributor';
}
$sql .= ' ORDER BY u.name';
$st = $pdo->prepare($sql);
$st->execute($args);
$locations = [];
foreach ($st->fetchAll() as $row) {
  $locations[] = [
    'id' => (int)$row['id'],
    'name' => $row['name'],
    'role' => $row['role'],
    'org_type' => $row['org_type'],
    'city' => $row['city'],
    'address' => $row['address'],
    'lat' => (float)$row['latitude'],
    'lng' => (float)$row['longitude'],
    'license_no' => $row['license_no'],
    'registration_no' => $row['registration_no'],
  ];
}
$out = ['ok' => true, 'locations' => $locations, 'my_role' => $role];
cache_put($cacheKey, $out);
respond($out);
