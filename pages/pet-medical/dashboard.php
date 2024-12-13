<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['posisi'] !== 'vet') {
    header("Location: ../../auth/restricted.php");
    exit();
}

include '../../config/connection.php';
include '../../layout/header.php';

$tab = $_GET['tab'] ?? 'medical-services';
$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$filterNamaHewan = trim($_GET['nama_hewan'] ?? '');
$filterNamaPemilik = trim($_GET['nama_pemilik'] ?? '');

// Fungsi untuk mengambil opsi filter
function getOptions($conn, $sql, $field) {
    $stmt = oci_parse($conn, $sql);
    oci_execute($stmt);
    $options = [];
    while ($row = oci_fetch_assoc($stmt)) {
        $options[] = $row[$field];
    }
    oci_free_statement($stmt);
    return $options;
}

$hewanOptions = getOptions($conn, "SELECT DISTINCT h.Nama FROM Hewan h WHERE h.onDelete = 0 ORDER BY h.Nama", 'NAMA');
$pemilikOptions = getOptions($conn, "SELECT DISTINCT ph.Nama FROM PemilikHewan ph WHERE ph.onDelete = 0 ORDER BY ph.Nama", 'NAMA');

// Menghapus data
if (isset($_GET['delete_id'])) {
    $deleteId = trim($_GET['delete_id']);
    $currentTab = $tab;
    $currentPage = $page;

    if (!preg_match('/^[a-f0-9\-]{36}$/i', $deleteId)) {
        $deleteMessage = '<div class="alert alert-danger">Format ID tidak valid.</div>';
    } else {
        $sqlDelete = match ($currentTab) {
            'medical-services' => "UPDATE LayananMedis SET onDelete = 1 WHERE ID = :id",
            'obat' => "UPDATE ResepObat SET onDelete = 1 WHERE ID = :id",
            default => null
        };

        if ($sqlDelete) {
            $stmtDelete = oci_parse($conn, $sqlDelete);
            oci_bind_by_name($stmtDelete, ':id', $deleteId);

            if (oci_execute($stmtDelete, OCI_COMMIT_ON_SUCCESS)) {
                $messageText = $currentTab === 'medical-services' 
                    ? 'Layanan Medis berhasil dihapus.' 
                    : 'Obat berhasil dihapus.';
                header("Location: dashboard.php?tab={$currentTab}&page={$currentPage}&message=" . urlencode($messageText));
                exit();
            } else {
                $error = oci_error($stmtDelete);
                $deleteMessage = '<div class="alert alert-danger">Gagal menghapus: ' . htmlentities($error['message']) . '</div>';
            }
            oci_free_statement($stmtDelete);
        } else {
            $deleteMessage = '<div class="alert alert-danger">Tab tidak dikenal untuk penghapusan.</div>';
        }
    }
}

$message = htmlentities($_GET['message'] ?? '');

// Fungsi untuk membangun klausa WHERE
function buildWhereClause($base, $statusCondition, $filters) {
    $clause = $base;
    if ($statusCondition) {
        $clause .= " AND lm.Status = 'Finished'";
    }
    foreach ($filters as $column => $placeholder) {
        if (!empty($placeholder['value'])) {
            $clause .= " AND {$column} LIKE :{$placeholder['name']}";
        }
    }
    return $clause;
}

// Data untuk Layanan Medis
if ($tab === 'medical-services') {
    $filters = [
        'h.Nama' => ['name' => 'nama_hewan', 'value' => $filterNamaHewan],
        'ph.Nama' => ['name' => 'nama_pemilik', 'value' => $filterNamaPemilik]
    ];

    $whereClause = buildWhereClause("WHERE lm.onDelete = 0", $filterNamaHewan || $filterNamaPemilik, $filters);

    $sql = "SELECT lm.ID, lm.Tanggal, lm.TotalBiaya, lm.Description, lm.Status, 
                   h.Nama AS NAMAHEWAN, h.Spesies, 
                   ph.Nama AS NAMAPEMILIK, ph.NomorTelpon
            FROM LayananMedis lm
            JOIN Hewan h ON lm.Hewan_ID = h.ID
            JOIN PemilikHewan ph ON h.PemilikHewan_ID = ph.ID
            $whereClause
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

    $stmt = oci_parse($conn, $sql);

    // Binding parameter filter tambahan jika ada
    foreach ($filters as $column => $placeholder) {
        if (!empty($placeholder['value'])) {
            $bindValue = '%' . $placeholder['value'] . '%';
            oci_bind_by_name($stmt, ":{$placeholder['name']}", $bindValue);
        }
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
    $sqlCount = "SELECT COUNT(*) AS TOTAL 
                 FROM LayananMedis lm
                 JOIN Hewan h ON lm.Hewan_ID = h.ID
                 JOIN PemilikHewan ph ON h.PemilikHewan_ID = ph.ID
                 $whereClause";
    $stmtCount = oci_parse($conn, $sqlCount);

    // Binding parameter filter tambahan untuk count jika ada
    foreach ($filters as $column => $placeholder) {
        if (!empty($placeholder['value'])) {
            $bindValue = '%' . $placeholder['value'] . '%';
            oci_bind_by_name($stmtCount, ":{$placeholder['name']}", $bindValue);
        }
    }

    oci_execute($stmtCount);
    $rowCount = oci_fetch_assoc($stmtCount);
    $totalData = $rowCount['TOTAL'] ?? 0;
    oci_free_statement($stmtCount);

    $totalPages = ceil($totalData / $limit);
}

// Data untuk Obat
if ($tab === 'obat') {
    $filters = [
        'h.Nama' => ['name' => 'nama_hewan', 'value' => $filterNamaHewan],
        'ph.Nama' => ['name' => 'nama_pemilik', 'value' => $filterNamaPemilik]
    ];

    $whereClauseObat = buildWhereClause("WHERE ro.onDelete = 0", $filterNamaHewan || $filterNamaPemilik, $filters);

    if ($filterNamaHewan || $filterNamaPemilik) {
        $whereClauseObat .= " AND lm.Status = 'Finished'";
    }

    // Include lm.Status in SELECT to determine if the record is editable
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
                $whereClauseObat
                ORDER BY ro.Nama ASC
                OFFSET :offset ROWS FETCH NEXT :limit ROWS ONLY";

    $stmtObat = oci_parse($conn, $sqlObat);

    // Binding parameter filter tambahan jika ada
    foreach ($filters as $column => $placeholder) {
        if (!empty($placeholder['value'])) {
            $bindValue = '%' . $placeholder['value'] . '%';
            oci_bind_by_name($stmtObat, ":{$placeholder['name']}", $bindValue);
        }
    }

    oci_bind_by_name($stmtObat, ":offset", $offset);
    oci_bind_by_name($stmtObat, ":limit", $limit);

    if (!oci_execute($stmtObat)) {
        $error = oci_error($stmtObat);
        die("Terjadi kesalahan saat mengambil data obat: " . htmlentities($error['message']));
    }

    $obatList = [];
    while ($row = oci_fetch_assoc($stmtObat)) {
        $obatList[] = $row;
    }
    oci_free_statement($stmtObat);

    // Menghitung Total Data untuk Pagination Obat
    $sqlCountObat = "SELECT COUNT(*) AS TOTAL 
                     FROM ResepObat ro
                     JOIN LayananMedis lm ON ro.LayananMedis_ID = lm.ID
                     JOIN Hewan h ON lm.Hewan_ID = h.ID
                     JOIN PemilikHewan ph ON h.PemilikHewan_ID = ph.ID
                     JOIN KategoriObat ko ON ro.KategoriObat_ID = ko.ID
                     $whereClauseObat";
    $stmtCountObat = oci_parse($conn, $sqlCountObat);

    // Binding parameter filter tambahan untuk count jika ada
    foreach ($filters as $column => $placeholder) {
        if (!empty($placeholder['value'])) {
            $bindValue = '%' . $placeholder['value'] . '%';
            oci_bind_by_name($stmtCountObat, ":{$placeholder['name']}", $bindValue);
        }
    }

    oci_execute($stmtCountObat);
    $rowCountObat = oci_fetch_assoc($stmtCountObat);
    $totalDataObat = $rowCountObat['TOTAL'] ?? 0;
    oci_free_statement($stmtCountObat);

    $totalPagesObat = ceil($totalDataObat / $limit);
}

oci_close($conn);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Pet Management</title>
</head>

<body>
    <div class="container mt-5">
        <h1 class="mb-4">Pet Management</h1>

        <?php if (isset($deleteMessage)): ?>
            <?= $deleteMessage; ?>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="alert alert-success"><?= $message; ?></div>
        <?php endif; ?>

        <!-- Tab Navigation -->
        <ul class="nav nav-tabs">
            <li class="nav-item">
                <a class="nav-link <?= $tab === 'medical-services' ? 'active' : ''; ?>" href="?tab=medical-services">Layanan Medis</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $tab === 'obat' ? 'active' : ''; ?>" href="?tab=obat">Obat</a>
            </li>
        </ul>

        <div class="mt-3">
            <?php if ($tab === 'medical-services'): ?>
                <!-- Filter Form -->
                <form method="GET" action="dashboard.php" class="mb-3">
                    <input type="hidden" name="tab" value="medical-services">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label for="nama_hewan" class="form-label">Nama Hewan</label>
                            <select name="nama_hewan" id="nama_hewan" class="form-control select2">
                                <option value="">Pilih Nama Hewan</option>
                                <?php foreach ($hewanOptions as $hewan): ?>
                                    <option value="<?= htmlentities($hewan); ?>" <?= $hewan === $filterNamaHewan ? 'selected' : ''; ?>>
                                        <?= htmlentities($hewan); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="nama_pemilik" class="form-label">Nama Pemilik</label>
                            <select name="nama_pemilik" id="nama_pemilik" class="form-control select2">
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

                <!-- Tambah Layanan Medis -->
                <div class="mb-3">
                    <a href="add-medical-services.php" class="btn btn-primary">Tambah Layanan Medis</a>
                </div>

                <?php if (empty($layananMedis)): ?>
                    <div class="alert alert-info">Tidak ada data layanan medis untuk ditampilkan.</div>
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
                            <?php $nomor = $offset + 1; ?>
                            <?php foreach ($layananMedis as $layanan): ?>
                                <tr class="<?= match ($layanan['STATUS']) {
                                    'Emergency' => 'table-danger',
                                    'Finished' => 'table-success',
                                    'Scheduled' => 'table-secondary',
                                    'Canceled' => 'table-light',
                                    default => ''
                                }; ?>">
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
                                        <?php if ($layanan['STATUS'] === 'Finished' || $layanan['STATUS'] === 'Canceled'): ?>
                                            <a href="#" class="btn btn-warning btn-sm" onclick="alert('Cannot update this record.'); return false;">Update</a>
                                        <?php else: ?>
                                            <a href="update-medical-services.php?id=<?= urlencode(htmlentities($layanan['ID'])); ?>" class="btn btn-warning btn-sm">Update</a>
                                        <?php endif; ?>
                                        <a href="dashboard.php?tab=medical-services&delete_id=<?= urlencode(htmlentities($layanan['ID'])); ?>&page=<?= $page; ?>&nama_hewan=<?= urlencode($filterNamaHewan); ?>&nama_pemilik=<?= urlencode($filterNamaPemilik); ?>" 
                                           class="btn btn-danger btn-sm" 
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus layanan ini?');">Hapus</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <nav>
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?= ($page <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?tab=medical-services&page=<?= $page - 1; ?>&nama_hewan=<?= urlencode($filterNamaHewan); ?>&nama_pemilik=<?= urlencode($filterNamaPemilik); ?>">Previous</a>
                            </li>
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= ($page === $i) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?tab=medical-services&page=<?= $i; ?>&nama_hewan=<?= urlencode($filterNamaHewan); ?>&nama_pemilik=<?= urlencode($filterNamaPemilik); ?>"><?= $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?tab=medical-services&page=<?= $page + 1; ?>&nama_hewan=<?= urlencode($filterNamaHewan); ?>&nama_pemilik=<?= urlencode($filterNamaPemilik); ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
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

                <!-- Tambah Obat -->
                <div class="mb-3">
                    <a href="add-obat.php" class="btn btn-primary">Tambah Obat</a>
                </div>

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
                                        <?php if ($obat['STATUS'] === 'Finished' || $obat['STATUS'] === 'Canceled'): ?>
                                            <a href="#" class="btn btn-warning btn-sm" onclick="alert('Cannot update this record.'); return false;">Update</a>
                                        <?php else: ?>
                                            <a href="update-obat.php?id=<?= urlencode(htmlentities($obat['ID'])); ?>" class="btn btn-warning btn-sm">Update</a>
                                        <?php endif; ?>
                                        <a href="dashboard.php?tab=obat&delete_id=<?= urlencode(htmlentities($obat['ID'])); ?>&page=<?= $page; ?>&nama_hewan=<?= urlencode($filterNamaHewan); ?>&nama_pemilik=<?= urlencode($filterNamaPemilik); ?>" 
                                           class="btn btn-danger btn-sm" 
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus obat ini?');">Hapus</a>
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

    <!-- Inisialisasi Select2 -->
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                theme: 'bootstrap4',
                placeholder: "Select Option",
                allowClear: true
            });
        });
    </script>
</body>

</html>
