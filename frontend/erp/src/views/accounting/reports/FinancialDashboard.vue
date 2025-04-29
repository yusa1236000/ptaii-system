<!-- src/views/accounting/reports/FinancialDashboard.vue -->
<template>
    <div class="financial-dashboard">
      <h1 class="page-title">Financial Dashboard</h1>

      <div class="filter-section card mb-4">
        <div class="card-body">
          <div class="row">
            <div class="col">
              <div class="form-group">
                <label>Period</label>
                <select v-model="selectedPeriod" class="form-control">
                  <option v-for="period in periods" :key="period.period_id" :value="period.period_id">
                    {{ period.period_name }}
                  </option>
                </select>
              </div>
            </div>
            <div class="col">
              <div class="form-group">
                <label>Date Range</label>
                <div class="d-flex">
                  <input type="date" v-model="startDate" class="form-control mr-2">
                  <input type="date" v-model="endDate" class="form-control">
                </div>
              </div>
            </div>
            <div class="col d-flex align-items-end">
              <button @click="refreshData" class="btn btn-primary">
                <i class="fas fa-sync-alt mr-1"></i> Refresh
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="metrics-overview mb-4">
        <div class="row">
          <div class="col-md-3">
            <div class="card">
              <div class="card-body">
                <h5 class="card-title text-primary">Total Revenue</h5>
                <h2 class="mb-0">{{ formatCurrency(financialSummary.totalRevenue) }}</h2>
                <div class="mt-2" :class="revenueGrowth >= 0 ? 'text-success' : 'text-danger'">
                  <i :class="revenueGrowth >= 0 ? 'fas fa-arrow-up' : 'fas fa-arrow-down'"></i>
                  {{ Math.abs(revenueGrowth) }}% vs last period
                </div>
              </div>
            </div>
          </div>

          <div class="col-md-3">
            <div class="card">
              <div class="card-body">
                <h5 class="card-title text-primary">Total Expenses</h5>
                <h2 class="mb-0">{{ formatCurrency(financialSummary.totalExpenses) }}</h2>
                <div class="mt-2" :class="expenseGrowth <= 0 ? 'text-success' : 'text-danger'">
                  <i :class="expenseGrowth <= 0 ? 'fas fa-arrow-down' : 'fas fa-arrow-up'"></i>
                  {{ Math.abs(expenseGrowth) }}% vs last period
                </div>
              </div>
            </div>
          </div>

          <div class="col-md-3">
            <div class="card">
              <div class="card-body">
                <h5 class="card-title text-primary">Net Income</h5>
                <h2 class="mb-0">{{ formatCurrency(financialSummary.netIncome) }}</h2>
                <div class="mt-2" :class="netIncomeGrowth >= 0 ? 'text-success' : 'text-danger'">
                  <i :class="netIncomeGrowth >= 0 ? 'fas fa-arrow-up' : 'fas fa-arrow-down'"></i>
                  {{ Math.abs(netIncomeGrowth) }}% vs last period
                </div>
              </div>
            </div>
          </div>

          <div class="col-md-3">
            <div class="card">
              <div class="card-body">
                <h5 class="card-title text-primary">Cash Balance</h5>
                <h2 class="mb-0">{{ formatCurrency(financialSummary.cashBalance) }}</h2>
                <div class="mt-2" :class="cashGrowth >= 0 ? 'text-success' : 'text-danger'">
                  <i :class="cashGrowth >= 0 ? 'fas fa-arrow-up' : 'fas fa-arrow-down'"></i>
                  {{ Math.abs(cashGrowth) }}% vs last period
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="charts-row row mb-4">
        <div class="col-md-8">
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h5 class="mb-0">Revenue vs Expenses</h5>
              <div class="chart-controls">
                <button class="btn btn-sm btn-outline-secondary" @click="changeChartPeriod('monthly')">
                  Monthly
                </button>
                <button class="btn btn-sm btn-outline-secondary ml-2" @click="changeChartPeriod('quarterly')">
                  Quarterly
                </button>
                <button class="btn btn-sm btn-outline-secondary ml-2" @click="changeChartPeriod('yearly')">
                  Yearly
                </button>
              </div>
            </div>
            <div class="card-body">
              <div class="chart-container" style="height: 300px;">
                <canvas ref="revenueExpenseChart"></canvas>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card h-100">
            <div class="card-header">
              <h5 class="mb-0">Expense Breakdown</h5>
            </div>
            <div class="card-body">
              <div class="chart-container" style="height: 300px;">
                <canvas ref="expenseBreakdownChart"></canvas>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="row mb-4">
        <div class="col-md-6">
          <div class="card">
            <div class="card-header">
              <h5 class="mb-0">Accounts Receivable Aging</h5>
            </div>
            <div class="card-body">
              <DataTable
                :columns="receivableColumns"
                :items="receivablesAging.data"
                :is-loading="loadingReceivables"
                empty-title="No Receivables"
                empty-message="There are no receivables to display."
                empty-icon="fas fa-dollar-sign"
              >
                <!-- Customer column template -->
                <template #customer="{ value }">
                  {{ value }}
                </template>

                <!-- Amount columns templates -->
                <template #current_amount="{ value }">
                  <span>{{ formatCurrency(value) }}</span>
                </template>
                <template #days_1_30="{ value }">
                  <span>{{ formatCurrency(value) }}</span>
                </template>
                <template #days_31_60="{ value }">
                  <span>{{ formatCurrency(value) }}</span>
                </template>
                <template #days_61_90="{ value }">
                  <span>{{ formatCurrency(value) }}</span>
                </template>
                <template #days_over_90="{ value }">
                  <span class="text-danger">{{ formatCurrency(value) }}</span>
                </template>
                <template #total_balance="{ value }">
                  <span class="font-weight-bold">{{ formatCurrency(value) }}</span>
                </template>

                <!-- Footer template for totals -->
                <template #footer>
                  <div class="d-flex justify-content-between align-items-center p-3 bg-light">
                    <span class="font-weight-bold">Totals:</span>
                    <div class="d-flex">
                      <span class="mx-3">{{ formatCurrency(receivablesAging.totals?.current_amount || 0) }}</span>
                      <span class="mx-3">{{ formatCurrency(receivablesAging.totals?.days_1_30 || 0) }}</span>
                      <span class="mx-3">{{ formatCurrency(receivablesAging.totals?.days_31_60 || 0) }}</span>
                      <span class="mx-3">{{ formatCurrency(receivablesAging.totals?.days_61_90 || 0) }}</span>
                      <span class="mx-3 text-danger">{{ formatCurrency(receivablesAging.totals?.days_over_90 || 0) }}</span>
                      <span class="mx-3 font-weight-bold">{{ formatCurrency(receivablesAging.totals?.total_balance || 0) }}</span>
                    </div>
                  </div>
                </template>
              </DataTable>
            </div>
          </div>
        </div>

        <div class="col-md-6">
          <div class="card">
            <div class="card-header">
              <h5 class="mb-0">Accounts Payable Aging</h5>
            </div>
            <div class="card-body">
              <DataTable
                :columns="payableColumns"
                :items="payablesAging.data"
                :is-loading="loadingPayables"
                empty-title="No Payables"
                empty-message="There are no payables to display."
                empty-icon="fas fa-file-invoice-dollar"
              >
                <!-- Vendor column template -->
                <template #vendor="{ value }">
                  {{ value }}
                </template>

                <!-- Amount columns templates -->
                <template #current_amount="{ value }">
                  <span>{{ formatCurrency(value) }}</span>
                </template>
                <template #days_1_30="{ value }">
                  <span>{{ formatCurrency(value) }}</span>
                </template>
                <template #days_31_60="{ value }">
                  <span>{{ formatCurrency(value) }}</span>
                </template>
                <template #days_61_90="{ value }">
                  <span>{{ formatCurrency(value) }}</span>
                </template>
                <template #days_over_90="{ value }">
                  <span class="text-danger">{{ formatCurrency(value) }}</span>
                </template>
                <template #total_balance="{ value }">
                  <span class="font-weight-bold">{{ formatCurrency(value) }}</span>
                </template>

                <!-- Footer template for totals -->
                <template #footer>
                  <div class="d-flex justify-content-between align-items-center p-3 bg-light">
                    <span class="font-weight-bold">Totals:</span>
                    <div class="d-flex">
                      <span class="mx-3">{{ formatCurrency(payablesAging.totals?.current_amount || 0) }}</span>
                      <span class="mx-3">{{ formatCurrency(payablesAging.totals?.days_1_30 || 0) }}</span>
                      <span class="mx-3">{{ formatCurrency(payablesAging.totals?.days_31_60 || 0) }}</span>
                      <span class="mx-3">{{ formatCurrency(payablesAging.totals?.days_61_90 || 0) }}</span>
                      <span class="mx-3 text-danger">{{ formatCurrency(payablesAging.totals?.days_over_90 || 0) }}</span>
                      <span class="mx-3 font-weight-bold">{{ formatCurrency(payablesAging.totals?.total_balance || 0) }}</span>
                    </div>
                  </div>
                </template>
              </DataTable>
            </div>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-md-12">
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h5 class="mb-0">Financial Reports</h5>
            </div>
            <div class="card-body">
              <div class="report-links d-flex flex-wrap">
                <router-link to="/accounting/reports/trial-balance" class="report-link-card">
                  <div class="icon-container">
                    <i class="fas fa-balance-scale"></i>
                  </div>
                  <div class="report-title">Trial Balance</div>
                </router-link>

                <router-link to="/accounting/reports/income-statement" class="report-link-card">
                  <div class="icon-container">
                    <i class="fas fa-chart-line"></i>
                  </div>
                  <div class="report-title">Income Statement</div>
                </router-link>

                <router-link to="/accounting/reports/balance-sheet" class="report-link-card">
                  <div class="icon-container">
                    <i class="fas fa-clipboard-list"></i>
                  </div>
                  <div class="report-title">Balance Sheet</div>
                </router-link>

                <router-link to="/accounting/reports/cash-flow" class="report-link-card">
                  <div class="icon-container">
                    <i class="fas fa-money-bill-wave"></i>
                  </div>
                  <div class="report-title">Cash Flow</div>
                </router-link>

                <router-link to="/accounting/reports/accounts-receivable" class="report-link-card">
                  <div class="icon-container">
                    <i class="fas fa-hand-holding-usd"></i>
                  </div>
                  <div class="report-title">Accounts Receivable</div>
                </router-link>

                <router-link to="/accounting/reports/accounts-payable" class="report-link-card">
                  <div class="icon-container">
                    <i class="fas fa-file-invoice-dollar"></i>
                  </div>
                  <div class="report-title">Accounts Payable</div>
                </router-link>

                <router-link to="/accounting/reports/configuration" class="report-link-card">
                  <div class="icon-container">
                    <i class="fas fa-cog"></i>
                  </div>
                  <div class="report-title">Report Configuration</div>
                </router-link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </template>

  <script>
  import axios from 'axios';
  import { ref, reactive, onMounted, computed } from 'vue';
  import Chart from 'chart.js/auto';

  export default {
    name: 'FinancialDashboard',
    setup() {
      // Charts references
      const revenueExpenseChart = ref(null);
      const expenseBreakdownChart = ref(null);
      let revenueExpenseChartInstance = null;
      let expenseBreakdownChartInstance = null;

      // State variables
      const periods = ref([]);
      const selectedPeriod = ref(null);
      const startDate = ref('');
      const endDate = ref('');
      const chartPeriod = ref('monthly');

      const loadingReceivables = ref(true);
      const loadingPayables = ref(true);

      const financialSummary = reactive({
        totalRevenue: 0,
        totalExpenses: 0,
        netIncome: 0,
        cashBalance: 0,
        previousRevenue: 0,
        previousExpenses: 0,
        previousNetIncome: 0,
        previousCashBalance: 0
      });

      const receivablesAging = reactive({
        data: [],
        totals: {}
      });

      const payablesAging = reactive({
        data: [],
        totals: {}
      });

      const revenueExpenseData = reactive({
        labels: [],
        revenues: [],
        expenses: []
      });

      const expenseBreakdownData = reactive({
        labels: [],
        values: []
      });

      // Computed properties
      const revenueGrowth = computed(() => {
        if (financialSummary.previousRevenue === 0) return 0;
        return Math.round(((financialSummary.totalRevenue - financialSummary.previousRevenue) /
          financialSummary.previousRevenue) * 100);
      });

      const expenseGrowth = computed(() => {
        if (financialSummary.previousExpenses === 0) return 0;
        return Math.round(((financialSummary.totalExpenses - financialSummary.previousExpenses) /
          financialSummary.previousExpenses) * 100);
      });

      const netIncomeGrowth = computed(() => {
        if (financialSummary.previousNetIncome === 0) return 0;
        return Math.round(((financialSummary.netIncome - financialSummary.previousNetIncome) /
          Math.abs(financialSummary.previousNetIncome)) * 100);
      });

      const cashGrowth = computed(() => {
        if (financialSummary.previousCashBalance === 0) return 0;
        return Math.round(((financialSummary.cashBalance - financialSummary.previousCashBalance) /
          financialSummary.previousCashBalance) * 100);
      });

      // Table columns
      const receivableColumns = [
        { key: 'customer_name', label: 'Customer', template: 'customer' },
        { key: 'current_amount', label: 'Current', template: 'current_amount', class: 'text-right' },
        { key: 'days_1_30', label: '1-30 Days', template: 'days_1_30', class: 'text-right' },
        { key: 'days_31_60', label: '31-60 Days', template: 'days_31_60', class: 'text-right' },
        { key: 'days_61_90', label: '61-90 Days', template: 'days_61_90', class: 'text-right' },
        { key: 'days_over_90', label: 'Over 90 Days', template: 'days_over_90', class: 'text-right' },
        { key: 'total_balance', label: 'Total', template: 'total_balance', class: 'text-right' }
      ];

      const payableColumns = [
        { key: 'vendor_name', label: 'Vendor', template: 'vendor' },
        { key: 'current_amount', label: 'Current', template: 'current_amount', class: 'text-right' },
        { key: 'days_1_30', label: '1-30 Days', template: 'days_1_30', class: 'text-right' },
        { key: 'days_31_60', label: '31-60 Days', template: 'days_31_60', class: 'text-right' },
        { key: 'days_61_90', label: '61-90 Days', template: 'days_61_90', class: 'text-right' },
        { key: 'days_over_90', label: 'Over 90 Days', template: 'days_over_90', class: 'text-right' },
        { key: 'total_balance', label: 'Total', template: 'total_balance', class: 'text-right' }
      ];

      // Methods
      const formatCurrency = (value) => {
        if (value === undefined || value === null) return 'Rp0';
        return new Intl.NumberFormat('id-ID', {
          style: 'currency',
          currency: 'IDR',
          minimumFractionDigits: 0
        }).format(value);
      };

      const loadAccountingPeriods = async () => {
        try {
          const response = await axios.get('/api/accounting/accounting-periods');
          periods.value = response.data.data;

          if (periods.value.length > 0) {
            // Find current period
            const currentPeriod = periods.value.find(p => p.status === 'Open');
            selectedPeriod.value = currentPeriod ? currentPeriod.period_id : periods.value[0].period_id;

            // Set date range based on selected period
            const period = periods.value.find(p => p.period_id === selectedPeriod.value);
            if (period) {
              startDate.value = period.start_date;
              endDate.value = period.end_date;
            }
          }
        } catch (error) {
          console.error('Error loading accounting periods:', error);
        }
      };

      const loadFinancialSummary = async () => {
        try {
          const response = await axios.get('/api/accounting/reports/income-statement', {
            params: {
              start_date: startDate.value,
              end_date: endDate.value,
              period_id: selectedPeriod.value,
              summary: true
            }
          });

          const data = response.data.data;
          financialSummary.totalRevenue = data.totalRevenue || 0;
          financialSummary.totalExpenses = data.totalExpenses || 0;
          financialSummary.netIncome = data.netIncome || 0;

          // Get previous period data
          const previousPeriodIndex = periods.value.findIndex(p => p.period_id === selectedPeriod.value) + 1;
          if (previousPeriodIndex < periods.value.length) {
            const previousPeriod = periods.value[previousPeriodIndex];
            const prevResponse = await axios.get('/api/accounting/reports/income-statement', {
              params: {
                start_date: previousPeriod.start_date,
                end_date: previousPeriod.end_date,
                period_id: previousPeriod.period_id,
                summary: true
              }
            });

            const prevData = prevResponse.data.data;
            financialSummary.previousRevenue = prevData.totalRevenue || 0;
            financialSummary.previousExpenses = prevData.totalExpenses || 0;
            financialSummary.previousNetIncome = prevData.netIncome || 0;
          }
        } catch (error) {
          console.error('Error loading financial summary:', error);
        }
      };

      const loadCashBalance = async () => {
        try {
          const response = await axios.get('/api/accounting/reports/balance-sheet', {
            params: {
              start_date: startDate.value,
              end_date: endDate.value,
              period_id: selectedPeriod.value
            }
          });

          const data = response.data.data;
          // Extract cash balance from balance sheet
          const cashAssets = data.assets.find(category =>
            category.name === 'Cash and Cash Equivalents' ||
            category.name === 'Cash' ||
            category.name.includes('Cash')
          );

          financialSummary.cashBalance = cashAssets ? cashAssets.amount : 0;

          // Get previous period data
          const previousPeriodIndex = periods.value.findIndex(p => p.period_id === selectedPeriod.value) + 1;
          if (previousPeriodIndex < periods.value.length) {
            const previousPeriod = periods.value[previousPeriodIndex];
            const prevResponse = await axios.get('/api/accounting/reports/balance-sheet', {
              params: {
                start_date: previousPeriod.start_date,
                end_date: previousPeriod.end_date,
                period_id: previousPeriod.period_id
              }
            });

            const prevData = prevResponse.data.data;
            const prevCashAssets = prevData.assets.find(category =>
              category.name === 'Cash and Cash Equivalents' ||
              category.name === 'Cash' ||
              category.name.includes('Cash')
            );

            financialSummary.previousCashBalance = prevCashAssets ? prevCashAssets.amount : 0;
          }
        } catch (error) {
          console.error('Error loading cash balance:', error);
        }
      };

      const loadReceivablesAging = async () => {
        loadingReceivables.value = true;
        try {
          const response = await axios.get('/api/accounting/customer-receivables/aging');
          receivablesAging.data = response.data.data;
          receivablesAging.totals = response.data.totals;
        } catch (error) {
          console.error('Error loading receivables aging:', error);
        } finally {
          loadingReceivables.value = false;
        }
      };

      const loadPayablesAging = async () => {
        loadingPayables.value = true;
        try {
          const response = await axios.get('/api/accounting/vendor-payables/aging');
          payablesAging.data = response.data.data;
          payablesAging.totals = response.data.totals;
        } catch (error) {
          console.error('Error loading payables aging:', error);
        } finally {
          loadingPayables.value = false;
        }
      };

      const loadRevenueExpenseData = async () => {
        try {
          // This would be replaced with actual API call to get revenue/expense time series data
          const response = await axios.get('/api/accounting/reports/income-statement', {
            params: {
              start_date: startDate.value,
              end_date: endDate.value,
              period_id: selectedPeriod.value,
              breakdown_by: chartPeriod.value
            }
          });

          const data = response.data.data;

          // Process data for chart
          revenueExpenseData.labels = data.periods || [];
          revenueExpenseData.revenues = data.revenueByPeriod || [];
          revenueExpenseData.expenses = data.expensesByPeriod || [];

          updateRevenueExpenseChart();
        } catch (error) {
          console.error('Error loading revenue/expense data:', error);

          // Fallback with sample data for demonstration
          revenueExpenseData.labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
          revenueExpenseData.revenues = [50000000, 60000000, 55000000, 80000000, 65000000, 90000000];
          revenueExpenseData.expenses = [30000000, 40000000, 35000000, 60000000, 45000000, 55000000];

          updateRevenueExpenseChart();
        }
      };

      const loadExpenseBreakdown = async () => {
        try {
          // This would be replaced with actual API call to get expense breakdown
          const response = await axios.get('/api/accounting/reports/income-statement', {
            params: {
              start_date: startDate.value,
              end_date: endDate.value,
              period_id: selectedPeriod.value,
              expense_breakdown: true
            }
          });

          const data = response.data.data;

          // Process data for chart
          expenseBreakdownData.labels = data.expenseCategories || [];
          expenseBreakdownData.values = data.expenseValues || [];

          updateExpenseBreakdownChart();
        } catch (error) {
          console.error('Error loading expense breakdown:', error);

          // Fallback with sample data for demonstration
          expenseBreakdownData.labels = ['Payroll', 'Rent', 'Utilities', 'Marketing', 'Supplies', 'Other'];
          expenseBreakdownData.values = [25000000, 10000000, 5000000, 8000000, 3000000, 4000000];

          updateExpenseBreakdownChart();
        }
      };

      const updateRevenueExpenseChart = () => {
        if (revenueExpenseChartInstance) {
          revenueExpenseChartInstance.destroy();
        }

        const ctx = revenueExpenseChart.value.getContext('2d');
        revenueExpenseChartInstance = new Chart(ctx, {
          type: 'bar',
          data: {
            labels: revenueExpenseData.labels,
            datasets: [
              {
                label: 'Revenue',
                data: revenueExpenseData.revenues,
                backgroundColor: 'rgba(37, 99, 235, 0.7)',
                borderColor: 'rgba(37, 99, 235, 1)',
                borderWidth: 1
              },
              {
                label: 'Expenses',
                data: revenueExpenseData.expenses,
                backgroundColor: 'rgba(220, 38, 38, 0.7)',
                borderColor: 'rgba(220, 38, 38, 1)',
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
                      notation: 'compact',
                      compactDisplay: 'short'
                    }).format(value);
                  }
                }
              }
            }
          }
        });
      };

      const updateExpenseBreakdownChart = () => {
        if (expenseBreakdownChartInstance) {
          expenseBreakdownChartInstance.destroy();
        }

        const ctx = expenseBreakdownChart.value.getContext('2d');
        expenseBreakdownChartInstance = new Chart(ctx, {
          type: 'doughnut',
          data: {
            labels: expenseBreakdownData.labels,
            datasets: [
              {
                data: expenseBreakdownData.values,
                backgroundColor: [
                  'rgba(37, 99, 235, 0.7)',
                  'rgba(5, 150, 105, 0.7)',
                  'rgba(245, 158, 11, 0.7)',
                  'rgba(220, 38, 38, 0.7)',
                  'rgba(139, 92, 246, 0.7)',
                  'rgba(75, 85, 99, 0.7)'
                ],
                borderWidth: 1
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              tooltip: {
                callbacks: {
                  label: function(context) {
                    const value = context.raw;
                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                    const percentage = Math.round((value / total) * 100);
                    return `${context.label}: ${formatCurrency(value)} (${percentage}%)`;
                  }
                }
              }
            }
          }
        });
      };

      const changeChartPeriod = (period) => {
        chartPeriod.value = period;
        loadRevenueExpenseData();
      };

      const refreshData = () => {
        loadFinancialSummary();
        loadCashBalance();
        loadReceivablesAging();
        loadPayablesAging();
        loadRevenueExpenseData();
        loadExpenseBreakdown();
      };

      // Initialize dashboard
      onMounted(async () => {
        await loadAccountingPeriods();
        refreshData();
      });

      return {
        // References
        revenueExpenseChart,
        expenseBreakdownChart,

        // State
        periods,
        selectedPeriod,
        startDate,
        endDate,
        financialSummary,
        receivablesAging,
        payablesAging,
        loadingReceivables,
        loadingPayables,

        // Computed
        revenueGrowth,
        expenseGrowth,
        netIncomeGrowth,
        cashGrowth,

        // Table columns
        receivableColumns,
        payableColumns,

        // Methods
        formatCurrency,
        refreshData,
        changeChartPeriod
      };
    }
  };
  </script>

  <style scoped>
  .financial-dashboard {
    padding: 1rem;
  }

  .page-title {
    margin-bottom: 1.5rem;
  }

  .filter-section {
    margin-bottom: 1.5rem;
  }

  .metrics-overview .card {
    height: 100%;
    transition: transform 0.3s ease;
  }

  .metrics-overview .card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
  }

  .chart-container {
    position: relative;
    width: 100%;
  }

  .chart-controls .btn {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
  }

  .report-links {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
  }

  .report-link-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
    border-radius: 0.5rem;
    background-color: var(--gray-50);
    border: 1px solid var(--gray-200);
    text-decoration: none;
    color: var(--gray-800);
    transition: all 0.3s ease;
  }

  .report-link-card:hover {
    background-color: var(--primary-bg);
    border-color: var(--primary-color);
    color: var(--primary-color);
    transform: translateY(-3px);
  }

  .icon-container {
    font-size: 2rem;
    margin-bottom: 1rem;
  }

  .report-title {
    font-weight: 500;
    text-align: center;
  }
  </style>
