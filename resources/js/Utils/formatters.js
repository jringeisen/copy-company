/**
 * Utility functions for formatting data across the application.
 * Import individual functions as needed to keep bundle size small.
 */

/**
 * Format a number with locale-specific separators.
 * @param {number} num - The number to format
 * @returns {string} Formatted number string
 */
export const formatNumber = (num) => {
    return new Intl.NumberFormat().format(num);
};

/**
 * Format a number as USD currency.
 * @param {number} num - The amount to format
 * @returns {string} Formatted currency string
 */
export const formatCurrency = (num) => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(num);
};

/**
 * Format a 24-hour time string to 12-hour format with AM/PM.
 * @param {string} time - Time in HH:MM format
 * @returns {string} Formatted time string (e.g., "2:30 PM")
 */
export const formatTime = (time) => {
    if (!time) return '';
    const [hours, minutes] = time.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    return `${displayHour}:${minutes} ${ampm}`;
};

/**
 * Format a limit value, returning "Unlimited" for null/undefined.
 * @param {number|null} limit - The limit value
 * @returns {string|number} The limit or "Unlimited"
 */
export const formatLimit = (limit) => {
    return limit === null || limit === undefined ? 'Unlimited' : limit;
};

/**
 * Format file size in bytes to human-readable format.
 * @param {number} bytes - File size in bytes
 * @returns {string} Human-readable file size
 */
export const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

/**
 * Format a date string to locale-specific format.
 * @param {string|Date} dateString - The date to format
 * @param {Object} options - Intl.DateTimeFormat options
 * @returns {string} Formatted date string
 */
export const formatDate = (dateString, options = {}) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        ...options,
    }).format(date);
};

/**
 * Format a date string to relative time (e.g., "2 hours ago").
 * @param {string|Date} dateString - The date to format
 * @returns {string} Relative time string
 */
export const formatRelativeTime = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    const now = new Date();
    const diffInSeconds = Math.floor((now - date) / 1000);

    if (diffInSeconds < 60) return 'just now';
    if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)} minutes ago`;
    if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)} hours ago`;
    if (diffInSeconds < 604800) return `${Math.floor(diffInSeconds / 86400)} days ago`;

    return formatDate(dateString);
};
