<?php
/**
 * logout.php
 * ==========
 * LOGOUT HANDLER
 *
 * When any user clicks "Logout", they are sent here.
 * This file destroys their session (clears all login data)
 * and sends them back to the homepage.
 *
 * Link to this from any dashboard:
 *   <a href="/GSK/logout.php">Logout</a>
 */

// Start the session so we can access and destroy it
session_start();

// Unset all session variables (clear all stored login info)
$_SESSION = [];

// Destroy the session completely on the server
session_destroy();

// Redirect the user to the homepage
header("Location: /GSK/index.php?msg=logged_out");
exit();
?>
