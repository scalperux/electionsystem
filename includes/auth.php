<?php
// auth.php – helper functions for authentication & roles
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Redirect logged-in users to their dashboard
 */
function redirectLoggedInUser(): void
{
    if (!empty($_SESSION['user_id']) && !empty($_SESSION['role'])) {
        if ($_SESSION['role'] === 'admin') {
            header('Location: /online_voting/admin/index.php');
            exit;
        }
        if ($_SESSION['role'] === 'voter') {
            header('Location: /online_voting/voter/index.php');
            exit;
        }
    }
}

/**
 * Make sure user is logged in, optionally check role
 */
function requireLogin(?string $role = null): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: /online_voting/login.php');
        exit;
    }

    if ($role !== null && ($_SESSION['role'] ?? null) !== $role) {
        // If role mismatch, send them out
        header('Location: /online_voting/login.php');
        exit;
    }
}