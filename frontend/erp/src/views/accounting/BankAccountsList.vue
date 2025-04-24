<!-- src/views/accounting/BankAccountsList.vue -->
<template>
    <div class="bank-accounts-list">
      <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
          <h1>Bank Accounts</h1>
          <router-link to="/accounting/bank-accounts/create" class="btn btn-primary">
            <i class="fas fa-plus mr-2"></i> Add New Bank Account
          </router-link>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <!-- Search and filters -->
          <SearchFilter
            v-model:value="searchTerm"
            placeholder="Search bank name or account number..."
            @search="loadBankAccounts"
            @clear="clearSearch"
          >
            <template #filters>
              <div class="filter-group">
                <label>Status</label>
                <select v-model="filters.status" class="form-control" @change="loadBankAccounts">
                  <option value="">All Accounts</option>
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                </select>
              </div>
            </template>
          </SearchFilter>

          <!-- Loading state -->
          <div v-if="isLoading" class="text-center py-5">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p class="mt-2">Loading bank accounts...</p>
          </div>

          <!-- Error state -->
          <div v-else-if="error" class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            {{ error }}
          </div>

          <!-- Empty state -->
          <div v-else-if="bankAccounts.length === 0" class="text-center py-5">
            <i class="fas fa-university fa-3x text-muted mb-3"></i>
            <h4>No Bank Accounts Found</h4>
            <p class="text-muted">Create a new bank account to get started</p>
            <router-link to="/accounting/bank-accounts/create" class="btn btn-primary mt-2">
              <i class="fas fa-plus mr-2"></i> Add New Bank Account
            </router-link>
          </div>

          <!-- Data table -->
          <div v-else class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Bank Name</th>
                  <th>Account Number</th>
                  <th>Account Name</th>
                  <th>GL Account</th>
                  <th class="text-right">Current Balance</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="account in bankAccounts" :key="account.bank_id">
                  <td>{{ account.bank_name }}</td>
                  <td>{{ account.account_number }}</td>
                  <td>{{ account.account_name }}</td>
                  <td>
                    <span v-if="account.chart_of_account">
                      {{ account.chart_of_account.account_code }} - {{ account.chart_of_account.name }}
                    </span>
                    <span v-else class="text-muted">Not assigned</span>
                  </td>
                  <td class="text-right">{{ formatCurrency(account.current_balance) }}</td>
                  <td>
                    <div class="btn-group">
                      <router-link :to="`/accounting/bank-accounts/${account.bank_id}`" class="btn btn-sm btn-info" title="View Details">
                        <i class="fas fa-eye"></i>
                      </router-link>
                      <router-link :to="`/accounting/bank-accounts/${account.bank_id}/edit`" class="btn btn-sm btn-primary" title="Edit">
                        <i class="fas fa-edit"></i>
                      </router-link>
                      <router-link :to="`/accounting/bank-accounts/${account.bank_id}/transactions`" class="btn btn-sm btn-secondary" title="Transactions">
                        <i class="fas fa-exchange-alt"></i>
                      </router-link>
                      <button
                        @click="confirmDelete(account)"
                        class="btn btn-sm btn-danger"
                        title="Delete"
                        :disabled="hasReconciliations(account)"
                      >
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div v-if="totalAccounts > 0" class="mt-4">
            <PaginationComponent
              :current-page="currentPage"
              :total-pages="totalPages"
              :from="paginationFrom"
              :to="paginationTo"
              :total="totalAccounts"
              @page-changed="onPageChange"
            />
          </div>
        </div>
      </div>

      <!-- Delete confirmation modal -->
      <ConfirmationModal
        v-if="showDeleteModal"
        title="Delete Bank Account"
        :message="`Are you sure you want to delete the bank account '${selectedAccount?.account_name}' at '${selectedAccount?.bank_name}'? This action cannot be undone.`"
        confirm-button-text="Delete"
        confirm-button-class="btn btn-danger"
        @confirm="deleteBankAccount"
        @close="showDeleteModal = false"
      />
    </div>
  </template>

  <script>
  import { ref, computed, onMounted, reactive } from 'vue';
  import axios from 'axios';

  export default {
    name: 'BankAccountsList',
    setup() {
      // State variables
      const bankAccounts = ref([]);
      const isLoading = ref(true);
      const error = ref(null);
      const searchTerm = ref('');
      const filters = reactive({
        status: ''
      });
      const currentPage = ref(1);
      const perPage = ref(10);
      const totalAccounts = ref(0);
      const totalPages = ref(0);
      const showDeleteModal = ref(false);
      const selectedAccount = ref(null);

      // Computed properties
      const paginationFrom = computed(() => {
        if (totalAccounts.value === 0) return 0;
        return (currentPage.value - 1) * perPage.value + 1;
      });

      const paginationTo = computed(() => {
        return Math.min(currentPage.value * perPage.value, totalAccounts.value);
      });

      // Load bank accounts from API
      const loadBankAccounts = async () => {
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

          if (filters.status) {
            params.status = filters.status;
          }

          const response = await axios.get('/api/accounting/bank-accounts', { params });

          if (response.data && response.data.data) {
            bankAccounts.value = response.data.data;

            // Pagination handling
            if (response.data.meta) {
              totalAccounts.value = response.data.meta.total;
              totalPages.value = response.data.meta.last_page;
            } else {
              totalAccounts.value = bankAccounts.value.length;
              totalPages.value = 1;
            }
          } else {
            throw new Error('Invalid response format');
          }
        } catch (err) {
          console.error('Error loading bank accounts:', err);
          error.value = 'Failed to load bank accounts. Please try again later.';
        } finally {
          isLoading.value = false;
        }
      };

      // Handle page change in pagination
      const onPageChange = (page) => {
        currentPage.value = page;
        loadBankAccounts();
      };

      // Clear search
      const clearSearch = () => {
        searchTerm.value = '';
        loadBankAccounts();
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

      // Check if account has reconciliations
      const hasReconciliations = (account) => {
        return account.bank_reconciliations && account.bank_reconciliations.length > 0;
      };

      // Confirm delete
      const confirmDelete = (account) => {
        if (hasReconciliations(account)) return;

        selectedAccount.value = account;
        showDeleteModal.value = true;
      };

      // Delete bank account
      const deleteBankAccount = async () => {
        if (!selectedAccount.value) return;

        try {
          await axios.delete(`/api/accounting/bank-accounts/${selectedAccount.value.bank_id}`);

          // Remove from list
          bankAccounts.value = bankAccounts.value.filter(acc => acc.bank_id !== selectedAccount.value.bank_id);

          // Update total
          totalAccounts.value--;

          // Reset modal
          showDeleteModal.value = false;
          selectedAccount.value = null;

          // Show success notification
          // This assumes you have a toast notification system
          if (window.$toast) {
            window.$toast.success('Bank account deleted successfully');
          }
        } catch (err) {
          console.error('Error deleting bank account:', err);

          // Show error notification
          if (window.$toast) {
            window.$toast.error(err.response?.data?.message || 'Failed to delete bank account');
          }
        }
      };

      // Lifecycle hooks
      onMounted(() => {
        loadBankAccounts();
      });

      return {
        bankAccounts,
        isLoading,
        error,
        searchTerm,
        filters,
        currentPage,
        totalAccounts,
        totalPages,
        paginationFrom,
        paginationTo,
        showDeleteModal,
        selectedAccount,
        loadBankAccounts,
        onPageChange,
        clearSearch,
        formatCurrency,
        hasReconciliations,
        confirmDelete,
        deleteBankAccount
      };
    }
  };
  </script>

  <style scoped>
  .bank-accounts-list {
    padding: 1rem 0;
  }

  .data-table th,
  .data-table td {
    vertical-align: middle;
  }

  .btn-group .btn {
    margin-right: 0.25rem;
  }

  .btn-group .btn:last-child {
    margin-right: 0;
  }
  </style>
