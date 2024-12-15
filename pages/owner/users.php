<?php
session_start();
ob_start();
include '../../config/connection.php';
include '../../config/database.php';
include '../../handlers/pegawai.php';

$pageTitle = 'Manage Users';
// include '../../layout/header.php';
include '../../layout/header-tailwind.php';

if (!isset($_SESSION['username']) || $_SESSION['posisi'] != 'owner') {
  header("Location: ../../auth/restricted.php");
  exit();
}

$db = new Database();

$dataEmployees = getAllDataEmployees($db);

// Delete Users
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
      <h2 class="text-3xl font-bold">Manage Employees</h2>

      <!-- Alert -->
      <?php if (isset($_SESSION['success_message']) && $_SESSION['success_message'] !== ""): ?>
        <div role="alert" class="alert alert-success py-2 px-7 rounded-full">
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
        <div role="alert" class="alert alert-error py-2 px-7 rounded-full">
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

      <a href="add-user.php" class="bg-[#D4F0EA] w-14 h-14 flex justify-center items-center rounded-full fixed bottom-5 right-5 border border-[#363636] shadow-md shadow-[#717171]"><i class="fas fa-plus fa-lg"></i></a>
    </div>

    <div role="tablist" class="tabs tabs-lifted">
      <input type="radio" name="my_tabs_2" role="tab" checked class="tab text-[#363636] text-base font-semibold [--tab-bg:#FCFCFC] [--tab-border-color:#363636]" aria-label="Employees" />
      <div role="tabpanel" class="tab-content  bg-[#FCFCFC] border-base-300 rounded-box p-6">
        <p class="text-lg text-[#363636] font-semibold">Registered Employees</p>

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
                      <a href="update-user.php?username=<?php echo $user['USERNAME']; ?>" class="btn btn-warning btn-sm">
                        <i class="fas fa-edit"></i>
                      </a>
                      <form method="POST" action="">
                        <input type="hidden" name="delete" value="<?php echo $user['USERNAME']; ?>">
                        <button
                          type="submit"
                          class="btn btn-error btn-sm"
                          onclick="return confirm('Are you sure you want to delete this user?');">
                          <i class="fas fa-trash-alt"></i>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

</body>

</html>