<?php
session_start();
require_once '../../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['success' => false, 'message' => 'Invalid request method']));
}

// Initialize database connection
$db = new Database();

try {
    // Get form data
    $customerId = $_POST['customer_id'] ?? '';
    $products = $_POST['produk'] ?? [];
    $quantities = $_POST['quantity'] ?? [];

    // Validate required fields
    if (empty($customerId)) {
        die(json_encode(['success' => false, 'message' => 'Customer harus dipilih']));
    }
    if (empty($products)) {
        die(json_encode(['success' => false, 'message' => 'Pilih setidaknya satu produk']));
    }

    // Start transaction
    $db->beginTransaction();

    // Calculate total cost and prepare product array
    $totalBiaya = 0;
    $productArrayValues = [];

    foreach ($products as $prodId) {
        $quantity = $quantities[$prodId];

        // Find product price and check stock
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

        // Add product to array multiple times based on quantity
        for ($i = 0; $i < $quantity; $i++) {
            $productArrayValues[] = $prodId;
        }

        // Insert log with negative perubahan (reduction)
        $perubahan = -$quantity;
        $db->query("INSERT INTO LOGPRODUK (STOKAWAL, STOKAKHIR, PERUBAHAN, KETERANGAN, TANGGALPERUBAHAN, PRODUK_ID, PEGAWAI_ID) 
                   VALUES (:stok_awal, :stok_akhir, :perubahan, 'Transaksi Penjualan', SYSTIMESTAMP, :produk_id, :pegawai_id)");
        $db->bind(':stok_awal', $stokAwal);
        $db->bind(':stok_akhir', $stokAkhir);
        $db->bind(':perubahan', $perubahan);
        $db->bind(':produk_id', $prodId);
        $db->bind(':pegawai_id', $_SESSION['employee_id']);
        $db->execute();
    }

    // Create VARRAY of products
    $productArray = "ARRAYPRODUK(" . implode(",", array_map(function ($id) {
        return "'$id'";
    }, $productArrayValues)) . ")";

    // Insert into Penjualan table using SYSTIMESTAMP for current date and time
    $db->query("INSERT INTO Penjualan (TANGGALTRANSAKSI, PRODUK, TOTALBIAYA, PEGAWAI_ID, PEMILIKHEWAN_ID) 
               VALUES (SYSTIMESTAMP, $productArray, :total_biaya, :employee_id, :customer_id)");
    $db->bind(':total_biaya', $totalBiaya);
    $db->bind(':employee_id', $_SESSION['employee_id']);
    $db->bind(':customer_id', $customerId);
    $db->execute();

    // Commit transaction
    $db->commit();

    die(json_encode(['success' => true, 'message' => 'Transaksi berhasil disimpan']));
} catch (Exception $e) {
    // Rollback transaction on error
    $db->rollBack();
    die(json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]));
}
