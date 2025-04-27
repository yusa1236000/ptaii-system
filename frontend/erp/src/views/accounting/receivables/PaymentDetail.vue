<!-- src/views/accounting/receivables/PaymentDetail.vue -->
<template>
    <div class="payment-detail">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h3>Payment Details</h3>
          <div>
            <router-link :to="`/accounting/customers/${customerId}`" v-if="customerId" class="btn btn-info mr-2">
              <i class="fas fa-user mr-1"></i> View Customer
            </router-link>
            <button class="btn btn-secondary" @click="printPaymentDetails">
              <i class="fas fa-print mr-1"></i> Print Receipt
            </button>
          </div>
        </div>

        <div class="card-body">
          <div v-if="isLoading" class="loading-indicator">
            <i class="fas fa-spinner fa-spin"></i> Loading payment details...
          </div>

          <div v-else-if="!payment" class="empty-state">
            <div class="empty-icon">
              <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3>Payment Not Found</h3>
            <p>The payment details you are looking for could not be found.</p>
            <router-link to="/accounting/receivable-payments" class="btn btn-primary">
              <i class="fas fa-arrow-left mr-1"></i> Back to Payments List
            </router-link>
          </div>

          <div v-else class="payment-details-content">
            <!-- Payment Status Badge -->
            <div class="payment-status mb-4">
              <span class="badge badge-success">
                <i class="fas fa-check-circle mr-1"></i> Payment Recorded
              </span>
              <span class="payment-date">{{ formatDate(payment.payment_date) }}</span>
            </div>

            <div class="row">
              <!-- Payment Information -->
              <div class="col-md-6 mb-4">
                <div class="detail-card">
                  <h4 class="card-section-title">
                    <i class="fas fa-file-invoice-dollar mr-2"></i> Payment Information
                  </h4>
                  <table class="detail-table">
                    <tbody>
                      <tr>
                        <th>Payment Date:</th>
                        <td>{{ formatDate(payment.payment_date) }}</td>
                      </tr>
                      <tr>
                        <th>Reference Number:</th>
                        <td>{{ payment.reference_number }}</td>
                      </tr>
                      <tr>
                        <th>Payment Method:</th>
                        <td>
                          <span :class="'badge-' + getPaymentMethodClass(payment.payment_method)" class="badge">
                            {{ payment.payment_method }}
                          </span>
                        </td>
                      </tr>
                      <tr>
                        <th>Amount:</th>
                        <td class="amount-value">{{ formatCurrency(payment.amount) }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- Customer Information -->
              <div class="col-md-6 mb-4">
                <div class="detail-card">
                  <h4 class="card-section-title">
                    <i class="fas fa-user mr-2"></i> Customer Information
                  </h4>
                  <table class="detail-table">
                    <tbody>
                      <tr v-if="customer">
                        <th>Customer Name:</th>
                        <td>{{ customer.name }}</td>
                      </tr>
                      <tr v-if="customer">
                        <th>Customer ID:</th>
                        <td>{{ customer.customer_id }}</td>
                      </tr>
                      <tr v-if="customer">
                        <th>Contact:</th>
                        <td>{{ customer.phone || customer.email || 'N/A' }}</td>
                      </tr>
                      <tr v-if="!customer">
                        <td colspan="2" class="text-center text-muted">Customer information not available</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <div class="row">
              <!-- Invoice Information -->
              <div class="col-md-6 mb-4">
                <div class="detail-card">
                  <h4 class="card-section-title">
                    <i class="fas fa-receipt mr-2"></i> Invoice Information
                  </h4>
                  <table class="detail-table">
                    <tbody>
                      <tr v-if="invoice">
                        <th>Invoice Number:</th>
                        <td>{{ invoice.invoice_number || `#${invoice.invoice_id}` }}</td>
                      </tr>
                      <tr v-if="receivable">
                        <th>Due Date:</th>
                        <td>{{ formatDate(receivable.due_date) }}</td>
                      </tr>
                      <tr v-if="receivable">
                        <th>Original Amount:</th>
                        <td>{{ formatCurrency(receivable.amount) }}</td>
                      </tr>
                      <tr v-if="receivable">
                        <th>Balance After Payment:</th>
                        <td>{{ formatCurrency(receivable.balance) }}</td>
                      </tr>
                      <tr v-if="receivable">
                        <th>Status:</th>
                        <td>
                          <span :class="'badge-' + getStatusClass(receivable.status)" class="badge">
                            {{ receivable.status }}
                          </span>
                        </td>
                      </tr>
                      <tr v-if="!receivable">
                        <td colspan="2" class="text-center text-muted">Invoice information not available</td>
                      </tr>
                    </tbody>
                  </table>
                  <div v-if="invoice" class="mt-3">
                    <router-link :to="`/sales/invoices/${invoice.invoice_id}`" class="btn btn-sm btn-outline-primary">
                      <i class="fas fa-eye mr-1"></i> View Invoice
                    </router-link>
                  </div>
                </div>
              </div>

              <!-- Journal Entry Information -->
              <div class="col-md-6 mb-4">
                <div class="detail-card">
                  <h4 class="card-section-title">
                    <i class="fas fa-book mr-2"></i> Journal Entry Information
                  </h4>
                  <table class="detail-table">
                    <tbody>
                      <tr v-if="journalEntry">
                        <th>Journal Number:</th>
                        <td>{{ journalEntry.journal_number }}</td>
                      </tr>
                      <tr v-if="journalEntry">
                        <th>Entry Date:</th>
                        <td>{{ formatDate(journalEntry.entry_date) }}</td>
                      </tr>
                      <tr v-if="journalEntry">
                        <th>Status:</th>
                        <td>
                          <span :class="journalEntry.status === 'Posted' ? 'badge-success' : 'badge-warning'" class="badge">
                            {{ journalEntry.status }}
                          </span>
                        </td>
                      </tr>
                      <tr v-if="!journalEntry">
                        <td colspan="2" class="text-center text-muted">No journal entry was created for this payment</td>
                      </tr>
                    </tbody>
                  </table>
                  <div v-if="journalEntry" class="mt-3">
                    <router-link :to="`/accounting/journal-entries/${journalEntry.journal_id}`" class="btn btn-sm btn-outline-primary">
                      <i class="fas fa-eye mr-1"></i> View Journal Entry
                    </router-link>
                  </div>
                </div>
              </div>
            </div>

            <!-- Actions Section -->
            <div class="actions-section mt-4">
              <div class="row">
                <div class="col-md-12">
                  <div class="d-flex justify-content-between">
                    <router-link to="/accounting/receivable-payments" class="btn btn-secondary">
                      <i class="fas fa-arrow-left mr-1"></i> Back to Payments
                    </router-link>

                    <div>
                      <button
                        v-if="payment && receivable && receivable.status !== 'Paid'"
                        @click="applyToInvoice"
                        class="btn btn-success mr-2"
                      >
                        <i class="fas fa-file-invoice mr-1"></i> Apply to Invoice
                      </button>

                      <button
                        @click="confirmDelete"
                        class="btn btn-danger"
                      >
                        <i class="fas fa-trash mr-1"></i> Reverse Payment
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Delete Confirmation Modal -->
      <ConfirmationModal
        v-if="showDeleteModal"
        title="Reverse Payment"
        :message="`Are you sure you want to reverse payment with reference number <strong>${payment?.reference_number}</strong>?<br>This action will update the receivable balance and cannot be undone.`"
        confirm-button-text="Reverse Payment"
        confirm-button-class="btn btn-danger"
        @confirm="deletePayment"
        @close="showDeleteModal = false"
      />
    </div>
  </template>

  <script>
  import { ref, computed, onMounted } from 'vue';
  import { useRoute, useRouter } from 'vue-router';
  import axios from 'axios';

  export default {
    name: 'PaymentDetail',
    setup() {
      const route = useRoute();
      const router = useRouter();
      const paymentId = route.params.id;

      // State
      const payment = ref(null);
      const receivable = ref(null);
      const customer = ref(null);
      const invoice = ref(null);
      const journalEntry = ref(null);
      const isLoading = ref(true);
      const showDeleteModal = ref(false);

      // Computed properties
      const customerId = computed(() => {
        return customer.value ? customer.value.customer_id : null;
      });

      // Methods
      const loadPaymentDetails = async () => {
        isLoading.value = true;
        try {
          // Load payment details
          const response = await axios.get(`/api/accounting/receivable-payments/${paymentId}`);
          payment.value = response.data.data;

          // Load related data if payment exists
          if (payment.value) {
            await Promise.all([
              loadReceivable(),
              loadJournalEntry()
            ]);
          }
        } catch (error) {
          console.error('Error loading payment details:', error);
          payment.value = null;

          if (this.$toast) {
            this.$toast.error('Failed to load payment details');
          }
        } finally {
          isLoading.value = false;
        }
      };

      const loadReceivable = async () => {
        if (!payment.value || !payment.value.receivable_id) return;

        try {
          const response = await axios.get(`/api/accounting/customer-receivables/${payment.value.receivable_id}`);
          receivable.value = response.data.data;

          // Load customer and invoice if receivable exists
          if (receivable.value) {
            if (receivable.value.customer) {
              customer.value = receivable.value.customer;
            } else if (receivable.value.customer_id) {
              // If customer isn't included in the receivable response, fetch it separately
              await loadCustomer(receivable.value.customer_id);
            }

            if (receivable.value.sales_invoice) {
              invoice.value = receivable.value.sales_invoice;
            } else if (receivable.value.invoice_id) {
              // If invoice isn't included in the receivable response, fetch it separately
              await loadInvoice(receivable.value.invoice_id);
            }
          }
        } catch (error) {
          console.error('Error loading receivable details:', error);
          receivable.value = null;
        }
      };

      const loadCustomer = async (customerId) => {
        try {
          const response = await axios.get(`/api/sales/customers/${customerId}`);
          customer.value = response.data.data;
        } catch (error) {
          console.error('Error loading customer details:', error);
          customer.value = null;
        }
      };

      const loadInvoice = async (invoiceId) => {
        try {
          const response = await axios.get(`/api/sales/invoices/${invoiceId}`);
          invoice.value = response.data.data;
        } catch (error) {
          console.error('Error loading invoice details:', error);
          invoice.value = null;
        }
      };

      const loadJournalEntry = async () => {
        if (!payment.value || !payment.value.payment_id) return;

        try {
          // Find journal entry related to this payment
          const response = await axios.get('/api/accounting/journal-entries', {
            params: {
              reference_type: 'ReceivablePayment',
              reference_id: payment.value.payment_id
            }
          });

          if (response.data.data && response.data.data.length > 0) {
            journalEntry.value = response.data.data[0];
          }
        } catch (error) {
          console.error('Error loading journal entry:', error);
          journalEntry.value = null;
        }
      };

      const confirmDelete = () => {
        showDeleteModal.value = true;
      };

      const deletePayment = async () => {
        try {
          await axios.delete(`/api/accounting/receivable-payments/${paymentId}`);

          if (this.$toast) {
            this.$toast.success('Payment has been reversed successfully');
          }

          // Redirect to payments list
          router.push('/accounting/receivable-payments');
        } catch (error) {
          console.error('Error reversing payment:', error);
          if (this.$toast) {
            this.$toast.error('Failed to reverse payment: ' + (error.response?.data?.message || 'Unknown error'));
          }
        } finally {
          showDeleteModal.value = false;
        }
      };

      const applyToInvoice = () => {
        router.push(`/accounting/receivable-payments/${paymentId}/apply`);
      };

      const printPaymentDetails = () => {
        window.print();
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

      const getPaymentMethodClass = (method) => {
        switch (method) {
          case 'Cash': return 'success';
          case 'Bank Transfer': return 'primary';
          case 'Check': return 'warning';
          case 'Credit Card': return 'info';
          case 'Online Payment': return 'secondary';
          default: return 'secondary';
        }
      };

      const getStatusClass = (status) => {
        switch (status) {
          case 'Paid': return 'success';
          case 'Partial': return 'warning';
          case 'Open': return 'danger';
          default: return 'secondary';
        }
      };

      // Lifecycle hooks
      onMounted(() => {
        loadPaymentDetails();
      });

      return {
        payment,
        receivable,
        customer,
        invoice,
        journalEntry,
        customerId,
        isLoading,
        showDeleteModal,
        confirmDelete,
        deletePayment,
        applyToInvoice,
        printPaymentDetails,
        formatDate,
        formatCurrency,
        getPaymentMethodClass,
        getStatusClass
      };
    }
  };
  </script>

  <style scoped>
  .payment-detail {
    padding: 1rem 0;
  }

  .payment-status {
    display: flex;
    align-items: center;
  }

  .badge {
    padding: 0.35rem 0.65rem;
    font-size: 0.875rem;
  }

  .payment-date {
    margin-left: 1rem;
    font-size: 0.875rem;
    color: var(--gray-600);
  }

  .detail-card {
    background-color: var(--gray-50);
    border-radius: 0.5rem;
    padding: 1.25rem;
    height: 100%;
  }

  .card-section-title {
    font-size: 1.125rem;
    color: var(--gray-700);
    margin-bottom: 1.25rem;
    border-bottom: 1px solid var(--gray-200);
    padding-bottom: 0.75rem;
  }

  .detail-table {
    width: 100%;
  }

  .detail-table th {
    width: 40%;
    font-weight: 600;
    padding: 0.5rem 0;
    color: var(--gray-600);
    vertical-align: top;
  }

  .detail-table td {
    padding: 0.5rem 0;
  }

  .amount-value {
    font-weight: 700;
    font-size: 1.125rem;
    color: var(--primary-color);
  }

  .badge-success {
    background-color: var(--success-bg);
    color: var(--success-color);
  }

  .badge-primary {
    background-color: var(--primary-bg);
    color: var(--primary-color);
  }

  .badge-warning {
    background-color: var(--warning-bg);
    color: var(--warning-color);
  }

  .badge-info {
    background-color: #e0f7fa;
    color: #0277bd;
  }

  .badge-danger {
    background-color: var(--danger-bg);
    color: var(--danger-color);
  }

  .badge-secondary {
    background-color: var(--gray-100);
    color: var(--gray-700);
  }

  .actions-section {
    border-top: 1px solid var(--gray-200);
    padding-top: 1.5rem;
  }

  @media print {
    .card-header, .actions-section, .btn {
      display: none !important;
    }
  }
  </style>
