$(document).ready(function () {
  const updateCheckIn = document.getElementById('updateCheckIn');
  const updateCheckOut = document.getElementById('updateCheckOut');

  const now = new Date();
  const hours = now.getHours().toString().padStart(2, '0');
  const minutes = now.getMinutes().toString().padStart(2, '0');

  flatpickr("#updateCheckIn", {
    enableTime: true,
    dateFormat: "Y-m-d H:i",
    minDate: "today", // Disable past dates
    minTime: `${hours}:${minutes}`, // Disable past times
    onChange: function (selectedDates, dateStr, instance) {
      updateCheckOut._flatpickr.set('minDate', new Date(selectedDates[0].getTime() + 60000)); // 1 minute after checkIn

      updatePrice();
    }
  });

  // Initialize checkOut flatpickr
  flatpickr("#updateCheckOut", {
    enableTime: true,
    dateFormat: "Y-m-d H:i",
    // minDate: "today", // Disable past dates
    minTime: `${hours}:${minutes + 1}`, // Disable past times
    onReady: function (selectedDates, dateStr, instance) {
      if (updateCheckIn._flatpickr.selectedDates.length > 0) {
        const minDate = new Date(updateCheckIn._flatpickr.selectedDates[0].getTime() + 60000); // 1 minute after checkIn
        instance.set('minDate', minDate);
      }
    },
    onChange: function (selectedDates, dateStr, instance) {
      updatePrice()
    }
  });
});