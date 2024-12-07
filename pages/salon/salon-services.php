<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['posisi'] != 'staff') {
    header("Location: ../../auth/restricted.php");
    exit();
}

include '../../config/connection.php';
include '../staff/header.php';

// Handle Delete Request
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    if ($delete_id > 0) {
        $sql = "UPDATE LayananSalon SET onDelete = 1 WHERE ID = :id";
        $stmtDelete = oci_parse($conn, $sql);
        oci_bind_by_name($stmtDelete, ":id", $delete_id);

        if (oci_execute($stmtDelete)) {
            echo "<script>alert('Layanan salon berhasil dihapus!'); window.location.href='medical-services.php';</script>";
            exit();
        } else {
            $error = oci_error($stmtDelete);
            echo "<script>alert('Gagal menghapus layanan salon: " . htmlentities($error['message']) . "');</script>";
        }
        oci_free_statement($stmtDelete);
    } else {
        echo "<script>alert('ID tidak valid!'); window.location.href='medical-services.php';</script>";
    }
}

// Ambil Data Layanan Salon
$sql = "SELECT ls.ID, ls.Tanggal, ls.TotalBiaya, ls.Status, 
               h.Nama AS NamaHewan, h.Spesies, 
               ph.Nama AS NamaPemilik, ph.NomorTelpon
        FROM LayananSalon ls
        JOIN Hewan h ON ls.Hewan_ID = h.ID
        JOIN PemilikHewan ph ON h.PemilikHewan_ID = ph.ID
        WHERE ls.onDelete = 0
        ORDER BY ls.Tanggal DESC";
$stmt = oci_parse($conn, $sql);

if (!oci_execute($stmt)) {
    $error = oci_error($stmt);
    die("Terjadi kesalahan saat mengambil data: " . htmlentities($error['message']));
}

$layananSalon = [];
while ($row = oci_fetch_assoc($stmt)) {
    $layananSalon[] = $row;
}
oci_free_statement($stmt);
oci_close($conn);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Layanan Salon</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1>Layanan Salon</h1>
            <a href="add-salon-services.php" class="btn btn-primary">Tambah Layanan Salon</a>
        </div>

        <?php if (empty($layananSalon)): ?>
            <p class="alert alert-info">Tidak ada data layanan Salon untuk ditampilkan.</p>
        <?php endif; ?>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tanggal</th>
                    <th>Total Biaya</th>
                    <th>Status</th>
                    <th>Nama Hewan</th>
                    <th>Spesies</th>
                    <th>Nama Pemilik</th>
                    <th>No. Telepon</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($layananSalon)): ?>
                    <?php foreach ($layananSalon as $layanan): ?>
                        <tr>
                            <td><?= htmlentities($layanan['ID']); ?></td>
                            <td><?= htmlentities($layanan['TANGGAL']); ?></td>
                            <td>Rp <?= number_format($layanan['TOTALBIAYA'], 0, ',', '.'); ?></td>
                            <td><?= htmlentities($layanan['STATUS']); ?></td>
                            <td><?= htmlentities($layanan['NAMAHEWAN']); ?></td>
                            <td><?= htmlentities($layanan['SPESIES']); ?></td>
                            <td><?= htmlentities($layanan['NAMAPEMILIK']); ?></td>
                            <td><?= htmlentities($layanan['NOMORTELPON']); ?></td>
                            <td>
                                <a href="update-salon-services.php?id=<?= $layanan['ID']; ?>" class="btn btn-warning btn-sm">Update</a>
                                <a href="salon-services.php?delete_id=<?= $layanan['ID']; ?>" 
                                    class="btn btn-danger btn-sm" 
                                    onclick="return confirm('Apakah Anda yakin ingin menghapus layanan ini?');">Hapus
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10" class="text-center">Tidak ada data tersedia.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>

</html>