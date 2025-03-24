// src/pages/inventory/items/ItemForm.jsx
import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from 'react-query';
import { Save, ArrowLeft, Loader } from 'lucide-react';
import { itemService, categoryService, unitOfMeasureService } from '../../../services/inventoryService';
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

const ItemForm = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const isEditMode = !!id;
  
  // Form state
  const [formData, setFormData] = useState({
    item_code: '',
    name: '',
    description: '',
    category_id: '',
    uom_id: '',
    minimum_stock: 0,
    maximum_stock: 0
  });
  
  // Form errors state
  const [errors, setErrors] = useState({});
  
  // Form submission status
  const [formAlert, setFormAlert] = useState(null);
  
  // Fetch item data for edit mode
  const { data: itemData, isLoading: itemLoading, isError: itemError } = useQuery(
    ['item', id],
    () => itemService.getById(id),
    {
      enabled: isEditMode,
      onSuccess: (response) => {
        const item = response.data;
        setFormData({
          item_code: item.item_code,
          name: item.name,
          description: item.description || '',
          category_id: item.category_id || '',
          uom_id: item.uom_id || '',
          minimum_stock: item.minimum_stock || 0,
          maximum_stock: item.maximum_stock || 0
        });
      }
    }
  );
  
  // Fetch categories for dropdown
  const { data: categoriesData, isLoading: categoriesLoading } = useQuery(
    'categories',
    () => categoryService.getAll()
  );
  
  // Fetch units of measure for dropdown
  const { data: uomData, isLoading: uomLoading } = useQuery(
    'units-of-measure',
    () => unitOfMeasureService.getAll()
  );
  
  // Create item mutation
  const createMutation = useMutation(
    (data) => itemService.create(data),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('items');
        setFormAlert({
          type: 'success',
          title: 'Success!',
          message: 'Item created successfully.'
        });
        setTimeout(() => {
          navigate('/inventory/items');
        }, 1500);
      },
      onError: (error) => {
        if (error.response?.data?.errors) {
          setErrors(error.response.data.errors);
        } else {
          setFormAlert({
            type: 'error',
            title: 'Error!',
            message: 'Failed to create item. Please try again.'
          });
        }
      }
    }
  );
  
  // Update item mutation
  const updateMutation = useMutation(
    ({ id, data }) => itemService.update(id, data),
    {
      onSuccess: () => {
        queryClient.invalidateQueries(['item', id]);
        queryClient.invalidateQueries('items');
        setFormAlert({
          type: 'success',
          title: 'Success!',
          message: 'Item updated successfully.'
        });
        setTimeout(() => {
          navigate('/inventory/items');
        }, 1500);
      },
      onError: (error) => {
        if (error.response?.data?.errors) {
          setErrors(error.response.data.errors);
        } else {
          setFormAlert({
            type: 'error',
            title: 'Error!',
            message: 'Failed to update item. Please try again.'
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
  };
  
  // Handle form submission
  const handleSubmit = (e) => {
    e.preventDefault();
    setErrors({});
    setFormAlert(null);
    
    // Validation
    const validationErrors = {};
    if (!formData.item_code.trim()) validationErrors.item_code = 'Item code is required';
    if (!formData.name.trim()) validationErrors.name = 'Name is required';
    
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
  
  const isLoading = itemLoading || categoriesLoading || uomLoading;
  const isSubmitting = createMutation.isLoading || updateMutation.isLoading;
  
  return (
    <div>
      <PageHeader 
        title={isEditMode ? 'Edit Item' : 'Create Item'} 
        subtitle={isEditMode ? 'Update item information' : 'Add a new item to inventory'}
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
      
      {itemError && (
        <Alert
          type="error"
          title="Error Loading Item"
          message="Failed to load item data. Please try again."
          className="mb-6"
        />
      )}
      
      {isLoading ? (
        <div className="flex justify-center items-center h-64">
          <Loader className="h-8 w-8 animate-spin text-indigo-600" />
          <span className="ml-2 text-lg text-gray-700">Loading...</span>
        </div>
      ) : (
        <form onSubmit={handleSubmit}>
          <Card>
            <CardBody>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <FormGroup>
                  <Label htmlFor="item_code" required>Item Code</Label>
                  <Input
                    id="item_code"
                    name="item_code"
                    value={formData.item_code}
                    onChange={handleChange}
                    error={errors.item_code}
                    required
                  />
                </FormGroup>
                
                <FormGroup>
                  <Label htmlFor="name" required>Name</Label>
                  <Input
                    id="name"
                    name="name"
                    value={formData.name}
                    onChange={handleChange}
                    error={errors.name}
                    required
                  />
                </FormGroup>
                
                <FormGroup className="md:col-span-2">
                  <Label htmlFor="description">Description</Label>
                  <Textarea
                    id="description"
                    name="description"
                    value={formData.description}
                    onChange={handleChange}
                    rows={3}
                    error={errors.description}
                  />
                </FormGroup>
                
                <FormGroup>
                  <Label htmlFor="category_id">Category</Label>
                  <Select
                    id="category_id"
                    name="category_id"
                    value={formData.category_id}
                    onChange={handleChange}
                    error={errors.category_id}
                    options={
                      categoriesLoading
                        ? []
                        : categoriesData?.data.map((category) => ({
                            value: category.category_id,
                            label: category.name,
                          })) || []
                    }
                    placeholder="Select a category"
                    disabled={categoriesLoading}
                  />
                </FormGroup>
                
                <FormGroup>
                  <Label htmlFor="uom_id">Unit of Measure</Label>
                  <Select
                    id="uom_id"
                    name="uom_id"
                    value={formData.uom_id}
                    onChange={handleChange}
                    error={errors.uom_id}
                    options={
                      uomLoading
                        ? []
                        : uomData?.data.map((uom) => ({
                            value: uom.uom_id,
                            label: `${uom.name} (${uom.symbol})`,
                          })) || []
                    }
                    placeholder="Select a unit of measure"
                    disabled={uomLoading}
                  />
                </FormGroup>
                
                <FormGroup>
                  <Label htmlFor="minimum_stock">Minimum Stock</Label>
                  <Input
                    id="minimum_stock"
                    name="minimum_stock"
                    type="number"
                    min="0"
                    step="0.01"
                    value={formData.minimum_stock}
                    onChange={handleChange}
                    error={errors.minimum_stock}
                  />
                </FormGroup>
                
                <FormGroup>
                  <Label htmlFor="maximum_stock">Maximum Stock</Label>
                  <Input
                    id="maximum_stock"
                    name="maximum_stock"
                    type="number"
                    min="0"
                    step="0.01"
                    value={formData.maximum_stock}
                    onChange={handleChange}
                    error={errors.maximum_stock}
                  />
                </FormGroup>
              </div>
            </CardBody>
            <CardFooter>
              <div className="flex justify-between">
                <Button 
                  type="button" 
                  variant="light" 
                  onClick={() => navigate('/inventory/items')}
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
                  {isEditMode ? 'Update Item' : 'Create Item'}
                </Button>
              </div>
            </CardFooter>
          </Card>
        </form>
      )}
    </div>
  );
};

export default ItemForm;

