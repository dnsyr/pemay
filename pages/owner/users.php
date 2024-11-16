<?php
session_start();
include '../../config/connection.php';
include '../owner/header.php';

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