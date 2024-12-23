<?php
session_start();
require_once '../../config/database.php';
require_once '../../layout/header-tailwind.php';

$db = new Database();

// Mendapatkan ID pelanggan dari parameter URL
if (!isset($_GET['id'])) {
    die("ID pelanggan tidak ditemukan.");
}

$id = $_GET['id'];

// Ambil data pelanggan berdasarkan ID
$query = "SELECT * FROM PEMILIKHEWAN WHERE ID = :id";
$db->query($query);
$db->bind(':id', $id);
$data = $db->single();

if (!$data) {
    die("Data pelanggan tidak ditemukan.");
}

// Proses update data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = $_POST['nama'];
    $email = $_POST['email'];
    $nomorTelpon = $_POST['nomor_telpon'];

    $updateQuery = "UPDATE PEMILIKHEWAN SET 
                    NAMA = :nama, 
                    EMAIL = :email, 
                    NOMORTELPON = :nomor_telpon 
                    WHERE ID = :id";

    $db->query($updateQuery);
    $db->bind(':nama', $nama);
    $db->bind(':email', $email);
    $db->bind(':nomor_telpon', $nomorTelpon);
    $db->bind(':id', $id);

    try {
        $db->execute();
        echo "<script>alert('Data berhasil diperbarui!'); window.location.href = 'customer.php';</script>";
    } catch (PDOException $e) {
        echo "<script>alert('Gagal memperbarui data: " . $e->getMessage() . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Customer</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 50px auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
            color: #333;
        }
        label {
            font-size: 14px;
            color: #555;
            margin-bottom: 5px;
            display: block;
        }
        input[type="text"], input[type="email"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
            font-size: 16px;
        }
        button {
            width: 100%;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #555;
            text-decoration: none;
            font-size: 16px;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="container">
        <h2>Edit Data Customer</h2>
        <form method="POST" action="">
            <label for="nama">Nama</label>
            <input type="text" id="nama" name="nama" value="<?php echo htmlspecialchars($data['NAMA']); ?>" required>

            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($data['EMAIL']); ?>" required>

            <label for="nomor_telpon">Nomor Telepon</label>
            <input type="text" id="nomor_telpon" name="nomor_telpon" value="<?php echo htmlspecialchars($data['NOMORTELPON']); ?>" required>

            <button type="submit">Simpan Perubahan</button>
        </form>

        <a href="customer.php" class="back-link">Kembali ke Daftar Customer</a>
    </div>

</body>
</html>
