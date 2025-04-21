<!-- src/views/purchasing/GoodsReceiptDetail.vue -->
<template>
    <div class="receipt-detail-container">
        <div class="page-header">
            <div class="header-left">
                <router-link to="/purchasing/goods-receipts" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to Goods Receipts
                </router-link>
                <h1>
                    {{ receipt?.receipt_number || "Goods Receipt Details" }}
                </h1>
            </div>
            <div class="header-actions">
                <div v-if="!isLoading && receipt" class="status-section">
                    <span
                        :class="[
                            'status-badge',
                            getStatusClass(receipt.status),
                        ]"
                    >
                        {{ formatStatus(receipt.status) }}
                    </span>
                </div>

                <div class="action-buttons">
                    <router-link
                        v-if="receipt && receipt.status === 'pending'"
                        :to="`/purchasing/goods-receipts/${id}/edit`"
                        class="btn btn-primary"
                    >
                        <i class="fas fa-edit"></i> Edit
                    </router-link>

                    <router-link
                        v-if="receipt && receipt.status === 'pending'"
                        :to="`/purchasing/goods-receipts/${id}/confirm`"
                        class="btn btn-success"
                    >
                        <i class="fas fa-check-circle"></i> Confirm
                    </router-link>

                    <button
                        v-if="receipt && receipt.status === 'pending'"
                        @click="confirmDelete"
                        class="btn btn-danger"
                    >
                        <i class="fas fa-trash"></i> Delete
                    </button>

                    <button class="btn btn-secondary" @click="printReceipt">
                        <i class="fas fa-print"></i> Print
                    </button>
                </div>
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

        <div v-else class="receipt-detail-content">
            <!-- Basic Information Card -->
            <div class="detail-card">
                <div class="card-header">
                    <h2 class="card-title">Receipt Information</h2>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Receipt Number</span>
                            <span class="info-value">{{
                                receipt.receipt_number
                            }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Receipt Date</span>
                            <span class="info-value">{{
                                formatDate(receipt.receipt_date)
                            }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Purchase Order</span>
                            <span class="info-value">
                                <router-link
                                    :to="`/purchasing/orders/${receipt.po_id}`"
                                >
                                    {{
                                        receipt.purchaseOrder?.po_number ||
                                        "N/A"
                                    }}
                                </router-link>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Vendor</span>
                            <span class="info-value">
                                <router-link
                                    :to="`/purchasing/vendors/${receipt.vendor?.vendor_id}`"
                                >
                                    {{ receipt.vendor?.name || "N/A" }}
                                </router-link>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Reference</span>
                            <span class="info-value">{{
                                receipt.reference || "N/A"
                            }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Received By</span>
                            <span class="info-value">{{
                                receipt.received_by || "N/A"
                            }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Receipt Items Card -->
            <div class="detail-card">
                <div class="card-header">
                    <h2 class="card-title">Received Items</h2>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Item</th>
                                    <th>Quantity</th>
                                    <th>Unit</th>
                                    <th>Warehouse</th>
                                    <th>Location</th>
                                    <th>Batch/Lot No.</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(line, index) in receipt.lines"
                                    :key="line.line_id"
                                >
                                    <td>{{ index + 1 }}</td>
                                    <td>
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
                </div>
            </div>

            <!-- Notes Section -->
            <div v-if="receipt.notes" class="detail-card">
                <div class="card-header">
                    <h2 class="card-title">Notes</h2>
                </div>
                <div class="card-body">
                    <p class="receipt-notes">{{ receipt.notes }}</p>
                </div>
            </div>
        </div>

        <!-- Confirmation Modal for Delete -->
        <ConfirmationModal
            v-if="showDeleteModal"
            :title="'Delete Goods Receipt'"
            :message="
                'Are you sure you want to delete goods receipt <strong>' +
                (receipt?.receipt_number || '') +
                '</strong>? This action cannot be undone.'
            "
            :confirm-button-text="'Delete'"
            :confirm-button-class="'btn btn-danger'"
            @confirm="deleteReceipt"
            @close="closeDeleteModal"
        />
    </div>
</template>

<script>
import { ref, onMounted } from "vue";
import { useRouter } from "vue-router";
import ConfirmationModal from "@/components/common/ConfirmationModal.vue";

export default {
    name: "GoodsReceiptDetail",
    components: {
        ConfirmationModal,
    },
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
        const showDeleteModal = ref(false);

        // Fetch goods receipt details
        const fetchReceipt = async () => {
            isLoading.value = true;
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

        // Get status CSS class
        const getStatusClass = (status) => {
            switch (status) {
                case "pending":
                    return "status-pending";
                case "confirmed":
                    return "status-completed";
                case "canceled":
                    return "status-canceled";
                default:
                    return "";
            }
        };

        // Delete confirmation
        const confirmDelete = () => {
            showDeleteModal.value = true;
        };

        // Close delete modal
        const closeDeleteModal = () => {
            showDeleteModal.value = false;
        };

        // Delete receipt
        const deleteReceipt = async () => {
            try {
                const response = await fetch(
                    `/api/goods-receipts/${props.id}`,
                    {
                        method: "DELETE",
                        headers: {
                            Authorization: `Bearer ${localStorage.getItem(
                                "token"
                            )}`,
                            "Content-Type": "application/json",
                        },
                    }
                );

                if (response.ok) {
                    router.push("/purchasing/goods-receipts");
                } else {
                    const errorData = await response.json();
                    alert(
                        errorData.message || "Failed to delete goods receipt"
                    );
                    closeDeleteModal();
                }
            } catch (error) {
                console.error("Error deleting goods receipt:", error);
                alert("An error occurred while deleting the goods receipt");
                closeDeleteModal();
            }
        };

        // Print receipt
        const printReceipt = () => {
            window.print();
        };

        // Initialize
        onMounted(() => {
            fetchReceipt();
        });

        return {
            receipt,
            isLoading,
            showDeleteModal,
            formatDate,
            formatStatus,
            getStatusClass,
            confirmDelete,
            closeDeleteModal,
            deleteReceipt,
            printReceipt,
        };
    },
};
</script>

<style scoped>
.receipt-detail-container {
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

.header-actions {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 1rem;
}

.status-section {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.action-buttons {
    display: flex;
    gap: 0.75rem;
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

.receipt-detail-content {
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

.table-responsive {
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
}

.data-table th {
    padding: 0.75rem;
    text-align: left;
    background-color: var(--gray-50);
    border-bottom: 1px solid var(--gray-200);
    font-weight: 500;
    color: var(--gray-700);
}

.data-table td {
    padding: 0.75rem;
    border-bottom: 1px solid var(--gray-100);
}

.data-table tbody tr:hover td {
    background-color: var(--gray-50);
}

.item-name {
    font-weight: 500;
}

.item-code {
    font-size: 0.75rem;
    color: var(--gray-500);
}

.receipt-notes {
    white-space: pre-line;
    color: var(--gray-700);
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

.status-pending {
    background-color: #fef3c7;
    color: #92400e;
}

.status-completed {
    background-color: #d1fae5;
    color: #065f46;
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

.btn-success:hover {
    background-color: #059669;
}

.btn-secondary {
    background-color: #e2e8f0;
    color: #1e293b;
}

.btn-secondary:hover {
    background-color: #cbd5e1;
}

.btn-danger {
    background-color: var(--danger-color);
    color: white;
}

.btn-danger:hover {
    background-color: #b91c1c;
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        gap: 1rem;
    }

    .header-actions {
        align-items: flex-start;
    }

    .info-grid {
        grid-template-columns: 1fr;
    }
}

@media print {
    .back-link,
    .header-actions {
        display: none !important;
    }

    .receipt-detail-container {
        padding: 0;
    }

    .detail-card {
        box-shadow: none;
        margin-bottom: 2rem;
    }

    .card-header {
        background-color: white;
        border-bottom: 2px solid #000;
    }

    .data-table th {
        background-color: white;
        border-bottom: 1px solid #000;
    }

    .data-table td {
        border-bottom: 1px solid #ddd;
    }
}
</style>
