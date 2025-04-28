<!-- src/views/purchasing/EvaluationMetricsDashboard.vue -->
<template>
    <div class="dashboard-container">
      <div class="page-header">
        <h1 class="text-2xl font-semibold">Vendor Evaluation Metrics Dashboard</h1>
        <div class="header-actions">
          <button @click="refreshData" class="btn-icon-text">
            <i class="fas fa-sync-alt"></i>
            <span>Refresh</span>
          </button>
          <div class="period-selector">
            <label for="period">Time Period:</label>
            <select id="period" v-model="selectedPeriod" @change="loadDashboardData" class="form-select">
              <option value="month">Last Month</option>
              <option value="quarter">Last Quarter</option>
              <option value="year">Last Year</option>
              <option value="all">All Time</option>
            </select>
          </div>
        </div>
      </div>

      <div v-if="loading" class="loading-overlay">
        <div class="loading-spinner">
          <i class="fas fa-spinner fa-spin"></i>
          <span>Loading dashboard data...</span>
        </div>
      </div>

      <div v-else class="dashboard-content">
        <!-- Key Performance Indicators -->
        <div class="kpi-section">
          <div class="section-header">
            <h2>Key Performance Indicators</h2>
            <span class="section-subtitle">{{ getPeriodTitle() }}</span>
          </div>
          <div class="kpi-cards">
            <div class="kpi-card">
              <div class="kpi-icon">
                <i class="fas fa-users"></i>
              </div>
              <div class="kpi-content">
                <div class="kpi-value">{{ dashboardData.totalVendors }}</div>
                <div class="kpi-label">Active Vendors</div>
              </div>
            </div>
            <div class="kpi-card">
              <div class="kpi-icon">
                <i class="fas fa-clipboard-check"></i>
              </div>
              <div class="kpi-content">
                <div class="kpi-value">{{ dashboardData.totalEvaluations }}</div>
                <div class="kpi-label">Total Evaluations</div>
              </div>
            </div>
            <div class="kpi-card">
              <div class="kpi-icon">
                <i class="fas fa-star"></i>
              </div>
              <div class="kpi-content">
                <div class="kpi-value">{{ dashboardData.averageScore?.toFixed(2) || '0.00' }}</div>
                <div class="kpi-label">Average Score</div>
              </div>
              <div
                v-if="dashboardData.scoreTrend"
                class="kpi-trend"
                :class="dashboardData.scoreTrend > 0 ? 'trend-up' : (dashboardData.scoreTrend < 0 ? 'trend-down' : 'trend-neutral')"
              >
                <i :class="dashboardData.scoreTrend > 0 ? 'fas fa-arrow-up' : (dashboardData.scoreTrend < 0 ? 'fas fa-arrow-down' : 'fas fa-equals')"></i>
                <span>{{ Math.abs(dashboardData.scoreTrend).toFixed(2) }}</span>
              </div>
            </div>
            <div class="kpi-card">
              <div class="kpi-icon">
                <i class="fas fa-truck"></i>
              </div>
              <div class="kpi-content">
                <div class="kpi-value">{{ dashboardData.averageDeliveryScore?.toFixed(2) || '0.00' }}</div>
                <div class="kpi-label">Avg. Delivery Score</div>
              </div>
            </div>
            <div class="kpi-card">
              <div class="kpi-icon">
                <i class="fas fa-dollar-sign"></i>
              </div>
              <div class="kpi-content">
                <div class="kpi-value">{{ dashboardData.averagePriceScore?.toFixed(2) || '0.00' }}</div>
                <div class="kpi-label">Avg. Price Score</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Performance Distribution Chart -->
        <div class="chart-section">
          <div class="chart-container performance-distribution">
            <div class="chart-header">
              <h3>Vendor Performance Distribution</h3>
              <div class="chart-legend">
                <div class="legend-item">
                  <div class="legend-color bg-excellent"></div>
                  <span>Excellent (4.0+)</span>
                </div>
                <div class="legend-item">
                  <div class="legend-color bg-good"></div>
                  <span>Good (3.0-3.9)</span>
                </div>
                <div class="legend-item">
                  <div class="legend-color bg-average"></div>
                  <span>Average (2.0-2.9)</span>
                </div>
                <div class="legend-item">
                  <div class="legend-color bg-poor"></div>
                  <span>Poor (&lt;)</span>
                </div>
              </div>
            </div>
            <div ref="performanceDistribution" class="chart-canvas"></div>
          </div>

          <div class="chart-container metrics-comparison">
            <div class="chart-header">
              <h3>Evaluation Metrics Comparison</h3>
            </div>
            <div ref="metricsComparison" class="chart-canvas"></div>
          </div>
        </div>

        <!-- Top and Bottom Performers -->
        <div class="performers-section">
          <div class="performers-container top-performers">
            <h3>Top Performers</h3>
            <div class="performers-list">
              <div v-for="vendor in dashboardData.topVendors" :key="vendor.vendor_id" class="performer-card">
                <div class="performer-rank">{{ vendor.rank }}</div>
                <div class="performer-info">
                  <div class="performer-name">{{ vendor.name }}</div>
                  <div class="performer-category">{{ vendor.category || 'General' }}</div>
                </div>
                <div class="performer-score">
                  <div class="score-badge" :class="getScoreClass(vendor.score)">
                    {{ vendor.score.toFixed(2) }}
                  </div>
                </div>
                <router-link :to="`/purchasing/vendors/${vendor.vendor_id}/performance`" class="btn-text">
                  View Details
                </router-link>
              </div>
              <div v-if="!dashboardData.topVendors?.length" class="empty-performers">
                <i class="fas fa-info-circle"></i>
                <span>No vendor data available</span>
              </div>
            </div>
          </div>

          <div class="performers-container bottom-performers">
            <h3>Needs Improvement</h3>
            <div class="performers-list">
              <div v-for="vendor in dashboardData.bottomVendors" :key="vendor.vendor_id" class="performer-card">
                <div class="performer-rank">{{ vendor.rank }}</div>
                <div class="performer-info">
                  <div class="performer-name">{{ vendor.name }}</div>
                  <div class="performer-category">{{ vendor.category || 'General' }}</div>
                </div>
                <div class="performer-score">
                  <div class="score-badge" :class="getScoreClass(vendor.score)">
                    {{ vendor.score.toFixed(2) }}
                  </div>
                </div>
                <router-link :to="`/purchasing/vendors/${vendor.vendor_id}/performance`" class="btn-text">
                  View Details
                </router-link>
              </div>
              <div v-if="!dashboardData.bottomVendors?.length" class="empty-performers">
                <i class="fas fa-info-circle"></i>
                <span>No vendor data available</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent Evaluations -->
        <div class="recent-evaluations-section">
          <div class="section-header">
            <h2>Recent Evaluations</h2>
            <router-link to="/purchasing/evaluations" class="btn-text">
              View All Evaluations
            </router-link>
          </div>
          <div class="table-container">
            <table class="evaluations-table">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Vendor</th>
                  <th>Quality</th>
                  <th>Delivery</th>
                  <th>Price</th>
                  <th>Service</th>
                  <th>Overall</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="evaluation in dashboardData.recentEvaluations" :key="evaluation.evaluation_id">
                  <td>{{ formatDate(evaluation.evaluation_date) }}</td>
                  <td>
                    <router-link :to="`/purchasing/vendors/${evaluation.vendor_id}`" class="vendor-link">
                      {{ evaluation.vendor.name }}
                    </router-link>
                  </td>
                  <td>
                    <div class="score-badge small" :class="getScoreClass(evaluation.quality_score)">
                      {{ evaluation.quality_score }}
                    </div>
                  </td>
                  <td>
                    <div class="score-badge small" :class="getScoreClass(evaluation.delivery_score)">
                      {{ evaluation.delivery_score }}
                    </div>
                  </td>
                  <td>
                    <div class="score-badge small" :class="getScoreClass(evaluation.price_score)">
                      {{ evaluation.price_score }}
                    </div>
                  </td>
                  <td>
                    <div class="score-badge small" :class="getScoreClass(evaluation.service_score)">
                      {{ evaluation.service_score }}
                    </div>
                  </td>
                  <td>
                    <div class="score-badge small" :class="getScoreClass(evaluation.total_score)">
                      {{ evaluation.total_score.toFixed(2) }}
                    </div>
                  </td>
                  <td class="actions-cell">
                    <router-link :to="`/purchasing/evaluations/${evaluation.evaluation_id}`" class="btn-icon" title="View Details">
                      <i class="fas fa-eye"></i>
                    </router-link>
                  </td>
                </tr>
                <tr v-if="!dashboardData.recentEvaluations?.length">
                  <td colspan="8" class="empty-table">No recent evaluations found</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </template>

  <script>
  import * as d3 from 'd3';

  export default {
    name: 'EvaluationMetricsDashboard',
    data() {
      return {
        loading: true,
        selectedPeriod: 'quarter',
        dashboardData: {
          totalVendors: 0,
          totalEvaluations: 0,
          averageScore: 0,
          scoreTrend: 0,
          averageDeliveryScore: 0,
          averagePriceScore: 0,
          performanceDistribution: {
            excellent: 0,
            good: 0,
            average: 0,
            poor: 0
          },
          metricsComparison: {
            quality: 0,
            delivery: 0,
            price: 0,
            service: 0
          },
          topVendors: [],
          bottomVendors: [],
          recentEvaluations: []
        },
        charts: {
          distribution: null,
          metrics: null
        }
      };
    },
    mounted() {
      this.loadDashboardData();
      window.addEventListener('resize', this.handleResize);
    },
    beforeUnmount() {
      window.removeEventListener('resize', this.handleResize);
    },
    methods: {
      async loadDashboardData() {
        this.loading = true;

        try {
          // In a real implementation, you would call your backend API
          // Here we're using a simulated API response with mock data

          // Simulate API call to get dashboard data
          // const response = await axios.get('/api/evaluations/dashboard', {
          //   params: { period: this.selectedPeriod }
          // });
          // const data = response.data;

          // For demo purposes, we'll use mock data
          const data = await this.getMockDashboardData();

          // Update dashboard data
          this.dashboardData = data;

          // After data is loaded, render charts
          this.$nextTick(() => {
            this.renderCharts();
          });
        } catch (error) {
          console.error("Error loading dashboard data:", error);
          // You could add a notification system here
        } finally {
          this.loading = false;
        }
      },

      // This is a mock function that would be replaced by an actual API call
      async getMockDashboardData() {
        // Simulate API latency
        await new Promise(resolve => setTimeout(resolve, 800));

        // Mock data for demonstration
        return {
          totalVendors: 48,
          totalEvaluations: 124,
          averageScore: 3.67,
          scoreTrend: 0.12,
          averageDeliveryScore: 3.42,
          averagePriceScore: 3.28,
          performanceDistribution: {
            excellent: 14,
            good: 21,
            average: 9,
            poor: 4
          },
          metricsComparison: {
            quality: 3.82,
            delivery: 3.42,
            price: 3.28,
            service: 3.65
          },
          topVendors: [
            { rank: 1, vendor_id: 101, name: 'Acme Supplies Ltd.', category: 'Office Supplies', score: 4.78 },
            { rank: 2, vendor_id: 203, name: 'TechPro Systems', category: 'Electronics', score: 4.65 },
            { rank: 3, vendor_id: 156, name: 'Global Materials Inc.', category: 'Raw Materials', score: 4.52 },
            { rank: 4, vendor_id: 187, name: 'Fast Logistics Partners', category: 'Logistics', score: 4.38 },
            { rank: 5, vendor_id: 142, name: 'Quality Manufacturing Co.', category: 'Manufacturing', score: 4.25 }
          ],
          bottomVendors: [
            { rank: 1, vendor_id: 132, name: 'Budget Suppliers Inc.', category: 'Office Supplies', score: 2.15 },
            { rank: 2, vendor_id: 178, name: 'Discount Warehouse', category: 'Various', score: 2.38 },
            { rank: 3, vendor_id: 291, name: 'Last-Minute Delivery', category: 'Logistics', score: 2.52 },
            { rank: 4, vendor_id: 265, name: 'Bargain Components Ltd.', category: 'Electronics', score: 2.68 },
            { rank: 5, vendor_id: 189, name: 'Value Distributors LLC', category: 'Distribution', score: 2.75 }
          ],
          recentEvaluations: [
            {
              evaluation_id: 501,
              evaluation_date: '2025-04-15',
              vendor_id: 101,
              vendor: { name: 'Acme Supplies Ltd.' },
              quality_score: 4.8,
              delivery_score: 4.7,
              price_score: 4.5,
              service_score: 4.9,
              total_score: 4.72
            },
            {
              evaluation_id: 502,
              evaluation_date: '2025-04-12',
              vendor_id: 203,
              vendor: { name: 'TechPro Systems' },
              quality_score: 4.6,
              delivery_score: 4.5,
              price_score: 4.7,
              service_score: 4.8,
              total_score: 4.65
            },
            {
              evaluation_id: 503,
              evaluation_date: '2025-04-10',
              vendor_id: 265,
              vendor: { name: 'Bargain Components Ltd.' },
              quality_score: 2.5,
              delivery_score: 2.3,
              price_score: 3.6,
              service_score: 2.4,
              total_score: 2.70
            },
            {
              evaluation_id: 504,
              evaluation_date: '2025-04-08',
              vendor_id: 156,
              vendor: { name: 'Global Materials Inc.' },
              quality_score: 4.4,
              delivery_score: 4.6,
              price_score: 4.3,
              service_score: 4.7,
              total_score: 4.50
            },
            {
              evaluation_id: 505,
              evaluation_date: '2025-04-05',
              vendor_id: 178,
              vendor: { name: 'Discount Warehouse' },
              quality_score: 2.2,
              delivery_score: 2.1,
              price_score: 3.9,
              service_score: 1.6,
              total_score: 2.45
            }
          ]
        };
      },

      getPeriodTitle() {
        switch (this.selectedPeriod) {
          case 'month':
            return 'Last 30 Days';
          case 'quarter':
            return 'Last 90 Days';
          case 'year':
            return 'Last 12 Months';
          case 'all':
            return 'All Time';
          default:
            return '';
        }
      },

      formatDate(dateString) {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
          year: 'numeric',
          month: 'short',
          day: 'numeric'
        });
      },

      getScoreClass(score) {
        if (score >= 4) return 'score-excellent';
        if (score >= 3) return 'score-good';
        if (score >= 2) return 'score-average';
        return 'score-poor';
      },

      refreshData() {
        this.loadDashboardData();
      },

      handleResize() {
        if (this.resizeTimeout) clearTimeout(this.resizeTimeout);
        this.resizeTimeout = setTimeout(() => {
          this.renderCharts();
        }, 300);
      },

      renderCharts() {
        this.renderDistributionChart();
        this.renderMetricsChart();
      },

      renderDistributionChart() {
        const container = this.$refs.performanceDistribution;
        if (!container) return;

        // Clear previous chart
        d3.select(container).selectAll('*').remove();

        const width = container.clientWidth;
        const height = container.clientHeight || 300;
        const margin = { top: 20, right: 30, bottom: 40, left: 40 };
        const innerWidth = width - margin.left - margin.right;
        const innerHeight = height - margin.top - margin.bottom;

        // Create SVG
        const svg = d3.select(container)
          .append('svg')
          .attr('width', width)
          .attr('height', height)
          .append('g')
          .attr('transform', `translate(${margin.left}, ${margin.top})`);

        // Data for the donut chart
        const data = [
          { category: 'Excellent', value: this.dashboardData.performanceDistribution.excellent, color: 'var(--success-color)' },
          { category: 'Good', value: this.dashboardData.performanceDistribution.good, color: '#3b82f6' },
          { category: 'Average', value: this.dashboardData.performanceDistribution.average, color: 'var(--warning-color)' },
          { category: 'Poor', value: this.dashboardData.performanceDistribution.poor, color: 'var(--danger-color)' }
        ];

        // Calculate total for percentage
        const total = data.reduce((sum, d) => sum + d.value, 0);

        // Calculate the position of each group on the pie
        const pie = d3.pie()
          .sort(null)
          .value(d => d.value);

        const arcData = pie(data);

        // Inner and outer radius for the donut
        const radius = Math.min(innerWidth, innerHeight) / 2;
        const innerRadius = radius * 0.6;
        const outerRadius = radius;

        // Arc generator
        const arc = d3.arc()
          .innerRadius(innerRadius)
          .outerRadius(outerRadius);

        // Center the pie chart
        const g = svg.append('g')
          .attr('transform', `translate(${innerWidth / 2}, ${innerHeight / 2})`);

        // Create the arcs
        const arcs = g.selectAll('.arc')
          .data(arcData)
          .enter()
          .append('g')
          .attr('class', 'arc');

        // Add color to each arc
        arcs.append('path')
          .attr('d', arc)
          .attr('fill', d => d.data.color)
          .attr('stroke', 'white')
          .style('stroke-width', '2px')
          .style('opacity', 0.9);

        // Add percentages label
        arcs.append('text')
          .attr('transform', d => {
            const [x, y] = arc.centroid(d);
            return `translate(${x}, ${y})`;
          })
          .attr('dy', '.35em')
          .attr('text-anchor', 'middle')
          .attr('fill', 'white')
          .attr('font-size', '12px')
          .attr('font-weight', 'bold')
          .text(d => {
            const percentage = (d.data.value / total * 100).toFixed(0);
            return percentage > 5 ? `${percentage}%` : '';
          });

        // Add total in the center
        g.append('text')
          .attr('text-anchor', 'middle')
          .attr('dy', '0em')
          .attr('font-size', '16px')
          .attr('font-weight', 'bold')
          .text(total);

        g.append('text')
          .attr('text-anchor', 'middle')
          .attr('dy', '1.2em')
          .attr('font-size', '12px')
          .attr('fill', 'var(--gray-600)')
          .text('Vendors');
      },

      renderMetricsChart() {
        const container = this.$refs.metricsComparison;
        if (!container) return;

        // Clear previous chart
        d3.select(container).selectAll('*').remove();

        const width = container.clientWidth;
        const height = container.clientHeight || 300;
        const margin = { top: 40, right: 20, bottom: 40, left: 60 };
        const innerWidth = width - margin.left - margin.right;
        const innerHeight = height - margin.top - margin.bottom;

        // Create SVG
        const svg = d3.select(container)
          .append('svg')
          .attr('width', width)
          .attr('height', height)
          .append('g')
          .attr('transform', `translate(${margin.left}, ${margin.top})`);

        // Data for the bar chart
        const data = [
          { metric: 'Quality', value: this.dashboardData.metricsComparison.quality, color: '#3b82f6' },
          { metric: 'Delivery', value: this.dashboardData.metricsComparison.delivery, color: 'var(--success-color)' },
          { metric: 'Price', value: this.dashboardData.metricsComparison.price, color: 'var(--warning-color)' },
          { metric: 'Service', value: this.dashboardData.metricsComparison.service, color: '#8b5cf6' } // Purple
        ];

        // X scale
        const x = d3.scaleBand()
          .domain(data.map(d => d.metric))
          .range([0, innerWidth])
          .padding(0.3);

        // Y scale
        const y = d3.scaleLinear()
          .domain([0, 5]) // Score is from 0 to 5
          .range([innerHeight, 0]);

        // Add X axis
        svg.append('g')
          .attr('transform', `translate(0, ${innerHeight})`)
          .call(d3.axisBottom(x))
          .selectAll('text')
          .attr('font-size', '12px');

        // Add Y axis
        svg.append('g')
          .call(d3.axisLeft(y).ticks(5))
          .selectAll('text')
          .attr('font-size', '12px');

        // Add Y axis label
        svg.append('text')
          .attr('transform', 'rotate(-90)')
          .attr('y', -margin.left + 15)
          .attr('x', -innerHeight / 2)
          .attr('dy', '1em')
          .attr('text-anchor', 'middle')
          .attr('font-size', '12px')
          .text('Average Score');

        // Add bars
        svg.selectAll('.bar')
          .data(data)
          .enter()
          .append('rect')
          .attr('class', 'bar')
          .attr('x', d => x(d.metric))
          .attr('width', x.bandwidth())
          .attr('y', d => y(d.value))
          .attr('height', d => innerHeight - y(d.value))
          .attr('fill', d => d.color)
          .attr('rx', 4)
          .attr('ry', 4);

        // Add value labels on top of bars
        svg.selectAll('.label')
          .data(data)
          .enter()
          .append('text')
          .attr('class', 'label')
          .attr('text-anchor', 'middle')
          .attr('x', d => x(d.metric) + x.bandwidth() / 2)
          .attr('y', d => y(d.value) - 5)
          .attr('font-size', '12px')
          .attr('font-weight', 'bold')
          .text(d => d.value.toFixed(2));

        // Add threshold line for "good" performance
        svg.append('line')
          .attr('x1', 0)
          .attr('x2', innerWidth)
          .attr('y1', y(3))
          .attr('y2', y(3))
          .attr('stroke', '#94a3b8')
          .attr('stroke-width', 1)
          .attr('stroke-dasharray', '4');

        svg.append('text')
          .attr('x', innerWidth)
          .attr('y', y(3) - 5)
          .attr('text-anchor', 'end')
          .attr('font-size', '10px')
          .attr('fill', '#94a3b8')
          .text('Good');
      }
    }
  };
  </script>

  <style scoped>
  .dashboard-container {
    width: 100%;
    padding: 1rem;
    background-color: var(--gray-50);
  }

  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
  }

  .header-actions {
    display: flex;
    gap: 1rem;
    align-items: center;
  }

  .btn-icon-text {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
    background-color: white;
    border: 1px solid var(--gray-300);
    color: var(--gray-700);
    font-weight: 500;
    transition: all 0.2s;
  }

  .btn-icon-text:hover {
    border-color: var(--gray-400);
    color: var(--gray-800);
  }

  .period-selector {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .period-selector label {
    font-weight: 500;
    color: var(--gray-700);
  }

  .form-select {
    padding: 0.5rem;
    border: 1px solid var(--gray-300);
    border-radius: 0.375rem;
    font-size: 0.875rem;
    background-color: white;
  }

  .loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(255, 255, 255, 0.8);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 10;
  }

  .loading-spinner {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
    color: var(--primary-color);
  }

  .loading-spinner i {
    font-size: 3rem;
  }

  .dashboard-content {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
  }

  .section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
  }

  .section-header h2 {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--gray-800);
  }

  .section-subtitle {
    color: var(--gray-500);
    font-size: 0.875rem;
  }

  .kpi-section {
    margin-bottom: 1.5rem;
  }

  .kpi-cards {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 1rem;
  }

  .kpi-card {
    background-color: white;
    border-radius: 0.5rem;
    padding: 1.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    position: relative;
  }

  .kpi-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    border-radius: 0.375rem;
    background-color: rgba(37, 99, 235, 0.1);
    color: var(--primary-color);
    font-size: 1.5rem;
  }

  .kpi-content {
    flex: 1;
  }

  .kpi-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--gray-800);
    line-height: 1.25;
  }

  .kpi-label {
    font-size: 0.875rem;
    color: var(--gray-500);
  }

  .kpi-trend {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.75rem;
    font-weight: 600;
    position: absolute;
    top: 0.75rem;
    right: 1rem;
  }

  .trend-up {
    color: var(--success-color);
  }

  .trend-down {
    color: var(--danger-color);
  }

  .trend-neutral {
    color: var(--gray-500);
  }

  .chart-section {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(450px, 1fr));
    gap: 1.5rem;
    margin-bottom: 1.5rem;
  }

  .chart-container {
    background-color: white;
    border-radius: 0.5rem;
    padding: 1.25rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  }

  .chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    flex-wrap: wrap;
  }

  .chart-header h3 {
    font-size: 1rem;
    font-weight: 600;
    color: var(--gray-800);
  }

  .chart-legend {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
  }

  .legend-item {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.75rem;
    color: var(--gray-600);
  }

  .legend-color {
    width: 12px;
    height: 12px;
    border-radius: 2px;
  }

  .bg-excellent {
    background-color: var(--success-color);
  }

  .bg-good {
    background-color: #3b82f6;
  }

  .bg-average {
    background-color: var(--warning-color);
  }

  .bg-poor {
    background-color: var(--danger-color);
  }

  .chart-canvas {
    width: 100%;
    height: 300px;
  }

  .performers-section {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
    margin-bottom: 1.5rem;
  }

  .performers-container {
    background-color: white;
    border-radius: 0.5rem;
    padding: 1.25rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  }

  .performers-container h3 {
    font-size: 1rem;
    font-weight: 600;
    color: var(--gray-800);
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid var(--gray-200);
  }

  .performers-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }

  .performer-card {
    display: flex;
    align-items: center;
    padding: 0.75rem;
    border-radius: 0.375rem;
    background-color: var(--gray-50);
    gap: 0.75rem;
  }

  .performer-rank {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: var(--gray-200);
    color: var(--gray-700);
    font-weight: 600;
    font-size: 0.875rem;
    border-radius: 50%;
  }

  .performer-info {
    flex: 1;
  }

  .performer-name {
    font-weight: 500;
    color: var(--gray-800);
  }

  .performer-category {
    font-size: 0.75rem;
    color: var(--gray-500);
  }

  .performer-score {
    margin-right: 0.5rem;
  }

  .recent-evaluations-section {
    background-color: white;
    border-radius: 0.5rem;
    padding: 1.25rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  }

  .btn-text {
    color: var(--primary-color);
    font-weight: 500;
    font-size: 0.875rem;
    transition: color 0.2s;
  }

  .btn-text:hover {
    color: var(--primary-dark);
    text-decoration: underline;
  }

  .table-container {
    overflow-x: auto;
  }

  .evaluations-table {
    width: 100%;
    border-collapse: collapse;
  }

  .evaluations-table th, .evaluations-table td {
    padding: 0.75rem 1rem;
    text-align: left;
    border-bottom: 1px solid var(--gray-200);
  }

  .evaluations-table th {
    background-color: var(--gray-100);
    font-weight: 600;
    font-size: 0.875rem;
    color: var(--gray-700);
  }

  .vendor-link {
    color: var(--gray-800);
    font-weight: 500;
    transition: color 0.2s;
  }

  .vendor-link:hover {
    color: var(--primary-color);
    text-decoration: underline;
  }

  .score-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    color: white;
    font-weight: 600;
  }

  .score-badge.small {
    width: 28px;
    height: 28px;
    font-size: 0.75rem;
  }

  .score-excellent {
    background-color: var(--success-color);
  }

  .score-good {
    background-color: #3b82f6; /* Blue */
  }

  .score-average {
    background-color: var(--warning-color);
  }

  .score-poor {
    background-color: var(--danger-color);
  }

  .actions-cell {
    white-space: nowrap;
  }

  .btn-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 4px;
    color: var(--gray-600);
    background-color: var(--gray-100);
    transition: all 0.2s;
  }

  .btn-icon:hover {
    background-color: var(--gray-200);
    color: var(--gray-800);
  }

  .empty-performers, .empty-table {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 1.5rem;
    color: var(--gray-500);
    font-size: 0.875rem;
  }

  .empty-table {
    text-align: center;
  }

  @media (max-width: 768px) {
    .kpi-cards, .chart-section, .performers-section {
      grid-template-columns: 1fr;
    }

    .chart-header {
      flex-direction: column;
      align-items: flex-start;
    }

    .chart-legend {
      margin-top: 0.5rem;
    }
  }
  </style>
