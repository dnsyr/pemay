<?php
function createDataEmployee($db)
{
  $db->beginTransaction();

  $username = $_POST['username'];
  $checkUsernameSql = "SELECT COUNT(*) AS count FROM Pegawai WHERE Username = :username";

  $db->query($checkUsernameSql);
  $db->bind(':username', $username);

  $isUsernameAvailable = $db->single();

  if ((int)$isUsernameAvailable['COUNT'] > 0) {
    $_SESSION['error_message'] = "Username not available!";
    header("Location: users.php");
    exit();
  } else {
    $nama = $_POST['nama'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $posisi = $_POST['posisi'];
    $email = $_POST['email'];
    $nomorTelpon = $_POST['nomorTelpon'];

    $sql = "BEGIN CreatePegawai(:nama, :username, :password, :posisi, :email, :nomorTelpon); END;";

    $db->query($sql);
    $db->bind(':nama', $nama);
    $db->bind(':username', $username);
    $db->bind(':password', $password);
    $db->bind(':posisi', $posisi);
    $db->bind(':email', $email);
    $db->bind(':nomorTelpon', $nomorTelpon);

    if ($db->execute()) {
      $db->commit();
      $_SESSION['success_message'] = "Data employee created successfully!";
      header("Location: users.php");
      exit();
    } else {
      $db->rollback();
      $_SESSION['error_message'] = "Failed to create data employee!";
    }
  }
}

function getAllDataEmployees($db)
{
  $dataEmployees = $db->executeProcedureWithCursor('SelectAllPegawai');

  return $dataEmployees;
}

function getDataEmployee($db, $username)
{
  $dataEmployee = $db->executeProcedureWithCursorAndParam('SelectPegawaiByUsername', 'p_username', $username);

  return $dataEmployee;
}

function updateDataEmployee($db, $username)
{
  $nama = $_POST['updateNama'];
  $password = $_POST['updatePassword'] ? password_hash($_POST['updatePassword'], PASSWORD_DEFAULT) : '';
  $posisi = $_POST['updatePosisi'];
  $email = $_POST['updateEmail'];
  $nomorTelpon = $_POST['updateNomorTelpon'];

  $sql = "BEGIN UpdatePegawai(:nama, :username, :password, :posisi, :email, :nomorTelpon); END;";

  $db->beginTransaction();

  $db->query($sql);
  $db->bind(':nama', $nama);
  $db->bind(':username', $username);
  $db->bind(':password', $password);
  $db->bind(':posisi', $posisi);
  $db->bind(':email', $email);
  $db->bind(':nomorTelpon', $nomorTelpon);

  if ($db->execute()) {
    $db->commit();
    $_SESSION['success_message'] = "Data employee updated successfully!";
    header("Location: users.php");
    exit();
  } else {
    $db->rollback();
    $_SESSION['error_message'] = "Failed to update data employee!";
  }
}

function deleteEmployee($db, $username)
{
  $sql = "BEGIN DeletePegawai(:username); END;";

  $db->beginTransaction();

  $db->query($sql);
  $db->bind(':username', $username);

  if ($db->execute()) {
    $db->commit();

    $_SESSION['success_message'] = "Data employee removed successfully!";
    header("Location: users.php");
    exit();
  } else {
    $_SESSION['error_message'] = "Failed to removed data employee!";
  }
}
