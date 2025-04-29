// src/services/ItemPriceService.js
import axios from "axios";

/**
 * Service for handling API requests related to item prices
 */
class ItemPriceService {
    /**
     * Get all item prices with optional filtering
     *
     * @param {Object} options - Query parameters
     * @param {number} options.page - Page number
     * @param {number} options.per_page - Items per page
     * @param {string} options.search - Search term
     * @param {string} options.price_type - Filter by price type (purchase/sale)
     * @param {string} options.status - Filter by status (active/inactive)
     * @param {number} options.item_id - Filter by item ID
     * @param {string} options.sort_field - Field to sort by
     * @param {string} options.sort_direction - Sort direction (asc/desc)
     * @returns {Promise} API response
     */
    async getPrices(options = {}) {
        const queryParams = new URLSearchParams();

        // Add all provided options as query parameters
        Object.entries(options).forEach(([key, value]) => {
            if (value !== null && value !== undefined && value !== "") {
                queryParams.append(key, value);
            }
        });

        const queryString = queryParams.toString()
            ? `?${queryParams.toString()}`
            : "";
        return axios.get(`/api/item-prices${queryString}`);
    }

    /**
     * Get a specific item price by ID
     *
     * @param {number} id - The price ID
     * @returns {Promise} API response
     */
    async getPrice(id) {
        return axios.get(`/api/item-prices/${id}`);
    }

    /**
     * Create a new item price
     *
     * @param {Object} priceData - The price data
     * @returns {Promise} API response
     */
    async createPrice(priceData) {
        return axios.post("/api/item-prices", priceData);
    }

    /**
     * Update an existing item price
     *
     * @param {number} id - The price ID
     * @param {Object} priceData - The updated price data
     * @returns {Promise} API response
     */
    async updatePrice(id, priceData) {
        return axios.put(`/api/item-prices/${id}`, priceData);
    }

    /**
     * Delete an item price
     *
     * @param {number} id - The price ID
     * @returns {Promise} API response
     */
    async deletePrice(id) {
        return axios.delete(`/api/item-prices/${id}`);
    }

    /**
     * Update the status of an item price
     *
     * @param {number} id - The price ID
     * @param {string} status - The new status (active/inactive)
     * @returns {Promise} API response
     */
    async updatePriceStatus(id, status) {
        return axios.patch(`/api/item-prices/${id}/status`, { status });
    }

    /**
     * Get price comparison data
     *
     * @param {Object} options - Query parameters
     * @param {string} options.filter_type - Type of filter (item/category/vendor/customer)
     * @param {number} options.filter_id - ID of the entity to filter by
     * @param {string} options.price_type - Type of prices to compare (purchase/sale/all)
     * @param {string} options.price_filter - How to filter prices (all/best/active)
     * @returns {Promise} API response
     */
    async getPriceComparison(options = {}) {
        const queryParams = new URLSearchParams();

        // Add all provided options as query parameters
        Object.entries(options).forEach(([key, value]) => {
            if (value !== null && value !== undefined && value !== "") {
                queryParams.append(key, value);
            }
        });

        const queryString = queryParams.toString()
            ? `?${queryParams.toString()}`
            : "";
        return axios.get(`/api/item-prices/comparison${queryString}`);
    }

    /**
     * Get price history for a specific item
     *
     * @param {number} itemId - ID of the item
     * @param {string} priceType - Type of price history (purchase/sale/all)
     * @returns {Promise} API response
     */
    async getPriceHistory(itemId, priceType = "all") {
        return axios.get(
            `/api/item-prices/history/${itemId}?price_type=${priceType}`
        );
    }

    /**
     * Get best prices for items
     *
     * @param {string} priceType - Type of prices (purchase/sale)
     * @param {number} limit - Maximum number of results
     * @returns {Promise} API response
     */
    async getBestPrices(priceType, limit = 10) {
        return axios.get(
            `/api/item-prices/best?price_type=${priceType}&limit=${limit}`
        );
    }
}

export default new ItemPriceService();
