<?php
session_start();
include '../../config/connection.php';

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

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add User</title>
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
            <a class="nav-link" href="/../../Stock/stock.php">Stock</a>
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
            <select class="form-control" name="posisi" required>
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

  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>