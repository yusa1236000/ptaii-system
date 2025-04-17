// src/services/PurchaseOrderService.js
import axios from "axios";

const API_URL = "/api";

export default {
    /**
     * Get all purchase orders with optional filters
     * @param {Object} params - Filter parameters
     */
    getAllPurchaseOrders(params = {}) {
        return axios.get(`${API_URL}/purchase-orders`, { params });
    },

    /**
     * Get a purchase order by ID
     * @param {Number|String} id - Purchase order ID
     */
    getPurchaseOrderById(id) {
        return axios.get(`${API_URL}/purchase-orders/${id}`);
    },

    /**
     * Create a new purchase order
     * @param {Object} purchaseOrderData - Purchase order data
     */
    createPurchaseOrder(purchaseOrderData) {
        return axios.post(`${API_URL}/purchase-orders`, purchaseOrderData);
    },

    /**
     * Update an existing purchase order
     * @param {Number|String} id - Purchase order ID
     * @param {Object} purchaseOrderData - Updated purchase order data
     */
    updatePurchaseOrder(id, purchaseOrderData) {
        return axios.put(`${API_URL}/purchase-orders/${id}`, purchaseOrderData);
    },

    /**
     * Delete a purchase order
     * @param {Number|String} id - Purchase order ID
     */
    deletePurchaseOrder(id) {
        return axios.delete(`${API_URL}/purchase-orders/${id}`);
    },

    /**
     * Update purchase order status
     * @param {Number|String} id - Purchase order ID
     * @param {String} status - New status
     */
    updatePurchaseOrderStatus(id, status) {
        return axios.patch(`${API_URL}/purchase-orders/${id}/status`, {
            status,
        });
    },

    /**
     * Create a purchase order from a vendor quotation
     * @param {Number|String} quotationId - Vendor quotation ID
     */
    createFromQuotation(quotationId) {
        return axios.post(`${API_URL}/purchase-orders/create-from-quotation`, {
            quotation_id: quotationId,
        });
    },

    /**
     * Get all vendors for dropdown selection
     */
    getVendors() {
        return axios.get(`${API_URL}/vendors`);
    },

    /**
     * Get all items for dropdown selection
     */
    getItems() {
        return axios.get(`${API_URL}/items`);
    },

    /**
     * Get all units of measure for dropdown selection
     */
    getUnitsOfMeasure() {
        return axios.get(`${API_URL}/unit-of-measures`);
    },

    /**
     * Get vendor quotation details
     * @param {Number|String} id - Quotation ID
     */
    getVendorQuotation(id) {
        return axios.get(`${API_URL}/vendor-quotations/${id}`);
    },
};
