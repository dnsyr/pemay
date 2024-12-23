<?php
require_once '../../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: ../../auth/login.php");
    exit();
}

// Initialize database connection
$db = new Database();
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
                <!-- Customer Selection -->
                <div class="form-control">
                    <label for="customer_id" class="label font-semibold">Customer</label>
                    <select name="customer_id" id="customer_id" class="select select-bordered w-full" required>
                        <option value="">Pilih Customer</option>
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
                        <!-- Kategori Produk Selection -->
                        <div class="form-control">
                            <select name="kategori_produk" id="kategori_produk" class="select select-bordered w-full">
                                <option value="">Pilih Kategori Produk</option>
                                <?php
                                $db->query("SELECT ID, NAMA FROM KategoriProduk WHERE onDelete = 0 ORDER BY NAMA");
                                $kategoriProdukList = $db->resultSet();
                                foreach ($kategoriProdukList as $kp): ?>
                                    <option value="<?php echo htmlentities($kp['ID']); ?>">
                                        <?php echo htmlentities($kp['NAMA']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Kategori Obat Selection -->
                        <div class="form-control">
                            <select name="kategori_obat" id="kategori_obat" class="select select-bordered w-full">
                                <option value="">Pilih Kategori Obat</option>
                        <?php
                                $db->query("SELECT ID, NAMA FROM KategoriObat WHERE onDelete = 0 ORDER BY NAMA");
                                $kategoriObatList = $db->resultSet();
                                foreach ($kategoriObatList as $ko): ?>
                                    <option value="<?php echo htmlentities($ko['ID']); ?>">
                                        <?php echo htmlentities($ko['NAMA']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Product Search -->
                    <div class="mb-4">
                        <select name="search_product" id="search_product" class="select select-bordered w-full">
                            <option value="">Cari Produk</option>
                            </select>
                        </div>

                    <!-- Selected Products -->
                    <div class="overflow-x-auto">
                        <label class="label font-semibold">Produk Terpilih</label>
                        <table class="table w-full" id="productTable">
                            <thead>
                                <tr class="bg-[#D4F0EA]">
                                    <th class="text-left">Nama Produk</th>
                                    <th class="text-right">Harga</th>
                                    <th class="text-center">Stok</th>
                                    <th class="text-center">Kuantitas</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="selectedProductsBody">
                                <!-- Selected products will be shown here -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Total -->
                <div class="form-control">
                    <label class="label font-semibold">Total Biaya</label>
                    <div class="text-xl font-bold">Rp <span id="total_biaya">0</span></div>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-4">
                    <button type="submit" name="submit" class="btn bg-[#D4F0EA] hover:bg-[#B2E0D6] text-[#363636] border-[#363636]">
                        Simpan Transaksi
                    </button>
                    <label for="add_drawer" class="btn btn-outline">Batal</label>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Include Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<!-- Include Select2 JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
.select2-container {
    width: 100% !important;
    z-index: 10000;
}
.select2-dropdown {
    z-index: 10001;
}
.drawer-side {
    z-index: 9999;
}
.select2-container .select2-selection--single {
    height: 40px !important;
    padding: 6px 8px;
    border-radius: 0.5rem;
    border-color: hsl(var(--bc) / 0.2);
    background-color: #fff;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 38px !important;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 28px !important;
    padding-left: 0;
}
.select2-search__field {
    padding: 8px !important;
    border-radius: 0.375rem !important;
}
.select2-container--default .select2-search--dropdown .select2-search__field {
    border: 1px solid hsl(var(--bc) / 0.2);
}
.select2-results__option {
    padding: 8px !important;
}
.select2-container--default .select2-results__option--highlighted[aria-selected] {
    background-color: #D4F0EA;
    color: #363636;
}
</style>

<script>
let selectedProducts = new Map(); // To store selected products

function updateTotal() {
    let total = 0;
    selectedProducts.forEach(product => {
        total += product.HARGA * product.quantity;
    });
    
    const totalElement = document.getElementById('total_biaya');
    if (totalElement) {
        totalElement.textContent = total.toLocaleString('id-ID');
    }
}

function addProduct(product) {
    selectedProducts.set(product.ID, {
        ...product,
        quantity: 1
    });
    updateSelectedProductsTable();
}

function removeProduct(productId) {
    selectedProducts.delete(productId);
    updateSelectedProductsTable();
}

function updateQuantity(productId, newQuantity) {
    const product = selectedProducts.get(productId);
    if (product) {
        product.quantity = parseInt(newQuantity);
        selectedProducts.set(productId, product);
        updateTotal();
    }
}

function updateSelectedProductsTable() {
    let html = '';
    selectedProducts.forEach((product, id) => {
        html += `
            <tr>
                <td>${product.NAMA}</td>
                <td class="text-right">Rp ${parseInt(product.HARGA).toLocaleString('id-ID')}</td>
                <td class="text-center">${product.JUMLAH}</td>
                <td class="text-center">
                    <input type="number" name="quantity[${id}]" 
                           class="input input-bordered w-20 quantity-input" 
                           min="1" value="${product.quantity}" 
                           max="${product.JUMLAH}"
                           data-harga="${product.HARGA}"
                           onchange="updateQuantity('${id}', this.value)">
                    <input type="hidden" name="produk[]" value="${id}">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-error" onclick="removeProduct('${id}')">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    
    if (html === '') {
        html = '<tr><td colspan="5" class="text-center py-4">Belum ada produk yang dipilih.</td></tr>';
    }
    
    document.getElementById('selectedProductsBody').innerHTML = html;
    updateTotal();
}

function initSelect2() {
    // Initialize basic select2
    $('#customer_id').select2({
        dropdownParent: $('.drawer-side'),
        placeholder: "Pilih Customer",
        allowClear: true,
        width: '100%'
    });

    $('#kategori_produk').select2({
        dropdownParent: $('.drawer-side'),
        placeholder: "Pilih Kategori Produk",
        allowClear: true,
        width: '100%'
    });

    $('#kategori_obat').select2({
        dropdownParent: $('.drawer-side'),
        placeholder: "Pilih Kategori Obat",
        allowClear: true,
        width: '100%'
    });

    // Initialize product search select2
    $('#search_product').select2({
        dropdownParent: $('.drawer-side'),
        placeholder: "Cari Produk",
        allowClear: true,
        width: '100%',
        ajax: {
            url: 'get-products.php',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    search: params.term || '',
                    kategori_produk: $('#kategori_produk').val() || '',
                    kategori_obat: $('#kategori_obat').val() || ''
                };
            },
            processResults: function(data) {
                if (!data.success) {
                    return { results: [] };
                }
                return {
                    results: data.products.map(function(product) {
                        return {
                            id: product.ID,
                            text: product.NAMA + ' (Stok: ' + product.JUMLAH + ') - Rp ' + parseInt(product.HARGA).toLocaleString('id-ID'),
                            product: product
                        };
                    }).filter(function(item) {
                        return !selectedProducts.has(item.id);
                    })
                };
            },
            cache: false
        },
        minimumInputLength: 0,
        language: {
            noResults: function() {
                return "Tidak ada produk yang ditemukan";
            },
            searching: function() {
                return "Mencari...";
            },
            errorLoading: function() {
                return "Error memuat data";
            }
        }
    }).on('select2:select', function(e) {
        if (e.params.data && e.params.data.product) {
            addProduct(e.params.data.product);
            $(this).val(null).trigger('change');
        }
    });

    // Reset other category when one is selected
    $('#kategori_produk').on('change', function() {
        if ($(this).val()) {
            $('#kategori_obat').val(null).trigger('change');
        }
        $('#search_product').val(null).trigger('change');
    });

    $('#kategori_obat').on('change', function() {
        if ($(this).val()) {
            $('#kategori_produk').val(null).trigger('change');
        }
        $('#search_product').val(null).trigger('change');
    });
}

// Initialize when drawer opens
$('#add_drawer').on('change', function() {
    if (this.checked) {
        setTimeout(function() {
            try {
                initSelect2();
                console.log('Select2 initialized successfully');
            } catch (error) {
                console.error('Error initializing Select2:', error);
            }
        }, 100);
    }
});

// Also initialize on document ready
$(document).ready(function() {
    updateTotal();
    if ($('#add_drawer').prop('checked')) {
        initSelect2();
    }

    // Handle form submission
    $('#transactionForm').on('submit', function(e) {
        e.preventDefault();

        if (selectedProducts.size === 0) {
            alert('Pilih setidaknya satu produk!');
            return false;
        }

        // Submit form using AJAX
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                try {
                    const data = JSON.parse(response);
                    if (data.success) {
                        // Close drawer
                        document.getElementById('add_drawer').checked = false;
                        // Refresh page to show new data
                        window.location.reload();
                    } else {
                        alert(data.message || 'Terjadi kesalahan saat menyimpan transaksi');
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                    alert('Terjadi kesalahan saat menyimpan transaksi');
                }
            },
            error: function() {
                alert('Terjadi kesalahan saat menyimpan transaksi');
            }
        });
    });
});
</script> 