<!-- src/views/accounting/TaxTransactionForm.vue -->
<template>
    <div class="tax-transaction-form">
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">{{ isEditing ? 'Edit Transaksi Pajak' : 'Tambah Transaksi Pajak Baru' }}</h3>
        </div>
        <div class="card-body">
          <form @submit.prevent="saveTaxTransaction">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <!-- Basic Information Section -->
              <div class="form-group">
                <label for="tax_number">Nomor Pajak <span class="text-danger">*</span></label>
                <input
                  type="text"
                  id="tax_number"
                  v-model="taxTransaction.tax_number"
                  class="form-control"
                  :class="{ 'is-invalid': errors.tax_number }"
                  placeholder="Masukkan nomor pajak"
                  required
                />
                <div v-if="errors.tax_number" class="text-danger">{{ errors.tax_number }}</div>
              </div>

              <div class="form-group">
                <label for="tax_type">Jenis Pajak <span class="text-danger">*</span></label>
                <select
                  id="tax_type"
                  v-model="taxTransaction.tax_type"
                  class="form-control"
                  :class="{ 'is-invalid': errors.tax_type }"
                  required
                >
                  <option value="">Pilih Jenis Pajak</option>
                  <option value="PPN">PPN</option>
                  <option value="PPH21">PPH21</option>
                  <option value="PPH23">PPH23</option>
                  <option value="PPH4(2)">PPH4(2)</option>
                  <option value="Other">Lainnya</option>
                </select>
                <div v-if="errors.tax_type" class="text-danger">{{ errors.tax_type }}</div>
              </div>

              <div class="form-group">
                <label for="transaction_date">Tanggal Transaksi <span class="text-danger">*</span></label>
                <input
                  type="date"
                  id="transaction_date"
                  v-model="taxTransaction.transaction_date"
                  class="form-control"
                  :class="{ 'is-invalid': errors.transaction_date }"
                  required
                />
                <div v-if="errors.transaction_date" class="text-danger">{{ errors.transaction_date }}</div>
              </div>

              <div class="form-group">
                <label for="tax_period">Periode Pajak <span class="text-danger">*</span></label>
                <input
                  type="month"
                  id="tax_period"
                  v-model="taxTransaction.tax_period"
                  class="form-control"
                  :class="{ 'is-invalid': errors.tax_period }"
                  required
                />
                <div v-if="errors.tax_period" class="text-danger">{{ errors.tax_period }}</div>
              </div>

              <div class="form-group">
                <label for="due_date">Tanggal Jatuh Tempo <span class="text-danger">*</span></label>
                <input
                  type="date"
                  id="due_date"
                  v-model="taxTransaction.due_date"
                  class="form-control"
                  :class="{ 'is-invalid': errors.due_date }"
                  required
                />
                <div v-if="errors.due_date" class="text-danger">{{ errors.due_date }}</div>
              </div>

              <div class="form-group">
                <label for="reference_type">Tipe Referensi</label>
                <select
                  id="reference_type"
                  v-model="taxTransaction.reference_type"
                  class="form-control"
                  :class="{ 'is-invalid': errors.reference_type }"
                >
                  <option value="">Pilih Tipe Referensi</option>
                  <option value="Invoice">Invoice</option>
                  <option value="Purchase">Purchase</option>
                  <option value="Payroll">Payroll</option>
                  <option value="Other">Lainnya</option>
                </select>
                <div v-if="errors.reference_type" class="text-danger">{{ errors.reference_type }}</div>
              </div>

              <div class="form-group">
                <label for="reference_id">ID Referensi</label>
                <input
                  type="text"
                  id="reference_id"
                  v-model="taxTransaction.reference_id"
                  class="form-control"
                  :class="{ 'is-invalid': errors.reference_id }"
                  placeholder="Masukkan ID referensi"
                />
                <div v-if="errors.reference_id" class="text-danger">{{ errors.reference_id }}</div>
              </div>

              <!-- Amount Information Section -->
              <div class="form-group">
                <label for="tax_amount">Jumlah Pajak <span class="text-danger">*</span></label>
                <div class="input-group">
                  <span class="input-group-text">Rp</span>
                  <input
                    type="number"
                    id="tax_amount"
                    v-model="taxTransaction.tax_amount"
                    class="form-control"
                    :class="{ 'is-invalid': errors.tax_amount }"
                    placeholder="0"
                    step="0.01"
                    min="0"
                    required
                  />
                </div>
                <div v-if="errors.tax_amount" class="text-danger">{{ errors.tax_amount }}</div>
              </div>

              <div class="form-group">
                <label for="taxable_amount">Jumlah Kena Pajak</label>
                <div class="input-group">
                  <span class="input-group-text">Rp</span>
                  <input
                    type="number"
                    id="taxable_amount"
                    v-model="taxTransaction.taxable_amount"
                    class="form-control"
                    :class="{ 'is-invalid': errors.taxable_amount }"
                    placeholder="0"
                    step="0.01"
                    min="0"
                  />
                </div>
                <div v-if="errors.taxable_amount" class="text-danger">{{ errors.taxable_amount }}</div>
              </div>

              <div class="form-group">
                <label for="tax_rate">Tarif Pajak (%)</label>
                <div class="input-group">
                  <input
                    type="number"
                    id="tax_rate"
                    v-model="taxTransaction.tax_rate"
                    class="form-control"
                    :class="{ 'is-invalid': errors.tax_rate }"
                    placeholder="0"
                    step="0.01"
                    min="0"
                  />
                  <span class="input-group-text">%</span>
                </div>
                <div v-if="errors.tax_rate" class="text-danger">{{ errors.tax_rate }}</div>
              </div>

              <div class="form-group">
                <label for="account_id">Akun Pajak <span class="text-danger">*</span></label>
                <select
                  id="account_id"
                  v-model="taxTransaction.account_id"
                  class="form-control"
                  :class="{ 'is-invalid': errors.account_id }"
                  required
                >
                  <option value="">Pilih Akun Pajak</option>
                  <option v-for="account in taxAccounts" :key="account.account_id" :value="account.account_id">
                    {{ account.account_code }} - {{ account.account_name }}
                  </option>
                </select>
                <div v-if="errors.account_id" class="text-danger">{{ errors.account_id }}</div>
              </div>

              <!-- Additional Information -->
              <div class="form-group col-span-2">
                <label for="description">Deskripsi</label>
                <textarea
                  id="description"
                  v-model="taxTransaction.description"
                  class="form-control"
                  :class="{ 'is-invalid': errors.description }"
                  rows="3"
                  placeholder="Masukkan deskripsi transaksi pajak"
                ></textarea>
                <div v-if="errors.description" class="text-danger">{{ errors.description }}</div>
              </div>

              <!-- Status only for editing -->
              <div v-if="isEditing" class="form-group">
                <label for="status">Status</label>
                <select
                  id="status"
                  v-model="taxTransaction.status"
                  class="form-control"
                  :class="{ 'is-invalid': errors.status }"
                >
                  <option value="Pending">Pending</option>
                  <option value="Filed">Filed</option>
                  <option value="Paid">Paid</option>
                </select>
                <div v-if="errors.status" class="text-danger">{{ errors.status }}</div>
              </div>
            </div>

            <!-- Form actions -->
            <div class="form-actions mt-4 d-flex justify-content-between">
              <router-link to="/accounting/tax-transactions" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Kembali
              </router-link>
              <div>
                <button type="button" @click="resetForm" class="btn btn-warning mr-2">
                  <i class="fas fa-undo mr-1"></i> Reset
                </button>
                <button type="submit" class="btn btn-primary" :disabled="loading">
                  <i class="fas fa-save mr-1"></i> {{ isEditing ? 'Perbarui' : 'Simpan' }}
                  <span v-if="loading" class="spinner-border spinner-border-sm ml-1" role="status" aria-hidden="true"></span>
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </template>

  <script>
  import { ref, reactive, computed, onMounted } from 'vue';
  import { useRoute, useRouter } from 'vue-router';
  import axios from 'axios';

  export default {
    name: 'TaxTransactionForm',
    setup() {
      const route = useRoute();
      const router = useRouter();
      const taxId = route.params.id;
      const isEditing = computed(() => !!taxId);

      const taxTransaction = reactive({
        tax_number: '',
        tax_type: '',
        transaction_date: new Date().toISOString().split('T')[0], // Default to today
        tax_period: new Date().toISOString().slice(0, 7), // Default to current month
        due_date: '',
        reference_type: '',
        reference_id: '',
        tax_amount: '',
        taxable_amount: '',
        tax_rate: '',
        account_id: '',
        description: '',
        status: 'Pending'
      });

      const originalTaxTransaction = ref({});
      const taxAccounts = ref([]);
      const loading = ref(false);
      const errors = ref({});

      const loadTaxAccounts = async () => {
        try {
          const response = await axios.get('/api/accounting/chart-of-accounts', {
            params: { account_type: 'Tax' }
          });
          taxAccounts.value = response.data.data;
        } catch (error) {
          console.error('Failed to load tax accounts:', error);
          alert('Failed to load tax accounts. Please try again later.');
        }
      };

      const loadTaxTransaction = async () => {
        if (!isEditing.value) return;

        loading.value = true;
        try {
          const response = await axios.get(`/api/accounting/tax-transactions/${taxId}`);
          const taxData = response.data.data;

          // Format dates
          taxData.transaction_date = taxData.transaction_date.slice(0, 10);
          taxData.tax_period = taxData.tax_period.slice(0, 7);
          taxData.due_date = taxData.due_date.slice(0, 10);

          // Update form data
          Object.assign(taxTransaction, taxData);
          originalTaxTransaction.value = { ...taxData };
        } catch (error) {
          console.error('Failed to load tax transaction:', error);
          alert('Failed to load tax transaction. Please try again later.');
          router.push('/accounting/tax-transactions');
        } finally {
          loading.value = false;
        }
      };

      const resetForm = () => {
        if (isEditing.value) {
          // Reset to original values if editing
          Object.assign(taxTransaction, originalTaxTransaction.value);
        } else {
          // Reset to defaults if creating new
          Object.keys(taxTransaction).forEach(key => {
            if (key === 'transaction_date') {
              taxTransaction[key] = new Date().toISOString().split('T')[0];
            } else if (key === 'tax_period') {
              taxTransaction[key] = new Date().toISOString().slice(0, 7);
            } else if (key === 'status') {
              taxTransaction[key] = 'Pending';
            } else {
              taxTransaction[key] = '';
            }
          });
        }
        // Clear any validation errors
        errors.value = {};
      };

      const saveTaxTransaction = async () => {
        loading.value = true;
        errors.value = {};

        try {
          //let response;

          if (isEditing.value) {
            await axios.put(`/api/accounting/tax-transactions/${taxId}`, taxTransaction);
          } else {
            await axios.post('/api/accounting/tax-transactions', taxTransaction);
          }

          alert(isEditing.value ? 'Transaksi pajak berhasil diperbarui!' : 'Transaksi pajak berhasil dibuat!');
          router.push('/accounting/tax-transactions');
        } catch (error) {
          console.error('Failed to save tax transaction:', error);

          if (error.response && error.response.data.errors) {
            errors.value = error.response.data.errors;
          } else {
            alert('Failed to save tax transaction. Please try again later.');
          }
        } finally {
          loading.value = false;
        }
      };

      onMounted(() => {
        loadTaxAccounts();
        if (isEditing.value) {
          loadTaxTransaction();
        } else {
          // Calculate default due date (end of current month)
          const currentDate = new Date();
          const lastDayOfMonth = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);
          taxTransaction.due_date = lastDayOfMonth.toISOString().split('T')[0];
        }
      });

      // Auto-calculate tax amount when taxable_amount and tax_rate change
      const calculateTaxAmount = () => {
        if (taxTransaction.taxable_amount && taxTransaction.tax_rate) {
          taxTransaction.tax_amount = (parseFloat(taxTransaction.taxable_amount) * parseFloat(taxTransaction.tax_rate) / 100).toFixed(2);
        }
      };

      // Watch for changes to calculate tax amount
      const watchTaxableAmount = () => {
        if (taxTransaction.taxable_amount && taxTransaction.tax_rate) {
          calculateTaxAmount();
        }
      };

      const watchTaxRate = () => {
        if (taxTransaction.taxable_amount && taxTransaction.tax_rate) {
          calculateTaxAmount();
        }
      };

      return {
        taxTransaction,
        taxAccounts,
        loading,
        errors,
        isEditing,
        saveTaxTransaction,
        resetForm,
        watchTaxableAmount,
        watchTaxRate
      };
    }
  };
  </script>

  <style scoped>
  .tax-transaction-form {
    margin-bottom: 2rem;
  }

  .form-group {
    margin-bottom: 1rem;
  }

  .form-control {
    width: 100%;
    padding: 0.625rem;
    font-size: 0.875rem;
    line-height: 1.5;
    border: 1px solid var(--gray-300);
    border-radius: 0.375rem;
    transition: border-color 0.2s;
  }

  .form-control:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
  }

  .form-control.is-invalid {
    border-color: var(--danger-color);
  }

  .text-danger {
    color: var(--danger-color);
    font-size: 0.75rem;
    margin-top: 0.25rem;
  }

  .input-group {
    display: flex;
  }

  .input-group-text {
    display: flex;
    align-items: center;
    padding: 0.625rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--gray-700);
    text-align: center;
    background-color: var(--gray-100);
    border: 1px solid var(--gray-300);
    border-radius: 0.375rem 0 0 0.375rem;
  }

  .input-group .form-control {
    border-radius: 0 0.375rem 0.375rem 0;
  }

  .input-group .input-group-text:last-child {
    border-radius: 0 0.375rem 0.375rem 0;
  }

  .col-span-2 {
    grid-column: span 2;
  }

  .form-actions {
    margin-top: 2rem;
    display: flex;
    justify-content: space-between;
  }

  .gap-4 {
    gap: 1rem;
  }

  .mr-1 {
    margin-right: 0.25rem;
  }

  .mr-2 {
    margin-right: 0.5rem;
  }

  .ml-1 {
    margin-left: 0.25rem;
  }

  @media (max-width: 768px) {
    .grid-cols-2 {
      grid-template-columns: 1fr;
    }

    .col-span-2 {
      grid-column: span 1;
    }

    .form-actions {
      flex-direction: column;
      gap: 1rem;
    }

    .form-actions > div {
      display: flex;
      gap: 0.5rem;
    }
  }
  </style>
