<?php
require __DIR__ . '/../config/db.php';
$u = current_user();
if (!$u) respond(['ok' => false, 'error' => 'Not logged in.'], 401);
respond(['ok' => true, 'user' => $u]);
