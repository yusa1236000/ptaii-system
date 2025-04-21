<!-- src/views/purchasing/PendingReceiptsDashboard.vue -->
<template>
    <div class="pending-receipts-dashboard-container">
        <div class="page-header">
            <h1>Pending Goods Receipts Dashboard</h1>
            <div class="header-actions">
                <router-link
                    to="/purchasing/goods-receipts/create"
                    class="btn btn-primary"
                >
                    <i class="fas fa-plus"></i> Create Goods Receipt
                </router-link>
            </div>
        </div>

        <div class="dashboard-content">
            <!-- Summary Cards Section -->
            <div class="summary-section">
                <div class="summary-card">
                    <div class="card-content">
                        <div class="card-icon pending-icon">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <div class="card-data">
                            <div class="card-value">{{ pendingCount }}</div>
                            <div class="card-label">Pending Receipts</div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <router-link
                            to="/purchasing/goods-receipts?status=pending"
                        >
                            View All <i class="fas fa-arrow-right"></i>
                        </router-link>
                    </div>
                </div>

                <div class="summary-card">
                    <div class="card-content">
                        <div class="card-icon overdue-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="card-data">
                            <div class="card-value">{{ overdueCount }}</div>
                            <div class="card-label">Overdue (>48h)</div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <router-link
                            to="/purchasing/goods-receipts?status=pending&sort_field=receipt_date&sort_direction=asc"
                        >
                            View All <i class="fas fa-arrow-right"></i>
                        </router-link>
                    </div>
                </div>

                <div class="summary-card">
                    <div class="card-content">
                        <div class="card-icon today-icon">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                        <div class="card-data">
                            <div class="card-value">{{ todayCount }}</div>
                            <div class="card-label">Created Today</div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <router-link
                            :to="`/purchasing/goods-receipts?date_from=${todayDateStr}&date_to=${todayDateStr}`"
                        >
                            View All <i class="fas fa-arrow-right"></i>
                        </router-link>
                    </div>
                </div>

                <div class="summary-card">
                    <div class="card-content">
                        <div class="card-icon confirmed-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="card-data">
                            <div class="card-value">
                                {{ confirmedTodayCount }}
                            </div>
                            <div class="card-label">Confirmed Today</div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <router-link
                            :to="`/purchasing/goods-receipts?status=confirmed&date_from=${todayDateStr}&date_to=${todayDateStr}`"
                        >
                            View All <i class="fas fa-arrow-right"></i>
                        </router-link>
                    </div>
                </div>
            </div>

            <!-- Recent Pending Receipts Section -->
            <div class="table-section">
                <div class="section-header">
                    <h2>Recent Pending Receipts</h2>
                    <router-link
                        to="/purchasing/goods-receipts?status=pending"
                        class="view-all-link"
                    >
                        View All <i class="fas fa-arrow-right"></i>
                    </router-link>
                </div>

                <div v-if="isLoading" class="loading-indicator">
                    <i class="fas fa-spinner fa-spin"></i> Loading pending
                    receipts...
                </div>

                <div
                    v-else-if="pendingReceipts.length === 0"
                    class="empty-state"
                >
                    <div class="empty-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3>All Caught Up!</h3>
                    <p>There are no pending goods receipts at the moment.</p>
                </div>

                <div v-else class="data-table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Receipt #</th>
                                <th>Date</th>
                                <th>Vendor</th>
                                <th>PO #</th>
                                <th>Age</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="receipt in pendingReceipts"
                                :key="receipt.receipt_id"
                                :class="{
                                    'overdue-row': isOverdue(
                                        receipt.receipt_date
                                    ),
                                }"
                            >
                                <td>{{ receipt.receipt_number }}</td>
                                <td>{{ formatDate(receipt.receipt_date) }}</td>
                                <td>
                                    {{
                                        receipt.vendor
                                            ? receipt.vendor.name
                                            : "N/A"
                                    }}
                                </td>
                                <td>
                                    <router-link
                                        v-if="receipt.purchaseOrder"
                                        :to="`/purchasing/orders/${receipt.po_id}`"
                                    >
                                        {{ receipt.purchaseOrder.po_number }}
                                    </router-link>
                                    <span v-else>N/A</span>
                                </td>
                                <td>
                                    <span
                                        :class="
                                            getAgeClass(receipt.receipt_date)
                                        "
                                    >
                                        {{ getAgeText(receipt.receipt_date) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <router-link
                                            :to="`/purchasing/goods-receipts/${receipt.receipt_id}`"
                                            class="action-btn view-btn"
                                            title="View Details"
                                        >
                                            <i class="fas fa-eye"></i>
                                        </router-link>
                                        <router-link
                                            :to="`/purchasing/goods-receipts/${receipt.receipt_id}/confirm`"
                                            class="action-btn confirm-btn"
                                            title="Confirm Receipt"
                                        >
                                            <i class="fas fa-check-circle"></i>
                                        </router-link>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Items to Receive Section -->
            <div class="table-section">
                <div class="section-header">
                    <h2>Top Items Awaiting Receipt</h2>
                </div>

                <div v-if="isLoadingItems" class="loading-indicator">
                    <i class="fas fa-spinner fa-spin"></i> Loading items data...
                </div>

                <div v-else-if="pendingItems.length === 0" class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <h3>No Items Pending</h3>
                    <p>There are no items awaiting receipt at the moment.</p>
                </div>

                <div v-else class="data-table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Code</th>
                                <th>Pending Qty</th>
                                <th>Value</th>
                                <th>Open POs</th>
                                <th>Oldest PO Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="item in pendingItems"
                                :key="item.item_id"
                            >
                                <td>{{ item.name }}</td>
                                <td>{{ item.item_code }}</td>
                                <td>{{ item.pending_quantity }}</td>
                                <td>
                                    {{ formatCurrency(item.pending_value) }}
                                </td>
                                <td>{{ item.open_pos }}</td>
                                <td>
                                    <span
                                        :class="
                                            getAgeClass(item.oldest_po_date)
                                        "
                                    >
                                        {{ formatDate(item.oldest_po_date) }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, computed, onMounted } from "vue";

export default {
    name: "PendingReceiptsDashboard",
    setup() {
        // Data
        const pendingReceipts = ref([]);
        const pendingItems = ref([]);
        const isLoading = ref(true);
        const isLoadingItems = ref(true);

        // Summary counts
        const pendingCount = ref(0);
        const overdueCount = ref(0);
        const todayCount = ref(0);
        const confirmedTodayCount = ref(0);

        // Today's date for filtering
        const today = new Date();
        const todayDateStr = computed(() => {
            return today.toISOString().slice(0, 10);
        });

        // Fetch pending receipts
        const fetchPendingReceipts = async () => {
            isLoading.value = true;

            try {
                const response = await fetch(
                    "/api/goods-receipts?status=pending&per_page=5&sort_field=receipt_date&sort_direction=asc",
                    {
                        headers: {
                            Authorization: `Bearer ${localStorage.getItem(
                                "token"
                            )}`,
                            "Content-Type": "application/json",
                        },
                    }
                );

                const data = await response.json();

                if (data && data.data) {
                    pendingReceipts.value = data.data;
                } else {
                    pendingReceipts.value = [];
                }
            } catch (error) {
                console.error("Error fetching pending receipts:", error);
                pendingReceipts.value = [];
            } finally {
                isLoading.value = false;
            }
        };

        // Fetch dashboard summary
        const fetchDashboardSummary = async () => {
            try {
                const response = await fetch(
                    "/api/goods-receipts/dashboard-summary",
                    {
                        headers: {
                            Authorization: `Bearer ${localStorage.getItem(
                                "token"
                            )}`,
                            "Content-Type": "application/json",
                        },
                    }
                );

                const data = await response.json();

                if (data && data.data) {
                    pendingCount.value = data.data.pending_count || 0;
                    overdueCount.value = data.data.overdue_count || 0;
                    todayCount.value = data.data.today_count || 0;
                    confirmedTodayCount.value =
                        data.data.confirmed_today_count || 0;
                }
            } catch (error) {
                console.error("Error fetching dashboard summary:", error);
            }
        };

        // Fetch pending items
        const fetchPendingItems = async () => {
            isLoadingItems.value = true;

            try {
                const response = await fetch(
                    "/api/goods-receipts/pending-items",
                    {
                        headers: {
                            Authorization: `Bearer ${localStorage.getItem(
                                "token"
                            )}`,
                            "Content-Type": "application/json",
                        },
                    }
                );

                const data = await response.json();

                if (data && data.data) {
                    pendingItems.value = data.data;
                } else {
                    pendingItems.value = [];
                }
            } catch (error) {
                console.error("Error fetching pending items:", error);
                pendingItems.value = [];
            } finally {
                isLoadingItems.value = false;
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

        // Format currency
        const formatCurrency = (value) => {
            if (value === undefined || value === null) return "$0.00";
            return new Intl.NumberFormat("en-US", {
                style: "currency",
                currency: "USD",
            }).format(value);
        };

        // Check if a receipt is overdue (older than 48 hours)
        const isOverdue = (dateString) => {
            if (!dateString) return false;

            const receiptDate = new Date(dateString);
            const now = new Date();
            const diffHours = (now - receiptDate) / (1000 * 60 * 60);

            return diffHours > 48;
        };

        // Get age text
        const getAgeText = (dateString) => {
            if (!dateString) return "N/A";

            const receiptDate = new Date(dateString);
            const now = new Date();
            const diffHours = Math.floor(
                (now - receiptDate) / (1000 * 60 * 60)
            );

            if (diffHours < 1) {
                return "Just now";
            } else if (diffHours < 24) {
                return `${diffHours} hour${diffHours > 1 ? "s" : ""}`;
            } else {
                const diffDays = Math.floor(diffHours / 24);
                return `${diffDays} day${diffDays > 1 ? "s" : ""}`;
            }
        };

        // Get CSS class based on age
        const getAgeClass = (dateString) => {
            if (!dateString) return "";

            const itemDate = new Date(dateString);
            const now = new Date();
            const diffHours = (now - itemDate) / (1000 * 60 * 60);

            if (diffHours < 24) {
                return "age-normal";
            } else if (diffHours < 48) {
                return "age-warning";
            } else {
                return "age-critical";
            }
        };

        // Initialize
        onMounted(() => {
            fetchPendingReceipts();
            fetchDashboardSummary();
            fetchPendingItems();
        });

        return {
            pendingReceipts,
            pendingItems,
            isLoading,
            isLoadingItems,
            pendingCount,
            overdueCount,
            todayCount,
            confirmedTodayCount,
            todayDateStr,
            formatDate,
            formatCurrency,
            isOverdue,
            getAgeText,
            getAgeClass,
        };
    },
};
</script>

<style scoped>
.pending-receipts-dashboard-container {
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

.dashboard-content {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

/* Summary Cards */
.summary-section {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1.5rem;
}

.summary-card {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.card-content {
    display: flex;
    padding: 1.5rem;
    gap: 1rem;
}

.card-icon {
    width: 3rem;
    height: 3rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.5rem;
    font-size: 1.5rem;
}

.pending-icon {
    background-color: #dbeafe;
    color: #1e40af;
}

.overdue-icon {
    background-color: #fee2e2;
    color: #b91c1c;
}

.today-icon {
    background-color: #f0fdf4;
    color: #166534;
}

.confirmed-icon {
    background-color: #d1fae5;
    color: #065f46;
}

.card-data {
    display: flex;
    flex-direction: column;
}

.card-value {
    font-size: 1.875rem;
    font-weight: 600;
    color: var(--gray-900);
}

.card-label {
    font-size: 0.875rem;
    color: var(--gray-500);
}

.card-footer {
    padding: 0.75rem 1.5rem;
    background-color: var(--gray-50);
    border-top: 1px solid var(--gray-200);
}

.card-footer a {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 0.5rem;
    color: var(--primary-color);
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
}

.card-footer a:hover {
    color: var(--primary-dark);
}

/* Table Sections */
.table-section {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    background-color: var(--gray-50);
    border-bottom: 1px solid var(--gray-200);
}

.section-header h2 {
    margin: 0;
    font-size: 1.25rem;
    color: var(--gray-800);
}

.view-all-link {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--primary-color);
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
}

.view-all-link:hover {
    color: var(--primary-dark);
}

.loading-indicator {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 2rem;
    color: var(--gray-500);
    font-size: 0.875rem;
}

.loading-indicator i {
    margin-right: 0.5rem;
}

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

.data-table-wrapper {
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
}

.data-table th {
    text-align: left;
    padding: 0.75rem 1rem;
    background-color: var(--gray-50);
    font-weight: 500;
    color: var(--gray-700);
    border-bottom: 1px solid var(--gray-200);
}

.data-table td {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--gray-100);
}

.data-table tr:hover td {
    background-color: var(--gray-50);
}

.overdue-row td {
    background-color: #fef2f2;
}

.overdue-row:hover td {
    background-color: #fee2e2;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
}

.action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: background-color 0.2s;
}

.view-btn {
    color: var(--primary-color);
}

.view-btn:hover {
    background-color: var(--primary-bg);
}

.confirm-btn {
    color: var(--success-color);
}

.confirm-btn:hover {
    background-color: var(--success-bg);
}

.age-normal {
    color: var(--gray-700);
}

.age-warning {
    color: #d97706;
}

.age-critical {
    color: #dc2626;
    font-weight: 500;
}

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

.btn-primary {
    background-color: var(--primary-color);
    color: white;
}

.btn-primary:hover {
    background-color: var(--primary-dark);
}

@media (max-width: 768px) {
    .summary-section {
        grid-template-columns: 1fr;
    }
}
</style>
