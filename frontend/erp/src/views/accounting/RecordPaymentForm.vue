<!-- src/views/accounting/RecordPaymentForm.vue -->
<!-- Template Section -->
<template>
    <div class="record-payment-form">
      <div class="card">
        <div class="card-header">
          <h3>Record Vendor Payment</h3>
        </div>

        <div class="card-body">
          <div v-if="isLoading" class="loading-indicator">
            <i class="fas fa-spinner fa-spin"></i> Loading...
          </div>

          <form v-else @submit.prevent="savePayment">
            <!-- Step 1: Select Vendor -->
            <div v-if="currentStep === 1" class="form-step">
              <h4 class="step-title">Step 1: Select Vendor</h4>

              <div class="form-group">
                <label for="vendorId">Vendor <span class="text-danger">*</span></label>
                <select
                  id="vendorId"
                  v-model="form.vendorId"
                  class="form-control"
                  :class="{ 'is-invalid': errors.vendorId }"
                  required
                >
                  <option value="">-- Select Vendor --</option>
                  <option v-for="vendor in vendors" :key="vendor.vendor_id" :value="vendor.vendor_id">
                    {{ vendor.name }}
                  </option>
                </select>
                <div v-if="errors.vendorId" class="invalid-feedback">
                  {{ errors.vendorId }}
                </div>
              </div>

              <div class="mt-4 d-flex justify-content-between">
                <router-link to="/accounting/payable-payments" class="btn btn-secondary">
                  <i class="fas fa-arrow-left mr-1"></i> Cancel
                </router-link>
                <button
                  type="button"
                  class="btn btn-primary"
                  @click="goToStep(2)"
                  :disabled="!form.vendorId"
                >
                  Next <i class="fas fa-arrow-right ml-1"></i>
                </button>
              </div>
            </div>

            <!-- Step 2: Select Invoices -->
            <div v-else-if="currentStep === 2" class="form-step">
              <h4 class="step-title">Step 2: Select Invoices to Pay</h4>

              <div v-if="isLoadingInvoices" class="text-center py-4">
                <i class="fas fa-spinner fa-spin"></i>
                <p class="mt-2">Loading invoices...</p>
              </div>

              <div v-else-if="openInvoices.length === 0" class="empty-state small py-4">
                <i class="fas fa-file-invoice"></i>
                <p>No open invoices found for this vendor</p>
                <button type="button" class="btn btn-outline-secondary" @click="goToStep(1)">
                  Select Different Vendor
                </button>
              </div>

              <div v-else>
                <div class="table-responsive">
                  <table class="table table-bordered">
                    <thead class="bg-light">
                      <tr>
                        <th width="5%">Select</th>
                        <th>Invoice #</th>
                        <th>Due Date</th>
                        <th class="text-right">Total</th>
                        <th class="text-right">Balance</th>
                        <th class="text-right">Amount to Pay</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="invoice in openInvoices" :key="invoice.payable_id">
                        <td class="text-center">
                          <input
                            type="checkbox"
                            v-model="invoice.selected"
                            @change="updatePaymentAmount(invoice)"
                          >
                        </td>
                        <td>{{ invoice.vendor_invoice.invoice_number }}</td>
                        <td>{{ formatDate(invoice.due_date) }}</td>
                        <td class="text-right">{{ formatCurrency(invoice.amount) }}</td>
                        <td class="text-right">{{ formatCurrency(invoice.balance) }}</td>
                        <td>
                          <input
                            type="number"
                            class="form-control text-right"
                            v-model="invoice.payment_amount"
                            :disabled="!invoice.selected"
                            :max="invoice.balance"
                            :step="0.01"
                            min="0.01"
                            @input="validatePaymentAmount(invoice)"
                          >
                        </td>
                      </tr>
                    </tbody>
                    <tfoot class="bg-light font-weight-bold">
                      <tr>
                        <td colspan="4" class="text-right">Total Payment Amount:</td>
                        <td colspan="2" class="text-right">{{ formatCurrency(totalPaymentAmount) }}</td>
                      </tr>
                    </tfoot>
                  </table>
                </div>

                <div v-if="paymentAmountError" class="alert alert-danger mt-3">
                  {{ paymentAmountError }}
                </div>
              </div>

              <div class="mt-4 d-flex justify-content-between">
                <button type="button" class="btn btn-secondary" @click="goToStep(1)">
                  <i class="fas fa-arrow-left mr-1"></i> Back
                </button>
                <button
                  type="button"
                  class="btn btn-primary"
                  @click="goToStep(3)"
                  :disabled="!hasSelectedInvoices || paymentAmountError"
                >
                  Next <i class="fas fa-arrow-right ml-1"></i>
                </button>
              </div>
            </div>

            <!-- Step 3: Payment Details -->
            <div v-else-if="currentStep === 3" class="form-step">
              <h4 class="step-title">Step 3: Payment Details</h4>

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

              <div class="mt-4 d-flex justify-content-between">
                <button type="button" class="btn btn-secondary" @click="goToStep(2)">
                  <i class="fas fa-arrow-left mr-1"></i> Back
                </button>
                <button
                  type="submit"
                  class="btn btn-success"
                  :disabled="isSaving"
                >
                  <i v-if="isSaving" class="fas fa-spinner fa-spin mr-1"></i>
                  <i v-else class="fas fa-save mr-1"></i>
                  {{ isSaving ? 'Processing...' : 'Save Payment' }}
                </button>
              </div>
            </div>

            <!-- Success Step -->
            <div v-else-if="currentStep === 4" class="form-step">
              <div class="success-message text-center py-5">
                <div class="success-icon mb-4">
                  <i class="fas fa-check-circle text-success"></i>
                </div>
                <h4>Payment Recorded Successfully!</h4>
                <p>Payment of {{ formatCurrency(totalPaymentAmount) }} has been recorded.</p>
                <div class="mt-4">
                  <router-link :to="`/accounting/payable-payments/${createdPaymentId}`" class="btn btn-info mr-2">
                    <i class="fas fa-eye mr-1"></i> View Payment
                  </router-link>
                  <router-link to="/accounting/payable-payments" class="btn btn-primary">
                    <i class="fas fa-list mr-1"></i> Back to Payments
                  </router-link>
                </div>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </template>
  <!-- Script Section -->
<script>
import { ref, computed, reactive, onMounted } from 'vue';
//import { useRouter } from 'vue-router';
import axios from 'axios';

export default {
  name: 'RecordPaymentForm',
  setup() {
    //const router = useRouter();

    // State
    const currentStep = ref(1);
    const isLoading = ref(false);
    const isLoadingInvoices = ref(false);
    const isSaving = ref(false);
    const vendors = ref([]);
    const openInvoices = ref([]);
    const cashAccounts = ref([]);
    const payableAccounts = ref([]);
    const errors = ref({});
    const paymentAmountError = ref('');
    const createdPaymentId = ref(null);

    // Form data
    const form = reactive({
      vendorId: '',
      paymentDate: new Date().toISOString().substr(0, 10),
      paymentMethod: '',
      referenceNumber: '',
      createJournalEntry: true,
      cashAccountId: '',
      payableAccountId: ''
    });

    // Computed properties
    const totalPaymentAmount = computed(() => {
      return openInvoices.value.reduce((sum, invoice) => {
        return sum + (invoice.selected ? parseFloat(invoice.payment_amount || 0) : 0);
      }, 0);
    });

    const hasSelectedInvoices = computed(() => {
      return openInvoices.value.some(invoice => invoice.selected && parseFloat(invoice.payment_amount) > 0);
    });

    // Methods
    const loadVendors = async () => {
      isLoading.value = true;
      try {
        const response = await axios.get('/api/vendors');
        vendors.value = response.data.data || [];
      } catch (error) {
        console.error('Error loading vendors:', error);
      } finally {
        isLoading.value = false;
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

    const loadVendorInvoices = async () => {
      if (!form.vendorId) return;

      isLoadingInvoices.value = true;
      openInvoices.value = [];

      try {
        const response = await axios.get('/api/accounting/vendor-payables', {
          params: {
            vendor_id: form.vendorId,
            status: 'Open'
          }
        });

        // Transform the data to add UI state
        openInvoices.value = (response.data.data || []).map(invoice => ({
          ...invoice,
          selected: false,
          payment_amount: invoice.balance
        }));

      } catch (error) {
        console.error('Error loading vendor invoices:', error);
      } finally {
        isLoadingInvoices.value = false;
      }
    };

    const goToStep = (step) => {
      if (step === 2 && currentStep.value === 1) {
        loadVendorInvoices();
      }

      currentStep.value = step;
      window.scrollTo(0, 0);

      // Clear errors when changing steps
      errors.value = {};
      paymentAmountError.value = '';
    };

    const updatePaymentAmount = (invoice) => {
      // If selected, default to full balance
      if (invoice.selected) {
        invoice.payment_amount = invoice.balance;
      } else {
        invoice.payment_amount = 0;
      }

      validatePaymentAmount(invoice);
    };

    const validatePaymentAmount = (invoice) => {
      paymentAmountError.value = '';

      // Convert to float to handle potential string input
      const amount = parseFloat(invoice.payment_amount);

      if (invoice.selected) {
        if (isNaN(amount) || amount <= 0) {
          paymentAmountError.value = 'Payment amount must be greater than zero';
          return false;
        }

        if (amount > invoice.balance) {
          invoice.payment_amount = invoice.balance;
          paymentAmountError.value = 'Payment amount cannot exceed the invoice balance';
          return false;
        }
      }

      return true;
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

      // Ensure at least one invoice is selected
      if (!hasSelectedInvoices.value) {
        errors.value.general = 'Please select at least one invoice to pay';
        isValid = false;
      }

      return isValid;
    };

    const savePayment = async () => {
      if (!validateForm()) {
        return;
      }

      isSaving.value = true;

      try {
        // Prepare payment data
        const selectedPayables = openInvoices.value
          .filter(invoice => invoice.selected && parseFloat(invoice.payment_amount) > 0)
          .map(invoice => ({
            payable_id: invoice.payable_id,
            amount: parseFloat(invoice.payment_amount)
          }));

        if (selectedPayables.length === 0) {
          errors.value.general = 'Please select at least one invoice to pay';
          isSaving.value = false;
          return;
        }

        // Create a single payment for each payable
        // We'll use the first payable for demonstration, but in real implementation
        // you might want to handle multiple payables differently
        const firstPayable = selectedPayables[0];

        const paymentData = {
          payable_id: firstPayable.payable_id,
          payment_date: form.paymentDate,
          amount: firstPayable.amount,
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

        // If more than one payable, create additional payments
        if (selectedPayables.length > 1) {
          for (let i = 1; i < selectedPayables.length; i++) {
            const additionalPayment = {
              ...paymentData,
              payable_id: selectedPayables[i].payable_id,
              amount: selectedPayables[i].amount
            };
            await axios.post('/api/accounting/payable-payments', additionalPayment);
          }
        }

        // Store the created payment ID for navigation
        createdPaymentId.value = response.data.data.payment_id;

        // Show success message
        goToStep(4);
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

    const formatDate = (dateString) => {
      if (!dateString) return '';
      const date = new Date(dateString);
      return date.toLocaleDateString('id-ID');
    };

    const formatCurrency = (amount) => {
      return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
      }).format(amount);
    };

    // Lifecycle hooks
    onMounted(() => {
      loadVendors();
      loadAccounts();
    });

    return {
      currentStep,
      isLoading,
      isLoadingInvoices,
      isSaving,
      vendors,
      openInvoices,
      cashAccounts,
      payableAccounts,
      errors,
      paymentAmountError,
      form,
      totalPaymentAmount,
      hasSelectedInvoices,
      createdPaymentId,
      goToStep,
      updatePaymentAmount,
      validatePaymentAmount,
      savePayment,
      formatDate,
      formatCurrency
    };
  }
};
</script>
<!-- Style Section -->
<style scoped>
.record-payment-form {
  padding: 1rem 0;
}

.form-step {
  padding: 1rem 0;
}

.step-title {
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid var(--gray-200);
}

.journal-entry-section {
  border: 1px solid var(--gray-300);
  border-radius: 0.5rem;
}

.success-icon {
  font-size: 4rem;
  color: var(--success-color);
}

.empty-state {
  text-align: center;
  color: var(--gray-500);
}

.empty-state i {
  font-size: 2rem;
  margin-bottom: 1rem;
}

/* Table styling */
.table th {
  background-color: var(--gray-100);
}

.table-container {
  overflow-x: auto;
}

/* Custom form elements */
.form-control:focus {
  border-color: var(--primary-color);
  box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.25);
}

.custom-control-input:checked ~ .custom-control-label::before {
  border-color: var(--primary-color);
  background-color: var(--primary-color);
}

/* Success message styling */
.success-message {
  background-color: var(--success-bg);
  border-radius: 0.5rem;
  padding: 2rem;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
  .form-step {
    padding: 0.5rem 0;
  }

  .journal-entry-section {
    padding: 1rem !important;
  }

  .mt-4.d-flex {
    flex-direction: column;
    gap: 0.5rem;
  }

  .mt-4.d-flex button,
  .mt-4.d-flex a {
    width: 100%;
  }
}
</style>
