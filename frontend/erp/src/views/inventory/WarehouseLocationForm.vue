<!-- src/views/inventory/WarehouseLocationForm.vue -->
<template>
    <div class="location-form-page">
      <!-- Loading State -->
      <div v-if="isLoading" class="loading-container">
        <div class="loading-spinner">
          <i class="fas fa-spinner fa-spin"></i>
        </div>
        <p>Loading zone information...</p>
      </div>
  
      <!-- Error State -->
      <div v-else-if="error" class="error-container">
        <div class="error-icon">
          <i class="fas fa-exclamation-triangle"></i>
        </div>
        <p>{{ error }}</p>
        <div class="error-actions">
          <button @click="goBack" class="btn btn-primary">
            <i class="fas fa-arrow-left"></i> Back
          </button>
        </div>
      </div>
  
      <!-- Form Content -->
      <div v-else class="form-container">
        <!-- Header with Breadcrumb -->
        <div class="form-header">
          <div class="header-breadcrumb">
            <button @click="goToWarehouses" class="btn-link">
              <i class="fas fa-warehouse"></i> Warehouses
            </button>
            <span class="breadcrumb-separator">/</span>
            <button @click="goToWarehouseDetail" class="btn-link">
              {{ warehouseName }}
            </button>
            <span class="breadcrumb-separator">/</span>
            <button @click="goToZoneDetail" class="btn-link">
              {{ zoneName }}
            </button>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current">
              {{ isEditMode ? 'Edit Location' : 'Add Location' }}
            </span>
          </div>
        </div>
  
        <!-- Location Form Card -->
        <div class="form-card">
          <div class="card-header">
            <h2 class="card-title">{{ isEditMode ? 'Edit Location' : 'Add New Location' }}</h2>
          </div>
          <div class="card-body">
            <form @submit.prevent="saveLocation" class="form">
              <!-- Location Code -->
              <div class="form-group">
                <label for="location-code">Location Code*</label>
                <div class="input-with-hint">
                  <input 
                    type="text" 
                    id="location-code" 
                    v-model="locationForm.code" 
                    required
                    :class="{ 'is-invalid': validationErrors.code }"
                  />
                  <div class="input-hint">
                    Use unique, scannable codes for easy identification
                  </div>
                </div>
                <div v-if="validationErrors.code" class="invalid-feedback">
                  {{ validationErrors.code }}
                </div>
              </div>
              
              <!-- Description -->
              <div class="form-group">
                <label for="location-description">Description</label>
                <div class="input-with-hint">
                  <textarea 
                    id="location-description" 
                    v-model="locationForm.description" 
                    rows="3"
                    :class="{ 'is-invalid': validationErrors.description }"
                  ></textarea>
                  <div class="input-hint">
                    Add details about this location's purpose or contents
                  </div>
                </div>
                <div v-if="validationErrors.description" class="invalid-feedback">
                  {{ validationErrors.description }}
                </div>
              </div>
  
              <!-- Advanced Options (optional) -->
              <div class="form-section">
                <div class="section-header" @click="toggleAdvancedOptions">
                  <h3 class="section-title">Advanced Options</h3>
                  <i :class="showAdvancedOptions ? 'fas fa-chevron-up' : 'fas fa-chevron-down'"></i>
                </div>
                <div v-if="showAdvancedOptions" class="section-content">
                  <!-- Max Capacity -->
                  <div class="form-group">
                    <label for="max-capacity">Maximum Capacity</label>
                    <div class="input-with-hint">
                      <input 
                        type="number" 
                        id="max-capacity" 
                        v-model="locationForm.max_capacity" 
                        min="0"
                        step="1"
                      />
                      <div class="input-hint">
                        Set a capacity limit for this location (optional)
                      </div>
                    </div>
                  </div>
  
                  <!-- Handling Instructions -->
                  <div class="form-group">
                    <label for="handling-instructions">Handling Instructions</label>
                    <div class="input-with-hint">
                      <textarea 
                        id="handling-instructions" 
                        v-model="locationForm.handling_instructions" 
                        rows="3"
                      ></textarea>
                      <div class="input-hint">
                        Special instructions for handling items in this location
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Form Actions -->
              <div class="form-actions">
                <button type="button" class="btn btn-secondary" @click="goBack">
                  Cancel
                </button>
                <button type="submit" class="btn btn-primary" :disabled="isSaving">
                  <i v-if="isSaving" class="fas fa-spinner fa-spin"></i>
                  {{ isEditMode ? 'Update Location' : 'Add Location' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script>
  import { ref, reactive, onMounted, computed } from 'vue';
  import { useRoute, useRouter } from 'vue-router';
  import WarehouseService from '@/services/WarehouseService';
  import WarehouseZoneService from '@/services/WarehouseZoneService';
  
  export default {
    name: 'WarehouseLocationForm',
    setup() {
      const route = useRoute();
      const router = useRouter();
      
      // Route params
      const warehouseId = computed(() => parseInt(route.params.warehouseId));
      const zoneId = computed(() => parseInt(route.params.zoneId));
      const locationId = computed(() => route.params.locationId ? parseInt(route.params.locationId) : null);
      
      // Form mode
      const isEditMode = computed(() => !!locationId.value);
      
      // Data
      const isLoading = ref(true);
      const error = ref(null);
      const warehouseName = ref('Warehouse');
      const zoneName = ref('Zone');
      const isSaving = ref(false);
      const showAdvancedOptions = ref(false);
      
      // Form data
      const locationForm = reactive({
        zone_id: null,
        code: '',
        description: '',
        // Advanced options
        max_capacity: null,
        handling_instructions: ''
      });
      
      // Validation errors
      const validationErrors = reactive({});
      
      // Fetch initial data
      const fetchInitialData = async () => {
        isLoading.value = true;
        error.value = null;
        
        try {
          // Get warehouse info
          const warehouseResponse = await WarehouseService.getWarehouseById(warehouseId.value);
          warehouseName.value = warehouseResponse.data.name;
          
          // Get zone info
          const zoneResponse = await WarehouseZoneService.getZoneById(warehouseId.value, zoneId.value);
          zoneName.value = zoneResponse.data.name;
          
          // Set form zone_id
          locationForm.zone_id = zoneId.value;
          
          // If editing, get location details
          if (isEditMode.value) {
            const location = zoneResponse.data.locations.find(l => l.location_id === locationId.value);
            
            if (location) {
              locationForm.code = location.code;
              locationForm.description = location.description || '';
              locationForm.max_capacity = location.max_capacity || null;
              locationForm.handling_instructions = location.handling_instructions || '';
            } else {
              error.value = 'Location not found.';
            }
          }
        } catch (err) {
          console.error('Error fetching initial data:', err);
          error.value = 'Failed to load. Please try again.';
        } finally {
          isLoading.value = false;
        }
      };
      
      // Toggle advanced options
      const toggleAdvancedOptions = () => {
        showAdvancedOptions.value = !showAdvancedOptions.value;
      };
      
      // Navigation
      const goBack = () => {
        router.push(`/warehouses/${warehouseId.value}/zones/${zoneId.value}`);
      };
      
      const goToWarehouses = () => {
        router.push('/warehouses');
      };
      
      const goToWarehouseDetail = () => {
        router.push(`/warehouses/${warehouseId.value}`);
      };
      
      const goToZoneDetail = () => {
        router.push(`/warehouses/${warehouseId.value}/zones/${zoneId.value}`);
      };
      
      // Save location
      const saveLocation = async () => {
        isSaving.value = true;
        validationErrors.value = {};
        
        try {
          if (isEditMode.value) {
            // Update existing location
            await WarehouseZoneService.updateLocation(
              warehouseId.value,
              zoneId.value,
              locationId.value,
              locationForm
            );
            
            alert('Location updated successfully!');
          } else {
            // Create new location
            await WarehouseZoneService.createLocation(
              warehouseId.value,
              zoneId.value,
              locationForm
            );
            
            alert('Location added successfully!');
          }
          
          // Navigate back to zone detail
          router.push(`/warehouses/${warehouseId.value}/zones/${zoneId.value}`);
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
      
      // Lifecycle hooks
      onMounted(() => {
        fetchInitialData();
      });
      
      return {
        // Data
        isLoading,
        error,
        warehouseName,
        zoneName,
        isEditMode,
        locationForm,
        validationErrors,
        isSaving,
        showAdvancedOptions,
        
        // Methods
        toggleAdvancedOptions,
        goBack,
        goToWarehouses,
        goToWarehouseDetail,
        goToZoneDetail,
        saveLocation
      };
    }
  };
  </script>
  
  <style scoped>
  .location-form-page {
    padding: 1rem;
    max-width: 800px;
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
  
  /* Form container */
  .form-container {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
  }
  
  /* Header */
  .form-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  
  .header-breadcrumb {
    display: flex;
    align-items: center;
    font-size: 0.875rem;
    flex-wrap: wrap;
  }
  
  .btn-link {
    background: none;
    border: none;
    color: var(--primary-color);
    font-weight: 500;
    cursor: pointer;
    display: inline-flex;
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
  
  /* Form card */
  .form-card {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
  }
  
  .card-header {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--gray-200);
    background-color: var(--gray-50);
  }
  
  .card-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
    color: var(--gray-800);
  }
  
  .card-body {
    padding: 1.5rem;
  }
  
  /* Form */
  .form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
  }
  
  .form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .form-group label {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--gray-700);
  }
  
  .input-with-hint {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
  }
  
  .input-hint {
    font-size: 0.75rem;
    color: var(--gray-500);
  }
  
  .form-group input,
  .form-group textarea,
  .form-group select {
    padding: 0.75rem;
    border: 1px solid var(--gray-300);
    border-radius: 0.375rem;
    font-size: 0.875rem;
    transition: border-color 0.2s, box-shadow 0.2s;
  }
  
  .form-group input:focus,
  .form-group textarea:focus,
  .form-group select:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.1);
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
  
  /* Form sections */
  .form-section {
    border: 1px solid var(--gray-200);
    border-radius: 0.375rem;
    overflow: hidden;
  }
  
  .section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background-color: var(--gray-50);
    cursor: pointer;
  }
  
  .section-title {
    font-size: 1rem;
    font-weight: 500;
    margin: 0;
    color: var(--gray-700);
  }
  
  .section-content {
    padding: 1rem;
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
    border-top: 1px solid var(--gray-200);
  }
  
  /* Form actions */
  .form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 1rem;
  }
  
  /* Buttons */
  .btn {
    padding: 0.75rem 1.25rem;
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
  
  .btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
  }
  
  /* Responsive */
  @media (max-width: 640px) {
    .form-header {
      flex-direction: column;
      align-items: flex-start;
      gap: 1rem;
    }
  }
  </style>