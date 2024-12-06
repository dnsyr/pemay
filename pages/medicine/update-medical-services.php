<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['posisi'] != 'vet') {
    header("Location: ../../auth/restricted.php");
    exit();
}

include '../../config/connection.php';
include '../vet/header.php';

// Ambil Data Layanan Medis Berdasarkan ID
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

// Update Status Layanan Medis
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
    oci_close($conn);
}
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
                    <option value="Emergency" <?= $layanan['STATUS'] == 'emergency' ? 'selected' : ''; ?>>Emergency</option>
                    <option value="Selesai" <?= $layanan['STATUS'] == 'Selesai' ? 'selected' : ''; ?>>Selesai</option>
                </select>
            </div>
            <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
            <a href="medical-services.php" class="btn btn-secondary">Kembali</a>
        </form>
    </div>
</body>

</html>
