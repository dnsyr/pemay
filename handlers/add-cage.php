<?php
if (!isset($db)) {
  throw new Exception("Database connection not established.");
}

try {
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
} catch (PDOException $e) {
  // Handle the exception in the parent script
  throw $e;
}