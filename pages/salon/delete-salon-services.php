<?php

session_start();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' ) {
    $_SESSION['error_message'] = "Error: Invalid request method";
    header('Location: salon-services.php');
    exit();
}

if (!isset($_SESSION['username']) || $_SESSION['posisi'] !== 'staff') {
    $_SESSION['error_message'] = "Error: Unauthorized";
    header('Location: salon-services.php');
    exit();
}

require_once '../../config/database.php';
require_once '../../config/connection.php';

$db = new Database();

$id = $_POST['id'];

$db->query("UPDATE LayananSalon SET onDelete = 1 WHERE ID = :id AND Pegawai_ID = :pegawai_id");
$db->bind(':id', $id);
$db->bind(':pegawai_id', $_SESSION['employee_id']);
$db->execute();

$_SESSION['success_message'] = "Layanan Salon berhasil dihapus";

header('Location: salon-services.php');
exit();
