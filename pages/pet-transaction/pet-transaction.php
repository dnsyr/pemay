<?php
session_start();
require_once '../../config/database.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: ../../auth/login.php");
    exit();
}

$pageTitle = 'Pet Transactions Management';
include '../../layout/header-tailwind.php';

// Inisialisasi database
$db = new Database();

// Get active tab
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'product';

// Filter parameters
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$itemsPerPage = 6;
$offset = ($page - 1) * $itemsPerPage;

// Base query for product transactions
$baseQueryProduct = "SELECT 
    PJ.ID, 
    TO_CHAR(PJ.TANGGALTRANSAKSI, 'DD Mon YYYY, HH24:MI') as TANGGALTRANSAKSI,
    PJ.TOTALBIAYA as TOTALHARGA,
    PH.NAMA as PEMILIK_NAMA,
    LISTAGG(PR.NAMA, ', ') WITHIN GROUP (ORDER BY PR.NAMA) as PRODUK_INFO
FROM PENJUALAN PJ
LEFT JOIN PemilikHewan PH ON PJ.PEMILIKHEWAN_ID = PH.ID
LEFT JOIN TABLE(PJ.PRODUK) TP ON 1=1
LEFT JOIN Produk PR ON TP.COLUMN_VALUE = PR.ID
WHERE PJ.onDelete = 0";

// Base query for medical transactions
$baseQueryMedical = "SELECT 
    PM.ID,
    TO_CHAR(PM.TANGGAL, 'DD Mon YYYY, HH24:MI') as TANGGALTRANSAKSI,
    PM.TOTALBIAYA as TOTALHARGA,
    PH.NAMA as PEMILIK_NAMA,
    H.NAMA as HEWAN_NAMA,
    LISTAGG(L.NAMA, ', ') WITHIN GROUP (ORDER BY L.NAMA) as LAYANAN_INFO
FROM LAYANANMEDIS PM
LEFT JOIN Hewan H ON PM.HEWAN_ID = H.ID
LEFT JOIN PemilikHewan PH ON H.PEMILIKHEWAN_ID = PH.ID
LEFT JOIN TABLE(PM.JENISLAYANAN) TL ON 1=1
LEFT JOIN JenisLayananMedis L ON TL.COLUMN_VALUE = L.ID
WHERE PM.STATUS IN ('Complete', 'Finished') AND PM.onDelete = 0";

// Add date filters if provided
$params = [];
if ($startDate) {
    $baseQueryProduct .= " AND TRUNC(PJ.TANGGALTRANSAKSI) >= TO_DATE(:start_date, 'YYYY-MM-DD')";
    $baseQueryMedical .= " AND TRUNC(PM.TANGGAL) >= TO_DATE(:start_date, 'YYYY-MM-DD')";
    $params[':start_date'] = $startDate;
}
if ($endDate) {
    $baseQueryProduct .= " AND TRUNC(PJ.TANGGALTRANSAKSI) <= TO_DATE(:end_date, 'YYYY-MM-DD')";
    $baseQueryMedical .= " AND TRUNC(PM.TANGGAL) <= TO_DATE(:end_date, 'YYYY-MM-DD')";
    $params[':end_date'] = $endDate;
}

// Add group by clause
$baseQueryProduct .= " GROUP BY PJ.ID, PJ.TANGGALTRANSAKSI, PJ.TOTALBIAYA, PH.NAMA";
$baseQueryMedical .= " GROUP BY PM.ID, PM.TANGGAL, PM.TOTALBIAYA, PH.NAMA, H.NAMA";

// Get total items based on active tab
if ($activeTab === 'product') {
    $countQuery = "SELECT COUNT(*) as TOTAL FROM (
        SELECT PJ.ID
        FROM PENJUALAN PJ
        LEFT JOIN PemilikHewan PH ON PJ.PEMILIKHEWAN_ID = PH.ID
        LEFT JOIN TABLE(PJ.PRODUK) TP ON 1=1
        LEFT JOIN Produk PR ON TP.COLUMN_VALUE = PR.ID
        WHERE PJ.onDelete = 0
        " . ($startDate ? " AND TRUNC(PJ.TANGGALTRANSAKSI) >= TO_DATE(:start_date, 'YYYY-MM-DD')" : "") . "
        " . ($endDate ? " AND TRUNC(PJ.TANGGALTRANSAKSI) <= TO_DATE(:end_date, 'YYYY-MM-DD')" : "") . "
        GROUP BY PJ.ID, PJ.TANGGALTRANSAKSI, PJ.TOTALBIAYA, PH.NAMA
    )";
    $baseQuery = $baseQueryProduct;
} else if ($activeTab === 'medical') {
    $countQuery = "SELECT COUNT(*) as TOTAL FROM (
        SELECT PM.ID
        FROM LAYANANMEDIS PM
        LEFT JOIN Hewan H ON PM.HEWAN_ID = H.ID
        LEFT JOIN PemilikHewan PH ON H.PEMILIKHEWAN_ID = PH.ID
        LEFT JOIN TABLE(PM.JENISLAYANAN) TL ON 1=1
        LEFT JOIN JenisLayananMedis L ON TL.COLUMN_VALUE = L.ID
        WHERE PM.STATUS = 'Complete' AND PM.onDelete = 0
        " . ($startDate ? " AND TRUNC(PM.TANGGAL) >= TO_DATE(:start_date, 'YYYY-MM-DD')" : "") . "
        " . ($endDate ? " AND TRUNC(PM.TANGGAL) <= TO_DATE(:end_date, 'YYYY-MM-DD')" : "") . "
        GROUP BY PM.ID, PM.TANGGAL, PM.TOTALBIAYA, PH.NAMA, H.NAMA
    )";
    $baseQuery = $baseQueryMedical;
}

// Count total rows for pagination
$db->query($countQuery);
foreach ($params as $key => $value) {
    $db->bind($key, $value);
}
$totalItems = $db->single()['TOTAL'];
$totalPages = ceil($totalItems / $itemsPerPage);

// Add order by and pagination
$query = $baseQuery . " ORDER BY " . ($activeTab === 'product' ? 'PJ.TANGGALTRANSAKSI' : 'PM.TANGGAL') . " DESC OFFSET :offset ROWS FETCH NEXT :limit ROWS ONLY";
$db->query($query);
foreach ($params as $key => $value) {
    $db->bind($key, $value);
}
$db->bind(':offset', $offset);
$db->bind(':limit', $itemsPerPage);

$transactions = $db->resultSet();
?>

<div class="pb-6 px-12">
    <div class="flex justify-between mb-6">
        <h2 class="text-3xl font-bold text-[#363636]">Pet Transaction Management</h2>
    </div>

    <!-- Main Content -->
    <div class="flex flex-col">
        <!-- Tabs -->
        <div class="flex w-fit">
            <a href="?tab=product" class="px-8 py-2 text-center <?= $activeTab === 'product' ? 'bg-[#D4F0EA] border-x border-t border-[#363636]' : 'bg-white border-x border-t border-[#363636]' ?> rounded-t-lg -ml-[1px] first:ml-0">Product Sales</a>
            <a href="?tab=medical" class="px-8 py-2 text-center <?= $activeTab === 'medical' ? 'bg-[#D4F0EA] border-x border-t border-[#363636]' : 'bg-white border-x border-t border-[#363636]' ?> rounded-t-lg -ml-[1px]">Medical Services</a>
            <a href="?tab=salon" class="px-8 py-2 text-center <?= $activeTab === 'salon' ? 'bg-[#D4F0EA] border-x border-t border-[#363636]' : 'bg-white border-x border-t border-[#363636]' ?> rounded-t-lg -ml-[1px]">Salon Services</a>
            <a href="?tab=hotel" class="px-8 py-2 text-center <?= $activeTab === 'hotel' ? 'bg-[#D4F0EA] border-x border-t border-[#363636]' : 'bg-white border-x border-t border-[#363636]' ?> rounded-t-lg -ml-[1px]">Hotel Services</a>
        </div>

        <!-- Content Area -->
        <div class="border border-[#363636] rounded-b-2xl p-6 -mt-[1px]">
            <div class="flex justify-between items-center mb-4">
                <p class="text-lg text-[#363636] font-semibold">Product Sales Transactions</p>
                
                <!-- Filter Form -->
                <form class="flex gap-4 items-end" id="filterForm">
                    <input type="hidden" name="tab" value="<?= $activeTab ?>">
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Start Date</span>
                        </label>
                        <input type="date" name="start_date" id="start_date" value="<?= $startDate ?>" class="input input-bordered">
                    </div>
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">End Date</span>
                        </label>
                        <input type="date" name="end_date" id="end_date" value="<?= $endDate ?>" class="input input-bordered">
                    </div>
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <?php if ($startDate || $endDate): ?>
                        <a href="?tab=<?= $activeTab ?>" class="btn btn-ghost">Reset</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Table Container -->
            <div class="border border-[#363636] rounded-xl overflow-x-auto">
                <table class="table w-full">
                    <thead>
                        <tr class="bg-[#D4F0EA] text-[#363636]">
                            <th class="py-4 px-6 text-center border-b border-[#363636]">No.</th>
                            <th class="py-4 px-6 text-left border-b border-[#363636]">Transaction Date</th>
                            <th class="py-4 px-6 text-left border-b border-[#363636]">Customer Name</th>
                            <?php if ($activeTab === 'medical'): ?>
                                <th class="py-4 px-6 text-left border-b border-[#363636]">Pet Name</th>
                                <th class="py-4 px-6 text-left border-b border-[#363636]">Services</th>
                            <?php else: ?>
                                <th class="py-4 px-6 text-left border-b border-[#363636]">Products</th>
                            <?php endif; ?>
                            <th class="py-4 px-6 text-right border-b border-[#363636]">Total Cost</th>
                            <th class="py-4 px-6 text-center border-b border-[#363636]">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="<?= $activeTab === 'medical' ? '7' : '6' ?>" class="text-center py-4">No transactions found.</td>
                            </tr>
                        <?php else: ?>
                            <?php 
                            $nomor = ($page - 1) * $itemsPerPage + 1;
                            foreach ($transactions as $transaction): 
                            ?>
                                <tr class="text-[#363636] hover:bg-gray-50 border-b border-[#363636] last:border-b-0">
                                    <td class="py-4 px-6 text-center"><?= $nomor++ ?></td>
                                    <td class="py-4 px-6"><?= $transaction['TANGGALTRANSAKSI'] ?></td>
                                    <td class="py-4 px-6"><?= htmlentities($transaction['PEMILIK_NAMA']) ?></td>
                                    <?php if ($activeTab === 'medical'): ?>
                                        <td class="py-4 px-6"><?= htmlentities($transaction['HEWAN_NAMA']) ?></td>
                                        <td class="py-4 px-6"><?= htmlentities($transaction['LAYANAN_INFO']) ?></td>
                                    <?php else: ?>
                                        <td class="py-4 px-6"><?= htmlentities($transaction['PRODUK_INFO']) ?></td>
                                    <?php endif; ?>
                                    <td class="py-4 px-6 text-right">Rp <?= number_format($transaction['TOTALHARGA'], 0, ',', '.') ?></td>
                                    <td class="py-4 px-6">
                                        <div class="flex gap-3 justify-center items-center">
                                            <?php if ($activeTab === 'product'): ?>
                                                <button type="button" class="btn btn-ghost btn-sm" disabled>
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-error btn-sm" onclick="deleteRecord('<?= $transaction['ID'] ?>')">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="join flex justify-center mt-4">
                <a class="join-item btn <?= ($page <= 1) ? 'btn-disabled' : '' ?>"
                   href="?tab=<?= $activeTab ?>&page=<?= ($page - 1) ?>&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>">«</a>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a class="join-item btn <?= ($page === $i) ? 'bg-[#D4F0EA]' : '' ?>"
                       href="?tab=<?= $activeTab ?>&page=<?= $i ?>&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>"><?= $i ?></a>
                <?php endfor; ?>
                
                <a class="join-item btn <?= ($page >= $totalPages) ? 'btn-disabled' : '' ?>"
                   href="?tab=<?= $activeTab ?>&page=<?= ($page + 1) ?>&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>">»</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($activeTab === 'product'): ?>
    <!-- Floating Add Button -->
    <div class="fixed bottom-4 right-4">
        <label for="add_drawer" class="btn btn-circle btn-lg bg-[#B2E0D6] hover:bg-[#9AC7BE] text-[#363636] border-none">
            <i class="fas fa-plus text-xl"></i>
        </label>
    </div>

    <!-- Add Transaction Drawer -->
    <?php include 'drawer-product.php'; ?>
<?php endif; ?>

<!-- Delete Confirmation Modal -->
<dialog id="delete_modal" class="modal">
    <div class="modal-box">
        <h3 class="font-bold text-lg mb-4">Konfirmasi Hapus</h3>
        <p>Apakah Anda yakin ingin menghapus transaksi ini? Stok produk akan dikembalikan.</p>
        <div class="modal-action">
            <form method="dialog">
                <button class="btn btn-sm mr-2">Batal</button>
                <button type="button" onclick="confirmDelete()" class="btn btn-sm btn-error">Hapus</button>
            </form>
        </div>
    </div>
</dialog>

<!-- Update Transaction Drawer -->
<div class="drawer drawer-end">
    <input id="update-product-drawer" type="checkbox" class="drawer-toggle" /> 
    <div class="drawer-side z-50">
        <label for="update-product-drawer" class="drawer-overlay"></label>
        <div class="p-4 w-[600px] min-h-full bg-base-200 text-base-content">
            <div id="update-form-content">
                <!-- Form update akan dimuat di sini -->
            </div>
        </div>
    </div>
</div>

<script>
let transactionToDelete = null;

function deleteRecord(id) {
    transactionToDelete = id;
    document.getElementById('delete_modal').showModal();
}

function confirmDelete() {
    if (!transactionToDelete) return;

    fetch('delete-transaction.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'id=' + encodeURIComponent(transactionToDelete)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Terjadi kesalahan saat menghapus transaksi');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat menghapus transaksi');
    })
    .finally(() => {
        document.getElementById('delete_modal').close();
        transactionToDelete = null;
    });
}

// Validasi tanggal
document.getElementById('start_date').addEventListener('change', function() {
    const startDate = this.value;
    const endDateInput = document.getElementById('end_date');
    
    // Set minimum end date sama dengan start date
    endDateInput.min = startDate;
    
    // Jika end date sudah dipilih dan lebih kecil dari start date, reset end date
    if (endDateInput.value && endDateInput.value < startDate) {
        endDateInput.value = startDate;
    }
});

document.getElementById('filterForm').addEventListener('submit', function(e) {
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    
    if (startDate && endDate && endDate < startDate) {
        e.preventDefault();
        alert('End date tidak boleh lebih kecil dari start date');
        return false;
    }
});

function loadTransaction(id) {
    // Add transaction_id to URL without redirecting
    const url = new URL(window.location.href);
    url.searchParams.set('transaction_id', id);
    window.history.pushState({}, '', url);
    
    // Show the drawer
    document.getElementById('update-product-drawer').checked = true;
    
    // Load the form content with cache-busting parameter
    fetch(`update-product-form.php?id=${id}&t=${Date.now()}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(html => {
            document.getElementById('update-form-content').innerHTML = html;
            // Initialize form after loading
            if (typeof initializeForm === 'function') {
                initializeForm();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('update-form-content').innerHTML = 
                `<div class="alert alert-error">Error loading form: ${error.message}</div>`;
        });
}

// Global helper functions for transaction management
function formatNumber(number) {
    return new Intl.NumberFormat('id-ID').format(number);
}

function updateSubtotal($input) {
    const $row = $input.closest('tr');
    const price = parseFloat($row.find('td:eq(2)').text().replace(/[^\d]/g, ''));
    const quantity = parseInt($input.val());
    const subtotal = price * quantity;
    $row.find('td:eq(4)').text(`Rp ${formatNumber(subtotal)}`);
    updateTotal();
}

function updateTotal() {
    const subtotals = $('#selected_products tr, #update_selected_products tr').map(function() {
        return parseFloat($(this).find('td:eq(4)').text().replace(/[^\d]/g, '')) || 0;
    }).get();
    const total = subtotals.reduce((sum, subtotal) => sum + subtotal, 0);
    $('#total_amount, #update_total_amount').text(formatNumber(total));
}

function renumberRows() {
    $('#selected_products tr, #update_selected_products tr').each(function(index) {
        $(this).find('td:first').text(index + 1);
    });
}

function removeProduct($button) {
    $button.closest('tr').remove();
    updateTotal();
    renumberRows();
}
</script>
