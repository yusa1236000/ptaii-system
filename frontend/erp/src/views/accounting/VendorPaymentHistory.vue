<!-- src/views/accounting/VendorPaymentHistory.vue -->
<template>
    <div class="vendor-payment-history">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h3>{{ vendor ? `Payment History: ${vendor.name}` : 'Vendor Payment History' }}</h3>
          <div>
            <router-link to="/accounting/payable-payments/create" class="btn btn-primary mr-2">
              <i class="fas fa-plus mr-1"></i> Record Payment
            </router-link>
            <router-link to="/accounting/payable-payments" class="btn btn-secondary">
              <i class="fas fa-arrow-left mr-1"></i> Back to Payments
            </router-link>
          </div>
        </div>

        <div class="card-body">
          <div v-if="isLoading" class="loading-indicator">
            <i class="fas fa-spinner fa-spin"></i> Loading payment history...
          </div>

          <div v-else-if="!vendor" class="empty-state">
            <div class="empty-icon">
              <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3>Vendor Not Found</h3>
            <p>The requested vendor could not be found.</p>
            <router-link to="/accounting/payable-payments" class="btn btn-primary">
              <i class="fas fa-arrow-left mr-1"></i> Back to Payments
            </router-link>
          </div>

          <div v-else>
            <!-- Vendor Information -->
            <div class="vendor-info mb-4">
              <div class="row">
                <div class="col-md-6">
                  <div class="info-card">
                    <h4 class="mb-3">Vendor Information</h4>
                    <div class="detail-row">
                      <div class="detail-label">Vendor Name:</div>
                      <div class="detail-value">{{ vendor.name }}</div>
                    </div>
                    <div class="detail-row">
                      <div class="detail-label">Vendor Code:</div>
                      <div class="detail-value">{{ vendor.vendor_code || '-' }}</div>
                    </div>
                    <div class="detail-row">
                      <div class="detail-label">Email:</div>
                      <div class="detail-value">{{ vendor.email || '-' }}</div>
                    </div>
                    <div class="detail-row">
                      <div class="detail-label">Phone:</div>
                      <div class="detail-value">{{ vendor.phone || '-' }}</div>
                    </div>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="info-card">
                    <h4 class="mb-3">Payment Summary</h4>
                    <div class="detail-row">
                      <div class="detail-label">Total Paid (YTD):</div>
                      <div class="detail-value amount">{{ formatCurrency(paymentStats.ytd_total || 0) }}</div>
                    </div>
                    <div class="detail-row">
                      <div class="detail-label">Total Paid (All Time):</div>
                      <div class="detail-value">{{ formatCurrency(paymentStats.lifetime_total || 0) }}</div>
                    </div>
                    <div class="detail-row">
                      <div class="detail-label">Last Payment:</div>
                      <div class="detail-value">{{ paymentStats.last_payment_date ? formatDate(paymentStats.last_payment_date) : 'No payments' }}</div>
                    </div>
                    <div class="detail-row">
                      <div class="detail-label">Open Balance:</div>
                      <div class="detail-value">{{ formatCurrency(paymentStats.open_balance || 0) }}</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Payment History -->
            <div class="payment-history mb-4">
              <h4 class="section-title">Payment History</h4>

              <!-- Filter controls -->
              <div class="filter-controls mb-3 d-flex flex-wrap align-items-center">
                <div class="form-group mr-3 mb-0">
                  <label class="mr-2">Date Range:</label>
                  <div class="d-inline-flex align-items-center">
                    <input type="date" v-model="filters.fromDate" class="form-control form-control-sm" @change="applyFilters">
                    <span class="mx-2">to</span>
                    <input type="date" v-model="filters.toDate" class="form-control form-control-sm" @change="applyFilters">
                  </div>
                </div>
                <div class="form-group mr-3 mb-0">
                  <label class="mr-2">Payment Method:</label>
                  <select v-model="filters.method" class="form-control form-control-sm" @change="applyFilters">
                    <option value="">All Methods</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                    <option value="Check">Check</option>
                    <option value="Cash">Cash</option>
                    <option value="Credit Card">Credit Card</option>
                  </select>
                </div>
                <button class="btn btn-sm btn-outline-secondary" @click="resetFilters">
                  <i class="fas fa-redo mr-1"></i> Reset
                </button>
              </div>

              <!-- Payments Table -->
              <div v-if="payments.length === 0" class="empty-state small py-4">
                <i class="fas fa-file-invoice-dollar"></i>
                <p>No payment records found for this vendor</p>
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
                      <td>{{ payment.vendor_payable?.vendor_invoice?.invoice_number || '-' }}</td>
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
                      <td colspan="4" class="text-right font-weight-bold">Total:</td>
                      <td class="text-right font-weight-bold">{{ formatCurrency(calculateTotalPayments()) }}</td>
                      <td></td>
                    </tr>
                  </tfoot>
                </table>
              </div>

              <!-- Pagination -->
              <PaginationComponent
                v-if="payments.length > 0"
                :current-page="pagination.currentPage"
                :total-pages="pagination.totalPages"
                :from="pagination.from"
                :to="pagination.to"
                :total="pagination.total"
                @page-changed="onPageChange"
              />
            </div>

            <!-- Open Payables -->
            <div class="open-payables">
              <h4 class="section-title">Open Invoices</h4>

              <div v-if="openPayables.length === 0" class="empty-state small py-4">
                <i class="fas fa-check-circle text-success"></i>
                <p>No open invoices for this vendor</p>
              </div>

              <div v-else class="table-responsive">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>Invoice #</th>
                      <th>Invoice Date</th>
                      <th>Due Date</th>
                      <th class="text-right">Original Amount</th>
                      <th class="text-right">Balance</th>
                      <th>Status</th>
                      <th class="actions-column">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="payable in openPayables" :key="payable.payable_id">
                      <td>{{ payable.vendor_invoice?.invoice_number || '-' }}</td>
                      <td>{{ formatDate(payable.vendor_invoice?.invoice_date) }}</td>
                      <td>{{ formatDate(payable.due_date) }}</td>
                      <td class="text-right">{{ formatCurrency(payable.amount) }}</td>
                      <td class="text-right">{{ formatCurrency(payable.balance) }}</td>
                      <td>
                        <span
                          :class="{
                            'badge': true,
                            'badge-success': payable.status === 'Paid',
                            'badge-warning': payable.status === 'Partial',
                            'badge-primary': payable.status === 'Open'
                          }"
                        >
                          {{ payable.status }}
                        </span>
                      </td>
                      <td class="actions-cell">
                        <router-link
                          :to="`/accounting/payable-payments/apply/${payable.payable_id}`"
                          class="btn btn-sm btn-primary mr-1"
                        >
                          <i class="fas fa-money-bill"></i>
                        </router-link>
                        <router-link
                          :to="`/accounting/vendor-invoices/${payable.invoice_id}`"
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
          </div>
        </div>
      </div>
    </div>
  </template>

  <script>
  import { ref, reactive, onMounted } from 'vue';
  import { useRoute } from 'vue-router';
  import axios from 'axios';

  export default {
    name: 'VendorPaymentHistory',
    setup() {
      const route = useRoute();
      const vendorId = route.params.id;

      // State
      const vendor = ref(null);
      const payments = ref([]);
      const openPayables = ref([]);
      const paymentStats = ref({});
      const isLoading = ref(true);

      // Filters
      const filters = reactive({
        fromDate: '',
        toDate: '',
        method: ''
      });

      // Pagination
      const pagination = reactive({
        currentPage: 1,
        totalPages: 1,
        from: 0,
        to: 0,
        total: 0
      });

      // Methods
      const loadVendor = async () => {
        try {
          const response = await axios.get(`/api/vendors/${vendorId}`);
          vendor.value = response.data.data;
        } catch (error) {
          console.error('Error loading vendor:', error);
          vendor.value = null;
        }
      };

      const loadPaymentHistory = async () => {
        try {
          const params = {
            vendor_id: vendorId,
            page: pagination.currentPage,
            per_page: 10
          };

          if (filters.fromDate && filters.toDate) {
            params.from_date = filters.fromDate;
            params.to_date = filters.toDate;
          }

          if (filters.method) {
            params.payment_method = filters.method;
          }

          const response = await axios.get('/api/accounting/payable-payments', { params });
          payments.value = response.data.data || [];

          // Update pagination
          if (response.data.meta) {
            pagination.totalPages = response.data.meta.last_page;
            pagination.total = response.data.meta.total;
            pagination.from = response.data.meta.from;
            pagination.to = response.data.meta.to;
            pagination.currentPage = response.data.meta.current_page;
          }
        } catch (error) {
          console.error('Error loading payment history:', error);
          payments.value = [];
        }
      };

      const loadOpenPayables = async () => {
        try {
          const response = await axios.get('/api/accounting/vendor-payables', {
            params: {
              vendor_id: vendorId,
              status: ['Open', 'Partial']
            }
          });
          openPayables.value = response.data.data || [];
        } catch (error) {
          console.error('Error loading open payables:', error);
          openPayables.value = [];
        }
      };

      const loadPaymentStats = async () => {
        try {
          const response = await axios.get(`/api/accounting/vendor-payables/stats/${vendorId}`);
          paymentStats.value = response.data.data || {};
        } catch (error) {
          console.error('Error loading payment stats:', error);
          paymentStats.value = {};
        }
      };

      const calculateTotalPayments = () => {
        return payments.value.reduce((sum, payment) => sum + parseFloat(payment.amount || 0), 0);
      };

      const applyFilters = () => {
        pagination.currentPage = 1;
        loadPaymentHistory();
      };

      const resetFilters = () => {
        filters.fromDate = '';
        filters.toDate = '';
        filters.method = '';
        applyFilters();
      };

      const onPageChange = (page) => {
        pagination.currentPage = page;
        loadPaymentHistory();
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
            loadVendor(),
            loadPaymentHistory(),
            loadOpenPayables(),
            loadPaymentStats()
          ]);
        } finally {
          isLoading.value = false;
        }
      });

      return {
        vendor,
        payments,
        openPayables,
        paymentStats,
        isLoading,
        filters,
        pagination,
        calculateTotalPayments,
        applyFilters,
        resetFilters,
        onPageChange,
        formatDate,
        formatCurrency
      };
    }
  };
  </script>

  <style scoped>
  .vendor-payment-history {
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

  .badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
  }

  .empty-state {
    text-align: center;
    color: var(--gray-500);
  }

  .empty-state i {
    font-size: 2rem;
    margin-bottom: 1rem;
  }

  .empty-state.small i {
    font-size: 1.25rem;
    margin-bottom: 0.5rem;
  }

  .table-responsive {
    overflow-x: auto;
  }

  .filter-controls {
    background-color: var(--gray-50);
    padding: 1rem;
    border-radius: 0.375rem;
  }
  </style>
