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

    // Validasi input
    if ($quantity < 0 || $price < 0) {
        echo "<script>alert('Quantity and Price must be at least 0.');</script>";
    } else {
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
}

oci_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
</head>
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
                <input type="number" class="form-control" id="jumlah" name="jumlah" min="0" value="<?php echo htmlentities($product['JUMLAH']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="harga" class="form-label">Price</label>
                <input type="number" class="form-control" id="harga" name="harga" min="0" value="<?php echo htmlentities($product['HARGA']); ?>" required>
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
                <select class="form-select select2" id="kategori" name="kategori" required>
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

    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
    function updateCategory() {
        const kategori = document.getElementById('kategori');
        const tipeKategori = document.querySelector('input[name="tipe_kategori"]:checked').value;

        // Menyembunyikan semua opsi kategori
        for (let option of kategori.options) {
            option.style.display = 'none';
        }

        // Menampilkan kategori sesuai dengan tipe yang dipilih
        const selectedCategoryClass = tipeKategori === 'produk' ? 'produk' : 'obat';
        for (let option of kategori.options) {
            if (option.classList.contains(selectedCategoryClass)) {
                option.style.display = 'block';
            }
        }

        // Reset pilihan kategori saat tipe kategori berubah
        kategori.value = '';  // Mengosongkan pilihan kategori
    }

    // Menjalankan updateCategory pada saat halaman dimuat
    window.onload = function () {
        updateCategory();  // Pastikan kategori yang sesuai muncul berdasarkan tipe yang ada
        const selectedOption = document.querySelector('#kategori option:checked');
        if (selectedOption) {
            selectedOption.style.display = 'block'; // Menampilkan opsi yang terpilih
        }
    };

    // Inisialisasi Select2 setelah DOM siap
    document.addEventListener('DOMContentLoaded', function () {
        $('.select2').select2({
            placeholder: '-- Select Category --',
            allowClear: true
        });
    });
</script>
</body>
</html>
