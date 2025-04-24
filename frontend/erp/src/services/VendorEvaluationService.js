// src/services/VendorEvaluationService.js
import axios from "axios";

const API_URL = process.env.VUE_APP_API_URL || "/api";

class VendorEvaluationService {
    /**
     * Get all vendor evaluations with optional filters
     * @param {Object} params - Optional query parameters
     * @returns {Promise} - Axios response
     */
    getAllEvaluations(params = {}) {
        return axios.get(`${API_URL}/vendor-evaluations`, { params });
    }

    /**
     * Get a specific vendor evaluation by ID
     * @param {Number|String} id - Evaluation ID
     * @returns {Promise} - Axios response
     */
    getEvaluationById(id) {
        return axios.get(`${API_URL}/vendor-evaluations/${id}`);
    }

    /**
     * Create a new vendor evaluation
     * @param {Object} evaluationData - Evaluation data
     * @returns {Promise} - Axios response
     */
    createEvaluation(evaluationData) {
        return axios.post(`${API_URL}/vendor-evaluations`, evaluationData);
    }

    /**
     * Update an existing vendor evaluation
     * @param {Number|String} id - Evaluation ID
     * @param {Object} evaluationData - Updated evaluation data
     * @returns {Promise} - Axios response
     */
    updateEvaluation(id, evaluationData) {
        return axios.put(`${API_URL}/vendor-evaluations/${id}`, evaluationData);
    }

    /**
     * Delete a vendor evaluation
     * @param {Number|String} id - Evaluation ID
     * @returns {Promise} - Axios response
     */
    deleteEvaluation(id) {
        return axios.delete(`${API_URL}/vendor-evaluations/${id}`);
    }

    /**
     * Get performance metrics for a specific vendor
     * @param {Object} params - Parameters including vendor_id and period
     * @returns {Promise} - Axios response
     */
    getVendorPerformance(params) {
        return axios.get(`${API_URL}/vendor-performance`, { params });
    }

    /**
     * Get purchase orders for a specific vendor
     * @param {Number|String} vendorId - Vendor ID
     * @returns {Promise} - Axios response
     */
    getVendorPurchaseOrders(vendorId) {
        return axios.get(`${API_URL}/vendors/${vendorId}/purchase-orders`);
    }

    /**
     * Get dashboard data for vendor evaluations
     * This is a placeholder method that would connect to a real endpoint in a production app
     * @param {Object} params - Parameters like period
     * @returns {Promise} - Axios response with mock data for demonstration
     */
    getDashboardData() {
        // In a real application, you would call an actual API endpoint
        // For demonstration, we're returning mock data

        // This would be replaced with a real API call in production:
        // return axios.get(`${API_URL}/vendor-evaluations/dashboard`);

        // Mock data for demonstration:
        return new Promise((resolve) => {
            setTimeout(() => {
                resolve({
                    data: {
                        status: "success",
                        data: {
                            metrics: {
                                totalVendors: 32,
                                totalEvaluations: 124,
                                averageScore: 3.7,
                                topPerformersCount: 8,
                                categoryAverages: {
                                    quality: 3.8,
                                    delivery: 3.4,
                                    price: 3.6,
                                    service: 4.0,
                                },
                                scoreDistribution: {
                                    excellent: 8,
                                    good: 14,
                                    average: 6,
                                    belowAverage: 3,
                                    poor: 1,
                                },
                            },
                            topVendors: [
                                {
                                    vendor_id: 1,
                                    name: "ABC Supplies Co.",
                                    scores: {
                                        quality: 4.8,
                                        delivery: 4.7,
                                        price: 4.5,
                                        service: 4.9,
                                        overall: 4.7,
                                    },
                                },
                                {
                                    vendor_id: 5,
                                    name: "XYZ Manufacturing",
                                    scores: {
                                        quality: 4.6,
                                        delivery: 4.5,
                                        price: 4.3,
                                        service: 4.7,
                                        overall: 4.5,
                                    },
                                },
                                {
                                    vendor_id: 12,
                                    name: "Global Tech Parts",
                                    scores: {
                                        quality: 4.4,
                                        delivery: 4.2,
                                        price: 4.5,
                                        service: 4.4,
                                        overall: 4.4,
                                    },
                                },
                                {
                                    vendor_id: 8,
                                    name: "Superior Materials Inc.",
                                    scores: {
                                        quality: 4.3,
                                        delivery: 4.1,
                                        price: 4.4,
                                        service: 4.2,
                                        overall: 4.2,
                                    },
                                },
                                {
                                    vendor_id: 19,
                                    name: "Quality Components Ltd.",
                                    scores: {
                                        quality: 4.2,
                                        delivery: 4.0,
                                        price: 4.1,
                                        service: 4.3,
                                        overall: 4.1,
                                    },
                                },
                            ],
                            recentEvaluations: [
                                {
                                    evaluation_id: 78,
                                    vendor: {
                                        vendor_id: 12,
                                        name: "Global Tech Parts",
                                    },
                                    evaluation_date: "2025-04-15",
                                    total_score: 4.4,
                                },
                                {
                                    evaluation_id: 77,
                                    vendor: {
                                        vendor_id: 7,
                                        name: "Premier Logistics",
                                    },
                                    evaluation_date: "2025-04-12",
                                    total_score: 3.7,
                                },
                                {
                                    evaluation_id: 76,
                                    vendor: {
                                        vendor_id: 5,
                                        name: "XYZ Manufacturing",
                                    },
                                    evaluation_date: "2025-04-08",
                                    total_score: 4.5,
                                },
                                {
                                    evaluation_id: 75,
                                    vendor: {
                                        vendor_id: 21,
                                        name: "East Coast Distributors",
                                    },
                                    evaluation_date: "2025-04-05",
                                    total_score: 3.2,
                                },
                                {
                                    evaluation_id: 74,
                                    vendor: {
                                        vendor_id: 1,
                                        name: "ABC Supplies Co.",
                                    },
                                    evaluation_date: "2025-04-01",
                                    total_score: 4.7,
                                },
                            ],
                            trendData: [
                                {
                                    period: "Jan 2025",
                                    quality: 3.6,
                                    delivery: 3.2,
                                    price: 3.4,
                                    service: 3.7,
                                    overall: 3.5,
                                },
                                {
                                    period: "Feb 2025",
                                    quality: 3.7,
                                    delivery: 3.3,
                                    price: 3.5,
                                    service: 3.8,
                                    overall: 3.6,
                                },
                                {
                                    period: "Mar 2025",
                                    quality: 3.8,
                                    delivery: 3.4,
                                    price: 3.6,
                                    service: 3.9,
                                    overall: 3.7,
                                },
                                {
                                    period: "Apr 2025",
                                    quality: 4.0,
                                    delivery: 3.6,
                                    price: 3.7,
                                    service: 4.1,
                                    overall: 3.9,
                                },
                            ],
                        },
                    },
                });
            }, 1000); // Simulate a 1-second API delay
        });
    }
}

export default new VendorEvaluationService();
