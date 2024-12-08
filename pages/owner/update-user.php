<?php
session_start();
ob_start();
include '../../config/database.php';
include '../../handlers/pegawai.php';

if (!isset($_SESSION['username']) || $_SESSION['posisi'] != 'owner') {
  header("Location: ../../auth/restricted.php");
  exit();
}

$pageTitle = 'Update User';
include '../../layout/header.php';

if (isset($_GET['username'])) {
  $username = $_GET['username'];

  $db = new Database();
  $dataEmployee = getDataEmployee($db, $username);
  $dataEmployee = $dataEmployee[0];
} else {
  header("Location: users.php");
  exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {
  updateDataEmployee($db, $username);
}

ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">

<body>
  <div class="page-container">
    <h2>Update User</h2>

    <form method="POST">
      <div class="d-flex flex-column gap-3">
        <div class="d-flex gap-5">
          <div class="form-group col-md-4">
            <label for="nama">Name</label>
            <input type="text" class="form-control" name="nama" value="<?php echo htmlentities($dataEmployee['NAMA']); ?>" required>
          </div>
          <div class="form-group col-md-4">
            <label for="username">Username</label>
            <input type="text" class="form-control" name="username" value="<?php echo htmlentities($dataEmployee['USERNAME']); ?>" disabled>
          </div>
        </div>

        <div class="d-flex gap-5">
          <div class="form-group col-md-4">
            <label for="password">Password</label>
            <input type="password" class="form-control" name="password" placeholder="Leave blank to keep current password">
          </div>
          <div class="form-group col-md-4">
            <label for="posisi">Role</label>
            <select class="form-select" name="posisi" required>
              <option value="owner" <?php echo ($dataEmployee['POSISI'] == 'owner') ? 'selected' : ''; ?>>Owner</option>
              <option value="vet" <?php echo ($dataEmployee['POSISI'] == 'vet') ? 'selected' : ''; ?>>Vet</option>
              <option value="staff" <?php echo ($dataEmployee['POSISI'] == 'staff') ? 'selected' : ''; ?>>Staff</option>
            </select>
          </div>
        </div>

        <div class="d-flex gap-5">
          <div class="form-group col-md-4">
            <label for="email">Email</label>
            <input type="email" class="form-control" name="email" value="<?php echo htmlentities($dataEmployee['EMAIL']); ?>" required>
          </div>
          <div class="form-group col-md-4">
            <label for="nomorTelpon">Phone Number</label>
            <input type="text" class="form-control" name="nomorTelpon" value="<?php echo htmlentities($dataEmployee['NOMORTELPON']); ?>" required>
          </div>
        </div>

        <div class="d-flex gap-3 mt-3">
          <button type="submit" name="update" class="btn btn-warning">Update User</button>
          <a href="users.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
  </div>

</body>

</html>