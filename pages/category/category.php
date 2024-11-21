<?php
session_start();
include '../../config/connection.php';

// Include the header based on user role
if ($_SESSION['posisi'] === 'owner') {
    include '../owner/header.php';  // Owner's header
} elseif ($_SESSION['posisi'] === 'staff') {
    include '../staff/header.php';  // Staff's header
} else {
    // Redirect to login page if the user is not logged in or has an invalid role
    header("Location: ../../auth/login.php");
    exit();
}

$message = "";

// Default tab
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'produk';

// Ambil data berdasarkan jenis kategori
$tables = [
    'produk' => ['table' => 'KategoriProduk', 'label' => 'Product'],
    'obat' => ['table' => 'KategoriObat', 'label' => 'Medicine'],
    'salon' => ['table' => 'JenisLayananSalon', 'label' => 'Salon Service'],
    'medis' => ['table' => 'JenisLayananMedis', 'label' => 'Medical Service'],
];

if (!array_key_exists($tab, $tables)) {
    $tab = 'produk'; // Default to 'produk' if tab is invalid
}

$currentTable = $tables[$tab]['table'];
$currentLabel = $tables[$tab]['label'];

// Proses Tambah Kategori
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $namaKategori = trim($_POST['namaKategori']);
    $biaya = isset($_POST['biaya']) ? (int) $_POST['biaya'] : 30000;

    $sql = "INSERT INTO $currentTable (Nama" . ($tab === 'salon' || $tab === 'medis' ? ', Biaya' : '') . ") 
            VALUES (:nama" . ($tab === 'salon' || $tab === 'medis' ? ', :biaya' : '') . ")";
    $stid = oci_parse($conn, $sql);
    oci_bind_by_name($stid, ":nama", $namaKategori);
    if ($tab === 'salon' || $tab === 'medis') {
        oci_bind_by_name($stid, ":biaya", $biaya);
    }

    if (oci_execute($stid)) {
        $message = "$currentLabel berhasil ditambahkan.";
    } else {
        $message = "Gagal menambahkan $currentLabel.";
    }
    oci_free_statement($stid);
}

// Proses Hapus Kategori
if (isset($_GET['delete_id'])) {
    $deleteId = $_GET['delete_id'];
    $sql = "DELETE FROM $currentTable WHERE ID = :id";
    $stid = oci_parse($conn, $sql);
    oci_bind_by_name($stid, ":id", $deleteId);

    if (oci_execute($stid)) {
        $message = "$currentLabel berhasil dihapus.";
    } else {
        $message = "Gagal menghapus $currentLabel.";
    }
    oci_free_statement($stid);
}

// Ambil Data Kategori
$sql = "SELECT * FROM $currentTable ORDER BY ID";
$stid = oci_parse($conn, $sql);
oci_execute($stid);

$categories = [];
while ($row = oci_fetch_assoc($stid)) {
    $categories[] = $row;
}
oci_free_statement($stid);
oci_close($conn);
?>

<!DOCTYPE html>
<html lang="en">

<body>
    <div class="page-container">
        <h2>Manage Categories</h2>
        <?php if ($message != ""): ?>
            <div class="alert alert-info">
                <?php echo htmlentities($message); ?>
            </div>
        <?php endif; ?>

        <!-- Tabs for Category Types -->
        <ul class="nav nav-tabs">
            <?php foreach ($tables as $key => $table): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $tab === $key ? 'active' : ''; ?>" href="?tab=<?php echo $key; ?>">
                        <?php echo htmlentities($table['label']); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <div class="mt-3">
            <!-- Add Form -->
            <form method="POST" action="?tab=<?php echo $tab; ?>">
                <input type="hidden" name="action" value="add">
                <div class="mb-3">
                    <label for="namaKategori" class="form-label"><?php echo $currentLabel; ?> Name</label>
                    <input type="text" class="form-control" id="namaKategori" name="namaKategori" required>
                </div>
                <?php if ($tab === 'salon' || $tab === 'medis'): ?>
                    <div class="mb-3">
                        <label for="biaya" class="form-label">Price</label>
                        <input type="number" class="form-control" id="biaya" name="biaya" required>
                    </div>
                <?php endif; ?>
                <button type="submit" class="btn btn-add rounded-circle"><i class="fas fa-plus fa-xl"></i></button>
            </form>

            <!-- Category List -->
            <table class="table mt-3">
                <thead>
                    <tr>
                        <th><?php echo $currentLabel; ?> Name</th>
                        <?php if ($tab === 'salon' || $tab === 'medis'): ?>
                            <th>Price</th>
                        <?php endif; ?>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><?php echo htmlentities($category['NAMA']); ?></td>
                            <?php if ($tab === 'salon' || $tab === 'medis'): ?>
                                <td><?php echo htmlentities($category['BIAYA']); ?></td>
                            <?php endif; ?>
                            <td>
                                <!-- Edit Button -->
                                <a href="update-category.php?id=<?php echo $category['ID']; ?>&tab=<?php echo $tab; ?>" class="btn btn-warning btn-sm">Edit</a>
                                <!-- Delete Button -->
                                <a href="?tab=<?php echo $tab; ?>&delete_id=<?php echo $category['ID']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus?')">Hapus</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>