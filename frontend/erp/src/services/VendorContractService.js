// src/services/VendorContractService.js
import axios from "axios";

const API_URL = "/api/vendor-contracts";

class VendorContractService {
    /**
     * Get all contracts with optional filtering
     * @param {Object} params - Query parameters for filtering, pagination, and sorting
     * @returns {Promise}
     */
    getAllContracts(params = {}) {
        return axios.get(API_URL, { params });
    }

    /**
     * Get contract by ID
     * @param {number} id - Contract ID
     * @returns {Promise}
     */
    getContractById(id) {
        return axios.get(`${API_URL}/${id}`);
    }

    /**
     * Create a new contract
     * @param {Object} contractData - Contract data
     * @returns {Promise}
     */
    createContract(contractData) {
        return axios.post(API_URL, contractData);
    }

    /**
     * Update an existing contract
     * @param {number} id - Contract ID
     * @param {Object} contractData - Contract data to update
     * @returns {Promise}
     */
    updateContract(id, contractData) {
        return axios.put(`${API_URL}/${id}`, contractData);
    }

    /**
     * Delete a contract
     * @param {number} id - Contract ID
     * @returns {Promise}
     */
    deleteContract(id) {
        return axios.delete(`${API_URL}/${id}`);
    }

    /**
     * Activate a contract
     * @param {number} id - Contract ID
     * @returns {Promise}
     */
    activateContract(id) {
        return axios.post(`${API_URL}/${id}/activate`);
    }

    /**
     * Terminate a contract
     * @param {number} id - Contract ID
     * @param {Object} data - Termination data (termination_date, termination_reason)
     * @returns {Promise}
     */
    terminateContract(id, data) {
        return axios.post(`${API_URL}/${id}/terminate`, data);
    }

    /**
     * Get contracts expiring within a specified period
     * @param {string} period - Period (30days, 60days, 90days, year)
     * @returns {Promise}
     */
    getExpiringContracts(period = "30days") {
        return axios.get(`${API_URL}/expiring`, { params: { period } });
    }

    /**
     * Get dashboard statistics
     * @returns {Promise}
     */
    getDashboardStats() {
        return axios.get(`${API_URL}/dashboard-stats`);
    }
}

export default new VendorContractService();
