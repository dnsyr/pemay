<?php
ob_start();
session_start();
if (!isset($_SESSION['username']) || $_SESSION['posisi'] !== 'vet') {
    header("Location: ../../auth/restricted.php");
    exit();
}

require_once '../../config/database.php';
require_once '../../config/connection.php';
require_once '../../layout/header-tailwind.php';
?>

<!-- Include styles -->
<link rel="stylesheet" href="styles/main.css">

<!-- Include JavaScript handlers -->
<script src="handlers/drawer-handlers.js"></script>
<script src="handlers/delete-handler.js"></script>
<script src="handlers/tab-handler.js"></script>

<?php
$pageTitle = 'Pet Service Management';
$db = new Database();

function formatTimestamp($timestamp) {
    try {
        if (empty($timestamp)) {
            return "Invalid timestamp";
        }
        
        // Split timestamp into date and time parts
        $parts = explode(' ', $timestamp);
        if (count($parts) !== 2) {
            return "Invalid timestamp format";
        }
        
        $dateParts = explode('-', $parts[0]);
        $timeParts = explode(':', $parts[1]);
        
        if (count($dateParts) !== 3 || count($timeParts) < 2) {
            return "Invalid timestamp parts";
        }
        
        // Create date string in correct format
        $date = $dateParts[0] . '-' . $dateParts[1] . '-' . $dateParts[2];
        $time = $timeParts[0] . ':' . $timeParts[1];
        
        $dateTime = DateTime::createFromFormat('d-m-Y H:i', $date . ' ' . $time);
        
        if ($dateTime === false) {
            return "Invalid timestamp conversion";
        }
        
        return $dateTime->format('d M Y, H:i');
    } catch (Exception $e) {
        error_log("Error formatting timestamp: " . $e->getMessage());
        return "Error formatting timestamp";
    }
}

// Process POST request if adding new medical service
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    require_once 'add-medical-services.php';
}

// Get current tab from URL or default to medical-services
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'medical-services';

// Get pagination parameters
$limit = 6;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Get filter parameters
$filterNamaHewan = trim($_GET['nama_hewan'] ?? '');
$filterNamaPemilik = trim($_GET['nama_pemilik'] ?? '');
$filterStatus = trim($_GET['status'] ?? '');

// Get dropdown filter options
$db->query("SELECT DISTINCT h.Nama FROM Hewan h WHERE h.onDelete = 0 ORDER BY h.Nama");
$hewanOptions = $db->resultSet();

$db->query("SELECT DISTINCT ph.Nama FROM PemilikHewan ph WHERE ph.onDelete = 0 ORDER BY ph.Nama");
$pemilikOptions = $db->resultSet();

// Get success/error messages
$message = htmlentities($_GET['message'] ?? '');
$error = htmlentities($_GET['error'] ?? '');

// Data for Medical Services tab
if ($tab === 'medical-services') {
    // Build WHERE clause with proper OCI binding
    $whereConditions = ["lm.onDelete = 0"];
    $bindParams = [];
    
    if (!empty($filterNamaHewan)) {
        $whereConditions[] = "UPPER(h.Nama) LIKE UPPER(:nama_hewan)";
        $bindParams[':nama_hewan'] = "%$filterNamaHewan%";
    }
    
    if (!empty($filterNamaPemilik)) {
        $whereConditions[] = "UPPER(ph.Nama) LIKE UPPER(:nama_pemilik)";
        $bindParams[':nama_pemilik'] = "%$filterNamaPemilik%";
    }
    
    if (!empty($filterStatus)) {
        $whereConditions[] = "lm.Status = :status";
        $bindParams[':status'] = $filterStatus;
    }
    
    $whereClause = implode(' AND ', $whereConditions);

    $sql = "SELECT lm.ID, 
            TO_CHAR(lm.Tanggal, 'DD-MM-YYYY HH24:MI') as TANGGAL,
            lm.TotalBiaya,
            lm.Description,
            lm.Status,
            h.Nama as NamaHewan,
            h.Spesies,
            ph.Nama as NamaPemilik,
            ph.NomorTelpon,
            (
                SELECT LISTAGG(jlm.Nama, ', ') WITHIN GROUP (ORDER BY jlm.Nama)
                FROM TABLE(lm.JenisLayanan) jl
                JOIN JenisLayananMedis jlm ON jlm.ID = COLUMN_VALUE
                WHERE jlm.onDelete = 0
            ) as JenisLayananNama,
            (
                SELECT SUM(jlm.Biaya)
                FROM TABLE(lm.JenisLayanan) jl
                JOIN JenisLayananMedis jlm ON jlm.ID = COLUMN_VALUE
                WHERE jlm.onDelete = 0
            ) as TotalBiayaLayanan
            FROM LayananMedis lm
            JOIN Hewan h ON lm.Hewan_ID = h.ID
            JOIN PemilikHewan ph ON h.PemilikHewan_ID = ph.ID
            WHERE $whereClause
            ORDER BY 
                CASE lm.Status 
                    WHEN 'Emergency' THEN 1
                    WHEN 'Scheduled' THEN 2
                    WHEN 'Finished' THEN 3
                    WHEN 'Canceled' THEN 4
                    ELSE 5
                END,
                lm.Tanggal DESC
            OFFSET " . ($offset) . " ROWS FETCH NEXT " . ($limit) . " ROWS ONLY";

    $stmt = oci_parse($conn, $sql);
    
    // Bind all parameters
    foreach ($bindParams as $key => $value) {
        oci_bind_by_name($stmt, $key, $bindParams[$key]);
    }
    
    oci_execute($stmt);

    $layananMedis = [];
    while ($row = oci_fetch_assoc($stmt)) {
        $layananMedis[] = $row;
    }

    // Get total count for pagination with the same filters
    $sqlCount = "SELECT COUNT(*) AS total 
                 FROM LayananMedis lm
                 JOIN Hewan h ON lm.Hewan_ID = h.ID
                 JOIN PemilikHewan ph ON h.PemilikHewan_ID = ph.ID
                 WHERE $whereClause";
                 
    $stmtCount = oci_parse($conn, $sqlCount);
    
    // Bind parameters again for count query
    foreach ($bindParams as $key => $value) {
        oci_bind_by_name($stmtCount, $key, $bindParams[$key]);
    }
    
    oci_execute($stmtCount);
    $rowCount = oci_fetch_assoc($stmtCount);
    $totalPages = ceil($rowCount['TOTAL'] / $limit);
}

// Data for Obat tab
if ($tab === 'obat') {
    $whereConditions = ["ro.onDelete = 0"];
    $bindParams = [];
    
    if (!empty($filterNamaHewan)) {
        $whereConditions[] = "UPPER(h.Nama) LIKE UPPER(:nama_hewan)";
        $bindParams[':nama_hewan'] = "%$filterNamaHewan%";
    }
    
    if (!empty($filterNamaPemilik)) {
        $whereConditions[] = "UPPER(ph.Nama) LIKE UPPER(:nama_pemilik)";
        $bindParams[':nama_pemilik'] = "%$filterNamaPemilik%";
    }
    
    if (!empty($filterStatus)) {
        $whereConditions[] = "lm.Status = :status";
        $bindParams[':status'] = $filterStatus;
    }
    
    $whereClause = implode(' AND ', $whereConditions);

    $sql = "SELECT ro.ID, 
            ro.Nama, 
            ro.Dosis, 
            ro.Frekuensi, 
            ro.Instruksi,
            TO_CHAR(lm.Tanggal, 'DD-MM-YYYY HH24:MI') as TANGGALLAYANAN,
            ko.Nama AS KATEGORIOBAT,
            h.Nama AS NAMAHEWAN,
            ph.Nama AS NAMAPEMILIK,
            lm.Status
            FROM ResepObat ro
            JOIN LayananMedis lm ON ro.LayananMedis_ID = lm.ID
            JOIN Hewan h ON lm.Hewan_ID = h.ID
            JOIN PemilikHewan ph ON h.PemilikHewan_ID = ph.ID
            JOIN KategoriObat ko ON ro.KategoriObat_ID = ko.ID
            WHERE $whereClause
            ORDER BY ro.Nama ASC
            OFFSET :offset ROWS FETCH NEXT :limit ROWS ONLY";

    $stmt = oci_parse($conn, $sql);
    
    // Bind parameters
    foreach ($bindParams as $key => $value) {
        oci_bind_by_name($stmt, $key, $bindParams[$key]);
    }
    oci_bind_by_name($stmt, ":offset", $offset);
    oci_bind_by_name($stmt, ":limit", $limit);
    
    oci_execute($stmt);
    
    $obatList = [];
    while ($row = oci_fetch_assoc($stmt)) {
        $obatList[] = $row;
    }

    // Get total count for pagination
    $sqlCount = "SELECT COUNT(*) AS total 
                 FROM ResepObat ro
                 JOIN LayananMedis lm ON ro.LayananMedis_ID = lm.ID
                 JOIN Hewan h ON lm.Hewan_ID = h.ID
                 JOIN PemilikHewan ph ON h.PemilikHewan_ID = ph.ID
                 JOIN KategoriObat ko ON ro.KategoriObat_ID = ko.ID
                 WHERE $whereClause";
                 
    $stmtCount = oci_parse($conn, $sqlCount);
    
    // Bind parameters for count query
    foreach ($bindParams as $key => $value) {
        oci_bind_by_name($stmtCount, $key, $bindParams[$key]);
    }
    
    oci_execute($stmtCount);
    $rowCount = oci_fetch_assoc($stmtCount);
    $totalPages = ceil($rowCount['TOTAL'] / $limit);
}
?>

<div class="pb-6 px-12 text-[#363636]">
    <div class="flex justify-between items-center mb-4">
        <div class="flex items-center gap-4">
            <h2 class="text-2xl font-semibold">Pet Service Management</h2>
        </div>
        <div class="flex gap-4">
            <?php if ($error): ?>
                <div role="alert" class="alert alert-error py-1.5 px-5 rounded-full text-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 stroke-current" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span><?= $error ?></span>
                </div>
            <?php endif; ?>

            <?php if ($message): ?>
                <div role="alert" class="alert alert-success py-1.5 px-5 rounded-full text-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 stroke-current" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span><?= $message ?></span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="mb-4">
        <form method="GET" action="dashboard.php">
            <input type="hidden" name="tab" value="<?= $tab ?>">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="text-sm text-gray-600 mb-1">Nama Hewan</label>
                    <select name="nama_hewan" class="select2 select select-bordered w-full bg-white">
                        <option value="">Filter by pet species</option>
                        <?php foreach ($hewanOptions as $hewan): ?>
                            <option value="<?= htmlentities($hewan['NAMA']) ?>" 
                                    <?= $hewan['NAMA'] === $filterNamaHewan ? 'selected' : '' ?>>
                                <?= htmlentities($hewan['NAMA']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="text-sm text-gray-600 mb-1">Nama Pemilik</label>
                    <select name="nama_pemilik" class="select2 select select-bordered w-full bg-white">
                        <option value="">Filter by pet species</option>
                        <?php foreach ($pemilikOptions as $pemilik): ?>
                            <option value="<?= htmlentities($pemilik['NAMA']) ?>" 
                                    <?= $pemilik['NAMA'] === $filterNamaPemilik ? 'selected' : '' ?>>
                                <?= htmlentities($pemilik['NAMA']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="text-sm text-gray-600 mb-1">Status Layanan</label>
                    <select name="status" class="select2 select select-bordered w-full bg-white">
                        <option value="">Filter by pet species</option>
                        <option value="Emergency" <?= $filterStatus === 'Emergency' ? 'selected' : '' ?>>Emergency</option>
                        <option value="Scheduled" <?= $filterStatus === 'Scheduled' ? 'selected' : '' ?>>Scheduled</option>
                        <option value="Finished" <?= $filterStatus === 'Finished' ? 'selected' : '' ?>>Finished</option>
                        <option value="Canceled" <?= $filterStatus === 'Canceled' ? 'selected' : '' ?>>Canceled</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="btn btn-sm bg-[#D4F0EA] hover:bg-[#D4F0EA] text-[#363636] border-[#363636] w-full">
                        <i class="fas fa-filter mr-2"></i> Filter
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Tab Navigation -->
    <div role="tablist" class="tabs tabs-lifted">
        <!-- Medical Services Tab -->
        <input type="radio" name="my_tabs_2" role="tab" 
               class="tab" 
               aria-label="Medical Services"
               value="medical-services"
               <?= $tab === 'medical-services' ? 'checked' : '' ?>>
        <div role="tabpanel" class="tab-content bg-base-100 border-base-300 rounded-box p-6">
            <!-- Medical Services Content -->
            <div class="flex flex-col gap-4">
                <!-- Medical Services Table -->
                <?php if (empty($layananMedis)): ?>
                    <div class="alert alert-info">Tidak ada data layanan medis untuk ditampilkan.</div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <div class="overflow-hidden border border-[#363636] rounded-xl shadow-md shadow-[#717171]">
                            <table class="table table-zebra bg-white w-full">
                                <thead>
                                    <tr class="bg-[#D4F0EA] text-[#363636] font-semibold">
                                        <th class="border-b border-[#363636] text-center">No.</th>
                                        <th class="border-b border-[#363636]">Tanggal</th>
                                        <th class="border-b border-[#363636]">Nama Hewan</th>
                                        <th class="border-b border-[#363636]">Spesies</th>
                                        <th class="border-b border-[#363636]">Nama Pemilik</th>
                                        <th class="border-b border-[#363636]">No. Telpon</th>
                                        <th class="border-b border-[#363636]">Jenis Layanan</th>
                                        <th class="border-b border-[#363636]">Total Biaya</th>
                                        <th class="border-b border-[#363636]">Status</th>
                                        <th class="border-b border-[#363636] text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $nomor = $offset + 1; ?>
                                    <?php foreach ($layananMedis as $layanan): ?>
                                        <tr>
                                            <td class="text-center"><?= $nomor++; ?></td>
                                            <td><?= formatTimestamp($layanan['TANGGAL']); ?></td>
                                            <td><?= htmlentities($layanan['NAMAHEWAN']); ?></td>
                                            <td><?= htmlentities($layanan['SPESIES']); ?></td>
                                            <td><?= htmlentities($layanan['NAMAPEMILIK']); ?></td>
                                            <td><?= htmlentities($layanan['NOMORTELPON']); ?></td>
                                            <td><?= htmlentities($layanan['JENISLAYANANNAMA']); ?></td>
                                            <td>Rp <?= number_format($layanan['TOTALBIAYA'], 0, ',', '.'); ?></td>
                                            <td>
                                                <span class="badge <?= 
                                                    $layanan['STATUS'] === 'Emergency' ? 'bg-red-100 text-red-800' : 
                                                    ($layanan['STATUS'] === 'Scheduled' ? 'bg-blue-100 text-blue-800' : 
                                                    ($layanan['STATUS'] === 'Finished' ? 'bg-green-100 text-green-800' : 
                                                    'bg-gray-100 text-gray-800')) 
                                                ?>">
                                                    <?= htmlentities($layanan['STATUS']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="flex gap-2 justify-center">
                                                    <?php if ($layanan['STATUS'] === 'Finished' || $layanan['STATUS'] === 'Canceled'): ?>
                                                        <button class="btn btn-sm bg-[#D4F0EA] hover:bg-[#D4F0EA] text-[#363636] border-none" 
                                                                onclick="alert('Cannot update this record.')">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <button class="btn btn-sm bg-[#D4F0EA] hover:bg-[#D4F0EA] text-[#363636] border-none"
                                                                onclick="openUpdateDrawer('<?= $layanan['ID'] ?>')">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <button onclick="deleteRecord('<?= $layanan['ID'] ?>', 'medical')" 
                                                            class="btn btn-sm bg-red-100 hover:bg-red-200 text-red-800 border-none">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Medications Tab -->
        <input type="radio" name="my_tabs_2" role="tab" 
               class="tab" 
               aria-label="Medications"
               value="obat"
               onclick="window.location.href='?tab=obat'"
               <?= $tab === 'obat' ? 'checked' : '' ?>>
        <div role="tabpanel" class="tab-content bg-base-100 border-base-300 rounded-box p-6">
            <!-- Medications Content -->
            <div class="flex flex-col gap-4">
                <!-- Medications Table -->
                <?php if (empty($obatList)): ?>
                    <div class="alert alert-info">Tidak ada data obat untuk ditampilkan.</div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <div class="overflow-hidden border border-[#363636] rounded-xl shadow-md shadow-[#717171]">
                            <table class="table table-zebra bg-white w-full dark:bg-white">
                                <thead>
                                    <tr class="bg-[#D4F0EA] text-[#363636] font-semibold">
                                        <th class="border-b border-[#363636] text-center">No.</th>
                                        <th class="border-b border-[#363636]">Nama Obat</th>
                                        <th class="border-b border-[#363636]">Dosis</th>
                                        <th class="border-b border-[#363636]">Frekuensi</th>
                                        <th class="border-b border-[#363636]">Instruksi</th>
                                        <th class="border-b border-[#363636]">Tanggal Layanan</th>
                                        <th class="border-b border-[#363636]">Kategori Obat</th>
                                        <th class="border-b border-[#363636]">Nama Hewan</th>
                                        <th class="border-b border-[#363636]">Nama Pemilik</th>
                                        <th class="border-b border-[#363636]">Status</th>
                                        <th class="border-b border-[#363636] text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($obatList as $index => $obat): ?>
                                        <tr class="text-[#363636]">
                                            <td class="text-center"><?= $index + 1; ?></td>
                                            <td><?= htmlentities($obat['NAMA']); ?></td>
                                            <td><?= htmlentities($obat['DOSIS']); ?></td>
                                            <td><?= htmlentities($obat['FREKUENSI']); ?></td>
                                            <td><?= htmlentities($obat['INSTRUKSI']); ?></td>
                                            <td><?= formatTimestamp($obat['TANGGALLAYANAN']); ?></td>
                                            <td><?= htmlentities($obat['KATEGORIOBAT']); ?></td>
                                            <td><?= htmlentities($obat['NAMAHEWAN']); ?></td>
                                            <td><?= htmlentities($obat['NAMAPEMILIK']); ?></td>
                                            <td>
                                                <span class="badge <?= 
                                                    $obat['STATUS'] === 'Emergency' ? 'bg-red-100 text-red-800' : 
                                                    ($obat['STATUS'] === 'Scheduled' ? 'bg-blue-100 text-blue-800' : 
                                                    ($obat['STATUS'] === 'Finished' ? 'bg-green-100 text-green-800' : 
                                                    'bg-gray-100 text-gray-800')) 
                                                ?>">
                                                    <?= htmlentities($obat['STATUS']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="flex gap-2 justify-center">
                                                    <?php if ($obat['STATUS'] === 'Finished' || $obat['STATUS'] === 'Canceled'): ?>
                                                        <button class="btn btn-sm bg-[#D4F0EA] hover:bg-[#D4F0EA] text-[#363636] border-none" 
                                                                onclick="alert('Cannot update this record.')">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <button class="btn btn-sm bg-[#D4F0EA] hover:bg-[#D4F0EA] text-[#363636] border-none"
                                                                onclick="openUpdateObatDrawer('<?= $obat['ID'] ?>')">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <button onclick="deleteRecord('<?= $obat['ID'] ?>', 'medication')" 
                                                            class="btn btn-sm bg-red-100 hover:bg-red-200 text-red-800 border-none">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <div class="join flex justify-center mt-4">
        <?php 
        $totalPagesCount = $tab === 'medical-services' ? $totalPages : $totalPages;
        $baseUrl = "?tab=$tab&page=";
        ?>
        <a class="join-item btn btn-sm <?= ($page <= 1) ? 'btn-disabled' : '' ?>"
                   href="<?= $baseUrl . ($page - 1) ?>&nama_hewan=<?= urlencode($filterNamaHewan) ?>&nama_pemilik=<?= urlencode($filterNamaPemilik) ?>&status=<?= urlencode($filterStatus) ?>">«</a>
                        
                        <?php for ($i = 1; $i <= $totalPagesCount; $i++): ?>
                            <a class="join-item btn btn-sm <?= ($page === $i) ? 'bg-[#D4F0EA]' : '' ?>"
                               href="<?= $baseUrl . $i ?>&nama_hewan=<?= urlencode($filterNamaHewan) ?>&nama_pemilik=<?= urlencode($filterNamaPemilik) ?>&status=<?= urlencode($filterStatus) ?>"><?= $i ?></a>
                        <?php endfor; ?>
                        
                        <a class="join-item btn btn-sm <?= ($page >= $totalPagesCount) ? 'btn-disabled' : '' ?>"
                        href="<?= $baseUrl . ($page + 1) ?>&nama_hewan=<?= urlencode($filterNamaHewan) ?>&nama_pemilik=<?= urlencode($filterNamaPemilik) ?>&status=<?= urlencode($filterStatus) ?>">»</a>
    </div>

<!-- Floating Add Button -->
<?php if ($tab === 'medical-services'): ?>
    <button onclick="document.getElementById('my-drawer').checked = true"
            class="bg-[#D4F0EA] w-14 h-14 flex justify-center items-center rounded-full fixed bottom-5 right-5 border border-[#363636] shadow-md shadow-[#717171]">
        <i class="fas fa-plus fa-lg"></i>
    </button>
<?php endif; ?>

<!-- Include drawers -->
<?php 
include 'components/drawers/update-medical-drawer.php';
include 'components/drawers/update-obat-drawer.php';
include 'components/drawers/add-medical-drawer.php';
?>
