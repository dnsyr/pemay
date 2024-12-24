<?php
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $db = new Database();
    $id = $_POST['id'];
    
    try {
        // Mulai transaksi
        $db->beginTransaction();

        // Ambil data hewan untuk pesan notifikasi
        $queryGet = "SELECT * FROM HEWAN WHERE ID = :id";
        $db->query($queryGet);
        $db->bind(':id', $id);
        $pet = $db->single();

        if (!$pet) {
            throw new Exception("Data hewan tidak ditemukan.");
        }
        
        // Soft delete data hewan (update ONDELETE menjadi 1)
        $queryDelete = "UPDATE HEWAN SET ONDELETE = 1 WHERE ID = :id";
        $db->query($queryDelete);
        $db->bind(':id', $id);
        
        if ($db->execute()) {
            // Verifikasi update berhasil
            $queryCheck = "SELECT ONDELETE FROM HEWAN WHERE ID = :id";
            $db->query($queryCheck);
            $db->bind(':id', $id);
            $result = $db->single();
            
            if ($result && isset($result['ONDELETE']) && $result['ONDELETE'] == 1) {
                // Commit transaksi jika berhasil
                $db->commit();
                $_SESSION['success_message'] = "Hewan " . htmlspecialchars($pet['NAMA']) . " berhasil dihapus!";
            } else {
                throw new Exception("Gagal mengubah status ONDELETE.");
            }
        } else {
            throw new Exception("Gagal menghapus data hewan.");
        }
    } catch (Exception $e) {
        // Rollback transaksi jika terjadi error
        $db->rollBack();
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
    }
    
    echo "<script>window.location.href = 'customer.php';</script>";
    exit();
}
?>

<!-- Modal untuk konfirmasi delete pet -->
<dialog id="modalDeletePet" class="modal modal-bottom sm:modal-middle">
  <div class="modal-box bg-[#FCFCFC]">
    <h3 class="font-bold text-lg text-[#363636]">Hapus Hewan</h3>
    <p class="py-4 text-[#363636]">Apakah Anda yakin ingin menghapus data hewan ini?</p>
    <div class="modal-action">
      <form method="POST" action="delete-pet.php">
        <input type="hidden" id="deletePet" name="id" value="">
        <button type="submit" class="btn btn-error">Hapus</button>
        <button type="button" class="btn btn-ghost" onclick="modalDeletePet.close()">Batal</button>
      </form>
    </div>
  </div>
  <form method="dialog" class="modal-backdrop">
    <button>close</button>
  </form>
</dialog>

<script>
// Fungsi untuk mengisi nilai ID dan menampilkan modal
function showDeletePetModal(id) {
    document.getElementById('deletePet').value = id;
    document.getElementById('modalDeletePet').showModal();
}
</script>