<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['posisi'] != 'owner') {
    header("Location: ../../auth/restricted.php");
    exit();
}

$pageTitle = 'Owner Dashboard';

// echo '<pre>';
// var_dump($_SESSION);
// echo '</pre>';

include '../owner/header.php';
?>
<!DOCTYPE html>
<html lang="en">

<body>
  <div class="page-container">
    <h1>Welcome to the Owner Dashboard, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
  </div>

</body>

</html>