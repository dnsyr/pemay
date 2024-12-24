<?php
session_start();
include '../../config/connection.php';

if (!isset($_SESSION['username']) || $_SESSION['posisi'] !== 'vet') {
    header("Location: ../../auth/restricted.php");
    exit();
}

if (isset($_GET['id']) && isset($_GET['type']) && isset($_GET['tab'])) {
    $deleteId = trim($_GET['id']);
    $type = trim($_GET['type']);
    $currentTab = trim($_GET['tab']);
    $currentPage = isset($_GET['page']) ? trim($_GET['page']) : '1';
    $filterNamaHewan = trim($_GET['nama_hewan'] ?? '');
    $filterNamaPemilik = trim($_GET['nama_pemilik'] ?? '');

    if (!preg_match('/^[a-f0-9\-]{36}$/i', $deleteId)) {
        $_SESSION['error_message'] = 'Invalid ID format.';
        header("Location: dashboard.php?tab={$currentTab}&page={$currentPage}&nama_hewan=" . urlencode($filterNamaHewan) . "&nama_pemilik=" . urlencode($filterNamaPemilik));
        exit();
    }

    // Determine which table to update based on type
    $sqlDelete = match ($type) {
        'medical' => "UPDATE LayananMedis SET onDelete = 1 WHERE ID = :id",
        'medication' => "UPDATE ResepObat SET onDelete = 1 WHERE ID = :id",
        default => null
    };

    if ($sqlDelete) {
        // Start transaction
        $stmtDelete = oci_parse($conn, $sqlDelete);
        oci_bind_by_name($stmtDelete, ':id', $deleteId);

        if (oci_execute($stmtDelete, OCI_COMMIT_ON_SUCCESS)) {
            // If deleting medical service, also delete related medications
            if ($type === 'medical') {
                $sqlDeleteMeds = "UPDATE ResepObat SET onDelete = 1 WHERE LayananMedis_ID = :id";
                $stmtDeleteMeds = oci_parse($conn, $sqlDeleteMeds);
                oci_bind_by_name($stmtDeleteMeds, ':id', $deleteId);
                oci_execute($stmtDeleteMeds, OCI_COMMIT_ON_SUCCESS);
                oci_free_statement($stmtDeleteMeds);
            }

            $messageText = $type === 'medical' 
                ? 'Medical service successfully deleted.' 
                : 'Medicine successfully deleted.';
            $_SESSION['success_message'] = $messageText;
        } else {
            $error = oci_error($stmtDelete);
            $_SESSION['error_message'] = 'Failed to delete: ' . htmlentities($error['message']);
        }
        oci_free_statement($stmtDelete);
    } else {
        $_SESSION['error_message'] = 'Invalid record type for deletion.';
    }
} else {
    $_SESSION['error_message'] = 'Required parameters are incomplete.';
}

oci_close($conn);
header("Location: dashboard.php?tab={$currentTab}&page={$currentPage}&nama_hewan=" . urlencode($filterNamaHewan) . "&nama_pemilik=" . urlencode($filterNamaPemilik));
exit();
?>