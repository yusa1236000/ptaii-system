<!-- src/views/accounting/JournalEntryDetail.vue -->
<template>
    <div class="journal-entry-detail-container">
      <div class="page-header">
        <h1>Detail Jurnal</h1>
        <div class="action-buttons">
          <button @click="$router.go(-1)" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
          </button>
          <button v-if="journalEntry && journalEntry.status === 'Draft'" @click="showPostConfirmation" class="btn btn-success ml-2">
            <i class="fas fa-check-circle"></i> Posting Jurnal
          </button>
          <router-link
            v-if="journalEntry && journalEntry.status === 'Draft'"
            :to="`/accounting/journal-entries/${journalEntry.journal_id}/edit`"
            class="btn btn-primary ml-2"
          >
            <i class="fas fa-edit"></i> Edit
          </router-link>
          <button @click="printJournal" class="btn btn-secondary ml-2">
            <i class="fas fa-print"></i> Cetak
          </button>
        </div>
      </div>

      <div class="card" v-if="isLoading">
        <div class="card-body">
          <div class="loading-indicator">
            <i class="fas fa-spinner fa-spin"></i> Memuat data...
          </div>
        </div>
      </div>

      <div v-else-if="!journalEntry" class="card">
        <div class="card-body">
          <div class="empty-state">
            <div class="empty-icon">
              <i class="fas fa-file-invoice"></i>
            </div>
            <h3>Data Tidak Ditemukan</h3>
            <p>Jurnal yang Anda cari tidak ditemukan atau telah dihapus.</p>
          </div>
        </div>
      </div>

      <div v-else>
        <!-- Journal Header Information -->
        <div class="card mb-4">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h2 class="card-title">Informasi Jurnal</h2>
            <span :class="getStatusBadgeClass(journalEntry.status)" class="badge">
              {{ journalEntry.status }}
            </span>
          </div>
          <div class="card-body">
            <div class="journal-info">
              <div class="info-row">
                <div class="info-group">
                  <span class="info-label">Nomor Jurnal</span>
                  <span class="info-value">{{ journalEntry.journal_number }}</span>
                </div>
                <div class="info-group">
                  <span class="info-label">Tanggal</span>
                  <span class="info-value">{{ formatDate(journalEntry.entry_date) }}</span>
                </div>
                <div class="info-group">
                  <span class="info-label">Periode</span>
                  <span class="info-value">{{ journalEntry.accounting_period?.period_name }}</span>
                </div>
              </div>

              <div class="info-row">
                <div class="info-group">
                  <span class="info-label">Tipe Referensi</span>
                  <span class="info-value">{{ journalEntry.reference_type || '-' }}</span>
                </div>
                <div class="info-group">
                  <span class="info-label">ID Referensi</span>
                  <span class="info-value">{{ journalEntry.reference_id || '-' }}</span>
                </div>
                <div class="info-group">
                  <span class="info-label">Dibuat Pada</span>
                  <span class="info-value">{{ formatDateTime(journalEntry.created_at) }}</span>
                </div>
              </div>

              <div class="info-row">
                <div class="info-group full-width">
                  <span class="info-label">Deskripsi</span>
                  <span class="info-value">{{ journalEntry.description || '-' }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Journal Lines -->
        <div class="card">
          <div class="card-header">
            <h2 class="card-title">Detail Jurnal</h2>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Kode Akun</th>
                    <th>Nama Akun</th>
                    <th class="text-right">Debit</th>
                    <th class="text-right">Kredit</th>
                    <th>Deskripsi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="line in journalEntry.journal_entry_lines" :key="line.line_id">
                    <td>{{ line.chart_of_account?.account_code }}</td>
                    <td>{{ line.chart_of_account?.name }}</td>
                    <td class="text-right">{{ formatCurrency(line.debit_amount) }}</td>
                    <td class="text-right">{{ formatCurrency(line.credit_amount) }}</td>
                    <td>{{ line.description || '-' }}</td>
                  </tr>
                </tbody>
                <tfoot>
                  <tr>
                    <th colspan="2" class="text-right">Total</th>
                    <th class="text-right">{{ formatCurrency(totalDebit) }}</th>
                    <th class="text-right">{{ formatCurrency(totalCredit) }}</th>
                    <th></th>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- Print Section (hidden, only for printing) -->
      <div id="print-section" class="print-only">
        <div class="print-header">
          <h1>Jurnal Akuntansi</h1>
          <h2>{{ journalEntry?.journal_number }}</h2>
        </div>

        <div class="print-info">
          <div class="print-info-row">
            <div class="print-info-group">
              <span class="print-info-label">Tanggal</span>
              <span class="print-info-value">{{ formatDate(journalEntry?.entry_date) }}</span>
            </div>
            <div class="print-info-group">
              <span class="print-info-label">Periode</span>
              <span class="print-info-value">{{ journalEntry?.accounting_period?.period_name }}</span>
            </div>
            <div class="print-info-group">
              <span class="print-info-label">Status</span>
              <span class="print-info-value">{{ journalEntry?.status }}</span>
            </div>
          </div>

          <div class="print-info-row">
            <div class="print-info-group full-width">
              <span class="print-info-label">Deskripsi</span>
              <span class="print-info-value">{{ journalEntry?.description || '-' }}</span>
            </div>
          </div>
        </div>

        <table class="print-table">
          <thead>
            <tr>
              <th>Kode Akun</th>
              <th>Nama Akun</th>
              <th>Debit</th>
              <th>Kredit</th>
              <th>Deskripsi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="line in journalEntry?.journal_entry_lines" :key="line.line_id">
              <td>{{ line.chart_of_account?.account_code }}</td>
              <td>{{ line.chart_of_account?.name }}</td>
              <td class="text-right">{{ formatCurrency(line.debit_amount) }}</td>
              <td class="text-right">{{ formatCurrency(line.credit_amount) }}</td>
              <td>{{ line.description || '-' }}</td>
            </tr>
          </tbody>
          <tfoot>
            <tr>
              <th colspan="2" class="text-right">Total</th>
              <th class="text-right">{{ formatCurrency(totalDebit) }}</th>
              <th class="text-right">{{ formatCurrency(totalCredit) }}</th>
              <th></th>
            </tr>
          </tfoot>
        </table>

        <div class="print-footer">
          <div class="signature-area">
            <div class="signature-box">
              <p>Dibuat Oleh</p>
              <div class="signature-line"></div>
              <p>Tanggal: _______________</p>
            </div>

            <div class="signature-box">
              <p>Diperiksa Oleh</p>
              <div class="signature-line"></div>
              <p>Tanggal: _______________</p>
            </div>

            <div class="signature-box">
              <p>Disetujui Oleh</p>
              <div class="signature-line"></div>
              <p>Tanggal: _______________</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Confirmation Modal for Posting -->
      <ConfirmationModal
        v-if="showPostModal"
        title="Posting Jurnal"
        :message="`Apakah Anda yakin ingin memposting jurnal <strong>${journalEntry?.journal_number}</strong>? <br><br>Jurnal yang sudah diposting tidak dapat diubah atau dihapus.`"
        confirm-button-text="Posting"
        confirm-button-class="btn btn-primary"
        @confirm="postJournal"
        @close="showPostModal = false"
      />
    </div>
  </template>

  <script>
  import axios from 'axios';

  export default {
    name: 'JournalEntryDetail',
    props: {
      id: {
        type: [Number, String],
        required: true
      }
    },
    data() {
      return {
        isLoading: true,
        journalEntry: null,
        showPostModal: false
      };
    },
    computed: {
      totalDebit() {
        if (!this.journalEntry || !this.journalEntry.journal_entry_lines) return 0;
        return this.journalEntry.journal_entry_lines.reduce((sum, line) => sum + parseFloat(line.debit_amount || 0), 0);
      },
      totalCredit() {
        if (!this.journalEntry || !this.journalEntry.journal_entry_lines) return 0;
        return this.journalEntry.journal_entry_lines.reduce((sum, line) => sum + parseFloat(line.credit_amount || 0), 0);
      }
    },
    created() {
      this.loadJournalEntry();
    },
    methods: {
      async loadJournalEntry() {
        this.isLoading = true;

        try {
          const response = await axios.get(`/api/accounting/journal-entries/${this.id}`);
          this.journalEntry = response.data.data;
        } catch (error) {
          this.$toast.error('Gagal memuat data jurnal', {
            position: 'top-right',
            duration: 3000
          });
          console.error('Error loading journal entry:', error);
        } finally {
          this.isLoading = false;
        }
      },
      formatDate(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', {
          day: '2-digit',
          month: 'short',
          year: 'numeric'
        });
      },
      formatDateTime(dateTimeString) {
        if (!dateTimeString) return '-';
        const date = new Date(dateTimeString);
        return date.toLocaleDateString('id-ID', {
          day: '2-digit',
          month: 'short',
          year: 'numeric',
          hour: '2-digit',
          minute: '2-digit'
        });
      },
      formatCurrency(value) {
        return new Intl.NumberFormat('id-ID', {
          style: 'currency',
          currency: 'IDR',
          minimumFractionDigits: 2
        }).format(value || 0);
      },
      getStatusBadgeClass(status) {
        switch (status) {
          case 'Posted':
            return 'badge-success';
          case 'Draft':
            return 'badge-secondary';
          case 'Voided':
            return 'badge-danger';
          default:
            return 'badge-secondary';
        }
      },
      showPostConfirmation() {
        this.showPostModal = true;
      },
      async postJournal() {
        try {
          await axios.post(`/api/accounting/journal-entries/${this.id}/post`);
          this.$toast.success('Jurnal berhasil diposting', {
            position: 'top-right',
            duration: 3000
          });
          this.showPostModal = false;
          this.loadJournalEntry();
        } catch (error) {
          this.$toast.error('Gagal posting jurnal', {
            position: 'top-right',
            duration: 3000
          });
          console.error('Error posting journal:', error);
        }
      },
      printJournal() {
        // Open print dialog
        window.print();
      }
    }
  };
  </script>

  <style scoped>
  .journal-entry-detail-container {
    padding: 1rem;
  }

  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
  }

  .action-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
  }

  .journal-info {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }

  .info-row {
    display: flex;
    flex-wrap: wrap;
    gap: 2rem;
    margin-bottom: 0.5rem;
  }

  .info-group {
    flex: 1;
    min-width: 200px;
  }

  .info-group.full-width {
    flex-basis: 100%;
  }

  .info-label {
    display: block;
    font-size: 0.75rem;
    color: var(--gray-500);
    margin-bottom: 0.25rem;
  }

  .info-value {
    font-size: 0.9375rem;
    color: var(--gray-800);
    font-weight: 500;
  }

  .badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
  }

  .text-right {
    text-align: right;
  }

  /* Print Styles */
  @media print {
    .page-header, .card-header, .action-buttons, .btn {
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

  .print-only {
    display: none;
  }

  @media print {
    .print-only {
      display: block;
    }

    .journal-entry-detail-container > div:not(#print-section) {
      display: none;
    }
  }

  /* Print Section Styles */
  .print-header {
    text-align: center;
    margin-bottom: 2rem;
  }

  .print-header h1 {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
  }

  .print-header h2 {
    font-size: 1.25rem;
    color: var(--gray-600);
  }

  .print-info {
    margin-bottom: 2rem;
  }

  .print-info-row {
    display: flex;
    margin-bottom: 1rem;
  }

  .print-info-group {
    flex: 1;
  }

  .print-info-group.full-width {
    flex-basis: 100%;
  }

  .print-info-label {
    font-weight: bold;
    display: block;
    margin-bottom: 0.25rem;
  }

  .print-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 2rem;
  }

  .print-table th,
  .print-table td {
    border: 1px solid var(--gray-300);
    padding: 0.5rem;
  }

  .print-table th {
    background-color: var(--gray-100);
    text-align: left;
  }

  .signature-area {
    display: flex;
    justify-content: space-around;
    margin-top: 3rem;
  }

  .signature-box {
    text-align: center;
    flex: 1;
    max-width: 200px;
  }

  .signature-line {
    height: 1px;
    background-color: var(--gray-300);
    margin: 2rem 0;
  }
  </style>
