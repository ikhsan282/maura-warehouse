<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

if (!empty($_SESSION['user'])) {
    header('Location: ' . APP_URL . '/pages/dashboard.php');
} else {
    header('Location: ' . APP_URL . '/auth/login.php');
}
exit;
