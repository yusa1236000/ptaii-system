// src/pages/inventory/items/ItemDetail.jsx
import React, { useState } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from 'react-query';
import { 
  Edit, 
  Trash, 
  Package, 
  Tag, 
  Ruler, 
  AlertTriangle, 
  Check, 
  Clock, 
  List, 
  TrendingUp, 
  TrendingDown 
} from 'lucide-react';
import { itemService, stockTransactionService } from '../../../services/inventoryService';
import PageHeader from '../../../components/common/PageHeader';
import { Card, CardHeader, CardBody } from '../../../components/common/Card';
import { Button } from '../../../components/common/FormElements';
import Table from '../../../components/common/Table';
import Alert from '../../../components/common/Alert';
import Modal from '../../../components/common/Modal';

const ItemDetail = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  
  // State for delete modal
  const [deleteModalOpen, setDeleteModalOpen] = useState(false);
  
  // Fetch item data
  const { 
    data: itemData, 
    isLoading: itemLoading, 
    isError: itemError 
  } = useQuery(
    ['item', id],
    () => itemService.getById(id)
  );
  
  // Fetch item transactions
  const { 
    data: transactionsData, 
    isLoading: transactionsLoading 
  } = useQuery(
    ['itemTransactions', id],
    () => stockTransactionService.getItemTransactions(id)
  );
  
  // Delete item mutation
  const deleteMutation = useMutation(
    () => itemService.delete(id),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('items');
        navigate('/inventory/items');
      }
    }
  );
  
  // Handle delete item
  const handleDeleteItem = () => {
    deleteMutation.mutate();
  };
  
  const item = itemData?.data;
  const transactions = transactionsData?.data || [];
  
  // Determine stock status for styling
  const getStockStatusInfo = () => {
    if (!item) return { color: 'gray', icon: Clock, text: 'Loading...' };
    
    if (item.current_stock <= 0) {
      return { color: 'red', icon: AlertTriangle, text: 'Out of Stock' };
    }
    
    if (item.current_stock <= item.minimum_stock) {
      return { color: 'orange', icon: AlertTriangle, text: 'Low Stock' };
    }
    
    if (item.current_stock >= item.maximum_stock) {
      return { color: 'yellow', icon: AlertTriangle, text: 'Over Stock' };
    }
    
    return { color: 'green', icon: Check, text: 'In Stock' };
  };
  
  const stockStatus = getStockStatusInfo();
  
  // Transaction table columns
  const transactionColumns = [
    {
      key: 'date',
      label: 'Date',
      render: (transaction) => (
        <div className="text-sm text-gray-900">
          {new Date(transaction.transaction_date).toLocaleDateString()}
        </div>
      )
    },
    {
      key: 'type',
      label: 'Type',
      render: (transaction) => {
        const isInflow = ['IN', 'RECEIPT', 'ADJUSTMENT_IN'].includes(transaction.transaction_type);
        return (
          <span 
            className={`px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ${
              isInflow ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
            }`}
          >
            {isInflow ? (
              <TrendingUp className="h-4 w-4 mr-1" />
            ) : (
              <TrendingDown className="h-4 w-4 mr-1" />
            )}
            {transaction.transaction_type}
          </span>
        );
      }
    },
    {
      key: 'quantity',
      label: 'Quantity',
      render: (transaction) => (
        <div className="text-sm text-gray-900">
          {transaction.quantity} {item?.unitOfMeasure?.symbol || ''}
        </div>
      )
    },
    {
      key: 'warehouse',
      label: 'Warehouse',
      render: (transaction) => (
        <div className="text-sm text-gray-900">
          {transaction.warehouse?.name || '-'}
        </div>
      )
    },
    {
      key: 'reference',
      label: 'Reference',
      render: (transaction) => (
        <div className="text-sm text-gray-900">
          {transaction.reference_document && transaction.reference_number ? 
            `${transaction.reference_document} #${transaction.reference_number}` : 
            '-'}
        </div>
      )
    }
  ];
  
  if (itemError) {
    return (
      <div className="container mx-auto px-4 py-8">
        <Alert
          type="error"
          title="Error Loading Item"
          message="Failed to load item details. Please try again or contact support."
        />
        <div className="mt-4">
          <Button variant="primary" onClick={() => navigate('/inventory/items')}>
            Back to Items List
          </Button>
        </div>
      </div>
    );
  }
  
  return (
    <div>
      <PageHeader 
        title="Item Details" 
        subtitle={itemLoading ? 'Loading...' : item?.name}
        actionLabel="Edit Item"
        actionUrl={`/inventory/items/${id}/edit`}
      />
      
      {/* Item Basic Info Card */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <Card className="lg:col-span-2">
          <CardHeader title="Basic Information" />
          <CardBody>
            {itemLoading ? (
              <div className="animate-pulse">
                <div className="h-4 bg-gray-200 rounded w-1/4 mb-4"></div>
                <div className="h-4 bg-gray-200 rounded w-1/2 mb-4"></div>
                <div className="h-4 bg-gray-200 rounded w-3/4 mb-4"></div>
                <div className="h-4 bg-gray-200 rounded w-full"></div>
              </div>
            ) : (
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <div className="mb-4">
                    <span className="block text-sm font-medium text-gray-500">Item Code</span>
                    <span className="block mt-1 text-base text-gray-900">{item?.item_code}</span>
                  </div>
                  <div className="mb-4">
                    <span className="block text-sm font-medium text-gray-500">Name</span>
                    <span className="block mt-1 text-base text-gray-900">{item?.name}</span>
                  </div>
                  <div className="mb-4">
                    <span className="block text-sm font-medium text-gray-500">Category</span>
                    <span className="block mt-1 text-base text-gray-900">
                      {item?.category ? (
                        <div className="flex items-center">
                          <Tag className="h-4 w-4 mr-1 text-gray-500" />
                          {item.category.name}
                        </div>
                      ) : (
                        'Not categorized'
                      )}
                    </span>
                  </div>
                  <div className="mb-4">
                    <span className="block text-sm font-medium text-gray-500">Unit of Measure</span>
                    <span className="block mt-1 text-base text-gray-900">
                      {item?.unitOfMeasure ? (
                        <div className="flex items-center">
                          <Ruler className="h-4 w-4 mr-1 text-gray-500" />
                          {item.unitOfMeasure.name} ({item.unitOfMeasure.symbol})
                        </div>
                      ) : (
                        'Not specified'
                      )}
                    </span>
                  </div>
                </div>
                <div>
                  <div className="mb-4">
                    <span className="block text-sm font-medium text-gray-500">Description</span>
                    <span className="block mt-1 text-base text-gray-900">
                      {item?.description || 'No description provided'}
                    </span>
                  </div>
                  <div className="mb-4">
                    <span className="block text-sm font-medium text-gray-500">Created At</span>
                    <span className="block mt-1 text-base text-gray-900">
                      {item?.created_at ? new Date(item.created_at).toLocaleString() : '-'}
                    </span>
                  </div>
                  <div className="mb-4">
                    <span className="block text-sm font-medium text-gray-500">Updated At</span>
                    <span className="block mt-1 text-base text-gray-900">
                      {item?.updated_at ? new Date(item.updated_at).toLocaleString() : '-'}
                    </span>
                  </div>
                </div>
              </div>
            )}
          </CardBody>
        </Card>
        
        <Card>
          <CardHeader title="Stock Information" />
          <CardBody>
            {itemLoading ? (
              <div className="animate-pulse">
                <div className="h-4 bg-gray-200 rounded w-1/2 mb-4"></div>
                <div className="h-4 bg-gray-200 rounded w-3/4 mb-4"></div>
                <div className="h-4 bg-gray-200 rounded w-1/2"></div>
              </div>
            ) : (
              <div>
                <div className="mb-6 text-center">
                  <div className={`inline-flex items-center justify-center p-4 bg-${stockStatus.color}-100 rounded-full`}>
                    <stockStatus.icon className={`h-8 w-8 text-${stockStatus.color}-600`} />
                  </div>
                  <div className="mt-2">
                    <span className={`px-2 py-1 text-sm font-medium bg-${stockStatus.color}-100 text-${stockStatus.color}-800 rounded-full`}>
                      {stockStatus.text}
                    </span>
                  </div>
                </div>
                
                <div className="space-y-4">
                  <div className="flex justify-between items-center py-2 border-b border-gray-200">
                    <span className="text-gray-600">Current Stock:</span>
                    <span className="font-medium text-lg">{item?.current_stock || 0} {item?.unitOfMeasure?.symbol || ''}</span>
                  </div>
                  <div className="flex justify-between items-center py-2 border-b border-gray-200">
                    <span className="text-gray-600">Minimum Stock:</span>
                    <span>{item?.minimum_stock || 0} {item?.unitOfMeasure?.symbol || ''}</span>
                  </div>
                  <div className="flex justify-between items-center py-2 border-b border-gray-200">
                    <span className="text-gray-600">Maximum Stock:</span>
                    <span>{item?.maximum_stock || 0} {item?.unitOfMeasure?.symbol || ''}</span>
                  </div>
                </div>
                
                <div className="mt-6 space-y-2">
                  <Link 
                    to="/inventory/transactions/create" 
                    state={{ selectedItem: item }}
                    className="block"
                  >
                    <Button variant="primary" className="w-full">
                      <TrendingUp className="h-4 w-4 mr-2" />
                      Add Transaction
                    </Button>
                  </Link>
                  <Button 
                    variant="danger" 
                    className="w-full"
                    onClick={() => setDeleteModalOpen(true)}
                  >
                    <Trash className="h-4 w-4 mr-2" />
                    Delete Item
                  </Button>
                </div>
              </div>
            )}
          </CardBody>
        </Card>
      </div>
      
      {/* Item Transactions */}
      <Card>
        <CardHeader 
          title="Transaction History" 
          subtitle="Records of all stock movements for this item"
          action={
            <Link to="/inventory/transactions/create" state={{ selectedItem: item }}>
              <Button variant="primary" size="sm">
                <TrendingUp className="h-4 w-4 mr-2" />
                Add Transaction
              </Button>
            </Link>
          }
        />
        <CardBody>
          <Table
            columns={transactionColumns}
            data={transactions}
            isLoading={transactionsLoading}
            emptyMessage="No transactions found for this item."
          />
        </CardBody>
      </Card>
      
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
            <Button 
              variant="danger" 
              onClick={handleDeleteItem}
              isLoading={deleteMutation.isLoading}
            >
              Delete Item
            </Button>
          </div>
        }
      >
        <p className="text-gray-700">
          Are you sure you want to delete this item? This action cannot be undone.
        </p>
        {item?.current_stock > 0 && (
          <div className="mt-4 p-3 bg-yellow-50 border-l-4 border-yellow-400 text-yellow-700">
            <div className="flex">
              <AlertTriangle className="h-5 w-5 mr-2" />
              <p>
                <strong>Warning:</strong> This item still has stock ({item.current_stock} {item.unitOfMeasure?.symbol || ''}). 
                Deleting it may cause inconsistencies in your inventory records.
              </p>
            </div>
          </div>
        )}
      </Modal>
    </div>
  );
};

export default ItemDetail;

