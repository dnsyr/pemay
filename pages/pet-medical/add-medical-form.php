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
        <span class="label-text">Status</span>
    </label>
    <div class="flex gap-4">
        <label class="flex items-center gap-2 px-4 py-2 rounded-full cursor-pointer bg-violet-100 hover:bg-violet-200">
            <input type="radio" name="status" value="Scheduled" class="radio hidden" required>
            <span class="text-sm font-medium">Scheduled</span>
        </label>
        
        <label class="flex items-center gap-2 px-4 py-2 rounded-full cursor-pointer bg-red-100 hover:bg-red-200">
            <input type="radio" name="status" value="Emergency" class="radio hidden" required>
            <span class="text-sm font-medium">Emergency</span>
        </label>
        
        <label class="flex items-center gap-2 px-4 py-2 rounded-full cursor-pointer bg-gray-100 hover:bg-gray-200">
            <input type="radio" name="status" value="Finished" class="radio hidden" required>
            <span class="text-sm font-medium">Finished</span>
        </label>
    </div>
</div>

<div class="form-control w-full">
    <label class="label">
        <span class="label-text">Tanggal</span>
    </label>
    <input type="datetime-local" class="input input-bordered w-full" 
           id="tanggal" name="tanggal" 
           value="<?= date('Y-m-d\TH:i'); ?>" 
           step="1"
           required>
</div>

        <div class="form-control w-full">
            <label class="label">
                <span class="label-text">Deskripsi</span>
            </label>
            <textarea class="textarea textarea-bordered h-24" 
                      id="description" name="description" required></textarea>
        </div>

        <div class="form-control w-full">
            <label class="label">
                <span class="label-text">Hewan</span>
            </label>
            <select class="select select-bordered w-full" id="hewan_id" name="hewan_id" required>
                <?php foreach ($hewanList as $hewan): ?>
                    <option value="<?= htmlentities($hewan['ID']); ?>">
                        <?= htmlentities($hewan['NAMAHEWAN'] . ' (' . $hewan['SPESIES'] . ') - ' . $hewan['NAMAPEMILIK']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div id="jenisLayananSection">
            <label class="label">
                <span class="label-text">Jenis Layanan</span>
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
                                - Biaya: Rp <?= number_format($layanan['BIAYA'], 0, ',', '.'); ?></span>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div id="totalBiayaSection" class="form-control w-full">
            <label class="label">
                <span class="label-text">Total Biaya</span>
            </label>
            <input type="number" class="input input-bordered w-full" 
                   id="total_biaya" name="total_biaya" readonly>
        </div>

        <div class="divider">Informasi Obat</div>

        <div class="form-control w-full">
            <label class="label">
                <span class="label-text">Apakah memerlukan obat?</span>
            </label>
            <select class="select select-bordered w-full" 
                    id="obat_pertanyaan" name="obat_pertanyaan" required onchange="toggleObatForm()">
                <option value="no">Tidak</option>
                <option value="yes">Ya</option>
            </select>
        </div>

        <div id="obatForm" class="hidden space-y-4">
            <div class="form-control w-full">
                <label class="label">
                    <span class="label-text">Nama Obat</span>
                </label>
                <input type="text" class="input input-bordered w-full" id="obat_nama" name="obat_nama">
            </div>

            <div class="form-control w-full">
                <label class="label">
                    <span class="label-text">Dosis</span>
                </label>
                <input type="text" class="input input-bordered w-full" id="obat_dosis" name="obat_dosis">
            </div>

            <div class="form-control w-full">
                <label class="label">
                    <span class="label-text">Frekuensi</span>
                </label>
                <input type="text" class="input input-bordered w-full" id="obat_frekuensi" name="obat_frekuensi">
            </div>

            <div class="form-control w-full">
                <label class="label">
                    <span class="label-text">Instruksi</span>
                </label>
                <textarea class="textarea textarea-bordered" id="obat_instruksi" name="obat_instruksi"></textarea>
            </div>

            <div class="form-control w-full">
                <label class="label">
                    <span class="label-text">Kategori Obat</span>
                </label>
                <select class="select select-bordered w-full" id="kategori_obat_id" name="kategori_obat_id">
                    <option value="">-- Pilih Kategori Obat --</option>
                    <?php foreach ($kategoriObatList as $kategori): ?>
                        <option value="<?= htmlentities($kategori['ID']); ?>">
                            <?= htmlentities($kategori['NAMA']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="button" class="btn btn-secondary" onclick="addObat()">Tambah Obat</button>
        </div>

        <!-- Tabel Daftar Obat -->
        <div id="obatListSection" class="hidden">
            <div class="divider">Daftar Obat</div>
            <table class="table table-zebra w-full">
                <thead>
                    <tr>
                        <th>Nama Obat</th>
                        <th>Dosis</th>
                        <th>Frekuensi</th>
                        <th>Instruksi</th>
                        <th>Kategori</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody id="obatTableBody">
                    <!-- Daftar obat akan ditambahkan di sini via JavaScript -->
                </tbody>
            </table>
            <!-- Tambahkan tombol print resep -->
    <div class="mt-4" id="printButtonSection">
        <button type="button" class="btn btn-secondary" onclick="printResep()" disabled>
            Print Resep
        </button>
    </div>
            <!-- Hidden input untuk menyimpan data obat -->
            <input type="hidden" name="obat_list" id="obatListInput" value="[]">
        </div>

        <div class="divider"></div>
        
        <button type="submit" class="btn btn-primary w-full">Simpan</button>
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
        // Sembunyikan hanya bagian obat
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
        document.querySelector('input[name="status"][value="Scheduled"]').checked = true;
        document.querySelectorAll('input[name="status"]').forEach(radio => {
        radio.addEventListener('change', toggleJenisLayananSection);
    });
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
    // Print hanya bisa dilakukan setelah layanan medis disimpan
    alert('Silakan simpan layanan medis terlebih dahulu untuk mencetak resep.');
}
</script>