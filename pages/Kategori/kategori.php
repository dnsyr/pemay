<?php
session_start();
include '../../config/connection.php';

// Default tab
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'produk';

// Ambil data berdasarkan jenis kategori
$tables = [
    'produk' => ['table' => 'KategoriProduk', 'label' => 'Kategori Produk'],
    'obat' => ['table' => 'KategoriObat', 'label' => 'Kategori Obat'],
    'salon' => ['table' => 'JenisLayananSalon', 'label' => 'Jenis Layanan Salon'],
    'medis' => ['table' => 'JenisLayananMedis', 'label' => 'Jenis Layanan Medis'],
];

if (!array_key_exists($tab, $tables)) {
    $tab = 'produk'; // Default to 'produk' if tab is invalid
}

$currentTable = $tables[$tab]['table'];
$currentLabel = $tables[$tab]['label'];

// Proses Tambah Kategori
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $namaKategori = trim($_POST['namaKategori']);
    $biaya = isset($_POST['biaya']) ? (int) $_POST['biaya'] : null;

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

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Kategori</title>
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

    <!-- Content -->
    <div class="container mt-5">
        <h2>CRUD Kategori</h2>
        <?php if (isset($message)): ?>
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
                    <label for="namaKategori" class="form-label">Nama <?php echo $currentLabel; ?></label>
                    <input type="text" class="form-control" id="namaKategori" name="namaKategori" required>
                </div>
                <?php if ($tab === 'salon' || $tab === 'medis'): ?>
                    <div class="mb-3">
                        <label for="biaya" class="form-label">Biaya</label>
                        <input type="number" class="form-control" id="biaya" name="biaya" required>
                    </div>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary">Tambah</button>
            </form>

            <!-- Category List -->
            <table class="table mt-3">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama</th>
                        <?php if ($tab === 'salon' || $tab === 'medis'): ?>
                            <th>Biaya</th>
                        <?php endif; ?>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><?php echo htmlentities($category['ID']); ?></td>
                            <td><?php echo htmlentities($category['NAMA']); ?></td>
                            <?php if ($tab === 'salon' || $tab === 'medis'): ?>
                                <td><?php echo htmlentities($category['BIAYA']); ?></td>
                            <?php endif; ?>
                            <td>
                                <!-- Edit Button -->
                                <a href="update_kategori.php?id=<?php echo $category['ID']; ?>&tab=<?php echo $tab; ?>" class="btn btn-warning btn-sm">Edit</a>
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
