// src/services/inventoryService.js
import api from './api';

// Item Service
export const itemService = {
  getAll: (params = {}) => api.get('/items', { params }),
  getById: (id) => api.get(`/items/${id}`),
  create: (data) => api.post('/items', data),
  update: (id, data) => api.put(`/items/${id}`, data),
  delete: (id) => api.delete(`/items/${id}`),
  getStockStatus: () => api.get('/items/stock-status'),
  getBatches: (itemId) => api.get(`/items/${itemId}/batches`),
  getRecentTransactions: (params = {}) => api.get('/stock-transactions', { 
    params: { 
      ...params,
      orderBy: 'transaction_date',
      orderDirection: 'desc'
    } 
  })
};

// Category Service
export const categoryService = {
  getAll: (params = {}) => api.get('/item-categories', { params }),
  getById: (id) => api.get(`/item-categories/${id}`),
  create: (data) => api.post('/item-categories', data),
  update: (id, data) => api.put(`/item-categories/${id}`, data),
  delete: (id) => api.delete(`/item-categories/${id}`)
};

// Unit of Measure Service
export const unitOfMeasureService = {
  getAll: (params = {}) => api.get('/unit-of-measures', { params }),
  getById: (id) => api.get(`/unit-of-measures/${id}`),
  create: (data) => api.post('/unit-of-measures', data),
  update: (id, data) => api.put(`/unit-of-measures/${id}`, data),
  delete: (id) => api.delete(`/unit-of-measures/${id}`)
};

// Warehouse Service
export const warehouseService = {
  getAll: (params = {}) => api.get('/warehouses', { params }),
  getById: (id) => api.get(`/warehouses/${id}`),
  create: (data) => api.post('/warehouses', data),
  update: (id, data) => api.put(`/warehouses/${id}`, data),
  delete: (id) => api.delete(`/warehouses/${id}`),
  getZones: (warehouseId) => api.get(`/warehouses/${warehouseId}/zones`),
  getLocations: (warehouseId, zoneId) => api.get(`/warehouses/${warehouseId}/zones/${zoneId}/locations`)
};

// Stock Transaction Service
export const stockTransactionService = {
  getAll: (params = {}) => api.get('/stock-transactions', { params }),
  getById: (id) => api.get(`/stock-transactions/${id}`),
  create: (data) => api.post('/stock-transactions', data),
  getItemTransactions: (itemId, params = {}) => api.get(`/stock-transactions/item/${itemId}`, { params }),
  getWarehouseTransactions: (warehouseId, params = {}) => api.get(`/stock-transactions/warehouse/${warehouseId}`, { params })
};

// Stock Adjustment Service
export const stockAdjustmentService = {
  getAll: (params = {}) => api.get('/stock-adjustments', { params }),
  getById: (id) => api.get(`/stock-adjustments/${id}`),
  create: (data) => api.post('/stock-adjustments', data),
  approve: (id) => api.patch(`/stock-adjustments/${id}/approve`),
  cancel: (id) => api.patch(`/stock-adjustments/${id}/cancel`)
};

// Inventory Reports Service
export const inventoryReportService = {
  getStockReport: (params = {}) => api.get('/reports/stock', { params }),
  getMovementReport: (params = {}) => api.get('/reports/movement', { params }),
  getAdjustmentReport: (params = {}) => api.get('/reports/adjustment', { params }),
  getValuationReport: (params = {}) => api.get('/reports/valuation', { params }),
  exportStockReport: (params = {}) => api.get('/reports/stock/export', { 
    params,
    responseType: 'blob'
  }),
  exportMovementReport: (params = {}) => api.get('/reports/movement/export', { 
    params,
    responseType: 'blob'
  }),
  exportAdjustmentReport: (params = {}) => api.get('/reports/adjustment/export', { 
    params,
    responseType: 'blob'
  }),
  exportValuationReport: (params = {}) => api.get('/reports/valuation/export', { 
    params,
    responseType: 'blob'
  })
};

// Inventory Settings Service
export const inventorySettingService = {
  getSettings: () => api.get('/inventory/settings'),
  updateSettings: (data) => api.put('/inventory/settings', data)
};