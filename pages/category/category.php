<?php
session_start();
include '../../config/database.php';

// Check user role and set permissions
$userRole = $_SESSION['posisi'] ?? '';
$canEdit = ($userRole === 'owner');
$canView = in_array($userRole, ['staff', 'vet']);

if (!$canEdit && !$canView) {
    header("Location: ../../index.php");
    exit();
}

$pageTitle = 'Manage Categories';
include '../../layout/header-tailwind.php';

// Initialize Database
$db = new Database();

$message = "";

// Get current tab
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'product';

// Setup tables array
$tables = [
    'product' => ['table' => 'KategoriProduk', 'label' => 'Product'],
    'medicine' => ['table' => 'KategoriObat', 'label' => 'Medicine'],
    'medical' => ['table' => 'JenisLayananMedis', 'label' => 'Medical Service'],
    'salon' => ['table' => 'JenisLayananSalon', 'label' => 'Salon Service']
];

$currentTable = $tables[$tab]['table'];
$currentLabel = $tables[$tab]['label'];

// Proses Tambah Kategori
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $namaKategori = trim($_POST['namaKategori']);
    $biaya = isset($_POST['biaya']) ? (int) $_POST['biaya'] : 30000;

    // Siapkan query berdasarkan tab yang aktif
    if ($tab === 'salon' || $tab === 'medical') {
        $sql = "INSERT INTO $currentTable (Nama, Biaya) VALUES (:nama, :biaya)";
        $db->query($sql);
        $db->bind(':nama', $namaKategori);
        $db->bind(':biaya', $biaya);
    } else {
        $sql = "INSERT INTO $currentTable (Nama) VALUES (:nama)";
        $db->query($sql);
        $db->bind(':nama', $namaKategori);
    }

    if ($db->execute()) {
        $message = "$currentLabel berhasil ditambahkan.";
    } else {
        $message = "Gagal menambahkan $currentLabel.";
    }
}

// Proses Hapus Kategori
if (isset($_GET['delete_id'])) {
    $deleteId = $_GET['delete_id'];
    $sql = "UPDATE $currentTable SET onDelete = 1 WHERE ID = :id";
    $db->query($sql);
    $db->bind(':id', $deleteId);

    if ($db->execute()) {
        $message = "$currentLabel berhasil dihapus.";
    } else {
        $message = "Gagal menghapus $currentLabel.";
    }
}

// Get sort parameter and setup pagination
$sort = $_GET['sort'] ?? 'asc';
$itemsPerPage = 6;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $itemsPerPage;

// Query untuk total items
$totalSql = "SELECT COUNT(*) as TOTAL FROM $currentTable WHERE ONDELETE = 0";
$db->query($totalSql);
$totalRow = $db->single();
$totalItems = $totalRow['TOTAL'];
$totalPages = ceil($totalItems / $itemsPerPage);

// Query untuk data dengan pagination dan sorting
$sql = "SELECT * FROM $currentTable WHERE ONDELETE = 0 
        ORDER BY NAMA " . ($sort === 'desc' ? 'DESC' : 'ASC') . " 
        OFFSET :offset ROWS FETCH NEXT :limit ROWS ONLY";
$db->query($sql);
$db->bind(':offset', $offset);
$db->bind(':limit', $itemsPerPage);
$categories = $db->resultSet();
?>

<!DOCTYPE html>
<html lang="en">
<body>
    <div class="pb-6 px-12 text-[#363636]">
        <div class="flex justify-between mb-6">
            <h2 class="text-3xl font-bold">Manage Categories</h2>
        </div>

        <!-- Tabs -->
        <div class="mb-6">
            <div class="inline-flex border-b border-[#363636]">
                <a href="?tab=product" class="tab h-10 min-h-[2.5rem] !outline-none <?php echo $tab === 'product' ? 'tab-active !bg-[#D4F0EA] !text-[#363636] !border-[#363636] !border-b-0' : 'text-[#363636] hover:text-[#363636]'; ?> text-base font-normal px-6">Product</a>
                <a href="?tab=medicine" class="tab h-10 min-h-[2.5rem] !outline-none <?php echo $tab === 'medicine' ? 'tab-active !bg-[#D4F0EA] !text-[#363636] !border-[#363636] !border-b-0' : 'text-[#363636] hover:text-[#363636]'; ?> text-base font-normal px-6">Medicine</a>
                <a href="?tab=medical" class="tab h-10 min-h-[2.5rem] !outline-none <?php echo $tab === 'medical' ? 'tab-active !bg-[#D4F0EA] !text-[#363636] !border-[#363636] !border-b-0' : 'text-[#363636] hover:text-[#363636]'; ?> text-base font-normal px-6">Medical Service</a>
                <a href="?tab=salon" class="tab h-10 min-h-[2.5rem] !outline-none <?php echo $tab === 'salon' ? 'tab-active !bg-[#D4F0EA] !text-[#363636] !border-[#363636] !border-b-0' : 'text-[#363636] hover:text-[#363636]'; ?> text-base font-normal px-6">Salon Service</a>
            </div>

            <!-- Tab Content -->
            <div class="bg-[#FCFCFC] border border-[#363636] rounded-b-xl p-6">
                <!-- Add Category Form -->
                <div class="bg-[#FCFCFC] border border-[#363636] rounded-xl p-6 mb-6 shadow-md shadow-[#717171]">
                    <h3 class="text-lg font-semibold mb-4"><?php echo $currentLabel; ?> Registration</h3>
                    <?php if ($canEdit): ?>
                    <form action="" method="post" class="flex gap-4 items-end">
                        <input type="hidden" name="action" value="add">
                        <div class="form-control flex-1">
                            <input type="text" name="namaKategori" class="input input-bordered rounded-full bg-[#F5F5F5] text-base" placeholder="<?php echo $currentLabel; ?> Name" required>
                        </div>
                        <?php if ($tab === 'salon' || $tab === 'medical'): ?>
                            <div class="form-control flex-1">
                                <input type="number" name="biaya" class="input input-bordered rounded-full bg-[#F5F5F5] text-base" placeholder="Price" required>
                            </div>
                        <?php endif; ?>
                        <button type="submit" class="btn bg-[#B2B5E0] text-[#363636] hover:bg-[#565656] hover:text-[#FCFCFC] rounded-full flex items-center gap-2">
                            <i class="fas fa-plus"></i> Add Item
                        </button>
                    </form>
                    <?php else: ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-6 py-3 rounded-full shadow-lg flex items-center gap-3">
                        <div class="w-3 h-3 bg-red-400 rounded-full"></div>
                        <span class="block sm:inline">Only OWNER can manage categories</span>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Listed Categories -->
                <div>
                    <h3 class="text-lg font-semibold mb-4">Listed <?php echo $currentLabel; ?></h3>
                    
                    <!-- Search and Filter -->
                    <div class="flex justify-between items-center mb-4">
                        <div class="form-control w-1/3">
                            <div class="relative">
                                <input type="text" id="searchInput" class="input input-bordered rounded-full bg-[#FCFCFC] pl-10 pr-4 w-full text-base" placeholder="Search by item name...">
                                <div class="absolute left-3 top-1/2 transform -translate-y-1/2">
                                    <i class="fas fa-search text-gray-400"></i>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="dropdown dropdown-end">
                                <label tabindex="0" class="btn m-1 bg-[#FCFCFC] text-[#363636] hover:bg-[#565656] hover:text-[#FCFCFC] rounded-full flex items-center gap-2">
                                    <i class="fas fa-sort-alpha-<?= $sort === 'desc' ? 'down' : 'up' ?>"></i> 
                                    <?= $sort === 'desc' ? 'Z to A' : 'A to Z' ?>
                                </label>
                                <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow bg-base-100 rounded-box w-52">
                                    <li>
                                        <a href="?tab=<?= $tab ?>&sort=asc" class="<?= $sort === 'asc' ? 'active' : '' ?>">
                                            <i class="fas fa-sort-alpha-up"></i> A to Z
                                        </a>
                                    </li>
                                    <li>
                                        <a href="?tab=<?= $tab ?>&sort=desc" class="<?= $sort === 'desc' ? 'active' : '' ?>">
                                            <i class="fas fa-sort-alpha-down"></i> Z to A
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Category Table -->
                    <div class="overflow-hidden border border-[#363636] rounded-xl shadow-md shadow-[#717171]">
                        <table class="table border-collapse w-full text-base">
                            <thead>
                                <tr class="bg-[#D4F0EA] text-[#363636] font-semibold">
                                    <th class="rounded-tl-xl px-6 py-3"><?php echo $currentLabel; ?> Name</th>
                                    <?php if ($tab === 'salon' || $tab === 'medical'): ?>
                                        <th class="px-6 py-3 text-center">Price</th>
                                    <?php endif; ?>
                                    <th class="rounded-tr-xl px-6 py-3 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($categories)): ?>
                                    <?php foreach ($categories as $index => $category): ?>
                                        <tr class="text-[#363636]">
                                            <td class="px-6 py-3 <?= $index === count($categories) - 1 ? 'rounded-bl-xl' : '' ?>">
                                                <?php echo htmlentities($category['NAMA']); ?>
                                            </td>
                                            <?php if ($tab === 'salon' || $tab === 'medical'): ?>
                                                <td class="px-6 py-3 text-center">Rp <?php echo number_format($category['BIAYA'], 0, ',', '.'); ?></td>
                                            <?php endif; ?>
                                            <td class="px-6 py-3 text-center <?= $index === count($categories) - 1 ? 'rounded-br-xl' : '' ?>">
                                                <div class="flex gap-3 justify-center items-center">
                                                    <?php if ($canEdit): ?>
                                                        <a href="update-category.php?id=<?php echo $category['ID']; ?>&tab=<?php echo $tab; ?>" 
                                                            class="btn btn-warning btn-sm">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="?tab=<?php echo $tab; ?>&delete_id=<?php echo $category['ID']; ?>" 
                                                            class="btn btn-error btn-sm" 
                                                            onclick="return confirm('Apakah Anda yakin ingin menghapus?')">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <div class="flex items-center gap-2">
                                                            <i class="fas fa-eye-slash text-gray-400"></i>
                                                            <span class="text-sm text-gray-400">View Only</span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="<?php echo ($tab === 'salon' || $tab === 'medical') ? '3' : '2'; ?>" class="px-6 py-3 text-center">
                                            Tidak ada data.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <nav aria-label="Page navigation" class="mt-4">
                            <ul class="flex justify-center gap-2">
                                <?php 
                                // Previous button
                                if ($page > 1): ?>
                                    <li>
                                        <a href="?tab=<?php echo $tab; ?>&sort=<?php echo $sort; ?>&page=<?php echo ($page - 1); ?>" class="btn btn-sm bg-[#D4F0EA] text-[#363636] hover:bg-[#565656] hover:text-[#FCFCFC]">Previous</a>
                                    </li>
                                <?php endif; ?>

                                <?php
                                // Determine the range of pages to display
                                $range = 2;
                                $start = max(1, $page - $range);
                                $end = min($totalPages, $page + $range);

                                // Page numbers
                                for ($i = $start; $i <= $end; $i++): ?>
                                    <li>
                                        <a href="?tab=<?php echo $tab; ?>&sort=<?php echo $sort; ?>&page=<?php echo $i; ?>" class="btn btn-sm <?php echo ($i == $page) ? 'bg-[#565656] text-[#FCFCFC]' : 'bg-[#D4F0EA] text-[#363636] hover:bg-[#565656] hover:text-[#FCFCFC]'; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>

                                <?php 
                                // Next button
                                if ($page < $totalPages): ?>
                                    <li>
                                        <a href="?tab=<?php echo $tab; ?>&sort=<?php echo $sort; ?>&page=<?php echo ($page + 1); ?>" class="btn btn-sm bg-[#D4F0EA] text-[#363636] hover:bg-[#565656] hover:text-[#FCFCFC]">Next</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <style>
        .tab-active {
            border-top-left-radius: 0.5rem;
            border-top-right-radius: 0.5rem;
            position: relative;
        }
        .tab-active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            right: 0;
            height: 1px;
            background-color: #FCFCFC;
            z-index: 1;
        }
        .tab {
            border-top-left-radius: 0.5rem;
            border-top-right-radius: 0.5rem;
            border: 1px solid transparent;
            margin-right: 0.25rem;
        }
        .tab:hover:not(.tab-active) {
            border: 1px solid #363636;
            border-bottom: 0;
            background: transparent;
        }
        .tab:last-child {
            margin-right: 0;
        }
    </style>

    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            let searchQuery = this.value.toLowerCase();
            let tableRows = document.querySelectorAll('tbody tr');
            
            tableRows.forEach(row => {
                let name = row.querySelector('td:first-child').textContent.toLowerCase();
                if (name.includes(searchQuery)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>
