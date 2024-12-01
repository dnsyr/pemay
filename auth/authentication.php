<?php
session_start();
include '../config/connection.php';

// Ambil data dari form login
$username = $_POST['username'];
$password = $_POST['password'];

$inputCaptcha = $_POST['captcha'] ?? '';
if ($inputCaptcha !== $_SESSION['captcha']) {
    die("CAPTCHA verification failed.");
}

// Proceed with your login logic
// echo "CAPTCHA verified. Proceeding with login.";

// Validasi input form
if (empty($username) || empty($password)) {
    die("Harap lengkapi semua data.");
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
        $_SESSION['message'] = "";

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
        die("Password salah.");
    }
} else {
    die("User  tidak ditemukan.");
}

// Bebaskan sumber daya statement
oci_free_statement($stmt);
oci_close($conn);
