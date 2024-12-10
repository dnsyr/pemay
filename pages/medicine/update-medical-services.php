<?php
ob_start(); // Mulai output buffering untuk mencegah peringatan "headers already sent"

session_start();
if (!isset($_SESSION['username']) || $_SESSION['posisi'] != 'vet') {
    header("Location: ../../auth/restricted.php");
    exit();
}

include '../../config/connection.php';
include '../vet/header.php';

$id = null;

// Pastikan ID tersedia baik melalui GET maupun POST
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
}

if ($id === null || $id <= 0) {
    echo "<script>alert('ID tidak valid atau tidak ditemukan!'); window.location.href='dashboard.php';</script>";
    exit();
}

// Ambil Data Layanan Medis
$sql = "SELECT lm.ID, lm.Tanggal, lm.TotalBiaya, lm.Description, lm.Status, 
               h.Nama AS NamaHewan, h.Spesies, 
               ph.Nama AS NamaPemilik, ph.NomorTelpon
        FROM LayananMedis lm
        JOIN Hewan h ON lm.Hewan_ID = h.ID
        JOIN PemilikHewan ph ON h.PemilikHewan_ID = ph.ID
        WHERE lm.ID = :id AND lm.onDelete = 0";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ":id", $id);

if (!oci_execute($stmt)) {
    $error = oci_error($stmt);
    die("Terjadi kesalahan saat mengambil data: " . htmlentities($error['message']));
}

$layanan = oci_fetch_assoc($stmt);
oci_free_statement($stmt);

if (!$layanan) {
    echo "<script>alert('Data layanan medis tidak ditemukan!'); window.location.href='dashboard.php';</script>";
    exit();
}

// Handle Update Status Layanan Medis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $status = $_POST['status'];

    // Validasi status
    $allowed_status = ['Emergency', 'Finished', 'Scheduled', 'Canceled'];
    if (!in_array($status, $allowed_status)) {
        echo "<script>alert('Status tidak valid!');</script>";
    } else {
        $sqlUpdate = "UPDATE LayananMedis SET Status = :status WHERE ID = :id";
        $stmtUpdate = oci_parse($conn, $sqlUpdate);
        oci_bind_by_name($stmtUpdate, ":status", $status);
        oci_bind_by_name($stmtUpdate, ":id", $id);

        if (oci_execute($stmtUpdate, OCI_COMMIT_ON_SUCCESS)) {
            echo "<script>alert('Status layanan medis berhasil diperbarui!'); window.location.href='dashboard.php';</script>";
            exit();
        } else {
            $error = oci_error($stmtUpdate);
            echo "<script>alert('Gagal memperbarui status layanan medis: " . htmlentities($error['message']) . "');</script>";
        }
        oci_free_statement($stmtUpdate);
    }
}

// Handle Delete Obat
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    if ($delete_id > 0) {
        $sqlDelete = "UPDATE Obat SET onDelete = 1 WHERE ID = :id";
        $stmtDelete = oci_parse($conn, $sqlDelete);
        oci_bind_by_name($stmtDelete, ":id", $delete_id);

        if (oci_execute($stmtDelete, OCI_COMMIT_ON_SUCCESS)) {
            echo "<script>alert('Obat berhasil dihapus!'); window.location.href='update-medical-services.php?id=$id';</script>";
            exit();
        } else {
            $error = oci_error($stmtDelete);
            echo "<script>alert('Gagal menghapus obat: " . htmlentities($error['message']) . "'); window.location.href='update-medical-services.php?id=$id';</script>";
        }
        oci_free_statement($stmtDelete);
    } else {
        echo "<script>alert('ID obat tidak valid!'); window.location.href='update-medical-services.php?id=$id';</script>";
    }
}

// Handle Add Obat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_obat'])) {
    $obatNama = trim($_POST['obat_nama']);
    $obatDosis = trim($_POST['obat_dosis']);
    $obatFrekuensi = trim($_POST['obat_frekuensi']);
    $obatInstruksi = trim($_POST['obat_instruksi']);
    $kategoriObatId = intval($_POST['kategori_obat_id']); 

    // Validasi input
    if (empty($obatNama) || empty($obatDosis) || empty($obatFrekuensi) || empty($obatInstruksi) || $kategoriObatId <= 0) {
        $messageObat = "Semua bidang obat harus diisi dengan benar.";
    } else {
        // Set default HARGA (misalnya 0 atau sesuai kebutuhan)
        $obatHarga = 0;

        $sqlObat = "INSERT INTO Obat (LayananMedis_ID, Nama, Dosis, Frekuensi, Instruksi, KategoriObat_ID, Harga) 
                    VALUES (:layanan_medis_id, :nama, :dosis, :frekuensi, :instruksi, :kategori_obat_id, :harga)";
        $stmtObat = oci_parse($conn, $sqlObat);
        oci_bind_by_name($stmtObat, ':layanan_medis_id', $id);
        oci_bind_by_name($stmtObat, ':nama', $obatNama);
        oci_bind_by_name($stmtObat, ':dosis', $obatDosis);
        oci_bind_by_name($stmtObat, ':frekuensi', $obatFrekuensi);
        oci_bind_by_name($stmtObat, ':instruksi', $obatInstruksi);
        oci_bind_by_name($stmtObat, ':kategori_obat_id', $kategoriObatId);
        oci_bind_by_name($stmtObat, ':harga', $obatHarga); // Bind HARGA ke nilai default

        if (oci_execute($stmtObat, OCI_COMMIT_ON_SUCCESS)) {
            $messageObat = "Obat berhasil ditambahkan.";
        } else {
            $error = oci_error($stmtObat);
            $messageObat = "Gagal menambahkan obat: " . htmlentities($error['message']);
        }
        oci_free_statement($stmtObat);
    }
}

// Ambil Data Obat yang Terkait Layanan Medis
$sqlObatList = "SELECT o.*, ko.Nama AS \"KategoriObat\"
                FROM Obat o
                JOIN KategoriObat ko ON o.KategoriObat_ID = ko.ID
                WHERE o.LayananMedis_ID = :id AND o.onDelete = 0";
$stmtObatList = oci_parse($conn, $sqlObatList);
oci_bind_by_name($stmtObatList, ":id", $id);

if (!oci_execute($stmtObatList)) {
    $error = oci_error($stmtObatList);
    die("Terjadi kesalahan saat mengambil data obat: " . htmlentities($error['message']));
}

$obatList = [];
while ($row = oci_fetch_assoc($stmtObatList)) {
    $obatList[] = $row;
}
oci_free_statement($stmtObatList);

$obatAda = count($obatList) > 0;

// Ambil Data Kategori Obat
$sqlKategori = "SELECT * FROM KategoriObat ORDER BY Nama";
$stmtKategori = oci_parse($conn, $sqlKategori);

if (!oci_execute($stmtKategori)) {
    $error = oci_error($stmtKategori);
    die("Terjadi kesalahan saat mengambil data kategori obat: " . htmlentities($error['message']));
}

$kategoriObatList = [];
while ($kategori = oci_fetch_assoc($stmtKategori)) {
    $kategoriObatList[] = $kategori;
}
oci_free_statement($stmtKategori);

// Menentukan apakah form obat harus ditampilkan
$showObatForm = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['obat_pertanyaan']) && $_POST['obat_pertanyaan'] === 'yes') {
        $showObatForm = true;
    }
}

oci_close($conn);
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Update Layanan Medis</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Sembunyikan form obat secara default */
        .obat-form {
            display: none;
        }
    </style>
    <script>
        function toggleObatForm() {
            var pertanyaan = document.getElementById('obat_pertanyaan').value;
            var obatForm = document.getElementById('obatForm');
            if (pertanyaan === 'yes') {
                obatForm.style.display = 'block';
            } else {
                obatForm.style.display = 'none';
            }
        }

        window.onload = function () {
            toggleObatForm(); // Sesuaikan tampilan form saat halaman dimuat
            var obatPertanyaan = document.getElementById('obat_pertanyaan');
            if (obatPertanyaan) {
                obatPertanyaan.addEventListener('change', toggleObatForm);
            }
        };
    </script>
</head>

<body>
    <div class="container mt-5">
        <h1>Update Layanan Medis</h1>

        <!-- Menampilkan pesan umum -->
        <?php if (isset($message)): ?>
            <div class="alert alert-info"><?= htmlentities($message); ?></div>
        <?php endif; ?>

        <!-- Menampilkan pesan untuk operasi obat -->
        <?php if (isset($messageObat)): ?>
            <div class="alert alert-info"><?= htmlentities($messageObat); ?></div>
        <?php endif; ?>

        <!-- Formulir untuk Memperbarui Status Layanan Medis -->
        <form action="update-medical-services.php?id=<?= htmlentities($layanan['ID']); ?>" method="POST">
            <!-- Field Tanggal -->
            <div class="mb-3">
                <label for="tanggal" class="form-label">Tanggal</label>
                <input type="text" class="form-control" id="tanggal" value="<?= htmlentities($layanan['TANGGAL']); ?>" readonly>
            </div>

            <!-- Field Total Biaya -->
            <div class="mb-3">
                <label for="total_biaya" class="form-label">Total Biaya</label>
                <input type="text" class="form-control" id="total_biaya" value="Rp <?= number_format($layanan['TOTALBIAYA'], 0, ',', '.'); ?>" readonly>
            </div>

            <!-- Field Deskripsi -->
            <div class="mb-3">
                <label for="description" class="form-label">Deskripsi</label>
                <textarea class="form-control" id="description" rows="3" readonly><?= htmlentities($layanan['DESCRIPTION']); ?></textarea>
            </div>

            <!-- Field Status -->
            <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status" required>
                    <option value="Emergency" <?= $layanan['STATUS'] == 'Emergency' ? 'selected' : ''; ?>>Emergency</option>
                    <option value="Finished" <?= $layanan['STATUS'] == 'Finished' ? 'selected' : ''; ?>>Finished</option>
                    <option value="Scheduled" <?= $layanan['STATUS'] == 'Scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                    <option value="Canceled" <?= $layanan['STATUS'] == 'Canceled' ? 'selected' : ''; ?>>Canceled</option>
                </select>
            </div>

            <!-- Tombol Update Status -->
            <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
            <a href="dashboard.php" class="btn btn-secondary">Kembali</a>
        </form>

        <hr>

        <!-- Formulir untuk Menambahkan Obat -->
        <form action="update-medical-services.php?id=<?= htmlentities($layanan['ID']); ?>" method="POST">
            <!-- Pertanyaan Menambahkan Obat -->
            <div class="mb-3">
                <label class="form-label" for="obat_pertanyaan">Apakah Anda ingin menambahkan obat?</label>
                <select class="form-select" id="obat_pertanyaan" name="obat_pertanyaan" required onchange="this.form.submit()">
                    <option value="no" <?= (isset($_POST['obat_pertanyaan']) && $_POST['obat_pertanyaan'] == 'no') ? 'selected' : ''; ?>>Tidak</option>
                    <option value="yes" <?= (isset($_POST['obat_pertanyaan']) && $_POST['obat_pertanyaan'] == 'yes') ? 'selected' : ''; ?>>Ya</option>
                </select>
            </div>

            <!-- Form Obat (Ditampilkan Jika Ya) -->
            <div id="obatForm" class="obat-form">
                <h3>Tambah Obat</h3>
                <div class="mb-3">
                    <label for="obat_nama" class="form-label">Nama Obat</label>
                    <input type="text" class="form-control" id="obat_nama" name="obat_nama" required>
                </div>
                <div class="mb-3">
                    <label for="obat_dosis" class="form-label">Dosis</label>
                    <input type="text" class="form-control" id="obat_dosis" name="obat_dosis" required>
                </div>
                <div class="mb-3">
                    <label for="obat_frekuensi" class="form-label">Frekuensi</label>
                    <input type="text" class="form-control" id="obat_frekuensi" name="obat_frekuensi" required>
                </div>
                <div class="mb-3">
                    <label for="obat_instruksi" class="form-label">Instruksi</label>
                    <textarea class="form-control" id="obat_instruksi" name="obat_instruksi" rows="3" required></textarea>
                </div>
                <div class="mb-3">
                    <label for="kategori_obat_id" class="form-label">Kategori Obat</label>
                    <select class="form-select" id="kategori_obat_id" name="kategori_obat_id" required>
                        <option value="">-- Pilih Kategori Obat --</option>
                        <?php foreach ($kategoriObatList as $kategori) : ?>
                            <option value="<?= htmlentities($kategori['ID']); ?>"><?= htmlentities($kategori['NAMA']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" name="add_obat" class="btn btn-success">Tambah Obat</button>
            </div>
        </form>

        <!-- Menampilkan Daftar Obat jika ada -->
        <?php if ($obatAda): ?>
            <a href="print_resep.php?id=<?= htmlentities($id); ?>" class="btn btn-secondary mb-3" target="_blank">Print Resep</a>
            <h2 class="mt-5">Daftar Obat yang Ditambahkan</h2>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Nama Obat</th>
                        <th>Dosis</th>
                        <th>Frekuensi</th>
                        <th>Instruksi</th>
                        <th>Kategori Obat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($obatList as $obat) : ?>
                        <tr>
                            <td><?= htmlentities($obat['NAMA']); ?></td>
                            <td><?= htmlentities($obat['DOSIS']); ?></td>
                            <td><?= htmlentities($obat['FREKUENSI']); ?></td>
                            <td><?= htmlentities($obat['INSTRUKSI']); ?></td>
                            <td><?= htmlentities($obat['KategoriObat']); ?></td>
                            <td>
                                <a href="update-medical-services.php?id=<?= htmlentities($id); ?>&delete_id=<?= htmlentities($obat['ID']); ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus obat ini?')">Hapus</a>
                                <a href="update-obat.php?id=<?= htmlentities($obat['ID']); ?>" class="btn btn-warning btn-sm">Update</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>

</html>
