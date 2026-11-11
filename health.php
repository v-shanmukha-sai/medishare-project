<?php
/* Render health check: verifies the app can reach Supabase PostgreSQL. */
require __DIR__ . '/config/db.php';

$ok = false;
try {
  db()->query('SELECT 1');
  $ok = true;
} catch (Throwable $e) {
    error_log('MEDRED health check: ' . $e->getMessage());
}

http_response_code($ok ? 200 : 503);
header('Content-Type: application/json');
echo json_encode(['ok' => $ok]);
