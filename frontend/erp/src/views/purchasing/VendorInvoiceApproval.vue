<!-- src/views/purchasing/VendorInvoiceApproval.vue -->
<template>
    <div class="invoice-approval-container">
        <div class="page-header">
            <div class="header-left">
                <router-link
                    :to="`/purchasing/vendor-invoices/${id}`"
                    class="back-link"
                >
                    <i class="fas fa-arrow-left"></i> Back to Invoice Details
                </router-link>
                <h1>Approve Vendor Invoice</h1>
            </div>
        </div>

        <div v-if="isLoading" class="loading-container">
            <div class="loading-spinner">
                <i class="fas fa-spinner fa-spin"></i>
            </div>
            <p>Loading invoice data...</p>
        </div>

        <div v-else-if="!vendorInvoice" class="error-container">
            <div class="error-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h2>Invoice Not Found</h2>
            <p>
                The requested vendor invoice could not be found or may have been
                deleted.
            </p>
            <router-link
                to="/purchasing/vendor-invoices"
                class="btn btn-primary"
            >
                Return to Vendor Invoices List
            </router-link>
        </div>

        <div v-else-if="!canApprove" class="error-container">
            <div class="error-icon">
                <i class="fas fa-lock"></i>
            </div>
            <h2>Cannot Approve Invoice</h2>
            <p>
                This invoice cannot be approved because its status is "{{
                    vendorInvoice.status
                }}".
            </p>
            <p>Only pending invoices can be approved.</p>
            <router-link
                :to="`/purchasing/vendor-invoices/${id}`"
                class="btn btn-primary"
            >
                Return to Invoice Details
            </router-link>
        </div>

        <div v-else class="approval-content">
            <div class="detail-card">
                <div class="card-header">
                    <h2 class="card-title">Invoice Approval</h2>
                </div>
                <div class="card-body">
                    <div class="summary-section">
                        <h3 class="section-subtitle">Invoice Summary</h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Invoice Number</span>
                                <span class="info-value">{{
                                    vendorInvoice.invoice_number
                                }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Invoice Date</span>
                                <span class="info-value">{{
                                    formatDate(vendorInvoice.invoice_date)
                                }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Due Date</span>
                                <span class="info-value">{{
                                    formatDate(vendorInvoice.due_date)
                                }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Vendor</span>
                                <span class="info-value">{{
                                    vendorInvoice.vendor.name
                                }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Purchase Order</span>
                                <span class="info-value">{{
                                    vendorInvoice.purchase_order.po_number
                                }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Total Amount</span>
                                <span class="info-value">{{
                                    formatCurrency(vendorInvoice.total_amount)
                                }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="validation-section">
                        <h3 class="section-subtitle">Validation Checks</h3>
                        <ul class="validation-list">
                            <li
                                :class="[
                                    'validation-item',
                                    poExists ? 'valid' : 'invalid',
                                ]"
                            >
                                <div class="validation-icon">
                                    <i
                                        class="fas"
                                        :class="
                                            poExists ? 'fa-check' : 'fa-times'
                                        "
                                    ></i>
                                </div>
                                <div class="validation-content">
                                    <div class="validation-title">
                                        Purchase Order Exists
                                    </div>
                                    <div class="validation-message">
                                        {{
                                            poExists
                                                ? "The purchase order exists and is valid."
                                                : "Purchase order not found or invalid."
                                        }}
                                    </div>
                                </div>
                            </li>
                            <li
                                :class="[
                                    'validation-item',
                                    poStatusValid ? 'valid' : 'invalid',
                                ]"
                            >
                                <div class="validation-icon">
                                    <i
                                        class="fas"
                                        :class="
                                            poStatusValid
                                                ? 'fa-check'
                                                : 'fa-times'
                                        "
                                    ></i>
                                </div>
                                <div class="validation-content">
                                    <div class="validation-title">
                                        Purchase Order Status
                                    </div>
                                    <div class="validation-message">
                                        {{
                                            poStatusValid
                                                ? "The purchase order is in a valid status for invoicing."
                                                : 'Purchase order must be in "partial", "received", or "completed" status.'
                                        }}
                                    </div>
                                </div>
                            </li>
                            <li
                                :class="[
                                    'validation-item',
                                    lineItemsMatch ? 'valid' : 'invalid',
                                ]"
                            >
                                <div class="validation-icon">
                                    <i
                                        class="fas"
                                        :class="
                                            lineItemsMatch
                                                ? 'fa-check'
                                                : 'fa-times'
                                        "
                                    ></i>
                                </div>
                                <div class="validation-content">
                                    <div class="validation-title">
                                        Line Items Match
                                    </div>
                                    <div class="validation-message">
                                        {{
                                            lineItemsMatch
                                                ? "All invoice line items match with purchase order lines."
                                                : "Some invoice line items do not match with purchase order."
                                        }}
                                    </div>
                                </div>
                            </li>
                            <li
                                :class="[
                                    'validation-item',
                                    quantitiesValid ? 'valid' : 'warning',
                                ]"
                            >
                                <div class="validation-icon">
                                    <i
                                        class="fas"
                                        :class="
                                            quantitiesValid
                                                ? 'fa-check'
                                                : 'fa-exclamation-triangle'
                                        "
                                    ></i>
                                </div>
                                <div class="validation-content">
                                    <div class="validation-title">
                                        Quantities Valid
                                    </div>
                                    <div class="validation-message">
                                        {{
                                            quantitiesValid
                                                ? "Invoice quantities match with received quantities."
                                                : "Some invoice quantities exceed received quantities."
                                        }}
                                    </div>
                                </div>
                            </li>
                            <li
                                :class="[
                                    'validation-item',
                                    pricesMatch ? 'valid' : 'warning',
                                ]"
                            >
                                <div class="validation-icon">
                                    <i
                                        class="fas"
                                        :class="
                                            pricesMatch
                                                ? 'fa-check'
                                                : 'fa-exclamation-triangle'
                                        "
                                    ></i>
                                </div>
                                <div class="validation-content">
                                    <div class="validation-title">
                                        Prices Match
                                    </div>
                                    <div class="validation-message">
                                        {{
                                            pricesMatch
                                                ? "Invoice prices match with purchase order prices."
                                                : "Some invoice prices differ from purchase order."
                                        }}
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <div class="approval-form">
                        <h3 class="section-subtitle">Approval Decision</h3>
                        <div class="form-group">
                            <label for="comments">Comments</label>
                            <textarea
                                id="comments"
                                v-model="approvalData.comments"
                                rows="3"
                                placeholder="Add any comments or notes about this approval decision"
                            ></textarea>
                        </div>

                        <div class="form-actions">
                            <button
                                type="button"
                                class="btn btn-danger"
                                @click="rejectInvoice"
                                :disabled="isProcessing"
                            >
                                <i class="fas fa-times-circle"></i>
                                {{
                                    isProcessing
                                        ? "Processing..."
                                        : "Reject Invoice"
                                }}
                            </button>
                            <button
                                type="button"
                                class="btn btn-success"
                                @click="approveInvoice"
                                :disabled="isProcessing || !allValidationsPass"
                            >
                                <i class="fas fa-check-circle"></i>
                                {{
                                    isProcessing
                                        ? "Processing..."
                                        : "Approve Invoice"
                                }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import VendorInvoiceService from "@/services/VendorInvoiceService";

export default {
    name: "VendorInvoiceApproval",
    props: {
        id: {
            type: [Number, String],
            required: true,
        },
    },
    setup(props) {
        const router = useRouter();
        const vendorInvoice = ref(null);
        const isLoading = ref(true);
        const isProcessing = ref(false);

        const approvalData = ref({
            comments: "",
        });

        // Fetch vendor invoice details
        const fetchVendorInvoice = async () => {
            isLoading.value = true;
            try {
                const response =
                    await VendorInvoiceService.getVendorInvoiceById(props.id);
                vendorInvoice.value =
                    response.data && response.data.data
                        ? response.data.data
                        : null;
            } catch (error) {
                console.error("Error fetching vendor invoice details:", error);
                vendorInvoice.value = null;
            } finally {
                isLoading.value = false;
            }
        };

        // Computed property to determine if invoice can be approved
        const canApprove = computed(() => {
            return (
                vendorInvoice.value && vendorInvoice.value.status === "pending"
            );
        });

        // Validation computed properties
        const poExists = computed(() => {
            return !!vendorInvoice.value?.purchase_order;
        });

        const poStatusValid = computed(() => {
            if (!vendorInvoice.value?.purchase_order) return false;

            const validStatuses = ["partial", "received", "completed"];
            return validStatuses.includes(
                vendorInvoice.value.purchase_order.status
            );
        });

        const lineItemsMatch = computed(() => {
            if (
                !vendorInvoice.value?.lines ||
                !vendorInvoice.value?.purchase_order?.lines
            )
                return false;

            // Check if all invoice lines have corresponding PO lines
            for (const invoiceLine of vendorInvoice.value.lines) {
                const matchingPoLine =
                    vendorInvoice.value.purchase_order.lines.find(
                        (poLine) => poLine.line_id === invoiceLine.po_line_id
                    );

                if (!matchingPoLine) return false;
            }

            return true;
        });

        const quantitiesValid = computed(() => {
            if (
                !vendorInvoice.value?.lines ||
                !vendorInvoice.value?.purchase_order?.lines
            )
                return false;

            // Check if invoice quantities are less than or equal to received quantities
            for (const invoiceLine of vendorInvoice.value.lines) {
                const matchingPoLine =
                    vendorInvoice.value.purchase_order.lines.find(
                        (poLine) => poLine.line_id === invoiceLine.po_line_id
                    );

                if (matchingPoLine) {
                    const receivedQty = matchingPoLine.received_quantity || 0;
                    if (invoiceLine.quantity > receivedQty) return false;
                }
            }

            return true;
        });

        const pricesMatch = computed(() => {
            if (
                !vendorInvoice.value?.lines ||
                !vendorInvoice.value?.purchase_order?.lines
            )
                return false;

            // Check if invoice prices match PO prices
            for (const invoiceLine of vendorInvoice.value.lines) {
                const matchingPoLine =
                    vendorInvoice.value.purchase_order.lines.find(
                        (poLine) => poLine.line_id === invoiceLine.po_line_id
                    );

                if (
                    matchingPoLine &&
                    invoiceLine.unit_price !== matchingPoLine.unit_price
                ) {
                    return false;
                }
            }

            return true;
        });

        const allValidationsPass = computed(() => {
            return (
                poExists.value && poStatusValid.value && lineItemsMatch.value
            );
            // Note: We're not requiring quantitiesValid and pricesMatch to pass
            // These are warnings but not blockers for approval
        });

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

        // Approve invoice
        const approveInvoice = async () => {
            if (!allValidationsPass.value) return;

            isProcessing.value = true;

            try {
                await VendorInvoiceService.approveVendorInvoice(props.id, {
                    comments: approvalData.value.comments,
                });

                // Navigate back to invoice detail page
                router.push(`/purchasing/vendor-invoices/${props.id}`);
            } catch (error) {
                console.error("Error approving vendor invoice:", error);

                if (
                    error.response &&
                    error.response.data &&
                    error.response.data.message
                ) {
                    alert(error.response.data.message);
                } else {
                    alert(
                        "Failed to approve vendor invoice. Please try again."
                    );
                }

                isProcessing.value = false;
            }
        };

        // Reject invoice
        const rejectInvoice = async () => {
            isProcessing.value = true;

            try {
                await VendorInvoiceService.rejectVendorInvoice(props.id, {
                    comments: approvalData.value.comments,
                });

                // Navigate back to invoice detail page
                router.push(`/purchasing/vendor-invoices/${props.id}`);
            } catch (error) {
                console.error("Error rejecting vendor invoice:", error);

                if (
                    error.response &&
                    error.response.data &&
                    error.response.data.message
                ) {
                    alert(error.response.data.message);
                } else {
                    alert("Failed to reject vendor invoice. Please try again.");
                }

                isProcessing.value = false;
            }
        };

        // Initialize data
        onMounted(() => {
            fetchVendorInvoice();
        });

        return {
            vendorInvoice,
            isLoading,
            isProcessing,
            approvalData,
            canApprove,
            poExists,
            poStatusValid,
            lineItemsMatch,
            quantitiesValid,
            pricesMatch,
            allValidationsPass,
            formatDate,
            formatCurrency,
            approveInvoice,
            rejectInvoice,
        };
    },
};
</script>

<style scoped>
.invoice-approval-container {
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

.approval-content {
    max-width: 800px;
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

.section-subtitle {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0 0 1rem 0;
    color: var(--gray-700);
}

.summary-section,
.validation-section {
    margin-bottom: 2rem;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.info-label {
    font-size: 0.75rem;
    color: var(--gray-500);
}

.info-value {
    font-size: 0.875rem;
    color: var(--gray-800);
    font-weight: 500;
}

.validation-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.validation-item {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    border-radius: 0.5rem;
    background-color: var(--gray-50);
}

.validation-item.valid {
    background-color: #d1fae5;
}

.validation-item.warning {
    background-color: #fef9c3;
}

.validation-item.invalid {
    background-color: #fee2e2;
}

.validation-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border-radius: 9999px;
    flex-shrink: 0;
}

.validation-item.valid .validation-icon {
    background-color: #10b981;
    color: white;
}

.validation-item.warning .validation-icon {
    background-color: #f59e0b;
    color: white;
}

.validation-item.invalid .validation-icon {
    background-color: #ef4444;
    color: white;
}

.validation-content {
    flex: 1;
}

.validation-title {
    font-weight: 600;
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
}

.validation-item.valid .validation-title {
    color: #047857;
}

.validation-item.warning .validation-title {
    color: #92400e;
}

.validation-item.invalid .validation-title {
    color: #b91c1c;
}

.validation-message {
    font-size: 0.875rem;
    color: var(--gray-700);
}

.approval-form {
    margin-top: 2rem;
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
    padding: 0.75rem;
    border: 1px solid var(--gray-200);
    border-radius: 0.375rem;
    font-size: 0.875rem;
    resize: vertical;
}

textarea:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
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

.btn-primary:hover:not(:disabled) {
    background-color: var(--primary-dark);
}

.btn-success {
    background-color: var(--success-color);
    color: white;
}

.btn-success:hover:not(:disabled) {
    background-color: var(--success-dark);
}

.btn-danger {
    background-color: var(--danger-color);
    color: white;
}

.btn-danger:hover:not(:disabled) {
    background-color: var(--danger-dark);
}

.btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

@media (max-width: 768px) {
    .info-grid {
        grid-template-columns: 1fr;
    }
}
</style>
