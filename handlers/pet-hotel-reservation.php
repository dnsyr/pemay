<?php
if (!isset($db)) {
  throw new Exception("Database connection not established.");
}

try {
  $reservatorID = isset($_POST['reservatorID']) ? (int)$_POST['reservatorID'] : 1;
  $kandang = isset($_POST['kandang']) ? (int)$_POST['kandang'] : 1;
  $checkIn = $_POST['checkIn'] ?? null;
  $checkOut = $_POST['checkOut'] ?? null;
  $price = $_POST['price'] ?? null;
  $pegawaiID = intval($_SESSION['employee_id']);
  $booked = 'Scheduled';

  $formattedCheckIn = $db->timestampFormat($checkIn);
  $formattedCheckOut = $db->timestampFormat($checkOut);

  $sqlAddReservation = "INSERT INTO $currentTable (Hewan_ID, Kandang_Nomor, CheckIn, CheckOut, TotalBiaya, Status, Pegawai_ID) 
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
    $message = "$currentLabel berhasil ditambahkan.";
  } else {
    $message = "Gagal menambahkan $currentLabel.";
  }
} catch (PDOException $e) {
  // Handle the exception in the parent script
  throw $e;
}