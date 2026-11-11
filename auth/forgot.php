<?php
/* Password reset: email OTP over SMTP -> short-lived reset token -> new password. */
require __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(['ok' => false, 'error' => 'Method not allowed.'], 405);

$d = body();
$action = $d['action'] ?? '';
$email = strtolower(trim($d['email'] ?? ''));

if (in_array($action, ['verify', 'reset'], true) && RESET_SECRET === '') {
  respond(['ok' => false, 'error' => 'Password reset is not configured.'], 503);
}

function otp_file(string $email): string {
  return sys_get_temp_dir() . '/medred_otp/' . md5($email) . '.json';
}

function otp_save(string $email, string $otp): void {
  $f = otp_file($email);
  if (!is_dir(dirname($f))) @mkdir(dirname($f), 0755, true);
  file_put_contents($f, json_encode(['hash' => password_hash($otp, PASSWORD_DEFAULT), 'expires' => time() + 600, 'tries' => 0]), LOCK_EX);
}

function otp_read(string $email): ?array {
  $f = otp_file($email);
  return is_file($f) ? json_decode((string)file_get_contents($f), true) : null;
}

function otp_mail(string $email, string $otp): bool {
  $body = "Hello,\n\nYour MEDRED password reset OTP is: $otp\n\n"
        . "This code expires in 10 minutes. If you did not request it, ignore this email.\n\n-- MEDRED Team";
  return smtp_send($email, 'MEDRED password reset OTP', $body);
}

if ($action === 'request') {
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) respond(['ok' => false, 'error' => 'Enter a valid email address.'], 422);
  $st = db()->prepare('SELECT id FROM users WHERE lower(email) = ?');
  $st->execute([$email]);
  if (!$st->fetch()) {
    respond(['ok' => true, 'message' => 'If that email is registered, a 6-digit OTP has been sent.']);
  }

  /* direct SMTP delivery is preferred: it does not depend on Supabase's
     magic-link template containing the {{ .Token }} placeholder. */
  if (SMTP_HOST !== '' && SMTP_USER !== '') {
    $otp = (string)random_int(100000, 999999);
    otp_save($email, $otp);
    if (otp_mail($email, $otp)) {
      respond(['ok' => true, 'message' => 'A 6-digit OTP has been sent to your email.']);
    }
    @unlink(otp_file($email));
    error_log('MEDRED: SMTP OTP delivery failed.');
  }

  if (APP_ENV === 'production') {
    respond(['ok' => false, 'error' => 'Password reset email is not configured. Contact the administrator.'], 503);
  }
  $otp = (string)random_int(100000, 999999);
  otp_save($email, $otp);
  file_put_contents(sys_get_temp_dir() . '/medred_otp.log', '[' . date('Y-m-d H:i:s') . "] $email OTP: $otp\n", FILE_APPEND | LOCK_EX);
  respond(['ok' => true, 'dev' => true, 'message' => 'Development mode: the OTP was written to ' . sys_get_temp_dir() . '/medred_otp.log']);
}

if ($action === 'verify') {
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) respond(['ok' => false, 'error' => 'Enter a valid email address.'], 422);
  $otp = preg_replace('/\D/', '', (string)($d['otp'] ?? ''));
  if (strlen($otp) !== 6) respond(['ok' => false, 'error' => 'Enter the 6-digit OTP.'], 422);

  $f = otp_file($email);
  $rec = otp_read($email);
  if ($rec) {
    if ($rec['expires'] < time()) {
      @unlink($f);
      respond(['ok' => false, 'error' => 'OTP expired. Request a new one.'], 422);
    }
    if (!password_verify($otp, $rec['hash'])) {
      $rec['tries'] = (int)($rec['tries'] ?? 0) + 1;
      if ($rec['tries'] >= 5) @unlink($f); else file_put_contents($f, json_encode($rec), LOCK_EX);
      respond(['ok' => false, 'error' => 'Invalid OTP.'], 422);
    }
    @unlink($f);
  } else {
    if (APP_ENV === 'production') {
      respond(['ok' => false, 'error' => 'Password reset email is not configured. Contact the administrator.'], 503);
    }
    respond(['ok' => false, 'error' => 'OTP expired. Request a new one.'], 422);
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
