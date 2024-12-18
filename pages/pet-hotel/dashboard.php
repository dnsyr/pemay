<?php
session_start();
ob_start();
require_once '../../config/database.php';
include '../../handlers/pet-hotel-and-cage.php';

$pageTitle = 'Pet Hotel';
include '../../layout/header.php';

$pegawaiID = $_SESSION['employee_id'];

if (!isset($_SESSION['username'])) {
  header("Location: ../../auth/restricted.php");
  exit();
}

// Default tab
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'reservation';

// Define table mapping for tabs
$tables = [
  'reservation' => ['table' => 'LayananHotel', 'label' => 'Reservation'],
  'cage' => ['table' => 'Kandang', 'label' => 'Cage']
];

// Validate tab and set defaults if invalid
if (!array_key_exists($tab, $tables)) {
  $tab = 'reservation';
}

$currentTable = $tables[$tab]['table'];
$currentLabel = $tables[$tab]['label'];

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
  } elseif ($_POST['action'] === 'addCage') {
    include '../../handlers/add-cage.php';
  } elseif ($_POST['action'] === 'deleteReservation') {
    deleteDataReservation($db);
  } elseif ($_POST['action'] === 'deleteCage') {
    deleteDataCage($db);
  }
}

// Fetch Data
$results = $tab === 'reservation'
  ? getAllDataReservations($db)
  : getAllDataCages($db);

$petAndOwnerNameQuery = "SELECT h.ID AS ID, h.NAMA AS NAMA, ph.NAMA AS PEMILIK 
                         FROM HEWAN h
                         JOIN PEMILIKHEWAN ph ON h.PEMILIKHEWAN_ID = ph.ID
                         ORDER BY ph.NAMA";

$db->query($petAndOwnerNameQuery);
$petAndOwnerNames = $db->resultSet(); // Fetch all results for pet and owner names

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
  </style>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script src="/pemay/public/js/handleFormReservationHotel.js?v=<?php echo time(); ?>"></script>
</head>

<body>
  <div class="page-container">
    <h2>Pet Hotel</h2>

    <!-- Alert -->
    <?php if (isset($_SESSION['success_message']) && $_SESSION['success_message'] !== ""): ?>
      <div class="alert alert-info">
        <?php echo htmlentities($_SESSION['success_message']);
        unset($_SESSION['success_message']); ?>
      </div>
    <?php elseif (isset($_SESSION['error_message']) && $_SESSION['error_message'] !== ""): ?>
      <div class="alert alert-danger">
        <?php echo htmlentities($_SESSION['error_message']);
        unset($_SESSION['error_message']); ?>
      </div>
    <?php endif; ?>

    <!-- Tabs for Category Types -->
    <ul class="nav nav-tabs">
      <?php foreach ($tables as $key => $table): ?>
        <li class="nav-item">
          <a class="nav-link <?php echo $tab === $key ? 'active' : ''; ?>" href="?tab=<?php echo $key; ?>">
            <?php echo htmlentities($table['label']); ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>

    <div class="mt-3">
      <!-- Add Form -->
      <div class="d-flex flex-column gap-3">
        <form method="POST" action="?tab=<?php echo $tab; ?>">
          <!-- Form Reservation -->
          <div>
            <?php if ($tab === 'reservation'): ?>
              <input type="hidden" name="action" value="addReservation">

              <div class="d-flex gap-5">
                <div class="mb-3 w-100">
                  <label for="reservatorID" class="form-label">Reservator Name</label>
                  <select name="reservatorID" id="reservatorID" class="form-select" required>
                    <option value="" disabled selected>-- Choose Pet and Owner --</option>
                    <?php foreach ($petAndOwnerNames as $petAndOwnerName): ?>
                      <option value="<?php echo $petAndOwnerName['ID'];  ?>">
                        Pet: <?php echo htmlentities($petAndOwnerName['NAMA']); ?> | Owner: <?php echo htmlentities($petAndOwnerName['PEMILIK']); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>

              <div class="d-flex gap-4">
                <div class="mb-3 w-25 flatpickr">
                  <label for="checkIn" class="form-label">Check In</label>
                  <input type="text" class="form-control" id="checkIn" placeholder="Select Date Time" name="checkIn" required disabled>
                </div>
                <div class="mb-3 w-25 flatpickr">
                  <label for="checkOut" class="form-label">Check Out</label>
                  <input type="text" class="form-control" id="checkOut" placeholder="Select Date Time" name="checkOut" required disabled>
                </div>
                <div class="mb-3 w-25">
                  <label for="kandang" class="form-label" id="cageLabel" style="display: none;">Cage Room</label>
                  <select name="kandang" id="kandang" class="form-select" required>
                    <option value="" disabled selected>-- Choose Cage Room --</option>
                    <?php foreach ($cageRooms as $cageRoom): ?>
                      <option value="<?php echo $cageRoom['ID']; ?>" data-size="<?php echo $cageRoom['UKURAN']; ?>">
                        No: <?php echo htmlentities($cageRoom['NOMOR']); ?> | Size: <?php echo htmlentities($cageRoom['UKURAN']); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="mb-3 w-25">
                  <label for="biaya" class="form-label" id="biayaLabel" style="display: none; margin-bottom: 12px;">Price</label>
                  <input type="hidden" id="price" name="price">
                  <input type="text" class="form-control" id="biaya" name="biaya" placeholder="Price: Rp0" disabled required style="display: none;">
                </div>
              </div>
            <?php endif; ?>

            <!-- Form Cage -->
            <?php if ($tab === 'cage'): ?>
              <input type="hidden" name="action" value="addCage">
              <div class="mb-3">
                <label for="ukuran" class="form-label"><?php echo $currentLabel; ?> Size</label>
                <select name="ukuran" id="ukuran" class="form-select" required>
                  <option value="" disabled selected>-- Choose Size --</option>
                  <option value="XS">XS</option>
                  <option value="S">S</option>
                  <option value="M">M</option>
                  <option value="L">L</option>
                  <option value="XL">XL</option>
                  <option value="XXL">XXL</option>
                  <option value="XXXL">XXXL</option>
                </select>
              </div>
            <?php endif; ?>
          </div>

          <button type="submit" name="add" class="btn btn-add rounded-circle"><i class="fas fa-plus fa-xl"></i></button>
        </form>
      </div>

      <!-- Display Reservation & Cage -->
      <div>
        <table class="table mt-3">
          <thead>
            <tr>
              <!-- Reservation -->
              <?php if ($tab === 'reservation'): ?>
                <th>Reservator Name</th>
                <th>Cage Room</th>
                <th>Check In</th>
                <th>Check Out</th>
                <th>Status</th>
                <th>Price</th>
                <th>Cashier</th>
              <?php endif; ?>

              <!-- Cage -->
              <?php if ($tab === 'cage'): ?>
                <th>Nomor</th>
                <th>Ukuran</th>
                <th>Status</th>
              <?php endif; ?>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($results as $result): ?>
              <!-- Reservation Data -->
              <?php if ($tab === 'reservation'): ?>
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
                    <div class="d-flex gap-3">
                      <?php if ($result['STATUS'] === 'Completed' || $result['STATUS'] === 'Canceled'): ?>
                        <button
                          class="btn btn-warning btn-sm"
                          onclick="alert('Cannot update this reservation!');">
                          Update
                        </button>
                      <?php else: ?>
                        <a href="update-reservation.php?id=<?php echo $result['ID']; ?>"
                          class="btn btn-warning btn-sm">
                          Update
                        </a>
                      <?php endif; ?>

                      <?php if ($result['STATUS'] === 'In Progress' || $result['STATUS'] === 'Scheduled'): ?>
                        <button
                          class="btn btn-danger btn-sm"
                          onclick="alert('Cannot remove this reservation!');">
                          Delete
                        </button>
                      <?php else: ?>
                        <form method="POST" action="?tab=<?php echo $tab; ?>">
                          <input type="hidden" name="action" value="deleteReservation">
                          <input type="hidden" name="delete_id" value="<?php echo $result['ID']; ?>">
                          <button
                            type="submit"
                            class="btn btn-danger btn-sm"
                            onclick="return confirm('Are you sure you want to delete this item?');">
                            Delete
                          </button>
                        </form>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php elseif ($tab === 'cage'): ?>
                <tr class="<?php if ($result['STATUS'] == 'Empty') {
                              echo 'table-success';
                            } elseif ($result['STATUS'] == 'Scheduled') {
                              echo 'table-secondary';
                            } elseif ($result['STATUS'] == 'Filled') {
                              echo 'table-info';
                            } elseif ($result['STATUS'] == 'Emergency') {
                              echo 'table-danger';
                            }
                            ?>">
                  <td><?php echo htmlentities($result['NOMOR']); ?></td>
                  <td><?php echo htmlentities($result['UKURAN']); ?></td>
                  <td><?php echo htmlentities($result['STATUS']); ?></td>

                  <!-- Delete Button -->
                  <td>
                    <div class="d-flex gap-3">
                      <?php if ($result['STATUS'] !== 'Empty'): ?>
                        <button
                          class="btn btn-danger btn-sm"
                          onclick="alert('Cannot remove cage while used');">
                          Delete
                        </button>
                      <?php elseif ($result['STATUS'] === 'Empty'): ?>
                        <form method="POST" action="?tab=<?php echo $tab; ?>">
                          <input type="hidden" name="action" value="deleteCage">
                          <input type="hidden" name="delete_id" value="<?php echo $result['NOMOR']; ?>">
                          <button
                            type="submit"
                            class="btn btn-danger btn-sm"
                            onclick="return confirm('Are you sure you want to delete this item?');">
                            Delete
                          </button>
                        </form>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endif; ?>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
</body>

</html>