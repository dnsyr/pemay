$(document).ready(function () {
  const checkIn = document.getElementById('checkIn');
  const checkOut = document.getElementById('checkOut');

  const now = new Date();
  const hours = now.getHours().toString().padStart(2, '0');
  const minutes = now.getMinutes().toString().padStart(2, '0');

  flatpickr("#checkIn", {
    enableTime: true,
    dateFormat: "Y-m-d H:i",
    minDate: "today", // Disable past dates
    onChange: function (selectedDates, dateStr, instance) {
      const selectedDate = selectedDates[0];
      let isToday = selectedDate.toDateString() === now.toDateString();

      if (isToday) {
        checkIn._flatpickr.set('minTime', `${hours}:${minutes}`);
      }

      checkOut.disabled = false;
      checkOut._flatpickr.set('minDate', new Date(selectedDates[0].getTime() + 60000)); // 1 minute after checkIn
      checkPrice();
      undisabledBtnAddReservation()
    }
  });

  // Initialize checkOut flatpickr
  flatpickr("#checkOut", {
    enableTime: true,
    dateFormat: "Y-m-d H:i",
    minDate: "today", // Disable past dates
    onChange: function (selectedDates, dateStr, instance) {
      checkPrice()
      undisabledBtnAddReservation()
    }
  });
});