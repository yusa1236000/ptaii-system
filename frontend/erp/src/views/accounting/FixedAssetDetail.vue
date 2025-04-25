<!-- src/views/accounting/FixedAssetDetail.vue -->
<template>
    <div class="fixed-asset-detail">
      <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
          <h2>Asset Details</h2>
          <div class="action-buttons">
            <router-link to="/accounting/fixed-assets" class="btn btn-outline-secondary mr-2">
              <i class="fas fa-arrow-left mr-1"></i> Back
            </router-link>
            <button class="btn btn-info mr-2" @click="printAssetDetails">
              <i class="fas fa-print mr-1"></i> Print
            </button>
            <router-link
              v-if="asset && asset.status !== 'Disposed'"
              :to="`/accounting/fixed-assets/${assetId}/edit`"
              class="btn btn-primary"
            >
              <i class="fas fa-edit mr-1"></i> Edit
            </router-link>
          </div>
        </div>
      </div>

      <div v-if="isLoading" class="text-center py-5">
        <i class="fas fa-spinner fa-spin fa-2x"></i>
        <p class="mt-2">Loading asset details...</p>
      </div>

      <div v-else-if="error" class="alert alert-danger" role="alert">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        {{ error }}
      </div>

      <div v-else-if="!asset" class="alert alert-warning" role="alert">
        <i class="fas fa-exclamation-circle mr-2"></i>
        Asset not found
      </div>

      <div v-else class="row">
        <!-- Asset Info Card -->
        <div class="col-md-6 mb-4">
          <div class="card h-100">
            <div class="card-header">
              <h3 class="card-title">Asset Information</h3>
            </div>
            <div class="card-body">
              <div class="asset-header mb-3">
                <div class="d-flex align-items-center">
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
                  <span class="asset-category ml-2">{{ asset.category }}</span>
                </div>
                <h2 class="asset-name mt-2">{{ asset.name }}</h2>
                <div class="asset-code">Code: {{ asset.asset_code }}</div>
              </div>

              <table class="table table-details">
                <tbody>
                  <tr>
                    <th>Acquisition Date</th>
                    <td>{{ formatDate(asset.acquisition_date) }}</td>
                  </tr>
                  <tr>
                    <th>Acquisition Cost</th>
                    <td>{{ formatCurrency(asset.acquisition_cost) }}</td>
                  </tr>
                  <tr>
                    <th>Current Book Value</th>
                    <td>{{ formatCurrency(asset.current_value) }}</td>
                  </tr>
                  <tr>
                    <th>Depreciation Rate</th>
                    <td>{{ asset.depreciation_rate }}% per year</td>
                  </tr>
                  <tr>
                    <th>Useful Life</th>
                    <td>{{ calculateUsefulLife() }} years</td>
                  </tr>
                  <tr>
                    <th>Value Reduction</th>
                    <td>{{ formatCurrency(asset.acquisition_cost - asset.current_value) }} ({{ calculateDepreciationPercentage() }}%)</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Asset Depreciation Overview Card -->
        <div class="col-md-6 mb-4">
          <div class="card h-100">
            <div class="card-header">
              <h3 class="card-title">Depreciation Overview</h3>
            </div>
            <div class="card-body">
              <div v-if="asset.status === 'Disposed'" class="alert alert-danger mb-3">
                <i class="fas fa-exclamation-circle mr-2"></i>
                This asset has been disposed and is no longer being depreciated.
              </div>

              <div class="depreciation-chart-container mb-4">
                <div class="d-flex justify-content-between mb-2">
                  <strong>Depreciation Progress</strong>
                  <span>{{ calculateDepreciationPercentage() }}% Depreciated</span>
                </div>
                <div class="progress">
                  <div
                    class="progress-bar"
                    role="progressbar"
                    :style="{ width: calculateDepreciationPercentage() + '%' }"
                    :class="{
                      'bg-danger': calculateDepreciationPercentage() > 75,
                      'bg-warning': calculateDepreciationPercentage() > 50 && calculateDepreciationPercentage() <= 75,
                      'bg-info': calculateDepreciationPercentage() > 25 && calculateDepreciationPercentage() <= 50,
                      'bg-success': calculateDepreciationPercentage() <= 25
                    }"
                  >
                    {{ calculateDepreciationPercentage() }}%
                  </div>
                </div>
              </div>

              <div class="depreciation-summary">
                <div class="summary-item">
                  <div class="summary-label">Monthly Depreciation</div>
                  <div class="summary-value">{{ formatCurrency(calculateMonthlyDepreciation()) }}</div>
                </div>
                <div class="summary-item">
                  <div class="summary-label">Annual Depreciation</div>
                  <div class="summary-value">{{ formatCurrency(calculateAnnualDepreciation()) }}</div>
                </div>
                <div class="summary-item">
                  <div class="summary-label">Remaining Book Value</div>
                  <div class="summary-value">{{ formatCurrency(asset.current_value) }}</div>
                </div>
                <div class="summary-item">
                  <div class="summary-label">Estimated Full Depreciation Date</div>
                  <div class="summary-value">{{ calculateFullDepreciationDate() }}</div>
                </div>
              </div>

              <div class="mt-4">
                <button
                  class="btn btn-primary btn-sm"
                  @click="showCalculateDepreciationModal"
                  :disabled="asset.status === 'Disposed'"
                >
                  <i class="fas fa-calculator mr-1"></i> Calculate Depreciation
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Depreciation History Card -->
        <div class="col-12 mb-4">
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h3 class="card-title">Depreciation History</h3>
            </div>
            <div class="card-body">
              <div v-if="isLoadingDepreciations" class="text-center py-4">
                <i class="fas fa-spinner fa-spin"></i>
                <p class="mt-2">Loading depreciation history...</p>
              </div>
              <div v-else-if="depreciations.length === 0" class="text-center py-4">
                <i class="fas fa-calculator fa-2x text-muted mb-3"></i>
                <p>No depreciation records found for this asset</p>
                <div v-if="asset.status === 'Active'" class="mt-3">
                  <button class="btn btn-outline-primary" @click="showCalculateDepreciationModal">
                    <i class="fas fa-calculator mr-1"></i> Calculate First Depreciation
                  </button>
                </div>
              </div>
              <div v-else class="table-responsive">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>Period</th>
                      <th>Date</th>
                      <th>Depreciation Amount</th>
                      <th>Accumulated Depreciation</th>
                      <th>Remaining Value</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="depreciation in depreciations" :key="depreciation.depreciation_id">
                      <td>{{ depreciation.accounting_period ? depreciation.accounting_period.period_name : '-' }}</td>
                      <td>{{ formatDate(depreciation.depreciation_date) }}</td>
                      <td class="text-right">{{ formatCurrency(depreciation.depreciation_amount) }}</td>
                      <td class="text-right">{{ formatCurrency(depreciation.accumulated_depreciation) }}</td>
                      <td class="text-right">{{ formatCurrency(depreciation.remaining_value) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Calculate Depreciation Modal -->
      <ConfirmationModal
        v-if="showDepreciationModal"
        title="Calculate Depreciation"
        :message="'Calculate depreciation for ' + (asset ? asset.name : '') + '?'"
      >
        <template #default>
          <div class="form-group mt-3">
            <label for="periodId">Accounting Period</label>
            <select id="periodId" v-model="depreciationForm.period_id" class="form-control" required>
              <option value="">Select Accounting Period</option>
              <option v-for="period in accountingPeriods" :key="period.period_id" :value="period.period_id">
                {{ period.period_name }}
              </option>
            </select>
          </div>
          <div class="form-group">
            <div class="custom-control custom-checkbox">
              <input type="checkbox" class="custom-control-input" id="createJournalEntry" v-model="depreciationForm.create_journal_entry">
              <label class="custom-control-label" for="createJournalEntry">Create Journal Entry</label>
            </div>
          </div>
          <div v-if="depreciationForm.create_journal_entry">
            <div class="form-group">
              <label for="depreciationExpenseAccount">Depreciation Expense Account</label>
              <select id="depreciationExpenseAccount" v-model="depreciationForm.depreciation_expense_account_id" class="form-control" required>
                <option value="">Select Account</option>
                <option v-for="account in expenseAccounts" :key="account.account_id" :value="account.account_id">
                  {{ account.account_code }} - {{ account.name }}
                </option>
              </select>
            </div>
            <div class="form-group">
              <label for="accumulatedDepreciationAccount">Accumulated Depreciation Account</label>
              <select id="accumulatedDepreciationAccount" v-model="depreciationForm.accumulated_depreciation_account_id" class="form-control" required>
                <option value="">Select Account</option>
                <option v-for="account in assetAccounts" :key="account.account_id" :value="account.account_id">
                  {{ account.account_code }} - {{ account.name }}
                </option>
              </select>
            </div>
          </div>
          <div v-if="depreciationError" class="alert alert-danger mt-3">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            {{ depreciationError }}
          </div>
        </template>
        <template #footer>
          <div class="d-flex justify-content-end">
            <button class="btn btn-secondary mr-2" @click="closeDepreciationModal">Cancel</button>
            <button
              class="btn btn-primary"
              @click="calculateDepreciation"
              :disabled="isCalculatingDepreciation || !depreciationForm.period_id || (depreciationForm.create_journal_entry && (!depreciationForm.depreciation_expense_account_id || !depreciationForm.accumulated_depreciation_account_id))"
            >
              <i v-if="isCalculatingDepreciation" class="fas fa-spinner fa-spin mr-1"></i>
              <span v-else><i class="fas fa-calculator mr-1"></i></span>
              {{ isCalculatingDepreciation ? 'Processing...' : 'Calculate' }}
            </button>
          </div>
        </template>
      </ConfirmationModal>
    </div>
  </template>

  <script>
  import axios from 'axios';
  import { ref, onMounted } from 'vue';
  import { useRoute } from 'vue-router';

  export default {
    name: 'FixedAssetDetail',
    setup() {
      const route = useRoute();
      const assetId = ref(route.params.id);

      // State
      const asset = ref(null);
      const depreciations = ref([]);
      const accountingPeriods = ref([]);
      const expenseAccounts = ref([]);
      const assetAccounts = ref([]);
      const isLoading = ref(true);
      const isLoadingDepreciations = ref(true);
      const error = ref(null);
      const showDepreciationModal = ref(false);
      const isCalculatingDepreciation = ref(false);
      const depreciationError = ref(null);

      // Depreciation form
      const depreciationForm = ref({
        period_id: '',
        create_journal_entry: true,
        depreciation_expense_account_id: '',
        accumulated_depreciation_account_id: ''
      });

      // Load asset details
      const loadAsset = async () => {
        isLoading.value = true;
        error.value = null;

        try {
          const response = await axios.get(`/api/accounting/fixed-assets/${assetId.value}`);
          asset.value = response.data.data;

          // Load depreciation history
          await loadDepreciations();

          // Load accounting periods and accounts for depreciation calculation
          await Promise.all([
            loadAccountingPeriods(),
            loadAccounts()
          ]);
        } catch (err) {
          console.error('Error loading asset:', err);
          error.value = 'Failed to load asset details. Please try again later.';
        } finally {
          isLoading.value = false;
        }
      };

      // Load asset depreciation history
      const loadDepreciations = async () => {
        isLoadingDepreciations.value = true;

        try {
          const response = await axios.get(`/api/accounting/asset-depreciations`, {
            params: { asset_id: assetId.value }
          });
          depreciations.value = response.data.data || [];
        } catch (err) {
          console.error('Error loading depreciations:', err);
        } finally {
          isLoadingDepreciations.value = false;
        }
      };

      // Load accounting periods for depreciation calculation
      const loadAccountingPeriods = async () => {
        try {
          const response = await axios.get('/api/accounting/accounting-periods', {
            params: { status: 'Open' }
          });
          accountingPeriods.value = response.data.data || [];

          // Set default period if available
          if (accountingPeriods.value.length > 0) {
            depreciationForm.value.period_id = accountingPeriods.value[0].period_id;
          }
        } catch (err) {
          console.error('Error loading accounting periods:', err);
        }
      };

      // Load chart of accounts for depreciation calculation
      const loadAccounts = async () => {
        try {
          const response = await axios.get('/api/accounting/chart-of-accounts');
          const accounts = response.data.data || [];

          // Filter expense accounts
          expenseAccounts.value = accounts.filter(account =>
            account.account_type === 'Expense' && account.is_active
          );

          // Filter asset accounts
          assetAccounts.value = accounts.filter(account =>
            account.account_type === 'Asset' && account.is_active
          );

          // Try to find default accounts
          const defaultExpenseAccount = expenseAccounts.value.find(account =>
            account.name.toLowerCase().includes('depreciation') &&
            account.name.toLowerCase().includes('expense')
          );

          const defaultAssetAccount = assetAccounts.value.find(account =>
            account.name.toLowerCase().includes('accumulated') &&
            account.name.toLowerCase().includes('depreciation')
          );

          if (defaultExpenseAccount) {
            depreciationForm.value.depreciation_expense_account_id = defaultExpenseAccount.account_id;
          }

          if (defaultAssetAccount) {
            depreciationForm.value.accumulated_depreciation_account_id = defaultAssetAccount.account_id;
          }
        } catch (err) {
          console.error('Error loading chart of accounts:', err);
        }
      };

      // Calculate depreciation
      const calculateDepreciation = async () => {
        isCalculatingDepreciation.value = true;
        depreciationError.value = null;

        try {
          await axios.post(`/api/accounting/fixed-assets/${assetId.value}/calculate-depreciation`, depreciationForm.value);

          // Refresh asset and depreciation data
          await Promise.all([
            loadAsset(),
            loadDepreciations()
          ]);

          // Close modal
          closeDepreciationModal();
        } catch (err) {
          console.error('Error calculating depreciation:', err);

          if (err.response && err.response.data && err.response.data.message) {
            depreciationError.value = err.response.data.message;
          } else {
            depreciationError.value = 'Failed to calculate depreciation. Please try again later.';
          }
        } finally {
          isCalculatingDepreciation.value = false;
        }
      };

      // Show calculate depreciation modal
      const showCalculateDepreciationModal = () => {
        depreciationError.value = null;
        showDepreciationModal.value = true;
      };

      // Close calculate depreciation modal
      const closeDepreciationModal = () => {
        showDepreciationModal.value = false;
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

      // Calculate useful life based on depreciation rate
      const calculateUsefulLife = () => {
        if (!asset.value || !asset.value.depreciation_rate || asset.value.depreciation_rate === 0) {
          return 'N/A';
        }

        return Math.round(100 / asset.value.depreciation_rate);
      };

      // Calculate depreciation percentage
      const calculateDepreciationPercentage = () => {
        if (!asset.value || !asset.value.acquisition_cost || asset.value.acquisition_cost === 0) {
          return 0;
        }

        const depreciatedAmount = asset.value.acquisition_cost - asset.value.current_value;
        const percentage = (depreciatedAmount / asset.value.acquisition_cost) * 100;

        return Math.round(percentage);
      };

      // Calculate monthly depreciation
      const calculateMonthlyDepreciation = () => {
        if (!asset.value || !asset.value.acquisition_cost || !asset.value.depreciation_rate) {
          return 0;
        }

        return (asset.value.acquisition_cost * (asset.value.depreciation_rate / 100)) / 12;
      };

      // Calculate annual depreciation
      const calculateAnnualDepreciation = () => {
        if (!asset.value || !asset.value.acquisition_cost || !asset.value.depreciation_rate) {
          return 0;
        }

        return asset.value.acquisition_cost * (asset.value.depreciation_rate / 100);
      };

      // Calculate full depreciation date
      const calculateFullDepreciationDate = () => {
        if (!asset.value || !asset.value.acquisition_date || !asset.value.depreciation_rate || asset.value.depreciation_rate === 0) {
          return 'Unknown';
        }

        const acquisitionDate = new Date(asset.value.acquisition_date);
        const usefulLifeYears = 100 / asset.value.depreciation_rate;

        const fullDepreciationDate = new Date(acquisitionDate);
        fullDepreciationDate.setFullYear(fullDepreciationDate.getFullYear() + usefulLifeYears);

        return formatDate(fullDepreciationDate);
      };

      // Print asset details
      const printAssetDetails = () => {
        window.print();
      };

      // Lifecycle hooks
      onMounted(() => {
        loadAsset();
      });

      return {
        assetId,
        asset,
        depreciations,
        accountingPeriods,
        expenseAccounts,
        assetAccounts,
        isLoading,
        isLoadingDepreciations,
        error,
        showDepreciationModal,
        isCalculatingDepreciation,
        depreciationError,
        depreciationForm,
        formatDate,
        formatCurrency,
        calculateUsefulLife,
        calculateDepreciationPercentage,
        calculateMonthlyDepreciation,
        calculateAnnualDepreciation,
        calculateFullDepreciationDate,
        showCalculateDepreciationModal,
        closeDepreciationModal,
        calculateDepreciation,
        printAssetDetails
      };
    }
  };
  </script>

  <style scoped>
  .fixed-asset-detail {
    max-width: 100%;
  }

  .asset-header {
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--gray-200);
  }

  .asset-name {
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0.5rem 0;
  }

  .asset-code {
    color: var(--gray-600);
    font-family: monospace;
    font-size: 0.9rem;
  }

  .asset-category {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    background-color: var(--gray-200);
    color: var(--gray-700);
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
  }

  .badge {
    padding: 0.5em 0.75em;
    font-size: 0.75em;
  }

  .table-details {
    margin-bottom: 0;
  }

  .table-details th {
    width: 40%;
    font-weight: 600;
  }

  .progress {
    height: 1.5rem;
    border-radius: 0.375rem;
  }

  .progress-bar {
    font-size: 0.75rem;
    font-weight: 600;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
  }

  .depreciation-summary {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    margin-top: 1.5rem;
  }

  .summary-item {
    padding: 1rem;
    background-color: var(--gray-50);
    border-radius: 0.375rem;
    border: 1px solid var(--gray-200);
  }

  .summary-label {
    font-size: 0.75rem;
    color: var(--gray-500);
    margin-bottom: 0.5rem;
  }

  .summary-value {
    font-size: 1rem;
    font-weight: 600;
    color: var(--gray-800);
  }

  .text-right {
    text-align: right;
  }

  @media print {
    .action-buttons,
    .btn,
    .card-header {
      display: none !important;
    }

    .card {
      border: none !important;
      box-shadow: none !important;
    }

    .card-body {
      padding: 0 !important;
    }
  }
  </style>
