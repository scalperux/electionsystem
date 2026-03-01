<?php
require __DIR__ . '/config.php';
require __DIR__ . '/includes/auth.php';

// If logged in → go to dashboard based on role
if (!empty($_SESSION['user_id'])) {
    redirectLoggedInUser();
} else {
    // If not logged in → go to login
    header('Location: /online_voting/login.php');
    exit;
}