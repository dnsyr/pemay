<?php
session_start();
include '../../config/connection.php';
require_once '../../config/database.php';

if (!isset($_GET['id'])) {
  $_SESSION['error_message'] = 'Invalid Request ID!';
}

$pageTitle = 'Update Reservation';
include '../../layout/header.php';

$reservationID = trim($_GET['id']);
$db = new Database();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $db->beginTransaction();

  include '../../handlers/update-pet-hotel-reservation.php';
}

$sql = "SELECT lh.*, h.nama AS Nama FROM LayananHotel lh JOIN Hewan h ON lh.Hewan_ID = h.ID WHERE lh.ID = :id";
$db->query($sql);
$db->bind(':id', $reservationID);
$reservation = $db->single();

if (!$reservation) {
  die('Reservation not found');
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

  <script src="/pemay/public/js/handleFormUpdateReservationHotel.js"></script>
</head>

<body>
  <div class="page-container">
    <h2>Update Reservation</h2>

    <form method="POST">
      <div class="d-flex flex-column gap-3">
        <div class="d-flex gap-5">
          <div class="form-group col-md-4">
            <label for="nama">Reservator Name</label>
            <input type="text" class="form-control" name="nama" value="<?php echo htmlentities($reservation['NAMA']); ?>" disabled required>
          </div>
          <div class="form-group col-md-4">
            <label for="kandang">Cage Room</label>
            <input type="number" class="form-control" name="kandang" value="<?php echo htmlentities($reservation['KANDANG_NOMOR']); ?>" disabled required>
          </div>
        </div>
        <div class="d-flex gap-5">
          <div class="form-group col-md-4">
            <label>Check In</label>
            <input type="text" class="form-control" id="updateCheckIn" name="checkIn" value="<?php echo htmlentities($reservation['CHECKIN']); ?>" required>
          </div>
          <div class="form-group col-md-4">
            <label>Check Out</label>
            <input type="text" class="form-control" id="updateCheckOut" name="checkOut" value="<?php echo htmlentities($reservation['CHECKOUT']); ?>" required>
          </div>
        </div>
        <div class="d-flex gap-5">
          <div class="form-group col-md-4">
            <label>Status</label>
            <select class="form-select" name="status" required>
              <option value="Scheduled" <?php echo $reservation['STATUS'] === 'Scheduled' ? 'selected' : ''; ?>>Scheduled</option>
              <option value="In Progress" class="d-none" disabled <?php echo $reservation['STATUS'] === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
              <option value="Completed" class="d-none" disabled <?php echo $reservation['STATUS'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
              <option value="Canceled" <?php echo $reservation['STATUS'] === 'Canceled' ? 'selected' : ''; ?>>Canceled</option>
            </select>
          </div>
          <div class="form-group col-md-4">
            <label>Price</label>
            <div class="d-flex">
              <button class="btn btn-secondary mb-2 w-40" id="checkUpdatePriceBtn" type="button">Check Price</button>
              <input type="hidden" id="price" name="updatePrice">
              <input type="text" class="form-control" id="updatePlaceholder" name="updatePlaceholder" placeholder="Price: Rp0" disabled required>
            </div>
          </div>
        </div>

        <div class="d-flex gap-3 mt-5">
          <button type="submit" name="update" class="btn btn-warning">Update</button>
          <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
  </div>
</body>

</html>