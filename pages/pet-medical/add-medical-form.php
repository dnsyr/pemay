<?php
date_default_timezone_set('Asia/Jakarta');

$db = new Database();

// Ambil data jenis layanan medis untuk checkbox
$db->query("SELECT * FROM JenisLayananMedis WHERE onDelete = 0");
$jenisLayananMedis = $db->resultSet();

// Ambil data hewan untuk dropdown
$db->query("SELECT DISTINCT h.ID, h.Nama AS NamaHewan, h.Spesies, ph.Nama AS NamaPemilik
            FROM Hewan h
            JOIN PemilikHewan ph ON h.PemilikHewan_ID = ph.ID
            WHERE h.onDelete = 0 AND ph.onDelete = 0");
$hewanList = $db->resultSet();

// Ambil Data Kategori Obat
$db->query("SELECT ID, Nama FROM KategoriObat WHERE onDelete = 0 ORDER BY Nama");
$kategoriObatList = $db->resultSet();

// Inisialisasi variabel
$error = null;
$message = null;
$messageObat = null;

// Menentukan apakah form obat harus ditampilkan
$showObatForm = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['obat_pertanyaan']) && $_POST['obat_pertanyaan'] === 'yes') {
        $showObatForm = true;
    }
}
?>

<div class="p-4 w-full">
    <!-- Form Layanan Medis -->
    <form method="POST" class="space-y-4" id="medicalForm">
        <input type="hidden" name="action" value="add">
        
        <div class="form-control w-full">
            <label class="label">
                <span class="label-text font-semibold">Status</span>
            </label>
            <div class="flex gap-4">
                <input type="radio" name="status" value="Scheduled" class="hidden" id="scheduled" required>
                <label for="scheduled" class="inline-flex items-center gap-2 px-4 py-1 rounded-full bg-[#E4E1F9] hover:bg-[#E4E1F9]/80 cursor-pointer border border-[#363636]">
                    <div class="w-2 h-2 rounded-full border-2 border-[#363636]"></div>
                    Scheduled
                </label>
                
                <input type="radio" name="status" value="Emergency" class="hidden" id="emergency" required>
                <label for="emergency" class="inline-flex items-center gap-2 px-4 py-1 rounded-full bg-[#FFE4E4] hover:bg-[#FFE4E4]/80 cursor-pointer border border-[#363636]">
                    <div class="w-2 h-2 rounded-full border-2 border-[#363636]"></div>
                    Emergency
                </label>

                <input type="radio" name="status" value="Finished" class="hidden" id="finished" required>
                <label for="finished" class="inline-flex items-center gap-2 px-4 py-1 rounded-full bg-green-500 hover:bg-green-400 text-white cursor-pointer border border-[#363636]">
                    <div class="w-2 h-2 rounded-full border-2 border-[#363636]"></div>
                    Finished
                </label>
            </div>
        </div>

        <div class="form-control w-full">
            <label class="label">
                <span class="label-text font-semibold">Date</span>
            </label>
            <input type="datetime-local" class="input input-bordered w-full" 
                   id="tanggal" name="tanggal" 
                   value="<?= date('Y-m-d\TH:i'); ?>" 
                   step="1"
                   required>
        </div>

        <div class="form-control w-full">
            <label class="label">
                <span class="label-text font-semibold">Description</span>
            </label>
            <textarea class="textarea textarea-bordered h-24" 
                      id="description" name="description" 
                      placeholder="Enter medical service description..."
                      required></textarea>
        </div>

        <div class="form-control w-full">
            <label class="label">
                <span class="label-text font-semibold">Pet</span>
            </label>
            <select class="select2 select select-bordered w-full" id="hewan_id" name="hewan_id" required data-placeholder="Select pet">
                <option value=""></option>
                <?php foreach ($hewanList as $hewan): ?>
                    <option value="<?= htmlentities($hewan['ID']); ?>">
                        <?= htmlentities($hewan['NAMAHEWAN'] . ' (' . $hewan['SPESIES'] . ') - ' . $hewan['NAMAPEMILIK']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div id="jenisLayananSection">
            <label class="label">
                <span class="label-text font-semibold">Service Types</span>
            </label>
            <div class="space-y-2">
                <?php foreach ($jenisLayananMedis as $layanan): ?>
                    <div class="form-control">
                        <label class="label cursor-pointer justify-start gap-4">
                            <input type="checkbox" class="checkbox" 
                                   name="jenis_layanan[]" 
                                   value="<?= htmlentities($layanan['ID']); ?>" 
                                   data-biaya="<?= htmlentities($layanan['BIAYA']); ?>">
                            <span class="label-text"><?= htmlentities($layanan['NAMA']); ?> 
                                - Cost: Rp <?= number_format($layanan['BIAYA'], 0, ',', '.'); ?></span>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div id="totalBiayaSection" class="form-control w-full">
            <label class="label">
                <span class="label-text font-semibold">Total Cost</span>
            </label>
            <input type="number" class="input input-bordered w-full" 
                   id="total_biaya" name="total_biaya" readonly>
        </div>

        <div class="divider">Medication Information</div>

        <div class="form-control w-full">
            <label class="label">
                <span class="label-text font-semibold">Need Medication?</span>
            </label>
            <select class="select2 select select-bordered w-full" 
                    id="obat_pertanyaan" name="obat_pertanyaan" required 
                    onchange="toggleObatForm()" data-placeholder="Select option">
                <option value=""></option>
                <option value="no">No</option>
                <option value="yes">Yes</option>
            </select>
        </div>

        <div id="obatForm" class="hidden space-y-4">
            <div class="form-control w-full">
                <label class="label">
                    <span class="label-text font-semibold">Medicine Name</span>
                </label>
                <input type="text" class="input input-bordered w-full" 
                       id="obat_nama" name="obat_nama"
                       placeholder="Enter medicine name">
            </div>

            <div class="form-control w-full">
                <label class="label">
                    <span class="label-text font-semibold">Dosage</span>
                </label>
                <input type="text" class="input input-bordered w-full" 
                       id="obat_dosis" name="obat_dosis"
                       placeholder="Enter dosage (e.g., 2 tablets)">
            </div>

            <div class="form-control w-full">
                <label class="label">
                    <span class="label-text font-semibold">Frequency</span>
                </label>
                <input type="text" class="input input-bordered w-full" 
                       id="obat_frekuensi" name="obat_frekuensi"
                       placeholder="Enter frequency (e.g., 3 times a day)">
            </div>

            <div class="form-control w-full">
                <label class="label">
                    <span class="label-text font-semibold">Instructions</span>
                </label>
                <textarea class="textarea textarea-bordered" 
                          id="obat_instruksi" name="obat_instruksi"
                          placeholder="Enter medication instructions..."></textarea>
            </div>

            <div class="form-control w-full">
                <label class="label">
                    <span class="label-text font-semibold">Medicine Category</span>
                </label>
                <select class="select2 select select-bordered w-full" 
                        id="kategori_obat_id" name="kategori_obat_id"
                        data-placeholder="Select medicine category">
                    <option value=""></option>
                    <?php foreach ($kategoriObatList as $kategori): ?>
                        <option value="<?= htmlentities($kategori['ID']); ?>">
                            <?= htmlentities($kategori['NAMA']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="button" class="btn btn-secondary" onclick="addObat()">Add Medicine</button>
        </div>

        <!-- Tabel Daftar Obat -->
        <div id="obatListSection" class="hidden">
            <div class="divider">Medicine List</div>
            <table class="table table-zebra w-full">
                <thead>
                    <tr>
                        <th>Medicine Name</th>
                        <th>Dosage</th>
                        <th>Frequency</th>
                        <th>Instructions</th>
                        <th>Category</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="obatTableBody">
                    <!-- Daftar obat akan ditambahkan di sini via JavaScript -->
                </tbody>
            </table>
            <!-- Tambahkan tombol print resep -->
    <div class="mt-4" id="printButtonSection">
        <button type="button" class="btn btn-secondary" onclick="printResep()" disabled>
            Print Recipe
        </button>
    </div>
            <!-- Hidden input untuk menyimpan data obat -->
            <input type="hidden" name="obat_list" id="obatListInput" value="[]">
        </div>

        <div class="divider"></div>
        
        <button type="submit" class="btn btn-primary w-full">Save</button>
    </form>
</div>
<script>
    let obatList = [];

    function toggleJenisLayananSection() {
    const status = document.querySelector('input[name="status"]:checked')?.value;
    const jenisLayananSection = document.getElementById('jenisLayananSection');
    const totalBiayaSection = document.getElementById('totalBiayaSection');
    const obatPertanyaanSection = document.getElementById('obat_pertanyaan').closest('.form-control');
    const obatForm = document.getElementById('obatForm');
    const obatListSection = document.getElementById('obatListSection');
    const dividerObat = document.querySelector('.divider');
    
    if (status === 'Scheduled') {
        // Sembunyikan hanya bsagian obat
        obatPertanyaanSection.style.display = 'none';
        obatForm.classList.add('hidden');
        obatListSection.classList.add('hidden');
        dividerObat.style.display = 'none';
        
        // Reset obat pertanyaan ke 'no'
        document.getElementById('obat_pertanyaan').value = 'no';
        obatList = [];
        updateObatTable();
    } else {
        // Tampilkan semua bagian
        obatPertanyaanSection.style.display = 'block';
        dividerObat.style.display = 'block';
        
        // Cek status obat pertanyaan untuk menampilkan/menyembunyikan form obat
        if (document.getElementById('obat_pertanyaan').value === 'yes') {
            obatForm.classList.remove('hidden');
            obatListSection.classList.remove('hidden');
        }
    }
    
    // Jenis Layanan dan Total Biaya selalu ditampilkan
    jenisLayananSection.style.display = 'block';
    totalBiayaSection.style.display = 'block';
}

function toggleObatForm() {
    const obatPertanyaan = document.getElementById('obat_pertanyaan');
    const obatForm = document.getElementById('obatForm');
    const obatListSection = document.getElementById('obatListSection');

    if (obatPertanyaan && obatForm && obatListSection) {
        if (obatPertanyaan.value === 'yes') {
            // Use only classList
            obatForm.classList.remove('hidden');
            obatListSection.classList.remove('hidden');
        } else {
            // Use only classList
            obatForm.classList.add('hidden');
            obatListSection.classList.add('hidden');
        }
    }
}

    function addObat() {
        const namaObat = document.getElementById('obat_nama').value;
        const dosis = document.getElementById('obat_dosis').value;
        const frekuensi = document.getElementById('obat_frekuensi').value;
        const instruksi = document.getElementById('obat_instruksi').value;
        const kategoriSelect = document.getElementById('kategori_obat_id');
        const kategoriId = kategoriSelect.value;
        const kategoriNama = kategoriSelect.options[kategoriSelect.selectedIndex].text;

        if (!namaObat || !dosis || !frekuensi || !instruksi || !kategoriId) {
            alert('Semua field obat harus diisi');
            return;
        }

        obatList.push({
            nama: namaObat,
            dosis: dosis,
            frekuensi: frekuensi,
            instruksi: instruksi,
            kategori_id: kategoriId,
            kategori_nama: kategoriNama
        });

        updateObatTable();
        clearObatForm();
    }

    function removeObat(index) {
        obatList.splice(index, 1);
        updateObatTable();
    }

    function updateObatTable() {
        const tbody = document.getElementById('obatTableBody');
        const obatListInput = document.getElementById('obatListInput');
        tbody.innerHTML = '';
        
        obatList.forEach((obat, index) => {
            const row = tbody.insertRow();
            row.innerHTML = `
                <td>${obat.nama}</td>
                <td>${obat.dosis}</td>
                <td>${obat.frekuensi}</td>
                <td>${obat.instruksi}</td>
                <td>${obat.kategori_nama}</td>
                <td>
                    <button type="button" class="btn btn-error btn-sm" 
                            onclick="removeObat(${index})">Hapus</button>
                </td>
            `;
        });

        obatListInput.value = JSON.stringify(obatList);
    }

    function clearObatForm() {
        document.getElementById('obat_nama').value = '';
        document.getElementById('obat_dosis').value = '';
        document.getElementById('obat_frekuensi').value = '';
        document.getElementById('obat_instruksi').value = '';
        document.getElementById('kategori_obat_id').value = '';
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Select2
        $('.select-bordered').select2({
            width: '100%',
            placeholder: $(this).data('placeholder'),
            allowClear: true,
            theme: 'classic'
        });

        // Initialize status buttons
        const statusInputs = document.querySelectorAll('input[name="status"]');
        const statusLabels = document.querySelectorAll('label[for^="scheduled"], label[for^="emergency"], label[for^="finished"]');

        // Function to update circle state
        function updateCircles() {
            statusInputs.forEach(input => {
                const label = document.querySelector(`label[for="${input.id}"]`);
                if (label) {
                    const circle = label.querySelector('div');
                    if (circle) {
                        if (input.checked) {
                            circle.classList.add('bg-[#363636]');
                        } else {
                            circle.classList.remove('bg-[#363636]');
                        }
                    }
                }
            });
        }

        // Add click event to each label
        statusLabels.forEach(label => {
            label.addEventListener('click', function(e) {
                e.preventDefault();
                const input = document.getElementById(this.getAttribute('for'));
                if (input) {
                    input.checked = true;
                    updateCircles();
                    toggleJenisLayananSection();
                }
            });
        });

        // Set initial state
        document.querySelector('input[name="status"][value="Scheduled"]').checked = true;
        updateCircles();
        toggleJenisLayananSection();
        
        document.querySelectorAll('input[name="jenis_layanan[]"]').forEach((checkbox) => {
        checkbox.addEventListener('change', function() {
            calculateTotalBiaya();
        });
    });

    function calculateTotalBiaya() {
        let total = 0;
        document.querySelectorAll('input[name="jenis_layanan[]"]:checked').forEach((checkedBox) => {
            total += parseFloat(checkedBox.getAttribute('data-biaya'));
        });
        document.getElementById('total_biaya').value = total;
    }

    toggleJenisLayananSection();
    
    const tanggalInput = document.getElementById('tanggal');
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    
    const formattedDateTime = `${year}-${month}-${day}T${hours}:${minutes}`;
    tanggalInput.value = formattedDateTime;
    tanggalInput.min = formattedDateTime;
});
    function printResep() {
    alert('Silakan simpan layanan medis terlebih dahulu untuk mencetak resep.');
}
</script>