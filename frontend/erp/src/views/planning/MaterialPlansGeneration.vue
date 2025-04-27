<!-- src/views/planning/MaterialPlansGeneration.vue -->
<template>
    <div class="material-plans-generation">
      <div class="page-header">
        <h1 class="page-title">Generate Material Plans</h1>
        <div class="header-actions">
          <router-link to="/planning/material-plans" class="btn btn-secondary">
            <i class="fas fa-list mr-2"></i>View Material Plans
          </router-link>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <form @submit.prevent="generatePlans">
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="start_period">Start Period</label>
                  <input
                    type="month"
                    id="start_period"
                    v-model="formData.start_period"
                    class="form-control"
                    required
                  />
                  <small class="form-text text-muted">Plans will be generated for 6 months from this date</small>
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-group">
                  <label for="buffer_percentage">Buffer Percentage</label>
                  <div class="input-group">
                    <input
                      type="number"
                      id="buffer_percentage"
                      v-model="formData.buffer_percentage"
                      class="form-control"
                      min="0"
                      max="100"
                      required
                    />
                    <div class="input-group-append">
                      <span class="input-group-text">%</span>
                    </div>
                  </div>
                  <small class="form-text text-muted">Safety stock percentage to add to requirements</small>
                </div>
              </div>
            </div>

            <div class="form-group">
              <label>Select Items (Optional)</label>
              <div class="item-selector">
                <div class="selected-items-container" v-if="selectedItems.length > 0">
                  <div class="selected-item" v-for="item in selectedItems" :key="item.item_id">
                    <span>{{ item.item_code }} - {{ item.name }}</span>
                    <button type="button" class="btn-remove" @click="removeItem(item)">
                      <i class="fas fa-times"></i>
                    </button>
                  </div>
                </div>
                <div class="item-search mt-2">
                  <div class="input-group">
                    <input
                      type="text"
                      class="form-control"
                      placeholder="Search items..."
                      v-model="itemSearch"
                      @input="searchItems"
                    />
                    <div class="input-group-append">
                      <button class="btn btn-outline-secondary" type="button">
                        <i class="fas fa-search"></i>
                      </button>
                    </div>
                  </div>
                  <div class="search-results" v-if="searchResults.length > 0">
                    <div
                      class="search-item"
                      v-for="item in searchResults"
                      :key="item.item_id"
                      @click="selectItem(item)"
                    >
                      {{ item.item_code }} - {{ item.name }}
                    </div>
                  </div>
                </div>
              </div>
              <small class="form-text text-muted">If none selected, plans will be generated for all finished goods with forecasts</small>
            </div>

            <div class="form-group">
              <div class="form-check">
                <input
                  type="checkbox"
                  id="explode_bom"
                  v-model="formData.explode_bom"
                  class="form-check-input"
                />
                <label class="form-check-label" for="explode_bom">Calculate Raw Material Requirements</label>
                <small class="d-block form-text text-muted">Automatically explode BOMs to calculate raw material needs</small>
              </div>
            </div>

            <div class="form-actions">
              <button type="submit" class="btn btn-primary" :disabled="isSubmitting">
                <i class="fas fa-cog mr-2"></i>
                {{ isSubmitting ? 'Processing...' : 'Generate Material Plans' }}
              </button>
              <button type="button" class="btn btn-outline-secondary ml-2" @click="resetForm">
                <i class="fas fa-undo mr-2"></i>Reset
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Results Panel -->
      <div class="card mt-4" v-if="generationResults">
        <div class="card-header bg-success text-white">
          <h5 class="card-title mb-0">
            <i class="fas fa-check-circle mr-2"></i>Material Plans Generated Successfully
          </h5>
        </div>
        <div class="card-body">
          <div class="summary-stats">
            <div class="stat-item">
              <div class="stat-value">{{ generationResults.finished_goods ? generationResults.finished_goods.length : 0 }}</div>
              <div class="stat-label">Finished Goods Plans</div>
            </div>
            <div class="stat-item">
              <div class="stat-value">{{ generationResults.raw_materials ? generationResults.raw_materials.length : 0 }}</div>
              <div class="stat-label">Raw Material Plans</div>
            </div>
          </div>

          <div class="mt-4">
            <router-link to="/planning/material-plans" class="btn btn-primary">
              <i class="fas fa-eye mr-2"></i>View Material Plans
            </router-link>
            <button class="btn btn-outline-primary ml-2" @click="generateNewPlans">
              <i class="fas fa-plus mr-2"></i>Generate New Plans
            </button>
          </div>
        </div>
      </div>

      <!-- Error Panel -->
      <div class="card mt-4 bg-danger text-white" v-if="error">
        <div class="card-body">
          <h5 class="card-title">
            <i class="fas fa-exclamation-triangle mr-2"></i>Error
          </h5>
          <p class="card-text">{{ error }}</p>
        </div>
      </div>
    </div>
  </template>

  <script>
  import axios from 'axios';

  export default {
    name: 'MaterialPlansGeneration',
    data() {
      return {
        formData: {
          start_period: this.getCurrentMonth(),
          buffer_percentage: 20,
          item_ids: [],
          explode_bom: true
        },
        selectedItems: [],
        itemSearch: '',
        searchResults: [],
        isSubmitting: false,
        generationResults: null,
        error: null
      };
    },
    methods: {
      getCurrentMonth() {
        const now = new Date();
        const year = now.getFullYear();
        const month = (now.getMonth() + 1).toString().padStart(2, '0');
        return `${year}-${month}`;
      },
      async searchItems() {
        if (this.itemSearch.length < 2) {
          this.searchResults = [];
          return;
        }

        try {
          const response = await axios.get('/api/items', {
            params: {
              search: this.itemSearch,
              limit: 10
            }
          });
          this.searchResults = response.data.data;
        } catch (error) {
          console.error('Error searching items:', error);
        }
      },
      selectItem(item) {
        if (!this.selectedItems.some(i => i.item_id === item.item_id)) {
          this.selectedItems.push(item);
          this.formData.item_ids.push(item.item_id);
        }
        this.itemSearch = '';
        this.searchResults = [];
      },
      removeItem(item) {
        this.selectedItems = this.selectedItems.filter(i => i.item_id !== item.item_id);
        this.formData.item_ids = this.formData.item_ids.filter(id => id !== item.item_id);
      },
      async generatePlans() {
        this.isSubmitting = true;
        this.error = null;
        this.generationResults = null;

        try {
          // Format start_period to YYYY-MM-DD (first day of month)
          const formattedStartPeriod = `${this.formData.start_period}-01`;

          const payload = {
            start_period: formattedStartPeriod,
            buffer_percentage: this.formData.buffer_percentage
          };

          // Add item_ids only if there are selected items
          if (this.formData.item_ids.length > 0) {
            payload.item_ids = this.formData.item_ids;
          }

          const response = await axios.post('/api/planning/generate-material-plans', payload);
          this.generationResults = response.data.data;
        } catch (error) {
          console.error('Error generating material plans:', error);
          this.error = error.response?.data?.message || 'Failed to generate material plans. Please try again.';
        } finally {
          this.isSubmitting = false;
        }
      },
      resetForm() {
        this.formData = {
          start_period: this.getCurrentMonth(),
          buffer_percentage: 20,
          item_ids: [],
          explode_bom: true
        };
        this.selectedItems = [];
        this.itemSearch = '';
        this.searchResults = [];
        this.generationResults = null;
        this.error = null;
      },
      generateNewPlans() {
        this.generationResults = null;
      }
    }
  };
  </script>

  <style scoped>
  .material-plans-generation {
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
  }

  .form-actions {
    margin-top: 2rem;
  }

  .selected-items-container {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 10px;
  }

  .selected-item {
    display: flex;
    align-items: center;
    background-color: var(--primary-color);
    color: white;
    padding: 4px 12px;
    border-radius: 16px;
    font-size: 0.9rem;
  }

  .btn-remove {
    background: none;
    border: none;
    color: white;
    margin-left: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .search-results {
    position: absolute;
    width: 100%;
    max-height: 200px;
    overflow-y: auto;
    background-color: white;
    border: 1px solid var(--gray-300);
    border-radius: 4px;
    z-index: 100;
    margin-top: 5px;
  }

  .search-item {
    padding: 8px 12px;
    cursor: pointer;
    border-bottom: 1px solid var(--gray-200);
  }

  .search-item:hover {
    background-color: var(--gray-100);
  }

  .summary-stats {
    display: flex;
    gap: 30px;
    margin-bottom: 20px;
  }

  .stat-item {
    text-align: center;
    flex: 1;
    padding: 15px;
    background-color: var(--gray-100);
    border-radius: 8px;
  }

  .stat-value {
    font-size: 2rem;
    font-weight: 600;
    color: var(--primary-color);
  }

  .stat-label {
    color: var(--gray-700);
    font-size: 0.9rem;
  }
  </style>
