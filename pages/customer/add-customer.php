<?php
session_start();
ob_start();

// Cek jika form disubmit dan redirect ke customer.php
if (isset($_POST['add'])) {
require_once '../../config/database.php';
$db = new Database();

    $nama = $_POST['nama'] ?? '';
    $email = $_POST['email'] ?? '';
    $nomorTelpon = $_POST['nomortelpon'] ?? '';

    // Validasi input
    if (empty($nama) || empty($email) || empty($nomorTelpon)) {
        $_SESSION['error_message'] = 'Semua field harus diisi!';
        header("Location: add-customer.php");
        exit();
    }

    // Cek apakah email sudah ada
    $queryCheckEmail = "SELECT COUNT(*) AS COUNT_EMAIL FROM PEMILIKHEWAN WHERE EMAIL = :email";
    $db->query($queryCheckEmail);
    $db->bind(':email', $email);
    $result = $db->single();

    if ($result['COUNT_EMAIL'] > 0) {
        $_SESSION['error_message'] = 'Email sudah terdaftar!';
        header("Location: add-customer.php");
        exit();
    }

    try {
        // Generate UUID
        $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4));
        
        $queryInsert = "INSERT INTO PEMILIKHEWAN (ID, NAMA, EMAIL, NOMORTELPON) VALUES (:id, :nama, :email, :nomortelpon)";
        $db->query($queryInsert);
        $db->bind(':id', $uuid);
        $db->bind(':nama', $nama);
        $db->bind(':email', $email);
        $db->bind(':nomortelpon', $nomorTelpon);
        
        if ($db->execute()) {
            $_SESSION['success_message'] = 'Data pelanggan berhasil ditambahkan!';
            header("Location: customer.php");
            exit();
        } else {
            throw new Exception("Gagal menambahkan data");
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = 'Gagal menambahkan data: ' . $e->getMessage();
        header("Location: add-customer.php");
        exit();
    }
}

// Load header dan database untuk tampilan
require_once '../../config/database.php';
require_once '../../layout/header-tailwind.php';

$db = new Database();

// Ambil data customer untuk ditampilkan di tabel
$searchCustomer = isset($_GET['search_customer']) ? $_GET['search_customer'] : '';
$queryCustomer = "SELECT * FROM PEMILIKHEWAN WHERE LOWER(NAMA) LIKE LOWER(:search)";
$db->query($queryCustomer);
$db->bind(':search', '%' . $searchCustomer . '%');
$customers = $db->resultSet();
?>

<!DOCTYPE html>
<html lang="en">
<body>
  <!-- Main Content -->
  <div class="pb-6 px-12 text-[#363636]">
    <div class="flex justify-between mb-6">
      <h2 class="text-3xl font-bold">Manage Customers</h2>

      <!-- Alert Messages -->
      <?php if (isset($_SESSION['success_message'])): ?>
        <div role="alert" class="alert alert-success py-2 px-7 rounded-full w-fit">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0 stroke-current" fill="none" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span><?php echo htmlspecialchars($_SESSION['success_message']); ?></span>
        </div>
        <?php unset($_SESSION['success_message']); ?>
      <?php endif; ?>

      <?php if (isset($_SESSION['error_message'])): ?>
        <div role="alert" class="alert alert-error py-2 px-7 rounded-full w-fit">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0 stroke-current" fill="none" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span><?php echo htmlspecialchars($_SESSION['error_message']); ?></span>
        </div>
        <?php unset($_SESSION['error_message']); ?>
      <?php endif; ?>
    </div>

    <div class="bg-[#FCFCFC] border-base-300 rounded-box p-6">
      <div class="flex justify-between items-center">
        <p class="text-lg text-[#363636] font-semibold">Registered Customers</p>
        <form method="GET" action="" class="flex gap-2">
          <input type="text" name="search_customer" placeholder="Search customer..." value="<?php echo htmlspecialchars($searchCustomer); ?>" class="input input-bordered w-full max-w-xs rounded-full" />
          <button type="submit" class="btn btn-circle bg-[#D4F0EA] text-[#363636] hover:bg-[#363636] hover:text-[#D4F0EA] border border-[#363636]">
            <i class="fas fa-search"></i>
          </button>
        </form>
      </div>

      <div class="overflow-hidden border border-[#363636] rounded-xl shadow-md shadow-[#717171] mt-3">
        <table class="table border-collapse w-full">
          <thead>
            <tr class="bg-[#D4F0EA] text-[#363636] font-semibold">
              <th class="rounded-tl-xl">No.</th>
              <th>Name</th>
              <th>Email</th>
              <th>Phone Number</th>
              <th class="rounded-tr-xl text-center">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php $no = 1; foreach ($customers as $index => $row): ?>
              <tr class="text-[#363636]">
                <td class="<?= $index === count($customers) - 1 ? 'rounded-bl-xl' : '' ?>"><?php echo $no++; ?></td>
                <td><?php echo htmlspecialchars($row['NAMA']); ?></td>
                <td><?php echo htmlspecialchars($row['EMAIL']); ?></td>
                <td><?php echo htmlspecialchars($row['NOMORTELPON']); ?></td>
                <td class="<?= $index === count($customers) - 1 ? 'rounded-br-xl' : '' ?>">
                  <div class="flex gap-3 justify-center items-center">
                    <a href="edit-customer.php?id=<?php echo $row['ID']; ?>" class="btn btn-warning btn-sm">
                      <i class="fas fa-edit"></i>
                    </a>
                    <a href="delete-customer.php?id=<?php echo $row['ID']; ?>" class="btn btn-error btn-sm" onclick="return confirm('Yakin ingin menghapus pelanggan ini?')">
                      <i class="fas fa-trash-alt"></i>
                    </a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Add Customer Drawer -->
  <div class="drawer drawer-end z-10">
    <input id="drawerAddCustomer" type="checkbox" class="drawer-toggle" />
    <div class="drawer-content">
      <!-- Page content here -->
      <label for="drawerAddCustomer" class="drawer-button btn bg-[#D4F0EA] w-14 h-14 flex justify-center text-[#363636] hover:bg-[#363636] hover:text-[#D4F0EA] items-center rounded-full fixed bottom-5 right-5 border border-[#363636] shadow-md shadow-[#717171]">
        <i class="fas fa-plus fa-lg"></i>
      </label>
    </div>

    <div class="drawer-side">
      <label for="drawerAddCustomer" aria-label="close sidebar" class="drawer-overlay"></label>
      <form method="POST" class="h-full">
        <div class="menu bg-[#FCFCFC] text-[#363636] min-h-screen w-96 flex flex-col justify-center px-8">
          <div class="flex items-center gap-2 mb-7">
            <i class="fas fa-user-plus text-xl"></i>
            <h3 class="text-lg font-semibold">Add New Customer</h3>
          </div>

          <div class="gap-5 flex flex-col">
            <div class="form-control">
              <label for="nama" class="label">
                <span class="label-text text-[#363636]">Name</span>
              </label>
              <input type="text" class="mt-1 w-full rounded-full bg-[#FCFCFC] border border-[#565656] text-[#565656] text-sm placeholder:text-[#565656] placeholder:text-sm px-7 py-2" 
                name="nama" id="nama" placeholder="Enter customer name" required>
            </div>

            <div class="form-control">
              <label for="email" class="label">
                <span class="label-text text-[#363636]">Email</span>
              </label>
              <input type="email" class="mt-1 w-full rounded-full bg-[#FCFCFC] border border-[#565656] text-[#565656] text-sm placeholder:text-[#565656] placeholder:text-sm px-7 py-2" 
                name="email" id="email" placeholder="Enter customer email" required>
            </div>

            <div class="form-control">
              <label for="nomortelpon" class="label">
                <span class="label-text text-[#363636]">Phone Number</span>
              </label>
              <input type="tel" class="mt-1 w-full rounded-full bg-[#FCFCFC] border border-[#565656] text-[#565656] text-sm placeholder:text-[#565656] placeholder:text-sm px-7 py-2" 
                name="nomortelpon" id="nomortelpon" placeholder="Enter customer phone number" required>
            </div>

            <div class="divider divider-neutral"></div>

            <div class="flex justify-end gap-3">
              <button type="submit" name="add" class="btn bg-[#B2B5E0] text-[#565656] shadow-md shadow-[#565656] px-5 rounded-full hover:bg-[#565656] hover:text-[#FCFCFC] flex items-center gap-2">
                <i class="fas fa-save"></i> Save Customer
              </button>
              <a href="customer.php" class="btn bg-[#E0BAB2] text-[#565656] shadow-md shadow-[#565656] px-5 rounded-full hover:bg-[#565656] hover:text-[#FCFCFC] flex items-center gap-2">
                <i class="fas fa-times"></i> Cancel
              </a>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>

  <script>
    // Auto open drawer when page loads
    window.onload = function() {
      document.getElementById('drawerAddCustomer').checked = true;
    }
  </script>
</body>
</html>
