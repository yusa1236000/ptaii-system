<!-- src/views/accounting/reports/BalanceSheetReport.vue -->
<template>
    <div class="balance-sheet-report">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="page-title">Balance Sheet</h1>
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
                <select v-model="selectedPeriod" class="form-control" @change="updateAsOfDate">
                  <option v-for="period in periods" :key="period.period_id" :value="period.period_id">
                    {{ period.period_name }}
                  </option>
                </select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>As of Date</label>
                <input type="date" v-model="asOfDate" class="form-control">
              </div>
            </div>
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
          </div>

          <div class="row mt-3">
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
            <div class="col-md-4 text-right">
              <div class="form-group">
                <label>&nbsp;</label>
                <div class="mt-2">
                  <button class="btn btn-primary" @click="loadBalanceSheet">
                    <i class="fas fa-sync-alt mr-1"></i> Generate Report
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="report-container" ref="reportContainer">
        <div class="report-header text-center mb-4">
          <h2 class="company-name">Your Company Name</h2>
          <h3 class="report-title">Balance Sheet</h3>
          <p class="report-period">
            As of {{ formatDate(asOfDate) }}
            <span v-if="comparisonType !== 'none'">
              compared to {{ comparisonLabel }}
            </span>
          </p>
        </div>

        <div v-if="isLoading" class="text-center py-5">
          <i class="fas fa-spinner fa-spin fa-2x"></i>
          <p class="mt-2">Loading balance sheet data...</p>
        </div>

        <div v-else-if="!reportData.assets || reportData.assets.length === 0" class="text-center py-5">
          <i class="fas fa-exclamation-circle fa-2x text-warning"></i>
          <p class="mt-2">No balance sheet data available for the selected period.</p>
        </div>

        <div v-else class="table-responsive">
          <table class="table table-bordered">
            <thead>
              <tr class="bg-light">
                <th style="width: 40%">Account</th>
                <th class="text-right" style="width: 15%">Current</th>
                <th v-if="comparisonType !== 'none'" class="text-right" style="width: 15%">
                  {{ comparisonLabel }}
                </th>
                <th v-if="showVariances && comparisonType !== 'none'" class="text-right" style="width: 15%">
                  Variance
                </th>
                <th v-if="showPercentages" class="text-right" style="width: 15%">
                  % of Total
                </th>
              </tr>
            </thead>

            <tbody>
              <!-- Assets Section -->
              <tr class="section-header">
                <td colspan="5" class="font-weight-bold bg-light">Assets</td>
              </tr>

              <template v-for="(category, categoryIndex) in filteredAssets" :key="'asset-cat-' + categoryIndex">
                <!-- Category Header -->
                <tr class="category-header">
                  <td colspan="5">{{ category.name }}</td>
                </tr>

                <!-- Category Items -->
                <tr v-for="(item, itemIndex) in category.accounts" :key="'asset-' + categoryIndex + '-' + itemIndex" class="item-row">
                  <td>
                    <span class="ml-3">{{ item.account_name }}</span>
                  </td>
                  <td class="text-right">{{ formatCurrency(item.amount) }}</td>
                  <td v-if="comparisonType !== 'none'" class="text-right">
                    {{ formatCurrency(item.comparison_amount || 0) }}
                  </td>
                  <td v-if="showVariances && comparisonType !== 'none'" class="text-right"
                      :class="getVarianceClass(item.amount, item.comparison_amount)">
                    {{ formatVariance(item.amount, item.comparison_amount) }}
                  </td>
                  <td v-if="showPercentages" class="text-right">
                    {{ calculatePercentage(item.amount, totalAssets) }}%
                  </td>
                </tr>

                <!-- Category Subtotal -->
                <tr class="subtotal-row">
                  <td>
                    <span>Total {{ category.name }}</span>
                  </td>
                  <td class="text-right font-weight-bold">{{ formatCurrency(category.total) }}</td>
                  <td v-if="comparisonType !== 'none'" class="text-right font-weight-bold">
                    {{ formatCurrency(category.comparison_total || 0) }}
                  </td>
                  <td v-if="showVariances && comparisonType !== 'none'" class="text-right font-weight-bold"
                      :class="getVarianceClass(category.total, category.comparison_total)">
                    {{ formatVariance(category.total, category.comparison_total) }}
                  </td>
                  <td v-if="showPercentages" class="text-right font-weight-bold">
                    {{ calculatePercentage(category.total, totalAssets) }}%
                  </td>
                </tr>
              </template>

              <!-- Total Assets Row -->
              <tr class="total-row">
                <td class="font-weight-bold">Total Assets</td>
                <td class="text-right font-weight-bold">{{ formatCurrency(totalAssets) }}</td>
                <td v-if="comparisonType !== 'none'" class="text-right font-weight-bold">
                  {{ formatCurrency(totalComparisonAssets) }}
                </td>
                <td v-if="showVariances && comparisonType !== 'none'" class="text-right font-weight-bold"
                    :class="getVarianceClass(totalAssets, totalComparisonAssets)">
                  {{ formatVariance(totalAssets, totalComparisonAssets) }}
                </td>
                <td v-if="showPercentages" class="text-right font-weight-bold">
                  100%
                </td>
              </tr>

              <!-- Liabilities Section -->
              <tr class="section-header">
                <td colspan="5" class="font-weight-bold bg-light">Liabilities</td>
              </tr>

              <template v-for="(category, categoryIndex) in filteredLiabilities" :key="'liability-cat-' + categoryIndex">
                <!-- Category Header -->
                <tr class="category-header">
                  <td colspan="5">{{ category.name }}</td>
                </tr>

                <!-- Category Items -->
                <tr v-for="(item, itemIndex) in category.accounts" :key="'liability-' + categoryIndex + '-' + itemIndex" class="item-row">
                  <td>
                    <span class="ml-3">{{ item.account_name }}</span>
                  </td>
                  <td class="text-right">{{ formatCurrency(item.amount) }}</td>
                  <td v-if="comparisonType !== 'none'" class="text-right">
                    {{ formatCurrency(item.comparison_amount || 0) }}
                  </td>
                  <td v-if="showVariances && comparisonType !== 'none'" class="text-right"
                      :class="getVarianceClass(item.amount, item.comparison_amount)">
                    {{ formatVariance(item.amount, item.comparison_amount) }}
                  </td>
                  <td v-if="showPercentages" class="text-right">
                    {{ calculatePercentage(item.amount, totalLiabilities + totalEquity) }}%
                  </td>
                </tr>

                <!-- Category Subtotal -->
                <tr class="subtotal-row">
                  <td>
                    <span>Total {{ category.name }}</span>
                  </td>
                  <td class="text-right font-weight-bold">{{ formatCurrency(category.total) }}</td>
                  <td v-if="comparisonType !== 'none'" class="text-right font-weight-bold">
                    {{ formatCurrency(category.comparison_total || 0) }}
                  </td>
                  <td v-if="showVariances && comparisonType !== 'none'" class="text-right font-weight-bold"
                      :class="getVarianceClass(category.total, category.comparison_total)">
                    {{ formatVariance(category.total, category.comparison_total) }}
                  </td>
                  <td v-if="showPercentages" class="text-right font-weight-bold">
                    {{ calculatePercentage(category.total, totalLiabilities + totalEquity) }}%
                  </td>
                </tr>
              </template>

              <!-- Total Liabilities Row -->
              <tr class="total-row">
                <td class="font-weight-bold">Total Liabilities</td>
                <td class="text-right font-weight-bold">{{ formatCurrency(totalLiabilities) }}</td>
                <td v-if="comparisonType !== 'none'" class="text-right font-weight-bold">
                  {{ formatCurrency(totalComparisonLiabilities) }}
                </td>
                <td v-if="showVariances && comparisonType !== 'none'" class="text-right font-weight-bold"
                    :class="getVarianceClass(totalLiabilities, totalComparisonLiabilities)">
                  {{ formatVariance(totalLiabilities, totalComparisonLiabilities) }}
                </td>
                <td v-if="showPercentages" class="text-right font-weight-bold">
                  {{ calculatePercentage(totalLiabilities, totalLiabilities + totalEquity) }}%
                </td>
              </tr>

              <!-- Equity Section -->
              <tr class="section-header">
                <td colspan="5" class="font-weight-bold bg-light">Equity</td>
              </tr>

              <template v-for="(category, categoryIndex) in filteredEquity" :key="'equity-cat-' + categoryIndex">
                <!-- Category Header -->
                <tr class="category-header">
                  <td colspan="5">{{ category.name }}</td>
                </tr>

                <!-- Category Items -->
                <tr v-for="(item, itemIndex) in category.accounts" :key="'equity-' + categoryIndex + '-' + itemIndex" class="item-row">
                  <td>
                    <span class="ml-3">{{ item.account_name }}</span>
                  </td>
                  <td class="text-right">{{ formatCurrency(item.amount) }}</td>
                  <td v-if="comparisonType !== 'none'" class="text-right">
                    {{ formatCurrency(item.comparison_amount || 0) }}
                  </td>
                  <td v-if="showVariances && comparisonType !== 'none'" class="text-right"
                      :class="getVarianceClass(item.amount, item.comparison_amount)">
                    {{ formatVariance(item.amount, item.comparison_amount) }}
                  </td>
                  <td v-if="showPercentages" class="text-right">
                    {{ calculatePercentage(item.amount, totalLiabilities + totalEquity) }}%
                  </td>
                </tr>

                <!-- Category Subtotal -->
                <tr class="subtotal-row">
                  <td>
                    <span>Total {{ category.name }}</span>
                  </td>
                  <td class="text-right font-weight-bold">{{ formatCurrency(category.total) }}</td>
                  <td v-if="comparisonType !== 'none'" class="text-right font-weight-bold">
                    {{ formatCurrency(category.comparison_total || 0) }}
                  </td>
                  <td v-if="showVariances && comparisonType !== 'none'" class="text-right font-weight-bold"
                      :class="getVarianceClass(category.total, category.comparison_total)">
                    {{ formatVariance(category.total, category.comparison_total) }}
                  </td>
                  <td v-if="showPercentages" class="text-right font-weight-bold">
                    {{ calculatePercentage(category.total, totalLiabilities + totalEquity) }}%
                  </td>
                </tr>
              </template>

              <!-- Total Equity Row -->
              <tr class="total-row">
                <td class="font-weight-bold">Total Equity</td>
                <td class="text-right font-weight-bold">{{ formatCurrency(totalEquity) }}</td>
                <td v-if="comparisonType !== 'none'" class="text-right font-weight-bold">
                  {{ formatCurrency(totalComparisonEquity) }}
                </td>
                <td v-if="showVariances && comparisonType !== 'none'" class="text-right font-weight-bold"
                    :class="getVarianceClass(totalEquity, totalComparisonEquity)">
                  {{ formatVariance(totalEquity, totalComparisonEquity) }}
                </td>
                <td v-if="showPercentages" class="text-right font-weight-bold">
                  {{ calculatePercentage(totalEquity, totalLiabilities + totalEquity) }}%
                </td>
              </tr>

              <!-- Total Liabilities and Equity Row -->
              <tr class="grand-total-row">
                <td class="font-weight-bold">Total Liabilities and Equity</td>
                <td class="text-right font-weight-bold">{{ formatCurrency(totalLiabilities + totalEquity) }}</td>
                <td v-if="comparisonType !== 'none'" class="text-right font-weight-bold">
                  {{ formatCurrency(totalComparisonLiabilities + totalComparisonEquity) }}
                </td>
                <td v-if="showVariances && comparisonType !== 'none'" class="text-right font-weight-bold"
                    :class="getVarianceClass(totalLiabilities + totalEquity, totalComparisonLiabilities + totalComparisonEquity)">
                  {{ formatVariance(totalLiabilities + totalEquity, totalComparisonLiabilities + totalComparisonEquity) }}
                </td>
                <td v-if="showPercentages" class="text-right font-weight-bold">
                  100%
                </td>
              </tr>

                              <!-- Check if Balance Sheet balances -->
              <tr v-if="totalAssets !== (totalLiabilities + totalEquity)" class="unbalanced-row">
                <td colspan="5" class="text-center text-danger">
                  <strong>WARNING: Balance sheet is not balanced!</strong><br>
                  Difference: {{ formatCurrency(Math.abs(totalAssets - (totalLiabilities + totalEquity))) }}
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
    name: 'BalanceSheetReport',
    setup() {
      const reportContainer = ref(null);
      const isLoading = ref(true);
      const periods = ref([]);
      const selectedPeriod = ref(null);
      const asOfDate = ref('');
      const comparisonType = ref('none');
      const showPercentages = ref(true);
      const showVariances = ref(true);
      const showZeroAmounts = ref(false);

      const reportData = ref({
        assets: [],
        liabilities: [],
        equity: []
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

      const filteredAssets = computed(() => {
        if (!reportData.value.assets) return [];

        return reportData.value.assets.map(category => {
          // Filter accounts based on zero amounts setting
          const filteredAccounts = showZeroAmounts.value
            ? category.accounts
            : category.accounts.filter(account =>
                account.amount !== 0 || (account.comparison_amount && account.comparison_amount !== 0)
              );

          return {
            ...category,
            accounts: filteredAccounts
          };
        }).filter(category => category.accounts.length > 0); // Only show categories with accounts
      });

      const filteredLiabilities = computed(() => {
        if (!reportData.value.liabilities) return [];

        return reportData.value.liabilities.map(category => {
          // Filter accounts based on zero amounts setting
          const filteredAccounts = showZeroAmounts.value
            ? category.accounts
            : category.accounts.filter(account =>
                account.amount !== 0 || (account.comparison_amount && account.comparison_amount !== 0)
              );

          return {
            ...category,
            accounts: filteredAccounts
          };
        }).filter(category => category.accounts.length > 0); // Only show categories with accounts
      });

      const filteredEquity = computed(() => {
        if (!reportData.value.equity) return [];

        return reportData.value.equity.map(category => {
          // Filter accounts based on zero amounts setting
          const filteredAccounts = showZeroAmounts.value
            ? category.accounts
            : category.accounts.filter(account =>
                account.amount !== 0 || (account.comparison_amount && account.comparison_amount !== 0)
              );

          return {
            ...category,
            accounts: filteredAccounts
          };
        }).filter(category => category.accounts.length > 0); // Only show categories with accounts
      });

      const totalAssets = computed(() => {
        if (!reportData.value.assets) return 0;
        return reportData.value.assets.reduce((sum, category) => sum + (category.total || 0), 0);
      });

      const totalComparisonAssets = computed(() => {
        if (!reportData.value.assets || comparisonType.value === 'none') return 0;
        return reportData.value.assets.reduce((sum, category) => sum + (category.comparison_total || 0), 0);
      });

      const totalLiabilities = computed(() => {
        if (!reportData.value.liabilities) return 0;
        return reportData.value.liabilities.reduce((sum, category) => sum + (category.total || 0), 0);
      });

      const totalComparisonLiabilities = computed(() => {
        if (!reportData.value.liabilities || comparisonType.value === 'none') return 0;
        return reportData.value.liabilities.reduce((sum, category) => sum + (category.comparison_total || 0), 0);
      });

      const totalEquity = computed(() => {
        if (!reportData.value.equity) return 0;
        return reportData.value.equity.reduce((sum, category) => sum + (category.total || 0), 0);
      });

      const totalComparisonEquity = computed(() => {
        if (!reportData.value.equity || comparisonType.value === 'none') return 0;
        return reportData.value.equity.reduce((sum, category) => sum + (category.comparison_total || 0), 0);
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
            updateAsOfDate();
          }
        } catch (error) {
          console.error('Error loading accounting periods:', error);
        }
      };

      const updateAsOfDate = () => {
        const period = periods.value.find(p => p.period_id === selectedPeriod.value);
        if (period) {
          asOfDate.value = period.end_date;
        }
      };

      const loadBalanceSheet = async () => {
        isLoading.value = true;

        try {
          const params = {
            as_of_date: asOfDate.value,
            period_id: selectedPeriod.value
          };

          if (comparisonType.value !== 'none') {
            params.comparison_type = comparisonType.value;
          }

          const response = await axios.get('/api/accounting/reports/balance-sheet', { params });
          reportData.value = response.data.data;
        } catch (error) {
          console.error('Error loading balance sheet:', error);
          reportData.value = { assets: [], liabilities: [], equity: [] };
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
          filename: `balance_sheet_${asOfDate.value}.pdf`,
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
        let headers = ['Account', 'Current'];
        if (comparisonType.value !== 'none') headers.push(comparisonLabel.value);
        if (showVariances.value && comparisonType.value !== 'none') headers.push('Variance');
        if (showPercentages.value) headers.push('% of Total');

        excelData.push(headers);

        // Add Assets section
        excelData.push(['ASSETS']);

        filteredAssets.value.forEach(category => {
          excelData.push([category.name]);

          // Add accounts
          category.accounts.forEach(account => {
            const row = [' ' + account.account_name, account.amount];
            if (comparisonType.value !== 'none') row.push(account.comparison_amount || 0);
            if (showVariances.value && comparisonType.value !== 'none') row.push(account.amount - (account.comparison_amount || 0));
            if (showPercentages.value) row.push(calculatePercentage(account.amount, totalAssets.value));
            excelData.push(row);
          });

          // Add category total
          const categoryTotalRow = [' Total ' + category.name, category.total];
          if (comparisonType.value !== 'none') categoryTotalRow.push(category.comparison_total || 0);
          if (showVariances.value && comparisonType.value !== 'none') categoryTotalRow.push(category.total - (category.comparison_total || 0));
          if (showPercentages.value) categoryTotalRow.push(calculatePercentage(category.total, totalAssets.value));
          excelData.push(categoryTotalRow);
        });

        // Add Total Assets row
        const totalAssetsRow = ['Total Assets', totalAssets.value];
        if (comparisonType.value !== 'none') totalAssetsRow.push(totalComparisonAssets.value);
        if (showVariances.value && comparisonType.value !== 'none') totalAssetsRow.push(totalAssets.value - totalComparisonAssets.value);
        if (showPercentages.value) totalAssetsRow.push(100);
        excelData.push(totalAssetsRow);

        // Add empty row
        excelData.push([]);

        // Add Liabilities section
        excelData.push(['LIABILITIES']);

        filteredLiabilities.value.forEach(category => {
          excelData.push([category.name]);

          // Add accounts
          category.accounts.forEach(account => {
            const row = [' ' + account.account_name, account.amount];
            if (comparisonType.value !== 'none') row.push(account.comparison_amount || 0);
            if (showVariances.value && comparisonType.value !== 'none') row.push(account.amount - (account.comparison_amount || 0));
            if (showPercentages.value) row.push(calculatePercentage(account.amount, totalLiabilities.value + totalEquity.value));
            excelData.push(row);
          });

          // Add category total
          const categoryTotalRow = [' Total ' + category.name, category.total];
          if (comparisonType.value !== 'none') categoryTotalRow.push(category.comparison_total || 0);
          if (showVariances.value && comparisonType.value !== 'none') categoryTotalRow.push(category.total - (category.comparison_total || 0));
          if (showPercentages.value) categoryTotalRow.push(calculatePercentage(category.total, totalLiabilities.value + totalEquity.value));
          excelData.push(categoryTotalRow);
        });

        // Add Total Liabilities row
        const totalLiabilitiesRow = ['Total Liabilities', totalLiabilities.value];
        if (comparisonType.value !== 'none') totalLiabilitiesRow.push(totalComparisonLiabilities.value);
        if (showVariances.value && comparisonType.value !== 'none') totalLiabilitiesRow.push(totalLiabilities.value - totalComparisonLiabilities.value);
        if (showPercentages.value) totalLiabilitiesRow.push(calculatePercentage(totalLiabilities.value, totalLiabilities.value + totalEquity.value));
        excelData.push(totalLiabilitiesRow);

        // Add empty row
        excelData.push([]);

        // Add Equity section
        excelData.push(['EQUITY']);

        filteredEquity.value.forEach(category => {
          excelData.push([category.name]);

          // Add accounts
          category.accounts.forEach(account => {
            const row = [' ' + account.account_name, account.amount];
            if (comparisonType.value !== 'none') row.push(account.comparison_amount || 0);
            if (showVariances.value && comparisonType.value !== 'none') row.push(account.amount - (account.comparison_amount || 0));
            if (showPercentages.value) row.push(calculatePercentage(account.amount, totalLiabilities.value + totalEquity.value));
            excelData.push(row);
          });

          // Add category total
          const categoryTotalRow = [' Total ' + category.name, category.total];
          if (comparisonType.value !== 'none') categoryTotalRow.push(category.comparison_total || 0);
          if (showVariances.value && comparisonType.value !== 'none') categoryTotalRow.push(category.total - (category.comparison_total || 0));
          if (showPercentages.value) categoryTotalRow.push(calculatePercentage(category.total, totalLiabilities.value + totalEquity.value));
          excelData.push(categoryTotalRow);
        });

        // Add Total Equity row
        const totalEquityRow = ['Total Equity', totalEquity.value];
        if (comparisonType.value !== 'none') totalEquityRow.push(totalComparisonEquity.value);
        if (showVariances.value && comparisonType.value !== 'none') totalEquityRow.push(totalEquity.value - totalComparisonEquity.value);
        if (showPercentages.value) totalEquityRow.push(calculatePercentage(totalEquity.value, totalLiabilities.value + totalEquity.value));
        excelData.push(totalEquityRow);

        // Add Total Liabilities and Equity row
        const totalLiabilitiesEquityRow = ['Total Liabilities and Equity', totalLiabilities.value + totalEquity.value];
        if (comparisonType.value !== 'none') totalLiabilitiesEquityRow.push(totalComparisonLiabilities.value + totalComparisonEquity.value);
        if (showVariances.value && comparisonType.value !== 'none') totalLiabilitiesEquityRow.push((totalLiabilities.value + totalEquity.value) - (totalComparisonLiabilities.value + totalComparisonEquity.value));
        if (showPercentages.value) totalLiabilitiesEquityRow.push(100);
        excelData.push(totalLiabilitiesEquityRow);

        const worksheet = XLSX.utils.aoa_to_sheet(excelData);
        const workbook = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(workbook, worksheet, 'Balance Sheet');

        // Generate Excel file
        XLSX.writeFile(workbook, `balance_sheet_${asOfDate.value}.xlsx`);
      };

      // Initialize component
      onMounted(async () => {
        await loadAccountingPeriods();
        loadBalanceSheet();
      });

      // Watch for changes in comparison type
      watch(comparisonType, () => {
        if (asOfDate.value) {
          loadBalanceSheet();
        }
      });

      return {
        reportContainer,
        isLoading,
        periods,
        selectedPeriod,
        asOfDate,
        comparisonType,
        comparisonLabel,
        showPercentages,
        showVariances,
        showZeroAmounts,
        reportData,
        filteredAssets,
        filteredLiabilities,
        filteredEquity,
        totalAssets,
        totalComparisonAssets,
        totalLiabilities,
        totalComparisonLiabilities,
        totalEquity,
        totalComparisonEquity,
        formatCurrency,
        formatDate,
        calculatePercentage,
        formatVariance,
        getVarianceClass,
        updateAsOfDate,
        loadBalanceSheet,
        printReport,
        exportPdf,
        exportExcel
      };
    }
  };
  </script>

  <style scoped>
  .balance-sheet-report {
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

  .category-header td {
    font-weight: 600;
    background-color: var(--gray-50);
    padding-top: 0.75rem;
    padding-bottom: 0.75rem;
  }

  .item-row:hover {
    background-color: var(--gray-50);
  }

  .subtotal-row {
    background-color: var(--gray-50);
  }

  .total-row {
    background-color: var(--primary-bg);
  }

  .grand-total-row {
    background-color: var(--primary-bg);
    font-weight: 700;
  }

  .unbalanced-row {
    background-color: var(--danger-bg);
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

    .balance-sheet-report {
      padding: 0;
    }
  }
  </style>
