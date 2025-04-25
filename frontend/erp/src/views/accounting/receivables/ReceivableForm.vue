<!-- src/views/accounting/receivables/ReceivableForm.vue -->
<template>
    <div class="receivable-form">
      <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
          <h2>{{ isEditing ? 'Edit Piutang' : 'Buat Piutang Baru' }}</h2>
          <router-link to="/accounting/receivables" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Kembali
          </router-link>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <div v-if="isLoading" class="text-center py-5">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p class="mt-2">Memuat data...</p>
          </div>

          <div v-else-if="error" class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            {{ error }}
          </div>

          <form v-else @submit.prevent="saveReceivable" class="form">
            <div class="form-row">
              <div class="form-group col-md-6">
                <label for="customerId">Pelanggan <span class="text-danger">*</span></label>
                <select
                  id="customerId"
                  v-model="form.customer_id"
                  class="form-control"
                  :class="{ 'is-invalid': validationErrors.customer_id }"
                  required
                  :disabled="isEditing"
                  @change="fetchCustomerInvoices"
                >
                  <option value="">Pilih Pelanggan</option>
                  <option v-for="customer in customers" :key="customer.customer_id" :value="customer.customer_id">
                    {{ customer.name }}
                  </option>
                </select>
                <div v-if="validationErrors.customer_id" class="invalid-feedback">
                  {{ validationErrors.customer_id[0] }}
                </div>
              </div>

              <div class="form-group col-md-6">
                <label for="invoiceId">Faktur <span class="text-danger">*</span></label>
                <select
                  id="invoiceId"
                  v-model="form.invoice_id"
                  class="form-control"
                  :class="{ 'is-invalid': validationErrors.invoice_id }"
                  required
                  :disabled="isEditing || !form.customer_id"
                  @change="setInvoiceDetails"
                >
                  <option value="">Pilih Faktur</option>
                  <option v-for="invoice in invoices" :key="invoice.invoice_id" :value="invoice.invoice_id">
                    {{ invoice.invoice_number }} - {{ formatCurrency(invoice.total_amount) }}
                  </option>
                </select>
                <div v-if="validationErrors.invoice_id" class="invalid-feedback">
                  {{ validationErrors.invoice_id[0] }}
                </div>
                <small v-if="!form.customer_id" class="form-text text-muted">
                  Pilih pelanggan terlebih dahulu untuk melihat faktur
                </small>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group col-md-4">
                <label for="amount">Jumlah <span class="text-danger">*</span></label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text">Rp</span>
                  </div>
                  <input
                    type="number"
                    id="amount"
                    v-model="form.amount"
                    class="form-control"
                    :class="{ 'is-invalid': validationErrors.amount }"
                    placeholder="Jumlah piutang"
                    required
                    min="0"
                    step="0.01"
                    :readonly="isEditing || autoFillAmount"
                  />
                </div>
                <div v-if="validationErrors.amount" class="invalid-feedback">
                  {{ validationErrors.amount[0] }}
                </div>
                <div class="form-check mt-2" v-if="!isEditing">
                  <input class="form-check-input" type="checkbox" id="autoFillAmount" v-model="autoFillAmount">
                  <label class="form-check-label" for="autoFillAmount">
                    Gunakan jumlah dari faktur
                  </label>
                </div>
              </div>

              <div class="form-group col-md-4">
                <label for="dueDate">Tanggal Jatuh Tempo <span class="text-danger">*</span></label>
                <input
                  type="date"
                  id="dueDate"
                  v-model="form.due_date"
                  class="form-control"
                  :class="{ 'is-invalid': validationErrors.due_date }"
                  required
                />
                <div v-if="validationErrors.due_date" class="invalid-feedback">
                  {{ validationErrors.due_date[0] }}
                </div>
              </div>

              <div class="form-group col-md-4">
                <label for="status">Status <span class="text-danger">*</span></label>
                <select
                  id="status"
                  v-model="form.status"
                  class="form-control"
                  :class="{ 'is-invalid': validationErrors.status }"
                  required
                >
                  <option value="Open">Belum Lunas</option>
                  <option value="Paid">Lunas</option>
                </select>
                <div v-if="validationErrors.status" class="invalid-feedback">
                  {{ validationErrors.status[0] }}
                </div>
              </div>
            </div>

            <div class="form-row" v-if="isEditing">
              <div class="form-group col-md-6">
                <label for="paidAmount">Jumlah Terbayar</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text">Rp</span>
                  </div>
                  <input
                    type="number"
                    id="paidAmount"
                    v-model="form.paid_amount"
                    class="form-control"
                    placeholder="Jumlah yang sudah dibayar"
                    min="0"
                    step="0.01"
                    readonly
                  />
                </div>
                <small class="form-text text-muted">
                  Nilai ini diperbarui otomatis saat pembayaran dilakukan
                </small>
              </div>

              <div class="form-group col-md-6">
                <label for="balance">Sisa Piutang</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text">Rp</span>
                  </div>
                  <input
                    type="number"
                    id="balance"
                    v-model="form.balance"
                    class="form-control"
                    placeholder="Sisa piutang"
                    min="0"
                    step="0.01"
                    readonly
                  />
                </div>
                <small class="form-text text-muted">
                  Nilai ini diperbarui otomatis berdasarkan jumlah terbayar
                </small>
              </div>
            </div>

            <div class="form-group" v-if="isEditing">
              <label>Riwayat Pembayaran</label>
              <div v-if="payments.length === 0" class="alert alert-info">
                Belum ada pembayaran untuk piutang ini
              </div>
              <div v-else class="table-responsive">
                <table class="table table-sm table-bordered">
                  <thead class="thead-light">
                    <tr>
                      <th>Tanggal</th>
                      <th>Metode Pembayaran</th>
                      <th>No. Referensi</th>
                      <th>Jumlah</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="payment in payments" :key="payment.payment_id">
                      <td>{{ formatDate(payment.payment_date) }}</td>
                      <td>{{ payment.payment_method }}</td>
                      <td>{{ payment.reference_number }}</td>
                      <td class="text-right">{{ formatCurrency(payment.amount) }}</td>
                    </tr>
                  </tbody>
                  <tfoot>
                    <tr class="font-weight-bold">
                      <td colspan="3" class="text-right">Total:</td>
                      <td class="text-right">{{ formatCurrency(totalPayment) }}</td>
                    </tr>
                  </tfoot>
                </table>
              </div>
            </div>

            <hr>

            <div class="form-actions mt-4">
              <button type="submit" class="btn btn-primary" :disabled="isSaving">
                <i v-if="isSaving" class="fas fa-spinner fa-spin mr-1"></i>
                <i v-else class="fas fa-save mr-1"></i>
                {{ isEditing ? 'Update Piutang' : 'Simpan Piutang' }}
              </button>
              <router-link to="/accounting/receivables" class="btn btn-secondary ml-2">
                Batal
              </router-link>
            </div>
          </form>
        </div>
      </div>
    </div>
  </template>

  <script>
  import { ref, computed, reactive, onMounted, watch } from 'vue';
  import { useRoute, useRouter } from 'vue-router';
  import axios from 'axios';

  export default {
    name: 'ReceivableForm',
    setup() {
      const route = useRoute();
      const router = useRouter();
      const receivableId = computed(() => route.params.id);
      const isEditing = computed(() => !!receivableId.value);

      // Form data
      const form = reactive({
        customer_id: '',
        invoice_id: '',
        amount: '',
        due_date: '',
        paid_amount: 0,
        balance: 0,
        status: 'Open'
      });

      // State variables
      const customers = ref([]);
      const invoices = ref([]);
      const payments = ref([]);
      const isLoading = ref(true);
      const isSaving = ref(false);
      const error = ref(null);
      const validationErrors = ref({});
      const autoFillAmount = ref(true);

      // Computed
      const totalPayment = computed(() => {
        return payments.value.reduce((sum, payment) => sum + parseFloat(payment.amount || 0), 0);
      });

      // Load data on mount
      onMounted(() => {
        loadCustomers();
        if (isEditing.value) {
          loadReceivable();
        } else {
          isLoading.value = false;
        }
      });

      // Watch for changes in amount and calculate balance
      watch(() => form.amount, (newAmount) => {
        if (isEditing.value) {
          form.balance = Math.max(0, parseFloat(newAmount || 0) - parseFloat(form.paid_amount || 0));
        }
      });

      // Load customers list
      const loadCustomers = async () => {
        try {
          const response = await axios.get('/api/sales/customers');
          customers.value = response.data.data || response.data;
        } catch (err) {
          console.error('Error loading customers:', err);
          error.value = 'Gagal memuat data pelanggan';
        }
      };

      // Load receivable data when editing
      const loadReceivable = async () => {
        isLoading.value = true;
        error.value = null;

        try {
          const response = await axios.get(`/api/accounting/customer-receivables/${receivableId.value}`);
          const receivable = response.data.data || response.data;

          // Populate form
          form.customer_id = receivable.customer_id;
          form.invoice_id = receivable.invoice_id;
          form.amount = receivable.amount;
          form.due_date = formatDateForInput(receivable.due_date);
          form.paid_amount = receivable.paid_amount;
          form.balance = receivable.balance;
          form.status = receivable.status;

          // Load payment history
          payments.value = receivable.receivable_payments || [];

          // Load invoices for this customer
          await fetchCustomerInvoices();
        } catch (err) {
          console.error('Error loading receivable:', err);
          error.value = 'Gagal memuat data piutang';
        } finally {
          isLoading.value = false;
        }
      };

      // Fetch invoices for selected customer
      const fetchCustomerInvoices = async () => {
        if (!form.customer_id) {
          invoices.value = [];
          return;
        }

        try {
          const response = await axios.get(`/api/sales/customers/${form.customer_id}/invoices`);
          invoices.value = response.data.data || response.data;
        } catch (err) {
          console.error('Error loading invoices:', err);
          error.value = 'Gagal memuat data faktur';
        }
      };

      // Set invoice details when invoice is selected
      const setInvoiceDetails = () => {
        if (autoFillAmount.value && form.invoice_id) {
          const selectedInvoice = invoices.value.find(inv => inv.invoice_id == form.invoice_id);
          if (selectedInvoice) {
            form.amount = selectedInvoice.total_amount;
            form.balance = selectedInvoice.total_amount;

            // Set due date to invoice date + 30 days if not already set
            if (!form.due_date && selectedInvoice.invoice_date) {
              const invoiceDate = new Date(selectedInvoice.invoice_date);
              invoiceDate.setDate(invoiceDate.getDate() + 30);
              form.due_date = formatDateForInput(invoiceDate);
            }
          }
        }
      };

      // Save the receivable
      const saveReceivable = async () => {
        isSaving.value = true;
        error.value = null;
        validationErrors.value = {};

        try {
          //let response;

          if (isEditing.value) {
             await axios.put(`/api/accounting/customer-receivables/${receivableId.value}`, form);
          } else {
             await axios.post('/api/accounting/customer-receivables', form);
          }

          router.push({
            path: '/accounting/receivables',
            query: { success: isEditing.value ? 'updated' : 'created' }
          });
        } catch (err) {
          console.error('Error saving receivable:', err);

          if (err.response && err.response.data && err.response.data.errors) {
            validationErrors.value = err.response.data.errors;
          } else {
            error.value = 'Gagal menyimpan data piutang';
          }
        } finally {
          isSaving.value = false;
        }
      };

      // Utility functions
      const formatDateForInput = (dateString) => {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toISOString().split('T')[0];
      };

      const formatDate = (dateString) => {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', {
          day: 'numeric',
          month: 'long',
          year: 'numeric'
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

      return {
        isEditing,
        form,
        customers,
        invoices,
        payments,
        isLoading,
        isSaving,
        error,
        validationErrors,
        autoFillAmount,
        totalPayment,
        fetchCustomerInvoices,
        setInvoiceDetails,
        saveReceivable,
        formatDate,
        formatCurrency
      };
    }
  };
  </script>

  <style scoped>
  .form {
    max-width: 100%;
  }

  .form-actions {
    padding-top: 1rem;
  }

  .invalid-feedback {
    display: block;
  }
  </style>
