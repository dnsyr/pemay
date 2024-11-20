<?php
// This file can be included in the staff dashboard pages
$currentPage = basename($_SERVER['PHP_SELF'], ".php"); // Get current page name without extension
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title><?php echo isset($pageTitle) ? $pageTitle : 'Vet'; ?></title>
  <link rel="shortcut icon" href="/pemay/public/img/icon.png" type="image/x-icon">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="/pemay/public/css/index.css">
  <link rel="stylesheet" href="/pemay/public/css/components.css">
</head>

<body>
  <nav class="navbar navbar-expand-lg navbar-light navbar-container">
    <div class="container-fluid">
      <a class="navbar-brand" href="/pemay/pages/vet/dashboard.php">
        <img src="/pemay/public/img/icon.png" alt="" width="30" height="30" class="d-inline-block align-text-top">
        <span class="navbar-title">Pemay</span>
      </a>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav">
          <!-- Dashboard Menu Item -->
          <li class="nav-item">
            <a class="nav-link <?php echo ($currentPage === 'dashboard') ? 'active' : ''; ?>" href="/pemay/pages/vet/dashboard.php">Dashboard</a>
          </li>
          <!-- Pet Hotel Menu Item -->
          <li class="nav-item">
            <a class="nav-link <?php echo ($currentPage === 'pet-hotel') ? 'active' : ''; ?>" href="/pemay/pages/pet-hotel/dashboard.php">Pet Hotel</a>
          </li>
          <!-- Medicine Menu Item -->
          <li class="nav-item">
            <a class="nav-link <?php echo ($currentPage === 'medicine') ? 'active' : ''; ?>" href="/pemay/pages/medicine/medicine.php">Medicines</a>
          </li>
          <!-- Category Menu Item -->
          <li class="nav-item">
            <a class="nav-link <?php echo ($currentPage === 'category') ? 'active' : ''; ?>" href="/pemay/pages/category/category.php">Categories</a>
          </li>
        </ul>
      </div>

      <!-- Logout Button -->
      <form action="/pemay/auth/logout.php" method="post" class="d-flex">
        <button class="btn btn-link text-dark text-decoration-none" type="submit">Logout</button>
      </form>
    </div>
  </nav>

  <!-- Bootstrap JS (for functionality of navbar-toggler) -->
  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>

</html>