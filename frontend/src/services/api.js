// src/services/api.js
import axios from 'axios';
import { toast } from 'react-toastify';

const API_URL = process.env.REACT_APP_API_URL || 'http://localhost:8000/api';

// Create axios instance
const api = axios.create({
  baseURL: API_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
});

// Add request interceptor for authentication
api.interceptors.request.use(
  (config) => {
    // Get token from localStorage
    const token = localStorage.getItem('token');
    
    // If token exists, add to headers
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Add response interceptor for error handling
api.interceptors.response.use(
  (response) => {
    return response;
  },
  (error) => {
    // Handle different error responses
    const { response } = error;
    
    if (response) {
      // Handle 401 Unauthorized - redirect to login
      if (response.status === 401) {
        localStorage.removeItem('token');
        window.location.href = '/login';
        toast.error('Your session has expired. Please log in again.');
      }
      
      // Handle 403 Forbidden
      else if (response.status === 403) {
        toast.error('You do not have permission to perform this action.');
      }
      
      // Handle 404 Not Found
      else if (response.status === 404) {
        // Don't show toast for 404s, let the components handle them
      }
      
      // Handle 422 Validation Errors
      else if (response.status === 422) {
        // Don't show toast for validation errors, let the forms handle them
      }
      
      // Handle 500 Server Errors
      else if (response.status >= 500) {
        toast.error('A server error occurred. Please try again later.');
      }
      
      // Handle other errors
      else {
        const errorMessage = response.data?.message || 'An error occurred.';
        toast.error(errorMessage);
      }
    } else {
      // Handle network errors
      toast.error('Network error. Please check your connection and try again.');
    }
    
    return Promise.reject(error);
  }
);

export default api;

