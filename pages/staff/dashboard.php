<?php
session_start();
ob_start();
require_once '../../config/connection.php';
require_once '../../config/database.php';

$pageTitle = 'Staff Dashboard';
include '../../layout/header-tailwind.php';


if (!isset($_SESSION['username']) || $_SESSION['posisi'] != 'staff') {
  header("Location: ../../auth/restricted.php");
  exit();
} // Include the header file for navigation

$db = new Database();
$db->query("SELECT COUNT(*) FROM LayananSalon WHERE STATUS = 'Waiting'");
$salonWaitingCount = $db->single()['COUNT(*)'];

$db->query("SELECT COUNT(*) FROM LayananMedis WHERE STATUS = 'Finished'");
$medicalFinishedCount = $db->single()['COUNT(*)'];

$db->query("SELECT COUNT(*) FROM KategoriProduk");
$productCategoryCount = $db->single()['COUNT(*)'];

$db->query("SELECT COUNT(*) FROM KategoriObat");
$medicineCategoryCount = $db->single()['COUNT(*)'];

$db->query("SELECT COUNT(*) FROM Kandang WHERE STATUS = 'Empty'");
$cageEmptyCount = $db->single()['COUNT(*)'];

$db->query("SELECT COUNT(*) FROM Kandang WHERE STATUS = 'Filled'");
$cageFilledCount = $db->single()['COUNT(*)'];

$db->query("SELECT COUNT(*) FROM Kandang WHERE STATUS = 'Scheduled'");
$cageScheduledCount = $db->single()['COUNT(*)'];

$db->query("SELECT COUNT(*) FROM LayananMedis WHERE STATUS = 'Cancelled'");
$medicalCancelledCount = $db->single()['COUNT(*)'];
?>

<!DOCTYPE html>
<html lang="en">

<body data-theme="light">
<div class="min-h-screen bg-white text-base-content p-24">
    <!-- Header -->
    <div class="flex justify-between items-center mb-10">
  <h1 class="text-2xl font-bold text-gray-500">Welcome to the dashboard, staff</h1>
  <div class="flex items-center space-x-4">
    <!-- Form with horizontal alignment -->
    <form method="POST" class="flex items-center space-x-2">
      <div class="flex items-center space-x-2">
        <label for="date" class="text-sm text-gray-500">Pilih Tanggal:</label>
        <input
          type="date"
          id="date"
          name="date"
          class="input input-bordered w-full max-w-xs"
          value="<?php echo date('Y-m-d'); ?>"
        />
      </div>
      <button
        type="submit"
        class="btn btn-primary"
      >
        Tampilkan Data
      </button>
    </form>
    <!-- Right section -->
    <div class="text-sm text-right text-gray-500">
      <div><?php echo date('l'); ?></div>
      <div><?php echo date('F Y'); ?></div>
    </div>
  </div>
</div>

  <!-- Main Grid Layout -->
  <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
    <!-- Salon Clients -->
    <div class="card shadow-lg bg-yellow-100 border border-zinc-400 rounded-3xl">
      <div class="card-body">
        <div class="flex justify-between items-center">
          <h2 class="card-title text-base text-gray-500 font-semibold">Salon Clients</h2>
          <a href="../salon/salon-services.php" class="text-sm text-gray-700" data-toggle="modal">⤴</a>
        </div>
        <p class="text-sm">Waiting</p>
        <p class="card-title text-5xl font-bold mt-2 text-gray-500"><?= $salonWaitingCount ?></p>
      </div>
    </div>

    <!-- Medical Clients -->
    <div class="card shadow-lg bg-teal-100 border border-zinc-400 rounded-3xl">
      <div class="card-body">
        <div class="flex justify-between items-center">
          <h2 class="card-title text-base text-gray-500 font-semibold">Medical Clients</h2>
          <a href="../pet-medical/dashboard.php" class="text-sm text-gray-700">⤴</a>
        </div>
        <p class="text-sm">Finished</p>
        <p class="card-title text-5xl font-bold mt-2 text-gray-500"><?= $medicalFinishedCount ?></p>
      </div>
    </div>
  </div>

  <!-- Stocks & Reservations Section -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 mt-2">
    <!-- Available Products -->
    <div class="card shadow-lg bg-white border border-zinc-400 rounded-3xl">
      <div class="card-body">
        <div class="flex justify-between items-center">
          <h3 class="card-title text-sm text-gray-500 font-semibold">Available Products</h3>
          <a href="#" class="text-sm text-gray-700" data-toggle="modal" data-target="#productModal">⤴</a>
        </div>
        <div class="flex justify-between mt-4">
          <span>Medicine</span>
          <span class="font-bold text-gray-500"><?= $medicineCategoryCount ?></span>
        </div>
        <div class="flex justify-between">
          <span>Products</span>
          <span class="font-bold text-gray-500"><?= $productCategoryCount ?></span>
        </div>
      </div>
    </div>

    <!-- Reservations -->
    <div class="card shadow-lg bg-gray-50 border border-zinc-400 rounded-3xl">
      <div class="card-body">
        <div class="flex justify-between items-center">
          <h3 class="card-title text-sm font-semibold text-gray-500">Cages</h3>
          <a href="#" class="text-sm text-gray-700" data-toggle="modal" data-target="#hotelModal">⤴</a>
        </div>
        <div class="flex justify-between mt-4">
          <span>Scheduled</span>
          <span class="font-bold text-yellow-600"><?= $cageScheduledCount ?></span>
        </div>
        <div class="flex justify-between">
          <span>Filled</span>
          <span class="font-bold text-indigo-600"><?= $cageFilledCount ?></span>
        </div>
        <div class="flex justify-between">
          <span>Empty</span>
          <span class="font-bold text-gray-600"><?= $cageEmptyCount ?></span>
        </div>
      </div>
    </div>
    
    <!-- Cancelled Appointments -->
    <div class="card shadow-lg bg-gray-100 border border-zinc-400 rounded-3xl">
      <div class="card-body">
        <div class="flex justify-between items-center">
          <h2 class="card-title text-base font-semibold">Cancelled Appointments</h2>
          <a href="#" class="text-sm text-gray-700" data-toggle="modal" data-target="#cancelledModal">⤴</a>
        </div>
        <div class="flex justify-between">
          <span>Medical services</span>
          <span class="font-bold"><?= $medicalCancelledCount ?></span>
        </div>
      </div>
    </div>

  </div>
</div>

</body>

</html>