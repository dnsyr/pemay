<?php
// Adjust with ur username & password Oracle DB
$username = "C##PET";
$password = "12345";
$connection_string = "localhost/XE";

$conn = oci_connect($username, $password, $connection_string);
if (!$conn) {
  $e = oci_error();
  echo "Failed connect to Oracle: " . htmlentities($e['message']);
  exit;
  // } else {
  //   echo "Successfully connect to Oracle!";
}

$setFormatSQL = "ALTER SESSION SET NLS_TIMESTAMP_FORMAT = 'YYYY-MM-DD HH24:MI:SS'";
$stid = oci_parse($conn, $setFormatSQL);
oci_execute($stid);
