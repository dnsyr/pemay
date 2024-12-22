<?php
require_once '../../config/database.php';
$db = new Database();

// Proses update jika ada POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id = $_POST['id'];
        $nama = $_POST['nama'];
        $dosis = $_POST['dosis'];
        $frekuensi = $_POST['frekuensi'];
        $instruksi = $_POST['instruksi'];
        $kategoriObat = $_POST['kategori_obat'];

        $query = "UPDATE ResepObat 
                 SET Nama = :nama, 
                     Dosis = :dosis, 
                     Frekuensi = :frekuensi, 
                     Instruksi = :instruksi, 
                     KategoriObat_ID = :kategori_obat 
                 WHERE ID = :id";

        $db->query($query);
        $db->bind(':id', $id);
        $db->bind(':nama', $nama);
        $db->bind(':dosis', $dosis);
        $db->bind(':frekuensi', $frekuensi);
        $db->bind(':instruksi', $instruksi);
        $db->bind(':kategori_obat', $kategoriObat);

        $db->execute();

        // Tampilkan pesan sukses dan redirect setelah 2 detik
        echo '<div class="alert alert-success">Data obat berhasil diperbarui</div>';
        echo '<script>
            setTimeout(function() {
                window.location.href = "dashboard.php?tab=obat";
            }, 2000);
        </script>';
        exit;

    } catch (Exception $e) {
        // Redirect dengan pesan error
        header('Location: dashboard.php?tab=obat&error=' . urlencode($e->getMessage()));
        exit;
    }
}

// Ambil data obat untuk form
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Ambil data obat
    $db->query("SELECT ro.*, ko.Nama as KategoriNama 
                FROM ResepObat ro 
                JOIN KategoriObat ko ON ro.KategoriObat_ID = ko.ID 
                WHERE ro.ID = :id");
    $db->bind(':id', $id);
    $obat = $db->single();
    
    // Ambil daftar kategori obat
    $db->query("SELECT ID, Nama FROM KategoriObat WHERE onDelete = 0 ORDER BY Nama");
    $kategoriObat = $db->resultSet();

    if ($obat) {
        ?>
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
                <a href="dashboard.php?tab=obat" class="btn btn-ghost">Batal</a>
                <button type="submit" class="btn bg-[#D4F0EA] hover:bg-[#D4F0EA] text-[#363636]">
                    Update Obat
                </button>
            </div>
        </form>
        <?php
    } else {
        echo "<div class='alert alert-error'>Data obat tidak ditemukan</div>";
    }
}
?>