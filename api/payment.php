<?php
/* MedShare payments: Razorpay online payments + manual confirmation (cash/bank). */
require __DIR__ . '/../config/db.php';
$u = current_user();
if (!$u) respond(['ok' => false, 'error' => 'Not logged in.'], 401);

$d = body();
$action = $d['action'] ?? 'confirm';
$pdo = db();

/* minimal Razorpay REST client (Basic auth, JSON) */
function rzp_request(string $method, string $path, array $data = []): array {
  $ch = curl_init('https://api.razorpay.com/v1/' . $path);
  $opts = [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_USERPWD => RAZORPAY_KEY_ID . ':' . RAZORPAY_KEY_SECRET,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT => 20,
  ];
  if ($method === 'POST') { $opts[CURLOPT_POST] = true; $opts[CURLOPT_POSTFIELDS] = json_encode($data); }
  curl_setopt_array($ch, $opts);
  $res = curl_exec($ch);
  $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  if ($res === false) return ['ok' => false, 'error' => 'Payment gateway unreachable. Try again.'];
  $json = json_decode($res, true);
  if ($code >= 400) return ['ok' => false, 'error' => $json['error']['description'] ?? 'Payment gateway error.'];
  return ['ok' => true, 'data' => $json];
}

/* buyer starts an online payment for one or more unpaid orders */
if ($action === 'create') {
  require_role('org');
  $ids = array_values(array_unique(array_filter(array_map('intval', (array)($d['order_ids'] ?? [])))));
  if (!$ids) respond(['ok' => false, 'error' => 'order_ids required.'], 422);

  $in = implode(',', array_fill(0, count($ids), '?'));
  $st = $pdo->prepare("SELECT o.id, o.total, t.status AS txn_status, t.method
                       FROM orders o JOIN transactions t ON t.order_id = o.id
                       WHERE o.id IN ($in) AND o.org_user_id = ?");
  $st->execute([...$ids, $u['id']]);
  $rows = $st->fetchAll();
  if (count($rows) !== count($ids)) respond(['ok' => false, 'error' => 'Order not found.'], 404);

  $total = 0;
  foreach ($rows as $r) {
    if ($r['txn_status'] !== 'Pending') respond(['ok' => false, 'error' => "Order #{$r['id']} is already paid."], 409);
    if ($r['method'] !== 'Razorpay') respond(['ok' => false, 'error' => "Order #{$r['id']} is a cash-on-delivery order."], 409);
    $total += (float)$r['total'];
  }

  $rzp = rzp_request('POST', 'orders', [
    'amount' => (int)round($total * 100),
    'currency' => 'INR',
    'receipt' => 'ms_' . $ids[0] . '_' . time(),
    'notes' => ['order_ids' => implode(',', $ids)],
  ]);
  if (!$rzp['ok']) respond(['ok' => false, 'error' => $rzp['error']], 502);

  $rzpOrderId = $rzp['data']['id'];
  $pdo->prepare("UPDATE transactions SET reference = ? WHERE order_id IN ($in) AND status = 'Pending'")
      ->execute([$rzpOrderId, ...$ids]);
  respond([
    'ok' => true,
    'key_id' => RAZORPAY_KEY_ID,
    'razorpay_order_id' => $rzpOrderId,
    'amount' => (int)round($total * 100),
    'total' => $total,
  ]);
}

/* buyer returns from Razorpay Checkout: verify the signature server-side */
if ($action === 'verify') {
  require_role('org');
  $rzpOrderId = trim($d['razorpay_order_id'] ?? '');
  $paymentId = trim($d['razorpay_payment_id'] ?? '');
  $signature = trim($d['razorpay_signature'] ?? '');
  if ($rzpOrderId === '' || $paymentId === '' || $signature === '') {
    respond(['ok' => false, 'error' => 'Payment details missing.'], 422);
  }

  $expected = hash_hmac('sha256', $rzpOrderId . '|' . $paymentId, RAZORPAY_KEY_SECRET);
  if (!hash_equals($expected, $signature)) respond(['ok' => false, 'error' => 'Payment verification failed.'], 400);

  $st = $pdo->prepare("SELECT t.order_id FROM transactions t JOIN orders o ON o.id = t.order_id
                       WHERE t.reference = ? AND o.org_user_id = ?");
  $st->execute([$rzpOrderId, $u['id']]);
  $orderIds = $st->fetchAll(PDO::FETCH_COLUMN);
  if (!$orderIds) respond(['ok' => false, 'error' => 'Order not found.'], 404);

  $in = implode(',', array_fill(0, count($orderIds), '?'));
  $pdo->prepare("UPDATE transactions SET status = 'Completed', reference = ? WHERE order_id IN ($in)")
      ->execute([$paymentId, ...$orderIds]);
  /* the order is confirmed only once the online payment is verified */
  $pdo->prepare("UPDATE orders SET status = 'Pending' WHERE id IN ($in) AND status = 'Pending Payment'")
      ->execute($orderIds);
  foreach ($orderIds as $id) {
    audit_log($pdo, $u['id'], "Paid online for order #$id (Razorpay $paymentId)");
    $st = $pdo->prepare('SELECT d.user_id FROM orders o JOIN distributors d ON d.id = o.distributor_id WHERE o.id = ?');
    $st->execute([$id]);
    $distUser = (int)$st->fetchColumn();
    if ($distUser) notify($pdo, $distUser, 'order', "Payment received for order #$id. The order is now confirmed.");
  }
  respond(['ok' => true, 'order_ids' => $orderIds, 'status' => 'Completed']);
}

/* distributor manually confirms cash/bank payments */
require_role('distributor');
$orderId = (int)($d['order_id'] ?? 0);
$status = in_array($d['status'] ?? '', ['Completed', 'Failed'], true) ? $d['status'] : '';
if (!$orderId || !$status) respond(['ok' => false, 'error' => 'order_id and status (Completed/Failed) required.'], 422);

$st = $pdo->prepare('SELECT o.id, o.status, o.org_user_id, t.status AS txn_status, t.amount, t.method
                     FROM orders o
                     JOIN distributors dd ON dd.id = o.distributor_id
                     JOIN transactions t ON t.order_id = o.id
                     WHERE o.id = ? AND dd.user_id = ?');
$st->execute([$orderId, $u['id']]);
$order = $st->fetch();
if (!$order) respond(['ok' => false, 'error' => 'Order not found.'], 404);
if ($order['method'] === 'Razorpay') respond(['ok' => false, 'error' => 'Online payments are verified automatically.'], 409);
if ($order['txn_status'] !== 'Pending') respond(['ok' => false, 'error' => "Payment already {$order['txn_status']}."], 409);

$pdo->beginTransaction();
try {
  $pdo->prepare('UPDATE transactions SET status = ? WHERE order_id = ?')->execute([$status, $orderId]);
  notify($pdo, (int)$order['org_user_id'], 'order',
    $status === 'Completed'
      ? "Payment confirmed for Order #$orderId ({$order['method']} {$order['amount']})."
      : "Payment failed for Order #$orderId.");
  audit_log($pdo, $u['id'], "Payment {$status} for order #$orderId");
  $pdo->commit();
  respond(['ok' => true, 'order_id' => $orderId, 'status' => $status]);
} catch (Exception $e) {
  $pdo->rollBack();
  respond(['ok' => false, 'error' => 'Could not update payment.'], 500);
}
