<?php
session_start();
include '../../config/connection.php';

// Include role-specific headers
switch ($_SESSION['posisi']) {
    case 'owner':
        include '../owner/header.php';
        break;
    case 'vet':
        include '../vet/header.php';
        break;
    case 'staff':
        include '../staff/header.php';
        break;
}

// Pastikan pengguna telah login
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: ../../auth/login.php");
    exit();
}

$pageTitle = 'Update Product Item';

// Ambil ID produk dari URL
$productId = $_GET['id'] ?? null;
if (!$productId) {
    die("Product ID not specified.");
}

// Ambil data produk dari database
$sql = "SELECT * FROM Produk WHERE ID = :id";
$stid = oci_parse($conn, $sql);
oci_bind_by_name($stid, ":id", $productId);
oci_execute($stid);
$product = oci_fetch_assoc($stid);
oci_free_statement($stid);

if (!$product) {
    die("Product not found.");
}

// Proses update jika ada data yang dikirim
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari form
    $name = $_POST['nama_item'];
    $quantity = $_POST['jumlah'];
    $price = $_POST['harga'];
    $category = $_POST['kategori'];

    // Update produk di database
    $updateSql = "UPDATE Produk SET NAMA = :name, JUMLAH = :quantity, HARGA = :price, KategoriProduk_ID = :category WHERE ID = :id";
    $updateStid = oci_parse($conn, $updateSql);
    oci_bind_by_name($updateStid, ":name", $name);
    oci_bind_by_name($updateStid, ":quantity", $quantity);
    oci_bind_by_name($updateStid, ":price", $price);
    oci_bind_by_name($updateStid, ":category", $category);
    oci_bind_by_name($updateStid, ":id", $productId);

    if (oci_execute($updateStid)) {
        echo "<script>alert('Product updated successfully!'); window.location.href='product.php';</script>";
    } else {
        echo "<script>alert('Failed to update product.');</script>";
    }
    oci_free_statement($updateStid);
}
// Fetch categories for the dropdown
$categoryQuery = "SELECT * FROM KategoriProduk ORDER BY Nama";
$categoryStid = oci_parse($conn, $categoryQuery);
oci_execute($categoryStid);

$categoriesList = [];
while ($categoryRow = oci_fetch_assoc($categoryStid)) {
    $categoriesList[] = $categoryRow;
}
oci_free_statement($categoryStid);
oci_close($conn);
?>

<!DOCTYPE html>
<html lang="en">

<body>
    <div class="container mt-4">
        <h2>Update Product Item</h2>
        <form action="update-product.php?id=<?php echo $productId; ?>" method="post">
            <div class="mb-3">
                <label for="nama_item" class="form-label">Item Name</label>
                <input type="text" class="form-control" id="nama_item" name="nama_item" value="<?php echo htmlentities($product['NAMA']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="jumlah" class="form-label">Quantity</label>
                <input type="number" class="form-control" id="jumlah" name="jumlah" value="<?php echo htmlentities($product['JUMLAH']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="harga" class="form-label">Price</label>
                <input type="number" class="form-control" id="harga" name="harga" value="<?php echo htmlentities($product['HARGA']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="kategori" class="form-label">Category</label>
                <select class="form-select" id="kategori" name="kategori" required>
                    <option value="">-- Select Category --</option>
                    <?php foreach ($categoriesList as $category): ?>
                        <option value="<?php echo $category['ID']; ?>" <?php echo $product['KATEGORIPRODUK_ID'] == $category['ID'] ? 'selected' : ''; ?>>
                            <?php echo htmlentities($category['NAMA']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <button type="submit" name="update" class="btn btn-warning">Update Stock Item</button>
                <a href="product.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/ 4.5.2/js/bootstrap.min.js"></script>
</body>

</html>