<?php
session_start();
require_once '../../config/database.php';
require_once '../../layout/header-tailwind.php';

$db = new Database();

// Proses penambahan data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = $_POST['nama'];
    $email = $_POST['email'];
    $nomorTelpon = $_POST['nomor_telpon'];

    // Cek apakah email sudah ada
    $queryCheckEmail = "SELECT COUNT(*) AS COUNT_EMAIL FROM PEMILIKHEWAN WHERE EMAIL = :email";
    $db->query($queryCheckEmail);
    $db->bind(':email', $email);
    $result = $db->single();

    if ($result['COUNT_EMAIL'] > 0) {
        // Jika email sudah ada
        echo "<script>alert('Email sudah terdaftar!');</script>";
    } else {
        // Lanjutkan dengan insert data
        $queryInsert = "INSERT INTO PEMILIKHEWAN (NAMA, EMAIL, NOMORTELPON) VALUES (:nama, :email, :nomor_telpon)";
        $db->query($queryInsert);
        $db->bind(':nama', $nama);
        $db->bind(':email', $email);
        $db->bind(':nomor_telpon', $nomorTelpon);

        try {
            $db->execute();
            echo "<script>alert('Data pelanggan berhasil ditambahkan!'); window.location.href = 'customer.php';</script>";
        } catch (PDOException $e) {
            echo "<script>alert('Gagal menambahkan data: " . $e->getMessage() . "');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Pelanggan</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap">
    <style>
        /* Styling global */
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f7f7f7;
            margin: 0;
            padding: 0;
        }

        /* Container form */
        .container {
            width: 100%;
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            color: #333;
            font-size: 24px;
            margin-bottom: 20px;
        }

        /* Styling form inputs */
        label {
            display: block;
            margin-bottom: 8px;
            font-size: 16px;
            color: #555;
        }

        input[type="text"], input[type="email"] {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
            font-size: 16px;
        }

        input[type="text"]:focus, input[type="email"]:focus {
            border-color: #007BFF;
            outline: none;
        }

        button[type="submit"] {
            width: 100%;
            padding: 12px;
            background-color:rgb(79, 179, 22);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 18px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        button[type="submit"]:hover {
            background-color: #218838;
        }

        /* Styling links */
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            text-decoration: none;
            color: #000000;
            font-size: 16px;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="container">
        <h2>Tambah Pelanggan Baru</h2>
        <form method="POST" action="">
            <label for="nama">Nama</label>
            <input type="text" id="nama" name="nama" required>

            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>

            <label for="nomor_telpon">Nomor Telepon</label>
            <input type="text" id="nomor_telpon" name="nomor_telpon" required>

            <button type="submit">Tambah Pelanggan</button>
        </form>

        <a href="customer.php" class="back-link">Kembali ke Halaman Pelanggan</a>
    </div>

</body>
</html>
