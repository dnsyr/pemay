<?php
ob_start();
session_start();
if (!isset($_SESSION['username']) || $_SESSION['posisi'] !== 'vet') {
    header("Location: ../../auth/restricted.php");
    exit();
}

include '../../config/database.php';
include '../../layout/header.php';

$db = new Database();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    include 'add-medical-services.php';
}

$tab = $_GET['tab'] ?? 'medical-services';
$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$filterNamaHewan = trim($_GET['nama_hewan'] ?? '');
$filterNamaPemilik = trim($_GET['nama_pemilik'] ?? '');

// Mendapatkan data untuk dropdown filter
$hewanOptions = $db->query("SELECT DISTINCT h.Nama FROM Hewan h WHERE h.onDelete = 0 ORDER BY h.Nama");
$pemilikOptions = $db->query("SELECT DISTINCT ph.Nama FROM PemilikHewan ph WHERE ph.onDelete = 0 ORDER BY h.Nama");

$message = htmlentities($_GET['message'] ?? '');

// Data untuk Layanan Medis
if ($tab === 'medical-services') {
    $whereConditions = ["lm.onDelete = 0"];
    $params = [];
    
    if ($filterNamaHewan) {
        $whereConditions[] = "h.Nama LIKE :nama_hewan";
        $params[':nama_hewan'] = "%$filterNamaHewan%";
    }
    
    if ($filterNamaPemilik) {
        $whereConditions[] = "ph.Nama LIKE :nama_pemilik";
        $params[':nama_pemilik'] = "%$filterNamaPemilik%";
    }
    
    $whereClause = implode(' AND ', $whereConditions);

    $sql = "SELECT lm.ID, lm.Tanggal, lm.TotalBiaya, lm.Description, lm.Status, 
                   h.Nama AS NAMAHEWAN, h.Spesies, 
                   ph.Nama AS NAMAPEMILIK, ph.NomorTelpon
            FROM LayananMedis lm
            JOIN Hewan h ON lm.Hewan_ID = h.ID
            JOIN PemilikHewan ph ON h.PemilikHewan_ID = ph.ID
            WHERE $whereClause
            ORDER BY
                CASE 
                    WHEN lm.Status = 'Emergency' THEN 1
                    WHEN lm.Status = 'Scheduled' THEN 2
                    WHEN lm.Status = 'Finished' THEN 3
                    WHEN lm.Status = 'Canceled' THEN 4
                    ELSE 5
                END,
                lm.Tanggal DESC
            OFFSET :offset ROWS FETCH NEXT :limit ROWS ONLY";

    $params[':offset'] = $offset;
    $params[':limit'] = $limit;
    
    $db->query($sql);
    foreach ($params as $key => $value) {
        $db->bind($key, $value);
    }
    $layananMedis = $db->resultSet();

    // Count total untuk pagination
    $sqlCount = "SELECT COUNT(*) AS TOTAL 
                 FROM LayananMedis lm
                 JOIN Hewan h ON lm.Hewan_ID = h.ID
                 JOIN PemilikHewan ph ON h.PemilikHewan_ID = ph.ID
                 WHERE $whereClause";
                 
    $db->query($sqlCount);
    foreach ($params as $key => $value) {
        if ($key !== ':offset' && $key !== ':limit') {
            $db->bind($key, $value);
        }
    }
    $rowCount = $db->single();
    $totalData = $rowCount['TOTAL'];
    $totalPages = ceil($totalData / $limit);
}

// Data untuk Obat
if ($tab === 'obat') {
    $whereConditions = ["ro.onDelete = 0"];
    $params = [];
    
    if ($filterNamaHewan) {
        $whereConditions[] = "h.Nama LIKE :nama_hewan";
        $params[':nama_hewan'] = "%$filterNamaHewan%";
        $whereConditions[] = "lm.Status = 'Finished'";
    }
    
    if ($filterNamaPemilik) {
        $whereConditions[] = "ph.Nama LIKE :nama_pemilik";
        $params[':nama_pemilik'] = "%$filterNamaPemilik%";
        $whereConditions[] = "lm.Status = 'Finished'";
    }
    
    $whereClause = implode(' AND ', $whereConditions);

    $sqlObat = "SELECT ro.ID, ro.Nama, ro.Dosis, ro.Frekuensi, ro.Instruksi, 
                       lm.Tanggal AS TANGGALLAYANAN, 
                       ko.Nama AS KATEGORIOBAT, 
                       h.Nama AS NAMAHEWAN, 
                       ph.Nama AS NAMAPEMILIK,
                       lm.Status AS STATUS
                FROM ResepObat ro
                JOIN LayananMedis lm ON ro.LayananMedis_ID = lm.ID
                JOIN Hewan h ON lm.Hewan_ID = h.ID
                JOIN PemilikHewan ph ON h.PemilikHewan_ID = ph.ID
                JOIN KategoriObat ko ON ro.KategoriObat_ID = ko.ID
                WHERE $whereClause
                ORDER BY ro.Nama ASC
                OFFSET :offset ROWS FETCH NEXT :limit ROWS ONLY";

    $params[':offset'] = $offset;
    $params[':limit'] = $limit;
    
    $db->query($sqlObat);
    foreach ($params as $key => $value) {
        $db->bind($key, $value);
    }
    $obatList = $db->resultSet();

    // Count total untuk pagination obat
    $sqlCountObat = "SELECT COUNT(*) AS TOTAL 
                     FROM ResepObat ro
                     JOIN LayananMedis lm ON ro.LayananMedis_ID = lm.ID
                     JOIN Hewan h ON lm.Hewan_ID = h.ID
                     JOIN PemilikHewan ph ON h.PemilikHewan_ID = ph.ID
                     JOIN KategoriObat ko ON ro.KategoriObat_ID = ko.ID
                     WHERE $whereClause";
                 
    $db->query($sqlCountObat);
    foreach ($params as $key => $value) {
        if ($key !== ':offset' && $key !== ':limit') {
            $db->bind($key, $value);
        }
    }
    $rowCount = $db->single();
    $totalDataObat = $rowCount['TOTAL'];
    $totalPagesObat = ceil($totalDataObat / $limit);
}

// Hapus oci_close karena tidak digunakan lagi
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <title>Pet Management</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@3.9.4/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body>
    <div class="container mx-auto p-5">
        <h1 class="text-2xl font-bold mb-4">Pet Management</h1>

        <?php if (isset($deleteMessage)): ?>
            <div class="alert alert-error"><?= $deleteMessage ?></div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="alert alert-success"><?= $message ?></div>
        <?php endif; ?>

        <!-- Tab Navigation -->
        <div class="tabs mb-4">
            <a class="tab tab-lifted <?= $tab === 'medical-services' ? 'tab-active' : '' ?>" 
               href="?tab=medical-services">Layanan Medis</a>
            <a class="tab tab-lifted <?= $tab === 'obat' ? 'tab-active' : '' ?>" 
               href="?tab=obat">Obat</a>
        </div>

        <div class="mt-3">
            <?php if ($tab === 'medical-services'): ?>
                <!-- Filter Form -->
                <form method="GET" action="dashboard.php" class="mb-4">
                    <input type="hidden" name="tab" value="medical-services">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="form-control">
                            <label class="label">Nama Hewan</label>
                            <select name="nama_hewan" class="select select-bordered w-full">
                                <option value="">Pilih Nama Hewan</option>
                                <?php foreach ($hewanOptions as $hewan): ?>
                                    <option value="<?= htmlentities($hewan['NAMA']) ?>" 
                                            <?= $hewan['NAMA'] === $filterNamaHewan ? 'selected' : '' ?>>
                                        <?= htmlentities($hewan['NAMA']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-control">
                            <label class="label">Nama Pemilik</label>
                            <select name="nama_pemilik" class="select select-bordered w-full">
                                <option value="">Pilih Nama Pemilik</option>
                                <?php foreach ($pemilikOptions as $pemilik): ?>
                                    <option value="<?= htmlentities($pemilik['NAMA']) ?>" 
                                            <?= $pemilik['NAMA'] === $filterNamaPemilik ? 'selected' : '' ?>>
                                        <?= htmlentities($pemilik['NAMA']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-control">
                            <label class="label opacity-0">Filter</label>
                            <button type="submit" class="btn btn-primary w-full">Filter</button>
                        </div>
                    </div>
                </form>

                <!-- Drawer Implementation -->
                <div class="drawer drawer-end">
                    <input id="my-drawer" type="checkbox" class="drawer-toggle" /> 
                    
                    <!-- Page content -->
                    <div class="drawer-content">
                        <label for="my-drawer" class="btn btn-primary mb-4">Tambah Layanan Medis</label>
                    </div> 

                    <!-- Drawer side -->
                    <div class="drawer-side z-50">
                        <label for="my-drawer" class="drawer-overlay"></label>
                        <div class="menu p-4 w-96 min-h-full bg-base-200 text-base-content">
                            <!-- Drawer header -->
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="font-bold text-lg">Tambah Layanan Medis</h3>
                                <label for="my-drawer" class="btn btn-sm btn-circle">✕</label>
                            </div>

                            <!-- Form content -->
                            <?php include 'add-medical-services.php'; ?>
                        </div>
                    </div>
                </div>

                <!-- Table Content -->
                <?php if (empty($layananMedis)): ?>
                    <div class="alert alert-info">Tidak ada data layanan medis untuk ditampilkan.</div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="table table-zebra w-full">
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
                                <?php $nomor = $offset + 1; ?>
                                <?php foreach ($layananMedis as $layanan): ?>
                                    <tr>
                                        <td><?= $nomor++; ?></td>
                                        <td><?= htmlentities($layanan['TANGGAL']); ?></td>
                                        <td>Rp <?= number_format($layanan['TOTALBIAYA'], 0, ',', '.'); ?></td>
                                        <td><?= htmlentities($layanan['DESCRIPTION']); ?></td>
                                        <td>
                                            <span class="badge <?= match ($layanan['STATUS']) {
                                                'Emergency' => 'badge-error',
                                                'Finished' => 'badge-success',
                                                'Scheduled' => 'badge-warning',
                                                'Canceled' => 'badge-ghost',
                                                default => 'badge-info'
                                            } ?>">
                                                <?= htmlentities($layanan['STATUS']); ?>
                                            </span>
                                        </td>
                                        <td><?= htmlentities($layanan['NAMAHEWAN']); ?></td>
                                        <td><?= htmlentities($layanan['SPESIES']); ?></td>
                                        <td><?= htmlentities($layanan['NAMAPEMILIK']); ?></td>
                                        <td><?= htmlentities($layanan['NOMORTELPON']); ?></td>
                                        <td>
                                            <div class="join">
                                                <?php if ($layanan['STATUS'] === 'Finished' || $layanan['STATUS'] === 'Canceled'): ?>
                                                    <button class="btn btn-warning btn-sm join-item" onclick="alert('Cannot update this record.')">Update</button>
                                                <?php else: ?>
                                                    <a href="update-medical-services.php?id=<?= urlencode(htmlentities($layanan['ID'])); ?>" 
                                                       class="btn btn-warning btn-sm join-item">Update</a>
                                                <?php endif; ?>
                                                <a href="delete-medical.php?tab=medical-services&delete_id=<?= urlencode(htmlentities($layanan['ID'])); ?>&page=<?= $page; ?>&nama_hewan=<?= urlencode($filterNamaHewan); ?>&nama_pemilik=<?= urlencode($filterNamaPemilik); ?>" 
                                                   class="btn btn-error btn-sm join-item" 
                                                   onclick="return confirm('Apakah Anda yakin ingin menghapus layanan ini?');">Hapus</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="join flex justify-center mt-4">
                        <a class="join-item btn <?= ($page <= 1) ? 'btn-disabled' : '' ?>"
                           href="?tab=medical-services&page=<?= $page - 1 ?>&nama_hewan=<?= urlencode($filterNamaHewan) ?>&nama_pemilik=<?= urlencode($filterNamaPemilik) ?>">«</a>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a class="join-item btn <?= ($page === $i) ? 'btn-active' : '' ?>"
                               href="?tab=medical-services&page=<?= $i ?>&nama_hewan=<?= urlencode($filterNamaHewan) ?>&nama_pemilik=<?= urlencode($filterNamaPemilik) ?>"><?= $i ?></a>
                        <?php endfor; ?>
                        
                        <a class="join-item btn <?= ($page >= $totalPages) ? 'btn-disabled' : '' ?>"
                           href="?tab=medical-services&page=<?= $page + 1 ?>&nama_hewan=<?= urlencode($filterNamaHewan) ?>&nama_pemilik=<?= urlencode($filterNamaPemilik) ?>">»</a>
                    </div>
                <?php endif; ?>

            <?php elseif ($tab === 'obat'): ?>
                <!-- Filter Form -->
                <form method="GET" action="dashboard.php" class="mb-3">
                    <input type="hidden" name="tab" value="obat">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label for="nama_hewan_obat" class="form-label">Nama Hewan</label>
                            <select name="nama_hewan" id="nama_hewan_obat" class="form-control select2">
                                <option value="">Pilih Nama Hewan</option>
                                <?php foreach ($hewanOptions as $hewan): ?>
                                    <option value="<?= htmlentities($hewan); ?>" <?= $hewan === $filterNamaHewan ? 'selected' : ''; ?>>
                                        <?= htmlentities($hewan); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="nama_pemilik_obat" class="form-label">Nama Pemilik</label>
                            <select name="nama_pemilik" id="nama_pemilik_obat" class="form-control select2">
                                <option value="">Pilih Nama Pemilik</option>
                                <?php foreach ($pemilikOptions as $pemilik): ?>
                                    <option value="<?= htmlentities($pemilik); ?>" <?= $pemilik === $filterNamaPemilik ? 'selected' : ''; ?>>
                                        <?= htmlentities($pemilik); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                    </div>
                </form>

                <?php if (empty($obatList)): ?>
                    <div class="alert alert-info">Tidak ada data obat untuk ditampilkan.</div>
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
                            <?php $nomor = $offset + 1; ?>
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
    <div class="btn-group" role="group">
        <?php if ($obat['STATUS'] === 'Finished' || $obat['STATUS'] === 'Canceled'): ?>
            <a href="#" class="btn btn-warning btn-sm" onclick="alert('Cannot update this record.'); return false;">Update</a>
        <?php else: ?>
            <a href="update-obat.php?id=<?= urlencode(htmlentities($obat['ID'])); ?>" class="btn btn-warning btn-sm">Update</a>
        <?php endif; ?>
        <a href="delete-medical.php?tab=obat&delete_id=<?= urlencode(htmlentities($obat['ID'])); ?>&page=<?= $page; ?>&nama_hewan=<?= urlencode($filterNamaHewan); ?>&nama_pemilik=<?= urlencode($filterNamaPemilik); ?>" 
           class="btn btn-danger btn-sm" 
           onclick="return confirm('Apakah Anda yakin ingin menghapus obat ini?');">Hapus</a>
    </div>
</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <nav>
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?= ($page <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?tab=obat&page=<?= $page - 1; ?>&nama_hewan=<?= urlencode($filterNamaHewan); ?>&nama_pemilik=<?= urlencode($filterNamaPemilik); ?>">Previous</a>
                            </li>
                            <?php for ($i = 1; $i <= $totalPagesObat; $i++): ?>
                                <li class="page-item <?= ($page === $i) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?tab=obat&page=<?= $i; ?>&nama_hewan=<?= urlencode($filterNamaHewan); ?>&nama_pemilik=<?= urlencode($filterNamaPemilik); ?>"><?= $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= ($page >= $totalPagesObat) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?tab=obat&page=<?= $page + 1; ?>&nama_hewan=<?= urlencode($filterNamaHewan); ?>&nama_pemilik=<?= urlencode($filterNamaPemilik); ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (isset($_GET['message'])): ?>
            document.getElementById('my-drawer').checked = false;
        <?php endif; ?>
    });
    </script>
</script>
</body>

</html>