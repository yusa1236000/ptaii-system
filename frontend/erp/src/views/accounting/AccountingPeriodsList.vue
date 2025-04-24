<!-- src/views/accounting/AccountingPeriodsList.vue -->
<template>
    <div class="accounting-periods">
      <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
          <h2>Periode Akuntansi</h2>
          <router-link to="/accounting/periods/create" class="btn btn-primary">
            <i class="fas fa-plus mr-2"></i> Tambah Periode
          </router-link>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <!-- Filter and search -->
          <SearchFilter
            v-model:value="searchTerm"
            placeholder="Cari periode..."
            @search="fetchAccountingPeriods"
            @clear="clearSearch"
          >
            <template #filters>
              <div class="filter-group">
                <label>Status</label>
                <select v-model="filters.status" class="form-control" @change="fetchAccountingPeriods">
                  <option value="">Semua Status</option>
                  <option value="Open">Terbuka</option>
                  <option value="Closed">Tertutup</option>
                  <option value="Locked">Terkunci</option>
                </select>
              </div>
              <div class="filter-group">
                <label>Tahun</label>
                <select v-model="filters.year" class="form-control" @change="fetchAccountingPeriods">
                  <option value="">Semua Tahun</option>
                  <option v-for="year in availableYears" :key="year" :value="year">
                    {{ year }}
                  </option>
                </select>
              </div>
            </template>
          </SearchFilter>

          <!-- Loading state -->
          <div v-if="isLoading" class="text-center py-5">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p class="mt-2">Memuat periode akuntansi...</p>
          </div>

          <!-- Error state -->
          <div v-else-if="error" class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            {{ error }}
          </div>

          <!-- Empty state -->
          <div v-else-if="periods.length === 0" class="text-center py-5">
            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
            <h4>Tidak ada periode ditemukan</h4>
            <p class="text-muted">Tambahkan periode akuntansi baru untuk memulai</p>
          </div>

          <!-- Data table -->
          <div v-else class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Nama Periode</th>
                  <th>Tanggal Mulai</th>
                  <th>Tanggal Akhir</th>
                  <th>Status</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="period in periods" :key="period.period_id">
                  <td>{{ period.period_name }}</td>
                  <td>{{ formatDate(period.start_date) }}</td>
                  <td>{{ formatDate(period.end_date) }}</td>
                  <td>
                    <span
                      :class="{
                        'badge': true,
                        'badge-success': period.status === 'Open',
                        'badge-warning': period.status === 'Closing',
                        'badge-danger': period.status === 'Closed',
                        'badge-secondary': period.status === 'Locked'
                      }"
                    >
                      {{ getStatusLabel(period.status) }}
                    </span>
                  </td>
                  <td>
                    <div class="btn-group">
                      <router-link
                        :to="`/accounting/periods/${period.period_id}`"
                        class="btn btn-sm btn-info"
                        title="Detail"
                      >
                        <i class="fas fa-eye"></i>
                      </router-link>
                      <router-link
                        v-if="period.status === 'Open'"
                        :to="`/accounting/periods/${period.period_id}/edit`"
                        class="btn btn-sm btn-primary"
                        title="Edit"
                      >
                        <i class="fas fa-edit"></i>
                      </router-link>
                      <router-link
                        v-if="period.status === 'Open'"
                        :to="`/accounting/periods/${period.period_id}/close`"
                        class="btn btn-sm btn-warning"
                        title="Tutup Periode"
                      >
                        <i class="fas fa-lock"></i>
                      </router-link>
                      <button
                        v-if="period.status === 'Open' && !hasDependencies(period)"
                        @click="confirmDelete(period)"
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
        title="Hapus Periode Akuntansi"
        :message="`Apakah Anda yakin ingin menghapus periode '${selectedPeriod?.period_name}'? Tindakan ini tidak dapat dibatalkan.`"
        confirm-button-text="Hapus"
        confirm-button-class="btn btn-danger"
        @confirm="deletePeriod"
        @close="showDeleteModal = false"
      />
    </div>
  </template>

  <script>
  import axios from 'axios';
  import { ref, reactive, computed, onMounted } from 'vue';

  export default {
    name: 'AccountingPeriodsList',
    setup() {
      const periods = ref([]);
      const isLoading = ref(true);
      const error = ref(null);
      const searchTerm = ref('');
      const filters = reactive({
        status: '',
        year: ''
      });
      const currentPage = ref(1);
      const perPage = ref(10);
      const totalRecords = ref(0);
      const totalPages = ref(0);
      const showDeleteModal = ref(false);
      const selectedPeriod = ref(null);
      const availableYears = ref([]);

      // Pagination computed properties
      const paginationFrom = computed(() => {
        if (totalRecords.value === 0) return 0;
        return (currentPage.value - 1) * perPage.value + 1;
      });

      const paginationTo = computed(() => {
        return Math.min(currentPage.value * perPage.value, totalRecords.value);
      });

      // Fetch accounting periods from API
      const fetchAccountingPeriods = async () => {
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

          if (filters.year) {
            params.year = filters.year;
          }

          const response = await axios.get('/api/accounting/accounting-periods', { params });
          periods.value = response.data.data || [];

          // Handle pagination information if available
          if (response.data.meta) {
            totalRecords.value = response.data.meta.total;
            totalPages.value = response.data.meta.last_page;
          } else {
            // If the API doesn't return pagination metadata, assume all records are returned
            totalRecords.value = periods.value.length;
            totalPages.value = 1;
          }

          // Extract available years from periods
          extractAvailableYears();
        } catch (err) {
          console.error('Error fetching accounting periods:', err);
          error.value = 'Gagal memuat data periode. Silakan coba lagi nanti.';
        } finally {
          isLoading.value = false;
        }
      };

      // Extract unique years from periods for filter dropdown
      const extractAvailableYears = () => {
        const years = new Set();
        periods.value.forEach(period => {
          const startYear = new Date(period.start_date).getFullYear();
          const endYear = new Date(period.end_date).getFullYear();
          years.add(startYear);
          if (startYear !== endYear) {
            years.add(endYear);
          }
        });
        availableYears.value = Array.from(years).sort((a, b) => b - a); // Sort descending
      };

      // Handle page change in pagination
      const onPageChange = (page) => {
        currentPage.value = page;
        fetchAccountingPeriods();
      };

      // Clear search
      const clearSearch = () => {
        searchTerm.value = '';
        fetchAccountingPeriods();
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

      // Get status label
      const getStatusLabel = (status) => {
        switch (status) {
          case 'Open':
            return 'Terbuka';
          case 'Closing':
            return 'Proses Penutupan';
          case 'Closed':
            return 'Tertutup';
          case 'Locked':
            return 'Terkunci';
          default:
            return status;
        }
      };

      // Check if period has dependencies
      const hasDependencies = () => {
        // This is a simplified check. In a real application, you might want to
        // check if this period has related journal entries, budgets, etc.
        return false; // For now, assuming no dependencies
      };

      // Confirm delete
      const confirmDelete = (period) => {
        selectedPeriod.value = period;
        showDeleteModal.value = true;
      };

      // Delete period
      const deletePeriod = async () => {
        if (!selectedPeriod.value) return;

        isLoading.value = true;
        try {
          await axios.delete(`/api/accounting/accounting-periods/${selectedPeriod.value.period_id}`);
          // Remove from list
          periods.value = periods.value.filter(p => p.period_id !== selectedPeriod.value.period_id);
          showDeleteModal.value = false;
          selectedPeriod.value = null;

          // Refresh the list
          fetchAccountingPeriods();
        } catch (err) {
          console.error('Error deleting period:', err);
          error.value = 'Gagal menghapus periode. ' + (err.response?.data?.message || 'Silakan coba lagi nanti.');
        } finally {
          isLoading.value = false;
        }
      };

      // Lifecycle hooks
      onMounted(() => {
        fetchAccountingPeriods();
      });

      return {
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
        showDeleteModal,
        selectedPeriod,
        availableYears,
        fetchAccountingPeriods,
        onPageChange,
        clearSearch,
        formatDate,
        getStatusLabel,
        hasDependencies,
        confirmDelete,
        deletePeriod
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
  </style>
