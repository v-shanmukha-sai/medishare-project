<?php
require __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  require_role('distributor');
  $pdo = db();
  $u = current_user();
  $action = in_array($_POST['action'] ?? '', ['add', 'update', 'delete'], true) ? $_POST['action'] : 'add';

  $st = $pdo->prepare('SELECT id FROM distributors WHERE user_id = ?');
  $st->execute([$u['id']]);
  $distId = (int)$st->fetchColumn();

  if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    $st = $pdo->prepare('SELECT name FROM medicines WHERE id = ? AND distributor_id = ? AND deleted = 0');
    $st->execute([$id, $distId]);
    $name = $st->fetchColumn();
    if ($name === false) respond(['ok' => false, 'error' => 'Medicine not found.'], 404);
    $pdo->prepare('UPDATE medicines SET deleted = 1, stock = 0 WHERE id = ?')->execute([$id]);
    audit_log($pdo, $u['id'], "Removed medicine: $name");
    respond(['ok' => true, 'id' => $id]);
  }

  $name = trim($_POST['name'] ?? '');
  $cat = trim($_POST['category'] ?? '');
  $price = (float)($_POST['price'] ?? 0);
  $mrp = (float)($_POST['mrp'] ?? 0);
  if ($mrp <= $price) $mrp = null;
  $stock = (int)($_POST['stock'] ?? 0);
  $batch = trim($_POST['batch_no'] ?? '');
  $expiry = $_POST['expiry'] ?? '';
  if ($name === '' || $cat === '' || $price <= 0 || $stock < 0 || !strtotime($expiry) || $expiry < date('Y-m-d')) {
    respond(['ok' => false, 'error' => 'Invalid medicine details (expiry must be a future date).'], 422);
  }

  $image = null;
  if (!empty($_FILES['image']['name'])) {
    $f = $_FILES['image'];
    [$ext, $mime, $err] = upload_validate($f, ['jpg', 'jpeg', 'png', 'webp'], 2 * 1024 * 1024);
    if ($err) respond(['ok' => false, 'error' => $err], 422);
    $stored = storage_store_medicine($f['tmp_name'], $ext, $mime);
    if ($stored['error']) respond(['ok' => false, 'error' => $stored['error']], 500);
    $image = $stored['path'];
  }

  if ($action === 'update') {
    $id = (int)($_POST['id'] ?? 0);
    $st = $pdo->prepare('SELECT id, image FROM medicines WHERE id = ? AND distributor_id = ? AND deleted = 0');
    $st->execute([$id, $distId]);
    $old = $st->fetch();
    if (!$old) respond(['ok' => false, 'error' => 'Medicine not found.'], 404);
    $pdo->prepare('UPDATE medicines SET name=?, category=?, price=?, mrp=?, stock=?, batch_no=?, image=?, expiry=?,
                   low_stock_notified = CASE WHEN ? >= 250 THEN 0 ELSE low_stock_notified END,
                   deleted = CASE WHEN ? = 0 THEN 1 ELSE 0 END WHERE id=?')
        ->execute([$name, $cat, $price, $mrp, $stock, $batch, $image ?: $old['image'], $expiry, $stock, $stock, $id]);
    if ($image && $old['image']) storage_delete_upload($old['image']);
    if ($stock === 0) {
      notify($pdo, $u['id'], 'low_stock', "Your medicine '$name' is out of stock and was removed from the store.");
    }
    audit_log($pdo, $u['id'], "Updated medicine: $name");
    respond(['ok' => true, 'id' => $id]);
  }

  $st = $pdo->prepare('INSERT INTO medicines (distributor_id, name, category, price, mrp, stock, batch_no, image, expiry) VALUES (?,?,?,?,?,?,?,?,?) RETURNING id');
  $st->execute([$distId, $name, $cat, $price, $mrp, $stock, $batch, $image, $expiry]);
  $newId = (int)$st->fetchColumn();
  audit_log($pdo, $u['id'], "Added medicine: $name");
  respond(['ok' => true, 'id' => $newId]);
}

/* ---------- GET (public) ---------- */
if (isset($_GET['id'])) {
  $id = (int)$_GET['id'];
  $cached = cache_get("medicines:item:$id", 20);
  if ($cached !== null) respond($cached);
  $pdo = db();
  $st = $pdo->prepare('SELECT m.*, u.name AS distributor, u.phone, u.address, u.id AS distributor_user_id,
                              COALESCE(AVG(r.rating), 0) AS rating
                       FROM medicines m
                       JOIN distributors d ON d.id = m.distributor_id
                       JOIN users u ON u.id = d.user_id
                       LEFT JOIN orders o ON o.distributor_id = d.id
                       LEFT JOIN reviews r ON r.order_id = o.id
                       WHERE m.id = ? AND m.deleted = 0 GROUP BY m.id, u.id, u.name, u.phone, u.address');
  $st->execute([$id]);
  $m = $st->fetch();
  if (!$m) respond(['ok' => false, 'error' => 'Medicine not found.'], 404);
  $m['shelf_months'] = max(0, (int)((strtotime($m['expiry']) - time()) / 2592000));
  $m['image'] = storage_medicine_url($m['image']);
  $out = ['ok' => true, 'medicine' => $m];
  cache_put("medicines:item:$id", $out);
  respond($out);
}

$u = current_user();
$where = ['m.expiry >= CURRENT_DATE', 'm.deleted = 0'];
$args = [];

$q = strtolower(trim($_GET['q'] ?? ''));
$cat = $_GET['cat'] ?? '';
$min = isset($_GET['shelf_min']) ? (int)$_GET['shelf_min'] : 0;
$max = isset($_GET['shelf_max']) ? (int)$_GET['shelf_max'] : 0;

$cacheKey = 'medicines:list:' . ($u['id'] ?? 0) . ':' . md5(json_encode([$q, $cat, $min, $max]));
$cached = cache_get($cacheKey, 20);
if ($cached !== null) respond($cached);

$pdo = db();

/* sweep: expired medicines are removed from the store, distributor notified once */
$expired = $pdo->query("SELECT m.id, m.name, m.batch_no, m.expiry, d.user_id
                        FROM medicines m JOIN distributors d ON d.id = m.distributor_id
                        WHERE m.expiry < CURRENT_DATE AND m.deleted = 0")->fetchAll();
if ($expired) {
  $hide = $pdo->prepare('UPDATE medicines SET stock = 0, deleted = 1 WHERE id = ?');
  foreach ($expired as $m) {
    $hide->execute([(int)$m['id']]);
    notify($pdo, (int)$m['user_id'], 'expiry', "Your medicine '{$m['name']}' (Batch " . ($m['batch_no'] ?: '-') . ") expired on {$m['expiry']} and was removed from the store.");
  }
}

/* sweep: out-of-stock medicines are removed directly, distributor notified once */
$zero = $pdo->query("SELECT m.id, m.name, d.user_id
                     FROM medicines m JOIN distributors d ON d.id = m.distributor_id
                     WHERE m.stock <= 0 AND m.deleted = 0")->fetchAll();
if ($zero) {
  $hide = $pdo->prepare('UPDATE medicines SET deleted = 1 WHERE id = ?');
  foreach ($zero as $m) {
    $hide->execute([(int)$m['id']]);
    notify($pdo, (int)$m['user_id'], 'low_stock', "Your medicine '{$m['name']}' is out of stock and was removed from the store.");
  }
}

/* sweep: low stock alert (stock < 250) — notify distributor once per medicine */
$low = $pdo->query("SELECT m.id, m.name, m.batch_no, m.stock, d.user_id
                    FROM medicines m JOIN distributors d ON d.id = m.distributor_id
                    WHERE m.stock > 0 AND m.stock < 250 AND m.low_stock_notified = 0 AND m.deleted = 0")->fetchAll();
if ($low) {
  $mark = $pdo->prepare('UPDATE medicines SET low_stock_notified = 1 WHERE id = ?');
  foreach ($low as $m) {
    $mark->execute([(int)$m['id']]);
    notify($pdo, (int)$m['user_id'], 'low_stock', "Your medicine '{$m['name']}' (Batch " . ($m['batch_no'] ?: '-') . ") is low on stock ({$m['stock']} units left).");
  }
}

if ($u && $u['role'] === 'distributor') {
  $st = $pdo->prepare('SELECT id FROM distributors WHERE user_id = ?');
  $st->execute([$u['id']]);
  $where[] = 'm.distributor_id = ?';
  $args[] = (int)$st->fetchColumn();
}

$sql = 'SELECT m.*, u.name AS distributor, u.phone, u.address, u.id AS distributor_user_id
        FROM medicines m
        JOIN distributors d ON d.id = m.distributor_id
        JOIN users u ON u.id = d.user_id';
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= ' ORDER BY m.expiry';
$st = $pdo->prepare($sql);
$st->execute($args);
$medicines = $st->fetchAll();

foreach ($medicines as &$m) {
  $m['shelf_months'] = max(0, (int)((strtotime($m['expiry']) - time()) / 2592000));
  $m['image'] = storage_medicine_url($m['image']);
  $skip = ($q && !str_contains(strtolower($m['name']), $q)) || ($cat && $m['category'] !== $cat);
  if ($min && $m['shelf_months'] < $min) $skip = true;
  if ($max && $m['shelf_months'] > $max) $skip = true;
  if ($skip) $m = null;
}
$out = ['ok' => true, 'medicines' => array_values(array_filter($medicines))];
cache_put($cacheKey, $out);
respond($out);
