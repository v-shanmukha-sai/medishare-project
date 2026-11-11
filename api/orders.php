<?php
require __DIR__ . '/../config/db.php';
$u = current_user();
if (!$u) respond(['ok' => false, 'error' => 'Not logged in.'], 401);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $pdo = db();
  $d = body();
  $action = $d['action'] ?? '';

  /* distributor accepts/rejects a pending order */
  if ($action === 'accept' || $action === 'reject') {
    require_role('distributor');
    $orderId = (int)($d['order_id'] ?? 0);
    $st = $pdo->prepare('SELECT o.id, o.status, o.org_user_id FROM orders o
                         JOIN distributors dd ON dd.id = o.distributor_id
                         WHERE o.id = ? AND dd.user_id = ?');
    $st->execute([$orderId, $u['id']]);
    $order = $st->fetch();
    if (!$order) respond(['ok' => false, 'error' => 'Order not found.'], 404);
    if ($order['status'] !== 'Pending') respond(['ok' => false, 'error' => "Order is already " . strtolower($order['status']) . '.'], 409);

    if ($action === 'accept') {
      $pdo->prepare("WITH upd AS (UPDATE orders SET status = 'Accepted' WHERE id = ? RETURNING id)
                     INSERT INTO audit_logs (user_id, action) SELECT ?, 'Accepted order #' || id FROM upd")
          ->execute([$orderId, $u['id']]);
      notify($pdo, (int)$order['org_user_id'], 'order', "Order #$orderId was accepted by the distributor.");
      respond(['ok' => true, 'status' => 'Accepted']);
    }

    $pdo->beginTransaction();
    try {
      $st = $pdo->prepare('SELECT medicine_id, qty FROM order_items WHERE order_id = ?');
      $st->execute([$orderId]);
      foreach ($st->fetchAll() as $it) {
        $pdo->prepare('UPDATE medicines SET stock = stock + ? WHERE id = ?')->execute([(int)$it['qty'], (int)$it['medicine_id']]);
      }
      $pdo->prepare('UPDATE medicines SET low_stock_notified = 0 WHERE stock >= 250')->execute();
      $pdo->prepare("UPDATE orders SET status = 'Cancelled' WHERE id = ?")->execute([$orderId]);
      $pdo->prepare("UPDATE transactions SET status = 'Cancelled' WHERE order_id = ?")->execute([$orderId]);
      notify($pdo, (int)$order['org_user_id'], 'order', "Order #$orderId was rejected by the distributor. Reserved stock restored.");
      audit_log($pdo, $u['id'], "Rejected order #$orderId");
      $pdo->commit();
      respond(['ok' => true, 'status' => 'Cancelled']);
    } catch (Exception $e) {
      $pdo->rollBack();
      respond(['ok' => false, 'error' => 'Could not reject the order.'], 500);
    }
  }

  /* distributor packs an accepted order */
  if ($action === 'pack') {
    require_role('distributor');
    $orderId = (int)($d['order_id'] ?? 0);
    $st = $pdo->prepare('SELECT o.id, o.status, o.org_user_id FROM orders o
                         JOIN distributors dd ON dd.id = o.distributor_id
                         WHERE o.id = ? AND dd.user_id = ?');
    $st->execute([$orderId, $u['id']]);
    $order = $st->fetch();
    if (!$order) respond(['ok' => false, 'error' => 'Order not found.'], 404);
    if ($order['status'] !== 'Accepted') respond(['ok' => false, 'error' => 'Only accepted orders can be packed.'], 409);
    $pdo->prepare("WITH upd AS (UPDATE orders SET status = 'Packed' WHERE id = ? RETURNING id)
                   INSERT INTO audit_logs (user_id, action) SELECT ?, 'Packed order #' || id FROM upd")
        ->execute([$orderId, $u['id']]);
    notify($pdo, (int)$order['org_user_id'], 'order', "Order #$orderId has been packed and is ready to ship.");
    respond(['ok' => true, 'status' => 'Packed']);
  }

  /* distributor ships a packed order */
  if ($action === 'ship') {
    require_role('distributor');
    $orderId = (int)($d['order_id'] ?? 0);
    $st = $pdo->prepare('SELECT o.id, o.status, o.org_user_id FROM orders o
                         JOIN distributors dd ON dd.id = o.distributor_id
                         WHERE o.id = ? AND dd.user_id = ?');
    $st->execute([$orderId, $u['id']]);
    $order = $st->fetch();
    if (!$order) respond(['ok' => false, 'error' => 'Order not found.'], 404);
    if ($order['status'] !== 'Packed') {
      respond(['ok' => false, 'error' => 'Only packed orders can be shipped.'], 409);
    }
    $pdo->prepare("WITH upd AS (UPDATE orders SET status = 'Shipped' WHERE id = ? RETURNING id)
                   INSERT INTO audit_logs (user_id, action) SELECT ?, 'Shipped order #' || id FROM upd")
        ->execute([$orderId, $u['id']]);
    notify($pdo, (int)$order['org_user_id'], 'order', "Order #$orderId has been shipped.");
    respond(['ok' => true, 'status' => 'Shipped']);
  }

  /* distributor hands the parcel to the delivery agent */
  if ($action === 'out_for_delivery') {
    require_role('distributor');
    $orderId = (int)($d['order_id'] ?? 0);
    $st = $pdo->prepare('SELECT o.id, o.status, o.org_user_id FROM orders o
                         JOIN distributors dd ON dd.id = o.distributor_id
                         WHERE o.id = ? AND dd.user_id = ?');
    $st->execute([$orderId, $u['id']]);
    $order = $st->fetch();
    if (!$order) respond(['ok' => false, 'error' => 'Order not found.'], 404);
    if ($order['status'] !== 'Shipped') respond(['ok' => false, 'error' => 'Only shipped orders can go out for delivery.'], 409);
    $pdo->prepare("WITH upd AS (UPDATE orders SET status = 'Out for Delivery' WHERE id = ? RETURNING id)
                   INSERT INTO audit_logs (user_id, action) SELECT ?, 'Out for delivery: order #' || id FROM upd")
        ->execute([$orderId, $u['id']]);
    notify($pdo, (int)$order['org_user_id'], 'order', "Order #$orderId is out for delivery.");
    respond(['ok' => true, 'status' => 'Out for Delivery']);
  }

  /* distributor delivers a shipped order (with tracking number) */
  if ($action === 'deliver') {
    require_role('distributor');
    $orderId = (int)($d['order_id'] ?? 0);
    $tracking = trim($d['tracking_number'] ?? '');
    $st = $pdo->prepare('SELECT o.id, o.status, o.org_user_id FROM orders o
                         JOIN distributors dd ON dd.id = o.distributor_id
                         WHERE o.id = ? AND dd.user_id = ?');
    $st->execute([$orderId, $u['id']]);
    $order = $st->fetch();
    if (!$order) respond(['ok' => false, 'error' => 'Order not found.'], 404);
    if ($order['status'] !== 'Out for Delivery') respond(['ok' => false, 'error' => 'Order must be out for delivery first.'], 409);
    $audit = "Delivered order #$orderId" . ($tracking ? " (tracking $tracking)" : '');
    $pdo->prepare("WITH upd AS (UPDATE orders SET status = 'Delivered', tracking_number = ?, delivered_at = NOW() WHERE id = ? RETURNING id)
                   INSERT INTO audit_logs (user_id, action) SELECT ?, ? FROM upd")
        ->execute([$tracking ?: null, $orderId, $u['id'], $audit]);
    /* cash on delivery: money is collected when the parcel is handed over */
    $pdo->prepare("UPDATE transactions SET status = 'Completed' WHERE order_id = ? AND method = 'Cash' AND status = 'Pending'")
        ->execute([$orderId]);
    notify($pdo, (int)$order['org_user_id'], 'order', "Order #$orderId was delivered." . ($tracking ? " Tracking: $tracking." : ''));
    respond(['ok' => true, 'status' => 'Delivered']);
  }

  /* organization cancels an order that has not been accepted yet */
  if ($action === 'cancel') {
    require_role('org');
    $orderId = (int)($d['order_id'] ?? 0);
    $st = $pdo->prepare('SELECT o.id, o.status, d.user_id AS dist_user_id
                         FROM orders o JOIN distributors d ON d.id = o.distributor_id
                         WHERE o.id = ? AND o.org_user_id = ?');
    $st->execute([$orderId, $u['id']]);
    $order = $st->fetch();
    if (!$order) respond(['ok' => false, 'error' => 'Order not found.'], 404);
    if (!in_array($order['status'], ['Pending', 'Pending Payment'], true)) {
      respond(['ok' => false, 'error' => 'This order can no longer be cancelled.'], 409);
    }
    $pdo->beginTransaction();
    try {
      $st = $pdo->prepare('SELECT medicine_id, qty FROM order_items WHERE order_id = ?');
      $st->execute([$orderId]);
      foreach ($st->fetchAll() as $it) {
        $pdo->prepare('UPDATE medicines SET stock = stock + ? WHERE id = ?')->execute([(int)$it['qty'], (int)$it['medicine_id']]);
      }
      $pdo->prepare('UPDATE medicines SET low_stock_notified = 0 WHERE stock >= 250')->execute();
      $pdo->prepare("UPDATE orders SET status = 'Cancelled' WHERE id = ?")->execute([$orderId]);
      $pdo->prepare("UPDATE transactions SET status = 'Cancelled' WHERE order_id = ?")->execute([$orderId]);
      notify($pdo, (int)$order['dist_user_id'], 'order', "Order #$orderId was cancelled by the buyer. Reserved stock restored.");
      audit_log($pdo, $u['id'], "Cancelled order #$orderId");
      $pdo->commit();
      respond(['ok' => true, 'status' => 'Cancelled']);
    } catch (Exception $e) {
      $pdo->rollBack();
      respond(['ok' => false, 'error' => 'Could not cancel the order.'], 500);
    }
  }

  require_role('org');
  $items = $d['items'] ?? [];
  if (empty($items) || !is_array($items)) respond(['ok' => false, 'error' => 'Cart is empty.'], 422);

  $delivery = trim($d['delivery_address'] ?? '');
  if ($delivery === '') {
    /* dashboard quick-order: fall back to the buyer's saved profile address */
    $st = $pdo->prepare('SELECT address FROM users WHERE id = ?');
    $st->execute([$u['id']]);
    $delivery = trim((string)$st->fetchColumn());
  }
  if ($delivery === '') respond(['ok' => false, 'error' => 'Delivery address is required. Add one in your profile.'], 422);

  $phone = preg_replace('/\D/', '', (string)($d['delivery_phone'] ?? ''));
  if (!preg_match('/^\d{10}$/', $phone)) respond(['ok' => false, 'error' => 'Enter a valid 10-digit mobile number for delivery.'], 422);

  $lat = trim($d['latitude'] ?? '');
  $lng = trim($d['longitude'] ?? '');

  $method = in_array($d['method'] ?? '', ['Cash', 'Bank Transfer', 'UPI', 'Card', 'Razorpay'], true) ? $d['method'] : 'Bank Transfer';
  $reference = trim($d['reference'] ?? '');

  /* group items by distributor so each order has one distributor_id */
  $pdo->beginTransaction();
  try {
    $orderIds = [];
    $ordersByDist = [];
    foreach ($items as $it) {
      $medId = (int)($it['medicine_id'] ?? 0);
      $qty = (int)($it['qty'] ?? 0);
      if ($qty <= 0) throw new Exception('Invalid quantity.');
      $st = $pdo->prepare('SELECT m.id, m.name, m.price, m.stock, m.distributor_id, m.expiry, d.user_id AS dist_user_id
                           FROM medicines m JOIN distributors d ON d.id = m.distributor_id
                           WHERE m.id = ? AND m.deleted = 0 FOR UPDATE');
      $st->execute([$medId]);
      $med = $st->fetch();
      if (!$med) throw new Exception('Medicine not found.');
      if ($med['expiry'] < date('Y-m-d')) throw new Exception("{$med['name']} has expired and is no longer available.");
      if ($med['stock'] < $qty) throw new Exception("Insufficient stock for {$med['name']}.");

      $pdo->prepare('UPDATE medicines SET stock = stock - ? WHERE id = ?')->execute([$qty, $medId]);
      if ((int)$med['stock'] - $qty <= 0) {
        /* sold out: remove it from the store and tell the distributor */
        $pdo->prepare('UPDATE medicines SET deleted = 1 WHERE id = ?')->execute([$medId]);
        notify($pdo, (int)$med['dist_user_id'], 'low_stock', "{$med['name']} is now out of stock and was removed from the store.");
      }
      if (!isset($ordersByDist[$med['distributor_id']])) {
        $ordersByDist[$med['distributor_id']] = ['user_id' => (int)$med['dist_user_id'], 'items' => []];
      }
      $ordersByDist[$med['distributor_id']]['items'][] = [$med, $qty];
    }

    foreach ($ordersByDist as $distId => $group) {
      $list = $group['items'];
      $total = 0;
      foreach ($list as [$med, $qty]) $total += $med['price'] * $qty;
      if ($total < 500) throw new Exception('Minimum order is ₹500 per distributor. Please add more medicines from this distributor.');
      $status = $method === 'Razorpay' ? 'Pending Payment' : 'Pending';
      $pdo->prepare('INSERT INTO orders (org_user_id, distributor_id, delivery_address, delivery_phone, latitude, longitude, total, status) VALUES (?,?,?,?,?,?,?,?)')
          ->execute([$u['id'], $distId, $delivery, $phone, $lat ?: null, $lng ?: null, $total, $status]);
      $orderId = (int)$pdo->lastInsertId();
      foreach ($list as [$med, $qty]) {
        $pdo->prepare('INSERT INTO order_items (order_id, medicine_id, qty, price) VALUES (?,?,?,?)')
            ->execute([$orderId, $med['id'], $qty, $med['price']]);
      }
      $pdo->prepare('INSERT INTO transactions (order_id, amount, method, status, reference) VALUES (?,?,?,?,?)')
          ->execute([$orderId, $total, $method, 'Pending', $reference]);
      $names = implode(', ', array_map(fn($x) => "{$x[0]['name']} x{$x[1]}", $list));
      notify($pdo, $u['id'], 'order', "Order #$orderId placed: $names");
      notify($pdo, (int)$group['user_id'], 'order', "New order #$orderId: $names");
      audit_log($pdo, $u['id'], "Placed order #$orderId (₹$total)");
      $orderIds[] = $orderId;
    }
    $pdo->commit();
    respond(['ok' => true, 'order_ids' => $orderIds]);
  } catch (Exception $e) {
    $pdo->rollBack();
    respond(['ok' => false, 'error' => $e->getMessage()], 409);
  }
}

/* ---------- GET ---------- */
$cacheKey = 'orders:' . $u['role'] . ':' . $u['id'];
$cached = cache_get($cacheKey, 10);
if ($cached !== null) respond($cached);

$pdo = db();
$sql = 'SELECT o.*, u.name AS buyer, COALESCE(o.latitude, u.latitude) AS org_lat, COALESCE(o.longitude, u.longitude) AS org_lng,
               t.status AS payment_status, t.method, t.reference,
               du.name AS distributor_name, du.latitude AS dist_lat, du.longitude AS dist_lng,
               string_agg(m.name || \' x\' || oi.qty, \', \') AS items
        FROM orders o
        JOIN order_items oi ON oi.order_id = o.id
        JOIN medicines m ON m.id = oi.medicine_id
        JOIN users u ON u.id = o.org_user_id
        JOIN distributors d ON d.id = o.distributor_id
        JOIN users du ON du.id = d.user_id
        JOIN transactions t ON t.order_id = o.id';
$args = [];
if ($u['role'] === 'org') {
  $sql .= ' WHERE o.org_user_id = ?';
  $args[] = $u['id'];
} elseif ($u['role'] === 'distributor') {
  $st = $pdo->prepare('SELECT id FROM distributors WHERE user_id = ?');
  $st->execute([$u['id']]);
  $sql .= ' WHERE o.distributor_id = ?';
  $args[] = (int)$st->fetchColumn();
}
$sql .= ' GROUP BY o.id, u.name, u.latitude, u.longitude, t.status, t.method, t.reference, du.name, du.latitude, du.longitude ORDER BY o.id DESC';
$st = $pdo->prepare($sql);
$st->execute($args);
$out = ['ok' => true, 'orders' => $st->fetchAll()];
cache_put($cacheKey, $out);
respond($out);
