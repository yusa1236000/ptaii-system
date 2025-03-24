// src/components/layout/Footer.jsx
import React from 'react';

const Footer = () => {
  const currentYear = new Date().getFullYear();
  
  return (
    <footer className="bg-white border-t border-gray-200 py-4">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex items-center justify-between">
          <div className="text-sm text-gray-500">
            &copy; {currentYear} ERP System. All rights reserved.
          </div>
          <div className="text-sm text-gray-500">
            Version 1.0.0
          </div>
        </div>
      </div>
    </footer>
  );
};

export default Footer;