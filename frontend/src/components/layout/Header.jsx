// src/components/layout/Header.jsx
import React from 'react';
import { useLocation } from 'react-router-dom';
import { Menu, Bell, Search } from 'lucide-react';

const Header = ({ sidebarOpen, setSidebarOpen }) => {
  const location = useLocation();
  
  // Function to format the page title based on the URL
  const getPageTitle = () => {
    const path = location.pathname;
    const parts = path.split('/').filter(part => part !== '');
    
    if (parts.length < 2) return 'Dashboard';
    
    if (parts[1] === 'dashboard') return 'Inventory Dashboard';
    
    if (parts[1] === 'items') {
      if (parts.length === 2) return 'Items';
      if (parts[2] === 'create') return 'Create Item';
      if (parts[3] === 'edit') return 'Edit Item';
      return 'Item Details';
    }
    
    if (parts[1] === 'categories') {
      if (parts.length === 2) return 'Categories';
      if (parts[2] === 'create') return 'Create Category';
      return 'Edit Category';
    }
    
    if (parts[1] === 'uom') {
      if (parts.length === 2) return 'Units of Measure';
      if (parts[2] === 'create') return 'Create Unit of Measure';
      return 'Edit Unit of Measure';
    }
    
    if (parts[1] === 'warehouses') {
      if (parts.length === 2) return 'Warehouses';
      if (parts[2] === 'create') return 'Create Warehouse';
      if (parts[3] === 'edit') return 'Edit Warehouse';
      return 'Warehouse Details';
    }
    
    if (parts[1] === 'transactions') {
      if (parts.length === 2) return 'Stock Transactions';
      return 'Create Stock Transaction';
    }
    
    if (parts[1] === 'adjustments') {
      if (parts.length === 2) return 'Stock Adjustments';
      if (parts[2] === 'create') return 'Create Stock Adjustment';
      return 'Stock Adjustment Details';
    }
    
    if (parts[1] === 'reports') {
      if (parts.length === 2) return 'Inventory Reports';
      if (parts[2] === 'stock') return 'Stock Report';
      if (parts[2] === 'movement') return 'Movement Report';
      if (parts[2] === 'adjustment') return 'Adjustment Report';
      if (parts[2] === 'valuation') return 'Valuation Report';
    }
    
    if (parts[1] === 'settings') return 'Inventory Settings';
    
    return 'Inventory Management';
  };

  return (
    <header className="bg-white shadow-sm z-10">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between h-16">
          <div className="flex">
            <button
              className="md:hidden px-4 text-gray-500 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500"
              onClick={() => setSidebarOpen(true)}
            >
              <span className="sr-only">Open sidebar</span>
              <Menu className="h-6 w-6" aria-hidden="true" />
            </button>
            <div className="flex-shrink-0 flex items-center">
              <h1 className="text-2xl font-semibold text-gray-900">{getPageTitle()}</h1>
            </div>
          </div>
          <div className="flex items-center">
            <div className="hidden md:ml-4 md:flex-shrink-0 md:flex md:items-center">
              <div className="relative">
                <div className="flex items-center px-3 py-2 border border-gray-300 rounded-md shadow-sm">
                  <Search className="h-4 w-4 text-gray-400" />
                  <input
                    type="search"
                    className="ml-2 w-full border-0 focus:outline-none focus:ring-0 text-sm"
                    placeholder="Search..."
                  />
                </div>
              </div>
              <button
                type="button"
                className="ml-3 p-1 rounded-full text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
              >
                <span className="sr-only">View notifications</span>
                <Bell className="h-6 w-6" aria-hidden="true" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </header>
  );
};

export default Header;