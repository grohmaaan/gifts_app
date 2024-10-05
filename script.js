// script.js

document.addEventListener('DOMContentLoaded', function () {
    // Add event listeners to all reservation forms
    var reservationForms = document.querySelectorAll('form[action="index.php"]');
    reservationForms.forEach(function (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();

            // Get the gift ID from the form
            var giftId = form.querySelector('input[name="gift_id"]').value;

            // Make an AJAX request to reserve the gift
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'index.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

            // Handle the AJAX response
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        // Successful reservation, update the UI
                        var reservedMessage = document.createElement('p');
                        reservedMessage.textContent = 'Reserved';
                        form.parentNode.appendChild(reservedMessage);
                        form.remove(); // Remove the form after reservation
                    } else {
                        // Handle the reservation error
                        console.error('Error reserving gift:', xhr.responseText);
                    }
                }
            };

            // Send the reservation request
            xhr.send('action=reserve&gift_id=' + giftId);
        });
    });
});
