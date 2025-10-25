<?php
require_once __DIR__ . '/../common/auth.php';

logout();

header('Location: /src/auth/login.php');
exit;
