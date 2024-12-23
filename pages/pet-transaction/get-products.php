<?php
session_start();
require_once '../../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    http_response_code(401);
    die(json_encode(['success' => false, 'error' => 'Unauthorized']));
}

// Initialize database connection
$db = new Database();

try {
    $categoryId = isset($_GET['category_id']) ? $_GET['category_id'] : '';
    $categoryType = isset($_GET['category_type']) ? $_GET['category_type'] : '';
    
    error_log("Received request in get-products.php");
    error_log("category_id: " . $categoryId);
    error_log("category_type: " . $categoryType);
    
    if (empty($categoryId)) {
        die(json_encode(['success' => false, 'error' => 'Category ID is required']));
    }

    // Verify category exists
    if ($categoryType === 'produk') {
        $checkQuery = "SELECT COUNT(*) as count FROM KategoriProduk WHERE ID = :id AND onDelete = 0";
    } else if ($categoryType === 'obat') {
        $checkQuery = "SELECT COUNT(*) as count FROM KategoriObat WHERE ID = :id AND onDelete = 0";
    } else {
        die(json_encode(['success' => false, 'error' => 'Invalid category type: ' . $categoryType]));
    }

    $db->query($checkQuery);
    $db->bind(':id', $categoryId);
    $result = $db->single();
    
    error_log("Category check result: " . json_encode($result));

    if ($result['COUNT'] == 0) {
        die(json_encode(['success' => false, 'error' => 'Category not found']));
    }

    // Query to get products by category
    if ($categoryType === 'produk') {
        $query = "SELECT ID, NAMA, HARGA, JUMLAH 
                FROM Produk 
                WHERE KATEGORIPRODUK_ID = :category_id 
                AND onDelete = 0 
                AND JUMLAH > 0 
                ORDER BY NAMA";
    } else if ($categoryType === 'obat') {
        $query = "SELECT ID, NAMA, HARGA, JUMLAH 
                FROM Produk 
                WHERE KATEGORIOBAT_ID = :category_id 
                AND onDelete = 0 
                AND JUMLAH > 0 
                ORDER BY NAMA";
    }
              
    error_log("Executing query: " . $query);
    error_log("With category_id: " . $categoryId);
    
    $db->query($query);
    $db->bind(':category_id', $categoryId);
    $products = $db->resultSet();
    
    error_log("Query executed successfully");
    error_log("Found " . count($products) . " products");
    error_log("Products data: " . json_encode($products));

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'products' => $products
    ]);

} catch (PDOException $e) {
    error_log("Database error in get-products.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Error in get-products.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Terjadi kesalahan: ' . $e->getMessage()
    ]);
} 