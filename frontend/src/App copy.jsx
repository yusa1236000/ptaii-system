// src/App.jsx
import React from 'react';
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from 'react-query';
import { ToastContainer } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';

// Auth
import { AuthProvider } from './contexts/AuthContext';
import ProtectedRoute from './components/auth/ProtectedRoute';
import Login from './pages/auth/Login';

// Layout
import MainLayout from './components/layout/MainLayout';

// Dashboard
import InventoryDashboard from './pages/inventory/Dashboard';

// Items
import ItemsList from './pages/inventory/items/ItemsList';
import ItemForm from './pages/inventory/items/ItemForm';
import ItemDetail from './pages/inventory/items/ItemDetail';

// Categories
import CategoriesList from './pages/inventory/categories/CategoriesList';
import CategoryForm from './pages/inventory/categories/CategoryForm';

// UOM
import UOMList from './pages/inventory/uom/UOMList';
import UOMForm from './pages/inventory/uom/UOMForm';

// Warehouses
import WarehousesList from './pages/inventory/warehouses/WarehousesList';
import WarehouseForm from './pages/inventory/warehouses/WarehouseForm';
import WarehouseDetail from './pages/inventory/warehouses/WarehouseDetail';

// Transactions
import StockTransactionsList from './pages/inventory/transactions/StockTransactionsList';
import StockTransactionForm from './pages/inventory/transactions/StockTransactionForm';

// Adjustments
import StockAdjustmentsList from './pages/inventory/adjustments/StockAdjustmentsList';
import StockAdjustmentForm from './pages/inventory/adjustments/StockAdjustmentForm';
import StockAdjustmentDetail from './pages/inventory/adjustments/StockAdjustmentDetail';

// Reports
import InventoryReports from './pages/inventory/reports/InventoryReports';
import StockReport from './pages/inventory/reports/StockReport';
import MovementReport from './pages/inventory/reports/MovementReport';
import AdjustmentReport from './pages/inventory/reports/AdjustmentReport';
import ValuationReport from './pages/inventory/reports/ValuationReport';

// Settings
import InventorySettings from './pages/inventory/settings/InventorySettings';

// Create a client
const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      refetchOnWindowFocus: false,
      retry: 1,
      staleTime: 30000,
    },
  },
});

function App() {
  return (
    <QueryClientProvider client={queryClient}>
      <AuthProvider>
        <Router>
          <Routes>
            <Route path="/login" element={<Login />} />
            
            <Route path="/" element={<ProtectedRoute><MainLayout /></ProtectedRoute>}>
              {/* Dashboard */}
              <Route index element={<Navigate to="/inventory/dashboard" replace />} />
              <Route path="inventory/dashboard" element={<InventoryDashboard />} />
              
              {/* Items */}
              <Route path="inventory/items" element={<ItemsList />} />
              <Route path="inventory/items/create" element={<ItemForm />} />
              <Route path="inventory/items/:id" element={<ItemDetail />} />
              <Route path="inventory/items/:id/edit" element={<ItemForm />} />
              
              {/* Categories */}
              <Route path="inventory/categories" element={<CategoriesList />} />
              <Route path="inventory/categories/create" element={<CategoryForm />} />
              <Route path="inventory/categories/:id/edit" element={<CategoryForm />} />
              
              {/* UOM */}
              <Route path="inventory/uom" element={<UOMList />} />
              <Route path="inventory/uom/create" element={<UOMForm />} />
              <Route path="inventory/uom/:id/edit" element={<UOMForm />} />
              
              {/* Warehouses */}
              <Route path="inventory/warehouses" element={<WarehousesList />} />
              <Route path="inventory/warehouses/create" element={<WarehouseForm />} />
              <Route path="inventory/warehouses/:id" element={<WarehouseDetail />} />
              <Route path="inventory/warehouses/:id/edit" element={<WarehouseForm />} />
              
              {/* Transactions */}
              <Route path="inventory/transactions" element={<StockTransactionsList />} />
              <Route path="inventory/transactions/create" element={<StockTransactionForm />} />
              
              {/* Adjustments */}
              <Route path="inventory/adjustments" element={<StockAdjustmentsList />} />
              <Route path="inventory/adjustments/create" element={<StockAdjustmentForm />} />
              <Route path="inventory/adjustments/:id" element={<StockAdjustmentDetail />} />
              
              {/* Reports */}
              <Route path="inventory/reports" element={<InventoryReports />} />
              <Route path="inventory/reports/stock" element={<StockReport />} />
              <Route path="inventory/reports/movement" element={<MovementReport />} />
              <Route path="inventory/reports/adjustment" element={<AdjustmentReport />} />
              <Route path="inventory/reports/valuation" element={<ValuationReport />} />
              
              {/* Settings */}
              <Route path="inventory/settings" element={<InventorySettings />} />
            </Route>
          </Routes>
        </Router>
        <ToastContainer 
          position="top-right"
          autoClose={3000}
          hideProgressBar={false}
          newestOnTop
          closeOnClick
          rtl={false}
          pauseOnFocusLoss
          draggable
          pauseOnHover
        />
      </AuthProvider>
    </QueryClientProvider>
  );
}

export default App;