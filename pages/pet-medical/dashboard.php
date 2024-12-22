<?php
ob_start();
session_start();
if (!isset($_SESSION['username']) || $_SESSION['posisi'] !== 'vet') {
    header("Location: ../../auth/restricted.php");
    exit();
}

require_once '../../config/database.php';
require_once '../../layout/header-tailwind.php';

// Buat koneksi OCI
$conn = oci_connect('C##PET', '12345', '//localhost:1521/xe');
if (!$conn) {
    $e = oci_error();
    die("Koneksi gagal: " . $e['message']);
}

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

// Get current tab and pagination parameters
$tab = $_GET['tab'] ?? 'medical-services';
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
    $params = [];
    
    if ($filterNamaHewan) {
        $whereConditions[] = "h.Nama LIKE :nama_hewan";
        $params[':nama_hewan'] = "%$filterNamaHewan%";
    }
    
    if ($filterNamaPemilik) {
        $whereConditions[] = "ph.Nama LIKE :nama_pemilik";
        $params[':nama_pemilik'] = "%$filterNamaPemilik%";
    }
    if ($filterStatus) {
        $whereConditions[] = "lm.Status = :status";
        $params[':status'] = $filterStatus;
    }
    $whereClause = implode(' AND ', $whereConditions);

    $sql = "SELECT ro.ID, ro.Nama, ro.Dosis, ro.Frekuensi, ro.Instruksi, 
    TO_CHAR(lm.Tanggal, 'DD-MM-YYYY HH24:MI:SS') as TANGGALLAYANAN,
    ko.Nama AS KategoriObat, 
    h.Nama AS NamaHewan, 
    ph.Nama AS NamaPemilik,
    lm.Status
FROM ResepObat ro
            JOIN LayananMedis lm ON ro.LayananMedis_ID = lm.ID
            JOIN Hewan h ON lm.Hewan_ID = h.ID
            JOIN PemilikHewan ph ON h.PemilikHewan_ID = ph.ID
            JOIN KategoriObat ko ON ro.KategoriObat_ID = ko.ID
            WHERE $whereClause
            ORDER BY ro.Nama ASC
            OFFSET :offset ROWS FETCH NEXT :limit ROWS ONLY";

    $db->query($sql);
    foreach ($params as $key => $value) {
        $db->bind($key, $value);
    }
    $db->bind(':offset', $offset);
    $db->bind(':limit', $limit);
    $obatList = $db->resultSet();

    // Get total count for pagination
    $sqlCount = "SELECT COUNT(*) AS total 
                 FROM ResepObat ro
                 JOIN LayananMedis lm ON ro.LayananMedis_ID = lm.ID
                 JOIN Hewan h ON lm.Hewan_ID = h.ID
                 JOIN PemilikHewan ph ON h.PemilikHewan_ID = ph.ID
                 JOIN KategoriObat ko ON ro.KategoriObat_ID = ko.ID
                 WHERE $whereClause";
                 
    $db->query($sqlCount);
    foreach ($params as $key => $value) {
        if ($key !== ':offset' && $key !== ':limit') {
            $db->bind($key, $value);
        }
    }
    $rowCount = $db->single();
    $totalPagesObat = ceil($rowCount['TOTAL'] / $limit);
}
if (isset($_GET['layanan_id'])) {
    $layanan_id = $_GET['layanan_id'];
    // Auto open drawer
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('update-medical-drawer').checked = true;
        });
    </script>";
}

if (isset($_GET['obat_id'])) {
    $obat_id = $_GET['obat_id'];
    // Auto open drawer
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('update-obat-drawer').checked = true;
        });
    </script>";
}
ob_end_flush();
?>

<body>
    <div class="pb-6 px-12 text-[#363636]">
        <!-- Header with Title and Alerts -->
        <div class="flex justify-between mb-6">
            <h2 class="text-3xl font-bold">Pet Service Management</h2>

            <?php if ($error): ?>
                <div role="alert" class="alert alert-error py-2 px-7 rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0 stroke-current" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span><?= $error ?></span>
                </div>
            <?php endif; ?>

            <?php if ($message): ?>
                <div role="alert" class="alert alert-success py-2 px-7 rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0 stroke-current" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span><?= $message ?></span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Tab Navigation -->
        <div role="tablist" class="tabs tabs-lifted">
            <!-- Medical Services Tab -->
            <input type="radio" name="my_tabs_2" role="tab" 
                   class="tab" 
                   aria-label="Medical Services"
                   <?= $tab === 'medical-services' ? 'checked' : '' ?>>
            <div role="tabpanel" class="tab-content bg-base-100 border-base-300 rounded-box p-6">
                <!-- Medical Services Content -->
                <div class="flex flex-col gap-4">
                    <!-- Filter Section -->
                    <div class="flex flex-wrap gap-4 items-end">
                        <form method="GET" action="dashboard.php" class="mb-4">
                            <input type="hidden" name="tab" value="<?= $tab ?>">
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div class="form-control">
                                    <label class="label font-semibold">Pet Name</label>
                                    <select name="nama_hewan" class="select2 select select-bordered w-full bg-white">
                                        <option value="">Select Pet Name</option>
                                        <?php foreach ($hewanOptions as $hewan): ?>
                                            <option value="<?= htmlentities($hewan['NAMA']) ?>" 
                                                    <?= $hewan['NAMA'] === $filterNamaHewan ? 'selected' : '' ?>>
                                                <?= htmlentities($hewan['NAMA']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-control">
                                    <label class="label font-semibold">Owner Name</label>
                                    <select name="nama_pemilik" class="select2 select select-bordered w-full bg-white">
                                        <option value="">Select Owner Name</option>
                                        <?php foreach ($pemilikOptions as $pemilik): ?>
                                            <option value="<?= htmlentities($pemilik['NAMA']) ?>" 
                                                    <?= $pemilik['NAMA'] === $filterNamaPemilik ? 'selected' : '' ?>>
                                                <?= htmlentities($pemilik['NAMA']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-control">
                                    <label class="label font-semibold">Service Status</label>
                                    <select name="status" class="select2 select select-bordered w-full bg-white">
                                        <option value="">All Status</option>
                                        <option value="Emergency" <?= $filterStatus === 'Emergency' ? 'selected' : '' ?>>Emergency</option>
                                        <option value="Scheduled" <?= $filterStatus === 'Scheduled' ? 'selected' : '' ?>>Scheduled</option>
                                        <option value="Finished" <?= $filterStatus === 'Finished' ? 'selected' : '' ?>>Finished</option>
                                        <option value="Canceled" <?= $filterStatus === 'Canceled' ? 'selected' : '' ?>>Canceled</option>
                                    </select>
                                </div>
                                <div class="form-control">
                                    <label class="label opacity-0">Filter</label>
                                    <button type="submit" class="btn bg-[#D4F0EA] hover:bg-[#D4F0EA] text-[#363636] border-[#363636] w-full">
                                        <i class="fas fa-filter"></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Medications Tab -->
            <input type="radio" name="my_tabs_2" role="tab" 
                   class="tab" 
                   aria-label="Medications"
                   <?= $tab === 'medications' ? 'checked' : '' ?>>
            <div role="tabpanel" class="tab-content bg-base-100 border-base-300 rounded-box p-6">
                <!-- Medications Content -->
                <div class="flex flex-col gap-4">
                    <!-- Filter Form -->
                    <form method="GET" action="dashboard.php" class="mb-4">
                        <input type="hidden" name="tab" value="medical-services">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="form-control">
                                <label class="label font-semibold">Pet Name</label>
                                <select name="nama_hewan" class="select2 select select-bordered w-full bg-white">
                                    <option value="">Select Pet Name</option>
                                    <?php foreach ($hewanOptions as $hewan): ?>
                                        <option value="<?= htmlentities($hewan['NAMA']) ?>" 
                                                <?= $hewan['NAMA'] === $filterNamaHewan ? 'selected' : '' ?>>
                                            <?= htmlentities($hewan['NAMA']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-control">
                                <label class="label font-semibold">Owner Name</label>
                                <select name="nama_pemilik" class="select2 select select-bordered w-full bg-white">
                                    <option value="">Select Owner Name</option>
                                    <?php foreach ($pemilikOptions as $pemilik): ?>
                                        <option value="<?= htmlentities($pemilik['NAMA']) ?>" 
                                                <?= $pemilik['NAMA'] === $filterNamaPemilik ? 'selected' : '' ?>>
                                            <?= htmlentities($pemilik['NAMA']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-control">
                                <label class="label font-semibold">Service Status</label>
                                <select name="status" class="select2 select select-bordered w-full bg-white">
                                    <option value="">All Status</option>
                                    <option value="Emergency" <?= $filterStatus === 'Emergency' ? 'selected' : '' ?>>Emergency</option>
                                    <option value="Scheduled" <?= $filterStatus === 'Scheduled' ? 'selected' : '' ?>>Scheduled</option>
                                    <option value="Finished" <?= $filterStatus === 'Finished' ? 'selected' : '' ?>>Finished</option>
                                    <option value="Canceled" <?= $filterStatus === 'Canceled' ? 'selected' : '' ?>>Canceled</option>
                                </select>
                            </div>
                            <div class="form-control">
                                <label class="label opacity-0">Filter</label>
                                <button type="submit" class="btn bg-[#D4F0EA] hover:bg-[#D4F0EA] text-[#363636] border-[#363636] w-full">
                                    <i class="fas fa-filter"></i>
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Medications Table -->
                    <?php if (empty($obatList)): ?>
                        <div class="alert alert-info">Tidak ada data obat untuk ditampilkan.</div>
                    <?php else: ?>
                        <div class="overflow-hidden border border-[#363636] rounded-xl shadow-md shadow-[#717171]">
                            <!-- Medications Table Content -->
                            <table class="table border-collapse w-full">
                            <thead>
                                    <tr class="bg-[#D4F0EA] text-[#363636] font-semibold">
                                        <th class="border-b border-[#363636]">No.</th>
                                        <th class="border-b border-[#363636]">Nama Obat</th>
                                        <th class="border-b border-[#363636]">Dosis</th>
                                        <th class="border-b border-[#363636]">Frekuensi</th>
                                        <th class="border-b border-[#363636]">Instruksi</th>
                                        <th class="border-b border-[#363636]">Tanggal Layanan</th>
                                        <th class="border-b border-[#363636]">Kategori Obat</th>
                                        <th class="border-b border-[#363636]">Nama Hewan</th>
                                        <th class="border-b border-[#363636]">Nama Pemilik</th>
                                        <th class="border-b border-[#363636]">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $nomor = $offset + 1; ?>
                                    <?php foreach ($obatList as $obat): ?>
                                        <tr class="border-b border-[#363636] hover:bg-gray-50">
                                            <td><?= $nomor++; ?></td>
                                            <td><?= htmlentities($obat['NAMA']); ?></td>
                                            <td><?= htmlentities($obat['DOSIS']); ?></td>
                                            <td><?= htmlentities($obat['FREKUENSI']); ?></td>
                                            <td><?= htmlentities($obat['INSTRUKSI']); ?></td>
                                            <td><?= formatTimestamp($obat['TANGGALLAYANAN']); ?></td>
                                            <td><?= htmlentities($obat['KATEGORIOBAT']); ?></td>
                                            <td><?= htmlentities($obat['NAMAHEWAN']); ?></td>
                                            <td><?= htmlentities($obat['NAMAPEMILIK']); ?></td>
                                            <td>
                                                <div class="flex gap-2 justify-center">
                                                    <?php if ($obat['STATUS'] === 'Finished' || $obat['STATUS'] === 'Canceled'): ?>
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
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div class="join flex justify-center mt-4">
            <?php 
            $totalPagesCount = $tab === 'medical-services' ? $totalPages : $totalPagesObat;
            $baseUrl = "?tab=$tab&page=";
            ?>
            <a class="join-item btn <?= ($page <= 1) ? 'btn-disabled' : '' ?>"
                       href="<?= $baseUrl . ($page - 1) ?>&nama_hewan=<?= urlencode($filterNamaHewan) ?>&nama_pemilik=<?= urlencode($filterNamaPemilik) ?>&status=<?= urlencode($filterStatus) ?>">«</a>
                    
                    <?php for ($i = 1; $i <= $totalPagesCount; $i++): ?>
                        <a class="join-item btn <?= ($page === $i) ? 'bg-[#D4F0EA]' : '' ?>"
                           href="<?= $baseUrl . $i ?>&nama_hewan=<?= urlencode($filterNamaHewan) ?>&nama_pemilik=<?= urlencode($filterNamaPemilik) ?>&status=<?= urlencode($filterStatus) ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    
                    <a class="join-item btn <?= ($page >= $totalPagesCount) ? 'btn-disabled' : '' ?>"
                    href="<?= $baseUrl . ($page + 1) ?>&nama_hewan=<?= urlencode($filterNamaHewan) ?>&nama_pemilik=<?= urlencode($filterNamaPemilik) ?>&status=<?= urlencode($filterStatus) ?>">»</a>
        </div>

<!-- Floating Add Button -->
<?php if ($tab === 'medical-services'): ?>
    <button class="bg-[#D4F0EA] w-14 h-14 flex justify-center items-center rounded-full fixed bottom-5 right-5 border border-[#363636] shadow-md shadow-[#717171]"
            onclick="document.getElementById('my-drawer').checked = true">
        <i class="fas fa-plus fa-lg"></i>
    </button>
<?php endif; ?>

        <!-- Drawer -->
        <div class="drawer drawer-end">
            <input id="my-drawer" type="checkbox" class="drawer-toggle" /> 
            <div class="drawer-side z-50">
                <label for="my-drawer" class="drawer-overlay"></label>
                <div class="p-4 w-[600px] min-h-full bg-base-200 text-base-content">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-bold text-lg">Tambah <?= $tab === 'medical-services' ? 'Layanan Medis' : 'Obat' ?></h3>
                        <label for="my-drawer" class="btn btn-sm btn-circle">✕</label>
                    </div>
                    <?php include 'add-medical-form.php'; ?>
                </div>
            </div>
        </div>
    </div>
    
<!-- Update Drawer -->
<div class="drawer drawer-end">
    <input id="update-medical-drawer" type="checkbox" class="drawer-toggle" /> 
    <div class="drawer-side z-50">
        <label for="update-medical-drawer" class="drawer-overlay"></label>
        <div class="p-4 w-[600px] min-h-full bg-base-200 text-base-content">
            <div id="update-form-content">
                <?php 
                if (isset($_GET['layanan_id'])) {
                    $_GET['id'] = $_GET['layanan_id'];
                    include 'update-medical-form.php';
                }
                ?>
            </div>
        </div>
    </div>
</div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
    <?php if (isset($_GET['message'])): ?>
        document.getElementById('my-drawer').checked = false;
    <?php endif; ?>
});

function deleteRecord(id, type) {
    if (confirm('Apakah Anda yakin ingin menghapus data ini?')) {
        const currentPage = <?= $page ?>;
        const namaHewan = '<?= urlencode($filterNamaHewan) ?>';
        const namaPemilik = '<?= urlencode($filterNamaPemilik) ?>';
        window.location.href = `delete-record.php?id=${id}&type=${type}&tab=<?= $tab ?>&page=${currentPage}&nama_hewan=${namaHewan}&nama_pemilik=${namaPemilik}`;
    }
}
function openUpdateDrawer(id) {
    // Add layanan_id to URL without redirecting
    const url = new URL(window.location.href);
    url.searchParams.set('layanan_id', id);
    window.history.pushState({}, '', url);
    
    // Show the drawer
    document.getElementById('update-medical-drawer').checked = true;
    
    // Load the form content with cache-busting parameter
    fetch(`update-medical-form.php?id=${id}&t=${Date.now()}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(html => {
            document.getElementById('update-form-content').innerHTML = html;
            // Initialize form after loading
            if (typeof initializeForm === 'function') {
                initializeForm();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('update-form-content').innerHTML = 
                `<div class="alert alert-error">Error loading form: ${error.message}</div>`;
        });
}
    </script>
    <?php 
    // Tutup koneksi
    if (isset($conn)) {
        oci_close($conn);
    }
    ?>
</body>
</html>

<style>
/* Style untuk tab yang active */
.tab[aria-checked="true"] {
    background-color: #8bae97 !important; /* Warna hijau */
    color: white !important;
    border-bottom-color: #8bae97 !important;
}

/* Style untuk tab yang inactive */
.tab:not([aria-checked="true"]) {
    background-color: #ffd700 !important; /* Warna kuning */
    color: black !important;
    border-bottom-color: #ffd700 !important;
}

/* Hover effect untuk tab */
.tab:hover {
    background-color: #a5c2b0 !important; /* Warna hijau yang lebih terang untuk hover */
    color: white !important;
}
</style>