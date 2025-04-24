<!-- src/views/accounting/BankAccountTransactions.vue -->
<template>
    <div class="bank-account-transactions">
      <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
          <h1>Bank Transactions</h1>
          <div class="action-buttons">
            <button @click="exportTransactions" class="btn btn-info mr-2">
              <i class="fas fa-file-export mr-1"></i> Export
            </button>
            <router-link :to="`/accounting/bank-accounts/${accountId}`" class="btn btn-secondary">
              <i class="fas fa-arrow-left mr-1"></i> Back to Account
            </router-link>
          </div>
        </div>
      </div>

      <!-- Account Summary Card -->
      <div v-if="account" class="card mb-4">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <div class="account-info">
              <h3 class="mb-0">{{ account.bank_name }}</h3>
              <div class="account-details">
                {{ account.account_name }} | {{ account.account_number }}
              </div>
            </div>
            <div class="account-balance">
              <div class="balance-label">Current Balance</div>
              <div class="balance-amount">{{ formatCurrency(account.current_balance) }}</div>
            </div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Transaction History</h3>
        </div>
        <div class="card-body">
          <!-- Search and filters -->
          <SearchFilter
            v-model:value="searchTerm"
            placeholder="Search transactions..."
            @search="loadTransactions"
            @clear="clearSearch"
          >
            <template #filters>
              <div class="filter-group">
                <label>Date Range</label>
                <div class="date-range-filter">
                  <input
                    type="date"
                    v-model="filters.startDate"
                    class="form-control"
                    @change="loadTransactions"
                  />
                  <span class="date-range-separator">to</span>
                  <input
                    type="date"
                    v-model="filters.endDate"
                    class="form-control"
                    @change="loadTransactions"
                  />
                </div>
              </div>
              <div class="filter-group">
                <label>Transaction Type</label>
                <select v-model="filters.type" class="form-control" @change="loadTransactions">
                  <option value="">All Types</option>
                  <option value="deposit">Deposits</option>
                  <option value="withdrawal">Withdrawals</option>
                  <option value="transfer">Transfers</option>
                  <option value="interest">Interest</option>
                  <option value="fee">Fees & Charges</option>
                </select>
              </div>
              <div class="filter-group">
                <label>Amount Range</label>
                <div class="amount-range-filter">
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text">Rp</span>
                    </div>
                    <input
                      type="number"
                      v-model="filters.minAmount"
                      class="form-control"
                      placeholder="Min"
                      min="0"
                      @change="loadTransactions"
                    />
                  </div>
                  <span class="amount-range-separator">to</span>
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text">Rp</span>
                    </div>
                    <input
                      type="number"
                      v-model="filters.maxAmount"
                      class="form-control"
                      placeholder="Max"
                      min="0"
                      @change="loadTransactions"
                    />
                  </div>
                </div>
              </div>
            </template>
            <template #actions>
              <button class="btn btn-outline-secondary" @click="resetFilters">
                <i class="fas fa-redo mr-1"></i> Reset
              </button>
            </template>
          </SearchFilter>

          <!-- Loading state -->
          <div v-if="isLoading" class="text-center py-5">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p class="mt-2">Loading transactions...</p>
          </div>

          <!-- Error state -->
          <div v-else-if="error" class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            {{ error }}
          </div>

          <!-- Empty state -->
          <div v-else-if="transactions.length === 0" class="text-center py-5">
            <i class="fas fa-exchange-alt fa-3x text-muted mb-3"></i>
            <h4>No Transactions Found</h4>
            <p class="text-muted">No transactions match your search criteria or there are no transactions for this account.</p>
          </div>

          <!-- Transactions table -->
          <div v-else>
            <!-- Summary Cards -->
            <div class="transaction-summary mb-4">
              <div class="summary-card deposits">
                <div class="summary-icon">
                  <i class="fas fa-arrow-down"></i>
                </div>
                <div class="summary-content">
                  <div class="summary-label">Total Deposits</div>
                  <div class="summary-value">{{ formatCurrency(summaryData.totalDeposits) }}</div>
                </div>
              </div>
              <div class="summary-card withdrawals">
                <div class="summary-icon">
                  <i class="fas fa-arrow-up"></i>
                </div>
                <div class="summary-content">
                  <div class="summary-label">Total Withdrawals</div>
                  <div class="summary-value">{{ formatCurrency(summaryData.totalWithdrawals) }}</div>
                </div>
              </div>
              <div class="summary-card count">
                <div class="summary-icon">
                  <i class="fas fa-list"></i>
                </div>
                <div class="summary-content">
                  <div class="summary-label">Transaction Count</div>
                  <div class="summary-value">{{ transactions.length }} items</div>
                </div>
              </div>
            </div>

            <!-- Transactions table -->
            <div class="table-responsive">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Date</th>
                    <th>Reference</th>
                    <th>Description</th>
                    <th>Category</th>
                    <th class="text-right">Deposits</th>
                    <th class="text-right">Withdrawals</th>
                    <th class="text-right">Balance</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(transaction, index) in transactions" :key="index" :class="getTransactionRowClass(transaction)">
                    <td>{{ formatDate(transaction.date) }}</td>
                    <td>{{ transaction.reference }}</td>
                    <td>{{ transaction.description }}</td>
                    <td>
                      <span :class="getCategoryClass(transaction.category)">
                        {{ transaction.category }}
                      </span>
                    </td>
                    <td class="text-right">{{ transaction.amount > 0 ? formatCurrency(transaction.amount) : '-' }}</td>
                    <td class="text-right">{{ transaction.amount < 0 ? formatCurrency(Math.abs(transaction.amount)) : '-' }}</td>
                    <td class="text-right">{{ formatCurrency(transaction.balance) }}</td>
                    <td>
                      <span :class="getStatusClass(transaction.status)">
                        {{ transaction.status }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Pagination -->
            <div v-if="totalTransactions > perPage" class="mt-4">
              <PaginationComponent
                :current-page="currentPage"
                :total-pages="totalPages"
                :from="paginationFrom"
                :to="paginationTo"
                :total="totalTransactions"
                @page-changed="onPageChange"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  </template>

  <script>
  import { ref, computed, onMounted, reactive, watch } from 'vue';
  import { useRoute } from 'vue-router';
  import axios from 'axios';

  export default {
    name: 'BankAccountTransactions',
    setup() {
      const route = useRoute();
    //   const router = useRouter();
      const accountId = computed(() => route.params.id);

      // State variables
      const account = ref(null);
      const transactions = ref([]);
      const isLoading = ref(true);
      const error = ref(null);
      const searchTerm = ref('');
      const filters = reactive({
        startDate: '',
        endDate: '',
        type: '',
        minAmount: '',
        maxAmount: ''
      });
      const currentPage = ref(1);
      const perPage = ref(20);
      const totalTransactions = ref(0);

      // Initialize date range filter to last 30 days
      const initDateRangeFilter = () => {
        const today = new Date();
        const thirtyDaysAgo = new Date();
        thirtyDaysAgo.setDate(today.getDate() - 30);

        filters.endDate = today.toISOString().split('T')[0];
        filters.startDate = thirtyDaysAgo.toISOString().split('T')[0];
      };

      // On initial load, check if export parameter is present
      onMounted(() => {
        if (route.query.export === 'true') {
          exportTransactions();
        }

        initDateRangeFilter();
        loadAccountDetails();
        loadTransactions();
      });

      // Watch for changes to the accountId
      watch(() => accountId.value, () => {
        loadAccountDetails();
        loadTransactions();
      });

      // Pagination computed properties
      const totalPages = computed(() => Math.ceil(totalTransactions.value / perPage.value));

      const paginationFrom = computed(() => {
        if (totalTransactions.value === 0) return 0;
        return (currentPage.value - 1) * perPage.value + 1;
      });

      const paginationTo = computed(() => {
        return Math.min(currentPage.value * perPage.value, totalTransactions.value);
      });

      // Computed summary data
      const summaryData = computed(() => {
        const totalDeposits = transactions.value
          .filter(t => t.amount > 0)
          .reduce((sum, t) => sum + t.amount, 0);

        const totalWithdrawals = Math.abs(transactions.value
          .filter(t => t.amount < 0)
          .reduce((sum, t) => sum + t.amount, 0));

        return {
          totalDeposits,
          totalWithdrawals
        };
      });

      // Load bank account details
      const loadAccountDetails = async () => {
        try {
          const response = await axios.get(`/api/accounting/bank-accounts/${accountId.value}`);
          if (response.data && response.data.data) {
            account.value = response.data.data;
          }
        } catch (err) {
          console.error('Error loading account details:', err);
          // Don't set main error, this is secondary information
        }
      };

      // Load transactions
      const loadTransactions = async () => {
        isLoading.value = true;
        error.value = null;

        try {
          const params = {
            page: currentPage.value,
            per_page: perPage.value
          };

          if (searchTerm.value) {
            params.search = searchTerm.value;
          }

          if (filters.startDate) {
            params.start_date = filters.startDate;
          }

          if (filters.endDate) {
            params.end_date = filters.endDate;
          }

          if (filters.type) {
            params.type = filters.type;
          }

          if (filters.minAmount) {
            params.min_amount = filters.minAmount;
          }

          if (filters.maxAmount) {
            params.max_amount = filters.maxAmount;
          }

          const response = await axios.get(`/api/accounting/bank-accounts/${accountId.value}/transactions`, { params });

          if (response.data) {
            if (response.data.data) {
              transactions.value = response.data.data;
            }

            if (response.data.meta) {
              totalTransactions.value = response.data.meta.total;
            } else {
              totalTransactions.value = transactions.value.length;
            }
          } else {
            throw new Error('Invalid response format');
          }
        } catch (err) {
          console.error('Error loading transactions:', err);
          error.value = 'Failed to load transactions. Please try again later.';
          transactions.value = [];
        } finally {
          isLoading.value = false;
        }
      };

      // Clear search
      const clearSearch = () => {
        searchTerm.value = '';
        loadTransactions();
      };

      // Reset filters
      const resetFilters = () => {
        initDateRangeFilter();
        filters.type = '';
        filters.minAmount = '';
        filters.maxAmount = '';
        searchTerm.value = '';
        currentPage.value = 1;
        loadTransactions();
      };

      // Handle page change
      const onPageChange = (page) => {
        currentPage.value = page;
        loadTransactions();
      };

      // Format date
      const formatDate = (dateString) => {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', {
          year: 'numeric',
          month: 'long',
          day: 'numeric'
        });
      };

      // Format currency
      const formatCurrency = (amount) => {
        return new Intl.NumberFormat('id-ID', {
          style: 'currency',
          currency: 'IDR',
          minimumFractionDigits: 0,
          maximumFractionDigits: 0
        }).format(amount || 0);
      };

      // Get class for transaction row
      const getTransactionRowClass = (transaction) => {
        if (transaction.status === 'Pending') return 'pending-transaction';
        if (transaction.status === 'Reconciled') return 'reconciled-transaction';
        return '';
      };

      // Get class for transaction category
      const getCategoryClass = (category) => {
        const classes = {
          'Deposit': 'category-deposit',
          'Withdrawal': 'category-withdrawal',
          'Transfer': 'category-transfer',
          'Interest': 'category-interest',
          'Fee': 'category-fee'
        };

        return classes[category] || 'category-other';
      };

      // Get class for transaction status
      const getStatusClass = (status) => {
        const classes = {
          'Cleared': 'status-cleared',
          'Pending': 'status-pending',
          'Reconciled': 'status-reconciled',
          'Bounced': 'status-bounced'
        };

        return classes[status] || '';
      };

      // Export transactions
      const exportTransactions = async () => {
        try {
          // Prepare export parameters
          const params = {
            export: true,
            format: 'csv'
          };

          // Add filters if set
          if (filters.startDate) params.start_date = filters.startDate;
          if (filters.endDate) params.end_date = filters.endDate;
          if (filters.type) params.type = filters.type;
          if (filters.minAmount) params.min_amount = filters.minAmount;
          if (filters.maxAmount) params.max_amount = filters.maxAmount;
          if (searchTerm.value) params.search = searchTerm.value;

          // Make export request
          const response = await axios.get(`/api/accounting/bank-accounts/${accountId.value}/transactions/export`, {
            params,
            responseType: 'blob'
          });

          // Create download link
          const url = window.URL.createObjectURL(new Blob([response.data]));
          const link = document.createElement('a');
          link.href = url;
          link.setAttribute('download', `bank-transactions-${accountId.value}.csv`);
          document.body.appendChild(link);
          link.click();

          // Cleanup
          window.URL.revokeObjectURL(url);
          link.remove();

          // Show success message
          if (window.$toast) {
            window.$toast.success('Transactions exported successfully');
          }
        } catch (err) {
          console.error('Error exporting transactions:', err);
          if (window.$toast) {
            window.$toast.error('Failed to export transactions');
          }
        }
      };

      return {
        accountId,
        account,
        transactions,
        summaryData,
        isLoading,
        error,
        searchTerm,
        filters,
        currentPage,
        totalTransactions,
        totalPages,
        paginationFrom,
        paginationTo,
        loadTransactions,
        clearSearch,
        resetFilters,
        onPageChange,
        formatDate,
        formatCurrency,
        getTransactionRowClass,
        getCategoryClass,
        getStatusClass,
        exportTransactions
      };
    }
  };
  </script>

  <style scoped>
  .bank-account-transactions {
    padding: 1rem 0;
  }

  /* Account summary styles */
  .account-info {
    flex: 1;
  }

  .account-details {
    font-size: 0.875rem;
    color: var(--gray-500);
    margin-top: 0.25rem;
  }

  .account-balance {
    text-align: right;
  }

  .balance-label {
    font-size: 0.75rem;
    text-transform: uppercase;
    color: var(--gray-500);
    margin-bottom: 0.25rem;
  }

  .balance-amount {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--primary-color);
  }

  /* Filter styles */
  .date-range-filter,
  .amount-range-filter {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .date-range-separator,
  .amount-range-separator {
    font-size: 0.875rem;
    color: var(--gray-500);
  }

  /* Transaction summary styles */
  .transaction-summary {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
  }

  .summary-card {
    display: flex;
    align-items: center;
    padding: 1rem;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  }

  .summary-card.deposits {
    background-color: var(--success-bg);
    border: 1px solid rgba(5, 150, 105, 0.2);
  }

  .summary-card.withdrawals {
    background-color: var(--danger-bg);
    border: 1px solid rgba(220, 38, 38, 0.2);
  }

  .summary-card.count {
    background-color: var(--primary-bg);
    border: 1px solid rgba(37, 99, 235, 0.2);
  }

  .summary-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    margin-right: 1rem;
    font-size: 1.25rem;
  }

  .summary-card.deposits .summary-icon {
    background-color: var(--success-color);
    color: white;
  }

  .summary-card.withdrawals .summary-icon {
    background-color: var(--danger-color);
    color: white;
  }

  .summary-card.count .summary-icon {
    background-color: var(--primary-color);
    color: white;
  }

  .summary-content {
    flex: 1;
  }

  .summary-label {
    font-size: 0.75rem;
    text-transform: uppercase;
    margin-bottom: 0.25rem;
  }

  .summary-card.deposits .summary-label {
    color: var(--success-color);
  }

  .summary-card.withdrawals .summary-label {
    color: var(--danger-color);
  }

  .summary-card.count .summary-label {
    color: var(--primary-color);
  }

  .summary-value {
    font-size: 1.25rem;
    font-weight: 600;
  }

  /* Table styles */
  .data-table th,
  .data-table td {
    vertical-align: middle;
    padding: 0.75rem 1rem;
  }

  /* Transaction row styles */
  .pending-transaction {
    background-color: rgba(245, 158, 11, 0.05);
  }

  .reconciled-transaction {
    background-color: rgba(5, 150, 105, 0.05);
  }

  /* Category badge styles */
  .category-deposit,
  .category-withdrawal,
  .category-transfer,
  .category-interest,
  .category-fee,
  .category-other {
    display: inline-block;
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
  }

  .category-deposit {
    background-color: var(--success-bg);
    color: var(--success-color);
  }

  .category-withdrawal {
    background-color: var(--danger-bg);
    color: var(--danger-color);
  }

  .category-transfer {
    background-color: var(--primary-bg);
    color: var(--primary-color);
  }

  .category-interest {
    background-color: var(--success-bg);
    color: var(--success-color);
  }

  .category-fee {
    background-color: var(--danger-bg);
    color: var(--danger-color);
  }

  .category-other {
    background-color: var(--gray-100);
    color: var(--gray-700);
  }

  /* Status styles */
  .status-cleared,
  .status-pending,
  .status-reconciled,
  .status-bounced {
    display: inline-block;
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    text-transform: uppercase;
  }

  .status-cleared {
    background-color: var(--gray-100);
    color: var(--gray-700);
  }

  .status-pending {
    background-color: var(--warning-bg);
    color: var(--warning-color);
  }

  .status-reconciled {
    background-color: var(--success-bg);
    color: var(--success-color);
  }

  .status-bounced {
    background-color: var(--danger-bg);
    color: var(--danger-color);
  }

  /* Responsive adjustments */
  @media (max-width: 768px) {
    .transaction-summary {
      grid-template-columns: 1fr;
      gap: 0.75rem;
    }

    .date-range-filter,
    .amount-range-filter {
      flex-direction: column;
      align-items: stretch;
    }

    .date-range-separator,
    .amount-range-separator {
      display: none;
    }
  }
  </style>
