<!-- frontend/erp/src/views/purchasing/QuotationComparisonMatrix.vue -->
<template>
    <div class="comparison-matrix-container">
      <div class="header-actions">
        <h2 class="section-title">Quotation Comparison Matrix</h2>
        <div class="action-buttons">
          <router-link :to="`/purchasing/rfqs/${rfqId}`" class="btn btn-outline-secondary me-2">
            <i class="fas fa-arrow-left"></i> Back to RFQ
          </router-link>
          <button class="btn btn-outline-primary me-2" @click="printMatrix">
            <i class="fas fa-print"></i> Print
          </button>
        </div>
      </div>

      <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
      </div>

      <div v-else-if="!rfq" class="alert alert-warning">
        Request for Quotation not found or has been deleted.
      </div>

      <div v-else-if="quotations.length === 0" class="alert alert-info">
        No quotations available for comparison. Please wait for vendor responses.
      </div>

      <div v-else class="comparison-content">
        <div class="card mb-4">
          <div class="card-header">
            <h5 class="card-title mb-0">RFQ: {{ rfq.rfq_number }}</h5>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-4">
                <div class="detail-row">
                  <div class="detail-label">RFQ Date:</div>
                  <div class="detail-value">{{ formatDate(rfq.rfq_date) }}</div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="detail-row">
                  <div class="detail-label">Valid Until:</div>
                  <div class="detail-value">{{ formatDate(rfq.validity_date) }}</div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="detail-row">
                  <div class="detail-label">Status:</div>
                  <div class="detail-value">
                    <span :class="getRfqStatusBadgeClass(rfq.status)">
                      {{ formatStatus(rfq.status) }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="card mb-4">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Quotation Comparison</h5>
            <div>
              <select v-model="sortBy" class="form-select form-select-sm ms-2 d-inline-block" style="width: auto;">
                <option value="vendor">Vendor Name</option>
                <option value="price">Total Price</option>
                <option value="delivery">Delivery Date</option>
              </select>
            </div>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-bordered comparison-table">
                <thead>
                  <tr>
                    <th style="min-width: 150px;">Details</th>
                    <th v-for="quotation in sortedQuotations" :key="quotation.quotation_id" style="min-width: 200px;">
                      <div class="vendor-header" :class="{ 'best-vendor': getBestQuotation() === quotation.quotation_id }">
                        <div class="vendor-name">{{ quotation.vendor.name }}</div>
                        <div class="vendor-status">
                          <span :class="getStatusBadgeClass(quotation.status)">
                            {{ formatStatus(quotation.status) }}
                          </span>
                        </div>
                        <div class="vendor-date">{{ formatDate(quotation.quotation_date) }}</div>
                        <div class="vendor-valid-until">Valid until: {{ formatDate(quotation.validity_date) }}</div>
                        <div class="vendor-total">Total: ${{ formatNumber(calculateQuotationTotal(quotation)) }}</div>
                        <div class="vendor-actions">
                          <button
                            v-if="quotation.status === 'received'"
                            @click="acceptQuotation(quotation.quotation_id)"
                            class="btn btn-sm btn-outline-success me-1"
                            title="Accept"
                          >
                            <i class="fas fa-check"></i>
                          </button>
                          <router-link
                            :to="`/purchasing/quotations/${quotation.quotation_id}`"
                            class="btn btn-sm btn-outline-primary"
                            title="View Details"
                          >
                            <i class="fas fa-eye"></i>
                          </router-link>
                        </div>
                      </div>
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <!-- Items comparison rows -->
                  <template v-for="item in getUniqueItems()" :key="item.item_id">
                    <tr class="item-header-row">
                      <td colspan="100%" class="item-header">
                        <strong>{{ item.name }}</strong>
                        <span class="item-code ms-2 text-muted">({{ item.item_code }})</span>
                      </td>
                    </tr>
                    <tr>
                      <td>Unit Price</td>
                      <td v-for="quotation in sortedQuotations" :key="`${quotation.quotation_id}-${item.item_id}-price`"
                        :class="{ 'best-price': isBestPrice(quotation, item.item_id) }"
                      >
                        ${{ formatNumber(getItemPrice(quotation, item.item_id)) }}
                      </td>
                    </tr>
                    <tr>
                      <td>Quantity</td>
                      <td v-for="quotation in sortedQuotations" :key="`${quotation.quotation_id}-${item.item_id}-quantity`">
                        {{ formatNumber(getItemQuantity(quotation, item.item_id)) }} {{ getItemUnit(quotation, item.item_id) }}
                      </td>
                    </tr>
                    <tr>
                      <td>Subtotal</td>
                      <td v-for="quotation in sortedQuotations" :key="`${quotation.quotation_id}-${item.item_id}-subtotal`"
                        :class="{ 'best-price': isBestPrice(quotation, item.item_id) }"
                      >
                        ${{ formatNumber(getItemSubtotal(quotation, item.item_id)) }}
                      </td>
                    </tr>
                    <tr>
                      <td>Delivery Date</td>
                      <td v-for="quotation in sortedQuotations" :key="`${quotation.quotation_id}-${item.item_id}-delivery`"
                        :class="{ 'best-delivery': isBestDelivery(quotation, item.item_id) }"
                      >
                        {{ formatDate(getItemDeliveryDate(quotation, item.item_id)) || 'Not specified' }}
                      </td>
                    </tr>
                  </template>

                  <!-- Totals row -->
                  <tr class="totals-row">
                    <td><strong>Total Amount</strong></td>
                    <td v-for="quotation in sortedQuotations" :key="`${quotation.quotation_id}-total`"
                      :class="{ 'best-price': isLowestTotalPrice(quotation) }"
                    >
                      <strong>${{ formatNumber(calculateQuotationTotal(quotation)) }}</strong>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="card mb-4">
          <div class="card-header">
            <h5 class="card-title mb-0">Recommendation</h5>
          </div>
          <div class="card-body">
            <div v-if="getBestQuotation()" class="alert alert-success">
              <div class="row align-items-center">
                <div class="col-md-8">
                  <h4 class="alert-heading">Recommended Vendor: {{ getBestVendorName() }}</h4>
                  <p class="mb-0">
                    Based on price comparison, delivery schedule, and overall value,
                    we recommend accepting the quotation from {{ getBestVendorName() }}.
                  </p>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                  <button
                    v-if="getBestQuotationStatus() === 'received'"
                    @click="acceptQuotation(getBestQuotation())"
                    class="btn btn-success me-2"
                  >
                    <i class="fas fa-check me-1"></i> Accept Quote
                  </button>
                  <router-link
                    v-if="getBestQuotationStatus() === 'accepted'"
                    :to="`/purchasing/quotations/${getBestQuotation()}/create-po`"
                    class="btn btn-primary"
                  >
                    <i class="fas fa-file-alt me-1"></i> Create PO
                  </router-link>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </template>

  <script>
  import { ref, computed, onMounted, watch } from 'vue';
  import { useRoute } from 'vue-router';
  import axios from 'axios';

  export default {
    name: 'QuotationComparisonMatrix',

    setup() {
      const route = useRoute();
      const rfqId = computed(() => route.params.id);

      const loading = ref(true);
      const rfq = ref(null);
      const quotations = ref([]);
      const sortBy = ref('price'); // Default sort by price

      // Fetch RFQ details and quotations
      const fetchData = async () => {
        loading.value = true;

        try {
          // Fetch RFQ details
          const rfqResponse = await axios.get(`/api/request-for-quotations/${rfqId.value}`);

          if (rfqResponse.data.status === 'success') {
            rfq.value = rfqResponse.data.data;

            // Get all quotations for this RFQ
            if (rfq.value.vendorQuotations && rfq.value.vendorQuotations.length > 0) {
              const quotationsDetails = [];

              // Fetch complete details for each quotation
              for (const quotation of rfq.value.vendorQuotations) {
                try {
                  const quotationResponse = await axios.get(`/api/vendor-quotations/${quotation.quotation_id}`);
                  if (quotationResponse.data.status === 'success') {
                    quotationsDetails.push(quotationResponse.data.data);
                  }
                } catch (error) {
                  console.error(`Error fetching quotation ${quotation.quotation_id}:`, error);
                }
              }

              quotations.value = quotationsDetails;
            }
          } else {
            console.error('Failed to fetch RFQ:', rfqResponse.data.message);
          }
        } catch (error) {
          console.error('Error fetching data:', error);
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

      // Get RFQ status badge class
      const getRfqStatusBadgeClass = (status) => {
        const baseClass = 'badge ';

        switch (status) {
          case 'draft':
            return baseClass + 'bg-secondary';
          case 'sent':
            return baseClass + 'bg-info';
          case 'closed':
            return baseClass + 'bg-success';
          case 'canceled':
            return baseClass + 'bg-danger';
          default:
            return baseClass + 'bg-secondary';
        }
      };

      // Calculate quotation total
      const calculateQuotationTotal = (quotation) => {
        if (!quotation || !quotation.lines) return 0;

        return quotation.lines.reduce((total, line) => {
          return total + (line.quantity * line.unit_price);
        }, 0);
      };

      // Get all unique items across all quotations
      const getUniqueItems = () => {
        const itemsMap = new Map();

        quotations.value.forEach(quotation => {
          quotation.lines.forEach(line => {
            if (line.item && !itemsMap.has(line.item.item_id)) {
              itemsMap.set(line.item.item_id, line.item);
            }
          });
        });

        return Array.from(itemsMap.values());
      };

      // Get price for an item in a quotation
      const getItemPrice = (quotation, itemId) => {
        const line = quotation.lines.find(line => line.item_id === itemId);
        return line ? line.unit_price : 0;
      };

      // Get quantity for an item in a quotation
      const getItemQuantity = (quotation, itemId) => {
        const line = quotation.lines.find(line => line.item_id === itemId);
        return line ? line.quantity : 0;
      };

      // Get unit for an item in a quotation
      const getItemUnit = (quotation, itemId) => {
        const line = quotation.lines.find(line => line.item_id === itemId);
        return line && line.unitOfMeasure ? line.unitOfMeasure.symbol : '';
      };

      // Get subtotal for an item in a quotation
      const getItemSubtotal = (quotation, itemId) => {
        const line = quotation.lines.find(line => line.item_id === itemId);
        return line ? (line.quantity * line.unit_price) : 0;
      };

      // Get delivery date for an item in a quotation
      const getItemDeliveryDate = (quotation, itemId) => {
        const line = quotation.lines.find(line => line.item_id === itemId);
        return line ? line.delivery_date : null;
      };

      // Check if this is the best price for this item
      const isBestPrice = (quotation, itemId) => {
        const currentPrice = getItemPrice(quotation, itemId);
        if (currentPrice === 0) return false;

        return quotations.value.every(q => {
          const price = getItemPrice(q, itemId);
          return price === 0 || currentPrice <= price;
        });
      };

      // Check if this is the best delivery date for this item
      const isBestDelivery = (quotation, itemId) => {
        const currentDate = getItemDeliveryDate(quotation, itemId);
        if (!currentDate) return false;

        return quotations.value.every(q => {
          const date = getItemDeliveryDate(q, itemId);
          return !date || new Date(currentDate) <= new Date(date);
        });
      };

      // Check if this is the lowest total price quotation
      const isLowestTotalPrice = (quotation) => {
        const currentTotal = calculateQuotationTotal(quotation);
        return quotations.value.every(q => calculateQuotationTotal(q) >= currentTotal);
      };

      // Get the quotation ID with the best overall value
      const getBestQuotation = () => {
        if (quotations.value.length === 0) return null;

        // Simple algorithm: pick the one with lowest total price
        let bestId = null;
        let lowestPrice = Infinity;

        quotations.value.forEach(quotation => {
          const total = calculateQuotationTotal(quotation);
          if (total < lowestPrice) {
            lowestPrice = total;
            bestId = quotation.quotation_id;
          }
        });

        return bestId;
      };

      // Get the name of the vendor with the best quotation
      const getBestVendorName = () => {
        const bestId = getBestQuotation();
        if (!bestId) return 'None';

        const bestQuotation = quotations.value.find(q => q.quotation_id === bestId);
        return bestQuotation && bestQuotation.vendor ? bestQuotation.vendor.name : 'Unknown';
      };

      // Get the status of the best quotation
      const getBestQuotationStatus = () => {
        const bestId = getBestQuotation();
        if (!bestId) return null;

        const bestQuotation = quotations.value.find(q => q.quotation_id === bestId);
        return bestQuotation ? bestQuotation.status : null;
      };

      // Sort quotations based on selected criteria
      const sortedQuotations = computed(() => {
        if (quotations.value.length === 0) return [];

        return [...quotations.value].sort((a, b) => {
          if (sortBy.value === 'vendor') {
            return a.vendor.name.localeCompare(b.vendor.name);
          } else if (sortBy.value === 'price') {
            return calculateQuotationTotal(a) - calculateQuotationTotal(b);
          } else if (sortBy.value === 'delivery') {
            // Sort by earliest delivery date (using the earliest item delivery date)
            const getEarliestDelivery = (quotation) => {
              if (!quotation.lines || quotation.lines.length === 0) return null;

              const deliveryDates = quotation.lines
                .map(line => line.delivery_date)
                .filter(date => date);

              if (deliveryDates.length === 0) return null;

              return new Date(Math.min(...deliveryDates.map(date => new Date(date))));
            };

            const aDate = getEarliestDelivery(a);
            const bDate = getEarliestDelivery(b);

            if (!aDate && !bDate) return 0;
            if (!aDate) return 1;
            if (!bDate) return -1;

            return aDate - bDate;
          }

          return 0;
        });
      });

      // Accept quotation
      const acceptQuotation = async (id) => {
        try {
          const response = await axios.patch(`/api/vendor-quotations/${id}/status`, {
            status: 'accepted'
          });

          if (response.data.status === 'success') {
            // Refresh the data
            fetchData();
          } else {
            console.error('Failed to accept quotation:', response.data.message);
          }
        } catch (error) {
          console.error('Error accepting quotation:', error);
        }
      };

      // Print the comparison matrix
      const printMatrix = () => {
        window.print();
      };

      // Watch for changes in rfqId
      watch(rfqId, () => {
        fetchData();
      });

      onMounted(() => {
        fetchData();
      });

      return {
        rfqId,
        loading,
        rfq,
        quotations,
        sortBy,
        sortedQuotations,
        formatDate,
        formatNumber,
        formatStatus,
        getStatusBadgeClass,
        getRfqStatusBadgeClass,
        calculateQuotationTotal,
        getUniqueItems,
        getItemPrice,
        getItemQuantity,
        getItemUnit,
        getItemSubtotal,
        getItemDeliveryDate,
        isBestPrice,
        isBestDelivery,
        isLowestTotalPrice,
        getBestQuotation,
        getBestVendorName,
        getBestQuotationStatus,
        acceptQuotation,
        printMatrix
      };
    }
  };
  </script>

  <style scoped>
  .comparison-matrix-container {
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
    margin-bottom: 0.5rem;
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

  .comparison-table {
    border: 1px solid #e2e8f0;
  }

  .comparison-table th, .comparison-table td {
    padding: 0.75rem;
    vertical-align: middle;
  }

  .vendor-header {
    text-align: center;
    padding: 0.5rem;
    border-radius: 0.25rem;
  }

  .best-vendor {
    background-color: #ecfdf5;
    border: 1px solid #10b981;
  }

  .vendor-name {
    font-weight: 600;
    margin-bottom: 0.25rem;
  }

  .vendor-status {
    margin-bottom: 0.25rem;
  }

  .vendor-date, .vendor-valid-until {
    font-size: 0.875rem;
    color: #64748b;
    margin-bottom: 0.25rem;
  }

  .vendor-total {
    font-weight: 600;
    margin-bottom: 0.5rem;
  }

  .vendor-actions {
    margin-top: 0.5rem;
  }

  .item-header-row {
    background-color: #f8fafc;
  }

  .item-header {
    padding: 0.5rem 0.75rem;
  }

  .item-code {
    font-size: 0.875rem;
  }

  .best-price {
    background-color: #ecfdf5;
    font-weight: 600;
    color: #047857;
  }

  .best-delivery {
    background-color: #eff6ff;
    font-weight: 600;
    color: #1d4ed8;
  }

  .totals-row {
    background-color: #f1f5f9;
    font-size: 1.125rem;
  }

  /* Print styles */
  @media print {
    .header-actions, .action-buttons, .vendor-actions {
      display: none;
    }

    .card {
      break-inside: avoid;
      box-shadow: none;
      border: 1px solid #e2e8f0;
    }

    .comparison-matrix-container {
      padding: 0;
    }
  }
  </style>
