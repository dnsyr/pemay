<?php
session_start();
include '../../config/connection.php';
include '../../config/database.php';
include '../../handlers/pegawai.php';

$pageTitle = 'Manage Users';
include '../../layout/header.php';

if (!isset($_SESSION['username']) || $_SESSION['posisi'] != 'owner') {
  header("Location: ../../auth/restricted.php");
  exit();
}

$db = new Database();

$dataEmployees = getAllDataEmployees($db);

// Delete Users
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
  $username = $_POST['delete'];

  deleteEmployee($db, $username);
}
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
        <?php foreach ($dataEmployees as $user): ?>
          <tr>
            <td><?php echo htmlentities($user['NAMA']); ?></td>
            <td><?php echo htmlentities($user['USERNAME']); ?></td>
            <td><?php echo htmlentities($user['POSISI']); ?></td>
            <td><?php echo htmlentities($user['EMAIL']); ?></td>
            <td><?php echo htmlentities($user['NOMORTELPON']); ?></td>
            <td>
              <div class="d-flex gap-3 align-items-center">
                <a href="update-user.php?username=<?php echo $user['USERNAME']; ?>" class="btn btn-warning">
                  <i class="fas fa-edit"></i> Edit
                </a>
                <form method="POST" action="">
                  <input type="hidden" name="delete" value="<?php echo $user['USERNAME']; ?>">
                  <button
                    type="submit"
                    class="btn btn-danger btn-sm"
                    onclick="return confirm('Are you sure you want to delete this user?');">
                    <i class="fas fa-trash-alt"></i> Delete
                  </button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

</body>

</html>