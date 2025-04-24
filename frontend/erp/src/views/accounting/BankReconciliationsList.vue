<!-- src/views/accounting/BankReconciliationsList.vue -->
<template>
    <div class="bank-reconciliations-list">
      <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
          <h2>Bank Reconciliations</h2>
          <router-link to="/accounting/bank-reconciliations/create" class="btn btn-primary">
            <i class="fas fa-plus mr-2"></i> New Reconciliation
          </router-link>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <!-- Search and Filters -->
          <SearchFilter
            v-model:value="searchTerm"
            placeholder="Search reconciliations..."
            @search="fetchReconciliations"
            @clear="clearSearch"
          >
            <template #filters>
              <div class="filter-group">
                <label>Bank Account</label>
                <select v-model="filters.bankId" class="form-control" @change="fetchReconciliations">
                  <option value="">All Bank Accounts</option>
                  <option v-for="bank in bankAccounts" :key="bank.bank_id" :value="bank.bank_id">
                    {{ bank.bank_name }} - {{ bank.account_number }}
                  </option>
                </select>
              </div>
              <div class="filter-group">
                <label>Status</label>
                <select v-model="filters.status" class="form-control" @change="fetchReconciliations">
                  <option value="">All Status</option>
                  <option value="Draft">Draft</option>
                  <option value="In Progress">In Progress</option>
                  <option value="Finalized">Finalized</option>
                </select>
              </div>
              <div class="filter-group">
                <label>Date Range</label>
                <div class="d-flex">
                  <input
                    type="date"
                    v-model="filters.fromDate"
                    class="form-control"
                    @change="fetchReconciliations"
                  >
                  <span class="mx-2 d-flex align-items-center">to</span>
                  <input
                    type="date"
                    v-model="filters.toDate"
                    class="form-control"
                    @change="fetchReconciliations"
                  >
                </div>
              </div>
            </template>
          </SearchFilter>

          <!-- Loading State -->
          <div v-if="isLoading" class="text-center py-5">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p class="mt-2">Loading reconciliations...</p>
          </div>

          <!-- Empty State -->
          <div v-else-if="reconciliations.length === 0" class="text-center py-5">
            <div class="empty-state">
              <div class="empty-icon">
                <i class="fas fa-file-invoice-dollar"></i>
              </div>
              <h3>No Reconciliations Found</h3>
              <p>Create your first bank reconciliation to start matching transactions.</p>
              <router-link to="/accounting/bank-reconciliations/create" class="btn btn-primary mt-3">
                <i class="fas fa-plus mr-1"></i> New Reconciliation
              </router-link>
            </div>
          </div>

          <!-- Data Table -->
          <div v-else class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Bank Account</th>
                  <th>Statement Date</th>
                  <th>Statement Balance</th>
                  <th>Book Balance</th>
                  <th>Difference</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="recon in reconciliations" :key="recon.reconciliation_id">
                  <td>
                    <div class="d-flex flex-column">
                      <span class="font-weight-medium">{{ recon.bank_account.bank_name }}</span>
                      <small class="text-muted">{{ recon.bank_account.account_number }}</small>
                    </div>
                  </td>
                  <td>{{ formatDate(recon.statement_date) }}</td>
                  <td>{{ formatCurrency(recon.statement_balance) }}</td>
                  <td>{{ formatCurrency(recon.book_balance) }}</td>
                  <td>
                    <span
                      :class="{'text-danger': getDifference(recon) !== 0, 'text-success': getDifference(recon) === 0}"
                    >
                      {{ formatCurrency(getDifference(recon)) }}
                    </span>
                  </td>
                  <td>
                    <span
                      :class="{
                        'badge': true,
                        'badge-warning': recon.status === 'Draft' || recon.status === 'In Progress',
                        'badge-success': recon.status === 'Finalized'
                      }"
                    >
                      {{ recon.status }}
                    </span>
                  </td>
                  <td>
                    <div class="btn-group">
                      <router-link
                        :to="`/accounting/bank-reconciliations/${recon.reconciliation_id}`"
                        class="btn btn-sm btn-info"
                        title="View Details"
                      >
                        <i class="fas fa-eye"></i>
                      </router-link>
                      <router-link
                        v-if="recon.status !== 'Finalized'"
                        :to="`/accounting/bank-reconciliations/${recon.reconciliation_id}/edit`"
                        class="btn btn-sm btn-primary"
                        title="Edit"
                      >
                        <i class="fas fa-edit"></i>
                      </router-link>
                      <router-link
                        v-if="recon.status !== 'Finalized'"
                        :to="`/accounting/bank-reconciliations/${recon.reconciliation_id}/match`"
                        class="btn btn-sm btn-success"
                        title="Match Transactions"
                      >
                        <i class="fas fa-exchange-alt"></i>
                      </router-link>
                      <router-link
                        v-if="recon.status !== 'Finalized'"
                        :to="`/accounting/bank-reconciliations/${recon.reconciliation_id}/finalize`"
                        class="btn btn-sm btn-warning"
                        title="Finalize"
                      >
                        <i class="fas fa-check-circle"></i>
                      </router-link>
                      <button
                        v-if="recon.status !== 'Finalized'"
                        @click="confirmDelete(recon)"
                        class="btn btn-sm btn-danger"
                        title="Delete"
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
          <div v-if="totalRecords > 0" class="mt-4">
            <PaginationComponent
              :current-page="currentPage"
              :total-pages="totalPages"
              :from="paginationFrom"
              :to="paginationTo"
              :total="totalRecords"
              @page-changed="onPageChange"
            />
          </div>
        </div>
      </div>

      <!-- Delete Confirmation Modal -->
      <ConfirmationModal
        v-if="showDeleteModal"
        title="Delete Reconciliation"
        :message="`Are you sure you want to delete the reconciliation for ${selectedReconciliation?.bank_account?.bank_name} on ${formatDate(selectedReconciliation?.statement_date)}?`"
        confirm-button-text="Delete"
        confirm-button-class="btn btn-danger"
        @confirm="deleteReconciliation"
        @close="showDeleteModal = false"
      />
    </div>
  </template>

  <script>
  import { ref, reactive, computed, onMounted } from 'vue';
  import axios from 'axios';

  export default {
    name: 'BankReconciliationsList',
    setup() {
      // State
      const reconciliations = ref([]);
      const bankAccounts = ref([]);
      const isLoading = ref(true);
      const searchTerm = ref('');
      const filters = reactive({
        bankId: '',
        status: '',
        fromDate: '',
        toDate: ''
      });
      const currentPage = ref(1);
      const perPage = ref(10);
      const totalRecords = ref(0);
      const totalPages = ref(0);
      const showDeleteModal = ref(false);
      const selectedReconciliation = ref(null);

      // Computed properties
      const paginationFrom = computed(() => {
        if (totalRecords.value === 0) return 0;
        return (currentPage.value - 1) * perPage.value + 1;
      });

      const paginationTo = computed(() => {
        return Math.min(currentPage.value * perPage.value, totalRecords.value);
      });

      // Methods
      const fetchReconciliations = async () => {
        isLoading.value = true;
        try {
          const params = {
            page: currentPage.value,
            per_page: perPage.value
          };

          if (searchTerm.value) {
            params.search = searchTerm.value;
          }

          if (filters.bankId) {
            params.bank_id = filters.bankId;
          }

          if (filters.status) {
            params.status = filters.status;
          }

          if (filters.fromDate && filters.toDate) {
            params.from_date = filters.fromDate;
            params.to_date = filters.toDate;
          }

          const response = await axios.get('/api/accounting/bank-reconciliations', { params });
          reconciliations.value = response.data.data;

          // Handle pagination info
          totalRecords.value = response.data.total || reconciliations.value.length;
          totalPages.value = response.data.last_page || 1;
          currentPage.value = response.data.current_page || 1;
        } catch (error) {
          console.error('Error fetching reconciliations:', error);
          // TODO: Show error toast message
        } finally {
          isLoading.value = false;
        }
      };

      const fetchBankAccounts = async () => {
        try {
          const response = await axios.get('/api/accounting/bank-accounts');
          bankAccounts.value = response.data.data;
        } catch (error) {
          console.error('Error fetching bank accounts:', error);
          // TODO: Show error toast message
        }
      };

      const clearSearch = () => {
        searchTerm.value = '';
        fetchReconciliations();
      };

      const onPageChange = (page) => {
        currentPage.value = page;
        fetchReconciliations();
      };

      const formatDate = (dateString) => {
        if (!dateString) return '';
        const options = { year: 'numeric', month: 'short', day: 'numeric' };
        return new Date(dateString).toLocaleDateString(undefined, options);
      };

      const formatCurrency = (amount) => {
        return new Intl.NumberFormat('id-ID', {
          style: 'currency',
          currency: 'IDR',
          minimumFractionDigits: 0,
          maximumFractionDigits: 0
        }).format(amount || 0);
      };

      const getDifference = (reconciliation) => {
        return reconciliation.statement_balance - reconciliation.book_balance;
      };

      const confirmDelete = (reconciliation) => {
        selectedReconciliation.value = reconciliation;
        showDeleteModal.value = true;
      };

      const deleteReconciliation = async () => {
        if (!selectedReconciliation.value) return;

        try {
          await axios.delete(`/api/accounting/bank-reconciliations/${selectedReconciliation.value.reconciliation_id}`);
          fetchReconciliations();
          showDeleteModal.value = false;
          selectedReconciliation.value = null;
          // TODO: Show success toast message
        } catch (error) {
          console.error('Error deleting reconciliation:', error);
          // TODO: Show error toast message
        }
      };

      // Lifecycle hooks
      onMounted(() => {
        fetchBankAccounts();
        fetchReconciliations();
      });

      return {
        reconciliations,
        bankAccounts,
        isLoading,
        searchTerm,
        filters,
        currentPage,
        totalRecords,
        totalPages,
        paginationFrom,
        paginationTo,
        showDeleteModal,
        selectedReconciliation,
        fetchReconciliations,
        clearSearch,
        onPageChange,
        formatDate,
        formatCurrency,
        getDifference,
        confirmDelete,
        deleteReconciliation
      };
    }
  };
  </script>

  <style scoped>
  .bank-reconciliations-list {
    padding: 1rem 0;
  }

  .filter-group {
    min-width: 200px;
  }

  .badge {
    padding: 0.35rem 0.65rem;
    font-size: 0.75rem;
    font-weight: 500;
  }

  .font-weight-medium {
    font-weight: 500;
  }

  .empty-state {
    padding: 2rem 0;
    text-align: center;
  }

  .empty-icon {
    font-size: 3rem;
    color: var(--gray-300);
    margin-bottom: 1rem;
  }
  </style>
