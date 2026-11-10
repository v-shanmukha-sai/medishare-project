<?php
require __DIR__ . '/../config/db.php';
require_role('admin');
$cached = cache_get('audit:logs', 15);
if ($cached !== null) respond($cached);
$st = db()->prepare('SELECT a.action, a.created_at, COALESCE(u.name, \'system\') AS user
                     FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id
                     ORDER BY a.id DESC LIMIT 50');
$st->execute();
$out = ['ok' => true, 'logs' => $st->fetchAll()];
cache_put('audit:logs', $out);
respond($out);
