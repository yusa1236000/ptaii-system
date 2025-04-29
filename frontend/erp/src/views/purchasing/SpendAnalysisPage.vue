<!-- src/views/purchasing/SpendAnalysisPage.vue -->
<template>
    <div class="spend-analysis">
      <div class="page-header mb-6">
        <h1 class="text-2xl font-semibold">Spend Analysis</h1>
        <p class="text-gray-500">Analyze your purchasing spend by various dimensions</p>
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
            <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
            <select v-model="filters.categoryId" class="w-full border border-gray-300 rounded-md shadow-sm px-3 py-2">
              <option value="">All Categories</option>
              <option v-for="category in categories" :key="category.category_id" :value="category.category_id">
                {{ category.name }}
              </option>
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

      <div class="summary-cards grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="card bg-white p-4 rounded-lg shadow">
          <h3 class="text-lg font-semibold text-gray-700 mb-2">Total Spend</h3>
          <p class="text-3xl font-bold text-blue-600">${{ formatNumber(summary.totalSpend) }}</p>
          <div class="text-sm text-gray-500 mt-1">
            <span :class="summary.spendChange >= 0 ? 'text-green-600' : 'text-red-600'">
              <i :class="summary.spendChange >= 0 ? 'fas fa-arrow-up' : 'fas fa-arrow-down'" class="mr-1"></i>
              {{ Math.abs(summary.spendChange) }}%
            </span>
            vs. previous period
          </div>
        </div>

        <div class="card bg-white p-4 rounded-lg shadow">
          <h3 class="text-lg font-semibold text-gray-700 mb-2">Total Orders</h3>
          <p class="text-3xl font-bold text-purple-600">{{ summary.totalOrders }}</p>
          <div class="text-sm text-gray-500 mt-1">
            <span :class="summary.ordersChange >= 0 ? 'text-green-600' : 'text-red-600'">
              <i :class="summary.ordersChange >= 0 ? 'fas fa-arrow-up' : 'fas fa-arrow-down'" class="mr-1"></i>
              {{ Math.abs(summary.ordersChange) }}%
            </span>
            vs. previous period
          </div>
        </div>

        <div class="card bg-white p-4 rounded-lg shadow">
          <h3 class="text-lg font-semibold text-gray-700 mb-2">Avg. Order Value</h3>
          <p class="text-3xl font-bold text-green-600">${{ formatNumber(summary.avgOrderValue) }}</p>
          <div class="text-sm text-gray-500 mt-1">
            <span :class="summary.avgOrderChange >= 0 ? 'text-green-600' : 'text-red-600'">
              <i :class="summary.avgOrderChange >= 0 ? 'fas fa-arrow-up' : 'fas fa-arrow-down'" class="mr-1"></i>
              {{ Math.abs(summary.avgOrderChange) }}%
            </span>
            vs. previous period
          </div>
        </div>
      </div>

      <div class="charts-grid grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="chart-card bg-white p-4 rounded-lg shadow">
          <h3 class="text-lg font-semibold mb-4">Spend by Category</h3>
          <div class="chart-container" style="height: 350px;">
            <canvas ref="categoryChart"></canvas>
          </div>
        </div>

        <div class="chart-card bg-white p-4 rounded-lg shadow">
          <h3 class="text-lg font-semibold mb-4">Spend by Vendor</h3>
          <div class="chart-container" style="height: 350px;">
            <canvas ref="vendorChart"></canvas>
          </div>
        </div>

        <div class="chart-card bg-white p-4 rounded-lg shadow">
          <h3 class="text-lg font-semibold mb-4">Monthly Spend Trend</h3>
          <div class="chart-container" style="height: 350px;">
            <canvas ref="trendChart"></canvas>
          </div>
        </div>

        <div class="chart-card bg-white p-4 rounded-lg shadow">
          <h3 class="text-lg font-semibold mb-4">Spend by Payment Terms</h3>
          <div class="chart-container" style="height: 350px;">
            <canvas ref="paymentTermsChart"></canvas>
          </div>
        </div>
      </div>

      <div class="detailed-data bg-white p-4 rounded-lg shadow">
        <h3 class="text-lg font-semibold mb-4">Detailed Purchase Orders</h3>

        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PO Number</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendor</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="order in purchaseOrders" :key="order.po_id">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-blue-600">
                  <router-link :to="`/purchasing/orders/${order.po_id}`">
                    {{ order.po_number }}
                  </router-link>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">{{ order.vendor_name }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDate(order.po_date) }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">${{ formatNumber(order.total_amount) }}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                    :class="getStatusClass(order.status)">
                    {{ order.status }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ order.primary_category || 'Multiple' }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="mt-4 flex justify-between items-center">
          <div class="text-sm text-gray-700">
            Showing <span class="font-medium">{{ paginationInfo.from }}</span> to <span class="font-medium">{{ paginationInfo.to }}</span> of <span class="font-medium">{{ paginationInfo.total }}</span> orders
          </div>
          <pagination-component
            :current-page="paginationInfo.current_page"
            :last-page="paginationInfo.last_page"
            @page-changed="changePage">
          </pagination-component>
        </div>
      </div>

      <div class="export-section mt-6 flex justify-end">
        <button @click="exportData" class="bg-gray-800 hover:bg-gray-900 text-white font-medium px-4 py-2 rounded flex items-center">
          <i class="fas fa-download mr-2"></i> Export Data
        </button>
      </div>
    </div>
  </template>

  <script>
  import { ref, onMounted, reactive } from 'vue';
  import axios from 'axios';
  import Chart from 'chart.js/auto';

  export default {
    name: 'SpendAnalysisPage',
    setup() {
      const categoryChart = ref(null);
      const vendorChart = ref(null);
      const trendChart = ref(null);
      const paymentTermsChart = ref(null);
      const vendors = ref([]);
      const categories = ref([]);
      const purchaseOrders = ref([]);

      const filters = reactive({
        dateRange: '180',
        startDate: '',
        endDate: '',
        vendorId: '',
        categoryId: ''
      });

      const summary = reactive({
        totalSpend: 0,
        spendChange: 0,
        totalOrders: 0,
        ordersChange: 0,
        avgOrderValue: 0,
        avgOrderChange: 0
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

      const loadCategories = async () => {
        try {
          const response = await axios.get('/api/categories');
          if (response.data && response.data.data) {
            categories.value = response.data.data;
          }
        } catch (error) {
          console.error('Error loading categories:', error);
        }
      };

      const loadAnalysisData = async () => {
        try {
          // In a real implementation, you would send the filters to the backend
          // For now, we'll simulate the response

          // Summary data
          summary.totalSpend = 456789.34;
          summary.spendChange = 5.2;
          summary.totalOrders = 125;
          summary.ordersChange = -2.1;
          summary.avgOrderValue = summary.totalSpend / summary.totalOrders;
          summary.avgOrderChange = 7.5;

          // Load purchase orders with pagination
          const poResponse = await axios.get('/api/purchase-orders', {
            params: {
              page: paginationInfo.current_page,
              per_page: 10,
              // Add other filters from the filter object
              vendor_id: filters.vendorId || undefined,
              date_from: filters.startDate || undefined,
              date_to: filters.endDate || undefined,
              // other filters as needed
            }
          });

          if (poResponse.data && poResponse.data.data) {
            purchaseOrders.value = poResponse.data.data.data.map(po => ({
              ...po,
              vendor_name: po.vendor ? po.vendor.name : 'Unknown',
              primary_category: po.lines && po.lines[0] && po.lines[0].item && po.lines[0].item.category ?
                po.lines[0].item.category.name : 'Uncategorized'
            }));

            // Update pagination info
            paginationInfo.current_page = poResponse.data.data.current_page;
            paginationInfo.from = poResponse.data.data.from;
            paginationInfo.to = poResponse.data.data.to;
            paginationInfo.total = poResponse.data.data.total;
            paginationInfo.last_page = poResponse.data.data.last_page;
          }

          // Render charts
          renderCharts();
        } catch (error) {
          console.error('Error loading analysis data:', error);
        }
      };

      const renderCharts = () => {
        // Sample data for charts
        const categoryData = {
          labels: ['Raw Materials', 'Office Supplies', 'Equipment', 'Services', 'Maintenance', 'Other'],
          datasets: [{
            data: [125000, 45000, 72000, 68000, 35000, 22000],
            backgroundColor: [
              '#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', '#6b7280'
            ]
          }]
        };

        const vendorData = {
          labels: ['Acme Inc', 'Global Supplies', 'Tech Solutions', 'Best Materials', 'Quality Services'],
          datasets: [{
            data: [85000, 72000, 65000, 45000, 38000],
            backgroundColor: [
              '#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899'
            ]
          }]
        };

        const trendData = {
          labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
          datasets: [{
            label: 'Monthly Spend',
            data: [38500, 42100, 36700, 44200, 39800, 45600],
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.3,
            fill: true
          }]
        };

        const paymentTermsData = {
          labels: ['Net 30', 'Net 45', 'Net 60', 'Immediate', 'Other'],
          datasets: [{
            data: [185000, 124000, 85000, 45000, 18000],
            backgroundColor: [
              '#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#6b7280'
            ]
          }]
        };

        // Render category chart
        if (categoryChart.value) {
          new Chart(categoryChart.value.getContext('2d'), {
            type: 'pie',
            data: categoryData,
            options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                legend: {
                  position: 'right'
                },
                tooltip: {
                  callbacks: {
                    label: function(tooltipItem) {
                      const value = tooltipItem.raw;
                      const label = tooltipItem.label;
                      return `${label}: ${value.toLocaleString()}`;
                    }
                  }
                }
              }
            }
          });
        }

        // Render vendor chart
        if (vendorChart.value) {
          new Chart(vendorChart.value.getContext('2d'), {
            type: 'bar',
            data: {
              labels: vendorData.labels,
              datasets: [{
                label: 'Spend by Vendor',
                data: vendorData.datasets[0].data,
                backgroundColor: vendorData.datasets[0].backgroundColor
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

        // Render trend chart
        if (trendChart.value) {
          new Chart(trendChart.value.getContext('2d'), {
            type: 'line',
            data: trendData,
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

        // Render payment terms chart
        if (paymentTermsChart.value) {
          new Chart(paymentTermsChart.value.getContext('2d'), {
            type: 'doughnut',
            data: paymentTermsData,
            options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                legend: {
                  position: 'right'
                },
                tooltip: {
                  callbacks: {
                    label: function(tooltipItem) {
                      const value = tooltipItem.raw;
                      const label = tooltipItem.label;
                      return `${label}: ${value.toLocaleString()}`;
                    }
                  }
                }
              }
            }
          });
        }
      };

      const applyFilters = () => {
        // Reset to first page when applying new filters
        paginationInfo.current_page = 1;
        loadAnalysisData();
      };

      const resetFilters = () => {
        filters.dateRange = '180';
        filters.startDate = '';
        filters.endDate = '';
        filters.vendorId = '';
        filters.categoryId = '';
        paginationInfo.current_page = 1;
        loadAnalysisData();
      };

      const changePage = (page) => {
        paginationInfo.current_page = page;
        loadAnalysisData();
      };

      const exportData = () => {
        // In a real implementation, this would trigger a backend export
        alert('Exporting data...');
        // You could use axios to call an export endpoint that returns a file:
        // window.location.href = `/api/purchasing/export-spend-analysis?${queryParams}`;
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
        loadVendors();
        loadCategories();
        loadAnalysisData();
      });

      return {
        categoryChart,
        vendorChart,
        trendChart,
        paymentTermsChart,
        vendors,
        categories,
        purchaseOrders,
        filters,
        summary,
        paginationInfo,
        applyFilters,
        resetFilters,
        changePage,
        exportData,
        formatNumber,
        formatDate,
        getStatusClass
      };
    }
  };
  </script>

  <style scoped>
  .spend-analysis {
    min-height: calc(100vh - 150px);
  }
  </style>
