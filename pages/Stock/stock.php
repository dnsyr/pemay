<?php
session_start();
include '../../config/connection.php';

$sql = "SELECT * FROM Stock";
$stid = oci_parse($conn, $sql);
oci_execute($stid);
$stocks = [];
while ($row = oci_fetch_assoc($stid)) {
  $stocks[] = $row;
}
oci_free_statement($stid);

// Delete Stock Item
if (isset($_GET['delete'])) {
  $id = $_GET['delete'];
  $sql = "DELETE FROM Stock WHERE ID = :id";
  $stid = oci_parse($conn, $sql);
  oci_bind_by_name($stid, ":id", $id);

  if (oci_execute($stid)) {
    echo "<script>alert('Stock item deleted successfully!');</script>";
  } else {
    echo "<script>alert('Failed to delete stock item.');</script>";
  }
  oci_free_statement($stid);
}

oci_close($conn);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Stock</title>
  <link rel="shortcut icon" href="../../public/img/icon.png" type="image/x-icon">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

  <link rel="stylesheet" href="../../public/css/index.css">
</head>

<body>
  <nav class="navbar navbar-expand-lg navbar-light navbar-container">
    <div class="container-fluid">
      <a class="navbar-brand" href="#">
        <img src="../../public/img/icon.png" alt="" width="30" height="30" class="d-inline-block align-text-top">
        <span class="navbar-title">Pemay</span>
      </a>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav">
          <li class="nav-item">
            <a class="nav-link" aria-current="page" href="dashboard.php">Dashboard</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="users.php">Users</a>
          </li>
          <li class="nav-item">
            <a class="nav-link active" href="stock.php">Stock</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#">Link</a>
          </li>
        </ul>
      </div>

      <form action="../../auth/logout.php" method="post">
        <button class="btn btn-link text-dark text-decoration-none" type="submit">Logout</button>
      </form>
    </div>
  </nav>

  <div class="page-container">
    <div class="d-flex justify-content-between">
      <h2>Manage Stock</h2>

      <a href="add_stock.php" class="btn btn-primary mb-3"><i class="fas fa-plus"></i> Add Stock</a>
    </div>

    <table class="table table-striped">
      <thead>
        <tr>
          <th>ID</th>
          <th>Item Name</th>
          <th>Quantity</th>
          <th>Price</th>
          <th>Category</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($stocks as $stock): ?>
          <tr>
            <td><?php echo htmlentities($stock['ID']); ?></td>
            <td><?php echo htmlentities($stock['NAMAITEM']); ?></td>
            <td><?php echo htmlentities($stock['JUMLAH']); ?></td>
            <td><?php echo htmlentities($stock['HARGA']); ?></td>
            <td><?php echo htmlentities($stock['KATEGORI']); ?></td>
            <td>
              <a href="update_stock.php?id=<?php echo $stock['ID']; ?>" class="btn btn-warning">
                <i class="fas fa-edit"></i> Edit
              </a>
              <a href="stock.php?delete=<?php echo $stock['ID']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this stock item?');">
                <i class="fas fa-trash-alt"></i> Delete
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>
