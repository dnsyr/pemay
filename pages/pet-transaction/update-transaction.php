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
    $transactionId = $_POST['transaction_id'] ?? '';
    $customerId = $_POST['customer_id'] ?? '';
    $products = json_decode($_POST['products'] ?? '[]', true);

    error_log("Update Transaction - Input Data:");
    error_log("Transaction ID: " . $transactionId);
    error_log("Customer ID: " . $customerId);
    error_log("Products: " . json_encode($products));

    // Validate required fields
    if (empty($transactionId)) {
        die(json_encode(['success' => false, 'message' => 'Transaction ID is required']));
    }
    if (empty($customerId)) {
        die(json_encode(['success' => false, 'message' => 'Customer ID is required']));
    }
    if (empty($products)) {
        die(json_encode(['success' => false, 'message' => 'At least one product is required']));
    }

    // Start transaction
    $db->beginTransaction();

    // Get current products in transaction
    $query = "
        SELECT 
            PR.ID,
            PR.NAMA,
            COUNT(*) as QUANTITY
        FROM PENJUALAN PJ
        CROSS JOIN TABLE(PJ.PRODUK) TP
        JOIN Produk PR ON TP.COLUMN_VALUE = PR.ID
        WHERE PJ.ID = :id AND PR.onDelete = 0
        GROUP BY PR.ID, PR.NAMA
    ";
    error_log("Current Products Query: " . $query);
    
    $db->query($query);
    $db->bind(':id', $transactionId);
    $currentProducts = $db->resultSet();
    
    error_log("Current Products in Transaction: " . json_encode($currentProducts));
    
    // Create map of current quantities
    $currentQuantities = [];
    foreach ($currentProducts as $product) {
        $currentQuantities[$product['ID']] = $product['QUANTITY'];
    }
    
    error_log("Current Quantities Map: " . json_encode($currentQuantities));

    // Calculate total cost and prepare product array
    $totalBiaya = 0;
    $productArrayValues = [];
    
    // Process each product
    foreach ($products as $product) {
        $prodId = $product['id'];
        $newQuantity = $product['quantity'];
        $currentQuantity = $currentQuantities[$prodId] ?? 0;
        
        error_log("Processing Product ID: " . $prodId);
        error_log("New Quantity: " . $newQuantity);
        error_log("Current Quantity: " . $currentQuantity);
        
        // Get product details and lock for update
        $db->query("SELECT ID, NAMA, HARGA, JUMLAH FROM Produk WHERE ID = :id AND onDelete = 0 FOR UPDATE");
        $db->bind(':id', $prodId);
        $productDetails = $db->single();
        
        error_log("Product Details: " . json_encode($productDetails));
        
        if (!$productDetails) {
            throw new Exception("Product not found or has been deleted");
        }

        // Calculate stock changes
        if ($newQuantity > $currentQuantity) {
            // Need more stock
            $stockNeeded = $newQuantity - $currentQuantity;
            error_log("Need more stock: " . $stockNeeded);
            
            if ($productDetails['JUMLAH'] < $stockNeeded) {
                throw new Exception("Insufficient stock for product: " . $productDetails['NAMA']);
            }
            
            // Decrease stock
            $db->query("UPDATE Produk SET JUMLAH = JUMLAH - :amount WHERE ID = :id");
            $db->bind(':amount', $stockNeeded);
            $db->bind(':id', $prodId);
            $db->execute();
            
            error_log("Stock decreased by: " . $stockNeeded);
            
        } elseif ($newQuantity < $currentQuantity) {
            // Return stock
            $stockReturn = $currentQuantity - $newQuantity;
            error_log("Returning stock: " . $stockReturn);
            
            $db->query("UPDATE Produk SET JUMLAH = JUMLAH + :amount WHERE ID = :id");
            $db->bind(':amount', $stockReturn);
            $db->bind(':id', $prodId);
            $db->execute();
            
            error_log("Stock increased by: " . $stockReturn);
        }

        // Add to total cost
        $productTotal = $productDetails['HARGA'] * $newQuantity;
        $totalBiaya += $productTotal;
        error_log("Product Total: " . $productTotal);
        error_log("Running Total: " . $totalBiaya);

        // Add product to array multiple times based on new quantity
        for ($i = 0; $i < $newQuantity; $i++) {
            $productArrayValues[] = $prodId;
        }
    }
    
    error_log("Final Product Array: " . json_encode($productArrayValues));
    error_log("Final Total: " . $totalBiaya);

    // Update transaction
    $updateQuery = "
        UPDATE PENJUALAN 
        SET PRODUK = ARRAY_PRODUK(:products),
            TOTALBIAYA = :total_biaya
        WHERE ID = :id
    ";
    error_log("Update Query: " . $updateQuery);
    
    $db->query($updateQuery);
    $db->bind(':products', $productArrayValues);
    $db->bind(':total_biaya', $totalBiaya);
    $db->bind(':id', $transactionId);
    $db->execute();

    // Commit transaction
    $db->commit();
    error_log("Transaction committed successfully");

    echo json_encode([
        'success' => true,
        'message' => 'Transaction updated successfully'
    ]);

} catch (Exception $e) {
    // Rollback on error
    if ($db->inTransaction()) {
        $db->rollBack();
        error_log("Transaction rolled back due to error");
    }
    
    error_log("Error in update-transaction.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 