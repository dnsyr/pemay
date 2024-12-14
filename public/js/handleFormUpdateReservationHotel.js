$(document).ready(function () {
  $('#updateKandang').select2({
    placeholder: 'Choose Cage Room & Size',
    allowClear: true,
    width: '100%'
  });

  $('#reservatorID').select2({
    placeholder: 'Choose Name of Pet and Owner',
    allowClear: true
  });

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

  // let cageSize = null;

  // $('#updateKandang').on('change', function () {
  //   // Get the cage size (UKURAN) based on the selected option's data-size attribute
  //   cageSize = $('#updateKandang option:selected').data('size');
  //   // Check if a value has been selected in the kandang select2
  //   if ($(this).val() !== '') {
  //     // Show the price-related elements (button and input field)
  //     $('#checkPriceBtn').show();
  //     $('#biaya').show();
  //   } else {
  //     // Hide the price-related elements if no value is selected
  //     $('#checkPriceBtn').hide();
  //     $('#biaya').hide();
  //   }
  // });

  function updatePrice() {
    // Get values from form inputs
    let updateCheckIn = $('#updateCheckIn').val();
    let updateCheckOut = $('#updateCheckOut').val();
    let cageSize = $('#kandangSize').val();


    // Validate that checkin and checkout are filled
    if (!updateCheckIn || !updateCheckOut) {
      alert('Please select both check-in and check-out dates.');
      return;
    }

    // Calculate the duration in milliseconds
    let checkinDate = new Date(updateCheckIn);
    let checkoutDate = new Date(updateCheckOut);

    // Ensure checkout is after checkin
    if (checkoutDate <= checkinDate) {
      alert('Check-out date must be after check-in date.');
      return;
    }

    let durationInMillis = checkoutDate - checkinDate; // Duration in milliseconds
    let durationInDays = durationInMillis / (1000 * 3600 * 24); // Convert to days

    // Round up to the next whole day if duration is less than 1 day
    let roundedDuration = Math.ceil(durationInDays);

    // Prices per day in IDR for each cage size
    let pricePerDay = {
      "XS": 20000,
      "S": 30000,
      "M": 50000,
      "L": 60000,
      "XL": 80000,
      "XXL": 90000,
      "XXXL": 100000
    };

    // Get the price per day based on the selected cage size
    let price = pricePerDay[cageSize] * roundedDuration;

    $('#updatePrice').val(price);
    $('#updatePlaceholder').val("Price: Rp " + price.toLocaleString());
  };

  // $('#checkUpdatePriceBtn').click(
  //   updatePrice()
  // );

  $('#updateBtn').click(
    updatePrice()
  );
});