<?php
/**
 * login_process.php
 * =================
 * LOGIN FORM HANDLER
 *
 * This runs when the user submits the login form (login.php).
 * Steps:
 *   1. Get email + password from the form
 *   2. Find the user in the database by email
 *   3. Verify the password with password_verify()
 *   4. Create a session (remember the user)
 *   5. Redirect to the correct dashboard based on role
 */

session_start();                        // Start the session
require_once 'config/db.php';           // Connect to database

// ─── Only handle POST requests ────────────────────────────────────────────────
// If someone visits this URL directly (not via form), send them to login page
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit();
}

// ─── Step 1: Get Form Data ────────────────────────────────────────────────────
// trim() removes leading/trailing spaces from the input
$email    = trim($_POST['email']    ?? '');
$password = trim($_POST['password'] ?? '');

// Basic check — both fields must be filled
if (empty($email) || empty($password)) {
    header("Location: login.php?error=Please+fill+in+all+fields");
    exit();
}

// ─── Step 2: Find the User in the Database ────────────────────────────────────
// We use a PREPARED STATEMENT to safely query with user input
// This prevents SQL Injection attacks (hackers injecting malicious SQL)
$stmt = mysqli_prepare($conn, "SELECT id, name, email, password, role FROM users WHERE email = ?");
mysqli_stmt_bind_param($stmt, 's', $email); // 's' = string type
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Check if we found a user with that email
if (mysqli_num_rows($result) === 0) {
    header("Location: login.php?error=No+account+found+with+that+email");
    exit();
}

$user = mysqli_fetch_assoc($result); // Get the user's data as an array

// ─── Step 3: Verify the Password ──────────────────────────────────────────────
// password_verify() compares the plain text input with the hashed password in DB
// NEVER compare passwords as plain text — always use this function
if (!password_verify($password, $user['password'])) {
    header("Location: login.php?error=Incorrect+password");
    exit();
}

// ─── Step 4: Create Session Variables ────────────────────────────────────────
// Sessions are like a "memory" on the server that follows the user between pages
// These variables are now available on every page with session_start()
$_SESSION['user_id']   = $user['id'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['user_email']= $user['email'];
$_SESSION['role']      = $user['role'];

// For shopkeepers, also store which shop they manage
if ($user['role'] === 'shopkeeper') {
    $shopStmt = mysqli_prepare($conn, "SELECT id FROM shops WHERE owner_id = ? LIMIT 1");
    mysqli_stmt_bind_param($shopStmt, 'i', $user['id']);
    mysqli_stmt_execute($shopStmt);
    $shopResult = mysqli_stmt_get_result($shopStmt);
    if ($shopRow = mysqli_fetch_assoc($shopResult)) {
        $_SESSION['shop_id'] = $shopRow['id']; // Remember their shop ID
    }
}

mysqli_stmt_close($stmt);

// ─── Step 5: Redirect to the Correct Dashboard ───────────────────────────────
// Each role has a different dashboard
switch ($user['role']) {
    case 'admin':
        header("Location: admin/dashboard.php");
        break;
    case 'shopkeeper':
        header("Location: shopkeeper/dashboard.php");
        break;
    case 'customer':
    default:
        header("Location: customer/dashboard.php");
}
exit();
?>
