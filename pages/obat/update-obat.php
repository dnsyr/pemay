<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['posisi'] != 'vet') {
    header("Location: ../../auth/restricted.php");
    exit();
}

include '../../config/connection.php';
include '../vet/header.php';

$pegawaiId = intval($_SESSION['employee_id']); // Ambil ID pegawai dari session

// Ambil data kategori obat untuk ditampilkan di dropdown
$sql = "SELECT * FROM KategoriObat";
$stmt = oci_parse($conn, $sql);
oci_execute($stmt);

$kategoriObatList = [];
while ($row = oci_fetch_assoc($stmt)) {
    $kategoriObatList[] = $row;
}
oci_free_statement($stmt);

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
    $layananMedisList[] = $row;
}
oci_free_statement($stmt);

// Ambil data obat yang ingin diupdate berdasarkan ID
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
        exit();
    }
}

// Proses update obat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $dosis = $_POST['dosis'];
    $nama = $_POST['nama'];
    $frekuensi = $_POST['frekuensi'];
    $instruksi = $_POST['instruksi'];
    $harga = $_POST['harga'];
    $layananMedisId = $_POST['layanan-medis-id'];
    $kategoriObatId = $_POST['kategori_obat_id'];

    // Update data obat
    $sql = "UPDATE Obat SET Dosis = :dosis, Nama = :nama, Frekuensi = :frekuensi, Instruksi = :instruksi, 
            Harga = :harga, LayananMedis_ID = :layanan-medis-id, KategoriObat_ID = :kategori_obat_id
            WHERE ID = :id";
    
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':dosis', $dosis);
    oci_bind_by_name($stmt, ':nama', $nama);
    oci_bind_by_name($stmt, ':frekuensi', $frekuensi);
    oci_bind_by_name($stmt, ':instruksi', $instruksi);
    oci_bind_by_name($stmt, ':harga', $harga);
    oci_bind_by_name($stmt, ':layanan-medis-id', $layananMedisId);
    oci_bind_by_name($stmt, ':kategori_obat_id', $kategoriObatId);
    oci_bind_by_name($stmt, ':id', $obatId);

    if (oci_execute($stmt)) {
        $message = "Obat berhasil diperbarui.";
    } else {
        $message = "Gagal memperbarui obat.";
    }
    oci_free_statement($stmt);
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

        <?php if (isset($message)): ?>
            <div class="alert alert-info"><?= htmlentities($message); ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="action" value="update">

            <div class="mb-3">
                <label for="dosis" class="form-label">Dosis</label>
                <input type="text" class="form-control" id="dosis" name="dosis" value="<?= htmlentities($obat['Dosis']); ?>" required>
            </div>

            <div class="mb-3">
                <label for="nama" class="form-label">Nama Obat</label>
                <input type="text" class="form-control" id="nama" name="nama" value="<?= htmlentities($obat['Nama']); ?>" required>
            </div>

            <div class="mb-3">
                <label for="frekuensi" class="form-label">Frekuensi</label>
                <input type="text" class="form-control" id="frekuensi" name="frekuensi" value="<?= htmlentities($obat['Frekuensi']); ?>" required>
            </div>

            <div class="mb-3">
                <label for="instruksi" class="form-label">Instruksi</label>
                <textarea class="form-control" id="instruksi" name="instruksi" required><?= htmlentities($obat['Instruksi']); ?></textarea>
            </div>

            <div class="mb-3">
                <label for="harga" class="form-label">Harga</label>
                <input type="number" class="form-control" id="harga" name="harga" value="<?= htmlentities($obat['Harga']); ?>" required>
            </div>

            <div class="mb-3">
                <label for="layanan-medis-id" class="form-label">Layanan Medis</label>
                <select class="form-select" id="layanan-medis-id" name="layanan-medis-id" required>
                    <?php foreach ($layananMedisList as $layanan): ?>
                        <option value="<?= $layanan['ID']; ?>" <?= ($obat['LayananMedis_ID'] == $layanan['ID']) ? 'selected' : ''; ?>>
                            <?= $layanan['ID']; ?> - <?= htmlentities($layanan['NamaHewan']); ?> (<?= htmlentities($layanan['Spesies']); ?>) - <?= htmlentities($layanan['NamaPemilik']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="kategori_obat_id" class="form-label">Kategori Obat</label>
                <select class="form-select" id="kategori_obat_id" name="kategori_obat_id" required>
                    <?php foreach ($kategoriObatList as $kategori): ?>
                        <option value="<?= $kategori['ID']; ?>" <?= ($obat['KategoriObat_ID'] == $kategori['ID']) ? 'selected' : ''; ?>>
                            Kategori ID: <?= $kategori['ID']; ?> - <?= htmlentities($kategori['Nama']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Update Obat</button>
        </form>
    </div>
</body>

</html>
