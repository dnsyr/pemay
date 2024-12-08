<?php
if (!isset($db)) {
  throw new Exception("Database connection not established.");
}

$reservatorID = isset($_POST['reservatorID']) ? (int)$_POST['reservatorID'] : 1;
$kandang = $_POST['kandang'] ?? null;
$checkIn = $_POST['checkIn'] ?? null;
$checkOut = $_POST['checkOut'] ?? null;
$price = $_POST['price'] ?? null;
$pegawaiID = intval($_SESSION['employee_id']);
$booked = 'Scheduled';

$formattedCheckIn = $db->timestampFormat($checkIn);
$formattedCheckOut = $db->timestampFormat($checkOut);

$sqlAddReservation = "INSERT INTO $currentTable (Hewan_ID, Kandang_ID, CheckIn, CheckOut, TotalBiaya, Status, Pegawai_ID) 
                           VALUES (:reservatorID, :kandang, TO_TIMESTAMP(:formattedCheckIn, 'YYYY-MM-DD HH24:MI:SS'), TO_TIMESTAMP(:formattedCheckOut, 'YYYY-MM-DD HH24:MI:SS'), :price, :booked, :pegawaiID)";

$db->query($sqlAddReservation);
$db->bind(':reservatorID', $reservatorID);
$db->bind(':kandang', $kandang);
$db->bind(':price', $price);
$db->bind(':booked', $booked);
$db->bind(':pegawaiID', $pegawaiID);
$db->bind(':formattedCheckIn', $formattedCheckIn);
$db->bind(':formattedCheckOut', $formattedCheckOut);

if ($db->execute()) {
  $db->commit();

  $_SESSION['success_message'] = "Reservation created successfully!";
} else {
  $_SESSION['error_message'] = "Failed to remove reservation!";
}
