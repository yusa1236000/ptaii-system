<!-- src/views/inventory/LocationInventory.vue -->
<template>
    <div class="location-inventory-page">
      <div class="page-header">
        <div class="header-content">
          <h2 class="page-title">
            Inventory for Location: <span class="location-code">{{ location?.code }}</span>
          </h2>
          <div class="location-details" v-if="location">
            <div class="detail-item">
              <span class="detail-label">Zone:</span>
              <span class="detail-value">{{ zone?.name }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Warehouse:</span>
              <span class="detail-value">{{ warehouse?.name }}</span>
            </div>
          </div>
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
          <router-link :to="`/warehouses/${warehouseId}/zones/${zoneId}`" class="breadcrumb-item">
            <i class="fas fa-map-marker-alt mr-1"></i> Locations
          </router-link>
          <span class="breadcrumb-separator">/</span>
          <span class="breadcrumb-item active">Inventory</span>
        </div>
      </div>
  
      <div class="content-section">
        <div class="section-header">
          <h3 class="section-title">Inventory Items</h3>
          
          <div class="filters">
            <div class="search-input">
              <i class="fas fa-search search-icon"></i>
              <input 
                type="text" 
                v-model="searchQuery" 
                placeholder="Search items..." 
                class="form-control search-control"
              />
            </div>
          </div>
        </div>
        
        <div v-if="loading" class="loading-indicator">
          <i class="fas fa-spinner fa-spin mr-2"></i> Loading inventory...
        </div>
  
        <div v-else-if="error" class="error-message">
          <i class="fas fa-exclamation-triangle mr-2"></i>
          {{ error }}
        </div>
  
        <div v-else-if="filteredInventory.length === 0" class="empty-state">
          <i class="fas fa-box-open empty-icon"></i>
          <h3>No items found</h3>
          <p v-if="searchQuery">No items match your search criteria. Try a different search term.</p>
          <p v-else>This location doesn't have any inventory items yet.</p>
        </div>
  
        <div v-else class="inventory-table-container">
          <table class="inventory-table">
            <thead>
              <tr>
                <th>Item Code</th>
                <th>Name</th>
                <th>Category</th>
                <th class="text-right">Quantity</th>
                <th>UOM</th>
                <th v-if="canAdjustStock" class="text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in filteredInventory" :key="item.item_id">
                <td>{{ item.item_code }}</td>
                <td>{{ item.name }}</td>
                <td>{{ item.category || 'Uncategorized' }}</td>
                <td class="text-right">
                  <span :class="getQuantityClass(item.stock)">{{ item.stock }}</span>
                </td>
                <td>{{ item.uom }}</td>
                <td v-if="canAdjustStock" class="actions-cell">
                  <button class="btn-sm btn-primary" @click="openStockAdjustmentModal(item)">
                    <i class="fas fa-balance-scale-right mr-1"></i> Adjust
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
  
      <!-- Stock Adjustment Modal -->
      <div v-if="showAdjustmentModal" class="modal-backdrop">
        <div class="modal-content">
          <div class="modal-header">
            <h3>Adjust Stock</h3>
            <button class="btn-close" @click="closeAdjustmentModal">
              <i class="fas fa-times"></i>
            </button>
          </div>
          <div class="modal-body">
            <div class="item-details mb-4">
              <div><strong>Item:</strong> {{ selectedItem?.name }}</div>
              <div><strong>Code:</strong> {{ selectedItem?.item_code }}</div>
              <div><strong>Current Stock:</strong> {{ selectedItem?.stock }} {{ selectedItem?.uom }}</div>
            </div>
  
            <form @submit.prevent="submitStockAdjustment">
              <div class="form-group">
                <label for="adjustmentType">Adjustment Type</label>
                <select id="adjustmentType" v-model="adjustmentForm.type" class="form-control" required>
                  <option value="add">Add Stock</option>
                  <option value="remove">Remove Stock</option>
                  <option value="set">Set to Specific Value</option>
                </select>
              </div>
  
              <div class="form-group">
                <label for="adjustmentQuantity">Quantity</label>
                <input
                  id="adjustmentQuantity"
                  v-model.number="adjustmentForm.quantity"
                  type="number"
                  class="form-control"
                  min="0"
                  step="0.01"
                  required
                />
              </div>
  
              <div class="form-group">
                <label for="adjustmentReason">Reason</label>
                <textarea
                  id="adjustmentReason"
                  v-model="adjustmentForm.reason"
                  class="form-control"
                  rows="3"
                  required
                ></textarea>
              </div>
  
              <div class="form-group">
                <label for="adjustmentReference">Reference Number (Optional)</label>
                <input
                  id="adjustmentReference"
                  v-model="adjustmentForm.reference"
                  type="text"
                  class="form-control"
                />
              </div>
  
              <div class="adjustment-preview">
                <div class="adjustment-preview-label">Stock After Adjustment:</div>
                <div class="adjustment-preview-value" :class="getQuantityClass(calculateNewStockLevel())">
                  {{ calculateNewStockLevel() }} {{ selectedItem?.uom }}
                </div>
              </div>
  
              <div class="form-group form-actions">
                <button type="button" class="btn-secondary" @click="closeAdjustmentModal">
                  Cancel
                </button>
                <button 
                  type="submit" 
                  class="btn-primary" 
                  :disabled="isSubmitting || !isValidAdjustment()"
                >
                  <i v-if="isSubmitting" class="fas fa-spinner fa-spin mr-2"></i>
                  Confirm Adjustment
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script>
  import { ref, reactive, computed, onMounted } from 'vue';
  import { useRoute } from 'vue-router';
  import axios from 'axios';
  
  export default {
    name: 'LocationInventory',
    setup() {
      const route = useRoute();
      
      const warehouseId = computed(() => route.params.warehouseId);
      const zoneId = computed(() => route.params.zoneId);
      const locationId = computed(() => route.params.locationId);
      
      const warehouse = ref(null);
      const zone = ref(null);
      const location = ref(null);
      const inventory = ref([]);
      const loading = ref(true);
      const error = ref(null);
      const searchQuery = ref('');
      const canAdjustStock = ref(true); // Set based on user permissions
      
      // Stock adjustment modal state
      const showAdjustmentModal = ref(false);
      const selectedItem = ref(null);
      const isSubmitting = ref(false);
      const adjustmentForm = reactive({
        type: 'add',
        quantity: 0,
        reason: '',
        reference: ''
      });
  
      const filteredInventory = computed(() => {
        if (!searchQuery.value) return inventory.value;
        
        const query = searchQuery.value.toLowerCase();
        return inventory.value.filter(item => 
          item.item_code.toLowerCase().includes(query) ||
          item.name.toLowerCase().includes(query) ||
          (item.category && item.category.toLowerCase().includes(query))
        );
      });
  
      const fetchLocationData = async () => {
        loading.value = true;
        error.value = null;
        
        try {
          // Fetch location details
          const locationResponse = await axios.get(`/zones/${zoneId.value}/locations/${locationId.value}/inventory`);
          location.value = locationResponse.data.data.location;
          zone.value = locationResponse.data.data.zone;
          warehouse.value = locationResponse.data.data.warehouse;
          inventory.value = locationResponse.data.data.inventory;
        } catch (err) {
          console.error('Error fetching location inventory:', err);
          error.value = 'Failed to load location inventory. Please try again.';
        } finally {
          loading.value = false;
        }
      };
  
      const getQuantityClass = (quantity) => {
        if (quantity <= 0) return 'quantity-empty';
        if (quantity < 5) return 'quantity-low';
        return 'quantity-normal';
      };
  
      const openStockAdjustmentModal = (item) => {
        selectedItem.value = item;
        adjustmentForm.type = 'add';
        adjustmentForm.quantity = 0;
        adjustmentForm.reason = '';
        adjustmentForm.reference = '';
        showAdjustmentModal.value = true;
      };
  
      const closeAdjustmentModal = () => {
        showAdjustmentModal.value = false;
        selectedItem.value = null;
      };
  
      const calculateNewStockLevel = () => {
        if (!selectedItem.value) return 0;
        
        const currentStock = selectedItem.value.stock;
        
        switch (adjustmentForm.type) {
          case 'add':
            return currentStock + adjustmentForm.quantity;
          case 'remove':
            return Math.max(0, currentStock - adjustmentForm.quantity);
          case 'set':
            return adjustmentForm.quantity;
          default:
            return currentStock;
        }
      };
  
      const isValidAdjustment = () => {
        if (adjustmentForm.quantity <= 0) return false;
        
        if (adjustmentForm.type === 'remove' && adjustmentForm.quantity > selectedItem.value?.stock) {
          return false; // Cannot remove more than available
        }
        
        return true;
      };
  
      const submitStockAdjustment = async () => {
        if (!isValidAdjustment()) return;
        
        isSubmitting.value = true;
        
        try {
          let adjustmentQuantity;
          
          switch (adjustmentForm.type) {
            case 'add':
              adjustmentQuantity = adjustmentForm.quantity;
              break;
            case 'remove':
              adjustmentQuantity = -adjustmentForm.quantity;
              break;
            case 'set':
              adjustmentQuantity = adjustmentForm.quantity - selectedItem.value.stock;
              break;
            default:
              adjustmentQuantity = 0;
          }
          
          const payload = {
            adjustment_quantity: adjustmentQuantity,
            warehouse_id: warehouseId.value,
            location_id: locationId.value,
            reason: adjustmentForm.reason,
            reference_number: adjustmentForm.reference || null
          };
          
          await axios.post(`/items/${selectedItem.value.item_id}/update-stock`, payload);
          
          // Refresh inventory data
          await fetchLocationData();
          
          // Close modal
          closeAdjustmentModal();
        } catch (err) {
          console.error('Error adjusting stock:', err);
          error.value = err.response?.data?.message || 'Failed to adjust stock. Please try again.';
        } finally {
          isSubmitting.value = false;
        }
      };
  
      onMounted(async () => {
        await fetchLocationData();
      });
  
      return {
        warehouseId,
        zoneId,
        locationId,
        warehouse,
        zone,
        location,
        inventory,
        loading,
        error,
        searchQuery,
        filteredInventory,
        canAdjustStock,
        showAdjustmentModal,
        selectedItem,
        isSubmitting,
        adjustmentForm,
        getQuantityClass,
        openStockAdjustmentModal,
        closeAdjustmentModal,
        calculateNewStockLevel,
        isValidAdjustment,
        submitStockAdjustment
      };
    }
  };
  </script>
  
  <style scoped>
  .location-inventory-page {
    padding: 1rem;
  }
  
  .page-header {
    margin-bottom: 2rem;
  }
  
  .header-content {
    margin-bottom: 1rem;
  }
  
  .page-title {
    margin: 0 0 0.5rem 0;
    font-size: 1.5rem;
    color: var(--gray-800);
  }
  
  .location-code {
    font-weight: 700;
    color: var(--primary-color);
  }
  
  .location-details {
    display: flex;
    gap: 1.5rem;
    margin-top: 0.5rem;
  }
  
  .detail-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }
  
  .detail-label {
    font-weight: 500;
    color: var(--gray-600);
  }
  
  .detail-value {
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
  
  .content-section {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
  }
  
  .section-header {
    padding: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--gray-200);
    background-color: var(--gray-50);
  }
  
  .section-title {
    margin: 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--gray-800);
  }
  
  .filters {
    display: flex;
    gap: 1rem;
  }
  
  .search-input {
    position: relative;
  }
  
  .search-icon {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--gray-400);
  }
  
  .search-control {
    padding-left: 2.25rem;
    width: 250px;
  }
  
  .loading-indicator {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 3rem;
    color: var(--gray-500);
  }
  
  .error-message {
    padding: 1rem;
    background-color: rgba(220, 38, 38, 0.1);
    color: var(--danger-color);
    margin: 1rem;
    border-radius: 0.375rem;
  }
  
  .empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem;
    text-align: center;
  }
  
  .empty-icon {
    font-size: 3rem;
    color: var(--gray-400);
    margin-bottom: 1rem;
  }
  
  .inventory-table-container {
    overflow-x: auto;
  }
  
  .inventory-table {
    width: 100%;
    border-collapse: collapse;
  }
  
  .inventory-table th,
  .inventory-table td {
    padding: 0.75rem 1rem;
    text-align: left;
  }
  
  .inventory-table th {
    background-color: var(--gray-50);
    color: var(--gray-600);
    font-weight: 500;
    font-size: 0.875rem;
  }
  
  .inventory-table td {
    border-top: 1px solid var(--gray-200);
  }
  
  .inventory-table tr:hover {
    background-color: var(--gray-50);
  }
  
  .quantity-empty {
    color: var(--danger-color);
    font-weight: 600;
  }
  
  .quantity-low {
    color: var(--warning-color);
    font-weight: 600;
  }
  
  .quantity-normal {
    color: var(--success-color);
    font-weight: 600;
  }
  
  .text-right {
    text-align: right;
  }
  
  .actions-cell {
    text-align: right;
  }
  
  .btn-sm {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
  }
  
  .btn-primary {
    background-color: var(--primary-color);
    color: white;
    border: none;
    cursor: pointer;
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
  
  .item-details {
    padding: 1rem;
    background-color: var(--gray-50);
    border-radius: 0.375rem;
    font-size: 0.875rem;
    line-height: 1.5;
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
  
  .adjustment-preview {
    margin: 1.5rem 0;
    padding: 1rem;
    background-color: var(--gray-50);
    border-radius: 0.375rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  
  .adjustment-preview-label {
    font-weight: 500;
    color: var(--gray-700);
  }
  
  .adjustment-preview-value {
    font-size: 1.125rem;
    font-weight: 600;
  }
  
  .form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    margin-top: 1.5rem;
  }
  
  .mb-4 {
    margin-bottom: 1rem;
  }
  
  .mr-1 {
    margin-right: 0.25rem;
  }
  
  .mr-2 {
    margin-right: 0.5rem;
  }
  </style>