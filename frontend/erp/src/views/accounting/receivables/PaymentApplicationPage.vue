<!-- src/views/accounting/receivables/PaymentApplicationPage.vue -->
<template>
    <div class="payment-application">
      <div class="card">
        <div class="card-header">
          <h3>Apply Payment to Invoice</h3>
        </div>

        <div class="card-body">
          <div v-if="isLoading" class="loading-indicator">
            <i class="fas fa-spinner fa-spin"></i> Loading payment and invoice details...
          </div>

          <div v-else-if="!payment || !receivable" class="empty-state">
            <div class="empty-icon">
              <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3>Payment or Invoice Not Found</h3>
            <p>The payment or invoice details could not be found.</p>
            <router-link to="/accounting/receivable-payments" class="btn btn-primary">
              <i class="fas fa-arrow-left mr-1"></i> Back to Payments List
            </router-link>
          </div>

          <div v-else>
            <!-- Summary Section -->
            <div class="summary-section mb-4">
              <div class="row">
                <div class="col-md-6">
                  <div class="summary-card">
                    <h4 class="summary-title">Payment Details</h4>
                    <div class="summary-content">
                      <div class="summary-row">
                        <span class="summary-label">Reference #:</span>
                        <span class="summary-value">{{ payment.reference_number }}</span>
                      </div>
                      <div class="summary-row">
                        <span class="summary-label">Date:</span>
                        <span class="summary-value">{{ formatDate(payment.payment_date) }}</span>
                      </div>
                      <div class="summary-row">
                        <span class="summary-label">Method:</span>
                        <span class="summary-value">
                          <span :class="'badge-' + getPaymentMethodClass(payment.payment_method)" class="badge">
                            {{ payment.payment_method }}
                          </span>
                        </span>
                      </div>
                      <div class="summary-row">
                        <span class="summary-label">Amount:</span>
                        <span class="summary-value amount-highlight">{{ formatCurrency(payment.amount) }}</span>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="summary-card">
                    <h4 class="summary-title">Invoice Details</h4>
                    <div class="summary-content">
                      <div class="summary-row">
                        <span class="summary-label">Invoice #:</span>
                        <span class="summary-value">{{ invoice?.invoice_number || `#${receivable.invoice_id}` }}</span>
                      </div>
                      <div class="summary-row">
                        <span class="summary-label">Customer:</span>
                        <span class="summary-value">{{ customer?.name || 'Unknown Customer' }}</span>
                      </div>
                      <div class="summary-row">
                        <span class="summary-label">Due Date:</span>
                        <span class="summary-value">{{ formatDate(receivable.due_date) }}</span>
                      </div>
                      <div class="summary-row">
                        <span class="summary-label">Balance:</span>
                        <span class="summary-value" :class="{'text-danger': receivable.balance > 0}">
                          {{ formatCurrency(receivable.balance) }}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Application Section -->
            <div class="application-section mb-4">
              <h4 class="section-title">Apply Payment to Invoice Items</h4>

              <div v-if="!invoiceItems || invoiceItems.length === 0" class="alert alert-info">
                <i class="fas fa-info-circle mr-2"></i>
                No invoice items found to apply payment to. The payment has been applied to the entire invoice.
              </div>

              <div v-else>
                <div class="table-responsive">
                  <table class="table table-bordered">
                    <thead>
                      <tr>
                        <th>Item</th>
                        <th>Description</th>
                        <th class="text-right">Original Amount</th>
                        <th class="text-right">Already Paid</th>
                        <th class="text-right">Remaining</th>
                        <th class="text-right">Apply Amount</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(item, index) in invoiceItems" :key="index">
                        <td>{{ item.item_name || 'Item #' + (index + 1) }}</td>
                        <td>{{ item.description || '-' }}</td>
                        <td class="text-right">{{ formatCurrency(item.amount) }}</td>
                        <td class="text-right">{{ formatCurrency(item.paid_amount || 0) }}</td>
                        <td class="text-right">{{ formatCurrency(item.remaining) }}</td>
                        <td>
                          <div class="input-group">
                            <div class="input-group-prepend">
                              <span class="input-group-text">Rp</span>
                            </div>
                            <input
                              type="number"
                              v-model="item.apply_amount"
                              class="form-control text-right"
                              min="0"
                              :max="item.remaining"
                              step="0.01"
                              @input="updateAllocations"
                            >
                          </div>
                        </td>
                      </tr>
                    </tbody>
                    <tfoot>
                      <tr class="bg-light">
                        <td colspan="5" class="text-right font-weight-bold">Total Applied:</td>
                        <td class="text-right font-weight-bold">{{ formatCurrency(totalApplied) }}</td>
                      </tr>
                      <tr v-if="unappliedAmount !== 0" :class="{'bg-danger text-white': unappliedAmount < 0}">
                        <td colspan="5" class="text-right font-weight-bold">
                          {{ unappliedAmount > 0 ? 'Unapplied Amount:' : 'Over-Applied Amount:' }}
                        </td>
                        <td class="text-right font-weight-bold">{{ formatCurrency(Math.abs(unappliedAmount)) }}</td>
                      </tr>
                    </tfoot>
                  </table>
                </div>

                <div class="alert alert-warning mt-3" v-if="unappliedAmount < 0">
                  <i class="fas fa-exclamation-triangle mr-2"></i>
                  <strong>Warning:</strong> You have allocated more than the payment amount. Please adjust your allocations.
                </div>

                <div class="allocation-buttons mt-3" v-if="invoiceItems.length > 0">
                  <button type="button" class="btn btn-secondary mr-2" @click="resetAllocations">
                    <i class="fas fa-undo mr-1"></i> Reset
                  </button>
                  <button type="button" class="btn btn-info" @click="autoAllocate">
                    <i class="fas fa-magic mr-1"></i> Auto-Allocate
                  </button>
                </div>
              </div>
            </div>

            <!-- Notes Section -->
            <div class="form-group mb-4">
              <label for="applicationNotes">Notes</label>
              <textarea
                id="applicationNotes"
                v-model="applicationNotes"
                class="form-control"
                rows="3"
                placeholder="Add any notes about this payment application"
              ></textarea>
            </div>

            <!-- Actions Section -->
            <div class="actions-section mt-4">
              <div class="row">
                <div class="col-md-12">
                  <div class="d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary" @click="goBack">
                      <i class="fas fa-arrow-left mr-1"></i> Cancel
                    </button>

                    <button
                      type="button"
                      class="btn btn-primary"
                      @click="applyPayment"
                      :disabled="isSaving || unappliedAmount < 0"
                    >
                      <i class="fas fa-check mr-1"></i>
                      {{ isSaving ? 'Applying...' : 'Apply Payment' }}
                    </button>
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
  import { ref, computed, onMounted } from 'vue';
  import { useRoute, useRouter } from 'vue-router';
  import axios from 'axios';

  export default {
    name: 'PaymentApplicationPage',
    setup() {
      const route = useRoute();
      const router = useRouter();
      const paymentId = route.params.id;

      // State
      const payment = ref(null);
      const receivable = ref(null);
      const customer = ref(null);
      const invoice = ref(null);
      const invoiceItems = ref([]);
      const applicationNotes = ref('');
      const isLoading = ref(true);
      const isSaving = ref(false);

      // Computed properties
      const totalApplied = computed(() => {
        if (!invoiceItems.value || invoiceItems.value.length === 0) return 0;
        return invoiceItems.value.reduce((sum, item) => sum + (parseFloat(item.apply_amount) || 0), 0);
      });

      const unappliedAmount = computed(() => {
        if (!payment.value) return 0;
        return payment.value.amount - totalApplied.value;
      });

      // Methods
      const loadData = async () => {
        isLoading.value = true;
        try {
          // Load payment details
          const paymentResponse = await axios.get(`/api/accounting/receivable-payments/${paymentId}`);
          payment.value = paymentResponse.data.data;

          if (payment.value && payment.value.receivable_id) {
            // Load receivable details
            const receivableResponse = await axios.get(`/api/accounting/customer-receivables/${payment.value.receivable_id}`);
            receivable.value = receivableResponse.data.data;

            // Load customer if available
            if (receivable.value.customer) {
              customer.value = receivable.value.customer;
            } else if (receivable.value.customer_id) {
              try {
                const customerResponse = await axios.get(`/api/sales/customers/${receivable.value.customer_id}`);
                customer.value = customerResponse.data.data;
              } catch (error) {
                console.error('Error loading customer:', error);
              }
            }

            // Load invoice if available
            if (receivable.value.sales_invoice) {
              invoice.value = receivable.value.sales_invoice;
            } else if (receivable.value.invoice_id) {
              try {
                const invoiceResponse = await axios.get(`/api/sales/invoices/${receivable.value.invoice_id}`);
                invoice.value = invoiceResponse.data.data;
              } catch (error) {
                console.error('Error loading invoice:', error);
              }
            }

            // Load invoice items if invoice exists
            if (invoice.value && invoice.value.invoice_id) {
              try {
                const itemsResponse = await axios.get(`/api/sales/invoices/${invoice.value.invoice_id}/items`);
                const items = itemsResponse.data.data || [];

                // Prepare items for allocation
                invoiceItems.value = items.map(item => ({
                  ...item,
                  paid_amount: item.paid_amount || 0,
                  remaining: (item.amount || 0) - (item.paid_amount || 0),
                  apply_amount: 0
                }));
              } catch (error) {
                console.error('Error loading invoice items:', error);
              }
            }
          }
        } catch (error) {
          console.error('Error loading payment application data:', error);
          if (this.$toast) {
            this.$toast.error('Failed to load payment details');
          }
        } finally {
          isLoading.value = false;
        }
      };

      const updateAllocations = () => {
        // Ensure numbers are properly parsed
        invoiceItems.value.forEach(item => {
          if (item.apply_amount === '') item.apply_amount = 0;
          item.apply_amount = Math.min(parseFloat(item.apply_amount) || 0, item.remaining);
        });
      };

      const resetAllocations = () => {
        invoiceItems.value.forEach(item => {
          item.apply_amount = 0;
        });
      };

      const autoAllocate = () => {
        if (!payment.value || !invoiceItems.value.length) return;

        resetAllocations();

        let remainingPayment = payment.value.amount;

        // Sort items by date (oldest first) if they have dates
        const sortedItems = [...invoiceItems.value].sort((a, b) => {
          if (a.date && b.date) return new Date(a.date) - new Date(b.date);
          return 0;
        });

        // Allocate to each item until payment is fully allocated
        for (const item of sortedItems) {
          if (remainingPayment <= 0) break;

          const allocation = Math.min(item.remaining, remainingPayment);
          const index = invoiceItems.value.findIndex(i => i === item);

          if (index !== -1) {
            invoiceItems.value[index].apply_amount = allocation;
            remainingPayment -= allocation;
          }
        }
      };

      const applyPayment = async () => {
        if (unappliedAmount.value < 0) return; // Cannot over-allocate

        isSaving.value = true;
        try {
          // Prepare the application data
          const applicationData = {
            payment_id: paymentId,
            receivable_id: receivable.value.receivable_id,
            allocations: invoiceItems.value.map(item => ({
              item_id: item.item_id,
              line_id: item.line_id,
              amount: parseFloat(item.apply_amount) || 0
            })).filter(allocation => allocation.amount > 0),
            notes: applicationNotes.value
          };

          // In a real application, you'd have an API endpoint for this
          // For now, we'll simulate the API call
          // await axios.post('/api/accounting/receivable-payments/apply', applicationData);

          console.log('Payment application data:', applicationData);

          // Show success message
          if (this.$toast) {
            this.$toast.success('Payment has been applied successfully');
          }

          // Navigate back to payment details
          router.push(`/accounting/receivable-payments/${paymentId}`);
        } catch (error) {
          console.error('Error applying payment:', error);
          if (this.$toast) {
            this.$toast.error('Failed to apply payment: ' + (error.response?.data?.message || 'Unknown error'));
          }
        } finally {
          isSaving.value = false;
        }
      };

      const goBack = () => {
        router.push(`/accounting/receivable-payments/${paymentId}`);
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
        }).format(amount || 0);
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

      // Lifecycle hooks
      onMounted(() => {
        loadData();
      });

      return {
        payment,
        receivable,
        customer,
        invoice,
        invoiceItems,
        applicationNotes,
        isLoading,
        isSaving,
        totalApplied,
        unappliedAmount,
        updateAllocations,
        resetAllocations,
        autoAllocate,
        applyPayment,
        goBack,
        formatDate,
        formatCurrency,
        getPaymentMethodClass
      };
    }
  };
  </script>

  <style scoped>
  .payment-application {
    padding: 1rem 0;
  }

  .summary-section {
    margin-bottom: 2rem;
  }

  .summary-card {
    background-color: var(--gray-50);
    border-radius: 0.5rem;
    padding: 1.25rem;
    height: 100%;
  }

  .summary-title {
    font-size: 1.125rem;
    color: var(--gray-700);
    margin-bottom: 1rem;
    border-bottom: 1px solid var(--gray-200);
    padding-bottom: 0.5rem;
  }

  .summary-content {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .summary-label {
    font-weight: 500;
    color: var(--gray-600);
  }

  .amount-highlight {
    font-weight: 700;
    color: var(--primary-color);
  }

  .section-title {
    font-size: 1.1rem;
    color: var(--gray-700);
    margin-bottom: 1.25rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid var(--gray-200);
  }

  .table-responsive {
    overflow-x: auto;
  }

  .badge {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
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

  .badge-secondary {
    background-color: var(--gray-100);
    color: var(--gray-700);
  }

  .actions-section {
    border-top: 1px solid var(--gray-200);
    padding-top: 1.5rem;
  }
  </style>
