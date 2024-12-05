<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['posisi'] != 'vet') {
    header("Location: ../../auth/restricted.php");
    exit();
}

include '../../config/connection.php';
include '../vet/header.php';

// Ambil data jenis layanan medis untuk ditampilkan sebagai checkbox
$sql = "SELECT * FROM JenisLayananMedis WHERE onDelete = 0 ORDER BY ID";
$stmt = oci_parse($conn, $sql);
oci_execute($stmt);

$jenisLayananMedis = [];
while ($row = oci_fetch_assoc($stmt)) {
    $jenisLayananMedis[] = $row;
}
oci_free_statement($stmt);

// Ambil data hewan untuk dropdown
$sql = "SELECT h.ID, h.Nama AS NamaHewan, h.Spesies, ph.Nama AS NamaPemilik
        FROM Hewan h
        JOIN PemilikHewan ph ON h.PemilikHewan_ID = ph.ID";
$stmt = oci_parse($conn, $sql);
oci_execute($stmt);

$hewanList = [];
while ($row = oci_fetch_assoc($stmt)) {
    $hewanList[] = $row;
}
oci_free_statement($stmt);

// Ambil data layanan medis yang akan diupdate
if (isset($_GET['id'])) {
    $layananId = intval($_GET['id']);
    $sql = "SELECT lm.*, 
    (SELECT LISTAGG(column_value, ',') WITHIN GROUP (ORDER BY column_value) 
     FROM TABLE(lm.JenisLayanan)) AS JenisLayananIDs 
FROM LayananMedis lm 
WHERE lm.ID = :layananId";

    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':layananId', $layananId);
    oci_execute($stmt);
    $layananData = oci_fetch_assoc($stmt);
    oci_free_statement($stmt);
}

// Proses update layanan medis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $layananId = $_POST['layanan_id'];
    $tanggal = $_POST['tanggal'];
    $totalBiaya = $_POST['total_biaya'];
    $description = $_POST['description'];
    $status = $_POST['status'];
    $hewan_id = $_POST['hewan_id'];
    $jenisLayananArray = $_POST['jenis_layanan'];

    // Konversi array menjadi string untuk VARRAY
    $jenisLayananString = "ARRAYJENISLAYANANMEDIS(" . implode(',', $jenisLayananArray) . ")";

    $sql = "UPDATE LayananMedis SET 
                Tanggal = TO_DATE(:tanggal, 'YYYY-MM-DD'), 
                TotalBiaya = :totalBiaya, 
                Description = :description, 
                Status = :status, 
                JenisLayanan = $jenisLayananString, 
                Hewan_ID = :hewan_id 
            WHERE ID = :layanan_id";
    
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':tanggal', $tanggal);
    oci_bind_by_name($stmt, ':totalBiaya', $totalBiaya);
    oci_bind_by_name($stmt, ':description', $description);
    oci_bind_by_name($stmt, ':status', $status);
    oci_bind_by_name($stmt, ':hewan_id', $hewan_id);
    oci_bind_by_name($stmt, ':layanan_id', $layananId);

    if (oci_execute($stmt)) {
        $message = "Layanan medis berhasil diperbarui.";
        header("Location: success.php?message=" . urlencode($message));
        exit();
    } else {
        $errorMessage = "Gagal memperbarui layanan medis.";
    }
    oci_free_statement($stmt);
}

// Jika layananData ada, ambil JenisLayananIDs untuk digunakan dalam checkbox
$selectedJenisLayanan = [];
if (isset($layananData['JENISLAYANANIDS'])) {
    $selectedJenisLayanan = explode(',', $layananData['JENISLAYANANIDS']);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Layanan Medis</title>
    <link rel="stylesheet" href="../../public/css/index.css">
    <script>
        function updateTotal() {
            let total = 0;
            const checkboxes = document.querySelectorAll('input[name="jenis_layanan[]"]:checked');
            checkboxes.forEach((checkbox) => {
                total += parseFloat(checkbox.getAttribute('data-biaya'));
            });
            document.getElementById('total_biaya').value = total;
        }
    </script>
</head>
<body>
    <div class="container">
        <h2>Update Layanan Medis</h2>
        <?php if (isset($errorMessage)): ?>
            <div class="error"><?php echo $errorMessage; ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <input type="hidden" name="layanan_id" value="<?php echo $layananId; ?>">
            
            <div class="mb-3">
                <label for="tanggal" class="form-label">Tanggal:</label>
                <input type="date" name="tanggal" id="tanggal" value="<?php echo date('Y-m-d', strtotime($layananData['TANGGAL'])); ?>" required class="form-control">
            </div>

            <div class="mb-3">
                <label for="hewan_id" class="form-label">Hewan:</label>
                <select name="hewan_id" id="hewan_id" required class="form-select">
                    <?php foreach ($hewanList as $hewan): ?>
                        <option value="<?php echo $hewan['ID']; ?>" <?php echo ($hewan['ID'] == $layananData['HEWAN_ID']) ? 'selected' : ''; ?>>
                            <?php echo $hewan['NAMAPEMILIK'] . ' - ' . $hewan['NAMAHEWAN'] . ' (' . $hewan['SPESIES'] . ')'; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="jenis_layanan" class="form-label">Jenis Layanan:</label>
                <div>
                    <?php foreach ($jenisLayananMedis as $layanan): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="jenis_layanan[]" 
                                   id="layanan_<?php echo $layanan['ID']; ?>" value="<?php echo $layanan['ID']; ?>" 
                                   data-biaya="<?php echo $layanan['BIAYA']; ?>" 
                                   <?php echo (in_array($layanan['ID'], $selectedJenisLayanan)) ? 'checked' : ''; ?> 
                                   onclick="updateTotal()">
                            <label class="form-check-label" for="layanan_<?php echo $layanan['ID']; ?>">
                                <?php echo $layanan['NAMA']; ?> - Biaya: Rp <?php echo number_format($layanan['BIAYA'], 0, ',', '.'); ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="mb-3">
                <label for="total_biaya" class="form-label">Total Biaya:</label>
                <input type="number" class="form-control" id="total_biaya" name="total_biaya" value="<?php echo number_format($layananData['TOTALBIAYA'], 0, ',', '.'); ?>" readonly>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Deskripsi:</label>
                <textarea class="form-control" id="description" name="description" required><?php echo htmlentities($layananData['DESCRIPTION']); ?></textarea>
            </div>

            <div class="mb-3">
                <label for="status" class="form-label">Status:</label>
                <select class="form-select" id="status" name="status" required>
                    <option value="Emergency" <?php echo ($layananData['STATUS'] == 'Emergency') ? 'selected' : ''; ?>>Emergency</option>
                    <option value="Selesai" <?php echo ($layananData['STATUS'] == 'Selesai') ? 'selected' : ''; ?>>Selesai</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Update Layanan Medis</button>
        </form>
    </div>
</body>
</html>
