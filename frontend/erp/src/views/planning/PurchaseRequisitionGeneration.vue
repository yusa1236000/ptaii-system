<!-- src/views/planning/PurchaseRequisitionGeneration.vue -->
<template>
    <div class="purchase-requisition-generation">
      <div class="page-header">
        <h1 class="page-title">Generate Purchase Requisitions</h1>
        <div class="header-actions">
          <router-link to="/planning/material-plans" class="btn btn-secondary">
            <i class="fas fa-list mr-2"></i>View Material Plans
          </router-link>
        </div>
      </div>

      <div class="row">
        <!-- Selection Panel -->
        <div class="col-md-5">
          <div class="card">
            <div class="card-header">
              <h5 class="card-title mb-0">Material Plans Selection</h5>
            </div>
            <div class="card-body">
              <div class="form-group">
                <label for="period-filter">Planning Period</label>
                <select
                  id="period-filter"
                  v-model="selectedPeriod"
                  class="form-control"
                  @change="filterPlans"
                >
                  <option value="">Select Period</option>
                  <option v-for="period in availablePeriods" :key="period" :value="period">
                    {{ formatPeriod(period) }}
                  </option>
                </select>
              </div>

              <div class="form-group mt-3">
                <label>Lead Time (Days)</label>
                <div class="input-group">
                  <input
                    type="number"
                    v-model="leadTimeDays"
                    class="form-control"
                    min="0"
                    placeholder="Lead time in days"
                  />
                  <div class="input-group-append">
                    <span class="input-group-text">days</span>
                  </div>
                </div>
                <small class="form-text text-muted">
                  Required delivery date will be set based on this lead time
                </small>
              </div>

              <div class="form-group mt-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <label class="mb-0">Available Raw Materials</label>
                  <div>
                    <button
                      class="btn btn-sm btn-outline-primary mr-2"
                      @click="selectAllItems"
                      :disabled="filteredPlans.length === 0"
                    >
                      <i class="fas fa-check-square mr-1"></i>Select All
                    </button>
                    <button
                      class="btn btn-sm btn-outline-secondary"
                      @click="deselectAllItems"
                      :disabled="selectedItems.length === 0"
                    >
                      <i class="fas fa-square mr-1"></i>Deselect All
                    </button>
                  </div>
                </div>

                <div class="plan-selection-list">
                  <div v-if="isLoading" class="text-center py-4">
                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                      <span class="sr-only">Loading...</span>
                    </div>
                    <p class="mb-0 mt-2">Loading material plans...</p>
                  </div>

                  <div v-else-if="filteredPlans.length === 0" class="text-center py-4">
                    <i class="fas fa-filter fa-2x text-muted mb-3"></i>
                    <p class="mb-0">No raw material plans found for the selected period.</p>
                    <p class="text-muted">Please select a different period or generate new material plans.</p>
                  </div>

                  <div v-else class="plan-item-container">
                    <div
                      v-for="plan in filteredPlans"
                      :key="plan.id"
                      class="plan-item"
                      :class="{ selected: isItemSelected(plan.id) }"
                      @click="toggleItemSelection(plan)"
                    >
                      <div class="material-info">
                        <div class="d-flex align-items-center">
                          <div class="checkbox-container mr-2">
                            <input
                              type="checkbox"
                              :checked="isItemSelected(plan.id)"
                              @click.stop
                            />
                            <div class="checkbox-custom"></div>
                          </div>
                          <div>
                            <div class="material-name">{{ plan.item.item_code }} - {{ plan.item.name }}</div>
                            <div class="material-details">
                              <span class="badge badge-info">{{ formatPeriod(plan.planning_period) }}</span>
                              <span class="text-muted ml-2">Net Req: {{ formatNumber(plan.net_requirement) }}</span>
                            </div>
                          </div>
                        </div>
                      </div>
                      <div class="quantity-input">
                        <input
                          type="number"
                          class="form-control form-control-sm"
                          v-model="itemQuantities[plan.id]"
                          min="0"
                          @click.stop
                        />
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Preview and Generation Panel -->
        <div class="col-md-7">
          <div class="card">
            <div class="card-header">
              <h5 class="card-title mb-0">Purchase Requisition Preview</h5>
            </div>
            <div class="card-body">
              <div v-if="selectedItems.length === 0" class="text-center py-5">
                <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                <h4>No Items Selected</h4>
                <p class="text-muted">
                  Please select items from the list on the left to include in the purchase requisition.
                </p>
              </div>

              <div v-else>
                <!-- PR Header -->
                <div class="pr-header">
                  <div class="row">
                    <div class="col-md-6">
                      <div class="form-group">
                        <label>PR Number</label>
                        <input
                          type="text"
                          class="form-control"
                          value="PR-AUTO-20250427-001"
                          readonly
                        />
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <label>PR Date</label>
                        <input
                          type="text"
                          class="form-control"
                          :value="currentDate"
                          readonly
                        />
                      </div>
                    </div>
                  </div>
                  <div class="row">
                    <div class="col-md-6">
                      <div class="form-group">
                        <label>Requester</label>
                        <input
                          type="text"
                          class="form-control"
                          value="Current User"
                          readonly
                        />
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <label>Required Delivery Date</label>
                        <input
                          type="text"
                          class="form-control"
                          :value="requiredDeliveryDate"
                          readonly
                        />
                      </div>
                    </div>
                  </div>
                  <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea
                      id="notes"
                      v-model="notes"
                      class="form-control"
                      rows="2"
                      placeholder="Add notes or special instructions here..."
                    ></textarea>
                  </div>
                </div>

                <!-- PR Lines -->
                <div class="pr-lines mt-4">
                  <h6>Selected Items</h6>
                  <div class="table-responsive">
                    <table class="table table-bordered">
                      <thead class="thead-light">
                        <tr>
                          <th width="10%">#</th>
                          <th width="15%">Item Code</th>
                          <th width="35%">Item Name</th>
                          <th width="20%">Quantity</th>
                          <th width="20%">Action</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(itemId, index) in selectedItems" :key="itemId">
                          <td>{{ index + 1 }}</td>
                          <td>{{ getItemById(itemId).item.item_code }}</td>
                          <td>{{ getItemById(itemId).item.name }}</td>
                          <td>{{ formatNumber(getItemQuantity(itemId)) }}</td>
                          <td>
                            <button
                              class="btn btn-sm btn-outline-danger"
                              @click="removeItem(itemId)"
                            >
                              <i class="fas fa-trash-alt"></i>
                            </button>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>

                <!-- Summary -->
                <div class="pr-summary mt-4">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <span class="text-muted">Total Items:</span>
                      <strong class="ml-2">{{ selectedItems.length }}</strong>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="card-footer">
              <button
                class="btn btn-primary"
                :disabled="selectedItems.length === 0 || isSubmitting"
                @click="generateRequisition"
              >
                <i class="fas fa-file-alt mr-2"></i>
                {{ isSubmitting ? 'Generating...' : 'Generate Purchase Requisition' }}
              </button>
              <button class="btn btn-outline-secondary ml-2" @click="resetForm">
                <i class="fas fa-undo mr-2"></i>Reset
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Success Modal -->
      <div class="modal fade" id="successModal" tabindex="-1" ref="successModal">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header bg-success text-white">
              <h5 class="modal-title">
                <i class="fas fa-check-circle mr-2"></i>Purchase Requisition Generated
              </h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <p>Purchase requisition has been successfully generated.</p>
              <div class="alert alert-info">
                <strong>PR Number:</strong> {{ generatedPR ? generatedPR.pr_number : '' }}
              </div>
              <p>The selected material plans have been updated to status "Requisitioned".</p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
              <button type="button" class="btn btn-primary" @click="viewPurchaseRequisition">
                <i class="fas fa-eye mr-2"></i>View Purchase Requisition
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Error Modal -->
      <div class="modal fade" id="errorModal" tabindex="-1" ref="errorModal">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header bg-danger text-white">
              <h5 class="modal-title">
                <i class="fas fa-exclamation-triangle mr-2"></i>Error
              </h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <p>Failed to generate purchase requisition.</p>
              <div class="alert alert-danger">
                {{ error }}
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </template>

  <script>
//   import axios from 'axios';
  import $ from 'jquery';

  export default {
    name: 'PurchaseRequisitionGeneration',
    data() {
      return {
        plans: [],
        selectedPeriod: '',
        leadTimeDays: 14,
        selectedItems: [],
        itemQuantities: {},
        notes: 'Auto-generated from material planning',
        isLoading: true,
        isSubmitting: false,
        generatedPR: null,
        error: null
      };
    },
    computed: {
      availablePeriods() {
        const periods = [...new Set(this.plans.map(plan => plan.planning_period))];
        return periods.sort();
      },
      filteredPlans() {
        if (!this.selectedPeriod) return [];

        return this.plans.filter(plan =>
          plan.planning_period === this.selectedPeriod &&
          plan.material_type === 'RM' &&
          plan.status === 'Draft' &&
          plan.net_requirement > 0
        );
      },
      currentDate() {
        return new Date().toLocaleDateString('en-US', {
          year: 'numeric', month: 'long', day: 'numeric'
        });
      },
      requiredDeliveryDate() {
        if (!this.selectedPeriod || !this.leadTimeDays) return '';

        const periodDate = new Date(this.selectedPeriod);
        const deliveryDate = new Date(periodDate);
        deliveryDate.setDate(deliveryDate.getDate() + parseInt(this.leadTimeDays));

        return deliveryDate.toLocaleDateString('en-US', {
          year: 'numeric', month: 'long', day: 'numeric'
        });
      }
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

        // Generate 15 raw material plans
        for (let i = 1; i <= 15; i++) {
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
              item_code: `RM${i.toString().padStart(3, '0')}`,
              name: `Raw Material ${i}`
            },
            planning_period: periods[Math.floor(Math.random() * periods.length)],
            material_type: 'RM',
            forecast_quantity: forecast,
            available_stock: available,
            wip_stock: wip,
            buffer_percentage: 20,
            buffer_quantity: buffer,
            net_requirement: net,
            planned_order_quantity: net,
            status: 'Draft',
            bom_id: null
          });
        }

        return plans;
      },

      filterPlans() {
        // Clear selections when period changes
        this.selectedItems = [];
        this.itemQuantities = {};
      },

      selectAllItems() {
        this.selectedItems = this.filteredPlans.map(plan => plan.id);

        // Initialize quantities
        this.filteredPlans.forEach(plan => {
          this.itemQuantities[plan.id] = plan.planned_order_quantity;
        });
      },

      deselectAllItems() {
        this.selectedItems = [];
        this.itemQuantities = {};
      },

      isItemSelected(itemId) {
        return this.selectedItems.includes(itemId);
      },

      toggleItemSelection(plan) {
        const index = this.selectedItems.indexOf(plan.id);

        if (index === -1) {
          // Add to selection
          this.selectedItems.push(plan.id);
          this.itemQuantities[plan.id] = plan.planned_order_quantity;
        } else {
          // Remove from selection
          this.selectedItems.splice(index, 1);
          delete this.itemQuantities[plan.id];
        }
      },

      getItemById(itemId) {
        return this.plans.find(plan => plan.id === itemId);
      },

      getItemQuantity(itemId) {
        return this.itemQuantities[itemId] || 0;
      },

      updateItemQuantity(itemId, value) {
        this.itemQuantities[itemId] = parseFloat(value) || 0;
      },

      removeItem(itemId) {
        const index = this.selectedItems.indexOf(itemId);
        if (index !== -1) {
          this.selectedItems.splice(index, 1);
          delete this.itemQuantities[itemId];
        }
      },

      formatPeriod(dateStr) {
        if (!dateStr) return '';
        const date = new Date(dateStr);
        return new Intl.DateTimeFormat('en-US', { year: 'numeric', month: 'long' }).format(date);
      },

      formatNumber(num) {
        return num.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 });
      },

      async generateRequisition() {
        if (this.selectedItems.length === 0) return;

        this.isSubmitting = true;
        this.error = null;

        try {
          // In a real implementation, this would be a call to your API
          // Replace this with your actual API endpoint
          /*
          const response = await axios.post('/api/planning/generate-purchase-requisitions', {
            period: this.selectedPeriod,
            lead_time_days: this.leadTimeDays,
            item_ids: this.selectedItems,
            notes: this.notes
          });
          this.generatedPR = response.data.data;
          */

          // Mock response for demonstration
          setTimeout(() => {
            this.generatedPR = {
              pr_id: 1,
              pr_number: 'PR-AUTO-20250427-001',
              pr_date: new Date().toISOString(),
              requester_id: 1,
              status: 'draft',
              notes: this.notes,
              lines: this.selectedItems.map((itemId, index) => {
                const plan = this.getItemById(itemId);
                return {
                  line_id: index + 1,
                  pr_id: 1,
                  item_id: plan.item_id,
                  quantity: this.itemQuantities[itemId],
                  uom_id: 1,
                  required_date: new Date(new Date(this.selectedPeriod).getTime() + this.leadTimeDays * 24 * 60 * 60 * 1000).toISOString(),
                  notes: `Based on material planning for ${this.formatPeriod(this.selectedPeriod)}`
                };
              })
            };

            // Update plan status to 'Requisitioned'
            this.selectedItems.forEach(itemId => {
              const plan = this.plans.find(p => p.id === itemId);
              if (plan) plan.status = 'Requisitioned';
            });

            // Show success modal
            $(this.$refs.successModal).modal('show');
            this.isSubmitting = false;
          }, 1500);
        } catch (error) {
          console.error('Error generating purchase requisition:', error);
          this.error = error.response?.data?.message || 'Failed to generate purchase requisition';
          $(this.$refs.errorModal).modal('show');
          this.isSubmitting = false;
        }
      },

      resetForm() {
        this.selectedPeriod = '';
        this.leadTimeDays = 14;
        this.selectedItems = [];
        this.itemQuantities = {};
        this.notes = 'Auto-generated from material planning';
        this.generatedPR = null;
        this.error = null;
      },

      viewPurchaseRequisition() {
        // In a real app, this would navigate to the PR detail page
        // For demo, we'll just close the modal
        $(this.$refs.successModal).modal('hide');

        // Simulate navigation to PR detail
        this.$router.push('/purchasing/requisitions');
      }
    }
  };
  </script>

  <style scoped>
  .purchase-requisition-generation {
    padding: 20px;
  }

  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
  }

  .card {
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    border-radius: 4px;
    border: none;
    margin-bottom: 20px;
  }

  .card-header {
    background-color: var(--gray-100);
    border-bottom: 1px solid var(--gray-200);
  }

  .plan-selection-list {
    border: 1px solid var(--gray-300);
    border-radius: 4px;
    max-height: 350px;
    overflow-y: auto;
  }

  .plan-item-container {
    max-height: 350px;
    overflow-y: auto;
  }

  .plan-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 15px;
    border-bottom: 1px solid var(--gray-200);
    cursor: pointer;
    transition: background-color 0.2s;
  }

  .plan-item:last-child {
    border-bottom: none;
  }

  .plan-item:hover {
    background-color: var(--gray-100);
  }

  .plan-item.selected {
    background-color: rgba(37, 99, 235, 0.1);
  }

  .material-name {
    font-weight: 500;
  }

  .material-details {
    font-size: 0.875rem;
    margin-top: 4px;
  }

  .quantity-input {
    width: 80px;
  }

  .checkbox-container {
    position: relative;
    display: inline-block;
    width: 18px;
    height: 18px;
  }

  .checkbox-container input {
    opacity: 0;
    position: absolute;
  }

  .checkbox-custom {
    position: absolute;
    top: 0;
    left: 0;
    width: 18px;
    height: 18px;
    border: 2px solid var(--gray-400);
    border-radius: 3px;
  }

  .checkbox-container input:checked + .checkbox-custom {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
  }

  .checkbox-container input:checked + .checkbox-custom:after {
    content: '';
    position: absolute;
    display: block;
    left: 5px;
    top: 2px;
    width: 5px;
    height: 10px;
    border: solid white;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
  }

  .pr-header {
    margin-bottom: 20px;
  }

  .pr-lines {
    margin-top: 20px;
  }

  .pr-summary {
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid var(--gray-200);
  }

  .badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-weight: 500;
    font-size: 0.75rem;
  }

  .badge-info {
    background-color: #17a2b8;
    color: white;
  }
  </style>
