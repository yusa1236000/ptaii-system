<!-- src/views/accounting/receivables/RecordPaymentForm.vue -->
<template>
    <div class="record-payment-form">
      <div class="card">
        <div class="card-header">
          <h3>{{ isEditing ? 'Edit Payment' : 'Record Customer Payment' }}</h3>
        </div>

        <div class="card-body">
          <div v-if="isLoading" class="loading-indicator">
            <i class="fas fa-spinner fa-spin"></i> Loading data...
          </div>

          <form v-else @submit.prevent="savePayment">
            <!-- Customer & Receivable Selection Section -->
            <div class="form-section mb-4">
              <h4 class="section-title">1. Select Customer & Invoice</h4>

              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="customerId">Customer <span class="text-danger">*</span></label>
                    <select
                      id="customerId"
                      v-model="selectedCustomerId"
                      class="form-control"
                      :class="{ 'is-invalid': v$.selectedCustomerId.$error }"
                      :disabled="isEditing"
                      @change="loadCustomerReceivables"
                    >
                      <option value="">-- Select Customer --</option>
                      <option v-for="customer in customers" :key="customer.customer_id" :value="customer.customer_id">
                        {{ customer.name }}
                      </option>
                    </select>
                    <div v-if="v$.selectedCustomerId.$error" class="invalid-feedback">
                      Customer is required
                    </div>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-group">
                    <label for="receivableId">Invoice to Pay <span class="text-danger">*</span></label>
                    <select
                      id="receivableId"
                      v-model="form.receivable_id"
                      class="form-control"
                      :class="{ 'is-invalid': v$.form.receivable_id.$error }"
                      :disabled="isEditing || !selectedCustomerId"
                    >
                      <option value="">-- Select Invoice --</option>
                      <option v-for="receivable in receivables" :key="receivable.receivable_id" :value="receivable.receivable_id">
                        {{ receivable.invoice_number || `Invoice #${receivable.invoice_id}` }} -
                        Due: {{ formatDate(receivable.due_date) }} -
                        Balance: {{ formatCurrency(receivable.balance) }}
                      </option>
                    </select>
                    <div v-if="v$.form.receivable_id.$error" class="invalid-feedback">
                      Invoice is required
                    </div>
                  </div>
                </div>
              </div>

              <!-- Show selected invoice details if available -->
              <div v-if="selectedReceivable" class="invoice-details mt-3">
                <div class="card bg-light">
                  <div class="card-body">
                    <h5 class="card-title">Invoice Details</h5>
                    <div class="row">
                      <div class="col-md-6">
                        <p><strong>Invoice Number:</strong> {{ selectedReceivable.invoice_number || `#${selectedReceivable.invoice_id}` }}</p>
                        <p><strong>Due Date:</strong> {{ formatDate(selectedReceivable.due_date) }}</p>
                      </div>
                      <div class="col-md-6">
                        <p><strong>Original Amount:</strong> {{ formatCurrency(selectedReceivable.amount) }}</p>
                        <p><strong>Paid Amount:</strong> {{ formatCurrency(selectedReceivable.paid_amount) }}</p>
                        <p><strong>Remaining Balance:</strong> <span class="text-danger font-weight-bold">{{ formatCurrency(selectedReceivable.balance) }}</span></p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Payment Details Section -->
            <div class="form-section mb-4">
              <h4 class="section-title">2. Payment Details</h4>

              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="paymentDate">Payment Date <span class="text-danger">*</span></label>
                    <input
                      type="date"
                      id="paymentDate"
                      v-model="form.payment_date"
                      class="form-control"
                      :class="{ 'is-invalid': v$.form.payment_date.$error }"
                    >
                    <div v-if="v$.form.payment_date.$error" class="invalid-feedback">
                      Payment date is required
                    </div>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-group">
                    <label for="paymentMethod">Payment Method <span class="text-danger">*</span></label>
                    <select
                      id="paymentMethod"
                      v-model="form.payment_method"
                      class="form-control"
                      :class="{ 'is-invalid': v$.form.payment_method.$error }"
                    >
                      <option value="">-- Select Payment Method --</option>
                      <option value="Cash">Cash</option>
                      <option value="Bank Transfer">Bank Transfer</option>
                      <option value="Check">Check</option>
                      <option value="Credit Card">Credit Card</option>
                      <option value="Online Payment">Online Payment</option>
                    </select>
                    <div v-if="v$.form.payment_method.$error" class="invalid-feedback">
                      Payment method is required
                    </div>
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="amount">Payment Amount <span class="text-danger">*</span></label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                        <span class="input-group-text">Rp</span>
                      </div>
                      <input
                        type="number"
                        id="amount"
                        v-model="form.amount"
                        class="form-control"
                        :class="{ 'is-invalid': v$.form.amount.$error }"
                        step="0.01"
                        min="0"
                        :max="selectedReceivable ? selectedReceivable.balance : null"
                      >
                    </div>
                    <div v-if="v$.form.amount.$error" class="invalid-feedback">
                      <div v-if="v$.form.amount.required.$invalid">Payment amount is required</div>
                      <div v-else-if="v$.form.amount.min.$invalid">Amount must be greater than 0</div>
                      <div v-else-if="v$.form.amount.max.$invalid">Amount cannot exceed the remaining balance</div>
                    </div>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-group">
                    <label for="referenceNumber">Reference Number <span class="text-danger">*</span></label>
                    <input
                      type="text"
                      id="referenceNumber"
                      v-model="form.reference_number"
                      class="form-control"
                      :class="{ 'is-invalid': v$.form.reference_number.$error }"
                      placeholder="e.g., Receipt #, Check #, Transaction ID"
                    >
                    <div v-if="v$.form.reference_number.$error" class="invalid-feedback">
                      Reference number is required
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Journal Entry Section -->
            <div class="form-section mb-4">
              <h4 class="section-title">3. Journal Entry</h4>

              <div class="form-check mb-3">
                <input
                  type="checkbox"
                  id="createJournalEntry"
                  v-model="form.create_journal_entry"
                  class="form-check-input"
                >
                <label class="form-check-label" for="createJournalEntry">
                  Create journal entry for this payment
                </label>
              </div>

              <div v-if="form.create_journal_entry" class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="cashAccountId">Cash/Bank Account <span class="text-danger">*</span></label>
                    <select
                      id="cashAccountId"
                      v-model="form.cash_account_id"
                      class="form-control"
                      :class="{ 'is-invalid': form.create_journal_entry && v$.form.cash_account_id.$error }"
                    >
                      <option value="">-- Select Account --</option>
                      <option v-for="account in cashAccounts" :key="account.account_id" :value="account.account_id">
                        {{ account.account_code }} - {{ account.name }}
                      </option>
                    </select>
                    <div v-if="form.create_journal_entry && v$.form.cash_account_id.$error" class="invalid-feedback">
                      Cash/bank account is required when creating a journal entry
                    </div>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="form-group">
                    <label for="receivableAccountId">Accounts Receivable <span class="text-danger">*</span></label>
                    <select
                      id="receivableAccountId"
                      v-model="form.receivable_account_id"
                      class="form-control"
                      :class="{ 'is-invalid': form.create_journal_entry && v$.form.receivable_account_id.$error }"
                    >
                      <option value="">-- Select Account --</option>
                      <option v-for="account in receivableAccounts" :key="account.account_id" :value="account.account_id">
                        {{ account.account_code }} - {{ account.name }}
                      </option>
                    </select>
                    <div v-if="form.create_journal_entry && v$.form.receivable_account_id.$error" class="invalid-feedback">
                      Receivable account is required when creating a journal entry
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="form-actions mt-4">
              <button type="button" class="btn btn-secondary mr-2" @click="goBack">
                Cancel
              </button>
              <button type="submit" class="btn btn-primary" :disabled="isSaving || v$.$invalid">
                <i class="fas fa-save mr-1"></i>
                {{ isSaving ? 'Saving...' : (isEditing ? 'Update Payment' : 'Record Payment') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </template>

  <script>
  import { ref, reactive, computed, onMounted, watch } from 'vue';
  import { useRoute, useRouter } from 'vue-router';
  import { useVuelidate } from '@vuelidate/core';
  import { required, min, max } from '@vuelidate/validators';
  import axios from 'axios';

  export default {
    name: 'RecordPaymentForm',
    setup() {
      const route = useRoute();
      const router = useRouter();
      const isEditing = computed(() => route.params.id !== undefined);

      // State
      const isLoading = ref(true);
      const isSaving = ref(false);
      const customers = ref([]);
      const receivables = ref([]);
      const cashAccounts = ref([]);
      const receivableAccounts = ref([]);
      const selectedCustomerId = ref('');

      // Form data
      const form = reactive({
        receivable_id: '',
        payment_date: new Date().toISOString().substr(0, 10), // Today's date
        amount: null,
        payment_method: '',
        reference_number: '',
        create_journal_entry: true,
        cash_account_id: '',
        receivable_account_id: ''
      });

      // Validation rules
      const rules = computed(() => {
        return {
          selectedCustomerId: { required },
          form: {
            receivable_id: { required },
            payment_date: { required },
            amount: {
              required,
              min: min(0.01),
              max: max(selectedReceivable.value ? selectedReceivable.value.balance : Infinity)
            },
            payment_method: { required },
            reference_number: { required },
            cash_account_id: { required: form.create_journal_entry ? required : null },
            receivable_account_id: { required: form.create_journal_entry ? required : null }
          }
        };
      });

      const v$ = useVuelidate(rules, { selectedCustomerId, form });

      // Computed properties
      const selectedReceivable = computed(() => {
        if (!form.receivable_id) return null;
        return receivables.value.find(r => r.receivable_id == form.receivable_id) || null;
      });

      // Methods
      const loadCustomers = async () => {
        try {
          const response = await axios.get('/api/sales/customers');
          customers.value = response.data.data || [];
        } catch (error) {
          console.error('Error loading customers:', error);
          if (this.$toast) {
            this.$toast.error('Failed to load customers');
          }
        }
      };

      const loadCustomerReceivables = async () => {
        if (!selectedCustomerId.value) {
          receivables.value = [];
          return;
        }

        try {
          const response = await axios.get(`/api/accounting/customer-receivables`, {
            params: {
              customer_id: selectedCustomerId.value,
              status: 'Open' // Only get unpaid receivables
            }
          });
          receivables.value = response.data.data || [];
        } catch (error) {
          console.error('Error loading receivables:', error);
          if (this.$toast) {
            this.$toast.error('Failed to load customer invoices');
          }
        }
      };

      const loadChartOfAccounts = async () => {
        try {
          const response = await axios.get('/api/accounting/chart-of-accounts');
          const accounts = response.data.data || [];

          // Filter accounts by type
          cashAccounts.value = accounts.filter(account =>
            account.account_type === 'Asset' && account.is_active
          );

          receivableAccounts.value = accounts.filter(account =>
            account.account_type === 'Asset' &&
            account.is_active &&
            (account.name.toLowerCase().includes('receivable') ||
             account.name.toLowerCase().includes('piutang'))
          );

          // Set default receivable account if available
          if (receivableAccounts.value.length > 0 && !form.receivable_account_id) {
            form.receivable_account_id = receivableAccounts.value[0].account_id;
          }
        } catch (error) {
          console.error('Error loading chart of accounts:', error);
          if (this.$toast) {
            this.$toast.error('Failed to load accounts');
          }
        }
      };

      const loadPayment = async (paymentId) => {
        try {
          const response = await axios.get(`/api/accounting/receivable-payments/${paymentId}`);
          const payment = response.data.data;

          // Set form data
          form.receivable_id = payment.receivable_id;
          form.payment_date = payment.payment_date;
          form.amount = payment.amount;
          form.payment_method = payment.payment_method;
          form.reference_number = payment.reference_number;

          // Set customer ID
          if (payment.customer_receivable && payment.customer_receivable.customer_id) {
            selectedCustomerId.value = payment.customer_receivable.customer_id;
            await loadCustomerReceivables();
          }

          // Find related journal entry if exists
          try {
            const journalResponse = await axios.get('/api/accounting/journal-entries', {
              params: {
                reference_type: 'ReceivablePayment',
                reference_id: paymentId
              }
            });

            const journalEntry = journalResponse.data.data[0];
            if (journalEntry) {
              form.create_journal_entry = true;

              // Extract account IDs from journal lines
              const debitLine = journalEntry.journal_entry_lines.find(line => line.debit_amount > 0);
              const creditLine = journalEntry.journal_entry_lines.find(line => line.credit_amount > 0);

              if (debitLine) form.cash_account_id = debitLine.account_id;
              if (creditLine) form.receivable_account_id = creditLine.account_id;
            } else {
              form.create_journal_entry = false;
            }
          } catch (error) {
            console.error('Error loading journal entry:', error);
            form.create_journal_entry = false;
          }
        } catch (error) {
          console.error('Error loading payment:', error);
          if (this.$toast) {
            this.$toast.error('Failed to load payment details');
          }
        }
      };

      const loadData = async () => {
        isLoading.value = true;
        try {
          // Load all necessary data in parallel
          await Promise.all([
            loadCustomers(),
            loadChartOfAccounts()
          ]);

          // If editing, load payment details
          if (isEditing.value) {
            await loadPayment(route.params.id);
          }
        } catch (error) {
          console.error('Error loading data:', error);
          if (this.$toast) {
            this.$toast.error('Failed to load required data');
          }
        } finally {
          isLoading.value = false;
        }
      };

      const savePayment = async () => {
        // Validate form
        const isFormValid = await v$.value.$validate();
        if (!isFormValid) return;

        isSaving.value = true;
        try {
          const payload = {
            receivable_id: form.receivable_id,
            payment_date: form.payment_date,
            amount: parseFloat(form.amount),
            payment_method: form.payment_method,
            reference_number: form.reference_number,
            create_journal_entry: form.create_journal_entry
          };

          // Add journal entry accounts if needed
          if (form.create_journal_entry) {
            payload.cash_account_id = form.cash_account_id;
            payload.receivable_account_id = form.receivable_account_id;
          }

          //let response;
          if (isEditing.value) {
            // For editing, we'd typically use PUT, but the API doesn't support updating payments
            // So we'll delete and recreate
            await axios.delete(`/api/accounting/receivable-payments/${route.params.id}`);
            await axios.post('/api/accounting/receivable-payments', payload);
          } else {
            await axios.post('/api/accounting/receivable-payments', payload);
          }

          // Success notification
          if (this.$toast) {
            this.$toast.success(isEditing.value ? 'Payment updated successfully' : 'Payment recorded successfully');
          }

          // Redirect to payment details or list
          router.push('/accounting/receivable-payments');
        } catch (error) {
          console.error('Error saving payment:', error);

          // Error notification
          if (this.$toast) {
            this.$toast.error(
              `Failed to ${isEditing.value ? 'update' : 'record'} payment: ` +
              (error.response?.data?.message || 'An error occurred')
            );
          }
        } finally {
          isSaving.value = false;
        }
      };

      const goBack = () => {
        router.push('/accounting/receivable-payments');
      };

      const formatDate = (dateString) => {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
          year: 'numeric',
          month: 'short',
          day: 'numeric'
        });
      };

      const formatCurrency = (amount) => {
        return new Intl.NumberFormat('id-ID', {
          style: 'currency',
          currency: 'IDR',
          minimumFractionDigits: 0
        }).format(amount);
      };

      // Reset the form when customer changes
      watch(selectedCustomerId, (newVal) => {
        if (newVal !== '' && !isEditing.value) {
          form.receivable_id = '';
        }
      });

      // Watch selected receivable and update max amount
      watch(() => form.receivable_id, (newVal) => {
        if (newVal && !isEditing.value) {
          const receivable = receivables.value.find(r => r.receivable_id == newVal);
          if (receivable) {
            form.amount = receivable.balance;
          }
        }
      });

      // Lifecycle hooks
      onMounted(() => {
        loadData();
      });

      return {
        isEditing,
        isLoading,
        isSaving,
        customers,
        receivables,
        cashAccounts,
        receivableAccounts,
        selectedCustomerId,
        form,
        selectedReceivable,
        v$,
        loadCustomerReceivables,
        savePayment,
        goBack,
        formatDate,
        formatCurrency
      };
    }
  };
  </script>

  <style scoped>
  .record-payment-form {
    padding: 1rem 0;
  }

  .form-section {
    padding-bottom: 1.5rem;
    border-bottom: 1px solid var(--gray-200);
  }

  .section-title {
    font-size: 1.1rem;
    color: var(--primary-color);
    margin-bottom: 1.25rem;
  }

  .invoice-details {
    border-radius: 0.375rem;
  }

  .form-actions {
    margin-top: 2rem;
    padding-top: 1rem;
    border-top: 1px solid var(--gray-200);
  }
  </style>
