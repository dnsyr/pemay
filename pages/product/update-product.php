<?php
session_start();
include '../../config/connection.php';

$pageTitle = 'Update Product Item';
include '../../layout/header.php';

// Pastikan pengguna telah login
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: ../../auth/login.php");
    exit();
}

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

// Fetch available categories for Produk
$categoryProdukQuery = "SELECT * FROM KategoriProduk ORDER BY Nama";
$categoryProdukStid = oci_parse($conn, $categoryProdukQuery);
oci_execute($categoryProdukStid);
$categoriesProduk = [];
while ($row = oci_fetch_assoc($categoryProdukStid)) {
    $categoriesProduk[] = $row;
}
oci_free_statement($categoryProdukStid);

// Fetch available categories for Obat
$categoryObatQuery = "SELECT * FROM KategoriObat ORDER BY Nama";
$categoryObatStid = oci_parse($conn, $categoryObatQuery);
oci_execute($categoryObatStid);
$categoriesObat = [];
while ($row = oci_fetch_assoc($categoryObatStid)) {
    $categoriesObat[] = $row;
}
oci_free_statement($categoryObatStid);

// Proses update jika ada data yang dikirim
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari form
    $name = $_POST['nama_item'];
    $quantity = $_POST['jumlah'];
    $price = $_POST['harga'];
    $category = $_POST['kategori'];
    $tipeKategori = $_POST['tipe_kategori']; // produk atau obat

    // Update produk di database
    $table = $tipeKategori === 'produk' ? 'KategoriProduk' : 'KategoriObat';
    $updateSql = "UPDATE Produk SET NAMA = :name, JUMLAH = :quantity, HARGA = :price, {$table}_ID = :category WHERE ID = :id";
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
                <label for="tipe_kategori" class="form-label">Category Type</label><br>
                <input type="radio" id="produk" name="tipe_kategori" value="produk" <?php echo $product['KATEGORIPRODUK_ID'] ? 'checked' : ''; ?> onclick="updateCategory()">
                <label for="produk">Produk</label>
                <input type="radio" id="obat" name="tipe_kategori" value="obat" <?php echo $product['KATEGORIOBAT_ID'] ? 'checked' : ''; ?> onclick="updateCategory()">
                <label for="obat">Obat</label>
            </div>
            <div class="mb-3">
                <label for="kategori" class="form-label">Category</label>
                <select class="form-select" id="kategori" name="kategori" required>
                    <option value="" disabled>-- Select Category --</option>
                    <?php foreach ($categoriesProduk as $category): ?>
                        <option value="<?php echo $category['ID']; ?>" class="produk" style="display: none;" <?php echo $product['KATEGORIPRODUK_ID'] == $category['ID'] ? 'selected' : ''; ?>>
                            <?php echo htmlentities($category['NAMA']); ?>
                        </option>
                    <?php endforeach; ?>
                    <?php foreach ($categoriesObat as $category): ?>
                        <option value="<?php echo $category['ID']; ?>" class="obat" style="display: none;" <?php echo $product['KATEGORIOBAT_ID'] == $category['ID'] ? 'selected' : ''; ?>>
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

    <script>
        function updateCategory() {
            const kategori = document.getElementById('kategori');
            const tipeKategori = document.querySelector('input[name="tipe_kategori"]:checked').value;
            for (let option of kategori.options) {
                if (option.classList.contains(tipeKategori)) {
                    option.style.display = 'block';
                } else {
                    option.style.display = 'none';
                }
            }
            // Reset selection when changing type
            kategori.value = '';
        }

        // Initialize category visibility on page load
        window.onload = updateCategory;
    </script>
</body>

</html>
