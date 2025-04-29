<!-- src/views/purchasing/PriceTrendAnalysis.vue -->
<template>
    <div class="price-trend-analysis">
      <div class="page-header mb-6">
        <h1 class="text-2xl font-semibold">Price Trend Analysis</h1>
        <p class="text-gray-500">Track and analyze purchasing price trends over time</p>
      </div>

      <div class="filters bg-white p-4 rounded-lg shadow mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div class="filter-group">
            <label class="block text-sm font-medium text-gray-700 mb-1">Date Range</label>
            <select v-model="filters.dateRange" class="w-full border border-gray-300 rounded-md shadow-sm px-3 py-2">
              <option value="180">Last 6 Months</option>
              <option value="365">Last Year</option>
              <option value="730">Last 2 Years</option>
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

          <div class="filter-group">
            <label class="block text-sm font-medium text-gray-700 mb-1">Item</label>
            <select v-model="filters.itemId" class="w-full border border-gray-300 rounded-md shadow-sm px-3 py-2">
              <option value="">All Items</option>
              <option v-for="item in items" :key="item.item_id" :value="item.item_id">
                {{ item.name }}
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

      <div class="summary-cards grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="card bg-white p-4 rounded-lg shadow">
          <h3 class="text-lg font-semibold text-gray-700 mb-2">Average Price</h3>
          <p class="text-3xl font-bold text-blue-600">${{ formatNumber(summary.avgPrice) }}</p>
          <div class="text-sm text-gray-500 mt-1">
            <span :class="summary.priceChange >= 0 ? 'text-red-600' : 'text-green-600'">
              <i :class="summary.priceChange >= 0 ? 'fas fa-arrow-up' : 'fas fa-arrow-down'" class="mr-1"></i>
              {{ Math.abs(summary.priceChange) }}%
            </span>
            vs. previous period
          </div>
        </div>

        <div class="card bg-white p-4 rounded-lg shadow">
          <h3 class="text-lg font-semibold text-gray-700 mb-2">Price Volatility</h3>
          <p class="text-3xl font-bold text-purple-600">{{ summary.volatility }}%</p>
          <div class="text-sm text-gray-500 mt-1">
            <span :class="summary.volatilityChange >= 0 ? 'text-red-600' : 'text-green-600'">
              <i :class="summary.volatilityChange >= 0 ? 'fas fa-arrow-up' : 'fas fa-arrow-down'" class="mr-1"></i>
              {{ Math.abs(summary.volatilityChange) }}%
            </span>
            vs. previous period
          </div>
        </div>

        <div class="card bg-white p-4 rounded-lg shadow">
          <h3 class="text-lg font-semibold text-gray-700 mb-2">Price Range</h3>
          <p class="text-3xl font-bold text-green-600">${{ formatNumber(summary.minPrice) }} - ${{ formatNumber(summary.maxPrice) }}</p>
          <div class="text-sm text-gray-500 mt-1">
            <span>Range: ${{ formatNumber(summary.maxPrice - summary.minPrice) }}</span>
          </div>
        </div>

        <div class="card bg-white p-4 rounded-lg shadow">
          <h3 class="text-lg font-semibold text-gray-700 mb-2">YoY Change</h3>
          <p class="text-3xl font-bold" :class="summary.yoyChange >= 0 ? 'text-red-600' : 'text-green-600'">
            {{ summary.yoyChange >= 0 ? '+' : '' }}{{ summary.yoyChange }}%
          </p>
          <div class="text-sm text-gray-500 mt-1">
            Year-over-year price change
          </div>
        </div>
      </div>

      <div class="charts-section mb-6">
        <div class="bg-white p-4 rounded-lg shadow">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Price Trend Over Time</h3>
            <div class="trend-controls flex items-center space-x-4">
              <div class="flex items-center">
                <label class="text-sm mr-2">Group By:</label>
                <select v-model="trendOptions.groupBy" class="border border-gray-300 rounded-md shadow-sm px-3 py-1">
                  <option value="month">Month</option>
                  <option value="quarter">Quarter</option>
                  <option value="year">Year</option>
                </select>
              </div>
              <div class="flex items-center">
                <label class="text-sm mr-2">Display:</label>
                <select v-model="trendOptions.display" class="border border-gray-300 rounded-md shadow-sm px-3 py-1">
                  <option value="line">Line</option>
                  <option value="bar">Bar</option>
                </select>
              </div>
            </div>
          </div>
          <div class="chart-container" style="height: 400px;">
            <canvas ref="trendChart"></canvas>
          </div>
        </div>
      </div>

      <div class="analysis-grid grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="chart-card bg-white p-4 rounded-lg shadow">
          <h3 class="text-lg font-semibold mb-4">Price Comparison by Vendor</h3>
          <div class="chart-container" style="height: 350px;">
            <canvas ref="vendorComparisonChart"></canvas>
          </div>
        </div>

        <div class="chart-card bg-white p-4 rounded-lg shadow">
          <h3 class="text-lg font-semibold mb-4">Price Comparison by Category</h3>
          <div class="chart-container" style="height: 350px;">
            <canvas ref="categoryComparisonChart"></canvas>
          </div>
        </div>
      </div>

      <div class="detailed-data bg-white p-4 rounded-lg shadow">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold">Price History Detail</h3>
          <button @click="exportData" class="bg-gray-800 hover:bg-gray-900 text-white font-medium px-4 py-2 rounded flex items-center">
            <i class="fas fa-download mr-2"></i> Export Data
          </button>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendor</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PO Number</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Change</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="(entry, index) in priceHistory" :key="entry.id">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800">{{ entry.item_name }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">{{ entry.vendor_name }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ entry.category_name }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDate(entry.date) }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800">${{ formatNumber(entry.unit_price) }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ entry.quantity }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-600">
                  <router-link :to="`/purchasing/orders/${entry.po_id}`">
                    {{ entry.po_number }}
                  </router-link>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                  <span v-if="index < priceHistory.length - 1"
                        :class="getPriceChangeClass(entry.unit_price, priceHistory[index + 1].unit_price)">
                    {{ calculatePriceChange(entry.unit_price, priceHistory[index + 1].unit_price) }}%
                  </span>
                  <span v-else>-</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="mt-4 flex justify-between items-center">
          <div class="text-sm text-gray-700">
            Showing <span class="font-medium">{{ paginationInfo.from }}</span> to <span class="font-medium">{{ paginationInfo.to }}</span> of <span class="font-medium">{{ paginationInfo.total }}</span> records
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
  import { ref, onMounted, reactive, watch } from 'vue';
  import axios from 'axios';
  import Chart from 'chart.js/auto';

  export default {
    name: 'PriceTrendAnalysis',
    setup() {
      const trendChart = ref(null);
      const vendorComparisonChart = ref(null);
      const categoryComparisonChart = ref(null);
      const vendors = ref([]);
      const categories = ref([]);
      const items = ref([]);
      const priceHistory = ref([]);
      let trendChartInstance = null;

      const filters = reactive({
        dateRange: '365',
        startDate: '',
        endDate: '',
        vendorId: '',
        categoryId: '',
        itemId: ''
      });

      const trendOptions = reactive({
        groupBy: 'month',
        display: 'line'
      });

      const summary = reactive({
        avgPrice: 0,
        priceChange: 0,
        volatility: 0,
        volatilityChange: 0,
        minPrice: 0,
        maxPrice: 0,
        yoyChange: 0
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

      const loadItems = async () => {
        try {
          const response = await axios.get('/api/items');
          if (response.data && response.data.data) {
            items.value = response.data.data;
          }
        } catch (error) {
          console.error('Error loading items:', error);
        }
      };

      const loadPriceData = async () => {
        try {
          // In a real implementation, this would be an API call with the filters
          // const response = await axios.get('/api/price-trend-analysis', {
          //   params: {
          //     ...filters,
          //     page: paginationInfo.current_page
          //   }
          // });

          // For demo purposes, we'll use sample data

          // Simulated price history data
          priceHistory.value = generateSamplePriceHistory();

          // Update pagination info
          paginationInfo.from = 1;
          paginationInfo.to = priceHistory.value.length;
          paginationInfo.total = priceHistory.value.length;
          paginationInfo.last_page = 1;

          // Calculate summary metrics
          calculateSummaryMetrics();

          // Render charts
          renderTrendChart();
          renderComparisonCharts();
        } catch (error) {
          console.error('Error loading price data:', error);
        }
      };

      const generateSamplePriceHistory = () => {
        // Generate sample price history data for demonstration
        const data = [];
        const items = ['Steel Pipe', 'Aluminum Sheet', 'Copper Wire', 'Plastic Resin', 'Electronic Component'];
        const vendors = ['Acme Supplies', 'Tech Parts Inc', 'Global Materials', 'Quality Components', 'Sunrise Distributors'];
        const categories = ['Raw Materials', 'Metal Products', 'Electronic Parts', 'Plastic Components', 'Misc Supplies'];

        const baseDate = new Date();
        baseDate.setFullYear(baseDate.getFullYear() - 1);

        for (let i = 0; i < 20; i++) {
          const date = new Date(baseDate);
          date.setDate(date.getDate() + i * 18); // Spread entries over the past year

          const itemIndex = i % items.length;
          const vendorIndex = i % vendors.length;
          const categoryIndex = i % categories.length;

          // Generate oscillating prices to show trends
          const basePrice = 50 + (itemIndex * 10);
          const priceVariation = Math.sin(i * 0.5) * 5; // Oscillating component
          const trendComponent = i * 0.2; // Slight upward trend
          const randomComponent = (Math.random() - 0.5) * 4; // Random noise

          const unitPrice = basePrice + priceVariation + trendComponent + randomComponent;

          data.push({
            id: i + 1,
            item_name: items[itemIndex],
            vendor_name: vendors[vendorIndex],
            category_name: categories[categoryIndex],
            date: date.toISOString().split('T')[0],
            unit_price: parseFloat(unitPrice.toFixed(2)),
            quantity: Math.floor(Math.random() * 100) + 10,
            po_id: 1000 + i,
            po_number: `PO-${2023}-${1000 + i}`
          });
        }

        // Sort by date descending
        return data.sort((a, b) => new Date(b.date) - new Date(a.date));
      };

      const calculateSummaryMetrics = () => {
        // Calculate average price
        const prices = priceHistory.value.map(entry => entry.unit_price);
        summary.avgPrice = prices.reduce((sum, price) => sum + price, 0) / prices.length;

        // Calculate min and max price
        summary.minPrice = Math.min(...prices);
        summary.maxPrice = Math.max(...prices);

        // Calculate price change (compared to previous period)
        // Assume first entry is most recent, last entry is oldest
        const currentPrice = prices[0];
        const previousPrice = prices[prices.length - 1];
        summary.priceChange = parseFloat(((currentPrice - previousPrice) / previousPrice * 100).toFixed(1));

        // Calculate volatility (standard deviation as percentage of mean)
        const mean = summary.avgPrice;
        const squaredDiffs = prices.map(price => (price - mean) ** 2);
        const variance = squaredDiffs.reduce((sum, sqDiff) => sum + sqDiff, 0) / prices.length;
        const stdDev = Math.sqrt(variance);
        summary.volatility = parseFloat(((stdDev / mean) * 100).toFixed(1));

        // Previous period volatility (simulated for demo)
        summary.volatilityChange = -1.2;

        // Year-over-year change (simulated for demo)
        summary.yoyChange = 3.5;
      };

      const renderTrendChart = () => {
        // Destroy existing chart if it exists
        if (trendChartInstance) {
          trendChartInstance.destroy();
        }

        if (!trendChart.value) return;

        // Prepare data for chart based on groupBy option
        const groupedData = groupPriceData(priceHistory.value, trendOptions.groupBy);

        // Create chart
        const ctx = trendChart.value.getContext('2d');
        trendChartInstance = new Chart(ctx, {
          type: trendOptions.display,
          data: {
            labels: groupedData.labels,
            datasets: [{
              label: 'Average Price',
              data: groupedData.averages,
              borderColor: '#3b82f6',
              backgroundColor: 'rgba(59, 130, 246, 0.1)',
              tension: 0.3,
              fill: trendOptions.display === 'line'
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              tooltip: {
                callbacks: {
                  label: function(tooltipItem) {
                    return `Avg Price: ${tooltipItem.raw.toFixed(2)}`;
                  }
                }
              }
            },
            scales: {
              y: {
                beginAtZero: false,
                ticks: {
                  callback: function(value) {
                    return '$' + value.toFixed(2);
                  }
                }
              }
            }
          }
        });
      };

      const groupPriceData = (priceData, groupBy) => {
        // Group price data by selected time period
        const groups = {};
        const labels = [];
        const averages = [];

        // Sort by date (oldest to newest for chart)
        const sortedData = [...priceData].sort((a, b) => new Date(a.date) - new Date(b.date));

        sortedData.forEach(entry => {
          const date = new Date(entry.date);
          let groupKey;

          if (groupBy === 'month') {
            groupKey = `${date.getFullYear()}-${(date.getMonth() + 1).toString().padStart(2, '0')}`;
          } else if (groupBy === 'quarter') {
            const quarter = Math.floor(date.getMonth() / 3) + 1;
            groupKey = `${date.getFullYear()}-Q${quarter}`;
          } else if (groupBy === 'year') {
            groupKey = date.getFullYear().toString();
          }

          if (!groups[groupKey]) {
            groups[groupKey] = {
              prices: [],
              label: formatGroupLabel(groupKey, groupBy)
            };
          }

          groups[groupKey].prices.push(entry.unit_price);
        });

        // Calculate averages and prepare labels
        Object.keys(groups).sort().forEach(key => {
          const group = groups[key];
          labels.push(group.label);
          const sum = group.prices.reduce((total, price) => total + price, 0);
          averages.push(parseFloat((sum / group.prices.length).toFixed(2)));
        });

        return { labels, averages };
      };

      const formatGroupLabel = (key, groupBy) => {
        if (groupBy === 'month') {
          const [year, month] = key.split('-');
          const date = new Date(parseInt(year), parseInt(month) - 1, 1);
          return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short' });
        } else if (groupBy === 'quarter') {
          return key; // Already in YYYY-QN format
        } else {
          return key; // Year
        }
      };

      const renderComparisonCharts = () => {
        // Vendor Comparison Chart
        if (vendorComparisonChart.value) {
          // Group and calculate average prices by vendor
          const vendorData = {};

          priceHistory.value.forEach(entry => {
            if (!vendorData[entry.vendor_name]) {
              vendorData[entry.vendor_name] = {
                prices: [],
                color: getRandomColor()
              };
            }

            vendorData[entry.vendor_name].prices.push(entry.unit_price);
          });

          const vendorLabels = [];
          const vendorAverages = [];
          const vendorColors = [];

          Object.keys(vendorData).forEach(vendor => {
            vendorLabels.push(vendor);
            const prices = vendorData[vendor].prices;
            const avg = prices.reduce((total, price) => total + price, 0) / prices.length;
            vendorAverages.push(parseFloat(avg.toFixed(2)));
            vendorColors.push(vendorData[vendor].color);
          });

          // Create chart
          new Chart(vendorComparisonChart.value.getContext('2d'), {
            type: 'bar',
            data: {
              labels: vendorLabels,
              datasets: [{
                label: 'Average Price by Vendor',
                data: vendorAverages,
                backgroundColor: vendorColors
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
                      return `Avg Price: ${tooltipItem.raw.toFixed(2)}`;
                    }
                  }
                }
              },
            scales: {
              y: {
                beginAtZero: false,
                ticks: {
                  callback: function(value) {
                    return '$' + value.toFixed(2);
                  }
                }
              }
            }
            }
          });
        }

        // Category Comparison Chart
        if (categoryComparisonChart.value) {
          // Group and calculate average prices by category
          const categoryData = {};

          priceHistory.value.forEach(entry => {
            if (!categoryData[entry.category_name]) {
              categoryData[entry.category_name] = {
                prices: [],
                color: getRandomColor()
              };
            }

            categoryData[entry.category_name].prices.push(entry.unit_price);
          });

          const categoryLabels = [];
          const categoryAverages = [];
          const categoryColors = [];

          Object.keys(categoryData).forEach(category => {
            categoryLabels.push(category);
            const prices = categoryData[category].prices;
            const avg = prices.reduce((total, price) => total + price, 0) / prices.length;
            categoryAverages.push(parseFloat(avg.toFixed(2)));
            categoryColors.push(categoryData[category].color);
          });

          // Create chart
          new Chart(categoryComparisonChart.value.getContext('2d'), {
            type: 'bar',
            data: {
              labels: categoryLabels,
              datasets: [{
                label: 'Average Price by Category',
                data: categoryAverages,
                backgroundColor: categoryColors
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
                      return `Avg Price: ${tooltipItem.raw.toFixed(2)}`;
                    }
                  }
                }
              },
            scales: {
              y: {
                beginAtZero: false,
                ticks: {
                  callback: function(value) {
                    return '$' + value.toFixed(2);
                  }
                }
              }
            }
            }
          });
        }
      };

      const getRandomColor = () => {
        // Generate random colors for chart elements
        const colors = [
          'rgba(59, 130, 246, 0.7)',  // Blue
          'rgba(16, 185, 129, 0.7)',  // Green
          'rgba(245, 158, 11, 0.7)',  // Amber
          'rgba(139, 92, 246, 0.7)',  // Purple
          'rgba(236, 72, 153, 0.7)',  // Pink
          'rgba(239, 68, 68, 0.7)',   // Red
          'rgba(168, 85, 247, 0.7)',  // Violet
          'rgba(14, 165, 233, 0.7)'   // Sky
        ];

        return colors[Math.floor(Math.random() * colors.length)];
      };

      const applyFilters = () => {
        paginationInfo.current_page = 1;
        loadPriceData();
      };

      const resetFilters = () => {
        filters.dateRange = '365';
        filters.startDate = '';
        filters.endDate = '';
        filters.vendorId = '';
        filters.categoryId = '';
        filters.itemId = '';
        paginationInfo.current_page = 1;
        loadPriceData();
      };

      const changePage = (page) => {
        paginationInfo.current_page = page;
        loadPriceData();
      };

      const exportData = () => {
        // This would typically export to CSV or Excel
        alert('Exporting price trend data...');
        // In a real implementation, you would call an API endpoint that returns a file
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

      const calculatePriceChange = (currentPrice, previousPrice) => {
        if (!previousPrice) return 0;
        const change = ((currentPrice - previousPrice) / previousPrice) * 100;
        return change.toFixed(2);
      };

      const getPriceChangeClass = (currentPrice, previousPrice) => {
        if (!previousPrice) return '';
        const change = currentPrice - previousPrice;
        if (change > 0) return 'text-red-600';
        if (change < 0) return 'text-green-600';
        return 'text-gray-600';
      };

      // Watch for changes in trend options to update chart
      watch([() => trendOptions.groupBy, () => trendOptions.display], () => {
        renderTrendChart();
      });

      onMounted(() => {
        loadVendors();
        loadCategories();
        loadItems();
        loadPriceData();
      });

      return {
        trendChart,
        vendorComparisonChart,
        categoryComparisonChart,
        vendors,
        categories,
        items,
        priceHistory,
        filters,
        trendOptions,
        summary,
        paginationInfo,
        applyFilters,
        resetFilters,
        changePage,
        exportData,
        formatNumber,
        formatDate,
        calculatePriceChange,
        getPriceChangeClass
      };
    }
  };
  </script>

  <style scoped>
  .price-trend-analysis {
    min-height: calc(100vh - 150px);
  }
  </style>
