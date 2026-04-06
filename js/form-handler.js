/**
 * Form Handler for RoboAIAPaths
 * This script intercepts form submissions and sends the data to Google Sheets via Apps Script.
 */

// -----------------------------------------------------------------------------
// CONFIGURATION
// -----------------------------------------------------------------------------

// REPLACE THE URL BELOW WITH YOUR DEPLOYED GOOGLE APPS SCRIPT WEB APP URL
const WEB_APP_URL = "https://script.google.com/macros/s/AKfycbyXqI2Lkod8J2hh9jc2dGC5PiqChk1IiBu92_sSr2wUGnJ3kP-apCwFIKI7sxdDmqGtSQ/exec";

// -----------------------------------------------------------------------------
// MAIN LOGIC
// -----------------------------------------------------------------------------

document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('form');

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            // Basic validation (optional, as HTML5 validation runs first)
            if (!form.checkValidity()) {
                e.stopPropagation();
                form.classList.add('was-validated');
                return;
            }

            const submitButton = form.querySelector('button[type="submit"], input[type="submit"]');
            const originalButtonText = submitButton ? submitButton.innerText || submitButton.value : 'Send';

            // Change button state to indicate loading
            if (submitButton) {
                if (submitButton.tagName === 'INPUT') {
                    submitButton.value = 'Sending...';
                } else {
                    submitButton.innerText = 'Sending...';
                }
                submitButton.disabled = true;
            }

            // Collect form data
            const formData = new FormData(form);

            // Add a hidden field to identify which page the form came from
            formData.append('formSource', document.title);

            // Send data to Google Apps Script
            fetch(WEB_APP_URL, {
                method: 'POST',
                body: formData
            })
                .then(() => {
                    showToast('Thank you! Your message has been sent successfully.', 'success');
                    form.reset();
                    if (form.classList.contains('was-validated')) {
                        form.classList.remove('was-validated');
                    }
                })
                .catch(error => {
                    console.error('Error!', error.message);
                    showToast('Oops! Something went wrong. Please check your connection and try again.', 'error');
                })
                .finally(() => {
                    // Reset button state
                    if (submitButton) {
                        submitButton.disabled = false;
                        if (submitButton.tagName === 'INPUT') {
                            submitButton.value = originalButtonText;
                        } else {
                            submitButton.innerText = originalButtonText;
                        }
                    }
                });
        });
    }
});
