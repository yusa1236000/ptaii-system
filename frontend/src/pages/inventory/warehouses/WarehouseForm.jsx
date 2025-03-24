// src/pages/inventory/warehouses/WarehouseForm.jsx
import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from 'react-query';
import { Save, ArrowLeft, MapPin } from 'lucide-react';
import { warehouseService } from '../../../services/inventoryService';
import PageHeader from '../../../components/common/PageHeader';
import { Card, CardBody, CardFooter } from '../../../components/common/Card';
import { FormGroup, Label, Input, Textarea, Checkbox, Button } from '../../../components/common/FormElements';
import Alert from '../../../components/common/Alert';

const WarehouseForm = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const isEditMode = !!id;
  
  // Form state
  const [formData, setFormData] = useState({
    name: '',
    code: '',
    address: '',
    is_active: true
  });
  
  // Form errors
  const [errors, setErrors] = useState({});
  
  // Alert state
  const [alert, setAlert] = useState(null);
  
  // Fetch warehouse data for edit mode
  const { 
    data: warehouseData, 
    isLoading: warehouseLoading 
  } = useQuery(
    ['warehouse', id],
    () => warehouseService.getById(id),
    {
      enabled: isEditMode,
      onSuccess: (data) => {
        const warehouse = data.data;
        setFormData({
          name: warehouse.name || '',
          code: warehouse.code || '',
          address: warehouse.address || '',
          is_active: warehouse.is_active !== false
        });
      },
      onError: (error) => {
        setAlert({
          type: 'error',
          title: 'Error',
          message: 'Failed to load warehouse data. Please try again.'
        });
      }
    }
  );
  
  // Create warehouse mutation
  const createMutation = useMutation(
    (data) => warehouseService.create(data),
    {
      onSuccess: (response) => {
        queryClient.invalidateQueries('warehouses');
        setAlert({
          type: 'success',
          title: 'Success',
          message: 'Warehouse created successfully.'
        });
        
        // Navigate to detail page after delay
        setTimeout(() => {
          navigate(`/inventory/warehouses/${response.data.warehouse_id}`);
        }, 1500);
      },
      onError: (error) => {
        if (error.response?.data?.errors) {
          setErrors(error.response.data.errors);
        } else {
          setAlert({
            type: 'error',
            title: 'Error',
            message: error.response?.data?.message || 'Failed to create warehouse. Please try again.'
          });
        }
      }
    }
  );
  
  // Update warehouse mutation
  const updateMutation = useMutation(
    ({ id, data }) => warehouseService.update(id, data),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('warehouses');
        queryClient.invalidateQueries(['warehouse', id]);
        setAlert({
          type: 'success',
          title: 'Success',
          message: 'Warehouse updated successfully.'
        });
        
        // Navigate back to detail page after delay
        setTimeout(() => {
          navigate(`/inventory/warehouses/${id}`);
        }, 1500);
      },
      onError: (error) => {
        if (error.response?.data?.errors) {
          setErrors(error.response.data.errors);
        } else {
          setAlert({
            type: 'error',
            title: 'Error',
            message: error.response?.data?.message || 'Failed to update warehouse. Please try again.'
          });
        }
      }
    }
  );
  
  // Handle form input changes
  const handleChange = (e) => {
    const { name, value, type, checked } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: type === 'checkbox' ? checked : value
    }));
    
    // Clear error for this field
    if (errors[name]) {
      setErrors(prev => ({
        ...prev,
        [name]: undefined
      }));
    }
  };
  
  // Handle form submission
  const handleSubmit = (e) => {
    e.preventDefault();
    setErrors({});
    
    // Basic validation
    const validationErrors = {};
    if (!formData.name.trim()) {
      validationErrors.name = 'Warehouse name is required';
    }
    
    if (!formData.code.trim()) {
      validationErrors.code = 'Warehouse code is required';
    }
    
    if (Object.keys(validationErrors).length > 0) {
      setErrors(validationErrors);
      return;
    }
    
    if (isEditMode) {
      updateMutation.mutate({ id, data: formData });
    } else {
      createMutation.mutate(formData);
    }
  };
  
  // Check if loading
  const isLoading = isEditMode && warehouseLoading;
  const isSubmitting = createMutation.isLoading || updateMutation.isLoading;
  
  return (
    <div>
      <PageHeader 
        title={isEditMode ? 'Edit Warehouse' : 'Create Warehouse'} 
        subtitle={isEditMode ? 'Update warehouse information' : 'Add a new warehouse location'}
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
      
      <form onSubmit={handleSubmit}>
        <Card>
          <CardBody>
            <div className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <FormGroup>
                  <Label htmlFor="name" required>Warehouse Name</Label>
                  <Input
                    id="name"
                    name="name"
                    value={formData.name}
                    onChange={handleChange}
                    disabled={isLoading}
                    error={errors.name}
                    required
                    placeholder="e.g. Main Warehouse, Distribution Center"
                  />
                </FormGroup>
                
                <FormGroup>
                  <Label htmlFor="code" required>Warehouse Code</Label>
                  <Input
                    id="code"
                    name="code"
                    value={formData.code}
                    onChange={handleChange}
                    disabled={isLoading}
                    error={errors.code}
                    required
                    placeholder="e.g. WH-001, DC-MAIN"
                  />
                </FormGroup>
              </div>
              
              <FormGroup>
                <Label htmlFor="address">Address</Label>
                <Textarea
                  id="address"
                  name="address"
                  value={formData.address}
                  onChange={handleChange}
                  disabled={isLoading}
                  error={errors.address}
                  rows={3}
                  placeholder="Full address of the warehouse"
                />
              </FormGroup>
              
              <FormGroup>
                <Checkbox
                  id="is_active"
                  name="is_active"
                  checked={formData.is_active}
                  onChange={handleChange}
                  disabled={isLoading}
                  label="Active Warehouse"
                />
                <div className="mt-1 ml-6 text-sm text-gray-500">
                  Inactive warehouses will not be available for transactions
                </div>
              </FormGroup>
              
              {isEditMode && (
                <div className="p-4 bg-yellow-50 border-l-4 border-yellow-400 text-yellow-700">
                  <div className="flex">
                    <svg className="h-5 w-5 text-yellow-400 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                      <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                    </svg>
                    <p>
                      <strong>Note:</strong> Changing warehouse information will affect all associated inventory transactions.
                    </p>
                  </div>
                </div>
              )}
            </div>
          </CardBody>
          <CardFooter>
            <div className="flex justify-between">
              <Button 
                type="button" 
                variant="light" 
                onClick={() => navigate('/inventory/warehouses')}
                disabled={isSubmitting}
              >
                <ArrowLeft className="h-4 w-4 mr-2" />
                Back to List
              </Button>
              <Button 
                type="submit" 
                variant="primary"
                isLoading={isSubmitting}
                disabled={isSubmitting}
              >
                <Save className="h-4 w-4 mr-2" />
                {isEditMode ? 'Update Warehouse' : 'Create Warehouse'}
              </Button>
            </div>
          </CardFooter>
        </Card>
      </form>
    </div>
  );
};

export default WarehouseForm;