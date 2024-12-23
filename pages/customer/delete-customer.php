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

// Proses hapus data pelanggan
$deleteQuery = "DELETE FROM PEMILIKHEWAN WHERE ID = :id";
$db->query($deleteQuery);
$db->bind(':id', $id);

try {
    $db->execute();
    echo "<script>
            alert('Data pelanggan berhasil dihapus!');
            window.location.href = 'customer.php';
          </script>";
} catch (PDOException $e) {
    echo "<script>
            alert('Gagal menghapus data: " . $e->getMessage() . "');
            window.location.href = 'customer.php';
          </script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hapus Pelanggan</title>
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
            text-align: center;
        }
        h2 {
            color: #333;
            font-size: 24px;
            margin-bottom: 20px;
        }
        .btn {
            padding: 12px 30px;
            font-size: 16px;
            color: white;
            background-color: #f44336;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 20px;
            text-decoration: none;
        }
        .btn:hover {
            background-color: #d32f2f;
        }
        .btn-cancel {
            background-color: #4CAF50;
        }
        .btn-cancel:hover {
            background-color: #388e3c;
        }
        .back-link {
            display: block;
            margin-top: 30px;
            text-decoration: none;
            color: #555;
            font-size: 16px;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="container">
        <h2>Apakah Anda yakin ingin menghapus pelanggan ini?</h2>
        <a href="delete-customer.php?id=<?php echo $_GET['id']; ?>" class="btn">Hapus</a>
        <a href="customer.php" class="btn btn-cancel">Batal</a>
    </div>

</body>
</html>
