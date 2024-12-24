<?php
session_start();
require_once '../../config/database.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: ../../auth/login.php");
    exit();
}

// Check user role and set permissions
$userRole = $_SESSION['posisi'] ?? '';
$canEdit = ($userRole === 'staff' || $userRole === 'owner');
$canPrint = ($userRole === 'owner');
$canView = ($userRole === 'vet');

if (!$canEdit && !$canView) {
    header("Location: ../../index.php");
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

// Initialize parameters array for binding
$params = [];
if ($startDate) {
    $params[':start_date'] = $startDate;
}
if ($endDate) {
    $params[':end_date'] = $endDate;
}

// Base query for product transactions
$baseQueryProduct = "WITH ProductCounts AS (
    SELECT 
        PJ.ID as PENJUALAN_ID,
        PJ.TANGGALTRANSAKSI,
        PJ.TOTALBIAYA,
        PH.NAMA as PEMILIK_NAMA,
        PR.NAMA as NAMA_PRODUK,
        COUNT(*) as JUMLAH
    FROM PENJUALAN PJ
    LEFT JOIN PEMILIKHEWAN PH ON PJ.PEMILIKHEWAN_ID = PH.ID
    LEFT JOIN TABLE(PJ.PRODUK) TP ON 1=1
    LEFT JOIN PRODUK PR ON TP.COLUMN_VALUE = PR.ID
    WHERE PJ.onDelete = 0
    AND PJ.PRODUK IS NOT NULL
    AND PJ.LAYANANMEDIS_ID IS NULL
    AND PJ.LAYANANHOTEL_ID IS NULL
    AND PJ.LAYANANSALON_ID IS NULL
    " . ($startDate ? " AND TRUNC(PJ.TANGGALTRANSAKSI) >= TO_DATE(:start_date, 'YYYY-MM-DD')" : "") . "
    " . ($endDate ? " AND TRUNC(PJ.TANGGALTRANSAKSI) <= TO_DATE(:end_date, 'YYYY-MM-DD')" : "") . "
    GROUP BY PJ.ID, PJ.TANGGALTRANSAKSI, PJ.TOTALBIAYA, PH.NAMA, PR.NAMA
    HAVING COUNT(PR.ID) > 0
)
SELECT 
    PC.PENJUALAN_ID as ID,
    TO_CHAR(PC.TANGGALTRANSAKSI, 'DD Mon YYYY, HH24:MI') as TANGGALTRANSAKSI,
    PC.TOTALBIAYA as TOTALHARGA,
    PC.PEMILIK_NAMA,
    LISTAGG(PC.NAMA_PRODUK || ' (x' || PC.JUMLAH || ')', ', ') 
    WITHIN GROUP (ORDER BY PC.NAMA_PRODUK) as PRODUK_INFO,
    SUM(PC.JUMLAH) as TOTAL_QUANTITY
FROM ProductCounts PC
GROUP BY 
    PC.PENJUALAN_ID,
    PC.TANGGALTRANSAKSI,
    PC.TOTALBIAYA,
    PC.PEMILIK_NAMA";

// Base query for medical transactions
$baseQueryMedical = "WITH ServiceInfo AS (
    SELECT 
        PM.ID,
        PM.TANGGAL,
        PM.TOTALBIAYA,
        PH.NAMA as PEMILIK_NAMA,
        H.NAMA as HEWAN_NAMA,
        LISTAGG(L.NAMA, ', ') 
            WITHIN GROUP (ORDER BY L.NAMA) as LAYANAN_INFO
    FROM LAYANANMEDIS PM
    LEFT JOIN HEWAN H ON PM.HEWAN_ID = H.ID
    LEFT JOIN PEMILIKHEWAN PH ON H.PEMILIKHEWAN_ID = PH.ID
    CROSS JOIN TABLE(PM.JENISLAYANAN) TL
    LEFT JOIN JENISLAYANANMEDIS L ON TL.COLUMN_VALUE = L.ID
    WHERE PM.STATUS IN ('Complete', 'Finished') AND PM.onDelete = 0
    " . ($startDate ? " AND TRUNC(PM.TANGGAL) >= TO_DATE(:start_date, 'YYYY-MM-DD')" : "") . "
    " . ($endDate ? " AND TRUNC(PM.TANGGAL) <= TO_DATE(:end_date, 'YYYY-MM-DD')" : "") . "
    GROUP BY PM.ID, PM.TANGGAL, PM.TOTALBIAYA, PH.NAMA, H.NAMA
)
SELECT 
    ID,
    TO_CHAR(TANGGAL, 'DD Mon YYYY, HH24:MI') as TANGGALTRANSAKSI,
    TOTALBIAYA as TOTALHARGA,
    PEMILIK_NAMA,
    HEWAN_NAMA,
    LAYANAN_INFO
FROM ServiceInfo";

// Base query for salon transactions
$baseQuerySalon = "WITH ServiceInfo AS (
    SELECT 
        LS.ID,
        LS.TANGGAL,
        LS.TOTALBIAYA,
        PH.NAMA as PEMILIK_NAMA,
        H.NAMA as HEWAN_NAMA,
        LISTAGG(L.NAMA, ', ') 
            WITHIN GROUP (ORDER BY L.NAMA) as LAYANAN_INFO
    FROM LAYANANSALON LS
    LEFT JOIN HEWAN H ON LS.HEWAN_ID = H.ID
    LEFT JOIN PEMILIKHEWAN PH ON H.PEMILIKHEWAN_ID = PH.ID
    CROSS JOIN TABLE(LS.JENISLAYANAN) TL  -- Changed from LEFT JOIN to CROSS JOIN
    LEFT JOIN JENISLAYANANSALON L ON TL.COLUMN_VALUE = L.ID
    WHERE LS.STATUS = 'Complete' AND LS.onDelete = 0
    " . ($startDate ? " AND TRUNC(LS.TANGGAL) >= TO_DATE(:start_date, 'YYYY-MM-DD')" : "") . "
    " . ($endDate ? " AND TRUNC(LS.TANGGAL) <= TO_DATE(:end_date, 'YYYY-MM-DD')" : "") . "
    GROUP BY LS.ID, LS.TANGGAL, LS.TOTALBIAYA, PH.NAMA, H.NAMA
)
SELECT 
    ID,
    TO_CHAR(TANGGAL, 'DD Mon YYYY, HH24:MI') as TANGGALTRANSAKSI,
    TOTALBIAYA as TOTALHARGA,
    PEMILIK_NAMA,
    HEWAN_NAMA,
    LAYANAN_INFO
FROM ServiceInfo";

// Base query for hotel transactions
$baseQueryHotel = "WITH HotelInfo AS (
    SELECT 
        LH.ID,
        LH.CHECKIN,
        LH.CHECKOUT,
        LH.TOTALBIAYA,
        PH.NAMA as PEMILIK_NAMA,
        H.NAMA as HEWAN_NAMA,
        K.NOMOR as KANDANG_NOMOR,
        K.UKURAN as KANDANG_UKURAN
    FROM LAYANANHOTEL LH
    LEFT JOIN HEWAN H ON LH.HEWAN_ID = H.ID
    LEFT JOIN PEMILIKHEWAN PH ON H.PEMILIKHEWAN_ID = PH.ID
    LEFT JOIN KANDANG K ON LH.KANDANG_ID = K.ID
    WHERE LH.STATUS = 'Complete' AND LH.onDelete = 0
    " . ($startDate ? " AND TRUNC(LH.CHECKIN) >= TO_DATE(:start_date, 'YYYY-MM-DD')" : "") . "
    " . ($endDate ? " AND TRUNC(LH.CHECKIN) <= TO_DATE(:end_date, 'YYYY-MM-DD')" : "") . "
)
SELECT 
    ID,
    TO_CHAR(CHECKIN, 'DD Mon YYYY, HH24:MI') as TANGGALTRANSAKSI,
    TOTALBIAYA as TOTALHARGA,
    PEMILIK_NAMA,
    HEWAN_NAMA,
    KANDANG_NOMOR,
    KANDANG_UKURAN,
    TO_CHAR(CHECKOUT, 'DD Mon YYYY, HH24:MI') as CHECKOUT
FROM HotelInfo";

// Get total items based on active tab
if ($activeTab === 'product') {
    $countQuery = "SELECT COUNT(DISTINCT PC.PENJUALAN_ID) as TOTAL
    FROM (
        SELECT 
            PJ.ID as PENJUALAN_ID,
            PJ.TANGGALTRANSAKSI
        FROM PENJUALAN PJ
        WHERE PJ.onDelete = 0
        AND PJ.PRODUK IS NOT NULL
        AND PJ.LAYANANMEDIS_ID IS NULL
        AND PJ.LAYANANHOTEL_ID IS NULL
        AND PJ.LAYANANSALON_ID IS NULL
        " . ($startDate ? " AND TRUNC(PJ.TANGGALTRANSAKSI) >= TO_DATE(:start_date, 'YYYY-MM-DD')" : "") . "
        " . ($endDate ? " AND TRUNC(PJ.TANGGALTRANSAKSI) <= TO_DATE(:end_date, 'YYYY-MM-DD')" : "") . "
    ) PC";
    $baseQuery = $baseQueryProduct;
} else if ($activeTab === 'medical') {
    $countQuery = "SELECT COUNT(DISTINCT SI.ID) as TOTAL
        FROM (
            SELECT 
                PM.ID,
                PM.TANGGAL
            FROM LAYANANMEDIS PM
            WHERE PM.STATUS IN ('Complete', 'Finished') AND PM.onDelete = 0
            " . ($startDate ? " AND TRUNC(PM.TANGGAL) >= TO_DATE(:start_date, 'YYYY-MM-DD')" : "") . "
            " . ($endDate ? " AND TRUNC(PM.TANGGAL) <= TO_DATE(:end_date, 'YYYY-MM-DD')" : "") . "
        ) SI";
    $baseQuery = $baseQueryMedical;
} else if ($activeTab === 'hotel') {
    $countQuery = "SELECT COUNT(DISTINCT HI.ID) as TOTAL
        FROM (
            SELECT 
                LH.ID,
                LH.CHECKIN
            FROM LAYANANHOTEL LH
            WHERE LH.STATUS = 'Complete' AND LH.onDelete = 0
            " . ($startDate ? " AND TRUNC(LH.CHECKIN) >= TO_DATE(:start_date, 'YYYY-MM-DD')" : "") . "
            " . ($endDate ? " AND TRUNC(LH.CHECKIN) <= TO_DATE(:end_date, 'YYYY-MM-DD')" : "") . "
        ) HI";
    $baseQuery = $baseQueryHotel;
} else if ($activeTab === 'salon') {
    $countQuery = "SELECT COUNT(DISTINCT SI.ID) as TOTAL
        FROM (
            SELECT 
                LS.ID,
                LS.TANGGAL
            FROM LAYANANSALON LS
            WHERE LS.STATUS = 'Complete' AND LS.onDelete = 0
            " . ($startDate ? " AND TRUNC(LS.TANGGAL) >= TO_DATE(:start_date, 'YYYY-MM-DD')" : "") . "
            " . ($endDate ? " AND TRUNC(LS.TANGGAL) <= TO_DATE(:end_date, 'YYYY-MM-DD')" : "") . "
        ) SI";
    $baseQuery = $baseQuerySalon;
}

// Count total rows for pagination
$totalItems = 0;
$totalPages = 1;

if (!empty($countQuery)) {
    $db->query($countQuery);
    if ($startDate) {
        $db->bind(':start_date', $startDate);
    }
    if ($endDate) {
        $db->bind(':end_date', $endDate);
    }
    $result = $db->single();
    $totalItems = $result ? $result['TOTAL'] : 0;
    $totalPages = max(1, ceil($totalItems / $itemsPerPage));
}

// Get total amount for the filtered period
if ($activeTab === 'product') {
    $totalAmountQuery = "
        SELECT SUM(PJ.TOTALBIAYA) as TOTAL_AMOUNT
        FROM PENJUALAN PJ
        WHERE PJ.onDelete = 0
        AND PJ.PRODUK IS NOT NULL
        AND PJ.LAYANANMEDIS_ID IS NULL
        AND PJ.LAYANANHOTEL_ID IS NULL
        AND PJ.LAYANANSALON_ID IS NULL
        " . ($startDate ? " AND TRUNC(PJ.TANGGALTRANSAKSI) >= TO_DATE(:start_date, 'YYYY-MM-DD')" : "") . "
        " . ($endDate ? " AND TRUNC(PJ.TANGGALTRANSAKSI) <= TO_DATE(:end_date, 'YYYY-MM-DD')" : "");
} else if ($activeTab === 'medical') {
    $totalAmountQuery = "
        SELECT SUM(LM.TOTALBIAYA) as TOTAL_AMOUNT
        FROM LAYANANMEDIS LM
        WHERE LM.onDelete = 0
        AND LM.Status = 'Finished'
        " . ($startDate ? " AND TRUNC(LM.TANGGAL) >= TO_DATE(:start_date, 'YYYY-MM-DD')" : "") . "
        " . ($endDate ? " AND TRUNC(LM.TANGGAL) <= TO_DATE(:end_date, 'YYYY-MM-DD')" : "");
}

// Get total amount
$totalAmount = 0;
if (!empty($totalAmountQuery)) {
    $db->query($totalAmountQuery);
    if ($startDate) {
        $db->bind(':start_date', $startDate);
    }
    if ($endDate) {
        $db->bind(':end_date', $endDate);
    }
    $result = $db->single();
    $totalAmount = $result ? $result['TOTAL_AMOUNT'] : 0;
}

// Add pagination to query
if (!empty($baseQuery)) {
    $query = "SELECT * FROM (
        SELECT a.*, ROWNUM rnum FROM (
            " . $baseQuery . "
            ORDER BY TANGGALTRANSAKSI DESC
        ) a WHERE ROWNUM <= " . ($offset + $itemsPerPage) . "
    ) WHERE rnum > " . $offset;

    $db->query($query);
    
    // Bind parameters
    if ($startDate) {
        $db->bind(':start_date', $startDate);
    }
    if ($endDate) {
        $db->bind(':end_date', $endDate);
    }
    
    $transactions = $db->resultSet();
} else {
    $transactions = [];
}

// Fetch categories for filter
$categoriesProduk = [];
$db->query("SELECT * FROM KATEGORIPRODUK WHERE ONDELETE = 0 ORDER BY NAMA");
$categoriesProduk = $db->resultSet();

// Fetch KategoriObat
$categoriesObat = [];
$db->query("SELECT * FROM KATEGORIOBAT WHERE ONDELETE = 0 ORDER BY NAMA");
$categoriesObat = $db->resultSet();
?>

<div class="pb-6 px-12">
    <div class="flex justify-between mb-6">
        <h2 class="text-3xl font-bold text-[#363636]">Pet Transaction Management</h2>
    </div>

    <!-- Tabs -->
    <div class="flex border-b border-[#363636] mb-6">
        <a href="?tab=product" class="tab h-10 min-h-[2.5rem] !outline-none <?php echo $activeTab === 'product' ? 'tab-active !bg-[#D4F0EA] !text-[#363636] !border-[#363636] !border-b-0' : 'text-[#363636] hover:text-[#363636]'; ?> text-base font-normal px-6">Product Sales</a>
        <a href="?tab=medical" class="tab h-10 min-h-[2.5rem] !outline-none <?php echo $activeTab === 'medical' ? 'tab-active !bg-[#D4F0EA] !text-[#363636] !border-[#363636] !border-b-0' : 'text-[#363636] hover:text-[#363636]'; ?> text-base font-normal px-6">Medical Services</a>
        <a href="?tab=salon" class="tab h-10 min-h-[2.5rem] !outline-none <?php echo $activeTab === 'salon' ? 'tab-active !bg-[#D4F0EA] !text-[#363636] !border-[#363636] !border-b-0' : 'text-[#363636] hover:text-[#363636]'; ?> text-base font-normal px-6">Salon Services</a>
        <a href="?tab=hotel" class="tab h-10 min-h-[2.5rem] !outline-none <?php echo $activeTab === 'hotel' ? 'tab-active !bg-[#D4F0EA] !text-[#363636] !border-[#363636] !border-b-0' : 'text-[#363636] hover:text-[#363636]'; ?> text-base font-normal px-6">Hotel Services</a>
    </div>

    <!-- Main Table -->
    <div class="bg-white rounded-lg p-6 shadow-md">
        <div class="flex justify-between items-center mb-4">
            <p class="text-lg text-[#363636] font-semibold">
                <?php
                switch($activeTab) {
                    case 'product':
                        echo 'Product Sales Transactions';
                        break;
                    case 'medical':
                        echo 'Medical Service Transactions';
                        break;
                    case 'salon':
                        echo 'Salon Service Transactions';
                        break;
                    case 'hotel':
                        echo 'Hotel Service Transactions';
                        break;
                }
                ?>
            </p>
            
            <!-- Filter Form -->
            <form class="flex gap-4 items-end" id="filterForm">
                <input type="hidden" name="tab" value="<?= $activeTab ?>">
                
                <!-- Quick Filter -->
                <div class="form-control">
                    <select name="quick_filter" id="quick_filter" class="select select-bordered" onchange="applyQuickFilter(this.value)">
                        <option value="">Custom Range</option>
                        <option value="week">Last 7 Days</option>
                        <option value="month">Last 30 Days</option>
                        <option value="year">Last 365 Days</option>
                    </select>
                </div>
                
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
                <?php if ($canPrint): ?>
                <button type="button" onclick="showPreview()" class="btn bg-green-600 hover:bg-green-700 text-white">
                    <i class="fas fa-print mr-2"></i>Print
                </button>
                <?php endif; ?>
            </form>
        </div>

        <!-- Print Content (Hidden) -->
        <div id="print-content" class="hidden">
            <div class="text-center mb-6">
                <h1 class="text-2xl font-bold">Laporan Transaksi <?= ucfirst($activeTab) ?></h1>
                <p>Periode: <?= $startDate ? date('d/m/Y', strtotime($startDate)) : 'Awal' ?> - <?= $endDate ? date('d/m/Y', strtotime($endDate)) : 'Akhir' ?></p>
            </div>
            
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pemilik</th>
                        <?php if ($activeTab === 'product'): ?>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produk</th>
                            <th class="px-6 py-3 bg-gray-50 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah</th>
                        <?php elseif ($activeTab === 'medical'): ?>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hewan</th>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Layanan</th>
                        <?php elseif ($activeTab === 'hotel'): ?>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hewan</th>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kandang</th>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Check Out</th>
                        <?php elseif ($activeTab === 'salon'): ?>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hewan</th>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Layanan</th>
                        <?php endif; ?>
                        <th class="px-6 py-3 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Biaya</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php $no = 1; foreach ($transactions as $transaction): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= $no++ ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= $transaction['TANGGALTRANSAKSI'] ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= $transaction['PEMILIK_NAMA'] ?></td>
                        <?php if ($activeTab === 'product'): ?>
                            <td class="px-6 py-4 text-sm text-gray-900"><?= $transaction['PRODUK_INFO'] ?></td>
                            <td class="px-6 py-4 text-center text-sm text-gray-900"><?= $transaction['TOTAL_QUANTITY'] ?></td>
                        <?php elseif ($activeTab === 'medical'): ?>
                            <td class="px-6 py-4 text-sm text-gray-900"><?= $transaction['HEWAN_NAMA'] ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?= $transaction['LAYANAN_INFO'] ?></td>
                        <?php elseif ($activeTab === 'hotel'): ?>
                            <td class="px-6 py-4 text-sm text-gray-900"><?= $transaction['HEWAN_NAMA'] ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900">Cage <?= $transaction['KANDANG_NOMOR'] ?> (<?= $transaction['KANDANG_UKURAN'] ?>)</td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?= $transaction['CHECKOUT'] ?></td>
                        <?php elseif ($activeTab === 'salon'): ?>
                            <td class="px-6 py-4 text-sm text-gray-900"><?= $transaction['HEWAN_NAMA'] ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?= $transaction['LAYANAN_INFO'] ?></td>
                        <?php endif; ?>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">Rp <?= number_format($transaction['TOTALHARGA'], 0, ',', '.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="<?= $activeTab === 'product' ? '5' : ($activeTab === 'hotel' ? '6' : '4') ?>" class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 text-right">Total Keseluruhan:</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 text-right">Rp <?= number_format($totalAmount, 0, ',', '.') ?></td>
                    </tr>
                </tfoot>
            </table>
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
                        <?php elseif ($activeTab === 'hotel'): ?>
                            <th class="py-4 px-6 text-left border-b border-[#363636]">Pet Name</th>
                            <th class="py-4 px-6 text-left border-b border-[#363636]">Cage Info</th>
                            <th class="py-4 px-6 text-left border-b border-[#363636]">Check Out</th>
                        <?php elseif ($activeTab === 'salon'): ?>
                            <th class="py-4 px-6 text-left border-b border-[#363636]">Pet Name</th>
                            <th class="py-4 px-6 text-left border-b border-[#363636]">Services</th>
                        <?php else: ?>
                            <th class="py-4 px-6 text-left border-b border-[#363636]">Products</th>
                            <th class="py-4 px-6 text-center border-b border-[#363636]">Total Items</th>
                        <?php endif; ?>
                        <th class="py-4 px-6 text-right border-b border-[#363636]">Total Cost</th>
                        <?php if ($activeTab === 'product' && $canEdit): ?>
                            <th class="py-4 px-6 text-center border-b border-[#363636]">Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody class="bg-white">
                    <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="<?= $activeTab === 'medical' ? '6' : ($activeTab === 'hotel' ? '7' : '7') ?>" class="text-center py-4">No transactions found.</td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $nomor = ($page - 1) * $itemsPerPage + 1;
                        foreach ($transactions as $transaction): 
                        ?>
                            <tr class="text-[#363636] hover:bg-gray-50 border-b border-[#363636] last:border-b-0">
                                <td class="py-4 px-6 text-center"><?= $nomor++ ?></td>
                                <td class="py-4 px-6"><?= $transaction['TANGGALTRANSAKSI'] ?></td>
                                <td class="py-4 px-6"><?= $transaction['PEMILIK_NAMA'] ? htmlentities($transaction['PEMILIK_NAMA']) : 'Non-Member' ?></td>
                                <?php if ($activeTab === 'medical'): ?>
                                    <td class="py-4 px-6"><?= htmlentities($transaction['HEWAN_NAMA']) ?></td>
                                    <td class="py-4 px-6"><?= htmlentities($transaction['LAYANAN_INFO']) ?></td>
                                <?php elseif ($activeTab === 'hotel'): ?>
                                    <td class="py-4 px-6"><?= htmlentities($transaction['HEWAN_NAMA']) ?></td>
                                    <td class="py-4 px-6">Cage <?= $transaction['KANDANG_NOMOR'] ?> (<?= $transaction['KANDANG_UKURAN'] ?>)</td>
                                    <td class="py-4 px-6"><?= $transaction['CHECKOUT'] ?></td>
                                <?php elseif ($activeTab === 'salon'): ?>
                                    <td class="py-4 px-6"><?= htmlentities($transaction['HEWAN_NAMA']) ?></td>
                                    <td class="py-4 px-6"><?= htmlentities($transaction['LAYANAN_INFO']) ?></td>
                                <?php else: ?>
                                    <td class="py-4 px-6"><?= htmlentities($transaction['PRODUK_INFO']) ?></td>
                                    <td class="py-4 px-6 text-center"><?= $transaction['TOTAL_QUANTITY'] ?></td>
                                <?php endif; ?>
                                <td class="py-4 px-6 text-right">Rp <?= number_format($transaction['TOTALHARGA'], 0, ',', '.') ?></td>
                                <?php if ($activeTab === 'product' && $canEdit): ?>
                                    <td class="py-4 px-6">
                                        <div class="flex gap-3 justify-center items-center">
                                            <button type="button" class="btn btn-error btn-sm" onclick="deleteRecord('<?= $transaction['ID'] ?>')">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </td>
                                <?php elseif (!$canEdit): ?>
                                    <td class="py-4 px-6">
                                        <div class="flex items-center gap-2">
                                            <i class="fas fa-eye-slash text-gray-400"></i>
                                            <span class="text-sm text-gray-400">View Only</span>
                                        </div>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Pagination Section -->
<div class="mt-6">
    <?php if (!empty($transactions)): ?>
        <div class="flex justify-center">
            <div class="btn-group">
                <?php if ($page > 1): ?>
                    <a href="?tab=<?= $activeTab ?>&page=1<?= $startDate ? '&start_date=' . urlencode($startDate) : '' ?><?= $endDate ? '&end_date=' . urlencode($endDate) : '' ?>" 
                       class="btn btn-sm">«</a>
                    
                    <a href="?tab=<?= $activeTab ?>&page=<?= $page - 1 ?><?= $startDate ? '&start_date=' . urlencode($startDate) : '' ?><?= $endDate ? '&end_date=' . urlencode($endDate) : '' ?>" 
                       class="btn btn-sm">Previous</a>
                <?php endif; ?>
                
                <?php
                // Calculate range of pages to show
                $startPage = max(1, min($page - 2, $totalPages - 4));
                $endPage = min($totalPages, max(5, $page + 2));
                
                for ($i = $startPage; $i <= $endPage; $i++):
                ?>
                    <a href="?tab=<?= $activeTab ?>&page=<?= $i ?><?= $startDate ? '&start_date=' . urlencode($startDate) : '' ?><?= $endDate ? '&end_date=' . urlencode($endDate) : '' ?>" 
                       class="btn btn-sm <?= ($page === $i) ? 'btn-active bg-[#D4F0EA] text-[#363636]' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?tab=<?= $activeTab ?>&page=<?= $page + 1 ?><?= $startDate ? '&start_date=' . urlencode($startDate) : '' ?><?= $endDate ? '&end_date=' . urlencode($endDate) : '' ?>" 
                       class="btn btn-sm">Next</a>
                    
                    <a href="?tab=<?= $activeTab ?>&page=<?= $totalPages ?><?= $startDate ? '&start_date=' . urlencode($startDate) : '' ?><?= $endDate ? '&end_date=' . urlencode($endDate) : '' ?>" 
                       class="btn btn-sm">»</a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="text-center mt-2 text-sm text-gray-600">
            Showing page <?= $page ?> of <?= $totalPages ?> (<?= $totalItems ?> total items)
        </div>
    <?php endif; ?>
</div>

<?php if ($activeTab === 'product' && $canEdit): ?>
    <!-- Floating Add Button -->
    <div class="fixed bottom-4 right-4">
        <label for="add_drawer" class="btn btn-circle btn-lg bg-[#B2E0D6] hover:bg-[#9AC7BE] text-[#363636] border-none">
            <i class="fas fa-plus text-xl"></i>
        </label>
    </div>

    <!-- Add Transaction Drawer -->
    <?php include 'drawer-product.php'; ?>

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
<?php elseif (!$canEdit): ?>
    <!-- View Only Info -->
    <div class="fixed bottom-5 right-5">
        <div class="bg-red-100 border border-red-400 text-red-700 px-6 py-3 rounded-full shadow-lg z-50 flex items-center gap-3">
            <div class="w-3 h-3 bg-red-400 rounded-full"></div>
            <span class="block sm:inline">Only STAFFS and OWNER can manage transactions</span>
        </div>
    </div>
<?php endif; ?>

<!-- Preview Modal -->
<div id="previewModal" class="modal">
    <div class="modal-box w-11/12 max-w-5xl">
        <h3 class="font-bold text-lg mb-4">Preview Laporan</h3>
        <div id="preview-content"></div>
        <div class="modal-action">
            <button onclick="printFromPreview()" class="btn btn-primary">
                <i class="fas fa-print mr-2"></i>Print
            </button>
            <button onclick="closePreviewModal()" class="btn">Tutup</button>
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
    const subtotals = $('#selected_products tr').map(function() {
        return parseFloat($(this).find('td:eq(4)').text().replace(/[^\d]/g, '')) || 0;
    }).get();
    const total = subtotals.reduce((sum, subtotal) => sum + subtotal, 0);
    $('#total_amount').text(formatNumber(total));
}

function renumberRows() {
    $('#selected_products tr').each(function(index) {
        $(this).find('td:first').text(index + 1);
    });
}

function removeProduct($button) {
    $button.closest('tr').remove();
    updateTotal();
    renumberRows();
}

function toggleProductDetail(element) {
    const detailDiv = element.querySelector('.product-detail');
    if (detailDiv) {
        const moreText = element.querySelector('span');
        if (detailDiv.classList.contains('hidden')) {
            detailDiv.classList.remove('hidden');
            const products = detailDiv.textContent.trim();
            const visibleText = element.textContent.split('...')[0];
            element.innerHTML = visibleText + products;
        } else {
            const allProducts = element.textContent.split(', ');
            const displayProducts = allProducts.slice(0, 3);
            const hiddenProducts = allProducts.slice(3);
            element.innerHTML = displayProducts.join(', ') + 
                ' <span class="text-blue-600">... ' + hiddenProducts.length + ' more</span>' +
                '<div class="hidden product-detail">' + hiddenProducts.join(', ') + '</div>';
        }
    }
}

$(document).ready(function() {
    // Inisialisasi Select2 dengan konfigurasi khusus untuk drawer
    $('.select2-in-drawer').select2({
        dropdownParent: $('#drawerAddProduct'),
        width: '100%',
        placeholder: 'Select an option',
        closeOnSelect: true,
        selectionCssClass: 'select2--small',
        dropdownCssClass: 'select2--small',
    });

    // Tambahkan event handler untuk menutup dropdown saat drawer ditutup
    $('#drawerAddProduct').on('hidden.bs.modal', function () {
        $('.select2-in-drawer').select2('close');
    });

    // Reset select2 saat drawer ditutup
    $('label[for="drawerAddProduct"]').on('click', function() {
        setTimeout(function() {
            $('.select2-in-drawer').select2('close');
        }, 100);
    });
});

function showPreview() {
    // Clone print content
    const printContent = document.getElementById('print-content').cloneNode(true);
    printContent.classList.remove('hidden');
    
    // Show in modal
    document.getElementById('preview-content').innerHTML = printContent.outerHTML;
    document.getElementById('previewModal').classList.add('modal-open');
}

function closePreviewModal() {
    document.getElementById('previewModal').classList.remove('modal-open');
}

function printFromPreview() {
    const printContent = document.getElementById('preview-content').firstChild;
    
    // Create new window
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Laporan Transaksi</title>
            <style>
                body { 
                    font-family: Arial, sans-serif;
                    padding: 20px;
                }
                table { 
                    width: 100%; 
                    border-collapse: collapse; 
                    margin-bottom: 1rem; 
                }
                th, td { 
                    padding: 8px; 
                    text-align: left; 
                    border: 1px solid #ddd;
                }
                th { 
                    background-color: #f8f9fa; 
                    font-weight: bold; 
                }
                .text-right { 
                    text-align: right; 
                }
                .text-center { 
                    text-align: center; 
                }
                .font-bold { 
                    font-weight: bold; 
                }
                @media print {
                    @page { 
                        size: landscape;
                        margin: 1cm; 
                    }
                }
            </style>
        </head>
        <body>
            ${printContent.outerHTML}
        </body>
        </html>
    `);
    
    // Print and close
    printWindow.document.close();
    printWindow.focus();
    setTimeout(() => {
        printWindow.print();
        printWindow.close();
    }, 250);
}

function applyQuickFilter(value) {
    const today = new Date();
    let startDate = '';
    
    switch(value) {
        case 'week':
            startDate = new Date(today.getTime() - (7 * 24 * 60 * 60 * 1000));
            break;
        case 'month':
            startDate = new Date(today.getTime() - (30 * 24 * 60 * 60 * 1000));
            break;
        case 'year':
            startDate = new Date(today.getTime() - (365 * 24 * 60 * 60 * 1000));
            break;
        default:
            // Reset dates if "Custom Range" is selected
            document.getElementById('start_date').value = '';
            document.getElementById('end_date').value = '';
            document.getElementById('filterForm').submit();
            return;
    }
    
    // Format dates
    document.getElementById('start_date').value = startDate.toISOString().split('T')[0];
    document.getElementById('end_date').value = today.toISOString().split('T')[0];
    
    // Submit form
    document.getElementById('filterForm').submit();
}
</script>

<style>
    .tab-active {
        border-top-left-radius: 0.5rem;
        border-top-right-radius: 0.5rem;
        position: relative;
    }
    .tab-active::after {
        content: '';
        position: absolute;
        bottom: -1px;
        left: 0;
        right: 0;
        height: 1px;
        background-color: #FCFCFC;
        z-index: 1;
    }
    .tab {
        border-top-left-radius: 0.5rem;
        border-top-right-radius: 0.5rem;
        border: 1px solid transparent;
        margin-right: 0.25rem;
    }
    .tab:hover:not(.tab-active) {
        border: 1px solid #363636;
        border-bottom: 0;
        background: transparent;
    }
    .tab:last-child {
        margin-right: 0;
    }

    /* Select2 in drawer styles */
    .select2-container {
        z-index: 9999;
    }
    
    .select2-dropdown {
        z-index: 9999;
    }

    .select2--small .select2-selection {
        height: 38px;
        padding: 4px 8px;
    }

    .select2--small .select2-selection__rendered {
        line-height: 30px;
    }

    .select2--small .select2-selection__arrow {
        height: 36px;
    }

    /* Modal styles */
    .modal-box {
        max-height: 90vh;
        overflow-y: auto;
    }

    #preview-content table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 1rem;
    }

    #preview-content th,
    #preview-content td {
        padding: 8px;
        border: 1px solid #ddd;
    }

    #preview-content th {
        background-color: #f8f9fa;
    }
</style>
