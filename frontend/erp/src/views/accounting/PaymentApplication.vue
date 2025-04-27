<!-- src/views/accounting/PaymentApplication.vue -->
<template>
    <div class="payment-application">
      <div class="card">
        <div class="card-header">
          <h3>Apply Payment to Invoice</h3>
        </div>

        <div class="card-body">
          <div v-if="isLoading" class="loading-indicator">
            <i class="fas fa-spinner fa-spin"></i> Loading invoice information...
          </div>

          <div v-else-if="!payable" class="empty-state">
            <div class="empty-icon">
              <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3>Invoice Not Found</h3>
            <p>The requested invoice could not be found or is already fully paid.</p>
            <router-link to="/accounting/payable-payments" class="btn btn-primary">
              <i class="fas fa-arrow-left mr-1"></i> Back to Payments
            </router-link>
          </div>

          <div v-else>
            <!-- Invoice Information -->
            <div class="invoice-info mb-4">
              <div class="row">
                <div class="col-md-6">
                  <div class="info-card">
                    <h4 class="mb-3">Invoice Information</h4>
                    <div class="detail-row">
                      <div class="detail-label">Invoice Number:</div>
                      <div class="detail-value">{{ payable.vendor_invoice?.invoice_number || 'N/A' }}</div>
                    </div>
                    <div class="detail-row">
                      <div class="detail-label">Invoice Date:</div>
                      <div class="detail-value">{{ formatDate(payable.vendor_invoice?.invoice_date) }}</div>
                    </div>
                    <div class="detail-row">
                      <div class="detail-label">Due Date:</div>
                      <div class="detail-value">{{ formatDate(payable.due_date) }}</div>
                    </div>
                    <div class="detail-row highlight">
                      <div class="detail-label">Original Amount:</div>
                      <div class="detail-value">{{ formatCurrency(payable.amount) }}</div>
                    </div>
                    <div class="detail-row highlight">
                      <div class="detail-label">Amount Paid:</div>
                      <div class="detail-value">{{ formatCurrency(payable.paid_amount) }}</div>
                    </div>
                    <div class="detail-row highlight">
                      <div class="detail-label">Balance Due:</div>
                      <div class="detail-value amount">{{ formatCurrency(payable.balance) }}</div>
                    </div>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="info-card">
                    <h4 class="mb-3">Vendor Information</h4>
                    <div v-if="payable.vendor" class="detail-content">
                      <div class="detail-row">
                        <div class="detail-label">Vendor Name:</div>
                        <div class="detail-value">{{ payable.vendor.name }}</div>
                      </div>
                      <div class="detail-row">
                        <div class="detail-label">Vendor Code:</div>
                        <div class="detail-value">{{ payable.vendor.vendor_code || '-' }}</div>
                      </div>
                      <div class="detail-row">
                        <div class="detail-label">Email:</div>
                        <div class="detail-value">{{ payable.vendor.email || '-' }}</div>
                      </div>
                      <div class="detail-row">
                        <div class="detail-label">Phone:</div>
                        <div class="detail-value">{{ payable.vendor.phone || '-' }}</div>
                      </div>
                    </div>
                    <div v-else class="text-muted">
                      Vendor information not available
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Payment Form -->
            <div class="payment-form-container">
              <h4 class="section-title">Payment Details</h4>

              <form @submit.prevent="savePayment">
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="paymentDate">Payment Date <span class="text-danger">*</span></label>
                      <input
                        type="date"
                        id="paymentDate"
                        v-model="form.paymentDate"
                        class="form-control"
                        :class="{ 'is-invalid': errors.paymentDate }"
                        required
                      >
                      <div v-if="errors.paymentDate" class="invalid-feedback">
                        {{ errors.paymentDate }}
                      </div>
                    </div>
                  </div>

                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="paymentMethod">Payment Method <span class="text-danger">*</span></label>
                      <select
                        id="paymentMethod"
                        v-model="form.paymentMethod"
                        class="form-control"
                        :class="{ 'is-invalid': errors.paymentMethod }"
                        required
                      >
                        <option value="">-- Select Payment Method --</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                        <option value="Check">Check</option>
                        <option value="Cash">Cash</option>
                        <option value="Credit Card">Credit Card</option>
                      </select>
                      <div v-if="errors.paymentMethod" class="invalid-feedback">
                        {{ errors.paymentMethod }}
                      </div>
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="referenceNumber">Reference Number <span class="text-danger">*</span></label>
                      <input
                        type="text"
                        id="referenceNumber"
                        v-model="form.referenceNumber"
                        class="form-control"
                        :class="{ 'is-invalid': errors.referenceNumber }"
                        placeholder="Check #, Transaction ID, etc."
                        required
                      >
                      <div v-if="errors.referenceNumber" class="invalid-feedback">
                        {{ errors.referenceNumber }}
                      </div>
                    </div>
                  </div>

                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="amount">Amount <span class="text-danger">*</span></label>
                      <div class="input-group">
                        <div class="input-group-prepend">
                          <span class="input-group-text">Rp</span>
                        </div>
                        <input
                          type="number"
                          id="amount"
                          v-model="form.amount"
                          class="form-control"
                          :class="{ 'is-invalid': errors.amount }"
                          :max="payable.balance"
                          min="0.01"
                          step="0.01"
                          required
                        >
                        <div class="input-group-append">
                          <button type="button" class="btn btn-outline-secondary" @click="setFullAmount">
                            Full Amount
                          </button>
                        </div>
                      </div>
                      <div v-if="errors.amount" class="invalid-feedback d-block">
                        {{ errors.amount }}
                      </div>
                      <small class="form-text text-muted">
                        Maximum payment amount: {{ formatCurrency(payable.balance) }}
                      </small>
                    </div>
                  </div>
                </div>

                <div class="form-group">
                  <div class="custom-control custom-checkbox">
                    <input
                      type="checkbox"
                      id="createJournalEntry"
                      v-model="form.createJournalEntry"
                      class="custom-control-input"
                    >
                    <label class="custom-control-label" for="createJournalEntry">
                      Create journal entry automatically
                    </label>
                  </div>
                </div>

                <div v-if="form.createJournalEntry" class="journal-entry-section p-3 bg-light rounded mb-3">
                  <h5>Journal Entry Accounts</h5>

                  <div class="row">
                    <div class="col-md-6">
                      <div class="form-group">
                        <label for="cashAccountId">Cash/Bank Account <span class="text-danger">*</span></label>
                        <select
                          id="cashAccountId"
                          v-model="form.cashAccountId"
                          class="form-control"
                          :class="{ 'is-invalid': errors.cashAccountId }"
                          required
                        >
                          <option value="">-- Select Account --</option>
                          <option v-for="account in cashAccounts" :key="account.account_id" :value="account.account_id">
                            {{ account.account_code }} - {{ account.name }}
                          </option>
                        </select>
                        <div v-if="errors.cashAccountId" class="invalid-feedback">
                          {{ errors.cashAccountId }}
                        </div>
                      </div>
                    </div>

                    <div class="col-md-6">
                      <div class="form-group">
                        <label for="payableAccountId">Accounts Payable <span class="text-danger">*</span></label>
                        <select
                          id="payableAccountId"
                          v-model="form.payableAccountId"
                          class="form-control"
                          :class="{ 'is-invalid': errors.payableAccountId }"
                          required
                        >
                          <option value="">-- Select Account --</option>
                          <option v-for="account in payableAccounts" :key="account.account_id" :value="account.account_id">
                            {{ account.account_code }} - {{ account.name }}
                          </option>
                        </select>
                        <div v-if="errors.payableAccountId" class="invalid-feedback">
                          {{ errors.payableAccountId }}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="form-actions">
                  <button
                    type="button"
                    class="btn btn-secondary mr-2"
                    @click="goBack"
                  >
                    <i class="fas fa-arrow-left mr-1"></i> Cancel
                  </button>
                  <button
                    type="submit"
                    class="btn btn-success"
                    :disabled="isSaving || !isFormValid"
                  >
                    <i v-if="isSaving" class="fas fa-spinner fa-spin mr-1"></i>
                    <i v-else class="fas fa-money-bill mr-1"></i>
                    {{ isSaving ? 'Processing...' : 'Make Payment' }}
                  </button>
                </div>
              </form>
            </div>

            <!-- Previous Payments -->
            <div v-if="previousPayments.length > 0" class="previous-payments mt-4">
              <h4 class="section-title">Previous Payments</h4>

              <div class="table-responsive">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>Payment Date</th>
                      <th>Reference #</th>
                      <th>Payment Method</th>
                      <th class="text-right">Amount</th>
                      <th class="actions-column">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="payment in previousPayments" :key="payment.payment_id">
                      <td>{{ formatDate(payment.payment_date) }}</td>
                      <td>{{ payment.reference_number }}</td>
                      <td>{{ payment.payment_method }}</td>
                      <td class="text-right">{{ formatCurrency(payment.amount) }}</td>
                      <td class="actions-cell">
                        <router-link :to="`/accounting/payable-payments/${payment.payment_id}`" class="btn btn-sm btn-info">
                          <i class="fas fa-eye"></i>
                        </router-link>
                      </td>
                    </tr>
                  </tbody>
                  <tfoot>
                    <tr>
                      <td colspan="3" class="text-right font-weight-bold">Total:</td>
                      <td class="text-right font-weight-bold">{{ formatCurrency(calculateTotalPaid()) }}</td>
                      <td></td>
                    </tr>
                  </tfoot>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </template>

  <script>
  import { ref, reactive, computed, onMounted } from 'vue';
  import { useRoute, useRouter } from 'vue-router';
  import axios from 'axios';

  export default {
    name: 'PaymentApplication',
    setup() {
      const route = useRoute();
      const router = useRouter();
      const payableId = route.params.id;

      // State
      const payable = ref(null);
      const previousPayments = ref([]);
      const cashAccounts = ref([]);
      const payableAccounts = ref([]);
      const isLoading = ref(true);
      const isSaving = ref(false);
      const errors = ref({});

      // Form data
      const form = reactive({
        paymentDate: new Date().toISOString().substr(0, 10),
        paymentMethod: '',
        referenceNumber: '',
        amount: 0,
        createJournalEntry: true,
        cashAccountId: '',
        payableAccountId: ''
      });

      // Computed properties
      const isFormValid = computed(() => {
        if (!form.paymentDate || !form.paymentMethod || !form.referenceNumber) {
          return false;
        }

        const amount = parseFloat(form.amount);
        if (isNaN(amount) || amount <= 0 || (payable.value && amount > payable.value.balance)) {
          return false;
        }

        if (form.createJournalEntry && (!form.cashAccountId || !form.payableAccountId)) {
          return false;
        }

        return true;
      });

      // Methods
      const loadPayable = async () => {
        try {
          const response = await axios.get(`/api/accounting/vendor-payables/${payableId}`);
          payable.value = response.data.data;

          // Default amount to full balance
          form.amount = payable.value.balance;
        } catch (error) {
          console.error('Error loading payable:', error);
          payable.value = null;
        }
      };

      const loadPreviousPayments = async () => {
        try {
          const response = await axios.get('/api/accounting/payable-payments', {
            params: {
              payable_id: payableId
            }
          });
          previousPayments.value = response.data.data || [];
        } catch (error) {
          console.error('Error loading previous payments:', error);
          previousPayments.value = [];
        }
      };

      const loadAccounts = async () => {
        try {
          const response = await axios.get('/api/accounting/chart-of-accounts');
          const accounts = response.data.data || [];

          // Filter accounts by type
          cashAccounts.value = accounts.filter(account =>
            account.account_type === 'Asset' && account.is_active
          );

          payableAccounts.value = accounts.filter(account =>
            account.account_type === 'Liability' && account.is_active
          );

          // Set default payable account if available
          const defaultPayable = payableAccounts.value.find(account =>
            account.name.toLowerCase().includes('payable') ||
            account.name.toLowerCase().includes('hutang')
          );

          if (defaultPayable) {
            form.payableAccountId = defaultPayable.account_id;
          }
        } catch (error) {
          console.error('Error loading accounts:', error);
        }
      };

      const calculateTotalPaid = () => {
        return previousPayments.value.reduce((sum, payment) => {
          return sum + parseFloat(payment.amount || 0);
        }, 0);
      };

      const setFullAmount = () => {
        if (payable.value) {
          form.amount = payable.value.balance;
        }
      };

      const validateForm = () => {
        errors.value = {};
        let isValid = true;

        // Basic validation
        if (!form.paymentDate) {
          errors.value.paymentDate = 'Payment date is required';
          isValid = false;
        }

        if (!form.paymentMethod) {
          errors.value.paymentMethod = 'Payment method is required';
          isValid = false;
        }

        if (!form.referenceNumber) {
          errors.value.referenceNumber = 'Reference number is required';
          isValid = false;
        }

        // Amount validation
        const amount = parseFloat(form.amount);
        if (isNaN(amount) || amount <= 0) {
          errors.value.amount = 'Amount must be greater than zero';
          isValid = false;
        } else if (payable.value && amount > payable.value.balance) {
          errors.value.amount = `Amount cannot exceed the invoice balance (${formatCurrency(payable.value.balance)})`;
          isValid = false;
        }

        // Journal entry validation
        if (form.createJournalEntry) {
          if (!form.cashAccountId) {
            errors.value.cashAccountId = 'Cash/Bank account is required';
            isValid = false;
          }

          if (!form.payableAccountId) {
            errors.value.payableAccountId = 'Accounts payable account is required';
            isValid = false;
          }
        }

        return isValid;
      };

      const savePayment = async () => {
        if (!validateForm()) {
          return;
        }

        isSaving.value = true;

        try {
          const paymentData = {
            payable_id: payableId,
            payment_date: form.paymentDate,
            amount: parseFloat(form.amount),
            payment_method: form.paymentMethod,
            reference_number: form.referenceNumber,
            create_journal_entry: form.createJournalEntry
          };

          // Add journal accounts if creating journal entry
          if (form.createJournalEntry) {
            paymentData.cash_account_id = form.cashAccountId;
            paymentData.payable_account_id = form.payableAccountId;
          }

          // Make API call to create payment
          const response = await axios.post('/api/accounting/payable-payments', paymentData);
          const paymentId = response.data.data.payment_id;

          // Redirect to payment detail page
          router.push({
            path: `/accounting/payable-payments/${paymentId}`,
            query: { success: 'created' }
          });
        } catch (error) {
          console.error('Error saving payment:', error);

          if (error.response && error.response.data && error.response.data.errors) {
            errors.value = error.response.data.errors;
          } else if (error.response && error.response.data && error.response.data.message) {
            errors.value.general = error.response.data.message;
          } else {
            errors.value.general = 'An error occurred while saving the payment. Please try again.';
          }
        } finally {
          isSaving.value = false;
        }
      };

      const goBack = () => {
        router.back();
      };

      const formatDate = (dateString) => {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID');
      };

      const formatCurrency = (amount) => {
        return new Intl.NumberFormat('id-ID', {
          style: 'currency',
          currency: 'IDR',
          minimumFractionDigits: 0
        }).format(parseFloat(amount) || 0);
      };

      // Lifecycle hooks
      onMounted(async () => {
        isLoading.value = true;
        try {
          await Promise.all([
            loadPayable(),
            loadPreviousPayments(),
            loadAccounts()
          ]);
        } finally {
          isLoading.value = false;
        }
      });

      return {
        payable,
        previousPayments,
        cashAccounts,
        payableAccounts,
        isLoading,
        isSaving,
        errors,
        form,
        isFormValid,
        calculateTotalPaid,
        setFullAmount,
        savePayment,
        goBack,
        formatDate,
        formatCurrency
      };
    }
  };
  </script>

  <style scoped>
  .payment-application {
    padding: 1rem 0;
  }

  .info-card {
    background-color: var(--gray-50);
    border-radius: 0.5rem;
    padding: 1.5rem;
    height: 100%;
    margin-bottom: 1rem;
  }

  .detail-row {
    display: flex;
    margin-bottom: 0.75rem;
  }

  .detail-row.highlight {
    font-weight: 500;
  }

  .detail-label {
    flex: 0 0 40%;
    font-weight: 500;
    color: var(--gray-600);
  }

  .detail-value {
    flex: 0 0 60%;
  }

  .detail-value.amount {
    font-weight: 700;
    font-size: 1.125rem;
    color: var(--primary-color);
  }

  .section-title {
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid var(--gray-200);
  }

  .payment-form-container {
    background-color: white;
    border-radius: 0.5rem;
    padding: 1.5rem;
    border: 1px solid var(--gray-200);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  }

  .journal-entry-section {
    border: 1px solid var(--gray-300);
    border-radius: 0.5rem;
  }

  .form-actions {
    margin-top: 2rem;
    padding-top: 1rem;
    border-top: 1px solid var(--gray-200);
    display: flex;
    justify-content: flex-end;
  }

  .empty-state {
    text-align: center;
    padding: 3rem 0;
  }

  .empty-state .empty-icon {
    font-size: 3rem;
    color: var(--gray-300);
    margin-bottom: 1rem;
  }

  .empty-state h3 {
    font-size: 1.25rem;
    color: var(--gray-700);
    margin-bottom: 0.5rem;
  }

  .empty-state p {
    color: var(--gray-500);
    margin-bottom: 1.5rem;
  }

  .table-responsive {
    overflow-x: auto;
  }
  </style>
