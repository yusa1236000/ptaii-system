<!-- src/views/purchasing/VendorQuotationDetail.vue -->
<template>
    <div class="quotation-detail">
      <div class="card">
        <div class="card-header">
          <div class="d-flex justify-content-between align-items-center">
            <h2>Vendor Quotation Details</h2>
            <div class="btn-toolbar">
              <button class="btn btn-secondary mr-2" @click="goBack">
                <i class="fas fa-arrow-left"></i> Back
              </button>
              <div class="btn-group" v-if="quotation">
                <button
                  v-if="quotation.status === 'received'"
                  class="btn btn-primary mr-2"
                  @click="goToEdit"
                >
                  <i class="fas fa-edit"></i> Edit
                </button>
                <button
                  v-if="quotation.status === 'received'"
                  class="btn btn-success mr-2"
                  @click="updateStatus('accepted')"
                  :disabled="updating"
                >
                  <i class="fas" :class="updating ? 'fa-spinner fa-spin' : 'fa-check'"></i> Accept
                </button>
                <button
                  v-if="quotation.status === 'received'"
                  class="btn btn-danger mr-2"
                  @click="updateStatus('rejected')"
                  :disabled="updating"
                >
                  <i class="fas" :class="updating ? 'fa-spinner fa-spin' : 'fa-times'"></i> Reject
                </button>
                <button
                  v-if="quotation.status === 'accepted'"
                  class="btn btn-warning"
                  @click="createPO"
                >
                  <i class="fas fa-file-invoice"></i> Create PO
                </button>
                <button
                  class="btn btn-info"
                  @click="printQuotation"
                >
                  <i class="fas fa-print"></i> Print
                </button>
              </div>
            </div>
          </div>
        </div>
        <div class="card-body">
          <!-- Loading State -->
          <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="sr-only">Loading...</span>
            </div>
            <p class="mt-2">Loading quotation details...</p>
          </div>

          <!-- Error State -->
          <div v-else-if="error" class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-triangle mr-2"></i> {{ error }}
          </div>

          <!-- Content -->
          <div v-else-if="quotation" class="quotation-content">
            <!-- Status Badge -->
            <div class="status-badge mb-4">
              <span
                class="badge"
                :class="{
                  'badge-info': quotation.status === 'received',
                  'badge-success': quotation.status === 'accepted',
                  'badge-danger': quotation.status === 'rejected'
                }"
              >
                {{ formatStatus(quotation.status) }}
              </span>
            </div>

            <!-- Quotation Info Cards -->
            <div class="row mb-4">
              <div class="col-md-6">
                <div class="info-card">
                  <h5 class="card-title">Quotation Information</h5>
                  <div class="card-content">
                    <div class="info-item">
                      <span class="info-label">Quotation ID:</span>
                      <span class="info-value">{{ quotation.quotation_id }}</span>
                    </div>
                    <div class="info-item">
                      <span class="info-label">Date:</span>
                      <span class="info-value">{{ formatDate(quotation.quotation_date) }}</span>
                    </div>
                    <div class="info-item">
                      <span class="info-label">Valid Until:</span>
                      <span class="info-value">{{ formatDate(quotation.validity_date) }}</span>
                    </div>
                    <div class="info-item">
                      <span class="info-label">RFQ Number:</span>
                      <span class="info-value">
                        <router-link
                          v-if="quotation.requestForQuotation"
                          :to="`/purchasing/rfqs/${quotation.rfq_id}`"
                        >
                          {{ quotation.requestForQuotation.rfq_number }}
                        </router-link>
                        <span v-else>N/A</span>
                      </span>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="info-card">
                  <h5 class="card-title">Vendor Information</h5>
                  <div class="card-content" v-if="quotation.vendor">
                    <div class="info-item">
                      <span class="info-label">Vendor:</span>
                      <span class="info-value">
                        <router-link :to="`/purchasing/vendors/${quotation.vendor_id}`">
                          {{ quotation.vendor.name }}
                        </router-link>
                      </span>
                    </div>
                    <div class="info-item">
                      <span class="info-label">Contact Person:</span>
                      <span class="info-value">{{ quotation.vendor.contact_person || 'N/A' }}</span>
                    </div>
                    <div class="info-item">
                      <span class="info-label">Email:</span>
                      <span class="info-value">{{ quotation.vendor.email || 'N/A' }}</span>
                    </div>
                    <div class="info-item">
                      <span class="info-label">Phone:</span>
                      <span class="info-value">{{ quotation.vendor.phone || 'N/A' }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Quotation Lines -->
            <div class="line-items mt-4">
              <h4>Quotation Items</h4>
              <div class="table-responsive">
                <table class="table table-bordered">
                  <thead class="bg-light">
                    <tr>
                      <th>Item</th>
                      <th>Quantity</th>
                      <th>UOM</th>
                      <th>Unit Price</th>
                      <th>Subtotal</th>
                      <th>Delivery Date</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="line in quotation.lines" :key="line.id">
                      <td>{{ line.item ? `${line.item.item_code} - ${line.item.name}` : 'Unknown Item' }}</td>
                      <td>{{ line.quantity }}</td>
                      <td>{{ line.unitOfMeasure ? line.unitOfMeasure.name : 'N/A' }}</td>
                      <td>{{ formatCurrency(line.unit_price) }}</td>
                      <td>{{ formatCurrency(line.unit_price * line.quantity) }}</td>
                      <td>{{ formatDate(line.delivery_date) }}</td>
                    </tr>
                  </tbody>
                  <tfoot>
                    <tr>
                      <td colspan="4" class="text-right font-weight-bold">Total:</td>
                      <td colspan="2" class="font-weight-bold">{{ formatCurrency(calculateTotal()) }}</td>
                    </tr>
                  </tfoot>
                </table>
              </div>
            </div>
          </div>
          <div v-else class="alert alert-warning">
            <i class="fas fa-exclamation-circle mr-2"></i> No quotation data found
          </div>
        </div>
      </div>
    </div>
  </template>

  <script>
  import { ref,onMounted } from 'vue';
  import { useRoute, useRouter } from 'vue-router';
  import axios from 'axios';

  export default {
    name: 'VendorQuotationDetail',

    setup() {
      const route = useRoute();
      const router = useRouter();

      const quotation = ref(null);
      const loading = ref(true);
      const error = ref(null);
      const updating = ref(false);

      const fetchQuotation = async () => {
        loading.value = true;
        error.value = null;

        try {
          const response = await axios.get(`/api/vendor-quotations/${route.params.id}`);

          if (response.data.status === 'success') {
            quotation.value = response.data.data;
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

      const updateStatus = async (status) => {
        if (updating.value) return;

        const confirmMsg = status === 'accepted'
          ? 'Are you sure you want to accept this quotation?'
          : 'Are you sure you want to reject this quotation?';

        if (!confirm(confirmMsg)) return;

        updating.value = true;

        try {
          const response = await axios.patch(`/api/vendor-quotations/${quotation.value.quotation_id}/status`, {
            status: status
          });

          if (response.data.status === 'success') {
            alert(`Quotation ${status === 'accepted' ? 'accepted' : 'rejected'} successfully!`);
            quotation.value.status = status;

            // If accepting a quotation, offer to create a PO
            if (status === 'accepted') {
              if (confirm('Would you like to create a Purchase Order from this quotation?')) {
                router.push(`/purchasing/quotations/${quotation.value.quotation_id}/create-po`);
              }
            }
          } else {
            throw new Error(`Failed to ${status} quotation`);
          }
        } catch (err) {
          console.error(`Error updating quotation status:`, err);
          alert(`Failed to update quotation status. ${err.response?.data?.message || 'Please try again.'}`);
        } finally {
          updating.value = false;
        }
      };

      const calculateTotal = () => {
        if (!quotation.value || !quotation.value.lines) return 0;

        return quotation.value.lines.reduce((total, line) => {
          return total + (line.unit_price * line.quantity);
        }, 0);
      };

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
        return amount !== null && amount !== undefined
          ? `$${Number(amount).toFixed(2)}`
          : 'N/A';
      };

      const goBack = () => {
        router.go(-1);
      };

      const goToEdit = () => {
        router.push(`/purchasing/quotations/${route.params.id}/edit`);
      };

      const createPO = () => {
        router.push(`/purchasing/quotations/${route.params.id}/create-po`);
      };

      const printQuotation = () => {
        window.print();
      };

      onMounted(() => {
        fetchQuotation();
      });

      return {
        quotation,
        loading,
        error,
        updating,
        updateStatus,
        calculateTotal,
        formatDate,
        formatStatus,
        formatCurrency,
        goBack,
        goToEdit,
        createPO,
        printQuotation
      };
    }
  };
  </script>

  <style scoped>
  .quotation-detail {
    max-width: 1200px;
    margin: 0 auto;
  }

  .card {
    border-radius: 8px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
    overflow: hidden;
  }

  .card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    padding: 1rem 1.5rem;
  }

  .card-body {
    padding: 1.5rem;
  }

  .badge {
    font-size: 0.9rem;
    padding: 8px 12px;
    border-radius: 4px;
  }

  .status-badge {
    margin-bottom: 1.5rem;
  }

  .info-card {
    background-color: #f8f9fa;
    border-radius: 8px;
    padding: 1.25rem;
    height: 100%;
  }

  .info-card .card-title {
    color: #495057;
    font-weight: 600;
    border-bottom: 1px solid #dee2e6;
    padding-bottom: 0.75rem;
    margin-bottom: 1rem;
  }

  .info-item {
    margin-bottom: 0.75rem;
    display: flex;
  }

  .info-label {
    font-weight: 500;
    color: #6c757d;
    width: 140px;
  }

  .info-value {
    flex: 1;
  }

  .line-items h4 {
    color: #495057;
    font-weight: 600;
    margin-bottom: 1rem;
  }

  .table {
    margin-bottom: 0;
  }

  .table thead th {
    background-color: #f8f9fa;
    color: #495057;
    font-weight: 600;
  }

  .table tfoot td {
    font-weight: 600;
    background-color: #f8f9fa;
  }

  .btn {
    padding: 0.5rem 1rem;
  }

  /* Print styles */
  @media print {
    .btn-toolbar {
      display: none;
    }

    .card {
      box-shadow: none;
      border: 1px solid #ddd;
    }
  }
  </style>
