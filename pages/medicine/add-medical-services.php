<?php
ob_start();
session_start();
date_default_timezone_set('Asia/Jakarta'); // Set zona waktu ke Jakarta

if (!isset($_SESSION['username']) || $_SESSION['posisi'] != 'vet') {
    header("Location: ../../auth/restricted.php");
    exit();
}

include '../../config/connection.php';
include '../../layout/header.php';

// Pastikan 'employee_id' adalah string UUID
$pegawaiId = $_SESSION['employee_id'];

// Ambil data jenis layanan medis untuk ditampilkan sebagai checkbox
$sql = "SELECT * FROM JenisLayananMedis WHERE onDelete = 0";
$stmt = oci_parse($conn, $sql);
oci_execute($stmt);

$jenisLayananMedis = [];
while ($row = oci_fetch_assoc($stmt)) {
    $jenisLayananMedis[] = $row;
}
oci_free_statement($stmt);

// Ambil data hewan untuk dropdown
$sql = "SELECT DISTINCT h.ID, h.Nama AS NamaHewan, h.Spesies, ph.Nama AS NamaPemilik
        FROM Hewan h
        JOIN PemilikHewan ph ON h.PemilikHewan_ID = ph.ID
        WHERE h.onDelete = 0 AND ph.onDelete = 0";
$stmt = oci_parse($conn, $sql);
oci_execute($stmt);

$hewanList = [];
while ($row = oci_fetch_assoc($stmt)) {
    $hewanList[] = $row;
}

oci_free_statement($stmt);

// Proses tambah layanan medis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $status = $_POST['status'];
    $tanggal = $_POST['tanggal']; // Format 'YYYY-MM-DDTHH:MM'
    $totalBiaya = $_POST['total_biaya'] ? $_POST['total_biaya'] : 0;
    $description = $_POST['description'];
    $hewan_id = $_POST['hewan_id'];
    $jenisLayananArray = isset($_POST['jenis_layanan']) ? $_POST['jenis_layanan'] : [];

    // Validasi input
    if ($status !== 'Scheduled' && empty($jenisLayananArray)) {
        $error = "Jenis layanan harus dipilih.";
    } else {
        // Validasi dan sanitasi input
        $tanggal = htmlspecialchars($tanggal, ENT_QUOTES);
        $totalBiaya = floatval($totalBiaya);
        $description = htmlspecialchars($description, ENT_QUOTES);
        
        if (empty($error)) {
            // Cek pegawai_id
            $checkPegawaiSql = "SELECT COUNT(*) AS count FROM Pegawai WHERE ID = :pegawai_id AND onDelete = 0";
            $checkPegawaiStmt = oci_parse($conn, $checkPegawaiSql);
            oci_bind_by_name($checkPegawaiStmt, ':pegawai_id', $pegawaiId);
            oci_execute($checkPegawaiStmt);
            $pegawaiCount = oci_fetch_assoc($checkPegawaiStmt)['COUNT'];
            oci_free_statement($checkPegawaiStmt);

            if ($pegawaiCount == 0) {
                $error = "Pegawai yang dipilih tidak valid.";
            }

        // Cek hewan_id
        $checkHewanSql = "SELECT COUNT(*) AS count FROM Hewan WHERE ID = :hewan_id AND onDelete = 0";
        $checkHewanStmt = oci_parse($conn, $checkHewanSql);
        oci_bind_by_name($checkHewanStmt, ':hewan_id', $hewan_id);
        oci_execute($checkHewanStmt);
        $hewanCount = oci_fetch_assoc($checkHewanStmt)['COUNT'];
        oci_free_statement($checkHewanStmt);

        if ($hewanCount == 0) {
            $error = "Hewan yang dipilih tidak valid.";
        }

        // Cek hewan_id
        $checkHewanSql = "SELECT COUNT(*) AS count FROM Hewan WHERE ID = :hewan_id AND onDelete = 0";
        $checkHewanStmt = oci_parse($conn, $checkHewanSql);
        oci_bind_by_name($checkHewanStmt, ':hewan_id', $hewan_id);
        oci_execute($checkHewanStmt);
        $hewanCount = oci_fetch_assoc($checkHewanStmt)['COUNT'];
        oci_free_statement($checkHewanStmt);

        if ($hewanCount == 0) {
            $error = "Hewan yang dipilih tidak valid.";
        }

        // Cek jenis layanan hanya jika status bukan 'Scheduled'
        if ($status !== 'Scheduled' && empty($error)) {
            foreach ($jenisLayananArray as $jenisId) {
                $checkJenisSql = "SELECT COUNT(*) AS count FROM JenisLayananMedis WHERE ID = :jenis_id AND onDelete = 0";
                $checkJenisStmt = oci_parse($conn, $checkJenisSql);
                oci_bind_by_name($checkJenisStmt, ':jenis_id', $jenisId);
                oci_execute($checkJenisStmt);
                $jenisCount = oci_fetch_assoc($checkJenisStmt)['COUNT'];
                oci_free_statement($checkJenisStmt);

                if ($jenisCount == 0) {
                    $error = "Jenis layanan medis dengan ID $jenisId tidak valid.";
                    break;
                }
            }
        }
    }

        if (!isset($error)) {
            // Konversi array menjadi string untuk VARRAY (VARRAY of VARCHAR2)
            if ($status !== 'Scheduled') {
            $jenisLayananString = "ArrayJenisLayananMedis(" . implode(',', array_map(function($id) {
                return "'" . addslashes($id) . "'";
            }, $jenisLayananArray)) . ")";
        } else {
            $jenisLayananString = "ArrayJenisLayananMedis()"; // Jika scheduled, jenis layanan kosong
        }
            // Ubah format tanggal dari 'YYYY-MM-DDTHH:MM' menjadi 'YYYY-MM-DD HH24:MI:SS'
            $tanggalFormatted = str_replace('T', ' ', $tanggal) . ":00";

            // Bangun pernyataan SQL untuk prosedur
            $sql = "BEGIN CreateLayananMedis(:tanggal, :totalBiaya, :description, :status, $jenisLayananString, :pegawai_id, :hewan_id); END;";
            
            $stmt = oci_parse($conn, $sql);
            oci_bind_by_name($stmt, ':tanggal', $tanggalFormatted);
            oci_bind_by_name($stmt, ':totalBiaya', $totalBiaya);
            oci_bind_by_name($stmt, ':description', $description);
            oci_bind_by_name($stmt, ':status', $status);
            oci_bind_by_name($stmt, ':pegawai_id', $pegawaiId);
            oci_bind_by_name($stmt, ':hewan_id', $hewan_id);

            if (oci_execute($stmt, OCI_COMMIT_ON_SUCCESS)) { // Menambahkan OCI_COMMIT_ON_SUCCESS untuk otomatis commit
                $message = "Layanan medis berhasil ditambahkan.";
                header("Location: dashboard.php"); // Redirect ke halaman dashboard setelah sukses
                exit();
            } else {
                $ociError = oci_error($stmt);
                // Log error ke file log server
                error_log("Gagal menambahkan layanan medis: " . $ociError['message']);
                // Tampilkan pesan error yang ramah pengguna
                echo "<script>alert('Gagal menambahkan layanan medis: " . htmlentities($ociError['message']) . "');</script>";
                $error = $ociError['message'];
            }

            oci_free_statement($stmt);
        }
    }

    ob_end_flush();
    oci_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Tambah Layanan Medis</title>
    <link rel="stylesheet" href="../../public/css/index.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

</head>
<body>
    <div class="container mt-5">
        <h1>Tambah Layanan Medis</h1>
        <?php if (isset($message)): ?>
    <div class="alert alert-info"><?= htmlentities($message); ?></div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?= htmlentities($error); ?></div>
<?php endif; ?>

        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status" required>
                    <option value="Emergency">Emergency</option>
                    <option value="Finished">Finished</option>
                    <option value="Scheduled">Scheduled</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="tanggal" class="form-label">Tanggal</label>
                <input type="datetime-local" class="form-control" id="tanggal" name="tanggal" value="<?= date('Y-m-d\TH:i'); ?>" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Deskripsi</label>
                <textarea class="form-control" id="description" name="description" required></textarea>
            </div>
            <div class="mb-3">
                <label for="hewan_id" class="form-label">Hewan</label>
                <select class="form-select" id="hewan_id" name="hewan_id" required>
                    <?php foreach ($hewanList as $hewan): ?>
                        <option value="<?= htmlentities($hewan['ID']); ?>">
                            <?= htmlentities($hewan['NAMAHEWAN'] . ' (' . $hewan['SPESIES'] . ') - ' . $hewan['NAMAPEMILIK']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <!-- Jenis Layanan dan Total Biaya -->
        <div id="jenisLayananSection">
                <div class="mb-3">
                    <label for="jenis_layanan" class="form-label">Jenis Layanan</label>
                    <div>
                        <?php foreach ($jenisLayananMedis as $layanan): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="jenis_layanan[]" 
                                       id="layanan_<?= htmlentities($layanan['ID']); ?>" value="<?= htmlentities($layanan['ID']); ?>" 
                                       data-biaya="<?= htmlentities($layanan['BIAYA']); ?>">
                                <label class="form-check-label" for="layanan_<?= htmlentities($layanan['ID']); ?>">
                                    <?= htmlentities($layanan['NAMA']); ?> - Biaya: Rp <?= number_format($layanan['BIAYA'], 0, ',', '.'); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div id="totalBiayaSection">
                <div class="mb-3">
                    <label for="total_biaya" class="form-label">Total Biaya</label>
                    <input type="number" class="form-control" id="total_biaya" name="total_biaya" readonly>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Tambah Layanan Medis</button>
        </form>
    </div>
</body>
<script>
    document.getElementById('status').addEventListener('change', function() {
            const status = this.value;
            const jenisLayananSection = document.getElementById('jenisLayananSection');
            const totalBiayaSection = document.getElementById('totalBiayaSection');
            if (status === 'Scheduled') {
                jenisLayananSection.style.display = 'none';
                totalBiayaSection.style.display = 'none';
            } else {
                jenisLayananSection.style.display = 'block';
                totalBiayaSection.style.display = 'block';
            }
        });

        document.querySelectorAll('input[name="jenis_layanan[]"]').forEach((checkbox) => {
            checkbox.addEventListener('change', function() {
                let total = 0;
                document.querySelectorAll('input[name="jenis_layanan[]"]:checked').forEach((checkedBox) => {
                    total += parseFloat(checkedBox.getAttribute('data-biaya'));
                });
                document.getElementById('total_biaya').value = total;
            });
        });
    </script>
</html>