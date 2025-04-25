<!-- src/views/accounting/receivables/ReceivableDetail.vue -->
<template>
    <div class="receivable-detail">
      <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
          <h2>Detail Piutang</h2>
          <div class="action-buttons">
            <router-link to="/accounting/receivables" class="btn btn-outline-secondary mr-2">
              <i class="fas fa-arrow-left mr-1"></i> Kembali
            </router-link>
            <button v-if="canAddPayment" class="btn btn-success mr-2" @click="showPaymentModal = true">
              <i class="fas fa-money-bill-wave mr-1"></i> Tambah Pembayaran
            </button>
            <router-link
              v-if="receivable && receivable.status !== 'Paid'"
              :to="`/accounting/receivables/${receivableId}/edit`"
              class="btn btn-primary"
            >
              <i class="fas fa-edit mr-1"></i> Edit
            </router-link>
          </div>
        </div>
      </div>

      <div v-if="isLoading" class="text-center py-5">
        <i class="fas fa-spinner fa-spin fa-2x"></i>
        <p class="mt-2">Memuat data piutang...</p>
      </div>

      <div v-else-if="error" class="alert alert-danger" role="alert">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        {{ error }}
      </div>

      <div v-else-if="!receivable" class="alert alert-warning" role="alert">
        <i class="fas fa-exclamation-circle mr-2"></i>
        Data piutang tidak ditemukan
      </div>

      <div v-else>
        <!-- Success Alert -->
        <div v-if="successMessage" class="alert alert-success alert-dismissible fade show" role="alert">
          <i class="fas fa-check-circle mr-2"></i>
          {{ successMessage }}
          <button type="button" class="close" @click="successMessage = ''">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <div class="row">
          <!-- Receivable Info Card -->
          <div class="col-md-6 mb-4">
            <div class="card h-100">
              <div class="card-header">
                <h3 class="card-title">Informasi Piutang</h3>
              </div>
              <div class="card-body">
                <table class="table table-details">
                  <tbody>
                    <tr>
                      <th>No. Invoice</th>
                      <td>
                        <span class="font-weight-bold">{{ invoice.invoice_number || 'N/A' }}</span>
                      </td>
                    </tr>
                    <tr>
                      <th>Pelanggan</th>
                      <td>{{ receivable.customer?.name || 'N/A' }}</td>
                    </tr>
                    <tr>
                      <th>Tanggal Invoice</th>
                      <td>{{ formatDate(invoice.invoice_date) }}</td>
                    </tr>
                    <tr>
                      <th>Tanggal Jatuh Tempo</th>
                      <td :class="{ 'text-danger': isOverdue(receivable.due_date) }">
                        {{ formatDate(receivable.due_date) }}
                        <span v-if="isOverdue(receivable.due_date)" class="badge badge-danger ml-2">
                          Jatuh Tempo
                        </span>
                      </td>
                    </tr>
                    <tr>
                      <th>Status</th>
                      <td>
                        <span
                          :class="{
                            'badge': true,
                            'badge-success': receivable.status === 'Paid',
                            'badge-warning': receivable.status === 'Open' && !isOverdue(receivable.due_date),
                            'badge-danger': receivable.status === 'Open' && isOverdue(receivable.due_date)
                          }"
                        >
                          {{ getStatusLabel(receivable.status, receivable.due_date) }}
                        </span>
                      </td>
                    </tr>
                    <tr>
                      <th>Lama Tunggakan</th>
                      <td>
                        {{ getDaysOverdue(receivable.due_date) }}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- Amount Summary Card -->
          <div class="col-md-6 mb-4">
            <div class="card h-100">
              <div class="card-header">
                <h3 class="card-title">Ringkasan Pembayaran</h3>
              </div>
              <div class="card-body">
                <div class="amount-summary">
                  <div class="amount-item">
                    <div class="amount-label">Total Piutang</div>
                    <div class="amount-value">{{ formatCurrency(receivable.amount) }}</div>
                  </div>

                  <div class="amount-item">
                    <div class="amount-label">Terbayar</div>
                    <div class="amount-value text-success">{{ formatCurrency(receivable.paid_amount) }}</div>
                  </div>

                  <div class="amount-divider"></div>

                  <div class="amount-item total">
                    <div class="amount-label">Sisa Piutang</div>
                    <div class="amount-value">{{ formatCurrency(receivable.balance) }}</div>
                  </div>

                  <div class="progress-container mt-3">
                    <div class="progress-label">
                      Progress Pembayaran:
                      <span class="font-weight-bold">{{ getPaymentPercentage() }}%</span>
                    </div>
                    <div class="progress">
                      <div
                        class="progress-bar bg-success"
                        role="progressbar"
                        :style="{ width: getPaymentPercentage() + '%' }"
                        :aria-valuenow="getPaymentPercentage()"
                        aria-valuemin="0"
                        aria-valuemax="100"
                      ></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Payment History Card -->
        <div class="card mb-4">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">Riwayat Pembayaran</h3>
            <button
              v-if="canAddPayment"
              class="btn btn-sm btn-success"
              @click="showPaymentModal = true"
            >
              <i class="fas fa-plus mr-1"></i> Tambah Pembayaran
            </button>
          </div>
          <div class="card-body">
            <div v-if="receivable.receivable_payments && receivable.receivable_payments.length === 0" class="alert alert-info">
              <i class="fas fa-info-circle mr-2"></i>
              Belum ada pembayaran untuk piutang ini
            </div>
            <div v-else class="table-responsive">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Tanggal Pembayaran</th>
                    <th>Metode Pembayaran</th>
                    <th>No. Referensi</th>
                    <th>Jumlah</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="payment in receivable.receivable_payments" :key="payment.payment_id">
                    <td>{{ formatDate(payment.payment_date) }}</td>
                    <td>{{ payment.payment_method }}</td>
                    <td>{{ payment.reference_number }}</td>
                    <td class="text-right">{{ formatCurrency(payment.amount) }}</td>
                    <td>
                      <button
                        class="btn btn-sm btn-danger"
                        @click="confirmDeletePayment(payment)"
                        :disabled="disableDeletePayment"
                      >
                        <i class="fas fa-trash"></i>
                      </button>
                    </td>
                  </tr>
                </tbody>
                <tfoot>
                  <tr class="font-weight-bold">
                    <td colspan="3" class="text-right">Total:</td>
                    <td class="text-right">{{ formatCurrency(receivable.paid_amount) }}</td>
                    <td></td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
        </div>

        <!-- Invoice Details Card -->
        <div class="card mb-4" v-if="invoice && invoice.invoice_items">
          <div class="card-header">
            <h3 class="card-title">Detail Faktur</h3>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Produk</th>
                    <th>Deskripsi</th>
                    <th class="text-right">Jumlah</th>
                    <th class="text-right">Harga Satuan</th>
                    <th class="text-right">Subtotal</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(item, index) in invoice.invoice_items" :key="index">
                    <td>{{ item.product_name || item.item_name || 'N/A' }}</td>
                    <td>{{ item.description || '-' }}</td>
                    <td class="text-right">{{ item.quantity }}</td>
                    <td class="text-right">{{ formatCurrency(item.unit_price) }}</td>
                    <td class="text-right">{{ formatCurrency(item.subtotal || (item.quantity * item.unit_price)) }}</td>
                  </tr>
                </tbody>
                <tfoot>
                  <tr>
                    <td colspan="4" class="text-right font-weight-bold">Subtotal:</td>
                    <td class="text-right">{{ formatCurrency(invoice.subtotal) }}</td>
                  </tr>
                  <tr v-if="invoice.tax_amount">
                    <td colspan="4" class="text-right font-weight-bold">Pajak:</td>
                    <td class="text-right">{{ formatCurrency(invoice.tax_amount) }}</td>
                  </tr>
                  <tr v-if="invoice.discount_amount">
                    <td colspan="4" class="text-right font-weight-bold">Diskon:</td>
                    <td class="text-right">{{ formatCurrency(invoice.discount_amount) }}</td>
                  </tr>
                  <tr class="font-weight-bold">
                    <td colspan="4" class="text-right">Total:</td>
                    <td class="text-right">{{ formatCurrency(invoice.total_amount) }}</td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- Add Payment Modal -->
      <ConfirmationModal
        v-if="showPaymentModal"
        title="Tambah Pembayaran"
        confirm-button-text="Simpan Pembayaran"
        confirm-button-class="btn btn-success"
        @confirm="savePayment"
        @close="showPaymentModal = false"
      >
        <template #default>
          <form class="payment-form">
            <div class="form-group">
              <label for="paymentDate">Tanggal Pembayaran <span class="text-danger">*</span></label>
              <input
                type="date"
                id="paymentDate"
                v-model="paymentForm.payment_date"
                class="form-control"
                :class="{ 'is-invalid': paymentValidationErrors.payment_date }"
                required
              />
              <div v-if="paymentValidationErrors.payment_date" class="invalid-feedback">
                {{ paymentValidationErrors.payment_date[0] }}
              </div>
            </div>

            <div class="form-group">
              <label for="paymentAmount">Jumlah Pembayaran <span class="text-danger">*</span></label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text">Rp</span>
                </div>
                <input
                  type="number"
                  id="paymentAmount"
                  v-model="paymentForm.amount"
                  class="form-control"
                  :class="{ 'is-invalid': paymentValidationErrors.amount }"
                  placeholder="Jumlah pembayaran"
                  required
                  min="0.01"
                  :max="receivable?.balance || 0"
                  step="0.01"
                />
              </div>
              <div v-if="paymentValidationErrors.amount" class="invalid-feedback">
                {{ paymentValidationErrors.amount[0] }}
              </div>
              <small class="form-text text-muted">
                Sisa piutang: {{ formatCurrency(receivable?.balance || 0) }}
              </small>
              <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" id="payFullAmount" v-model="payFullAmount">
                <label class="form-check-label" for="payFullAmount">
                  Bayar jumlah penuh
                </label>
              </div>
            </div>

            <div class="form-group">
              <label for="paymentMethod">Metode Pembayaran <span class="text-danger">*</span></label>
              <select
                id="paymentMethod"
                v-model="paymentForm.payment_method"
                class="form-control"
                :class="{ 'is-invalid': paymentValidationErrors.payment_method }"
                required
              >
                <option value="">Pilih Metode Pembayaran</option>
                <option value="Cash">Tunai</option>
                <option value="Bank Transfer">Transfer Bank</option>
                <option value="Credit Card">Kartu Kredit</option>
                <option value="Debit Card">Kartu Debit</option>
                <option value="Check">Cek</option>
                <option value="Other">Lainnya</option>
              </select>
              <div v-if="paymentValidationErrors.payment_method" class="invalid-feedback">
                {{ paymentValidationErrors.payment_method[0] }}
              </div>
            </div>

            <div class="form-group">
              <label for="referenceNumber">Nomor Referensi <span class="text-danger">*</span></label>
              <input
                type="text"
                id="referenceNumber"
                v-model="paymentForm.reference_number"
                class="form-control"
                :class="{ 'is-invalid': paymentValidationErrors.reference_number }"
                placeholder="Nomor referensi pembayaran"
                required
              />
              <div v-if="paymentValidationErrors.reference_number" class="invalid-feedback">
                {{ paymentValidationErrors.reference_number[0] }}
              </div>
            </div>

            <div class="form-group">
              <div class="custom-control custom-checkbox">
                <input
                  type="checkbox"
                  class="custom-control-input"
                  id="createJournalEntry"
                  v-model="paymentForm.create_journal_entry"
                />
                <label class="custom-control-label" for="createJournalEntry">
                  Buat jurnal akuntansi otomatis
                </label>
              </div>
              <small class="form-text text-muted">
                Sistem akan membuat jurnal akuntansi untuk mencatat transaksi pembayaran ini
              </small>
            </div>

            <div v-if="paymentForm.create_journal_entry" class="account-selection">
              <div class="form-group">
                <label for="cashAccountId">Akun Kas/Bank <span class="text-danger">*</span></label>
                <select
                  id="cashAccountId"
                  v-model="paymentForm.cash_account_id"
                  class="form-control"
                  :class="{ 'is-invalid': paymentValidationErrors.cash_account_id }"
                  required
                >
                  <option value="">Pilih Akun Kas/Bank</option>
                  <option v-for="account in cashAccounts" :key="account.account_id" :value="account.account_id">
                    {{ account.account_code }} - {{ account.name }}
                  </option>
                </select>
                <div v-if="paymentValidationErrors.cash_account_id" class="invalid-feedback">
                  {{ paymentValidationErrors.cash_account_id[0] }}
                </div>
              </div>

              <div class="form-group">
                <label for="receivableAccountId">Akun Piutang <span class="text-danger">*</span></label>
                <select
                  id="receivableAccountId"
                  v-model="paymentForm.receivable_account_id"
                  class="form-control"
                  :class="{ 'is-invalid': paymentValidationErrors.receivable_account_id }"
                  required
                >
                  <option value="">Pilih Akun Piutang</option>
                  <option v-for="account in receivableAccounts" :key="account.account_id" :value="account.account_id">
                    {{ account.account_code }} - {{ account.name }}
                  </option>
                </select>
                <div v-if="paymentValidationErrors.receivable_account_id" class="invalid-feedback">
                  {{ paymentValidationErrors.receivable_account_id[0] }}
                </div>
              </div>
            </div>
          </form>
        </template>
      </ConfirmationModal>

      <!-- Delete Payment Confirmation Modal -->
      <ConfirmationModal
        v-if="showDeletePaymentModal"
        title="Hapus Pembayaran"
        :message="'Apakah Anda yakin ingin menghapus pembayaran sebesar ' +
                  (selectedPayment ? formatCurrency(selectedPayment.amount) : '') +
                  ' tanggal ' +
                  (selectedPayment ? formatDate(selectedPayment.payment_date) : '') +
                  '? Tindakan ini tidak dapat dibatalkan.'"
        confirm-button-text="Hapus"
        confirm-button-class="btn btn-danger"
        @confirm="deletePayment"
        @close="showDeletePaymentModal = false"
      />
    </div>
  </template>

  <script>
  import { ref, computed, reactive, onMounted, watch } from 'vue';
  import { useRoute } from 'vue-router';
  import axios from 'axios';

  export default {
    name: 'ReceivableDetail',
    setup() {
      const route = useRoute();
      //const router = useRouter();
      const receivableId = computed(() => route.params.id);

      // State
      const receivable = ref(null);
      const invoice = ref({});
      const isLoading = ref(true);
      const error = ref(null);
      const successMessage = ref('');
      const disableDeletePayment = ref(false);
      const cashAccounts = ref([]);
      const receivableAccounts = ref([]);

      // Payment modal state
      const showPaymentModal = ref(false);
      const showDeletePaymentModal = ref(false);
      const selectedPayment = ref(null);
      const payFullAmount = ref(false);
      const paymentForm = reactive({
        receivable_id: receivableId.value,
        payment_date: new Date().toISOString().split('T')[0],
        amount: '',
        payment_method: '',
        reference_number: '',
        create_journal_entry: true,
        cash_account_id: '',
        receivable_account_id: ''
      });
      const paymentValidationErrors = ref({});

      // Computed properties
      const canAddPayment = computed(() => {
        return receivable.value &&
               receivable.value.status !== 'Paid' &&
               receivable.value.balance > 0;
      });

      // Watch for changes to payFullAmount
      watch(payFullAmount, (newValue) => {
        if (newValue && receivable.value) {
          paymentForm.amount = receivable.value.balance;
        }
      });

      // Load receivable data
      const loadReceivable = async () => {
        isLoading.value = true;
        error.value = null;

        try {
          const response = await axios.get(`/api/accounting/customer-receivables/${receivableId.value}`);
          receivable.value = response.data.data || response.data;

          // Load invoice details if available
          if (receivable.value.invoice_id) {
            await loadInvoiceDetails(receivable.value.invoice_id);
          }

          // Load chart of accounts for payment form
          await loadChartOfAccounts();
        } catch (err) {
          console.error('Error loading receivable:', err);
          error.value = 'Gagal memuat data piutang';
        } finally {
          isLoading.value = false;
        }
      };

      // Load invoice details
      const loadInvoiceDetails = async (invoiceId) => {
        try {
          const response = await axios.get(`/api/sales/invoices/${invoiceId}`);
          invoice.value = response.data.data || response.data;
        } catch (err) {
          console.error('Error loading invoice details:', err);
        }
      };

      // Load chart of accounts for payment form
      const loadChartOfAccounts = async () => {
        try {
          const response = await axios.get('/api/accounting/chart-of-accounts');
          const accounts = response.data.data || response.data;

          // Filter for cash/bank accounts (Asset type)
          cashAccounts.value = accounts.filter(account =>
            account.account_type === 'Asset' && account.is_active
          );

          // Filter for receivable accounts (Asset type)
          receivableAccounts.value = accounts.filter(account =>
            account.account_type === 'Asset' &&
            account.is_active &&
            (account.name.toLowerCase().includes('receivable') ||
             account.name.toLowerCase().includes('piutang'))
          );

          // Set default accounts if available
          if (cashAccounts.value.length > 0) {
            paymentForm.cash_account_id = cashAccounts.value[0].account_id;
          }

          if (receivableAccounts.value.length > 0) {
            paymentForm.receivable_account_id = receivableAccounts.value[0].account_id;
          }
        } catch (err) {
          console.error('Error loading chart of accounts:', err);
        }
      };

      // Add payment
      const savePayment = async () => {
        paymentValidationErrors.value = {};

        try {
           await axios.post('/api/accounting/receivable-payments', {
            ...paymentForm,
            receivable_id: receivableId.value
          });

          // Close modal and reload data
          showPaymentModal.value = false;
          await loadReceivable();

          // Show success message
          successMessage.value = 'Pembayaran berhasil ditambahkan';

          // Reset form
          resetPaymentForm();
        } catch (err) {
          console.error('Error adding payment:', err);

          if (err.response && err.response.data && err.response.data.errors) {
            paymentValidationErrors.value = err.response.data.errors;
          } else {
            alert('Gagal menambahkan pembayaran: ' + (err.response?.data?.message || 'Terjadi kesalahan'));
          }
        }
      };

      // Confirm delete payment
      const confirmDeletePayment = (payment) => {
        selectedPayment.value = payment;
        showDeletePaymentModal.value = true;
      };

      // Delete payment
      const deletePayment = async () => {
        if (!selectedPayment.value) return;

        disableDeletePayment.value = true;

        try {
          await axios.delete(`/api/accounting/receivable-payments/${selectedPayment.value.payment_id}`);

          // Close modal and reload data
          showDeletePaymentModal.value = false;
          await loadReceivable();

          // Show success message
          successMessage.value = 'Pembayaran berhasil dihapus';
        } catch (err) {
          console.error('Error deleting payment:', err);
          alert('Gagal menghapus pembayaran: ' + (err.response?.data?.message || 'Terjadi kesalahan'));
        } finally {
          disableDeletePayment.value = false;
          selectedPayment.value = null;
        }
      };

      // Reset payment form
      const resetPaymentForm = () => {
        paymentForm.payment_date = new Date().toISOString().split('T')[0];
        paymentForm.amount = '';
        paymentForm.payment_method = '';
        paymentForm.reference_number = '';
        paymentForm.create_journal_entry = true;
        payFullAmount.value = false;
        paymentValidationErrors.value = {};
      };

      // Calculate payment percentage
      const getPaymentPercentage = () => {
        if (!receivable.value || !receivable.value.amount || receivable.value.amount === 0) {
          return 0;
        }

        const percentage = (receivable.value.paid_amount / receivable.value.amount) * 100;
        return Math.min(100, Math.round(percentage));
      };

      // Check if date is overdue
      const isOverdue = (dateString) => {
        if (!dateString) return false;
        const dueDate = new Date(dateString);
        const today = new Date();

        // Reset time part for accurate day comparison
        dueDate.setHours(0, 0, 0, 0);
        today.setHours(0, 0, 0, 0);

        return dueDate < today;
      };

      // Get days overdue
      const getDaysOverdue = (dateString) => {
        if (!dateString) return 'Tidak ada tanggal jatuh tempo';

        const dueDate = new Date(dateString);
        const today = new Date();

        // Reset time part for accurate day comparison
        dueDate.setHours(0, 0, 0, 0);
        today.setHours(0, 0, 0, 0);

        if (dueDate >= today) {
          const diff = dueDate - today;
          const days = Math.ceil(diff / (1000 * 60 * 60 * 24));
          return `${days} hari lagi menuju jatuh tempo`;
        } else {
          const diff = today - dueDate;
          const days = Math.ceil(diff / (1000 * 60 * 60 * 24));
          return `Terlambat ${days} hari`;
        }
      };

      // Get status label
      const getStatusLabel = (status, dueDate) => {
        if (status === 'Paid') return 'Lunas';
        if (status === 'Open') {
          return isOverdue(dueDate) ? 'Jatuh Tempo' : 'Belum Lunas';
        }
        return status;
      };

      // Format date
      const formatDate = (dateString) => {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', {
          day: 'numeric',
          month: 'long',
          year: 'numeric'
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

      // Lifecycle hooks
      onMounted(() => {
        loadReceivable();
      });

      return {
        receivableId,
        receivable,
        invoice,
        isLoading,
        error,
        successMessage,
        showPaymentModal,
        showDeletePaymentModal,
        selectedPayment,
        paymentForm,
        paymentValidationErrors,
        payFullAmount,
        cashAccounts,
        receivableAccounts,
        canAddPayment,
        disableDeletePayment,
        savePayment,
        confirmDeletePayment,
        deletePayment,
        getPaymentPercentage,
        isOverdue,
        getDaysOverdue,
        getStatusLabel,
        formatDate,
        formatCurrency
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

  .amount-summary {
    padding: 1rem;
  }

  .amount-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 1rem;
    font-size: 1rem;
  }

  .amount-label {
    color: var(--gray-600);
  }

  .amount-value {
    font-weight: 600;
    color: var(--gray-800);
  }

  .amount-item.total {
    font-size: 1.25rem;
    font-weight: 700;
  }

  .amount-divider {
    height: 1px;
    background-color: var(--gray-200);
    margin: 1rem 0;
  }

  .progress-container {
    margin-top: 1.5rem;
  }

  .progress-label {
    margin-bottom: 0.5rem;
    color: var(--gray-600);
  }

  .progress {
    height: 0.75rem;
    border-radius: 0.5rem;
  }

  .payment-form .invalid-feedback {
    display: block;
  }

  .badge {
    padding: 0.5em 0.75em;
    font-size: 0.75em;
  }

  .account-selection {
    background-color: var(--gray-50);
    padding: 1rem;
    border-radius: 0.5rem;
    margin-top: 1rem;
  }
  </style>
