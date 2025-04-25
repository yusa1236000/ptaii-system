<!-- src/views/accounting/FixedAssetReport.vue -->
<template>
    <div class="fixed-asset-report">
      <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
          <h2>Fixed Asset Register</h2>
          <div class="action-buttons">
            <router-link to="/accounting/fixed-assets" class="btn btn-outline-secondary mr-2">
              <i class="fas fa-arrow-left mr-1"></i> Back to Assets
            </router-link>
            <button class="btn btn-primary" @click="printReport">
              <i class="fas fa-print mr-1"></i> Print Report
            </button>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <!-- Report Filters -->
          <div class="report-filters mb-4">
            <div class="row">
              <div class="col-md-3">
                <div class="form-group">
                  <label for="categoryFilter">Category</label>
                  <select id="categoryFilter" v-model="filters.category" class="form-control" @change="applyFilters">
                    <option value="">All Categories</option>
                    <option v-for="category in categories" :key="category" :value="category">
                      {{ category }}
                    </option>
                  </select>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group">
                  <label for="statusFilter">Status</label>
                  <select id="statusFilter" v-model="filters.status" class="form-control" @change="applyFilters">
                    <option value="">All Status</option>
                    <option value="Active">Active</option>
                    <option value="Disposed">Disposed</option>
                    <option value="Inactive">Inactive</option>
                    <option value="Under Maintenance">Under Maintenance</option>
                  </select>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group">
                  <label for="acquisitionYearFilter">Acquisition Year</label>
                  <select id="acquisitionYearFilter" v-model="filters.acquisitionYear" class="form-control" @change="applyFilters">
                    <option value="">All Years</option>
                    <option v-for="year in acquisitionYears" :key="year" :value="year">
                      {{ year }}
                    </option>
                  </select>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group">
                  <label for="assetCodeFilter">Search by Asset Code</label>
                  <input
                    id="assetCodeFilter"
                    v-model="filters.assetCode"
                    type="text"
                    class="form-control"
                    placeholder="Enter asset code"
                    @input="applyFilters"
                  />
                </div>
              </div>
            </div>

            <div class="d-flex justify-content-end">
              <button @click="resetFilters" class="btn btn-outline-secondary">
                <i class="fas fa-redo mr-1"></i> Reset Filters
              </button>
              <button @click="exportToCSV" class="btn btn-success ml-2">
                <i class="fas fa-file-csv mr-1"></i> Export to CSV
              </button>
            </div>
          </div>

          <!-- Report Header -->
          <div class="report-header text-center mb-4 print-only-block">
            <h2 class="mb-1">Fixed Asset Register</h2>
            <p class="mb-1">{{ companyName }}</p>
            <p class="mb-1">As of {{ formatDate(new Date()) }}</p>
            <hr />
          </div>

          <!-- Loading state -->
          <div v-if="isLoading" class="text-center py-5">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p class="mt-2">Loading asset data...</p>
          </div>

          <!-- Error state -->
          <div v-else-if="error" class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            {{ error }}
          </div>

          <!-- Empty state -->
          <div v-else-if="filteredAssets.length === 0" class="text-center py-5">
            <i class="fas fa-search fa-3x text-muted mb-3"></i>
            <h4>No assets found</h4>
            <p class="text-muted">Try adjusting your filters</p>
          </div>

          <!-- Report Content -->
          <div v-else>
            <!-- Report Summary -->
            <div class="report-summary mb-4">
              <div class="row">
                <div class="col-md-3">
                  <div class="summary-card">
                    <div class="summary-value">{{ filteredAssets.length }}</div>
                    <div class="summary-label">Total Assets</div>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="summary-card">
                    <div class="summary-value">{{ formatCurrency(calculateTotalAcquisitionCost()) }}</div>
                    <div class="summary-label">Total Cost</div>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="summary-card">
                    <div class="summary-value">{{ formatCurrency(calculateTotalCurrentValue()) }}</div>
                    <div class="summary-label">Current Book Value</div>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="summary-card">
                    <div class="summary-value">{{ formatCurrency(calculateTotalDepreciation()) }}</div>
                    <div class="summary-label">Total Depreciation</div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Asset Categories Summary -->
            <div class="category-summary mb-4">
              <h4 class="mb-3">Assets by Category</h4>
              <div class="table-responsive">
                <table class="table table-sm table-bordered">
                  <thead class="bg-light">
                    <tr>
                      <th>Category</th>
                      <th class="text-center">Count</th>
                      <th class="text-right">Total Cost</th>
                      <th class="text-right">Current Value</th>
                      <th class="text-right">Depreciation</th>
                      <th class="text-right">Depreciation %</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(summary, category) in categorySummary" :key="category">
                      <td>{{ category }}</td>
                      <td class="text-center">{{ summary.count }}</td>
                      <td class="text-right">{{ formatCurrency(summary.acquisitionCost) }}</td>
                      <td class="text-right">{{ formatCurrency(summary.currentValue) }}</td>
                      <td class="text-right">{{ formatCurrency(summary.depreciation) }}</td>
                      <td class="text-right">{{ Math.round(summary.depreciationPercentage) }}%</td>
                    </tr>
                  </tbody>
                  <tfoot class="font-weight-bold">
                    <tr>
                      <td>Total</td>
                      <td class="text-center">{{ filteredAssets.length }}</td>
                      <td class="text-right">{{ formatCurrency(calculateTotalAcquisitionCost()) }}</td>
                      <td class="text-right">{{ formatCurrency(calculateTotalCurrentValue()) }}</td>
                      <td class="text-right">{{ formatCurrency(calculateTotalDepreciation()) }}</td>
                      <td class="text-right">{{ Math.round(calculateTotalDepreciationPercentage()) }}%</td>
                    </tr>
                  </tfoot>
                </table>
              </div>
            </div>

            <!-- Asset Details Table -->
            <div class="asset-details">
              <h4 class="mb-3">Asset Details</h4>
              <div class="table-responsive">
                <table class="table table-sm table-bordered">
                  <thead class="bg-light">
                    <tr>
                      <th>Asset Code</th>
                      <th>Name</th>
                      <th>Category</th>
                      <th>Acquisition Date</th>
                      <th class="text-right">Acquisition Cost</th>
                      <th class="text-right">Current Value</th>
                      <th class="text-right">Depr. Rate</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="asset in filteredAssets" :key="asset.asset_id">
                      <td>{{ asset.asset_code }}</td>
                      <td>{{ asset.name }}</td>
                      <td>{{ asset.category }}</td>
                      <td>{{ formatDate(asset.acquisition_date) }}</td>
                      <td class="text-right">{{ formatCurrency(asset.acquisition_cost) }}</td>
                      <td class="text-right">{{ formatCurrency(asset.current_value) }}</td>
                      <td class="text-right">{{ asset.depreciation_rate }}%</td>
                      <td>{{ asset.status }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- Report Footer -->
          <div class="report-footer mt-4 print-only-block">
            <hr />
            <div class="d-flex justify-content-between">
              <div>Generated on: {{ formatDate(new Date()) }}</div>
              <div>Page 1 of 1</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </template>

  <script>
  import axios from 'axios';
  import { ref, computed, onMounted } from 'vue';

  export default {
    name: 'FixedAssetReport',
    setup() {
      // State
      const assets = ref([]);
      const filteredAssets = ref([]);
      const categories = ref([]);
      const acquisitionYears = ref([]);
      const isLoading = ref(true);
      const error = ref(null);
      const companyName = ref('Your Company Name'); // Replace with actual company name or make dynamic

      // Filters
      const filters = ref({
        category: '',
        status: '',
        acquisitionYear: '',
        assetCode: ''
      });

      // Load assets
      const loadAssets = async () => {
        isLoading.value = true;
        error.value = null;

        try {
          const response = await axios.get('/api/accounting/fixed-assets');
          assets.value = response.data.data || [];

          // Apply initial filtering
          applyFilters();

          // Extract categories and years for filter dropdowns
          extractCategories();
          extractAcquisitionYears();
        } catch (err) {
          console.error('Error loading assets:', err);
          error.value = 'Failed to load asset data. Please try again later.';
        } finally {
          isLoading.value = false;
        }
      };

      // Extract unique categories for filter dropdown
      const extractCategories = () => {
        const uniqueCategories = new Set();
        assets.value.forEach(asset => {
          if (asset.category) {
            uniqueCategories.add(asset.category);
          }
        });
        categories.value = Array.from(uniqueCategories).sort();
      };

      // Extract unique acquisition years for filter dropdown
      const extractAcquisitionYears = () => {
        const uniqueYears = new Set();
        assets.value.forEach(asset => {
          if (asset.acquisition_date) {
            const year = new Date(asset.acquisition_date).getFullYear();
            uniqueYears.add(year);
          }
        });
        acquisitionYears.value = Array.from(uniqueYears).sort((a, b) => b - a); // Descending order
      };

      // Apply filters
      const applyFilters = () => {
        filteredAssets.value = assets.value.filter(asset => {
          // Category filter
          if (filters.value.category && asset.category !== filters.value.category) {
            return false;
          }

          // Status filter
          if (filters.value.status && asset.status !== filters.value.status) {
            return false;
          }

          // Acquisition year filter
          if (filters.value.acquisitionYear) {
            const assetYear = new Date(asset.acquisition_date).getFullYear();
            if (assetYear != filters.value.acquisitionYear) {
              return false;
            }
          }

          // Asset code filter
          if (filters.value.assetCode) {
            return asset.asset_code.toLowerCase().includes(filters.value.assetCode.toLowerCase());
          }

          return true;
        });
      };

      // Reset filters
      const resetFilters = () => {
        filters.value.category = '';
        filters.value.status = '';
        filters.value.acquisitionYear = '';
        filters.value.assetCode = '';
        applyFilters();
      };

      // Calculate total acquisition cost
      const calculateTotalAcquisitionCost = () => {
        return filteredAssets.value.reduce((sum, asset) => sum + Number(asset.acquisition_cost || 0), 0);
      };

      // Calculate total current value
      const calculateTotalCurrentValue = () => {
        return filteredAssets.value.reduce((sum, asset) => sum + Number(asset.current_value || 0), 0);
      };

      // Calculate total depreciation
      const calculateTotalDepreciation = () => {
        return calculateTotalAcquisitionCost() - calculateTotalCurrentValue();
      };

      // Calculate total depreciation percentage
      const calculateTotalDepreciationPercentage = () => {
        const totalCost = calculateTotalAcquisitionCost();
        if (totalCost === 0) return 0;

        return (calculateTotalDepreciation() / totalCost) * 100;
      };

      // Category summary computed property
      const categorySummary = computed(() => {
        const summary = {};

        filteredAssets.value.forEach(asset => {
          const category = asset.category || 'Uncategorized';

          if (!summary[category]) {
            summary[category] = {
              count: 0,
              acquisitionCost: 0,
              currentValue: 0,
              depreciation: 0,
              depreciationPercentage: 0
            };
          }

          summary[category].count++;
          summary[category].acquisitionCost += Number(asset.acquisition_cost || 0);
          summary[category].currentValue += Number(asset.current_value || 0);
        });

        // Calculate depreciation and percentage
        Object.keys(summary).forEach(category => {
          summary[category].depreciation = summary[category].acquisitionCost - summary[category].currentValue;

          if (summary[category].acquisitionCost > 0) {
            summary[category].depreciationPercentage = (summary[category].depreciation / summary[category].acquisitionCost) * 100;
          }
        });

        return summary;
      });

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

      // Print report
      const printReport = () => {
        window.print();
      };

      // Export to CSV
      const exportToCSV = () => {
        if (filteredAssets.value.length === 0) return;

        // Define columns for CSV
        const columns = [
          'Asset Code',
          'Name',
          'Category',
          'Acquisition Date',
          'Acquisition Cost',
          'Current Value',
          'Depreciation',
          'Depreciation %',
          'Status'
        ];

        // Create CSV content
        let csvContent = columns.join(',') + '\n';

        // Add data rows
        filteredAssets.value.forEach(asset => {
          const acquisitionCost = Number(asset.acquisition_cost || 0);
          const currentValue = Number(asset.current_value || 0);
          const depreciation = acquisitionCost - currentValue;
          const depreciationPercentage = acquisitionCost > 0 ? (depreciation / acquisitionCost) * 100 : 0;

          // Format values and escape quotes for CSV
          const values = [
            `"${asset.asset_code}"`,
            `"${asset.name.replace(/"/g, '""')}"`,
            `"${asset.category || ''}"`,
            `"${formatDate(asset.acquisition_date)}"`,
            acquisitionCost,
            currentValue,
            depreciation,
            `${Math.round(depreciationPercentage)}%`,
            `"${asset.status}"`
          ];

          csvContent += values.join(',') + '\n';
        });

        // Create and download CSV file
        const encodedUri = encodeURI('data:text/csv;charset=utf-8,' + csvContent);
        const link = document.createElement('a');
        link.setAttribute('href', encodedUri);
        link.setAttribute('download', `asset_register_${new Date().toISOString().slice(0, 10)}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
      };

      // Lifecycle hooks
      onMounted(() => {
        loadAssets();
      });

      return {
        assets,
        filteredAssets,
        categories,
        acquisitionYears,
        isLoading,
        error,
        filters,
        companyName,
        categorySummary,
        formatDate,
        formatCurrency,
        applyFilters,
        resetFilters,
        calculateTotalAcquisitionCost,
        calculateTotalCurrentValue,
        calculateTotalDepreciation,
        calculateTotalDepreciationPercentage,
        printReport,
        exportToCSV
      };
    }
  };
  </script>

  <style scoped>
  .fixed-asset-report {
    max-width: 100%;
  }

  .report-filters {
    background-color: var(--gray-50);
    padding: 1.5rem;
    border-radius: 0.5rem;
    margin-bottom: 2rem;
  }

  .summary-card {
    background-color: white;
    border: 1px solid var(--gray-200);
    border-radius: 0.5rem;
    padding: 1.25rem;
    text-align: center;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  }

  .summary-value {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--gray-800);
    margin-bottom: 0.5rem;
  }

  .summary-label {
    font-size: 0.875rem;
    color: var(--gray-600);
  }

  .text-right {
    text-align: right;
  }

  .text-center {
    text-align: center;
  }

  /* Print styles */
  @media print {
    .report-filters,
    .action-buttons,
    .btn {
      display: none !important;
    }

    .card {
      border: none !important;
      box-shadow: none !important;
    }

    .card-body {
      padding: 0 !important;
    }

    .report-header,
    .report-footer {
      display: block !important;
    }

    .print-only-block {
      display: block !important;
    }
  }

  /* Hide the report header and footer when not printing */
  .print-only-block {
    display: none;
  }
  </style>
