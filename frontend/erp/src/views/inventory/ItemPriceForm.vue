<!-- src/views/inventory/ItemPriceForm.vue -->
<template>
    <div class="item-price-form">
      <form @submit.prevent="savePrice">
        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label">Price Type</label>
            <div class="btn-group w-100" role="group">
              <input
                type="radio"
                class="btn-check"
                name="priceType"
                id="typeSale"
                value="sale"
                v-model="localPriceData.price_type"
                autocomplete="off"
              />
              <label class="btn btn-outline-success" for="typeSale">Sale Price</label>

              <input
                type="radio"
                class="btn-check"
                name="priceType"
                id="typePurchase"
                value="purchase"
                v-model="localPriceData.price_type"
                autocomplete="off"
              />
              <label class="btn btn-outline-info" for="typePurchase">Purchase Price</label>
            </div>
          </div>

          <div class="col-md-6">
            <label class="form-label">Status</label>
            <div class="btn-group w-100" role="group">
              <input
                type="radio"
                class="btn-check"
                name="status"
                id="statusActive"
                value="active"
                v-model="localPriceData.status"
                autocomplete="off"
              />
              <label class="btn btn-outline-success" for="statusActive">Active</label>

              <input
                type="radio"
                class="btn-check"
                name="status"
                id="statusInactive"
                value="inactive"
                v-model="localPriceData.status"
                autocomplete="off"
              />
              <label class="btn btn-outline-secondary" for="statusInactive">Inactive</label>
            </div>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-6">
            <div class="form-group">
              <label class="form-label">Item</label>
              <div v-if="isEditing || formState.initialItemId">
                <input type="text" class="form-control" :value="itemName" disabled />
                <small class="text-muted">Item cannot be changed after creation</small>
              </div>
              <div v-else>
                <select
                  class="form-select"
                  v-model="localPriceData.item_id"
                  :disabled="loading.items"
                  required
                >
                  <option value="" disabled>Select an item</option>
                  <option v-for="item in items" :key="item.item_id" :value="item.item_id">
                    {{ item.item_code }} - {{ item.name }}
                  </option>
                </select>
                <div v-if="loading.items" class="spinner-border spinner-border-sm mt-2" role="status">
                  <span class="visually-hidden">Loading...</span>
                </div>
              </div>
            </div>
          </div>

          <div class="col-md-6">
            <div class="form-group">
              <label class="form-label">
                {{ localPriceData.price_type === 'purchase' ? 'Vendor' : 'Customer' }}
              </label>
              <select
                class="form-select"
                v-model="localPriceData.entity_id"
                :disabled="loading.entities"
                required
              >
                <option value="" disabled>
                  {{ localPriceData.price_type === 'purchase' ? 'Select a vendor' : 'Select a customer' }}
                </option>
                <option v-for="entity in filteredEntities" :key="entity.id" :value="entity.id">
                  {{ entity.name }}
                </option>
              </select>
              <div v-if="loading.entities" class="spinner-border spinner-border-sm mt-2" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
            </div>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-4">
            <div class="form-group">
              <label class="form-label">Price</label>
              <div class="input-group">
                <span class="input-group-text">$</span>
                <input
                  type="number"
                  class="form-control"
                  v-model="localPriceData.price"
                  step="0.01"
                  min="0"
                  required
                />
              </div>
            </div>
          </div>

          <div class="col-md-4">
            <div class="form-group">
              <label class="form-label">Start Date</label>
              <input
                type="date"
                class="form-control"
                v-model="localPriceData.start_date"
                required
              />
            </div>
          </div>

          <div class="col-md-4">
            <div class="form-group">
              <label class="form-label">End Date</label>
              <input
                type="date"
                class="form-control"
                v-model="localPriceData.end_date"
                :min="localPriceData.start_date"
              />
              <small class="text-muted">Leave empty for no end date</small>
            </div>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-4">
            <div class="form-group">
              <label class="form-label">Minimum Quantity</label>
              <input
                type="number"
                class="form-control"
                v-model="localPriceData.min_quantity"
                min="0"
                step="1"
              />
              <small class="text-muted">Minimum order quantity for this price</small>
            </div>
          </div>

          <div class="col-md-4">
            <div class="form-group">
              <label class="form-label">Currency</label>
              <select class="form-select" v-model="localPriceData.currency">
                <option value="USD">USD - US Dollar</option>
                <option value="EUR">EUR - Euro</option>
                <option value="GBP">GBP - British Pound</option>
                <option value="JPY">JPY - Japanese Yen</option>
                <option value="CAD">CAD - Canadian Dollar</option>
                <option value="IDR">IDR - Indonesian Rupiah</option>
                <option value="SGD">SGD - Singapore Dollar</option>
                <option value="MYR">MYR - Malaysian Ringgit</option>
                <option value="AUD">AUD - Australian Dollar</option>
              </select>
            </div>
          </div>

          <div class="col-md-4">
            <div class="form-group">
              <label class="form-label">Price UOM</label>
              <select
                class="form-select"
                v-model="localPriceData.uom_id"
                :disabled="loading.uoms"
              >
                <option v-for="uom in uoms" :key="uom.uom_id" :value="uom.uom_id">
                  {{ uom.name }} ({{ uom.symbol }})
                </option>
              </select>
            </div>
          </div>
        </div>

        <div class="form-group mb-3">
          <label class="form-label">Notes</label>
          <textarea
            class="form-control"
            v-model="localPriceData.notes"
            rows="3"
            placeholder="Additional information about this price..."
          ></textarea>
        </div>

        <div class="row mb-3" v-if="localPriceData.price_type === 'sale'">
          <div class="col-md-6">
            <div class="form-group">
              <label class="form-label">Discount Type</label>
              <select class="form-select" v-model="localPriceData.discount_type">
                <option value="none">No Discount</option>
                <option value="percentage">Percentage</option>
                <option value="fixed">Fixed Amount</option>
              </select>
            </div>
          </div>

          <div class="col-md-6" v-if="localPriceData.discount_type !== 'none'">
            <div class="form-group">
              <label class="form-label">Discount Value</label>
              <div class="input-group">
                <input
                  type="number"
                  class="form-control"
                  v-model="localPriceData.discount_value"
                  step="0.01"
                  min="0"
                />
                <span class="input-group-text" v-if="localPriceData.discount_type === 'percentage'">%</span>
                <span class="input-group-text" v-else>$</span>
              </div>
            </div>
          </div>
        </div>

        <div class="row mb-3" v-if="localPriceData.price_type === 'purchase'">
          <div class="col-md-6">
            <div class="form-group">
              <label class="form-label">Lead Time (Days)</label>
              <input
                type="number"
                class="form-control"
                v-model="localPriceData.lead_time"
                min="0"
                step="1"
              />
            </div>
          </div>

          <div class="col-md-6">
            <div class="form-group">
              <label class="form-label">MOQ (Minimum Order Quantity)</label>
              <input
                type="number"
                class="form-control"
                v-model="localPriceData.moq"
                min="0"
                step="1"
              />
            </div>
          </div>
        </div>

        <div class="card mb-3" v-if="isEditing">
          <div class="card-header bg-light">
            <h6 class="mb-0">Price History</h6>
          </div>
          <div class="card-body p-0">
            <table class="table table-sm mb-0">
              <thead>
                <tr>
                  <th>Date Modified</th>
                  <th>Previous Price</th>
                  <th>Current Price</th>
                  <th>Change</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="priceHistory.length === 0">
                  <td colspan="4" class="text-center py-3">No price change history available</td>
                </tr>
                <tr v-for="(history, index) in priceHistory" :key="index">
                  <td>{{ formatDate(history.date) }}</td>
                  <td>{{ formatCurrency(history.old_price) }}</td>
                  <td>{{ formatCurrency(history.new_price) }}</td>
                  <td :class="getPriceChangeClass(history.old_price, history.new_price)">
                    {{ calculatePriceChange(history.old_price, history.new_price) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="card mb-4" v-if="localPriceData.price_type === 'purchase'">
          <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Vendor Price Comparison</h6>
            <span class="badge bg-primary">{{ otherVendorPrices.length }} other vendors</span>
          </div>
          <div class="card-body p-0">
            <table class="table table-sm mb-0">
              <thead>
                <tr>
                  <th>Vendor</th>
                  <th>Price</th>
                  <th>Min Quantity</th>
                  <th>Difference</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="otherVendorPrices.length === 0">
                  <td colspan="4" class="text-center py-3">No other vendor prices available</td>
                </tr>
                <tr v-for="(vendorPrice, index) in otherVendorPrices" :key="index">
                  <td>{{ vendorPrice.vendor_name }}</td>
                  <td>{{ formatCurrency(vendorPrice.price) }}</td>
                  <td>{{ vendorPrice.min_quantity }}</td>
                  <td :class="getPriceDifferenceClass(vendorPrice.price, localPriceData.price)">
                    {{ calculatePriceDifference(vendorPrice.price, localPriceData.price) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="d-flex justify-content-between mt-4">
          <button type="button" class="btn btn-secondary" @click="cancel">
            <i class="fas fa-times me-2"></i>Cancel
          </button>
          <div>
            <button type="submit" class="btn btn-primary" :disabled="loading.saving">
              <i class="fas fa-save me-2"></i>{{ isEditing ? 'Update' : 'Save' }}
              <span v-if="loading.saving" class="spinner-border spinner-border-sm ms-2" role="status"></span>
            </button>
          </div>
        </div>
      </form>
    </div>
  </template>

  <script>
  import { ref, reactive, computed, watch, onMounted } from 'vue';
  import axios from 'axios';

  export default {
    name: 'ItemPriceForm',
    props: {
      priceData: {
        type: Object,
        required: true
      },
      isEditing: {
        type: Boolean,
        default: false
      }
    },
    emits: ['save-success', 'cancel'],
    setup(props, { emit }) {
      // Create a local reactive copy of priceData to avoid mutating prop directly
      const localPriceData = reactive({ ...props.priceData });

      // Form state
      const formState = reactive({
        initialItemId: localPriceData.item_id || null
      });

      // Initialize additional fields if they don't exist
      if (props.isEditing === false) {
        if (!localPriceData.discount_type) localPriceData.discount_type = 'none';
        if (!localPriceData.discount_value) localPriceData.discount_value = 0;
        if (!localPriceData.lead_time) localPriceData.lead_time = 0;
        if (!localPriceData.moq) localPriceData.moq = 0;
      }

      // Data lists
      const items = ref([]);
      const customers = ref([]);
      const vendors = ref([]);
      const uoms = ref([]);
      const priceHistory = ref([]);
      const otherVendorPrices = ref([]);

      // UI state
      const loading = reactive({
        items: false,
        entities: false,
        uoms: false,
        saving: false,
        history: false,
        comparison: false
      });

      // Item name for display when editing
      const itemName = ref('');

      // Filter entities based on price type
      const filteredEntities = computed(() => {
        if (localPriceData.price_type === 'purchase') {
          return vendors.value;
        } else {
          return customers.value;
        }
      });

      // Watch for price type change and reset the entity
      watch(() => localPriceData.price_type, () => {
        localPriceData.entity_id = '';
      });

      // Fetch items data
      const fetchItems = async () => {
        loading.items = true;
        try {
          const response = await axios.get('/api/items');
          if (response.data.success) {
            items.value = response.data.data;

            // If editing, find item name
            if (props.isEditing || formState.initialItemId) {
              const item = items.value.find(i => i.item_id === localPriceData.item_id);
              if (item) {
                itemName.value = `${item.item_code} - ${item.name}`;
              }
            }
          }
        } catch (error) {
          console.error('Error fetching items:', error);
        } finally {
          loading.items = false;
        }
      };

      // Fetch customers data
      const fetchCustomers = async () => {
        loading.entities = true;
        try {
          const response = await axios.get('/api/customers');
          if (response.data.data) {
            customers.value = response.data.data.map(customer => ({
              id: customer.customer_id,
              name: customer.name
            }));
          }
        } catch (error) {
          console.error('Error fetching customers:', error);
        } finally {
          loading.entities = false;
        }
      };

      // Fetch vendors data
      const fetchVendors = async () => {
        loading.entities = true;
        try {
          const response = await axios.get('/api/vendors');
          if (response.data.status === 'success') {
            vendors.value = response.data.data.data.map(vendor => ({
              id: vendor.vendor_id,
              name: vendor.name
            }));
          }
        } catch (error) {
          console.error('Error fetching vendors:', error);
        } finally {
          loading.entities = false;
        }
      };

      // Fetch UOMs data
      const fetchUOMs = async () => {
        loading.uoms = true;
        try {
          const response = await axios.get('/api/uoms');
          if (response.data.success) {
            uoms.value = response.data.data;

            // Set default UOM if not set
            if (!localPriceData.uom_id && uoms.value.length > 0) {
              localPriceData.uom_id = uoms.value[0].uom_id;
            }
          }
        } catch (error) {
          console.error('Error fetching UOMs:', error);
        } finally {
          loading.uoms = false;
        }
      };

      // Fetch price history if editing
      const fetchPriceHistory = async () => {
        if (!props.isEditing || !localPriceData.price_id) {
          return;
        }

        loading.history = true;
        try {
          const response = await axios.get(`/api/item-prices/${localPriceData.price_id}/history`);
          if (response.data.status === 'success') {
            priceHistory.value = response.data.data;
          }
        } catch (error) {
          console.error('Error fetching price history:', error);
        } finally {
          loading.history = false;
        }
      };

      // Fetch other vendor prices for comparison
      const fetchOtherVendorPrices = async () => {
        if (!props.isEditing || localPriceData.price_type !== 'purchase' || !localPriceData.item_id) {
          return;
        }

        loading.comparison = true;
        try {
          const response = await axios.get(`/api/item-prices/item/${localPriceData.item_id}/vendors`);
          if (response.data.status === 'success') {
            // Filter out current vendor
            otherVendorPrices.value = response.data.data.filter(price =>
              price.vendor_id !== localPriceData.vendor_id
            );
          }
        } catch (error) {
          console.error('Error fetching vendor price comparison:', error);
        } finally {
          loading.comparison = false;
        }
      };

      // Save or update price
      const savePrice = async () => {
        loading.saving = true;
        try {
          let response;
          if (props.isEditing) {
            // Update existing price
            response = await axios.put(`/api/item-prices/${localPriceData.price_id}`, localPriceData);
          } else {
            // Create new price
            response = await axios.post('/api/item-prices', localPriceData);
          }

          if (response.data.status === 'success') {
            // Show success notification
            alert(props.isEditing ? 'Price updated successfully' : 'Price created successfully');
            emit('save-success', response.data.data);
          } else {
            alert('Error: ' + response.data.message);
          }
        } catch (error) {
          console.error('Error saving price:', error);
          if (error.response && error.response.data && error.response.data.errors) {
            // Format validation errors
            const errorMessages = Object.values(error.response.data.errors).flat().join('\n');
            alert(`Validation Error:\n${errorMessages}`);
          } else {
            alert('Error saving price. Please try again.');
          }
        } finally {
          loading.saving = false;
        }
      };

      // Cancel form
      const cancel = () => {
        emit('cancel');
      };

      // Format date
      const formatDate = (date) => {
        if (!date) return '';
        return new Date(date).toLocaleDateString('en-US', {
          year: 'numeric',
          month: 'short',
          day: 'numeric'
        });
      };

      // Format currency
      const formatCurrency = (value) => {
        if (value === undefined || value === null) return '';
        return new Intl.NumberFormat('en-US', {
          style: 'currency',
          currency: localPriceData.currency || 'USD'
        }).format(value);
      };

      // Calculate price change percentage
      const calculatePriceChange = (oldPrice, newPrice) => {
        if (!oldPrice || oldPrice === 0) return 'N/A';

        const difference = newPrice - oldPrice;
        const percentage = (difference / oldPrice) * 100;

        if (difference > 0) {
          return `+${percentage.toFixed(2)}%`;
        } else {
          return `${percentage.toFixed(2)}%`;
        }
      };

      // Get CSS class for price change
      const getPriceChangeClass = (oldPrice, newPrice) => {
        if (!oldPrice || oldPrice === 0) return '';

        const difference = newPrice - oldPrice;
        if (difference > 0) {
          return 'text-success';
        } else if (difference < 0) {
          return 'text-danger';
        }
        return '';
      };

      // Calculate price difference for vendor comparison
      const calculatePriceDifference = (otherPrice, currentPrice) => {
        if (!otherPrice || !currentPrice) return 'N/A';

        const difference = currentPrice - otherPrice;
        const percentage = (difference / otherPrice) * 100;

        if (difference > 0) {
          return `+${percentage.toFixed(2)}% more expensive`;
        } else if (difference < 0) {
          return `${Math.abs(percentage).toFixed(2)}% cheaper`;
        } else {
          return 'Same price';
        }
      };

      // Get CSS class for price difference
      const getPriceDifferenceClass = (otherPrice, currentPrice) => {
        if (!otherPrice || !currentPrice) return '';

        const difference = currentPrice - otherPrice;
        if (difference > 0) {
          return 'text-danger';
        } else if (difference < 0) {
          return 'text-success';
        }
        return '';
      };

      // Initialize form
      onMounted(() => {
        fetchItems();
        fetchUOMs();

        // Set default values for new price
        if (!props.isEditing) {
          if (!localPriceData.currency) {
            localPriceData.currency = 'USD';
          }
          if (!localPriceData.min_quantity) {
            localPriceData.min_quantity = 1;
          }
        } else {
          // Fetch history and comparison data if editing
          fetchPriceHistory();
          fetchOtherVendorPrices();
        }

        // Fetch either customers or vendors based on price type
        if (localPriceData.price_type === 'purchase') {
          fetchVendors();
        } else {
          fetchCustomers();
        }
      });

      // Watch for price type change to fetch appropriate entities
      watch(() => localPriceData.price_type, (newType) => {
        if (newType === 'purchase') {
          fetchVendors();
        } else {
          fetchCustomers();
        }
      });

      return {
        items,
        customers,
        vendors,
        uoms,
        priceHistory,
        otherVendorPrices,
        loading,
        filteredEntities,
        itemName,
        formState,
        savePrice,
        cancel,
        formatDate,
        formatCurrency,
        calculatePriceChange,
        getPriceChangeClass,
        calculatePriceDifference,
        getPriceDifferenceClass,
        localPriceData
      };
    }
  };
  </script>

  <style scoped>
  .form-label {
    font-weight: 500;
    color: var(--gray-700);
  }

  .input-group-text {
    background-color: var(--gray-100);
  }

  .btn-check:checked + .btn-outline-success {
    background-color: var(--success-color);
    border-color: var(--success-color);
  }

  .btn-check:checked + .btn-outline-info {
    background-color: #0dcaf0;
    border-color: #0dcaf0;
  }

  .card-header {
    font-weight: 500;
  }

  table th {
    font-weight: 600;
    font-size: 0.875rem;
  }

  .text-success {
    color: var(--success-color) !important;
  }

  .text-danger {
    color: var(--danger-color) !important;
  }

  .table-sm td, .table-sm th {
    padding: 0.5rem;
  }
  </style>
