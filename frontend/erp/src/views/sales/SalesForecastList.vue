<!-- src/views/sales/SalesForecastList.vue -->
<template>
    <div class="sales-forecasts">
        <div class="page-header">
            <h1>Sales Forecasts</h1>
            <div class="page-actions">
                <button class="btn btn-primary" @click="openGenerateModal">
                    <i class="fas fa-chart-line"></i> Generate Forecasts
                </button>
                <button class="btn btn-secondary" @click="openCreateModal">
                    <i class="fas fa-plus"></i> Create Forecast
                </button>
            </div>
        </div>

        <!-- Search and Filter Section -->
        <SearchFilter
            v-model:value="searchQuery"
            placeholder="Search forecasts..."
            @search="applyFilters"
            @clear="clearSearch"
        >
            <template #filters>
                <div class="filter-group">
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

                <div class="filter-group">
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

                <div class="filter-group">
                    <label for="periodFilter">Period</label>
                    <select
                        id="periodFilter"
                        v-model="filters.period"
                        @change="applyFilters"
                    >
                        <option value="">All Periods</option>
                        <option value="current_month">Current Month</option>
                        <option value="last_month">Last Month</option>
                        <option value="last_quarter">Last Quarter</option>
                        <option value="last_year">Last Year</option>
                        <option value="custom">Custom Range</option>
                    </select>
                </div>
            </template>

            <template #actions>
                <button class="btn btn-info" @click="updateActuals">
                    <i class="fas fa-sync"></i> Update Actuals
                </button>
                <button class="btn btn-success" @click="navigateToAnalytics">
                    <i class="fas fa-chart-bar"></i> View Analytics
                </button>
            </template>
        </SearchFilter>

        <!-- Custom Date Range (when Custom is selected) -->
        <div v-if="filters.period === 'custom'" class="custom-date-range">
            <div class="date-range-inputs">
                <div class="filter-group">
                    <label for="startDate">Start Period</label>
                    <input
                        type="date"
                        id="startDate"
                        v-model="customDateRange.startDate"
                        @change="applyFilters"
                    />
                </div>

                <div class="filter-group">
                    <label for="endDate">End Period</label>
                    <input
                        type="date"
                        id="endDate"
                        v-model="customDateRange.endDate"
                        @change="applyFilters"
                    />
                </div>
            </div>
        </div>

        <!-- Forecasts Table -->
        <DataTable
            :columns="columns"
            :items="forecasts"
            :is-loading="isLoading"
            keyField="forecast_id"
            emptyIcon="fas fa-chart-line"
            emptyTitle="No forecasts found"
            emptyMessage="No forecasts match your search criteria or no forecasts have been created yet."
            @sort="handleSort"
        >
            <template #period="{ value }">
                {{ formatDate(value, "MMMM yyyy") }}
            </template>

            <template #forecast_quantity="{ value }">
                {{ formatNumber(value) }}
            </template>

            <template #actual_quantity="{ value }">
                <span v-if="value !== null">{{ formatNumber(value) }}</span>
                <span v-else class="text-muted">--</span>
            </template>

            <template #variance="{ value, item }">
                <span
                    v-if="item.actual_quantity !== null"
                    :class="getVarianceClass(value)"
                >
                    {{ formatNumber(value) }}
                    <i v-if="value > 0" class="fas fa-arrow-up"></i>
                    <i v-else-if="value < 0" class="fas fa-arrow-down"></i>
                </span>
                <span v-else class="text-muted">--</span>
            </template>

            <template #accuracy="{ item }">
                <span
                    v-if="item.actual_quantity !== null"
                    :class="getAccuracyClass(item)"
                >
                    {{ calculateAccuracy(item) }}%
                </span>
                <span v-else class="text-muted">--</span>
            </template>

            <template #actions="{ item }">
                <button
                    class="action-btn"
                    title="View Details"
                    @click="viewForecast(item)"
                >
                    <i class="fas fa-eye"></i>
                </button>
                <button
                    class="action-btn"
                    title="Edit Forecast"
                    @click="editForecast(item)"
                >
                    <i class="fas fa-edit"></i>
                </button>
                <button
                    class="action-btn"
                    title="Delete Forecast"
                    @click="confirmDelete(item)"
                >
                    <i class="fas fa-trash"></i>
                </button>
            </template>
        </DataTable>

        <!-- Pagination -->
        <PaginationComponent
            v-if="totalForecasts > 0"
            :current-page="currentPage"
            :total-pages="totalPages"
            :from="(currentPage - 1) * perPage + 1"
            :to="Math.min(currentPage * perPage, totalForecasts)"
            :total="totalForecasts"
            @page-changed="changePage"
        />

        <!-- Create/Edit Forecast Modal -->
        <SalesForecastFormModal
            v-if="showFormModal"
            :is-edit-mode="isEditMode"
            :forecast-data="currentForecast"
            :customers="customers"
            :items="items"
            @close="closeFormModal"
            @save="saveForecast"
        />

        <!-- Generate Forecasts Modal -->
        <SalesForecastGenerateModal
            v-if="showGenerateModal"
            :customers="customers"
            :items="items"
            @close="closeGenerateModal"
            @generate="generateForecasts"
        />

        <!-- Delete Confirmation Modal -->
        <ConfirmationModal
            v-if="showDeleteModal"
            title="Confirm Delete"
            :message="`Are you sure you want to delete this forecast for ${
                forecastToDelete.item?.name
            } (${formatDate(
                forecastToDelete.forecast_period,
                'MMMM yyyy'
            )})?<br>This action cannot be undone.`"
            confirm-button-text="Delete"
            confirm-button-class="btn btn-danger"
            @confirm="deleteForecast"
            @close="closeDeleteModal"
        />

        <!-- Update Actuals Modal -->
        <SalesForecastUpdateActualsModal
            v-if="showUpdateActualsModal"
            @close="closeUpdateActualsModal"
            @update="updateForecastActuals"
        />
    </div>
</template>

<script>
import { ref, onMounted, reactive } from "vue";
import { useRouter } from "vue-router";
import axios from "axios";
import SalesForecastFormModal from "../sales/SalesForecastFormModal.vue";
import SalesForecastGenerateModal from "../sales/SalesForecastGenerateModal.vue";
import SalesForecastUpdateActualsModal from "../sales/SalesForecastUpdateActualsModal.vue";

export default {
    name: "SalesForecastList",
    components: {
        SalesForecastFormModal,
        SalesForecastGenerateModal,
        SalesForecastUpdateActualsModal,
    },
    setup() {
        const router = useRouter();
        const forecasts = ref([]);
        const customers = ref([]);
        const items = ref([]);
        const isLoading = ref(true);

        // Search and filtering
        const searchQuery = ref("");
        const filters = reactive({
            customer_id: "",
            item_id: "",
            period: "current_month",
        });

        const customDateRange = ref({
            startDate: new Date().toISOString().substr(0, 10),
            endDate: new Date().toISOString().substr(0, 10),
        });

        // Pagination
        const currentPage = ref(1);
        const perPage = ref(10);
        const totalForecasts = ref(0);
        const totalPages = ref(1);

        // Sorting
        const sortBy = ref("forecast_period");
        const sortOrder = ref("desc");

        // Modals state
        const showFormModal = ref(false);
        const showGenerateModal = ref(false);
        const showDeleteModal = ref(false);
        const showUpdateActualsModal = ref(false);
        const isEditMode = ref(false);
        const currentForecast = ref({});
        const forecastToDelete = ref({});

        // Table columns
        const columns = ref([
            {
                key: "forecast_period",
                label: "Period",
                sortable: true,
                template: "period",
            },
            { key: "item.name", label: "Item", sortable: true },
            { key: "customer.name", label: "Customer", sortable: true },
            {
                key: "forecast_quantity",
                label: "Forecast Qty",
                sortable: true,
                template: "forecast_quantity",
            },
            {
                key: "actual_quantity",
                label: "Actual Qty",
                sortable: true,
                template: "actual_quantity",
            },
            {
                key: "variance",
                label: "Variance",
                sortable: true,
                template: "variance",
            },
            {
                key: "accuracy",
                label: "Accuracy",
                sortable: false,
                template: "accuracy",
            },
        ]);

        // Fetch data methods
        const fetchForecasts = async () => {
            isLoading.value = true;

            try {
                // Build query parameters
                const params = {
                    page: currentPage.value,
                    per_page: perPage.value,
                    sort_by: sortBy.value,
                    sort_order: sortOrder.value,
                    search: searchQuery.value,
                };

                // Add filters
                if (filters.customer_id) {
                    params.customer_id = filters.customer_id;
                }

                if (filters.item_id) {
                    params.item_id = filters.item_id;
                }

                // Add date range
                if (filters.period === "custom") {
                    params.start_date = customDateRange.value.startDate;
                    params.end_date = customDateRange.value.endDate;
                } else if (filters.period === "current_month") {
                    const now = new Date();
                    params.start_date = new Date(
                        now.getFullYear(),
                        now.getMonth(),
                        1
                    )
                        .toISOString()
                        .substr(0, 10);
                    params.end_date = new Date(
                        now.getFullYear(),
                        now.getMonth() + 1,
                        0
                    )
                        .toISOString()
                        .substr(0, 10);
                } else if (filters.period === "last_month") {
                    const now = new Date();
                    params.start_date = new Date(
                        now.getFullYear(),
                        now.getMonth() - 1,
                        1
                    )
                        .toISOString()
                        .substr(0, 10);
                    params.end_date = new Date(
                        now.getFullYear(),
                        now.getMonth(),
                        0
                    )
                        .toISOString()
                        .substr(0, 10);
                } else if (filters.period === "last_quarter") {
                    const now = new Date();
                    const quarter = Math.floor(now.getMonth() / 3);
                    params.start_date = new Date(
                        now.getFullYear(),
                        quarter * 3 - 3,
                        1
                    )
                        .toISOString()
                        .substr(0, 10);
                    params.end_date = new Date(
                        now.getFullYear(),
                        quarter * 3,
                        0
                    )
                        .toISOString()
                        .substr(0, 10);
                } else if (filters.period === "last_year") {
                    const now = new Date();
                    params.start_date = new Date(now.getFullYear() - 1, 0, 1)
                        .toISOString()
                        .substr(0, 10);
                    params.end_date = new Date(now.getFullYear() - 1, 11, 31)
                        .toISOString()
                        .substr(0, 10);
                }

                const response = await axios.get("/sales-forecasts", {
                    params,
                });

                forecasts.value = response.data.data;
                totalForecasts.value =
                    response.data.meta?.total || forecasts.value.length;
                totalPages.value =
                    response.data.meta?.last_page ||
                    Math.ceil(totalForecasts.value / perPage.value);
            } catch (error) {
                console.error("Error fetching forecasts:", error);
            } finally {
                isLoading.value = false;
            }
        };

        const fetchCustomers = async () => {
            try {
                const response = await axios.get("/customers");
                customers.value = response.data.data;
            } catch (error) {
                console.error("Error fetching customers:", error);
            }
        };

        const fetchItems = async () => {
            try {
                const response = await axios.get("/items");
                items.value = response.data.data;
            } catch (error) {
                console.error("Error fetching items:", error);
            }
        };

        // Forecast operations
        const openCreateModal = () => {
            isEditMode.value = false;
            currentForecast.value = {
                item_id: "",
                customer_id: "",
                forecast_period: new Date().toISOString().substr(0, 10),
                forecast_quantity: 0,
                actual_quantity: null,
                variance: null,
            };
            showFormModal.value = true;
        };

        const editForecast = (forecast) => {
            isEditMode.value = true;
            currentForecast.value = { ...forecast };
            showFormModal.value = true;
        };

        const closeFormModal = () => {
            showFormModal.value = false;
        };

        const saveForecast = async (forecastData) => {
            try {
                if (isEditMode.value) {
                    await axios.put(
                        `/sales-forecasts/${forecastData.forecast_id}`,
                        forecastData
                    );
                } else {
                    await axios.post("/sales-forecasts", forecastData);
                }

                closeFormModal();
                fetchForecasts(); // Refresh the list
            } catch (error) {
                console.error("Error saving forecast:", error);
                alert("Failed to save forecast. Please try again.");
            }
        };

        const viewForecast = (forecast) => {
            router.push(`/sales/forecasts/${forecast.forecast_id}`);
        };

        const confirmDelete = (forecast) => {
            forecastToDelete.value = forecast;
            showDeleteModal.value = true;
        };

        const closeDeleteModal = () => {
            showDeleteModal.value = false;
        };

        const deleteForecast = async () => {
            try {
                await axios.delete(
                    `/sales-forecasts/${forecastToDelete.value.forecast_id}`
                );
                closeDeleteModal();
                fetchForecasts(); // Refresh the list
            } catch (error) {
                console.error("Error deleting forecast:", error);
                alert("Failed to delete forecast. Please try again.");
            }
        };

        // Forecast generation
        const openGenerateModal = () => {
            showGenerateModal.value = true;
        };

        const closeGenerateModal = () => {
            showGenerateModal.value = false;
        };

        const generateForecasts = async (params) => {
            try {
                const response = await axios.post(
                    "/sales-forecasts/generate",
                    params
                );
                closeGenerateModal();
                fetchForecasts(); // Refresh the list
                alert(`${response.data.message}`);
            } catch (error) {
                console.error("Error generating forecasts:", error);
                alert("Failed to generate forecasts. Please try again.");
            }
        };

        // Update actuals
        const updateActuals = () => {
            showUpdateActualsModal.value = true;
        };

        const closeUpdateActualsModal = () => {
            showUpdateActualsModal.value = false;
        };

        const updateForecastActuals = async (params) => {
            try {
                const response = await axios.post(
                    "/sales-forecasts/update-actuals",
                    params
                );
                closeUpdateActualsModal();
                fetchForecasts(); // Refresh the list
                alert(`${response.data.message}`);
            } catch (error) {
                console.error("Error updating actuals:", error);
                alert("Failed to update actual quantities. Please try again.");
            }
        };

        // Analytics
        const navigateToAnalytics = () => {
            router.push("/sales/forecasts/analytics");
        };

        // Filters and search
        const applyFilters = () => {
            currentPage.value = 1;
            fetchForecasts();
        };

        const clearSearch = () => {
            searchQuery.value = "";
            applyFilters();
        };

        // Pagination
        const changePage = (page) => {
            currentPage.value = page;
            fetchForecasts();
        };

        // Sorting
        const handleSort = ({ key, order }) => {
            sortBy.value = key;
            sortOrder.value = order;
            fetchForecasts();
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
            if (!variance) return "";

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

        // Lifecycle hooks
        onMounted(async () => {
            await Promise.all([fetchCustomers(), fetchItems()]);
            fetchForecasts();
        });

        return {
            // Data
            forecasts,
            customers,
            items,
            isLoading,
            searchQuery,
            filters,
            customDateRange,
            currentPage,
            perPage,
            totalForecasts,
            totalPages,
            columns,

            // Modal state
            showFormModal,
            showGenerateModal,
            showDeleteModal,
            showUpdateActualsModal,
            isEditMode,
            currentForecast,
            forecastToDelete,

            // Methods
            formatDate,
            formatNumber,
            getVarianceClass,
            calculateAccuracy,
            getAccuracyClass,
            openCreateModal,
            editForecast,
            closeFormModal,
            saveForecast,
            viewForecast,
            confirmDelete,
            closeDeleteModal,
            deleteForecast,
            openGenerateModal,
            closeGenerateModal,
            generateForecasts,
            updateActuals,
            closeUpdateActualsModal,
            updateForecastActuals,
            navigateToAnalytics,
            applyFilters,
            clearSearch,
            changePage,
            handleSort,
        };
    },
};
</script>

<style scoped>
.sales-forecasts {
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

.page-actions {
    display: flex;
    gap: 0.75rem;
}

.custom-date-range {
    background-color: var(--gray-50);
    border: 1px solid var(--gray-200);
    border-radius: 0.375rem;
    padding: 1rem;
    margin-bottom: 1rem;
}

.date-range-inputs {
    display: flex;
    gap: 1rem;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
}

.filter-group label {
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--gray-500);
}

.filter-group select,
.filter-group input {
    padding: 0.5rem;
    border: 1px solid var(--gray-200);
    border-radius: 0.375rem;
    font-size: 0.875rem;
    min-width: 8rem;
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

.action-btn {
    background: none;
    border: none;
    color: var(--gray-500);
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 0.25rem;
    transition: background-color 0.2s;
}

.action-btn:hover {
    background-color: var(--gray-100);
    color: var(--gray-700);
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }

    .date-range-inputs {
        flex-direction: column;
    }
}
</style>
