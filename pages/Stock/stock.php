<?php
session_start();
include '../../config/connection.php';

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
oci_close($conn);

$totalPages = ceil($totalItems / $itemsPerPage);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Management</title>
    <link rel="shortcut icon" href="../../public/img/icon.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-light navbar-container">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <img src="../../public/img/icon.png" alt="" width="30" height="30" class="d-inline-block align-text-top">
                <span class="navbar-title">Pemay</span>
            </a>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="/pemay-master/pemay-master/pages/owner/dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/pemay-master/pemay-master/pages/owner/users.php">Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="stock.php">Stock</a>
                    </li>
                </ul>
            </div>
            <form action="../../auth/logout.php" method="post">
                <button class="btn btn-link text-dark text-decoration-none" type="submit">Logout</button>
            </form>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>Stock Management</h2>
        <a href="../Stock/add_stock.php" class="btn btn-primary mb-3">Add Stock Item</a>

        <!-- Filter Form -->
        <form method="POST" class="row g-3 mb-4">
            <div class="col-md-4">
                <input type="text" class="form-control" name="search" placeholder="Search by item name..." value="<?php echo htmlentities($searchTerm); ?>">
            </div>
            <div class="col-md-4">
                <select class="form-select" name="category">
                    <option value="">-- Filter by Category --</option>
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
                    <th>ID</th>
                    <th>Item Name</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Category</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stocks as $stock): ?>
                    <tr>
                        <td><?php echo htmlentities($stock['ID']); ?></td>
                        <td><?php echo htmlentities($stock['NAMA']); ?></td>
                        <td><?php echo htmlentities($stock['JUMLAH']); ?></td>
                        <td><?php echo 'Rp' . number_format($stock['HARGA'], 2, ',', '.'); ?></td>
                        <td><?php echo htmlentities($stock['KATEGORINAMA']); ?></td>
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

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>
