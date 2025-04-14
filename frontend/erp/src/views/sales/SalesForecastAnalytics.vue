<!-- src/views/sales/SalesForecastAnalytics.vue -->
<template>
    <div class="forecast-analytics">
        <div class="page-header">
            <h1>Forecast Analytics</h1>
            <button class="btn btn-secondary" @click="goBack">
                <i class="fas fa-arrow-left"></i> Back to Forecasts
            </button>
        </div>

        <!-- Filter Controls -->
        <div class="filter-card">
            <div class="card-header">
                <h3>Analytics Filters</h3>
            </div>
            <div class="card-body">
                <div class="filter-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="startDate">Start Period</label>
                            <input
                                type="date"
                                id="startDate"
                                v-model="filters.start_period"
                                @change="applyFilters"
                            />
                        </div>

                        <div class="form-group">
                            <label for="endDate">End Period</label>
                            <input
                                type="date"
                                id="endDate"
                                v-model="filters.end_period"
                                @change="applyFilters"
                            />
                        </div>

                        <div class="form-group">
                            <label for="customerFilter">Customer</label>
                            <select
                                id="customerFilter"
                                v-model="filters.customer_id"
                                @change="applyFilters"
                            >
                                <option value="">All Customers</option>
                                <option
                                    v-for="customer in customers"
                                    :key="customer.customer_id"
                                    :value="customer.customer_id"
                                >
                                    {{ customer.name }}
                                </option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="itemFilter">Item</label>
                            <select
                                id="itemFilter"
                                v-model="filters.item_id"
                                @change="applyFilters"
                            >
                                <option value="">All Items</option>
                                <option
                                    v-for="item in items"
                                    :key="item.item_id"
                                    :value="item.item_id"
                                >
                                    {{ item.name }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading state -->
        <div v-if="isLoading" class="loading-container">
            <i class="fas fa-spinner fa-spin"></i> Loading forecast analytics...
        </div>

        <!-- Data display -->
        <div v-else-if="!analyticsData" class="empty-analytics">
            <div class="empty-icon">
                <i class="fas fa-chart-bar"></i>
            </div>
            <h2>No analytics data available</h2>
            <p>
                Try adjusting your filters or ensuring there are completed
                forecasts with actual quantities.
            </p>
        </div>

        <div v-else class="analytics-container">
            <!-- Key Metrics Cards -->
            <div class="metrics-row">
                <div class="metric-card">
                    <div class="metric-icon">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <div class="metric-content">
                        <div class="metric-value">
                            {{ analyticsData.total_forecasts }}
                        </div>
                        <div class="metric-label">Total Forecasts</div>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-icon">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <div class="metric-content">
                        <div
                            class="metric-value"
                            :class="
                                getMAPEClass(
                                    analyticsData.mean_absolute_percentage_error
                                )
                            "
                        >
                            {{
                                formatNumber(
                                    analyticsData.mean_absolute_percentage_error
                                )
                            }}%
                        </div>
                        <div class="metric-label">
                            Mean Absolute Percentage Error
                        </div>
                        <div class="metric-description">Lower is better</div>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-icon">
                        <i class="fas fa-balance-scale"></i>
                    </div>
                    <div class="metric-content">
                        <div
                            class="metric-value"
                            :class="getBiasClass(analyticsData.bias_percentage)"
                        >
                            {{ formatNumber(analyticsData.bias_percentage) }}%
                            <i
                                v-if="analyticsData.bias_percentage > 0"
                                class="fas fa-arrow-up"
                            ></i>
                            <i
                                v-else-if="analyticsData.bias_percentage < 0"
                                class="fas fa-arrow-down"
                            ></i>
                        </div>
                        <div class="metric-label">Forecast Bias</div>
                        <div class="metric-description">
                            {{
                                analyticsData.bias_percentage > 0
                                    ? "Under-forecasting"
                                    : analyticsData.bias_percentage < 0
                                    ? "Over-forecasting"
                                    : "Neutral"
                            }}
                        </div>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-icon">
                        <i class="fas fa-ruler"></i>
                    </div>
                    <div class="metric-content">
                        <div class="metric-value">
                            {{
                                formatNumber(
                                    analyticsData.mean_absolute_deviation
                                )
                            }}
                        </div>
                        <div class="metric-label">Mean Absolute Deviation</div>
                        <div class="metric-description">
                            Average units off per forecast
                        </div>
                    </div>
                </div>
            </div>

            <!-- Accuracy Chart -->
            <div class="card chart-card">
                <div class="card-header">
                    <h3>Forecast Accuracy Over Time</h3>
                </div>
                <div class="card-body">
                    <div class="chart-wrapper">
                        <canvas ref="accuracyChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Forecast vs Actual Chart -->
            <div class="card chart-card">
                <div class="card-header">
                    <h3>Forecast vs Actual Comparison</h3>
                </div>
                <div class="card-body">
                    <div class="chart-wrapper">
                        <canvas ref="comparisonChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Detailed Forecast Table -->
            <div class="card">
                <div class="card-header">
                    <h3>Forecast Details</h3>
                    <button
                        class="btn btn-sm btn-secondary"
                        @click="exportToCSV"
                    >
                        <i class="fas fa-download"></i> Export to CSV
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Period</th>
                                    <th>Item</th>
                                    <th>Customer</th>
                                    <th>Forecast</th>
                                    <th>Actual</th>
                                    <th>Variance</th>
                                    <th>Accuracy</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(
                                        forecast, index
                                    ) in analyticsData.forecasts"
                                    :key="index"
                                >
                                    <td>
                                        {{
                                            formatDate(
                                                forecast.forecast_period,
                                                "MMM yyyy"
                                            )
                                        }}
                                    </td>
                                    <td>{{ forecast.item.name }}</td>
                                    <td>{{ forecast.customer.name }}</td>
                                    <td>
                                        {{
                                            formatNumber(
                                                forecast.forecast_quantity
                                            )
                                        }}
                                    </td>
                                    <td>
                                        {{
                                            formatNumber(
                                                forecast.actual_quantity
                                            )
                                        }}
                                    </td>
                                    <td
                                        :class="
                                            getVarianceClass(forecast.variance)
                                        "
                                    >
                                        {{ formatNumber(forecast.variance) }}
                                        <i
                                            v-if="forecast.variance > 0"
                                            class="fas fa-arrow-up"
                                        ></i>
                                        <i
                                            v-else-if="forecast.variance < 0"
                                            class="fas fa-arrow-down"
                                        ></i>
                                    </td>
                                    <td
                                        :class="
                                            getAccuracyClass(
                                                calculateAccuracy(forecast)
                                            )
                                        "
                                    >
                                        {{ calculateAccuracy(forecast) }}%
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
import { ref, reactive, onMounted, nextTick } from "vue";
import { useRouter } from "vue-router";
import axios from "axios";
import Chart from "chart.js/auto";

export default {
    name: "SalesForecastAnalytics",
    setup() {
        const router = useRouter();
        const customers = ref([]);
        const items = ref([]);
        const analyticsData = ref(null);
        const isLoading = ref(true);
        const accuracyChart = ref(null);
        const comparisonChart = ref(null);
        const accuracyChartInstance = ref(null);
        const comparisonChartInstance = ref(null);

        // Default to last 6 months
        const today = new Date();
        const sixMonthsAgo = new Date(today);
        sixMonthsAgo.setMonth(today.getMonth() - 6);

        const filters = reactive({
            start_period: sixMonthsAgo.toISOString().substr(0, 10),
            end_period: today.toISOString().substr(0, 10),
            customer_id: "",
            item_id: "",
        });

        // Load reference data
        const loadReferenceData = async () => {
            try {
                const [customersResponse, itemsResponse] = await Promise.all([
                    axios.get("/customers"),
                    axios.get("/items"),
                ]);

                customers.value = customersResponse.data.data || [];
                items.value = itemsResponse.data.data || [];
            } catch (error) {
                console.error("Error loading reference data:", error);
            }
        };

        // Load analytics data
        const loadAnalytics = async () => {
            isLoading.value = true;

            try {
                const response = await axios.get("/sales-forecasts/accuracy", {
                    params: filters,
                });

                analyticsData.value = response.data.data;

                // Render charts on next tick to ensure DOM is updated
                nextTick(() => {
                    renderCharts();
                });
            } catch (error) {
                console.error("Error loading analytics data:", error);
                analyticsData.value = null;
            } finally {
                isLoading.value = false;
            }
        };

        // Apply filters and reload data
        const applyFilters = () => {
            loadAnalytics();
        };

        // Render charts
        const renderCharts = () => {
            if (
                !analyticsData.value ||
                !analyticsData.value.forecasts ||
                analyticsData.value.forecasts.length === 0
            ) {
                return;
            }

            renderAccuracyChart();
            renderComparisonChart();
        };

        const renderAccuracyChart = () => {
            if (!accuracyChart.value) return;

            // Destroy previous chart if exists
            if (accuracyChartInstance.value) {
                accuracyChartInstance.value.destroy();
            }

            const ctx = accuracyChart.value.getContext("2d");

            // Prepare data
            const forecasts = analyticsData.value.forecasts;
            const periods = forecasts.map((f) =>
                formatDate(f.forecast_period, "MMM yyyy")
            );
            const accuracies = forecasts.map((f) => {
                if (f.actual_quantity === null || f.actual_quantity === 0)
                    return null;
                return 100 - (Math.abs(f.variance) / f.actual_quantity) * 100;
            });

            // Create chart
            accuracyChartInstance.value = new Chart(ctx, {
                type: "line",
                data: {
                    labels: periods,
                    datasets: [
                        {
                            label: "Forecast Accuracy %",
                            data: accuracies,
                            borderColor: "#2563eb",
                            backgroundColor: "rgba(37, 99, 235, 0.1)",
                            borderWidth: 2,
                            tension: 0.2,
                            fill: true,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            min: 0,
                            max: 100,
                            title: {
                                display: true,
                                text: "Accuracy (%)",
                            },
                        },
                        x: {
                            title: {
                                display: true,
                                text: "Period",
                            },
                        },
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    return `Accuracy: ${context.parsed.y.toFixed(
                                        1
                                    )}%`;
                                },
                            },
                        },
                    },
                },
            });
        };

        const renderComparisonChart = () => {
            if (!comparisonChart.value) return;

            // Destroy previous chart if exists
            if (comparisonChartInstance.value) {
                comparisonChartInstance.value.destroy();
            }

            const ctx = comparisonChart.value.getContext("2d");

            // Prepare data
            const forecasts = analyticsData.value.forecasts;
            const periods = forecasts.map((f) =>
                formatDate(f.forecast_period, "MMM yyyy")
            );
            const forecastValues = forecasts.map((f) => f.forecast_quantity);
            const actualValues = forecasts.map((f) => f.actual_quantity);

            // Create chart
            comparisonChartInstance.value = new Chart(ctx, {
                type: "bar",
                data: {
                    labels: periods,
                    datasets: [
                        {
                            label: "Forecast",
                            data: forecastValues,
                            backgroundColor: "rgba(37, 99, 235, 0.6)",
                            borderColor: "rgba(37, 99, 235, 1)",
                            borderWidth: 1,
                        },
                        {
                            label: "Actual",
                            data: actualValues,
                            backgroundColor: "rgba(5, 150, 105, 0.6)",
                            borderColor: "rgba(5, 150, 105, 1)",
                            borderWidth: 1,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: "Quantity",
                            },
                        },
                        x: {
                            title: {
                                display: true,
                                text: "Period",
                            },
                        },
                    },
                },
            });
        };

        // Export data to CSV
        const exportToCSV = () => {
            if (!analyticsData.value || !analyticsData.value.forecasts) return;

            const forecasts = analyticsData.value.forecasts;
            const headers = [
                "Period",
                "Item",
                "Customer",
                "Forecast Quantity",
                "Actual Quantity",
                "Variance",
                "Accuracy (%)",
            ];

            // Create CSV content
            let csvContent = headers.join(",") + "\n";

            forecasts.forEach((forecast) => {
                const row = [
                    formatDate(forecast.forecast_period, "MMM yyyy"),
                    `"${forecast.item.name}"`,
                    `"${forecast.customer.name}"`,
                    forecast.forecast_quantity,
                    forecast.actual_quantity,
                    forecast.variance,
                    calculateAccuracy(forecast),
                ];

                csvContent += row.join(",") + "\n";
            });

            // Create download link
            const blob = new Blob([csvContent], {
                type: "text/csv;charset=utf-8;",
            });
            const url = URL.createObjectURL(blob);
            const link = document.createElement("a");
            link.setAttribute("href", url);
            link.setAttribute("download", "forecast_analytics.csv");
            link.style.visibility = "hidden";
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        };

        // Navigation
        const goBack = () => {
            router.push("/sales/forecasts");
        };

        // Formatting and display helpers
        const formatDate = (dateString, format = "dd/MM/yyyy") => {
            if (!dateString) return "-";

            const date = new Date(dateString);

            if (format === "MMMM yyyy") {
                return date.toLocaleDateString("en-US", {
                    month: "long",
                    year: "numeric",
                });
            } else if (format === "MMM yyyy") {
                return date.toLocaleDateString("en-US", {
                    month: "short",
                    year: "numeric",
                });
            }

            return date.toLocaleDateString("en-US");
        };

        const formatNumber = (value) => {
            if (value === null || value === undefined) return "-";

            return new Intl.NumberFormat("en-US", {
                maximumFractionDigits: 2,
                minimumFractionDigits: 0,
            }).format(value);
        };

        const getVarianceClass = (variance) => {
            if (variance === null || variance === undefined) return "";

            if (variance > 0) return "text-success";
            if (variance < 0) return "text-danger";
            return "";
        };

        const calculateAccuracy = (forecast) => {
            if (
                forecast.actual_quantity === null ||
                forecast.actual_quantity === 0
            )
                return "-";

            const accuracy =
                100 -
                (Math.abs(forecast.variance) / forecast.actual_quantity) * 100;
            return accuracy.toFixed(1);
        };

        const getAccuracyClass = (accuracy) => {
            if (accuracy === "-") return "";

            const numAccuracy = parseFloat(accuracy);
            if (numAccuracy >= 90) return "text-success";
            if (numAccuracy >= 70) return "text-warning";
            return "text-danger";
        };

        const getMAPEClass = (mape) => {
            if (mape <= 10) return "text-success";
            if (mape <= 20) return "text-warning";
            return "text-danger";
        };

        const getBiasClass = (bias) => {
            // Absolute bias less than 5% is considered good
            if (Math.abs(bias) <= 5) return "text-success";
            // Absolute bias less than 10% is considered acceptable
            if (Math.abs(bias) <= 10) return "text-warning";
            // Anything else is considered poor
            return "text-danger";
        };

        onMounted(async () => {
            await loadReferenceData();
            loadAnalytics();
        });

        return {
            customers,
            items,
            analyticsData,
            isLoading,
            filters,
            accuracyChart,
            comparisonChart,
            applyFilters,
            formatDate,
            formatNumber,
            getVarianceClass,
            calculateAccuracy,
            getAccuracyClass,
            getMAPEClass,
            getBiasClass,
            exportToCSV,
            goBack,
        };
    },
};
</script>

<style scoped>
.forecast-analytics {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.page-header h1 {
    margin: 0;
    font-size: 1.5rem;
    color: var(--gray-800);
}

.filter-card,
.card {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    background-color: var(--gray-50);
    border-bottom: 1px solid var(--gray-200);
}

.card-header h3 {
    margin: 0;
    font-size: 1.125rem;
    color: var(--gray-800);
}

.card-body {
    padding: 1.5rem;
}

.filter-form {
    width: 100%;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: var(--gray-700);
    font-size: 0.875rem;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 0.625rem;
    border: 1px solid var(--gray-300);
    border-radius: 0.375rem;
    font-size: 0.875rem;
}

.loading-container {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 4rem;
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.loading-container i {
    font-size: 2rem;
    color: var(--primary-color);
    margin-right: 1rem;
}

.empty-analytics {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 4rem 2rem;
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.empty-icon {
    font-size: 3rem;
    color: var(--gray-300);
    margin-bottom: 1rem;
}

.empty-analytics h2 {
    margin-bottom: 0.5rem;
    color: var(--gray-800);
}

.empty-analytics p {
    color: var(--gray-600);
    max-width: 500px;
}

.metrics-row {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.metric-card {
    flex: 1;
    min-width: 200px;
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.metric-icon {
    width: 3rem;
    height: 3rem;
    background-color: var(--primary-bg);
    color: var(--primary-color);
    border-radius: 9999px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
}

.metric-content {
    flex: 1;
}

.metric-value {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--gray-800);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.metric-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--gray-600);
    margin-bottom: 0.25rem;
}

.metric-description {
    font-size: 0.75rem;
    color: var(--gray-500);
}

.chart-card {
    margin-bottom: 1.5rem;
}

.chart-wrapper {
    width: 100%;
    height: 300px;
}

.text-success {
    color: var(--success-color);
}

.text-warning {
    color: var(--warning-color);
}

.text-danger {
    color: var(--danger-color);
}

.table-responsive {
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th,
.data-table td {
    padding: 0.75rem 1rem;
    text-align: left;
    border-bottom: 1px solid var(--gray-100);
}

.data-table th {
    background-color: var(--gray-50);
    font-weight: 500;
    color: var(--gray-600);
    font-size: 0.875rem;
}

.data-table td {
    font-size: 0.875rem;
    color: var(--gray-800);
}

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }

    .metrics-row {
        flex-direction: column;
    }

    .metric-card {
        width: 100%;
    }

    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>
