<?php
ob_start();
session_start();
include '../../config/connection.php';
include '../../layout/header.php';

$id = $_GET['id'] ?? null;
$tab = $_GET['tab'] ?? 'produk';

if (!$id) {
    header("Location: category.php?tab=" . $tab);
    exit;
}

$tables = [
    'produk' => ['table' => 'KategoriProduk', 'label' => 'Product'],
    'obat' => ['table' => 'KategoriObat', 'label' => 'Medicine'],
    'salon' => ['table' => 'JenisLayananSalon', 'label' => 'Salon Service'],
    'medis' => ['table' => 'JenisLayananMedis', 'label' => 'Medical Service'],
];

if (!array_key_exists($tab, $tables)) {
    $tab = 'produk';
}

$currentTable = $tables[$tab]['table'];
$currentLabel = $tables[$tab]['label'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $namaKategori = trim($_POST['namaKategori']);
    $biaya = isset($_POST['biaya']) ? (int) $_POST['biaya'] : null;

    $sql = "UPDATE $currentTable SET Nama = :nama" . ($tab === 'salon' || $tab === 'medis' ? ', Biaya = :biaya' : '') . " WHERE ID = :id";
    $stid = oci_parse($conn, $sql);
    oci_bind_by_name($stid, ":nama", $namaKategori);
    if ($tab === 'salon' || $tab === 'medis') {
        oci_bind_by_name($stid, ":biaya", $biaya);
    }
    oci_bind_by_name($stid, ":id", $id);

    if (oci_execute($stid)) {
        header("Location: category.php?tab=" . $tab);
        exit;
    } else {
        $message = "Gagal memperbarui $currentLabel.";
    }
    oci_free_statement($stid);
}

// Ambil data kategori untuk edit
$sql = "SELECT * FROM $currentTable WHERE ID = :id";
$stid = oci_parse($conn, $sql);
oci_bind_by_name($stid, ":id", $id);
oci_execute($stid);

$category = oci_fetch_assoc($stid);
oci_free_statement($stid);
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">

<body>
    <div class="page-container">
        <h2>Edit <?php echo $currentLabel; ?></h2>

        <?php if (isset($message)): ?>
            <div class="alert alert-danger">
                <?php echo htmlentities($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="update-category.php?id=<?php echo $id; ?>&tab=<?php echo $tab; ?>">
            <input type="hidden" name="action" value="update">
            <div class="mb-3">
                <label for="namaKategori" class="form-label"><?php echo $currentLabel; ?> Name</label>
                <input type="text" class="form-control" id="namaKategori" name="namaKategori" value="<?php echo htmlentities($category['NAMA']); ?>" required>
            </div>
            <?php if ($tab === 'salon' || $tab === 'medis'): ?>
                <div class="mb-3">
                    <label for="biaya" class="form-label">Price</label>
                    <input type="number" class="form-control" id="biaya" name="biaya" value="<?php echo htmlentities($category['BIAYA']); ?>" required>
                </div>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary">Update</button>
        </form>
    </div>
</body>

</html>
