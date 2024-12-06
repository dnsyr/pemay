<?php
include '../../config/connection.php';
include '../../layout/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $namaKategori = trim($_POST['namaKategori']);

    // Query untuk menambahkan kategori
    $sql = "INSERT INTO KategoriProduk (Nama) VALUES (:nama)";
    $stid = oci_parse($conn, $sql);
    oci_bind_by_name($stid, ":nama", $namaKategori);

    if (oci_execute($stid)) {
        $message = "Kategori berhasil ditambahkan.";
    } else {
        $message = "Gagal menambahkan kategori.";
    }

    oci_free_statement($stid);
    oci_close($conn);

    // Redirect ke halaman kategori dengan pesan
    header("Location: kategori.php?message=" . urlencode($message));
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<body>
    <div class="container mt-5">
        <h2>Tambah Kategori</h2>
        <form method="POST" action="add_kategori.php">
            <div class="mb-3">
                <label for="namaKategori" class="form-label">Nama Kategori</label>
                <input type="text" class="form-control" id="namaKategori" name="namaKategori" required>
            </div>
            <button type="submit" class="btn btn-primary">Tambah</button>
            <a href="kategori.php" class="btn btn-secondary">Kembali</a>
        </form>
    </div>
</body>

</html>