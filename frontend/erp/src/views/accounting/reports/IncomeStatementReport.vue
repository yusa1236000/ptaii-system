<!-- src/views/accounting/reports/IncomeStatementReport.vue -->
<template>
    <div class="income-statement-report">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="page-title">Income Statement</h1>
        <div class="d-flex gap-2">
          <button class="btn btn-outline-secondary" @click="printReport">
            <i class="fas fa-print mr-1"></i> Print
          </button>
          <button class="btn btn-outline-secondary" @click="exportPdf">
            <i class="fas fa-file-pdf mr-1"></i> Export PDF
          </button>
          <button class="btn btn-outline-secondary" @click="exportExcel">
            <i class="fas fa-file-excel mr-1"></i> Export Excel
          </button>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-body">
          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label>Accounting Period</label>
                <select v-model="selectedPeriod" class="form-control" @change="updateDateRange">
                  <option v-for="period in periods" :key="period.period_id" :value="period.period_id">
                    {{ period.period_name }}
                  </option>
                </select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Start Date</label>
                <input type="date" v-model="startDate" class="form-control">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>End Date</label>
                <input type="date" v-model="endDate" class="form-control">
              </div>
            </div>
          </div>

          <div class="row mt-3">
            <div class="col-md-4">
              <div class="form-group">
                <label>Comparison Type</label>
                <select v-model="comparisonType" class="form-control">
                  <option value="none">No Comparison</option>
                  <option value="previous_period">Previous Period</option>
                  <option value="previous_year">Previous Year</option>
                  <option value="budget">Budget</option>
                </select>
              </div>
            </div>
            <div class="col-md-8">
              <div class="form-group">
                <label>Display Options</label>
                <div class="d-flex mt-2">
                  <div class="form-check mr-3">
                    <input class="form-check-input" type="checkbox" v-model="showPercentages" id="showPercentages">
                    <label class="form-check-label" for="showPercentages">
                      Show Percentages
                    </label>
                  </div>
                  <div class="form-check mr-3">
                    <input class="form-check-input" type="checkbox" v-model="showVariances" id="showVariances">
                    <label class="form-check-label" for="showVariances">
                      Show Variances
                    </label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" v-model="showZeroAmounts" id="showZeroAmounts">
                    <label class="form-check-label" for="showZeroAmounts">
                      Show Zero Amounts
                    </label>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="row mt-3">
            <div class="col-12 text-right">
              <button class="btn btn-primary" @click="loadIncomeStatement">
                <i class="fas fa-sync-alt mr-1"></i> Generate Report
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="report-container" ref="reportContainer">
        <div class="report-header text-center mb-4">
          <h2 class="company-name">Your Company Name</h2>
          <h3 class="report-title">Income Statement</h3>
          <p class="report-period">
            For the period: {{ formatDate(startDate) }} to {{ formatDate(endDate) }}
          </p>
        </div>

        <div v-if="isLoading" class="text-center py-5">
          <i class="fas fa-spinner fa-spin fa-2x"></i>
          <p class="mt-2">Loading income statement data...</p>
        </div>

        <div v-else-if="!reportData.revenues || reportData.revenues.length === 0" class="text-center py-5">
          <i class="fas fa-exclamation-circle fa-2x text-warning"></i>
          <p class="mt-2">No income statement data available for the selected period.</p>
        </div>

        <div v-else class="table-responsive">
          <table class="table table-bordered">
            <thead>
              <tr class="bg-light">
                <th style="width: 40%">Account</th>
                <th class="text-right" style="width: 15%">Current Period</th>
                <th v-if="comparisonType !== 'none'" class="text-right" style="width: 15%">
                  {{ comparisonLabel }}
                </th>
                <th v-if="showVariances && comparisonType !== 'none'" class="text-right" style="width: 15%">
                  Variance
                </th>
                <th v-if="showPercentages" class="text-right" style="width: 15%">
                  % of Revenue
                </th>
              </tr>
            </thead>

            <tbody>
              <!-- Revenue Section -->
              <tr class="section-header">
                <td colspan="5" class="font-weight-bold bg-light">Revenue</td>
              </tr>

              <tr v-for="revenue in filteredRevenues" :key="revenue.account_id" class="item-row">
                <td>{{ revenue.account_name }}</td>
                <td class="text-right">{{ formatCurrency(revenue.amount) }}</td>
                <td v-if="comparisonType !== 'none'" class="text-right">
                  {{ formatCurrency(revenue.comparison_amount || 0) }}
                </td>
                <td v-if="showVariances && comparisonType !== 'none'" class="text-right"
                    :class="getVarianceClass(revenue.amount, revenue.comparison_amount)">
                  {{ formatVariance(revenue.amount, revenue.comparison_amount) }}
                </td>
                <td v-if="showPercentages" class="text-right">
                  {{ calculatePercentage(revenue.amount, totalRevenue) }}%
                </td>
              </tr>

              <!-- Total Revenue Row -->
              <tr class="total-row">
                <td class="font-weight-bold">Total Revenue</td>
                <td class="text-right font-weight-bold">{{ formatCurrency(totalRevenue) }}</td>
                <td v-if="comparisonType !== 'none'" class="text-right font-weight-bold">
                  {{ formatCurrency(totalComparisonRevenue) }}
                </td>
                <td v-if="showVariances && comparisonType !== 'none'" class="text-right font-weight-bold"
                    :class="getVarianceClass(totalRevenue, totalComparisonRevenue)">
                  {{ formatVariance(totalRevenue, totalComparisonRevenue) }}
                </td>
                <td v-if="showPercentages" class="text-right font-weight-bold">
                  100%
                </td>
              </tr>

              <!-- Expenses Section -->
              <tr class="section-header">
                <td colspan="5" class="font-weight-bold bg-light">Expenses</td>
              </tr>

              <tr v-for="expense in filteredExpenses" :key="expense.account_id" class="item-row">
                <td>{{ expense.account_name }}</td>
                <td class="text-right">{{ formatCurrency(expense.amount) }}</td>
                <td v-if="comparisonType !== 'none'" class="text-right">
                  {{ formatCurrency(expense.comparison_amount || 0) }}
                </td>
                <td v-if="showVariances && comparisonType !== 'none'" class="text-right"
                    :class="getVarianceClass(expense.comparison_amount, expense.amount)">
                  {{ formatVariance(expense.comparison_amount, expense.amount) }}
                </td>
                <td v-if="showPercentages" class="text-right">
                  {{ calculatePercentage(expense.amount, totalRevenue) }}%
                </td>
              </tr>

              <!-- Total Expenses Row -->
              <tr class="total-row">
                <td class="font-weight-bold">Total Expenses</td>
                <td class="text-right font-weight-bold">{{ formatCurrency(totalExpenses) }}</td>
                <td v-if="comparisonType !== 'none'" class="text-right font-weight-bold">
                  {{ formatCurrency(totalComparisonExpenses) }}
                </td>
                <td v-if="showVariances && comparisonType !== 'none'" class="text-right font-weight-bold"
                    :class="getVarianceClass(totalComparisonExpenses, totalExpenses)">
                  {{ formatVariance(totalComparisonExpenses, totalExpenses) }}
                </td>
                <td v-if="showPercentages" class="text-right font-weight-bold">
                  {{ calculatePercentage(totalExpenses, totalRevenue) }}%
                </td>
              </tr>

              <!-- Net Income Row -->
              <tr class="grand-total-row">
                <td class="font-weight-bold">Net Income</td>
                <td class="text-right font-weight-bold">{{ formatCurrency(netIncome) }}</td>
                <td v-if="comparisonType !== 'none'" class="text-right font-weight-bold">
                  {{ formatCurrency(netComparisonIncome) }}
                </td>
                <td v-if="showVariances && comparisonType !== 'none'" class="text-right font-weight-bold"
                    :class="getVarianceClass(netIncome, netComparisonIncome)">
                  {{ formatVariance(netIncome, netComparisonIncome) }}
                </td>
                <td v-if="showPercentages" class="text-right font-weight-bold">
                  {{ calculatePercentage(netIncome, totalRevenue) }}%
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </template>

  <script>
  import axios from 'axios';
  import { ref, computed, onMounted, watch } from 'vue';
  import html2pdf from 'html2pdf.js';
  import * as XLSX from 'xlsx';

  export default {
    name: 'IncomeStatementReport',
    setup() {
      const reportContainer = ref(null);
      const isLoading = ref(true);
      const periods = ref([]);
      const selectedPeriod = ref(null);
      const startDate = ref('');
      const endDate = ref('');
      const comparisonType = ref('none');
      const showPercentages = ref(true);
      const showVariances = ref(true);
      const showZeroAmounts = ref(false);

      const reportData = ref({
        revenues: [],
        expenses: []
      });

      // Computed properties
      const comparisonLabel = computed(() => {
        switch (comparisonType.value) {
          case 'previous_period':
            return 'Previous Period';
          case 'previous_year':
            return 'Previous Year';
          case 'budget':
            return 'Budget';
          default:
            return '';
        }
      });

      const filteredRevenues = computed(() => {
        if (!reportData.value.revenues) return [];

        return reportData.value.revenues.filter(item => {
          if (!showZeroAmounts.value && item.amount === 0 && (!item.comparison_amount || item.comparison_amount === 0)) {
            return false;
          }
          return true;
        });
      });

      const filteredExpenses = computed(() => {
        if (!reportData.value.expenses) return [];

        return reportData.value.expenses.filter(item => {
          if (!showZeroAmounts.value && item.amount === 0 && (!item.comparison_amount || item.comparison_amount === 0)) {
            return false;
          }
          return true;
        });
      });

      const totalRevenue = computed(() => {
        if (!reportData.value.revenues) return 0;
        return reportData.value.revenues.reduce((sum, item) => sum + (item.amount || 0), 0);
      });

      const totalComparisonRevenue = computed(() => {
        if (!reportData.value.revenues || comparisonType.value === 'none') return 0;
        return reportData.value.revenues.reduce((sum, item) => sum + (item.comparison_amount || 0), 0);
      });

      const totalExpenses = computed(() => {
        if (!reportData.value.expenses) return 0;
        return reportData.value.expenses.reduce((sum, item) => sum + (item.amount || 0), 0);
      });

      const totalComparisonExpenses = computed(() => {
        if (!reportData.value.expenses || comparisonType.value === 'none') return 0;
        return reportData.value.expenses.reduce((sum, item) => sum + (item.comparison_amount || 0), 0);
      });

      const netIncome = computed(() => {
        return totalRevenue.value - totalExpenses.value;
      });

      const netComparisonIncome = computed(() => {
        return totalComparisonRevenue.value - totalComparisonExpenses.value;
      });

      // Methods
      const formatCurrency = (value) => {
        if (value === undefined || value === null) return 'Rp0';
        return new Intl.NumberFormat('id-ID', {
          style: 'currency',
          currency: 'IDR',
          minimumFractionDigits: 0
        }).format(value);
      };

      const formatDate = (dateString) => {
        if (!dateString) return '';
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        return new Date(dateString).toLocaleDateString('id-ID', options);
      };

      const calculatePercentage = (amount, total) => {
        if (!total || total === 0) return 0;
        return Math.round((amount / total) * 100);
      };

      const formatVariance = (current, comparison) => {
        const variance = current - (comparison || 0);
        return formatCurrency(variance);
      };

      const getVarianceClass = (current, comparison) => {
        if (!current || !comparison) return '';
        const variance = current - comparison;
        if (variance > 0) return 'text-success';
        if (variance < 0) return 'text-danger';
        return '';
      };

      const loadAccountingPeriods = async () => {
        try {
          const response = await axios.get('/api/accounting/accounting-periods');
          periods.value = response.data.data;

          if (periods.value.length > 0) {
            // Find current period
            const currentPeriod = periods.value.find(p => p.status === 'Open');
            selectedPeriod.value = currentPeriod ? currentPeriod.period_id : periods.value[0].period_id;
            updateDateRange();
          }
        } catch (error) {
          console.error('Error loading accounting periods:', error);
        }
      };

      const updateDateRange = () => {
        const period = periods.value.find(p => p.period_id === selectedPeriod.value);
        if (period) {
          startDate.value = period.start_date;
          endDate.value = period.end_date;
        }
      };

      const loadIncomeStatement = async () => {
        isLoading.value = true;

        try {
          const params = {
            start_date: startDate.value,
            end_date: endDate.value,
            period_id: selectedPeriod.value
          };

          if (comparisonType.value !== 'none') {
            params.comparison_type = comparisonType.value;
          }

          const response = await axios.get('/api/accounting/reports/income-statement', { params });
          reportData.value = response.data.data;
        } catch (error) {
          console.error('Error loading income statement:', error);
          reportData.value = { revenues: [], expenses: [] };
        } finally {
          isLoading.value = false;
        }
      };

      const printReport = () => {
        window.print();
      };

      const exportPdf = () => {
        const element = reportContainer.value;
        const opt = {
          margin: [10, 10, 10, 10],
          filename: `income_statement_${startDate.value}_to_${endDate.value}.pdf`,
          image: { type: 'jpeg', quality: 0.98 },
          html2canvas: { scale: 2 },
          jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };

        html2pdf().set(opt).from(element).save();
      };

      const exportExcel = () => {
        // Prepare data for Excel export
        const excelData = [];

        // Add headers
        let headers = ['Account', 'Current Period'];
        if (comparisonType.value !== 'none') headers.push(comparisonLabel.value);
        if (showVariances.value && comparisonType.value !== 'none') headers.push('Variance');
        if (showPercentages.value) headers.push('% of Revenue');

        // Add Revenue section
        excelData.push(headers);
        excelData.push(['Revenue']);

        filteredRevenues.value.forEach(revenue => {
          const row = [revenue.account_name, revenue.amount];
          if (comparisonType.value !== 'none') row.push(revenue.comparison_amount || 0);
          if (showVariances.value && comparisonType.value !== 'none') row.push(revenue.amount - (revenue.comparison_amount || 0));
          if (showPercentages.value) row.push(calculatePercentage(revenue.amount, totalRevenue.value));
          excelData.push(row);
        });

        // Add Total Revenue row
        const totalRevenueRow = ['Total Revenue', totalRevenue.value];
        if (comparisonType.value !== 'none') totalRevenueRow.push(totalComparisonRevenue.value);
        if (showVariances.value && comparisonType.value !== 'none') totalRevenueRow.push(totalRevenue.value - totalComparisonRevenue.value);
        if (showPercentages.value) totalRevenueRow.push(100);
        excelData.push(totalRevenueRow);

        // Add Expenses section
        excelData.push(['Expenses']);

        filteredExpenses.value.forEach(expense => {
          const row = [expense.account_name, expense.amount];
          if (comparisonType.value !== 'none') row.push(expense.comparison_amount || 0);
          if (showVariances.value && comparisonType.value !== 'none') row.push((expense.comparison_amount || 0) - expense.amount);
          if (showPercentages.value) row.push(calculatePercentage(expense.amount, totalRevenue.value));
          excelData.push(row);
        });

        // Add Total Expenses row
        const totalExpensesRow = ['Total Expenses', totalExpenses.value];
        if (comparisonType.value !== 'none') totalExpensesRow.push(totalComparisonExpenses.value);
        if (showVariances.value && comparisonType.value !== 'none') totalExpensesRow.push(totalComparisonExpenses.value - totalExpenses.value);
        if (showPercentages.value) totalExpensesRow.push(calculatePercentage(totalExpenses.value, totalRevenue.value));
        excelData.push(totalExpensesRow);

        // Add Net Income row
        const netIncomeRow = ['Net Income', netIncome.value];
        if (comparisonType.value !== 'none') netIncomeRow.push(netComparisonIncome.value);
        if (showVariances.value && comparisonType.value !== 'none') netIncomeRow.push(netIncome.value - netComparisonIncome.value);
        if (showPercentages.value) netIncomeRow.push(calculatePercentage(netIncome.value, totalRevenue.value));
        excelData.push(netIncomeRow);

        const worksheet = XLSX.utils.aoa_to_sheet(excelData);
        const workbook = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(workbook, worksheet, 'Income Statement');

        // Generate Excel file
        XLSX.writeFile(workbook, `income_statement_${startDate.value}_to_${endDate.value}.xlsx`);
      };

      // Initialize component
      onMounted(async () => {
        await loadAccountingPeriods();
        loadIncomeStatement();
      });

      // Watch for changes in comparison type
      watch(comparisonType, () => {
        if (startDate.value && endDate.value) {
          loadIncomeStatement();
        }
      });

      return {
        reportContainer,
        isLoading,
        periods,
        selectedPeriod,
        startDate,
        endDate,
        comparisonType,
        comparisonLabel,
        showPercentages,
        showVariances,
        showZeroAmounts,
        reportData,
        filteredRevenues,
        filteredExpenses,
        totalRevenue,
        totalComparisonRevenue,
        totalExpenses,
        totalComparisonExpenses,
        netIncome,
        netComparisonIncome,
        formatCurrency,
        formatDate,
        calculatePercentage,
        formatVariance,
        getVarianceClass,
        updateDateRange,
        loadIncomeStatement,
        printReport,
        exportPdf,
        exportExcel
      };
    }
  };
  </script>

  <style scoped>
  .income-statement-report {
    padding: 1rem;
  }

  .page-title {
    margin-bottom: 1.5rem;
  }

  .report-container {
    background-color: white;
    padding: 2rem;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  }

  .report-header {
    margin-bottom: 2rem;
  }

  .company-name {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
  }

  .report-title {
    font-size: 1.25rem;
    font-weight: 500;
    margin-bottom: 0.5rem;
  }

  .report-period {
    color: var(--gray-600);
  }

  .section-header td {
    background-color: var(--gray-100);
    font-weight: 600;
    padding-top: 1rem;
    padding-bottom: 1rem;
  }

  .item-row:hover {
    background-color: var(--gray-50);
  }

  .total-row {
    background-color: var(--gray-50);
  }

  .grand-total-row {
    background-color: var(--primary-bg);
    font-weight: 700;
  }

  .text-success {
    color: var(--success-color) !important;
  }

  .text-danger {
    color: var(--danger-color) !important;
  }

  @media print {
    .btn, .card, .d-flex:not(.report-header) {
      display: none !important;
    }

    .report-container {
      padding: 0;
      box-shadow: none;
    }

    .income-statement-report {
      padding: 0;
    }
  }
  </style>
