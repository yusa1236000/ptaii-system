<!-- src/views/purchasing/VendorQuotationList.vue -->
<template>
    <div class="quotation-list">
      <div class="card">
        <div class="card-header">
          <div class="d-flex justify-content-between align-items-center">
            <h2>Vendor Quotations</h2>
            <router-link
              to="/purchasing/quotations/create"
              class="btn btn-primary"
            >
              <i class="fas fa-plus"></i> Create Quotation
            </router-link>
          </div>
        </div>
        <div class="card-body">
          <!-- Search and Filter Controls -->
          <div class="filter-controls mb-4">
            <div class="row">
              <div class="col-md-3">
                <div class="input-group">
                  <input
                    type="text"
                    class="form-control"
                    placeholder="Search by quotation or vendor..."
                    v-model="filters.search"
                    @input="debounceSearch"
                  />
                  <div class="input-group-append">
                    <span class="input-group-text">
                      <i class="fas fa-search"></i>
                    </span>
                  </div>
                </div>
              </div>
              <div class="col-md-2">
                <select class="form-control" v-model="filters.status">
                  <option value="">All Statuses</option>
                  <option value="received">Received</option>
                  <option value="accepted">Accepted</option>
                  <option value="rejected">Rejected</option>
                </select>
              </div>
              <div class="col-md-2">
                <select class="form-control" v-model="filters.vendor_id">
                  <option value="">All Vendors</option>
                  <option v-for="vendor in vendors" :key="vendor.vendor_id" :value="vendor.vendor_id">
                    {{ vendor.name }}
                  </option>
                </select>
              </div>
              <div class="col-md-2">
                <select class="form-control" v-model="filters.rfq_id">
                  <option value="">All RFQs</option>
                  <option v-for="rfq in rfqs" :key="rfq.rfq_id" :value="rfq.rfq_id">
                    {{ rfq.rfq_number }}
                  </option>
                </select>
              </div>
              <div class="col-md-3">
                <div class="input-group">
                  <input
                    type="date"
                    class="form-control"
                    placeholder="Date From"
                    v-model="filters.date_from"
                  />
                  <input
                    type="date"
                    class="form-control"
                    placeholder="Date To"
                    v-model="filters.date_to"
                  />
                  <div class="input-group-append">
                    <button class="btn btn-outline-secondary" @click="applyFilters">
                      <i class="fas fa-filter"></i>
                    </button>
                    <button class="btn btn-outline-secondary" @click="resetFilters">
                      <i class="fas fa-times"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Loading State -->
          <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="sr-only">Loading...</span>
            </div>
            <p class="mt-2">Loading quotations...</p>
          </div>

          <!-- Error State -->
          <div v-else-if="error" class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-triangle mr-2"></i> {{ error }}
          </div>

          <!-- Empty State -->
          <div v-else-if="!quotations.length" class="text-center py-5">
            <i class="fas fa-file-invoice-dollar fa-3x text-muted mb-3"></i>
            <h4>No quotations found</h4>
            <p class="text-muted">Try changing your search criteria or create a new quotation.</p>
          </div>

          <!-- Data Table -->
          <div v-else class="table-responsive">
            <table class="table table-hover">
              <thead class="bg-light">
                <tr>
                  <th @click="sortBy('quotation_id')" class="sortable">
                    ID
                    <i
                      class="fas"
                      :class="{
                        'fa-sort': sortField !== 'quotation_id',
                        'fa-sort-up': sortField === 'quotation_id' && sortDirection === 'asc',
                        'fa-sort-down': sortField === 'quotation_id' && sortDirection === 'desc'
                      }"
                    ></i>
                  </th>
                  <th @click="sortBy('vendor.name')" class="sortable">
                    Vendor
                    <i
                      class="fas"
                      :class="{
                        'fa-sort': sortField !== 'vendor.name',
                        'fa-sort-up': sortField === 'vendor.name' && sortDirection === 'asc',
                        'fa-sort-down': sortField === 'vendor.name' && sortDirection === 'desc'
                      }"
                    ></i>
                  </th>
                  <th @click="sortBy('rfq_id')" class="sortable">
                    RFQ
                    <i
                      class="fas"
                      :class="{
                        'fa-sort': sortField !== 'rfq_id',
                        'fa-sort-up': sortField === 'rfq_id' && sortDirection === 'asc',
                        'fa-sort-down': sortField === 'rfq_id' && sortDirection === 'desc'
                      }"
                    ></i>
                  </th>
                  <th @click="sortBy('quotation_date')" class="sortable">
                    Date
                    <i
                      class="fas"
                      :class="{
                        'fa-sort': sortField !== 'quotation_date',
                        'fa-sort-up': sortField === 'quotation_date' && sortDirection === 'asc',
                        'fa-sort-down': sortField === 'quotation_date' && sortDirection === 'desc'
                      }"
                    ></i>
                  </th>
                  <th @click="sortBy('validity_date')" class="sortable">
                    Valid Until
                    <i
                      class="fas"
                      :class="{
                        'fa-sort': sortField !== 'validity_date',
                        'fa-sort-up': sortField === 'validity_date' && sortDirection === 'asc',
                        'fa-sort-down': sortField === 'validity_date' && sortDirection === 'desc'
                      }"
                    ></i>
                  </th>
                  <th @click="sortBy('status')" class="sortable">
                    Status
                    <i
                      class="fas"
                      :class="{
                        'fa-sort': sortField !== 'status',
                        'fa-sort-up': sortField === 'status' && sortDirection === 'asc',
                        'fa-sort-down': sortField === 'status' && sortDirection === 'desc'
                      }"
                    ></i>
                  </th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="quotation in quotations" :key="quotation.quotation_id">
                  <td>{{ quotation.quotation_id }}</td>
                  <td>{{ quotation.vendor ? quotation.vendor.name : 'N/A' }}</td>
                  <td>{{ quotation.requestForQuotation ? quotation.requestForQuotation.rfq_number : 'N/A' }}</td>
                  <td>{{ formatDate(quotation.quotation_date) }}</td>
                  <td>{{ formatDate(quotation.validity_date) }}</td>
                  <td>
                    <span
                      class="badge"
                      :class="{
                        'badge-info': quotation.status === 'received',
                        'badge-success': quotation.status === 'accepted',
                        'badge-danger': quotation.status === 'rejected'
                      }"
                    >
                      {{ formatStatus(quotation.status) }}
                    </span>
                  </td>
                  <td>
                    <div class="btn-group">
                      <router-link
                        :to="`/purchasing/quotations/${quotation.quotation_id}`"
                        class="btn btn-sm btn-info"
                        title="View Details"
                      >
                        <i class="fas fa-eye"></i>
                      </router-link>
                      <router-link
                        v-if="quotation.status === 'received'"
                        :to="`/purchasing/quotations/${quotation.quotation_id}/edit`"
                        class="btn btn-sm btn-primary"
                        title="Edit"
                      >
                        <i class="fas fa-edit"></i>
                      </router-link>
                      <button
                        v-if="quotation.status === 'received'"
                        @click="updateQuotationStatus(quotation, 'accepted')"
                        class="btn btn-sm btn-success"
                        title="Accept"
                      >
                        <i class="fas fa-check"></i>
                      </button>
                      <button
                        v-if="quotation.status === 'received'"
                        @click="updateQuotationStatus(quotation, 'rejected')"
                        class="btn btn-sm btn-danger"
                        title="Reject"
                      >
                        <i class="fas fa-times"></i>
                      </button>
                      <router-link
                        v-if="quotation.status === 'accepted'"
                        :to="`/purchasing/quotations/${quotation.quotation_id}/create-po`"
                        class="btn btn-sm btn-warning"
                        title="Create PO"
                      >
                        <i class="fas fa-file-invoice"></i>
                      </router-link>
                      <button
                        v-if="quotation.status === 'received'"
                        @click="confirmDelete(quotation)"
                        class="btn btn-sm btn-danger"
                        title="Delete"
                      >
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div class="d-flex justify-content-between align-items-center mt-4">
            <div class="pagination-info">
              Showing {{ paginationInfo.from }} to {{ paginationInfo.to }} of {{ paginationInfo.total }} entries
            </div>
            <PaginationComponent
              :current-page="currentPage"
              :last-page="lastPage"
              @page-changed="changePage"
            />
          </div>
        </div>
      </div>

      <!-- Confirmation Modal -->
      <ConfirmationModal
        :show="showDeleteModal"
        :title="'Delete Quotation'"
        :message="'Are you sure you want to delete this quotation? This action cannot be undone.'"
        @confirmed="deleteQuotation"
        @canceled="cancelDelete"
      />
    </div>
  </template>

  <script>
  import axios from 'axios';
  import { ref, reactive, onMounted} from 'vue';
  import { useRouter } from 'vue-router';

  export default {
    name: 'VendorQuotationList',

    setup() {
      const router = useRouter();
      const quotations = ref([]);
      const vendors = ref([]);
      const rfqs = ref([]);
      const loading = ref(true);
      const error = ref(null);
      const currentPage = ref(1);
      const lastPage = ref(1);
      const perPage = ref(15);
      const sortField = ref('quotation_date');
      const sortDirection = ref('desc');
      const showDeleteModal = ref(false);
      const quotationToDelete = ref(null);

      const filters = reactive({
        search: '',
        status: '',
        vendor_id: '',
        rfq_id: '',
        date_from: '',
        date_to: '',
        sort_field: sortField.value,
        sort_direction: sortDirection.value,
      });

      const paginationInfo = reactive({
        from: 0,
        to: 0,
        total: 0,
      });

      // Fetch all quotations
      const fetchQuotations = async () => {
        loading.value = true;
        error.value = null;

        try {
          // Build query parameters
          const params = {
            page: currentPage.value,
            per_page: perPage.value,
            sort_field: sortField.value,
            sort_direction: sortDirection.value,
          };

          // Add filter parameters if they are set
          if (filters.search) params.search = filters.search;
          if (filters.status) params.status = filters.status;
          if (filters.vendor_id) params.vendor_id = filters.vendor_id;
          if (filters.rfq_id) params.rfq_id = filters.rfq_id;
          if (filters.date_from) params.date_from = filters.date_from;
          if (filters.date_to) params.date_to = filters.date_to;

          const response = await axios.get('/api/vendor-quotations', { params });

          if (response.data.status === 'success') {
            quotations.value = response.data.data.data;
            currentPage.value = response.data.data.current_page;
            lastPage.value = response.data.data.last_page;
            paginationInfo.from = response.data.data.from || 0;
            paginationInfo.to = response.data.data.to || 0;
            paginationInfo.total = response.data.data.total || 0;
          } else {
            throw new Error('Failed to fetch quotations');
          }
        } catch (err) {
          console.error('Error fetching quotations:', err);
          error.value = 'Failed to load quotations. Please try again.';
        } finally {
          loading.value = false;
        }
      };

      // Fetch vendors for filter dropdown
      const fetchVendors = async () => {
        try {
          const response = await axios.get('/api/vendors');
          if (response.data.status === 'success') {
            vendors.value = response.data.data.data;
          }
        } catch (err) {
          console.error('Error fetching vendors:', err);
        }
      };

      // Fetch RFQs for filter dropdown
      const fetchRFQs = async () => {
        try {
          const response = await axios.get('/api/request-for-quotations');
          if (response.data.status === 'success') {
            rfqs.value = response.data.data.data;
          }
        } catch (err) {
          console.error('Error fetching RFQs:', err);
        }
      };

      // Update quotation status (accept/reject)
      const updateQuotationStatus = async (quotation, status) => {
        try {
          const response = await axios.patch(`/api/vendor-quotations/${quotation.quotation_id}/status`, {
            status: status
          });

          if (response.data.status === 'success') {
            // Update the quotation status in the local list
            const index = quotations.value.findIndex(q => q.quotation_id === quotation.quotation_id);
            if (index !== -1) {
              quotations.value[index].status = status;
            }

            // Show success message
            alert(`Quotation ${status === 'accepted' ? 'accepted' : 'rejected'} successfully!`);

            // If accepting a quotation, offer to create a PO
            if (status === 'accepted') {
              if (confirm('Would you like to create a Purchase Order from this quotation?')) {
                router.push(`/purchasing/quotations/${quotation.quotation_id}/create-po`);
              }
            }
          } else {
            throw new Error(`Failed to ${status} quotation`);
          }
        } catch (err) {
          console.error(`Error updating quotation status:`, err);
          alert(`Failed to update quotation status. ${err.response?.data?.message || 'Please try again.'}`);
        }
      };

      // Sorting function
      const sortBy = (field) => {
        if (sortField.value === field) {
          sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
        } else {
          sortField.value = field;
          sortDirection.value = 'asc';
        }

        filters.sort_field = sortField.value;
        filters.sort_direction = sortDirection.value;

        fetchQuotations();
      };

      // Pagination
      const changePage = (page) => {
        currentPage.value = page;
        fetchQuotations();
      };

      // Filter handlers
      const applyFilters = () => {
        currentPage.value = 1; // Reset to first page when filtering
        fetchQuotations();
      };

      const resetFilters = () => {
        filters.search = '';
        filters.status = '';
        filters.vendor_id = '';
        filters.rfq_id = '';
        filters.date_from = '';
        filters.date_to = '';
        currentPage.value = 1;
        fetchQuotations();
      };

      // Delete confirmation
      const confirmDelete = (quotation) => {
        quotationToDelete.value = quotation;
        showDeleteModal.value = true;
      };

      const cancelDelete = () => {
        quotationToDelete.value = null;
        showDeleteModal.value = false;
      };

      const deleteQuotation = async () => {
        if (!quotationToDelete.value) return;

        try {
          const response = await axios.delete(`/api/vendor-quotations/${quotationToDelete.value.quotation_id}`);
          if (response.data.status === 'success') {
            // Remove from local list
            quotations.value = quotations.value.filter(q => q.quotation_id !== quotationToDelete.value.quotation_id);
            showDeleteModal.value = false;
            quotationToDelete.value = null;

            // Show success message
            alert('Quotation deleted successfully!');

            // Refresh the list if it's now empty on the current page
            if (quotations.value.length === 0 && currentPage.value > 1) {
              currentPage.value -= 1;
              fetchQuotations();
            }
          } else {
            throw new Error('Failed to delete quotation');
          }
        } catch (err) {
          console.error('Error deleting quotation:', err);
          alert(`Failed to delete quotation. ${err.response?.data?.message || 'Please try again.'}`);
        } finally {
          showDeleteModal.value = false;
          quotationToDelete.value = null;
        }
      };

      // Debounced search
      let searchTimeout;
      const debounceSearch = () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
          applyFilters();
        }, 500);
      };

      // Helper functions
      const formatDate = (dateString) => {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
          year: 'numeric',
          month: 'short',
          day: 'numeric'
        });
      };

      const formatStatus = (status) => {
        if (!status) return 'Unknown';
        return status.charAt(0).toUpperCase() + status.slice(1);
      };

      // Lifecycle hooks
      onMounted(() => {
        fetchQuotations();
        fetchVendors();
        fetchRFQs();
      });

      return {
        quotations,
        vendors,
        rfqs,
        loading,
        error,
        filters,
        sortField,
        sortDirection,
        currentPage,
        lastPage,
        paginationInfo,
        showDeleteModal,
        quotationToDelete,
        fetchQuotations,
        sortBy,
        changePage,
        applyFilters,
        resetFilters,
        updateQuotationStatus,
        confirmDelete,
        cancelDelete,
        deleteQuotation,
        debounceSearch,
        formatDate,
        formatStatus
      };
    }
  };
  </script>

  <style scoped>
  .quotation-list {
    width: 100%;
  }

  .card {
    border-radius: 8px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
    overflow: hidden;
  }

  .card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    padding: 1rem 1.5rem;
  }

  .card-body {
    padding: 1.5rem;
  }

  .filter-controls {
    background-color: #f8f9fa;
    padding: 1rem;
    border-radius: 6px;
    margin-bottom: 1.5rem;
  }

  .table {
    margin-bottom: 0;
  }

  th.sortable {
    cursor: pointer;
  }

  th.sortable:hover {
    background-color: rgba(0, 0, 0, 0.05);
  }

  th i {
    margin-left: 5px;
    font-size: 0.8rem;
  }

  .table thead th {
    border-top: none;
    background-color: #f8f9fa;
    color: #495057;
    font-weight: 600;
  }

  .badge {
    padding: 6px 10px;
    font-weight: 500;
    border-radius: 4px;
  }

  .badge-info {
    background-color: #17a2b8;
    color: white;
  }

  .badge-success {
    background-color: #28a745;
    color: white;
  }

  .badge-danger {
    background-color: #dc3545;
    color: white;
  }

  .btn-group {
    display: flex;
    gap: 4px;
  }

  .btn-group .btn {
    border-radius: 4px;
  }

  .btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
  }

  .pagination-info {
    color: #6c757d;
  }
  </style>
