<?php
if (!isset($db)) {
  throw new Exception("Database connection not established.");
}

$ukuran = trim($_POST['ukuran']);

$sqlAddCage = "INSERT INTO $currentTable (Ukuran, Status) VALUES (:ukuran, :status)";
$db->query($sqlAddCage);
$db->bind(':ukuran', $ukuran);
$db->bind(':status', 'Empty');

if ($db->execute()) {
  $_SESSION['success_message'] = "$currentLabel berhasil ditambahkan.";
} else {
  $_SESSION['error_message'] = "Gagal menambahkan $currentLabel.";
}
