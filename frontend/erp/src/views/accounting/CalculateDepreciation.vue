<!-- src/views/accounting/CalculateDepreciation.vue -->
<template>
    <div class="calculate-depreciation">
      <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
          <h2>Hitung Penyusutan Aset</h2>
          <router-link to="/accounting/depreciation" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left mr-2"></i> Kembali ke Daftar
          </router-link>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Formulir Penyusutan</h3>
        </div>
        <div class="card-body">
          <div v-if="isLoading" class="text-center py-4">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p class="mt-2">Memuat data...</p>
          </div>

          <div v-else-if="error" class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            {{ error }}
          </div>

          <form v-else @submit.prevent="calculateDepreciation" class="depreciation-form">
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="assetId">Aset <span class="text-danger">*</span></label>
                  <select
                    id="assetId"
                    v-model="form.assetId"
                    class="form-control"
                    :class="{ 'is-invalid': errors.asset_id }"
                    required
                    @change="loadAssetDetails"
                  >
                    <option value="">-- Pilih Aset --</option>
                    <option
                      v-for="asset in assets"
                      :key="asset.asset_id"
                      :value="asset.asset_id"
                      :disabled="asset.status !== 'Active'"
                    >
                      {{ asset.name }} ({{ asset.asset_code }})
                      <span v-if="asset.status !== 'Active'"> - {{ asset.status }}</span>
                    </option>
                  </select>
                  <div v-if="errors.asset_id" class="invalid-feedback">
                    {{ errors.asset_id[0] }}
                  </div>
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-group">
                  <label for="periodId">Periode Akuntansi <span class="text-danger">*</span></label>
                  <select
                    id="periodId"
                    v-model="form.periodId"
                    class="form-control"
                    :class="{ 'is-invalid': errors.period_id }"
                    required
                  >
                    <option value="">-- Pilih Periode --</option>
                    <option
                      v-for="period in periods"
                      :key="period.period_id"
                      :value="period.period_id"
                      :disabled="period.status !== 'Open'"
                    >
                      {{ period.period_name }}
                      <span v-if="period.status !== 'Open'"> - {{ period.status }}</span>
                    </option>
                  </select>
                  <div v-if="errors.period_id" class="invalid-feedback">
                    {{ errors.period_id[0] }}
                  </div>
                </div>
              </div>
            </div>

            <!-- Asset Details Card -->
            <div v-if="selectedAsset" class="asset-details-card mb-4">
              <div class="card">
                <div class="card-header bg-light">
                  <h4 class="mb-0">Detail Aset</h4>
                </div>
                <div class="card-body">
                  <div class="row">
                    <div class="col-md-6">
                      <table class="table table-sm table-borderless">
                        <tbody>
                          <tr>
                            <th width="40%">Kode Aset:</th>
                            <td>{{ selectedAsset.asset_code }}</td>
                          </tr>
                          <tr>
                            <th>Kategori:</th>
                            <td>{{ selectedAsset.category }}</td>
                          </tr>
                          <tr>
                            <th>Tanggal Perolehan:</th>
                            <td>{{ formatDate(selectedAsset.acquisition_date) }}</td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                    <div class="col-md-6">
                      <table class="table table-sm table-borderless">
                        <tbody>
                          <tr>
                            <th width="40%">Harga Perolehan:</th>
                            <td>{{ formatCurrency(selectedAsset.acquisition_cost) }}</td>
                          </tr>
                          <tr>
                            <th>Nilai Buku Saat Ini:</th>
                            <td>{{ formatCurrency(selectedAsset.current_value) }}</td>
                          </tr>
                          <tr>
                            <th>Tingkat Penyusutan:</th>
                            <td>{{ selectedAsset.depreciation_rate }}% per tahun</td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Journal Entry Configuration -->
            <div class="journal-config mb-4">
              <h4 class="mb-3">Konfigurasi Jurnal</h4>

              <div class="form-check mb-3">
                <input
                  id="createJournalEntry"
                  v-model="form.createJournalEntry"
                  type="checkbox"
                  class="form-check-input"
                />
                <label class="form-check-label" for="createJournalEntry">
                  Buat jurnal penyusutan otomatis
                </label>
              </div>

              <div v-if="form.createJournalEntry" class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="depreciationExpenseAccountId">
                      Akun Beban Penyusutan <span class="text-danger">*</span>
                    </label>
                    <select
                      id="depreciationExpenseAccountId"
                      v-model="form.depreciationExpenseAccountId"
                      class="form-control"
                      :class="{ 'is-invalid': errors.depreciation_expense_account_id }"
                      required
                    >
                      <option value="">-- Pilih Akun --</option>
                      <option
                        v-for="account in expenseAccounts"
                        :key="account.account_id"
                        :value="account.account_id"
                      >
                        {{ account.account_code }} - {{ account.name }}
                      </option>
                    </select>
                    <div v-if="errors.depreciation_expense_account_id" class="invalid-feedback">
                      {{ errors.depreciation_expense_account_id[0] }}
                    </div>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-group">
                    <label for="accumulatedDepreciationAccountId">
                      Akun Akumulasi Penyusutan <span class="text-danger">*</span>
                    </label>
                    <select
                      id="accumulatedDepreciationAccountId"
                      v-model="form.accumulatedDepreciationAccountId"
                      class="form-control"
                      :class="{ 'is-invalid': errors.accumulated_depreciation_account_id }"
                      required
                    >
                      <option value="">-- Pilih Akun --</option>
                      <option
                        v-for="account in assetAccounts"
                        :key="account.account_id"
                        :value="account.account_id"
                      >
                        {{ account.account_code }} - {{ account.name }}
                      </option>
                    </select>
                    <div v-if="errors.accumulated_depreciation_account_id" class="invalid-feedback">
                      {{ errors.accumulated_depreciation_account_id[0] }}
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Calculation Preview Card -->
            <div v-if="calculationPreview" class="calculation-preview mb-4">
              <div class="card bg-light">
                <div class="card-header">
                  <h4 class="mb-0">Pratinjau Hasil Perhitungan</h4>
                </div>
                <div class="card-body">
                  <div class="row">
                    <div class="col-md-6">
                      <div class="form-group">
                        <label>Nilai Buku Saat Ini:</label>
                        <div class="form-control-static">
                          {{ formatCurrency(calculationPreview.currentValue) }}
                        </div>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <label>Tingkat Penyusutan:</label>
                        <div class="form-control-static">
                          {{ calculationPreview.depreciationRate }}% per tahun
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="row">
                    <div class="col-md-4">
                      <div class="form-group">
                        <label>Penyusutan Periode Ini:</label>
                        <div class="form-control-static font-weight-bold text-primary">
                          {{ formatCurrency(calculationPreview.depreciationAmount) }}
                        </div>
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="form-group">
                        <label>Total Akumulasi Penyusutan:</label>
                        <div class="form-control-static">
                          {{ formatCurrency(calculationPreview.accumulatedDepreciation) }}
                        </div>
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="form-group">
                        <label>Nilai Buku Setelah Penyusutan:</label>
                        <div class="form-control-static font-weight-bold">
                          {{ formatCurrency(calculationPreview.remainingValue) }}
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="alert alert-info mt-3 mb-0">
                    <i class="fas fa-info-circle mr-2"></i>
                    Pratinjau ini menunjukkan hasil perhitungan berdasarkan data aset saat ini.
                    Nilai penyusutan akan dihitung secara otomatis berdasarkan metode
                    <strong>Garis Lurus (Straight Line)</strong>
                    dengan tingkat penyusutan {{ calculationPreview.depreciationRate }}% per tahun.
                  </div>
                </div>
              </div>
            </div>

            <div class="form-actions mt-4">
              <button type="button" class="btn btn-secondary mr-2" @click="goBack">
                <i class="fas fa-times mr-1"></i> Batal
              </button>
              <button
                type="button"
                class="btn btn-info mr-2"
                @click="showCalculationPreview"
                :disabled="!canPreview || isCalculating"
              >
                <i class="fas fa-calculator mr-1"></i> Hitung Pratinjau
              </button>
              <button
                type="submit"
                class="btn btn-primary"
                :disabled="isSaving || !canCalculate"
              >
                <i v-if="isSaving" class="fas fa-spinner fa-spin mr-1"></i>
                <i v-else class="fas fa-save mr-1"></i>
                {{ isSaving ? 'Menyimpan...' : 'Simpan Penyusutan' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </template>

  <script>
  import { ref, reactive, computed, onMounted } from 'vue';
  import { useRouter } from 'vue-router';
  import axios from 'axios';

  export default {
    name: 'CalculateDepreciation',
    setup() {
      const router = useRouter();

      // State
      const assets = ref([]);
      const periods = ref([]);
      const accounts = ref([]);
      const selectedAsset = ref(null);
      const isLoading = ref(true);
      const isCalculating = ref(false);
      const isSaving = ref(false);
      const error = ref(null);
      const errors = ref({});
      const calculationPreview = ref(null);

      // Form data
      const form = reactive({
        assetId: '',
        periodId: '',
        createJournalEntry: true,
        depreciationExpenseAccountId: '',
        accumulatedDepreciationAccountId: ''
      });

      // Computed properties
      const assetAccounts = computed(() => {
        return accounts.value.filter(account =>
          account.account_type === 'Asset' && account.is_active
        );
      });

      const expenseAccounts = computed(() => {
        return accounts.value.filter(account =>
          account.account_type === 'Expense' && account.is_active
        );
      });

      const canPreview = computed(() => {
        return form.assetId && selectedAsset.value;
      });

      const canCalculate = computed(() => {
        if (!form.assetId || !form.periodId) return false;

        if (form.createJournalEntry) {
          return form.depreciationExpenseAccountId &&
                 form.accumulatedDepreciationAccountId;
        }

        return true;
      });

      // Methods
      const loadData = async () => {
        isLoading.value = true;
        error.value = null;

        try {
          // Load assets, periods, and accounts in parallel
          const [assetsResponse, periodsResponse, accountsResponse] = await Promise.all([
            axios.get('/api/accounting/fixed-assets', { params: { status: 'Active' } }),
            axios.get('/api/accounting/accounting-periods', { params: { status: 'Open' } }),
            axios.get('/api/accounting/chart-of-accounts')
          ]);

          assets.value = assetsResponse.data.data || [];
          periods.value = periodsResponse.data.data || [];
          accounts.value = accountsResponse.data.data || [];

          // Set default period to current period if available
          const currentPeriod = periods.value.find(p => {
            const now = new Date();
            const startDate = new Date(p.start_date);
            const endDate = new Date(p.end_date);
            return now >= startDate && now <= endDate && p.status === 'Open';
          });

          if (currentPeriod) {
            form.periodId = currentPeriod.period_id;
          } else if (periods.value.length > 0) {
            form.periodId = periods.value[0].period_id;
          }

          // Find default accounts
          const findAccount = (keyword, type) => {
            return accounts.value.find(account =>
              account.account_type === type &&
              account.name.toLowerCase().includes(keyword.toLowerCase()) &&
              account.is_active
            );
          };

          // Try to find depreciation expense account
          const expenseAccount = findAccount('depreciation', 'Expense') ||
                                findAccount('penyusutan', 'Expense');
          if (expenseAccount) {
            form.depreciationExpenseAccountId = expenseAccount.account_id;
          }

          // Try to find accumulated depreciation account
          const accumulatedAccount = findAccount('accumulated', 'Asset') ||
                                   findAccount('akumulasi', 'Asset');
          if (accumulatedAccount) {
            form.accumulatedDepreciationAccountId = accumulatedAccount.account_id;
          }
        } catch (err) {
          console.error('Error loading data:', err);
          error.value = 'Gagal memuat data. Silakan coba lagi nanti.';
        } finally {
          isLoading.value = false;
        }
      };

      const loadAssetDetails = async () => {
        if (!form.assetId) {
          selectedAsset.value = null;
          calculationPreview.value = null;
          return;
        }

        try {
          const response = await axios.get(`/api/accounting/fixed-assets/${form.assetId}`);
          selectedAsset.value = response.data.data;
        } catch (err) {
          console.error('Error loading asset details:', err);
          error.value = 'Gagal memuat detail aset. Silakan coba lagi nanti.';
        }
      };

      const showCalculationPreview = async () => {
        if (!canPreview.value) return;

        isCalculating.value = true;
        calculationPreview.value = null;

        try {
          // Simulate calculation preview (normally would be done via API)
          // In a real application, you might want to add a specific endpoint for this preview

          const asset = selectedAsset.value;

          // Calculate values based on asset data
          const annualRate = asset.depreciation_rate / 100;
          const monthlyRate = annualRate / 12;
          const depreciationAmount = Math.round(asset.current_value * monthlyRate);
          const previousAccum = asset.acquisition_cost - asset.current_value;
          const newAccumulated = previousAccum + depreciationAmount;
          const newRemainingValue = asset.acquisition_cost - newAccumulated;

          calculationPreview.value = {
            currentValue: asset.current_value,
            depreciationRate: asset.depreciation_rate,
            depreciationAmount: depreciationAmount,
            accumulatedDepreciation: newAccumulated,
            remainingValue: newRemainingValue
          };
        } catch (err) {
          console.error('Error calculating preview:', err);
          error.value = 'Gagal menghitung pratinjau penyusutan. Silakan coba lagi nanti.';
        } finally {
          isCalculating.value = false;
        }
      };

      const calculateDepreciation = async () => {
        if (!canCalculate.value) return;

        isSaving.value = true;
        error.value = null;
        errors.value = {};

        const payload = {
          asset_id: form.assetId,
          period_id: form.periodId,
          create_journal_entry: form.createJournalEntry
        };

        if (form.createJournalEntry) {
          payload.depreciation_expense_account_id = form.depreciationExpenseAccountId;
          payload.accumulated_depreciation_account_id = form.accumulatedDepreciationAccountId;
        }

        try {
          const response = await axios.post(
            `/api/accounting/fixed-assets/${form.assetId}/calculate-depreciation`,
            payload
          );

          // Redirect to the detail page of the newly created depreciation
          router.push({
            path: `/accounting/depreciation/${response.data.data.depreciation_id}`,
            query: { success: 'created' }
          });
        } catch (err) {
          console.error('Error calculating depreciation:', err);

          if (err.response && err.response.status === 422) {
            errors.value = err.response.data.errors || {};
            error.value = 'Silakan perbaiki kesalahan dalam formulir.';
          } else {
            error.value = err.response?.data?.message ||
                         'Gagal menghitung dan menyimpan penyusutan. Silakan coba lagi nanti.';
          }
        } finally {
          isSaving.value = false;
        }
      };

      const goBack = () => {
        router.push('/accounting/depreciation');
      };

      const formatDate = (dateString) => {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', {
          year: 'numeric',
          month: 'long',
          day: 'numeric'
        });
      };

      const formatCurrency = (amount) => {
        return new Intl.NumberFormat('id-ID', {
          style: 'currency',
          currency: 'IDR',
          minimumFractionDigits: 0,
          maximumFractionDigits: 0
        }).format(amount || 0);
      };

      // Lifecycle hooks
      onMounted(() => {
        loadData();
      });

      return {
        assets,
        periods,
        accounts,
        assetAccounts,
        expenseAccounts,
        selectedAsset,
        isLoading,
        isCalculating,
        isSaving,
        error,
        errors,
        form,
        calculationPreview,
        canPreview,
        canCalculate,
        loadAssetDetails,
        showCalculationPreview,
        calculateDepreciation,
        goBack,
        formatDate,
        formatCurrency
      };
    }
  };
  </script>

  <style scoped>
  .depreciation-form {
    max-width: 100%;
  }

  .form-control-static {
    padding: 0.375rem 0.75rem;
    font-size: 1rem;
    line-height: 1.5;
    background-color: #f8f9fa;
    border: 1px solid #e2e8f0;
    border-radius: 0.25rem;
    font-weight: 500;
  }

  .form-actions {
    padding-top: 1rem;
    border-top: 1px solid #e2e8f0;
  }

  .asset-details-card {
    margin-top: 1.5rem;
  }

  .calculation-preview {
    margin-top: 1.5rem;
  }

  @media (max-width: 768px) {
    .calculation-preview .row > div {
      margin-bottom: 1rem;
    }
  }
  </style>
