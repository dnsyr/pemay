<?php
session_start();
ob_start();
require_once '../../config/database.php';

$pageTitle = 'Report';
include '../../layout/header-tailwind.php';

$pegawaiID = $_SESSION['employee_id'];

if (!isset($_SESSION['username']) && $_SESSION['posisi'] !== 'owner') {
  header("Location: ../../auth/restricted.php");
  exit();
}

ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">

<body>
  <div class="pb-6 px-12 text-[#363636]">
    <div class="flex justify-between mb-6">
      <h2 class="text-3xl font-bold italic">Report</h2>

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

      <div role="alert" id="alertReport" class="alert bg-[#D4F0EA] py-2 px-7 rounded-full w-fit hidden text-[#363636]">
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
        <span></span>
        <div>
          <!-- <button class="btn btn-circle btn-outline w-6 h-6 min-h-fit text-black hover:bg-black hover:text-white border border-2 hover:border-none" onclick="closeAlertReport()">
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
            </button> -->
        </div>
      </div>
    </div>

    <div role="tablist" class="tabs tabs-lifted relative z-0">
      <!-- Report -->
      <input type="radio" name="my_tabs_2" role="tab" checked class="tab text-[#363636] text-base font-semibold [--tab-bg:#D4F0EA] [--tab-border-color:#363636] min-w-[105px] w-[105px]" aria-label="Finansial" />
      <div role="tabpanel" class="tab-content  bg-[#FCFCFC] border-base-300 rounded-box p-6">
        <h2>Content 1</h2>
      </div>

      <!-- Cage -->
      <input
        type="radio"
        name="my_tabs_2"
        role="tab"
        class="tab text-[#363636] text-base font-semibold [--tab-bg:#D4F0EA] [--tab-border-color:#363636]"

        aria-label="Product" />
      <div role="tabpanel" class="tab-content  bg-[#FCFCFC] border-base-300 rounded-box p-6">
        <h2>Content 2</h2>
      </div>
    </div>
</body>

</html>