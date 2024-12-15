<?php
session_start();
include '../../config/connection.php';

if (!isset($_SESSION['username']) || $_SESSION['posisi'] !== 'vet') {
    header("Location: ../../auth/restricted.php");
    exit();
}

if (isset($_GET['delete_id']) && isset($_GET['tab'])) {
    $deleteId = trim($_GET['delete_id']);
    $currentTab = trim($_GET['tab']);
    $currentPage = trim($_GET['page']);
    $filterNamaHewan = trim($_GET['nama_hewan'] ?? '');
    $filterNamaPemilik = trim($_GET['nama_pemilik'] ?? '');

    if (!preg_match('/^[a-f0-9\-]{36}$/i', $deleteId)) {
        $_SESSION['error_message'] = 'Format ID tidak valid.';
        header("Location: dashboard.php?tab={$currentTab}&page={$currentPage}&nama_hewan=" . urlencode($filterNamaHewan) . "&nama_pemilik=" . urlencode($filterNamaPemilik));
        exit();
    }

    $sqlDelete = match ($currentTab) {
        'medical-services' => "UPDATE LayananMedis SET onDelete = 1 WHERE ID = :id",
        'obat' => "UPDATE ResepObat SET onDelete = 1 WHERE ID = :id",
        default => null
    };

    if ($sqlDelete) {
        $stmtDelete = oci_parse($conn, $sqlDelete);
        oci_bind_by_name($stmtDelete, ':id', $deleteId);

        if (oci_execute($stmtDelete, OCI_COMMIT_ON_SUCCESS)) {
            $messageText = $currentTab === 'medical-services' 
                ? 'Layanan Medis berhasil dihapus.' 
                : 'Obat berhasil dihapus.';
            $_SESSION['success_message'] = $messageText;
        } else {
            $error = oci_error($stmtDelete);
            $_SESSION['error_message'] = 'Gagal menghapus: ' . htmlentities($error['message']);
        }
        oci_free_statement($stmtDelete);
    } else {
        $_SESSION['error_message'] = 'Tab tidak dikenal untuk penghapusan.';
    }
} else {
    $_SESSION['error_message'] = 'ID atau tab tidak ditemukan.';
}

oci_close($conn);
header("Location: dashboard.php?tab={$currentTab}&page={$currentPage}&nama_hewan=" . urlencode($filterNamaHewan) . "&nama_pemilik=" . urlencode($filterNamaPemilik));
exit();