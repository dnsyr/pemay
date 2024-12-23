<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
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
        'message' => 'Invalid session'
    ]);
    exit;
}

// Debug: log all POST data
error_log("POST data: " . print_r($_POST, true));

// Initialize variables
$error = null;
$message = null;

// Validate required inputs
if (!isset($_POST['status']) || !isset($_POST['tanggal']) || !isset($_POST['description']) || !isset($_POST['hewan_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Required data is incomplete'
    ]);
    exit;
}

// Process medical service addition
$status = htmlspecialchars($_POST['status'], ENT_QUOTES);
$tanggal = htmlspecialchars($_POST['tanggal'], ENT_QUOTES);
$totalBiaya = floatval($_POST['total_biaya'] ?? 0);
$description = htmlspecialchars($_POST['description'] ?? '', ENT_QUOTES);
$hewan_id = htmlspecialchars($_POST['hewan_id'], ENT_QUOTES);
$jenisLayananArray = $_POST['jenis_layanan'] ?? [];
$obatList = json_decode($_POST['obat_list'] ?? '[]', true);
$saveAction = $_POST['action'] ?? 'save';

// Debug: log processed data
error_log("Processed data: " . print_r([
    'status' => $status,
    'tanggal' => $tanggal,
    'totalBiaya' => $totalBiaya,
    'description' => $description,
    'hewan_id' => $hewan_id,
    'jenisLayananArray' => $jenisLayananArray,
    'obatList' => $obatList,
    'saveAction' => $saveAction
], true));

// Validate input
if (empty($description)) {
    $error = "Description is required.";
}

if ($status !== 'Scheduled' && empty($jenisLayananArray)) {
    $error = "Service type must be selected for non-scheduled status.";
}

if (empty($hewan_id)) {
    $error = "Pet must be selected.";
}

if (empty($error)) {
    try {
        $db->beginTransaction();

        // Format service type array for procedure
        $jenisLayananString = !empty($jenisLayananArray) 
            ? "ArrayJenisLayananMedis(" . implode(',', array_map(fn($id) => "'".addslashes($id)."'", $jenisLayananArray)) . ")"
            : "ArrayJenisLayananMedis()";

        // Format date for Oracle TIMESTAMP
        $tanggalObj = DateTime::createFromFormat('Y-m-d\TH:i', $tanggal);
        if ($tanggalObj === false) {
            throw new Exception("Invalid date format");
        }
        $tanggalFormatted = $tanggalObj->format('Y-m-d H:i:s.u');

        // Execute CreateLayananMedis
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
            throw new Exception("Failed to get medical service ID");
        }

        // If there are medicines to add
        if (!empty($obatList)) {
            foreach ($obatList as $obat) {
                // Generate ID for medicine prescription using SYS_GUID
                $db->query("SELECT RAWTOHEX(SYS_GUID()) as new_id FROM dual");
                $result = $db->single();
                $guidHex = $result['NEW_ID'];
                
                // Format GUID with dashes
                $obatId = substr($guidHex, 0, 8) . '-' . 
                         substr($guidHex, 8, 4) . '-' . 
                         substr($guidHex, 12, 4) . '-' . 
                         substr($guidHex, 16, 4) . '-' . 
                         substr($guidHex, 20);

                // Insert medicine prescription
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
        $message = "Medical service has been successfully added.";

        // Return response in JSON format with appropriate redirect
        $redirectUrl = ($saveAction === 'save_and_print') 
            ? "/pemay/pages/pet-medical/print.php?id=" . $layananMedisId
            : "dashboard.php?message=" . urlencode($message);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'id' => $layananMedisId,
            'message' => $message,
            'redirect' => $redirectUrl
        ]);
        exit;

    } catch (Exception $e) {
        $db->rollBack();
        $error = "An error occurred: " . $e->getMessage();
        error_log("Error in add-medical-services.php: " . $e->getMessage());
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $error
        ]);
        exit;
    }
}

// If there's a validation error, return in JSON format
if ($error) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $error
    ]);
    exit;
}
?>