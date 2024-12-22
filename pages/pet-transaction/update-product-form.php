<?php
session_start();
require_once '../../config/database.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('HTTP/1.1 401 Unauthorized');
    exit('Unauthorized');
}

// Inisialisasi database
$db = new Database();

// Get transaction ID from query parameter
$transactionId = isset($_GET['id']) ? $_GET['id'] : null;

if (!$transactionId) {
    header('HTTP/1.1 400 Bad Request');
    exit('Transaction ID is required');
}

// Fetch transaction data
$query = "SELECT 
    PJ.ID,
    PJ.PEMILIKHEWAN_ID,
    PH.NAMA as PEMILIK_NAMA,
    PJ.TOTALBIAYA
FROM PENJUALAN PJ
LEFT JOIN PemilikHewan PH ON PJ.PEMILIKHEWAN_ID = PH.ID
WHERE PJ.ID = :id AND PJ.onDelete = 0";

$db->query($query);
$db->bind(':id', $transactionId);
$transaction = $db->single();

if (!$transaction) {
    header('HTTP/1.1 404 Not Found');
    exit('Transaction not found');
}

// Fetch all product categories
$db->query("SELECT ID, NAMA FROM KategoriProduk WHERE onDelete = 0 ORDER BY NAMA");
$categories = $db->resultSet();

// Fetch selected products with quantities using a separate query
$query = "
    WITH ProductQuantities AS (
        SELECT 
            PR.ID,
            PR.NAMA,
            PR.HARGA,
            COUNT(*) as QUANTITY
        FROM PENJUALAN PJ
        CROSS JOIN TABLE(PJ.PRODUK) TP
        JOIN Produk PR ON TP.COLUMN_VALUE = PR.ID
        WHERE PJ.ID = :id AND PR.onDelete = 0
        GROUP BY PR.ID, PR.NAMA, PR.HARGA
    )
    SELECT * FROM ProductQuantities ORDER BY NAMA";
$db->query($query);
$db->bind(':id', $transactionId);
$selectedProducts = $db->resultSet();
?>

<div class="p-4">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-bold">Update Transaction</h3>
        <label for="update-product-drawer" class="btn btn-sm btn-circle">✕</label>
    </div>

    <form id="updateTransactionForm" class="space-y-4">
        <input type="hidden" name="transaction_id" value="<?= htmlspecialchars($transactionId) ?>">
        <input type="hidden" name="customer_id" value="<?= htmlspecialchars($transaction['PEMILIKHEWAN_ID']) ?>">
        
        <!-- Customer Name (Disabled) -->
        <div class="form-control">
            <label class="label">
                <span class="label-text">Customer</span>
            </label>
            <input type="text" value="<?= htmlspecialchars($transaction['PEMILIK_NAMA']) ?>" class="input input-bordered w-full" disabled>
        </div>

        <!-- Product Category Selection -->
        <div class="form-control">
            <label class="label">
                <span class="label-text">Product Category</span>
            </label>
            <select id="update_category_select" class="select select-bordered w-full">
                <option value="">Select Category</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= $category['ID'] ?>">
                        <?= htmlspecialchars($category['NAMA']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Product Selection -->
        <div class="form-control">
            <label class="label">
                <span class="label-text">Product</span>
            </label>
            <select id="update_product_select" class="select select-bordered w-full" disabled>
                <option value="">Select Product</option>
            </select>
        </div>

        <!-- Selected Products Table -->
        <div class="overflow-x-auto border rounded-lg">
            <table class="table w-full">
                <thead>
                    <tr class="bg-[#D4F0EA]">
                        <th class="text-center">No.</th>
                        <th>Product</th>
                        <th class="text-right">Price</th>
                        <th class="text-center">Quantity</th>
                        <th class="text-right">Subtotal</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody id="update_selected_products">
                    <?php 
                    $no = 1;
                    foreach ($selectedProducts as $product): 
                        $subtotal = $product['HARGA'] * $product['QUANTITY'];
                    ?>
                        <tr data-product-id="<?= $product['ID'] ?>">
                            <td class="text-center"><?= $no++ ?></td>
                            <td><?= htmlspecialchars($product['NAMA']) ?></td>
                            <td class="text-right product-price">Rp <?= number_format($product['HARGA'], 0, ',', '.') ?></td>
                            <td class="text-center">
                                <input type="number" class="input input-bordered w-20 text-center quantity-input" 
                                       value="<?= $product['QUANTITY'] ?>" min="1">
                            </td>
                            <td class="text-right product-subtotal">Rp <?= number_format($subtotal, 0, ',', '.') ?></td>
                            <td class="text-center">
                                <button type="button" class="btn btn-error btn-sm remove-product"> 
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" class="text-right font-bold">Total:</td>
                        <td class="text-right font-bold">
                            Rp <span id="update_total_amount">
                                <?= number_format($transaction['TOTALBIAYA'], 0, ',', '.') ?>
                            </span>
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="mt-4 flex justify-end gap-2">
            <label for="update-product-drawer" class="btn">Cancel</label>
            <button type="submit" class="btn btn-primary">Update Transaction</button>
        </div>
    </form>
</div>

<script>
$(document).ready(function() {
    // Debug: Log initial products
    console.log('Initial selected products:', <?= json_encode($selectedProducts) ?>);

    // Event delegation for quantity changes
    $('#update_selected_products').on('input', '.quantity-input', function() {
        console.log('Quantity changed');
        const $row = $(this).closest('tr');
        const priceText = $row.find('.product-price').text();
        console.log('Price text:', priceText);
        
        const price = parseInt(priceText.replace(/[^\d]/g, ''));
        console.log('Parsed price:', price);
        
        const quantity = parseInt($(this).val()) || 0;
        console.log('Quantity:', quantity);
        
        const subtotal = price * quantity;
        console.log('Calculated subtotal:', subtotal);
        
        $row.find('.product-subtotal').text(`Rp ${formatNumber(subtotal)}`);
        updateTotal();
    });

    // Event delegation for remove buttons
    $('#update_selected_products').on('click', '.remove-product', function() {
        console.log('Removing product row');
        $(this).closest('tr').remove();
        renumberRows();
        updateTotal();
    });

    // Initialize select2 for category
    $('#update_category_select').select2({
        dropdownParent: $('#update-product-drawer'),
        placeholder: 'Select Category',
        width: '100%'
    }).on('change', function() {
        const categoryId = $(this).val();
        console.log('Selected category:', categoryId);
        const $productSelect = $('#update_product_select');
        
        if (categoryId) {
            $productSelect.prop('disabled', false);
            // Load products for selected category
            const url = `get-products.php?category_id=${categoryId}`;
            console.log('Fetching products from:', url);
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    console.log('Received data:', data);
                    if (!data.success) {
                        throw new Error(data.error || 'Failed to load products');
                    }
                    
                    const products = data.products;
                    console.log('Processing products:', products);
                    
                    $productSelect.empty().append('<option value="">Select Product</option>');
                    products.forEach(product => {
                        console.log('Adding product:', product);
                        const option = new Option(
                            `${product.NAMA} - Rp ${formatNumber(product.HARGA)}`,
                            product.ID,
                            false,
                            false
                        );
                        $(option).data('product', product);
                        $productSelect.append(option);
                    });
                    $productSelect.trigger('change');
                })
                .catch(error => {
                    console.error('Error loading products:', error);
                    alert('Failed to load products: ' + error.message);
                });
        } else {
            $productSelect.prop('disabled', true)
                         .empty()
                         .append('<option value="">Select Product</option>')
                         .trigger('change');
        }
    });

    // Initialize select2 for product
    $('#update_product_select').select2({
        dropdownParent: $('#update-product-drawer'),
        placeholder: 'Select Product',
        width: '100%'
    }).on('select2:select', function(e) {
        console.log('Product selected:', e.params.data);
        const $option = $(e.params.data.element);
        const product = $option.data('product');
        console.log('Product data:', product);
        
        if (product) {
            addProduct(product.ID, product.NAMA, product.HARGA);
        }
        
        $(this).val(null).trigger('change');
    });

    // Handle form submission
    $('#updateTransactionForm').on('submit', function(e) {
        e.preventDefault();
        updateTransaction();
    });

    // Calculate initial total
    updateTotal();
});

function formatNumber(number) {
    return new Intl.NumberFormat('id-ID').format(number);
}

function updateTotal() {
    let total = 0;
    $('#update_selected_products tr').each(function() {
        const subtotalText = $(this).find('.product-subtotal').text();
        console.log('Subtotal text:', subtotalText);
        const subtotal = parseInt(subtotalText.replace(/[^\d]/g, '')) || 0;
        console.log('Parsed subtotal:', subtotal);
        total += subtotal;
    });
    console.log('Total calculated:', total);
    $('#update_total_amount').text(formatNumber(total));
}

function renumberRows() {
    $('#update_selected_products tr').each(function(index) {
        $(this).find('td:first').text(index + 1);
    });
}

function addProduct(productId, productName, productPrice) {
    console.log('Adding product:', { productId, productName, productPrice });
    
    // Check if product already exists
    if ($(`#update_selected_products tr[data-product-id="${productId}"]`).length) {
        alert('Product already added');
        return;
    }

    const $tbody = $('#update_selected_products');
    const rowCount = $tbody.find('tr').length;
    
    const row = `
        <tr data-product-id="${productId}">
            <td class="text-center">${rowCount + 1}</td>
            <td>${productName}</td>
            <td class="text-right product-price">Rp ${formatNumber(productPrice)}</td>
            <td class="text-center">
                <input type="number" class="input input-bordered w-20 text-center quantity-input" 
                       value="1" min="1">
            </td>
            <td class="text-right product-subtotal">Rp ${formatNumber(productPrice)}</td>
            <td class="text-center">
                <button type="button" class="btn btn-error btn-sm remove-product">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `;

    $tbody.append(row);
    updateTotal();
}

function updateTransaction() {
    const formData = new FormData();
    formData.append('transaction_id', $('[name="transaction_id"]').val());
    formData.append('customer_id', $('[name="customer_id"]').val());

    // Get selected products with quantities
    const products = $('#update_selected_products tr').map(function() {
        const product = {
            id: $(this).data('product-id'),
            quantity: parseInt($(this).find('.quantity-input').val()) || 0
        };
        console.log('Product to update:', product);
        return product;
    }).get();
    
    console.log('All products to update:', products);
    formData.append('products', JSON.stringify(products));

    fetch('update-transaction.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('Update response:', data);
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Failed to update transaction');
        }
    })
    .catch(error => {
        console.error('Error updating transaction:', error);
        alert('Failed to update transaction');
    });
}
</script> 