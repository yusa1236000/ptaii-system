// src/pages/inventory/reports/StockReport.jsx
import React, { useState, useEffect } from 'react';
import { useQuery } from 'react-query';
import { Download, Filter, Eye, RefreshCw } from 'lucide-react';
import { inventoryReportService, categoryService } from '../../../services/inventoryService';
import PageHeader from '../../../components/common/PageHeader';
import Table from '../../../components/common/Table';
import { Card, CardBody } from '../../../components/common/Card';
import { 
  FormGroup, 
  Label, 
  Input, 
  Select, 
  Button 
} from '../../../components/common/FormElements';
import Alert from '../../../components/common/Alert';

const StockReport = () => {
  // State for filters
  const [filters, setFilters] = useState({
    category_id: '',
    status: '',
    search: '',
    min_stock: '',
    max_stock: '',
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
      status: '',
      search: '',
      min_stock: '',
      max_stock: '',
    });
  };
  
  // Fetch report data
  const { 
    data: reportData, 
    isLoading: reportLoading, 
    isError: reportError,
    refetch: refetchReport
  } = useQuery(
    ['stockReport', filters],
    () => inventoryReportService.getStockReport(filters)
  );
  
  // Fetch categories for filter dropdown
  const { 
    data: categoriesData, 
    isLoading: categoriesLoading 
  } = useQuery('categories', () => categoryService.getAll());
  
  // Handle export
  const handleExport = async () => {
    try {
      const response = await inventoryReportService.exportStockReport(filters);
      
      // Create a blob from the response data
      const blob = new Blob([response.data], { type: response.headers['content-type'] });
      const url = window.URL.createObjectURL(blob);
      
      // Create a temporary link element and trigger download
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `stock-report-${new Date().toISOString().split('T')[0]}.xlsx`);
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
      key: 'uom',
      label: 'UOM',
      render: (item) => <div className="text-sm text-gray-500">{item.unitOfMeasure?.symbol || '-'}</div>,
    },
    {
      key: 'current_stock',
      label: 'Current Stock',
      render: (item) => <div className="text-sm font-medium text-gray-900">{item.current_stock}</div>,
    },
    {
      key: 'min_stock',
      label: 'Min Stock',
      render: (item) => <div className="text-sm text-gray-500">{item.minimum_stock}</div>,
    },
    {
      key: 'max_stock',
      label: 'Max Stock',
      render: (item) => <div className="text-sm text-gray-500">{item.maximum_stock}</div>,
    },
    {
      key: 'status',
      label: 'Status',
      render: (item) => {
        const status = item.status || (
          item.current_stock <= 0 ? 'Out of Stock' :
          item.current_stock <= item.minimum_stock ? 'Low Stock' :
          item.current_stock >= item.maximum_stock ? 'Over Stock' : 'Normal'
        );
        
        let statusClass = 'bg-green-100 text-green-800'; // Normal
        if (status === 'Low Stock') {
          statusClass = 'bg-red-100 text-red-800';
        } else if (status === 'Out of Stock') {
          statusClass = 'bg-gray-100 text-gray-800';
        } else if (status === 'Over Stock') {
          statusClass = 'bg-yellow-100 text-yellow-800';
        }
        
        return (
          <span className={`px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ${statusClass}`}>
            {status}
          </span>
        );
      },
    },
    {
      key: 'action',
      label: 'Action',
      render: (item) => (
        <div className="flex space-x-2">
          <a href={`/inventory/items/${item.item_id}`} className="text-blue-600 hover:text-blue-900">
            <Eye className="h-5 w-5" />
          </a>
        </div>
      ),
    },
  ];
  
  return (
    <div>
      <PageHeader 
        title="Stock Status Report" 
        subtitle="View current inventory levels with status indicators"
        actionLabel="Export to Excel"
        onActionClick={handleExport}
      />
      
      {/* Filters */}
      <Card className="mb-6">
        <CardBody>
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
            <FormGroup>
              <Label htmlFor="search">Search</Label>
              <Input
                id="search"
                value={filters.search}
                onChange={(e) => handleFilterChange('search', e.target.value)}
                placeholder="Search by code or name"
              />
            </FormGroup>
            
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
              <Label htmlFor="status">Status</Label>
              <Select
                id="status"
                value={filters.status}
                onChange={(e) => handleFilterChange('status', e.target.value)}
                options={[
                  { value: 'Low Stock', label: 'Low Stock' },
                  { value: 'Out of Stock', label: 'Out of Stock' },
                  { value: 'Normal', label: 'Normal' },
                  { value: 'Over Stock', label: 'Over Stock' },
                ]}
                placeholder="All Status"
              />
            </FormGroup>
            
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
        emptyMessage="No items found matching your filters."
      />
      
      {/* Summary Section */}
      {reportData?.summary && (
        <div className="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
          <Card>
            <CardBody>
              <div className="text-center">
                <div className="text-sm text-gray-500">Total Items</div>
                <div className="text-2xl font-bold text-gray-900">{reportData.summary.total_items}</div>
              </div>
            </CardBody>
          </Card>
          
          <Card>
            <CardBody>
              <div className="text-center">
                <div className="text-sm text-gray-500">Low Stock Items</div>
                <div className="text-2xl font-bold text-red-600">{reportData.summary.low_stock_items}</div>
              </div>
            </CardBody>
          </Card>
          
          <Card>
            <CardBody>
              <div className="text-center">
                <div className="text-sm text-gray-500">Out of Stock Items</div>
                <div className="text-2xl font-bold text-gray-600">{reportData.summary.out_of_stock_items}</div>
              </div>
            </CardBody>
          </Card>
          
          <Card>
            <CardBody>
              <div className="text-center">
                <div className="text-sm text-gray-500">Over Stock Items</div>
                <div className="text-2xl font-bold text-yellow-600">{reportData.summary.over_stock_items}</div>
              </div>
            </CardBody>
          </Card>
        </div>
      )}
    </div>
  );
};

export default StockReport;

