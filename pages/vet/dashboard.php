<?php
session_start();
ob_start();
include '../../config/connection.php';
require_once '../../config/database.php';

$pageTitle = 'Vet Dashboard';
include '../../layout/header-tailwind.php';


// Cek sesi user dan posisi
if (!isset($_SESSION['username']) || $_SESSION['posisi'] !== 'vet') {
  header("Location: ../../auth/restricted.php");
  exit();
}

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
function getLayananByStatus($conn, $status, $selectedDate)
{
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

<div class="min-h-screen bg-white text-base-content py-8 px-24">
  <!-- Header -->
  <div class="flex justify-between items-center mb-10">
    <h1 class="text-2xl font-bold text-gray-500">Welcome to the dashboard, vet</h1>
    <div class="flex items-center space-x-4">
      <!-- Form with horizontal alignment -->
      <form method="POST" class="flex items-center space-x-2">
        <div class="flex items-center space-x-2">
          <label for="date" class="text-sm text-gray-500">Pilih Tanggal:</label>
          <input
            type="date"
            id="date"
            name="date"
            class="input input-bordered w-full max-w-xs"
            value="<?php echo htmlspecialchars($selectedDate); ?>" />
        </div>
        <button
          type="submit"
          class="btn btn-primary">
          Tampilkan Data
        </button>
      </form>
      <!-- Right section -->
      <div class="text-sm text-right text-gray-500">
        <div>Monday</div>
        <div>December 2024</div>
      </div>
    </div>
  </div>

  <!-- Main Row: Emergency, Scheduled, and Finished Clients -->
  <div class="grid grid-cols-1 md:grid-cols-3 gap-2 mb-2">
    <!-- Emergency Clients -->
    <div class="card shadow-lg bg-red-100 border border-zinc-400 rounded-3xl">
      <div class="card-body">
        <div class="flex justify-between items-center">
          <h2 class="card-title text-base text-gray-500 font-semibold">Emergency Clients</h2>
          <a href="#" class="text-sm text-gray-700" data-toggle="modal" data-target="#emergencyModal">⤴</a>
        </div>
        <p class="text-sm">Emergency</p>
        <p class="card-title text-5xl font-bold mt-2 text-gray-500"><?php echo htmlspecialchars($emergencyCount); ?></p>
      </div>
    </div>

    <!-- Scheduled Clients -->
    <div class="card shadow-lg bg-yellow-100 border border-zinc-400 rounded-3xl">
      <div class="card-body">
        <div class="flex justify-between items-center">
          <h2 class="card-title text-base text-gray-500 font-semibold">Scheduled Clients</h2>
          <a href="#" class="text-sm text-gray-700" data-toggle="modal" data-target="#scheduledModal">⤴</a>
        </div>
        <p class="text-sm">Scheduled</p>
        <p class="card-title text-5xl font-bold mt-2 text-gray-500"><?php echo htmlspecialchars($scheduledCount); ?></p>
      </div>
    </div>

    <!-- Finished Clients -->
    <div class="card shadow-lg bg-teal-100 border border-zinc-400 rounded-3xl">
      <div class="card-body">
        <div class="flex justify-between items-center">
          <h2 class="card-title text-base text-gray-500 font-semibold">Finished Clients</h2>
          <a href="#" class="text-sm text-gray-700" data-toggle="modal" data-target="#finishedModal">⤴</a>
        </div>
        <p class="text-sm">Completed</p>
        <p class="card-title text-5xl font-bold mt-2 text-gray-500"><?php echo htmlspecialchars($finishedCount); ?></p>
      </div>
    </div>
  </div>

  <!-- Stocks & Reservations Section -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 mt-2">
    <!-- Available Products -->
    <div class="card shadow-lg bg-white border border-zinc-400 rounded-3xl">
      <div class="card-body">
        <div class="flex justify-between items-center">
          <h3 class="card-title text-sm text-gray-500 font-semibold">Available Medicine</h3>
          <a href="#" class="text-sm text-gray-500">⤴</a>
        </div>
        <div class="flex justify-between mt-4">
          <span>Antibiiotics</span>
          <span class="font-bold text-gray-500">10</span>
        </div>
        <div class="flex justify-between">
          <span>Vitamins</span>
          <span class="font-bold text-gray-500">10</span>
        </div>
      </div>
    </div>

    <!-- available treatments -->
    <div class="card shadow-lg bg-gray-50 border border-zinc-400 rounded-3xl">
      <div class="card-body">
        <div class="flex justify-between items-center">
          <h3 class="card-title text-sm font-semibold text-gray-500">Available Treatments</h3>
          <a href="#" class="text-sm text-gray-500">⤴</a>
        </div>
        <div class="flex justify-between mt-4">
          <span>Wound Treatment</span>
          <span class="font-bold text-gray-600">10</span>
        </div>
        <div class="flex justify-between">
          <span>Regular check</span>
          <span class="font-bold text-gray-600">10</span>
        </div>
        <div class="flex justify-between">
          <span>Sterilisation</span>
          <span class="font-bold text-gray-600">10</span>
        </div>
        <div class="flex justify-between">
          <span>Vaccination</span>
          <span class="font-bold text-gray-600">10</span>
        </div>
      </div>
    </div>

    <!-- Cancelled Appointments -->
    <div class="card shadow-lg bg-gray-100 border border-zinc-400 rounded-3xl">
      <div class="card-body">
        <h2 class="card-title text-base font-semibold">Cancelled Appointments</h2>
        <div class="flex justify-between mt-4">
          <span>Salon services</span>
          <span class="font-bold">10</span>
        </div>
        <div class="flex justify-between">
          <span>Medical services</span>
          <span class="font-bold">10</span>
        </div>
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