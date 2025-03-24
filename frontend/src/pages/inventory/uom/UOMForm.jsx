// src/pages/inventory/uom/UOMForm.jsx
import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from 'react-query';
import { Save, ArrowLeft, Plus, Trash } from 'lucide-react';
import { unitOfMeasureService } from '../../../services/inventoryService';
import PageHeader from '../../../components/common/PageHeader';
import { Card, CardBody, CardFooter } from '../../../components/common/Card';
import { FormGroup, Label, Input, Select, Textarea, Button } from '../../../components/common/FormElements';
import Alert from '../../../components/common/Alert';

const UOMForm = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const isEditMode = !!id;
  
  // Form state
  const [formData, setFormData] = useState({
    name: '',
    symbol: '',
    description: '',
    conversions: []
  });
  
  // Form errors
  const [errors, setErrors] = useState({});
  
  // Alert state
  const [alert, setAlert] = useState(null);
  
  // Fetch all UOMs for conversion dropdown
  const { 
    data: uomData, 
    isLoading: uomLoading 
  } = useQuery(
    'unitOfMeasures',
    () => unitOfMeasureService.getAll()
  );
  
  // Fetch UOM data for edit mode
  const { 
    data: uomDetailData, 
    isLoading: uomDetailLoading 
  } = useQuery(
    ['unitOfMeasure', id],
    () => unitOfMeasureService.getById(id),
    {
      enabled: isEditMode,
      onSuccess: (data) => {
        const uom = data.data;
        setFormData({
          name: uom.name || '',
          symbol: uom.symbol || '',
          description: uom.description || '',
          conversions: uom.conversions?.map(conversion => ({
            to_uom_id: conversion.to_uom_id,
            conversion_factor: conversion.conversion_factor
          })) || []
        });
      },
      onError: (error) => {
        setAlert({
          type: 'error',
          title: 'Error',
          message: 'Failed to load unit of measure data. Please try again.'
        });
      }
    }
  );
  
  // Create UOM mutation
  const createMutation = useMutation(
    (data) => unitOfMeasureService.create(data),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('unitOfMeasures');
        setAlert({
          type: 'success',
          title: 'Success',
          message: 'Unit of measure created successfully.'
        });
        
        // Navigate back to list after delay
        setTimeout(() => {
          navigate('/inventory/uom');
        }, 1500);
      },
      onError: (error) => {
        if (error.response?.data?.errors) {
          setErrors(error.response.data.errors);
        } else {
          setAlert({
            type: 'error',
            title: 'Error',
            message: error.response?.data?.message || 'Failed to create unit of measure. Please try again.'
          });
        }
      }
    }
  );
  
  // Update UOM mutation
  const updateMutation = useMutation(
    ({ id, data }) => unitOfMeasureService.update(id, data),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('unitOfMeasures');
        queryClient.invalidateQueries(['unitOfMeasure', id]);
        setAlert({
          type: 'success',
          title: 'Success',
          message: 'Unit of measure updated successfully.'
        });
        
        // Navigate back to list after delay
        setTimeout(() => {
          navigate('/inventory/uom');
        }, 1500);
      },
      onError: (error) => {
        if (error.response?.data?.errors) {
          setErrors(error.response.data.errors);
        } else {
          setAlert({
            type: 'error',
            title: 'Error',
            message: error.response?.data?.message || 'Failed to update unit of measure. Please try again.'
          });
        }
      }
    }
  );
  
  // Handle form input changes
  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
    
    // Clear error for this field
    if (errors[name]) {
      setErrors(prev => ({
        ...prev,
        [name]: undefined
      }));
    }
  };
  
  // Handle conversion input changes
  const handleConversionChange = (index, field, value) => {
    const updatedConversions = [...formData.conversions];
    updatedConversions[index] = {
      ...updatedConversions[index],
      [field]: value
    };
    
    setFormData(prev => ({
      ...prev,
      conversions: updatedConversions
    }));
    
    // Clear conversion errors if any
    if (errors[`conversions.${index}.${field}`]) {
      setErrors(prev => ({
        ...prev,
        [`conversions.${index}.${field}`]: undefined
      }));
    }
  };
  
  // Add new conversion
  const addConversion = () => {
    setFormData(prev => ({
      ...prev,
      conversions: [
        ...prev.conversions,
        { to_uom_id: '', conversion_factor: '' }
      ]
    }));
  };
  
  // Remove conversion
  const removeConversion = (index) => {
    const updatedConversions = [...formData.conversions];
    updatedConversions.splice(index, 1);
    
    setFormData(prev => ({
      ...prev,
      conversions: updatedConversions
    }));
  };
  
  // Handle form submission
  const handleSubmit = (e) => {
    e.preventDefault();
    setErrors({});
    
    // Basic validation
    const validationErrors = {};
    if (!formData.name.trim()) {
      validationErrors.name = 'Name is required';
    }
    
    if (!formData.symbol.trim()) {
      validationErrors.symbol = 'Symbol is required';
    }
    
    // Validate conversions
    formData.conversions.forEach((conversion, index) => {
      if (!conversion.to_uom_id) {
        validationErrors[`conversions.${index}.to_uom_id`] = 'Target UOM is required';
      }
      
      if (!conversion.conversion_factor || conversion.conversion_factor <= 0) {
        validationErrors[`conversions.${index}.conversion_factor`] = 'Conversion factor must be greater than 0';
      }
    });
    
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
  
  // Get UOM options for conversion dropdown
  const getUOMOptions = () => {
    if (!uomData?.data) return [];
    
    return uomData.data
      .filter(uom => isEditMode ? uom.uom_id !== parseInt(id) : true)
      .map(uom => ({
        value: uom.uom_id,
        label: `${uom.name} (${uom.symbol})`
      }));
  };
  
  // Check if loading
  const isLoading = (isEditMode && uomDetailLoading) || uomLoading;
  const isSubmitting = createMutation.isLoading || updateMutation.isLoading;
  
  return (
    <div>
      <PageHeader 
        title={isEditMode ? 'Edit Unit of Measure' : 'Create Unit of Measure'} 
        subtitle={isEditMode ? 'Update UOM information' : 'Add a new unit of measure'}
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
        <Card className="mb-6">
          <CardBody>
            <div className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <FormGroup>
                  <Label htmlFor="name" required>Name</Label>
                  <Input
                    id="name"
                    name="name"
                    value={formData.name}
                    onChange={handleChange}
                    disabled={isLoading}
                    error={errors.name}
                    required
                    placeholder="e.g. Kilogram, Liter, Piece"
                  />
                </FormGroup>
                
                <FormGroup>
                  <Label htmlFor="symbol" required>Symbol</Label>
                  <Input
                    id="symbol"
                    name="symbol"
                    value={formData.symbol}
                    onChange={handleChange}
                    disabled={isLoading}
                    error={errors.symbol}
                    required
                    placeholder="e.g. kg, L, pc"
                  />
                </FormGroup>
              </div>
              
              <FormGroup>
                <Label htmlFor="description">Description</Label>
                <Textarea
                  id="description"
                  name="description"
                  value={formData.description}
                  onChange={handleChange}
                  disabled={isLoading}
                  error={errors.description}
                  rows={2}
                  placeholder="Optional description for this unit of measure"
                />
              </FormGroup>
            </div>
          </CardBody>
        </Card>
        
        <Card className="mb-6">
          <CardBody>
            <div className="flex justify-between items-center mb-4">
              <h3 className="text-lg font-medium text-gray-900">Unit Conversions</h3>
              <Button 
                type="button" 
                variant="secondary" 
                size="sm"
                onClick={addConversion}
              >
                <Plus className="h-4 w-4 mr-2" />
                Add Conversion
              </Button>
            </div>
            
            <div className="space-y-4">
              {formData.conversions.length === 0 ? (
                <div className="text-center py-4 text-gray-500">
                  <p>No conversions added yet.</p>
                  <p className="text-sm">Add conversions to enable automatic conversion between units.</p>
                </div>
              ) : (
                formData.conversions.map((conversion, index) => (
                  <div key={index} className="flex items-end space-x-4 p-4 border border-gray-200 rounded-md">
                    <div className="text-center font-medium text-gray-700">
                      1 {formData.symbol} =
                    </div>
                    
                    <FormGroup className="flex-1">
                      <Input
                        type="number"
                        step="0.0001"
                        min="0.0001"
                        value={conversion.conversion_factor}
                        onChange={(e) => handleConversionChange(index, 'conversion_factor', e.target.value)}
                        disabled={isLoading}
                        error={errors[`conversions.${index}.conversion_factor`]}
                        placeholder="Conversion factor"
                      />
                    </FormGroup>
                    
                    <FormGroup className="flex-1">
                      <Select
                        value={conversion.to_uom_id}
                        onChange={(e) => handleConversionChange(index, 'to_uom_id', e.target.value)}
                        disabled={isLoading || uomLoading}
                        error={errors[`conversions.${index}.to_uom_id`]}
                        options={getUOMOptions()}
                        placeholder="Select target UOM"
                      />
                    </FormGroup>
                    
                    <Button
                      type="button"
                      variant="danger"
                      size="sm"
                      onClick={() => removeConversion(index)}
                    >
                      <Trash className="h-4 w-4" />
                    </Button>
                  </div>
                ))
              )}
            </div>
            
            {formData.conversions.length > 0 && (
              <div className="mt-4 p-3 bg-blue-50 border-l-4 border-blue-400 text-blue-700">
                <p className="text-sm">
                  <strong>Note:</strong> Conversions are one-way. If you need two-way conversion, add both directions.
                </p>
              </div>
            )}
          </CardBody>
        </Card>
        
        <div className="flex justify-between">
          <Button 
            type="button" 
            variant="light" 
            onClick={() => navigate('/inventory/uom')}
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
            {isEditMode ? 'Update UOM' : 'Create UOM'}
          </Button>
        </div>
      </form>
    </div>
  );
};

export default UOMForm;