<?php
session_start();
include '../../config/connection.php';
include '../owner/header.php';

$pageTitle = 'Update Product';

// Ensure the user is logged in
if (!isset($_SESSION['employee_id'])) {
    header("Location: ../../login.php");
    exit();
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Fetch product data from Produk table
    $sql = "SELECT * FROM Produk WHERE ID = :id";
    $stid = oci_parse($conn, $sql);
    oci_bind_by_name($stid, ":id", $id);
    oci_execute($stid);
    $item = oci_fetch_assoc($stid);
    oci_free_statement($stid);

    if (!$item) {
        echo "<script>alert('Product not found!'); window.location.href='stock.php';</script>";
        exit();
    }

    // Fetch categories for the dropdown
    $categoryQuery = "SELECT * FROM KategoriProduk ORDER BY Nama";
    $categoryStid = oci_parse($conn, $categoryQuery);
    oci_execute($categoryStid);

    $categories = [];
    while ($row = oci_fetch_assoc($categoryStid)) {
        $categories[] = $row;
    }
    oci_free_statement($categoryStid);
} else {
    header("Location: stock.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {
    $namaItem = $_POST['nama_item'];
    $jumlah = $_POST['jumlah'];
    $harga = $_POST['harga'];
    $kategori = $_POST['kategori'];
    $pegawaiId = intval($_SESSION['employee_id']);

    // Ensure that input values are valid
    if ($jumlah <= 0 || $harga <= 0) {
        echo "<script>alert('Quantity and Price must be positive values!');</script>";
    } else {
        $sql = "UPDATE Produk 
                SET Nama = :namaItem, Jumlah = :jumlah, Harga = :harga, KategoriProduk_ID = :kategori, Pegawai_ID = :pegawaiId 
                WHERE ID = :id";
        $stid = oci_parse($conn, $sql);
        oci_bind_by_name($stid, ":namaItem", $namaItem);
        oci_bind_by_name($stid, ":jumlah", $jumlah);
        oci_bind_by_name($stid, ":harga", $harga);
        oci_bind_by_name($stid, ":kategori", $kategori);
        oci_bind_by_name($stid, ":pegawaiId", $pegawaiId);
        oci_bind_by_name($stid, ":id", $id);

        if (oci_execute($stid)) {
            echo "<script>alert('Stock item updated successfully!'); window.location.href='stock.php';</script>";
        } else {
            echo "<script>alert('Failed to update stock item.');</script>";
        }
        oci_free_statement($stid);
    }
}

oci_close($conn);
?>

<!DOCTYPE html>
<html lang="en">

<body>


    <div class="container mt-4">
        <h2>Update Product Item</h2>
        <form action="update_stock.php?id=<?php echo $id; ?>" method="post">
            <div class="mb-3">
                <label for="nama_item" class="form-label">Item Name</label>
                <input type="text" class="form-control" id="nama_item" name="nama_item" value="<?php echo htmlentities($item['NAMA']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="jumlah" class="form-label">Quantity</label>
                <input type="number" class="form-control" id="jumlah" name="jumlah" value="<?php echo htmlentities($item['JUMLAH']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="harga" class="form-label">Price</label>
                <input type="number" class="form-control" id="harga" name="harga" value="<?php echo htmlentities($item['HARGA']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="kategori" class="form-label">Category</label>
                <select class="form-select" id="kategori" name="kategori" required>
                    <option value="">-- Select Category --</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['ID']; ?>" <?php echo $item['KATEGORIPRODUK_ID'] == $category['ID'] ? 'selected' : ''; ?>>
                            <?php echo htmlentities($category['NAMA']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <button type="submit" name="update" class="btn btn-warning">Update Stock Item</button>
                <a href="stock.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</body>

</html>