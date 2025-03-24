// src/pages/inventory/items/ItemsList.jsx
import React, { useState, useEffect } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { useQuery } from 'react-query';
import { Search, Filter, Plus, Trash, Edit, Eye } from 'lucide-react';
import { itemService, categoryService } from '../../../services/inventoryService';
import PageHeader from '../../../components/common/PageHeader';
import Table from '../../../components/common/Table';
import { Card, CardBody } from '../../../components/common/Card';
import { 
  FormGroup, 
  Label, 
  Input, 
  Select, 
  Button 
} from '../../../components/common/FormElements';
import Alert from '../../../components/common/Alert';
import Modal from '../../../components/common/Modal';

const ItemsList = () => {
  const location = useLocation();
  const navigate = useNavigate();
  const queryParams = new URLSearchParams(location.search);
  
  // State for filters
  const [filters, setFilters] = useState({
    search: queryParams.get('search') || '',
    category: queryParams.get('category') || '',
    status: queryParams.get('status') || '',
  });
  
  // State for pagination
  const [pagination, setPagination] = useState({
    page: parseInt(queryParams.get('page') || '1', 10),
    perPage: parseInt(queryParams.get('perPage') || '10', 10),
  });
  
  // State for delete modal
  const [deleteModalOpen, setDeleteModalOpen] = useState(false);
  const [itemToDelete, setItemToDelete] = useState(null);
  
  // Update URL with filters and pagination
  useEffect(() => {
    const params = new URLSearchParams();
    if (filters.search) params.append('search', filters.search);
    if (filters.category) params.append('category', filters.category);
    if (filters.status) params.append('status', filters.status);
    params.append('page', pagination.page.toString());
    params.append('perPage', pagination.perPage.toString());
    
    navigate({
      pathname: location.pathname,
      search: params.toString(),
    }, { replace: true });
  }, [filters, pagination, navigate, location.pathname]);
  
  // Fetch items with filters and pagination
  const { 
    data: itemsData, 
    isLoading: itemsLoading, 
    isError: itemsError,
    refetch: refetchItems
  } = useQuery(
    ['items', filters, pagination],
    () => itemService.getAll({
      search: filters.search,
      category_id: filters.category,
      status: filters.status,
      page: pagination.page,
      per_page: pagination.perPage,
    }),
    {
      keepPreviousData: true,
    }
  );
  
  // Fetch categories for filter dropdown
  const { 
    data: categoriesData, 
    isLoading: categoriesLoading 
  } = useQuery(
    'categories',
    () => categoryService.getAll()
  );
  
  // Handle filter changes
  const handleFilterChange = (name, value) => {
    setFilters((prev) => ({
      ...prev,
      [name]: value,
    }));
    // Reset to first page when filters change
    setPagination((prev) => ({
      ...prev,
      page: 1,
    }));
  };
  
  // Handle page change
  const handlePageChange = (newPage) => {
    setPagination((prev) => ({
      ...prev,
      page: newPage,
    }));
  };
  
  // Handle per page change
  const handlePerPageChange = (e) => {
    const newPerPage = parseInt(e.target.value, 10);
    setPagination({
      page: 1, // Reset to first page
      perPage: newPerPage,
    });
  };
  
  // Handle reset filters
  const handleResetFilters = () => {
    setFilters({
      search: '',
      category: '',
      status: '',
    });
    setPagination({
      page: 1,
      perPage: 10,
    });
  };
  
  // Handle delete item
  const handleDeleteItem = async () => {
    if (!itemToDelete) return;
    
    try {
      await itemService.delete(itemToDelete.item_id);
      setDeleteModalOpen(false);
      setItemToDelete(null);
      refetchItems();
    } catch (error) {
      console.error('Error deleting item:', error);
      // Handle error, maybe show a notification
    }
  };
  
  // Table columns definition
  const columns = [
    {
      key: 'item_code',
      label: 'Item Code',
      render: (item) => (
        <div className="font-medium text-gray-900">{item.item_code}</div>
      ),
    },
    {
      key: 'name',
      label: 'Name',
      render: (item) => (
        <Link to={`/inventory/items/${item.item_id}`} className="text-indigo-600 hover:text-indigo-900">
          {item.name}
        </Link>
      ),
    },
    {
      key: 'category',
      label: 'Category',
      render: (item) => (
        <div>{item.category?.name || '-'}</div>
      ),
    },
    {
      key: 'uom',
      label: 'UOM',
      render: (item) => (
        <div>{item.unitOfMeasure?.symbol || '-'}</div>
      ),
    },
    {
      key: 'current_stock',
      label: 'Current Stock',
      render: (item) => (
        <div>{item.current_stock}</div>
      ),
    },
    {
      key: 'status',
      label: 'Status',
      render: (item) => {
        let statusClass = 'bg-green-100 text-green-800'; // Normal
        if (item.status === 'Low Stock') {
          statusClass = 'bg-red-100 text-red-800';
        } else if (item.status === 'Over Stock') {
          statusClass = 'bg-yellow-100 text-yellow-800';
        }
        
        return (
          <span className={`px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ${statusClass}`}>
            {item.status}
          </span>
        );
      },
    },
    {
      key: 'actions',
      label: 'Actions',
      render: (item) => (
        <div className="flex space-x-2">
          <Link 
            to={`/inventory/items/${item.item_id}`}
            className="text-blue-600 hover:text-blue-900"
            title="View"
          >
            <Eye className="h-5 w-5" />
          </Link>
          <Link 
            to={`/inventory/items/${item.item_id}/edit`}
            className="text-indigo-600 hover:text-indigo-900"
            title="Edit"
          >
            <Edit className="h-5 w-5" />
          </Link>
          <button 
            onClick={() => {
              setItemToDelete(item);
              setDeleteModalOpen(true);
            }}
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
        title="Items" 
        subtitle="Manage your inventory items"
        actionLabel="Add New Item"
        actionUrl="/inventory/items/create"
      />
      
      {/* Filters */}
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
                  placeholder="Search by code or name"
                  className="pl-10"
                />
              </div>
            </FormGroup>
            
            <FormGroup>
              <Label htmlFor="category">Category</Label>
              <Select
                id="category"
                value={filters.category}
                onChange={(e) => handleFilterChange('category', e.target.value)}
                options={
                  categoriesLoading
                    ? []
                    : categoriesData?.data.map((category) => ({
                        value: category.category_id,
                        label: category.name,
                      })) || []
                }
                placeholder="All Categories"
                disabled={categoriesLoading}
              />
            </FormGroup>
            
            <FormGroup>
              <Label htmlFor="status">Status</Label>
              <Select
                id="status"
                value={filters.status}
                onChange={(e) => handleFilterChange('status', e.target.value)}
                options={[
                  { value: 'Low Stock', label: 'Low Stock' },
                  { value: 'Normal', label: 'Normal' },
                  { value: 'Over Stock', label: 'Over Stock' },
                ]}
                placeholder="All Status"
              />
            </FormGroup>
            
            <div className="flex items-end">
              <Button
                variant="secondary"
                onClick={handleResetFilters}
                className="w-full"
              >
                <Filter className="h-4 w-4 mr-2" />
                Reset Filters
              </Button>
            </div>
          </div>
        </CardBody>
      </Card>
      
      {/* Items Table */}
      {itemsError && (
        <Alert
          type="error"
          title="Error loading items"
          message="There was an error loading the items. Please try again later."
          className="mb-6"
        />
      )}
      
      <Table
        columns={columns}
        data={itemsData?.data || []}
        isLoading={itemsLoading}
        pagination={itemsData?.meta}
        onPageChange={handlePageChange}
        emptyMessage="No items found matching your filters."
      />
      
      {/* Items per page selector */}
      <div className="mt-4 flex justify-end items-center">
        <span className="mr-2 text-sm text-gray-600">Items per page:</span>
        <select
          value={pagination.perPage}
          onChange={handlePerPageChange}
          className="form-select border border-gray-300 rounded-md shadow-sm text-sm"
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
        title="Confirm Delete"
        size="sm"
        footer={
          <div className="flex justify-end space-x-3">
            <Button variant="light" onClick={() => setDeleteModalOpen(false)}>
              Cancel
            </Button>
            <Button variant="danger" onClick={handleDeleteItem}>
              Delete
            </Button>
          </div>
        }
      >
        <p className="text-gray-700">
          Are you sure you want to delete the item "{itemToDelete?.name}"? This action cannot be undone.
        </p>
      </Modal>
    </div>
  );
};

export default ItemsList;
