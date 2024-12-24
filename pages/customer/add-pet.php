<?php
session_start();
ob_start();
require_once '../../config/database.php';
require_once '../../layout/header-tailwind.php';

$db = new Database();
$success_message = '';
$error_message = '';

// Ambil data pemilik hewan untuk dropdown
$queryPemilik = "SELECT ID, NAMA FROM PEMILIKHEWAN ORDER BY NAMA";
$db->query($queryPemilik);
$pemilikList = $db->resultSet();

// Ambil data hewan untuk tabel
$searchPet = isset($_GET['search_pet']) ? $_GET['search_pet'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 6;
$offset = ($page - 1) * $limit;

// Query untuk menghitung total data
$countQuery = "SELECT COUNT(*) as TOTAL FROM HEWAN h 
               LEFT JOIN PEMILIKHEWAN p ON h.PEMILIKHEWAN_ID = p.ID 
               WHERE LOWER(h.NAMA) LIKE LOWER(:search)";
$db->query($countQuery);
$db->bind(':search', '%' . $searchPet . '%');
$totalResult = $db->single();
$totalData = $totalResult['TOTAL'];
$totalPages = ceil($totalData / $limit);

// Modifikasi query untuk pagination
$queryPet = "SELECT * FROM (
                SELECT a.*, ROWNUM rnum FROM (
                    SELECT h.ID, h.NAMA, p.NAMA as NAMA_PEMILIK, h.SPESIES, h.RAS, h.GENDER, 
                    h.BERAT, TO_CHAR(h.TANGGALLAHIR, 'DD-MON-YY') as TANGGALLAHIR, 
                    h.TINGGI, h.LEBAR 
                    FROM HEWAN h 
                    LEFT JOIN PEMILIKHEWAN p ON h.PEMILIKHEWAN_ID = p.ID 
                    WHERE LOWER(h.NAMA) LIKE LOWER(:search)
                    ORDER BY h.ID
                ) a WHERE ROWNUM <= :end_row
            ) WHERE rnum > :start_row";

$db->query($queryPet);
$db->bind(':search', '%' . $searchPet . '%');
$db->bind(':start_row', $offset);
$db->bind(':end_row', $offset + $limit);
$pets = $db->resultSet();

// Proses penambahan data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    try {
        // Dapatkan ID terakhir
        $queryLastId = "SELECT MAX(ID) as MAX_ID FROM HEWAN";
        $db->query($queryLastId);
        $lastId = $db->single();
        $newId = isset($lastId['MAX_ID']) ? (int)$lastId['MAX_ID'] + 1 : 1;

        $nama = $_POST['nama'];
        $ras = $_POST['ras'];
        $spesies = $_POST['spesies'];
        $gender = $_POST['gender'];
        $berat = $_POST['berat'];
        $tanggalLahir = $_POST['tanggallahir'];
        $tinggi = $_POST['tinggi'];
        $lebar = $_POST['lebar'];
        $pemilikId = $_POST['pemilikId'];

        // Insert data hewan dengan ID dan ID Pemilik
        $queryInsert = "INSERT INTO HEWAN (ID, NAMA, RAS, SPESIES, GENDER, BERAT, TANGGALLAHIR, TINGGI, LEBAR, PEMILIKHEWAN_ID) 
                        VALUES (:id, :nama, :ras, :spesies, :gender, :berat, TO_DATE(:tanggallahir, 'YYYY-MM-DD'), :tinggi, :lebar, :pemilik_id)";
        
        $db->query($queryInsert);
        $db->bind(':id', $newId);
        $db->bind(':nama', $nama);
        $db->bind(':ras', $ras);
        $db->bind(':spesies', $spesies);
        $db->bind(':gender', $gender);
        $db->bind(':berat', $berat);
        $db->bind(':tanggallahir', $tanggalLahir);
        $db->bind(':tinggi', $tinggi);
        $db->bind(':lebar', $lebar);
        $db->bind(':pemilik_id', $pemilikId);

        $db->execute();
        echo "<script>
                alert('Data hewan berhasil ditambahkan!');
                window.location.href = 'customer.php';
              </script>";
        exit();
    } catch (PDOException $e) {
        $error_message = 'Gagal menambahkan data: ' . $e->getMessage();
    }
}

ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<body>
  <!-- Main Content -->
  <div class="pb-6 px-12 text-[#363636]">
    <div class="flex justify-between mb-6">
      <h2 class="text-3xl font-bold">Manage Pets</h2>

      <!-- Alert Messages -->
      <?php if ($success_message): ?>
        <div role="alert" class="alert alert-success py-2 px-7 rounded-full w-fit">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0 stroke-current" fill="none" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span><?php echo $success_message; ?></span>
        </div>
      <?php endif; ?>

      <?php if ($error_message): ?>
        <div role="alert" class="alert alert-error py-2 px-7 rounded-full w-fit">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0 stroke-current" fill="none" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span><?php echo $error_message; ?></span>
        </div>
      <?php endif; ?>
    </div>

    <div class="bg-[#FCFCFC] border-base-300 rounded-box p-6">
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
              <th>Pet Name</th>
              <th>Owner</th>
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
                <td><?php echo htmlspecialchars($row['NAMA_PEMILIK']); ?></td>
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
                    <a href="delete-pet.php?id=<?php echo $row['ID']; ?>" class="btn btn-error btn-sm" onclick="return confirm('Yakin ingin menghapus data ini?')">
                      <i class="fas fa-trash-alt"></i>
                    </a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="flex justify-center mt-4 gap-2">
        <?php if($page > 1): ?>
          <a href="?page=<?php echo $page-1; ?>&search_pet=<?php echo urlencode($searchPet); ?>" 
             class="btn btn-sm bg-[#D4F0EA] text-[#363636] hover:bg-[#363636] hover:text-[#D4F0EA] border border-[#363636]">
            <i class="fas fa-chevron-left"></i>
          </a>
        <?php endif; ?>

        <?php for($i = 1; $i <= $totalPages; $i++): ?>
          <a href="?page=<?php echo $i; ?>&search_pet=<?php echo urlencode($searchPet); ?>" 
             class="btn btn-sm <?php echo $i === $page ? 'bg-[#363636] text-[#D4F0EA]' : 'bg-[#D4F0EA] text-[#363636] hover:bg-[#363636] hover:text-[#D4F0EA]'; ?> border border-[#363636]">
            <?php echo $i; ?>
          </a>
        <?php endfor; ?>

        <?php if($page < $totalPages): ?>
          <a href="?page=<?php echo $page+1; ?>&search_pet=<?php echo urlencode($searchPet); ?>" 
             class="btn btn-sm bg-[#D4F0EA] text-[#363636] hover:bg-[#363636] hover:text-[#D4F0EA] border border-[#363636]">
            <i class="fas fa-chevron-right"></i>
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Add Pet Drawer -->
  <div class="drawer drawer-end z-10">
    <input id="drawerAddPet" type="checkbox" class="drawer-toggle" />
    <div class="drawer-content">
      <!-- Page content here -->
      <label for="drawerAddPet" class="drawer-button btn bg-[#D4F0EA] w-14 h-14 flex justify-center text-[#363636] hover:bg-[#363636] hover:text-[#D4F0EA] items-center rounded-full fixed bottom-5 right-5 border border-[#363636] shadow-md shadow-[#717171]">
        <i class="fas fa-paw fa-lg"></i>
      </label>
    </div>

    <div class="drawer-side">
      <label for="drawerAddPet" aria-label="close sidebar" class="drawer-overlay"></label>
      <form method="POST" class="h-full">
        <div class="menu bg-[#FCFCFC] text-[#363636] min-h-screen w-96 flex flex-col justify-center px-8">
          <div class="flex items-center gap-2 mb-7">
            <i class="fas fa-paw text-xl"></i>
            <h3 class="text-lg font-semibold">Add New Pet</h3>
          </div>

          <div class="gap-5 flex flex-col">
            <div class="form-control">
              <label for="pemilikId" class="label">
                <span class="label-text text-[#363636]">Pet Owner</span>
              </label>
              <select id="pemilikId" name="pemilikId" class="select select-bordered w-full rounded-full bg-[#FCFCFC] border border-[#565656] text-[#565656] text-sm" required>
                <option value="">Select Pet Owner</option>
                <?php foreach ($pemilikList as $pemilik): ?>
                    <option value="<?php echo $pemilik['ID']; ?>">
                        <?php echo htmlspecialchars($pemilik['NAMA']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            </div>

            <div class="form-control">
              <label for="nama" class="label">
                <span class="label-text text-[#363636]">Pet Name</span>
              </label>
              <input type="text" class="mt-1 w-full rounded-full bg-[#FCFCFC] border border-[#565656] text-[#565656] text-sm placeholder:text-[#565656] placeholder:text-sm px-7 py-2" name="nama" placeholder="Enter pet name" required>
            </div>

            <div class="form-control">
              <label for="spesies" class="label">
                <span class="label-text text-[#363636]">Species</span>
              </label>
              <input type="text" class="mt-1 w-full rounded-full bg-[#FCFCFC] border border-[#565656] text-[#565656] text-sm placeholder:text-[#565656] placeholder:text-sm px-7 py-2" name="spesies" placeholder="e.g. Cat, Dog, etc." required>
            </div>

            <div class="form-control">
              <label for="ras" class="label">
                <span class="label-text text-[#363636]">Race/Breed</span>
              </label>
              <input type="text" class="mt-1 w-full rounded-full bg-[#FCFCFC] border border-[#565656] text-[#565656] text-sm placeholder:text-[#565656] placeholder:text-sm px-7 py-2" name="ras" placeholder="e.g. Persian, Bulldog, etc." required>
            </div>

            <div class="form-control">
              <label for="gender" class="label">
                <span class="label-text text-[#363636]">Gender</span>
              </label>
              <select id="gender" name="gender" class="select select-bordered w-full rounded-full bg-[#FCFCFC] border border-[#565656] text-[#565656] text-sm" required>
                <option value="">Select Gender</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
            </select>
            </div>

            <div class="form-control">
              <label for="berat" class="label">
                <span class="label-text text-[#363636]">Weight (kg)</span>
              </label>
              <input type="number" class="mt-1 w-full rounded-full bg-[#FCFCFC] border border-[#565656] text-[#565656] text-sm placeholder:text-[#565656] placeholder:text-sm px-7 py-2" name="berat" step="0.01" placeholder="Enter weight in kg" required>
            </div>

            <div class="form-control">
              <label for="tanggallahir" class="label">
                <span class="label-text text-[#363636]">Birth Date</span>
              </label>
              <input type="date" class="mt-1 w-full rounded-full bg-[#FCFCFC] border border-[#565656] text-[#565656] text-sm placeholder:text-[#565656] placeholder:text-sm px-7 py-2" name="tanggallahir" required>
            </div>

            <div class="form-control">
              <label for="tinggi" class="label">
                <span class="label-text text-[#363636]">Height (cm)</span>
              </label>
              <input type="number" class="mt-1 w-full rounded-full bg-[#FCFCFC] border border-[#565656] text-[#565656] text-sm placeholder:text-[#565656] placeholder:text-sm px-7 py-2" name="tinggi" step="0.01" placeholder="Enter height in cm" required>
            </div>

            <div class="form-control">
              <label for="lebar" class="label">
                <span class="label-text text-[#363636]">Width (cm)</span>
              </label>
              <input type="number" class="mt-1 w-full rounded-full bg-[#FCFCFC] border border-[#565656] text-[#565656] text-sm placeholder:text-[#565656] placeholder:text-sm px-7 py-2" name="lebar" step="0.01" placeholder="Enter width in cm" required>
            </div>

            <div class="divider divider-neutral"></div>

            <div class="flex justify-end gap-3">
              <button type="submit" name="add" class="btn bg-[#B2B5E0] text-[#565656] shadow-md shadow-[#565656] px-5 rounded-full hover:bg-[#565656] hover:text-[#FCFCFC] flex items-center gap-2">
                <i class="fas fa-save"></i> Save Pet
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
      document.getElementById('drawerAddPet').checked = true;
    }
  </script>
</body>
</html>
