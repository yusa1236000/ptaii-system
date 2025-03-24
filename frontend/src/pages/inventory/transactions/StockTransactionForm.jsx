// src/pages/inventory/transactions/StockTransactionForm.jsx
import React, { useState, useEffect } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import { useMutation, useQuery, useQueryClient } from 'react-query';
import { Save, ArrowLeft, Plus, Minus, Trash } from 'lucide-react';
import { stockTransactionService, itemService, warehouseService } from '../../../services/inventoryService';
import PageHeader from '../../../components/common/PageHeader';
import { Card, CardBody, CardFooter } from '../../../components/common/Card';
import { 
  FormGroup, 
  Label, 
  Input, 
  Select, 
  Textarea, 
  Button 
} from '../../../components/common/FormElements';
import Alert from '../../../components/common/Alert';

const StockTransactionForm = () => {
  const navigate = useNavigate();
  const location = useLocation();
  const queryClient = useQueryClient();
  
  // Check if an item was pre-selected (from item detail page)
  const preSelectedItem = location.state?.selectedItem;
  
  // Form state
  const [formData, setFormData] = useState({
    item_id: preSelectedItem?.item_id || '',
    warehouse_id: '',
    location_id: '',
    transaction_type: '',
    quantity: '',
    transaction_date: new Date().toISOString().substr(0, 10),
    reference_document: '',
    reference_number: '',
    batch_id: '',
    notes: ''
  });
  
  // State for selected item details
  const [selectedItem, setSelectedItem] = useState(preSelectedItem || null);
  
  // State for selected warehouse details (for locations dropdown)
  const [selectedWarehouse, setSelectedWarehouse] = useState(null);
  
  // Form errors state
  const [errors, setErrors] = useState({});
  
  // Form submission status
  const [formAlert, setFormAlert] = useState(null);
  
  // Fetch items for dropdown
  const { 
    data: itemsData, 
    isLoading: itemsLoading 
  } = useQuery('items-dropdown', () => itemService.getAll({ per_page: 100 }));
  
  // Fetch warehouses for dropdown
  const { 
    data: warehousesData, 
    isLoading: warehousesLoading 
  } = useQuery('warehouses-dropdown', () => warehouseService.getAll());
  
  // Fetch item details when item is selected
  const { 
    data: itemDetailsData, 
    isLoading: itemDetailsLoading 
  } = useQuery(
    ['item-details', formData.item_id],
    () => itemService.getById(formData.item_id),
    {
      enabled: !!formData.item_id,
      onSuccess: (data) => {
        setSelectedItem(data.data);
      }
    }
  );
  
  // Fetch warehouse details (including locations) when warehouse is selected
  const { 
    data: warehouseDetailsData, 
    isLoading: warehouseDetailsLoading 
  } = useQuery(
    ['warehouse-details', formData.warehouse_id],
    () => warehouseService.getById(formData.warehouse_id),
    {
      enabled: !!formData.warehouse_id,
      onSuccess: (data) => {
        setSelectedWarehouse(data.data);
      }
    }
  );
  
  // Fetch batches for selected item
  const { 
    data: batchesData, 
    isLoading: batchesLoading 
  } = useQuery(
    ['item-batches', formData.item_id],
    () => itemService.getBatches(formData.item_id),
    {
      enabled: !!formData.item_id
    }
  );
  
  // Create transaction mutation
  const createMutation = useMutation(
    (data) => stockTransactionService.create(data),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('transactions');
        queryClient.invalidateQueries(['item-details', formData.item_id]);
        queryClient.invalidateQueries('items');
        queryClient.invalidateQueries('stockStatus');
        
        setFormAlert({
          type: 'success',
          title: 'Success!',
          message: 'Stock transaction created successfully.'
        });
        
        setTimeout(() => {
          navigate('/inventory/transactions');
        }, 1500);
      },
      onError: (error) => {
        if (error.response?.data?.errors) {
          setErrors(error.response.data.errors);
        } else {
          setFormAlert({
            type: 'error',
            title: 'Error!',
            message: error.response?.data?.message || 'Failed to create transaction. Please try again.'
          });
        }
      }
    }
  );
  
  // Handle form input changes
  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({
      ...prev,
      [name]: value
    }));
    
    // Clear error for this field
    if (errors[name]) {
      setErrors((prev) => ({
        ...prev,
        [name]: undefined
      }));
    }
    
    // Clear location_id when warehouse changes
    if (name === 'warehouse_id' && value !== formData.warehouse_id) {
      setFormData((prev) => ({
        ...prev,
        location_id: ''
      }));
    }
  };
  
  // Handle form submission
  const handleSubmit = (e) => {
    e.preventDefault();
    setErrors({});
    setFormAlert(null);
    
    // Validation
    const validationErrors = {};
    if (!formData.item_id) validationErrors.item_id = 'Item is required';
    if (!formData.warehouse_id) validationErrors.warehouse_id = 'Warehouse is required';
    if (!formData.transaction_type) validationErrors.transaction_type = 'Transaction type is required';
    if (!formData.quantity || parseFloat(formData.quantity) <= 0) {
      validationErrors.quantity = 'Quantity must be greater than 0';
    }
    if (!formData.transaction_date) validationErrors.transaction_date = 'Transaction date is required';
    
    if (Object.keys(validationErrors).length > 0) {
      setErrors(validationErrors);
      return;
    }
    
    createMutation.mutate(formData);
  };
  
  // Get locations options for the selected warehouse
  const getLocationOptions = () => {
    if (!selectedWarehouse) return [];
    
    const locations = [];
    selectedWarehouse.zones?.forEach(zone => {
      zone.locations?.forEach(location => {
        locations.push({
          value: location.location_id,
          label: `${zone.name} - ${location.code}`
        });
      });
    });
    
    return locations;
  };
  
  // Get batch options for the selected item
  const getBatchOptions = () => {
    if (!batchesData?.data) return [];
    
    return batchesData.data.map(batch => ({
      value: batch.batch_id,
      label: `${batch.batch_number} ${batch.expiry_date ? `(Exp: ${new Date(batch.expiry_date).toLocaleDateString()})` : ''}`
    }));
  };
  
  const isLoading = itemsLoading || warehousesLoading;
  const isSubmitting = createMutation.isLoading;
  
  return (
    <div>
      <PageHeader 
        title="Add Stock Transaction" 
        subtitle="Record movement of inventory items"
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
      
      <form onSubmit={handleSubmit}>
        <Card className="mb-6">
          <CardBody>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <FormGroup>
                <Label htmlFor="item_id" required>Item</Label>
                <Select
                  id="item_id"
                  name="item_id"
                  value={formData.item_id}
                  onChange={handleChange}
                  error={errors.item_id}
                  options={
                    itemsLoading
                      ? []
                      : itemsData?.data.map((item) => ({
                          value: item.item_id,
                          label: `${item.name} (${item.item_code})`,
                        })) || []
                  }
                  placeholder="Select an item"
                  disabled={itemsLoading || !!preSelectedItem}
                  required
                />
                {selectedItem && (
                  <div className="mt-2 p-2 bg-gray-50 rounded-md text-sm">
                    <div className="font-medium">Current Stock: {selectedItem.current_stock} {selectedItem.unitOfMeasure?.symbol || ''}</div>
                    {selectedItem.current_stock <= selectedItem.minimum_stock && (
                      <div className="text-red-600 text-xs mt-1">
                        Low stock! Minimum: {selectedItem.minimum_stock} {selectedItem.unitOfMeasure?.symbol || ''}
                      </div>
                    )}
                  </div>
                )}
              </FormGroup>
              
              <FormGroup>
                <Label htmlFor="transaction_type" required>Transaction Type</Label>
                <Select
                  id="transaction_type"
                  name="transaction_type"
                  value={formData.transaction_type}
                  onChange={handleChange}
                  error={errors.transaction_type}
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
                  placeholder="Select a transaction type"
                  required
                />
              </FormGroup>
              
              <FormGroup>
                <Label htmlFor="quantity" required>Quantity</Label>
                <div className="relative rounded-md shadow-sm">
                  <Input
                    id="quantity"
                    name="quantity"
                    type="number"
                    min="0.01"
                    step="0.01"
                    value={formData.quantity}
                    onChange={handleChange}
                    error={errors.quantity}
                    required
                  />
                  {selectedItem?.unitOfMeasure && (
                    <div className="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                      <span className="text-gray-500 sm:text-sm">
                        {selectedItem.unitOfMeasure.symbol}
                      </span>
                    </div>
                  )}
                </div>
              </FormGroup>
              
              <FormGroup>
                <Label htmlFor="transaction_date" required>Transaction Date</Label>
                <Input
                  id="transaction_date"
                  name="transaction_date"
                  type="date"
                  value={formData.transaction_date}
                  onChange={handleChange}
                  error={errors.transaction_date}
                  required
                  max={new Date().toISOString().substr(0, 10)}
                />
              </FormGroup>
              
              <FormGroup>
                <Label htmlFor="warehouse_id" required>Warehouse</Label>
                <Select
                  id="warehouse_id"
                  name="warehouse_id"
                  value={formData.warehouse_id}
                  onChange={handleChange}
                  error={errors.warehouse_id}
                  options={
                    warehousesLoading
                      ? []
                      : warehousesData?.data.map((warehouse) => ({
                          value: warehouse.warehouse_id,
                          label: warehouse.name,
                        })) || []
                  }
                  placeholder="Select a warehouse"
                  disabled={warehousesLoading}
                  required
                />
              </FormGroup>
              
              <FormGroup>
                <Label htmlFor="location_id">Location</Label>
                <Select
                  id="location_id"
                  name="location_id"
                  value={formData.location_id}
                  onChange={handleChange}
                  error={errors.location_id}
                  options={getLocationOptions()}
                  placeholder={formData.warehouse_id ? "Select a location" : "Select a warehouse first"}
                  disabled={!formData.warehouse_id || warehouseDetailsLoading}
                />
              </FormGroup>
              
              <FormGroup>
                <Label htmlFor="reference_document">Reference Document</Label>
                <Input
                  id="reference_document"
                  name="reference_document"
                  value={formData.reference_document}
                  onChange={handleChange}
                  error={errors.reference_document}
                  placeholder="e.g. Purchase Order, Sales Order"
                />
              </FormGroup>
              
              <FormGroup>
                <Label htmlFor="reference_number">Reference Number</Label>
                <Input
                  id="reference_number"
                  name="reference_number"
                  value={formData.reference_number}
                  onChange={handleChange}
                  error={errors.reference_number}
                  placeholder="e.g. PO-12345"
                />
              </FormGroup>
              
              <FormGroup>
                <Label htmlFor="batch_id">Batch</Label>
                <Select
                  id="batch_id"
                  name="batch_id"
                  value={formData.batch_id}
                  onChange={handleChange}
                  error={errors.batch_id}
                  options={getBatchOptions()}
                  placeholder={formData.item_id ? "Select a batch" : "Select an item first"}
                  disabled={!formData.item_id || batchesLoading}
                />
              </FormGroup>
              
              <FormGroup className="md:col-span-2">
                <Label htmlFor="notes">Notes</Label>
                <Textarea
                  id="notes"
                  name="notes"
                  value={formData.notes}
                  onChange={handleChange}
                  error={errors.notes}
                  rows={3}
                  placeholder="Additional information about this transaction"
                />
              </FormGroup>
            </div>
          </CardBody>
          <CardFooter>
            <div className="flex justify-between">
              <Button 
                type="button" 
                variant="light" 
                onClick={() => navigate('/inventory/transactions')}
                disabled={isSubmitting}
              >
                <ArrowLeft className="h-4 w-4 mr-2" />
                Cancel
              </Button>
              <Button 
                type="submit" 
                variant="primary"
                isLoading={isSubmitting}
                disabled={isSubmitting}
              >
                <Save className="h-4 w-4 mr-2" />
                Create Transaction
              </Button>
            </div>
          </CardFooter>
        </Card>
      </form>
    </div>
  );
};

export default StockTransactionForm;