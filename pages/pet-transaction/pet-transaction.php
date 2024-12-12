<?php
session_start();
include '../../config/connection.php'; // Pastikan path ini benar
$pageTitle = 'Manage Pet Transactions';
include '../../layout/header.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: ../../auth/login.php");
    exit();
}

// Tentukan tab aktif berdasarkan parameter URL
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'produk';

// Inisialisasi variabel berdasarkan tab aktif
$searchTerm = '';
$selectedPegawai = '';
$selectedPemilikHewan = '';
$startDate = '';
$endDate = '';
$selectedProducts = [];

// Pengaturan Pagination
$itemsPerPage = 10; // Sesuaikan jika diperlukan
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $itemsPerPage;

// Handle form submission untuk filter (hanya untuk tab 'produk')
if ($_SERVER["REQUEST_METHOD"] == "POST" && $activeTab === 'produk') {
    $searchTerm = isset($_POST['search']) ? trim($_POST['search']) : '';
    $selectedPegawai = isset($_POST['pegawai']) ? $_POST['pegawai'] : '';
    $selectedPemilikHewan = isset($_POST['pemilik_hewan']) ? $_POST['pemilik_hewan'] : '';
    $startDate = isset($_POST['start_date']) ? $_POST['start_date'] : '';
    $endDate = isset($_POST['end_date']) ? $_POST['end_date'] : '';
    $selectedProducts = isset($_POST['product_filter']) ? $_POST['product_filter'] : [];
}

// Handle permintaan pembatalan (hanya untuk tab 'produk')
if (isset($_GET['cancel_id']) && $activeTab === 'produk') {
    $cancel_id = $_GET['cancel_id'];

    // Panggil prosedur tersimpan DeletePenjualan
    $sqlCancel = "BEGIN DeletePenjualan(:id); END;";
    $stidCancel = oci_parse($conn, $sqlCancel);
    oci_bind_by_name($stidCancel, ":id", $cancel_id);

    if (oci_execute($stidCancel, OCI_NO_AUTO_COMMIT)) {
        // Commit transaksi setelah pembatalan berhasil
        if (oci_commit($conn)) {
            echo "<script>alert('Pet transaction canceled successfully! Stock has been restored.'); window.location.href='pet-transaction.php?tab=" . urlencode($activeTab) . "';</script>";
        } else {
            $e = oci_error($conn);
            echo "<script>alert('Failed to commit cancellation: " . htmlentities($e['message']) . "');</script>";
            oci_rollback($conn);
        }
    } else {
        $e = oci_error($stidCancel);
        echo "<script>alert('Failed to cancel pet transaction: " . htmlentities($e['message']) . "');</script>";
        oci_rollback($conn);
    }
    oci_free_statement($stidCancel);
}

// Fetch list Pegawai untuk filter (hanya untuk tab 'produk')
$pegawaiList = [];
$pegawaiQuery = "SELECT ID, NAMA FROM Pegawai WHERE onDelete = 0 ORDER BY NAMA";
$pegawaiStid = oci_parse($conn, $pegawaiQuery);
if (oci_execute($pegawaiStid)) {
    while ($row = oci_fetch_assoc($pegawaiStid)) {
        $pegawaiList[] = $row;
    }
} else {
    $e = oci_error($pegawaiStid);
    echo "<script>alert('Failed to fetch Pegawai: " . htmlentities($e['message']) . "');</script>";
}
oci_free_statement($pegawaiStid);

// Fetch list Pemilik Hewan untuk filter (hanya untuk tab 'produk')
$pemilikHewanList = [];
$pemilikHewanQuery = "SELECT ID, NAMA FROM PemilikHewan ORDER BY NAMA";
$pemilikHewanStid = oci_parse($conn, $pemilikHewanQuery);
if (oci_execute($pemilikHewanStid)) {
    while ($row = oci_fetch_assoc($pemilikHewanStid)) {
        $pemilikHewanList[] = $row;
    }
} else {
    $e = oci_error($pemilikHewanStid);
    echo "<script>alert('Failed to fetch Pemilik Hewan: " . htmlentities($e['message']) . "');</script>";
}
oci_free_statement($pemilikHewanStid);

// Inisialisasi transaksi dan variabel pagination
$transactions = [];
$totalItems = 0;
$totalPages = 1;

// Handle logika untuk tab 'produk'
if ($activeTab === 'produk') {
    // Siapkan wildcard pencarian
    $searchWildcard = '%' . $searchTerm . '%';

    // Bangun query utama dengan filter
    $searchQuery = " WHERE PJ.onDelete = 0"; // Tampilkan hanya transaksi aktif

    // Jika ada kata kunci pencarian, tambahkan ke query (pencarian berdasarkan ID Transaksi atau Nama Pegawai)
    if ($searchTerm) {
        $searchQuery .= " AND (UPPER(PJ.ID) LIKE UPPER(:searchTerm) OR UPPER(P.NAMA) LIKE UPPER(:searchTerm))";
    }

    // Jika rentang tanggal disediakan, filter berdasarkan tanggal
    if ($startDate) {
        $searchQuery .= " AND PJ.TANGGALTRANSAKSI >= TO_TIMESTAMP(:startDate, 'YYYY-MM-DD')";
    }
    if ($endDate) {
        $searchQuery .= " AND PJ.TANGGALTRANSAKSI <= TO_TIMESTAMP(:endDate, 'YYYY-MM-DD') + INTERVAL '1' DAY";
    }

    // Jika Pegawai dipilih, filter berdasarkan Pegawai_ID
    if ($selectedPegawai) {
        $searchQuery .= " AND PJ.PEGAWAI_ID = :pegawai_id";
    }

    // Jika Pemilik Hewan dipilih, filter berdasarkan PemilikHewan_ID
    if ($selectedPemilikHewan) {
        $searchQuery .= " AND PJ.PEMILIKHEWAN_ID = :pemilik_hewan_id";
    }

    // Jika Produk dipilih, filter berdasarkan Produk
    if (!empty($selectedProducts)) {
        // Buat placeholder dinamis untuk produk
        $productPlaceholders = [];
        foreach ($selectedProducts as $index => $prodId) {
            $placeholder = ":prod" . $index;
            $productPlaceholders[] = $placeholder;
        }
        $placeholdersString = implode(',', $productPlaceholders);
        $searchQuery .= " AND EXISTS (
                            SELECT 1 FROM TABLE(PJ.PRODUK) COLUMN_VALUE 
                            WHERE COLUMN_VALUE IN (" . $placeholdersString . ")
                        )";
    }

    // Query utama dengan LISTAGG dan COLUMN_VALUE
    $sql = "SELECT 
                PJ.ID, 
                PJ.TANGGALTRANSAKSI, 
                PJ.onDelete,
                (SELECT LISTAGG(COLUMN_VALUE, ', ') WITHIN GROUP (ORDER BY COLUMN_VALUE) 
                 FROM TABLE(PJ.PRODUK)) AS PRODUK
            FROM Penjualan PJ
            LEFT JOIN Pegawai P ON PJ.PEGAWAI_ID = P.ID
            LEFT JOIN PemilikHewan PH ON PJ.PEMILIKHEWAN_ID = PH.ID" . 
            $searchQuery . 
            " ORDER BY PJ.TANGGALTRANSAKSI DESC " . 
            " OFFSET :offset ROWS FETCH NEXT :itemsPerPage ROWS ONLY";

    $stid = oci_parse($conn, $sql);

    // Bind parameter
    if ($searchTerm) {
        oci_bind_by_name($stid, ":searchTerm", $searchWildcard);
    }
    if ($startDate) {
        oci_bind_by_name($stid, ":startDate", $startDate);
    }
    if ($endDate) {
        oci_bind_by_name($stid, ":endDate", $endDate);
    }
    if ($selectedPegawai) {
        oci_bind_by_name($stid, ":pegawai_id", $selectedPegawai);
    }
    if ($selectedPemilikHewan) {
        oci_bind_by_name($stid, ":pemilik_hewan_id", $selectedPemilikHewan);
    }

    // Bind pagination
    oci_bind_by_name($stid, ":offset", $offset, -1, SQLT_INT);
    oci_bind_by_name($stid, ":itemsPerPage", $itemsPerPage, -1, SQLT_INT);

    // Bind produk jika ada
    if (!empty($selectedProducts)) {
        foreach ($selectedProducts as $index => $prodId) {
            $placeholder = ":prod" . $index;
            oci_bind_by_name($stid, $placeholder, $selectedProducts[$index]);
        }
    }

    // Eksekusi query utama
    if (oci_execute($stid)) {
        while ($row = oci_fetch_assoc($stid)) {
            // Konversi string PRODUK menjadi array ID produk
            $produkIds = explode(', ', $row['PRODUK']);
            $produkArray = [];

            foreach ($produkIds as $prodId) {
                $produkArray[] = [
                    'product_id' => $prodId,
                    'nama'       => '', // Akan diisi nanti
                    'harga'      => 0,  // Akan diisi nanti
                    'quantity'   => 1,  // Placeholder; sesuaikan jika ada data quantity
                    'subtotal'   => 0   // Akan diisi nanti
                ];
            }

            // Sertakan status onDelete (opsional)
            $row['onDelete'] = $row['ONDELETE']; // Sesuaikan dengan sensitivitas huruf
            $row['PRODUK'] = $produkArray;
            $transactions[] = $row;
        }
    } else {
        $e = oci_error($stid);
        echo "<script>alert('Failed to fetch transactions: " . htmlentities($e['message']) . "');</script>";
    }
    oci_free_statement($stid);

    // Kumpulkan semua ID produk unik
    $allProductIds = [];
    foreach ($transactions as $transaction) {
        foreach ($transaction['PRODUK'] as $prod) {
            $allProductIds[] = $prod['product_id'];
        }
    }
    $allProductIds = array_unique($allProductIds);

    // Ambil detail semua produk dalam satu query
    $productMap = [];
    if (!empty($allProductIds)) {
        $productPlaceholders = [];
        foreach ($allProductIds as $index => $prodId) {
            $placeholder = ":prod_map" . $index;
            $productPlaceholders[] = $placeholder;
        }
        $placeholdersString = implode(',', $productPlaceholders);
        $productDetailsQuery = "SELECT ID, NAMA, HARGA FROM Produk WHERE ID IN (" . $placeholdersString . ")";
        $productDetailsStid = oci_parse($conn, $productDetailsQuery);

        // Bind parameter produk
        foreach ($allProductIds as $index => $prodId) {
            $placeholder = ":prod_map" . $index;
            oci_bind_by_name($productDetailsStid, $placeholder, $allProductIds[$index]);
        }

        // Eksekusi query detail produk
        if (oci_execute($productDetailsStid)) {
            while ($prodRow = oci_fetch_assoc($productDetailsStid)) {
                $productMap[$prodRow['ID']] = [
                    'nama'  => $prodRow['NAMA'],
                    'harga' => $prodRow['HARGA']
                ];
            }
        } else {
            $e = oci_error($productDetailsStid);
            echo "<script>alert('Failed to fetch product details: " . htmlentities($e['message']) . "');</script>";
        }
        oci_free_statement($productDetailsStid);
    }

    // Kaitkan detail produk dengan transaksi
    foreach ($transactions as &$transaction) {
        foreach ($transaction['PRODUK'] as &$prod) {
            if (isset($productMap[$prod['product_id']])) {
                $prod['nama'] = $productMap[$prod['product_id']]['nama'];
                $prod['harga'] = $productMap[$prod['product_id']]['harga'];
                $prod['subtotal'] = $prod['harga'] * $prod['quantity'];
            } else {
                $prod['nama'] = 'Unknown Product';
                $prod['harga'] = 0;
                $prod['subtotal'] = 0;
            }
        }
    }

    // Hitung total item untuk pagination
    $countSql = "SELECT COUNT(*) AS TOTAL 
                 FROM Penjualan PJ
                 LEFT JOIN Pegawai P ON PJ.PEGAWAI_ID = P.ID
                 LEFT JOIN PemilikHewan PH ON PJ.PEMILIKHEWAN_ID = PH.ID" . $searchQuery;

    $countStid = oci_parse($conn, $countSql);

    // Bind parameter untuk count
    if ($searchTerm) {
        oci_bind_by_name($countStid, ":searchTerm", $searchWildcard);
    }
    if ($startDate) {
        oci_bind_by_name($countStid, ":startDate", $startDate);
    }
    if ($endDate) {
        oci_bind_by_name($countStid, ":endDate", $endDate);
    }
    if ($selectedPegawai) {
        oci_bind_by_name($countStid, ":pegawai_id", $selectedPegawai);
    }
    if ($selectedPemilikHewan) {
        oci_bind_by_name($countStid, ":pemilik_hewan_id", $selectedPemilikHewan);
    }

    // Bind produk jika ada
    if (!empty($selectedProducts)) {
        foreach ($selectedProducts as $index => $prodId) {
            $placeholder = ":prod_map" . $index;
            oci_bind_by_name($countStid, $placeholder, $prodId);
        }
    }

    // Eksekusi query count
    if (oci_execute($countStid)) {
        $countRow = oci_fetch_assoc($countStid);
        $totalItems = $countRow['TOTAL'];
        $totalPages = ceil($totalItems / $itemsPerPage);
    }
    oci_free_statement($countStid);
}

// ===========================
// Handle View untuk Tab 'Medis'
// ===========================

if ($activeTab === 'medis') {
    // Karena logika untuk 'medis' akan ditangani kemudian, kita hanya menyiapkan view-nya saja
    // Data akan diisi nanti saat logika backend sudah siap
    // Anda bisa menambahkan form filter dan tabel sesuai kebutuhan
    // Berikut adalah contoh sederhana berdasarkan kode yang Anda berikan

    // Fetch list Hewan dan Pemilik Hewan untuk filter
    $hewanOptions = [];
    $hewanQuery = "SELECT DISTINCT h.NAMA FROM Hewan h WHERE h.onDelete = 0 ORDER BY h.NAMA";
    $hewanStid = oci_parse($conn, $hewanQuery);
    if (oci_execute($hewanStid)) {
        while ($row = oci_fetch_assoc($hewanStid)) {
            $hewanOptions[] = $row['NAMA'];
        }
    }
    oci_free_statement($hewanStid);

    $pemilikOptions = [];
    $pemilikQuery = "SELECT DISTINCT ph.NAMA FROM PemilikHewan ph WHERE ph.onDelete = 0 ORDER BY ph.NAMA";
    $pemilikStid = oci_parse($conn, $pemilikQuery);
    if (oci_execute($pemilikStid)) {
        while ($row = oci_fetch_assoc($pemilikStid)) {
            $pemilikOptions[] = $row['NAMA'];
        }
    }
    oci_free_statement($pemilikStid);
}

// ===========================
// Handle View untuk Tab 'Salon'
// ===========================

if ($activeTab === 'salon') {
    // Karena logika untuk 'salon' akan ditangani kemudian, kita hanya menyiapkan view-nya saja
    // Data akan diisi nanti saat logika backend sudah siap
    // Anda bisa menambahkan form filter dan tabel sesuai kebutuhan
    // Berikut adalah contoh sederhana berdasarkan kode yang Anda berikan

    // Fetch list Hewan dan Pemilik Hewan untuk filter
    // Jika sudah di-fetch sebelumnya, gunakan variabel yang sama
    // Tidak perlu melakukan query ulang
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title><?php echo htmlentities($pageTitle); ?></title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- Font Awesome CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <style>
        /* Optional: Style select2 elements to match Bootstrap */
        .select2-container .select2-selection--single {
            height: 38px;
            padding: 6px 12px;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 26px;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }

        .select2-container .select2-selection--multiple {
            min-height: 38px;
        }

        .table-secondary {
            background-color: #e9ecef;
        }
    </style>
</head>

<body>
    <div class="container mt-5">
        <h2>Pet Transactions Management</h2>

        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs" id="transactionTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link <?php echo ($activeTab === 'produk') ? 'active' : ''; ?>" href="pet-transaction.php?tab=produk">Product Sales</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($activeTab === 'medis') ? 'active' : ''; ?>" href="pet-transaction.php?tab=medis">Medical Services</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($activeTab === 'salon') ? 'active' : ''; ?>" href="pet-transaction.php?tab=salon">Salon Services</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($activeTab === 'hotel') ? 'active' : ''; ?>" href="pet-transaction.php?tab=hotel">Hotel Services</a>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="transactionTabsContent">
            <?php if ($activeTab === 'produk'): ?>
                <div class="tab-pane fade show active" id="produk" role="tabpanel" aria-labelledby="produk-tab">
                    <a href="./add-product-transaction.php" class="btn btn-success mt-3 mb-3"><i class="fas fa-plus"></i> Add Product Transaction</a>

                    <!-- Filter Form -->
                    <form method="POST" class="row g-3 mb-4">
                        <!-- Filter by Product using Select2 -->
                        <div class="col-md-4">
                            <label for="product_filter" class="form-label">Filter by Product</label>
                            <select class="form-control select2" id="product_filter" name="product_filter[]" multiple="multiple">
                                <?php
                                // Fetch all products for filter
                                $productFilterQuery = "SELECT ID, NAMA FROM Produk ORDER BY NAMA";
                                $productFilterStid = oci_parse($conn, $productFilterQuery);
                                if (oci_execute($productFilterStid)) {
                                    while ($row = oci_fetch_assoc($productFilterStid)) {
                                        // Check if product is selected in filter
                                        $selected = (in_array($row['ID'], $selectedProducts)) ? 'selected' : '';
                                        echo '<option value="' . htmlspecialchars($row['ID']) . '" ' . $selected . '>' . htmlspecialchars($row['NAMA']) . '</option>';
                                    }
                                } else {
                                    $e = oci_error($productFilterStid);
                                    echo "<option disabled>Failed to load products: " . htmlentities($e['message']) . "</option>";
                                }
                                oci_free_statement($productFilterStid);
                                ?>
                            </select>
                        </div>

                        <!-- Date Range -->
                        <div class="col-md-3">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlentities($startDate); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlentities($endDate); ?>">
                        </div>

                        <!-- Submit and Reset Buttons -->
                        <div class="col-md-2 d-flex align-items-end">
                            <button class="btn btn-outline-primary mr-2" type="submit">Filter</button>
                            <a href="pet-transaction.php?tab=produk" class="btn btn-outline-secondary">Reset</a>
                        </div>
                    </form>

                    <!-- Transactions Table -->
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Date</th>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Harga</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if (!empty($transactions)) {
                                $no = $offset + 1;
                                foreach ($transactions as $transaction): 
                                    $productDetails = $transaction['PRODUK'];
                            ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo htmlentities($transaction['TANGGALTRANSAKSI']); ?></td>
                                    <td>
                                        <?php 
                                        foreach ($productDetails as $pd) {
                                            echo htmlspecialchars($pd['nama']) . '<br>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        foreach ($productDetails as $pd) {
                                            echo htmlspecialchars($pd['quantity']) . '<br>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        foreach ($productDetails as $pd) {
                                            echo 'Rp ' . number_format($pd['harga'], 0, ',', '.') . '<br>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <a href="update-transaction.php?id=<?php echo htmlentities($transaction['ID']); ?>" class="btn btn-warning btn-sm">Update</a>
                                        <a href="pet-transaction.php?cancel_id=<?php echo htmlentities($transaction['ID']); ?>&tab=produk" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to cancel this transaction?')">Cancel</a>
                                    </td>
                                </tr>
                            <?php 
                                endforeach;
                            } else { 
                            ?>
                                <tr>
                                    <td colspan="6">No product transactions found.</td>
                                </tr>
                            <?php 
                            } 
                            ?>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <nav aria-label="Page navigation">
                            <ul class="pagination">
                                <?php 
                                // Determine the range of pages to display
                                $range = 2; // Number of pages around the current page
                                $start = max(1, $page - $range);
                                $end = min($totalPages, $page + $range);

                                // Previous button
                                if ($page > 1) {
                                    echo '<li class="page-item"><a class="page-link" href="?tab=produk&page=' . ($page - 1) . '">Previous</a></li>';
                                } else {
                                    echo '<li class="page-item disabled"><span class="page-link">Previous</span></li>';
                                }

                                // Page numbers
                                for ($i = $start; $i <= $end; $i++) {
                                    $active = ($i == $page) ? 'active' : '';
                                    echo '<li class="page-item ' . $active . '"><a class="page-link" href="?tab=produk&page=' . $i . '">' . $i . '</a></li>';
                                }

                                // Next button
                                if ($page < $totalPages) {
                                    echo '<li class="page-item"><a class="page-link" href="?tab=produk&page=' . ($page + 1) . '">Next</a></li>';
                                } else {
                                    echo '<li class="page-item disabled"><span class="page-link">Next</span></li>';
                                }
                                ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            <?php elseif ($activeTab === 'medis'): ?>
                <div class="tab-pane fade show active" id="medis" role="tabpanel" aria-labelledby="medis-tab">
                    <h3 class="mt-4">Medical Services</h3>
                    <!-- Filter Form for Medis -->
                    <form method="GET" action="pet-transaction.php" class="row g-3 mb-4">
                        <input type="hidden" name="tab" value="medis">
                        <!-- Filter by Hewan -->
                        <div class="col-md-4">
                            <label for="nama_hewan_medis" class="form-label">Nama Hewan</label>
                            <select name="nama_hewan" id="nama_hewan_medis" class="form-control select2">
                                <option value="">Pilih Nama Hewan</option>
                                <?php foreach ($hewanOptions as $hewan): ?>
                                    <option value="<?= htmlentities($hewan); ?>" <?= ($hewan === $searchTerm) ? 'selected' : ''; ?>>
                                        <?= htmlentities($hewan); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Filter by Pemilik Hewan -->
                        <div class="col-md-4">
                            <label for="nama_pemilik_medis" class="form-label">Nama Pemilik</label>
                            <select name="nama_pemilik" id="nama_pemilik_medis" class="form-control select2">
                                <option value="">Pilih Nama Pemilik</option>
                                <?php foreach ($pemilikOptions as $pemilik): ?>
                                    <option value="<?= htmlentities($pemilik); ?>" <?= ($pemilik === $selectedPemilikHewan) ? 'selected' : ''; ?>>
                                        <?= htmlentities($pemilik); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Date Range -->
                        <div class="col-md-2">
                            <label for="start_date_medis" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date_medis" name="start_date" value="<?php echo htmlentities($startDate); ?>">
                        </div>
                        <div class="col-md-2">
                            <label for="end_date_medis" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date_medis" name="end_date" value="<?php echo htmlentities($endDate); ?>">
                        </div>

                        <!-- Submit and Reset Buttons -->
                        <div class="col-md-2 d-flex align-items-end">
                            <button class="btn btn-outline-primary mr-2" type="submit">Filter</button>
                            <a href="pet-transaction.php?tab=medis" class="btn btn-outline-secondary">Reset</a>
                        </div>
                    </form>

                    <!-- Transactions Table for Medis -->
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <!-- <th>ID</th> --> <!-- Menghapus kolom ID -->
                                <th>Date</th>
                                <th>Total Biaya</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Nama Hewan</th>
                                <th>Nama Pemilik</th>
                                <!-- <th>Actions</th> --> <!-- Menghapus kolom Actions -->
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Karena logika untuk 'medis' belum diimplementasikan, kita hanya menampilkan data jika sudah diisi
                            if ($activeTab === 'medis') {
                                if (!empty($transactions)) {
                                    $no = $offset + 1;
                                    foreach ($transactions as $medis): 
                            ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <!-- <td><?php echo htmlentities($medis['ID']); ?></td> --> <!-- Menghapus kolom ID -->
                                            <td><?php echo htmlentities($medis['TANGGALTRANSAKSI']); ?></td>
                                            <td>Rp <?php echo number_format($medis['TOTALBIAYA'], 0, ',', '.'); ?></td>
                                            <td><?php echo htmlentities($medis['Description']); ?></td>
                                            <td><?php echo htmlentities($medis['Status']); ?></td>
                                            <td><?php echo htmlentities($medis['NamaHewan']); ?></td>
                                            <td><?php echo htmlentities($medis['NamaPemilik']); ?></td>
                                            <!-- <td>
                                                <a href="update-medis.php?id=<?php echo htmlentities($medis['ID']); ?>" class="btn btn-warning btn-sm">Update</a>
                                                <a href="pet-transaction.php?cancel_id=<?php echo htmlentities($medis['ID']); ?>&tab=medis" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to cancel this medical transaction?')">Cancel</a>
                                            </td> --> <!-- Menghapus kolom Actions -->
                                        </tr>
                            <?php 
                                    endforeach;
                                } else {
                                    echo '<tr><td colspan="7">No medical transactions found.</td></tr>';
                                }
                            }
                            ?>
                        </tbody>
                    </table>

                    <!-- Pagination for Medis -->
                    <?php if ($activeTab === 'medis' && $totalPages > 1): ?>
                        <nav aria-label="Page navigation">
                            <ul class="pagination">
                                <?php 
                                // Determine the range of pages to display
                                $range = 2; // Number of pages around the current page
                                $start = max(1, $page - $range);
                                $end = min($totalPages, $page + $range);

                                // Previous button
                                if ($page > 1) {
                                    echo '<li class="page-item"><a class="page-link" href="?tab=medis&page=' . ($page - 1) . '">Previous</a></li>';
                                } else {
                                    echo '<li class="page-item disabled"><span class="page-link">Previous</span></li>';
                                }

                                // Page numbers
                                for ($i = $start; $i <= $end; $i++) {
                                    $active = ($i == $page) ? 'active' : '';
                                    echo '<li class="page-item ' . $active . '"><a class="page-link" href="?tab=medis&page=' . $i . '">' . $i . '</a></li>';
                                }

                                // Next button
                                if ($page < $totalPages) {
                                    echo '<li class="page-item"><a class="page-link" href="?tab=medis&page=' . ($page + 1) . '">Next</a></li>';
                                } else {
                                    echo '<li class="page-item disabled"><span class="page-link">Next</span></li>';
                                }
                                ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            <?php elseif ($activeTab === 'salon'): ?>
                <div class="tab-pane fade show active" id="salon" role="tabpanel" aria-labelledby="salon-tab">
                    <h3 class="mt-4">Salon Services</h3>
                    <!-- Filter Form for Salon -->
                    <form method="GET" action="pet-transaction.php" class="row g-3 mb-4">
                        <input type="hidden" name="tab" value="salon">
                        <!-- Filter by Hewan -->
                        <div class="col-md-4">
                            <label for="nama_hewan_salon" class="form-label">Nama Hewan</label>
                            <select name="nama_hewan" id="nama_hewan_salon" class="form-control select2">
                                <option value="">Pilih Nama Hewan</option>
                                <?php foreach ($hewanOptions as $hewan): ?>
                                    <option value="<?= htmlentities($hewan); ?>" <?= ($hewan === $searchTerm) ? 'selected' : ''; ?>>
                                        <?= htmlentities($hewan); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Filter by Pemilik Hewan -->
                        <div class="col-md-4">
                            <label for="nama_pemilik_salon" class="form-label">Nama Pemilik</label>
                            <select name="nama_pemilik" id="nama_pemilik_salon" class="form-control select2">
                                <option value="">Pilih Nama Pemilik</option>
                                <?php foreach ($pemilikOptions as $pemilik): ?>
                                    <option value="<?= htmlentities($pemilik); ?>" <?= ($pemilik === $selectedPemilikHewan) ? 'selected' : ''; ?>>
                                        <?= htmlentities($pemilik); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Date Range -->
                        <div class="col-md-2">
                            <label for="start_date_salon" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date_salon" name="start_date" value="<?php echo htmlentities($startDate); ?>">
                        </div>
                        <div class="col-md-2">
                            <label for="end_date_salon" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date_salon" name="end_date" value="<?php echo htmlentities($endDate); ?>">
                        </div>

                        <!-- Submit and Reset Buttons -->
                        <div class="col-md-2 d-flex align-items-end">
                            <button class="btn btn-outline-primary mr-2" type="submit">Filter</button>
                            <a href="pet-transaction.php?tab=salon" class="btn btn-outline-secondary">Reset</a>
                        </div>
                    </form>

                    <!-- Transactions Table for Salon -->
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <!-- <th>ID</th> --> <!-- Menghapus kolom ID -->
                                <th>Date</th>
                                <th>Total Biaya</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Nama Hewan</th>
                                <th>Nama Pemilik</th>
                                <!-- <th>Actions</th> --> <!-- Menghapus kolom Actions -->
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Karena logika untuk 'salon' belum diimplementasikan, kita hanya menampilkan data jika sudah diisi
                            if ($activeTab === 'salon') {
                                if (!empty($transactions)) {
                                    $no = $offset + 1;
                                    foreach ($transactions as $salon): 
                            ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <!-- <td><?php echo htmlentities($salon['ID']); ?></td> --> <!-- Menghapus kolom ID -->
                                            <td><?php echo htmlentities($salon['TANGGALTRANSAKSI']); ?></td>
                                            <td>Rp <?php echo number_format($salon['TOTALBIAYA'], 0, ',', '.'); ?></td>
                                            <td><?php echo htmlentities($salon['Description']); ?></td>
                                            <td><?php echo htmlentities($salon['Status']); ?></td>
                                            <td><?php echo htmlentities($salon['NamaHewan']); ?></td>
                                            <td><?php echo htmlentities($salon['NamaPemilik']); ?></td>
                                            <!-- <td>
                                                <a href="update-salon.php?id=<?php echo htmlentities($salon['ID']); ?>" class="btn btn-warning btn-sm">Update</a>
                                                <a href="pet-transaction.php?cancel_id=<?php echo htmlentities($salon['ID']); ?>&tab=salon" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to cancel this salon transaction?')">Cancel</a>
                                            </td> --> <!-- Menghapus kolom Actions -->
                                        </tr>
                            <?php 
                                    endforeach;
                                } else {
                                    echo '<tr><td colspan="7">No salon transactions found.</td></tr>';
                                }
                            }
                            ?>
                        </tbody>
                    </table>

                    <!-- Pagination for Salon -->
                    <?php if ($activeTab === 'salon' && $totalPages > 1): ?>
                        <nav aria-label="Page navigation">
                            <ul class="pagination">
                                <?php 
                                // Determine the range of pages to display
                                $range = 2; // Number of pages around the current page
                                $start = max(1, $page - $range);
                                $end = min($totalPages, $page + $range);

                                // Previous button
                                if ($page > 1) {
                                    echo '<li class="page-item"><a class="page-link" href="?tab=salon&page=' . ($page - 1) . '">Previous</a></li>';
                                } else {
                                    echo '<li class="page-item disabled"><span class="page-link">Previous</span></li>';
                                }

                                // Page numbers
                                for ($i = $start; $i <= $end; $i++) {
                                    $active = ($i == $page) ? 'active' : '';
                                    echo '<li class="page-item ' . $active . '"><a class="page-link" href="?tab=salon&page=' . $i . '">' . $i . '</a></li>';
                                }

                                // Next button
                                if ($page < $totalPages) {
                                    echo '<li class="page-item"><a class="page-link" href="?tab=salon&page=' . ($page + 1) . '">Next</a></li>';
                                } else {
                                    echo '<li class="page-item disabled"><span class="page-link">Next</span></li>';
                                }
                                ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Future Tab Panes for Hotel -->
            <div class="tab-pane fade" id="hotel" role="tabpanel" aria-labelledby="hotel-tab">
                <!-- Content for Hotel Services -->
                <p class="mt-3">Hotel Services content goes here.</p>
            </div>
        </div>
    </div>

    <!-- Include jQuery and Select2 JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- Initialize Select2 -->
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                theme: 'bootstrap4',
                placeholder: "Select Options",
                allowClear: true
            });
        });
    </script>
</body>

</html>
