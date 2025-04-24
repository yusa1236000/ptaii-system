<!-- src/views/purchasing/VendorEvaluationDashboard.vue -->
<template>
    <div class="dashboard-container">
        <div class="page-header">
            <h1>Vendor Evaluation Metrics</h1>
            <div class="header-actions">
                <div class="filter-group">
                    <label for="period-filter">Time Period:</label>
                    <select
                        id="period-filter"
                        v-model="selectedPeriod"
                        @change="fetchDashboardData"
                    >
                        <option value="month">Last Month</option>
                        <option value="quarter">Last Quarter</option>
                        <option value="year">Last Year</option>
                        <option value="all">All Time</option>
                    </select>
                </div>
            </div>
        </div>

        <div v-if="isLoading" class="loading-container">
            <div class="loading-spinner">
                <i class="fas fa-spinner fa-spin"></i>
            </div>
            <p>Loading dashboard data...</p>
        </div>

        <div v-else class="dashboard-content">
            <!-- Summary Cards Row -->
            <div class="summary-cards">
                <div class="summary-card">
                    <div class="card-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="card-content">
                        <div class="card-value">{{ metrics.totalVendors }}</div>
                        <div class="card-label">Total Vendors</div>
                    </div>
                </div>

                <div class="summary-card">
                    <div class="card-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="card-content">
                        <div class="card-value">
                            {{ metrics.totalEvaluations }}
                        </div>
                        <div class="card-label">Total Evaluations</div>
                    </div>
                </div>

                <div class="summary-card">
                    <div class="card-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="card-content">
                        <div class="card-value">
                            {{ formatScore(metrics.averageScore) }}
                        </div>
                        <div class="card-label">Average Score</div>
                    </div>
                </div>

                <div class="summary-card">
                    <div class="card-icon">
                        <i class="fas fa-thumbs-up"></i>
                    </div>
                    <div class="card-content">
                        <div class="card-value">
                            {{ metrics.topPerformersCount }}
                        </div>
                        <div class="card-label">Top Performers</div>
                        <div class="card-subtext">(Score ≥ 4.5)</div>
                    </div>
                </div>
            </div>

            <!-- Category Scores Chart -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h2 class="card-title">Average Category Scores</h2>
                </div>
                <div class="card-body">
                    <div class="chart-container radar-chart">
                        <RadarChart
                            :chart-data="radarChartData"
                            :chart-options="radarChartOptions"
                        />
                    </div>
                </div>
            </div>

            <!-- Score Distribution Chart -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h2 class="card-title">Vendor Score Distribution</h2>
                </div>
                <div class="card-body">
                    <div class="chart-container bar-chart">
                        <BarChart
                            :chart-data="distributionChartData"
                            :chart-options="distributionChartOptions"
                        />
                    </div>
                </div>
            </div>

            <!-- Top Vendors Table -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h2 class="card-title">Top Performing Vendors</h2>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Rank</th>
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
                                <tr
                                    v-for="(vendor, index) in topVendors"
                                    :key="vendor.vendor_id"
                                >
                                    <td>{{ index + 1 }}</td>
                                    <td>{{ vendor.name }}</td>
                                    <td>
                                        <div class="mini-score">
                                            {{
                                                formatScore(
                                                    vendor.scores.quality
                                                )
                                            }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="mini-score">
                                            {{
                                                formatScore(
                                                    vendor.scores.delivery
                                                )
                                            }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="mini-score">
                                            {{
                                                formatScore(vendor.scores.price)
                                            }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="mini-score">
                                            {{
                                                formatScore(
                                                    vendor.scores.service
                                                )
                                            }}
                                        </div>
                                    </td>
                                    <td>
                                        <div
                                            class="mini-score-badge"
                                            :class="
                                                getScoreClass(
                                                    vendor.scores.overall
                                                )
                                            "
                                        >
                                            {{
                                                formatScore(
                                                    vendor.scores.overall
                                                )
                                            }}
                                        </div>
                                    </td>
                                    <td>
                                        <router-link
                                            :to="`/purchasing/vendors/${vendor.vendor_id}/performance`"
                                            class="action-btn"
                                            title="View Performance"
                                        >
                                            <i class="fas fa-chart-line"></i>
                                        </router-link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Performance Trend Chart -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h2 class="card-title">Performance Trend Over Time</h2>
                </div>
                <div class="card-body">
                    <div class="chart-container line-chart">
                        <LineChart
                            :chart-data="trendChartData"
                            :chart-options="trendChartOptions"
                        />
                    </div>
                </div>
            </div>

            <!-- Recent Evaluations -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h2 class="card-title">Recent Evaluations</h2>
                    <router-link
                        to="/purchasing/evaluations"
                        class="btn btn-sm btn-outline"
                    >
                        View All
                    </router-link>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Vendor</th>
                                    <th>Overall Score</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="evaluation in recentEvaluations"
                                    :key="evaluation.evaluation_id"
                                >
                                    <td>
                                        {{
                                            formatDate(
                                                evaluation.evaluation_date
                                            )
                                        }}
                                    </td>
                                    <td>{{ evaluation.vendor.name }}</td>
                                    <td>
                                        <div
                                            class="mini-score-badge"
                                            :class="
                                                getScoreClass(
                                                    evaluation.total_score
                                                )
                                            "
                                        >
                                            {{
                                                formatScore(
                                                    evaluation.total_score
                                                )
                                            }}
                                        </div>
                                    </td>
                                    <td>
                                        <router-link
                                            :to="`/purchasing/evaluations/${evaluation.evaluation_id}`"
                                            class="action-btn"
                                            title="View Evaluation"
                                        >
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
    </div>
</template>

<script>
import { ref, computed, onMounted } from "vue";
import VendorEvaluationService from "@/services/VendorEvaluationService";
import {
    Chart as ChartJS,
    RadialLinearScale,
    PointElement,
    LineElement,
    CategoryScale,
    LinearScale,
    BarElement,
    Title,
    Tooltip,
    Legend,
    Filler,
} from "chart.js";
import {
    Radar as RadarChart,
    Bar as BarChart,
    Line as LineChart,
} from "vue-chartjs";

// Register the Chart.js components
ChartJS.register(
    RadialLinearScale,
    PointElement,
    LineElement,
    CategoryScale,
    LinearScale,
    BarElement,
    Title,
    Tooltip,
    Legend,
    Filler
);

export default {
    name: "VendorEvaluationDashboard",
    components: {
        RadarChart,
        BarChart,
        LineChart,
    },
    setup() {
        const selectedPeriod = ref("year");
        const isLoading = ref(true);
        const metrics = ref({
            totalVendors: 0,
            totalEvaluations: 0,
            averageScore: 0,
            topPerformersCount: 0,
            categoryAverages: {
                quality: 0,
                delivery: 0,
                price: 0,
                service: 0,
            },
            scoreDistribution: {
                excellent: 0,
                good: 0,
                average: 0,
                belowAverage: 0,
                poor: 0,
            },
        });
        const topVendors = ref([]);
        const recentEvaluations = ref([]);
        const trendData = ref([]);

        // Fetch dashboard data
        const fetchDashboardData = async () => {
            isLoading.value = true;

            try {
                // In a real application, you would call an API endpoint to get this data
                // For demonstration, we're simulating the data fetching
                const response = await VendorEvaluationService.getDashboardData(
                    {
                        period: selectedPeriod.value,
                    }
                );

                if (response.data && response.data.data) {
                    const data = response.data.data;

                    metrics.value = data.metrics;
                    topVendors.value = data.topVendors;
                    recentEvaluations.value = data.recentEvaluations;
                    trendData.value = data.trendData;
                }
            } catch (error) {
                console.error("Error fetching dashboard data:", error);
                // Initialize with default values in case of error
                metrics.value = {
                    totalVendors: 0,
                    totalEvaluations: 0,
                    averageScore: 0,
                    topPerformersCount: 0,
                    categoryAverages: {
                        quality: 0,
                        delivery: 0,
                        price: 0,
                        service: 0,
                    },
                    scoreDistribution: {
                        excellent: 0,
                        good: 0,
                        average: 0,
                        belowAverage: 0,
                        poor: 0,
                    },
                };
                topVendors.value = [];
                recentEvaluations.value = [];
                trendData.value = [];
            } finally {
                isLoading.value = false;
            }
        };

        // Format date
        const formatDate = (dateString) => {
            if (!dateString) return "N/A";
            const date = new Date(dateString);
            return date.toLocaleDateString("en-US", {
                year: "numeric",
                month: "short",
                day: "2-digit",
            });
        };

        // Format score to one decimal place
        const formatScore = (score) => {
            return parseFloat(score).toFixed(1);
        };

        // Get CSS class based on score
        const getScoreClass = (score) => {
            if (score >= 4.5) return "score-excellent";
            if (score >= 3.5) return "score-good";
            if (score >= 2.5) return "score-average";
            if (score >= 1.5) return "score-below-average";
            return "score-poor";
        };

        // Radar Chart Data
        const radarChartData = computed(() => {
            return {
                labels: ["Quality", "Delivery", "Price", "Service"],
                datasets: [
                    {
                        label: "Category Averages",
                        data: [
                            metrics.value.categoryAverages.quality,
                            metrics.value.categoryAverages.delivery,
                            metrics.value.categoryAverages.price,
                            metrics.value.categoryAverages.service,
                        ],
                        backgroundColor: "rgba(37, 99, 235, 0.2)",
                        borderColor: "rgba(37, 99, 235, 1)",
                        pointBackgroundColor: "rgba(37, 99, 235, 1)",
                        pointBorderColor: "#fff",
                        pointHoverBackgroundColor: "#fff",
                        pointHoverBorderColor: "rgba(37, 99, 235, 1)",
                    },
                ],
            };
        });

        // Radar Chart Options
        const radarChartOptions = {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                r: {
                    min: 0,
                    max: 5,
                    ticks: {
                        stepSize: 1,
                    },
                },
            },
            plugins: {
                legend: {
                    display: false,
                },
            },
        };

        // Distribution Chart Data
        const distributionChartData = computed(() => {
            return {
                labels: [
                    "Excellent (4.5-5)",
                    "Good (3.5-4.4)",
                    "Average (2.5-3.4)",
                    "Below Average (1.5-2.4)",
                    "Poor (1-1.4)",
                ],
                datasets: [
                    {
                        label: "Vendors",
                        data: [
                            metrics.value.scoreDistribution.excellent,
                            metrics.value.scoreDistribution.good,
                            metrics.value.scoreDistribution.average,
                            metrics.value.scoreDistribution.belowAverage,
                            metrics.value.scoreDistribution.poor,
                        ],
                        backgroundColor: [
                            "rgba(21, 128, 61, 0.8)",
                            "rgba(101, 163, 13, 0.8)",
                            "rgba(245, 158, 11, 0.8)",
                            "rgba(249, 115, 22, 0.8)",
                            "rgba(220, 38, 38, 0.8)",
                        ],
                        borderColor: [
                            "rgba(21, 128, 61, 1)",
                            "rgba(101, 163, 13, 1)",
                            "rgba(245, 158, 11, 1)",
                            "rgba(249, 115, 22, 1)",
                            "rgba(220, 38, 38, 1)",
                        ],
                        borderWidth: 1,
                    },
                ],
            };
        });

        // Distribution Chart Options
        const distributionChartOptions = {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0,
                    },
                },
            },
            plugins: {
                legend: {
                    display: false,
                },
            },
        };

        // Trend Chart Data
        const trendChartData = computed(() => {
            if (!trendData.value || trendData.value.length === 0) {
                return { labels: [], datasets: [] };
            }

            const labels = trendData.value.map((item) => item.period);

            return {
                labels,
                datasets: [
                    {
                        label: "Quality",
                        data: trendData.value.map((item) => item.quality),
                        borderColor: "#3b82f6",
                        backgroundColor: "rgba(59, 130, 246, 0.1)",
                        borderWidth: 2,
                        tension: 0.4,
                    },
                    {
                        label: "Delivery",
                        data: trendData.value.map((item) => item.delivery),
                        borderColor: "#10b981",
                        backgroundColor: "rgba(16, 185, 129, 0.1)",
                        borderWidth: 2,
                        tension: 0.4,
                    },
                    {
                        label: "Price",
                        data: trendData.value.map((item) => item.price),
                        borderColor: "#f59e0b",
                        backgroundColor: "rgba(245, 158, 11, 0.1)",
                        borderWidth: 2,
                        tension: 0.4,
                    },
                    {
                        label: "Service",
                        data: trendData.value.map((item) => item.service),
                        borderColor: "#8b5cf6",
                        backgroundColor: "rgba(139, 92, 246, 0.1)",
                        borderWidth: 2,
                        tension: 0.4,
                    },
                    {
                        label: "Overall",
                        data: trendData.value.map((item) => item.overall),
                        borderColor: "#ef4444",
                        backgroundColor: "rgba(239, 68, 68, 0.1)",
                        borderWidth: 3,
                        tension: 0.4,
                    },
                ],
            };
        });

        // Trend Chart Options
        const trendChartOptions = {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    min: 0,
                    max: 5,
                    ticks: {
                        stepSize: 1,
                    },
                },
            },
            plugins: {
                legend: {
                    position: "top",
                },
            },
        };

        // Initialize
        onMounted(() => {
            fetchDashboardData();
        });

        return {
            selectedPeriod,
            isLoading,
            metrics,
            topVendors,
            recentEvaluations,
            radarChartData,
            radarChartOptions,
            distributionChartData,
            distributionChartOptions,
            trendChartData,
            trendChartOptions,
            formatDate,
            formatScore,
            getScoreClass,
            fetchDashboardData,
        };
    },
};
</script>

<style scoped>
.dashboard-container {
    padding: 1rem;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.page-header h1 {
    margin: 0;
    font-size: 1.5rem;
    color: var(--gray-800);
}

.header-actions {
    display: flex;
    gap: 1rem;
}

.filter-group {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.filter-group label {
    font-size: 0.875rem;
    color: var(--gray-700);
}

.filter-group select {
    padding: 0.375rem 0.75rem;
    border: 1px solid var(--gray-300);
    border-radius: 0.375rem;
    font-size: 0.875rem;
    background-color: white;
}

.loading-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 2rem;
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    text-align: center;
}

.loading-spinner {
    font-size: 2rem;
    color: var(--primary-color);
    margin-bottom: 1rem;
}

.dashboard-content {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
}

/* Summary Cards */
.summary-cards {
    grid-column: 1 / 3;
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1.5rem;
}

.summary-card {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.card-icon {
    width: 3rem;
    height: 3rem;
    background-color: var(--primary-bg);
    color: var(--primary-color);
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.card-content {
    display: flex;
    flex-direction: column;
}

.card-value {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--gray-900);
}

.card-label {
    font-size: 0.875rem;
    color: var(--gray-600);
}

.card-subtext {
    font-size: 0.75rem;
    color: var(--gray-400);
}

/* Dashboard Cards */
.dashboard-card {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.dashboard-card:nth-child(2),
.dashboard-card:nth-child(3) {
    grid-column: span 1;
}

.dashboard-card:nth-child(4),
.dashboard-card:nth-child(5),
.dashboard-card:nth-child(6) {
    grid-column: 1 / 3;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    background-color: var(--gray-50);
    border-bottom: 1px solid var(--gray-200);
}

.card-title {
    margin: 0;
    font-size: 1.25rem;
    color: var(--gray-800);
}

.card-body {
    padding: 1.5rem;
}

/* Chart Containers */
.chart-container {
    position: relative;
}

.radar-chart,
.bar-chart {
    height: 300px;
}

.line-chart {
    height: 400px;
}

/* Table Styling */
.table-responsive {
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
}

.data-table th {
    text-align: left;
    padding: 0.75rem;
    background-color: var(--gray-50);
    border-bottom: 1px solid var(--gray-200);
    font-weight: 500;
    color: var(--gray-700);
}

.data-table td {
    padding: 0.75rem;
    border-bottom: 1px solid var(--gray-100);
}

.mini-score {
    font-weight: 500;
}

.mini-score-badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-weight: 500;
}

/* Score Classes */
.score-excellent {
    background-color: #15803d;
    color: white;
}

.score-good {
    background-color: #65a30d;
    color: white;
}

.score-average {
    background-color: #f59e0b;
    color: white;
}

.score-below-average {
    background-color: #f97316;
    color: white;
}

.score-poor {
    background-color: #dc2626;
    color: white;
}

/* Buttons */
.btn {
    padding: 0.625rem 1.25rem;
    font-size: 0.875rem;
    font-weight: 500;
    border-radius: 0.375rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    text-decoration: none;
    transition: background-color 0.2s, color 0.2s;
}

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.75rem;
}

.btn-outline {
    background-color: white;
    border: 1px solid var(--gray-300);
    color: var(--gray-700);
}

.btn-outline:hover {
    background-color: var(--gray-100);
}

.action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border-radius: 0.375rem;
    color: var(--primary-color);
    text-decoration: none;
    transition: background-color 0.2s;
}

.action-btn:hover {
    background-color: var(--primary-bg);
}

@media (max-width: 1024px) {
    .summary-cards {
        grid-template-columns: repeat(2, 1fr);
    }

    .dashboard-content {
        grid-template-columns: 1fr;
    }

    .dashboard-card:nth-child(2),
    .dashboard-card:nth-child(3),
    .dashboard-card:nth-child(4),
    .dashboard-card:nth-child(5),
    .dashboard-card:nth-child(6) {
        grid-column: 1;
    }

    .summary-cards {
        grid-column: 1;
    }
}

@media (max-width: 640px) {
    .summary-cards {
        grid-template-columns: 1fr;
    }

    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
}
</style>