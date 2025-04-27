<!-- src/views/accounting/receivables/ReceivablePaymentsList.vue -->
<template>
    <div class="receivable-payments-list">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h3>Receivable Payments</h3>
          <router-link to="/accounting/receivable-payments/create" class="btn btn-primary">
            <i class="fas fa-plus mr-2"></i> Record New Payment
          </router-link>
        </div>

        <div class="card-body">
          <!-- Search and Filters -->
          <SearchFilter
            v-model:value="searchTerm"
            @search="loadPayments"
            placeholder="Search by reference number..."
          >
            <template #filters>
              <div class="filter-group">
                <label>Date Range</label>
                <div class="d-flex">
                  <input
                    type="date"
                    v-model="filters.fromDate"
                    class="form-control"
                    @change="loadPayments"
                  >
                  <span class="mx-2 align-self-center">to</span>
                  <input
                    type="date"
                    v-model="filters.toDate"
                    class="form-control"
                    @change="loadPayments"
                  >
                </div>
              </div>

              <div class="filter-group">
                <label>Payment Method</label>
                <select v-model="filters.paymentMethod" class="form-control" @change="loadPayments">
                  <option value="">All Methods</option>
                  <option value="Cash">Cash</option>
                  <option value="Bank Transfer">Bank Transfer</option>
                  <option value="Check">Check</option>
                  <option value="Credit Card">Credit Card</option>
                  <option value="Online Payment">Online Payment</option>
                </select>
              </div>
            </template>

            <template #actions>
              <button class="btn btn-outline-secondary" @click="resetFilters">
                <i class="fas fa-redo mr-1"></i> Reset
              </button>
            </template>
          </SearchFilter>

          <!-- Loading and Empty States -->
          <div v-if="isLoading" class="loading-indicator">
            <i class="fas fa-spinner fa-spin"></i> Loading payments...
          </div>

          <div v-else-if="payments.length === 0" class="empty-state">
            <div class="empty-icon">
              <i class="fas fa-file-invoice-dollar"></i>
            </div>
            <h3>No Payments Found</h3>
            <p>No payments match your search criteria or there are no payments recorded yet.</p>
          </div>

          <!-- Payments Table -->
          <div v-else class="table-container">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Reference #</th>
                  <th>Customer</th>
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
                  <td>{{ payment.customer_receivable?.customer?.name || '-' }}</td>
                  <td>
                    <span v-if="payment.customer_receivable?.sales_invoice">
                      {{ payment.customer_receivable.sales_invoice.invoice_number }}
                    </span>
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
                      class="btn btn-sm btn-info mr-1"
                    >
                      <i class="fas fa-eye"></i>
                    </router-link>
                    <button
                      @click="confirmDelete(payment)"
                      class="btn btn-sm btn-danger"
                    >
                      <i class="fas fa-trash"></i>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <PaginationComponent
            v-if="payments.length > 0"
            :current-page="currentPage"
            :total-pages="totalPages"
            :from="paginationFrom"
            :to="paginationTo"
            :total="totalPayments"
            @page-changed="onPageChange"
          />
        </div>
      </div>

      <!-- Delete Confirmation Modal -->
      <ConfirmationModal
        v-if="showDeleteModal"
        title="Delete Payment"
        :message="`Are you sure you want to delete payment with reference number <strong>${selectedPayment?.reference_number}</strong>?<br>This action will reverse the payment and update the receivable balance.`"
        confirm-button-text="Delete"
        confirm-button-class="btn btn-danger"
        @confirm="deletePayment"
        @close="showDeleteModal = false"
      />
    </div>
  </template>

  <script>
  import { ref, reactive, computed, onMounted } from 'vue';
  import axios from 'axios';

  export default {
    name: 'ReceivablePaymentsList',
    setup() {
      // State
      const payments = ref([]);
      const isLoading = ref(true);
      const searchTerm = ref('');
      const filters = reactive({
        fromDate: '',
        toDate: '',
        paymentMethod: ''
      });
      const currentPage = ref(1);
      const totalPages = ref(1);
      const totalPayments = ref(0);
      const perPage = ref(15);
      const showDeleteModal = ref(false);
      const selectedPayment = ref(null);

      // Computed properties
      const paginationFrom = computed(() => {
        if (totalPayments.value === 0) return 0;
        return ((currentPage.value - 1) * perPage.value) + 1;
      });

      const paginationTo = computed(() => {
        if (totalPayments.value === 0) return 0;
        return Math.min(currentPage.value * perPage.value, totalPayments.value);
      });

      // Methods
      const loadPayments = async () => {
        isLoading.value = true;
        try {
          const params = {
            page: currentPage.value,
            per_page: perPage.value
          };

          if (searchTerm.value) {
            params.search = searchTerm.value;
          }

          if (filters.fromDate && filters.toDate) {
            params.from_date = filters.fromDate;
            params.to_date = filters.toDate;
          }

          if (filters.paymentMethod) {
            params.payment_method = filters.paymentMethod;
          }

          const response = await axios.get('/api/accounting/receivable-payments', { params });
          payments.value = response.data.data;

          if (response.data.meta) {
            totalPages.value = response.data.meta.last_page;
            totalPayments.value = response.data.meta.total;
            currentPage.value = response.data.meta.current_page;
          } else {
            // Fallback if pagination metadata is not provided
            totalPayments.value = payments.value.length;
            totalPages.value = 1;
          }
        } catch (error) {
          console.error('Error loading payments:', error);
          // Show error notification
          if (this.$toast) {
            this.$toast.error('Failed to load payments');
          }
        } finally {
          isLoading.value = false;
        }
      };

      const onPageChange = (page) => {
        currentPage.value = page;
        loadPayments();
      };

      const resetFilters = () => {
        searchTerm.value = '';
        filters.fromDate = '';
        filters.toDate = '';
        filters.paymentMethod = '';
        currentPage.value = 1;
        loadPayments();
      };

      const confirmDelete = (payment) => {
        selectedPayment.value = payment;
        showDeleteModal.value = true;
      };

      const deletePayment = async () => {
        if (!selectedPayment.value) return;

        try {
          await axios.delete(`/api/accounting/receivable-payments/${selectedPayment.value.payment_id}`);

          // Show success notification
          if (this.$toast) {
            this.$toast.success('Payment deleted successfully');
          }

          showDeleteModal.value = false;
          loadPayments(); // Reload the list
        } catch (error) {
          console.error('Error deleting payment:', error);

          // Show error notification
          if (this.$toast) {
            this.$toast.error('Failed to delete payment: ' + (error.response?.data?.message || 'Unknown error'));
          }
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

      // Lifecycle hooks
      onMounted(() => {
        loadPayments();
      });

      return {
        payments,
        isLoading,
        searchTerm,
        filters,
        currentPage,
        totalPages,
        totalPayments,
        paginationFrom,
        paginationTo,
        showDeleteModal,
        selectedPayment,
        loadPayments,
        onPageChange,
        resetFilters,
        confirmDelete,
        deletePayment,
        formatDate,
        formatCurrency,
        getPaymentMethodClass
      };
    }
  };
  </script>

  <style scoped>
  .receivable-payments-list {
    padding: 1rem 0;
  }

  .filter-group {
    min-width: 200px;
  }

  .table-container {
    overflow-x: auto;
  }

  .badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
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
  </style>
