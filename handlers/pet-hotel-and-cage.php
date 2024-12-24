<?php
// Handle Request for Reservation Pet Hotel
function createDataReservation($db)
{
  try {
    $db->beginTransaction();

    $hewanID = $_POST['reservatorID'];
    $kandangID = $_POST['kandangID'];
    $checkIn = $_POST['checkIn'];
    $checkOut = $_POST['checkOut'];
    $totalBiaya = $_POST['price'];
    $pegawaiID = $_SESSION['employee_id'];
    $status = 'Scheduled';

    $formattedCheckIn = $db->timestampFormat($checkIn);
    $formattedCheckOut = $db->timestampFormat($checkOut);

    $sql = "
    BEGIN
      CreateLayananHotel(
          p_checkin    => TO_TIMESTAMP(:formattedCheckIn, 'YYYY-MM-DD HH24:MI:SS'),
          p_checkout   => TO_TIMESTAMP(:formattedCheckOut, 'YYYY-MM-DD HH24:MI:SS'),
          p_totalbiaya => :totalBiaya,
          p_status     => :status,
          p_hewan_id   => :hewanID,
          p_pegawai_id => :pegawaiID,
          p_kandang_id => :kandangID
      );
    END;
  ";

    $db->query($sql);
    $db->bind(':formattedCheckIn', $formattedCheckIn);
    $db->bind(':formattedCheckOut', $formattedCheckOut);
    $db->bind(':totalBiaya', $totalBiaya);
    $db->bind(':status', $status);
    $db->bind(':hewanID', $hewanID);
    $db->bind(':pegawaiID', $pegawaiID);
    $db->bind(':kandangID', $kandangID);

    if ($db->execute()) {
      $db->commit();
      $_SESSION['success_message'] = "Reservation created successfully!";
    }
  } catch (PDOException $e) {
    if (strpos($e->getMessage(), 'ORA-20001') !== false) {
      $db->rollBack();
      $_SESSION['error_message'] = "Dates are not available for this Cage Room. Please select a different time range!";
    } else {
      $db->rollback();
      $_SESSION['error_message'] = "Failed to create reservation!";
    }
  }
}

function getAllDataReservations($db)
{
  $dataReservations = $db->executeProcedureWithCursor('SelectAllLayananHotel');

  return $dataReservations;
}

function getDataReservation($db, $reservationID)
{
  $dataReservation = $db->executeProcedureWithCursorAndParam('SelectLayananHotelByID', 'p_id', $reservationID);

  return $dataReservation;
}

function updateDataReservation($db, $reservationID)
{
  // Fetch values from POST
  $checkIn = $_POST['updateCheckIn'];
  $checkOut = $_POST['updateCheckOut'];
  $totalBiaya = $_POST['updatePrice'];
  $status = $_POST['updateStatus'];
  $hewanID = $_POST['updateHewanID'];
  $pegawaiID = $_POST['updatePegawaiID'];
  $kandangID = $_POST['updateKandangID'];

  // Prepare the SQL for calling the procedure
  $sql = "
    BEGIN
      UpdateLayananHotel(
          p_id         => :reservationID,
          p_checkin    => TO_TIMESTAMP(:checkIn, 'YYYY-MM-DD HH24:MI:SS'),
          p_checkout   => TO_TIMESTAMP(:checkOut, 'YYYY-MM-DD HH24:MI:SS'),
          p_totalbiaya => :totalBiaya,
          p_status     => :status,
          p_hewan_id   => :hewanID,
          p_pegawai_id => :pegawaiID,
          p_kandang_id => :kandangID
      );
    END;
  ";

  // Start a transaction
  $db->beginTransaction();

  // Bind the parameters
  $db->query($sql);
  $db->bind(':reservationID', $reservationID);
  $db->bind(':checkIn', $checkIn);
  $db->bind(':checkOut', $checkOut);
  $db->bind(':totalBiaya', $totalBiaya);
  $db->bind(':status', $status);
  $db->bind(':hewanID', $hewanID);
  $db->bind(':pegawaiID', $pegawaiID);
  $db->bind(':kandangID', $kandangID);

  // Execute the query and commit the transaction if successful
  if ($db->execute()) {
    $db->commit();
    $_SESSION['success_message'] = "Reservation updated successfully!";
    header("Location: dashboard.php");
    exit();
  } else {
    $db->rollback();
    $_SESSION['error_message'] = "Failed to update reservation!";
  }
}

function deleteDataReservation($db)
{
  $reservationID = $_POST['delete_id'];

  $sql = "BEGIN DeleteLayananHotel(:reservationID); END;";

  $db->beginTransaction();

  // Bind the parameters
  $db->query($sql);
  $db->bind(':reservationID', $reservationID);

  if ($db->execute()) {
    $db->commit();
    $_SESSION['success_message'] = "Reservation removed successfully!";
    header("Location: dashboard.php");
    exit();
  } else {
    $_SESSION['error_message'] = "Failed to removed reservation!";
  }
}

// Handle Request for CRUD Cage
function createDataCage($db)
{
  $db->beginTransaction();

  $ukuran = trim($_POST['ukuran']);

  $sqlAddCage = "INSERT INTO Kandang (Ukuran, Status) VALUES (:ukuran, :status)";
  $db->query($sqlAddCage);
  $db->bind(':ukuran', $ukuran);
  $db->bind(':status', 'Empty');

  if ($db->execute()) {
    $db->commit();
    $_SESSION['success_message'] = "Cage created succesfully!";
  } else {
    $_SESSION['error_message'] = "Failed to create cage!";
  }
}

function getAllDataCages($db)
{
  $dataCages = $db->executeProcedureWithCursor('SelectAllKandang');

  return $dataCages;
}

function getDataCage($db) {}

function updateDataCage($db) {}

function deleteDataCage($db)
{
  $db->beginTransaction();

  $cageID = $_POST['deleteCageID'];

  $sql = "UPDATE Kandang SET onDelete = 1 WHERE ID = :cageID";

  // Bind the parameters
  $db->query($sql);
  $db->bind(':cageID', $cageID);

  if ($db->execute()) {
    $db->commit();
    $_SESSION['success_message'] = "Data cage removed successfully!";
  } else {
    $_SESSION['error_message'] = "Failed to removed data cage!";
  }
}
