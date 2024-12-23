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
    "/pemay/pages/pet-salon/salon-services.php",
    "/pemay/pages/pet-salon/add-salon-services.php",
    "/pemay/pages/pet-salon/update-salon-services.php"
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
$linkActive = 'underline-offset-[5px] underline decoration-[#565656] decoration-2';

$isManagementsActive = (
  in_array($currentUri, $usersUri) ||
  in_array($currentUri, $productUris) ||
  in_array($currentUri, $categoryUri) ||
  in_array($currentUri, $customerUri)
) ? $linkActive : '';

$isPetServicesActive = (
  in_array($currentUri, $petHotelUri) ||
  in_array($currentUri, $petSalonUri) ||
  in_array($currentUri, $petMedicalUri)
) ? $linkActive : '';
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title><?php echo isset($pageTitle) ? $pageTitle : 'Owner'; ?></title>
  <link rel="shortcut icon" href="/pemay/public/img/pemay-logo.png" type="image/x-icon">

  <!-- Daisy UI + Tailwind CSS -->
  <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.14/dist/full.min.css" rel="stylesheet" type="text/css" />
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Font Awesome Icon CSS -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

  <!-- Select2 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

  <!-- Custom CSS -->
  <link rel="stylesheet" href="/pemay/public/css/index.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="/pemay/public/css/components.css?v=<?php echo time(); ?>">

  <!-- Custom JS -->
  <script src="/pemay/public/js/drawer.js?v=<?php echo time(); ?>"></script>

  <!-- Include Select2 CSS and JS -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

  <script>
    $(document).ready(function() {
      $('.select2').select2();
    });
  </script>
</head>

<body class="min-h-screen">
  <nav class="bg-[#F9F9F9] py-6 px-24">
    <div class="flex gap-10 justify-between items-center w-100">
      <a class=" text-[#363636] bg-[#B2E0D6] py-2 px-5 h-full rounded-full font-extrabold text-lg tracking-wide" href="/pemay/pages/<?php echo $role; ?>/dashboard.php">
        <!-- <img src="/pemay/public/img/icon.png" alt="" width="30" height="30" class="d-inline-block align-text-top"> -->
        PEMAY
      </a>

      <div class="w-full flex justify-between border border-[#565656] rounded-full px-5">
        <div class="flex gap-5 items-center" id="navbarNav">
          <!-- Management Dropdown -->
          <div class="relative inline-block w-[140px]">
            <!-- Dropdown Toggle -->
            <label tabindex="0" class="btn btnManagement min-h-[2.375rem] h-[2.375rem] max-h-[2.375rem] border-none font-normal hover:bg-[#FCFCFC] py-2 px-2 w-full justify-between bg-transparent text-[#363636] text-xs <?php echo $isManagementsActive; ?> hover:font-semibold italic">
              <span id="selectedManagement">Managements</span>
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 inline float-right" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </label>

            <!-- Dropdown Menu -->
            <ul
              tabindex="0"
              class="menuManagement dropdown-content menu absolute z-10 mt-2 py-1 px-3 shadow bg-[#FCFCFC] border border-[#565656] text-[#565656] rounded-2xl w-full hidden">
              <?php if ($role === 'owner'): ?>
                <li>
                  <a href="/pemay/pages/owner/users.php" class="hover:bg-[#565656] hover:font-semibold hover:text-[#FCFCFC] text-[#363636] <?php echo (in_array($currentUri, $usersUri)) ? $linkActive : ''; ?> italic text-xs">Employees</a>
                </li>
              <?php endif; ?>
              <li>
                <a href="/pemay/pages/product/product.php" class="hover:bg-[#565656] hover:font-semibold hover:text-[#FCFCFC] text-[#363636] <?php echo (in_array($currentUri, $productUris)) ? $linkActive : ''; ?> italic text-xs">Products</a>
              </li>
              <li>
                <a href="/pemay/pages/category/category.php" class="hover:bg-[#565656] hover:font-semibold hover:text-[#FCFCFC] text-[#363636] <?php echo (in_array($currentUri, $categoryUri)) ? $linkActive : ''; ?> italic text-xs">Categories</a>
              </li>
              <li>
                <a href="/pemay/pages/category/category.php" class="hover:bg-[#565656] hover:font-semibold hover:text-[#FCFCFC] text-[#363636] <?php echo ($currentUri === $categoryUri) ? $linkActive : ''; ?> italic text-xs">Customers</a>
              </li>
            </ul>
          </div>

          <!-- Pet Services Dropdown -->
          <div class="relative inline-block w-[140px]">
            <!-- Dropdown Toggle -->
            <label tabindex="0" class="btn btnPetService min-h-[2.375rem] h-[2.375rem] max-h-[2.375rem] border-none font-normal hover:bg-[#FCFCFC] py-1 px-1 w-full justify-between bg-transparent text-[#363636] text-xs <?php echo $isPetServicesActive; ?> hover:font-semibold italic">
              <span id="selectedPetService">Pet Services</span>
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 inline float-right" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </label>

            <!-- Dropdown Menu -->
            <ul
              tabindex="0"
              class="menuPetService dropdown-content menu absolute z-10 mt-2 py-2 px-3 shadow bg-[#FCFCFC] border border-[#565656] text-[#565656] rounded-2xl w-full hidden">
              <li>
                <a href="/pemay/pages/pet-hotel/dashboard.php" class="hover:bg-[#565656] hover:font-semibold hover:text-[#FCFCFC] text-[#363636] <?php echo (in_array($currentUri, $petHotelUri)) ? $linkActive : ''; ?> italic text-xs">Pet Hotel</a>
              </li>
              <li>
                <a href="/pemay/pages/pet-medical/dashboard.php" class="hover:bg-[#565656] hover:font-semibold hover:text-[#FCFCFC] text-[#363636] <?php echo (in_array($currentUri, $petMedicalUri)) ? $linkActive : ''; ?> italic text-xs">Pet Medical</a>
              </li>
              <li>
                <a href="/pemay/pages/salon/salon-services.php" class="hover:bg-[#565656] hover:font-semibold hover:text-[#FCFCFC] text-[#363636] <?php echo (in_array($currentUri, $petSalonUri)) ? $linkActive : ''; ?> italic text-xs">Pet Salon</a>
              </li>
            </ul>
          </div>

          <!-- Report Menu Item -->
          <?php if ($role === 'owner'): ?>
            <a class="text-[#363636] font-normal text-xs py-1 px-3 <?php echo ($currentUri === $reportUri) ? $linkActive : ''; ?> hover:font-semibold italic" href="/pemay/pages/owner/reports.php">Reports</a>
          <?php endif; ?>
        </div>

        <!-- Logout Button -->
        <form action="/pemay/auth/logout.php" method="post" class="flex items-center">
          <button class="text-[#363636] font-normal text-xs py-1 px-3 decoration-none hover:font-semibold italic" type="submit">Logout</button>
        </form>
      </div>
    </div>
  </nav>

  <!-- Dropdown JS -->
  <script>
    const dropdownLabelManagement = document.querySelector('.btnManagement');
    const dropdownMenuManagement = document.querySelector('.menuManagement');
    const dropdownLabelPetService = document.querySelector('.btnPetService');
    const dropdownMenuPetService = document.querySelector('.menuPetService');

    dropdownLabelManagement.addEventListener('click', () => {
      dropdownMenuManagement.classList.toggle('hidden');
    });
    dropdownLabelPetService.addEventListener('click', () => {
      dropdownMenuPetService.classList.toggle('hidden');
    });

    window.addEventListener('click', (event) => {
      if (!dropdownLabelManagement.contains(event.target) && !dropdownMenuManagement.contains(event.target)) {
        dropdownMenuManagement.classList.add('hidden');
      }

      if (!dropdownLabelPetService.contains(event.target) && !dropdownMenuPetService.contains(event.target)) {
        dropdownMenuPetService.classList.add('hidden');
      }
    });
  </script>

  <!-- Select2 JS -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</body>

</html>