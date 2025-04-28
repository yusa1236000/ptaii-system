<!-- Template section for TaxSummaryReport.vue -->
<template>
    <div class="tax-summary-report">
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h3 class="card-title">Laporan Ringkasan Pajak</h3>
          <div class="header-actions">
            <button @click="exportToCsv" class="btn btn-secondary mr-2">
              <i class="fas fa-file-csv mr-1"></i> Export CSV
            </button>
            <button @click="printReport" class="btn btn-primary">
              <i class="fas fa-print mr-1"></i> Cetak
            </button>
          </div>
        </div>
        <div class="card-body">
          <!-- Report filters -->
          <div class="filter-container mb-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div class="form-group">
                <label for="period-type">Jenis Periode</label>
                <select id="period-type" v-model="filters.periodType" class="form-control" @change="updatePeriodOptions">
                  <option value="monthly">Bulanan</option>
                  <option value="quarterly">Triwulanan</option>
                  <option value="yearly">Tahunan</option>
                </select>
              </div>

              <div class="form-group">
                <label for="period">Periode</label>
                <select id="period" v-model="filters.period" class="form-control">
                  <option v-for="(option, index) in periodOptions" :key="index" :value="option.value">
                    {{ option.label }}
                  </option>
                </select>
              </div>

              <div class="form-group">
                <label for="tax-type">Jenis Pajak</label>
                <select id="tax-type" v-model="filters.taxType" class="form-control">
                  <option value="">Semua Jenis</option>
                  <option value="PPN">PPN</option>
                  <option value="PPH21">PPH21</option>
                  <option value="PPH23">PPH23</option>
                  <option value="PPH4(2)">PPH4(2)</option>
                  <option value="Other">Lainnya</option>
                </select>
              </div>

              <div class="form-group d-flex align-items-end">
                <button @click="loadReport" class="btn btn-primary w-100">
                  <i class="fas fa-search mr-1"></i> Tampilkan
                </button>
              </div>
            </div>
          </div>

          <!-- Loading state -->
          <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Memuat data ringkasan pajak...</p>
          </div>

          <!-- Summary cards -->
          <div v-else-if="summaryData">
            <div class="summary-header mb-4">
              <h4>{{ reportTitle }}</h4>
              <p class="text-muted">{{ reportSubtitle }}</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
              <div class="summary-card">
                <div class="summary-card-body">
                  <h6 class="summary-card-title">Total Pajak</h6>
                  <div class="summary-card-value">{{ formatCurrency(summaryData.totalTaxAmount) }}</div>
                  <div class="summary-card-subtitle">{{ summaryData.totalTransactions }} transaksi</div>
                </div>
              </div>

              <div class="summary-card">
                <div class="summary-card-body">
                  <h6 class="summary-card-title">Sudah Dibayar</h6>
                  <div class="summary-card-value text-success">{{ formatCurrency(summaryData.paidAmount) }}</div>
                  <div class="summary-card-subtitle">{{ summaryData.paidTransactions }} transaksi</div>
                </div>
              </div>

              <div class="summary-card">
                <div class="summary-card-body">
                  <h6 class="summary-card-title">Belum Dibayar</h6>
                  <div class="summary-card-value text-warning">{{ formatCurrency(summaryData.pendingAmount) }}</div>
                  <div class="summary-card-subtitle">{{ summaryData.pendingTransactions }} transaksi</div>
                </div>
              </div>
            </div>

            <!-- Charts section -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
              <div class="card">
                <div class="card-header">
                  <h5 class="card-title">Distribusi Pajak berdasarkan Jenis</h5>
                </div>
                <div class="card-body chart-container">
                  <canvas ref="taxTypePieChart"></canvas>
                </div>
              </div>

              <div class="card">
                <div class="card-header">
                  <h5 class="card-title">Status Pembayaran Pajak</h5>
                </div>
                <div class="card-body chart-container">
                  <canvas ref="taxStatusPieChart"></canvas>
                </div>
              </div>
            </div>

            <!-- Tax Breakdown Table -->
            <div class="card mb-4">
              <div class="card-header">
                <h5 class="card-title">Rincian Pajak berdasarkan Jenis</h5>
              </div>
              <div class="card-body">
                <div class="table-container">
                  <table class="data-table">
                    <thead>
                      <tr>
                        <th>Jenis Pajak</th>
                        <th class="text-right">Jumlah Transaksi</th>
                        <th class="text-right">Total Pajak</th>
                        <th class="text-right">Sudah Dibayar</th>
                        <th class="text-right">Belum Dibayar</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(item, index) in summaryData.byTaxType" :key="index">
                        <td>
                          <span :class="getTaxTypeBadgeClass(item.taxType)">{{ item.taxType }}</span>
                        </td>
                        <td class="text-right">{{ item.count }}</td>
                        <td class="text-right">{{ formatCurrency(item.totalAmount) }}</td>
                        <td class="text-right">{{ formatCurrency(item.paidAmount) }}</td>
                        <td class="text-right">{{ formatCurrency(item.pendingAmount) }}</td>
                      </tr>
                    </tbody>
                    <tfoot>
                      <tr>
                        <td><strong>Total</strong></td>
                        <td class="text-right"><strong>{{ summaryData.totalTransactions }}</strong></td>
                        <td class="text-right"><strong>{{ formatCurrency(summaryData.totalTaxAmount) }}</strong></td>
                        <td class="text-right"><strong>{{ formatCurrency(summaryData.paidAmount) }}</strong></td>
                        <td class="text-right"><strong>{{ formatCurrency(summaryData.pendingAmount) }}</strong></td>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </div>
            </div>

            <!-- Monthly Trend Chart -->
            <div class="card mb-4">
              <div class="card-header">
                <h5 class="card-title">Tren Pajak per Periode</h5>
              </div>
              <div class="card-body">
                <div class="chart-container">
                  <canvas ref="taxTrendChart" height="300"></canvas>
                </div>
              </div>
            </div>

            <!-- Recent Transactions Table -->
            <div class="card">
              <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title">Transaksi Pajak Terbaru</h5>
                <router-link to="/accounting/tax-transactions" class="btn btn-sm btn-primary">
                  Lihat Semua
                </router-link>
              </div>
              <div class="card-body">
                <div class="table-container">
                  <table class="data-table">
                    <thead>
                      <tr>
                        <th>Nomor Pajak</th>
                        <th>Jenis Pajak</th>
                        <th>Tanggal Transaksi</th>
                        <th>Jatuh Tempo</th>
                        <th class="text-right">Jumlah</th>
                        <th>Status</th>
                        <th class="text-center">Aksi</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(transaction, index) in summaryData.recentTransactions" :key="index">
                        <td>{{ transaction.tax_number }}</td>
                        <td>
                          <span :class="getTaxTypeBadgeClass(transaction.tax_type)">{{ transaction.tax_type }}</span>
                        </td>
                        <td>{{ formatDate(transaction.transaction_date) }}</td>
                        <td>{{ formatDate(transaction.due_date) }}</td>
                        <td class="text-right">{{ formatCurrency(transaction.tax_amount) }}</td>
                        <td>
                          <span :class="getStatusBadgeClass(transaction.status)">{{ transaction.status }}</span>
                        </td>
                        <td class="text-center">
                          <router-link :to="`/accounting/tax-transactions/${transaction.tax_transaction_id}`" class="btn btn-sm btn-info">
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

          <!-- Empty state -->
          <div v-else class="empty-state py-5">
            <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
            <h4>Data tidak tersedia</h4>
            <p>Tidak ada data pajak untuk periode yang dipilih. Silakan pilih periode lain atau sesuaikan filter.</p>
          </div>
        </div>
      </div>
    </div>
  </template>
  <script>
  import { ref, reactive, computed, onMounted, nextTick, onBeforeUnmount } from 'vue';

  export default {
    name: 'TaxSummaryReport',
    setup() {
      const loading = ref(false);
      const summaryData = ref(null);
      const taxTypePieChart = ref(null);
      const taxStatusPieChart = ref(null);
      const taxTrendChart = ref(null);
      const chartInstances = reactive({
        taxType: null,
        taxStatus: null,
        taxTrend: null
      });

      const filters = reactive({
        periodType: 'monthly',
        period: new Date().toISOString().slice(0, 7), // Default to current month
        taxType: '',
      });

      const periodOptions = ref([]);

      const reportTitle = computed(() => {
        let title = 'Laporan Ringkasan Pajak';
        if (filters.taxType) {
          title += ` - ${filters.taxType}`;
        }
        return title;
      });

      const reportSubtitle = computed(() => {
        if (filters.periodType === 'monthly') {
          return `Periode: ${formatMonthYear(filters.period)}`;
        } else if (filters.periodType === 'quarterly') {
          const quarter = parseInt(filters.period.split('-')[1]);
          const year = filters.period.split('-')[0];
          return `Periode: Triwulan ${quarter} ${year}`;
        } else if (filters.periodType === 'yearly') {
          return `Periode: Tahun ${filters.period}`;
        }
        return '';
      });

      const updatePeriodOptions = () => {
        const currentYear = new Date().getFullYear();
        const currentMonth = new Date().getMonth();

        if (filters.periodType === 'monthly') {
          periodOptions.value = [];
          // Generate last 12 months
          for (let i = 0; i < 12; i++) {
            const date = new Date(currentYear, currentMonth - i, 1);
            const value = date.toISOString().slice(0, 7);
            const label = formatMonthYear(value);
            periodOptions.value.push({ value, label });
          }
          filters.period = periodOptions.value[0].value;
        } else if (filters.periodType === 'quarterly') {
          periodOptions.value = [];
          // Generate last 4 quarters
          for (let i = 0; i < 4; i++) {
            const quarter = Math.floor(currentMonth / 3) + 1 - i;
            const year = currentYear - (quarter <= 0 ? 1 : 0);
            const adjustedQuarter = quarter <= 0 ? quarter + 4 : quarter;
            const value = `${year}-${adjustedQuarter}`;
            const label = `Triwulan ${adjustedQuarter} ${year}`;
            periodOptions.value.push({ value, label });
          }
          filters.period = periodOptions.value[0].value;
        } else if (filters.periodType === 'yearly') {
          periodOptions.value = [];
          // Generate last 5 years
          for (let i = 0; i < 5; i++) {
            const year = currentYear - i;
            periodOptions.value.push({ value: year.toString(), label: `Tahun ${year}` });
          }
          filters.period = periodOptions.value[0].value;
        }
      };

      const loadReport = async () => {
        loading.value = true;
        try {
          // In a real implementation, you would make an API call to your backend
          // For now, we'll simulate a response with mock data
          await new Promise(resolve => setTimeout(resolve, 800)); // Simulate network delay

          // Generate mock summary data based on the selected filters
          const mockData = generateMockSummaryData();
          summaryData.value = mockData;

          // Wait for DOM to update before initializing charts
          await nextTick();
          initCharts();
        } catch (error) {
          console.error('Failed to load tax summary:', error);
          alert('Gagal memuat ringkasan pajak. Silakan coba lagi nanti.');
          summaryData.value = null;
        } finally {
          loading.value = false;
        }
      };

      const generateMockSummaryData = () => {
        // This function generates mock data for demonstration purposes
        // In a real implementation, this data would come from your API

        const taxTypes = ['PPN', 'PPH21', 'PPH23', 'PPH4(2)', 'Other'];
        const statuses = ['Pending', 'Filed', 'Paid'];
        const mockTransactions = [];

        // Filter tax types based on selected filter
        const filteredTaxTypes = filters.taxType ?
          [filters.taxType] :
          taxTypes;

        // Generate mock transactions
        for (let i = 0; i < 50; i++) {
          const taxType = filteredTaxTypes[Math.floor(Math.random() * filteredTaxTypes.length)];
          const status = statuses[Math.floor(Math.random() * statuses.length)];
          const taxAmount = Math.floor(Math.random() * 10000000) + 500000; // Between 500K and 10.5M

          // Generate dates based on selected period
          let transactionDate, dueDate;
          if (filters.periodType === 'monthly') {
            const [year, month] = filters.period.split('-');
            const daysInMonth = new Date(parseInt(year), parseInt(month), 0).getDate();
            const day = Math.floor(Math.random() * daysInMonth) + 1;
            transactionDate = new Date(parseInt(year), parseInt(month) - 1, day);
            dueDate = new Date(parseInt(year), parseInt(month) - 1, day + 14);
          } else if (filters.periodType === 'quarterly') {
            const [year, quarter] = filters.period.split('-');
            const monthInQuarter = ((parseInt(quarter) - 1) * 3) + Math.floor(Math.random() * 3);
            const daysInMonth = new Date(parseInt(year), monthInQuarter + 1, 0).getDate();
            const day = Math.floor(Math.random() * daysInMonth) + 1;
            transactionDate = new Date(parseInt(year), monthInQuarter, day);
            dueDate = new Date(parseInt(year), monthInQuarter, day + 14);
          } else {
            const year = parseInt(filters.period);
            const month = Math.floor(Math.random() * 12);
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const day = Math.floor(Math.random() * daysInMonth) + 1;
            transactionDate = new Date(year, month, day);
            dueDate = new Date(year, month, day + 14);
          }

          mockTransactions.push({
            tax_transaction_id: i + 1,
            tax_number: `TX-${2025}-${10000 + i}`,
            tax_type: taxType,
            transaction_date: transactionDate.toISOString().split('T')[0],
            due_date: dueDate.toISOString().split('T')[0],
            tax_amount: taxAmount,
            status: status
          });
        }

        // Calculate summary statistics
        const totalTaxAmount = mockTransactions.reduce((sum, tx) => sum + tx.tax_amount, 0);
        const paidTransactions = mockTransactions.filter(tx => tx.status === 'Paid');
        const paidAmount = paidTransactions.reduce((sum, tx) => sum + tx.tax_amount, 0);
        const filedTransactions = mockTransactions.filter(tx => tx.status === 'Filed');
        const filedAmount = filedTransactions.reduce((sum, tx) => sum + tx.tax_amount, 0);
        const pendingTransactions = mockTransactions.filter(tx => tx.status === 'Pending');
        const pendingAmount = pendingTransactions.reduce((sum, tx) => sum + tx.tax_amount, 0);

        // Generate breakdown by tax type
        const byTaxType = [];
        for (const taxType of filteredTaxTypes) {
          const taxTypeTransactions = mockTransactions.filter(tx => tx.tax_type === taxType);
          const totalAmount = taxTypeTransactions.reduce((sum, tx) => sum + tx.tax_amount, 0);
          const paidAmount = taxTypeTransactions
            .filter(tx => tx.status === 'Paid')
            .reduce((sum, tx) => sum + tx.tax_amount, 0);
          const pendingAmount = totalAmount - paidAmount;

          byTaxType.push({
            taxType,
            count: taxTypeTransactions.length,
            totalAmount,
            paidAmount,
            pendingAmount
          });
        }

        // Generate trend data
        let trendLabels = [];
        let trendData = [];

        if (filters.periodType === 'monthly') {
          // For monthly, break down by days
          const [year, month] = filters.period.split('-');
          const daysInMonth = new Date(parseInt(year), parseInt(month), 0).getDate();

          for (let day = 1; day <= daysInMonth; day += 5) { // Every 5 days
            const dateLabel = `${day}/${month}`;
            const dayTransactions = mockTransactions.filter(tx => {
              const txDay = parseInt(tx.transaction_date.split('-')[2]);
              return txDay >= day && txDay < day + 5;
            });
            const dayAmount = dayTransactions.reduce((sum, tx) => sum + tx.tax_amount, 0);

            trendLabels.push(dateLabel);
            trendData.push(dayAmount);
          }
        } else if (filters.periodType === 'quarterly') {
          // For quarterly, break down by months
          const [year, quarter] = filters.period.split('-');
          const startMonth = (parseInt(quarter) - 1) * 3 + 1;

          for (let monthOffset = 0; monthOffset < 3; monthOffset++) {
            const monthNum = startMonth + monthOffset;
            const date = new Date(parseInt(year), monthNum - 1, 1);
            const monthLabel = date.toLocaleDateString('id-ID', { month: 'short' });

            const monthTransactions = mockTransactions.filter(tx => {
              const txMonth = parseInt(tx.transaction_date.split('-')[1]);
              return txMonth === monthNum;
            });
            const monthAmount = monthTransactions.reduce((sum, tx) => sum + tx.tax_amount, 0);

            trendLabels.push(monthLabel);
            trendData.push(monthAmount);
          }
        } else {
          // For yearly, break down by quarters
           parseInt(filters.period);

          for (let quarter = 1; quarter <= 4; quarter++) {
            const startMonth = (quarter - 1) * 3 + 1;
            const endMonth = startMonth + 2;

            const quarterTransactions = mockTransactions.filter(tx => {
              const txMonth = parseInt(tx.transaction_date.split('-')[1]);
              return txMonth >= startMonth && txMonth <= endMonth;
            });
            const quarterAmount = quarterTransactions.reduce((sum, tx) => sum + tx.tax_amount, 0);

            trendLabels.push(`Q${quarter}`);
            trendData.push(quarterAmount);
          }
        }

        return {
          totalTransactions: mockTransactions.length,
          totalTaxAmount,
          paidTransactions: paidTransactions.length,
          paidAmount,
          filedTransactions: filedTransactions.length,
          filedAmount,
          pendingTransactions: pendingTransactions.length,
          pendingAmount,
          byTaxType,
          trend: trendLabels.map((period, index) => ({
            period,
            amount: trendData[index]
          })),
          recentTransactions: mockTransactions
            .sort((a, b) => new Date(b.transaction_date) - new Date(a.transaction_date))
            .slice(0, 10)
        };
      };

      const initCharts = () => {
        if (!summaryData.value) return;

        // Import Chart.js dynamically
        import('chart.js/auto').then((ChartModule) => {
          const Chart = ChartModule.default;

          // Destroy existing chart instances
          if (chartInstances.taxType) chartInstances.taxType.destroy();
          if (chartInstances.taxStatus) chartInstances.taxStatus.destroy();
          if (chartInstances.taxTrend) chartInstances.taxTrend.destroy();

          // Tax Type Pie Chart
          const taxTypeData = summaryData.value.byTaxType.map(item => ({
            label: item.taxType,
            value: item.totalAmount
          }));

          chartInstances.taxType = new Chart(taxTypePieChart.value, {
            type: 'pie',
            data: {
              labels: taxTypeData.map(item => item.label),
              datasets: [{
                data: taxTypeData.map(item => item.value),
                backgroundColor: [
                  '#3b82f6', // PPN
                  '#0891b2', // PPH21
                  '#f59e0b', // PPH23
                  '#71717a', // PPH4(2)
                  '#4b5563'  // Other
                ]
              }]
            },
            options: {
              responsive: true,
              plugins: {
                legend: {
                  position: 'right'
                },
                tooltip: {
                  callbacks: {
                    label: function(context) {
                      const label = context.label || '';
                      const value = context.raw || 0;
                      return `${label}: ${formatCurrency(value)}`;
                    }
                  }
                }
              }
            }
          });

          // Tax Status Pie Chart
          chartInstances.taxStatus = new Chart(taxStatusPieChart.value, {
            type: 'doughnut',
            data: {
              labels: ['Dibayar', 'Dilaporkan', 'Pending'],
              datasets: [{
                data: [
                  summaryData.value.paidAmount,
                  summaryData.value.filedAmount,
                  summaryData.value.pendingAmount
                ],
                backgroundColor: [
                  '#059669', // Paid (Success)
                  '#0288d1', // Filed (Info)
                  '#d97706'  // Pending (Warning)
                ]
              }]
            },
            options: {
              responsive: true,
              plugins: {
                legend: {
                  position: 'right'
                },
                tooltip: {
                  callbacks: {
                    label: function(context) {
                      const label = context.label || '';
                      const value = context.raw || 0;
                      return `${label}: ${formatCurrency(value)}`;
                    }
                  }
                }
              }
            }
          });

          // Tax Trend Chart
          const trendLabels = summaryData.value.trend.map(item => item.period);
          const trendData = summaryData.value.trend.map(item => item.amount);

          chartInstances.taxTrend = new Chart(taxTrendChart.value, {
            type: 'bar',
            data: {
              labels: trendLabels,
              datasets: [{
                label: 'Total Pajak',
                data: trendData,
                backgroundColor: '#3b82f6',
                borderColor: '#2563eb',
                borderWidth: 1
              }]
            },
            options: {
              responsive: true,
              scales: {
                y: {
                  beginAtZero: true,
                  ticks: {
                    callback: function(value) {
                      return formatCurrency(value);
                    }
                  }
                }
              },
              plugins: {
                tooltip: {
                  callbacks: {
                    label: function(context) {
                      const label = context.dataset.label || '';
                      const value = context.raw || 0;
                      return `${label}: ${formatCurrency(value)}`;
                    }
                  }
                }
              }
            }
          });
        }).catch(error => {
          console.error('Failed to load Chart.js:', error);
        });
      };

      const formatDate = (dateString) => {
        if (!dateString) return '-';
        const options = { year: 'numeric', month: 'short', day: 'numeric' };
        return new Date(dateString).toLocaleDateString('id-ID', options);
      };

      const formatMonthYear = (dateString) => {
        if (!dateString) return '-';
        const [year, month] = dateString.split('-');
        const date = new Date(parseInt(year), parseInt(month) - 1, 1);
        const options = { year: 'numeric', month: 'long' };
        return date.toLocaleDateString('id-ID', options);
      };

      const formatCurrency = (amount) => {
        return new Intl.NumberFormat('id-ID', {
          style: 'currency',
          currency: 'IDR',
          minimumFractionDigits: 0,
          maximumFractionDigits: 0
        }).format(amount || 0);
      };

      const getTaxTypeBadgeClass = (taxType) => {
        const classes = {
          'PPN': 'badge badge-primary',
          'PPH21': 'badge badge-info',
          'PPH23': 'badge badge-warning',
          'PPH4(2)': 'badge badge-secondary',
          'Other': 'badge badge-dark'
        };
        return classes[taxType] || 'badge badge-dark';
      };

      const getStatusBadgeClass = (status) => {
        const classes = {
          'Pending': 'badge badge-warning',
          'Filed': 'badge badge-info',
          'Paid': 'badge badge-success'
        };
        return classes[status] || 'badge badge-secondary';
      };

      const exportToCsv = () => {
        if (!summaryData.value) return;

        const header = 'Jenis Pajak,Jumlah Transaksi,Total Pajak,Sudah Dibayar,Belum Dibayar\n';
        const rows = summaryData.value.byTaxType.map(item =>
          `"${item.taxType}",${item.count},${item.totalAmount},${item.paidAmount},${item.pendingAmount}`
        ).join('\n');

        const csvContent = `data:text/csv;charset=utf-8,${header}${rows}`;
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement('a');
        link.setAttribute('href', encodedUri);
        link.setAttribute('download', `Laporan_Pajak_${filters.period}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
      };

      const printReport = () => {
        window.print();
      };

      const cleanupCharts = () => {
        // Cleanup chart instances when component is unmounted
        if (chartInstances.taxType) chartInstances.taxType.destroy();
        if (chartInstances.taxStatus) chartInstances.taxStatus.destroy();
        if (chartInstances.taxTrend) chartInstances.taxTrend.destroy();
      };

      onMounted(() => {
        updatePeriodOptions();
        loadReport();
      });

      onBeforeUnmount(() => {
        cleanupCharts();
      });

      return {
        loading,
        summaryData,
        filters,
        periodOptions,
        reportTitle,
        reportSubtitle,
        taxTypePieChart,
        taxStatusPieChart,
        taxTrendChart,
        updatePeriodOptions,
        loadReport,
        formatDate,
        formatMonthYear,
        formatCurrency,
        getTaxTypeBadgeClass,
        getStatusBadgeClass,
        exportToCsv,
        printReport
      };
    }
  };
  </script>
  <style scoped>
  .tax-summary-report {
    margin-bottom: 2rem;
  }

  .filter-container {
    padding: 1rem;
    background-color: var(--gray-50);
    border-radius: 0.375rem;
    border: 1px solid var(--gray-200);
  }

  .summary-header {
    text-align: center;
  }

  .summary-header h4 {
    margin-bottom: 0.5rem;
  }

  .summary-card {
    background-color: white;
    border-radius: 0.375rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    height: 100%;
  }

  .summary-card-body {
    padding: 1.5rem;
    text-align: center;
  }

  .summary-card-title {
    font-size: 0.875rem;
    color: var(--gray-600);
    margin-bottom: 0.75rem;
  }

  .summary-card-value {
    font-size: 1.75rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
  }

  .summary-card-subtitle {
    font-size: 0.813rem;
    color: var(--gray-500);
  }

  .chart-container {
    position: relative;
    height: 300px;
    width: 100%;
  }

  .badge {
    padding: 0.375rem 0.75rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
  }

  .badge-primary {
    background-color: var(--primary-bg);
    color: var(--primary-color);
  }

  .badge-info {
    background-color: #e0f7fa;
    color: #0288d1;
  }

  .badge-success {
    background-color: var(--success-bg);
    color: var(--success-color);
  }

  .badge-warning {
    background-color: var(--warning-bg);
    color: var(--warning-color);
  }

  .badge-danger {
    background-color: var(--danger-bg);
    color: var(--danger-color);
  }

  .badge-secondary {
    background-color: var(--gray-100);
    color: var(--gray-700);
  }

  .badge-dark {
    background-color: #e0e0e0;
    color: #424242;
  }

  .data-table {
    width: 100%;
    border-collapse: collapse;
  }

  .data-table th,
  .data-table td {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--gray-200);
  }

  .data-table th {
    background-color: var(--gray-50);
    font-weight: 500;
    color: var(--gray-600);
    text-align: left;
  }

  .data-table tr:last-child td {
    border-bottom: none;
  }

  .data-table tfoot tr td {
    background-color: var(--gray-50);
    font-weight: 500;
  }

  .text-right {
    text-align: right;
  }

  .text-center {
    text-align: center;
  }

  .text-success {
    color: var(--success-color);
  }

  .text-warning {
    color: var(--warning-color);
  }

  .form-group {
    margin-bottom: 1rem;
  }

  .form-control {
    width: 100%;
    padding: 0.625rem;
    font-size: 0.875rem;
    line-height: 1.5;
    border: 1px solid var(--gray-300);
    border-radius: 0.375rem;
    transition: border-color 0.2s;
  }

  .mr-1 {
    margin-right: 0.25rem;
  }

  .mr-2 {
    margin-right: 0.5rem;
  }

  .mb-3 {
    margin-bottom: 1rem;
  }

  .mb-4 {
    margin-bottom: 1.5rem;
  }

  .mt-2 {
    margin-top: 0.5rem;
  }

  .py-3 {
    padding-top: 0.75rem;
    padding-bottom: 0.75rem;
  }

  .py-5 {
    padding-top: 1.5rem;
    padding-bottom: 1.5rem;
  }

  .text-muted {
    color: var(--gray-500);
  }

  .w-100 {
    width: 100%;
  }

  .gap-4 {
    gap: 1rem;
  }

  .empty-state {
    text-align: center;
    padding: 3rem 0;
  }

  .empty-state i {
    color: var(--gray-300);
    margin-bottom: 1rem;
  }

  .empty-state h4 {
    font-size: 1.125rem;
    color: var(--gray-700);
    margin-bottom: 0.5rem;
  }

  .empty-state p {
    color: var(--gray-500);
    max-width: 24rem;
    margin: 0 auto;
  }

  /* Responsive styling */
  @media print {
    .header-actions, .filter-container {
      display: none;
    }

    .card {
      border: none;
      box-shadow: none;
      margin-bottom: 1rem;
    }

    .chart-container {
      page-break-inside: avoid;
    }

    .summary-card {
      box-shadow: none;
      border: 1px solid var(--gray-200);
    }
  }

  @media (max-width: 768px) {
    .header-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;
    }

    .summary-card {
      margin-bottom: 1rem;
    }

    .chart-container {
      height: 250px;
    }
  }
  </style>
