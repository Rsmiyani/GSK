<?php
/**
 * includes/auth_check.php
 * =======================
 * AUTHENTICATION GUARD
 *
 * Include this at the TOP of any protected page (customer, shopkeeper, admin).
 * It checks if the user is logged in and has the correct role.
 *
 * HOW TO USE:
 *   // To protect a customer-only page:
 *   $required_role = 'customer';
 *   require_once '../includes/auth_check.php';
 *
 *   // To protect a shopkeeper-only page:
 *   $required_role = 'shopkeeper';
 *   require_once '../includes/auth_check.php';
 *
 *   // To just check if logged in (any role):
 *   require_once '../includes/auth_check.php';
 */

// Start the session if it's not already started
// Sessions keep users "remembered" as they move between pages
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ─── Check 1: Is the user logged in? ─────────────────────────────────────────
// After login, we store the user's ID in $_SESSION['user_id']
// If it's not there, the user is NOT logged in
if (!isset($_SESSION['user_id'])) {
    // Send them to the login page
    header("Location: /GSK/login.php?error=Please+log+in+first");
    exit(); // ALWAYS exit after a redirect
}

// ─── Check 2: Does the user have the right role? ──────────────────────────────
// $required_role is set by the page that includes this file
if (isset($required_role) && $_SESSION['role'] !== $required_role) {
    // User is logged in but wrong role — redirect to their correct dashboard
    switch ($_SESSION['role']) {
        case 'admin':
            header("Location: /GSK/admin/dashboard.php");
            break;
        case 'shopkeeper':
            header("Location: /GSK/shopkeeper/dashboard.php");
            break;
        case 'customer':
        default:
            header("Location: /GSK/customer/dashboard.php");
    }
    exit();
}
?>
