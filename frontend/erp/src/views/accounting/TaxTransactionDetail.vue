<!-- Template section for TaxTransactionDetail.vue -->
<template>
    <div class="tax-transaction-detail">
      <!-- Loading state -->
      <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-2">Memuat detail transaksi pajak...</p>
      </div>

      <div v-else-if="!taxTransaction" class="empty-state py-5">
        <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
        <h4>Data tidak ditemukan</h4>
        <p>Transaksi pajak yang Anda cari tidak dapat ditemukan.</p>
        <router-link to="/accounting/tax-transactions" class="btn btn-primary mt-3">
          <i class="fas fa-arrow-left mr-2"></i> Kembali ke Daftar
        </router-link>
      </div>

      <div v-else>
        <!-- Header with actions -->
        <div class="card mb-4">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">Detail Transaksi Pajak</h3>
            <div class="header-actions">
              <router-link
                v-if="taxTransaction.status === 'Pending'"
                :to="`/accounting/tax-transactions/${taxTransaction.tax_transaction_id}/edit`"
                class="btn btn-primary mr-2"
              >
                <i class="fas fa-edit mr-1"></i> Edit
              </router-link>
              <button
                v-if="taxTransaction.status === 'Pending'"
                @click="confirmFileTax"
                class="btn btn-success mr-2"
              >
                <i class="fas fa-check mr-1"></i> Laporkan
              </button>
              <button
                v-if="taxTransaction.status === 'Filed'"
                @click="confirmPayTax"
                class="btn btn-warning mr-2"
              >
                <i class="fas fa-money-bill-wave mr-1"></i> Bayar
              </button>
              <button
                v-if="taxTransaction.status === 'Pending'"
                @click="confirmDeleteTax"
                class="btn btn-danger"
              >
                <i class="fas fa-trash mr-1"></i> Hapus
              </button>
              <button
                @click="printDetail"
                class="btn btn-secondary ml-2"
              >
                <i class="fas fa-print mr-1"></i> Cetak
              </button>
            </div>
          </div>
        </div>

        <!-- Tax Transaction Info -->
        <div class="card mb-4">
          <div class="card-body">
            <div class="tax-header d-flex justify-content-between mb-4">
              <div>
                <h4 class="mb-1">{{ taxTransaction.tax_number }}</h4>
                <span :class="getStatusBadgeClass(taxTransaction.status)">{{ taxTransaction.status }}</span>
                <span :class="[getTaxTypeBadgeClass(taxTransaction.tax_type), 'ml-2']">{{ taxTransaction.tax_type }}</span>
              </div>
              <div class="tax-amount">
                <h3>{{ formatCurrency(taxTransaction.tax_amount) }}</h3>
                <div class="text-muted">Jatuh Tempo: {{ formatDate(taxTransaction.due_date) }}</div>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <!-- Left column -->
              <div>
                <h5 class="section-title">Informasi Umum</h5>
                <div class="info-table">
                  <div class="info-row">
                    <div class="info-label">Jenis Pajak</div>
                    <div class="info-value">{{ taxTransaction.tax_type }}</div>
                  </div>
                  <div class="info-row">
                    <div class="info-label">Periode Pajak</div>
                    <div class="info-value">{{ formatMonthYear(taxTransaction.tax_period) }}</div>
                  </div>
                  <div class="info-row">
                    <div class="info-label">Tanggal Transaksi</div>
                    <div class="info-value">{{ formatDate(taxTransaction.transaction_date) }}</div>
                  </div>
                  <div class="info-row">
                    <div class="info-label">Tanggal Jatuh Tempo</div>
                    <div class="info-value" :class="{ 'text-danger': isOverdue }">
                      {{ formatDate(taxTransaction.due_date) }}
                      <span v-if="isOverdue" class="badge badge-danger ml-2">Terlambat</span>
                    </div>
                  </div>
                  <div class="info-row">
                    <div class="info-label">Status</div>
                    <div class="info-value">
                      <span :class="getStatusBadgeClass(taxTransaction.status)">{{ taxTransaction.status }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Right column -->
              <div>
                <h5 class="section-title">Detail Keuangan</h5>
                <div class="info-table">
                  <div class="info-row">
                    <div class="info-label">Jumlah Kena Pajak</div>
                    <div class="info-value">{{ formatCurrency(taxTransaction.taxable_amount || 0) }}</div>
                  </div>
                  <div class="info-row">
                    <div class="info-label">Tarif Pajak</div>
                    <div class="info-value">{{ taxTransaction.tax_rate || 0 }}%</div>
                  </div>
                  <div class="info-row">
                    <div class="info-label">Jumlah Pajak</div>
                    <div class="info-value font-weight-bold">{{ formatCurrency(taxTransaction.tax_amount) }}</div>
                  </div>
                  <div class="info-row">
                    <div class="info-label">Akun Pajak</div>
                    <div class="info-value">{{ taxAccount ? `${taxAccount.account_code} - ${taxAccount.account_name}` : '-' }}</div>
                  </div>
                  <div class="info-row">
                    <div class="info-label">Referensi</div>
                    <div class="info-value">
                      {{ taxTransaction.reference_type ? `${taxTransaction.reference_type}: ${taxTransaction.reference_id}` : '-' }}
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Description -->
            <div class="mt-4">
              <h5 class="section-title">Deskripsi</h5>
              <div class="description-box">
                {{ taxTransaction.description || 'Tidak ada deskripsi tambahan.' }}
              </div>
            </div>

            <!-- Timeline -->
            <div class="mt-4">
              <h5 class="section-title">Timeline</h5>
              <div class="timeline">
                <div class="timeline-item">
                  <div class="timeline-point"></div>
                  <div class="timeline-content">
                    <h6>Dibuat</h6>
                    <p>{{ formatDateTime(taxTransaction.created_at) }}</p>
                  </div>
                </div>
                <div v-if="taxTransaction.filed_date" class="timeline-item">
                  <div class="timeline-point success"></div>
                  <div class="timeline-content">
                    <h6>Dilaporkan</h6>
                    <p>{{ formatDateTime(taxTransaction.filed_date) }}</p>
                  </div>
                </div>
                <div v-if="taxTransaction.payment_date" class="timeline-item">
                  <div class="timeline-point warning"></div>
                  <div class="timeline-content">
                    <h6>Dibayar</h6>
                    <p>{{ formatDateTime(taxTransaction.payment_date) }}</p>
                  </div>
                </div>
                <div v-if="taxTransaction.last_updated_at && taxTransaction.last_updated_at !== taxTransaction.created_at" class="timeline-item">
                  <div class="timeline-point info"></div>
                  <div class="timeline-content">
                    <h6>Terakhir Diperbarui</h6>
                    <p>{{ formatDateTime(taxTransaction.last_updated_at) }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Journal Entry Section -->
        <div class="card mb-4">
          <div class="card-header">
            <h5 class="card-title">Jurnal Terkait</h5>
          </div>
          <div class="card-body">
            <div v-if="!relatedJournal" class="text-center py-3">
              <p>Tidak ada jurnal terkait untuk transaksi pajak ini.</p>
              <button v-if="taxTransaction.status !== 'Pending'" @click="generateJournal" class="btn btn-primary mt-2">
                <i class="fas fa-plus-circle mr-1"></i> Buat Jurnal
              </button>
            </div>
            <div v-else class="table-container">
              <div class="journal-header d-flex justify-content-between mb-3">
                <div>
                  <strong>Nomor Jurnal:</strong> {{ relatedJournal.journal_number }}
                </div>
                <div>
                  <router-link :to="`/accounting/journal-entries/${relatedJournal.journal_id}`" class="btn btn-sm btn-info">
                    <i class="fas fa-eye mr-1"></i> Lihat Detail
                  </router-link>
                </div>
              </div>
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Akun</th>
                    <th>Deskripsi</th>
                    <th class="text-right">Debit</th>
                    <th class="text-right">Kredit</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(line, index) in relatedJournal.lines" :key="index">
                    <td>{{ line.account_code }} - {{ line.account_name }}</td>
                    <td>{{ line.description }}</td>
                    <td class="text-right">{{ formatCurrency(line.debit_amount) }}</td>
                    <td class="text-right">{{ formatCurrency(line.credit_amount) }}</td>
                  </tr>
                </tbody>
                <tfoot>
                  <tr>
                    <td colspan="2" class="text-right"><strong>Total</strong></td>
                    <td class="text-right"><strong>{{ formatCurrency(totalDebit) }}</strong></td>
                    <td class="text-right"><strong>{{ formatCurrency(totalCredit) }}</strong></td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
        </div>

        <!-- Document Attachments -->
        <div class="card mb-4">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title">Dokumen Terkait</h5>
            <button class="btn btn-sm btn-primary">
              <i class="fas fa-upload mr-1"></i> Tambah Dokumen
            </button>
          </div>
          <div class="card-body">
            <div v-if="documents.length === 0" class="text-center py-3">
              <p>Tidak ada dokumen yang dilampirkan.</p>
            </div>
            <div v-else class="documents-list">
              <div v-for="(doc, index) in documents" :key="index" class="document-item">
                <div class="document-icon">
                  <i class="fas fa-file-pdf"></i>
                </div>
                <div class="document-info">
                  <h6>{{ doc.document_name }}</h6>
                  <p class="text-muted">{{ formatFileSize(doc.file_size) }} - Diunggah pada {{ formatDate(doc.uploaded_at) }}</p>
                </div>
                <div class="document-actions">
                  <button class="btn btn-sm btn-info mr-1">
                    <i class="fas fa-download"></i>
                  </button>
                  <button class="btn btn-sm btn-danger">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Confirmation Modal -->
      <ConfirmationModal
        v-if="showConfirmationModal"
        :title="confirmationModal.title"
        :message="confirmationModal.message"
        :confirm-button-text="confirmationModal.confirmButtonText"
        :confirm-button-class="confirmationModal.confirmButtonClass"
        @confirm="confirmationModal.onConfirm"
        @close="showConfirmationModal = false"
      />
    </div>
  </template>
  <script>
  import { ref, reactive, computed, onMounted } from 'vue';
  import { useRoute, useRouter } from 'vue-router';
  import axios from 'axios';

  export default {
    name: 'TaxTransactionDetail',
    setup() {
      const route = useRoute();
      const router = useRouter();
      const taxId = route.params.id;

      const taxTransaction = ref(null);
      const taxAccount = ref(null);
      const relatedJournal = ref(null);
      const documents = ref([]);
      const loading = ref(true);

      const showConfirmationModal = ref(false);
      const confirmationModal = reactive({
        title: '',
        message: '',
        confirmButtonText: '',
        confirmButtonClass: '',
        onConfirm: () => {}
      });

      const isOverdue = computed(() => {
        if (!taxTransaction.value || taxTransaction.value.status === 'Paid') return false;
        const today = new Date();
        const dueDate = new Date(taxTransaction.value.due_date);
        return today > dueDate;
      });

      const totalDebit = computed(() => {
        if (!relatedJournal.value || !relatedJournal.value.lines) return 0;
        return relatedJournal.value.lines.reduce((sum, line) => sum + parseFloat(line.debit_amount || 0), 0);
      });

      const totalCredit = computed(() => {
        if (!relatedJournal.value || !relatedJournal.value.lines) return 0;
        return relatedJournal.value.lines.reduce((sum, line) => sum + parseFloat(line.credit_amount || 0), 0);
      });

      const loadTaxTransaction = async () => {
        loading.value = true;
        try {
          const response = await axios.get(`/api/accounting/tax-transactions/${taxId}`);
          taxTransaction.value = response.data.data;

          // Load tax account details
          if (taxTransaction.value.account_id) {
            await loadTaxAccount(taxTransaction.value.account_id);
          }

          // Load related journal entries
          await loadRelatedJournal();

          // Load documents (mock data for now)
          documents.value = [
            {
              document_name: 'Bukti Pembayaran.pdf',
              file_size: 1024 * 1024, // 1MB
              uploaded_at: new Date().toISOString(),
              document_url: '#'
            },
            {
              document_name: 'Bukti Potong Pajak.pdf',
              file_size: 2.5 * 1024 * 1024, // 2.5MB
              uploaded_at: new Date().toISOString(),
              document_url: '#'
            },
            {
              document_name: 'Formulir Pajak.pdf',
              file_size: 1.2 * 1024 * 1024, // 1.2MB
              uploaded_at: new Date().toISOString(),
              document_url: '#'
            }
          ];
        } catch (error) {
          console.error('Failed to load tax transaction:', error);
          taxTransaction.value = null;
        } finally {
          loading.value = false;
        }
      };

      const loadTaxAccount = async (accountId) => {
        try {
          const response = await axios.get(`/api/accounting/chart-of-accounts/${accountId}`);
          taxAccount.value = response.data.data;
        } catch (error) {
          console.error('Failed to load tax account:', error);
          taxAccount.value = null;
        }
      };

      const loadRelatedJournal = async () => {
        try {
          // In a real implementation, you would fetch the related journal entries
          // For now, we'll create a mock journal entry if the tax transaction is filed or paid
          if (taxTransaction.value && (taxTransaction.value.status === 'Filed' || taxTransaction.value.status === 'Paid')) {
            relatedJournal.value = {
              journal_id: 123,
              journal_number: `J-TAX-${new Date().getFullYear()}-${taxId}`,
              entry_date: taxTransaction.value.filed_date || taxTransaction.value.transaction_date,
              status: 'Posted',
              lines: [
                {
                  account_code: '2100',
                  account_name: 'Utang Pajak',
                  description: `Pencatatan ${taxTransaction.value.tax_type} untuk periode ${formatMonthYear(taxTransaction.value.tax_period)}`,
                  debit_amount: 0,
                  credit_amount: taxTransaction.value.tax_amount
                },
                {
                  account_code: '6200',
                  account_name: 'Beban Pajak',
                  description: `Pencatatan Beban ${taxTransaction.value.tax_type}`,
                  debit_amount: taxTransaction.value.tax_amount,
                  credit_amount: 0
                }
              ]
            };

            // If the tax is paid, add the payment journal entries
            if (taxTransaction.value.status === 'Paid') {
              relatedJournal.value.lines.push({
                account_code: '2100',
                account_name: 'Utang Pajak',
                description: `Pembayaran ${taxTransaction.value.tax_type}`,
                debit_amount: taxTransaction.value.tax_amount,
                credit_amount: 0
              });
              relatedJournal.value.lines.push({
                account_code: '1100',
                account_name: 'Kas',
                description: `Pembayaran ${taxTransaction.value.tax_type}`,
                debit_amount: 0,
                credit_amount: taxTransaction.value.tax_amount
              });
            }
          } else {
            relatedJournal.value = null;
          }
        } catch (error) {
          console.error('Failed to load related journal:', error);
          relatedJournal.value = null;
        }
      };

      const generateJournal = async () => {
        try {
          // In a real implementation, you would make an API call to generate a journal entry
          await new Promise(resolve => setTimeout(resolve, 500)); // Simulate API call

          alert('Jurnal berhasil dibuat!');
          await loadRelatedJournal(); // Reload the journal data
        } catch (error) {
          console.error('Failed to generate journal:', error);
          alert('Gagal membuat jurnal. Silakan coba lagi nanti.');
        }
      };

      const formatDate = (dateString) => {
        if (!dateString) return '-';
        const options = { year: 'numeric', month: 'short', day: 'numeric' };
        return new Date(dateString).toLocaleDateString('id-ID', options);
      };

      const formatDateTime = (dateString) => {
        if (!dateString) return '-';
        const options = {
          year: 'numeric',
          month: 'short',
          day: 'numeric',
          hour: '2-digit',
          minute: '2-digit'
        };
        return new Date(dateString).toLocaleDateString('id-ID', options);
      };

      const formatMonthYear = (dateString) => {
        if (!dateString) return '-';
        const [year, month] = dateString.split('-');
        const date = new Date(parseInt(year), parseInt(month) - 1, 1);
        const options = { year: 'numeric', month: 'long' };
        return date.toLocaleDateString('id-ID', options);
      };

      const formatCurrency = (amount) => {
        return new Intl.NumberFormat('id-ID', {
          style: 'currency',
          currency: 'IDR',
          minimumFractionDigits: 0,
          maximumFractionDigits: 0
        }).format(amount || 0);
      };

      const formatFileSize = (bytes) => {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
      };

      const getTaxTypeBadgeClass = (taxType) => {
        const classes = {
          'PPN': 'badge badge-primary',
          'PPH21': 'badge badge-info',
          'PPH23': 'badge badge-warning',
          'PPH4(2)': 'badge badge-secondary',
          'Other': 'badge badge-dark'
        };
        return classes[taxType] || 'badge badge-dark';
      };

      const getStatusBadgeClass = (status) => {
        const classes = {
          'Pending': 'badge badge-warning',
          'Filed': 'badge badge-info',
          'Paid': 'badge badge-success'
        };
        return classes[status] || 'badge badge-secondary';
      };

      const confirmFileTax = () => {
        showConfirmationModal.value = true;
        confirmationModal.title = 'Konfirmasi Pelaporan Pajak';
        confirmationModal.message = `Apakah Anda yakin ingin melaporkan pajak <strong>${taxTransaction.value.tax_number}</strong>?`;
        confirmationModal.confirmButtonText = 'Laporkan';
        confirmationModal.confirmButtonClass = 'btn btn-success';
        confirmationModal.onConfirm = fileTax;
      };

      const fileTax = async () => {
        try {
          await axios.post(`/api/accounting/tax-transactions/${taxId}/file`);
          showConfirmationModal.value = false;
          alert('Pajak berhasil dilaporkan!');
          loadTaxTransaction(); // Reload the data
        } catch (error) {
          console.error('Failed to file tax:', error);
          alert('Gagal melaporkan pajak. Silakan coba lagi nanti.');
        }
      };

      const confirmPayTax = () => {
        showConfirmationModal.value = true;
        confirmationModal.title = 'Konfirmasi Pembayaran Pajak';
        confirmationModal.message = `Apakah Anda yakin ingin menandai pajak <strong>${taxTransaction.value.tax_number}</strong> sebagai sudah dibayar?`;
        confirmationModal.confirmButtonText = 'Bayar';
        confirmationModal.confirmButtonClass = 'btn btn-warning';
        confirmationModal.onConfirm = payTax;
      };

      const payTax = async () => {
        try {
          await axios.post(`/api/accounting/tax-transactions/${taxId}/pay`);
          showConfirmationModal.value = false;
          alert('Pajak berhasil ditandai sebagai dibayar!');
          loadTaxTransaction(); // Reload the data
        } catch (error) {
          console.error('Failed to pay tax:', error);
          alert('Gagal menandai pajak sebagai dibayar. Silakan coba lagi nanti.');
        }
      };

      const confirmDeleteTax = () => {
        showConfirmationModal.value = true;
        confirmationModal.title = 'Konfirmasi Hapus Pajak';
        confirmationModal.message = `Apakah Anda yakin ingin menghapus transaksi pajak <strong>${taxTransaction.value.tax_number}</strong>? Tindakan ini tidak dapat dibatalkan.`;
        confirmationModal.confirmButtonText = 'Hapus';
        confirmationModal.confirmButtonClass = 'btn btn-danger';
        confirmationModal.onConfirm = deleteTax;
      };

      const deleteTax = async () => {
        try {
          await axios.delete(`/api/accounting/tax-transactions/${taxId}`);
          showConfirmationModal.value = false;
          alert('Transaksi pajak berhasil dihapus!');
          router.push('/accounting/tax-transactions');
        } catch (error) {
          console.error('Failed to delete tax transaction:', error);
          alert('Gagal menghapus transaksi pajak. Silakan coba lagi nanti.');
        }
      };

      const printDetail = () => {
        window.print();
      };

      onMounted(() => {
        loadTaxTransaction();
      });

      return {
        taxTransaction,
        taxAccount,
        relatedJournal,
        documents,
        loading,
        isOverdue,
        totalDebit,
        totalCredit,
        showConfirmationModal,
        confirmationModal,
        formatDate,
        formatDateTime,
        formatMonthYear,
        formatCurrency,
        formatFileSize,
        getTaxTypeBadgeClass,
        getStatusBadgeClass,
        confirmFileTax,
        confirmPayTax,
        confirmDeleteTax,
        generateJournal,
        printDetail
      };
    }
  };
  </script>
  <style scoped>
  .tax-transaction-detail {
    margin-bottom: 2rem;
  }

  .badge {
    padding: 0.375rem 0.75rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
  }

  .badge-primary {
    background-color: var(--primary-bg);
    color: var(--primary-color);
  }

  .badge-info {
    background-color: #e0f7fa;
    color: #0288d1;
  }

  .badge-success {
    background-color: var(--success-bg);
    color: var(--success-color);
  }

  .badge-warning {
    background-color: var(--warning-bg);
    color: var(--warning-color);
  }

  .badge-danger {
    background-color: var(--danger-bg);
    color: var(--danger-color);
  }

  .badge-secondary {
    background-color: var(--gray-100);
    color: var(--gray-700);
  }

  .badge-dark {
    background-color: #e0e0e0;
    color: #424242;
  }

  .section-title {
    font-size: 1rem;
    font-weight: 500;
    color: var(--gray-700);
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid var(--gray-200);
  }

  .info-table {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }

  .info-row {
    display: flex;
    align-items: flex-start;
  }

  .info-label {
    width: 40%;
    min-width: 120px;
    color: var(--gray-600);
    font-size: 0.875rem;
  }

  .info-value {
    flex-grow: 1;
    font-weight: 500;
  }

  .description-box {
    padding: 1rem;
    background-color: var(--gray-50);
    border-radius: 0.375rem;
    border: 1px solid var(--gray-200);
    font-size: 0.875rem;
    white-space: pre-line;
  }

  .timeline {
    position: relative;
    margin-left: 1.25rem;
    padding-left: 1.5rem;
    border-left: 2px solid var(--gray-200);
  }

  .timeline-item {
    position: relative;
    margin-bottom: 1.5rem;
  }

  .timeline-item:last-child {
    margin-bottom: 0;
  }

  .timeline-point {
    position: absolute;
    left: -1.7rem;
    width: 1rem;
    height: 1rem;
    border-radius: 50%;
    background-color: var(--primary-color);
    border: 2px solid white;
  }

  .timeline-point.success {
    background-color: var(--success-color);
  }

  .timeline-point.warning {
    background-color: var(--warning-color);
  }

  .timeline-point.info {
    background-color: #0288d1;
  }

  .timeline-content h6 {
    font-weight: 600;
    margin-bottom: 0.25rem;
  }

  .timeline-content p {
    font-size: 0.813rem;
    color: var(--gray-500);
    margin: 0;
  }

  .documents-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }

  .document-item {
    display: flex;
    align-items: center;
    padding: 1rem;
    background-color: var(--gray-50);
    border-radius: 0.375rem;
    border: 1px solid var(--gray-200);
  }

  .document-icon {
    font-size: 1.5rem;
    color: #e53935;
    margin-right: 1rem;
  }

  .document-info {
    flex-grow: 1;
  }

  .document-info h6 {
    margin-bottom: 0.25rem;
  }

  .document-actions {
    display: flex;
    gap: 0.5rem;
  }

  .data-table {
    width: 100%;
    border-collapse: collapse;
  }

  .data-table th,
  .data-table td {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--gray-200);
  }

  .data-table th {
    background-color: var(--gray-50);
    font-weight: 500;
    color: var(--gray-600);
    text-align: left;
  }

  .data-table tr:last-child td {
    border-bottom: none;
  }

  .data-table tfoot tr td {
    background-color: var(--gray-50);
    font-weight: 500;
  }

  .text-right {
    text-align: right;
  }

  .font-weight-bold {
    font-weight: 600;
  }

  .mr-1 {
    margin-right: 0.25rem;
  }

  .mr-2 {
    margin-right: 0.5rem;
  }

  .ml-2 {
    margin-left: 0.5rem;
  }

  .mt-2 {
    margin-top: 0.5rem;
  }

  .mt-3 {
    margin-top: 1rem;
  }

  .mt-4 {
    margin-top: 1.5rem;
  }

  .mb-1 {
    margin-bottom: 0.25rem;
  }

  .mb-3 {
    margin-bottom: 1rem;
  }

  .mb-4 {
    margin-bottom: 1.5rem;
  }

  .py-3 {
    padding-top: 0.75rem;
    padding-bottom: 0.75rem;
  }

  .py-5 {
    padding-top: 1.5rem;
    padding-bottom: 1.5rem;
  }

  .text-muted {
    color: var(--gray-500);
  }

  .text-danger {
    color: var(--danger-color);
  }

  .gap-4 {
    gap: 1rem;
  }

  /* Responsive styling */
  @media print {
    .header-actions, .document-actions {
      display: none;
    }

    .card {
      border: none;
      box-shadow: none;
    }
  }

  @media (max-width: 768px) {
    .header-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;
    }

    .tax-header {
      flex-direction: column;
      gap: 1rem;
    }

    .info-row {
      flex-direction: column;
    }

    .info-label {
      width: 100%;
      margin-bottom: 0.25rem;
    }
  }
  </style>
