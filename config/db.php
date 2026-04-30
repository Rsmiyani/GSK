<?php
/**
 * config/db.php
 * =============
 * DATABASE CONNECTION FILE
 *
 * This file connects our PHP app to the MySQL database.
 * Include this at the top of any page that needs to read/write data:
 *   require_once '../config/db.php';   (from a subfolder like /customer/)
 *   require_once 'config/db.php';      (from the root folder)
 */

// ─── Database Settings ────────────────────────────────────────────────────────
// These match the default XAMPP MySQL settings
define('DB_HOST', 'localhost');   // MySQL server (always localhost for XAMPP)
define('DB_USER', 'root');        // Default XAMPP username
define('DB_PASS', '');            // Default XAMPP password is blank
define('DB_NAME', 'gsk_bakery'); // Our database name

// ─── Open the Connection ──────────────────────────────────────────────────────
// mysqli_connect() opens a connection to MySQL and returns a $conn object
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check if connection succeeded
if (!$conn) {
    // If it fails, stop the script and show a helpful message
    die("
        <div style='font-family:Arial;max-width:600px;margin:50px auto;padding:20px;background:#fff3f3;border:2px solid red;border-radius:8px;'>
            <h2>❌ Database Connection Failed</h2>
            <p><strong>Error:</strong> " . mysqli_connect_error() . "</p>
            <p>✅ Make sure XAMPP MySQL is running.</p>
            <p>✅ Run <a href='/GSK/setup.php'>/GSK/setup.php</a> first to create the database.</p>
        </div>
    ");
}

// Set character encoding to UTF-8 so the ₹ symbol and Hindi text show correctly
mysqli_set_charset($conn, "utf8");
?>
