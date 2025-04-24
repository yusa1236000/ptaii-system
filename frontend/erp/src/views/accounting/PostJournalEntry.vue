<!-- src/views/accounting/PostJournalEntry.vue -->
<template>
    <div class="post-journal-entry-container">
      <div class="page-header">
        <h1>Posting Jurnal</h1>
        <div class="action-buttons">
          <button @click="$router.go(-1)" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
          </button>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h2 class="card-title">Posting Jurnal #{{ id }}</h2>
        </div>

        <div class="card-body">
          <div v-if="isLoading" class="loading-indicator">
            <i class="fas fa-spinner fa-spin"></i> Memuat data...
          </div>

          <div v-else-if="!journalEntry" class="empty-state">
            <div class="empty-icon">
              <i class="fas fa-file-invoice"></i>
            </div>
            <h3>Data Tidak Ditemukan</h3>
            <p>Jurnal yang Anda cari tidak ditemukan atau telah dihapus.</p>
          </div>

          <div v-else>
            <div class="alert alert-info">
              <i class="fas fa-info-circle"></i>
              <div>
                <p><strong>Perhatian:</strong> Jurnal yang sudah diposting tidak dapat diubah atau dihapus.</p>
                <p>Harap periksa kembali data jurnal sebelum melakukan posting.</p>
              </div>
            </div>

            <!-- Journal Header Information -->
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
                  <span class="info-label">Status</span>
                  <span :class="getStatusClass(journalEntry.status)" class="badge">
                    {{ journalEntry.status }}
                  </span>
                </div>
              </div>

              <div class="info-row">
                <div class="info-group full-width">
                  <span class="info-label">Deskripsi</span>
                  <span class="info-value">{{ journalEntry.description || '-' }}</span>
                </div>
              </div>
            </div>

            <!-- Journal Lines -->
            <div class="journal-lines">
              <h3>Detail Jurnal</h3>
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

            <div class="post-confirmation">
              <div class="confirmation-checkbox">
                <label class="checkbox-container">
                  <input type="checkbox" v-model="confirmPosting">
                  <span class="checkbox-label">Saya telah memeriksa jurnal ini dan yakin untuk melakukan posting</span>
                </label>
              </div>

              <div class="post-actions">
                <button @click="$router.go(-1)" class="btn btn-secondary">
                  Batal
                </button>
                <button
                  @click="postJournal"
                  :disabled="!confirmPosting || isPosting"
                  class="btn btn-primary"
                >
                  <i v-if="isPosting" class="fas fa-spinner fa-spin"></i>
                  <i v-else class="fas fa-check-circle"></i>
                  {{ isPosting ? 'Memproses...' : 'Posting Jurnal' }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </template>

  <script>
  import axios from 'axios';

  export default {
    name: 'PostJournalEntry',
    props: {
      id: {
        type: [Number, String],
        required: true
      }
    },
    data() {
      return {
        isLoading: true,
        isPosting: false,
        journalEntry: null,
        confirmPosting: false
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

          // Redirect if journal is already posted
          if (this.journalEntry.status === 'Posted') {
            this.$toast.info('Jurnal ini sudah diposting sebelumnya', {
              position: 'top-right',
              duration: 3000
            });
            this.$router.push(`/accounting/journal-entries/${this.id}`);
          }
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
      formatCurrency(value) {
        return new Intl.NumberFormat('id-ID', {
          style: 'currency',
          currency: 'IDR',
          minimumFractionDigits: 2
        }).format(value || 0);
      },
      getStatusClass(status) {
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
      async postJournal() {
        if (!this.confirmPosting) return;

        this.isPosting = true;

        try {
          await axios.post(`/api/accounting/journal-entries/${this.id}/post`);

          this.$toast.success('Jurnal berhasil diposting', {
            position: 'top-right',
            duration: 3000
          });

          // Redirect to journal detail page
          this.$router.push(`/accounting/journal-entries/${this.id}`);
        } catch (error) {
          this.$toast.error('Gagal posting jurnal', {
            position: 'top-right',
            duration: 3000
          });
          console.error('Error posting journal:', error);
        } finally {
          this.isPosting = false;
        }
      }
    }
  };
  </script>

  <style scoped>
  .post-journal-entry-container {
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

  .alert {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1rem;
    border-radius: 0.5rem;
    margin-bottom: 1.5rem;
  }

  .alert-info {
    background-color: rgba(37, 99, 235, 0.1);
    border-left: 4px solid var(--primary-color);
  }

  .alert i {
    font-size: 1.5rem;
    color: var(--primary-color);
  }

  .alert p {
    margin-bottom: 0.5rem;
  }

  .alert p:last-child {
    margin-bottom: 0;
  }

  .journal-info {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-bottom: 2rem;
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

  .badge-success {
    background-color: var(--success-bg);
    color: var(--success-color);
  }

  .badge-secondary {
    background-color: var(--gray-100);
    color: var(--gray-700);
  }

  .badge-danger {
    background-color: var(--danger-bg);
    color: var(--danger-color);
  }

  .journal-lines {
    margin-bottom: 2rem;
  }

  .journal-lines h3 {
    margin-bottom: 1rem;
    font-size: 1.125rem;
  }

  .table-responsive {
    overflow-x: auto;
  }

  .data-table {
    width: 100%;
    border-collapse: collapse;
  }

  .data-table th,
  .data-table td {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--gray-100);
  }

  .data-table th {
    background-color: var(--gray-50);
    font-weight: 500;
    color: var(--gray-600);
    text-align: left;
  }

  .data-table tfoot th {
    border-top: 1px solid var(--gray-200);
  }

  .text-right {
    text-align: right;
  }

  .post-confirmation {
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid var(--gray-200);
  }

  .confirmation-checkbox {
    margin-bottom: 1.5rem;
  }

  .checkbox-container {
    display: flex;
    align-items: center;
    cursor: pointer;
  }

  .checkbox-label {
    margin-left: 0.5rem;
    font-weight: 500;
  }

  .post-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
  }

  @media (max-width: 768px) {
    .info-row {
      flex-direction: column;
      gap: 1rem;
    }

    .info-group {
      min-width: auto;
    }

    .post-actions {
      flex-direction: column;
      gap: 0.5rem;
    }

    .post-actions button {
      width: 100%;
    }
  }
  </style>
