<?php
// Adjust with ur username & password Oracle DB
$username = "DVF";
$password = "DVF";
$connection_string = "localhost/XE";

$conn = oci_connect($username, $password, $connection_string);
if (!$conn) {
  $e = oci_error();
  echo "Failed connect to Oracle: " . htmlentities($e['message']);
  exit;
  // } else {
  //   echo "Successfully connect to Oracle!";
}
