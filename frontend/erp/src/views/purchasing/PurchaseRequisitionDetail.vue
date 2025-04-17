<!-- src/views/purchasing/PurchaseRequisitionDetail.vue -->
<template>
    <div class="pr-detail-container">
        <div class="page-header">
            <div class="header-left">
                <router-link to="/purchasing/requisitions" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to Requisitions
                </router-link>
                <h1>
                    Purchase Requisition: {{ pr?.pr_number || "Loading..." }}
                </h1>
            </div>
            <div class="header-actions">
                <button
                    v-if="
                        pr && (pr.status === 'draft' || pr.status === 'pending')
                    "
                    @click="editPR"
                    class="btn btn-primary"
                >
                    <i class="fas fa-edit"></i> Edit
                </button>
                <button
                    v-if="pr && pr.status === 'pending'"
                    @click="approvePR"
                    class="btn btn-success"
                >
                    <i class="fas fa-check-circle"></i> Approve/Reject
                </button>
                <button
                    v-if="pr && pr.status === 'approved'"
                    @click="convertToRFQ"
                    class="btn btn-info"
                >
                    <i class="fas fa-exchange-alt"></i> Convert to RFQ
                </button>
                <button
                    v-if="pr && pr.status === 'draft'"
                    @click="confirmDelete"
                    class="btn btn-danger"
                >
                    <i class="fas fa-trash"></i> Delete
                </button>
            </div>
        </div>

        <div v-if="isLoading" class="loading-container">
            <div class="loading-spinner">
                <i class="fas fa-spinner fa-spin"></i>
            </div>
            <p>Loading purchase requisition data...</p>
        </div>

        <div v-else-if="!pr" class="error-container">
            <div class="error-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h2>Purchase Requisition Not Found</h2>
            <p>
                The requested purchase requisition could not be found or may
                have been deleted.
            </p>
            <router-link to="/purchasing/requisitions" class="btn btn-primary">
                Return to Purchase Requisitions
            </router-link>
        </div>

        <div v-else class="pr-detail-content">
            <!-- Header Information Card -->
            <div class="detail-card">
                <div class="card-header">
                    <h2 class="card-title">Requisition Information</h2>
                    <span :class="['status-badge', getStatusClass(pr.status)]">
                        {{ getStatusLabel(pr.status) }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">PR Number</span>
                            <span class="info-value">{{ pr.pr_number }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Date</span>
                            <span class="info-value">{{
                                formatDate(pr.pr_date)
                            }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Requester</span>
                            <span class="info-value">{{
                                pr.requester ? pr.requester.name : "N/A"
                            }}</span>
                        </div>
                        <div class="info-item info-item-full">
                            <span class="info-label">Notes</span>
                            <span class="info-value">{{
                                pr.notes || "No notes provided"
                            }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Requisition Items Card -->
            <div class="detail-card">
                <div class="card-header">
                    <h2 class="card-title">Requisition Items</h2>
                </div>
                <div class="card-body">
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Quantity</th>
                                <th>Unit</th>
                                <th>Required Date</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="line in pr.lines" :key="line.line_id">
                                <td>
                                    <div class="item-info">
                                        <span class="item-name">{{
                                            line.item ? line.item.name : "N/A"
                                        }}</span>
                                        <span class="item-code">{{
                                            line.item ? line.item.item_code : ""
                                        }}</span>
                                    </div>
                                </td>
                                <td>{{ line.quantity }}</td>
                                <td>
                                    {{
                                        line.unitOfMeasure
                                            ? line.unitOfMeasure.name
                                            : "N/A"
                                    }}
                                </td>
                                <td>{{ formatDate(line.required_date) }}</td>
                                <td>{{ line.notes || "No notes" }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Approval History Card -->
            <div v-if="pr.status !== 'draft'" class="detail-card">
                <div class="card-header">
                    <h2 class="card-title">Approval History</h2>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="timeline-item">
                            <div
                                class="timeline-marker"
                                :class="{
                                    approved: pr.status === 'approved',
                                    rejected: pr.status === 'rejected',
                                }"
                            >
                                <i
                                    :class="
                                        pr.status === 'approved'
                                            ? 'fas fa-check'
                                            : pr.status === 'rejected'
                                            ? 'fas fa-times'
                                            : 'fas fa-clock'
                                    "
                                ></i>
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-title">
                                    {{
                                        pr.status === "approved"
                                            ? "Approved"
                                            : pr.status === "rejected"
                                            ? "Rejected"
                                            : "Pending Approval"
                                    }}
                                </div>
                                <div class="timeline-date">
                                    {{
                                        pr.status !== "pending"
                                            ? "On " + formatDate(pr.updated_at)
                                            : "Awaiting approval"
                                    }}
                                </div>
                                <div
                                    v-if="pr.approval_notes"
                                    class="timeline-notes"
                                >
                                    Notes: {{ pr.approval_notes }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Confirmation Modal for Delete -->
        <ConfirmationModal
            v-if="showDeleteModal"
            :title="'Delete Purchase Requisition'"
            :message="
                'Are you sure you want to delete purchase requisition <strong>' +
                (pr?.pr_number || '') +
                '</strong>? This action cannot be undone.'
            "
            :confirm-button-text="'Delete'"
            :confirm-button-class="'btn btn-danger'"
            @confirm="deletePR"
            @close="closeDeleteModal"
        />
    </div>
</template>

<script>
import { ref, onMounted } from "vue";
import { useRouter } from "vue-router";
import PurchaseRequisitionService from "@/services/PurchaseRequisitionService";
import ConfirmationModal from "@/components/common/ConfirmationModal.vue";

export default {
    name: "PurchaseRequisitionDetail",
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
        const pr = ref(null);
        const isLoading = ref(true);
        const showDeleteModal = ref(false);

        // Fetch PR details
        const fetchPRDetails = async () => {
            isLoading.value = true;
            try {
                const response =
                    await PurchaseRequisitionService.getPurchaseRequisitionById(
                        props.id
                    );
                pr.value = response.data.data || null;
            } catch (error) {
                console.error(
                    "Error fetching purchase requisition details:",
                    error
                );
                pr.value = null;
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

        // Get CSS class for status badge
        const getStatusClass = (status) => {
            switch (status) {
                case "draft":
                    return "status-draft";
                case "pending":
                    return "status-pending";
                case "approved":
                    return "status-approved";
                case "rejected":
                    return "status-rejected";
                case "canceled":
                    return "status-canceled";
                default:
                    return "status-draft";
            }
        };

        // Get readable status label
        const getStatusLabel = (status) => {
            switch (status) {
                case "draft":
                    return "Draft";
                case "pending":
                    return "Pending Approval";
                case "approved":
                    return "Approved";
                case "rejected":
                    return "Rejected";
                case "canceled":
                    return "Canceled";
                default:
                    return status;
            }
        };

        // Navigation actions
        const editPR = () => {
            router.push(`/purchasing/requisitions/${props.id}/edit`);
        };

        const approvePR = () => {
            router.push(`/purchasing/requisitions/${props.id}/approve`);
        };

        const convertToRFQ = () => {
            router.push(`/purchasing/requisitions/${props.id}/convert`);
        };

        const confirmDelete = () => {
            showDeleteModal.value = true;
        };

        const closeDeleteModal = () => {
            showDeleteModal.value = false;
        };

        const deletePR = async () => {
            try {
                await PurchaseRequisitionService.deletePurchaseRequisition(
                    props.id
                );
                router.push("/purchasing/requisitions");
            } catch (error) {
                if (error.response && error.response.status === 400) {
                    // Show error message if PR cannot be deleted
                    alert(
                        error.response.data.message ||
                            "This purchase requisition cannot be deleted in its current status."
                    );
                } else {
                    console.error(
                        "Error deleting purchase requisition:",
                        error
                    );
                }
                closeDeleteModal();
            }
        };

        // Initialize
        onMounted(() => {
            fetchPRDetails();
        });

        return {
            pr,
            isLoading,
            showDeleteModal,
            formatDate,
            getStatusClass,
            getStatusLabel,
            editPR,
            approvePR,
            convertToRFQ,
            confirmDelete,
            closeDeleteModal,
            deletePR,
        };
    },
};
</script>

<style scoped>
.pr-detail-container {
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

.pr-detail-content {
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
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.info-item-full {
    grid-column: 1 / -1;
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

.items-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
}

.items-table th {
    text-align: left;
    padding: 0.75rem 1rem;
    background-color: var(--gray-50);
    border-bottom: 1px solid var(--gray-200);
    font-weight: 500;
    color: var(--gray-600);
}

.items-table td {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--gray-100);
}

.items-table tr:last-child td {
    border-bottom: none;
}

.item-info {
    display: flex;
    flex-direction: column;
}

.item-name {
    font-weight: 500;
}

.item-code {
    font-size: 0.75rem;
    color: var(--gray-500);
}

.timeline {
    margin-top: 0.5rem;
}

.timeline-item {
    display: flex;
    margin-bottom: 1.5rem;
}

.timeline-item:last-child {
    margin-bottom: 0;
}

.timeline-marker {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    background-color: var(--gray-100);
    border-radius: 50%;
    margin-right: 1rem;
    flex-shrink: 0;
}

.timeline-marker.approved {
    background-color: var(--success-color);
    color: white;
}

.timeline-marker.rejected {
    background-color: var(--danger-color);
    color: white;
}

.timeline-content {
    padding-top: 0.25rem;
}

.timeline-title {
    font-weight: 500;
    font-size: 1rem;
    margin-bottom: 0.25rem;
}

.timeline-date {
    font-size: 0.875rem;
    color: var(--gray-500);
    margin-bottom: 0.25rem;
}

.timeline-notes {
    font-size: 0.875rem;
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

.status-rejected {
    background-color: #fee2e2;
    color: #b91c1c;
}

.status-canceled {
    background-color: #e2e8f0;
    color: #475569;
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

.btn-danger {
    background-color: var(--danger-color);
    color: white;
}

.btn-danger:hover {
    background-color: #b91c1c;
}

.btn-success {
    background-color: var(--success-color);
    color: white;
}

.btn-success:hover {
    background-color: #15803d;
}

.btn-info {
    background-color: var(--info-color);
    color: white;
}

.btn-info:hover {
    background-color: #0369a1;
}

@media (max-width: 768px) {
    .header-actions {
        flex-wrap: wrap;
    }

    .info-grid {
        grid-template-columns: 1fr;
    }

    .items-table {
        display: block;
        overflow-x: auto;
    }
}
</style>
