<?php
session_start();
include '../../config/connection.php';

// Include role-specific headers
switch ($_SESSION['posisi']) {
    case 'owner':
        include '../owner/header.php';
        break;
    case 'vet':
        include '../vet/header.php';
        break;
    case 'staff':
        include '../staff/header.php';
        break;
}

$id = $_GET['id'] ?? null;
$tab = $_GET['tab'] ?? 'produk';

// If there's no ID, redirect to the category list page
if (!$id) {
    header("Location: category.php?tab=" . $tab);
    exit;
}

// Determine the category table and label based on the 'tab' parameter
$tables = [
    'produk' => ['table' => 'KategoriProduk', 'label' => 'Kategori Produk'],
    'obat' => ['table' => 'KategoriObat', 'label' => 'Kategori Obat'],
    'salon' => ['table' => 'JenisLayananSalon', 'label' => 'Jenis Layanan Salon'],
    'medis' => ['table' => 'JenisLayananMedis', 'label' => 'Jenis Layanan Medis'],
];

// Check if the tab exists, else default to 'produk'
if (!array_key_exists($tab, $tables)) {
    $tab = 'produk';
}

$currentTable = $tables[$tab]['table'];
$currentLabel = $tables[$tab]['label'];

// Process the form when submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];

    if ($action === 'update') {
        $namaKategori = trim($_POST['namaKategori']);

        // Check if the category is used elsewhere (e.g., in Produk or other relevant tables)
        $checkSql = "SELECT COUNT(*) AS total FROM Produk WHERE {$currentTable}_ID = :id";
        $checkStid = oci_parse($conn, $checkSql);
        oci_bind_by_name($checkStid, ":id", $id);
        oci_execute($checkStid);
        $checkRow = oci_fetch_assoc($checkStid);
        oci_free_statement($checkStid);

        // If category is being used, show an alert
        if ($checkRow['TOTAL'] > 0) {
            $message = "$currentLabel tidak dapat diperbarui karena masih digunakan oleh produk.";
            echo "<script type='text/javascript'>alert('$message'); window.location.href='category.php?tab=$tab';</script>";
            exit;
        }

        // Update the category
        $sql = "UPDATE $currentTable SET Nama = :nama WHERE ID = :id";
        $stid = oci_parse($conn, $sql);
        oci_bind_by_name($stid, ":nama", $namaKategori);
        oci_bind_by_name($stid, ":id", $id);

        if (oci_execute($stid)) {
            $message = "$currentLabel berhasil diperbarui.";
        } else {
            $message = "Gagal memperbarui $currentLabel.";
        }
        oci_free_statement($stid);

        // Redirect back with message
        echo "<script type='text/javascript'>alert('$message'); window.location.href='category.php?tab=$tab';</script>";
        exit;
    }
}

// Fetch the category data for the update form
$sql = "SELECT * FROM $currentTable WHERE ID = :id";
$stid = oci_parse($conn, $sql);
oci_bind_by_name($stid, ":id", $id);
oci_execute($stid);
$row = oci_fetch_assoc($stid);
oci_free_statement($stid);

// If category not found, redirect with error message
if (!$row) {
    header("Location: category.php?tab=$tab&message=" . urlencode("$currentLabel tidak ditemukan."));
    exit;
}

oci_close($conn);
?>

<!DOCTYPE html>
<html lang="en">

<body>
    <div class="container mt-5">
        <h2>Update <?php echo $currentLabel; ?></h2>
        <form method="POST" action="update-category.php?id=<?php echo htmlentities($id); ?>&tab=<?php echo $tab; ?>">
            <div class="mb-3">
                <label for="namaKategori" class="form-label">Nama <?php echo $currentLabel; ?></label>
                <input type="text" class="form-control" id="namaKategori" name="namaKategori" value="<?php echo htmlentities($row['NAMA']); ?>" required>
            </div>
            <button type="submit" name="action" value="update" class="btn btn-primary">Update</button>
            <a href="category.php?tab=<?php echo $tab; ?>" class="btn btn-secondary">Kembali</a>
        </form>
    </div>
</body>

</html>