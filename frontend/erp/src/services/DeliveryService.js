// src/services/DeliveryService.js
import api from './api';

/**
 * Service for delivery management operations
 */
const DeliveryService = {
  /**
   * Get all deliveries with optional parameters
   * @param {Object} params - Query parameters (pagination, filters, etc.)
   * @returns {Promise} Promise with deliveries response
   */
  getDeliveries: async (params = {}) => {
    try {
      const response = await api.get('/deliveries', { params });
      return response.data;
    } catch (error) {
      console.error('Error fetching deliveries:', error);
      throw error;
    }
  },
  
  /**
   * Get delivery by ID
   * @param {Number} id - Delivery ID
   * @returns {Promise} Promise with delivery response
   */
  getDeliveryById: async (id) => {
    try {
      const response = await api.get(`/deliveries/${id}`);
      return response.data;
    } catch (error) {
      console.error(`Error fetching delivery ${id}:`, error);
      throw error;
    }
  },
  
  /**
   * Create a new delivery
   * @param {Object} deliveryData - Delivery data
   * @returns {Promise} Promise with created delivery response
   */
  createDelivery: async (deliveryData) => {
    try {
      const response = await api.post('/deliveries', deliveryData);
      return response.data;
    } catch (error) {
      console.error('Error creating delivery:', error);
      throw error;
    }
  },
  
  /**
   * Update an existing delivery
   * @param {Number} id - Delivery ID
   * @param {Object} deliveryData - Delivery data to update
   * @returns {Promise} Promise with updated delivery response
   */
  updateDelivery: async (id, deliveryData) => {
    try {
      const response = await api.put(`/deliveries/${id}`, deliveryData);
      return response.data;
    } catch (error) {
      console.error(`Error updating delivery ${id}:`, error);
      throw error;
    }
  },
  
  /**
   * Delete a delivery
   * @param {Number} id - Delivery ID
   * @returns {Promise} Promise with delete response
   */
  deleteDelivery: async (id) => {
    try {
      const response = await api.delete(`/deliveries/${id}`);
      return response.data;
    } catch (error) {
      console.error(`Error deleting delivery ${id}:`, error);
      throw error;
    }
  },
  
  /**
   * Complete a delivery
   * @param {Number} id - Delivery ID
   * @returns {Promise} Promise with complete delivery response
   */
  completeDelivery: async (id) => {
    try {
      const response = await api.post(`/deliveries/${id}/complete`);
      return response.data;
    } catch (error) {
      console.error(`Error completing delivery ${id}:`, error);
      throw error;
    }
  },
  
  /**
   * Get warehouses for delivery
   * @returns {Promise} Promise with warehouses response
   */
  getWarehouses: async () => {
    try {
      const response = await api.get('/warehouses');
      return response.data;
    } catch (error) {
      console.error('Error fetching warehouses:', error);
      throw error;
    }
  },
  
  /**
   * Get warehouse locations for a specific warehouse
   * @param {Number} warehouseId - Warehouse ID
   * @returns {Promise} Promise with warehouse locations response
   */
  getWarehouseLocations: async (warehouseId) => {
    try {
      const response = await api.get(`/warehouses/${warehouseId}/locations`);
      return response.data;
    } catch (error) {
      console.error(`Error fetching locations for warehouse ${warehouseId}:`, error);
      throw error;
    }
  },
  
  /**
   * Get all available sales orders for delivery
   * @returns {Promise} Promise with sales orders response
   */
  getAvailableSalesOrders: async () => {
    try {
      const response = await api.get('/orders', { params: { status: 'Confirmed' } });
      return response.data;
    } catch (error) {
      console.error('Error fetching available sales orders:', error);
      throw error;
    }
  }
};

export default DeliveryService;