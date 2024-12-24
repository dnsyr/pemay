<?php
session_start();

// Check if the user is logged in and has permission
if (!isset($_SESSION['user_logged_in']) || !in_array($_SESSION['posisi'], ['owner', 'staff', 'vet'])) {
    header("Location: ../../auth/restricted.php");
    exit();
}

// Check user role and set permissions
$userRole = $_SESSION['posisi'] ?? '';
$canEdit = ($userRole === 'staff' || $userRole === 'owner');
$canView = ($userRole === 'vet');

if (!$canEdit && !$canView) {
    header("Location: ../../index.php");
    exit();
}

include '../../config/database.php';
$db = new Database();

// Verify employee_id exists
if (!isset($_SESSION['employee_id'])) {
    die("Invalid session: employee_id not found");
}

$pegawaiId = trim($_SESSION['employee_id']);

// Verify pegawaiId exists in database
$db->query("SELECT 1 FROM Pegawai WHERE ID = :pegawai_id AND onDelete = 0");
$db->bind(':pegawai_id', $pegawaiId);
$pegawaiExists = $db->single();
if (!$pegawaiExists) {
    die("Invalid employee ID");
}

$pageTitle = 'Manage Product';
include '../../layout/header-tailwind.php';

// Notification message based on role
$roleMessage = '';
if (!$canEdit) {
    $roleMessage = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
        <span class="block sm:inline">Only STAFFS and OWNER are allowed to create and edit list.</span>
    </div>';
}

// Pagination setup
$itemsPerPage = 6;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $itemsPerPage;

// Initialize filter variables
$searchTerm = '';
$selectedCategory = '';
$selectedCategoryType = '';

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and sanitize inputs
    $searchTerm = isset($_POST['search']) ? trim($_POST['search']) : '';
    $selectedCategory = isset($_POST['category']) ? $_POST['category'] : '';
    $selectedCategoryType = isset($_POST['category_type']) ? $_POST['category_type'] : '';

    // Ensure category_type is valid
    if (!in_array($selectedCategoryType, ['produk', 'obat'])) {
        $selectedCategoryType = '';
    }
}

// Build main query with filters
$searchQuery = " WHERE P.onDelete = 0";
$params = [];

// If a search term is provided, add it to the query
if ($searchTerm) {
    $searchQuery .= " AND UPPER(P.NAMA) LIKE UPPER(:searchTerm)";
    $params[':searchTerm'] = '%' . $searchTerm . '%';
}

// If category_type is selected, filter by type
if ($selectedCategoryType) {
    if ($selectedCategoryType == 'produk') {
        $searchQuery .= " AND P.KategoriProduk_ID IS NOT NULL";
    } elseif ($selectedCategoryType == 'obat') {
        $searchQuery .= " AND P.KategoriObat_ID IS NOT NULL";
    }
}

// If both category_type and category are selected, filter by category
if ($selectedCategory && $selectedCategoryType) {
    if ($selectedCategoryType == 'produk') {
        $searchQuery .= " AND P.KategoriProduk_ID = :category";
        $params[':category'] = $selectedCategory;
    } elseif ($selectedCategoryType == 'obat') {
        $searchQuery .= " AND P.KategoriObat_ID = :category";
        $params[':category'] = $selectedCategory;
    }
}

// Main SQL query with Oracle-style pagination
$sql = "
    SELECT * FROM (
        SELECT a.*, ROWNUM rnum FROM (
            SELECT 
                P.*, 
                KP.Nama AS KATEGORIPRODUKNAMA, 
                KO.Nama AS KATEGORIOBATNAMA
            FROM Produk P
            LEFT JOIN KategoriProduk KP ON P.KategoriProduk_ID = KP.ID AND KP.ONDELETE = 0
            LEFT JOIN KategoriObat KO ON P.KategoriObat_ID = KO.ID AND KO.ONDELETE = 0
            {$searchQuery}
            ORDER BY P.JUMLAH ASC
        ) a WHERE ROWNUM <= " . ($offset + $itemsPerPage) . "
    ) WHERE rnum > " . $offset;

// Execute the query
$db->query($sql);
foreach ($params as $param => $value) {
    $db->bind($param, $value);
}
$stocks = $db->resultSet();

// Fetch categories for filter
$categoriesProduk = [];
$db->query("SELECT * FROM KategoriProduk WHERE ONDELETE = 0 ORDER BY Nama");
$categoriesProduk = $db->resultSet();

// Fetch KategoriObat
$categoriesObat = [];
$db->query("SELECT * FROM KategoriObat WHERE ONDELETE = 0 ORDER BY Nama");
$categoriesObat = $db->resultSet();

// Count total items for pagination
$totalSql = "
    SELECT COUNT(*) AS TOTAL 
    FROM Produk P
    LEFT JOIN KategoriProduk KP ON P.KategoriProduk_ID = KP.ID AND KP.ONDELETE = 0
    LEFT JOIN KategoriObat KO ON P.KategoriObat_ID = KO.ID AND KO.ONDELETE = 0
    {$searchQuery}";

$db->query($totalSql);
foreach ($params as $param => $value) {
    $db->bind($param, $value);
}
$totalRow = $db->single();
$totalItems = $totalRow['TOTAL'];

// Handle delete request
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $sqlDelete = "DELETE FROM Produk WHERE ID = :id";
    $db->query($sqlDelete);
    $db->bind(':id', $delete_id);

    if ($db->execute()) {
        echo "<script>alert('Stock item deleted successfully!'); window.location.href='product.php';</script>";
    } else {
        echo "<script>alert('Failed to delete stock item.');</script>";
    }
}

// Process add product if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'add') {
    $namaItem = $_POST['nama_item'];
    $jumlah = $_POST['jumlah'];
    $harga = $_POST['harga'];
    $kategori = $_POST['kategori'];
    $tipeKategori = $_POST['tipe_kategori'];

    if ($jumlah < 0 || $harga < 0) {
        echo "<script>alert('Quantity and Price must be zero or positive!');</script>";
    } else {
        try {
            // Debug employee ID
            error_log("Employee ID being used: " . $_SESSION['employee_id']);

            // Verify employee exists first
            $db->query("SELECT ID FROM Pegawai WHERE ID = :pegawai_id AND onDelete = 0");
            $db->bind(':pegawai_id', $_SESSION['employee_id']);
            $pegawaiResult = $db->single();
            
            if (!$pegawaiResult) {
                throw new Exception("Invalid employee ID. Please try logging in again.");
            }

            // Begin transaction
            $db->beginTransaction();

            // Then verify category exists
            $table = $tipeKategori === 'produk' ? 'KategoriProduk' : 'KategoriObat';
            $db->query("SELECT ID FROM {$table} WHERE ID = :kategori_id AND onDelete = 0");
            $db->bind(':kategori_id', $kategori);
            $categoryResult = $db->single();
            
            if (!$categoryResult) {
                throw new Exception("Selected category not found");
            }

            // Generate new GUID for product
            $db->query("SELECT SYS_GUID() as NEW_ID FROM DUAL");
            $result = $db->single();
            $productId = $result['NEW_ID'];

            // Prepare insert SQL based on category type
            if ($tipeKategori === 'produk') {
                $insertSql = "INSERT INTO Produk (ID, Nama, Jumlah, Harga, Pegawai_ID, KategoriProduk_ID, onDelete) 
                             VALUES (:product_id, :namaItem, :jumlah, :harga, :pegawai_id, :kategori, 0)";
            } else {
                $insertSql = "INSERT INTO Produk (ID, Nama, Jumlah, Harga, Pegawai_ID, KategoriObat_ID, onDelete) 
                             VALUES (:product_id, :namaItem, :jumlah, :harga, :pegawai_id, :kategori, 0)";
            }

            // Execute product insertion
            $db->query($insertSql);
            $db->bind(':product_id', $productId);
            $db->bind(':namaItem', $namaItem);
            $db->bind(':jumlah', $jumlah);
            $db->bind(':harga', $harga);
            $db->bind(':pegawai_id', $_SESSION['employee_id']);
            $db->bind(':kategori', $kategori);

            if (!$db->execute()) {
                throw new Exception("Failed to insert product");
            }

            // If we get here, everything succeeded
            $db->commit();
            echo "<script>alert('Product item added successfully!'); window.location.href='product.php';</script>";
            exit();

        } catch (Exception $e) {
            // Rollback and show error
            $db->rollBack();
            $errorMessage = str_replace("'", "\\'", $e->getMessage());
            echo "<script>
                console.error('Error:', '" . $errorMessage . "');
                alert('Error: " . $errorMessage . "');
            </script>";
            error_log("Error adding product: " . $e->getMessage());
        }
    }
}

$totalPages = ceil($totalItems / $itemsPerPage);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</head>

<body>
    <div class="pb-6 px-12 text-[#363636]">
        <div class="flex justify-between mb-6">
            <h2 class="text-3xl font-bold">Stock Management</h2>
        </div>

        <!-- Tabs -->
        <div role="tablist" class="tabs tabs-lifted relative z-0 mb-6">
            <input type="radio" name="my_tabs_2" role="tab" checked class="tab text-[#363636] text-base font-semibold [--tab-bg:#D4F0EA] [--tab-border-color:#363636] h-10 px-8" aria-label="Product" />
            <div role="tabpanel" class="tab-content bg-[#FCFCFC] border-base-300 rounded-box p-6">
                <!-- Filter Form -->
                <form method="POST" class="flex gap-3 mb-4">
                    <!-- Search Field -->
                    <input type="text" class="w-1/4 rounded-full bg-[#FCFCFC] border border-[#565656] text-[#565656] text-sm px-7 py-2" name="search" placeholder="Search by item name..." value="<?php echo htmlentities($searchTerm); ?>">

                    <!-- Category Type Dropdown -->
                    <select class="w-1/4 rounded-full bg-[#FCFCFC] border border-[#565656] text-[#565656] text-sm px-7 py-2" name="category_type">
                        <option disabled value="" <?php echo empty($selectedCategoryType) ? 'selected' : ''; ?>>-- Select Category Type --</option>
                        <option value="all" <?php echo ($selectedCategoryType) == 'all' ?'selected' : ''; ?>>All</option>
                        <option value="produk" <?php echo $selectedCategoryType == 'produk' ? 'selected' : ''; ?>>Produk</option>
                        <option value="obat" <?php echo $selectedCategoryType == 'obat' ? 'selected' : ''; ?>>Obat</option>
                    </select>

            <!-- Category Dropdown -->
            <select class="w-1/4 rounded-full bg-[#FCFCFC] border border-[#565656] text-[#565656] text-sm px-7 py-2" name="category" <?php echo empty($selectedCategoryType) ? 'disabled' : ''; ?>>
                <option value="" <?php echo empty($selectedCategory) ? 'selected' : ''; ?>>-- Select Category --</option>
                <?php
                if ($selectedCategoryType == 'produk') {
                    if (!empty($categoriesProduk)) {
                        foreach ($categoriesProduk as $category) {
                            $selected = ($selectedCategory == $category['ID']) ? 'selected' : '';
                            echo '<option value="' . htmlspecialchars($category['ID']) . '" ' . $selected . '>' . htmlspecialchars($category['NAMA']) . '</option>';
                        }
                    } else {
                        echo '<option value="" disabled>No Produk Categories Available</option>';
                    }
                } elseif ($selectedCategoryType == 'obat') {
                    if (!empty($categoriesObat)) {
                        foreach ($categoriesObat as $category) {
                            $selected = ($selectedCategory == $category['ID']) ? 'selected' : '';
                            echo '<option value="' . htmlspecialchars($category['ID']) . '" ' . $selected . '>' . htmlspecialchars($category['NAMA']) . '</option>';
                        }
                    } else {
                        echo '<option value="" disabled>No Obat Categories Available</option>';
                    }
                }
                ?>
            </select>

            <!-- Filter Button -->
            <div class="flex gap-2">
                <button class="btn bg-[#D4F0EA] text-[#363636] shadow-md shadow-[#565656] w-12 h-12 rounded-full hover:bg-[#565656] hover:text-[#FCFCFC] flex items-center justify-center" type="submit">
                    <i class="fas fa-filter"></i>
                </button>
                <a href="product.php" class="btn bg-[#E0BAB2] text-[#363636] shadow-md shadow-[#565656] w-12 h-12 rounded-full hover:bg-[#565656] hover:text-[#FCFCFC] flex items-center justify-center">
                    <i class="fas fa-undo"></i>
                </a>
            </div>
        </form>

                <!-- Table -->
                <div class="overflow-hidden border border-[#363636] rounded-xl shadow-md shadow-[#717171] mt-3">
                    <table class="table border-collapse w-full">
                        <thead>
                            <tr class="bg-[#D4F0EA] text-[#363636] font-semibold">
                                <th class="rounded-tl-xl">Item Name</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Category</th>
                                <th class="rounded-tr-xl">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($stocks)): ?>
                                <?php foreach ($stocks as $index => $stock): ?>
                                    <tr class="text-[#363636] <?php echo ($stock['JUMLAH'] <= 10) ? 'bg-yellow-100' : ''; ?>">
                                        <td class="<?= $index === count($stocks) - 1 ? 'rounded-bl-xl' : '' ?>"><?php echo htmlentities($stock['NAMA']); ?></td>
                                        <td><?php echo htmlentities($stock['JUMLAH']); ?></td>
                                        <td><?php echo 'Rp' . number_format($stock['HARGA'], 2, ',', '.'); ?></td>
                                        <td>
                                            <?php 
                                                if ($stock['KATEGORIPRODUKNAMA']) {
                                                    echo htmlentities($stock['KATEGORIPRODUKNAMA']);
                                                } elseif ($stock['KATEGORIOBATNAMA']) {
                                                    echo htmlentities($stock['KATEGORIOBATNAMA']);
                                                } else {
                                                    echo 'N/A';
                                                }
                                            ?>
                                        </td>
                                        <td class="<?= $index === count($stocks) - 1 ? 'rounded-br-xl' : '' ?>">
                                            <?php if ($canEdit): ?>
                                            <div class="flex gap-3">
                                                <!-- Update Button -->
                                                <button type="button" class="drawer-btn btn btn-warning btn-sm" onclick="handleUpdateBtn('<?php echo $stock['ID']; ?>')">
                                                    <i class="fas fa-edit"></i>
                                                </button>

                                                <!-- Delete Button -->
                                                <a href="product.php?delete_id=<?php echo htmlentities($stock['ID']); ?>" class="btn btn-error btn-sm" onclick="return confirm('Are you sure you want to delete this item?')">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">Barang tidak ada.</td>
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
                                    <a href="?page=<?php echo ($page - 1); ?>" class="btn btn-sm bg-[#D4F0EA] text-[#363636] hover:bg-[#565656] hover:text-[#FCFCFC]">Previous</a>
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
                                    <a href="?page=<?php echo $i; ?>" class="btn btn-sm <?php echo ($i == $page) ? 'bg-[#565656] text-[#FCFCFC]' : 'bg-[#D4F0EA] text-[#363636] hover:bg-[#565656] hover:text-[#FCFCFC]'; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php 
                            // Next button
                            if ($page < $totalPages): ?>
                                <li>
                                    <a href="?page=<?php echo ($page + 1); ?>" class="btn btn-sm bg-[#D4F0EA] text-[#363636] hover:bg-[#565656] hover:text-[#FCFCFC]">Next</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>

            <input type="radio" name="my_tabs_2" role="tab" class="tab text-[#363636] text-base font-semibold [--tab-bg:#D4F0EA] [--tab-border-color:#363636] h-10 px-8" aria-label="Product Log" />
            <div role="tabpanel" class="tab-content bg-[#FCFCFC] border-base-300 rounded-box p-6">
                <!-- Filter Form for Log -->
                <form method="POST" class="flex gap-3 mb-4">
                    <input type="hidden" name="tab" value="log">
                    <!-- Date Range -->
                    <div class="flex gap-2 items-center">
                        <label class="text-sm">From:</label>
                        <input type="datetime-local" name="date_from" class="rounded-full bg-[#FCFCFC] border border-[#565656] text-[#565656] text-sm px-4 py-2" 
                               value="<?php echo isset($_POST['date_from']) ? date('Y-m-d\TH:i', strtotime($_POST['date_from'])) : date('Y-m-d\TH:i', strtotime('-1 month')); ?>">
                        
                        <label class="text-sm">To:</label>
                        <input type="datetime-local" name="date_to" class="rounded-full bg-[#FCFCFC] border border-[#565656] text-[#565656] text-sm px-4 py-2" 
                               value="<?php echo isset($_POST['date_to']) ? date('Y-m-d\TH:i', strtotime($_POST['date_to'])) : date('Y-m-d\TH:i'); ?>">
                    </div>

                    <!-- Filter & Print Buttons -->
                    <div class="flex gap-2">
                        <button type="submit" class="btn bg-[#D4F0EA] text-[#363636] shadow-md shadow-[#565656] px-7 rounded-full hover:bg-[#565656] hover:text-[#FCFCFC]">
                            <i class="fas fa-filter mr-2"></i>Filter
                        </button>
                        <button type="button" onclick="printProductLog()" class="btn bg-green-600 text-white shadow-md shadow-[#565656] px-7 rounded-full hover:bg-green-700">
                            <i class="fas fa-print mr-2"></i>Print
                        </button>
                        <a href="?tab=log" class="btn bg-[#E0BAB2] text-[#363636] shadow-md shadow-[#565656] px-7 rounded-full hover:bg-[#565656] hover:text-[#FCFCFC]">
                            <i class="fas fa-undo mr-2"></i>Reset
                        </a>
                    </div>
                </form>

                <!-- Log Table -->
                <div class="overflow-hidden border border-[#363636] rounded-xl shadow-md shadow-[#717171] mt-3">
                    <table class="table border-collapse w-full" id="logTable">
                        <thead>
                            <tr class="bg-[#D4F0EA] text-[#363636] font-semibold">
                                <th class="rounded-tl-xl">Date</th>
                                <th>Product Name</th>
                                <th>Initial Stock</th>
                                <th>Final Stock</th>
                                <th>Change</th>
                                <th>Description</th>
                                <th class="rounded-tr-xl">Staff</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Get log data with date filter
                            $logQuery = "
                                SELECT 
                                    TO_CHAR(L.TanggalPerubahan, 'DD-MON-YYYY HH24:MI:SS') as TanggalPerubahan,
                                    P.Nama as ProductName,
                                    L.StokAwal,
                                    L.StokAkhir,
                                    L.Perubahan,
                                    L.Keterangan,
                                    PG.Nama as StaffName
                                FROM LogProduk L
                                JOIN Produk P ON L.Produk_ID = P.ID
                                JOIN Pegawai PG ON L.Pegawai_ID = PG.ID
                                WHERE P.onDelete = 0
                            ";

                            if (isset($_POST['tab']) && $_POST['tab'] === 'log') {
                                if (!empty($_POST['date_from'])) {
                                    $logQuery .= " AND L.TanggalPerubahan >= TO_TIMESTAMP(:date_from, 'YYYY-MM-DD HH24:MI:SS')";
                                }
                                if (!empty($_POST['date_to'])) {
                                    $logQuery .= " AND L.TanggalPerubahan <= TO_TIMESTAMP(:date_to, 'YYYY-MM-DD HH24:MI:SS')";
                                }
                            }

                            $logQuery .= " ORDER BY L.TanggalPerubahan DESC";

                            $db->query($logQuery);
                            foreach ($params as $param => $value) {
                                $db->bind($param, $value);
                            }
                            $logs = $db->resultSet();

                            if (!empty($logs)):
                                foreach ($logs as $log):
                            ?>
                                <tr class="text-[#363636]">
                                    <td><?php echo $log['TANGGALPERUBAHAN']; ?></td>
                                    <td><?php echo htmlentities($log['PRODUCTNAME']); ?></td>
                                    <td><?php echo htmlentities($log['STOKAWAL']); ?></td>
                                    <td><?php echo htmlentities($log['STOKAKHIR']); ?></td>
                                    <td class="<?php echo $log['PERUBAHAN'] >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                        <?php echo ($log['PERUBAHAN'] >= 0 ? '+' : '') . $log['PERUBAHAN']; ?>
                                    </td>
                                    <td><?php echo htmlentities($log['KETERANGAN']); ?></td>
                                    <td><?php echo htmlentities($log['STAFFNAME']); ?></td>
                                </tr>
                            <?php
                                endforeach;
                            else:
                            ?>
                                <tr>
                                    <td colspan="7" class="text-center">No log data available.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Add Product Drawer -->
        <?php if ($canEdit): ?>
        <div class="drawer drawer-end z-10">
            <input id="drawerAddProduct" type="checkbox" class="drawer-toggle" />
            <div class="drawer-content">
                <label for="drawerAddProduct" class="drawer-button btn bg-[#D4F0EA] w-14 h-14 flex justify-center text-[#363636] hover:bg-[#363636] hover:text-[#D4F0EA] items-center rounded-full fixed bottom-5 right-5 border border-[#363636] shadow-md shadow-[#717171]">
                    <i class="fas fa-plus fa-lg"></i>
                </label>
            </div>
            <div class="drawer-side">
                <label for="drawerAddProduct" aria-label="close sidebar" class="drawer-overlay"></label>
                <div class="menu bg-[#FCFCFC] text-[#363636] min-h-screen w-96 flex flex-col justify-center px-8">
                    <h3 class="text-lg font-semibold mb-7">Add Product</h3>
                    <form method="post" class="gap-5 flex flex-col" id="addProductForm" onsubmit="return validateForm()">
                        <input type="hidden" name="action" value="add">
                        <div>
                            <label for="nama_item">Item Name</label>
                            <input type="text" class="mt-1 w-full rounded-full bg-[#FCFCFC] border border-[#565656] text-[#565656] text-sm px-7 py-2" id="nama_item" name="nama_item" required>
                        </div>
                        <div>
                            <label for="jumlah">Quantity</label>
                            <input type="number" class="mt-1 w-full rounded-full bg-[#FCFCFC] border border-[#565656] text-[#565656] text-sm px-7 py-2" id="jumlah" name="jumlah" min="0" required>
                        </div>
                        <div>
                            <label for="harga">Price</label>
                            <input type="number" class="mt-1 w-full rounded-full bg-[#FCFCFC] border border-[#565656] text-[#565656] text-sm px-7 py-2" id="harga" name="harga" min="0" required>
                        </div>
                        <div>
                            <label>Category Type</label><br>
                            <div class="mt-1 flex gap-4">
                                <div>
                                    <input type="radio" id="produk" name="tipe_kategori" value="produk" checked onclick="updateCategory()">
                                    <label for="produk">Produk</label>
                                </div>
                                <div>
                                    <input type="radio" id="obat" name="tipe_kategori" value="obat" onclick="updateCategory()">
                                    <label for="obat">Obat</label>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label for="kategori">Category</label>
                            <select class="mt-1 w-full rounded-full bg-[#FCFCFC] border border-[#565656] text-[#565656] text-sm px-7 py-2 select2" id="kategori" name="kategori" required>
                                <option value="" disabled selected>-- Select Category --</option>
                                <?php foreach ($categoriesProduk as $category): ?>
                                    <option value="<?php echo $category['ID']; ?>" class="produk">
                                        <?php echo htmlentities($category['NAMA']); ?>
                                    </option>
                                <?php endforeach; ?>
                                <?php foreach ($categoriesObat as $category): ?>
                                    <option value="<?php echo $category['ID']; ?>" class="obat" style="display: none;">
                                        <?php echo htmlentities($category['NAMA']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="flex justify-end gap-5 mt-5">
                            <button type="submit" class="btn bg-[#B2B5E0] text-[#565656] shadow-md shadow-[#565656] px-3 rounded-full hover:bg-[#565656] hover:text-[#FCFCFC] flex items-center">
                                <i class="fas fa-save fa-md"></i> Add Product
                            </button>
                            <label for="drawerAddProduct" class="btn bg-[#E0BAB2] text-[#565656] shadow-md shadow-[#565656] px-3 rounded-full hover:bg-[#565656] hover:text-[#FCFCFC]">Cancel</label>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Role-based notification -->
        <?php if (!$canEdit): ?>
        <div class="fixed bottom-5 right-5 bg-red-100 border border-red-400 text-red-700 px-6 py-3 rounded-full shadow-lg z-50 flex items-center gap-3" role="alert">
            <div class="w-3 h-3 bg-red-400 rounded-full"></div>
            <span class="block sm:inline">Only STAFFS and OWNER are allowed to create and edit list.</span>
        </div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Fungsi untuk mengupdate kategori
            window.updateCategory = function() {
                const kategori = document.getElementById('kategori');
                const tipeKategori = document.querySelector('input[name="tipe_kategori"]:checked').value;
                const kategoriSelect = $(kategori);

                // Reset nilai dropdown
                kategoriSelect.val(null).trigger('change');

                // Sembunyikan semua opsi terlebih dahulu
                kategoriSelect.find('option').not('[value=""]').hide();

                // Tampilkan opsi sesuai kategori yang dipilih
                kategoriSelect.find('option.' + tipeKategori).show();

                // Refresh Select2
                kategoriSelect.select2({
                    placeholder: '-- Select Category --',
                    allowClear: true,
                    dropdownParent: $('.drawer-side')
                });
            }

            // Inisialisasi Select2
            $('.select2').select2({
                placeholder: '-- Select Category --',
                allowClear: true,
                dropdownParent: $('.drawer-side')
            });

            // Event listener untuk tipe kategori
            document.querySelectorAll('input[name="tipe_kategori"]').forEach(function (radio) {
                radio.addEventListener('change', updateCategory);
            });

            // Panggil updateCategory saat halaman dimuat
            updateCategory();
        });

        // Fungsi validasi form
        function validateForm() {
            const namaItem = document.getElementById('nama_item').value;
            const jumlah = document.getElementById('jumlah').value;
            const harga = document.getElementById('harga').value;
            const kategori = document.getElementById('kategori').value;

            if (!namaItem || !jumlah || !harga || !kategori) {
                alert('Please fill in all required fields');
                return false;
            }

            if (jumlah < 0) {
                alert('Quantity must be zero or positive!');
                return false;
            }

            if (harga < 0) {
                alert('Price must be zero or positive!');
                return false;
            }

            return true;
        }

        // Debug form submission
        document.getElementById('addProductForm').addEventListener('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
                return false;
            }
            
            // Log form data untuk debugging
            const formData = new FormData(this);
            console.log('Form data:');
            for (let pair of formData.entries()) {
                console.log(pair[0] + ': ' + pair[1]);
            }
            return true;
        });

        // Fungsi untuk menangani klik tombol update
        function handleUpdateBtn(id) {
            window.location.href = 'update-product.php?id=' + id;
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Handle category type change
            const categoryTypeSelect = document.querySelector('select[name="category_type"]');
            const categorySelect = document.querySelector('select[name="category"]');

            categoryTypeSelect.addEventListener('change', function() {
                const selectedType = this.value;
                categorySelect.disabled = selectedType === 'all';

                // Clear current options
                categorySelect.innerHTML = '<option value="" selected>-- Select Category --</option>';

                if (selectedType === 'produk') {
                    <?php foreach ($categoriesProduk as $category): ?>
                        categorySelect.add(new Option('<?php echo htmlspecialchars($category['NAMA']); ?>', '<?php echo $category['ID']; ?>'));
                    <?php endforeach; ?>
                } else if (selectedType === 'obat') {
                    <?php foreach ($categoriesObat as $category): ?>
                        categorySelect.add(new Option('<?php echo htmlspecialchars($category['NAMA']); ?>', '<?php echo $category['ID']; ?>'));
                    <?php endforeach; ?>
                }
            });
        });

        function printProductLog() {
            const printWindow = window.open('', '_blank');
            const dateFrom = document.querySelector('input[name="date_from"]').value;
            const dateTo = document.querySelector('input[name="date_to"]').value;
            
            // Calculate totals while iterating through rows
            const productSummary = {};
            
            document.querySelectorAll('#logTable tbody tr').forEach(row => {
                if (row.cells.length > 1) { // Skip empty state row
                    const productName = row.cells[1].textContent.trim();
                    const change = parseFloat(row.cells[4].textContent.replace('+', ''));
                    
                    if (!productSummary[productName]) {
                        productSummary[productName] = {
                            pembelian: 0,
                            restock: 0
                        };
                    }
                    
                    // Negative change means pembelian, positive means restock
                    if (change < 0) {
                        productSummary[productName].pembelian += Math.abs(change);
                    } else {
                        productSummary[productName].restock += change;
                    }
                }
            });
            
            // Create the print content
            let printContent = `
                <html>
                <head>
                    <title>Product Log Report</title>
                    <style>
                        body { font-family: Arial, sans-serif; padding: 20px; }
                        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        th { background-color: #f2f2f2; }
                        .text-red { color: red; }
                        .text-green { color: green; }
                        .header { margin-bottom: 30px; }
                        .date-range { margin-bottom: 20px; color: #666; }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h1>Product Log Report</h1>
                        <div class="date-range">
                            Period: ${new Date(dateFrom).toLocaleString()} - ${new Date(dateTo).toLocaleString()}
                        </div>
                    </div>

                    <h2>Summary</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Total Pembelian (-)</th>
                                <th>Total Restock (+)</th>
                            </tr>
                        </thead>
                        <tbody>
                    `;
                    
                    // Add summary rows
                    Object.entries(productSummary).forEach(([product, totals]) => {
                        printContent += `
                            <tr>
                                <td>${product}</td>
                                <td class="text-red">${totals.pembelian}</td>
                                <td class="text-green">${totals.restock}</td>
                            </tr>
                        `;
                    });
                    
                    // Add the log table
                    printContent += `
                        </tbody>
                    </table>

                    <h2>Detailed Log</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Product Name</th>
                                <th>Initial Stock</th>
                                <th>Final Stock</th>
                                <th>Change</th>
                                <th>Description</th>
                                <th>Staff</th>
                            </tr>
                        </thead>
                        <tbody>
                    `;
                    
                    // Add log rows
                    document.querySelectorAll('#logTable tbody tr').forEach(row => {
                        if (row.cells.length > 1) {
                            printContent += '<tr>' + row.innerHTML + '</tr>';
                        }
                    });
                    
                    printContent += `
                        </tbody>
                    </table>
                </body>
                </html>
            `;
            
            // Write to the new window and print
            printWindow.document.write(printContent);
            printWindow.document.close();
            
            // Wait for images to load before printing
            printWindow.onload = function() {
                printWindow.print();
            };
        }
    </script>
</body>

</html>
