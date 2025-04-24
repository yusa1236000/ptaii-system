<!-- src/views/purchasing/VendorPerformanceReport.vue -->
<template>
    <div class="vendor-performance-container">
        <div class="header-section mb-4">
            <h1 class="page-title">Vendor Performance Report</h1>
            <div class="filter-controls d-flex align-items-center flex-wrap">
                <div class="form-group mr-3">
                    <label for="vendorSelect" class="mb-1"
                        >Select Vendor:</label
                    >
                    <select
                        id="vendorSelect"
                        v-model="selectedVendor"
                        class="form-control"
                        @change="loadVendorData"
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
                <div class="form-group mr-3">
                    <label for="periodSelect" class="mb-1">Time Period:</label>
                    <select
                        id="periodSelect"
                        v-model="selectedPeriod"
                        class="form-control"
                        @change="loadVendorData"
                    >
                        <option value="month">Last Month</option>
                        <option value="quarter">Last Quarter</option>
                        <option value="year">Last Year</option>
                        <option value="all">All Time</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="aspectSelect" class="mb-1"
                        >Performance Aspect:</label
                    >
                    <select
                        id="aspectSelect"
                        v-model="selectedAspect"
                        class="form-control"
                        @change="updateChartView"
                    >
                        <option value="overall">Overall Performance</option>
                        <option value="quality">Quality</option>
                        <option value="delivery">Delivery</option>
                        <option value="price">Price</option>
                        <option value="service">Service</option>
                    </select>
                </div>
            </div>
        </div>

        <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Loading...</span>
            </div>
            <p class="mt-2">Loading vendor performance data...</p>
        </div>

        <div v-else class="performance-content">
            <!-- Performance Summary Cards -->
            <div
                class="grid grid-cols-5 sm:grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-4"
            >
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title text-gray-600">Overall Score</h5>
                        <div class="text-center">
                            <div
                                class="score-circle"
                                :class="getScoreColorClass(summary.overall)"
                            >
                                <span>{{ summary.overall }}%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title text-gray-600">Quality Score</h5>
                        <div class="text-center">
                            <div
                                class="score-circle"
                                :class="getScoreColorClass(summary.quality)"
                            >
                                <span>{{ summary.quality }}%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title text-gray-600">Delivery Score</h5>
                        <div class="text-center">
                            <div
                                class="score-circle"
                                :class="getScoreColorClass(summary.delivery)"
                            >
                                <span>{{ summary.delivery }}%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title text-gray-600">Price Score</h5>
                        <div class="text-center">
                            <div
                                class="score-circle"
                                :class="getScoreColorClass(summary.price)"
                            >
                                <span>{{ summary.price }}%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title text-gray-600">Service Score</h5>
                        <div class="text-center">
                            <div
                                class="score-circle"
                                :class="getScoreColorClass(summary.service)"
                            >
                                <span>{{ summary.service }}%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance Metrics Row -->
            <div
                class="grid grid-cols-2 sm:grid-cols-1 md:grid-cols-2 gap-4 mb-4"
            >
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Performance Radar</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container" style="height: 300px">
                            <performance-radar-chart :chart-data="radarData" />
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Performance Trend</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container" style="height: 300px">
                            <performance-trend-chart
                                :chart-data="trendData"
                                :aspect="selectedAspect"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vendor Comparison / Details Row -->
            <div class="grid grid-cols-1 gap-4 mb-4">
                <div v-if="!selectedVendor" class="card">
                    <div
                        class="card-header d-flex justify-content-between align-items-center"
                    >
                        <h5 class="card-title mb-0">Vendor Comparison</h5>
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
                                            @click="sortTable('name')"
                                        >
                                            Vendor
                                            <i
                                                :class="
                                                    getSortIconClass('name')
                                                "
                                            ></i>
                                        </th>
                                        <th
                                            class="sortable"
                                            @click="sortTable('quality')"
                                        >
                                            Quality
                                            <i
                                                :class="
                                                    getSortIconClass('quality')
                                                "
                                            ></i>
                                        </th>
                                        <th
                                            class="sortable"
                                            @click="sortTable('delivery')"
                                        >
                                            Delivery
                                            <i
                                                :class="
                                                    getSortIconClass('delivery')
                                                "
                                            ></i>
                                        </th>
                                        <th
                                            class="sortable"
                                            @click="sortTable('price')"
                                        >
                                            Price
                                            <i
                                                :class="
                                                    getSortIconClass('price')
                                                "
                                            ></i>
                                        </th>
                                        <th
                                            class="sortable"
                                            @click="sortTable('service')"
                                        >
                                            Service
                                            <i
                                                :class="
                                                    getSortIconClass('service')
                                                "
                                            ></i>
                                        </th>
                                        <th
                                            class="sortable"
                                            @click="sortTable('overall')"
                                        >
                                            Overall
                                            <i
                                                :class="
                                                    getSortIconClass('overall')
                                                "
                                            ></i>
                                        </th>
                                        <th>Last Evaluated</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="vendor in paginatedVendors"
                                        :key="vendor.vendor_id"
                                    >
                                        <td>{{ vendor.name }}</td>
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
                                                            getProgressBarColor(
                                                                vendor.quality
                                                            )
                                                        "
                                                        :style="`width: ${vendor.quality}%`"
                                                    ></div>
                                                </div>
                                                <span
                                                    >{{ vendor.quality }}%</span
                                                >
                                            </div>
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
                                                            getProgressBarColor(
                                                                vendor.delivery
                                                            )
                                                        "
                                                        :style="`width: ${vendor.delivery}%`"
                                                    ></div>
                                                </div>
                                                <span
                                                    >{{
                                                        vendor.delivery
                                                    }}%</span
                                                >
                                            </div>
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
                                                            getProgressBarColor(
                                                                vendor.price
                                                            )
                                                        "
                                                        :style="`width: ${vendor.price}%`"
                                                    ></div>
                                                </div>
                                                <span>{{ vendor.price }}%</span>
                                            </div>
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
                                                            getProgressBarColor(
                                                                vendor.service
                                                            )
                                                        "
                                                        :style="`width: ${vendor.service}%`"
                                                    ></div>
                                                </div>
                                                <span
                                                    >{{ vendor.service }}%</span
                                                >
                                            </div>
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
                                                            getProgressBarColor(
                                                                vendor.overall
                                                            )
                                                        "
                                                        :style="`width: ${vendor.overall}%`"
                                                    ></div>
                                                </div>
                                                <span
                                                    >{{ vendor.overall }}%</span
                                                >
                                            </div>
                                        </td>
                                        <td>
                                            {{
                                                formatDate(
                                                    vendor.last_evaluated
                                                )
                                            }}
                                        </td>
                                        <td>
                                            <button
                                                class="btn btn-sm btn-primary"
                                                @click="
                                                    viewVendorDetails(
                                                        vendor.vendor_id
                                                    )
                                                "
                                            >
                                                View
                                            </button>
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
                                {{ filteredVendors.length }} vendors
                            </div>
                            <ul class="pagination mb-0">
                                <li
                                    class="page-item"
                                    :class="{ disabled: currentPage === 1 }"
                                >
                                    <a
                                        class="page-link"
                                        href="#"
                                        @click.prevent="
                                            changePage(currentPage - 1)
                                        "
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
                                        @click.prevent="
                                            changePage(currentPage + 1)
                                        "
                                    >
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div v-else class="vendor-detail-cards">
                    <div class="card mb-4">
                        <div
                            class="card-header d-flex justify-content-between align-items-center"
                        >
                            <h5 class="card-title mb-0">
                                {{ selectedVendorName }} - Evaluation History
                            </h5>
                            <button
                                class="btn btn-sm btn-outline-secondary"
                                @click="selectedVendor = ''"
                            >
                                <i class="fas fa-arrow-left mr-1"></i> Back to
                                All Vendors
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-container">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Quality</th>
                                            <th>Delivery</th>
                                            <th>Price</th>
                                            <th>Service</th>
                                            <th>Overall</th>
                                            <th>Comments</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="(
                                                evaluation, index
                                            ) in vendorEvaluations"
                                            :key="index"
                                        >
                                            <td>
                                                {{
                                                    formatDate(
                                                        evaluation.evaluation_date
                                                    )
                                                }}
                                            </td>
                                            <td>
                                                <span
                                                    :class="
                                                        getScoreTextColor(
                                                            evaluation.quality_score
                                                        )
                                                    "
                                                >
                                                    {{
                                                        evaluation.quality_score
                                                    }}%
                                                </span>
                                            </td>
                                            <td>
                                                <span
                                                    :class="
                                                        getScoreTextColor(
                                                            evaluation.delivery_score
                                                        )
                                                    "
                                                >
                                                    {{
                                                        evaluation.delivery_score
                                                    }}%
                                                </span>
                                            </td>
                                            <td>
                                                <span
                                                    :class="
                                                        getScoreTextColor(
                                                            evaluation.price_score
                                                        )
                                                    "
                                                >
                                                    {{
                                                        evaluation.price_score
                                                    }}%
                                                </span>
                                            </td>
                                            <td>
                                                <span
                                                    :class="
                                                        getScoreTextColor(
                                                            evaluation.service_score
                                                        )
                                                    "
                                                >
                                                    {{
                                                        evaluation.service_score
                                                    }}%
                                                </span>
                                            </td>
                                            <td>
                                                <span
                                                    :class="
                                                        getScoreTextColor(
                                                            evaluation.total_score
                                                        )
                                                    "
                                                >
                                                    {{
                                                        evaluation.total_score
                                                    }}%
                                                </span>
                                            </td>
                                            <td>
                                                {{
                                                    evaluation.comments ||
                                                    "No comments"
                                                }}
                                            </td>
                                        </tr>
                                        <tr
                                            v-if="
                                                vendorEvaluations.length === 0
                                            "
                                        >
                                            <td
                                                colspan="7"
                                                class="text-center py-4"
                                            >
                                                No evaluation history available
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from "axios";
import PerformanceRadarChart from "@/components/purchasing/charts/PerformanceRadarChart.vue";
import PerformanceTrendChart from "@/components/purchasing/charts/PerformanceTrendChart.vue";

export default {
    name: "VendorPerformanceReport",
    components: {
        PerformanceRadarChart,
        PerformanceTrendChart,
    },
    data() {
        return {
            loading: true,
            vendors: [],
            selectedVendor: "",
            selectedVendorName: "",
            selectedPeriod: "quarter",
            selectedAspect: "overall",
            summary: {
                overall: 0,
                quality: 0,
                delivery: 0,
                price: 0,
                service: 0,
            },
            radarData: [],
            trendData: [],
            vendorPerformanceData: [],
            vendorEvaluations: [],

            // Sorting and pagination
            sortField: "overall",
            sortDirection: "desc",
            currentPage: 1,
            pageSize: 10,
        };
    },
    computed: {
        filteredVendors() {
            const data = [...this.vendorPerformanceData];

            // Apply sorting
            data.sort((a, b) => {
                const fieldA = a[this.sortField];
                const fieldB = b[this.sortField];

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
        paginatedVendors() {
            const start = (this.currentPage - 1) * this.pageSize;
            const end = start + this.pageSize;
            return this.filteredVendors.slice(start, end);
        },
        totalPages() {
            return Math.ceil(this.filteredVendors.length / this.pageSize);
        },
        paginationStart() {
            return (this.currentPage - 1) * this.pageSize;
        },
        paginationEnd() {
            const end = this.paginationStart + this.pageSize;
            return end > this.filteredVendors.length
                ? this.filteredVendors.length
                : end;
        },
    },
    created() {
        this.loadInitialData();
    },
    methods: {
        async loadInitialData() {
            this.loading = true;

            try {
                // Load vendors list for the dropdown
                const vendorsResponse = await axios.get("/api/vendors");
                this.vendors = vendorsResponse.data.data || [];

                // Load performance data
                await this.loadVendorData();
            } catch (error) {
                console.error("Error loading vendor data:", error);
                // Load mock data for demonstration
                this.loadMockData();
            } finally {
                this.loading = false;
            }
        },

        async loadVendorData() {
            this.loading = true;

            try {
                let url = "/api/vendor-performance";
                const params = {
                    period: this.selectedPeriod,
                };

                if (this.selectedVendor) {
                    params.vendor_id = this.selectedVendor;
                    // Set selected vendor name for display
                    const vendor = this.vendors.find(
                        (v) =>
                            v.vendor_id.toString() ===
                            this.selectedVendor.toString()
                    );
                    this.selectedVendorName = vendor ? vendor.name : "";

                    // Load evaluations for the selected vendor
                    const evaluationsResponse = await axios.get(
                        `/api/vendors/${this.selectedVendor}/evaluations`
                    );
                    this.vendorEvaluations =
                        evaluationsResponse.data.data || [];
                }

                // Get performance data
                const response = await axios.get(url, { params });
                const data = response.data.data;

                // Update component data
                if (this.selectedVendor) {
                    // Single vendor mode
                    this.summary = {
                        overall: data.averages.total,
                        quality: data.averages.quality,
                        delivery: data.averages.delivery,
                        price: data.averages.price,
                        service: data.averages.service,
                    };

                    // Prepare radar data
                    this.radarData = [
                        { name: "Quality", value: data.averages.quality },
                        { name: "Delivery", value: data.averages.delivery },
                        { name: "Price", value: data.averages.price },
                        { name: "Service", value: data.averages.service },
                    ];

                    // Prepare trend data
                    this.trendData = data.evaluations.map((eval) => ({
                        date: eval.evaluation_date,
                        quality: eval.quality_score,
                        delivery: eval.delivery_score,
                        price: eval.price_score,
                        service: eval.service_score,
                        overall: eval.total_score,
                    }));
                } else {
                    // All vendors mode
                    this.vendorPerformanceData = data.vendors || [];

                    // Calculate averages for summary
                    const vendors = this.vendorPerformanceData;
                    if (vendors.length > 0) {
                        this.summary = {
                            overall: this.calculateAverage(vendors, "overall"),
                            quality: this.calculateAverage(vendors, "quality"),
                            delivery: this.calculateAverage(
                                vendors,
                                "delivery"
                            ),
                            price: this.calculateAverage(vendors, "price"),
                            service: this.calculateAverage(vendors, "service"),
                        };

                        // Prepare radar data - average of all vendors
                        this.radarData = [
                            { name: "Quality", value: this.summary.quality },
                            { name: "Delivery", value: this.summary.delivery },
                            { name: "Price", value: this.summary.price },
                            { name: "Service", value: this.summary.service },
                        ];

                        // Prepare trend data - monthly averages
                        this.trendData = data.trends || [];
                    }
                }
            } catch (error) {
                console.error("Error loading vendor performance data:", error);
                // Load mock data if API fails
                this.loadMockData();
            } finally {
                this.loading = false;
            }
        },

        // Load mock data for development/demo
        loadMockData() {
            // Mock vendors if needed
            if (this.vendors.length === 0) {
                this.vendors = [
                    { vendor_id: 1, name: "ABC Supplies" },
                    { vendor_id: 2, name: "XYZ Manufacturing" },
                    { vendor_id: 3, name: "Global Distributors" },
                    { vendor_id: 4, name: "Tech Solutions Inc" },
                    { vendor_id: 5, name: "Reliable Parts Co" },
                ];
            }

            if (this.selectedVendor) {
                // Single vendor mode
                const vendor = this.vendors.find(
                    (v) =>
                        v.vendor_id.toString() ===
                        this.selectedVendor.toString()
                );
                this.selectedVendorName = vendor
                    ? vendor.name
                    : "Selected Vendor";

                this.summary = {
                    overall: 85,
                    quality: 88,
                    delivery: 79,
                    price: 92,
                    service: 81,
                };

                // Mock radar data
                this.radarData = [
                    { name: "Quality", value: 88 },
                    { name: "Delivery", value: 79 },
                    { name: "Price", value: 92 },
                    { name: "Service", value: 81 },
                ];

                // Mock trend data - last 12 evaluations
                this.trendData = [];
                for (let i = 11; i >= 0; i--) {
                    const date = new Date();
                    date.setMonth(date.getMonth() - i);

                    // Create some variation in the scores
                    const quality = 85 + Math.floor(Math.random() * 10) - 5;
                    const delivery = 80 + Math.floor(Math.random() * 10) - 5;
                    const price = 90 + Math.floor(Math.random() * 10) - 5;
                    const service = 75 + Math.floor(Math.random() * 10) - 5;
                    const overall = Math.round(
                        (quality + delivery + price + service) / 4
                    );

                    this.trendData.push({
                        date: this.formatDateForInput(date),
                        quality,
                        delivery,
                        price,
                        service,
                        overall,
                    });
                }

                // Mock evaluations for the selected vendor
                this.vendorEvaluations = this.trendData.map((item) => ({
                    evaluation_date: item.date,
                    quality_score: item.quality,
                    delivery_score: item.delivery,
                    price_score: item.price,
                    service_score: item.service,
                    total_score: item.overall,
                    comments: this.getRandomComment(item.overall),
                }));
            } else {
                // All vendors mode
                this.vendorPerformanceData = [];

                // Generate mock data for each vendor
                this.vendors.forEach((vendor) => {
                    const quality = 60 + Math.floor(Math.random() * 35);
                    const delivery = 60 + Math.floor(Math.random() * 35);
                    const price = 60 + Math.floor(Math.random() * 35);
                    const service = 60 + Math.floor(Math.random() * 35);
                    const overall = Math.round(
                        (quality + delivery + price + service) / 4
                    );

                    const date = new Date();
                    date.setDate(
                        date.getDate() - Math.floor(Math.random() * 30)
                    );

                    this.vendorPerformanceData.push({
                        vendor_id: vendor.vendor_id,
                        name: vendor.name,
                        quality,
                        delivery,
                        price,
                        service,
                        overall,
                        last_evaluated: this.formatDateForInput(date),
                    });
                });

                // Add some more mock vendors
                for (let i = 1; i <= 15; i++) {
                    const quality = 60 + Math.floor(Math.random() * 35);
                    const delivery = 60 + Math.floor(Math.random() * 35);
                    const price = 60 + Math.floor(Math.random() * 35);
                    const service = 60 + Math.floor(Math.random() * 35);
                    const overall = Math.round(
                        (quality + delivery + price + service) / 4
                    );

                    const date = new Date();
                    date.setDate(
                        date.getDate() - Math.floor(Math.random() * 30)
                    );

                    this.vendorPerformanceData.push({
                        vendor_id: 5 + i,
                        name: `Vendor ${i}`,
                        quality,
                        delivery,
                        price,
                        service,
                        overall,
                        last_evaluated: this.formatDateForInput(date),
                    });
                }

                // Calculate average for summary
                this.summary = {
                    overall: this.calculateAverage(
                        this.vendorPerformanceData,
                        "overall"
                    ),
                    quality: this.calculateAverage(
                        this.vendorPerformanceData,
                        "quality"
                    ),
                    delivery: this.calculateAverage(
                        this.vendorPerformanceData,
                        "delivery"
                    ),
                    price: this.calculateAverage(
                        this.vendorPerformanceData,
                        "price"
                    ),
                    service: this.calculateAverage(
                        this.vendorPerformanceData,
                        "service"
                    ),
                };

                // Mock radar data - average of all vendors
                this.radarData = [
                    { name: "Quality", value: this.summary.quality },
                    { name: "Delivery", value: this.summary.delivery },
                    { name: "Price", value: this.summary.price },
                    { name: "Service", value: this.summary.service },
                ];

                // Mock trend data - monthly averages for the last 12 months
                this.trendData = [];
                for (let i = 11; i >= 0; i--) {
                    const date = new Date();
                    date.setMonth(date.getMonth() - i);

                    // Create some variation in the scores
                    const quality = 80 + Math.floor(Math.random() * 10) - 5;
                    const delivery = 75 + Math.floor(Math.random() * 10) - 5;
                    const price = 85 + Math.floor(Math.random() * 10) - 5;
                    const service = 78 + Math.floor(Math.random() * 10) - 5;
                    const overall = Math.round(
                        (quality + delivery + price + service) / 4
                    );

                    this.trendData.push({
                        date: this.formatDateForInput(date),
                        quality,
                        delivery,
                        price,
                        service,
                        overall,
                    });
                }
            }
        },

        getRandomComment(score) {
            const comments = [
                "Consistently high quality products and excellent service.",
                "Some delivery delays but good quality overall.",
                "Price negotiation needed for future orders.",
                "Responsive to issues and quick to resolve problems.",
                "Communication could be improved.",
                "Great value for the price point.",
            ];

            return comments[Math.floor(Math.random() * comments.length)];
        },

        calculateAverage(array, property) {
            if (!array || array.length === 0) return 0;
            const sum = array.reduce(
                (total, item) => total + item[property],
                0
            );
            return Math.round(sum / array.length);
        },

        updateChartView() {
            // This method is called when the performance aspect is changed
            // The charts will automatically update based on the props
        },

        viewVendorDetails(vendorId) {
            this.selectedVendor = vendorId;
            this.loadVendorData();
        },

        sortTable(field) {
            if (this.sortField === field) {
                this.sortDirection =
                    this.sortDirection === "asc" ? "desc" : "asc";
            } else {
                this.sortField = field;
                this.sortDirection = "desc";
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
                "Vendor",
                "Quality",
                "Delivery",
                "Price",
                "Service",
                "Overall",
                "Last Evaluated",
            ];
            let csvContent = headers.join(",") + "\n";

            this.filteredVendors.forEach((vendor) => {
                const row = [
                    vendor.name,
                    vendor.quality,
                    vendor.delivery,
                    vendor.price,
                    vendor.service,
                    vendor.overall,
                    this.formatDate(vendor.last_evaluated),
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
                `vendor-performance-${this.formatDateForFilename(
                    new Date()
                )}.csv`
            );
            link.style.visibility = "hidden";
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        },

        formatDate(dateString) {
            if (!dateString) return "N/A";
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

        getScoreColorClass(score) {
            if (score >= 90) return "score-excellent";
            if (score >= 80) return "score-good";
            if (score >= 70) return "score-adequate";
            if (score >= 60) return "score-fair";
            return "score-poor";
        },

        getScoreTextColor(score) {
            if (score >= 90) return "text-success";
            if (score >= 80) return "text-primary";
            if (score >= 70) return "text-info";
            if (score >= 60) return "text-warning";
            return "text-danger";
        },

        getProgressBarColor(score) {
            if (score >= 90) return "bg-success";
            if (score >= 80) return "bg-primary";
            if (score >= 70) return "bg-info";
            if (score >= 60) return "bg-warning";
            return "bg-danger";
        },
    },
};
</script>

<style scoped>
.vendor-performance-container {
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

.score-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    font-size: 1.25rem;
    font-weight: 600;
    color: white;
}

.score-excellent {
    background-color: var(--success-color);
}

.score-good {
    background-color: var(--primary-color);
}

.score-adequate {
    background-color: var(--primary-light);
}

.score-fair {
    background-color: var(--warning-color);
}

.score-poor {
    background-color: var(--danger-color);
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
