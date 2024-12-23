<?php
// Add Medical Services Drawer
?>
<div class="drawer drawer-end">
    <input id="my-drawer" type="checkbox" class="drawer-toggle" /> 
    <div class="drawer-side z-50">
        <label for="my-drawer" class="drawer-overlay"></label>
        <div class="p-4 w-[600px] min-h-full bg-white text-base-content">
            <div id="add-form-content">
                <div class="p-4">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-bold text-lg">Add Medical Service</h3>
                        <label for="my-drawer" class="btn btn-sm btn-circle">✕</label>
                    </div>
                    
                    <form method="POST" action="add-medical-services.php" class="space-y-4" id="addMedicalForm">
                        <!-- Status Section -->
                        <div class="form-control w-full">
                            <label class="label">
                                <span class="label-text font-semibold text-black">Status</span>
                            </label>
                            <div class="flex gap-4">
                                <label class="inline-flex items-center gap-2 px-4 py-1 rounded-full bg-[#E4E1F9] hover:bg-[#E4E1F9]/80 cursor-pointer border border-[#363636] text-black">
                                    <input type="radio" name="status" value="Scheduled" class="hidden" required>
                                    <div class="radio-dot w-2 h-2 rounded-full border-2 border-[#363636] transition-colors"></div>
                                    Scheduled
                                </label>
                                
                                <label class="inline-flex items-center gap-2 px-4 py-1 rounded-full bg-[#FFE4E4] hover:bg-[#FFE4E4]/80 cursor-pointer border border-[#363636] text-black">
                                    <input type="radio" name="status" value="Emergency" class="hidden" required>
                                    <div class="radio-dot w-2 h-2 rounded-full border-2 border-[#363636] transition-colors"></div>
                                    Emergency
                                </label>

                                <label class="inline-flex items-center gap-2 px-4 py-1 rounded-full bg-[#EFEFEF] hover:bg-[#EFEFEF]/80 cursor-pointer border border-[#363636] text-black">
                                    <input type="radio" name="status" value="Finished" class="hidden" required>
                                    <div class="radio-dot w-2 h-2 rounded-full border-2 border-[#363636] transition-colors"></div>
                                    Finished
                                </label>
                            </div>
                        </div>

                        <!-- Pet Selection -->
                        <div class="form-control w-full">
                            <label class="label">
                                <span class="label-text font-semibold text-black">Pet</span>
                            </label>
                            <select name="hewan_id" class="select2 select select-bordered w-full bg-white text-black" required>
                                <option value="">Select Pet</option>
                                <?php
                                $db = new Database();
                                $db->query("SELECT h.ID, h.Nama as NamaHewan, h.Spesies, ph.Nama as NamaPemilik 
                                          FROM Hewan h 
                                          JOIN PemilikHewan ph ON h.PemilikHewan_ID = ph.ID 
                                          WHERE h.onDelete = 0 AND ph.onDelete = 0 
                                          ORDER BY h.Nama");
                                $hewanList = $db->resultSet();
                                foreach ($hewanList as $hewan): ?>
                                    <option value="<?= htmlspecialchars($hewan['ID']) ?>">
                                        <?= htmlspecialchars($hewan['NAMAHEWAN']) ?> 
                                        (<?= htmlspecialchars($hewan['SPESIES']) ?>) - 
                                        Owner: <?= htmlspecialchars($hewan['NAMAPEMILIK']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Date Section -->
                        <div class="form-control w-full">
                            <label class="label">
                                <span class="label-text font-semibold text-black">Date</span>
                            </label>
                            <input type="datetime-local" name="tanggal" 
                                   class="input input-bordered w-full bg-white text-black" required id="tanggalDrawer"
                                   onkeydown="return false" min="<?= date('Y-m-d\TH:i'); ?>"
                                   value="<?= date('Y-m-d\TH:i'); ?>">
                        </div>

                        <!-- Description Section -->
                        <div class="form-control w-full">
                            <label class="label">
                                <span class="label-text font-semibold text-black">Description</span>
                            </label>
                            <textarea name="description" class="textarea textarea-bordered w-full bg-white text-black" 
                                      required placeholder="Enter medical service description..."></textarea>
                        </div>

                        <!-- Service Types Section -->
                        <div id="addJenisLayananSection">
                            <label class="label">
                                <span class="label-text font-semibold text-black">Service Types</span>
                            </label>
                            <div class="space-y-2 bg-white">
                                <?php
                                $db->query("SELECT ID, Nama, Biaya FROM JenisLayananMedis WHERE onDelete = 0 ORDER BY Nama");
                                $jenisLayananList = $db->resultSet();
                                foreach ($jenisLayananList as $layanan): ?>
                                    <div class="form-control bg-white">
                                        <label class="label cursor-pointer justify-start gap-4 bg-white">
                                            <input type="checkbox" class="checkbox bg-white" 
                                                   name="jenis_layanan[]" 
                                                   value="<?= htmlspecialchars($layanan['ID']) ?>"
                                                   data-biaya="<?= htmlspecialchars($layanan['BIAYA']) ?>">
                                            <span class="label-text text-black"><?= htmlspecialchars($layanan['NAMA']) ?> 
                                                - Cost: Rp <?= number_format($layanan['BIAYA'], 0, ',', '.') ?></span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Total Cost Display -->
                        <div class="form-control w-full">
                            <label class="label">
                                <span class="label-text font-semibold text-black">Total Cost</span>
                            </label>
                            <input type="text" id="addTotalBiayaDisplay" class="input input-bordered w-full bg-white text-black" readonly>
                            <input type="hidden" name="total_biaya" id="addTotalBiaya">
                        </div>

                        <!-- Medication Information -->
                        <div class="divider text-black">Medication Information</div>

                        <!-- List Obat -->
                        <div class="mb-4">
                            <div class="overflow-x-auto">
                                <table class="table w-full bg-white">
                                    <thead class="bg-white">
                                        <tr>
                                            <th class="text-black">Medicine Name</th>
                                            <th class="text-black">Dosage</th>
                                            <th class="text-black">Frequency</th>
                                            <th class="text-black">Category</th>
                                            <th class="text-black">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="addObatTableBody" class="bg-white">
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Form Input Obat -->
                        <div class="mb-4">
                            <button type="button" class="btn btn-sm bg-[#D4F0EA] hover:bg-[#D4F0EA] text-[#363636]" onclick="showObatForm()">
                                <i class="fas fa-plus mr-2"></i> Add Medication
                            </button>
                        </div>

                        <div id="addObatForm" class="hidden">
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text text-black">Medicine Name</span>
                                    </label>
                                    <input type="text" id="addNamaObat" class="input input-bordered bg-white text-black" placeholder="Enter medicine name">
                                </div>
                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text text-black">Dosage</span>
                                    </label>
                                    <input type="text" id="addDosisObat" class="input input-bordered bg-white text-black" placeholder="Enter dosage">
                                </div>
                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text text-black">Frequency</span>
                                    </label>
                                    <input type="text" id="addFrekuensiObat" class="input input-bordered bg-white text-black" placeholder="Enter frequency">
                                </div>
                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text text-black">Category</span>
                                    </label>
                                    <select id="addKategoriObat" class="select2 select select-bordered bg-white text-black">
                                        <option value="">Select Category</option>
                                        <?php
                                        $db = new Database();
                                        $db->query("SELECT ID, Nama FROM KategoriObat WHERE onDelete = 0 ORDER BY Nama");
                                        $kategoriList = $db->resultSet();
                                        foreach ($kategoriList as $kategori): ?>
                                            <option value="<?= htmlspecialchars($kategori['ID']) ?>">
                                                <?= htmlspecialchars($kategori['NAMA']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-control mb-4">
                                <label class="label">
                                    <span class="label-text text-black">Instructions</span>
                                </label>
                                <textarea id="addInstruksiObat" class="textarea textarea-bordered bg-white text-black" placeholder="Enter instructions"></textarea>
                            </div>
                            <div class="flex justify-end gap-2">
                                <button type="button" class="btn btn-ghost" onclick="hideObatForm()">Cancel</button>
                                <button type="button" class="btn bg-[#D4F0EA] hover:bg-[#D4F0EA] text-[#363636]" onclick="addObatToList()">Add to List</button>
                            </div>
                        </div>

                        <input type="hidden" name="obat_list" id="addObatListData" value="[]">

                        <div class="flex justify-end gap-2 mt-6">
                            <label for="my-drawer" class="btn btn-ghost">Cancel</label>
                            <button type="submit" name="action" value="save" class="btn bg-[#D4F0EA] hover:bg-[#D4F0EA] text-[#363636]">Save</button>
                            <button type="submit" name="action" value="save_and_print" class="btn bg-[#D4F0EA] hover:bg-[#D4F0EA] text-[#363636]">Save and Print</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Style for radio button dots */
input[type="radio"]:checked + .radio-dot {
    background-color: #363636;
}

/* Style for status labels */
input[type="radio"]:checked + .radio-dot + span {
    font-weight: 600;
}
</style>

<script>
// Inisialisasi Select2
document.addEventListener('DOMContentLoaded', function() {
    // Inisialisasi Select2 untuk semua dropdown
    $('.select2').select2({
        dropdownParent: $('#my-drawer'),
        width: '100%'
    });

    // Event listener untuk checkbox jenis layanan
    const checkboxes = document.querySelectorAll('input[name="jenis_layanan[]"]');
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            let total = 0;
            checkboxes.forEach(cb => {
                if (cb.checked) {
                    total += parseInt(cb.dataset.biaya || 0);
                }
            });
            document.getElementById('addTotalBiayaDisplay').value = `Rp ${total.toLocaleString('id-ID')}`;
            document.getElementById('addTotalBiaya').value = total;
        });
    });
});

// Fungsi untuk menampilkan form obat
function showObatForm() {
    document.getElementById('addObatForm').classList.remove('hidden');
}

// Fungsi untuk menyembunyikan form obat
function hideObatForm() {
    document.getElementById('addObatForm').classList.add('hidden');
    clearObatForm();
}

// Fungsi untuk membersihkan form obat
function clearObatForm() {
    document.getElementById('addNamaObat').value = '';
    document.getElementById('addDosisObat').value = '';
    document.getElementById('addFrekuensiObat').value = '';
    $('#addKategoriObat').val('').trigger('change'); // Clear Select2
    document.getElementById('addInstruksiObat').value = '';
}

// Fungsi untuk menambahkan obat ke list
function addObatToList() {
    const nama = document.getElementById('addNamaObat').value;
    const dosis = document.getElementById('addDosisObat').value;
    const frekuensi = document.getElementById('addFrekuensiObat').value;
    const kategoriSelect = document.getElementById('addKategoriObat');
    const kategoriId = kategoriSelect.value;
    const kategoriNama = kategoriSelect.options[kategoriSelect.selectedIndex]?.text;
    const instruksi = document.getElementById('addInstruksiObat').value;

    if (!nama || !dosis || !frekuensi || !kategoriId || !instruksi) {
        alert('Semua field harus diisi');
        return;
    }

    const tbody = document.getElementById('addObatTableBody');
    const row = tbody.insertRow();
    row.dataset.kategoriId = kategoriId;
    row.dataset.instruksi = instruksi;

    row.innerHTML = `
        <td>${nama}</td>
        <td>${dosis}</td>
        <td>${frekuensi}</td>
        <td>${kategoriNama}</td>
        <td>
            <button type="button" class="btn btn-sm bg-red-100 hover:bg-red-200 text-red-800 border-none" onclick="removeObat(this)">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;

    updateObatList();
    hideObatForm();
}

// Fungsi untuk menghapus obat dari list
function removeObat(button) {
    const row = button.closest('tr');
    row.remove();
    updateObatList();
}

// Fungsi untuk mengupdate hidden input obat_list
function updateObatList() {
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
    document.getElementById('addObatListData').value = JSON.stringify(obatList);
}

document.addEventListener('DOMContentLoaded', function() {
    const drawerToggle = document.getElementById('my-drawer');
    const tanggalInput = document.getElementById('tanggalDrawer');

    // Fungsi untuk mengatur tanggal minimum
    function setMinDateTime() {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        
        // Format: YYYY-MM-DDThh:mm
        const formattedDateTime = `${year}-${month}-${day}T${hours}:${minutes}`;
        
        tanggalInput.min = formattedDateTime;
        tanggalInput.value = formattedDateTime;

        // Tambahkan event listener untuk memvalidasi jam
        tanggalInput.addEventListener('change', function() {
            const selectedDate = new Date(this.value);
            const currentDate = new Date();
            
            // Jika tanggal yang dipilih adalah hari ini, pastikan jamnya tidak lebih awal
            if (selectedDate.toDateString() === currentDate.toDateString() && 
                selectedDate.getTime() < currentDate.getTime()) {
                this.value = formattedDateTime;
            }
        });
    }

    // Set tanggal minimum saat drawer dibuka
    drawerToggle.addEventListener('change', function() {
        if (this.checked) {
            setMinDateTime();
        }
    });

    // Set tanggal minimum saat halaman dimuat
    setMinDateTime();
});
</script> 