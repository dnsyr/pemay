// Handler untuk drawer update obat
function openUpdateObatDrawer(id) {
    // Add obat_id to URL without redirecting
    const url = new URL(window.location.href);
    url.searchParams.set('obat_id', id);
    window.history.pushState({}, '', url);
    
    // Show the drawer
    document.getElementById('update-obat-drawer').checked = true;
    
    // Load the form content
    fetch(`update-obat.php?id=${id}&t=${Date.now()}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(html => {
            document.getElementById('update-obat-form-content').innerHTML = html;
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('update-obat-form-content').innerHTML = 
                `<div class="alert alert-error">Error loading form: ${error.message}</div>`;
        });
}

// Handler untuk drawer update layanan medis
function openUpdateDrawer(id) {
    document.getElementById('update-medical-drawer').checked = true;
    
    // Fetch layanan data
    fetch(`get-layanan-medis.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            // Set status
            const statusInput = document.querySelector(`input[name="status"][value="${data.STATUS}"]`);
            if (statusInput) {
                statusInput.checked = true;
                // Tambahkan class untuk visual feedback
                document.querySelectorAll('input[name="status"]').forEach(radio => {
                    const dot = radio.nextElementSibling;
                    if (radio.checked) {
                        dot.style.backgroundColor = '#363636';
                    } else {
                        dot.style.backgroundColor = 'transparent';
                    }
                });
            }
            
            // Set date
            document.getElementById('tanggal').value = data.TANGGALFORMATTED;
            
            // Set description
            document.getElementById('description').value = data.DESCRIPTION;
            
            // Set total biaya
            document.getElementById('totalBiayaDisplay').value = `Rp ${parseInt(data.TOTALBIAYA).toLocaleString('id-ID')}`;
            document.getElementById('totalBiaya').value = data.TOTALBIAYA;
            
            // Set layanan ID
            document.getElementById('layananId').value = data.ID;
            
            // Populate jenis layanan
            const container = document.getElementById('jenisLayananContainer');
            container.innerHTML = ''; // Clear existing content
            
            data.JENISLAYANAN_OPTIONS.forEach(layanan => {
                const isChecked = data.SELECTED_LAYANAN.includes(layanan.ID);
                container.innerHTML += `
                    <div class="form-control">
                        <label class="label cursor-pointer justify-start gap-4">
                            <input type="checkbox" class="checkbox" 
                                   name="jenis_layanan[]" 
                                   value="${layanan.ID}" 
                                   data-biaya="${layanan.BIAYA}"
                                   ${isChecked ? 'checked' : ''}>
                            <span class="label-text">${layanan.NAMA} 
                                - Cost: Rp ${parseInt(layanan.BIAYA).toLocaleString('id-ID')}</span>
                        </label>
                    </div>
                `;
            });
            
            // Add event listeners to new checkboxes
            document.querySelectorAll('input[name="jenis_layanan[]"]').forEach(checkbox => {
                checkbox.addEventListener('change', updateTotalBiaya);
            });

            // Populate kategori obat options
            const kategoriSelect = document.getElementById('kategoriObat');
            kategoriSelect.innerHTML = '<option value="">Pilih Kategori</option>';
            data.KATEGORI_OBAT_OPTIONS.forEach(kategori => {
                kategoriSelect.innerHTML += `
                    <option value="${kategori.ID}">${kategori.NAMA}</option>
                `;
            });

            // Populate obat table
            const obatTableBody = document.getElementById('obatTableBody');
            obatTableBody.innerHTML = ''; // Clear existing content
            
            if (data.OBAT_LIST && data.OBAT_LIST.length > 0) {
                data.OBAT_LIST.forEach(obat => {
                    obatTableBody.innerHTML += createObatTableRow(obat);
                });
            }

            // Initialize obat list data
            updateObatListData();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat mengambil data');
        });
}

function updateTotalBiaya() {
    let total = 0;
    document.querySelectorAll('input[name="jenis_layanan[]"]:checked').forEach(checkbox => {
        total += parseInt(checkbox.dataset.biaya || 0);
    });
    document.getElementById('totalBiayaDisplay').value = `Rp ${total.toLocaleString('id-ID')}`;
    document.getElementById('totalBiaya').value = total;
}

// Create table row for medication
function createObatTableRow(obat) {
    return `
        <tr data-id="${obat.ID}" 
            data-kategori-id="${obat.KATEGORI_ID}"
            data-instruksi="${obat.INSTRUKSI || ''}" 
            class="text-[#363636]">
            <td>${obat.NAMA}</td>
            <td>${obat.DOSIS}</td>
            <td>${obat.FREKUENSI}</td>
            <td>${obat.KATEGORI_NAMA}</td>
            <td>
                <button type="button" class="btn btn-sm btn-error" onclick="removeObat(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `;
}

// Toggle obat form visibility
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('toggleObatForm');
    const obatForm = document.getElementById('obatForm');
    const cancelBtn = document.getElementById('cancelObat');
    
    if (toggleBtn && obatForm && cancelBtn) {
        toggleBtn.addEventListener('click', () => {
            obatForm.classList.remove('hidden');
            clearObatForm();
        });
        
        cancelBtn.addEventListener('click', () => {
            obatForm.classList.add('hidden');
            clearObatForm();
        });
    }

    // Add obat to list
    const addObatBtn = document.getElementById('addObatToList');
    if (addObatBtn) {
        addObatBtn.addEventListener('click', () => {
            const nama = document.getElementById('namaObat').value;
            const dosis = document.getElementById('dosisObat').value;
            const frekuensi = document.getElementById('frekuensiObat').value;
            const kategoriSelect = document.getElementById('kategoriObat');
            const kategoriId = kategoriSelect.value;
            const kategoriNama = kategoriSelect.options[kategoriSelect.selectedIndex].text;
            const instruksi = document.getElementById('instruksiObat').value;

            if (!nama || !dosis || !frekuensi || !kategoriId || !instruksi) {
                alert('Semua field harus diisi');
                return;
            }

            const obatTableBody = document.getElementById('obatTableBody');
            const newObat = {
                ID: 'temp_' + Date.now(), // Temporary ID for new items
                NAMA: nama,
                DOSIS: dosis,
                FREKUENSI: frekuensi,
                KATEGORI_ID: kategoriId,
                KATEGORI_NAMA: kategoriNama,
                INSTRUKSI: instruksi
            };

            obatTableBody.innerHTML += createObatTableRow(newObat);
            updateObatListData();
            
            // Clear form and hide it
            clearObatForm();
            document.getElementById('obatForm').classList.add('hidden');
        });
    }
});

// Clear obat form
function clearObatForm() {
    document.getElementById('namaObat').value = '';
    document.getElementById('dosisObat').value = '';
    document.getElementById('frekuensiObat').value = '';
    document.getElementById('kategoriObat').value = '';
    document.getElementById('instruksiObat').value = '';
}

// Remove obat from table
function removeObat(button) {
    const row = button.closest('tr');
    row.remove();
    updateObatListData();
}

// Update hidden input with obat list data
function updateObatListData() {
    const obatList = [];
    document.querySelectorAll('#obatTableBody tr').forEach(row => {
        obatList.push({
            id: row.dataset.id,
            nama: row.cells[0].textContent,
            dosis: row.cells[1].textContent,
            frekuensi: row.cells[2].textContent,
            kategori_id: row.dataset.kategoriId,
            instruksi: row.dataset.instruksi
        });
    });
    document.getElementById('obatListData').value = JSON.stringify(obatList);
}

// Add event listeners for radio buttons
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('input[name="status"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.querySelectorAll('input[name="status"]').forEach(r => {
                const dot = r.nextElementSibling;
                if (r.checked) {
                    dot.style.backgroundColor = '#363636';
                } else {
                    dot.style.backgroundColor = 'transparent';
                }
            });
        });
    });
});

// Handle form submission
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('updateMedicalForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            // Add obat list data
            const obatList = [];
            document.querySelectorAll('#obatTableBody tr').forEach(row => {
                obatList.push({
                    id: row.dataset.id,
                    nama: row.cells[0].textContent,
                    dosis: row.cells[1].textContent,
                    frekuensi: row.cells[2].textContent,
                    kategori_id: row.dataset.kategoriId,
                    instruksi: row.dataset.instruksi
                });
            });

            // Debug
            console.log('Sending obat list:', obatList);
            formData.append('obat_list', JSON.stringify(obatList));
            
            fetch('update-medical-services.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => {
                        throw new Error(err.message || 'HTTP error!');
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    document.getElementById('update-medical-drawer').checked = false;
                    location.reload(); // Reload to show updated data
                } else {
                    throw new Error(data.message || 'Terjadi kesalahan');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert(error.message || 'Terjadi kesalahan saat menyimpan data');
            });
        });
    }
});

// Handler untuk drawer add medical service
function updateAddTotalBiaya() {
    let total = 0;
    document.querySelectorAll('#addJenisLayananSection input[name="jenis_layanan[]"]:checked').forEach(checkbox => {
        total += parseInt(checkbox.dataset.biaya || 0);
    });
    document.getElementById('addTotalBiayaDisplay').value = `Rp ${total.toLocaleString('id-ID')}`;
    document.getElementById('addTotalBiaya').value = total;
}

// Add event listeners for add medical service form
document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners for service type checkboxes
    document.querySelectorAll('#addJenisLayananSection input[name="jenis_layanan[]"]').forEach(checkbox => {
        checkbox.addEventListener('change', updateAddTotalBiaya);
    });

    // Add event listeners for add medication form
    const toggleAddObatFormBtn = document.getElementById('toggleAddObatForm');
    const addObatForm = document.getElementById('addObatForm');
    const cancelAddObatBtn = document.getElementById('cancelAddObat');
    const addObatToListBtn = document.getElementById('addObatToList');

    if (toggleAddObatFormBtn) {
        toggleAddObatFormBtn.addEventListener('click', () => {
            console.log('Toggle form clicked');
            const form = document.getElementById('addObatForm');
            if (form) {
                form.classList.remove('hidden');
            }
        });
    }

    if (cancelAddObatBtn) {
        cancelAddObatBtn.addEventListener('click', () => {
            console.log('Cancel clicked');
            const form = document.getElementById('addObatForm');
            if (form) {
                form.classList.add('hidden');
                clearAddObatForm();
            }
        });
    }

    if (addObatToListBtn) {
        addObatToListBtn.addEventListener('click', () => {
            console.log('Add obat button clicked');
            
            const nama = document.getElementById('addNamaObat')?.value;
            const dosis = document.getElementById('addDosisObat')?.value;
            const frekuensi = document.getElementById('addFrekuensiObat')?.value;
            const kategoriSelect = document.getElementById('addKategoriObat');
            const kategoriId = kategoriSelect?.value;
            const kategoriNama = kategoriSelect?.options[kategoriSelect?.selectedIndex]?.text;
            const instruksi = document.getElementById('addInstruksiObat')?.value;

            console.log('Form values:', { nama, dosis, frekuensi, kategoriId, kategoriNama, instruksi });

            if (!nama || !dosis || !frekuensi || !kategoriId || !instruksi) {
                alert('Semua field harus diisi');
                return;
            }

            const obatTableBody = document.getElementById('addObatTableBody');
            console.log('Table body element:', obatTableBody);

            if (!obatTableBody) {
                console.error('Table body element not found!');
                return;
            }

            const row = document.createElement('tr');
            row.dataset.kategoriId = kategoriId;
            row.dataset.instruksi = instruksi;
            row.className = 'text-[#363636]';
            row.innerHTML = `
                <td>${nama}</td>
                <td>${dosis}</td>
                <td>${frekuensi}</td>
                <td>${kategoriNama}</td>
                <td>
                    <button type="button" class="btn btn-sm bg-red-100 hover:bg-red-200 text-red-800 border-none" onclick="removeAddObat(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;

            try {
                obatTableBody.appendChild(row);
                console.log('Row added successfully');
                updateAddObatListData();
                
                // Clear form and hide it
                clearAddObatForm();
                const form = document.getElementById('addObatForm');
                if (form) {
                    form.classList.add('hidden');
                }
            } catch (error) {
                console.error('Error adding row:', error);
            }
        });
    }

    // Add event listeners for radio buttons in add form
    document.querySelectorAll('#addMedicalForm input[name="status"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.querySelectorAll('#addMedicalForm input[name="status"]').forEach(r => {
                const dot = r.nextElementSibling;
                if (r.checked) {
                    dot.style.backgroundColor = '#363636';
                } else {
                    dot.style.backgroundColor = 'transparent';
                }
            });
        });
    });

    // Handle add form submission
    const addForm = document.getElementById('addMedicalForm');
    if (addForm) {
        addForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            // Add obat list data
            const obatList = [];
            document.querySelectorAll('#addObatTableBody tr').forEach(row => {
                obatList.push({
                    nama: row.cells[0].textContent,
                    dosis: row.cells[1].textContent,
                    frekuensi: row.cells[2].textContent,
                    kategori_id: row.dataset.kategoriId,
                    instruksi: row.dataset.instruksi
                });
            });
            formData.append('obat_list', JSON.stringify(obatList));
            formData.append('action', 'add');
            
            fetch('add-medical-services.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => {
                        throw new Error(err.message || 'HTTP error!');
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    document.getElementById('my-drawer').checked = false;
                    location.reload(); // Reload to show updated data
                } else {
                    throw new Error(data.message || 'Terjadi kesalahan');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert(error.message || 'Terjadi kesalahan saat menyimpan data');
            });
        });
    }
});

// Clear add obat form
function clearAddObatForm() {
    document.getElementById('addNamaObat').value = '';
    document.getElementById('addDosisObat').value = '';
    document.getElementById('addFrekuensiObat').value = '';
    document.getElementById('addKategoriObat').value = '';
    document.getElementById('addInstruksiObat').value = '';
}

// Remove obat from add table
window.removeAddObat = function(button) {
    console.log('Remove button clicked');
    const row = button.closest('tr');
    if (row) {
        row.remove();
        updateAddObatListData();
    }
}

// Update hidden input with add obat list data
function updateAddObatListData() {
    console.log('Updating obat list data');
    const obatList = [];
    const rows = document.querySelectorAll('#addObatTableBody tr');
    console.log('Found rows:', rows.length);
    
    rows.forEach(row => {
        if (row.cells.length >= 4) {
            const obat = {
                nama: row.cells[0].textContent,
                dosis: row.cells[1].textContent,
                frekuensi: row.cells[2].textContent,
                kategori_id: row.dataset.kategoriId,
                instruksi: row.dataset.instruksi
            };
            obatList.push(obat);
        }
    });
    
    console.log('Updated obat list:', obatList);
    const input = document.getElementById('addObatListData');
    if (input) {
        input.value = JSON.stringify(obatList);
    }
} 