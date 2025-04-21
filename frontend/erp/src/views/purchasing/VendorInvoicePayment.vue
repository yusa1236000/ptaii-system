<!-- src/views/purchasing/VendorInvoicePayment.vue -->
<template>
    <div class="invoice-payment-container">
        <div class="page-header">
            <div class="header-left">
                <router-link
                    :to="`/purchasing/vendor-invoices/${id}`"
                    class="back-link"
                >
                    <i class="fas fa-arrow-left"></i> Back to Invoice Details
                </router-link>
                <h1>Record Payment for Invoice</h1>
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

        <div v-else-if="!canPay" class="error-container">
            <div class="error-icon">
                <i class="fas fa-lock"></i>
            </div>
            <h2>Cannot Record Payment</h2>
            <p>
                This invoice cannot be paid because its status is "{{
                    vendorInvoice.status
                }}".
            </p>
            <p>
                Only approved or partially paid invoices can receive payments.
            </p>
            <router-link
                :to="`/purchasing/vendor-invoices/${id}`"
                class="btn btn-primary"
            >
                Return to Invoice Details
            </router-link>
        </div>

        <div v-else-if="balanceDue <= 0" class="error-container">
            <div class="error-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h2>Invoice Fully Paid</h2>
            <p>
                This invoice has been fully paid. No further payments are
                needed.
            </p>
            <router-link
                :to="`/purchasing/vendor-invoices/${id}`"
                class="btn btn-primary"
            >
                Return to Invoice Details
            </router-link>
        </div>

        <div v-else class="payment-content">
            <div class="payment-cards-container">
                <!-- Invoice Summary Card -->
                <div class="detail-card summary-card">
                    <div class="card-header">
                        <h2 class="card-title">Invoice Summary</h2>
                    </div>
                    <div class="card-body">
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
                                <span class="info-label">Status</span>
                                <span
                                    :class="[
                                        'info-value',
                                        'status-badge',
                                        getStatusClass(vendorInvoice.status),
                                    ]"
                                >
                                    {{ formatStatus(vendorInvoice.status) }}
                                </span>
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
                        </div>

                        <div class="payment-summary">
                            <div class="payment-summary-item">
                                <span class="summary-label">Total Amount</span>
                                <span class="summary-value">{{
                                    formatCurrency(vendorInvoice.total_amount)
                                }}</span>
                            </div>
                            <div class="payment-summary-item">
                                <span class="summary-label">Amount Paid</span>
                                <span class="summary-value">{{
                                    formatCurrency(amountPaid)
                                }}</span>
                            </div>
                            <div class="payment-summary-item highlight">
                                <span class="summary-label">Balance Due</span>
                                <span class="summary-value">{{
                                    formatCurrency(balanceDue)
                                }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Form Card -->
                <div class="detail-card payment-form-card">
                    <div class="card-header">
                        <h2 class="card-title">Payment Details</h2>
                    </div>
                    <div class="card-body">
                        <form @submit.prevent="recordPayment">
                            <div class="form-group">
                                <label for="payment_date">Payment Date*</label>
                                <input
                                    type="date"
                                    id="payment_date"
                                    v-model="paymentData.payment_date"
                                    required
                                />
                                <div
                                    v-if="errors.payment_date"
                                    class="error-message"
                                >
                                    {{ errors.payment_date }}
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="amount">Payment Amount*</label>
                                <input
                                    type="number"
                                    id="amount"
                                    v-model.number="paymentData.amount"
                                    min="0.01"
                                    :max="balanceDue"
                                    step="0.01"
                                    required
                                />
                                <div v-if="errors.amount" class="error-message">
                                    {{ errors.amount }}
                                </div>
                                <div class="input-help">
                                    <button
                                        type="button"
                                        class="btn-link"
                                        @click="setFullAmount"
                                    >
                                        Pay full amount ({{
                                            formatCurrency(balanceDue)
                                        }})
                                    </button>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="payment_method"
                                    >Payment Method*</label
                                >
                                <select
                                    id="payment_method"
                                    v-model="paymentData.payment_method"
                                    required
                                >
                                    <option value="">
                                        Select Payment Method
                                    </option>
                                    <option value="bank_transfer">
                                        Bank Transfer
                                    </option>
                                    <option value="check">Check</option>
                                    <option value="credit_card">
                                        Credit Card
                                    </option>
                                    <option value="cash">Cash</option>
                                    <option value="other">Other</option>
                                </select>
                                <div
                                    v-if="errors.payment_method"
                                    class="error-message"
                                >
                                    {{ errors.payment_method }}
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="reference_number"
                                    >Reference Number</label
                                >
                                <input
                                    type="text"
                                    id="reference_number"
                                    v-model="paymentData.reference_number"
                                    placeholder="Check number, transaction ID, etc."
                                />
                                <div
                                    v-if="errors.reference_number"
                                    class="error-message"
                                >
                                    {{ errors.reference_number }}
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="notes">Notes</label>
                                <textarea
                                    id="notes"
                                    v-model="paymentData.notes"
                                    rows="3"
                                    placeholder="Any additional information about this payment"
                                ></textarea>
                                <div v-if="errors.notes" class="error-message">
                                    {{ errors.notes }}
                                </div>
                            </div>

                            <div class="form-actions">
                                <button
                                    type="button"
                                    class="btn btn-secondary"
                                    @click="cancel"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    class="btn btn-primary"
                                    :disabled="isSubmitting"
                                >
                                    {{
                                        isSubmitting
                                            ? "Processing..."
                                            : "Record Payment"
                                    }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Previous Payments Section (if any) -->
            <div
                v-if="
                    vendorInvoice.payments && vendorInvoice.payments.length > 0
                "
                class="detail-card previous-payments-card"
            >
                <div class="card-header">
                    <h2 class="card-title">Previous Payments</h2>
                </div>
                <div class="card-body">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Payment Date</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Reference</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="payment in vendorInvoice.payments"
                                :key="payment.payment_id"
                            >
                                <td>{{ formatDate(payment.payment_date) }}</td>
                                <td>{{ formatCurrency(payment.amount) }}</td>
                                <td>
                                    {{
                                        formatPaymentMethod(
                                            payment.payment_method
                                        )
                                    }}
                                </td>
                                <td>{{ payment.reference_number || "N/A" }}</td>
                                <td>{{ payment.notes || "N/A" }}</td>
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
import { useRouter } from "vue-router";
import VendorInvoiceService from "@/services/VendorInvoiceService";

export default {
    name: "VendorInvoicePayment",
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
        const isSubmitting = ref(false);
        const errors = ref({});

        // Payment form data
        const paymentData = ref({
            payment_date: new Date().toISOString().slice(0, 10),
            amount: 0,
            payment_method: "",
            reference_number: "",
            notes: "",
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

                // If invoice is found, set initial payment amount to balance due
                if (vendorInvoice.value) {
                    paymentData.value.amount = calculateBalanceDue();
                }
            } catch (error) {
                console.error("Error fetching vendor invoice details:", error);
                vendorInvoice.value = null;
            } finally {
                isLoading.value = false;
            }
        };

        // Calculate amount paid
        const amountPaid = computed(() => {
            if (!vendorInvoice.value || !vendorInvoice.value.payments) return 0;

            return vendorInvoice.value.payments.reduce((total, payment) => {
                return total + (payment.amount || 0);
            }, 0);
        });

        // Calculate balance due
        const balanceDue = computed(() => {
            if (!vendorInvoice.value) return 0;

            const totalAmount = vendorInvoice.value.total_amount || 0;
            return totalAmount - amountPaid.value;
        });

        // Computed property to determine if invoice can be paid
        const canPay = computed(() => {
            return (
                vendorInvoice.value &&
                (vendorInvoice.value.status === "approved" ||
                    vendorInvoice.value.status === "partially_paid")
            );
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

        // Format status text
        const formatStatus = (status) => {
            switch (status) {
                case "pending":
                    return "Pending";
                case "approved":
                    return "Approved";
                case "paid":
                    return "Paid";
                case "partially_paid":
                    return "Partially Paid";
                case "canceled":
                    return "Canceled";
                default:
                    return status;
            }
        };

        // Format payment method
        const formatPaymentMethod = (method) => {
            switch (method) {
                case "bank_transfer":
                    return "Bank Transfer";
                case "check":
                    return "Check";
                case "credit_card":
                    return "Credit Card";
                case "cash":
                    return "Cash";
                case "other":
                    return "Other";
                default:
                    return method;
            }
        };

        // Get status CSS class
        const getStatusClass = (status) => {
            switch (status) {
                case "pending":
                    return "status-pending";
                case "approved":
                    return "status-approved";
                case "paid":
                    return "status-completed";
                case "partially_paid":
                    return "status-partial";
                case "canceled":
                    return "status-canceled";
                default:
                    return "status-pending";
            }
        };

        // Set payment amount to full balance due
        const setFullAmount = () => {
            paymentData.value.amount = balanceDue.value;
        };

        // Calculate balance due (for non-computed contexts)
        const calculateBalanceDue = () => {
            if (!vendorInvoice.value) return 0;

            const totalAmount = vendorInvoice.value.total_amount || 0;
            const paid = vendorInvoice.value.payments
                ? vendorInvoice.value.payments.reduce((total, payment) => {
                      return total + (payment.amount || 0);
                  }, 0)
                : 0;

            return totalAmount - paid;
        };

        // Record payment
        const recordPayment = async () => {
            // Validate form
            const validationErrors = {};

            if (!paymentData.value.payment_date) {
                validationErrors.payment_date = "Payment date is required";
            }

            if (!paymentData.value.amount) {
                validationErrors.amount = "Payment amount is required";
            } else if (paymentData.value.amount <= 0) {
                validationErrors.amount =
                    "Payment amount must be greater than zero";
            } else if (paymentData.value.amount > balanceDue.value) {
                validationErrors.amount = `Payment amount cannot exceed balance due (${formatCurrency(
                    balanceDue.value
                )})`;
            }

            if (!paymentData.value.payment_method) {
                validationErrors.payment_method = "Payment method is required";
            }

            // If validation errors exist, update errors and stop submission
            if (Object.keys(validationErrors).length > 0) {
                errors.value = validationErrors;
                return;
            }

            // Clear validation errors
            errors.value = {};
            isSubmitting.value = true;

            try {
                await VendorInvoiceService.recordPayment(
                    props.id,
                    paymentData.value
                );

                // Navigate back to invoice detail page
                router.push(`/purchasing/vendor-invoices/${props.id}`);
            } catch (error) {
                console.error("Error recording payment:", error);

                if (error.response && error.response.status === 422) {
                    errors.value = error.response.data.errors || {};
                } else if (
                    error.response &&
                    error.response.data &&
                    error.response.data.message
                ) {
                    alert(error.response.data.message);
                } else {
                    alert("Failed to record payment. Please try again.");
                }

                isSubmitting.value = false;
            }
        };

        // Cancel form
        const cancel = () => {
            router.push(`/purchasing/vendor-invoices/${props.id}`);
        };

        // Initialize data
        onMounted(() => {
            fetchVendorInvoice();
        });

        return {
            vendorInvoice,
            isLoading,
            isSubmitting,
            paymentData,
            errors,
            amountPaid,
            balanceDue,
            canPay,
            formatDate,
            formatCurrency,
            formatStatus,
            formatPaymentMethod,
            getStatusClass,
            setFullAmount,
            recordPayment,
            cancel,
        };
    },
};
</script>

<style scoped>
.invoice-payment-container {
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
    margin-bottom: 1rem;
}

.error-container .error-icon {
    color: var(--danger-color);
}

.error-container h2 {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
    color: var(--gray-800);
}

.payment-content {
    max-width: 1000px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.payment-cards-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
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
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
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

.payment-summary {
    padding: 1.5rem;
    background-color: var(--gray-50);
    border-radius: 0.5rem;
    border: 1px solid var(--gray-200);
}

.payment-summary-item {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--gray-200);
}

.payment-summary-item:last-child {
    border-bottom: none;
}

.payment-summary-item.highlight {
    font-weight: 600;
    font-size: 1.125rem;
    color: var(--gray-900);
}

.summary-label {
    color: var(--gray-700);
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

input[type="text"],
input[type="date"],
input[type="number"],
select,
textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--gray-200);
    border-radius: 0.375rem;
    font-size: 0.875rem;
}

input:focus,
select:focus,
textarea:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.input-help {
    margin-top: 0.5rem;
    font-size: 0.75rem;
    color: var(--gray-500);
}

.btn-link {
    background: none;
    border: none;
    padding: 0;
    color: var(--primary-color);
    cursor: pointer;
    font-size: 0.75rem;
    text-decoration: underline;
}

.btn-link:hover {
    color: var(--primary-dark);
}

.error-message {
    margin-top: 0.375rem;
    font-size: 0.75rem;
    color: var(--danger-color);
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 1.5rem;
}

.previous-payments-card {
    margin-top: 1.5rem;
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

.status-approved {
    background-color: #dcfce7;
    color: #166534;
}

.status-completed {
    background-color: #bbf7d0;
    color: #15803d;
}

.status-partial {
    background-color: #fef9c3;
    color: #854d0e;
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

.btn-primary:hover:not(:disabled) {
    background-color: var(--primary-dark);
}

.btn-secondary {
    background-color: var(--gray-200);
    color: var(--gray-800);
}

.btn-secondary:hover {
    background-color: var(--gray-300);
}

.btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

@media (max-width: 768px) {
    .payment-cards-container {
        grid-template-columns: 1fr;
    }

    .info-grid {
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 480px) {
    .info-grid {
        grid-template-columns: 1fr;
    }
}
</style>
