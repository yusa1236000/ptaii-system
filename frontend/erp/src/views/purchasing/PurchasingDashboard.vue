<!-- src/views/purchasing/PurchasingDashboard.vue -->
<template>
    <div class="purchasing-dashboard">
      <div class="page-header">
        <h1 class="text-2xl font-semibold mb-6">Purchasing Dashboard</h1>
      </div>

      <div class="stats-overview grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="stat-card bg-white p-4 rounded-lg shadow">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-gray-500 text-sm">Open Purchase Orders</h3>
              <p class="text-2xl font-semibold text-gray-800">{{ stats.openPOs }}</p>
            </div>
            <div class="bg-blue-100 p-3 rounded-full">
              <i class="fas fa-clipboard-list text-blue-500"></i>
            </div>
          </div>
          <p class="text-sm text-gray-500 mt-2">
            <span :class="stats.openPOsChange > 0 ? 'text-green-500' : 'text-red-500'">
              <i :class="stats.openPOsChange > 0 ? 'fas fa-arrow-up' : 'fas fa-arrow-down'"></i>
              {{ Math.abs(stats.openPOsChange) }}%
            </span>
            from last month
          </p>
        </div>

        <div class="stat-card bg-white p-4 rounded-lg shadow">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-gray-500 text-sm">Total Spend</h3>
              <p class="text-2xl font-semibold text-gray-800">${{ formatNumber(stats.totalSpend) }}</p>
            </div>
            <div class="bg-green-100 p-3 rounded-full">
              <i class="fas fa-dollar-sign text-green-500"></i>
            </div>
          </div>
          <p class="text-sm text-gray-500 mt-2">
            <span :class="stats.spendChange > 0 ? 'text-green-500' : 'text-red-500'">
              <i :class="stats.spendChange > 0 ? 'fas fa-arrow-up' : 'fas fa-arrow-down'"></i>
              {{ Math.abs(stats.spendChange) }}%
            </span>
            from last month
          </p>
        </div>

        <div class="stat-card bg-white p-4 rounded-lg shadow">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-gray-500 text-sm">Pending Receipts</h3>
              <p class="text-2xl font-semibold text-gray-800">{{ stats.pendingReceipts }}</p>
            </div>
            <div class="bg-yellow-100 p-3 rounded-full">
              <i class="fas fa-truck-loading text-yellow-500"></i>
            </div>
          </div>
          <p class="text-sm text-gray-500 mt-2">
            <span :class="stats.receiptsChange > 0 ? 'text-red-500' : 'text-green-500'">
              <i :class="stats.receiptsChange > 0 ? 'fas fa-arrow-up' : 'fas fa-arrow-down'"></i>
              {{ Math.abs(stats.receiptsChange) }}%
            </span>
            from last month
          </p>
        </div>

        <div class="stat-card bg-white p-4 rounded-lg shadow">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-gray-500 text-sm">Vendor Performance</h3>
              <p class="text-2xl font-semibold text-gray-800">{{ stats.avgVendorRating }}/5</p>
            </div>
            <div class="bg-purple-100 p-3 rounded-full">
              <i class="fas fa-star text-purple-500"></i>
            </div>
          </div>
          <p class="text-sm text-gray-500 mt-2">
            <span :class="stats.ratingChange > 0 ? 'text-green-500' : 'text-red-500'">
              <i :class="stats.ratingChange > 0 ? 'fas fa-arrow-up' : 'fas fa-arrow-down'"></i>
              {{ Math.abs(stats.ratingChange) }}%
            </span>
            from last month
          </p>
        </div>
      </div>

      <div class="dashboard-charts grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="chart-card bg-white p-4 rounded-lg shadow">
          <h2 class="text-lg font-semibold mb-4">Monthly Spend Trend</h2>
          <div class="chart-container" style="height: 300px;">
            <canvas ref="spendTrendChart"></canvas>
          </div>
        </div>

        <div class="chart-card bg-white p-4 rounded-lg shadow">
          <h2 class="text-lg font-semibold mb-4">Purchase Orders by Status</h2>
          <div class="chart-container" style="height: 300px;">
            <canvas ref="poStatusChart"></canvas>
          </div>
        </div>
      </div>

      <div class="dashboard-tables grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="table-card bg-white p-4 rounded-lg shadow">
          <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold">Recent Purchase Orders</h2>
            <router-link to="/purchasing/orders" class="text-blue-500 hover:underline text-sm">
              View All <i class="fas fa-chevron-right ml-1"></i>
            </router-link>
          </div>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PO Number</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendor</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="po in recentPOs" :key="po.po_id">
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-blue-600">
                    <router-link :to="`/purchasing/orders/${po.po_id}`">
                      {{ po.po_number }}
                    </router-link>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">{{ po.vendor.name }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDate(po.po_date) }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">${{ formatNumber(po.total_amount) }}</td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                      :class="getStatusClass(po.status)">
                      {{ po.status }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="table-card bg-white p-4 rounded-lg shadow">
          <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold">Top Vendors</h2>
            <router-link to="/purchasing/vendors" class="text-blue-500 hover:underline text-sm">
              View All <i class="fas fa-chevron-right ml-1"></i>
            </router-link>
          </div>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendor</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Spend</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Orders</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rating</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="vendor in topVendors" :key="vendor.vendor_id">
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-blue-600">
                    <router-link :to="`/purchasing/vendors/${vendor.vendor_id}`">
                      {{ vendor.name }}
                    </router-link>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">${{ formatNumber(vendor.spend) }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ vendor.orders }}</td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <span class="text-sm text-gray-800 mr-2">{{ vendor.rating }}</span>
                      <div class="flex text-yellow-400">
                        <i v-for="n in 5" :key="n" :class="n <= Math.floor(vendor.rating) ? 'fas fa-star' : (n <= vendor.rating ? 'fas fa-star-half-alt' : 'far fa-star')"></i>
                      </div>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </template>

  <script>
  import { ref, onMounted } from 'vue';
  import axios from 'axios';
  import Chart from 'chart.js/auto';

  export default {
    name: 'PurchasingDashboard',
    setup() {
      const spendTrendChart = ref(null);
      const poStatusChart = ref(null);
      const stats = ref({
        openPOs: 0,
        openPOsChange: 0,
        totalSpend: 0,
        spendChange: 0,
        pendingReceipts: 0,
        receiptsChange: 0,
        avgVendorRating: 0,
        ratingChange: 0
      });
      const recentPOs = ref([]);
      const topVendors = ref([]);

      const loadDashboardData = async () => {
        try {
          // Assuming backend provides this endpoint for dashboard data
           await axios.get('/api/purchasing/dashboard');

          // In a real implementation, this would use actual data from the response
          // For now, we'll simulate with sample data
          stats.value = {
            openPOs: 24,
            openPOsChange: 10,
            totalSpend: 157850.75,
            spendChange: -5.2,
            pendingReceipts: 15,
            receiptsChange: 8.7,
            avgVendorRating: 4.2,
            ratingChange: 1.5
          };

          // Load Purchase Orders
          const poResponse = await axios.get('/api/purchase-orders', {
            params: {
              per_page: 5,
              sort_field: 'po_date',
              sort_direction: 'desc'
            }
          });
          if (poResponse.data && poResponse.data.data && poResponse.data.data.data) {
            recentPOs.value = poResponse.data.data.data;
          }

          // For top vendors, we would need a dedicated endpoint
          // This is simulated for now
          topVendors.value = [
            { vendor_id: 1, name: 'Acme Supplies', spend: 45678.90, orders: 12, rating: 4.8 },
            { vendor_id: 2, name: 'Tech Parts Inc', spend: 32456.78, orders: 8, rating: 4.5 },
            { vendor_id: 3, name: 'Global Materials', spend: 28975.45, orders: 15, rating: 4.2 },
            { vendor_id: 4, name: 'Quality Components', spend: 18765.30, orders: 10, rating: 3.9 },
            { vendor_id: 5, name: 'Sunrise Distributors', spend: 15432.80, orders: 7, rating: 4.0 }
          ];

          setupCharts();
        } catch (error) {
          console.error('Error loading dashboard data:', error);
        }
      };

      const setupCharts = () => {
        // Monthly Spend Trend Chart
        if (spendTrendChart.value) {
          const spendCtx = spendTrendChart.value.getContext('2d');
          new Chart(spendCtx, {
            type: 'line',
            data: {
              labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
              datasets: [{
                label: 'Monthly Spend ($)',
                data: [32500, 28700, 31200, 26800, 29500, 32100, 33400, 29800, 35600, 38200, 36500, 34900],
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37, 99, 235, 0.1)',
                tension: 0.3,
                fill: true
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                legend: {
                  display: false
                }
              },
              scales: {
                y: {
                  beginAtZero: true,
                  ticks: {
                    callback: function(value) {
                      return '$' + value.toLocaleString();
                    }
                  }
                }
              }
            }
          });
        }

        // PO Status Chart
        if (poStatusChart.value) {
          const poCtx = poStatusChart.value.getContext('2d');
          new Chart(poCtx, {
            type: 'doughnut',
            data: {
              labels: ['Draft', 'Submitted', 'Approved', 'Sent', 'Partial', 'Received', 'Completed', 'Canceled'],
              datasets: [{
                data: [8, 12, 15, 10, 7, 14, 25, 5],
                backgroundColor: [
                  '#f1f5f9', // Draft - light gray
                  '#cbd5e1', // Submitted - gray
                  '#2563eb', // Approved - blue
                  '#3b82f6', // Sent - lighter blue
                  '#f59e0b', // Partial - amber
                  '#10b981', // Received - green
                  '#059669', // Completed - darker green
                  '#ef4444'  // Canceled - red
                ],
                borderWidth: 1
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                legend: {
                  position: 'right'
                }
              }
            }
          });
        }
      };

      const formatNumber = (num) => {
        return num ? num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '0.00';
      };

      const formatDate = (dateString) => {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
          year: 'numeric',
          month: 'short',
          day: 'numeric'
        });
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

      onMounted(() => {
        loadDashboardData();
      });

      return {
        spendTrendChart,
        poStatusChart,
        stats,
        recentPOs,
        topVendors,
        formatNumber,
        formatDate,
        getStatusClass
      };
    }
  };
  </script>

  <style scoped>
  .purchasing-dashboard {
    min-height: calc(100vh - 150px);
  }
  </style>
