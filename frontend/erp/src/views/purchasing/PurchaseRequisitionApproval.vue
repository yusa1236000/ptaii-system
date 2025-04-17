<!-- src/views/purchasing/PurchaseRequisitionApproval.vue -->
<template>
    <div class="pr-approval-container">
        <div class="page-header">
            <div class="header-left">
                <router-link
                    :to="`/purchasing/requisitions/${id}`"
                    class="back-link"
                >
                    <i class="fas fa-arrow-left"></i> Back to Requisition
                </router-link>
                <h1>Approve Purchase Requisition</h1>
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

        <div v-else-if="pr.status !== 'pending'" class="error-container">
            <div class="error-icon">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <h2>Cannot Approve This Requisition</h2>
            <p>
                This purchase requisition is in
                {{ getStatusLabel(pr.status) }} status and cannot be approved or
                rejected.
            </p>
            <router-link
                :to="`/purchasing/requisitions/${id}`"
                class="btn btn-primary"
            >
                Return to Requisition Details
            </router-link>
        </div>

        <div v-else class="approval-content">
            <!-- PR Summary Card -->
            <div class="summary-card">
                <div class="card-header">
                    <h2 class="card-title">Requisition Summary</h2>
                    <span class="status-badge status-pending">{{
                        getStatusLabel(pr.status)
                    }}</span>
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

            <!-- Items Summary Card -->
            <div class="summary-card">
                <div class="card-header">
                    <h2 class="card-title">Requested Items</h2>
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

            <!-- Approval Form -->
            <div class="approval-card">
                <div class="card-header">
                    <h2 class="card-title">Approval Decision</h2>
                </div>
                <div class="card-body">
                    <form @submit.prevent="submitDecision">
                        <div class="decision-options">
                            <div class="decision-option">
                                <input
                                    type="radio"
                                    id="approve"
                                    value="approved"
                                    v-model="decision.status"
                                    required
                                />
                                <label for="approve" class="option-label">
                                    <div class="option-icon approve-icon">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <div class="option-text">
                                        <span class="option-title"
                                            >Approve</span
                                        >
                                        <span class="option-description"
                                            >Approve this purchase
                                            requisition</span
                                        >
                                    </div>
                                </label>
                            </div>

                            <div class="decision-option">
                                <input
                                    type="radio"
                                    id="reject"
                                    value="rejected"
                                    v-model="decision.status"
                                />
                                <label for="reject" class="option-label">
                                    <div class="option-icon reject-icon">
                                        <i class="fas fa-times-circle"></i>
                                    </div>
                                    <div class="option-text">
                                        <span class="option-title">Reject</span>
                                        <span class="option-description"
                                            >Reject this purchase
                                            requisition</span
                                        >
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="approval-notes">Notes</label>
                            <textarea
                                id="approval-notes"
                                v-model="decision.notes"
                                rows="3"
                                placeholder="Add any comments or reasoning for your decision"
                            ></textarea>
                        </div>

                        <div class="form-actions">
                            <router-link
                                :to="`/purchasing/requisitions/${id}`"
                                class="btn btn-secondary"
                            >
                                Cancel
                            </router-link>
                            <button
                                type="submit"
                                class="btn btn-primary"
                                :disabled="isSubmitting"
                            >
                                {{
                                    isSubmitting
                                        ? "Processing..."
                                        : "Submit Decision"
                                }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, reactive, onMounted } from "vue";
import { useRouter } from "vue-router";
import PurchaseRequisitionService from "@/services/PurchaseRequisitionService";

export default {
    name: "PurchaseRequisitionApproval",
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
        const isSubmitting = ref(false);

        const decision = reactive({
            status: "approved", // Default to approve
            notes: "",
        });

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

        // Submit approval/rejection decision
        const submitDecision = async () => {
            isSubmitting.value = true;

            try {
                await PurchaseRequisitionService.updatePurchaseRequisitionStatus(
                    props.id,
                    decision.status
                );

                // If there are notes, update the PR with approval notes
                if (decision.notes) {
                    await PurchaseRequisitionService.updatePurchaseRequisition(
                        props.id,
                        {
                            approval_notes: decision.notes,
                        }
                    );
                }

                // Redirect back to PR detail page
                router.push(`/purchasing/requisitions/${props.id}`);
            } catch (error) {
                console.error("Error processing approval decision:", error);
                alert("Failed to process your decision. Please try again.");
                isSubmitting.value = false;
            }
        };

        // Initialize
        onMounted(() => {
            fetchPRDetails();
        });

        return {
            pr,
            isLoading,
            isSubmitting,
            decision,
            formatDate,
            getStatusLabel,
            submitDecision,
        };
    },
};
</script>

<style scoped>
.pr-approval-container {
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

.approval-content {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    max-width: 1000px;
    margin: 0 auto;
}

.summary-card,
.approval-card {
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

.decision-options {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.decision-option {
    position: relative;
}

.decision-option input[type="radio"] {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.option-label {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.5rem;
    border: 2px solid var(--gray-200);
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.2s;
}

.decision-option input[type="radio"]:checked + .option-label {
    border-color: var(--primary-color);
    background-color: var(--primary-bg);
}

.decision-option input[type="radio"][value="rejected"]:checked + .option-label {
    border-color: var(--danger-color);
    background-color: var(--danger-bg);
}

.option-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 3rem;
    height: 3rem;
    border-radius: 50%;
    font-size: 1.5rem;
}

.approve-icon {
    background-color: var(--success-bg);
    color: var(--success-color);
}

.reject-icon {
    background-color: var(--danger-bg);
    color: var(--danger-color);
}

.option-text {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.option-title {
    font-weight: 600;
    font-size: 1.125rem;
}

.option-description {
    font-size: 0.875rem;
    color: var(--gray-500);
}

.form-group {
    margin-bottom: 1.5rem;
}

label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: var(--gray-700);
    font-size: 0.875rem;
}

textarea {
    width: 100%;
    padding: 0.625rem;
    border: 1px solid var(--gray-200);
    border-radius: 0.375rem;
    font-size: 0.875rem;
    background-color: white;
    transition: border-color 0.2s;
    resize: vertical;
    min-height: 100px;
}

textarea:focus {
    border-color: var(--primary-color);
    outline: none;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
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
    text-decoration: none;
}

.btn-primary {
    background-color: var(--primary-color);
    color: white;
}

.btn-primary:hover:not(:disabled) {
    background-color: var(--primary-dark);
}

.btn-primary:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.btn-secondary {
    background-color: var(--gray-100);
    color: var(--gray-700);
}

.btn-secondary:hover {
    background-color: var(--gray-200);
}

@media (max-width: 768px) {
    .info-grid {
        grid-template-columns: 1fr;
    }

    .decision-options {
        grid-template-columns: 1fr;
    }

    .items-table {
        display: block;
        overflow-x: auto;
    }
}
</style>
