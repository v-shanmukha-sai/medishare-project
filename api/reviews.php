<?php
require __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  require_role('org');
  $pdo = db();
  $u = current_user();
  $d = body();
  $orderId = (int)($d['order_id'] ?? 0);
  $rating = (int)($d['rating'] ?? 0);
  if ($rating < 1 || $rating > 5) respond(['ok' => false, 'error' => 'Rating must be 1-5.'], 422);

  $st = $pdo->prepare("SELECT id FROM orders WHERE id = ? AND org_user_id = ? AND status = 'Delivered'");
  $st->execute([$orderId, $u['id']]);
  if (!$st->fetch()) respond(['ok' => false, 'error' => 'Order not found or not delivered.'], 403);

  $st = $pdo->prepare('SELECT id FROM reviews WHERE order_id = ? AND user_id = ?');
  $st->execute([$orderId, $u['id']]);
  if ($st->fetch()) respond(['ok' => false, 'error' => 'You already reviewed this order.'], 409);

  $st = $pdo->prepare('INSERT INTO reviews (order_id, user_id, rating, comment) VALUES (?,?,?,?)');
  $st->execute([$orderId, $u['id'], $rating, trim($d['comment'] ?? '')]);
  audit_log($pdo, $u['id'], "Reviewed order #$orderId ($rating stars)");
  respond(['ok' => true]);
}

/* GET: the signed-in organization's own reviews + delivered orders awaiting a review */
if (($_GET['mine'] ?? '') !== '') {
  require_role('org');
  $u = current_user();
  $cacheKey = 'reviews:mine:' . $u['id'];
  $cached = cache_get($cacheKey, 15);
  if ($cached !== null) respond($cached);
  $pdo = db();
  $pending = $pdo->prepare(
    "SELECT o.id, o.total, o.created_at, u2.name AS distributor,
            (SELECT string_agg(m.name || ' x' || oi.qty, ', ')
             FROM order_items oi JOIN medicines m ON m.id = oi.medicine_id
             WHERE oi.order_id = o.id) AS items
     FROM orders o
     JOIN distributors d ON d.id = o.distributor_id
     JOIN users u2 ON u2.id = d.user_id
     WHERE o.org_user_id = ? AND o.status = 'Delivered'
       AND NOT EXISTS (SELECT 1 FROM reviews r WHERE r.order_id = o.id AND r.user_id = ?)
     ORDER BY o.id DESC"
  );
  $pending->execute([$u['id'], $u['id']]);
  $mine = $pdo->prepare(
    'SELECT r.id, r.order_id, r.rating, r.comment, r.created_at, u2.name AS distributor
     FROM reviews r
     JOIN orders o ON o.id = r.order_id
     JOIN distributors d ON d.id = o.distributor_id
     JOIN users u2 ON u2.id = d.user_id
     WHERE r.user_id = ? ORDER BY r.id DESC'
  );
  $mine->execute([$u['id']]);
  $avg = $pdo->prepare('SELECT COALESCE(AVG(rating), 0) AS avg, COUNT(*) AS count FROM reviews WHERE user_id = ?');
  $avg->execute([$u['id']]);
  $out = ['ok' => true, 'pending' => $pending->fetchAll(), 'reviews' => $mine->fetchAll(), 'stats' => $avg->fetch()];
  cache_put($cacheKey, $out);
  respond($out);
}

/* GET: reviews for a distributor (public) */
$distId = (int)($_GET['distributor_id'] ?? 0);
if (!$distId) respond(['ok' => false, 'error' => 'distributor_id required.'], 422);
$cached = cache_get("reviews:dist:$distId", 20);
if ($cached !== null) respond($cached);
$pdo = db();
$st = $pdo->prepare('SELECT r.rating, r.comment, r.created_at, u.name AS reviewer
                     FROM reviews r
                     JOIN orders o ON o.id = r.order_id
                     JOIN users u ON u.id = r.user_id
                     WHERE o.distributor_id = ? ORDER BY r.id DESC LIMIT 50');
$st->execute([$distId]);
$avg = $pdo->prepare('SELECT COALESCE(AVG(r.rating), 0) AS avg, COUNT(*) AS count
                      FROM reviews r JOIN orders o ON o.id = r.order_id WHERE o.distributor_id = ?');
$avg->execute([$distId]);
$out = ['ok' => true, 'avg' => $avg->fetch(), 'reviews' => $st->fetchAll()];
cache_put("reviews:dist:$distId", $out);
respond($out);
