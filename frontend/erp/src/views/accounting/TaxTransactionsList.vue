<!-- src/views/accounting/TaxTransactionsList.vue -->
<template>
    <div class="tax-transactions-container">
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h3 class="card-title">Transaksi Pajak</h3>
          <router-link to="/accounting/tax-transactions/create" class="btn btn-primary">
            <i class="fas fa-plus mr-2"></i> Tambah Transaksi
          </router-link>
        </div>
        <div class="card-body">
          <SearchFilter
            v-model:value="searchQuery"
            placeholder="Cari nomor transaksi pajak, jenis, atau status..."
            @search="loadTransactions(1)"
            @clear="clearFilters"
          >
            <template #filters>
              <div class="filter-group">
                <label for="tax-type">Jenis Pajak</label>
                <select id="tax-type" v-model="filters.taxType" class="form-control" @change="loadTransactions(1)">
                  <option value="">Semua Jenis</option>
                  <option value="PPN">PPN</option>
                  <option value="PPH21">PPH21</option>
                  <option value="PPH23">PPH23</option>
                  <option value="PPH4(2)">PPH4(2)</option>
                  <option value="Other">Lainnya</option>
                </select>
              </div>
              <div class="filter-group">
                <label for="status">Status</label>
                <select id="status" v-model="filters.status" class="form-control" @change="loadTransactions(1)">
                  <option value="">Semua Status</option>
                  <option value="Pending">Pending</option>
                  <option value="Filed">Filed</option>
                  <option value="Paid">Paid</option>
                </select>
              </div>
              <div class="filter-group">
                <label for="date-range">Periode</label>
                <div class="d-flex gap-2">
                  <input
                    type="date"
                    v-model="filters.startDate"
                    class="form-control"
                    placeholder="Dari Tanggal"
                  />
                  <input
                    type="date"
                    v-model="filters.endDate"
                    class="form-control"
                    placeholder="Sampai Tanggal"
                  />
                  <button class="btn btn-secondary" @click="loadTransactions(1)">
                    <i class="fas fa-filter"></i>
                  </button>
                </div>
              </div>
            </template>
            <template #actions>
              <router-link to="/accounting/tax-transactions/summary" class="btn btn-secondary mr-2">
                <i class="fas fa-chart-bar mr-1"></i> Laporan Ringkasan
              </router-link>
              <router-link to="/accounting/tax-transactions/filing" class="btn btn-success">
                <i class="fas fa-file-alt mr-1"></i> Persiapan Pelaporan
              </router-link>
            </template>
          </SearchFilter>

          <!-- Loading state -->
          <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Memuat data transaksi pajak...</p>
          </div>

          <!-- Empty state -->
          <div v-else-if="transactions.length === 0" class="empty-state py-5">
            <i class="fas fa-file-invoice-dollar fa-3x text-muted mb-3"></i>
            <h4>Tidak ada transaksi pajak</h4>
            <p>Belum ada transaksi pajak yang tersedia. Klik tombol 'Tambah Transaksi' untuk membuat transaksi baru.</p>
          </div>

          <!-- Data table -->
          <div v-else class="table-container">
            <DataTable
              :columns="columns"
              :items="transactions"
              :is-loading="loading"
              key-field="tax_transaction_id"
            >
              <template #tax_type="{ value }">
                <span :class="getTaxTypeBadgeClass(value)">{{ value }}</span>
              </template>

              <template #tax_period="{ value }">
                {{ formatDate(value) }}
              </template>

              <template #tax_amount="{ value }">
                {{ formatCurrency(value) }}
              </template>

              <template #status="{ value }">
                <span :class="getStatusBadgeClass(value)">{{ value }}</span>
              </template>

              <template #actions="{ item }">
                <div class="d-flex gap-1">
                  <router-link :to="`/accounting/tax-transactions/${item.tax_transaction_id}`" class="btn btn-sm btn-info">
                    <i class="fas fa-eye"></i>
                  </router-link>
                  <router-link
                    v-if="item.status !== 'Filed' && item.status !== 'Paid'"
                    :to="`/accounting/tax-transactions/${item.tax_transaction_id}/edit`"
                    class="btn btn-sm btn-primary"
                  >
                    <i class="fas fa-edit"></i>
                  </router-link>
                  <button
                    v-if="item.status === 'Pending'"
                    @click="confirmFileTax(item)"
                    class="btn btn-sm btn-success"
                  >
                    <i class="fas fa-check"></i>
                  </button>
                  <button
                    v-if="item.status === 'Filed'"
                    @click="confirmPayTax(item)"
                    class="btn btn-sm btn-warning"
                  >
                    <i class="fas fa-money-bill-wave"></i>
                  </button>
                  <button
                    v-if="item.status === 'Pending'"
                    @click="confirmDeleteTax(item)"
                    class="btn btn-sm btn-danger"
                  >
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </template>
            </DataTable>
          </div>

          <!-- Pagination -->
          <PaginationComponent
            v-if="!loading && paginationInfo.totalPages > 0"
            :current-page="paginationInfo.currentPage"
            :total-pages="paginationInfo.totalPages"
            :from="paginationInfo.from"
            :to="paginationInfo.to"
            :total="paginationInfo.total"
            @page-changed="loadTransactions"
          />
        </div>
      </div>

      <!-- Confirmation Modal -->
      <ConfirmationModal
        v-if="showConfirmationModal"
        :title="confirmationModal.title"
        :message="confirmationModal.message"
        :confirm-button-text="confirmationModal.confirmButtonText"
        :confirm-button-class="confirmationModal.confirmButtonClass"
        @confirm="confirmationModal.onConfirm"
        @close="showConfirmationModal = false"
      />
    </div>
  </template>

  <script>
  import { ref, reactive, onMounted } from 'vue';
  import axios from 'axios';

  export default {
    name: 'TaxTransactionsList',
    setup() {
      const transactions = ref([]);
      const loading = ref(false);
      const searchQuery = ref('');
      const showConfirmationModal = ref(false);
      const confirmationModal = reactive({
        title: '',
        message: '',
        confirmButtonText: '',
        confirmButtonClass: '',
        onConfirm: () => {}
      });

      const filters = reactive({
        taxType: '',
        status: '',
        startDate: '',
        endDate: ''
      });

      const columns = [
        { key: 'tax_number', label: 'Nomor Pajak', sortable: true },
        { key: 'tax_type', label: 'Jenis Pajak', sortable: true },
        { key: 'tax_period', label: 'Periode Pajak', sortable: true },
        { key: 'due_date', label: 'Jatuh Tempo', sortable: true },
        { key: 'tax_amount', label: 'Jumlah', sortable: true },
        { key: 'status', label: 'Status', sortable: true }
      ];

      const paginationInfo = reactive({
        currentPage: 1,
        totalPages: 0,
        from: 0,
        to: 0,
        total: 0
      });

      const loadTransactions = async (page = 1) => {
        loading.value = true;
        try {
          const response = await axios.get('/api/accounting/tax-transactions', {
            params: {
              page,
              search: searchQuery.value,
              tax_type: filters.taxType,
              status: filters.status,
              start_date: filters.startDate,
              end_date: filters.endDate
            }
          });

          const data = response.data;
          transactions.value = data.data;

          paginationInfo.currentPage = data.current_page;
          paginationInfo.totalPages = data.last_page;
          paginationInfo.from = data.from;
          paginationInfo.to = data.to;
          paginationInfo.total = data.total;
        } catch (error) {
          console.error('Failed to load tax transactions:', error);
          alert('Failed to load tax transactions. Please try again later.');
        } finally {
          loading.value = false;
        }
      };

      const clearFilters = () => {
        searchQuery.value = '';
        filters.taxType = '';
        filters.status = '';
        filters.startDate = '';
        filters.endDate = '';
        loadTransactions(1);
      };

      const formatDate = (dateString) => {
        const options = { year: 'numeric', month: 'short', day: 'numeric' };
        return new Date(dateString).toLocaleDateString('id-ID', options);
      };

      const formatCurrency = (amount) => {
        return new Intl.NumberFormat('id-ID', {
          style: 'currency',
          currency: 'IDR',
          minimumFractionDigits: 0,
          maximumFractionDigits: 0,
        }).format(amount);
      };

      const getTaxTypeBadgeClass = (taxType) => {
        const classes = {
          'PPN': 'badge badge-primary',
          'PPH21': 'badge badge-info',
          'PPH23': 'badge badge-warning',
          'PPH4(2)': 'badge badge-secondary',
          'Other': 'badge badge-dark'
        };
        return classes[taxType] || 'badge badge-dark';
      };

      const getStatusBadgeClass = (status) => {
        const classes = {
          'Pending': 'badge badge-warning',
          'Filed': 'badge badge-info',
          'Paid': 'badge badge-success'
        };
        return classes[status] || 'badge badge-secondary';
      };

      const confirmFileTax = (item) => {
        showConfirmationModal.value = true;
        confirmationModal.title = 'Konfirmasi Pelaporan Pajak';
        confirmationModal.message = `Apakah Anda yakin ingin melaporkan pajak <strong>${item.tax_number}</strong>?`;
        confirmationModal.confirmButtonText = 'Laporkan';
        confirmationModal.confirmButtonClass = 'btn btn-success';
        confirmationModal.onConfirm = () => fileTax(item.tax_transaction_id);
      };

      const fileTax = async (id) => {
        try {
          await axios.post(`/api/accounting/tax-transactions/${id}/file`);
          showConfirmationModal.value = false;
          loadTransactions(paginationInfo.currentPage);
          alert('Pajak berhasil dilaporkan!');
        } catch (error) {
          console.error('Failed to file tax:', error);
          alert('Failed to file tax. Please try again later.');
        }
      };

      const confirmPayTax = (item) => {
        showConfirmationModal.value = true;
        confirmationModal.title = 'Konfirmasi Pembayaran Pajak';
        confirmationModal.message = `Apakah Anda yakin ingin menandai pajak <strong>${item.tax_number}</strong> sebagai sudah dibayar?`;
        confirmationModal.confirmButtonText = 'Bayar';
        confirmationModal.confirmButtonClass = 'btn btn-warning';
        confirmationModal.onConfirm = () => payTax(item.tax_transaction_id);
      };

      const payTax = async (id) => {
        try {
          await axios.post(`/api/accounting/tax-transactions/${id}/pay`);
          showConfirmationModal.value = false;
          loadTransactions(paginationInfo.currentPage);
          alert('Pajak berhasil ditandai sebagai dibayar!');
        } catch (error) {
          console.error('Failed to pay tax:', error);
          alert('Failed to pay tax. Please try again later.');
        }
      };

      const confirmDeleteTax = (item) => {
        showConfirmationModal.value = true;
        confirmationModal.title = 'Konfirmasi Hapus Pajak';
        confirmationModal.message = `Apakah Anda yakin ingin menghapus transaksi pajak <strong>${item.tax_number}</strong>? Tindakan ini tidak dapat dibatalkan.`;
        confirmationModal.confirmButtonText = 'Hapus';
        confirmationModal.confirmButtonClass = 'btn btn-danger';
        confirmationModal.onConfirm = () => deleteTax(item.tax_transaction_id);
      };

      const deleteTax = async (id) => {
        try {
          await axios.delete(`/api/accounting/tax-transactions/${id}`);
          showConfirmationModal.value = false;
          loadTransactions(paginationInfo.currentPage);
          alert('Transaksi pajak berhasil dihapus!');
        } catch (error) {
          console.error('Failed to delete tax transaction:', error);
          alert('Failed to delete tax transaction. Please try again later.');
        }
      };

      onMounted(() => {
        loadTransactions(1);
      });

      return {
        transactions,
        loading,
        columns,
        searchQuery,
        filters,
        paginationInfo,
        showConfirmationModal,
        confirmationModal,
        loadTransactions,
        clearFilters,
        formatDate,
        formatCurrency,
        getTaxTypeBadgeClass,
        getStatusBadgeClass,
        confirmFileTax,
        confirmPayTax,
        confirmDeleteTax
      };
    }
  };
  </script>

  <style scoped>
  .tax-transactions-container {
    margin-bottom: 2rem;
  }

  .badge {
    padding: 0.375rem 0.75rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
  }

  .badge-primary {
    background-color: var(--primary-bg);
    color: var(--primary-color);
  }

  .badge-info {
    background-color: #e0f7fa;
    color: #0288d1;
  }

  .badge-success {
    background-color: var(--success-bg);
    color: var(--success-color);
  }

  .badge-warning {
    background-color: var(--warning-bg);
    color: var(--warning-color);
  }

  .badge-danger {
    background-color: var(--danger-bg);
    color: var(--danger-color);
  }

  .badge-secondary {
    background-color: var(--gray-100);
    color: var(--gray-700);
  }

  .badge-dark {
    background-color: #e0e0e0;
    color: #424242;
  }

  .gap-1 {
    gap: 0.25rem;
  }

  .gap-2 {
    gap: 0.5rem;
  }
  </style>
