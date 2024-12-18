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
function formatTimestamp($timestamp) {
    try {
        // Create a DateTime object from the input timestamp
        $dateTime = DateTime::createFromFormat('d-m-Y H:i:s', $timestamp);

        // Check if parsing was successful
        if ($dateTime) {
            // Format the date to the desired format
            return $dateTime->format('d M Y, h:i A');
        } else {
            // Handle invalid timestamp
            return "Invalid timestamp: $timestamp";
        }
    } catch (Exception $e) {
        // Handle exceptions
        return "Error formatting timestamp: " . $e->getMessage();
    }
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

    $sql = "SELECT lm.ID, 
    TO_CHAR(lm.Tanggal, 'DD-MM-YYYY HH24:MI:SS') as TANGGAL,
    lm.TotalBiaya, lm.Description, lm.Status, 
    h.Nama AS NamaHewan, h.Spesies, 
    h.ID as Hewan_ID,
    ph.Nama AS NamaPemilik, ph.NomorTelpon,
    (
        SELECT LISTAGG(COLUMN_VALUE, ',') WITHIN GROUP (ORDER BY COLUMN_VALUE)
        FROM TABLE(lm.JenisLayanan)
    ) as JenisLayananStr
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
            class="tab <?= $tab === 'medical-services' ? 'active-tab' : 'inactive-tab' ?>" 
            aria-label="Medical Services" 
            <?= $tab === 'medical-services' ? 'checked' : '' ?> 
            onclick="location.href='?tab=medical-services&nama_hewan=<?= urlencode($filterNamaHewan) ?>&nama_pemilik=<?= urlencode($filterNamaPemilik) ?>'"/>

            <div role="tabpanel" class="tab-content bg-[#FCFCFC] border-base-300 rounded-box p-6">
                <p class="text-lg text-[#363636] font-semibold mb-4">Medical Services</p>
                
                <!-- Filter Form -->
                <form method="GET" action="dashboard.php" class="mb-4">
    <input type="hidden" name="tab" value="medical-services">
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


                <!-- Medical Services Table -->
                <?php if ($tab === 'medical-services'): ?>
                    <?php if (empty($layananMedis)): ?>
                        <div class="alert alert-info">Tidak ada data layanan medis untuk ditampilkan.</div>
                    <?php else: ?>
                        <div class="overflow-hidden border border-[#363636] rounded-xl shadow-md shadow-[#717171]">
                            <!-- Medical Services Table Content -->
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
                        <td class="text-center"><?= $nomor++; ?></td>
                        <td class="text-center"><?= formatTimestamp($layanan['TANGGAL']); ?></td>
                        <td class="text-center">
    <?php 
    $totalBiaya = $layanan['TOTALBIAYA'];
    // Hapus kondisi pengecekan status Scheduled
    if ($totalBiaya === null) {
        echo "Rp 0";
    } else {
        echo "Rp " . number_format((float)$totalBiaya, 0, ',', '.');
    }
    ?>
</td>
                        <td><?= htmlentities($layanan['DESCRIPTION']); ?></td>
                        <td class="text-center">
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
                        <td class="text-center"><?= htmlentities($layanan['NAMAHEWAN']); ?></td>
                        <td class="text-center"><?= htmlentities($layanan['SPESIES']); ?></td>
                        <td class="text-center"><?= htmlentities($layanan['NAMAPEMILIK']); ?></td>
                        <td class="text-center"><?= htmlentities($layanan['NOMORTELPON']); ?></td>
                        <td>
                            <div class="flex gap-2 justify-center">
                            <?php if ($layanan['STATUS'] === 'Finished' || $layanan['STATUS'] === 'Canceled'): ?>
    <button class="btn btn-sm bg-[#D4F0EA] hover:bg-[#D4F0EA] text-[#363636] border-none" 
            onclick="alert('Cannot update this record.')">
        <i class="fas fa-edit"></i>
    </button>
<?php else: ?>
    <button class="btn btn-sm bg-[#D4F0EA] hover:bg-[#D4F0EA] text-[#363636] border-none"
        onclick="openUpdateDrawer('<?= $layanan['ID'] ?>', 
                               '<?= htmlspecialchars($layanan['STATUS']) ?>', 
                               '<?= htmlspecialchars($layanan['DESCRIPTION']) ?>', 
                               '<?= $layanan['TANGGAL'] ?>', 
                               <?= $layanan['TOTALBIAYA'] ?>)">
    <i class="fas fa-edit"></i>
</button>
<?php endif; ?>
                                <button onclick="deleteRecord('<?= $layanan['ID'] ?>', 'medical')" 
                                class="btn btn-sm bg-red-100 hover:bg-red-200 text-red-800 border-none">
                                <i class="fas fa-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php endif; ?>
</div>

            <!-- Obat Tab -->
            <input type="radio" name="my_tabs_2" role="tab" 
            class="tab <?= $tab === 'obat' ? 'active-tab' : 'inactive-tab' ?>" 
            aria-label="Obat" 
            <?= $tab === 'obat' ? 'checked' : '' ?> 
            onclick="location.href='?tab=obat&nama_hewan=<?= urlencode($filterNamaHewan) ?>&nama_pemilik=<?= urlencode($filterNamaPemilik) ?>'"/>

        <div role="tabpanel" class="tab-content bg-[#FCFCFC] border-base-300 rounded-box p-6">
            <p class="text-lg text-[#363636] font-semibold mb-4">Medications</p>
                
                <!-- Filter Form for Obat -->
                <form method="GET" action="dashboard.php" class="mb-4">
                    <input type="hidden" name="tab" value="obat">
                    <!--filter controls -->
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
                                                        <a href="update-obat.php?id=<?= urlencode($obat['ID']); ?>" 
                                                           class="btn btn-sm bg-[#D4F0EA] hover:bg-[#D4F0EA] text-[#363636] border-none">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
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

        <!-- Pagination -->
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
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-bold text-lg">Update Layanan Medis</h3>
                <label for="update-medical-drawer" class="btn btn-sm btn-circle">✕</label>
            </div>
            <?php 
            if (isset($_GET['layanan_id'])) {
                include 'update-medical-form.php';
            }
            ?>
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
function openUpdateDrawer(id, status, description, tanggal, totalBiaya) {
    // Cegah default action
    event.preventDefault();
    
    // Set layanan_id ke URL dan tambahkan parameter lain
    let url = `dashboard.php?layanan_id=${id}`;
    
    // Buka URL baru
    window.location.href = url;
}
    </script>
</body>
</html>