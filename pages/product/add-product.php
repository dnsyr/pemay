<?php
session_start();
include '../../config/connection.php';
include '../owner/header.php';

$pageTitle = 'Add Product Item';

// Check if the user is logged in and the session is active
if (!isset($_SESSION['username']) || $_SESSION['posisi'] !== 'owner') {
    die("Access denied. Please log in as an owner.");
}

$pegawaiId = intval($_SESSION['employee_id']);

// Fetch available categories
$categoryQuery = "SELECT * FROM KategoriProduk ORDER BY Nama";
$categoryStid = oci_parse($conn, $categoryQuery);
oci_execute($categoryStid);

$categories = [];
while ($row = oci_fetch_assoc($categoryStid)) {
    $categories[] = $row;
}
oci_free_statement($categoryStid);

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $namaItem = $_POST['nama_item'];
    $jumlah = $_POST['jumlah'];
    $harga = $_POST['harga'];
    $kategori = $_POST['kategori'];

    // Insert new product item into the Produk table, automatically using Pegawai_ID from the session
    $sql = "INSERT INTO Produk (Nama, Jumlah, Harga, Pegawai_ID, KategoriProduk_ID) 
            VALUES (:namaItem, :jumlah, :harga, :pegawai_id, :kategori)";
    $stid = oci_parse($conn, $sql);

    oci_bind_by_name($stid, ":namaItem", $namaItem);
    oci_bind_by_name($stid, ":jumlah", $jumlah);
    oci_bind_by_name($stid, ":harga", $harga);
    oci_bind_by_name($stid, ":pegawai_id", $pegawaiId);  // Automatically using session Pegawai_ID
    oci_bind_by_name($stid, ":kategori", $kategori);

    if (oci_execute($stid)) {
        echo "<script>alert('product item added successfully!'); window.location.href='product.php';</script>";
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
                <label for="kategori" class="form-label">Category</label>
                <select class="form-select" id="kategori" name="kategori" required>
                    <option value="">-- Select Category --</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['ID']; ?>">
                            <?php echo htmlentities($category['NAMA']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <button type="submit" class="btn btn-primary">Add product Item</button>
                <a href="product.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

</body>

</html>