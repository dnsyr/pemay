<?php
if (!isset($db)) {
  throw new Exception("Database connection not established.");
}

$checkIn = $_POST['checkIn'];
$checkOut = $_POST['checkOut'];
$status = $_POST['status'];

$sql = "UPDATE LayananHotel SET CHECKIN = :checkIn, CHECKOUT = :checkOut, STATUS = :status WHERE ID = :id";
$db->query($sql);
$db->bind(':checkIn', $checkIn);
$db->bind(':checkOut', $checkOut);
$db->bind(':status', $status);
$db->bind(':id', $reservationID);

if ($db->execute()) {
  $db->commit();
  $_SESSION['success_message'] = "Reservation updated successfully!";
  header("Location: dashboard.php?tab=reservation");
  exit();
}
