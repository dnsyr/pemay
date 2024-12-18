<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['posisi'] !== 'vet') {
    header("Location: ../../auth/restricted.php");
    exit();
}

require_once '../../config/database.php';

try {
    $db = new Database();
    
    // Debug incoming data
    error_log("Raw POST data: " . print_r($_POST, true));
    
    // Validate input
    $id = trim($_POST['id'] ?? '');
    $status = trim($_POST['status'] ?? '');
    $tanggal = trim($_POST['tanggal'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $jenisLayanan = $_POST['jenis_layanan'] ?? [];
    
    // Get raw total_biaya value
    $rawTotalBiaya = $_POST['total_biaya'] ?? '0';
    error_log("Raw total_biaya: " . $rawTotalBiaya);
    
    // Remove formatting
    $cleanTotalBiaya = str_replace([',', '.', 'Rp', ' '], '', $rawTotalBiaya);
    error_log("Cleaned total_biaya: " . $cleanTotalBiaya);
    
    // Convert to number
    $totalBiaya = floatval($cleanTotalBiaya);
    error_log("Final totalBiaya as float: " . $totalBiaya);

    if (empty($id)) {
        throw new Exception('ID tidak boleh kosong');
    }

    // Get existing data
    $db->query("SELECT ID, PEGAWAI_ID, HEWAN_ID FROM LayananMedis WHERE ID = :id AND onDelete = 0");
    $db->bind(':id', $id);
    $layanan = $db->single();

    if (!$layanan) {
        throw new Exception('Data not found');
    }

    // Format timestamp properly
    $formattedTanggal = $db->timestampFormat($tanggal);

    // Gunakan koneksi hardcode
    $conn = oci_connect('C##PET', '12345', '//localhost:1521/xe');
    if (!$conn) {
        $e = oci_error();
        throw new Exception("Connection failed: " . $e['message']);
    }

    // Create collection untuk jenis layanan
$jenisLayananCollection = oci_new_collection($conn, 'ARRAYJENISLAYANANMEDIS');
if (!empty($jenisLayanan)) {
    foreach ($jenisLayanan as $jenisId) {
        $jenisLayananCollection->append($jenisId);
    }
}

    // Prepare procedure call
    $sql = "BEGIN UpdateLayananMedis(:id, TO_TIMESTAMP(:tanggal, 'YYYY-MM-DD HH24:MI:SS'), 
            :totalBiaya, :description, :status, :jenisLayanan, :pegawai_id, :hewan_id); END;";
    
    $stmt = oci_parse($conn, $sql);
    
    // Debug total biaya sebelum binding
    error_log("Total biaya before binding: " . $totalBiaya);
    
    // Bind parameters
    oci_bind_by_name($stmt, ":id", $layanan['ID']);
    oci_bind_by_name($stmt, ":tanggal", $formattedTanggal);
    oci_bind_by_name($stmt, ":totalBiaya", $totalBiaya); // Hapus SQLT_NUM
    oci_bind_by_name($stmt, ":description", $description);
    oci_bind_by_name($stmt, ":status", $status);
    oci_bind_by_name($stmt, ":pegawai_id", $layanan['PEGAWAI_ID']);
    oci_bind_by_name($stmt, ":hewan_id", $layanan['HEWAN_ID']);
    oci_bind_by_name($stmt, ":jenisLayanan", $jenisLayananCollection, -1, SQLT_NTY);

    // Execute procedure
    $result = oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);
    
    if (!$result) {
        $e = oci_error($stmt);
        throw new Exception($e['message']);
    }
    
    // Free resources
    oci_free_statement($stmt);
    oci_close($conn);

    header("Location: dashboard.php?message=" . urlencode("Data berhasil diupdate"));
    exit();

} catch (Exception $e) {
    error_log("Error updating medical service: " . $e->getMessage());
    header("Location: dashboard.php?error=" . urlencode($e->getMessage()));
    exit();
}
?>