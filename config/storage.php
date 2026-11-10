<?php
/* Supabase Storage integration (server-side only, service role key).
   Buckets: medicine-images (public), distributor-documents (private),
   ngo-documents (private), invoices (private), qr-codes (private).

   When SUPABASE_SERVICE_ROLE_KEY is not set (local XAMPP), uploads fall back
   to the existing local folders so development keeps working unchanged. */

const STORAGE_BUCKET_MEDICINES = 'medicine-images';
const STORAGE_BUCKET_DISTRIBUTOR_DOCS = 'distributor-documents';
const STORAGE_BUCKET_NGO_DOCS = 'ngo-documents';
const STORAGE_BUCKET_INVOICES = 'invoices';
const STORAGE_BUCKET_QR = 'qr-codes';

function storage_enabled(): bool {
  return SUPABASE_URL !== '' && SUPABASE_SERVICE_ROLE_KEY !== '';
}

function storage_encode_path(string $path): string {
  return implode('/', array_map('rawurlencode', explode('/', ltrim($path, '/'))));
}

function storage_request(string $method, string $path, ?string $body = null, string $contentType = 'application/json', array $extraHeaders = []): array {
  if (!storage_enabled()) return [0, [], 'Storage is not configured.'];
  $headers = [
    'apikey: ' . SUPABASE_SERVICE_ROLE_KEY,
    'Authorization: Bearer ' . SUPABASE_SERVICE_ROLE_KEY,
    'Content-Type: ' . $contentType,
  ];
  foreach ($extraHeaders as $h) $headers[] = $h;
  $ch = curl_init(rtrim(SUPABASE_URL, '/') . '/storage/v1' . $path);
  $opts = [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => $method,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_TIMEOUT => 30,
  ];
  if ($body !== null) $opts[CURLOPT_POSTFIELDS] = $body;
  curl_setopt_array($ch, $opts);
  $raw = curl_exec($ch);
  $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $err = $raw === false ? curl_error($ch) : '';
  curl_close($ch);
  return [$code, json_decode((string)$raw, true) ?: [], $err];
}

/* Create a bucket if it does not exist yet. Used by the migration script and
   as a one-time retry when an upload hits a missing bucket. */
function storage_ensure_bucket(string $bucket, bool $public): array {
  [$code] = storage_request('GET', '/bucket/' . rawurlencode($bucket));
  if ($code === 200) return [true, null];
  [$code, $json, $err] = storage_request('POST', '/bucket', json_encode([
    'id' => $bucket,
    'name' => $bucket,
    'public' => $public,
    'file_size_limit' => 5 * 1024 * 1024,
    'allowed_mime_types' => ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml', 'application/pdf'],
  ]));
  if ($code >= 200 && $code < 300) return [true, null];
  return [false, $json['message'] ?? $json['error'] ?? ($err ?: "HTTP $code")];
}

function storage_upload_object(string $bucket, string $object, string $tmp, string $mime): array {
  $bytes = @file_get_contents($tmp);
  if ($bytes === false) return [false, 'Could not read the uploaded file.'];
  [$code, $json, $err] = storage_request(
    'POST',
    '/object/' . rawurlencode($bucket) . '/' . storage_encode_path($object),
    $bytes,
    $mime,
    ['x-upsert: true']
  );
  if ($code >= 200 && $code < 300) return [true, null];
  return [false, $json['message'] ?? $json['error'] ?? ($err ?: "HTTP $code")];
}

function storage_delete_object(string $bucket, string $object): bool {
  [$code] = storage_request('DELETE', '/object/' . rawurlencode($bucket) . '/' . storage_encode_path($object));
  return $code >= 200 && $code < 300;
}

function storage_public_url(string $bucket, string $object): string {
  return rtrim(SUPABASE_URL, '/') . '/storage/v1/object/public/' . rawurlencode($bucket) . '/' . storage_encode_path($object);
}

function storage_signed_url(string $bucket, string $object, int $expires = 3600): ?string {
  [$code, $json, $err] = storage_request(
    'POST',
    '/object/sign/' . rawurlencode($bucket) . '/' . storage_encode_path($object),
    json_encode(['expiresIn' => $expires])
  );
  if ($code >= 200 && $code < 300 && !empty($json['signedURL'])) {
    $signed = $json['signedURL'];
    if (str_starts_with($signed, 'http')) return $signed;
    return rtrim(SUPABASE_URL, '/') . '/storage/v1' . $signed;
  }
  if ($err) error_log('MediShare storage sign: ' . $err);
  return null;
}

/* Map the path stored in PostgreSQL (uploads/medicines/x.jpg,
   uploads/licenses/x.jpg) to a Storage object path. */
function storage_object_path(string $rel): string {
  $rel = ltrim($rel, '/');
  if (str_starts_with($rel, 'uploads/medicines/')) {
    return 'medicines/' . substr($rel, strlen('uploads/medicines/'));
  }
  if (str_starts_with($rel, 'uploads/licenses/')) {
    return 'licenses/' . substr($rel, strlen('uploads/licenses/'));
  }
  return $rel;
}

function storage_local_put(string $rel, string $tmp): bool {
  $dest = dirname(__DIR__) . '/' . ltrim($rel, '/');
  if (!is_dir(dirname($dest))) @mkdir(dirname($dest), 0755, true);
  /* copy() keeps CLI tests working; HTTP uploads use move_uploaded_file(). */
  return is_uploaded_file($tmp) ? move_uploaded_file($tmp, $dest) : @copy($tmp, $dest);
}

function storage_store_file(string $bucket, string $object, string $rel, string $tmp, string $mime, bool $public): array {
  if (!storage_enabled()) {
    return storage_local_put($rel, $tmp)
      ? ['path' => $rel, 'error' => null]
      : ['path' => null, 'error' => 'File save failed.'];
  }
  [$ok, $error] = storage_upload_object($bucket, $object, $tmp, $mime);
  if (!$ok) {
    /* First deployment: create the bucket and retry once. */
    [$created] = storage_ensure_bucket($bucket, $public);
    if ($created) [$ok, $error] = storage_upload_object($bucket, $object, $tmp, $mime);
  }
  if (!$ok) error_log("MediShare storage upload ($bucket/$object): $error");
  return $ok
    ? ['path' => $rel, 'error' => null]
    : ['path' => null, 'error' => 'File upload failed. Try again.'];
}

function storage_store_medicine(string $tmp, string $ext, string $mime): array {
  $name = bin2hex(random_bytes(8)) . '.' . $ext;
  return storage_store_file(
    STORAGE_BUCKET_MEDICINES,
    'medicines/' . $name,
    'uploads/medicines/' . $name,
    $tmp,
    $mime,
    true
  );
}

function storage_store_document(string $tmp, string $ext, string $mime, string $role): array {
  $name = bin2hex(random_bytes(8)) . '.' . $ext;
  $bucket = $role === 'org' ? STORAGE_BUCKET_NGO_DOCS : STORAGE_BUCKET_DISTRIBUTOR_DOCS;
  return storage_store_file($bucket, 'licenses/' . $name, 'uploads/licenses/' . $name, $tmp, $mime, false);
}

function storage_delete_upload(?string $rel, ?string $role = null): void {
  if (!$rel || preg_match('#^https?://#', $rel)) return;
  if (storage_enabled()) {
    $bucket = null;
    if (str_starts_with($rel, 'uploads/medicines/')) $bucket = STORAGE_BUCKET_MEDICINES;
    elseif (str_starts_with($rel, 'uploads/licenses/')) {
      $bucket = $role === 'org' ? STORAGE_BUCKET_NGO_DOCS : STORAGE_BUCKET_DISTRIBUTOR_DOCS;
    }
    if ($bucket) {
      storage_delete_object($bucket, storage_object_path($rel));
      return;
    }
  }
  $local = dirname(__DIR__) . '/' . ltrim($rel, '/');
  if (is_file($local)) @unlink($local);
}

function storage_medicine_url(?string $rel): ?string {
  if (!$rel) return null;
  if (preg_match('#^https?://#', $rel)) return $rel;
  if (!storage_enabled()) return $rel;
  return storage_public_url(STORAGE_BUCKET_MEDICINES, storage_object_path($rel));
}

function storage_document_url(?string $rel, string $role): ?string {
  if (!$rel) return null;
  if (preg_match('#^https?://#', $rel)) return $rel;
  if (!storage_enabled()) return $rel;
  $bucket = $role === 'org' ? STORAGE_BUCKET_NGO_DOCS : STORAGE_BUCKET_DISTRIBUTOR_DOCS;
  return storage_signed_url($bucket, storage_object_path($rel)) ?: '';
}

/* Validate extension, size and real MIME type. Returns [ext, mime, error]. */
function upload_validate(array $f, array $exts, int $maxBytes): array {
  if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    return [null, null, 'Image upload failed.'];
  }
  if (($f['size'] ?? 0) > $maxBytes) {
    return [null, null, 'Image must be under ' . round($maxBytes / 1048576) . 'MB.'];
  }
  $ext = strtolower(pathinfo($f['name'] ?? '', PATHINFO_EXTENSION));
  if (!in_array($ext, $exts, true)) {
    return [null, null, 'Only JPG/PNG/WEBP allowed.'];
  }
  $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']) ?: '';
  $allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
  if (($allowed[$ext] ?? '') !== $mime) {
    return [null, null, 'File content does not match its extension.'];
  }
  return [$ext, $mime, null];
}
