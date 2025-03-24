// src/pages/inventory/uom/UOMList.jsx
import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from 'react-query';
import { Plus, Edit, Trash, Search } from 'lucide-react';
import { unitOfMeasureService } from '../../../services/inventoryService';
import PageHeader from '../../../components/common/PageHeader';
import Table from '../../../components/common/Table';
import { Card, CardBody } from '../../../components/common/Card';
import { FormGroup, Label, Input, Button } from '../../../components/common/FormElements';
import Alert from '../../../components/common/Alert';
import Modal from '../../../components/common/Modal';

const UOMList = () => {
  const queryClient = useQueryClient();
  
  // State for search
  const [searchTerm, setSearchTerm] = useState('');
  
  // State for delete modal
  const [deleteModalOpen, setDeleteModalOpen] = useState(false);
  const [uomToDelete, setUomToDelete] = useState(null);
  
  // State for alert
  const [alert, setAlert] = useState(null);
  
  // Fetch units of measure
  const { 
    data: uomData, 
    isLoading, 
    isError, 
    error 
  } = useQuery(
    ['unitOfMeasures', searchTerm],
    () => unitOfMeasureService.getAll({ search: searchTerm })
  );
  
  // Delete unit of measure mutation
  const deleteMutation = useMutation(
    (id) => unitOfMeasureService.delete(id),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('unitOfMeasures');
        setAlert({
          type: 'success',
          title: 'UOM Deleted',
          message: 'The unit of measure has been successfully deleted.'
        });
        setDeleteModalOpen(false);
      },
      onError: (err) => {
        setAlert({
          type: 'error',
          title: 'Delete Failed',
          message: err.response?.data?.message || 'Failed to delete the unit of measure. It may be in use by items.'
        });
        setDeleteModalOpen(false);
      }
    }
  );
  
  // Handle search
  const handleSearch = (e) => {
    setSearchTerm(e.target.value);
  };
  
  // Open delete modal
  const openDeleteModal = (uom) => {
    setUomToDelete(uom);
    setDeleteModalOpen(true);
  };
  
  // Handle delete
  const handleDelete = () => {
    if (uomToDelete) {
      deleteMutation.mutate(uomToDelete.uom_id);
    }
  };
  
  // Table columns
  const columns = [
    {
      key: 'name',
      label: 'Name',
      render: (uom) => (
        <div className="font-medium text-gray-900">{uom.name}</div>
      ),
    },
    {
      key: 'symbol',
      label: 'Symbol',
      render: (uom) => (
        <div className="text-sm font-medium bg-gray-100 text-gray-800 px-2 py-1 rounded inline-block">
          {uom.symbol}
        </div>
      ),
    },
    {
      key: 'description',
      label: 'Description',
      render: (uom) => (
        <div className="text-sm text-gray-500">
          {uom.description || '-'}
        </div>
      ),
    },
    {
      key: 'conversions',
      label: 'Conversions',
      render: (uom) => (
        <div className="text-sm text-gray-500">
          {uom.conversions && uom.conversions.length > 0 ? (
            <div className="space-y-1">
              {uom.conversions.map((conversion, index) => (
                <div key={index}>
                  1 {uom.symbol} = {conversion.conversion_factor} {conversion.to_uom?.symbol || '?'}
                </div>
              ))}
            </div>
          ) : (
            'No conversions'
          )}
        </div>
      ),
    },
    {
      key: 'actions',
      label: 'Actions',
      render: (uom) => (
        <div className="flex space-x-2">
          <Link
            to={`/inventory/uom/${uom.uom_id}/edit`}
            className="text-indigo-600 hover:text-indigo-900"
          >
            <Edit className="h-5 w-5" />
          </Link>
          <button
            onClick={() => openDeleteModal(uom)}
            className="text-red-600 hover:text-red-900"
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
        title="Units of Measure" 
        subtitle="Manage measurement units for inventory items"
        actionLabel="Add UOM"
        actionUrl="/inventory/uom/create"
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
          <div className="flex justify-between items-end">
            <FormGroup className="w-full max-w-xs">
              <Label htmlFor="search">Search</Label>
              <div className="relative rounded-md shadow-sm">
                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <Search className="h-5 w-5 text-gray-400" />
                </div>
                <Input
                  id="search"
                  value={searchTerm}
                  onChange={handleSearch}
                  placeholder="Search by name or symbol"
                  className="pl-10"
                />
              </div>
            </FormGroup>
            
            <Link to="/inventory/uom/create">
              <Button variant="primary">
                <Plus className="h-4 w-4 mr-2" />
                Add Unit of Measure
              </Button>
            </Link>
          </div>
        </CardBody>
      </Card>
      
      <Table
        columns={columns}
        data={uomData?.data || []}
        isLoading={isLoading}
        emptyMessage="No units of measure found. Create your first UOM to get started."
      />
      
      {/* Delete Confirmation Modal */}
      <Modal
        isOpen={deleteModalOpen}
        onClose={() => setDeleteModalOpen(false)}
        title="Delete Unit of Measure"
        size="sm"
      >
        <div className="py-3">
          <p className="text-gray-700">
            Are you sure you want to delete the unit of measure <span className="font-medium">{uomToDelete?.name} ({uomToDelete?.symbol})</span>?
            This action cannot be undone.
          </p>
          
          <div className="mt-4 p-3 bg-yellow-50 border-l-4 border-yellow-400 text-yellow-700">
            <div className="flex">
              <svg className="h-5 w-5 text-yellow-400 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
              </svg>
              <p>
                <strong>Warning:</strong> If this unit of measure is used by any items, those items will be affected.
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

export default UOMList;