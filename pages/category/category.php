<?php
session_start();
include '../../config/database.php';

$pageTitle = 'Manage Categories';
include '../../layout/header-tailwind.php';

// Initialize Database
$db = new Database();

$message = "";

// Default tab
$tab = $_GET['tab'] ?? 'produk';

// Daftar tabel untuk kategori
$tables = [
    'produk' => ['table' => 'KategoriProduk', 'label' => 'Product'],
    'obat' => ['table' => 'KategoriObat', 'label' => 'Medicine'],
    'salon' => ['table' => 'JenisLayananSalon', 'label' => 'Salon Service'],
    'medis' => ['table' => 'JenisLayananMedis', 'label' => 'Medical Service'],
];

// Validasi tab
if (!array_key_exists($tab, $tables)) {
    $tab = 'produk'; // Default ke produk jika tab tidak valid
}

$currentTable = $tables[$tab]['table'];
$currentLabel = $tables[$tab]['label'];

// Proses Tambah Kategori
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $namaKategori = trim($_POST['namaKategori']);
    $biaya = isset($_POST['biaya']) ? (int) $_POST['biaya'] : 30000;

    // Siapkan query berdasarkan tab yang aktif
    if ($tab === 'salon' || $tab === 'medis') {
        $sql = "INSERT INTO $currentTable (Nama, Biaya) VALUES (:nama, :biaya)";
        $db->query($sql);
        $db->bind(':nama', $namaKategori);
        $db->bind(':biaya', $biaya);
    } else {
        $sql = "INSERT INTO $currentTable (Nama) VALUES (:nama)";
        $db->query($sql);
        $db->bind(':nama', $namaKategori);
    }

    if ($db->execute()) {
        $message = "$currentLabel berhasil ditambahkan.";
    } else {
        $message = "Gagal menambahkan $currentLabel.";
    }
}

// Proses Hapus Kategori
if (isset($_GET['delete_id'])) {
    $deleteId = $_GET['delete_id'];
    $sql = "UPDATE $currentTable SET onDelete = 1 WHERE ID = :id";
    $db->query($sql);
    $db->bind(':id', $deleteId);

    if ($db->execute()) {
        $message = "$currentLabel berhasil dihapus.";
    } else {
        $message = "Gagal menghapus $currentLabel.";
    }
}

// Ambil Data Kategori
$sql = "SELECT * FROM $currentTable WHERE onDelete = 0 ORDER BY ID";
$db->query($sql);
$categories = $db->resultSet();
?>

<!DOCTYPE html>
<html lang="en">
<body>
    <div class="pb-6 px-12 text-[#363636]">
        <div class="flex justify-between mb-6">
            <h2 class="text-3xl font-bold">Manage Categories</h2>
        </div>

        <!-- Tabs -->
        <div class="mb-6">
            <div class="inline-flex border-b border-[#363636]">
                <a href="?tab=produk" class="tab h-10 min-h-[2.5rem] !outline-none <?php echo $tab === 'produk' ? 'tab-active !bg-[#D4F0EA] !text-[#363636] !border-[#363636] !border-b-0' : 'text-[#363636] hover:text-[#363636]'; ?> text-base font-normal px-6">Product</a>
                <a href="?tab=obat" class="tab h-10 min-h-[2.5rem] !outline-none <?php echo $tab === 'obat' ? 'tab-active !bg-[#D4F0EA] !text-[#363636] !border-[#363636] !border-b-0' : 'text-[#363636] hover:text-[#363636]'; ?> text-base font-normal px-6">Medicine</a>
                <a href="?tab=salon" class="tab h-10 min-h-[2.5rem] !outline-none <?php echo $tab === 'salon' ? 'tab-active !bg-[#D4F0EA] !text-[#363636] !border-[#363636] !border-b-0' : 'text-[#363636] hover:text-[#363636]'; ?> text-base font-normal px-6">Salon Service</a>
                <a href="?tab=medis" class="tab h-10 min-h-[2.5rem] !outline-none <?php echo $tab === 'medis' ? 'tab-active !bg-[#D4F0EA] !text-[#363636] !border-[#363636] !border-b-0' : 'text-[#363636] hover:text-[#363636]'; ?> text-base font-normal px-6">Medical Service</a>
            </div>

            <!-- Tab Content -->
            <div class="bg-[#FCFCFC] border border-[#363636] rounded-b-xl p-6">
                <?php include 'tab-content.php'; ?>
            </div>
        </div>
    </div>

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
    </style>
</body>
</html>
