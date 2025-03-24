// src/pages/inventory/transactions/StockTransactionsList.jsx (Continued)
import React, { useState, useEffect } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { useQuery } from 'react-query';
import { Search, Filter, Plus, TrendingUp, TrendingDown, Calendar, Package, Warehouse } from 'lucide-react';
import { stockTransactionService, itemService, warehouseService } from '../../../services/inventoryService';
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

const StockTransactionsList = () => {
  const location = useLocation();
  const navigate = useNavigate();
  const queryParams = new URLSearchParams(location.search);
  
  // State for filters
  const [filters, setFilters] = useState({
    search: queryParams.get('search') || '',
    item_id: queryParams.get('item_id') || '',
    warehouse_id: queryParams.get('warehouse_id') || '',
    transaction_type: queryParams.get('transaction_type') || '',
    date_from: queryParams.get('date_from') || '',
    date_to: queryParams.get('date_to') || ''
  });
  
  // State for pagination
  const [pagination, setPagination] = useState({
    page: parseInt(queryParams.get('page') || '1', 10),
    perPage: parseInt(queryParams.get('perPage') || '10', 10),
  });
  
  // Update URL with filters and pagination
  useEffect(() => {
    const params = new URLSearchParams();
    Object.entries(filters).forEach(([key, value]) => {
      if (value) params.append(key, value);
    });
    params.append('page', pagination.page.toString());
    params.append('perPage', pagination.perPage.toString());
    
    navigate({
      pathname: location.pathname,
      search: params.toString(),
    }, { replace: true });
  }, [filters, pagination, navigate, location.pathname]);
  
  // Fetch transactions with filters and pagination
  const { 
    data: transactionsData, 
    isLoading: transactionsLoading, 
    isError: transactionsError
  } = useQuery(
    ['transactions', filters, pagination],
    () => stockTransactionService.getAll({
      search: filters.search,
      item_id: filters.item_id,
      warehouse_id: filters.warehouse_id,
      transaction_type: filters.transaction_type,
      date_from: filters.date_from,
      date_to: filters.date_to,
      page: pagination.page,
      per_page: pagination.perPage,
    }),
    {
      keepPreviousData: true,
    }
  );
  
  // Fetch items for filter dropdown
  const { 
    data: itemsData
  } = useQuery('items-dropdown', () => itemService.getAll({ per_page: 100 }));
  
  // Fetch warehouses for filter dropdown
  const { 
    data: warehousesData
  } = useQuery('warehouses-dropdown', () => warehouseService.getAll());
  
  // Handle filter changes
  const handleFilterChange = (name, value) => {
    setFilters((prev) => ({
      ...prev,
      [name]: value,
    }));
    // Reset to first page when filters change
    setPagination((prev) => ({
      ...prev,
      page: 1,
    }));
  };
  
  // Handle page change
  const handlePageChange = (newPage) => {
    setPagination((prev) => ({
      ...prev,
      page: newPage,
    }));
  };
  
  // Handle per page change
  const handlePerPageChange = (e) => {
    const newPerPage = parseInt(e.target.value, 10);
    setPagination({
      page: 1, // Reset to first page
      perPage: newPerPage,
    });
  };
  
  // Handle reset filters
  const handleResetFilters = () => {
    setFilters({
      search: '',
      item_id: '',
      warehouse_id: '',
      transaction_type: '',
      date_from: '',
      date_to: ''
    });
    setPagination({
      page: 1,
      perPage: 10,
    });
  };
  
  // Table columns definition
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
        title="Stock Transactions" 
        subtitle="Track all stock movement activities"
        actionLabel="Add Transaction"
        actionUrl="/inventory/transactions/create"
      />
      
      {/* Filters */}
      <Card className="mb-6">
        <CardBody>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div className="space-y-4">
              <FormGroup>
                <Label htmlFor="search">Search</Label>
                <div className="relative rounded-md shadow-sm">
                  <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <Search className="h-5 w-5 text-gray-400" />
                  </div>
                  <Input
                    id="search"
                    value={filters.search}
                    onChange={(e) => handleFilterChange('search', e.target.value)}
                    placeholder="Search by reference or item"
                    className="pl-10"
                  />
                </div>
              </FormGroup>
              
              <FormGroup>
                <Label htmlFor="item_id">Item</Label>
                <Select
                  id="item_id"
                  value={filters.item_id}
                  onChange={(e) => handleFilterChange('item_id', e.target.value)}
                  options={
                    itemsData?.data?.map((item) => ({
                      value: item.item_id,
                      label: `${item.name} (${item.item_code})`,
                    })) || []
                  }
                  placeholder="All Items"
                />
              </FormGroup>
            </div>
            
            <div className="space-y-4">
              <FormGroup>
                <Label htmlFor="warehouse_id">Warehouse</Label>
                <Select
                  id="warehouse_id"
                  value={filters.warehouse_id}
                  onChange={(e) => handleFilterChange('warehouse_id', e.target.value)}
                  options={
                    warehousesData?.data?.map((warehouse) => ({
                      value: warehouse.warehouse_id,
                      label: warehouse.name,
                    })) || []
                  }
                  placeholder="All Warehouses"
                />
              </FormGroup>
              
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
            </div>
            
            <div className="space-y-4">
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
              
              <div className="flex justify-end">
                <Button
                  variant="secondary"
                  onClick={handleResetFilters}
                >
                  <Filter className="h-4 w-4 mr-2" />
                  Reset Filters
                </Button>
              </div>
            </div>
          </div>
        </CardBody>
      </Card>
      
      {/* Transactions Table */}
      {transactionsError && (
        <Alert
          type="error"
          title="Error loading transactions"
          message="There was an error loading the transactions. Please try again later."
          className="mb-6"
        />
      )}
      
      <Table
        columns={columns}
        data={transactionsData?.data || []}
        isLoading={transactionsLoading}
        pagination={transactionsData?.meta}
        onPageChange={handlePageChange}
        emptyMessage="No transactions found matching your filters."
      />
      
      {/* Items per page selector */}
      <div className="mt-4 flex justify-end items-center">
        <span className="mr-2 text-sm text-gray-600">Items per page:</span>
        <select
          value={pagination.perPage}
          onChange={handlePerPageChange}
          className="form-select border border-gray-300 rounded-md shadow-sm text-sm"
        >
          <option value="10">10</option>
          <option value="25">25</option>
          <option value="50">50</option>
          <option value="100">100</option>
        </select>
      </div>
    </div>
  );
};

export default StockTransactionsList;

