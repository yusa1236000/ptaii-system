<!-- src/views/accounting/BankAccountDetail.vue -->
<template>
    <div class="bank-account-detail">
      <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
          <h1>Bank Account Details</h1>
          <div class="action-buttons">
            <router-link :to="`/accounting/bank-accounts/${accountId}/transactions`" class="btn btn-info mr-2">
              <i class="fas fa-exchange-alt mr-1"></i> Transactions
            </router-link>
            <router-link :to="`/accounting/bank-accounts/${accountId}/edit`" class="btn btn-primary mr-2">
              <i class="fas fa-edit mr-1"></i> Edit
            </router-link>
            <router-link to="/accounting/bank-accounts" class="btn btn-secondary">
              <i class="fas fa-arrow-left mr-1"></i> Back
            </router-link>
          </div>
        </div>
      </div>

      <div class="row">
        <!-- Loading state -->
        <div v-if="isLoading" class="col-12 text-center py-5">
          <i class="fas fa-spinner fa-spin fa-2x"></i>
          <p class="mt-2">Loading account details...</p>
        </div>

        <!-- Error state -->
        <div v-else-if="error" class="col-12">
          <div class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            {{ error }}
          </div>
        </div>

        <template v-else-if="account">
          <!-- Account Details Card -->
          <div class="col-md-6 mb-4">
            <div class="card h-100">
              <div class="card-header">
                <h3 class="card-title">Account Information</h3>
              </div>
              <div class="card-body">
                <div class="account-summary mb-4">
                  <div class="bank-logo">
                    <i class="fas fa-university fa-3x"></i>
                  </div>
                  <div class="account-details">
                    <h4 class="bank-name">{{ account.bank_name }}</h4>
                    <div class="account-number">{{ formatAccountNumber(account.account_number) }}</div>
                    <div class="account-holder">{{ account.account_name }}</div>
                  </div>
                </div>

                <div class="detail-section">
                  <table class="table table-details">
                    <tbody>
                      <tr>
                        <th>Bank Name</th>
                        <td>{{ account.bank_name }}</td>
                      </tr>
                      <tr>
                        <th>Account Number</th>
                        <td>{{ account.account_number }}</td>
                      </tr>
                      <tr>
                        <th>Account Name</th>
                        <td>{{ account.account_name }}</td>
                      </tr>
                      <tr>
                        <th>Current Balance</th>
                        <td><strong>{{ formatCurrency(account.current_balance) }}</strong></td>
                      </tr>
                      <tr>
                        <th>GL Account</th>
                        <td>
                          <span v-if="account.chart_of_account">
                            {{ account.chart_of_account.account_code }} - {{ account.chart_of_account.name }}
                          </span>
                          <span v-else class="text-muted">Not assigned</span>
                        </td>
                      </tr>
                      <tr v-if="account.description">
                        <th>Description</th>
                        <td>{{ account.description }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>

          <!-- Account Stats Card -->
          <div class="col-md-6 mb-4">
            <div class="card h-100">
              <div class="card-header">
                <h3 class="card-title">Account Statistics</h3>
              </div>
              <div class="card-body">
                <div class="stats-grid">
                  <div class="stat-item">
                    <div class="stat-label">Last Reconciliation</div>
                    <div class="stat-value">
                      <span v-if="lastReconciliation">
                        {{ formatDate(lastReconciliation.statement_date) }}
                      </span>
                      <span v-else class="text-muted">Never</span>
                    </div>
                  </div>

                  <div class="stat-item">
                    <div class="stat-label">Statement Balance</div>
                    <div class="stat-value">
                      <span v-if="lastReconciliation">
                        {{ formatCurrency(lastReconciliation.statement_balance) }}
                      </span>
                      <span v-else class="text-muted">N/A</span>
                    </div>
                  </div>

                  <div class="stat-item">
                    <div class="stat-label">Book Balance</div>
                    <div class="stat-value">
                      <span v-if="lastReconciliation">
                        {{ formatCurrency(lastReconciliation.book_balance) }}
                      </span>
                      <span v-else class="text-muted">N/A</span>
                    </div>
                  </div>

                  <div class="stat-item">
                    <div class="stat-label">Reconciliation Status</div>
                    <div class="stat-value">
                      <span
                        v-if="lastReconciliation"
                        :class="{
                          'badge': true,
                          'badge-success': lastReconciliation.status === 'Finalized',
                          'badge-warning': lastReconciliation.status === 'In Progress',
                          'badge-secondary': lastReconciliation.status === 'Draft'
                        }"
                      >
                        {{ lastReconciliation.status }}
                      </span>
                      <span v-else class="text-muted">No reconciliations</span>
                    </div>
                  </div>
                </div>

                <div class="detail-section mt-4">
                  <h5>Transaction Summary</h5>
                  <div class="transactions-summary">
                    <div class="summary-item deposits">
                      <div class="summary-label">Deposits (MTD)</div>
                      <div class="summary-value">{{ formatCurrency(accountStats.deposits_mtd || 0) }}</div>
                    </div>
                    <div class="summary-item withdrawals">
                      <div class="summary-label">Withdrawals (MTD)</div>
                      <div class="summary-value">{{ formatCurrency(accountStats.withdrawals_mtd || 0) }}</div>
                    </div>
                  </div>
                </div>

                <div class="actions-section mt-4">
                  <h5>Quick Actions</h5>
                  <div class="quick-actions">
                    <router-link :to="`/accounting/bank-reconciliations/create?bank_id=${accountId}`" class="btn btn-outline-primary">
                      <i class="fas fa-balance-scale mr-1"></i> New Reconciliation
                    </router-link>
                    <button class="btn btn-outline-info ml-2" @click="exportTransactions">
                      <i class="fas fa-file-export mr-1"></i> Export Transactions
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Recent Reconciliations Card -->
          <div class="col-12 mb-4">
            <div class="card">
              <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Recent Reconciliations</h3>
                <router-link :to="`/accounting/bank-reconciliations/create?bank_id=${accountId}`" class="btn btn-sm btn-primary">
                  <i class="fas fa-plus mr-1"></i> New Reconciliation
                </router-link>
              </div>
              <div class="card-body">
                <div v-if="reconciliations.length === 0" class="text-center py-4">
                  <i class="fas fa-balance-scale fa-2x text-muted mb-3"></i>
                  <p>No reconciliations found for this account</p>
                  <div class="mt-3">
                    <router-link :to="`/accounting/bank-reconciliations/create?bank_id=${accountId}`" class="btn btn-outline-primary">
                      <i class="fas fa-plus mr-1"></i> Create First Reconciliation
                    </router-link>
                  </div>
                </div>
                <div v-else class="table-responsive">
                  <table class="data-table">
                    <thead>
                      <tr>
                        <th>Statement Date</th>
                        <th class="text-right">Statement Balance</th>
                        <th class="text-right">Book Balance</th>
                        <th class="text-right">Difference</th>
                        <th>Status</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="recon in reconciliations" :key="recon.reconciliation_id">
                        <td>{{ formatDate(recon.statement_date) }}</td>
                        <td class="text-right">{{ formatCurrency(recon.statement_balance) }}</td>
                        <td class="text-right">{{ formatCurrency(recon.book_balance) }}</td>
                        <td class="text-right" :class="getDifferenceClass(recon.statement_balance - recon.book_balance)">
                          {{ formatCurrency(recon.statement_balance - recon.book_balance) }}
                        </td>
                        <td>
                          <span
                            :class="{
                              'badge': true,
                              'badge-success': recon.status === 'Finalized',
                              'badge-warning': recon.status === 'In Progress',
                              'badge-secondary': recon.status === 'Draft'
                            }"
                          >
                            {{ recon.status }}
                          </span>
                        </td>
                        <td>
                          <router-link :to="`/accounting/bank-reconciliations/${recon.reconciliation_id}`" class="btn btn-sm btn-info">
                            <i class="fas fa-eye"></i>
                          </router-link>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>

          <!-- Recent Transactions Card -->
          <div class="col-12">
            <div class="card">
              <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Recent Transactions</h3>
                <router-link :to="`/accounting/bank-accounts/${accountId}/transactions`" class="btn btn-sm btn-info">
                  <i class="fas fa-exchange-alt mr-1"></i> View All Transactions
                </router-link>
              </div>
              <div class="card-body">
                <div v-if="transactions.length === 0" class="text-center py-4">
                  <i class="fas fa-exchange-alt fa-2x text-muted mb-3"></i>
                  <p>No transactions found for this account</p>
                </div>
                <div v-else class="table-responsive">
                  <table class="data-table">
                    <thead>
                      <tr>
                        <th>Date</th>
                        <th>Reference</th>
                        <th>Description</th>
                        <th class="text-right">Deposits</th>
                        <th class="text-right">Withdrawals</th>
                        <th class="text-right">Balance</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(transaction, index) in transactions" :key="index">
                        <td>{{ formatDate(transaction.date) }}</td>
                        <td>{{ transaction.reference }}</td>
                        <td>{{ transaction.description }}</td>
                        <td class="text-right">{{ transaction.amount > 0 ? formatCurrency(transaction.amount) : '-' }}</td>
                        <td class="text-right">{{ transaction.amount < 0 ? formatCurrency(Math.abs(transaction.amount)) : '-' }}</td>
                        <td class="text-right">{{ formatCurrency(transaction.balance) }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </template>
      </div>
    </div>
  </template>

  <script>
  import { ref, computed, onMounted } from 'vue';
  import { useRoute, useRouter } from 'vue-router';
  import axios from 'axios';

  export default {
    name: 'BankAccountDetail',
    setup() {
      const route = useRoute();
      const router = useRouter();
      const accountId = computed(() => route.params.id);

      // State variables
      const account = ref(null);
      const reconciliations = ref([]);
      const transactions = ref([]);
      const accountStats = ref({});
      const isLoading = ref(true);
      const error = ref(null);

      // Computed properties
      const lastReconciliation = computed(() => {
        if (!reconciliations.value || reconciliations.value.length === 0) return null;
        return reconciliations.value[0]; // Assuming sorted by most recent first
      });

      // Load bank account data
      const loadBankAccount = async () => {
        isLoading.value = true;
        error.value = null;

        try {
          const response = await axios.get(`/api/accounting/bank-accounts/${accountId.value}`);

          if (response.data && response.data.data) {
            account.value = response.data.data;

            // Assuming the bank_reconciliations relationship is eager loaded
            if (account.value.bank_reconciliations) {
              reconciliations.value = account.value.bank_reconciliations
                .sort((a, b) => new Date(b.statement_date) - new Date(a.statement_date))
                .slice(0, 5); // Get most recent 5
            }

            // Load additional data in parallel
            await Promise.all([
              loadAccountStats(),
              loadRecentTransactions()
            ]);
          } else {
            throw new Error('Invalid response format');
          }
        } catch (err) {
          console.error('Error loading bank account:', err);
          error.value = 'Failed to load bank account details. Please try again later.';
        } finally {
          isLoading.value = false;
        }
      };

      // Load account statistics
      const loadAccountStats = async () => {
        try {
          const response = await axios.get(`/api/accounting/bank-accounts/${accountId.value}/stats`);

          if (response.data && response.data.data) {
            accountStats.value = response.data.data;
          }
        } catch (err) {
          console.error('Error loading account stats:', err);
          // Don't set main error, just log it
        }
      };

      // Load recent transactions
      const loadRecentTransactions = async () => {
        try {
          const response = await axios.get(`/api/accounting/bank-accounts/${accountId.value}/transactions`, {
            params: { limit: 5 } // Only get most recent 5
          });

          if (response.data && response.data.data) {
            transactions.value = response.data.data;
          }
        } catch (err) {
          console.error('Error loading transactions:', err);
          // Don't set main error, just log it
        }
      };

      // Format account number for display (e.g., **** **** **** 1234)
      const formatAccountNumber = (accountNumber) => {
        if (!accountNumber) return '';

        // If account number is less than 4 characters, don't mask
        if (accountNumber.length <= 4) return accountNumber;

        // Show only last 4 digits
        const lastFourDigits = accountNumber.slice(-4);
        const maskedPart = '*'.repeat(accountNumber.length - 4);

        // Group by 4 for readability if the number is long enough
        if (accountNumber.length >= 8) {
          let formatted = '';
          for (let i = 0; i < maskedPart.length; i += 4) {
            formatted += maskedPart.substr(i, 4) + ' ';
          }
          return (formatted + lastFourDigits).trim();
        }

        return maskedPart + lastFourDigits;
      };

      // Format date for display
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

      // Get CSS class for reconciliation difference
      const getDifferenceClass = (difference) => {
        if (Math.abs(difference) < 0.01) return 'text-success';
        return 'text-danger';
      };

      // Export transactions (placeholder function)
      const exportTransactions = () => {
        // In a real app, this would make an API call to generate a CSV/Excel export
        if (window.$toast) {
          window.$toast.info('Exporting transactions...');
        }

        // Redirect to transactions page with export param
        router.push(`/accounting/bank-accounts/${accountId.value}/transactions?export=true`);
      };

      // Load data on component mount
      onMounted(() => {
        loadBankAccount();
      });

      return {
        accountId,
        account,
        reconciliations,
        transactions,
        accountStats,
        lastReconciliation,
        isLoading,
        error,
        formatAccountNumber,
        formatDate,
        formatCurrency,
        getDifferenceClass,
        exportTransactions
      };
    }
  };
  </script>

  <style scoped>
  .bank-account-detail {
    padding: 1rem 0;
  }

  /* Account summary styles */
  .account-summary {
    display: flex;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid var(--gray-200);
  }

  .bank-logo {
    width: 60px;
    height: 60px;
    background-color: var(--primary-bg);
    color: var(--primary-color);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
  }

  .account-details {
    flex: 1;
  }

  .bank-name {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
  }

  .account-number {
    font-family: monospace;
    font-size: 1rem;
    color: var(--gray-600);
    margin-bottom: 0.25rem;
  }

  .account-holder {
    font-size: 0.875rem;
    color: var(--gray-500);
  }

  /* Details table styles */
  .table-details {
    margin-bottom: 0;
  }

  .table-details th {
    width: 40%;
    font-weight: 600;
    border-top: none;
  }

  .table-details td {
    border-top: none;
  }

  /* Stats grid styles */
  .stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
  }

  .stat-item {
    background-color: var(--gray-50);
    padding: 1rem;
    border-radius: 0.5rem;
    border: 1px solid var(--gray-200);
  }

  .stat-label {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: var(--gray-500);
    margin-bottom: 0.5rem;
  }

  .stat-value {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--gray-800);
  }

  /* Transaction summary styles */
  .transactions-summary {
    display: flex;
    gap: 1.5rem;
    margin-top: 1rem;
  }

  .summary-item {
    flex: 1;
    padding: 1rem;
    border-radius: 0.5rem;
  }

  .summary-item.deposits {
    background-color: var(--success-bg);
    color: var(--success-color);
    border: 1px solid rgba(5, 150, 105, 0.2);
  }

  .summary-item.withdrawals {
    background-color: var(--danger-bg);
    color: var(--danger-color);
    border: 1px solid rgba(220, 38, 38, 0.2);
  }

  .summary-label {
    font-size: 0.75rem;
    text-transform: uppercase;
    margin-bottom: 0.5rem;
  }

  .summary-value {
    font-size: 1.25rem;
    font-weight: 600;
  }

  /* Actions section */
  .quick-actions {
    margin-top: 1rem;
  }

  /* Badge styles */
  .badge {
    padding: 0.5em 0.75em;
    font-size: 0.75em;
  }

  /* Responsive adjustments */
  @media (max-width: 768px) {
    .stats-grid {
      grid-template-columns: 1fr;
      gap: 1rem;
    }

    .transactions-summary {
      flex-direction: column;
      gap: 1rem;
    }
  }
  </style>
