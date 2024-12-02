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
                   (SELECT LISTAGG(jm.ID, ',') WITHIN GROUP (ORDER BY jm.ID) 
                    FROM TABLE(lm.JenisLayanan) jm) AS JenisLayananIDs 
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
    $jenisLayananString = "ArrayJenisLayananMedis(" . implode(',', $jenisLayananArray) . ")";

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
            <label for="tanggal">Tanggal:</label>
            <input type="date" name="tanggal" id="tanggal" value="<?php echo date('Y-m-d', strtotime($layananData['TANGGAL'])); ?>" required>

            <label for="hewan_id">Hewan:</label>
            <select name="hewan_id" id="hewan_id" required>
                <?php foreach ($hewanList as $hewan): ?>
                    <option value="<?php echo $hewan['ID']; ?>" <?php echo ($hewan['ID'] == $layananData['HEWAN_ID']) ? 'selected' : ''; ?>>
                        <?php echo $hewan['NamaHewan'] . ' (' . $hewan['NamaPemilik'] . ')'; ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Jenis Layanan:</label>
            <?php foreach ($jenisLayananMedis as $layanan): ?>
                <input type="checkbox" name="jenis_layanan[]" 
                       id="layanan_<?php echo $layanan['ID']; ?>" 
                       value="<?php echo $layanan['ID']; ?>" 
                       data-biaya="<?php echo $layanan['BIAYA']; ?>" 
                       onchange="updateTotal()"
                       <?php echo in_array($layanan['ID'], $selectedJenisLayanan) ? 'checked' : ''; ?>>
                <label for="layanan_<?php echo $layanan['ID']; ?>"><?php echo $layanan['Nama']; ?> (Biaya: <?php echo $layanan['BIAYA']; ?>)</label><br>
            <?php endforeach; ?>

            <label for="total_biaya">Total Biaya:</label>
            <input type="text" name="total_biaya" id="total_biaya" value="<?php echo $layananData['TOTALBIAYA']; ?>" readonly>

            <label for="description">Deskripsi:</label>
            <textarea name="description" id="description" required><?php echo $layananData['DESCRIPTION']; ?></textarea>

            <label for="status">Status:</label>
            <select name="status" id="status" required>
                <option value="active" <?php echo ($layananData['STATUS'] == 'active') ? 'selected' : ''; ?>>Aktif</option>
                <option value="inactive" <?php echo ($layananData['STATUS'] == 'inactive') ? 'selected' : ''; ?>>Tidak Aktif</option>
            </select>

            <input type="hidden" name="action" value="update">
            <button type="submit">Perbarui Layanan Medis</button>
        </form>
    </div>
</body>
</html>