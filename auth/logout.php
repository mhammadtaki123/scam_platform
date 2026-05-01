<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
session_destroy();
header('Location: ' . APP_URL . '/auth/login.php');
exit;
