<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['posisi'] != 'vet') {
    header("Location: ../../auth/restricted.php");
    exit();
}

include '../../config/connection.php';

// Proses Tambah Layanan Medis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $tanggal = $_POST['tanggal'];
    $totalBiaya = $_POST['total_biaya'];
    $description = $_POST['description'];
    $status = $_POST['status'];
    $pegawai_id = $_SESSION['id']; // Ambil ID pegawai dari sesi
    $hewan_id = $_POST['hewan_id']; // ID Hewan yang dipilih
    $jenisLayananArray = $_POST['jenis_layanan']; // Array ID jenis layanan medis

    // Mengonversi array menjadi string untuk VARRAY
    $jenisLayananString = "ArrayJenisLayananMedis(" . implode(',', $jenisLayananArray) . ")";

    $sql = "INSERT INTO LayananMedis (Tanggal, TotalBiaya, Description, Status, JenisLayanan, Pegawai_ID, Hewan_ID) 
            VALUES (TO_DATE(:tanggal, 'YYYY-MM-DD'), :totalBiaya, :description, :status, $jenisLayananString, :pegawai_id, :hewan_id)";
    
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':tanggal', $tanggal);
    oci_bind_by_name($stmt, ':totalBiaya', $totalBiaya);
    oci_bind_by_name($stmt, ':description', $description);
    oci_bind_by_name($stmt, ':status', $status);
    oci_bind_by_name($stmt, ':pegawai_id', $pegawai_id);
    oci_bind_by_name($stmt, ':hewan_id', $hewan_id);
    
    if (oci_execute($stmt)) {
        $message = "Layanan medis berhasil ditambahkan.";
    } else {
        $message = "Gagal menambahkan layanan medis.";
    }
    oci_free_statement($stmt);
}

// Proses Hapus Layanan Medis
if (isset($_GET['delete_id'])) {
    $deleteId = $_GET['delete_id'];
    $sql = "DELETE FROM LayananMedis WHERE ID = :id";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':id', $deleteId);

    if (oci_execute($stmt)) {
        $message = "Layanan medis berhasil dihapus.";
    } else {
        $message = "Gagal menghapus layanan medis.";
    }
    oci_free_statement($stmt);
}

// Ambil Data Layanan Medis
$sql = "SELECT * FROM LayananMedis";
$stmt = oci_parse($conn, $sql);
oci_execute($stmt);

$layananMedis = [];


// Ambil Data Jenis Layanan Medis untuk ditampilkan di form
$sql = "SELECT * FROM JenisLayananMedis";
$stmt = oci_parse($conn, $sql);
oci_execute($stmt);

$jenisLayananMedis = [];
while ($row = oci_fetch_assoc($stmt)) {
    $jenisLayananMedis[] = $row ;
}
oci_free_statement($stmt);
oci_close($conn);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Layanan Medis</title>
    <link rel="shortcut icon" href="../../public/img/icon.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../public/css/index.css">
    <script>
        function updateTotal() {
            const checkboxes = document.querySelectorAll('input[name="jenis_layanan[]"]:checked');
            let total = 0;
            checkboxes.forEach((checkbox) => {
                const biaya = parseFloat(checkbox.getAttribute('data-biaya'));
                if (!isNaN(biaya)) {
                    total += biaya;
                }
            });
            document.getElementById('total_biaya').value = total;
        }
    </script>
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
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="medical-services.php">Layanan Medis</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Link</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Link</a>
                    </li>
                </ul>
            </div>
            <form action="../../auth/logout.php" method="post">
                <button class="btn btn-link text-dark text-decoration-none" type="submit">Logout</button>
            </form>
        </div>
    </nav>

    <div class="page-container">
        <h1>Layanan Medis</h1>
        <?php if (isset($message)): ?>
            <div class="alert alert-info">
                <?php echo htmlentities($message); ?>
            </div>
        <?php endif; ?>

        <!-- Form Tambah Layanan Medis -->
        <form method="POST" action="medical-services.php">
            <input type="hidden" name="action" value="add">
            <div class="mb-3">
                <label for="tanggal" class="form-label">Tanggal</label>
                <input type="date" class="form-control" id="tanggal" name="tanggal" required>
            </div>
            <div class="mb-3">
                <label for="total_biaya" class="form-label">Total Biaya</label>
                <input type="number" class="form-control" id="total_biaya" name="total_biaya" readonly>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Deskripsi</label>
                <textarea class="form-control" id="description" name="description" required></textarea>
            </div>
            <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status" required>
                    <option value="active">Aktif</option>
                    <option value="completed">Selesai</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="hewan_id" class="form-label">ID Hewan</label>
                <input type="number" class="form-control" id="hewan_id" name="hewan_id" required>
            </div>
            <div class="mb-3">
                <label for="jenis_layanan" class="form-label">Jenis Layanan Medis</label>
                <div>
                    <?php foreach ($jenisLayananMedis as $layanan): ?>
                        <div class="form-check">
                            <input class=" form-check-input" type="checkbox" name="jenis_layanan[]" id="layanan_<?php echo $layanan['ID']; ?>" value="<?php echo $layanan['ID']; ?>" data-biaya="<?php echo $layanan['BIAYA']; ?>" onclick="updateTotal()">
                            <label class="form-check-label" for="layanan_<?php echo $layanan['ID']; ?>">
                                <?php echo htmlentities($layanan['NAMA']); ?> - Biaya: <?php echo htmlentities($layanan['BIAYA']); ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Tambah Layanan Medis</button>
        </form>

        <!-- Tabel Layanan Medis -->
        <table class="table mt-3">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tanggal</th>
                    <th>Total Biaya</th>
                    <th>Deskripsi</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($layananMedis as $layanan): ?>
                    <tr>
                        <td><?php echo htmlentities($layanan['ID']); ?></td>
                        <td><?php echo htmlentities($layanan['TANGGAL']); ?></td>
                        <td><?php echo htmlentities($layanan['TOTALBIAYA']); ?></td>
                        <td><?php echo htmlentities($layanan['DESCRIPTION']); ?></td>
                        <td><?php echo htmlentities($layanan['STATUS']); ?></td>
                        <td>
                            <a href="?delete_id=<?php echo $layanan['ID']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus?')">Hapus</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>