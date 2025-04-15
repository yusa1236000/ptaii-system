<!-- src/views/inventory/WarehouseZoneDetail.vue -->
<template>
    <div class="zone-detail">
      <!-- Loading State -->
      <div v-if="isLoading" class="loading-container">
        <div class="loading-spinner">
          <i class="fas fa-spinner fa-spin"></i>
        </div>
        <p>Loading zone details...</p>
      </div>
  
      <!-- Error State -->
      <div v-else-if="error" class="error-container">
        <div class="error-icon">
          <i class="fas fa-exclamation-triangle"></i>
        </div>
        <p>{{ error }}</p>
        <div class="error-actions">
          <button @click="fetchZoneDetails" class="btn btn-secondary">
            <i class="fas fa-sync"></i> Try Again
          </button>
          <button @click="goBack" class="btn btn-primary">
            <i class="fas fa-arrow-left"></i> Back to Warehouse
          </button>
        </div>
      </div>
  
      <!-- Zone Details -->
      <div v-else-if="zone" class="detail-container">
        <!-- Header with Actions -->
        <div class="detail-header">
          <div class="header-breadcrumb">
            <button @click="goToWarehouses" class="btn-link">
              <i class="fas fa-warehouse"></i> Warehouses
            </button>
            <span class="breadcrumb-separator">/</span>
            <button @click="goBack" class="btn-link">
              {{ warehouseName }}
            </button>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current">{{ zone.name }}</span>
          </div>
          <div class="header-actions">
            <button @click="editZone" class="btn btn-primary">
              <i class="fas fa-edit"></i> Edit Zone
            </button>
            <button @click="confirmDeleteZone" class="btn btn-danger">
              <i class="fas fa-trash"></i> Delete Zone
            </button>
          </div>
        </div>
  
        <!-- Zone Overview -->
        <div class="detail-card">
          <div class="card-header">
            <h2 class="card-title">Zone Details</h2>
          </div>
          <div class="card-body">
            <div class="detail-grid">
              <div class="detail-item">
                <h3 class="detail-label">Zone ID</h3>
                <p class="detail-value">{{ zone.zone_id }}</p>
              </div>
              <div class="detail-item">
                <h3 class="detail-label">Code</h3>
                <p class="detail-value">{{ zone.code }}</p>
              </div>
              <div class="detail-item">
                <h3 class="detail-label">Name</h3>
                <p class="detail-value">{{ zone.name }}</p>
              </div>
              <div class="detail-item">
                <h3 class="detail-label">Warehouse</h3>
                <p class="detail-value">{{ warehouseName }}</p>
              </div>
              <div class="detail-item full-width">
                <h3 class="detail-label">Description</h3>
                <p class="detail-value description-value">
                  {{ zone.description || 'No description provided' }}
                </p>
              </div>
            </div>
          </div>
        </div>
  
        <!-- Zone Locations -->
        <div class="detail-card">
          <div class="card-header">
            <h2 class="card-title">Locations</h2>
            <button @click="openAddLocationModal" class="btn btn-sm btn-primary">
              <i class="fas fa-plus"></i> Add Location
            </button>
          </div>
          <div class="card-body">
            <div v-if="!zone.locations || zone.locations.length === 0" class="empty-state-small">
              <div class="empty-icon-small">
                <i class="fas fa-map-marker-alt"></i>
              </div>
              <p>No locations found for this zone.</p>
              <button @click="openAddLocationModal" class="btn btn-sm btn-primary">
                <i class="fas fa-plus"></i> Add Location
              </button>
            </div>
            <div v-else>
              <!-- Locations Table -->
              <div class="locations-table">
                <table>
                  <thead>
                    <tr>
                      <th>Code</th>
                      <th>Description</th>
                      <th>Items</th>
                      <th class="actions-column">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="location in zone.locations" :key="location.location_id">
                      <td>{{ location.code }}</td>
                      <td>{{ location.description || '-' }}</td>
                      <td>{{ location.itemCount || 0 }}</td>
                      <td class="actions-cell">
                        <button @click="editLocation(location)" class="action-btn-sm" title="Edit Location">
                          <i class="fas fa-edit"></i>
                        </button>
                        <button @click="viewLocationItems(location)" class="action-btn-sm" title="View Items">
                          <i class="fas fa-eye"></i>
                        </button>
                        <button @click="confirmDeleteLocation(location)" class="action-btn-sm" title="Delete Location">
                          <i class="fas fa-trash"></i>
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
  
      <!-- Zone Form Modal -->
      <div v-if="showZoneModal" class="modal-overlay">
        <div class="modal-container">
          <div class="modal-header">
            <h2>Edit Zone</h2>
            <button class="close-btn" @click="closeZoneModal">
              <i class="fas fa-times"></i>
            </button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveZone" class="form">
              <div class="form-group">
                <label for="zone-name">Zone Name*</label>
                <input 
                  type="text" 
                  id="zone-name" 
                  v-model="zoneForm.name" 
                  required
                  :class="{ 'is-invalid': validationErrors.name }"
                />
                <div v-if="validationErrors.name" class="invalid-feedback">
                  {{ validationErrors.name }}
                </div>
              </div>
              
              <div class="form-group">
                <label for="zone-code">Zone Code*</label>
                <input 
                  type="text" 
                  id="zone-code" 
                  v-model="zoneForm.code" 
                  required
                  :class="{ 'is-invalid': validationErrors.code }"
                />
                <div v-if="validationErrors.code" class="invalid-feedback">
                  {{ validationErrors.code }}
                </div>
              </div>
              
              <div class="form-group">
                <label for="zone-description">Description</label>
                <textarea 
                  id="zone-description" 
                  v-model="zoneForm.description" 
                  rows="3"
                  :class="{ 'is-invalid': validationErrors.description }"
                ></textarea>
                <div v-if="validationErrors.description" class="invalid-feedback">
                  {{ validationErrors.description }}
                </div>
              </div>
              
              <div class="form-actions">
                <button type="button" class="btn btn-secondary" @click="closeZoneModal">
                  Cancel
                </button>
                <button type="submit" class="btn btn-primary" :disabled="isSaving">
                  <i v-if="isSaving" class="fas fa-spinner fa-spin"></i>
                  Update Zone
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
  
      <!-- Location Form Modal -->
      <div v-if="showLocationModal" class="modal-overlay">
        <div class="modal-container">
          <div class="modal-header">
            <h2>{{ isEditingLocation ? 'Edit Location' : 'Add New Location' }}</h2>
            <button class="close-btn" @click="closeLocationModal">
              <i class="fas fa-times"></i>
            </button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveLocation" class="form">
              <div class="form-group">
                <label for="location-code">Location Code*</label>
                <input 
                  type="text" 
                  id="location-code" 
                  v-model="locationForm.code" 
                  required
                  :class="{ 'is-invalid': validationErrors.code }"
                />
                <div v-if="validationErrors.code" class="invalid-feedback">
                  {{ validationErrors.code }}
                </div>
              </div>
              
              <div class="form-group">
                <label for="location-description">Description</label>
                <textarea 
                  id="location-description" 
                  v-model="locationForm.description" 
                  rows="3"
                  :class="{ 'is-invalid': validationErrors.description }"
                ></textarea>
                <div v-if="validationErrors.description" class="invalid-feedback">
                  {{ validationErrors.description }}
                </div>
              </div>
              
              <div class="form-actions">
                <button type="button" class="btn btn-secondary" @click="closeLocationModal">
                  Cancel
                </button>
                <button type="submit" class="btn btn-primary" :disabled="isSaving">
                  <i v-if="isSaving" class="fas fa-spinner fa-spin"></i>
                  {{ isEditingLocation ? 'Update Location' : 'Add Location' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
  
      <!-- Delete Zone Confirmation Modal -->
      <ConfirmationModal
        v-if="showDeleteZoneModal"
        title="Confirm Delete Zone"
        :message="`Are you sure you want to delete zone <strong>${zone?.name}</strong>?<br>This will also delete all locations in this zone. This action cannot be undone.`"
        confirmButtonText="Delete Zone"
        confirmButtonClass="btn btn-danger"
        @confirm="deleteZone"
        @close="closeDeleteZoneModal"
      />
  
      <!-- Delete Location Confirmation Modal -->
      <ConfirmationModal
        v-if="showDeleteLocationModal"
        title="Confirm Delete Location"
        :message="`Are you sure you want to delete location <strong>${locationToDelete?.code}</strong>?<br>This action cannot be undone.`"
        confirmButtonText="Delete Location"
        confirmButtonClass="btn btn-danger"
        @confirm="deleteLocation"
        @close="closeDeleteLocationModal"
      />
    </div>
  </template>
  
  <script>
  import { ref, reactive, onMounted, computed } from 'vue';
  import { useRoute, useRouter } from 'vue-router';
  import WarehouseService from '@/services/WarehouseService';
  import WarehouseZoneService from '@/services/WarehouseZoneService';
  import ConfirmationModal from '@/components/common/ConfirmationModal.vue';
  
  export default {
    name: 'WarehouseZoneDetail',
    components: {
      ConfirmationModal
    },
    setup() {
      const route = useRoute();
      const router = useRouter();
      
      // Route params
      const warehouseId = computed(() => parseInt(route.params.warehouseId));
      const zoneId = computed(() => parseInt(route.params.zoneId));
      
      // Data
      const warehouse = ref(null);
      const zone = ref(null);
      const isLoading = ref(true);
      const error = ref(null);
      const warehouseName = ref('Warehouse');
      
      // Zone Form Modal
      const showZoneModal = ref(false);
      const isSaving = ref(false);
      const zoneForm = reactive({
        warehouse_id: null,
        name: '',
        code: '',
        description: ''
      });
      
      // Location Form Modal
      const showLocationModal = ref(false);
      const isEditingLocation = ref(false);
      const locationForm = reactive({
        zone_id: null,
        code: '',
        description: ''
      });
      const locationToDelete = ref(null);
      
      // Validation errors
      const validationErrors = reactive({});
      
      // Delete confirmation modals
      const showDeleteZoneModal = ref(false);
      const showDeleteLocationModal = ref(false);
  
      // Fetch zone details
      const fetchZoneDetails = async () => {
        isLoading.value = true;
        error.value = null;
        
        try {
          // First get the warehouse to get the name
          const warehouseResponse = await WarehouseService.getWarehouseById(warehouseId.value);
          warehouse.value = warehouseResponse.data;
          warehouseName.value = warehouse.value.name;
          
          // Then get the zone details
          const zoneResponse = await WarehouseZoneService.getZoneById(warehouseId.value, zoneId.value);
          zone.value = zoneResponse.data;
          
          // If the zone has locations, fetch item counts for each location
          if (zone.value.locations && zone.value.locations.length > 0) {
            // In a real implementation, you would fetch item counts for each location
            // For demo, we'll set random counts
            zone.value.locations.forEach(location => {
              location.itemCount = Math.floor(Math.random() * 20);
            });
          }
        } catch (err) {
          console.error('Error fetching zone details:', err);
          error.value = 'Failed to load zone details. Please try again.';
        } finally {
          isLoading.value = false;
        }
      };
      
      // Navigation
      const goBack = () => {
        router.push(`/warehouses/${warehouseId.value}`);
      };
      
      const goToWarehouses = () => {
        router.push('/warehouses');
      };
      
      // Zone operations
      const editZone = () => {
        zoneForm.warehouse_id = warehouseId.value;
        zoneForm.name = zone.value.name;
        zoneForm.code = zone.value.code;
        zoneForm.description = zone.value.description || '';
        
        validationErrors.value = {};
        showZoneModal.value = true;
      };
      
      const closeZoneModal = () => {
        showZoneModal.value = false;
      };
      
      const saveZone = async () => {
        isSaving.value = true;
        validationErrors.value = {};
        
        try {
          await WarehouseZoneService.updateZone(
            warehouseId.value,
            zoneId.value,
            zoneForm
          );
          
          // Update local state
          zone.value.name = zoneForm.name;
          zone.value.code = zoneForm.code;
          zone.value.description = zoneForm.description;
          
          closeZoneModal();
          alert('Zone updated successfully!');
        } catch (err) {
          console.error('Error updating zone:', err);
          
          // Handle validation errors
          if (err.response && err.response.status === 422) {
            validationErrors.value = err.validationErrors || {};
          } else {
            alert('An error occurred while updating the zone. Please try again.');
          }
        } finally {
          isSaving.value = false;
        }
      };
      
      const confirmDeleteZone = () => {
        showDeleteZoneModal.value = true;
      };
      
      const closeDeleteZoneModal = () => {
        showDeleteZoneModal.value = false;
      };
      
      const deleteZone = async () => {
        try {
          await WarehouseZoneService.deleteZone(warehouseId.value, zoneId.value);
          
          alert('Zone deleted successfully!');
          router.push(`/warehouses/${warehouseId.value}`);
        } catch (err) {
          console.error('Error deleting zone:', err);
          
          // Handle specific errors
          if (err.response && err.response.status === 422) {
            alert('This zone cannot be deleted because it contains items or has other dependencies.');
          } else {
            alert('An error occurred while deleting the zone. Please try again.');
          }
          
          closeDeleteZoneModal();
        }
      };
      
      // Location operations
      const openAddLocationModal = () => {
        isEditingLocation.value = false;
        locationForm.zone_id = zoneId.value;
        locationForm.code = '';
        locationForm.description = '';
        
        validationErrors.value = {};
        showLocationModal.value = true;
      };
      
      const editLocation = (location) => {
        isEditingLocation.value = true;
        locationForm.zone_id = zoneId.value;
        locationForm.location_id = location.location_id;
        locationForm.code = location.code;
        locationForm.description = location.description || '';
        
        validationErrors.value = {};
        showLocationModal.value = true;
      };
      
      const closeLocationModal = () => {
        showLocationModal.value = false;
      };
      
      const saveLocation = async () => {
        isSaving.value = true;
        validationErrors.value = {};
        
        try {
          if (isEditingLocation.value) {
            // Update existing location
            await WarehouseZoneService.updateLocation(
              warehouseId.value,
              zoneId.value,
              locationForm.location_id,
              locationForm
            );
            
            // Update local state
            const index = zone.value.locations.findIndex(l => l.location_id === locationForm.location_id);
            if (index !== -1) {
              zone.value.locations[index] = {
                ...zone.value.locations[index],
                code: locationForm.code,
                description: locationForm.description
              };
            }
            
            alert('Location updated successfully!');
          } else {
            // Create new location
            const response = await WarehouseZoneService.createLocation(
              warehouseId.value,
              zoneId.value,
              locationForm
            );
            
            // Add to local state
            const newLocation = {
              location_id: response.data.location_id || Date.now(), // Temporary ID if not provided
              zone_id: zoneId.value,
              code: locationForm.code,
              description: locationForm.description,
              itemCount: 0
            };
            
            if (!zone.value.locations) {
              zone.value.locations = [];
            }
            
            zone.value.locations.push(newLocation);
            alert('Location added successfully!');
          }
          
          closeLocationModal();
        } catch (err) {
          console.error('Error saving location:', err);
          
          // Handle validation errors
          if (err.response && err.response.status === 422) {
            validationErrors.value = err.validationErrors || {};
          } else {
            alert('An error occurred while saving the location. Please try again.');
          }
        } finally {
          isSaving.value = false;
        }
      };
      
      const confirmDeleteLocation = (location) => {
        locationToDelete.value = location;
        showDeleteLocationModal.value = true;
      };
      
      const closeDeleteLocationModal = () => {
        showDeleteLocationModal.value = false;
        locationToDelete.value = null;
      };
      
      const deleteLocation = async () => {
        try {
          await WarehouseZoneService.deleteLocation(
            warehouseId.value,
            zoneId.value,
            locationToDelete.value.location_id
          );
          
          // Update local state
          zone.value.locations = zone.value.locations.filter(
            l => l.location_id !== locationToDelete.value.location_id
          );
          
          alert('Location deleted successfully!');
          closeDeleteLocationModal();
        } catch (err) {
          console.error('Error deleting location:', err);
          
          // Handle specific errors
          if (err.response && err.response.status === 422) {
            alert('This location cannot be deleted because it contains items or has other dependencies.');
          } else {
            alert('An error occurred while deleting the location. Please try again.');
          }
          
          closeDeleteLocationModal();
        }
      };
      
      const viewLocationItems = (location) => {
        // Navigate to location items page
        router.push(`/stock-transactions?location_id=${location.location_id}`);
      };
      
      // Lifecycle hooks
      onMounted(() => {
        fetchZoneDetails();
      });
      
      return {
        // Data
        warehouse,
        zone,
        isLoading,
        error,
        warehouseName,
        
        // Zone modal
        showZoneModal,
        zoneForm,
        validationErrors,
        isSaving,
        
        // Location modal
        showLocationModal,
        isEditingLocation,
        locationForm,
        locationToDelete,
        
        // Confirmation modals
        showDeleteZoneModal,
        showDeleteLocationModal,
        
        // Methods
        fetchZoneDetails,
        goBack,
        goToWarehouses,
        editZone,
        closeZoneModal,
        saveZone,
        confirmDeleteZone,
        closeDeleteZoneModal,
        deleteZone,
        openAddLocationModal,
        editLocation,
        closeLocationModal,
        saveLocation,
        confirmDeleteLocation,
        closeDeleteLocationModal,
        deleteLocation,
        viewLocationItems
      };
    }
  };
  </script>
  
  <style scoped>
  .zone-detail {
    padding: 1rem;
    max-width: 1200px;
    margin: 0 auto;
  }
  
  /* Loading and error states */
  .loading-container,
  .error-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 0;
    text-align: center;
  }
  
  .loading-spinner {
    font-size: 2rem;
    margin-bottom: 1rem;
    color: var(--gray-500);
  }
  
  .error-icon {
    font-size: 3rem;
    color: var(--danger-color);
    margin-bottom: 1rem;
  }
  
  .error-container p {
    font-size: 1.125rem;
    color: var(--gray-700);
    margin-bottom: 1.5rem;
  }
  
  .error-actions {
    display: flex;
    gap: 1rem;
  }
  
  /* Detail container */
  .detail-container {
    display: flex;
    flex-direction: column;
    gap: 2rem;
  }
  
  /* Header */
  .detail-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
  }
  
  .header-breadcrumb {
    display: flex;
    align-items: center;
    font-size: 0.875rem;
  }
  
  .btn-link {
    background: none;
    border: none;
    color: var(--primary-color);
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0;
  }
  
  .breadcrumb-separator {
    margin: 0 0.5rem;
    color: var(--gray-400);
  }
  
  .breadcrumb-current {
    font-weight: 600;
    color: var(--gray-800);
  }
  
  .header-actions {
    display: flex;
    gap: 0.5rem;
  }
  
  /* Cards */
  .detail-card {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
  }
  
  .card-header {
    padding: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--gray-200);
    background-color: var(--gray-50);
  }
  
  .card-title {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0;
    color: var(--gray-800);
  }
  
  .card-body {
    padding: 1.5rem;
  }
  
  /* Detail grid */
  .detail-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
  }
  
  .detail-item {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .detail-item.full-width {
    grid-column: span 2;
  }
  
  .detail-label {
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--gray-500);
    margin: 0;
  }
  
  .detail-value {
    font-size: 1rem;
    color: var(--gray-800);
    margin: 0;
  }
  
  .description-value {
    white-space: pre-line;
  }
  
  /* Empty states */
  .empty-state-small {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem 0;
    text-align: center;
  }
  
  .empty-icon-small {
    font-size: 2rem;
    color: var(--gray-300);
    margin-bottom: 0.5rem;
  }
  
  .empty-state-small p {
    color: var(--gray-500);
    margin-bottom: 1rem;
  }
  
  /* Locations Table */
  .locations-table {
    width: 100%;
    overflow-x: auto;
  }
  
  .locations-table table {
    width: 100%;
    border-collapse: collapse;
  }
  
  .locations-table th {
    padding: 0.75rem;
    text-align: left;
    font-weight: 500;
    color: var(--gray-600);
    border-bottom: 1px solid var(--gray-200);
    background-color: var(--gray-50);
  }
  
  .locations-table td {
    padding: 0.75rem;
    border-bottom: 1px solid var(--gray-100);
  }
  
  .actions-column {
    width: 1%;
    white-space: nowrap;
    text-align: right;
  }
  
  .actions-cell {
    text-align: right;
    white-space: nowrap;
  }
  
  .action-btn-sm {
    background: none;
    border: none;
    color: var(--gray-500);
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 0.25rem;
    transition: background-color 0.2s, color 0.2s;
  }
  
  .action-btn-sm:hover {
    background-color: var(--gray-200);
    color: var(--gray-800);
  }
  
  /* Small button variant */
  .btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.75rem;
  }
  
  /* Modal styles */
  .modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 100;
  }
  
  .modal-container {
    background-color: white;
    border-radius: 0.5rem;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  }
  
  .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--gray-200);
  }
  
  .modal-header h2 {
    font-size: 1.25rem;
    margin: 0;
    color: var(--gray-800);
  }
  
  .close-btn {
    background: none;
    border: none;
    color: var(--gray-500);
    cursor: pointer;
    font-size: 1.25rem;
  }
  
  .modal-body {
    padding: 1.5rem;
  }
  
  /* Form */
  .form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }
  
  .form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .form-group label {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--gray-700);
  }
  
  .form-group input,
  .form-group textarea {
    padding: 0.625rem;
    border: 1px solid var(--gray-300);
    border-radius: 0.375rem;
    font-size: 0.875rem;
  }
  
  .form-group input:focus,
  .form-group textarea:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
  }
  
  .form-group textarea {
    resize: vertical;
    min-height: 80px;
  }
  
  .form-group .is-invalid {
    border-color: var(--danger-color);
  }
  
  .invalid-feedback {
    font-size: 0.75rem;
    color: var(--danger-color);
  }
  
  .form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 1rem;
  }
  
  /* Buttons */
  .btn {
    padding: 0.625rem 1rem;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s;
    border: none;
  }
  
  .btn-primary {
    background-color: var(--primary-color);
    color: white;
  }
  
  .btn-primary:hover {
    background-color: var(--primary-dark);
  }
  
  .btn-secondary {
    background-color: var(--gray-200);
    color: var(--gray-700);
  }
  
  .btn-secondary:hover {
    background-color: var(--gray-300);
  }
  
  .btn-danger {
    background-color: var(--danger-color);
    color: white;
  }
  
  .btn-danger:hover {
    background-color: #b91c1c;
  }
  
  .btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
  }
  
  /* Responsive */
  @media (max-width: 768px) {
    .detail-grid {
      grid-template-columns: 1fr;
    }
    
    .detail-header {
      flex-direction: column;
      align-items: flex-start;
      gap: 1rem;
    }
    
    .header-actions {
      align-self: flex-end;
    }
  }
  </style>