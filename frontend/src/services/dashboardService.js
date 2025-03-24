// src/services/dashboardService.js
import api from './api';

export const dashboardService = {
  getInventorySummary: () => api.get('/dashboard/inventory-summary'),
  getLowStockItems: (limit = 5) => api.get('/dashboard/low-stock-items', { params: { limit } }),
  getRecentTransactions: (limit = 5) => api.get('/dashboard/recent-transactions', { params: { limit } }),
  getStockMovementTrend: (period = 'month', limit = 6) => api.get('/dashboard/stock-movement-trend', { 
    params: { period, limit } 
  }),
  getTopMovingItems: (type = 'in', limit = 5) => api.get('/dashboard/top-moving-items', { 
    params: { type, limit } 
  })
};