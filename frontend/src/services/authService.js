// src/services/authService.js
import api from './api';

export const authService = {
  // User Authentication
  login: (credentials) => api.post('/auth/login', credentials),
  logout: () => api.post('/auth/logout'),
  register: (userData) => api.post('/auth/register', userData),
  forgotPassword: (email) => api.post('/auth/forgot-password', { email }),
  resetPassword: (data) => api.post('/auth/reset-password', data),

  // User Profile
  getProfile: () => api.get('/user'),
  updateProfile: (data) => api.put('/user/profile', data),
  changePassword: (data) => api.put('/user/password', data),
  
  // Token Validation
  validateToken: () => api.get('/auth/validate-token'),
  
  // User Management (Admin)
  getUsers: (params = {}) => api.get('/users', { params }),
  getUserById: (id) => api.get(`/users/${id}`),
  createUser: (data) => api.post('/users', data),
  updateUser: (id, data) => api.put(`/users/${id}`, data),
  deleteUser: (id) => api.delete(`/users/${id}`),
  
  // Role Management
  getRoles: () => api.get('/roles'),
  getUserRoles: (userId) => api.get(`/users/${userId}/roles`),
  assignRole: (userId, roleIds) => api.post(`/users/${userId}/roles`, { role_ids: roleIds }),
  
  // Permission Management
  getPermissions: () => api.get('/permissions'),
  getRolePermissions: (roleId) => api.get(`/roles/${roleId}/permissions`),
  assignPermissions: (roleId, permissionIds) => api.post(`/roles/${roleId}/permissions`, { permission_ids: permissionIds }),
  
  // Helper Methods
  storeAuthToken: (token) => {
    localStorage.setItem('auth_token', token);
    api.defaults.headers.Authorization = `Bearer ${token}`;
  },
  
  getAuthToken: () => localStorage.getItem('auth_token'),
  
  clearAuthToken: () => {
    localStorage.removeItem('auth_token');
    delete api.defaults.headers.Authorization;
  },
  
  hasPermission: (requiredPermission) => {
    const user = JSON.parse(localStorage.getItem('user') || '{}');
    const permissions = user.permissions || [];
    return permissions.includes(requiredPermission);
  },
  
  hasRole: (requiredRole) => {
    const user = JSON.parse(localStorage.getItem('user') || '{}');
    const roles = user.roles || [];
    return roles.some(role => role === requiredRole || role.name === requiredRole);
  },
  
  storeUserData: (userData) => {
    localStorage.setItem('user', JSON.stringify(userData));
  },
  
  getUserData: () => {
    return JSON.parse(localStorage.getItem('user') || '{}');
  },
  
  clearUserData: () => {
    localStorage.removeItem('user');
  },
  
  // Clear all auth data (token and user data)
  clearAuth: () => {
    authService.clearAuthToken();
    authService.clearUserData();
  }
};

export default authService;