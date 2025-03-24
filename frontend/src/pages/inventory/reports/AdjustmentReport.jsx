// src/pages/inventory/reports/AdjustmentReport.jsx
import React, { useState } from 'react';
import { useQuery } from 'react-query';
import { Download, Filter, RefreshCw, AlertTriangle, Check, Calendar } from 'lucide-react';
import { inventoryReportService } from '../../../services/inventoryService';
import PageHeader from '../../../components/common/PageHeader';
import Table from '../../../components/common/Table';
import { Card, CardBody, CardHeader } from '../../../components/common/Card';
import { 
  FormGroup, 
  Label, 
  Input, 
  Select, 
  Button 
} from '../../../components/common/FormElements';
import Alert from '../../../components/common/Alert';
import { PieChart, Pie, Cell, ResponsiveContainer, Legend, Tooltip } from 'recharts';

const AdjustmentReport = () => {
  // State for filters
  const [filters, setFilters] = useState({
    status: '',
    date_from: '',
    date_to: new Date().toISOString().split('T')[0],
    adjustment_reason: ''
  });
  
  // Handle filter changes
  const handleFilterChange = (name, value) => {
    setFilters((prev) => ({
      ...prev,
      [name]: value,
    }));
  };
  
  // Handle reset filters
  const handleResetFilters = () => {
    setFilters({
      status: '',
      date_from: '',
      date_to: new Date().toISOString().split('T')[0],
      adjustment_reason: ''
    });
  };
  
  // Fetch report data
  const { 
    data: reportData, 
    isLoading: reportLoading, 
    isError: reportError,
    refetch: refetchReport
  } = useQuery(
    ['adjustmentReport', filters],
    () => inventoryReportService.getAdjustmentReport(filters)
  );
  
  // Handle export
  const handleExport = async () => {
    try {
      const response = await inventoryReportService.exportAdjustmentReport(filters);
      
      // Create a blob from the response data
      const blob = new Blob([response.data], { type: response.headers['content-type'] });
      const url = window.URL.createObjectURL(blob);
      
      // Create a temporary link element and trigger download
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `adjustment-report-${new Date().toISOString().split('T')[0]}.xlsx`);
      document.body.appendChild(link);
      link.click();
      
      // Clean up
      link.parentNode.removeChild(link);
      window.URL.revokeObjectURL(url);
    } catch (error) {
      console.error('Error exporting report:', error);
    }
  };
  
  // Define table columns
  const columns = [
    {
      key: 'adjustment_number',
      label: 'Adjustment #',
      render: (adjustment) => (
        <div className="font-medium text-blue-600 hover:text-blue-800">
          {adjustment.adjustment_number}
        </div>
      ),
    },
    {
      key: 'date',
      label: 'Date',
      render: (adjustment) => (
        <div className="whitespace-nowrap text-sm text-gray-900">
          {new Date(adjustment.adjustment_date).toLocaleDateString()}
        </div>
      ),
    },
    {
      key: 'items',
      label: 'Items',
      render: (adjustment) => (
        <div className="text-sm text-gray-900">
          {adjustment.items_count} {adjustment.items_count === 1 ? 'item' : 'items'}
        </div>
      ),
    },
    {
      key: 'value',
      label: 'Total Value',
      render: (adjustment) => (
        <div className="text-sm font-medium text-gray-900">
          ${adjustment.total_value.toFixed(2)}
        </div>
      ),
    },
    {
      key: 'reason',
      label: 'Reason',
      render: (adjustment) => (
        <div className="text-sm text-gray-900 max-w-xs truncate">
          {adjustment.adjustment_reason}
        </div>
      ),
    },
    {
      key: 'status',
      label: 'Status',
      render: (adjustment) => {
        let statusClass = 'bg-gray-100 text-gray-800'; // Draft
        let Icon = AlertTriangle;
        
        if (adjustment.status === 'Approved') {
          statusClass = 'bg-green-100 text-green-800';
          Icon = Check;
        } else if (adjustment.status === 'Cancelled') {
          statusClass = 'bg-red-100 text-red-800';
          Icon = AlertTriangle;
        }
        
        return (
          <span className={`px-2 py-1 inline-flex items-center text-xs leading-5 font-semibold rounded-full ${statusClass}`}>
            <Icon className="h-3 w-3 mr-1" />
            {adjustment.status}
          </span>
        );
      },
    },
    {
      key: 'reference',
      label: 'Reference',
      render: (adjustment) => (
        <div className="text-sm text-gray-500">
          {adjustment.reference_document || '-'}
        </div>
      ),
    }
  ];
  
  // Prepare data for pie chart
  const getPieChartData = () => {
    if (!reportData?.summary) return [];
    
    return [
      { name: 'Physical Count', value: reportData.summary.physical_count_adjustments },
      { name: 'Damage/Loss', value: reportData.summary.damage_loss_adjustments },
      { name: 'Expiry', value: reportData.summary.expiry_adjustments },
      { name: 'Other', value: reportData.summary.other_adjustments }
    ];
  };
  
  const COLORS = ['#0088FE', '#00C49F', '#FFBB28', '#FF8042'];
  
  return (
    <div>
      <PageHeader 
        title="Inventory Adjustments Report" 
        subtitle="View and analyze all stock adjustments"
        actionLabel="Export to Excel"
        onActionClick={handleExport}
      />
      
      {/* Filters */}
      <Card className="mb-6">
        <CardBody>
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
            <FormGroup>
              <Label htmlFor="status">Status</Label>
              <Select
                id="status"
                value={filters.status}
                onChange={(e) => handleFilterChange('status', e.target.value)}
                options={[
                  { value: 'Draft', label: 'Draft' },
                  { value: 'Approved', label: 'Approved' },
                  { value: 'Cancelled', label: 'Cancelled' },
                ]}
                placeholder="All Status"
              />
            </FormGroup>
            
            <FormGroup>
              <Label htmlFor="adjustment_reason">Reason</Label>
              <Select
                id="adjustment_reason"
                value={filters.adjustment_reason}
                onChange={(e) => handleFilterChange('adjustment_reason', e.target.value)}
                options={[
                  { value: 'Physical Count', label: 'Physical Count' },
                  { value: 'Damage/Loss', label: 'Damage/Loss' },
                  { value: 'Expiry', label: 'Expiry' },
                  { value: 'Quality Issues', label: 'Quality Issues' },
                  { value: 'Other', label: 'Other' },
                ]}
                placeholder="All Reasons"
              />
            </FormGroup>
            
            <FormGroup>
              <Label htmlFor="date_from">Date From</Label>
              <Input
                id="date_from"
                type="date"
                value={filters.date_from}
                onChange={(e) => handleFilterChange('date_from', e.target.value)}
                max={filters.date_to || undefined}
              />
            </FormGroup>
            
            <FormGroup>
              <Label htmlFor="date_to">Date To</Label>
              <Input
                id="date_to"
                type="date"
                value={filters.date_to}
                onChange={(e) => handleFilterChange('date_to', e.target.value)}
                min={filters.date_from || undefined}
              />
            </FormGroup>
            
            <div className="md:col-span-4 flex justify-end space-x-2">
              <Button
                variant="secondary"
                onClick={handleResetFilters}
              >
                <Filter className="h-4 w-4 mr-2" />
                Reset Filters
              </Button>
              <Button
                variant="primary"
                onClick={() => refetchReport()}
              >
                <RefreshCw className="h-4 w-4 mr-2" />
                Refresh Report
              </Button>
            </div>
          </div>
        </CardBody>
      </Card>
      
      {/* Summary Section */}
      {reportData?.summary && (
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
          <Card className="md:col-span-2">
            <CardHeader title="Adjustment Summary" />
            <CardBody>
              <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                <div className="text-center p-4 bg-gray-50 rounded-lg">
                  <div className="text-sm text-gray-500">Total Adjustments</div>
                  <div className="text-2xl font-bold text-gray-900">{reportData.summary.total_adjustments}</div>
                </div>
                <div className="text-center p-4 bg-green-50 rounded-lg">
                  <div className="text-sm text-green-500">Approved</div>
                  <div className="text-2xl font-bold text-green-700">{reportData.summary.approved_adjustments}</div>
                </div>
                <div className="text-center p-4 bg-yellow-50 rounded-lg">
                  <div className="text-sm text-yellow-500">Draft</div>
                  <div className="text-2xl font-bold text-yellow-700">{reportData.summary.draft_adjustments}</div>
                </div>
                <div className="text-center p-4 bg-red-50 rounded-lg">
                  <div className="text-sm text-red-500">Cancelled</div>
                  <div className="text-2xl font-bold text-red-700">{reportData.summary.cancelled_adjustments}</div>
                </div>
              </div>
              
              <div className="grid grid-cols-2 gap-4">
                <div className="p-4 bg-blue-50 rounded-lg">
                  <div className="text-sm text-blue-500 mb-1">Total Items Adjusted</div>
                  <div className="text-xl font-bold text-blue-700">{reportData.summary.total_items_adjusted}</div>
                </div>
                <div className="p-4 bg-purple-50 rounded-lg">
                  <div className="text-sm text-purple-500 mb-1">Total Adjustment Value</div>
                  <div className="text-xl font-bold text-purple-700">${reportData.summary.total_adjustment_value.toFixed(2)}</div>
                </div>
              </div>
            </CardBody>
          </Card>
          
          <Card>
            <CardHeader title="Adjustment Reasons" />
            <CardBody className="h-60">
              <ResponsiveContainer width="100%" height="100%">
                <PieChart>
                  <Pie
                    data={getPieChartData()}
                    cx="50%"
                    cy="50%"
                    labelLine={false}
                    outerRadius={80}
                    fill="#8884d8"
                    dataKey="value"
                    label={({ name, percent }) => `${name}: ${(percent * 100).toFixed(0)}%`}
                  >
                    {getPieChartData().map((entry, index) => (
                      <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                    ))}
                  </Pie>
                  <Tooltip />
                  <Legend />
                </PieChart>
              </ResponsiveContainer>
            </CardBody>
          </Card>
        </div>
      )}
      
      {/* Report Table */}
      {reportError && (
        <Alert
          type="error"
          title="Error loading report"
          message="There was an error loading the report. Please try again later."
          className="mb-6"
        />
      )}
      
      <Table
        columns={columns}
        data={reportData?.data || []}
        isLoading={reportLoading}
        emptyMessage="No adjustments found matching your filters."
        pagination={reportData?.meta}
        onPageChange={(page) => handleFilterChange('page', page)}
      />
    </div>
  );
};

export default AdjustmentReport;

