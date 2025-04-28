<!-- src/views/purchasing/VendorQuotationForm.vue -->
<template>
    <div class="quotation-form">
      <div class="card">
        <div class="card-header">
          <div class="d-flex justify-content-between align-items-center">
            <h2>{{ isEditMode ? 'Edit Vendor Quotation' : 'Create Vendor Quotation' }}</h2>
            <button class="btn btn-secondary" @click="goBack">
              <i class="fas fa-arrow-left"></i> Back
            </button>
          </div>
        </div>
        <div class="card-body">
          <!-- Loading State -->
          <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="sr-only">Loading...</span>
            </div>
            <p class="mt-2">{{ isEditMode ? 'Loading quotation data...' : 'Preparing form...' }}</p>
          </div>

          <!-- Error State -->
          <div v-else-if="error" class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-triangle mr-2"></i> {{ error }}
            <div class="mt-2">
              <button class="btn btn-outline-danger" @click="goBack">Go Back</button>
            </div>
          </div>

          <!-- Form Content -->
          <form v-else @submit.prevent="submitForm">
            <!-- Form Header Section -->
            <div class="form-section mb-4">
              <h4 class="section-title">Quotation Information</h4>
              <div class="row">
                <!-- Left Column -->
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="rfq_id">Request for Quotation <span class="text-danger">*</span></label>
                    <select
                      id="rfq_id"
                      class="form-control"
                      v-model="formData.rfq_id"
                      :disabled="isEditMode"
                      required
                      @change="loadRfqDetails"
                    >
                      <option value="" disabled>Select an RFQ</option>
                      <option
                        v-for="rfq in rfqOptions"
                        :key="rfq.rfq_id"
                        :value="rfq.rfq_id"
                      >
                        {{ rfq.rfq_number }} ({{ formatDate(rfq.rfq_date) }})
                      </option>
                    </select>
                    <div v-if="validationErrors.rfq_id" class="text-danger">
                      {{ validationErrors.rfq_id[0] }}
                    </div>
                    <small class="form-text text-muted">Select the Request for Quotation to respond to</small>
                  </div>

                  <div class="form-group">
                    <label for="vendor_id">Vendor <span class="text-danger">*</span></label>
                    <select
                      id="vendor_id"
                      class="form-control"
                      v-model="formData.vendor_id"
                      :disabled="isEditMode"
                      required
                    >
                      <option value="" disabled>Select a vendor</option>
                      <option
                        v-for="vendor in vendorOptions"
                        :key="vendor.vendor_id"
                        :value="vendor.vendor_id"
                      >
                        {{ vendor.name }}
                      </option>
                    </select>
                    <div v-if="validationErrors.vendor_id" class="text-danger">
                      {{ validationErrors.vendor_id[0] }}
                    </div>
                    <small class="form-text text-muted">Select the vendor providing this quotation</small>
                  </div>
                </div>

                <!-- Right Column -->
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="quotation_date">Quotation Date <span class="text-danger">*</span></label>
                    <input
                      type="date"
                      id="quotation_date"
                      class="form-control"
                      v-model="formData.quotation_date"
                      required
                    >
                    <div v-if="validationErrors.quotation_date" class="text-danger">
                      {{ validationErrors.quotation_date[0] }}
                    </div>
                    <small class="form-text text-muted">Date when this quotation was issued</small>
                  </div>

                  <div class="form-group">
                    <label for="validity_date">Valid Until <span class="text-danger">*</span></label>
                    <input
                      type="date"
                      id="validity_date"
                      class="form-control"
                      v-model="formData.validity_date"
                      required
                      :min="formData.quotation_date"
                    >
                    <div v-if="validationErrors.validity_date" class="text-danger">
                      {{ validationErrors.validity_date[0] }}
                    </div>
                    <small class="form-text text-muted">Date until which this quotation is valid</small>
                  </div>
                </div>
              </div>
            </div>

            <!-- RFQ Items Section -->
            <div class="form-section mt-4">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="section-title mb-0">Quotation Items</h4>
                <div v-if="rfqItems.length > 0" class="text-muted">
                  <i class="fas fa-info-circle"></i> Please provide pricing for all items
                </div>
              </div>

              <div v-if="!rfqItems.length" class="alert alert-info">
                <p class="mb-0">
                  <i class="fas fa-info-circle mr-2"></i>
                  Select an RFQ to load the items.
                </p>
              </div>

              <div v-else-if="!formData.lines.length" class="alert alert-warning">
                <p class="mb-0">
                  <i class="fas fa-exclamation-circle mr-2"></i>
                  No items found in the selected RFQ.
                </p>
              </div>

              <div v-else class="table-responsive">
                <table class="table table-bordered">
                  <thead class="bg-light">
                    <tr>
                      <th style="width: 5%">#</th>
                      <th style="width: 35%">Item</th>
                      <th style="width: 10%">Quantity</th>
                      <th style="width: 10%">UOM</th>
                      <th style="width: 20%">Unit Price <span class="text-danger">*</span></th>
                      <th style="width: 20%">Delivery Date</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(item, index) in formData.lines" :key="index">
                      <td class="text-center">{{ index + 1 }}</td>
                      <td>
                        {{ getItemName(item.item_id) }}
                        <input type="hidden" v-model="item.item_id">
                      </td>
                      <td>
                        <input
                          type="number"
                          class="form-control"
                          v-model="item.quantity"
                          min="0.01"
                          step="0.01"
                          readonly
                        >
                      </td>
                      <td>
                        {{ getUomName(item.uom_id) }}
                        <input type="hidden" v-model="item.uom_id">
                      </td>
                      <td>
                        <div class="input-group">
                          <div class="input-group-prepend">
                            <span class="input-group-text">$</span>
                          </div>
                          <input
                            type="number"
                            class="form-control"
                            v-model="item.unit_price"
                            min="0.01"
                            step="0.01"
                            required
                            @change="calculateItemTotal(index)"
                          >
                        </div>
                        <div v-if="validationErrors[`lines.${index}.unit_price`]" class="text-danger">
                          {{ validationErrors[`lines.${index}.unit_price`][0] }}
                        </div>
                        <div class="text-right mt-1">
                          <small class="text-muted">Total: {{ formatCurrency(calculateLineTotal(item)) }}</small>
                        </div>
                      </td>
                      <td>
                        <input
                          type="date"
                          class="form-control"
                          v-model="item.delivery_date"
                          :min="formData.quotation_date"
                        >
                        <div v-if="validationErrors[`lines.${index}.delivery_date`]" class="text-danger">
                          {{ validationErrors[`lines.${index}.delivery_date`][0] }}
                        </div>
                      </td>
                    </tr>
                  </tbody>
                  <tfoot>
                    <tr>
                      <td colspan="4" class="text-right font-weight-bold">Total Amount:</td>
                      <td colspan="2" class="font-weight-bold">{{ formatCurrency(calculateTotal()) }}</td>
                    </tr>
                  </tfoot>
                </table>
              </div>
            </div>

            <!-- Notes Section -->
            <div class="form-section mt-4">
              <h4 class="section-title">Additional Notes</h4>
              <div class="form-group">
                <textarea
                  class="form-control"
                  v-model="formData.notes"
                  rows="3"
                  placeholder="Additional notes or terms and conditions..."
                ></textarea>
                <div v-if="validationErrors.notes" class="text-danger">
                  {{ validationErrors.notes[0] }}
                </div>
              </div>
            </div>

            <!-- Form Buttons -->
            <div class="mt-4 d-flex justify-content-between">
              <button type="button" class="btn btn-secondary" @click="goBack">
                <i class="fas fa-times"></i> Cancel
              </button>
              <button type="submit" class="btn btn-primary" :disabled="submitting">
                <i class="fas" :class="submitting ? 'fa-spinner fa-spin' : 'fa-save'"></i>
                {{ submitting ? 'Saving...' : 'Save Quotation' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </template>

  <script>
  import { ref, reactive, computed, onMounted, watch } from 'vue';
  import { useRoute, useRouter } from 'vue-router';
  import axios from 'axios';

  export default {
    name: 'VendorQuotationForm',

    setup() {
      const route = useRoute();
      const router = useRouter();
      const isEditMode = computed(() => !!route.params.id);

      // State variables
      const loading = ref(true);
      const submitting = ref(false);
      const error = ref(null);
      const vendorOptions = ref([]);
      const rfqOptions = ref([]);
      const rfqItems = ref([]);
      const items = ref([]);
      const uoms = ref([]);
      const validationErrors = ref({});

      // Form data
      const formData = reactive({
        rfq_id: '',
        vendor_id: '',
        quotation_date: new Date().toISOString().split('T')[0], // Today
        validity_date: '', // Will be set to default below
        notes: '',
        lines: []
      });

      // Set validity date to 30 days from today by default
      const defaultValidityDate = () => {
        const date = new Date();
        date.setDate(date.getDate() + 30);
        return date.toISOString().split('T')[0];
      };
      formData.validity_date = defaultValidityDate();

      // Watch for date changes to ensure validity date is always after quotation date
      watch(() => formData.quotation_date, (newDate) => {
        const quotationDate = new Date(newDate);
        const validityDate = new Date(formData.validity_date);

        if (validityDate <= quotationDate) {
          // Set validity to 30 days after new quotation date
          quotationDate.setDate(quotationDate.getDate() + 30);
          formData.validity_date = quotationDate.toISOString().split('T')[0];
        }
      });

      // Load vendors and RFQs on mount
      onMounted(async () => {
        try {
          loading.value = true;

          // Load master data in parallel
          const [vendorsResponse, rfqsResponse, itemsResponse, uomsResponse] = await Promise.all([
            axios.get('/api/vendors'),
            axios.get('/api/request-for-quotations?status=sent'),
            axios.get('/api/items'),
            axios.get('/api/uoms')
          ]);

          vendorOptions.value = vendorsResponse.data.status === 'success' ? vendorsResponse.data.data.data : [];
          rfqOptions.value = rfqsResponse.data.status === 'success' ? rfqsResponse.data.data.data : [];
          items.value = itemsResponse.data.status === 'success' ? itemsResponse.data.data : [];
          uoms.value = uomsResponse.data.status === 'success' ? uomsResponse.data.data : [];

          // If in edit mode, load the quotation data
          if (isEditMode.value) {
            await loadQuotation(route.params.id);
          }
        } catch (err) {
          console.error('Error initializing form:', err);
          error.value = 'Failed to load form data. Please try again.';
        } finally {
          loading.value = false;
        }
      });

      // Load quotation data for edit mode
      const loadQuotation = async (quotationId) => {
        try {
          const response = await axios.get(`/api/vendor-quotations/${quotationId}`);

          if (response.data.status === 'success') {
            const quotation = response.data.data;

            // Check if quotation can be edited (only received status)
            if (quotation.status !== 'received') {
              error.value = `This quotation cannot be edited because it has a status of "${quotation.status}". Only quotations with "received" status can be edited.`;
              return;
            }

            // Populate form data
            formData.rfq_id = quotation.rfq_id;
            formData.vendor_id = quotation.vendor_id;
            formData.quotation_date = quotation.quotation_date;
            formData.validity_date = quotation.validity_date;
            formData.notes = quotation.notes || '';

            // Load RFQ details to get the items
            await loadRfqDetails();

            // Map quotation lines to form data
            if (quotation.lines && quotation.lines.length > 0) {
              formData.lines = quotation.lines.map(line => ({
                item_id: line.item_id,
                quantity: line.quantity,
                uom_id: line.uom_id,
                unit_price: line.unit_price,
                delivery_date: line.delivery_date || null,
                total: line.unit_price * line.quantity
              }));
            }
          } else {
            throw new Error('Failed to load quotation data');
          }
        } catch (err) {
          console.error('Error loading quotation:', err);
          error.value = 'Failed to load quotation data. Please try again.';
        }
      };

      // Load RFQ details when RFQ is selected
      const loadRfqDetails = async () => {
        if (!formData.rfq_id) {
          rfqItems.value = [];
          formData.lines = [];
          return;
        }

        try {
          const response = await axios.get(`/api/request-for-quotations/${formData.rfq_id}`);

          if (response.data.status === 'success') {
            const rfq = response.data.data;

            // Verify RFQ is in sent status
            if (rfq.status !== 'sent') {
              error.value = `This RFQ has a status of "${rfq.status}". You can only create quotations for RFQs with "sent" status.`;
              formData.rfq_id = '';
              rfqItems.value = [];
              formData.lines = [];
              return;
            }

            rfqItems.value = rfq.lines || [];

            // If not in edit mode or no lines exist, initialize lines from RFQ items
            if (!isEditMode.value || formData.lines.length === 0) {
              formData.lines = rfqItems.value.map(line => ({
                item_id: line.item_id,
                quantity: line.quantity,
                uom_id: line.uom_id,
                unit_price: 0, // Default to 0
                delivery_date: line.required_date || null,
                total: 0
              }));
            }
          } else {
            throw new Error('Failed to load RFQ details');
          }
        } catch (err) {
          console.error('Error loading RFQ details:', err);
          error.value = 'Failed to load RFQ details. Please try again.';
        }
      };

      // Helper function to calculate line total
      const calculateLineTotal = (line) => {
        return line.quantity * line.unit_price;
      };

      // Calculate item total and update it in the form data
      const calculateItemTotal = (index) => {
        const line = formData.lines[index];
        line.total = calculateLineTotal(line);
      };

      // Calculate total amount for all items
      const calculateTotal = () => {
        return formData.lines.reduce((total, line) => {
          return total + (line.quantity * line.unit_price);
        }, 0);
      };

      // Helper functions to get names
      const getItemName = (itemId) => {
        const item = items.value.find(i => i.item_id === itemId);
        return item ? `${item.item_code} - ${item.name}` : 'Unknown Item';
      };

      const getUomName = (uomId) => {
        const uom = uoms.value.find(u => u.uom_id === uomId);
        return uom ? uom.name : 'Unknown UOM';
      };

      // Format currency values
      const formatCurrency = (amount) => {
        if (amount === null || amount === undefined || isNaN(amount)) return '$0.00';
        return `$${parseFloat(amount).toFixed(2)}`;
      };

      // Format dates
      const formatDate = (dateString) => {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
          year: 'numeric',
          month: 'short',
          day: 'numeric'
        });
      };

      // Form validation
      const validateForm = () => {
        const errors = {};

        // Basic validation
        if (!formData.rfq_id) errors.rfq_id = ['Please select an RFQ'];
        if (!formData.vendor_id) errors.vendor_id = ['Please select a vendor'];
        if (!formData.quotation_date) errors.quotation_date = ['Quotation date is required'];
        if (!formData.validity_date) errors.validity_date = ['Validity date is required'];

        // Validate line items
        formData.lines.forEach((line, index) => {
          if (!line.unit_price || line.unit_price <= 0) {
            errors[`lines.${index}.unit_price`] = ['Unit price must be greater than zero'];
          }

          if (line.delivery_date && new Date(line.delivery_date) < new Date(formData.quotation_date)) {
            errors[`lines.${index}.delivery_date`] = ['Delivery date cannot be before quotation date'];
          }
        });

        return errors;
      };

      // Form submission
      const submitForm = async () => {
        // Client-side validation
        const errors = validateForm();
        if (Object.keys(errors).length > 0) {
          validationErrors.value = errors;
          window.scrollTo(0, 0); // Scroll to top to show errors
          return;
        }

        validationErrors.value = {};
        submitting.value = true;

        try {
          // Prepare data for submission (excluding calculated fields)
          const submitData = {
            rfq_id: formData.rfq_id,
            vendor_id: formData.vendor_id,
            quotation_date: formData.quotation_date,
            validity_date: formData.validity_date,
            notes: formData.notes,
            lines: formData.lines.map(line => ({
              item_id: line.item_id,
              quantity: line.quantity,
              uom_id: line.uom_id,
              unit_price: line.unit_price,
              delivery_date: line.delivery_date
            }))
          };

          let response;

          if (isEditMode.value) {
            response = await axios.put(`/api/vendor-quotations/${route.params.id}`, submitData);
          } else {
            response = await axios.post('/api/vendor-quotations', submitData);
          }

          if (response.data.status === 'success') {
            // Show success message
            alert(`Quotation ${isEditMode.value ? 'updated' : 'created'} successfully!`);

            // Redirect to quotation list or detail page
            if (isEditMode.value) {
              router.push(`/purchasing/quotations/${route.params.id}`);
            } else {
              router.push('/purchasing/quotations');
            }
          } else {
            throw new Error(`Failed to ${isEditMode.value ? 'update' : 'create'} quotation`);
          }
        } catch (err) {
          console.error(`Error ${isEditMode.value ? 'updating' : 'creating'} quotation:`, err);

          if (err.response && err.response.status === 422) {
            // Server validation errors
            validationErrors.value = err.response.data.errors || {};
            error.value = 'Please correct the errors in the form.';
            window.scrollTo(0, 0); // Scroll to top to show errors
          } else if (err.response && err.response.data && err.response.data.message) {
            // Server returned an error message
            error.value = err.response.data.message;
          } else {
            // Generic error
            error.value = `Failed to ${isEditMode.value ? 'update' : 'create'} quotation. Please try again.`;
          }
        } finally {
          submitting.value = false;
        }
      };

      const goBack = () => {
        if (isEditMode.value) {
          router.push(`/purchasing/quotations/${route.params.id}`);
        } else {
          router.push('/purchasing/quotations');
        }
      };

      return {
        isEditMode,
        loading,
        submitting,
        error,
        formData,
        vendorOptions,
        rfqOptions,
        rfqItems,
        validationErrors,
        loadRfqDetails,
        getItemName,
        getUomName,
        calculateLineTotal,
        calculateItemTotal,
        calculateTotal,
        formatCurrency,
        formatDate,
        submitForm,
        goBack
      };
    }
  };
  </script>

  <style scoped>
  .quotation-form {
    max-width: 1200px;
    margin: 0 auto;
  }

  .card {
    border-radius: 8px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    margin-bottom: 2rem;
  }

  .card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    padding: 1rem 1.5rem;
  }

  .card-body {
    padding: 1.5rem;
  }

  .form-section {
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e9ecef;
  }

  .form-section:last-child {
    border-bottom: none;
  }

  .section-title {
    color: #495057;
    font-weight: 600;
    margin-bottom: 1.25rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #e9ecef;
  }

  .form-group {
    margin-bottom: 1.5rem;
  }

  label {
    font-weight: 500;
    margin-bottom: 0.5rem;
    color: #495057;
    display: block;
  }

  .form-control {
    border-radius: 4px;
    border: 1px solid #ced4da;
    padding: 0.5rem 0.75rem;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
  }

  .form-control:focus {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
  }

  .input-group-text {
    background-color: #e9ecef;
    border: 1px solid #ced4da;
    border-radius: 4px 0 0 4px;
  }

  .form-text {
    display: block;
    margin-top: 0.25rem;
    font-size: 0.875rem;
  }

  .table {
    width: 100%;
    margin-bottom: 1rem;
    color: #212529;
    border-collapse: collapse;
  }

  .table th,
  .table td {
    padding: 0.75rem;
    vertical-align: middle;
    border: 1px solid #dee2e6;
  }

  .table thead th {
    background-color: #f8f9fa;
    color: #495057;
    font-weight: 600;
    border-bottom: 2px solid #dee2e6;
    vertical-align: bottom;
  }

  .table tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.03);
  }

  .table tfoot td {
    background-color: #f8f9fa;
    font-weight: 500;
  }

  .text-danger {
    color: #dc3545 !important;
    font-size: 0.875rem;
    margin-top: 0.25rem;
  }

  .btn {
    display: inline-block;
    font-weight: 400;
    text-align: center;
    vertical-align: middle;
    user-select: none;
    border: 1px solid transparent;
    padding: 0.5rem 1rem;
    font-size: 1rem;
    line-height: 1.5;
    border-radius: 0.25rem;
    transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
  }

  .btn:focus,
  .btn:hover {
    text-decoration: none;
  }

  .btn-primary {
    color: #fff;
    background-color: #2563eb;
    border-color: #2563eb;
  }

  .btn-primary:hover {
    background-color: #1d4ed8;
    border-color: #1d4ed8;
  }

  .btn-primary:focus {
    box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.5);
  }

  .btn-primary:disabled {
    background-color: #6c9cff;
    border-color: #6c9cff;
  }

  .btn-secondary {
    color: #fff;
    background-color: #6c757d;
    border-color: #6c757d;
  }

  .btn-secondary:hover {
    background-color: #5a6268;
    border-color: #545b62;
  }

  .btn-secondary:focus {
    box-shadow: 0 0 0 0.2rem rgba(108, 117, 125, 0.5);
  }

  .alert {
    position: relative;
    padding: 1rem;
    margin-bottom: 1rem;
    border: 1px solid transparent;
    border-radius: 0.25rem;
  }

  .alert-danger {
    color: #721c24;
    background-color: #f8d7da;
    border-color: #f5c6cb;
  }

  .alert-info {
    color: #0c5460;
    background-color: #d1ecf1;
    border-color: #bee5eb;
  }

  .alert-warning {
    color: #856404;
    background-color: #fff3cd;
    border-color: #ffeeba;
  }

  .spinner-border {
    display: inline-block;
    width: 2rem;
    height: 2rem;
    vertical-align: text-bottom;
    border: 0.25em solid currentColor;
    border-right-color: transparent;
    border-radius: 50%;
    animation: spinner-border 0.75s linear infinite;
  }

  @keyframes spinner-border {
    to {
      transform: rotate(360deg);
    }
  }

  /* Responsive adjustments */
  @media (max-width: 767.98px) {
    .card-body {
      padding: 1rem;
    }

    .row {
      margin-right: -0.5rem;
      margin-left: -0.5rem;
    }

    .col-md-6 {
      padding-right: 0.5rem;
      padding-left: 0.5rem;
    }

    .table thead {
      display: none;
    }

    .table, .table tbody, .table tr, .table td {
      display: block;
      width: 100%;
    }

    .table tr {
      margin-bottom: 1rem;
      border: 1px solid #dee2e6;
    }

    .table td {
      position: relative;
      padding-left: 50%;
      text-align: right;
      border-top: none;
      border-left: none;
      border-right: none;
    }

    .table td:before {
      content: attr(data-label);
      position: absolute;
      left: 0;
      width: 45%;
      padding-left: 0.75rem;
      font-weight: 600;
      text-align: left;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .btn {
      display: block;
      width: 100%;
      margin-bottom: 0.5rem;
    }

    .d-flex {
      flex-direction: column;
    }
  }

  /* Print styles */
  @media print {
    .quotation-form {
      max-width: 100%;
      margin: 0;
    }

    .card {
      box-shadow: none;
      border: 1px solid #dee2e6;
    }

    .btn, .form-control, select, button {
      display: none;
    }

    .table {
      width: 100%;
      border-collapse: collapse;
    }

    .table th, .table td {
      border: 1px solid #dee2e6;
    }
  }
  </style>
