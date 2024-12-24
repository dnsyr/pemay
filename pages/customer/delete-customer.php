<?php
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $db = new Database();
    $id = $_POST['id'];
    
    try {
        // Cek apakah customer memiliki hewan
        $query = "SELECT COUNT(*) as TOTAL FROM HEWAN WHERE PEMILIKHEWAN_ID = :id";
        $db->query($query);
        $db->bind(':id', $id);
        $result = $db->single();
        
        if ($result['TOTAL'] > 0) {
            $_SESSION['error_message'] = "Cannot delete customer. Please delete their pets first.";
            echo "<script>window.location.href = 'customer.php';</script>";
            exit();
        } else {
            // Hapus customer
            $query = "DELETE FROM PEMILIKHEWAN WHERE ID = :id";
            $db->query($query);
            $db->bind(':id', $id);
            
            if ($db->execute()) {
                $_SESSION['success_message'] = "Customer deleted successfully!";
                echo "<script>window.location.href = 'customer.php';</script>";
                exit();
            } else {
                $_SESSION['error_message'] = "Failed to delete customer.";
                echo "<script>window.location.href = 'customer.php';</script>";
                exit();
            }
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Database error: " . $e->getMessage();
        echo "<script>window.location.href = 'customer.php';</script>";
        exit();
    }
}
?>

<!-- Modal untuk konfirmasi delete customer -->
<dialog id="modalDeleteCustomer" class="modal modal-bottom sm:modal-middle">
  <div class="modal-box bg-[#FCFCFC]">
    <h3 class="font-bold text-lg text-[#363636]">Delete Customer</h3>
    <p class="py-4 text-[#363636]">Are you sure you want to delete this customer?</p>
    <div class="modal-action">
      <form method="POST">
        <input type="hidden" id="deleteCustomer" name="id" value="">
        <button type="submit" class="btn btn-error">Delete</button>
        <button type="button" class="btn btn-ghost" onclick="modalDeleteCustomer.close()">Cancel</button>
      </form>
    </div>
  </div>
  <form method="dialog" class="modal-backdrop">
    <button>close</button>
  </form>
</dialog>
