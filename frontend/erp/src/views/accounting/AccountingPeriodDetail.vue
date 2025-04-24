<!-- src/views/accounting/AccountingPeriodDetail.vue -->
<template>
    <div class="accounting-period-detail">
      <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
          <h2>Detail Periode Akuntansi</h2>
          <div class="action-buttons">
            <router-link to="/accounting/periods" class="btn btn-outline-secondary mr-2">
              <i class="fas fa-arrow-left mr-1"></i> Kembali
            </router-link>
            <router-link
              v-if="period && period.status === 'Open'"
              :to="`/accounting/periods/${periodId}/edit`"
              class="btn btn-primary mr-2"
            >
              <i class="fas fa-edit mr-1"></i> Edit
            </router-link>
            <router-link
              v-if="period && period.status === 'Open'"
              :to="`/accounting/periods/${periodId}/close`"
              class="btn btn-warning"
            >
              <i class="fas fa-lock mr-1"></i> Tutup Periode
            </router-link>
          </div>
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

      <div v-else class="row">
        <!-- Period Info Card -->
        <div class="col-md-6 mb-4">
          <div class="card h-100">
            <div class="card-header">
              <h3 class="card-title">Informasi Periode</h3>
            </div>
            <div class="card-body">
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
                  <tr v-if="period.created_at">
                    <th>Tanggal Dibuat</th>
                    <td>{{ formatDateTime(period.created_at) }}</td>
                  </tr>
                  <tr v-if="period.status !== 'Open' && period.closed_at">
                    <th>Tanggal Ditutup</th>
                    <td>{{ formatDateTime(period.closed_at) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Period Stats Card -->
        <div class="col-md-6 mb-4">
          <div class="card h-100">
            <div class="card-header">
              <h3 class="card-title">Statistik Periode</h3>
            </div>
            <div class="card-body">
              <div v-if="isLoadingStats" class="text-center py-4">
                <i class="fas fa-spinner fa-spin"></i>
                <p class="mt-2">Memuat statistik...</p>
              </div>
              <div v-else>
                <div class="stats-grid">
                  <div class="stat-item">
                    <div class="stat-label">Jurnal Dibuat</div>
                    <div class="stat-value">
                      <span class="stat-number">{{ stats.journalCount || 0 }}</span>
                      <span class="stat-icon"><i class="fas fa-book"></i></span>
                    </div>
                  </div>

                  <div class="stat-item">
                    <div class="stat-label">Total Transaksi</div>
                    <div class="stat-value">
                      <span class="stat-number">{{ stats.transactionCount || 0 }}</span>
                      <span class="stat-icon"><i class="fas fa-exchange-alt"></i></span>
                    </div>
                  </div>

                  <div class="stat-item">
                    <div class="stat-label">Total Debit</div>
                    <div class="stat-value">
                      <span class="stat-number">{{ formatCurrency(stats.totalDebit || 0) }}</span>
                      <span class="stat-icon"><i class="fas fa-arrow-up text-danger"></i></span>
                    </div>
                  </div>

                  <div class="stat-item">
                    <div class="stat-label">Total Kredit</div>
                    <div class="stat-value">
                      <span class="stat-number">{{ formatCurrency(stats.totalCredit || 0) }}</span>
                      <span class="stat-icon"><i class="fas fa-arrow-down text-success"></i></span>
                    </div>
                  </div>
                </div>

                <div class="status-info mt-4">
                  <div class="status-item" :class="{ 'text-success': isBalanced() }">
                    <i :class="isBalanced() ? 'fas fa-check-circle' : 'fas fa-exclamation-circle'"></i>
                    <span>Jurnal {{ isBalanced() ? 'Seimbang' : 'Tidak Seimbang' }}</span>
                  </div>

                  <div class="status-item" :class="{ 'text-success': isCompleted() }">
                    <i :class="isCompleted() ? 'fas fa-check-circle' : 'fas fa-hourglass-half'"></i>
                    <span>Proses {{ isCompleted() ? 'Selesai' : 'Belum Selesai' }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Journal Entries Card -->
        <div class="col-12 mb-4">
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h3 class="card-title">Jurnal Akuntansi</h3>
              <div v-if="period.status === 'Open'">
                <button class="btn btn-sm btn-primary" @click="navigateToCreateJournal">
                  <i class="fas fa-plus mr-1"></i> Tambah Jurnal
                </button>
              </div>
            </div>
            <div class="card-body">
              <div v-if="isLoadingJournals" class="text-center py-4">
                <i class="fas fa-spinner fa-spin"></i>
                <p class="mt-2">Memuat jurnal...</p>
              </div>
              <div v-else-if="journals.length === 0" class="text-center py-4">
                <i class="fas fa-book fa-2x text-muted mb-3"></i>
                <p>Tidak ada jurnal akuntansi untuk periode ini</p>
                <div v-if="period.status === 'Open'" class="mt-3">
                  <button class="btn btn-outline-primary" @click="navigateToCreateJournal">
                    <i class="fas fa-plus mr-1"></i> Buat Jurnal Baru
                  </button>
                </div>
              </div>
              <div v-else class="table-responsive">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>No. Jurnal</th>
                      <th>Tanggal</th>
                      <th>Deskripsi</th>
                      <th>Status</th>
                      <th>Debit</th>
                      <th>Kredit</th>
                      <th>Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="journal in journals" :key="journal.journal_id">
                      <td>{{ journal.journal_number }}</td>
                      <td>{{ formatDate(journal.entry_date) }}</td>
                      <td>{{ journal.description }}</td>
                      <td>
                        <span
                          :class="{
                            'badge': true,
                            'badge-success': journal.status === 'Posted',
                            'badge-warning': journal.status === 'Draft',
                            'badge-danger': journal.status === 'Rejected',
                            'badge-secondary': journal.status === 'Voided'
                          }"
                        >
                          {{ getJournalStatusLabel(journal.status) }}
                        </span>
                      </td>
                      <td class="text-right">{{ formatCurrency(journal.total_debit) }}</td>
                      <td class="text-right">{{ formatCurrency(journal.total_credit) }}</td>
                      <td>
                        <div class="btn-group">
                          <router-link
                            :to="`/accounting/journal-entries/${journal.journal_id}`"
                            class="btn btn-sm btn-info"
                            title="Detail"
                          >
                            <i class="fas fa-eye"></i>
                          </router-link>
                          <router-link
                            v-if="journal.status === 'Draft' && period.status === 'Open'"
                            :to="`/accounting/journal-entries/${journal.journal_id}/edit`"
                            class="btn btn-sm btn-primary"
                            title="Edit"
                          >
                            <i class="fas fa-edit"></i>
                          </router-link>
                          <button
                            v-if="journal.status === 'Draft' && period.status === 'Open'"
                            @click="confirmPostJournal(journal)"
                            class="btn btn-sm btn-success"
                            title="Posting"
                          >
                            <i class="fas fa-check"></i>
                          </button>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                  <tfoot v-if="journals.length > 0">
                    <tr class="font-weight-bold">
                      <td colspan="4" class="text-right">Total:</td>
                      <td class="text-right">{{ formatCurrency(calculateTotalDebit()) }}</td>
                      <td class="text-right">{{ formatCurrency(calculateTotalCredit()) }}</td>
                      <td></td>
                    </tr>
                  </tfoot>
                </table>
              </div>

              <div v-if="journals.length > 0 && totalJournals > journals.length" class="text-center mt-3">
                <router-link :to="'/accounting/journal-entries?period_id=' + periodId" class="btn btn-outline-primary">
                  Lihat Semua Jurnal
                </router-link>
              </div>
            </div>
          </div>
        </div>

        <!-- Financial Reports Card -->
        <div class="col-12 mb-4">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">Laporan Keuangan</h3>
            </div>
            <div class="card-body">
              <div class="row">
                <div class="col-md-4 mb-3">
                  <div class="report-card">
                    <div class="report-card-body">
                      <div class="report-icon">
                        <i class="fas fa-balance-scale"></i>
                      </div>
                      <div class="report-content">
                        <h4>Neraca Saldo</h4>
                        <p>Laporan saldo akun pada akhir periode</p>
                      </div>
                    </div>
                    <div class="report-card-footer">
                      <router-link :to="`/accounting/reports/trial-balance?period_id=${periodId}`" class="btn btn-sm btn-outline-primary">
                        Lihat Laporan
                      </router-link>
                    </div>
                  </div>
                </div>

                <div class="col-md-4 mb-3">
                  <div class="report-card">
                    <div class="report-card-body">
                      <div class="report-icon">
                        <i class="fas fa-chart-line"></i>
                      </div>
                      <div class="report-content">
                        <h4>Laba Rugi</h4>
                        <p>Laporan pendapatan dan biaya periode ini</p>
                      </div>
                    </div>
                    <div class="report-card-footer">
                      <router-link :to="`/accounting/reports/income-statement?period_id=${periodId}`" class="btn btn-sm btn-outline-primary">
                        Lihat Laporan
                      </router-link>
                    </div>
                  </div>
                </div>

                <div class="col-md-4 mb-3">
                  <div class="report-card">
                    <div class="report-card-body">
                      <div class="report-icon">
                        <i class="fas fa-landmark"></i>
                      </div>
                      <div class="report-content">
                        <h4>Neraca</h4>
                        <p>Laporan posisi keuangan pada akhir periode</p>
                      </div>
                    </div>
                    <div class="report-card-footer">
                      <router-link :to="`/accounting/reports/balance-sheet?period_id=${periodId}`" class="btn btn-sm btn-outline-primary">
                        Lihat Laporan
                      </router-link>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Post Journal Confirmation Modal -->
      <ConfirmationModal
        v-if="showPostJournalModal"
        title="Posting Jurnal"
        :message="`Apakah Anda yakin ingin posting jurnal ${selectedJournal?.journal_number}? Jurnal yang sudah di-posting tidak dapat diubah lagi.`"
        confirm-button-text="Posting"
        confirm-button-class="btn btn-success"
        @confirm="postJournal"
        @close="showPostJournalModal = false"
      />
    </div>
  </template>

  <script>
  import axios from 'axios';
  import { ref, computed, onMounted } from 'vue';
  import { useRoute, useRouter } from 'vue-router';

  export default {
    name: 'AccountingPeriodDetail',
    setup() {
      const route = useRoute();
      const router = useRouter();
      const periodId = computed(() => route.params.id);

      const isLoading = ref(true);
      const isLoadingStats = ref(true);
      const isLoadingJournals = ref(true);
      const error = ref(null);
      const period = ref(null);
      const stats = ref({});
      const journals = ref([]);
      const totalJournals = ref(0);
      const showPostJournalModal = ref(false);
      const selectedJournal = ref(null);
      const isPostingJournal = ref(false);

      // Load period data
      const loadPeriod = async () => {
        isLoading.value = true;
        error.value = null;

        try {
          const response = await axios.get(`/api/accounting/accounting-periods/${periodId.value}`);
          period.value = response.data.data;

          // Load additional data in parallel
          await Promise.all([
            loadPeriodStats(),
            loadJournalEntries()
          ]);
        } catch (err) {
          console.error('Error loading period:', err);
          error.value = 'Gagal memuat data periode. Silakan coba lagi nanti.';
        } finally {
          isLoading.value = false;
        }
      };

      // Load period statistics
      const loadPeriodStats = async () => {
        isLoadingStats.value = true;

        try {
          const response = await axios.get(`/api/accounting/accounting-periods/${periodId.value}/stats`);
          stats.value = response.data.data || {};
        } catch (err) {
          console.error('Error loading period stats:', err);
          // Don't set the main error message, just log it
        } finally {
          isLoadingStats.value = false;
        }
      };

      // Load journal entries for the period
      const loadJournalEntries = async () => {
        isLoadingJournals.value = true;

        try {
          const response = await axios.get('/api/accounting/journal-entries', {
            params: {
              period_id: periodId.value,
              limit: 10 // Show the latest few entries
            }
          });

          journals.value = response.data.data || [];

          // Get total count for the "view all" link
          if (response.data.meta) {
            totalJournals.value = response.data.meta.total;
          } else {
            totalJournals.value = journals.value.length;
          }
        } catch (err) {
          console.error('Error loading journal entries:', err);
          // Don't set the main error message, just log it
        } finally {
          isLoadingJournals.value = false;
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

      // Format date time for display
      const formatDateTime = (dateTimeString) => {
        if (!dateTimeString) return '';
        const date = new Date(dateTimeString);
        return date.toLocaleDateString('id-ID', {
          year: 'numeric',
          month: 'long',
          day: 'numeric',
          hour: '2-digit',
          minute: '2-digit'
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

      // Get journal status label
      const getJournalStatusLabel = (status) => {
        switch (status) {
          case 'Draft':
            return 'Draft';
          case 'Posted':
            return 'Posted';
          case 'Voided':
            return 'Dibatalkan';
          case 'Rejected':
            return 'Ditolak';
          default:
            return status;
        }
      };

      // Check if journal entries are balanced
      const isBalanced = () => {
        return stats.value &&
               stats.value.totalDebit &&
               stats.value.totalCredit &&
               Math.abs(stats.value.totalDebit - stats.value.totalCredit) < 0.01;
      };

      // Check if period processing is completed
      const isCompleted = () => {
        return period.value && (period.value.status === 'Closed' || period.value.status === 'Locked');
      };

      // Calculate total debit from journal entries
      const calculateTotalDebit = () => {
        return journals.value.reduce((sum, journal) => sum + (journal.total_debit || 0), 0);
      };

      // Calculate total credit from journal entries
      const calculateTotalCredit = () => {
        return journals.value.reduce((sum, journal) => sum + (journal.total_credit || 0), 0);
      };

      // Navigate to create journal page
      const navigateToCreateJournal = () => {
        router.push({
          path: '/accounting/journal-entries/create',
          query: { period_id: periodId.value }
        });
      };

      // Confirm posting a journal
      const confirmPostJournal = (journal) => {
        selectedJournal.value = journal;
        showPostJournalModal.value = true;
      };

      // Post a journal
      const postJournal = async () => {
        if (!selectedJournal.value) return;

        isPostingJournal.value = true;

        try {
          await axios.post(`/api/accounting/journal-entries/${selectedJournal.value.journal_id}/post`);

          // Update journal status in the list
          const index = journals.value.findIndex(j => j.journal_id === selectedJournal.value.journal_id);
          if (index !== -1) {
            journals.value[index].status = 'Posted';
          }

          // Reload stats to reflect changes
          await loadPeriodStats();

          showPostJournalModal.value = false;
          selectedJournal.value = null;
        } catch (err) {
          console.error('Error posting journal:', err);
          error.value = 'Gagal posting jurnal. ' + (err.response?.data?.message || 'Silakan coba lagi nanti.');
        } finally {
          isPostingJournal.value = false;
        }
      };

      // Lifecycle hooks
      onMounted(() => {
        loadPeriod();
      });

      return {
        periodId,
        isLoading,
        isLoadingStats,
        isLoadingJournals,
        error,
        period,
        stats,
        journals,
        totalJournals,
        showPostJournalModal,
        selectedJournal,
        isPostingJournal,
        formatDate,
        formatDateTime,
        formatCurrency,
        calculateDuration,
        getStatusLabel,
        getJournalStatusLabel,
        isBalanced,
        isCompleted,
        calculateTotalDebit,
        calculateTotalCredit,
        navigateToCreateJournal,
        confirmPostJournal,
        postJournal
      };
    }
  };
  </script>

  <style scoped>
  .badge {
    padding: 0.5em 0.75em;
    font-size: 0.75em;
  }

  .table-details {
    margin-bottom: 0;
  }

  .table-details th {
    width: 40%;
    font-weight: 600;
  }

  .stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
  }

  .stat-item {
    background-color: #f8fafc;
    border-radius: 0.5rem;
    padding: 1rem;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
  }

  .stat-label {
    font-size: 0.875rem;
    color: #64748b;
    margin-bottom: 0.5rem;
  }

  .stat-value {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .stat-number {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1e293b;
  }

  .stat-icon {
    background-color: #e2e8f0;
    color: #475569;
    border-radius: 9999px;
    width: 2.5rem;
    height: 2.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .status-info {
    margin-top: 1.5rem;
  }

  .status-item {
    display: flex;
    align-items: center;
    margin-bottom: 0.5rem;
    color: #64748b;
  }

  .status-item i {
    margin-right: 0.5rem;
  }

  .status-item.text-success {
    color: #059669;
  }

  .status-item.text-warning {
    color: #d97706;
  }

  /* Financial Report Cards */
  .report-card {
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    overflow: hidden;
    height: 100%;
    display: flex;
    flex-direction: column;
    transition: transform 0.2s, box-shadow 0.2s;
  }

  .report-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  }

  .report-card-body {
    padding: 1.5rem;
    display: flex;
    align-items: center;
    flex-grow: 1;
  }

  .report-icon {
    font-size: 2rem;
    color: #2563eb;
    margin-right: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 4rem;
    height: 4rem;
    background-color: #eff6ff;
    border-radius: 0.5rem;
  }

  .report-content {
    flex-grow: 1;
  }

  .report-content h4 {
    font-size: 1.125rem;
    margin-bottom: 0.5rem;
    color: #334155;
  }

  .report-content p {
    font-size: 0.875rem;
    color: #64748b;
    margin-bottom: 0;
  }

  .report-card-footer {
    padding: 1rem;
    background-color: #f8fafc;
    border-top: 1px solid #e2e8f0;
    text-align: center;
  }

  /* Responsive adjustments */
  @media (max-width: 768px) {
    .stats-grid {
      grid-template-columns: 1fr;
      gap: 1rem;
    }

    .report-card-body {
      flex-direction: column;
      text-align: center;
    }

    .report-icon {
      margin-right: 0;
      margin-bottom: 1rem;
    }
  }
  </style>
