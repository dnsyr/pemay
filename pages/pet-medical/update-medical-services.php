<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['posisi'] !== 'vet') {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once '../../config/database.php';
require_once '../../config/connection.php';

try {
    // Debug received data
    error_log("POST data received: " . print_r($_POST, true));
    
    // Validate input
    $id = trim($_POST['id'] ?? '');
    $status = trim($_POST['status'] ?? '');
    $tanggal = trim($_POST['tanggal'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $jenisLayanan = $_POST['jenis_layanan'] ?? [];
    $action = $_POST['action'] ?? 'save';
    $totalBiaya = 0; // Initialize total biaya

    // Validasi input
    if (empty($id)) throw new Exception('ID is required');
    if (empty($status)) throw new Exception('Status must be selected');
    if (empty($tanggal)) throw new Exception('Date is required');
    if (empty($description)) throw new Exception('Description is required');
    if (empty($jenisLayanan)) throw new Exception('At least one service type must be selected');
    
    // Additional validation for save_and_print
    if ($action === 'save_and_print') {
        $obatList = json_decode($_POST['obat_list'] ?? '[]', true);
        if (empty($obatList)) {
            throw new Exception('Cannot save and print without medicines');
        }
    }

    // Calculate total biaya from selected services
    if (!empty($jenisLayanan)) {
        $db = new Database();
        $placeholders = implode(',', array_fill(0, count($jenisLayanan), '?'));
        $query = "SELECT SUM(Biaya) as TotalBiaya FROM JenisLayananMedis WHERE ID IN ($placeholders)";
        $db->query($query);
        foreach ($jenisLayanan as $index => $value) {
            $db->bind($index + 1, $value);
        }
        $result = $db->single();
        $totalBiaya = floatval($result['TOTALBIAYA'] ?? 0);
    }

    // Get existing data
    $sql = "SELECT ID, PEGAWAI_ID, HEWAN_ID FROM LayananMedis WHERE ID = :id AND onDelete = 0";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ":id", $id);
    oci_execute($stmt);
    $layanan = oci_fetch_assoc($stmt);

    if (!$layanan) {
        throw new Exception('Medical service data not found');
    }

    // Format timestamp
    $formattedTanggal = str_replace('T', ' ', $tanggal) . ':00';

    // Create collection untuk jenis layanan
    $jenisLayananCollection = oci_new_collection($conn, 'ARRAYJENISLAYANANMEDIS');
    if (!empty($jenisLayanan)) {
        foreach ($jenisLayanan as $jenisId) {
            $jenisLayananCollection->append($jenisId);
        }
    }

    // Start transaction
    oci_execute($stmt, OCI_NO_AUTO_COMMIT);

    // Update LayananMedis menggunakan stored procedure
    $sql = "BEGIN UpdateLayananMedis(:id, TO_TIMESTAMP(:tanggal, 'YYYY-MM-DD HH24:MI:SS'), 
            :totalBiaya, :description, :status, :jenisLayanan, :pegawai_id, :hewan_id); END;";
    
    $stmt = oci_parse($conn, $sql);
    
    oci_bind_by_name($stmt, ":id", $id);
    oci_bind_by_name($stmt, ":tanggal", $formattedTanggal);
    oci_bind_by_name($stmt, ":totalBiaya", $totalBiaya);
    oci_bind_by_name($stmt, ":description", $description);
    oci_bind_by_name($stmt, ":status", $status);
    oci_bind_by_name($stmt, ":pegawai_id", $layanan['PEGAWAI_ID']);
    oci_bind_by_name($stmt, ":hewan_id", $layanan['HEWAN_ID']);
    oci_bind_by_name($stmt, ":jenisLayanan", $jenisLayananCollection, -1, SQLT_NTY);

    if (!oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
        $e = oci_error($stmt);
        throw new Exception($e['message']);
    }

    // Handle obat
    if ($status !== 'Scheduled') {
        // Soft delete existing obat
        $sql = "UPDATE ResepObat SET onDelete = 1 WHERE LayananMedis_ID = :layanan_id";
        $stmt = oci_parse($conn, $sql);
        oci_bind_by_name($stmt, ":layanan_id", $id);
        if (!oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $e = oci_error($stmt);
            throw new Exception("Error deleting existing medicines: " . $e['message']);
        }

        // Tambahkan obat baru jika ada
        $obatList = json_decode($_POST['obat_list'] ?? '[]', true);
        error_log("Received obat list: " . print_r($obatList, true));
        
        if (!empty($obatList)) {
            foreach ($obatList as $obat) {
                // Skip if no nama (empty row)
                if (empty($obat['nama'])) continue;

                // Generate ID untuk resep obat menggunakan SYS_GUID
                $db = new Database();
                $db->query("SELECT RAWTOHEX(SYS_GUID()) as new_id FROM dual");
                $result = $db->single();
                $guidHex = $result['NEW_ID'];
                
                // Format GUID dengan strip
                $obatId = substr($guidHex, 0, 8) . '-' . 
                         substr($guidHex, 8, 4) . '-' . 
                         substr($guidHex, 12, 4) . '-' . 
                         substr($guidHex, 16, 4) . '-' . 
                         substr($guidHex, 20);

                $sql = "INSERT INTO ResepObat (ID, LayananMedis_ID, Nama, Dosis, Frekuensi, 
                        Instruksi, KategoriObat_ID, Harga, onDelete) 
                        VALUES (:id, :layanan_id, :nama, :dosis, :frekuensi, :instruksi, 
                        :kategori_obat_id, 0, 0)";
                
                $stmt = oci_parse($conn, $sql);
                oci_bind_by_name($stmt, ":id", $obatId);
                oci_bind_by_name($stmt, ":layanan_id", $id);
                oci_bind_by_name($stmt, ":nama", $obat['nama']);
                oci_bind_by_name($stmt, ":dosis", $obat['dosis']);
                oci_bind_by_name($stmt, ":frekuensi", $obat['frekuensi']);
                oci_bind_by_name($stmt, ":instruksi", $obat['instruksi']);
                oci_bind_by_name($stmt, ":kategori_obat_id", $obat['kategori_id']);
                
                if (!oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
                    $e = oci_error($stmt);
                    throw new Exception("Error adding medicine: " . $e['message']);
                }
            }
        }
    }

    // Commit transaction
    oci_commit($conn);
    
    // Prepare redirect URL based on action
    $redirectUrl = 'dashboard.php';
    if ($action === 'save_and_print') {
        $redirectUrl = "/pemay/pages/pet-medical/print.php?id=" . $id;
    }
    
    // Send JSON response for AJAX requests
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'message' => 'Data has been successfully updated',
        'id' => $id,
        'redirect' => $redirectUrl
    ]);
    exit();

} catch (Exception $e) {
    if (isset($conn)) {
        oci_rollback($conn);
        oci_close($conn);
    }
    error_log("Error updating medical service: " . $e->getMessage());
    
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage(),
        'id' => $id ?? null
    ]);
    exit();
}
?>
