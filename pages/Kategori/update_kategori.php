<?php
include '../../config/connection.php';

$id = isset($_GET['id']) ? $_GET['id'] : null;

// Jika tidak ada ID yang diberikan, kembali ke halaman kategori
if (!$id) {
    header("Location: kategori.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];

    if ($action === 'update') {
        // Update nama kategori
        $namaKategori = trim($_POST['namaKategori']);
        $sql = "UPDATE KategoriProduk SET Nama = :nama WHERE ID = :id";
        $stid = oci_parse($conn, $sql);
        oci_bind_by_name($stid, ":nama", $namaKategori);
        oci_bind_by_name($stid, ":id", $id);

        if (oci_execute($stid)) {
            $message = "Kategori berhasil diperbarui.";
        } else {
            $message = "Gagal memperbarui kategori.";
        }
        oci_free_statement($stid);
        
        // Redirect ke kategori.php setelah update
        header("Location: kategori.php?message=" . urlencode($message));
        exit;
    }
}

// Ambil data kategori untuk diupdate
$sql = "SELECT * FROM KategoriProduk WHERE ID = :id";
$stid = oci_parse($conn, $sql);
oci_bind_by_name($stid, ":id", $id);
oci_execute($stid);
$row = oci_fetch_assoc($stid);
oci_free_statement($stid);

// Jika kategori tidak ditemukan, kembali ke halaman kategori
if (!$row) {
    header("Location: kategori.php?message=" . urlencode("Kategori tidak ditemukan."));
    exit;
}

// Check if category is used in another table (Produk)
$checkSql = "SELECT COUNT(*) AS total FROM Produk WHERE KategoriProduk_ID = :id";
$checkStid = oci_parse($conn, $checkSql);
oci_bind_by_name($checkStid, ":id", $id);
oci_execute($checkStid);
$checkRow = oci_fetch_assoc($checkStid);
oci_free_statement($checkStid);

// If the category is used in another table, show a message
if ($checkRow['TOTAL'] > 0) {
    $message = "Kategori tidak dapat dihapus karena masih digunakan oleh produk.";
    header("Location: kategori.php?message=" . urlencode($message));
    exit;
}

oci_close($conn);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Kategori</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <h2>Update Kategori</h2>
        <form method="POST" action="update_kategori.php?id=<?php echo htmlentities($id); ?>">
            <div class="mb-3">
                <label for="namaKategori" class="form-label">Nama Kategori</label>
                <input type="text" class="form-control" id="namaKategori" name="namaKategori" value="<?php echo htmlentities($row['NAMA']); ?>" required>
            </div>
            <button type="submit" name="action" value="update" class="btn btn-primary">Update</button>
            <a href="kategori.php" class="btn btn-secondary">Kembali</a>
        </form>
    </div>
</body>

</html>
