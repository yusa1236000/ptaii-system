<!-- src/views/purchasing/VendorEvaluationList.vue -->
<template>
    <div class="vendor-evaluations-container">
      <div class="page-header">
        <h1 class="text-2xl font-semibold">Vendor Evaluations</h1>
        <div class="actions">
          <router-link to="/purchasing/evaluations/create" class="btn-primary">
            <i class="fas fa-plus mr-2"></i> New Evaluation
          </router-link>
        </div>
      </div>

      <div class="filters-panel">
        <div class="search-bar">
          <div class="form-group">
            <label for="vendor">Vendor</label>
            <select id="vendor" v-model="filters.vendor_id" class="form-control">
              <option value="">All Vendors</option>
              <option v-for="vendor in vendors" :key="vendor.vendor_id" :value="vendor.vendor_id">
                {{ vendor.name }}
              </option>
            </select>
          </div>
          <div class="form-group">
            <label for="dateFrom">Date From</label>
            <input type="date" id="dateFrom" v-model="filters.date_from" class="form-control" />
          </div>
          <div class="form-group">
            <label for="dateTo">Date To</label>
            <input type="date" id="dateTo" v-model="filters.date_to" class="form-control" />
          </div>
          <button @click="applyFilters" class="btn-secondary">
            <i class="fas fa-filter mr-2"></i> Apply Filters
          </button>
          <button @click="resetFilters" class="btn-outline">
            <i class="fas fa-undo mr-2"></i> Reset
          </button>
        </div>
      </div>

      <div class="evaluations-panel">
        <div v-if="loading" class="loading-container">
          <i class="fas fa-spinner fa-spin text-4xl text-primary"></i>
          <p>Loading evaluations...</p>
        </div>

        <div v-else-if="evaluations.length === 0" class="empty-state">
          <i class="fas fa-clipboard-check text-5xl mb-4 text-gray-400"></i>
          <h3>No evaluations found</h3>
          <p>Try adjusting your filters or create a new evaluation</p>
        </div>

        <div v-else class="table-container">
          <table class="evaluations-table">
            <thead>
              <tr>
                <th @click="sortBy('evaluation_id')" class="sortable-header">
                  ID
                  <i v-if="sortField === 'evaluation_id'" :class="['fas', sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down']"></i>
                </th>
                <th @click="sortBy('vendor.name')" class="sortable-header">
                  Vendor
                  <i v-if="sortField === 'vendor.name'" :class="['fas', sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down']"></i>
                </th>
                <th @click="sortBy('evaluation_date')" class="sortable-header">
                  Date
                  <i v-if="sortField === 'evaluation_date'" :class="['fas', sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down']"></i>
                </th>
                <th @click="sortBy('quality_score')" class="sortable-header">
                  Quality
                  <i v-if="sortField === 'quality_score'" :class="['fas', sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down']"></i>
                </th>
                <th @click="sortBy('delivery_score')" class="sortable-header">
                  Delivery
                  <i v-if="sortField === 'delivery_score'" :class="['fas', sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down']"></i>
                </th>
                <th @click="sortBy('price_score')" class="sortable-header">
                  Price
                  <i v-if="sortField === 'price_score'" :class="['fas', sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down']"></i>
                </th>
                <th @click="sortBy('service_score')" class="sortable-header">
                  Service
                  <i v-if="sortField === 'service_score'" :class="['fas', sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down']"></i>
                </th>
                <th @click="sortBy('total_score')" class="sortable-header">
                  Total Score
                  <i v-if="sortField === 'total_score'" :class="['fas', sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down']"></i>
                </th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="evaluation in evaluations" :key="evaluation.evaluation_id">
                <td>{{ evaluation.evaluation_id }}</td>
                <td>{{ evaluation.vendor ? evaluation.vendor.name : 'N/A' }}</td>
                <td>{{ formatDate(evaluation.evaluation_date) }}</td>
                <td>
                  <div class="score-badge" :class="getScoreClass(evaluation.quality_score)">
                    {{ evaluation.quality_score }}
                  </div>
                </td>
                <td>
                  <div class="score-badge" :class="getScoreClass(evaluation.delivery_score)">
                    {{ evaluation.delivery_score }}
                  </div>
                </td>
                <td>
                  <div class="score-badge" :class="getScoreClass(evaluation.price_score)">
                    {{ evaluation.price_score }}
                  </div>
                </td>
                <td>
                  <div class="score-badge" :class="getScoreClass(evaluation.service_score)">
                    {{ evaluation.service_score }}
                  </div>
                </td>
                <td>
                  <div class="score-badge total-score" :class="getScoreClass(evaluation.total_score)">
                    {{ evaluation.total_score.toFixed(2) }}
                  </div>
                </td>
                <td class="actions-cell">
                  <router-link :to="`/purchasing/evaluations/${evaluation.evaluation_id}`" class="btn-icon" title="View Details">
                    <i class="fas fa-eye"></i>
                  </router-link>
                  <router-link :to="`/purchasing/evaluations/${evaluation.evaluation_id}/edit`" class="btn-icon" title="Edit">
                    <i class="fas fa-edit"></i>
                  </router-link>
                  <button @click="confirmDelete(evaluation.evaluation_id)" class="btn-icon btn-danger" title="Delete">
                    <i class="fas fa-trash"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination Component -->
        <pagination-component
          v-if="pagination.total > 0"
          :total="pagination.total"
          :per-page="pagination.per_page"
          :current-page="pagination.current_page"
          :last-page="pagination.last_page"
          @page-changed="handlePageChange"
        ></pagination-component>
      </div>

      <!-- Delete Confirmation Modal -->
      <confirmation-modal
        v-if="showDeleteModal"
        :title="'Delete Vendor Evaluation'"
        :message="'Are you sure you want to delete this evaluation? This action cannot be undone.'"
        @confirm="deleteEvaluation"
        @cancel="showDeleteModal = false"
      ></confirmation-modal>
    </div>
  </template>

  <script>
  import axios from "axios";

  export default {
    name: "VendorEvaluationList",
    data() {
      return {
        evaluations: [],
        vendors: [],
        loading: false,
        filters: {
          vendor_id: "",
          date_from: "",
          date_to: "",
        },
        sortField: "evaluation_date",
        sortDirection: "desc",
        pagination: {
          total: 0,
          per_page: 15,
          current_page: 1,
          last_page: 1,
        },
        showDeleteModal: false,
        evaluationToDelete: null,
      };
    },
    created() {
      this.loadVendors();
      this.loadEvaluations();
    },
    methods: {
      async loadVendors() {
        try {
          const response = await axios.get("/api/vendors");
          this.vendors = response.data.data; // Assuming the response has a "data" property
        } catch (error) {
          console.error("Error loading vendors:", error);
          // You can add error handling/notification here
        }
      },
      async loadEvaluations() {
        this.loading = true;
        try {
          const params = {
            page: this.pagination.current_page,
            sort_field: this.sortField,
            sort_direction: this.sortDirection,
            ...this.filters
          };

          const response = await axios.get("/api/vendor-evaluations", { params });
          const data = response.data.data; // Accessing paginated data

          this.evaluations = data.data; // Data from pagination

          // Update pagination information
          this.pagination = {
            total: data.total,
            per_page: data.per_page,
            current_page: data.current_page,
            last_page: data.last_page
          };
        } catch (error) {
          console.error("Error loading evaluations:", error);
          // You can add error handling/notification here
        } finally {
          this.loading = false;
        }
      },
      formatDate(dateString) {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
          year: 'numeric',
          month: 'short',
          day: 'numeric'
        });
      },
      getScoreClass(score) {
        if (score >= 4) return 'score-excellent';
        if (score >= 3) return 'score-good';
        if (score >= 2) return 'score-average';
        return 'score-poor';
      },
      applyFilters() {
        this.pagination.current_page = 1; // Reset to first page when applying filters
        this.loadEvaluations();
      },
      resetFilters() {
        this.filters = {
          vendor_id: "",
          date_from: "",
          date_to: "",
        };
        this.pagination.current_page = 1;
        this.loadEvaluations();
      },
      sortBy(field) {
        if (this.sortField === field) {
          this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
          this.sortField = field;
          this.sortDirection = 'asc';
        }
        this.loadEvaluations();
      },
      handlePageChange(page) {
        this.pagination.current_page = page;
        this.loadEvaluations();
      },
      confirmDelete(evaluationId) {
        this.evaluationToDelete = evaluationId;
        this.showDeleteModal = true;
      },
      async deleteEvaluation() {
        try {
          await axios.delete(`/api/vendor-evaluations/${this.evaluationToDelete}`);
          this.showDeleteModal = false;
          this.evaluationToDelete = null;
          this.loadEvaluations(); // Reload the list
          // You can add a success notification here
        } catch (error) {
          console.error("Error deleting evaluation:", error);
          // You can add error handling/notification here
        }
      }
    }
  };
  </script>

  <style scoped>
  .vendor-evaluations-container {
    width: 100%;
    padding: 1rem;
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  }

  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
  }

  .btn-primary {
    display: inline-flex;
    align-items: center;
    background-color: var(--primary-color);
    color: white;
    padding: 0.625rem 1rem;
    border-radius: 0.375rem;
    font-weight: 500;
    transition: background-color 0.2s;
  }

  .btn-primary:hover {
    background-color: var(--primary-dark);
    text-decoration: none;
  }

  .filters-panel {
    background-color: var(--gray-50);
    padding: 1rem;
    border-radius: 0.375rem;
    margin-bottom: 1.5rem;
  }

  .search-bar {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    align-items: flex-end;
  }

  .form-group {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    flex: 1;
    min-width: 200px;
  }

  .form-control {
    padding: 0.5rem;
    border: 1px solid var(--gray-300);
    border-radius: 0.375rem;
    font-size: 0.875rem;
  }

  .btn-secondary {
    background-color: var(--gray-600);
    color: white;
    padding: 0.625rem 1rem;
    border-radius: 0.375rem;
    font-weight: 500;
    transition: background-color 0.2s;
    display: inline-flex;
    align-items: center;
  }

  .btn-secondary:hover {
    background-color: var(--gray-700);
  }

  .btn-outline {
    background-color: transparent;
    color: var(--gray-700);
    padding: 0.625rem 1rem;
    border: 1px solid var(--gray-300);
    border-radius: 0.375rem;
    font-weight: 500;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
  }

  .btn-outline:hover {
    border-color: var(--gray-500);
    color: var(--gray-800);
  }

  .loading-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem;
    color: var(--gray-500);
  }

  .empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem;
    color: var(--gray-500);
  }

  .table-container {
    overflow-x: auto;
  }

  .evaluations-table {
    width: 100%;
    border-collapse: collapse;
  }

  .evaluations-table th, .evaluations-table td {
    padding: 0.75rem 1rem;
    text-align: left;
    border-bottom: 1px solid var(--gray-200);
  }

  .evaluations-table th {
    background-color: var(--gray-100);
    font-weight: 600;
    font-size: 0.875rem;
    color: var(--gray-700);
  }

  .sortable-header {
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .score-badge {
    display: inline-block;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    color: white;
  }

  .score-excellent {
    background-color: var(--success-color);
  }

  .score-good {
    background-color: #3b82f6; /* Blue */
  }

  .score-average {
    background-color: var(--warning-color);
  }

  .score-poor {
    background-color: var(--danger-color);
  }

  .total-score {
    width: 60px;
    height: 40px;
    border-radius: 20px;
  }

  .actions-cell {
    display: flex;
    gap: 0.5rem;
  }

  .btn-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 4px;
    color: var(--gray-600);
    background-color: var(--gray-100);
    transition: all 0.2s;
  }

  .btn-icon:hover {
    background-color: var(--gray-200);
    color: var(--gray-800);
  }

  .btn-danger {
    color: var(--danger-color);
  }

  .btn-danger:hover {
    background-color: rgba(220, 38, 38, 0.1);
    color: var(--danger-color);
  }
  </style>
