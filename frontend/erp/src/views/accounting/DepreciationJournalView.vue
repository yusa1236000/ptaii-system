<!-- src/views/accounting/DepreciationJournalView.vue -->
<template>
    <div class="depreciation-journal">
      <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
          <h2>Jurnal Penyusutan Aset</h2>
          <div>
            <button @click="printJournal" class="btn btn-info mr-2">
              <i class="fas fa-print mr-2"></i> Cetak
            </button>
            <router-link to="/accounting/depreciation" class="btn btn-outline-secondary">
              <i class="fas fa-arrow-left mr-2"></i> Kembali
            </router-link>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <div v-if="isLoading" class="text-center py-5">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p class="mt-2">Memuat jurnal penyusutan...</p>
          </div>

          <div v-else-if="error" class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            {{ error }}
          </div>

          <div v-else-if="!depreciation" class="text-center py-5">
            <i class="fas fa-book-times fa-3x text-muted mb-3"></i>
            <h4>Data Penyusutan Tidak Ditemukan</h4>
            <p class="text-muted">Data penyusutan yang Anda cari mungkin telah dihapus atau tidak ada.</p>
            <router-link to="/accounting/depreciation" class="btn btn-primary mt-2">
              Kembali ke Daftar Penyusutan
            </router-link>
          </div>

          <div v-else>
            <!-- Asset and Depreciation Info -->
            <div class="row mb-4">
              <div class="col-md-6">
                <div class="info-card">
                  <h3 class="info-card-title">Informasi Aset</h3>
                  <table class="table table-details">
                    <tbody>
                      <tr>
                        <th width="40%">Nama Aset:</th>
                        <td>{{ asset.name }}</td>
                      </tr>
                      <tr>
                        <th>Kode Aset:</th>
                        <td>{{ asset.asset_code }}</td>
                      </tr>
                      <tr>
                        <th>Kategori:</th>
                        <td>{{ asset.category }}</td>
                      </tr>
                      <tr>
                        <th>Tanggal Perolehan:</th>
                        <td>{{ formatDate(asset.acquisition_date) }}</td>
                      </tr>
                      <tr>
                        <th>Harga Perolehan:</th>
                        <td>{{ formatCurrency(asset.acquisition_cost) }}</td>
                      </tr>
                      <tr>
                        <th>Tingkat Penyusutan:</th>
                        <td>{{ asset.depreciation_rate }}% per tahun</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
              <div class="col-md-6">
                <div class="info-card">
                  <h3 class="info-card-title">Informasi Penyusutan</h3>
                  <table class="table table-details">
                    <tbody>
                      <tr>
                        <th width="40%">Tanggal Penyusutan:</th>
                        <td>{{ formatDate(depreciation.depreciation_date) }}</td>
                      </tr>
                      <tr>
                        <th>Periode:</th>
                        <td>{{ depreciation.accounting_period?.period_name || '-' }}</td>
                      </tr>
                      <tr>
                        <th>Nilai Penyusutan:</th>
                        <td>{{ formatCurrency(depreciation.depreciation_amount) }}</td>
                      </tr>
                      <tr>
                        <th>Akumulasi Penyusutan:</th>
                        <td>{{ formatCurrency(depreciation.accumulated_depreciation) }}</td>
                      </tr>
                      <tr>
                        <th>Nilai Buku Saat Ini:</th>
                        <td>{{ formatCurrency(depreciation.remaining_value) }}</td>
                      </tr>
                      <tr>
                        <th>Status Jurnal:</th>
                        <td>
                          <span v-if="journalEntry" class="badge badge-success">
                            <i class="fas fa-check-circle mr-1"></i> Dibuat
                          </span>
                          <span v-else class="badge badge-warning">
                            <i class="fas fa-exclamation-circle mr-1"></i> Belum Dibuat
                          </span>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <!-- Journal Entry Section -->
            <div v-if="journalEntry" class="journal-entry-section">
              <h3 class="mb-3">Detail Jurnal</h3>

              <div class="journal-entry-card">
                <div class="journal-entry-header">
                  <div class="row">
                    <div class="col-md-6">
                      <h4 class="mb-1">{{ journalEntry.journal_number }}</h4>
                      <div class="journal-date">
                        <i class="fas fa-calendar-alt mr-1"></i> Tanggal: {{ formatDate(journalEntry.entry_date) }}
                      </div>
                    </div>
                    <div class="col-md-6 text-right">
                      <div class="journal-status">
                        <span :class="['badge', journalEntry.status === 'Posted' ? 'badge-success' : 'badge-warning']">
                          {{ journalEntry.status }}
                        </span>
                        <router-link :to="`/accounting/journal-entries/${journalEntry.journal_id}`" class="btn btn-sm btn-outline-primary ml-2">
                          <i class="fas fa-external-link-alt mr-1"></i> Lihat Jurnal
                        </router-link>
                      </div>
                      <div class="journal-description mt-1">
                        {{ journalEntry.description }}
                      </div>
                    </div>
                  </div>
                </div>

                <div class="journal-entry-lines mt-3">
                  <table class="data-table">
                    <thead>
                      <tr>
                        <th width="10%">No</th>
                        <th width="40%">Akun</th>
                        <th class="text-right">Debit</th>
                        <th class="text-right">Kredit</th>
                        <th>Keterangan</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(line, index) in journalEntry.journal_entry_lines" :key="line.line_id">
                        <td>{{ index + 1 }}</td>
                        <td>
                          <div>
                            <div>{{ getAccountName(line.account_id) }}</div>
                            <small class="text-muted">{{ getAccountCode(line.account_id) }}</small>
                          </div>
                        </td>
                        <td class="text-right">{{ formatCurrency(line.debit_amount) }}</td>
                        <td class="text-right">{{ formatCurrency(line.credit_amount) }}</td>
                        <td>{{ line.description || '-' }}</td>
                      </tr>
                      <tr class="total-row">
                        <td colspan="2" class="text-right font-weight-bold">Total</td>
                        <td class="text-right font-weight-bold">{{ formatCurrency(totalDebit) }}</td>
                        <td class="text-right font-weight-bold">{{ formatCurrency(totalCredit) }}</td>
                        <td></td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <!-- No Journal Entry Section -->
            <div v-else class="no-journal-section">
              <div class="alert alert-info">
                <div class="d-flex">
                  <i class="fas fa-info-circle mr-3 mt-1 fa-2x"></i>
                  <div>
                    <h4 class="mb-2">Jurnal Belum Dibuat</h4>
                    <p class="mb-3">
                      Penyusutan ini belum memiliki jurnal akuntansi yang terkait.
                      Anda dapat membuat jurnal penyusutan secara manual.
                    </p>
                    <button
                      @click="createJournalEntry"
                      class="btn btn-primary"
                      :disabled="isCreatingJournal"
                    >
                      <i :class="isCreatingJournal ? 'fas fa-spinner fa-spin' : 'fas fa-plus'"></i>
                      {{ isCreatingJournal ? 'Membuat Jurnal...' : 'Buat Jurnal Penyusutan' }}
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Journal Entry Template -->
            <div v-if="!journalEntry" class="journal-template mt-4">
              <h3 class="mb-3">Contoh Jurnal yang akan Dibuat</h3>

              <div class="card bg-light">
                <div class="card-body">
                  <div class="table-responsive">
                    <table class="data-table">
                      <thead>
                        <tr>
                          <th width="40%">Akun</th>
                          <th class="text-right">Debit</th>
                          <th class="text-right">Kredit</th>
                          <th>Keterangan</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr>
                          <td>
                            <div>Beban Penyusutan</div>
                            <small class="text-muted">Expense Account</small>
                          </td>
                          <td class="text-right">{{ formatCurrency(depreciation.depreciation_amount) }}</td>
                          <td class="text-right">-</td>
                          <td>Penyusutan {{ asset.name }} periode {{ depreciation.accounting_period?.period_name }}</td>
                        </tr>
                        <tr>
                          <td>
                            <div>Akumulasi Penyusutan</div>
                            <small class="text-muted">Contra Asset Account</small>
                          </td>
                          <td class="text-right">-</td>
                          <td class="text-right">{{ formatCurrency(depreciation.depreciation_amount) }}</td>
                          <td>Penyusutan {{ asset.name }} periode {{ depreciation.accounting_period?.period_name }}</td>
                        </tr>
                        <tr class="total-row">
                          <td class="text-right font-weight-bold">Total</td>
                          <td class="text-right font-weight-bold">{{ formatCurrency(depreciation.depreciation_amount) }}</td>
                          <td class="text-right font-weight-bold">{{ formatCurrency(depreciation.depreciation_amount) }}</td>
                          <td></td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </template>

  <script>
  import { ref, onMounted, computed } from 'vue';
  import { useRoute } from 'vue-router';
  import axios from 'axios';

  export default {
    name: 'DepreciationJournalView',
    setup() {
      const route = useRoute();
      //const router = useRouter();
      const depreciationId = computed(() => route.params.id);

      const isLoading = ref(true);
      const isCreatingJournal = ref(false);
      const error = ref(null);
      const depreciation = ref(null);
      const asset = ref(null);
      const journalEntry = ref(null);
      const accounts = ref([]);

      // Computed properties
      const totalDebit = computed(() => {
        if (!journalEntry.value || !journalEntry.value.journal_entry_lines) {
          return 0;
        }
        return journalEntry.value.journal_entry_lines.reduce((sum, line) => {
          return sum + parseFloat(line.debit_amount || 0);
        }, 0);
      });

      const totalCredit = computed(() => {
        if (!journalEntry.value || !journalEntry.value.journal_entry_lines) {
          return 0;
        }
        return journalEntry.value.journal_entry_lines.reduce((sum, line) => {
          return sum + parseFloat(line.credit_amount || 0);
        }, 0);
      });

      // Methods
      const fetchData = async () => {
        isLoading.value = true;
        error.value = null;

        try {
          // Get depreciation details
          const response = await axios.get(`/api/accounting/asset-depreciations/${depreciationId.value}`);
          depreciation.value = response.data.data;

          // Get asset details
          const assetId = depreciation.value.asset_id;
          const assetResponse = await axios.get(`/api/accounting/fixed-assets/${assetId}`);
          asset.value = assetResponse.data.data;

          // Get chart of accounts for references
          const accountsResponse = await axios.get('/api/accounting/chart-of-accounts');
          accounts.value = accountsResponse.data.data || [];

          // Check for related journal entry
          const journalResponse = await axios.get('/api/accounting/journal-entries', {
            params: {
              reference_type: 'AssetDepreciation',
              reference_id: depreciationId.value
            }
          });

          if (journalResponse.data.data && journalResponse.data.data.length > 0) {
            // Get detailed journal entry
            const journalId = journalResponse.data.data[0].journal_id;
            const journalDetailResponse = await axios.get(`/api/accounting/journal-entries/${journalId}`);
            journalEntry.value = journalDetailResponse.data.data;
          } else {
            journalEntry.value = null;
          }
        } catch (err) {
          console.error('Error fetching depreciation data:', err);
          error.value = 'Gagal memuat data penyusutan. Silakan coba lagi nanti.';
        } finally {
          isLoading.value = false;
        }
      };

      const getAccountName = (accountId) => {
        const account = accounts.value.find(a => a.account_id === accountId);
        return account ? account.name : 'Unknown Account';
      };

      const getAccountCode = (accountId) => {
        const account = accounts.value.find(a => a.account_id === accountId);
        return account ? account.account_code : '';
      };

      const createJournalEntry = async () => {
        if (!depreciation.value || !asset.value) return;

        isCreatingJournal.value = true;
        error.value = null;

        try {
          // Find appropriate accounts
          const findAccount = (keyword, type) => {
            return accounts.value.find(account =>
              account.account_type === type &&
              account.name.toLowerCase().includes(keyword.toLowerCase()) &&
              account.is_active
            );
          };

          // Find depreciation expense account
          const expenseAccount = findAccount('depreciation', 'Expense') ||
                                findAccount('penyusutan', 'Expense');

          // Find accumulated depreciation account
          const accumulatedAccount = findAccount('accumulated', 'Asset') ||
                                   findAccount('akumulasi', 'Asset');

          if (!expenseAccount || !accumulatedAccount) {
            throw new Error('Akun beban penyusutan dan akumulasi penyusutan tidak ditemukan.');
          }

          // Get the current period
          const currentPeriodResponse = await axios.get('/api/accounting/accounting-periods/current');
          const currentPeriod = currentPeriodResponse.data.data;

          if (!currentPeriod) {
            throw new Error('Tidak ada periode akuntansi yang aktif saat ini.');
          }

          // Prepare journal entry data
          const journalData = {
            journal_number: `DEPR-${new Date().getTime().toString().substr(-6)}`,
            entry_date: new Date().toISOString().split('T')[0],
            reference_type: 'AssetDepreciation',
            reference_id: depreciation.value.depreciation_id,
            description: `Penyusutan ${asset.value.name} periode ${depreciation.value.accounting_period?.period_name || 'saat ini'}`,
            period_id: currentPeriod.period_id,
            status: 'Posted',
            lines: [
              {
                account_id: expenseAccount.account_id,
                debit_amount: depreciation.value.depreciation_amount,
                credit_amount: 0,
                description: `Penyusutan ${asset.value.name}`
              },
              {
                account_id: accumulatedAccount.account_id,
                debit_amount: 0,
                credit_amount: depreciation.value.depreciation_amount,
                description: `Akumulasi penyusutan ${asset.value.name}`
              }
            ]
          };

          // Create journal entry
          await axios.post('/api/accounting/journal-entries', journalData);

          // Refresh data to show new journal
          await fetchData();

        } catch (err) {
          console.error('Error creating journal entry:', err);
          error.value = 'Gagal membuat jurnal penyusutan: ' +
                        (err.response?.data?.message || err.message || 'Silakan coba lagi nanti.');
        } finally {
          isCreatingJournal.value = false;
        }
      };

      const printJournal = () => {
        window.print();
      };

      const formatDate = (dateString) => {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', {
          year: 'numeric',
          month: 'long',
          day: 'numeric'
        });
      };

      const formatCurrency = (amount) => {
        return new Intl.NumberFormat('id-ID', {
          style: 'currency',
          currency: 'IDR',
          minimumFractionDigits: 0,
          maximumFractionDigits: 0
        }).format(amount || 0);
      };

      // Lifecycle hooks
      onMounted(() => {
        fetchData();
      });

      return {
        depreciationId,
        isLoading,
        isCreatingJournal,
        error,
        depreciation,
        asset,
        journalEntry,
        totalDebit,
        totalCredit,
        getAccountName,
        getAccountCode,
        createJournalEntry,
        printJournal,
        formatDate,
        formatCurrency
      };
    }
  };
  </script>

  <style scoped>
  .info-card {
    background-color: var(--gray-50);
    border-radius: 0.5rem;
    padding: 1.25rem;
    height: 100%;
    margin-bottom: 1rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  }

  .info-card-title {
    font-size: 1.125rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: var(--gray-700);
    border-bottom: 1px solid var(--gray-200);
    padding-bottom: 0.5rem;
  }

  .table-details {
    margin-bottom: 0;
  }

  .table-details th {
    font-weight: 600;
    padding: 0.5rem 0.25rem;
  }

  .table-details td {
    padding: 0.5rem 0.25rem;
  }

  .journal-entry-card {
    border: 1px solid var(--gray-200);
    border-radius: 0.5rem;
    overflow: hidden;
    background-color: white;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  }

  .journal-entry-header {
    padding: 1.25rem;
    background-color: var(--gray-50);
    border-bottom: 1px solid var(--gray-200);
  }

  .journal-entry-lines {
    padding: 0.5rem;
  }

  .journal-date {
    color: var(--gray-600);
    font-size: 0.875rem;
  }

  .journal-description {
    color: var(--gray-600);
    font-size: 0.875rem;
  }

  .total-row {
    background-color: var(--gray-50);
    font-weight: bold;
  }

  .badge {
    padding: 0.35em 0.65em;
    font-size: 0.75em;
  }

  @media print {
    .page-header .btn,
    .no-journal-section {
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
  </style>
