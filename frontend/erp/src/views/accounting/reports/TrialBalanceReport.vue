<!-- src/views/accounting/reports/TrialBalanceReport.vue -->
<template>
    <div class="trial-balance-report">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="page-title">Trial Balance</h1>
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
                <select v-model="selectedPeriod" class="form-control" @change="loadTrialBalance">
                  <option v-for="period in periods" :key="period.period_id" :value="period.period_id">
                    {{ period.period_name }}
                  </option>
                </select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>As of Date</label>
                <input type="date" v-model="asOfDate" class="form-control" @change="loadTrialBalance">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Display Options</label>
                <div class="d-flex mt-2">
                  <div class="form-check mr-3">
                    <input class="form-check-input" type="checkbox" v-model="showZeroBalances" id="showZeroBalances" @change="applyFilters">
                    <label class="form-check-label" for="showZeroBalances">
                      Show Zero Balances
                    </label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" v-model="showPostingAccounts" id="showPostingAccounts" @change="applyFilters">
                    <label class="form-check-label" for="showPostingAccounts">
                      Show Posting Accounts Only
                    </label>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="report-container" ref="reportContainer">
        <div class="report-header text-center mb-4">
          <h2 class="company-name">Your Company Name</h2>
          <h3 class="report-title">Trial Balance</h3>
          <p class="report-period">
            {{ selectedPeriodName }}
            <span v-if="asOfDate"> | As of {{ formatDate(asOfDate) }}</span>
          </p>
        </div>

        <div v-if="isLoading" class="text-center py-5">
          <i class="fas fa-spinner fa-spin fa-2x"></i>
          <p class="mt-2">Loading trial balance...</p>
        </div>

        <div v-else-if="trialBalanceData.length === 0" class="text-center py-5">
          <i class="fas fa-exclamation-circle fa-2x text-warning"></i>
          <p class="mt-2">No trial balance data available for the selected period.</p>
        </div>

        <div v-else class="table-responsive">
          <table class="table table-bordered table-striped">
            <thead>
              <tr>
                <th>Account Code</th>
                <th>Account Name</th>
                <th class="text-right">Debit</th>
                <th class="text-right">Credit</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in filteredTrialBalance" :key="item.account_id">
                <td>{{ item.account_code }}</td>
                <td>{{ item.account_name }}</td>
                <td class="text-right">{{ item.debit > 0 ? formatCurrency(item.debit) : '' }}</td>
                <td class="text-right">{{ item.credit > 0 ? formatCurrency(item.credit) : '' }}</td>
              </tr>
            </tbody>
            <tfoot>
              <tr class="bg-light font-weight-bold">
                <td colspan="2">Totals</td>
                <td class="text-right">{{ formatCurrency(totalDebit) }}</td>
                <td class="text-right">{{ formatCurrency(totalCredit) }}</td>
              </tr>
              <tr v-if="totalDebit !== totalCredit" class="bg-danger text-white">
                <td colspan="4" class="text-center">
                  WARNING: Trial balance does not balance. Difference: {{ formatCurrency(Math.abs(totalDebit - totalCredit)) }}
                </td>
              </tr>
            </tfoot>
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
    name: 'TrialBalanceReport',
    setup() {
      const reportContainer = ref(null);
      const isLoading = ref(true);
      const periods = ref([]);
      const selectedPeriod = ref(null);
      const asOfDate = ref('');
      const showZeroBalances = ref(false);
      const showPostingAccounts = ref(false);

      const trialBalanceData = ref([]);
      const filteredTrialBalance = ref([]);

      // Computed properties
      const selectedPeriodName = computed(() => {
        const period = periods.value.find(p => p.period_id === selectedPeriod.value);
        return period ? period.period_name : '';
      });

      const totalDebit = computed(() => {
        return filteredTrialBalance.value.reduce((sum, item) => sum + (item.debit || 0), 0);
      });

      const totalCredit = computed(() => {
        return filteredTrialBalance.value.reduce((sum, item) => sum + (item.credit || 0), 0);
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
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        return new Date(dateString).toLocaleDateString('id-ID', options);
      };

      const loadAccountingPeriods = async () => {
        try {
          const response = await axios.get('/api/accounting/accounting-periods');
          periods.value = response.data.data;

          if (periods.value.length > 0) {
            // Find current period
            const currentPeriod = periods.value.find(p => p.status === 'Open');
            selectedPeriod.value = currentPeriod ? currentPeriod.period_id : periods.value[0].period_id;

            // Set date to end of the selected period
            const period = periods.value.find(p => p.period_id === selectedPeriod.value);
            if (period) {
              asOfDate.value = period.end_date;
            }
          }
        } catch (error) {
          console.error('Error loading accounting periods:', error);
        }
      };

      const loadTrialBalance = async () => {
        if (!selectedPeriod.value && !asOfDate.value) return;

        isLoading.value = true;
        try {
          const response = await axios.get('/api/accounting/reports/trial-balance', {
            params: {
              period_id: selectedPeriod.value,
              as_of_date: asOfDate.value
            }
          });

          trialBalanceData.value = response.data.data;
          applyFilters();
        } catch (error) {
          console.error('Error loading trial balance:', error);
          trialBalanceData.value = [];
          filteredTrialBalance.value = [];
        } finally {
          isLoading.value = false;
        }
      };

      const applyFilters = () => {
        filteredTrialBalance.value = trialBalanceData.value.filter(item => {
          // Filter zero balances if option is not checked
          if (!showZeroBalances.value && item.debit === 0 && item.credit === 0) {
            return false;
          }

          // Filter non-posting accounts if option is checked
          if (showPostingAccounts.value && !item.is_posting_account) {
            return false;
          }

          return true;
        });
      };

      const printReport = () => {
        window.print();
      };

      const exportPdf = () => {
        const element = reportContainer.value;
        const opt = {
          margin: [10, 10, 10, 10],
          filename: `trial_balance_${asOfDate.value}.pdf`,
          image: { type: 'jpeg', quality: 0.98 },
          html2canvas: { scale: 2 },
          jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };

        html2pdf().set(opt).from(element).save();
      };

      const exportExcel = () => {
        // Prepare data for Excel export
        const excelData = filteredTrialBalance.value.map(item => ({
          'Account Code': item.account_code,
          'Account Name': item.account_name,
          'Debit': item.debit,
          'Credit': item.credit
        }));

        // Add totals row
        excelData.push({
          'Account Code': '',
          'Account Name': 'Totals',
          'Debit': totalDebit.value,
          'Credit': totalCredit.value
        });

        const worksheet = XLSX.utils.json_to_sheet(excelData);
        const workbook = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(workbook, worksheet, 'Trial Balance');

        // Generate Excel file
        XLSX.writeFile(workbook, `trial_balance_${asOfDate.value}.xlsx`);
      };

      // Initialize component
      onMounted(async () => {
        await loadAccountingPeriods();
        loadTrialBalance();
      });

      // Watch for changes in period or date
      watch([selectedPeriod, asOfDate], () => {
        loadTrialBalance();
      });

      return {
        reportContainer,
        isLoading,
        periods,
        selectedPeriod,
        selectedPeriodName,
        asOfDate,
        showZeroBalances,
        showPostingAccounts,
        trialBalanceData,
        filteredTrialBalance,
        totalDebit,
        totalCredit,
        formatCurrency,
        formatDate,
        loadTrialBalance,
        applyFilters,
        printReport,
        exportPdf,
        exportExcel
      };
    }
  };
  </script>

  <style scoped>
  .trial-balance-report {
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

  @media print {
    .btn, .card, .d-flex:not(.report-header) {
      display: none !important;
    }

    .report-container {
      padding: 0;
      box-shadow: none;
    }

    .trial-balance-report {
      padding: 0;
    }
  }
  </style>
