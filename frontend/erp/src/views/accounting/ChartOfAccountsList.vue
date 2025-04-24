<template>
    <div class="page-content">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Chart of Accounts</h1>
        <router-link to="/accounting/chart-of-accounts/create" class="btn btn-primary">
          <i class="fas fa-plus mr-2"></i> Add New Account
        </router-link>
      </div>

      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h3 class="card-title">Accounts Hierarchy</h3>
          <div>
            <button class="btn btn-secondary mr-2" @click="expandAll">
              <i class="fas fa-expand-alt mr-1"></i> Expand All
            </button>
            <button class="btn btn-secondary" @click="collapseAll">
              <i class="fas fa-compress-alt mr-1"></i> Collapse All
            </button>
          </div>
        </div>
        <div class="card-body">
          <div v-if="isLoading" class="loading-indicator">
            <i class="fas fa-spinner fa-spin"></i> Loading accounts...
          </div>

          <div v-else-if="accounts.length === 0" class="empty-state">
            <div class="empty-icon">
              <i class="fas fa-file-invoice-dollar"></i>
            </div>
            <h3>No Accounts Found</h3>
            <p>There are no chart of accounts created yet.</p>
          </div>

          <div v-else class="accounts-hierarchy">
            <!-- Root level accounts (no parent) -->
            <div v-for="account in rootAccounts" :key="account.account_id" class="account-node">
              <account-tree-item
                :account="account"
                :all-accounts="accounts"
                :expanded-nodes="expandedNodes"
                @toggle-expand="toggleNode"
                @view-account="viewAccount"
                @edit-account="editAccount"
                @delete-account="confirmDelete"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Confirmation Modal -->
      <confirmation-modal
        v-if="showDeleteModal"
        :title="'Delete Account'"
        :message="'Are you sure you want to delete account <strong>' + (accountToDelete ? accountToDelete.name : '') + '</strong>? This action cannot be undone.'"
        confirm-button-text="Delete"
        confirm-button-class="btn btn-danger"
        @confirm="deleteAccount"
        @close="cancelDelete"
      />
    </div>
  </template>

  <script>
  import axios from 'axios';
  import AccountTreeItem from '../components/accounting/AccountTreeItem.vue';

  export default {
    name: 'ChartOfAccountsList',
    components: {
      AccountTreeItem
    },
    data() {
      return {
        accounts: [],
        isLoading: true,
        expandedNodes: new Set(),
        showDeleteModal: false,
        accountToDelete: null
      };
    },
    computed: {
      rootAccounts() {
        return this.accounts.filter(account => !account.parent_account_id);
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

          // Automatically expand the first level
          this.rootAccounts.forEach(account => {
            this.expandedNodes.add(account.account_id);
          });
        } catch (error) {
          console.error('Error fetching chart of accounts:', error);
          this.$toast.error('Failed to load chart of accounts');
        } finally {
          this.isLoading = false;
        }
      },

      toggleNode(accountId) {
        if (this.expandedNodes.has(accountId)) {
          this.expandedNodes.delete(accountId);
        } else {
          this.expandedNodes.add(accountId);
        }
      },

      expandAll() {
        this.accounts.forEach(account => {
          this.expandedNodes.add(account.account_id);
        });
      },

      collapseAll() {
        this.expandedNodes.clear();
      },

      viewAccount(account) {
        this.$router.push(`/accounting/chart-of-accounts/${account.account_id}`);
      },

      editAccount(account) {
        this.$router.push(`/accounting/chart-of-accounts/${account.account_id}/edit`);
      },

      confirmDelete(account) {
        this.accountToDelete = account;
        this.showDeleteModal = true;
      },

      cancelDelete() {
        this.showDeleteModal = false;
        this.accountToDelete = null;
      },

      async deleteAccount() {
        if (!this.accountToDelete) return;

        try {
          await axios.delete(`/api/accounting/chart-of-accounts/${this.accountToDelete.account_id}`);
          this.$toast.success('Account deleted successfully');
          this.fetchAccounts();
        } catch (error) {
          if (error.response && error.response.data && error.response.data.message) {
            this.$toast.error(error.response.data.message);
          } else {
            this.$toast.error('Failed to delete account');
          }
        } finally {
          this.showDeleteModal = false;
          this.accountToDelete = null;
        }
      }
    }
  };
  </script>

  <style scoped>
  .accounts-hierarchy {
    padding: 0.5rem 0;
  }

  .account-node {
    margin-bottom: 0.5rem;
  }

  /* Add styles for account types */
  .account-type-label {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
  }

  .type-asset {
    background-color: var(--primary-bg);
    color: var(--primary-color);
  }

  .type-liability {
    background-color: var(--warning-bg);
    color: var(--warning-color);
  }

  .type-equity {
    background-color: var(--success-bg);
    color: var(--success-color);
  }

  .type-revenue {
    background-color: var(--success-bg);
    color: var(--success-color);
  }

  .type-expense {
    background-color: var(--danger-bg);
    color: var(--danger-color);
  }
  </style>
