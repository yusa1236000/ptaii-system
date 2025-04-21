<!-- src/views/purchasing/ReceiptConfirmation.vue -->
<template>
    <div class="receipt-confirmation-container">
        <div class="page-header">
            <div class="header-left">
                <router-link
                    :to="`/purchasing/goods-receipts/${id}`"
                    class="back-link"
                >
                    <i class="fas fa-arrow-left"></i> Back to Goods Receipt
                </router-link>
                <h1>Confirm Goods Receipt</h1>
            </div>
        </div>

        <div v-if="isLoading" class="loading-container">
            <div class="loading-spinner">
                <i class="fas fa-spinner fa-spin"></i>
            </div>
            <p>Loading goods receipt data...</p>
        </div>

        <div v-else-if="!receipt" class="error-container">
            <div class="error-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h2>Goods Receipt Not Found</h2>
            <p>
                The requested goods receipt could not be found or may have been
                deleted.
            </p>
            <router-link
                to="/purchasing/goods-receipts"
                class="btn btn-primary"
            >
                Return to Goods Receipts List
            </router-link>
        </div>

        <div v-else-if="receipt.status !== 'pending'" class="error-container">
            <div class="error-icon">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <h2>Cannot Confirm This Receipt</h2>
            <p>
                This goods receipt is already in
                {{ formatStatus(receipt.status) }} status and cannot be
                confirmed.
            </p>
            <router-link
                :to="`/purchasing/goods-receipts/${id}`"
                class="btn btn-primary"
            >
                Return to Goods Receipt Details
            </router-link>
        </div>

        <div v-else class="confirmation-content">
            <div class="detail-card">
                <div class="card-header">
                    <h2 class="card-title">Review and Confirm</h2>
                </div>
                <div class="card-body">
                    <div class="confirmation-message">
                        <i class="fas fa-info-circle message-icon"></i>
                        <div class="message-content">
                            <p class="message-title">
                                You are about to confirm receipt
                                <strong>{{ receipt.receipt_number }}</strong>
                            </p>
                            <p class="message-description">
                                This action will update inventory stock levels
                                and cannot be undone. Please review the details
                                below carefully before confirming.
                            </p>
                        </div>
                    </div>

                    <div class="receipt-summary">
                        <div class="summary-header">Receipt Details</div>
                        <div class="summary-grid">
                            <div class="summary-item">
                                <span class="summary-label"
                                    >Receipt Number:</span
                                >
                                <span class="summary-value">{{
                                    receipt.receipt_number
                                }}</span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Receipt Date:</span>
                                <span class="summary-value">{{
                                    formatDate(receipt.receipt_date)
                                }}</span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label"
                                    >Purchase Order:</span
                                >
                                <span class="summary-value">{{
                                    receipt.purchaseOrder?.po_number || "N/A"
                                }}</span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Vendor:</span>
                                <span class="summary-value">{{
                                    receipt.vendor?.name || "N/A"
                                }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="items-summary">
                        <div class="summary-header">Items to be Received</div>
                        <table class="summary-table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Quantity</th>
                                    <th>Unit</th>
                                    <th>Warehouse</th>
                                    <th>Location</th>
                                    <th>Batch/Lot</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="line in receipt.lines"
                                    :key="line.line_id"
                                >
                                    <td class="item-cell">
                                        <div class="item-name">
                                            {{ line.item?.name || "N/A" }}
                                        </div>
                                        <div class="item-code">
                                            {{ line.item?.item_code || "" }}
                                        </div>
                                    </td>
                                    <td>{{ line.received_quantity }}</td>
                                    <td>
                                        {{
                                            line.purchaseOrderLine
                                                ?.unitOfMeasure?.name || "N/A"
                                        }}
                                    </td>
                                    <td>{{ line.warehouse?.name || "N/A" }}</td>
                                    <td>{{ line.location?.name || "N/A" }}</td>
                                    <td>{{ line.batch_number || "N/A" }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="form-group" v-if="errorMessage">
                        <div class="error-message">{{ errorMessage }}</div>
                    </div>

                    <div class="confirmation-actions">
                        <router-link
                            :to="`/purchasing/goods-receipts/${id}`"
                            class="btn btn-secondary"
                        >
                            Cancel
                        </router-link>
                        <button
                            @click="confirmReceipt"
                            class="btn btn-success"
                            :disabled="isConfirming"
                        >
                            <i class="fas fa-check-circle"></i>
                            {{
                                isConfirming
                                    ? "Confirming..."
                                    : "Confirm Receipt"
                            }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, onMounted } from "vue";
import { useRouter } from "vue-router";

export default {
    name: "ReceiptConfirmation",
    props: {
        id: {
            type: [Number, String],
            required: true,
        },
    },
    setup(props) {
        const router = useRouter();
        const receipt = ref(null);
        const isLoading = ref(true);
        const isConfirming = ref(false);
        const errorMessage = ref("");

        // Fetch goods receipt details
        const fetchReceipt = async () => {
            isLoading.value = true;
            errorMessage.value = "";

            try {
                const response = await fetch(
                    `/api/goods-receipts/${props.id}`,
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
                    receipt.value = data.data;
                } else {
                    receipt.value = null;
                }
            } catch (error) {
                console.error("Error fetching goods receipt details:", error);
                receipt.value = null;
            } finally {
                isLoading.value = false;
            }
        };

        // Confirm goods receipt
        const confirmReceipt = async () => {
            if (!receipt.value || receipt.value.status !== "pending") {
                return;
            }

            isConfirming.value = true;
            errorMessage.value = "";

            try {
                const response = await fetch(
                    `/api/goods-receipts/${props.id}/confirm`,
                    {
                        method: "POST",
                        headers: {
                            Authorization: `Bearer ${localStorage.getItem(
                                "token"
                            )}`,
                            "Content-Type": "application/json",
                        },
                    }
                );

                const data = await response.json();

                if (response.ok) {
                    // Success - redirect to receipt detail page
                    router.push(`/purchasing/goods-receipts/${props.id}`);
                } else {
                    // Error
                    errorMessage.value =
                        data.message || "Failed to confirm goods receipt";
                }
            } catch (error) {
                console.error("Error confirming goods receipt:", error);
                errorMessage.value =
                    "An error occurred while confirming the goods receipt";
            } finally {
                isConfirming.value = false;
            }
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

        // Format status
        const formatStatus = (status) => {
            switch (status) {
                case "pending":
                    return "Pending";
                case "confirmed":
                    return "Confirmed";
                case "canceled":
                    return "Canceled";
                default:
                    return status;
            }
        };

        // Initialize
        onMounted(() => {
            fetchReceipt();
        });

        return {
            receipt,
            isLoading,
            isConfirming,
            errorMessage,
            formatDate,
            formatStatus,
            confirmReceipt,
        };
    },
};
</script>

<style scoped>
.receipt-confirmation-container {
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

.confirmation-content {
    max-width: 1000px;
    margin: 0 auto;
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

.confirmation-message {
    display: flex;
    gap: 1rem;
    padding: 1.25rem;
    background-color: #f0f9ff;
    border-radius: 0.5rem;
    margin-bottom: 1.5rem;
    border-left: 4px solid #0ea5e9;
}

.message-icon {
    font-size: 1.5rem;
    color: #0ea5e9;
    flex-shrink: 0;
}

.message-content {
    flex: 1;
}

.message-title {
    font-weight: 600;
    font-size: 1rem;
    color: #0c4a6e;
    margin: 0 0 0.5rem 0;
}

.message-description {
    color: #0369a1;
    margin: 0;
    font-size: 0.875rem;
}

.receipt-summary,
.items-summary {
    margin-bottom: 2rem;
}

.summary-header {
    font-weight: 600;
    color: var(--gray-700);
    padding-bottom: 0.5rem;
    margin-bottom: 1rem;
    border-bottom: 1px solid var(--gray-200);
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1.5rem;
}

.summary-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.summary-label {
    font-size: 0.75rem;
    color: var(--gray-500);
}

.summary-value {
    font-weight: 500;
    color: var(--gray-800);
}

.summary-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
}

.summary-table th {
    text-align: left;
    padding: 0.75rem;
    background-color: var(--gray-50);
    font-weight: 500;
    color: var(--gray-700);
    border-bottom: 1px solid var(--gray-200);
}

.summary-table td {
    padding: 0.75rem;
    border-bottom: 1px solid var(--gray-100);
}

.item-cell {
    min-width: 200px;
}

.item-name {
    font-weight: 500;
}

.item-code {
    font-size: 0.75rem;
    color: var(--gray-500);
}

.confirmation-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 2rem;
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

.btn-success {
    background-color: var(--success-color);
    color: white;
}

.btn-success:hover:not(:disabled) {
    background-color: #059669;
}

.btn-success:disabled {
    background-color: #86efac;
    cursor: not-allowed;
}

.btn-secondary {
    background-color: #e2e8f0;
    color: #1e293b;
}

.btn-secondary:hover {
    background-color: #cbd5e1;
}

.error-message {
    color: var(--danger-color);
    background-color: #fee2e2;
    padding: 1rem;
    border-radius: 0.375rem;
    margin-top: 1rem;
}

@media (max-width: 768px) {
    .summary-grid {
        grid-template-columns: 1fr;
    }
}
</style>
