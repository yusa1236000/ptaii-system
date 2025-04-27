<!-- src/views/accounting/PayablePaymentsList.vue -->
<template>
    <div class="payable-payments-list">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h3>Vendor Payments</h3>
          <router-link to="/accounting/payable-payments/create" class="btn btn-primary">
            <i class="fas fa-plus mr-2"></i> Record Payment
          </router-link>
        </div>

        <div class="card-body">
          <!-- Search and Filters -->
          <SearchFilter :value="searchQuery" @search="onSearch" placeholder="Search reference or vendor...">
            <template #filters>
              <div class="filter-group">
                <label>Date Range</label>
                <div class="d-flex">
                  <input type="date" v-model="fromDate" class="form-control" @change="loadPayments">
                  <span class="mx-2 align-self-center">to</span>
                  <input type="date" v-model="toDate" class="form-control" @change="loadPayments">
                </div>
              </div>

              <div class="filter-group">
                <label>Payment Method</label>
                <select v-model="methodFilter" class="form-control" @change="loadPayments">
                  <option value="">All Methods</option>
                  <option value="Bank Transfer">Bank Transfer</option>
                  <option value="Check">Check</option>
                  <option value="Cash">Cash</option>
                  <option value="Credit Card">Credit Card</option>
                </select>
              </div>

              <div class="filter-group">
                <label>Vendor</label>
                <select v-model="vendorFilter" class="form-control" @change="loadPayments">
                  <option value="">All Vendors</option>
                  <option v-for="vendor in vendors" :key="vendor.vendor_id" :value="vendor.vendor_id">
                    {{ vendor.name }}
                  </option>
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
                  <th>Vendor</th>
                  <th>Invoice</th>
                  <th>Payment Method</th>
                  <th class="text-right">Amount</th>
                  <th class="actions-column">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="payment in payments" :key="payment.payment_id">
                  <td>{{ formatDate(payment.payment_date) }}</td>
                  <td>{{ payment.reference_number }}</td>
                  <td>{{ payment.vendor_payable?.vendor?.name || '-' }}</td>
                  <td>
                    <span v-if="payment.vendor_payable?.vendor_invoice">
                      {{ payment.vendor_payable.vendor_invoice.invoice_number }}
                    </span>
                    <span v-else>-</span>
                  </td>
                  <td>{{ payment.payment_method }}</td>
                  <td class="text-right">{{ formatCurrency(payment.amount) }}</td>
                  <td class="actions-cell">
                    <router-link :to="`/accounting/payable-payments/${payment.payment_id}`" class="btn btn-sm btn-info mr-1">
                      <i class="fas fa-eye"></i>
                    </router-link>
                    <button @click="confirmDelete(payment)" class="btn btn-sm btn-danger">
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
        :message="`Are you sure you want to delete the payment with reference <strong>${selectedPayment?.reference_number}</strong>?<br>This action cannot be undone and may affect accounting balances.`"
        confirm-button-text="Delete"
        confirm-button-class="btn btn-danger"
        @confirm="deletePayment"
        @close="showDeleteModal = false"
      />
    </div>
  </template>

  <script>
  import { ref, computed, onMounted } from 'vue';
  import axios from 'axios';

  export default {
    name: 'PayablePaymentsList',
    setup() {
      // State
      const payments = ref([]);
      const vendors = ref([]);
      const isLoading = ref(true);
      const searchQuery = ref('');
      const vendorFilter = ref('');
      const methodFilter = ref('');
      const fromDate = ref('');
      const toDate = ref('');
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

          if (searchQuery.value) {
            params.search = searchQuery.value;
          }

          if (vendorFilter.value) {
            params.vendor_id = vendorFilter.value;
          }

          if (methodFilter.value) {
            params.payment_method = methodFilter.value;
          }

          if (fromDate.value && toDate.value) {
            params.from_date = fromDate.value;
            params.to_date = toDate.value;
          }

          const response = await axios.get('/api/accounting/payable-payments', { params });
          payments.value = response.data.data;

          if (response.data.meta) {
            totalPages.value = response.data.meta.last_page;
            totalPayments.value = response.data.meta.total;
            currentPage.value = response.data.meta.current_page;
          } else {
            // Fallback if pagination metadata is not provided
            totalPayments.value = payments.value.length;
            totalPages.value = 1;
            currentPage.value = 1;
          }
        } catch (error) {
          console.error('Error loading payments:', error);
          // TODO: Show error notification
        } finally {
          isLoading.value = false;
        }
      };

      const loadVendors = async () => {
        try {
          const response = await axios.get('/api/vendors');
          vendors.value = response.data.data || [];
        } catch (error) {
          console.error('Error loading vendors:', error);
        }
      };

      const onSearch = (value) => {
        searchQuery.value = value;
        currentPage.value = 1;
        loadPayments();
      };

      const onPageChange = (page) => {
        currentPage.value = page;
        loadPayments();
      };

      const resetFilters = () => {
        searchQuery.value = '';
        vendorFilter.value = '';
        methodFilter.value = '';
        fromDate.value = '';
        toDate.value = '';
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
          await axios.delete(`/api/accounting/payable-payments/${selectedPayment.value.payment_id}`);
          // TODO: Show success notification
          showDeleteModal.value = false;
          loadPayments();
        } catch (error) {
          console.error('Error deleting payment:', error);
          // TODO: Show error notification
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
        loadPayments();
      });

      return {
        payments,
        vendors,
        isLoading,
        searchQuery,
        vendorFilter,
        methodFilter,
        fromDate,
        toDate,
        currentPage,
        totalPages,
        totalPayments,
        paginationFrom,
        paginationTo,
        showDeleteModal,
        selectedPayment,
        onSearch,
        onPageChange,
        resetFilters,
        loadPayments,
        confirmDelete,
        deletePayment,
        formatDate,
        formatCurrency
      };
    }
  };
  </script>

  <style scoped>
  .payable-payments-list {
    padding: 1rem 0;
  }

  .filter-group {
    min-width: 200px;
  }

  .table-container {
    overflow-x: auto;
  }
  </style>
