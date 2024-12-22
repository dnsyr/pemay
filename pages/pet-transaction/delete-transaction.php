<?php
session_start();
require_once '../../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $db = new Database();
    
    try {
        $db->beginTransaction();
        
        // Get transaction details first with products
        $db->query("SELECT T.COLUMN_VALUE as PRODUK_ID, COUNT(*) as QUANTITY
                   FROM PENJUALAN PJ,
                   TABLE(PJ.PRODUK) T
                   WHERE PJ.ID = :id AND PJ.onDelete = 0
                   GROUP BY T.COLUMN_VALUE");
        $db->bind(':id', $_POST['id']);
        $products = $db->resultSet();
        
        if (empty($products)) {
            throw new Exception('Transaksi tidak ditemukan atau tidak memiliki produk.');
        }

        // Return products to stock
        foreach ($products as $product) {
            // Get current stock
            $db->query("SELECT JUMLAH FROM PRODUK WHERE ID = :id FOR UPDATE");
            $db->bind(':id', $product['PRODUK_ID']);
            $currentStock = $db->single()['JUMLAH'];

            // Update stock
            $newStock = $currentStock + $product['QUANTITY'];
            $db->query("UPDATE PRODUK SET JUMLAH = :jumlah WHERE ID = :id");
            $db->bind(':jumlah', $newStock);
            $db->bind(':id', $product['PRODUK_ID']);
            $db->execute();

            // Log stock change
            $db->query("INSERT INTO LOGPRODUK (STOKAWAL, STOKAKHIR, PERUBAHAN, KETERANGAN, TANGGALPERUBAHAN, PRODUK_ID, PEGAWAI_ID) 
                       VALUES (:stok_awal, :stok_akhir, :perubahan, 'Pembatalan Transaksi Penjualan', SYSTIMESTAMP, :produk_id, :pegawai_id)");
            $db->bind(':stok_awal', $currentStock);
            $db->bind(':stok_akhir', $newStock);
            $db->bind(':perubahan', $product['QUANTITY']);
            $db->bind(':produk_id', $product['PRODUK_ID']);
            $db->bind(':pegawai_id', $_SESSION['employee_id']);
            $db->execute();
        }

        // Soft delete the transaction
        $db->query("UPDATE PENJUALAN SET onDelete = 1 WHERE ID = :id");
        $db->bind(':id', $_POST['id']);
        $db->execute();

        $db->commit();
        
        $_SESSION['success_message'] = "Transaksi berhasil dihapus dan stok produk telah dikembalikan.";
        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
} 