<!-- src/views/planning/MaterialPlansList.vue -->
<template>
    <div class="material-plans-list">
      <div class="page-header">
        <h1 class="page-title">Material Plans</h1>
        <div class="header-actions">
          <router-link to="/planning/generate-plans" class="btn btn-primary">
            <i class="fas fa-plus mr-2"></i>Generate New Plans
          </router-link>
          <router-link to="/planning/generate-requisitions" class="btn btn-success ml-2">
            <i class="fas fa-file-alt mr-2"></i>Generate Requisitions
          </router-link>
          <button class="btn btn-outline-secondary ml-2" @click="exportToCsv">
            <i class="fas fa-download mr-2"></i>Export
          </button>
        </div>
      </div>

      <!-- Filters -->
      <div class="card mb-4">
        <div class="card-body">
          <div class="row">
            <div class="col-md-3">
              <div class="form-group">
                <label for="period-filter">Planning Period</label>
                <select id="period-filter" class="form-control" v-model="filters.period">
                  <option value="">All Periods</option>
                  <option v-for="period in availablePeriods" :key="period" :value="period">
                    {{ formatPeriod(period) }}
                  </option>
                </select>
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label for="type-filter">Material Type</label>
                <select id="type-filter" class="form-control" v-model="filters.materialType">
                  <option value="">All Types</option>
                  <option value="FG">Finished Goods</option>
                  <option value="RM">Raw Materials</option>
                </select>
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label for="status-filter">Status</label>
                <select id="status-filter" class="form-control" v-model="filters.status">
                  <option value="">All Statuses</option>
                  <option value="Draft">Draft</option>
                  <option value="Requisitioned">Requisitioned</option>
                  <option value="Completed">Completed</option>
                </select>
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label for="search-filter">Search</label>
                <input
                  type="text"
                  id="search-filter"
                  class="form-control"
                  placeholder="Search by item..."
                  v-model="filters.search"
                />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="stats-cards mb-4">
        <div class="stat-card">
          <div class="stat-card-body">
            <div class="stat-icon"><i class="fas fa-boxes"></i></div>
            <div class="stat-info">
              <div class="stat-value">{{ totalPlans }}</div>
              <div class="stat-label">Total Plans</div>
            </div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-card-body">
            <div class="stat-icon"><i class="fas fa-industry"></i></div>
            <div class="stat-info">
              <div class="stat-value">{{ fgPlans }}</div>
              <div class="stat-label">Finished Goods</div>
            </div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-card-body">
            <div class="stat-icon"><i class="fas fa-cubes"></i></div>
            <div class="stat-info">
              <div class="stat-value">{{ rmPlans }}</div>
              <div class="stat-label">Raw Materials</div>
            </div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-card-body">
            <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
            <div class="stat-info">
              <div class="stat-value">{{ toRequisition }}</div>
              <div class="stat-label">To Requisition</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Loading Indicator -->
      <div v-if="isLoading" class="text-center my-5">
        <div class="spinner-border text-primary" role="status">
          <span class="sr-only">Loading...</span>
        </div>
        <p class="mt-2">Loading material plans...</p>
      </div>

      <!-- Empty State -->
      <div v-else-if="filteredPlans.length === 0" class="card">
        <div class="card-body text-center py-5">
          <i class="fas fa-search fa-3x text-muted mb-3"></i>
          <h3>No Material Plans Found</h3>
          <p class="text-muted">Try adjusting your filters or generate new material plans.</p>
          <router-link to="/planning/generate-plans" class="btn btn-primary mt-3">
            <i class="fas fa-plus mr-2"></i>Generate New Plans
          </router-link>
        </div>
      </div>

      <!-- Material Plans Table -->
      <div v-else class="card">
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>
                  <div class="d-flex align-items-center">
                    Item Code
                    <button class="btn-sort" @click="updateSorting('item_code')">
                      <i :class="getSortIconClass('item_code')"></i>
                    </button>
                  </div>
                </th>
                <th>Item Name</th>
                <th>
                  <div class="d-flex align-items-center">
                    Type
                    <button class="btn-sort" @click="updateSorting('material_type')">
                      <i :class="getSortIconClass('material_type')"></i>
                    </button>
                  </div>
                </th>
                <th>
                  <div class="d-flex align-items-center">
                    Period
                    <button class="btn-sort" @click="updateSorting('planning_period')">
                      <i :class="getSortIconClass('planning_period')"></i>
                    </button>
                  </div>
                </th>
                <th>Forecast Qty</th>
                <th>Available Stock</th>
                <th>Net Requirement</th>
                <th>Planned Order</th>
                <th>
                  <div class="d-flex align-items-center">
                    Status
                    <button class="btn-sort" @click="updateSorting('status')">
                      <i :class="getSortIconClass('status')"></i>
                    </button>
                  </div>
                </th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="plan in paginatedPlans" :key="plan.id">
                <td>{{ plan.item.item_code }}</td>
                <td>{{ plan.item.name }}</td>
                <td>
                  <span
                    :class="[
                      'badge',
                      plan.material_type === 'FG' ? 'badge-primary' : 'badge-info'
                    ]"
                  >
                    {{ plan.material_type === 'FG' ? 'Finished Good' : 'Raw Material' }}
                  </span>
                </td>
                <td>{{ formatPeriod(plan.planning_period) }}</td>
                <td>{{ formatNumber(plan.forecast_quantity) }}</td>
                <td>{{ formatNumber(plan.available_stock) }}</td>
                <td
                  :class="[
                    plan.net_requirement > 0 ? 'text-danger' : 'text-success'
                  ]"
                >
                  {{ formatNumber(plan.net_requirement) }}
                </td>
                <td>{{ formatNumber(plan.planned_order_quantity) }}</td>
                <td>
                  <span
                    :class="[
                      'badge',
                      plan.status === 'Draft' ? 'badge-warning' :
                      plan.status === 'Requisitioned' ? 'badge-success' : 'badge-secondary'
                    ]"
                  >
                    {{ plan.status }}
                  </span>
                </td>
                <td>
                  <button
                    class="btn btn-sm btn-outline-primary"
                    @click="viewPlanDetails(plan)"
                  >
                    <i class="fas fa-eye"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Pagination -->
      <div class="pagination-container mt-4" v-if="filteredPlans.length > 0">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            Showing {{ startIndex + 1 }} to {{ Math.min(endIndex, filteredPlans.length) }} of {{ filteredPlans.length }} material plans
          </div>
          <div>
            <button
              class="btn btn-sm btn-outline-primary mr-2"
              @click="previousPage"
              :disabled="currentPage === 1"
            >
              <i class="fas fa-chevron-left"></i> Previous
            </button>
            <button
              class="btn btn-sm btn-outline-primary"
              @click="nextPage"
              :disabled="endIndex >= filteredPlans.length"
            >
              Next <i class="fas fa-chevron-right"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- Plan Details Modal -->
      <div class="modal fade" id="planDetailsModal" tabindex="-1" ref="planDetailsModal">
        <div class="modal-dialog modal-lg">
          <div class="modal-content" v-if="selectedPlan">
            <div class="modal-header">
              <h5 class="modal-title">Material Plan Details</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <div class="row">
                <div class="col-md-6">
                  <h6>Item Information</h6>
                  <table class="table table-sm">
                    <tr>
                      <th>Item Code:</th>
                      <td>{{ selectedPlan.item.item_code }}</td>
                    </tr>
                    <tr>
                      <th>Item Name:</th>
                      <td>{{ selectedPlan.item.name }}</td>
                    </tr>
                    <tr>
                      <th>Type:</th>
                      <td>{{ selectedPlan.material_type === 'FG' ? 'Finished Good' : 'Raw Material' }}</td>
                    </tr>
                  </table>
                </div>
                <div class="col-md-6">
                  <h6>Plan Information</h6>
                  <table class="table table-sm">
                    <tr>
                      <th>Period:</th>
                      <td>{{ formatPeriod(selectedPlan.planning_period) }}</td>
                    </tr>
                    <tr>
                      <th>Status:</th>
                      <td>{{ selectedPlan.status }}</td>
                    </tr>
                    <tr>
                      <th>BOM:</th>
                      <td>{{ selectedPlan.bom_id ? `#${selectedPlan.bom_id}` : 'N/A' }}</td>
                    </tr>
                  </table>
                </div>
              </div>

              <div class="row mt-4">
                <div class="col-12">
                  <h6>Quantity Breakdown</h6>
                  <table class="table table-bordered">
                    <thead class="thead-light">
                      <tr>
                        <th>Forecast Quantity</th>
                        <th>Available Stock</th>
                        <th>WIP Stock</th>
                        <th>Buffer Quantity ({{ selectedPlan.buffer_percentage }}%)</th>
                        <th>Net Requirement</th>
                        <th>Planned Order</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td>{{ formatNumber(selectedPlan.forecast_quantity) }}</td>
                        <td>{{ formatNumber(selectedPlan.available_stock) }}</td>
                        <td>{{ formatNumber(selectedPlan.wip_stock) }}</td>
                        <td>{{ formatNumber(selectedPlan.buffer_quantity) }}</td>
                        <td
                          :class="[
                            selectedPlan.net_requirement > 0 ? 'text-danger' : 'text-success'
                          ]"
                        >
                          {{ formatNumber(selectedPlan.net_requirement) }}
                        </td>
                        <td>{{ formatNumber(selectedPlan.planned_order_quantity) }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <div class="modal-calculation mt-4">
                <h6>Calculation Summary</h6>
                <p>Net Requirement = Forecast Quantity - Available Stock - WIP Stock + Buffer Quantity</p>
                <p>
                  <strong>{{ formatNumber(selectedPlan.forecast_quantity) }}</strong> -
                  <strong>{{ formatNumber(selectedPlan.available_stock) }}</strong> -
                  <strong>{{ formatNumber(selectedPlan.wip_stock) }}</strong> +
                  <strong>{{ formatNumber(selectedPlan.buffer_quantity) }}</strong> =
                  <strong>{{ formatNumber(selectedPlan.net_requirement) }}</strong>
                </p>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>

              <!-- Action buttons based on plan type and status -->
              <template v-if="selectedPlan.material_type === 'RM' && selectedPlan.status === 'Draft'">
                <router-link
                  to="/planning/generate-requisitions"
                  class="btn btn-primary"
                >
                  <i class="fas fa-file-alt mr-2"></i>Generate Requisition
                </router-link>
              </template>

              <template v-else-if="selectedPlan.material_type === 'FG' && selectedPlan.status === 'Draft'">
                <button type="button" class="btn btn-primary" @click="createWorkOrder(selectedPlan)">
                  <i class="fas fa-industry mr-2"></i>Create Work Order
                </button>
              </template>
            </div>
          </div>
        </div>
      </div>
    </div>
  </template>

  <script>
  //import axios from 'axios';
  import $ from 'jquery';

  export default {
    name: 'MaterialPlansList',
    data() {
      return {
        plans: [],
        isLoading: true,
        filters: {
          period: '',
          materialType: '',
          status: '',
          search: ''
        },
        sorting: {
          field: 'planning_period',
          direction: 'asc'
        },
        currentPage: 1,
        itemsPerPage: 10,
        selectedPlan: null
      };
    },
    created() {
      this.fetchMaterialPlans();
    },
    methods: {
      async fetchMaterialPlans() {
        this.isLoading = true;
        try {
          // In a real implementation, this would be a call to your API
          // For demo purposes, we're creating mock data here
          // Replace this with your actual API endpoint
          // const response = await axios.get('/api/planning/material-plans');
          // this.plans = response.data.data;

          // Mock data for demonstration
          setTimeout(() => {
            this.plans = this.generateMockPlans();
            this.isLoading = false;
          }, 1000);
        } catch (error) {
          console.error('Error fetching material plans:', error);
          this.isLoading = false;
        }
      },

      generateMockPlans() {
        // Generate some mock data for demonstration
        const plans = [];
        const periods = ['2025-01-01', '2025-02-01', '2025-03-01'];
        const statuses = ['Draft', 'Requisitioned', 'Completed'];

        // Generate 20 plans
        for (let i = 1; i <= 20; i++) {
          const isFG = i <= 10;
          const forecast = Math.floor(Math.random() * 1000) + 100;
          const available = Math.floor(Math.random() * (forecast / 2));
          const wip = Math.floor(Math.random() * 50);
          const buffer = Math.floor(forecast * 0.2);
          const net = Math.max(0, forecast - available - wip + buffer);

          plans.push({
            id: i,
            item_id: i,
            item: {
              item_id: i,
              item_code: isFG ? `FG${i.toString().padStart(3, '0')}` : `RM${i.toString().padStart(3, '0')}`,
              name: isFG ? `Finished Good ${i}` : `Raw Material ${i}`
            },
            planning_period: periods[Math.floor(Math.random() * periods.length)],
            material_type: isFG ? 'FG' : 'RM',
            forecast_quantity: forecast,
            available_stock: available,
            wip_stock: wip,
            buffer_percentage: 20,
            buffer_quantity: buffer,
            net_requirement: net,
            planned_order_quantity: net,
            status: statuses[Math.floor(Math.random() * statuses.length)],
            bom_id: isFG ? (Math.floor(Math.random() * 5) + 1) : null
          });
        }

        return plans;
      },

      formatPeriod(dateStr) {
        if (!dateStr) return '';
        const date = new Date(dateStr);
        return new Intl.DateTimeFormat('en-US', { year: 'numeric', month: 'long' }).format(date);
      },

      formatNumber(num) {
        return num.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 });
      },

      updateSorting(field) {
        if (this.sorting.field === field) {
          // Toggle direction if clicking on the same field
          this.sorting.direction = this.sorting.direction === 'asc' ? 'desc' : 'asc';
        } else {
          // Set new field and default to ascending
          this.sorting.field = field;
          this.sorting.direction = 'asc';
        }
      },

      getSortIconClass(field) {
        if (this.sorting.field !== field) {
          return 'fas fa-sort';
        }
        return this.sorting.direction === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down';
      },

      previousPage() {
        if (this.currentPage > 1) {
          this.currentPage--;
        }
      },

      nextPage() {
        if (this.endIndex < this.filteredPlans.length) {
          this.currentPage++;
        }
      },

      viewPlanDetails(plan) {
        this.selectedPlan = plan;
        // Using jQuery to show the modal
        $(this.$refs.planDetailsModal).modal('show');
      },

      createWorkOrder(plan) {
        // In a real implementation, this would navigate to work order creation
        // or make an API call to create a work order
        alert(`Work order would be created for ${plan.item.name}`);
        $(this.$refs.planDetailsModal).modal('hide');
      },

      exportToCsv() {
        // Generate CSV content from filtered plans
        const headers = [
          'Item Code',
          'Item Name',
          'Type',
          'Period',
          'Forecast Quantity',
          'Available Stock',
          'WIP Stock',
          'Buffer Quantity',
          'Net Requirement',
          'Planned Order',
          'Status'
        ];

        const rows = this.filteredPlans.map(plan => [
          plan.item.item_code,
          plan.item.name,
          plan.material_type === 'FG' ? 'Finished Good' : 'Raw Material',
          plan.planning_period,
          plan.forecast_quantity,
          plan.available_stock,
          plan.wip_stock,
          plan.buffer_quantity,
          plan.net_requirement,
          plan.planned_order_quantity,
          plan.status
        ]);

        // Create CSV content
        const csvContent = [
          headers.join(','),
          ...rows.map(row => row.join(','))
        ].join('\n');

        // Create download link
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');

        link.setAttribute('href', url);
        link.setAttribute('download', `material-plans-${new Date().toISOString().slice(0, 10)}.csv`);
        link.style.visibility = 'hidden';

        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
      }
    },
    computed: {
      availablePeriods() {
        const periods = [...new Set(this.plans.map(plan => plan.planning_period))];
        return periods.sort();
      },
      filteredPlans() {
        let result = [...this.plans];

        // Apply filters
        if (this.filters.period) {
          result = result.filter(plan => plan.planning_period === this.filters.period);
        }

        if (this.filters.materialType) {
          result = result.filter(plan => plan.material_type === this.filters.materialType);
        }

        if (this.filters.status) {
          result = result.filter(plan => plan.status === this.filters.status);
        }

        if (this.filters.search) {
          const search = this.filters.search.toLowerCase();
          result = result.filter(plan =>
            plan.item.item_code.toLowerCase().includes(search) ||
            plan.item.name.toLowerCase().includes(search)
          );
        }

        // Apply sorting
        result.sort((a, b) => {
          let valueA, valueB;

          // Handle different field types
          switch (this.sorting.field) {
            case 'item_code':
              valueA = a.item.item_code;
              valueB = b.item.item_code;
              break;
            case 'planning_period':
              valueA = a.planning_period;
              valueB = b.planning_period;
              break;
            default:
              valueA = a[this.sorting.field];
              valueB = b[this.sorting.field];
          }

          // Compare values based on sorting direction
          if (this.sorting.direction === 'asc') {
            return valueA > valueB ? 1 : -1;
          } else {
            return valueA < valueB ? 1 : -1;
          }
        });

        return result;
      },
      startIndex() {
        return (this.currentPage - 1) * this.itemsPerPage;
      },
      endIndex() {
        return this.startIndex + this.itemsPerPage;
      },
      paginatedPlans() {
        return this.filteredPlans.slice(this.startIndex, this.endIndex);
      },
      totalPlans() {
        return this.plans.length;
      },
      fgPlans() {
        return this.plans.filter(plan => plan.material_type === 'FG').length;
      },
      rmPlans() {
        return this.plans.filter(plan => plan.material_type === 'RM').length;
      },
      toRequisition() {
        return this.plans.filter(plan =>
          plan.status === 'Draft' &&
          plan.material_type === 'RM' &&
          plan.net_requirement > 0
        ).length;
      }
    }
  };
  </script>

  <style scoped>
  .material-plans-list {
    padding: 20px;
  }

  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
  }

  .stats-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
  }

  .stat-card {
    background-color: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  }

  .stat-card-body {
    padding: 20px;
    display: flex;
    align-items: center;
  }

  .stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background-color: var(--primary-color);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
    font-size: 1.5rem;
  }

  .stat-info {
    flex: 1;
  }

  .stat-value {
    font-size: 1.8rem;
    font-weight: 600;
    line-height: 1.2;
  }

  .stat-label {
    color: var(--gray-600);
    font-size: 0.9rem;
  }

  .card {
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    border-radius: 4px;
    border: none;
    margin-bottom: 20px;
  }

  .table th {
    background-color: var(--gray-100);
    border-top: none;
  }

  .btn-sort {
    background: none;
    border: none;
    cursor: pointer;
    color: var(--gray-600);
    margin-left: 5px;
  }

  .btn-sort:hover {
    color: var(--primary-color);
  }

  .pagination-container {
    margin-top: 20px;
  }

  /* Badge styling */
  .badge {
    padding: 5px 10px;
    border-radius: 12px;
    font-weight: 500;
    font-size: 0.75rem;
  }

  .badge-primary {
    background-color: var(--primary-color);
    color: white;
  }

  .badge-info {
    background-color: #17a2b8;
    color: white;
  }

  .badge-warning {
    background-color: #ffc107;
    color: #212529;
  }

  .badge-success {
    background-color: #28a745;
    color: white;
  }

  .badge-secondary {
    background-color: #6c757d;
    color: white;
  }

  /* Modal styling */
  .modal-calculation {
    background-color: var(--gray-100);
    padding: 15px;
    border-radius: 4px;
  }

  .modal-header {
    background-color: var(--gray-100);
    border-bottom: 1px solid var(--gray-200);
  }

  .modal-footer {
    border-top: 1px solid var(--gray-200);
  }

  /* Table hover effect */
  .table-hover tbody tr:hover {
    background-color: rgba(37, 99, 235, 0.05);
  }

  /* Text highlights */
  .text-danger {
    color: #dc3545;
    font-weight: 500;
  }

  .text-success {
    color: #28a745;
    font-weight: 500;
  }

  /* Empty state styling */
  .empty-state {
    text-align: center;
    padding: 3rem 1rem;
  }

  .empty-state i {
    font-size: 3rem;
    color: var(--gray-400);
    margin-bottom: 1rem;
  }

  .empty-state h3 {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
  }

  .empty-state p {
    color: var(--gray-600);
  }

  /* Print styling */
  @media print {
    .header-actions,
    .pagination-container,
    .btn-sort,
    .btn {
      display: none !important;
    }

    .material-plans-list {
      padding: 0;
    }

    .card {
      box-shadow: none;
      border: 1px solid #dee2e6;
    }

    .page-header {
      margin-bottom: 1rem;
    }

    .stats-cards {
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
    }

    .stat-card {
      flex: 1;
      min-width: 120px;
      margin-right: 10px;
      margin-bottom: 10px;
      border: 1px solid #dee2e6;
    }

    .table {
      border-collapse: collapse;
      width: 100%;
    }

    .table th,
    .table td {
      border: 1px solid #dee2e6;
      padding: 8px;
    }
  }
  </style>
