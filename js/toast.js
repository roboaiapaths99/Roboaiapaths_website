/**
 * Toast Notification System for RoboAIAPaths
 * Usage: showToast('Your message here', 'success' | 'error' | 'info')
 */

function showToast(message, type = 'info', action = null) {
    // Create container if it doesn't exist
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    // Select icon based on type
    const icons = {
        success: '<i class="fas fa-check-circle"></i>',
        error: '<i class="fas fa-exclamation-circle"></i>',
        info: '<i class="fas fa-info-circle"></i>'
    };

    // Build toast element
    const toast = document.createElement('div');
    toast.className = `custom-toast toast-${type}`;

    let actionHtml = '';
    if (action && action.text && action.url) {
        actionHtml = `<a href="${action.url}" class="toast-action-btn">${action.text}</a>`;
    }

    toast.innerHTML = `
        <span class="toast-icon">${icons[type] || icons.info}</span>
        <div class="toast-message">
            ${message}
            ${actionHtml}
        </div>
        <button class="toast-close" aria-label="Close">&times;</button>
        <div class="toast-progress"></div>
    `;

    // Close button handler
    toast.querySelector('.toast-close').addEventListener('click', function () {
        dismissToast(toast);
    });

    // Append and auto-dismiss after 4 seconds
    container.appendChild(toast);

    const autoDismiss = setTimeout(function () {
        dismissToast(toast);
    }, 4000);

    // Store timeout so manual close can clear it
    toast._autoDismiss = autoDismiss;
}

function dismissToast(toast) {
    if (toast._dismissed) return;
    toast._dismissed = true;

    clearTimeout(toast._autoDismiss);
    toast.classList.add('toast-removing');

    toast.addEventListener('animationend', function () {
        toast.remove();
    });
}
