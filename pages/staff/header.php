<?php
$currentUri = strtok($_SERVER['REQUEST_URI'], '?');

// List of uri
$dashboardUri = "/pemay/pages/staff/dashboard.php";
$petHotelUri = "/pemay/pages/pet-hotel/dashboard.php";
$productUri = "/pemay/pages/product/product.php";
$categoryUri = "/pemay/pages/category/category.php";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'Staff'; ?></title>
    <link rel="shortcut icon" href="../../public/img/icon.png" type="image/x-icon">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

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
            <a class="navbar-brand" href="#">
                <img src="../../public/img/icon.png" alt="" width="30" height="30" class="d-inline-block align-text-top">
                <span class="navbar-title">Pemay</span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentUri === $dashboardUri) ? 'active' : ''; ?>" href="/pemay/pages/staff/dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentUri === $petHotelUri) ? 'active' : ''; ?>" href="/pemay/pages/pet-hotel/dashboard.php">Pet Hotel</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentUri === $productUri) ? 'active' : ''; ?>" href="/pemay/pages/product/product.php">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentUri === $categoryUri) ? 'active' : ''; ?>" href="/pemay/pages/category/category.php">Categories</a>
                    </li>
                </ul>
            </div>

            <form action="../../auth/logout.php" method="post">
                <button class="btn btn-link text-dark text-decoration-none" type="submit">Logout</button>
            </form>
        </div>
    </nav>

    <!-- Bootstrap JS (for functionality of navbar-toggler) -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <!-- Select2 JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</body>

</html>