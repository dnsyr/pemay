<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['posisi'] != 'staff') {
  header("Location: ../../auth/restricted.php");
  exit();
}
include '../staff/header.php';

$pageTitle = 'Pet Hotel';
?>
<!DOCTYPE html>
<html lang="en">


<body>
  <div class="page-container">
    <div class="d-flex justify-content-between">
      <h2>Pet Hotel Information</h2>

      <a href="reserve_hotel.php" class="btn btn-add rounded-circle"><i class="fas fa-plus fa-xl"></i></a>
    </div>
</body>

</html>