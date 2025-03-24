// src/pages/inventory/warehouses/WarehouseDetail.jsx
import React, { useState, useEffect } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from 'react-query';
import { 
  Edit, 
  Trash, 
  Map, 
  Plus, 
  Grid, 
  CheckSquare, 
  ChevronRight,
  X,
  AlertTriangle
} from 'lucide-react';
import { warehouseService } from '../../../services/inventoryService';
import PageHeader from '../../../components/common/PageHeader';
import { Card, CardHeader, CardBody, CardFooter } from '../../../components/common/Card';
import { 
  FormGroup, 
  Label, 
  Input, 
  Select, 
  Checkbox,
  Button, 
  Textarea 
} from '../../../components/common/FormElements';
import Alert from '../../../components/common/Alert';
import Modal from '../../../components/common/Modal';
import Table from '../../../components/common/Table';

const WarehouseDetail = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  
  // State for zone modal
  const [zoneModalOpen, setZoneModalOpen] = useState(false);
  const [zoneFormData, setZoneFormData] = useState({
    name: '',
    code: '',
    description: '',
  });
  const [zoneModalMode, setZoneModalMode] = useState('create'); // 'create' or 'edit'
  const [selectedZoneId, setSelectedZoneId] = useState(null);
  
  // State for location modal
  const [locationModalOpen, setLocationModalOpen] = useState(false);
  const [locationFormData, setLocationFormData] = useState({
    code: '',
    description: '',
    zone_id: '',
    is_pickable: true,
    is_receivable: true,
    max_weight: '',
    max_volume: '',
  });
  const [locationModalMode, setLocationModalMode] = useState('create'); // 'create' or 'edit'
  const [selectedLocationId, setSelectedLocationId] = useState(null);
  
  // State for delete modal
  const [deleteModalOpen, setDeleteModalOpen] = useState(false);
  const [deleteItemType, setDeleteItemType] = useState(''); // 'warehouse', 'zone', or 'location'
  const [deleteItemId, setDeleteItemId] = useState(null);
  
  // State for form alert
  const [formAlert, setFormAlert] = useState(null);
  
  // Fetch warehouse data
  const { 
    data: warehouseData, 
    isLoading: warehouseLoading, 
    isError: warehouseError,
    refetch: refetchWarehouse
  } = useQuery(
    ['warehouse', id],
    () => warehouseService.getById(id),
    {
      enabled: !!id
    }
  );
  
  // Create zone mutation
  const createZoneMutation = useMutation(
    (data) => warehouseService.createZone(id, data),
    {
      onSuccess: () => {
        queryClient.invalidateQueries(['warehouse', id]);
        setFormAlert({
          type: 'success',
          title: 'Zone Created',
          message: 'Warehouse zone has been successfully created.'
        });
        setZoneModalOpen(false);
        resetZoneForm();
      },
      onError: (error) => {
        setFormAlert({
          type: 'error',
          title: 'Error Creating Zone',
          message: error.response?.data?.message || 'Failed to create zone. Please try again.'
        });
      }
    }
  );
  
  // Update zone mutation
  const updateZoneMutation = useMutation(
    ({ zoneId, data }) => warehouseService.updateZone(id, zoneId, data),
    {
      onSuccess: () => {
        queryClient.invalidateQueries(['warehouse', id]);
        setFormAlert({
          type: 'success',
          title: 'Zone Updated',
          message: 'Warehouse zone has been successfully updated.'
        });
        setZoneModalOpen(false);
        resetZoneForm();
      },
      onError: (error) => {
        setFormAlert({
          type: 'error',
          title: 'Error Updating Zone',
          message: error.response?.data?.message || 'Failed to update zone. Please try again.'
        });
      }
    }
  );
  
  // Create location mutation
  const createLocationMutation = useMutation(
    (data) => warehouseService.createLocation(id, data.zone_id, data),
    {
      onSuccess: () => {
        queryClient.invalidateQueries(['warehouse', id]);
        setFormAlert({
          type: 'success',
          title: 'Location Created',
          message: 'Warehouse location has been successfully created.'
        });
        setLocationModalOpen(false);
        resetLocationForm();
      },
      onError: (error) => {
        setFormAlert({
          type: 'error',
          title: 'Error Creating Location',
          message: error.response?.data?.message || 'Failed to create location. Please try again.'
        });
      }
    }
  );
  
  // Update location mutation
  const updateLocationMutation = useMutation(
    ({ zoneId, locationId, data }) => warehouseService.updateLocation(id, zoneId, locationId, data),
    {
      onSuccess: () => {
        queryClient.invalidateQueries(['warehouse', id]);
        setFormAlert({
          type: 'success',
          title: 'Location Updated',
          message: 'Warehouse location has been successfully updated.'
        });
        setLocationModalOpen(false);
        resetLocationForm();
      },
      onError: (error) => {
        setFormAlert({
          type: 'error',
          title: 'Error Updating Location',
          message: error.response?.data?.message || 'Failed to update location. Please try again.'
        });
      }
    }
  );
  
  // Delete warehouse mutation
  const deleteWarehouseMutation = useMutation(
    () => warehouseService.delete(id),
    {
      onSuccess: () => {
        navigate('/inventory/warehouses');
      },
      onError: (error) => {
        setFormAlert({
          type: 'error',
          title: 'Error Deleting Warehouse',
          message: error.response?.data?.message || 'Failed to delete warehouse. Please try again.'
        });
        setDeleteModalOpen(false);
      }
    }
  );
  
  // Delete zone mutation
  const deleteZoneMutation = useMutation(
    (zoneId) => warehouseService.deleteZone(id, zoneId),
    {
      onSuccess: () => {
        queryClient.invalidateQueries(['warehouse', id]);
        setFormAlert({
          type: 'success',
          title: 'Zone Deleted',
          message: 'Warehouse zone has been successfully deleted.'
        });
        setDeleteModalOpen(false);
      },
      onError: (error) => {
        setFormAlert({
          type: 'error',
          title: 'Error Deleting Zone',
          message: error.response?.data?.message || 'Failed to delete zone. Please try again.'
        });
        setDeleteModalOpen(false);
      }
    }
  );
  
  // Delete location mutation
  const deleteLocationMutation = useMutation(
    ({ zoneId, locationId }) => warehouseService.deleteLocation(id, zoneId, locationId),
    {
      onSuccess: () => {
        queryClient.invalidateQueries(['warehouse', id]);
        setFormAlert({
          type: 'success',
          title: 'Location Deleted',
          message: 'Warehouse location has been successfully deleted.'
        });
        setDeleteModalOpen(false);
      },
      onError: (error) => {
        setFormAlert({
          type: 'error',
          title: 'Error Deleting Location',
          message: error.response?.data?.message || 'Failed to delete location. Please try again.'
        });
        setDeleteModalOpen(false);
      }
    }
  );
  
  // Reset zone form
  const resetZoneForm = () => {
    setZoneFormData({
      name: '',
      code: '',
      description: '',
    });
    setSelectedZoneId(null);
  };
  
  // Reset location form
  const resetLocationForm = () => {
    setLocationFormData({
      code: '',
      description: '',
      zone_id: '',
      is_pickable: true,
      is_receivable: true,
      max_weight: '',
      max_volume: '',
    });
    setSelectedLocationId(null);
  };
  
  // Handle zone form changes
  const handleZoneChange = (e) => {
    const { name, value } = e.target;
    setZoneFormData((prev) => ({
      ...prev,
      [name]: value
    }));
  };
  
  // Handle location form changes
  const handleLocationChange = (e) => {
    const { name, value, type, checked } = e.target;
    setLocationFormData((prev) => ({
      ...prev,
      [name]: type === 'checkbox' ? checked : value
    }));
  };
  
  // Open zone modal for create
  const openCreateZoneModal = () => {
    resetZoneForm();
    setZoneModalMode('create');
    setZoneModalOpen(true);
  };
  
  // Open zone modal for edit
  const openEditZoneModal = (zone) => {
    setZoneFormData({
      name: zone.name || '',
      code: zone.code || '',
      description: zone.description || '',
    });
    setSelectedZoneId(zone.zone_id);
    setZoneModalMode('edit');
    setZoneModalOpen(true);
  };
  
  // Open location modal for create
  const openCreateLocationModal = (zoneId) => {
    resetLocationForm();
    setLocationFormData((prev) => ({
      ...prev,
      zone_id: zoneId
    }));
    setLocationModalMode('create');
    setLocationModalOpen(true);
  };
  
  // Open location modal for edit
  const openEditLocationModal = (location, zoneId) => {
    setLocationFormData({
      code: location.code || '',
      description: location.description || '',
      zone_id: zoneId || '',
      is_pickable: location.is_pickable !== false,
      is_receivable: location.is_receivable !== false,
      max_weight: location.max_weight || '',
      max_volume: location.max_volume || '',
    });
    setSelectedLocationId(location.location_id);
    setLocationModalMode('edit');
    setLocationModalOpen(true);
  };
  
  // Open delete confirmation modal
  const openDeleteModal = (type, id, zoneId = null) => {
    setDeleteItemType(type);
    if (type === 'location') {
      setDeleteItemId({ locationId: id, zoneId });
    } else {
      setDeleteItemId(id);
    }
    setDeleteModalOpen(true);
  };
  
  // Handle zone form submission
  const handleZoneSubmit = (e) => {
    e.preventDefault();
    
    if (zoneModalMode === 'create') {
      createZoneMutation.mutate(zoneFormData);
    } else {
      updateZoneMutation.mutate({ zoneId: selectedZoneId, data: zoneFormData });
    }
  };
  
  // Handle location form submission
  const handleLocationSubmit = (e) => {
    e.preventDefault();
    
    if (locationModalMode === 'create') {
      createLocationMutation.mutate(locationFormData);
    } else {
      updateLocationMutation.mutate({ 
        zoneId: locationFormData.zone_id, 
        locationId: selectedLocationId, 
        data: locationFormData 
      });
    }
  };
  
  // Handle delete confirmation
  const handleDeleteConfirm = () => {
    if (deleteItemType === 'warehouse') {
      deleteWarehouseMutation.mutate();
    } else if (deleteItemType === 'zone') {
      deleteZoneMutation.mutate(deleteItemId);
    } else if (deleteItemType === 'location') {
      deleteLocationMutation.mutate({ zoneId: deleteItemId.zoneId, locationId: deleteItemId.locationId });
    }
  };
  
  // Determine if warehouse has any zones
  const hasZones = warehouseData?.data?.zones?.length > 0;
  
  // Get warehouse data
  const warehouse = warehouseData?.data;
  
  return (
    <div>
      <PageHeader 
        title="Warehouse Details" 
        subtitle={warehouseLoading ? 'Loading...' : warehouse?.name}
        actionLabel="Edit Warehouse"
        actionUrl={`/inventory/warehouses/${id}/edit`}
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
      
      {warehouseError ? (
        <Alert
          type="error"
          title="Error Loading Warehouse"
          message="Failed to load warehouse details. Please try again or contact support."
          className="mb-6"
        />
      ) : (
        <div className="space-y-6">
          {/* Warehouse Info Card */}
          <Card>
            <CardHeader 
              title="Warehouse Information" 
              action={
                <Button 
                  variant="danger" 
                  size="sm"
                  onClick={() => openDeleteModal('warehouse', id)}
                >
                  <Trash className="h-4 w-4 mr-2" />
                  Delete Warehouse
                </Button>
              }
            />
            <CardBody>
              {warehouseLoading ? (
                <div className="animate-pulse">
                  <div className="h-4 bg-gray-200 rounded w-1/4 mb-4"></div>
                  <div className="h-4 bg-gray-200 rounded w-1/2 mb-4"></div>
                  <div className="h-4 bg-gray-200 rounded w-3/4 mb-4"></div>
                </div>
              ) : (
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <div className="mb-4">
                      <span className="block text-sm font-medium text-gray-500">Warehouse Code</span>
                      <span className="block mt-1 text-base text-gray-900">{warehouse?.code}</span>
                    </div>
                    <div className="mb-4">
                      <span className="block text-sm font-medium text-gray-500">Name</span>
                      <span className="block mt-1 text-base text-gray-900">{warehouse?.name}</span>
                    </div>
                    <div className="mb-4">
                      <span className="block text-sm font-medium text-gray-500">Status</span>
                      <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                        warehouse?.is_active 
                          ? 'bg-green-100 text-green-800' 
                          : 'bg-red-100 text-red-800'
                      }`}>
                        {warehouse?.is_active ? 'Active' : 'Inactive'}
                      </span>
                    </div>
                  </div>
                  <div>
                    <div className="mb-4">
                      <span className="block text-sm font-medium text-gray-500">Address</span>
                      <span className="block mt-1 text-base text-gray-900 whitespace-pre-line">
                        {warehouse?.address || 'No address provided'}
                      </span>
                    </div>
                  </div>
                </div>
              )}
            </CardBody>
          </Card>
          
          {/* Zones and Locations */}
          <Card>
            <CardHeader 
              title="Zones and Locations" 
              subtitle="Manage warehouse zones and storage locations"
              action={
                <Button 
                  variant="primary" 
                  size="sm"
                  onClick={openCreateZoneModal}
                >
                  <Plus className="h-4 w-4 mr-2" />
                  Add Zone
                </Button>
              }
            />
            <CardBody>
              {warehouseLoading ? (
                <div className="animate-pulse">
                  <div className="h-10 bg-gray-200 rounded mb-4"></div>
                  <div className="h-10 bg-gray-200 rounded mb-4"></div>
                </div>
              ) : !hasZones ? (
                <div className="text-center py-8">
                  <Grid className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                  <h3 className="text-lg font-medium text-gray-900">No Zones Found</h3>
                  <p className="mt-2 text-sm text-gray-500">
                    This warehouse doesn't have any zones yet. Zones help organize your warehouse into distinct areas.
                  </p>
                  <div className="mt-6">
                    <Button 
                      variant="primary"
                      onClick={openCreateZoneModal}
                    >
                      <Plus className="h-4 w-4 mr-2" />
                      Add Your First Zone
                    </Button>
                  </div>
                </div>
              ) : (
                <div className="space-y-6">
                  {warehouse?.zones?.map((zone) => (
                    <div key={zone.zone_id} className="border border-gray-200 rounded-lg overflow-hidden">
                      <div className="bg-gray-50 px-4 py-4 flex items-center justify-between">
                        <div>
                          <h3 className="text-lg font-medium text-gray-900 flex items-center">
                            <Map className="h-5 w-5 text-gray-500 mr-2" />
                            {zone.name}
                            <span className="ml-2 text-sm text-gray-500">({zone.code})</span>
                          </h3>
                          {zone.description && (
                            <p className="mt-1 text-sm text-gray-500">{zone.description}</p>
                          )}
                        </div>
                        <div className="flex items-center space-x-2">
                          <Button 
                            variant="light"
                            size="sm"
                            onClick={() => openEditZoneModal(zone)}
                          >
                            <Edit className="h-4 w-4 mr-1" />
                            Edit
                          </Button>
                          <Button 
                            variant="light"
                            size="sm"
                            onClick={() => openCreateLocationModal(zone.zone_id)}
                          >
                            <Plus className="h-4 w-4 mr-1" />
                            Add Location
                          </Button>
                          <Button 
                            variant="danger"
                            size="sm"
                            onClick={() => openDeleteModal('zone', zone.zone_id)}
                          >
                            <Trash className="h-4 w-4 mr-1" />
                            Delete
                          </Button>
                        </div>
                      </div>
                      
                      {/* Locations */}
                      {zone.locations && zone.locations.length > 0 ? (
                        <div className="overflow-x-auto">
                          <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                              <tr>
                                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                  Code
                                </th>
                                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                  Description
                                </th>
                                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                  Pickable
                                </th>
                                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                  Receivable
                                </th>
                                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                  Actions
                                </th>
                              </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                              {zone.locations.map((location) => (
                                <tr key={location.location_id}>
                                  <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {location.code}
                                  </td>
                                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {location.description || '-'}
                                  </td>
                                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {location.is_pickable !== false ? (
                                      <CheckSquare className="h-5 w-5 text-green-500" />
                                    ) : (
                                      <X className="h-5 w-5 text-red-500" />
                                    )}
                                  </td>
                                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {location.is_receivable !== false ? (
                                      <CheckSquare className="h-5 w-5 text-green-500" />
                                    ) : (
                                      <X className="h-5 w-5 text-red-500" />
                                    )}
                                  </td>
                                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <div className="flex space-x-2">
                                      <button
                                        onClick={() => openEditLocationModal(location, zone.zone_id)}
                                        className="text-indigo-600 hover:text-indigo-900"
                                      >
                                        <Edit className="h-5 w-5" />
                                      </button>
                                      <button
                                        onClick={() => openDeleteModal('location', location.location_id, zone.zone_id)}
                                        className="text-red-600 hover:text-red-900"
                                      >
                                        <Trash className="h-5 w-5" />
                                      </button>
                                    </div>
                                  </td>
                                </tr>
                              ))}
                            </tbody>
                          </table>
                        </div>
                      ) : (
                        <div className="px-4 py-6 text-center text-sm text-gray-500">
                          No locations found in this zone. 
                          <button 
                            onClick={() => openCreateLocationModal(zone.zone_id)}
                            className="ml-1 text-indigo-600 hover:text-indigo-900"
                          >
                            Add a location
                          </button>
                        </div>
                      )}
                    </div>
                  ))}
                </div>
              )}
            </CardBody>
          </Card>
        </div>
      )}
      
      {/* Zone Modal */}
      <Modal
        isOpen={zoneModalOpen}
        onClose={() => setZoneModalOpen(false)}
        title={zoneModalMode === 'create' ? 'Add Warehouse Zone' : 'Edit Warehouse Zone'}
        size="md"
      >
        <form onSubmit={handleZoneSubmit}>
          <div className="space-y-4">
            <FormGroup>
              <Label htmlFor="name" required>Zone Name</Label>
              <Input
                id="name"
                name="name"
                value={zoneFormData.name}
                onChange={handleZoneChange}
                required
                placeholder="e.g. Receiving Area, Storage Area"
              />
            </FormGroup>
            
            <FormGroup>
              <Label htmlFor="code" required>Zone Code</Label>
              <Input
                id="code"
                name="code"
                value={zoneFormData.code}
                onChange={handleZoneChange}
                required
                placeholder="e.g. RECV-01, STOR-A"
              />
            </FormGroup>
            
            <FormGroup>
              <Label htmlFor="description">Description</Label>
              <Textarea
                id="description"
                name="description"
                value={zoneFormData.description}
                onChange={handleZoneChange}
                rows={3}
                placeholder="Optional description for this zone"
              />
            </FormGroup>
          </div>
          
          <div className="mt-6 flex justify-end space-x-3">
            <Button
              variant="light"
              onClick={() => setZoneModalOpen(false)}
            >
              Cancel
            </Button>
            <Button
              type="submit"
              variant="primary"
              isLoading={zoneModalMode === 'create' ? createZoneMutation.isLoading : updateZoneMutation.isLoading}
            >
              {zoneModalMode === 'create' ? 'Create Zone' : 'Update Zone'}
            </Button>
          </div>
        </form>
      </Modal>
      
      {/* Location Modal */}
      <Modal
        isOpen={locationModalOpen}
        onClose={() => setLocationModalOpen(false)}
        title={locationModalMode === 'create' ? 'Add Location' : 'Edit Location'}
        size="md"
      >
        <form onSubmit={handleLocationSubmit}>
          <div className="space-y-4">
            <FormGroup>
              <Label htmlFor="code" required>Location Code</Label>
              <Input
                id="code"
                name="code"
                value={locationFormData.code}
                onChange={handleLocationChange}
                required
                placeholder="e.g. A01-B02-C03"
              />
            </FormGroup>
            
            <FormGroup>
              <Label htmlFor="description">Description</Label>
              <Textarea
                id="description"
                name="description"
                value={locationFormData.description}
                onChange={handleLocationChange}
                rows={2}
                placeholder="Optional description for this location"
              />
            </FormGroup>
            
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <FormGroup>
                <Checkbox
                  id="is_pickable"
                  name="is_pickable"
                  checked={locationFormData.is_pickable}
                  onChange={handleLocationChange}
                  label="Pickable Location"
                />
              </FormGroup>
              
              <FormGroup>
                <Checkbox
                  id="is_receivable"
                  name="is_receivable"
                  checked={locationFormData.is_receivable}
                  onChange={handleLocationChange}
                  label="Receivable Location"
                />
              </FormGroup>
            </div>
            
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <FormGroup>
                <Label htmlFor="max_weight">Max Weight (kg)</Label>
                <Input
                  id="max_weight"
                  name="max_weight"
                  type="number"
                  min="0"
                  step="0.01"
                  value={locationFormData.max_weight}
                  onChange={handleLocationChange}
                  placeholder="Optional"
                />
              </FormGroup>
              
              <FormGroup>
                <Label htmlFor="max_volume">Max Volume (m³)</Label>
                <Input
                  id="max_volume"
                  name="max_volume"
                  type="number"
                  min="0"
                  step="0.01"
                  value={locationFormData.max_volume}
                  onChange={handleLocationChange}
                  placeholder="Optional"
                />
              </FormGroup>
            </div>
          </div>
          
          <div className="mt-6 flex justify-end space-x-3">
            <Button
              variant="light"
              onClick={() => setLocationModalOpen(false)}
            >
              Cancel
            </Button>
            <Button
              type="submit"
              variant="primary"
              isLoading={locationModalMode === 'create' ? createLocationMutation.isLoading : updateLocationMutation.isLoading}
            >
              {locationModalMode === 'create' ? 'Create Location' : 'Update Location'}
            </Button>
          </div>
        </form>
      </Modal>
      
      {/* Delete Confirmation Modal */}
      <Modal
        isOpen={deleteModalOpen}
        onClose={() => setDeleteModalOpen(false)}
        title={`Delete ${
          deleteItemType === 'warehouse' ? 'Warehouse' : 
          deleteItemType === 'zone' ? 'Zone' : 'Location'
        }`}
        size="sm"
      >
        <div className="py-3">
          <p className="text-gray-700">
            Are you sure you want to delete this {
              deleteItemType === 'warehouse' ? 'warehouse' : 
              deleteItemType === 'zone' ? 'zone' : 'location'
            }? This action cannot be undone.
          </p>
          
          {deleteItemType === 'warehouse' && (
            <div className="mt-4 p-3 bg-yellow-50 border-l-4 border-yellow-400 text-yellow-700">
              <div className="flex">
                <AlertTriangle className="h-5 w-5 mr-2" />
                <p>
                  <strong>Warning:</strong> Deleting this warehouse will also remove all its zones and locations.
                </p>
              </div>
            </div>
          )}
          
          {deleteItemType === 'zone' && (
            <div className="mt-4 p-3 bg-yellow-50 border-l-4 border-yellow-400 text-yellow-700">
              <div className="flex">
                <AlertTriangle className="h-5 w-5 mr-2" />
                <p>
                  <strong>Warning:</strong> Deleting this zone will also remove all its locations.
                </p>
              </div>
            </div>
          )}
        </div>
        
        <div className="mt-4 flex justify-end space-x-3">
          <Button
            variant="light"
            onClick={() => setDeleteModalOpen(false)}
          >
            Cancel
          </Button>
          <Button
            variant="danger"
            onClick={handleDeleteConfirm}
            isLoading={
              deleteItemType === 'warehouse' ? deleteWarehouseMutation.isLoading : 
              deleteItemType === 'zone' ? deleteZoneMutation.isLoading : 
              deleteLocationMutation.isLoading
            }
          >
            Delete
          </Button>
        </div>
      </Modal>
    </div>
  );
};

export default WarehouseDetail;