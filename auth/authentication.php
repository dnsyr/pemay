<?php
session_start();
include '../config/connection.php';

// Ambil data dari form login
$username = $_POST['username'];
$password = $_POST['password'];

// Validasi input form
if (empty($username) || empty($password)) {
    die("Harap lengkapi semua data.");
}

// Query ke database untuk mencocokkan data login
$query = "SELECT * FROM pegawai WHERE username = :username";
$stmt = oci_parse($conn, $query);

// Bind variabel untuk menghindari SQL Injection
oci_bind_by_name($stmt, ":username", $username);

// Eksekusi query
oci_execute($stmt);

// Periksa hasil query
$user = oci_fetch_assoc($stmt);

if ($user) {
    // Debugging: Tampilkan data user yang ditemukan
    echo '<pre>'; print_r($user); echo '</pre>';

    // Verifikasi password (asumsi password terenkripsi)
    if (password_verify($password, $user['PASSWORD'])) { // PASSWORD = nama kolom di tabel Anda
        // Set session
        $_SESSION['user_logged_in'] = true;
        $_SESSION['username'] = $user['USERNAME'];  // Sesuaikan nama kolom
        $_SESSION['posisi'] = $user['POSISI'];      // Sesuaikan nama kolom
        $_SESSION['pegawai_id'] = $user['ID'];      // Sesuaikan nama kolom

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
?>