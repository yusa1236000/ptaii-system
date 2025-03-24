// src/pages/inventory/settings/InventorySettings.jsx
import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from 'react-query';
import { Save, RefreshCw, Settings, AlertTriangle, CheckSquare, Calendar, DollarSign, Tag, Package } from 'lucide-react';
import { inventorySettingService } from '../../../services/inventoryService';
import PageHeader from '../../../components/common/PageHeader';
import { Card, CardBody, CardHeader, CardFooter } from '../../../components/common/Card';
import { 
  FormGroup, 
  Label, 
  Input, 
  Select, 
  Checkbox,
  Button 
} from '../../../components/common/FormElements';
import Alert from '../../../components/common/Alert';

const InventorySettings = () => {
  const queryClient = useQueryClient();
  
  // State for form alert
  const [formAlert, setFormAlert] = useState(null);
  
  // Fetch current settings
  const { 
    data: settingsData, 
    isLoading: settingsLoading, 
    isError: settingsError,
    refetch: refetchSettings
  } = useQuery('inventory-settings', inventorySettingService.getSettings, {
    onSuccess: (data) => {
      setFormData(data.data);
    }
  });
  
  // State for settings form
  const [formData, setFormData] = useState({
    default_valuation_method: 'average_cost',
    low_stock_threshold_percentage: 20,
    enable_batch_tracking: true,
    enable_expiry_date_tracking: true,
    enable_auto_reorder: false,
    default_reorder_point_percentage: 30,
    enable_serial_number_tracking: false,
    enable_location_tracking: true,
    require_stock_before_sale: true,
    enable_negative_stock: false,
    default_stock_adjustment_approval: true,
    inventory_count_frequency: 'quarterly',
    preferred_cost_method: 'last_purchase_price'
  });
  
  // Update settings mutation
  const updateSettingsMutation = useMutation(
    (data) => inventorySettingService.updateSettings(data),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('inventory-settings');
        setFormAlert({
          type: 'success',
          title: 'Settings Updated',
          message: 'Inventory settings have been successfully updated.'
        });
      },
      onError: (error) => {
        setFormAlert({
          type: 'error',
          title: 'Update Failed',
          message: error.response?.data?.message || 'Failed to update settings. Please try again.'
        });
      }
    }
  );
  
  // Handle form input changes
  const handleChange = (e) => {
    const { name, value, type, checked } = e.target;
    
    setFormData((prev) => ({
      ...prev,
      [name]: type === 'checkbox' ? checked : value
    }));
  };
  
  // Handle form submission
  const handleSubmit = (e) => {
    e.preventDefault();
    updateSettingsMutation.mutate(formData);
  };
  
  // Handle reset to defaults
  const handleResetDefaults = () => {
    if (window.confirm('Are you sure you want to reset all settings to default values? This cannot be undone.')) {
      const defaultSettings = {
        default_valuation_method: 'average_cost',
        low_stock_threshold_percentage: 20,
        enable_batch_tracking: true,
        enable_expiry_date_tracking: true,
        enable_auto_reorder: false,
        default_reorder_point_percentage: 30,
        enable_serial_number_tracking: false,
        enable_location_tracking: true,
        require_stock_before_sale: true,
        enable_negative_stock: false,
        default_stock_adjustment_approval: true,
        inventory_count_frequency: 'quarterly',
        preferred_cost_method: 'last_purchase_price'
      };
      
      setFormData(defaultSettings);
      updateSettingsMutation.mutate(defaultSettings);
    }
  };
  
  if (settingsLoading) {
    return (
      <div>
        <PageHeader 
          title="Inventory Settings" 
          subtitle="Configure your inventory management preferences"
        />
        <Card>
          <CardBody>
            <div className="flex justify-center items-center h-64">
              <RefreshCw className="h-8 w-8 animate-spin text-gray-400" />
              <span className="ml-2 text-gray-600">Loading settings...</span>
            </div>
          </CardBody>
        </Card>
      </div>
    );
  }
  
  if (settingsError) {
    return (
      <div>
        <PageHeader 
          title="Inventory Settings" 
          subtitle="Configure your inventory management preferences"
        />
        <Alert
          type="error"
          title="Error Loading Settings"
          message="Failed to load inventory settings. Please refresh the page or contact support."
        />
      </div>
    );
  }
  
  return (
    <div>
      <PageHeader 
        title="Inventory Settings" 
        subtitle="Configure your inventory management preferences"
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
      
      <form onSubmit={handleSubmit}>
        <div className="space-y-6">
          {/* Valuation & Costing Settings */}
          <Card>
            <CardHeader 
              title="Valuation & Costing" 
              subtitle="Configure how inventory items are valued and costs are calculated"
              action={<DollarSign className="h-5 w-5 text-gray-400" />}
            />
            <CardBody>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <FormGroup>
                  <Label htmlFor="default_valuation_method">Default Valuation Method</Label>
                  <Select
                    id="default_valuation_method"
                    name="default_valuation_method"
                    value={formData.default_valuation_method}
                    onChange={handleChange}
                    options={[
                      { value: 'average_cost', label: 'Average Cost' },
                      { value: 'fifo', label: 'FIFO (First In, First Out)' },
                      { value: 'lifo', label: 'LIFO (Last In, First Out)' }
                    ]}
                  />
                </FormGroup>
                
                <FormGroup>
                  <Label htmlFor="preferred_cost_method">Preferred Cost Method</Label>
                  <Select
                    id="preferred_cost_method"
                    name="preferred_cost_method"
                    value={formData.preferred_cost_method}
                    onChange={handleChange}
                    options={[
                      { value: 'last_purchase_price', label: 'Last Purchase Price' },
                      { value: 'average_purchase_price', label: 'Average Purchase Price' },
                      { value: 'standard_cost', label: 'Standard Cost' }
                    ]}
                  />
                </FormGroup>
              </div>
            </CardBody>
          </Card>
          
          {/* Inventory Tracking Settings */}
          <Card>
            <CardHeader 
              title="Inventory Tracking" 
              subtitle="Configure item-level tracking options"
              action={<Package className="h-5 w-5 text-gray-400" />}
            />
            <CardBody>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <FormGroup>
                    <Checkbox
                      id="enable_batch_tracking"
                      name="enable_batch_tracking"
                      checked={formData.enable_batch_tracking}
                      onChange={handleChange}
                      label="Enable Batch/Lot Tracking"
                    />
                    <p className="ml-6 mt-1 text-sm text-gray-500">
                      Track items by batch or lot number
                    </p>
                  </FormGroup>
                  
                  <FormGroup>
                    <Checkbox
                      id="enable_expiry_date_tracking"
                      name="enable_expiry_date_tracking"
                      checked={formData.enable_expiry_date_tracking}
                      onChange={handleChange}
                      label="Enable Expiry Date Tracking"
                    />
                    <p className="ml-6 mt-1 text-sm text-gray-500">
                      Track expiration dates for perishable items
                    </p>
                  </FormGroup>
                  
                  <FormGroup>
                    <Checkbox
                      id="enable_serial_number_tracking"
                      name="enable_serial_number_tracking"
                      checked={formData.enable_serial_number_tracking}
                      onChange={handleChange}
                      label="Enable Serial Number Tracking"
                    />
                    <p className="ml-6 mt-1 text-sm text-gray-500">
                      Track individual items by unique serial numbers
                    </p>
                  </FormGroup>
                </div>
                
                <div>
                  <FormGroup>
                    <Checkbox
                      id="enable_location_tracking"
                      name="enable_location_tracking"
                      checked={formData.enable_location_tracking}
                      onChange={handleChange}
                      label="Enable Location Tracking"
                    />
                    <p className="ml-6 mt-1 text-sm text-gray-500">
                      Track item locations within warehouses
                    </p>
                  </FormGroup>
                  
                  <FormGroup>
                    <Label htmlFor="inventory_count_frequency">Inventory Count Frequency</Label>
                    <Select
                      id="inventory_count_frequency"
                      name="inventory_count_frequency"
                      value={formData.inventory_count_frequency}
                      onChange={handleChange}
                      options={[
                        { value: 'monthly', label: 'Monthly' },
                        { value: 'quarterly', label: 'Quarterly' },
                        { value: 'biannually', label: 'Biannually' },
                        { value: 'annually', label: 'Annually' }
                      ]}
                    />
                  </FormGroup>
                </div>
              </div>
            </CardBody>
          </Card>
          
          {/* Stock Level Settings */}
          <Card>
            <CardHeader 
              title="Stock Levels" 
              subtitle="Configure stock threshold and ordering preferences"
              action={<Tag className="h-5 w-5 text-gray-400" />}
            />
            <CardBody>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <FormGroup>
                  <Label htmlFor="low_stock_threshold_percentage">Low Stock Threshold (%)</Label>
                  <Input
                    id="low_stock_threshold_percentage"
                    name="low_stock_threshold_percentage"
                    type="number"
                    min="5"
                    max="100"
                    value={formData.low_stock_threshold_percentage}
                    onChange={handleChange}
                  />
                  <p className="mt-1 text-sm text-gray-500">
                    Percentage of minimum stock level that triggers low stock warnings
                  </p>
                </FormGroup>
                
                <FormGroup>
                  <Checkbox
                    id="enable_auto_reorder"
                    name="enable_auto_reorder"
                    checked={formData.enable_auto_reorder}
                    onChange={handleChange}
                    label="Enable Automatic Reordering"
                  />
                  <p className="ml-6 mt-1 text-sm text-gray-500">
                    Automatically create purchase requisitions for low stock items
                  </p>
                  
                  {formData.enable_auto_reorder && (
                    <div className="mt-4">
                      <Label htmlFor="default_reorder_point_percentage">Reorder Point (%)</Label>
                      <Input
                        id="default_reorder_point_percentage"
                        name="default_reorder_point_percentage"
                        type="number"
                        min="10"
                        max="100"
                        value={formData.default_reorder_point_percentage}
                        onChange={handleChange}
                      />
                    </div>
                  )}
                </FormGroup>
              </div>
              
              <div className="mt-6 space-y-4">
                <FormGroup>
                  <Checkbox
                    id="require_stock_before_sale"
                    name="require_stock_before_sale"
                    checked={formData.require_stock_before_sale}
                    onChange={handleChange}
                    label="Require Stock Before Sale"
                  />
                  <p className="ml-6 mt-1 text-sm text-gray-500">
                    Prevent sales if stock is not available
                  </p>
                </FormGroup>
                
                <FormGroup>
                  <Checkbox
                    id="enable_negative_stock"
                    name="enable_negative_stock"
                    checked={formData.enable_negative_stock}
                    onChange={handleChange}
                    label="Allow Negative Stock"
                  />
                  <p className="ml-6 mt-1 text-sm text-gray-500">
                    Allow stock quantities to go below zero
                  </p>
                </FormGroup>
              </div>
            </CardBody>
          </Card>
          
          {/* Approval Settings */}
          <Card>
            <CardHeader 
              title="Approvals" 
              subtitle="Configure approval requirements"
              action={<CheckSquare className="h-5 w-5 text-gray-400" />}
            />
            <CardBody>
              <FormGroup>
                <Checkbox
                  id="default_stock_adjustment_approval"
                  name="default_stock_adjustment_approval"
                  checked={formData.default_stock_adjustment_approval}
                  onChange={handleChange}
                  label="Require Approval for Stock Adjustments"
                />
                <p className="ml-6 mt-1 text-sm text-gray-500">
                  Stock adjustments must be approved before they affect inventory
                </p>
              </FormGroup>
            </CardBody>
          </Card>
        </div>
        
        <div className="mt-6 flex justify-between">
          <Button
            type="button"
            variant="danger"
            onClick={handleResetDefaults}
          >
            Reset to Defaults
          </Button>
          
          <Button
            type="submit"
            variant="primary"
            isLoading={updateSettingsMutation.isLoading}
          >
            <Save className="h-4 w-4 mr-2" />
            Save Settings
          </Button>
        </div>
      </form>
    </div>
  );
}

export default InventorySettings;