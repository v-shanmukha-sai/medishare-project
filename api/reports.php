<?php
require __DIR__ . '/../config/db.php';
current_user() ?? respond(['ok' => false, 'error' => 'Not logged in.'], 401);

$cached = cache_get('reports:stats', 20);
if ($cached !== null && ($_GET['format'] ?? '') !== 'csv') respond($cached);

$pdo = db();
$s = $pdo->query("SELECT
  (SELECT COUNT(*) FROM medicines) AS total,
  (SELECT COUNT(*) FROM medicines WHERE stock > 0 AND stock < 250) AS low,
  (SELECT COUNT(*) FROM medicines WHERE expiry BETWEEN CURRENT_DATE AND CURRENT_DATE + INTERVAL '3 months') AS expiring,
  (SELECT COALESCE(SUM(amount),0) FROM transactions WHERE status = 'Completed') AS revenue")->fetch();
$total = (int)$s['total']; $low = (int)$s['low']; $expiring = (int)$s['expiring']; $revenue = (float)$s['revenue'];

$byCat = [];
foreach ($pdo->query('SELECT category, SUM(stock) AS s FROM medicines GROUP BY category') as $row) {
  $byCat[$row['category']] = (int)$row['s'];
}
$risk = $pdo->query('SELECT m.name, u.name AS distributor, m.expiry
                     FROM medicines m JOIN distributors d ON d.id = m.distributor_id
                     JOIN users u ON u.id = d.user_id
                     WHERE m.expiry BETWEEN CURRENT_DATE AND (CURRENT_DATE + INTERVAL \'3 months\')
                     ORDER BY m.expiry')->fetchAll();

if (($_GET['format'] ?? '') === 'csv') {
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="medshare_reports_' . date('Y-m-d') . '.csv"');
  $out = fopen('php://output', 'w');
  fputcsv($out, ['Metric', 'Value']);
  fputcsv($out, ['Medicines Listed', $total]);
  fputcsv($out, ['Low Stock', $low]);
  fputcsv($out, ['Expiring <= 3 Months', $expiring]);
  fputcsv($out, ['Revenue (Completed)', $revenue]);
  fputcsv($out, []);
  fputcsv($out, ['Category', 'Stock']);
  foreach ($byCat as $cat => $stock) fputcsv($out, [$cat, $stock]);
  fputcsv($out, []);
  fputcsv($out, ['Medicine', 'Distributor', 'Expiry']);
  foreach ($risk as $m) fputcsv($out, [$m['name'], $m['distributor'], $m['expiry']]);
  fclose($out);
  exit;
}

$out = ['ok' => true, 'stats' => compact('total', 'low', 'expiring', 'revenue', 'byCat', 'risk')];
cache_put('reports:stats', $out);
respond($out);
