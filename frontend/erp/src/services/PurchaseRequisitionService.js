// src/services/PurchaseRequisitionService.js
import axios from "axios";

const apiClient = axios.create({
    baseURL: process.env.VUE_APP_API_URL || "/api",
    headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
    },
});

// Add auth token to requests
apiClient.interceptors.request.use((config) => {
    const token = localStorage.getItem("token");
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

export default {
    // Get all purchase requisitions with optional filters
    getAllPurchaseRequisitions(params = {}) {
        return apiClient.get("/purchase-requisitions", { params });
    },

    // Get a specific purchase requisition by ID
    getPurchaseRequisitionById(id) {
        return apiClient.get(`/purchase-requisitions/${id}`);
    },

    // Create a new purchase requisition
    createPurchaseRequisition(data) {
        return apiClient.post("/purchase-requisitions", data);
    },

    // Update an existing purchase requisition
    updatePurchaseRequisition(id, data) {
        return apiClient.put(`/purchase-requisitions/${id}`, data);
    },

    // Delete a purchase requisition
    deletePurchaseRequisition(id) {
        return apiClient.delete(`/purchase-requisitions/${id}`);
    },

    // Update purchase requisition status
    updatePurchaseRequisitionStatus(id, status) {
        return apiClient.patch(`/purchase-requisitions/${id}/status`, {
            status,
        });
    },

    // Get items for selection in forms
    getItems() {
        return apiClient.get("/items");
    },

    // Get units of measure for selection in forms
    getUnitOfMeasures() {
        return apiClient.get("/unit-of-measures");
    },

    // Get users for requester selection
    getUsers() {
        return apiClient.get("/user");
    },

    // Convert PR to RFQ
    convertToRFQ(prId, data) {
        return apiClient.post(`/request-for-quotations/from-pr/${prId}`, data);
    },
};
