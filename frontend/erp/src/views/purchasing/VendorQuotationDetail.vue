<!-- frontend/erp/src/views/purchasing/VendorQuotationDetail.vue -->
<template>
    <div class="quotation-detail-container">
      <div class="header-actions">
        <h2 class="section-title">Vendor Quotation Details</h2>
        <div class="action-buttons">
          <router-link :to="`/purchasing/quotations`" class="btn btn-outline-secondary me-2">
            <i class="fas fa-arrow-left"></i> Back to List
          </router-link>

          <div class="btn-group" v-if="quotation">
            <button
              v-if="quotation.status === 'received'"
              @click="updateStatus('accepted')"
              class="btn btn-success me-2"
            >
              <i class="fas fa-check"></i> Accept
            </button>

            <button
              v-if="quotation.status === 'received'"
              @click="updateStatus('rejected')"
              class="btn btn-danger me-2"
            >
              <i class="fas fa-times"></i> Reject
            </button>

            <router-link
              v-if="quotation.status === 'received'"
              :to="`/purchasing/quotations/${quotationId}/edit`"
              class="btn btn-primary me-2"
            >
              <i class="fas fa-edit"></i> Edit
            </router-link>

            <router-link
              v-if="quotation.status === 'accepted'"
              :to="`/purchasing/quotations/${quotationId}/create-po`"
              class="btn btn-info me-2"
            >
              <i class="fas fa-file-alt"></i> Create PO
            </router-link>

            <button v-if="canDelete" @click="confirmDelete" class="btn btn-outline-danger">
              <i class="fas fa-trash"></i> Delete
            </button>
          </div>
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

      <div v-else class="quotation-detail-content">
        <div class="row">
          <div class="col-md-6">
            <div class="card mb-4">
              <div class="card-header">
                <h5 class="card-title mb-0">Quotation Information</h5>
              </div>
              <div class="card-body">
                <div class="detail-row">
                  <div class="detail-label">Status:</div>
                  <div class="detail-value">
                    <span :class="getStatusBadgeClass(quotation.status)">
                      {{ formatStatus(quotation.status) }}
                    </span>
                  </div>
                </div>
                <div class="detail-row">
                  <div class="detail-label">RFQ Number:</div>
                  <div class="detail-value">
                    <router-link :to="`/purchasing/rfqs/${quotation.rfq_id}`">
                      {{ quotation.requestForQuotation?.rfq_number || 'N/A' }}
                    </router-link>
                  </div>
                </div>
                <div class="detail-row">
                  <div class="detail-label">Quotation Date:</div>
                  <div class="detail-value">{{ formatDate(quotation.quotation_date) }}</div>
                </div>
                <div class="detail-row">
                  <div class="detail-label">Valid Until:</div>
                  <div class="detail-value">{{ formatDate(quotation.validity_date) }}</div>
                </div>
                <div class="detail-row">
                  <div class="detail-label">Created At:</div>
                  <div class="detail-value">{{ formatDateTime(quotation.created_at) }}</div>
                </div>
                <div class="detail-row">
                  <div class="detail-label">Last Updated:</div>
                  <div class="detail-value">{{ formatDateTime(quotation.updated_at) }}</div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-md-6">
            <div class="card mb-4">
              <div class="card-header">
                <h5 class="card-title mb-0">Vendor Information</h5>
              </div>
              <div class="card-body">
                <div class="detail-row">
                  <div class="detail-label">Name:</div>
                  <div class="detail-value">
                    <router-link :to="`/purchasing/vendors/${quotation.vendor?.vendor_id}`">
                      {{ quotation.vendor?.name || 'N/A' }}
                    </router-link>
                  </div>
                </div>
                <div class="detail-row">
                  <div class="detail-label">Contact Person:</div>
                  <div class="detail-value">{{ quotation.vendor?.contact_person || 'N/A' }}</div>
                </div>
                <div class="detail-row">
                  <div class="detail-label">Email:</div>
                  <div class="detail-value">{{ quotation.vendor?.email || 'N/A' }}</div>
                </div>
                <div class="detail-row">
                  <div class="detail-label">Phone:</div>
                  <div class="detail-value">{{ quotation.vendor?.phone || 'N/A' }}</div>
                </div>
                <div class="detail-row">
                  <div class="detail-label">Address:</div>
                  <div class="detail-value">{{ quotation.vendor?.address || 'N/A' }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="card mb-4">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Quotation Lines</h5>
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
                    <th class="text-center">Delivery Date</th>
                    <th class="text-end">Subtotal</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(line, index) in quotation.lines" :key="index">
                    <td>{{ line.item?.item_code || 'N/A' }}</td>
                    <td>{{ line.item?.name || 'N/A' }}</td>
                    <td class="text-center">{{ formatNumber(line.quantity) }}</td>
                    <td class="text-center">{{ line.unitOfMeasure?.symbol || 'N/A' }}</td>
                    <td class="text-end">${{ formatNumber(line.unit_price) }}</td>
                    <td class="text-center">{{ formatDate(line.delivery_date) || 'Not Specified' }}</td>
                    <td class="text-end">${{ formatNumber(line.quantity * line.unit_price) }}</td>
                  </tr>
                </tbody>
                <tfoot>
                  <tr>
                    <td colspan="5"></td>
                    <td class="text-end fw-bold">Total:</td>
                    <td class="text-end fw-bold">${{ formatNumber(calculateTotal()) }}</td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- Confirmation Modal -->
      <ConfirmationModal
        v-if="showDeleteModal"
        :title="'Delete Quotation'"
        :message="'Are you sure you want to delete this quotation? This action cannot be undone.'"
        :confirm-button-text="'Delete'"
        :confirm-button-variant="'danger'"
        @confirm="deleteQuotation"
        @cancel="showDeleteModal = false"
      />
    </div>
  </template>

  <script>
  import { ref, computed, onMounted } from 'vue';
  import { useRouter, useRoute } from 'vue-router';
  import axios from 'axios';

  export default {
    name: 'VendorQuotationDetail',

    setup() {
      const router = useRouter();
      const route = useRoute();
      const quotationId = computed(() => route.params.id);

      const loading = ref(true);
      const quotation = ref(null);
      const showDeleteModal = ref(false);

      // Fetch quotation details
      const fetchQuotation = async () => {
        loading.value = true;

        try {
          const response = await axios.get(`/api/vendor-quotations/${quotationId.value}`);

          if (response.data.status === 'success') {
            quotation.value = response.data.data;
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
        if (!dateString) return null;
        const date = new Date(dateString);
        return date.toLocaleDateString();
      };

      // Format date and time
      const formatDateTime = (dateTimeString) => {
        if (!dateTimeString) return 'N/A';
        const date = new Date(dateTimeString);
        return `${date.toLocaleDateString()} ${date.toLocaleTimeString()}`;
      };

      // Format number with commas
      const formatNumber = (num) => {
        if (num === undefined || num === null) return 'N/A';
        return num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      };

      // Format status
      const formatStatus = (status) => {
        if (!status) return 'Unknown';

        return status.charAt(0).toUpperCase() + status.slice(1);
      };

      // Get status badge class
      const getStatusBadgeClass = (status) => {
        const baseClass = 'badge ';

        switch (status) {
          case 'received':
            return baseClass + 'bg-info';
          case 'accepted':
            return baseClass + 'bg-success';
          case 'rejected':
            return baseClass + 'bg-danger';
          default:
            return baseClass + 'bg-secondary';
        }
      };

      // Calculate total amount
      const calculateTotal = () => {
        if (!quotation.value || !quotation.value.lines) return 0;

        return quotation.value.lines.reduce((total, line) => {
          return total + (line.quantity * line.unit_price);
        }, 0);
      };

      // Update quotation status
      const updateStatus = async (newStatus) => {
        try {
          const response = await axios.patch(`/api/vendor-quotations/${quotationId.value}/status`, {
            status: newStatus
          });

          if (response.data.status === 'success') {
            // Refresh the quotation details
            fetchQuotation();
          } else {
            console.error('Failed to update status:', response.data.message);
          }
        } catch (error) {
          console.error('Error updating status:', error);
        }
      };

      // Check if quotation can be deleted
      const canDelete = computed(() => {
        return quotation.value && quotation.value.status === 'received';
      });

      // Show delete confirmation modal
      const confirmDelete = () => {
        showDeleteModal.value = true;
      };

      // Delete quotation
      const deleteQuotation = async () => {
        try {
          const response = await axios.delete(`/api/vendor-quotations/${quotationId.value}`);

          if (response.data.status === 'success') {
            router.push('/purchasing/quotations');
          } else {
            console.error('Failed to delete quotation:', response.data.message);
          }
        } catch (error) {
          console.error('Error deleting quotation:', error);
        } finally {
          showDeleteModal.value = false;
        }
      };

      onMounted(() => {
        fetchQuotation();
      });

      return {
        quotationId,
        loading,
        quotation,
        showDeleteModal,
        canDelete,
        formatDate,
        formatDateTime,
        formatNumber,
        formatStatus,
        getStatusBadgeClass,
        calculateTotal,
        updateStatus,
        confirmDelete,
        deleteQuotation
      };
    }
  };
  </script>

  <style scoped>
  .quotation-detail-container {
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

  .badge {
    font-size: 0.75rem;
    padding: 0.35rem 0.65rem;
  }

  table th, table td {
    vertical-align: middle;
  }
  </style>
