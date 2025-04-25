<!-- src/views/accounting/AssetDepreciationList.vue -->
<template>
    <div class="asset-depreciation-list">
      <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
          <h2>Penyusutan Aset</h2>
          <router-link to="/accounting/depreciation/calculate" class="btn btn-primary">
            <i class="fas fa-calculator mr-2"></i> Hitung Penyusutan
          </router-link>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <!-- Filter and search -->
          <SearchFilter
            v-model:value="searchTerm"
            placeholder="Cari penyusutan..."
            @search="fetchDepreciations"
            @clear="clearSearch"
          >
            <template #filters>
              <div class="filter-group">
                <label>Periode</label>
                <select v-model="filters.periodId" class="form-control" @change="fetchDepreciations">
                  <option value="">Semua Periode</option>
                  <option v-for="period in periods" :key="period.period_id" :value="period.period_id">
                    {{ period.period_name }}
                  </option>
                </select>
              </div>
              <div class="filter-group">
                <label>Aset</label>
                <select v-model="filters.assetId" class="form-control" @change="fetchDepreciations">
                  <option value="">Semua Aset</option>
                  <option v-for="asset in assets" :key="asset.asset_id" :value="asset.asset_id">
                    {{ asset.name }}
                  </option>
                </select>
              </div>
              <div class="filter-group">
                <label>Tanggal</label>
                <div class="d-flex align-items-center">
                  <input
                    type="date"
                    v-model="filters.startDate"
                    class="form-control"
                    @change="fetchDepreciations"
                  />
                  <span class="mx-2">s/d</span>
                  <input
                    type="date"
                    v-model="filters.endDate"
                    class="form-control"
                    @change="fetchDepreciations"
                  />
                </div>
              </div>
            </template>
          </SearchFilter>

          <!-- Loading state -->
          <div v-if="isLoading" class="text-center py-5">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p class="mt-2">Memuat data penyusutan...</p>
          </div>

          <!-- Error state -->
          <div v-else-if="error" class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            {{ error }}
          </div>

          <!-- Empty state -->
          <div v-else-if="depreciations.length === 0" class="text-center py-5">
            <i class="fas fa-calculator fa-3x text-muted mb-3"></i>
            <h4>Tidak ada data penyusutan</h4>
            <p class="text-muted">Belum ada penyusutan aset yang dicatat</p>
            <router-link to="/accounting/depreciation/calculate" class="btn btn-primary mt-2">
              Hitung Penyusutan Baru
            </router-link>
          </div>

          <!-- Data table -->
          <div v-else class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Aset</th>
                  <th>Kode Aset</th>
                  <th>Tanggal Penyusutan</th>
                  <th>Periode</th>
                  <th class="text-right">Nilai Penyusutan</th>
                  <th class="text-right">Akumulasi Penyusutan</th>
                  <th class="text-right">Nilai Sisa</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="depreciation in depreciations" :key="depreciation.depreciation_id">
                  <td>{{ depreciation.fixed_asset?.name || '-' }}</td>
                  <td>{{ depreciation.fixed_asset?.asset_code || '-' }}</td>
                  <td>{{ formatDate(depreciation.depreciation_date) }}</td>
                  <td>{{ depreciation.accounting_period?.period_name || '-' }}</td>
                  <td class="text-right">{{ formatCurrency(depreciation.depreciation_amount) }}</td>
                  <td class="text-right">{{ formatCurrency(depreciation.accumulated_depreciation) }}</td>
                  <td class="text-right">{{ formatCurrency(depreciation.remaining_value) }}</td>
                  <td>
                    <div class="btn-group">
                      <router-link
                        :to="`/accounting/depreciation/${depreciation.depreciation_id}`"
                        class="btn btn-sm btn-info"
                        title="Detail"
                      >
                        <i class="fas fa-eye"></i>
                      </router-link>
                      <router-link
                        :to="`/accounting/depreciation/${depreciation.depreciation_id}/journal`"
                        class="btn btn-sm btn-primary"
                        title="Lihat Jurnal"
                      >
                        <i class="fas fa-book"></i>
                      </router-link>
                      <router-link
                        :to="`/accounting/depreciation/${depreciation.depreciation_id}/schedule`"
                        class="btn btn-sm btn-secondary"
                        title="Jadwal Penyusutan"
                      >
                        <i class="fas fa-calendar-alt"></i>
                      </router-link>
                      <button
                        v-if="isLatestDepreciation(depreciation)"
                        @click="confirmDelete(depreciation)"
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
        title="Hapus Penyusutan Aset"
        :message="`Apakah Anda yakin ingin menghapus penyusutan untuk aset '${selectedDepreciation?.fixed_asset?.name || 'ini'}'? Tindakan ini akan mempengaruhi nilai buku aset.`"
        confirm-button-text="Hapus"
        confirm-button-class="btn btn-danger"
        @confirm="deleteDepreciation"
        @close="showDeleteModal = false"
      />
    </div>
  </template>

  <script>
  import { ref, reactive, computed, onMounted } from 'vue';
  import axios from 'axios';

  export default {
    name: 'AssetDepreciationList',
    setup() {
      const depreciations = ref([]);
      const assets = ref([]);
      const periods = ref([]);
      const isLoading = ref(true);
      const error = ref(null);
      const searchTerm = ref('');
      const filters = reactive({
        assetId: '',
        periodId: '',
        startDate: '',
        endDate: ''
      });
      const currentPage = ref(1);
      const perPage = ref(10);
      const totalRecords = ref(0);
      const totalPages = ref(0);
      const showDeleteModal = ref(false);
      const selectedDepreciation = ref(null);

      // Computed properties for pagination
      const paginationFrom = computed(() => {
        if (totalRecords.value === 0) return 0;
        return (currentPage.value - 1) * perPage.value + 1;
      });

      const paginationTo = computed(() => {
        return Math.min(currentPage.value * perPage.value, totalRecords.value);
      });

      // Fetch asset depreciations
      const fetchDepreciations = async () => {
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

          if (filters.assetId) {
            params.asset_id = filters.assetId;
          }

          if (filters.periodId) {
            params.period_id = filters.periodId;
          }

          if (filters.startDate && filters.endDate) {
            params.from_date = filters.startDate;
            params.to_date = filters.endDate;
          }

          const response = await axios.get('/api/accounting/asset-depreciations', { params });

          depreciations.value = response.data.data || [];

          // Handle pagination data if available
          if (response.data.meta) {
            currentPage.value = response.data.meta.current_page;
            totalPages.value = response.data.meta.last_page;
            totalRecords.value = response.data.meta.total;
          } else {
            totalRecords.value = depreciations.value.length;
            totalPages.value = 1;
          }
        } catch (err) {
          console.error('Error fetching depreciations:', err);
          error.value = 'Gagal memuat data penyusutan. Silakan coba lagi nanti.';
        } finally {
          isLoading.value = false;
        }
      };

      // Fetch assets for filter dropdown
      const fetchAssets = async () => {
        try {
          const response = await axios.get('/api/accounting/fixed-assets');
          assets.value = response.data.data || [];
        } catch (err) {
          console.error('Error fetching assets:', err);
        }
      };

      // Fetch accounting periods for filter dropdown
      const fetchPeriods = async () => {
        try {
          const response = await axios.get('/api/accounting/accounting-periods');
          periods.value = response.data.data || [];
        } catch (err) {
          console.error('Error fetching periods:', err);
        }
      };

      // Handle page change
      const onPageChange = (page) => {
        currentPage.value = page;
        fetchDepreciations();
      };

      // Clear search
      const clearSearch = () => {
        searchTerm.value = '';
        fetchDepreciations();
      };

      // Format date
      const formatDate = (dateString) => {
        if (!dateString) return '-';
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

      // Check if depreciation is the latest for an asset
      const isLatestDepreciation = (depreciation) => {
        if (!depreciation || !depreciation.asset_id) return false;

        const assetDepreciations = depreciations.value.filter(
          d => d.asset_id === depreciation.asset_id
        );

        if (assetDepreciations.length === 0) return false;

        // Sort by depreciation date descending
        assetDepreciations.sort((a, b) =>
          new Date(b.depreciation_date) - new Date(a.depreciation_date)
        );

        // Check if this is the most recent depreciation for the asset
        return assetDepreciations[0].depreciation_id === depreciation.depreciation_id;
      };

      // Confirm delete
      const confirmDelete = (depreciation) => {
        selectedDepreciation.value = depreciation;
        showDeleteModal.value = true;
      };

      // Delete depreciation
      const deleteDepreciation = async () => {
        if (!selectedDepreciation.value) return;

        isLoading.value = true;
        try {
          await axios.delete(`/api/accounting/asset-depreciations/${selectedDepreciation.value.depreciation_id}`);
          // Remove from list
          depreciations.value = depreciations.value.filter(
            d => d.depreciation_id !== selectedDepreciation.value.depreciation_id
          );
          showDeleteModal.value = false;
          selectedDepreciation.value = null;

          // Optionally refresh the list
          fetchDepreciations();
        } catch (err) {
          console.error('Error deleting depreciation:', err);
          error.value = 'Gagal menghapus penyusutan. ' +
            (err.response?.data?.message || 'Silakan coba lagi nanti.');
        } finally {
          isLoading.value = false;
        }
      };

      // Lifecycle hooks
      onMounted(() => {
        fetchDepreciations();
        fetchAssets();
        fetchPeriods();
      });

      return {
        depreciations,
        assets,
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
        selectedDepreciation,
        fetchDepreciations,
        onPageChange,
        clearSearch,
        formatDate,
        formatCurrency,
        isLatestDepreciation,
        confirmDelete,
        deleteDepreciation
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

  .filter-group {
    min-width: 200px;
  }
  </style>
