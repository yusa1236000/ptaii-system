<!-- src/views/inventory/WarehouseList.vue -->
<template>
    <div class="warehouse-list">
      <div class="page-header">
        <h1>Warehouse Management</h1>
        <div class="page-actions">
          <button class="btn btn-primary" @click="openAddWarehouseModal">
            <i class="fas fa-plus"></i> Add Warehouse
          </button>
        </div>
      </div>
  
      <!-- Search and Filter Section -->
      <div class="filters-container">
        <SearchFilter 
          placeholder="Search warehouses..." 
          v-model:value="searchQuery"
          @search="applyFilters"
          @clear="clearSearch"
        >
          <template #filters>
            <div class="filter-group">
              <label for="status-filter">Status</label>
              <select id="status-filter" v-model="filters.status" @change="applyFilters">
                <option value="">All Status</option>
                <option value="true">Active</option>
                <option value="false">Inactive</option>
              </select>
            </div>
          </template>
        </SearchFilter>
      </div>
  
      <!-- Loading Indicator -->
      <div v-if="isLoading" class="loading-container">
        <div class="loading-spinner">
          <i class="fas fa-spinner fa-spin"></i>
        </div>
        <p>Loading warehouses...</p>
      </div>
  
      <!-- Error Message -->
      <div v-else-if="error" class="error-container">
        <div class="error-icon">
          <i class="fas fa-exclamation-triangle"></i>
        </div>
        <p>{{ error }}</p>
        <button @click="fetchWarehouses" class="btn btn-secondary">
          <i class="fas fa-sync"></i> Try Again
        </button>
      </div>
  
      <!-- Empty State -->
      <div v-else-if="filteredWarehouses.length === 0" class="empty-state">
        <div class="empty-icon">
          <i class="fas fa-warehouse"></i>
        </div>
        <h3>No warehouses found</h3>
        <p v-if="searchQuery || filters.status">
          Try adjusting your search filters or
          <button @click="resetFilters" class="btn-link">clear all filters</button>
        </p>
        <p v-else>
          Get started by adding your first warehouse.
        </p>
        <button class="btn btn-primary" @click="openAddWarehouseModal">
          <i class="fas fa-plus"></i> Add Warehouse
        </button>
      </div>
  
      <!-- Warehouses Grid -->
      <div v-else class="warehouses-grid">
        <div 
          v-for="warehouse in filteredWarehouses" 
          :key="warehouse.warehouse_id" 
          class="warehouse-card"
        >
          <div class="warehouse-header">
            <div class="warehouse-status" :class="{ 'inactive': !warehouse.is_active }">
              {{ warehouse.is_active ? 'Active' : 'Inactive' }}
            </div>
            <div class="warehouse-actions">
              <button class="action-btn" title="Edit Warehouse" @click="editWarehouse(warehouse)">
                <i class="fas fa-edit"></i>
              </button>
              <button class="action-btn" title="Delete Warehouse" @click="confirmDelete(warehouse)">
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </div>
          
          <div class="warehouse-content" @click="viewWarehouse(warehouse)">
            <div class="warehouse-icon">
              <i class="fas fa-warehouse"></i>
            </div>
            <div class="warehouse-info">
              <h3 class="warehouse-name">{{ warehouse.name }}</h3>
              <div class="warehouse-code">{{ warehouse.code }}</div>
              <p v-if="warehouse.address" class="warehouse-address">
                <i class="fas fa-map-marker-alt"></i> {{ warehouse.address }}
              </p>
            </div>
          </div>
          
          <div class="warehouse-footer">
            <div class="warehouse-stat">
              <div class="stat-label">Zones</div>
              <div class="stat-value">{{ warehouse.zoneCount || 0 }}</div>
            </div>
            <div class="warehouse-stat">
              <div class="stat-label">Locations</div>
              <div class="stat-value">{{ warehouse.locationCount || 0 }}</div>
            </div>
            <div class="warehouse-stat">
              <div class="stat-label">Items</div>
              <div class="stat-value">{{ warehouse.itemCount || 0 }}</div>
            </div>
          </div>
        </div>
      </div>
  
      <!-- Warehouse Form Modal -->
      <div v-if="showWarehouseModal" class="modal-overlay">
        <div class="modal-container">
          <div class="modal-header">
            <h2>{{ isEditMode ? 'Edit Warehouse' : 'Add New Warehouse' }}</h2>
            <button class="close-btn" @click="closeWarehouseModal">
              <i class="fas fa-times"></i>
            </button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveWarehouse" class="form">
              <div class="form-row">
                <div class="form-group">
                  <label for="name">Warehouse Name*</label>
                  <input 
                    type="text" 
                    id="name" 
                    v-model="warehouseForm.name" 
                    required
                    :class="{ 'is-invalid': validationErrors.name }"
                  />
                  <div v-if="validationErrors.name" class="invalid-feedback">
                    {{ validationErrors.name }}
                  </div>
                </div>
                <div class="form-group">
                  <label for="code">Warehouse Code*</label>
                  <input 
                    type="text" 
                    id="code" 
                    v-model="warehouseForm.code" 
                    required
                    :disabled="isEditMode"
                    :class="{ 'is-invalid': validationErrors.code }"
                  />
                  <div v-if="validationErrors.code" class="invalid-feedback">
                    {{ validationErrors.code }}
                  </div>
                </div>
              </div>
              
              <div class="form-group">
                <label for="address">Address</label>
                <textarea 
                  id="address" 
                  v-model="warehouseForm.address" 
                  rows="3"
                  :class="{ 'is-invalid': validationErrors.address }"
                ></textarea>
                <div v-if="validationErrors.address" class="invalid-feedback">
                  {{ validationErrors.address }}
                </div>
              </div>
              
              <div class="form-group">
                <div class="checkbox-group">
                  <input 
                    type="checkbox" 
                    id="is_active" 
                    v-model="warehouseForm.is_active"
                  />
                  <label for="is_active">Active</label>
                </div>
              </div>
              
              <div class="form-actions">
                <button type="button" class="btn btn-secondary" @click="closeWarehouseModal">
                  Cancel
                </button>
                <button type="submit" class="btn btn-primary" :disabled="isSaving">
                  <i v-if="isSaving" class="fas fa-spinner fa-spin"></i>
                  {{ isEditMode ? 'Update Warehouse' : 'Add Warehouse' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
  
      <!-- Delete Confirmation Modal -->
      <ConfirmationModal
        v-if="showDeleteModal"
        title="Confirm Delete"
        :message="`Are you sure you want to delete <strong>${warehouseToDelete.name}</strong>?<br>This action cannot be undone.`"
        confirmButtonText="Delete"
        confirmButtonClass="btn btn-danger"
        @confirm="deleteWarehouse"
        @close="closeDeleteModal"
      />
    </div>
  </template>
  
  <script>
  import { ref, computed, onMounted, reactive } from 'vue';
  import { useRouter } from 'vue-router';
  import WarehouseService from '@/services/WarehouseService';
  import SearchFilter from '@/components/common/SearchFilter.vue';
  import ConfirmationModal from '@/components/common/ConfirmationModal.vue';
  
  export default {
    name: 'WarehouseList',
    components: {
      SearchFilter,
      ConfirmationModal
    },
    setup() {
      const router = useRouter();
      
      // Data
      const warehouses = ref([]);
      const isLoading = ref(true);
      const error = ref(null);
      const searchQuery = ref('');
      const isSaving = ref(false);
      
      // Filters
      const filters = reactive({
        status: ''
      });
      
      // Modals
      const showWarehouseModal = ref(false);
      const showDeleteModal = ref(false);
      const isEditMode = ref(false);
      const warehouseForm = reactive({
        warehouse_id: null,
        name: '',
        code: '',
        address: '',
        is_active: true
      });
      const validationErrors = reactive({});
      const warehouseToDelete = ref({});
      
      // Computed properties
      const filteredWarehouses = computed(() => {
        let filtered = [...warehouses.value];
        
        // Apply search query
        if (searchQuery.value) {
          const query = searchQuery.value.toLowerCase();
          filtered = filtered.filter(warehouse => 
            warehouse.name.toLowerCase().includes(query) || 
            warehouse.code.toLowerCase().includes(query) ||
            (warehouse.address && warehouse.address.toLowerCase().includes(query))
          );
        }
        
        // Apply status filter
        if (filters.status !== '') {
          const isActive = filters.status === 'true';
          filtered = filtered.filter(warehouse => warehouse.is_active === isActive);
        }
        
        return filtered;
      });
      
      // Methods
      const fetchWarehouses = async () => {
        isLoading.value = true;
        error.value = null;
        
        try {
          const response = await WarehouseService.getWarehouses();
          
          // Process warehouses to add location and zone counts
          const warehousesWithDetails = response.data.map(warehouse => {
            const zoneCount = warehouse.zones ? warehouse.zones.length : 0;
            let locationCount = 0;
            
            if (warehouse.zones) {
              locationCount = warehouse.zones.reduce((total, zone) => {
                return total + (zone.locations ? zone.locations.length : 0);
              }, 0);
            }
            
            return {
              ...warehouse,
              zoneCount,
              locationCount
            };
          });
          
          warehouses.value = warehousesWithDetails;
          
          // Fetch item counts for each warehouse
          for (const warehouse of warehouses.value) {
            try {
              warehouse.itemCount = await WarehouseService.getWarehouseItemCount(warehouse.warehouse_id);
            } catch (error) {
              console.error(`Error fetching item count for warehouse ${warehouse.warehouse_id}:`, error);
              warehouse.itemCount = 0;
            }
          }
        } catch (err) {
          console.error('Error fetching warehouses:', err);
          error.value = 'Failed to load warehouses. Please try again.';
        } finally {
          isLoading.value = false;
        }
      };
      
      const applyFilters = () => {
        // Filters are handled by computed property
      };
      
      const clearSearch = () => {
        searchQuery.value = '';
      };
      
      const resetFilters = () => {
        searchQuery.value = '';
        filters.status = '';
      };
      
      const openAddWarehouseModal = () => {
        isEditMode.value = false;
        resetWarehouseForm();
        validationErrors.value = {};
        showWarehouseModal.value = true;
      };
      
      const editWarehouse = (warehouse) => {
        isEditMode.value = true;
        resetWarehouseForm();
        
        // Populate form data
        warehouseForm.warehouse_id = warehouse.warehouse_id;
        warehouseForm.name = warehouse.name;
        warehouseForm.code = warehouse.code;
        warehouseForm.address = warehouse.address || '';
        warehouseForm.is_active = warehouse.is_active;
        
        validationErrors.value = {};
        showWarehouseModal.value = true;
      };
      
      const viewWarehouse = (warehouse) => {
        router.push(`/warehouses/${warehouse.warehouse_id}`);
      };
      
      const resetWarehouseForm = () => {
        warehouseForm.warehouse_id = null;
        warehouseForm.name = '';
        warehouseForm.code = '';
        warehouseForm.address = '';
        warehouseForm.is_active = true;
      };
      
      const closeWarehouseModal = () => {
        showWarehouseModal.value = false;
      };
      
      const saveWarehouse = async () => {
        isSaving.value = true;
        validationErrors.value = {};
        
        try {
          if (isEditMode.value) {
            // Update existing warehouse
            await WarehouseService.updateWarehouse(
              warehouseForm.warehouse_id, 
              warehouseForm
            );
            
            // Update local state
            const index = warehouses.value.findIndex(w => w.warehouse_id === warehouseForm.warehouse_id);
            if (index !== -1) {
              warehouses.value[index] = {
                ...warehouses.value[index],
                name: warehouseForm.name,
                address: warehouseForm.address,
                is_active: warehouseForm.is_active
              };
            }
            
            // Close modal and show success
            closeWarehouseModal();
            alert('Warehouse updated successfully!');
          } else {
            // Create new warehouse
            const response = await WarehouseService.createWarehouse(warehouseForm);
            
            // Add to local state with default counts
            const newWarehouse = {
              ...response.data,
              zoneCount: 0,
              locationCount: 0,
              itemCount: 0
            };
            
            warehouses.value.push(newWarehouse);
            
            // Close modal and show success
            closeWarehouseModal();
            alert('Warehouse added successfully!');
          }
        } catch (err) {
          console.error('Error saving warehouse:', err);
          
          // Handle validation errors
          if (err.response && err.response.status === 422) {
            validationErrors.value = err.validationErrors || {};
          } else {
            alert('An error occurred while saving the warehouse. Please try again.');
          }
        } finally {
          isSaving.value = false;
        }
      };
      
      const confirmDelete = (warehouse) => {
        warehouseToDelete.value = warehouse;
        showDeleteModal.value = true;
      };
      
      const closeDeleteModal = () => {
        showDeleteModal.value = false;
        warehouseToDelete.value = {};
      };
      
      const deleteWarehouse = async () => {
        try {
          await WarehouseService.deleteWarehouse(warehouseToDelete.value.warehouse_id);
          
          // Remove from local state
          warehouses.value = warehouses.value.filter(
            w => w.warehouse_id !== warehouseToDelete.value.warehouse_id
          );
          
          // Close modal and show success
          closeDeleteModal();
          alert('Warehouse deleted successfully!');
        } catch (err) {
          console.error('Error deleting warehouse:', err);
          
          // Handle specific errors
          if (err.response && err.response.status === 422) {
            alert('This warehouse cannot be deleted because it has zones or transactions associated with it.');
          } else {
            alert('An error occurred while deleting the warehouse. Please try again.');
          }
          
          closeDeleteModal();
        }
      };
      
      // Lifecycle hooks
      onMounted(() => {
        fetchWarehouses();
      });
      
      return {
        // Data
        warehouses,
        isLoading,
        error,
        searchQuery,
        filters,
        showWarehouseModal,
        showDeleteModal,
        isEditMode,
        warehouseForm,
        warehouseToDelete,
        validationErrors,
        isSaving,
        filteredWarehouses,
        
        // Methods
        fetchWarehouses,
        applyFilters,
        clearSearch,
        resetFilters,
        openAddWarehouseModal,
        editWarehouse,
        viewWarehouse,
        closeWarehouseModal,
        saveWarehouse,
        confirmDelete,
        closeDeleteModal,
        deleteWarehouse
      };
    }
  };
  </script>
  
  <style scoped>
  .warehouse-list {
    padding: 1rem;
    max-width: 1200px;
    margin: 0 auto;
  }
  
  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
  }
  
  .page-header h1 {
    font-size: 1.75rem;
    margin: 0;
    color: var(--gray-800);
  }
  
  .page-actions {
    display: flex;
    gap: 0.5rem;
  }
  
  .filters-container {
    margin-bottom: 1.5rem;
  }
  
  /* Loading state */
  .loading-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem 0;
    color: var(--gray-500);
  }
  
  .loading-spinner {
    font-size: 2rem;
    margin-bottom: 1rem;
  }
  
  /* Error state */
  .error-container {
    text-align: center;
    padding: 3rem 0;
  }
  
  .error-icon {
    font-size: 2.5rem;
    color: var(--danger-color);
    margin-bottom: 1rem;
  }
  
  .error-container p {
    font-size: 1rem;
    color: var(--gray-700);
    margin-bottom: 1rem;
  }
  
  /* Empty state */
  .empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 4rem 0;
  }
  
  .empty-icon {
    font-size: 3rem;
    color: var(--gray-300);
    margin-bottom: 1rem;
  }
  
  .empty-state h3 {
    font-size: 1.25rem;
    margin-bottom: 0.5rem;
    color: var(--gray-700);
  }
  
  .empty-state p {
    margin-bottom: 1.5rem;
    color: var(--gray-600);
  }
  
  .btn-link {
    background: none;
    border: none;
    color: var(--primary-color);
    text-decoration: underline;
    cursor: pointer;
    padding: 0;
  }
  
  /* Warehouses grid */
  .warehouses-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
  }
  
  .warehouse-card {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transition: box-shadow 0.3s;
  }
  
  .warehouse-card:hover {
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  }
  
  .warehouse-header {
    padding: 0.75rem 1rem;
    background-color: var(--gray-50);
    border-bottom: 1px solid var(--gray-200);
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  
  .warehouse-status {
    font-size: 0.75rem;
    font-weight: 500;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    background-color: #d1fae5;
    color: #059669;
  }
  
  .warehouse-status.inactive {
    background-color: var(--gray-100);
    color: var(--gray-600);
  }
  
  .warehouse-actions {
    display: flex;
    gap: 0.5rem;
  }
  
  .action-btn {
    background: none;
    border: none;
    color: var(--gray-500);
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 0.25rem;
    transition: background-color 0.2s, color 0.2s;
  }
  
  .action-btn:hover {
    background-color: var(--gray-100);
    color: var(--gray-800);
  }
  
  .warehouse-content {
    padding: 1.5rem;
    display: flex;
    gap: 1rem;
    flex: 1;
    cursor: pointer;
  }
  
  .warehouse-icon {
    font-size: 2rem;
    color: var(--primary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    width: 3rem;
    height: 3rem;
    background-color: #eff6ff;
    border-radius: 0.5rem;
  }
  
  .warehouse-info {
    flex: 1;
  }
  
  .warehouse-name {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0 0 0.5rem 0;
    color: var(--gray-800);
  }
  
  .warehouse-code {
    font-size: 0.875rem;
    color: var(--gray-500);
    margin-bottom: 0.75rem;
  }
  
  .warehouse-address {
    font-size: 0.875rem;
    color: var(--gray-700);
    margin: 0;
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
  }
  
  .warehouse-address i {
    margin-top: 0.25rem;
  }
  
  .warehouse-footer {
    padding: 0.75rem 1rem;
    border-top: 1px solid var(--gray-200);
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.5rem;
  }
  
  .warehouse-stat {
    text-align: center;
  }
  
  .stat-label {
    font-size: 0.75rem;
    color: var(--gray-500);
    margin-bottom: 0.25rem;
  }
  
  .stat-value {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--gray-800);
  }
  
  /* Modal styling */
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
  
  /* Form styling */
  .form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }
  
  .form-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
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
  .form-group textarea,
  .form-group select {
    padding: 0.625rem;
    border: 1px solid var(--gray-300);
    border-radius: 0.375rem;
    font-size: 0.875rem;
  }
  
  .form-group input:disabled {
    background-color: var(--gray-100);
    cursor: not-allowed;
  }
  
  .form-group textarea {
    resize: vertical;
    min-height: 80px;
  }
  
  .checkbox-group {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }
  
  .checkbox-group input[type="checkbox"] {
    width: 1rem;
    height: 1rem;
  }
  
  .form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 1rem;
  }
  
  .form-group .is-invalid {
    border-color: var(--danger-color);
  }
  
  .invalid-feedback {
    font-size: 0.75rem;
    color: var(--danger-color);
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
  
  /* Responsive styling */
  @media (max-width: 768px) {
    .form-row {
      grid-template-columns: 1fr;
    }
    
    .warehouses-grid {
      grid-template-columns: 1fr;
    }
  }
  </style>