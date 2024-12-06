<?php
session_start();

include '../config/connection.php';

$role = "";
$is_logged_in = isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;

if (isset($_SESSION['posisi'])) {
  $role = $_SESSION['posisi'];
}

if (!isset($_SESSION['captcha'])) {
  $captchaText = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890'), 0, 6);
  $_SESSION['captcha'] = $captchaText;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login</title>
  <link rel="shortcut icon" href="../public/img/icon.png" type="image/x-icon">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

  <link rel="stylesheet" href="../public/css/index.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="../public/css/components.css?v=<?php echo time(); ?>">

  <script>
    const isLoggedIn = <?php echo json_encode($is_logged_in); ?>;
    const role = <?php echo json_encode($role); ?>;
    if (isLoggedIn) {
      if (role === 'owner') {
        window.location.href = '/pemay/pages/owner/dashboard.php'
      } else if (role === 'vet') {
        window.location.href = '/pemay/pages/vet/dashboard.php'
      } else if (role === 'staff') {
        window.location.href = '/pemay/pages/staff/dashboard.php'
      }
    }
  </script>
</head>

<body>
  <div class="container container-login d-flex justify-content-end align-items-center">
    <div class="login-card">
      <h2 class="title text-center">Pet Shop</h2>
      <h2 class="title text-center">Management System</h2>
      <form class="w-100 needs-validation mt-5 d-flex flex-column gap-3 align-items-center" validate action="authentication.php" method="POST">
        <div class="col-md-6">
          <div class="input-group">
            <input type="text" name="username" class="form-control" placeholder="Username" required>
          </div>
        </div>
        <div class="col-md-6">
          <div class="input-group">
            <input type="password" name="password" class="form-control" placeholder="Password" required>
          </div>
        </div>

        <div class="col-md-6">
          <select class="form-select" name="posisi" required>
            <option selected disabled value="">Role</option>
            <option value=" owner">Owner</option>
            <option value="vet">Vet</option>
            <option value="staff">Staff</option>
          </select>
        </div>

        <div class="col-md-6">
          <input type="text" name="captcha" class="form-control" placeholder="Enter CAPTCHA" required>
          <img src="generate-captcha.php" class="w-50 mt-3" alt="CAPTCHA">
        </div>

        <div class="col-12 text-end">
          <button class="btn btn-login" type="submit">Login</button>
        </div>

        <!-- Alert -->
        <?php if (isset($_SESSION['success_message']) && $_SESSION['success_message'] !== ""): ?>
          <div class="alert alert-info m-0 p-2">
            <?php echo htmlentities($_SESSION['success_message']);
            unset($_SESSION['success_message']); ?>
          </div>
        <?php elseif (isset($_SESSION['error_message']) && $_SESSION['error_message'] !== ""): ?>
          <div class="alert alert-danger m-0 p-2">
            <?php echo htmlentities($_SESSION['error_message']);
            unset($_SESSION['error_message']); ?>
          </div>
        <?php endif; ?>
      </form>

      <!-- <form action="generate-dummy-users.php" method="post">
        <button type="submit">Generate Dummy Users</button>
      </form> -->
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>