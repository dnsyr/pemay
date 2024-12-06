<?php
session_start();
include '../../config/connection.php';
include '../../layout/header.php';

$pageTitle = 'Manage Users';

if (!isset($_SESSION['username']) || $_SESSION['posisi'] != 'owner') {
  header("Location: ../../auth/restricted.php");
  exit();
}

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

<body>
  <div class="page-container">
    <div class="d-flex justify-content-between">
      <h2>Manage Users</h2>

      <!-- Alert -->
      <?php if (isset($_SESSION['success_message']) && $_SESSION['success_message'] !== ""): ?>
        <div class="alert alert-info">
          <?php echo htmlentities($_SESSION['success_message']);
          unset($_SESSION['success_message']); ?>
        </div>
      <?php elseif (isset($_SESSION['error_message']) && $_SESSION['error_message'] !== ""): ?>
        <div class="alert alert-danger">
          <?php echo htmlentities($_SESSION['error_message']);
          unset($_SESSION['error_message']); ?>
        </div>
      <?php endif; ?>

      <a href="add-user.php" class="btn btn-add rounded-circle"><i class="fas fa-plus fa-xl"></i></a>
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
              <a href="update-user.php?username=<?php echo $user['USERNAME']; ?>" class="btn btn-warning">
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

</body>

</html>