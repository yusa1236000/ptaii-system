<!-- src/views/accounting/BudgetsList.vue -->
<template>
    <div class="budget-list">
      <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
          <h2>Anggaran (Budget)</h2>
          <router-link to="/accounting/budgets/create" class="btn btn-primary">
            <i class="fas fa-plus mr-2"></i> Tambah Anggaran
          </router-link>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <!-- Filter and search -->
          <SearchFilter
            v-model:value="searchTerm"
            placeholder="Cari anggaran..."
            @search="fetchBudgets"
            @clear="clearSearch"
          >
            <template #filters>
              <div class="filter-group">
                <label>Periode</label>
                <select v-model="filters.period_id" class="form-control" @change="fetchBudgets">
                  <option value="">Semua Periode</option>
                  <option v-for="period in periods" :key="period.period_id" :value="period.period_id">
                    {{ period.period_name }}
                  </option>
                </select>
              </div>
              <div class="filter-group">
                <label>Jenis Akun</label>
                <select v-model="filters.account_type" class="form-control" @change="fetchBudgets">
                  <option value="">Semua Jenis</option>
                  <option value="Revenue">Pendapatan</option>
                  <option value="Expense">Biaya</option>
                  <option value="Asset">Aset</option>
                  <option value="Liability">Kewajiban</option>
                  <option value="Equity">Ekuitas</option>
                </select>
              </div>
            </template>
            <template #actions>
              <router-link to="/accounting/budgets/variance" class="btn btn-info">
                <i class="fas fa-chart-bar mr-1"></i> Analisis Varian
              </router-link>
            </template>
          </SearchFilter>

          <!-- Loading state -->
          <div v-if="isLoading" class="text-center py-5">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p class="mt-2">Memuat data anggaran...</p>
          </div>

          <!-- Error state -->
          <div v-else-if="error" class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            {{ error }}
          </div>

          <!-- Empty state -->
          <div v-else-if="budgets.length === 0" class="text-center py-5">
            <i class="fas fa-file-invoice-dollar fa-3x text-muted mb-3"></i>
            <h4>Tidak ada anggaran ditemukan</h4>
            <p class="text-muted">Tambahkan anggaran baru untuk memulai pengelolaan keuangan</p>
            <router-link to="/accounting/budgets/create" class="btn btn-primary">
              <i class="fas fa-plus mr-1"></i> Tambah Anggaran
            </router-link>
          </div>

          <!-- Data table -->
          <div v-else>
            <div class="table-responsive">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Periode</th>
                    <th>Akun</th>
                    <th>Jenis Akun</th>
                    <th class="text-right">Anggaran</th>
                    <th class="text-right">Aktual</th>
                    <th class="text-right">Varian</th>
                    <th class="text-right">% Varian</th>
                    <th>Status</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="budget in budgets" :key="budget.budget_id">
                    <td>{{ budget.accounting_period?.period_name || '-' }}</td>
                    <td>
                      <div class="d-flex flex-column">
                        <span class="font-weight-medium">{{ budget.chart_of_account?.name || '-' }}</span>
                        <small class="text-muted">{{ budget.chart_of_account?.account_code || '-' }}</small>
                      </div>
                    </td>
                    <td>
                      <span :class="getAccountTypeClass(budget.chart_of_account?.account_type)">
                        {{ formatAccountType(budget.chart_of_account?.account_type) }}
                      </span>
                    </td>
                    <td class="text-right">{{ formatCurrency(budget.budgeted_amount) }}</td>
                    <td class="text-right">{{ formatCurrency(budget.actual_amount) }}</td>
                    <td class="text-right" :class="getVarianceClass(budget.variance)">
                      {{ formatCurrency(budget.variance) }}
                    </td>
                    <td class="text-right" :class="getVarianceClass(budget.variance)">
                      {{ calculateVariancePercentage(budget) }}%
                    </td>
                    <td>
                      <span :class="getBudgetStatusClass(budget)">
                        {{ getBudgetStatus(budget) }}
                      </span>
                    </td>
                    <td>
                      <div class="btn-group">
                        <router-link
                          :to="`/accounting/budgets/${budget.budget_id}`"
                          class="btn btn-sm btn-info"
                          title="Detail"
                        >
                          <i class="fas fa-eye"></i>
                        </router-link>
                        <router-link
                          :to="`/accounting/budgets/${budget.budget_id}/edit`"
                          class="btn btn-sm btn-primary"
                          title="Edit"
                        >
                          <i class="fas fa-edit"></i>
                        </router-link>
                        <button
                          @click="confirmDelete(budget)"
                          class="btn btn-sm btn-danger"
                          title="Hapus"
                        >
                          <i class="fas fa-trash"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Summary -->
            <div class="budget-summary mt-4">
              <div class="row">
                <div class="col-md-6">
                  <div class="card bg-light">
                    <div class="card-body p-3">
                      <h5 class="card-title">Ringkasan Anggaran</h5>
                      <div class="summary-item d-flex justify-content-between border-bottom pb-2 mb-2">
                        <span>Total Anggaran:</span>
                        <span class="font-weight-bold">{{ formatCurrency(totalBudgeted) }}</span>
                      </div>
                      <div class="summary-item d-flex justify-content-between border-bottom pb-2 mb-2">
                        <span>Total Aktual:</span>
                        <span class="font-weight-bold">{{ formatCurrency(totalActual) }}</span>
                      </div>
                      <div class="summary-item d-flex justify-content-between">
                        <span>Total Varian:</span>
                        <span
                          class="font-weight-bold"
                          :class="getVarianceClass(totalVariance)"
                        >
                          {{ formatCurrency(totalVariance) }} ({{ calculateTotalVariancePercentage() }}%)
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
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

      <!-- Delete confirmation modal -->
      <ConfirmationModal
        v-if="showDeleteModal"
        title="Hapus Anggaran"
        :message="`Apakah Anda yakin ingin menghapus anggaran untuk akun '${selectedBudget?.chart_of_account?.name || 'ini'}'?`"
        confirm-button-text="Hapus"
        confirm-button-class="btn btn-danger"
        @confirm="deleteBudget"
        @close="showDeleteModal = false"
      />
    </div>
  </template>

  <script>
  import axios from 'axios';
  import { ref, reactive, computed, onMounted } from 'vue';

  export default {
    name: 'BudgetsList',
    setup() {
      const budgets = ref([]);
      const periods = ref([]);
      const isLoading = ref(true);
      const error = ref(null);
      const searchTerm = ref('');
      const filters = reactive({
        period_id: '',
        account_type: ''
      });
      const currentPage = ref(1);
      const perPage = ref(10);
      const totalRecords = ref(0);
      const totalPages = ref(0);
      const showDeleteModal = ref(false);
      const selectedBudget = ref(null);

      // Pagination computed properties
      const paginationFrom = computed(() => {
        if (totalRecords.value === 0) return 0;
        return (currentPage.value - 1) * perPage.value + 1;
      });

      const paginationTo = computed(() => {
        return Math.min(currentPage.value * perPage.value, totalRecords.value);
      });

      // Totals computed properties
      const totalBudgeted = computed(() => {
        return budgets.value.reduce((sum, budget) => sum + (budget.budgeted_amount || 0), 0);
      });

      const totalActual = computed(() => {
        return budgets.value.reduce((sum, budget) => sum + (budget.actual_amount || 0), 0);
      });

      const totalVariance = computed(() => {
        return budgets.value.reduce((sum, budget) => sum + (budget.variance || 0), 0);
      });

      // Fetch budgets from API
      const fetchBudgets = async () => {
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

          if (filters.period_id) {
            params.period_id = filters.period_id;
          }

          if (filters.account_type) {
            params.account_type = filters.account_type;
          }

          const response = await axios.get('/api/accounting/budgets', { params });

          if (response.data.data) {
            budgets.value = response.data.data;
          } else {
            budgets.value = response.data;
          }

          // Handle pagination information if available
          if (response.data.meta) {
            totalRecords.value = response.data.meta.total;
            totalPages.value = response.data.meta.last_page;
            currentPage.value = response.data.meta.current_page;
          } else {
            // If the API doesn't return pagination metadata, assume all records are returned
            totalRecords.value = budgets.value.length;
            totalPages.value = 1;
          }
        } catch (err) {
          console.error('Error fetching budgets:', err);
          error.value = 'Gagal memuat data anggaran. Silakan coba lagi nanti.';
        } finally {
          isLoading.value = false;
        }
      };

      // Fetch accounting periods
      const fetchPeriods = async () => {
        try {
          const response = await axios.get('/api/accounting/accounting-periods');
          periods.value = response.data.data || response.data;
        } catch (err) {
          console.error('Error fetching periods:', err);
        }
      };

      // Handle page change in pagination
      const onPageChange = (page) => {
        currentPage.value = page;
        fetchBudgets();
      };

      // Clear search
      const clearSearch = () => {
        searchTerm.value = '';
        fetchBudgets();
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

      // Format account type
      const formatAccountType = (type) => {
        switch (type) {
          case 'Asset': return 'Aset';
          case 'Liability': return 'Kewajiban';
          case 'Equity': return 'Ekuitas';
          case 'Revenue': return 'Pendapatan';
          case 'Expense': return 'Biaya';
          default: return type || '-';
        }
      };

      // Get account type css class
      const getAccountTypeClass = (type) => {
        switch (type) {
          case 'Asset': return 'badge badge-primary';
          case 'Liability': return 'badge badge-warning';
          case 'Equity': return 'badge badge-info';
          case 'Revenue': return 'badge badge-success';
          case 'Expense': return 'badge badge-danger';
          default: return 'badge badge-secondary';
        }
      };

      // Get variance css class
      const getVarianceClass = (variance) => {
        if (!variance) return '';
        return variance < 0 ? 'text-danger' : 'text-success';
      };

      // Calculate variance percentage
      const calculateVariancePercentage = (budget) => {
        if (!budget.budgeted_amount || budget.budgeted_amount === 0) return '0.00';
        const percentage = (budget.variance / budget.budgeted_amount) * 100;
        return percentage.toFixed(2);
      };

      // Calculate total variance percentage
      const calculateTotalVariancePercentage = () => {
        if (!totalBudgeted.value || totalBudgeted.value === 0) return '0.00';
        const percentage = (totalVariance.value / totalBudgeted.value) * 100;
        return percentage.toFixed(2);
      };

      // Get budget status
      const getBudgetStatus = (budget) => {
        if (!budget.actual_amount) return 'Belum Realisasi';

        const variance = budget.variance || 0;
        const variancePercentage = Math.abs(variance) / (budget.budgeted_amount || 1) * 100;

        if (variancePercentage <= 5) return 'Sesuai Target';
        if (variance > 0) return 'Di Atas Target';
        return 'Di Bawah Target';
      };

      // Get budget status css class
      const getBudgetStatusClass = (budget) => {
        const status = getBudgetStatus(budget);
        switch (status) {
          case 'Sesuai Target': return 'badge badge-success';
          case 'Di Atas Target': return 'badge badge-info';
          case 'Di Bawah Target': return 'badge badge-warning';
          default: return 'badge badge-secondary';
        }
      };

      // Confirm delete
      const confirmDelete = (budget) => {
        selectedBudget.value = budget;
        showDeleteModal.value = true;
      };

      // Delete budget
      const deleteBudget = async () => {
        if (!selectedBudget.value) return;

        isLoading.value = true;

        try {
          await axios.delete(`/api/accounting/budgets/${selectedBudget.value.budget_id}`);

          // Remove from list
          budgets.value = budgets.value.filter(b => b.budget_id !== selectedBudget.value.budget_id);
          showDeleteModal.value = false;
          selectedBudget.value = null;

          // If last item on page is deleted, go to previous page (except if on first page)
          if (budgets.value.length === 0 && currentPage.value > 1) {
            currentPage.value--;
            fetchBudgets();
          }
        } catch (err) {
          console.error('Error deleting budget:', err);
          error.value = 'Gagal menghapus anggaran. Silakan coba lagi nanti.';
        } finally {
          isLoading.value = false;
        }
      };

      // Lifecycle hooks
      onMounted(() => {
        fetchPeriods();
        fetchBudgets();
      });

      return {
        budgets,
        periods,
        isLoading,
        error,
        searchTerm,
        filters,
        currentPage,
        totalRecords,
        totalPages,
        paginationFrom,
        paginationTo,
        totalBudgeted,
        totalActual,
        totalVariance,
        showDeleteModal,
        selectedBudget,
        fetchBudgets,
        onPageChange,
        clearSearch,
        formatCurrency,
        formatAccountType,
        getAccountTypeClass,
        getVarianceClass,
        calculateVariancePercentage,
        calculateTotalVariancePercentage,
        getBudgetStatus,
        getBudgetStatusClass,
        confirmDelete,
        deleteBudget
      };
    }
  };
  </script>

  <style scoped>
  .budget-list {
    margin-bottom: 2rem;
  }

  .badge {
    padding: 0.4em 0.6em;
    font-size: 0.75em;
  }

  .summary-item {
    font-size: 0.95rem;
  }
  </style>
