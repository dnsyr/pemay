<?php
ob_start();
session_start();
if (!isset($_SESSION['username']) || $_SESSION['posisi'] !== 'vet') {
    header("Location: ../../auth/restricted.php");
    exit();
}

require_once '../../config/database.php';
require_once '../../layout/header-tailwind.php';

$pageTitle = 'Pet Service Management';
$db = new Database();

// Process POST request if adding new medical service
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    require_once 'add-medical-services.php';
}

// Get current tab and pagination parameters
$tab = $_GET['tab'] ?? 'medical-services';
$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Get filter parameters
$filterNamaHewan = trim($_GET['nama_hewan'] ?? '');
$filterNamaPemilik = trim($_GET['nama_pemilik'] ?? '');

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
    $whereConditions = ["lm.onDelete = 0"];
    $params = [];
    
    if ($filterNamaHewan) {
        $whereConditions[] = "h.Nama LIKE :nama_hewan";
        $params[':nama_hewan'] = "%$filterNamaHewan%";
    }
    
    if ($filterNamaPemilik) {
        $whereConditions[] = "ph.Nama LIKE :nama_pemilik";
        $params[':nama_pemilik'] = "%$filterNamaPemilik%";
    }
    
    $whereClause = implode(' AND ', $whereConditions);

    $sql = "SELECT lm.ID, lm.Tanggal, lm.TotalBiaya, lm.Description, lm.Status, 
                   h.Nama AS NamaHewan, h.Spesies, 
                   ph.Nama AS NamaPemilik, ph.NomorTelpon
            FROM LayananMedis lm
            JOIN Hewan h ON lm.Hewan_ID = h.ID
            JOIN PemilikHewan ph ON h.PemilikHewan_ID = ph.ID
            WHERE $whereClause
            ORDER BY lm.Tanggal DESC
            OFFSET :offset ROWS FETCH NEXT :limit ROWS ONLY";

    $db->query($sql);
    foreach ($params as $key => $value) {
        $db->bind($key, $value);
    }
    $db->bind(':offset', $offset);
    $db->bind(':limit', $limit);
    $layananMedis = $db->resultSet();

    // Get total count for pagination
    $sqlCount = "SELECT COUNT(*) AS total FROM LayananMedis lm
                 JOIN Hewan h ON lm.Hewan_ID = h.ID
                 JOIN PemilikHewan ph ON h.PemilikHewan_ID = ph.ID
                 WHERE $whereClause";
                 
    $db->query($sqlCount);
    foreach ($params as $key => $value) {
        if ($key !== ':offset' && $key !== ':limit') {
            $db->bind($key, $value);
        }
    }
    $rowCount = $db->single();
    $totalPages = ceil($rowCount['TOTAL'] / $limit);
}
// Data for Medications tab
if ($tab === 'medications') {
    $whereConditions = ["ro.onDelete = 0"];
    $params = [];
    
    $sql = "SELECT ro.ID, ro.Nama, ro.Dosis, ro.Frekuensi, ro.Instruksi, 
                   lm.Tanggal AS TanggalLayanan, 
                   ko.Nama AS KategoriObat, 
                   h.Nama AS NamaHewan
            FROM ResepObat ro
            JOIN LayananMedis lm ON ro.LayananMedis_ID = lm.ID
            JOIN Hewan h ON lm.Hewan_ID = h.ID
            JOIN KategoriObat ko ON ro.KategoriObat_ID = ko.ID
            WHERE $whereClause
            ORDER BY ro.Nama ASC
            OFFSET :offset ROWS FETCH NEXT :limit ROWS ONLY";

    $db->query($sql);
    $db->bind(':offset', $offset);
    $db->bind(':limit', $limit);
    $obatList = $db->resultSet();
}
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<body>
    <div class="pb-6 px-12 text-[#363636]">
        <div class="flex justify-between mb-6">
            <h2 class="text-3xl font-bold">Pet Service Management</h2>

            <!-- Alert Messages -->
            <?php if ($error): ?>
                <div role="alert" class="alert alert-error py-2 px-7 rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0 stroke-current" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span><?= $error ?></span>
                </div>
            <?php endif; ?>

            <?php if ($message): ?>
                <div role="alert" class="alert alert-success py-2 px-7 rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0 stroke-current" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span><?= $message ?></span>
                </div>
            <?php endif; ?>
        </div>

        <div role="tablist" class="tabs tabs-lifted">
            <input type="radio" name="my_tabs_2" role="tab" checked 
                   class="tab text-[#363636] text-base font-semibold [--tab-bg:#FCFCFC] [--tab-border-color:#363636]" 
                   aria-label="Medical Services" />
            <input type="radio" name="my_tabs_2" role="tab"
                   class="tab text-[#363636] text-base font-semibold [--tab-bg:#FCFCFC] [--tab-border-color:#363636]" 
                   aria-label="Medications" />
            
            <div role="tabpanel" class="tab-content bg-[#FCFCFC] border-base-300 rounded-box p-6">
                <p class="text-lg text-[#363636] font-semibold mb-4">Pet's Appointment</p>

                <!-- Search and Filter -->
                <div class="grid grid-cols-3 gap-4 mb-6">
                    <div class="relative">
                        <input type="text" placeholder="Search by item name..." 
                               class="input input-bordered w-full pr-10 bg-white" />
                        <button class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    <select class="select select-bordered bg-white">
                        <option value="">Filter by pet species</option>
                        <?php foreach ($hewanOptions as $hewan): ?>
                            <option value="<?= htmlentities($hewan['NAMA']) ?>">
                                <?= htmlentities($hewan['NAMA']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn text-[#363636]">
                        <i class="fas fa-filter"></i>
                    </button>
                </div>

                <!-- Table -->
                <div class="overflow-hidden border border-[#363636] rounded-xl shadow-md shadow-[#717171]">
                    <table class="table border-collapse w-full">
                        <thead>
                            <tr class="bg-[#D4F0EA] text-[#363636] font-semibold">
                                <th class="border-b border-[#363636] rounded-tl-xl">Salon</th>
                                <th class="border-b border-[#363636]">Date</th>
                                <th class="border-b border-[#363636]">Total Price</th>
                                <th class="border-b border-[#363636]">Status</th>
                                <th class="border-b border-[#363636]">Pet Name</th>
                                <th class="border-b border-[#363636] rounded-tr-xl"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($layananMedis)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">No appointments found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($layananMedis as $layanan): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="border-b border-[#363636]"><?= htmlentities($layanan['DESCRIPTION']); ?></td>
                                        <td class="border-b border-[#363636]"><?= htmlentities($layanan['TANGGAL']); ?></td>
                                        <td class="border-b border-[#363636]">Rp <?= number_format($layanan['TOTALBIAYA'], 0, ',', '.'); ?></td>
                                        <td class="border-b border-[#363636]">
                                            <span class="px-3 py-1 rounded-full text-sm <?= match ($layanan['STATUS']) {
                                                'Emergency' => 'bg-red-100 text-red-800',
                                                'Finished' => 'bg-green-100 text-green-800',
                                                'Scheduled' => 'bg-yellow-100 text-yellow-800',
                                                'Canceled' => 'bg-gray-100 text-gray-800',
                                                default => 'bg-blue-100 text-blue-800'
                                            } ?>">
                                                <?= htmlentities($layanan['STATUS']); ?>
                                            </span>
                                        </td>
                                        <td class="border-b border-[#363636]"><?= htmlentities($layanan['NAMAHEWAN']); ?></td>
                                        <td class="border-b border-[#363636]">
                                            <div class="flex gap-2 justify-center">
                                                <a href="update-medical-services.php?id=<?= urlencode($layanan['ID']); ?>" 
                                                   class="btn btn-sm bg-[#D4F0EA] hover:bg-[#D4F0EA] text-[#363636] border-none">
                                                   <i class="fas fa-edit"></i>
                                                </a>
                                                <button onclick="return confirm('Delete this record?')" 
                                                        class="btn btn-sm bg-red-100 hover:bg-red-200 text-red-800 border-none">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Floating Add Button -->
                <button class="bg-[#D4F0EA] w-14 h-14 flex justify-center items-center rounded-full fixed bottom-5 right-5 border border-[#363636] shadow-md shadow-[#717171]"
                        onclick="document.getElementById('my-drawer').checked = true">
                    <i class="fas fa-plus fa-lg"></i>
                </button>

                <!-- Drawer -->
                <div class="drawer drawer-end">
                    <input id="my-drawer" type="checkbox" class="drawer-toggle" /> 
                    <div class="drawer-side z-50">
                        <label for="my-drawer" class="drawer-overlay"></label>
                        <div class="menu p-4 w-96 min-h-full bg-base-200 text-base-content">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="font-bold text-lg">Add Medical Service</h3>
                                <label for="my-drawer" class="btn btn-sm btn-circle">✕</label>
                            </div>
                            <?php include 'add-medical-form.php'; ?>
                        </div>
                    </div>
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
    </script>
</body>
</html>