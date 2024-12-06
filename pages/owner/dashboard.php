<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['posisi'] != 'owner') {
  header("Location: ../../auth/restricted.php");
  exit();
}
include '../../layout/header.php';

$pageTitle = 'Owner Dashboard';
?>
<!DOCTYPE html>
<html lang="en">

<body>
  <div class="page-container">
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

    <h1>Welcome to the Owner Dashboard, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
  </div>

</body>

</html>