// src/components/common/LoadingScreen.jsx
import React from 'react';

const LoadingScreen = ({ message = 'Loading...' }) => {
  return (
    <div className="fixed inset-0 bg-gray-100 flex items-center justify-center z-50">
      <div className="text-center">
        <div className="animate-spin rounded-full h-16 w-16 border-b-2 border-indigo-600 mx-auto"></div>
        <p className="mt-4 text-lg text-gray-700 font-medium">{message}</p>
      </div>
    </div>
  );
};

export default LoadingScreen;