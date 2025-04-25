<!-- src/views/accounting/FixedAssetsList.vue -->
<template>
    <div class="fixed-assets-list">
      <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
          <h2>Fixed Assets Management</h2>
          <router-link to="/accounting/fixed-assets/create" class="btn btn-primary">
            <i class="fas fa-plus mr-2"></i> Add New Asset
          </router-link>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <!-- Filter and search -->
          <SearchFilter
            v-model:value="searchTerm"
            placeholder="Search assets..."
            @search="fetchAssets"
            @clear="clearSearch"
          >
            <template #filters>
              <div class="filter-group">
                <label>Category</label>
                <select v-model="filters.category" class="form-control" @change="fetchAssets">
                  <option value="">All Categories</option>
                  <option v-for="category in categories" :key="category" :value="category">
                    {{ category }}
                  </option>
                </select>
              </div>
              <div class="filter-group">
                <label>Status</label>
                <select v-model="filters.status" class="form-control" @change="fetchAssets">
                  <option value="">All Status</option>
                  <option value="Active">Active</option>
                  <option value="Disposed">Disposed</option>
                  <option value="Inactive">Inactive</option>
                  <option value="Under Maintenance">Under Maintenance</option>
                </select>
              </div>
            </template>
            <template #actions>
              <router-link to="/accounting/fixed-assets/report" class="btn btn-info mr-2">
                <i class="fas fa-file-alt mr-1"></i> Asset Register
              </router-link>
              <button class="btn btn-outline-secondary" @click="resetFilters">
                <i class="fas fa-redo mr-1"></i> Reset
              </button>
            </template>
          </SearchFilter>

          <!-- Loading state -->
          <div v-if="isLoading" class="text-center py-5">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p class="mt-2">Loading assets...</p>
          </div>

          <!-- Error state -->
          <div v-else-if="error" class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            {{ error }}
          </div>

          <!-- Empty state -->
          <div v-else-if="assets.length === 0" class="text-center py-5">
            <i class="fas fa-building fa-3x text-muted mb-3"></i>
            <h4>No assets found</h4>
            <p class="text-muted">Add a new asset to get started</p>
          </div>

          <!-- Data table -->
          <div v-else class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Asset Code</th>
                  <th>Name</th>
                  <th>Category</th>
                  <th>Acquisition Date</th>
                  <th>Cost</th>
                  <th>Current Value</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="asset in assets" :key="asset.asset_id">
                  <td>{{ asset.asset_code }}</td>
                  <td>{{ asset.name }}</td>
                  <td>{{ asset.category }}</td>
                  <td>{{ formatDate(asset.acquisition_date) }}</td>
                  <td class="text-right">{{ formatCurrency(asset.acquisition_cost) }}</td>
                  <td class="text-right">{{ formatCurrency(asset.current_value) }}</td>
                  <td>
                    <span
                      :class="{
                        'badge': true,
                        'badge-success': asset.status === 'Active',
                        'badge-danger': asset.status === 'Disposed',
                        'badge-secondary': asset.status === 'Inactive',
                        'badge-warning': asset.status === 'Under Maintenance'
                      }"
                    >
                      {{ asset.status }}
                    </span>
                  </td>
                  <td>
                    <div class="btn-group">
                      <router-link
                        :to="`/accounting/fixed-assets/${asset.asset_id}`"
                        class="btn btn-sm btn-info"
                        title="View Details"
                      >
                        <i class="fas fa-eye"></i>
                      </router-link>
                      <router-link
                        :to="`/accounting/fixed-assets/${asset.asset_id}/edit`"
                        class="btn btn-sm btn-primary"
                        title="Edit"
                      >
                        <i class="fas fa-edit"></i>
                      </router-link>
                      <button
                        v-if="asset.status !== 'Disposed'"
                        @click="confirmDelete(asset)"
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

      <!-- Delete confirmation modal -->
      <ConfirmationModal
        v-if="showDeleteModal"
        title="Delete Asset"
        :message="`Are you sure you want to delete the asset '${selectedAsset?.name}'? This action cannot be undone.`"
        confirm-button-text="Delete"
        confirm-button-class="btn btn-danger"
        @confirm="deleteAsset"
        @close="showDeleteModal = false"
      />
    </div>
  </template>

  <script>
  import axios from 'axios';
  import { ref, reactive, computed, onMounted } from 'vue';

  export default {
    name: 'FixedAssetsList',
    setup() {
      const assets = ref([]);
      const isLoading = ref(true);
      const error = ref(null);
      const searchTerm = ref('');
      const categories = ref([]);
      const filters = reactive({
        category: '',
        status: ''
      });
      const currentPage = ref(1);
      const perPage = ref(10);
      const totalRecords = ref(0);
      const totalPages = ref(0);
      const showDeleteModal = ref(false);
      const selectedAsset = ref(null);

      // Pagination computed properties
      const paginationFrom = computed(() => {
        if (totalRecords.value === 0) return 0;
        return (currentPage.value - 1) * perPage.value + 1;
      });

      const paginationTo = computed(() => {
        return Math.min(currentPage.value * perPage.value, totalRecords.value);
      });

      // Fetch assets from API
      const fetchAssets = async () => {
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

          if (filters.category) {
            params.category = filters.category;
          }

          if (filters.status) {
            params.status = filters.status;
          }

          const response = await axios.get('/api/accounting/fixed-assets', { params });
          assets.value = response.data.data || [];

          // Handle pagination information if available
          if (response.data.meta) {
            totalRecords.value = response.data.meta.total;
            totalPages.value = response.data.meta.last_page;
          } else {
            // If the API doesn't return pagination metadata, assume all records are returned
            totalRecords.value = assets.value.length;
            totalPages.value = 1;
          }

          // Extract unique categories for filter dropdown
          extractCategories();
        } catch (err) {
          console.error('Error fetching assets:', err);
          error.value = 'Failed to load assets. Please try again later.';
        } finally {
          isLoading.value = false;
        }
      };

      // Extract unique categories from assets for filter dropdown
      const extractCategories = () => {
        const uniqueCategories = new Set();
        assets.value.forEach(asset => {
          if (asset.category) {
            uniqueCategories.add(asset.category);
          }
        });
        categories.value = Array.from(uniqueCategories).sort();
      };

      // Handle page change in pagination
      const onPageChange = (page) => {
        currentPage.value = page;
        fetchAssets();
      };

      // Clear search
      const clearSearch = () => {
        searchTerm.value = '';
        fetchAssets();
      };

      // Reset filters
      const resetFilters = () => {
        searchTerm.value = '';
        filters.category = '';
        filters.status = '';
        currentPage.value = 1;
        fetchAssets();
      };

      // Format date for display
      const formatDate = (dateString) => {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
          year: 'numeric',
          month: 'short',
          day: 'numeric'
        });
      };

      // Format currency
      const formatCurrency = (value) => {
        return new Intl.NumberFormat('id-ID', {
          style: 'currency',
          currency: 'IDR',
          minimumFractionDigits: 0,
          maximumFractionDigits: 0
        }).format(value || 0);
      };

      // Confirm delete
      const confirmDelete = (asset) => {
        selectedAsset.value = asset;
        showDeleteModal.value = true;
      };

      // Delete asset
      const deleteAsset = async () => {
        if (!selectedAsset.value) return;

        isLoading.value = true;
        try {
          await axios.delete(`/api/accounting/fixed-assets/${selectedAsset.value.asset_id}`);
          assets.value = assets.value.filter(a => a.asset_id !== selectedAsset.value.asset_id);
          showDeleteModal.value = false;
          selectedAsset.value = null;

          // Refresh the list
          fetchAssets();
        } catch (err) {
          console.error('Error deleting asset:', err);
          error.value = 'Failed to delete asset. ' + (err.response?.data?.message || 'Please try again later.');
        } finally {
          isLoading.value = false;
        }
      };

      // Lifecycle hooks
      onMounted(() => {
        fetchAssets();
      });

      return {
        assets,
        isLoading,
        error,
        searchTerm,
        categories,
        filters,
        currentPage,
        totalRecords,
        totalPages,
        paginationFrom,
        paginationTo,
        showDeleteModal,
        selectedAsset,
        fetchAssets,
        onPageChange,
        clearSearch,
        resetFilters,
        formatDate,
        formatCurrency,
        confirmDelete,
        deleteAsset
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

  .text-right {
    text-align: right;
  }
  </style>
