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
    $customerId = $_POST['customer_id'] ?? null;
    if ($customerId === '' || $customerId === 'null' || empty($customerId)) {
        $customerId = null;
    }
    $products = $_POST['produk'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $categoryType = $_POST['category_type'] ?? 'produk';

    // Validate products
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
        $table = ($categoryType === 'produk') ? 'Produk' : 'Obat';
        $db->query("SELECT HARGA, JUMLAH FROM $table WHERE ID = :id AND onDelete = 0 FOR UPDATE");
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
        $db->query("UPDATE $table SET JUMLAH = :stok_akhir WHERE ID = :id");
        $db->bind(':stok_akhir', $stokAkhir);
        $db->bind(':id', $prodId);
        $db->execute();

        // Add product to array multiple times based on quantity
        for ($i = 0; $i < $quantity; $i++) {
            $productArrayValues[] = $prodId;
        }

        // Insert log with negative perubahan (reduction)
        $perubahan = -$quantity;
        $logTable = ($categoryType === 'produk') ? 'LOGPRODUK' : 'LOGOBAT';
        $idColumn = ($categoryType === 'produk') ? 'PRODUK_ID' : 'OBAT_ID';

        $db->query("INSERT INTO $logTable (STOKAWAL, STOKAKHIR, PERUBAHAN, KETERANGAN, TANGGALPERUBAHAN, $idColumn, PEGAWAI_ID) 
                   VALUES (:stok_awal, :stok_akhir, :perubahan, 'Transaksi Penjualan', SYSTIMESTAMP, :produk_id, :pegawai_id)");
        $db->bind(':stok_awal', $stokAwal);
        $db->bind(':stok_akhir', $stokAkhir);
        $db->bind(':perubahan', $perubahan);
        $db->bind(':produk_id', $prodId);
        $db->bind(':pegawai_id', $_SESSION['employee_id']);
        $db->execute();
    }

    // Create VARRAY of products
    $arrayType = ($categoryType === 'produk') ? 'ARRAYPRODUK' : 'ARRAYOBAT';
    $productArray = "$arrayType(" . implode(",", array_map(function ($id) {
        return "'$id'";
    }, $productArrayValues)) . ")";

    // Insert into Penjualan table using SYSTIMESTAMP for current date and time
    $produkColumn = ($categoryType === 'produk') ? 'PRODUK' : 'OBAT';

    // Prepare base query
    if ($customerId !== null) {
        $query = "INSERT INTO Penjualan (TANGGALTRANSAKSI, $produkColumn, TOTALBIAYA, PEGAWAI_ID, PEMILIKHEWAN_ID) 
                 VALUES (SYSTIMESTAMP, $productArray, :total_biaya, :employee_id, :customer_id)";
        $params = [
            ':total_biaya' => $totalBiaya,
            ':employee_id' => $_SESSION['employee_id'],
            ':customer_id' => $customerId
        ];
    } else {
        $query = "INSERT INTO Penjualan (TANGGALTRANSAKSI, $produkColumn, TOTALBIAYA, PEGAWAI_ID) 
                 VALUES (SYSTIMESTAMP, $productArray, :total_biaya, :employee_id)";
        $params = [
            ':total_biaya' => $totalBiaya,
            ':employee_id' => $_SESSION['employee_id']
        ];
    }

    error_log("Query: " . $query);
    error_log("Params: " . print_r($params, true));

    $db->query($query);
    foreach ($params as $key => $value) {
        $db->bind($key, $value);
    }
    $db->execute();

    // Commit transaction
    $db->commit();

    die(json_encode(['success' => true, 'message' => 'Transaksi berhasil disimpan']));
} catch (Exception $e) {
    // Rollback transaction on error
    $db->rollBack();
    error_log("Error in add-product-transaction.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    die(json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]));
}
