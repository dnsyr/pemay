<?php
session_start();
date_default_timezone_set('Asia/Jakarta'); // Set zona waktu ke Jakarta

// Cek apakah pengguna sudah login dan berposisi sebagai 'vet'
if (!isset($_SESSION['username']) || $_SESSION['posisi'] != 'vet') {
    header("Location: ../../auth/restricted.php");
    exit();
}

include '../../config/connection.php';
include '../../layout/header.php';

// Menangani Penghapusan Layanan Medis
if (isset($_GET['delete_id'])) {
    $deleteId = intval($_GET['delete_id']);

    // Pastikan ID valid
    if ($deleteId > 0) {
        // Misalnya, kita hanya mengubah flag onDelete menjadi 1
        $sqlDelete = "UPDATE LayananMedis SET onDelete = 1 WHERE ID = :id";
        $stmtDelete = oci_parse($conn, $sqlDelete);
        oci_bind_by_name($stmtDelete, ":id", $deleteId);

        if (oci_execute($stmtDelete, OCI_COMMIT_ON_SUCCESS)) {
            $_SESSION['success_message'] = "Layanan berhasil dihapus.";
        } else {
            $error = oci_error($stmtDelete);
            $_SESSION['error_message'] = "Gagal menghapus layanan: " . htmlentities($error['message']);
        }

        oci_free_statement($stmtDelete);
        header("Location: dashboard.php");
        exit();
    } else {
        $_SESSION['error_message'] = "ID layanan tidak valid.";
        header("Location: dashboard.php");
        exit();
    }
}

// Mendapatkan tanggal yang dipilih atau default ke hari ini
$selectedDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Validasi format tanggal
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
    $selectedDate = date('Y-m-d');
}

// Mengatur tanggal sebelumnya dan berikutnya
$prevDate = date('Y-m-d', strtotime($selectedDate . ' -1 day'));
$nextDate = date('Y-m-d', strtotime($selectedDate . ' +1 day'));

// Mengambil data Layanan Emergency
$sqlEmergency = "SELECT lm.ID, lm.Tanggal, lm.TotalBiaya, lm.Description, lm.Status, 
                       h.Nama AS NamaHewan, h.Spesies, 
                       ph.Nama AS NamaPemilik, ph.NomorTelpon
                FROM LayananMedis lm
                JOIN Hewan h ON lm.Hewan_ID = h.ID
                JOIN PemilikHewan ph ON h.PemilikHewan_ID = ph.ID
                WHERE lm.Status = 'Emergency' AND lm.onDelete = 0
                ORDER BY lm.Tanggal DESC";

$stmtEmergency = oci_parse($conn, $sqlEmergency);
oci_execute($stmtEmergency);

$emergencyServices = [];
while ($row = oci_fetch_assoc($stmtEmergency)) {
    $emergencyServices[] = $row;
}
oci_free_statement($stmtEmergency);

// Mengambil data Layanan Scheduled pada tanggal yang dipilih
$sqlScheduled = "SELECT lm.ID, lm.Tanggal, lm.TotalBiaya, lm.Description, lm.Status, 
                       h.Nama AS NamaHewan, h.Spesies, 
                       ph.Nama AS NamaPemilik, ph.NomorTelpon
                FROM LayananMedis lm
                JOIN Hewan h ON lm.Hewan_ID = h.ID
                JOIN PemilikHewan ph ON h.PemilikHewan_ID = ph.ID
                WHERE lm.Status = 'Scheduled' 
                  AND lm.onDelete = 0
                  AND TRUNC(lm.Tanggal) = TO_DATE(:selectedDate, 'YYYY-MM-DD')
                ORDER BY lm.Tanggal ASC"; // Mengurutkan dari jam terendah ke tertinggi

$stmtScheduled = oci_parse($conn, $sqlScheduled);
oci_bind_by_name($stmtScheduled, ":selectedDate", $selectedDate);
oci_execute($stmtScheduled);

$scheduledServices = [];
while ($row = oci_fetch_assoc($stmtScheduled)) {
    $scheduledServices[] = $row;
}
oci_free_statement($stmtScheduled);

// Tutup koneksi setelah selesai mengambil data
oci_close($conn);
?>
    <style>
        .table-emergency {
            background-color: #f8d7da; /* Merah muda untuk Emergency */
        }

        .table-scheduled {
            background-color: #d1ecf1; /* Biru muda untuk Scheduled */
        }
    </style>

    <div class="container mt-5">
        <!-- Pesan Sukses atau Error -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?= htmlentities($_SESSION['success_message']); ?>
                <?php unset($_SESSION['success_message']); ?>
            </div>
        <?php elseif (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger">
                <?= htmlentities($_SESSION['error_message']); ?>
                <?php unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>

        <!-- Navigasi Tanggal -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <a href="?date=<?= $prevDate; ?>" class="btn btn-outline-primary">&laquo; Sebelumnya</a>
                <a href="?date=<?= $nextDate; ?>" class="btn btn-outline-primary">Berikutnya &raquo;</a>
            </div>
            <div>
                <form method="GET" action="dashboard.php" class="d-flex">
                    <input type="hidden" name="tab" value="dashboard">
                    <input type="date" name="date" value="<?= htmlentities($selectedDate); ?>" class="form-control me-2" required>
                    <button type="submit" class="btn btn-primary">Pilih Tanggal</button>
                </form>
            </div>
        </div>

        <!-- Layanan Emergency -->
        <div class="mb-5">
            <h2>Layanan Emergency</h2>
            <?php if (empty($emergencyServices)): ?>
                <p class="alert alert-info">Tidak ada data layanan emergency untuk ditampilkan.</p>
            <?php else: ?>
                <table class="table table-bordered table-emergency">
                    <thead class="table-dark">
                        <tr>
                            <th>No.</th>
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
                        <?php $nomor = 1; ?>
                        <?php foreach ($emergencyServices as $service): ?>
                            <tr>
                                <td><?= $nomor++; ?></td>
                                <td><?= htmlentities(date('d-m-Y H:i', strtotime($service['TANGGAL']))); ?></td>
                                <td>Rp <?= number_format($service['TOTALBIAYA'], 0, ',', '.'); ?></td>
                                <td><?= htmlentities($service['DESCRIPTION']); ?></td>
                                <td><?= htmlentities($service['STATUS']); ?></td>
                                <td><?= htmlentities($service['NAMAHEWAN']); ?></td>
                                <td><?= htmlentities($service['SPESIES']); ?></td>
                                <td><?= htmlentities($service['NAMAPEMILIK']); ?></td>
                                <td><?= htmlentities($service['NOMORTELPON']); ?></td>
                                <td>
                                    <a href="update-medical-services.php?id=<?= $service['ID']; ?>" class="btn btn-warning btn-sm">Update</a>
                                    <a href="dashboard.php?delete_id=<?= $service['ID']; ?>" 
                                       class="btn btn-danger btn-sm" 
                                       onclick="return confirm('Apakah Anda yakin ingin menghapus layanan ini?');">Hapus
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Layanan Scheduled -->
        <div class="mb-5">
            <h2>Layanan Scheduled pada <?= htmlentities(date('d-m-Y', strtotime($selectedDate))); ?></h2>
            <?php if (empty($scheduledServices)): ?>
                <p class="alert alert-info">Tidak ada data layanan scheduled pada tanggal ini.</p>
            <?php else: ?>
                <table class="table table-bordered table-scheduled">
                    <thead class="table-dark">
                        <tr>
                            <th>No.</th>
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
                        <?php $nomor = 1; ?>
                        <?php foreach ($scheduledServices as $service): ?>
                            <tr>
                                <td><?= $nomor++; ?></td>
                                <td><?= htmlentities(date('d-m-Y H:i', strtotime($service['TANGGAL']))); ?></td>
                                <td>Rp <?= number_format($service['TOTALBIAYA'], 0, ',', '.'); ?></td>
                                <td><?= htmlentities($service['DESCRIPTION']); ?></td>
                                <td><?= htmlentities($service['STATUS']); ?></td>
                                <td><?= htmlentities($service['NAMAHEWAN']); ?></td>
                                <td><?= htmlentities($service['SPESIES']); ?></td>
                                <td><?= htmlentities($service['NAMAPEMILIK']); ?></td>
                                <td><?= htmlentities($service['NOMORTELPON']); ?></td>
                                <td>
                                    <a href="update-medical-services.php?id=<?= $service['ID']; ?>" class="btn btn-warning btn-sm">Update</a>
                                    <a href="dashboard.php?delete_id=<?= $service['ID']; ?>" 
                                       class="btn btn-danger btn-sm" 
                                       onclick="return confirm('Apakah Anda yakin ingin menghapus layanan ini?');">Hapus
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

