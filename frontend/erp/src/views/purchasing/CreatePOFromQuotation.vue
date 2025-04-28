<!-- src/views/purchasing/CreatePOFromQuotation.vue - Template -->
<template>
    <div class="create-po">
      <div class="card">
        <div class="card-header">
          <div class="d-flex justify-content-between align-items-center">
            <h2>Create Purchase Order from Quotation</h2>
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
            <p class="mt-2">Loading quotation data...</p>
          </div>

          <!-- Error State -->
          <div v-else-if="error" class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-triangle mr-2"></i> {{ error }}
            <div class="mt-2">
              <button class="btn btn-outline-danger" @click="goBack">Go Back</button>
            </div>
          </div>

          <!-- Create PO Form -->
          <form v-else @submit.prevent="createPO">
            <!-- Source Quotation Info Section -->
            <div class="form-section mb-4">
              <h4 class="section-title">Source Quotation Information</h4>
              <div class="info-card">
                <div class="row">
                  <div class="col-md-6">
                    <div class="info-item">
                      <span class="info-label">Quotation ID:</span>
                      <span class="info-value">{{ quotation.quotation_id }}</span>
                    </div>
                    <div class="info-item">
                      <span class="info-label">Vendor:</span>
                      <span class="info-value">
                        {{ quotation.vendor ? quotation.vendor.name : 'N/A' }}
                      </span>
                    </div>
                    <div class="info-item">
                      <span class="info-label">RFQ:</span>
                      <span class="info-value">
                        {{ quotation.requestForQuotation ? quotation.requestForQuotation.rfq_number : 'N/A' }}
                      </span>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="info-item">
                      <span class="info-label">Quotation Date:</span>
                      <span class="info-value">{{ formatDate(quotation.quotation_date) }}</span>
                    </div>
                    <div class="info-item">
                      <span class="info-label">Valid Until:</span>
                      <span class="info-value">{{ formatDate(quotation.validity_date) }}</span>
                    </div>
                    <div class="info-item">
                      <span class="info-label">Status:</span>
                      <span class="info-value">
                        <span class="badge badge-success">{{ formatStatus(quotation.status) }}</span>
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- PO Details Section -->
            <div class="form-section mb-4">
              <h4 class="section-title">Purchase Order Details</h4>
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="po_date">PO Date <span class="text-danger">*</span></label>
                    <input
                      type="date"
                      id="po_date"
                      class="form-control"
                      v-model="formData.po_date"
                      required
                    >
                    <div v-if="validationErrors.po_date" class="text-danger">
                      {{ validationErrors.po_date[0] }}
                    </div>
                    <small class="form-text text-muted">Date when this PO is issued</small>
                  </div>
                  <div class="form-group">
                    <label for="payment_terms">Payment Terms</label>
                    <select
                      id="payment_terms"
                      class="form-control"
                      v-model="formData.payment_terms"
                    >
                      <option value="">Select payment terms</option>
                      <option value="Net 30">Net 30</option>
                      <option value="Net 45">Net 45</option>
                      <option value="Net 60">Net 60</option>
                      <option value="COD">Cash on Delivery</option>
                      <option value="Advance">Advance Payment</option>
                      <option value="Letter of Credit">Letter of Credit</option>
                    </select>
                    <div v-if="validationErrors.payment_terms" class="text-danger">
                      {{ validationErrors.payment_terms[0] }}
                    </div>
                    <small class="form-text text-muted">Terms for payment of this order</small>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="delivery_terms">Delivery Terms</label>
                    <select
                      id="delivery_terms"
                      class="form-control"
                      v-model="formData.delivery_terms"
                    >
                      <option value="">Select delivery terms</option>
                      <option value="EXW">EXW (Ex Works)</option>
                      <option value="FCA">FCA (Free Carrier)</option>
                      <option value="FOB">FOB (Free on Board)</option>
                      <option value="CIF">CIF (Cost, Insurance & Freight)</option>
                      <option value="DAP">DAP (Delivered at Place)</option>
                      <option value="DDP">DDP (Delivered Duty Paid)</option>
                    </select>
                    <div v-if="validationErrors.delivery_terms" class="text-danger">
                      {{ validationErrors.delivery_terms[0] }}
                    </div>
                    <small class="form-text text-muted">Terms for delivery of this order</small>
                  </div>
                  <div class="form-group">
                    <label for="expected_delivery">Expected Delivery Date <span class="text-danger">*</span></label>
                    <input
                      type="date"
                      id="expected_delivery"
                      class="form-control"
                      v-model="formData.expected_delivery"
                      required
                      :min="formData.po_date"
                    >
                    <div v-if="validationErrors.expected_delivery" class="text-danger">
                      {{ validationErrors.expected_delivery[0] }}
                    </div>
                    <small class="form-text text-muted">Expected date for delivery of this order</small>
                  </div>
                </div>
              </div>
            </div>

            <!-- Line Items Section -->
            <div class="form-section mb-4">
              <h4 class="section-title">Line Items</h4>
              <div class="table-responsive">
                <table class="table table-bordered">
                  <thead class="bg-light">
                    <tr>
                      <th style="width: 5%">#</th>
                      <th style="width: 35%">Item</th>
                      <th style="width: 10%">Quantity</th>
                      <th style="width: 10%">UOM</th>
                      <th style="width: 15%">Unit Price</th>
                      <th style="width: 10%">Tax</th>
                      <th style="width: 15%">Total</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(line, index) in formData.lines" :key="index">
                      <td class="text-center">{{ index + 1 }}</td>
                      <td>
                        {{ line.item_name }}
                        <input type="hidden" v-model="line.item_id">
                      </td>
                      <td>
                        <input
                          type="number"
                          class="form-control"
                          v-model="line.quantity"
                          min="0.01"
                          step="0.01"
                          @change="updateLineTotals(index)"
                        >
                        <div v-if="validationErrors[`lines.${index}.quantity`]" class="text-danger">
                          {{ validationErrors[`lines.${index}.quantity`][0] }}
                        </div>
                      </td>
                      <td>
                        {{ line.uom_name }}
                        <input type="hidden" v-model="line.uom_id">
                      </td>
                      <td>
                        <div class="input-group">
                          <div class="input-group-prepend">
                            <span class="input-group-text">$</span>
                          </div>
                          <input
                            type="number"
                            class="form-control"
                            v-model="line.unit_price"
                            min="0.01"
                            step="0.01"
                            @change="updateLineTotals(index)"
                          >
                        </div>
                        <div v-if="validationErrors[`lines.${index}.unit_price`]" class="text-danger">
                          {{ validationErrors[`lines.${index}.unit_price`][0] }}
                        </div>
                      </td>
                      <td>
                        <div class="input-group">
                          <input
                            type="number"
                            class="form-control"
                            v-model="line.tax"
                            min="0"
                            step="0.01"
                            @change="updateLineTotals(index)"
                          >
                          <div class="input-group-append">
                            <span class="input-group-text">$</span>
                          </div>
                        </div>
                        <div v-if="validationErrors[`lines.${index}.tax`]" class="text-danger">
                          {{ validationErrors[`lines.${index}.tax`][0] }}
                        </div>
                      </td>
                      <td>
                        <div class="input-group">
                          <div class="input-group-prepend">
                            <span class="input-group-text">$</span>
                          </div>
                          <input
                            type="number"
                            class="form-control"
                            v-model="line.total"
                            readonly
                          >
                        </div>
                      </td>
                    </tr>
                  </tbody>
                  <tfoot>
                    <tr>
                      <td colspan="5" class="text-right font-weight-bold">Subtotal:</td>
                      <td colspan="2">{{ formatCurrency(calculateSubtotal()) }}</td>
                    </tr>
                    <tr>
                      <td colspan="5" class="text-right font-weight-bold">Tax Total:</td>
                      <td colspan="2">{{ formatCurrency(calculateTaxTotal()) }}</td>
                    </tr>
                    <tr>
                      <td colspan="5" class="text-right font-weight-bold">Grand Total:</td>
                      <td colspan="2" class="font-weight-bold">{{ formatCurrency(calculateGrandTotal()) }}</td>
                    </tr>
                  </tfoot>
                </table>
              </div>
            </div>

            <!-- Notes Section -->
            <div class="form-section mb-4">
              <h4 class="section-title">Additional Notes</h4>
              <div class="form-group">
                <textarea
                  class="form-control"
                  v-model="formData.notes"
                  rows="3"
                  placeholder="Additional notes, special instructions, or reference information..."
                ></textarea>
                <div v-if="validationErrors.notes" class="text-danger">
                  {{ validationErrors.notes[0] }}
                </div>
              </div>
            </div>

            <!-- Form Buttons -->
            <div class="form-actions d-flex justify-content-between">
              <button type="button" class="btn btn-secondary" @click="goBack">
                <i class="fas fa-times"></i> Cancel
              </button>
              <button type="submit" class="btn btn-primary" :disabled="submitting">
                <i class="fas" :class="submitting ? 'fa-spinner fa-spin' : 'fa-file-invoice'"></i>
                {{ submitting ? 'Creating...' : 'Create Purchase Order' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </template>
  <!-- src/views/purchasing/CreatePOFromQuotation.vue - Script -->
  <script>
  import { ref, reactive, onMounted, watch } from 'vue';
  import { useRoute, useRouter } from 'vue-router';
  import axios from 'axios';

  export default {
    name: 'CreatePOFromQuotation',

    setup() {
      const route = useRoute();
      const router = useRouter();

      // State variables
      const quotation = ref({});
      const loading = ref(true);
      const error = ref(null);
      const submitting = ref(false);
      const validationErrors = ref({});

      // Form data for the PO
      const formData = reactive({
        quotation_id: '',
        po_date: new Date().toISOString().split('T')[0], // Today
        vendor_id: '',
        payment_terms: '',
        delivery_terms: '',
        expected_delivery: '',
        notes: '',
        lines: []
      });

      // Calculate expected delivery date - 14 days from today by default
      const setDefaultExpectedDelivery = () => {
        const date = new Date();
        date.setDate(date.getDate() + 14);
        return date.toISOString().split('T')[0];
      };
      formData.expected_delivery = setDefaultExpectedDelivery();

      // Watch for changes in po_date to ensure expected_delivery is always after po_date
      watch(() => formData.po_date, (newDate) => {
        const poDate = new Date(newDate);
        const expectedDate = new Date(formData.expected_delivery);

        if (expectedDate <= poDate) {
          // Set expected delivery to 14 days after new PO date
          poDate.setDate(poDate.getDate() + 14);
          formData.expected_delivery = poDate.toISOString().split('T')[0];
        }
      });

      // Fetch quotation data
      const fetchQuotation = async () => {
        loading.value = true;
        error.value = null;

        try {
          const quotationId = route.params.id;
          if (!quotationId) {
            error.value = 'No quotation ID provided.';
            loading.value = false;
            return;
          }

          const response = await axios.get(`/api/vendor-quotations/${quotationId}`);

          if (response.data.status === 'success') {
            quotation.value = response.data.data;

            // Make sure the quotation is in 'accepted' status
            if (quotation.value.status !== 'accepted') {
              error.value = 'Only accepted quotations can be converted to Purchase Orders.';
              loading.value = false;
              return;
            }

            // Check if quotation has lines
            if (!quotation.value.lines || quotation.value.lines.length === 0) {
              error.value = 'This quotation has no line items. Cannot create a Purchase Order.';
              loading.value = false;
              return;
            }

            // Initialize form data
            formData.quotation_id = quotation.value.quotation_id;
            formData.vendor_id = quotation.value.vendor_id;

            // Try to set smart defaults for payment and delivery terms
            if (quotation.value.vendor && quotation.value.vendor.payment_terms) {
              formData.payment_terms = quotation.value.vendor.payment_terms;
            } else {
              formData.payment_terms = 'Net 30'; // Default
            }

            if (quotation.value.vendor && quotation.value.vendor.delivery_terms) {
              formData.delivery_terms = quotation.value.vendor.delivery_terms;
            }

            // Copy the quotation notes if available
            if (quotation.value.notes) {
              formData.notes = quotation.value.notes;
            }

            // Use earliest delivery date from lines as expected delivery if available
            const deliveryDates = quotation.value.lines
              .filter(line => line.delivery_date)
              .map(line => new Date(line.delivery_date));

            if (deliveryDates.length > 0) {
              const earliestDate = new Date(Math.min(...deliveryDates));
              formData.expected_delivery = earliestDate.toISOString().split('T')[0];
            }

            // Initialize line items
            formData.lines = quotation.value.lines.map(line => {
              const item_name = line.item ? `${line.item.item_code} - ${line.item.name}` : `Item ID: ${line.item_id}`;
              const uom_name = line.unitOfMeasure ? line.unitOfMeasure.name : 'N/A';

              // Calculate line subtotal
              const subtotal = line.unit_price * line.quantity;

              return {
                item_id: line.item_id,
                item_name: item_name,
                quantity: line.quantity,
                uom_id: line.uom_id,
                uom_name: uom_name,
                unit_price: line.unit_price,
                tax: 0, // Default to 0
                subtotal: subtotal,
                total: subtotal // Initialize total to subtotal
              };
            });
          } else {
            throw new Error('Failed to fetch quotation details');
          }
        } catch (err) {
          console.error('Error fetching quotation:', err);
          error.value = 'Failed to load quotation details. Please try again.';
        } finally {
          loading.value = false;
        }
      };

      // Update line totals when quantity, unit price or tax changes
      const updateLineTotals = (index) => {
        const line = formData.lines[index];

        // Ensure values are numbers
        const quantity = parseFloat(line.quantity) || 0;
        const unitPrice = parseFloat(line.unit_price) || 0;
        const tax = parseFloat(line.tax) || 0;

        // Calculate line subtotal
        line.subtotal = quantity * unitPrice;

        // Calculate line total
        line.total = line.subtotal + tax;
      };

      // Calculate totals
      const calculateSubtotal = () => {
        return formData.lines.reduce((total, line) => {
          return total + parseFloat(line.subtotal || 0);
        }, 0);
      };

      const calculateTaxTotal = () => {
        return formData.lines.reduce((total, line) => {
          return total + parseFloat(line.tax || 0);
        }, 0);
      };

      const calculateGrandTotal = () => {
        return calculateSubtotal() + calculateTaxTotal();
      };

      // Create Purchase Order
      const createPO = async () => {
        // Validate the form first
        if (!validateForm()) {
          return;
        }

        submitting.value = true;
        validationErrors.value = {};

        try {
          // Prepare data for submission
          const submitData = {
            quotation_id: formData.quotation_id,
            po_date: formData.po_date,
            vendor_id: formData.vendor_id,
            payment_terms: formData.payment_terms,
            delivery_terms: formData.delivery_terms,
            expected_delivery: formData.expected_delivery,
            notes: formData.notes,
            lines: formData.lines.map(line => ({
              item_id: line.item_id,
              unit_price: line.unit_price,
              quantity: line.quantity,
              uom_id: line.uom_id,
              tax: line.tax
            })),
            total_amount: calculateGrandTotal(),
            tax_amount: calculateTaxTotal()
          };

          // Send request to create a new PO
          // First try to use the specialized endpoint for creating from quotation
          try {
            const response = await axios.post('/api/purchase-orders/create-from-quotation', {
              quotation_id: formData.quotation_id
            });

            if (response.data.status === 'success') {
              alert('Purchase Order created successfully!');
              router.push(`/purchasing/orders/${response.data.data.po_id}`);
            } else {
              throw new Error('Failed to create Purchase Order');
            }
          } catch (err) {
            // If the specialized endpoint fails, try the standard create endpoint
            console.warn('Specialized endpoint failed, trying standard create:', err);

            const response = await axios.post('/api/purchase-orders', submitData);

            if (response.data.status === 'success') {
              alert('Purchase Order created successfully!');
              router.push(`/purchasing/orders/${response.data.data.po_id}`);
            } else {
              throw new Error('Failed to create Purchase Order');
            }
          }
        } catch (err) {
          console.error('Error creating Purchase Order:', err);

          if (err.response && err.response.status === 422) {
            // Validation errors
            validationErrors.value = err.response.data.errors || {};
            window.scrollTo(0, 0); // Scroll to top to show errors
          } else if (err.response && err.response.data && err.response.data.message) {
            // Server returned an error message
            error.value = err.response.data.message;
          } else {
            // Generic error
            error.value = 'Failed to create Purchase Order. Please try again.';
          }
        } finally {
          submitting.value = false;
        }
      };

      // Validate form
      const validateForm = () => {
        const errors = {};

        // Basic validation
        if (!formData.po_date) {
          errors.po_date = ['PO date is required'];
        }

        if (!formData.expected_delivery) {
          errors.expected_delivery = ['Expected delivery date is required'];
        } else if (new Date(formData.expected_delivery) < new Date(formData.po_date)) {
          errors.expected_delivery = ['Expected delivery date must be after PO date'];
        }

        // Validate line items
        formData.lines.forEach((line, index) => {
          // Check quantity
          if (!line.quantity || parseFloat(line.quantity) <= 0) {
            errors[`lines.${index}.quantity`] = ['Quantity must be greater than zero'];
          }

          // Check unit price
          if (!line.unit_price || parseFloat(line.unit_price) <= 0) {
            errors[`lines.${index}.unit_price`] = ['Unit price must be greater than zero'];
          }

          // Check tax (can be zero but not negative)
          if (line.tax && parseFloat(line.tax) < 0) {
            errors[`lines.${index}.tax`] = ['Tax cannot be negative'];
          }
        });

        // Update validation errors and return result
        if (Object.keys(errors).length > 0) {
          validationErrors.value = errors;
          window.scrollTo(0, 0); // Scroll to top to show errors
          return false;
        }

        return true;
      };

      // Navigation
      const goBack = () => {
        router.push(`/purchasing/quotations/${route.params.id}`);
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

      const formatCurrency = (amount) => {
        if (amount === null || amount === undefined || isNaN(amount)) return '$0.00';
        return `${parseFloat(amount).toFixed(2)}`;
      };

      onMounted(() => {
        fetchQuotation();
      });

      return {
        quotation,
        formData,
        loading,
        error,
        submitting,
        validationErrors,
        updateLineTotals,
        calculateSubtotal,
        calculateTaxTotal,
        calculateGrandTotal,
        createPO,
        goBack,
        formatDate,
        formatStatus,
        formatCurrency
      };
    }
  };
  </script>
  <!-- src/views/purchasing/CreatePOFromQuotation.vue - Style -->
  <style scoped>
  .create-po {
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

  .info-card {
    background-color: #f8f9fa;
    border-radius: 8px;
    padding: 1.25rem;
  }

  .info-item {
    margin-bottom: 0.5rem;
    display: flex;
    flex-wrap: wrap;
  }

  .info-label {
    font-weight: 500;
    color: #6c757d;
    min-width: 140px;
    margin-right: 0.5rem;
  }

  .info-value {
    flex: 1;
  }

  .badge {
    font-size: 0.8rem;
    padding: 5px 8px;
    border-radius: 4px;
  }

  .badge-success {
    background-color: #28a745;
    color: white;
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

  .input-group-append .input-group-text {
    border-radius: 0 4px 4px 0;
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

  .btn-outline-danger {
    color: #dc3545;
    border-color: #dc3545;
  }

  .btn-outline-danger:hover {
    color: #fff;
    background-color: #dc3545;
    border-color: #dc3545;
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

  .form-actions {
    margin-top: 2rem;
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

    .info-label {
      min-width: 120px;
    }

    .table-responsive {
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
    }

    .d-flex {
      flex-direction: column;
    }

    .btn {
      display: block;
      width: 100%;
      margin-bottom: 0.5rem;
    }
  }

  /* Print styles */
  @media print {
    .create-po {
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

    .form-actions {
      display: none;
    }
  }
  </style>
