<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['posisi'] !== 'staff') {
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
    $jenisLayanan = $_POST['jenis_layanan'] ?? [];
    $totalBiaya = 0; // Initialize total biaya

    // Calculate total biaya from selected services
    if (!empty($jenisLayanan)) {
        $db = new Database();
        $placeholders = implode(',', array_fill(0, count($jenisLayanan), '?'));
        $query = "SELECT SUM(Biaya) as TotalBiaya FROM JenisLayananSalon WHERE ID IN ($placeholders)";
        $db->query($query);
        foreach ($jenisLayanan as $index => $value) {
            $db->bind($index + 1, $value);
        }
        $result = $db->single();
        $totalBiaya = floatval($result['TOTALBIAYA'] ?? 0);
    }

    // Debug output
    error_log("Received data - Total Biaya: " . $totalBiaya);
    error_log("Jenis Layanan: " . print_r($jenisLayanan, true));

    // Validasi input
    if (empty($id)) throw new Exception('ID tidak boleh kosong');
    if (empty($status)) throw new Exception('Status harus dipilih');
    if (empty($tanggal)) throw new Exception('Tanggal harus diisi');
    if (empty($jenisLayanan)) throw new Exception('Minimal satu jenis layanan harus dipilih');
    
    // Get existing data
    $sql = "SELECT ID, PEGAWAI_ID, HEWAN_ID FROM LayananSalon WHERE ID = :id AND onDelete = 0";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ":id", $id);
    oci_execute($stmt);
    $layanan = oci_fetch_assoc($stmt);

    if (!$layanan) {
        throw new Exception('Data layanan salon tidak ditemukan');
    }

    // Format timestamp
    $formattedTanggal = str_replace('T', ' ', $tanggal) . ':00';

    // Create collection untuk jenis layanan
    $jenisLayananCollection = oci_new_collection($conn, 'ARRAYJENISLAYANANSALON');
    if (!empty($jenisLayanan)) {
        foreach ($jenisLayanan as $jenisId) {
            $jenisLayananCollection->append($jenisId);
        }
    }

    // Start transaction
    oci_execute($stmt, OCI_NO_AUTO_COMMIT);

    // Update LayananSalon menggunakan stored procedure
    $sql = "BEGIN UpdateLayananSalon(:id, TO_TIMESTAMP(:tanggal, 'YYYY-MM-DD HH24:MI:SS'), 
            :totalBiaya, :status, :jenisLayanan, :pegawai_id, :hewan_id); END;";
    
    $stmt = oci_parse($conn, $sql);
    
    oci_bind_by_name($stmt, ":id", $id);
    oci_bind_by_name($stmt, ":tanggal", $formattedTanggal);
    oci_bind_by_name($stmt, ":totalBiaya", $totalBiaya);
    oci_bind_by_name($stmt, ":status", $status);
    oci_bind_by_name($stmt, ":pegawai_id", $layanan['PEGAWAI_ID']);
    oci_bind_by_name($stmt, ":hewan_id", $layanan['HEWAN_ID']);
    oci_bind_by_name($stmt, ":jenisLayanan", $jenisLayananCollection, -1, SQLT_NTY);

    if (!oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
        $e = oci_error($stmt);
        throw new Exception($e['message']);
    }

    // Debug output setelah update
    error_log("Updated LayananSalon with total biaya: " . $totalBiaya);

    // Commit transaction
    oci_commit($conn);
    
    // Send JSON response for AJAX requests
    header('Content-Type: application/json');
    $_SESSION['success_message'] = 'Data berhasil diupdate';
    echo json_encode(['success' => true, 'message' => 'Data berhasil diupdate']);
    exit();

} catch (Exception $e) {
    if (isset($conn)) {
        oci_rollback($conn);
        oci_close($conn);
    }
    error_log("Error updating medical service: " . $e->getMessage());
    
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit();
}
?>