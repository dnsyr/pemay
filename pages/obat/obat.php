<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['posisi'] != 'vet') {
    header("Location: ../../auth/restricted.php");
    exit();
}

include '../../config/connection.php';
include '../vet/header.php';

// Handle Delete Request
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    if ($delete_id > 0) {
        $sql = "UPDATE Obat SET onDelete = 1 WHERE ID = :id";
        $stmtDelete = oci_parse($conn, $sql);
        oci_bind_by_name($stmtDelete, ":id", $delete_id);

        if (oci_execute($stmtDelete)) {
            echo "<script>alert('Obat berhasil dihapus!'); window.location.href='obat.php';</script>";
            exit();
        } else {
            $error = oci_error($stmtDelete);
            echo "<script>alert('Gagal menghapus obat: " . htmlentities($error['message']) . "');</script>";
        }
        oci_free_statement($stmtDelete);
    } else {
        echo "<script>alert('ID tidak valid!'); window.location.href='obat.php';</script>";
    }
}

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
oci_close($conn);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Daftar Obat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1>Daftar Obat</h1>
            <a href="add-obat.php" class="btn btn-primary">Tambah Obat</a>
        </div>

        <?php if (empty($obatList)): ?>
            <p class="alert alert-info">Tidak ada data obat untuk ditampilkan.</p>
        <?php endif; ?>

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
                <?php if (!empty($obatList)): ?>
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
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="text-center">Tidak ada data tersedia.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>

</html>
