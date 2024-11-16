<?php
session_start();
include '../../config/connection.php';

$sql = "SELECT * FROM Pegawai";
$stid = oci_parse($conn, $sql);
oci_execute($stid);
$users = [];
while ($row = oci_fetch_assoc($stid)) {
  $users[] = $row;
}
oci_free_statement($stid);

// Delete Users
if (isset($_GET['delete'])) {
  $username = $_GET['delete'];
  $sql = "DELETE FROM Pegawai WHERE Username = :username";
  $stid = oci_parse($conn, $sql);
  oci_bind_by_name($stid, ":username", $username);

  if (oci_execute($stid)) {
    echo "<script>alert('User deleted successfully!');</script>";
  } else {
    echo "<script>alert('Failed to delete user.');</script>";
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
  <title>Manage Users</title>
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
            <a class="nav-link active" href="users.php">Users</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="../Stock/stock.php">Stock</a>
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
      <h2>Manage Users</h2>

      <a href="add_user.php" class="btn btn-primary mb-3"><i class="fas fa-plus"></i> Add User</a>
    </div>

    <table class="table table-striped">
      <thead>
        <tr>
          <th>Name</th>
          <th>Username</th>
          <th>Role</th>
          <th>Email</th>
          <th>Phone Number</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $user): ?>
          <tr>
            <td><?php echo htmlentities($user['NAMA']); ?></td>
            <td><?php echo htmlentities($user['USERNAME']); ?></td>
            <td><?php echo htmlentities($user['POSISI']); ?></td>
            <td><?php echo htmlentities($user['EMAIL']); ?></td>
            <td><?php echo htmlentities($user['NOMORTELPON']); ?></td>
            <td>
              <a href="update_user.php?username=<?php echo $user['USERNAME']; ?>" class="btn btn-warning">
                <i class="fas fa-edit"></i> Edit
              </a>
              <a href="users.php?delete=<?php echo $user['USERNAME']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this user?');">
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