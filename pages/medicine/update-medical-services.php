<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['posisi'] != 'vet') {
    header("Location: ../../auth/restricted.php");
    exit();
}

include '../../config/connection.php';
include '../vet/header.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    if ($id > 0) {
        $sql = "SELECT lm.ID, lm.Tanggal, lm.TotalBiaya, lm.Description, lm.Status, 
                       h.Nama AS NamaHewan, h.Spesies, 
                       ph.Nama AS NamaPemilik, ph.NomorTelpon
                FROM LayananMedis lm
                JOIN Hewan h ON lm.Hewan_ID = h.ID
                JOIN PemilikHewan ph ON h.PemilikHewan_ID = ph.ID
                WHERE lm.ID = :id AND lm.onDelete = 0";
        $stmt = oci_parse($conn, $sql);
        oci_bind_by_name($stmt, ":id", $id);

        if (!oci_execute($stmt)) {
            $error = oci_error($stmt);
            die("Terjadi kesalahan saat mengambil data: " . htmlentities($error['message']));
        }

        $layanan = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);
    } else {
        echo "<script>alert('ID tidak valid!'); window.location.href='medical-services.php';</script>";
        exit();
    }
} else {
    echo "<script>alert('ID tidak ditemukan!'); window.location.href='medical-services.php';</script>";
    exit();
}

// Handle Delete Obat
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    if ($delete_id > 0) {
        $sqlDelete = "UPDATE Obat SET onDelete = 1 WHERE ID = :id";
        $stmtDelete = oci_parse($conn, $sqlDelete);
        oci_bind_by_name($stmtDelete, ":id", $delete_id);

        if (oci_execute($stmtDelete)) {
            echo "<script>alert('Obat berhasil dihapus!'); window.location.href='update-medical-services.php?id=$id';</script>";
            exit();
        } else {
            $error = oci_error($stmtDelete);
            echo "<script>alert('Gagal menghapus obat: " . htmlentities($error['message']) . "');</script>";
        }
        oci_free_statement($stmtDelete);
    } else {
        echo "<script>alert('ID obat tidak valid!'); window.location.href='update-medical-services.php?id=$id';</script>";
    }
}

// Handle Update Status Layanan Medis
if (isset($_POST['update_status'])) {
    $status = $_POST['status'];
    $sqlUpdate = "UPDATE LayananMedis SET Status = :status WHERE ID = :id";
    $stmtUpdate = oci_parse($conn, $sqlUpdate);
    oci_bind_by_name($stmtUpdate, ":status", $status);
    oci_bind_by_name($stmtUpdate, ":id", $id);

    if (oci_execute($stmtUpdate)) {
        echo "<script>alert('Status layanan medis berhasil diperbarui!'); window.location.href='medical-services.php';</script>";
    } else {
        $error = oci_error($stmtUpdate);
        echo "<script>alert('Gagal memperbarui status layanan medis: " . htmlentities($error['message']) . "');</script>";
    }
    oci_free_statement($stmtUpdate);
}

// Menambahkan obat
if (isset($_POST['add_obat'])) {
    $obatNama = $_POST['obat_nama'];
    $obatDosis = $_POST['obat_dosis'];
    $obatFrekuensi = $_POST['obat_frekuensi'];
    $obatHarga = $_POST['obat_harga'];
    $obatInstruksi = $_POST['obat_instruksi'];
    $kategoriObatId = $_POST['kategori_obat_id']; 

    // Insert data obat
    $sqlObat = "INSERT INTO Obat (LayananMedis_ID, Nama, Dosis, Frekuensi, Harga, Instruksi, KategoriObat_ID) 
                VALUES (:layanan_medis_id, :nama, :dosis, :frekuensi, :harga, :instruksi, :kategori_obat_id)";
    $stmtObat = oci_parse($conn, $sqlObat);
    oci_bind_by_name($stmtObat, ':layanan_medis_id', $id);
    oci_bind_by_name($stmtObat, ':nama', $obatNama);
    oci_bind_by_name($stmtObat, ':dosis', $obatDosis);
    oci_bind_by_name($stmtObat, ':frekuensi', $obatFrekuensi);
    oci_bind_by_name($stmtObat, ':harga', $obatHarga);
    oci_bind_by_name($stmtObat, ':instruksi', $obatInstruksi);
    oci_bind_by_name($stmtObat, ':kategori_obat_id', $kategoriObatId);

    if (oci_execute($stmtObat)) {
        $messageObat = "Obat berhasil ditambahkan.";
        $obatAda = true; // Menandakan obat sudah ditambahkan
    } else {
        $messageObat = "Gagal menambahkan obat.";
    }
    oci_free_statement($stmtObat);
}

// Ambil Data Obat yang Terkait Layanan Medis
$sqlObatList = "SELECT * FROM Obat WHERE LayananMedis_ID = :id AND onDelete = 0";
$stmtObatList = oci_parse($conn, $sqlObatList);
oci_bind_by_name($stmtObatList, ":id", $id);
oci_execute($stmtObatList);

$obatList = [];
while ($row = oci_fetch_assoc($stmtObatList)) {
    $obatList[] = $row;
}

$obatAda = count($obatList) > 0 ? true : false;
oci_free_statement($stmtObatList);

// Ambil Data Kategori Obat
$sqlKategori = "SELECT * FROM KategoriObat";
$stmtKategori = oci_parse($conn, $sqlKategori);
oci_execute($stmtKategori);

oci_close($conn);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Update Layanan Medis</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <h1>Update Layanan Medis</h1>
        <form action="update-medical-services.php?id=<?= $layanan['ID']; ?>" method="POST">
            <!-- Form Fields for Layanan Medis -->
            <div class="mb-3">
                <label for="tanggal" class="form-label">Tanggal</label>
                <input type="text" class="form-control" id="tanggal" value="<?= htmlentities($layanan['TANGGAL']); ?>" readonly>
            </div>
            <div class="mb-3">
                <label for="total_biaya" class="form-label">Total Biaya</label>
                <input type="text" class="form-control" id="total_biaya" value="Rp <?= number_format($layanan['TOTALBIAYA'], 0, ',', '.'); ?>" readonly>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Deskripsi</label>
                <textarea class="form-control" id="description" rows="3" readonly><?= htmlentities($layanan['DESCRIPTION']); ?></textarea>
            </div>
            <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="Emergency" <?= $layanan['STATUS'] == 'Emergency' ? 'selected' : ''; ?>>Emergency</option>
                    <option value="Selesai" <?= $layanan['STATUS'] == 'Selesai' ? 'selected' : ''; ?>>Selesai</option>
                </select>
            </div>

            <!-- Form Input Obat -->
            <?php if (($_POST['obat_pertanyaan'] ?? '') == 'yes' || $obatAda): ?>
                <div id="obatForm">
                    <h3>Tambah Obat</h3>
                    <div class="mb-3">
                        <label for="kategori_obat_id" class="form-label">Kategori Obat</label>
                        <select class="form-select" id="kategori_obat_id" name="kategori_obat_id" required>
                            <?php while ($kategori = oci_fetch_assoc($stmtKategori)) : ?>
                                <option value="<?= $kategori['ID']; ?>"><?= htmlentities($kategori['NAMA']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="obat_nama" class="form-label">Nama Obat</label>
                        <input type="text" class="form-control" id="obat_nama" name="obat_nama" value="<?= isset($obatNama) ? $obatNama : ''; ?>">
                    </div>
                    <div class="mb-3">
                        <label for="obat_dosis" class="form-label">Dosis</label>
                        <input type="text" class="form-control" id="obat_dosis" name="obat_dosis" value="<?= isset($obatDosis) ? $obatDosis : ''; ?>">
                    </div>
                    <div class="mb-3">
                        <label for="obat_frekuensi" class="form-label">Frekuensi</label>
                        <input type="text" class="form-control" id="obat_frekuensi" name="obat_frekuensi" value="<?= isset($obatFrekuensi) ? $obatFrekuensi : ''; ?>">
                    </div>
                    <div class="mb-3">
                        <label for="obat_instruksi" class="form-label">Instruksi</label>
                        <textarea class="form-control" id="obat_instruksi" name="obat_instruksi"><?= isset($obatInstruksi) ? $obatInstruksi : ''; ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="obat_harga" class="form-label">Harga</label>
                        <input type="number" class="form-control" id="obat_harga" name="obat_harga" value="<?= isset($obatHarga) ? $obatHarga : ''; ?>">
                    </div>
                    <button type="submit" name="add_obat" class="btn btn-success">Tambah Obat</button>
                </div>
            <?php endif; ?>

            <!-- Update Status Button -->
            <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
            <a href="medical-services.php" class="btn btn-secondary">Kembali</a>
        </form>

        <!-- Tabel Obat yang sudah ditambahkan -->
<h3>Obat yang Ditambahkan</h3>
<table class="table">
    <thead>
        <tr>
            <th>Nama Obat</th>
            <th>Dosis</th>
            <th>Frekuensi</th>
            <th>Instruksi</th>
            <th>Harga</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($obatList as $obat): ?>
            <tr>
                <td><?= htmlentities($obat['NAMA']); ?></td>
                <td><?= htmlentities($obat['DOSIS']); ?></td>
                <td><?= htmlentities($obat['FREKUENSI']); ?></td>
                <td><?= htmlentities($obat['INSTRUKSI']); ?></td>
                <td>Rp <?= number_format($obat['HARGA'], 0, ',', '.'); ?></td>
                <td>
                    <!-- Tombol Hapus Obat -->
                    <a href="update-medical-services.php?id=<?= $id; ?>&delete_id=<?= $obat['ID']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus obat ini?')">Hapus</a>
                    
                    <!-- Tombol Update Obat -->
                    <a href="../obat/update-obat.php?id=<?= $obat['ID']; ?>" class="btn btn-warning btn-sm">Update</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>

</body>
</html>
