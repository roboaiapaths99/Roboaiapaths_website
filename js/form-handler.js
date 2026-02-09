/**
 * Form Handler for Robo AIA Paths
 * This script intercepts form submissions and sends the data to Google Sheets via Apps Script.
 */

// -----------------------------------------------------------------------------
// CONFIGURATION
// -----------------------------------------------------------------------------

// REPLACE THE URL BELOW WITH YOUR DEPLOYED GOOGLE APPS SCRIPT WEB APP URL
const WEB_APP_URL = "https://script.google.com/macros/s/AKfycbzd8MKeO0IWM3sfP2dnOE_3KTeIfAIKR2X3fIMfG131CUKj0rLQ4DvTbznKLSsAIa6w/exec";

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
            // Using no-cors mode to avoid CORS errors on localhost
            fetch(WEB_APP_URL, {
                method: 'POST',
                body: formData,
                mode: 'no-cors'
            })
                .then(() => {
                    // With no-cors, response is opaque. We assume success if fetch completes.
                    alert('Thank you! Your message has been sent successfully.');
                    form.reset();
                    if (form.classList.contains('was-validated')) {
                        form.classList.remove('was-validated');
                    }
                })
                .catch(error => {
                    console.error('Error!', error.message);
                    alert('Oops! Something went wrong. Please check your connection and try again.');
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
