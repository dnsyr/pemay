<?php
session_start();
include '../../config/connection.php';

$pageTitle = 'Manage Product';
include '../../layout/header.php';

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: ../../auth/login.php");
    exit();
}

// Pagination setup
$itemsPerPage = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $itemsPerPage;

// Search term and category
$searchTerm = isset($_POST['search']) && !empty(trim($_POST['search'])) ? trim($_POST['search']) : '';
$searchWildcard = '%' . $searchTerm . '%';
$selectedCategory = isset($_POST['category']) ? $_POST['category'] : '';

// Build main query
$searchQuery = " WHERE 1=1";
if ($searchTerm) {
    $searchQuery .= " AND UPPER(P.NAMA) LIKE UPPER(:searchTerm)";
}
if ($selectedCategory) {
    $searchQuery .= " AND P.KategoriProduk_ID = :category";
}

$sql = "SELECT P.*, K.Nama AS KategoriNama 
        FROM Produk P 
        JOIN KategoriProduk K ON P.KategoriProduk_ID = K.ID" .
    $searchQuery .
    " OFFSET :offset ROWS FETCH NEXT :itemsPerPage ROWS ONLY";
$stid = oci_parse($conn, $sql);

// Bind search term if exists
if ($searchTerm) {
    oci_bind_by_name($stid, ":searchTerm", $searchWildcard);
}

// Bind category if selected
if ($selectedCategory) {
    oci_bind_by_name($stid, ":category", $selectedCategory);
}

// Bind pagination parameters
oci_bind_by_name($stid, ":offset", $offset, -1, SQLT_INT);
oci_bind_by_name($stid, ":itemsPerPage", $itemsPerPage, -1, SQLT_INT);

// Execute query
oci_execute($stid);

$stocks = [];
while ($row = oci_fetch_assoc($stid)) {
    $stocks[] = $row;
}
oci_free_statement($stid);

// Fetch categories for filter
$categoryQuery = "SELECT * FROM KategoriProduk ORDER BY Nama";
$categoryStid = oci_parse($conn, $categoryQuery);
oci_execute($categoryStid);

$categoriesList = [];
while ($categoryRow = oci_fetch_assoc($categoryStid)) {
    $categoriesList[] = $categoryRow;
}
oci_free_statement($categoryStid);

// Count total items for pagination
$totalSql = "SELECT COUNT(*) AS total 
             FROM Produk P 
             JOIN KategoriProduk K ON P.KategoriProduk_ID = K.ID" .
    $searchQuery;
$totalStid = oci_parse($conn, $totalSql);

if ($searchTerm) {
    oci_bind_by_name($totalStid, ":searchTerm", $searchWildcard);
}
if ($selectedCategory) {
    oci_bind_by_name($totalStid, ":category", $selectedCategory);
}

oci_execute($totalStid);
$totalRow = oci_fetch_assoc($totalStid);
$totalItems = $totalRow['TOTAL'];
oci_free_statement($totalStid);

// Handle delete request
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $sql = "DELETE FROM Produk WHERE ID = :id";
    $stid = oci_parse($conn, $sql);
    oci_bind_by_name($stid, ":id", $delete_id);

    if (oci_execute($stid)) {
        echo "<script>alert('Stock item deleted successfully!'); window.location.href='product.php';</script>";
    } else {
        echo "<script>alert('Failed to delete stock item.');</script>";
    }
    oci_free_statement($stid);
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
            <div class="col-md-4">
                <input type="text" class="form-control" name="search" placeholder="Search by item name..." value="<?php echo htmlentities($searchTerm); ?>">
            </div>
            <div class="col-md-4">
                <select class="form-select" name="category">
                    <option value="" selected disabled>-- Filter by Category --</option>
                    <?php foreach ($categoriesList as $category): ?>
                        <option value="<?php echo $category['ID']; ?>" <?php echo $selectedCategory == $category['ID'] ? 'selected' : ''; ?>>
                            <?php echo htmlentities($category['NAMA']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <button class="btn btn-outline-secondary" type="submit">Filter</button>
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
                <?php foreach ($stocks as $stock): ?>
                    <tr class="<?php echo $stock['JUMLAH'] <= 10 ? 'table-warning' : ''; ?>">
                        <td><?php echo htmlentities($stock['NAMA']); ?></td>
                        <td><?php echo htmlentities($stock['JUMLAH']); ?></td>
                        <td><?php echo 'Rp' . number_format($stock['HARGA'], 2, ',', '.'); ?></td>
                        <td><?php echo htmlentities($stock['KATEGORINAMA']); ?></td>
                        <td>
                            <!-- Update Button -->
                            <a href="update-product.php?id=<?php echo $stock['ID']; ?>" class="btn btn-warning btn-sm">Update</a>

                            <!-- Delete Button -->
                            <a href="product.php?delete_id=<?php echo $stock['ID']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this item?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <nav aria-label="Page navigation">
            <ul class="pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>

</body>

</html>