// src/services/fileService.js
import api from './api';

export const fileService = {
  upload: (file, type = 'general') => {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('type', type);
    
    return api.post('/files/upload', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });
  },
  
  delete: (fileId) => api.delete(`/files/${fileId}`),
  
  download: (fileId) => api.get(`/files/${fileId}/download`, {
    responseType: 'blob'
  })
};