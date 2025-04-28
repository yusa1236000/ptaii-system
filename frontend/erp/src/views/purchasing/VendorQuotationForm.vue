<!-- frontend/erp/src/views/purchasing/VendorQuotationForm.vue -->
<template>
    <div class="quotation-form-container">
      <div class="header-actions">
        <h2 class="section-title">{{ isEditing ? 'Edit Vendor Quotation' : 'Create Vendor Quotation' }}</h2>
        <div class="action-buttons">
          <router-link :to="`/purchasing/quotations`" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to List
          </router-link>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
          </div>

          <form v-else @submit.prevent="saveQuotation" class="quotation-form">
            <div class="row mb-4">
              <div class="col-md-6">
                <div class="form-group mb-3">
                  <label for="rfq_id" class="form-label">Request for Quotation <span class="text-danger">*</span></label>
                  <select
                    id="rfq_id"
                    v-model="form.rfq_id"
                    class="form-select"
                    :class="{ 'is-invalid': errors.rfq_id }"
                    :disabled="isEditing"
                    @change="loadRfqDetails"
                    required
                  >
                    <option value="" disabled>Select RFQ</option>
                    <option v-for="rfq in rfqOptions" :key="rfq.rfq_id" :value="rfq.rfq_id">
                      {{ rfq.rfq_number }}
                    </option>
                  </select>
                  <div v-if="errors.rfq_id" class="invalid-feedback">{{ errors.rfq_id }}</div>
                </div>

                <div class="form-group mb-3">
                  <label for="vendor_id" class="form-label">Vendor <span class="text-danger">*</span></label>
                  <select
                    id="vendor_id"
                    v-model="form.vendor_id"
                    class="form-select"
                    :class="{ 'is-invalid': errors.vendor_id }"
                    :disabled="isEditing"
                    required
                  >
                    <option value="" disabled>Select Vendor</option>
                    <option v-for="vendor in vendorOptions" :key="vendor.vendor_id" :value="vendor.vendor_id">
                      {{ vendor.name }}
                    </option>
                  </select>
                  <div v-if="errors.vendor_id" class="invalid-feedback">{{ errors.vendor_id }}</div>
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-group mb-3">
                  <label for="quotation_date" class="form-label">Quotation Date <span class="text-danger">*</span></label>
                  <input
                    type="date"
                    id="quotation_date"
                    v-model="form.quotation_date"
                    class="form-control"
                    :class="{ 'is-invalid': errors.quotation_date }"
                    required
                  />
                  <div v-if="errors.quotation_date" class="invalid-feedback">{{ errors.quotation_date }}</div>
                </div>

                <div class="form-group mb-3">
                  <label for="validity_date" class="form-label">Validity Date <span class="text-danger">*</span></label>
                  <input
                    type="date"
                    id="validity_date"
                    v-model="form.validity_date"
                    class="form-control"
                    :class="{ 'is-invalid': errors.validity_date }"
                    required
                  />
                  <div v-if="errors.validity_date" class="invalid-feedback">{{ errors.validity_date }}</div>
                </div>
              </div>
            </div>

            <div class="items-section mb-4">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="section-subtitle">Quotation Lines</h3>
              </div>

              <div class="table-responsive">
                <table class="table table-striped">
                  <thead>
                    <tr>
                      <th>Item</th>
                      <th>Quantity</th>
                      <th>Unit</th>
                      <th>Unit Price</th>
                      <th>Delivery Date</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(line, index) in form.lines" :key="index">
                      <td>
                        <select
                          v-model="line.item_id"
                          class="form-select"
                          :class="{ 'is-invalid': lineErrors[index]?.item_id }"
                          required
                        >
                          <option value="" disabled>Select Item</option>
                          <option v-for="item in itemOptions" :key="item.item_id" :value="item.item_id">
                            {{ item.name }}
                          </option>
                        </select>
                        <div v-if="lineErrors[index]?.item_id" class="invalid-feedback">{{ lineErrors[index].item_id }}</div>
                      </td>
                      <td>
                        <input
                          type="number"
                          v-model.number="line.quantity"
                          class="form-control"
                          :class="{ 'is-invalid': lineErrors[index]?.quantity }"
                          min="0.01"
                          step="0.01"
                          required
                        />
                        <div v-if="lineErrors[index]?.quantity" class="invalid-feedback">{{ lineErrors[index].quantity }}</div>
                      </td>
                      <td>
                        <select
                          v-model="line.uom_id"
                          class="form-select"
                          :class="{ 'is-invalid': lineErrors[index]?.uom_id }"
                          required
                        >
                          <option value="" disabled>Select UOM</option>
                          <option v-for="uom in uomOptions" :key="uom.uom_id" :value="uom.uom_id">
                            {{ uom.name }}
                          </option>
                        </select>
                        <div v-if="lineErrors[index]?.uom_id" class="invalid-feedback">{{ lineErrors[index].uom_id }}</div>
                      </td>
                      <td>
                        <input
                          type="number"
                          v-model.number="line.unit_price"
                          class="form-control"
                          :class="{ 'is-invalid': lineErrors[index]?.unit_price }"
                          min="0.01"
                          step="0.01"
                          required
                        />
                        <div v-if="lineErrors[index]?.unit_price" class="invalid-feedback">{{ lineErrors[index].unit_price }}</div>
                      </td>
                      <td>
                        <input
                          type="date"
                          v-model="line.delivery_date"
                          class="form-control"
                          :class="{ 'is-invalid': lineErrors[index]?.delivery_date }"
                        />
                        <div v-if="lineErrors[index]?.delivery_date" class="invalid-feedback">{{ lineErrors[index].delivery_date }}</div>
                      </td>
                      <td>
                        <button type="button" @click="removeLine(index)" class="btn btn-sm btn-outline-danger">
                          <i class="fas fa-trash"></i>
                        </button>
                      </td>
                    </tr>
                  </tbody>
                  <tfoot>
                    <tr>
                      <td colspan="6">
                        <button type="button" @click="addLine" class="btn btn-sm btn-outline-primary">
                          <i class="fas fa-plus"></i> Add Line
                        </button>
                      </td>
                    </tr>
                  </tfoot>
                </table>
              </div>
            </div>

            <div class="form-actions">
              <button type="button" @click="$router.push('/purchasing/quotations')" class="btn btn-outline-secondary me-2">
                Cancel
              </button>
              <button type="submit" class="btn btn-primary" :disabled="saving">
                <span v-if="saving">
                  <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                  Saving...
                </span>
                <span v-else>{{ isEditing ? 'Update Quotation' : 'Create Quotation' }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </template>

  <script>
  import { ref, computed, onMounted, reactive } from 'vue';
  import { useRouter, useRoute } from 'vue-router';
  import axios from 'axios';

  export default {
    name: 'VendorQuotationForm',

    setup() {
      const router = useRouter();
      const route = useRoute();
      const quotationId = computed(() => route.params.id);
      const isEditing = computed(() => !!quotationId.value);

      const loading = ref(true);
      const saving = ref(false);
      const errors = ref({});
      const lineErrors = ref([]);

      // Form data
      const form = reactive({
        rfq_id: '',
        vendor_id: '',
        quotation_date: new Date().toISOString().substr(0, 10), // Default to today
        validity_date: '',
        lines: []
      });

      // Options for select inputs
      const rfqOptions = ref([]);
      const vendorOptions = ref([]);
      const itemOptions = ref([]);
      const uomOptions = ref([]);

      // Fetch necessary data for the form
      const fetchFormData = async () => {
        loading.value = true;

        try {
          // Fetch RFQs in sent status
          const rfqResponse = await axios.get('/api/request-for-quotations', {
            params: { status: 'sent' }
          });
          if (rfqResponse.data.status === 'success') {
            rfqOptions.value = rfqResponse.data.data.data || [];
          }

          // Fetch vendors
          const vendorResponse = await axios.get('/api/vendors');
          if (vendorResponse.data.status === 'success') {
            vendorOptions.value = vendorResponse.data.data.data || [];
          }

          // Fetch items
          const itemResponse = await axios.get('/api/items');
          if (itemResponse.data.status === 'success') {
            itemOptions.value = itemResponse.data.data || [];
          }

          // Fetch units of measure
          const uomResponse = await axios.get('/api/uoms');
          if (uomResponse.data.status === 'success') {
            uomOptions.value = uomResponse.data.data || [];
          }

          // If editing, fetch quotation details
          if (isEditing.value) {
            const quotationResponse = await axios.get(`/api/vendor-quotations/${quotationId.value}`);
            if (quotationResponse.data.status === 'success') {
              const quotation = quotationResponse.data.data;

              // Populate form with existing data
              form.rfq_id = quotation.rfq_id;
              form.vendor_id = quotation.vendor_id;
              form.quotation_date = new Date(quotation.quotation_date).toISOString().substr(0, 10);
              form.validity_date = new Date(quotation.validity_date).toISOString().substr(0, 10);

              // Format lines
              form.lines = quotation.lines.map(line => ({
                item_id: line.item_id,
                quantity: line.quantity,
                uom_id: line.uom_id,
                unit_price: line.unit_price,
                delivery_date: line.delivery_date ? new Date(line.delivery_date).toISOString().substr(0, 10) : null
              }));
            }
          }
        } catch (error) {
          console.error('Error fetching form data:', error);
        } finally {
          loading.value = false;
        }
      };

      // Load RFQ details when RFQ is selected
      const loadRfqDetails = async () => {
        if (!form.rfq_id) return;

        try {
          const response = await axios.get(`/api/request-for-quotations/${form.rfq_id}`);
          if (response.data.status === 'success') {
            const rfq = response.data.data;

            // Set default validity date (e.g., RFQ validity date)
            if (rfq.validity_date) {
              form.validity_date = new Date(rfq.validity_date).toISOString().substr(0, 10);
            }

            // Prepopulate lines based on RFQ lines
            form.lines = rfq.lines.map(line => ({
              item_id: line.item_id,
              quantity: line.quantity,
              uom_id: line.uom_id,
              unit_price: 0, // Default to 0, vendor will provide price
              delivery_date: null
            }));
          }
        } catch (error) {
          console.error('Error loading RFQ details:', error);
        }
      };

      // Add a new line
      const addLine = () => {
        form.lines.push({
          item_id: '',
          quantity: 1,
          uom_id: '',
          unit_price: 0,
          delivery_date: null
        });
      };

      // Remove a line
      const removeLine = (index) => {
        form.lines.splice(index, 1);
      };

      // Save the quotation
      const saveQuotation = async () => {
        saving.value = true;
        errors.value = {};
        lineErrors.value = [];

        try {
          let response;

          if (isEditing.value) {
            // Update existing quotation
            response = await axios.put(`/api/vendor-quotations/${quotationId.value}`, form);
          } else {
            // Create new quotation
            response = await axios.post('/api/vendor-quotations', form);
          }

          if (response.data.status === 'success') {
            // Show success message or toast
            router.push(`/purchasing/quotations/${response.data.data.quotation_id}`);
          } else {
            // Handle non-success response (unlikely with your API)
            console.error('Failed to save quotation:', response.data.message);
          }
        } catch (error) {
          console.error('Error saving quotation:', error);

          // Handle validation errors
          if (error.response && error.response.status === 422) {
            const validationErrors = error.response.data.errors;

            // Handle general form errors
            for (const field in validationErrors) {
              if (field.startsWith('lines.')) {
                // Handle line item errors
                const [index, lineField] = field.split('.');
                if (!lineErrors.value[index]) {
                  lineErrors.value[index] = {};
                }
                lineErrors.value[index][lineField] = validationErrors[field][0];
              } else {
                // Handle top-level form errors
                errors.value[field] = validationErrors[field][0];
              }
            }
          }
        } finally {
          saving.value = false;
        }
      };

      onMounted(() => {
        fetchFormData();

        // Add at least one line if creating a new quotation
        if (!isEditing.value && form.lines.length === 0) {
          addLine();
        }
      });

      return {
        isEditing,
        loading,
        saving,
        form,
        errors,
        lineErrors,
        rfqOptions,
        vendorOptions,
        itemOptions,
        uomOptions,
        loadRfqDetails,
        addLine,
        removeLine,
        saveQuotation
      };
    }
  };
  </script>

  <style scoped>
  .quotation-form-container {
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

  .section-subtitle {
    font-size: 1.25rem;
    margin: 0;
  }

  .card {
    border-radius: 0.5rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    margin-bottom: 1.5rem;
  }

  .form-actions {
    display: flex;
    justify-content: flex-end;
    padding-top: 1.5rem;
    border-top: 1px solid #e2e8f0;
  }

  .table th, .table td {
    vertical-align: middle;
    padding: 0.75rem;
  }

  .table select, .table input {
    padding: 0.375rem 0.75rem;
  }

  /* Make form controls smaller in the table */
  .table .form-control, .table .form-select {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
  }

  .table .btn-sm {
    padding: 0.25rem 0.5rem;
  }
  </style>
