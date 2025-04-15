<!-- src/views/inventory/WarehouseDetail.vue -->
<template>
    <div class="warehouse-detail">
      <!-- Loading State -->
      <div v-if="isLoading" class="loading-container">
        <div class="loading-spinner">
          <i class="fas fa-spinner fa-spin"></i>
        </div>
        <p>Loading warehouse details...</p>
      </div>
  
      <!-- Error State -->
      <div v-else-if="error" class="error-container">
        <div class="error-icon">
          <i class="fas fa-exclamation-triangle"></i>
        </div>
        <p>{{ error }}</p>
        <div class="error-actions">
          <button @click="fetchWarehouseDetails" class="btn btn-secondary">
            <i class="fas fa-sync"></i> Try Again
          </button>
          <button @click="goBack" class="btn btn-primary">
            <i class="fas fa-arrow-left"></i> Back to Warehouses
          </button>
        </div>
      </div>
  
      <!-- Warehouse Details -->
      <div v-else-if="warehouse" class="detail-container">
        <!-- Header with Actions -->
        <div class="detail-header">
          <div class="header-breadcrumb">
            <button @click="goBack" class="btn-link">
              <i class="fas fa-arrow-left"></i> Warehouses
            </button>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current">{{ warehouse.name }}</span>
          </div>
          <div class="header-actions">
            <button @click="editWarehouse" class="btn btn-primary">
              <i class="fas fa-edit"></i> Edit
            </button>
            <button @click="confirmDelete" class="btn btn-danger">
              <i class="fas fa-trash"></i> Delete
            </button>
          </div>
        </div>
  
        <!-- Warehouse Overview -->
        <div class="detail-card">
          <div class="card-header">
            <h2 class="card-title">Warehouse Details</h2>
            <div class="warehouse-status" :class="{ 'inactive': !warehouse.is_active }">
              {{ warehouse.is_active ? 'Active' : 'Inactive' }}
            </div>
          </div>
          <div class="card-body">
            <div class="detail-grid">
              <div class="detail-item">
                <h3 class="detail-label">Warehouse ID</h3>
                <p class="detail-value">{{ warehouse.warehouse_id }}</p>
              </div>
              <div class="detail-item">
                <h3 class="detail-label">Code</h3>
                <p class="detail-value">{{ warehouse.code }}</p>
              </div>
              <div class="detail-item">
                <h3 class="detail-label">Name</h3>
                <p class="detail-value">{{ warehouse.name }}</p>
              </div>
              <div class="detail-item">
                <h3 class="detail-label">Status</h3>
                <p class="detail-value">{{ warehouse.is_active ? 'Active' : 'Inactive' }}</p>
              </div>
              <div class="detail-item full-width">
                <h3 class="detail-label">Address</h3>
                <p class="detail-value address-value">
                  {{ warehouse.address || 'No address provided' }}
                </p>
              </div>
            </div>
          </div>
        </div>
  
        <!-- Warehouse Zones -->
        <div class="detail-card">
          <div class="card-header">
            <h2 class="card-title">Zones</h2>
            <button @click="openAddZoneModal" class="btn btn-sm btn-primary">
              <i class="fas fa-plus"></i> Add Zone
            </button>
          </div>
          <div class="card-body">
            <div v-if="!warehouse.zones || warehouse.zones.length === 0" class="empty-state-small">
              <div class="empty-icon-small">
                <i class="fas fa-map"></i>
              </div>
              <p>No zones found for this warehouse.</p>
              <button @click="openAddZoneModal" class="btn btn-sm btn-primary">
                <i class="fas fa-plus"></i> Add Zone
              </button>
            </div>
            <div v-else class="zones-container">
              <div v-for="zone in warehouse.zones" :key="zone.zone_id" class="zone-card">
                <div class="zone-header">
                  <h3 class="zone-name">{{ zone.name }}</h3>
                  <div class="zone-actions">
                    <button @click="editZone(zone)" class="action-btn-sm" title="Edit Zone">
                      <i class="fas fa-edit"></i>
                    </button>
                    <button @click="deleteZone(zone)" class="action-btn-sm" title="Delete Zone">
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </div>
                <div class="zone-body">
                  <div class="zone-code">
                    <span class="zone-label">Code:</span> {{ zone.code }}
                  </div>
                  <div class="zone-description" v-if="zone.description">
                    <span class="zone-label">Description:</span> {{ zone.description }}
                  </div>
                  <div class="zone-locations">
                    <span class="zone-label">Locations:</span> 
                    {{ zone.locations ? zone.locations.length : 0 }}
                  </div>
                </div>
                <div class="zone-footer">
                  <button @click="viewZone(zone)" class="btn btn-sm btn-secondary">
                    <i class="fas fa-eye"></i> View Locations
                  </button>
                  <button @click="addLocation(zone)" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus"></i> Add Location
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
  
        <!-- Recent Transactions -->
        <div class="detail-card">
          <div class="card-header">
            <h2 class="card-title">Recent Transactions</h2>
            <button @click="viewAllTransactions" class="btn btn-sm btn-secondary">
              <i class="fas fa-list"></i> View All
            </button>
          </div>
          <div class="card-body">
            <div v-if="isLoadingTransactions" class="loading-small">
              <i class="fas fa-spinner fa-spin"></i> Loading transactions...
            </div>
            <div v-else-if="!recentTransactions || recentTransactions.length === 0" class="empty-state-small">
              <div class="empty-icon-small">
                <i class="fas fa-exchange-alt"></i>
              </div>
              <p>No recent transactions found for this warehouse.</p>
            </div>
            <div v-else class="transactions-table">
              <table>
                <thead>
                  <tr>
                    <th>Date</th>
                    <th>Item</th>
                    <th>Type</th>
                    <th>Quantity</th>
                    <th>Reference</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="transaction in recentTransactions" :key="transaction.transaction_id">
                    <td>{{ formatDate(transaction.transaction_date) }}</td>
                    <td>{{ transaction.item ? transaction.item.name : 'Unknown Item' }}</td>
                    <td>
                      <span class="transaction-badge" :class="getTransactionTypeClass(transaction.transaction_type)">
                        {{ transaction.transaction_type }}
                      </span>
                    </td>
                    <td :class="getQuantityClass(transaction.transaction_type)">
                      {{ transaction.quantity }}
                    </td>
                    <td>{{ transaction.reference_document || '-' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
  
      <!-- Zone Form Modal -->
      <div v-if="showZoneModal" class="modal-overlay">
        <div class="modal-container">
          <div class="modal-header">
            <h2>{{ isEditingZone ? 'Edit Zone' : 'Add New Zone' }}</h2>
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
                  {{ isEditingZone ? 'Update Zone' : 'Add Zone' }}
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
        :message="`Are you sure you want to delete <strong>${warehouse.name}</strong>?<br>This action cannot be undone.`"
        confirmButtonText="Delete"
        confirmButtonClass="btn btn-danger"
        @confirm="deleteWarehouse"
        @close="closeDeleteModal"
      />
    </div>
  </template>
  
  <script>
  import { ref, reactive, onMounted } from 'vue';
  import { useRoute, useRouter } from 'vue-router';
  import WarehouseService from '@/services/WarehouseService';
  import ConfirmationModal from '@/components/common/ConfirmationModal.vue';
  
  export default {
    name: 'WarehouseDetail',
    components: {
      ConfirmationModal
    },
    setup() {
      const route = useRoute();
      const router = useRouter();
      
      // Data
      const warehouse = ref(null);
      const isLoading = ref(true);
      const error = ref(null);
      const recentTransactions = ref([]);
      const isLoadingTransactions = ref(false);
  
      // Zone Modal
      const showZoneModal = ref(false);
      const isEditingZone = ref(false);
      const isSaving = ref(false);
      const zoneForm = reactive({
        zone_id: null,
        warehouse_id: null,
        name: '',
        code: '',
        description: ''
      });
      const validationErrors = reactive({});
      
      // Delete Modal
      const showDeleteModal = ref(false);
  
      // Fetch warehouse details
      const fetchWarehouseDetails = async () => {
        const warehouseId = parseInt(route.params.id);
        isLoading.value = true;
        error.value = null;
        
        try {
          const response = await WarehouseService.getWarehouseById(warehouseId);
          warehouse.value = response.data;
          
          // Fetch recent transactions for this warehouse
          fetchRecentTransactions();
        } catch (err) {
          console.error('Error fetching warehouse details:', err);
          error.value = 'Failed to load warehouse details. Please try again.';
        } finally {
          isLoading.value = false;
        }
      };
      
      // Fetch recent transactions
      const fetchRecentTransactions = async () => {
        if (!warehouse.value) return;
        
        isLoadingTransactions.value = true;
        
        try {
          // Limited to 5 most recent transactions
          const response = await WarehouseService.getWarehouseTransactions(warehouse.value.warehouse_id, { limit: 5 });
          recentTransactions.value = response.data || [];
        } catch (err) {
          console.error('Error fetching transactions:', err);
          recentTransactions.value = [];
        } finally {
          isLoadingTransactions.value = false;
        }
      };
  
      // Format date
      const formatDate = (dateString) => {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
          year: 'numeric',
          month: 'short',
          day: 'numeric'
        });
      };
      
      // Get transaction type class
      const getTransactionTypeClass = (type) => {
        if (['IN', 'RECEIPT', 'RETURN', 'ADJUSTMENT_IN'].includes(type)) {
          return 'type-in';
        } else if (['OUT', 'ISSUE', 'SALE', 'ADJUSTMENT_OUT'].includes(type)) {
          return 'type-out';
        }
        return '';
      };
      
      // Get quantity class
      const getQuantityClass = (type) => {
        if (['IN', 'RECEIPT', 'RETURN', 'ADJUSTMENT_IN'].includes(type)) {
          return 'quantity-in';
        } else if (['OUT', 'ISSUE', 'SALE', 'ADJUSTMENT_OUT'].includes(type)) {
          return 'quantity-out';
        }
        return '';
      };
  
      // Go back to warehouses
      const goBack = () => {
        router.push('/warehouses');
      };
      
      // Edit warehouse
      const editWarehouse = () => {
        // Navigate to edit page or open modal
        router.push(`/warehouses/${warehouse.value.warehouse_id}/edit`);
      };
      
      // Confirm delete warehouse
      const confirmDelete = () => {
        showDeleteModal.value = true;
      };
      
      // Close delete modal
      const closeDeleteModal = () => {
        showDeleteModal.value = false;
      };
      
      // Delete warehouse
      const deleteWarehouse = async () => {
        try {
          await WarehouseService.deleteWarehouse(warehouse.value.warehouse_id);
          
          // Show success and redirect
          alert('Warehouse deleted successfully!');
          router.push('/warehouses');
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
      
      // Open add zone modal
      const openAddZoneModal = () => {
        isEditingZone.value = false;
        resetZoneForm();
        validationErrors.value = {};
        zoneForm.warehouse_id = warehouse.value.warehouse_id;
        showZoneModal.value = true;
      };
      
      // Edit zone
      const editZone = (zone) => {
        isEditingZone.value = true;
        resetZoneForm();
        
        // Populate form data
        zoneForm.zone_id = zone.zone_id;
        zoneForm.warehouse_id = warehouse.value.warehouse_id;
        zoneForm.name = zone.name;
        zoneForm.code = zone.code;
        zoneForm.description = zone.description || '';
        
        validationErrors.value = {};
        showZoneModal.value = true;
      };
      
      // Reset zone form
      const resetZoneForm = () => {
        zoneForm.zone_id = null;
        zoneForm.warehouse_id = null;
        zoneForm.name = '';
        zoneForm.code = '';
        zoneForm.description = '';
      };
      
      // Close zone modal
      const closeZoneModal = () => {
        showZoneModal.value = false;
      };
      
      // Save zone
      const saveZone = async () => {
        isSaving.value = true;
        validationErrors.value = {};
        
        try {
          if (isEditingZone.value) {
            // Update existing zone
            // In a real implementation, you would use a zones API endpoint
            // await WarehouseService.updateZone(zoneForm.zone_id, zoneForm);
            
            alert('Zone updated successfully!');
            
            // Update local state
            if (warehouse.value.zones) {
              const index = warehouse.value.zones.findIndex(z => z.zone_id === zoneForm.zone_id);
              if (index !== -1) {
                warehouse.value.zones[index] = {
                  ...warehouse.value.zones[index],
                  name: zoneForm.name,
                  code: zoneForm.code,
                  description: zoneForm.description
                };
              }
            }
          } else {
            // Create new zone
            // In a real implementation, you would use a zones API endpoint
            // const response = await WarehouseService.createZone(zoneForm);
            
            // Simulate response for demo
            const newZone = {
              zone_id: Date.now(), // Temporary ID for demo
              warehouse_id: zoneForm.warehouse_id,
              name: zoneForm.name,
              code: zoneForm.code,
              description: zoneForm.description,
              locations: []
            };
            
            // Add to local state
            if (!warehouse.value.zones) {
              warehouse.value.zones = [];
            }
            
            warehouse.value.zones.push(newZone);
            alert('Zone added successfully!');
          }
          
          // Close modal
          closeZoneModal();
        } catch (err) {
          console.error('Error saving zone:', err);
          
          // Handle validation errors
          if (err.response && err.response.status === 422) {
            validationErrors.value = err.validationErrors || {};
          } else {
            alert('An error occurred while saving the zone. Please try again.');
          }
        } finally {
          isSaving.value = false;
        }
      };
      
      // Delete zone
      const deleteZone = async (zone) => {
        if (confirm(`Are you sure you want to delete ${zone.name}?`)) {
          try {
            // In a real implementation, you would use a zones API endpoint
            // await WarehouseService.deleteZone(zone.zone_id);
            
            // Update local state
            if (warehouse.value.zones) {
              warehouse.value.zones = warehouse.value.zones.filter(z => z.zone_id !== zone.zone_id);
            }
            
            alert('Zone deleted successfully!');
          } catch (err) {
            console.error('Error deleting zone:', err);
            alert('An error occurred while deleting the zone. Please try again.');
          }
        }
      };
      
      // View zone details
      const viewZone = (zone) => {
        // Navigate to zone details
        router.push(`/warehouses/${warehouse.value.warehouse_id}/zones/${zone.zone_id}`);
      };
      
      // Add location to zone
      const addLocation = (zone) => {
        // Navigate to add location page or open modal
        router.push(`/warehouses/${warehouse.value.warehouse_id}/zones/${zone.zone_id}/locations/add`);
      };
      
      // View all transactions
      const viewAllTransactions = () => {
        router.push(`/stock-transactions?warehouse_id=${warehouse.value.warehouse_id}`);
      };
      
      // Lifecycle hooks
      onMounted(() => {
        fetchWarehouseDetails();
      });
      
      return {
        // Data
        warehouse,
        isLoading,
        error,
        recentTransactions,
        isLoadingTransactions,
        showZoneModal,
        isEditingZone,
        isSaving,
        zoneForm,
        validationErrors,
        showDeleteModal,
        
        // Methods
        fetchWarehouseDetails,
        formatDate,
        getTransactionTypeClass,
        getQuantityClass,
        goBack,
        editWarehouse,
        confirmDelete,
        closeDeleteModal,
        deleteWarehouse,
        openAddZoneModal,
        editZone,
        closeZoneModal,
        saveZone,
        deleteZone,
        viewZone,
        addLocation,
        viewAllTransactions
      };
    }
  };
  </script>
  
  <style scoped>
  .warehouse-detail {
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
  
  .address-value {
    white-space: pre-line;
  }
  
  /* Warehouse status */
  .warehouse-status {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
    background-color: #d1fae5;
    color: #059669;
  }
  
  .warehouse-status.inactive {
    background-color: var(--gray-100);
    color: var(--gray-600);
  }
  
  /* Zones */
  .zones-container {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1rem;
  }
  
  .zone-card {
    border: 1px solid var(--gray-200);
    border-radius: 0.375rem;
    overflow: hidden;
  }
  
  .zone-header {
    padding: 0.75rem 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background-color: var(--gray-50);
    border-bottom: 1px solid var(--gray-200);
  }
  
  .zone-name {
    font-size: 1rem;
    font-weight: 600;
    margin: 0;
    color: var(--gray-800);
  }
  
  .zone-actions {
    display: flex;
    gap: 0.25rem;
  }
  
  .action-btn-sm {
    background: none;
    border: none;
    color: var(--gray-500);
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
  }
  
  .action-btn-sm:hover {
    background-color: var(--gray-200);
    color: var(--gray-800);
  }
  
  .zone-body {
    padding: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .zone-code,
  .zone-description,
  .zone-locations {
    font-size: 0.875rem;
    color: var(--gray-700);
  }
  
  .zone-label {
    font-weight: 500;
    color: var(--gray-600);
  }
  
  .zone-footer {
    padding: 0.75rem 1rem;
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    border-top: 1px solid var(--gray-200);
    background-color: var(--gray-50);
  }
  
  /* Small button variant */
  .btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.75rem;
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
  
  /* Loading small */
  .loading-small {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 2rem 0;
    color: var(--gray-500);
    font-size: 0.875rem;
  }
  
  .loading-small i {
    margin-right: 0.5rem;
  }
  
  /* Transactions table */
  .transactions-table {
    overflow-x: auto;
  }
  
  .transactions-table table {
    width: 100%;
    border-collapse: collapse;
  }
  
  .transactions-table th {
    padding: 0.75rem;
    text-align: left;
    font-weight: 500;
    color: var(--gray-600);
    border-bottom: 1px solid var(--gray-200);
    background-color: var(--gray-50);
  }
  
  .transactions-table td {
    padding: 0.75rem;
    border-bottom: 1px solid var(--gray-100);
  }
  
  .transaction-badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
  }
  
  .transaction-badge.type-in {
    background-color: #d1fae5;
    color: #059669;
  }
  
  .transaction-badge.type-out {
    background-color: #fee2e2;
    color: #dc2626;
  }
  
  .quantity-in {
    color: #059669;
    font-weight: 500;
  }
  
  .quantity-out {
    color: #dc2626;
    font-weight: 500;
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
    
    .zones-container {
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