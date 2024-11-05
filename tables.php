<?php
include 'config/connection.php';

$sql = "SELECT table_name FROM user_tables";
$stid = oci_parse($conn, $sql);
oci_execute($stid);

echo "<h2>Tables in Oracle Database Pemay:</h2>";
echo "<ul>";
while ($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS)) {
  echo "<li>" . htmlentities($row['TABLE_NAME']) . "</li>";
}
echo "</ul>";

oci_free_statement($stid);
oci_close($conn);
