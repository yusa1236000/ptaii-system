<!-- src/views/inventory/StockTransactionsList.vue -->
<template>
    <div class="stock-transactions-list">
      <div class="page-header">
        <h1>Stock Transactions</h1>
        <div class="page-actions">
          <router-link to="/stock-transactions/create" class="btn btn-primary">
            <i class="fas fa-plus"></i> New Transaction
          </router-link>
          <router-link to="/stock-transactions/transfer" class="btn btn-secondary">
            <i class="fas fa-exchange-alt"></i> Stock Transfer
          </router-link>
        </div>
      </div>
  
      <!-- Filters Panel -->
      <div class="filters-panel card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h3 class="card-title">Filters</h3>
          <button class="btn-text" @click="toggleFiltersPanel">
            {{ showFilters ? 'Hide Filters' : 'Show Filters' }}
            <i :class="showFilters ? 'fas fa-chevron-up' : 'fas fa-chevron-down'" class="ml-1"></i>
          </button>
        </div>
        <div v-if="showFilters" class="card-body">
          <form @submit.prevent="applyFilters">
            <div class="filters-grid">
              <!-- Item Filter -->
              <div class="form-group">
                <label for="item-filter">Item</label>
                <div class="select-with-status">
                  <select 
                    id="item-filter" 
                    v-model="filters.item_id"
                    class="form-control"
                  >
                    <option value="">All Items</option>
                    <option v-for="item in items" :key="item.item_id" :value="item.item_id">
                      {{ item.name }} ({{ item.item_code }})
                    </option>
                  </select>
                  <div v-if="itemsLoading" class="status-indicator">
                    <i class="fas fa-spinner fa-spin"></i>
                  </div>
                </div>
              </div>
  
              <!-- Warehouse Filter -->
              <div class="form-group">
                <label for="warehouse-filter">Warehouse</label>
                <div class="select-with-status">
                  <select 
                    id="warehouse-filter" 
                    v-model="filters.warehouse_id"
                    class="form-control"
                  >
                    <option value="">All Warehouses</option>
                    <option v-for="warehouse in warehouses" :key="warehouse.warehouse_id" :value="warehouse.warehouse_id">
                      {{ warehouse.name }}
                    </option>
                  </select>
                  <div v-if="warehousesLoading" class="status-indicator">
                    <i class="fas fa-spinner fa-spin"></i>
                  </div>
                </div>
              </div>
  
              <!-- Transaction Type Filter -->
              <div class="form-group">
                <label for="type-filter">Transaction Type</label>
                <select id="type-filter" v-model="filters.transaction_type" class="form-control">
                  <option value="">All Types</option>
                  <option value="receive">Receive</option>
                  <option value="issue">Issue</option>
                  <option value="transfer">Transfer</option>
                  <option value="adjustment">Adjustment</option>
                  <option value="return">Return</option>
                </select>
              </div>
  
              <!-- Date Range Filter -->
              <div class="form-group">
                <label for="start-date">From Date</label>
                <input
                  type="date"
                  id="start-date"
                  v-model="filters.start_date"
                  class="form-control"
                />
              </div>
              
              <div class="form-group">
                <label for="end-date">To Date</label>
                <input
                  type="date"
                  id="end-date"
                  v-model="filters.end_date"
                  class="form-control"
                />
              </div>
  
              <!-- Reference Filter -->
              <div class="form-group">
                <label for="reference-filter">Reference</label>
                <input
                  type="text"
                  id="reference-filter"
                  v-model="filters.reference"
                  placeholder="Document or number"
                  class="form-control"
                />
              </div>
            </div>
  
            <div class="filters-actions">
              <button type="button" class="btn btn-secondary" @click="resetFilters">
                <i class="fas fa-undo"></i> Reset
              </button>
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Apply Filters
              </button>
            </div>
          </form>
        </div>
      </div>
  
      <!-- Quick Search -->
      <div class="quick-search mb-3">
        <div class="input-group">
          <span class="input-group-text">
            <i class="fas fa-search"></i>
          </span>
          <input
            type="text"
            v-model="quickSearch"
            placeholder="Quick search: transaction ID, reference, item code..."
            class="form-control"
            @input="handleQuickSearch"
          />
          <button 
            v-if="quickSearch" 
            class="btn btn-outline-secondary" 
            type="button"
            @click="clearQuickSearch"
          >
            <i class="fas fa-times"></i>
          </button>
        </div>
      </div>
  
      <!-- Transactions Table -->
      <div class="card">
        <div class="card-body p-0">
          <!-- Loading State -->
          <div v-if="loading" class="text-center py-5">
            <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
            <p>Loading transactions...</p>
          </div>
  
          <!-- Error State -->
          <div v-else-if="error" class="alert alert-danger m-3">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            {{ error }}
            <button class="btn btn-sm btn-outline-danger ml-2" @click="fetchTransactions">
              Try Again
            </button>
          </div>
  
          <!-- Empty State -->
          <div v-else-if="transactions.length === 0" class="text-center py-5">
            <i class="fas fa-box-open fa-3x mb-3" style="color: var(--gray-400)"></i>
            <h3 class="h5">No Transactions Found</h3>
            <p class="text-muted">
              {{ isFiltered 
                ? 'No transactions match your filters. Try adjusting your search criteria.' 
                : 'There are no stock transactions recorded yet.' 
              }}
            </p>
            <div v-if="isFiltered" class="mt-3">
              <button class="btn btn-outline-secondary" @click="resetFilters">
                Clear Filters
              </button>
            </div>
            <div v-else class="mt-3">
              <router-link to="/stock-transactions/create" class="btn btn-primary">
                Create First Transaction
              </router-link>
            </div>
          </div>
  
          <!-- Transactions Table -->
          <div v-else class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Date</th>
                  <th>Type</th>
                  <th>Item</th>
                  <th>Warehouse</th>
                  <th class="text-right">Quantity</th>
                  <th>Reference</th>
                  <th class="text-right">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="transaction in transactions" :key="transaction.transaction_id">
                  <td>
                    <router-link :to="`/stock-transactions/${transaction.transaction_id}`" class="font-weight-bold">
                      #{{ transaction.transaction_id }}
                    </router-link>
                  </td>
                  <td>{{ formatDate(transaction.transaction_date) }}</td>
                  <td>
                    <span class="badge" :class="getTransactionTypeClass(transaction.transaction_type)">
                      {{ formatTransactionType(transaction.transaction_type) }}
                    </span>
                  </td>
                  <td>
                    <div v-if="transaction.item">
                      <div class="font-weight-bold">{{ transaction.item.item_code }}</div>
                      <div class="small text-muted">{{ transaction.item.name }}</div>
                    </div>
                    <div v-else class="text-muted">--</div>
                  </td>
                  <td>
                    <div v-if="transaction.warehouse">
                      {{ transaction.warehouse.name }}
                      <div v-if="transaction.location" class="small text-muted">
                        Location: {{ transaction.location.code }}
                      </div>
                    </div>
                    <div v-else class="text-muted">--</div>
                  </td>
                  <td class="text-right">
                    <span :class="getQuantityClass(transaction.quantity)">
                      {{ transaction.quantity }}
                      <span v-if="transaction.item && transaction.item.unitOfMeasure" class="text-muted">
                        {{ transaction.item.unitOfMeasure.symbol }}
                      </span>
                    </span>
                  </td>
                  <td>
                    <div v-if="transaction.reference_document" class="font-weight-bold">
                      {{ transaction.reference_document }}
                    </div>
                    <div v-if="transaction.reference_number" class="small text-muted">
                      {{ transaction.reference_number }}
                    </div>
                    <div v-if="!transaction.reference_document && !transaction.reference_number" class="text-muted">
                      --
                    </div>
                  </td>
                  <td class="actions-cell">
                    <div class="actions-dropdown">
                      <button class="btn-icon dropdown-toggle" @click="toggleActionMenu(transaction.transaction_id)">
                        <i class="fas fa-ellipsis-v"></i>
                      </button>
                      <div 
                        v-if="activeActionMenu === transaction.transaction_id" 
                        class="dropdown-menu show"
                        @click.stop
                      >
                        <router-link :to="`/stock-transactions/${transaction.transaction_id}`" class="dropdown-item">
                          <i class="fas fa-eye mr-2"></i> View Details
                        </router-link>
                        <router-link 
                          v-if="transaction.item"
                          :to="`/stock-transactions/items/${transaction.item.item_id}/movement`" 
                          class="dropdown-item"
                        >
                          <i class="fas fa-chart-line mr-2"></i> Item Movement
                        </router-link>
                        <button 
                          class="dropdown-item text-danger" 
                          @click="showReverseTransactionModal(transaction)"
                          v-if="canReverseTransaction(transaction)"
                        >
                          <i class="fas fa-undo mr-2"></i> Reverse Transaction
                        </button>
                      </div>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
  
          <!-- Pagination -->
          <div v-if="totalPages > 1" class="pagination-container">
            <nav aria-label="Transaction pagination">
              <ul class="pagination">
                <li class="page-item" :class="{ 'disabled': currentPage === 1 }">
                  <button 
                    class="page-link" 
                    @click="changePage(currentPage - 1)" 
                    :disabled="currentPage === 1"
                  >
                    <i class="fas fa-chevron-left"></i>
                  </button>
                </li>
                <li 
                  v-for="page in displayedPages" 
                  :key="page" 
                  class="page-item" 
                  :class="{ 'active': currentPage === page }"
                >
                  <button 
                    class="page-link" 
                    @click="changePage(page)"
                  >
                    {{ page }}
                  </button>
                </li>
                <li class="page-item" :class="{ 'disabled': currentPage === totalPages }">
                  <button 
                    class="page-link" 
                    @click="changePage(currentPage + 1)" 
                    :disabled="currentPage === totalPages"
                  >
                    <i class="fas fa-chevron-right"></i>
                  </button>
                </li>
              </ul>
            </nav>
            <div class="per-page-select">
              <label for="per-page">Items per page:</label>
              <select id="per-page" v-model="perPage" @change="perPageChanged">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
              </select>
            </div>
          </div>
        </div>
      </div>
  
      <!-- Reverse Transaction Modal -->
      <div v-if="showReversalModal" class="modal-backdrop">
        <div class="modal-content">
          <div class="modal-header">
            <h3>Reverse Transaction</h3>
            <button class="btn-close" @click="closeReversalModal">
              <i class="fas fa-times"></i>
            </button>
          </div>
          <div class="modal-body">
            <div class="alert alert-warning mb-4">
              <i class="fas fa-exclamation-triangle mr-2"></i>
              You are about to create a reverse transaction for transaction #{{ transactionToReverse.transaction_id }}.
              This will create a new transaction with an opposite quantity.
            </div>
  
            <div class="transaction-details mb-4">
              <p><strong>Transaction Type:</strong> {{ formatTransactionType(transactionToReverse.transaction_type) }}</p>
              <p><strong>Item:</strong> {{ transactionToReverse.item?.item_code }} - {{ transactionToReverse.item?.name }}</p>
              <p><strong>Warehouse:</strong> {{ transactionToReverse.warehouse?.name }}</p>
              <p><strong>Quantity:</strong> <span :class="getQuantityClass(transactionToReverse.quantity)">{{ transactionToReverse.quantity }}</span></p>
              <p><strong>New Quantity:</strong> <span :class="getQuantityClass(-transactionToReverse.quantity)">{{ -transactionToReverse.quantity }}</span></p>
            </div>
  
            <div class="form-group">
              <label for="reversal-reason">Reason for Reversal*</label>
              <textarea 
                id="reversal-reason" 
                v-model="reversalReason" 
                rows="3" 
                class="form-control"
                :class="{ 'is-invalid': reversalReasonError }"
                required
              ></textarea>
              <div v-if="reversalReasonError" class="invalid-feedback">
                {{ reversalReasonError }}
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="closeReversalModal">Cancel</button>
            <button 
              type="button" 
              class="btn btn-danger" 
              @click="reverseTransaction"
              :disabled="isReversing"
            >
              <i v-if="isReversing" class="fas fa-spinner fa-spin mr-2"></i>
              Confirm Reversal
            </button>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script>
  import { ref, reactive, computed, onMounted, onBeforeUnmount } from 'vue';
  import axios from 'axios';
  //import { useRouter } from 'vue-router';
  
  export default {
    name: 'StockTransactionsList',
    setup() {
      //const router = useRouter();
      
      // Data
      const transactions = ref([]);
      const loading = ref(true);
      const error = ref(null);
      const showFilters = ref(false);
      const quickSearch = ref('');
      const quickSearchTimeout = ref(null);
      const activeActionMenu = ref(null);
      
      // Pagination
      const currentPage = ref(1);
      const totalPages = ref(1);
      const totalItems = ref(0);
      const perPage = ref(25);
      
      // Filter data
      const items = ref([]);
      const warehouses = ref([]);
      const itemsLoading = ref(false);
      const warehousesLoading = ref(false);
      
      // Filters
      const filters = reactive({
        item_id: '',
        warehouse_id: '',
        transaction_type: '',
        start_date: '',
        end_date: '',
        reference: '',
        search: ''
      });
      
      // Computed
      const isFiltered = computed(() => {
        return filters.item_id !== '' || 
               filters.warehouse_id !== '' || 
               filters.transaction_type !== '' || 
               filters.start_date !== '' || 
               filters.end_date !== '' || 
               filters.reference !== '' ||
               filters.search !== '';
      });
      
      const displayedPages = computed(() => {
        if (totalPages.value <= 7) {
          return Array.from({ length: totalPages.value }, (_, i) => i + 1);
        }
        
        if (currentPage.value <= 4) {
          return [1, 2, 3, 4, 5, '...', totalPages.value];
        }
        
        if (currentPage.value >= totalPages.value - 3) {
          return [1, '...', totalPages.value - 4, totalPages.value - 3, totalPages.value - 2, totalPages.value - 1, totalPages.value];
        }
        
        return [1, '...', currentPage.value - 1, currentPage.value, currentPage.value + 1, '...', totalPages.value];
      });
      
      // Reversal modal
      const showReversalModal = ref(false);
      const transactionToReverse = ref({});
      const reversalReason = ref('');
      const reversalReasonError = ref(null);
      const isReversing = ref(false);
      
      // Methods
      const fetchTransactions = async () => {
        loading.value = true;
        error.value = null;
        
        try {
          // Build query parameters
          const params = {
            page: currentPage.value,
            per_page: perPage.value
          };
          
          // Add filters if set
          if (filters.item_id) params.item_id = filters.item_id;
          if (filters.warehouse_id) params.warehouse_id = filters.warehouse_id;
          if (filters.transaction_type) params.transaction_type = filters.transaction_type;
          if (filters.start_date) params.start_date = filters.start_date;
          if (filters.end_date) params.end_date = filters.end_date;
          if (filters.reference) params.reference = filters.reference;
          if (filters.search) params.search = filters.search;
          
          const response = await axios.get('/transactions', { params });
          
          transactions.value = response.data.data.data;
          totalPages.value = Math.ceil(response.data.data.total / response.data.data.per_page);
          totalItems.value = response.data.data.total;
          currentPage.value = response.data.data.current_page;
        } catch (err) {
          console.error('Error fetching transactions:', err);
          error.value = 'Failed to load transactions. Please try again.';
        } finally {
          loading.value = false;
        }
      };
      
      const fetchItems = async () => {
        itemsLoading.value = true;
        try {
          const response = await axios.get('/items');
          items.value = response.data.data;
        } catch (err) {
          console.error('Error fetching items:', err);
        } finally {
          itemsLoading.value = false;
        }
      };
      
      const fetchWarehouses = async () => {
        warehousesLoading.value = true;
        try {
          const response = await axios.get('/warehouses');
          warehouses.value = response.data.data;
        } catch (err) {
          console.error('Error fetching warehouses:', err);
        } finally {
          warehousesLoading.value = false;
        }
      };
      
      const applyFilters = () => {
        currentPage.value = 1;
        fetchTransactions();
      };
      
      const resetFilters = () => {
        Object.keys(filters).forEach(key => {
          filters[key] = '';
        });
        quickSearch.value = '';
        currentPage.value = 1;
        fetchTransactions();
      };
      
      const handleQuickSearch = () => {
        if (quickSearchTimeout.value) {
          clearTimeout(quickSearchTimeout.value);
        }
        
        quickSearchTimeout.value = setTimeout(() => {
          filters.search = quickSearch.value;
          currentPage.value = 1;
          fetchTransactions();
        }, 500);
      };
      
      const clearQuickSearch = () => {
        quickSearch.value = '';
        filters.search = '';
        currentPage.value = 1;
        fetchTransactions();
      };
      
      const changePage = (page) => {
        if (page >= 1 && page <= totalPages.value && page !== currentPage.value) {
          currentPage.value = page;
          fetchTransactions();
        }
      };
      
      const perPageChanged = () => {
        currentPage.value = 1;
        fetchTransactions();
      };
      
      const toggleActionMenu = (transactionId) => {
        if (activeActionMenu.value === transactionId) {
          activeActionMenu.value = null;
        } else {
          activeActionMenu.value = transactionId;
        }
      };
      
      const closeActionMenus = (event) => {
        if (activeActionMenu.value !== null && !event.target.closest('.actions-dropdown')) {
          activeActionMenu.value = null;
        }
      };
      
      const toggleFiltersPanel = () => {
        showFilters.value = !showFilters.value;
      };
      
      const formatDate = (dateString) => {
        if (!dateString) return '--';
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
          year: 'numeric',
          month: 'short',
          day: 'numeric'
        });
      };
      
      const formatTransactionType = (type) => {
        if (!type) return '--';
        
        const types = {
          'receive': 'Receive',
          'issue': 'Issue',
          'transfer': 'Transfer',
          'adjustment': 'Adjustment',
          'return': 'Return'
        };
        
        return types[type.toLowerCase()] || type;
      };
      
      const getTransactionTypeClass = (type) => {
        if (!type) return '';
        
        const typeClasses = {
          'receive': 'badge-success',
          'issue': 'badge-danger',
          'transfer': 'badge-warning',
          'adjustment': 'badge-secondary',
          'return': 'badge-info'
        };
        
        return typeClasses[type.toLowerCase()] || 'badge-secondary';
      };
      
      const getQuantityClass = (quantity) => {
        if (!quantity) return '';
        
        if (quantity > 0) {
          return 'text-success';
        } else if (quantity < 0) {
          return 'text-danger';
        }
        
        return '';
      };
      
      const canReverseTransaction = () => {
        // Here you can implement business rules to determine
        // if a transaction can be reversed
        return true;
      };
      
      const showReverseTransactionModal = (transaction) => {
        transactionToReverse.value = transaction;
        reversalReason.value = '';
        reversalReasonError.value = null;
        showReversalModal.value = true;
        activeActionMenu.value = null;
      };
      
      const closeReversalModal = () => {
        showReversalModal.value = false;
        transactionToReverse.value = {};
        reversalReason.value = '';
        reversalReasonError.value = null;
      };
      
      const reverseTransaction = async () => {
        if (!reversalReason.value.trim()) {
          reversalReasonError.value = 'Please provide a reason for reversal';
          return;
        }
        
        isReversing.value = true;
        
        try {
          // Create the reversal transaction
          await axios.post('/transactions', {
            item_id: transactionToReverse.value.item_id,
            warehouse_id: transactionToReverse.value.warehouse_id,
            location_id: transactionToReverse.value.location_id,
            transaction_type: 'adjustment',
            quantity: -transactionToReverse.value.quantity,
            transaction_date: new Date().toISOString().split('T')[0],
            reference_document: 'Reversal',
            reference_number: `REV-${transactionToReverse.value.transaction_id}`,
            batch_id: transactionToReverse.value.batch_id,
            notes: `Reversal for transaction #${transactionToReverse.value.transaction_id}. Reason: ${reversalReason.value}`
          });
          
          // Close modal
          closeReversalModal();
          
          // Show success and refresh
          alert('Transaction successfully reversed');
          fetchTransactions();
        } catch (err) {
          console.error('Error reversing transaction:', err);
          reversalReasonError.value = 'Failed to reverse transaction. Please try again.';
        } finally {
          isReversing.value = false;
        }
      };
      
      // Lifecycle hooks
      onMounted(() => {
        fetchTransactions();
        fetchItems();
        fetchWarehouses();
        document.addEventListener('click', closeActionMenus);
      });
      
      onBeforeUnmount(() => {
        document.removeEventListener('click', closeActionMenus);
        if (quickSearchTimeout.value) {
          clearTimeout(quickSearchTimeout.value);
        }
      });
      
      return {
        // Data
        transactions,
        loading,
        error,
        showFilters,
        quickSearch,
        items,
        warehouses,
        itemsLoading,
        warehousesLoading,
        filters,
        activeActionMenu,
        
        // Pagination
        currentPage,
        totalPages,
        totalItems,
        perPage,
        displayedPages,
        
        // Reversal
        showReversalModal,
        transactionToReverse,
        reversalReason,
        reversalReasonError,
        isReversing,
        
        // Computed
        isFiltered,
        
        // Methods
        fetchTransactions,
        applyFilters,
        resetFilters,
        handleQuickSearch,
        clearQuickSearch,
        changePage,
        perPageChanged,
        toggleActionMenu,
        toggleFiltersPanel,
        formatDate,
        formatTransactionType,
        getTransactionTypeClass,
        getQuantityClass,
        canReverseTransaction,
        showReverseTransactionModal,
        closeReversalModal,
        reverseTransaction
      };
    }
  }
  </script>
  
  <style scoped>
  .stock-transactions-list {
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
  
  .filters-panel {
    margin-bottom: 1.5rem;
  }
  
  .btn-text {
    background: none;
    border: none;
    color: var(--primary-color);
    cursor: pointer;
    padding: 0.25rem 0.5rem;
    display: inline-flex;
    align-items: center;
  }
  
  .btn-text:hover {
    text-decoration: underline;
  }
  
  .filters-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1rem;
  }
  
  .filters-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    margin-top: 1.5rem;
  }
  
  .quick-search {
    margin-bottom: 1.5rem;
  }
  
  .input-group {
    display: flex;
    align-items: center;
  }
  
  .input-group-text {
    display: flex;
    align-items: center;
    padding: 0.375rem 0.75rem;
    background-color: var(--gray-100);
    border: 1px solid var(--gray-300);
    border-right: none;
    border-radius: 0.375rem 0 0 0.375rem;
  }
  
  .pagination-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background-color: var(--gray-50);
    border-top: 1px solid var(--gray-200);
  }
  
  .pagination {
    display: flex;
    list-style-type: none;
    margin: 0;
    padding: 0;
  }
  
  .page-item {
    margin: 0 0.125rem;
  }
  
  .page-link {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 2rem;
    height: 2rem;
    padding: 0 0.5rem;
    border: 1px solid var(--gray-300);
    border-radius: 0.25rem;
    background-color: white;
    color: var(--gray-700);
    cursor: pointer;
  }
  
  .page-item.active .page-link {
    background-color: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
  }
  
  .page-item.disabled .page-link {
    color: var(--gray-400);
    cursor: not-allowed;
  }
  </style>