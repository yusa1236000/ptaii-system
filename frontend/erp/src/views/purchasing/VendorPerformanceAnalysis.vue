<!-- src/views/purchasing/VendorPerformanceAnalysis.vue -->
<template>
    <div class="vendor-performance-container">
        <div class="page-header">
            <div class="header-left">
                <router-link to="/purchasing/vendors" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to Vendors
                </router-link>
                <h1>{{ vendor?.name || "Vendor" }} Performance Analysis</h1>
            </div>
            <div class="header-actions">
                <div class="filter-group inline-filter">
                    <label for="period-filter">Time Period:</label>
                    <select
                        id="period-filter"
                        v-model="selectedPeriod"
                        @change="fetchPerformanceData"
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
            <p>Loading performance data...</p>
        </div>

        <div v-else-if="!vendor" class="error-container">
            <div class="error-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h2>Vendor Not Found</h2>
            <p>
                The requested vendor could not be found or may have been
                deleted.
            </p>
            <router-link to="/purchasing/vendors" class="btn btn-primary">
                Return to Vendors List
            </router-link>
        </div>

        <div v-else class="performance-content">
            <!-- Overall Performance Score Card -->
            <div class="performance-card overall-card">
                <div class="card-header">
                    <h2 class="card-title">Overall Performance</h2>
                </div>
                <div class="card-body">
                    <div class="overall-score-display">
                        <div
                            class="score-circle"
                            :class="getScoreClass(averages.total)"
                        >
                            <span class="score-value">{{
                                formatScore(averages.total)
                            }}</span>
                            <span class="score-label">out of 5</span>
                        </div>
                    </div>
                    <div class="score-details">
                        <div class="score-item">
                            <span class="score-label">Quality</span>
                            <div class="score-bar-container">
                                <div class="score-bar">
                                    <div
                                        class="score-fill"
                                        :style="{
                                            width: `${averages.quality * 20}%`,
                                        }"
                                    ></div>
                                </div>
                                <span class="score-number">{{
                                    formatScore(averages.quality)
                                }}</span>
                            </div>
                        </div>
                        <div class="score-item">
                            <span class="score-label">Delivery</span>
                            <div class="score-bar-container">
                                <div class="score-bar">
                                    <div
                                        class="score-fill"
                                        :style="{
                                            width: `${averages.delivery * 20}%`,
                                        }"
                                    ></div>
                                </div>
                                <span class="score-number">{{
                                    formatScore(averages.delivery)
                                }}</span>
                            </div>
                        </div>
                        <div class="score-item">
                            <span class="score-label">Price</span>
                            <div class="score-bar-container">
                                <div class="score-bar">
                                    <div
                                        class="score-fill"
                                        :style="{
                                            width: `${averages.price * 20}%`,
                                        }"
                                    ></div>
                                </div>
                                <span class="score-number">{{
                                    formatScore(averages.price)
                                }}</span>
                            </div>
                        </div>
                        <div class="score-item">
                            <span class="score-label">Service</span>
                            <div class="score-bar-container">
                                <div class="score-bar">
                                    <div
                                        class="score-fill"
                                        :style="{
                                            width: `${averages.service * 20}%`,
                                        }"
                                    ></div>
                                </div>
                                <span class="score-number">{{
                                    formatScore(averages.service)
                                }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance Trend Chart -->
            <div class="performance-card trend-card">
                <div class="card-header">
                    <h2 class="card-title">Performance Trend</h2>
                </div>
                <div class="card-body">
                    <div v-if="evaluations.length < 2" class="no-trend-data">
                        <i class="fas fa-chart-line"></i>
                        <p>
                            Not enough data to display performance trend. At
                            least 2 evaluations are required.
                        </p>
                    </div>
                    <div v-else class="chart-container">
                        <LineChart
                            :chart-data="chartData"
                            :chart-options="chartOptions"
                        />
                    </div>
                </div>
            </div>

            <!-- Evaluation History Card -->
            <div class="performance-card history-card">
                <div class="card-header">
                    <h2 class="card-title">Evaluation History</h2>
                    <router-link
                        :to="`/purchasing/evaluations/create?vendor_id=${vendorId}`"
                        class="btn btn-sm btn-primary"
                    >
                        <i class="fas fa-plus"></i> New Evaluation
                    </router-link>
                </div>
                <div class="card-body">
                    <div v-if="evaluations.length === 0" class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <h3>No Evaluations Available</h3>
                        <p>
                            There are no evaluations recorded for this vendor in
                            the selected time period.
                        </p>
                    </div>
                    <div v-else class="evaluation-timeline">
                        <div
                            v-for="(evaluation, index) in evaluations"
                            :key="evaluation.evaluation_id"
                            class="timeline-item"
                        >
                            <div class="timeline-date">
                                {{ formatDate(evaluation.evaluation_date) }}
                            </div>
                            <div
                                class="timeline-connector"
                                v-if="index < evaluations.length - 1"
                            ></div>
                            <div class="timeline-content">
                                <div class="timeline-header">
                                    <div
                                        :class="[
                                            'timeline-score',
                                            getScoreClass(
                                                evaluation.total_score
                                            ),
                                        ]"
                                    >
                                        {{
                                            formatScore(evaluation.total_score)
                                        }}
                                    </div>
                                    <div class="timeline-actions">
                                        <router-link
                                            :to="`/purchasing/evaluations/${evaluation.evaluation_id}`"
                                            class="btn btn-sm btn-outline"
                                        >
                                            View Details
                                        </router-link>
                                    </div>
                                </div>
                                <div class="timeline-scores">
                                    <div class="mini-score">
                                        <span class="mini-label">Quality</span>
                                        <span class="mini-value">{{
                                            evaluation.quality_score
                                        }}</span>
                                    </div>
                                    <div class="mini-score">
                                        <span class="mini-label">Delivery</span>
                                        <span class="mini-value">{{
                                            evaluation.delivery_score
                                        }}</span>
                                    </div>
                                    <div class="mini-score">
                                        <span class="mini-label">Price</span>
                                        <span class="mini-value">{{
                                            evaluation.price_score
                                        }}</span>
                                    </div>
                                    <div class="mini-score">
                                        <span class="mini-label">Service</span>
                                        <span class="mini-value">{{
                                            evaluation.service_score
                                        }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recommendations Card -->
            <div class="performance-card recommendations-card">
                <div class="card-header">
                    <h2 class="card-title">Recommendations</h2>
                </div>
                <div class="card-body">
                    <div class="recommendation-list">
                        <div
                            v-if="recommendations.length === 0"
                            class="no-recommendations"
                        >
                            <p>
                                This vendor's performance is great! No specific
                                recommendations at this time.
                            </p>
                        </div>
                        <div
                            v-else
                            v-for="(recommendation, index) in recommendations"
                            :key="index"
                            class="recommendation-item"
                        >
                            <div class="recommendation-icon">
                                <i :class="recommendation.icon"></i>
                            </div>
                            <div class="recommendation-content">
                                <h4 class="recommendation-title">
                                    {{ recommendation.title }}
                                </h4>
                                <p class="recommendation-description">
                                    {{ recommendation.description }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, computed, onMounted } from "vue";
import { useRoute } from "vue-router";
import VendorService from "@/services/VendorService";
import VendorEvaluationService from "@/services/VendorEvaluationService";
import { Line as LineChart } from "vue-chartjs";
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
} from "chart.js";

// Register the Chart.js components
ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend
);

export default {
    name: "VendorPerformanceAnalysis",
    components: {
        LineChart,
    },
    setup() {
        const route = useRoute();
        // const router = useRouter();
        const vendorId = ref(route.params.id);
        const vendor = ref(null);
        const evaluations = ref([]);
        const averages = ref({
            quality: 0,
            delivery: 0,
            price: 0,
            service: 0,
            total: 0,
        });
        const selectedPeriod = ref("year");
        const isLoading = ref(true);
        const recommendations = ref([]);

        // Chart data
        const chartData = computed(() => {
            if (evaluations.value.length === 0) {
                return { datasets: [] };
            }

            // Sort evaluations by date
            const sortedEvaluations = [...evaluations.value].sort(
                (a, b) =>
                    new Date(a.evaluation_date) - new Date(b.evaluation_date)
            );

            const labels = sortedEvaluations.map((e) =>
                formatDate(e.evaluation_date)
            );

            return {
                labels,
                datasets: [
                    {
                        label: "Quality",
                        borderColor: "#3b82f6",
                        backgroundColor: "rgba(59, 130, 246, 0.2)",
                        data: sortedEvaluations.map((e) => e.quality_score),
                        tension: 0.4,
                    },
                    {
                        label: "Delivery",
                        borderColor: "#10b981",
                        backgroundColor: "rgba(16, 185, 129, 0.2)",
                        data: sortedEvaluations.map((e) => e.delivery_score),
                        tension: 0.4,
                    },
                    {
                        label: "Price",
                        borderColor: "#f59e0b",
                        backgroundColor: "rgba(245, 158, 11, 0.2)",
                        data: sortedEvaluations.map((e) => e.price_score),
                        tension: 0.4,
                    },
                    {
                        label: "Service",
                        borderColor: "#8b5cf6",
                        backgroundColor: "rgba(139, 92, 246, 0.2)",
                        data: sortedEvaluations.map((e) => e.service_score),
                        tension: 0.4,
                    },
                    {
                        label: "Overall",
                        borderColor: "#ef4444",
                        backgroundColor: "rgba(239, 68, 68, 0.2)",
                        data: sortedEvaluations.map((e) => e.total_score),
                        tension: 0.4,
                        borderWidth: 2,
                    },
                ],
            };
        });

        // Chart options
        const chartOptions = {
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
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            let label = context.dataset.label || "";
                            if (label) {
                                label += ": ";
                            }
                            if (context.parsed.y !== null) {
                                label += context.parsed.y.toFixed(1);
                            }
                            return label;
                        },
                    },
                },
            },
        };

        // Fetch vendor details
        const fetchVendor = async () => {
            try {
                const response = await VendorService.getVendorById(
                    vendorId.value
                );
                vendor.value =
                    response.data && response.data.data
                        ? response.data.data
                        : null;
            } catch (error) {
                console.error("Error fetching vendor details:", error);
                vendor.value = null;
            }
        };

        // Fetch vendor performance data
        const fetchPerformanceData = async () => {
            isLoading.value = true;

            try {
                const params = {
                    vendor_id: vendorId.value,
                    period: selectedPeriod.value,
                };

                const response =
                    await VendorEvaluationService.getVendorPerformance(params);

                if (response.data && response.data.data) {
                    evaluations.value = response.data.data.evaluations || [];
                    averages.value = response.data.data.averages || {
                        quality: 0,
                        delivery: 0,
                        price: 0,
                        service: 0,
                        total: 0,
                    };

                    // Generate recommendations based on scores
                    generateRecommendations();
                }
            } catch (error) {
                console.error("Error fetching vendor performance:", error);
                evaluations.value = [];
                averages.value = {
                    quality: 0,
                    delivery: 0,
                    price: 0,
                    service: 0,
                    total: 0,
                };
            } finally {
                isLoading.value = false;
            }
        };

        // Generate recommendations based on scores
        const generateRecommendations = () => {
            recommendations.value = [];

            // Quality recommendations
            if (averages.value.quality < 3) {
                recommendations.value.push({
                    title: "Improve Quality Control",
                    description:
                        "This vendor has low quality scores. Consider requesting improved quality control measures or looking for alternative suppliers.",
                    icon: "fas fa-clipboard-check",
                });
            }

            // Delivery recommendations
            if (averages.value.delivery < 3) {
                recommendations.value.push({
                    title: "Address Delivery Issues",
                    description:
                        "Delivery performance is below average. Discuss delivery timeframes and reliability with this vendor.",
                    icon: "fas fa-truck",
                });
            }

            // Price recommendations
            if (averages.value.price < 3) {
                recommendations.value.push({
                    title: "Review Pricing Structure",
                    description:
                        "Price competitiveness is lower than expected. Consider negotiating better terms or exploring alternatives.",
                    icon: "fas fa-tags",
                });
            }

            // Service recommendations
            if (averages.value.service < 3) {
                recommendations.value.push({
                    title: "Improve Communication",
                    description:
                        "Service quality needs improvement. Establish better communication channels and set clear expectations.",
                    icon: "fas fa-comments",
                });
            }

            // Overall performance recommendation
            if (averages.value.total < 2.5) {
                recommendations.value.push({
                    title: "Consider Vendor Replacement",
                    description:
                        "Overall performance is consistently poor. Consider finding alternative vendors for future purchases.",
                    icon: "fas fa-exclamation-triangle",
                });
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

        // Watch for period changes
        // watch(selectedPeriod, () => {
        //     fetchPerformanceData();
        // });

        // Initialize
        onMounted(() => {
            Promise.all([fetchVendor(), fetchPerformanceData()]).catch(
                (error) => {
                    console.error("Error initializing data:", error);
                }
            );
        });

        return {
            vendorId,
            vendor,
            evaluations,
            averages,
            selectedPeriod,
            isLoading,
            recommendations,
            chartData,
            chartOptions,
            formatDate,
            formatScore,
            getScoreClass,
            fetchPerformanceData,
        };
    },
};
</script>

<style scoped>
.vendor-performance-container {
    padding: 1rem;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1.5rem;
}

.header-left {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--gray-600);
    text-decoration: none;
    font-size: 0.875rem;
}

.back-link:hover {
    color: var(--primary-color);
}

.header-left h1 {
    margin: 0;
    font-size: 1.5rem;
    color: var(--gray-800);
}

.inline-filter {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.loading-container,
.error-container {
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

.error-icon {
    font-size: 3rem;
    color: var(--danger-color);
    margin-bottom: 1rem;
}

.performance-content {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
}

.performance-card {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.overall-card {
    grid-column: 1 / 2;
    grid-row: 1 / 2;
}

.trend-card {
    grid-column: 2 / 3;
    grid-row: 1 / 2;
}

.history-card {
    grid-column: 1 / 3;
    grid-row: 2 / 3;
}

.recommendations-card {
    grid-column: 1 / 3;
    grid-row: 3 / 4;
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

/* Overall Score Card Styling */
.overall-score-display {
    display: flex;
    justify-content: center;
    margin-bottom: 2rem;
}

.score-circle {
    position: relative;
    width: 150px;
    height: 150px;
    border-radius: 50%;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.score-value {
    font-size: 3rem;
    font-weight: bold;
    line-height: 1;
}

.score-label {
    font-size: 0.875rem;
    opacity: 0.8;
}

.score-details {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.score-item {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.score-label {
    min-width: 70px;
    font-weight: 500;
}

.score-bar-container {
    display: flex;
    align-items: center;
    flex: 1;
    gap: 1rem;
}

.score-bar {
    position: relative;
    width: 100%;
    height: 0.75rem;
    background-color: var(--gray-100);
    border-radius: 0.25rem;
    overflow: hidden;
}

.score-fill {
    position: absolute;
    top: 0;
    left: 0;
    height: 100%;
    background-color: var(--primary-color);
    border-radius: 0.25rem;
}

.score-number {
    font-weight: 500;
    min-width: 2rem;
    text-align: right;
}

/* Chart Styling */
.chart-container {
    height: 300px;
}

.no-trend-data {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    color: var(--gray-500);
    text-align: center;
}

.no-trend-data i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.3;
}

/* Timeline Styling */
.evaluation-timeline {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.timeline-item {
    position: relative;
    display: flex;
    margin-bottom: 1.5rem;
}

.timeline-date {
    min-width: 100px;
    font-size: 0.875rem;
    font-weight: 500;
    padding-top: 0.5rem;
}

.timeline-connector {
    position: absolute;
    left: 100px;
    top: 2rem;
    width: 2px;
    height: calc(100% + 1.5rem);
    background-color: var(--gray-200);
}

.timeline-content {
    flex: 1;
    padding: 1rem;
    border: 1px solid var(--gray-200);
    border-radius: 0.5rem;
    background-color: var(--gray-50);
}

.timeline-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.timeline-score {
    font-weight: bold;
    font-size: 1.25rem;
    padding: 0.25rem 0.75rem;
    border-radius: 0.375rem;
}

.timeline-scores {
    display: flex;
    gap: 2rem;
    flex-wrap: wrap;
}

.mini-score {
    display: flex;
    flex-direction: column;
    align-items: center;
    min-width: 70px;
}

.mini-label {
    font-size: 0.75rem;
    color: var(--gray-500);
    margin-bottom: 0.25rem;
}

.mini-value {
    font-weight: 600;
    font-size: 1.125rem;
}

/* Recommendations Styling */
.recommendation-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.recommendation-item {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    border-radius: 0.5rem;
    background-color: var(--gray-50);
    border-left: 4px solid var(--primary-color);
}

.recommendation-icon {
    font-size: 1.5rem;
    color: var(--primary-color);
}

.recommendation-content {
    flex: 1;
}

.recommendation-title {
    margin: 0 0 0.5rem 0;
    font-size: 1.125rem;
}

.recommendation-description {
    margin: 0;
    color: var(--gray-600);
}

.no-recommendations {
    text-align: center;
    padding: 1rem;
    color: var(--gray-600);
}

/* Empty State Styling */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem 2rem;
    text-align: center;
}

.empty-icon {
    font-size: 2.5rem;
    color: var(--gray-300);
    margin-bottom: 1rem;
}

.empty-state h3 {
    font-size: 1.125rem;
    color: var(--gray-700);
    margin-bottom: 0.5rem;
}

.empty-state p {
    color: var(--gray-500);
    max-width: 24rem;
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

/* Button Styles */
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
    border: none;
    transition: background-color 0.2s, color 0.2s;
}

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.75rem;
}

.btn-primary {
    background-color: var(--primary-color);
    color: white;
}

.btn-primary:hover {
    background-color: var(--primary-dark);
}

.btn-outline {
    background-color: transparent;
    border: 1px solid var(--gray-300);
    color: var(--gray-700);
}

.btn-outline:hover {
    background-color: var(--gray-100);
}

@media (max-width: 768px) {
    .performance-content {
        grid-template-columns: 1fr;
    }

    .overall-card,
    .trend-card,
    .history-card,
    .recommendations-card {
        grid-column: 1 / 2;
    }

    .overall-card {
        grid-row: 1 / 2;
    }

    .trend-card {
        grid-row: 2 / 3;
    }

    .history-card {
        grid-row: 3 / 4;
    }

    .recommendations-card {
        grid-row: 4 / 5;
    }

    .timeline-scores {
        gap: 1rem;
    }
}
</style>
