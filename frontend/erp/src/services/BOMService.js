// src/services/BOMService.js
import api from "./api";

/**
 * Service for Bill of Materials (BOM) operations
 */
const BOMService = {
    /**
     * Get all BOMs with filtering and pagination options
     * @param {Object} params - Query parameters (search, filters, pagination, sorting)
     * @returns {Promise} Promise with BOMs response
     */
    getBOMs: async (params = {}) => {
        try {
            const response = await api.get("/boms", { params });
            return response.data;
        } catch (error) {
            console.error("Error fetching BOMs:", error);
            throw error;
        }
    },

    /**
     * Get a specific BOM by ID
     * @param {Number} id - BOM ID
     * @returns {Promise} Promise with BOM details
     */
    getBOMById: async (id) => {
        try {
            const response = await api.get(`/boms/${id}`);
            return response.data;
        } catch (error) {
            console.error(`Error fetching BOM ${id}:`, error);
            throw error;
        }
    },

    /**
     * Create a new BOM
     * @param {Object} bomData - BOM data including potential BOM lines
     * @returns {Promise} Promise with created BOM
     */
    createBOM: async (bomData) => {
        try {
            const response = await api.post("/boms", bomData);
            return response.data;
        } catch (error) {
            console.error("Error creating BOM:", error);
            throw error;
        }
    },

    /**
     * Update an existing BOM
     * @param {Number} id - BOM ID
     * @param {Object} bomData - BOM data to update
     * @returns {Promise} Promise with updated BOM
     */
    updateBOM: async (id, bomData) => {
        try {
            const response = await api.put(`/boms/${id}`, bomData);
            return response.data;
        } catch (error) {
            console.error(`Error updating BOM ${id}:`, error);
            throw error;
        }
    },

    /**
     * Delete a BOM
     * @param {Number} id - BOM ID
     * @returns {Promise} Promise with delete response
     */
    deleteBOM: async (id) => {
        try {
            const response = await api.delete(`/boms/${id}`);
            return response.data;
        } catch (error) {
            console.error(`Error deleting BOM ${id}:`, error);
            throw error;
        }
    },

    /**
     * Get all BOM lines for a specific BOM
     * @param {Number} bomId - BOM ID
     * @returns {Promise} Promise with BOM lines
     */
    getBOMLines: async (bomId) => {
        try {
            const response = await api.get(`/boms/${bomId}/lines`);
            return response.data;
        } catch (error) {
            console.error(`Error fetching BOM lines for BOM ${bomId}:`, error);
            throw error;
        }
    },

    /**
     * Create a new BOM line
     * @param {Number} bomId - BOM ID
     * @param {Object} lineData - BOM line data
     * @returns {Promise} Promise with created BOM line
     */
    createBOMLine: async (bomId, lineData) => {
        try {
            const response = await api.post(`/boms/${bomId}/lines`, lineData);
            return response.data;
        } catch (error) {
            console.error(`Error creating BOM line for BOM ${bomId}:`, error);
            throw error;
        }
    },

    /**
     * Update an existing BOM line
     * @param {Number} bomId - BOM ID
     * @param {Number} lineId - BOM line ID
     * @param {Object} lineData - BOM line data to update
     * @returns {Promise} Promise with updated BOM line
     */
    updateBOMLine: async (bomId, lineId, lineData) => {
        try {
            const response = await api.put(
                `/boms/${bomId}/lines/${lineId}`,
                lineData
            );
            return response.data;
        } catch (error) {
            console.error(
                `Error updating BOM line ${lineId} for BOM ${bomId}:`,
                error
            );
            throw error;
        }
    },

    /**
     * Delete a BOM line
     * @param {Number} bomId - BOM ID
     * @param {Number} lineId - BOM line ID
     * @returns {Promise} Promise with delete response
     */
    deleteBOMLine: async (bomId, lineId) => {
        try {
            const response = await api.delete(`/boms/${bomId}/lines/${lineId}`);
            return response.data;
        } catch (error) {
            console.error(
                `Error deleting BOM line ${lineId} for BOM ${bomId}:`,
                error
            );
            throw error;
        }
    },
};

export default BOMService;
