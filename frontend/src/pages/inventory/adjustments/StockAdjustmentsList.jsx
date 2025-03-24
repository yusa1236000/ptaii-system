// src/pages/inventory/adjustments/StockAdjustmentsList.jsx
import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from 'react-query';
import { 
  Plus, 
  Edit, 
  Trash, 
  Eye, 
  Search, 
  Filter,
  CheckCircle, 
  XCircle, 
  Clock,
  Calendar
} from 'lucide-react';
import { stockAdjustmentService } from '../../../services/inventoryService';
import PageHeader from '../../../components/common/PageHeader';
import Table from '../../../components/common/Table';
import { Card, CardBody } from '../../../components/common/Card';
import { FormGroup, Label, Input, Select, Button } from '../../../components/common/FormElements';
import Alert from '../../../components/common/Alert';
import Modal from '../../../components/common/Modal';

const StockAdjustmentsList = () => {
  const queryClient = useQueryClient();
  
  // State for filters
  const [filters, setFilters] = useState({
    search: '',
    status: '',
    date_from: '',
    date_to: ''
  });
  
  // State for pagination
  const [pagination, setPagination] = useState({
    page: 1,
    perPage: 10
  });
  
  // State for delete modal
  const [deleteModalOpen, setDeleteModalOpen] = useState(false);
  const [adjustmentToDelete, setAdjustmentToDelete] = useState(null);
  
  // State for alert
  const [alert, setAlert] = useState(null);
  
  // Fetch stock adjustments
  const { 
    data: adjustmentsData, 
    isLoading, 
    isError
  } = useQuery(
    ['stockAdjustments', filters, pagination],
    () => stockAdjustmentService.getAll({ 
      ...filters,
      page: pagination.page,
      per_page: pagination.perPage
    })
  );
  
  // Delete adjustment mutation
  const deleteMutation = useMutation(
    (id) => stockAdjustmentService.delete(id),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('stockAdjustments');
        setAlert({
          type: 'success',
          title: 'Adjustment Deleted',
          message: 'The stock adjustment has been successfully deleted.'
        });
        setDeleteModalOpen(false);
      },
      onError: (err) => {
        setAlert({
          type: 'error',
          title: 'Delete Failed',
          message: err.response?.data?.message || 'Failed to delete the adjustment. It may have already been approved.'
        });
        setDeleteModalOpen(false);
      }
    }
  );
  
  // Approve adjustment mutation
  const approveMutation = useMutation(
    (id) => stockAdjustmentService.approve(id),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('stockAdjustments');
        setAlert({
          type: 'success',
          title: 'Adjustment Approved',
          message: 'The stock adjustment has been successfully approved.'
        });
      },
      onError: (err) => {
        setAlert({
          type: 'error',
          title: 'Approve Failed',
          message: err.response?.data?.message || 'Failed to approve the adjustment.'
        });
      }
    }
  );
  
  // Cancel adjustment mutation
  const cancelMutation = useMutation(
    (id) => stockAdjustmentService.cancel(id),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('stockAdjustments');
        setAlert({
          type: 'success',
          title: 'Adjustment Cancelled',
          message: 'The stock adjustment has been cancelled.'
        });
      },
      onError: (err) => {
        setAlert({
          type: 'error',
          title: 'Cancel Failed',
          message: err.response?.data?.message || 'Failed to cancel the adjustment.'
        });
      }
    }
  );
  
  // Handle filter changes
  const handleFilterChange = (name, value) => {
    setFilters(prev => ({
      ...prev,
      [name]: value
    }));
    
    // Reset to first page when filters change
    setPagination(prev => ({
      ...prev,
      page: 1
    }));
  };
  
  // Handle page change
  const handlePageChange = (page) => {
    setPagination(prev => ({
      ...prev,
      page
    }));
  };
  
  // Handle per page change
  const handlePerPageChange = (e) => {
    const perPage = parseInt(e.target.value);
    setPagination({
      page: 1,
      perPage
    });
  };
  
  // Reset filters
  const handleResetFilters = () => {
    setFilters({
      search: '',
      status: '',
      date_from: '',
      date_to: ''
    });
  };
  
  // Open delete modal
  const openDeleteModal = (adjustment) => {
    setAdjustmentToDelete(adjustment);
    setDeleteModalOpen(true);
  };
  
  // Handle delete
  const handleDelete = () => {
    if (adjustmentToDelete) {
      deleteMutation.mutate(adjustmentToDelete.adjustment_id);
    }
  };
  
  // Handle approve
  const handleApprove = (id) => {
    approveMutation.mutate(id);
  };
  
  // Handle cancel
  const handleCancel = (id) => {
    cancelMutation.mutate(id);
  };
  
  // Table columns
  const columns = [
    {
      key: 'adjustment_date',
      label: 'Date',
      render: (adjustment) => (
        <div className="text-sm text-gray-900 whitespace-nowrap">
          {new Date(adjustment.adjustment_date).toLocaleDateString()}
        </div>
      ),
    },
    {
      key: 'reference',
      label: 'Reference',
      render: (adjustment) => (
        <Link 
          to={`/inventory/adjustments/${adjustment.adjustment_id}`} 
          className="font-medium text-indigo-600 hover:text-indigo-900"
        >
          {adjustment.reference_document || `ADJ-${adjustment.adjustment_id}`}
        </Link>
      ),
    },
    {
      key: 'items_count',
      label: 'Items',
      render: (adjustment) => (
        <div className="text-sm text-gray-900">
          {adjustment.items_count || 0} items
        </div>
      ),
    },
    {
      key: 'reason',
      label: 'Reason',
      render: (adjustment) => (
        <div className="text-sm text-gray-500 max-w-xs truncate">
          {adjustment.adjustment_reason || '-'}
        </div>
      ),
    },
    {
      key: 'status',
      label: 'Status',
      render: (adjustment) => {
        let statusClass = 'bg-yellow-100 text-yellow-800';
        let StatusIcon = Clock;
        
        if (adjustment.status === 'Approved') {
          statusClass = 'bg-green-100 text-green-800';
          StatusIcon = CheckCircle;
        } else if (adjustment.status === 'Cancelled') {
          statusClass = 'bg-red-100 text-red-800';
          StatusIcon = XCircle;
        }
        
        return (
          <span 
            className={`px-2 py-1 inline-flex items-center text-xs leading-5 font-semibold rounded-full ${statusClass}`}
          >
            <StatusIcon className="h-3 w-3 mr-1" />
            {adjustment.status}
          </span>
        );
      },
    },
    {
      key: 'actions',
      label: 'Actions',
      render: (adjustment) => (
        <div className="flex space-x-2">
          <Link
            to={`/inventory/adjustments/${adjustment.adjustment_id}`}
            className="text-blue-600 hover:text-blue-900"
            title="View"
          >
            <Eye className="h-5 w-5" />
          </Link>
          
          {adjustment.status === 'Draft' && (
            <>
              <button
                onClick={() => handleApprove(adjustment.adjustment_id)}
                className="text-green-600 hover:text-green-900"
                title="Approve"
                disabled={approveMutation.isLoading}
              >
                <CheckCircle className="h-5 w-5" />
              </button>
              
              <button
                onClick={() => handleCancel(adjustment.adjustment_id)}
                className="text-gray-600 hover:text-gray-900"
                title="Cancel"
                disabled={cancelMutation.isLoading}
              >
                <XCircle className="h-5 w-5" />
              </button>
              
              <button
                onClick={() => openDeleteModal(adjustment)}
                className="text-red-600 hover:text-red-900"
                title="Delete"
                disabled={deleteMutation.isLoading}
              >
                <Trash className="h-5 w-5" />
              </button>
            </>
          )}
        </div>
      ),
    },
  ];
  
  return (
    <div>
      <PageHeader 
        title="Stock Adjustments" 
        subtitle="Manage inventory count adjustments"
        actionLabel="Create Adjustment"
        actionUrl="/inventory/adjustments/create"
      />
      
      {alert && (
        <Alert
          type={alert.type}
          title={alert.title}
          message={alert.message}
          onClose={() => setAlert(null)}
          className="mb-6"
        />
      )}
      
      <Card className="mb-6">
        <CardBody>
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
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
                  placeholder="Search by reference"
                  className="pl-10"
                />
              </div>
            </FormGroup>
            
            <FormGroup>
              <Label htmlFor="status">Status</Label>
              <Select
                id="status"
                value={filters.status}
                onChange={(e) => handleFilterChange('status', e.target.value)}
                options={[
                  { value: '', label: 'All Status' },
                  { value: 'Draft', label: 'Draft' },
                  { value: 'Approved', label: 'Approved' },
                  { value: 'Cancelled', label: 'Cancelled' }
                ]}
              />
            </FormGroup>
            
            <FormGroup>
              <Label htmlFor="date_from">From Date</Label>
              <Input
                id="date_from"
                type="date"
                value={filters.date_from}
                onChange={(e) => handleFilterChange('date_from', e.target.value)}
                max={filters.date_to || undefined}
              />
            </FormGroup>
            
            <FormGroup>
              <Label htmlFor="date_to">To Date</Label>
              <Input
                id="date_to"
                type="date"
                value={filters.date_to}
                onChange={(e) => handleFilterChange('date_to', e.target.value)}
                min={filters.date_from || undefined}
              />
            </FormGroup>
            
            <div className="md:col-span-4 flex justify-end space-x-2">
              <Button
                variant="secondary"
                onClick={handleResetFilters}
              >
                <Filter className="h-4 w-4 mr-2" />
                Reset Filters
              </Button>
              
              <Link to="/inventory/adjustments/create">
                <Button variant="primary">
                  <Plus className="h-4 w-4 mr-2" />
                  Create Adjustment
                </Button>
              </Link>
            </div>
          </div>
        </CardBody>
      </Card>
      
      <Table
        columns={columns}
        data={adjustmentsData?.data || []}
        isLoading={isLoading}
        pagination={adjustmentsData?.meta}
        onPageChange={handlePageChange}
        emptyMessage="No stock adjustments found."
      />
      
      {/* Items per page selector */}
      <div className="mt-4 flex justify-end items-center">
        <span className="mr-2 text-sm text-gray-600">Items per page:</span>
        <select
          value={pagination.perPage}
          onChange={handlePerPageChange}
          className="form-select border border-gray-300 rounded-md shadow-sm text-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
        >
          <option value="10">10</option>
          <option value="25">25</option>
          <option value="50">50</option>
          <option value="100">100</option>
        </select>
      </div>
      
      {/* Delete Confirmation Modal */}
      <Modal
        isOpen={deleteModalOpen}
        onClose={() => setDeleteModalOpen(false)}
        title="Delete Stock Adjustment"
        size="sm"
      >
        <div className="py-3">
          <p className="text-gray-700">
            Are you sure you want to delete this stock adjustment?
            This action cannot be undone.
          </p>
          
          <div className="mt-4 p-3 bg-yellow-50 border-l-4 border-yellow-400 text-yellow-700">
            <div className="flex">
              <svg className="h-5 w-5 text-yellow-400 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
              </svg>
              <p>
                <strong>Warning:</strong> This will permanently remove the adjustment record from the system.
              </p>
            </div>
          </div>
        </div>
        
        <div className="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
          <Button
            variant="danger"
            onClick={handleDelete}
            isLoading={deleteMutation.isLoading}
            className="w-full sm:ml-3 sm:w-auto"
          >
            Delete
          </Button>
          <Button
            variant="light"
            onClick={() => setDeleteModalOpen(false)}
            className="mt-3 w-full sm:mt-0 sm:w-auto"
          >
            Cancel
          </Button>
        </div>
      </Modal>
    </div>
  );
};

export default StockAdjustmentsList;