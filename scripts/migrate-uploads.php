<?php
/* One-time migration: copy existing local uploads to Supabase Storage and
   create the five production buckets. Run from the project root:
       php scripts/migrate-uploads.php
   Requires SUPABASE_URL and SUPABASE_SERVICE_ROLE_KEY in the environment (or
   config/secrets.php). Local files are never deleted. */

if (PHP_SAPI !== 'cli') {
  http_response_code(403);
  exit("CLI only\n");
}

require dirname(__DIR__) . '/config/db.php';

if (!storage_enabled()) {
  fwrite(STDERR, "Set SUPABASE_URL and SUPABASE_SERVICE_ROLE_KEY first (env or config/secrets.php).\n");
  exit(1);
}

$root = dirname(__DIR__);
$buckets = [
  STORAGE_BUCKET_MEDICINES => true,
  STORAGE_BUCKET_DISTRIBUTOR_DOCS => false,
  STORAGE_BUCKET_NGO_DOCS => false,
  STORAGE_BUCKET_INVOICES => false,
  STORAGE_BUCKET_QR => false,
];

echo "== Buckets ==\n";
foreach ($buckets as $bucket => $public) {
  [$ok, $error] = storage_ensure_bucket($bucket, $public);
  echo ($ok ? '[ok]   ' : '[fail] ') . $bucket . ($ok ? '' : " - $error") . "\n";
  if (!$ok) exit(1);
}

/* Which private bucket owns each license file? */
$roleByFile = [];
$pdo = db();
foreach ([
  'distributor' => 'SELECT license_image FROM distributors WHERE license_image IS NOT NULL',
  'org' => 'SELECT license_image FROM ngos WHERE license_image IS NOT NULL',
] as $role => $sql) {
  foreach ($pdo->query($sql) as $row) {
    if (!empty($row['license_image'])) {
      $roleByFile[basename($row['license_image'])] = $role;
    }
  }
}

$uploaded = 0;
$failed = 0;

function migrate_file(string $bucket, string $object, string $file): void {
  global $uploaded, $failed;
  $mime = mime_content_type($file) ?: 'application/octet-stream';
  [$ok, $error] = storage_upload_object($bucket, $object, $file, $mime);
  if ($ok) {
    $uploaded++;
    echo "[ok]   $bucket/$object\n";
  } else {
    $failed++;
    echo "[fail] $bucket/$object - $error\n";
  }
}

echo "\n== Medicine images ==\n";
foreach (glob($root . '/uploads/medicines/*') ?: [] as $file) {
  $name = basename($file);
  if ($name[0] === '.') continue;
  migrate_file(STORAGE_BUCKET_MEDICINES, 'medicines/' . $name, $file);
}

echo "\n== License documents ==\n";
foreach (glob($root . '/uploads/licenses/*') ?: [] as $file) {
  $name = basename($file);
  if ($name[0] === '.') continue;
  $role = $roleByFile[$name] ?? 'distributor';
  if (!isset($roleByFile[$name])) {
    echo "[warn] $name is not referenced by the database; using distributor-documents\n";
  }
  $bucket = $role === 'org' ? STORAGE_BUCKET_NGO_DOCS : STORAGE_BUCKET_DISTRIBUTOR_DOCS;
  migrate_file($bucket, 'licenses/' . $name, $file);
}

echo "\n== Summary ==\n";
echo "uploaded: $uploaded\n";
echo "failed:   $failed\n";
echo "Local files were kept. Database paths were not changed.\n";
exit($failed === 0 ? 0 : 1);
