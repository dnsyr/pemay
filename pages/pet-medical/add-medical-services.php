<?php
ob_start();
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['username']) || $_SESSION['posisi'] != 'vet') {
    header("Location: ../../auth/restricted.php");
    exit();
}

include '../../config/database.php';
$db = new Database();
$pegawaiId = $_SESSION['employee_id'];

// Ambil data jenis layanan medis untuk checkbox
$jenisLayananMedis = $db->query("SELECT * FROM JenisLayananMedis WHERE onDelete = 0");

// Ambil data hewan untuk dropdown
$hewanList = $db->query("SELECT DISTINCT h.ID, h.Nama AS NamaHewan, h.Spesies, ph.Nama AS NamaPemilik
                         FROM Hewan h
                         JOIN PemilikHewan ph ON h.PemilikHewan_ID = ph.ID
                         WHERE h.onDelete = 0 AND ph.onDelete = 0");

// Ambil Data Kategori Obat
$kategoriObatList = $db->query("SELECT ID, Nama FROM KategoriObat WHERE onDelete = 0 ORDER BY Nama");

// Menentukan apakah form obat harus ditampilkan
$showObatForm = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['obat_pertanyaan']) && $_POST['obat_pertanyaan'] === 'yes') {
        $showObatForm = true;
    }
}

// Initialize variables
$error = null;
$message = null;
$messageObat = null;
$obatList = [];
$id = null;

// Proses tambah layanan medis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $status = $_POST['status'];
    $tanggal = $_POST['tanggal'];
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
            // Konversi array untuk VARRAY
            if ($status !== 'Scheduled') {
                $jenisLayananString = "ArrayJenisLayananMedis(" . implode(',', array_map(function($id) {
                    return "'" . addslashes($id) . "'";
                }, $jenisLayananArray)) . ")";
            } else {
                $jenisLayananString = "ArrayJenisLayananMedis()";
            }

            $tanggalFormatted = str_replace('T', ' ', $tanggal) . ":00";

            // Eksekusi CreateLayananMedis
            $sql = "BEGIN CreateLayananMedis(:tanggal, :totalBiaya, :description, :status, $jenisLayananString, :pegawai_id, :hewan_id); END;";
            $stmt = oci_parse($conn, $sql);
            oci_bind_by_name($stmt, ':tanggal', $tanggalFormatted);
            oci_bind_by_name($stmt, ':totalBiaya', $totalBiaya);
            oci_bind_by_name($stmt, ':description', $description);
            oci_bind_by_name($stmt, ':status', $status);
            oci_bind_by_name($stmt, ':pegawai_id', $pegawaiId);
            oci_bind_by_name($stmt, ':hewan_id', $hewan_id);

            if (oci_execute($stmt, OCI_COMMIT_ON_SUCCESS)) {
                // Get ID of newly created LayananMedis
                $sqlGetId = "SELECT ID FROM LayananMedis WHERE Pegawai_ID = :pegawai_id AND Hewan_ID = :hewan_id 
                             AND ROWNUM = 1 ORDER BY Tanggal DESC";
                $stmtGetId = oci_parse($conn, $sqlGetId);
                oci_bind_by_name($stmtGetId, ':pegawai_id', $pegawaiId);
                oci_bind_by_name($stmtGetId, ':hewan_id', $hewan_id);
                oci_execute($stmtGetId);
                $row = oci_fetch_assoc($stmtGetId);
                $id = $row['ID'];
                oci_free_statement($stmtGetId);
            
                // Ambil data obat yang terkait
                $sqlObatList = "SELECT o.ID, o.Nama, o.Dosis, o.Frekuensi, o.Instruksi, ko.Nama AS KategoriObat
                                FROM ResepObat o
                                JOIN KategoriObat ko ON o.KategoriObat_ID = ko.ID
                                WHERE o.LayananMedis_ID = :id AND o.onDelete = 0";
                $stmtObatList = oci_parse($conn, $sqlObatList);
                oci_bind_by_name($stmtObatList, ":id", $id);
                oci_execute($stmtObatList);
            
                $obatList = [];
                while ($row = oci_fetch_assoc($stmtObatList)) {
                    $obatList[] = $row;
                }
                oci_free_statement($stmtObatList);
            
                $obatAda = count($obatList) > 0;
            } else {
                $ociError = oci_error($stmt);
                error_log("Gagal menambahkan layanan medis: " . $ociError['message']);
                $error = $ociError['message'];
            }
            oci_free_statement($stmt);
        }
    }
}

// Handle Add Obat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_obat'])) {
    $obatNama = trim($_POST['obat_nama']);
    $obatDosis = trim($_POST['obat_dosis']);
    $obatFrekuensi = trim($_POST['obat_frekuensi']);
    $obatInstruksi = trim($_POST['obat_instruksi']);
    $kategoriObatId = trim($_POST['kategori_obat_id']);

    if (empty($obatNama) || empty($obatDosis) || empty($obatFrekuensi) || empty($obatInstruksi) || empty($kategoriObatId)) {
        $messageObat = "Semua bidang obat harus diisi.";
    } else {
        $obatHarga = 0;

        // Get new ID for obat
        $sqlGetId = "SELECT RESEPOBAT_SEQ.NEXTVAL AS new_id FROM dual";
        $stmtGetId = oci_parse($conn, $sqlGetId);
        
        if (oci_execute($stmtGetId)) {
            $row = oci_fetch_assoc($stmtGetId);
            $newObatId = 'A' . str_pad($row['NEW_ID'], 10, '0', STR_PAD_LEFT);
            
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
            oci_bind_by_name($stmtObat, ':harga', $obatHarga);

            if (oci_execute($stmtObat, OCI_COMMIT_ON_SUCCESS)) {
                $messageObat = "Obat berhasil ditambahkan.";
                
                // Refresh obat list
                $sqlObatList = "SELECT o.ID, o.Nama, o.Dosis, o.Frekuensi, o.Instruksi, ko.Nama AS KategoriObat
                               FROM ResepObat o
                               JOIN KategoriObat ko ON o.KategoriObat_ID = ko.ID
                               WHERE o.LayananMedis_ID = :id AND o.onDelete = 0";
                $stmtObatList = oci_parse($conn, $sqlObatList);
                oci_bind_by_name($stmtObatList, ":id", $id);
                oci_execute($stmtObatList);

                $obatList = [];
                while ($row = oci_fetch_assoc($stmtObatList)) {
                    $obatList[] = $row;
                }
                oci_free_statement($stmtObatList);
            } else {
                $error = oci_error($stmtObat);
                $messageObat = "Gagal menambahkan obat: " . htmlentities($error['message']);
            }
            oci_free_statement($stmtObat);
        } else {
            $error = oci_error($stmtGetId);
            $messageObat = "Gagal mendapatkan ID baru: " . htmlentities($error['message']);
        }
        oci_free_statement($stmtGetId);
    }
}

ob_end_flush();
oci_close($conn);
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <title>Tambah Layanan Medis</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.4.19/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>
    <div class="container mx-auto p-6">
        <h1 class="text-2xl font-bold mb-6">Tambah Layanan Medis</h1>

        <?php if (isset($message)): ?>
            <div class="alert alert-info mb-4">
                <span><?= htmlentities($message); ?></span>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error mb-4">
                <span><?= htmlentities($error); ?></span>
            </div>
        <?php endif; ?>

        <!-- Form Layanan Medis -->
        <div class="p-4 w-full">
    <form method="POST" class="space-y-4">
        <input type="hidden" name="action" value="add">
        
        <div class="form-control w-full">
            <label class="label">
                <span class="label-text">Status</span>
            </label>
            <select class="select select-bordered w-full" id="status" name="status" required>
                <option value="Emergency">Emergency</option>
                <option value="Finished">Finished</option>
                <option value="Scheduled">Scheduled</option>
            </select>
        </div>

        <div class="form-control w-full">
            <label class="label">
                <span class="label-text">Tanggal</span>
            </label>
            <input type="datetime-local" class="input input-bordered w-full" 
                   id="tanggal" name="tanggal" 
                   value="<?= date('Y-m-d\TH:i'); ?>" 
                   required min="<?= date('Y-m-d\TH:i'); ?>">
        </div>

        <div class="form-control w-full">
            <label class="label">
                <span class="label-text">Deskripsi</span>
            </label>
            <textarea class="textarea textarea-bordered h-24" 
                      id="description" name="description" required></textarea>
        </div>

        <div class="form-control w-full">
            <label class="label">
                <span class="label-text">Hewan</span>
            </label>
            <select class="select select-bordered w-full" id="hewan_id" name="hewan_id" required>
                <?php foreach ($hewanList as $hewan): ?>
                    <option value="<?= htmlentities($hewan['ID']); ?>">
                        <?= htmlentities($hewan['NAMAHEWAN'] . ' (' . $hewan['SPESIES'] . ') - ' . $hewan['NAMAPEMILIK']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div id="jenisLayananSection">
            <label class="label">
                <span class="label-text">Jenis Layanan</span>
            </label>
            <div class="space-y-2">
                <?php foreach ($jenisLayananMedis as $layanan): ?>
                    <div class="form-control">
                        <label class="label cursor-pointer justify-start gap-4">
                            <input type="checkbox" class="checkbox" 
                                   name="jenis_layanan[]" 
                                   value="<?= htmlentities($layanan['ID']); ?>" 
                                   data-biaya="<?= htmlentities($layanan['BIAYA']); ?>">
                            <span class="label-text"><?= htmlentities($layanan['NAMA']); ?> 
                                - Biaya: Rp <?= number_format($layanan['BIAYA'], 0, ',', '.'); ?></span>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div id="totalBiayaSection" class="form-control w-full">
            <label class="label">
                <span class="label-text">Total Biaya</span>
            </label>
            <input type="number" class="input input-bordered w-full" 
                   id="total_biaya" name="total_biaya" readonly>
        </div>

        <div class="form-control w-full">
            <label class="label">
                <span class="label-text">Apakah memerlukan obat?</span>
            </label>
            <select class="select select-bordered w-full" 
                    id="obat_pertanyaan" name="obat_pertanyaan" required>
                <option value="no">Tidak</option>
                <option value="yes">Ya</option>
            </select>
        </div>

            <div id="obatForm" class="hidden space-y-4">
            <!-- Form input obat -->
            <div class="form-control w-full">
                <label class="label">
                    <span class="label-text">Nama Obat</span>
                </label>
                <input type="text" class="input input-bordered w-full" 
                       id="obat_nama" name="obat_nama">
            </div>

                <div class="form-control w-full mb-4">
                    <label class="label">
                        <span class="label-text">Dosis</span>
                    </label>
                    <input type="text" class="input input-bordered w-full" id="obat_dosis" name="obat_dosis">
                </div>

                <div class="form-control w-full mb-4">
                    <label class="label">
                        <span class="label-text">Frekuensi</span>
                    </label>
                    <input type="text" class="input input-bordered w-full" id="obat_frekuensi" name="obat_frekuensi">
                </div>

                <div class="form-control w-full mb-4">
                    <label class="label">
                        <span class="label-text">Instruksi</span>
                    </label>
                    <textarea class="textarea textarea-bordered h-24" id="obat_instruksi" name="obat_instruksi"></textarea>
                </div>

                <div class="form-control w-full mb-4">
                    <label class="label">
                        <span class="label-text">Kategori Obat</span>
                    </label>
                    <select class="select select-bordered w-full" id="kategori_obat_id" name="kategori_obat_id">
                        <option value="">-- Pilih Kategori Obat --</option>
                        <?php foreach ($kategoriObatList as $kategori) : ?>
                            <option value="<?= htmlentities($kategori['ID']); ?>"><?= htmlentities($kategori['NAMA']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary w-full">Simpan</button>
    </form>

    <!-- Daftar Obat jika ada -->
    <?php if (!empty($obatList)): ?>
        <div class="divider">Daftar Obat</div>
        <div class="overflow-x-auto">
            <table class="table table-zebra w-full">
                <thead>
                    <tr>
                        <th>Nama Obat</th>
                        <th>Dosis</th>
                        <th>Frekuensi</th>
                        <th>Kategori</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($obatList as $obat): ?>
                        <tr>
                            <td><?= htmlentities($obat['NAMA']); ?></td>
                            <td><?= htmlentities($obat['DOSIS']); ?></td>
                            <td><?= htmlentities($obat['FREKUENSI']); ?></td>
                            <td><?= htmlentities($obat['KATEGORIOBAT']); ?></td>
                            <td>
                                <div class="join">
                                    <a href="update-obat.php?id=<?= htmlentities($obat['ID']); ?>" 
                                       class="btn btn-warning btn-xs join-item">Update</a>
                                    <button onclick="deleteObat('<?= htmlentities($obat['ID']); ?>')" 
                                            class="btn btn-error btn-xs join-item">Hapus</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

    <script>
        // Menambahkan event listener saat status berubah
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

        // Update total biaya ketika ada perubahan di jenis layanan
        document.querySelectorAll('input[name="jenis_layanan[]"]').forEach((checkbox) => {
            checkbox.addEventListener('change', function() {
                let total = 0;
                document.querySelectorAll('input[name="jenis_layanan[]"]:checked').forEach((checkedBox) => {
                    total += parseFloat(checkedBox.getAttribute('data-biaya'));
                });
                document.getElementById('total_biaya').value = total;
            });
        });

        // Validasi sebelum submit form
        document.querySelector('form').addEventListener('submit', function(event) {
            const status = document.getElementById('status').value;
            const jenisLayanan = document.querySelectorAll('input[name="jenis_layanan[]"]:checked').length;

            if (status !== 'Scheduled' && jenisLayanan === 0) {
                event.preventDefault();
                alert('Silakan pilih jenis layanan medis.');
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const tanggalInput = document.getElementById('tanggal');
            const now = new Date();
            const mindate = now.toISOString().slice(0, 16);
            tanggalInput.setAttribute('min', mindate);
        });

        document.getElementById('obat_pertanyaan').addEventListener('change', function() {
        const obatForm = document.getElementById('obatForm');
        if (this.value === 'yes') {
            obatForm.classList.remove('hidden');
        } else {
            obatForm.classList.add('hidden');
        }
    });

    function deleteObat(id) {
        if (confirm('Apakah Anda yakin ingin menghapus obat ini?')) {
            window.location.href = `delete-medical.php?tab=obat&delete_id=${id}`;
        }
    }
    </script>
</body>
</html>