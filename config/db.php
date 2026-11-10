<?php
/* MedShare DB connection + JSON helper. Supabase Postgres (session pooler). */
/* Email is off by default: unconfigured mail() blocks each request for seconds.
   Set MEDISHARE_EMAIL to true after configuring SMTP in php.ini. */
const MEDISHARE_EMAIL = false;
/* Credentials live in config/secrets.php (gitignored). Copy
   config/secrets.example.php there on a fresh clone. */
$secretsFile = __DIR__ . '/secrets.php';
if (!is_file($secretsFile)) {
  http_response_code(500);
  header('Content-Type: application/json');
  echo json_encode(['ok' => false, 'error' => 'Server not configured: copy config/secrets.example.php to config/secrets.php and fill in the credentials.']);
  exit;
}
$SECRETS = require $secretsFile;
define('DB_DSN', $SECRETS['db_dsn'] ?? '');
define('DB_USER', $SECRETS['db_user'] ?? '');
define('DB_PASS', $SECRETS['db_pass'] ?? '');
define('RAZORPAY_KEY_ID', $SECRETS['razorpay_key_id'] ?? '');
define('RAZORPAY_KEY_SECRET', $SECRETS['razorpay_key_secret'] ?? '');
define('SUPABASE_URL', $SECRETS['supabase_url'] ?? '');
define('SUPABASE_ANON_KEY', $SECRETS['supabase_anon_key'] ?? '');
define('RESET_SECRET', $SECRETS['reset_secret'] ?? '');

function db(): PDO {
  static $pdo = null;
  if ($pdo === null) {
    try {
      $pdo = new PDO(
        DB_DSN,
        DB_USER,
        DB_PASS,
        [
          PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
          PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
          /* ponytail: no persistent connections - the Supabase pooler drops idle
             sessions and a reused dead handle causes fatal 500s. */
        ]
      );
    } catch (PDOException $e) {
      respond(['ok' => false, 'error' => 'Database unavailable. Check the Supabase connection and try again.'], 503);
    }
  }
  return $pdo;
}

function respond(array $data, int $code = 200): void {
  http_response_code($code);
  header('Content-Type: application/json');
  echo json_encode($data);
  exit;
}

function body(): array {
  return json_decode(file_get_contents('php://input'), true) ?? [];
}

/* Tiny file cache for GET endpoints: a Supabase round trip costs 0.5-3s, a
   local JSON file costs ~1ms. TTLs are short and every POST clears it, so the
   worst case is a few seconds of staleness for other users. */
function cache_file(string $key): string {
  [$ns] = explode(':', $key, 2);
  return sys_get_temp_dir() . '/medshare_cache/' . preg_replace('/[^a-z0-9_]/i', '_', $ns) . '/' . md5($key) . '.json';
}
function cache_get(string $key, int $ttl) {
  $f = cache_file($key);
  if (is_file($f) && time() - filemtime($f) < $ttl) {
    $d = json_decode((string)file_get_contents($f), true);
    if (is_array($d)) return $d;
  }
  return null;
}
function cache_put(string $key, array $data): void {
  $f = cache_file($key);
  if (!is_dir(dirname($f))) @mkdir(dirname($f), 0755, true);
  @file_put_contents($f, json_encode($data), LOCK_EX);
}
function cache_clear(): void {
  foreach (glob(sys_get_temp_dir() . '/medshare_cache/*/*.json') ?: [] as $f) @unlink($f);
}

function audit_log(PDO $pdo, ?int $userId, string $action): void {
  $st = $pdo->prepare('INSERT INTO audit_logs (user_id, action) VALUES (?, ?)');
  $st->execute([$userId ?? 0, $action]);
}

/* Notify user: DB record + email + SMS stub.
   Email uses PHP mail() (needs SMTP configured in php.ini to actually deliver).
   SMS is a stub writing to logs/sms.log — add a gateway (Twilio/etc.) with real
   credentials when you want live SMS. */
function notify(PDO $pdo, int $userId, string $type, string $text): void {
  /* one round trip: insert the notification and fetch the recipient */
  $st = $pdo->prepare('WITH ins AS (
                         INSERT INTO notifications (user_id, type, text) VALUES (?, ?, ?) RETURNING user_id
                       )
                       SELECT u.name, u.email, u.phone FROM users u JOIN ins i ON i.user_id = u.id');
  $st->execute([$userId, $type, $text]);
  $user = $st->fetch();
  if (!$user) return;

  if (MEDISHARE_EMAIL) {
    $to = $user['email'];
    $subject = 'MediShare - ' . ucfirst($type);
    $body = "Hello {$user['name']},\n\n$text\n\n-- MediShare Team";
    $headers = "From: noreply@medshare.com\r\n";
    @mail($to, $subject, $body, $headers);
  }

  if ($user['phone']) {
    $smsLog = __DIR__ . '/../logs/sms.log';
    $dir = dirname($smsLog);
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    file_put_contents($smsLog, "[" . date('Y-m-d H:i:s') . "] SMS to {$user['phone']}: $text\n", FILE_APPEND);
  }
}

function current_user(): ?array {
  static $loaded = false, $user = null;
  if (!$loaded) {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $user = $_SESSION['user'] ?? null;
    /* ponytail: release the session file lock - API endpoints only read the
       session, and holding the lock serialized every parallel request. */
    session_write_close();
    $loaded = true;
  }
  return $user;
}

function require_role(string $role): array {
  $u = current_user();
  if (!$u) respond(['ok' => false, 'error' => 'Not logged in.'], 401);
  if ($u['role'] !== $role) respond(['ok' => false, 'error' => 'Forbidden.'], 403);
  return $u;
}

/* writes invalidate every cached GET response */
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') cache_clear();
