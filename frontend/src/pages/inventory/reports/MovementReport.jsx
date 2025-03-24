// src/pages/inventory/reports/MovementReport.jsx
import React, { useState, useEffect } from 'react';
import { useQuery } from 'react-query';
import { Download, Filter, RefreshCw, Calendar, Package, Warehouse, TrendingUp, TrendingDown } from 'lucide-react';
import { inventoryReportService, itemService, warehouseService } from '../../../services/inventoryService';
import PageHeader from '../../../components/common/PageHeader';
import Table from '../../../components/common/Table';
import { Card, CardBody, CardHeader } from '../../../components/common/Card';
import { 
  FormGroup, 
  Label, 
  Input, 
  Select, 
  Button 
} from '../../../components/common/FormElements';
import Alert from '../../../components/common/Alert';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';

const MovementReport = () => {
  // State for filters
  const [filters, setFilters] = useState({
    item_id: '',
    warehouse_id: '',
    transaction_type: '',
    date_from: '',
    date_to: new Date().toISOString().split('T')[0],
  });
  
  // Handle filter changes
  const handleFilterChange = (name, value) => {
    setFilters((prev) => ({
      ...prev,
      [name]: value,
    }));
  };
  
  // Handle reset filters
  const handleResetFilters = () => {
    setFilters({
      item_id: '',
      warehouse_id: '',
      transaction_type: '',
      date_from: '',
      date_to: new Date().toISOString().split('T')[0],
    });
  };
  
  // Fetch report data
  const { 
    data: reportData, 
    isLoading: reportLoading, 
    isError: reportError,
    refetch: refetchReport
  } = useQuery(
    ['movementReport', filters],
    () => inventoryReportService.getMovementReport(filters)
  );
  
  // Fetch items for filter dropdown
  const { 
    data: itemsData, 
    isLoading: itemsLoading 
  } = useQuery('items-dropdown', () => itemService.getAll({ per_page: 100 }));
  
  // Fetch warehouses for filter dropdown
  const { 
    data: warehousesData, 
    isLoading: warehousesLoading 
  } = useQuery('warehouses-dropdown', () => warehouseService.getAll());
  
  // Handle export
  const handleExport = async () => {
    try {
      const response = await inventoryReportService.exportMovementReport(filters);
      
      // Create a blob from the response data
      const blob = new Blob([response.data], { type: response.headers['content-type'] });
      const url = window.URL.createObjectURL(blob);
      
      // Create a temporary link element and trigger download
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `movement-report-${new Date().toISOString().split('T')[0]}.xlsx`);
      document.body.appendChild(link);
      link.click();
      
      // Clean up
      link.parentNode.removeChild(link);
      window.URL.revokeObjectURL(url);
    } catch (error) {
      console.error('Error exporting report:', error);
      // You might want to show an error notification here
    }
  };
  
  // Define table columns
  const columns = [
    {
      key: 'date',
      label: 'Date',
      render: (transaction) => (
        <div className="whitespace-nowrap text-sm text-gray-900">
          {new Date(transaction.transaction_date).toLocaleDateString()}
        </div>
      ),
    },
    {
      key: 'item',
      label: 'Item',
      render: (transaction) => (
        <div>
          <div className="font-medium text-gray-900">{transaction.item?.name}</div>
          <div className="text-xs text-gray-500">{transaction.item?.item_code}</div>
        </div>
      ),
    },
    {
      key: 'type',
      label: 'Type',
      render: (transaction) => {
        const isInflow = ['IN', 'RECEIPT', 'ADJUSTMENT_IN', 'RETURN'].includes(transaction.transaction_type);
        return (
          <span 
            className={`px-2 py-1 inline-flex items-center text-xs leading-5 font-semibold rounded-full ${
              isInflow ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
            }`}
          >
            {isInflow ? (
              <TrendingUp className="h-3 w-3 mr-1" />
            ) : (
              <TrendingDown className="h-3 w-3 mr-1" />
            )}
            {transaction.transaction_type}
          </span>
        );
      },
    },
    {
      key: 'quantity',
      label: 'Quantity',
      render: (transaction) => (
        <div className="text-sm font-medium text-gray-900">
          {transaction.quantity} {transaction.item?.unitOfMeasure?.symbol || ''}
        </div>
      ),
    },
    {
      key: 'warehouse',
      label: 'Warehouse',
      render: (transaction) => (
        <div className="text-sm text-gray-900">
          {transaction.warehouse?.name || '-'}
        </div>
      ),
    },
    {
      key: 'reference',
      label: 'Reference',
      render: (transaction) => (
        <div className="text-sm text-gray-500">
          {transaction.reference_document ? `${transaction.reference_document} ${transaction.reference_number || ''}` : '-'}
        </div>
      ),
    },
  ];
  
  return (
    <div>
      <PageHeader 
        title="Inventory Movement Report" 
        subtitle="Track stock inflows and outflows over time"
        actionLabel="Export to Excel"
        onActionClick={handleExport}
      />
      
      {/* Filters */}
      <Card className="mb-6">
        <CardBody>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div className="space-y-4">
              <FormGroup>
                <Label htmlFor="item_id">Item</Label>
                <Select
                  id="item_id"
                  value={filters.item_id}
                  onChange={(e) => handleFilterChange('item_id', e.target.value)}
                  options={
                    itemsLoading
                      ? []
                      : itemsData?.data.map((item) => ({
                          value: item.item_id,
                          label: `${item.name} (${item.item_code})`,
                        })) || []
                  }
                  placeholder="All Items"
                  disabled={itemsLoading}
                />
              </FormGroup>
              
              <FormGroup>
                <Label htmlFor="warehouse_id">Warehouse</Label>
                <Select
                  id="warehouse_id"
                  value={filters.warehouse_id}
                  onChange={(e) => handleFilterChange('warehouse_id', e.target.value)}
                  options={
                    warehousesLoading
                      ? []
                      : warehousesData?.data.map((warehouse) => ({
                          value: warehouse.warehouse_id,
                          label: warehouse.name,
                        })) || []
                  }
                  placeholder="All Warehouses"
                  disabled={warehousesLoading}
                />
              </FormGroup>
            </div>
            
            <div className="space-y-4">
              <FormGroup>
                <Label htmlFor="transaction_type">Transaction Type</Label>
                <Select
                  id="transaction_type"
                  value={filters.transaction_type}
                  onChange={(e) => handleFilterChange('transaction_type', e.target.value)}
                  options={[
                    { value: 'IN', label: 'Stock In' },
                    { value: 'OUT', label: 'Stock Out' },
                    { value: 'RECEIPT', label: 'Receipt' },
                    { value: 'ISSUE', label: 'Issue' },
                    { value: 'SALE', label: 'Sale' },
                    { value: 'RETURN', label: 'Return' },
                    { value: 'ADJUSTMENT_IN', label: 'Adjustment In' },
                    { value: 'ADJUSTMENT_OUT', label: 'Adjustment Out' },
                  ]}
                  placeholder="All Types"
                />
              </FormGroup>
              
              <div className="grid grid-cols-2 gap-2">
                <FormGroup>
                  <Label htmlFor="date_from">Date From</Label>
                  <Input
                    id="date_from"
                    type="date"
                    value={filters.date_from}
                    onChange={(e) => handleFilterChange('date_from', e.target.value)}
                    max={filters.date_to || undefined}
                  />
                </FormGroup>
                
                <FormGroup>
                  <Label htmlFor="date_to">Date To</Label>
                  <Input
                    id="date_to"
                    type="date"
                    value={filters.date_to}
                    onChange={(e) => handleFilterChange('date_to', e.target.value)}
                    min={filters.date_from || undefined}
                  />
                </FormGroup>
              </div>
            </div>
            
            <div className="flex flex-col justify-end space-y-2">
              <Button
                variant="secondary"
                onClick={handleResetFilters}
                className="w-full"
              >
                <Filter className="h-4 w-4 mr-2" />
                Reset Filters
              </Button>
              <Button
                variant="primary"
                onClick={() => refetchReport()}
                className="w-full"
              >
                <RefreshCw className="h-4 w-4 mr-2" />
                Refresh Report
              </Button>
            </div>
          </div>
        </CardBody>
      </Card>
      
      {/* Chart Section */}
      {reportData?.chart && (
        <Card className="mb-6">
          <CardHeader title="Movement Trend" />
          <CardBody className="h-80">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart
                data={reportData.chart}
                margin={{ top: 5, right: 30, left: 20, bottom: 5 }}
              >
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis dataKey="date" />
                <YAxis />
                <Tooltip />
                <Legend />
                <Bar name="In" dataKey="in" fill="#10B981" />
                <Bar name="Out" dataKey="out" fill="#EF4444" />
              </BarChart>
            </ResponsiveContainer>
          </CardBody>
        </Card>
      )}
      
      {/* Summary Cards */}
      {reportData?.summary && (
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
          <Card>
            <CardBody>
              <div className="flex items-center">
                <div className="bg-green-100 p-3 rounded-full">
                  <TrendingUp className="h-6 w-6 text-green-600" />
                </div>
                <div className="ml-4">
                  <div className="text-sm text-gray-500">Total In</div>
                  <div className="text-xl font-bold text-gray-900">{reportData.summary.total_in}</div>
                </div>
              </div>
            </CardBody>
          </Card>
          
          <Card>
            <CardBody>
              <div className="flex items-center">
                <div className="bg-red-100 p-3 rounded-full">
                  <TrendingDown className="h-6 w-6 text-red-600" />
                </div>
                <div className="ml-4">
                  <div className="text-sm text-gray-500">Total Out</div>
                  <div className="text-xl font-bold text-gray-900">{reportData.summary.total_out}</div>
                </div>
              </div>
            </CardBody>
          </Card>
          
          <Card>
            <CardBody>
              <div className="flex items-center">
                <div className="bg-blue-100 p-3 rounded-full">
                  <Package className="h-6 w-6 text-blue-600" />
                </div>
                <div className="ml-4">
                  <div className="text-sm text-gray-500">Net Change</div>
                  <div className="text-xl font-bold text-gray-900">{reportData.summary.net_change}</div>
                </div>
              </div>
            </CardBody>
          </Card>
          
          <Card>
            <CardBody>
              <div className="flex items-center">
                <div className="bg-purple-100 p-3 rounded-full">
                  <Calendar className="h-6 w-6 text-purple-600" />
                </div>
                <div className="ml-4">
                  <div className="text-sm text-gray-500">Transactions</div>
                  <div className="text-xl font-bold text-gray-900">{reportData.summary.transaction_count}</div>
                </div>
              </div>
            </CardBody>
          </Card>
        </div>
      )}
      
      {/* Report Table */}
      {reportError && (
        <Alert
          type="error"
          title="Error loading report"
          message="There was an error loading the report. Please try again later."
          className="mb-6"
        />
      )}
      
      <Table
        columns={columns}
        data={reportData?.data || []}
        isLoading={reportLoading}
        emptyMessage="No transactions found matching your filters."
      />
    </div>
  );
};

export default MovementReport

