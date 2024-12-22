<?php
session_start();
require_once '../../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$db = new Database();

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    // Get transaction details
    try {
        $db->query("SELECT 
                    PJ.ID,
                    PJ.TANGGALTRANSAKSI,
                    PJ.TOTALBIAYA,
                    PJ.PEMILIKHEWAN_ID,
                    PH.NAMA as CUSTOMER_NAME,
                    PJ.PRODUK
                FROM PENJUALAN PJ
                LEFT JOIN PemilikHewan PH ON PJ.PEMILIKHEWAN_ID = PH.ID
                WHERE PJ.ID = :id AND PJ.onDelete = 0");
        $db->bind(':id', $_GET['id']);
        $transaction = $db->single();

        if (!$transaction) {
            die(json_encode(['success' => false, 'message' => 'Transaksi tidak ditemukan']));
        }

        // Get products from the transaction
        $db->query("SELECT PR.ID, PR.NAMA, PR.HARGA, PR.JUMLAH,
                          (SELECT COUNT(*) 
                           FROM TABLE(SELECT PRODUK FROM PENJUALAN WHERE ID = :trans_id) T 
                           WHERE T.COLUMN_VALUE = PR.ID) as QUANTITY
                   FROM TABLE(SELECT PRODUK FROM PENJUALAN WHERE ID = :id) TP
                   JOIN Produk PR ON TP.COLUMN_VALUE = PR.ID
                   GROUP BY PR.ID, PR.NAMA, PR.HARGA, PR.JUMLAH");
        $db->bind(':id', $_GET['id']);
        $db->bind(':trans_id', $_GET['id']);
        $products = $db->resultSet();

        echo json_encode([
            'success' => true,
            'transaction' => $transaction,
            'products' => $products
        ]);
        exit;
    } catch (Exception $e) {
        die(json_encode(['success' => false, 'message' => $e->getMessage()]));
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();

        $transactionId = $_POST['id'];
        $customerId = $_POST['customer_id'];
        $tanggalTransaksi = $_POST['tanggal_transaksi'];
        $selectedProducts = isset($_POST['produk']) ? $_POST['produk'] : [];
        $quantities = isset($_POST['quantity']) ? $_POST['quantity'] : [];

        // Get current transaction details
        $db->query("SELECT PRODUK FROM PENJUALAN WHERE ID = :id AND onDelete = 0 FOR UPDATE");
        $db->bind(':id', $transactionId);
        $currentTransaction = $db->single();

        if (!$currentTransaction) {
            throw new Exception('Transaksi tidak ditemukan.');
        }

        // Return current products to stock
        $db->query("SELECT COLUMN_VALUE as PRODUK_ID, COUNT(*) as QUANTITY 
                   FROM TABLE(SELECT PRODUK FROM PENJUALAN WHERE ID = :id) 
                   GROUP BY COLUMN_VALUE");
        $db->bind(':id', $transactionId);
        $currentProducts = $db->resultSet();

        foreach ($currentProducts as $product) {
            // Get current stock
            $db->query("SELECT JUMLAH FROM PRODUK WHERE ID = :id FOR UPDATE");
            $db->bind(':id', $product['PRODUK_ID']);
            $currentStock = $db->single()['JUMLAH'];

            // Return stock
            $newStock = $currentStock + $product['QUANTITY'];
            $db->query("UPDATE PRODUK SET JUMLAH = :jumlah WHERE ID = :id");
            $db->bind(':jumlah', $newStock);
            $db->bind(':id', $product['PRODUK_ID']);
            $db->execute();

            // Log stock return
            $db->query("INSERT INTO LOGPRODUK (STOKAWAL, STOKAKHIR, PERUBAHAN, KETERANGAN, TANGGALPERUBAHAN, PRODUK_ID, PEGAWAI_ID) 
                       VALUES (:stok_awal, :stok_akhir, :perubahan, 'Pengembalian Stok - Update Transaksi', SYSTIMESTAMP, :produk_id, :pegawai_id)");
            $db->bind(':stok_awal', $currentStock);
            $db->bind(':stok_akhir', $newStock);
            $db->bind(':perubahan', $product['QUANTITY']);
            $db->bind(':produk_id', $product['PRODUK_ID']);
            $db->bind(':pegawai_id', $_SESSION['employee_id']);
            $db->execute();
        }

        // Process new products
        $totalBiaya = 0;
        $productArrayValues = [];

        foreach ($selectedProducts as $prodId) {
            $quantity = $quantities[$prodId];
            
            // Get product details and check stock
            $db->query("SELECT HARGA, JUMLAH FROM Produk WHERE ID = :id AND onDelete = 0 FOR UPDATE");
            $db->bind(':id', $prodId);
            $product = $db->single();
            
            if (!$product) {
                throw new Exception("Produk tidak ditemukan atau sudah dihapus.");
            }

            $harga = $product['HARGA'];
            $stokAwal = $product['JUMLAH'];
            $stokAkhir = $stokAwal - $quantity;

            if ($stokAkhir < 0) {
                throw new Exception("Stok produk tidak mencukupi.");
            }

            $totalBiaya += $harga * $quantity;

            // Update stock
            $db->query("UPDATE Produk SET JUMLAH = :stok_akhir WHERE ID = :id");
            $db->bind(':stok_akhir', $stokAkhir);
            $db->bind(':id', $prodId);
            $db->execute();

            // Add to product array
            for ($i = 0; $i < $quantity; $i++) {
                $productArrayValues[] = $prodId;
            }

            // Log stock reduction
            $db->query("INSERT INTO LOGPRODUK (STOKAWAL, STOKAKHIR, PERUBAHAN, KETERANGAN, TANGGALPERUBAHAN, PRODUK_ID, PEGAWAI_ID) 
                       VALUES (:stok_awal, :stok_akhir, :perubahan, 'Update Transaksi Penjualan', SYSTIMESTAMP, :produk_id, :pegawai_id)");
            $db->bind(':stok_awal', $stokAwal);
            $db->bind(':stok_akhir', $stokAkhir);
            $db->bind(':perubahan', -$quantity);
            $db->bind(':produk_id', $prodId);
            $db->bind(':pegawai_id', $_SESSION['employee_id']);
            $db->execute();
        }

        // Create VARRAY of products
        $productArray = "ARRAYPRODUK('" . implode("','", $productArrayValues) . "')";
        
        // Update transaction
        $db->query("UPDATE PENJUALAN SET 
                    TANGGALTRANSAKSI = TO_TIMESTAMP(:tanggal, 'YYYY-MM-DD HH24:MI:SS'),
                    PRODUK = $productArray,
                    TOTALBIAYA = :total_biaya,
                    PEMILIKHEWAN_ID = :customer_id
                   WHERE ID = :id");
        $db->bind(':tanggal', $tanggalTransaksi);
        $db->bind(':total_biaya', $totalBiaya);
        $db->bind(':customer_id', $customerId);
        $db->bind(':id', $transactionId);
        $db->execute();

        $db->commit();
        
        $_SESSION['success_message'] = "Transaksi berhasil diupdate!";
        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} 