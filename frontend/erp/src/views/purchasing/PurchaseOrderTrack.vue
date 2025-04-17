<!-- src/views/purchasing/PurchaseOrderTrack.vue -->
<template>
    <div class="po-track-container">
        <div class="page-header">
            <div class="header-left">
                <router-link :to="`/purchasing/orders/${id}`" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to Purchase Order
                </router-link>
                <h1>Purchase Order Status Tracking</h1>
            </div>
        </div>

        <div v-if="isLoading" class="loading-container">
            <div class="loading-spinner">
                <i class="fas fa-spinner fa-spin"></i>
            </div>
            <p>Loading purchase order data...</p>
        </div>

        <div v-else-if="!purchaseOrder" class="error-container">
            <div class="error-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h2>Purchase Order Not Found</h2>
            <p>
                The requested purchase order could not be found or may have been
                deleted.
            </p>
            <router-link to="/purchasing/orders" class="btn btn-primary">
                Return to Purchase Orders List
            </router-link>
        </div>

        <div v-else class="po-track-content">
            <!-- Purchase Order Details -->
            <div class="detail-card">
                <div class="card-header">
                    <h2 class="card-title">Purchase Order Information</h2>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">PO Number</span>
                            <span class="info-value">{{
                                purchaseOrder.po_number
                            }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">PO Date</span>
                            <span class="info-value">{{
                                formatDate(purchaseOrder.po_date)
                            }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Vendor</span>
                            <span class="info-value">{{
                                purchaseOrder.vendor.name
                            }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Current Status</span>
                            <span
                                :class="[
                                    'info-value status-badge',
                                    getStatusClass(purchaseOrder.status),
                                ]"
                            >
                                {{ formatStatus(purchaseOrder.status) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Timeline -->
            <div class="detail-card">
                <div class="card-header">
                    <h2 class="card-title">Status Timeline</h2>
                </div>
                <div class="card-body">
                    <div class="status-timeline">
                        <div
                            v-for="(status, index) in statusFlow"
                            :key="status.id"
                            :class="[
                                'timeline-item',
                                {
                                    'status-completed': statusReached(
                                        status.id
                                    ),
                                    'status-current': isCurrent(status.id),
                                    'status-pending': !statusReached(status.id),
                                },
                            ]"
                        >
                            <div
                                class="timeline-connector"
                                v-if="index < statusFlow.length - 1"
                            ></div>
                            <div class="timeline-step">
                                <div class="timeline-icon">
                                    <i :class="status.icon"></i>
                                </div>
                                <div class="timeline-content">
                                    <div class="timeline-title">
                                        {{ status.label }}
                                    </div>
                                    <div class="timeline-description">
                                        {{ status.description }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Actions -->
            <div v-if="canUpdateStatus" class="detail-card">
                <div class="card-header">
                    <h2 class="card-title">Status Updates</h2>
                </div>
                <div class="card-body">
                    <div class="status-updates">
                        <p>
                            You can update the status of this purchase order to:
                        </p>
                        <div class="status-actions">
                            <div
                                v-for="status in availableStatusTransitions"
                                :key="status"
                                class="status-action-card"
                            >
                                <div class="status-action-header">
                                    <span
                                        :class="[
                                            'status-badge',
                                            getStatusClass(status),
                                        ]"
                                    >
                                        {{ formatStatus(status) }}
                                    </span>
                                </div>
                                <div class="status-action-description">
                                    <p>{{ getStatusDescription(status) }}</p>
                                </div>
                                <div class="status-action-footer">
                                    <button
                                        @click="updateStatus(status)"
                                        :disabled="isUpdating"
                                        class="btn btn-primary"
                                    >
                                        {{
                                            isUpdating
                                                ? "Updating..."
                                                : "Update to " +
                                                  formatStatus(status)
                                        }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Related Records -->
            <div v-if="hasRelatedRecords" class="detail-card">
                <div class="card-header">
                    <h2 class="card-title">Related Records</h2>
                </div>
                <div class="card-body">
                    <!-- Goods Receipts -->
                    <div
                        v-if="
                            purchaseOrder.goodsReceipts &&
                            purchaseOrder.goodsReceipts.length > 0
                        "
                        class="related-records-section"
                    >
                        <h3 class="section-subtitle">Goods Receipts</h3>
                        <div class="related-records-cards">
                            <div
                                v-for="receipt in purchaseOrder.goodsReceipts"
                                :key="receipt.receipt_id"
                                class="related-record-card"
                            >
                                <div class="related-record-header">
                                    <span>{{ receipt.receipt_number }}</span>
                                    <span
                                        :class="[
                                            'status-badge',
                                            getReceiptStatusClass(
                                                receipt.status
                                            ),
                                        ]"
                                    >
                                        {{ receipt.status }}
                                    </span>
                                </div>
                                <div class="related-record-content">
                                    <div class="record-info">
                                        <span class="info-label">Date:</span>
                                        <span>{{
                                            formatDate(receipt.receipt_date)
                                        }}</span>
                                    </div>
                                    <div class="record-info">
                                        <span class="info-label"
                                            >Received By:</span
                                        >
                                        <span>{{
                                            receipt.received_by || "N/A"
                                        }}</span>
                                    </div>
                                </div>
                                <div class="related-record-footer">
                                    <router-link
                                        :to="`/purchasing/goods-receipts/${receipt.receipt_id}`"
                                        class="btn btn-sm btn-outline"
                                    >
                                        View Details
                                    </router-link>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Invoices -->
                    <div
                        v-if="
                            purchaseOrder.invoices &&
                            purchaseOrder.invoices.length > 0
                        "
                        class="related-records-section"
                    >
                        <h3 class="section-subtitle">Vendor Invoices</h3>
                        <div class="related-records-cards">
                            <div
                                v-for="invoice in purchaseOrder.invoices"
                                :key="invoice.invoice_id"
                                class="related-record-card"
                            >
                                <div class="related-record-header">
                                    <span>{{ invoice.invoice_number }}</span>
                                    <span
                                        :class="[
                                            'status-badge',
                                            getInvoiceStatusClass(
                                                invoice.status
                                            ),
                                        ]"
                                    >
                                        {{ invoice.status }}
                                    </span>
                                </div>
                                <div class="related-record-content">
                                    <div class="record-info">
                                        <span class="info-label">Date:</span>
                                        <span>{{
                                            formatDate(invoice.invoice_date)
                                        }}</span>
                                    </div>
                                    <div class="record-info">
                                        <span class="info-label">Amount:</span>
                                        <span>{{
                                            formatCurrency(invoice.total_amount)
                                        }}</span>
                                    </div>
                                    <div class="record-info">
                                        <span class="info-label"
                                            >Due Date:</span
                                        >
                                        <span>{{
                                            formatDate(invoice.due_date)
                                        }}</span>
                                    </div>
                                </div>
                                <div class="related-record-footer">
                                    <router-link
                                        :to="`/purchasing/vendor-invoices/${invoice.invoice_id}`"
                                        class="btn btn-sm btn-outline"
                                    >
                                        View Details
                                    </router-link>
                                </div>
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
//import { useRouter } from "vue-router";
import PurchaseOrderService from "@/services/PurchaseOrderService";

export default {
    name: "PurchaseOrderTrack",
    props: {
        id: {
            type: [Number, String],
            required: true,
        },
    },
    setup(props) {
        //const router = useRouter();
        const purchaseOrder = ref(null);
        const isLoading = ref(true);
        const isUpdating = ref(false);

        // Status flow definition
        const statusFlow = [
            {
                id: "draft",
                label: "Draft",
                icon: "fas fa-pencil-alt",
                description:
                    "Purchase order created but not yet submitted for approval.",
            },
            {
                id: "submitted",
                label: "Submitted",
                icon: "fas fa-clipboard-check",
                description: "Purchase order submitted for approval.",
            },
            {
                id: "approved",
                label: "Approved",
                icon: "fas fa-check-circle",
                description:
                    "Purchase order approved and ready to be sent to vendor.",
            },
            {
                id: "sent",
                label: "Sent to Vendor",
                icon: "fas fa-paper-plane",
                description: "Purchase order has been sent to the vendor.",
            },
            {
                id: "received",
                label: "Goods Received",
                icon: "fas fa-truck-loading",
                description: "Goods have been received from the vendor.",
            },
            {
                id: "completed",
                label: "Completed",
                icon: "fas fa-flag-checkered",
                description: "Purchase order completely fulfilled and closed.",
            },
        ];

        // Status transitions map - defines which statuses can transition to which other statuses
        const statusTransitions = {
            draft: ["submitted", "canceled"],
            submitted: ["approved", "canceled"],
            approved: ["sent", "canceled"],
            sent: ["partial", "received", "canceled"],
            partial: ["completed", "canceled"],
            received: ["completed", "canceled"],
            completed: ["canceled"],
            canceled: [],
        };

        // Status descriptions for status change actions
        const statusDescriptions = {
            submitted:
                "Submit this purchase order for approval by the authorized personnel.",
            approved:
                "Approve this purchase order. Ready to be sent to the vendor.",
            sent: "Mark as sent to the vendor. The vendor will process the order.",
            partial:
                "Mark as partially received. Some items have arrived, but not all.",
            received: "Mark as received. All ordered items have been received.",
            completed:
                "Mark as completed. All items received and administrative tasks completed.",
            canceled:
                "Cancel this purchase order. This action cannot be reversed.",
        };

        // Fetch purchase order details
        const fetchPurchaseOrder = async () => {
            isLoading.value = true;
            try {
                const response =
                    await PurchaseOrderService.getPurchaseOrderById(props.id);
                purchaseOrder.value = response.data.data || null;
            } catch (error) {
                console.error("Error fetching purchase order details:", error);
                purchaseOrder.value = null;
            } finally {
                isLoading.value = false;
            }
        };

        // Computed property to get available status transitions for current status
        const availableStatusTransitions = computed(() => {
            if (!purchaseOrder.value) return [];
            return statusTransitions[purchaseOrder.value.status] || [];
        });

        // Computed property to determine if user can update status
        const canUpdateStatus = computed(() => {
            return (
                purchaseOrder.value &&
                availableStatusTransitions.value.length > 0
            );
        });

        // Computed property to determine if there are related records
        const hasRelatedRecords = computed(() => {
            if (!purchaseOrder.value) return false;

            const hasGoodsReceipts =
                purchaseOrder.value.goodsReceipts &&
                purchaseOrder.value.goodsReceipts.length > 0;
            const hasInvoices =
                purchaseOrder.value.invoices &&
                purchaseOrder.value.invoices.length > 0;

            return hasGoodsReceipts || hasInvoices;
        });

        // Check if a status has been reached
        const statusReached = (statusId) => {
            if (!purchaseOrder.value) return false;

            const statusOrder = statusFlow.map((s) => s.id);
            const currentIndex = statusOrder.indexOf(
                purchaseOrder.value.status
            );
            const targetIndex = statusOrder.indexOf(statusId);

            // Special handling for 'partial' status
            if (
                purchaseOrder.value.status === "partial" &&
                statusId !== "partial"
            ) {
                return (
                    statusId === "draft" ||
                    statusId === "submitted" ||
                    statusId === "approved" ||
                    statusId === "sent"
                );
            }

            // Special handling for 'canceled' status
            if (purchaseOrder.value.status === "canceled") {
                return false;
            }

            return targetIndex <= currentIndex;
        };

        // Check if a status is the current status
        const isCurrent = (statusId) => {
            if (!purchaseOrder.value) return false;

            // Special handling for 'partial' status which isn't in the normal flow
            if (
                purchaseOrder.value.status === "partial" &&
                statusId === "received"
            ) {
                return true;
            }

            return purchaseOrder.value.status === statusId;
        };

        // Format date strings
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
        const formatCurrency = (amount) => {
            if (amount === null || amount === undefined) return "$0.00";
            return new Intl.NumberFormat("en-US", {
                style: "currency",
                currency: "USD",
            }).format(amount);
        };

        // Format status text
        const formatStatus = (status) => {
            switch (status) {
                case "draft":
                    return "Draft";
                case "submitted":
                    return "Submitted";
                case "approved":
                    return "Approved";
                case "sent":
                    return "Sent";
                case "partial":
                    return "Partially Received";
                case "received":
                    return "Received";
                case "completed":
                    return "Completed";
                case "canceled":
                    return "Canceled";
                default:
                    return status;
            }
        };

        // Get status description for action cards
        const getStatusDescription = (status) => {
            return statusDescriptions[status] || "";
        };

        // Get status CSS class
        const getStatusClass = (status) => {
            switch (status) {
                case "draft":
                    return "status-draft";
                case "submitted":
                    return "status-pending";
                case "approved":
                    return "status-approved";
                case "sent":
                    return "status-sent";
                case "partial":
                    return "status-partial";
                case "received":
                    return "status-received";
                case "completed":
                    return "status-completed";
                case "canceled":
                    return "status-canceled";
                default:
                    return "status-draft";
            }
        };

        // Get receipt status CSS class
        const getReceiptStatusClass = (status) => {
            switch (status) {
                case "pending":
                    return "status-pending";
                case "confirmed":
                    return "status-completed";
                case "canceled":
                    return "status-canceled";
                default:
                    return "status-draft";
            }
        };

        // Get invoice status CSS class
        const getInvoiceStatusClass = (status) => {
            switch (status) {
                case "pending":
                    return "status-pending";
                case "approved":
                    return "status-approved";
                case "paid":
                    return "status-completed";
                case "canceled":
                    return "status-canceled";
                default:
                    return "status-draft";
            }
        };

        // Update purchase order status
        const updateStatus = async (status) => {
            if (!status) return;

            isUpdating.value = true;

            try {
                await PurchaseOrderService.updatePurchaseOrderStatus(
                    props.id,
                    status
                );

                // Refresh data
                await fetchPurchaseOrder();
            } catch (error) {
                console.error("Error updating purchase order status:", error);

                if (
                    error.response &&
                    error.response.data &&
                    error.response.data.message
                ) {
                    alert(error.response.data.message);
                } else {
                    alert("Failed to update status. Please try again.");
                }
            } finally {
                isUpdating.value = false;
            }
        };

        // Initialize
        onMounted(() => {
            fetchPurchaseOrder();
        });

        return {
            purchaseOrder,
            isLoading,
            isUpdating,
            statusFlow,
            availableStatusTransitions,
            canUpdateStatus,
            hasRelatedRecords,
            statusReached,
            isCurrent,
            formatDate,
            formatCurrency,
            formatStatus,
            getStatusDescription,
            getStatusClass,
            getReceiptStatusClass,
            getInvoiceStatusClass,
            updateStatus,
        };
    },
};
</script>

<style scoped>
.po-track-container {
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

.po-track-content {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.detail-card {
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

.card-title {
    margin: 0;
    font-size: 1.25rem;
    color: var(--gray-800);
}

.card-body {
    padding: 1.5rem;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1.5rem;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.info-label {
    font-size: 0.875rem;
    color: var(--gray-500);
}

.info-value {
    font-size: 1rem;
    color: var(--gray-800);
    font-weight: 500;
}

/* Status Timeline Styling */
.status-timeline {
    position: relative;
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.timeline-item {
    position: relative;
    display: flex;
    flex-direction: column;
}

.timeline-connector {
    position: absolute;
    top: 2rem;
    left: 1rem;
    width: 2px;
    height: calc(100% + 2rem);
    background-color: var(--gray-200);
    z-index: 1;
}

.timeline-step {
    display: flex;
    gap: 1.5rem;
    position: relative;
    z-index: 2;
}

.timeline-icon {
    width: 2rem;
    height: 2rem;
    border-radius: 50%;
    background-color: var(--gray-200);
    color: var(--gray-600);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.timeline-content {
    flex: 1;
    padding-top: 0.25rem;
}

.timeline-title {
    font-weight: 600;
    color: var(--gray-700);
    margin-bottom: 0.25rem;
}

.timeline-description {
    font-size: 0.875rem;
    color: var(--gray-500);
}

/* Status styles */
.status-completed .timeline-icon {
    background-color: var(--primary-color);
    color: white;
}

.status-completed .timeline-connector {
    background-color: var(--primary-color);
}

.status-completed .timeline-title {
    color: var(--primary-color);
}

.status-current .timeline-icon {
    background-color: var(--warning-color);
    color: white;
    box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.2);
}

.status-current .timeline-title {
    color: var(--warning-color);
}

/* Status Actions Styling */
.status-updates p {
    margin-top: 0;
    margin-bottom: 1.5rem;
}

.status-actions {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1rem;
}

.status-action-card {
    border: 1px solid var(--gray-200);
    border-radius: 0.5rem;
    overflow: hidden;
}

.status-action-header {
    padding: 1rem;
    background-color: var(--gray-50);
    border-bottom: 1px solid var(--gray-200);
}

.status-action-description {
    padding: 1rem;
}

.status-action-description p {
    margin: 0;
    font-size: 0.875rem;
    color: var(--gray-600);
}

.status-action-footer {
    padding: 1rem;
    background-color: var(--gray-50);
    border-top: 1px solid var(--gray-200);
    display: flex;
    justify-content: flex-end;
}

/* Related Records Styling */
.related-records-section {
    margin-bottom: 2rem;
}

.related-records-section:last-child {
    margin-bottom: 0;
}

.section-subtitle {
    font-size: 1.125rem;
    color: var(--gray-700);
    margin-top: 0;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid var(--gray-200);
}

.related-records-cards {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1rem;
}

.related-record-card {
    border: 1px solid var(--gray-200);
    border-radius: 0.5rem;
    overflow: hidden;
}

.related-record-header {
    padding: 0.75rem 1rem;
    background-color: var(--gray-50);
    border-bottom: 1px solid var(--gray-200);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.related-record-content {
    padding: 1rem;
}

.record-info {
    margin-bottom: 0.5rem;
    display: flex;
    font-size: 0.875rem;
}

.record-info .info-label {
    min-width: 100px;
    font-weight: 500;
}

.related-record-footer {
    padding: 0.75rem 1rem;
    background-color: var(--gray-50);
    border-top: 1px solid var(--gray-200);
    text-align: right;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
}

.status-draft {
    background-color: var(--gray-100);
    color: var(--gray-700);
}

.status-pending {
    background-color: #fef3c7;
    color: #92400e;
}

.status-approved {
    background-color: #dcfce7;
    color: #166534;
}

.status-sent {
    background-color: #dbeafe;
    color: #1e40af;
}

.status-partial {
    background-color: #fef9c3;
    color: #854d0e;
}

.status-received {
    background-color: #d1fae5;
    color: #065f46;
}

.status-completed {
    background-color: #bbf7d0;
    color: #15803d;
}

.status-canceled {
    background-color: #fee2e2;
    color: #b91c1c;
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

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.75rem;
}

.btn-primary {
    background-color: var(--primary-color);
    color: white;
}

.btn-primary:hover:not(:disabled) {
    background-color: var(--primary-dark);
}

.btn-primary:disabled {
    background-color: #93c5fd;
    cursor: not-allowed;
}

.btn-outline {
    background-color: transparent;
    border: 1px solid var(--primary-color);
    color: var(--primary-color);
}

.btn-outline:hover {
    background-color: var(--primary-bg);
}

@media (max-width: 768px) {
    .info-grid {
        grid-template-columns: 1fr;
    }

    .status-actions,
    .related-records-cards {
        grid-template-columns: 1fr;
    }
}
</style>
