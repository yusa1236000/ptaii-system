<!-- src/views/purchasing/ConvertToRFQ.vue -->
<template>
    <div class="convert-to-rfq-container">
        <div class="page-header">
            <div class="header-left">
                <router-link
                    :to="`/purchasing/requisitions/${id}`"
                    class="back-link"
                >
                    <i class="fas fa-arrow-left"></i> Back to Requisition
                </router-link>
                <h1>Convert to Request for Quotation</h1>
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

        <div v-else-if="pr.status !== 'approved'" class="error-container">
            <div class="error-icon">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <h2>Cannot Convert This Requisition</h2>
            <p>
                This purchase requisition is in
                {{ getStatusLabel(pr.status) }} status. Only approved
                requisitions can be converted to RFQs.
            </p>
            <router-link
                :to="`/purchasing/requisitions/${id}`"
                class="btn btn-primary"
            >
                Return to Requisition Details
            </router-link>
        </div>

        <div v-else class="convert-to-rfq-content">
            <!-- PR Summary Card -->
            <div class="summary-card">
                <div class="card-header">
                    <h2 class="card-title">Requisition Summary</h2>
                    <span class="status-badge status-approved">{{
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
                    </div>
                </div>
            </div>

            <!-- RFQ Form -->
            <div class="rfq-form-card">
                <div class="card-header">
                    <h2 class="card-title">Request for Quotation Details</h2>
                </div>
                <div class="card-body">
                    <form @submit.prevent="createRFQ">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="rfq_date">RFQ Date*</label>
                                <input
                                    type="date"
                                    id="rfq_date"
                                    v-model="rfqForm.rfq_date"
                                    required
                                />
                                <div
                                    v-if="errors.rfq_date"
                                    class="error-message"
                                >
                                    {{ errors.rfq_date }}
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="response_deadline"
                                    >Response Deadline*</label
                                >
                                <input
                                    type="date"
                                    id="response_deadline"
                                    v-model="rfqForm.response_deadline"
                                    required
                                />
                                <div
                                    v-if="errors.response_deadline"
                                    class="error-message"
                                >
                                    {{ errors.response_deadline }}
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="notes">Notes</label>
                            <textarea
                                id="notes"
                                v-model="rfqForm.notes"
                                rows="3"
                                placeholder="Additional information for vendors"
                            ></textarea>
                            <div v-if="errors.notes" class="error-message">
                                {{ errors.notes }}
                            </div>
                        </div>

                        <!-- Vendor Selection Section -->
                        <div class="vendor-selection-section">
                            <h3 class="section-subtitle">
                                Vendors to Request Quotation From
                            </h3>

                            <div
                                v-if="isLoadingVendors"
                                class="loading-message"
                            >
                                <i class="fas fa-spinner fa-spin"></i> Loading
                                vendors...
                            </div>

                            <div
                                v-else-if="vendors.length === 0"
                                class="empty-message"
                            >
                                <i class="fas fa-exclamation-circle"></i> No
                                vendors found. Please add vendors to the system
                                first.
                            </div>

                            <div v-else class="vendor-list">
                                <div
                                    v-for="vendor in vendors"
                                    :key="vendor.vendor_id"
                                    class="vendor-item"
                                >
                                    <div class="checkbox-wrapper">
                                        <input
                                            type="checkbox"
                                            :id="'vendor-' + vendor.vendor_id"
                                            :value="vendor.vendor_id"
                                            v-model="rfqForm.vendor_ids"
                                        />
                                        <label
                                            :for="'vendor-' + vendor.vendor_id"
                                        >
                                            <strong>{{ vendor.name }}</strong>
                                            <div class="vendor-details">
                                                <span>{{
                                                    vendor.vendor_code
                                                }}</span>
                                                <span
                                                    v-if="vendor.contact_person"
                                                    >Contact:
                                                    {{
                                                        vendor.contact_person
                                                    }}</span
                                                >
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div
                                v-if="errors.vendor_ids"
                                class="error-message mt-2"
                            >
                                {{ errors.vendor_ids }}
                            </div>
                        </div>

                        <!-- Item Selection Section -->
                        <div class="item-selection-section">
                            <h3 class="section-subtitle">
                                Items to Include in RFQ
                            </h3>

                            <table class="items-table">
                                <thead>
                                    <tr>
                                        <th class="checkbox-column">
                                            <div class="checkbox-wrapper">
                                                <input
                                                    type="checkbox"
                                                    id="select-all"
                                                    :checked="
                                                        isAllItemsSelected
                                                    "
                                                    @change="toggleAllItems"
                                                />
                                                <label for="select-all"
                                                    >All</label
                                                >
                                            </div>
                                        </th>
                                        <th>Item</th>
                                        <th>Quantity</th>
                                        <th>Unit</th>
                                        <th>Required Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="line in pr.lines"
                                        :key="line.line_id"
                                    >
                                        <td class="checkbox-column">
                                            <div class="checkbox-wrapper">
                                                <input
                                                    type="checkbox"
                                                    :id="'line-' + line.line_id"
                                                    :value="line.line_id"
                                                    v-model="rfqForm.line_ids"
                                                />
                                                <label
                                                    :for="
                                                        'line-' + line.line_id
                                                    "
                                                ></label>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="item-info">
                                                <span class="item-name">{{
                                                    line.item
                                                        ? line.item.name
                                                        : "N/A"
                                                }}</span>
                                                <span class="item-code">{{
                                                    line.item
                                                        ? line.item.item_code
                                                        : ""
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
                                        <td>
                                            {{ formatDate(line.required_date) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <div
                                v-if="errors.line_ids"
                                class="error-message mt-2"
                            >
                                {{ errors.line_ids }}
                            </div>
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
                                        ? "Converting..."
                                        : "Create RFQ"
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
import { ref, reactive, computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import PurchaseRequisitionService from "@/services/PurchaseRequisitionService";

export default {
    name: "ConvertToRFQ",
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
        const vendors = ref([]);
        const isLoadingVendors = ref(true);
        const errors = ref({});

        // Form data
        const rfqForm = reactive({
            rfq_date: new Date().toISOString().split("T")[0], // Default to today
            response_deadline: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000)
                .toISOString()
                .split("T")[0], // Default to 7 days from now
            notes: "",
            vendor_ids: [],
            line_ids: [],
        });

        // Computed properties
        const isAllItemsSelected = computed(() => {
            if (!pr.value || !pr.value.lines) return false;
            return (
                pr.value.lines.length > 0 &&
                rfqForm.line_ids.length === pr.value.lines.length
            );
        });

        // Toggle all items selection
        const toggleAllItems = (event) => {
            if (event.target.checked) {
                rfqForm.line_ids = pr.value.lines.map((line) => line.line_id);
            } else {
                rfqForm.line_ids = [];
            }
        };

        // Fetch PR details
        const fetchPRDetails = async () => {
            isLoading.value = true;
            try {
                const response =
                    await PurchaseRequisitionService.getPurchaseRequisitionById(
                        props.id
                    );
                pr.value = response.data.data || null;

                // Pre-select all lines by default
                if (pr.value && pr.value.lines) {
                    rfqForm.line_ids = pr.value.lines.map(
                        (line) => line.line_id
                    );
                }
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

        // Fetch vendors
        const fetchVendors = async () => {
            isLoadingVendors.value = true;
            try {
                const response = await PurchaseRequisitionService.getAllVendors(
                    { status: "active" }
                );
                vendors.value = response.data.data || [];
            } catch (error) {
                console.error("Error fetching vendors:", error);
                vendors.value = [];
            } finally {
                isLoadingVendors.value = false;
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

        // Validate form
        const validateForm = () => {
            const validationErrors = {};

            if (!rfqForm.rfq_date) {
                validationErrors.rfq_date = "RFQ date is required";
            }

            if (!rfqForm.response_deadline) {
                validationErrors.response_deadline =
                    "Response deadline is required";
            } else if (
                new Date(rfqForm.response_deadline) <=
                new Date(rfqForm.rfq_date)
            ) {
                validationErrors.response_deadline =
                    "Response deadline must be after RFQ date";
            }

            if (rfqForm.vendor_ids.length === 0) {
                validationErrors.vendor_ids =
                    "Please select at least one vendor";
            }

            if (rfqForm.line_ids.length === 0) {
                validationErrors.line_ids = "Please select at least one item";
            }

            return validationErrors;
        };

        // Create RFQ
        const createRFQ = async () => {
            // Validate form
            const validationErrors = validateForm();

            if (Object.keys(validationErrors).length > 0) {
                errors.value = validationErrors;
                return;
            }

            isSubmitting.value = true;
            errors.value = {};

            try {
                const response = await PurchaseRequisitionService.convertToRFQ(
                    props.id,
                    rfqForm
                );

                // Redirect to the newly created RFQ
                router.push(`/purchasing/rfqs/${response.data.data.rfq_id}`);
            } catch (error) {
                console.error("Error creating RFQ:", error);

                // Handle validation errors from server
                if (error.response && error.response.status === 422) {
                    errors.value = error.response.data.errors || {};
                } else {
                    // Handle other errors
                    alert("Failed to create RFQ. Please try again.");
                }

                isSubmitting.value = false;
            }
        };

        // Initialize
        onMounted(() => {
            Promise.all([fetchPRDetails(), fetchVendors()]);
        });

        return {
            pr,
            isLoading,
            isSubmitting,
            vendors,
            isLoadingVendors,
            rfqForm,
            errors,
            isAllItemsSelected,
            formatDate,
            getStatusLabel,
            toggleAllItems,
            createRFQ,
        };
    },
};
</script>

<style scoped>
.convert-to-rfq-container {
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

.convert-to-rfq-content {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    max-width: 1000px;
    margin: 0 auto;
}

.summary-card,
.rfq-form-card {
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

.info-label {
    font-size: 0.875rem;
    color: var(--gray-500);
}

.info-value {
    font-size: 1rem;
    color: var(--gray-800);
    font-weight: 500;
}

.section-subtitle {
    font-size: 1.125rem;
    color: var(--gray-800);
    margin: 1.5rem 0 1rem;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.25rem;
    margin-bottom: 1.25rem;
}

.form-group {
    margin-bottom: 1.25rem;
}

.form-group:last-child {
    margin-bottom: 0;
}

label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: var(--gray-700);
    font-size: 0.875rem;
}

input[type="text"],
input[type="date"],
select,
textarea {
    width: 100%;
    padding: 0.625rem;
    border: 1px solid var(--gray-200);
    border-radius: 0.375rem;
    font-size: 0.875rem;
    background-color: white;
    transition: border-color 0.2s;
}

input[type="text"]:focus,
input[type="date"]:focus,
select:focus,
textarea:focus {
    border-color: var(--primary-color);
    outline: none;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

textarea {
    resize: vertical;
    min-height: 80px;
}

.error-message {
    margin-top: 0.375rem;
    font-size: 0.75rem;
    color: var(--danger-color);
}

.mt-2 {
    margin-top: 0.5rem;
}

.vendor-selection-section,
.item-selection-section {
    margin-top: 1.5rem;
}

.loading-message,
.empty-message {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    background-color: var(--gray-50);
    border-radius: 0.375rem;
    color: var(--gray-600);
    font-size: 0.875rem;
}

.vendor-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 0.75rem;
    margin-top: 1rem;
}

.vendor-item {
    background-color: var(--gray-50);
    border: 1px solid var(--gray-200);
    border-radius: 0.375rem;
    padding: 0.75rem;
}

.checkbox-wrapper {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
}

.checkbox-wrapper input[type="checkbox"] {
    margin-top: 0.25rem;
}

.vendor-details {
    display: flex;
    flex-direction: column;
    font-size: 0.75rem;
    color: var(--gray-500);
    margin-top: 0.25rem;
}

.items-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
    margin-top: 1rem;
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

.checkbox-column {
    width: 40px;
    text-align: center;
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

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 2rem;
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

.status-approved {
    background-color: #dcfce7;
    color: #166534;
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

    .form-row {
        grid-template-columns: 1fr;
    }

    .vendor-list {
        grid-template-columns: 1fr;
    }

    .items-table {
        display: block;
        overflow-x: auto;
    }
}
</style>
