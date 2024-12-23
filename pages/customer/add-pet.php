<?php
session_start();
require_once '../../config/database.php';
require_once '../../layout/header-tailwind.php';

$db = new Database();

// Ambil data pemilik hewan untuk dropdown
$queryPemilik = "SELECT ID, NAMA FROM PEMILIKHEWAN ORDER BY NAMA";
$db->query($queryPemilik);
$pemilikList = $db->resultSet();

// Proses penambahan data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Dapatkan ID terakhir
        $queryLastId = "SELECT MAX(ID) as MAX_ID FROM HEWAN";
        $db->query($queryLastId);
        $lastId = $db->single();
        $newId = isset($lastId['MAX_ID']) ? (int)$lastId['MAX_ID'] + 1 : 1;

        $nama = $_POST['nama'];
        $ras = $_POST['ras'];
        $spesies = $_POST['spesies'];
        $gender = $_POST['gender'];
        $berat = $_POST['berat'];
        $tanggalLahir = $_POST['tanggallahir'];
        $tinggi = $_POST['tinggi'];
        $lebar = $_POST['lebar'];
        $pemilikId = $_POST['id'];

        // Insert data hewan dengan ID dan ID Pemilik
        $queryInsert = "INSERT INTO HEWAN (ID, NAMA, RAS, SPESIES, GENDER, BERAT, TANGGALLAHIR, TINGGI, LEBAR, PEMILIKHEWAN_ID) 
                        VALUES (:id, :nama, :ras, :spesies, :gender, :berat, TO_DATE(:tanggallahir, 'YYYY-MM-DD'), :tinggi, :lebar, :pemilik_id)";
        
        $db->query($queryInsert);
        $db->bind(':id', $newId);
        $db->bind(':nama', $nama);
        $db->bind(':ras', $ras);
        $db->bind(':spesies', $spesies);
        $db->bind(':gender', $gender);
        $db->bind(':berat', $berat);
        $db->bind(':tanggallahir', $tanggalLahir);
        $db->bind(':tinggi', $tinggi);
        $db->bind(':lebar', $lebar);
        $db->bind(':pemilik_id', $pemilikId);

        $db->execute();
        echo "<script>alert('Data hewan berhasil ditambahkan!'); window.location.href = 'hewan.php';</script>";
    } catch (PDOException $e) {
        // Tampilkan pesan error yang lebih detail
        $errorMessage = "Error Code: " . $e->getCode() . " - ";
        $errorMessage .= "Error Message: " . $e->getMessage();
        echo "<script>alert('" . addslashes($errorMessage) . "');</script>";
    } catch (Exception $e) {
        echo "<script>alert('Terjadi kesalahan: " . addslashes($e->getMessage()) . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Hewan</title>
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
            box-sizing: border-box;
        }
        form button {
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 18px;
            margin-top: 20px;
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
        <h2>Tambah Data Hewan Baru</h2>
        <form method="POST" action="">
            <label for="id">Pemilik Hewan</label>
            <select id="id" name="id" required>
                <option value="">Pilih Pemilik Hewan</option>
                <?php foreach ($pemilikList as $pemilik): ?>
                    <option value="<?php echo $pemilik['ID']; ?>">
                        <?php echo htmlspecialchars($pemilik['NAMA']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="nama">Nama</label>
            <input type="text" id="nama" name="nama" required>

            <label for="ras">Ras</label>
            <input type="text" id="ras" name="ras" required>

            <label for="spesies">Spesies</label>
            <input type="text" id="spesies" name="spesies" required>

            <label for="gender">Gender</label>
            <select id="gender" name="gender" required>
                <option value="Jantan">Jantan</option>
                <option value="Betina">Betina</option>
            </select>

            <label for="berat">Berat (kg)</label>
            <input type="number" id="berat" name="berat" step="0.01" required>

            <label for="tanggallahir">Tanggal Lahir</label>
            <input type="date" id="tanggallahir" name="tanggallahir" required>

            <label for="tinggi">Tinggi (cm)</label>
            <input type="number" id="tinggi" name="tinggi" step="0.01" required>

            <label for="lebar">Lebar (cm)</label>
            <input type="number" id="lebar" name="lebar" step="0.01" required>

            <button type="submit">Tambah Hewan</button>
        </form>

        <a href="hewan.php" class="back-link">&larr; Kembali ke Daftar Hewan</a>
    </div>
</body>
</html>
