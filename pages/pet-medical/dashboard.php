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
function formatDateTime($dateTime) {
    return date('H:i:s', strtotime($dateTime));
}
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
            ORDER BY
                CASE 
                    WHEN lm.Status = 'Emergency' THEN 1
                    WHEN lm.Status = 'Scheduled' THEN 2
                    WHEN lm.Status = 'Finished' THEN 3
                    WHEN lm.Status = 'Canceled' THEN 4
                    ELSE 5
                END,
                lm.Tanggal DESC
            OFFSET :offset ROWS FETCH NEXT :limit ROWS ONLY";

    $db->query($sql);
    foreach ($params as $key => $value) {
        $db->bind($key, $value);
    }
    $db->bind(':offset', $offset);
    $db->bind(':limit', $limit);
    $layananMedis = $db->resultSet();

    // Get total count for pagination
    $sqlCount = "SELECT COUNT(*) AS total 
                 FROM LayananMedis lm
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

// Data for Obat tab
if ($tab === 'obat') {
    $whereConditions = ["ro.onDelete = 0"];
    $params = [];
    
    if ($filterNamaHewan) {
        $whereConditions[] = "h.Nama LIKE :nama_hewan";
        $params[':nama_hewan'] = "%$filterNamaHewan%";
        $whereConditions[] = "lm.Status = 'Finished'";
    }
    
    if ($filterNamaPemilik) {
        $whereConditions[] = "ph.Nama LIKE :nama_pemilik";
        $params[':nama_pemilik'] = "%$filterNamaPemilik%";
        $whereConditions[] = "lm.Status = 'Finished'";
    }
    
    $whereClause = implode(' AND ', $whereConditions);

    $sql = "SELECT ro.ID, ro.Nama, ro.Dosis, ro.Frekuensi, ro.Instruksi, 
                   lm.Tanggal AS TanggalLayanan, 
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

ob_end_flush();
?>

<body>
    <div class="pb-6 px-12 text-[#363636]">
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
    <a class="tab text-[#363636] text-base font-semibold [--tab-bg:#FCFCFC] [--tab-border-color:#363636] <?= $tab === 'medical-services' ? 'tab-active bg-[#D4F0EA]' : '' ?>" 
       href="?tab=medical-services">Medical Services</a>
    <a class="tab text-[#363636] text-base font-semibold [--tab-bg:#FCFCFC] [--tab-border-color:#363636] <?= $tab === 'obat' ? 'tab-active bg-[#D4F0EA]' : '' ?>" 
       href="?tab=obat">Medications</a>

    <div class="tab-content bg-[#FCFCFC] border-base-300 rounded-box p-6">
                <!-- Filter Form Common Styling -->
                <form method="GET" action="dashboard.php" class="mb-4">
                    <input type="hidden" name="tab" value="<?= $tab ?>">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="form-control">
                            <label class="label font-semibold">Nama Hewan</label>
                            <select name="nama_hewan" class="select select-bordered w-full bg-white">
                                <option value="">Pilih Nama Hewan</option>
                                <?php foreach ($hewanOptions as $hewan): ?>
                                    <option value="<?= htmlentities($hewan['NAMA']) ?>" 
                                            <?= $hewan['NAMA'] === $filterNamaHewan ? 'selected' : '' ?>>
                                        <?= htmlentities($hewan['NAMA']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-control">
                            <label class="label font-semibold">Nama Pemilik</label>
                            <select name="nama_pemilik" class="select select-bordered w-full bg-white">
                                <option value="">Pilih Nama Pemilik</option>
                                <?php foreach ($pemilikOptions as $pemilik): ?>
                                    <option value="<?= htmlentities($pemilik['NAMA']) ?>" 
                                            <?= $pemilik['NAMA'] === $filterNamaPemilik ? 'selected' : '' ?>>
                                        <?= htmlentities($pemilik['NAMA']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-control">
                            <label class="label opacity-0">Filter</label>
                            <button type="submit" class="btn bg-[#D4F0EA] hover:bg-[#D4F0EA] text-[#363636] border-[#363636] w-full">Filter</button>
                        </div>
                    </div>
                </form>

                <!-- Add Button & Drawer -->
                <div class="drawer drawer-end">
                    <input id="my-drawer" type="checkbox" class="drawer-toggle" /> 
                    <div class="drawer-content mb-4">
                        <label for="my-drawer" class="btn bg-[#D4F0EA] hover:bg-[#D4F0EA] text-[#363636] border-[#363636]">
                            <?= $tab === 'medical-services' ? 'Tambah Layanan Medis' : 'Tambah Obat' ?>
                        </label>
                    </div> 
                    
                    <div class="drawer-side z-50">
                        <label for="my-drawer" class="drawer-overlay"></label>
                        <div class="p-4 w-96 min-h-full bg-base-200 text-base-content">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="font-bold text-lg">Tambah <?= $tab === 'medical-services' ? 'Layanan Medis' : 'Obat' ?></h3>
                                <label for="my-drawer" class="btn btn-sm btn-circle">✕</label>
                            </div>
                            <?php include 'add-medical-form.php'; ?>
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                <?php if ($tab === 'medical-services'): ?>
                    <!-- Medical Services Table -->
                    <?php if (empty($layananMedis)): ?>
                        <div class="alert alert-info">Tidak ada data layanan medis untuk ditampilkan.</div>
                    <?php else: ?>
                        <div class="overflow-hidden border border-[#363636] rounded-xl shadow-md shadow-[#717171]">
                            <table class="table border-collapse w-full">
                                <thead>
                                    <tr class="bg-[#D4F0EA] text-[#363636] font-semibold">
                                        <th class="border-b border-[#363636]">No.</th>
                                        <th class="border-b border-[#363636]">Tanggal</th>
                                        <th class="border-b border-[#363636]">Total Biaya</th>
                                        <th class="border-b border-[#363636]">Deskripsi</th>
                                        <th class="border-b border-[#363636]">Status</th>
                                        <th class="border-b border-[#363636]">Nama Hewan</th>
                                        <th class="border-b border-[#363636]">Spesies</th>
                                        <th class="border-b border-[#363636]">Nama Pemilik</th>
                                        <th class="border-b border-[#363636]">No. Telepon</th>
                                        <th class="border-b border-[#363636]">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $nomor = $offset + 1; ?>
                                    <?php foreach ($layananMedis as $layanan): ?>
                                        <tr class="border-b border-[#363636] hover:bg-gray-50">
                                            <td><?= $nomor++; ?></td>
                                            <td><?= formatDateTime($layanan['TANGGAL']); ?></td>
                                            <td>Rp <?= number_format($layanan['TOTALBIAYA'], 0, ',', '.'); ?></td>
                                            <td><?= htmlentities($layanan['DESCRIPTION']); ?></td>
                                            <td>
                                                <span class="badge <?= match ($layanan['STATUS']) {
                                                    'Emergency' => 'badge-error',
                                                    'Finished' => 'badge-success',
                                                    'Scheduled' => 'badge-warning',
                                                    'Canceled' => 'badge-ghost',
                                                    default => 'badge-info'
                                                } ?>">
                                                    <?= htmlentities($layanan['STATUS']); ?>
                                                </span>
                                            </td>
                                            <td><?= htmlentities($layanan['NAMAHEWAN']); ?></td>
                                            <td><?= htmlentities($layanan['SPESIES']); ?></td>
                                            <td><?= htmlentities($layanan['NAMAPEMILIK']); ?></td>
                                            <td><?= htmlentities($layanan['NOMORTELPON']); ?></td>
                                            <td>
                                                <div class="flex gap-2 justify-center">
                                                    <?php if ($layanan['STATUS'] === 'Finished' || $layanan['STATUS'] === 'Canceled'): ?>
                                                        <button class="btn btn-sm bg-[#D4F0EA] hover:bg-[#D4F0EA] text-[#363636] border-none" 
                                                                onclick="alert('Cannot update this record.')">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <a href="update-medical-services.php?id=<?= urlencode($layanan['ID']); ?>" 
                                                           class="btn btn-sm bg-[#D4F0EA] hover:bg-[#D4F0EA] text-[#363636] border-none">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <button onclick="return confirm('Delete this record?')" 
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

                <?php else: ?>
                    <!-- Medications Table -->
                    <?php if (empty($obatList)): ?>
                        <div class="alert alert-info">Tidak ada data obat untuk ditampilkan.</div>
                    <?php else: ?>
                        <div class="overflow-hidden border border-[#363636] rounded-xl shadow-md shadow-[#717171]">
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
                                            <td><?= formatDateTime($obat['TANGGALLAYANAN']); ?></td>
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
                                                        <a href="update-obat.php?id=<?= urlencode($obat['ID']); ?>" 
                                                           class="btn btn-sm bg-[#D4F0EA] hover:bg-[#D4F0EA] text-[#363636] border-none">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <button onclick="return confirm('Delete this record?')" 
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
                <?php endif; ?>

                <!-- Pagination with consistent styling -->
                <div class="join flex justify-center mt-4">
                    <?php 
                    $totalPagesCount = $tab === 'medical-services' ? $totalPages : $totalPagesObat;
                    $baseUrl = "?tab=$tab&page=";
                    ?>
                    <a class="join-item btn <?= ($page <= 1) ? 'btn-disabled' : '' ?>"
                       href="<?= $baseUrl . ($page - 1) ?>&nama_hewan=<?= urlencode($filterNamaHewan) ?>&nama_pemilik=<?= urlencode($filterNamaPemilik) ?>">«</a>
                    
                    <?php for ($i = 1; $i <= $totalPagesCount; $i++): ?>
                        <a class="join-item btn <?= ($page === $i) ? 'bg-[#D4F0EA]' : '' ?>"
                           href="<?= $baseUrl . $i ?>&nama_hewan=<?= urlencode($filterNamaHewan) ?>&nama_pemilik=<?= urlencode($filterNamaPemilik) ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    
                    <a class="join-item btn <?= ($page >= $totalPagesCount) ? 'btn-disabled' : '' ?>"
                    href="<?= $baseUrl . ($page + 1) ?>&nama_hewan=<?= urlencode($filterNamaHewan) ?>&nama_pemilik=<?= urlencode($filterNamaPemilik) ?>">»</a>
                </div>
            </div>
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
                <div class="p-4 w-96 min-h-full bg-base-200 text-base-content">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-bold text-lg">Tambah <?= $tab === 'medical-services' ? 'Layanan Medis' : 'Obat' ?></h3>
                        <label for="my-drawer" class="btn btn-sm btn-circle">✕</label>
                    </div>
                    <?php include 'add-medical-form.php'; ?>
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