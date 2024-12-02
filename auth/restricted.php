<?php
session_start();
if (isset($_SESSION['user_logged_in'])) {
  unset($_SESSION['user_logged_in']);
  unset($_SESSION['username']);
  unset($_SESSION['posisi']);
  unset($_SESSION['employee_id']);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Access Restricted</title>
  <link rel="shortcut icon" href="../public/img/icon.png" type="image/x-icon">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

  <link rel="stylesheet" href="/pemay/public/css/index.css">
  <link rel="stylesheet" href="/pemay/public/css/components.css">

  <style>
    .w-40 {
      width: 40%;
    }
  </style>
</head>

<body>
  <div class="page-centered page-container">
    <h1>Access Restricted</h1>
    <p>You do not have permission to access this page.</p>
    <div class="d-flex justify-content-around w-25">
      <?php
      if (isset($_SESSION['employee_id'])) {
      ?>
        <form class="w-40" action="javascript:history.back()">
          <button type="submit" class="btn btn-primary py-2 px-3 w-100">Go Back</button>
        </form>
      <?php
      } else { ?>
        <a href="/pemay/auth/login.php" type="button" class="btn btn-primary py-2 px-3 w-40">Login</a>
      <?php
      }
      ?>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>