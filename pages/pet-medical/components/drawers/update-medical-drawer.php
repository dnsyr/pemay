<?php
// Update Medical Services Drawer
?>
<div class="drawer drawer-end">
    <input id="update-medical-drawer" type="checkbox" class="drawer-toggle" /> 
    <div class="drawer-side z-50">
        <label for="update-medical-drawer" class="drawer-overlay"></label>
        <div class="p-4 w-[600px] min-h-full bg-white text-base-content">
            <div id="update-form-content">
                <div class="p-4">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-bold text-lg text-black">Update Medical Service</h3>
                        <label for="update-medical-drawer" class="btn btn-sm btn-circle">✕</label>
                    </div>
                    
                    <form method="POST" action="update-medical-services.php" class="space-y-4" id="updateMedicalForm">
                        <input type="hidden" name="id" id="layananId">
                        
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
                                
                                <label class="inline-flex items-center gap-2 px-4 py-1 rounded-full bg-[#EFEFEF] hover:bg-[#EFEFEF]/80 cursor-pointer border border-[#363636] text-black">
                                    <input type="radio" name="status" value="Canceled" class="hidden" required>
                                    <div class="radio-dot w-2 h-2 rounded-full border-2 border-[#363636] transition-colors"></div>
                                    Canceled
                                </label>
                            </div>
                        </div>

                        <!-- Date Section -->
                        <div class="form-control w-full">
                            <label class="label">
                                <span class="label-text font-semibold">Date</span>
                            </label>
                            <input type="datetime-local" name="tanggal" id="tanggal"
                                   class="input input-bordered w-full bg-white text-black" required
                                   onkeydown="return false">
                        </div>

                        <!-- Description Section -->
                        <div class="form-control w-full">
                            <label class="label">
                                <span class="label-text font-semibold">Description</span>
                            </label>
                            <textarea name="description" id="description" class="textarea textarea-bordered w-full" 
                                      required></textarea>
                        </div>

                        <!-- Service Types Section -->
                        <div id="jenisLayananSection">
                            <label class="label">
                                <span class="label-text font-semibold">Service Types</span>
                            </label>
                            <div class="space-y-2" id="jenisLayananContainer">
                                <!-- Will be populated by AJAX -->
                            </div>
                        </div>

                        <!-- Total Cost Display -->
                        <div class="form-control w-full">
                            <label class="label">
                                <span class="label-text font-semibold">Total Cost</span>
                            </label>
                            <input type="text" id="totalBiayaDisplay" class="input input-bordered w-full" readonly>
                            <input type="hidden" name="total_biaya" id="totalBiaya">
                        </div>

                        <!-- Medication Information -->
                        <div class="divider">Medication Information</div>
                        
                        <div id="obatSection">
                            <!-- Tabel Obat -->
                            <div class="overflow-x-auto mb-4">
                                <table class="table table-zebra bg-white w-full">
                                    <thead>
                                        <tr class="bg-[#D4F0EA] text-[#363636] font-semibold">
                                            <th class="border-b border-[#363636]">Nama Obat</th>
                                            <th class="border-b border-[#363636]">Dosis</th>
                                            <th class="border-b border-[#363636]">Frekuensi</th>
                                            <th class="border-b border-[#363636]">Kategori</th>
                                            <th class="border-b border-[#363636]">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="obatTableBody">
                                        <!-- Will be populated by AJAX -->
                                    </tbody>
                                </table>
                            </div>

                            <!-- Form Tambah Obat -->
                            <div class="flex justify-between items-center mb-2">
                                <label class="label">
                                    <span class="label-text font-semibold">Add New Medication</span>
                                </label>
                                <button type="button" id="toggleObatForm" 
                                        class="btn btn-sm bg-[#D4F0EA] hover:bg-[#D4F0EA] text-[#363636]">
                                    <i class="fas fa-plus mr-2"></i> Add Medication
                                </button>
                            </div>
                            
                            <div id="obatForm" class="hidden bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="form-control">
                                        <label class="label">
                                            <span class="label-text">Nama Obat</span>
                                        </label>
                                        <input type="text" id="namaObat" class="input input-bordered w-full">
                                    </div>
                                    <div class="form-control">
                                        <label class="label">
                                            <span class="label-text">Dosis</span>
                                        </label>
                                        <input type="text" id="dosisObat" class="input input-bordered w-full">
                                    </div>
                                    <div class="form-control">
                                        <label class="label">
                                            <span class="label-text">Frekuensi</span>
                                        </label>
                                        <input type="text" id="frekuensiObat" class="input input-bordered w-full">
                                    </div>
                                    <div class="form-control">
                                        <label class="label">
                                            <span class="label-text">Kategori</span>
                                        </label>
                                        <select id="kategoriObat" class="select2 select select-bordered w-full bg-white text-black">
                                            <option value="">Pilih Kategori</option>
                                            <!-- Will be populated by AJAX -->
                                        </select>
                                    </div>
                                </div>
                                <div class="form-control mt-4">
                                    <label class="label">
                                        <span class="label-text">Instruksi</span>
                                    </label>
                                    <textarea id="instruksiObat" class="textarea textarea-bordered w-full"></textarea>
                                </div>
                                <div class="flex justify-end gap-2 mt-4">
                                    <button type="button" id="cancelObat" class="btn btn-ghost">
                                        Cancel
                                    </button>
                                    <button type="button" id="addObatToList" class="btn bg-[#D4F0EA] hover:bg-[#D4F0EA] text-[#363636]">
                                        Add to List
                                    </button>
                                </div>
                            </div>

                            <!-- Hidden input untuk menyimpan data obat -->
                            <input type="hidden" name="obat_list" id="obatListData">
                        </div>

                        <div class="flex justify-end gap-2 mt-6">
                            <label for="update-medical-drawer" class="btn btn-ghost">Cancel</label>
                            <button type="submit" class="btn bg-[#D4F0EA] hover:bg-[#D4F0EA] text-[#363636]">
                                Save Changes
                            </button>
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