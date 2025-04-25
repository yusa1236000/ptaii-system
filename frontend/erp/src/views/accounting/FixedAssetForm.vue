<!-- src/views/accounting/FixedAssetForm.vue -->
<template>
    <div class="fixed-asset-form">
      <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
          <h2>{{ isEditing ? 'Edit Asset' : 'Add New Asset' }}</h2>
          <router-link to="/accounting/fixed-assets" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left mr-2"></i> Back to Assets
          </router-link>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <div v-if="isLoading" class="text-center py-5">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p class="mt-2">Loading asset data...</p>
          </div>

          <div v-else-if="error" class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            {{ error }}
          </div>

          <form v-else @submit.prevent="saveAsset" class="asset-form">
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="assetCode">Asset Code <span class="text-danger">*</span></label>
                  <input
                    id="assetCode"
                    v-model="asset.asset_code"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': validationErrors.asset_code }"
                    required
                    :disabled="isEditing && assetHasDepreciation"
                    placeholder="Enter asset code"
                  />
                  <div v-if="validationErrors.asset_code" class="invalid-feedback">
                    {{ validationErrors.asset_code[0] }}
                  </div>
                  <small class="form-text text-muted">
                    A unique identifier for this asset (e.g., FA-2023-001)
                  </small>
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-group">
                  <label for="name">Asset Name <span class="text-danger">*</span></label>
                  <input
                    id="name"
                    v-model="asset.name"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': validationErrors.name }"
                    required
                    placeholder="Enter asset name"
                  />
                  <div v-if="validationErrors.name" class="invalid-feedback">
                    {{ validationErrors.name[0] }}
                  </div>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="category">Category <span class="text-danger">*</span></label>
                  <select
                    id="category"
                    v-model="asset.category"
                    class="form-control"
                    :class="{ 'is-invalid': validationErrors.category }"
                    required
                  >
                    <option value="">Select a category</option>
                    <option value="Building">Building</option>
                    <option value="Computer Equipment">Computer Equipment</option>
                    <option value="Furniture">Furniture</option>
                    <option value="Land">Land</option>
                    <option value="Machinery">Machinery</option>
                    <option value="Office Equipment">Office Equipment</option>
                    <option value="Software">Software</option>
                    <option value="Vehicle">Vehicle</option>
                    <option value="Other">Other</option>
                  </select>
                  <div v-if="validationErrors.category" class="invalid-feedback">
                    {{ validationErrors.category[0] }}
                  </div>
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-group">
                  <label for="acquisitionDate">Acquisition Date <span class="text-danger">*</span></label>
                  <input
                    id="acquisitionDate"
                    v-model="asset.acquisition_date"
                    type="date"
                    class="form-control"
                    :class="{ 'is-invalid': validationErrors.acquisition_date }"
                    required
                    :disabled="isEditing && assetHasDepreciation"
                  />
                  <div v-if="validationErrors.acquisition_date" class="invalid-feedback">
                    {{ validationErrors.acquisition_date[0] }}
                  </div>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="acquisitionCost">Acquisition Cost <span class="text-danger">*</span></label>
                  <input
                    id="acquisitionCost"
                    v-model="asset.acquisition_cost"
                    type="number"
                    class="form-control"
                    :class="{ 'is-invalid': validationErrors.acquisition_cost }"
                    step="0.01"
                    min="0"
                    required
                    placeholder="0.00"
                    :disabled="isEditing && assetHasDepreciation"
                  />
                  <div v-if="validationErrors.acquisition_cost" class="invalid-feedback">
                    {{ validationErrors.acquisition_cost[0] }}
                  </div>
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-group">
                  <label for="depreciationRate">Depreciation Rate (%) <span class="text-danger">*</span></label>
                  <input
                    id="depreciationRate"
                    v-model="asset.depreciation_rate"
                    type="number"
                    class="form-control"
                    :class="{ 'is-invalid': validationErrors.depreciation_rate }"
                    step="0.01"
                    min="0"
                    max="100"
                    required
                    placeholder="0.00"
                    :disabled="isEditing && assetHasDepreciation"
                  />
                  <div v-if="validationErrors.depreciation_rate" class="invalid-feedback">
                    {{ validationErrors.depreciation_rate[0] }}
                  </div>
                  <small class="form-text text-muted">
                    Annual depreciation rate (e.g., 10% for 10 years)
                  </small>
                </div>
              </div>
            </div>

            <div class="row" v-if="isEditing">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="currentValue">Current Value</label>
                  <input
                    id="currentValue"
                    v-model="asset.current_value"
                    type="number"
                    class="form-control"
                    step="0.01"
                    min="0"
                    placeholder="0.00"
                    readonly
                  />
                  <small class="form-text text-muted">
                    Current book value after depreciation
                  </small>
                </div>
              </div>
            </div>

            <div class="form-group">
              <label for="status">Status <span class="text-danger">*</span></label>
              <select
                id="status"
                v-model="asset.status"
                class="form-control"
                :class="{ 'is-invalid': validationErrors.status }"
                required
              >
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
                <option value="Under Maintenance">Under Maintenance</option>
                <option value="Disposed">Disposed</option>
              </select>
              <div v-if="validationErrors.status" class="invalid-feedback">
                {{ validationErrors.status[0] }}
              </div>
            </div>

            <div v-if="isEditing && assetHasDepreciation" class="alert alert-warning mb-4">
              <i class="fas fa-exclamation-triangle mr-2"></i>
              <span>
                Some fields cannot be changed as depreciation has already been recorded for this asset.
              </span>
            </div>

            <div class="form-buttons mt-4">
              <button type="submit" class="btn btn-primary" :disabled="isSaving">
                <i v-if="isSaving" class="fas fa-spinner fa-spin mr-2"></i>
                <i v-else class="fas fa-save mr-2"></i>
                {{ isEditing ? 'Update Asset' : 'Save Asset' }}
              </button>
              <router-link to="/accounting/fixed-assets" class="btn btn-secondary ml-2">
                Cancel
              </router-link>
            </div>
          </form>
        </div>
      </div>
    </div>
  </template>

  <script>
  import axios from 'axios';
  import { ref, computed, onMounted } from 'vue';
  import { useRoute, useRouter } from 'vue-router';

  export default {
    name: 'FixedAssetForm',
    props: {
      assetId: {
        type: [String, Number],
        required: false
      }
    },
    setup(props) {
      const route = useRoute();
      const router = useRouter();
      const assetId = computed(() => props.assetId || route.params.id);
      const isEditing = computed(() => !!assetId.value);

      const asset = ref({
        asset_code: '',
        name: '',
        category: '',
        acquisition_date: '',
        acquisition_cost: '',
        current_value: '',
        depreciation_rate: '',
        status: 'Active'
      });

      const isLoading = ref(false);
      const isSaving = ref(false);
      const error = ref(null);
      const validationErrors = ref({});
      const assetHasDepreciation = ref(false);

      // Load asset data if editing
      const loadAsset = async () => {
        if (!isEditing.value) return;

        isLoading.value = true;
        error.value = null;

        try {
          const response = await axios.get(`/api/accounting/fixed-assets/${assetId.value}`);
          const assetData = response.data.data;

          // Set asset data
          asset.value = {
            asset_code: assetData.asset_code,
            name: assetData.name,
            category: assetData.category,
            acquisition_date: assetData.acquisition_date,
            acquisition_cost: assetData.acquisition_cost,
            current_value: assetData.current_value,
            depreciation_rate: assetData.depreciation_rate,
            status: assetData.status
          };

          // Check if asset has depreciation records
          if (assetData.asset_depreciations && assetData.asset_depreciations.length > 0) {
            assetHasDepreciation.value = true;
          }
        } catch (err) {
          console.error('Error loading asset:', err);
          error.value = 'Failed to load asset data. Please try again later.';
        } finally {
          isLoading.value = false;
        }
      };

      // Save asset
      const saveAsset = async () => {
        isSaving.value = true;
        error.value = null;
        validationErrors.value = {};

        try {
          if (isEditing.value) {
            await axios.put(`/api/accounting/fixed-assets/${assetId.value}`, asset.value);
          } else {
            await axios.post('/api/accounting/fixed-assets', asset.value);
          }

          // Redirect to assets list with success message
          router.push({
            path: '/accounting/fixed-assets',
            query: { success: isEditing.value ? 'updated' : 'created' }
          });
        } catch (err) {
          console.error('Error saving asset:', err);

          if (err.response && err.response.status === 422) {
            // Validation errors
            validationErrors.value = err.response.data.errors || {};
          } else {
            error.value = 'Failed to save asset. ' + (err.response?.data?.message || 'Please try again later.');
          }
        } finally {
          isSaving.value = false;
        }
      };

      // Lifecycle hooks
      onMounted(() => {
        // Set current date as default for new assets
        if (!isEditing.value) {
          asset.value.acquisition_date = new Date().toISOString().substr(0, 10);
        }

        loadAsset();
      });

      return {
        asset,
        isLoading,
        isSaving,
        error,
        validationErrors,
        isEditing,
        assetHasDepreciation,
        saveAsset
      };
    }
  };
  </script>

  <style scoped>
  .fixed-asset-form {
    max-width: 100%;
  }

  .form-buttons {
    border-top: 1px solid var(--gray-200);
    padding-top: 1.5rem;
  }
  </style>
