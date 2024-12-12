<?php
session_start();

// Cek sesi user dan posisi
if (!isset($_SESSION['username']) || $_SESSION['posisi'] !== 'vet') {
    header("Location: ../../auth/restricted.php");
    exit();
}

include '../../config/connection.php';
include '../../layout/header.php';

// Cek jika ada input tanggal
$selectedDate = isset($_POST['date']) ? $_POST['date'] : date('Y-m-d');  // Default ke hari ini

// Menghitung jumlah layanan medis berdasarkan status
$sqlCounts = "
    SELECT 
        SUM(CASE WHEN lm.Status = 'Emergency' THEN 1 ELSE 0 END) AS emergency_count,
        SUM(CASE WHEN lm.Status = 'Scheduled' AND TRUNC(lm.Tanggal) = TO_DATE(:selectedDate, 'YYYY-MM-DD') THEN 1 ELSE 0 END) AS scheduled_count,
        SUM(CASE WHEN lm.Status = 'Finished' AND TRUNC(lm.Tanggal) = TO_DATE(:selectedDate, 'YYYY-MM-DD') THEN 1 ELSE 0 END) AS finished_count,
        SUM(CASE WHEN lm.Status = 'Canceled' AND TRUNC(lm.Tanggal) = TO_DATE(:selectedDate, 'YYYY-MM-DD') THEN 1 ELSE 0 END) AS canceled_count
    FROM LayananMedis lm
    WHERE lm.onDelete = 0
";

$stmtCounts = oci_parse($conn, $sqlCounts);
oci_bind_by_name($stmtCounts, ":selectedDate", $selectedDate);
oci_execute($stmtCounts);
$rowCounts = oci_fetch_assoc($stmtCounts);
oci_free_statement($stmtCounts);

// Ambil jumlah dari hasil query
$emergencyCount = $rowCounts['EMERGENCY_COUNT'] ?? 0;
$scheduledCount = $rowCounts['SCHEDULED_COUNT'] ?? 0;
$finishedCount = $rowCounts['FINISHED_COUNT'] ?? 0;
$canceledCount = $rowCounts['CANCELED_COUNT'] ?? 0;

// Fungsi untuk mengambil data layanan medis berdasarkan status dan tanggal
function getLayananByStatus($conn, $status, $selectedDate) {
    if ($status == 'Emergency') {
        // Emergency tidak terpengaruh oleh tanggal, ambil semua data dengan status 'Emergency'
        $sql = "
            SELECT lm.ID, lm.Tanggal, lm.Status, h.Nama AS NAMAHEWAN, h.SPESIES, ph.Nama AS NAMAPEMILIK, 
                   ph.NOMORTELPON AS NOMORTELPON
            FROM LayananMedis lm
            JOIN Hewan h ON lm.Hewan_ID = h.ID
            JOIN PemilikHewan ph ON h.PemilikHewan_ID = ph.ID
            WHERE lm.Status = :status AND lm.onDelete = 0
        ";
    } else {
        // Untuk status lainnya, filter berdasarkan tanggal
        $sql = "
            SELECT lm.ID, lm.Tanggal, lm.Status, h.Nama AS NAMAHEWAN, h.SPESIES, ph.Nama AS NAMAPEMILIK, 
                   ph.NOMORTELPON AS NOMORTELPON
            FROM LayananMedis lm
            JOIN Hewan h ON lm.Hewan_ID = h.ID
            JOIN PemilikHewan ph ON h.PemilikHewan_ID = ph.ID
            WHERE lm.Status = :status AND lm.onDelete = 0 AND TRUNC(lm.Tanggal) = TO_DATE(:selectedDate, 'YYYY-MM-DD')
        ";
    }

    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ":status", $status);
    if ($status != 'Emergency') {
        oci_bind_by_name($stmt, ":selectedDate", $selectedDate);
    }
    oci_execute($stmt);
    $layanan = [];
    while ($row = oci_fetch_assoc($stmt)) {
        $layanan[] = $row;
    }
    oci_free_statement($stmt);
    return $layanan;
}

// Ambil data untuk masing-masing status berdasarkan tanggal yang dipilih
$emergencyData = getLayananByStatus($conn, 'Emergency', $selectedDate);
$scheduledData = getLayananByStatus($conn, 'Scheduled', $selectedDate);
$finishedData = getLayananByStatus($conn, 'Finished', $selectedDate);
$canceledData = getLayananByStatus($conn, 'Canceled', $selectedDate);

oci_close($conn);
?>

<div class="container mt-4">
    <!-- Button untuk menambah layanan medis baru -->
    <a href="../../pages/pet-medical/add-medical-services.php" class="btn btn-success mb-3">Tambah Layanan Medis</a>

    <form method="POST">
        <div class="form-group">
            <label for="date">Pilih Tanggal:</label>
            <input type="date" id="date" name="date" class="form-control" value="<?php echo htmlspecialchars($selectedDate); ?>">
        </div>
        <button type="submit" class="btn btn-primary">Tampilkan Data</button>
    </form>

    <div class="row mt-4">
        <!-- Card Emergency -->
        <div class="col-md-3">
            <div class="card text-white bg-danger mb-3">
                <div class="card-header">Emergency</div>
                <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($emergencyCount); ?></h5>
                    <p class="card-text">Jumlah layanan darurat.</p>
                    <button class="btn btn-light" data-toggle="modal" data-target="#emergencyModal">Show Data</button>
                </div>
            </div>
        </div>

        <!-- Card Scheduled -->
        <div class="col-md-3">
            <div class="card text-white bg-warning mb-3">
                <div class="card-header">Scheduled</div>
                <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($scheduledCount); ?></h5>
                    <p class="card-text">Jumlah layanan terjadwal pada <?php echo htmlspecialchars(date('d-m-Y', strtotime($selectedDate))); ?>.</p>
                    <button class="btn btn-light" data-toggle="modal" data-target="#scheduledModal">Show Data</button>
                </div>
            </div>
        </div>

        <!-- Card Finished -->
        <div class="col-md-3">
            <div class="card text-white bg-success mb-3">
                <div class="card-header">Finished</div>
                <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($finishedCount); ?></h5>
                    <p class="card-text">Jumlah layanan yang selesai.</p>
                    <button class="btn btn-light" data-toggle="modal" data-target="#finishedModal">Show Data</button>
                </div>
            </div>
        </div>

        <!-- Card Canceled -->
        <div class="col-md-3">
            <div class="card text-white bg-secondary mb-3">
                <div class="card-header">Canceled Today</div>
                <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($canceledCount); ?></h5>
                    <p class="card-text">Jumlah layanan yang dibatalkan hari ini.</p>
                    <button class="btn btn-light" data-toggle="modal" data-target="#canceledModal">Show Data</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Emergency Data -->
    <div class="modal fade" id="emergencyModal" tabindex="-1" role="dialog" aria-labelledby="emergencyModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <!-- Modal Header -->
                <div class="modal-header">
                    <h5 class="modal-title" id="emergencyModalLabel">Emergency Services</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <!-- Modal Body -->
                <div class="modal-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nama Hewan</th>
                                <th>Spesies</th>
                                <th>Nama Pemilik</th>
                                <th>No Telp</th>
                                <th>Jam</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($emergencyData as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['NAMAHEWAN']); ?></td>
                                    <td><?php echo htmlspecialchars($row['SPESIES']); ?></td>
                                    <td><?php echo htmlspecialchars($row['NAMAPEMILIK']); ?></td>
                                    <td><?php echo htmlspecialchars($row['NOMORTELPON']); ?></td>
                                    <td><?php echo htmlspecialchars(date('H:i', strtotime($row['TANGGAL']))); ?></td>
                                    <td>
                                        <?php if ($row['STATUS'] !== 'Finished' && $row['STATUS'] !== 'Canceled'): ?>
                                            <a href="../../pages/pet-medical/update-medical-services.php?id=<?php echo urlencode($row['ID']); ?>" class="btn btn-warning btn-sm">Update Status</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <!-- Modal Footer -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Scheduled Data -->
    <div class="modal fade" id="scheduledModal" tabindex="-1" role="dialog" aria-labelledby="scheduledModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <!-- Modal Header -->
                <div class="modal-header">
                    <h5 class="modal-title" id="scheduledModalLabel">Scheduled Services</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <!-- Modal Body -->
                <div class="modal-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nama Hewan</th>
                                <th>Spesies</th>
                                <th>Nama Pemilik</th>
                                <th>No Telp</th>
                                <th>Jam</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($scheduledData as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['NAMAHEWAN']); ?></td>
                                    <td><?php echo htmlspecialchars($row['SPESIES']); ?></td>
                                    <td><?php echo htmlspecialchars($row['NAMAPEMILIK']); ?></td>
                                    <td><?php echo htmlspecialchars($row['NOMORTELPON']); ?></td>
                                    <td><?php echo htmlspecialchars(date('H:i', strtotime($row['TANGGAL']))); ?></td>
                                    <td>
                                        <?php if ($row['STATUS'] !== 'Finished' && $row['STATUS'] !== 'Canceled'): ?>
                                            <a href="../../pages/pet-medical/update-medical-services.php?id=<?php echo urlencode($row['ID']); ?>" class="btn btn-warning btn-sm">Update Status</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <!-- Modal Footer -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Finished Data -->
    <div class="modal fade" id="finishedModal" tabindex="-1" role="dialog" aria-labelledby="finishedModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <!-- Modal Header -->
                <div class="modal-header">
                    <h5 class="modal-title" id="finishedModalLabel">Finished Services</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <!-- Modal Body -->
                <div class="modal-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nama Hewan</th>
                                <th>Spesies</th>
                                <th>Nama Pemilik</th>
                                <th>No Telp</th>
                                <th>Jam</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($finishedData as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['NAMAHEWAN']); ?></td>
                                    <td><?php echo htmlspecialchars($row['SPESIES']); ?></td>
                                    <td><?php echo htmlspecialchars($row['NAMAPEMILIK']); ?></td>
                                    <td><?php echo htmlspecialchars($row['NOMORTELPON']); ?></td>
                                    <td><?php echo htmlspecialchars(date('H:i', strtotime($row['TANGGAL']))); ?></td>
                                    <td>
                                        <!-- No Update Status Button -->
                                        <span class="text-muted">No actions available</span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <!-- Modal Footer -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Canceled Data -->
    <div class="modal fade" id="canceledModal" tabindex="-1" role="dialog" aria-labelledby="canceledModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <!-- Modal Header -->
                <div class="modal-header">
                    <h5 class="modal-title" id="canceledModalLabel">Canceled Services</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <!-- Modal Body -->
                <div class="modal-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nama Hewan</th>
                                <th>Spesies</th>
                                <th>Nama Pemilik</th>
                                <th>No Telp</th>
                                <th>Jam</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($canceledData as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['NAMAHEWAN']); ?></td>
                                    <td><?php echo htmlspecialchars($row['SPESIES']); ?></td>
                                    <td><?php echo htmlspecialchars($row['NAMAPEMILIK']); ?></td>
                                    <td><?php echo htmlspecialchars($row['NOMORTELPON']); ?></td>
                                    <td><?php echo htmlspecialchars(date('H:i', strtotime($row['TANGGAL']))); ?></td>
                                    <td>
                                        <!-- No Update Status Button -->
                                        <span class="text-muted">No actions available</span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <!-- Modal Footer -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include jQuery, Popper.js, and Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
