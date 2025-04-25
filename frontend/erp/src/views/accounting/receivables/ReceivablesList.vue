<!-- src/views/accounting/receivables/ReceivablesList.vue -->
<template>
    <div class="receivables-list">
      <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
          <h2>Piutang Pelanggan</h2>
          <router-link to="/accounting/receivables/create" class="btn btn-primary">
            <i class="fas fa-plus mr-2"></i> Tambah Piutang
          </router-link>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <!-- Filter and search -->
          <SearchFilter
            v-model:value="searchTerm"
            placeholder="Cari no. invoice, pelanggan..."
            @search="fetchReceivables"
            @clear="clearSearch"
          >
            <template #filters>
              <div class="filter-group">
                <label>Status</label>
                <select v-model="filters.status" class="form-control" @change="fetchReceivables">
                  <option value="">Semua Status</option>
                  <option value="Open">Belum Lunas</option>
                  <option value="Paid">Lunas</option>
                  <option value="Overdue">Jatuh Tempo</option>
                </select>
              </div>
              <div class="filter-group">
                <label>Jatuh Tempo</label>
                <div class="d-flex">
                  <input
                    type="date"
                    v-model="filters.fromDate"
                    class="form-control"
                    @change="fetchReceivables"
                  />
                  <span class="mx-2 align-self-center">s/d</span>
                  <input
                    type="date"
                    v-model="filters.toDate"
                    class="form-control"
                    @change="fetchReceivables"
                  />
                </div>
              </div>
              <div class="filter-group">
                <label>Pelanggan</label>
                <select v-model="filters.customerId" class="form-control" @change="fetchReceivables">
                  <option value="">Semua Pelanggan</option>
                  <option v-for="customer in customers" :key="customer.customer_id" :value="customer.customer_id">
                    {{ customer.name }}
                  </option>
                </select>
              </div>
            </template>

            <template #actions>
              <div class="btn-group">
                <router-link to="/accounting/receivables/aging-report" class="btn btn-info">
                  <i class="fas fa-chart-bar mr-1"></i> Laporan Aging
                </router-link>
                <button class="btn btn-secondary" @click="resetFilters">
                  <i class="fas fa-redo mr-1"></i> Reset
                </button>
              </div>
            </template>
          </SearchFilter>

          <!-- Loading state -->
          <div v-if="isLoading" class="text-center py-5">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p class="mt-2">Memuat data piutang...</p>
          </div>

          <!-- Error state -->
          <div v-else-if="error" class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            {{ error }}
          </div>

          <!-- Empty state -->
          <div v-else-if="receivables.length === 0" class="text-center py-5">
            <i class="fas fa-file-invoice-dollar fa-3x text-muted mb-3"></i>
            <h4>Tidak ada piutang ditemukan</h4>
            <p class="text-muted">Tidak ada data piutang yang sesuai dengan kriteria pencarian</p>
          </div>

          <!-- Data table -->
          <div v-else class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th>No. Invoice</th>
                  <th>Pelanggan</th>
                  <th>Tanggal Jatuh Tempo</th>
                  <th>Jumlah</th>
                  <th>Terbayar</th>
                  <th>Sisa</th>
                  <th>Status</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="receivable in receivables" :key="receivable.receivable_id">
                  <td>
                    <router-link :to="`/accounting/receivables/${receivable.receivable_id}`" class="font-weight-bold">
                      {{ receivable.sales_invoice?.invoice_number || 'N/A' }}
                    </router-link>
                  </td>
                  <td>{{ receivable.customer?.name || 'N/A' }}</td>
                  <td :class="{ 'text-danger': isOverdue(receivable.due_date) }">
                    {{ formatDate(receivable.due_date) }}
                    <span v-if="isOverdue(receivable.due_date)" class="badge badge-danger ml-2">
                      Jatuh Tempo
                    </span>
                  </td>
                  <td class="text-right">{{ formatCurrency(receivable.amount) }}</td>
                  <td class="text-right">{{ formatCurrency(receivable.paid_amount) }}</td>
                  <td class="text-right font-weight-bold">{{ formatCurrency(receivable.balance) }}</td>
                  <td>
                    <span
                      :class="{
                        'badge': true,
                        'badge-success': receivable.status === 'Paid',
                        'badge-warning': receivable.status === 'Open' && !isOverdue(receivable.due_date),
                        'badge-danger': receivable.status === 'Open' && isOverdue(receivable.due_date)
                      }"
                    >
                      {{ getStatusLabel(receivable.status, receivable.due_date) }}
                    </span>
                  </td>
                  <td>
                    <div class="btn-group">
                      <router-link :to="`/accounting/receivables/${receivable.receivable_id}`" class="btn btn-sm btn-info" title="Detail">
                        <i class="fas fa-eye"></i>
                      </router-link>
                      <router-link
                        v-if="receivable.status !== 'Paid'"
                        :to="`/accounting/receivables/${receivable.receivable_id}/payment`"
                        class="btn btn-sm btn-success"
                        title="Bayar"
                      >
                        <i class="fas fa-money-bill-wave"></i>
                      </router-link>
                      <router-link
                        :to="`/accounting/customers/${receivable.customer_id}/statement`"
                        class="btn btn-sm btn-secondary"
                        title="Lihat Statement"
                      >
                        <i class="fas fa-file-alt"></i>
                      </router-link>
                    </div>
                  </td>
                </tr>
              </tbody>
              <tfoot>
                <tr class="bg-light">
                  <th colspan="3" class="text-right">Total:</th>
                  <th class="text-right">{{ formatCurrency(totalAmount) }}</th>
                  <th class="text-right">{{ formatCurrency(totalPaid) }}</th>
                  <th class="text-right">{{ formatCurrency(totalBalance) }}</th>
                  <th colspan="2"></th>
                </tr>
              </tfoot>
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
    </div>
  </template>

  <script>
  import { ref, reactive, computed, onMounted } from 'vue';
  import axios from 'axios';

  export default {
    name: 'ReceivablesList',
    setup() {
      const receivables = ref([]);
      const customers = ref([]);
      const isLoading = ref(true);
      const error = ref(null);
      const searchTerm = ref('');
      const filters = reactive({
        status: '',
        customerId: '',
        fromDate: '',
        toDate: ''
      });
      const currentPage = ref(1);
      const perPage = ref(15);
      const totalRecords = ref(0);
      const totalPages = ref(0);

      // Computed properties for totals
      const totalAmount = computed(() => {
        return receivables.value.reduce((sum, receivable) => sum + parseFloat(receivable.amount || 0), 0);
      });

      const totalPaid = computed(() => {
        return receivables.value.reduce((sum, receivable) => sum + parseFloat(receivable.paid_amount || 0), 0);
      });

      const totalBalance = computed(() => {
        return receivables.value.reduce((sum, receivable) => sum + parseFloat(receivable.balance || 0), 0);
      });

      // Pagination computed properties
      const paginationFrom = computed(() => {
        if (totalRecords.value === 0) return 0;
        return (currentPage.value - 1) * perPage.value + 1;
      });

      const paginationTo = computed(() => {
        return Math.min(currentPage.value * perPage.value, totalRecords.value);
      });

      // Fetch receivables from API
      const fetchReceivables = async () => {
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

          if (filters.customerId) {
            params.customer_id = filters.customerId;
          }

          if (filters.fromDate && filters.toDate) {
            params.from_date = filters.fromDate;
            params.to_date = filters.toDate;
          }

          const response = await axios.get('/api/accounting/customer-receivables', { params });

          if (response.data.data) {
            receivables.value = response.data.data;
          } else {
            receivables.value = response.data; // In case the API returns the array directly
          }

          // Handle pagination information
          if (response.data.meta) {
            totalRecords.value = response.data.meta.total;
            totalPages.value = response.data.meta.last_page;
            currentPage.value = response.data.meta.current_page;
          } else {
            // If the API doesn't return pagination metadata, assume all records are returned
            totalRecords.value = receivables.value.length;
            totalPages.value = 1;
          }
        } catch (err) {
          console.error('Error fetching receivables:', err);
          error.value = 'Gagal memuat data piutang. Silakan coba lagi nanti.';
        } finally {
          isLoading.value = false;
        }
      };

      // Fetch customers list for filter dropdown
      const fetchCustomers = async () => {
        try {
          const response = await axios.get('/api/sales/customers');
          customers.value = response.data.data || response.data;
        } catch (err) {
          console.error('Error fetching customers:', err);
        }
      };

      // Handle page change in pagination
      const onPageChange = (page) => {
        currentPage.value = page;
        fetchReceivables();
      };

      // Clear search
      const clearSearch = () => {
        searchTerm.value = '';
        fetchReceivables();
      };

      // Reset all filters
      const resetFilters = () => {
        searchTerm.value = '';
        filters.status = '';
        filters.customerId = '';
        filters.fromDate = '';
        filters.toDate = '';
        currentPage.value = 1;
        fetchReceivables();
      };

      // Format date
      const formatDate = (dateString) => {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', {
          day: 'numeric',
          month: 'long',
          year: 'numeric'
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

      // Check if date is overdue
      const isOverdue = (dateString) => {
        if (!dateString) return false;
        const dueDate = new Date(dateString);
        const today = new Date();
        return dueDate < today;
      };

      // Get status label
      const getStatusLabel = (status, dueDate) => {
        if (status === 'Paid') return 'Lunas';
        if (status === 'Open') {
          return isOverdue(dueDate) ? 'Jatuh Tempo' : 'Belum Lunas';
        }
        return status;
      };

      // Lifecycle hooks
      onMounted(() => {
        fetchCustomers();
        fetchReceivables();
      });

      return {
        receivables,
        customers,
        isLoading,
        error,
        searchTerm,
        filters,
        currentPage,
        totalRecords,
        totalPages,
        paginationFrom,
        paginationTo,
        totalAmount,
        totalPaid,
        totalBalance,
        fetchReceivables,
        onPageChange,
        clearSearch,
        resetFilters,
        formatDate,
        formatCurrency,
        isOverdue,
        getStatusLabel
      };
    }
  };
  </script>

  <style scoped>
  .page-header {
    margin-bottom: 1.5rem;
  }

  .badge {
    padding: 0.5em 0.75em;
    font-size: 0.75em;
  }

  .btn-group .btn {
    margin-right: 0.25rem;
  }

  .btn-group .btn:last-child {
    margin-right: 0;
  }
  </style>
