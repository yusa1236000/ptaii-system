<!-- src/views/accounting/BudgetForm.vue -->
<template>
    <div class="budget-form">
      <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
          <h2>{{ isEditing ? 'Edit Anggaran' : 'Tambah Anggaran' }}</h2>
          <router-link to="/accounting/budgets" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Kembali
          </router-link>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <div v-if="isLoading" class="text-center py-5">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p class="mt-2">Memuat data...</p>
          </div>

          <div v-else-if="error" class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            {{ error }}
          </div>

          <form v-else @submit.prevent="saveBudget" class="budget-form-container">
            <!-- Period Selection -->
            <div class="form-group">
              <label for="period_id">Periode Akuntansi <span class="text-danger">*</span></label>
              <select
                id="period_id"
                v-model="form.period_id"
                class="form-control"
                :class="{ 'is-invalid': validationErrors.period_id }"
                required
              >
                <option value="">-- Pilih Periode --</option>
                <option v-for="period in periods" :key="period.period_id" :value="period.period_id">
                  {{ period.period_name }}
                </option>
              </select>
              <div v-if="validationErrors.period_id" class="invalid-feedback">
                {{ validationErrors.period_id[0] }}
              </div>
            </div>

            <!-- Account Selection -->
            <div class="form-group">
              <label for="account_id">Akun <span class="text-danger">*</span></label>
              <select
                id="account_id"
                v-model="form.account_id"
                class="form-control"
                :class="{ 'is-invalid': validationErrors.account_id }"
                required
              >
                <option value="">-- Pilih Akun --</option>
                <optgroup
                  v-for="group in accountsByType"
                  :key="group.type"
                  :label="formatAccountType(group.type)"
                >
                  <option
                    v-for="account in group.accounts"
                    :key="account.account_id"
                    :value="account.account_id"
                  >
                    {{ account.account_code }} - {{ account.name }}
                  </option>
                </optgroup>
              </select>
              <div v-if="validationErrors.account_id" class="invalid-feedback">
                {{ validationErrors.account_id[0] }}
              </div>
            </div>

            <div class="row">
              <div class="col-md-6">
                <!-- Budgeted Amount -->
                <div class="form-group">
                  <label for="budgeted_amount">Jumlah Anggaran <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text">Rp</span>
                    </div>
                    <input
                      id="budgeted_amount"
                      v-model="form.budgeted_amount"
                      type="number"
                      class="form-control"
                      :class="{ 'is-invalid': validationErrors.budgeted_amount }"
                      min="0"
                      step="0.01"
                      placeholder="0.00"
                      required
                    />
                  </div>
                  <div v-if="validationErrors.budgeted_amount" class="invalid-feedback d-block">
                    {{ validationErrors.budgeted_amount[0] }}
                  </div>
                  <small class="form-text text-muted">
                    Masukkan jumlah anggaran yang direncanakan untuk periode yang dipilih
                  </small>
                </div>
              </div>

              <div class="col-md-6">
                <!-- Actual Amount (for editing only) -->
                <div class="form-group" v-if="isEditing">
                  <label for="actual_amount">Jumlah Aktual</label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text">Rp</span>
                    </div>
                    <input
                      id="actual_amount"
                      v-model="form.actual_amount"
                      type="number"
                      class="form-control"
                      :class="{ 'is-invalid': validationErrors.actual_amount }"
                      min="0"
                      step="0.01"
                      placeholder="0.00"
                    />
                  </div>
                  <div v-if="validationErrors.actual_amount" class="invalid-feedback d-block">
                    {{ validationErrors.actual_amount[0] }}
                  </div>
                  <small class="form-text text-muted">
                    Jumlah aktual realisasi anggaran (opsional, biasanya diupdate otomatis dari transaksi)
                  </small>
                </div>
              </div>
            </div>

            <!-- Notes Field -->
            <div class="form-group">
              <label for="notes">Catatan</label>
              <textarea
                id="notes"
                v-model="form.notes"
                class="form-control"
                :class="{ 'is-invalid': validationErrors.notes }"
                rows="3"
                placeholder="Tambahkan catatan tentang anggaran ini..."
              ></textarea>
              <div v-if="validationErrors.notes" class="invalid-feedback">
                {{ validationErrors.notes[0] }}
              </div>
            </div>

            <!-- Budget Distribution (Monthly breakdown) -->
            <div class="form-group">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <label>Distribusi Anggaran (Opsional)</label>
                <button type="button" class="btn btn-sm btn-outline-secondary" @click="distributeEvenly">
                  <i class="fas fa-balance-scale mr-1"></i> Distribusi Merata
                </button>
              </div>

              <div class="budget-distribution mt-3">
                <div class="table-responsive">
                  <table class="table table-sm table-bordered">
                    <thead>
                      <tr>
                        <th>Bulan</th>
                        <th class="text-right">Jumlah</th>
                        <th class="text-right">%</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(month, index) in months" :key="index">
                        <td>{{ month }}</td>
                        <td>
                          <div class="input-group input-group-sm">
                            <div class="input-group-prepend">
                              <span class="input-group-text">Rp</span>
                            </div>
                            <input
                              v-model="monthlyDistribution[index]"
                              type="number"
                              class="form-control text-right"
                              min="0"
                              step="0.01"
                              @input="updateDistributionPercentages"
                            />
                          </div>
                        </td>
                        <td class="text-right">
                          {{ calculateDistributionPercentage(index) }}%
                        </td>
                      </tr>
                    </tbody>
                    <tfoot>
                      <tr class="bg-light font-weight-bold">
                        <td>Total</td>
                        <td class="text-right" :class="{ 'text-danger': !isDistributionBalanced }">
                          {{ formatCurrency(totalDistribution) }}
                        </td>
                        <td class="text-right" :class="{ 'text-danger': !isDistributionBalanced }">
                          {{ totalDistributionPercentage }}%
                        </td>
                      </tr>
                    </tfoot>
                  </table>
                </div>

                <div v-if="!isDistributionBalanced" class="alert alert-warning small mt-2">
                  <i class="fas fa-exclamation-triangle mr-1"></i>
                  Total distribusi anggaran ({{ formatCurrency(totalDistribution) }}) tidak sama dengan jumlah anggaran ({{ formatCurrency(form.budgeted_amount) }})
                </div>
              </div>
            </div>

            <div class="form-actions mt-4">
              <button type="submit" class="btn btn-primary" :disabled="isSaving">
                <i v-if="isSaving" class="fas fa-spinner fa-spin mr-1"></i>
                <i v-else class="fas fa-save mr-1"></i>
                {{ isEditing ? 'Update Anggaran' : 'Simpan Anggaran' }}
              </button>
              <router-link to="/accounting/budgets" class="btn btn-secondary ml-2">
                Batal
              </router-link>
            </div>
          </form>
        </div>
      </div>
    </div>
  </template>

  <script>
  import axios from 'axios';
  import { ref, reactive, computed, watch, onMounted } from 'vue';
  import { useRoute, useRouter } from 'vue-router';

  export default {
    name: 'BudgetForm',
    setup() {
      const route = useRoute();
      const router = useRouter();
      const budgetId = computed(() => route.params.id);
      const isEditing = computed(() => !!budgetId.value);

      // Form state
      const form = reactive({
        period_id: '',
        account_id: '',
        budgeted_amount: '',
        actual_amount: null,
        notes: '',
        distribution: null // For monthly breakdown
      });

      // UI state
      const isLoading = ref(true);
      const isSaving = ref(false);
      const error = ref(null);
      const validationErrors = ref({});
      const periods = ref([]);
      const accounts = ref([]);
      const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                      'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
      const monthlyDistribution = ref(Array(12).fill(0));

      // Computed properties
      const accountsByType = computed(() => {
        const groupedAccounts = accounts.value.reduce((groups, account) => {
          const type = account.account_type;
          if (!groups[type]) {
            groups[type] = [];
          }
          groups[type].push(account);
          return groups;
        }, {});

        return Object.keys(groupedAccounts).map(type => ({
          type,
          accounts: groupedAccounts[type].sort((a, b) => a.account_code.localeCompare(b.account_code))
        }));
      });

      const totalDistribution = computed(() => {
        return monthlyDistribution.value.reduce((sum, amount) => sum + parseFloat(amount || 0), 0);
      });

      const isDistributionBalanced = computed(() => {
        return Math.abs(totalDistribution.value - parseFloat(form.budgeted_amount || 0)) < 0.01;
      });

      const totalDistributionPercentage = computed(() => {
        if (!form.budgeted_amount || parseFloat(form.budgeted_amount) === 0) return 0;
        return ((totalDistribution.value / parseFloat(form.budgeted_amount)) * 100).toFixed(2);
      });

      // Methods
      const loadData = async () => {
        isLoading.value = true;
        error.value = null;

        try {
          // Load periods and accounts in parallel
          const [periodsResponse, accountsResponse] = await Promise.all([
            axios.get('/api/accounting/accounting-periods'),
            axios.get('/api/accounting/chart-of-accounts')
          ]);

          periods.value = periodsResponse.data.data || periodsResponse.data;

          // Filter accounts to include only active ones
          accounts.value = (accountsResponse.data.data || accountsResponse.data)
            .filter(account => account.is_active);

          // If editing, load budget data
          if (isEditing.value) {
            await loadBudget();
          } else {
            // Set default period to current period if available
            const currentPeriod = periods.value.find(p => p.status === 'Open');
            if (currentPeriod) {
              form.period_id = currentPeriod.period_id;
            }
          }
        } catch (err) {
          console.error('Error loading form data:', err);
          error.value = 'Gagal memuat data. Silakan coba lagi nanti.';
        } finally {
          isLoading.value = false;
        }
      };

      const loadBudget = async () => {
        try {
          const response = await axios.get(`/api/accounting/budgets/${budgetId.value}`);
          const budget = response.data.data || response.data;

          // Populate form with budget data
          form.period_id = budget.period_id;
          form.account_id = budget.account_id;
          form.budgeted_amount = budget.budgeted_amount;
          form.actual_amount = budget.actual_amount;
          form.notes = budget.notes || '';

          // Load distribution if available
          if (budget.distribution) {
            try {
              const distribution = JSON.parse(budget.distribution);
              if (Array.isArray(distribution) && distribution.length === 12) {
                monthlyDistribution.value = distribution;
              }
            } catch (e) {
              console.error('Error parsing budget distribution:', e);
            }
          }
        } catch (err) {
          console.error('Error loading budget:', err);
          error.value = 'Gagal memuat data anggaran. Silakan coba lagi nanti.';
        }
      };

      const saveBudget = async () => {
        validationErrors.value = {};
        isSaving.value = true;

        try {
          // Prepare form data with distribution
          const formData = {
            period_id: form.period_id,
            account_id: form.account_id,
            budgeted_amount: parseFloat(form.budgeted_amount),
            notes: form.notes,
            distribution: JSON.stringify(monthlyDistribution.value)
          };

          // Include actual amount if editing
          if (isEditing.value && form.actual_amount !== null) {
            formData.actual_amount = parseFloat(form.actual_amount);
          }

          //let response;
          if (isEditing.value) {
             await axios.put(`/api/accounting/budgets/${budgetId.value}`, formData);
          } else {
             await axios.post('/api/accounting/budgets', formData);
          }

          // Navigate back to list with success parameter
          router.push({
            path: '/accounting/budgets',
            query: { success: isEditing.value ? 'updated' : 'created' }
          });
        } catch (err) {
          console.error('Error saving budget:', err);

          if (err.response && err.response.status === 422) {
            // Validation errors
            validationErrors.value = err.response.data.errors || {};
          } else {
            error.value = 'Gagal menyimpan anggaran. Silakan coba lagi nanti.';
          }
        } finally {
          isSaving.value = false;
        }
      };

      const distributeEvenly = () => {
        const budgetAmount = parseFloat(form.budgeted_amount || 0);
        if (budgetAmount <= 0) return;

        const monthlyAmount = (budgetAmount / 12).toFixed(2);
        monthlyDistribution.value = Array(12).fill(monthlyAmount);

        // Adjust the last month to account for rounding errors
        const total = monthlyDistribution.value.reduce((sum, val) => sum + parseFloat(val), 0);
        const diff = budgetAmount - total;
        if (Math.abs(diff) > 0.01) {
          monthlyDistribution.value[11] = (parseFloat(monthlyDistribution.value[11]) + diff).toFixed(2);
        }
      };

      const updateDistributionPercentages = () => {
        // This function is called when user changes any monthly amount
        // No need to do anything here as the computed properties will update automatically
      };

      const calculateDistributionPercentage = (index) => {
        const amount = parseFloat(monthlyDistribution.value[index] || 0);
        const totalBudget = parseFloat(form.budgeted_amount || 0);

        if (totalBudget === 0) return '0.00';
        return ((amount / totalBudget) * 100).toFixed(2);
      };

      const formatCurrency = (amount) => {
        return new Intl.NumberFormat('id-ID', {
          style: 'currency',
          currency: 'IDR',
          minimumFractionDigits: 0,
          maximumFractionDigits: 0
        }).format(amount || 0);
      };

      const formatAccountType = (type) => {
        switch (type) {
          case 'Asset': return 'Aset';
          case 'Liability': return 'Kewajiban';
          case 'Equity': return 'Ekuitas';
          case 'Revenue': return 'Pendapatan';
          case 'Expense': return 'Biaya';
          default: return type || '';
        }
      };

      // Watch for changes in budgeted amount to update distribution
      watch(() => form.budgeted_amount, (newValue) => {
        // If distribution is empty or total is 0, distribute evenly
        if (totalDistribution.value === 0 && newValue && parseFloat(newValue) > 0) {
          distributeEvenly();
        }
      });

      // Lifecycle hooks
      onMounted(() => {
        loadData();
      });

      return {
        isEditing,
        isLoading,
        isSaving,
        error,
        form,
        validationErrors,
        periods,
        accounts,
        accountsByType,
        months,
        monthlyDistribution,
        totalDistribution,
        isDistributionBalanced,
        totalDistributionPercentage,
        saveBudget,
        distributeEvenly,
        updateDistributionPercentages,
        calculateDistributionPercentage,
        formatCurrency,
        formatAccountType
      };
    }
  };
  </script>

  <style scoped>
  .budget-form-container {
    max-width: 100%;
  }

  .form-actions {
    border-top: 1px solid var(--gray-200);
    padding-top: 1.5rem;
  }

  .budget-distribution {
    border: 1px solid var(--gray-200);
    border-radius: 0.5rem;
    padding: 1rem;
    background-color: var(--gray-50);
  }

  .table th, .table td {
    vertical-align: middle;
  }

  @media (max-width: 768px) {
    .budget-distribution {
      overflow-x: auto;
    }
  }
  </style>
