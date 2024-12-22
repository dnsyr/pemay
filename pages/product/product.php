<?php
session_start();
include '../../config/connection.php';

$pageTitle = 'Manage Product';
include '../../layout/header-tailwind.php';

// Check if the user is logged in
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

// Prepare search wildcard
$searchWildcard = '%' . $searchTerm . '%';

// Build main query with filters
$searchQuery = " WHERE 1=1";

// If a search term is provided, add it to the query
if ($searchTerm) {
    $searchQuery .= " AND UPPER(P.NAMA) LIKE UPPER(:searchTerm)";
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
    } elseif ($selectedCategoryType == 'obat') {
        $searchQuery .= " AND P.KategoriObat_ID = :category";
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
        " ORDER BY P.JUMLAH ASC" . // Sorting by lowest quantity first
        " OFFSET :offset ROWS FETCH NEXT :itemsPerPage ROWS ONLY";

$stid = oci_parse($conn, $sql);

// Bind parameters
if ($searchTerm) {
    oci_bind_by_name($stid, ":searchTerm", $searchWildcard);
}

if ($selectedCategory && $selectedCategoryType) {
    oci_bind_by_name($stid, ":category", $selectedCategory);
}

oci_bind_by_name($stid, ":offset", $offset, -1, SQLT_INT);
oci_bind_by_name($stid, ":itemsPerPage", $itemsPerPage, -1, SQLT_INT);

// Execute main query
oci_execute($stid);

$stocks = [];
while ($row = oci_fetch_assoc($stid)) {
    $stocks[] = $row;
}
oci_free_statement($stid);

// Fetch categories for filter based on category type
$categoriesProdukList = [];
$categoriesObatList = [];

// Fetch KategoriProduk
$categoryProdukQuery = "SELECT * FROM KategoriProduk ORDER BY Nama";
$categoryProdukStid = oci_parse($conn, $categoryProdukQuery);
oci_execute($categoryProdukStid);
while ($row = oci_fetch_assoc($categoryProdukStid)) {
    $categoriesProdukList[] = $row;
}
oci_free_statement($categoryProdukStid);

// Fetch KategoriObat
$categoryObatQuery = "SELECT * FROM KategoriObat ORDER BY Nama";
$categoryObatStid = oci_parse($conn, $categoryObatQuery);
oci_execute($categoryObatStid);
while ($row = oci_fetch_assoc($categoryObatStid)) {
    $categoriesObatList[] = $row;
}
oci_free_statement($categoryObatStid);

// Count total items for pagination
$totalSql = "SELECT COUNT(*) AS TOTAL 
             FROM Produk P
             LEFT JOIN KategoriProduk KP ON P.KategoriProduk_ID = KP.ID
             LEFT JOIN KategoriObat KO ON P.KategoriObat_ID = KO.ID" . $searchQuery;

$totalStid = oci_parse($conn, $totalSql);

if ($searchTerm) {
    oci_bind_by_name($totalStid, ":searchTerm", $searchWildcard);
}

if ($selectedCategory && $selectedCategoryType) {
    oci_bind_by_name($totalStid, ":category", $selectedCategory);
}

oci_execute($totalStid);
$totalRow = oci_fetch_assoc($totalStid);
$totalItems = $totalRow['TOTAL'];
oci_free_statement($totalStid);

// Handle delete request
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $sqlDelete = "DELETE FROM Produk WHERE ID = :id";
    $stidDelete = oci_parse($conn, $sqlDelete);
    oci_bind_by_name($stidDelete, ":id", $delete_id);

    if (oci_execute($stidDelete)) {
        echo "<script>alert('Stock item deleted successfully!'); window.location.href='product.php';</script>";
    } else {
        echo "<script>alert('Failed to delete stock item.');</script>";
    }
    oci_free_statement($stidDelete);
}

oci_close($conn);

$totalPages = ceil($totalItems / $itemsPerPage);
?>
<!DOCTYPE html>
<html lang="en">

<body>
    <div class="page-container">
        <h2>Stock Management</h2>
        <a href="./add-product.php" class="btn btn-add rounded-circle"><i class="fas fa-plus fa-xl"></i></a>

        <!-- Filter Form -->
        <form method="POST" class="row g-3 mb-4">
            <!-- Search Field -->
            <div class="col-md-3">
                <input type="text" class="form-control" name="search" placeholder="Search by item name..." value="<?php echo htmlentities($searchTerm); ?>">
            </div>

            <!-- Category Type Dropdown -->
            <div class="col-md-3">
                <select class="form-select" name="category_type">
                    <option disabled value="" <?php echo empty($selectedCategoryType) ? 'selected' : ''; ?>>-- Select Category Type --</option>
                    <option value="all" <?php echo ($selectedCategoryType) == 'all' ?'selected' : ''; ?>>All</option>
                    <option value="produk" <?php echo $selectedCategoryType == 'produk' ? 'selected' : ''; ?>>Produk</option>
                    <option value="obat" <?php echo $selectedCategoryType == 'obat' ? 'selected' : ''; ?>>Obat</option>
                </select>
            </div>

            <!-- Category Dropdown -->
            <div class="col-md-3">
                <select class="form-select" name="category" <?php echo empty($selectedCategoryType) ? 'disabled' : ''; ?>>
                    <option value="" <?php echo empty($selectedCategory) ? 'selected' : ''; ?>>-- Select Category --</option>
                    <?php
                    if ($selectedCategoryType == 'produk') {
                        if (!empty($categoriesProdukList)) {
                            foreach ($categoriesProdukList as $category) {
                                $selected = ($selectedCategory == $category['ID']) ? 'selected' : '';
                                echo '<option value="' . htmlspecialchars($category['ID']) . '" ' . $selected . '>' . htmlspecialchars($category['NAMA']) . '</option>';
                            }
                        } else {
                            echo '<option value="" disabled>No Produk Categories Available</option>';
                        }
                    } elseif ($selectedCategoryType == 'obat') {
                        if (!empty($categoriesObatList)) {
                            foreach ($categoriesObatList as $category) {
                                $selected = ($selectedCategory == $category['ID']) ? 'selected' : '';
                                echo '<option value="' . htmlspecialchars($category['ID']) . '" ' . $selected . '>' . htmlspecialchars($category['NAMA']) . '</option>';
                            }
                        } else {
                            echo '<option value="" disabled>No Obat Categories Available</option>';
                        }
                    }
                    ?>
                </select>
            </div>

            <!-- Filter Button -->
            <div class="col-md-3">
                <button class="btn btn-outline-secondary" type="submit">Filter</button>
                <a href="product.php" class="btn btn-outline-secondary">Reset Filter</a>
            </div>
        </form>

        <!-- Table -->
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Item Name</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Category</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($stocks)): ?>
                    <?php foreach ($stocks as $stock): ?>
                        <tr class="<?php echo ($stock['JUMLAH'] <= 10) ? 'table-warning' : ''; ?>">
                            <td><?php echo htmlentities($stock['NAMA']); ?></td>
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
                            <td>
                                <!-- Update Button -->
                                <a href="update-product.php?id=<?php echo htmlentities($stock['ID']); ?>" class="btn btn-warning btn-sm">Update</a>

                                <!-- Delete Button -->
                                <a href="product.php?delete_id=<?php echo htmlentities($stock['ID']); ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this item?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">Barang tidak ada.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination">
                    <?php 
                    // Determine the range of pages to display
                    $range = 2; // Number of pages to show on either side of the current page
                    $start = max(1, $page - $range);
                    $end = min($totalPages, $page + $range);

                    // Previous button
                    if ($page > 1) {
                        echo '<li class="page-item"><a class="page-link" href="?page=' . ($page - 1) . '">Previous</a></li>';
                    } else {
                        echo '<li class="page-item disabled"><span class="page-link">Previous</span></li>';
                    }

                    // Page numbers
                    for ($i = $start; $i <= $end; $i++) {
                        $active = ($i == $page) ? 'active' : '';
                        echo '<li class="page-item ' . $active . '"><a class="page-link" href="?page=' . $i . '">' . $i . '</a></li>';
                    }

                    // Next button
                    if ($page < $totalPages) {
                        echo '<li class="page-item"><a class="page-link" href="?page=' . ($page + 1) . '">Next</a></li>';
                    } else {
                        echo '<li class="page-item disabled"><span class="page-link">Next</span></li>';
                    }
                    ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</body>

</html>
