<?php
session_start();
include '../../config/connection.php';

$itemsPerPage = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $itemsPerPage;

$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
$searchQuery = $searchTerm ? " WHERE NAMAITEM LIKE :searchTerm" : '';

$sql = "SELECT * FROM Stock" . $searchQuery . " OFFSET :offset ROWS FETCH NEXT :itemsPerPage ROWS ONLY";
$stid = oci_parse($conn, $sql);
if ($searchTerm) {
    $searchLike = '%' . $searchTerm . '%';
    oci_bind_by_name($stid, ":searchTerm", $searchLike);
}
oci_bind_by_name($stid, ":offset", $offset, -1, SQLT_INT);
oci_bind_by_name($stid, ":itemsPerPage", $itemsPerPage, -1, SQLT_INT);
oci_execute($stid);
$stocks = [];
while ($row = oci_fetch_assoc($stid)) {
    $stocks[] = $row;
}
oci_free_statement($stid);

$totalSql = "SELECT COUNT(*) AS total FROM Stock" . $searchQuery;
$totalStid = oci_parse($conn, $totalSql);
if ($searchTerm) {
    oci_bind_by_name($totalStid, ":searchTerm", $searchLike);
}
oci_execute($totalStid);
$totalRow = oci_fetch_assoc($totalStid);
$totalItems = $totalRow['TOTAL'];
oci_free_statement($totalStid);
oci_close($conn);

$totalPages = ceil($totalItems / $itemsPerPage);
?>
