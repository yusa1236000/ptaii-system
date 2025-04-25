<!-- src/views/accounting/receivables/ReceivableAgingReport.vue -->
<template>
    <div class="aging-report">
      <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
          <h2>Laporan Aging Piutang</h2>
          <div class="actions">
            <router-link to="/accounting/receivables" class="btn btn-outline-secondary mr-2">
              <i class="fas fa-arrow-left mr-1"></i> Kembali
            </router-link>
            <button class="btn btn-primary" @click="printReport">
              <i class="fas fa-print mr-1"></i> Cetak Laporan
            </button>
          </div>
        </div>
      </div>

      <!-- Filter Form -->
      <div class="card mb-4">
        <div class="card-body">
          <div class="d-flex flex-wrap align-items-end">
            <div class="form-group mr-3 mb-2">
              <label for="asOfDate">Per Tanggal</label>
              <input
                type="date"
                id="asOfDate"
                v-model="filters.asOfDate"
                class="form-control"
                @change="generateReport"
              />
            </div>
            <div class="form-group mr-3 mb-2">
              <label for="customerId">Pelanggan</label>
              <select
                id="customerId"
                v-model="filters.customerId"
                class="form-control"
                @change="generateReport"
              >
                <option value="">Semua Pelanggan</option>
                <option v-for="customer in customers" :key="customer.customer_id" :value="customer.customer_id">
                  {{ customer.name }}
                </option>
              </select>
            </div>
            <div class="form-group mr-3 mb-2">
              <label for="minAmount">Minimal Jumlah</label>
              <input
                type="number"
                id="minAmount"
                v-model="filters.minAmount"
                class="form-control"
                placeholder="Minimal jumlah"
                min="0"
                @change="applyFilters"
              />
            </div>
            <div class="form-group mb-2">
              <button class="btn btn-secondary" @click="resetFilters">
                <i class="fas fa-redo mr-1"></i> Reset
              </button>
            </div>
          </div>
        </div>
      </div>

      <div v-if="isLoading" class="text-center py-5">
        <i class="fas fa-spinner fa-spin fa-2x"></i>
        <p class="mt-2">Memuat data aging...</p>
      </div>

      <div v-else-if="error" class="alert alert-danger" role="alert">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        {{ error }}
      </div>

      <div v-else id="printableReport">
        <!-- Report Header -->
        <div class="report-header">
          <h2 class="report-title">Laporan Aging Piutang</h2>
          <p class="report-subtitle">
            Per Tanggal: <strong>{{ formatDate(filters.asOfDate) }}</strong>
          </p>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4">
          <div class="col-md-3 mb-3">
            <div class="summary-card current">
              <div class="summary-title">Belum Jatuh Tempo</div>
              <div class="summary-value">{{ formatCurrency(totals.current_amount) }}</div>
              <div class="summary-percent">{{ calculatePercentage(totals.current_amount, totals.total_balance) }}%</div>
            </div>
          </div>
          <div class="col-md-3 mb-3">
            <div class="summary-card days-1-30">
              <div class="summary-title">1 - 30 Hari</div>
              <div class="summary-value">{{ formatCurrency(totals.days_1_30) }}</div>
              <div class="summary-percent">{{ calculatePercentage(totals.days_1_30, totals.total_balance) }}%</div>
            </div>
          </div>
          <div class="col-md-3 mb-3">
            <div class="summary-card days-31-60">
              <div class="summary-title">31 - 60 Hari</div>
              <div class="summary-value">{{ formatCurrency(totals.days_31_60) }}</div>
              <div class="summary-percent">{{ calculatePercentage(totals.days_31_60, totals.total_balance) }}%</div>
            </div>
          </div>
          <div class="col-md-3 mb-3">
            <div class="summary-card days-over-60">
              <div class="summary-title">> 60 Hari</div>
              <div class="summary-value">{{ formatCurrency(totals.days_over_90) }}</div>
              <div class="summary-percent">{{ calculatePercentage(totals.days_over_90, totals.total_balance) }}%</div>
            </div>
          </div>
        </div>

        <!-- Pie Chart -->
        <div class="row mb-4">
          <div class="col-md-6">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Distribusi Aging Piutang</h3>
              </div>
              <div class="card-body">
                <div class="chart-container">
                  <canvas id="agingPieChart" ref="pieChartRef"></canvas>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Total Piutang</h3>
              </div>
              <div class="card-body">
                <div class="total-summary">
                  <div class="total-item">
                    <div class="total-label">Total Piutang</div>
                    <div class="total-value">{{ formatCurrency(totals.total_balance) }}</div>
                  </div>
                  <div class="total-item">
                    <div class="total-label">Jumlah Pelanggan</div>
                    <div class="total-value">{{ agingData.length }}</div>
                  </div>
                  <div class="total-item" v-if="totalOverdue > 0">
                    <div class="total-label text-danger">Total Jatuh Tempo</div>
                    <div class="total-value text-danger">{{ formatCurrency(totalOverdue) }}</div>
                  </div>
                  <div class="total-item" v-if="totalOverdue > 0">
                    <div class="total-label">Persentase Jatuh Tempo</div>
                    <div class="total-value">{{ calculatePercentage(totalOverdue, totals.total_balance) }}%</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Detailed Table -->
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Detail Aging Piutang per Pelanggan</h3>
          </div>
          <div class="card-body">
            <div v-if="filteredAgingData.length === 0" class="alert alert-info">
              <i class="fas fa-info-circle mr-2"></i>
              Tidak ada data piutang untuk ditampilkan
            </div>
            <div v-else class="table-responsive">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Pelanggan</th>
                    <th class="text-right">Belum Jatuh Tempo</th>
                    <th class="text-right">1-30 Hari</th>
                    <th class="text-right">31-60 Hari</th>
                    <th class="text-right">61-90 Hari</th>
                    <th class="text-right">> 90 Hari</th>
                    <th class="text-right">Total</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="aging in filteredAgingData" :key="aging.customer_id">
                    <td>{{ aging.customer_name }}</td>
                    <td class="text-right">{{ formatCurrency(aging.current_amount) }}</td>
                    <td class="text-right">{{ formatCurrency(aging.days_1_30) }}</td>
                    <td class="text-right">{{ formatCurrency(aging.days_31_60) }}</td>
                    <td class="text-right">{{ formatCurrency(aging.days_61_90) }}</td>
                    <td class="text-right">{{ formatCurrency(aging.days_over_90) }}</td>
                    <td class="text-right font-weight-bold">{{ formatCurrency(aging.total_balance) }}</td>
                    <td>
                      <router-link
                        :to="`/accounting/customers/${aging.customer_id}/statement`"
                        class="btn btn-sm btn-info"
                      >
                        <i class="fas fa-file-alt mr-1"></i> Statement
                      </router-link>
                    </td>
                  </tr>
                </tbody>
                <tfoot>
                  <tr class="font-weight-bold">
                    <td>Total</td>
                    <td class="text-right">{{ formatCurrency(totals.current_amount) }}</td>
                    <td class="text-right">{{ formatCurrency(totals.days_1_30) }}</td>
                    <td class="text-right">{{ formatCurrency(totals.days_31_60) }}</td>
                    <td class="text-right">{{ formatCurrency(totals.days_61_90) }}</td>
                    <td class="text-right">{{ formatCurrency(totals.days_over_90) }}</td>
                    <td class="text-right">{{ formatCurrency(totals.total_balance) }}</td>
                    <td></td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </template>

  <script>
  import { ref, reactive, computed, onMounted, watch } from 'vue';
  import axios from 'axios';
  import Chart from 'chart.js/auto';

  export default {
    name: 'ReceivableAgingReport',
    setup() {
      // State
      const isLoading = ref(true);
      const error = ref(null);
      const agingData = ref([]);
      const customers = ref([]);
      const pieChart = ref(null);
      const pieChartRef = ref(null);

      // Filters
      const filters = reactive({
        asOfDate: new Date().toISOString().split('T')[0],
        customerId: '',
        minAmount: ''
      });

      // Computed properties
      const filteredAgingData = computed(() => {
        let filtered = [...agingData.value];

        // Filter by customer
        if (filters.customerId) {
          filtered = filtered.filter(item => item.customer_id == filters.customerId);
        }

        // Filter by minimum amount
        if (filters.minAmount && !isNaN(parseFloat(filters.minAmount))) {
          const minAmount = parseFloat(filters.minAmount);
          filtered = filtered.filter(item => item.total_balance >= minAmount);
        }

        return filtered;
      });

      const totals = computed(() => {
        return {
          current_amount: sum(filteredAgingData.value, 'current_amount'),
          days_1_30: sum(filteredAgingData.value, 'days_1_30'),
          days_31_60: sum(filteredAgingData.value, 'days_31_60'),
          days_61_90: sum(filteredAgingData.value, 'days_61_90'),
          days_over_90: sum(filteredAgingData.value, 'days_over_90'),
          total_balance: sum(filteredAgingData.value, 'total_balance')
        };
      });

      const totalOverdue = computed(() => {
        return totals.value.days_1_30 +
               totals.value.days_31_60 +
               totals.value.days_61_90 +
               totals.value.days_over_90;
      });

      // Helper function to sum a property across an array of objects
      const sum = (array, property) => {
        return array.reduce((acc, item) => acc + parseFloat(item[property] || 0), 0);
      };

      // Load customers
      const loadCustomers = async () => {
        try {
          const response = await axios.get('/api/sales/customers');
          customers.value = response.data.data || response.data;
        } catch (err) {
          console.error('Error loading customers:', err);
        }
      };

      // Generate aging report
      const generateReport = async () => {
        isLoading.value = true;
        error.value = null;

        try {
          const response = await axios.get('/api/accounting/customer-receivables/aging', {
            params: { as_of_date: filters.asOfDate }
          });

          agingData.value = response.data.data || [];

          // If we have a specific chart instance, destroy it to prevent memory leaks
          if (pieChart.value) {
            pieChart.value.destroy();
          }

          // Check if the chart reference exists and has been mounted in the DOM
          if (pieChartRef.value) {
            // Draw the pie chart on the next tick to ensure the DOM is fully updated
            setTimeout(() => {
              drawPieChart();
            }, 0);
          }
        } catch (err) {
          console.error('Error generating aging report:', err);
          error.value = 'Gagal memuat laporan aging. Silakan coba lagi nanti.';
        } finally {
          isLoading.value = false;
        }
      };

      // Apply filters to existing data
      const applyFilters = () => {
        // No need to reload data from server, just let computed properties do their job

        // If we have a specific chart instance, destroy it to prevent memory leaks
        if (pieChart.value) {
          pieChart.value.destroy();
        }

        // Redraw the chart with filtered data
        setTimeout(() => {
          drawPieChart();
        }, 0);
      };

      // Reset filters
      const resetFilters = () => {
        filters.asOfDate = new Date().toISOString().split('T')[0];
        filters.customerId = '';
        filters.minAmount = '';
        generateReport();
      };

      // Draw pie chart
      const drawPieChart = () => {
        if (!pieChartRef.value) return;

        const ctx = pieChartRef.value.getContext('2d');

        pieChart.value = new Chart(ctx, {
          type: 'pie',
          data: {
            labels: [
              'Belum Jatuh Tempo',
              '1-30 Hari',
              '31-60 Hari',
              '61-90 Hari',
              '> 90 Hari'
            ],
            datasets: [{
              data: [
                totals.value.current_amount,
                totals.value.days_1_30,
                totals.value.days_31_60,
                totals.value.days_61_90,
                totals.value.days_over_90
              ],
              backgroundColor: [
                '#10b981', // Green for current
                '#3b82f6', // Blue for 1-30
                '#f59e0b', // Yellow for 31-60
                '#f97316', // Orange for 61-90
                '#ef4444'  // Red for 90+
              ],
              borderWidth: 1
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                position: 'right'
              },
              tooltip: {
                callbacks: {
                  label: function(context) {
                    const label = context.label || '';
                    const value = context.raw;
                    const percentage = ((value / totals.value.total_balance) * 100).toFixed(1);
                    return `${label}: ${formatCurrency(value)} (${percentage}%)`;
                  }
                }
              }
            }
          }
        });
      };

      // Print report
      const printReport = () => {
        window.print();
      };

      // Calculate percentage
      const calculatePercentage = (value, total) => {
        if (!total) return 0;
        return ((value / total) * 100).toFixed(1);
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

      // Format date
      const formatDate = (dateString) => {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', {
          day: 'numeric',
          month: 'long',
          year: 'numeric'
        });
      };

      // Watch for filter changes
      watch(
        () => filters.customerId,
        () => applyFilters()
      );

      // Lifecycle hooks
      onMounted(() => {
        loadCustomers();
        generateReport();
      });

      return {
        isLoading,
        error,
        agingData,
        customers,
        pieChartRef,
        filters,
        filteredAgingData,
        totals,
        totalOverdue,
        generateReport,
        applyFilters,
        resetFilters,
        printReport,
        calculatePercentage,
        formatCurrency,
        formatDate
      };
    }
  };
  </script>

  <style scoped>
  .report-header {
    text-align: center;
    margin-bottom: 2rem;
  }

  .report-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
  }

  .report-subtitle {
    font-size: 1rem;
    color: var(--gray-600);
  }

  .summary-card {
    background-color: white;
    border-radius: 0.5rem;
    padding: 1.25rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    border-top: 5px solid;
  }

  .summary-card.current {
    border-top-color: #10b981; /* Green */
  }

  .summary-card.days-1-30 {
    border-top-color: #3b82f6; /* Blue */
  }

  .summary-card.days-31-60 {
    border-top-color: #f59e0b; /* Yellow */
  }

  .summary-card.days-over-60 {
    border-top-color: #ef4444; /* Red */
  }

  .summary-title {
    color: var(--gray-500);
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
  }

  .summary-value {
    font-size: 1.25rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
  }

  .summary-percent {
    font-size: 0.875rem;
    color: var(--gray-600);
  }

  .chart-container {
    position: relative;
    height: 300px;
  }

  .total-summary {
    padding: 1rem 0;
  }

  .total-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--gray-200);
  }

  .total-item:last-child {
    border-bottom: none;
  }

  .total-label {
    font-weight: 600;
  }

  .total-value {
    font-size: 1.125rem;
    font-weight: 700;
  }

  @media print {
    .page-header, .card-header, .actions, button, .btn, input, select, label {
      display: none !important;
    }

    .card {
      border: none !important;
      box-shadow: none !important;
      margin-bottom: 1rem !important;
    }

    .card-body {
      padding: 0 !important;
    }

    .total-summary {
      page-break-inside: avoid;
    }

    .summary-card {
      box-shadow: none;
      border: 1px solid #ddd;
    }

    .table-responsive {
      overflow: visible !important;
    }

    th {
      background-color: #f8f9fa !important;
      color: #000 !important;
    }
  }
  </style>
