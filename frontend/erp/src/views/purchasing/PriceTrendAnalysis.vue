<!-- src/views/purchasing/PriceTrendAnalysis.vue -->
<template>
    <div class="price-trend-container">
        <div class="header-section mb-4">
            <h1 class="page-title">Price Trend Analysis</h1>
            <div class="filter-controls d-flex align-items-center flex-wrap">
                <div class="form-group mr-3">
                    <label for="timeRangeSelect" class="mb-1"
                        >Time Period:</label
                    >
                    <select
                        id="timeRangeSelect"
                        v-model="filters.timeRange"
                        class="form-control"
                        @change="applyFilters"
                    >
                        <option value="6months">Last 6 Months</option>
                        <option value="1year">Last Year</option>
                        <option value="2years">Last 2 Years</option>
                        <option value="custom">Custom Range</option>
                    </select>
                </div>
                <div
                    v-if="filters.timeRange === 'custom'"
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
                    <label for="categorySelect" class="mb-1">Category:</label>
                    <select
                        id="categorySelect"
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
                <div class="form-group">
                    <label for="itemSelect" class="mb-1">Item:</label>
                    <select
                        id="itemSelect"
                        v-model="filters.item"
                        class="form-control"
                    >
                        <option value="">All Items</option>
                        <option
                            v-for="item in filteredItems"
                            :key="item.item_id"
                            :value="item.item_id"
                        >
                            {{ item.name }}
                        </option>
                    </select>
                </div>
                <button class="btn btn-primary mt-3 ml-3" @click="applyFilters">
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
            <p class="mt-2">Loading price trend data...</p>
        </div>

        <div v-else class="analysis-content">
            <!-- Summary Cards -->
            <div
                class="grid grid-cols-3 sm:grid-cols-1 md:grid-cols-3 gap-4 mb-4"
            >
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title text-gray-600">
                            Average Price Change
                        </h5>
                        <div class="d-flex align-items-center mt-3">
                            <h2
                                :class="
                                    summary.avgPriceChange >= 0
                                        ? 'text-danger'
                                        : 'text-success'
                                "
                            >
                                {{ summary.avgPriceChange >= 0 ? "+" : ""
                                }}{{ summary.avgPriceChange }}%
                            </h2>
                            <i
                                :class="[
                                    summary.avgPriceChange >= 0
                                        ? 'fas fa-arrow-up text-danger'
                                        : 'fas fa-arrow-down text-success',
                                    'ml-2 fa-lg',
                                ]"
                            ></i>
                        </div>
                        <small class="text-gray-500"
                            >Over the selected period</small
                        >
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title text-gray-600">
                            Items with Price Increase
                        </h5>
                        <div class="d-flex align-items-center mt-3">
                            <h2 class="text-danger">
                                {{ summary.priceIncreaseCount }}
                            </h2>
                            <span class="text-danger ml-2"
                                >({{ summary.priceIncreasePercentage }}%)</span
                            >
                        </div>
                        <small class="text-gray-500"
                            >Out of {{ summary.totalItems }} items</small
                        >
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title text-gray-600">
                            Items with Price Decrease
                        </h5>
                        <div class="d-flex align-items-center mt-3">
                            <h2 class="text-success">
                                {{ summary.priceDecreaseCount }}
                            </h2>
                            <span class="text-success ml-2"
                                >({{ summary.priceDecreasePercentage }}%)</span
                            >
                        </div>
                        <small class="text-gray-500"
                            >Out of {{ summary.totalItems }} items</small
                        >
                    </div>
                </div>
            </div>

            <!-- Main Price Trend Chart -->
            <div class="card mb-4">
                <div
                    class="card-header d-flex justify-content-between align-items-center"
                >
                    <h5 class="card-title mb-0">
                        {{
                            filters.item
                                ? getItemName(filters.item) + " - Price Trend"
                                : "Price Trend Over Time"
                        }}
                    </h5>
                    <div class="chart-controls d-flex">
                        <div class="form-group mr-2 mb-0">
                            <select
                                v-model="chartView"
                                class="form-control form-control-sm"
                            >
                                <option value="line">Line Chart</option>
                                <option value="area">Area Chart</option>
                                <option value="column">Column Chart</option>
                            </select>
                        </div>
                        <div class="form-group mb-0">
                            <div class="btn-group">
                                <button
                                    v-for="interval in [
                                        'monthly',
                                        'quarterly',
                                        'yearly',
                                    ]"
                                    :key="interval"
                                    class="btn btn-sm"
                                    :class="
                                        timeInterval === interval
                                            ? 'btn-primary'
                                            : 'btn-secondary'
                                    "
                                    @click="timeInterval = interval"
                                >
                                    {{ getIntervalLabel(interval) }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="height: 400px">
                        <price-trend-chart
                            :chart-data="priceData"
                            :chart-type="chartView"
                            :multiple-items="!filters.item"
                        />
                    </div>
                </div>
            </div>

            <!-- Price Comparison by Vendor (only shown when a specific item is selected) -->
            <div v-if="filters.item" class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        {{ getItemName(filters.item) }} - Price by Vendor
                    </h5>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="height: 350px">
                        <vendor-price-comparison-chart
                            :chart-data="vendorPriceData"
                        />
                    </div>
                </div>
            </div>

            <!-- Price Analysis by Category -->
            <div
                v-if="!filters.item"
                class="grid grid-cols-2 sm:grid-cols-1 md:grid-cols-2 gap-4 mb-4"
            >
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            Price Change by Category
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container" style="height: 350px">
                            <category-price-chart
                                :chart-data="categoryPriceData"
                            />
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            Items with Highest Price Change
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Category</th>
                                        <th>Previous Price</th>
                                        <th>Current Price</th>
                                        <th>Change</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="item in topPriceChanges"
                                        :key="item.item_id"
                                    >
                                        <td>
                                            <a
                                                href="#"
                                                @click.prevent="
                                                    selectItem(item.item_id)
                                                "
                                                class="text-primary"
                                            >
                                                {{ item.name }}
                                            </a>
                                        </td>
                                        <td>{{ item.category }}</td>
                                        <td>
                                            ${{
                                                formatNumber(item.previousPrice)
                                            }}
                                        </td>
                                        <td>
                                            ${{
                                                formatNumber(item.currentPrice)
                                            }}
                                        </td>
                                        <td>
                                            <span
                                                :class="
                                                    item.percentageChange >= 0
                                                        ? 'text-danger'
                                                        : 'text-success'
                                                "
                                            >
                                                {{
                                                    item.percentageChange >= 0
                                                        ? "+"
                                                        : ""
                                                }}{{ item.percentageChange }}%
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Price Data Table -->
            <div class="card">
                <div
                    class="card-header d-flex justify-content-between align-items-center"
                >
                    <h5 class="card-title mb-0">Price History</h5>
                    <button
                        class="btn btn-sm btn-outline-primary"
                        @click="downloadCSV"
                    >
                        <i class="fas fa-download mr-1"></i> Export CSV
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
                                        @click="sortTable('item')"
                                    >
                                        Item
                                        <i
                                            :class="getSortIconClass('item')"
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
                                        @click="sortTable('unit_price')"
                                    >
                                        Unit Price
                                        <i
                                            :class="
                                                getSortIconClass('unit_price')
                                            "
                                        ></i>
                                    </th>
                                    <th
                                        class="sortable"
                                        @click="sortTable('change')"
                                    >
                                        Change
                                        <i
                                            :class="getSortIconClass('change')"
                                        ></i>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(record, index) in paginatedRecords"
                                    :key="index"
                                >
                                    <td>{{ formatDate(record.date) }}</td>
                                    <td>
                                        <a
                                            href="#"
                                            @click.prevent="
                                                selectItem(record.item_id)
                                            "
                                            class="text-primary"
                                        >
                                            {{ record.item }}
                                        </a>
                                    </td>
                                    <td>{{ record.vendor }}</td>
                                    <td>
                                        <router-link
                                            :to="`/purchasing/orders/${record.po_id}`"
                                            class="text-primary"
                                        >
                                            {{ record.po_number }}
                                        </router-link>
                                    </td>
                                    <td>
                                        ${{ formatNumber(record.unit_price) }}
                                    </td>
                                    <td>
                                        <span
                                            v-if="record.change !== null"
                                            :class="
                                                record.change >= 0
                                                    ? 'text-danger'
                                                    : 'text-success'
                                            "
                                        >
                                            {{ record.change >= 0 ? "+" : ""
                                            }}{{ record.change }}%
                                        </span>
                                        <span v-else>-</span>
                                    </td>
                                </tr>
                                <tr v-if="paginatedRecords.length === 0">
                                    <td colspan="6" class="text-center py-4">
                                        No price history found
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
                            {{ filteredRecords.length }} records
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
import PriceTrendChart from "./PriceTrendChart.vue";
import VendorPriceComparisonChart from "./VendorPriceComparisonChart.vue";
import CategoryPriceChart from "./CategoryPriceChart.vue";

export default {
    name: "PriceTrendAnalysis",
    components: {
        PriceTrendChart,
        VendorPriceComparisonChart,
        CategoryPriceChart,
    },
    data() {
        return {
            loading: true,
            filters: {
                timeRange: "1year",
                startDate: null,
                endDate: null,
                category: "",
                item: "",
            },
            categories: [],
            items: [],
            chartView: "line",
            timeInterval: "monthly",
            summary: {
                avgPriceChange: 0,
                priceIncreaseCount: 0,
                priceDecreaseCount: 0,
                priceIncreasePercentage: 0,
                priceDecreasePercentage: 0,
                totalItems: 0,
            },
            priceData: [],
            vendorPriceData: [],
            categoryPriceData: [],
            topPriceChanges: [],
            priceRecords: [],

            // Table sorting and pagination
            sortField: "date",
            sortDirection: "desc",
            currentPage: 1,
            pageSize: 10,
        };
    },
    computed: {
        filteredItems() {
            if (!this.filters.category) {
                return this.items;
            }
            return this.items.filter(
                (item) =>
                    item.category_id.toString() ===
                    this.filters.category.toString()
            );
        },
        filteredRecords() {
            let data = [...this.priceRecords];

            // Apply sorting
            data.sort((a, b) => {
                let fieldA = a[this.sortField];
                let fieldB = b[this.sortField];

                // Handle numeric fields
                if (
                    this.sortField === "unit_price" ||
                    this.sortField === "change"
                ) {
                    fieldA = parseFloat(fieldA || 0);
                    fieldB = parseFloat(fieldB || 0);
                }

                // Handle date fields
                if (this.sortField === "date") {
                    fieldA = new Date(fieldA);
                    fieldB = new Date(fieldB);
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
        paginatedRecords() {
            const start = (this.currentPage - 1) * this.pageSize;
            const end = start + this.pageSize;
            return this.filteredRecords.slice(start, end);
        },
        totalPages() {
            return Math.ceil(this.filteredRecords.length / this.pageSize);
        },
        paginationStart() {
            return (this.currentPage - 1) * this.pageSize;
        },
        paginationEnd() {
            const end = this.paginationStart + this.pageSize;
            return end > this.filteredRecords.length
                ? this.filteredRecords.length
                : end;
        },
    },
    created() {
        // Set default date range (last year)
        const end = new Date();
        const start = new Date();
        start.setFullYear(start.getFullYear() - 1);

        this.filters.startDate = this.formatDateForInput(start);
        this.filters.endDate = this.formatDateForInput(end);

        this.loadInitialData();
    },
    methods: {
        async loadInitialData() {
            this.loading = true;

            try {
                // Load categories and items for the filters
                const [categoriesResponse, itemsResponse] = await Promise.all([
                    axios.get("/api/categories"),
                    axios.get("/api/items"),
                ]);

                this.categories = categoriesResponse.data.data || [];
                this.items = itemsResponse.data.data || [];

                // Load price trend data
                await this.loadPriceTrendData();
            } catch (error) {
                console.error("Error loading initial data:", error);
                // Load mock data for demonstration
                this.loadMockData();
            } finally {
                this.loading = false;
            }
        },

        async loadPriceTrendData() {
            try {
                // Calculate date range based on filter
                let startDate = this.filters.startDate;
                let endDate = this.filters.endDate;

                if (this.filters.timeRange !== "custom") {
                    const end = new Date();
                    const start = new Date();

                    if (this.filters.timeRange === "6months") {
                        start.setMonth(start.getMonth() - 6);
                    } else if (this.filters.timeRange === "1year") {
                        start.setFullYear(start.getFullYear() - 1);
                    } else if (this.filters.timeRange === "2years") {
                        start.setFullYear(start.getFullYear() - 2);
                    }

                    startDate = this.formatDateForInput(start);
                    endDate = this.formatDateForInput(end);

                    // Update the date inputs for UI consistency
                    this.filters.startDate = startDate;
                    this.filters.endDate = endDate;
                }

                // API call to get price trend data
                const response = await axios.get(
                    "/api/purchasing/price-trend",
                    {
                        params: {
                            start_date: startDate,
                            end_date: endDate,
                            category_id: this.filters.category,
                            item_id: this.filters.item,
                            interval: this.timeInterval,
                        },
                    }
                );

                const data = response.data.data;

                // Update component data
                this.summary = data.summary;
                this.priceData = data.priceData;
                this.vendorPriceData = data.vendorPriceData;
                this.categoryPriceData = data.categoryPriceData;
                this.topPriceChanges = data.topPriceChanges;
                this.priceRecords = data.priceRecords;
            } catch (error) {
                console.error("Error loading price trend data:", error);
                // Load mock data if API fails
                this.loadMockData();
            }
        },

        // Mock data for development/demo
        loadMockData() {
            // Mock categories if needed
            if (this.categories.length === 0) {
                this.categories = [
                    { id: 1, name: "Raw Materials" },
                    { id: 2, name: "Packaging" },
                    { id: 3, name: "Equipment" },
                    { id: 4, name: "Office Supplies" },
                ];
            }

            // Mock items if needed
            if (this.items.length === 0) {
                this.items = [
                    { item_id: 1, name: "Steel Sheets", category_id: 1 },
                    { item_id: 2, name: "Aluminum Rods", category_id: 1 },
                    { item_id: 3, name: "Plastic Pellets", category_id: 1 },
                    { item_id: 4, name: "Cardboard Boxes", category_id: 2 },
                    { item_id: 5, name: "Packaging Tape", category_id: 2 },
                    { item_id: 6, name: "Drill Press", category_id: 3 },
                    { item_id: 7, name: "Laser Cutter", category_id: 3 },
                    { item_id: 8, name: "Copy Paper", category_id: 4 },
                    { item_id: 9, name: "Toner Cartridges", category_id: 4 },
                ];
            }

            // Mock summary data
            this.summary = {
                avgPriceChange: 5.8,
                priceIncreaseCount: 28,
                priceDecreaseCount: 12,
                priceIncreasePercentage: 70,
                priceDecreasePercentage: 30,
                totalItems: 40,
            };

            // Mock price trend data
            if (this.filters.item) {
                // Single item price trend
                const selectedItem = this.items.find(
                    (item) =>
                        item.item_id.toString() === this.filters.item.toString()
                );
                const itemName = selectedItem
                    ? selectedItem.name
                    : "Selected Item";

                this.priceData = this.generateMockPriceData(itemName);

                // Mock vendor price comparison data
                this.vendorPriceData = [
                    { vendor: "ABC Supplies", price: 125.5, change: 7.2 },
                    { vendor: "XYZ Manufacturing", price: 118.75, change: 5.5 },
                    {
                        vendor: "Global Distributors",
                        price: 132.25,
                        change: 9.8,
                    },
                    { vendor: "Tech Solutions Inc", price: 120.9, change: 6.2 },
                    { vendor: "Reliable Parts Co", price: 128.4, change: 8.1 },
                ];
            } else {
                // Multiple items price trend
                this.priceData = [
                    {
                        name: "Steel Sheets",
                        data: this.generateMockPriceDataPoints(),
                    },
                    {
                        name: "Aluminum Rods",
                        data: this.generateMockPriceDataPoints(),
                    },
                    {
                        name: "Plastic Pellets",
                        data: this.generateMockPriceDataPoints(),
                    },
                    {
                        name: "Cardboard Boxes",
                        data: this.generateMockPriceDataPoints(),
                    },
                    {
                        name: "Drill Press",
                        data: this.generateMockPriceDataPoints(),
                    },
                ];

                // Mock category price data
                this.categoryPriceData = [
                    {
                        category: "Raw Materials",
                        change: 8.5,
                        avgPrice: 115.25,
                    },
                    { category: "Packaging", change: 4.2, avgPrice: 76.5 },
                    { category: "Equipment", change: 3.1, avgPrice: 1250.75 },
                    {
                        category: "Office Supplies",
                        change: 6.7,
                        avgPrice: 45.8,
                    },
                ];

                // Mock top price changes
                this.topPriceChanges = [
                    {
                        item_id: 3,
                        name: "Plastic Pellets",
                        category: "Raw Materials",
                        previousPrice: 95.5,
                        currentPrice: 120.75,
                        percentageChange: 26.4,
                    },
                    {
                        item_id: 9,
                        name: "Toner Cartridges",
                        category: "Office Supplies",
                        previousPrice: 65.25,
                        currentPrice: 78.8,
                        percentageChange: 20.8,
                    },
                    {
                        item_id: 1,
                        name: "Steel Sheets",
                        category: "Raw Materials",
                        previousPrice: 175.5,
                        currentPrice: 202.35,
                        percentageChange: 15.3,
                    },
                    {
                        item_id: 8,
                        name: "Copy Paper",
                        category: "Office Supplies",
                        previousPrice: 32.75,
                        currentPrice: 36.5,
                        percentageChange: 11.5,
                    },
                    {
                        item_id: 5,
                        name: "Packaging Tape",
                        category: "Packaging",
                        previousPrice: 12.25,
                        currentPrice: 13.6,
                        percentageChange: 11.0,
                    },
                    {
                        item_id: 6,
                        name: "Drill Press",
                        category: "Equipment",
                        previousPrice: 1450.0,
                        currentPrice: 1380.25,
                        percentageChange: -4.8,
                    },
                    {
                        item_id: 7,
                        name: "Laser Cutter",
                        category: "Equipment",
                        previousPrice: 4200.0,
                        currentPrice: 3950.75,
                        percentageChange: -5.9,
                    },
                ];
            }

            // Mock price records
            this.priceRecords = [];
            const vendors = [
                "ABC Supplies",
                "XYZ Manufacturing",
                "Global Distributors",
                "Tech Solutions Inc",
                "Reliable Parts Co",
            ];

            // Generate records for each item
            this.items.forEach((item) => {
                let prevPrice = null;

                // Generate multiple price points over time
                for (let i = 0; i < 5; i++) {
                    const date = new Date();
                    date.setMonth(date.getMonth() - i * 2); // Every 2 months

                    const vendor =
                        vendors[Math.floor(Math.random() * vendors.length)];
                    const poNumber = `PO-2023-${
                        1000 + i * 10 + this.items.indexOf(item)
                    }`;
                    const unitPrice =
                        50 + Math.random() * 50 + this.items.indexOf(item) * 10;

                    let change = null;
                    if (prevPrice !== null) {
                        change = (
                            ((unitPrice - prevPrice) / prevPrice) *
                            100
                        ).toFixed(1);
                    }

                    prevPrice = unitPrice;

                    this.priceRecords.push({
                        date: this.formatDateForInput(date),
                        item_id: item.item_id,
                        item: item.name,
                        vendor,
                        po_id: 1000 + i * 10 + this.items.indexOf(item),
                        po_number: poNumber,
                        unit_price: unitPrice.toFixed(2),
                        change,
                    });
                }
            });
        },

        generateMockPriceData(itemName) {
            if (this.timeInterval === "monthly") {
                const months = [
                    "Jan",
                    "Feb",
                    "Mar",
                    "Apr",
                    "May",
                    "Jun",
                    "Jul",
                    "Aug",
                    "Sep",
                    "Oct",
                    "Nov",
                    "Dec",
                ];
                return [
                    {
                        name: itemName,
                        data: months.map((month, index) => {
                            const basePrice = 100;
                            // Add some randomness and a slight upward trend
                            const price =
                                basePrice +
                                index * 0.5 +
                                (Math.random() * 10 - 5);
                            return { x: month, y: price.toFixed(2) };
                        }),
                    },
                ];
            } else if (this.timeInterval === "quarterly") {
                const quarters = [
                    "Q1 2023",
                    "Q2 2023",
                    "Q3 2023",
                    "Q4 2023",
                    "Q1 2024",
                ];
                return [
                    {
                        name: itemName,
                        data: quarters.map((quarter, index) => {
                            const basePrice = 100;
                            // Add some randomness and a slight upward trend
                            const price =
                                basePrice + index * 2 + (Math.random() * 8 - 4);
                            return { x: quarter, y: price.toFixed(2) };
                        }),
                    },
                ];
            } else {
                // Yearly
                const years = ["2020", "2021", "2022", "2023", "2024"];
                return [
                    {
                        name: itemName,
                        data: years.map((year, index) => {
                            const basePrice = 100;
                            // Add some randomness and a slight upward trend
                            const price =
                                basePrice + index * 5 + (Math.random() * 6 - 3);
                            return { x: year, y: price.toFixed(2) };
                        }),
                    },
                ];
            }
        },

        generateMockPriceDataPoints() {
            if (this.timeInterval === "monthly") {
                const months = [
                    "Jan",
                    "Feb",
                    "Mar",
                    "Apr",
                    "May",
                    "Jun",
                    "Jul",
                    "Aug",
                    "Sep",
                    "Oct",
                    "Nov",
                    "Dec",
                ];
                return months.map((month, index) => {
                    const basePrice = 50 + Math.random() * 100;
                    // Add some randomness and a slight trend
                    const price =
                        basePrice +
                        index * (Math.random() > 0.5 ? 0.5 : -0.3) +
                        (Math.random() * 10 - 5);
                    return { x: month, y: price.toFixed(2) };
                });
            } else if (this.timeInterval === "quarterly") {
                const quarters = [
                    "Q1 2023",
                    "Q2 2023",
                    "Q3 2023",
                    "Q4 2023",
                    "Q1 2024",
                ];
                return quarters.map((quarter, index) => {
                    const basePrice = 50 + Math.random() * 100;
                    // Add some randomness and a slight trend
                    const price =
                        basePrice +
                        index * (Math.random() > 0.5<thinking>
//The attempt to edit the file using a diff editor failed due to an editor error. I will instead read the full content of the file, modify the import statements in the content, and then overwrite the entire file with the corrected content using create_file tool. This will avoid the diff editor issue.
</thinking>

<read_file>
<path>frontend/erp/src/views/purchasing/PriceTrendAnalysis.vue</path>
</read_file>
