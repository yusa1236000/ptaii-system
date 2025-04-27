<!-- src/views/inventory/ZonesList.vue -->
<template>
    <div class="zones-list-page">
      <div class="page-header">
        <div class="header-actions">
          <h2 class="page-title">Zones for Warehouse: {{ warehouse?.name }}</h2>
          <button class="btn-primary" @click="openCreateZoneModal">
            <i class="fas fa-plus-circle mr-2"></i> Add Zone
          </button>
        </div>
        <div class="breadcrumbs">
          <router-link to="/warehouses" class="breadcrumb-item">
            <i class="fas fa-warehouse mr-1"></i> Warehouses
          </router-link>
          <span class="breadcrumb-separator">/</span>
          <span class="breadcrumb-item active">Zones</span>
        </div>
      </div>
  
      <div v-if="loading" class="loading-indicator">
        <i class="fas fa-spinner fa-spin mr-2"></i> Loading zones...
      </div>
  
      <div v-else-if="error" class="error-message">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        {{ error }}
      </div>
  
      <div v-else-if="zones.length === 0" class="empty-state">
        <i class="fas fa-map-marker-alt empty-icon"></i>
        <h3>No zones found</h3>
        <p>This warehouse doesn't have any zones yet. Get started by adding a new zone.</p>
        <button class="btn-primary mt-4" @click="openCreateZoneModal">
          <i class="fas fa-plus-circle mr-2"></i> Add Zone
        </button>
      </div>
  
      <div v-else class="zones-grid">
        <div v-for="zone in zones" :key="zone.zone_id" class="zone-card">
          <div class="zone-card-header">
            <h3 class="zone-name">{{ zone.name }}</h3>
            <div class="zone-code">{{ zone.code }}</div>
          </div>
          <div class="zone-card-body">
            <p v-if="zone.description" class="zone-description">{{ zone.description }}</p>
            <p v-else class="zone-description text-muted">No description available</p>
            
            <div class="zone-stats">
              <div class="stat-item">
                <i class="fas fa-map-marker-alt"></i>
                <span>{{ zone.locations ? zone.locations.length : 0 }} Locations</span>
              </div>
            </div>
          </div>
          <div class="zone-card-footer">
            <button class="btn-text" @click="viewLocations(zone)">
              <i class="fas fa-map-marker-alt mr-1"></i> Locations
            </button>
            <button class="btn-text" @click="editZone(zone)">
              <i class="fas fa-edit mr-1"></i> Edit
            </button>
            <button class="btn-text btn-danger" @click="confirmDeleteZone(zone)">
              <i class="fas fa-trash-alt mr-1"></i> Delete
            </button>
          </div>
        </div>
      </div>
  
      <!-- Create/Edit Zone Modal -->
      <div v-if="showZoneModal" class="modal-backdrop">
        <div class="modal-content">
          <div class="modal-header">
            <h3>{{ isEditing ? 'Edit Zone' : 'Create New Zone' }}</h3>
            <button class="btn-close" @click="closeZoneModal">
              <i class="fas fa-times"></i>
            </button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveZone">
              <div class="form-group">
                <label for="zoneName">Zone Name</label>
                <input
                  id="zoneName"
                  v-model="zoneForm.name"
                  type="text"
                  class="form-control"
                  required
                />
              </div>
  
              <div class="form-group">
                <label for="zoneCode">Zone Code</label>
                <input
                  id="zoneCode"
                  v-model="zoneForm.code"
                  type="text"
                  class="form-control"
                  required
                />
                <small class="form-text text-muted">
                  A unique code to identify this zone
                </small>
              </div>
  
              <div class="form-group">
                <label for="zoneDescription">Description</label>
                <textarea
                  id="zoneDescription"
                  v-model="zoneForm.description"
                  class="form-control"
                  rows="3"
                ></textarea>
              </div>
  
              <div class="form-group form-actions">
                <button type="button" class="btn-secondary" @click="closeZoneModal">
                  Cancel
                </button>
                <button type="submit" class="btn-primary" :disabled="isSubmitting">
                  <i v-if="isSubmitting" class="fas fa-spinner fa-spin mr-2"></i>
                  {{ isEditing ? 'Update Zone' : 'Create Zone' }}
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
              Are you sure you want to delete the zone
              <strong>{{ zoneToDelete?.name }}</strong>?
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
            <button class="btn-danger" @click="deleteZone" :disabled="isDeleting">
              <i v-if="isDeleting" class="fas fa-spinner fa-spin mr-2"></i>
              Delete Zone
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
    name: 'ZonesList',
    setup() {
      const route = useRoute();
      const router = useRouter();
      const warehouseId = computed(() => route.params.id);
      
      const warehouse = ref(null);
      const zones = ref([]);
      const loading = ref(true);
      const error = ref(null);
      
      // Zone modal state
      const showZoneModal = ref(false);
      const isEditing = ref(false);
      const isSubmitting = ref(false);
      const zoneForm = reactive({
        name: '',
        code: '',
        description: ''
      });
      
      // Delete modal state
      const showDeleteModal = ref(false);
      const zoneToDelete = ref(null);
      const isDeleting = ref(false);
  
      const fetchWarehouse = async () => {
        try {
          const response = await axios.get(`/warehouses/${warehouseId.value}`);
          warehouse.value = response.data.data;
        } catch (err) {
          console.error('Error fetching warehouse:', err);
          error.value = 'Failed to load warehouse details. Please try again.';
        }
      };
  
      const fetchZones = async () => {
        loading.value = true;
        error.value = null;
        
        try {
          const response = await axios.get(`/warehouses/${warehouseId.value}/zones`);
          zones.value = response.data.data;
        } catch (err) {
          console.error('Error fetching zones:', err);
          error.value = 'Failed to load zones. Please try again.';
        } finally {
          loading.value = false;
        }
      };
  
      const openCreateZoneModal = () => {
        resetZoneForm();
        isEditing.value = false;
        showZoneModal.value = true;
      };
  
      const editZone = (zone) => {
        zoneForm.name = zone.name;
        zoneForm.code = zone.code;
        zoneForm.description = zone.description || '';
        zoneToDelete.value = zone;
        isEditing.value = true;
        showZoneModal.value = true;
      };
  
      const closeZoneModal = () => {
        showZoneModal.value = false;
        resetZoneForm();
      };
  
      const resetZoneForm = () => {
        zoneForm.name = '';
        zoneForm.code = '';
        zoneForm.description = '';
        zoneToDelete.value = null;
      };
  
      const saveZone = async () => {
        isSubmitting.value = true;
        
        try {
          if (isEditing.value) {
            await axios.put(`/warehouses/${warehouseId.value}/zones/${zoneToDelete.value.zone_id}`, zoneForm);
          } else {
            await axios.post(`/warehouses/${warehouseId.value}/zones`, zoneForm);
          }
          
          await fetchZones();
          closeZoneModal();
        } catch (err) {
          console.error('Error saving zone:', err);
          // Display error in form
          error.value = err.response?.data?.message || 'Failed to save zone. Please try again.';
        } finally {
          isSubmitting.value = false;
        }
      };
  
      const confirmDeleteZone = (zone) => {
        zoneToDelete.value = zone;
        showDeleteModal.value = true;
      };
  
      const deleteZone = async () => {
        if (!zoneToDelete.value) return;
        
        isDeleting.value = true;
        
        try {
          await axios.delete(`/warehouses/${warehouseId.value}/zones/${zoneToDelete.value.zone_id}`);
          await fetchZones();
          showDeleteModal.value = false;
        } catch (err) {
          console.error('Error deleting zone:', err);
          error.value = err.response?.data?.message || 'Failed to delete zone. Please try again.';
        } finally {
          isDeleting.value = false;
        }
      };
  
      const viewLocations = (zone) => {
        router.push(`/warehouses/${warehouseId.value}/zones/${zone.zone_id}`);
      };
  
      onMounted(async () => {
        await fetchWarehouse();
        await fetchZones();
      });
  
      return {
        warehouse,
        zones,
        loading,
        error,
        showZoneModal,
        isEditing,
        isSubmitting,
        zoneForm,
        showDeleteModal,
        zoneToDelete,
        isDeleting,
        openCreateZoneModal,
        editZone,
        closeZoneModal,
        saveZone,
        confirmDeleteZone,
        deleteZone,
        viewLocations
      };
    }
  };
  </script>
  
  <style scoped>
  .zones-list-page {
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
  
  .btn-text {
    background: none;
    border: none;
    color: var(--primary-color);
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    cursor: pointer;
    border-radius: 0.25rem;
  }
  
  .btn-text:hover {
    background-color: var(--gray-100);
  }
  
  .btn-text.btn-danger {
    color: var(--danger-color);
    background: none;
  }
  
  .btn-text.btn-danger:hover {
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
  
  .zones-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
  }
  
  .zone-card {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
  }
  
  .zone-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  }
  
  .zone-card-header {
    padding: 1rem;
    border-bottom: 1px solid var(--gray-200);
    background-color: var(--gray-50);
  }
  
  .zone-name {
    margin: 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--gray-800);
  }
  
  .zone-code {
    font-size: 0.875rem;
    color: var(--gray-500);
    margin-top: 0.25rem;
  }
  
  .zone-card-body {
    padding: 1rem;
  }
  
  .zone-description {
    margin: 0 0 1rem 0;
    font-size: 0.875rem;
    color: var(--gray-600);
    line-height: 1.5;
  }
  
  .text-muted {
    color: var(--gray-400);
    font-style: italic;
  }
  
  .zone-stats {
    display: flex;
    justify-content: space-between;
  }
  
  .stat-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: var(--gray-600);
  }
  
  .zone-card-footer {
    padding: 0.75rem 1rem;
    border-top: 1px solid var(--gray-200);
    background-color: var(--gray-50);
    display: flex;
    justify-content: space-between;
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