<!-- src/views/accounting/BudgetVsActual.vue -->
<template>
    <div class="budget-vs-actual">
      <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
          <h2>Anggaran vs Aktual</h2>
          <div class="action-buttons">
            <router-link to="/accounting/budgets" class="btn btn-outline-secondary">
              <i class="fas fa-arrow-left mr-1"></i> Kembali
            </router-link>
          </div>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-body">
          <!-- Filter Controls -->
          <div class="filter-controls row">
            <div class="col-md-4">
              <div class="form-group">
                <label for="period_id">Periode</label>
                <select
                  id="period_id"
                  v-model="filters.period_id"
                  class="form-control"
                  @change="loadBudgetData"
                >
                  <option value="">Semua Periode</option>
                  <option v-for="period in periods" :key="period.period_id" :value="period.period_id">
                    {{ period.period_name }}
                  </option>
                </select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label for="account_type">Jenis Akun</label>
                <select
                  id="account_type"
                  v-model="filters.account_type"
                  class="form-control"
                  @change="loadBudgetData"
                >
                  <option value="">Semua Jenis</option>
                  <option value="Revenue">Pendapatan</option>
                  <option value="Expense">Biaya</option>
                  <option value="Asset">Aset</option>
                  <option value="Liability">Kewajiban</option>
                  <option value="Equity">Ekuitas</option>
                </select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label for="variance_filter">Filter Varian</label>
                <select
                  id="variance_filter"
                  v-model="filters.variance"
                  class="form-control"
                  @change="loadBudgetData"
                >
                  <option value="">Semua</option>
                  <option value="over">Di Atas Anggaran</option>
                  <option value="under">Di Bawah Anggaran</option>
                  <option value="within">Sesuai Anggaran (±5%)</option>
                </select>
              </div>
            </div>
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

      <div v-else-if="filteredBudgets.length === 0" class="alert alert-info" role="alert">
        <i class="fas fa-info-circle mr-2"></i>
        Tidak ada data anggaran yang sesuai dengan filter
      </div>

      <div v-else class="row">
        <!-- Summary Cards -->
        <div class="col-12">
          <div class="summary-cards mb-4">
            <div class="row">
              <div class="col-md-3">
                <div class="card bg-light">
                  <div class="card-body">
                    <h5 class="card-title">Total Anggaran</h5>
                    <div class="card-value">{{ formatCurrency(totalBudgeted) }}</div>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="card bg-light">
                  <div class="card-body">
                    <h5 class="card-title">Total Aktual</h5>
                    <div class="card-value">{{ formatCurrency(totalActual) }}</div>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="card bg-light">
                  <div class="card-body">
                    <h5 class="card-title">Total Varian</h5>
                    <div :class="['card-value', getVarianceClass(totalVariance)]">
                      {{ formatCurrency(totalVariance) }}
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="card bg-light">
                  <div class="card-body">
                    <h5 class="card-title">Persentase Varian</h5>
                    <div :class="['card-value', getVarianceClass(totalVariance)]">
                      {{ totalVariancePercentage }}%
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Charts Section -->
        <div class="col-md-6 mb-4">
          <div class="card h-100">
            <div class="card-header">
              <h3 class="card-title">Grafik Anggaran vs Aktual</h3>
            </div>
            <div class="card-body">
              <div class="chart-container" style="height: 300px;">
                <canvas id="budgetVsActualChart"></canvas>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-6 mb-4">
          <div class="card h-100">
            <div class="card-header">
              <h3 class="card-title">Distribusi Varian</h3>
            </div>
            <div class="card-body">
              <div class="chart-container" style="height: 300px;">
                <canvas id="varianceDistributionChart"></canvas>
              </div>
            </div>
          </div>
        </div>

        <!-- Data Table -->
        <div class="col-12">
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h3 class="card-title">Detail Anggaran vs Aktual</h3>

              <div class="btn-group">
                <button
                  @click="exportToExcel"
                  class="btn btn-sm btn-success"
                  :disabled="isExporting"
                >
                  <i :class="isExporting ? 'fas fa-spinner fa-spin' : 'fas fa-file-excel'"></i>
                  {{ isExporting ? 'Exporting...' : 'Export Excel' }}
                </button>
                <button
                  @click="printReport"
                  class="btn btn-sm btn-info"
                >
                  <i class="fas fa-print"></i> Print
                </button>
              </div>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>Periode</th>
                      <th>Akun</th>
                      <th>Jenis Akun</th>
                      <th class="text-right">Anggaran</th>
                      <th class="text-right">Aktual</th>
                      <th class="text-right">Varian</th>
                      <th class="text-right">% Varian</th>
                      <th>Status</th>
                      <th>Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="budget in filteredBudgets" :key="budget.budget_id">
                      <td>{{ budget.accounting_period?.period_name || '-' }}</td>
                      <td>
                        <div class="d-flex flex-column">
                          <span class="font-weight-medium">{{ budget.chart_of_account?.name || '-' }}</span>
                          <small class="text-muted">{{ budget.chart_of_account?.account_code || '-' }}</small>
                        </div>
                      </td>
                      <td>
                        <span :class="getAccountTypeClass(budget.chart_of_account?.account_type)">
                          {{ formatAccountType(budget.chart_of_account?.account_type) }}
                        </span>
                      </td>
                      <td class="text-right">{{ formatCurrency(budget.budgeted_amount) }}</td>
                      <td class="text-right">{{ formatCurrency(budget.actual_amount) }}</td>
                      <td class="text-right" :class="getVarianceClass(budget.variance)">
                        {{ formatCurrency(budget.variance) }}
                      </td>
                      <td class="text-right" :class="getVarianceClass(budget.variance)">
                        {{ calculateVariancePercentage(budget) }}%
                      </td>
                      <td>
                        <span :class="getBudgetStatusClass(budget)">
                          {{ getBudgetStatus(budget) }}
                        </span>
                      </td>
                      <td>
                        <router-link
                          :to="`/accounting/budgets/${budget.budget_id}`"
                          class="btn btn-sm btn-info"
                          title="Detail"
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
  import { ref, reactive, computed, onMounted, watch } from 'vue';
  import Chart from 'chart.js/auto';

  export default {
    name: 'BudgetVsActual',
    setup() {
      const isLoading = ref(true);
      const isExporting = ref(false);
      const error = ref(null);
      const periods = ref([]);
      const budgets = ref([]);
      const filters = reactive({
        period_id: '',
        account_type: '',
        variance: ''
      });

      let budgetVsActualChart = null;
      let varianceDistributionChart = null;

      // Computed properties
      const filteredBudgets = computed(() => {
        let result = budgets.value;

        // Apply variance filter if set
        if (filters.variance) {
          result = result.filter(budget => {
            if (!budget.budgeted_amount || budget.budgeted_amount === 0) return false;

            const variancePercentage = (budget.variance / budget.budgeted_amount) * 100;

            switch (filters.variance) {
              case 'over':
                // For revenue accounts, over budget is positive variance
                // For expense accounts, over budget is positive variance
                return variancePercentage > 5;
              case 'under':
                // For revenue accounts, under budget is negative variance
                // For expense accounts, under budget is negative variance
                return variancePercentage < -5;
              case 'within':
                // Within budget is variance between -5% and 5%
                return Math.abs(variancePercentage) <= 5;
              default:
                return true;
            }
          });
        }

        return result;
      });

      const totalBudgeted = computed(() => {
        return filteredBudgets.value.reduce((sum, budget) => sum + (budget.budgeted_amount || 0), 0);
      });

      const totalActual = computed(() => {
        return filteredBudgets.value.reduce((sum, budget) => sum + (budget.actual_amount || 0), 0);
      });

      const totalVariance = computed(() => {
        return filteredBudgets.value.reduce((sum, budget) => sum + (budget.variance || 0), 0);
      });

      const totalVariancePercentage = computed(() => {
        if (!totalBudgeted.value || totalBudgeted.value === 0) return '0.00';
        const percentage = (totalVariance.value / totalBudgeted.value) * 100;
        return percentage.toFixed(2);
      });

      // Methods
      const loadPeriods = async () => {
        try {
          const response = await axios.get('/api/accounting/accounting-periods');
          periods.value = response.data.data || response.data;

          // Set default period to latest if available
          if (periods.value.length > 0) {
            // Sort periods by start date in descending order
            const sortedPeriods = [...periods.value].sort((a, b) =>
              new Date(b.start_date) - new Date(a.start_date)
            );

            // Set first (latest) period as default
            filters.period_id = sortedPeriods[0].period_id;
          }
        } catch (err) {
          console.error('Error loading periods:', err);
        }
      };

      const loadBudgetData = async () => {
        isLoading.value = true;
        error.value = null;

        try {
          const params = {};

          if (filters.period_id) {
            params.period_id = filters.period_id;
          }

          if (filters.account_type) {
            params.account_type = filters.account_type;
          }

          const response = await axios.get('/api/accounting/budgets', { params });
          budgets.value = response.data.data || response.data;

          // Initialize/update charts after data is loaded
          initializeCharts();
        } catch (err) {
          console.error('Error loading budget data:', err);
          error.value = 'Gagal memuat data anggaran. Silakan coba lagi nanti.';
        } finally {
          isLoading.value = false;
        }
      };

      const initializeCharts = () => {
        // Initialize budget vs actual chart
        initBudgetVsActualChart();

        // Initialize variance distribution chart
        initVarianceDistributionChart();
      };

      const initBudgetVsActualChart = () => {
        const canvas = document.getElementById('budgetVsActualChart');
        if (!canvas) return;

        // Destroy previous chart if exists
        if (budgetVsActualChart) {
          budgetVsActualChart.destroy();
        }

        // Prepare data for chart - use top 10 accounts by budget amount
        const topAccounts = [...filteredBudgets.value]
          .sort((a, b) => b.budgeted_amount - a.budgeted_amount)
          .slice(0, 10);

        const labels = topAccounts.map(budget =>
          budget.chart_of_account?.name || `Account ${budget.account_id}`
        );

        const budgetedData = topAccounts.map(budget => budget.budgeted_amount || 0);
        const actualData = topAccounts.map(budget => budget.actual_amount || 0);

        // Create chart
        budgetVsActualChart = new Chart(canvas, {
          type: 'bar',
          data: {
            labels,
            datasets: [
              {
                label: 'Anggaran',
                data: budgetedData,
                backgroundColor: 'rgba(37, 99, 235, 0.7)',
                borderColor: 'rgba(37, 99, 235, 1)',
                borderWidth: 1
              },
              {
                label: 'Aktual',
                data: actualData,
                backgroundColor: 'rgba(5, 150, 105, 0.7)',
                borderColor: 'rgba(5, 150, 105, 1)',
                borderWidth: 1
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
              x: {
                ticks: {
                  callback: function(value) {
                    const label = this.getLabelForValue(value);
                    // Truncate long labels
                    return label.length > 15 ? label.substr(0, 15) + '...' : label;
                  }
                }
              },
              y: {
                beginAtZero: true,
                ticks: {
                  callback: function(value) {
                    return new Intl.NumberFormat('id-ID', {
                      style: 'currency',
                      currency: 'IDR',
                      notation: 'compact',
                      compactDisplay: 'short'
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
                      currency: 'IDR'
                    }).format(context.raw);
                    return label;
                  }
                }
              },
              legend: {
                position: 'top'
              }
            }
          }
        });
      };

      const initVarianceDistributionChart = () => {
        const canvas = document.getElementById('varianceDistributionChart');
        if (!canvas) return;

        // Destroy previous chart if exists
        if (varianceDistributionChart) {
          varianceDistributionChart.destroy();
        }

        // Count budgets in different variance categories
        const overBudget = filteredBudgets.value.filter(budget => {
          if (!budget.budgeted_amount || budget.budgeted_amount === 0) return false;
          const variancePercentage = (budget.variance / budget.budgeted_amount) * 100;
          return variancePercentage > 5;
        }).length;

        const underBudget = filteredBudgets.value.filter(budget => {
          if (!budget.budgeted_amount || budget.budgeted_amount === 0) return false;
          const variancePercentage = (budget.variance / budget.budgeted_amount) * 100;
          return variancePercentage < -5;
        }).length;

        const withinBudget = filteredBudgets.value.filter(budget => {
          if (!budget.budgeted_amount || budget.budgeted_amount === 0) return false;
          const variancePercentage = (budget.variance / budget.budgeted_amount) * 100;
          return Math.abs(variancePercentage) <= 5;
        }).length;

        // Create pie chart
        varianceDistributionChart = new Chart(canvas, {
          type: 'pie',
          data: {
            labels: ['Di Atas Anggaran', 'Sesuai Anggaran', 'Di Bawah Anggaran'],
            datasets: [{
              data: [overBudget, withinBudget, underBudget],
              backgroundColor: [
                'rgba(220, 38, 38, 0.7)',   // red for over budget
                'rgba(5, 150, 105, 0.7)',    // green for within budget
                'rgba(245, 158, 11, 0.7)'    // amber for under budget
              ],
              borderColor: [
                'rgba(220, 38, 38, 1)',
                'rgba(5, 150, 105, 1)',
                'rgba(245, 158, 11, 1)'
              ],
              borderWidth: 1
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              tooltip: {
                callbacks: {
                  label: function(context) {
                    const label = context.label || '';
                    const value = context.raw;
                    const percentage = Math.round((value / filteredBudgets.value.length) * 100);
                    return `${label}: ${value} (${percentage}%)`;
                  }
                }
              },
              legend: {
                position: 'bottom'
              }
            }
          }
        });
      };

      const exportToExcel = async () => {
        isExporting.value = true;

        try {
          // In a real implementation, this would call an API endpoint to generate and download the Excel file
          // For this example, we'll simulate a delay and then show a success message

          // Create parameters for the export request
          const params = {};
          if (filters.period_id) params.period_id = filters.period_id;
          if (filters.account_type) params.account_type = filters.account_type;

          // Call export API with params
          const response = await axios.get('/api/accounting/budgets/export', {
            params,
            responseType: 'blob'
          });

          // Create a download link and trigger download
          const url = window.URL.createObjectURL(new Blob([response.data]));
          const link = document.createElement('a');
          link.href = url;
          link.setAttribute('download', 'budget-vs-actual-report.xlsx');
          document.body.appendChild(link);
          link.click();
          document.body.removeChild(link);

          // Show success message (you might want to implement toast notifications)
          alert('Report exported successfully');
        } catch (err) {
          console.error('Error exporting data:', err);
          alert('Failed to export report. Please try again.');
        } finally {
          isExporting.value = false;
        }
      };

      const printReport = () => {
        window.print();
      };

      // Utility functions
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
          default: return type || '-';
        }
      };

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
        return variance < 0 ? 'text-danger' : 'text-success';
      };

      const calculateVariancePercentage = (budget) => {
        if (!budget.budgeted_amount || budget.budgeted_amount === 0) return '0.00';
        const percentage = (budget.variance / budget.budgeted_amount) * 100;
        return percentage.toFixed(2);
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

      // Watch for filter changes
      watch(filters, () => {
        loadBudgetData();
      }, { deep: true });

      // Lifecycle hooks
      onMounted(async () => {
        // Load periods first, then load budget data
        await loadPeriods();
        loadBudgetData();
      });

      return {
        isLoading,
        isExporting,
        error,
        periods,
        budgets,
        filters,
        filteredBudgets,
        totalBudgeted,
        totalActual,
        totalVariance,
        totalVariancePercentage,
        loadBudgetData,
        exportToExcel,
        printReport,
        formatCurrency,
        formatAccountType,
        getAccountTypeClass,
        getVarianceClass,
        calculateVariancePercentage,
        getBudgetStatus,
        getBudgetStatusClass
      };
    }
  };
  </script>

  <style scoped>
  .budget-vs-actual {
    margin-bottom: 2rem;
  }

  .card {
    height: 100%;
  }

  .badge {
    padding: 0.4em 0.6em;
    font-size: 0.75em;
  }

  .chart-container {
    position: relative;
    height: 300px;
    width: 100%;
  }

  .summary-cards .card {
    text-align: center;
    padding: 0.5rem;
  }

  .card-title {
    font-size: 1rem;
    color: var(--gray-700);
    margin-bottom: 0.75rem;
  }

  .card-value {
    font-size: 1.5rem;
    font-weight: 600;
  }

  @media print {
    .filter-controls,
    .action-buttons,
    .btn-group,
    th:last-child,
    td:last-child {
      display: none !important;
    }

    .card {
      border: none !important;
      box-shadow: none !important;
      margin-bottom: 1rem !important;
    }

    .card-header {
      background-color: white !important;
      border-bottom: 2px solid #333 !important;
    }
  }
  </style>
