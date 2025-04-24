<!-- src/views/purchasing/PurchasingDashboard.vue -->
<template>
    <div class="dashboard-container">
        <div class="header-section mb-4">
            <h1 class="page-title">Purchasing Overview</h1>
            <div class="date-filter d-flex align-items-center">
                <div class="form-group mr-3">
                    <label for="dateRangeSelect" class="mb-1"
                        >Time Period:</label
                    >
                    <select
                        id="dateRangeSelect"
                        v-model="dateRange"
                        class="form-control"
                        @change="loadDashboardData"
                    >
                        <option value="week">Last 7 Days</option>
                        <option value="month">Last 30 Days</option>
                        <option value="quarter">Last Quarter</option>
                        <option value="year">Last Year</option>
                        <option value="custom">Custom Range</option>
                    </select>
                </div>
                <div
                    v-if="dateRange === 'custom'"
                    class="d-flex align-items-center"
                >
                    <div class="form-group mr-2">
                        <label for="startDate" class="mb-1">From:</label>
                        <input
                            type="date"
                            id="startDate"
                            v-model="startDate"
                            class="form-control"
                            @change="loadDashboardData"
                        />
                    </div>
                    <div class="form-group">
                        <label for="endDate" class="mb-1">To:</label>
                        <input
                            type="date"
                            id="endDate"
                            v-model="endDate"
                            class="form-control"
                            @change="loadDashboardData"
                        />
                    </div>
                </div>
                <button class="btn btn-primary ml-3" @click="loadDashboardData">
                    <i class="fas fa-sync-alt mr-1"></i> Refresh
                </button>
            </div>
        </div>

        <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Loading...</span>
            </div>
            <p class="mt-2">Loading dashboard data...</p>
        </div>

        <div v-else class="dashboard-content">
            <!-- Summary Cards Row -->
            <div
                class="grid grid-cols-4 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4"
            >
                <div class="card">
                    <div class="card-body d-flex align-items-center">
                        <div
                            class="icon-container bg-primary-bg p-3 rounded-circle mr-3"
                        >
                            <i class="fas fa-file-invoice text-primary"></i>
                        </div>
                        <div>
                            <h6 class="card-title text-gray-600 mb-0">
                                Total POs
                            </h6>
                            <h3 class="mb-0">{{ summary.totalPOs }}</h3>
                            <small
                                :class="
                                    summary.poChangePercentage >= 0
                                        ? 'text-success'
                                        : 'text-danger'
                                "
                            >
                                <i
                                    :class="
                                        summary.poChangePercentage >= 0
                                            ? 'fas fa-arrow-up'
                                            : 'fas fa-arrow-down'
                                    "
                                ></i>
                                {{ Math.abs(summary.poChangePercentage) }}% from
                                previous period
                            </small>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body d-flex align-items-center">
                        <div
                            class="icon-container bg-success-bg p-3 rounded-circle mr-3"
                        >
                            <i class="fas fa-dollar-sign text-success"></i>
                        </div>
                        <div>
                            <h6 class="card-title text-gray-600 mb-0">
                                Total Spend
                            </h6>
                            <h3 class="mb-0">
                                ${{ formatNumber(summary.totalSpend) }}
                            </h3>
                            <small
                                :class="
                                    summary.spendChangePercentage >= 0
                                        ? 'text-success'
                                        : 'text-danger'
                                "
                            >
                                <i
                                    :class="
                                        summary.spendChangePercentage >= 0
                                            ? 'fas fa-arrow-up'
                                            : 'fas fa-arrow-down'
                                    "
                                ></i>
                                {{ Math.abs(summary.spendChangePercentage) }}%
                                from previous period
                            </small>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body d-flex align-items-center">
                        <div
                            class="icon-container bg-warning-bg p-3 rounded-circle mr-3"
                        >
                            <i class="fas fa-truck text-warning"></i>
                        </div>
                        <div>
                            <h6 class="card-title text-gray-600 mb-0">
                                Pending Deliveries
                            </h6>
                            <h3 class="mb-0">
                                {{ summary.pendingDeliveries }}
                            </h3>
                            <small
                                :class="
                                    summary.deliveryChangePercentage >= 0
                                        ? 'text-danger'
                                        : 'text-success'
                                "
                            >
                                <i
                                    :class="
                                        summary.deliveryChangePercentage >= 0
                                            ? 'fas fa-arrow-up'
                                            : 'fas fa-arrow-down'
                                    "
                                ></i>
                                {{
                                    Math.abs(summary.deliveryChangePercentage)
                                }}% from previous period
                            </small>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body d-flex align-items-center">
                        <div
                            class="icon-container bg-danger-bg p-3 rounded-circle mr-3"
                        >
                            <i
                                class="fas fa-exclamation-triangle text-danger"
                            ></i>
                        </div>
                        <div>
                            <h6 class="card-title text-gray-600 mb-0">
                                Late Deliveries
                            </h6>
                            <h3 class="mb-0">{{ summary.lateDeliveries }}</h3>
                            <small
                                :class="
                                    summary.lateDeliveryChangePercentage >= 0
                                        ? 'text-danger'
                                        : 'text-success'
                                "
                            >
                                <i
                                    :class="
                                        summary.lateDeliveryChangePercentage >=
                                        0
                                            ? 'fas fa-arrow-up'
                                            : 'fas fa-arrow-down'
                                    "
                                ></i>
                                {{
                                    Math.abs(
                                        summary.lateDeliveryChangePercentage
                                    )
                                }}% from previous period
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div
                class="grid grid-cols-2 sm:grid-cols-1 lg:grid-cols-2 gap-4 mb-4"
            >
                <div class="card">
                    <div
                        class="card-header d-flex justify-content-between align-items-center"
                    >
                        <h5 class="card-title mb-0">PO Status Distribution</h5>
                        <div class="btn-group">
                            <button
                                class="btn btn-sm"
                                :class="
                                    poChartType === 'pie'
                                        ? 'btn-primary'
                                        : 'btn-secondary'
                                "
                                @click="poChartType = 'pie'"
                            >
                                <i class="fas fa-chart-pie"></i>
                            </button>
                            <button
                                class="btn btn-sm"
                                :class="
                                    poChartType === 'bar'
                                        ? 'btn-primary'
                                        : 'btn-secondary'
                                "
                                @click="poChartType = 'bar'"
                            >
                                <i class="fas fa-chart-bar"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="chart-container" style="height: 250px">
                            <po-status-chart
                                :chart-data="poStatusData"
                                :chart-type="poChartType"
                            />
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Monthly Purchase Amount</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container" style="height: 250px">
                            <monthly-spend-chart
                                :chart-data="monthlySpendData"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Vendors and Recent PO Row -->
            <div class="grid grid-cols-2 sm:grid-cols-1 lg:grid-cols-2 gap-4">
                <div class="card">
                    <div
                        class="card-header d-flex justify-content-between align-items-center"
                    >
                        <h5 class="card-title mb-0">Top Vendors</h5>
                        <div class="dropdown">
                            <button
                                class="btn btn-sm btn-secondary dropdown-toggle"
                                type="button"
                                id="vendorFilterDropdown"
                                data-toggle="dropdown"
                            >
                                {{ vendorFilterText }}
                            </button>
                            <div class="dropdown-menu dropdown-menu-right">
                                <a
                                    class="dropdown-item"
                                    href="#"
                                    @click.prevent="setVendorFilter('amount')"
                                    >By Amount</a
                                >
                                <a
                                    class="dropdown-item"
                                    href="#"
                                    @click.prevent="setVendorFilter('count')"
                                    >By PO Count</a
                                >
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Vendor</th>
                                        <th>PO Count</th>
                                        <th>Total Amount</th>
                                        <th>Performance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="vendor in topVendors"
                                        :key="vendor.vendor_id"
                                    >
                                        <td>
                                            <router-link
                                                :to="`/purchasing/vendors/${vendor.vendor_id}`"
                                                class="font-weight-bold text-primary"
                                            >
                                                {{ vendor.name }}
                                            </router-link>
                                        </td>
                                        <td>{{ vendor.po_count }}</td>
                                        <td>
                                            ${{
                                                formatNumber(
                                                    vendor.total_amount
                                                )
                                            }}
                                        </td>
                                        <td>
                                            <div
                                                class="d-flex align-items-center"
                                            >
                                                <div
                                                    class="progress flex-grow-1 mr-2"
                                                    style="height: 6px"
                                                >
                                                    <div
                                                        class="progress-bar"
                                                        :class="
                                                            getPerformanceColor(
                                                                vendor.performance_score
                                                            )
                                                        "
                                                        :style="`width: ${vendor.performance_score}%`"
                                                    ></div>
                                                </div>
                                                <span
                                                    >{{
                                                        vendor.performance_score
                                                    }}%</span
                                                >
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Recent Purchase Orders</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>PO Number</th>
                                        <th>Vendor</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="po in recentPOs" :key="po.po_id">
                                        <td>
                                            <router-link
                                                :to="`/purchasing/orders/${po.po_id}`"
                                                class="font-weight-bold text-primary"
                                            >
                                                {{ po.po_number }}
                                            </router-link>
                                        </td>
                                        <td>{{ po.vendor.name }}</td>
                                        <td>{{ formatDate(po.po_date) }}</td>
                                        <td>
                                            ${{ formatNumber(po.total_amount) }}
                                        </td>
                                        <td>
                                            <span
                                                class="badge"
                                                :class="
                                                    getStatusBadgeClass(
                                                        po.status
                                                    )
                                                "
                                            >
                                                {{ po.status }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer text-center">
                            <router-link
                                to="/purchasing/orders"
                                class="btn btn-sm btn-outline-primary"
                            >
                                View All Purchase Orders
                            </router-link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from "axios";
import PoStatusChart from "./PoStatusChart.vue";
import MonthlySpendChart from "./MonthlySpendChart.vue";

export default {
    name: "PurchasingDashboard",
    components: {
        PoStatusChart,
        MonthlySpendChart,
    },
    data() {
        return {
            loading: true,
            dateRange: "month",
            startDate: null,
            endDate: null,
            vendorFilter: "amount",
            poChartType: "pie",
            summary: {
                totalPOs: 0,
                totalSpend: 0,
                pendingDeliveries: 0,
                lateDeliveries: 0,
                poChangePercentage: 0,
                spendChangePercentage: 0,
                deliveryChangePercentage: 0,
                lateDeliveryChangePercentage: 0,
            },
            poStatusData: [],
            monthlySpendData: [],
            topVendors: [],
            recentPOs: [],
        };
    },
    computed: {
        vendorFilterText() {
            return this.vendorFilter === "amount" ? "By Amount" : "By PO Count";
        },
    },
    created() {
        // Set default date range (last 30 days)
        const end = new Date();
        const start = new Date();
        start.setDate(start.getDate() - 30);

        this.startDate = this.formatDateForInput(start);
        this.endDate = this.formatDateForInput(end);

        this.loadDashboardData();
    },
    methods: {
        async loadDashboardData() {
            this.loading = true;

            try {
                // Calculate date range based on selection
                let startDate = this.startDate;
                let endDate = this.endDate;

                if (this.dateRange !== "custom") {
                    const end = new Date();
                    const start = new Date();

                    if (this.dateRange === "week") {
                        start.setDate(start.getDate() - 7);
                    } else if (this.dateRange === "month") {
                        start.setDate(start.getDate() - 30);
                    } else if (this.dateRange === "quarter") {
                        start.setMonth(start.getMonth() - 3);
                    } else if (this.dateRange === "year") {
                        start.setFullYear(start.getFullYear() - 1);
                    }

                    startDate = this.formatDateForInput(start);
                    endDate = this.formatDateForInput(end);
                }

                // Make API call to get dashboard data
                const response = await axios.get("/api/purchasing/dashboard", {
                    params: {
                        start_date: startDate,
                        end_date: endDate,
                    },
                });

                const data = response.data.data;

                // Update component data with API response
                this.summary = data.summary;
                this.poStatusData = data.poStatusData;
                this.monthlySpendData = data.monthlySpendData;
                this.topVendors = data.topVendors;
                this.recentPOs = data.recentPOs;
            } catch (error) {
                console.error("Error loading dashboard data:", error);

                // For demo purposes, fill with mock data if API call fails
                this.loadMockData();
            } finally {
                this.loading = false;
            }
        },

        // Mock data for development/demo purposes
        loadMockData() {
            this.summary = {
                totalPOs: 124,
                totalSpend: 458920.75,
                pendingDeliveries: 18,
                lateDeliveries: 5,
                poChangePercentage: 12.4,
                spendChangePercentage: -5.2,
                deliveryChangePercentage: -8.7,
                lateDeliveryChangePercentage: -2.1,
            };

            this.poStatusData = [
                { name: "Draft", value: 14, color: "#94a3b8" },
                { name: "Approved", value: 32, color: "#3b82f6" },
                { name: "Sent", value: 48, color: "#0ea5e9" },
                { name: "Partial", value: 18, color: "#f59e0b" },
                { name: "Completed", value: 62, color: "#10b981" },
                { name: "Canceled", value: 6, color: "#ef4444" },
            ];

            this.monthlySpendData = [
                { month: "Jan", amount: 42500 },
                { month: "Feb", amount: 38200 },
                { month: "Mar", amount: 45800 },
                { month: "Apr", amount: 39600 },
                { month: "May", amount: 47200 },
                { month: "Jun", amount: 52100 },
                { month: "Jul", amount: 48900 },
                { month: "Aug", amount: 53600 },
                { month: "Sep", amount: 49700 },
                { month: "Oct", amount: 51300 },
                { month: "Nov", amount: 54800 },
                { month: "Dec", amount: 58900 },
            ];

            this.topVendors = [
                {
                    vendor_id: 1,
                    name: "ABC Supplies",
                    po_count: 28,
                    total_amount: 124500.0,
                    performance_score: 92,
                },
                {
                    vendor_id: 2,
                    name: "XYZ Manufacturing",
                    po_count: 16,
                    total_amount: 98700.5,
                    performance_score: 88,
                },
                {
                    vendor_id: 3,
                    name: "Global Distributors",
                    po_count: 22,
                    total_amount: 86200.25,
                    performance_score: 94,
                },
                {
                    vendor_id: 4,
                    name: "Tech Solutions Inc",
                    po_count: 12,
                    total_amount: 72400.0,
                    performance_score: 78,
                },
                {
                    vendor_id: 5,
                    name: "Reliable Parts Co",
                    po_count: 18,
                    total_amount: 54300.75,
                    performance_score: 85,
                },
            ];

            this.recentPOs = [
                {
                    po_id: 1,
                    po_number: "PO-2023-0124",
                    vendor: { name: "ABC Supplies" },
                    po_date: "2023-11-25",
                    total_amount: 12450.0,
                    status: "approved",
                },
                {
                    po_id: 2,
                    po_number: "PO-2023-0123",
                    vendor: { name: "XYZ Manufacturing" },
                    po_date: "2023-11-22",
                    total_amount: 8760.5,
                    status: "sent",
                },
                {
                    po_id: 3,
                    po_number: "PO-2023-0122",
                    vendor: { name: "Global Distributors" },
                    po_date: "2023-11-20",
                    total_amount: 6540.25,
                    status: "partial",
                },
                {
                    po_id: 4,
                    po_number: "PO-2023-0121",
                    vendor: { name: "Tech Solutions Inc" },
                    po_date: "2023-11-18",
                    total_amount: 4320.0,
                    status: "completed",
                },
                {
                    po_id: 5,
                    po_number: "PO-2023-0120",
                    vendor: { name: "Reliable Parts Co" },
                    po_date: "2023-11-15",
                    total_amount: 9870.75,
                    status: "draft",
                },
            ];
        },

        setVendorFilter(filter) {
            this.vendorFilter = filter;
            // In a real application, you would reload the top vendors data with the new filter
        },

        formatNumber(number) {
            return number
                ? number.toLocaleString("en-US", {
                      minimumFractionDigits: 2,
                      maximumFractionDigits: 2,
                  })
                : "0.00";
        },

        formatDate(dateString) {
            if (!dateString) return "";
            const date = new Date(dateString);
            return date.toLocaleDateString("en-US", {
                year: "numeric",
                month: "short",
                day: "numeric",
            });
        },

        formatDateForInput(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, "0");
            const day = String(date.getDate()).padStart(2, "0");
            return `${year}-${month}-${day}`;
        },

        getStatusBadgeClass(status) {
            switch (status) {
                case "draft":
                    return "badge-secondary";
                case "submitted":
                    return "badge-info";
                case "approved":
                    return "badge-primary";
                case "sent":
                    return "badge-primary";
                case "partial":
                    return "badge-warning";
                case "received":
                    return "badge-success";
                case "completed":
                    return "badge-success";
                case "canceled":
                    return "badge-danger";
                default:
                    return "badge-secondary";
            }
        },

        getPerformanceColor(score) {
            if (score >= 90) return "bg-success";
            if (score >= 70) return "bg-primary";
            if (score >= 50) return "bg-warning";
            return "bg-danger";
        },
    },
};
</script>

<style scoped>
.dashboard-container {
    margin-bottom: 2rem;
}

.header-section {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
}

.chart-container {
    width: 100%;
    height: 100%;
}

.icon-container {
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}

.icon-container i {
    font-size: 1.25rem;
}

.progress {
    background-color: var(--gray-200);
}

@media (max-width: 768px) {
    .header-section {
        flex-direction: column;
        align-items: flex-start;
    }

    .date-filter {
        margin-top: 1rem;
        width: 100%;
    }
}
</style>
