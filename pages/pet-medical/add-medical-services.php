<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action']) || $_POST['action'] !== 'add') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method or action'
    ]);
    exit;
}

require_once '../../config/database.php';
require_once '../../config/connection.php';

$db = new Database();
$pegawaiId = $_SESSION['employee_id'] ?? null;

if (!$pegawaiId) {
    echo json_encode([
        'success' => false,
        'message' => 'Session tidak valid'
    ]);
    exit;
}

// Debug: tampilkan semua data POST
error_log("POST data: " . print_r($_POST, true));

// Inisialisasi variabel
$error = null;
$message = null;

// Validasi input yang diperlukan
if (!isset($_POST['status']) || !isset($_POST['tanggal']) || !isset($_POST['description']) || !isset($_POST['hewan_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Data yang diperlukan tidak lengkap'
    ]);
    exit;
}

// Proses tambah layanan medis
$status = htmlspecialchars($_POST['status'], ENT_QUOTES);
$tanggal = htmlspecialchars($_POST['tanggal'], ENT_QUOTES);
$totalBiaya = floatval($_POST['total_biaya'] ?? 0);
$description = htmlspecialchars($_POST['description'] ?? '', ENT_QUOTES);
$hewan_id = htmlspecialchars($_POST['hewan_id'], ENT_QUOTES);
$jenisLayananArray = $_POST['jenis_layanan'] ?? [];
$obatList = json_decode($_POST['obat_list'] ?? '[]', true);

// Debug: tampilkan data yang akan diproses
error_log("Processed data: " . print_r([
    'status' => $status,
    'tanggal' => $tanggal,
    'totalBiaya' => $totalBiaya,
    'description' => $description,
    'hewan_id' => $hewan_id,
    'jenisLayananArray' => $jenisLayananArray,
    'obatList' => $obatList
], true));

// Validasi input
if (empty($description)) {
    $error = "Description harus diisi.";
}

if ($status !== 'Scheduled' && empty($jenisLayananArray)) {
    $error = "Jenis layanan harus dipilih untuk status non-scheduled.";
}

if (empty($hewan_id)) {
    $error = "Pet harus dipilih.";
}

if (empty($error)) {
    try {
        $db->beginTransaction();

        // Format array jenis layanan untuk procedure
        $jenisLayananString = !empty($jenisLayananArray) 
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

        if (!$layananMedisId) {
            throw new Exception("Gagal mendapatkan ID layanan medis");
        }

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

        // Return response dalam format JSON
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'id' => $layananMedisId,
            'message' => $message
        ]);
        exit;

    } catch (Exception $e) {
        $db->rollBack();
        $error = "Terjadi kesalahan: " . $e->getMessage();
        error_log("Error in add-medical-services.php: " . $e->getMessage());
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $error
        ]);
        exit;
    }
}

// Jika ada error validasi, return dalam format JSON
if ($error) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $error
    ]);
    exit;
}
?>