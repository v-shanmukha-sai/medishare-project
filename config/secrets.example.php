<?php
/* Copy this file to config/secrets.php and fill in your own credentials.
   config/secrets.php is gitignored - never commit real keys. */
return [
  'db_dsn' => 'pgsql:host=aws-0-ap-southeast-1.pooler.supabase.com;port=5432;dbname=postgres;sslmode=require',
  'db_user' => 'postgres.YOUR_PROJECT_REF',
  'db_pass' => 'YOUR_DB_PASSWORD',
  'razorpay_key_id' => 'rzp_test_XXXXXXXX',
  'razorpay_key_secret' => 'YOUR_RAZORPAY_KEY_SECRET',
  'supabase_url' => 'https://YOUR_PROJECT_REF.supabase.co',
  'supabase_anon_key' => 'YOUR_SUPABASE_ANON_KEY',
  'reset_secret' => 'CHANGE_TO_A_LONG_RANDOM_STRING',
];
