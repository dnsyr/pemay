<?php
session_start();
ob_start();
require_once '../../config/database.php';
include '../../handlers/pet-hotel-and-cage.php';

$pageTitle = 'Pet Hotel';

include '../../layout/header-tailwind.php';
include '../../components/drawer/update-pet-hotel-reservation.php';
include '../../components/modal/delete-pet-hotel-reservation.php';
include '../../components/modal/delete-cage.php';

$pegawaiID = $_SESSION['employee_id'];

if (!isset($_SESSION['username'])) {
  header("Location: ../../auth/restricted.php");
  exit();
}

function formatTimestamp($timestamp)
{
  try {
    // Create a DateTime object from the input timestamp
    $dateTime = DateTime::createFromFormat('Y-m-d H:i:s', $timestamp);

    // Check if parsing was successful
    if ($dateTime) {
      // Format the date to the desired format
      return $dateTime->format('d M Y, h:i A');
    } else {
      // Handle invalid timestamp
      return "Invalid timestamp: $timestamp";
    }
  } catch (Exception $e) {
    // Handle exceptions
    return "Error formatting timestamp: " . $e->getMessage();
  }
}

// Initialize Database class
$db = new Database();

// Handle Create or Delete (Reservation & Cage)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  if ($_POST['action'] === 'addReservation') {
    createDataReservation($db);
  } elseif ($_POST['action'] === 'updateReservation') {
    $reservationID = $_POST['updateReservationID'];
    updateDataReservation($db, $reservationID);
  } elseif ($_POST['action'] === 'addCage') {
    createDataCage($db);
  } elseif ($_POST['action'] === 'deleteReservation') {
    deleteDataReservation($db);
  } elseif ($_POST['action'] === 'deleteCage') {
    deleteDataCage($db);
  }
}

$petAndOwnerNameQuery = "SELECT h.ID AS ID, h.NAMA AS NAMA, ph.NAMA AS PEMILIK 
                         FROM HEWAN h
                         JOIN PEMILIKHEWAN ph ON h.PEMILIKHEWAN_ID = ph.ID
                         ORDER BY ph.NAMA";

$db->query($petAndOwnerNameQuery);
$petAndOwnerNames = $db->resultSet(); // Fetch all results for pet and owner names

$petHotelReservations = getAllDataReservations($db);
$cageRooms = getAllDataCages($db);

ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <style>
    #select2-ukuran-results {
      display: flex;
      gap: 12px;
    }

    #select2-reservatorID-results {
      display: flex;
      flex-wrap: wrap;
    }

    #select2-reservatorID-results>li {
      min-width: 25%;
      max-width: 25%;
    }

    .w-10 {
      width: 10%;
    }

    .menuReservatorID a.selected,
    .menuKandangID a.selected,
    .menuKandangIDUpdate a.selected,
    .menuStatusUpdate a.selected {
      text-underline-offset: 5px;
      text-decoration: underline;
      text-decoration-color: #565656;
      text-decoration-thickness: 2px;
    }
  </style>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script src="/pemay/public/js/handleFormReservationHotel.js?v=<?php echo time(); ?>"></script>
  <script src="/pemay/public/js/handleFormUpdateReservationHotel.js?v=<?php echo time(); ?>"></script>
</head>

<body>
  <div class="pb-6 px-12 text-[#363636]">
    <div class="flex justify-between mb-6">
      <h2 class="text-3xl font-bold italic">Pet Hotel</h2>

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

      <div role="alert" id="alertPetHotel" class="alert bg-[#D4F0EA] py-2 px-7 rounded-full w-fit hidden text-[#363636]">
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
          <button class="btn btn-circle btn-outline w-6 h-6 min-h-fit text-black hover:bg-black hover:text-white border border-2 hover:border-none" onclick="closeAlertPetHotel()">
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
      <!-- Pet Hotel -->
      <input type="radio" name="my_tabs_2" role="tab" checked class="tab text-[#363636] text-base font-semibold [--tab-bg:#D4F0EA] [--tab-border-color:#363636] min-w-[105px] w-[105px]" aria-label="Pet Hotel" />
      <div role="tabpanel" class="tab-content  bg-[#FCFCFC] border-base-300 rounded-box p-6">
        <!-- Form Add Reservation -->
        <div class="w-[62%] h-auto border border-[#565656] rounded-xl text-[#343434] shadow-md py-5 px-7 flex flex-col justify-between gap-4">
          <form method="POST">
            <p class="text-lg tracking-wide font-semibold">Pet Check-In Information</p>

            <div class="flex flex-col gap-3">
              <input type="hidden" name="action" value="addReservation">
              <input type="hidden" name="reservatorID" id="reservatorID">

              <div class="relative inline-block w-full mt-1">
                <!-- Dropdown Toggle -->
                <label tabindex="0" class="btn btnReservatorID min-h-[2.375rem] h-[2.375rem] max-h-[2.375rem] rounded-full font-normal hover:bg-[#FCFCFC] py-2 px-7 w-full justify-between bg-[#FCFCFC] border border-[#565656] text-[#565656] focus:outline-none focus:ring-[#565656] text-sm">
                  <span id="selectedreservatorID">Reservator Name</span>
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 inline float-right" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </label>

                <!-- Dropdown Menu -->
                <ul
                  tabindex="0"
                  class="dropdown-content menu menuReservatorID absolute z-10 mt-2 py-2 px-3 shadow bg-[#FCFCFC] text-[#565656] rounded-2xl w-full border border-[#565656] hidden">
                  <?php foreach ($petAndOwnerNames as $petAndOwnerName): ?>
                    <li>
                      <a href="#" class="hover:bg-[#565656] hover:text-semibold hover:text-[#FCFCFC]" id="<?php echo $petAndOwnerName['ID'];  ?>" onclick="handleSelectReservatorID('<?php echo $petAndOwnerName['ID'];  ?>', 'Pet: <?php echo htmlentities($petAndOwnerName['NAMA']); ?> | Owner: <?php echo htmlentities($petAndOwnerName['PEMILIK']); ?>')">
                        Pet: <?php echo htmlentities($petAndOwnerName['NAMA']); ?> | Owner: <?php echo htmlentities($petAndOwnerName['PEMILIK']); ?>
                      </a>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>

              <div class="flex gap-3">
                <div class="flex text-sm flex-col text-[#565656] font-medium">
                  <label for="checkIn">Check-In</label>
                  <input type="text" class="mt-2 rounded-full bg-[#FCFCFC] border border-[#565656] text-[#565656] text-sm placeholder:text-[#565656] placeholder:text-sm px-7 py-2" id="checkIn" placeholder="Check-In Date" name="checkIn" required disabled>
                </div>
                <div class="flex text-sm flex-col text-[#565656] font-medium">
                  <label for="checkOut">Check-Out</label>
                  <input type="text" class="mt-2 rounded-full bg-[#FCFCFC] border border-[#565656] text-[#565656] text-sm placeholder:text-[#565656] placeholder:text-sm px-7 py-2" id="checkOut" placeholder="Check-Out Date" name="checkOut" required disabled>
                </div>

                <div class="divider divider-horizontal divider-neutral"></div>

                <div class="flex text-sm flex-col text-[#565656] font-medium w-full">
                  <input type="hidden" name="kandangID" id="kandangID">

                  <label>Room No.</label>
                  <div class="relative inline-block w-full mt-2">
                    <!-- Dropdown Toggle -->
                    <label tabindex="0" class="btn btnKandangID min-h-[2.375rem] h-[2.375rem] max-h-[2.375rem] rounded-full font-normal hover:bg-[#FCFCFC] py-2 px-7 w-full justify-between bg-[#FCFCFC] border border-[#565656] text-[#565656] focus:outline-none focus:ring-[#565656] text-sm">
                      <span id="selectedKandangID">Room No.</span>
                      <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 inline float-right" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                      </svg>
                    </label>

                    <!-- Dropdown Menu -->
                    <ul
                      tabindex="0"
                      class="dropdown-content menu menuKandangID absolute z-10 mt-2 py-2 px-3 shadow bg-[#FCFCFC] text-[#565656] rounded-2xl w-full border border-[#565656] hidden">
                      <?php foreach ($cageRooms as $cageRoom): ?>
                        <li>
                          <a href="#" class="hover:bg-[#565656] hover:text-semibold hover:text-[#FCFCFC]" id="<?php echo $cageRoom['ID'];  ?>" data-size="<?php echo $cageRoom['UKURAN']; ?>" onclick="handleSelectKandangID('<?php echo $cageRoom['ID'];  ?>', 'No: <?php echo htmlentities($cageRoom['NOMOR']); ?> | Size: <?php echo htmlentities($cageRoom['UKURAN']); ?>')">
                            No: <?php echo htmlentities($cageRoom['NOMOR']); ?> | Size: <?php echo htmlentities($cageRoom['UKURAN']); ?>
                          </a>
                        </li>
                      <?php endforeach; ?>
                    </ul>
                  </div>
                </div>
              </div>

              <div class="flex gap-3">
                <div class="flex text-sm flex-col text-[#565656] font-medium w-[65%]">
                  <label for="price">Room Price</label>
                  <input type="hidden" name="price" id="price">
                  <input type="text" class="mt-2 rounded-full bg-[#FCFCFC] border border-[#565656] text-[#565656] text-sm placeholder:text-[#565656] placeholder:text-sm px-7 py-2" id="biaya" placeholder="Rp.---" name="biaya" disabled required>
                </div>
                <div class="flex text-smtext-[#565656] font-medium w-[34%] items-end">
                  <button disabled id="btnAddReservation" class="bg-[#B2B5E0] text rounded-full border border-[#565656] text-black text-md font-semibold py-2 px-5 w-full" type="submit"><i class="fa-solid fa-plus"></i> Add Item</button>
                </div>
              </div>
            </div>
          </form>
        </div>

        <div class="divider divider-neutral mt-8"></div>

        <p class="text-lg text-[#363636] font-semibold italic">Listed Pet Stays</p>
        <div class="overflow-hidden border border-[#565656] rounded-xl shadow-md shadow-[#717171] mt-3">
          <table class="table border-collapse">
            <thead>
              <tr class="bg-[#D4F0EA] text-[#363636] font-semibold">
                <th>Reservator Name</th>
                <th>Cage Room</th>
                <th>Check In</th>
                <th>Check Out</th>
                <th>Status</th>
                <th>Price</th>
                <th>Cashier</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($petHotelReservations as $result): ?>
                <!-- Reservation Data -->
                <tr class="<?php if ($result['STATUS'] == 'Completed') {
                              echo 'table-success';
                            } elseif ($result['STATUS'] == 'Scheduled') {
                              echo 'table-secondary';
                            } elseif ($result['STATUS'] == 'In Progress') {
                              echo 'table-info';
                            } elseif ($result['STATUS'] == 'Canceled') {
                              echo 'table-danger';
                            }
                            ?>">
                  <td><?php echo htmlentities($result['HEWAN_NAMA']); ?></td>
                  <td><?php echo htmlentities($result['KANDANG_NOMOR']); ?></td>
                  <td><?php echo htmlentities(formatTimestamp($result['CHECKIN'])); ?></td>
                  <td><?php echo htmlentities(formatTimestamp($result['CHECKOUT'])); ?></td>
                  <td><?php echo htmlentities($result['STATUS']); ?></td>
                  <td>Rp.<?php echo htmlentities($result['TOTALBIAYA']); ?></td>
                  <td><?php echo htmlentities($result['PEGAWAI_NAMA']); ?></td>
                  <!-- Action Button -->
                  <td>
                    <div class="flex gap-3 justify-center items-center">
                      <button
                        type="button"
                        class="btn btn-warning btn-sm"
                        onclick="handleUpdateReservationBtn('<?php echo $result['ID']; ?>', '<?php echo $result['STATUS']; ?>')">
                        <i class="fas fa-edit"></i>
                      </button>

                      <button
                        type="button"
                        class="btn btn-error btn-sm"
                        onclick="handleDeleteReservationBtn('<?php echo $result['ID']; ?>', '<?php echo $result['STATUS']; ?>')">
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

      <!-- Cage -->
      <input
        type="radio"
        name="my_tabs_2"
        role="tab"
        class="tab text-[#363636] text-base font-semibold [--tab-bg:#D4F0EA] [--tab-border-color:#363636]"

        aria-label="Cage" />
      <div role="tabpanel" class="tab-content  bg-[#FCFCFC] border-base-300 rounded-box p-6">
        <!-- Form Add Cage -->
        <div class="w-[40%] h-auto border border-[#565656] rounded-xl text-[#343434] shadow-md py-5 px-7 flex flex-col justify-between gap-4">
          <form method="POST">
            <p class="text-lg tracking-wide font-semibold">Pet Cage Information</p>

            <div class="flex flex-col gap-3 mt-4">
              <input type="hidden" name="action" value="addCage">
              <div class="flex gap-3">
                <div class="flex text-sm flex-col text-[#565656] font-medium w-[65%]">
                  <label for="ukuran">Ukuran</label>
                  <input type="text" class="mt-2 rounded-full bg-[#FCFCFC] border border-[#565656] text-[#565656] text-sm placeholder:text-[#565656] placeholder:text-sm px-7 py-2" id="ukuran" placeholder="Size of Cage" name="ukuran" required>
                </div>
                <div class="flex text-sm text-[#565656] font-medium w-[34%] items-end">
                  <button id="btnAddCage" class="bg-[#B2B5E0] text rounded-full border border-[#565656] text-black text-md font-semibold py-2 px-5 w-full" type="submit"><i class="fa-solid fa-plus"></i> Add Item</button>
                </div>
              </div>
            </div>
          </form>
        </div>

        <div class="divider divider-neutral mt-8"></div>

        <es class="text-lg text-[#363636] font-semibold italic">Listed Cages</es>
        <div class="overflow-hidden border border-[#565656] rounded-xl shadow-md shadow-[#717171] mt-3">
          <table class="table border-collapse">
            <thead>
              <tr class="bg-[#D4F0EA] text-[#363636] font-semibold">
                <th>Nomor</th>
                <th>Ukuran</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($cageRooms as $result): ?>
                <!-- Reservation Data -->
                <tr class="<?php if ($result['STATUS'] == 'Completed') {
                              echo 'table-success';
                            } elseif ($result['STATUS'] == 'Scheduled') {
                              echo 'table-secondary';
                            } elseif ($result['STATUS'] == 'In Progress') {
                              echo 'table-info';
                            } elseif ($result['STATUS'] == 'Canceled') {
                              echo 'table-danger';
                            }
                            ?>">
                  <td><?php echo htmlentities($result['NOMOR']); ?></td>
                  <td><?php echo htmlentities($result['UKURAN']); ?></td>
                  <td><?php echo htmlentities($result['STATUS']); ?></td>
                  <!-- Action Button -->
                  <td>
                    <div class="flex gap-3 justify-start items-center">
                      <button
                        type="button"
                        class="btn btn-error btn-sm"
                        onclick="handleDeleteCageBtn('<?php echo $result['ID']; ?>', '<?php echo $result['STATUS']; ?>')">
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
  </div>
</body>

<script>
  // Custom Dynamic Alert
  function showAlertPetHotel(message) {
    const alert = document.getElementById('alertPetHotel')
    const spanElement = alert.querySelector('span');

    spanElement.textContent = message;
    alert.classList.remove('hidden')
  }

  function closeAlertPetHotel() {
    document.getElementById('alertPetHotel').classList.add('hidden')
  }

  // HANDLE Delete
  function handleDeleteReservationBtn(reservationID, reservationStatus) {
    if (reservationStatus === "Scheduled" || reservationStatus === "In Progress" || reservationStatus === "Completed") {
      showAlertPetHotel("You can't delete 'Scheduled', 'In Progress', 'Completed' Reservation")
      window.scrollTo({
        top: 0, // Scroll to the top
        behavior: 'smooth' // Smooth scrolling animation
      });
    } else {
      document.getElementById('delete_id').value = reservationID;
      document.getElementById('modalDeleteReservationHotel').showModal()
    }
  }

  function handleDeleteCageBtn(cageID, cageStatus) {
    console.log("cageid", cageID, cageStatus);

    if (cageStatus === "Filled" || cageStatus === "Scheduled") {
      showAlertPetHotel("You can't delete 'Scheduled' & 'Filled' Cage")
      window.scrollTo({
        top: 0, // Scroll to the top
        behavior: 'smooth' // Smooth scrolling animation
      });
    } else {
      document.getElementById('deleteCageID').value = cageID;
      document.getElementById('modalDeleteCage').showModal()
    }
  }

  // HANDLE UPDATE
  const reservations = <?php echo json_encode(array_reduce($petHotelReservations, function ($carry, $reservation) {
                          $carry[$reservation['ID']] = [
                            'reservationID' => $reservation['ID'],
                            'hewanID' => $reservation['HEWAN_ID'],
                            'pegawaiID' => $reservation['PEGAWAI_ID'],
                            'kandangID' => $reservation['KANDANG_ID'],
                            'status' => $reservation['STATUS'],
                            'totalBiaya' => $reservation['TOTALBIAYA'],
                            'checkIn' => $reservation['CHECKIN'],
                            'checkOut' => $reservation['CHECKOUT'],
                            'hewanNama' => $reservation['HEWAN_NAMA'],
                            'kandangNomor' => $reservation['KANDANG_NOMOR'],
                            'kandangUkuran' => $reservation['KANDANG_UKURAN'],
                          ];
                          return $carry;
                        }, [])); ?>;

  function getDataReservation(reservationID) {
    console.log("data reservation", reservations[reservationID]);

    return reservations[reservationID] || {};
  }

  function handleUpdateReservationBtn(reservationID, reservationStatus) {
    if (reservationStatus === "Canceled" || reservationStatus === "Completed") {
      showAlertPetHotel("You can't update 'Canceled' & 'Completed' Reservation")
      window.scrollTo({
        top: 0, // Scroll to the top
        behavior: 'smooth' // Smooth scrolling animation
      });
    } else {
      const reservationData = getDataReservation(reservationID);

      document.getElementById('updateReservationID').value = reservationData.reservationID;
      document.getElementById('updateHewanID').value = reservationData.hewanID;
      document.getElementById('selectedreservatorIDUpdate').textContent = reservationData.hewanNama;
      document.getElementById('updatePegawaiID').value = reservationData.pegawaiID;
      document.getElementById('updateKandangID').value = reservationData.kandangID;
      document.getElementById('updateStatus').value = reservationData.status;
      document.getElementById('updatePrice').value = reservationData.totalBiaya;
      document.getElementById('updateBiaya').value = "Rp " + (reservationData.totalBiaya).toLocaleString();
      document.getElementById('updateCheckIn').value = reservationData.checkIn;
      document.getElementById('updateCheckOut').value = reservationData.checkOut;

      handleSelectUpdateKandangID(reservationData.kandangID, `No: ${reservationData.kandangNomor} | Size: ${reservationData.kandangUkuran}`)

      let cancelOption = document.getElementById('liCanceledOption')
      let scheduledOption = document.getElementById('liScheduledOption')
      let inProgressOption = document.getElementById('liInProgressOption')
      let completedOption = document.getElementById('liCompletedOption')

      if (reservationData.status === "In Progress") {
        handleSelectUpdateStatus("InProgress")
        cancelOption.hidden = true;
        scheduledOption.hidden = false;
        inProgressOption.hidden = false;
        completedOption.hidden = false;
      } else if (reservationData.status === "Scheduled") {
        handleSelectUpdateStatus(reservationData.status)
        cancelOption.hidden = false;
        scheduledOption.hidden = false;
        inProgressOption.hidden = false;
        completedOption.hidden = true;
      }

      document.getElementById('drawerUpdateReservationHotel').checked = true;
    }
  }

  // FORM ADD RESERVATION
  const dropdownLabelReservatorID = document.querySelector('.btnReservatorID');
  const dropdownMenuReservatorID = document.querySelector('.menuReservatorID');
  const dropdownItemsReservatorID = document.querySelectorAll('.menuReservatorID a');

  const dropdownLabelKandangID = document.querySelector('.btnKandangID');
  const dropdownMenuKandangID = document.querySelector('.menuKandangID');
  const dropdownItemsKandangID = document.querySelectorAll('.menuKandangID a');

  const checkIn = document.getElementById('checkIn');
  const checkOut = document.getElementById('checkOut');
  let reservatorID = document.getElementById('reservatorID');
  let kandangID = document.getElementById('kandangID');

  let cageSize = "";

  dropdownLabelReservatorID.addEventListener('click', () => {
    dropdownMenuReservatorID.classList.toggle('hidden');
  });

  dropdownLabelKandangID.addEventListener('click', () => {
    dropdownMenuKandangID.classList.toggle('hidden');
  });

  function handleSelectReservatorID(selectedID, selectedName) {
    const optionSelected = document.getElementById(selectedID);
    selectedReservatorID(selectedName);

    dropdownItemsReservatorID.forEach(item => {
      item.classList.remove('selected');
    });
    optionSelected.classList.add('selected');

    reservatorID.value = selectedID;

    checkIn.disabled = false;

    undisabledBtnAddReservation()
  }

  function selectedReservatorID(name) {
    document.getElementById('selectedreservatorID').textContent = name;
    dropdownMenuReservatorID.classList.add('hidden');
  }

  function handleSelectKandangID(selectedID, selectedName) {
    const optionSelectedKandangID = document.getElementById(selectedID);
    selectKandangID(selectedID, selectedName);

    dropdownItemsKandangID.forEach(item => {
      item.classList.remove('selected');
    });
    optionSelectedKandangID.classList.add('selected');

    kandangID.value = selectedID;

    cageSize = optionSelectedKandangID.dataset.size;

    undisabledBtnAddReservation()

    checkPrice()
  }

  function selectKandangID(id, name) {
    document.getElementById('selectedKandangID').textContent = name;
    dropdownMenuKandangID.classList.add('hidden');
  }

  function undisabledBtnAddReservation() {
    if (reservatorID.value != "" && checkIn.value != "" && checkOut.value != "" && kandangID.value != "") {
      let btnAddReservation = document.getElementById('btnAddReservation')

      btnAddReservation.disabled = false;
    }
  }

  function checkPrice() {
    let checkin = document.getElementById('checkIn').value;
    let checkout = document.getElementById('checkOut').value;

    if (!checkin || !checkout) {
      alert('Please select both check-in and check-out dates.');
      return;
    }

    let checkinDate = new Date(checkin);
    let checkoutDate = new Date(checkout);

    if (checkoutDate <= checkinDate) {
      alert('Check-out date must be after check-in date.');
      return;
    }

    let durationInMillis = checkoutDate - checkinDate; // Duration in milliseconds
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

    let price = pricePerDay[cageSize] * roundedDuration;

    // Update the price in the form
    document.getElementById('price').value = price; // Set hidden input value
    document.getElementById('biaya').value = "Rp " + price.toLocaleString();
  }
</script>

</html>