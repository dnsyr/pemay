<?php
session_start();
include '../../config/connection.php';
require_once '../../config/database.php';

$pageTitle = 'Pet Hotel';

// Ensure the session position is valid
if (!isset($_SESSION['posisi']) || !in_array($_SESSION['posisi'], ['owner', 'vet', 'staff'])) {
  header("Location: ../../auth/restricted.php");
  exit();
}

// Include role-specific headers
switch ($_SESSION['posisi']) {
  case 'owner':
    include '../owner/header.php';
    break;
  case 'vet':
    include '../vet/header.php';
    break;
  case 'staff':
    include '../staff/header.php';
    break;
}

$pegawaiID = intval($_SESSION['pegawai_id']);

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

$message = "";

function formatTimestamp($timestamp)
{
  try {
    // Create a DateTime object from the input timestamp
    $dateTime = DateTime::createFromFormat('d-M-y h.i.s.u A', $timestamp);

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

// Handle Create Reservation & Cage
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  try {
    $db->beginTransaction(); // Begin the transaction

    if ($_POST['action'] === 'addReservation') {
      $reservatorID = isset($_POST['reservatorID']) ? (int)$_POST['reservatorID'] : 1;
      $kandang = isset($_POST['kandang']) ? (int)$_POST['kandang'] : 1;
      $checkIn;
      $checkOut;
      $price;

      $sqlAddReservation = "INSERT INTO $currentTable (Hewan_ID, Kandang, CheckIn, CheckOut, TotalBiaya, Status, Pegawai_ID) VALUES (:reservatorID, :kandang, :checkIn, :checkOut, :price, :booked, :pegawaiID)";

      $db->query($sqlAddReservation);
      $db->bind(':reservatorID', $reservatorID);
      $db->bind(':kandang', $kandang);
      $db->bind(':checkIn', $checkIn);
      $db->bind(':checkOut', $checkOut);
      $db->bind(':price', $price);
      $db->bind(':booked', 'Booked');
      $db->bind(':pegawaiID', $pegawaiID);

      if ($db->execute()) {
        $message = "$currentLabel berhasil ditambahkan.";
      } else {
        $message = "Gagal menambahkan $currentLabel.";
      }
    } elseif ($_POST['action'] === 'addCage') {
      // Handle Create Cage
      $ukuran = trim($_POST['ukuran']);

      $sqlAddCage = "INSERT INTO $currentTable (Ukuran, Status) VALUES (:ukuran, :status)";
      $db->query($sqlAddCage);
      $db->bind(':ukuran', $ukuran);
      $db->bind(':status', 'Empty');

      if ($db->execute()) {
        $message = "$currentLabel berhasil ditambahkan.";
      } else {
        $message = "Gagal menambahkan $currentLabel.";
      }
    }

    // Commit the transaction after successful execution
    $db->commit();


    sleep(1); // delay second
    header("Location: /pemay/pages/pet-hotel/dashboard.php?tab=cage");
    exit();
  } catch (PDOException $e) {
    // Rollback if there is an error
    $db->rollBack();
    $message = "Error: " . $e->getMessage();
  }
}

// Handle Delete
if (isset($_GET['delete_id'])) {
  try {
    $db->beginTransaction();

    $deleteId = $_GET['delete_id'];
    $sqlReservation = "UPDATE $currentTable SET onDelete = 1 WHERE ID = :id";
    $sqlKandang = "UPDATE $currentTable SET onDelete = 1 WHERE Nomor = :id";

    $db->query($tab == 'reservation' ? $sqlReservation : $sqlKandang);

    $db->bind(':id', $deleteId); // Bind the ID from the URL to the query

    if ($db->execute()) {
      $message = "$currentLabel berhasil dihapus.";
    } else {
      $message = "Gagal menghapus $currentLabel.";
    }
    $db->commit();

    sleep(2); // delay second
    header("Location: /pemay/pages/pet-hotel/dashboard.php?tab=cage");
    exit();
  } catch (PDOException $e) {
    // Rollback if there is an error
    $db->rollBack();
    $message = "Error: " . $e->getMessage();
  }
}

// Fetch Data
$sqlQuery = $tab === 'reservation' ? "SELECT lh.*, h.NAMA AS HEWAN_NAMA FROM $currentTable lh JOIN HEWAN h ON lh.HEWAN_ID = h.ID WHERE lh.onDelete = 0 ORDER BY lh.CheckOut" : "SELECT * FROM $currentTable WHERE onDelete = 0 ORDER BY Nomor";

$db->query($sqlQuery);
$results = $db->resultSet();
?>

<!DOCTYPE html>
<html lang="en">

<body>
  <div class="page-container">
    <h2>Pet Hotel</h2>
    <?php if ($message != ""): ?>
      <div class="alert alert-info">
        <?php echo htmlentities($message); ?>
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
          <?php if ($tab === 'reservation'): ?>
            <input type="hidden" name="action" value="addReservation">

            <div>
              <div class="d-flex gap-5">
                <div class="mb-3 w-100">
                  <label for="reservatorID" class="form-label">Reservator Name</label>
                  <input type="text" class="form-control" id="reservatorID" name="reservatorID" required>
                </div>
              </div>

              <div class="d-flex gap-4">
                <div class="mb-3 w-25">
                  <label for="kandang" class="form-label">Cage Room</label>
                  <input type="number" class="form-control" id="kandang" name="kandang" required>
                </div>
                <div class="mb-3 w-25">
                  <label for="checkIn" class="form-label">Check In</label>
                  <input type="datetime-local" class="form-control" id="checkIn" name="checkIn" required>
                </div>
                <div class="mb-3 w-25">
                  <label for="checkOut" class="form-label">Check Out</label>
                  <input type="datetime-local" class="form-control" id="checkOut" name="checkOut" required>
                </div>
                <div class="mb-3 w-25">
                  <label for="biaya" class="form-label">Price</label>
                  <input type="number" class="form-control" id="biaya" name="biaya" required>
                </div>
              </div>
            <?php endif; ?>

            <!-- Form Cage -->
            <?php if ($tab === 'cage'): ?>
              <input type="hidden" name="action" value="addCage">
              <!-- <div class="mb-3">
                <label for="ukuran" class="form-label"><?php echo $currentLabel; ?> Size</label>
                <select class="form-select" name="ukuran" id="ukuran" required>
                  <option value="" disabled selected>-- Choose Size --</option>
                  <option value="XS">XS</option>
                  <option value="S">S</option>
                  <option value="M">M</option>
                  <option value="L">L</option>
                  <option value="XL">XL</option>
                  <option value="XXL">XXL</option>
                  <option value="XXXL">XXXL</option>
                </select>
              </div> -->

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
                <tr>
                  <td><?php echo htmlentities($result['HEWAN_NAMA']); ?></td>
                  <td><?php echo htmlentities($result['KANDANG_NOMOR']); ?></td>
                  <td><?php echo htmlentities(formatTimestamp($result['CHECKIN'])); ?></td>
                  <td><?php echo htmlentities(formatTimestamp($result['CHECKOUT'])); ?></td>
                  <td><?php echo htmlentities($result['STATUS']); ?></td>
                  <td><?php echo htmlentities($result['TOTALBIAYA']); ?></td>
                  <!-- Action Button -->
                  <td>
                    <button
                      class="btn btn-warning btn-sm"
                      onclick="alert('Edit');">
                      Edit
                    </button>
                    <a href="?tab=<?php echo $tab; ?>&delete_id=<?php echo $result['ID']; ?>"
                      class="btn btn-danger btn-sm"
                      onclick="return confirm('Are you sure you want to delete this item?');">
                      Delete
                    </a>
                  </td>
                </tr>
              <?php elseif ($tab === 'cage'): ?>
                <tr class="<?php if ($result['STATUS'] == 'Empty') {
                              echo 'table-success';
                            } elseif ($result['STATUS'] == 'Booked') {
                              echo 'table-secondary';
                            } elseif ($result['STATUS'] == 'Filled') {
                              echo 'table-primary';
                            } elseif ($result['STATUS'] == 'Emergency') {
                              echo 'table-danger';
                            }
                            ?>">
                  <td><?php echo htmlentities($result['NOMOR']); ?></td>
                  <td><?php echo htmlentities($result['UKURAN']); ?></td>
                  <td><?php echo htmlentities($result['STATUS']); ?></td>

                  <!-- Delete Button -->
                  <td>
                    <?php if ($result['STATUS'] !== 'Empty'): ?>
                      <button
                        class="btn btn-danger btn-sm"
                        onclick="alert('Cannot remove cage while used');">
                        Delete
                      </button>
                    <?php elseif ($result['STATUS'] === 'Empty'): ?>
                      <a href="?tab=<?php echo $tab; ?>&delete_id=<?php echo $result['NOMOR']; ?>"
                        class="btn btn-danger btn-sm"
                        onclick="return confirm('Are you sure you want to delete this item?');">
                        Delete
                      </a>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endif; ?>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Searchable Dropdown -->
    <script>
      $(document).ready(function() {
        $('#ukuran').select2({
          placeholder: 'Select an option',
          allowClear: true
        });
      });
    </script>
</body>

</html>