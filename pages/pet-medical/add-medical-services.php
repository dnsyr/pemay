<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action']) || $_POST['action'] !== 'add') {
    exit;
}

require_once '../../config/database.php';
$db = new Database();
$pegawaiId = $_SESSION['employee_id'];

// Inisialisasi variabel
$error = null;
$message = null;

// Proses tambah layanan medis
$status = htmlspecialchars($_POST['status'], ENT_QUOTES);
$tanggal = htmlspecialchars($_POST['tanggal'], ENT_QUOTES);
$totalBiaya = floatval($_POST['total_biaya'] ?? 0);
$description = htmlspecialchars($_POST['description'], ENT_QUOTES);
$hewan_id = htmlspecialchars($_POST['hewan_id'], ENT_QUOTES);
$jenisLayananArray = $_POST['jenis_layanan'] ?? [];
$obatList = json_decode($_POST['obat_list'] ?? '[]', true);

// Validasi input
if ($status !== 'Scheduled' && empty($jenisLayananArray)) {
    $error = "Jenis layanan harus dipilih.";
}

if (empty($error)) {
    try {
        $db->beginTransaction();

        // Format array jenis layanan untuk procedure
        $jenisLayananString = $status !== 'Scheduled' 
            ? "ArrayJenisLayananMedis(" . implode(',', array_map(fn($id) => "'".addslashes($id)."'", $jenisLayananArray)) . ")"
            : "ArrayJenisLayananMedis()";

        // Format tanggal untuk Oracle TIMESTAMP
        $tanggalObj = DateTime::createFromFormat('Y-m-d\TH:i', $tanggal);
        if ($tanggalObj === false) {
            throw new Exception("Format tanggal tidak valid");
        }
        $tanggalFormatted = $tanggalObj->format('Y-m-d H:i:s.u');

        // Eksekusi CreateLayananMedis
        $sql = "BEGIN CreateLayananMedis(TO_TIMESTAMP(:tanggal, 'YYYY-MM-DD HH24:MI:SS.FF'), :totalBiaya, :description, :status, $jenisLayananString, :pegawai_id, :hewan_id); END;";
        
        $db->query($sql);
        $db->bind(':tanggal', $tanggalFormatted);
        $db->bind(':totalBiaya', $totalBiaya);
        $db->bind(':description', $description);
        $db->bind(':status', $status);
        $db->bind(':pegawai_id', $pegawaiId);
        $db->bind(':hewan_id', $hewan_id);
        
        $result = $db->execute();

        // Get the created LayananMedis ID
        $db->query("SELECT ID FROM LayananMedis WHERE Pegawai_ID = :pegawai_id ORDER BY Tanggal DESC FETCH FIRST 1 ROW ONLY");
        $db->bind(':pegawai_id', $pegawaiId);
        $result = $db->single();
        $layananMedisId = $result['ID'];

        // Jika ada obat yang perlu ditambahkan
        if (!empty($obatList)) {
            foreach ($obatList as $obat) {
                // Generate ID untuk resep obat menggunakan SYS_GUID
                $db->query("SELECT RAWTOHEX(SYS_GUID()) as new_id FROM dual");
                $result = $db->single();
                $guidHex = $result['NEW_ID'];
                
                // Format GUID dengan strip
                $obatId = substr($guidHex, 0, 8) . '-' . 
                         substr($guidHex, 8, 4) . '-' . 
                         substr($guidHex, 12, 4) . '-' . 
                         substr($guidHex, 16, 4) . '-' . 
                         substr($guidHex, 20);

                // Insert resep obat
                $sqlObat = "INSERT INTO ResepObat (ID, LayananMedis_ID, Nama, Dosis, Frekuensi, Instruksi, KategoriObat_ID, Harga, onDelete) 
                           VALUES (:id, :layanan_medis_id, :nama, :dosis, :frekuensi, :instruksi, :kategori_obat_id, 0, 0)";
                
                $db->query($sqlObat);
                $db->bind(':id', $obatId);
                $db->bind(':layanan_medis_id', $layananMedisId);
                $db->bind(':nama', $obat['nama']);
                $db->bind(':dosis', $obat['dosis']);
                $db->bind(':frekuensi', $obat['frekuensi']);
                $db->bind(':instruksi', $obat['instruksi']);
                $db->bind(':kategori_obat_id', $obat['kategori_id']);
                
                $db->execute();
            }
        }

        $db->commit();
        $message = "Layanan medis berhasil ditambahkan.";

        // Redirect ke dashboard dengan pesan sukses
        if (!empty($obatList)) {
            // Jika ada obat, redirect ke halaman print resep
            header("Location: print.php?id=" . urlencode($layananMedisId));
        } else {
            // Jika tidak ada obat, kembali ke dashboard
            header("Location: dashboard.php?tab=medical-services&message=" . urlencode($message));
        }
        exit;

    } catch (Exception $e) {
        $db->rollBack();
        $error = "Terjadi kesalahan: " . $e->getMessage();
        error_log("Error in add-medical-services.php: " . $e->getMessage());
        
        // Redirect dengan pesan error
        header("Location: dashboard.php?tab=medical-services&error=" . urlencode($error));
        exit;
    }
}

// Jika ada error, redirect kembali dengan pesan error
if ($error) {
    header("Location: dashboard.php?tab=medical-services&error=" . urlencode($error));
    exit;
}
?>