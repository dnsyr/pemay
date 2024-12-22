<?php
session_start();
include '../../config/connection.php';

$pageTitle = 'Add Product Transaction';
include '../../layout/header-tailwind.php';

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: ../../auth/login.php");
    exit();
}

$selectedemployee = isset($_SESSION['employee_id']) ? $_SESSION['employee_id'] : '';

// If employee ID not found, redirect to login
if (empty($selectedemployee)) {
    echo "<script>alert('Employee tidak ditemukan di session. Silakan login kembali.'); window.location.href='../../auth/login.php';</script>";
    exit();
}

// Initialize variables
$tanggalTransaksi = '';
$selectedProducts = [];
$selectedKategoriProduk = '';
$selectedKategoriObat = '';
$quantities = [];
$errors = [];
$totalBiaya = 0;

// Fetch KategoriProduk
$kategoriProdukList = [];
$kategoriProdukQuery = "SELECT ID, NAMA FROM KategoriProduk WHERE onDelete = 0 ORDER BY NAMA";
$kategoriProdukStid = oci_parse($conn, $kategoriProdukQuery);
if (!oci_execute($kategoriProdukStid)) {
    $e = oci_error($kategoriProdukStid);
    $errors[] = "Gagal mengambil Kategori Produk: " . htmlentities($e['message']);
}
while ($row = oci_fetch_assoc($kategoriProdukStid)) {
    $kategoriProdukList[] = $row;
}
oci_free_statement($kategoriProdukStid);

// Fetch KategoriObat
$kategoriObatList = [];
$kategoriObatQuery = "SELECT ID, NAMA FROM KategoriObat WHERE onDelete = 0 ORDER BY NAMA";
$kategoriObatStid = oci_parse($conn, $kategoriObatQuery);
if (!oci_execute($kategoriObatStid)) {
    $e = oci_error($kategoriObatStid);
    $errors[] = "Gagal mengambil Kategori Obat: " . htmlentities($e['message']);
}
while ($row = oci_fetch_assoc($kategoriObatStid)) {
    $kategoriObatList[] = $row;
}
oci_free_statement($kategoriObatStid);

// Fetch all products and group them
$allProducts = [];
$produkQuery = "SELECT ID, NAMA, HARGA, KATEGORIPRODUK_ID, KATEGORIOBAT_ID FROM Produk WHERE ONDELETE = 0 ORDER BY NAMA";
$produkStid = oci_parse($conn, $produkQuery);
if (!oci_execute($produkStid)) {
    $e = oci_error($produkStid);
    $errors[] = "Gagal mengambil Produk: " . htmlentities($e['message']);
}
while ($row = oci_fetch_assoc($produkStid)) {
    if (!empty($row['KATEGORIPRODUK_ID'])) {
        $kategoriType = 'produk';
        $kategoriId = $row['KATEGORIPRODUK_ID'];
    } elseif (!empty($row['KATEGORIOBAT_ID'])) {
        $kategoriType = 'obat';
        $kategoriId = $row['KATEGORIOBAT_ID'];
    } else {
        continue;
    }

    // Initialize arrays if not set
    if (!isset($allProducts[$kategoriType])) {
        $allProducts[$kategoriType] = [];
    }
    if (!isset($allProducts[$kategoriType][$kategoriId])) {
        $allProducts[$kategoriType][$kategoriId] = [];
    }

    // Add product to the appropriate category
    $allProducts[$kategoriType][$kategoriId][] = [
        'ID' => $row['ID'],
        'NAMA' => $row['NAMA'],
        'HARGA' => $row['HARGA']
    ];
}
oci_free_statement($produkStid);

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tanggalTransaksi = isset($_POST['tanggal_transaksi']) ? $_POST['tanggal_transaksi'] : '';
    $selectedKategoriProduk = isset($_POST['kategori_produk']) ? $_POST['kategori_produk'] : '';
    $selectedKategoriObat = isset($_POST['kategori_obat']) ? $_POST['kategori_obat'] : '';
    $selectedProducts = isset($_POST['produk']) ? $_POST['produk'] : [];
    $quantities = isset($_POST['quantity']) ? $_POST['quantity'] : [];

    // Validate input
    if (empty($tanggalTransaksi)) {
        $errors[] = "Tanggal transaksi harus diisi.";
    }
    if (empty($selectedKategoriProduk) && empty($selectedKategoriObat)) {
        $errors[] = "Setidaknya satu kategori harus dipilih (Produk atau Obat).";
    }
    if (empty($selectedProducts)) {
        $errors[] = "Setidaknya satu produk harus dipilih.";
    }
    // Validate quantities
    foreach ($selectedProducts as $prodId) {
        if (empty($quantities[$prodId]) || !is_numeric($quantities[$prodId]) || $quantities[$prodId] <= 0) {
            $errors[] = "Kuantitas untuk produk " . htmlspecialchars($prodId) . " harus berupa angka positif.";
            break;
        }
    }

    // Convert date format
    if (!empty($tanggalTransaksi)) {
        $date = DateTime::createFromFormat('Y-m-d\TH:i', $tanggalTransaksi);
        if ($date) {
            $tanggalTransaksi = $date->format('Y-m-d H:i:s');
        } else {
            $errors[] = "Format tanggal transaksi tidak valid.";
        }
    }

    // If no errors, proceed
    if (empty($errors)) {
        // Calculate total cost
        foreach ($selectedProducts as $prodId) {
            $quantity = $quantities[$prodId];
            // Find product price
            $harga = 0;
            foreach ($allProducts as $kategoriType => $kategori) {
                foreach ($kategori as $kategoriId => $produkList) {
                    foreach ($produkList as $produk) {
                        if ($produk['ID'] === $prodId) {
                            $harga = $produk['HARGA'];
                            break 3;
                        }
                    }
                }
            }
            $totalBiaya += $harga * $quantity;
        }
    }
        $ARRAYPRODUK = oci_new_collection($conn, 'ARRAYPRODUK', 'C##PET'); 
        if (!$ARRAYPRODUK) {
            $e = oci_error($conn);
            $errors[] = "Gagal membuat koleksi produk: " . htmlentities($e['message']);
        } else {
            foreach ($selectedProducts as $prodId) {
                $ARRAYPRODUK->append($prodId);
            }

            // Call stored procedure CreatePenjualan with TO_TIMESTAMP
            $sql = "BEGIN CreatePenjualan (
                        TO_TIMESTAMP(:tanggal, 'YYYY-MM-DD HH24:MI:SS'),
                        :produk,
                        :employee_id,
                        NULL,
                        NULL,
                        NULL,
                        NULL
                    ); END;";
            $stid = oci_parse($conn, $sql);
            oci_bind_by_name($stid, ":tanggal", $tanggalTransaksi);
            oci_bind_by_name($stid, ":employee_id", $selectedemployee);
            oci_bind_by_name($stid, ":produk", $ARRAYPRODUK, -1, OCI_B_NTY);

            // Execute stored procedure
            if (oci_execute($stid, OCI_NO_AUTO_COMMIT)) {
                error_log("Stored procedure CreatePenjualan executed successfully.");

                // Proceed to update stock and log
                $stokError = false;
                foreach ($selectedProducts as $prodId) {
                    $quantity = $quantities[$prodId];

                    // 1. Fetch current stock with row lock
                    $stokQuery = "SELECT JUMLAH FROM Produk WHERE ID = :id FOR UPDATE";
                    $stokStid = oci_parse($conn, $stokQuery);
                    oci_bind_by_name($stokStid, ":id", $prodId);

                    if (!oci_execute($stokStid)) {
                        $e = oci_error($stokStid);
                        $errors[] = "Gagal mengambil stok produk ID " . htmlentities($prodId) . ": " . htmlentities($e['message']);
                        // Log error
                        $logMessage = "[" . date('Y-m-d H:i:s') . "] Gagal mengambil stok produk ID " . htmlentities($prodId) . ": " . $e['message'] . "\n";
                        file_put_contents('error_log.txt', $logMessage, FILE_APPEND);
                        oci_free_statement($stokStid);
                        $stokError = true;
                        break;
                    }

                    $stokRow = oci_fetch_assoc($stokStid);
                    if (!$stokRow) {
                        $errors[] = "Produk dengan ID " . htmlentities($prodId) . " tidak ditemukan.";
                        oci_free_statement($stokStid);
                        $stokError = true;
                        break;
                    }

                    $stokAwal = $stokRow['JUMLAH'];
                    oci_free_statement($stokStid);

                    // 2. Calculate new stock
                    $stokAkhir = $stokAwal - $quantity;

                    if ($stokAkhir < 0) {
                        $errors[] = "Stok produk dengan ID " . htmlentities($prodId) . " tidak mencukupi. Stok saat ini: " . htmlentities($stokAwal) . ", kuantitas yang dibeli: " . htmlentities($quantity) . ".";
                        $stokError = true;
                        break;
                    }

                    // 3. Update stock in Produk
                    $updateStokQuery = "UPDATE Produk SET JUMLAH = :stok_akhir WHERE ID = :id";
                    $updateStokStid = oci_parse($conn, $updateStokQuery);
                    oci_bind_by_name($updateStokStid, ":stok_akhir", $stokAkhir);
                    oci_bind_by_name($updateStokStid, ":id", $prodId);

                    if (!oci_execute($updateStokStid)) {
                        $e = oci_error($updateStokStid);
                        $errors[] = "Gagal memperbarui stok produk ID " . htmlentities($prodId) . ": " . htmlentities($e['message']);
                        // Log error
                        $logMessage = "[" . date('Y-m-d H:i:s') . "] Gagal memperbarui stok produk ID " . htmlentities($prodId) . ": " . $e['message'] . "\n";
                        file_put_contents('error_log.txt', $logMessage, FILE_APPEND);
                        oci_free_statement($updateStokStid);
                        $stokError = true;
                        break;
                    }
                    oci_free_statement($updateStokStid);

                    // 4. Insert into LOGPRODUK
                    $insertLogQuery = "
                        INSERT INTO LOGPRODUK (
                            ID,
                            STOKAWAL,
                            STOKAKHIR,
                            PERUBAHAN,
                            KETERANGAN,
                            TANGGALPERUBAHAN,
                            PRODUK_ID,
                            PEGAWAI_ID
                        ) VALUES (
                            SYS_GUID(),
                            :stok_awal,
                            :stok_akhir,
                            :perubahan,
                            'Transaksi Penjualan',
                            SYSTIMESTAMP,
                            :produk_id,
                            :pegawai_id
                        )
                    ";
                    $insertLogStid = oci_parse($conn, $insertLogQuery);
                    oci_bind_by_name($insertLogStid, ":stok_awal", $stokAwal);
                    oci_bind_by_name($insertLogStid, ":stok_akhir", $stokAkhir);
                    oci_bind_by_name($insertLogStid, ":perubahan", $quantity);
                    oci_bind_by_name($insertLogStid, ":produk_id", $prodId);
                    oci_bind_by_name($insertLogStid, ":pegawai_id", $selectedemployee);

                    if (!oci_execute($insertLogStid)) {
                        $e = oci_error($insertLogStid);
                        $errors[] = "Gagal mencatat log untuk produk ID " . htmlentities($prodId) . ": " . htmlentities($e['message']);
                        // Log error
                        $logMessage = "[" . date('Y-m-d H:i:s') . "] Gagal mencatat log untuk produk ID " . htmlentities($prodId) . ": " . $e['message'] . "\n";
                        file_put_contents('error_log.txt', $logMessage, FILE_APPEND);
                        oci_free_statement($insertLogStid);
                        $stokError = true;
                        break;
                    }
                    oci_free_statement($insertLogStid);
                }

                if (!$stokError) {
                    // All operations successful, commit transaction
                    if (oci_commit($conn)) {
                        echo "<script>alert('Transaksi produk berhasil ditambahkan! Total Biaya: Rp " . number_format($totalBiaya, 2, ',', '.') . "'); window.location.href='pet-transaction.php?tab=produk';</script>";
                        exit();
                    } else {
                        $e = oci_error($conn);
                        $errors[] = "Gagal melakukan commit transaksi: " . htmlentities($e['message']);
                        oci_rollback($conn);
                        // Log error
                        $logMessage = "[" . date('Y-m-d H:i:s') . "] Gagal melakukan commit transaksi: " . $e['message'] . "\n";
                        file_put_contents('error_log.txt', $logMessage, FILE_APPEND);
                    }
                } else {
                    // Error occurred, rollback transaction
                    oci_rollback($conn);
                }
            } else {
                $e = oci_error($stid);
                $errors[] = "Gagal menambahkan transaksi produk: " . htmlentities($e['message']);

                // Log error
                $logMessage = "[" . date('Y-m-d H:i:s') . "] Gagal menambahkan transaksi produk: " . $e['message'] . "\n";
                file_put_contents('error_log.txt', $logMessage, FILE_APPEND);

                oci_rollback($conn);
            }

            // Free the collection and statement
            if ($ARRAYPRODUK) {
                $ARRAYPRODUK->free();
            }
            oci_free_statement($stid);
        }
    }
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <title><?php echo htmlentities($pageTitle); ?></title>
    <!-- Include necessary CSS and JS files -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Include Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- Include Font Awesome for icons if not already included -->
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

        .quantity-input {
            width: 80px;
        }
    </style>
</head>

<body>
    <div class="container mt-5">
        <h2>Add Product Transaction</h2>

        <!-- Display errors if any -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlentities($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Transaction Form -->
        <form method="POST">
            <div class="form-group">
                <label for="tanggal_transaksi">Tanggal Transaksi</label>
                <input type="datetime-local" class="form-control" id="tanggal_transaksi" name="tanggal_transaksi" value="<?php echo htmlentities($tanggalTransaksi); ?>" required>
            </div>

            <!-- Select Kategori Produk -->
            <div class="form-group">
                <label for="kategori_produk">Kategori Produk</label>
                <select class="form-control select2" id="kategori_produk" name="kategori_produk">
                    <option value="">Pilih Kategori Produk</option>
                    <?php foreach ($kategoriProdukList as $kp): ?>
                        <option value="<?php echo htmlentities($kp['ID']); ?>" <?php echo ($selectedKategoriProduk === $kp['ID']) ? 'selected' : ''; ?>><?php echo htmlentities($kp['NAMA']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Select Kategori Obat -->
            <div class="form-group">
                <label for="kategori_obat">Kategori Obat</label>
                <select class="form-control select2" id="kategori_obat" name="kategori_obat">
                    <option value="">Pilih Kategori Obat</option>
                    <?php foreach ($kategoriObatList as $ko): ?>
                        <option value="<?php echo htmlentities($ko['ID']); ?>" <?php echo ($selectedKategoriObat === $ko['ID']) ? 'selected' : ''; ?>><?php echo htmlentities($ko['NAMA']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Select Produk -->
            <div class="form-group">
                <label for="produk">Produk</label>
                <select class="form-control select2" id="produk" name="produk[]" multiple="multiple" required>
                    <option value="">Pilih Produk</option>
                    <?php
                    // If categories are selected, display appropriate products
                    if ((!empty($selectedKategoriProduk) || !empty($selectedKategoriObat)) && !empty($selectedProducts)) {
                        foreach ($selectedProducts as $prodId) {
                            // Find product name and price
                            $namaProduk = '';
                            $hargaProduk = 0;
                            foreach ($allProducts as $kategoriType => $kategori) {
                                foreach ($kategori as $kategoriId => $produkList) {
                                    foreach ($produkList as $produk) {
                                        if ($produk['ID'] === $prodId) {
                                            $namaProduk = $produk['NAMA'];
                                            $hargaProduk = $produk['HARGA'];
                                            break 3; // Exit all loops
                                        }
                                    }
                                }
                            }
                            if ($namaProduk !== '') {
                                echo '<option value="' . htmlentities($prodId) . '" selected>' . htmlentities($namaProduk) . ' (Rp ' . number_format($hargaProduk, 2, ',', '.') . ')</option>';
                            }
                        }
                    }
                    ?>
                </select>
                <small class="form-text text-muted">Pilih satu atau lebih produk yang dijual.</small>
            </div>

            <!-- Quantity Inputs -->
            <div class="form-group" id="quantity_inputs">
                <label>Kuantitas Produk</label>
                <!-- Quantity inputs will be dynamically populated using JavaScript -->
                <?php
                // If products are already selected (e.g., after validation failure), display quantity inputs
                if (!empty($selectedProducts)) {
                    foreach ($selectedProducts as $prodId) {
                        // Find product name based on ID
                        $namaProduk = '';
                        if (isset($allProducts)) {
                            foreach ($allProducts as $kategoriType => $kategori) {
                                foreach ($kategori as $kategoriId => $produkList) {
                                    foreach ($produkList as $produk) {
                                        if ($produk['ID'] === $prodId) {
                                            $namaProduk = $produk['NAMA'];
                                            break 3;
                                        }
                                    }
                                }
                            }
                        }
                        $quantity = isset($quantities[$prodId]) ? $quantities[$prodId] : 1;
                        echo '
                        <div class="input-group mb-2">
                            <div class="input-group-prepend">
                                <span class="input-group-text">' . htmlspecialchars($namaProduk) . '</span>
                            </div>
                            <input type="number" class="form-control quantity-input" name="quantity[' . htmlspecialchars($prodId) . ']" min="1" value="' . htmlspecialchars($quantity) . '" required>
                        </div>
                        ';
                    }
                }
                ?>
                <small class="form-text text-muted">Masukkan kuantitas untuk setiap produk yang dipilih.</small>
            </div>

            <!-- Total Cost -->
            <div class="form-group">
                <label>Total Biaya:</label>
                <p>Rp <span id="total_biaya"><?php echo number_format($totalBiaya, 2, ',', '.'); ?></span></p>
            </div>

            <!-- Submit and Back Buttons -->
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Transaction</button>
            <a href="pet-transaction.php?tab=produk" class="btn btn-secondary">Back</a>
        </form>
    </div>

    <!-- Include jQuery and Select2 JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- Include Bootstrap JS (Optional if needed) -->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <!-- JavaScript to Manage Categories and Products -->
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                placeholder: "Select options",
                allowClear: true
            });

            // Retrieve all products encoded in PHP
            const allProducts = <?php echo json_encode($allProducts); ?>;

            // Function to load products based on selected categories
            function loadProduk() {
                const selectedKategoriProduk = $('#kategori_produk').val();
                const selectedKategoriObat = $('#kategori_obat').val();
                const selectedProduk = $('#produk').val() || [];

                let availableProducts = [];

                // Add products from selected KategoriProduk
                if (selectedKategoriProduk && allProducts['produk'] && allProducts['produk'][selectedKategoriProduk]) {
                    availableProducts = availableProducts.concat(allProducts['produk'][selectedKategoriProduk]);
                }

                // Add products from selected KategoriObat
                if (selectedKategoriObat && allProducts['obat'] && allProducts['obat'][selectedKategoriObat]) {
                    availableProducts = availableProducts.concat(allProducts['obat'][selectedKategoriObat]);
                }

                // Remove duplicate products
                const uniqueProducts = {};
                availableProducts.forEach(function(produk) {
                    uniqueProducts[produk.ID] = produk;
                });
                availableProducts = Object.values(uniqueProducts);

                // Populate Produk select
                const produkSelect = $('#produk');
                produkSelect.empty();
                produkSelect.append('<option value="">Pilih Produk</option>');
                availableProducts.forEach(function(produk) {
                    let selected = selectedProduk.includes(produk.ID) ? 'selected' : '';
                    produkSelect.append('<option value="' + produk.ID + '" ' + selected + '>' + produk.NAMA + ' (Rp ' + parseFloat(produk.HARGA).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ')</option>');
                });

                produkSelect.trigger('change');

                // Update quantity inputs and total cost
                updateQuantityInputs();
                calculateTotal();
            }

            // Function to update quantity inputs based on selected products
            function updateQuantityInputs() {
                const selectedProduk = $('#produk').val();
                const quantityInputsDiv = $('#quantity_inputs');
                quantityInputsDiv.empty(); // Clear quantity inputs
                quantityInputsDiv.append('<label>Kuantitas Produk</label>');

                if (selectedProduk && selectedProduk.length > 0) {
                    selectedProduk.forEach(function(prodId) {
                        // Find product name from allProducts
                        let namaProduk = '';
                        for (const [kategoriType, kategori] of Object.entries(allProducts)) {
                            for (const [kategoriId, produkList] of Object.entries(kategori)) {
                                const produk = produkList.find(p => p.ID === prodId);
                                if (produk) {
                                    namaProduk = produk.NAMA;
                                    break;
                                }
                            }
                            if (namaProduk !== '') break;
                        }

                        // Find existing quantity or default to 1
                        let existingQuantity = $('input[name="quantity[' + prodId + ']"]').val() || 1;

                        quantityInputsDiv.append(`
                            <div class="input-group mb-2">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">${namaProduk}</span>
                                </div>
                                <input type="number" class="form-control quantity-input" name="quantity[${prodId}]" min="1" value="${existingQuantity}" required>
                            </div>
                        `);
                    });
                }

                quantityInputsDiv.append('<small class="form-text text-muted">Masukkan kuantitas untuk setiap produk yang dipilih.</small>');
            }

            // Function to calculate total cost
            function calculateTotal() {
                let total = 0;
                $('select[name="produk[]"] option:selected').each(function() {
                    let prodId = $(this).val();
                    let hargaText = $(this).text();
                    let hargaMatch = hargaText.match(/Rp\s([\d.,]+)/);
                    let harga = hargaMatch ? parseFloat(hargaMatch[1].replace(/\./g, '').replace(',', '.')) : 0;
                    let quantity = parseInt($('input[name="quantity[' + prodId + ']"]').val()) || 0;
                    total += harga * quantity;
                });
                $('#total_biaya').text(total.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            }

            // Event handlers for category changes
            $('#kategori_produk').on('change', function() {
                loadProduk();
            });

            $('#kategori_obat').on('change', function() {
                loadProduk();
            });

            // Event handler for product changes
            $('#produk').on('change', function() {
                updateQuantityInputs();
                calculateTotal();
            });

            // Event handler for quantity changes
            $(document).on('input', 'input[name^="quantity["]', function() {
                calculateTotal();
            });

            <?php if (!empty($selectedKategoriProduk) || !empty($selectedKategoriObat)): ?>
                loadProduk();
            <?php endif; ?>
        });
    </script>
</body>

</html>
