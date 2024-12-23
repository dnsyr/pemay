<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['posisi'] !== 'vet') {
    header("Location: ../../auth/restricted.php");
    exit();
}

require_once '../../config/database.php';

try {
    if (!isset($_POST['obat_id'])) {
        throw new Exception('ID obat tidak ditemukan');
    }

    $obatId = $_POST['obat_id'];
    
    // Gunakan koneksi OCI langsung
    $conn = oci_connect('C##PET', '12345', '//localhost:1521/xe');
    if (!$conn) {
        throw new Exception(oci_error()['message']);
    }

    // Get LayananMedis_ID before deleting
    $sql = "SELECT LayananMedis_ID FROM ResepObat WHERE ID = :id AND onDelete = 0";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ":id", $obatId);
    oci_execute($stmt);
    $row = oci_fetch_assoc($stmt);
    
    if (!$row) {
        throw new Exception('Data obat tidak ditemukan');
    }
    
    $layananId = $row['LAYANANMEDIS_ID'];

    // Soft delete the obat
    $sql = "UPDATE ResepObat SET onDelete = 1 WHERE ID = :id";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ":id", $obatId);
    
    if (!oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
        $e = oci_error($stmt);
        throw new Exception('Gagal menghapus obat: ' . $e['message']);
    }

    // Commit the transaction
    oci_commit($conn);
    
    // Clean up
    oci_free_statement($stmt);
    oci_close($conn);

    // Redirect back to update form
    header("Location: dashboard.php?layanan_id=" . $layananId . "&message=" . urlencode("Obat berhasil dihapus"));
    exit();

} catch (Exception $e) {
    if (isset($conn)) {
        oci_rollback($conn);
        oci_close($conn);
    }
    // Redirect back to dashboard with error
    header("Location: dashboard.php?error=" . urlencode($e->getMessage()));
    exit();
}
?> 