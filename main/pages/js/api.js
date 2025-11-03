/**
 * API helper functions for making HTTP requests
 */

const API_BASE = 'api';

/**
 * Make API request with error handling
 * @param {string} endpoint - API endpoint
 * @param {object} options - Fetch options
 * @returns {Promise<object>} Response data
 */
async function apiRequest(endpoint, options = {}) {
    const defaultOptions = {
        credentials: 'include',
        headers: {
            'Content-Type': 'application/json',
            ...options.headers
        }
    };

    const mergedOptions = { ...defaultOptions, ...options };

    try {
        const response = await fetch(`${API_BASE}/${endpoint}`, mergedOptions);
        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.error || `HTTP error! status: ${response.status}`);
        }

        return data;
    } catch (error) {
        console.error('API request failed:', error);
        throw error;
    }
}

/**
 * GET request
 * @param {string} endpoint - API endpoint
 * @param {object} params - Query parameters
 * @returns {Promise<object>} Response data
 */
async function apiGet(endpoint, params = {}) {
    const queryString = new URLSearchParams(params).toString();
    const url = queryString ? `${endpoint}?${queryString}` : endpoint;
    return apiRequest(url, { method: 'GET' });
}

/**
 * POST request
 * @param {string} endpoint - API endpoint
 * @param {object} data - Request body
 * @returns {Promise<object>} Response data
 */
async function apiPost(endpoint, data) {
    return apiRequest(endpoint, {
        method: 'POST',
        body: JSON.stringify(data)
    });
}

/**
 * Business API methods
 */
const BusinessAPI = {
    /**
     * Get all businesses
     */
    async getAll() {
        return apiGet('data.php', { action: 'all_businesses' });
    },

    /**
     * Get single business
     * @param {number} id - Business ID
     */
    async get(id) {
        return apiGet('data.php', { action: 'get_business', id });
    },

    /**
     * Save business to favorites
     * @param {number} businessId - Business ID
     */
    async saveFavorite(businessId) {
        return apiPost('data.php?action=save_business', { business_id: businessId });
    },

    /**
     * Remove business from favorites
     * @param {number} businessId - Business ID
     */
    async removeFavorite(businessId) {
        return apiPost('data.php?action=remove_business', { business_id: businessId });
    },

    /**
     * Check if business is favorited
     * @param {number} businessId - Business ID
     */
    async checkFavorite(businessId) {
        const data = await apiGet('data.php', { action: 'check_saved', business_id: businessId });
        return data.saved || false;
    },

    /**
     * Get saved businesses
     */
    async getSaved() {
        const data = await apiGet('data.php', { action: 'get_saved_businesses' });
        return data.savedCompanies || [];
    }
};

/**
 * Review API methods
 */
const ReviewAPI = {
    /**
     * Get reviews for a business
     * @param {number} businessId - Business ID
     */
    async get(businessId) {
        const response = await fetch(`api/displayReviews.php?b_id=${businessId}`, {
            credentials: 'include'
        });
        const data = await response.json();
        return data.reviews || [];
    },

    /**
     * Add a review
     * @param {object} reviewData - Review data
     */
    async add(reviewData) {
        const response = await fetch('api/addReview.php', {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(reviewData)
        });
        return response.json();
    }
};

/**
 * Carbon Offset API methods
 */
const CarbonAPI = {
    /**
     * Get carbon offset stats
     */
    async getStats() {
        return apiGet('data.php', { action: 'get_carbon_offset_stats' });
    },

    /**
     * Get carbon offset history
     */
    async getHistory() {
        return apiGet('data.php', { action: 'get_carbon_offset_history' });
    },

    /**
     * Record carbon offset
     * @param {object} offsetData - Offset data
     */
    async record(offsetData) {
        return apiPost('data.php?action=record_carbon_offset', offsetData);
    }
};

// Export for use in modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        apiRequest,
        apiGet,
        apiPost,
        BusinessAPI,
        ReviewAPI,
        CarbonAPI
    };
}

