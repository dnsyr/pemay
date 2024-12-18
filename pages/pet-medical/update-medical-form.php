<?php
require_once '../../config/database.php';
$db = new Database();

// Get jenis layanan for checkboxes
$db->query("SELECT * FROM JenisLayananMedis WHERE onDelete = 0");
$jenisLayananMedis = $db->resultSet();

// Get kategori obat
$db->query("SELECT ID, Nama FROM KategoriObat WHERE onDelete = 0 ORDER BY Nama");
$kategoriObatList = $db->resultSet();

// Validate and get layanan ID
$id = null;
if (isset($_GET['layanan_id'])) {
    $id = trim($_GET['layanan_id']);
}

if (!$id) {
    echo "<div class='alert alert-error'>ID Layanan tidak ditemukan</div>";
    exit;
}

try {
    // Gunakan koneksi OCI langsung dengan string koneksi hardcode
    $conn = oci_connect('C##PET', '12345', '//localhost:1521/xe');
    if (!$conn) {
        throw new Exception(oci_error()['message']);
    }

    // Prepare dan execute query untuk data layanan
    $sql = "SELECT lm.ID, 
            TO_CHAR(lm.Tanggal, 'YYYY-MM-DD\"T\"HH24:MI:SS') as Tanggal,
            lm.TotalBiaya, 
            lm.Description, 
            lm.Status, 
            lm.Pegawai_ID,
            lm.Hewan_ID,
            h.Nama AS NamaHewan,
            h.Spesies,
            ph.Nama AS NamaPemilik,
            ph.NomorTelpon
            FROM LayananMedis lm
            JOIN Hewan h ON lm.Hewan_ID = h.ID
            JOIN PemilikHewan ph ON h.PemilikHewan_ID = ph.ID
            WHERE lm.ID = :id AND lm.onDelete = 0";

    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ":id", $id);
    oci_execute($stmt);

    $layanan = oci_fetch_assoc($stmt);
    
    if (!$layanan) {
        throw new Exception("Layanan tidak ditemukan");
    }

// Get selected jenis layanan menggunakan query terpisah
$sql_jenislayanan = "SELECT CAST(COLUMN_VALUE AS VARCHAR2(36)) AS JENISLAYANAN_ID 
                     FROM TABLE(
                         SELECT COALESCE(JenisLayanan, ArrayJenisLayananMedis()) 
                         FROM LayananMedis 
                         WHERE ID = :id
                     )";

// Debug value before query
error_log("Getting jenis layanan for ID: " . $id);

$stmt_jl = oci_parse($conn, $sql_jenislayanan);
oci_bind_by_name($stmt_jl, ":id", $id);
oci_execute($stmt_jl);

$currentJenisLayanan = [];
while ($row = oci_fetch_assoc($stmt_jl)) {
    error_log("Raw row data: " . print_r($row, true));
    if (isset($row['JENISLAYANAN_ID'])) {
        $currentJenisLayanan[] = trim($row['JENISLAYANAN_ID']); // Tambahkan trim()
    }
}

error_log("Final currentJenisLayanan array: " . print_r($currentJenisLayanan, true));
    // Get existing obat
    $sql_obat = "SELECT ro.*, ko.Nama as KategoriNama 
                 FROM ResepObat ro 
                 JOIN KategoriObat ko ON ro.KategoriObat_ID = ko.ID 
                 WHERE ro.LayananMedis_ID = :layanan_id 
                 AND ro.onDelete = 0";
                 
    $stmt_obat = oci_parse($conn, $sql_obat);
    oci_bind_by_name($stmt_obat, ":layanan_id", $id);
    oci_execute($stmt_obat);

    $existingObat = [];
    while ($row = oci_fetch_assoc($stmt_obat)) {
        $existingObat[] = $row;
    }

    // Clean up
    oci_free_statement($stmt);
    oci_free_statement($stmt_jl);
    oci_free_statement($stmt_obat);
    oci_close($conn);

} catch (Exception $e) {
    echo "<div class='alert alert-error'>" . htmlspecialchars($e->getMessage()) . "</div>";
    exit;
}
?>

<form method="POST" class="space-y-4" id="update-drawer-form" action="update-medical-services.php">
    <input type="hidden" name="id" value="<?= htmlspecialchars($layanan['ID']) ?>">
    
    <!-- Status Radio Buttons -->
    <div class="form-control w-full">
        <label class="label">
            <span class="label-text">Status</span>
        </label>
        <div class="flex gap-4 flex-wrap">
            <?php
            $statusOptions = [
                'Scheduled' => 'bg-violet-100 hover:bg-violet-200',
                'Emergency' => 'bg-red-100 hover:bg-red-200',
                'Finished' => 'bg-green-100 hover:bg-green-200',
                'Canceled' => 'bg-gray-100 hover:bg-gray-200'
            ];
            foreach ($statusOptions as $value => $classes): ?>
                <label class="flex items-center gap-2 px-4 py-2 rounded-full cursor-pointer <?= $classes ?>">
                    <input type="radio" name="status" value="<?= $value ?>" 
                           class="radio hidden" required
                           <?= ($layanan['STATUS'] === $value) ? 'checked' : '' ?>>
                    <span class="text-sm font-medium"><?= $value ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Date Time Input -->
    <div class="form-control w-full">
        <label class="label">
            <span class="label-text">Tanggal</span>
        </label>
        <input type="datetime-local" class="input input-bordered w-full" 
               id="tanggal" name="tanggal" 
               value="<?= $layanan['TANGGAL'] ?? date('Y-m-d\TH:i') ?>" 
               required>
    </div>

    <!-- Description -->
    <div class="form-control w-full">
        <label class="label">
            <span class="label-text">Deskripsi</span>
        </label>
        <textarea class="textarea textarea-bordered h-24" 
                  id="description" name="description" 
                  required><?= htmlspecialchars($layanan['DESCRIPTION'] ?? '') ?></textarea>
    </div>

    <!-- Jenis Layanan Section -->
<div id="jenisLayananSection">
    <label class="label">
        <span class="label-text">Jenis Layanan</span>
    </label>
    <div class="space-y-2">
        <?php foreach ($jenisLayananMedis as $jenis): 
            error_log("Checking jenis: " . $jenis['ID'] . " against current: " . print_r($currentJenisLayanan, true));?>
            <div class="form-control">
                <label class="label cursor-pointer justify-start gap-4">
                <input type="checkbox" class="checkbox" 
       name="jenis_layanan[]" 
       value="<?= htmlspecialchars($jenis['ID']) ?>"
       data-biaya="<?= htmlspecialchars($jenis['BIAYA']) ?>"
       <?= in_array($jenis['ID'], $currentJenisLayanan, true) ? 'checked' : '' ?>>
                    <span class="label-text">
                        <?= htmlspecialchars($jenis['NAMA']) ?> 
                        - Biaya: Rp <?= number_format($jenis['BIAYA'], 0, ',', '.') ?>
                    </span>
                </label>
            </div>
        <?php endforeach; ?>
    </div>
</div>

    <!-- Total Biaya Section -->
    <div id="totalBiayaSection" class="form-control w-full">
        <label class="label">
            <span class="label-text">Total Biaya</span>
        </label>
        <input type="hidden" id="total_biaya_actual" name="total_biaya" 
       value="<?= htmlspecialchars($layanan['TOTALBIAYA'] ?? '0') ?>">
<input type="text" 
       class="input input-bordered w-full" 
       id="total_biaya_display" 
       value="Rp <?= number_format((float)($layanan['TOTALBIAYA'] ?? 0), 0, ',', '.') ?>"
       readonly>
    </div>

    <div id="obatSection">
    <div class="divider">Informasi Obat</div>
    
    <?php if (!empty($existingObat)): ?>
        <div class="mb-4">
            <h4 class="font-semibold mb-2">Obat yang Telah Digunakan</h4>
            <table class="table w-full">
                <thead>
                    <tr>
                        <th>Nama Obat</th>
                        <th>Dosis</th>
                        <th>Frekuensi</th>
                        <th>Instruksi</th>
                        <th>Kategori</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($existingObat as $obat): ?>
                        <tr>
                            <td><?= htmlentities($obat['NAMA']) ?></td>
                            <td><?= htmlentities($obat['DOSIS']) ?></td>
                            <td><?= htmlentities($obat['FREKUENSI']) ?></td>
                            <td><?= htmlentities($obat['INSTRUKSI']) ?></td>
                            <td><?= htmlentities($obat['KATEGORINAMA']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

<!-- Form Tambah Obat Baru -->
<div class="form-control w-full">
    <label class="label">
        <span class="label-text">Tambah Obat Baru?</span>
    </label>
    <select class="select select-bordered w-full" 
            id="obat_pertanyaan" 
            name="obat_pertanyaan">
        <option value="no">Tidak</option>
        <option value="yes">Ya</option>
    </select>
</div>

<div id="obatForm" class="hidden space-y-4">
    <div class="form-control w-full">
        <label class="label">
            <span class="label-text">Nama Obat</span>
        </label>
        <input type="text" class="input input-bordered w-full" 
               id="obat_nama" name="obat_nama">
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
                    <option value="<?= htmlentities($kategori['ID']) ?>">
                        <?= htmlentities($kategori['NAMA']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="button" class="btn btn-secondary" onclick="addObat()">Tambah Obat</button>
    </div>
    <div id="obatListSection" class="hidden">
    <div class="divider">Daftar Obat Baru</div>
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
                <!-- Akan diisi via JavaScript -->
            </tbody>
        </table>
    </div>
</div>
            <!-- Tambahkan tombol print resep -->
    <div class="mt-4" id="printButtonSection">
        <button type="button" class="btn btn-secondary" onclick="printResep()" disabled>
            Print Resep
        </button>
    </div>
            <!-- Hidden input untuk menyimpan data obat -->
            <input type="hidden" name="obat_list" id="obatListInput" value="[]">
            <div class="mt-4">
        <button type="submit" class="btn btn-primary w-full">Update Layanan Medis</button>
    </div>
</form>

<script>
let obatList = [];
let previousTotal = 0;

document.addEventListener('DOMContentLoaded', function() {
    // Single initialization point
    initializeEventListeners();
    calculateTotal();
    initializeFormState();
});

function initializeFormState() {
    // Set initial states
    const status = document.querySelector('input[name="status"]:checked')?.value;
    const printButton = document.querySelector('#printButtonSection button');
    if (printButton) {
        printButton.disabled = (status !== 'Finished');
    }

    // Initialize obat form visibility
    toggleObatForm();
    
    // Initialize sections based on status
    toggleSections();
}

function initializeEventListeners() {
    // Jenis Layanan checkboxes
    document.querySelectorAll('input[name="jenis_layanan[]"]').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            calculateTotal();
        });
    });
    
    // Status radio buttons
    document.querySelectorAll('input[name="status"]').forEach(radio => {
        radio.addEventListener('change', function() {
            toggleSections();
            updatePrintButton(this.value);
        });
    });

    // Obat pertanyaan dropdown
    const obatPertanyaan = document.getElementById('obat_pertanyaan');
    if (obatPertanyaan) {
        obatPertanyaan.addEventListener('change', toggleObatForm);
    }
}

function toggleObatForm() {
    const obatPertanyaan = document.getElementById('obat_pertanyaan');
    const obatForm = document.getElementById('obatForm');
    const obatListSection = document.getElementById('obatListSection');
    const status = document.querySelector('input[name="status"]:checked')?.value;

    if (!obatPertanyaan || !obatForm || !obatListSection) {
        console.error('Required elements not found');
        return;
    }

    // Show/hide based on both status and obat_pertanyaan value
    if (status === 'Scheduled') {
        obatForm.classList.add('hidden');
        obatListSection.classList.add('hidden');
        obatPertanyaan.value = 'no';
        obatPertanyaan.closest('.form-control').classList.add('hidden');
    } else {
        obatPertanyaan.closest('.form-control').classList.remove('hidden');
        if (obatPertanyaan.value === 'yes') {
            obatForm.classList.remove('hidden');
            obatListSection.classList.remove('hidden');
        } else {
            obatForm.classList.add('hidden');
            obatListSection.classList.add('hidden');
        }
    }
}

function toggleSections() {
    const status = document.querySelector('input[name="status"]:checked')?.value;
    const jenisLayananSection = document.getElementById('jenisLayananSection');
    const totalBiayaSection = document.getElementById('totalBiayaSection');
    const obatSection = document.getElementById('obatSection');
    
    if (jenisLayananSection) jenisLayananSection.style.display = 'block';
    if (totalBiayaSection) totalBiayaSection.style.display = 'block';
    
    // Always show existing selections for jenis layanan
    document.querySelectorAll('input[name="jenis_layanan[]"]:checked').forEach(checkbox => {
        checkbox.closest('.form-control').style.display = 'block';
    });
    
    // Toggle obat section based on status
    if (obatSection) {
        if (status === 'Scheduled') {
            obatSection.classList.add('hidden');
        } else {
            obatSection.classList.remove('hidden');
            toggleObatForm(); // Update obat form visibility
        }
    }
    
    calculateTotal();
}

function updatePrintButton(status) {
    const printButton = document.querySelector('#printButtonSection button');
    if (printButton) {
        printButton.disabled = (status !== 'Finished');
    }
}

function calculateTotal() {
    let total = 0;
    const checkedServices = document.querySelectorAll('input[name="jenis_layanan[]"]:checked');
    
    checkedServices.forEach(checkbox => {
        const biaya = parseInt(checkbox.dataset.biaya) || 0;
        total += biaya;
    });
    
    const totalBiayaDisplay = document.getElementById('total_biaya_display');
    const totalBiayaActual = document.getElementById('total_biaya_actual');
    
    if (totalBiayaDisplay && totalBiayaActual) {
        totalBiayaActual.value = total;
        totalBiayaDisplay.value = 'Rp ' + total.toLocaleString('id-ID');
    }
}

function toggleSections() {
    const jenisLayananSection = document.getElementById('jenisLayananSection');
    const totalBiayaSection = document.getElementById('totalBiayaSection');
    const obatSection = document.getElementById('obatSection');
    
    jenisLayananSection.style.display = 'block';
    totalBiayaSection.style.display = 'block';
    if (obatSection) obatSection.style.display = 'block';
    
    // Recalculate total
    calculateTotal();
}

// Initialize the obat list array
let obatList = [];

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

// Make sure the event listener is properly added
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Content Loaded');
    const obatPertanyaan = document.getElementById('obat_pertanyaan');
    
    if (obatPertanyaan) {
        console.log('Found obat_pertanyaan select');
        obatPertanyaan.addEventListener('change', function(e) {
            console.log('Select changed to:', e.target.value);
            toggleObatForm();
        });
    } else {
        console.error('Could not find obat_pertanyaan select element');
    }

    // Initialize form state
    toggleObatForm();
});

// Pastikan event listener terpasang
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Content Loaded');
    
    const obatPertanyaan = document.getElementById('obat_pertanyaan');
    if (obatPertanyaan) {
        console.log('Found obat_pertanyaan select');
        obatPertanyaan.addEventListener('change', function(e) {
            console.log('Select changed to:', e.target.value);
            toggleObatForm();
        });
    }

    // Initialize form state
    toggleObatForm();
});

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

    function printResep() {
    // Print hanya bisa dilakukan setelah layanan medis disimpan
    alert('Silakan simpan layanan medis terlebih dahulu untuk mencetak resep.');
}
</script>