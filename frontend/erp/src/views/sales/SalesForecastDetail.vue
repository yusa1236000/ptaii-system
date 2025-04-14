<!-- src/views/sales/SalesForecastDetail.vue -->
<template>
    <div class="forecast-detail">
        <div class="page-header">
            <div class="header-left">
                <button class="btn btn-secondary btn-sm" @click="goBack">
                    <i class="fas fa-arrow-left"></i> Back to List
                </button>
                <h1 v-if="forecast">Forecast Details</h1>
            </div>
            <div class="header-actions" v-if="forecast">
                <button class="btn btn-primary" @click="editForecast">
                    <i class="fas fa-edit"></i> Edit Forecast
                </button>
            </div>
        </div>

        <div v-if="isLoading" class="loading-container">
            <i class="fas fa-spinner fa-spin"></i> Loading forecast details...
        </div>

        <div v-else-if="!forecast" class="not-found">
            <div class="not-found-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h2>Forecast Not Found</h2>
            <p>
                The forecast you're looking for doesn't exist or you don't have
                permission to view it.
            </p>
            <button class="btn btn-primary" @click="goBack">
                Go Back to List
            </button>
        </div>

        <div v-else class="forecast-container">
            <!-- Forecast Header -->
            <div class="card forecast-info">
                <div class="card-header">
                    <h3>Forecast Information</h3>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Period</div>
                            <div class="info-value">
                                {{
                                    formatDate(
                                        forecast.forecast_period,
                                        "MMMM yyyy"
                                    )
                                }}
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Customer</div>
                            <div class="info-value">
                                {{ forecast.customer.name }}
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Item</div>
                            <div class="info-value">
                                {{ forecast.item.name }}
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Item Code</div>
                            <div class="info-value">
                                {{ forecast.item.item_code }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Forecast Metrics -->
            <div class="row metrics-container">
                <div class="card metric-card">
                    <div class="metric-title">Forecast Quantity</div>
                    <div class="metric-value">
                        {{ formatNumber(forecast.forecast_quantity) }}
                    </div>
                    <div class="metric-label">Units</div>
                </div>

                <div class="card metric-card">
                    <div class="metric-title">Actual Quantity</div>
                    <div
                        class="metric-value"
                        :class="{
                            'text-muted': forecast.actual_quantity === null,
                        }"
                    >
                        {{
                            forecast.actual_quantity !== null
                                ? formatNumber(forecast.actual_quantity)
                                : "Pending"
                        }}
                    </div>
                    <div class="metric-label">Units</div>
                </div>

                <div class="card metric-card">
                    <div class="metric-title">Variance</div>
                    <div
                        class="metric-value"
                        :class="getVarianceClass(forecast.variance)"
                    >
                        <template v-if="forecast.actual_quantity !== null">
                            {{ formatNumber(forecast.variance) }}
                            <i
                                v-if="forecast.variance > 0"
                                class="fas fa-arrow-up"
                            ></i>
                            <i
                                v-else-if="forecast.variance < 0"
                                class="fas fa-arrow-down"
                            ></i>
                        </template>
                        <template v-else>Pending</template>
                    </div>
                    <div class="metric-label">Units</div>
                </div>

                <div class="card metric-card">
                    <div class="metric-title">Accuracy</div>
                    <div
                        class="metric-value"
                        :class="getAccuracyClass(forecast)"
                    >
                        <template v-if="forecast.actual_quantity !== null">
                            {{ calculateAccuracy(forecast) }}%
                        </template>
                        <template v-else>Pending</template>
                    </div>
                    <div class="metric-label">Percentage</div>
                </div>
            </div>

            <!-- Historical Data Chart -->
            <div class="card chart-container" v-if="hasHistoricalData">
                <div class="card-header">
                    <h3>Historical Performance</h3>
                </div>
                <div class="card-body">
                    <div class="chart-wrapper">
                        <!-- Chart will be rendered here -->
                        <canvas ref="chartCanvas"></canvas>
                    </div>
                    <div class="chart-legend">
                        <div class="legend-item">
                            <div
                                class="legend-color"
                                style="background-color: rgba(37, 99, 235, 0.5)"
                            ></div>
                            <div>Forecast</div>
                        </div>
                        <div class="legend-item">
                            <div
                                class="legend-color"
                                style="background-color: rgba(5, 150, 105, 0.5)"
                            ></div>
                            <div>Actual</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Related Sales Data -->
            <div class="card related-sales">
                <div class="card-header">
                    <h3>Related Sales</h3>
                </div>
                <div class="card-body">
                    <div v-if="isLoadingSales" class="loading-indicator">
                        <i class="fas fa-spinner fa-spin"></i> Loading sales
                        data...
                    </div>
                    <div v-else-if="salesData.length === 0" class="empty-state">
                        <p>No related sales data found for this period.</p>
                    </div>
                    <table v-else class="data-table">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Date</th>
                                <th>Quantity</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="sale in salesData"
                                :key="sale.invoice_id"
                            >
                                <td>{{ sale.invoice_number }}</td>
                                <td>{{ formatDate(sale.invoice_date) }}</td>
                                <td>{{ formatNumber(sale.quantity) }}</td>
                                <td>{{ formatCurrency(sale.amount) }}</td>
                                <td>
                                    <span
                                        class="status-badge"
                                        :class="getStatusClass(sale.status)"
                                    >
                                        {{ sale.status }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2" class="total-label">Total</td>
                                <td>{{ formatNumber(totalSalesQuantity) }}</td>
                                <td>{{ formatCurrency(totalSalesAmount) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Edit Forecast Modal -->
        <SalesForecastFormModal
            v-if="showEditModal"
            :is-edit-mode="true"
            :forecast-data="forecast"
            :customers="[forecast.customer]"
            :items="[forecast.item]"
            @close="closeEditModal"
            @save="saveForecast"
        />
    </div>
</template>

<script>
import { ref, computed, onMounted, nextTick } from "vue";
import { useRoute, useRouter } from "vue-router";
import axios from "axios";
import Chart from "chart.js/auto";
import SalesForecastFormModal from "../sales/SalesForecastFormModal.vue";

export default {
    name: "SalesForecastDetail",
    components: {
        SalesForecastFormModal,
    },
    setup() {
        const route = useRoute();
        const router = useRouter();
        const forecastId = Number(route.params.id);

        const forecast = ref(null);
        const historicalData = ref([]);
        const salesData = ref([]);
        const isLoading = ref(true);
        const isLoadingSales = ref(true);
        const showEditModal = ref(false);
        const chartCanvas = ref(null);
        const chart = ref(null);

        // Fetch forecast details
        const fetchForecast = async () => {
            isLoading.value = true;
            try {
                const response = await axios.get(
                    `/sales-forecasts/${forecastId}`
                );
                forecast.value = response.data.data;

                // After loading the main forecast, load the related data
                fetchHistoricalData();
                fetchRelatedSales();
            } catch (error) {
                console.error("Error fetching forecast:", error);
                forecast.value = null;
            } finally {
                isLoading.value = false;
            }
        };

        // Fetch historical forecast and actual data for the same item-customer combination
        const fetchHistoricalData = async () => {
            if (!forecast.value) return;

            try {
                // In a real implementation, you would have an API endpoint to get historical data
                // This is a mockup that would be replaced with actual API call
                const response = await axios.get(
                    "/sales-forecasts/historical",
                    {
                        params: {
                            item_id: forecast.value.item_id,
                            customer_id: forecast.value.customer_id,
                            end_period: forecast.value.forecast_period,
                        },
                    }
                );

                historicalData.value = response.data.data || [];

                // After loading the historical data, render the chart
                nextTick(() => {
                    renderChart();
                });
            } catch (error) {
                console.error("Error fetching historical data:", error);
                historicalData.value = [];
            }
        };

        // Fetch related sales invoices for this forecast period
        const fetchRelatedSales = async () => {
            if (!forecast.value) return;

            isLoadingSales.value = true;
            try {
                // In a real implementation, you would have an API endpoint to get sales data
                // This is a mockup that would be replaced with actual API call
                const response = await axios.get("/sales-invoices", {
                    params: {
                        item_id: forecast.value.item_id,
                        customer_id: forecast.value.customer_id,
                        start_date: new Date(
                            forecast.value.forecast_period
                        ).toISOString(),
                        end_date: new Date(
                            new Date(forecast.value.forecast_period).setMonth(
                                new Date(
                                    forecast.value.forecast_period
                                ).getMonth() + 1
                            )
                        ).toISOString(),
                    },
                });

                salesData.value = response.data.data || [];
            } catch (error) {
                console.error("Error fetching sales data:", error);
                salesData.value = [];
            } finally {
                isLoadingSales.value = false;
            }
        };

        // Computed properties
        const hasHistoricalData = computed(() => {
            return historicalData.value.length > 0;
        });

        const totalSalesQuantity = computed(() => {
            return salesData.value.reduce(
                (sum, sale) => sum + sale.quantity,
                0
            );
        });

        const totalSalesAmount = computed(() => {
            return salesData.value.reduce((sum, sale) => sum + sale.amount, 0);
        });

        // Chart rendering
        const renderChart = () => {
            if (!chartCanvas.value || !hasHistoricalData.value) return;

            const ctx = chartCanvas.value.getContext("2d");

            // If there's an existing chart, destroy it first
            if (chart.value) {
                chart.value.destroy();
            }

            // Prepare data for the chart
            const periods = historicalData.value.map((data) =>
                formatDate(data.forecast_period, "MMM yyyy")
            );

            const forecastData = historicalData.value.map(
                (data) => data.forecast_quantity
            );
            const actualData = historicalData.value.map(
                (data) => data.actual_quantity
            );

            // Create a new chart
            chart.value = new Chart(ctx, {
                type: "bar",
                data: {
                    labels: periods,
                    datasets: [
                        {
                            label: "Forecast",
                            data: forecastData,
                            backgroundColor: "rgba(37, 99, 235, 0.5)",
                            borderColor: "rgba(37, 99, 235, 1)",
                            borderWidth: 1,
                        },
                        {
                            label: "Actual",
                            data: actualData,
                            backgroundColor: "rgba(5, 150, 105, 0.5)",
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

        // Form handling
        const editForecast = () => {
            showEditModal.value = true;
        };

        const closeEditModal = () => {
            showEditModal.value = false;
        };

        const saveForecast = async (updatedForecast) => {
            try {
                await axios.put(
                    `/sales-forecasts/${forecastId}`,
                    updatedForecast
                );
                closeEditModal();
                fetchForecast(); // Refresh the data
            } catch (error) {
                console.error("Error updating forecast:", error);
                alert("Failed to update forecast. Please try again.");
            }
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

        const formatCurrency = (value) => {
            if (value === null || value === undefined) return "-";

            return new Intl.NumberFormat("en-US", {
                style: "currency",
                currency: "USD",
            }).format(value);
        };

        const getVarianceClass = (variance) => {
            if (variance === null || variance === undefined) return "";

            if (variance > 0) return "text-success";
            if (variance < 0) return "text-danger";
            return "";
        };

        const calculateAccuracy = (item) => {
            if (item.actual_quantity === null || item.forecast_quantity === 0)
                return "-";

            const accuracy =
                100 - (Math.abs(item.variance) / item.actual_quantity) * 100;
            return accuracy.toFixed(1);
        };

        const getAccuracyClass = (item) => {
            if (item.actual_quantity === null) return "";

            const accuracy = calculateAccuracy(item);

            if (accuracy >= 90) return "text-success";
            if (accuracy >= 70) return "text-warning";
            return "text-danger";
        };

        const getStatusClass = (status) => {
            switch (status) {
                case "Paid":
                    return "status-success";
                case "Pending":
                    return "status-warning";
                case "Overdue":
                    return "status-danger";
                default:
                    return "";
            }
        };

        onMounted(() => {
            fetchForecast();
        });

        return {
            forecast,
            salesData,
            isLoading,
            isLoadingSales,
            showEditModal,
            chartCanvas,
            hasHistoricalData,
            totalSalesQuantity,
            totalSalesAmount,
            formatDate,
            formatNumber,
            formatCurrency,
            getVarianceClass,
            calculateAccuracy,
            getAccuracyClass,
            getStatusClass,
            editForecast,
            closeEditModal,
            saveForecast,
            goBack,
        };
    },
};
</script>

<style scoped>
.forecast-detail {
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

.header-left {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.page-header h1 {
    margin: 0;
    font-size: 1.5rem;
    color: var(--gray-800);
}

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
}

.loading-container,
.not-found {
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

.loading-container i {
    font-size: 2rem;
    color: var(--primary-color);
    margin-bottom: 1rem;
}

.not-found-icon {
    font-size: 3rem;
    color: var(--warning-color);
    margin-bottom: 1rem;
}

.not-found h2 {
    margin-bottom: 1rem;
    color: var(--gray-800);
}

.not-found p {
    margin-bottom: 1.5rem;
    color: var(--gray-600);
}

.forecast-container {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

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

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1.5rem;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.info-label {
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--gray-500);
    text-transform: uppercase;
}

.info-value {
    font-size: 0.875rem;
    color: var(--gray-800);
    font-weight: 500;
}

.row {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
}

.metrics-container {
    justify-content: space-between;
}

.metric-card {
    flex: 1;
    padding: 1.5rem;
    min-width: 200px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
}

.metric-title {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--gray-600);
    margin-bottom: 0.5rem;
}

.metric-value {
    font-size: 2rem;
    font-weight: 600;
    color: var(--gray-800);
    margin-bottom: 0.25rem;
}

.metric-label {
    font-size: 0.75rem;
    color: var(--gray-500);
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

.text-muted {
    color: var(--gray-500);
}

.chart-container {
    height: 400px;
}

.chart-wrapper {
    width: 100%;
    height: 300px;
}

.chart-legend {
    display: flex;
    gap: 2rem;
    justify-content: center;
    margin-top: 1rem;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.legend-color {
    width: 1rem;
    height: 1rem;
    border-radius: 0.25rem;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th {
    text-align: left;
    padding: 0.75rem 1rem;
    background-color: var(--gray-50);
    border-bottom: 1px solid var(--gray-200);
    color: var(--gray-600);
    font-weight: 500;
    font-size: 0.875rem;
}

.data-table td {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--gray-100);
    color: var(--gray-800);
    font-size: 0.875rem;
}

.data-table tfoot td {
    background-color: var(--gray-50);
    font-weight: 500;
}

.total-label {
    text-align: right;
}

.status-badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
}

.status-success {
    background-color: var(--success-bg);
    color: var(--success-color);
}

.status-warning {
    background-color: var(--warning-bg);
    color: var(--warning-color);
}

.status-danger {
    background-color: var(--danger-bg);
    color: var(--danger-color);
}

.loading-indicator {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 2rem;
    color: var(--gray-500);
}

.loading-indicator i {
    margin-right: 0.5rem;
}

.empty-state {
    text-align: center;
    padding: 2rem;
    color: var(--gray-500);
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }

    .info-grid {
        grid-template-columns: 1fr;
    }

    .metrics-container {
        flex-direction: column;
    }

    .metric-card {
        width: 100%;
    }

    .chart-container {
        height: auto;
    }
}
</style>
