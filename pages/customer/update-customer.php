<?php
$conn = mysqli_connect("localhost", "root", "", "pemay");

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>