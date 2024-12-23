<?php
session_start();
include '../../config/database.php';

$pageTitle = 'Manage Product';
include '../../layout/header-tailwind.php';

// Initialize Database
$db = new Database();

// Pastikan pengguna telah login
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: ../../auth/login.php");
    exit();
}

// Pagination setup
$itemsPerPage = 5;
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
$searchQuery = " WHERE 1=1";
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

// Main SQL query with sorting by lowest quantity first
$sql = "SELECT P.*, 
               KP.Nama AS KATEGORIPRODUKNAMA, 
               KO.Nama AS KATEGORIOBATNAMA
        FROM Produk P
        LEFT JOIN KategoriProduk KP ON P.KategoriProduk_ID = KP.ID
        LEFT JOIN KategoriObat KO ON P.KategoriObat_ID = KO.ID" . 
        $searchQuery . 
        " ORDER BY P.JUMLAH ASC OFFSET :offset ROWS FETCH NEXT :itemsPerPage ROWS ONLY";

// Add pagination parameters
$params[':offset'] = $offset;
$params[':itemsPerPage'] = $itemsPerPage;

// Execute the query
$db->query($sql);
foreach ($params as $param => $value) {
    $db->bind($param, $value);
}
$stocks = $db->resultSet();

// Count total items for pagination
$totalSql = "SELECT COUNT(*) AS TOTAL 
             FROM Produk P
             LEFT JOIN KategoriProduk KP ON P.KategoriProduk_ID = KP.ID
             LEFT JOIN KategoriObat KO ON P.KategoriObat_ID = KO.ID" . $searchQuery;

$db->query($totalSql);
foreach ($params as $param => $value) {
    if ($param !== ':offset' && $param !== ':itemsPerPage') {
        $db->bind($param, $value);
    }
}
$totalRow = $db->single();
$totalItems = $totalRow['TOTAL'];
$totalPages = ceil($totalItems / $itemsPerPage);

// Ambil ID produk dari URL
$productId = $_GET['id'] ?? null;
if (!$productId) {
    die("Product ID not specified.");
}

// Ambil data produk dari database
$sql = "SELECT * FROM Produk WHERE ID = :id";
$db->query($sql);
$db->bind(':id', $productId);
$product = $db->single();

if (!$product) {
    die("Product not found.");
}

// Fetch available categories for Produk
$categoryProdukQuery = "SELECT * FROM KategoriProduk ORDER BY Nama";
$db->query($categoryProdukQuery);
$categoriesProduk = $db->resultSet();

// Fetch available categories for Obat
$categoryObatQuery = "SELECT * FROM KategoriObat ORDER BY Nama";
$db->query($categoryObatQuery);
$categoriesObat = $db->resultSet();

// Proses update jika ada data yang dikirim
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari form
    $name = $_POST['nama_item'];
    $quantity = $_POST['jumlah'];
    $price = $_POST['harga'];
    $category = $_POST['kategori'];
    $tipeKategori = $_POST['tipe_kategori']; // produk atau obat

    // Validasi input
    if ($quantity < 0 || $price < 0) {
        echo "<script>alert('Quantity and Price must be at least 0.');</script>";
    } else {
        // Update produk di database
        $table = $tipeKategori === 'produk' ? 'KategoriProduk' : 'KategoriObat';
        $updateSql = "UPDATE Produk SET NAMA = :name, JUMLAH = :quantity, HARGA = :price, {$table}_ID = :category WHERE ID = :id";
        $db->query($updateSql);
        $db->bind(':name', $name);
        $db->bind(':quantity', $quantity);
        $db->bind(':price', $price);
        $db->bind(':category', $category);
        $db->bind(':id', $productId);

        if ($db->execute()) {
            echo "<script>alert('Product updated successfully!'); window.location.href='product.php';</script>";
        } else {
            echo "<script>alert('Failed to update product.');</script>";
        }
    }
}
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
                    <input type="text" class="w-1/4 rounded-full bg-[#FCFCFC] border border-[#565656] text-[#565656] text-sm px-7 py-2" name="search" placeholder="Search by item name..." value="<?php echo htmlentities($searchTerm ?? ''); ?>">

                    <!-- Category Type Dropdown -->
                    <select class="w-1/4 rounded-full bg-[#FCFCFC] border border-[#565656] text-[#565656] text-sm px-7 py-2" name="category_type">
                        <option disabled value="" <?php echo empty($selectedCategoryType ?? '') ? 'selected' : ''; ?>>-- Select Category Type --</option>
                        <option value="all" <?php echo ($selectedCategoryType ?? '') == 'all' ?'selected' : ''; ?>>All</option>
                        <option value="produk" <?php echo ($selectedCategoryType ?? '') == 'produk' ? 'selected' : ''; ?>>Produk</option>
                        <option value="obat" <?php echo ($selectedCategoryType ?? '') == 'obat' ? 'selected' : ''; ?>>Obat</option>
                    </select>

                    <!-- Filter Button -->
                    <div class="flex gap-2">
                        <button class="btn bg-[#D4F0EA] text-[#363636] shadow-md shadow-[#565656] px-7 rounded-full hover:bg-[#565656] hover:text-[#FCFCFC]" type="submit">Filter</button>
                        <a href="product.php" class="btn bg-[#E0BAB2] text-[#363636] shadow-md shadow-[#565656] px-7 rounded-full hover:bg-[#565656] hover:text-[#FCFCFC]">Reset</a>
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
                                            <div class="flex gap-3 justify-center items-center">
                                                <!-- Update Button -->
                                                <button type="button" class="drawer-btn btn btn-warning btn-sm" onclick="handleUpdateBtn('<?php echo $stock['ID']; ?>')">
                                                    <i class="fas fa-edit"></i>
                                                </button>

                                                <!-- Delete Button -->
                                                <a href="product.php?delete_id=<?php echo htmlentities($stock['ID']); ?>" class="btn btn-error btn-sm" onclick="return confirm('Are you sure you want to delete this item?')">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </div>
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
            </div>
        </div>

        <!-- Update Product Drawer -->
        <div class="drawer drawer-end z-10">
            <input id="drawerUpdateProduct" type="checkbox" class="drawer-toggle" checked />
            <div class="drawer-side">
                <label for="drawerUpdateProduct" aria-label="close sidebar" class="drawer-overlay"></label>
                <div class="menu bg-[#FCFCFC] text-[#363636] min-h-screen w-96 flex flex-col justify-center px-8">
                    <h3 class="text-lg font-semibold mb-7">Update Product</h3>
                    <form action="update-product.php?id=<?php echo $productId; ?>" method="post" class="gap-5 flex flex-col">
                        <div>
                            <label for="nama_item">Item Name</label>
                            <input type="text" class="mt-1 w-full rounded-full bg-[#FCFCFC] border border-[#565656] text-[#565656] text-sm px-7 py-2" id="nama_item" name="nama_item" value="<?php echo htmlentities($product['NAMA']); ?>" required>
                        </div>
                        <div>
                            <label for="jumlah">Quantity</label>
                            <input type="number" class="mt-1 w-full rounded-full bg-[#FCFCFC] border border-[#565656] text-[#565656] text-sm px-7 py-2" id="jumlah" name="jumlah" min="0" value="<?php echo htmlentities($product['JUMLAH']); ?>" required>
                        </div>
                        <div>
                            <label for="harga">Price</label>
                            <input type="number" class="mt-1 w-full rounded-full bg-[#FCFCFC] border border-[#565656] text-[#565656] text-sm px-7 py-2" id="harga" name="harga" min="0" value="<?php echo htmlentities($product['HARGA']); ?>" required>
                        </div>
                        <div>
                            <label>Category Type</label><br>
                            <div class="mt-1 flex gap-4">
                                <div>
                                    <input type="radio" id="produk" name="tipe_kategori" value="produk" <?php echo $product['KATEGORIPRODUK_ID'] ? 'checked' : ''; ?> onclick="updateCategory()">
                                    <label for="produk">Produk</label>
                                </div>
                                <div>
                                    <input type="radio" id="obat" name="tipe_kategori" value="obat" <?php echo $product['KATEGORIOBAT_ID'] ? 'checked' : ''; ?> onclick="updateCategory()">
                                    <label for="obat">Obat</label>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label for="kategori">Category</label>
                            <select class="mt-1 w-full rounded-full bg-[#FCFCFC] border border-[#565656] text-[#565656] text-sm px-7 py-2 select2" id="kategori" name="kategori" required>
                                <option value="" disabled>-- Select Category --</option>
                                <?php foreach ($categoriesProduk as $category): ?>
                                    <option value="<?php echo $category['ID']; ?>" class="produk" <?php echo $product['KATEGORIPRODUK_ID'] == $category['ID'] ? 'selected' : ''; ?>>
                                        <?php echo htmlentities($category['NAMA']); ?>
                                    </option>
                                <?php endforeach; ?>
                                <?php foreach ($categoriesObat as $category): ?>
                                    <option value="<?php echo $category['ID']; ?>" class="obat" <?php echo $product['KATEGORIOBAT_ID'] == $category['ID'] ? 'selected' : ''; ?>>
                                        <?php echo htmlentities($category['NAMA']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="flex justify-end gap-5 mt-5">
                            <button type="submit" name="update" class="btn bg-[#B2B5E0] text-[#565656] shadow-md shadow-[#565656] px-3 rounded-full hover:bg-[#565656] hover:text-[#FCFCFC] flex items-center">
                                <i class="fas fa-save fa-md"></i> Update Product
                            </button>
                            <a href="product.php" class="btn bg-[#E0BAB2] text-[#565656] shadow-md shadow-[#565656] px-3 rounded-full hover:bg-[#565656] hover:text-[#FCFCFC]">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
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
                kategoriSelect.find('option').not('[value=""]').remove();

                // Tambahkan kembali opsi sesuai kategori yang dipilih
                if (tipeKategori === 'produk') {
                    <?php foreach ($categoriesProduk as $category): ?>
                        var selected = <?php echo $product['KATEGORIPRODUK_ID'] == $category['ID'] ? 'true' : 'false'; ?>;
                        kategoriSelect.append(new Option('<?php echo htmlentities($category['NAMA']); ?>', '<?php echo $category['ID']; ?>', selected, selected));
                    <?php endforeach; ?>
                } else {
                    <?php foreach ($categoriesObat as $category): ?>
                        var selected = <?php echo $product['KATEGORIOBAT_ID'] == $category['ID'] ? 'true' : 'false'; ?>;
                        kategoriSelect.append(new Option('<?php echo htmlentities($category['NAMA']); ?>', '<?php echo $category['ID']; ?>', selected, selected));
                    <?php endforeach; ?>
                }

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
    </script>
</body>
</html>
