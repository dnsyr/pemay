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

if (!isset($_SESSION['username']) || $_SESSION['posisi'] !== 'staff') {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
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
if (!isset($_POST['status']) || !isset($_POST['tanggal']) || !isset($_POST['hewan_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Data yang diperlukan tidak lengkap'
    ]);
    exit;
}

// Proses tambah layanan Salon
$status = htmlspecialchars($_POST['status'], ENT_QUOTES);
$tanggal = htmlspecialchars($_POST['tanggal'], ENT_QUOTES);
$totalBiaya = floatval($_POST['total_biaya'] ?? 0);
$hewan_id = htmlspecialchars($_POST['hewan_id'], ENT_QUOTES);
$jenisLayananArray = $_POST['jenis_layanan'] ?? [];

// Debug: tampilkan data yang akan diproses
error_log("Processed data: " . print_r([
    'status' => $status,
    'tanggal' => $tanggal,
    'totalBiaya' => $totalBiaya,
    'hewan_id' => $hewan_id,
    'jenisLayananArray' => $jenisLayananArray,
], true));

// Validasi input
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
            ? "ArrayJenisLayananSalon(" . implode(',', array_map(fn($id) => "'".addslashes($id)."'", $jenisLayananArray)) . ")"
            : "ArrayJenisLayananSalon()";

        // Format tanggal untuk Oracle TIMESTAMP
        $tanggalObj = DateTime::createFromFormat('Y-m-d\TH:i', $tanggal);
        if ($tanggalObj === false) {
            throw new Exception("Format tanggal tidak valid");
        }
        $tanggalFormatted = $tanggalObj->format('Y-m-d H:i:s.u');

        // Eksekusi CreateLayananSalon
        $sql = "BEGIN CreateLayananSalon(TO_TIMESTAMP(:tanggal, 'YYYY-MM-DD HH24:MI:SS.FF'), :totalBiaya, :status, $jenisLayananString, :pegawai_id, :hewan_id); END;";
        
        $db->query($sql);
        $db->bind(':tanggal', $tanggalFormatted);
        $db->bind(':totalBiaya', $totalBiaya);
        $db->bind(':status', $status);
        $db->bind(':pegawai_id', $pegawaiId);
        $db->bind(':hewan_id', $hewan_id);
        
        $result = $db->execute();

        // Get the created LayananSalon ID
        $db->query("SELECT ID FROM LayananSalon WHERE Pegawai_ID = :pegawai_id ORDER BY Tanggal DESC FETCH FIRST 1 ROW ONLY");
        $db->bind(':pegawai_id', $pegawaiId);
        $result = $db->single();
        $layananSalonId = $result['ID'];

        if (!$layananSalonId) {
            throw new Exception("Gagal mendapatkan ID layanan salon");
        }

        $db->commit();
        $message = "Layanan salon berhasil ditambahkan.";
        $_SESSION['success_message'] = $message;

        // Return response dalam format JSON
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'id' => $layananSalonId,
            'message' => $message
        ]);
        exit;

    } catch (Exception $e) {
        $db->rollBack();
        $error = "Terjadi kesalahan: " . $e->getMessage();
        error_log("Error in add-salon-services.php: " . $e->getMessage());
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