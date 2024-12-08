<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['posisi'] != 'vet') {
    header("Location: ../../auth/restricted.php");
    exit();
}

include '../../config/connection.php';
include '../vet/header.php';

$pegawaiId = intval($_SESSION['employee_id']); // Ambil ID pegawai dari session

// Ambil data layanan medis untuk ditampilkan di dropdown
$sql = "
    SELECT lm.ID, h.Nama AS NamaHewan, h.Spesies, ph.Nama AS NamaPemilik
    FROM LayananMedis lm
    JOIN Hewan h ON lm.Hewan_ID = h.ID
    JOIN PemilikHewan ph ON h.PemilikHewan_ID = ph.ID
";
$stmt = oci_parse($conn, $sql);
oci_execute($stmt);

$layananMedisList = [];
while ($row = oci_fetch_assoc($stmt)) {
    // $row['ID'] = $row['ID'];
    $layananMedisList[] = $row;
}
oci_free_statement($stmt);

// Check if $layananMedisList is empty
if (empty($layananMedisList)) {
    echo '<div class="alert alert-warning">Tidak ada layanan medis yang tersedia.</div>';
}

// Ambil data kategori obat untuk ditampilkan di dropdown
$sql = "SELECT * FROM KategoriObat";
$stmt = oci_parse($conn, $sql);
oci_execute($stmt);

$kategoriObatList = [];
while ($row = oci_fetch_assoc($stmt)) {
    $kategoriObatList[] = $row;
}
oci_free_statement($stmt);

// Proses tambah obat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $dosis = $_POST['dosis'];
    $nama = $_POST['nama'];
    $frekuensi = $_POST['frekuensi'];
    $instruksi = $_POST['instruksi'];
    $layananMedisId = $_POST['layanan_medis_id'];
    $kategoriObatId = $_POST['kategori_obat_id'];

    // Insert data obat
    $sql = "INSERT INTO Obat (Dosis, Nama, Frekuensi, Instruksi, LayananMedis_ID, KategoriObat_ID) 
            VALUES (:dosis, :nama, :frekuensi, :instruksi, :layanan_medis_id, :kategori_obat_id)";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':dosis', $dosis);
    oci_bind_by_name($stmt, ':nama', $nama);
    oci_bind_by_name($stmt, ':frekuensi', $frekuensi);
    oci_bind_by_name($stmt, ':instruksi', $instruksi);
    oci_bind_by_name($stmt, ':layanan_medis_id', $layananMedisId);
    oci_bind_by_name($stmt, ':kategori_obat_id', $kategoriObatId);

    if (oci_execute($stmt)) {
        $message = "Obat berhasil ditambahkan.";
    } else {
        $message = "Gagal menambahkan obat.";
    }
    oci_free_statement($stmt);
    oci_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Tambah Obat</title>
    <link rel="stylesheet" href="../../public/css/index.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <h1>Tambah Obat</h1>

        <?php if (isset($message)): ?>
            <div class="alert alert-info"><?= htmlentities($message); ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="action" value="add">

            <div class="mb-3">
                <label for="dosis" class="form-label">Dosis</label>
                <input type="text" class="form-control" id="dosis" name="dosis" required>
            </div>

            <div class="mb-3">
                <label for="nama" class="form-label">Nama Obat</label>
                <input type="text" class="form-control" id="nama" name="nama" required>
            </div>

            <div class="mb-3">
                <label for="frekuensi" class="form-label">Frekuensi</label>
                <input type="text" class="form-control" id="frekuensi" name="frekuensi" required>
            </div>

            <div class="mb-3">
                <label for="instruksi" class="form-label">Instruksi</label>
                <textarea class="form-control" id="instruksi" name="instruksi" required></textarea>
            </div>

            <div class="mb-3">
    <label for="layanan_medis_id" class="form-label">Layanan Medis</label>
    <select class="form-select" id="layanan_medis_id" name="layanan_medis_id" required>
        <?php foreach ($layananMedisList as $layanan): ?>
            <option value="<?= $layanan['ID']; ?>">
                <?= $layanan['ID']; ?> - <?= htmlentities($layanan['NAMAHEWAN']); ?> (<?= htmlentities($layanan['SPESIES']); ?>) - <?= htmlentities($layanan['NAMAPEMILIK']); ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

<div class="mb-3">
    <label for="kategori_obat_id" class="form-label">Kategori Obat</label>
    <select class="form-select" id="kategori_obat_id" name="kategori_obat_id" required>
        <?php foreach ($kategoriObatList as $kategori): ?>
            <option value="<?= $kategori['ID']; ?>">
                Kategori ID: <?= $kategori['ID']; ?> - <?= htmlentities($kategori['NAMA']); ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

            <button type="submit" class="btn btn-primary">Tambah Obat</button>
        </form>
    </div>
</body>

</html>
