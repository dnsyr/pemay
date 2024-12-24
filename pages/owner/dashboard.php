<?php
session_start();
include '../../config/connection.php';
include '../../config/database.php';

$pageTitle = 'owner Dashboard';
include '../../layout/header-tailwind.php';

if (!isset($_SESSION['username']) || $_SESSION['posisi'] != 'owner') {
  header("Location: ../../auth/restricted.php");
  exit();
}

?>

<!DOCTYPE html>
<html lang="en">

<body>
  <div class="border border-0 border-t-2 pt-10 pb-20 px-16">
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

  </div>
  
  <div class="min-h-screen bg-white text-base-content p-24">
  <!-- Header -->
  <div class="flex justify-between items-center mb-10">
  <h1 class="text-2xl font-bold text-gray-500">Welcome to the dashboard, Manager</h1>
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
          value="<?php echo htmlspecialchars($selectedDate); ?>"
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
      <div>Monday</div>
      <div>December 2024</div>
    </div>
  </div>
</div>

<!-- Grid Layout -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4">
    <!-- Sales Distribution Card -->
    <div class="card bg-white shadow-lg p-6 rounded-3xl border border-gray-800 md:col-span-3">
      <h2 class="text-lg font-semibold mb-4 text-gray-500">Sales Distribution</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
        <!-- Inner Card Example -->
        <div class="card bg-white p-4 rounded-2xl border border-gray-500">
          <div class="card-body">
            <h1 class="text-2xl font-semibold text-gray-800">Rp. 2.000.000</h1>
            <span class="text-gray-500">Total Sales</span>
          </div>
        </div>

        <div class="card bg-gray-50 rounded-xl border border-gray-200">
      <div class="card-body">
        <div class="flex justify-between items-center">
          <h3 class="card-title text-sm font-semibold text-gray-500">Employees</h3>
          <a href="#" class="text-sm text-gray-700" data-toggle="modal" data-target="#hotelModal">⤴</a>
        </div>
        <div class="flex justify-between mt-4">
          <span>Pet Stays</span>
          <span class="font-bold text-gray-600">Rp. 500.000</span>
        </div>
        <div class="flex justify-between mt-4">
          <span>Pet Stays</span>
          <span class="font-bold text-gray-600">Rp. 500.000</span>
        </div>
        <div class="flex justify-between mt-4">
          <span>Pet Stays</span>
          <span class="font-bold text-gray-600">Rp. 500.000</span>
        </div>
        <div class="flex justify-between mt-4">
          <span>Pet Stays</span>
          <span class="font-bold text-gray-600">Rp. 500.000</span>
        </div>
        <div class="flex justify-between mt-4">
          <span>Pet Stays</span>
          <span class="font-bold text-gray-600">Rp. 500.000</span>
        </div>
      </div>
    </div>

      </div>
    </div>

    <div class="card bg-gray-50 shadow-md rounded-3xl border border-gray-200">
      <div class="card-body">
        <div class="flex justify-between items-center">
          <h3 class="card-title text-sm text-gray-500 font-semibold">Available Products</h3>
          <a href="#" class="text-sm text-gray-700" data-toggle="modal" data-target="#productModal">⤴</a>
        </div>
        <div class="flex justify-between mt-4">
          <span>Staffs</span>
          <span class="font-bold text-gray-500">10</span>
        </div>
        <div class="flex justify-between">
          <span>Vetss</span>
          <span class="font-bold text-gray-500">5</span>
        </div>
      </div>
    </div>
</div>

<!--grid layout again-->
<div class ="grid grid-cols-1 md:grid-cols-5 gap-2 mt-2">
    <!-- Products & Cages -->
    <div class="card bg-white shadow-lg p-6 rounded-3xl border border-gray-500 md:col-span-3">
      <h2 class="text-lg font-semibold mb-4 text-gray-500">Products & Cages</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- First Inner Card -->
        <div class="card bg-white shadow-md rounded-2xl border border-gray-500">
          <div class="card-body p-4">
            <div class="flex justify-between items-center">
              <h3 class="text-sm font-semibold text-gray-500">Products Overview</h3>
              <a href="#" class="text-sm text-gray-500">⤴</a>
            </div>
            <div class="mt-4">
              <div class="flex justify-between border-b py-2">
                <span class="text-gray-500">Medicine</span>
                <span class="font-bold text-gray-800">10</span>
              </div>
              <div class="flex justify-between border-b py-2">
                <span class="text-gray-500">Products</span>
                <span class="font-bold text-gray-800">10</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Second Inner Card -->
        <div class="card bg-gray-50 shadow-md rounded-xl border border-gray-200">
          <div class="card-body p-4">
            <div class="flex justify-between items-center">
              <h3 class="text-sm font-semibold text-gray-500">Reservations</h3>
              <a href="#" class="text-sm text-gray-500">⤴</a>
            </div>
            <div class="mt-4">
              <div class="flex justify-between border-b py-2">
                <span class="text-gray-500">Scheduled</span>
                <span class="font-bold text-yellow-600">10</span>
              </div>
              <div class="flex justify-between border-b py-2">
                <span class="text-gray-500">Filled</span>
                <span class="font-bold text-indigo-600">10</span>
              </div>
              <div class="flex justify-between border-b py-2">
                <span class="text-gray-500">Empty</span>
                <span class="font-bold text-gray-600">10</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Available Services -->
    <div class="card bg-gray-50 shadow-md rounded-3xl border border-gray-200">
      <div class="card-body">
        <div class="flex justify-between items-center">
          <h3 class="card-title text-sm text-gray-500 font-semibold">Available Products</h3>
          <a href="#" class="text-sm text-gray-700" data-toggle="modal" data-target="#productModal">⤴</a>
        </div>
        <div class="flex justify-between mt-4">
          <span>Medicine</span>
          <span class="font-bold text-gray-500">10</span>
        </div>
        <div class="flex justify-between">
          <span>Products</span>
          <span class="font-bold text-gray-500">10</span>
        </div>
      </div>
    </div>

    <div class="card bg-gray-50 shadow-md rounded-3xl border border-gray-200">
      <div class="card-body">
        <div class="flex justify-between items-center">
          <h3 class="card-title text-sm text-gray-500 font-semibold">Available Products</h3>
          <a href="#" class="text-sm text-gray-700" data-toggle="modal" data-target="#productModal">⤴</a>
        </div>
        <div class="flex justify-between mt-4">
          <span>Medicine</span>
          <span class="font-bold text-gray-500">10</span>
        </div>
        <div class="flex justify-between">
          <span>Products</span>
          <span class="font-bold text-gray-500">10</span>
        </div>
      </div>
    </div>

      
    </div>
  </div>
</div>
</body>

</html>