<!-- src/views/inventory/PriceComparisonScreen.vue -->
<template>
    <div class="price-comparison">
      <div class="card mb-4">
        <div class="card-header bg-white">
          <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Price Comparison</h5>
            <div>
              <button @click="exportToCsv" class="btn btn-outline-secondary me-2">
                <i class="fas fa-file-csv me-2"></i>Export to CSV
              </button>
              <button @click="printReport" class="btn btn-outline-primary">
                <i class="fas fa-print me-2"></i>Print
              </button>
            </div>
          </div>
        </div>
        <div class="card-body">
          <!-- Filter controls -->
          <div class="row mb-4 border-bottom pb-3">
            <div class="col-md-3">
              <div class="form-group">
                <label class="form-label">Filter By</label>
                <select v-model="filterType" class="form-select">
                  <option value="item">Item</option>
                  <option value="category">Category</option>
                  <option value="vendor">Vendor</option>
                  <option value="customer">Customer</option>
                </select>
              </div>
            </div>

            <div class="col-md-3">
              <div class="form-group">
                <label class="form-label">{{ getFilterEntityLabel }}</label>
                <select v-model="filterId" class="form-select">
                  <option value="">All {{ getFilterEntityLabel }}s</option>
                  <option v-for="entity in filterEntities" :key="entity.id" :value="entity.id">
                    {{ entity.name }}
                  </option>
                </select>
              </div>
            </div>

            <div class="col-md-2">
              <div class="form-group">
                <label class="form-label">Price Type</label>
                <select v-model="priceType" class="form-select">
                  <option value="all">All Prices</option>
                  <option value="purchase">Purchase</option>
                  <option value="sale">Sale</option>
                </select>
              </div>
            </div>

            <div class="col-md-2">
              <div class="form-group">
                <label class="form-label">Show Only</label>
                <select v-model="priceFilter" class="form-select">
                  <option value="all">All Prices</option>
                  <option value="best">Best Prices</option>
                  <option value="active">Active Only</option>
                </select>
              </div>
            </div>

            <div class="col-md-2 d-flex align-items-end">
              <button @click="fetchComparisonData" class="btn btn-primary w-100">
                <i class="fas fa-search me-2"></i>Apply Filters
              </button>
            </div>
          </div>

          <!-- Loading state -->
          <div v-if="loading" class="text-center py-5">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p class="mt-2">Loading price comparison data...</p>
          </div>

          <!-- Data display -->
          <div v-else-if="comparisonData.length > 0">
            <!-- Summary statistics -->
            <div class="row mb-4">
              <div class="col-md-3">
                <div class="card bg-light">
                  <div class="card-body text-center">
                    <h6 class="text-muted mb-2">Total Items</h6>
                    <h2 class="mb-0">{{ summaryStats.totalItems }}</h2>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="card bg-light">
                  <div class="card-body text-center">
                    <h6 class="text-muted mb-2">Avg. Purchase Price</h6>
                    <h2 class="mb-0">{{ formatCurrency(summaryStats.avgPurchasePrice) }}</h2>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="card bg-light">
                  <div class="card-body text-center">
                    <h6 class="text-muted mb-2">Avg. Sale Price</h6>
                    <h2 class="mb-0">{{ formatCurrency(summaryStats.avgSalePrice) }}</h2>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="card bg-light">
                  <div class="card-body text-center">
                    <h6 class="text-muted mb-2">Avg. Margin</h6>
                    <h2 class="mb-0" :class="summaryStats.avgMargin < 20 ? 'text-warning' : 'text-success'">
                      {{ summaryStats.avgMargin.toFixed(2) }}%
                    </h2>
                  </div>
                </div>
              </div>
            </div>

            <!-- Price comparison by item -->
            <div v-if="filterType === 'item' || filterType === 'category'" class="table-responsive">
              <table class="table table-striped table-bordered">
                <thead class="table-light">
                  <tr>
                    <th>Item</th>
                    <th v-if="priceType !== 'sale'" class="bg-light-blue text-center">
                      Best Purchase Price
                    </th>
                    <th v-if="priceType !== 'sale' && priceFilter !== 'best'" class="bg-light-blue text-center">
                      Vendor Comparison
                    </th>
                    <th v-if="priceType !== 'purchase'" class="bg-light-green text-center">
                      Best Sale Price
                    </th>
                    <th v-if="priceType !== 'purchase' && priceFilter !== 'best'" class="bg-light-green text-center">
                      Customer Comparison
                    </th>
                    <th v-if="priceType === 'all'" class="bg-warning text-center">
                      Margin
                    </th>
                    <th class="text-center">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in comparisonData" :key="item.item_id">
                    <td>
                      <div class="fw-bold">{{ item.item_code }}</div>
                      <div>{{ item.item_name }}</div>
                      <div class="text-muted small">{{ item.category_name }}</div>
                    </td>

                    <!-- Best Purchase Price -->
                    <td v-if="priceType !== 'sale'" class="bg-light-blue">
                      <div v-if="item.best_purchase_price">
                        <div class="d-flex justify-content-between">
                          <span class="fw-bold">{{ formatCurrency(item.best_purchase_price.price) }}</span>
                          <span class="badge bg-info">{{ item.best_purchase_price.vendor_name }}</span>
                        </div>
                        <div class="d-flex justify-content-between text-muted small mt-1">
                          <span>Min Qty: {{ item.best_purchase_price.min_quantity }}</span>
                          <span>{{ formatDate(item.best_purchase_price.start_date) }}</span>
                        </div>
                      </div>
                      <div v-else class="text-center text-muted py-2">
                        No purchase price available
                      </div>
                    </td>

                    <!-- Purchase Price Comparison -->
                    <td v-if="priceType !== 'sale' && priceFilter !== 'best'" class="bg-light-blue">
                      <div v-if="item.purchase_prices && item.purchase_prices.length > 0">
                        <ul class="price-list">
                          <li v-for="(price, index) in item.purchase_prices.slice(0, 3)" :key="index" class="price-item">
                            <div class="d-flex justify-content-between">
                              <span>{{ formatCurrency(price.price) }}</span>
                              <span class="vendor-name">{{ price.vendor_name }}</span>
                            </div>
                            <div class="text-muted small">
                              <span>Min Qty: {{ price.min_quantity }}</span>
                            </div>
                          </li>
                        </ul>
                        <div v-if="item.purchase_prices.length > 3" class="text-center mt-1">
                          <small class="text-muted">
                            + {{ item.purchase_prices.length - 3 }} more vendor(s)
                          </small>
                        </div>
                      </div>
                      <div v-else class="text-center text-muted py-2">
                        No other vendors
                      </div>
                    </td>

                    <!-- Best Sale Price -->
                    <td v-if="priceType !== 'purchase'" class="bg-light-green">
                      <div v-if="item.best_sale_price">
                        <div class="d-flex justify-content-between">
                          <span class="fw-bold">{{ formatCurrency(item.best_sale_price.price) }}</span>
                          <span class="badge bg-success">{{ item.best_sale_price.customer_name }}</span>
                        </div>
                        <div class="d-flex justify-content-between text-muted small mt-1">
                          <span>Min Qty: {{ item.best_sale_price.min_quantity }}</span>
                          <span>{{ formatDate(item.best_sale_price.start_date) }}</span>
                        </div>
                      </div>
                      <div v-else class="text-center text-muted py-2">
                        No sale price available
                      </div>
                    </td>

                    <!-- Sale Price Comparison -->
                    <td v-if="priceType !== 'purchase' && priceFilter !== 'best'" class="bg-light-green">
                      <div v-if="item.sale_prices && item.sale_prices.length > 0">
                        <ul class="price-list">
                          <li v-for="(price, index) in item.sale_prices.slice(0, 3)" :key="index" class="price-item">
                            <div class="d-flex justify-content-between">
                              <span>{{ formatCurrency(price.price) }}</span>
                              <span class="customer-name">{{ price.customer_name }}</span>
                            </div>
                            <div class="text-muted small">
                              <span>Min Qty: {{ price.min_quantity }}</span>
                            </div>
                          </li>
                        </ul>
                        <div v-if="item.sale_prices.length > 3" class="text-center mt-1">
                          <small class="text-muted">
                            + {{ item.sale_prices.length - 3 }} more customer(s)
                          </small>
                        </div>
                      </div>
                      <div v-else class="text-center text-muted py-2">
                        No other customers
                      </div>
                    </td>

                    <!-- Margin -->
                    <td v-if="priceType === 'all'" :class="getMarginClass(item)">
                      <div v-if="item.best_purchase_price && item.best_sale_price">
                        <div class="text-center">
                          <span class="fw-bold">{{ calculateMargin(item) }}%</span>
                        </div>
                        <div class="text-center text-muted small mt-1">
                          <span>Diff: {{ formatCurrency(item.best_sale_price.price - item.best_purchase_price.price) }}</span>
                        </div>
                      </div>
                      <div v-else class="text-center text-muted py-2">
                        N/A
                      </div>
                    </td>

                    <!-- Actions -->
                    <td class="text-center">
                      <div class="btn-group">
                        <button
                          @click="navigateToItemPrices(item.item_id)"
                          class="btn btn-sm btn-outline-primary"
                          title="View all prices for this item"
                        >
                          <i class="fas fa-eye"></i>
                        </button>
                        <button
                          @click="showAddPriceModal(item)"
                          class="btn btn-sm btn-outline-success"
                          title="Add new price for this item"
                        >
                          <i class="fas fa-plus"></i>
                        </button>
                        <button
                          @click="showPriceHistoryModal(item)"
                          class="btn btn-sm btn-outline-info"
                          title="View price history"
                        >
                          <i class="fas fa-history"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Price comparison by vendor/customer -->
            <div v-else-if="filterType === 'vendor' || filterType === 'customer'" class="table-responsive">
              <table class="table table-striped table-bordered">
                <thead class="table-light">
                  <tr>
                    <th>{{ filterType === 'vendor' ? 'Vendor' : 'Customer' }}</th>
                    <th>Item</th>
                    <th>Price</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Min Quantity</th>
                    <th>Status</th>
                    <th v-if="filterType === 'vendor'">Best Price</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(price, index) in comparisonData" :key="index">
                    <td>
                      <div class="fw-bold">
                        {{ filterType === 'vendor' ? price.vendor_name : price.customer_name }}
                      </div>
                    </td>
                    <td>
                      <div class="fw-bold">{{ price.item_code }}</div>
                      <div>{{ price.item_name }}</div>
                      <div class="text-muted small">{{ price.category_name }}</div>
                    </td>
                    <td>{{ formatCurrency(price.price) }}</td>
                    <td>{{ formatDate(price.start_date) }}</td>
                    <td>{{ price.end_date ? formatDate(price.end_date) : 'No End Date' }}</td>
                    <td>{{ price.min_quantity }}</td>
                    <td>
                      <span
                        :class="price.status === 'active' ? 'badge bg-success' : 'badge bg-secondary'"
                      >
                        {{ price.status }}
                      </span>
                    </td>
                    <td v-if="filterType === 'vendor'">
                      <span
                        v-if="price.is_best"
                        class="badge bg-warning"
                        title="This is the best price for this item"
                      >
                        Best Price
                      </span>
                      <span v-else class="text-muted">-</span>
                    </td>
                    <td>
                      <div class="btn-group">
                        <button
                          @click="editPrice(price)"
                          class="btn btn-sm btn-outline-primary"
                          title="Edit price"
                        >
                          <i class="fas fa-edit"></i>
                        </button>
                        <button
                          @click="togglePriceStatus(price)"
                          class="btn btn-sm btn-outline-warning"
                          :title="price.status === 'active' ? 'Deactivate price' : 'Activate price'"
                        >
                          <i :class="price.status === 'active' ? 'fas fa-ban' : 'fas fa-check'"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Empty state -->
          <div v-else class="text-center py-5">
            <i class="fas fa-search-dollar fa-3x text-muted mb-3"></i>
            <h5>No Price Data Found</h5>
            <p class="text-muted">No price data matches your current filter criteria.</p>
            <button @click="resetFilters" class="btn btn-outline-primary mt-2">
              Reset Filters
            </button>
          </div>
        </div>
      </div>

      <!-- Price History Modal -->
      <div v-if="showHistoryModal" class="modal fade show" tabindex="-1" style="display: block;">
        <div class="modal-dialog modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Price History</h5>
              <button @click="closeHistoryModal" type="button" class="btn-close"></button>
            </div>
            <div class="modal-body">
              <div v-if="loading" class="text-center py-4">
                <i class="fas fa-spinner fa-spin fa-2x"></i>
                <p class="mt-2">Loading price history...</p>
              </div>
              <div v-else>
                <div class="d-flex justify-content-between mb-3">
                  <div>
                    <h6>{{ selectedItem.item_code }} - {{ selectedItem.item_name }}</h6>
                    <p class="text-muted">{{ selectedItem.category_name }}</p>
                  </div>
                  <div class="btn-group">
                    <button
                      @click="historyChartType = 'line'"
                      :class="['btn', 'btn-sm', historyChartType === 'line' ? 'btn-primary' : 'btn-outline-primary']"
                    >
                      <i class="fas fa-chart-line me-1"></i>Line
                    </button>
                    <button
                      @click="historyChartType = 'table'"
                      :class="['btn', 'btn-sm', historyChartType === 'table' ? 'btn-primary' : 'btn-outline-primary']"
                    >
                      <i class="fas fa-table me-1"></i>Table
                    </button>
                  </div>
                </div>

                <!-- Price History Chart -->
                <div v-if="historyChartType === 'line'" class="price-history-chart">
                  <canvas id="priceHistoryChart" height="300"></canvas>
                </div>

                <!-- Price History Table -->
                <div v-else class="table-responsive">
                  <table class="table table-striped table-bordered">
                    <thead>
                      <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Entity</th>
                        <th>Price</th>
                        <th>Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(history, index) in priceHistory" :key="index">
                        <td>{{ formatDate(history.start_date) }}</td>
                        <td>
                          <span
                            :class="history.price_type === 'purchase' ? 'badge bg-info' : 'badge bg-success'"
                          >
                            {{ history.price_type === 'purchase' ? 'Purchase' : 'Sale' }}
                          </span>
                        </td>
                        <td>{{ history.entity_name }}</td>
                        <td>{{ formatCurrency(history.price) }}</td>
                        <td>
                          <span
                            :class="history.status === 'active' ? 'badge bg-success' : 'badge bg-secondary'"
                          >
                            {{ history.status }}
                          </span>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button @click="closeHistoryModal" type="button" class="btn btn-secondary">
                Close
              </button>
            </div>
          </div>
        </div>
        <div class="modal-backdrop fade show"></div>
      </div>

      <!-- Add Price Modal -->
      <div v-if="showAddPriceForm" class="modal fade show" tabindex="-1" style="display: block;">
        <div class="modal-dialog modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Add New Price</h5>
              <button @click="closeAddPriceModal" type="button" class="btn-close"></button>
            </div>
            <div class="modal-body">
              <item-price-form
                :price-data="newPriceData"
                :is-editing="false"
                @save-success="onPriceSaved"
                @cancel="closeAddPriceModal"
              ></item-price-form>
            </div>
          </div>
        </div>
        <div class="modal-backdrop fade show"></div>
      </div>
    </div>
  </template>
  <script>
  import { ref, computed, onMounted, watch, nextTick } from 'vue';
  import { useRouter } from 'vue-router';
  import axios from 'axios';
  import ItemPriceForm from './ItemPriceForm.vue';
  import ItemPriceService from '@/services/ItemPriceService';
  import Chart from 'chart.js/auto';

  export default {
    name: 'PriceComparisonScreen',
    components: {
      ItemPriceForm
    },
    setup() {
      const router = useRouter();

      // State
      const loading = ref(false);
      const comparisonData = ref([]);

      // Filter state
      const filterType = ref('item');
      const filterId = ref('');
      const priceType = ref('all');
      const priceFilter = ref('all');

      // Data for filters
      const items = ref([]);
      const categories = ref([]);
      const vendors = ref([]);
      const customers = ref([]);

      // Modal states
      const showHistoryModal = ref(false);
      const showAddPriceForm = ref(false);
      const selectedItem = ref({});
      const priceHistory = ref([]);
      const historyChartType = ref('line');
      const priceHistoryChart = ref(null);

      // New price data
      const newPriceData = ref({
        price_type: 'sale',
        item_id: null,
        price: 0,
        status: 'active',
        start_date: new Date().toISOString().split('T')[0],
        currency: 'USD',
        min_quantity: 1
      });

      // Summary statistics
      const summaryStats = computed(() => {
        if (comparisonData.value.length === 0) {
          return {
            totalItems: 0,
            avgPurchasePrice: 0,
            avgSalePrice: 0,
            avgMargin: 0
          };
        }

        const itemsWithPurchasePrices = comparisonData.value.filter(item => item.best_purchase_price);
        const itemsWithSalePrices = comparisonData.value.filter(item => item.best_sale_price);
        const itemsWithBothPrices = comparisonData.value.filter(item =>
          item.best_purchase_price && item.best_sale_price
        );

        const totalPurchasePrice = itemsWithPurchasePrices.reduce((sum, item) =>
          sum + item.best_purchase_price.price, 0
        );

        const totalSalePrice = itemsWithSalePrices.reduce((sum, item) =>
          sum + item.best_sale_price.price, 0
        );

        const totalMargin = itemsWithBothPrices.reduce((sum, item) => {
          const purchasePrice = item.best_purchase_price.price;
          const salePrice = item.best_sale_price.price;
          return sum + ((salePrice - purchasePrice) / purchasePrice) * 100;
        }, 0);

        return {
          totalItems: comparisonData.value.length,
          avgPurchasePrice: itemsWithPurchasePrices.length > 0 ?
            totalPurchasePrice / itemsWithPurchasePrices.length : 0,
          avgSalePrice: itemsWithSalePrices.length > 0 ?
            totalSalePrice / itemsWithSalePrices.length : 0,
          avgMargin: itemsWithBothPrices.length > 0 ?
            totalMargin / itemsWithBothPrices.length : 0
        };
      });

      // Computed property for filter entities
      const filterEntities = computed(() => {
        switch (filterType.value) {
          case 'item':
            return items.value.map(item => ({
              id: item.item_id,
              name: `${item.item_code} - ${item.name}`
            }));
          case 'category':
            return categories.value.map(category => ({
              id: category.category_id,
              name: category.name
            }));
          case 'vendor':
            return vendors.value.map(vendor => ({
              id: vendor.vendor_id,
              name: vendor.name
            }));
          case 'customer':
            return customers.value.map(customer => ({
              id: customer.customer_id,
              name: customer.name
            }));
          default:
            return [];
        }
      });

      // Get label for the filter entity
      const getFilterEntityLabel = computed(() => {
        switch (filterType.value) {
          case 'item':
            return 'Item';
          case 'category':
            return 'Category';
          case 'vendor':
            return 'Vendor';
          case 'customer':
            return 'Customer';
          default:
            return 'Entity';
        }
      });

      // Fetch filter data
      const fetchFilterData = async () => {
        try {
          // Fetch items
          const itemsResponse = await axios.get('/api/items');
          if (itemsResponse.data.success) {
            items.value = itemsResponse.data.data;
          }

          // Fetch categories
          const categoriesResponse = await axios.get('/api/categories');
          if (categoriesResponse.data.success) {
            categories.value = categoriesResponse.data.data;
          }

          // Fetch vendors
          const vendorsResponse = await axios.get('/api/vendors');
          if (vendorsResponse.data.status === 'success') {
            vendors.value = vendorsResponse.data.data.data;
          }

          // Fetch customers
          const customersResponse = await axios.get('/api/customers');
          if (customersResponse.data.data) {
            customers.value = customersResponse.data.data;
          }
        } catch (error) {
          console.error('Error fetching filter data:', error);
        }
      };

      // Fetch comparison data
      const fetchComparisonData = async () => {
        loading.value = true;
        try {
          const response = await ItemPriceService.getPriceComparison({
            filter_type: filterType.value,
            filter_id: filterId.value,
            price_type: priceType.value,
            price_filter: priceFilter.value
          });

          if (response.data.status === 'success') {
            comparisonData.value = response.data.data;
          } else {
            console.error('Error fetching comparison data:', response.data.message);
            comparisonData.value = [];
          }
        } catch (error) {
          console.error('Error fetching comparison data:', error);
          comparisonData.value = [];
        } finally {
          loading.value = false;
        }
      };

      // Reset filters
      const resetFilters = () => {
        filterType.value = 'item';
        filterId.value = '';
        priceType.value = 'all';
        priceFilter.value = 'all';
        fetchComparisonData();
      };

      // Calculate margin percentage
      const calculateMargin = (item) => {
        if (!item.best_purchase_price || !item.best_sale_price) {
          return 'N/A';
        }

        const purchasePrice = item.best_purchase_price.price;
        const salePrice = item.best_sale_price.price;

        if (purchasePrice <= 0) {
          return 'N/A';
        }

        const margin = ((salePrice - purchasePrice) / purchasePrice) * 100;
        return margin.toFixed(2);
      };

      // Get CSS class for margin cell
      const getMarginClass = (item) => {
        if (!item.best_purchase_price || !item.best_sale_price) {
          return '';
        }

        const purchasePrice = item.best_purchase_price.price;
        const salePrice = item.best_sale_price.price;

        if (purchasePrice <= 0) {
          return '';
        }

        const margin = ((salePrice - purchasePrice) / purchasePrice) * 100;

        if (margin < 0) {
          return 'bg-danger text-white';
        } else if (margin < 15) {
          return 'bg-warning';
        } else if (margin > 50) {
          return 'bg-success text-white';
        } else {
          return 'bg-warning';
        }
      };

      // Format currency value
      const formatCurrency = (value) => {
        if (!value) return '$0.00';
        return new Intl.NumberFormat('en-US', {
          style: 'currency',
          currency: 'USD'
        }).format(value);
      };

      // Format date value
      const formatDate = (dateString) => {
        if (!dateString) return '';
        const date = new Date(dateString);
        return new Intl.DateTimeFormat('en-US', {
          year: 'numeric',
          month: 'short',
          day: 'numeric'
        }).format(date);
      };

      // Navigate to item prices
      const navigateToItemPrices = (itemId) => {
        router.push(`/items/${itemId}/prices`);
      };

      // Show price history modal
    const showPriceHistoryModal = async (item) => {
      selectedItem.value = item;
      showHistoryModal.value = true;
      loading.value = true;

      try {
        const response = await ItemPriceService.getPriceHistory(item.item_id);
        if (response.data.status === 'success') {
          priceHistory.value = response.data.data.prices;

          // Create chart in next tick after DOM is updated
          nextTick(() => {
            createPriceHistoryChart();
          });
        }
      } catch (error) {
        console.error('Error fetching price history:', error);
      } finally {
        loading.value = false;
      }
    };

    // Create price history chart
    const createPriceHistoryChart = () => {
      const canvas = document.getElementById('priceHistoryChart');
      if (!canvas) return;

      // Destroy existing chart if exists
      if (priceHistoryChart.value) {
        priceHistoryChart.value.destroy();
      }

      // Prepare data
      const purchasePrices = priceHistory.value
        .filter(price => price.price_type === 'purchase' && price.status === 'active')
        .sort((a, b) => new Date(a.start_date) - new Date(b.start_date));

      const salePrices = priceHistory.value
        .filter(price => price.price_type === 'sale' && price.status === 'active')
        .sort((a, b) => new Date(a.start_date) - new Date(b.start_date));

      // Get all unique dates
      const allDates = [...new Set([
        ...purchasePrices.map(price => price.start_date),
        ...salePrices.map(price => price.start_date)
      ])].sort((a, b) => new Date(a) - new Date(b));

      // Create chart
      priceHistoryChart.value = new Chart(canvas, {
        type: 'line',
        data: {
          labels: allDates.map(date => formatDate(date)),
          datasets: [
            {
              label: 'Purchase Prices',
              data: purchasePrices.map(price => price.price),
              borderColor: 'rgba(54, 162, 235, 1)',
              backgroundColor: 'rgba(54, 162, 235, 0.2)',
              fill: false,
              tension: 0.1
            },
            {
              label: 'Sale Prices',
              data: salePrices.map(price => price.price),
              borderColor: 'rgba(75, 192, 192, 1)',
              backgroundColor: 'rgba(75, 192, 192, 0.2)',
              fill: false,
              tension: 0.1
            }
          ]
        },
        options: {
          responsive: true,
          plugins: {
            title: {
              display: true,
              text: 'Price History'
            },
            tooltip: {
              callbacks: {
                label: function(context) {
                  let label = context.dataset.label || '';
                  if (label) {
                    label += ': ';
                  }
                  if (context.parsed.y !== null) {
                    label += formatCurrency(context.parsed.y);
                  }
                  return label;
                }
              }
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                callback: function(value) {
                  return formatCurrency(value);
                }
              }
            }
          }
        }
      });
    };

    // Close price history modal
    const closeHistoryModal = () => {
      showHistoryModal.value = false;
      selectedItem.value = {};
      priceHistory.value = [];

      // Destroy chart if exists
      if (priceHistoryChart.value) {
        priceHistoryChart.value.destroy();
        priceHistoryChart.value = null;
      }
    };

    // Show add price modal
    const showAddPriceModal = (item) => {
      newPriceData.value = {
        price_type: 'sale',
        item_id: item.item_id,
        price: 0,
        status: 'active',
        start_date: new Date().toISOString().split('T')[0],
        currency: 'USD',
        min_quantity: 1
      };
      showAddPriceForm.value = true;
    };

    // Close add price modal
    const closeAddPriceModal = () => {
      showAddPriceForm.value = false;
    };

    // Handle price save success
    const onPriceSaved = () => {
      closeAddPriceModal();
      fetchComparisonData();
    };

    // Edit price
    const editPrice = (price) => {
      router.push(`/item-prices/${price.price_id}/edit`);
    };

    // Toggle price status
    const togglePriceStatus = async (price) => {
      try {
        const newStatus = price.status === 'active' ? 'inactive' : 'active';
        await ItemPriceService.updatePriceStatus(price.price_id, newStatus);
        price.status = newStatus;
      } catch (error) {
        console.error('Error updating price status:', error);
        alert('Failed to update price status');
      }
    };

    // Export data to CSV
    const exportToCsv = () => {
      if (comparisonData.value.length === 0) {
        alert('No data to export');
        return;
      }

      let csvContent = '';
      let headers = [];

      // Set headers based on view type
      if (filterType.value === 'item' || filterType.value === 'category') {
        headers = ['Item Code', 'Item Name', 'Category'];

        if (priceType.value !== 'sale') {
          headers.push('Best Purchase Price', 'Vendor');
        }

        if (priceType.value !== 'purchase') {
          headers.push('Best Sale Price', 'Customer');
        }

        if (priceType.value === 'all') {
          headers.push('Margin %');
        }
      } else {
        headers = [
          filterType.value === 'vendor' ? 'Vendor' : 'Customer',
          'Item Code',
          'Item Name',
          'Category',
          'Price',
          'Start Date',
          'End Date',
          'Min Quantity',
          'Status'
        ];

        if (filterType.value === 'vendor') {
          headers.push('Is Best Price');
        }
      }

      // Add headers to CSV
      csvContent += headers.join(',') + '\n';

      // Add data rows
      comparisonData.value.forEach(item => {
        let row = [];

        if (filterType.value === 'item' || filterType.value === 'category') {
          row = [
            `"${item.item_code}"`,
            `"${item.item_name}"`,
            `"${item.category_name}"`
          ];

          if (priceType.value !== 'sale') {
            row.push(
              item.best_purchase_price ? item.best_purchase_price.price : '',
              item.best_purchase_price ? `"${item.best_purchase_price.vendor_name}"` : ''
            );
          }

          if (priceType.value !== 'purchase') {
            row.push(
              item.best_sale_price ? item.best_sale_price.price : '',
              item.best_sale_price ? `"${item.best_sale_price.customer_name}"` : ''
            );
          }

          if (priceType.value === 'all') {
            row.push(calculateMargin(item));
          }
        } else {
          row = [
            `"${filterType.value === 'vendor' ? item.vendor_name : item.customer_name}"`,
            `"${item.item_code}"`,
            `"${item.item_name}"`,
            `"${item.category_name}"`,
            item.price,
            `"${formatDate(item.start_date)}"`,
            `"${item.end_date ? formatDate(item.end_date) : 'No End Date'}"`,
            item.min_quantity,
            `"${item.status}"`
          ];

          if (filterType.value === 'vendor') {
            row.push(item.is_best ? 'Yes' : 'No');
          }
        }

        csvContent += row.join(',') + '\n';
      });

      // Create download link
      const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');

      link.setAttribute('href', url);
      link.setAttribute('download', `price-comparison-${new Date().toISOString().split('T')[0]}.csv`);
      link.style.visibility = 'hidden';

      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    };

    // Print report
    const printReport = () => {
      window.print();
    };

    // Reset filter ID when filter type changes
    watch(filterType, () => {
      filterId.value = '';
    });

    // Initialize component
    onMounted(() => {
      fetchFilterData();
      fetchComparisonData();
    });

    return {
      loading,
      comparisonData,
      filterType,
      filterId,
      priceType,
      priceFilter,
      filterEntities,
      getFilterEntityLabel,
      fetchComparisonData,
      resetFilters,
      calculateMargin,
      getMarginClass,
      formatCurrency,
      formatDate,
      exportToCsv,
      printReport,
      navigateToItemPrices,
      showPriceHistoryModal,
      closeHistoryModal,
      showHistoryModal,
      selectedItem,
      priceHistory,
      historyChartType,
      priceHistoryChart,
      showAddPriceModal,
      closeAddPriceModal,
      showAddPriceForm,
      newPriceData,
      onPriceSaved,
      editPrice,
      togglePriceStatus,
      summaryStats
    };
  }
};
</script>
<style scoped>
.bg-light-blue {
  background-color: rgba(13, 110, 253, 0.1);
}

.bg-light-green {
  background-color: rgba(25, 135, 84, 0.1);
}

.price-list {
  list-style-type: none;
  padding: 0;
  margin: 0;
}

.price-item {
  border-bottom: 1px solid #e2e8f0;
  padding: 6px 0;
}

.price-item:last-child {
  border-bottom: none;
}

.vendor-name, .customer-name {
  font-size: 0.85rem;
  font-weight: 500;
}

.card {
  border-radius: 0.5rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.card-header {
  border-bottom: 1px solid #e2e8f0;
  padding: 1rem 1.5rem;
}

.price-history-chart {
  height: 300px;
  position: relative;
}

.form-label {
  font-weight: 500;
  margin-bottom: 0.5rem;
}

.badge {
  font-weight: 500;
  padding: 0.35em 0.65em;
}

.table th {
  font-size: 0.875rem;
  font-weight: 600;
}

.table-bordered {
  border-color: #e2e8f0;
}

.btn-group .btn {
  padding: 0.25rem 0.5rem;
  font-size: 0.875rem;
}

.modal-content {
  border-radius: 0.5rem;
  box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
}

.modal-header, .modal-footer {
  border-color: #e2e8f0;
  padding: 1rem 1.5rem;
}

.modal-body {
  padding: 1.5rem;
}

/* Empty state styling */
.fas.fa-search-dollar {
  color: #e2e8f0;
}

/* Summary card styling */
.card.bg-light {
  background-color: var(--gray-50);
  border: 1px solid var(--gray-200);
}

.card.bg-light .text-muted {
  color: var(--gray-600) !important;
}

.card.bg-light h2 {
  font-size: 1.75rem;
  font-weight: 600;
  color: var(--gray-800);
}

/* Margin highlighting */
.bg-success.text-white {
  background-color: var(--success-color) !important;
}

.bg-danger.text-white {
  background-color: var(--danger-color) !important;
}

.bg-warning {
  background-color: #fff7e6 !important;
  color: #714b00;
}

/* Print styles */
@media print {
  .btn, .form-group, .col-md-3, .col-md-2 {
    display: none !important;
  }

  .card {
    box-shadow: none;
    border: none;
  }

  .card-header {
    background-color: #fff !important;
    color: #000 !important;
  }

  .card-body {
    padding: 0;
  }

  table {
    width: 100% !important;
  }

  .modal {
    display: none !important;
  }

  .bg-light-blue, .bg-light-green {
    background-color: transparent !important;
  }

  .bg-warning, .bg-success, .bg-danger {
    background-color: transparent !important;
    color: #000 !important;
  }

  .badge {
    border: 1px solid #000;
    color: #000 !important;
    background-color: transparent !important;
  }

  /* Force showing content */
  @page {
    size: A4 landscape;
    margin: 1cm;
  }

  body {
    min-width: 992px !important;
  }

  .container {
    min-width: 992px !important;
  }

  .row {
    display: block !important;
  }
}
</style>
