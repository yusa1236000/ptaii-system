<!-- src/views/purchasing/VendorPerformanceReport.vue -->
<template>
    <div class="vendor-performance">
      <div class="page-header mb-6">
        <h1 class="text-2xl font-semibold">Vendor Performance Report</h1>
        <p class="text-gray-500">Track and analyze vendor performance metrics</p>
      </div>

      <div class="filters bg-white p-4 rounded-lg shadow mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
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
            <label class="block text-sm font-medium text-gray-700 mb-1">Performance Category</label>
            <select v-model="filters.category" class="w-full border border-gray-300 rounded-md shadow-sm px-3 py-2">
              <option value="">All Categories</option>
              <option value="quality">Quality</option>
              <option value="delivery">Delivery</option>
              <option value="price">Price</option>
              <option value="communication">Communication</option>
              <option value="overall">Overall</option>
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

      <div class="performance-summary bg-white p-4 rounded-lg shadow mb-6">
        <h2 class="text-lg font-semibold mb-4">Performance Overview</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
          <div class="score-card p-3 border rounded-lg text-center">
            <div class="text-sm font-medium text-gray-500 mb-1">Overall Score</div>
            <div class="rating">
              <div class="text-2xl font-bold" :class="getRatingColorClass(overallMetrics.overall)">{{ overallMetrics.overall.toFixed(1) }}</div>
              <div class="stars flex justify-center text-yellow-400 mt-1">
                <i v-for="n in 5" :key="n" :class="getStarClass(n, overallMetrics.overall)"></i>
              </div>
            </div>
          </div>

          <div class="score-card p-3 border rounded-lg text-center">
            <div class="text-sm font-medium text-gray-500 mb-1">Quality</div>
            <div class="rating">
              <div class="text-2xl font-bold" :class="getRatingColorClass(overallMetrics.quality)">{{ overallMetrics.quality.toFixed(1) }}</div>
              <div class="stars flex justify-center text-yellow-400 mt-1">
                <i v-for="n in 5" :key="n" :class="getStarClass(n, overallMetrics.quality)"></i>
              </div>
            </div>
          </div>

          <div class="score-card p-3 border rounded-lg text-center">
            <div class="text-sm font-medium text-gray-500 mb-1">Delivery</div>
            <div class="rating">
              <div class="text-2xl font-bold" :class="getRatingColorClass(overallMetrics.delivery)">{{ overallMetrics.delivery.toFixed(1) }}</div>
              <div class="stars flex justify-center text-yellow-400 mt-1">
                <i v-for="n in 5" :key="n" :class="getStarClass(n, overallMetrics.delivery)"></i>
              </div>
            </div>
          </div>

          <div class="score-card p-3 border rounded-lg text-center">
            <div class="text-sm font-medium text-gray-500 mb-1">Price</div>
            <div class="rating">
              <div class="text-2xl font-bold" :class="getRatingColorClass(overallMetrics.price)">{{ overallMetrics.price.toFixed(1) }}</div>
              <div class="stars flex justify-center text-yellow-400 mt-1">
                <i v-for="n in 5" :key="n" :class="getStarClass(n, overallMetrics.price)"></i>
              </div>
            </div>
          </div>

          <div class="score-card p-3 border rounded-lg text-center">
            <div class="text-sm font-medium text-gray-500 mb-1">Communication</div>
            <div class="rating">
              <div class="text-2xl font-bold" :class="getRatingColorClass(overallMetrics.communication)">{{ overallMetrics.communication.toFixed(1) }}</div>
              <div class="stars flex justify-center text-yellow-400 mt-1">
                <i v-for="n in 5" :key="n" :class="getStarClass(n, overallMetrics.communication)"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="charts-section grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="chart-card bg-white p-4 rounded-lg shadow">
          <h3 class="text-lg font-semibold mb-4">Performance Trend</h3>
          <div class="chart-container" style="height: 350px;">
            <canvas ref="trendChart"></canvas>
          </div>
        </div>

        <div class="chart-card bg-white p-4 rounded-lg shadow">
          <h3 class="text-lg font-semibold mb-4">Performance Comparison</h3>
          <div class="chart-container" style="height: 350px;">
            <canvas ref="comparisonChart"></canvas>
          </div>
        </div>
      </div>

      <div class="key-metrics grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="metric-card bg-white p-4 rounded-lg shadow">
          <h3 class="text-lg font-semibold mb-3">On-Time Delivery</h3>
          <div class="text-center mb-2">
            <div class="text-3xl font-bold text-blue-600">{{ deliveryMetrics.onTimePercentage }}%</div>
            <div class="text-sm text-gray-500">of orders delivered on time</div>
          </div>
          <div class="flex justify-between text-sm mt-4">
            <div>
              <div class="font-medium">Total Orders</div>
              <div>{{ deliveryMetrics.totalOrders }}</div>
            </div>
            <div>
              <div class="font-medium">On Time</div>
              <div>{{ deliveryMetrics.onTimeOrders }}</div>
            </div>
            <div>
              <div class="font-medium">Late</div>
              <div>{{ deliveryMetrics.lateOrders }}</div>
            </div>
          </div>
        </div>

        <div class="metric-card bg-white p-4 rounded-lg shadow">
          <h3 class="text-lg font-semibold mb-3">Quality Compliance</h3>
          <div class="text-center mb-2">
            <div class="text-3xl font-bold text-green-600">{{ qualityMetrics.acceptedPercentage }}%</div>
            <div class="text-sm text-gray-500">acceptance rate</div>
          </div>
          <div class="flex justify-between text-sm mt-4">
            <div>
              <div class="font-medium">Total Shipments</div>
              <div>{{ qualityMetrics.totalShipments }}</div>
            </div>
            <div>
              <div class="font-medium">Accepted</div>
              <div>{{ qualityMetrics.acceptedShipments }}</div>
            </div>
            <div>
              <div class="font-medium">Rejected</div>
              <div>{{ qualityMetrics.rejectedShipments }}</div>
            </div>
          </div>
        </div>

        <div class="metric-card bg-white p-4 rounded-lg shadow">
          <h3 class="text-lg font-semibold mb-3">Price Competitiveness</h3>
          <div class="text-center mb-2">
            <div class="text-3xl font-bold text-purple-600">{{ priceMetrics.variancePercentage }}%</div>
            <div class="text-sm text-gray-500">{{ priceMetrics.variancePercentage < 0 ? 'below' : 'above' }} market average</div>
          </div>
          <div class="flex justify-between text-sm mt-4">
            <div>
              <div class="font-medium">Avg. Price</div>
              <div>${{ formatNumber(priceMetrics.avgPrice) }}</div>
            </div>
            <div>
              <div class="font-medium">Market Avg.</div>
              <div>${{ formatNumber(priceMetrics.marketAvg) }}</div>
            </div>
            <div>
              <div class="font-medium">Savings</div>
              <div>${{ formatNumber(priceMetrics.estimatedSavings) }}</div>
            </div>
          </div>
        </div>
      </div>

      <div class="vendor-table bg-white p-4 rounded-lg shadow">
        <h3 class="text-lg font-semibold mb-4">Vendor Performance Details</h3>

        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendor</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Overall</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quality</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Delivery</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Communication</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="vendor in vendorPerformance" :key="vendor.vendor_id">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-blue-600">
                  <router-link :to="`/purchasing/vendors/${vendor.vendor_id}`">
                    {{ vendor.name }}
                  </router-link>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <span class="text-sm mr-2" :class="getRatingTextClass(vendor.overall)">{{ vendor.overall.toFixed(1) }}</span>
                    <div class="stars flex text-yellow-400">
                      <i v-for="n in 5" :key="n" :class="getStarClass(n, vendor.overall)"></i>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <span class="text-sm mr-2" :class="getRatingTextClass(vendor.quality)">{{ vendor.quality.toFixed(1) }}</span>
                    <div class="stars flex text-yellow-400">
                      <i v-for="n in 5" :key="n" :class="getStarClass(n, vendor.quality)"></i>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <span class="text-sm mr-2" :class="getRatingTextClass(vendor.delivery)">{{ vendor.delivery.toFixed(1) }}</span>
                    <div class="stars flex text-yellow-400">
                      <i v-for="n in 5" :key="n" :class="getStarClass(n, vendor.delivery)"></i>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <span class="text-sm mr-2" :class="getRatingTextClass(vendor.price)">{{ vendor.price.toFixed(1) }}</span>
                    <div class="stars flex text-yellow-400">
                      <i v-for="n in 5" :key="n" :class="getStarClass(n, vendor.price)"></i>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <span class="text-sm mr-2" :class="getRatingTextClass(vendor.communication)">{{ vendor.communication.toFixed(1) }}</span>
                    <div class="stars flex text-yellow-400">
                      <i v-for="n in 5" :key="n" :class="getStarClass(n, vendor.communication)"></i>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                  <router-link :to="`/purchasing/vendors/${vendor.vendor_id}/performance`" class="text-blue-600 hover:underline">
                    Detailed Analysis
                  </router-link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="mt-4 flex justify-between items-center">
          <div class="text-sm text-gray-700">
            Showing <span class="font-medium">{{ paginationInfo.from }}</span> to <span class="font-medium">{{ paginationInfo.to }}</span> of <span class="font-medium">{{ paginationInfo.total }}</span> vendors
          </div>
          <pagination-component
            :current-page="paginationInfo.current_page"
            :last-page="paginationInfo.last_page"
            @page-changed="changePage">
          </pagination-component>
        </div>
      </div>
    </div>
  </template>

  <script>
  import { ref, onMounted, reactive } from 'vue';
  import axios from 'axios';
  import Chart from 'chart.js/auto';

  export default {
    name: 'VendorPerformanceReport',
    setup() {
      const trendChart = ref(null);
      const comparisonChart = ref(null);
      const vendors = ref([]);
      const vendorPerformance = ref([]);

      const filters = reactive({
        dateRange: '180',
        startDate: '',
        endDate: '',
        vendorId: '',
        category: ''
      });

      const overallMetrics = reactive({
        overall: 4.2,
        quality: 4.1,
        delivery: 3.8,
        price: 4.5,
        communication: 4.4
      });

      const deliveryMetrics = reactive({
        onTimePercentage: 87,
        totalOrders: 324,
        onTimeOrders: 282,
        lateOrders: 42
      });

      const qualityMetrics = reactive({
        acceptedPercentage: 96,
        totalShipments: 315,
        acceptedShipments: 302,
        rejectedShipments: 13
      });

      const priceMetrics = reactive({
        variancePercentage: -4.8,
        avgPrice: 142.75,
        marketAvg: 150.00,
        estimatedSavings: 28450.50
      });

      const paginationInfo = reactive({
        current_page: 1,
        from: 1,
        to: 0,
        total: 0,
        last_page: 1
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

      const loadPerformanceData = async () => {
        try {
          // In a real implementation, you would get this from the backend
          // For demo purposes, we're using sample data

          // Get vendor performance data - in real scenario, this would come from the API
          // const response = await axios.get('/api/vendor-performance', {
          //   params: { ...filters, page: paginationInfo.current_page }
          // });

          // Simulated vendor performance data
          const data = [
            {
              vendor_id: 1,
              name: 'Acme Supplies',
              overall: 4.5,
              quality: 4.6,
              delivery: 4.3,
              price: 4.7,
              communication: 4.4
            },
            {
              vendor_id: 2,
              name: 'Tech Parts Inc',
              overall: 4.2,
              quality: 4.3,
              delivery: 3.9,
              price: 4.5,
              communication: 4.1
            },
            {
              vendor_id: 3,
              name: 'Global Materials',
              overall: 3.8,
              quality: 3.9,
              delivery: 3.5,
              price: 4.2,
              communication: 3.6
            },
            {
              vendor_id: 4,
              name: 'Quality Components',
              overall: 4.0,
              quality: 4.2,
              delivery: 3.7,
              price: 4.0,
              communication: 4.1
            },
            {
              vendor_id: 5,
              name: 'Sunrise Distributors',
              overall: 3.6,
              quality: 3.5,
              delivery: 3.4,
              price: 4.1,
              communication: 3.4
            }
          ];

          vendorPerformance.value = data;

          // Update pagination info
          paginationInfo.total = data.length;
          paginationInfo.to = Math.min(paginationInfo.from + data.length - 1, paginationInfo.total);
          paginationInfo.last_page = Math.ceil(paginationInfo.total / 10); // assuming 10 per page

          renderCharts();
        } catch (error) {
          console.error('Error loading performance data:', error);
        }
      };

      const renderCharts = () => {
        // Performance Trend Chart
        if (trendChart.value) {
          const trendCtx = trendChart.value.getContext('2d');
          new Chart(trendCtx, {
            type: 'line',
            data: {
              labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
              datasets: [
                {
                  label: 'Overall',
                  data: [3.9, 4.0, 4.1, 4.3, 4.2, 4.2],
                  borderColor: '#3b82f6',
                  backgroundColor: 'rgba(59, 130, 246, 0.5)',
                  tension: 0.3
                },
                {
                  label: 'Quality',
                  data: [3.8, 4.0, 4.1, 4.2, 4.1, 4.1],
                  borderColor: '#10b981',
                  backgroundColor: 'rgba(16, 185, 129, 0.5)',
                  tension: 0.3
                },
                {
                  label: 'Delivery',
                  data: [3.5, 3.6, 3.7, 3.8, 3.8, 3.8],
                  borderColor: '#f59e0b',
                  backgroundColor: 'rgba(245, 158, 11, 0.5)',
                  tension: 0.3
                },
                {
                  label: 'Price',
                  data: [4.2, 4.3, 4.4, 4.5, 4.5, 4.5],
                  borderColor: '#8b5cf6',
                  backgroundColor: 'rgba(139, 92, 246, 0.5)',
                  tension: 0.3
                },
                {
                  label: 'Communication',
                  data: [4.1, 4.2, 4.3, 4.4, 4.4, 4.4],
                  borderColor: '#ec4899',
                  backgroundColor: 'rgba(236, 72, 153, 0.5)',
                  tension: 0.3
                }
              ]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              scales: {
                y: {
                  min: 0,
                  max: 5,
                  ticks: {
                    stepSize: 1
                  }
                }
              }
            }
          });
        }

        // Vendor Comparison Chart
        if (comparisonChart.value) {
          const comparisonCtx = comparisonChart.value.getContext('2d');
          new Chart(comparisonCtx, {
            type: 'radar',
            data: {
              labels: ['Quality', 'Delivery', 'Price', 'Communication', 'Response Time'],
              datasets: [
                {
                  label: 'Top Vendor',
                  data: [4.6, 4.3, 4.7, 4.4, 4.5],
                  backgroundColor: 'rgba(59, 130, 246, 0.2)',
                  borderColor: '#3b82f6',
                  pointBackgroundColor: '#3b82f6',
                  pointBorderColor: '#fff',
                  pointHoverBackgroundColor: '#fff',
                  pointHoverBorderColor: '#3b82f6'
                },
                {
                  label: 'Average',
                  data: [4.1, 3.8, 4.5, 4.4, 4.0],
                  backgroundColor: 'rgba(245, 158, 11, 0.2)',
                  borderColor: '#f59e0b',
                  pointBackgroundColor: '#f59e0b',
                  pointBorderColor: '#fff',
                  pointHoverBackgroundColor: '#fff',
                  pointHoverBorderColor: '#f59e0b'
                },
                {
                  label: 'Lowest Performer',
                  data: [3.5, 3.4, 4.1, 3.4, 3.2],
                  backgroundColor: 'rgba(239, 68, 68, 0.2)',
                  borderColor: '#ef4444',
                  pointBackgroundColor: '#ef4444',
                  pointBorderColor: '#fff',
                  pointHoverBackgroundColor: '#fff',
                  pointHoverBorderColor: '#ef4444'
                }
              ]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              scales: {
                r: {
                  min: 0,
                  max: 5,
                  ticks: {
                    stepSize: 1
                  }
                }
              }
            }
          });
        }
      };

      const applyFilters = () => {
        paginationInfo.current_page = 1;
        loadPerformanceData();
      };

      const resetFilters = () => {
        filters.dateRange = '180';
        filters.startDate = '';
        filters.endDate = '';
        filters.vendorId = '';
        filters.category = '';
        paginationInfo.current_page = 1;
        loadPerformanceData();
      };

      const changePage = (page) => {
        paginationInfo.current_page = page;
        loadPerformanceData();
      };

      const formatNumber = (num) => {
        return num ? num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '0.00';
      };

      const getRatingColorClass = (rating) => {
        if (rating >= 4.5) return 'text-green-600';
        if (rating >= 4.0) return 'text-blue-600';
        if (rating >= 3.5) return 'text-yellow-600';
        if (rating >= 3.0) return 'text-orange-600';
        return 'text-red-600';
      };

      const getRatingTextClass = (rating) => {
        if (rating >= 4.5) return 'text-green-600 font-medium';
        if (rating >= 4.0) return 'text-blue-600 font-medium';
        if (rating >= 3.5) return 'text-yellow-600 font-medium';
        if (rating >= 3.0) return 'text-orange-600 font-medium';
        return 'text-red-600 font-medium';
      };

      const getStarClass = (position, rating) => {
        if (position <= Math.floor(rating)) {
          return 'fas fa-star';
        } else if (position <= Math.ceil(rating) && position > Math.floor(rating)) {
          return 'fas fa-star-half-alt';
        } else {
          return 'far fa-star';
        }
      };

      onMounted(() => {
        loadVendors();
        loadPerformanceData();
      });

      return {
        trendChart,
        comparisonChart,
        vendors,
        vendorPerformance,
        filters,
        overallMetrics,
        deliveryMetrics,
        qualityMetrics,
        priceMetrics,
        paginationInfo,
        applyFilters,
        resetFilters,
        changePage,
        formatNumber,
        getRatingColorClass,
        getRatingTextClass,
        getStarClass
      };
    }
  };
  </script>

  <style scoped>
  .vendor-performance {
    min-height: calc(100vh - 150px);
  }
  </style>
