// src/services/VendorInvoiceService.js
import axios from "axios";

class VendorInvoiceService {
    /**
     * Get all vendor invoices with optional filters
     * @param {Object} params - Query parameters
     * @returns {Promise}
     */
    async getAllVendorInvoices(params = {}) {
        return axios.get("/api/vendor-invoices", { params });
    }

    /**
     * Get a specific vendor invoice by ID
     * @param {number|string} id - Vendor invoice ID
     * @returns {Promise}
     */
    async getVendorInvoiceById(id) {
        return axios.get(`/api/vendor-invoices/${id}`);
    }

    /**
     * Create a new vendor invoice
     * @param {Object} invoiceData - The invoice data
     * @returns {Promise}
     */
    async createVendorInvoice(invoiceData) {
        return axios.post("/api/vendor-invoices", invoiceData);
    }

    /**
     * Update an existing vendor invoice
     * @param {number|string} id - Vendor invoice ID
     * @param {Object} invoiceData - The updated invoice data
     * @returns {Promise}
     */
    async updateVendorInvoice(id, invoiceData) {
        return axios.put(`/api/vendor-invoices/${id}`, invoiceData);
    }

    /**
     * Delete a vendor invoice
     * @param {number|string} id - Vendor invoice ID
     * @returns {Promise}
     */
    async deleteVendorInvoice(id) {
        return axios.delete(`/api/vendor-invoices/${id}`);
    }

    /**
     * Approve a vendor invoice
     * @param {number|string} id - Vendor invoice ID
     * @param {Object} approvalData - Approval data (comments, etc.)
     * @returns {Promise}
     */
    async approveVendorInvoice(id, approvalData = {}) {
        return axios.post(`/api/vendor-invoices/${id}/approve`, approvalData);
    }

    /**
     * Reject a vendor invoice
     * @param {number|string} id - Vendor invoice ID
     * @param {Object} rejectionData - Rejection data (reason, comments, etc.)
     * @returns {Promise}
     */
    async rejectVendorInvoice(id, rejectionData = {}) {
        return axios.post(`/api/vendor-invoices/${id}/reject`, rejectionData);
    }

    /**
     * Record a payment for a vendor invoice
     * @param {number|string} id - Vendor invoice ID
     * @param {Object} paymentData - Payment data
     * @returns {Promise}
     */
    async recordPayment(id, paymentData) {
        return axios.post(`/api/vendor-invoices/${id}/pay`, paymentData);
    }
}

export default new VendorInvoiceService();
