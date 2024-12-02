<?php
session_start();
include '../config/connection.php';

// Ambil data dari form login
$username = $_POST['username'];
$password = $_POST['password'];
$selectedRole = $_POST['posisi'];

// Validasi input form
if (empty($username) || empty($password) || empty($selectedRole)) {
    die("Harap lengkapi semua data.");
}

// Query ke database untuk mencocokkan data login
$query = "SELECT * FROM pegawai WHERE username = :username";
$stmt = oci_parse($conn, $query);

// Bind parameters
oci_bind_by_name($stmt, ":username", $username);

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

// Check if the selected role matches the role from the database
if ($selectedRole === $user['POSISI']) {
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
} else {
    // Redirect to restricted.php if roles do not match
    header("Location: ../auth/restricted.php");
    exit();
}
} else {
die("Password salah.");
}
} else {
die("User  tidak ditemukan.");
}

// Bebaskan sumber daya statement
oci_free_statement($stmt);
oci_close($conn);
?>
