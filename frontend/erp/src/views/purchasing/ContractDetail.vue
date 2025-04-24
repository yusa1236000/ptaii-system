<!-- src/views/purchasing/ContractDetail.vue -->
<template>
    <div class="contract-detail-container">
        <div class="page-header">
            <div class="header-left">
                <router-link to="/purchasing/contracts" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to Contracts
                </router-link>
                <h1>{{ contract?.contract_number || "Contract Details" }}</h1>
            </div>
            <div class="header-actions">
                <div v-if="!isLoading && contract" class="status-section">
                    <span
                        :class="[
                            'status-badge',
                            getStatusClass(contract.status, contract),
                        ]"
                    >
                        {{ formatStatus(contract.status, contract) }}
                    </span>
                </div>

                <div class="action-buttons">
                    <router-link
                        v-if="contract && contract.status === 'draft'"
                        :to="`/purchasing/contracts/${id}/edit`"
                        class="btn btn-primary"
                    >
                        <i class="fas fa-edit"></i> Edit
                    </router-link>

                    <button
                        v-if="contract && contract.status === 'draft'"
                        @click="confirmActivate"
                        class="btn btn-success"
                    >
                        <i class="fas fa-check-circle"></i> Activate
                    </button>

                    <button
                        v-if="contract && contract.status === 'active'"
                        @click="confirmTerminate"
                        class="btn btn-danger"
                    >
                        <i class="fas fa-ban"></i> Terminate
                    </button>

                    <button
                        v-if="contract && contract.status === 'draft'"
                        @click="confirmDelete"
                        class="btn btn-danger"
                    >
                        <i class="fas fa-trash"></i> Delete
                    </button>

                    <button class="btn btn-secondary" @click="printContract">
                        <i class="fas fa-print"></i> Print
                    </button>
                </div>
            </div>
        </div>

        <div v-if="isLoading" class="loading-container">
            <div class="loading-spinner">
                <i class="fas fa-spinner fa-spin"></i>
            </div>
            <p>Loading contract data...</p>
        </div>

        <div v-else-if="!contract" class="error-container">
            <div class="error-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h2>Contract Not Found</h2>
            <p>
                The requested contract could not be found or may have been
                deleted.
            </p>
            <router-link to="/purchasing/contracts" class="btn btn-primary">
                Return to Contracts List
            </router-link>
        </div>

        <div v-else class="contract-detail-content">
            <!-- Basic Information Card -->
            <div class="detail-card">
                <div class="card-header">
                    <h2 class="card-title">Contract Information</h2>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Contract Number</span>
                            <span class="info-value">{{
                                contract.contract_number
                            }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Contract Type</span>
                            <span class="info-value">{{
                                formatContractType(contract.contract_type)
                            }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Vendor</span>
                            <span class="info-value">
                                <router-link
                                    :to="`/purchasing/vendors/${contract.vendor.vendor_id}`"
                                >
                                    {{ contract.vendor.name }}
                                </router-link>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Status</span>
                            <span
                                :class="[
                                    'info-value status-badge',
                                    getStatusClass(contract.status, contract),
                                ]"
                            >
                                {{ formatStatus(contract.status, contract) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contract Period Card -->
            <div class="detail-card">
                <div class="card-header">
                    <h2 class="card-title">Contract Period</h2>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Start Date</span>
                            <span class="info-value">{{
                                formatDate(contract.start_date)
                            }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">End Date</span>
                            <span class="info-value">{{
                                formatDate(contract.end_date)
                            }}</span>
                        </div>
                        <div
                            class="info-item"
                            v-if="getDaysRemaining(contract.end_date) !== null"
                        >
                            <span class="info-label">Time Remaining</span>
                            <span
                                :class="[
                                    'info-value',
                                    getRemainingDaysClass(contract.end_date),
                                ]"
                            >
                                {{ getDaysRemaining(contract.end_date) }}
                            </span>
                        </div>
                        <div
                            class="info-item"
                            v-if="contract.status === 'terminated'"
                        >
                            <span class="info-label">Termination Date</span>
                            <span class="info-value">{{
                                formatDate(contract.termination_date)
                            }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contract Details Card -->
            <div class="detail-card">
                <div class="card-header">
                    <h2 class="card-title">Contract Details</h2>
                </div>
                <div class="card-body">
                    <div class="info-section">
                        <h3 class="section-subtitle">Description</h3>
                        <p class="info-text">
                            {{
                                contract.description ||
                                "No description provided."
                            }}
                        </p>
                    </div>

                    <div class="info-grid mt-4">
                        <div class="info-item">
                            <span class="info-label">Total Value</span>
                            <span class="info-value">{{
                                contract.total_value
                                    ? formatCurrency(contract.total_value)
                                    : "Not specified"
                            }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Payment Terms</span>
                            <span class="info-value">{{
                                contract.payment_terms || "Not specified"
                            }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Renewal Terms</span>
                            <span class="info-value">{{
                                contract.renewal_terms || "Not specified"
                            }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contract Timeline Card -->
            <div class="detail-card">
                <div class="card-header">
                    <h2 class="card-title">Contract Timeline</h2>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <h3 class="timeline-title">Created</h3>
                                <p class="timeline-date">
                                    {{ formatDate(contract.created_at) }}
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="contract.status !== 'draft'"
                            class="timeline-item"
                        >
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <h3 class="timeline-title">Activated</h3>
                                <p class="timeline-date">
                                    {{
                                        formatDate(contract.activation_date) ||
                                        formatDate(contract.updated_at)
                                    }}
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="contract.status === 'terminated'"
                            class="timeline-item"
                        >
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <h3 class="timeline-title">Terminated</h3>
                                <p class="timeline-date">
                                    {{ formatDate(contract.termination_date) }}
                                </p>
                                <p
                                    class="timeline-note"
                                    v-if="contract.termination_reason"
                                >
                                    Reason: {{ contract.termination_reason }}
                                </p>
                            </div>
                        </div>

                        <div v-if="isExpired(contract)" class="timeline-item">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <h3 class="timeline-title">Expired</h3>
                                <p class="timeline-date">
                                    {{ formatDate(contract.end_date) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Confirmation Modal for Delete -->
        <ConfirmationModal
            v-if="showDeleteModal"
            :title="'Delete Contract'"
            :message="
                'Are you sure you want to delete contract <strong>' +
                (contract?.contract_number || '') +
                '</strong>? This action cannot be undone.'
            "
            :confirm-button-text="'Delete'"
            :confirm-button-class="'btn btn-danger'"
            @confirm="deleteContract"
            @close="closeDeleteModal"
        />

        <!-- Confirmation Modal for Activate -->
        <ConfirmationModal
            v-if="showActivateModal"
            :title="'Activate Contract'"
            :message="
                'Are you sure you want to activate contract <strong>' +
                (contract?.contract_number || '') +
                '</strong>? This will make the contract active.'
            "
            :confirm-button-text="'Activate'"
            :confirm-button-class="'btn btn-success'"
            @confirm="activateContract"
            @close="closeActivateModal"
        />

        <!-- Confirmation Modal for Terminate -->
        <div v-if="showTerminateModal" class="modal">
            <div class="modal-backdrop" @click="closeTerminateModal"></div>
            <div class="modal-content modal-sm">
                <div class="modal-header">
                    <h2>Terminate Contract</h2>
                    <button class="close-btn" @click="closeTerminateModal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <p>
                        Are you sure you want to terminate contract
                        <strong>{{ contract?.contract_number }}</strong
                        >?
                    </p>
                    <div class="form-group">
                        <label for="termination_date">Termination Date*</label>
                        <input
                            type="date"
                            id="termination_date"
                            v-model="terminationDate"
                            class="form-control"
                            :min="getCurrentDate()"
                            required
                        />
                        <div v-if="terminationDateError" class="error-message">
                            {{ terminationDateError }}
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="termination_reason"
                            >Termination Reason</label
                        >
                        <textarea
                            id="termination_reason"
                            v-model="terminationReason"
                            class="form-control"
                            rows="3"
                            placeholder="Reason for termination"
                        ></textarea>
                    </div>
                    <div class="form-actions">
                        <button
                            type="button"
                            class="btn btn-secondary"
                            @click="closeTerminateModal"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            class="btn btn-danger"
                            @click="terminateContract"
                        >
                            Terminate
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
import VendorContractService from "@/services/VendorContractService";
import ConfirmationModal from "@/components/common/ConfirmationModal.vue";

export default {
    name: "ContractDetail",
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
        const contract = ref(null);
        const isLoading = ref(true);
        const showDeleteModal = ref(false);
        const showActivateModal = ref(false);
        const showTerminateModal = ref(false);
        const terminationDate = ref("");
        const terminationReason = ref("");
        const terminationDateError = ref("");

        // Fetch contract details
        const fetchContractDetails = async () => {
            isLoading.value = true;
            try {
                const response = await VendorContractService.getContractById(
                    props.id
                );
                contract.value = response.data.data || null;
            } catch (error) {
                console.error("Error fetching contract details:", error);
                contract.value = null;
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

        // Format currency
        const formatCurrency = (amount) => {
            if (amount === null || amount === undefined) return "N/A";
            return new Intl.NumberFormat("en-US", {
                style: "currency",
                currency: "USD",
            }).format(amount);
        };

        // Format contract type
        const formatContractType = (type) => {
            switch (type) {
                case "purchase":
                    return "Purchase Agreement";
                case "service":
                    return "Service Agreement";
                case "rental":
                    return "Rental/Lease Agreement";
                case "maintenance":
                    return "Maintenance Agreement";
                default:
                    return type;
            }
        };

        // Format status text
        const formatStatus = (status, item) => {
            // Check if contract is expired based on end_date
            if (status === "active" && item.end_date) {
                const today = new Date();
                const endDate = new Date(item.end_date);
                if (endDate < today) {
                    return "Expired";
                }
            }

            switch (status) {
                case "draft":
                    return "Draft";
                case "active":
                    return "Active";
                case "terminated":
                    return "Terminated";
                default:
                    return status;
            }
        };

        // Get status CSS class
        const getStatusClass = (status, item) => {
            // Check if contract is expired
            if (status === "active" && item.end_date) {
                const today = new Date();
                const endDate = new Date(item.end_date);
                if (endDate < today) {
                    return "status-expired";
                }
            }

            switch (status) {
                case "draft":
                    return "status-draft";
                case "active":
                    return "status-active";
                case "terminated":
                    return "status-terminated";
                default:
                    return "status-draft";
            }
        };

        // Check if contract is expired
        const isExpired = (item) => {
            if (!item.end_date) return false;

            const today = new Date();
            const endDate = new Date(item.end_date);
            return endDate < today && item.status === "active";
        };

        // Get current date for input min value
        const getCurrentDate = () => {
            const now = new Date();
            return now.toISOString().slice(0, 10);
        };

        // Get days remaining until end date
        const getDaysRemaining = (endDateString) => {
            if (!endDateString) return null;

            const today = new Date();
            const endDate = new Date(endDateString);

            // If already expired
            if (endDate < today) {
                return "Expired";
            }

            // Calculate days remaining
            const diffTime = Math.abs(endDate - today);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

            if (diffDays === 0) {
                return "Expires today";
            } else if (diffDays === 1) {
                return "1 day remaining";
            } else {
                return `${diffDays} days remaining`;
            }
        };

        // Get CSS class for remaining days
        const getRemainingDaysClass = (endDateString) => {
            if (!endDateString) return "";

            const today = new Date();
            const endDate = new Date(endDateString);

            if (endDate < today) {
                return "text-danger";
            }

            const diffTime = Math.abs(endDate - today);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

            if (diffDays <= 30) {
                return "text-warning";
            } else {
                return "text-success";
            }
        };

        // Delete Contract
        const confirmDelete = () => {
            showDeleteModal.value = true;
        };

        const closeDeleteModal = () => {
            showDeleteModal.value = false;
        };

        const deleteContract = async () => {
            try {
                await VendorContractService.deleteContract(props.id);
                router.push("/purchasing/contracts");
            } catch (error) {
                if (error.response && error.response.status === 400) {
                    alert(
                        error.response.data.message ||
                            "This contract cannot be deleted."
                    );
                } else {
                    console.error("Error deleting contract:", error);
                }
                closeDeleteModal();
            }
        };

        // Activate Contract
        const confirmActivate = () => {
            showActivateModal.value = true;
        };

        const closeActivateModal = () => {
            showActivateModal.value = false;
        };

        const activateContract = async () => {
            try {
                await VendorContractService.activateContract(props.id);
                fetchContractDetails();
                closeActivateModal();
            } catch (error) {
                if (
                    error.response &&
                    error.response.data &&
                    error.response.data.message
                ) {
                    alert(error.response.data.message);
                } else {
                    console.error("Error activating contract:", error);
                }
                closeActivateModal();
            }
        };

        // Terminate Contract
        const confirmTerminate = () => {
            terminationDate.value = getCurrentDate();
            terminationReason.value = "";
            terminationDateError.value = "";
            showTerminateModal.value = true;
        };

        const closeTerminateModal = () => {
            showTerminateModal.value = false;
        };

        const terminateContract = async () => {
            // Validate termination date
            if (!terminationDate.value) {
                terminationDateError.value = "Termination date is required";
                return;
            }

            try {
                await VendorContractService.terminateContract(props.id, {
                    termination_date: terminationDate.value,
                    termination_reason: terminationReason.value,
                });
                fetchContractDetails();
                closeTerminateModal();
            } catch (error) {
                if (
                    error.response &&
                    error.response.data &&
                    error.response.data.message
                ) {
                    terminationDateError.value = error.response.data.message;
                } else {
                    console.error("Error terminating contract:", error);
                    terminationDateError.value =
                        "An error occurred while terminating the contract";
                }
            }
        };

        // Print contract
        const printContract = () => {
            window.print();
        };

        // Initialize
        onMounted(() => {
            fetchContractDetails();
        });

        return {
            contract,
            isLoading,
            showDeleteModal,
            showActivateModal,
            showTerminateModal,
            terminationDate,
            terminationReason,
            terminationDateError,
            formatDate,
            formatCurrency,
            formatContractType,
            formatStatus,
            getStatusClass,
            isExpired,
            getCurrentDate,
            getDaysRemaining,
            getRemainingDaysClass,
            confirmDelete,
            closeDeleteModal,
            deleteContract,
            confirmActivate,
            closeActivateModal,
            activateContract,
            confirmTerminate,
            closeTerminateModal,
            terminateContract,
            printContract,
        };
    },
};
</script>

<style scoped>
.contract-detail-container {
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
    margin-bottom: 0.5rem;
}

.action-buttons {
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

.contract-detail-content {
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

.mt-4 {
    margin-top: 1rem;
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

.info-section {
    margin-bottom: 1.5rem;
}

.section-subtitle {
    font-size: 1rem;
    font-weight: 600;
    color: var(--gray-700);
    margin-top: 0;
    margin-bottom: 0.5rem;
}

.info-text {
    font-size: 0.875rem;
    color: var(--gray-700);
    line-height: 1.5;
}

/* Timeline styling */
.timeline {
    position: relative;
    padding-left: 2rem;
}

.timeline::before {
    content: "";
    position: absolute;
    top: 0;
    bottom: 0;
    left: 0.5rem;
    width: 2px;
    background-color: var(--gray-200);
}

.timeline-item {
    position: relative;
    padding-bottom: 2rem;
}

.timeline-item:last-child {
    padding-bottom: 0;
}

.timeline-marker {
    position: absolute;
    top: 0;
    left: -2rem;
    width: 1rem;
    height: 1rem;
    border-radius: 50%;
    background-color: var(--primary-color);
    border: 2px solid white;
}

.timeline-content {
    margin-bottom: 0.5rem;
}

.timeline-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--gray-800);
    margin: 0 0 0.25rem 0;
}

.timeline-date {
    font-size: 0.75rem;
    color: var(--gray-500);
    margin: 0 0 0.25rem 0;
}

.timeline-note {
    font-size: 0.875rem;
    color: var(--gray-700);
    margin: 0.25rem 0 0 0;
    font-style: italic;
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

.status-active {
    background-color: var(--success-bg);
    color: var(--success-color);
}

.status-terminated {
    background-color: var(--danger-bg);
    color: var(--danger-color);
}

.status-expired {
    background-color: var(--warning-bg);
    color: var(--warning-color);
}

.text-danger {
    color: var(--danger-color) !important;
}

.text-warning {
    color: var(--warning-color) !important;
}

.text-success {
    color: var(--success-color) !important;
}

.modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 50;
    display: flex;
    justify-content: center;
    align-items: center;
}

.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 50;
}

.modal-content {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    width: 100%;
    z-index: 60;
    overflow: hidden;
}

.modal-sm {
    max-width: 400px;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.modal-header h2 {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
    color: #1e293b;
}

.close-btn {
    background: none;
    border: none;
    color: #64748b;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.25rem;
    border-radius: 0.25rem;
}

.close-btn:hover {
    background-color: #f1f5f9;
    color: #0f172a;
}

.modal-body {
    padding: 1.5rem;
}

.modal-body p {
    margin-top: 0;
    margin-bottom: 1.5rem;
    color: #1e293b;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #334155;
    font-size: 0.875rem;
}

.form-control {
    width: 100%;
    padding: 0.625rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    background-color: white;
    transition: border-color 0.2s;
}

.form-control:focus {
    border-color: #2563eb;
    outline: none;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.error-message {
    margin-top: 0.375rem;
    font-size: 0.75rem;
    color: #dc2626;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
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

.btn-secondary {
    background-color: var(--gray-200);
    color: var(--gray-700);
}

.btn-secondary:hover {
    background-color: var(--gray-300);
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

    .action-buttons {
        flex-wrap: wrap;
    }
}

@media print {
    .back-link,
    .header-actions,
    .btn {
        display: none !important;
    }

    .detail-card {
        box-shadow: none;
        border: 1px solid #e2e8f0;
        margin-bottom: 1rem;
        page-break-inside: avoid;
    }
}
</style>
