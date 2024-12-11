<?php
// Start output buffering to prevent "headers already sent" errors
ob_start();

// Start the session and check user authentication
session_start();
if (!isset($_SESSION['username']) || $_SESSION['posisi'] != 'vet') {
    header("Location: ../../auth/restricted.php");
    exit();
}

// Include the database connection
include '../../config/connection.php';

// Check if 'id' is provided in the URL
if (isset($_GET['id'])) {
    $id = trim($_GET['id']);
    if ($id > 0) {
        // Fetch Medical Service Details
        $sql = "SELECT lm.ID, lm.Tanggal, lm.TotalBiaya, lm.Description, lm.Status, 
                       h.Nama AS NamaHewan, h.Spesies, 
                       ph.Nama AS NamaPemilik, ph.NomorTelpon
                FROM LayananMedis lm
                JOIN Hewan h ON lm.Hewan_ID = h.ID
                JOIN PemilikHewan ph ON h.PemilikHewan_ID = ph.ID
                WHERE lm.ID = :id AND lm.onDelete = 0";
        $stmt = oci_parse($conn, $sql);
        oci_bind_by_name($stmt, ":id", $id);
    
        if (!oci_execute($stmt)) {
            $error = oci_error($stmt);
            die("Terjadi kesalahan saat mengambil data: " . htmlentities($error['message']));
        }
    
        $layanan = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);
    
        // Check if the medical service exists
        if (!$layanan) {
            echo "<script>alert('Layanan medis tidak ditemukan.'); window.location.href='dashboard.php';</script>";
            exit();
        }
    
        // Fetch Associated Medications (Obat)
        $sqlObatList = "SELECT o.*, ko.Nama AS KategoriObat
                        FROM ResepObat o
                        JOIN KategoriObat ko ON o.KategoriObat_ID = ko.ID
                        WHERE o.LayananMedis_ID = :id AND o.onDelete = 0";
        $stmtObatList = oci_parse($conn, $sqlObatList);
        oci_bind_by_name($stmtObatList, ":id", $id);
        oci_execute($stmtObatList);
    
        $obatList = [];
        while ($row = oci_fetch_assoc($stmtObatList)) {
            $obatList[] = $row;
        }
        oci_free_statement($stmtObatList);
    
        // Fetch Kategori Obat for Display (Optional)
        // If you need to display more details or for future enhancements
    
    } else {
        echo "<script>alert('ID tidak valid!'); window.location.href='dashboard.php';</script>";
        exit();
    }
} else {
    echo "<script>alert('ID tidak ditemukan!'); window.location.href='dashboard.php';</script>";
    exit();
}

// Close the database connection
oci_close($conn);

// End output buffering and flush output
ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Print Resep - Layanan Medis</title>
    <!-- Bootstrap CSS for styling -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Custom styles for the prescription */
        .prescription-container {
            margin: 20px auto;
            padding: 20px;
            border: 2px solid #000;
            max-width: 800px;
            background-color: #fff;
        }

        .prescription-header,
        .prescription-footer {
            text-align: center;
        }

        .prescription-header h1 {
            margin-bottom: 0;
        }

        .prescription-header p {
            margin-top: 0;
        }

        .prescription-details {
            margin-top: 20px;
        }

        .prescription-details table {
            width: 100%;
        }

        .prescription-details th,
        .prescription-details td {
            padding: 8px;
            text-align: left;
        }

        .prescription-medicine {
            margin-top: 20px;
        }

        .prescription-medicine table {
            width: 100%;
        }

        .prescription-medicine th,
        .prescription-medicine td {
            padding: 8px;
            text-align: left;
        }

        /* Print Styles */
        @media print {
            .no-print {
                display: none;
            }

            body {
                background-color: #fff;
            }
        }
    </style>
</head>

<body>
    <div class="prescription-container">
        <div class="prescription-header">
            <h1>Resep Dokter Hewan</h1>
            <p>Cukup Selama Hidup Hewan Peliharaan Anda</p>
            <hr>
        </div>

        <div class="prescription-details">
            <h3>Detail Layanan Medis</h3>
            <table class="table table-bordered">
                <tr>
                    <th>Tanggal</th>
                    <td><?= htmlentities($layanan['TANGGAL']); ?></td>
                </tr>
                <tr>
                    <th>Nama Hewan</th>
                    <td><?= htmlentities($layanan['NAMAHEWAN']); ?></td>
                </tr>
                <tr>
                    <th>Spesies</th>
                    <td><?= htmlentities($layanan['SPESIES']); ?></td>
                </tr>
                <tr>
                    <th>Nama Pemilik</th>
                    <td><?= htmlentities($layanan['NAMAPEMILIK']); ?></td>
                </tr>
                <tr>
                    <th>Nomor Telepon</th>
                    <td><?= htmlentities($layanan['NOMORTELPON']); ?></td>
                </tr>
                <tr>
                    <th>Deskripsi</th>
                    <td><?= nl2br(htmlentities($layanan['DESCRIPTION'])); ?></td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td><?= htmlentities($layanan['STATUS']); ?></td>
                </tr>
            </table>
        </div>

        <div class="prescription-medicine">
            <h3>Daftar Obat</h3>
            <?php if (!empty($obatList)): ?>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Nama Obat</th>
                            <th>Dosis</th>
                            <th>Frekuensi</th>
                            <th>Instruksi</th>
                            <th>Kategori Obat</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($obatList as $index => $obat): ?>
                            <tr>
                                <td><?= $index + 1; ?></td>
                                <td><?= htmlentities($obat['NAMA']); ?></td>
                                <td><?= htmlentities($obat['DOSIS']); ?></td>
                                <td><?= htmlentities($obat['FREKUENSI']); ?></td>
                                <td><?= htmlentities($obat['INSTRUKSI']); ?></td>
                                <td><?= htmlentities($obat['KATEGORIOBAT']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Barang tidak ada.</p>
            <?php endif; ?>
        </div>

        <div class="prescription-footer mt-4">
            <hr>
            <p>Terima kasih atas kepercayaan Anda.</p>
            <p>Dokter Hewan,</p>
            <br><br><br>
            <p>(____________________)</p>
        </div>

        <!-- Print Button -->
        <div class="text-center no-print mt-4">
            <button class="btn btn-primary" onclick="window.print()">Cetak Resep</button>
            <a href="update-medical-services.php?id=<?= htmlentities($id); ?>" class="btn btn-secondary">Kembali</a>
        </div>
    </div>
</body>

</html>
