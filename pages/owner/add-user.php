<?php
session_start();
ob_start();
include '../../config/database.php';
include '../../handlers/pegawai.php';

if (!isset($_SESSION['username']) || $_SESSION['posisi'] != 'owner') {
  header("Location: ../../auth/restricted.php");
  exit();
}

$pageTitle = 'Add User';
include '../../layout/header.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add'])) {
  $db = new Database();

  createDataEmployee($db);
}

ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">

<body>
  <div class="page-container">
    <h2>Add User</h2>

    <form method="POST">
      <div class="d-flex flex-column gap-3">
        <div class="d-flex gap-5">
          <div class="form-group col-md-4">
            <label for="nama">Name</label>
            <input type="text" class="form-control" name="nama" required>
          </div>
          <div class="form-group col-md-4">
            <label for="username">Username</label>
            <input type="text" class="form-control" name="username" required>
          </div>
        </div>

        <div class="d-flex gap-5">
          <div class="form-group col-md-4">
            <label for="password">Password</label>
            <input type="password" class="form-control" name="password" required>
          </div>
          <div class="form-group col-md-4">
            <label for="posisi">Role</label>
            <select class="form-select" name="posisi" required>
              <option value="owner">Owner</option>
              <option value="vet">Vet</option>
              <option value="staff">Staff</option>
            </select>
          </div>
        </div>

        <div class="d-flex gap-5">
          <div class="form-group col-md-4">
            <label for="email">Email</label>
            <input type="email" class="form-control" name="email" required>
          </div>
          <div class="form-group col-md-4">
            <label for="nomorTelpon">Phone Number</label>
            <input type="text" class="form-control" name="nomorTelpon" required>
          </div>
        </div>

        <div class="d-flex gap-3 mt-3">
          <button type="submit" name="add" class="btn btn-success">Add User</button>
          <a href="users.php" class="btn btn-secondary">Cancel</a>
        </div>
      </div>
    </form>
  </div>

</body>

</html>