// src/pages/inventory/items/ItemBatches.jsx
import React, { useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from 'react-query';
import { 
  Edit, 
  Trash, 
  Plus, 
  Calendar, 
  AlertTriangle, 
  Clock,
  Filter,
  Download 
} from 'lucide-react';
import { itemService } from '../../../services/inventoryService';
import PageHeader from '../../../components/common/PageHeader';
import { Card, CardHeader, CardBody } from '../../../components/common/Card';
import { 
  FormGroup, 
  Label, 
  Input, 
  Select, 
  Button 
} from '../../../components/common/FormElements';
import Alert from '../../../components/common/Alert';
import Modal from '../../../components/common/Modal';
import Table from '../../../components/common/Table';

const ItemBatches = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const queryClient = useQueryClient();

  // State for filters
  const [filters, setFilters] = useState({
    search: '',
    status: '', // 'active', 'expired', 'expiring_soon'
    sort_by: 'expiry_date',
    sort_order: 'asc'
  });

  // State for batch modal
  const [batchModalOpen, setBatchModalOpen] = useState(false);
  const [batchFormData, setBatchFormData] = useState({
    batch_number: '',
    expiry_date: '',
    manufacturing_date: '',
    lot_number: '',
    quantity: '',
    unit_cost: '',
    notes: ''
  });
  const [batchModalMode, setBatchModalMode] = useState('create'); // 'create' or 'edit'
  const [selectedBatchId, setSelectedBatchId] = useState(null);

  // State for delete modal
  const [deleteModalOpen, setDeleteModalOpen] = useState(false);
  const [batchToDelete, setBatchToDelete] = useState(null);

  // State for alert
  const [formAlert, setFormAlert] = useState(null);

  // Fetch item details
  const { 
    data: itemData, 
    isLoading: itemLoading 
  } = useQuery(
    ['item', id],
    () => itemService.getById(id)
  );

  // Fetch item batches
  const { 
    data: batchesData, 
    isLoading: batchesLoading, 
    refetch: refetchBatches 
  } = useQuery(
    ['itemBatches', id, filters],
    () => itemService.getBatches(id, filters)
  );

  // Create batch mutation
  const createBatchMutation = useMutation(
    (data) => itemService.createBatch(id, data),
    {
      onSuccess: () => {
        queryClient.invalidateQueries(['itemBatches', id]);
        setBatchModalOpen(false);
        resetBatchForm();
        setFormAlert({
          type: 'success',
          title: 'Batch Created',
          message: 'New batch has been successfully created.'
        });
      },
      onError: (error) => {
        setFormAlert({
          type: 'error',
          title: 'Error Creating Batch',
          message: error.response?.data?.message || 'Failed to create batch. Please try again.'
        });
      }
    }
  );

  // Update batch mutation
  const updateBatchMutation = useMutation(
    ({ batchId, data }) => itemService.updateBatch(id, batchId, data),
    {
      onSuccess: () => {
        queryClient.invalidateQueries(['itemBatches', id]);
        setBatchModalOpen(false);
        resetBatchForm();
        setFormAlert({
          type: 'success',
          title: 'Batch Updated',
          message: 'Batch has been successfully updated.'
        });
      },
      onError: (error) => {
        setFormAlert({
          type: 'error',
          title: 'Error Updating Batch',
          message: error.response?.data?.message || 'Failed to update batch. Please try again.'
        });
      }
    }
  );

  // Delete batch mutation
  const deleteBatchMutation = useMutation(
    (batchId) => itemService.deleteBatch(id, batchId),
    {
      onSuccess: () => {
        queryClient.invalidateQueries(['itemBatches', id]);
        setDeleteModalOpen(false);
        setBatchToDelete(null);
        setFormAlert({
          type: 'success',
          title: 'Batch Deleted',
          message: 'Batch has been successfully deleted.'
        });
      },
      onError: (error) => {
        setFormAlert({
          type: 'error',
          title: 'Error Deleting Batch',
          message: error.response?.data?.message || 'Failed to delete batch. Please try again.'
        });
      }
    }
  );

  // Handle filter changes
  const handleFilterChange = (name, value) => {
    setFilters((prev) => ({
      ...prev,
      [name]: value
    }));
  };

  // Reset filters
  const handleResetFilters = () => {
    setFilters({
      search: '',
      status: '',
      sort_by: 'expiry_date',
      sort_order: 'asc'
    });
  };

  // Reset batch form
  const resetBatchForm = () => {
    setBatchFormData({
      batch_number: '',
      expiry_date: '',
      manufacturing_date: '',
      lot_number: '',
      quantity: '',
      unit_cost: '',
      notes: ''
    });
    setSelectedBatchId(null);
  };

  // Handle batch form changes
  const handleBatchChange = (e) => {
    const { name, value } = e.target;
    setBatchFormData((prev) => ({
      ...prev,
      [name]: value
    }));
  };

  // Open batch modal for create
  const openCreateBatchModal = () => {
    resetBatchForm();
    setBatchModalMode('create');
    setBatchModalOpen(true);
  };

  // Open batch modal for edit
  const openEditBatchModal = (batch) => {
    setBatchFormData({
      batch_number: batch.batch_number || '',
      expiry_date: batch.expiry_date ? batch.expiry_date.split('T')[0] : '',
      manufacturing_date: batch.manufacturing_date ? batch.manufacturing_date.split('T')[0] : '',
      lot_number: batch.lot_number || '',
      quantity: batch.quantity || '',
      unit_cost: batch.unit_cost || '',
      notes: batch.notes || ''
    });
    setSelectedBatchId(batch.batch_id);
    setBatchModalMode('edit');
    setBatchModalOpen(true);
  };

  // Open delete modal
  const openDeleteModal = (batch) => {
    setBatchToDelete(batch);
    setDeleteModalOpen(true);
  };

  // Handle batch form submission
  const handleBatchSubmit = (e) => {
    e.preventDefault();
    
    if (batchModalMode === 'create') {
      createBatchMutation.mutate(batchFormData);
    } else {
      updateBatchMutation.mutate({ batchId: selectedBatchId, data: batchFormData });
    }
  };

  // Handle delete batch
  const handleDeleteBatch = () => {
    if (batchToDelete) {
      deleteBatchMutation.mutate(batchToDelete.batch_id);
    }
  };

  // Export batches to CSV
  const handleExportBatches = () => {
    // In a real application, this would call an API endpoint to generate a CSV
    console.log('Exporting batches with filters:', filters);
    // Mock function for demo
    alert('Batches exported to CSV successfully.');
  };

  // Determine batch status
  const getBatchStatus = (batch) => {
    if (!batch.expiry_date) return { label: 'No Expiry', color: 'gray' };
    
    const now = new Date();
    const expiryDate = new Date(batch.expiry_date);
    
    if (expiryDate < now) {
      return { label: 'Expired', color: 'red' };
    }
    
    // Consider "expiring soon" if within 30 days
    const thirtyDaysFromNow = new Date();
    thirtyDaysFromNow.setDate(now.getDate() + 30);
    
    if (expiryDate <= thirtyDaysFromNow) {
      return { label: 'Expiring Soon', color: 'amber' };
    }
    
    return { label: 'Active', color: 'green' };
  };

  // Get batch columns
  const getColumns = () => [
    {
      key: 'batch_number',
      label: 'Batch Number',
      render: (batch) => (
        <div className="font-medium text-gray-900">{batch.batch_number}</div>
      ),
    },
    {
      key: 'lot_number',
      label: 'Lot Number',
      render: (batch) => (
        <div className="text-sm text-gray-500">{batch.lot_number || '-'}</div>
      ),
    },
    {
      key: 'quantity',
      label: 'Quantity',
      render: (batch) => (
        <div className="text-sm font-medium text-gray-900">
          {batch.quantity || 0} {itemData?.data?.unitOfMeasure?.symbol || ''}
        </div>
      ),
    },
    {
      key: 'manufacturing_date',
      label: 'Manufacturing Date',
      render: (batch) => (
        <div className="text-sm text-gray-500">
          {batch.manufacturing_date ? new Date(batch.manufacturing_date).toLocaleDateString() : '-'}
        </div>
      ),
    },
    {
      key: 'expiry_date',
      label: 'Expiry Date',
      render: (batch) => {
        const status = getBatchStatus(batch);
        return (
          <div>
            <div className="text-sm text-gray-900">
              {batch.expiry_date ? new Date(batch.expiry_date).toLocaleDateString() : '-'}
            </div>
            {status.label !== 'No Expiry' && (
              <span className={`px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-${status.color}-100 text-${status.color}-800`}>
                {status.label}
              </span>
            )}
          </div>
        );
      },
    },
    {
      key: 'unit_cost',
      label: 'Unit Cost',
      render: (batch) => (
        <div className="text-sm text-gray-900">
          {batch.unit_cost ? `$${parseFloat(batch.unit_cost).toFixed(2)}` : '-'}
        </div>
      ),
    },
    {
      key: 'actions',
      label: 'Actions',
      render: (batch) => (
        <div className="flex space-x-2">
          <button
            onClick={() => openEditBatchModal(batch)}
            className="text-indigo-600 hover:text-indigo-900"
            title="Edit"
          >
            <Edit className="h-5 w-5" />
          </button>
          <button
            onClick={() => openDeleteModal(batch)}
            className="text-red-600 hover:text-red-900"
            title="Delete"
          >
            <Trash className="h-5 w-5" />
          </button>
        </div>
      ),
    },
  ];

  // Get item info
  const item = itemData?.data;
  const batches = batchesData?.data || [];
  const isLoading = itemLoading || batchesLoading;

  return (
    <div>
      <PageHeader 
        title="Item Batches" 
        subtitle={itemLoading ? 'Loading...' : `${item?.name} (${item?.item_code})`}
        actionLabel="Back to Item"
        actionUrl={`/inventory/items/${id}`}
      />
      
      {formAlert && (
        <Alert
          type={formAlert.type}
          title={formAlert.title}
          message={formAlert.message}
          onClose={() => setFormAlert(null)}
          className="mb-6"
        />
      )}
      
      {/* Filters Card */}
      <Card className="mb-6">
        <CardBody>
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
            <FormGroup>
              <Label htmlFor="search">Search</Label>
              <Input
                id="search"
                value={filters.search}
                onChange={(e) => handleFilterChange('search', e.target.value)}
                placeholder="Search batch or lot number"
              />
            </FormGroup>
            
            <FormGroup>
              <Label htmlFor="status">Status</Label>
              <Select
                id="status"
                value={filters.status}
                onChange={(e) => handleFilterChange('status', e.target.value)}
                options={[
                  { value: 'active', label: 'Active' },
                  { value: 'expired', label: 'Expired' },
                  { value: 'expiring_soon', label: 'Expiring Soon' },
                ]}
                placeholder="All Status"
              />
            </FormGroup>
            
            <FormGroup>
              <Label htmlFor="sort_by">Sort By</Label>
              <Select
                id="sort_by"
                value={filters.sort_by}
                onChange={(e) => handleFilterChange('sort_by', e.target.value)}
                options={[
                  { value: 'batch_number', label: 'Batch Number' },
                  { value: 'expiry_date', label: 'Expiry Date' },
                  { value: 'manufacturing_date', label: 'Manufacturing Date' },
                  { value: 'quantity', label: 'Quantity' },
                ]}
              />
            </FormGroup>
            
            <FormGroup>
              <Label htmlFor="sort_order">Order</Label>
              <Select
                id="sort_order"
                value={filters.sort_order}
                onChange={(e) => handleFilterChange('sort_order', e.target.value)}
                options={[
                  { value: 'asc', label: 'Ascending' },
                  { value: 'desc', label: 'Descending' },
                ]}
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
              <Button
                variant="secondary"
                onClick={handleExportBatches}
              >
                <Download className="h-4 w-4 mr-2" />
                Export CSV
              </Button>
              <Button
                variant="primary"
                onClick={openCreateBatchModal}
              >
                <Plus className="h-4 w-4 mr-2" />
                Add Batch
              </Button>
            </div>
          </div>
        </CardBody>
      </Card>
      
      {/* Batches Table */}
      <Card>
        <CardHeader 
          title="Batch Inventory" 
          subtitle="Track batches, lot numbers, and expiry dates"
        />
        <CardBody>
          <Table
            columns={getColumns()}
            data={batches}
            isLoading={isLoading}
            emptyMessage="No batches found for this item."
          />
        </CardBody>
      </Card>
      
      {/* Batch Modal */}
      <Modal
        isOpen={batchModalOpen}
        onClose={() => setBatchModalOpen(false)}
        title={batchModalMode === 'create' ? 'Add New Batch' : 'Edit Batch'}
        size="md"
      >
        <form onSubmit={handleBatchSubmit}>
          <div className="space-y-4">
            <FormGroup>
              <Label htmlFor="batch_number" required>Batch Number</Label>
              <Input
                id="batch_number"
                name="batch_number"
                value={batchFormData.batch_number}
                onChange={handleBatchChange}
                required
                placeholder="e.g. B12345"
              />
            </FormGroup>
            
            <div className="grid grid-cols-2 gap-4">
              <FormGroup>
                <Label htmlFor="manufacturing_date">Manufacturing Date</Label>
                <Input
                  id="manufacturing_date"
                  name="manufacturing_date"
                  type="date"
                  value={batchFormData.manufacturing_date}
                  onChange={handleBatchChange}
                  max={new Date().toISOString().split('T')[0]}
                />
              </FormGroup>
              
              <FormGroup>
                <Label htmlFor="expiry_date">Expiry Date</Label>
                <Input
                  id="expiry_date"
                  name="expiry_date"
                  type="date"
                  value={batchFormData.expiry_date}
                  onChange={handleBatchChange}
                  min={new Date().toISOString().split('T')[0]}
                />
              </FormGroup>
            </div>
            
            <FormGroup>
              <Label htmlFor="lot_number">Lot Number</Label>
              <Input
                id="lot_number"
                name="lot_number"
                value={batchFormData.lot_number}
                onChange={handleBatchChange}
                placeholder="e.g. LOT-789"
              />
            </FormGroup>
            
            <div className="grid grid-cols-2 gap-4">
              <FormGroup>
                <Label htmlFor="quantity">Quantity</Label>
                <Input
                  id="quantity"
                  name="quantity"
                  type="number"
                  min="0"
                  step="0.01"
                  value={batchFormData.quantity}
                  onChange={handleBatchChange}
                  placeholder="Enter quantity"
                />
                {item?.unitOfMeasure && (
                  <span className="text-xs text-gray-500 mt-1">
                    in {item.unitOfMeasure.name} ({item.unitOfMeasure.symbol})
                  </span>
                )}
              </FormGroup>
              
              <FormGroup>
                <Label htmlFor="unit_cost">Unit Cost</Label>
                <div className="relative rounded-md shadow-sm">
                  <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <span className="text-gray-500 sm:text-sm">$</span>
                  </div>
                  <Input
                    id="unit_cost"
                    name="unit_cost"
                    type="number"
                    min="0"
                    step="0.01"
                    value={batchFormData.unit_cost}
                    onChange={handleBatchChange}
                    className="pl-7"
                    placeholder="0.00"
                  />
                </div>
              </FormGroup>
            </div>
            
            <FormGroup>
              <Label htmlFor="notes">Notes</Label>
              <Input
                id="notes"
                name="notes"
                value={batchFormData.notes}
                onChange={handleBatchChange}
                placeholder="Any additional information about this batch"
              />
            </FormGroup>
          </div>
          
          <div className="mt-6 flex justify-end space-x-3">
            <Button
              variant="light"
              onClick={() => setBatchModalOpen(false)}
            >
              Cancel
            </Button>
            <Button
              type="submit"
              variant="primary"
              isLoading={batchModalMode === 'create' ? createBatchMutation.isLoading : updateBatchMutation.isLoading}
            >
              {batchModalMode === 'create' ? 'Create Batch' : 'Update Batch'}
            </Button>
          </div>
        </form>
      </Modal>
      
      {/* Delete Confirmation Modal */}
      <Modal
        isOpen={deleteModalOpen}
        onClose={() => setDeleteModalOpen(false)}
        title="Delete Batch"
        size="sm"
      >
        <div className="py-3">
          <p className="text-gray-700">
            Are you sure you want to delete batch <span className="font-semibold">{batchToDelete?.batch_number}</span>? 
            This action cannot be undone.
          </p>
          
          {batchToDelete?.quantity > 0 && (
            <div className="mt-4 p-3 bg-yellow-50 border-l-4 border-yellow-400 text-yellow-700">
              <div className="flex">
                <AlertTriangle className="h-5 w-5 mr-2" />
                <p>
                  <strong>Warning:</strong> This batch has {batchToDelete.quantity} units in stock. 
                  Deleting it may affect inventory calculations.
                </p>
              </div>
            </div>
          )}
        </div>
        
        <div className="mt-4 flex justify-end space-x-3">
          <Button
            variant="light"
            onClick={() => setDeleteModalOpen(false)}
          >
            Cancel
          </Button>
          <Button
            variant="danger"
            onClick={handleDeleteBatch}
            isLoading={deleteBatchMutation.isLoading}
          >
            Delete Batch
          </Button>
        </div>
      </Modal>
    </div>
  );
};

export default ItemBatches;