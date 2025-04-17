<!-- src/views/purchasing/RFQDetail.vue -->
<template>
    <div class="rfq-detail-container">
        <div class="page-header">
            <div class="header-left">
                <router-link to="/purchasing/rfqs" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to RFQs
                </router-link>
                <h1>{{ rfq?.rfq_number || "RFQ Details" }}</h1>
            </div>
            <div class="header-actions">
                <button
                    v-if="rfq && rfq.status === 'draft'"
                    @click="editRFQ"
                    class="btn btn-primary"
                >
                    <i class="fas fa-edit"></i> Edit
                </button>
                <button
                    v-if="rfq && rfq.status === 'draft'"
                    @click="sendRFQ"
                    class="btn btn-info"
                >
                    <i class="fas fa-paper-plane"></i> Send to Vendors
                </button>
                <button
                    v-if="rfq && rfq.status === 'sent'"
                    @click="compareQuotations"
                    class="btn btn-accent"
                >
                    <i class="fas fa-balance-scale"></i> Compare Quotations
                </button>
                <button
                    v-if="
                        rfq &&
                        rfq.status !== 'canceled' &&
                        rfq.status !== 'closed'
                    "
                    @click="showStatusModal = true"
                    class="btn btn-secondary"
                >
                    <i class="fas fa-cog"></i> Change Status
                </button>
            </div>
        </div>

        <div v-if="isLoading" class="loading-container">
            <div class="loading-spinner">
                <i class="fas fa-spinner fa-spin"></i>
            </div>
            <p>Loading RFQ data...</p>
        </div>

        <div v-else-if="!rfq" class="error-container">
            <div class="error-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h2>RFQ Not Found</h2>
            <p>
                The requested RFQ could not be found or may have been deleted.
            </p>
            <router-link to="/purchasing/rfqs" class="btn btn-primary">
                Return to RFQs List
            </router-link>
        </div>

        <div v-else class="rfq-detail-content">
            <!-- RFQ Information Card -->
            <div class="detail-card">
                <div class="card-header">
                    <h2 class="card-title">RFQ Information</h2>
                    <span :class="['status-badge', getStatusClass(rfq.status)]">
                        {{
                            rfq.status.charAt(0).toUpperCase() +
                            rfq.status.slice(1)
                        }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">RFQ Number</span>
                            <span class="info-value">{{ rfq.rfq_number }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Date</span>
                            <span class="info-value">{{
                                formatDate(rfq.rfq_date)
                            }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Valid Until</span>
                            <span class="info-value">{{
                                formatDate(rfq.validity_date)
                            }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RFQ Items Card -->
            <div class="detail-card">
                <div class="card-header">
                    <h2 class="card-title">Requested Items</h2>
                </div>
                <div class="card-body">
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Description</th>
                                <th>Quantity</th>
                                <th>UOM</th>
                                <th>Required Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="line in rfq.lines" :key="line.line_id">
                                <td>
                                    {{ line.item.item_code }} -
                                    {{ line.item.name }}
                                </td>
                                <td>{{ line.item.description || "N/A" }}</td>
                                <td>{{ line.quantity }}</td>
                                <td>{{ line.unitOfMeasure?.name || "N/A" }}</td>
                                <td>{{ formatDate(line.required_date) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Vendor Quotations Card -->
            <div class="detail-card">
                <div class="card-header">
                    <h2 class="card-title">Vendor Quotations</h2>
                    <div v-if="rfq.status === 'sent'" class="card-actions">
                        <button
                            @click="showAddQuotationModal = true"
                            class="btn btn-sm btn-primary"
                        >
                            <i class="fas fa-plus"></i> Add Quotation
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div
                        v-if="
                            rfq.vendorQuotations &&
                            rfq.vendorQuotations.length > 0
                        "
                    >
                        <table class="quotations-table">
                            <thead>
                                <tr>
                                    <th>Vendor</th>
                                    <th>Quotation Date</th>
                                    <th>Valid Until</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="quotation in rfq.vendorQuotations"
                                    :key="quotation.quotation_id"
                                >
                                    <td>{{ quotation.vendor.name }}</td>
                                    <td>
                                        {{
                                            formatDate(quotation.quotation_date)
                                        }}
                                    </td>
                                    <td>
                                        {{
                                            formatDate(quotation.validity_date)
                                        }}
                                    </td>
                                    <td>
                                        <span
                                            :class="[
                                                'status-badge',
                                                getQuotationStatusClass(
                                                    quotation.status
                                                ),
                                            ]"
                                        >
                                            {{
                                                quotation.status
                                                    .charAt(0)
                                                    .toUpperCase() +
                                                quotation.status.slice(1)
                                            }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button
                                                @click="
                                                    viewQuotation(quotation)
                                                "
                                                class="action-btn view-btn"
                                                title="View Quotation"
                                            >
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button
                                                v-if="
                                                    quotation.status ===
                                                    'received'
                                                "
                                                @click="
                                                    acceptQuotation(quotation)
                                                "
                                                class="action-btn accept-btn"
                                                title="Accept Quotation"
                                            >
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button
                                                v-if="
                                                    quotation.status ===
                                                    'received'
                                                "
                                                @click="
                                                    rejectQuotation(quotation)
                                                "
                                                class="action-btn reject-btn"
                                                title="Reject Quotation"
                                            >
                                                <i class="fas fa-times"></i>
                                            </button>
                                            <button
                                                v-if="
                                                    quotation.status ===
                                                    'accepted'
                                                "
                                                @click="createPO(quotation)"
                                                class="action-btn po-btn"
                                                title="Create Purchase Order"
                                            >
                                                <i
                                                    class="fas fa-file-invoice"
                                                ></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                        <h3>No Quotations Yet</h3>
                        <p v-if="rfq.status === 'draft'">
                            Please send this RFQ to vendors first to receive
                            quotations.
                        </p>
                        <p v-else-if="rfq.status === 'sent'">
                            No vendor quotations have been received yet for this
                            RFQ.
                        </p>
                        <p v-else>
                            No vendor quotations were received for this RFQ.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Change Modal -->
        <ConfirmationModal
            v-if="showStatusModal"
            :title="'Change RFQ Status'"
            :message="'Please select the new status for this RFQ:'"
            :confirm-button-text="'Update Status'"
            :confirm-button-class="'btn btn-primary'"
            @confirm="updateStatus"
            @close="showStatusModal = false"
        >
            <div class="status-select-container">
                <select v-model="newStatus" class="status-select">
                    <option v-if="rfq?.status === 'draft'" value="sent">
                        Send
                    </option>
                    <option v-if="rfq?.status === 'sent'" value="closed">
                        Close
                    </option>
                    <option v-if="rfq?.status !== 'canceled'" value="canceled">
                        Cancel
                    </option>
                </select>
            </div>
        </ConfirmationModal>
    </div>
</template>

<script>
import { ref, onMounted } from "vue";
import { useRouter } from "vue-router";
import ConfirmationModal from "@/components/common/ConfirmationModal.vue";
import axios from "axios";

export default {
    name: "RFQDetail",
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
        const rfq = ref(null);
        const isLoading = ref(true);
        const showStatusModal = ref(false);
        const showAddQuotationModal = ref(false);
        const newStatus = ref("");

        // Fetch RFQ details
        const fetchRFQDetails = async () => {
            isLoading.value = true;
            try {
                const response = await axios.get(
                    `/api/request-for-quotations/${props.id}`
                );
                if (response.data && response.data.data) {
                    rfq.value = response.data.data;
                }
            } catch (error) {
                console.error("Error fetching RFQ details:", error);
                rfq.value = null;
            } finally {
                isLoading.value = false;
            }
        };

        // Format date strings
        const formatDate = (dateString) => {
            if (!dateString) return "N/A";
            const date = new Date(dateString);
            return date.toLocaleDateString();
        };

        // Get CSS class for status badge
        const getStatusClass = (status) => {
            switch (status) {
                case "draft":
                    return "status-draft";
                case "sent":
                    return "status-sent";
                case "closed":
                    return "status-closed";
                case "canceled":
                    return "status-canceled";
                default:
                    return "status-draft";
            }
        };

        // Get CSS class for quotation status badge
        const getQuotationStatusClass = (status) => {
            switch (status) {
                case "received":
                    return "status-received";
                case "accepted":
                    return "status-accepted";
                case "rejected":
                    return "status-rejected";
                default:
                    return "status-received";
            }
        };

        // Navigation actions
        const editRFQ = () => {
            router.push(`/purchasing/rfqs/${props.id}/edit`);
        };

        const sendRFQ = () => {
            router.push(`/purchasing/rfqs/${props.id}/send`);
        };

        const compareQuotations = () => {
            router.push(`/purchasing/rfqs/${props.id}/compare`);
        };

        // Update RFQ status
        const updateStatus = async () => {
            try {
                await axios.patch(
                    `/api/request-for-quotations/${props.id}/status`,
                    {
                        status: newStatus.value,
                    }
                );
                showStatusModal.value = false;
                fetchRFQDetails();
            } catch (error) {
                console.error("Error updating RFQ status:", error);
                alert("Failed to update status. Please try again.");
            }
        };

        // Quotation actions
        const viewQuotation = (quotation) => {
            router.push(`/purchasing/quotations/${quotation.quotation_id}`);
        };

        const acceptQuotation = async (quotation) => {
            try {
                await axios.patch(
                    `/api/vendor-quotations/${quotation.quotation_id}/status`,
                    {
                        status: "accepted",
                    }
                );
                fetchRFQDetails();
            } catch (error) {
                console.error("Error accepting quotation:", error);
                alert("Failed to accept quotation. Please try again.");
            }
        };

        const rejectQuotation = async (quotation) => {
            try {
                await axios.patch(
                    `/api/vendor-quotations/${quotation.quotation_id}/status`,
                    {
                        status: "rejected",
                    }
                );
                fetchRFQDetails();
            } catch (error) {
                console.error("Error rejecting quotation:", error);
                alert("Failed to reject quotation. Please try again.");
            }
        };

        const createPO = (quotation) => {
            router.push(
                `/purchasing/orders/create-from-quotation/${quotation.quotation_id}`
            );
        };

        // Initialize data
        onMounted(() => {
            fetchRFQDetails();
        });

        return {
            rfq,
            isLoading,
            showStatusModal,
            showAddQuotationModal,
            newStatus,
            formatDate,
            getStatusClass,
            getQuotationStatusClass,
            editRFQ,
            sendRFQ,
            compareQuotations,
            updateStatus,
            viewQuotation,
            acceptQuotation,
            rejectQuotation,
            createPO,
        };
    },
};
</script>

<style scoped>
.rfq-detail-container {
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
    gap: 0.75rem;
}

.loading-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 2rem;
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.loading-spinner {
    font-size: 2rem;
    color: var(--primary-color);
    margin-bottom: 1rem;
}

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

.error-icon {
    font-size: 3rem;
    color: var(--danger-color);
    margin-bottom: 1rem;
}

.rfq-detail-content {
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

.card-actions {
    display: flex;
    gap: 0.5rem;
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

.items-table,
.quotations-table {
    width: 100%;
    border-collapse: collapse;
}

.items-table th,
.quotations-table th {
    text-align: left;
    padding: 0.75rem 1rem;
    background-color: var(--gray-50);
    font-weight: 500;
    font-size: 0.875rem;
    color: var(--gray-700);
    border-bottom: 1px solid var(--gray-200);
}

.items-table td,
.quotations-table td {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--gray-100);
    font-size: 0.875rem;
}

.items-table tr:last-child td,
.quotations-table tr:last-child td {
    border-bottom: none;
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

.status-sent {
    background-color: #dbeafe;
    color: #1e40af;
}

.status-closed {
    background-color: #d1fae5;
    color: #065f46;
}

.status-canceled {
    background-color: #fee2e2;
    color: #b91c1c;
}

.status-received {
    background-color: #e0f2fe;
    color: #0369a1;
}

.status-accepted {
    background-color: #d1fae5;
    color: #065f46;
}

.status-rejected {
    background-color: #fef3c7;
    color: #92400e;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
}

.action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border-radius: 0.375rem;
    border: none;
    background: none;
    cursor: pointer;
    transition: background-color 0.2s;
}

.view-btn {
    color: var(--primary-color);
}

.view-btn:hover {
    background-color: var(--primary-bg);
}

.accept-btn {
    color: #22c55e;
}

.accept-btn:hover {
    background-color: #dcfce7;
}

.reject-btn {
    color: #ef4444;
}

.reject-btn:hover {
    background-color: #fee2e2;
}

.po-btn {
    color: #6366f1;
}

.po-btn:hover {
    background-color: #eef2ff;
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem;
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

.btn-primary:hover {
    background-color: var(--primary-dark);
}

.btn-secondary {
    background-color: var(--gray-200);
    color: var(--gray-700);
}

.btn-secondary:hover {
    background-color: var(--gray-300);
}

.btn-info {
    background-color: #3b82f6;
    color: white;
}

.btn-info:hover {
    background-color: #2563eb;
}

.btn-accent {
    background-color: #8b5cf6;
    color: white;
}

.btn-accent:hover {
    background-color: #7c3aed;
}

.status-select-container {
    margin-top: 1rem;
}

.status-select {
    width: 100%;
    padding: 0.625rem;
    border: 1px solid var(--gray-200);
    border-radius: 0.375rem;
    font-size: 0.875rem;
    background-color: white;
}

@media (max-width: 768px) {
    .header-actions {
        flex-wrap: wrap;
    }

    .info-grid {
        grid-template-columns: 1fr;
    }

    .items-table,
    .quotations-table {
        display: block;
        overflow-x: auto;
    }
}
</style>
