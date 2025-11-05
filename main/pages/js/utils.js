/**
 * Utility functions for the Green Business Directory
 */

/**
 * Format date to readable string
 * @param {string|Date} dateString - ISO date string or Date object
 * @returns {string} Formatted date
 */
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-SG', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

/**
 * Format date with time
 * @param {string|Date} dateString - ISO date string or Date object
 * @returns {string} Formatted date with time
 */
function formatDateTime(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleString('en-SG', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

/**
 * Get relative time (e.g., "2 hours ago")
 * @param {string|Date} dateString - ISO date string or Date object
 * @returns {string} Relative time string
 */
function getRelativeTime(dateString) {
    if (!dateString) return 'Unknown';
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins} minute${diffMins !== 1 ? 's' : ''} ago`;
    if (diffHours < 24) return `${diffHours} hour${diffHours !== 1 ? 's' : ''} ago`;
    if (diffDays < 7) return `${diffDays} day${diffDays !== 1 ? 's' : ''} ago`;
    return formatDate(dateString);
}

/**
 * Get category icon class
 * @param {string} category - Business category
 * @returns {string} Font Awesome icon class
 */
function getCategoryIcon(category) {
    const icons = {
        'Technology': 'fas fa-laptop-code',
        'Food and Beverage': 'fas fa-leaf',
        'Food & Beverage': 'fas fa-leaf',
        'Energy': 'fas fa-solar-panel',
        'Retail': 'fas fa-shopping-bag',
        'Manufacturing': 'fas fa-industry',
        'Services': 'fas fa-briefcase'
    };
    return icons[category] || 'fas fa-building';
}

/**
 * Get initials from name
 * @param {string} name - Full name
 * @returns {string} Initials (max 2 characters)
 */
function getInitials(name) {
    if (!name) return '?';
    const parts = name.trim().split(' ');
    if (parts.length === 1) return parts[0].charAt(0).toUpperCase();
    return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
}

/**
 * Debounce function
 * @param {Function} func - Function to debounce
 * @param {number} wait - Wait time in milliseconds
 * @returns {Function} Debounced function
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Show alert message
 * @param {string} message - Message to display
 * @param {string} type - Alert type: 'success', 'error', 'info', 'warning'
 */
function showAlert(message, type = 'info') {
    // Simple alert for now, can be enhanced with toast notifications
    if (type === 'error') {
        alert('Error: ' + message);
    } else {
        alert(message);
    }
}

// Export for use in modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        formatDate,
        formatDateTime,
        getRelativeTime,
        getCategoryIcon,
        getInitials,
        debounce,
        showAlert
    };
}

