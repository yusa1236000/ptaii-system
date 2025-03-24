// src/components/layout/Sidebar.jsx
import React from 'react';
import { Link } from 'react-router-dom';

const Sidebar = () => {
  return (
    <div className="sidebar bg-gray-800 text-white w-64 min-h-screen p-4">
      <div className="logo mb-8">
        <h1 className="text-xl font-bold">ERP System</h1>
      </div>
      
      <div className="menu">
        <h2 className="text-lg font-semibold mb-2">Inventory Management</h2>
        <ul>
          <li className="mb-2">
            <Link to="/dashboard" className="hover:text-blue-300">Dashboard</Link>
          </li>
          <li className="mb-2">
            <Link to="/items" className="hover:text-blue-300">Items</Link>
          </li>
          <li className="mb-2">
            <Link to="/item-categories" className="hover:text-blue-300">Categories</Link>
          </li>
          <li className="mb-2">
            <Link to="/uom" className="hover:text-blue-300">Units of Measure</Link>
          </li>
          <li className="mb-2">
            <Link to="/warehouses" className="hover:text-blue-300">Warehouses</Link>
          </li>
          <li className="mb-2">
            <Link to="/stock-transactions" className="hover:text-blue-300">Stock Transactions</Link>
          </li>
          <li className="mb-2">
            <Link to="/stock-adjustments" className="hover:text-blue-300">Stock Adjustments</Link>
          </li>
        </ul>
        
        <h2 className="text-lg font-semibold mb-2 mt-6">Other Modules</h2>
        <ul>
          <li className="mb-2">
            <Link to="/purchasing" className="hover:text-blue-300">Purchasing</Link>
          </li>
          <li className="mb-2">
            <Link to="/sales" className="hover:text-blue-300">Sales</Link>
          </li>
          <li className="mb-2">
            <Link to="/manufacturing" className="hover:text-blue-300">Manufacturing</Link>
          </li>
          <li className="mb-2">
            <Link to="/accounting" className="hover:text-blue-300">Accounting</Link>
          </li>
        </ul>
      </div>
    </div>
  );
};

export default Sidebar;