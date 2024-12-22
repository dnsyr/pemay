<?php
session_start();
ob_start();
include '../../config/connection.php';
include '../../config/database.php';
include '../../handlers/pet-hotel-and-cage.php';

$pageTitle = 'Update Reservation';
include '../../layout/header-tailwind.php';

if (isset($_GET['id'])) {
  $reservationID = $_GET['id'];

  $db = new Database();
  $dataReservation = getDataReservation($db, $reservationID);
  $dataReservation = $dataReservation[0];
} else {
  header("Location: dashboard.php");
  $_SESSION['error_message'] = 'Invalid Request ID!';
  exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  updateDataReservation($db, $reservationID);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <style>
    .w-40 {
      width: 40%;
    }
  </style>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

  <script src="/pemay/public/js/handleFormUpdateReservationHotel.js?v=<?php echo time(); ?>"></script>
</head>

<body>
  <div class="page-container">
    <h2>Update Reservation</h2>

    <form method="POST">
      <div class="d-flex flex-column gap-3">
        <div class="d-flex gap-5">
          <input type="hidden" name="pegawaiID" value="<?php echo $dataReservation['PEGAWAI_ID']; ?>">
          <div class="form-group col-md-4">
            <label for="nama">Reservator Name</label>
            <input type="hidden" name="hewanID" value="<?php echo $dataReservation['HEWAN_ID']; ?>">
            <input type="text" class="form-control" name="placeholderNama" value="<?php echo htmlentities($dataReservation['NAMA_HEWAN']); ?>" disabled required>
          </div>
          <div class="form-group col-md-4">
            <label for="kandang">Cage Room</label>
            <input type="hidden" name="kandangID" id="kandangID" value="<?php echo $dataReservation['KANDANG_ID']; ?>">
            <input type="hidden" name="kandangSize" id="kandangSize" value="<?php echo $dataReservation['KANDANG_UKURAN']; ?>">
            <input type="text" class="form-control" name="placeholderKandang" value="<?php echo htmlentities($dataReservation['KANDANG_NOMOR'] . " | Size: " . $dataReservation['KANDANG_UKURAN']); ?>" disabled required>
          </div>
        </div>
        <div class="d-flex gap-5">
          <div class="form-group col-md-4">
            <label for="updateCheckIn">Check In</label>
            <input type="text" class="form-control" id="updateCheckIn" name="updateCheckIn" value="<?php echo htmlentities($dataReservation['CHECKIN']); ?>" required>
          </div>
          <div class="form-group col-md-4">
            <label for="updateCheckOut">Check Out</label>
            <input type="text" class="form-control" id="updateCheckOut" name="updateCheckOut" value="<?php echo htmlentities($dataReservation['CHECKOUT']); ?>" required>
          </div>
        </div>
        <div class="d-flex gap-5">
          <div class="form-group col-md-4">
            <label>Status</label>
            <select class="form-select" name="status" required>
              <option value="Scheduled" <?php echo $dataReservation['STATUS'] === 'Scheduled' ? 'selected' : ''; ?>>Scheduled</option>
              <option value="In Progress" class="<?php echo $dataReservation['STATUS'] === 'In Progress' ? 'd-none' : ''; ?>" <?php echo $dataReservation['STATUS'] === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
              <option value="Completed" class="<?php echo $dataReservation['STATUS'] !== 'In Progress' ? 'd-none' : ''; ?>">Completed</option>
              <option value="Canceled">Canceled</option>
            </select>
          </div>
          <div class="form-group col-md-4">
            <label>Price</label>
            <div class="d-flex">
              <input type="hidden" id="updatePrice" name="updatePrice">
              <input type="text" class="form-control" id="updatePlaceholder" name="updatePlaceholder" placeholder="Price: Rp<?php echo $dataReservation['TOTALBIAYA']; ?>" disabled required>
            </div>
          </div>
        </div>

        <div class="d-flex gap-3 mt-5">
          <button type="submit" name="update" id="updateBtn" class="btn btn-warning">Update</button>
          <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
  </div>
</body>

</html>