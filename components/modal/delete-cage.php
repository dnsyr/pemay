<!DOCTYPE html>
<html lang="en">

<body>
  <dialog id="modalDeleteCage" class="modal">
    <div class="modal-box bg-[#FCFCFC]">
      <p class="my-3 font-semibold text-lg text-[#363636]">Are you sure to delete data?</p>
      <div class="modal-action">
        <form method="POST" action="">
          <input type="hidden" name="action" value="deleteCage">
          <input type="hidden" id="deleteCageID" name="deleteCageID">
          <button
            type="submit"
            class="btn btn-error btn-sm">
            Yes, delete!
          </button>
        </form>
        <form method="dialog">
          <button class="btn bg-gray-400 text-black hover:bg-[#363636] hover:text-[#FCFCFC] btn-sm">Cancel</button>
        </form>
      </div>
    </div>
    <form method="dialog" class="modal-backdrop">
      <button>close</button>
    </form>
  </dialog>
</body>

</html>