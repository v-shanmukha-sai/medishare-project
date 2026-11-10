<?php
require __DIR__ . '/../config/db.php';
$u = current_user();
if (!$u) respond(['ok' => false, 'error' => 'Not logged in.'], 401);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $pdo = db();
  $d = body();
  if (($d['action'] ?? '') !== 'read_all') {
    respond(['ok' => false, 'error' => 'Invalid action.'], 422);
  }
  $st = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0');
  $st->execute([$u['id']]);
  respond(['ok' => true, 'marked' => $st->rowCount()]);
}

$cacheKey = 'notifications:' . $u['id'];
$cached = cache_get($cacheKey, 10);
if ($cached !== null) respond($cached);
$pdo = db();
$st = $pdo->prepare('SELECT type, text, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50');
$st->execute([$u['id']]);
$out = ['ok' => true, 'notifications' => $st->fetchAll()];
cache_put($cacheKey, $out);
respond($out);
