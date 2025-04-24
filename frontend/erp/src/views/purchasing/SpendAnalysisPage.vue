<!-- src/views/purchasing/SpendAnalysisPage.vue -->
<template>
    <div class="spend-analysis-container">
        <div class="header-section mb-4">
            <h1 class="page-title">Spend Analysis</h1>
            <div class="filter-controls d-flex align-items-center flex-wrap">
                <div class="form-group mr-3">
                    <label for="dateRange" class="mb-1">Time Period:</label>
                    <select
                        id="dateRange"
                        v-model="filters.dateRange"
                        class="form-control"
                        @change="applyFilters"
                    >
                        <option value="month">Last 30 Days</option>
                        <option value="quarter">Last Quarter</option>
                        <option value="year">Last Year</option>
                        <option value="custom">Custom Range</option>
                    </select>
                </div>
                <div
                    v-if="filters.dateRange === 'custom'"
                    class="date-range-picker d-flex align-items-center"
                >
                    <div class="form-group mr-2">
                        <label for="startDate" class="mb-1">From:</label>
                        <input
                            type="date"
                            id="startDate"
                            v-model="filters.startDate"
                            class="form-control"
                        />
                    </div>
                    <div class="form-group">
                        <label for="endDate" class="mb-1">To:</label>
                        <input
                            type="date"
                            id="endDate"
                            v-model="filters.endDate"
                            class="form-control"
                        />
                    </div>
                </div>
                <div class="form-group mx-3">
                    <label for="categoryFilter" class="mb-1">Category:</label>
                    <select
                        id="categoryFilter"
                        v-model="filters.category"
                        class="form-control"
                    >
                        <option value="">All Categories</option>
                        <option
                            v-for="category in categories"
                            :key="category.id"
                            :value="category.id"
                        >
                            {{ category.name }}
                        </option>
                    </select>
                </div>
                <div class="form-group mr-3">
                    <label for="vendorFilter" class="mb-1">Vendor:</label>
                    <select
                        id="vendorFilter"
                        v-model="filters.vendor"
                        class="form-control"
                    >
                        <option value="">All Vendors</option>
                        <option
                            v-for="vendor in vendors"
                            :key="vendor.vendor_id"
                            :value="vendor.vendor_id"
                        >
                            {{ vendor.name }}
                        </option>
                    </select>
                </div>
                <button class="btn btn-primary mt-3" @click="applyFilters">
                    <i class="fas fa-filter mr-1"></i> Apply Filters
                </button>
                <button
                    class="btn btn-secondary mt-3 ml-2"
                    @click="resetFilters"
                >
                    <i class="fas fa-undo mr-1"></i> Reset
                </button>
            </div>
        </div>

        <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Loading...</span>
            </div>
            <p class="mt-2">Loading data...</p>
        </div>

        <div v-else class="analysis-content">
            <!-- Summary Cards -->
            <div
                class="grid grid-cols-3 sm:grid-cols-1 md:grid-cols-3 gap-4 mb-4"
            >
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title text-gray-600">Total Spend</h5>
                        <div class="d-flex align-items-center">
                            <h2 class="mb-0">
                                ${{ formatNumber(summary.totalSpend) }}
                            </h2>
                            <span
                                class="ml-2"
                                :class="
                                    summary.spendChange >= 0
                                        ? 'text-success'
                                        : 'text-danger'
                                "
                            >
                                <i
                                    :class="
                                        summary.spendChange >= 0
                                            ? 'fas fa-arrow-up'
                                            : 'fas fa-arrow-down'
                                    "
                                ></i>
                                {{ Math.abs(summary.spendChange) }}%
                            </span>
                        </div>
                        <small class="text-gray-500">vs. previous period</small>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title text-gray-600">Total Orders</h5>
                        <div class="d-flex align-items-center">
                            <h2 class="mb-0">{{ summary.totalOrders }}</h2>
                            <span
                                class="ml-2"
                                :class="
                                    summary.orderChange >= 0
                                        ? 'text-success'
                                        : 'text-danger'
                                "
                            >
                                <i
                                    :class="
                                        summary.orderChange >= 0
                                            ? 'fas fa-arrow-up'
                                            : 'fas fa-arrow-down'
                                    "
                                ></i>
                                {{ Math.abs(summary.orderChange) }}%
                            </span>
                        </div>
                        <small class="text-gray-500">vs. previous period</small>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title text-gray-600">
                            Average Order Value
                        </h5>
                        <div class="d-flex align-items-center">
                            <h2 class="mb-0">
                                ${{ formatNumber(summary.avgOrderValue) }}
                            </h2>
                            <span
                                class="ml-2"
                                :class="
                                    summary.avgOrderChange >= 0
                                        ? 'text-success'
                                        : 'text-danger'
                                "
                            >
                                <i
                                    :class="
                                        summary.avgOrderChange >= 0
                                            ? 'fas fa-arrow-up'
                                            : 'fas fa-arrow-down'
                                    "
                                ></i>
                                {{ Math.abs(summary.avgOrderChange) }}%
                            </span>
                        </div>
                        <small class="text-gray-500">vs. previous period</small>
                    </div>
                </div>
            </div>

            <!-- Main Analysis Charts -->
            <div
                class="grid grid-cols-2 sm:grid-cols-1 md:grid-cols-2 gap-4 mb-4"
            >
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Spend by Category</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container" style="height: 300px">
                            <category-spend-chart :chart-data="categoryData" />
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Spend Trend</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container" style="height: 300px">
                            <spend-trend-chart :chart-data="trendData" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Vendors Card -->
            <div class="card mb-4">
                <div
                    class="card-header d-flex justify-content-between align-items-center"
                >
                    <h5 class="card-title mb-0">Top Vendors by Spend</h5>
                    <div class="btn-group">
                        <button
                            class="btn btn-sm"
                            :class="
                                vendorChartType === 'pie'
                                    ? 'btn-primary'
                                    : 'btn-secondary'
                            "
                            @click="vendorChartType = 'pie'"
                        >
                            <i class="fas fa-chart-pie"></i>
                        </button>
                        <button
                            class="btn btn-sm"
                            :class="
                                vendorChartType === 'bar'
                                    ? 'btn-primary'
                                    : 'btn-secondary'
                            "
                            @click="vendorChartType = 'bar'"
                        >
                            <i class="fas fa-chart-bar"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="height: 300px">
                        <vendor-spend-chart
                            :chart-data="vendorData"
                            :chart-type="vendorChartType"
                        />
                    </div>
                </div>
            </div>

            <!-- Spend Details Table -->
            <div class="card">
                <div
                    class="card-header d-flex justify-content-between align-items-center"
                >
                    <h5 class="card-title mb-0">Spend Details</h5>
                    <button
                        class="btn btn-sm btn-outline-primary"
                        @click="downloadCSV"
                    >
                        <i class="fas fa-download mr-1"></i> Export
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th
                                        class="sortable"
                                        @click="sortTable('date')"
                                    >
                                        Date
                                        <i
                                            :class="getSortIconClass('date')"
                                        ></i>
                                    </th>
                                    <th
                                        class="sortable"
                                        @click="sortTable('po_number')"
                                    >
                                        PO Number
                                        <i
                                            :class="
                                                getSortIconClass('po_number')
                                            "
                                        ></i>
                                    </th>
                                    <th
                                        class="sortable"
                                        @click="sortTable('vendor')"
                                    >
                                        Vendor
                                        <i
                                            :class="getSortIconClass('vendor')"
                                        ></i>
                                    </th>
                                    <th
                                        class="sortable"
                                        @click="sortTable('category')"
                                    >
                                        Category
                                        <i
                                            :class="
                                                getSortIconClass('category')
                                            "
                                        ></i>
                                    </th>
                                    <th
                                        class="sortable"
                                        @click="sortTable('amount')"
                                    >
                                        Amount
                                        <i
                                            :class="getSortIconClass('amount')"
                                        ></i>
                                    </th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="item in paginatedData"
                                    :key="item.id"
                                >
                                    <td>{{ formatDate(item.date) }}</td>
                                    <td>
                                        <router-link
                                            :to="`/purchasing/orders/${item.po_id}`"
                                            class="text-primary"
                                        >
                                            {{ item.po_number }}
                                        </router-link>
                                    </td>
                                    <td>{{ item.vendor }}</td>
                                    <td>{{ item.category }}</td>
                                    <td>${{ formatNumber(item.amount) }}</td>
                                    <td>
                                        <span
                                            class="badge"
                                            :class="
                                                getStatusBadgeClass(item.status)
                                            "
                                        >
                                            {{ item.status }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="paginatedData.length === 0">
                                    <td colspan="6" class="text-center py-4">
                                        No data available
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    <div
                        class="d-flex justify-content-between align-items-center"
                    >
                        <div class="pagination-info">
                            Showing {{ paginationStart + 1 }} to
                            {{ paginationEnd }} of
                            {{ filteredData.length }} entries
                        </div>
                        <ul class="pagination mb-0">
                            <li
                                class="page-item"
                                :class="{ disabled: currentPage === 1 }"
                            >
                                <a
                                    class="page-link"
                                    href="#"
                                    @click.prevent="changePage(currentPage - 1)"
                                >
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                            <li
                                v-for="page in totalPages"
                                :key="page"
                                class="page-item"
                                :class="{ active: currentPage === page }"
                            >
                                <a
                                    class="page-link"
                                    href="#"
                                    @click.prevent="changePage(page)"
                                    >{{ page }}</a
                                >
                            </li>
                            <li
                                class="page-item"
                                :class="{
                                    disabled: currentPage === totalPages,
                                }"
                            >
                                <a
                                    class="page-link"
                                    href="#"
                                    @click.prevent="changePage(currentPage + 1)"
                                >
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from "axios";
import CategorySpendChart from "@/components/purchasing/charts/CategorySpendChart.vue";
import SpendTrendChart from "@/components/purchasing/charts/SpendTrendChart.vue";
import VendorSpendChart from "@/components/purchasing/charts/VendorSpendChart.vue";

export default {
    name: "SpendAnalysisPage",
    components: {
        CategorySpendChart,
        SpendTrendChart,
        VendorSpendChart,
    },
    data() {
        return {
            loading: true,
            filters: {
                dateRange: "month",
                startDate: null,
                endDate: null,
                category: "",
                vendor: "",
            },
            categories: [],
            vendors: [],
            summary: {
                totalSpend: 0,
                totalOrders: 0,
                avgOrderValue: 0,
                spendChange: 0,
                orderChange: 0,
                avgOrderChange: 0,
            },
            categoryData: [],
            trendData: [],
            vendorData: [],
            spendData: [],
            vendorChartType: "pie",

            // Table sorting and pagination
            sortField: "date",
            sortDirection: "desc",
            currentPage: 1,
            pageSize: 10,
        };
    },
    computed: {
        filteredData() {
            let data = [...this.spendData];

            if (this.filters.category) {
                data = data.filter(
                    (item) =>
                        item.category_id.toString() ===
                        this.filters.category.toString()
                );
            }

            if (this.filters.vendor) {
                data = data.filter(
                    (item) =>
                        item.vendor_id.toString() ===
                        this.filters.vendor.toString()
                );
            }

            // Apply sorting
            data.sort((a, b) => {
                let fieldA = a[this.sortField];
                let fieldB = b[this.sortField];

                if (this.sortField === "amount") {
                    fieldA = parseFloat(fieldA);
                    fieldB = parseFloat(fieldB);
                }

                if (fieldA < fieldB) {
                    return this.sortDirection === "asc" ? -1 : 1;
                }
                if (fieldA > fieldB) {
                    return this.sortDirection === "asc" ? 1 : -1;
                }
                return 0;
            });

            return data;
        },
        paginatedData() {
            const start = (this.currentPage - 1) * this.pageSize;
            const end = start + this.pageSize;
            return this.filteredData.slice(start, end);
        },
        totalPages() {
            return Math.ceil(this.filteredData.length / this.pageSize);
        },
        paginationStart() {
            return (this.currentPage - 1) * this.pageSize;
        },
        paginationEnd() {
            const end = this.paginationStart + this.pageSize;
            return end > this.filteredData.length
                ? this.filteredData.length
                : end;
        },
    },
    created() {
        // Set default date range (last 30 days)
        const end = new Date();
        const start = new Date();
        start.setDate(start.getDate() - 30);

        this.filters.startDate = this.formatDateForInput(start);
        this.filters.endDate = this.formatDateForInput(end);

        this.loadInitialData();
    },
    methods: {
        async loadInitialData() {
            this.loading = true;

            try {
                // Load categories and vendors for filters
                const [categoriesResponse, vendorsResponse] = await Promise.all(
                    [axios.get("/api/categories"), axios.get("/api/vendors")]
                );

                this.categories = categoriesResponse.data.data || [];
                this.vendors = vendorsResponse.data.data || [];

                // Load analysis data
                await this.loadAnalysisData();
            } catch (error) {
                console.error("Error loading initial data:", error);
                // Load mock data for demonstration
                this.loadMockData();
            } finally {
                this.loading = false;
            }
        },

        async loadAnalysisData() {
            try {
                // Calculate date range based on filter
                let startDate = this.filters.startDate;
                let endDate = this.filters.endDate;

                if (this.filters.dateRange !== "custom") {
                    const end = new Date();
                    const start = new Date();

                    if (this.filters.dateRange === "month") {
                        start.setDate(start.getDate() - 30);
                    } else if (this.filters.dateRange === "quarter") {
                        start.setMonth(start.getMonth() - 3);
                    } else if (this.filters.dateRange === "year") {
                        start.setFullYear(start.getFullYear() - 1);
                    }

                    startDate = this.formatDateForInput(start);
                    endDate = this.formatDateForInput(end);

                    // Update the date inputs for UI consistency
                    this.filters.startDate = startDate;
                    this.filters.endDate = endDate;
                }

                // API call to get spend analysis data
                const response = await axios.get(
                    "/api/purchasing/spend-analysis",
                    {
                        params: {
                            start_date: startDate,
                            end_date: endDate,
                            category_id: this.filters.category,
                            vendor_id: this.filters.vendor,
                        },
                    }
                );

                const data = response.data.data;

                // Update component data
                this.summary = data.summary;
                this.categoryData = data.categoryData;
                this.trendData = data.trendData;
                this.vendorData = data.vendorData;
                this.spendData = data.spendData;
            } catch (error) {
                console.error("Error loading analysis data:", error);
                // Load mock data if API fails
                this.loadMockData();
            }
        },

        // Mock data for development/demo
        loadMockData() {
            this.summary = {
                totalSpend: 1250480.75,
                totalOrders: 312,
                avgOrderValue: 4007.95,
                spendChange: 8.5,
                orderChange: 3.2,
                avgOrderChange: 5.3,
            };

            this.categoryData = [
                { name: "Raw Materials", value: 480250.5, color: "#3b82f6" },
                { name: "Packaging", value: 210450.25, color: "#10b981" },
                { name: "Equipment", value: 325600.0, color: "#f59e0b" },
                { name: "Office Supplies", value: 45780.5, color: "#8b5cf6" },
                { name: "Maintenance", value: 98720.75, color: "#ef4444" },
                { name: "Services", value: 89678.75, color: "#64748b" },
            ];

            this.trendData = [
                { date: "2023-01", amount: 95420.5 },
                { date: "2023-02", amount: 88750.25 },
                { date: "2023-03", amount: 102650.0 },
                { date: "2023-04", amount: 91870.75 },
                { date: "2023-05", amount: 108950.5 },
                { date: "2023-06", amount: 120450.25 },
                { date: "2023-07", amount: 105320.5 },
                { date: "2023-08", amount: 118750.25 },
                { date: "2023-09", amount: 130250.0 },
                { date: "2023-10", amount: 123870.75 },
                { date: "2023-11", amount: 142950.5 },
                { date: "2023-12", amount: 131250.25 },
            ];

            this.vendorData = [
                { name: "ABC Supplies", value: 320150.5, color: "#3b82f6" },
                {
                    name: "XYZ Manufacturing",
                    value: 245780.25,
                    color: "#10b981",
                },
                {
                    name: "Global Distributors",
                    value: 180950.75,
                    color: "#f59e0b",
                },
                {
                    name: "Tech Solutions Inc",
                    value: 150320.5,
                    color: "#8b5cf6",
                },
                {
                    name: "Reliable Parts Co",
                    value: 120480.25,
                    color: "#ef4444",
                },
                { name: "Others", value: 232798.5, color: "#64748b" },
            ];

            // Generate mock spend data for table
            this.spendData = [];
            const statuses = [
                "draft",
                "approved",
                "sent",
                "partial",
                "completed",
            ];
            const vendors = [
                "ABC Supplies",
                "XYZ Manufacturing",
                "Global Distributors",
                "Tech Solutions Inc",
                "Reliable Parts Co",
            ];
            const categories = [
                "Raw Materials",
                "Packaging",
                "Equipment",
                "Office Supplies",
                "Maintenance",
                "Services",
            ];

            for (let i = 1; i <= 100; i++) {
                const vendor =
                    vendors[Math.floor(Math.random() * vendors.length)];
                const category =
                    categories[Math.floor(Math.random() * categories.length)];
                const status =
                    statuses[Math.floor(Math.random() * statuses.length)];
                const amount = parseFloat(
                    (Math.random() * 15000 + 1000).toFixed(2)
                );

                // Generate date within the last year
                const date = new Date();
                date.setDate(date.getDate() - Math.floor(Math.random() * 365));

                this.spendData.push({
                    id: i,
                    po_id: i,
                    po_number: `PO-2023-${(1000 + i).toString().substring(1)}`,
                    date: date.toISOString().split("T")[0],
                    vendor,
                    vendor_id: vendors.indexOf(vendor) + 1,
                    category,
                    category_id: categories.indexOf(category) + 1,
                    amount,
                    status,
                });
            }
        },

        applyFilters() {
            this.loading = true;
            this.currentPage = 1; // Reset pagination
            this.loadAnalysisData();
        },

        resetFilters() {
            this.filters.dateRange = "month";
            this.filters.category = "";
            this.filters.vendor = "";

            // Reset date range to last 30 days
            const end = new Date();
            const start = new Date();
            start.setDate(start.getDate() - 30);

            this.filters.startDate = this.formatDateForInput(start);
            this.filters.endDate = this.formatDateForInput(end);

            this.applyFilters();
        },

        sortTable(field) {
            if (this.sortField === field) {
                this.sortDirection =
                    this.sortDirection === "asc" ? "desc" : "asc";
            } else {
                this.sortField = field;
                this.sortDirection = "asc";
            }
        },

        getSortIconClass(field) {
            if (this.sortField !== field) {
                return "fas fa-sort";
            }
            return this.sortDirection === "asc"
                ? "fas fa-sort-up"
                : "fas fa-sort-down";
        },

        changePage(page) {
            if (page < 1 || page > this.totalPages) {
                return;
            }
            this.currentPage = page;
        },

        downloadCSV() {
            // Generate CSV data
            const headers = [
                "Date",
                "PO Number",
                "Vendor",
                "Category",
                "Amount",
                "Status",
            ];
            let csvContent = headers.join(",") + "\n";

            this.filteredData.forEach((item) => {
                const row = [
                    this.formatDate(item.date),
                    item.po_number,
                    item.vendor,
                    item.category,
                    item.amount,
                    item.status,
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
            link.setAttribute(
                "download",
                `spend-analysis-${this.formatDateForFilename(new Date())}.csv`
            );
            link.style.visibility = "hidden";
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
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

        formatDateForFilename(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, "0");
            const day = String(date.getDate()).padStart(2, "0");
            return `${year}${month}${day}`;
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
    },
};
</script>

<style scoped>
.spend-analysis-container {
    margin-bottom: 2rem;
}

.header-section {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
}

.filter-controls {
    flex-wrap: wrap;
}

.chart-container {
    width: 100%;
    height: 100%;
}

.sortable {
    cursor: pointer;
}

.sortable i {
    margin-left: 5px;
    font-size: 0.8rem;
}

.page-link {
    cursor: pointer;
}

@media (max-width: 768px) {
    .header-section {
        flex-direction: column;
    }

    .filter-controls {
        margin-top: 1rem;
        width: 100%;
    }
}
</style>
