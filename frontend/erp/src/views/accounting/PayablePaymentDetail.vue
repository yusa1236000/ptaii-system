<!-- src/views/accounting/PayablePaymentDetail.vue -->
<template>
    <div class="payment-detail">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h3>Payment Details</h3>
          <div>
            <button @click="printPaymentDetail" class="btn btn-info mr-2">
              <i class="fas fa-print mr-1"></i> Print
            </button>
            <button v-if="canDelete" @click="confirmDelete" class="btn btn-danger">
              <i class="fas fa-trash mr-1"></i> Delete
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
            <p>The requested payment could not be found.</p>
            <router-link to="/accounting/payable-payments" class="btn btn-primary">
              <i class="fas fa-arrow-left mr-1"></i> Back to Payments
            </router-link>
          </div>

          <div v-else>
            <!-- Payment Header Information -->
            <div class="row mb-4">
              <div class="col-md-6">
                <div class="info-card">
                  <h4 class="mb-3">Payment Information</h4>
                  <div class="detail-row">
                    <div class="detail-label">Payment Date:</div>
                    <div class="detail-value">{{ formatDate(payment.payment_date) }}</div>
                  </div>
                  <div class="detail-row">
                    <div class="detail-label">Reference Number:</div>
                    <div class="detail-value">{{ payment.reference_number }}</div>
                  </div>
                  <div class="detail-row">
                    <div class="detail-label">Payment Method:</div>
                    <div class="detail-value">{{ payment.payment_method }}</div>
                  </div>
                  <div class="detail-row">
                    <div class="detail-label">Amount:</div>
                    <div class="detail-value amount">{{ formatCurrency(payment.amount) }}</div>
                  </div>
                </div>
              </div>

              <div class="col-md-6">
                <div class="info-card">
                  <h4 class="mb-3">Vendor Information</h4>
                  <div v-if="payment.vendor_payable && payment.vendor_payable.vendor">
                    <div class="detail-row">
                      <div class="detail-label">Vendor:</div>
                      <div class="detail-value">{{ payment.vendor_payable.vendor.name }}</div>
                    </div>
                    <div class="detail-row">
                      <div class="detail-label">Vendor Code:</div>
                      <div class="detail-value">{{ payment.vendor_payable.vendor.vendor_code || '-' }}</div>
                    </div>
                    <div class="detail-row">
                      <div class="detail-label">Contact Email:</div>
                      <div class="detail-value">{{ payment.vendor_payable.vendor.email || '-' }}</div>
                    </div>
                    <div class="detail-row">
                      <div class="detail-label">Contact Phone:</div>
                      <div class="detail-value">{{ payment.vendor_payable.vendor.phone || '-' }}</div>
                    </div>
                  </div>
                  <div v-else class="text-muted">
                    Vendor information not available
                  </div>
                </div>
              </div>
            </div>

            <!-- Invoice Information -->
            <div class="row mb-4">
              <div class="col-12">
                <div class="info-card">
                  <h4 class="mb-3">Invoice Information</h4>
                  <div v-if="payment.vendor_payable && payment.vendor_payable.vendor_invoice">
                    <div class="row">
                      <div class="col-md-6">
                        <div class="detail-row">
                          <div class="detail-label">Invoice Number:</div>
                          <div class="detail-value">{{ payment.vendor_payable.vendor_invoice.invoice_number }}</div>
                        </div>
                        <div class="detail-row">
                          <div class="detail-label">Invoice Date:</div>
                          <div class="detail-value">{{ formatDate(payment.vendor_payable.vendor_invoice.invoice_date) }}</div>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="detail-row">
                          <div class="detail-label">Invoice Amount:</div>
                          <div class="detail-value">{{ formatCurrency(payment.vendor_payable.amount) }}</div>
                        </div>
                        <div class="detail-row">
                          <div class="detail-label">Balance After Payment:</div>
                          <div class="detail-value">{{ formatCurrency(payment.vendor_payable.balance) }}</div>
                        </div>
                        <div class="detail-row">
                          <div class="detail-label">Status:</div>
                          <div class="detail-value">
                            <span
                              :class="{
                                'badge': true,
                                'badge-success': payment.vendor_payable.status === 'Paid',
                                'badge-warning': payment.vendor_payable.status === 'Partial',
                                'badge-primary': payment.vendor_payable.status === 'Open'
                              }"
                            >
                              {{ payment.vendor_payable.status }}
                            </span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div v-else class="text-muted">
                    Invoice information not available
                  </div>
                </div>
              </div>
            </div>

            <!-- Journal Entry Information -->
            <div class="row mb-4">
              <div class="col-12">
                <div class="info-card">
                  <h4 class="mb-3">Journal Entry</h4>
                  <div v-if="journalEntry">
                    <div class="journal-header mb-3">
                      <div class="row">
                        <div class="col-md-4">
                          <div class="detail-row">
                            <div class="detail-label">Journal Number:</div>
                            <div class="detail-value">{{ journalEntry.journal_number }}</div>
                          </div>
                        </div>
                        <div class="col-md-4">
                          <div class="detail-row">
                            <div class="detail-label">Entry Date:</div>
                            <div class="detail-value">{{ formatDate(journalEntry.entry_date) }}</div>
                          </div>
                        </div>
                        <div class="col-md-4">
                          <div class="detail-row">
                            <div class="detail-label">Status:</div>
                            <div class="detail-value">
                              <span
                                :class="{
                                  'badge': true,
                                  'badge-success': journalEntry.status === 'Posted',
                                  'badge-warning': journalEntry.status !== 'Posted'
                                }"
                              >
                                {{ journalEntry.status }}
                              </span>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>

                    <div class="table-responsive">
                      <table class="table table-bordered table-striped">
                        <thead class="bg-light">
                          <tr>
                            <th>Account</th>
                            <th class="text-right">Debit</th>
                            <th class="text-right">Credit</th>
                            <th>Description</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="line in journalEntry.journal_entry_lines" :key="line.line_id">
                            <td>
                              <div v-if="line.chart_of_account">
                                {{ line.chart_of_account.account_code }} - {{ line.chart_of_account.name }}
                              </div>
                              <div v-else>Unknown Account</div>
                            </td>
                            <td class="text-right">{{ formatCurrency(line.debit_amount) }}</td>
                            <td class="text-right">{{ formatCurrency(line.credit_amount) }}</td>
                            <td>{{ line.description || '-' }}</td>
                          </tr>
                        </tbody>
                        <tfoot class="bg-light">
                          <tr>
                            <th>Total</th>
                            <th class="text-right">{{ formatCurrency(calculateTotalDebit()) }}</th>
                            <th class="text-right">{{ formatCurrency(calculateTotalCredit()) }}</th>
                            <th></th>
                          </tr>
                        </tfoot>
                      </table>
                    </div>

                    <div class="text-right mt-2">
                      <router-link :to="`/accounting/journal-entries/${journalEntry.journal_id}`" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-external-link-alt mr-1"></i> View Full Journal Entry
                      </router-link>
                    </div>
                  </div>
                  <div v-else class="text-muted">
                    No journal entry was created for this payment
                  </div>
                </div>
              </div>
            </div>

            <!-- Navigation Buttons -->
            <div class="mt-4">
              <router-link to="/accounting/payable-payments" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Back to Payments
              </router-link>
              <router-link
                v-if="payment.vendor_payable && payment.vendor_payable.vendor_id"
                :to="`/accounting/vendor-payments/${payment.vendor_payable.vendor_id}`"
                class="btn btn-outline-primary ml-2"
              >
                <i class="fas fa-history mr-1"></i> View Vendor Payment History
              </router-link>
            </div>
          </div>
        </div>
      </div>

      <!-- Delete Confirmation Modal -->
      <ConfirmationModal
        v-if="showDeleteModal"
        title="Delete Payment"
        :message="`Are you sure you want to delete this payment?<br>This action cannot be undone and may affect accounting balances.`"
        confirm-button-text="Delete"
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
    name: 'PayablePaymentDetail',
    setup() {
      const route = useRoute();
      const router = useRouter();
      const paymentId = route.params.id;

      // State
      const payment = ref(null);
      const journalEntry = ref(null);
      const isLoading = ref(true);
      const showDeleteModal = ref(false);
      const isDeleting = ref(false);

      // Computed properties
      const canDelete = computed(() => {
        return payment.value &&
               (!journalEntry.value || journalEntry.value.status !== 'Posted');
      });

      // Methods
      const loadPaymentDetails = async () => {
        isLoading.value = true;
        try {
          const response = await axios.get(`/api/accounting/payable-payments/${paymentId}`);
          payment.value = response.data.data;

          // Try to load associated journal entry if it exists
          await loadJournalEntry();
        } catch (error) {
          console.error('Error loading payment details:', error);
          payment.value = null;
        } finally {
          isLoading.value = false;
        }
      };

      const loadJournalEntry = async () => {
        try {
          // Find journal entry by reference to this payment
          const response = await axios.get('/api/accounting/journal-entries', {
            params: {
              reference_type: 'PayablePayment',
              reference_id: paymentId
            }
          });

          if (response.data.data && response.data.data.length > 0) {
            // Get full journal entry details
            const entryResponse = await axios.get(`/api/accounting/journal-entries/${response.data.data[0].journal_id}`);
            journalEntry.value = entryResponse.data.data;
          }
        } catch (error) {
          console.error('Error loading journal entry:', error);
          journalEntry.value = null;
        }
      };

      const calculateTotalDebit = () => {
        if (!journalEntry.value || !journalEntry.value.journal_entry_lines) return 0;

        return journalEntry.value.journal_entry_lines.reduce((sum, line) => {
          return sum + parseFloat(line.debit_amount || 0);
        }, 0);
      };

      const calculateTotalCredit = () => {
        if (!journalEntry.value || !journalEntry.value.journal_entry_lines) return 0;

        return journalEntry.value.journal_entry_lines.reduce((sum, line) => {
          return sum + parseFloat(line.credit_amount || 0);
        }, 0);
      };

      const confirmDelete = () => {
        showDeleteModal.value = true;
      };

      const deletePayment = async () => {
        isDeleting.value = true;
        try {
          await axios.delete(`/api/accounting/payable-payments/${paymentId}`);
          // Redirect back to list after successful deletion
          router.push({
            path: '/accounting/payable-payments',
            query: { delete: 'success' }
          });
        } catch (error) {
          console.error('Error deleting payment:', error);
          // TODO: Show error notification
        } finally {
          isDeleting.value = false;
          showDeleteModal.value = false;
        }
      };

      const printPaymentDetail = () => {
        window.print();
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
      onMounted(() => {
        loadPaymentDetails();
      });

      return {
        payment,
        journalEntry,
        isLoading,
        showDeleteModal,
        isDeleting,
        canDelete,
        confirmDelete,
        deletePayment,
        calculateTotalDebit,
        calculateTotalCredit,
        printPaymentDetail,
        formatDate,
        formatCurrency
      };
    }
  };
  </script>

  <style scoped>
  .payment-detail {
    padding: 1rem 0;
  }

  .info-card {
    background-color: var(--gray-50);
    border-radius: 0.5rem;
    padding: 1.5rem;
    height: 100%;
  }

  .detail-row {
    display: flex;
    margin-bottom: 0.75rem;
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

  .badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
  }

  .journal-header {
    background-color: var(--gray-100);
    padding: 1rem;
    border-radius: 0.375rem;
    margin-bottom: 1rem;
  }

  @media print {
    .card-header,
    .btn,
    .actions-column,
    .actions-cell {
      display: none !important;
    }

    .card {
      border: none !important;
      box-shadow: none !important;
    }

    .info-card {
      background-color: white !important;
      padding: 0 !important;
    }
  }
  </style>
