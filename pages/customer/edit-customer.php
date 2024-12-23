<?php
session_start();
ob_start();
require_once '../../config/database.php';
require_once '../../layout/header-tailwind.php';

$db = new Database();
$error_message = '';
$success_message = '';

// Ambil data customer berdasarkan ID
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $query = "SELECT * FROM PEMILIKHEWAN WHERE ID = :id";
    $db->query($query);
    $db->bind(':id', $id);
    $customer = $db->single();
}

// Proses update data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $id = $_POST['id'] ?? '';
    $nama = $_POST['nama'] ?? '';
    $email = $_POST['email'] ?? '';
    $nomortelpon = $_POST['nomortelpon'] ?? '';

    // Validasi input
    if (empty($nama) || empty($email) || empty($nomortelpon)) {
        $_SESSION['error_message'] = "Semua field harus diisi!";
    } else {
        try {
            // Update data
            $query = "UPDATE PEMILIKHEWAN SET NAMA = :nama, EMAIL = :email, NOMORTELPON = :nomortelpon WHERE ID = :id";
            $db->query($query);
            $db->bind(':id', $id);
            $db->bind(':nama', $nama);
            $db->bind(':email', $email);
            $db->bind(':nomortelpon', $nomortelpon);
            
            if ($db->execute()) {
                $_SESSION['success_message'] = "Data customer berhasil diperbarui!";
                echo "<script>window.location.href = 'customer.php';</script>";
                exit();
            } else {
                $_SESSION['error_message'] = "Gagal memperbarui data!";
            }
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Gagal memperbarui data: " . $e->getMessage();
        }
    }
}
?>

<!-- Form untuk edit customer -->
<div class="drawer drawer-end z-10">
  <input id="drawerEditCustomer" type="checkbox" class="drawer-toggle" />
  <div class="drawer-side">
    <label for="drawerEditCustomer" aria-label="close sidebar" class="drawer-overlay"></label>
    <form method="POST" class="h-full">
      <div class="menu bg-[#FCFCFC] text-[#363636] min-h-screen w-96 flex flex-col justify-center px-8">
        <div class="flex items-center gap-2 mb-7">
          <i class="fas fa-user-edit text-xl"></i>
          <h3 class="text-lg font-semibold">Edit Customer</h3>
        </div>

        <div class="gap-5 flex flex-col">
          <input type="hidden" name="id" value="<?php echo htmlspecialchars($customer['ID'] ?? ''); ?>">
          
          <div class="form-control">
            <label for="nama" class="label">
              <span class="label-text text-[#363636]">Name</span>
            </label>
            <input type="text" class="mt-1 w-full rounded-full bg-[#FCFCFC] border border-[#565656] text-[#565656] text-sm placeholder:text-[#565656] placeholder:text-sm px-7 py-2" 
              name="nama" id="nama" value="<?php echo htmlspecialchars($customer['NAMA'] ?? ''); ?>" required>
          </div>

          <div class="form-control">
            <label for="email" class="label">
              <span class="label-text text-[#363636]">Email</span>
            </label>
            <input type="email" class="mt-1 w-full rounded-full bg-[#FCFCFC] border border-[#565656] text-[#565656] text-sm placeholder:text-[#565656] placeholder:text-sm px-7 py-2" 
              name="email" id="email" value="<?php echo htmlspecialchars($customer['EMAIL'] ?? ''); ?>" required>
          </div>

          <div class="form-control">
            <label for="nomortelpon" class="label">
              <span class="label-text text-[#363636]">Phone Number</span>
            </label>
            <input type="tel" class="mt-1 w-full rounded-full bg-[#FCFCFC] border border-[#565656] text-[#565656] text-sm placeholder:text-[#565656] placeholder:text-sm px-7 py-2" 
              name="nomortelpon" id="nomortelpon" value="<?php echo htmlspecialchars($customer['NOMORTELPON'] ?? ''); ?>" required>
          </div>

          <div class="divider divider-neutral"></div>

          <div class="flex justify-end gap-3">
            <button type="submit" name="update" class="btn bg-[#B2B5E0] text-[#565656] shadow-md shadow-[#565656] px-5 rounded-full hover:bg-[#565656] hover:text-[#FCFCFC] flex items-center gap-2">
              <i class="fas fa-save"></i> Update Customer
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
    document.getElementById('drawerEditCustomer').checked = true;
  }
</script>
