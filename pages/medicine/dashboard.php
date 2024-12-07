<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['posisi'] != 'vet') {
    header("Location: ../../auth/restricted.php");
    exit();
}

include '../../config/connection.php';
include '../../layout/header.php';

// Menangani Tab yang Dipilih
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'medical-services'; // Default ke 'medical-services'

// Data untuk Medical Services
if ($tab === 'medical-services') {
    // Ambil Data Layanan Medis
    $sql = "SELECT lm.ID, lm.Tanggal, lm.TotalBiaya, lm.Description, lm.Status, 
                   h.Nama AS NamaHewan, h.Spesies, 
                   ph.Nama AS NamaPemilik, ph.NomorTelpon
            FROM LayananMedis lm
            JOIN Hewan h ON lm.Hewan_ID = h.ID
            JOIN PemilikHewan ph ON h.PemilikHewan_ID = ph.ID
            WHERE lm.onDelete = 0
            ORDER BY lm.Tanggal DESC";
    $stmt = oci_parse($conn, $sql);
    if (!oci_execute($stmt)) {
        $error = oci_error($stmt);
        die("Terjadi kesalahan saat mengambil data: " . htmlentities($error['message']));
    }
    $layananMedis = [];
    while ($row = oci_fetch_assoc($stmt)) {
        $layananMedis[] = $row;
    }
    oci_free_statement($stmt);
}

// Data untuk Obat
if ($tab === 'obat') {
    // Ambil Data Obat
    $sql = "SELECT o.ID, o.Nama, o.Dosis, o.Frekuensi, o.Instruksi, o.Harga, 
                   lm.Tanggal AS TanggalLayanan, ko.Nama AS KategoriObat
            FROM Obat o
            JOIN LayananMedis lm ON o.LayananMedis_ID = lm.ID
            JOIN KategoriObat ko ON o.KategoriObat_ID = ko.ID
            WHERE o.onDelete = 0
            ORDER BY o.Nama ASC";
    $stmt = oci_parse($conn, $sql);
    if (!oci_execute($stmt)) {
        $error = oci_error($stmt);
        die("Terjadi kesalahan saat mengambil data: " . htmlentities($error['message']));
    }
    $obatList = [];
    while ($row = oci_fetch_assoc($stmt)) {
        $obatList[] = $row;
    }
    oci_free_statement($stmt);
}

oci_close($conn);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Pet Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1>Pet Management</h1>
        </div>

        <!-- Tab Navigation -->
        <ul class="nav nav-tabs">
            <li class="nav-item">
                <a class="nav-link <?php echo $tab === 'medical-services' ? 'active' : ''; ?>" href="?tab=medical-services">Layanan Medis</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $tab === 'obat' ? 'active' : ''; ?>" href="?tab=obat">Obat</a>
            </li>
        </ul>

        <div class="mt-3">
            <!-- Content for Medical Services -->
            <?php if ($tab === 'medical-services'): ?>
                <div class="d-flex justify-content-between mb-3">
                    <a href="add-medical-services.php" class="btn btn-primary">Tambah Layanan Medis</a>
                </div>

                <?php if (empty($layananMedis)): ?>
                    <p class="alert alert-info">Tidak ada data layanan medis untuk ditampilkan.</p>
                <?php else: ?>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tanggal</th>
                                <th>Total Biaya</th>
                                <th>Deskripsi</th>
                                <th>Status</th>
                                <th>Nama Hewan</th>
                                <th>Spesies</th>
                                <th>Nama Pemilik</th>
                                <th>No. Telepon</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($layananMedis as $layanan): ?>
                                <tr>
                                    <td><?= htmlentities($layanan['ID']); ?></td>
                                    <td><?= htmlentities($layanan['TANGGAL']); ?></td>
                                    <td>Rp <?= number_format($layanan['TOTALBIAYA'], 0, ',', '.'); ?></td>
                                    <td><?= htmlentities($layanan['DESCRIPTION']); ?></td>
                                    <td><?= htmlentities($layanan['STATUS']); ?></td>
                                    <td><?= htmlentities($layanan['NAMAHEWAN']); ?></td>
                                    <td><?= htmlentities($layanan['SPESIES']); ?></td>
                                    <td><?= htmlentities($layanan['NAMAPEMILIK']); ?></td>
                                    <td><?= htmlentities($layanan['NOMORTELPON']); ?></td>
                                    <td>
                                        <a href="update-medical-services.php?id=<?= $layanan['ID']; ?>" class="btn btn-warning btn-sm">Update</a>
                                        <a href="dashboard.php?delete_id=<?= $layanan['ID']; ?>" 
                                           class="btn btn-danger btn-sm" 
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus layanan ini?');">Hapus
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

            <!-- Content for Obat -->
            <?php elseif ($tab === 'obat'): ?>
                <div class="d-flex justify-content-between mb-3">
                    <a href="add-obat.php" class="btn btn-primary">Tambah Obat</a>
                </div>

                <?php if (empty($obatList)): ?>
                    <p class="alert alert-info">Tidak ada data obat untuk ditampilkan.</p>
                <?php else: ?>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama Obat</th>
                                <th>Dosis</th>
                                <th>Frekuensi</th>
                                <th>Instruksi</th>
                                <th>Harga</th>
                                <th>Tanggal Layanan</th>
                                <th>Kategori Obat</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($obatList as $obat): ?>
                                <tr>
                                    <td><?= htmlentities($obat['ID']); ?></td>
                                    <td><?= htmlentities($obat['NAMA']); ?></td>
                                    <td><?= htmlentities($obat['DOSIS']); ?></td>
                                    <td><?= htmlentities($obat['FREKUENSI']); ?></td>
                                    <td><?= htmlentities($obat['INSTRUKSI']); ?></td>
                                    <td>Rp <?= number_format($obat['HARGA'], 0, ',', '.'); ?></td>
                                    <td><?= htmlentities($obat['TANGGALLAYANAN']); ?></td>
                                    <td><?= htmlentities($obat['KATEGORIOBAT']); ?></td>
                                    <td>
                                        <a href="update-obat.php?id=<?= $obat['ID']; ?>" class="btn btn-warning btn-sm">Update</a>
                                        <a href="obat.php?delete_id=<?= $obat['ID']; ?>" 
                                           class="btn btn-danger btn-sm" 
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus obat ini?');">Hapus
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>
