<!-- src/views/accounting/ChartOfAccountDetail.vue -->
<template>
    <div class="page-content">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Account Details</h1>
        <div>
          <router-link :to="`/accounting/chart-of-accounts/${accountId}/edit`" class="btn btn-primary mr-2">
            <i class="fas fa-edit mr-2"></i> Edit Account
          </router-link>
          <router-link to="/accounting/chart-of-accounts" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i> Back to Accounts
          </router-link>
        </div>
      </div>

      <div v-if="isLoading" class="loading-indicator">
        <i class="fas fa-spinner fa-spin"></i> Loading account data...
      </div>

      <div v-else class="row">
        <!-- Account Basic Info -->
        <div class="col-lg-6">
          <div class="card mb-4">
            <div class="card-header">
              <h3 class="card-title">Account Information</h3>
            </div>
            <div class="card-body">
              <div class="account-header mb-3">
                <div class="d-flex align-items-center">
                  <span :class="['account-type-badge', 'bg-' + accountTypeBadgeClass]">
                    {{ account.account_type }}
                  </span>
                  <span v-if="!account.is_active" class="inactive-badge ml-2">Inactive</span>
                </div>
                <h2 class="account-name mt-2">{{ account.name }}</h2>
                <div class="account-code">Code: {{ account.account_code }}</div>
              </div>

              <div class="detail-grid">
                <div class="detail-item">
                  <div class="detail-label">Parent Account</div>
                  <div class="detail-value">
                    <template v-if="parentAccount">
                      <router-link :to="`/accounting/chart-of-accounts/${parentAccount.account_id}`">
                        {{ parentAccount.account_code }} - {{ parentAccount.name }}
                      </router-link>
                    </template>
                    <template v-else>
                      <span class="text-muted">No parent (Top Level)</span>
                    </template>
                  </div>
                </div>
                <div class="detail-item">
                  <div class="detail-label">Account Type</div>
                  <div class="detail-value">{{ account.account_type }}</div>
                </div>
                <div class="detail-item">
                  <div class="detail-label">Status</div>
                  <div class="detail-value">
                    <span :class="account.is_active ? 'text-success' : 'text-danger'">
                      {{ account.is_active ? 'Active' : 'Inactive' }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Child Accounts -->
        <div class="col-lg-6">
          <div class="card mb-4">
            <div class="card-header">
              <h3 class="card-title">Child Accounts</h3>
            </div>
            <div class="card-body">
              <div v-if="childAccounts.length === 0" class="empty-state small">
                <i class="fas fa-info-circle"></i>
                <p>No child accounts found</p>
              </div>
              <div v-else class="child-accounts-list">
                <div v-for="childAccount in childAccounts" :key="childAccount.account_id" class="child-account-item">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <span class="account-code">{{ childAccount.account_code }}</span>
                      <router-link :to="`/accounting/chart-of-accounts/${childAccount.account_id}`" class="account-name ml-2">
                        {{ childAccount.name }}
                      </router-link>
                      <span :class="['account-type-badge', 'bg-' + getAccountTypeBadgeClass(childAccount.account_type), 'small', 'ml-2']">
                        {{ childAccount.account_type }}
                      </span>
                      <span v-if="!childAccount.is_active" class="inactive-badge small ml-2">Inactive</span>
                    </div>
                    <div>
                      <router-link :to="`/accounting/chart-of-accounts/${childAccount.account_id}`" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-eye"></i>
                      </router-link>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent Transactions -->
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">Recent Transactions</h3>
            </div>
            <div class="card-body">
              <div v-if="journalEntries.length === 0" class="empty-state">
                <div class="empty-icon">
                  <i class="fas fa-file-invoice"></i>
                </div>
                <h3>No Transactions Found</h3>
                <p>There are no journal entries for this account yet.</p>
              </div>
              <div v-else>
                <div class="table-container">
                  <table class="data-table">
                    <thead>
                      <tr>
                        <th>Date</th>
                        <th>Journal #</th>
                        <th>Description</th>
                        <th class="text-right">Debit</th>
                        <th class="text-right">Credit</th>
                        <th>Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="entry in journalEntries" :key="entry.line_id">
                        <td>{{ formatDate(entry.entry_date) }}</td>
                        <td>{{ entry.journal_number }}</td>
                        <td>{{ entry.description }}</td>
                        <td class="text-right">{{ formatCurrency(entry.debit_amount) }}</td>
                        <td class="text-right">{{ formatCurrency(entry.credit_amount) }}</td>
                        <td>
                          <span :class="['badge', entry.status === 'Posted' ? 'badge-success' : 'badge-secondary']">
                            {{ entry.status }}
                          </span>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </template>

  <script>
  import axios from 'axios';

  export default {
    name: 'ChartOfAccountDetail',
    props: {
      accountId: {
        type: String,
        required: true
      }
    },
    data() {
      return {
        account: {},
        allAccounts: [],
        journalEntries: [],
        isLoading: true
      };
    },
    computed: {
      parentAccount() {
        if (!this.account.parent_account_id) return null;
        return this.allAccounts.find(acc => acc.account_id === this.account.parent_account_id);
      },
      childAccounts() {
        return this.allAccounts.filter(acc => acc.parent_account_id === this.account.account_id);
      },
      accountTypeBadgeClass() {
        return this.getAccountTypeBadgeClass(this.account.account_type);
      }
    },
    created() {
      this.fetchData();
    },
    methods: {
      async fetchData() {
        this.isLoading = true;
        try {
          // Fetch all accounts to find parent and children
          const accountsResponse = await axios.get('/api/accounting/chart-of-accounts');
          this.allAccounts = accountsResponse.data.data;

          // Fetch the specific account details
          const accountResponse = await axios.get(`/api/accounting/chart-of-accounts/${this.accountId}`);
          this.account = accountResponse.data.data;

          // Fetch recent journal entries for this account
          // Note: This is a simplified example. In a real app, you'd need an API endpoint to fetch journal entries by account
          // For now, we'll simulate it with sample data
          this.journalEntries = this.getSampleJournalEntries();
        } catch (error) {
          console.error('Error fetching account data:', error);
          this.$toast.error('Failed to load account data');
        } finally {
          this.isLoading = false;
        }
      },

      getAccountTypeBadgeClass(accountType) {
        switch (accountType) {
          case 'Asset': return 'primary';
          case 'Liability': return 'warning';
          case 'Equity': return 'success';
          case 'Revenue': return 'success';
          case 'Expense': return 'danger';
          default: return 'secondary';
        }
      },

      formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString();
      },

      formatCurrency(value) {
        if (value === 0) return '-';
        return new Intl.NumberFormat('id-ID', {
          style: 'currency',
          currency: 'IDR',
          minimumFractionDigits: 2
        }).format(value);
      },

      // Sample data generator for journal entries (in a real app, this would come from the API)
      getSampleJournalEntries() {
        // This is just sample data - in a real app you'd fetch from an API
        return [
          {
            line_id: 1,
            journal_number: 'J-2023-001',
            entry_date: '2023-01-15',
            description: 'Monthly expense allocation',
            debit_amount: 1500000,
            credit_amount: 0,
            status: 'Posted'
          },
          {
            line_id: 2,
            journal_number: 'J-2023-002',
            entry_date: '2023-01-31',
            description: 'Vendor payment',
            debit_amount: 0,
            credit_amount: 2500000,
            status: 'Posted'
          },
          {
            line_id: 3,
            journal_number: 'J-2023-005',
            entry_date: '2023-02-10',
            description: 'Customer payment received',
            debit_amount: 3750000,
            credit_amount: 0,
            status: 'Posted'
          }
        ];
      }
    }
  };
  </script>

  <style scoped>
  .account-header {
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--gray-200);
  }

  .account-type-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  .bg-primary {
    background-color: var(--primary-bg);
    color: var(--primary-color);
  }

  .bg-warning {
    background-color: var(--warning-bg);
    color: var(--warning-color);
  }

  .bg-success {
    background-color: var(--success-bg);
    color: var(--success-color);
  }

  .bg-danger {
    background-color: var(--danger-bg);
    color: var(--danger-color);
  }

  .inactive-badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    background-color: var(--gray-200);
    color: var(--gray-700);
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
  }

  .account-name {
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0.5rem 0;
  }

  .account-code {
    color: var(--gray-600);
    font-family: monospace;
    font-size: 0.9rem;
  }

  .detail-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1rem;
    margin-top: 1.5rem;
  }

  .detail-item {
    display: flex;
    flex-direction: column;
  }

  .detail-label {
    font-size: 0.75rem;
    color: var(--gray-500);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.25rem;
  }

  .detail-value {
    font-size: 1rem;
    font-weight: 500;
  }

  .child-accounts-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .child-account-item {
    padding: 0.75rem;
    border-radius: 0.375rem;
    border: 1px solid var(--gray-200);
    background-color: white;
  }

  .child-account-item:hover {
    background-color: var(--gray-50);
  }

  .empty-state {
    text-align: center;
    padding: 2rem 0;
    color: var(--gray-500);
  }

  .empty-state.small {
    padding: 1rem;
  }

  .empty-state i {
    font-size: 2rem;
    color: var(--gray-300);
    margin-bottom: 1rem;
  }

  .empty-state.small i {
    font-size: 1.25rem;
    margin-bottom: 0.5rem;
  }

  .table-container {
    overflow-x: auto;
  }

  @media (min-width: 768px) {
    .detail-grid {
      grid-template-columns: repeat(2, 1fr);
    }
  }
  </style>
