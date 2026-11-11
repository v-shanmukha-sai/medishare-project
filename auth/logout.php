<?php
require __DIR__ . '/../config/db.php';
ms_session_start();
$_SESSION = [];
forget_login();
setcookie(session_name(), '', ms_cookie_options(time() - 3600));
session_destroy();
header('Location: ../index.html');
exit;
