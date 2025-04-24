<template>
    <div class="po-status-container">
        <!-- template content unchanged -->
    </div>
</template>

<script>
import axios from "axios";
import POStatusDistributionChart from "./POStatusDistributionChart.vue";
import POStatusTrendChart from "./POStatusTrendChart.vue";
import DeliveryPerformanceChart from "./DeliveryPerformanceChart.vue";

export default {
    name: "POStatusSummary",
    components: {
        //POStatusDistributionChart,
        //POStatusTrendChart,
        //DeliveryPerformanceChart,
    },
    data() {
        return {
            loading: true,
            filters: {
                dateRange: "month",
                startDate: null,
                endDate: null,
                vendor: "",
            },
            vendors: [],
            summary: {
                draftCount: 0,
                approvedCount: 0,
                sentCount: 0,
                partialCount: 0,
                completedCount: 0,
                canceledCount: 0,
                draftChange: 0,
                approvedChange: 0,
                sentChange: 0,
                partialChange: 0,
                completedChange: 0,
                canceledChange: 0,
            },
            statusData: [],
            trendData: [],
            purchaseOrders: [],
            deliveryPerformance: {
                onTimePercentage: 0,
                totalDeliveries: 0,
                onTimeCount: 0,
                lateCount: 0,
                avgDelay: 0,
                monthlyData: [],
            },
            chartType: "pie",

            // Table sorting and pagination
            sortField: "po_date",
            sortDirection: "desc",
            currentPage: 1,
            pageSize: 10,

            // Status update modal
            selectedPO: null,
            newStatus: "",
            availableStatuses: [],
        };
    },
    computed: {
        filteredPOs() {
            let data = [...this.purchaseOrders];

            // Apply vendor filter
            if (this.filters.vendor) {
                data = data.filter(
                    (po) =>
                        po.vendor_id.toString() ===
                        this.filters.vendor.toString()
                );
            }

            // Apply sorting
            data.sort((a, b) => {
                let fieldA = a[this.sortField];
                let fieldB = b[this.sortField];

                // Handle nested fields (e.g., vendor.name)
                if (this.sortField === "vendor") {
                    fieldA = a.vendor?.name;
                    fieldB = b.vendor?.name;
                }

                // Handle numeric fields
                if (this.sortField === "total_amount") {
                    fieldA = parseFloat(fieldA);
                    fieldB = parseFloat(fieldB);
                }

                // Handle date fields
                if (
                    this.sortField === "po_date" ||
                    this.sortField === "expected_delivery"
                ) {
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
        paginatedPOs() {
            const start = (this.currentPage - 1) * this.pageSize;
            const end = start + this.pageSize;
            return this.filteredPOs.slice(start, end);
        },
        totalPages() {
            return Math.ceil(this.filteredPOs.length / this.pageSize);
        },
        paginationStart() {
            return (this.currentPage - 1) * this.pageSize;
        },
        paginationEnd() {
            const end = this.paginationStart + this.pageSize;
            return end > this.filteredPOs.length
                ? this.filteredPOs.length
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
                // Load vendors list for the filter
                const vendorsResponse = await axios.get("/api/vendors");
                this.vendors = vendorsResponse.data.data || [];

                // Load PO status data
                await this.loadPOStatusData();
            } catch (error) {
                console.error("Error loading initial data:", error);
                // Load mock data for demonstration
                this.loadMockData();
            } finally {
                this.loading = false;
            }
        },

        async loadPOStatusData() {
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

                // API call to get PO status data
                const response = await axios.get(
                    "/api/purchasing/po-status-summary",
                    {
                        params: {
                            start_date: startDate,
                            end_date: endDate,
                            vendor_id: this.filters.vendor,
                        },
                    }
                );

                const data = response.data.data;

                // Update component data
                this.summary = data.summary;
                this.statusData = data.statusDistribution;
                this.trendData = data.statusTrend;
                this.purchaseOrders = data.purchaseOrders;
                this.deliveryPerformance = data.deliveryPerformance;
            } catch (error) {
                console.error("Error loading PO status data:", error);
                // Load mock data if API fails
                this.loadMockData();
            }
        },

        // Mock data for development/demo
        loadMockData() {
            // Mock summary data
            this.summary = {
                draftCount: 14,
                approvedCount: 32,
                sentCount: 48,
                partialCount: 18,
                completedCount: 62,
                canceledCount: 6,
                draftChange: -5.2,
                approvedChange: 12.3,
                sentChange: 7.5,
                partialChange: -2.8,
                completedChange: 15.6,
                canceledChange: -10.4,
            };

            // Mock status distribution data
            this.statusData = [
                { name: "Draft", value: 14, color: "#94a3b8" },
                { name: "Approved", value: 32, color: "#3b82f6" },
                { name: "Sent", value: 48, color: "#0ea5e9" },
                { name: "Partial", value: 18, color: "#f59e0b" },
                { name: "Completed", value: 62, color: "#10b981" },
                { name: "Canceled", value: 6, color: "#ef4444" },
            ];

            // Mock trend data
            this.trendData = [];
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

            for (let i = 0; i < 12; i++) {
                const month = months[i];
                this.trendData.push({
                    month,
                    draft: Math.floor(Math.random() * 10) + 5,
                    approved: Math.floor(Math.random() * 15) + 10,
                    sent: Math.floor(Math.random() * 20) + 15,
                    partial: Math.floor(Math.random() * 10) + 5,
                    completed: Math.floor(Math.random() * 25) + 20,
                    canceled: Math.floor(Math.random() * 5) + 1,
                });
            }

            // Mock PO data
            this.purchaseOrders = [];
            const statuses = [
                "draft",
                "approved",
                "sent",
                "partial",
                "completed",
                "canceled",
            ];
            const vendors = [
                { vendor_id: 1, name: "ABC Supplies" },
                { vendor_id: 2, name: "XYZ Manufacturing" },
                { vendor_id: 3, name: "Global Distributors" },
                { vendor_id: 4, name: "Tech Solutions Inc" },
                { vendor_id: 5, name: "Reliable Parts Co" },
            ];

            // Generate mock vendors if needed
            if (this.vendors.length === 0) {
                this.vendors = vendors;
            }

            // Generate mock POs
            for (let i = 1; i <= 100; i++) {
                const vendor =
                    vendors[Math.floor(Math.random() * vendors.length)];
                const status =
                    statuses[Math.floor(Math.random() * statuses.length)];
                const amount = parseFloat(
                    (Math.random() * 25000 + 1000).toFixed(2)
                );

                // Generate dates
                const poDate = new Date();
                poDate.setDate(
                    poDate.getDate() - Math.floor(Math.random() * 180)
                ); // Last 6 months

                const expectedDelivery = new Date(poDate);
                expectedDelivery.setDate(
                    poDate.getDate() + Math.floor(Math.random() * 30) + 15
                ); // 15-45 days after PO date

                this.purchaseOrders.push({
                    po_id: i,
                    po_number: `PO-2023-${(1000 + i).toString()}`,
                    po_date: this.formatDateForInput(poDate),
                    vendor_id: vendor.vendor_id,
                    vendor: vendor,
                    total_amount: amount,
                    expected_delivery:
                        this.formatDateForInput(expectedDelivery),
                    status,
                });
            }

            // Mock delivery performance data
            const onTimeCount = 142;
            const lateCount = 28;
            const totalDeliveries = onTimeCount + lateCount;
            const onTimePercentage = Math.round(
                (onTimeCount / totalDeliveries) * 100
            );

            this.deliveryPerformance = {
                onTimePercentage,
                totalDeliveries,
                onTimeCount,
                lateCount,
                avgDelay: 3.5,
                monthlyData: [],
            };

            // Generate monthly delivery performance data
            for (let i = 0; i < 12; i++) {
                const month = months[i];
                const onTime = Math.floor(Math.random() * 20) + 10;
                const late = Math.floor(Math.random() * 8) + 1;
                const percentage = Math.round((onTime / (onTime + late)) * 100);

                this.deliveryPerformance.monthlyData.push({
                    month,
                    onTime,
                    late,
                    percentage,
                });
            }
        },

        applyFilters() {
            this.loading = true;
            this.currentPage = 1; // Reset pagination
            this.loadPOStatusData();
        },

        resetFilters() {
            this.filters.dateRange = "month";
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
                "PO Number",
                "Date",
                "Vendor",
                "Amount",
                "Expected Delivery",
                "Status",
            ];
            let csvContent = headers.join(",") + "\n";

            this.filteredPOs.forEach((po) => {
                const row = [
                    po.po_number,
                    this.formatDate(po.po_date),
                    po.vendor.name,
                    po.total_amount,
                    this.formatDate(po.expected_delivery),
                    po.status,
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
                `po-status-${this.formatDateForFilename(new Date())}.csv`
            );
            link.style.visibility = "hidden";
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        },

        generateReport() {
            // This would typically generate a PDF report
            // For now, just show an alert
            alert("Report generation functionality would be implemented here.");
        },

        openStatusModal(po) {
            this.selectedPO = po;
            this.newStatus = po.status;

            // Determine available status transitions
            this.availableStatuses = this.getAvailableStatuses(po.status);

            // Open the modal - you would need to use a proper modal implementation here
            // For Bootstrap, this would be:
            // $('#statusUpdateModal').modal('show');
        },

        async updateStatus() {
            if (!this.selectedPO || this.newStatus === this.selectedPO.status) {
                return;
            }

            try {
                // API call to update status
                await axios.patch(
                    `/api/purchase-orders/${this.selectedPO.po_id}/status`,
                    {
                        status: this.newStatus,
                    }
                );

                // Update local data
                const poIndex = this.purchaseOrders.findIndex(
                    (po) => po.po_id === this.selectedPO.po_id
                );
                if (poIndex !== -1) {
                    this.purchaseOrders[poIndex].status = this.newStatus;
                }

                // Close modal
                // $('#statusUpdateModal').modal('hide');

                // Refresh data
                this.loadPOStatusData();
            } catch (error) {
                console.error("Error updating PO status:", error);
                alert("Failed to update PO status. Please try again.");
            }
        },

        getAvailableStatuses(currentStatus) {
            // Define valid status transitions based on current status
            const validTransitions = {
                draft: ["submitted", "canceled"],
                submitted: ["approved", "canceled"],
                approved: ["sent", "canceled"],
                sent: ["partial", "received", "canceled"],
                partial: ["completed", "canceled"],
                received: ["completed", "canceled"],
                completed: ["canceled"],
                canceled: [],
            };

            return validTransitions[currentStatus] || [];
        },

        canEditStatus(status) {
            // Check if the status can be edited (has valid transitions)
            const transitions = this.getAvailableStatuses(status);
            return transitions.length > 0;
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

        getDeliveryPerformanceClass(percentage) {
            if (percentage >= 90) return "circle-excellent";
            if (percentage >= 80) return "circle-good";
            if (percentage >= 70) return "circle-adequate";
            if (percentage >= 60) return "circle-fair";
            return "circle-poor";
        },
    },
};
</script>

<style scoped>
.po-status-container {
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

.status-icon-container {
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}

.status-icon-container i {
    font-size: 1.25rem;
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

/* Progress circle styles */
.progress-circle {
    width: 150px;
    height: 150px;
}

.circular-chart {
    width: 100%;
    height: 100%;
    transform: rotate(-90deg);
}

.circle-bg {
    fill: none;
    stroke: #eee;
    stroke-width: 3.8;
}

.circle-excellent,
.circle-good,
.circle-adequate,
.circle-fair,
.circle-poor {
    fill: none;
    stroke-width: 3.8;
    stroke-linecap: round;
}

.circle-excellent {
    stroke: var(--success-color);
}

.circle-good {
    stroke: var(--primary-color);
}

.circle-adequate {
    stroke: var(--primary-light);
}

.circle-fair {
    stroke: var(--warning-color);
}

.circle-poor {
    stroke: var(--danger-color);
}

.percentage {
    fill: var(--gray-700);
    font-size: 0.5em;
    text-anchor: middle;
    font-weight: bold;
}

.progress-label {
    position: absolute;
    bottom: -30px;
    left: 0;
    right: 0;
    text-align: center;
    font-weight: 500;
    color: var(--gray-700);
}

.delivery-stat {
    display: flex;
    justify-content: space-between;
    padding: 4px 20px;
}

.stat-label {
    font-weight: 500;
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
