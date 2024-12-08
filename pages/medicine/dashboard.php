<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['posisi'] != 'vet') {
    header("Location: ../../auth/restricted.php");
    exit();
}

include '../../config/connection.php';
include '../../layout/header.php';

// Menangani Tab yang Dipilih
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'medical-services'; // Default ke 'medical-services'

// Pagination Setup
$limit = 5; // Batasan 5 data per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Halaman saat ini, default 1
$offset = ($page - 1) * $limit; // Offset berdasarkan halaman

// Filter untuk Obat
$filterNamaHewan = isset($_GET['nama_hewan']) ? $_GET['nama_hewan'] : '';
$filterNamaPemilik = isset($_GET['nama_pemilik']) ? $_GET['nama_pemilik'] : '';

// Menangani Data Layanan Medis dengan Filter dan Pagination
if ($tab === 'medical-services') {
    // Filter query
    $whereClause = "WHERE lm.onDelete = 0";
    if ($filterNamaHewan) {
        $whereClause .= " AND h.Nama LIKE :nama_hewan";
    }
    if ($filterNamaPemilik) {
        $whereClause .= " AND ph.Nama LIKE :nama_pemilik";
    }

    // Ambil Data Layanan Medis dengan Pagination dan Filter
    $sql = "SELECT lm.ID, lm.Tanggal, lm.TotalBiaya, lm.Description, lm.Status, 
                   h.Nama AS NamaHewan, h.Spesies, 
                   ph.Nama AS NamaPemilik, ph.NomorTelpon
            FROM LayananMedis lm
            JOIN Hewan h ON lm.Hewan_ID = h.ID
            JOIN PemilikHewan ph ON h.PemilikHewan_ID = ph.ID
            $whereClause
            ORDER BY
                CASE 
                WHEN lm.Status = 'Emergency' THEN 1
                WHEN lm.Status = 'Reserved' THEN 2
                WHEN lm.Status = 'Selesai' THEN 3
                ELSE 4
            END,
            lm.Tanggal DESC
        OFFSET :offset ROWS FETCH NEXT :limit ROWS ONLY";
    $stmt = oci_parse($conn, $sql);
    // Bind parameter filter jika ada
    if ($filterNamaHewan) {
        oci_bind_by_name($stmt, ":nama_hewan", $filterNamaHewan);
    }
    if ($filterNamaPemilik) {
        oci_bind_by_name($stmt, ":nama_pemilik", $filterNamaPemilik);
    }

    oci_bind_by_name($stmt, ":offset", $offset);
    oci_bind_by_name($stmt, ":limit", $limit);

    if (!oci_execute($stmt)) {
        $error = oci_error($stmt);
        die("Terjadi kesalahan saat mengambil data: " . htmlentities($error['message']));
    }

    $layananMedis = [];
    while ($row = oci_fetch_assoc($stmt)) {
        $layananMedis[] = $row;
    }
    oci_free_statement($stmt);

    // Menghitung Total Data untuk Pagination Layanan Medis
    $sqlCount = "SELECT COUNT(*) AS total 
                 FROM LayananMedis lm
                 JOIN Hewan h ON lm.Hewan_ID = h.ID
                 JOIN PemilikHewan ph ON h.PemilikHewan_ID = ph.ID
                 $whereClause";
    $stmtCount = oci_parse($conn, $sqlCount);

    // Bind parameter filter jika ada
    if ($filterNamaHewan) {
        oci_bind_by_name($stmtCount, ":nama_hewan", $filterNamaHewan);
    }
    if ($filterNamaPemilik) {
        oci_bind_by_name($stmtCount, ":nama_pemilik", $filterNamaPemilik);
    }

    oci_execute($stmtCount);
    $rowCount = oci_fetch_assoc($stmtCount);
    $totalData = $rowCount['TOTAL'];
    oci_free_statement($stmtCount);

    $totalPages = ceil($totalData / $limit); // Menghitung total halaman
}


$sqlHewan = "SELECT DISTINCT h.Nama FROM Hewan h";
$stmtHewan = oci_parse($conn, $sqlHewan);
oci_execute($stmtHewan);
$hewanOptions = [];
while ($row = oci_fetch_assoc($stmtHewan)) {
    $hewanOptions[] = $row['NAMA'];
}
oci_free_statement($stmtHewan);

$sqlPemilik = "SELECT DISTINCT ph.Nama FROM PemilikHewan ph";
$stmtPemilik = oci_parse($conn, $sqlPemilik);
oci_execute($stmtPemilik);
$pemilikOptions = [];
while ($row = oci_fetch_assoc($stmtPemilik)) {
    $pemilikOptions[] = $row['NAMA'];
}
oci_free_statement($stmtPemilik);
// Data untuk Obat dengan Filter dan Pagination
if ($tab === 'obat') {
    // Filter query
    $whereClause = "WHERE o.onDelete = 0";
    if ($filterNamaHewan) {
        $whereClause .= " AND h.Nama LIKE :nama_hewan";
    }
    if ($filterNamaPemilik) {
        $whereClause .= " AND ph.Nama LIKE :nama_pemilik";
    }

    // Ambil Data Obat dengan Pagination dan Filter
    $sql = "SELECT o.ID, o.Nama, o.Dosis, o.Frekuensi, o.Instruksi, o.Harga, 
    lm.Tanggal AS TanggalLayanan, ko.Nama AS KategoriObat,
    h.Nama AS NamaHewan, ph.Nama AS NamaPemilik
FROM Obat o
JOIN LayananMedis lm ON o.LayananMedis_ID = lm.ID
JOIN KategoriObat ko ON o.KategoriObat_ID = ko.ID
JOIN Hewan h ON lm.Hewan_ID = h.ID
JOIN PemilikHewan ph ON h.PemilikHewan_ID = ph.ID
$whereClause
ORDER BY o.Nama ASC
OFFSET :offset ROWS FETCH NEXT :limit ROWS ONLY";

$stmt = oci_parse($conn, $sql);

if ($filterNamaHewan) {
    oci_bind_by_name($stmt, ":nama_hewan", $filterNamaHewan);
}
if ($filterNamaPemilik) {
    oci_bind_by_name($stmt, ":nama_pemilik", $filterNamaPemilik);
}

oci_bind_by_name($stmt, ":offset", $offset);
oci_bind_by_name($stmt, ":limit", $limit);

if (!oci_execute($stmt)) {
    $error = oci_error($stmt);
    die("Terjadi kesalahan saat mengambil data: " . htmlentities($error['message']));
}
$obatList = [];
while ($row = oci_fetch_assoc($stmt)) {
    $obatList[] = $row;
}
oci_free_statement($stmt);
    
    // Menghitung Total Data untuk Pagination Obat
    $sqlCount = "SELECT COUNT(*) AS total 
                 FROM Obat o
                 JOIN LayananMedis lm ON o.LayananMedis_ID = lm.ID
                 JOIN Hewan h ON lm.Hewan_ID = h.ID
                 JOIN PemilikHewan ph ON h.PemilikHewan_ID = ph.ID
                 $whereClause";
    $stmtCount = oci_parse($conn, $sqlCount);
    if ($filterNamaHewan) {
        oci_bind_by_name($stmtCount, ":nama_hewan", $filterNamaHewan);
    }
    if ($filterNamaPemilik) {
        oci_bind_by_name($stmtCount, ":nama_pemilik", $filterNamaPemilik);
    }

    oci_execute($stmtCount);
    $rowCount = oci_fetch_assoc($stmtCount);
    $totalData = $rowCount['TOTAL'];
    oci_free_statement($stmtCount);

    $totalPages = ceil($totalData / $limit); // Menghitung total halaman
}

oci_close($conn);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Pet Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1>Pet Management</h1>
        </div>

        <!-- Tab Navigation -->
        <ul class="nav nav-tabs">
            <li class="nav-item">
                <a class="nav-link <?php echo $tab === 'medical-services' ? 'active' : ''; ?>" href="?tab=medical-services">Layanan Medis</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $tab === 'obat' ? 'active' : ''; ?>" href="?tab=obat">Obat</a>
            </li>
        </ul>

        <div class="mt-3">
            <!-- Content for Medical Services -->
             <!-- Filter Form untuk Layanan Medis -->
<?php if ($tab === 'medical-services'): ?>
    <div class="mb-3">
        <form method="GET" action="dashboard.php">
            <input type="hidden" name="tab" value="medical-services">
            <div class="row">
                <div class="col-md-4">
                    <select name="nama_hewan" class="form-control">
                        <option value="">Pilih Nama Hewan</option>
                        <?php foreach ($hewanOptions as $hewan): ?>
                            <option value="<?= $hewan; ?>" <?= $hewan === $filterNamaHewan ? 'selected' : ''; ?>><?= $hewan; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <select name="nama_pemilik" class="form-control">
                        <option value="">Pilih Nama Pemilik</option>
                        <?php foreach ($pemilikOptions as $pemilik): ?>
                            <option value="<?= $pemilik; ?>" <?= $pemilik === $filterNamaPemilik ? 'selected' : ''; ?>><?= $pemilik; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>
            </div>
        </form>
    </div>
<?php endif; ?>
            <?php if ($tab === 'medical-services'): ?>
                <div class="d-flex justify-content-between mb-3">
                    <a href="add-medical-services.php" class="btn btn-primary">Tambah Layanan Medis</a>
                </div>

                <?php if (empty($layananMedis)): ?>
                    <p class="alert alert-info">Tidak ada data layanan medis untuk ditampilkan.</p>
                <?php else: ?>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Tanggal</th>
                                <th>Total Biaya</th>
                                <th>Deskripsi</th>
                                <th>Status</th>
                                <th>Nama Hewan</th>
                                <th>Spesies</th>
                                <th>Nama Pemilik</th>
                                <th>No. Telepon</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $nomor = 1; ?>
                            <?php foreach ($layananMedis as $layanan): ?>
                                <tr class="
                                <?php
            if ($layanan['STATUS'] === 'Emergency') {
                echo 'table-danger'; // Merah untuk Emergency
            } elseif ($layanan['STATUS'] === 'Selesai') {
                echo 'table-success'; // Hijau untuk Selesai
            } elseif ($layanan['STATUS'] === 'Reserved') {
                echo 'table-secondary'; // Abu-abu untuk Reserved
            }
        ?>
    ">
                                    <td><?= $nomor++; ?></td>
                                    <td><?= htmlentities($layanan['TANGGAL']); ?></td>
                                    <td>Rp <?= number_format($layanan['TOTALBIAYA'], 0, ',', '.'); ?></td>
                                    <td><?= htmlentities($layanan['DESCRIPTION']); ?></td>
                                    <td><?= htmlentities($layanan['STATUS']); ?></td>
                                    <td><?= htmlentities($layanan['NAMAHEWAN']); ?></td>
                                    <td><?= htmlentities($layanan['SPESIES']); ?></td>
                                    <td><?= htmlentities($layanan['NAMAPEMILIK']); ?></td>
                                    <td><?= htmlentities($layanan['NOMORTELPON']); ?></td>
                                    <td>
                                        <a href="update-medical-services.php?id=<?= $layanan['ID']; ?>" class="btn btn-warning btn-sm">Update</a>
                                        <a href="dashboard.php?delete_id=<?= $layanan['ID']; ?>" 
                                           class="btn btn-danger btn-sm" 
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus layanan ini?');">Hapus
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <nav>
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?= ($page <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?tab=medical-services&page=<?= $page - 1; ?>">Previous</a>
                            </li>
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= ($page === $i) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?tab=medical-services&page=<?= $i; ?>"><?= $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?tab=medical-services&page=<?= $page + 1; ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>

            <!-- Content for Obat -->
            <?php elseif ($tab === 'obat'): ?>

                <div class="mb-3">
                    <form method="GET" action="dashboard.php">
                        <input type="hidden" name="tab" value="obat">
                        <div class="row">
                            <div class="col-md-4">
                                <select name="nama_hewan" class="form-control">
                                    <option value="">Pilih Nama Hewan</option>
                                    <?php foreach ($hewanOptions as $hewan): ?>
                                        <option value="<?= $hewan; ?>" <?= $hewan === $filterNamaHewan ? 'selected' : ''; ?>><?= $hewan; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <select name="nama_pemilik" class="form-control">
                                    <option value="">Pilih Nama Pemilik</option>
                                    <?php foreach ($pemilikOptions as $pemilik): ?>
                                        <option value="<?= $pemilik; ?>" <?= $pemilik === $filterNamaPemilik ? 'selected' : ''; ?>><?= $pemilik; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary">Filter</button>
                            </div>
                        </div>
                    </form>
                </div>

                <?php if (empty($obatList)): ?>
                    <p class="alert alert-info">Tidak ada data obat untuk ditampilkan.</p>
                <?php else: ?>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Nama Obat</th>
                                <th>Dosis</th>
                                <th>Frekuensi</th>
                                <th>Instruksi</th>
                                <th>Tanggal Layanan</th>
                                <th>Kategori Obat</th>
                                <th>Nama Hewan</th>
                                <th>Nama Pemilik</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $nomor = 1; ?>
                            <?php foreach ($obatList as $obat): ?>
                                <tr>
                                    <td><?= $nomor++; ?></td>
                                    <td><?= htmlentities($obat['NAMA']); ?></td>
                                    <td><?= htmlentities($obat['DOSIS']); ?></td>
                                    <td><?= htmlentities($obat['FREKUENSI']); ?></td>
                                    <td><?= htmlentities($obat['INSTRUKSI']); ?></td>
                                    <td><?= htmlentities($obat['TANGGALLAYANAN']); ?></td>
                                    <td><?= htmlentities($obat['KATEGORIOBAT']); ?></td>
                                    <td><?= htmlentities($obat['NAMAHEWAN']); ?></td>
                                    <td><?= htmlentities($obat['NAMAPEMILIK']); ?></td>
                                    <td>
                                        <a href="update-obat.php?id=<?= $obat['ID']; ?>" class="btn btn-warning btn-sm">Update</a>
                                        <a href="dashboard.php?delete_id=<?= $obat['ID']; ?>" 
                                           class="btn btn-danger btn-sm" 
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus obat ini?');">Hapus
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <nav>
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?= ($page <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?tab=obat&page=<?= $page - 1; ?>">Previous</a>
                            </li>
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= ($page === $i) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?tab=obat&page=<?= $i; ?>"><?= $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?tab=obat&page=<?= $page + 1; ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>
