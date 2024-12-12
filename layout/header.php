<?php
$currentUri = strtok($_SERVER['REQUEST_URI'], '?');

// dashboard uri
$ownerDashboardUri = "/pemay/pages/owner/dashboard.php";
$vetDashboardUri = "/pemay/pages/vet/dashboard.php";
$staffDashboardUri = "/pemay/pages/staff/dashboard.php";

// pet hotel uri
$petHotelUri =
  [
    "/pemay/pages/pet-hotel/dashboard.php",
    "/pemay/pages/pet-hotel/update-cage.php",
    "/pemay/pages/pet-hotel/update-reservation.php"
  ];

// pet salon uri
$petSalonUri =
  [
    "/pemay/pages/salon/salon-services.php",
    "/pemay/pages/salon/add-salon-services.php",
    "/pemay/pages/salon/update-salon-services.php"
  ];
// pet hotel uri
$petMedicalUri =
  [
    "/pemay/pages/pet-medical/dashboard.php",
    "/pemay/pages/pet-medical/add-medical-services.php",
    "/pemay/pages/pet-medical/update-medical-services.php"
  ];

// manage users uri
$usersUri = [
  "/pemay/pages/owner/users.php",
  "/pemay/pages/owner/add-user.php",
  "/pemay/pages/owner/update-user.php"
];

// manage product uri
$productUris = [
  "/pemay/pages/product/product.php",
  "/pemay/pages/product/add-product.php",
  "/pemay/pages/product/update-product.php"
];

// manage category uri
$categoryUri = [
  "/pemay/pages/category/category.php",
  "/pemay/pages/category/update-category.php"
];

// manage customer uri
$customerUri = ["/pemay/pages/category/category.php"];

// reports uri
$reportUri = "/pemay/pages/owner/reports.php";

$role = $_SESSION['posisi'];

$isManagementsActive = (
  in_array($currentUri, $usersUri) ||
  in_array($currentUri, $productUris) ||
  in_array($currentUri, $categoryUri) ||
  in_array($currentUri, $customerUri)
) ? 'active' : '';

$isPetServicesActive = (
  in_array($currentUri, $petHotelUri) ||
  in_array($currentUri, $petSalonUri) ||
  in_array($currentUri, $petMedicalUri)
) ? 'active' : '';
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title><?php echo isset($pageTitle) ? $pageTitle : 'Owner'; ?></title>
  <link rel="shortcut icon" href="/pemay/public/img/icon.png" type="image/x-icon">

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Daisy UI + Tailwind CSS -->
  <!-- <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.14/dist/full.min.css" rel="stylesheet" type="text/css" />
  <script src="https://cdn.tailwindcss.com"></script> -->

  <!-- Font Awesome Icon CSS -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

  <!-- Select2 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

  <!-- Custom CSS -->
  <link rel="stylesheet" href="/pemay/public/css/index.css">
  <link rel="stylesheet" href="/pemay/public/css/components.css">
</head>

<body>
  <nav class="navbar navbar-expand-lg navbar-light navbar-container">
    <div class="container-fluid">
      <a class="navbar-brand" href="/pemay/pages/<?php echo $role; ?>/dashboard.php">
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
            <a class="nav-link <?php echo ($currentUri === $dashboardUri) ? 'active' : ''; ?>" href="/pemay/pages/<?php echo $role; ?>/dashboard.php"">Dashboard</a>
          </li>

          <!-- Management Dropdown -->
          <li class=" nav-item dropdown">
              <a class="nav-link dropdown-toggle <?php echo $isManagementsActive; ?>" href="#" id="dropdownManagements" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                Managements
              </a>
              <ul class="dropdown-menu" aria-labelledby="dropdownManagements">
                <!-- Users Menu Item -->
                <?php if ($role === 'owner'): ?>
                  <a class="dropdown-item <?php echo (in_array($currentUri, $usersUri)) ? 'active' : ''; ?>" href="/pemay/pages/owner/users.php">Users</a>
                <?php endif; ?>

                <!-- Product Menu Item -->
                <li><a class="dropdown-item <?php echo (in_array($currentUri, $productUris)) ? 'active' : ''; ?>" href="/pemay/pages/product/product.php">Products</a></li>

                <!-- Categories Menu Item -->
                <li><a class="dropdown-item <?php echo (in_array($currentUri, $categoryUri)) ? 'active' : ''; ?>" href="/pemay/pages/category/category.php">Categories</a></li>

                <!-- Customers Menu Item -->
                <li><a class="dropdown-item <?php echo ($currentUri === $categoryUri) ? 'active' : ''; ?>" href="/pemay/pages/category/category.php">Customers</a></li>
              </ul>
          </li>

          <!-- Pet Services Dropdown -->
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle <?php echo $isPetServicesActive; ?>" href="#" id="dropdownPetServices" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              Pet Services
            </a>
            <ul class="dropdown-menu" aria-labelledby="dropdownPetServices">
              <!-- Pet Hotel Menu Item -->
              <li>
                <a class="dropdown-item <?php echo (in_array($currentUri, $petHotelUri)) ? 'active' : ''; ?>" href="/pemay/pages/pet-hotel/dashboard.php">Pet Hotel</a>
              </li>

              <!-- Pet Salon Menu Item -->
              <li>
                <a class="dropdown-item <?php echo (in_array($currentUri, $petSalonUri)) ? 'active' : ''; ?>" href="/pemay/pages/salon/salon-services.php">Pet Salon</a>
              </li>

              <!-- Pet Medical Menu Item -->
              <li>
                <a class="dropdown-item <?php echo (in_array($currentUri, $petSalonUri)) ? 'active' : ''; ?>" href="/pemay/pages/pet-medical/dashboard.php">Pet Medical</a>
              </li>
            </ul>
          </li>

          <!-- Report Menu Item -->
          <?php if ($role === 'owner'): ?>
            <li class="nav-item">
              <a class="nav-link <?php echo ($currentUri === $reportUri) ? 'active' : ''; ?>" href="/pemay/pages/owner/reports.php">Reports</a>

            </li>
          <?php endif; ?>
        </ul>
      </div>

      <!-- Logout Button -->
      <form action="/pemay/auth/logout.php" method="post" class="d-flex">
        <button class="btn btn-link text-dark text-decoration-none" type="submit">Logout</button>
      </form>
    </div>
  </nav>

  <!-- Bootstrap JS (for functionality of navbar-toggler) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>

  <!-- Select2 JS -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</body>

</html>