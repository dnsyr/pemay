<?php
session_start();
include '../../config/connection.php';

if (!isset($_SESSION['username']) || $_SESSION['posisi'] != 'owner') {
  header("Location: ../../auth/restricted.php");
  exit();
}

$pageTitle = 'Add User';
include '../../layout/header.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add'])) {
  $nama = $_POST['nama'];
  $username = $_POST['username'];

  $checkSql = "SELECT COUNT(*) AS count FROM Pegawai WHERE Username = :username";
  $checkStid = oci_parse($conn, $checkSql);
  oci_bind_by_name($checkStid, ":username", $username);
  oci_execute($checkStid);
  $checkRow = oci_fetch_assoc($checkStid);
  oci_free_statement($checkStid);

  if ($checkRow['COUNT'] > 0) {
    echo "<script>alert('Username already exists!');</script>";
  } else {
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $posisi = $_POST['posisi'];
    $email = $_POST['email'];
    $nomorTelpon = $_POST['nomorTelpon'];

    $sql = "INSERT INTO Pegawai (Nama, Username, Password, Posisi, Email, NomorTelpon) 
                VALUES (:nama, :username, :password, :posisi, :email, :nomorTelpon)";
    $stid = oci_parse($conn, $sql);
    oci_bind_by_name($stid, ":nama", $nama);
    oci_bind_by_name($stid, ":username", $username);
    oci_bind_by_name($stid, ":password", $password);
    oci_bind_by_name($stid, ":posisi", $posisi);
    oci_bind_by_name($stid, ":email", $email);
    oci_bind_by_name($stid, ":nomorTelpon", $nomorTelpon);

    if (oci_execute($stid)) {
      echo "<script>alert('User added successfully!'); window.location.href='users.php';</script>";
    } else {
      echo "<script>alert('Failed to add user.');</script>";
    }
    oci_free_statement($stid);
  }
}

oci_close($conn);
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