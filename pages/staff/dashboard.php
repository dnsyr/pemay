<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['posisi'] != 'staff') {
  header("Location: ../../auth/restricted.php");
  exit();
}
include '../staff/header.php'; // Include the header file for navigation

$pageTitle = 'Staff Dashboard';
?>

<!DOCTYPE html>
<html lang="en">

<body>
  <div class="page-container">
    <h1>Welcome to the Staff Dashboard, <?php echo $_SESSION['username']; ?>!</h1>
  </div>
</body>

</html>