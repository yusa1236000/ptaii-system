// src/pages/Dashboard/InventoryDashboard.jsx
import React, { useEffect, useState } from 'react';
import { itemService, warehouseService } from '../../services/inventoryService';
import { 
  Card, 
  CardContent, 
  CardHeader, 
  CardTitle 
} from '../../components/ui/card';
import {
  BarChart,
  Bar,
  LineChart,
  Line,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  Legend,
  ResponsiveContainer
} from 'recharts';

const InventoryDashboard = () => {
  const [stockStatus, setStockStatus] = useState([]);
  const [lowStockItems, setLowStockItems] = useState([]);
  const [warehouseStats, setWarehouseStats] = useState([]);
  const [loading, setLoading] = useState(true);
  
  useEffect(() => {
    const fetchData = async () => {
      try {
        // Fetch stock status data
        const stockResponse = await itemService.getStockStatus();
        setStockStatus(stockResponse.data.data);
        
        // Filter low stock items
        const lowStock = stockResponse.data.data.filter(item => item.status === 'Low Stock');
        setLowStockItems(lowStock);
        
        // Fetch warehouse data
        const warehouseResponse = await warehouseService.getAll();
        setWarehouseStats(warehouseResponse.data.data);
        
      } catch (error) {
        console.error('Error fetching dashboard data:', error);
      } finally {
        setLoading(false);
      }
    };
    
    fetchData();
  }, []);
  
  // Prepare data for charts
  const stockStatusData = [
    { name: 'Low Stock', value: stockStatus.filter(item => item.status === 'Low Stock').length },
    { name: 'Normal', value: stockStatus.filter(item => item.status === 'Normal').length },
    { name: 'Over Stock', value: stockStatus.filter(item => item.status === 'Over Stock').length }
  ];
  
  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold">Inventory Dashboard</h1>
      
      {loading ? (
        <div className="flex justify-center items-center h-64">
          <p>Loading dashboard data...</p>
        </div>
      ) : (
        <>
          {/* Summary Cards */}
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            <Card>
              <CardHeader>
                <CardTitle>Total Items</CardTitle>
              </CardHeader>
              <CardContent>
                <p className="text-3xl font-bold">{stockStatus.length}</p>
              </CardContent>
            </Card>
            <Card>
              <CardHeader>
                <CardTitle>Low Stock Items</CardTitle>
              </CardHeader>
              <CardContent>
                <p className="text-3xl font-bold text-red-600">{lowStockItems.length}</p>
              </CardContent>
            </Card>
            <Card>
              <CardHeader>
                <CardTitle>Total Warehouses</CardTitle>
              </CardHeader>
              <CardContent>
                <p className="text-3xl font-bold">{warehouseStats.length}</p>
              </CardContent>
            </Card>
          </div>
          
          {/* Charts */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <Card>
              <CardHeader>
                <CardTitle>Stock Status Distribution</CardTitle>
              </CardHeader>
              <CardContent>
                <div className="h-80">
                  <ResponsiveContainer width="100%" height="100%">
                    <BarChart data={stockStatusData}>
                      <CartesianGrid strokeDasharray="3 3" />
                      <XAxis dataKey="name" />
                      <YAxis />
                      <Tooltip />
                      <Legend />
                      <Bar dataKey="value" fill="#4F46E5" />
                    </BarChart>
                  </ResponsiveContainer>
                </div>
              </CardContent>
            </Card>
            
            <Card>
              <CardHeader>
                <CardTitle>Low Stock Alert</CardTitle>
              </CardHeader>
              <CardContent>
                <div className="overflow-x-auto">
                  <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                      <tr>
                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item Code</th>
                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Stock</th>
                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Min. Stock</th>
                      </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-200">
                      {lowStockItems.length > 0 ? (
                        lowStockItems.slice(0, 5).map((item) => (
                          <tr key={item.item_id}>
                            <td className="px-6 py-4 whitespace-nowrap">{item.item_code}</td>
                            <td className="px-6 py-4 whitespace-nowrap">{item.name}</td>
                            <td className="px-6 py-4 whitespace-nowrap text-red-600 font-medium">{item.current_stock}</td>
                            <td className="px-6 py-4 whitespace-nowrap">{item.minimum_stock}</td>
                          </tr>
                        ))
                      ) : (
                        <tr>
                          <td colSpan="4" className="px-6 py-4 text-center">No low stock items</td>
                        </tr>
                      )}
                    </tbody>
                  </table>
                  {lowStockItems.length > 5 && (
                    <div className="mt-4 text-right">
                      <a href="/items" className="text-blue-600 hover:text-blue-800">
                        View all {lowStockItems.length} low stock items
                      </a>
                    </div>
                  )}
                </div>
              </CardContent>
            </Card>
          </div>
        </>
      )}
    </div>
  );
};