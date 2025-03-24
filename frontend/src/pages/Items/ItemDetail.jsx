// src/pages/Items/ItemDetail.jsx
import React, { useState, useEffect } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import { itemService, stockTransactionService } from '../../services/inventoryService';

const ItemDetail = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const [item, setItem] = useState(null);
  const [transactions, setTransactions] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  
  useEffect(() => {
    const fetchData = async () => {
      try {
        const [itemRes, transactionsRes] = await Promise.all([
          itemService.getById(id),
          stockTransactionService.getItemTransactions(id)
        ]);
        
        setItem(itemRes.data.data);
        setTransactions(transactionsRes.data.data);
      } catch (err) {
        setError('Error loading item details');
        console.error('Error fetching item details:', err);
      } finally {
        setLoading(false);
      }
    };
    
    fetchData();
  }, [id]);
  
  if (loading) {
    return (
      <div className="flex justify-center items-center h-64">
        <p>Loading item details...</p>
      </div>
    );
  }
  
  if (error || !item) {
    return (
      <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
        {error || 'Item not found'}
      </div>
    );
  }
  
  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h1 className="text-2xl font-bold">Item Details</h1>
        <div className="space-x-2">
          <Link to={`/items/${id}/edit`} className="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md">
            Edit Item
          </Link>
          <Link to="/stock-transactions/create" className="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md">
            Add Transaction
          </Link>
        </div>
      </div>
      
      {/* Item Details Card */}
      <div className="bg-white shadow-md rounded-md overflow-hidden">
        <div className="p-6">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <h2 className="text-xl font-semibold mb-4">Basic Information</h2>
              <table className="min-w-full">
                <tbody>
                  <tr>
                    <td className="py-2 font-medium">Item Code:</td>
                    <td className="py-2">{item.item_code}</td>
                  </tr>
                  <tr>
                    <td className="py-2 font-medium">Name:</td>
                    <td className="py-2">{item.name}</td>
                  </tr>
                  <tr>
                    <td className="py-2 font-medium">Category:</td>
                    <td className="py-2">{item.category?.name || '-'}</td>
                  </tr>
                  <tr>
                    <td className="py-2 font-medium">UOM:</td>
                    <td className="py-2">{item.unitOfMeasure?.name || '-'} ({item.unitOfMeasure?.symbol || '-'})</td>
                  </tr>
                  <tr>
                    <td className="py-2 font-medium">Description:</td>
                    <td className="py-2">{item.description || '-'}</td>
                  </tr>
                </tbody>
              </table>
            </div>
            
            <div>
              <h2 className="text-xl font-semibold mb-4">Stock Information</h2>
              <table className="min-w-full">
                <tbody>
                  <tr>
                    <td className="py-2 font-medium">Current Stock:</td>
                    <td className="py-2 font-bold">{item.current_stock} {item.unitOfMeasure?.symbol || ''}</td>
                  </tr>
                  <tr>
                    <td className="py-2 font-medium">Minimum Stock:</td>
                    <td className="py-2">{item.minimum_stock} {item.unitOfMeasure?.symbol || ''}</td>
                  </tr>
                  <tr>
                    <td className="py-2 font-medium">Maximum Stock:</td>
                    <td className="py-2">{item.maximum_stock} {item.unitOfMeasure?.symbol || ''}</td>
                  </tr>
                  <tr>
                    <td className="py-2 font-medium">Status:</td>
                    <td className="py-2">
                      <span 
                        className={`px-2 py-1 rounded-full text-xs font-medium
                          ${item.current_stock <= item.minimum_stock ? 'bg-red-100 text-red-800' : 
                            item.current_stock >= item.maximum_stock ? 'bg-yellow-100 text-yellow-800' : 
                            'bg-green-100 text-green-800'}`}
                      >
                        {item.current_stock <= item.minimum_stock ? 'Low Stock' : 
                         item.current_stock >= item.maximum_stock ? 'Over Stock' : 'Normal'}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
      
      {/* Item Batches */}
      {item.batches && item.batches.length > 0 && (
        <div className="bg-white shadow-md rounded-md overflow-hidden">
          <div className="p-6">
            <h2 className="text-xl font-semibold mb-4">Batches</h2>
            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-gray-200">
                <thead className="bg-gray-50">
                  <tr>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Batch Number</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expiry Date</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Manufacturing Date</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lot Number</th>
                  </tr>
                </thead>
                <tbody className="bg-white divide-y divide-gray-200">
                  {item.batches.map(batch => (
                    <tr key={batch.batch_id}>
                      <td className="px-6 py-4 whitespace-nowrap">{batch.batch_number}</td>
                      <td className="px-6 py-4 whitespace-nowrap">{batch.expiry_date || '-'}</td>
                      <td className="px-6 py-4 whitespace-nowrap">{batch.manufacturing_date || '-'}</td>
                      <td className="px-6 py-4 whitespace-nowrap">{batch.lot_number || '-'}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}
      
      {/* Transaction History */}
      <div className="bg-white shadow-md rounded-md overflow-hidden">
        <div className="p-6">
          <h2 className="text-xl font-semibold mb-4">Transaction History</h2>
          
          {transactions.length > 0 ? (
            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-gray-200">
                <thead className="bg-gray-50">
                  <tr>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Warehouse</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                  </tr>
                </thead>
                <tbody className="bg-white divide-y divide-gray-200">
                  {transactions.map(transaction => (
                    <tr key={transaction.transaction_id}>
                      <td className="px-6 py-4 whitespace-nowrap">{new Date(transaction.transaction_date).toLocaleDateString()}</td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <span 
                          className={`px-2 py-1 rounded-full text-xs font-medium
                            ${transaction.transaction_type.includes('IN') || transaction.transaction_type.includes('RECEIPT') ? 
                              'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`}
                        >
                          {transaction.transaction_type}
                        </span>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        {transaction.quantity} {item.unitOfMeasure?.symbol || ''}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">{transaction.warehouse?.name || '-'}</td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        {transaction.reference_document ? 
                          `${transaction.reference_document} ${transaction.reference_number || ''}` : '-'}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          ) : (
            <p className="text-gray-500">No transactions found for this item.</p>
          )}
        </div>
      </div>
    </div>
  );
};

export default ItemDetail;