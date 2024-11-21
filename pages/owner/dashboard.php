<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['posisi'] != 'owner') {
  header("Location: ../../auth/restricted.php");
  exit();
}
include '../owner/header.php';

$pageTitle = 'Owner Dashboard';

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
  } catch (PDOException $e) {
    // Rollback if there is an error
    $db->rollBack();
    $message = "Error: " . $e->getMessage();
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<body>
  <div class="page-container">
    <h1>Welcome to the Owner Dashboard, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
  </div>

</body>

</html>