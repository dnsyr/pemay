<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['posisi'] !== 'vet') {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

require_once '../../config/database.php';

try {
    $id = $_GET['id'] ?? '';
    if (empty($id)) {
        throw new Exception('ID tidak boleh kosong');
    }

    $db = new Database();
    
    // Get layanan medis data
    $query = "SELECT lm.ID, 
            TO_CHAR(lm.Tanggal, 'YYYY-MM-DD\"T\"HH24:MI') as TanggalFormatted,
            lm.TotalBiaya, 
            lm.Description, 
            lm.Status,
            (
                SELECT LISTAGG(COLUMN_VALUE, ',') WITHIN GROUP (ORDER BY COLUMN_VALUE)
                FROM TABLE(lm.JenisLayanan)
            ) as JenisLayanan
            FROM LayananMedis lm
            WHERE lm.ID = :id AND lm.onDelete = 0";
    
    $db->query($query);
    $db->bind(':id', $id);
    $layananMedis = $db->single();
    
    if (!$layananMedis) {
        throw new Exception('Data layanan medis tidak ditemukan');
    }
    
    // Get selected layanan
    $selectedLayanan = [];
    if (!empty($layananMedis['JENISLAYANAN'])) {
        $selectedLayanan = explode(',', $layananMedis['JENISLAYANAN']);
    }
    
    // Get jenis layanan options
    $db->query("SELECT ID, Nama, Biaya FROM JenisLayananMedis WHERE onDelete = 0 ORDER BY Nama");
    $jenisLayananMedis = $db->resultSet();
    
    // Get obat data
    $db->query("SELECT ro.ID, ro.Nama, ro.Dosis, ro.Frekuensi, ro.Instruksi, ro.KategoriObat_ID,
                ko.Nama as KategoriNama
                FROM ResepObat ro
                JOIN KategoriObat ko ON ro.KategoriObat_ID = ko.ID
                WHERE ro.LayananMedis_ID = :id AND ro.onDelete = 0
                ORDER BY ro.Nama");
    $db->bind(':id', $id);
    $obatList = $db->resultSet();
    
    // Get kategori obat options
    $db->query("SELECT ID, Nama FROM KategoriObat WHERE onDelete = 0 ORDER BY Nama");
    $kategoriObatOptions = $db->resultSet();
    
    // Prepare response
    $response = [
        'ID' => $layananMedis['ID'],
        'TANGGALFORMATTED' => $layananMedis['TANGGALFORMATTED'],
        'TOTALBIAYA' => $layananMedis['TOTALBIAYA'],
        'DESCRIPTION' => $layananMedis['DESCRIPTION'],
        'STATUS' => $layananMedis['STATUS'],
        'SELECTED_LAYANAN' => $selectedLayanan,
        'JENISLAYANAN_OPTIONS' => array_map(function($layanan) {
            return [
                'ID' => $layanan['ID'],
                'NAMA' => $layanan['NAMA'],
                'BIAYA' => $layanan['BIAYA']
            ];
        }, $jenisLayananMedis),
        'OBAT_LIST' => array_map(function($obat) {
            return [
                'ID' => $obat['ID'],
                'NAMA' => $obat['NAMA'],
                'DOSIS' => $obat['DOSIS'],
                'FREKUENSI' => $obat['FREKUENSI'],
                'INSTRUKSI' => $obat['INSTRUKSI'],
                'KATEGORI_ID' => $obat['KATEGORIOBAT_ID'],
                'KATEGORI_NAMA' => $obat['KATEGORINAMA']
            ];
        }, $obatList),
        'KATEGORI_OBAT_OPTIONS' => array_map(function($kategori) {
            return [
                'ID' => $kategori['ID'],
                'NAMA' => $kategori['NAMA']
            ];
        }, $kategoriObatOptions)
    ];
    
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?> 