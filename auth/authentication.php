<?php
session_start();
include '../config/connection.php';

// Ambil data dari form login
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$posisi = $_POST['posisi'] ?? '';
$inputCaptcha = $_POST['captcha'] ?? '';

// Reset messages
$_SESSION['success_message'] = "";
$_SESSION['error_message'] = "";

// CAPTCHA Validation
if ($inputCaptcha !== $_SESSION['captcha']) {
    $_SESSION['error_message'] = "Invalid CAPTCHA!";
    $captchaText = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890'), 0, 6);
    $_SESSION['captcha'] = $captchaText;  // Regenerate CAPTCHA after failure
    header("Location: login.php");
    exit();
}

// Query to check the username
$query = "SELECT * FROM pegawai WHERE username = :username AND posisi = :posisi";
$stmt = oci_parse($conn, $query);

// Bind parameters
oci_bind_by_name($stmt, ":username", $username);
oci_bind_by_name($stmt, ":posisi", $posisi);

// Execute the query
oci_execute($stmt);

// Fetch user data
$user = oci_fetch_assoc($stmt);
if ($user) {
    if (password_verify($password, $user['PASSWORD'])) {
        // Set session variables for authenticated user
        $_SESSION['username'] = $username;
        $_SESSION['posisi'] = $user['POSISI'];
        $_SESSION['user_logged_in'] = true;
        $_SESSION['employee_id'] = $user['ID'];

        // Redirect to the appropriate dashboard based on the role
        switch ($user['POSISI']) {
            case 'owner':
                header("Location: ../pages/owner/dashboard.php");
                break;
            case 'vet':
                header("Location: ../pages/vet/dashboard.php");
                break;
            case 'staff':
                header("Location: ../pages/staff/dashboard.php");
                break;
            default:
                die("Role tidak dikenali.");
        }

        $_SESSION['success_message'] = "Login Successfully!";
    } else {
        $_SESSION['error_message'] = "Invalid Credentials!";
        $captchaText = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890'), 0, 6);
        $_SESSION['captcha'] = $captchaText;  // Regenerate CAPTCHA after wrong password
        header("Location: login.php");
    }
} else {
    $_SESSION['error_message'] = "User Not Found!";
    $captchaText = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890'), 0, 6);
    $_SESSION['captcha'] = $captchaText;  // Regenerate CAPTCHA if user not found
    header("Location: login.php");
}

unset($_SESSION['captcha']); // Optionally unset CAPTCHA after login attempt
// Free statement resources
oci_free_statement($stmt);
oci_close($conn);
