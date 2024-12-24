<?php
require_once '../../config/database.php';

// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check for authentication
if (!isset($_SESSION['username']) || $_SESSION['posisi'] !== 'vet') {
    echo "<div class='alert alert-error'>Unauthorized access</div>";
    exit;
}

if (!isset($_GET['id'])) {
    echo "<div class='alert alert-error'>ID layanan medis tidak ditemukan</div>";
    exit;
}

$layananId = $_GET['id'];
$db = new Database();

try {
    // Gunakan koneksi OCI langsung
    $conn = oci_connect('C##PET', '12345', '//localhost:1521/xe');
    if (!$conn) {
        throw new Exception(oci_error()['message']);
    }

    // Query untuk data LayananMedis
    $sql = "SELECT lm.ID, 
            TO_CHAR(lm.Tanggal, 'YYYY-MM-DD\"T\"HH24:MI') as TanggalFormatted,
            lm.TotalBiaya, 
            lm.Description, 
            lm.Status, 
            lm.Pegawai_ID,
            p.Nama AS NamaPegawai,
            lm.Hewan_ID,
            h.Nama AS NamaHewan,
            (
                SELECT LISTAGG(COLUMN_VALUE, ',') WITHIN GROUP (ORDER BY COLUMN_VALUE)
                FROM TABLE(lm.JenisLayanan)
            ) as JenisLayanan
            FROM LayananMedis lm
            JOIN Pegawai p ON lm.Pegawai_ID = p.ID
            JOIN Hewan h ON lm.Hewan_ID = h.ID
            WHERE lm.ID = :id AND lm.onDelete = 0";

    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ":id", $layananId);
    oci_execute($stmt);

    $layananMedis = oci_fetch_assoc($stmt);

if (!$layananMedis) {
        throw new Exception("Data layanan medis tidak ditemukan");
    }

    // Parse JenisLayanan string menjadi array
    $selectedLayanan = [];
    if (!empty($layananMedis['JENISLAYANAN'])) {
        $selectedLayanan = explode(',', $layananMedis['JENISLAYANAN']);
}

// Get ResepObat data
    $sql_obat = "SELECT ro.*, ko.Nama as KategoriNama 
           FROM ResepObat ro 
           LEFT JOIN KategoriObat ko ON ro.KategoriObat_ID = ko.ID 
                 WHERE ro.LayananMedis_ID = :id AND ro.onDelete = 0";
                 
    $stmt_obat = oci_parse($conn, $sql_obat);
    oci_bind_by_name($stmt_obat, ":id", $layananId);
    oci_execute($stmt_obat);

    $resepObatList = [];
    while ($row = oci_fetch_assoc($stmt_obat)) {
        $resepObatList[] = $row;
    }

    // Clean up
    oci_free_statement($stmt);
    oci_free_statement($stmt_obat);
    oci_close($conn);

} catch (Exception $e) {
    echo "<div class='alert alert-error'>" . htmlspecialchars($e->getMessage()) . "</div>";
    exit;
}

// Get other required data using PDO
$db->query("SELECT * FROM JenisLayananMedis WHERE onDelete = 0");
$jenisLayananMedis = $db->resultSet();

$db->query("SELECT DISTINCT h.ID, h.Nama AS NamaHewan, h.Spesies, ph.Nama AS NamaPemilik 
           FROM Hewan h 
           JOIN PemilikHewan ph ON h.PemilikHewan_ID = ph.ID 
           WHERE h.onDelete = 0 AND ph.onDelete = 0");
$hewanList = $db->resultSet();

$db->query("SELECT ID, Nama FROM KategoriObat WHERE onDelete = 0 ORDER BY Nama");
$kategoriObatList = $db->resultSet();
?>

<!-- Form HTML tetap sama seperti sebelumnya -->
<div class="p-4 w-full">
    <div class="flex justify-between items-center mb-4">
        <h3 class="font-bold text-lg">Update Medical Service</h3>
        <a href="dashboard.php" class="btn btn-sm btn-circle">✕</a>
    </div>

    <form method="POST" action="update-medical-services.php" class="space-y-4" id="updateMedicalForm" onsubmit="return handleSubmit(event)">
        <input type="hidden" name="id" value="<?= htmlspecialchars($layananMedis['ID']) ?>">
        <input type="hidden" name="obat_list" id="obatListInput" value="">
        
        <!-- Status Section -->
        <div class="form-control w-full">
            <label class="label">
                <span class="label-text font-semibold">Status</span>
            </label>
            <div class="flex gap-4">
                <label class="inline-flex items-center gap-2 px-4 py-1 rounded-full bg-[#E4E1F9] hover:bg-[#E4E1F9]/80 cursor-pointer border border-[#363636]">
                    <input type="radio" name="status" value="Scheduled" class="hidden" required
                           <?= $layananMedis['STATUS'] === 'Scheduled' ? 'checked' : '' ?>>
                    <div class="w-2 h-2 rounded-full border-2 border-[#363636] transition-colors"></div>
                    Scheduled
                </label>
                
                <label class="inline-flex items-center gap-2 px-4 py-1 rounded-full bg-[#FFE4E4] hover:bg-[#FFE4E4]/80 cursor-pointer border border-[#363636]">
                    <input type="radio" name="status" value="Emergency" class="hidden" required
                           <?= $layananMedis['STATUS'] === 'Emergency' ? 'checked' : '' ?>>
                    <div class="w-2 h-2 rounded-full border-2 border-[#363636] transition-colors"></div>
                    Emergency
                </label>
                
                <label class="inline-flex items-center gap-2 px-4 py-1 rounded-full bg-[#EFEFEF] hover:bg-[#EFEFEF]/80 cursor-pointer border border-[#363636]">
                    <input type="radio" name="status" value="Finished" class="hidden" required
                           <?= $layananMedis['STATUS'] === 'Finished' ? 'checked' : '' ?>>
                    <div class="w-2 h-2 rounded-full border-2 border-[#363636] transition-colors"></div>
                    Finished
                </label>

                <label class="inline-flex items-center gap-2 px-4 py-1 rounded-full bg-[#EFEFEF] hover:bg-[#EFEFEF]/80 cursor-pointer border border-[#363636]">
                    <input type="radio" name="status" value="Canceled" class="hidden" required
                           <?= $layananMedis['STATUS'] === 'Canceled' ? 'checked' : '' ?>>
                    <div class="w-2 h-2 rounded-full border-2 border-[#363636] transition-colors"></div>
                    Canceled
                </label>
            </div>
        </div>

        <!-- Date Section -->
        <div class="form-control w-full">
            <label class="label">
                <span class="label-text font-semibold">Date</span>
            </label>
            <input type="datetime-local" class="input input-bordered w-full" 
                   id="tanggal" name="tanggal" 
                   value="<?= $layananMedis['TANGGALFORMATTED'] ?>" 
                   required>
        </div>

        <!-- Description Section -->
        <div class="form-control w-full">
            <label class="label">
                <span class="label-text font-semibold">Description</span>
            </label>
            <textarea class="textarea textarea-bordered h-24" 
                      id="description" name="description" 
                      placeholder="Enter medical service description..."
                      required><?= htmlspecialchars($layananMedis['DESCRIPTION']) ?></textarea>
        </div>

        <!-- Service Types Section -->
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
                                   data-biaya="<?= htmlentities($layanan['BIAYA']); ?>"
                                   <?= in_array($layanan['ID'], $selectedLayanan) ? 'checked' : '' ?>>
                            <span class="label-text"><?= htmlentities($layanan['NAMA']); ?> 
                                - Cost: Rp <?= number_format($layanan['BIAYA'], 0, ',', '.'); ?></span>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Total Cost Section -->
        <div class="form-control w-full mt-4">
            <label class="label">
                <span class="label-text font-semibold">Total Cost</span>
            </label>
            <input type="text" id="total_biaya_display" class="input input-bordered w-full" readonly>
            <input type="hidden" id="total_biaya" name="total_biaya">
        </div>

        <script>
        // Definisikan fungsi hitungTotal di scope global
        window.hitungTotal = function() {
            console.log('Menghitung total biaya...');
            let total = 0;
            const checkboxes = document.querySelectorAll('input[name="jenis_layanan[]"]:checked');
            
            checkboxes.forEach(function(checkbox) {
                const biaya = parseInt(checkbox.getAttribute('data-biaya'));
                console.log('Biaya layanan:', biaya);
                if (!isNaN(biaya)) {
                    total += biaya;
                }
            });
            
            console.log('Total biaya:', total);
            // Update hidden input untuk nilai asli
            const totalBiayaInput = document.getElementById('total_biaya');
            const totalBiayaDisplay = document.getElementById('total_biaya_display');
            
            if (totalBiayaInput && totalBiayaDisplay) {
                totalBiayaInput.value = total;
                totalBiayaDisplay.value = formatRupiah(total);
                console.log('Total biaya updated:', totalBiayaDisplay.value);
            } else {
                console.error('Total biaya elements not found');
            }
        }

        // Fungsi untuk format rupiah
        function formatRupiah(angka) {
            return 'Rp ' + new Intl.NumberFormat('id-ID').format(angka);
        }

        // Tambahkan event listener untuk checkbox
        document.querySelectorAll('input[name="jenis_layanan[]"]').forEach(function(checkbox) {
            checkbox.addEventListener('change', window.hitungTotal);
        });

        // Hitung total awal
        setTimeout(window.hitungTotal, 100);

        // Fungsi untuk toggle form obat
        function toggleObatForm(value) {
            const obatForm = document.getElementById('obatForm');
            const obatListSection = document.getElementById('obatListSection');

            if (value === 'yes') {
                if (obatForm) {
                    obatForm.style.display = 'block';
                    obatForm.classList.remove('hidden');
                }
                if (obatListSection && window.obatList && window.obatList.length > 0) {
                    obatListSection.style.display = 'block';
                    obatListSection.classList.remove('hidden');
                }
            } else {
                if (obatForm) {
                    obatForm.style.display = 'none';
                    obatForm.classList.add('hidden');
                }
                if (obatListSection) {
                    obatListSection.style.display = 'none';
                    obatListSection.classList.add('hidden');
                }
                clearObatForm();
                window.obatList = [];
                updateObatTable();
            }
        }

        // Inisialisasi array obat
        window.obatList = window.obatList || [];

        let obatCounter = 0;

        // Fungsi untuk tambah field obat
        function tambahFieldObat() {
            const container = document.getElementById('obatContainer');
            const obatDiv = document.createElement('div');
            obatDiv.className = 'card bg-base-100 shadow-xl p-4 mt-4';
            obatDiv.id = `obat-${obatCounter}`;
            
            obatDiv.innerHTML = `
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-bold">New Medication</h3>
                    <button type="button" class="btn btn-sm btn-error" onclick="hapusFieldObat(${obatCounter})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Medicine Name</span>
                        </label>
                        <input type="text" name="obat_baru[${obatCounter}][nama]" class="input input-bordered" required>
                    </div>
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Dosage</span>
                        </label>
                        <input type="text" name="obat_baru[${obatCounter}][dosis]" class="input input-bordered" required>
                    </div>
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Frequency</span>
                        </label>
                        <input type="text" name="obat_baru[${obatCounter}][frekuensi]" class="input input-bordered" required>
                    </div>
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Category</span>
                        </label>
                        <select name="obat_baru[${obatCounter}][kategori_id]" class="select select-bordered" required>
                            <option value="">Select Category</option>
                            <?php foreach ($kategoriObatList as $kategori): ?>
                                <option value="<?= htmlspecialchars($kategori['ID']) ?>">
                                    <?= htmlspecialchars($kategori['NAMA']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-control col-span-2">
                        <label class="label">
                            <span class="label-text">Instructions</span>
                        </label>
                        <textarea name="obat_baru[${obatCounter}][instruksi]" class="textarea textarea-bordered" required></textarea>
                    </div>
                </div>
            `;
            
            container.appendChild(obatDiv);
            obatDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
            obatCounter++;
        }

        // Fungsi untuk menghapus field obat
        function hapusFieldObat(index) {
            const obatDiv = document.getElementById(`obat-${index}`);
            if (obatDiv) {
                obatDiv.remove();
            }
        }

        // Fungsi untuk menghapus obat yang sudah ada
        function removeExistingObat(obatId) {
            if (confirm('Are you sure you want to delete this medication?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'delete-obat.php';
                form.innerHTML = `<input type="hidden" name="obat_id" value="${obatId}">`;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Fungsi untuk handle submit form
        function handleSubmit(event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);

            // Kumpulkan data obat baru
            const obatContainer = document.getElementById('obatContainer');
            const obatDivs = obatContainer.querySelectorAll('[id^="obat-"]');
            const obatBaru = [];

            obatDivs.forEach(div => {
                const index = div.id.split('-')[1];
                const obat = {
                    nama: formData.get(`obat_baru[${index}][nama]`),
                    dosis: formData.get(`obat_baru[${index}][dosis]`),
                    frekuensi: formData.get(`obat_baru[${index}][frekuensi]`),
                    instruksi: formData.get(`obat_baru[${index}][instruksi]`),
                    kategori_id: formData.get(`obat_baru[${index}][kategori_id]`)
                };
                
                // Hanya tambahkan jika semua field diisi
                if (obat.nama && obat.dosis && obat.frekuensi && obat.instruksi && obat.kategori_id) {
                    obatBaru.push(obat);
                }
            });

            // Set nilai obat_list
            formData.set('obat_list', JSON.stringify(obatBaru));

            fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Redirect ke dashboard
                    window.location.href = '/pemay/pages/pet-medical/dashboard.php?message=' + encodeURIComponent('Data updated successfully');
                } else {
                    throw new Error(data.message || 'An error occurred');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating data: ' + error.message);
            });

            return false;
        }

        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM Content Loaded');
            
            // Set initial state of obat form
            const obatPertanyaan = document.getElementById('obat_pertanyaan');
            
            <?php if (!empty($resepObatList)): ?>
            // Jika ada resep obat, set nilai select ke "yes" dan tampilkan form
            if (obatPertanyaan) {
                obatPertanyaan.value = 'yes';
                toggleObatForm('yes');
            }
            <?php else: ?>
            // Jika tidak ada resep obat, cek nilai saat ini
            if (obatPertanyaan) {
                const value = obatPertanyaan.value;
                if (value === 'yes') {
                    toggleObatForm('yes');
                }
            }
            <?php endif; ?>
            
            // Initialize obatList if not exists
            if (!window.obatList) {
                window.obatList = [];
            }

            // Pastikan total biaya dihitung setelah semua elemen dimuat
            setTimeout(() => {
                console.log('Calculating initial total...');
                hitungTotal();
            }, 100);
            
            // Tambahkan event listener untuk setiap checkbox jenis layanan
            const checkboxes = document.querySelectorAll('input[name="jenis_layanan[]"]');
            checkboxes.forEach(function(checkbox) {
                checkbox.addEventListener('change', hitungTotal);
            });
        });

        // Fungsi untuk handle close drawer
        function closeDrawer() {
            window.location.href = 'dashboard.php';
        }

        // Tambahkan event listener untuk tombol close
        document.addEventListener('DOMContentLoaded', function() {
            const closeBtn = document.querySelector('.btn-circle');
            if (closeBtn) {
                closeBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    closeDrawer();
                });
            }
        });

        // Tambahkan event listener untuk drawer
        document.addEventListener('DOMContentLoaded', function() {
            const drawer = document.getElementById('update-medical-drawer');
            if (drawer) {
                drawer.addEventListener('change', function() {
                    if (this.checked) {
                        setTimeout(hitungTotal, 100);
                    }
                });
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            // Initialize status buttons
            const statusLabels = document.querySelectorAll('label:has(input[name="status"])');
            
            function updateCircles() {
                statusLabels.forEach(label => {
                    const input = label.querySelector('input[type="radio"]');
                    const circle = label.querySelector('div');
                    if (input && circle) {
                        if (input.checked) {
                            circle.classList.add('bg-[#363636]');
                        } else {
                            circle.classList.remove('bg-[#363636]');
                        }
                    }
                });
            }

            // Add click event to each label
            statusLabels.forEach(label => {
                label.addEventListener('click', function() {
                    const input = this.querySelector('input[type="radio"]');
                    if (input) {
                        input.checked = true;
                        updateCircles();
                    }
                });
            });

            // Set initial state
            updateCircles();
        });
        </script>

        <!-- Form Obat yang Lebih Sederhana -->
        <div class="divider">Medication Information</div>

        <!-- Tampilkan obat yang sudah ada dalam tabel -->
        <?php if (!empty($resepObatList)): ?>
            <div class="mb-4">
                <h4 class="font-semibold mb-2">Used Medications</h4>
                <table class="table w-full">
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
                    <tbody>
                        <?php foreach ($resepObatList as $obat): ?>
                            <tr>
                                <td><?= htmlspecialchars($obat['NAMA']) ?></td>
                                <td><?= htmlspecialchars($obat['DOSIS']) ?></td>
                                <td><?= htmlspecialchars($obat['FREKUENSI']) ?></td>
                                <td><?= htmlspecialchars($obat['INSTRUKSI']) ?></td>
                                <td><?= htmlspecialchars($obat['KATEGORINAMA']) ?></td>
                                <td>
                                    <button type="button" 
                                            onclick="removeExistingObat('<?= $obat['ID'] ?>')" 
                                            class="btn btn-sm bg-red-100 hover:bg-red-200 text-red-800 border-none">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- Form Tambah Obat Baru -->
        <div class="form-control w-full">
            <label class="label">
                <span class="label-text font-semibold">Add New Medication?</span>
            </label>
            <div class="flex gap-4">
                <button type="button" class="btn btn-primary" onclick="tambahFieldObat()">
                    <i class="fas fa-plus mr-2"></i>Add Medication
                </button>
            </div>
        </div>

        <!-- Container untuk field obat dinamis -->
        <div id="obatContainer"></div>
        
        <button type="submit" class="btn btn-primary w-full">Save Changes</button>
    </form>
</div>