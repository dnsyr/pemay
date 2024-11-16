<?php
session_start();
include '../../config/connection.php';

// Fetch available categories
$categoryQuery = "SELECT * FROM KategoriProduk ORDER BY Nama";
$categoryStid = oci_parse($conn, $categoryQuery);
oci_execute($categoryStid);

$categories = [];
while ($row = oci_fetch_assoc($categoryStid)) {
    $categories[] = $row;
}
oci_free_statement($categoryStid);

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $Nama = $_POST['nama_item'];
    $jumlah = $_POST['jumlah'];
    $harga = $_POST['harga'];
    $kategori = $_POST['kategori'];

    // Insert new stock item
    $sql = "INSERT INTO PRODUK (Nama, Jumlah, Harga, KategoriProduk_ID) VALUES (:Nama, :jumlah, :harga, :kategori)";
    $stid = oci_parse($conn, $sql);
    
    oci_bind_by_name($stid, ":Nama", $Nama);
    oci_bind_by_name($stid, ":jumlah", $jumlah);
    oci_bind_by_name($stid, ":harga", $harga);
    oci_bind_by_name($stid, ":kategori", $kategori);

    if (oci_execute($stid)) {
        echo "<script>alert('Stock item added successfully!'); window.location.href='stock.php';</script>";
    } else {
        echo "<script>alert('Failed to add stock item.');</script>";
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
    <title>Add Stock Item</title>
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
        <h2>Add Stock Item</h2>
        <form action="add_stock.php" method="post">
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
                <button type="submit" class="btn btn-primary">Add Stock Item</button>
                <a href="stock.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>
