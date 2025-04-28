<!-- frontend/erp/src/views/purchasing/CreatePOFromQuotation.vue -->
<template>
    <div class="create-po-container">
      <div class="header-actions">
        <h2 class="section-title">Create PO from Quotation</h2>
        <div class="action-buttons">
          <router-link :to="`/purchasing/quotations/${quotationId}`" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Quotation
          </router-link>
        </div>
      </div>

      <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
      </div>

      <div v-else-if="!quotation" class="alert alert-warning">
        Quotation not found or has been deleted.
      </div>

      <div v-else-if="quotation.status !== 'accepted'" class="alert alert-danger">
        Only accepted quotations can be converted to Purchase Orders.
      </div>

      <div v-else class="create-po-content">
        <div class="card mb-4">
          <div class="card-header">
            <h5 class="card-title mb-0">Quotation Information</h5>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-6">
                <div class="detail-row">
                  <div class="detail-label">RFQ Number:</div>
                  <div class="detail-value">{{ quotation.requestForQuotation?.rfq_number || 'N/A' }}</div>
                </div>
                <div class="detail-row">
                  <div class="detail-label">Quotation Date:</div>
                  <div class="detail-value">{{ formatDate(quotation.quotation_date) }}</div>
                </div>
                <div class="detail-row">
                  <div class="detail-label">Validity Date:</div>
                  <div class="detail-value">{{ formatDate(quotation.validity_date) }}</div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="detail-row">
                  <div class="detail-label">Vendor:</div>
                  <div class="detail-value">{{ quotation.vendor?.name || 'N/A' }}</div>
                </div>
                <div class="detail-row">
                  <div class="detail-label">Contact Person:</div>
                  <div class="detail-value">{{ quotation.vendor?.contact_person || 'N/A' }}</div>
                </div>
                <div class="detail-row">
                  <div class="detail-label">Email:</div>
                  <div class="detail-value">{{ quotation.vendor?.email || 'N/A' }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <form @submit.prevent="createPurchaseOrder" class="po-form">
          <div class="card mb-4">
            <div class="card-header">
              <h5 class="card-title mb-0">Purchase Order Details</h5>
            </div>
            <div class="card-body">
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group mb-3">
                    <label for="po_date" class="form-label">PO Date <span class="text-danger">*</span></label>
                    <input
                      type="date"
                      id="po_date"
                      v-model="form.po_date"
                      class="form-control"
                      :class="{ 'is-invalid': errors.po_date }"
                      required
                    />
                    <div v-if="errors.po_date" class="invalid-feedback">{{ errors.po_date }}</div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group mb-3">
                    <label for="expected_delivery" class="form-label">Expected Delivery Date</label>
                    <input
                      type="date"
                      id="expected_delivery"
                      v-model="form.expected_delivery"
                      class="form-control"
                      :class="{ 'is-invalid': errors.expected_delivery }"
                    />
                    <div v-if="errors.expected_delivery" class="invalid-feedback">{{ errors.expected_delivery }}</div>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group mb-3">
                    <label for="payment_terms" class="form-label">Payment Terms</label>
                    <select
                      id="payment_terms"
                      v-model="form.payment_terms"
                      class="form-select"
                      :class="{ 'is-invalid': errors.payment_terms }"
                    >
                      <option value="">Select Payment Terms</option>
                      <option value="net_30">Net 30 Days</option>
                      <option value="net_60">Net 60 Days</option>
                      <option value="net_90">Net 90 Days</option>
                      <option value="immediate">Immediate</option>
                      <option value="cod">Cash on Delivery</option>
                    </select>
                    <div v-if="errors.payment_terms" class="invalid-feedback">{{ errors.payment_terms }}</div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group mb-3">
                    <label for="delivery_terms" class="form-label">Delivery Terms</label>
                    <select
                      id="delivery_terms"
                      v-model="form.delivery_terms"
                      class="form-select"
                      :class="{ 'is-invalid': errors.delivery_terms }"
                    >
                      <option value="">Select Delivery Terms</option>
                      <option value="FOB">FOB - Free On Board</option>
                      <option value="CIF">CIF - Cost, Insurance, Freight</option>
                      <option value="DDP">DDP - Delivered Duty Paid</option>
                      <option value="EXW">EXW - Ex Works</option>
                    </select>
                    <div v-if="errors.delivery_terms" class="invalid-feedback">{{ errors.delivery_terms }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h5 class="card-title mb-0">Purchase Order Lines</h5>
              <div class="total-section">
                <span class="fw-bold">Total Amount: </span>
                <span class="total-amount">${{ calculateTotal().toFixed(2) }}</span>
              </div>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-striped">
                  <thead>
                    <tr>
                      <th>Item</th>
                      <th>Description</th>
                      <th class="text-center">Quantity</th>
                      <th class="text-center">Unit</th>
                      <th class="text-end">Unit Price</th>
                      <th class="text-center">Tax</th>
                      <th class="text-end">Subtotal</th>
                      <th class="text-end">Total</th>
                      <th class="text-center">Include</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(line, index) in form.lines" :key="index">
                      <td>{{ line.item.item_code }}</td>
                      <td>{{ line.item.name }}</td>
                      <td class="text-center">
                        <input
                          type="number"
                          v-model.number="line.quantity"
                          class="form-control form-control-sm text-center"
                          :class="{ 'is-invalid': lineErrors[index]?.quantity }"
                          min="0.01"
                          step="0.01"
                          required
                        />
                      </td>
                      <td class="text-center">{{ line.unitOfMeasure?.symbol || 'N/A' }}</td>
                      <td class="text-end">
                        <input
                          type="number"
                          v-model.number="line.unit_price"
                          class="form-control form-control-sm text-end"
                          :class="{ 'is-invalid': lineErrors[index]?.unit_price }"
                          min="0.01"
                          step="0.01"
                          required
                        />
                      </td>
                      <td class="text-center">
                        <input
                          type="number"
                          v-model.number="line.tax"
                          class="form-control form-control-sm text-center"
                          :class="{ 'is-invalid': lineErrors[index]?.tax }"
                          min="0"
                          step="0.01"
                        />
                      </td>
                      <td class="text-end">${{ formatNumber(line.quantity * line.unit_price) }}</td>
                      <td class="text-end">${{ formatNumber((line.quantity * line.unit_price) + (line.tax || 0)) }}</td>
                      <td class="text-center">
                        <div class="form-check">
                          <input
                            type="checkbox"
                            v-model="line.include"
                            class="form-check-input"
                            :id="`include-${index}`"
                          />
                          <label class="form-check-label" :for="`include-${index}`"></label>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                  <tfoot>
                    <tr>
                      <td colspan="6" class="text-end fw-bold">Subtotal:</td>
                      <td class="text-end fw-bold">${{ formatNumber(calculateSubtotal()) }}</td>
                      <td colspan="2"></td>
                    </tr>
                    <tr>
                      <td colspan="6" class="text-end fw-bold">Tax:</td>
                      <td class="text-end fw-bold">${{ formatNumber(calculateTaxTotal()) }}</td>
                      <td colspan="2"></td>
                    </tr>
                    <tr>
                      <td colspan="6" class="text-end fw-bold">Total:</td>
                      <td class="text-end fw-bold">${{ formatNumber(calculateTotal()) }}</td>
                      <td colspan="2"></td>
                    </tr>
                  </tfoot>
                </table>
              </div>
            </div>
          </div>

          <div class="form-actions">
            <button type="button" @click="$router.push(`/purchasing/quotations/${quotationId}`)" class="btn btn-outline-secondary me-2">
              Cancel
            </button>
            <button type="submit" class="btn btn-primary" :disabled="submitting || !hasSelectedLines">
              <span v-if="submitting">
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                Creating PO...
              </span>
              <span v-else>Create Purchase Order</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </template>

  <script>
  import { ref, computed, onMounted, reactive } from 'vue';
  import { useRouter, useRoute } from 'vue-router';
  import axios from 'axios';

  export default {
    name: 'CreatePOFromQuotation',

    setup() {
      const router = useRouter();
      const route = useRoute();
      const quotationId = computed(() => route.params.id);

      const loading = ref(true);
      const submitting = ref(false);
      const quotation = ref(null);
      const errors = ref({});
      const lineErrors = ref([]);

      // Form data
      const form = reactive({
        po_date: new Date().toISOString().substr(0, 10), // Default to today
        expected_delivery: '',
        payment_terms: '',
        delivery_terms: '',
        quotation_id: '',
        lines: []
      });

      // Fetch quotation details
      const fetchQuotation = async () => {
        loading.value = true;

        try {
          const response = await axios.get(`/api/vendor-quotations/${quotationId.value}`);

          if (response.data.status === 'success') {
            quotation.value = response.data.data;

            // Set form data from quotation
            form.quotation_id = quotation.value.quotation_id;

            // Look for earliest delivery date from quotation lines to set as expected delivery
            const deliveryDates = quotation.value.lines
              .map(line => line.delivery_date)
              .filter(date => date);

            if (deliveryDates.length > 0) {
              form.expected_delivery = new Date(Math.max(...deliveryDates.map(date => new Date(date))))
                .toISOString().substr(0, 10);
            }

            // Prepare form lines from quotation lines
            form.lines = quotation.value.lines.map(line => ({
              item_id: line.item_id,
              item: line.item,
              quantity: line.quantity,
              uom_id: line.uom_id,
              unitOfMeasure: line.unitOfMeasure,
              unit_price: line.unit_price,
              tax: 0,
              include: true // Default to include all lines
            }));
          } else {
            console.error('Failed to fetch quotation:', response.data.message);
          }
        } catch (error) {
          console.error('Error fetching quotation:', error);
        } finally {
          loading.value = false;
        }
      };

      // Format date
      const formatDate = (dateString) => {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        return date.toLocaleDateString();
      };

      // Format number with commas
      const formatNumber = (num) => {
        if (num === undefined || num === null) return 'N/A';
        return num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      };

      // Calculate subtotal (sum of line subtotals)
      const calculateSubtotal = () => {
        return form.lines
          .filter(line => line.include)
          .reduce((total, line) => total + (line.quantity * line.unit_price), 0);
      };

      // Calculate tax total
      const calculateTaxTotal = () => {
        return form.lines
          .filter(line => line.include)
          .reduce((total, line) => total + (line.tax || 0), 0);
      };

      // Calculate total amount
      const calculateTotal = () => {
        return calculateSubtotal() + calculateTaxTotal();
      };

      // Check if at least one line is selected
      const hasSelectedLines = computed(() => {
        return form.lines.some(line => line.include);
      });

      // Create purchase order
      const createPurchaseOrder = async () => {
        submitting.value = true;
        errors.value = {};
        lineErrors.value = [];

        try {
          // Prepare data for API
          const poData = {
            po_date: form.po_date,
            vendor_id: quotation.value.vendor_id,
            payment_terms: form.payment_terms,
            delivery_terms: form.delivery_terms,
            expected_delivery: form.expected_delivery,
            quotation_id: form.quotation_id,
            lines: form.lines
              .filter(line => line.include)
              .map(line => ({
                item_id: line.item_id,
                unit_price: line.unit_price,
                quantity: line.quantity,
                uom_id: line.uom_id,
                tax: line.tax || 0
              }))
          };

          // Call API to create PO
          const response = await axios.post('/api/purchase-orders/create-from-quotation', poData);

          if (response.data.status === 'success') {
            // Navigate to the created PO
            router.push(`/purchasing/orders/${response.data.data.po_id}`);
          } else {
            console.error('Failed to create PO:', response.data.message);
          }
        } catch (error) {
          console.error('Error creating PO:', error);

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
          submitting.value = false;
        }
      };

      onMounted(() => {
        fetchQuotation();
      });

      return {
        quotationId,
        loading,
        submitting,
        quotation,
        form,
        errors,
        lineErrors,
        hasSelectedLines,
        formatDate,
        formatNumber,
        calculateSubtotal,
        calculateTaxTotal,
        calculateTotal,
        createPurchaseOrder
      };
    }
  };
  </script>

  <style scoped>
  .create-po-container {
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

  .card {
    border-radius: 0.5rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    margin-bottom: 1.5rem;
  }

  .card-header {
    background-color: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
  }

  .card-title {
    color: #1e293b;
  }

  .detail-row {
    display: flex;
    margin-bottom: 0.75rem;
    border-bottom: 1px solid #f1f5f9;
    padding-bottom: 0.75rem;
  }

  .detail-row:last-child {
    margin-bottom: 0;
    border-bottom: none;
    padding-bottom: 0;
  }

  .detail-label {
    width: 35%;
    font-weight: 600;
    color: #64748b;
  }

  .detail-value {
    width: 65%;
    color: #1e293b;
  }

  .form-actions {
    display: flex;
    justify-content: flex-end;
    padding-top: 1rem;
  }

  /* Make form controls smaller in the table */
  .table .form-control-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
  }

  .total-section {
    background-color: #f1f5f9;
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
  }

  .total-amount {
    font-size: 1.125rem;
    font-weight: 700;
    color: #1e293b;
  }

  table th, table td {
    vertical-align: middle;
  }

  /* Overrides for checkboxes */
  .form-check {
    display: flex;
    justify-content: center;
    min-height: auto;
    margin: 0;
  }

  .form-check-input {
    margin-top: 0;
    margin-left: 0;
    float: none;
  }
  </style>
