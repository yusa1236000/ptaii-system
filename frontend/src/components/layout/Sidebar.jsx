// src/components/layout/Sidebar.jsx
import React from 'react';
import { Link, useLocation } from 'react-router-dom';
import { 
  Home, 
  Package, 
  Tag, 
  Ruler, 
  Warehouse, 
  RefreshCw, 
  Edit3, 
  BarChart2, 
  Settings,
  Menu,
  X
} from 'lucide-react';
import { useAuth } from '../../contexts/AuthContext';

const Sidebar = ({ sidebarOpen, setSidebarOpen }) => {
  const { pathname } = useLocation();
  const { currentUser, logout } = useAuth();

  const navigation = [
    { name: 'Dashboard', href: '/inventory/dashboard', icon: Home },
    { name: 'Items', href: '/inventory/items', icon: Package },
    { name: 'Categories', href: '/inventory/categories', icon: Tag },
    { name: 'Units of Measure', href: '/inventory/uom', icon: Ruler },
    { name: 'Warehouses', href: '/inventory/warehouses', icon: Warehouse },
    { name: 'Stock Transactions', href: '/inventory/transactions', icon: RefreshCw },
    { name: 'Stock Adjustments', href: '/inventory/adjustments', icon: Edit3 },
    { name: 'Reports', href: '/inventory/reports', icon: BarChart2 },
    { name: 'Settings', href: '/inventory/settings', icon: Settings },
  ];

  return (
    <>
      {/* Mobile sidebar */}
      <div className={`${sidebarOpen ? 'block' : 'hidden'} fixed inset-0 z-40 md:hidden`}>
        <div 
          className="fixed inset-0 bg-gray-600 bg-opacity-75" 
          onClick={() => setSidebarOpen(false)}
          aria-hidden="true"
        ></div>
        
        <div className="fixed inset-y-0 left-0 flex flex-col z-40 w-64 max-w-xs bg-white shadow-lg">
          <div className="flex items-center justify-between h-16 px-6 border-b border-gray-200">
            <div className="flex items-center">
              <img 
                className="h-8 w-auto" 
                src="/logo.svg" 
                alt="Logo" 
              />
              <span className="ml-2 text-xl font-bold text-gray-900">ERP System</span>
            </div>
            <button
              className="rounded-md text-gray-500 hover:text-gray-900 focus:outline-none"
              onClick={() => setSidebarOpen(false)}
            >
              <X className="h-6 w-6" />
            </button>
          </div>
          
          <div className="flex-1 overflow-y-auto pt-5 pb-4">
            <nav className="mt-5 px-2 space-y-1">
              {navigation.map((item) => {
                const isActive = pathname.startsWith(item.href);
                return (
                  <Link
                    key={item.name}
                    to={item.href}
                    className={`${
                      isActive
                        ? 'bg-indigo-50 text-indigo-700'
                        : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                    } group flex items-center px-2 py-2 text-base font-medium rounded-md`}
                    onClick={() => setSidebarOpen(false)}
                  >
                    <item.icon 
                      className={`${
                        isActive ? 'text-indigo-500' : 'text-gray-400 group-hover:text-gray-500'
                      } mr-4 flex-shrink-0 h-6 w-6`} 
                    />
                    {item.name}
                  </Link>
                );
              })}
            </nav>
          </div>
          
          <div className="flex-shrink-0 flex border-t border-gray-200 p-4">
            <div className="flex-shrink-0 group block">
              <div className="flex items-center">
                <div>
                  <div className="inline-block h-9 w-9 rounded-full bg-gray-100 text-gray-500 flex items-center justify-center">
                    {currentUser?.name?.charAt(0) || 'U'}
                  </div>
                </div>
                <div className="ml-3">
                  <p className="text-base font-medium text-gray-700 group-hover:text-gray-900">
                    {currentUser?.name || 'User'}
                  </p>
                  <button 
                    onClick={logout} 
                    className="text-sm font-medium text-gray-500 group-hover:text-gray-700"
                  >
                    Logout
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      {/* Desktop sidebar */}
      <div className="hidden md:flex md:w-64 md:flex-col md:fixed md:inset-y-0">
        <div className="flex flex-col flex-grow border-r border-gray-200 bg-white overflow-y-auto">
          <div className="flex items-center h-16 flex-shrink-0 px-4 border-b border-gray-200">
            <img 
              className="h-8 w-auto" 
              src="/logo.svg" 
              alt="Logo" 
            />
            <span className="ml-2 text-xl font-bold text-gray-900">ERP System</span>
          </div>
          
          <div className="flex-1 flex flex-col pt-5 pb-4 overflow-y-auto">
            <nav className="mt-5 flex-1 px-2 space-y-1">
              {navigation.map((item) => {
                const isActive = pathname.startsWith(item.href);
                return (
                  <Link
                    key={item.name}
                    to={item.href}
                    className={`${
                      isActive
                        ? 'bg-indigo-50 text-indigo-700'
                        : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                    } group flex items-center px-2 py-2 text-sm font-medium rounded-md`}
                  >
                    <item.icon 
                      className={`${
                        isActive ? 'text-indigo-500' : 'text-gray-400 group-hover:text-gray-500'
                      } mr-3 flex-shrink-0 h-6 w-6`} 
                    />
                    {item.name}
                  </Link>
                );
              })}
            </nav>
          </div>
          
          <div className="flex-shrink-0 flex border-t border-gray-200 p-4">
            <div className="flex-shrink-0 w-full group block">
              <div className="flex items-center">
                <div>
                  <div className="inline-block h-9 w-9 rounded-full bg-gray-100 text-gray-500 flex items-center justify-center">
                    {currentUser?.name?.charAt(0) || 'U'}
                  </div>
                </div>
                <div className="ml-3">
                  <p className="text-sm font-medium text-gray-700 group-hover:text-gray-900">
                    {currentUser?.name || 'User'}
                  </p>
                  <button 
                    onClick={logout} 
                    className="text-xs font-medium text-gray-500 group-hover:text-gray-700"
                  >
                    Logout
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </>
  );
};

export default Sidebar;
