
$(document).ready(function () {
  $('#ukuran').select2({
    placeholder: 'Choose Size',
    allowClear: true
  });

  $('#kandang').select2({
    placeholder: 'Choose Cage Room & Size',
    allowClear: true,
    width: '100%'
  }).hide();

  $('#kandang').next('.select2-container').hide();

  $('#reservatorID').select2({
    placeholder: 'Choose Name of Pet and Owner',
    allowClear: true
  });

  const now = new Date();
  const hours = now.getHours().toString().padStart(2, '0');
  const minutes = now.getMinutes().toString().padStart(2, '0');
  // Initialize flatpickr for checkIn and checkOut
  const initFlatpickr = () => {
    // Initialize checkIn flatpickr
    flatpickr("#checkIn", {
      enableTime: true,
      dateFormat: "Y-m-d H:i",
      minDate: "today", // Disable past dates
      minTime: `${hours}:${minutes}`, // Disable past times
      onChange: function (selectedDates, dateStr, instance) {
        const checkOut = document.getElementById('checkOut');
        checkOut._flatpickr.set('minDate', new Date(selectedDates[0].getTime() + 60000)); // 1 minute after checkIn
      }
    });

    // Initialize checkOut flatpickr
    flatpickr("#checkOut", {
      enableTime: true,
      dateFormat: "Y-m-d H:i",
      minDate: "today", // Disable past dates
      minTime: `${hours}:${minutes + 1}`, // Disable past times
    });
  };

  const checkIn = document.getElementById('checkIn');
  const checkOut = document.getElementById('checkOut');
  let cageSize = null;

  // Event listener for reservatorID using Select2
  $('#reservatorID').on('change', function () {
    if ($(this).val() !== '') {
      checkIn.disabled = false;
      checkOut.disabled = false;

      initFlatpickr();
    } else {
      checkIn.disabled = true;
      checkOut.disabled = true;
    }
  });

  $('#kandang').on('change', function () {
    // Get the cage size (UKURAN) based on the selected option's data-size attribute
    cageSize = $('#kandang option:selected').data('size');
    // Check if a value has been selected in the kandang select2
    if ($(this).val() !== '') {
      // Show the price-related elements (button and input field)
      $('#checkPriceBtn').show();
      $('#biaya').show();
    } else {
      // Hide the price-related elements if no value is selected
      $('#checkPriceBtn').hide();
      $('#biaya').hide();
    }
  });

  $('#checkPriceBtn').click(function () {
    // Get values from form inputs
    let checkin = $('#checkIn').val();
    let checkout = $('#checkOut').val();


    // Validate that checkin and checkout are filled
    if (!checkin || !checkout) {
      alert('Please select both check-in and check-out dates.');
      return;
    }

    // Calculate the duration in milliseconds
    let checkinDate = new Date(checkin);
    let checkoutDate = new Date(checkout);

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

    // Display the result
    console.log("price: ", price);

    $('#price').val(price);
    $('#biaya').val("Price: Rp " + price.toLocaleString());
  });
});

document.addEventListener('DOMContentLoaded', () => {
  const reservatorID = document.getElementById('reservatorID');
  const checkIn = document.getElementById('checkIn');
  const checkOut = document.getElementById('checkOut');
  const cageLabel = document.getElementById('cageLabel');

  // Enable checkIn and checkOut when reservator is selected
  reservatorID.addEventListener('change', () => {
    if (reservatorID.value !== '') {
      checkIn.disabled = false;
      checkOut.disabled = false;
    } else {
      checkIn.disabled = true;
      checkOut.disabled = true;
      cageLabel.style.display = 'none';
      $('#kandang').next('.select2-container').hide();
    }
  });

  // Show cage button and cage select when both dates are chosen
  const handleDateSelection = () => {
    if (checkIn.value !== '' && checkOut.value !== '') {
      cageLabel.style.display = 'block';
      $('#kandang').next('.select2-container').show();
    } else {
      cageLabel.style.display = 'none';
      $('#kandang').next('.select2-container').hide();
    }
  };

  checkIn.addEventListener('change', handleDateSelection);
  checkOut.addEventListener('change', handleDateSelection);

  handleDateSelection();
});