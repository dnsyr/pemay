<?php
session_start();
require_once '../../config/database.php';
require_once '../../layout/header-tailwind.php';

$db = new Database();

// Pencarian pelanggan
$searchCustomer = isset($_GET['search_customer']) ? $_GET['search_customer'] : '';
$queryCustomer = "SELECT * FROM PEMILIKHEWAN WHERE LOWER(NAMA) LIKE LOWER(:search)";
$db->query($queryCustomer);
$db->bind(':search', '%' . $searchCustomer . '%');
$customers = $db->resultSet();

// Pencarian hewan
$searchPet = isset($_GET['search_pet']) ? $_GET['search_pet'] : '';
$queryPet = "SELECT * FROM HEWAN WHERE LOWER(NAMA) LIKE LOWER(:search) AND (ONDELETE = 0 OR ONDELETE IS NULL)";
$db->query($queryPet);
$db->bind(':search', '%' . $searchPet . '%');
$pets = $db->resultSet();

// Pagination untuk tabel customer
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 6;
$offset = ($page - 1) * $limit;

// Query untuk menghitung total data customer
$countQuery = "SELECT COUNT(*) as TOTAL FROM PEMILIKHEWAN WHERE LOWER(NAMA) LIKE LOWER(:search)";
$db->query($countQuery);
$db->bind(':search', '%' . $searchCustomer . '%');
$totalResult = $db->single();
$totalData = $totalResult['TOTAL'];
$totalPages = ceil($totalData / $limit);

// Modifikasi query customer untuk pagination
$queryCustomer = "SELECT * FROM (
                    SELECT a.*, ROWNUM rnum FROM (
                        SELECT * FROM PEMILIKHEWAN 
                        WHERE LOWER(NAMA) LIKE LOWER(:search)
                        ORDER BY ID
                    ) a WHERE ROWNUM <= :end_row
                ) WHERE rnum > :start_row";

$db->query($queryCustomer);
$db->bind(':search', '%' . $searchCustomer . '%');
$db->bind(':start_row', $offset);
$db->bind(':end_row', $offset + $limit);
$customers = $db->resultSet();

// Pagination untuk tabel hewan
$petPage = isset($_GET['pet_page']) ? (int)$_GET['pet_page'] : 1;
$petOffset = ($petPage - 1) * $limit;

// Query untuk menghitung total data hewan
$countPetQuery = "SELECT COUNT(*) as TOTAL FROM HEWAN h 
                  LEFT JOIN PEMILIKHEWAN p ON h.PEMILIKHEWAN_ID = p.ID 
                  WHERE LOWER(h.NAMA) LIKE LOWER(:search) AND (h.ONDELETE = 0 OR h.ONDELETE IS NULL)";
$db->query($countPetQuery);
$db->bind(':search', '%' . $searchPet . '%');
$totalPetResult = $db->single();
$totalPetData = $totalPetResult['TOTAL'];
$totalPetPages = ceil($totalPetData / $limit);

// Modifikasi query hewan untuk pagination
$queryPet = "SELECT * FROM (
                SELECT a.*, ROWNUM rnum FROM (
                    SELECT h.ID, h.NAMA, p.NAMA as NAMA_PEMILIK, h.SPESIES, h.RAS, h.GENDER, 
                    h.BERAT, TO_CHAR(h.TANGGALLAHIR, 'DD-MON-YY') as TANGGALLAHIR, 
                    h.TINGGI, h.LEBAR 
                    FROM HEWAN h 
                    LEFT JOIN PEMILIKHEWAN p ON h.PEMILIKHEWAN_ID = p.ID 
                    WHERE LOWER(h.NAMA) LIKE LOWER(:search) AND (h.ONDELETE = 0 OR h.ONDELETE IS NULL)
                    ORDER BY h.ID
                ) a WHERE ROWNUM <= :end_row
            ) WHERE rnum > :start_row";

$db->query($queryPet);
$db->bind(':search', '%' . $searchPet . '%');
$db->bind(':start_row', $petOffset);
$db->bind(':end_row', $petOffset + $limit);
$pets = $db->resultSet();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer and Pet Information</title>
</head>
<body>
  <div class="pb-6 px-12 text-[#363636]">
    <div class="flex justify-between mb-6">
      <h2 class="text-3xl font-bold">Manage Customers & Pets</h2>

      <!-- Alert -->
      <?php if (isset($_SESSION['success_message']) && $_SESSION['success_message'] !== ""): ?>
        <div role="alert" class="alert alert-success py-2 px-7 rounded-full w-fit">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0 stroke-current" fill="none" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span><?php echo htmlentities($_SESSION['success_message']); unset($_SESSION['success_message']); ?></span>
        </div>
      <?php elseif (isset($_SESSION['error_message']) && $_SESSION['error_message'] !== ""): ?>
        <div role="alert" class="alert alert-error py-2 px-7 rounded-full w-fit">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0 stroke-current" fill="none" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span><?php echo htmlentities($_SESSION['error_message']); unset($_SESSION['error_message']); ?></span>
        </div>
      <?php endif; ?>
    </div>

    <div role="tablist" class="tabs tabs-lifted relative z-0">
      <input type="radio" name="my_tabs_2" role="tab" checked class="tab text-[#363636] text-base font-semibold [--tab-bg:#D4F0EA] [--tab-border-color:#363636]" aria-label="Customers" />
      <div role="tabpanel" class="tab-content bg-[#FCFCFC] border-base-300 rounded-box p-6">
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
                      <button onclick="showDeleteCustomerModal('<?php echo $row['ID']; ?>')" class="btn btn-error btn-sm">
                        <i class="fas fa-trash-alt"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Pagination untuk Customer -->
        <div class="flex justify-center mt-4 gap-2">
          <?php if($page > 1): ?>
            <a href="?page=<?php echo $page-1; ?>&search_customer=<?php echo urlencode($searchCustomer); ?>&pet_page=<?php echo $petPage; ?>&search_pet=<?php echo urlencode($searchPet); ?>" 
               class="btn btn-sm bg-[#D4F0EA] text-[#363636] hover:bg-[#363636] hover:text-[#D4F0EA] border border-[#363636]">
              <i class="fas fa-chevron-left"></i>
            </a>
          <?php endif; ?>

          <?php for($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?php echo $i; ?>&search_customer=<?php echo urlencode($searchCustomer); ?>&pet_page=<?php echo $petPage; ?>&search_pet=<?php echo urlencode($searchPet); ?>" 
               class="btn btn-sm <?php echo $i === $page ? 'bg-[#363636] text-[#D4F0EA]' : 'bg-[#D4F0EA] text-[#363636] hover:bg-[#363636] hover:text-[#D4F0EA]'; ?> border border-[#363636]">
              <?php echo $i; ?>
            </a>
          <?php endfor; ?>

          <?php if($page < $totalPages): ?>
            <a href="?page=<?php echo $page+1; ?>&search_customer=<?php echo urlencode($searchCustomer); ?>&pet_page=<?php echo $petPage; ?>&search_pet=<?php echo urlencode($searchPet); ?>" 
               class="btn btn-sm bg-[#D4F0EA] text-[#363636] hover:bg-[#363636] hover:text-[#D4F0EA] border border-[#363636]">
              <i class="fas fa-chevron-right"></i>
            </a>
          <?php endif; ?>
        </div>
      </div>

      <input type="radio" name="my_tabs_2" role="tab" class="tab text-[#363636] text-base font-semibold [--tab-bg:#D4F0EA] [--tab-border-color:#363636]" aria-label="Pets" />
      <div role="tabpanel" class="tab-content bg-[#FCFCFC] border-base-300 rounded-box p-6">
        <div class="flex justify-between items-center">
          <p class="text-lg text-[#363636] font-semibold">Registered Pets</p>
          <form method="GET" action="" class="flex gap-2">
            <input type="text" name="search_pet" placeholder="Search pet..." value="<?php echo htmlspecialchars($searchPet); ?>" class="input input-bordered w-full max-w-xs rounded-full" />
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
                <th>Species</th>
                <th>Race</th>
                <th>Gender</th>
                <th>Weight</th>
                <th>Birth Date</th>
                <th>Height</th>
                <th>Width</th>
                <th class="rounded-tr-xl text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php $no = 1; foreach ($pets as $index => $row): ?>
                <tr class="text-[#363636]">
                  <td class="<?= $index === count($pets) - 1 ? 'rounded-bl-xl' : '' ?>"><?php echo $no++; ?></td>
                  <td><?php echo htmlspecialchars($row['NAMA']); ?></td>
                  <td><?php echo htmlspecialchars($row['SPESIES']); ?></td>
                  <td><?php echo htmlspecialchars($row['RAS']); ?></td>
                  <td><?php echo htmlspecialchars($row['GENDER']); ?></td>
                  <td><?php echo htmlspecialchars($row['BERAT']); ?> kg</td>
                  <td><?php echo htmlspecialchars($row['TANGGALLAHIR']); ?></td>
                  <td><?php echo htmlspecialchars($row['TINGGI']); ?> cm</td>
                  <td><?php echo htmlspecialchars($row['LEBAR']); ?> cm</td>
                  <td class="<?= $index === count($pets) - 1 ? 'rounded-br-xl' : '' ?>">
                    <div class="flex gap-3 justify-center items-center">
                      <a href="edit-pet.php?id=<?php echo $row['ID']; ?>" class="btn btn-warning btn-sm">
                        <i class="fas fa-edit"></i>
                      </a>
                      <button onclick="showDeletePetModal('<?php echo $row['ID']; ?>')" class="btn btn-error btn-sm">
                        <i class="fas fa-trash-alt"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Pagination untuk Pet -->
        <div class="flex justify-center mt-4 gap-2">
          <?php if($petPage > 1): ?>
            <a href="?page=<?php echo $page; ?>&search_customer=<?php echo urlencode($searchCustomer); ?>&pet_page=<?php echo $petPage-1; ?>&search_pet=<?php echo urlencode($searchPet); ?>" 
               class="btn btn-sm bg-[#D4F0EA] text-[#363636] hover:bg-[#363636] hover:text-[#D4F0EA] border border-[#363636]">
              <i class="fas fa-chevron-left"></i>
            </a>
          <?php endif; ?>

          <?php for($i = 1; $i <= $totalPetPages; $i++): ?>
            <a href="?page=<?php echo $page; ?>&search_customer=<?php echo urlencode($searchCustomer); ?>&pet_page=<?php echo $i; ?>&search_pet=<?php echo urlencode($searchPet); ?>" 
               class="btn btn-sm <?php echo $i === $petPage ? 'bg-[#363636] text-[#D4F0EA]' : 'bg-[#D4F0EA] text-[#363636] hover:bg-[#363636] hover:text-[#D4F0EA]'; ?> border border-[#363636]">
              <?php echo $i; ?>
            </a>
          <?php endfor; ?>

          <?php if($petPage < $totalPetPages): ?>
            <a href="?page=<?php echo $page; ?>&search_customer=<?php echo urlencode($searchCustomer); ?>&pet_page=<?php echo $petPage+1; ?>&search_pet=<?php echo urlencode($searchPet); ?>" 
               class="btn btn-sm bg-[#D4F0EA] text-[#363636] hover:bg-[#363636] hover:text-[#D4F0EA] border border-[#363636]">
              <i class="fas fa-chevron-right"></i>
            </a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Add Customer Button -->
  <div class="drawer drawer-end z-10">
    <input id="drawerAddCustomer" type="checkbox" class="drawer-toggle" />
    <div class="drawer-content">
      <a href="add-customer.php" class="btn bg-[#D4F0EA] w-14 h-14 flex justify-center text-[#363636] hover:bg-[#363636] hover:text-[#D4F0EA] items-center rounded-full fixed bottom-5 right-5 border border-[#363636] shadow-md shadow-[#717171]">
        <i class="fas fa-plus fa-lg"></i>
      </a>
    </div>
  </div>

  <!-- Add Pet Button -->
  <div class="drawer drawer-end z-10">
    <input id="drawerAddPet" type="checkbox" class="drawer-toggle" />
    <div class="drawer-content">
      <a href="add-pet.php" class="btn bg-[#D4F0EA] w-14 h-14 flex justify-center text-[#363636] hover:bg-[#363636] hover:text-[#D4F0EA] items-center rounded-full fixed bottom-20 right-5 border border-[#363636] shadow-md shadow-[#717171]">
        <i class="fas fa-paw fa-lg"></i>
      </a>
    </div>
  </div>

  <?php 
  // Include modal files before scripts
  require_once 'delete-customer.php';
  require_once 'delete-pet.php';
  ?>

  <script>
    // Script untuk menangani tab yang aktif
    const tabs = document.querySelectorAll('input[name="my_tabs_2"]');
    const addCustomerBtn = document.querySelector('[href="add-customer.php"]');
    const addPetBtn = document.querySelector('[href="add-pet.php"]');

    tabs.forEach((tab, index) => {
      tab.addEventListener('change', () => {
        if (index === 0) { // Tab Customer
          addCustomerBtn.style.display = 'flex';
          addPetBtn.style.display = 'none';
        } else { // Tab Pet
          addCustomerBtn.style.display = 'none';
          addPetBtn.style.display = 'flex';
        }
      });
    });

    // Set tampilan awal
    addPetBtn.style.display = 'none';

    // Auto open drawer when page loads
    window.onload = function() {
      document.getElementById('drawerAddCustomer').checked = true;
    }

    // Function to show delete customer modal
    function showDeleteCustomerModal(id) {
      document.getElementById('deleteCustomer').value = id;
      document.getElementById('modalDeleteCustomer').showModal();
    }

    // Function to show delete pet modal
    function showDeletePetModal(id) {
      document.getElementById('deletePet').value = id;
      document.getElementById('modalDeletePet').showModal();
    }
  </script>
</body>
</html>
