<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['posisi'] != 'vet') {
    header("Location: ../../auth/restricted.php");
    exit();
}

include '../../config/connection.php';
include '../vet/header.php';

// Fungsi untuk menghasilkan UUID (jika diperlukan)
// Anda bisa menghapus fungsi ini jika tidak diperlukan di sini
function generate_uuid() {
    $data = openssl_random_pseudo_bytes(16);
    assert(strlen($data) == 16);

    // Set version to 0100
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    // Set bits 6-7 to 10
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

$message = '';
$obat = [];

// Pastikan koneksi terhubung
if (!$conn) {
    die("Koneksi database gagal.");
}

// Ambil data kategori obat untuk dropdown
$sql = "SELECT * FROM KategoriObat WHERE onDelete = 0 ORDER BY Nama";
$stmt = oci_parse($conn, $sql);
oci_execute($stmt);

$kategoriObatList = [];
while ($row = oci_fetch_assoc($stmt)) {
    $kategoriObatList[] = $row;
}
oci_free_statement($stmt);

// Ambil data obat berdasarkan ID
if (isset($_GET['id'])) {
    $obatId = trim($_GET['id']);

    // Validasi format ID (UUID)
    if (!preg_match('/^[a-f0-9\-]{36}$/i', $obatId)) {
        echo '<div class="alert alert-danger">Format ID obat tidak valid.</div>';
        oci_close($conn);
        exit();
    }

    $sql = "SELECT * FROM ResepObat WHERE ID = :id AND onDelete = 0";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':id', $obatId);
    oci_execute($stmt);

    $obat = oci_fetch_assoc($stmt);
    oci_free_statement($stmt);

    if (!$obat) {
        echo '<div class="alert alert-danger">Obat tidak ditemukan.</div>';
        oci_close($conn);
        exit();
    }
} else {
    echo '<div class="alert alert-danger">ID obat tidak diberikan.</div>';
    oci_close($conn);
    exit();
}

// Proses update obat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    // Ambil ID dari hidden field
    $obatIdPost = isset($_POST['id']) ? trim($_POST['id']) : '';
    $layananMedisId = isset($_POST['layanan-medis-id']) ? trim($_POST['layanan-medis-id']) : '';

    // Validasi ID dari hidden field
    if (empty($obatIdPost) || !preg_match('/^[a-f0-9\-]{36}$/i', $obatIdPost)) {
        $message = 'ID obat tidak valid.';
    } else {
        $dosis = trim($_POST['dosis'] ?? '');
        $nama = trim($_POST['nama'] ?? '');
        $frekuensi = trim($_POST['frekuensi'] ?? '');
        $instruksi = trim($_POST['instruksi'] ?? '');
        $kategoriObatId = trim($_POST['kategori_obat_id'] ?? '');

        // Validasi data
        if (empty($dosis) || empty($nama) || empty($frekuensi) || empty($instruksi) || empty($kategoriObatId)) {
            $message = 'Semua field harus diisi dengan benar.';
        } else {
            // Validasi format UUID untuk kategoriObatId
            if (!preg_match('/^[a-f0-9\-]{36}$/i', $kategoriObatId)) {
                $message = 'Format ID Kategori Obat tidak valid.';
            } else {
                // Update data obat
                $sqlUpdate = "UPDATE ResepObat 
                              SET Dosis = :dosis, 
                                  Nama = :nama, 
                                  Frekuensi = :frekuensi, 
                                  Instruksi = :instruksi, 
                                  KategoriObat_ID = :kategori_obat_id
                              WHERE ID = :id";

                $stmtUpdate = oci_parse($conn, $sqlUpdate);
                oci_bind_by_name($stmtUpdate, ':dosis', $dosis);
                oci_bind_by_name($stmtUpdate, ':nama', $nama);
                oci_bind_by_name($stmtUpdate, ':frekuensi', $frekuensi);
                oci_bind_by_name($stmtUpdate, ':instruksi', $instruksi);
                oci_bind_by_name($stmtUpdate, ':kategori_obat_id', $kategoriObatId);
                oci_bind_by_name($stmtUpdate, ':id', $obatIdPost);

                if (oci_execute($stmtUpdate, OCI_COMMIT_ON_SUCCESS)) {
                    // Redirect ke halaman update-medical-services.php dengan ID Layanan Medis
                    header("Location: ../medicine/update-medical-services.php?id={$layananMedisId}");
                    exit(); // Pastikan setelah header redirect, skrip tidak diteruskan
                } else {
                    $error = oci_error($stmtUpdate);
                    $message = "Gagal memperbarui obat: " . htmlentities($error['message']);
                }
                oci_free_statement($stmtUpdate);
            }
        }
    }
    oci_close($conn);
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Update Obat</title>
    <link rel="stylesheet" href="../../public/css/index.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <h1>Update Obat</h1>

        <?php if ($message): ?>
            <div class="alert alert-danger"><?= htmlentities($message); ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?= htmlentities($obat['ID'] ?? ''); ?>">
            <input type="hidden" name="layanan-medis-id" value="<?= htmlentities($obat['LAYANANMEDIS_ID'] ?? ''); ?>">

            <div class="mb-3">
                <label for="dosis" class="form-label">Dosis</label>
                <input type="text" class="form-control" id="dosis" name="dosis" value="<?= htmlentities($obat['DOSIS'] ?? ''); ?>" required>
            </div>

            <div class="mb-3">
                <label for="nama" class="form-label">Nama Obat</label>
                <input type="text" class="form-control" id="nama" name="nama" value="<?= htmlentities($obat['NAMA'] ?? ''); ?>" required>
            </div>

            <div class="mb-3">
                <label for="frekuensi" class="form-label">Frekuensi</label>
                <input type="text" class="form-control" id="frekuensi" name="frekuensi" value="<?= htmlentities($obat['FREKUENSI'] ?? ''); ?>" required>
            </div>

            <div class="mb-3">
                <label for="instruksi" class="form-label">Instruksi</label>
                <textarea class="form-control" id="instruksi" name="instruksi" required><?= htmlentities($obat['INSTRUKSI'] ?? ''); ?></textarea>
            </div>

            <div class="mb-3">
                <label for="kategori_obat_id" class="form-label">Kategori Obat</label>
                <select class="form-select" id="kategori_obat_id" name="kategori_obat_id" required>
                    <option value="">-- Pilih Kategori Obat --</option>
                    <?php foreach ($kategoriObatList as $kategori): ?>
                        <option value="<?= htmlentities($kategori['ID']); ?>" <?= (isset($obat['KATEGORIOBAT_ID']) && $obat['KATEGORIOBAT_ID'] == $kategori['ID']) ? 'selected' : ''; ?>>
                            <?= htmlentities($kategori['NAMA']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Update Obat</button>
            <a href="../medicine/update-medical-services.php?id=<?= htmlentities($obat['LAYANANMEDIS_ID'] ?? ''); ?>" class="btn btn-secondary">Kembali</a>
        </form>
    </div>
</body>

</html>
