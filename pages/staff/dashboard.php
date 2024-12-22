<?php
session_start();
ob_start();
include '../../config/connection.php';
include '../../config/database.php';

$pageTitle = 'Staff Dashboard';
include '../../layout/header-tailwind.php';


if (!isset($_SESSION['username']) || $_SESSION['posisi'] != 'staff') {
  header("Location: ../../auth/restricted.php");
  exit();
} // Include the header file for navigation
?>

<!DOCTYPE html>
<html lang="en">

<body>
  <div class="page-container">
    <h1>Welcome to the Dashboard, <?php echo $_SESSION['username']; ?>!</h1>
  </div>
</body>

</html>