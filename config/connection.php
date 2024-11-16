<?php
$username = "C##PETSHOP";
$password = "petshop";
$connection_string = "localhost/XE";

$conn = oci_connect($username, $password, $connection_string);
if (!$conn) {
  $e = oci_error();
  echo "Failed connect to Oracle: " . htmlentities($e['message']);
  exit;
  // } else {
  //   echo "Successfully connect to Oracle!";
}
