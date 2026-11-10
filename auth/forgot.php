<?php
/* Password reset: email OTP (Supabase Auth) -> short-lived reset token -> new password. */
require __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(['ok' => false, 'error' => 'Method not allowed.'], 405);

$d = body();
$action = $d['action'] ?? '';
$email = strtolower(trim($d['email'] ?? ''));

if (in_array($action, ['verify', 'reset'], true) && RESET_SECRET === '') {
  respond(['ok' => false, 'error' => 'Password reset is not configured.'], 503);
}

function supabase_auth(string $path, array $payload): array {
  $ch = curl_init(SUPABASE_URL . '/auth/v1/' . $path);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => [
      'Content-Type: application/json',
      'apikey: ' . SUPABASE_ANON_KEY,
      'Authorization: Bearer ' . SUPABASE_ANON_KEY,
    ],
    CURLOPT_TIMEOUT => 20,
  ]);
  $raw = curl_exec($ch);
  $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  return [$code, json_decode((string)$raw, true)];
}

function otp_file(string $email): string {
  return sys_get_temp_dir() . '/medshare_otp/' . md5($email) . '.json';
}

if ($action === 'request') {
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) respond(['ok' => false, 'error' => 'Enter a valid email address.'], 422);
  $st = db()->prepare('SELECT id FROM users WHERE lower(email) = ?');
  $st->execute([$email]);
  if (!$st->fetch()) {
    respond(['ok' => true, 'message' => 'If that email is registered, a 6-digit OTP has been sent.']);
  }

  if (SUPABASE_ANON_KEY === '') {
    if (APP_ENV === 'production') {
      respond(['ok' => false, 'error' => 'Password reset email is not configured. Contact the administrator.'], 503);
    }
    $otp = (string)random_int(100000, 999999);
    $f = otp_file($email);
    if (!is_dir(dirname($f))) @mkdir(dirname($f), 0755, true);
    file_put_contents($f, json_encode(['hash' => password_hash($otp, PASSWORD_DEFAULT), 'expires' => time() + 600, 'tries' => 0]), LOCK_EX);
    file_put_contents(sys_get_temp_dir() . '/medshare_otp.log', '[' . date('Y-m-d H:i:s') . "] $email OTP: $otp\n", FILE_APPEND | LOCK_EX);
    respond(['ok' => true, 'dev' => true, 'message' => 'Development mode: the OTP was written to ' . sys_get_temp_dir() . '/medshare_otp.log']);
  }

  [$code, $r] = supabase_auth('otp', ['email' => $email, 'create_user' => true]);
  if ($code === 429) respond(['ok' => false, 'error' => 'Too many requests. Wait a minute and try again.'], 429);
  if ($code >= 400) respond(['ok' => false, 'error' => 'Could not send the OTP right now. Try again.'], 502);
  respond(['ok' => true, 'message' => 'A 6-digit OTP has been sent to your email.']);
}

if ($action === 'verify') {
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) respond(['ok' => false, 'error' => 'Enter a valid email address.'], 422);
  $otp = preg_replace('/\D/', '', (string)($d['otp'] ?? ''));
  if (strlen($otp) !== 6) respond(['ok' => false, 'error' => 'Enter the 6-digit OTP.'], 422);

  if (SUPABASE_ANON_KEY === '') {
    if (APP_ENV === 'production') {
      respond(['ok' => false, 'error' => 'Password reset email is not configured. Contact the administrator.'], 503);
    }
    $f = otp_file($email);
    $rec = is_file($f) ? json_decode((string)file_get_contents($f), true) : null;
    if (!$rec || $rec['expires'] < time()) respond(['ok' => false, 'error' => 'OTP expired. Request a new one.'], 422);
    if (!password_verify($otp, $rec['hash'])) {
      $rec['tries'] = (int)($rec['tries'] ?? 0) + 1;
      if ($rec['tries'] >= 5) @unlink($f); else file_put_contents($f, json_encode($rec), LOCK_EX);
      respond(['ok' => false, 'error' => 'Invalid OTP.'], 422);
    }
    @unlink($f);
  } else {
    [$code, $r] = supabase_auth('verify', ['type' => 'email', 'email' => $email, 'token' => $otp]);
    if ($code >= 400) respond(['ok' => false, 'error' => 'Invalid or expired OTP.'], 422);
  }

  $exp = time() + 600;
  $sig = hash_hmac('sha256', $email . '|' . $exp, RESET_SECRET);
  respond(['ok' => true, 'reset_token' => base64_encode($email . '|' . $exp . '|' . $sig)]);
}

if ($action === 'reset') {
  $token = (string)($d['token'] ?? '');
  $pass = (string)($d['password'] ?? '');
  if (strlen($pass) < 6) respond(['ok' => false, 'error' => 'Password must be at least 6 characters.'], 422);
  $raw = base64_decode($token, true);
  $parts = $raw ? explode('|', $raw) : [];
  if (count($parts) !== 3) respond(['ok' => false, 'error' => 'Invalid reset request.'], 422);
  [$tokEmail, $exp, $sig] = $parts;
  if ((int)$exp < time() || !hash_equals(hash_hmac('sha256', $tokEmail . '|' . $exp, RESET_SECRET), $sig)) {
    respond(['ok' => false, 'error' => 'Reset expired. Start again.'], 422);
  }
  $st = db()->prepare('UPDATE users SET password = ? WHERE lower(email) = ? RETURNING id');
  $st->execute([password_hash($pass, PASSWORD_DEFAULT), $tokEmail]);
  $uid = (int)$st->fetchColumn();
  if (!$uid) respond(['ok' => false, 'error' => 'Account not found.'], 404);
  audit_log(db(), $uid, "Password reset for $tokEmail");
  respond(['ok' => true, 'message' => 'Password changed successfully.']);
}

respond(['ok' => false, 'error' => 'Invalid action.'], 422);
