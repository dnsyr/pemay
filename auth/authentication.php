<?php
session_start();
include '../config/connection.php';

// Ambil data dari form login
$username = $_POST['username'];
$password = $_POST['password'];

$_SESSION['success_message'] = '';
$_SESSION['error_message'] = '';

$inputCaptcha = $_POST['captcha'] ?? '';
if ($inputCaptcha !== $_SESSION['captcha']) {
    $_SESSION['error_message'] = 'CAPTCHA Invalid!';
    // die("CAPTCHA verification failed.");
}

// Validasi input form
if (empty($username) || empty($password)) {
    $_SESSION['error_message'] = 'Input all data!';
    // die("Harap lengkapi semua data.");
}

// Query ke database untuk mencocokkan data login
$query = "SELECT * FROM pegawai WHERE username = :username";
$stmt = oci_parse($conn, $query);

// Bind parameters
oci_bind_by_name($stmt, ":username", $username);
oci_bind_by_name($stmt, ":posisi", $posisi);

// Execute the query
oci_execute($stmt);

// Fetch user data
$user = oci_fetch_assoc($stmt);
if ($user) {
    if ($user && password_verify($password, $user['PASSWORD'])) {
        // Set session variables for authenticated user
        $_SESSION['username'] = $username;
        $_SESSION['posisi'] = $user['POSISI'];
        $_SESSION['user_logged_in'] = true;
        $_SESSION['employee_id'] = $user['ID'];

        // Redirect ke dashboard sesuai posisi
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
        exit();
    } else {
        $_SESSION['error_message'] = "Password Invalid!";
        // die("Password Invalid!");
    }
} else {
    $_SESSION['error_message'] = "User Not Found!";
    // die("User Not Found!");
}

// Bebaskan sumber daya statement
oci_free_statement($stmt);
oci_close($conn);
