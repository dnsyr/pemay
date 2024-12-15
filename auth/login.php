<?php
session_start();

include '../config/connection.php';

$role = "";
$is_logged_in = isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;

if (isset($_SESSION['posisi'])) {
  $role = $_SESSION['posisi'];
}

if (!isset($_SESSION['captcha'])) {
  $captchaText = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890'), 0, 6);
  $_SESSION['captcha'] = $captchaText;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login</title>
  <link rel="shortcut icon" href="../public/img/icon.png" type="image/x-icon">

  <!-- Daisy UI + Tailwind CSS -->
  <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.14/dist/full.min.css" rel="stylesheet" type="text/css" />
  <script src="https://cdn.tailwindcss.com"></script>

  <script>
    const isLoggedIn = <?php echo json_encode($is_logged_in); ?>;
    const role = <?php echo json_encode($role); ?>;
    if (isLoggedIn) {
      if (role === 'owner') {
        window.location.href = '/pemay/pages/owner/dashboard.php'
      } else if (role === 'vet') {
        window.location.href = '/pemay/pages/vet/dashboard.php'
      } else if (role === 'staff') {
        window.location.href = '/pemay/pages/staff/dashboard.php'
      }
    }
  </script>

  <!-- Custom Font Plus Jakarta Sans -->
  <style>
    @import url("https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&display=swap");

    body {
      font-family: "Plus Jakarta Sans", sans-serif;
      font-optical-sizing: auto;
      font-style: normal;
      background-color: #FCFCFC;
    }
  </style>
</head>

<body class="min-h-screen py-11 px-20">
  <div class="bg-[#E5E0DA] min-h-full flex w-full p-[1.5rem]">
    <div class="bg-white rounded-[2rem] min-h-[80vh] w-[55%] py-8 px-12">
      <div class="flex flex-col gap-3 text-[#363636]">
        <p class="text-md font-semibold">Welcome to</p>
        <p class="text-2xl font-bold">Petshop Management System</p>
      </div>
    </div>
    <div class="flex flex-col items-center justify-center w-[45%]">
      <div class="w-[60%]">
        <h2 class="text-5xl font-bold text-[#565656] mb-12">Sign in</h2>
        <form class="w-full flex flex-col gap-5" action="authentication.php" method="POST">
          <input type="text" name="username" class="w-full rounded-full bg-[#FCFCFC] border border-[#565656] text-[#565656] text-sm placeholder:text-[#565656] placeholder:text-sm px-7 py-2" placeholder="Username" required>

          <input type="password" name="password" class="w-full rounded-full bg-[#FCFCFC] border border-[#565656] text-[#565656] text-sm placeholder:text-[#565656] placeholder:text-sm px-7 py-2" placeholder="Password" required>

          <input type="hidden" name="posisi" id="posisi">

          <div class="relative inline-block w-full">
            <!-- Dropdown Toggle -->
            <label tabindex="0" class="btn min-h-[2.375rem] h-[2.375rem] max-h-[2.375rem] rounded-full font-normal hover:bg-[#FCFCFC] py-2 px-7 w-full justify-between bg-[#FCFCFC] border border-[#565656] text-[#565656] focus:outline-none focus:ring-[#565656] text-sm">
              <span id="selectedOption">Role</span>
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 inline float-right" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </label>

            <!-- Dropdown Menu -->
            <ul
              tabindex="0"
              class="dropdown-content menu absolute z-10 mt-2 py-2 px-3 shadow bg-[#FCFCFC] text-[#565656] rounded-2xl w-full border border-[#565656] hidden">
              <li>
                <a href="#" class="hover:bg-[#565656] hover:text-semibold hover:text-[#FCFCFC]" onclick="handleSelectRole('Owner')">Owner</a>
              </li>
              <li>
                <a href="#" class="hover:bg-[#565656] hover:text-semibold hover:text-[#FCFCFC]" onclick="handleSelectRole('Vet')">Vet</a>
              </li>
              <li>
                <a href="#" class="hover:bg-[#565656] hover:text-semibold hover:text-[#FCFCFC]" onclick="handleSelectRole('Staff')">Staff</a>
              </li>
            </ul>
          </div>

          <input type="text" name="captcha" class="w-full rounded-full bg-[#FCFCFC] border border-[#565656] text-[#565656] text-sm placeholder:text-[#565656] placeholder:text-sm px-7 py-2" placeholder="Enter CAPTCHA" required>

          <div class="flex justify-between mt-3">
            <img src="generate-captcha.php" class="rounded-xl max-w-[50%] border border-[#565656]" alt="CAPTCHA">
            <button class="btn bg-[#FCFCFC] w-[50%] rounded-full text-[#565656] text-xl text-extrabold italic hover:bg-[#565656] hover:text-[#FCFCFC]" type="submit">Login</button>
          </div>

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
        </form>

        <!-- <form action="generate-dummy-users.php" method="post">
        <button type="submit">Generate Dummy Users</button>
      </form> -->
      </div>
    </div>
  </div>

  <script>
    const dropdownLabel = document.querySelector('.btn');
    const dropdownMenu = document.querySelector('.dropdown-content');

    dropdownLabel.addEventListener('click', () => {
      dropdownMenu.classList.toggle('hidden');
    });

    function handleSelectRole(selectedValue) {
      selectOption(selectedValue);
      let posisi = document.getElementById('posisi');

      if (selectedValue == "Owner") {
        posisi.value = "owner";
      } else if (selectedValue == "Vet") {
        posisi.value = "vet";
      } else if (selectedValue == "Staff") {
        posisi.value = "staff";
      }
    }

    function selectOption(value) {
      document.getElementById('selectedOption').textContent = value;
      dropdownMenu.classList.add('hidden');
    }
  </script>
</body>

</html>