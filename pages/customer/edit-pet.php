<?php
session_start();
require_once '../../config/database.php';
require_once '../../layout/header-tailwind.php';

$db = new Database();

// Mendapatkan ID hewan dari parameter URL
$id = $_GET['id'];

// Ambil data hewan berdasarkan ID
$query = "SELECT * FROM HEWAN WHERE ID = :id";
$db->query($query);
$db->bind(':id', $id);
$data = $db->single();

if (!$data) {
    die("Data hewan tidak ditemukan.");
}

// Proses update data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = $_POST['nama'];
    $ras = $_POST['ras'];
    $spesies = $_POST['spesies'];
    $gender = $_POST['gender'];
    $berat = $_POST['berat'];
    $tanggalLahir = $_POST['tanggallahir'];
    $tinggi = $_POST['tinggi'];
    $lebar = $_POST['lebar'];

    $updateQuery = "UPDATE HEWAN SET 
                    NAMA = :nama, RAS = :ras, SPESIES = :spesies, 
                    GENDER = :gender, BERAT = :berat, 
                    TANGGALLAHIR = TO_DATE(:tanggallahir, 'YYYY-MM-DD'), 
                    TINGGI = :tinggi, LEBAR = :lebar 
                    WHERE ID = :id";

    $db->query($updateQuery);
    $db->bind(':nama', $nama);
    $db->bind(':ras', $ras);
    $db->bind(':spesies', $spesies);
    $db->bind(':gender', $gender);
    $db->bind(':berat', $berat);
    $db->bind(':tanggallahir', $tanggalLahir);
    $db->bind(':tinggi', $tinggi);
    $db->bind(':lebar', $lebar);
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
    <title>Edit Hewan</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f7f9fc;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 50px auto;
            background: #ffffff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #4CAF50;
        }
        form label {
            display: block;
            margin-top: 10px;
            font-weight: bold;
        }
        form input, form select, form button {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        form button {
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 18px;
        }
        form button:hover {
            background-color: #45a049;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            text-decoration: none;
            color: #4CAF50;
            font-weight: bold;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Edit Data Hewan</h2>
        <form method="POST" action="">
            <label for="nama">Nama</label>
            <input type="text" id="nama" name="nama" value="<?php echo htmlspecialchars($data['NAMA']); ?>" required>

            <label for="ras">Ras</label>
            <input type="text" id="ras" name="ras" value="<?php echo htmlspecialchars($data['RAS']); ?>" required>

            <label for="spesies">Spesies</label>
            <input type="text" id="spesies" name="spesies" value="<?php echo htmlspecialchars($data['SPESIES']); ?>" required>

            <label for="gender">Gender</label>
            <select id="gender" name="gender" required>
                <option value="Jantan" <?php echo $data['GENDER'] === 'Jantan' ? 'selected' : ''; ?>>Jantan</option>
                <option value="Betina" <?php echo $data['GENDER'] === 'Betina' ? 'selected' : ''; ?>>Betina</option>
            </select>

            <label for="berat">Berat (kg)</label>
            <input type="number" id="berat" name="berat" step="0.01" value="<?php echo htmlspecialchars($data['BERAT']); ?>" required>

            <label for="tanggallahir">Tanggal Lahir</label>
            <input type="date" id="tanggallahir" name="tanggallahir" value="<?php echo htmlspecialchars(date('Y-m-d', strtotime($data['TANGGALLAHIR']))); ?>" required>

            <label for="tinggi">Tinggi (cm)</label>
            <input type="number" id="tinggi" name="tinggi" step="0.01" value="<?php echo htmlspecialchars($data['TINGGI']); ?>" required>

            <label for="lebar">Lebar (cm)</label>
            <input type="number" id="lebar" name="lebar" step="0.01" value="<?php echo htmlspecialchars($data['LEBAR']); ?>" required>

            <button type="submit">Simpan Perubahan</button>
        </form>
        <a href="customer.php" class="back-link">← Kembali ke Daftar Hewan</a>
    </div>
</body>
</html>
