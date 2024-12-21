<?php
session_start();

$host = 'localhost';
$port = '1521';
$sid = 'xe';
$username = 'DVF';
$password = 'DVF';

$conn = oci_connect($username, $password, "//{$host}:{$port}/{$sid}");

if (!$conn) {
    $e = oci_error();
    die("Connection failed: " . $e['message']);
}

switch ($_SESSION['posisi']) {
    case 'owner':
        include '../owner/header.php';
        break;
    case 'vet':
        include '../vet/header.php';
        break;
    case 'staff':
        include '../staff/header.php';
        break;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['nomortelepon'];

    if (!empty($name) && !empty($email) && !empty($phone)) {
        $query = "INSERT INTO PEMILIKHEWAN (NAMA, EMAIL, NOMORTLEPON) VALUES (:name, :email, :nomortelepon)";
        $stmt = oci_parse($conn, $query);

        oci_bind_by_name($stmt, ':name', $name);
        oci_bind_by_name($stmt, ':email', $email);
        oci_bind_by_name($stmt, ':nomortelepon', $phone);

        if (oci_execute($stmt)) {
            echo "<script>alert('Pelanggan berhasil ditambahkan!'); window.location.href = 'index.php';</script>";
        } else {
            $e = oci_error($stmt);
            echo "<script>alert('Gagal menambahkan pelanggan: " . $e['message'] . "');</script>";
        }
    } else {
        echo "<script>alert('Semua field harus diisi!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tambah Pelanggan</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 20px;
    }
    .form-container {
      max-width: 400px;
      margin: 0 auto;
      padding: 20px;
      border: 1px solid #ccc;
      border-radius: 10px;
    }
    .form-container input {
      width: 100%;
      padding: 10px;
      margin: 10px 0;
      border: 1px solid #ccc;
      border-radius: 5px;
    }
    .form-container button {
      background-color: #4CAF50;
      color: #fff;
      border: none;
      padding: 10px 20px;
      cursor: pointer;
      border-radius: 5px;
      width: 100%;
    }
    .form-container button:hover {
      background-color: #45a049;
    }
  </style>
</head>
<body>
  <h2>Tambah Pelanggan Baru</h2>
  <div class="form-container">
    <form action="add-customer.php" method="POST">
      <label for="name">Nama:</label>
      <input type="text" id="name" name="name" placeholder="Nama Pelanggan" required>

      <label for="email">Email:</label>
      <input type="email" id="email" name="email" placeholder="Email Pelanggan" required>

      <label for="nomortelepon">No Telp:</label>
      <input type="text" id="nomortelepon" name="nomortelepon" placeholder="Nomor Telepon" required>

      <button type="submit">Tambah Pelanggan</button>
    </form>
  </div>
</body>
</html>
