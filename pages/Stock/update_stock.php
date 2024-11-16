<?php
session_start();
include '../../config/connection.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Fetch product data from Produk table
    $sql = "SELECT * FROM Produk WHERE ID = :id";
    $stid = oci_parse($conn, $sql);
    oci_bind_by_name($stid, ":id", $id);
    oci_execute($stid);
    $item = oci_fetch_assoc($stid);
    oci_free_statement($stid);

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
    $pegawaiId = $_SESSION['pegawai_id'];  // Use the logged-in user ID

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

oci_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="shortcut icon" href="../../public/img/icon.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../../public/css/index.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light navbar-container">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">
            <img src="../../public/img/icon.png" alt="" width="30" height="30" class="d-inline-block align-text-top">
            <span class="navbar-title">Pemay</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link active" aria-current="page" href="/pemay/pages/owner/dashboard.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/pemay/pages/owner/users.php">Users</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/pemay/pages/Stock/stock.php">Stok</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/pemay/pages/Kategori/kategori.php">Kategori</a>
                </li>
            </ul>
        </div>
        <form action="../../auth/logout.php" method="post">
            <button class="btn btn-link text-dark text-decoration-none" type="submit">Logout</button>
        </form>
    </div>
</nav>

<div class="container mt-4">
    <h2>Update Stock Item</h2>
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

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
