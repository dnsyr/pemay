<?php
session_start();

include '../config/connection.php';

$role = "";
$is_logged_in = isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;

if (isset($_SESSION['posisi'])) {
  $role = $_SESSION['posisi'];
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

  <link rel="stylesheet" href="../public/css/index.css">
  <link rel="stylesheet" href="../public/css/components.css">

  <script src="https://www.google.com/recaptcha/enterprise.js" async defer></script>

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
      <form class="w-100 needs-validation mt-5 d-flex flex-column gap-3 align-items-center" novalidate action="authentication.php" method="POST">
        <div class="col-md-6">
          <div class="input-group has-validation">
            <span class="input-group-text" id="inputGroupPrepend"><i class="fa-solid fa-user"></i></span>
            <input type="text" name="username" class="form-control" placeholder="Username" id="validationCustomUsername" aria-describedby="inputGroupPrepend" required>
            <div class="invalid-feedback">
              Please input registered user.
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="input-group has-validation">
            <span class="input-group-text" id="inputGroupPrepend"><i class="fa-solid fa-lock"></i></span>
            <input type="password" name="password" class="form-control" placeholder="Password" id="validationCustomPassword" aria-describedby="inputGroupPrepend" required>
            <div class="invalid-feedback">
              Please input registered user.
            </div>
          </div>
        </div>

        <div class="col-md-6">
          <select class="form-select" name="posisi" id="validationCustom04" required>
            <option selected disabled value="">Role</option>
            <option value="owner">Owner</option>
            <option value="vet">Vet</option>
            <option value="staff">Staff</option>
          </select>
          <div class="invalid-feedback">
            Please choose ur role.
          </div>
        </div>

        <div class="col-md-6">
          <input type="text" name="captcha" class="form-control" placeholder="Enter CAPTCHA" required>
          <img src="generate-captcha.php" class="w-50 mt-3" alt="CAPTCHA">
        </div>

        <div class="col-12 text-end">
          <button class="btn btn-login" type="submit">Login</button>
        </div>
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