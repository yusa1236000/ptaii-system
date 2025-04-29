<!-- src/views/purchasing/POStatusSummary.vue -->
<template>
    <div class="po-status-summary">
      <div class="page-header mb-6">
        <h1 class="text-2xl font-semibold">Purchase Order Status Summary</h1>
        <p class="text-gray-500">Track and manage purchase orders by status</p>
      </div>

      <div class="filters bg-white p-4 rounded-lg shadow mb-6">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
          <div class="filter-group">
            <label class="block text-sm font-medium text-gray-700 mb-1">Date Range</label>
            <select v-model="filters.dateRange" class="w-full border border-gray-300 rounded-md shadow-sm px-3 py-2">
              <option value="30">Last 30 Days</option>
              <option value="90">Last 90 Days</option>
              <option value="180">Last 6 Months</option>
              <option value="365">Last Year</option>
              <option value="custom">Custom Range</option>
            </select>
          </div>

          <div class="filter-group" v-if="filters.dateRange === 'custom'">
            <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
            <input type="date" v-model="filters.startDate" class="w-full border border-gray-300 rounded-md shadow-sm px-3 py-2">
          </div>

          <div class="filter-group" v-if="filters.dateRange === 'custom'">
            <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
            <input type="date" v-model="filters.endDate" class="w-full border border-gray-300 rounded-md shadow-sm px-3 py-2">
          </div>

          <div class="filter-group">
            <label class="block text-sm font-medium text-gray-700 mb-1">Vendor</label>
            <select v-model="filters.vendorId" class="w-full border border-gray-300 rounded-md shadow-sm px-3 py-2">
              <option value="">All Vendors</option>
              <option v-for="vendor in vendors" :key="vendor.vendor_id" :value="vendor.vendor_id">
                {{ vendor.name }}
              </option>
            </select>
          </div>

          <div class="filter-group">
            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select v-model="filters.status" class="w-full border border-gray-300 rounded-md shadow-sm px-3 py-2">
              <option value="">All Statuses</option>
              <option value="draft">Draft</option>
              <option value="submitted">Submitted</option>
              <option value="approved">Approved</option>
              <option value="sent">Sent</option>
              <option value="partial">Partial</option>
              <option value="received">Received</option>
              <option value="completed">Completed</option>
              <option value="canceled">Canceled</option>
            </select>
          </div>

          <div class="filter-group md:col-span-2 flex items-end">
            <button @click="applyFilters" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded">
              Apply Filters
            </button>
            <button @click="resetFilters" class="ml-2 bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium px-4 py-2 rounded">
              Reset
            </button>
          </div>
        </div>
      </div>

      <div class="status-cards grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="card p-4 bg-white rounded-lg shadow text-center relative overflow-hidden">
          <div class="status-indicator absolute top-0 left-0 w-full h-1 bg-gray-300"></div>
          <h3 class="text-lg font-semibold text-gray-700">Total POs</h3>
          <p class="text-3xl font-bold text-gray-800 my-2">{{ summary.totalPOs }}</p>
          <p class="text-sm text-gray-500">Value: ${{ formatNumber(summary.totalValue) }}</p>
        </div>

        <div class="card p-4 bg-white rounded-lg shadow text-center relative overflow-hidden">
          <div class="status-indicator absolute top-0 left-0 w-full h-1 bg-blue-500"></div>
          <h3 class="text-lg font-semibold text-gray-700">Open POs</h3>
          <p class="text-3xl font-bold text-blue-600 my-2">{{ summary.openPOs }}</p>
          <p class="text-sm text-gray-500">Value: ${{ formatNumber(summary.openValue) }}</p>
        </div>

        <div class="card p-4 bg-white rounded-lg shadow text-center relative overflow-hidden">
          <div class="status-indicator absolute top-0 left-0 w-full h-1 bg-yellow-500"></div>
          <h3 class="text-lg font-semibold text-gray-700">Pending Receipt</h3>
          <p class="text-3xl font-bold text-yellow-600 my-2">{{ summary.pendingPOs }}</p>
          <p class="text-sm text-gray-500">Value: ${{ formatNumber(summary.pendingValue) }}</p>
        </div>

        <div class="card p-4 bg-white rounded-lg shadow text-center relative overflow-hidden">
          <div class="status-indicator absolute top-0 left-0 w-full h-1 bg-green-500"></div>
          <h3 class="text-lg font-semibold text-gray-700">Completed</h3>
          <p class="text-3xl font-bold text-green-600 my-2">{{ summary.completedPOs }}</p>
          <p class="text-sm text-gray-500">Value: ${{ formatNumber(summary.completedValue) }}</p>
        </div>
      </div>

      <div class="charts-grid grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="chart-card bg-white p-4 rounded-lg shadow">
          <h3 class="text-lg font-semibold mb-4">PO Status Distribution</h3>
          <div class="chart-container" style="height: 300px;">
            <canvas ref="statusDistributionChart"></canvas>
          </div>
        </div>

        <div class="chart-card bg-white p-4 rounded-lg shadow">
          <h3 class="text-lg font-semibold mb-4">PO Value by Status</h3>
          <div class="chart-container" style="height: 300px;">
            <canvas ref="valueByStatusChart"></canvas>
          </div>
        </div>
      </div>

      <div class="status-details">
        <div class="tab-header bg-white p-4 rounded-t-lg shadow flex border-b">
          <button
            v-for="tab in statusTabs"
            :key="tab.value"
            @click="currentTab = tab.value"
            class="tab-button py-2 px-4 font-medium rounded-t-lg mr-2"
            :class="currentTab === tab.value ? 'bg-blue-100 text-blue-700 border-b-2 border-blue-700' : 'text-gray-600 hover:bg-gray-100'"
          >
            {{ tab.label }}
            <span class="ml-1 px-2 py-0.5 text-xs rounded-full"
              :class="getStatusBadgeClass(tab.value)">
              {{ getPOCountByStatus(tab.value) }}
            </span>
          </button>
        </div>

        <div class="tab-content bg-white p-4 rounded-b-lg shadow">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PO Number</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendor</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expected Delivery</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="po in filteredPOs" :key="po.po_id">
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-blue-600">
                    <router-link :to="`/purchasing/orders/${po.po_id}`">
                      {{ po.po_number }}
                    </router-link>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">{{ po.vendor ? po.vendor.name : 'N/A' }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDate(po.po_date) }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">${{ formatNumber(po.total_amount) }}</td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                      :class="getStatusClass(po.status)">
                      {{ po.status }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ formatDate(po.expected_delivery) }}
                    <span v-if="isDeliveryLate(po)" class="text-xs text-red-600 ml-2">
                      <i class="fas fa-exclamation-circle"></i> Late
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm">
                    <div class="flex space-x-2">
                      <router-link :to="`/purchasing/orders/${po.po_id}/track`" class="text-blue-600 hover:text-blue-800">
                        <i class="fas fa-chart-line"></i>
                      </router-link>
                      <router-link :to="`/purchasing/orders/${po.po_id}/edit`" class="text-gray-600 hover:text-gray-800">
                        <i class="fas fa-edit"></i>
                      </router-link>
                      <button v-if="canUpdateStatus(po.status)" @click="openStatusUpdateModal(po)" class="text-purple-600 hover:text-purple-800">
                        <i class="fas fa-sync-alt"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="mt-4 flex justify-between items-center">
            <div class="text-sm text-gray-700">
              Showing <span class="font-medium">{{ paginationInfo.from }}</span> to <span class="font-medium">{{ paginationInfo.to }}</span> of <span class="font-medium">{{ paginationInfo.total }}</span> purchase orders
            </div>
            <pagination-component
              :current-page="paginationInfo.current_page"
              :last-page="paginationInfo.last_page"
              @page-changed="changePage">
            </pagination-component>
          </div>
        </div>
      </div>

      <!-- Status Update Modal (would be implemented with a modal component) -->
      <div v-if="showStatusModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-lg p-6 w-96">
          <h3 class="text-lg font-semibold mb-4">Update PO Status</h3>
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Current Status</label>
            <div class="px-3 py-2 border border-gray-300 rounded-md bg-gray-50">
              <span class="px-2 py-1 text-xs rounded-full" :class="getStatusClass(selectedPO.status)">
                {{ selectedPO.status }}
              </span>
            </div>
          </div>
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">New Status</label>
            <select v-model="newStatus" class="w-full border border-gray-300 rounded-md shadow-sm px-3 py-2">
              <option v-for="status in availableStatuses" :key="status" :value="status">{{ status }}</option>
            </select>
          </div>
          <div class="flex justify-end space-x-2">
            <button @click="closeStatusModal" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md">Cancel</button>
            <button @click="updatePOStatus" class="px-4 py-2 bg-blue-600 text-white rounded-md">Update</button>
          </div>
        </div>
      </div>
    </div>
  </template>

  <script>
  import { ref, onMounted, reactive, computed } from 'vue';
  import axios from 'axios';
  import Chart from 'chart.js/auto';

  export default {
    name: 'POStatusSummary',
    setup() {
      const statusDistributionChart = ref(null);
      const valueByStatusChart = ref(null);
      const vendors = ref([]);
      const purchaseOrders = ref([]);
      const currentTab = ref('all');
      const showStatusModal = ref(false);
      const selectedPO = ref({});
      const newStatus = ref('');

      const filters = reactive({
        dateRange: '180',
        startDate: '',
        endDate: '',
        vendorId: '',
        status: ''
      });

      const summary = reactive({
        totalPOs: 0,
        totalValue: 0,
        openPOs: 0,
        openValue: 0,
        pendingPOs: 0,
        pendingValue: 0,
        completedPOs: 0,
        completedValue: 0
      });

      const paginationInfo = reactive({
        current_page: 1,
        from: 1,
        to: 0,
        total: 0,
        last_page: 1
      });

      const statusTabs = [
        { label: 'All', value: 'all' },
        { label: 'Draft', value: 'draft' },
        { label: 'Submitted', value: 'submitted' },
        { label: 'Approved', value: 'approved' },
        { label: 'Sent', value: 'sent' },
        { label: 'Partial', value: 'partial' },
        { label: 'Received', value: 'received' },
        { label: 'Completed', value: 'completed' },
        { label: 'Canceled', value: 'canceled' }
      ];

      const filteredPOs = computed(() => {
        if (currentTab.value === 'all') {
          return purchaseOrders.value;
        }
        return purchaseOrders.value.filter(po => po.status.toLowerCase() === currentTab.value);
      });

      const availableStatuses = computed(() => {
        const currentStatus = selectedPO.value.status;
        if (!currentStatus) return [];

        // Define valid status transitions based on your business rules
        const validTransitions = {
          'draft': ['submitted', 'canceled'],
          'submitted': ['approved', 'canceled'],
          'approved': ['sent', 'canceled'],
          'sent': ['partial', 'received', 'canceled'],
          'partial': ['completed', 'canceled'],
          'received': ['completed', 'canceled'],
          'completed': ['canceled'],
          'canceled': []
        };

        return validTransitions[currentStatus.toLowerCase()] || [];
      });

      const loadVendors = async () => {
        try {
          const response = await axios.get('/api/vendors');
          if (response.data && response.data.data) {
            vendors.value = response.data.data;
          }
        } catch (error) {
          console.error('Error loading vendors:', error);
        }
      };

      const loadPurchaseOrders = async () => {
        try {
          // Construct query parameters from filters
          const params = {
            page: paginationInfo.current_page,
            per_page: 10
          };

          if (filters.vendorId) params.vendor_id = filters.vendorId;
          if (filters.status) params.status = filters.status;
          if (filters.startDate) params.date_from = filters.startDate;
          if (filters.endDate) params.date_to = filters.endDate;

          // Make API request
          const response = await axios.get('/api/purchase-orders', { params });

          if (response.data && response.data.data) {
            purchaseOrders.value = response.data.data.data || [];

            // Update pagination info
            paginationInfo.current_page = response.data.data.current_page;
            paginationInfo.from = response.data.data.from || 1;
            paginationInfo.to = response.data.data.to || 0;
            paginationInfo.total = response.data.data.total || 0;
            paginationInfo.last_page = response.data.data.last_page || 1;

            // Update summary data
            updateSummary();
            renderCharts();
          }
        } catch (error) {
          console.error('Error loading purchase orders:', error);
        }
      };

      const updateSummary = () => {
        // Reset summary values
        summary.totalPOs = purchaseOrders.value.length;
        summary.totalValue = purchaseOrders.value.reduce((total, po) => total + (po.total_amount || 0), 0);

        // Count open POs (draft, submitted, approved, sent)
        const openStatuses = ['draft', 'submitted', 'approved', 'sent'];
        const openPOs = purchaseOrders.value.filter(po => openStatuses.includes(po.status.toLowerCase()));
        summary.openPOs = openPOs.length;
        summary.openValue = openPOs.reduce((total, po) => total + (po.total_amount || 0), 0);

        // Count pending receipt POs (sent, partial)
        const pendingStatuses = ['sent', 'partial'];
        const pendingPOs = purchaseOrders.value.filter(po => pendingStatuses.includes(po.status.toLowerCase()));
        summary.pendingPOs = pendingPOs.length;
        summary.pendingValue = pendingPOs.reduce((total, po) => total + (po.total_amount || 0), 0);

        // Count completed POs
        const completedPOs = purchaseOrders.value.filter(po => po.status.toLowerCase() === 'completed');
        summary.completedPOs = completedPOs.length;
        summary.completedValue = completedPOs.reduce((total, po) => total + (po.total_amount || 0), 0);
      };

      const renderCharts = () => {
        // Status Distribution Chart
        if (statusDistributionChart.value) {
          // Count POs by status
          const statusCounts = {};
          statusTabs.forEach(tab => {
            if (tab.value !== 'all') {
              statusCounts[tab.value] = purchaseOrders.value.filter(
                po => po.status.toLowerCase() === tab.value
              ).length;
            }
          });

          // Prepare data for chart
          const labels = Object.keys(statusCounts).map(status =>
            status.charAt(0).toUpperCase() + status.slice(1)
          );
          const data = Object.values(statusCounts);

          // Define colors for each status
          const colors = {
            'draft': '#f1f5f9',       // Light gray
            'submitted': '#cbd5e1',   // Gray
            'approved': '#2563eb',    // Blue
            'sent': '#3b82f6',        // Lighter blue
            'partial': '#f59e0b',     // Amber
            'received': '#10b981',    // Green
            'completed': '#059669',   // Darker green
            'canceled': '#ef4444'     // Red
          };

          const backgroundColor = Object.keys(statusCounts).map(status => colors[status]);

          // Create chart
          new Chart(statusDistributionChart.value.getContext('2d'), {
            type: 'doughnut',
            data: {
              labels: labels,
              datasets: [{
                data: data,
                backgroundColor: backgroundColor
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                legend: {
                  position: 'right',
                  labels: {
                    padding: 20
                  }
                }
              }
            }
          });
        }

        // Value by Status Chart
        if (valueByStatusChart.value) {
          // Calculate total value by status
          const statusValues = {};
          statusTabs.forEach(tab => {
            if (tab.value !== 'all') {
              statusValues[tab.value] = purchaseOrders.value
                .filter(po => po.status.toLowerCase() === tab.value)
                .reduce((total, po) => total + (po.total_amount || 0), 0);
            }
          });

          // Prepare data for chart
          const labels = Object.keys(statusValues).map(status =>
            status.charAt(0).toUpperCase() + status.slice(1)
          );
          const data = Object.values(statusValues);

          // Define colors for each status (same as above)
          const colors = {
            'draft': '#f1f5f9',
            'submitted': '#cbd5e1',
            'approved': '#2563eb',
            'sent': '#3b82f6',
            'partial': '#f59e0b',
            'received': '#10b981',
            'completed': '#059669',
            'canceled': '#ef4444'
          };

          const backgroundColor = Object.keys(statusValues).map(status => colors[status]);

          // Create chart
          new Chart(valueByStatusChart.value.getContext('2d'), {
            type: 'bar',
            data: {
              labels: labels,
              datasets: [{
                label: 'Total Value',
                data: data,
                backgroundColor: backgroundColor
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                legend: {
                  display: false
                },
                tooltip: {
                  callbacks: {
                    label: function(tooltipItem) {
                      return `${tooltipItem.raw.toLocaleString()}`;
                    }
                  }
                }
              },
              scales: {
                y: {
                  beginAtZero: true,
ticks: {
  callback: function(value) {
    return value.toLocaleString();
  }
}
                }
              }
            }
          });
        }
      };

      const applyFilters = () => {
        paginationInfo.current_page = 1;
        loadPurchaseOrders();
      };

      const resetFilters = () => {
        filters.dateRange = '180';
        filters.startDate = '';
        filters.endDate = '';
        filters.vendorId = '';
        filters.status = '';
        paginationInfo.current_page = 1;
        loadPurchaseOrders();
      };

      const changePage = (page) => {
        paginationInfo.current_page = page;
        loadPurchaseOrders();
      };

      const getPOCountByStatus = (status) => {
        if (status === 'all') {
          return purchaseOrders.value.length;
        }
        return purchaseOrders.value.filter(po => po.status.toLowerCase() === status).length;
      };

      const getStatusClass = (status) => {
        const statusClasses = {
          'draft': 'bg-gray-100 text-gray-800',
          'submitted': 'bg-blue-100 text-blue-800',
          'approved': 'bg-cyan-100 text-cyan-800',
          'sent': 'bg-indigo-100 text-indigo-800',
          'partial': 'bg-amber-100 text-amber-800',
          'received': 'bg-green-100 text-green-800',
          'completed': 'bg-emerald-100 text-emerald-800',
          'canceled': 'bg-red-100 text-red-800'
        };

        return statusClasses[status.toLowerCase()] || 'bg-gray-100 text-gray-800';
      };

      const getStatusBadgeClass = (status) => {
        if (status === 'all') return 'bg-gray-200 text-gray-800';

        const statusClasses = {
          'draft': 'bg-gray-200 text-gray-800',
          'submitted': 'bg-blue-200 text-blue-800',
          'approved': 'bg-cyan-200 text-cyan-800',
          'sent': 'bg-indigo-200 text-indigo-800',
          'partial': 'bg-amber-200 text-amber-800',
          'received': 'bg-green-200 text-green-800',
          'completed': 'bg-emerald-200 text-emerald-800',
          'canceled': 'bg-red-200 text-red-800'
        };

        return statusClasses[status.toLowerCase()] || 'bg-gray-200 text-gray-800';
      };

      const formatNumber = (num) => {
        return num ? num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '0.00';
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

      const isDeliveryLate = (po) => {
        if (!po.expected_delivery) return false;

        const today = new Date();
        const expectedDate = new Date(po.expected_delivery);

        return expectedDate < today && ['sent', 'partial'].includes(po.status.toLowerCase());
      };

      const canUpdateStatus = (status) => {
        // Only allow status updates for certain statuses
        const updatableStatuses = ['draft', 'submitted', 'approved', 'sent', 'partial', 'received'];
        return updatableStatuses.includes(status.toLowerCase());
      };

      const openStatusUpdateModal = (po) => {
        selectedPO.value = po;
        newStatus.value = availableStatuses.value[0] || '';
        showStatusModal.value = true;
      };

      const closeStatusModal = () => {
        showStatusModal.value = false;
        selectedPO.value = {};
        newStatus.value = '';
      };

      const updatePOStatus = async () => {
        try {
          // Call the API to update the status
          await axios.patch(`/api/purchase-orders/${selectedPO.value.po_id}/status`, {
            status: newStatus.value
          });

          // Update the local PO status
          const index = purchaseOrders.value.findIndex(po => po.po_id === selectedPO.value.po_id);
          if (index !== -1) {
            purchaseOrders.value[index].status = newStatus.value;
          }

          // Update summary and charts
          updateSummary();
          renderCharts();

          // Close the modal
          closeStatusModal();

          // Show success message (would implement with a toast/notification component)
          alert('Status updated successfully');
        } catch (error) {
          console.error('Error updating status:', error);
          alert('Failed to update status: ' + (error.response?.data?.message || error.message));
        }
      };

      onMounted(() => {
        loadVendors();
        loadPurchaseOrders();
      });

      return {
        statusDistributionChart,
        valueByStatusChart,
        vendors,
        purchaseOrders,
        filters,
        summary,
        paginationInfo,
        statusTabs,
        currentTab,
        filteredPOs,
        showStatusModal,
        selectedPO,
        newStatus,
        availableStatuses,
        applyFilters,
        resetFilters,
        changePage,
        getPOCountByStatus,
        getStatusClass,
        getStatusBadgeClass,
        formatNumber,
        formatDate,
        isDeliveryLate,
        canUpdateStatus,
        openStatusUpdateModal,
        closeStatusModal,
        updatePOStatus
      };
    }
  };
  </script>

  <style scoped>
  .po-status-summary {
    min-height: calc(100vh - 150px);
  }

  .tab-button {
    transition: background-color 0.2s;
  }

  .status-indicator {
    height: 4px;
  }
  </style>
