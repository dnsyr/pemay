<?php
session_start();
include '../../config/database.php';

$pageTitle = 'Update Category';
include '../../layout/header-tailwind.php';

// Initialize Database
$db = new Database();

// Ambil ID kategori dari URL
$id = $_GET['id'] ?? null;
$tab = $_GET['tab'] ?? 'produk';

if (!$id) {
    header("Location: category.php?tab=" . $tab);
    exit;
}

$tables = [
    'produk' => ['table' => 'KategoriProduk', 'label' => 'Product'],
    'obat' => ['table' => 'KategoriObat', 'label' => 'Medicine'],
    'salon' => ['table' => 'JenisLayananSalon', 'label' => 'Salon Service'],
    'medis' => ['table' => 'JenisLayananMedis', 'label' => 'Medical Service'],
];

if (!array_key_exists($tab, $tables)) {
    $tab = 'produk';
}

$currentTable = $tables[$tab]['table'];
$currentLabel = $tables[$tab]['label'];

// Ambil data kategori untuk edit
$sql = "SELECT * FROM $currentTable WHERE ID = :id";
$db->query($sql);
$db->bind(':id', $id);
$categoryToUpdate = $db->single();

if (!$categoryToUpdate) {
    die("Category not found.");
}

// Ambil semua data kategori untuk ditampilkan di tabel
$sql = "SELECT * FROM $currentTable WHERE onDelete = 0 ORDER BY ID";
$db->query($sql);
$categories = $db->resultSet();

// Proses update jika ada data yang dikirim
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $namaKategori = trim($_POST['namaKategori']);
    $biaya = isset($_POST['biaya']) ? (int) $_POST['biaya'] : null;

    if ($tab === 'salon' || $tab === 'medis') {
        $sql = "UPDATE $currentTable SET Nama = :nama, Biaya = :biaya WHERE ID = :id";
        $db->query($sql);
        $db->bind(':nama', $namaKategori);
        $db->bind(':biaya', $biaya);
        $db->bind(':id', $id);
    } else {
        $sql = "UPDATE $currentTable SET Nama = :nama WHERE ID = :id";
        $db->query($sql);
        $db->bind(':nama', $namaKategori);
        $db->bind(':id', $id);
    }

    if ($db->execute()) {
        echo "<script>alert('Category updated successfully!'); window.location.href='category.php?tab=" . $tab . "';</script>";
    } else {
        echo "<script>alert('Failed to update category.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
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
                <!-- Category List -->
                <div class="overflow-hidden border border-[#363636] rounded-xl shadow-md shadow-[#717171]">
                    <table class="table border-collapse w-full">
                        <thead>
                            <tr class="bg-[#D4F0EA] text-[#363636] font-semibold">
                                <th class="rounded-tl-xl px-6 py-3"><?php echo $currentLabel; ?> Name</th>
                                <?php if ($tab === 'salon' || $tab === 'medis'): ?>
                                    <th class="px-6 py-3 text-center">Price</th>
                                <?php endif; ?>
                                <th class="rounded-tr-xl px-6 py-3 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($categories)): ?>
                                <?php foreach ($categories as $index => $category): ?>
                                    <tr class="text-[#363636]">
                                        <td class="px-6 py-3 <?= $index === count($categories) - 1 ? 'rounded-bl-xl' : '' ?>">
                                            <?php echo htmlentities($category['NAMA']); ?>
                                        </td>
                                        <?php if ($tab === 'salon' || $tab === 'medis'): ?>
                                            <td class="px-6 py-3 text-center">Rp <?php echo number_format($category['BIAYA'], 0, ',', '.'); ?></td>
                                        <?php endif; ?>
                                        <td class="px-6 py-3 text-center <?= $index === count($categories) - 1 ? 'rounded-br-xl' : '' ?>">
                                            <div class="flex gap-3 justify-center items-center">
                                                <a href="update-category.php?id=<?php echo $category['ID']; ?>&tab=<?php echo $tab; ?>" 
                                                    class="btn btn-warning btn-sm">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="category.php?tab=<?php echo $tab; ?>&delete_id=<?php echo $category['ID']; ?>" 
                                                    class="btn btn-error btn-sm" 
                                                    onclick="return confirm('Apakah Anda yakin ingin menghapus?')">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="<?php echo ($tab === 'salon' || $tab === 'medis') ? '3' : '2'; ?>" class="px-6 py-3 text-center">
                                        Tidak ada data.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Category Drawer -->
    <div class="drawer drawer-end z-10">
        <input id="drawerUpdateCategory" type="checkbox" class="drawer-toggle" checked />
        <div class="drawer-side">
            <label for="drawerUpdateCategory" aria-label="close sidebar" class="drawer-overlay"></label>
            <div class="menu bg-[#FCFCFC] text-[#363636] min-h-screen w-96 flex flex-col justify-center px-8">
                <h3 class="text-lg font-semibold mb-7">Update <?php echo $currentLabel; ?></h3>
                <form action="update-category.php?id=<?php echo $id; ?>&tab=<?php echo $tab; ?>" method="post" class="gap-5 flex flex-col">
                    <input type="hidden" name="action" value="update">
                    <div>
                        <label for="namaKategori"><?php echo $currentLabel; ?> Name</label>
                        <input type="text" class="mt-1 w-full rounded-full bg-[#FCFCFC] border border-[#565656] text-[#565656] text-sm px-7 py-2" 
                            id="namaKategori" name="namaKategori" value="<?php echo htmlentities($categoryToUpdate['NAMA']); ?>" required>
                    </div>
                    <?php if ($tab === 'salon' || $tab === 'medis'): ?>
                        <div>
                            <label for="biaya">Price</label>
                            <input type="number" class="mt-1 w-full rounded-full bg-[#FCFCFC] border border-[#565656] text-[#565656] text-sm px-7 py-2" 
                                id="biaya" name="biaya" value="<?php echo htmlentities($categoryToUpdate['BIAYA']); ?>" required>
                        </div>
                    <?php endif; ?>
                    <div class="flex justify-end gap-5 mt-5">
                        <button type="submit" class="btn bg-[#B2B5E0] text-[#565656] shadow-md shadow-[#565656] px-3 rounded-full hover:bg-[#565656] hover:text-[#FCFCFC] flex items-center">
                            <i class="fas fa-save fa-md"></i> Update Category
                        </button>
                        <a href="category.php?tab=<?php echo $tab; ?>" class="btn bg-[#E0BAB2] text-[#565656] shadow-md shadow-[#565656] px-3 rounded-full hover:bg-[#565656] hover:text-[#FCFCFC]">Cancel</a>
                    </div>
                </form>
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
