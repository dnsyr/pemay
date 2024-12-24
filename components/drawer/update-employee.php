<!DOCTYPE html>
<html lang="en">

<body>
  <!-- Add Users -->
  <div class="drawer drawer-end z-10">
    <input id="drawerUpdateEmployee" type="checkbox" class="drawer-toggle" />
    <div class="drawer-content">
      <!-- Page content here -->
      <!-- <label for="drawerUpdateEmployee" class="drawer-button btn bg-[#D4F0EA] w-14 h-14 flex justify-center text-[#363636] hover:bg-[#363636] hover:text-[#D4F0EA] items-center rounded-full fixed bottom-5 right-5 border border-[#363636] shadow-md shadow-[#717171]"><i class="fas fa-edit fa-lg"></i></label> -->
    </div>

    <div class="drawer-side">
      <label for="drawerUpdateEmployee" aria-label="close sidebar" class="drawer-overlay"></label>
      <form method="POST">
        <div class="menu bg-[#FCFCFC] text-[#363636] min-h-screen w-96 flex flex-col justify-center px-8">
          <input type="hidden" name="oldUsername" id="oldUsername">
          <h3 class="text-lg font-semibold mb-7">Update Data Employee</h3>

          <div class="gap-5 flex flex-col">
            <div class="">
              <label for="updateNama">Name</label>
              <input type="text" class="mt-1 w-full rounded-full bg-[#FCFCFC] border border-[#565656] text-[#565656] text-sm placeholder:text-[#565656] placeholder:text-sm px-7 py-2" name="updateNama" id="updateNama" placeholder="e.g. Mr. Adudu" required>
            </div>
            <div class="">
              <label for="updateEmail">Email</label>
              <input type="updateEmail" class="mt-1 w-full rounded-full bg-[#FCFCFC] border border-[#565656] text-[#565656] text-sm placeholder:text-[#565656] placeholder:text-sm px-7 py-2" name="updateEmail" id="updateEmail" placeholder="e.g. youremail@gmail.com" required>
            </div>
            <div class="">
              <label for="updateNomorTelpon">Phone Number</label>
              <input type="text" class="mt-1 w-full rounded-full bg-[#FCFCFC] border border-[#565656] text-[#565656] text-sm placeholder:text-[#565656] placeholder:text-sm px-7 py-2" name="updateNomorTelpon" id="updateNomorTelpon" placeholder="+62 8xxxx" required>
            </div>
            <div class="">
              <label for="nama">Posisi</label>
              <input type="hidden" name="updatePosisi" id="updatePosisi">

              <div class="relative inline-block w-full mt-1">
                <!-- Dropdown Toggle -->
                <label tabindex="0" class="btn btnUpdatePosisi min-h-[2.375rem] h-[2.375rem] max-h-[2.375rem] rounded-full font-normal hover:bg-[#FCFCFC] py-2 px-7 w-full justify-between bg-[#FCFCFC] border border-[#565656] text-[#565656] focus:outline-none focus:ring-[#565656] text-sm">
                  <span id="selectedUpdatePosisi">Role</span>
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 inline float-right" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </label>

                <!-- Dropdown Menu -->
                <ul
                  tabindex="0"
                  class="dropdown-content menu menuUpdatePosisi absolute z-10 mt-2 py-2 px-3 shadow bg-[#FCFCFC] text-[#565656] rounded-2xl w-full border border-[#565656] hidden">
                  <li>
                    <a href="#" class="hover:bg-[#565656] hover:text-semibold hover:text-[#FCFCFC]" id="optionOwnerUpdate" onclick="handleSelectUpdateRole('Owner')">Owner</a>
                  </li>
                  <li>
                    <a href="#" class="hover:bg-[#565656] hover:text-semibold hover:text-[#FCFCFC]" id="optionVetUpdate" onclick="handleSelectUpdateRole('Vet')">Vet</a>
                  </li>
                  <li>
                    <a href="#" class="hover:bg-[#565656] hover:text-semibold hover:text-[#FCFCFC]" id="optionStaffUpdate" onclick="handleSelectUpdateRole('Staff')">Staff</a>
                  </li>
                </ul>
              </div>
            </div>


            <div class="">
              <div class="divider divider-neutral m-0 mb-5 border-2"></div>
              <label for="updateUsername">Username</label>
              <input type="text" class="mt-1 w-full rounded-full bg-[#FCFCFC] border border-[#565656] text-[#565656] text-sm placeholder:text-[#565656] placeholder:text-sm px-7 py-2" name="updateUsername" id="updateUsername" readonly placeholder="your unique name" required>
            </div>
            <div class="">
              <label for="password">Password</label>
              <input type="password" class="mt-1 w-full rounded-full bg-[#FCFCFC] border border-[#565656] text-[#565656] text-sm placeholder:text-[#565656] placeholder:text-sm px-7 py-2" name="password" placeholder="Leave empty to keep old password!">
            </div>
            <div class="flex justify-end gap-5">
              <button type="submit" name="update" class="btn bg-[#B2B5E0] text-[#565656] shadow-md shadow-[#565656] px-3 rounded-full hover:bg-[#565656] hover:text-[#FCFCFC] flex items-center"><i class="fas fa-edit fa-md"></i> Update Employee</button>
              <label for="drawerUpdateEmployee" aria-label="close sidebar" class="btn bg-[#E0BAB2] text-[#565656] shadow-md shadow-[#565656] px-3 rounded-full hover:bg-[#565656] hover:text-[#FCFCFC]">Cancel</label>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>

  <script>
    const dropdownLabelUpdatePosisi = document.querySelector('.btnUpdatePosisi');
    const dropdownMenuUpdatePosisi = document.querySelector('.menuUpdatePosisi');

    const optionOwnerUpdate = document.getElementById('optionOwnerUpdate');
    const optionVetUpdate = document.getElementById('optionVetUpdate');
    const optionStaffUpdate = document.getElementById('optionStaffUpdate');

    function fillUpdateRole(selectedValue) {
      let posisi = document.getElementById('updatePosisi');

      if (selectedValue == "owner") {
        posisi.value = selectedValue;
        selectUpdatePosisi("Owner");

        addActiveClassOwner(optionOwnerUpdate)
        removeActiveClassStaff(optionStaffUpdate)
        removeActiveClassVet(optionVetUpdate)
      } else if (selectedValue == "vet") {
        posisi.value = selectedValue;
        selectUpdatePosisi("Vet");

        addActiveClassVet(optionVetUpdate)
        removeActiveClassOwner(optionOwnerUpdate)
        removeActiveClassStaff(optionStaffUpdate)
      } else if (selectedValue == "staff") {
        posisi.value = selectedValue;
        selectUpdatePosisi("Staff");

        addActiveClassStaff(optionStaffUpdate)
        removeActiveClassOwner(optionOwnerUpdate)
        removeActiveClassVet(optionVetUpdate)
      }
    }

    dropdownLabelUpdatePosisi.addEventListener('click', () => {
      dropdownMenuUpdatePosisi.classList.toggle('hidden');
    });

    function handleSelectUpdateRole(selectedValue) {
      selectUpdatePosisi(selectedValue);
      let posisi = document.getElementById('updatePosisi');

      if (selectedValue == "Owner") {
        posisi.value = "owner";

        addActiveClassOwner(optionOwnerUpdate)
        removeActiveClassStaff(optionStaffUpdate)
        removeActiveClassVet(optionVetUpdate)
      } else if (selectedValue == "Vet") {
        posisi.value = "vet";

        addActiveClassVet(optionVetUpdate)
        removeActiveClassOwner(optionOwnerUpdate)
        removeActiveClassStaff(optionStaffUpdate)
      } else if (selectedValue == "Staff") {
        posisi.value = "staff";

        addActiveClassStaff(optionStaffUpdate)
        removeActiveClassOwner(optionOwnerUpdate)
        removeActiveClassVet(optionVetUpdate)
      }
    }

    function selectUpdatePosisi(value) {
      document.getElementById('selectedUpdatePosisi').textContent = value;
      dropdownMenuUpdatePosisi.classList.add('hidden');
    }
  </script>
</body>

</html>