<?php
session_start();
include '../../config/connection.php';
include '../owner/header.php';

$pageTitle = 'Update User';

if (!isset($_SESSION['username']) || $_SESSION['posisi'] != 'owner') {
  header("Location: ../../auth/restricted.php");
  exit();
}

if (isset($_GET['username'])) {
  $username = $_GET['username'];
  $sql = "SELECT * FROM Pegawai WHERE Username = :username";
  $stid = oci_parse($conn, $sql);
  oci_bind_by_name($stid, ":username", $username);
  oci_execute($stid);
  $user = oci_fetch_assoc($stid);
  oci_free_statement($stid);
} else {
  header("Location: users.php");
  exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {
  $nama = $_POST['nama'];
  $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Encrypt password
  $posisi = $_POST['posisi'];
  $email = $_POST['email'];
  $nomorTelpon = $_POST['nomorTelpon'];

  $sql = "UPDATE Pegawai SET Nama = :nama, Password = :password, Posisi = :posisi, 
            Email = :email, NomorTelpon = :nomorTelpon WHERE Username = :username";
  $stid = oci_parse($conn, $sql);
  oci_bind_by_name($stid, ":nama", $nama);
  oci_bind_by_name($stid, ":password", $password);
  oci_bind_by_name($stid, ":posisi", $posisi);
  oci_bind_by_name($stid, ":email", $email);
  oci_bind_by_name($stid, ":nomorTelpon", $nomorTelpon);
  oci_bind_by_name($stid, ":username", $username);

  if (oci_execute($stid)) {
    echo "<script>alert('User updated successfully!'); window.location.href='users.php';</script>";
  } else {
    echo "<script>alert('Failed to update user.');</script>";
  }
  oci_free_statement($stid);
}

oci_close($conn);
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
            <input type="text" class="form-control" name="nama" value="<?php echo htmlentities($user['NAMA']); ?>" required>
          </div>
          <div class="form-group col-md-4">
            <label for="username">Username</label>
            <input type="text" class="form-control" name="username" value="<?php echo htmlentities($user['USERNAME']); ?>" disabled>
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
              <option value="owner" <?php echo ($user['POSISI'] == 'owner') ? 'selected' : ''; ?>>Owner</option>
              <option value="vet" <?php echo ($user['POSISI'] == 'vet') ? 'selected' : ''; ?>>Vet</option>
              <option value="staff" <?php echo ($user['POSISI'] == 'staff') ? 'selected' : ''; ?>>Staff</option>
            </select>
          </div>
        </div>

        <div class="d-flex gap-5">
          <div class="form-group col-md-4">
            <label for="email">Email</label>
            <input type="email" class="form-control" name="email" value="<?php echo htmlentities($user['EMAIL']); ?>" required>
          </div>
          <div class="form-group col-md-4">
            <label for="nomorTelpon">Phone Number</label>
            <input type="text" class="form-control" name="nomorTelpon" value="<?php echo htmlentities($user['NOMORTELPON']); ?>" required>
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