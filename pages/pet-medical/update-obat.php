<?php
require_once '../../config/database.php';
require_once '../../config/connection.php';

// Proses update jika ada POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id = $_POST['id'];
        $nama = $_POST['nama'];
        $dosis = $_POST['dosis'];
        $frekuensi = $_POST['frekuensi'];
        $instruksi = $_POST['instruksi'];
        $kategoriObat = $_POST['kategori_obat'];

        // Debug: Log POST data
        error_log("POST Data: " . print_r($_POST, true));

        // Gunakan prepared statement dengan PDO
        $db = new Database();
        $query = "UPDATE ResepObat 
                 SET Nama = :nama, 
                     Dosis = :dosis, 
                     Frekuensi = :frekuensi, 
                     Instruksi = :instruksi, 
                     KategoriObat_ID = :kategori_obat 
                 WHERE ID = :id AND onDelete = 0";

        $db->query($query);
        $db->bind(':id', $id);
        $db->bind(':nama', $nama);
        $db->bind(':dosis', $dosis);
        $db->bind(':frekuensi', $frekuensi);
        $db->bind(':instruksi', $instruksi);
        $db->bind(':kategori_obat', $kategoriObat);

        if ($db->execute()) {
            // Tampilkan pesan sukses dan redirect setelah 2 detik
            echo '<div class="alert alert-success">Data obat berhasil diperbarui</div>';
            echo '<script>
                setTimeout(function() {
                    window.location.href = "dashboard.php?tab=obat";
                }, 2000);
            </script>';
            exit;
        } else {
            throw new Exception("Gagal mengupdate data obat");
        }

    } catch (Exception $e) {
        // Debug: Log error
        error_log("Error updating obat: " . $e->getMessage());
        // Redirect dengan pesan error
        header('Location: dashboard.php?tab=obat&error=' . urlencode($e->getMessage()));
        exit;
    }
}

// Ambil data obat untuk form
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Debug: Log ID yang diterima
    error_log("Received ID: " . $id);
    
    $db = new Database();
    
    // Ambil data obat dengan join ke KategoriObat
    $query = "SELECT ro.*, ko.Nama as KategoriNama, lm.Status as LayananStatus
             FROM ResepObat ro 
             JOIN KategoriObat ko ON ro.KategoriObat_ID = ko.ID 
             JOIN LayananMedis lm ON ro.LayananMedis_ID = lm.ID
             WHERE ro.ID = :id AND ro.onDelete = 0";
             
    // Debug: Log query
    error_log("Query: " . $query);
    
    $db->query($query);
    $db->bind(':id', $id);
    $obat = $db->single();
    
    // Debug: Log hasil query
    error_log("Query Result: " . print_r($obat, true));
    
    if ($obat) {
        // Cek status layanan medis
        if ($obat['LAYANANSTATUS'] === 'Finished' || $obat['LAYANANSTATUS'] === 'Canceled') {
            echo "<div class='alert alert-error'>Tidak dapat mengubah data obat untuk layanan yang sudah selesai atau dibatalkan</div>";
            echo "<script>
                setTimeout(function() {
                    window.location.href = 'dashboard.php?tab=obat';
                }, 2000);
            </script>";
            exit;
        }
        
        // Ambil daftar kategori obat
        $db->query("SELECT ID, Nama FROM KategoriObat WHERE onDelete = 0 ORDER BY Nama");
        $kategoriObat = $db->resultSet();
        ?>
        <div class="p-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-bold text-lg">Update Obat</h3>
                <label for="update-obat-drawer" class="btn btn-sm btn-circle">✕</label>
            </div>
            
            <form method="POST" action="update-obat.php" class="space-y-4">
                <input type="hidden" name="id" value="<?= $obat['ID'] ?>">
                
                <div class="form-control">
                    <label class="label font-medium">Nama Obat</label>
                    <input type="text" name="nama" value="<?= htmlentities($obat['NAMA']) ?>" 
                           class="input input-bordered w-full" required>
                </div>

                <div class="form-control">
                    <label class="label font-medium">Dosis</label>
                    <input type="text" name="dosis" value="<?= htmlentities($obat['DOSIS']) ?>" 
                           class="input input-bordered w-full" required>
                </div>

                <div class="form-control">
                    <label class="label font-medium">Frekuensi</label>
                    <input type="text" name="frekuensi" value="<?= htmlentities($obat['FREKUENSI']) ?>" 
                           class="input input-bordered w-full" required>
                </div>

                <div class="form-control">
                    <label class="label font-medium">Instruksi</label>
                    <textarea name="instruksi" class="textarea textarea-bordered w-full" 
                              required><?= htmlentities($obat['INSTRUKSI']) ?></textarea>
                </div>

                <div class="form-control">
                    <label class="label font-medium">Kategori Obat</label>
                    <select name="kategori_obat" class="select select-bordered w-full" required>
                        <?php foreach ($kategoriObat as $kategori): ?>
                            <option value="<?= $kategori['ID'] ?>" 
                                    <?= $kategori['ID'] === $obat['KATEGORIOBAT_ID'] ? 'selected' : '' ?>>
                                <?= htmlentities($kategori['NAMA']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <label for="update-obat-drawer" class="btn btn-ghost">Batal</label>
                    <button type="submit" class="btn bg-[#D4F0EA] hover:bg-[#D4F0EA] text-[#363636]">
                        Update Obat
                    </button>
                </div>
            </form>
        </div>
        <?php
    } else {
        echo "<div class='alert alert-error'>Data obat tidak ditemukan</div>";
        echo "<script>
            setTimeout(function() {
                window.location.href = 'dashboard.php?tab=obat';
            }, 2000);
        </script>";
    }
}
?>