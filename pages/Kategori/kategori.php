<?php
session_start();
include '../../config/connection.php';

// Default tab
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'produk';

$tables = [
    'produk' => ['table' => '"KategoriProduk"', 'label' => 'Kategori Produk'],
    'obat' => ['table' => '"KategoriObat"', 'label' => 'Kategori Obat'],
    'salon' => ['table' => '"JenisLayananSalon"', 'label' => 'Jenis Layanan Salon'],
    'medis' => ['table' => '"JenisLayananMedis"', 'label' => 'Jenis Layanan Medis'],
];

if (!array_key_exists($tab, $tables)) {
    $tab = 'produk'; // Default to 'produk' if tab is invalid
}

$currentTable = $tables[$tab]['table'];
$currentLabel = $tables[$tab]['label'];

// Ambil Data Kategori
$sql = "SELECT * FROM $currentTable ORDER BY ID";
$stid = oci_parse($conn, $sql);

if (!oci_execute($stid)) {
    $error = oci_error($stid);
    die("SQL Error: " . $error['message']);
}

$categories = [];
while ($row = oci_fetch_assoc($stid)) {
    $categories[] = $row;
}
oci_free_statement($stid);
oci_close($conn);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRUD Kategori</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <h2>CRUD Kategori</h2>

        <ul class="nav nav-tabs">
            <?php foreach ($tables as $key => $table): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $tab === $key ? 'active' : ''; ?>" href="?tab=<?php echo $key; ?>">
                        <?php echo htmlentities($table['label']); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <div class="mt-3">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><?php echo htmlentities($category['ID']); ?></td>
                            <td><?php echo htmlentities($category['NAMA']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>
