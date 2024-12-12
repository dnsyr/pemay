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
if (isset($_GET['id'])) {
    $id = trim($_GET['id']);
} elseif (isset($_POST['id'])) {
    $id = trim($_POST['id']);
}

// Validasi ID
if ($id === null || empty($id)) {
    echo "<script>alert('ID tidak valid atau tidak ditemukan!'); window.location.href='dashboard.php';</script>";
    exit();
}

// Validasi format ID (UUID)
if (!preg_match('/^[a-f0-9\-]{36}$/i', $id)) {
    echo "<script>alert('Format ID tidak valid!'); window.location.href='dashboard.php';</script>";
    exit();
}

// Ambil Data Layanan Medis dengan semua parameter yang dibutuhkan
$sql = "SELECT lm.ID, lm.Tanggal, lm.TotalBiaya, lm.Description, lm.Status, 
               lm.Pegawai_ID, lm.Hewan_ID,
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

// Ambil Jenis Layanan Medis yang sudah dipilih
$sqlJenisLayanan = "SELECT COLUMN_VALUE AS JENISLAYANAN_ID
                    FROM TABLE((SELECT JENISLAYANAN FROM LayananMedis WHERE ID = :id AND onDelete = 0))";
$stmtJenisLayanan = oci_parse($conn, $sqlJenisLayanan);
oci_bind_by_name($stmtJenisLayanan, ":id", $id);

if (!oci_execute($stmtJenisLayanan)) {
    $error = oci_error($stmtJenisLayanan);
    die("Terjadi kesalahan saat mengambil data jenis layanan: " . htmlentities($error['message']));
}

$currentJenisLayanan = [];
while ($row = oci_fetch_assoc($stmtJenisLayanan)) {
    $currentJenisLayanan[] = $row['JENISLAYANAN_ID'];
}
oci_free_statement($stmtJenisLayanan);

// Ambil semua jenis layanan medis untuk ditampilkan sebagai checkbox
$sqlJenisLayananAll = "SELECT ID, Nama, Biaya FROM JenisLayananMedis WHERE onDelete = 0 ORDER BY Nama";
$stmtJenisLayananAll = oci_parse($conn, $sqlJenisLayananAll);
oci_execute($stmtJenisLayananAll);

$jenisLayananMedis = [];
while ($row = oci_fetch_assoc($stmtJenisLayananAll)) {
    $jenisLayananMedis[] = $row;
}
oci_free_statement($stmtJenisLayananAll);

// Initialize error and message variables
$error = null;
$message = null;
$messageObat = null;

// Handle Update Layanan Medis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_layanan'])) {
    // Retrieve 'status' safely
    $status = isset($_POST['status']) ? trim($_POST['status']) : null;
echo $status;
    if ($status === null) {
        $error = "Status tidak dikirimkan.";
    } else {
        $jenisLayananArray = isset($_POST['jenis_layanan']) ? $_POST['jenis_layanan'] : [];
        $totalBiaya = 0;
    }
        // Validasi status
        $allowed_status = ['Emergency', 'Finished', 'Scheduled', 'Canceled'];
        if (!in_array($status, $allowed_status)) {
            $error = "Status tidak valid!";
        } else {
            // Validasi jenis layanan jika status bukan 'Scheduled'
            if ($status !== 'Scheduled' && empty($jenisLayananArray)) {
                $error = "Jenis layanan harus dipilih.";
            } else {
                // Validasi setiap jenis layanan dan hitung total biaya
                if ($status !== 'Scheduled') {
                    foreach ($jenisLayananArray as $jenisId) {
                        $validJenis = false;
                        foreach ($jenisLayananMedis as $layananJenis) {
                            if ($layananJenis['ID'] == $jenisId) {
                                $validJenis = true;
                                $totalBiaya += $layananJenis['BIAYA'];
                                break;
                            }
                        }
                        if (!$validJenis) {
                            $error = "Jenis layanan medis dengan ID $jenisId tidak valid.";
                            break;
                        }
                    }
                }

                if (!isset($error)) {
                    // Retrieve 'tanggal' from POST data
                    $tanggal_input = isset($_POST['tanggal']) ? trim($_POST['tanggal']) : null;

                    if (empty($tanggal_input)) {
                        $error = "Tanggal tidak boleh kosong.";
                    } else {
                        // Convert 'tanggal' to 'YYYY-MM-DD HH:MM:SS' format
                        $tanggal = date('Y-m-d H:i:s', strtotime($tanggal_input));

                        // Debugging: Log the 'Tanggal' value
                        error_log("Tanggal Value: " . $tanggal);

                        // Prepare p_JenisLayanan as a collection
                        $jenisLayananCollection = oci_new_collection($conn, 'ARRAYJENISLAYANANMEDIS'); // Ensure the type name matches exactly

                        if ($status !== 'Scheduled') {
                            foreach ($jenisLayananArray as $jenisId) {
                                $jenisLayananCollection->append($jenisId);
                            }
                        }
                        // Else, leave the collection empty

                        // Bangun pernyataan SQL untuk prosedur update
                        $sqlUpdate = "BEGIN 
                                        UpdateLayananMedis(
                                            :id, 
                                            TO_TIMESTAMP(:tanggal, 'YYYY-MM-DD HH24:MI:SS'), 
                                            :totalBiaya, 
                                            :description, 
                                            :status, 
                                            :jenisLayanan, 
                                            :pegawai_id, 
                                            :hewan_id
                                        ); 
                                      END;";

                        // Bind semua parameter yang dibutuhkan
                        $stmtUpdate = oci_parse($conn, $sqlUpdate);
                        oci_bind_by_name($stmtUpdate, ":id", $id);
                        oci_bind_by_name($stmtUpdate, ":tanggal", $tanggal);
                        oci_bind_by_name($stmtUpdate, ":totalBiaya", $totalBiaya);
                        oci_bind_by_name($stmtUpdate, ":description", $layanan['DESCRIPTION']);
                        oci_bind_by_name($stmtUpdate, ":status", $status);
                        oci_bind_by_name($stmtUpdate, ":pegawai_id", $layanan['PEGAWAI_ID']);
                        oci_bind_by_name($stmtUpdate, ":hewan_id", $layanan['HEWAN_ID']);

                        // Bind the collection
                        if ($status !== 'Scheduled') {
                            oci_bind_by_name($stmtUpdate, ":jenisLayanan", $jenisLayananCollection, -1, OCI_B_NTY);
                        } else {
                            // Bind an empty collection
                            $emptyCollection = oci_new_collection($conn, 'ARRAYJENISLAYANANMEDIS'); // Ensure this matches your collection type
                            oci_bind_by_name($stmtUpdate, ":jenisLayanan", $emptyCollection, -1, OCI_B_NTY);
                        }

                        // Execute the statement
                        if (oci_execute($stmtUpdate, OCI_COMMIT_ON_SUCCESS)) {
                            echo "<script>alert('Layanan medis berhasil diperbarui!'); window.location.href='update-medical-services.php?id=$id';</script>";
                            exit();
                        } else {
                            $ociError = oci_error($stmtUpdate);
                            // Log the error for debugging
                            error_log("OCI Execute Error: " . print_r($ociError, true));
                            $error = "Gagal memperbarui layanan medis: " . htmlentities($ociError['message']);
                        }
                        oci_free_statement($stmtUpdate);
                    }
                }
            }
        }
    }

    // Handle Delete Obat
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['delete_id'])) {
        $delete_id = trim($_GET['delete_id']);
        
        // Validasi format delete_id (UUID)
        if (!preg_match('/^[a-f0-9\-]{36}$/i', $delete_id)) {
            echo "<script>alert('Format ID obat tidak valid!'); window.location.href='update-medical-services.php?id=$id';</script>";
            exit();
        }
    
        if (!empty($delete_id)) {
            $sqlDelete = "UPDATE ResepObat SET onDelete = 1 WHERE ID = :id";
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
        $kategoriObatId = trim($_POST['kategori_obat_id']); 

        // Validasi input
        if (empty($obatNama) || empty($obatDosis) || empty($obatFrekuensi) || empty($obatInstruksi) || empty($kategoriObatId)) {
            $messageObat = "Semua bidang obat harus diisi dengan benar.";
        } else {
            // Pastikan kategoriObatId adalah UUID yang valid
            if (!preg_match('/^[a-f0-9\-]{36}$/i', $kategoriObatId)) {
                $messageObat = "Format Kategori Obat tidak valid.";
            } else {
                // Set default HARGA (misalnya 0 atau sesuai kebutuhan)
                $obatHarga = 0;

                // Menggunakan sequence 'RESEPOBAT_SEQ' untuk mendapatkan ID baru
                $sqlGetId = "SELECT RESEPOBAT_SEQ.NEXTVAL AS new_id FROM dual";
                $stmtGetId = oci_parse($conn, $sqlGetId);
                if (!oci_execute($stmtGetId)) {
                    $error = oci_error($stmtGetId);
                    $messageObat = "Gagal mendapatkan ID baru untuk obat: " . htmlentities($error['message']);
                } else {
                    $row = oci_fetch_assoc($stmtGetId);
                    $newObatId = 'A' . str_pad($row['NEW_ID'], 10, '0', STR_PAD_LEFT); // Contoh pembentukan ID
                    oci_free_statement($stmtGetId);

                    // Insert Obat
                    $sqlObat = "INSERT INTO ResepObat (ID, LayananMedis_ID, Nama, Dosis, Frekuensi, Instruksi, KategoriObat_ID, Harga, onDelete) 
                                VALUES (:id, :layanan_medis_id, :nama, :dosis, :frekuensi, :instruksi, :kategori_obat_id, :harga, 0)";
                    $stmtObat = oci_parse($conn, $sqlObat);
                    oci_bind_by_name($stmtObat, ':id', $newObatId);
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
        }
    }

    // Ambil Data Obat yang Terkait Layanan Medis
    $sqlObatList = "SELECT o.ID, o.Nama, o.Dosis, o.Frekuensi, o.Instruksi, ko.Nama AS KategoriObat
                    FROM ResepObat o
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
    $sqlKategori = "SELECT ID, Nama FROM KategoriObat ORDER BY Nama";
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
        function toggleJenisLayananSection() {
            const status = document.getElementById('status').value;
            const jenisLayananSection = document.getElementById('jenisLayananSection');
            const totalBiayaSection = document.getElementById('totalBiayaSection');
            if (status === 'Scheduled') {
                jenisLayananSection.style.display = 'none';
                totalBiayaSection.style.display = 'none';
            } else {
                jenisLayananSection.style.display = 'block';
                totalBiayaSection.style.display = 'block';
            }
        }

        window.onload = function () {
            toggleJenisLayananSection(); // Sesuaikan tampilan saat halaman dimuat

            document.getElementById('status').addEventListener('change', function () {
                toggleJenisLayananSection();
                updateTotalBiaya();
            });

            document.querySelectorAll('input[name="jenis_layanan[]"]').forEach((checkbox) => {
                checkbox.addEventListener('change', function () {
                    let total = 0;
                    document.querySelectorAll('input[name="jenis_layanan[]"]:checked').forEach((checkedBox) => {
                        total += parseFloat(checkedBox.getAttribute('data-biaya'));
                    });
                    document.getElementById('total_biaya').value = total;
                });
            });
        };

        function updateTotalBiaya() {
            let total = 0;
            document.querySelectorAll('input[name="jenis_layanan[]"]:checked').forEach((checkedBox) => {
                total += parseFloat(checkedBox.getAttribute('data-biaya'));
            });
            document.getElementById('total_biaya').value = total;
        }

        document.addEventListener('DOMContentLoaded', function() {
            const tanggalInput = document.getElementById('tanggal');
            const now = new Date();
            const mindate= now.toISOString().slice(0, 16);
            tanggalInput.setAttribute('min', mindate);
        });
    </script>
</head>

<body>
    <div class="container mt-5">
        <h1>Update Layanan Medis</h1>

        <!-- Menampilkan pesan kesalahan -->
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlentities($error); ?></div>
        <?php endif; ?>

        <!-- Menampilkan pesan umum -->
        <?php if (isset($message)): ?>
            <div class="alert alert-info"><?= htmlentities($message); ?></div>
        <?php endif; ?>

        <!-- Menampilkan pesan untuk operasi obat -->
        <?php if (isset($messageObat)): ?>
            <div class="alert alert-info"><?= htmlentities($messageObat); ?></div>
        <?php endif; ?>

        <!-- Formulir untuk Memperbarui Status dan Jenis Layanan Medis -->
        <form action="dashboard.php?id=<?= htmlentities($layanan['ID']); ?>" method="POST">
            <input type="hidden" name="id" value="<?= htmlentities($layanan['ID']); ?>">

            <!-- Field Tanggal -->
            <div class="mb-3">
                <label for="tanggal" class="form-label">Tanggal</label>
                <input type="datetime-local" class="form-control" id="tanggal" name="tanggal" value="<?= date('Y-m-d\TH:i'); ?>" required min="<?= date('Y-m-d\TH:i'); ?>">
            </div>

            <!-- Field Deskripsi -->
            <div class="mb-3">
                <label for="description" class="form-label">Deskripsi</label>
                <textarea class="form-control" id="description" rows="3"><?= htmlentities($layanan['DESCRIPTION']); ?></textarea>
            </div>

            <!-- Field Status -->
            <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status" required>
                    <option value="Emergency" <?= ($layanan['STATUS'] == 'Emergency') ? 'selected' : ''; ?>>Emergency</option>
                    <option value="Finished" <?= ($layanan['STATUS'] == 'Finished') ? 'selected' : ''; ?>>Finished</option>
                    <option value="Scheduled" <?= ($layanan['STATUS'] == 'Scheduled') ? 'selected' : ''; ?>>Scheduled</option>
                    <option value="Canceled" <?= ($layanan['STATUS'] == 'Canceled') ? 'selected' : ''; ?>>Canceled</option>
                </select>
            </div>

            <!-- Jenis Layanan dan Total Biaya (Ditampilkan Jika Status Tidak 'Scheduled') -->
            <div id="jenisLayananSection" style="<?= ($layanan['STATUS'] !== 'Scheduled') ? 'display:block;' : 'display:none;'; ?>">
                <div class="mb-3">
                    <label for="jenis_layanan" class="form-label">Jenis Layanan</label>
                    <div>
                        <?php foreach ($jenisLayananMedis as $layananJenis): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="jenis_layanan[]" 
                                       id="layanan_<?= htmlentities($layananJenis['ID']); ?>" value="<?= htmlentities($layananJenis['ID']); ?>" 
                                       <?= in_array($layananJenis['ID'], $currentJenisLayanan) ? 'checked' : ''; ?> 
                                       data-biaya="<?= htmlentities($layananJenis['BIAYA']); ?>">
                                <label class="form-check-label" for="layanan_<?= htmlentities($layananJenis['ID']); ?>">
                                    <?= htmlentities($layananJenis['NAMA']); ?> - Biaya: Rp <?= number_format($layananJenis['BIAYA'], 0, ',', '.'); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div id="totalBiayaSection" style="<?= ($layanan['STATUS'] !== 'Scheduled') ? 'display:block;' : 'display:none;'; ?>">
                <div class="mb-3">
                    <label for="total_biaya" class="form-label">Total Biaya</label>
                    <input type="number" class="form-control" id="total_biaya" name="totalBiaya" readonly value="<?= ($layanan['STATUS'] !== 'Scheduled') ? $layanan['TOTALBIAYA'] : 0; ?>">
                </div>
            </div>

            <!-- Tombol Update Layanan Medis -->
            <button type="submit" name="update_layanan" class="btn btn-primary">Update Layanan Medis</button>
            <a href="dashboard.php" class="btn btn-secondary">Kembali</a>
        </form>

        <hr>

        <!-- Formulir untuk Menambahkan Obat -->
        <form action="update-medical-services.php?id=<?= htmlentities($layanan['ID']); ?>" method="POST">
            <input type="hidden" name="id" value="<?= htmlentities($layanan['ID']); ?>">

            <!-- Pertanyaan Menambahkan Obat -->
            <div class="mb-3">
                <label class="form-label" for="obat_pertanyaan">Apakah Anda ingin menambahkan obat?</label>
                <select class="form-select" id="obat_pertanyaan" name="obat_pertanyaan" required onchange="this.form.submit()">
                    <option value="no" <?= (isset($_POST['obat_pertanyaan']) && $_POST['obat_pertanyaan'] == 'no') ? 'selected' : ''; ?>>Tidak</option>
                    <option value="yes" <?= (isset($_POST['obat_pertanyaan']) && $_POST['obat_pertanyaan'] == 'yes') ? 'selected' : ''; ?>>Ya</option>
                </select>
            </div>

            <!-- Form Obat (Ditampilkan Jika Ya) -->
            <div id="obatForm" class="obat-form" style="<?= $showObatForm ? 'display:block;' : 'display:none;'; ?>">
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
                            <td><?= htmlentities($obat['KATEGORIOBAT']); ?></td>
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