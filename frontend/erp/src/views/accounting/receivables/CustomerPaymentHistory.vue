<!-- src/views/accounting/receivables/CustomerPaymentHistory.vue -->
<template>
    <div class="customer-payment-history">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h3>Customer Payment History</h3>
          <div v-if="customer">
            <router-link :to="`/accounting/receivable-payments/create?customer_id=${customerId}`" class="btn btn-primary">
              <i class="fas fa-plus mr-1"></i> Record New Payment
            </router-link>
          </div>
        </div>

        <div class="card-body">
          <!-- Customer Information Card -->
          <div class="customer-info-card mb-4" v-if="customer">
            <div class="row">
              <div class="col-md-6">
                <div class="customer-profile">
                  <div class="customer-avatar">
                    <i class="fas fa-user"></i>
                  </div>
                  <div class="customer-details">
                    <h4>{{ customer.name }}</h4>
                    <p v-if="customer.code">Customer ID: {{ customer.code }}</p>
                    <p v-if="customer.email"><i class="fas fa-envelope mr-2"></i>{{ customer.email }}</p>
                    <p v-if="customer.phone"><i class="fas fa-phone mr-2"></i>{{ customer.phone }}</p>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="financial-summary">
                  <div class="summary-item">
                    <div class="summary-label">Outstanding Balance</div>
                    <div class="summary-value" :class="{'text-danger': totalOutstanding > 0}">
                      {{ formatCurrency(totalOutstanding) }}
                    </div>
                  </div>
                  <div class="summary-item">
                    <div class="summary-label">Total Paid</div>
                    <div class="summary-value text-success">
                      {{ formatCurrency(totalPaid) }}
                    </div>
                  </div>
                  <div class="summary-item">
                    <div class="summary-label">Total Invoiced</div>
                    <div class="summary-value">
                      {{ formatCurrency(totalInvoiced) }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Loading State -->
          <div v-if="isLoading" class="loading-indicator">
            <i class="fas fa-spinner fa-spin"></i> Loading payment history...
          </div>

          <!-- Empty State -->
          <div v-else-if="!customer" class="empty-state">
            <div class="empty-icon">
              <i class="fas fa-user-slash"></i>
            </div>
            <h3>Customer Not Found</h3>
            <p>The customer information could not be found.</p>
            <router-link to="/sales/customers" class="btn btn-primary">
              <i class="fas fa-users mr-1"></i> View All Customers
            </router-link>
          </div>

          <div v-else-if="payments.length === 0 && receivables.length === 0" class="empty-state">
            <div class="empty-icon">
              <i class="fas fa-file-invoice-dollar"></i>
            </div>
            <h3>No Payment History</h3>
            <p>This customer has no payment history or invoices yet.</p>
          </div>

          <!-- Tabs for different views -->
          <div v-else class="payment-history-content">
            <ul class="nav nav-tabs mb-4">
              <li class="nav-item">
                <a
                  class="nav-link"
                  :class="{ active: activeTab === 'payments' }"
                  href="#"
                  @click.prevent="activeTab = 'payments'"
                >
                  Payments
                </a>
              </li>
              <li class="nav-item">
                <a
                  class="nav-link"
                  :class="{ active: activeTab === 'invoices' }"
                  href="#"
                  @click.prevent="activeTab = 'invoices'"
                >
                  Invoices
                </a>
              </li>
              <li class="nav-item">
                <a
                  class="nav-link"
                  :class="{ active: activeTab === 'timeline' }"
                  href="#"
                  @click.prevent="activeTab = 'timeline'"
                >
                  Activity Timeline
                </a>
              </li>
            </ul>

            <!-- Payments Tab -->
            <div v-show="activeTab === 'payments'" class="tab-content">
              <div v-if="payments.length === 0" class="alert alert-info">
                <i class="fas fa-info-circle mr-2"></i> No payments have been recorded for this customer.
              </div>
              <div v-else class="table-responsive">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>Date</th>
                      <th>Reference #</th>
                      <th>Invoice #</th>
                      <th>Payment Method</th>
                      <th class="text-right">Amount</th>
                      <th class="actions-column">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="payment in payments" :key="payment.payment_id">
                      <td>{{ formatDate(payment.payment_date) }}</td>
                      <td>{{ payment.reference_number }}</td>
                      <td>
                        <router-link
                          v-if="payment.customer_receivable?.sales_invoice"
                          :to="`/sales/invoices/${payment.customer_receivable.invoice_id}`"
                        >
                          {{ payment.customer_receivable.sales_invoice.invoice_number || `#${payment.customer_receivable.invoice_id}` }}
                        </router-link>
                        <span v-else>-</span>
                      </td>
                      <td>
                        <span :class="'badge-' + getPaymentMethodClass(payment.payment_method)" class="badge">
                          {{ payment.payment_method }}
                        </span>
                      </td>
                      <td class="text-right">{{ formatCurrency(payment.amount) }}</td>
                      <td class="actions-cell">
                        <router-link
                          :to="`/accounting/receivable-payments/${payment.payment_id}`"
                          class="btn btn-sm btn-info"
                        >
                          <i class="fas fa-eye"></i>
                        </router-link>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Invoices Tab -->
            <div v-show="activeTab === 'invoices'" class="tab-content">
              <div v-if="receivables.length === 0" class="alert alert-info">
                <i class="fas fa-info-circle mr-2"></i> No invoices have been created for this customer.
              </div>
              <div v-else class="table-responsive">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>Invoice #</th>
                      <th>Due Date</th>
                      <th class="text-right">Total Amount</th>
                      <th class="text-right">Paid Amount</th>
                      <th class="text-right">Balance</th>
                      <th>Status</th>
                      <th class="actions-column">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="receivable in receivables" :key="receivable.receivable_id">
                      <td>
                        <router-link
                          v-if="receivable.sales_invoice"
                          :to="`/sales/invoices/${receivable.invoice_id}`"
                        >
                          {{ receivable.sales_invoice.invoice_number || `#${receivable.invoice_id}` }}
                        </router-link>
                        <span v-else>{{ `#${receivable.invoice_id}` }}</span>
                      </td>
                      <td>{{ formatDate(receivable.due_date) }}</td>
                      <td class="text-right">{{ formatCurrency(receivable.amount) }}</td>
                      <td class="text-right">{{ formatCurrency(receivable.paid_amount) }}</td>
                      <td class="text-right" :class="{'text-danger': receivable.balance > 0}">
                        {{ formatCurrency(receivable.balance) }}
                      </td>
                      <td>
                        <span :class="'badge-' + getStatusClass(receivable.status)" class="badge">
                          {{ receivable.status }}
                        </span>
                      </td>
                      <td class="actions-cell">
                        <router-link
                          :to="`/accounting/receivable-payments/create?receivable_id=${receivable.receivable_id}`"
                          class="btn btn-sm btn-primary"
                          v-if="receivable.status !== 'Paid'"
                        >
                          <i class="fas fa-money-bill-wave"></i>
                        </router-link>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Activity Timeline Tab -->
            <div v-show="activeTab === 'timeline'" class="tab-content">
              <div class="activity-timeline">
                <div v-for="(activity, index) in timelineActivities" :key="index" class="timeline-item">
                  <div class="timeline-icon" :class="getActivityIconClass(activity.type)">
                    <i :class="getActivityIcon(activity.type)"></i>
                  </div>
                  <div class="timeline-content">
                    <div class="timeline-time">{{ formatDate(activity.date) }}</div>
                    <div class="timeline-title">{{ activity.title }}</div>
                    <div class="timeline-description">{{ activity.description }}</div>
                    <div class="timeline-amount">{{ formatCurrency(activity.amount) }}</div>
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
  import { ref, computed, onMounted, watch } from 'vue';
  import { useRoute } from 'vue-router';
  import axios from 'axios';

  export default {
    name: 'CustomerPaymentHistory',
    setup() {
      const route = useRoute();
      //const router = useRouter();
      const customerId = route.params.id;

      // State
      const customer = ref(null);
      const payments = ref([]);
      const receivables = ref([]);
      const isLoading = ref(true);
      const activeTab = ref('payments');

      // Computed properties
      const totalOutstanding = computed(() => {
        if (!receivables.value || receivables.value.length === 0) return 0;
        return receivables.value.reduce((sum, receivable) => sum + (receivable.balance || 0), 0);
      });

      const totalPaid = computed(() => {
        if (!receivables.value || receivables.value.length === 0) return 0;
        return receivables.value.reduce((sum, receivable) => sum + (receivable.paid_amount || 0), 0);
      });

      const totalInvoiced = computed(() => {
        if (!receivables.value || receivables.value.length === 0) return 0;
        return receivables.value.reduce((sum, receivable) => sum + (receivable.amount || 0), 0);
      });

      // Timeline activities (combines payments and invoices)
      const timelineActivities = computed(() => {
        const activities = [];

        // Add payments to timeline
        payments.value.forEach(payment => {
          activities.push({
            type: 'payment',
            date: payment.payment_date,
            title: `Payment Received`,
            description: `Payment via ${payment.payment_method} (${payment.reference_number})`,
            amount: payment.amount,
            id: payment.payment_id
          });
        });

        // Add invoices to timeline
        receivables.value.forEach(receivable => {
          activities.push({
            type: 'invoice',
            date: receivable.due_date, // Using due date for invoices
            title: `Invoice ${receivable.sales_invoice?.invoice_number || `#${receivable.invoice_id}`}`,
            description: `Invoice created with due date ${formatDate(receivable.due_date)}`,
            amount: receivable.amount,
            id: receivable.receivable_id
          });
        });

        // Sort by date (newest first)
        return activities.sort((a, b) => new Date(b.date) - new Date(a.date));
      });

      // Methods
      const loadCustomer = async () => {
        try {
          const response = await axios.get(`/api/sales/customers/${customerId}`);
          customer.value = response.data.data;
        } catch (error) {
          console.error('Error loading customer:', error);
          customer.value = null;
          if (this.$toast) {
            this.$toast.error('Failed to load customer details');
          }
        }
      };

      const loadPaymentHistory = async () => {
        try {
          // Load customer payments
          const paymentsResponse = await axios.get('/api/accounting/receivable-payments', {
            params: { customer_id: customerId }
          });
          payments.value = paymentsResponse.data.data || [];

          // Load customer receivables
          const receivablesResponse = await axios.get('/api/accounting/customer-receivables', {
            params: { customer_id: customerId }
          });
          receivables.value = receivablesResponse.data.data || [];
        } catch (error) {
          console.error('Error loading payment history:', error);
          if (this.$toast) {
            this.$toast.error('Failed to load payment history');
          }
        }
      };

      const loadData = async () => {
        isLoading.value = true;
        try {
          await Promise.all([
            loadCustomer(),
            loadPaymentHistory()
          ]);
        } catch (error) {
          console.error('Error loading data:', error);
        } finally {
          isLoading.value = false;
        }
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

      const getStatusClass = (status) => {
        switch (status) {
          case 'Paid': return 'success';
          case 'Partial': return 'warning';
          case 'Open': return 'danger';
          default: return 'secondary';
        }
      };

      const getActivityIcon = (type) => {
        switch (type) {
          case 'payment': return 'fas fa-money-bill-wave';
          case 'invoice': return 'fas fa-file-invoice';
          default: return 'fas fa-calendar';
        }
      };

      const getActivityIconClass = (type) => {
        switch (type) {
          case 'payment': return 'bg-success';
          case 'invoice': return 'bg-primary';
          default: return 'bg-secondary';
        }
      };

      // Watch for customer ID changes (in case of route change)
      watch(() => route.params.id, (newId) => {
        if (newId && newId !== customerId.value) {
          loadData();
        }
      });

      // Lifecycle hooks
      onMounted(() => {
        loadData();
      });

      return {
        customerId,
        customer,
        payments,
        receivables,
        isLoading,
        activeTab,
        totalOutstanding,
        totalPaid,
        totalInvoiced,
        timelineActivities,
        formatDate,
        formatCurrency,
        getPaymentMethodClass,
        getStatusClass,
        getActivityIcon,
        getActivityIconClass
      };
    }
  };
  </script>

  <style scoped>
  .customer-payment-history {
    padding: 1rem 0;
  }

  .customer-info-card {
    background-color: var(--gray-50);
    border-radius: 0.5rem;
    padding: 1.5rem;
    margin-bottom: 2rem;
  }

  .customer-profile {
    display: flex;
    align-items: center;
  }

  .customer-avatar {
    width: 60px;
    height: 60px;
    background-color: var(--primary-bg);
    color: var(--primary-color);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-right: 1.5rem;
  }

  .customer-details h4 {
    margin-bottom: 0.5rem;
    font-size: 1.25rem;
  }

  .customer-details p {
    margin-bottom: 0.25rem;
    color: var(--gray-600);
    font-size: 0.875rem;
  }

  .financial-summary {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    height: 100%;
    justify-content: center;
  }

  .summary-item {
    background-color: white;
    padding: 1rem;
    border-radius: 0.375rem;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
  }

  .summary-label {
    font-size: 0.75rem;
    color: var(--gray-500);
    text-transform: uppercase;
    letter-spacing: 0.025em;
  }

  .summary-value {
    font-size: 1.25rem;
    font-weight: 600;
    margin-top: 0.25rem;
  }

  .payment-history-content {
    margin-top: 1rem;
  }

  .nav-tabs {
    border-bottom: 1px solid var(--gray-200);
  }

  .nav-tabs .nav-link {
    color: var(--gray-600);
    border: none;
    padding: 0.75rem 1rem;
    font-weight: 500;
  }

  .nav-tabs .nav-link.active {
    color: var(--primary-color);
    border-bottom: 2px solid var(--primary-color);
    background-color: transparent;
  }

  .tab-content {
    padding: 1.5rem 0;
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

  .badge-danger {
    background-color: var(--danger-bg);
    color: var(--danger-color);
  }

  .badge-info {
    background-color: #e0f7fa;
    color: #0277bd;
  }

  .badge-secondary {
    background-color: var(--gray-100);
    color: var(--gray-700);
  }

  .activity-timeline {
    position: relative;
    margin: 2rem 0;
    padding-left: 2rem;
  }

  .activity-timeline:before {
    content: '';
    position: absolute;
    top: 0;
    bottom: 0;
    left: 8px;
    width: 2px;
    background-color: var(--gray-200);
    z-index: 1;
  }

  .timeline-item {
    position: relative;
    margin-bottom: 2rem;
  }

  .timeline-item:last-child {
    margin-bottom: 0;
  }

  .timeline-icon {
    position: absolute;
    left: -2rem;
    width: 2rem;
    height: 2rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    z-index: 2;
  }

  .timeline-icon.bg-success {
    background-color: var(--success-color);
  }

  .timeline-icon.bg-primary {
    background-color: var(--primary-color);
  }

  .timeline-icon.bg-secondary {
    background-color: var(--gray-500);
  }

  .timeline-content {
    background-color: white;
    border-radius: 0.5rem;
    padding: 1rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  }

  .timeline-time {
    font-size: 0.75rem;
    color: var(--gray-500);
    margin-bottom: 0.5rem;
  }

  .timeline-title {
    font-weight: 600;
    margin-bottom: 0.25rem;
  }

  .timeline-description {
    font-size: 0.875rem;
    color: var(--gray-600);
    margin-bottom: 0.5rem;
  }

  .timeline-amount {
    font-weight: 700;
    color: var(--primary-color);
  }

  @media (max-width: 768px) {
    .customer-profile {
      flex-direction: column;
      text-align: center;
      margin-bottom: 1.5rem;
    }

    .customer-avatar {
      margin-right: 0;
      margin-bottom: 1rem;
    }

    .financial-summary {
      flex-direction: row;
      flex-wrap: wrap;
      gap: 0.5rem;
    }

    .summary-item {
      flex: 1 1 45%;
    }
  }
  </style>
