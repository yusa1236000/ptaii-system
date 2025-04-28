<!-- src/views/purchasing/QuotationComparisonMatrix.vue - Template -->
<template>
    <div class="comparison-matrix">
      <div class="card">
        <div class="card-header">
          <div class="d-flex justify-content-between align-items-center">
            <h2>Quotation Comparison Matrix</h2>
            <div class="btn-toolbar">
              <button class="btn btn-secondary mr-2" @click="goBack">
                <i class="fas fa-arrow-left"></i> Back
              </button>
              <button class="btn btn-info mr-2" @click="printMatrix">
                <i class="fas fa-print"></i> Print
              </button>
              <button
                v-if="selectedQuotation"
                class="btn btn-success"
                @click="acceptQuotation"
                :disabled="updating"
              >
                <i class="fas" :class="updating ? 'fa-spinner fa-spin' : 'fa-check'"></i>
                {{ updating ? 'Processing...' : 'Accept Selected' }}
              </button>
            </div>
          </div>
        </div>
        <div class="card-body">
          <!-- Loading State -->
          <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="sr-only">Loading...</span>
            </div>
            <p class="mt-2">Loading quotations data...</p>
          </div>

          <!-- Error State -->
          <div v-else-if="error" class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-triangle mr-2"></i> {{ error }}
            <div class="mt-2">
              <button class="btn btn-outline-danger" @click="goBack">Go Back</button>
            </div>
          </div>

          <!-- RFQ Info -->
          <div v-else-if="rfq" class="rfq-info mb-4">
            <div class="info-card">
              <h5 class="card-title">Request For Quotation Details</h5>
              <div class="row">
                <div class="col-md-3">
                  <div class="info-item">
                    <span class="info-label">RFQ Number:</span>
                    <span class="info-value">{{ rfq.rfq_number }}</span>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="info-item">
                    <span class="info-label">Date:</span>
                    <span class="info-value">{{ formatDate(rfq.rfq_date) }}</span>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="info-item">
                    <span class="info-label">Valid Until:</span>
                    <span class="info-value">{{ formatDate(rfq.validity_date) }}</span>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="info-item">
                    <span class="info-label">Status:</span>
                    <span class="info-value">
                      <span
                        class="badge"
                        :class="{
                          'badge-info': rfq.status === 'sent',
                          'badge-success': rfq.status === 'closed',
                          'badge-danger': rfq.status === 'canceled'
                        }"
                      >
                        {{ formatStatus(rfq.status) }}
                      </span>
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- No Quotations State -->
          <div v-else-if="!quotations.length" class="alert alert-warning">
            <i class="fas fa-exclamation-circle mr-2"></i> No quotations found for comparison
          </div>

          <!-- Quotation Filters and Controls -->
          <div v-else class="controls-section mb-4">
            <div class="row">
              <div class="col-md-9">
                <div class="filters">
                  <div class="row">
                    <div class="col-md-4">
                      <div class="form-group">
                        <label for="sortBy">Sort By:</label>
                        <select id="sortBy" class="form-control" v-model="sortBy" @change="sortQuotations">
                          <option value="vendor_name">Vendor Name</option>
                          <option value="total_price">Total Price</option>
                          <option value="quotation_date">Quotation Date</option>
                          <option value="validity_date">Validity Date</option>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="form-group">
                        <label for="sortDirection">Sort Direction:</label>
                        <select id="sortDirection" class="form-control" v-model="sortDirection" @change="sortQuotations">
                          <option value="asc">Ascending</option>
                          <option value="desc">Descending</option>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="form-group">
                        <label for="statusFilter">Filter Status:</label>
                        <select id="statusFilter" class="form-control" v-model="statusFilter">
                          <option value="all">All Statuses</option>
                          <option value="received">Only Received</option>
                          <option value="accepted">Only Accepted</option>
                        </select>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="comparison-summary text-center bg-light p-3 rounded">
                  <h6 class="mb-2">Quotations Summary</h6>
                  <div><strong>Total Quotations:</strong> {{ quotations.length }}</div>
                  <div><strong>Received:</strong> {{ quotations.filter(q => q.status === 'received').length }}</div>
                  <div><strong>Accepted:</strong> {{ quotations.filter(q => q.status === 'accepted').length }}</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Comparison Matrix -->
          <div v-if="filteredQuotations.length" class="comparison-table">
            <div class="table-responsive">
              <table class="table table-bordered">
                <thead class="bg-light">
                  <tr>
                    <th style="width: 3%"></th>
                    <th style="width: 25%">Item</th>
                    <th style="width: 7%">Quantity</th>
                    <th style="width: 5%">UOM</th>
                    <th
                      v-for="quotation in filteredQuotations"
                      :key="quotation.quotation_id"
                      style="min-width: 200px"
                      :class="{ 'best-value': isBestValueVendor(quotation) }"
                    >
                      <div class="vendor-header">
                        <div class="form-check">
                          <input
                            class="form-check-input"
                            type="radio"
                            :id="`vendor-${quotation.quotation_id}`"
                            :value="quotation.quotation_id"
                            v-model="selectedQuotationId"
                            :disabled="quotation.status !== 'received'"
                          >
                          <label class="form-check-label" :for="`vendor-${quotation.quotation_id}`">
                            {{ quotation.vendor ? quotation.vendor.name : 'Unknown Vendor' }}
                          </label>
                        </div>
                        <div class="vendor-status">
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
                        <div class="vendor-date">
                          <div>Date: {{ formatDate(quotation.quotation_date) }}</div>
                          <div>Valid until: {{ formatDate(quotation.validity_date) }}</div>
                        </div>
                        <div class="vendor-actions">
                          <router-link
                            :to="`/purchasing/quotations/${quotation.quotation_id}`"
                            class="btn btn-sm btn-info"
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
                  <template v-for="(item, itemIndex) in itemsList" :key="itemIndex">
                    <tr>
                      <td class="text-center">{{ itemIndex + 1 }}</td>
                      <td>{{ item.name }}</td>
                      <td>{{ item.quantity }}</td>
                      <td>{{ item.uom }}</td>
                      <td
                        v-for="quotation in filteredQuotations"
                        :key="`${itemIndex}-${quotation.quotation_id}`"
                        :class="{ 'best-price': isBestPrice(quotation, item.id) }"
                      >
                        <div class="price-info">
                          <div class="unit-price">
                            Unit Price: {{ formatCurrency(getUnitPrice(quotation, item.id)) }}
                          </div>
                          <div class="total-price">
                            Total Price: {{ formatCurrency(getTotalPrice(quotation, item.id)) }}
                          </div>
                          <div class="delivery-date">
                            Delivery: {{ formatDate(getDeliveryDate(quotation, item.id)) }}
                          </div>
                        </div>
                      </td>
                    </tr>
                  </template>
                </tbody>
                <tfoot>
                  <tr class="totals-row">
                    <td colspan="4" class="text-right font-weight-bold">Total Amount</td>
                    <td
                      v-for="quotation in filteredQuotations"
                      :key="`total-${quotation.quotation_id}`"
                      :class="{ 'best-total': isBestTotal(quotation) }"
                      class="font-weight-bold"
                    >
                      {{ formatCurrency(calculateTotal(quotation)) }}
                    </td>
                  </tr>
                  <tr class="totals-row">
                    <td colspan="4" class="text-right font-weight-bold">Average Delivery Time</td>
                    <td
                      v-for="quotation in filteredQuotations"
                      :key="`delivery-${quotation.quotation_id}`"
                      :class="{ 'best-delivery': isBestDelivery(quotation) }"
                    >
                      {{ calculateAverageDeliveryDays(quotation) }} days
                    </td>
                  </tr>
                  <tr class="totals-row">
                    <td colspan="4" class="text-right font-weight-bold">Recommendation</td>
                    <td
                      v-for="quotation in filteredQuotations"
                      :key="`recommendation-${quotation.quotation_id}`"
                      :class="{ 'best-recommendation': isBestValueVendor(quotation) }"
                    >
                      <span
                        v-if="isBestValueVendor(quotation)"
                        class="recommendation-tag"
                      >
                        <i class="fas fa-award mr-1"></i> Best Value
                      </span>
                      <span v-else-if="isBestTotal(quotation)" class="recommendation-tag secondary">
                        <i class="fas fa-dollar-sign mr-1"></i> Best Price
                      </span>
                      <span v-else-if="isBestDelivery(quotation)" class="recommendation-tag secondary">
                        <i class="fas fa-truck mr-1"></i> Fastest Delivery
                      </span>
                      <span v-else>-</span>
                    </td>
                  </tr>
                </tfoot>
              </table>
            </div>

            <!-- Legend -->
            <div class="legend mt-3">
              <div class="legend-item">
                <span class="legend-color best-price-bg"></span>
                <span class="legend-text">Best price for item</span>
              </div>
              <div class="legend-item">
                <span class="legend-color best-total-bg"></span>
                <span class="legend-text">Best total price</span>
              </div>
              <div class="legend-item">
                <span class="legend-color best-delivery-bg"></span>
                <span class="legend-text">Best delivery time</span>
              </div>
              <div class="legend-item">
                <span class="legend-color best-value-bg"></span>
                <span class="legend-text">Best overall value</span>
              </div>
            </div>
          </div>
          <div v-else-if="quotations.length > 0" class="alert alert-info">
            <i class="fas fa-info-circle mr-2"></i> No quotations match the current filter criteria
          </div>
        </div>
      </div>
    </div>
  </template>

<script>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';

export default {
  name: 'QuotationComparisonMatrix',

  setup() {
    const route = useRoute();
    const router = useRouter();

    // State variables
    const rfq = ref(null);
    const quotations = ref([]);
    const loading = ref(true);
    const error = ref(null);
    const selectedQuotationId = ref(null);
    const updating = ref(false);

    // Sort and filter states
    const sortBy = ref('total_price');
    const sortDirection = ref('asc');
    const statusFilter = ref('all');

    // Get the RFQ ID from the route
    const rfqId = computed(() => route.params.id);

    // Get selected quotation
    const selectedQuotation = computed(() => {
      if (!selectedQuotationId.value) return null;
      return quotations.value.find(q => q.quotation_id === selectedQuotationId.value);
    });

    // Filter quotations by status
    const filteredQuotations = computed(() => {
      if (statusFilter.value === 'all') {
        return quotations.value;
      } else {
        return quotations.value.filter(q => q.status === statusFilter.value);
      }
    });

    // Generate a unique list of items across all quotations
    const itemsList = computed(() => {
      const allItems = new Map();

      // First, get all items from RFQ
      if (rfq.value && rfq.value.lines) {
        rfq.value.lines.forEach(line => {
          const itemName = line.item ? `${line.item.item_code} - ${line.item.name}` : `Item ID: ${line.item_id}`;
          const uomName = line.unitOfMeasure ? line.unitOfMeasure.name : 'N/A';

          allItems.set(line.item_id, {
            id: line.item_id,
            name: itemName,
            quantity: line.quantity,
            uom: uomName
          });
        });
      }

      // Then get any additional items from quotations (should not be needed normally)
      quotations.value.forEach(quotation => {
        if (quotation.lines) {
          quotation.lines.forEach(line => {
            if (!allItems.has(line.item_id)) {
              const itemName = line.item ? `${line.item.item_code} - ${line.item.name}` : `Item ID: ${line.item_id}`;
              const uomName = line.unitOfMeasure ? line.unitOfMeasure.name : 'N/A';

              allItems.set(line.item_id, {
                id: line.item_id,
                name: itemName,
                quantity: line.quantity,
                uom: uomName
              });
            }
          });
        }
      });

      return Array.from(allItems.values());
    });

    // Fetch RFQ and quotations data
    const fetchData = async () => {
      loading.value = true;
      error.value = null;

      try {
        // Get RFQ details
        const rfqResponse = await axios.get(`/api/request-for-quotations/${rfqId.value}`);

        if (rfqResponse.data.status === 'success') {
          rfq.value = rfqResponse.data.data;

          // Check if RFQ is in a valid status
          if (rfq.value.status !== 'sent' && rfq.value.status !== 'closed') {
            error.value = `This RFQ has a status of "${rfq.value.status}". Only RFQs with "sent" or "closed" status can be used for comparison.`;
            loading.value = false;
            return;
          }

          // Get all quotations for this RFQ
          const quotationsResponse = await axios.get(`/api/vendor-quotations`, {
            params: { rfq_id: rfqId.value }
          });

          if (quotationsResponse.data.status === 'success') {
            // Extract quotations from paginated response
            quotations.value = quotationsResponse.data.data.data || [];

            if (quotations.value.length === 0) {
              error.value = 'No quotations found for this RFQ.';
              loading.value = false;
              return;
            }

            // Sort quotations
            sortQuotations();

            // Auto-select the "best value" quotation if it's in received status
            const bestValue = quotations.value.find(q =>
              q.status === 'received' && isBestValueVendor(q)
            );

            if (bestValue) {
              selectedQuotationId.value = bestValue.quotation_id;
            }
          } else {
            throw new Error('Failed to fetch quotations');
          }
        } else {
          throw new Error('Failed to fetch RFQ details');
        }
      } catch (err) {
        console.error('Error fetching data:', err);
        error.value = 'Failed to load comparison data. Please try again.';
      } finally {
        loading.value = false;
      }
    };

    // Sort quotations
    const sortQuotations = () => {
      quotations.value.sort((a, b) => {
        let valueA, valueB;

        if (sortBy.value === 'vendor_name') {
          valueA = a.vendor ? a.vendor.name : '';
          valueB = b.vendor ? b.vendor.name : '';
        } else if (sortBy.value === 'total_price') {
          valueA = calculateTotal(a);
          valueB = calculateTotal(b);
        } else if (sortBy.value === 'quotation_date') {
          valueA = new Date(a.quotation_date).getTime();
          valueB = new Date(b.quotation_date).getTime();
        } else if (sortBy.value === 'validity_date') {
          valueA = new Date(a.validity_date).getTime();
          valueB = new Date(b.validity_date).getTime();
        }

        const direction = sortDirection.value === 'asc' ? 1 : -1;

        if (valueA < valueB) return -1 * direction;
        if (valueA > valueB) return 1 * direction;
        return 0;
      });
    };

    // Helper functions to get data from quotation
    const getUnitPrice = (quotation, itemId) => {
      if (!quotation.lines) return null;

      const line = quotation.lines.find(l => l.item_id === itemId);
      return line ? line.unit_price : null;
    };

    const getTotalPrice = (quotation, itemId) => {
      if (!quotation.lines) return null;

      const line = quotation.lines.find(l => l.item_id === itemId);
      if (!line) return null;

      return line.unit_price * line.quantity;
    };

    const getDeliveryDate = (quotation, itemId) => {
      if (!quotation.lines) return null;

      const line = quotation.lines.find(l => l.item_id === itemId);
      return line ? line.delivery_date : null;
    };

    // Calculate total price for a quotation
    const calculateTotal = (quotation) => {
      if (!quotation.lines) return 0;

      return quotation.lines.reduce((total, line) => {
        return total + (line.unit_price * line.quantity);
      }, 0);
    };

    // Calculate average delivery days
    const calculateAverageDeliveryDays = (quotation) => {
      if (!quotation.lines || quotation.lines.length === 0) return 'N/A';

      const linesWithDates = quotation.lines.filter(line => line.delivery_date);
      if (linesWithDates.length === 0) return 'N/A';

      const today = new Date();
      const totalDays = linesWithDates.reduce((sum, line) => {
        const deliveryDate = new Date(line.delivery_date);
        const days = Math.ceil((deliveryDate - today) / (1000 * 60 * 60 * 24));
        return sum + (days > 0 ? days : 0);
      }, 0);

      return Math.round(totalDays / linesWithDates.length);
    };

    // Best value highlighting
    const isBestPrice = (quotation, itemId) => {
      const price = getUnitPrice(quotation, itemId);
      if (price === null) return false;

      return filteredQuotations.value.every(q => {
        const otherPrice = getUnitPrice(q, itemId);
        return otherPrice === null || otherPrice === 0 || price <= otherPrice;
      });
    };

    const isBestTotal = (quotation) => {
      const total = calculateTotal(quotation);
      if (total === 0) return false;

      return filteredQuotations.value.every(q => {
        const otherTotal = calculateTotal(q);
        return otherTotal === 0 || total <= otherTotal;
      });
    };

    const isBestDelivery = (quotation) => {
      const avgDays = calculateAverageDeliveryDays(quotation);
      if (avgDays === 'N/A') return false;

      return filteredQuotations.value.every(q => {
        const otherDays = calculateAverageDeliveryDays(q);
        return otherDays === 'N/A' || avgDays <= otherDays;
      });
    };

    const isBestValueVendor = (quotation) => {
      // Simple algorithm: best value if it has lowest total or best delivery and not too expensive
      const isLowestTotal = isBestTotal(quotation);
      const isBestDeliveryTime = isBestDelivery(quotation);

      if (isLowestTotal) return true;

      if (isBestDeliveryTime) {
        const total = calculateTotal(quotation);
        const lowestTotal = Math.min(...filteredQuotations.value
          .filter(q => calculateTotal(q) > 0)
          .map(q => calculateTotal(q)));

        // If within 10% of lowest price but has best delivery
        return total <= lowestTotal * 1.1;
      }

      return false;
    };

    // Actions
    const acceptQuotation = async () => {
      if (!selectedQuotation.value) {
        alert('Please select a quotation first.');
        return;
      }

      if (selectedQuotation.value.status !== 'received') {
        alert('Only quotations with "Received" status can be accepted.');
        return;
      }

      if (!confirm(`Are you sure you want to accept the quotation from ${selectedQuotation.value.vendor.name}?`)) {
        return;
      }

      updating.value = true;

      try {
        const response = await axios.patch(`/api/vendor-quotations/${selectedQuotation.value.quotation_id}/status`, {
          status: 'accepted'
        });

        if (response.data.status === 'success') {
          alert('Quotation accepted successfully!');

          // Update the local state
          const index = quotations.value.findIndex(q => q.quotation_id === selectedQuotation.value.quotation_id);
          if (index !== -1) {
            quotations.value[index].status = 'accepted';
          }

          // Offer to create PO
          if (confirm('Would you like to create a Purchase Order from this quotation?')) {
            router.push(`/purchasing/quotations/${selectedQuotation.value.quotation_id}/create-po`);
          }
        } else {
          throw new Error('Failed to accept quotation');
        }
      } catch (err) {
        console.error('Error accepting quotation:', err);
        alert(`Failed to accept quotation. ${err.response?.data?.message || 'Please try again.'}`);
      } finally {
        updating.value = false;
      }
    };

    const goBack = () => {
      router.push(`/purchasing/rfqs/${rfqId.value}`);
    };

    const printMatrix = () => {
      window.print();
    };

    // Format helpers
    const formatDate = (dateString) => {
      if (!dateString) return 'N/A';
      const date = new Date(dateString);
      return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      });
    };

    const formatCurrency = (amount) => {
      if (amount === null || amount === undefined || isNaN(amount)) return 'N/A';
      return `$${Number(amount).toFixed(2)}`;
    };

    const formatStatus = (status) => {
      if (!status) return 'Unknown';
      return status.charAt(0).toUpperCase() + status.slice(1);
    };

    onMounted(() => {
      fetchData();
    });

    return {
      rfq,
      quotations,
      filteredQuotations,
      loading,
      error,
      selectedQuotationId,
      selectedQuotation,
      updating,
      sortBy,
      sortDirection,
      statusFilter,
      itemsList,
      getUnitPrice,
      getTotalPrice,
      getDeliveryDate,
      calculateTotal,
      calculateAverageDeliveryDays,
      isBestPrice,
      isBestTotal,
      isBestDelivery,
      isBestValueVendor,
      sortQuotations,
      acceptQuotation,
      goBack,
      printMatrix,
      formatDate,
      formatCurrency,
      formatStatus
    };
  }
};
</script>

<style scoped>
.comparison-matrix {
  max-width: 1400px;
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

.info-card {
  background-color: #f8f9fa;
  border-radius: 8px;
  padding: 1.25rem;
}

.info-card .card-title {
  color: #495057;
  font-weight: 600;
  border-bottom: 1px solid #dee2e6;
  padding-bottom: 0.75rem;
  margin-bottom: 1rem;
}

.info-item {
  margin-bottom: 0.5rem;
  display: flex;
  flex-wrap: wrap;
}

.info-label {
  font-weight: 500;
  color: #6c757d;
  min-width: 100px;
  margin-right: 0.5rem;
}

.info-value {
  flex: 1;
}

.controls-section {
  margin-bottom: 1.5rem;
}

.comparison-summary {
  border: 1px solid #dee2e6;
  border-radius: 8px;
  height: 100%;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.comparison-summary h6 {
  font-weight: 600;
  color: #495057;
}

.badge {
  font-size: 0.8rem;
  padding: 5px 8px;
  border-radius: 4px;
}

.badge-info {
  background-color: #17a2b8;
  color: white;
}

.badge-success {
  background-color: #28a745;
  color: white;
}

.badge-danger {
  background-color: #dc3545;
  color: white;
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
  vertical-align: top;
}

.table tbody tr:hover {
  background-color: rgba(0, 0, 0, 0.03);
}

.vendor-header {
  padding: 0.5rem 0;
}

.vendor-status {
  margin: 0.5rem 0;
}

.vendor-date {
  font-size: 0.85rem;
  color: #6c757d;
  margin-bottom: 0.5rem;
}

.vendor-actions {
  margin-top: 0.5rem;
}

.price-info {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}

.unit-price, .total-price {
  font-size: 0.95rem;
}

.delivery-date {
  font-size: 0.85rem;
  color: #6c757d;
}

.totals-row {
  background-color: #f8f9fa;
}

.best-price {
  background-color: rgba(25, 135, 84, 0.1);
  border-left: 3px solid #198754;
}

.best-total {
  background-color: rgba(13, 110, 253, 0.1);
  border-left: 3px solid #0d6efd;
}

.best-delivery {
  background-color: rgba(255, 193, 7, 0.1);
  border-left: 3px solid #ffc107;
}

.best-value {
  background-color: rgba(111, 66, 193, 0.1);
  border-left: 3px solid #6f42c1;
}

.best-recommendation {
  background-color: rgba(111, 66, 193, 0.15);
  font-weight: bold;
}

.recommendation-tag {
  display: inline-block;
  background-color: #6f42c1;
  color: white;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.875rem;
}

.recommendation-tag.secondary {
  background-color: #6c757d;
}

.legend {
  display: flex;
  gap: 1.5rem;
  flex-wrap: wrap;
  margin-top: 1rem;
  padding: 1rem;
  background-color: #f8f9fa;
  border-radius: 8px;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.legend-color {
  width: 20px;
  height: 20px;
  border-radius: 4px;
}

.legend-text {
  font-size: 0.875rem;
  color: #495057;
}

.best-price-bg {
  background-color: rgba(25, 135, 84, 0.1);
  border: 1px solid rgba(25, 135, 84, 0.5);
}

.best-total-bg {
  background-color: rgba(13, 110, 253, 0.1);
  border: 1px solid rgba(13, 110, 253, 0.5);
}

.best-delivery-bg {
  background-color: rgba(255, 193, 7, 0.1);
  border: 1px solid rgba(255, 193, 7, 0.5);
}

.best-value-bg {
  background-color: rgba(111, 66, 193, 0.1);
  border: 1px solid rgba(111, 66, 193, 0.5);
}

.form-check {
  margin-bottom: 0.5rem;
}

.form-check-input {
  margin-right: 0.5rem;
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

.btn-sm {
  padding: 0.25rem 0.5rem;
  font-size: 0.875rem;
  border-radius: 0.2rem;
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

.btn-secondary {
  color: #fff;
  background-color: #6c757d;
  border-color: #6c757d;
}

.btn-success {
  color: #fff;
  background-color: #28a745;
  border-color: #28a745;
}

.btn-info {
  color: #fff;
  background-color: #17a2b8;
  border-color: #17a2b8;
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

.alert-warning {
  color: #856404;
  background-color: #fff3cd;
  border-color: #ffeeba;
}

.alert-info {
  color: #0c5460;
  background-color: #d1ecf1;
  border-color: #bee5eb;
}

/* Responsive adjustments */
@media (max-width: 991.98px) {
  .card-body {
    padding: 1rem;
  }

  .table {
    width: max-content;
  }

  .comparison-summary {
    margin-top: 1rem;
  }
}

/* Print styles */
@media print {
  .comparison-matrix {
    max-width: 100%;
    margin: 0;
  }

  .card {
    box-shadow: none;
    border: 1px solid #dee2e6;
  }

  .btn-toolbar,
  .filters,
  .form-check-input {
    display: none;
  }

  .table th, .table td {
    padding: 0.5rem;
  }

  .table {
    page-break-inside: auto;
  }

  .table tr {
    page-break-inside: avoid;
    page-break-after: auto;
  }

  .table thead {
    display: table-header-group;
  }

  .table tfoot {
    display: table-footer-group;
  }
}
</style>
