// src/services/RFQService.js
import axios from "axios";

const RFQService = {
    /**
     * Get all request for quotations with optional filtering
     * @param {Object} params - Query parameters for filtering and pagination
     * @returns {Promise} - API response
     */
    getAllRFQs(params = {}) {
        return axios.get("/api/request-for-quotations", { params });
    },

    /**
     * Get a single request for quotation by ID
     * @param {Number|String} id - The RFQ ID
     * @returns {Promise} - API response
     */
    getRFQById(id) {
        return axios.get(`/api/request-for-quotations/${id}`);
    },

    /**
     * Create a new request for quotation
     * @param {Object} rfqData - The RFQ data
     * @returns {Promise} - API response
     */
    createRFQ(rfqData) {
        return axios.post("/api/request-for-quotations", rfqData);
    },

    /**
     * Update an existing request for quotation
     * @param {Number|String} id - The RFQ ID
     * @param {Object} rfqData - The updated RFQ data
     * @returns {Promise} - API response
     */
    updateRFQ(id, rfqData) {
        return axios.put(`/api/request-for-quotations/${id}`, rfqData);
    },

    /**
     * Delete a request for quotation
     * @param {Number|String} id - The RFQ ID to delete
     * @returns {Promise} - API response
     */
    deleteRFQ(id) {
        return axios.delete(`/api/request-for-quotations/${id}`);
    },

    /**
     * Update the status of a request for quotation
     * @param {Number|String} id - The RFQ ID
     * @param {String} status - The new status ('draft', 'sent', 'closed', 'canceled')
     * @returns {Promise} - API response
     */
    updateRFQStatus(id, status) {
        return axios.patch(`/api/request-for-quotations/${id}/status`, {
            status,
        });
    },

    /**
     * Get vendor quotations for an RFQ
     * @param {Number|String} rfqId - The RFQ ID
     * @returns {Promise} - API response
     */
    getVendorQuotations(rfqId) {
        return axios.get(
            `/api/request-for-quotations/${rfqId}/vendor-quotations`
        );
    },

    /**
     * Create a new vendor quotation for an RFQ
     * @param {Object} quotationData - The quotation data
     * @returns {Promise} - API response
     */
    createVendorQuotation(quotationData) {
        return axios.post("/api/vendor-quotations", quotationData);
    },

    /**
     * Update vendor quotation status
     * @param {Number|String} quotationId - The quotation ID
     * @param {String} status - The new status ('received', 'accepted', 'rejected')
     * @returns {Promise} - API response
     */
    updateQuotationStatus(quotationId, status) {
        return axios.patch(`/api/vendor-quotations/${quotationId}/status`, {
            status,
        });
    },
};

export default RFQService;
