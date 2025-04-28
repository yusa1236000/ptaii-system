<!-- src/views/purchasing/VendorPerformanceAnalysis.vue -->
<template>
    <div class="vendor-performance-container">
      <div class="page-header">
        <h1 class="text-2xl font-semibold">Vendor Performance Analysis</h1>
        <div class="actions">
          <router-link to="/purchasing/vendors" class="btn-outline">
            <i class="fas fa-arrow-left mr-2"></i> Back to Vendors
          </router-link>
        </div>
      </div>

      <div class="vendor-info-panel" v-if="vendor">
        <div class="vendor-profile">
          <div class="vendor-avatar">
            <i class="fas fa-building"></i>
          </div>
          <div class="vendor-details">
            <h2>{{ vendor.name }}</h2>
            <p class="vendor-code">{{ vendor.vendor_code }}</p>
            <div class="vendor-contacts">
              <div class="contact-item">
                <i class="fas fa-user"></i>
                <span>{{ vendor.contact_person || 'No contact person' }}</span>
              </div>
              <div class="contact-item">
                <i class="fas fa-envelope"></i>
                <span>{{ vendor.email || 'No email' }}</span>
              </div>
              <div class="contact-item">
                <i class="fas fa-phone"></i>
                <span>{{ vendor.phone || 'No phone' }}</span>
              </div>
            </div>
          </div>
          <div class="vendor-status">
            <div class="status-badge" :class="getStatusClass(vendor.status)">
              {{ vendor.status }}
            </div>
          </div>
        </div>
      </div>

      <div class="filters-panel">
        <div class="period-selector">
          <label for="period">Performance Period</label>
          <select id="period" v-model="selectedPeriod" class="form-control" @change="loadPerformanceData">
            <option value="month">Last Month</option>
            <option value="quarter">Last Quarter</option>
            <option value="year">Last Year</option>
            <option value="all">All Time</option>
          </select>
        </div>
      </div>

      <div class="performance-overview" v-if="performanceData">
        <div class="metrics-summary">
          <div class="metric-card">
            <div class="metric-label">Quality</div>
            <div class="metric-value" :class="getScoreClass(performanceData.averages.quality)">
              {{ performanceData.averages.quality }}
            </div>
          </div>
          <div class="metric-card">
            <div class="metric-label">Delivery</div>
            <div class="metric-value" :class="getScoreClass(performanceData.averages.delivery)">
              {{ performanceData.averages.delivery }}
            </div>
          </div>
          <div class="metric-card">
            <div class="metric-label">Price</div>
            <div class="metric-value" :class="getScoreClass(performanceData.averages.price)">
              {{ performanceData.averages.price }}
            </div>
          </div>
          <div class="metric-card">
            <div class="metric-label">Service</div>
            <div class="metric-value" :class="getScoreClass(performanceData.averages.service)">
              {{ performanceData.averages.service }}
            </div>
          </div>
          <div class="metric-card total-score">
            <div class="metric-label">Overall</div>
            <div class="metric-value total" :class="getScoreClass(performanceData.averages.total)">
              {{ performanceData.averages.total }}
            </div>
            <div class="metric-descriptor">{{ getPerformanceDescription(performanceData.averages.total) }}</div>
          </div>
        </div>

        <div class="performance-charts">
          <div class="chart-container">
            <h3>Performance Radar Chart</h3>
            <div ref="radarChartContainer" class="radar-chart"></div>
          </div>
          <div class="chart-container">
            <h3>Performance Trend</h3>
            <div ref="trendChartContainer" class="trend-chart"></div>
          </div>
        </div>

        <div class="evaluation-history">
          <h3>Evaluation History</h3>
          <div v-if="loading" class="loading-container">
            <i class="fas fa-spinner fa-spin text-4xl text-primary"></i>
            <p>Loading evaluations...</p>
          </div>

          <div v-else-if="performanceData.evaluations.length === 0" class="empty-state">
            <i class="fas fa-clipboard-check text-5xl mb-4 text-gray-400"></i>
            <h3>No evaluations found</h3>
            <p>No evaluation records for this vendor in the selected period</p>
          </div>

          <div v-else class="table-container">
            <table class="evaluations-table">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Quality</th>
                  <th>Delivery</th>
                  <th>Price</th>
                  <th>Service</th>
                  <th>Total</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="evaluation in performanceData.evaluations" :key="evaluation.evaluation_id">
                  <td>{{ formatDate(evaluation.evaluation_date) }}</td>
                  <td>
                    <div class="score-badge" :class="getScoreClass(evaluation.quality_score)">
                      {{ evaluation.quality_score }}
                    </div>
                  </td>
                  <td>
                    <div class="score-badge" :class="getScoreClass(evaluation.delivery_score)">
                      {{ evaluation.delivery_score }}
                    </div>
                  </td>
                  <td>
                    <div class="score-badge" :class="getScoreClass(evaluation.price_score)">
                      {{ evaluation.price_score }}
                    </div>
                  </td>
                  <td>
                    <div class="score-badge" :class="getScoreClass(evaluation.service_score)">
                      {{ evaluation.service_score }}
                    </div>
                  </td>
                  <td>
                    <div class="score-badge total-score" :class="getScoreClass(evaluation.total_score)">
                      {{ evaluation.total_score.toFixed(2) }}
                    </div>
                  </td>
                  <td class="actions-cell">
                    <router-link :to="`/purchasing/evaluations/${evaluation.evaluation_id}`" class="btn-icon" title="View Details">
                      <i class="fas fa-eye"></i>
                    </router-link>
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
  import axios from "axios";
  import * as d3 from 'd3';

  export default {
    name: "VendorPerformanceAnalysis",
    props: {
      id: {
        type: [Number, String],
        required: true
      }
    },
    data() {
      return {
        vendor: null,
        performanceData: null,
        selectedPeriod: 'quarter',
        loading: false,
        charts: {
          radar: null,
          trend: null
        }
      };
    },
    created() {
      this.loadVendorData();
      this.loadPerformanceData();
    },
    mounted() {
      window.addEventListener('resize', this.handleResize);
    },
    beforeUnmount() {
      window.removeEventListener('resize', this.handleResize);
    },
    methods: {
      async loadVendorData() {
        try {
          const response = await axios.get(`/api/vendors/${this.id}`);
          this.vendor = response.data.data;
        } catch (error) {
          console.error("Error loading vendor data:", error);
          // You can add error handling/notification here
        }
      },
      async loadPerformanceData() {
        this.loading = true;
        try {
          const response = await axios.get('/api/vendor-performance', {
            params: {
              vendor_id: this.id,
              period: this.selectedPeriod
            }
          });

          this.performanceData = response.data.data;

          // After data is loaded, render charts
          this.$nextTick(() => {
            this.renderCharts();
          });
        } catch (error) {
          console.error("Error loading performance data:", error);
          // You can add error handling/notification here
        } finally {
          this.loading = false;
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
      getStatusClass(status) {
        switch (status?.toLowerCase()) {
          case 'active':
            return 'status-active';
          case 'inactive':
            return 'status-inactive';
          case 'suspended':
            return 'status-suspended';
          default:
            return 'status-unknown';
        }
      },
      getScoreClass(score) {
        if (score >= 4) return 'score-excellent';
        if (score >= 3) return 'score-good';
        if (score >= 2) return 'score-average';
        return 'score-poor';
      },
      getPerformanceDescription(score) {
        if (score >= 4.5) return 'Outstanding performer';
        if (score >= 4) return 'Excellent performer';
        if (score >= 3.5) return 'Very good performer';
        if (score >= 3) return 'Good performer';
        if (score >= 2.5) return 'Average performer';
        if (score >= 2) return 'Below average performer';
        return 'Poor performer, consider finding alternatives';
      },
      handleResize() {
        // Debounce and redraw charts when window is resized
        if (this.resizeTimeout) clearTimeout(this.resizeTimeout);
        this.resizeTimeout = setTimeout(() => {
          this.renderCharts();
        }, 300);
      },
      renderCharts() {
        if (!this.performanceData) return;

        this.renderRadarChart();
        this.renderTrendChart();
      },
      renderRadarChart() {
        const container = this.$refs.radarChartContainer;
        if (!container) return;

        // Clear previous chart
        d3.select(container).selectAll('*').remove();

        const margin = { top: 50, right: 80, bottom: 50, left: 80 };
        const width = container.clientWidth - margin.left - margin.right;
        const height = container.clientHeight - margin.top - margin.bottom;
        const radius = Math.min(width, height) / 2;

        const svg = d3.select(container)
          .append('svg')
          .attr('width', width + margin.left + margin.right)
          .attr('height', height + margin.top + margin.bottom)
          .append('g')
          .attr('transform', `translate(${width / 2 + margin.left}, ${height / 2 + margin.top})`);

        // Data for radar chart
        const averages = this.performanceData.averages;
        const data = [
          { axis: "Quality", value: averages.quality / 5 },
          { axis: "Delivery", value: averages.delivery / 5 },
          { axis: "Price", value: averages.price / 5 },
          { axis: "Service", value: averages.service / 5 }
        ];

        // Number of axes
        const totalAxes = data.length;

        // Angle for each axis
        const angleSlice = (Math.PI * 2) / totalAxes;

        // Scale for radius
        const rScale = d3.scaleLinear()
          .range([0, radius])
          .domain([0, 1]);

        // Create the circular grid
        const axisGrid = svg.append("g").attr("class", "axis-grid");

        // Draw the circular grid lines
        const levels = 5;
        for (let j = 0; j < levels; j++) {
          const levelFactor = radius * ((j + 1) / levels);

          // Text for each level
          axisGrid.append("text")
            .attr("class", "legend")
            .attr("x", 4)
            .attr("y", -levelFactor + 4)
            .attr("dy", "0.4em")
            .style("font-size", "10px")
            .style("fill", "#737373")
            .text((j + 1));
        }

        // Create the axes
        const axes = axisGrid.selectAll(".axis")
          .data(data)
          .enter()
          .append("g")
          .attr("class", "axis");

        // Append lines
        axes.append("line")
          .attr("x1", 0)
          .attr("y1", 0)
          .attr("x2", (d, i) => rScale(1.1) * Math.cos(angleSlice * i - Math.PI / 2))
          .attr("y2", (d, i) => rScale(1.1) * Math.sin(angleSlice * i - Math.PI / 2))
          .attr("class", "line")
          .style("stroke", "#999")
          .style("stroke-width", "1px");

        // Append axis labels
        axes.append("text")
          .attr("class", "legend")
          .attr("text-anchor", "middle")
          .attr("dy", "0.35em")
          .attr("x", (d, i) => rScale(1.15) * Math.cos(angleSlice * i - Math.PI / 2))
          .attr("y", (d, i) => rScale(1.15) * Math.sin(angleSlice * i - Math.PI / 2))
          .text(d => d.axis)
          .style("font-size", "11px")
          .style("fill", "#333");

        // Create the radar chart blobs
        const radarLine = d3.lineRadial()
          .curve(d3.curveLinearClosed)
          .radius(d => rScale(d.value))
          .angle((d, i) => i * angleSlice);

        // Append the paths
        svg.append("path")
          .datum(data)
          .attr("class", "radar-area")
          .attr("d", radarLine)
          .style("fill", "#2563eb")
          .style("fill-opacity", 0.7)
          .style("stroke", "#2563eb")
          .style("stroke-width", "2px");

        // Create circles for each point
        svg.selectAll(".radar-circle")
          .data(data)
          .enter()
          .append("circle")
          .attr("class", "radar-circle")
          .attr("r", 5)
          .attr("cx", (d, i) => rScale(d.value) * Math.cos(angleSlice * i - Math.PI / 2))
          .attr("cy", (d, i) => rScale(d.value) * Math.sin(angleSlice * i - Math.PI / 2))
          .style("fill", "#fff")
          .style("stroke", "#2563eb")
          .style("stroke-width", "2px");
      },
      renderTrendChart() {
        const container = this.$refs.trendChartContainer;
        if (!container || !this.performanceData.evaluations || this.performanceData.evaluations.length === 0) return;

        // Clear previous chart
        d3.select(container).selectAll('*').remove();

        const margin = { top: 40, right: 40, bottom: 60, left: 60 };
        const width = container.clientWidth - margin.left - margin.right;
        const height = container.clientHeight - margin.top - margin.bottom;

        const svg = d3.select(container)
          .append('svg')
          .attr('width', width + margin.left + margin.right)
          .attr('height', height + margin.top + margin.bottom)
          .append('g')
          .attr('transform', `translate(${margin.left}, ${margin.top})`);

        // Process data for trend chart - sort by date
        const evaluations = [...this.performanceData.evaluations]
          .sort((a, b) => new Date(a.evaluation_date) - new Date(b.evaluation_date));

        // X scale - time
        const xScale = d3.scaleTime()
          .domain(d3.extent(evaluations, d => new Date(d.evaluation_date)))
          .range([0, width]);

        // Y scale - score (1-5)
        const yScale = d3.scaleLinear()
          .domain([1, 5])
          .range([height, 0]);

        // Line generators for each metric
        const line = metric => {
          return d3.line()
            .x(d => xScale(new Date(d.evaluation_date)))
            .y(d => yScale(d[metric]))
            .curve(d3.curveMonotoneX);
        };

        // Generate color scale for lines
        const colorScale = d3.scaleOrdinal()
          .domain(['quality_score', 'delivery_score', 'price_score', 'service_score', 'total_score'])
          .range(['#3b82f6', '#059669', '#d97706', '#8b5cf6', '#dc2626']);

        // Draw X axis
        svg.append('g')
          .attr('transform', `translate(0, ${height})`)
          .call(d3.axisBottom(xScale).ticks(5).tickFormat(d3.timeFormat("%b %Y")))
          .selectAll("text")
          .style("text-anchor", "end")
          .attr("dx", "-.8em")
          .attr("dy", ".15em")
          .attr("transform", "rotate(-45)");

        // Draw Y axis
        svg.append('g')
          .call(d3.axisLeft(yScale).ticks(5));

        // Add X axis label
        svg.append("text")
          .attr("text-anchor", "middle")
          .attr("x", width / 2)
          .attr("y", height + margin.bottom - 10)
          .text("Date")
          .style("font-size", "12px");

        // Add Y axis label
        svg.append("text")
          .attr("text-anchor", "middle")
          .attr("transform", "rotate(-90)")
          .attr("y", -margin.left + 15)
          .attr("x", -height / 2)
          .text("Score")
          .style("font-size", "12px");

        // Add title
        svg.append("text")
          .attr("x", width / 2)
          .attr("y", -margin.top / 2)
          .attr("text-anchor", "middle")
          .style("font-size", "14px")
          .style("font-weight", "bold")
          .text("Vendor Performance Trend");

        // Draw lines for each metric
        const metrics = [
          { id: 'quality_score', name: 'Quality' },
          { id: 'delivery_score', name: 'Delivery' },
          { id: 'price_score', name: 'Price' },
          { id: 'service_score', name: 'Service' },
          { id: 'total_score', name: 'Overall' }
        ];

        metrics.forEach(metric => {
          svg.append("path")
            .datum(evaluations)
            .attr("fill", "none")
            .attr("stroke", colorScale(metric.id))
            .attr("stroke-width", metric.id === 'total_score' ? 3 : 2)
            .attr("stroke-linejoin", "round")
            .attr("stroke-linecap", "round")
            .attr("d", line(metric.id));

          // Add dots for each data point
          svg.selectAll(`.dot-${metric.id}`)
            .data(evaluations)
            .enter()
            .append("circle")
            .attr("class", `dot-${metric.id}`)
            .attr("cx", d => xScale(new Date(d.evaluation_date)))
            .attr("cy", d => yScale(d[metric.id]))
            .attr("r", 4)
            .attr("fill", colorScale(metric.id));
        });

        // Add legend
        const legend = svg.append("g")
          .attr("class", "legend")
          .attr("transform", `translate(${width - 100}, 0)`);

        metrics.forEach((metric, i) => {
          const legendRow = legend.append("g")
            .attr("transform", `translate(0, ${i * 20})`);

          legendRow.append("rect")
            .attr("width", 10)
            .attr("height", 10)
            .attr("fill", colorScale(metric.id));

          legendRow.append("text")
            .attr("x", 15)
            .attr("y", 10)
            .attr("text-anchor", "start")
            .style("font-size", "12px")
            .text(metric.name);
        });
      }
    }
  };
  </script>

  <style scoped>
  .vendor-performance-container {
    width: 100%;
    padding: 1rem;
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  }

  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
  }

  .btn-outline {
    background-color: transparent;
    color: var(--gray-700);
    padding: 0.625rem 1.25rem;
    border: 1px solid var(--gray-300);
    border-radius: 0.375rem;
    font-weight: 500;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
  }

  .btn-outline:hover {
    border-color: var(--gray-500);
    color: var(--gray-800);
    text-decoration: none;
  }

  .vendor-info-panel {
    background-color: var(--gray-50);
    border-radius: 0.5rem;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
  }

  .vendor-profile {
    display: flex;
    align-items: center;
    gap: 1.5rem;
  }

  .vendor-avatar {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 80px;
    height: 80px;
    background-color: var(--primary-color);
    color: white;
    border-radius: 0.5rem;
    font-size: 2rem;
  }

  .vendor-details {
    flex: 1;
  }

  .vendor-details h2 {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
  }

  .vendor-code {
    color: var(--gray-500);
    font-size: 0.875rem;
    margin-bottom: 0.75rem;
  }

  .vendor-contacts {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
  }

  .contact-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--gray-600);
    font-size: 0.875rem;
  }

  .vendor-status {
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .status-badge {
    padding: 0.375rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
  }

  .status-active {
    background-color: rgba(5, 150, 105, 0.1);
    color: var(--success-color);
  }

  .status-inactive {
    background-color: rgba(100, 116, 139, 0.1);
    color: var(--gray-500);
  }

  .status-suspended {
    background-color: rgba(220, 38, 38, 0.1);
    color: var(--danger-color);
  }

  .status-unknown {
    background-color: rgba(217, 119, 6, 0.1);
    color: var(--warning-color);
  }

  .filters-panel {
    background-color: white;
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 1.5rem;
    border: 1px solid var(--gray-200);
  }

  .period-selector {
    display: flex;
    align-items: center;
    gap: 1rem;
  }

  .period-selector label {
    font-weight: 500;
    color: var(--gray-700);
    margin-bottom: 0;
  }

  .form-control {
    padding: 0.5rem;
    border: 1px solid var(--gray-300);
    border-radius: 0.375rem;
    font-size: 0.875rem;
    min-width: 150px;
  }

  .performance-overview {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
  }

  .metrics-summary {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    justify-content: space-between;
  }

  .metric-card {
    flex: 1;
    min-width: 150px;
    background-color: white;
    border-radius: 0.5rem;
    padding: 1rem;
    text-align: center;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    display: flex;
    flex-direction: column;
    align-items: center;
  }

  .metric-card.total-score {
    background-color: var(--gray-50);
    flex: 2;
  }

  .metric-label {
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: var(--gray-700);
  }

  .metric-value {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    color: white;
    font-size: 1.25rem;
    font-weight: 700;
  }

  .metric-value.total {
    width: 80px;
    height: 80px;
    font-size: 1.5rem;
  }

  .metric-descriptor {
    margin-top: 0.5rem;
    font-style: italic;
    color: var(--gray-600);
    font-size: 0.875rem;
  }

  .performance-charts {
    display: flex;
    flex-wrap: wrap;
    gap: 1.5rem;
  }

  .chart-container {
    flex: 1;
    min-width: 300px;
    background-color: white;
    border-radius: 0.5rem;
    padding: 1rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  }

  .chart-container h3 {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 1rem;
    text-align: center;
  }

  .radar-chart, .trend-chart {
    width: 100%;
    height: 300px;
  }

  .evaluation-history {
    background-color: white;
    border-radius: 0.5rem;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  }

  .evaluation-history h3 {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 1rem;
  }

  .loading-container, .empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem;
    color: var(--gray-500);
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

  .total-score {
    width: 50px;
    border-radius: 18px;
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
    width: 32px;
    height: 32px;
    border-radius: 4px;
    color: var(--gray-600);
    background-color: var(--gray-100);
    transition: all 0.2s;
  }

  .btn-icon:hover {
    background-color: var(--gray-200);
    color: var(--gray-800);
  }

  @media (max-width: 768px) {
    .vendor-profile {
      flex-direction: column;
      text-align: center;
    }

    .vendor-contacts {
      justify-content: center;
    }

    .metrics-summary {
      flex-direction: column;
    }

    .metric-card {
      width: 100%;
    }

    .performance-charts {
      flex-direction: column;
    }
  }
  </style>
