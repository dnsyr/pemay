<?php
include 'config/connection.php';

$sql = "SELECT h.*, p.nama as namapemilik FROM hewan h join pemilikhewan p on h.pemilikhewan_id = p.id";
$stid = oci_parse($conn, $sql);
oci_execute($stid);

echo "<h2>Tables in Oracle Database Pemay:</h2>";
?>
<table>
  <thead>
    <tr>
      <th>Nama</th>
      <th>Ras</th>
      <th>Spesies</th>
      <th>Gender</th>
      <th>Tanggal Lahir</th>
      <th>Berat</th>
      <th>Tinggi</th>
      <th>Lebar</th>
      <th>Nama Pemilik</th>
    </tr>
  </thead>
  <tbody>
    <?php
    while ($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS)) { ?>
      <tr>
        <td><?php echo htmlentities($row['NAMA']) . " (Owner: " . htmlentities($row['NAMAPEMILIK']) . ")"; ?></td>
        <td><?php echo htmlentities($row['RAS']); ?></td>
        <td><?php echo htmlentities($row['SPESIES']); ?></td>
        <td><?php echo htmlentities($row['GENDER']); ?></td>
        <td><?php echo htmlentities($row['TANGGALLAHIR']); ?></td>
        <td><?php echo htmlentities($row['BERAT']); ?></td>
        <td><?php echo htmlentities($row['TINGGI']); ?></td>
        <td><?php echo htmlentities($row['LEBAR']); ?></td>
        <td><?php echo htmlentities($row['NAMAPEMILIK']); ?></td>
      </tr>
    <?php } ?>
  </tbody>
</table>
<?php


oci_free_statement($stid);
oci_close($conn);
