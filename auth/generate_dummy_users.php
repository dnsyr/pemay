<?php
require '../config/connection.php';

function insertDummyUser($db, $name, $username, $password, $position, $email, $phone)
{
  $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

  $stmt = $db->prepare("SELECT * FROM Pegawai WHERE Username = :username");
  $stmt->bindParam(':username', $username);
  $stmt->execute();

  if ($stmt->rowCount() === 0) {
    $stmt = $db->prepare("INSERT INTO Pegawai (ID, Nama, Username, Password, Posisi, Email, NomorTelpon) VALUES (Pegawai_Seq.NEXTVAL, :nama, :username, :password, :posisi, :email, :nomor_telpon)");
    $stmt->bindParam(':nama', $name);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':password', $hashedPassword);
    $stmt->bindParam(':posisi', $position);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':nomor_telpon', $phone);
    $stmt->execute();
  }
}

try {
  $db = new PDO("oci:dbname=//localhost/XE", "xdb", "xdb");

  insertDummyUser($db, 'Owner User', 'owner', 'owner', 'owner', 'owner@example.com', '1234567890');
  insertDummyUser($db, 'Staff User', 'staff', 'staff', 'staff', 'staff@example.com', '1234567890');
  insertDummyUser($db, 'Veterinarian User', 'vet', 'vet', 'vet', 'vet@example.com', '1234567890');

  echo "Dummy users created successfully!";
} catch (PDOException $e) {
  echo "Error: " . $e->getMessage();
}
