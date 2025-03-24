// src/pages/inventory/categories/CategoryForm.jsx
import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from 'react-query';
import { Save, ArrowLeft } from 'lucide-react';
import { categoryService } from '../../../services/inventoryService';
import PageHeader from '../../../components/common/PageHeader';
import { Card, CardBody, CardFooter } from '../../../components/common/Card';
import { FormGroup, Label, Input, Select, Textarea, Button } from '../../../components/common/FormElements';
import Alert from '../../../components/common/Alert';

const CategoryForm = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const isEditMode = !!id;
  
  // Form state
  const [formData, setFormData] = useState({
    name: '',
    description: '',
    parent_category_id: ''
  });
  
  // Form errors
  const [errors, setErrors] = useState({});
  
  // Alert state
  const [alert, setAlert] = useState(null);
  
  // Fetch categories for parent dropdown
  const { 
    data: categoriesData, 
    isLoading: categoriesLoading 
  } = useQuery(
    'categories',
    () => categoryService.getAll()
  );
  
  // Fetch category data for edit mode
  const { 
    data: categoryData, 
    isLoading: categoryLoading 
  } = useQuery(
    ['category', id],
    () => categoryService.getById(id),
    {
      enabled: isEditMode,
      onSuccess: (data) => {
        const category = data.data;
        setFormData({
          name: category.name || '',
          description: category.description || '',
          parent_category_id: category.parent_category_id || ''
        });
      },
      onError: (error) => {
        setAlert({
          type: 'error',
          title: 'Error',
          message: 'Failed to load category data. Please try again.'
        });
      }
    }
  );
  
  // Create category mutation
  const createMutation = useMutation(
    (data) => categoryService.create(data),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('categories');
        setAlert({
          type: 'success',
          title: 'Success',
          message: 'Category created successfully.'
        });
        
        // Navigate back to list after delay
        setTimeout(() => {
          navigate('/inventory/categories');
        }, 1500);
      },
      onError: (error) => {
        if (error.response?.data?.errors) {
          setErrors(error.response.data.errors);
        } else {
          setAlert({
            type: 'error',
            title: 'Error',
            message: error.response?.data?.message || 'Failed to create category. Please try again.'
          });
        }
      }
    }
  );
  
  // Update category mutation
  const updateMutation = useMutation(
    ({ id, data }) => categoryService.update(id, data),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('categories');
        queryClient.invalidateQueries(['category', id]);
        setAlert({
          type: 'success',
          title: 'Success',
          message: 'Category updated successfully.'
        });
        
        // Navigate back to list after delay
        setTimeout(() => {
          navigate('/inventory/categories');
        }, 1500);
      },
      onError: (error) => {
        if (error.response?.data?.errors) {
          setErrors(error.response.data.errors);
        } else {
          setAlert({
            type: 'error',
            title: 'Error',
            message: error.response?.data?.message || 'Failed to update category. Please try again.'
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
  
  // Handle form submission
  const handleSubmit = (e) => {
    e.preventDefault();
    setErrors({});
    
    // Basic validation
    const validationErrors = {};
    if (!formData.name.trim()) {
      validationErrors.name = 'Category name is required';
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
  
  // Filter out the current category from parent options (to prevent circular references)
  const getParentOptions = () => {
    if (!categoriesData?.data) return [];
    
    return categoriesData.data
      .filter(category => category.category_id !== parseInt(id))
      .map(category => ({
        value: category.category_id,
        label: category.name
      }));
  };
  
  // Check if loading
  const isLoading = (isEditMode && categoryLoading) || categoriesLoading;
  const isSubmitting = createMutation.isLoading || updateMutation.isLoading;
  
  return (
    <div>
      <PageHeader 
        title={isEditMode ? 'Edit Category' : 'Create Category'} 
        subtitle={isEditMode ? 'Update category information' : 'Add a new item category'}
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
              <FormGroup>
                <Label htmlFor="name" required>Category Name</Label>
                <Input
                  id="name"
                  name="name"
                  value={formData.name}
                  onChange={handleChange}
                  disabled={isLoading}
                  error={errors.name}
                  required
                />
              </FormGroup>
              
              <FormGroup>
                <Label htmlFor="description">Description</Label>
                <Textarea
                  id="description"
                  name="description"
                  value={formData.description}
                  onChange={handleChange}
                  disabled={isLoading}
                  error={errors.description}
                  rows={3}
                />
              </FormGroup>
              
              <FormGroup>
                <Label htmlFor="parent_category_id">Parent Category</Label>
                <Select
                  id="parent_category_id"
                  name="parent_category_id"
                  value={formData.parent_category_id}
                  onChange={handleChange}
                  disabled={isLoading || categoriesLoading}
                  error={errors.parent_category_id}
                  options={getParentOptions()}
                  placeholder="Select parent category (optional)"
                />
                <p className="mt-1 text-sm text-gray-500">
                  Leave empty for top-level category
                </p>
              </FormGroup>
            </div>
          </CardBody>
          <CardFooter>
            <div className="flex justify-between">
              <Button 
                type="button" 
                variant="light" 
                onClick={() => navigate('/inventory/categories')}
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
                {isEditMode ? 'Update Category' : 'Create Category'}
              </Button>
            </div>
          </CardFooter>
        </Card>
      </form>
    </div>
  );
};

export default CategoryForm;