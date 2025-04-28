<!-- src/views/accounting/VarianceAnalysis.vue -->
<template>
    <div class="variance-analysis">
      <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
          <h2>Analisis Varian Anggaran</h2>
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
                  @change="loadAnalysisData"
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
                <label for="analysis_type">Jenis Analisis</label>
                <select
                  id="analysis_type"
                  v-model="filters.analysisType"
                  class="form-control"
                  @change="loadAnalysisData"
                >
                  <option value="all">Semua Jenis Akun</option>
                  <option value="revenue">Analisis Pendapatan</option>
                  <option value="expense">Analisis Biaya</option>
                  <option value="significant">Varian Signifikan (> 10%)</option>
                </select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label for="sort_by">Urutkan Berdasarkan</label>
                <select
                  id="sort_by"
                  v-model="filters.sortBy"
                  class="form-control"
                  @change="loadAnalysisData"
                >
                  <option value="variance_amount">Jumlah Varian</option>
                  <option value="variance_percent">Persentase Varian</option>
                  <option value="account_name">Nama Akun</option>
                  <option value="budget_amount">Jumlah Anggaran</option>
                </select>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div v-if="isLoading" class="text-center py-5">
        <i class="fas fa-spinner fa-spin fa-2x"></i>
        <p class="mt-2">Memuat data analisis...</p>
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
        <!-- Main Analysis Dashboard -->
        <div class="col-12 mb-4">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">Rangkuman Analisis Varian</h3>
            </div>
            <div class="card-body">
              <div class="summary-metrics row">
                <div class="col-md-3">
                  <div class="metric-card favorable">
                    <div class="metric-icon">
                      <i class="fas fa-thumbs-up"></i>
                    </div>
                    <div class="metric-content">
                      <div class="metric-label">Varian Menguntungkan</div>
                      <div class="metric-value">{{ favorableVarianceCount }}</div>
                      <div class="metric-subtext">{{ formatCurrency(totalFavorableVariance) }}</div>
                    </div>
                  </div>
                </div>

                <div class="col-md-3">
                  <div class="metric-card unfavorable">
                    <div class="metric-icon">
                      <i class="fas fa-thumbs-down"></i>
                    </div>
                    <div class="metric-content">
                      <div class="metric-label">Varian Tidak Menguntungkan</div>
                      <div class="metric-value">{{ unfavorableVarianceCount }}</div>
                      <div class="metric-subtext">{{ formatCurrency(totalUnfavorableVariance) }}</div>
                    </div>
                  </div>
                </div>

                <div class="col-md-3">
                  <div class="metric-card neutral">
                    <div class="metric-icon">
                      <i class="fas fa-equals"></i>
                    </div>
                    <div class="metric-content">
                      <div class="metric-label">Sesuai Anggaran</div>
                      <div class="metric-value">{{ neutralVarianceCount }}</div>
                      <div class="metric-subtext">±5% dari anggaran</div>
                    </div>
                  </div>
                </div>

                <div class="col-md-3">
                  <div class="metric-card total">
                    <div class="metric-icon">
                      <i class="fas fa-calculator"></i>
                    </div>
                    <div class="metric-content">
                      <div class="metric-label">Varian Bersih</div>
                      <div class="metric-value" :class="getVarianceClass(netVariance)">
                        {{ formatCurrency(netVariance) }}
                      </div>
                      <div class="metric-subtext">{{ netVariancePercentage }}% dari anggaran</div>
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
              <h3 class="card-title">Varian per Kategori Akun</h3>
            </div>
            <div class="card-body">
              <div class="chart-container" style="height: 350px;">
                <canvas id="varianceByTypeChart"></canvas>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-6 mb-4">
          <div class="card h-100">
            <div class="card-header">
              <h3 class="card-title">Top 5 Varian Terbesar</h3>
            </div>
            <div class="card-body">
              <div class="chart-container" style="height: 350px;">
                <canvas id="topVariancesChart"></canvas>
              </div>
            </div>
          </div>
        </div>

        <!-- Variance Analysis Table -->
        <div class="col-12">
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h3 class="card-title">Detail Analisis Varian</h3>

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
                      <th>Akun</th>
                      <th>Jenis Akun</th>
                      <th class="text-right">Anggaran</th>
                      <th class="text-right">Aktual</th>
                      <th class="text-right">Varian</th>
                      <th class="text-right">% Varian</th>
                      <th>Status</th>
                      <th>Analisis</th>
                      <th>Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="budget in filteredBudgets" :key="budget.budget_id">
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
                        <span :class="getVarianceStatusClass(budget)">
                          {{ getVarianceStatus(budget) }}
                        </span>
                      </td>
                      <td>{{ generateVarianceAnalysis(budget) }}</td>
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
    name: 'VarianceAnalysis',
    setup() {
      const isLoading = ref(true);
      const isExporting = ref(false);
      const error = ref(null);
      const periods = ref([]);
      const budgets = ref([]);
      const filters = reactive({
        period_id: '',
        analysisType: 'all',
        sortBy: 'variance_amount'
      });

      let varianceByTypeChart = null;
      let topVariancesChart = null;

      // Computed properties
      const filteredBudgets = computed(() => {
        let result = [...budgets.value];

        // Apply analysis type filter
        if (filters.analysisType !== 'all') {
          switch (filters.analysisType) {
            case 'revenue':
              result = result.filter(budget =>
                budget.chart_of_account?.account_type === 'Revenue'
              );
              break;
            case 'expense':
              result = result.filter(budget =>
                budget.chart_of_account?.account_type === 'Expense'
              );
              break;
            case 'significant':
              result = result.filter(budget => {
                if (!budget.budgeted_amount || budget.budgeted_amount === 0) return false;
                const variancePercentage = Math.abs(budget.variance / budget.budgeted_amount) * 100;
                return variancePercentage > 10;
              });
              break;
          }
        }

        // Apply sorting
        return sortBudgets(result);
      });

      // Sort budgets based on selected sort option
      const sortBudgets = (budgetsToSort) => {
        switch (filters.sortBy) {
          case 'variance_amount':
            return [...budgetsToSort].sort((a, b) =>
              Math.abs(b.variance || 0) - Math.abs(a.variance || 0)
            );
          case 'variance_percent':
            return [...budgetsToSort].sort((a, b) => {
              const percentA = a.budgeted_amount ? Math.abs(a.variance / a.budgeted_amount) : 0;
              const percentB = b.budgeted_amount ? Math.abs(b.variance / b.budgeted_amount) : 0;
              return percentB - percentA;
            });
          case 'account_name':
            return [...budgetsToSort].sort((a, b) =>
              (a.chart_of_account?.name || '').localeCompare(b.chart_of_account?.name || '')
            );
          case 'budget_amount':
            return [...budgetsToSort].sort((a, b) =>
              (b.budgeted_amount || 0) - (a.budgeted_amount || 0)
            );
          default:
            return budgetsToSort;
        }
      };

      // Metrics for summary
      const favorableVarianceCount = computed(() => {
        return filteredBudgets.value.filter(budget => isFavorableVariance(budget)).length;
      });

      const unfavorableVarianceCount = computed(() => {
        return filteredBudgets.value.filter(budget => isUnfavorableVariance(budget)).length;
      });

      const neutralVarianceCount = computed(() => {
        return filteredBudgets.value.filter(budget => isNeutralVariance(budget)).length;
      });

      const totalFavorableVariance = computed(() => {
        return filteredBudgets.value
          .filter(budget => isFavorableVariance(budget))
          .reduce((sum, budget) => sum + Math.abs(budget.variance || 0), 0);
      });

      const totalUnfavorableVariance = computed(() => {
        return filteredBudgets.value
          .filter(budget => isUnfavorableVariance(budget))
          .reduce((sum, budget) => sum + Math.abs(budget.variance || 0), 0);
      });

      const netVariance = computed(() => {
        return filteredBudgets.value.reduce((sum, budget) => sum + (budget.variance || 0), 0);
      });

      const netVariancePercentage = computed(() => {
        const totalBudgeted = filteredBudgets.value.reduce((sum, budget) =>
          sum + (budget.budgeted_amount || 0), 0);

        if (!totalBudgeted || totalBudgeted === 0) return '0.00';

        const percentage = (netVariance.value / totalBudgeted) * 100;
        return percentage.toFixed(2);
      });

      // Helper for variance classification
      const isFavorableVariance = (budget) => {
        if (!budget.variance) return false;

        if (!budget.budgeted_amount || budget.budgeted_amount === 0) return false;

        const variancePercentage = (budget.variance / budget.budgeted_amount) * 100;

        if (Math.abs(variancePercentage) <= 5) return false;

        // For revenue accounts, positive variance is favorable
        if (budget.chart_of_account?.account_type === 'Revenue') {
          return budget.variance > 0;
        }

        // For expense accounts, negative variance is favorable
        if (budget.chart_of_account?.account_type === 'Expense') {
          return budget.variance < 0;
        }

        // For other accounts, we'll consider positive variance favorable
        return budget.variance > 0;
      };

      const isUnfavorableVariance = (budget) => {
        if (!budget.variance) return false;

        if (!budget.budgeted_amount || budget.budgeted_amount === 0) return false;

        const variancePercentage = (budget.variance / budget.budgeted_amount) * 100;

        if (Math.abs(variancePercentage) <= 5) return false;

        // For revenue accounts, negative variance is unfavorable
        if (budget.chart_of_account?.account_type === 'Revenue') {
          return budget.variance < 0;
        }

        // For expense accounts, positive variance is unfavorable
        if (budget.chart_of_account?.account_type === 'Expense') {
          return budget.variance > 0;
        }

        // For other accounts, we'll consider negative variance unfavorable
        return budget.variance < 0;
      };

      const isNeutralVariance = (budget) => {
        if (!budget.variance) return true;

        if (!budget.budgeted_amount || budget.budgeted_amount === 0) return false;

        const variancePercentage = (budget.variance / budget.budgeted_amount) * 100;

        return Math.abs(variancePercentage) <= 5;
      };

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

      const loadAnalysisData = async () => {
        isLoading.value = true;
        error.value = null;

        try {
          const params = {};

          if (filters.period_id) {
            params.period_id = filters.period_id;
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
        // Initialize variance by type chart
        initVarianceByTypeChart();

        // Initialize top variances chart
        initTopVariancesChart();
      };

      const initVarianceByTypeChart = () => {
        const canvas = document.getElementById('varianceByTypeChart');
        if (!canvas) return;

        // Destroy previous chart if exists
        if (varianceByTypeChart) {
          varianceByTypeChart.destroy();
        }

        // Group variances by account type
        const accountTypes = ['Revenue', 'Expense', 'Asset', 'Liability', 'Equity'];
        const typeData = accountTypes.map(type => {
          const typeBudgets = filteredBudgets.value.filter(
            budget => budget.chart_of_account?.account_type === type
          );

          return {
            type,
            favorable: typeBudgets.filter(budget => isFavorableVariance(budget))
              .reduce((sum, budget) => sum + Math.abs(budget.variance || 0), 0),
            unfavorable: typeBudgets.filter(budget => isUnfavorableVariance(budget))
              .reduce((sum, budget) => sum + Math.abs(budget.variance || 0), 0),
            neutral: typeBudgets.filter(budget => isNeutralVariance(budget))
              .reduce((sum, budget) => sum + Math.abs(budget.variance || 0), 0)
          };
        }).filter(item => item.favorable > 0 || item.unfavorable > 0 || item.neutral > 0);

        // Prepare data for chart
        const labels = typeData.map(item => formatAccountType(item.type));
        const favorableData = typeData.map(item => item.favorable);
        const unfavorableData = typeData.map(item => item.unfavorable);
        const neutralData = typeData.map(item => item.neutral);

        // Create chart
        varianceByTypeChart = new Chart(canvas, {
          type: 'bar',
          data: {
            labels,
            datasets: [
              {
                label: 'Menguntungkan',
                data: favorableData,
                backgroundColor: 'rgba(5, 150, 105, 0.7)',
                borderColor: 'rgba(5, 150, 105, 1)',
                borderWidth: 1
              },
              {
                label: 'Tidak Menguntungkan',
                data: unfavorableData,
                backgroundColor: 'rgba(220, 38, 38, 0.7)',
                borderColor: 'rgba(220, 38, 38, 1)',
                borderWidth: 1
              },
              {
                label: 'Netral',
                data: neutralData,
                backgroundColor: 'rgba(245, 158, 11, 0.7)',
                borderColor: 'rgba(245, 158, 11, 1)',
                borderWidth: 1
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
              x: {
                stacked: true
              },
              y: {
                stacked: true,
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

      const initTopVariancesChart = () => {
        const canvas = document.getElementById('topVariancesChart');
        if (!canvas) return;

        // Destroy previous chart if exists
        if (topVariancesChart) {
          topVariancesChart.destroy();
        }

        // Get top 5 variances by absolute variance amount
        const topVariances = [...filteredBudgets.value]
          .sort((a, b) => Math.abs(b.variance || 0) - Math.abs(a.variance || 0))
          .slice(0, 5);

        // Prepare data for chart
        const labels = topVariances.map(budget =>
          budget.chart_of_account?.name || `Account ${budget.account_id}`
        );

        const colors = topVariances.map(budget =>
          isFavorableVariance(budget) ? 'rgba(5, 150, 105, 0.7)' :
          isUnfavorableVariance(budget) ? 'rgba(220, 38, 38, 0.7)' : 'rgba(245, 158, 11, 0.7)'
        );

        const borderColors = topVariances.map(budget =>
          isFavorableVariance(budget) ? 'rgba(5, 150, 105, 1)' :
          isUnfavorableVariance(budget) ? 'rgba(220, 38, 38, 1)' : 'rgba(245, 158, 11, 1)'
        );

        const data = topVariances.map(budget => budget.variance || 0);

        // Create horizontal bar chart
        topVariancesChart = new Chart(canvas, {
          type: 'bar',
          data: {
            labels,
            datasets: [{
              label: 'Varian',
              data,
              backgroundColor: colors,
              borderColor: borderColors,
              borderWidth: 1
            }]
          },
          options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            scales: {
              x: {
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
              },
              y: {
                ticks: {
                  callback: function(value) {
                    const label = this.getLabelForValue(value);
                    // Truncate long labels
                    return label.length > 20 ? label.substr(0, 20) + '...' : label;
                  }
                }
              }
            },
            plugins: {
              tooltip: {
                callbacks: {
                  label: function(context) {
                    const budget = topVariances[context.dataIndex];
                    let label = 'Varian: ' + new Intl.NumberFormat('id-ID', {
                      style: 'currency',
                      currency: 'IDR'
                    }).format(context.raw);

                    const percentage = budget.budgeted_amount ?
                      (budget.variance / budget.budgeted_amount * 100).toFixed(2) + '%' : 'N/A';

                    label += ` (${percentage})`;
                    return label;
                  },
                  afterLabel: function(context) {
                    const budget = topVariances[context.dataIndex];
                    let budgetLabel = 'Anggaran: ' + new Intl.NumberFormat('id-ID', {
                      style: 'currency',
                      currency: 'IDR'
                    }).format(budget.budgeted_amount || 0);

                    let actualLabel = 'Aktual: ' + new Intl.NumberFormat('id-ID', {
                      style: 'currency',
                      currency: 'IDR'
                    }).format(budget.actual_amount || 0);

                    return [budgetLabel, actualLabel];
                  }
                }
              },
              legend: {
                display: false
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
          if (filters.analysisType !== 'all') params.analysis_type = filters.analysisType;

          // Call export API with params
          const response = await axios.get('/api/accounting/budgets/variance/export', {
            params,
            responseType: 'blob'
          });

          // Create a download link and trigger download
          const url = window.URL.createObjectURL(new Blob([response.data]));
          const link = document.createElement('a');
          link.href = url;
          link.setAttribute('download', 'variance-analysis-report.xlsx');
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

      // Generate text analysis for a budget variance
      const generateVarianceAnalysis = (budget) => {
        if (!budget.variance || !budget.budgeted_amount) {
          return 'Tidak ada data yang cukup untuk analisis';
        }

        const variancePercentage = (budget.variance / budget.budgeted_amount) * 100;

        if (Math.abs(variancePercentage) <= 5) {
          return 'Varian tidak signifikan, kinerja sesuai anggaran';
        }

        let analysis = '';

        // For revenue accounts
        if (budget.chart_of_account?.account_type === 'Revenue') {
          if (variancePercentage > 0) {
            analysis = `Pendapatan melebihi target sebesar ${variancePercentage.toFixed(1)}%. `;
            if (variancePercentage > 20) {
              analysis += 'Perlu evaluasi apakah anggaran terlalu konservatif.';
            } else {
              analysis += 'Kinerja sangat baik.';
            }
          } else {
            analysis = `Pendapatan di bawah target sebesar ${Math.abs(variancePercentage).toFixed(1)}%. `;
            if (Math.abs(variancePercentage) > 20) {
              analysis += 'Perlu investigasi lebih lanjut & tindakan korektif.';
            } else {
              analysis += 'Perlu perhatian untuk mencapai target.';
            }
          }
        }
        // For expense accounts
        else if (budget.chart_of_account?.account_type === 'Expense') {
          if (variancePercentage < 0) {
            analysis = `Pengeluaran di bawah anggaran sebesar ${Math.abs(variancePercentage).toFixed(1)}%. `;
            if (Math.abs(variancePercentage) > 20) {
              analysis += 'Evaluasi apakah anggaran terlalu tinggi atau ada penundaan pengeluaran.';
            } else {
              analysis += 'Efisiensi pengeluaran baik.';
            }
          } else {
            analysis = `Pengeluaran melebihi anggaran sebesar ${variancePercentage.toFixed(1)}%. `;
            if (variancePercentage > 20) {
              analysis += 'Perlu tindakan kontrol biaya segera.';
            } else {
              analysis += 'Perlu perhatian untuk kontrol biaya.';
            }
          }
        }
        // For other account types
        else {
          if (variancePercentage > 0) {
            analysis = `Nilai aktual melebihi anggaran sebesar ${variancePercentage.toFixed(1)}%.`;
          } else {
            analysis = `Nilai aktual di bawah anggaran sebesar ${Math.abs(variancePercentage).toFixed(1)}%.`;
          }
        }

        return analysis;
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

      const getVarianceStatus = (budget) => {
        if (!budget.variance || !budget.budgeted_amount) return 'Tidak Ada Data';

        const variancePercentage = (budget.variance / budget.budgeted_amount) * 100;

        if (Math.abs(variancePercentage) <= 5) {
          return 'Sesuai Anggaran';
        }

        // For revenue accounts
        if (budget.chart_of_account?.account_type === 'Revenue') {
          return variancePercentage > 0 ? 'Menguntungkan' : 'Tidak Menguntungkan';
        }
        // For expense accounts
        else if (budget.chart_of_account?.account_type === 'Expense') {
          return variancePercentage < 0 ? 'Menguntungkan' : 'Tidak Menguntungkan';
        }
        // For other account types
        else {
          return variancePercentage > 0 ? 'Di Atas Anggaran' : 'Di Bawah Anggaran';
        }
      };

      const getVarianceStatusClass = (budget) => {
        if (!budget.variance || !budget.budgeted_amount) return 'badge badge-secondary';

        const variancePercentage = (budget.variance / budget.budgeted_amount) * 100;

        if (Math.abs(variancePercentage) <= 5) {
          return 'badge badge-info';
        }

        if (isFavorableVariance(budget)) {
          return 'badge badge-success';
        } else if (isUnfavorableVariance(budget)) {
          return 'badge badge-danger';
        } else {
          return 'badge badge-secondary';
        }
      };

      // Watch for filter changes
      watch(filters, () => {
        loadAnalysisData();
      }, { deep: true });

      // Lifecycle hooks
      onMounted(async () => {
        // Load periods first, then load budget data
        await loadPeriods();
        loadAnalysisData();
      });

      return {
        isLoading,
        isExporting,
        error,
        periods,
        budgets,
        filters,
        filteredBudgets,
        favorableVarianceCount,
        unfavorableVarianceCount,
        neutralVarianceCount,
        totalFavorableVariance,
        totalUnfavorableVariance,
        netVariance,
        netVariancePercentage,
        exportToExcel,
        printReport,
        formatCurrency,
        formatAccountType,
        getAccountTypeClass,
        getVarianceClass,
        calculateVariancePercentage,
        getVarianceStatus,
        getVarianceStatusClass,
        generateVarianceAnalysis
      };
    }
  };
  </script>

  <style scoped>
  .variance-analysis {
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
    height: 350px;
    width: 100%;
  }

  .summary-metrics {
    margin-bottom: 1.5rem;
  }

  .metric-card {
    display: flex;
    padding: 1rem;
    border-radius: 0.5rem;
    background-color: var(--gray-50);
    border-left: 4px solid;
    height: 100%;
  }

  .metric-card.favorable {
    border-left-color: var(--success-color);
  }

  .metric-card.unfavorable {
    border-left-color: var(--danger-color);
  }

  .metric-card.neutral {
    border-left-color: var(--warning-color);
  }

  .metric-card.total {
    border-left-color: var(--primary-color);
  }

  .metric-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 3rem;
    height: 3rem;
    border-radius: 50%;
    margin-right: 1rem;
    font-size: 1.25rem;
  }

  .favorable .metric-icon {
    background-color: rgba(5, 150, 105, 0.1);
    color: var(--success-color);
  }

  .unfavorable .metric-icon {
    background-color: rgba(220, 38, 38, 0.1);
    color: var(--danger-color);
  }

  .neutral .metric-icon {
    background-color: rgba(245, 158, 11, 0.1);
    color: var(--warning-color);
  }

  .total .metric-icon {
    background-color: rgba(37, 99, 235, 0.1);
    color: var(--primary-color);
  }

  .metric-content {
    flex-grow: 1;
  }

  .metric-label {
    font-size: 0.875rem;
    color: var(--gray-600);
    margin-bottom: 0.5rem;
  }

  .metric-value {
    font-size: 1.5rem;
    font-weight: 600;
    line-height: 1.2;
  }

  .metric-subtext {
    font-size: 0.875rem;
    color: var(--gray-500);
    margin-top: 0.25rem;
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
