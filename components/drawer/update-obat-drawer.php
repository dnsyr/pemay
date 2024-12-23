<?php
// Update Obat Drawer
?>
<div class="drawer drawer-end">
    <input id="update-obat-drawer" type="checkbox" class="drawer-toggle" /> 
    <div class="drawer-side z-50">
        <label for="update-obat-drawer" class="drawer-overlay"></label>
        <div class="p-4 w-[600px] min-h-full bg-base-200 text-base-content">
            <div id="update-obat-form-content">
                <?php 
                if (isset($_GET['obat_id'])) {
                    $_GET['id'] = $_GET['obat_id'];
                    include dirname(__FILE__) . '/../../update-obat.php';
                }
                ?>
            </div>
        </div>
    </div>
</div> 