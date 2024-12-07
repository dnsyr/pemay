<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['posisi'] != 'vet') {
  header("Location: ../../auth/restricted.php");
  exit();
}

$pageTitle = 'Vet Dashboard';
include './header.php'; // Include the header file for navigatio
?>

<!DOCTYPE html>
<html lang="en">

<body>


  <div class="page-container">
    <?php if (isset($_SESSION['success_message'])): ?>
      <div class="alert alert-info">
        <?php echo htmlentities($_SESSION['success_message']);
        unset($_SESSION['success_message']); ?>
      </div>
    <?php elseif (isset($_SESSION['error_message'])): ?>
      <div class="alert alert-danger">
        <?php echo htmlentities($_SESSION['error_message']);
        unset($_SESSION['error_message']); ?>
      </div>
    <?php endif; ?>

    <h1>Welcome to the Vet Dashboard, <?php echo $_SESSION['username']; ?>!</h1>
  </div>

  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>