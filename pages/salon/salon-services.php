<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['posisi'] != 'staff') {
    header("Location: ../../auth/restricted.php");
    exit();
}

$salonSerivicesSql = "SELECT * FROM JenisLayananSalon WHERE onDelete = 0";
$salonSerivicesStmt = oci_parse($conn, $salonSerivicesSql);
oci_execute($salonSerivicesStmt);
$salonSerivices = [];
while ($row = oci_fetch_assoc($salonSerivicesStmt)) {
    $salonSerivices[] = $row;
}
oci_free_statement($salonSerivicesStmt);

// Ambil Data Layanan Salon
$sql = "SELECT ls.ID, ls.Tanggal, ls.TotalBiaya, ls.Status, 
               h.Nama AS NamaHewan, h.Spesies, 
               ph.Nama AS NamaPemilik, ph.NomorTelpon,
               h.ID AS Hewan_ID,
               (
                SELECT LISTAGG(COLUMN_VALUE, ',') WITHIN GROUP (ORDER BY COLUMN_VALUE)
                FROM TABLE(ls.JenisLayanan)
                ) as JenisLayanan,
               ls.PEGAWAI_ID
        FROM LayananSalon ls
        JOIN Hewan h ON ls.Hewan_ID = h.ID
        JOIN PemilikHewan ph ON h.PemilikHewan_ID = ph.ID
        WHERE ls.onDelete = 0
        ORDER BY ls.Tanggal DESC";
$stmt = oci_parse($conn, $sql);

if (!oci_execute($stmt)) {
    $error = oci_error($stmt);
    die("Terjadi kesalahan saat mengambil data: " . htmlentities($error['message']));
}

$layananSalon = [];
while ($row = oci_fetch_assoc($stmt)) {
    $layananSalon[] = $row;
}
oci_free_statement($stmt);
oci_close($conn);
?>

<section>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        * {
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #565656;
        }

        .table>:not(caption)>*>* {
            border: none;
            padding-top: 1rem;
            padding-bottom: 1rem;
        }
    </style>
</section>

<body data-theme="light">
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="font-bold"><i>Manage Salon Services</i></h2>
        </div>

        <div role="tablist" class="tabs tabs-lifted pt-5">
            <input type="radio" name="salon_tabs" role="tab"
                class="tab h-12 text-lg font-semibold w-full px-5 !rounded-t-3xl" aria-label="Salon Service"
                checked="checked" />
            <div role="tabpanel" class="tab-content bg-base-100 border-base-300 rounded-box p-6">
                <h3 class="font-bold text-xl mb-3">Registered Salon Services</h3>

                <?php if (isset($_SESSION['success_message'])): ?>
                    <div role="alert" class="alert alert-success">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0 stroke-current" fill="none"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span><?= $_SESSION['success_message']; ?></span>
                    </div>
                <?php endif;
                unset($_SESSION['success_message']); ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div role="alert" class="alert alert-error">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0 stroke-current" fill="none"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span><?= $_SESSION['error_message']; ?></span>
                    </div>
                <?php endif;
                unset($_SESSION['error_message']); ?>

                <?php if (empty($layananSalon)): ?>
                    <div role="alert" class="alert alert-info">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            class="h-6 w-6 shrink-0 stroke-current">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>Tidak ada data layanan Salon untuk ditampilkan.</span>
                    </div>
                <?php endif; ?>

                <div class="overflow-x-auto rounded-xl border">
                    <style>
                        table>*>*>*:last-child {
                            padding-right: 1.5rem;
                        }

                        table>*>*>*:first-child {
                            padding-left: 1.5rem;
                        }
                    </style>
                    <table class="table mb-0">
                        <!-- head -->
                        <thead>
                            <tr>
                                <th class="!bg-gray-300">Tanggal</th>
                                <th class="!bg-gray-300">Total Biaya</th>
                                <th class="!bg-gray-300">Status</th>
                                <th class="!bg-gray-300">Nama Hewan</th>
                                <th class="!bg-gray-300">Spesies</th>
                                <th class="!bg-gray-300">Nama Pemilik</th>
                                <th class="!bg-gray-300">No. Telepon</th>
                                <th class="!bg-gray-300"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($layananSalon)): ?>
                                <?php foreach ($layananSalon as $layanan): ?>
                                    <tr>
                                        <td><?= htmlentities($layanan['TANGGAL']); ?></td>
                                        <td>Rp <?= number_format($layanan['TOTALBIAYA'], 0, ',', '.'); ?></td>
                                        <td><?= htmlentities($layanan['STATUS']); ?></td>
                                        <td><?= htmlentities($layanan['NAMAHEWAN']); ?></td>
                                        <td><?= htmlentities($layanan['SPESIES']); ?></td>
                                        <td><?= htmlentities($layanan['NAMAPEMILIK']); ?></td>
                                        <td><?= htmlentities($layanan['NOMORTELPON']); ?></td>
                                        <td class="flex justify-start">
                                            <?php if ($_SESSION['employee_id'] == $layanan['PEGAWAI_ID']): ?>
                                                <div class="drawer drawer-end">
                                                    <input id="drawer-edit-salon-<?= $layanan['ID']; ?>" type="checkbox"
                                                        class="drawer-toggle" />
                                                    <div class="drawer-content">
                                                        <label for="drawer-edit-salon-<?= $layanan['ID']; ?>">
                                                            <i class="text-[#E7A906] text-xl fa-solid fa-pen-to-square"></i>
                                                        </label>
                                                    </div>
                                                    <div class="drawer-side z-10">
                                                        <label for="drawer-edit-salon-<?= $layanan['ID']; ?>"
                                                            aria-label="close sidebar" class="drawer-overlay"></label>
                                                        <div
                                                            class="p-4 w-[600px] min-h-full bg-white text-base-content flex items-center">
                                                            <div id="add-form-content" class="w-full">
                                                                <div class="p-4">
                                                                    <div class="flex justify-between items-center mb-4">
                                                                        <h3 class="font-bold text-lg text-black">Update Salon
                                                                            Service
                                                                        </h3>
                                                                    </div>
                                                                    <form method="POST" action="update-salon-services.php"
                                                                        class="space-y-4 data-form" id="editSalonForm">
                                                                        <input type="hidden" name="id"
                                                                            value="<?= $layanan['ID']; ?>">
                                                                        <label class="form-control !border-none !p-0 w-full">
                                                                            <div class="label">
                                                                                <span class="label-text">Tanggal</span>
                                                                            </div>
                                                                            <input name="tanggal" type="datetime-local"
                                                                                placeholder="Type here"
                                                                                class="input input-bordered w-full rounded-3xl"
                                                                                value="<?= htmlentities($layanan['TANGGAL']); ?>"
                                                                                min="<?= htmlentities($layanan['TANGGAL']); ?>" />
                                                                        </label>
                                                                        <div class="form-control !border-none !p-0 w-full">
                                                                            <style>
                                                                                /* Style for radio button dots */
                                                                                input[type="radio"]:checked+.radio-dot {
                                                                                    background-color: #363636;
                                                                                }
                                                                            </style>
                                                                            <div class="label">
                                                                                <span class="label-text">Status</span>
                                                                            </div>
                                                                            <div class="flex gap-4">

                                                                                <label
                                                                                    class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-[#E4E1F9] hover:bg-[#E4E1F9]/80 cursor-pointer border border-[#363636] text-black">
                                                                                    <input
                                                                                        <?= htmlentities($layanan['STATUS']) == 'Waiting' ? 'checked' : ''; ?> type="radio"
                                                                                        name="status" value="Waiting"
                                                                                        class="hidden" required>
                                                                                    <div
                                                                                        class="radio-dot w-2 h-2 rounded-full border-2 border-[#363636] transition-colors">
                                                                                    </div>
                                                                                    Waiting
                                                                                </label>

                                                                                <label
                                                                                    class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-[#EFEFEF] hover:bg-[#EFEFEF]/80 cursor-pointer border border-[#363636] text-black">
                                                                                    <input <?= htmlentities($layanan['STATUS']) == 'In Progress' ? 'checked' : ''; ?> type="radio"
                                                                                        name="status" value="In Progress"
                                                                                        class="hidden" required>
                                                                                    <div
                                                                                        class="radio-dot w-2 h-2 rounded-full border-2 border-[#363636] transition-colors">
                                                                                    </div>
                                                                                    In Progress
                                                                                </label>

                                                                                <label
                                                                                    class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-[#EFEFEF] hover:bg-[#EFEFEF]/80 cursor-pointer border border-[#363636] text-black">
                                                                                    <input
                                                                                        <?= htmlentities($layanan['STATUS']) == 'Completed' ? 'checked' : ''; ?> type="radio"
                                                                                        name="status" value="Completed"
                                                                                        class="hidden" required>
                                                                                    <div
                                                                                        class="radio-dot w-2 h-2 rounded-full border-2 border-[#363636] transition-colors">
                                                                                    </div>
                                                                                    Completed
                                                                                </label>
                                                                            </div>
                                                                        </div>
                                                                        <label class="form-control !border-none !p-0 w-full">
                                                                            <style>
                                                                                .select2-container--default {
                                                                                    width: 100% !important;
                                                                                }

                                                                                .select2-container--default .select2-selection--single {
                                                                                    border-radius: 24px;
                                                                                    padding: 8px;
                                                                                    border: 1px solid #ccc;
                                                                                    height: auto;
                                                                                }
                                                                            </style>
                                                                            <div class="label">
                                                                                <span class="label-text">Hewan</span>
                                                                            </div>
                                                                            <div class="flex gap-4">
                                                                                <!-- Readonly hewan select -->
                                                                                <select readonly name="hewan_id" required
                                                                                    class="select select-bordered w-full rounded-3xl">
                                                                                    <?php
                                                                                    $db = new Database();
                                                                                    $db->query("SELECT h.ID, h.Nama as NamaHewan, h.Spesies, ph.Nama as NamaPemilik 
                                                                                    FROM Hewan h 
                                                                                    JOIN PemilikHewan ph ON h.PemilikHewan_ID = ph.ID 
                                                                                    WHERE h.onDelete = 0 AND ph.onDelete = 0 AND h.ID = '${layanan['HEWAN_ID']}'
                                                                                    ORDER BY h.Nama");
                                                                                    $hewanList = $db->resultSet();
                                                                                    foreach ($hewanList as $hewan): ?>
                                                                                        <option
                                                                                            <?= htmlentities($layanan['HEWAN_ID']) == $hewan['ID'] ? 'selected' : ''; ?>
                                                                                            value="<?= htmlspecialchars($hewan['ID']) ?>">
                                                                                            <?= htmlspecialchars($hewan['NAMAHEWAN']) ?>
                                                                                            (<?= htmlspecialchars($hewan['SPESIES']) ?>)
                                                                                            -
                                                                                            Owner:
                                                                                            <?= htmlspecialchars($hewan['NAMAPEMILIK']) ?>
                                                                                        </option>
                                                                                    <?php endforeach; ?>
                                                                                </select>
                                                                            </div>
                                                                        </label>
                                                                        <div class="form-control !border-none !p-0 w-full">
                                                                            <div class="label">
                                                                                <span class="label-text">Layanan salon</span>
                                                                            </div>
                                                                            <?php
                                                                            $variableSafeId = str_replace('-', '_', htmlspecialchars($layanan['ID']));
                                                                            $userSelectedLayanan = explode(',', $layanan['JENISLAYANAN']);
                                                                            foreach ($salonSerivices as $service): ?>
                                                                                <label
                                                                                    class="label cursor-pointer justify-start gap-4 bg-white">
                                                                                    <input
                                                                                        id="jenis_layanan_<?= htmlspecialchars($variableSafeId) ?>"
                                                                                        <?= in_array($service['ID'], $userSelectedLayanan) ? 'checked="checked"' : ''; ?> type="checkbox" class="checkbox bg-white"
                                                                                        name="jenis_layanan[]"
                                                                                        value="<?= htmlspecialchars($service['ID']) ?>"
                                                                                        data-biaya="<?= htmlspecialchars($service['BIAYA']) ?>">
                                                                                    <span
                                                                                        class="label-text text-black"><?= htmlspecialchars($service['NAMA']) ?>
                                                                                        - Cost: Rp
                                                                                        <?= number_format($service['BIAYA'], 0, ',', '.') ?></span>
                                                                                </label>
                                                                            <?php endforeach; ?>
                                                                        </div>

                                                                        <!-- Total Cost Display -->
                                                                        <div class="w-full">
                                                                            <label class="label">
                                                                                <span
                                                                                    class="label-text font-semibold text-black">Total
                                                                                    Cost</span>
                                                                            </label>
                                                                            <input type="text"
                                                                                id="addTotalBiayaDisplay<?= $variableSafeId ?>"
                                                                                class="input input-bordered rounded-3xl w-full bg-white text-black"
                                                                                readonly>
                                                                            <input type="hidden" name="total_biaya"
                                                                                id="addTotalBiaya<?= $variableSafeId ?>">
                                                                        </div>

                                                                        <script>
                                                                            // Event listener untuk checkbox jenis layanan
                                                                            let checkboxesUpdate<?= $variableSafeId ?> = document.querySelectorAll('#jenis_layanan_<?= $variableSafeId ?>');
                                                                            function checkboxesChange<?= $variableSafeId ?>() {
                                                                                let total = 0;
                                                                                checkboxesUpdate<?= $variableSafeId ?>.forEach(cb => {
                                                                                    if (cb.checked) {
                                                                                        total += parseInt(cb.dataset.biaya || 0);
                                                                                    }
                                                                                });
                                                                                document.getElementById('addTotalBiayaDisplay<?= $variableSafeId ?>').value = `Rp ${total.toLocaleString('id-ID')}`;
                                                                                document.getElementById('addTotalBiaya<?= $variableSafeId ?>').value = total;
                                                                            }
                                                                            checkboxesChange<?= $variableSafeId ?>();
                                                                            checkboxesUpdate<?= $variableSafeId ?>.forEach(checkbox => {
                                                                                checkbox.addEventListener('change', checkboxesChange<?= $variableSafeId ?>);
                                                                            });
                                                                        </script>

                                                                        <div class="flex justify-end gap-2">
                                                                            <button type="submit"
                                                                                class="btn !bg-[#B2B5E0] text-[#565656] !rounded-3xl !flex !items-center !gap-2 !px-5">
                                                                                <i class="fas fa-save"></i>
                                                                                Update Item
                                                                            </button>
                                                                            <button type="button"
                                                                                class="btn !bg-[#E0BAB2] text-[#565656] !rounded-3xl !flex !items-center !gap-2 !px-5">
                                                                                Cancel
                                                                            </button>
                                                                        </div>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <form action="delete-salon-services.php" method="POST">
                                                    <input type="hidden" name="id" value="<?= $layanan['ID']; ?>">
                                                    <button type="submit">
                                                        <i class="text-[#DC3545] fa-solid fa-trash text-xl"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" class="text-center">Tidak ada data tersedia.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="drawer drawer-end">
        <input id="drawer-add-salon" type="checkbox" class="drawer-toggle" />
        <div class="drawer-content">
            <!-- Floating Add Button -->
            <label for="drawer-add-salon" id="fab"
                class="hidden bg-[#D4F0EA] w-14 h-14 flex justify-center items-center rounded-full fixed bottom-5 right-5 border border-[#363636] shadow-md shadow-[#717171]">
                <i class="fas fa-plus fa-lg"></i>
            </label>
        </div>
        <div class="drawer-side">
            <label for="drawer-add-salon" aria-label="close sidebar" class="drawer-overlay"></label>
            <div class="p-4 w-[600px] min-h-full bg-white text-base-content flex items-center">
                <div id="add-form-content" class="w-full">
                    <div class="p-4">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="font-bold text-lg text-black">Add Salon Service</h3>
                        </div>

                        <form method="POST" action="add-salon-services.php" class="space-y-4 data-form"
                            id="addSalonForm" aria-action="add">
                            <label class="form-control !border-none !p-0 w-full">
                                <div class="label">
                                    <span class="label-text">Tanggal</span>
                                </div>
                                <?php
                                $dateStartAllowed = (new DateTime('now', new DateTimeZone('Asia/Jakarta')))
                                    ->modify('+1 hour')
                                    ->format('Y-m-d\TH:i');
                                ?>
                                <input name="tanggal" type="datetime-local" placeholder="Type here"
                                    class="input input-bordered w-full rounded-3xl" value="<?= $dateStartAllowed; ?>"
                                    min="<?= $dateStartAllowed; ?>" />
                            </label>
                            <div class="form-control !border-none !p-0 w-full">
                                <style>
                                    /* Style for radio button dots */
                                    input[type="radio"]:checked+.radio-dot {
                                        background-color: #363636;
                                    }
                                </style>
                                <div class="label">
                                    <span class="label-text">Status</span>
                                </div>
                                <div class="flex gap-4">
                                    <label
                                        class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-[#E4E1F9] hover:bg-[#E4E1F9]/80 cursor-pointer border border-[#363636] text-black">
                                        <input type="radio" name="status" value="Waiting" class="hidden" required>
                                        <div
                                            class="radio-dot w-2 h-2 rounded-full border-2 border-[#363636] transition-colors">
                                        </div>
                                        Waiting
                                    </label>

                                    <label
                                        class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-[#EFEFEF] hover:bg-[#EFEFEF]/80 cursor-pointer border border-[#363636] text-black">
                                        <input type="radio" name="status" value="Done" class="hidden" required>
                                        <div
                                            class="radio-dot w-2 h-2 rounded-full border-2 border-[#363636] transition-colors">
                                        </div>
                                        In Progress
                                    </label>

                                    <label
                                        class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-[#EFEFEF] hover:bg-[#EFEFEF]/80 cursor-pointer border border-[#363636] text-black">
                                        <input type="radio" name="status" value="Done" class="hidden" required>
                                        <div
                                            class="radio-dot w-2 h-2 rounded-full border-2 border-[#363636] transition-colors">
                                        </div>
                                        Completed
                                    </label>
                                </div>
                            </div>
                            <label class="form-control !border-none !p-0 w-full">
                                <style>
                                    .select2-container--default {
                                        width: 100% !important;
                                    }

                                    .select2-container--default .select2-selection--single {
                                        border-radius: 24px;
                                        padding: 8px;
                                        border: 1px solid #ccc;
                                        height: auto;
                                    }
                                </style>
                                <div class="label">
                                    <span class="label-text">Hewan</span>
                                </div>
                                <div class="flex gap-4">
                                    <select name="hewan_id" required
                                        class="select2 select select-bordered w-full rounded-3xl">
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
                            </label>
                            <div class="form-control !border-none !p-0 w-full">
                                <div class="label">
                                    <span class="label-text">Layanan salon</span>
                                </div>
                                <?php foreach ($salonSerivices as $layanan): ?>
                                    <label class="label cursor-pointer justify-start gap-4 bg-white">
                                        <input type="checkbox" class="checkbox bg-white" name="jenis_layanan[]"
                                            value="<?= htmlspecialchars($layanan['ID']) ?>"
                                            data-biaya="<?= htmlspecialchars($layanan['BIAYA']) ?>">
                                        <span class="label-text text-black"><?= htmlspecialchars($layanan['NAMA']) ?>
                                            - Cost: Rp <?= number_format($layanan['BIAYA'], 0, ',', '.') ?></span>
                                    </label>
                                <?php endforeach; ?>
                                <script>
                                    // Event listener untuk checkbox jenis layanan
                                    const checkboxes = document.querySelectorAll('input[name="jenis_layanan[]"]');
                                    checkboxes.forEach(checkbox => {
                                        checkbox.addEventListener('change', function () {
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
                                </script>
                            </div>

                            <!-- Total Cost Display -->
                            <div class="w-full">
                                <label class="label">
                                    <span class="label-text font-semibold text-black">Total Cost</span>
                                </label>
                                <input type="text" id="addTotalBiayaDisplay"
                                    class="input input-bordered rounded-3xl w-full bg-white text-black" readonly>
                                <input type="hidden" name="total_biaya" id="addTotalBiaya">
                            </div>

                            <div class="flex justify-end gap-2">
                                <button type="submit"
                                    class="btn !bg-[#B2B5E0] text-[#565656] !rounded-3xl !flex !items-center !gap-2 !px-5">
                                    <i class="fas fa-plus"></i>
                                    Add Item
                                </button>
                                <button type="button"
                                    class="btn !bg-[#E0BAB2] text-[#565656] !rounded-3xl !flex !items-center !gap-2 !px-5">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="handlers/formSubmitHandler.js"></script>
    <script>
        const fab = document.getElementById("fab");
        function updateCurrentlyActiveTab(id) {
            if (id === "Salon Service") {
                fab.classList.remove("hidden");
            } else {
                fab.classList.add("hidden");
            }

            console.log(id);
        }

        document.querySelectorAll("input[type=radio][role=tab]").forEach(tab => {
            tab.addEventListener("click", function () {
                updateCurrentlyActiveTab(this.ariaLabel);
            });

            if (tab.checked) {
                updateCurrentlyActiveTab(tab.ariaLabel);
            }
        });
    </script>
</body>

</html>