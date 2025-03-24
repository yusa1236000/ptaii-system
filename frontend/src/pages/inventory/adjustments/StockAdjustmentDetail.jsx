// src/pages/inventory/adjustments/StockAdjustmentDetail.jsx
import React, { useState } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from 'react-query';
import { 
  ArrowLeft, 
  CheckCircle, 
  XCircle, 
  Printer,
  Download,
  Clock,
  Calendar,
  File,
  AlertTriangle
} from 'lucide-react';
import { stockAdjustmentService } from '../../../services/inventoryService';
import PageHeader from '../../../components/common/PageHeader';
import { Card, CardBody, CardHeader, CardFooter } from '../../../components/common/Card';
import { Button } from '../../../components/common/FormElements';
import Alert from '../../../components/common/Alert';
import Modal from '../../../components/common/Modal';

const StockAdjustmentDetail = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  
  // State for modals
  const [confirmModalOpen, setConfirmModalOpen] = useState(false);
  const [confirmAction, setConfirmAction] = useState(''); // 'approve' or 'cancel'
  
  // State for alert
  const [alert, setAlert] = useState(null);
  
  // Fetch adjustment details
  const { 
    data: adjustmentData, 
    isLoading, 
    isError, 
    error 
  } = useQuery(
    ['stockAdjustment', id],
    () => stockAdjustmentService.getById(id),
    {
      onError: (err) => {
        setAlert({
          type: 'error',
          title: 'Error',
          message: 'Failed to load adjustment details. Please try again.'
        });
      }
    }
  );
  
  // Approve adjustment mutation
  const approveMutation = useMutation(
    (id) => stockAdjustmentService.approve(id),
    {
      onSuccess: () => {
        queryClient.invalidateQueries(['stockAdjustment', id]);
        queryClient.invalidateQueries('stockAdjustments');
        setAlert({
          type: 'success',
          title: 'Adjustment Approved',
          message: 'The stock adjustment has been successfully approved and inventory has been updated.'
        });
        setConfirmModalOpen(false);
      },
      onError: (err) => {
        setAlert({
          type: 'error',
          title: 'Approve Failed',
          message: err.response?.data?.message || 'Failed to approve the adjustment. Please try again.'
        });
        setConfirmModalOpen(false);
      }
    }
  );
  
  // Cancel adjustment mutation
  const cancelMutation = useMutation(
    (id) => stockAdjustmentService.cancel(id),
    {
      onSuccess: () => {
        queryClient.invalidateQueries(['stockAdjustment', id]);
        queryClient.invalidateQueries('stockAdjustments');
        setAlert({
          type: 'success',
          title: 'Adjustment Cancelled',
          message: 'The stock adjustment has been cancelled.'
        });
        setConfirmModalOpen(false);
      },
      onError: (err) => {
        setAlert({
          type: 'error',
          title: 'Cancel Failed',
          message: err.response?.data?.message || 'Failed to cancel the adjustment. Please try again.'
        });
        setConfirmModalOpen(false);
      }
    }
  );
  
  // Open confirm modal
  const openConfirmModal = (action) => {
    setConfirmAction(action);
    setConfirmModalOpen(true);
  };
  
  // Handle confirm action
  const handleConfirmAction = () => {
    if (confirmAction === 'approve') {
      approveMutation.mutate(id);
    } else if (confirmAction === 'cancel') {
      cancelMutation.mutate(id);
    }
  };
  
  // Handle print
  const handlePrint = () => {
    window.print();
  };
  
  // Handle export
  const handleExport = async () => {
    try {
      const response = await stockAdjustmentService.export(id);
      
      // Create a blob from the response data
      const blob = new Blob([response.data], { type: response.headers['content-type'] });
      const url = window.URL.createObjectURL(blob);
      
      // Create a temporary link element and trigger download
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `adjustment-${id}.pdf`);
      document.body.appendChild(link);
      link.click();
      
      // Clean up
      link.parentNode.removeChild(link);
      window.URL.revokeObjectURL(url);
    } catch (error) {
      console.error('Error exporting document:', error);
      setAlert({
        type: 'error',
        title: 'Export Failed',
        message: 'Failed to export the adjustment document. Please try again.'
      });
    }
  };
  
  // Get status info (color, icon)
  const getStatusInfo = (status) => {
    switch (status) {
      case 'Approved':
        return { color: 'green', icon: CheckCircle };
      case 'Cancelled':
        return { color: 'red', icon: XCircle };
      default: // Draft
        return { color: 'yellow', icon: Clock };
    }
  };
  
  // Calculate totals
  const calculateTotals = () => {
    if (!adjustmentData?.data?.lines) return { totalItems: 0, totalPositive: 0, totalNegative: 0 };
    
    const lines = adjustmentData.data.lines;
    const totalItems = lines.length;
    const totalPositive = lines.filter(line => line.variance_type === 'POSITIVE').length;
    const totalNegative = lines.filter(line => line.variance_type === 'NEGATIVE').length;
    
    return { totalItems, totalPositive, totalNegative };
  };
  
  const adjustment = adjustmentData?.data;
  const isLoaded = !isLoading && !isError && adjustment;
  const statusInfo = isLoaded ? getStatusInfo(adjustment.status) : { color: 'gray', icon: Clock };
  const totals = calculateTotals();
  const StatusIcon = statusInfo.icon;
  
  return (
    <div className="print:bg-white print:w-full print:max-w-none">
      <div className="print:hidden">
        <PageHeader 
          title="Stock Adjustment Details" 
          subtitle={isLoaded ? `Reference: ${adjustment.reference_document || `ADJ-${adjustment.adjustment_id}`}` : 'Loading...'}
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
      </div>
      
      {isLoading ? (
        <div className="flex justify-center items-center h-64">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-500"></div>
          <span className="ml-3 text-lg text-gray-700">Loading adjustment details...</span>
        </div>
      ) : isError ? (
        <div className="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
          <div className="flex">
            <div className="flex-shrink-0">
              <AlertTriangle className="h-5 w-5 text-red-400" />
            </div>
            <div className="ml-3">
              <p className="text-sm text-red-700">
                Error loading adjustment details. Please try refreshing the page or go back to the adjustments list.
              </p>
            </div>
          </div>
        </div>
      ) : (
        <div className="space-y-6">
          {/* Actions Bar (only visible on non-print) */}
          <Card className="print:hidden">
            <CardBody>
              <div className="flex flex-wrap justify-between items-center gap-2">
                <Button 
                  variant="light" 
                  onClick={() => navigate('/inventory/adjustments')}
                >
                  <ArrowLeft className="h-4 w-4 mr-2" />
                  Back to List
                </Button>
                
                <div className="flex flex-wrap space-x-2">
                  <Button
                    variant="secondary"
                    onClick={handlePrint}
                  >
                    <Printer className="h-4 w-4 mr-2" />
                    Print
                  </Button>
                  
                  <Button
                    variant="secondary"
                    onClick={handleExport}
                  >
                    <Download className="h-4 w-4 mr-2" />
                    Export PDF
                  </Button>
                  
                  {adjustment.status === 'Draft' && (
                    <>
                      <Button
                        variant="success"
                        onClick={() => openConfirmModal('approve')}
                        disabled={approveMutation.isLoading}
                      >
                        <CheckCircle className="h-4 w-4 mr-2" />
                        Approve
                      </Button>
                      
                      <Button
                        variant="danger"
                        onClick={() => openConfirmModal('cancel')}
                        disabled={cancelMutation.isLoading}
                      >
                        <XCircle className="h-4 w-4 mr-2" />
                        Cancel
                      </Button>
                    </>
                  )}
                </div>
              </div>
            </CardBody>
          </Card>
          
          {/* Header Section */}
          <Card>
            <CardBody className="p-6">
              <div className="flex flex-col md:flex-row justify-between md:items-center">
                <div>
                  <h1 className="text-2xl font-bold text-gray-900">Stock Adjustment</h1>
                  <p className="text-sm text-gray-500 mt-1">
                    Reference: {adjustment.reference_document || `ADJ-${adjustment.adjustment_id}`}
                  </p>
                </div>
                
                <div className="mt-4 md:mt-0 flex items-center">
                  <span className={`px-3 py-1 rounded-full text-sm font-medium bg-${statusInfo.color}-100 text-${statusInfo.color}-800 flex items-center`}>
                    <StatusIcon className="h-4 w-4 mr-1.5" />
                    {adjustment.status}
                  </span>
                </div>
              </div>
              
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mt-8">
                <div>
                  <p className="text-sm font-medium text-gray-500">Adjustment Date</p>
                  <p className="mt-1 flex items-center text-sm text-gray-900">
                    <Calendar className="h-4 w-4 mr-1.5 text-gray-400" />
                    {new Date(adjustment.adjustment_date).toLocaleDateString()}
                  </p>
                </div>
                
                <div>
                  <p className="text-sm font-medium text-gray-500">Reason</p>
                  <p className="mt-1 flex items-center text-sm text-gray-900">
                    <File className="h-4 w-4 mr-1.5 text-gray-400" />
                    {adjustment.adjustment_reason}
                  </p>
                </div>
                
                {adjustment.status !== 'Draft' && (
                  <>
                    <div>
                      <p className="text-sm font-medium text-gray-500">
                        {adjustment.status === 'Approved' ? 'Approved Date' : 'Cancelled Date'}
                      </p>
                      <p className="mt-1 flex items-center text-sm text-gray-900">
                        <Calendar className="h-4 w-4 mr-1.5 text-gray-400" />
                        {new Date(adjustment.status_date || adjustment.updated_at).toLocaleDateString()}
                      </p>
                    </div>
                    
                    <div>
                      <p className="text-sm font-medium text-gray-500">
                        {adjustment.status === 'Approved' ? 'Approved By' : 'Cancelled By'}
                      </p>
                      <p className="mt-1 flex items-center text-sm text-gray-900">
                        {adjustment.status_user || '-'}
                      </p>
                    </div>
                  </>
                )}
              </div>
              
              {adjustment.notes && (
                <div className="mt-6 p-4 bg-gray-50 rounded-md">
                  <p className="text-sm font-medium text-gray-500">Notes</p>
                  <p className="mt-1 text-sm text-gray-900 whitespace-pre-line">{adjustment.notes}</p>
                </div>
              )}
            </CardBody>
          </Card>
          
          {/* Adjustment Items */}
          <Card>
            <CardHeader 
              title="Adjustment Items" 
              subtitle={`${totals.totalItems} items (${totals.totalPositive} positive, ${totals.totalNegative} negative adjustments)`}
            />
            <CardBody className="p-0">
              <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-gray-200">
                  <thead className="bg-gray-50">
                    <tr>
                      <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Item
                      </th>
                      <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Book Quantity
                      </th>
                      <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actual Quantity
                      </th>
                      <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Variance
                      </th>
                      <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Notes
                      </th>
                    </tr>
                  </thead>
                  <tbody className="bg-white divide-y divide-gray-200">
                    {adjustment.lines?.map((line, index) => (
                      <tr key={index}>
                        <td className="px-6 py-4 whitespace-nowrap">
                          <div className="font-medium text-gray-900">{line.item?.name}</div>
                          <div className="text-sm text-gray-500">{line.item?.item_code}</div>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                          <div className="text-sm text-gray-900">
                            {line.book_quantity} {line.item?.unitOfMeasure?.symbol || ''}
                          </div>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                          <div className="text-sm text-gray-900">
                            {line.actual_quantity} {line.item?.unitOfMeasure?.symbol || ''}
                          </div>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                          <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                            line.variance_type === 'POSITIVE'
                              ? 'bg-green-100 text-green-800'
                              : 'bg-red-100 text-red-800'
                          }`}>
                            {line.variance_type === 'POSITIVE' ? '+' : '-'}{line.variance} {line.item?.unitOfMeasure?.symbol || ''}
                          </span>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                          {line.notes || '-'}
                        </td>
                      </tr>
                    ))}
                    
                    {(!adjustment.lines || adjustment.lines.length === 0) && (
                      <tr>
                        <td colSpan="5" className="px-6 py-4 text-center text-sm text-gray-500">
                          No items in this adjustment
                        </td>
                      </tr>
                    )}
                  </tbody>
                </table>
              </div>
            </CardBody>
          </Card>
          
          {/* Additional Information (only for approved adjustments) */}
          {adjustment.status === 'Approved' && adjustment.transaction_ids && (
            <Card className="print:hidden">
              <CardHeader 
                title="Stock Transactions" 
                subtitle="Inventory transactions created by this adjustment"
              />
              <CardBody>
                <div className="p-4 bg-green-50 border-l-4 border-green-400 text-green-700">
                  <p className="text-sm">
                    <strong>Note:</strong> This adjustment has been approved and inventory quantities have been updated accordingly.
                    {adjustment.transaction_ids.length} stock transactions were created.
                  </p>
                </div>
              </CardBody>
            </Card>
          )}
        </div>
      )}
      
      {/* Confirm Modal */}
      <Modal
        isOpen={confirmModalOpen}
        onClose={() => setConfirmModalOpen(false)}
        title={confirmAction === 'approve' ? 'Approve Adjustment' : 'Cancel Adjustment'}
        size="sm"
      >
        <div className="py-3">
          {confirmAction === 'approve' ? (
            <>
              <p className="text-gray-700">
                Are you sure you want to approve this stock adjustment? This will update the inventory quantities.
              </p>
              <div className="mt-4 p-3 bg-yellow-50 border-l-4 border-yellow-400 text-yellow-700">
                <div className="flex">
                  <AlertTriangle className="h-5 w-5 mr-2" />
                  <p>
                    <strong>Warning:</strong> This action cannot be undone. Once approved, the adjustment will affect inventory levels.
                  </p>
                </div>
              </div>
            </>
          ) : (
            <>
              <p className="text-gray-700">
                Are you sure you want to cancel this stock adjustment? Cancelled adjustments cannot be approved later.
              </p>
              <div className="mt-4 p-3 bg-yellow-50 border-l-4 border-yellow-400 text-yellow-700">
                <div className="flex">
                  <AlertTriangle className="h-5 w-5 mr-2" />
                  <p>
                    <strong>Warning:</strong> This action cannot be undone. The adjustment will be marked as cancelled.
                  </p>
                </div>
              </div>
            </>
          )}
        </div>
        
        <div className="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
          <Button
            variant={confirmAction === 'approve' ? 'success' : 'danger'}
            onClick={handleConfirmAction}
            isLoading={approveMutation.isLoading || cancelMutation.isLoading}
            className="w-full sm:ml-3 sm:w-auto"
          >
            {confirmAction === 'approve' ? 'Approve' : 'Cancel Adjustment'}
          </Button>
          <Button
            variant="light"
            onClick={() => setConfirmModalOpen(false)}
            className="mt-3 w-full sm:mt-0 sm:w-auto"
          >
            Go Back
          </Button>
        </div>
      </Modal>
    </div>
  );
};

export default StockAdjustmentDetail;