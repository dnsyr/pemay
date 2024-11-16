<?php
session_start();
include '../config/connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $username = $_POST['username'];
  $password = $_POST['password'];
  $posisi = $_POST['posisi'];

  // Prepare SQL to retrieve user info
  $sql = "SELECT id, password, posisi FROM Pegawai WHERE username = :username AND posisi = :posisi";
  $stid = oci_parse($conn, $sql);

  // Bind parameters
  oci_bind_by_name($stid, ":username", $username);
  oci_bind_by_name($stid, ":posisi", $posisi);

  // Execute the query
  oci_execute($stid);

  // Fetch user data
  $user = oci_fetch_assoc($stid);
  if ($user && password_verify($password, $user['PASSWORD'])) {
    // Set session variables for authenticated user
    $_SESSION['username'] = $username;
    $_SESSION['posisi'] = $user['POSISI'];
    $_SESSION['user_logged_in'] = true;
    $_SESSION['employee_id'] = $user['ID'];

    // Redirect based on role
    if ($user['POSISI'] == 'owner') {
      header("Location: ../pages/owner/dashboard.php");
    } elseif ($user['POSISI'] == 'vet') {
      header("Location: ../pages/vet/dashboard.php");
    } elseif ($user['POSISI'] == 'staff') {
      header("Location: ../pages/staff/dashboard.php");
    }

    exit();
  } else {
    echo "Invalid username, password, or role.";
  }

  // Free resources and close the connection
  oci_free_statement($stid);
  oci_close($conn);
}
