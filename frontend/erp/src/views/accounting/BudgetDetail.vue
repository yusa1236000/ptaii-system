<!-- src/views/accounting/BudgetDetail.vue -->
<template>
    <div class="budget-detail">
      <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
          <h2>Detail Anggaran</h2>
          <div class="action-buttons">
            <router-link
              v-if="budget"
              :to="`/accounting/budgets/${budgetId}/edit`"
              class="btn btn-primary mr-2"
            >
              <i class="fas fa-edit mr-1"></i> Edit
            </router-link>
            <router-link to="/accounting/budgets" class="btn btn-outline-secondary">
              <i class="fas fa-arrow-left mr-1"></i> Kembali
            </router-link>
          </div>
        </div>
      </div>

      <div v-if="isLoading" class="text-center py-5">
        <i class="fas fa-spinner fa-spin fa-2x"></i>
        <p class="mt-2">Memuat data anggaran...</p>
      </div>

      <div v-else-if="error" class="alert alert-danger" role="alert">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        {{ error }}
      </div>

      <div v-else-if="!budget" class="alert alert-warning" role="alert">
        <i class="fas fa-exclamation-circle mr-2"></i>
        Anggaran tidak ditemukan
      </div>

      <div v-else class="row">
        <!-- Budget Info Card -->
        <div class="col-md-6 mb-4">
          <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h3 class="card-title">Informasi Anggaran</h3>
              <span :class="getBudgetStatusClass(budget)">
                {{ getBudgetStatus(budget) }}
              </span>
            </div>
            <div class="card-body">
              <div class="mb-4">
                <div class="account-info">
                  <h4 class="account-name">{{ budget.chart_of_account?.name || 'Akun tidak ditemukan' }}</h4>
                  <div class="account-code">{{ budget.chart_of_account?.account_code || '-' }}</div>
                  <div class="account-type">
                    <span :class="getAccountTypeClass(budget.chart_of_account?.account_type)">
                      {{ formatAccountType(budget.chart_of_account?.account_type) }}
                    </span>
                  </div>
                </div>
              </div>

              <table class="table table-details">
                <tbody>
                  <tr>
                    <th>Periode</th>
                    <td>{{ budget.accounting_period?.period_name || '-' }}</td>
                  </tr>
                  <tr>
                    <th>Anggaran</th>
                    <td class="value-highlight">{{ formatCurrency(budget.budgeted_amount) }}</td>
                  </tr>
                  <tr>
                    <th>Aktual</th>
                    <td class="value-highlight">{{ formatCurrency(budget.actual_amount) }}</td>
                  </tr>
                  <tr>
                    <th>Varian</th>
                    <td :class="['value-highlight', getVarianceClass(budget.variance)]">
                      {{ formatCurrency(budget.variance) }} ({{ calculateVariancePercentage(budget) }}%)
                    </td>
                  </tr>
                  <tr v-if="budget.notes">
                    <th>Catatan</th>
                    <td>{{ budget.notes }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Budget Progress Card -->
        <div class="col-md-6 mb-4">
          <div class="card h-100">
            <div class="card-header">
              <h3 class="card-title">Progres Anggaran</h3>
            </div>
            <div class="card-body d-flex flex-column justify-content-between">
              <div>
                <div class="progress-container mb-4">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="label">Persentase Realisasi</div>
                    <div class="value font-weight-bold">{{ calculateRealizationPercentage(budget) }}%</div>
                  </div>
                  <div class="progress" style="height: 1.5rem;">
                    <div
                      class="progress-bar"
                      :class="getProgressBarClass(budget)"
                      :style="{ width: calculateRealizationPercentage(budget) + '%' }"
                    >
                      {{ calculateRealizationPercentage(budget) }}%
                    </div>
                  </div>
                </div>

                <div class="status-indicators">
                  <div class="status-item">
                    <div :class="['status-indicator', getStatusIndicatorClass(budget, 'progress')]"></div>
                    <div class="status-detail">
                      <div class="status-label">Progres</div>
                      <div class="status-value">
                        {{ getProgressStatus(budget) }}
                      </div>
                    </div>
                  </div>

                  <div class="status-item">
                    <div :class="['status-indicator', getStatusIndicatorClass(budget, 'trend')]"></div>
                    <div class="status-detail">
                      <div class="status-label">Tren</div>
                      <div class="status-value">
                        {{ getTrendStatus(budget) }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="remaining-budget mt-4">
                <div class="d-flex justify-content-between">
                  <span>Anggaran:</span>
                  <span class="font-weight-bold">{{ formatCurrency(budget.budgeted_amount) }}</span>
                </div>
                <div class="d-flex justify-content-between">
                  <span>Terpakai:</span>
                  <span class="font-weight-bold">{{ formatCurrency(budget.actual_amount) }}</span>
                </div>
                <div class="d-flex justify-content-between mt-2 pt-2 border-top">
                  <span>Sisa Anggaran:</span>
                  <span
                    class="font-weight-bold"
                    :class="getRemainingBudgetClass(budget)"
                  >
                    {{ formatCurrency(calculateRemainingBudget(budget)) }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Monthly Distribution Chart -->
        <div class="col-12 mb-4">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">Distribusi Anggaran Bulanan</h3>
            </div>
            <div class="card-body">
              <div v-if="!hasDistribution" class="text-center py-4">
                <i class="fas fa-chart-bar fa-2x text-muted mb-3"></i>
                <p>Distribusi anggaran bulanan tidak tersedia</p>
              </div>
              <div v-else>
                <div class="row">
                  <div class="col-lg-8">
                    <!-- Chart will be rendered here -->
                    <div class="chart-container" style="height: 300px;">
                      <canvas id="budgetChart"></canvas>
                    </div>
                  </div>
                  <div class="col-lg-4">
                    <div class="table-responsive">
                      <table class="table table-sm table-bordered">
                        <thead>
                          <tr>
                            <th>Bulan</th>
                            <th class="text-right">Anggaran</th>
                            <th class="text-right">Aktual</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="(month, index) in months" :key="index">
                            <td>{{ month }}</td>
                            <td class="text-right">{{ formatCurrency(monthlyDistribution[index]) }}</td>
                            <td class="text-right">
                              {{ formatCurrency(getMonthlyActual(index)) }}
                            </td>
                          </tr>
                        </tbody>
                        <tfoot>
                          <tr class="bg-light font-weight-bold">
                            <td>Total</td>
                            <td class="text-right">{{ formatCurrency(budget.budgeted_amount) }}</td>
                            <td class="text-right">{{ formatCurrency(budget.actual_amount) }}</td>
                          </tr>
                        </tfoot>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Related Transactions -->
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">Transaksi Terkait</h3>
            </div>
            <div class="card-body">
              <div v-if="isLoadingTransactions" class="text-center py-4">
                <i class="fas fa-spinner fa-spin"></i>
                <p class="mt-2">Memuat transaksi...</p>
              </div>
              <div v-else-if="transactions.length === 0" class="text-center py-4">
                <i class="fas fa-file-invoice fa-2x text-muted mb-3"></i>
                <p>Tidak ada transaksi terkait untuk anggaran ini</p>
              </div>
              <div v-else class="table-responsive">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>Tanggal</th>
                      <th>No. Jurnal</th>
                      <th>Deskripsi</th>
                      <th class="text-right">Debit</th>
                      <th class="text-right">Kredit</th>
                      <th>Referensi</th>
                      <th>Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="transaction in transactions" :key="transaction.line_id">
                      <td>{{ formatDate(transaction.entry_date) }}</td>
                      <td>{{ transaction.journal_number }}</td>
                      <td>{{ transaction.description }}</td>
                      <td class="text-right">{{ formatCurrency(transaction.debit_amount) }}</td>
                      <td class="text-right">{{ formatCurrency(transaction.credit_amount) }}</td>
                      <td>
                        <span v-if="transaction.reference_type">
                          {{ transaction.reference_type }} #{{ transaction.reference_id }}
                        </span>
                        <span v-else>-</span>
                      </td>
                      <td>
                        <router-link
                          :to="`/accounting/journal-entries/${transaction.journal_id}`"
                          class="btn btn-sm btn-info"
                        >
                          <i class="fas fa-eye"></i>
                        </router-link>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </template>

  <script>
  import axios from 'axios';
  import { ref, computed, onMounted } from 'vue';
  import { useRoute } from 'vue-router';
  import Chart from 'chart.js/auto';

  export default {
    name: 'BudgetDetail',
    setup() {
      const route = useRoute();
      const budgetId = computed(() => route.params.id);

      const isLoading = ref(true);
      const isLoadingTransactions = ref(true);
      const error = ref(null);
      const budget = ref(null);
      const transactions = ref([]);
      const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                      'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
      const monthlyDistribution = ref(Array(12).fill(0));
      const monthlyActual = ref(Array(12).fill(0));
      let budgetChart = null;

      const hasDistribution = computed(() => {
        return monthlyDistribution.value.some(amount => parseFloat(amount) > 0);
      });

      // Load budget data
      const loadBudget = async () => {
        isLoading.value = true;
        error.value = null;

        try {
          const response = await axios.get(`/api/accounting/budgets/${budgetId.value}`);
          budget.value = response.data.data || response.data;

          // Parse distribution if available
          if (budget.value.distribution) {
            try {
              const distribution = JSON.parse(budget.value.distribution);
              if (Array.isArray(distribution) && distribution.length === 12) {
                monthlyDistribution.value = distribution;
              }
            } catch (e) {
              console.error('Error parsing budget distribution:', e);
            }
          }

          // Load related transactions
          await loadTransactions();

          // Initialize chart after data is loaded
          initializeChart();
        } catch (err) {
          console.error('Error loading budget:', err);
          error.value = 'Gagal memuat data anggaran. Silakan coba lagi nanti.';
        } finally {
          isLoading.value = false;
        }
      };

      // Load related transactions
      const loadTransactions = async () => {
        isLoadingTransactions.value = true;

        try {
          // Assuming there's an endpoint to get transactions related to this budget
          const response = await axios.get(`/api/accounting/budgets/${budgetId.value}/transactions`);
          transactions.value = response.data.data || response.data || [];

          // Process transactions to calculate monthly actuals
          calculateMonthlyActuals();
        } catch (err) {
          console.error('Error loading transactions:', err);
          // Don't set the main error, just show empty transactions
          transactions.value = [];
        } finally {
          isLoadingTransactions.value = false;
        }
      };

      // Calculate monthly actuals from transactions
      const calculateMonthlyActuals = () => {
        // Reset monthly actuals
        monthlyActual.value = Array(12).fill(0);

        // Process each transaction
        transactions.value.forEach(transaction => {
          if (!transaction.entry_date) return;

          // Get month from transaction date
          const date = new Date(transaction.entry_date);
          const month = date.getMonth();

          // For revenue accounts, credit increases the account balance
          // For expense accounts, debit increases the account balance
          if (budget.value.chart_of_account?.account_type === 'Revenue') {
            monthlyActual.value[month] += parseFloat(transaction.credit_amount || 0) -
                                         parseFloat(transaction.debit_amount || 0);
          } else if (budget.value.chart_of_account?.account_type === 'Expense') {
            monthlyActual.value[month] += parseFloat(transaction.debit_amount || 0) -
                                         parseFloat(transaction.credit_amount || 0);
          } else {
            // For other account types, just use the net amount
            monthlyActual.value[month] += parseFloat(transaction.debit_amount || 0) -
                                         parseFloat(transaction.credit_amount || 0);
          }
        });
      };

      // Get monthly actual for a specific month
      const getMonthlyActual = (month) => {
        return monthlyActual.value[month] || 0;
      };

      // Initialize chart
      const initializeChart = () => {
        if (!hasDistribution.value) return;

        // Wait for DOM to be fully rendered
        setTimeout(() => {
          const canvas = document.getElementById('budgetChart');
          if (!canvas) return;

          // Destroy previous chart if exists
          if (budgetChart) {
            budgetChart.destroy();
          }

          // Create chart context
          const ctx = canvas.getContext('2d');

          // Create chart
          budgetChart = new Chart(ctx, {
            type: 'bar',
            data: {
              labels: months,
              datasets: [
                {
                  label: 'Anggaran',
                  data: monthlyDistribution.value,
                  backgroundColor: 'rgba(37, 99, 235, 0.2)',
                  borderColor: 'rgba(37, 99, 235, 1)',
                  borderWidth: 1
                },
                {
                  label: 'Aktual',
                  data: monthlyActual.value,
                  backgroundColor: 'rgba(5, 150, 105, 0.2)',
                  borderColor: 'rgba(5, 150, 105, 1)',
                  borderWidth: 1
                }
              ]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              scales: {
                y: {
                  beginAtZero: true,
                  ticks: {
                    callback: function(value) {
                      return new Intl.NumberFormat('id-ID', {
                        style: 'currency',
                        currency: 'IDR',
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 0
                      }).format(value);
                    }
                  }
                }
              },
              plugins: {
                tooltip: {
                  callbacks: {
                    label: function(context) {
                      let label = context.dataset.label || '';
                      if (label) {
                        label += ': ';
                      }
                      label += new Intl.NumberFormat('id-ID', {
                        style: 'currency',
                        currency: 'IDR',
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 0
                      }).format(context.raw);
                      return label;
                    }
                  }
                }
              }
            }
          });
        }, 100);
      };

      // Format utilities
      const formatCurrency = (amount) => {
        return new Intl.NumberFormat('id-ID', {
          style: 'currency',
          currency: 'IDR',
          minimumFractionDigits: 0,
          maximumFractionDigits: 0
        }).format(amount || 0);
      };

      const formatDate = (dateString) => {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID');
      };

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

      // Status and styling utilities
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

      const getVarianceClass = (variance) => {
        if (!variance) return '';

        // For revenue accounts, positive variance is good
        // For expense accounts, negative variance is good
        if (budget.value?.chart_of_account?.account_type === 'Revenue') {
          return variance >= 0 ? 'text-success' : 'text-danger';
        } else if (budget.value?.chart_of_account?.account_type === 'Expense') {
          return variance <= 0 ? 'text-success' : 'text-danger';
        }

        // For other accounts, just use absolute variance
        return variance < 0 ? 'text-danger' : 'text-success';
      };

      const getBudgetStatus = (budget) => {
        if (!budget.actual_amount) return 'Belum Realisasi';

        const variance = budget.variance || 0;
        const variancePercentage = Math.abs(variance) / (budget.budgeted_amount || 1) * 100;

        if (variancePercentage <= 5) return 'Sesuai Target';

        // For revenue, above budget is good
        if (budget.chart_of_account?.account_type === 'Revenue') {
          return variance > 0 ? 'Di Atas Target' : 'Di Bawah Target';
        }

        // For expense, below budget is good
        if (budget.chart_of_account?.account_type === 'Expense') {
          return variance < 0 ? 'Di Bawah Target' : 'Di Atas Target';
        }

        // For other types
        return variance > 0 ? 'Di Atas Target' : 'Di Bawah Target';
      };

      const getBudgetStatusClass = (budget) => {
        const status = getBudgetStatus(budget);
        switch (status) {
          case 'Sesuai Target': return 'badge badge-success';
          case 'Di Atas Target':
            if (budget.chart_of_account?.account_type === 'Revenue') {
              return 'badge badge-success';
            }
            if (budget.chart_of_account?.account_type === 'Expense') {
              return 'badge badge-danger';
            }
            return 'badge badge-info';
          case 'Di Bawah Target':
            if (budget.chart_of_account?.account_type === 'Revenue') {
              return 'badge badge-danger';
            }
            if (budget.chart_of_account?.account_type === 'Expense') {
              return 'badge badge-success';
            }
            return 'badge badge-warning';
          default: return 'badge badge-secondary';
        }
      };

      const calculateVariancePercentage = (budget) => {
        if (!budget.budgeted_amount || budget.budgeted_amount === 0) return '0.00';
        const percentage = (budget.variance / budget.budgeted_amount) * 100;
        return percentage.toFixed(2);
      };

      const calculateRealizationPercentage = (budget) => {
        if (!budget.budgeted_amount || budget.budgeted_amount === 0) return '0.00';
        const percentage = (budget.actual_amount / budget.budgeted_amount) * 100;
        return percentage.toFixed(2);
      };

      const calculateRemainingBudget = (budget) => {
        return (budget.budgeted_amount || 0) - (budget.actual_amount || 0);
      };

      const getRemainingBudgetClass = (budget) => {
        const remaining = calculateRemainingBudget(budget);

        if (remaining < 0) {
          // Over budget is bad for expenses, good for revenue
          if (budget.chart_of_account?.account_type === 'Expense') {
            return 'text-danger';
          } else if (budget.chart_of_account?.account_type === 'Revenue') {
            return 'text-success';
          }
        }

        return '';
      };

      const getProgressBarClass = (budget) => {
        const realization = parseFloat(calculateRealizationPercentage(budget));

        if (budget.chart_of_account?.account_type === 'Revenue') {
          if (realization >= 100) return 'bg-success';
          if (realization >= 80) return 'bg-info';
          if (realization >= 50) return 'bg-warning';
          return 'bg-danger';
        } else if (budget.chart_of_account?.account_type === 'Expense') {
          if (realization > 100) return 'bg-danger';
          if (realization > 90) return 'bg-warning';
          return 'bg-success';
        }

        // Default for other account types
        if (realization > 100) return 'bg-danger';
        if (realization > 90) return 'bg-warning';
        return 'bg-info';
      };

      const getStatusIndicatorClass = (budget, type) => {
        if (type === 'progress') {
          const realization = parseFloat(calculateRealizationPercentage(budget));

          if (budget.chart_of_account?.account_type === 'Revenue') {
            if (realization >= 100) return 'indicator-success';
            if (realization >= 80) return 'indicator-info';
            if (realization >= 50) return 'indicator-warning';
            return 'indicator-danger';
          } else if (budget.chart_of_account?.account_type === 'Expense') {
            if (realization > 100) return 'indicator-danger';
            if (realization > 90) return 'indicator-warning';
            return 'indicator-success';
          }

          // Default
          if (realization > 100) return 'indicator-danger';
          if (realization > 90) return 'indicator-warning';
          return 'indicator-info';
        } else if (type === 'trend') {
          // Determine trend based on variance
          const variance = budget.variance || 0;

          if (budget.chart_of_account?.account_type === 'Revenue') {
            return variance >= 0 ? 'indicator-success' : 'indicator-danger';
          } else if (budget.chart_of_account?.account_type === 'Expense') {
            return variance <= 0 ? 'indicator-success' : 'indicator-danger';
          }

          // Default
          return variance >= 0 ? 'indicator-success' : 'indicator-danger';
        }

        return 'indicator-info';
      };

      const getProgressStatus = (budget) => {
        const realization = parseFloat(calculateRealizationPercentage(budget));

        if (budget.chart_of_account?.account_type === 'Revenue') {
          if (realization >= 100) return 'Tercapai';
          if (realization >= 80) return 'Hampir tercapai';
          if (realization >= 50) return 'Dalam proses';
          return 'Perlu perhatian';
        } else if (budget.chart_of_account?.account_type === 'Expense') {
          if (realization > 100) return 'Melebihi anggaran';
          if (realization > 90) return 'Mendekati batas';
          if (realization > 70) return 'Dalam kendali';
          return 'Efisien';
        }

        // Default
        if (realization > 100) return 'Melebihi anggaran';
        if (realization > 90) return 'Mendekati batas';
        if (realization > 50) return 'Dalam proses';
        return 'Masih tersedia';
      };

      const getTrendStatus = (budget) => {
        // Determine trend based on variance
        const variance = budget.variance || 0;

        if (budget.chart_of_account?.account_type === 'Revenue') {
          if (variance > 0) return 'Di atas target';
          if (variance === 0) return 'Sesuai target';
          return 'Di bawah target';
        } else if (budget.chart_of_account?.account_type === 'Expense') {
          if (variance < 0) return 'Di bawah anggaran';
          if (variance === 0) return 'Sesuai anggaran';
          return 'Melebihi anggaran';
        }

        // Default
        if (variance > 0) return 'Di atas anggaran';
        if (variance === 0) return 'Sesuai anggaran';
        return 'Di bawah anggaran';
      };

      // Lifecycle hooks
      onMounted(() => {
        loadBudget();
      });

      return {
        budgetId,
        isLoading,
        isLoadingTransactions,
        error,
        budget,
        transactions,
        months,
        monthlyDistribution,
        hasDistribution,
        getMonthlyActual,
        formatCurrency,
        formatDate,
        formatAccountType,
        getAccountTypeClass,
        getVarianceClass,
        getBudgetStatus,
        getBudgetStatusClass,
        calculateVariancePercentage,
        calculateRealizationPercentage,
        calculateRemainingBudget,
        getRemainingBudgetClass,
        getProgressBarClass,
        getStatusIndicatorClass,
        getProgressStatus,
        getTrendStatus
      };
    }
  };
  </script>

  <style scoped>
  .budget-detail {
    margin-bottom: 2rem;
  }

  .card {
    height: 100%;
  }

  .badge {
    padding: 0.4em 0.6em;
    font-size: 0.75em;
  }

  .table-details {
    margin-bottom: 0;
  }

  .table-details th {
    width: 40%;
    font-weight: 600;
  }

  .value-highlight {
    font-weight: 600;
    font-size: 1.1em;
  }

  .account-info {
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--gray-200);
  }

  .account-name {
    font-size: 1.25rem;
    margin-bottom: 0.25rem;
  }

  .account-code {
    color: var(--gray-600);
    font-family: monospace;
    margin-bottom: 0.5rem;
  }

  .progress {
    height: 1.5rem;
    border-radius: 0.5rem;
  }

  .progress-bar {
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    transition: width 0.5s ease;
  }

  .status-indicators {
    margin-top: 2rem;
  }

  .status-item {
    display: flex;
    align-items: center;
    margin-bottom: 1rem;
  }

  .status-indicator {
    width: 1rem;
    height: 1rem;
    border-radius: 50%;
    margin-right: 1rem;
  }

  .indicator-success {
    background-color: var(--success-color);
  }

  .indicator-danger {
    background-color: var(--danger-color);
  }

  .indicator-warning {
    background-color: var(--warning-color);
  }

  .indicator-info {
    background-color: var(--primary-color);
  }

  .status-detail {
    flex-grow: 1;
  }

  .status-label {
    color: var(--gray-500);
    font-size: 0.875rem;
  }

  .status-value {
    font-weight: 600;
  }

  .remaining-budget {
    background-color: var(--gray-50);
    padding: 1rem;
    border-radius: 0.5rem;
  }

  .chart-container {
    position: relative;
    height: 300px;
    width: 100%;
  }
  </style>
