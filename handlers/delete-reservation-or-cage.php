<?php
if (!isset($db)) {
  throw new Exception("Database connection not established.");
}

$deleteId = $_POST['delete_id'];
$sqlReservation = "UPDATE $currentTable SET onDelete = 1 WHERE ID = :id";
$sqlKandang = "UPDATE $currentTable SET onDelete = 1 WHERE Nomor = :id";

$db->query($tab == 'reservation' ? $sqlReservation : $sqlKandang);

$db->bind(':id', $deleteId);

if ($db->execute()) {
  $db->commit();

  $_SESSION['success_message'] = "$currentLabel removed successfully!";
} else {
  $_SESSION['error_message'] = "Failed to removed $currentLabel!";
}
