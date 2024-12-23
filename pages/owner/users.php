<?php
session_start();
ob_start();
include '../../config/connection.php';
include '../../config/database.php';
include '../../handlers/pegawai.php';

$pageTitle = 'Manage Users';
include '../../layout/header-tailwind.php';
include '../../components/drawer/add-employee.php';
include '../../components/drawer/update-employee.php';
include '../../components/modal/delete-employee.php';

if (!isset($_SESSION['username']) || $_SESSION['posisi'] != 'owner') {
  header("Location: ../../auth/restricted.php");
  exit();
}

$db = new Database();

$dataEmployees = getAllDataEmployees($db);

// Update employee
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {
  $username = $_POST['oldUsername'];

  updateDataEmployee($db, $username);
}

// Delete employee
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
  $username = $_POST['delete'];

  deleteEmployee($db, $username);
}

ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">

<body>
  <div class="pb-6 px-12 text-[#363636]">
    <div class="flex justify-between mb-6">
      <h2 class="text-3xl font-bold italic">Manage Employees</h2>

      <!-- Alert -->
      <?php if (isset($_SESSION['success_message']) && $_SESSION['success_message'] !== ""): ?>
        <div role="alert" class="alert alert-success py-2 px-7 rounded-full w-fit">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-6 w-6 shrink-0 stroke-current"
            fill="none"
            viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span>
            <?php echo htmlentities($_SESSION['success_message']);
            unset($_SESSION['success_message']); ?></span>
        </div>
      <?php elseif (isset($_SESSION['error_message']) && $_SESSION['error_message'] !== ""): ?>
        <div role="alert" class="alert alert-error py-2 px-7 rounded-full w-fit">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-6 w-6 shrink-0 stroke-current"
            fill="none"
            viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span><?php echo htmlentities($_SESSION['error_message']);
                unset($_SESSION['error_message']); ?></span>
        </div>
      <?php endif; ?>

      <div role="alert" id="alertDeleteSelf" class="alert bg-[#D4F0EA] py-2 px-7 rounded-full w-fit hidden text-[#363636]">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
          class="h-6 w-6 shrink-0 stroke-current">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <span>You can't delete your account by your self.</span>
        <div>
          <button class="btn btn-circle btn-outline w-6 h-6 min-h-fit text-black hover:bg-black hover:text-white border border-2 hover:border-none" onclick="closeAlertDeleteSelf()">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="h-3 w-3"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <div role="tablist" class="tabs tabs-lifted relative z-0">
      <input type="radio" name="my_tabs_2" role="tab" checked class="tab text-[#363636] text-base font-semibold [--tab-bg:#D4F0EA] [--tab-border-color:#363636]" aria-label="Employees" />
      <div role="tabpanel" class="tab-content  bg-[#FCFCFC] border-base-300 rounded-box p-6">
        <p class="text-lg text-[#363636] font-semibold italic">Registered Employees</p>

        <div class="overflow-hidden border border-[#363636] rounded-xl shadow-md shadow-[#717171] mt-3">
          <table class="table border-collapse">
            <thead>
              <tr class="bg-[#D4F0EA] text-[#363636] font-semibold">
                <th class="rounded-tl-xl">Name</th>
                <th>Username</th>
                <th>Role</th>
                <th>Email</th>
                <th>Phone Number</th>
                <th class="rounded-tr-xl"></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($dataEmployees as $index => $user): ?>
                <tr class="text-[#363636]">
                  <td class="<?= $index === count($dataEmployees) - 1 ? 'rounded-bl-xl' : '' ?>"><?php echo htmlentities($user['NAMA']); ?></td>
                  <td><?php echo htmlentities($user['USERNAME']); ?></td>
                  <td><?php echo htmlentities($user['POSISI']); ?></td>
                  <td><?php echo htmlentities($user['EMAIL']); ?></td>
                  <td><?php echo htmlentities($user['NOMORTELPON']); ?></td>
                  <td class="<?= $index === count($dataEmployees) - 1 ? 'rounded-br-xl' : '' ?>">
                    <div class="flex gap-3 justify-center items-center">
                      <button
                        type="button"
                        class="btn btn-warning btn-sm"
                        onclick="handleUpdateBtn('<?php echo $user['USERNAME']; ?>')">
                        <i class="fas fa-edit"></i>
                      </button>

                      <button
                        type="button"
                        class="btn btn-error btn-sm"
                        onclick="handleDeleteBtn('<?php echo $user['USERNAME']; ?>')">
                        <i class="fas fa-trash-alt"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <script>
      function showAlertDeleteSelf() {
        document.getElementById('alertDeleteSelf').classList.remove('hidden')
      }

      function closeAlertDeleteSelf() {
        document.getElementById('alertDeleteSelf').classList.add('hidden')
      }

      function handleDeleteBtn(username) {
        const sessionUsername = "<?php echo $_SESSION['username']; ?>";

        if (sessionUsername === username) {
          showAlertDeleteSelf()
        } else {
          document.getElementById('deleteEmployee').value = username;
          document.getElementById('modalDeleteEmployee').showModal()
        }
      }

      function handleUpdateBtn(username) {
        const userData = getDataEmployee(username);
        document.getElementById('oldUsername').value = userData.username;

        fillUpdateRole(userData.role)

        // Populate input fields
        document.getElementById('updateNama').value = userData.name;
        document.getElementById('updateUsername').value = userData.username;
        document.getElementById('updateEmail').value = userData.email;
        document.getElementById('updatePosisi').value = userData.role;
        document.getElementById('updateNomorTelpon').value = userData.nomorTelpon;

        document.getElementById('drawerUpdateEmployee').checked = true;
      }

      const users = <?php echo json_encode(array_reduce($dataEmployees, function ($carry, $employee) {
                      $carry[$employee['USERNAME']] = [
                        'name' => $employee['NAMA'],
                        'email' => $employee['EMAIL'],
                        'nomorTelpon' => $employee['NOMORTELPON'],
                        'role' => $employee['POSISI'],
                        'username' => $employee['USERNAME']
                      ];
                      return $carry;
                    }, [])); ?>;

      function getDataEmployee(username) {
        return users[username] || {};
      }
    </script>
</body>

</html>