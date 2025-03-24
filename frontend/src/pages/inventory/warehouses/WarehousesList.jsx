// src/pages/inventory/warehouses/WarehousesList.jsx
import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from 'react-query';
import { Plus, Edit, Trash, Eye, Search, Map } from 'lucide-react';
import { warehouseService } from '../../../services/inventoryService';
import PageHeader from '../../../components/common/PageHeader';
import Table from '../../../components/common/Table';
import { Card, CardBody } from '../../../components/common/Card';
import { FormGroup, Label, Input, Select, Button } from '../../../components/common/FormElements';
import Alert from '../../../components/common/Alert';
import Modal from '../../../components/common/Modal';

const WarehousesList = () => {
  const queryClient = useQueryClient();
  
  // State for search
  const [searchTerm, setSearchTerm] = useState('');
  const [filterActive, setFilterActive] = useState('');
  
  // State for delete modal
  const [deleteModalOpen, setDeleteModalOpen] = useState(false);
  const [warehouseToDelete, setWarehouseToDelete] = useState(null);
  
  // State for alert
  const [alert, setAlert] = useState(null);
  
  // Fetch warehouses
  const { 
    data: warehousesData, 
    isLoading, 
    isError
  } = useQuery(
    ['warehouses', searchTerm, filterActive],
    () => warehouseService.getAll({ 
      search: searchTerm,
      is_active: filterActive
    })
  );
  
  // Delete warehouse mutation
  const deleteMutation = useMutation(
    (id) => warehouseService.delete(id),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('warehouses');
        setAlert({
          type: 'success',
          title: 'Warehouse Deleted',
          message: 'The warehouse has been successfully deleted.'
        });
        setDeleteModalOpen(false);
      },
      onError: (err) => {
        setAlert({
          type: 'error',
          title: 'Delete Failed',
          message: err.response?.data?.message || 'Failed to delete the warehouse. It may have associated inventory.'
        });
        setDeleteModalOpen(false);
      }
    }
  );
  
  // Handle search
  const handleSearch = (e) => {
    setSearchTerm(e.target.value);
  };
  
  // Handle filter change
  const handleFilterChange = (e) => {
    setFilterActive(e.target.value);
  };
  
  // Open delete modal
  const openDeleteModal = (warehouse) => {
    setWarehouseToDelete(warehouse);
    setDeleteModalOpen(true);
  };
  
  // Handle delete
  const handleDelete = () => {
    if (warehouseToDelete) {
      deleteMutation.mutate(warehouseToDelete.warehouse_id);
    }
  };
  
  // Table columns
  const columns = [
    {
      key: 'code',
      label: 'Code',
      render: (warehouse) => (
        <div className="text-sm font-medium text-gray-900">{warehouse.code}</div>
      ),
    },
    {
      key: 'name',
      label: 'Name',
      render: (warehouse) => (
        <Link 
          to={`/inventory/warehouses/${warehouse.warehouse_id}`} 
          className="font-medium text-indigo-600 hover:text-indigo-900"
        >
          {warehouse.name}
        </Link>
      ),
    },
    {
      key: 'address',
      label: 'Address',
      render: (warehouse) => (
        <div className="text-sm text-gray-500 max-w-xs truncate">
          {warehouse.address || '-'}
        </div>
      ),
    },
    {
      key: 'zones',
      label: 'Zones',
      render: (warehouse) => (
        <div className="text-sm text-gray-900">
          {warehouse.zones_count || '0'} zones
        </div>
      ),
    },
    {
      key: 'status',
      label: 'Status',
      render: (warehouse) => (
        <span 
          className={`px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ${
            warehouse.is_active 
              ? 'bg-green-100 text-green-800' 
              : 'bg-red-100 text-red-800'
          }`}
        >
          {warehouse.is_active ? 'Active' : 'Inactive'}
        </span>
      ),
    },
    {
      key: 'actions',
      label: 'Actions',
      render: (warehouse) => (
        <div className="flex space-x-2">
          <Link
            to={`/inventory/warehouses/${warehouse.warehouse_id}`}
            className="text-blue-600 hover:text-blue-900"
            title="View"
          >
            <Eye className="h-5 w-5" />
          </Link>
          <Link
            to={`/inventory/warehouses/${warehouse.warehouse_id}/edit`}
            className="text-indigo-600 hover:text-indigo-900"
            title="Edit"
          >
            <Edit className="h-5 w-5" />
          </Link>
          <button
            onClick={() => openDeleteModal(warehouse)}
            className="text-red-600 hover:text-red-900"
            title="Delete"
          >
            <Trash className="h-5 w-5" />
          </button>
        </div>
      ),
    },
  ];
  
  return (
    <div>
      <PageHeader 
        title="Warehouses" 
        subtitle="Manage your inventory warehouses and locations"
        actionLabel="Add Warehouse"
        actionUrl="/inventory/warehouses/create"
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
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <FormGroup>
              <Label htmlFor="search">Search</Label>
              <div className="relative rounded-md shadow-sm">
                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <Search className="h-5 w-5 text-gray-400" />
                </div>
                <Input
                  id="search"
                  value={searchTerm}
                  onChange={handleSearch}
                  placeholder="Search by name or code"
                  className="pl-10"
                />
              </div>
            </FormGroup>
            
            <FormGroup>
              <Label htmlFor="status">Status</Label>
              <Select
                id="status"
                value={filterActive}
                onChange={handleFilterChange}
                options={[
                  { value: '', label: 'All Status' },
                  { value: 'true', label: 'Active' },
                  { value: 'false', label: 'Inactive' }
                ]}
              />
            </FormGroup>
            
            <div className="flex items-end">
              <Link to="/inventory/warehouses/create" className="w-full md:w-auto">
                <Button variant="primary" className="w-full md:w-auto">
                  <Plus className="h-4 w-4 mr-2" />
                  Add Warehouse
                </Button>
              </Link>
            </div>
          </div>
        </CardBody>
      </Card>
      
      <Table
        columns={columns}
        data={warehousesData?.data || []}
        isLoading={isLoading}
        emptyMessage="No warehouses found. Create your first warehouse to get started."
      />
      
      {/* Delete Confirmation Modal */}
      <Modal
        isOpen={deleteModalOpen}
        onClose={() => setDeleteModalOpen(false)}
        title="Delete Warehouse"
        size="sm"
      >
        <div className="py-3">
          <p className="text-gray-700">
            Are you sure you want to delete the warehouse <span className="font-medium">{warehouseToDelete?.name}</span>?
            This action cannot be undone.
          </p>
          
          <div className="mt-4 p-3 bg-yellow-50 border-l-4 border-yellow-400 text-yellow-700">
            <div className="flex">
              <svg className="h-5 w-5 text-yellow-400 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
              </svg>
              <p>
                <strong>Warning:</strong> Deleting this warehouse will also remove all its zones, locations, and may affect inventory data.
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

export default WarehousesList;