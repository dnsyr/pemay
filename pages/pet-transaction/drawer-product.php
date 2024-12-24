<?php
require_once '../../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: ../../auth/login.php");
    exit();
}

// Initialize database connection
$db = new Database();

// Fetch categories for filter
$db->query("SELECT * FROM KategoriProduk WHERE ONDELETE = 0 ORDER BY NAMA");
$categoriesProduk = $db->resultSet();

// Fetch KategoriObat
$db->query("SELECT * FROM KategoriObat WHERE ONDELETE = 0 ORDER BY NAMA");
$categoriesObat = $db->resultSet();

// Get current datetime in Indonesia/Jakarta timezone
date_default_timezone_set('Asia/Jakarta');
$currentDateTime = new DateTime();
$minDateTime = $currentDateTime->format('Y-m-d\TH:i');
?>

<!-- Add Transaction Drawer -->
<div class="drawer drawer-end z-[9999]">
    <input id="add_drawer" type="checkbox" class="drawer-toggle" />
    <div class="drawer-side">
        <label for="add_drawer" class="drawer-overlay"></label>
        <div class="p-6 w-[600px] min-h-full bg-[#FCFCFC] text-base-content relative z-[9999]">
            <div class="flex justify-between items-center mb-6">
                <h3 class="font-bold text-lg text-[#363636]">Add Transaction</h3>
                <label for="add_drawer" class="btn btn-sm btn-circle">✕</label>
            </div>

            <form method="POST" class="space-y-6" id="transactionForm" action="add-product-transaction.php">
                <!-- Transaction Date -->
                <div class="form-control">
                    <label for="transaction_date" class="label font-semibold">Transaction Date</label>
                    <input type="datetime-local" 
                           id="transaction_date" 
                           name="transaction_date" 
                           class="input input-bordered w-full" 
                           min="<?php echo $minDateTime; ?>" 
                           value="<?php echo $minDateTime; ?>" 
                           required>
                </div>

                <!-- Customer Selection -->
                <div class="form-control">
                    <label for="customer_id" class="label font-semibold">Customer</label>
                    <select name="customer_id" id="customer_id" class="select2 select select-bordered w-full">
                        <option value="">Non-Member</option>
                        <?php 
                        $db->query("SELECT ID, NAMA FROM PemilikHewan WHERE onDelete = 0 ORDER BY NAMA");
                        $customers = $db->resultSet();
                        foreach ($customers as $customer) {
                            echo '<option value="' . htmlentities($customer['ID']) . '">' . htmlentities($customer['NAMA']) . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <!-- Product Selection -->
                <div class="form-control">
                    <label class="label font-semibold">Cari & Tambah Produk</label>
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <!-- Category Type Selection -->
                        <select name="category_type" class="select2 select select-bordered w-full">
                            <option value="">Pilih Tipe Kategori</option>
                            <option value="produk">Produk</option>
                            <option value="obat">Obat</option>
                        </select>

                        <!-- Category Selection -->
                        <select name="category" class="select2 select select-bordered w-full" disabled>
                            <option value="">Pilih Kategori</option>
                        </select>
                    </div>

                    <!-- Product Selection -->
                    <select name="product" class="select2 select select-bordered w-full mb-4" disabled>
                        <option value="">Pilih Produk</option>
                    </select>

                    <!-- Selected Products Table -->
                    <div class="overflow-x-auto">
                        <label class="label font-semibold">Produk Terpilih</label>
                        <table class="table w-full">
                            <thead>
                                <tr class="bg-[#D4F0EA]">
                                    <th class="text-left">Nama Produk</th>
                                    <th class="text-right">Harga</th>
                                    <th class="text-center">Stok</th>
                                    <th class="text-center">Kuantitas</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="selected_products">
                                <!-- Selected products will be shown here -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Total -->
                <div class="form-control">
                    <label class="label font-semibold">Total Biaya</label>
                    <div class="text-xl font-bold">Rp <span id="total_amount">0</span></div>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-4">
                    <button type="submit" class="btn bg-[#D4F0EA] hover:bg-[#B2E0D6] text-[#363636] border-[#363636]">
                        Simpan Transaksi
                    </button>
                    <label for="add_drawer" class="btn btn-outline">Batal</label>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Definisikan fungsi hitungTotal di awal script
function hitungTotal() {
    console.log('Menghitung total...');
    let total = 0;
    
    // Iterasi setiap baris produk
    $('#selected_products tr').each(function() {
        const priceText = $(this).find('.product-price').text();
        const quantity = parseInt($(this).find('input[type="number"]').val()) || 0;
        
        // Ekstrak angka dari format "Rp X.XXX.XXX"
        const price = parseInt(priceText.replace(/[^\d]/g, '')) || 0;
        
        const subtotal = price * quantity;
        console.log('Price:', price, 'Quantity:', quantity, 'Subtotal:', subtotal);
        
        // Update subtotal untuk baris ini
        $(this).find('.product-subtotal').text(`Rp ${formatNumber(subtotal)}`);
        
        total += subtotal;
    });
    
    console.log('Total akhir:', total);
    $('#total_amount').text(formatNumber(total));
}

function formatNumber(number) {
    return new Intl.NumberFormat('id-ID').format(number);
}

document.addEventListener('DOMContentLoaded', function() {
    // Check if jQuery is loaded
    if (typeof jQuery === 'undefined') {
        console.error('jQuery is not loaded!');
        return;
    }

    // Event delegation for quantity changes
    $(document).on('input', '.quantity-input', function() {
        console.log('Quantity changed');
        hitungTotal();
    });

    // Event delegation for remove buttons
    $(document).on('click', '.remove-product', function() {
        console.log('Removing product row');
        $(this).closest('tr').remove();
        renumberRows();
        hitungTotal();
    });

    // Initialize Select2 when drawer is opened
    const drawer = document.getElementById('add_drawer');
    const initSelect2 = () => {
        console.log('Initializing Select2...');
        
        // Destroy existing Select2 instances if any
        $('#customer_id').select2('destroy');
        $('select[name="category_type"]').select2('destroy');
        $('select[name="category"]').select2('destroy');
        $('select[name="product"]').select2('destroy');
        
        // Reinitialize Select2
        $('#customer_id').select2({
            dropdownParent: document.querySelector('.drawer-side'),
            width: '100%'
        });

        $('select[name="category_type"]').select2({
            dropdownParent: document.querySelector('.drawer-side'),
            width: '100%'
        });

        $('select[name="category"]').select2({
            dropdownParent: document.querySelector('.drawer-side'),
            width: '100%'
        });

        $('select[name="product"]').select2({
            dropdownParent: document.querySelector('.drawer-side'),
            width: '100%'
        });
    };

    // Add event listener for drawer
    drawer.addEventListener('change', function(e) {
        if (e.target.checked) {
            // Wait for drawer animation to complete
            setTimeout(initSelect2, 300);
        }
    });

    const categoryTypeSelect = document.querySelector('select[name="category_type"]');
    const categorySelect = document.querySelector('select[name="category"]');
    const productSelect = document.querySelector('select[name="product"]');
    const transactionDateInput = document.getElementById('transaction_date');

    // Set default value for transaction date to current time in Asia/Jakarta
    const now = new Date();
    const jakartaTime = new Date(now.getTime() + (7 * 60 * 60 * 1000)); // UTC+7
    transactionDateInput.value = jakartaTime.toISOString().slice(0, 16);

    // Validate transaction date
    transactionDateInput.addEventListener('change', function() {
        const selectedDate = new Date(this.value);
        const now = new Date();
        
        if (selectedDate < now) {
            alert('Tidak dapat memilih waktu yang sudah lewat');
            const jakartaTime = new Date(now.getTime() + (7 * 60 * 60 * 1000));
            this.value = jakartaTime.toISOString().slice(0, 16);
        }
    });

    if (categoryTypeSelect && categorySelect && productSelect) {
        // Category Type Change Event
        $(categoryTypeSelect).on('change', function() {
            const selectedType = this.value;
            $(categorySelect).prop('disabled', selectedType === '').trigger('change');
            $(productSelect).prop('disabled', true).trigger('change');
            
            // Clear and update options
            $(categorySelect).empty().append('<option value="">Pilih Kategori</option>');
            $(productSelect).empty().append('<option value="">Pilih Produk</option>');

            if (selectedType === 'produk') {
                <?php foreach ($categoriesProduk as $category): ?>
                    $(categorySelect).append(new Option('<?php echo htmlspecialchars($category['NAMA']); ?>', '<?php echo $category['ID']; ?>'));
                <?php endforeach; ?>
            } else if (selectedType === 'obat') {
                <?php foreach ($categoriesObat as $category): ?>
                    $(categorySelect).append(new Option('<?php echo htmlspecialchars($category['NAMA']); ?>', '<?php echo $category['ID']; ?>'));
                <?php endforeach; ?>
            }
            
            $(categorySelect).prop('disabled', false).trigger('change');
        });

        // Category Change Event
        $(categorySelect).on('change', function() {
            const selectedCategory = this.value;
            const selectedType = categoryTypeSelect.value;
            
            $(productSelect).prop('disabled', !selectedCategory).trigger('change');
            $(productSelect).empty().append('<option value="">Pilih Produk</option>');

            if (selectedCategory) {
                // Use get-products.php instead
                fetch(`get-products.php?category_id=${selectedCategory}&category_type=${selectedType}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.error || 'Failed to fetch products');
                    }
                    
                    data.products.forEach(product => {
                        const option = new Option(
                            `${product.NAMA} - Rp${parseInt(product.HARGA).toLocaleString('id-ID')} (Stock: ${product.JUMLAH})`,
                            product.ID
                        );
                        option.dataset.productInfo = JSON.stringify(product);
                        $(productSelect).append(option);
                    });
                    $(productSelect).prop('disabled', false).trigger('change');
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat mengambil data produk: ' + error.message);
                });
            }
        });

        // Product Change Event
        $(productSelect).on('change', function() {
            const selectedProduct = this.value;
            if (selectedProduct) {
                const selectedOption = this.options[this.selectedIndex];
                const productInfo = JSON.parse(selectedOption.dataset.productInfo);
                
                addProduct(productInfo.ID, productInfo.NAMA, productInfo.HARGA);
                
                $(this).val('').trigger('change');
            }
        });
    }
});

function addProduct(productId, productName, productPrice) {
    console.log('Adding product:', { productId, productName, productPrice });
    
    // Check if product already exists
    if ($(`#selected_products tr[data-product-id="${productId}"]`).length) {
        alert('Produk sudah ditambahkan');
        return;
    }

    const $tbody = $('#selected_products');
    const rowCount = $tbody.find('tr').length;
    
    const row = `
        <tr data-product-id="${productId}">
            <td>${productName}</td>
            <td class="text-right product-price">Rp ${formatNumber(productPrice)}</td>
            <td class="text-center">
                <input type="number" 
                       name="quantity[${productId}]" 
                       class="input input-bordered w-20 text-center quantity-input" 
                       value="1" 
                       min="1"
                       onchange="hitungTotal()">
                <input type="hidden" name="produk[]" value="${productId}">
                <input type="hidden" name="harga[${productId}]" value="${productPrice}">
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
    hitungTotal();
}

function renumberRows() {
    $('#selected_products tr').each(function(index) {
        $(this).find('td:first').text(index + 1);
    });
}

// Prevent form from submitting normally
document.getElementById('transactionForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Validate if products are selected
    const products = document.querySelectorAll('#selected_products tr');
    if (products.length === 0) {
        alert('Silakan pilih minimal satu produk');
        return;
    }
    
    // Get form data
    const formData = new FormData(this);
    
    // Add total amount to form data
    const totalAmount = document.getElementById('total_amount').textContent.replace(/\./g, '');
    formData.append('total_amount', totalAmount);
    
    // If no customer selected, set to null
    if (!formData.get('customer_id')) {
        formData.set('customer_id', null);
    }
    
    // Submit form using fetch with proper headers
    fetch(this.action, {
        method: 'POST',
        body: formData,
        headers: {
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Invalid JSON:', text);
                throw new Error('Invalid server response');
            }
        });
    })
    .then(data => {
        if (data.success) {
            alert('Transaksi berhasil disimpan');
            location.reload();
        } else {
            throw new Error(data.message || 'Terjadi kesalahan saat menyimpan transaksi');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert(error.message || 'Terjadi kesalahan saat menyimpan transaksi');
    });
});
</script>

<!-- Add custom styles for Select2 in drawer -->
<style>
.select2-container {
    z-index: 9999;
}
.select2-dropdown {
    z-index: 10000;
}
</style> 