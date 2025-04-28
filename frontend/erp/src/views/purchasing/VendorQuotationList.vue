<!-- frontend/erp/src/views/purchasing/VendorQuotationList.vue -->
<template>
    <div class="quotation-list-container">
      <div class="header-actions">
        <h2 class="section-title">Vendor Quotations</h2>
        <div class="action-buttons">
          <router-link to="/purchasing/quotations/create" class="btn btn-primary">
            <i class="fas fa-plus"></i> New Quotation
          </router-link>
        </div>
      </div>

      <div class="filters-container">
        <SearchFilter
          @search="handleSearch"
          :filters="availableFilters"
          @filter-change="handleFilterChange"
        />
      </div>

      <div class="card">
        <div class="card-body">
          <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
          </div>

          <div v-else-if="quotations.length === 0" class="text-center py-5">
            <div class="empty-state">
              <i class="fas fa-file-invoice-dollar fa-3x text-muted"></i>
              <h3 class="mt-3">No Quotations Found</h3>
              <p class="text-muted">There are no vendor quotations matching your criteria.</p>
              <router-link to="/purchasing/quotations/create" class="btn btn-primary mt-3">
                Create Quotation
              </router-link>
            </div>
          </div>

          <div v-else class="table-responsive">
            <table class="table table-striped table-hover">
              <thead>
                <tr>
                  <th @click="sortBy('rfq_id')" class="sortable">
                    RFQ Number
                    <i v-if="sortField === 'rfq_id'" :class="getSortIconClass('rfq_id')"></i>
                  </th>
                  <th @click="sortBy('vendor_id')" class="sortable">
                    Vendor
                    <i v-if="sortField === 'vendor_id'" :class="getSortIconClass('vendor_id')"></i>
                  </th>
                  <th @click="sortBy('quotation_date')" class="sortable">
                    Date
                    <i v-if="sortField === 'quotation_date'" :class="getSortIconClass('quotation_date')"></i>
                  </th>
                  <th @click="sortBy('validity_date')" class="sortable">
                    Valid Until
                    <i v-if="sortField === 'validity_date'" :class="getSortIconClass('validity_date')"></i>
                  </th>
                  <th @click="sortBy('status')" class="sortable">
                    Status
                    <i v-if="sortField === 'status'" :class="getSortIconClass('status')"></i>
                  </th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="quotation in quotations" :key="quotation.quotation_id">
                  <td>{{ quotation.requestForQuotation?.rfq_number || 'N/A' }}</td>
                  <td>{{ quotation.vendor?.name || 'N/A' }}</td>
                  <td>{{ formatDate(quotation.quotation_date) }}</td>
                  <td>{{ formatDate(quotation.validity_date) }}</td>
                  <td>
                    <span :class="getStatusBadgeClass(quotation.status)">
                      {{ formatStatus(quotation.status) }}
                    </span>
                  </td>
                  <td>
                    <div class="btn-group">
                      <router-link :to="`/purchasing/quotations/${quotation.quotation_id}`" class="btn btn-sm btn-outline-primary" title="View">
                        <i class="fas fa-eye"></i>
                      </router-link>
                      <router-link v-if="quotation.status === 'received'" :to="`/purchasing/quotations/${quotation.quotation_id}/edit`" class="btn btn-sm btn-outline-secondary" title="Edit">
                        <i class="fas fa-edit"></i>
                      </router-link>
                      <button v-if="quotation.status === 'received'" @click="acceptQuotation(quotation.quotation_id)" class="btn btn-sm btn-outline-success" title="Accept">
                        <i class="fas fa-check"></i>
                      </button>
                      <button v-if="quotation.status === 'received'" @click="rejectQuotation(quotation.quotation_id)" class="btn btn-sm btn-outline-danger" title="Reject">
                        <i class="fas fa-times"></i>
                      </button>
                      <router-link v-if="quotation.status === 'accepted'" :to="`/purchasing/quotations/${quotation.quotation_id}/create-po`" class="btn btn-sm btn-outline-info" title="Create PO">
                        <i class="fas fa-file-alt"></i>
                      </router-link>
                      <button v-if="canDelete(quotation)" @click="confirmDelete(quotation)" class="btn btn-sm btn-outline-danger" title="Delete">
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <PaginationComponent
            v-if="pagination.total > 0"
            :current-page="pagination.current_page"
            :total-pages="pagination.last_page"
            :total="pagination.total"
            @page-changed="handlePageChange"
          />
        </div>
      </div>

      <!-- Confirmation Modal -->
      <ConfirmationModal
        v-if="showDeleteModal"
        :title="'Delete Quotation'"
        :message="'Are you sure you want to delete this quotation? This action cannot be undone.'"
        :confirm-button-text="'Delete'"
        :confirm-button-variant="'danger'"
        @confirm="deleteQuotation"
        @cancel="showDeleteModal = false"
      />
    </div>
  </template>

  <script>
  import { ref, onMounted, computed, reactive } from 'vue';
  import axios from 'axios';
  //import { useRouter } from 'vue-router';

  export default {
    name: 'VendorQuotationList',

    setup() {
      //const router = useRouter();
      const quotations = ref([]);
      const loading = ref(true);
      const showDeleteModal = ref(false);
      const quotationToDelete = ref(null);

      const pagination = reactive({
        current_page: 1,
        last_page: 1,
        total: 0
      });

      const filters = reactive({
        status: '',
        vendor_id: '',
        rfq_id: '',
        date_from: '',
        date_to: '',
        search: '',
        sort_field: 'quotation_date',
        sort_direction: 'desc',
        per_page: 15
      });

      const availableFilters = [
        {
          name: 'status',
          label: 'Status',
          type: 'select',
          options: [
            { value: '', label: 'All Statuses' },
            { value: 'received', label: 'Received' },
            { value: 'accepted', label: 'Accepted' },
            { value: 'rejected', label: 'Rejected' }
          ]
        },
        {
          name: 'date_from',
          label: 'From Date',
          type: 'date'
        },
        {
          name: 'date_to',
          label: 'To Date',
          type: 'date'
        }
      ];

      const sortField = computed(() => filters.sort_field);
      const sortDirection = computed(() => filters.sort_direction);

      // Fetch quotations list
      const fetchQuotations = async () => {
        loading.value = true;
        try {
          // Build query params
          const params = { ...filters };

          const response = await axios.get('/api/vendor-quotations', { params });

          if (response.data.status === 'success') {
            quotations.value = response.data.data.data || [];

            // Update pagination
            pagination.current_page = response.data.data.current_page;
            pagination.last_page = response.data.data.last_page;
            pagination.total = response.data.data.total;
          } else {
            console.error('Failed to fetch quotations:', response.data.message);
          }
        } catch (error) {
          console.error('Error fetching quotations:', error);
        } finally {
          loading.value = false;
        }
      };

      // Format date using local date format
      const formatDate = (dateString) => {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        return date.toLocaleDateString();
      };

      // Format status for display
      const formatStatus = (status) => {
        if (!status) return 'Unknown';

        return status.charAt(0).toUpperCase() + status.slice(1);
      };

      // Get status badge class based on status
      const getStatusBadgeClass = (status) => {
        const baseClass = 'badge ';

        switch (status) {
          case 'received':
            return baseClass + 'bg-info';
          case 'accepted':
            return baseClass + 'bg-success';
          case 'rejected':
            return baseClass + 'bg-danger';
          default:
            return baseClass + 'bg-secondary';
        }
      };

      // Handle search
      const handleSearch = (searchTerm) => {
        filters.search = searchTerm;
        filters.page = 1; // Reset to first page
        fetchQuotations();
      };

      // Handle filter changes
      const handleFilterChange = (filterName, value) => {
        filters[filterName] = value;
        filters.page = 1; // Reset to first page
        fetchQuotations();
      };

      // Handle pagination
      const handlePageChange = (page) => {
        filters.page = page;
        fetchQuotations();
      };

      // Sorting functionality
      const sortBy = (field) => {
        if (filters.sort_field === field) {
          filters.sort_direction = filters.sort_direction === 'asc' ? 'desc' : 'asc';
        } else {
          filters.sort_field = field;
          filters.sort_direction = 'asc';
        }
        fetchQuotations();
      };

      // Get sort icon class
      const getSortIconClass = (field) => {
        if (filters.sort_field !== field) return 'fas fa-sort';
        return filters.sort_direction === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down';
      };

      // Accept quotation
      const acceptQuotation = async (id) => {
        try {
          const response = await axios.patch(`/api/vendor-quotations/${id}/status`, {
            status: 'accepted'
          });

          if (response.data.status === 'success') {
            // Show success message or toast
            fetchQuotations(); // Refresh the list
          } else {
            console.error('Failed to accept quotation:', response.data.message);
          }
        } catch (error) {
          console.error('Error accepting quotation:', error);
        }
      };

      // Reject quotation
      const rejectQuotation = async (id) => {
        try {
          const response = await axios.patch(`/api/vendor-quotations/${id}/status`, {
            status: 'rejected'
          });

          if (response.data.status === 'success') {
            // Show success message or toast
            fetchQuotations(); // Refresh the list
          } else {
            console.error('Failed to reject quotation:', response.data.message);
          }
        } catch (error) {
          console.error('Error rejecting quotation:', error);
        }
      };

      // Check if quotation can be deleted
      const canDelete = (quotation) => {
        return quotation.status === 'received';
      };

      // Show delete confirmation modal
      const confirmDelete = (quotation) => {
        quotationToDelete.value = quotation;
        showDeleteModal.value = true;
      };

      // Delete quotation
      const deleteQuotation = async () => {
        if (!quotationToDelete.value) return;

        try {
          const response = await axios.delete(`/api/vendor-quotations/${quotationToDelete.value.quotation_id}`);

          if (response.data.status === 'success') {
            // Show success message or toast
            fetchQuotations(); // Refresh the list
          } else {
            console.error('Failed to delete quotation:', response.data.message);
          }
        } catch (error) {
          console.error('Error deleting quotation:', error);
        } finally {
          showDeleteModal.value = false;
          quotationToDelete.value = null;
        }
      };

      onMounted(() => {
        fetchQuotations();
      });

      return {
        quotations,
        loading,
        pagination,
        availableFilters,
        sortField,
        sortDirection,
        showDeleteModal,
        formatDate,
        formatStatus,
        getStatusBadgeClass,
        handleSearch,
        handleFilterChange,
        handlePageChange,
        sortBy,
        getSortIconClass,
        acceptQuotation,
        rejectQuotation,
        canDelete,
        confirmDelete,
        deleteQuotation
      };
    }
  };
  </script>

  <style scoped>
  .quotation-list-container {
    padding: 1rem 0;
  }

  .header-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
  }

  .section-title {
    font-size: 1.5rem;
    margin: 0;
  }

  .filters-container {
    margin-bottom: 1.5rem;
  }

  .action-buttons {
    display: flex;
    gap: 0.5rem;
  }

  .card {
    border-radius: 0.5rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  }

  .empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem;
  }

  .sortable {
    cursor: pointer;
  }

  .sortable i {
    margin-left: 0.25rem;
  }

  table th, table td {
    vertical-align: middle;
  }

  .btn-group .btn {
    padding: 0.25rem 0.5rem;
  }
  </style>
