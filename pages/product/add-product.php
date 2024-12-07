<?php
session_start();
include '../../config/connection.php';

// Check if the user is logged in and the session is active
if (!isset($_SESSION['username']) || $_SESSION['posisi'] !== 'owner') {
    header("Location: ../../auth/restricted.php");
    // die("Access denied. Please log in as an owner.");
    exit();
}

$pageTitle = 'Add Product Item';
include '../../layout/header.php';

$pegawaiId = intval($_SESSION['employee_id']);

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

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $namaItem = $_POST['nama_item'];
    $jumlah = $_POST['jumlah'];
    $harga = $_POST['harga'];
    $kategori = $_POST['kategori'];
    $tipeKategori = $_POST['tipe_kategori']; // produk atau obat

    // Insert new product item into the correct table
    $table = $tipeKategori === 'produk' ? 'KategoriProduk' : 'KategoriObat';
    $sql = "INSERT INTO Produk (Nama, Jumlah, Harga, Pegawai_ID, {$table}_ID) 
            VALUES (:namaItem, :jumlah, :harga, :pegawai_id, :kategori)";
    $stid = oci_parse($conn, $sql);

    oci_bind_by_name($stid, ":namaItem", $namaItem);
    oci_bind_by_name($stid, ":jumlah", $jumlah);
    oci_bind_by_name($stid, ":harga", $harga);
    oci_bind_by_name($stid, ":pegawai_id", $pegawaiId);
    oci_bind_by_name($stid, ":kategori", $kategori);

    if (oci_execute($stid)) {
        echo "<script>alert('Product item added successfully!'); window.location.href='product.php';</script>";
    } else {
        echo "<script>alert('Failed to add product item.');</script>";
    }
    oci_free_statement($stid);
}

oci_close($conn);
?>
<!DOCTYPE html>
<html lang="en">

<body>
    <div class="container mt-4">
        <h2>Add Product Item</h2>
        <form action="add-product.php" method="post">
            <div class="mb-3">
                <label for="nama_item" class="form-label">Item Name</label>
                <input type="text" class="form-control" id="nama_item" name="nama_item" required>
            </div>
            <div class="mb-3">
                <label for="jumlah" class="form-label">Quantity</label>
                <input type="number" class="form-control" id="jumlah" name="jumlah" required>
            </div>
            <div class="mb-3">
                <label for="harga" class="form-label">Price</label>
                <input type="number" class="form-control" id="harga" name="harga" required>
            </div>
            <div class="mb-3">
                <label for="tipe_kategori" class="form-label">Category Type</label><br>
                <input type="radio" id="produk" name="tipe_kategori" value="produk" checked onclick="updateCategory()">
                <label for="produk">Produk</label>
                <input type="radio" id="obat" name="tipe_kategori" value="obat" onclick="updateCategory()">
                <label for="obat">Obat</label>
            </div>
            <div class="mb-3">
                <label for="kategori" class="form-label">Category</label>
                <select class="form-select" id="kategori" name="kategori" required>
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
            <div class="mb-3">
                <button type="submit" class="btn btn-primary">Add Product Item</button>
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
    </script>
</body>

</html>
