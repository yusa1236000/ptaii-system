// Template Section
<template>
  <div class="cash-flow-report">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="page-title">Cash Flow Statement</h1>
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
            <button class="btn btn-primary" @click="loadCashFlow">
              <i class="fas fa-sync-alt mr-1"></i> Generate Report
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="report-container" ref="reportContainer">
      <div class="report-header text-center mb-4">
        <h2 class="company-name">Your Company Name</h2>
        <h3 class="report-title">Cash Flow Statement</h3>
        <p class="report-period">
          For the period: {{ formatDate(startDate) }} to {{ formatDate(endDate) }}
        </p>
      </div>

      <div v-if="isLoading" class="text-center py-5">
        <i class="fas fa-spinner fa-spin fa-2x"></i>
        <p class="mt-2">Loading cash flow data...</p>
      </div>

      <div v-else-if="!reportData.operating || reportData.operating.length === 0" class="text-center py-5">
        <i class="fas fa-exclamation-circle fa-2x text-warning"></i>
        <p class="mt-2">No cash flow data available for the selected period.</p>
      </div>

      <div v-else class="table-responsive">
        <table class="table table-bordered">
          <thead>
            <tr class="bg-light">
              <th style="width: 40%">Description</th>
              <th class="text-right" style="width: 15%">Current Period</th>
              <th v-if="comparisonType !== 'none'" class="text-right" style="width: 15%">
                {{ comparisonLabel }}
              </th>
              <th v-if="showVariances && comparisonType !== 'none'" class="text-right" style="width: 15%">
                Variance
              </th>
              <th v-if="showPercentages" class="text-right" style="width: 15%">
                % of Total Cash
              </th>
            </tr>
          </thead>

          <tbody>
            <!-- Beginning Cash Balance -->
            <tr class="beginning-balance">
              <td class="font-weight-bold">Beginning Cash Balance</td>
              <td class="text-right font-weight-bold">{{ formatCurrency(reportData.beginningBalance) }}</td>
              <td v-if="comparisonType !== 'none'" class="text-right font-weight-bold">
                {{ formatCurrency(reportData.comparisonBeginningBalance || 0) }}
              </td>
              <td v-if="showVariances && comparisonType !== 'none'" class="text-right font-weight-bold"
                  :class="getVarianceClass(reportData.beginningBalance, reportData.comparisonBeginningBalance)">
                {{ formatVariance(reportData.beginningBalance, reportData.comparisonBeginningBalance) }}
              </td>
              <td v-if="showPercentages" class="text-right">
                &nbsp;
              </td>
            </tr>

            <!-- Operating Activities Section -->
            <tr class="section-header">
              <td colspan="5" class="font-weight-bold bg-light">Cash Flows from Operating Activities</td>
            </tr>

            <tr v-for="(item, index) in filteredOperatingItems" :key="'operating-' + index" class="item-row">
              <td>{{ item.description }}</td>
              <td class="text-right" :class="{ 'text-success': item.amount > 0, 'text-danger': item.amount < 0 }">
                {{ formatCurrency(item.amount) }}
              </td>
              <td v-if="comparisonType !== 'none'" class="text-right"
                  :class="{ 'text-success': item.comparison_amount > 0, 'text-danger': item.comparison_amount < 0 }">
                {{ formatCurrency(item.comparison_amount || 0) }}
              </td>
              <td v-if="showVariances && comparisonType !== 'none'" class="text-right"
                  :class="getVarianceClass(item.amount, item.comparison_amount)">
                {{ formatVariance(item.amount, item.comparison_amount) }}
              </td>
              <td v-if="showPercentages" class="text-right">
                {{ calculatePercentage(item.amount, totalCashFlow) }}%
              </td>
            </tr>

            <!-- Net Operating Cash Flow -->
            <tr class="total-row">
              <td class="font-weight-bold">Net Cash from Operating Activities</td>
              <td class="text-right font-weight-bold" :class="{ 'text-success': netOperatingCashFlow > 0, 'text-danger': netOperatingCashFlow < 0 }">
                {{ formatCurrency(netOperatingCashFlow) }}
              </td>
              <td v-if="comparisonType !== 'none'" class="text-right font-weight-bold"
                  :class="{ 'text-success': netComparisonOperatingCashFlow > 0, 'text-danger': netComparisonOperatingCashFlow < 0 }">
                {{ formatCurrency(netComparisonOperatingCashFlow) }}
              </td>
              <td v-if="showVariances && comparisonType !== 'none'" class="text-right font-weight-bold"
                  :class="getVarianceClass(netOperatingCashFlow, netComparisonOperatingCashFlow)">
                {{ formatVariance(netOperatingCashFlow, netComparisonOperatingCashFlow) }}
              </td>
              <td v-if="showPercentages" class="text-right font-weight-bold">
                {{ calculatePercentage(netOperatingCashFlow, totalCashFlow) }}%
              </td>
            </tr>

            <!-- Investing Activities Section -->
            <tr class="section-header">
              <td colspan="5" class="font-weight-bold bg-light">Cash Flows from Investing Activities</td>
            </tr>

            <tr v-for="(item, index) in filteredInvestingItems" :key="'investing-' + index" class="item-row">
              <td>{{ item.description }}</td>
              <td class="text-right" :class="{ 'text-success': item.amount > 0, 'text-danger': item.amount < 0 }">
                {{ formatCurrency(item.amount) }}
              </td>
              <td v-if="comparisonType !== 'none'" class="text-right"
                  :class="{ 'text-success': item.comparison_amount > 0, 'text-danger': item.comparison_amount < 0 }">
                {{ formatCurrency(item.comparison_amount || 0) }}
              </td>
              <td v-if="showVariances && comparisonType !== 'none'" class="text-right"
                  :class="getVarianceClass(item.amount, item.comparison_amount)">
                {{ formatVariance(item.amount, item.comparison_amount) }}
              </td>
              <td v-if="showPercentages" class="text-right">
                {{ calculatePercentage(item.amount, totalCashFlow) }}%
              </td>
            </tr>

            <!-- Net Investing Cash Flow -->
            <tr class="total-row">
              <td class="font-weight-bold">Net Cash from Investing Activities</td>
              <td class="text-right font-weight-bold" :class="{ 'text-success': netInvestingCashFlow > 0, 'text-danger': netInvestingCashFlow < 0 }">
                {{ formatCurrency(netInvestingCashFlow) }}
              </td>
              <td v-if="comparisonType !== 'none'" class="text-right font-weight-bold"
                  :class="{ 'text-success': netComparisonInvestingCashFlow > 0, 'text-danger': netComparisonInvestingCashFlow < 0 }">
                {{ formatCurrency(netComparisonInvestingCashFlow) }}
              </td>
              <td v-if="showVariances && comparisonType !== 'none'" class="text-right font-weight-bold"
                  :class="getVarianceClass(netInvestingCashFlow, netComparisonInvestingCashFlow)">
                {{ formatVariance(netInvestingCashFlow, netComparisonInvestingCashFlow) }}
              </td>
              <td v-if="showPercentages" class="text-right font-weight-bold">
                {{ calculatePercentage(netInvestingCashFlow, totalCashFlow) }}%
              </td>
            </tr>

            <!-- Financing Activities Section -->
            <tr class="section-header">
              <td colspan="5" class="font-weight-bold bg-light">Cash Flows from Financing Activities</td>
            </tr>

            <tr v-for="(item, index) in filteredFinancingItems" :key="'financing-' + index" class="item-row">
              <td>{{ item.description }}</td>
              <td class="text-right" :class="{ 'text-success': item.amount > 0, 'text-danger': item.amount < 0 }">
                {{ formatCurrency(item.amount) }}
              </td>
              <td v-if="comparisonType !== 'none'" class="text-right"
                  :class="{ 'text-success': item.comparison_amount > 0, 'text-danger': item.comparison_amount < 0 }">
                {{ formatCurrency(item.comparison_amount || 0) }}
              </td>
              <td v-if="showVariances && comparisonType !== 'none'" class="text-right"
                  :class="getVarianceClass(item.amount, item.comparison_amount)">
                {{ formatVariance(item.amount, item.comparison_amount) }}
              </td>
              <td v-if="showPercentages" class="text-right">
                {{ calculatePercentage(item.amount, totalCashFlow) }}%
              </td>
            </tr>

            <!-- Net Financing Cash Flow -->
            <tr class="total-row">
              <td class="font-weight-bold">Net Cash from Financing Activities</td>
              <td class="text-right font-weight-bold" :class="{ 'text-success': netFinancingCashFlow > 0, 'text-danger': netFinancingCashFlow < 0 }">
                {{ formatCurrency(netFinancingCashFlow) }}
              </td>
              <td v-if="comparisonType !== 'none'" class="text-right font-weight-bold"
                  :class="{ 'text-success': netComparisonFinancingCashFlow > 0, 'text-danger': netComparisonFinancingCashFlow < 0 }">
                {{ formatCurrency(netComparisonFinancingCashFlow) }}
              </td>
              <td v-if="showVariances && comparisonType !== 'none'" class="text-right font-weight-bold"
                  :class="getVarianceClass(netFinancingCashFlow, netComparisonFinancingCashFlow)">
                {{ formatVariance(netFinancingCashFlow, netComparisonFinancingCashFlow) }}
              </td>
              <td v-if="showPercentages" class="text-right font-weight-bold">
                {{ calculatePercentage(netFinancingCashFlow, totalCashFlow) }}%
              </td>
            </tr>

            <!-- Net Change in Cash -->
            <tr class="grand-total-row">
              <td class="font-weight-bold">Net Change in Cash</td>
              <td class="text-right font-weight-bold" :class="{ 'text-success': netCashChange > 0, 'text-danger': netCashChange < 0 }">
                {{ formatCurrency(netCashChange) }}
              </td>
              <td v-if="comparisonType !== 'none'" class="text-right font-weight-bold"
                  :class="{ 'text-success': netComparisonCashChange > 0, 'text-danger': netComparisonCashChange < 0 }">
                {{ formatCurrency(netComparisonCashChange) }}
              </td>
              <td v-if="showVariances && comparisonType !== 'none'" class="text-right font-weight-bold"
                  :class="getVarianceClass(netCashChange, netComparisonCashChange)">
                {{ formatVariance(netCashChange, netComparisonCashChange) }}
              </td>
              <td v-if="showPercentages" class="text-right font-weight-bold">
                100%
              </td>
            </tr>

            <!-- Ending Cash Balance -->
            <tr class="ending-balance">
              <td class="font-weight-bold">Ending Cash Balance</td>
              <td class="text-right font-weight-bold">{{ formatCurrency(endingBalance) }}</td>
              <td v-if="comparisonType !== 'none'" class="text-right font-weight-bold">
                {{ formatCurrency(comparisonEndingBalance) }}
              </td>
              <td v-if="showVariances && comparisonType !== 'none'" class="text-right font-weight-bold"
                  :class="getVarianceClass(endingBalance, comparisonEndingBalance)">
                {{ formatVariance(endingBalance, comparisonEndingBalance) }}
              </td>
              <td v-if="showPercentages" class="text-right">
                &nbsp;
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

// Script Section
<script>
import axios from 'axios';
import { ref, computed, onMounted, watch } from 'vue';
import html2pdf from 'html2pdf.js';
import * as XLSX from 'xlsx';

export default {
  name: 'CashFlowReport',
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
      beginningBalance: 0,
      comparisonBeginningBalance: 0,
      operating: [],
      investing: [],
      financing: []
    });

    // Computed properties
    const comparisonLabel = computed(() => {
      switch (comparisonType.value) {
        case 'previous_period':
          return 'Previous Period';
        case 'previous_year':
          return 'Previous Year';
        default:
          return '';
      }
    });

    const filteredOperatingItems = computed(() => {
      if (!reportData.value.operating) return [];

      return reportData.value.operating.filter(item => {
        if (!showZeroAmounts.value && item.amount === 0 && (!item.comparison_amount || item.comparison_amount === 0)) {
          return false;
        }
        return true;
      });
    });

    const filteredInvestingItems = computed(() => {
      if (!reportData.value.investing) return [];

      return reportData.value.investing.filter(item => {
        if (!showZeroAmounts.value && item.amount === 0 && (!item.comparison_amount || item.comparison_amount === 0)) {
          return false;
        }
        return true;
      });
    });

    const filteredFinancingItems = computed(() => {
      if (!reportData.value.financing) return [];

      return reportData.value.financing.filter(item => {
        if (!showZeroAmounts.value && item.amount === 0 && (!item.comparison_amount || item.comparison_amount === 0)) {
          return false;
        }
        return true;
      });
    });

    const netOperatingCashFlow = computed(() => {
      if (!reportData.value.operating) return 0;
      return reportData.value.operating.reduce((sum, item) => sum + (item.amount || 0), 0);
    });

    const netComparisonOperatingCashFlow = computed(() => {
      if (!reportData.value.operating || comparisonType.value === 'none') return 0;
      return reportData.value.operating.reduce((sum, item) => sum + (item.comparison_amount || 0), 0);
    });

    const netInvestingCashFlow = computed(() => {
      if (!reportData.value.investing) return 0;
      return reportData.value.investing.reduce((sum, item) => sum + (item.amount || 0), 0);
    });

    const netComparisonInvestingCashFlow = computed(() => {
      if (!reportData.value.investing || comparisonType.value === 'none') return 0;
      return reportData.value.investing.reduce((sum, item) => sum + (item.comparison_amount || 0), 0);
    });

    const netFinancingCashFlow = computed(() => {
      if (!reportData.value.financing) return 0;
      return reportData.value.financing.reduce((sum, item) => sum + (item.amount || 0), 0);
    });

    const netComparisonFinancingCashFlow = computed(() => {
      if (!reportData.value.financing || comparisonType.value === 'none') return 0;
      return reportData.value.financing.reduce((sum, item) => sum + (item.comparison_amount || 0), 0);
    });

    const netCashChange = computed(() => {
      return netOperatingCashFlow.value + netInvestingCashFlow.value + netFinancingCashFlow.value;
    });

    const netComparisonCashChange = computed(() => {
      return netComparisonOperatingCashFlow.value + netComparisonInvestingCashFlow.value + netComparisonFinancingCashFlow.value;
    });

    const totalCashFlow = computed(() => {
      // Use absolute values for percentage calculations
      return Math.abs(netOperatingCashFlow.value) + Math.abs(netInvestingCashFlow.value) + Math.abs(netFinancingCashFlow.value);
    });

    const endingBalance = computed(() => {
      return (reportData.value.beginningBalance || 0) + netCashChange.value;
    });

    const comparisonEndingBalance = computed(() => {
      return (reportData.value.comparisonBeginningBalance || 0) + netComparisonCashChange.value;
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
      return Math.round((Math.abs(amount) / total) * 100);
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

    const loadCashFlow = async () => {
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

        const response = await axios.get('/api/accounting/reports/cash-flow', { params });
        reportData.value = response.data.data;
      } catch (error) {
        console.error('Error loading cash flow:', error);
        reportData.value = {
          beginningBalance: 0,
          comparisonBeginningBalance: 0,
          operating: [],
          investing: [],
          financing: []
        };
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
        filename: `cash_flow_${startDate.value}_to_${endDate.value}.pdf`,
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
      let headers = ['Description', 'Current Period'];
      if (comparisonType.value !== 'none') headers.push(comparisonLabel.value);
      if (showVariances.value && comparisonType.value !== 'none') headers.push('Variance');
      if (showPercentages.value) headers.push('% of Total');

      excelData.push(headers);

      // Add Beginning Balance
      const beginningBalanceRow = ['Beginning Cash Balance', reportData.value.beginningBalance];
      if (comparisonType.value !== 'none') beginningBalanceRow.push(reportData.value.comparisonBeginningBalance || 0);
      if (showVariances.value && comparisonType.value !== 'none') beginningBalanceRow.push(reportData.value.beginningBalance - (reportData.value.comparisonBeginningBalance || 0));
      if (showPercentages.value) beginningBalanceRow.push('');
      excelData.push(beginningBalanceRow);

      // Add Operating Activities
      excelData.push(['Cash Flows from Operating Activities']);

      filteredOperatingItems.value.forEach(item => {
        const row = [item.description, item.amount];
        if (comparisonType.value !== 'none') row.push(item.comparison_amount || 0);
        if (showVariances.value && comparisonType.value !== 'none') row.push(item.amount - (item.comparison_amount || 0));
        if (showPercentages.value) row.push(calculatePercentage(item.amount, totalCashFlow.value));
        excelData.push(row);
      });

      // Add Operating Activities Total
      const operatingTotalRow = ['Net Cash from Operating Activities', netOperatingCashFlow.value];
      if (comparisonType.value !== 'none') operatingTotalRow.push(netComparisonOperatingCashFlow.value);
      if (showVariances.value && comparisonType.value !== 'none') operatingTotalRow.push(netOperatingCashFlow.value - netComparisonOperatingCashFlow.value);
      if (showPercentages.value) operatingTotalRow.push(calculatePercentage(netOperatingCashFlow.value, totalCashFlow.value));
      excelData.push(operatingTotalRow);

      // Add Investing Activities
      excelData.push(['Cash Flows from Investing Activities']);

      filteredInvestingItems.value.forEach(item => {
        const row = [item.description, item.amount];
        if (comparisonType.value !== 'none') row.push(item.comparison_amount || 0);
        if (showVariances.value && comparisonType.value !== 'none') row.push(item.amount - (item.comparison_amount || 0));
        if (showPercentages.value) row.push(calculatePercentage(item.amount, totalCashFlow.value));
        excelData.push(row);
      });

      // Add Investing Activities Total
      const investingTotalRow = ['Net Cash from Investing Activities', netInvestingCashFlow.value];
      if (comparisonType.value !== 'none') investingTotalRow.push(netComparisonInvestingCashFlow.value);
      if (showVariances.value && comparisonType.value !== 'none') investingTotalRow.push(netInvestingCashFlow.value - netComparisonInvestingCashFlow.value);
      if (showPercentages.value) investingTotalRow.push(calculatePercentage(netInvestingCashFlow.value, totalCashFlow.value));
      excelData.push(investingTotalRow);

      // Add Financing Activities
      excelData.push(['Cash Flows from Financing Activities']);

      filteredFinancingItems.value.forEach(item => {
        const row = [item.description, item.amount];
        if (comparisonType.value !== 'none') row.push(item.comparison_amount || 0);
        if (showVariances.value && comparisonType.value !== 'none') row.push(item.amount - (item.comparison_amount || 0));
        if (showPercentages.value) row.push(calculatePercentage(item.amount, totalCashFlow.value));
        excelData.push(row);
      });

      // Add Financing Activities Total
      const financingTotalRow = ['Net Cash from Financing Activities', netFinancingCashFlow.value];
      if (comparisonType.value !== 'none') financingTotalRow.push(netComparisonFinancingCashFlow.value);
      if (showVariances.value && comparisonType.value !== 'none') financingTotalRow.push(netFinancingCashFlow.value - netComparisonFinancingCashFlow.value);
      if (showPercentages.value) financingTotalRow.push(calculatePercentage(netFinancingCashFlow.value, totalCashFlow.value));
      excelData.push(financingTotalRow);

      // Add Net Change in Cash
      const netChangeRow = ['Net Change in Cash', netCashChange.value];
      if (comparisonType.value !== 'none') netChangeRow.push(netComparisonCashChange.value);
      if (showVariances.value && comparisonType.value !== 'none') netChangeRow.push(netCashChange.value - netComparisonCashChange.value);
      if (showPercentages.value) netChangeRow.push(100);
      excelData.push(netChangeRow);

      // Add Ending Balance
      const endingBalanceRow = ['Ending Cash Balance', endingBalance.value];
      if (comparisonType.value !== 'none') endingBalanceRow.push(comparisonEndingBalance.value);
      if (showVariances.value && comparisonType.value !== 'none') endingBalanceRow.push(endingBalance.value - comparisonEndingBalance.value);
      if (showPercentages.value) endingBalanceRow.push('');
      excelData.push(endingBalanceRow);

      const worksheet = XLSX.utils.aoa_to_sheet(excelData);
      const workbook = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(workbook, worksheet, 'Cash Flow');

      // Generate Excel file
      XLSX.writeFile(workbook, `cash_flow_${startDate.value}_to_${endDate.value}.xlsx`);
    };

    // Initialize component
    onMounted(async () => {
      await loadAccountingPeriods();
      loadCashFlow();
    });

    // Watch for changes in comparison type
    watch(comparisonType, () => {
      if (startDate.value && endDate.value) {
        loadCashFlow();
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
      filteredOperatingItems,
      filteredInvestingItems,
      filteredFinancingItems,
      netOperatingCashFlow,
      netComparisonOperatingCashFlow,
      netInvestingCashFlow,
      netComparisonInvestingCashFlow,
      netFinancingCashFlow,
      netComparisonFinancingCashFlow,
      netCashChange,
      netComparisonCashChange,
      totalCashFlow,
      endingBalance,
      comparisonEndingBalance,
      formatCurrency,
      formatDate,
      calculatePercentage,
      formatVariance,
      getVarianceClass,
      updateDateRange,
      loadCashFlow,
      printReport,
      exportPdf,
      exportExcel
    };
  }
};
</script>

// Style Section
<style scoped>
.cash-flow-report {
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

.beginning-balance, .ending-balance {
  background-color: var(--gray-50);
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

  .cash-flow-report {
    padding: 0;
  }
}
</style>
