// Handler untuk delete record
function deleteRecord(id, type) {
    if (confirm('Are u sure want to delete this record?')) {
        const currentPage = document.querySelector('input[name="page"]')?.value || 1;
        const namaHewan = document.querySelector('select[name="nama_hewan"]')?.value || '';
        const namaPemilik = document.querySelector('select[name="nama_pemilik"]')?.value || '';
        const status = document.querySelector('select[name="status"]')?.value || '';
        const tab = document.querySelector('input[name="my_tabs_2"]:checked')?.value || 'medical-services';
        
        window.location.href = `delete-record.php?id=${id}&type=${type}&tab=${tab}&page=${currentPage}&nama_hewan=${encodeURIComponent(namaHewan)}&nama_pemilik=${encodeURIComponent(namaPemilik)}&status=${encodeURIComponent(status)}`;
    }
} 