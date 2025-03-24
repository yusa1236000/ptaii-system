// src/pages/inventory/categories/CategoriesList.jsx
import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from 'react-query';
import { Plus, Edit, Trash, Search } from 'lucide-react';
import { categoryService } from '../../../services/inventoryService';
import PageHeader from '../../../components/common/PageHeader';
import Table from '../../../components/common/Table';
import { Card, CardBody } from '../../../components/common/Card';
import { FormGroup, Label, Input, Button } from '../../../components/common/FormElements';
import Alert from '../../../components/common/Alert';
import Modal from '../../../components/common/Modal';

const CategoriesList = () => {
  const queryClient = useQueryClient();
  
  // State for search
  const [searchTerm, setSearchTerm] = useState('');
  
  // State for delete modal
  const [deleteModalOpen, setDeleteModalOpen] = useState(false);
  const [categoryToDelete, setCategoryToDelete] = useState(null);
  
  // State for alert
  const [alert, setAlert] = useState(null);
  
  // Fetch categories
  const { 
    data: categoriesData, 
    isLoading, 
    isError, 
    error 
  } = useQuery(
    ['categories', searchTerm],
    () => categoryService.getAll({ search: searchTerm })
  );
  
  // Delete category mutation
  const deleteMutation = useMutation(
    (id) => categoryService.delete(id),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('categories');
        setAlert({
          type: 'success',
          title: 'Category Deleted',
          message: 'The category has been successfully deleted.'
        });
        setDeleteModalOpen(false);
      },
      onError: (err) => {
        setAlert({
          type: 'error',
          title: 'Delete Failed',
          message: err.response?.data?.message || 'Failed to delete the category. Please try again.'
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
  const openDeleteModal = (category) => {
    setCategoryToDelete(category);
    setDeleteModalOpen(true);
  };
  
  // Handle delete
  const handleDelete = () => {
    if (categoryToDelete) {
      deleteMutation.mutate(categoryToDelete.category_id);
    }
  };
  
  // Table columns
  const columns = [
    {
      key: 'name',
      label: 'Name',
      render: (category) => (
        <div className="font-medium text-gray-900">{category.name}</div>
      ),
    },
    {
      key: 'description',
      label: 'Description',
      render: (category) => (
        <div className="text-sm text-gray-500">
          {category.description || '-'}
        </div>
      ),
    },
    {
      key: 'parent_category',
      label: 'Parent Category',
      render: (category) => (
        <div className="text-sm text-gray-500">
          {category.parent_category ? category.parent_category.name : '-'}
        </div>
      ),
    },
    {
      key: 'actions',
      label: 'Actions',
      render: (category) => (
        <div className="flex space-x-2">
          <Link
            to={`/inventory/categories/${category.category_id}/edit`}
            className="text-indigo-600 hover:text-indigo-900"
          >
            <Edit className="h-5 w-5" />
          </Link>
          <button
            onClick={() => openDeleteModal(category)}
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
        title="Categories" 
        subtitle="Manage item categories"
        actionLabel="Add Category"
        actionUrl="/inventory/categories/create"
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
                  placeholder="Search categories"
                  className="pl-10"
                />
              </div>
            </FormGroup>
            
            <Link to="/inventory/categories/create">
              <Button variant="primary">
                <Plus className="h-4 w-4 mr-2" />
                Add Category
              </Button>
            </Link>
          </div>
        </CardBody>
      </Card>
      
      <Table
        columns={columns}
        data={categoriesData?.data || []}
        isLoading={isLoading}
        emptyMessage="No categories found. Create your first category to get started."
      />
      
      {/* Delete Confirmation Modal */}
      <Modal
        isOpen={deleteModalOpen}
        onClose={() => setDeleteModalOpen(false)}
        title="Delete Category"
        size="sm"
      >
        <div className="py-3">
          <p className="text-gray-700">
            Are you sure you want to delete the category <span className="font-medium">{categoryToDelete?.name}</span>?
            This action cannot be undone.
          </p>
          
          {/* Warning about related items */}
          <div className="mt-4 p-3 bg-yellow-50 border-l-4 border-yellow-400 text-yellow-700">
            <div className="flex">
              <svg className="h-5 w-5 text-yellow-400 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
              </svg>
              <p>
                <strong>Warning:</strong> Deleting this category will remove it from all associated items.
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

export default CategoriesList;