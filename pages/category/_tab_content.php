<!-- Add Form -->
<form method="POST" action="?tab=<?php echo $tab; ?>" class="flex gap-3 mb-4">
    <input type="hidden" name="action" value="add">
    <div class="w-1/4">
        <input type="text" class="w-full rounded-full bg-[#FCFCFC] border border-[#565656] text-[#565656] text-sm px-7 py-2" 
            id="namaKategori" name="namaKategori" placeholder="Enter <?php echo $currentLabel; ?> name" required>
    </div>
    <?php if ($tab === 'salon' || $tab === 'medis'): ?>
        <div class="w-1/4">
            <input type="number" class="w-full rounded-full bg-[#FCFCFC] border border-[#565656] text-[#565656] text-sm px-7 py-2" 
                id="biaya" name="biaya" placeholder="Enter price" required>
        </div>
    <?php endif; ?>
    <button type="submit" class="btn bg-[#D4F0EA] w-12 h-12 rounded-full hover:bg-[#565656] hover:text-[#FCFCFC] flex items-center justify-center">
        <i class="fas fa-plus"></i>
    </button>
</form>

<!-- Category List -->
<div class="overflow-hidden border border-[#363636] rounded-xl shadow-md shadow-[#717171] mt-3">
    <table class="table border-collapse w-full">
        <thead>
            <tr class="bg-[#D4F0EA] text-[#363636] font-semibold">
                <th class="rounded-tl-xl"><?php echo $currentLabel; ?> Name</th>
                <?php if ($tab === 'salon' || $tab === 'medis'): ?>
                    <th>Price</th>
                <?php endif; ?>
                <th class="rounded-tr-xl">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($categories)): ?>
                <?php foreach ($categories as $index => $category): ?>
                    <tr class="text-[#363636]">
                        <td class="<?= $index === count($categories) - 1 ? 'rounded-bl-xl' : '' ?>">
                            <?php echo htmlentities($category['NAMA']); ?>
                        </td>
                        <?php if ($tab === 'salon' || $tab === 'medis'): ?>
                            <td>Rp <?php echo number_format($category['BIAYA'], 0, ',', '.'); ?></td>
                        <?php endif; ?>
                        <td class="<?= $index === count($categories) - 1 ? 'rounded-br-xl' : '' ?>">
                            <div class="flex gap-3 justify-center items-center">
                                <a href="update-category.php?id=<?php echo $category['ID']; ?>&tab=<?php echo $tab; ?>" 
                                    class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="?tab=<?php echo $tab; ?>&delete_id=<?php echo $category['ID']; ?>" 
                                    class="btn btn-error btn-sm" 
                                    onclick="return confirm('Apakah Anda yakin ingin menghapus?')">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="<?php echo ($tab === 'salon' || $tab === 'medis') ? '3' : '2'; ?>" class="text-center">
                        Tidak ada data.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div> 