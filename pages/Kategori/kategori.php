<?php
include '../../config/connection.php';

$sql = "SELECT * FROM KategoriProduk ORDER BY ID";
$stid = oci_parse($conn, $sql);
oci_execute($stid);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Kategori</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <h2>Daftar Kategori</h2>
        <?php if (isset($_GET['message'])): ?>
            <div class="alert alert-info">
                <?php echo htmlentities($_GET['message']); ?>
            </div>
        <?php endif; ?>
        <a href="add_kategori.php" class="btn btn-primary mb-3">Tambah Kategori</a>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama Kategori</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = oci_fetch_assoc($stid)): ?>
                    <tr>
                        <td><?php echo htmlentities($row['ID']); ?></td>
                        <td><?php echo htmlentities($row['NAMA']); ?></td>
                        <td>
                            <a href="update_kategori.php?id=<?php echo $row['ID']; ?>" class="btn btn-warning btn-sm">Update</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>

</html>
<?php
oci_free_statement($stid);
oci_close($conn);
?>
