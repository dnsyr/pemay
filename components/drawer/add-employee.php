<?php
ob_start();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add'])) {
  $db = new Database();
  createDataEmployee($db);
}

ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">

<body>
  <!-- Add Users -->
  <div class="drawer drawer-end z-10">
    <input id="drawerAddEmployee" type="checkbox" class="drawer-toggle" />
    <div class="drawer-content">
      <!-- Page content here -->
      <label for="drawerAddEmployee" class="drawer-button btn bg-[#D4F0EA] w-14 h-14 flex justify-center text-[#363636] hover:bg-[#363636] hover:text-[#D4F0EA] items-center rounded-full fixed bottom-5 right-5 border border-[#363636] shadow-md shadow-[#717171]"><i class="fas fa-plus fa-lg"></i></label>
    </div>

    <div class="drawer-side">
      <label for="drawerAddEmployee" aria-label="close sidebar" class="drawer-overlay"></label>
      <form method="POST">
        <div class="menu bg-[#FCFCFC] text-[#363636] min-h-screen w-96 flex flex-col justify-center px-8">
          <input type="hidden" name="action" value="addEmployee">
          <h3 class="text-lg font-semibold mb-7">Add New Employee</h3>

          <div class="gap-5 flex flex-col">
            <div class="">
              <label for="nama">Name</label>
              <input type="text" class="mt-1 w-full rounded-full bg-[#FCFCFC] border border-[#565656] text-[#565656] text-sm placeholder:text-[#565656] placeholder:text-sm px-7 py-2" name="nama" placeholder="e.g. Mr. Adudu" required>
            </div>
            <div class="">
              <label for="email">Email</label>
              <input type="email" class="mt-1 w-full rounded-full bg-[#FCFCFC] border border-[#565656] text-[#565656] text-sm placeholder:text-[#565656] placeholder:text-sm px-7 py-2" name="email" placeholder="e.g. youremail@gmail.com" required>
            </div>
            <div class="">
              <label for="nomorTelpon">Phone Number</label>
              <input type="text" class="mt-1 w-full rounded-full bg-[#FCFCFC] border border-[#565656] text-[#565656] text-sm placeholder:text-[#565656] placeholder:text-sm px-7 py-2" name="nomorTelpon" placeholder="+62 8xxxx" required>
            </div>
            <div class="">
              <label for="nama">Posisi</label>
              <input type="hidden" name="posisi" id="addPosisi">

              <div class="relative inline-block w-full mt-1">
                <!-- Dropdown Toggle -->
                <label tabindex="0" class="btn btnAddPosisi min-h-[2.375rem] h-[2.375rem] max-h-[2.375rem] rounded-full font-normal hover:bg-[#FCFCFC] py-2 px-7 w-full justify-between bg-[#FCFCFC] border border-[#565656] text-[#565656] focus:outline-none focus:ring-[#565656] text-sm">
                  <span id="selectedAddPosisi">Role</span>
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 inline float-right" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </label>

                <!-- Dropdown Menu -->
                <ul
                  tabindex="0"
                  class="dropdown-content menu menuAddPosisi absolute z-10 mt-2 py-2 px-3 shadow bg-[#FCFCFC] text-[#565656] rounded-2xl w-full border border-[#565656] hidden">
                  <li>
                    <a href="#" class="hover:bg-[#565656] hover:text-semibold hover:text-[#FCFCFC]" id="optionOwnerAdd" onclick="handleSelectAddRole('Owner')">Owner</a>
                  </li>
                  <li>
                    <a href="#" class="hover:bg-[#565656] hover:text-semibold hover:text-[#FCFCFC]" id="optionVetAdd" onclick="handleSelectAddRole('Vet')">Vet</a>
                  </li>
                  <li>
                    <a href="#" class="hover:bg-[#565656] hover:text-semibold hover:text-[#FCFCFC]" id="optionStaffAdd" onclick="handleSelectAddRole('Staff')">Staff</a>
                  </li>
                </ul>
              </div>
            </div>


            <div class="">
              <div class="divider divider-neutral m-0 mb-5 border-2"></div>
              <label for="username">Username</label>
              <input type="text" class="mt-1 w-full rounded-full bg-[#FCFCFC] border border-[#565656] text-[#565656] text-sm placeholder:text-[#565656] placeholder:text-sm px-7 py-2" name="username" placeholder="your unique name" required>
            </div>
            <div class="">
              <label for="password">Password</label>
              <input type="password" class="mt-1 w-full rounded-full bg-[#FCFCFC] border border-[#565656] text-[#565656] text-sm placeholder:text-[#565656] placeholder:text-sm px-7 py-2" name="password" placeholder="your secure password" required>
            </div>
            <a class="flex justify-end gap-5">
              <button type="submit" name="add" class="btn bg-[#B2B5E0] text-[#565656] shadow-md shadow-[#565656] px-3 rounded-full hover:bg-[#565656] hover:text-[#FCFCFC] flex items-center"><i class="fas fa-plus fa-md"></i> Add Employee</button>
              <label for="drawerAddEmployee" aria-label="close sidebar" class="btn bg-[#E0BAB2] text-[#565656] shadow-md shadow-[#565656] px-3 rounded-full hover:bg-[#565656] hover:text-[#FCFCFC]">Cancel</label>
            </a>
          </div>
        </div>
      </form>
    </div>
  </div>

  <script>
    const dropdownLabelAddEmployee = document.querySelector('.btnAddPosisi');
    const dropdownMenuAddEmployee = document.querySelector('.menuAddPosisi');

    const optionOwnerAdd = document.getElementById('optionOwnerAdd');
    const optionVetAdd = document.getElementById('optionVetAdd');
    const optionStaffAdd = document.getElementById('optionStaffAdd');

    dropdownLabelAddEmployee.addEventListener('click', () => {
      dropdownMenuAddEmployee.classList.toggle('hidden');
    });

    function handleSelectAddRole(selectedValue) {
      selectAddPosisi(selectedValue);
      let posisi = document.getElementById('addPosisi');

      if (selectedValue == "Owner") {
        posisi.value = "owner";

        addActiveClassOwner(optionOwnerAdd)
        removeActiveClassStaff(optionStaffAdd)
        removeActiveClassVet(optionVetAdd)
      } else if (selectedValue == "Vet") {
        posisi.value = "vet";

        addActiveClassVet(optionVetAdd)
        removeActiveClassOwner(optionOwnerAdd)
        removeActiveClassStaff(optionStaffAdd)
      } else if (selectedValue == "Staff") {
        posisi.value = "staff";

        addActiveClassStaff(optionStaffAdd)
        removeActiveClassOwner(optionOwnerAdd)
        removeActiveClassVet(optionVetAdd)
      }
    }

    function selectAddPosisi(value) {
      document.getElementById('selectedAddPosisi').textContent = value;
      dropdownMenuAddEmployee.classList.add('hidden');
    }
  </script>
</body>

</html>