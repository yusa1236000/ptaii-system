// src/services/item.service.js
import api from './api';

/**
 * Service for item management operations
 */
const ItemService = {
  /**
   * Get all items
   * @param {Object} params - Query parameters
   * @returns {Promise} Promise with items response
   */
  getItems: async (params = {}) => {
    const response = await api.get('/items', { params });
    return response.data;
  },
  
  /**
   * Get item by ID
   * @param {Number} id - Item ID
   * @returns {Promise} Promise with item response
   */
  getItemById: async (id) => {
    const response = await api.get(`/items/${id}`);
    return response.data;
  },
  
  /**
   * Create a new item
   * @param {Object} itemData - Item data
   * @returns {Promise} Promise with create item response
   */
  createItem: async (itemData) => {
    const response = await api.post('/items', itemData);
    return response.data;
  },
  
  /**
   * Update item
   * @param {Number} id - Item ID
   * @param {Object} itemData - Item data to update
   * @returns {Promise} Promise with update item response
   */
  updateItem: async (id, itemData) => {
    const response = await api.put(`/items/${id}`, itemData);
    return response.data;
  },
  
  /**
   * Delete item
   * @param {Number} id - Item ID
   * @returns {Promise} Promise with delete item response
   */
  deleteItem: async (id) => {
    const response = await api.delete(`/items/${id}`);
    return response.data;
  },
  
  /**
   * Get stock status for all items
   * @returns {Promise} Promise with stock status response
   */
  getStockStatus: async () => {
    const response = await api.get('/items/stock-status');
    return response.data;
  },
  
  /**
   * Get item categories
   * @returns {Promise} Promise with categories response
   */
  getCategories: async () => {
    const response = await api.get('/item-categories');
    return response.data;
  },
  
  /**
   * Get units of measure
   * @returns {Promise} Promise with UOM response
   */
  getUnitsOfMeasure: async () => {
    const response = await api.get('/unit-of-measures');
    return response.data;
  },
  
  /**
   * Get transactions for a specific item
   * @param {Number} itemId - Item ID
   * @param {Object} params - Query parameters
   * @returns {Promise} Promise with transactions response
   */
  getItemTransactions: async (itemId, params = {}) => {
    const response = await api.get(`/stock-transactions/item/${itemId}`, { params });
    return response.data;
  }
};

export default ItemService;
