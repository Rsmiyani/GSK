<?php
/**
 * signup_process.php
 * ==================
 * SIGNUP FORM HANDLER
 *
 * This runs when a new user submits the signup form (signup.php).
 * Steps:
 *   1. Validate all form fields
 *   2. Check if email is already used
 *   3. Hash the password (never store plain text!)
 *   4. Insert the new user into the database
 *   5. Create a session and redirect to customer dashboard
 */

session_start();
require_once 'config/db.php';

// ─── Only handle POST requests ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: signup.php");
    exit();
}

// ─── Step 1: Get and Clean Form Data ─────────────────────────────────────────
$name             = trim($_POST['name']             ?? '');
$phone            = trim($_POST['phone']            ?? '');
$email            = trim($_POST['email']            ?? '');
$password         = trim($_POST['password']         ?? '');
$confirm_password = trim($_POST['confirm_password'] ?? '');

// ─── Step 2: Validate Fields ──────────────────────────────────────────────────
// Check all fields are filled
if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
    header("Location: signup.php?error=Please+fill+in+all+fields");
    exit();
}

// Check passwords match
if ($password !== $confirm_password) {
    header("Location: signup.php?error=Passwords+do+not+match");
    exit();
}

// Check password length (at least 6 characters)
if (strlen($password) < 6) {
    header("Location: signup.php?error=Password+must+be+at+least+6+characters");
    exit();
}

// Check valid email format using PHP's filter_var
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: signup.php?error=Invalid+email+address");
    exit();
}

// ─── Step 3: Check if Email Already Exists ───────────────────────────────────
// We use a prepared statement for safety
$checkStmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
mysqli_stmt_bind_param($checkStmt, 's', $email);
mysqli_stmt_execute($checkStmt);
mysqli_stmt_store_result($checkStmt);

if (mysqli_stmt_num_rows($checkStmt) > 0) {
    header("Location: signup.php?error=An+account+with+this+email+already+exists");
    exit();
}
mysqli_stmt_close($checkStmt);

// ─── Step 4: Hash the Password ───────────────────────────────────────────────
// password_hash() uses bcrypt to securely scramble the password
// Even if someone steals the database, they can't read the passwords
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

// ─── Step 5: Insert New User into Database ───────────────────────────────────
// New users are always 'customer' role by default
$role = 'customer';
$insertStmt = mysqli_prepare($conn, "INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)");
mysqli_stmt_bind_param($insertStmt, 'sssss', $name, $email, $phone, $hashedPassword, $role);

if (mysqli_stmt_execute($insertStmt)) {
    // Get the new user's ID (the ID MySQL auto-assigned)
    $newUserId = mysqli_insert_id($conn);

    // ─── Step 6: Create Session ──────────────────────────────────────────────
    // Log them in immediately after signup
    $_SESSION['user_id']    = $newUserId;
    $_SESSION['user_name']  = $name;
    $_SESSION['user_email'] = $email;
    $_SESSION['role']       = 'customer';

    // Redirect to their dashboard
    header("Location: customer/dashboard.php?welcome=1");
    exit();
} else {
    // Something went wrong with the database insert
    header("Location: signup.php?error=Registration+failed.+Please+try+again.");
    exit();
}

mysqli_stmt_close($insertStmt);
?>
