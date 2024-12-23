<!-- Edit Transaction Drawer -->
<div class="drawer drawer-end" id="edit_drawer_container">
    <input id="edit_drawer" type="checkbox" class="drawer-toggle" />
    
    <div class="drawer-content">
        <!-- Page content here -->
    </div>
    
    <div class="drawer-side z-50">
        <label for="edit_drawer" class="drawer-overlay"></label>
        
        <div class="w-[600px] min-h-full bg-white text-[#363636] p-6 overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold">Edit Transaction</h3>
                <label for="edit_drawer" class="btn btn-ghost btn-sm">✕</label>
            </div>

            <form id="editTransactionForm" class="space-y-4">
                <input type="hidden" id="edit_transaction_id" name="id">
                
                <!-- Transaction Date -->
                <div class="form-control w-full">
                    <label class="label">
                        <span class="label-text">Transaction Date</span>
                        <span class="label-text-alt text-error">*</span>
                    </label>
                    <input type="datetime-local" id="edit_tanggal_transaksi" name="tanggal_transaksi" class="input input-bordered w-full" required>
                </div>

                <!-- Customer Selection -->
                <div class="form-control w-full">
                    <label class="label">
                        <span class="label-text">Customer</span>
                        <span class="label-text-alt text-error">*</span>
                    </label>
                    <select id="edit_customer_id" name="customer_id" class="select select-bordered w-full" required>
                        <option value="">Select Customer</option>
                        <?php
                        $db->query("SELECT ID, NAMA FROM PemilikHewan WHERE onDelete = 0 ORDER BY NAMA");
                        $customers = $db->resultSet();
                        foreach ($customers as $customer) {
                            echo '<option value="' . $customer['ID'] . '">' . htmlspecialchars($customer['NAMA']) . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <!-- Product Selection -->
                <div class="form-control w-full">
                    <label class="label">
                        <span class="label-text">Product Category</span>
                    </label>
                    <select id="edit_product_category" class="select select-bordered w-full">
                        <option value="">Select Category</option>
                        <?php
                        $db->query("SELECT DISTINCT KATEGORI FROM Produk WHERE onDelete = 0 ORDER BY KATEGORI");
                        $categories = $db->resultSet();
                        foreach ($categories as $category) {
                            echo '<option value="' . htmlspecialchars($category['KATEGORI']) . '">' . htmlspecialchars($category['KATEGORI']) . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <!-- Selected Products Table -->
                <div class="overflow-x-auto">
                    <table class="table w-full">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="edit_selected_products">
                            <!-- Selected products will be added here -->
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-right font-bold">Total:</td>
                                <td class="font-bold">Rp <span id="edit_total_amount">0</span></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Product List -->
                <div id="edit_product_list" class="grid grid-cols-2 gap-4 mt-4">
                    <!-- Products will be loaded here based on category -->
                </div>

                <div class="mt-6">
                    <button type="submit" class="btn btn-primary w-full">Update Transaction</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Load products when category changes
$('#edit_product_category').on('change', function() {
    const category = $(this).val();
    if (!category) return;

    $.get('get-products.php', { category: category })
        .done(function(response) {
            const data = JSON.parse(response);
            if (data.success) {
                let html = '';
                data.products.forEach(product => {
                    if (product.JUMLAH > 0) {
                        html += `
                            <div class="card bg-base-100 shadow-md">
                                <div class="card-body p-4">
                                    <h3 class="card-title text-sm">${product.NAMA}</h3>
                                    <p class="text-sm">Stock: ${product.JUMLAH}</p>
                                    <p class="text-sm">Price: Rp ${formatNumber(product.HARGA)}</p>
                                    <button type="button" onclick="addEditProduct(${product.ID}, '${product.NAMA}', ${product.HARGA}, ${product.JUMLAH})" class="btn btn-primary btn-sm mt-2">Add</button>
                                </div>
                            </div>`;
                    }
                });
                $('#edit_product_list').html(html);
            } else {
                alert('Terjadi kesalahan saat memuat produk. Silakan coba lagi.');
            }
        })
        .fail(function() {
            alert('Terjadi kesalahan saat memuat produk. Silakan coba lagi.');
        });
});

let editSelectedProducts = new Map();

function addEditProduct(id, name, price, maxStock) {
    const currentProduct = editSelectedProducts.get(id);
    const currentQty = currentProduct ? currentProduct.quantity : 0;
    
    if (currentQty >= maxStock) {
        alert('Stok tidak mencukupi!');
        return;
    }

    if (currentProduct) {
        currentProduct.quantity++;
        updateEditProductRow(id);
    } else {
        editSelectedProducts.set(id, {
            name: name,
            price: price,
            quantity: 1,
            maxStock: maxStock
        });
        addEditProductRow(id);
    }
    
    updateEditTotal();
}

function updateEditProductQuantity(id, change) {
    const product = editSelectedProducts.get(id);
    if (!product) return;

    const newQty = product.quantity + change;
    if (newQty <= 0) {
        editSelectedProducts.delete(id);
        $(`#edit_product_row_${id}`).remove();
    } else if (newQty <= product.maxStock) {
        product.quantity = newQty;
        updateEditProductRow(id);
    } else {
        alert('Stok tidak mencukupi!');
        return;
    }
    
    updateEditTotal();
}

function removeEditProduct(id) {
    editSelectedProducts.delete(id);
    $(`#edit_product_row_${id}`).remove();
    updateEditTotal();
}

function addEditProductRow(id) {
    const product = editSelectedProducts.get(id);
    const html = `
        <tr id="edit_product_row_${id}">
            <td>${product.name}</td>
            <td>Rp ${formatNumber(product.price)}</td>
            <td>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="updateEditProductQuantity(${id}, -1)" class="btn btn-xs">-</button>
                    <span>${product.quantity}</span>
                    <button type="button" onclick="updateEditProductQuantity(${id}, 1)" class="btn btn-xs">+</button>
                </div>
            </td>
            <td>Rp ${formatNumber(product.price * product.quantity)}</td>
            <td>
                <button type="button" onclick="removeEditProduct(${id})" class="btn btn-error btn-xs">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </td>
        </tr>`;
    $('#edit_selected_products').append(html);
}

function updateEditProductRow(id) {
    const product = editSelectedProducts.get(id);
    const row = $(`#edit_product_row_${id}`);
    row.find('td:nth-child(3) span').text(product.quantity);
    row.find('td:nth-child(4)').text(`Rp ${formatNumber(product.price * product.quantity)}`);
}

function updateEditTotal() {
    let total = 0;
    editSelectedProducts.forEach(product => {
        total += product.price * product.quantity;
    });
    $('#edit_total_amount').text(formatNumber(total));
}

function loadTransaction(id) {
    editSelectedProducts.clear();
    $('#edit_selected_products').empty();
    updateEditTotal();
    
    $.get('edit-transaction.php', { id: id })
        .done(function(response) {
            const data = JSON.parse(response);
            if (data.success) {
                const transaction = data.transaction;
                
                // Set form values
                $('#edit_transaction_id').val(transaction.ID);
                $('#edit_tanggal_transaksi').val(formatDateTime(transaction.TANGGALTRANSAKSI));
                $('#edit_customer_id').val(transaction.PEMILIKHEWAN_ID);
                
                // Load products
                data.products.forEach(product => {
                    editSelectedProducts.set(product.ID, {
                        name: product.NAMA,
                        price: product.HARGA,
                        quantity: product.QUANTITY,
                        maxStock: product.JUMLAH + product.QUANTITY
                    });
                    addEditProductRow(product.ID);
                });
                
                updateEditTotal();
                
                // Show drawer
                document.getElementById('edit_drawer').checked = true;
            } else {
                alert(data.message || 'Terjadi kesalahan saat memuat transaksi');
            }
        })
        .fail(function() {
            alert('Terjadi kesalahan saat memuat transaksi');
        });
}

$('#editTransactionForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    // Add products and quantities
    editSelectedProducts.forEach((product, id) => {
        formData.append('produk[]', id);
        formData.append(`quantity[${id}]`, product.quantity);
    });
    
    $.ajax({
        url: 'edit-transaction.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            const data = JSON.parse(response);
            if (data.success) {
                document.getElementById('edit_drawer').checked = false;
                location.reload();
            } else {
                alert(data.message || 'Terjadi kesalahan saat mengupdate transaksi');
            }
        },
        error: function() {
            alert('Terjadi kesalahan saat mengupdate transaksi');
        }
    });
});

function formatDateTime(dateStr) {
    const date = new Date(dateStr);
    return date.toISOString().slice(0, 16);
}

function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}
</script> 