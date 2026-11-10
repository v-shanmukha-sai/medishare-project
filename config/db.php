<?php
/* MediShare configuration + JSON helpers. Supabase Postgres + Storage.
   Values come from environment variables first (Render), then from the
   gitignored config/secrets.php for local XAMPP use. */

function env_value(string $key, string $default = ''): string {
  $v = getenv($key);
  return ($v === false || $v === '') ? $default : $v;
}

function db_dsn_from_url(string $url): ?array {
  $p = parse_url($url);
  if (!$p || empty($p['host'])) return null;
  $db = ltrim($p['path'] ?? '', '/');
  if ($db === '') $db = 'postgres';
  $ssl = 'require';
  if (!empty($p['query'])) {
    parse_str($p['query'], $q);
    if (!empty($q['sslmode'])) $ssl = $q['sslmode'];
  }
  return [
    'dsn' => sprintf('pgsql:host=%s;port=%d;dbname=%s;sslmode=%s', $p['host'], $p['port'] ?? 5432, $db, $ssl),
    'user' => urldecode($p['user'] ?? ''),
    'pass' => urldecode($p['pass'] ?? ''),
  ];
}

$secretsFile = __DIR__ . '/secrets.php';
$SECRETS = is_file($secretsFile) ? (require $secretsFile) : [];

$dbDsn = env_value('DB_DSN', $SECRETS['db_dsn'] ?? '');
$dbUser = env_value('DB_USER', $SECRETS['db_user'] ?? '');
$dbPass = env_value('DB_PASS', $SECRETS['db_pass'] ?? '');
$databaseUrl = env_value('DATABASE_URL', $SECRETS['database_url'] ?? '');
if ($databaseUrl !== '') {
  $url = db_dsn_from_url($databaseUrl);
  if ($url) {
    if ($dbDsn === '') $dbDsn = $url['dsn'];
    if ($dbUser === '') $dbUser = $url['user'];
    if ($dbPass === '') $dbPass = $url['pass'];
  }
}
if ($dbDsn !== '' && !str_contains($dbDsn, 'connect_timeout')) {
  $dbDsn = rtrim($dbDsn, ';') . ';connect_timeout=5';
}

define('DB_DSN', $dbDsn);
define('DB_USER', $dbUser);
define('DB_PASS', $dbPass);
define('RAZORPAY_KEY_ID', env_value('RAZORPAY_KEY_ID', $SECRETS['razorpay_key_id'] ?? ''));
define('RAZORPAY_KEY_SECRET', env_value('RAZORPAY_KEY_SECRET', $SECRETS['razorpay_key_secret'] ?? ''));
define('SUPABASE_URL', env_value('SUPABASE_URL', $SECRETS['supabase_url'] ?? ''));
define('SUPABASE_ANON_KEY', env_value('SUPABASE_ANON_KEY', $SECRETS['supabase_anon_key'] ?? ''));
define('SUPABASE_SERVICE_ROLE_KEY', env_value('SUPABASE_SERVICE_ROLE_KEY', $SECRETS['supabase_service_role_key'] ?? ''));
define('RESET_SECRET', env_value('RESET_SECRET', $SECRETS['reset_secret'] ?? ''));
define('APP_ENV', env_value('APP_ENV', getenv('PORT') !== false ? 'production' : 'development'));
define('MEDISHARE_EMAIL', filter_var(env_value('MEDISHARE_EMAIL', 'false'), FILTER_VALIDATE_BOOLEAN));
define('SMTP_HOST', env_value('SMTP_HOST', $SECRETS['smtp_host'] ?? ''));
define('SMTP_PORT', env_value('SMTP_PORT', $SECRETS['smtp_port'] ?? '587'));
define('SMTP_SECURE', env_value('SMTP_SECURE', $SECRETS['smtp_secure'] ?? 'tls'));
define('SMTP_USER', env_value('SMTP_USER', $SECRETS['smtp_user'] ?? ''));
define('SMTP_PASS', env_value('SMTP_PASS', $SECRETS['smtp_pass'] ?? ''));
define('SMTP_FROM', env_value('SMTP_FROM', $SECRETS['smtp_from'] ?? 'noreply@medishare.local'));

require __DIR__ . '/storage.php';

if (DB_DSN === '') {
  http_response_code(500);
  header('Content-Type: application/json');
  echo json_encode(['ok' => false, 'error' => 'Server not configured: set DB_DSN or DATABASE_URL.']);
  exit;
}

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

/* Minimal SMTP sender over cURL. Used only when MEDISHARE_EMAIL is true and
   SMTP_HOST is set; otherwise falls back to PHP mail(). */
function smtp_send(string $to, string $subject, string $body): bool {
  $secure = strtolower(SMTP_SECURE);
  $scheme = $secure === 'ssl' ? 'smtps' : 'smtp';
  $port = (int)(SMTP_PORT ?: ($secure === 'ssl' ? 465 : 587));
  $from = SMTP_FROM;
  $headers = [
    'To: <' . $to . '>',
    'From: MediShare <' . $from . '>',
    'Subject: ' . $subject,
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
  ];
  $message = implode("\r\n", $headers) . "\r\n\r\n" . $body;
  $fp = fopen('php://temp', 'r+');
  fwrite($fp, $message);
  rewind($fp);
  $ch = curl_init($scheme . '://' . SMTP_HOST . ':' . $port);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_MAIL_FROM => '<' . $from . '>',
    CURLOPT_MAIL_RCPT => ['<' . $to . '>'],
    CURLOPT_USERNAME => SMTP_USER,
    CURLOPT_PASSWORD => SMTP_PASS,
    CURLOPT_USE_SSL => $scheme === 'smtps' ? CURLUSESSL_SSL : CURLUSESSL_TRY,
    CURLOPT_UPLOAD => true,
    CURLOPT_INFILE => $fp,
    CURLOPT_INFILESIZE => strlen($message),
    CURLOPT_TIMEOUT => 15,
  ]);
  $ok = curl_exec($ch) !== false;
  if (!$ok) error_log('MediShare SMTP: ' . curl_error($ch));
  curl_close($ch);
  fclose($fp);
  return $ok;
}

/* Notify user: DB record + email + SMS stub.
   Email uses SMTP when configured, otherwise PHP mail().
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
    $headers = "From: " . SMTP_FROM . "\r\n";
    $sent = SMTP_HOST !== ''
      ? smtp_send($to, $subject, $body)
      : @mail($to, $subject, $body, $headers);
    if (!$sent) error_log("MediShare: could not send email to $to");
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
