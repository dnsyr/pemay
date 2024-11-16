<?php
session_start();
include '../../config/connection.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Fetch item data
    $sql = "SELECT * FROM Stock WHERE Id = :id";
    $stid = oci_parse($conn, $sql);
    oci_bind_by_name($stid, ":id", $id);
    oci_execute($stid);
    $item = oci_fetch_assoc($stid);
    oci_free_statement($stid);

    // Fetch available categories
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

    $sql = "UPDATE Stock 
            SET NamaItem = :namaItem, Jumlah = :jumlah, Harga = :harga, KategoriProduk_ID = :kategori 
            WHERE Id = :id";
    $stid = oci_parse($conn, $sql);
    oci_bind_by_name($stid, ":namaItem", $namaItem);
    oci_bind_by_name($stid, ":jumlah", $jumlah);
    oci_bind_by_name($stid, ":harga", $harga);
    oci_bind_by_name($stid, ":kategori", $kategori);
    oci_bind_by_name($stid, ":id", $id);

    if (oci_execute($stid)) {
        echo "<script>alert('Stock item updated successfully!'); window.location.href='stock.php';</script>";
    } else {
        echo "<script>alert('Failed to update stock item.');</script>";
    }
    oci_free_statement($stid);
}

oci_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Stock Item</title>
    <link rel="shortcut icon" href="../../public/img/icon.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light navbar-container">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Pemay</a>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>Update Stock Item</h2>
        <form action="update_stock.php?id=<?php echo $id; ?>" method="post">
            <div class="mb-3">
                <label for="nama_item" class="form-label">Item Name</label>
                <input type="text" class="form-control" id="nama_item" name="nama_item" value="<?php echo htmlentities($item['NAMAITEM']); ?>" required>
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
                        <option value="<?php echo $category['ID']; ?>" 
                                <?php echo $item['KATEGORIPRODUK_ID'] == $category['ID'] ? 'selected' : ''; ?>>
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

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
