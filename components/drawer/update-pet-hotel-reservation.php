<?php
require_once '../../config/database.php';
$db = new Database();
$cageRooms = getAllDataCages($db);
?>

<!DOCTYPE html>
<html lang="en">

<body>
  <!-- Update Pet Hotel Reservation -->
  <div class="drawer drawer-end z-10">
    <input id="drawerUpdateReservationHotel" type="checkbox" class="drawer-toggle" />
    <div class="drawer-content">
    </div>

    <div class="drawer-side">
      <label for="drawerUpdateReservationHotel" aria-label="close sidebar" class="drawer-overlay"></label>
      <form method="POST">
        <div class="menu bg-[#FCFCFC] text-[#363636] min-h-screen w-[600px] flex flex-col justify-center px-8 gap-5">
          <h3 class="text-lg font-semibold mb-7">Update Data Reservation Hotel</h3>

          <input type="hidden" name="action" value="updateReservation">
          <input type="hidden" name="updatePegawaiID" id="updatePegawaiID">
          <input type="hidden" name="updateHewanID" id="updateHewanID">
          <input type="hidden" name="updateReservationID" id="updateReservationID">

          <div class="flex gap-3">
            <div class="flex text-sm flex-col text-[#565656] font-medium w-[60%]">
              <label>Reservator Name</label>
              <div class="relative inline-block w-full mt-2">
                <!-- Dropdown Toggle -->
                <label tabindex="0" class="btn min-h-[2.375rem] h-[2.375rem] max-h-[2.375rem] rounded-full font-normal hover:bg-[#FCFCFC] py-2 px-7 w-full justify-between bg-[#FCFCFC] border border-[#565656] text-[#565656] focus:outline-none focus:ring-[#565656] text-sm">
                  <span disabled id="selectedreservatorIDUpdate">Reservator Name</span>
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 inline float-right" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </label>
              </div>
            </div>
            <div class="flex text-sm flex-col text-[#565656] font-medium w-[40%]">
              <input type="hidden" name="updateKandangID" id="updateKandangID">
              <label>Room No.</label>
              <div class="relative inline-block w-full mt-2">
                <!-- Dropdown Toggle -->
                <label tabindex="0" class="btn btnUpdateKandangID min-h-[2.375rem] h-[2.375rem] max-h-[2.375rem] rounded-full font-normal hover:bg-[#FCFCFC] py-2 px-7 w-full justify-between bg-[#FCFCFC] border border-[#565656] text-[#565656] focus:outline-none focus:ring-[#565656] text-sm">
                  <span id="selectedUpdateKandangID">Room No.</span>
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 inline float-right" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </label>

                <!-- Dropdown Menu -->
                <ul
                  tabindex="0"
                  class="dropdown-content menu menuKandangIDUpdate absolute z-10 mt-2 py-2 px-3 shadow bg-[#FCFCFC] text-[#565656] rounded-2xl w-full border border-[#565656] hidden">
                  <?php foreach ($cageRooms as $cageRoom): ?>
                    <li>
                      <a href="#" class="hover:bg-[#565656] hover:text-semibold hover:text-[#FCFCFC]" id="<?php echo $cageRoom['ID'];  ?>" data-size="<?php echo $cageRoom['UKURAN']; ?>" onclick="handleSelectUpdateKandangID('<?php echo $cageRoom['ID'];  ?>', 'No: <?php echo htmlentities($cageRoom['NOMOR']); ?> | Size: <?php echo htmlentities($cageRoom['UKURAN']); ?>')">
                        No: <?php echo htmlentities($cageRoom['NOMOR']); ?> | Size: <?php echo htmlentities($cageRoom['UKURAN']); ?>
                      </a>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>
            </div>
          </div>

          <div class="flex gap-3">
            <div class="flex text-sm flex-col text-[#565656] font-medium w-[50%]">
              <label for="updateCheckIn">Check-In</label>
              <input type="text" class="mt-2 rounded-full bg-[#FCFCFC] border border-[#565656] text-[#565656] text-sm placeholder:text-[#565656] placeholder:text-sm px-7 py-2" id="updateCheckIn" placeholder="Check-In Date" name="updateCheckIn" required>
            </div>


            <div class="flex text-sm flex-col text-[#565656] font-medium w-[50%]">
              <label for="updateCheckOut">Check-Out</label>
              <input type="text" class="mt-2 rounded-full bg-[#FCFCFC] border border-[#565656] text-[#565656] text-sm placeholder:text-[#565656] placeholder:text-sm px-7 py-2" id="updateCheckOut" placeholder="Check-Out Date" name="updateCheckOut" required>
            </div>
          </div>

          <div class="flex gap-3">
            <div class="flex text-sm flex-col text-[#565656] font-medium w-[60%]">
              <label for="updatePrice">Room Price</label>
              <input type="hidden" name="updatePrice" id="updatePrice">
              <input type="text" class="mt-2 rounded-full bg-[#FCFCFC] border border-[#565656] text-[#565656] text-sm placeholder:text-[#565656] placeholder:text-sm px-7 py-2" id="updateBiaya" placeholder="Rp.---" name="updateBiaya" disabled required>
            </div>
            <div class="flex text-sm flex-col text-[#565656] font-medium w-[40%]">
              <input type="hidden" name="updateStatus" id="updateStatus">
              <label>Status</label>
              <div class="relative inline-block w-full mt-2">
                <!-- Dropdown Toggle -->
                <label tabindex="0" class="btn btnUpdateStatus min-h-[2.375rem] h-[2.375rem] max-h-[2.375rem] rounded-full font-normal hover:bg-[#FCFCFC] py-2 px-7 w-full justify-between bg-[#FCFCFC] border border-[#565656] text-[#565656] focus:outline-none focus:ring-[#565656] text-sm">
                  <span id="selectedUpdateStatus">Status</span>
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 inline float-right" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </label>

                <!-- Dropdown Menu -->
                <ul
                  tabindex="0"
                  class="dropdown-content menu menuStatusUpdate absolute z-10 mt-2 py-2 px-3 shadow bg-[#FCFCFC] text-[#565656] rounded-2xl w-full border border-[#565656] hidden">
                  <li id="liCompletedOption">
                    <a href="#" class="hover:bg-[#565656] hover:text-semibold hover:text-[#FCFCFC]" id="Completed" onclick="handleSelectUpdateStatus('Completed')">
                      Completed
                    </a>
                  </li>
                  <li id="liInProgressOption">
                    <a href="#" class="hover:bg-[#565656] hover:text-semibold hover:text-[#FCFCFC]" id="InProgress" onclick="handleSelectUpdateStatus('InProgress')">
                      In Progress
                    </a>
                  </li>
                  <li id="liScheduledOption">
                    <a href="#" class="hover:bg-[#565656] hover:text-semibold hover:text-[#FCFCFC]" id="Scheduled" onclick="handleSelectUpdateStatus('Scheduled')">
                      Scheduled
                    </a>
                  </li>
                  <li id="liCanceledOption">
                    <a href="#" class="hover:bg-[#565656] hover:text-semibold hover:text-[#FCFCFC]" id="Canceled" onclick="handleSelectUpdateStatus('Canceled')">
                      Canceled
                    </a>
                  </li>
                </ul>
              </div>
            </div>
          </div>
          <div class="flex justify-end gap-5">
            <button type="submit" name="update" class="btn bg-[#B2B5E0] text-[#565656] shadow-md shadow-[#565656] px-3 rounded-full hover:bg-[#565656] hover:text-[#FCFCFC] flex justify-around items-center"><i class="fas fa-edit fa-md"></i> Reservation</button>
            <label for="drawerUpdateReservationHotel" aria-label="close sidebar" class="btn bg-[#E0BAB2] text-[#565656] shadow-md shadow-[#565656] px-3 rounded-full hover:bg-[#565656] hover:text-[#FCFCFC]">Cancel</label>
          </div>
        </div>
      </form>
    </div>
  </div>

  <script>
    const dropdownLabelKandangIDUpdate = document.querySelector('.btnUpdateKandangID');
    const dropdownMenuKandangIDUpdate = document.querySelector('.menuKandangIDUpdate');
    const dropdownItemsKandangIDUpdate = document.querySelectorAll('.menuKandangIDUpdate a');

    const dropdownLabelStatusUpdate = document.querySelector('.btnUpdateStatus');
    const dropdownMenuStatusUpdate = document.querySelector('.menuStatusUpdate');
    const dropdownItemsStatusUpdate = document.querySelectorAll('.menuStatusUpdate a');

    const updateCheckIn = document.getElementById('updateCheckIn');
    const updateCheckOut = document.getElementById('updateCheckOut');
    let updateKandangID = document.getElementById('updateKandangID');
    let updateStatus = document.getElementById('updateStatus');

    let updateCageSize = "";

    dropdownLabelKandangIDUpdate.addEventListener('click', () => {
      dropdownMenuKandangIDUpdate.classList.toggle('hidden');
    });
    dropdownLabelStatusUpdate.addEventListener('click', () => {
      dropdownMenuStatusUpdate.classList.toggle('hidden');
    });

    function handleSelectUpdateKandangID(selectedID, selectedName) {
      const optionUpdateSelected = document.getElementById(selectedID);
      selectUpdateKandangID(selectedName);

      dropdownItemsKandangIDUpdate.forEach(item => {
        item.classList.remove('selected');
      });
      optionUpdateSelected.classList.add('selected');

      updateKandangID.value = selectedID;

      updateCageSize = optionUpdateSelected.dataset.size;

      updatePrice()
    }

    function selectUpdateKandangID(name) {
      document.getElementById('selectedUpdateKandangID').textContent = name;
      dropdownMenuKandangIDUpdate.classList.add('hidden');
    }

    function handleSelectUpdateStatus(selectedStatus) {
      const optionUpdateSelectedStatus = document.getElementById(selectedStatus);
      selectUpdateStatus(selectedStatus);

      dropdownItemsStatusUpdate.forEach(item => {
        item.classList.remove('selected');
      });
      optionUpdateSelectedStatus.classList.add('selected');

      updateStatus.value = selectedStatus;

      updateCageSize = optionUpdateSelectedStatus.dataset.size;

      updatePrice()
    }

    function selectUpdateStatus(status) {
      document.getElementById('selectedUpdateStatus').textContent = status;
      dropdownMenuStatusUpdate.classList.add('hidden');
    }

    function updatePrice() {
      let updateCheckin = document.getElementById('updateCheckIn').value;
      let updateCheckout = document.getElementById('updateCheckOut').value;

      if (!updateCheckin || !updateCheckout) {
        alert('Please select both check-in and check-out dates.');
        return;
      }

      let updateCheckinDate = new Date(updateCheckin);
      let updateCheckoutDate = new Date(updateCheckout);

      if (updateCheckoutDate <= updateCheckinDate) {
        alert('Check-out date must be after check-in date.');
        return;
      }

      let durationInMillis = updateCheckoutDate - updateCheckinDate; // Duration in milliseconds
      let durationInDays = durationInMillis / (1000 * 3600 * 24); // Convert to days

      // Round up to the next whole day if the duration is less than 1 day
      let roundedDuration = Math.ceil(durationInDays);

      let pricePerDay = {
        "XS": 20000,
        "S": 30000,
        "M": 50000,
        "L": 60000,
        "XL": 80000,
        "XXL": 90000,
        "XXXL": 100000
      };

      let price = pricePerDay[updateCageSize] * roundedDuration;

      // Update the price in the form
      document.getElementById('updatePrice').value = price; // Set hidden input value
      document.getElementById('updateBiaya').value = "Rp " + price.toLocaleString();
    }
  </script>
</body>

</html>