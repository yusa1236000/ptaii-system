// src/pages/inventory/Dashboard.jsx
import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { useQuery } from 'react-query';
import { 
  Package, 
  AlertTriangle, 
  Plus, 
  Minus, 
  ArrowRight, 
  TrendingUp, 
  TrendingDown 
} from 'lucide-react';
import { itemService } from '../../services/inventoryService';
import { Card, CardHeader, CardBody } from '../../components/common/Card';
import PageHeader from '../../components/common/PageHeader';
import { Button } from '../../components/common/FormElements';
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
  ResponsiveContainer,
  PieChart,
  Pie,
  Cell,
} from 'recharts';

const InventoryDashboard = () => {
  const [stockStats, setStockStats] = useState({
    totalItems: 0,
    lowStockItems: 0,
    outOfStockItems: 0,
    overStockItems: 0,
  });

  // Fetch stock status data
  const { data: stockStatusData, isLoading: stockStatusLoading } = useQuery(
    'stockStatus', 
    itemService.getStockStatus
  );

  // Fetch recent transactions
  const { data: recentTransactions, isLoading: transactionsLoading } = useQuery(
    'recentTransactions',
    () => itemService.getRecentTransactions({ limit: 5 })
  );

  // Prepare stats from fetched data
  useEffect(() => {
    if (stockStatusData?.data) {
      const items = stockStatusData.data;
      setStockStats({
        totalItems: items.length,
        lowStockItems: items.filter(item => item.status === 'Low Stock').length,
        outOfStockItems: items.filter(item => item.current_stock <= 0).length,
        overStockItems: items.filter(item => item.status === 'Over Stock').length,
      });
    }
  }, [stockStatusData]);

  // Stock distribution data for pie chart
  const stockDistributionData = [
    { name: 'Low Stock', value: stockStats.lowStockItems },
    { name: 'In Stock', value: stockStats.totalItems - stockStats.lowStockItems - stockStats.outOfStockItems - stockStats.overStockItems },
    { name: 'Out of Stock', value: stockStats.outOfStockItems },
    { name: 'Over Stock', value: stockStats.overStockItems },
  ];

  // Create a color array for the pie chart
  const COLORS = ['#FF8042', '#00C49F', '#FF0000', '#FFBB28'];

  // Mockup data for inventory trend chart (would come from API in real implementation)
  const inventoryTrendData = [
    { name: 'Jan', inflow: 400, outflow: 240 },
    { name: 'Feb', inflow: 300, outflow: 139 },
    { name: 'Mar', inflow: 200, outflow: 980 },
    { name: 'Apr', inflow: 278, outflow: 390 },
    { name: 'May', inflow: 189, outflow: 480 },
    { name: 'Jun', inflow: 239, outflow: 380 },
  ];

  return (
    <div>
      <PageHeader 
        title="Inventory Dashboard" 
        subtitle="Overview of your inventory status"
      />

      {/* Stats Card Section */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <Card>
          <CardBody>
            <div className="flex items-center">
              <div className="flex-shrink-0 bg-indigo-100 rounded-md p-3">
                <Package className="h-6 w-6 text-indigo-600" />
              </div>
              <div className="ml-4">
                <p className="text-sm font-medium text-gray-500">Total Items</p>
                <p className="text-2xl font-semibold text-gray-900">
                  {stockStatusLoading ? '...' : stockStats.totalItems}
                </p>
              </div>
            </div>
          </CardBody>
        </Card>

        <Card>
          <CardBody>
            <div className="flex items-center">
              <div className="flex-shrink-0 bg-red-100 rounded-md p-3">
                <AlertTriangle className="h-6 w-6 text-red-600" />
              </div>
              <div className="ml-4">
                <p className="text-sm font-medium text-gray-500">Low Stock Items</p>
                <p className="text-2xl font-semibold text-gray-900">
                  {stockStatusLoading ? '...' : stockStats.lowStockItems}
                </p>
              </div>
            </div>
          </CardBody>
        </Card>

        <Card>
          <CardBody>
            <div className="flex items-center">
              <div className="flex-shrink-0 bg-yellow-100 rounded-md p-3">
                <Minus className="h-6 w-6 text-yellow-600" />
              </div>
              <div className="ml-4">
                <p className="text-sm font-medium text-gray-500">Out of Stock</p>
                <p className="text-2xl font-semibold text-gray-900">
                  {stockStatusLoading ? '...' : stockStats.outOfStockItems}
                </p>
              </div>
            </div>
          </CardBody>
        </Card>

        <Card>
          <CardBody>
            <div className="flex items-center">
              <div className="flex-shrink-0 bg-green-100 rounded-md p-3">
                <Plus className="h-6 w-6 text-green-600" />
              </div>
              <div className="ml-4">
                <p className="text-sm font-medium text-gray-500">Over Stock</p>
                <p className="text-2xl font-semibold text-gray-900">
                  {stockStatusLoading ? '...' : stockStats.overStockItems}
                </p>
              </div>
            </div>
          </CardBody>
        </Card>
      </div>

      {/* Charts Row */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <Card>
          <CardHeader 
            title="Stock Distribution" 
            subtitle="Current inventory status distribution"
          />
          <CardBody className="h-80">
            <ResponsiveContainer width="100%" height="100%">
              <PieChart>
                <Pie
                  data={stockDistributionData}
                  cx="50%"
                  cy="50%"
                  labelLine={true}
                  outerRadius={100}
                  fill="#8884d8"
                  dataKey="value"
                  label={({ name, percent }) => `${name}: ${(percent * 100).toFixed(0)}%`}
                >
                  {stockDistributionData.map((entry, index) => (
                    <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                  ))}
                </Pie>
                <Tooltip />
                <Legend />
              </PieChart>
            </ResponsiveContainer>
          </CardBody>
        </Card>

        <Card>
          <CardHeader 
            title="Inventory Trends" 
            subtitle="Monthly inflows and outflows"
          />
          <CardBody className="h-80">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart
                data={inventoryTrendData}
                margin={{
                  top: 5,
                  right: 30,
                  left: 20,
                  bottom: 5,
                }}
              >
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis dataKey="name" />
                <YAxis />
                <Tooltip />
                <Legend />
                <Bar name="Inflow" dataKey="inflow" fill="#4F46E5" />
                <Bar name="Outflow" dataKey="outflow" fill="#F97316" />
              </BarChart>
            </ResponsiveContainer>
          </CardBody>
        </Card>
      </div>

      {/* Recent Low Stock & Transactions Row */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <Card>
          <CardHeader 
            title="Low Stock Items" 
            subtitle="Items that need to be restocked soon"
            action={
              <Link to="/inventory/items?status=Low Stock">
                <Button variant="light" size="sm">
                  View All
                </Button>
              </Link>
            }
          />
          <CardBody>
            {stockStatusLoading ? (
              <p className="text-gray-500 text-center py-4">Loading...</p>
            ) : stockStatusData?.data?.filter(item => item.status === 'Low Stock').length > 0 ? (
              <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-gray-200">
                  <thead className="bg-gray-50">
                    <tr>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Stock</th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Min Stock</th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                    </tr>
                  </thead>
                  <tbody className="bg-white divide-y divide-gray-200">
                    {stockStatusData.data
                      .filter(item => item.status === 'Low Stock')
                      .slice(0, 5)
                      .map((item) => (
                        <tr key={item.item_id}>
                          <td className="px-6 py-4 whitespace-nowrap">
                            <div className="font-medium text-gray-900">{item.name}</div>
                            <div className="text-sm text-gray-500">{item.item_code}</div>
                          </td>
                          <td className="px-6 py-4 whitespace-nowrap">
                            <span className="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                              {item.current_stock}
                            </span>
                          </td>
                          <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {item.minimum_stock}
                          </td>
                          <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <Link 
                              to={`/inventory/items/${item.item_id}`} 
                              className="text-indigo-600 hover:text-indigo-900"
                            >
                              View <ArrowRight className="h-4 w-4 inline" />
                            </Link>
                          </td>
                        </tr>
                      ))}
                  </tbody>
                </table>
              </div>
            ) : (
              <p className="text-gray-500 text-center py-4">No low stock items found</p>
            )}
          </CardBody>
        </Card>

        <Card>
          <CardHeader 
            title="Recent Transactions" 
            subtitle="Latest stock movements"
            action={
              <Link to="/inventory/transactions">
                <Button variant="light" size="sm">
                  View All
                </Button>
              </Link>
            }
          />
          <CardBody>
            {transactionsLoading ? (
              <p className="text-gray-500 text-center py-4">Loading...</p>
            ) : recentTransactions?.data?.length > 0 ? (
              <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-gray-200">
                  <thead className="bg-gray-50">
                    <tr>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                    </tr>
                  </thead>
                  <tbody className="bg-white divide-y divide-gray-200">
                    {recentTransactions.data.map((transaction) => (
                      <tr key={transaction.transaction_id}>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                          {new Date(transaction.transaction_date).toLocaleDateString()}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                          <div className="font-medium text-gray-900">{transaction.item.name}</div>
                          <div className="text-sm text-gray-500">{transaction.item.item_code}</div>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                          {transaction.transaction_type === 'IN' || 
                           transaction.transaction_type === 'RECEIPT' || 
                           transaction.transaction_type === 'ADJUSTMENT_IN' ? (
                            <span className="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                              <TrendingUp className="h-4 w-4 mr-1" />
                              {transaction.transaction_type}
                            </span>
                          ) : (
                            <span className="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                              <TrendingDown className="h-4 w-4 mr-1" />
                              {transaction.transaction_type}
                            </span>
                          )}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                          {transaction.quantity}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            ) : (
              <p className="text-gray-500 text-center py-4">No recent transactions found</p>
            )}
          </CardBody>
        </Card>
      </div>
    </div>
  );
};

export default InventoryDashboard;