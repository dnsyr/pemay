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
    
    error_log("Received category_id: " . $categoryId);
    
    if (empty($categoryId)) {
        die(json_encode(['success' => false, 'error' => 'Category ID is required']));
    }

    // Query to get products by category
    $query = "SELECT ID, NAMA, HARGA, JUMLAH 
              FROM Produk 
              WHERE KATEGORIPRODUK_ID = :category_id 
              AND onDelete = 0 
              AND JUMLAH > 0 
              ORDER BY NAMA";
              
    error_log("Executing query: " . $query);
    
    $db->query($query);
    $db->bind(':category_id', $categoryId);
    $products = $db->resultSet();
    
    error_log("Found products: " . json_encode($products));

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'products' => $products
    ]);

} catch (Exception $e) {
    error_log("Error in get-products.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 