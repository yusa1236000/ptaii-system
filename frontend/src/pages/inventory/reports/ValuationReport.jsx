// src/pages/inventory/reports/ValuationReport.jsx
import React, { useState } from 'react';
import { useQuery } from 'react-query';
import { Download, Filter, RefreshCw, DollarSign, TrendingUp, Package, Tag } from 'lucide-react';
import { inventoryReportService, categoryService, warehouseService } from '../../../services/inventoryService';
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

const ValuationReport = () => {
  // State for filters
  const [filters, setFilters] = useState({
    category_id: '',
    warehouse_id: '',
    valuation_method: 'average_cost',
    min_value: '',
    max_value: '',
    as_of_date: new Date().toISOString().split('T')[0]
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
      category_id: '',
      warehouse_id: '',
      valuation_method: 'average_cost',
      min_value: '',
      max_value: '',
      as_of_date: new Date().toISOString().split('T')[0]
    });
  };
  
  // Fetch report data
  const { 
    data: reportData, 
    isLoading: reportLoading, 
    isError: reportError,
    refetch: refetchReport
  } = useQuery(
    ['valuationReport', filters],
    () => inventoryReportService.getValuationReport(filters)
  );
  
  // Fetch categories for filter dropdown
  const { 
    data: categoriesData, 
    isLoading: categoriesLoading 
  } = useQuery('categories', () => categoryService.getAll());
  
  // Fetch warehouses for filter dropdown
  const { 
    data: warehousesData, 
    isLoading: warehousesLoading 
  } = useQuery('warehouses-dropdown', () => warehouseService.getAll());
  
  // Handle export
  const handleExport = async () => {
    try {
      const response = await inventoryReportService.exportValuationReport(filters);
      
      // Create a blob from the response data
      const blob = new Blob([response.data], { type: response.headers['content-type'] });
      const url = window.URL.createObjectURL(blob);
      
      // Create a temporary link element and trigger download
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `valuation-report-${filters.as_of_date}.xlsx`);
      document.body.appendChild(link);
      link.click();
      
      // Clean up
      link.parentNode.removeChild(link);
      window.URL.revokeObjectURL(url);
    } catch (error) {
      console.error('Error exporting report:', error);
    }
  };
  
  // Define table columns
  const columns = [
    {
      key: 'item_code',
      label: 'Item Code',
      render: (item) => <div className="text-sm text-gray-900">{item.item_code}</div>,
    },
    {
      key: 'name',
      label: 'Name',
      render: (item) => <div className="font-medium text-gray-900">{item.name}</div>,
    },
    {
      key: 'category',
      label: 'Category',
      render: (item) => <div className="text-sm text-gray-500">{item.category?.name || '-'}</div>,
    },
    {
      key: 'quantity',
      label: 'Quantity',
      render: (item) => (
        <div className="text-sm text-gray-900">
          {item.quantity} {item.unitOfMeasure?.symbol || ''}
        </div>
      ),
    },
    {
      key: 'unit_cost',
      label: 'Unit Cost',
      render: (item) => (
        <div className="text-sm text-gray-900">
          ${item.unit_cost.toFixed(2)}
        </div>
      ),
    },
    {
      key: 'total_value',
      label: 'Total Value',
      render: (item) => (
        <div className="text-sm font-medium text-gray-900">
          ${item.total_value.toFixed(2)}
        </div>
      ),
    },
    {
      key: 'warehouse',
      label: 'Warehouse',
      render: (item) => <div className="text-sm text-gray-500">{item.warehouse?.name || 'All'}</div>,
    },
    {
      key: 'percentage',
      label: '% of Total',
      render: (item) => (
        <div className="relative pt-1">
          <div className="overflow-hidden h-2 text-xs flex rounded bg-blue-200">
            <div 
              style={{ width: `${item.percentage_of_total}%` }} 
              className="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-blue-500"
            ></div>
          </div>
          <div className="text-xs text-gray-500 mt-1">{item.percentage_of_total.toFixed(1)}%</div>
        </div>
      ),
    },
  ];
  
  return (
    <div>
      <PageHeader 
        title="Inventory Valuation Report" 
        subtitle="Analyze the financial value of your inventory"
        actionLabel="Export to Excel"
        onActionClick={handleExport}
      />
      
      {/* Filters */}
      <Card className="mb-6">
        <CardBody>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div className="space-y-4">
              <FormGroup>
                <Label htmlFor="category_id">Category</Label>
                <Select
                  id="category_id"
                  value={filters.category_id}
                  onChange={(e) => handleFilterChange('category_id', e.target.value)}
                  options={
                    categoriesLoading
                      ? []
                      : categoriesData?.data.map((category) => ({
                          value: category.category_id,
                          label: category.name,
                        })) || []
                  }
                  placeholder="All Categories"
                  disabled={categoriesLoading}
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
                <Label htmlFor="valuation_method">Valuation Method</Label>
                <Select
                  id="valuation_method"
                  value={filters.valuation_method}
                  onChange={(e) => handleFilterChange('valuation_method', e.target.value)}
                  options={[
                    { value: 'average_cost', label: 'Average Cost' },
                    { value: 'fifo', label: 'FIFO (First In, First Out)' },
                    { value: 'lifo', label: 'LIFO (Last In, First Out)' },
                  ]}
                />
              </FormGroup>
              
              <FormGroup>
                <Label htmlFor="as_of_date">As of Date</Label>
                <Input
                  id="as_of_date"
                  type="date"
                  value={filters.as_of_date}
                  onChange={(e) => handleFilterChange('as_of_date', e.target.value)}
                  max={new Date().toISOString().split('T')[0]}
                />
              </FormGroup>
            </div>
            
            <div className="space-y-4">
              <div className="grid grid-cols-2 gap-2">
                <FormGroup>
                  <Label htmlFor="min_value">Min Value ($)</Label>
                  <Input
                    id="min_value"
                    type="number"
                    min="0"
                    step="0.01"
                    value={filters.min_value}
                    onChange={(e) => handleFilterChange('min_value', e.target.value)}
                  />
                </FormGroup>
                
                <FormGroup>
                  <Label htmlFor="max_value">Max Value ($)</Label>
                  <Input
                    id="max_value"
                    type="number"
                    min="0"
                    step="0.01"
                    value={filters.max_value}
                    onChange={(e) => handleFilterChange('max_value', e.target.value)}
                  />
                </FormGroup>
              </div>
              
              <div className="flex justify-end space-y-2">
                <Button
                  variant="secondary"
                  onClick={handleResetFilters}
                  className="mr-2"
                >
                  <Filter className="h-4 w-4 mr-2" />
                  Reset Filters
                </Button>
                <Button
                  variant="primary"
                  onClick={() => refetchReport()}
                >
                  <RefreshCw className="h-4 w-4 mr-2" />
                  Refresh Report
                </Button>
              </div>
            </div>
          </div>
        </CardBody>
      </Card>
      
      {/* Summary Section */}
      {reportData?.summary && (
        <div className="mb-6">
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <Card>
              <CardBody>
                <div className="flex items-center">
                  <div className="bg-blue-100 p-3 rounded-full">
                    <DollarSign className="h-6 w-6 text-blue-600" />
                  </div>
                  <div className="ml-4">
                    <div className="text-sm text-gray-500">Total Valuation</div>
                    <div className="text-xl font-bold text-gray-900">${reportData.summary.total_valuation.toFixed(2)}</div>
                  </div>
                </div>
              </CardBody>
            </Card>
            
            <Card>
              <CardBody>
                <div className="flex items-center">
                  <div className="bg-green-100 p-3 rounded-full">
                    <Package className="h-6 w-6 text-green-600" />
                  </div>
                  <div className="ml-4">
                    <div className="text-sm text-gray-500">Total Items</div>
                    <div className="text-xl font-bold text-gray-900">{reportData.summary.total_items}</div>
                  </div>
                </div>
              </CardBody>
            </Card>
            
            <Card>
              <CardBody>
                <div className="flex items-center">
                  <div className="bg-purple-100 p-3 rounded-full">
                    <Tag className="h-6 w-6 text-purple-600" />
                  </div>
                  <div className="ml-4">
                    <div className="text-sm text-gray-500">Average Item Value</div>
                    <div className="text-xl font-bold text-gray-900">${reportData.summary.average_item_value.toFixed(2)}</div>
                  </div>
                </div>
              </CardBody>
            </Card>
            
            <Card>
              <CardBody>
                <div className="flex items-center">
                  <div className="bg-amber-100 p-3 rounded-full">
                    <TrendingUp className="h-6 w-6 text-amber-600" />
                  </div>
                  <div className="ml-4">
                    <div className="text-sm text-gray-500">Highest Value Item</div>
                    <div className="text-xl font-bold text-gray-900">${reportData.summary.highest_value_item.toFixed(2)}</div>
                  </div>
                </div>
              </CardBody>
            </Card>
          </div>
          
          {/* Category Distribution Chart */}
          {reportData?.categoryDistribution && (
            <Card className="mb-6">
              <CardHeader title="Valuation by Category" />
              <CardBody className="h-80">
                <ResponsiveContainer width="100%" height="100%">
                  <BarChart
                    data={reportData.categoryDistribution}
                    margin={{ top: 20, right: 30, left: 20, bottom: 5 }}
                  >
                    <CartesianGrid strokeDasharray="3 3" />
                    <XAxis dataKey="name" />
                    <YAxis
                      tickFormatter={(value) => `$${value}`}
                    />
                    <Tooltip 
                      formatter={(value) => [`$${value.toFixed(2)}`, 'Value']}
                    />
                    <Legend />
                    <Bar 
                      name="Category Value" 
                      dataKey="value" 
                      fill="#8884d8" 
                      label={{ 
                        position: 'top', 
                        formatter: (value) => `$${value.toFixed(0)}` 
                      }} 
                    />
                  </BarChart>
                </ResponsiveContainer>
              </CardBody>
            </Card>
          )}
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
        emptyMessage="No valuation data found matching your filters."
        pagination={reportData?.meta}
        onPageChange={(page) => handleFilterChange('page', page)}
      />
    </div>
  );
};

export default ValuationReport;