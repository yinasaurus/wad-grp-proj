/**
 * API helper functions for making HTTP requests
 */

const API_BASE = 'api';

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

async function apiGet(endpoint, params = {}) {
    const queryString = new URLSearchParams(params).toString();
    const url = queryString ? `${endpoint}?${queryString}` : endpoint;
    return apiRequest(url, { method: 'GET' });
}

async function apiPost(endpoint, data) {
    return apiRequest(endpoint, {
        method: 'POST',
        body: JSON.stringify(data)
    });
}

const BusinessAPI = {

    async getAll() {
        return apiGet('data.php', { action: 'all_businesses' });
    },

    async get(id) {
        return apiGet('data.php', { action: 'get_business', id });
    },


    async saveFavorite(businessId) {
        return apiPost('data.php?action=save_business', { business_id: businessId });
    },

    async removeFavorite(businessId) {
        return apiPost('data.php?action=remove_business', { business_id: businessId });
    },

    async checkFavorite(businessId) {
        const data = await apiGet('data.php', { action: 'check_saved', business_id: businessId });
        return data.saved || false;
    },

    async getSaved() {
        const data = await apiGet('data.php', { action: 'get_saved_businesses' });
        return data.savedCompanies || [];
    }
};

const ReviewAPI = {

    async get(businessId) {
        const response = await fetch(`api/displayReviews.php?b_id=${businessId}`, {
            credentials: 'include'
        });
        const data = await response.json();
        return data.reviews || [];
    },

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

const CarbonAPI = {
    async getStats() {
        return apiGet('data.php', { action: 'get_carbon_offset_stats' });
    },

    async getHistory() {
        return apiGet('data.php', { action: 'get_carbon_offset_history' });
    },


    async record(offsetData) {
        return apiPost('data.php?action=record_carbon_offset', offsetData);
    }
};

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

