<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['posisi'] != 'vet') {
    header("Location: ../../auth/restricted.php");
    exit();
}

include '../../config/connection.php';
include '../vet/header.php';

// Ambil data kategori obat untuk dropdown
$sql = "SELECT * FROM KategoriObat";
$stmt = oci_parse($conn, $sql);
oci_execute($stmt);

$kategoriObatList = [];
while ($row = oci_fetch_assoc($stmt)) {
    $kategoriObatList[] = $row;
}
oci_free_statement($stmt);

// Ambil data layanan medis untuk dropdown
$sqlLayanan = "
    SELECT lm.ID, h.Nama AS NamaHewan, h.Spesies, ph.Nama AS NamaPemilik
    FROM LayananMedis lm
    JOIN Hewan h ON lm.Hewan_ID = h.ID
    JOIN PemilikHewan ph ON h.PemilikHewan_ID = ph.ID
";
$stmtLayanan = oci_parse($conn, $sqlLayanan);
oci_execute($stmtLayanan);

$layananMedisList = [];
while ($row = oci_fetch_assoc($stmtLayanan)) {
    $layananMedisList[] = $row; // Menyimpan hasil query ke dalam array
}
oci_free_statement($stmtLayanan);

// Ambil data obat berdasarkan ID
$obat = [];
if (isset($_GET['id'])) {
    $obatId = intval($_GET['id']);
    $sql = "SELECT * FROM Obat WHERE ID = :id";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':id', $obatId);
    oci_execute($stmt);

    $obat = oci_fetch_assoc($stmt);
    oci_free_statement($stmt);

    if (!$obat) {
        echo '<div class="alert alert-danger">Obat tidak ditemukan.</div>';
        oci_close($conn);
        exit();
    }
}

// Proses update obat
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $dosis = $_POST['dosis'] ?? '';
    $nama = $_POST['nama'] ?? '';
    $frekuensi = $_POST['frekuensi'] ?? '';
    $instruksi = $_POST['instruksi'] ?? '';
    $harga = $_POST['harga'] ?? 0;
    $layananMedisId = $_POST['layanan-medis-id'] ?? '';
    $kategoriObatId = $_POST['kategori_obat_id'] ?? '';

    // Validasi data
    if (empty($dosis) || empty($nama) || empty($frekuensi) || empty($instruksi) || $harga <= 0 || empty($layananMedisId) || empty($kategoriObatId)) {
        $message = 'Semua field harus diisi dengan benar.';
    } else {
        // Update data obat
        $sql = "UPDATE Obat SET Dosis = :dosis, Nama = :nama, Frekuensi = :frekuensi, Instruksi = :instruksi, 
                Harga = :harga, LayananMedis_ID = :layanan_medis_id, KategoriObat_ID = :kategori_obat_id
                WHERE ID = :id";

        $stmt = oci_parse($conn, $sql);
        oci_bind_by_name($stmt, ':dosis', $dosis);
        oci_bind_by_name($stmt, ':nama', $nama);
        oci_bind_by_name($stmt, ':frekuensi', $frekuensi);
        oci_bind_by_name($stmt, ':instruksi', $instruksi);
        oci_bind_by_name($stmt, ':harga', $harga);
        oci_bind_by_name($stmt, ':layanan_medis_id', $layananMedisId);
        oci_bind_by_name($stmt, ':kategori_obat_id', $kategoriObatId);
        oci_bind_by_name($stmt, ':id', $obatId);

        if (oci_execute($stmt)) {
            $message = "Obat berhasil diperbarui.";
        } else {
            $error = oci_error($stmt);
            $message = "Gagal memperbarui obat: " . htmlentities($error['message']);
        }
        oci_free_statement($stmt);
    }
    oci_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Update Obat</title>
    <link rel="stylesheet" href="../../public/css/index.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <h1>Update Obat</h1>

        <?php if ($message): ?>
            <div class="alert alert-info"><?= htmlentities($message); ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="action" value="update">

            <div class="mb-3">
                <label for="dosis" class="form-label">Dosis</label>
                <input type="text" class="form-control" id="dosis" name="dosis" value="<?= htmlentities($obat['DOSIS'] ?? ''); ?>" required>
            </div>

            <div class="mb-3">
                <label for="nama" class="form-label">Nama Obat</label>
                <input type="text" class="form-control" id="nama" name="nama" value="<?= htmlentities($obat['NAMA'] ?? ''); ?>" required>
            </div>

            <div class="mb-3">
                <label for="frekuensi" class="form-label">Frekuensi</label>
                <input type="text" class="form-control" id="frekuensi" name="frekuensi" value="<?= htmlentities($obat['FREKUENSI'] ?? ''); ?>" required>
            </div>

            <div class="mb-3">
                <label for="instruksi" class="form-label">Instruksi</label>
                <textarea class="form-control" id="instruksi" name="instruksi" required><?= htmlentities($obat['INSTRUKSI'] ?? ''); ?></textarea>
            </div>

            <div class="mb-3">
                <label for="harga" class="form-label">Harga</label>
                <input type="number" class="form-control" id="harga" name="harga" value="<?= htmlentities($obat['HARGA'] ?? 0); ?>" required>
            </div>

            <div class="mb-3">
    <label for="layanan-medis-id" class="form-label">Layanan Medis</label>
    <select class="form-select" id="layanan-medis-id" name="layanan-medis-id" required>
        <?php foreach ($layananMedisList as $layanan): ?>
            <option value="<?= htmlentities($layanan['ID']); ?>" <?= (isset($obat['LAYANANMEDIS_ID']) && $obat['LAYANANMEDIS_ID'] == $layanan['ID']) ? 'selected' : ''; ?>>
                <?= htmlentities($layanan['ID']); ?> -  
                <?= htmlentities($layanan['NAMAHEWAN']); ?> 
                (<?= htmlentities($layanan['SPESIES']); ?>) - 
                <?= htmlentities($layanan['NAMAPEMILIK']); ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

<div class="mb-3">
    <label for="kategori_obat_id" class="form-label">Kategori Obat</label>
    <select class="form-select" id="kategori_obat_id" name="kategori_obat_id" required>
        <?php foreach ($kategoriObatList as $kategori): ?>
            <option value="<?= $kategori['ID']; ?>" <?= (isset($obat['KATEGORIOBAT_ID']) && $obat['KATEGORIOBAT_ID'] == $kategori['ID']) ? 'selected' : ''; ?>>
                <?= htmlentities($kategori['NAMA']); // Pastikan 'Nama' adalah kunci yang benar ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

            <button type="submit" class="btn btn-primary">Update Obat</button>
        </form>
    </div>
</body>

</html>
