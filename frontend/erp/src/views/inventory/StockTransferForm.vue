<!-- src/views/inventory/StockTransferForm.vue -->
<template>
    <div class="stock-transfer-form">
      <div class="page-header">
        <h1>Stock Transfer</h1>
        <div class="page-actions">
          <router-link to="/stock-transactions" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i> Back to Transactions
          </router-link>
        </div>
      </div>
  
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Transfer Details</h3>
        </div>
        <div class="card-body">
          <form @submit.prevent="submitTransfer">
            <!-- Item Selection -->
            <div class="form-section">
              <h4 class="section-title">Item Details</h4>
              <div class="form-row">
                <div class="form-group col-md-6">
                  <label for="item">Item*</label>
                  <div class="select-with-status">
                    <select 
                      id="item" 
                      v-model="form.item_id" 
                      class="form-control"
                      :class="{ 'is-invalid': errors.item_id }"
                      required
                    >
                      <option value="">Select an item</option>
                      <option v-for="item in items" :key="item.item_id" :value="item.item_id">
                        {{ item.item_code }} - {{ item.name }}
                      </option>
                    </select>
                    <div v-if="itemsLoading" class="status-indicator">
                      <i class="fas fa-spinner fa-spin"></i>
                    </div>
                  </div>
                  <div v-if="errors.item_id" class="error-message">
                    {{ errors.item_id }}
                  </div>
                </div>
  
                <div class="form-group col-md-6" v-if="selectedItem">
                  <label>Current Stock</label>
                  <div class="info-display">
                    <span :class="getStockStatusClass(selectedItem)">
                      {{ selectedItem.current_stock }}
                      <span class="ml-1">
                        {{ selectedItem.unitOfMeasure ? selectedItem.unitOfMeasure.symbol : '' }}
                      </span>
                    </span>
                  </div>
                </div>
              </div>
  
              <!-- Batch Selection (if applicable) -->
              <div class="form-row" v-if="selectedItem && hasBatches">
                <div class="form-group col-md-6">
                  <label for="batch">Batch</label>
                  <div class="select-with-status">
                    <select 
                      id="batch" 
                      v-model="form.batch_id" 
                      class="form-control"
                      :class="{ 'is-invalid': errors.batch_id }"
                    >
                      <option value="">Select a batch</option>
                      <option v-for="batch in batches" :key="batch.batch_id" :value="batch.batch_id">
                        {{ batch.batch_number }}
                        <template v-if="batch.expiry_date">
                          (Expires: {{ formatDate(batch.expiry_date) }})
                        </template>
                      </option>
                    </select>
                    <div v-if="batchesLoading" class="status-indicator">
                      <i class="fas fa-spinner fa-spin"></i>
                    </div>
                  </div>
                  <div v-if="errors.batch_id" class="error-message">
                    {{ errors.batch_id }}
                  </div>
                </div>
              </div>
            </div>
  
            <!-- Source Location -->
            <div class="form-section">
              <h4 class="section-title">Source Location</h4>
              <div class="form-row">
                <div class="form-group col-md-6">
                  <label for="sourceWarehouse">Source Warehouse*</label>
                  <div class="select-with-status">
                    <select 
                      id="sourceWarehouse" 
                      v-model="form.from_warehouse_id" 
                      class="form-control"
                      :class="{ 'is-invalid': errors.from_warehouse_id }"
                      required
                    >
                      <option value="">Select source warehouse</option>
                      <option v-for="warehouse in warehouses" :key="warehouse.warehouse_id" :value="warehouse.warehouse_id">
                        {{ warehouse.name }}
                      </option>
                    </select>
                    <div v-if="warehousesLoading" class="status-indicator">
                      <i class="fas fa-spinner fa-spin"></i>
                    </div>
                  </div>
                  <div v-if="errors.from_warehouse_id" class="error-message">
                    {{ errors.from_warehouse_id }}
                  </div>
                </div>
  
                <div class="form-group col-md-6">
                  <label for="sourceLocation">Source Location</label>
                  <div class="select-with-status">
                    <select 
                      id="sourceLocation" 
                      v-model="form.from_location_id" 
                      class="form-control"
                      :class="{ 'is-invalid': errors.from_location_id }"
                      :disabled="!form.from_warehouse_id || sourceLocationsLoading"
                    >
                      <option value="">Select source location</option>
                      <option v-for="location in sourceLocations" :key="location.location_id" :value="location.location_id">
                        {{ location.code }}
                      </option>
                    </select>
                    <div v-if="sourceLocationsLoading" class="status-indicator">
                      <i class="fas fa-spinner fa-spin"></i>
                    </div>
                  </div>
                  <div v-if="errors.from_location_id" class="error-message">
                    {{ errors.from_location_id }}
                  </div>
                </div>
              </div>
  
              <div class="form-row" v-if="selectedItem && form.from_warehouse_id">
                <div class="form-group col-md-6">
                  <label>Available Stock at Source</label>
                  <div class="info-display" :class="{ 'text-danger': sourceStock === 0 }">
                    {{ sourceStock !== null ? sourceStock : 'Loading...' }}
                    <span class="ml-1" v-if="selectedItem.unitOfMeasure && sourceStock !== null">
                      {{ selectedItem.unitOfMeasure.symbol }}
                    </span>
                    <div v-if="sourceStockLoading" class="spinner-inline">
                      <i class="fas fa-spinner fa-spin"></i>
                    </div>
                  </div>
                  <small class="form-text text-muted" v-if="sourceStock === 0">
                    No stock available at this location. Please select a different warehouse or location.
                  </small>
                </div>
              </div>
            </div>
  
            <!-- Destination Location -->
            <div class="form-section">
              <h4 class="section-title">Destination Location</h4>
              <div class="form-row">
                <div class="form-group col-md-6">
                  <label for="destinationWarehouse">Destination Warehouse*</label>
                  <div class="select-with-status">
                    <select 
                      id="destinationWarehouse" 
                      v-model="form.to_warehouse_id" 
                      class="form-control"
                      :class="{ 'is-invalid': errors.to_warehouse_id }"
                      required
                    >
                      <option value="">Select destination warehouse</option>
                      <option 
                        v-for="warehouse in warehouses" 
                        :key="warehouse.warehouse_id" 
                        :value="warehouse.warehouse_id"
                        :disabled="warehouse.warehouse_id === form.from_warehouse_id && !form.to_location_id"
                      >
                        {{ warehouse.name }}
                        {{ warehouse.warehouse_id === form.from_warehouse_id ? '(Same as source)' : '' }}
                      </option>
                    </select>
                    <div v-if="warehousesLoading" class="status-indicator">
                      <i class="fas fa-spinner fa-spin"></i>
                    </div>
                  </div>
                  <div v-if="errors.to_warehouse_id" class="error-message">
                    {{ errors.to_warehouse_id }}
                  </div>
                  <small 
                    v-if="form.from_warehouse_id === form.to_warehouse_id"
                    class="form-text text-warning"
                  >
                    <i class="fas fa-info-circle"></i>
                    You're transferring within the same warehouse. Please select different locations.
                  </small>
                </div>
  
                <div class="form-group col-md-6">
                  <label for="destinationLocation">Destination Location</label>
                  <div class="select-with-status">
                    <select 
                      id="destinationLocation" 
                      v-model="form.to_location_id" 
                      class="form-control"
                      :class="{ 'is-invalid': errors.to_location_id }"
                      :disabled="!form.to_warehouse_id || destinationLocationsLoading"
                    >
                      <option value="">Select destination location</option>
                      <option 
                        v-for="location in destinationLocations" 
                        :key="location.location_id" 
                        :value="location.location_id"
                        :disabled="location.location_id === form.from_location_id"
                      >
                        {{ location.code }}
                        {{ location.location_id === form.from_location_id ? '(Same as source)' : '' }}
                      </option>
                    </select>
                    <div v-if="destinationLocationsLoading" class="status-indicator">
                      <i class="fas fa-spinner fa-spin"></i>
                    </div>
                  </div>
                  <div v-if="errors.to_location_id" class="error-message">
                    {{ errors.to_location_id }}
                  </div>
                </div>
              </div>
            </div>
  
            <!-- Transfer Details -->
            <div class="form-section">
              <h4 class="section-title">Transfer Details</h4>
              <div class="form-row">
                <div class="form-group col-md-6">
                  <label for="quantity">Quantity to Transfer*</label>
                  <div class="input-group">
                    <input 
                      type="number" 
                      id="quantity" 
                      v-model.number="form.quantity" 
                      class="form-control"
                      :class="{ 'is-invalid': errors.quantity }"
                      :step="quantityStep"
                      min="0.01"
                      :max="sourceStock"
                      required
                    />
                    <div class="input-group-append" v-if="selectedItem && selectedItem.unitOfMeasure">
                      <span class="input-group-text">{{ selectedItem.unitOfMeasure.symbol }}</span>
                    </div>
                  </div>
                  <div v-if="errors.quantity" class="error-message">
                    {{ errors.quantity }}
                  </div>
                  <div 
                    v-else-if="form.quantity && sourceStock !== null && form.quantity > sourceStock" 
                    class="error-message"
                  >
                    Quantity exceeds available stock
                  </div>
                </div>
  
                <div class="form-group col-md-6">
                  <label for="transaction-date">Transfer Date*</label>
                  <input 
                    type="date" 
                    id="transaction-date" 
                    v-model="form.transaction_date" 
                    class="form-control"
                    :class="{ 'is-invalid': errors.transaction_date }"
                    required
                  />
                  <div v-if="errors.transaction_date" class="error-message">
                    {{ errors.transaction_date }}
                  </div>
                </div>
              </div>
  
              <div class="form-row">
                <div class="form-group col-md-6">
                  <label for="reference-number">Reference Number</label>
                  <input 
                    type="text" 
                    id="reference-number" 
                    v-model="form.reference_number" 
                    class="form-control"
                    :class="{ 'is-invalid': errors.reference_number }"
                    placeholder="E.g., TR-12345, etc."
                  />
                  <div v-if="errors.reference_number" class="error-message">
                    {{ errors.reference_number }}
                  </div>
                </div>
  
                <div class="form-group col-md-6">
                  <label for="notes">Notes</label>
                  <textarea 
                    id="notes" 
                    v-model="form.notes" 
                    class="form-control"
                    :class="{ 'is-invalid': errors.notes }"
                    rows="1"
                    placeholder="Additional notes about this transfer"
                  ></textarea>
                  <div v-if="errors.notes" class="error-message">
                    {{ errors.notes }}
                  </div>
                </div>
              </div>
            </div>
  
            <!-- Form Actions -->
            <div class="form-actions">
              <button type="button" class="btn btn-secondary" @click="cancel">
                Cancel
              </button>
              <button 
                type="submit" 
                class="btn btn-primary" 
                :disabled="isSubmitting || !isFormValid"
              >
                <i v-if="isSubmitting" class="fas fa-spinner fa-spin mr-2"></i>
                Complete Transfer
              </button>
            </div>
          </form>
        </div>
      </div>
  
      <!-- Transfer Confirmation Modal -->
      <div v-if="showConfirmationModal" class="modal-backdrop">
        <div class="modal-content">
          <div class="modal-header">
            <h3>Confirm Transfer</h3>
            <button class="btn-close" @click="showConfirmationModal = false">
              <i class="fas fa-times"></i>
            </button>
          </div>
          <div class="modal-body">
            <p class="mb-4">Please review the transfer details before proceeding:</p>
            
            <div class="confirmation-details">
              <div class="detail-row">
                <div class="detail-label">Item:</div>
                <div class="detail-value">
                  {{ selectedItem ? `${selectedItem.item_code} - ${selectedItem.name}` : '' }}
                </div>
              </div>
              
              <div class="detail-row">
                <div class="detail-label">From:</div>
                <div class="detail-value">
                  {{ getWarehouseName(form.from_warehouse_id) }}
                  <span v-if="form.from_location_id">
                    - {{ getLocationCode(form.from_location_id, true) }}
                  </span>
                </div>
              </div>
              
              <div class="detail-row">
                <div class="detail-label">To:</div>
                <div class="detail-value">
                  {{ getWarehouseName(form.to_warehouse_id) }}
                  <span v-if="form.to_location_id">
                    - {{ getLocationCode(form.to_location_id, false) }}
                  </span>
                </div>
              </div>
              
              <div class="detail-row">
                <div class="detail-label">Quantity:</div>
                <div class="detail-value">
                  {{ form.quantity }}
                  <span v-if="selectedItem && selectedItem.unitOfMeasure">
                    {{ selectedItem.unitOfMeasure.symbol }}
                  </span>
                </div>
              </div>
              
              <div class="detail-row">
                <div class="detail-label">Date:</div>
                <div class="detail-value">{{ formatDate(form.transaction_date) }}</div>
              </div>
              
              <div v-if="form.batch_id" class="detail-row">
                <div class="detail-label">Batch:</div>
                <div class="detail-value">{{ getBatchNumber(form.batch_id) }}</div>
              </div>
              
              <div v-if="form.reference_number" class="detail-row">
                <div class="detail-label">Reference:</div>
                <div class="detail-value">{{ form.reference_number }}</div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showConfirmationModal = false">
              Review Changes
            </button>
            <button 
              type="button" 
              class="btn btn-primary" 
              @click="confirmTransfer"
              :disabled="isSubmitting"
            >
              <i v-if="isSubmitting" class="fas fa-spinner fa-spin mr-2"></i>
              Confirm Transfer
            </button>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script>
  import { ref, reactive, computed, watch, onMounted } from 'vue';
  import { useRouter } from 'vue-router';
  import axios from 'axios';
  
  export default {
    name: 'StockTransferForm',
    setup() {
      const router = useRouter();
  
      // Form data
      const form = reactive({
        item_id: '',
        from_warehouse_id: '',
        from_location_id: '',
        to_warehouse_id: '',
        to_location_id: '',
        quantity: null,
        transaction_date: new Date().toISOString().split('T')[0], // Default to today
        reference_number: '',
        batch_id: '',
        notes: ''
      });
  
      // UI state
      const errors = reactive({});
      const isSubmitting = ref(false);
      const showConfirmationModal = ref(false);
  
      // Data for selects
      const items = ref([]);
      const warehouses = ref([]);
      const sourceLocations = ref([]);
      const destinationLocations = ref([]);
      const batches = ref([]);
      
      // Loading states
      const itemsLoading = ref(false);
      const warehousesLoading = ref(false);
      const sourceLocationsLoading = ref(false);
      const destinationLocationsLoading = ref(false);
      const batchesLoading = ref(false);
      const sourceStockLoading = ref(false);
      
      // Stock information
      const sourceStock = ref(null);
  
      // Computed properties
      const selectedItem = computed(() => {
        if (!form.item_id) return null;
        return items.value.find(item => item.item_id === form.item_id);
      });
      
      const hasBatches = computed(() => {
        return selectedItem.value && batches.value.length > 0;
      });
      
      const quantityStep = computed(() => {
        // Can be customized based on UOM of selected item
        return 0.01;
      });
      
      const isFormValid = computed(() => {
        if (!form.item_id || !form.from_warehouse_id || !form.to_warehouse_id || 
            !form.quantity || !form.transaction_date) {
          return false;
        }
        
        // Check if quantity is valid
        if (sourceStock.value !== null && form.quantity > sourceStock.value) {
          return false;
        }
        
        // Check if source and destination are different
        if (form.from_warehouse_id === form.to_warehouse_id && 
            (!form.from_location_id || !form.to_location_id || form.from_location_id === form.to_location_id)) {
          return false;
        }
        
        return true;
      });
  
      // Methods
      const fetchItems = async () => {
        itemsLoading.value = true;
        try {
          const response = await axios.get('/items');
          items.value = response.data.data;
        } catch (error) {
          console.error('Error fetching items:', error);
        } finally {
          itemsLoading.value = false;
        }
      };
  
      const fetchWarehouses = async () => {
        warehousesLoading.value = true;
        try {
          const response = await axios.get('/warehouses');
          warehouses.value = response.data.data;
        } catch (error) {
          console.error('Error fetching warehouses:', error);
        } finally {
          warehousesLoading.value = false;
        }
      };
  
      const fetchSourceLocations = async (warehouseId) => {
        if (!warehouseId) {
          sourceLocations.value = [];
          return;
        }
        
        sourceLocationsLoading.value = true;
        try {
          // This endpoint might need adjustment based on your API structure
          const response = await axios.get(`/warehouses/${warehouseId}/locations`);
          sourceLocations.value = response.data.data;
        } catch (error) {
          console.error('Error fetching source locations:', error);
          sourceLocations.value = [];
        } finally {
          sourceLocationsLoading.value = false;
        }
      };
      
      const fetchDestinationLocations = async (warehouseId) => {
        if (!warehouseId) {
          destinationLocations.value = [];
          return;
        }
        
        destinationLocationsLoading.value = true;
        try {
          const response = await axios.get(`/warehouses/${warehouseId}/locations`);
          destinationLocations.value = response.data.data;
        } catch (error) {
          console.error('Error fetching destination locations:', error);
          destinationLocations.value = [];
        } finally {
          destinationLocationsLoading.value = false;
        }
      };
  
      const fetchBatches = async (itemId) => {
        if (!itemId) {
          batches.value = [];
          return;
        }
        
        batchesLoading.value = true;
        try {
          const response = await axios.get(`/items/${itemId}/batches`);
          batches.value = response.data.data;
        } catch (error) {
          console.error('Error fetching batches:', error);
          batches.value = [];
        } finally {
          batchesLoading.value = false;
        }
      };
      
      const fetchSourceStock = async () => {
        if (!form.item_id || !form.from_warehouse_id) {
          sourceStock.value = null;
          return;
        }
        
        sourceStockLoading.value = true;
        try {
          // Build query params based on what's selected
          const params = {
            item_id: form.item_id,
            warehouse_id: form.from_warehouse_id
          };
          
          if (form.from_location_id) {
            params.location_id = form.from_location_id;
          }
          
          if (form.batch_id) {
            params.batch_id = form.batch_id;
          }
          
          // This endpoint might need adjustment based on your API structure
          const response = await axios.get(`/warehouses/${form.from_warehouse_id}/inventory`, { params });
          
          // Find the specific item's stock
          const inventoryItem = response.data.data.inventory.find(i => i.item_id === form.item_id);
          sourceStock.value = inventoryItem ? inventoryItem.stock : 0;
        } catch (error) {
          console.error('Error fetching source stock:', error);
          sourceStock.value = 0;
        } finally {
          sourceStockLoading.value = false;
        }
      };
  
      const getWarehouseName = (warehouseId) => {
        if (!warehouseId) return '';
        const warehouse = warehouses.value.find(w => w.warehouse_id === warehouseId);
        return warehouse ? warehouse.name : '';
      };
      
      const getLocationCode = (locationId, isSource) => {
        if (!locationId) return '';
        const locations = isSource ? sourceLocations.value : destinationLocations.value;
        const location = locations.find(l => l.location_id === locationId);
        return location ? location.code : '';
      };
      
      const getBatchNumber = (batchId) => {
        if (!batchId) return '';
        const batch = batches.value.find(b => b.batch_id === batchId);
        return batch ? batch.batch_number : '';
      };
  
      const getStockStatusClass = (item) => {
        if (!item) return '';
        
        if (item.current_stock <= 0) {
          return 'text-danger';
        } else if (item.current_stock <= item.minimum_stock) {
          return 'text-warning';
        } else {
          return 'text-success';
        }
      };
  
      const validateForm = () => {
        errors.item_id = '';
        errors.from_warehouse_id = '';
        errors.to_warehouse_id = '';
        errors.from_location_id = '';
        errors.to_location_id = '';
        errors.quantity = '';
        errors.transaction_date = '';
        
        let isValid = true;
        
        if (!form.item_id) {
          errors.item_id = 'Please select an item';
          isValid = false;
        }
        
        if (!form.from_warehouse_id) {
          errors.from_warehouse_id = 'Please select a source warehouse';
          isValid = false;
        }
        
        if (!form.to_warehouse_id) {
          errors.to_warehouse_id = 'Please select a destination warehouse';
          isValid = false;
        }
        
        if (form.from_warehouse_id === form.to_warehouse_id) {
          if (!form.from_location_id || !form.to_location_id) {
            errors.from_location_id = 'Locations must be specified for transfers within the same warehouse';
            errors.to_location_id = 'Locations must be specified for transfers within the same warehouse';
            isValid = false;
          } else if (form.from_location_id === form.to_location_id) {
            errors.to_location_id = 'Source and destination locations cannot be the same';
            isValid = false;
          }
        }
        
        if (!form.quantity) {
          errors.quantity = 'Please enter a quantity';
          isValid = false;
        } else if (form.quantity <= 0) {
          errors.quantity = 'Quantity must be greater than zero';
          isValid = false;
        } else if (sourceStock.value !== null && form.quantity > sourceStock.value) {
          errors.quantity = 'Quantity exceeds available stock';
          isValid = false;
        }
        
        if (!form.transaction_date) {
          errors.transaction_date = 'Please select a transaction date';
          isValid = false;
        }
        
        return isValid;
      };
  
      const submitTransfer = async () => {
        if (!validateForm()) {
          // Scroll to the first error
          const firstError = document.querySelector('.error-message');
          if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
          }
          return;
        }
        
        // Show confirmation modal
        showConfirmationModal.value = true;
      };
      
      const confirmTransfer = async () => {
        isSubmitting.value = true;
        
        try {
          // Prepare payload based on API requirements
          const payload = {
            item_id: form.item_id,
            from_warehouse_id: form.from_warehouse_id,
            from_location_id: form.from_location_id || null,
            to_warehouse_id: form.to_warehouse_id,
            to_location_id: form.to_location_id || null,
            quantity: form.quantity,
            transaction_date: form.transaction_date,
            reference_number: form.reference_number || null,
            batch_id: form.batch_id || null
          };
          
          // Execute the transfer
          await axios.post('/transactions/transfer', payload);
          
          // Success notification
          alert('Stock transfer completed successfully');
          
          // Navigate to transaction list
          router.push('/stock-transactions');
        } catch (error) {
          console.error('Error processing transfer:', error);
          
          // Close confirmation modal
          showConfirmationModal.value = false;
          
          // Handle validation errors from the server
          if (error.response && error.response.data && error.response.data.errors) {
            Object.assign(errors, error.response.data.errors);
          } else {
            alert('Failed to process transfer. Please try again.');
          }
        } finally {
          isSubmitting.value = false;
        }
      };
  
      const cancel = () => {
        router.push('/stock-transactions');
      };
  
      const formatDate = (dateString) => {
        if (!dateString) return '';
        
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
          year: 'numeric',
          month: 'short',
          day: 'numeric'
        });
      };
  
      // Watchers
      watch(() => form.item_id, (newItemId) => {
        form.batch_id = '';
        sourceStock.value = null;
        
        if (newItemId) {
          fetchBatches(newItemId);
          if (form.from_warehouse_id) {
            fetchSourceStock();
          }
        }
      });
  
      watch(() => form.from_warehouse_id, (newWarehouseId) => {
        form.from_location_id = '';
        sourceStock.value = null;
        
        if (newWarehouseId) {
          fetchSourceLocations(newWarehouseId);
          if (form.item_id) {
            fetchSourceStock();
          }
        }
      });
  
      watch(() => form.to_warehouse_id, (newWarehouseId) => {
        form.to_location_id = '';
        
        if (newWarehouseId) {
          fetchDestinationLocations(newWarehouseId);
        }
      });
      
      watch(() => form.from_location_id, () => {
        if (form.item_id && form.from_warehouse_id) {
          fetchSourceStock();
        }
      });
      
      watch(() => form.batch_id, () => {
        if (form.item_id && form.from_warehouse_id) {
          fetchSourceStock();
        }
      });
  
      // Lifecycle hooks
      onMounted(() => {
        fetchItems();
        fetchWarehouses();
      });
  
      return {
        form,
        errors,
        isSubmitting,
        showConfirmationModal,
        items,
        warehouses,
        sourceLocations,
        destinationLocations,
        batches,
        itemsLoading,
        warehousesLoading,
        sourceLocationsLoading,
        destinationLocationsLoading,
        batchesLoading,
        sourceStock,
        sourceStockLoading,
        selectedItem,
        hasBatches,
        quantityStep,
        isFormValid,
        getWarehouseName,
        getLocationCode,
        getBatchNumber,
        getStockStatusClass,
        submitTransfer,
        confirmTransfer,
        cancel,
        formatDate
      };
    }
  };
  </script>
  
  <style scoped>
  .stock-transfer-form {
    padding: 1rem;
  }
  
  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
  }
  
  .page-header h1 {
    margin: 0;
  }
  
  .page-actions {
    display: flex;
    gap: 0.75rem;
  }
  
  .form-section {
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid var(--gray-200);
  }
  
  .form-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
  }
  
  .section-title {
    margin-bottom: 1rem;
    color: var(--gray-700);
    font-size: 1.125rem;
  }
  
  .select-with-status {
    position: relative;
  }
  
  .status-indicator {
    position: absolute;
    right: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--gray-500);
  }
  
  .spinner-inline {
    display: inline-block;
    margin-left: 0.5rem;
    color: var(--gray-500);
  }
  
  .info-display {
    padding: 0.625rem;
    border: 1px solid var(--gray-300);
    border-radius: 0.375rem;
    background-color: var(--gray-50);
    min-height: 38px;
    display: flex;
    align-items: center;
    font-weight: 600;
  }
  
  .error-message {
    color: var(--danger-color);
    font-size: 0.75rem;
    margin-top: 0.25rem;
  }
  
  .form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    margin-top: 1.5rem;
  }
  
  .form-row {
    display: flex;
    flex-wrap: wrap;
    margin-right: -0.5rem;
    margin-left: -0.5rem;
  }
  
  .col-md-6 {
    flex: 0 0 50%;
    max-width: 50%;
    padding-right: 0.5rem;
    padding-left: 0.5rem;
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
  
  .form-control.is-invalid {
    border-color: var(--danger-color);
  }
  
  .form-control:disabled {
    background-color: var(--gray-100);
    cursor: not-allowed;
  }
  
  .input-group {
    position: relative;
    display: flex;
    flex-wrap: nowrap;
    align-items: stretch;
    width: 100%;
  }
  
  .input-group-append {
    margin-left: -1px;
    display: flex;
  }
  
  .input-group-text {
    display: flex;
    align-items: center;
    padding: 0.375rem 0.75rem;
    font-weight: 500;
    color: var(--gray-700);
    text-align: center;
    white-space: nowrap;
    background-color: var(--gray-100);
    border: 1px solid var(--gray-300);
    border-top-right-radius: 0.375rem;
    border-bottom-right-radius: 0.375rem;
  }
  
  .input-group .form-control {
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
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
    width: 90%;
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
  
  .modal-footer {
    padding: 1rem;
    border-top: 1px solid var(--gray-200);
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
  }
  
  .confirmation-details {
    background-color: var(--gray-50);
    border: 1px solid var(--gray-200);
    border-radius: 0.375rem;
    padding: 1rem;
    margin-bottom: 1rem;
  }
  
  .detail-row {
    display: flex;
    margin-bottom: 0.75rem;
  }
  
  .detail-row:last-child {
    margin-bottom: 0;
  }
  
  .detail-label {
    font-weight: 500;
    color: var(--gray-700);
    width: 35%;
  }
  
  .detail-value {
    flex: 1;
  }
  
  .text-warning {
    color: var(--warning-color);
  }
  
  .text-danger {
    color: var(--danger-color);
  }
  
  .text-success {
    color: var(--success-color);
  }
  
  .mb-4 {
    margin-bottom: 1.5rem;
  }
  
  .mr-2 {
    margin-right: 0.5rem;
  }
  
  .ml-1 {
    margin-left: 0.25rem;
  }
  
  /* Responsive styles */
  @media (max-width: 768px) {
    .col-md-6 {
      flex: 0 0 100%;
      max-width: 100%;
    }
    
    .form-actions {
      flex-direction: column-reverse;
      gap: 0.5rem;
    }
    
    .btn {
      width: 100%;
    }
  }
  </style>