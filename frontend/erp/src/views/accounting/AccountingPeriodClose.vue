<!-- src/views/accounting/AccountingPeriodClose.vue -->
<template>
    <div class="accounting-period-close">
      <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
          <h2>Tutup Periode Akuntansi</h2>
          <router-link :to="`/accounting/periods/${periodId}`" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Kembali
          </router-link>
        </div>
      </div>

      <div v-if="isLoading" class="text-center py-5">
        <i class="fas fa-spinner fa-spin fa-2x"></i>
        <p class="mt-2">Memuat data periode...</p>
      </div>

      <div v-else-if="error" class="alert alert-danger" role="alert">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        {{ error }}
      </div>

      <div v-else-if="!period" class="alert alert-warning" role="alert">
        <i class="fas fa-exclamation-circle mr-2"></i>
        Periode akuntansi tidak ditemukan
      </div>

      <div v-else>
        <!-- Period Info Card -->
        <div class="card mb-4">
          <div class="card-header">
            <h3 class="card-title">Informasi Periode</h3>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-6">
                <table class="table table-details">
                  <tbody>
                    <tr>
                      <th>Nama Periode</th>
                      <td>{{ period.period_name }}</td>
                    </tr>
                    <tr>
                      <th>Tanggal Mulai</th>
                      <td>{{ formatDate(period.start_date) }}</td>
                    </tr>
                    <tr>
                      <th>Tanggal Akhir</th>
                      <td>{{ formatDate(period.end_date) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="col-md-6">
                <table class="table table-details">
                  <tbody>
                    <tr>
                      <th>Status</th>
                      <td>
                        <span
                          :class="{
                            'badge': true,
                            'badge-success': period.status === 'Open',
                            'badge-warning': period.status === 'Closing',
                            'badge-danger': period.status === 'Closed',
                            'badge-secondary': period.status === 'Locked'
                          }"
                        >
                          {{ getStatusLabel(period.status) }}
                        </span>
                      </td>
                    </tr>
                    <tr>
                      <th>Durasi</th>
                      <td>{{ calculateDuration() }} hari</td>
                    </tr>
                    <tr>
                      <th>Status Tutup Buku</th>
                      <td>
                        <span
                          :class="{
                            'badge': true,
                            'badge-success': isCompleted,
                            'badge-warning': !isCompleted
                          }"
                        >
                          {{ isCompleted ? 'Selesai' : 'Belum Selesai' }}
                        </span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Closing Process Card -->
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Proses Tutup Buku</h3>
          </div>
          <div class="card-body">
            <div v-if="period.status !== 'Open'" class="alert alert-warning">
              <i class="fas fa-exclamation-triangle mr-2"></i>
              Periode ini tidak dapat ditutup karena status tidak "Terbuka"
            </div>

            <div v-else>
              <div class="alert alert-info mb-4">
                <i class="fas fa-info-circle mr-2"></i>
                <strong>Proses tutup buku akan melakukan tindakan berikut:</strong>
                <ul class="mb-0 mt-2">
                  <li>Memverifikasi bahwa seluruh jurnal sudah seimbang (debit = kredit)</li>
                  <li>Memindahkan saldo akun pendapatan dan biaya ke akun laba ditahan</li>
                  <li>Mempersiapkan akun-akun neraca untuk periode berikutnya</li>
                  <li>Mengubah status periode menjadi "Tertutup"</li>
                </ul>
              </div>

              <!-- Closing Checklist -->
              <div class="closing-checklist mb-4">
                <h4 class="mb-3">Daftar Pemeriksaan</h4>

                <div class="checklist-item" :class="{ 'checked': checksCompleted.journalBalance }">
                  <div class="check-status">
                    <i :class="checksCompleted.journalBalance ? 'fas fa-check-circle text-success' : 'fas fa-circle'"></i>
                  </div>
                  <div class="check-content">
                    <h5>Keseimbangan Jurnal</h5>
                    <p>Memeriksa bahwa semua jurnal dalam periode ini memiliki total debit sama dengan total kredit</p>
                    <div v-if="checks.journalBalance.status === 'checking'" class="check-action">
                      <i class="fas fa-spinner fa-spin mr-2"></i> Memeriksa...
                    </div>
                    <div v-else-if="checks.journalBalance.status === 'completed'" class="check-result success">
                      <i class="fas fa-check-circle mr-2"></i> {{ checks.journalBalance.message }}
                    </div>
                    <div v-else-if="checks.journalBalance.status === 'failed'" class="check-result failure">
                      <i class="fas fa-times-circle mr-2"></i> {{ checks.journalBalance.message }}
                    </div>
                    <button
                      v-if="!checks.journalBalance.status || checks.journalBalance.status === 'failed'"
                      class="btn btn-sm btn-outline-primary mt-2"
                      @click="runCheck('journalBalance')"
                      :disabled="isRunningChecks"
                    >
                      <i class="fas fa-sync-alt mr-1"></i> Periksa
                    </button>
                  </div>
                </div>

                <div class="checklist-item" :class="{ 'checked': checksCompleted.openEntries }">
                  <div class="check-status">
                    <i :class="checksCompleted.openEntries ? 'fas fa-check-circle text-success' : 'fas fa-circle'"></i>
                  </div>
                  <div class="check-content">
                    <h5>Jurnal Draft</h5>
                    <p>Memeriksa apakah ada jurnal yang masih dalam status draft dan belum di-posting</p>
                    <div v-if="checks.openEntries.status === 'checking'" class="check-action">
                      <i class="fas fa-spinner fa-spin mr-2"></i> Memeriksa...
                    </div>
                    <div v-else-if="checks.openEntries.status === 'completed'" class="check-result success">
                      <i class="fas fa-check-circle mr-2"></i> {{ checks.openEntries.message }}
                    </div>
                    <div v-else-if="checks.openEntries.status === 'failed'" class="check-result failure">
                      <i class="fas fa-times-circle mr-2"></i> {{ checks.openEntries.message }}
                      <div v-if="checks.openEntries.data && checks.openEntries.data.length > 0" class="mt-2">
                        <button class="btn btn-sm btn-outline-secondary" @click="showOpenEntries = true">
                          Lihat Jurnal Draft
                        </button>
                      </div>
                    </div>
                    <button
                      v-if="!checks.openEntries.status || checks.openEntries.status === 'failed'"
                      class="btn btn-sm btn-outline-primary mt-2"
                      @click="runCheck('openEntries')"
                      :disabled="isRunningChecks"
                    >
                      <i class="fas fa-sync-alt mr-1"></i> Periksa
                    </button>
                  </div>
                </div>

                <div class="checklist-item" :class="{ 'checked': checksCompleted.trialBalance }">
                  <div class="check-status">
                    <i :class="checksCompleted.trialBalance ? 'fas fa-check-circle text-success' : 'fas fa-circle'"></i>
                  </div>
                  <div class="check-content">
                    <h5>Neraca Saldo</h5>
                    <p>Memeriksa neraca saldo untuk memastikan total debit sama dengan total kredit</p>
                    <div v-if="checks.trialBalance.status === 'checking'" class="check-action">
                      <i class="fas fa-spinner fa-spin mr-2"></i> Memeriksa...
                    </div>
                    <div v-else-if="checks.trialBalance.status === 'completed'" class="check-result success">
                      <i class="fas fa-check-circle mr-2"></i> {{ checks.trialBalance.message }}
                    </div>
                    <div v-else-if="checks.trialBalance.status === 'failed'" class="check-result failure">
                      <i class="fas fa-times-circle mr-2"></i> {{ checks.trialBalance.message }}
                    </div>
                    <button
                      v-if="!checks.trialBalance.status || checks.trialBalance.status === 'failed'"
                      class="btn btn-sm btn-outline-primary mt-2"
                      @click="runCheck('trialBalance')"
                      :disabled="isRunningChecks"
                    >
                      <i class="fas fa-sync-alt mr-1"></i> Periksa
                    </button>
                  </div>
                </div>
              </div>

              <!-- Income Statement Transfer Setup -->
              <div class="closing-config mb-4">
                <h4 class="mb-3">Konfigurasi Penutupan</h4>

                <div class="form-group">
                  <label for="retainedEarningsAccount">Akun Laba Ditahan <span class="text-danger">*</span></label>
                  <select
                    id="retainedEarningsAccount"
                    v-model="closingConfig.retainedEarningsAccountId"
                    class="form-control"
                    :disabled="isRunningProcess"
                    required
                  >
                    <option value="" disabled>Pilih akun laba ditahan</option>
                    <option
                      v-for="account in equityAccounts"
                      :key="account.account_id"
                      :value="account.account_id"
                    >
                      {{ account.account_code }} - {{ account.name }}
                    </option>
                  </select>
                  <small class="form-text text-muted">
                    Akun laba ditahan untuk mentransfer saldo dari akun pendapatan dan biaya
                  </small>
                </div>

                <div class="form-group">
                  <label for="closingReference">Referensi Penutupan</label>
                  <input
                    id="closingReference"
                    v-model="closingConfig.closingReference"
                    type="text"
                    class="form-control"
                    placeholder="Contoh: Penutupan Periode Q1 2024"
                    :disabled="isRunningProcess"
                  />
                  <small class="form-text text-muted">
                    Referensi atau deskripsi untuk jurnal penutupan yang akan dibuat
                  </small>
                </div>

                <div class="form-check mb-3">
                  <input
                    id="automaticNextPeriod"
                    v-model="closingConfig.createNextPeriod"
                    type="checkbox"
                    class="form-check-input"
                    :disabled="isRunningProcess"
                  />
                  <label class="form-check-label" for="automaticNextPeriod">
                    Buat periode berikutnya secara otomatis
                  </label>
                </div>

                <div v-if="closingConfig.createNextPeriod" class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="nextPeriodName">Nama Periode Berikutnya</label>
                      <input
                        id="nextPeriodName"
                        v-model="closingConfig.nextPeriodName"
                        type="text"
                        class="form-control"
                        placeholder="Contoh: Q2 2024"
                        :disabled="isRunningProcess"
                      />
                    </div>
                  </div>

                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="nextPeriodDuration">Durasi (hari)</label>
                      <input
                        id="nextPeriodDuration"
                        v-model="closingConfig.nextPeriodDuration"
                        type="number"
                        class="form-control"
                        min="1"
                        :disabled="isRunningProcess"
                      />
                    </div>
                  </div>
                </div>
              </div>

              <!-- Close Period Button -->
              <div class="closing-actions">
                <button
                  class="btn btn-lg btn-warning"
                  @click="confirmClosePeriod"
                  :disabled="!canClosePeriod || isRunningProcess"
                >
                  <i class="fas fa-lock mr-2"></i>
                  <span v-if="isRunningProcess">Memproses...</span>
                  <span v-else>Tutup Periode</span>
                </button>
                <div v-if="!canClosePeriod && !isRunningProcess" class="text-danger mt-2">
                  <i class="fas fa-exclamation-circle mr-1"></i>
                  Selesaikan semua pemeriksaan dan isi konfigurasi yang diperlukan untuk menutup periode
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Open Entries Modal -->
        <ConfirmationModal
          v-if="showOpenEntries"
          title="Jurnal Draft"
          :message="'Terdapat ' + (checks.openEntries.data?.length || 0) + ' jurnal yang masih dalam status draft:'"
          confirm-button-text="Tutup"
          confirm-button-class="btn btn-primary"
          @confirm="showOpenEntries = false"
          @close="showOpenEntries = false"
        >
          <template #default>
            <div class="open-entries-list mt-3">
              <table class="table table-sm">
                <thead>
                  <tr>
                    <th>No. Jurnal</th>
                    <th>Tanggal</th>
                    <th>Deskripsi</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="entry in checks.openEntries.data" :key="entry.journal_id">
                    <td>{{ entry.journal_number }}</td>
                    <td>{{ formatDate(entry.entry_date) }}</td>
                    <td>{{ entry.description }}</td>
                    <td>
                      <router-link
                        :to="`/accounting/journal-entries/${entry.journal_id}`"
                        class="btn btn-sm btn-primary"
                      >
                        <i class="fas fa-eye"></i>
                      </router-link>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </template>
        </ConfirmationModal>

        <!-- Close Period Confirmation Modal -->
        <ConfirmationModal
          v-if="showCloseConfirmation"
          title="Konfirmasi Tutup Periode"
          :message="'Apakah Anda yakin ingin menutup periode <strong>' + period.period_name + '</strong>? Tindakan ini tidak dapat dibatalkan.'"
          confirm-button-text="Tutup Periode"
          confirm-button-class="btn btn-warning"
          @confirm="closePeriod"
          @close="showCloseConfirmation = false"
        />
      </div>
    </div>
  </template>

  <script>
  import axios from 'axios';
  import { ref, reactive, computed, onMounted } from 'vue';
  import { useRoute, useRouter } from 'vue-router';

  export default {
    name: 'AccountingPeriodClose',
    setup() {
      const route = useRoute();
      const router = useRouter();
      const periodId = computed(() => route.params.id);

      const isLoading = ref(true);
      const error = ref(null);
      const period = ref(null);
      const equityAccounts = ref([]);
      const isRunningChecks = ref(false);
      const isRunningProcess = ref(false);
      const showOpenEntries = ref(false);
      const showCloseConfirmation = ref(false);
      const isCompleted = ref(false);

      // Closing checks
      const checks = reactive({
        journalBalance: {
          status: null,
          message: '',
          data: null
        },
        openEntries: {
          status: null,
          message: '',
          data: null
        },
        trialBalance: {
          status: null,
          message: '',
          data: null
        }
      });

      // Check completion status
      const checksCompleted = computed(() => {
        return {
          journalBalance: checks.journalBalance.status === 'completed',
          openEntries: checks.openEntries.status === 'completed',
          trialBalance: checks.trialBalance.status === 'completed'
        };
      });

      // Closing configuration
      const closingConfig = reactive({
        retainedEarningsAccountId: '',
        closingReference: '',
        createNextPeriod: true,
        nextPeriodName: '',
        nextPeriodDuration: 30
      });

      // Determine if period can be closed
      const canClosePeriod = computed(() => {
        return checksCompleted.value.journalBalance &&
               checksCompleted.value.openEntries &&
               checksCompleted.value.trialBalance &&
               closingConfig.value.retainedEarningsAccountId;
      });

      // Load period data
      const loadPeriod = async () => {
        isLoading.value = true;
        error.value = null;

        try {
          const response = await axios.get(`/api/accounting/accounting-periods/${periodId.value}`);
          period.value = response.data.data;

          // Load equity accounts for retained earnings selection
          await loadEquityAccounts();

          // Auto-suggest next period name if current period name has a pattern
          suggestNextPeriodName();
        } catch (err) {
          console.error('Error loading period:', err);
          error.value = 'Gagal memuat data periode. Silakan coba lagi nanti.';
        } finally {
          isLoading.value = false;
        }
      };

      // Load equity accounts for retained earnings selection
      const loadEquityAccounts = async () => {
        try {
          const response = await axios.get('/api/accounting/chart-of-accounts', {
            params: { account_type: 'Equity' }
          });
          equityAccounts.value = response.data.data || [];

          // Try to select a retained earnings account automatically
          const retainedEarningsAccount = equityAccounts.value.find(account =>
            account.name.toLowerCase().includes('retained') ||
            account.name.toLowerCase().includes('earnings') ||
            account.name.toLowerCase().includes('laba ditahan')
          );

          if (retainedEarningsAccount) {
            closingConfig.retainedEarningsAccountId = retainedEarningsAccount.account_id;
          }
        } catch (err) {
          console.error('Error loading equity accounts:', err);
        }
      };

      // Suggest next period name based on current period
      const suggestNextPeriodName = () => {
        if (!period.value) return;

        const currentName = period.value.period_name;

        // Try to detect quarter pattern (Q1, Q2, etc.)
        const quarterMatch = currentName.match(/Q(\d+)\s+(\d{4})/i);
        if (quarterMatch) {
          const quarter = parseInt(quarterMatch[1]);
          const year = parseInt(quarterMatch[2]);

          if (quarter < 4) {
            closingConfig.nextPeriodName = `Q${quarter + 1} ${year}`;
            closingConfig.nextPeriodDuration = 90; // Approximately 3 months
          } else {
            closingConfig.nextPeriodName = `Q1 ${year + 1}`;
            closingConfig.nextPeriodDuration = 90;
          }
          return;
        }

        // Try to detect month pattern
        const months = [
          'januari', 'februari', 'maret', 'april', 'mei', 'juni',
          'juli', 'agustus', 'september', 'oktober', 'november', 'desember'
        ];

        for (let i = 0; i < months.length; i++) {
          if (currentName.toLowerCase().includes(months[i])) {
            const nextMonthIndex = (i + 1) % 12;
            const isDecember = i === 11;

            // Extract year from current name
            const yearMatch = currentName.match(/\d{4}/);
            let year = new Date().getFullYear();

            if (yearMatch) {
              year = parseInt(yearMatch[0]);
              if (isDecember) year++;
            }

            closingConfig.nextPeriodName = `${months[nextMonthIndex].charAt(0).toUpperCase() + months[nextMonthIndex].slice(1)} ${year}`;

            // Set approx days in month
            const daysInMonth = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
            closingConfig.nextPeriodDuration = daysInMonth[nextMonthIndex];

            // Adjust for leap year
            if (nextMonthIndex === 1 && ((year % 4 === 0 && year % 100 !== 0) || year % 400 === 0)) {
              closingConfig.nextPeriodDuration = 29;
            }

            return;
          }
        }

        // Try to detect year pattern
        const yearMatch = currentName.match(/\d{4}/);
        if (yearMatch) {
          const year = parseInt(yearMatch[0]);
          closingConfig.nextPeriodName = `${year + 1}`;
          closingConfig.nextPeriodDuration = 365;
          return;
        }

        // Default suggestion
        const currentDate = new Date();
        closingConfig.nextPeriodName = `Period ${currentDate.getFullYear()}-${(currentDate.getMonth() + 2).toString().padStart(2, '0')}`;
        closingConfig.nextPeriodDuration = 30;
      };

      // Run a specific check
      const runCheck = async (checkType) => {
        isRunningChecks.value = true;
        checks[checkType].status = 'checking';

        try {
          let response;

          switch (checkType) {
            case 'journalBalance':
              response = await axios.get(`/api/accounting/accounting-periods/${periodId.value}/check-journal-balance`);
              if (response.data.balanced) {
                checks.journalBalance.status = 'completed';
                checks.journalBalance.message = 'Semua jurnal seimbang';
              } else {
                checks.journalBalance.status = 'failed';
                checks.journalBalance.message = `Ditemukan ${response.data.unbalanced_count} jurnal tidak seimbang`;
                checks.journalBalance.data = response.data.unbalanced_journals;
              }
              break;

            case 'openEntries':
              response = await axios.get(`/api/accounting/accounting-periods/${periodId.value}/check-open-entries`);
              if (response.data.all_posted) {
                checks.openEntries.status = 'completed';
                checks.openEntries.message = 'Semua jurnal sudah di-posting';
              } else {
                checks.openEntries.status = 'failed';
                checks.openEntries.message = `Ditemukan ${response.data.draft_count} jurnal draft yang belum di-posting`;
                checks.openEntries.data = response.data.draft_journals;
              }
              break;

            case 'trialBalance':
              response = await axios.get(`/api/accounting/accounting-periods/${periodId.value}/check-trial-balance`);
              if (response.data.balanced) {
                checks.trialBalance.status = 'completed';
                checks.trialBalance.message = 'Neraca saldo seimbang';
              } else {
                checks.trialBalance.status = 'failed';
                checks.trialBalance.message = `Neraca saldo tidak seimbang. Selisih: ${formatCurrency(response.data.difference)}`;
                checks.trialBalance.data = response.data;
              }
              break;
          }
        } catch (err) {
          console.error(`Error running ${checkType} check:`, err);
          checks[checkType].status = 'failed';
          checks[checkType].message = `Gagal menjalankan pemeriksaan: ${err.response?.data?.message || 'Terjadi kesalahan'}`;
        } finally {
          isRunningChecks.value = false;
        }
      };

      // Confirm close period
      const confirmClosePeriod = () => {
        if (!canClosePeriod.value) return;
        showCloseConfirmation.value = true;
      };

      // Close period
      const closePeriod = async () => {
        isRunningProcess.value = true;

        try {
          const payload = {
            retained_earnings_account_id: closingConfig.retainedEarningsAccountId,
            closing_reference: closingConfig.closingReference || `Penutupan ${period.value.period_name}`,
            create_next_period: closingConfig.createNextPeriod
          };

          if (closingConfig.createNextPeriod) {
            payload.next_period_name = closingConfig.nextPeriodName;

            // Calculate next period start and end dates
            const currentEndDate = new Date(period.value.end_date);
            const nextStartDate = new Date(currentEndDate);
            nextStartDate.setDate(nextStartDate.getDate() + 1);

            const nextEndDate = new Date(nextStartDate);
            nextEndDate.setDate(nextStartDate.getDate() + parseInt(closingConfig.nextPeriodDuration) - 1);

            payload.next_period_start_date = nextStartDate.toISOString().split('T')[0];
            payload.next_period_end_date = nextEndDate.toISOString().split('T')[0];
          }

           await axios.post(`/api/accounting/accounting-periods/${periodId.value}/close`, payload);

          // Show success and redirect to period detail
          isCompleted.value = true;
          showCloseConfirmation.value = false;

          // Update local period data with the closed status
          period.value = {
            ...period.value,
            status: 'Closed'
          };

          // Redirect after a delay
          setTimeout(() => {
            router.push({
              path: `/accounting/periods/${periodId.value}`,
              query: { success: 'closed' }
            });
          }, 2000);
        } catch (err) {
          console.error('Error closing period:', err);
          error.value = 'Gagal menutup periode. ' + (err.response?.data?.message || 'Silakan coba lagi nanti.');
          showCloseConfirmation.value = false;
        } finally {
          isRunningProcess.value = false;
        }
      };

      // Format date for display
      const formatDate = (dateString) => {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', {
          year: 'numeric',
          month: 'long',
          day: 'numeric'
        });
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

      // Calculate duration between start and end date
      const calculateDuration = () => {
        if (!period.value || !period.value.start_date || !period.value.end_date) {
          return 0;
        }

        const startDate = new Date(period.value.start_date);
        const endDate = new Date(period.value.end_date);
        const diffTime = Math.abs(endDate - startDate);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1; // +1 to include both start and end days

        return diffDays;
      };

      // Get status label
      const getStatusLabel = (status) => {
        switch (status) {
          case 'Open':
            return 'Terbuka';
          case 'Closing':
            return 'Proses Penutupan';
          case 'Closed':
            return 'Tertutup';
          case 'Locked':
            return 'Terkunci';
          default:
            return status;
        }
      };

      // Lifecycle hooks
      onMounted(() => {
        loadPeriod();
      });

      return {
        periodId,
        isLoading,
        error,
        period,
        equityAccounts,
        isRunningChecks,
        isRunningProcess,
        showOpenEntries,
        showCloseConfirmation,
        isCompleted,
        checks,
        checksCompleted,
        closingConfig,
        canClosePeriod,
        formatDate,
        formatCurrency,
        calculateDuration,
        getStatusLabel,
        runCheck,
        confirmClosePeriod,
        closePeriod
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

  .badge {
    padding: 0.5em 0.75em;
    font-size: 0.75em;
  }

  /* Closing checklist styles */
  .closing-checklist {
    padding: 1.5rem;
    background-color: #f8fafc;
    border-radius: 0.5rem;
    border: 1px solid #e2e8f0;
  }

  .checklist-item {
    display: flex;
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
  }

  .checklist-item:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
  }

  .checklist-item.checked {
    opacity: 0.8;
  }

  .check-status {
    flex-shrink: 0;
    width: 2rem;
    margin-right: 1rem;
    font-size: 1.5rem;
    color: #cbd5e1;
  }

  .check-content {
    flex-grow: 1;
  }

  .check-content h5 {
    margin-bottom: 0.5rem;
    font-size: 1rem;
    font-weight: 600;
  }

  .check-content p {
    margin-bottom: 0.75rem;
    color: #64748b;
  }

  .check-result {
    padding: 0.5rem 0.75rem;
    border-radius: 0.375rem;
    font-size: 0.875rem;
  }

  .check-result.success {
    background-color: #d1fae5;
    color: #059669;
  }

  .check-result.failure {
    background-color: #fee2e2;
    color: #dc2626;
  }

  /* Closing config styles */
  .closing-config {
    padding: 1.5rem;
    background-color: #f8fafc;
    border-radius: 0.5rem;
    border: 1px solid #e2e8f0;
  }

  /* Closing actions styles */
  .closing-actions {
    text-align: center;
    margin-top: 2rem;
  }
  </style>
