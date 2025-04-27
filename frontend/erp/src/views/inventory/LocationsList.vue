<!-- src/views/inventory/LocationsList.vue -->
<template>
    <div class="locations-list-page">
      <div class="page-header">
        <div class="header-actions">
          <h2 class="page-title">Locations for Zone: {{ zone?.name }}</h2>
          <button class="btn-primary" @click="openCreateLocationModal">
            <i class="fas fa-plus-circle mr-2"></i> Add Location
          </button>
        </div>
        <div class="breadcrumbs">
          <router-link to="/warehouses" class="breadcrumb-item">
            <i class="fas fa-warehouse mr-1"></i> Warehouses
          </router-link>
          <span class="breadcrumb-separator">/</span>
          <router-link :to="`/warehouses/${warehouseId}`" class="breadcrumb-item">
            <i class="fas fa-map mr-1"></i> Zones
          </router-link>
          <span class="breadcrumb-separator">/</span>
          <span class="breadcrumb-item active">Locations</span>
        </div>
      </div>
  
      <div v-if="loading" class="loading-indicator">
        <i class="fas fa-spinner fa-spin mr-2"></i> Loading locations...
      </div>
  
      <div v-else-if="error" class="error-message">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        {{ error }}
      </div>
  
      <div v-else-if="locations.length === 0" class="empty-state">
        <i class="fas fa-map-marker-alt empty-icon"></i>
        <h3>No locations found</h3>
        <p>This zone doesn't have any locations yet. Get started by adding a new location.</p>
        <button class="btn-primary mt-4" @click="openCreateLocationModal">
          <i class="fas fa-plus-circle mr-2"></i> Add Location
        </button>
      </div>
  
      <div v-else class="locations-table-container">
        <table class="locations-table">
          <thead>
            <tr>
              <th>Code</th>
              <th>Description</th>
              <th class="text-center">Items</th>
              <th class="text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="location in locations" :key="location.location_id">
              <td class="location-code">{{ location.code }}</td>
              <td>
                <span v-if="location.description">{{ location.description }}</span>
                <span v-else class="text-muted">No description</span>
              </td>
              <td class="text-center">
                <span class="stock-badge">{{ locationItemCounts[location.location_id] || 0 }}</span>
              </td>
              <td class="actions-cell">
                <button class="btn-icon" @click="viewInventory(location)" title="View Inventory">
                  <i class="fas fa-boxes"></i>
                </button>
                <button class="btn-icon" @click="editLocation(location)" title="Edit Location">
                  <i class="fas fa-edit"></i>
                </button>
                <button class="btn-icon btn-danger" @click="confirmDeleteLocation(location)" title="Delete Location">
                  <i class="fas fa-trash-alt"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
  
      <!-- Create/Edit Location Modal -->
      <div v-if="showLocationModal" class="modal-backdrop">
        <div class="modal-content">
          <div class="modal-header">
            <h3>{{ isEditing ? 'Edit Location' : 'Create New Location' }}</h3>
            <button class="btn-close" @click="closeLocationModal">
              <i class="fas fa-times"></i>
            </button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveLocation">
              <div class="form-group">
                <label for="locationCode">Location Code</label>
                <input
                  id="locationCode"
                  v-model="locationForm.code"
                  type="text"
                  class="form-control"
                  required
                />
                <small class="form-text text-muted">
                  A unique code to identify this location (e.g., A-01-02)
                </small>
              </div>
  
              <div class="form-group">
                <label for="locationDescription">Description</label>
                <textarea
                  id="locationDescription"
                  v-model="locationForm.description"
                  class="form-control"
                  rows="3"
                ></textarea>
              </div>
  
              <div class="form-group form-actions">
                <button type="button" class="btn-secondary" @click="closeLocationModal">
                  Cancel
                </button>
                <button type="submit" class="btn-primary" :disabled="isSubmitting">
                  <i v-if="isSubmitting" class="fas fa-spinner fa-spin mr-2"></i>
                  {{ isEditing ? 'Update Location' : 'Create Location' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
  
      <!-- Delete Confirmation Modal -->
      <div v-if="showDeleteModal" class="modal-backdrop">
        <div class="modal-content modal-sm">
          <div class="modal-header">
            <h3>Confirm Delete</h3>
            <button class="btn-close" @click="showDeleteModal = false">
              <i class="fas fa-times"></i>
            </button>
          </div>
          <div class="modal-body">
            <p>
              Are you sure you want to delete the location
              <strong>{{ locationToDelete?.code }}</strong>?
            </p>
            <p class="text-danger">
              <i class="fas fa-exclamation-triangle mr-1"></i>
              This action cannot be undone.
            </p>
          </div>
          <div class="modal-footer">
            <button class="btn-secondary" @click="showDeleteModal = false">
              Cancel
            </button>
            <button class="btn-danger" @click="deleteLocation" :disabled="isDeleting">
              <i v-if="isDeleting" class="fas fa-spinner fa-spin mr-2"></i>
              Delete Location
            </button>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script>
  import { ref, reactive, onMounted, computed } from 'vue';
  import { useRoute, useRouter } from 'vue-router';
  import axios from 'axios';
  
  export default {
    name: 'LocationsList',
    setup() {
      const route = useRoute();
      const router = useRouter();
      const warehouseId = computed(() => route.params.warehouseId);
      const zoneId = computed(() => route.params.zoneId);
      
      const zone = ref(null);
      const locations = ref([]);
      const loading = ref(true);
      const error = ref(null);
      const locationItemCounts = ref({});
      
      // Location modal state
      const showLocationModal = ref(false);
      const isEditing = ref(false);
      const isSubmitting = ref(false);
      const locationForm = reactive({
        code: '',
        description: ''
      });
      
      // Delete modal state
      const showDeleteModal = ref(false);
      const locationToDelete = ref(null);
      const isDeleting = ref(false);
  
      const fetchZone = async () => {
        try {
          const response = await axios.get(`/warehouses/${warehouseId.value}/zones/${zoneId.value}`);
          zone.value = response.data.data;
        } catch (err) {
          console.error('Error fetching zone:', err);
          error.value = 'Failed to load zone details. Please try again.';
        }
      };
  
      const fetchLocations = async () => {
        loading.value = true;
        error.value = null;
        
        try {
          const response = await axios.get(`/zones/${zoneId.value}/locations`);
          locations.value = response.data.data;
          await fetchLocationItemCounts();
        } catch (err) {
          console.error('Error fetching locations:', err);
          error.value = 'Failed to load locations. Please try again.';
        } finally {
          loading.value = false;
        }
      };
  
      const fetchLocationItemCounts = async () => {
        for (const location of locations.value) {
          try {
            const response = await axios.get(`/zones/${zoneId.value}/locations/${location.location_id}/inventory`);
            locationItemCounts.value[location.location_id] = response.data.data.inventory.length || 0;
          } catch (err) {
            console.error(`Error fetching inventory for location ${location.location_id}:`, err);
            locationItemCounts.value[location.location_id] = 0;
          }
        }
      };
  
      const openCreateLocationModal = () => {
        resetLocationForm();
        isEditing.value = false;
        showLocationModal.value = true;
      };
  
      const editLocation = (location) => {
        locationForm.code = location.code;
        locationForm.description = location.description || '';
        locationToDelete.value = location;
        isEditing.value = true;
        showLocationModal.value = true;
      };
  
      const closeLocationModal = () => {
        showLocationModal.value = false;
        resetLocationForm();
      };
  
      const resetLocationForm = () => {
        locationForm.code = '';
        locationForm.description = '';
        locationToDelete.value = null;
      };
  
      const saveLocation = async () => {
        isSubmitting.value = true;
        
        try {
          if (isEditing.value) {
            await axios.put(`/zones/${zoneId.value}/locations/${locationToDelete.value.location_id}`, locationForm);
          } else {
            await axios.post(`/zones/${zoneId.value}/locations`, locationForm);
          }
          
          await fetchLocations();
          closeLocationModal();
        } catch (err) {
          console.error('Error saving location:', err);
          error.value = err.response?.data?.message || 'Failed to save location. Please try again.';
        } finally {
          isSubmitting.value = false;
        }
      };
  
      const confirmDeleteLocation = (location) => {
        locationToDelete.value = location;
        showDeleteModal.value = true;
      };
  
      const deleteLocation = async () => {
        if (!locationToDelete.value) return;
        
        isDeleting.value = true;
        
        try {
          await axios.delete(`/zones/${zoneId.value}/locations/${locationToDelete.value.location_id}`);
          await fetchLocations();
          showDeleteModal.value = false;
        } catch (err) {
          console.error('Error deleting location:', err);
          error.value = err.response?.data?.message || 'Failed to delete location. Please try again.';
        } finally {
          isDeleting.value = false;
        }
      };
  
      const viewInventory = (location) => {
        router.push(`/warehouses/${warehouseId.value}/zones/${zoneId.value}/locations/${location.location_id}/inventory`);
      };
  
      onMounted(async () => {
        await fetchZone();
        await fetchLocations();
      });
  
      return {
        warehouseId,
        zoneId,
        zone,
        locations,
        loading,
        error,
        locationItemCounts,
        showLocationModal,
        isEditing,
        isSubmitting,
        locationForm,
        showDeleteModal,
        locationToDelete,
        isDeleting,
        openCreateLocationModal,
        editLocation,
        closeLocationModal,
        saveLocation,
        confirmDeleteLocation,
        deleteLocation,
        viewInventory
      };
    }
  };
  </script>
  
  <style scoped>
  .locations-list-page {
    padding: 1rem;
  }
  
  .page-header {
    margin-bottom: 2rem;
  }
  
  .header-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
  }
  
  .page-title {
    margin: 0;
    font-size: 1.5rem;
    color: var(--gray-800);
  }
  
  .breadcrumbs {
    display: flex;
    align-items: center;
    margin-bottom: 1rem;
    font-size: 0.875rem;
  }
  
  .breadcrumb-item {
    color: var(--primary-color);
  }
  
  .breadcrumb-item.active {
    color: var(--gray-600);
  }
  
  .breadcrumb-separator {
    margin: 0 0.5rem;
    color: var(--gray-400);
  }
  
  .btn-primary {
    background-color: var(--primary-color);
    color: white;
    border: none;
    border-radius: 0.375rem;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
  }
  
  .btn-primary:hover {
    background-color: var(--primary-dark);
  }
  
  .btn-secondary {
    background-color: var(--gray-200);
    color: var(--gray-800);
    border: none;
    border-radius: 0.375rem;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
  }
  
  .btn-secondary:hover {
    background-color: var(--gray-300);
  }
  
  .btn-danger {
    background-color: var(--danger-color);
    color: white;
    border: none;
    border-radius: 0.375rem;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
  }
  
  .btn-icon {
    background: none;
    border: none;
    color: var(--primary-color);
    padding: 0.5rem;
    font-size: 1rem;
    cursor: pointer;
    border-radius: 0.25rem;
    transition: background-color 0.2s;
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }
  
  .btn-icon:hover {
    background-color: var(--gray-100);
  }
  
  .btn-icon.btn-danger {
    color: var(--danger-color);
  }
  
  .btn-icon.btn-danger:hover {
    background-color: rgba(220, 38, 38, 0.1);
  }
  
  .loading-indicator {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 2rem;
    color: var(--gray-500);
  }
  
  .error-message {
    padding: 1rem;
    background-color: rgba(220, 38, 38, 0.1);
    color: var(--danger-color);
    border-radius: 0.375rem;
    margin-bottom: 1rem;
  }
  
  .empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem;
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    text-align: center;
  }
  
  .empty-icon {
    font-size: 3rem;
    color: var(--gray-400);
    margin-bottom: 1rem;
  }
  
  .locations-table-container {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
  }
  
  .locations-table {
    width: 100%;
    border-collapse: collapse;
  }
  
  .locations-table th,
  .locations-table td {
    padding: 0.75rem 1rem;
    text-align: left;
  }
  
  .locations-table th {
    background-color: var(--gray-50);
    color: var(--gray-600);
    font-weight: 500;
    border-bottom: 1px solid var(--gray-200);
    font-size: 0.875rem;
  }
  
  .locations-table td {
    border-bottom: 1px solid var(--gray-200);
  }
  
  .locations-table tr:last-child td {
    border-bottom: none;
  }
  
  .locations-table tr:hover {
    background-color: var(--gray-50);
  }
  
  .location-code {
    font-weight: 500;
    color: var(--gray-800);
  }
  
  .text-muted {
    color: var(--gray-400);
    font-style: italic;
  }
  
  .text-center {
    text-align: center;
  }
  
  .text-right {
    text-align: right;
  }
  
  .stock-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 1.5rem;
    height: 1.5rem;
    padding: 0 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--primary-color);
    background-color: rgba(37, 99, 235, 0.1);
    border-radius: 9999px;
  }
  
  .actions-cell {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
  }
  
  .modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
  }
  
  .modal-content {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    width: 95%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
  }
  
  .modal-sm {
    max-width: 400px;
  }
  
  .modal-header {
    padding: 1rem;
    border-bottom: 1px solid var(--gray-200);
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  
  .modal-header h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--gray-800);
  }
  
  .btn-close {
    background: none;
    border: none;
    color: var(--gray-500);
    cursor: pointer;
    font-size: 1.25rem;
    line-height: 1;
    padding: 0.25rem;
  }
  
  .btn-close:hover {
    color: var(--gray-800);
  }
  
  .modal-body {
    padding: 1rem;
  }
  
  .modal-footer {
    padding: 1rem;
    border-top: 1px solid var(--gray-200);
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
  }
  
  .form-group {
    margin-bottom: 1rem;
  }
  
  .form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: var(--gray-700);
  }
  
  .form-control {
    width: 100%;
    padding: 0.5rem 0.75rem;
    font-size: 1rem;
    line-height: 1.5;
    color: var(--gray-900);
    background-color: white;
    border: 1px solid var(--gray-300);
    border-radius: 0.375rem;
  }
  
  .form-control:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
  }
  
  .form-text {
    display: block;
    margin-top: 0.25rem;
    font-size: 0.75rem;
  }
  
  .form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    margin-top: 1.5rem;
  }
  
  .text-danger {
    color: var(--danger-color);
  }
  
  .mr-1 {
    margin-right: 0.25rem;
  }
  
  .mr-2 {
    margin-right: 0.5rem;
  }
  
  .mt-4 {
    margin-top: 1rem;
  }
  </style>