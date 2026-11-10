<?php
/* Copy this file to config/secrets.php and fill in your own credentials.
   config/secrets.php is gitignored - never commit real keys.
   Environment variables (Render) take precedence over these values. */
return [
  'db_dsn' => 'pgsql:host=YOUR_POOLER_HOST;port=5432;dbname=postgres;sslmode=require',
  'db_user' => 'postgres.YOUR_PROJECT_REF',
  'db_pass' => 'YOUR_DB_PASSWORD',
  'razorpay_key_id' => 'rzp_test_XXXXXXXX',
  'razorpay_key_secret' => 'YOUR_RAZORPAY_KEY_SECRET',
  'supabase_url' => 'https://YOUR_PROJECT_REF.supabase.co',
  'supabase_anon_key' => 'YOUR_SUPABASE_ANON_KEY',
  'supabase_service_role_key' => 'YOUR_SUPABASE_SERVICE_ROLE_KEY',
  'reset_secret' => 'CHANGE_TO_A_LONG_RANDOM_STRING',
  'smtp_host' => '',
  'smtp_port' => '587',
  'smtp_secure' => 'tls',
  'smtp_user' => '',
  'smtp_pass' => '',
  'smtp_from' => 'noreply@example.com',
];
