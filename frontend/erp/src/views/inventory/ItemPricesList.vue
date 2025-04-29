<!-- src/views/inventory/ItemPricesList.vue -->
<template>
    <div class="item-prices-list">
      <div class="card mb-4">
        <div class="card-header bg-white">
          <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Item Prices</h5>
            <div>
              <button @click="openCreateForm" class="btn btn-primary">
                <i class="fas fa-plus-circle me-2"></i>Add New Price
              </button>
            </div>
          </div>
        </div>
        <div class="card-body">
          <!-- Search and filters -->
          <div class="row mb-4">
            <div class="col-md-4">
              <div class="input-group">
                <input
                  type="text"
                  class="form-control"
                  placeholder="Search item prices..."
                  v-model="searchQuery"
                  @input="handleSearch"
                />
                <span class="input-group-text">
                  <i class="fas fa-search"></i>
                </span>
              </div>
            </div>
            <div class="col-md-3">
              <select class="form-select" v-model="priceType" @change="fetchPrices">
                <option value="all">All Price Types</option>
                <option value="purchase">Purchase Prices</option>
                <option value="sale">Sale Prices</option>
              </select>
            </div>
            <div class="col-md-3">
              <select class="form-select" v-model="priceStatus" @change="fetchPrices">
                <option value="all">All Statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
            <div class="col-md-2">
              <button @click="fetchPrices" class="btn btn-outline-primary w-100">
                <i class="fas fa-filter me-2"></i>Filter
              </button>
            </div>
          </div>

          <!-- Loading indicator -->
          <div v-if="loading" class="text-center py-5">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p class="mt-2">Loading price data...</p>
          </div>

          <!-- Data table -->
          <div v-else-if="itemPrices.length > 0" class="table-responsive">
            <table class="table table-striped table-hover">
              <thead class="table-light">
                <tr>
                  <th @click="sortBy('price_id')" class="sortable">
                    ID
                    <i v-if="sortKey === 'price_id'" :class="getSortIconClass()"></i>
                  </th>
                  <th @click="sortBy('price_type')" class="sortable">
                    Type
                    <i v-if="sortKey === 'price_type'" :class="getSortIconClass()"></i>
                  </th>
                  <th @click="sortBy('item_code')" class="sortable">
                    Item Code
                    <i v-if="sortKey === 'item_code'" :class="getSortIconClass()"></i>
                  </th>
                  <th @click="sortBy('item_name')" class="sortable">
                    Item Name
                    <i v-if="sortKey === 'item_name'" :class="getSortIconClass()"></i>
                  </th>
                  <th @click="sortBy('entity_name')" class="sortable">
                    Vendor/Customer
                    <i v-if="sortKey === 'entity_name'" :class="getSortIconClass()"></i>
                  </th>
                  <th @click="sortBy('price')" class="sortable">
                    Price
                    <i v-if="sortKey === 'price'" :class="getSortIconClass()"></i>
                  </th>
                  <th @click="sortBy('start_date')" class="sortable">
                    Start Date
                    <i v-if="sortKey === 'start_date'" :class="getSortIconClass()"></i>
                  </th>
                  <th @click="sortBy('end_date')" class="sortable">
                    End Date
                    <i v-if="sortKey === 'end_date'" :class="getSortIconClass()"></i>
                  </th>
                  <th @click="sortBy('status')" class="sortable">
                    Status
                    <i v-if="sortKey === 'status'" :class="getSortIconClass()"></i>
                  </th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="price in itemPrices" :key="price.price_id">
                  <td>{{ price.price_id }}</td>
                  <td>
                    <span
                      :class="price.price_type === 'purchase' ? 'badge bg-info' : 'badge bg-success'"
                    >
                      {{ price.price_type === 'purchase' ? 'Purchase' : 'Sale' }}
                    </span>
                  </td>
                  <td>{{ price.item_code }}</td>
                  <td>{{ price.item_name }}</td>
                  <td>{{ price.entity_name }}</td>
                  <td>{{ formatCurrency(price.price) }}</td>
                  <td>{{ formatDate(price.start_date) }}</td>
                  <td>{{ formatDate(price.end_date) }}</td>
                  <td>
                    <span
                      :class="price.status === 'active' ? 'badge bg-success' : 'badge bg-secondary'"
                    >
                      {{ price.status }}
                    </span>
                  </td>
                  <td>
                    <div class="btn-group">
                      <button @click="editPrice(price)" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button @click="togglePriceStatus(price)" class="btn btn-sm btn-outline-warning">
                        <i :class="price.status === 'active' ? 'fas fa-ban' : 'fas fa-check'"></i>
                      </button>
                      <button @click="confirmDelete(price)" class="btn btn-sm btn-outline-danger">
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-4">
              <div>
                <span>Showing {{ paginationInfo.from }} to {{ paginationInfo.to }} of {{ paginationInfo.total }} entries</span>
              </div>
              <pagination-component
                :pagination="pagination"
                @page-changed="changePage"
              ></pagination-component>
            </div>
          </div>

          <!-- Empty state -->
          <div v-else class="text-center py-5">
            <i class="fas fa-dollar-sign fa-3x text-muted mb-3"></i>
            <h5>No Price Data Found</h5>
            <p class="text-muted">No item prices match your current filters or search criteria.</p>
            <button @click="resetFilters" class="btn btn-outline-primary mt-2">
              Reset Filters
            </button>
          </div>
        </div>
      </div>

      <!-- Confirmation Modal for Delete -->
      <confirmation-modal
        v-if="showDeleteModal"
        :title="'Delete Price'"
        :message="'Are you sure you want to delete this price record? This action cannot be undone.'"
        :confirmText="'Delete'"
        :cancelText="'Cancel'"
        @confirm="deletePrice"
        @cancel="cancelDelete"
      ></confirmation-modal>

      <!-- Item Price Form Modal -->
      <div v-if="showFormModal" class="modal fade show" tabindex="-1" style="display: block;">
        <div class="modal-dialog modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">{{ isEditing ? 'Edit Price' : 'Add New Price' }}</h5>
              <button @click="closeForm" type="button" class="btn-close"></button>
            </div>
            <div class="modal-body">
              <item-price-form
                :price-data="selectedPrice"
                :is-editing="isEditing"
                @save-success="onSaveSuccess"
                @cancel="closeForm"
              ></item-price-form>
            </div>
          </div>
        </div>
        <div class="modal-backdrop fade show"></div>
      </div>
    </div>
  </template>

  <script>
  import { ref, onMounted, computed } from 'vue';
  import { useRoute } from 'vue-router';
  import axios from 'axios';
  import ItemPriceForm from './ItemPriceForm.vue';

  export default {
    name: 'ItemPricesList',
    components: {
      ItemPriceForm
    },
    setup() {
      const route = useRoute();
      //const router = useRouter();

      // State
      const itemPrices = ref([]);
      const loading = ref(true);
      const searchQuery = ref('');
      const priceType = ref('all');
      const priceStatus = ref('all');
      const sortKey = ref('price_id');
      const sortOrder = ref('desc');
      const showDeleteModal = ref(false);
      const showFormModal = ref(false);
      const isEditing = ref(false);
      const selectedPrice = ref(null);
      const priceToDelete = ref(null);

      // Pagination
      const pagination = ref({
        current_page: 1,
        per_page: 15,
        total: 0
      });

      const paginationInfo = computed(() => {
        return {
          from: (pagination.value.current_page - 1) * pagination.value.per_page + 1,
          to: Math.min(pagination.value.current_page * pagination.value.per_page, pagination.value.total),
          total: pagination.value.total
        };
      });

      // Item ID from route if applicable
      const itemId = computed(() => route.params.id || null);

      // Methods
      const fetchPrices = async (page = 1) => {
        loading.value = true;
        try {
          let url = `/api/item-prices?page=${page}&per_page=${pagination.value.per_page}`;

          // Add filters
          if (searchQuery.value) {
            url += `&search=${searchQuery.value}`;
          }
          if (priceType.value !== 'all') {
            url += `&price_type=${priceType.value}`;
          }
          if (priceStatus.value !== 'all') {
            url += `&status=${priceStatus.value}`;
          }
          if (itemId.value) {
            url += `&item_id=${itemId.value}`;
          }

          // Add sorting
          url += `&sort_field=${sortKey.value}&sort_direction=${sortOrder.value}`;

          const response = await axios.get(url);

          if (response.data.status === 'success') {
            itemPrices.value = response.data.data.data;
            pagination.value = {
              current_page: response.data.data.current_page,
              per_page: response.data.data.per_page,
              total: response.data.data.total
            };
          } else {
            console.error('Error fetching item prices:', response.data.message);
          }
        } catch (error) {
          console.error('Error fetching item prices:', error);
        } finally {
          loading.value = false;
        }
      };

      const handleSearch = () => {
        // Debounce search input
        if (window.searchTimeout) {
          clearTimeout(window.searchTimeout);
        }
        window.searchTimeout = setTimeout(() => {
          fetchPrices();
        }, 300);
      };

      const sortBy = (key) => {
        if (sortKey.value === key) {
          sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
        } else {
          sortKey.value = key;
          sortOrder.value = 'asc';
        }
        fetchPrices();
      };

      const getSortIconClass = () => {
        return sortOrder.value === 'asc'
          ? 'fas fa-sort-up'
          : 'fas fa-sort-down';
      };

      const changePage = (page) => {
        fetchPrices(page);
      };

      const resetFilters = () => {
        searchQuery.value = '';
        priceType.value = 'all';
        priceStatus.value = 'all';
        sortKey.value = 'price_id';
        sortOrder.value = 'desc';
        fetchPrices();
      };

      const openCreateForm = () => {
        isEditing.value = false;
        selectedPrice.value = {
          item_id: itemId.value,
          price_type: 'sale',
          price: 0,
          status: 'active',
          start_date: new Date().toISOString().split('T')[0],
          end_date: null
        };
        showFormModal.value = true;
      };

      const editPrice = (price) => {
        isEditing.value = true;
        selectedPrice.value = { ...price };
        showFormModal.value = true;
      };

      const closeForm = () => {
        showFormModal.value = false;
        selectedPrice.value = null;
      };

      const onSaveSuccess = () => {
        closeForm();
        fetchPrices(pagination.value.current_page);
      };

      const confirmDelete = (price) => {
        priceToDelete.value = price;
        showDeleteModal.value = true;
      };

      const deletePrice = async () => {
        try {
          const response = await axios.delete(`/api/item-prices/${priceToDelete.value.price_id}`);
          if (response.data.status === 'success') {
            // Show success notification
            alert('Price record deleted successfully');
            fetchPrices(pagination.value.current_page);
          } else {
            alert('Error deleting price record: ' + response.data.message);
          }
        } catch (error) {
          console.error('Error deleting price:', error);
          alert('Error deleting price record');
        } finally {
          showDeleteModal.value = false;
          priceToDelete.value = null;
        }
      };

      const cancelDelete = () => {
        showDeleteModal.value = false;
        priceToDelete.value = null;
      };

      const togglePriceStatus = async (price) => {
        try {
          const newStatus = price.status === 'active' ? 'inactive' : 'active';
          const response = await axios.patch(`/api/item-prices/${price.price_id}/status`, {
            status: newStatus
          });

          if (response.data.status === 'success') {
            // Update the local state
            price.status = newStatus;
            // Show success notification
            alert(`Price status updated to ${newStatus}`);
          } else {
            alert('Error updating price status: ' + response.data.message);
          }
        } catch (error) {
          console.error('Error updating price status:', error);
          alert('Error updating price status');
        }
      };

      const formatCurrency = (value) => {
        if (!value) return '0.00';
        return new Intl.NumberFormat('en-US', {
          style: 'currency',
          currency: 'USD'
        }).format(value);
      };

      const formatDate = (dateString) => {
        if (!dateString) return 'No End Date';
        const date = new Date(dateString);
        return new Intl.DateTimeFormat('en-US', {
          year: 'numeric',
          month: 'short',
          day: 'numeric'
        }).format(date);
      };

      // Initialize
      onMounted(() => {
        fetchPrices();
      });

      return {
        itemPrices,
        loading,
        searchQuery,
        priceType,
        priceStatus,
        sortKey,
        sortOrder,
        pagination,
        paginationInfo,
        showDeleteModal,
        showFormModal,
        isEditing,
        selectedPrice,
        handleSearch,
        fetchPrices,
        sortBy,
        getSortIconClass,
        changePage,
        resetFilters,
        openCreateForm,
        editPrice,
        closeForm,
        onSaveSuccess,
        confirmDelete,
        deletePrice,
        cancelDelete,
        togglePriceStatus,
        formatCurrency,
        formatDate
      };
    }
  };
  </script>

  <style scoped>
  .sortable {
    cursor: pointer;
  }

  .sortable:hover {
    background-color: #f8f9fa;
  }

  th i {
    margin-left: 5px;
  }

  .modal-backdrop {
    opacity: 0.5;
  }

  .card {
    border-radius: 0.5rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  }

  .card-header {
    border-bottom: 1px solid #e2e8f0;
    padding: 1rem 1.5rem;
  }

  .btn-group .btn {
    padding: 0.375rem 0.5rem;
  }
  </style>
