<!-- src/views/accounting/FiscalYearSetup.vue -->
<template>
    <div class="fiscal-year-setup">
      <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
          <h2>Pengaturan Tahun Fiskal</h2>
          <router-link to="/accounting/periods" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Kembali ke Periode
          </router-link>
        </div>
      </div>

      <div class="row">
        <!-- Fiscal Year Setup Card -->
        <div class="col-md-7">
          <div class="card mb-4">
            <div class="card-header">
              <h3 class="card-title">Buat Periode Tahun Fiskal</h3>
            </div>
            <div class="card-body">
              <div v-if="isLoading" class="text-center py-4">
                <i class="fas fa-spinner fa-spin fa-2x"></i>
                <p class="mt-2">Memuat data...</p>
              </div>

              <div v-else-if="error" class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                {{ error }}
              </div>

              <form v-else @submit.prevent="generatePeriods" class="fiscal-year-form">
                <div class="form-group">
                  <label for="fiscalYearName">Nama Tahun Fiskal <span class="text-danger">*</span></label>
                  <input
                    id="fiscalYearName"
                    v-model="form.fiscalYearName"
                    type="text"
                    class="form-control"
                    placeholder="Contoh: Tahun Fiskal 2024"
                    required
                  />
                  <small class="form-text text-muted">
                    Nama untuk tahun fiskal (biasanya tahun kalender)
                  </small>
                </div>

                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="startDate">Tanggal Mulai <span class="text-danger">*</span></label>
                      <input
                        id="startDate"
                        v-model="form.startDate"
                        type="date"
                        class="form-control"
                        required
                        @change="updatePeriodPreview"
                      />
                    </div>
                  </div>

                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="endDate">Tanggal Akhir <span class="text-danger">*</span></label>
                      <input
                        id="endDate"
                        v-model="form.endDate"
                        type="date"
                        class="form-control"
                        required
                        @change="updatePeriodPreview"
                      />
                      <small v-if="dateError" class="text-danger">
                        {{ dateError }}
                      </small>
                    </div>
                  </div>
                </div>

                <div class="form-group">
                  <label for="periodType">Jenis Periode <span class="text-danger">*</span></label>
                  <select
                    id="periodType"
                    v-model="form.periodType"
                    class="form-control"
                    required
                    @change="updatePeriodPreview"
                  >
                    <option value="monthly">Bulanan</option>
                    <option value="quarterly">Kuartalan</option>
                    <option value="half-yearly">Semester</option>
                    <option value="yearly">Tahunan</option>
                  </select>
                </div>

                <div class="form-group">
                  <label for="periodNamingFormat">Format Penamaan Periode</label>
                  <select
                    id="periodNamingFormat"
                    v-model="form.periodNamingFormat"
                    class="form-control"
                    @change="updatePeriodPreview"
                  >
                    <template v-if="form.periodType === 'monthly'">
                      <option value="month-year">Bulan Tahun (Contoh: Januari 2024)</option>
                      <option value="month-abbr-year">Bulan Singkat Tahun (Contoh: Jan 2024)</option>
                      <option value="year-month">Tahun-Bulan (Contoh: 2024-01)</option>
                    </template>
                    <template v-else-if="form.periodType === 'quarterly'">
                      <option value="quarter-year">Kuartal Tahun (Contoh: Q1 2024)</option>
                      <option value="quarter-name-year">Nama Kuartal Tahun (Contoh: Kuartal 1 2024)</option>
                    </template>
                    <template v-else-if="form.periodType === 'half-yearly'">
                      <option value="half-year">Semester Tahun (Contoh: S1 2024)</option>
                      <option value="half-name-year">Nama Semester Tahun (Contoh: Semester 1 2024)</option>
                    </template>
                    <template v-else>
                      <option value="year">Tahun (Contoh: 2024)</option>
                      <option value="fiscal-year">Tahun Fiskal (Contoh: Tahun Fiskal 2024)</option>
                    </template>
                  </select>
                </div>

                <div class="form-check mb-4">
                  <input
                    id="autoOpenFirstPeriod"
                    v-model="form.autoOpenFirstPeriod"
                    type="checkbox"
                    class="form-check-input"
                  />
                  <label class="form-check-label" for="autoOpenFirstPeriod">
                    Otomatis buka periode pertama
                  </label>
                  <small class="form-text text-muted">
                    Jika dicentang, periode pertama dalam tahun fiskal akan dibuka secara otomatis
                  </small>
                </div>

                <button
                  type="submit"
                  class="btn btn-primary"
                  :disabled="isGenerating || !!dateError || !canGenerate"
                >
                  <i class="fas fa-calendar-check mr-1"></i>
                  <span v-if="isGenerating">Membuat periode...</span>
                  <span v-else>Buat Periode</span>
                </button>
              </form>
            </div>
          </div>
        </div>

        <!-- Period Preview Card -->
        <div class="col-md-5">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">Pratinjau Periode</h3>
            </div>
            <div class="card-body">
              <div v-if="previewPeriods.length === 0" class="text-center py-4">
                <i class="fas fa-calendar fa-2x text-muted mb-3"></i>
                <p>Isi formulir untuk melihat pratinjau periode</p>
              </div>
              <div v-else>
                <div class="alert alert-info mb-3">
                  <i class="fas fa-info-circle mr-2"></i>
                  {{ previewPeriods.length }} periode akan dibuat
                </div>

                <ul class="period-preview-list">
                  <li v-for="(period, index) in previewPeriods" :key="index" class="period-preview-item">
                    <div class="period-name">{{ period.name }}</div>
                    <div class="period-dates">{{ formatDate(period.startDate) }} - {{ formatDate(period.endDate) }}</div>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Success Message Card -->
      <div v-if="generationSuccess" class="card mt-4 bg-success text-white">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <i class="fas fa-check-circle fa-2x mr-3"></i>
            <div>
              <h4 class="mb-1">Periode Berhasil Dibuat!</h4>
              <p class="mb-0">{{ generatedPeriods.length }} periode untuk {{ form.fiscalYearName }} telah berhasil dibuat.</p>
            </div>
          </div>
          <div class="text-right mt-3">
            <router-link to="/accounting/periods" class="btn btn-outline-light">
              Lihat Semua Periode
            </router-link>
          </div>
        </div>
      </div>
    </div>
  </template>

  <script>
  import axios from 'axios';
  import { ref, reactive, computed, watch } from 'vue';
  import { useRouter } from 'vue-router';

  export default {
    name: 'FiscalYearSetup',
    setup() {
      const router = useRouter();

      const isLoading = ref(false);
      const isGenerating = ref(false);
      const error = ref(null);
      const dateError = ref(null);
      const generationSuccess = ref(false);
      const generatedPeriods = ref([]);

      // Default to current year for the form
      const currentDate = new Date();
      const currentYear = currentDate.getFullYear();
      const defaultStartDate = `${currentYear}-01-01`;
      const defaultEndDate = `${currentYear}-12-31`;

      const form = reactive({
        fiscalYearName: `Tahun Fiskal ${currentYear}`,
        startDate: defaultStartDate,
        endDate: defaultEndDate,
        periodType: 'monthly',
        periodNamingFormat: 'month-year',
        autoOpenFirstPeriod: true
      });

      const previewPeriods = ref([]);

      // Computed property to check if we can generate periods
      const canGenerate = computed(() => {
        return form.startDate &&
               form.endDate &&
               !dateError.value &&
               previewPeriods.value.length > 0;
      });

      // Watch for form changes to validate dates
      watch([() => form.startDate, () => form.endDate], () => {
        validateDates();
      });

      // Validate date range
      const validateDates = () => {
        dateError.value = null;

        if (form.startDate && form.endDate) {
          const startDate = new Date(form.startDate);
          const endDate = new Date(form.endDate);

          if (endDate < startDate) {
            dateError.value = 'Tanggal akhir tidak boleh lebih awal dari tanggal mulai';
            previewPeriods.value = [];
            return false;
          }

          // Check if date range is reasonable (not more than 5 years)
          const diffYears = (endDate - startDate) / (1000 * 60 * 60 * 24 * 365);
          if (diffYears > 5) {
            dateError.value = 'Rentang tanggal tidak boleh lebih dari 5 tahun';
            previewPeriods.value = [];
            return false;
          }
        }

        return true;
      };

      // Update period preview based on form inputs
      const updatePeriodPreview = () => {
        if (!validateDates()) return;
        if (!form.startDate || !form.endDate) {
          previewPeriods.value = [];
          return;
        }

        const startDate = new Date(form.startDate);
        const endDate = new Date(form.endDate);
        const periods = [];

        // Generate periods based on periodType
        switch (form.periodType) {
          case 'monthly':
            generateMonthlyPeriods(startDate, endDate, periods);
            break;
          case 'quarterly':
            generateQuarterlyPeriods(startDate, endDate, periods);
            break;
          case 'half-yearly':
            generateHalfYearlyPeriods(startDate, endDate, periods);
            break;
          case 'yearly':
            generateYearlyPeriods(startDate, endDate, periods);
            break;
        }

        previewPeriods.value = periods;
      };

      // Helper functions to generate different period types
      const generateMonthlyPeriods = (startDate, endDate, periods) => {
        let currentDate = new Date(startDate);

        while (currentDate <= endDate) {
          const year = currentDate.getFullYear();
          const month = currentDate.getMonth();

          // Calculate period end date (last day of the month)
          const periodEndDate = new Date(year, month + 1, 0);

          // If period would extend beyond the overall end date, cap it
          const actualPeriodEndDate = periodEndDate > endDate ? endDate : periodEndDate;

          // Format the period name
          let periodName = '';
          switch (form.periodNamingFormat) {
            case 'month-year':
              periodName = formatMonthYear(year, month);
              break;
            case 'month-abbr-year':
              periodName = formatMonthAbbrYear(year, month);
              break;
            case 'year-month':
              periodName = `${year}-${String(month + 1).padStart(2, '0')}`;
              break;
          }

          periods.push({
            name: periodName,
            startDate: new Date(currentDate),
            endDate: new Date(actualPeriodEndDate)
          });

          // Move to the first day of the next month
          currentDate = new Date(year, month + 1, 1);
        }
      };

      const generateQuarterlyPeriods = (startDate, endDate, periods) => {
        // Helper to get the quarter from a month (0-indexed)
        const getQuarter = (month) => Math.floor(month / 3) + 1;

        // Start from the beginning of the quarter containing the start date
        const startYear = startDate.getFullYear();
        const startMonth = startDate.getMonth();
        const startQuarter = getQuarter(startMonth);

        // First day of the start quarter
        let currentDate = new Date(startYear, (startQuarter - 1) * 3, 1);

        // If the calculated start date is before the actual start date, use the actual start date
        if (currentDate < startDate) {
          currentDate = new Date(startDate);
        }

        while (currentDate <= endDate) {
          const year = currentDate.getFullYear();
          const month = currentDate.getMonth();
          const quarter = getQuarter(month);

          // Calculate quarter end date (last day of the last month in the quarter)
          const quarterLastMonth = (quarter * 3) - 1;
          const periodEndDate = new Date(year, quarterLastMonth + 1, 0);

          // If period would extend beyond the overall end date, cap it
          const actualPeriodEndDate = periodEndDate > endDate ? endDate : periodEndDate;

          // Format the period name
          let periodName = '';
          switch (form.periodNamingFormat) {
            case 'quarter-year':
              periodName = `Q${quarter} ${year}`;
              break;
            case 'quarter-name-year':
              periodName = `Kuartal ${quarter} ${year}`;
              break;
          }

          periods.push({
            name: periodName,
            startDate: new Date(currentDate),
            endDate: new Date(actualPeriodEndDate)
          });

          // Move to the first day of the next quarter
          currentDate = new Date(year, quarter * 3, 1);
        }
      };

      const generateHalfYearlyPeriods = (startDate, endDate, periods) => {
        // Helper to get the half from a month (0-indexed)
        const getHalf = (month) => Math.floor(month / 6) + 1;

        // Start from the beginning of the half containing the start date
        const startYear = startDate.getFullYear();
        const startMonth = startDate.getMonth();
        const startHalf = getHalf(startMonth);

        // First day of the start half
        let currentDate = new Date(startYear, (startHalf - 1) * 6, 1);

        // If the calculated start date is before the actual start date, use the actual start date
        if (currentDate < startDate) {
          currentDate = new Date(startDate);
        }

        while (currentDate <= endDate) {
          const year = currentDate.getFullYear();
          const month = currentDate.getMonth();
          const half = getHalf(month);

          // Calculate half end date (last day of the last month in the half)
          const halfLastMonth = (half * 6) - 1;
          const periodEndDate = new Date(year, halfLastMonth + 1, 0);

          // If period would extend beyond the overall end date, cap it
          const actualPeriodEndDate = periodEndDate > endDate ? endDate : periodEndDate;

          // Format the period name
          let periodName = '';
          switch (form.periodNamingFormat) {
            case 'half-year':
              periodName = `S${half} ${year}`;
              break;
            case 'half-name-year':
              periodName = `Semester ${half} ${year}`;
              break;
          }

          periods.push({
            name: periodName,
            startDate: new Date(currentDate),
            endDate: new Date(actualPeriodEndDate)
          });

          // Move to the first day of the next half
          currentDate = new Date(year, half * 6, 1);
        }
      };

      const generateYearlyPeriods = (startDate, endDate, periods) => {
        // Start from the beginning of the year containing the start date
        const startYear = startDate.getFullYear();
        const endYear = endDate.getFullYear();

        for (let year = startYear; year <= endYear; year++) {
          const yearStart = new Date(year, 0, 1);
          const yearEnd = new Date(year, 11, 31);

          // Adjust if outside the overall range
          const actualYearStart = yearStart < startDate ? startDate : yearStart;
          const actualYearEnd = yearEnd > endDate ? endDate : yearEnd;

          // Format the period name
          let periodName = '';
          switch (form.periodNamingFormat) {
            case 'year':
              periodName = `${year}`;
              break;
            case 'fiscal-year':
              periodName = `Tahun Fiskal ${year}`;
              break;
          }

          periods.push({
            name: periodName,
            startDate: new Date(actualYearStart),
            endDate: new Date(actualYearEnd)
          });
        }
      };

      // Helper methods for formatting
      const formatMonthYear = (year, month) => {
        const months = [
          'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
          'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];
        return `${months[month]} ${year}`;
      };

      const formatMonthAbbrYear = (year, month) => {
        const monthsAbbr = [
          'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
          'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'
        ];
        return `${monthsAbbr[month]} ${year}`;
      };

      // Format date for display
      const formatDate = (date) => {
        if (!date) return '';
        return date.toLocaleDateString('id-ID', {
          year: 'numeric',
          month: 'long',
          day: 'numeric'
        });
      };

      // Generate periods
      const generatePeriods = async () => {
        if (!canGenerate.value) return;

        isGenerating.value = true;
        error.value = null;
        generationSuccess.value = false;
        generatedPeriods.value = [];

        try {
          // Prepare periods data for API
          const periodsToCreate = previewPeriods.value.map(period => ({
            period_name: period.name,
            start_date: formatApiDate(period.startDate),
            end_date: formatApiDate(period.endDate),
            status: form.autoOpenFirstPeriod && period === previewPeriods.value[0] ? 'Open' : 'Closed'
          }));

          // Call API to create periods
          const response = await axios.post('/api/accounting/accounting-periods/batch', {
            fiscal_year_name: form.fiscalYearName,
            periods: periodsToCreate
          });

          // Handle success
          generatedPeriods.value = response.data.data || [];
          generationSuccess.value = true;

          // Optionally redirect to periods list after a delay
          setTimeout(() => {
            router.push({
              path: '/accounting/periods',
              query: { success: 'fiscal-year-created' }
            });
          }, 3000);
        } catch (err) {
          console.error('Error generating periods:', err);
          error.value = 'Gagal membuat periode. ' + (err.response?.data?.message || 'Silakan coba lagi nanti.');
        } finally {
          isGenerating.value = false;
        }
      };

      // Format date for API (YYYY-MM-DD)
      const formatApiDate = (date) => {
        return date.toISOString().split('T')[0];
      };

      // Initialize period preview
      updatePeriodPreview();

      return {
        isLoading,
        isGenerating,
        error,
        dateError,
        form,
        previewPeriods,
        canGenerate,
        generationSuccess,
        generatedPeriods,
        formatDate,
        updatePeriodPreview,
        generatePeriods
      };
    }
  };
  </script>

  <style scoped>
  .fiscal-year-form {
    max-width: 100%;
  }

  .period-preview-list {
    list-style: none;
    padding: 0;
    margin: 0;
    max-height: 400px;
    overflow-y: auto;
  }

  .period-preview-item {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #e2e8f0;
  }

  .period-preview-item:last-child {
    border-bottom: none;
  }

  .period-name {
    font-weight: 600;
    color: #1e293b;
  }

  .period-dates {
    font-size: 0.875rem;
    color: #64748b;
    margin-top: 0.25rem;
  }
  </style>
