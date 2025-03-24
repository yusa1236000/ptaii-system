// src/App.jsx
import React from 'react';
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import MainLayout from './components/layout/MainLayout';

// Import pages
import InventoryDashboard from './pages/Dashboard/InventoryDashboard';
import ItemsList from './pages/Items/ItemsList';
import ItemForm from './pages/Items/ItemForm';
import ItemDetail from './pages/Items/ItemDetail';
import CategoryList from './pages/Categories/CategoryList';
import CategoryForm from './pages/Categories/CategoryForm';
import UOMList from './pages/UOM/UOMList';
import UOMForm from './pages/UOM/UOMForm';
import WarehouseList from './pages/Warehouses/WarehouseList';
import WarehouseForm from './pages/Warehouses/WarehouseForm';
import WarehouseDetail from './pages/Warehouses/WarehouseDetail';
import StockTransactionList from './pages/StockTransactions/StockTransactionList';
import StockTransactionForm from './pages/StockTransactions/StockTransactionForm';
import StockAdjustmentList from './pages/StockAdjustments/StockAdjustmentList';
import StockAdjustmentForm from './pages/StockAdjustments/StockAdjustmentForm';
import StockAdjustmentDetail from './pages/StockAdjustments/StockAdjustmentDetail';

import NotFound from './pages/NotFound';

const App = () => {
  return (
    <Router>
      <Routes>
        {/* Redirect from root to dashboard */}
        <Route path="/" element={<Navigate replace to="/dashboard" />} />
        
        {/* Main layout routes */}
        <Route path="/" element={<MainLayout />}>
          {/* Dashboard */}
          <Route path="dashboard" element={<InventoryDashboard />} />
          
          {/* Items */}
          <Route path="items" element={<ItemsList />} />
          <Route path="items/create" element={<ItemForm />} />
          <Route path="items/:id" element={<ItemDetail />} />
          <Route path="items/:id/edit" element={<ItemForm />} />
          
          {/* Categories */}
          <Route path="item-categories" element={<CategoryList />} />
          <Route path="item-categories/create" element={<CategoryForm />} />
          <Route path="item-categories/:id/edit" element={<CategoryForm />} />
          
          {/* Units of Measure */}
          <Route path="uom" element={<UOMList />} />
          <Route path="uom/create" element={<UOMForm />} />
          <Route path="uom/:id/edit" element={<UOMForm />} />
          
          {/* Warehouses */}
          <Route path="warehouses" element={<WarehouseList />} />
          <Route path="warehouses/create" element={<WarehouseForm />} />
          <Route path="warehouses/:id" element={<WarehouseDetail />} />
          <Route path="warehouses/:id/edit" element={<WarehouseForm />} />
          
          {/* Stock Transactions */}
          <Route path="stock-transactions" element={<StockTransactionList />} />
          <Route path="stock-transactions/create" element={<StockTransactionForm />} />
          
          {/* Stock Adjustments */}
          <Route path="stock-adjustments" element={<StockAdjustmentList />} />
          <Route path="stock-adjustments/create" element={<StockAdjustmentForm />} />
          <Route path="stock-adjustments/:id" element={<StockAdjustmentDetail />} />
        </Route>
        
        {/* Not Found */}
        <Route path="*" element={<NotFound />} />
      </Routes>
    </Router>
  );
};

export default App;