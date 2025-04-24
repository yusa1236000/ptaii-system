<template>
    <div class="page-content">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Account Structure</h1>
        <router-link to="/accounting/chart-of-accounts" class="btn btn-secondary">
          <i class="fas fa-arrow-left mr-2"></i> Back to Accounts
        </router-link>
      </div>

      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h3 class="card-title">Chart of Accounts Hierarchy</h3>
          <div class="d-flex">
            <div class="form-group mb-0 mr-3">
              <select v-model="selectedAccountType" class="form-control">
                <option value="">All Account Types</option>
                <option value="Asset">Assets</option>
                <option value="Liability">Liabilities</option>
                <option value="Equity">Equity</option>
                <option value="Revenue">Revenue</option>
                <option value="Expense">Expenses</option>
              </select>
            </div>
            <button class="btn btn-primary" @click="generateAccountTree">
              <i class="fas fa-sync-alt mr-2"></i> Refresh
            </button>
          </div>
        </div>
        <div class="card-body">
          <div v-if="isLoading" class="loading-indicator">
            <i class="fas fa-spinner fa-spin"></i> Loading account structure...
          </div>

          <div v-else>
            <!-- Account Type Legend -->
            <div class="account-type-legend mb-4">
              <div class="legend-item">
                <span class="legend-color type-asset"></span>
                <span class="legend-text">Asset</span>
              </div>
              <div class="legend-item">
                <span class="legend-color type-liability"></span>
                <span class="legend-text">Liability</span>
              </div>
              <div class="legend-item">
                <span class="legend-color type-equity"></span>
                <span class="legend-text">Equity</span>
              </div>
              <div class="legend-item">
                <span class="legend-color type-revenue"></span>
                <span class="legend-text">Revenue</span>
              </div>
              <div class="legend-item">
                <span class="legend-color type-expense"></span>
                <span class="legend-text">Expense</span>
              </div>
            </div>

            <!-- Tree Visualization -->
            <div class="structure-tree-container">
              <div v-if="displayedAccounts.length === 0" class="empty-state">
                <div class="empty-icon">
                  <i class="fas fa-sitemap"></i>
                </div>
                <h3>No Accounts Found</h3>
                <p v-if="selectedAccountType">No {{ selectedAccountType }} accounts found. Try changing the filter.</p>
                <p v-else>No accounts found in the chart of accounts.</p>
              </div>

              <div v-else class="structure-tree">
                <div v-for="(accountType, index) in accountTypeGroups" :key="index" class="account-group">
                  <div class="account-group-header" :class="'type-' + accountType.toLowerCase()">
                    {{ accountType }}
                  </div>
                  <div class="account-tree-nodes">
                    <structure-tree-node
                      v-for="rootAccount in filterRootAccountsByType(accountType)"
                      :key="rootAccount.account_id"
                      :account="rootAccount"
                      :all-accounts="accounts"
                      @navigate-to-account="navigateToAccount"
                    />
                  </div>
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
  import StructureTreeNode from '../components/accounting/StructureTreeNode.vue';

  export default {
    name: 'AccountStructureViewer',
    components: {
      StructureTreeNode
    },
    data() {
      return {
        accounts: [],
        isLoading: true,
        selectedAccountType: '',
        accountTypeGroups: ['Asset', 'Liability', 'Equity', 'Revenue', 'Expense']
      };
    },
    computed: {
      displayedAccounts() {
        if (!this.selectedAccountType) {
          return this.accounts;
        }
        return this.accounts.filter(account =>
          account.account_type === this.selectedAccountType
        );
      }
    },
    created() {
      this.fetchAccounts();
    },
    methods: {
      async fetchAccounts() {
        this.isLoading = true;
        try {
          const response = await axios.get('/api/accounting/chart-of-accounts');
          this.accounts = response.data.data;
          this.generateAccountTree();
        } catch (error) {
          console.error('Error fetching chart of accounts:', error);
          if (this.$toast && typeof this.$toast.error === 'function') {
            this.$toast.error('Failed to load chart of accounts');
          } else {
            alert('Failed to load chart of accounts');
          }
        } finally {
          this.isLoading = false;
        }
      },

      generateAccountTree() {
        // This method is a placeholder for any additional processing you might want to do
        // when refreshing or generating the tree
        this.isLoading = true;
        setTimeout(() => {
          this.isLoading = false;
        }, 300);
      },

      filterRootAccountsByType(accountType) {
        return this.displayedAccounts.filter(account =>
          account.account_type === accountType && !account.parent_account_id
        );
      },

      navigateToAccount(accountId) {
        this.$router.push(`/accounting/chart-of-accounts/${accountId}`);
      }
    }
  };
  </script>

  <style scoped>
  .account-type-legend {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    padding: 0.5rem;
    background-color: var(--gray-50);
    border-radius: 0.375rem;
  }

  .legend-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .legend-color {
    width: 1rem;
    height: 1rem;
    border-radius: 0.25rem;
  }

  .legend-text {
    font-size: 0.875rem;
    font-weight: 500;
  }

  .structure-tree-container {
    margin-top: 1.5rem;
  }

  .structure-tree {
    display: flex;
    flex-direction: column;
    gap: 2rem;
  }

  .account-group {
    border: 1px solid var(--gray-200);
    border-radius: 0.5rem;
    overflow: hidden;
  }

  .account-group-header {
    padding: 0.75rem 1rem;
    font-weight: 600;
    color: white;
  }

  .type-asset {
    background-color: var(--primary-color);
  }

  .type-liability {
    background-color: var(--warning-color);
  }

  .type-equity {
    background-color: var(--success-color);
  }

  .type-revenue {
    background-color: var(--success-dark, var(--success-color));
  }

  .type-expense {
    background-color: var(--danger-color);
  }

  .account-tree-nodes {
    padding: 1rem;
  }

  .empty-state {
    text-align: center;
    padding: 3rem 0;
  }

  .empty-state .empty-icon {
    font-size: 3rem;
    color: var(--gray-300);
    margin-bottom: 1rem;
  }

  .empty-state h3 {
    font-size: 1.25rem;
    color: var(--gray-700);
    margin-bottom: 0.5rem;
  }

  .empty-state p {
    color: var(--gray-500);
  }
  </style>
