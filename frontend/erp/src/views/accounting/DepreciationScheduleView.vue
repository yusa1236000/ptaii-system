<!-- src/views/accounting/DepreciationScheduleView.vue -->
<template>
    <div class="depreciation-schedule">
      <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
          <h2>Jadwal Penyusutan Aset</h2>
          <div>
            <button @click="printSchedule" class="btn btn-info mr-2">
              <i class="fas fa-print mr-2"></i> Cetak
            </button>
            <router-link to="/accounting/depreciation" class="btn btn-outline-secondary">
              <i class="fas fa-arrow-left mr-2"></i> Kembali
            </router-link>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <div v-if="isLoading" class="text-center py-5">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p class="mt-2">Memuat jadwal penyusutan...</p>
          </div>

          <div v-else-if="error" class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            {{ error }}
          </div>

          <div v-else-if="!depreciation" class="text-center py-5">
            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
            <h4>Data Penyusutan Tidak Ditemukan</h4>
            <p class="text-muted">Data penyusutan yang Anda cari mungkin telah dihapus atau tidak ada.</p>
            <router-link to="/accounting/depreciation" class="btn btn-primary mt-2">
              Kembali ke Daftar Penyusutan
            </router-link>
          </div>

          <div v-else>
            <!-- Asset Information -->
            <div class="asset-info mb-4">
              <h3 class="mb-3">Informasi Aset</h3>
              <div class="row">
                <div class="col-md-6">
                  <table class="table table-details">
                    <tbody>
                      <tr>
                        <th width="40%">Nama Aset:</th>
                        <td>{{ asset.name }}</td>
                      </tr>
                      <tr>
                        <th>Kode Aset:</th>
                        <td>{{ asset.asset_code }}</td>
                      </tr>
                      <tr>
                        <th>Kategori:</th>
                        <td>{{ asset.category }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                <div class="col-md-6">
                  <table class="table table-details">
                    <tbody>
                      <tr>
                        <th width="40%">Tanggal Perolehan:</th>
                        <td>{{ formatDate(asset.acquisition_date) }}</td>
                      </tr>
                      <tr>
                        <th>Harga Perolehan:</th>
                        <td>{{ formatCurrency(asset.acquisition_cost) }}</td>
                      </tr>
                      <tr>
                        <th>Tingkat Penyusutan:</th>
                        <td>{{ asset.depreciation_rate }}% per tahun</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <!-- Current Depreciation -->
            <div class="current-depreciation mb-4">
              <h3 class="mb-3">Penyusutan Saat Ini</h3>
              <div class="current-depreciation-card">
                <div class="row">
                  <div class="col-md-4">
                    <div class="info-card bg-primary-light">
                      <div class="info-card-title">Tanggal Penyusutan</div>
                      <div class="info-card-value">{{ formatDate(depreciation.depreciation_date) }}</div>
                      <div class="info-card-footer">Periode: {{ depreciation.accounting_period?.period_name }}</div>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="info-card bg-success-light">
                      <div class="info-card-title">Nilai Penyusutan</div>
                      <div class="info-card-value">{{ formatCurrency(depreciation.depreciation_amount) }}</div>
                      <div class="info-card-footer">Periode ini</div>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="info-card bg-warning-light">
                      <div class="info-card-title">Nilai Buku</div>
                      <div class="info-card-value">{{ formatCurrency(depreciation.remaining_value) }}</div>
                      <div class="info-card-footer">Setelah penyusutan</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Schedule Table -->
            <div class="schedule-table">
              <h3 class="mb-3">Jadwal Penyusutan</h3>

              <div class="table-responsive">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>No.</th>
                      <th>Periode</th>
                      <th>Tanggal</th>
                      <th class="text-right">Nilai Penyusutan</th>
                      <th class="text-right">Akumulasi Penyusutan</th>
                      <th class="text-right">Nilai Buku</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(item, index) in scheduleItems" :key="index" :class="{ 'current-row': isCurrent(item) }">
                      <td>{{ index + 1 }}</td>
                      <td>{{ item.period_name || item.period }}</td>
                      <td>{{ formatDate(item.date) }}</td>
                      <td class="text-right">{{ formatCurrency(item.depreciation_amount) }}</td>
                      <td class="text-right">{{ formatCurrency(item.accumulated_depreciation) }}</td>
                      <td class="text-right">{{ formatCurrency(item.remaining_value) }}</td>
                      <td>
                        <span
                          :class="{
                            'badge': true,
                            'badge-success': item.status === 'Completed',
                            'badge-primary': item.status === 'Current',
                            'badge-secondary': item.status === 'Scheduled'
                          }"
                        >
                          {{ item.status }}
                        </span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Visualization -->
            <div class="depreciation-chart mt-4">
              <h3 class="mb-3">Visualisasi Penyusutan</h3>
              <div class="chart-container">
                <canvas id="depreciationChart" width="400" height="200"></canvas>
              </div>
              <div class="chart-legend mt-3 d-flex justify-content-center">
                <div class="legend-item">
                  <span class="legend-color bg-primary"></span>
                  <span class="legend-text">Nilai Buku</span>
                </div>
                <div class="legend-item ml-4">
                  <span class="legend-color bg-success"></span>
                  <span class="legend-text">Akumulasi Penyusutan</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </template>

  <script>
  import { ref, onMounted, computed } from 'vue';
  import { useRoute } from 'vue-router';
  import axios from 'axios';
  import Chart from 'chart.js';

  export default {
    name: 'DepreciationScheduleView',
    setup() {
      const route = useRoute();
      //const router = useRouter();
      const depreciationId = computed(() => route.params.id);

      const isLoading = ref(true);
      const error = ref(null);
      const depreciation = ref(null);
      const asset = ref(null);
      const scheduleItems = ref([]);
      let chart = null; // Chart.js instance

      const fetchDepreciation = async () => {
        isLoading.value = true;
        error.value = null;

        try {
          // Get depreciation details
          const response = await axios.get(`/api/accounting/asset-depreciations/${depreciationId.value}`);
          depreciation.value = response.data.data;

          // Get asset details
          const assetResponse = await axios.get(`/api/accounting/fixed-assets/${depreciation.value.asset_id}`);
          asset.value = assetResponse.data.data;

          // Generate schedule
          generateDepreciationSchedule();

        } catch (err) {
          console.error('Error fetching depreciation details:', err);
          error.value = 'Gagal memuat data penyusutan. Silakan coba lagi nanti.';
        } finally {
          isLoading.value = false;
        }
      };

      const generateDepreciationSchedule = () => {
        if (!asset.value || !depreciation.value) return;

        // Get basic asset information
        const acquisitionCost = asset.value.acquisition_cost;
        const depreciationRate = asset.value.depreciation_rate / 100;
        const annualDepreciation = acquisitionCost * depreciationRate;
        const monthlyDepreciation = annualDepreciation / 12;
        const usefulLifeMonths = Math.ceil(100 / asset.value.depreciation_rate * 12);

        // Get the current depreciation date
        const currentDate = new Date(depreciation.value.depreciation_date);
        //const currentMonth = currentDate.getMonth();
        //const currentYear = currentDate.getFullYear();

        // Generate past depreciations (completed)
        const pastDepreciations = [];
        const previousMonths = asset.value.acquisition_cost - asset.value.current_value - depreciation.value.depreciation_amount;
        const pastMonthsCount = Math.round(previousMonths / monthlyDepreciation);

        let accumulatedDepreciation = 0;
        let remainingValue = acquisitionCost;

        for (let i = 1; i <= pastMonthsCount; i++) {
          // Calculate date for this past depreciation (roughly)
          const date = new Date(currentDate);
          date.setMonth(date.getMonth() - (pastMonthsCount - i + 1));

          // Calculate values
          const depreciationAmount = monthlyDepreciation;
          accumulatedDepreciation += depreciationAmount;
          remainingValue = acquisitionCost - accumulatedDepreciation;

          // Create schedule item
          pastDepreciations.push({
            period: `Month ${i}`,
            period_name: `${date.toLocaleString('id-ID', { month: 'long' })} ${date.getFullYear()}`,
            date: date.toISOString().split('T')[0],
            depreciation_amount: depreciationAmount,
            accumulated_depreciation: accumulatedDepreciation,
            remaining_value: remainingValue,
            status: 'Completed'
          });
        }

        // Current depreciation
        accumulatedDepreciation = depreciation.value.accumulated_depreciation - depreciation.value.depreciation_amount;
        const currentItem = {
          period: `Month ${pastMonthsCount + 1}`,
          period_name: depreciation.value.accounting_period?.period_name ||
                      `${currentDate.toLocaleString('id-ID', { month: 'long' })} ${currentDate.getFullYear()}`,
          date: depreciation.value.depreciation_date,
          depreciation_amount: depreciation.value.depreciation_amount,
          accumulated_depreciation: depreciation.value.accumulated_depreciation,
          remaining_value: depreciation.value.remaining_value,
          status: 'Current'
        };

        // Future depreciations (remaining useful life)
        const futureDepreciations = [];
        const remainingMonths = Math.max(0, usefulLifeMonths - pastMonthsCount - 1);
        let futureAccumulatedDepreciation = depreciation.value.accumulated_depreciation;
        let futureRemainingValue = depreciation.value.remaining_value;

        for (let i = 1; i <= Math.min(remainingMonths, 36); i++) { // Show max 36 future months
          // Calculate date for this future depreciation
          const date = new Date(currentDate);
          date.setMonth(date.getMonth() + i);

          // Calculate values
          const depreciationAmount = Math.min(monthlyDepreciation, futureRemainingValue);
          futureAccumulatedDepreciation += depreciationAmount;
          futureRemainingValue -= depreciationAmount;

          if (futureRemainingValue <= 0) break;

          // Create schedule item
          futureDepreciations.push({
            period: `Month ${pastMonthsCount + 1 + i}`,
            period_name: `${date.toLocaleString('id-ID', { month: 'long' })} ${date.getFullYear()}`,
            date: date.toISOString().split('T')[0],
            depreciation_amount: depreciationAmount,
            accumulated_depreciation: futureAccumulatedDepreciation,
            remaining_value: futureRemainingValue,
            status: 'Scheduled'
          });
        }

        // Combine all items into a schedule
        scheduleItems.value = [...pastDepreciations, currentItem, ...futureDepreciations];

        // Schedule chart creation after DOM has been updated
        setTimeout(() => {
          createDepreciationChart();
        }, 0);
      };

      const createDepreciationChart = () => {
        const canvas = document.getElementById('depreciationChart');
        if (!canvas || !scheduleItems.value.length) return;

        // Prepare data for the chart
        const labels = scheduleItems.value.map(item => item.period_name);
        const remainingValues = scheduleItems.value.map(item => item.remaining_value);
        const accumulatedValues = scheduleItems.value.map(item => item.accumulated_depreciation);

        // Destroy previous chart if it exists
        if (chart) {
          chart.destroy();
        }

        // Create new chart
        const ctx = canvas.getContext('2d');
        chart = new Chart(ctx, {
          type: 'line',
          data: {
            labels: labels,
            datasets: [
              {
                label: 'Nilai Buku',
                data: remainingValues,
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37, 99, 235, 0.1)',
                borderWidth: 2,
                fill: true
              },
              {
                label: 'Akumulasi Penyusutan',
                data: accumulatedValues,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                borderWidth: 2,
                fill: true
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            legend: {
              display: false
            },
            scales: {
              xAxes: [{
                ticks: {
                  maxTicksLimit: 10,
                  maxRotation: 45,
                  minRotation: 45
                }
              }],
              yAxes: [{
                ticks: {
                  callback: (value) => {
                    return new Intl.NumberFormat('id-ID', {
                      style: 'currency',
                      currency: 'IDR',
                      minimumFractionDigits: 0,
                      maximumFractionDigits: 0
                    }).format(value);
                  }
                }
              }]
            },
            tooltips: {
              callbacks: {
                label: (tooltipItem, data) => {
                  const label = data.datasets[tooltipItem.datasetIndex].label || '';
                  const value = tooltipItem.yLabel;
                  return `${label}: ${formatCurrency(value)}`;
                }
              }
            }
          }
        });
      };

      const isCurrent = (item) => {
        return item.status === 'Current';
      };

      const printSchedule = () => {
        window.print();
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

      onMounted(() => {
        fetchDepreciation();
      });

      return {
        depreciationId,
        isLoading,
        error,
        depreciation,
        asset,
        scheduleItems,
        isCurrent,
        printSchedule,
        formatDate,
        formatCurrency
      };
    }
  };
  </script>

  <style scoped>
  .table-details {
    margin-bottom: 0;
  }

  .table-details th {
    width: 40%;
    font-weight: 600;
  }

  .current-depreciation-card {
    margin-top: 1rem;
  }

  .info-card {
    border-radius: 0.5rem;
    padding: 1.25rem;
    height: 100%;
    display: flex;
    flex-direction: column;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    margin-bottom: 1rem;
  }

  .bg-primary-light {
    background-color: #eff6ff;
    border-left: 4px solid #2563eb;
  }

  .bg-success-light {
    background-color: #d1fae5;
    border-left: 4px solid #10b981;
  }

  .bg-warning-light {
    background-color: #fef3c7;
    border-left: 4px solid #f59e0b;
  }

  .info-card-title {
    font-size: 0.875rem;
    font-weight: 500;
    color: #64748b;
    margin-bottom: 0.5rem;
  }

  .info-card-value {
    font-size: 1.5rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 0.5rem;
  }

  .info-card-footer {
    font-size: 0.75rem;
    color: #64748b;
    margin-top: auto;
  }

  .schedule-table {
    margin-top: 2rem;
  }

  .chart-container {
    position: relative;
    height: 300px;
    width: 100%;
    background-color: white;
    border-radius: 0.5rem;
    padding: 1rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  }

  .current-row {
    background-color: #eff6ff !important;
    font-weight: 500;
  }

  .legend-item {
    display: flex;
    align-items: center;
  }

  .legend-color {
    width: 12px;
    height: 12px;
    border-radius: 3px;
    margin-right: 0.5rem;
  }

  .legend-text {
    font-size: 0.875rem;
    color: #64748b;
  }

  @media print {
    .page-header .btn,
    .depreciation-chart {
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
