<!-- Template section -->
<template>
    <div class="invoice-detail">
        <div class="page-header">
            <h1>Invoice Detail</h1>
            <div class="page-actions">
                <button class="btn btn-secondary" @click="goBack">
                    <i class="fas fa-arrow-left"></i> Back
                </button>
                <div class="btn-group" v-if="invoice">
                    <button
                        class="btn btn-primary"
                        @click="editInvoice"
                        v-if="canEdit"
                    >
                        <i class="fas fa-edit"></i> Edit
                    </button>

                    <button
                        v-if="invoice.status === 'Draft'"
                        class="btn btn-info"
                        @click="markAsSent"
                    >
                        <i class="fas fa-paper-plane"></i> Send
                    </button>

                    <button
                        v-if="
                            ['Sent', 'Partially Paid', 'Overdue'].includes(
                                invoice.status
                            )
                        "
                        class="btn btn-success"
                        @click="recordPayment"
                    >
                        <i class="fas fa-money-bill"></i> Record Payment
                    </button>

                    <button class="btn btn-secondary" @click="printInvoice">
                        <i class="fas fa-print"></i> Print
                    </button>
                </div>
            </div>
        </div>

        <div v-if="isLoading" class="loading-indicator">
            <i class="fas fa-spinner fa-spin"></i> Loading invoice...
        </div>

        <div v-else-if="!invoice" class="empty-state">
            <div class="empty-icon">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <h3>Invoice not found</h3>
            <p>
                The invoice you're looking for doesn't exist or may have been
                deleted.
            </p>
            <button class="btn btn-primary" @click="goBack">
                Back to invoices list
            </button>
        </div>

        <div v-else class="invoice-container">
            <!-- Invoice Header Information -->
            <div class="detail-card">
                <div class="card-header">
                    <h2>Invoice Information</h2>
                    <div
                        class="invoice-status"
                        :class="getStatusClass(invoice.status)"
                    >
                        {{ invoice.status }}
                    </div>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-group">
                            <label>Invoice Number</label>
                            <div class="info-value">
                                {{ invoice.invoice_number }}
                            </div>
                        </div>

                        <div class="info-group">
                            <label>Invoice Date</label>
                            <div class="info-value">
                                {{ formatDate(invoice.invoice_date) }}
                            </div>
                        </div>

                        <div class="info-group">
                            <label>Customer</label>
                            <div class="info-value">
                                {{ invoice.customer.name }}
                            </div>
                        </div>

                        <div class="info-group">
                            <label>Due Date</label>
                            <div class="info-value">
                                {{ formatDate(invoice.due_date) }}
                            </div>
                        </div>

                        <div class="info-group">
                            <label>Reference</label>
                            <div class="info-value">
                                {{ invoice.reference || "-" }}
                            </div>
                        </div>

                        <div class="info-group">
                            <label>Payment Terms</label>
                            <div class="info-value">
                                {{ invoice.payment_terms || "-" }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer Information -->
            <div class="detail-card">
                <div class="card-header">
                    <h2>Customer Information</h2>
                </div>
                <div class="card-body">
                    <div class="customer-info">
                        <div class="info-group">
                            <label>Customer Name</label>
                            <div class="info-value">
                                {{ invoice.customer.name }}
                            </div>
                        </div>

                        <div class="info-group">
                            <label>Customer Code</label>
                            <div class="info-value">
                                {{ invoice.customer.customer_code }}
                            </div>
                        </div>

                        <div class="info-group">
                            <label>Address</label>
                            <div class="info-value">
                                {{ invoice.customer.address || "-" }}
                            </div>
                        </div>

                        <div class="info-group">
                            <label>Tax ID</label>
                            <div class="info-value">
                                {{ invoice.customer.tax_id || "-" }}
                            </div>
                        </div>

                        <div class="info-group">
                            <label>Contact Person</label>
                            <div class="info-value">
                                {{ invoice.customer.contact_person || "-" }}
                            </div>
                        </div>

                        <div class="info-group">
                            <label>Phone</label>
                            <div class="info-value">
                                {{ invoice.customer.phone || "-" }}
                            </div>
                        </div>

                        <div class="info-group">
                            <label>Email</label>
                            <div class="info-value">
                                {{ invoice.customer.email || "-" }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Invoice Items -->
            <div class="detail-card">
                <div class="card-header">
                    <h2>Invoice Items</h2>
                </div>
                <div class="card-body">
                    <div class="invoice-items">
                        <table class="items-table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Unit Price</th>
                                    <th>Quantity</th>
                                    <th>UOM</th>
                                    <th>Discount</th>
                                    <th>Tax</th>
                                    <th>Subtotal</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="line in invoice.invoiceLines"
                                    :key="line.line_id"
                                >
                                    <td>
                                        <div class="item-info">
                                            <div class="item-code">
                                                {{ line.item.item_code }}
                                            </div>
                                            <div class="item-name">
                                                {{ line.item.name }}
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        {{ formatCurrency(line.unit_price) }}
                                    </td>
                                    <td>{{ line.quantity }}</td>
                                    <td>{{ getUomSymbol(line.uom_id) }}</td>
                                    <td>
                                        {{ formatCurrency(line.discount || 0) }}
                                    </td>
                                    <td>{{ formatCurrency(line.tax || 0) }}</td>
                                    <td>{{ formatCurrency(line.subtotal) }}</td>
                                    <td>{{ formatCurrency(line.total) }}</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="6" class="totals-label">
                                        Subtotal
                                    </td>
                                    <td colspan="2" class="totals-value">
                                        {{
                                            formatCurrency(calculateSubtotal())
                                        }}
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="6" class="totals-label">
                                        Total Discount
                                    </td>
                                    <td colspan="2" class="totals-value">
                                        {{
                                            formatCurrency(
                                                calculateTotalDiscount()
                                            )
                                        }}
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="6" class="totals-label">
                                        Total Tax
                                    </td>
                                    <td colspan="2" class="totals-value">
                                        {{
                                            formatCurrency(calculateTotalTax())
                                        }}
                                    </td>
                                </tr>
                                <tr class="grand-total">
                                    <td colspan="6" class="totals-label">
                                        Total
                                    </td>
                                    <td colspan="2" class="totals-value">
                                        {{
                                            formatCurrency(invoice.total_amount)
                                        }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Payment Information -->
            <div class="detail-card">
                <div class="card-header">
                    <h2>Payment Information</h2>
                    <button
                        v-if="canAddPayment"
                        class="btn btn-sm btn-success"
                        @click="recordPayment"
                    >
                        <i class="fas fa-plus"></i> Add Payment
                    </button>
                </div>
                <div class="card-body">
                    <div class="payment-summary">
                        <div class="summary-item">
                            <div class="summary-label">Total Amount</div>
                            <div class="summary-value">
                                {{ formatCurrency(invoice.total_amount) }}
                            </div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-label">Paid Amount</div>
                            <div class="summary-value">
                                {{ formatCurrency(invoice.paid_amount || 0) }}
                            </div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-label">Balance Due</div>
                            <div class="summary-value">
                                {{ formatCurrency(calculateBalanceDue()) }}
                            </div>
                        </div>
                    </div>

                    <div v-if="payments.length > 0" class="payment-history">
                        <h3>Payment History</h3>
                        <table class="payment-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Reference</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="payment in payments"
                                    :key="payment.payment_id"
                                >
                                    <td>
                                        {{ formatDate(payment.payment_date) }}
                                    </td>
                                    <td>
                                        {{ formatCurrency(payment.amount) }}
                                    </td>
                                    <td>{{ payment.payment_method }}</td>
                                    <td>{{ payment.reference || "-" }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else class="no-payments">
                        <p>No payments have been recorded yet.</p>
                    </div>
                </div>
            </div>

            <!-- Related Sales Order -->
            <div v-if="invoice.sales_order" class="detail-card">
                <div class="card-header">
                    <h2>Related Sales Order</h2>
                </div>
                <div class="card-body">
                    <div class="related-order">
                        <div class="info-grid">
                            <div class="info-group">
                                <label>Sales Order Number</label>
                                <div class="info-value">
                                    {{ invoice.sales_order.so_number }}
                                </div>
                            </div>

                            <div class="info-group">
                                <label>Order Date</label>
                                <div class="info-value">
                                    {{
                                        formatDate(invoice.sales_order.so_date)
                                    }}
                                </div>
                            </div>

                            <div class="info-group">
                                <label>Status</label>
                                <div class="info-value">
                                    {{ invoice.sales_order.status }}
                                </div>
                            </div>

                            <div class="info-group">
                                <div class="view-order-btn">
                                    <button
                                        class="btn btn-sm btn-primary"
                                        @click="
                                            viewOrder(invoice.sales_order.so_id)
                                        "
                                    >
                                        <i class="fas fa-eye"></i> View Order
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Modal -->
        <div v-if="showPaymentModal" class="modal">
            <div class="modal-backdrop" @click="closePaymentModal"></div>
            <div class="modal-content modal-sm">
                <div class="modal-header">
                    <h2>Record Payment</h2>
                    <button class="close-btn" @click="closePaymentModal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <form @submit.prevent="submitPayment">
                        <div class="form-group">
                            <label for="payment_amount">Amount</label>
                            <input
                                type="number"
                                id="payment_amount"
                                v-model="paymentForm.amount"
                                min="0.01"
                                :max="calculateBalanceDue()"
                                step="0.01"
                                required
                            />
                            <div class="payment-info">
                                <div>
                                    Total:
                                    {{ formatCurrency(invoice.total_amount) }}
                                </div>
                                <div>
                                    Already Paid:
                                    {{
                                        formatCurrency(invoice.paid_amount || 0)
                                    }}
                                </div>
                                <div>
                                    Balance Due:
                                    {{ formatCurrency(calculateBalanceDue()) }}
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="payment_date">Payment Date</label>
                            <input
                                type="date"
                                id="payment_date"
                                v-model="paymentForm.date"
                                required
                            />
                        </div>

                        <div class="form-group">
                            <label for="payment_method">Payment Method</label>
                            <select
                                id="payment_method"
                                v-model="paymentForm.method"
                                required
                            >
                                <option value="Bank Transfer">
                                    Bank Transfer
                                </option>
                                <option value="Cash">Cash</option>
                                <option value="Check">Check</option>
                                <option value="Credit Card">Credit Card</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="payment_reference"
                                >Reference / Notes</label
                            >
                            <input
                                type="text"
                                id="payment_reference"
                                v-model="paymentForm.reference"
                            />
                        </div>

                        <div class="form-actions">
                            <button
                                type="button"
                                class="btn btn-secondary"
                                @click="closePaymentModal"
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
    </div>
</template>
<!-- Script section -->
<script>
import { ref, computed, onMounted, reactive } from "vue";
import { useRouter, useRoute } from "vue-router";
import SalesInvoiceService from "@/services/SalesInvoiceService";
import UnitOfMeasureService from "@/services/UnitOfMeasureService";

export default {
    name: "SalesInvoiceDetail",
    setup() {
        const router = useRouter();
        const route = useRoute();
        const invoiceId = route.params.id;

        // Data
        const invoice = ref(null);
        const payments = ref([]);
        const unitOfMeasures = ref([]);
        const isLoading = ref(true);
        const isSubmitting = ref(false);
        const showPaymentModal = ref(false);

        // Payment form
        const paymentForm = reactive({
            amount: 0,
            date: new Date().toISOString().substr(0, 10),
            method: "Bank Transfer",
            reference: "",
        });

        // Computed properties
        const canEdit = computed(() => {
            if (!invoice.value) return false;
            return ["Draft"].includes(invoice.value.status);
        });

        const canAddPayment = computed(() => {
            if (!invoice.value) return false;
            return (
                ["Sent", "Partially Paid", "Overdue"].includes(
                    invoice.value.status
                ) && calculateBalanceDue() > 0
            );
        });

        // Fetch invoice and related data
        const fetchInvoice = async () => {
            isLoading.value = true;
            try {
                const result = await SalesInvoiceService.getInvoiceById(
                    invoiceId
                );
                invoice.value = result.data;
                fetchPayments();
            } catch (error) {
                console.error("Error fetching invoice:", error);
                invoice.value = null;
            } finally {
                isLoading.value = false;
            }
        };

        const fetchPayments = async () => {
            try {
                const result = await SalesInvoiceService.getPaymentHistory(
                    invoiceId
                );
                payments.value = result.data || [];
            } catch (error) {
                console.error("Error fetching payments:", error);
                payments.value = [];
            }
        };

        const fetchUnitOfMeasures = async () => {
            try {
                const result = await UnitOfMeasureService.getAll();
                unitOfMeasures.value = result.data || [];
            } catch (error) {
                console.error("Error fetching units of measure:", error);
                unitOfMeasures.value = [];
            }
        };

        // Calculation methods
        const calculateSubtotal = () => {
            if (!invoice.value || !invoice.value.invoiceLines) return 0;
            return invoice.value.invoiceLines.reduce(
                (sum, line) => sum + (line.subtotal || 0),
                0
            );
        };

        const calculateTotalDiscount = () => {
            if (!invoice.value || !invoice.value.invoiceLines) return 0;
            return invoice.value.invoiceLines.reduce(
                (sum, line) => sum + (line.discount || 0),
                0
            );
        };

        const calculateTotalTax = () => {
            if (!invoice.value || !invoice.value.invoiceLines) return 0;
            return invoice.value.invoiceLines.reduce(
                (sum, line) => sum + (line.tax || 0),
                0
            );
        };

        const calculateBalanceDue = () => {
            if (!invoice.value) return 0;
            return (
                invoice.value.total_amount - (invoice.value.paid_amount || 0)
            );
        };

        // Helper methods
        const formatDate = (dateString) => {
            if (!dateString) return "-";
            const date = new Date(dateString);
            return date.toLocaleDateString("en-US", {
                day: "2-digit",
                month: "short",
                year: "numeric",
            });
        };

        const formatCurrency = (value) => {
            return new Intl.NumberFormat("id-ID", {
                style: "currency",
                currency: "IDR",
            }).format(value || 0);
        };

        const getUomSymbol = (uomId) => {
            const uom = unitOfMeasures.value.find((u) => u.uom_id === uomId);
            return uom ? uom.symbol : "";
        };

        const getStatusClass = (status) => {
            switch (status) {
                case "Draft":
                    return "status-draft";
                case "Sent":
                    return "status-sent";
                case "Paid":
                    return "status-paid";
                case "Partially Paid":
                    return "status-partial";
                case "Overdue":
                    return "status-overdue";
                case "Cancelled":
                    return "status-cancelled";
                default:
                    return "";
            }
        };

        // Action methods
        const goBack = () => {
            router.push("/sales/invoices");
        };

        const editInvoice = () => {
            router.push(`/sales/invoices/${invoiceId}/edit`);
        };

        const printInvoice = () => {
            router.push(`/sales/invoices/${invoiceId}/print`);
        };

        const viewOrder = (orderId) => {
            router.push(`/sales/orders/${orderId}`);
        };

        const markAsSent = async () => {
            try {
                await SalesInvoiceService.updateStatus(invoiceId, "Sent");
                fetchInvoice();
            } catch (error) {
                console.error("Error updating invoice status:", error);
                alert("Failed to update invoice status. Please try again.");
            }
        };

        const recordPayment = () => {
            paymentForm.amount = calculateBalanceDue();
            showPaymentModal.value = true;
        };

        const closePaymentModal = () => {
            showPaymentModal.value = false;
        };

        const submitPayment = async () => {
            isSubmitting.value = true;
            try {
                await SalesInvoiceService.recordPayment(invoiceId, {
                    amount: paymentForm.amount,
                    payment_date: paymentForm.date,
                    payment_method: paymentForm.method,
                    reference: paymentForm.reference,
                });

                closePaymentModal();
                fetchInvoice(); // Refresh invoice data to update payment status
            } catch (error) {
                console.error("Error recording payment:", error);
                alert("Failed to record payment. Please try again.");
            } finally {
                isSubmitting.value = false;
            }
        };

        onMounted(() => {
            fetchInvoice();
            fetchUnitOfMeasures();
        });

        return {
            invoice,
            payments,
            isLoading,
            isSubmitting,
            showPaymentModal,
            paymentForm,
            canEdit,
            canAddPayment,
            formatDate,
            formatCurrency,
            getUomSymbol,
            getStatusClass,
            calculateSubtotal,
            calculateTotalDiscount,
            calculateTotalTax,
            calculateBalanceDue,
            goBack,
            editInvoice,
            printInvoice,
            viewOrder,
            markAsSent,
            recordPayment,
            closePaymentModal,
            submitPayment,
        };
    },
};
</script>
<!-- Style section -->
<style scoped>
.invoice-detail {
    padding: 1rem 0;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.page-header h1 {
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0;
    color: var(--gray-800);
}

.page-actions {
    display: flex;
    gap: 0.75rem;
}

.btn-group {
    display: flex;
    gap: 0.5rem;
}

.invoice-container {
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
    background-color: var(--gray-50);
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--gray-200);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-header h2 {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0;
    color: var(--gray-800);
}

.card-body {
    padding: 1.5rem;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
}

.customer-info {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
}

.info-group {
    margin-bottom: 0.75rem;
}

.info-group label {
    display: block;
    font-size: 0.75rem;
    color: var(--gray-500);
    margin-bottom: 0.25rem;
}

.info-value {
    font-size: 0.875rem;
    color: var(--gray-800);
    font-weight: 500;
}

.invoice-status {
    font-size: 0.75rem;
    font-weight: 500;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
}

.status-draft {
    background-color: var(--gray-200);
    color: var(--gray-700);
}

.status-sent {
    background-color: var(--primary-bg);
    color: var(--primary-color);
}

.status-paid {
    background-color: var(--success-bg);
    color: var(--success-color);
}

.status-partial {
    background-color: var(--warning-bg);
    color: var(--warning-color);
}

.status-overdue {
    background-color: var(--danger-bg);
    color: var(--danger-color);
}

.status-cancelled {
    background-color: var(--gray-300);
    color: var(--gray-800);
}

.items-table {
    width: 100%;
    border-collapse: collapse;
}

.items-table th {
    text-align: left;
    padding: 0.75rem 1rem;
    background-color: var(--gray-50);
    border-bottom: 1px solid var(--gray-200);
    font-weight: 500;
    color: var(--gray-600);
    font-size: 0.75rem;
}

.items-table td {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--gray-100);
    vertical-align: middle;
}

.item-info {
    display: flex;
    flex-direction: column;
}

.item-code {
    font-size: 0.75rem;
    color: var(--gray-500);
}

.item-name {
    font-weight: 500;
}

.totals-label {
    text-align: right;
    font-weight: 500;
    color: var(--gray-700);
}

.totals-value {
    text-align: right;
    font-weight: 500;
}

.grand-total td {
    font-weight: 600;
    color: var(--gray-800);
    font-size: 1rem;
}

.payment-summary {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.summary-item {
    background-color: var(--gray-50);
    padding: 1rem;
    border-radius: 0.375rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.summary-label {
    font-size: 0.75rem;
    color: var(--gray-600);
    font-weight: 500;
}

.summary-value {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--gray-800);
}

.payment-history h3 {
    font-size: 1rem;
    font-weight: 600;
    margin: 0 0 1rem 0;
    color: var(--gray-700);
}

.payment-table {
    width: 100%;
    border-collapse: collapse;
}

.payment-table th {
    text-align: left;
    padding: 0.75rem 1rem;
    background-color: var(--gray-50);
    border-bottom: 1px solid var(--gray-200);
    font-weight: 500;
    color: var(--gray-600);
    font-size: 0.75rem;
}

.payment-table td {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--gray-100);
}

.no-payments {
    text-align: center;
    padding: 2rem;
    color: var(--gray-500);
    font-style: italic;
}

.loading-indicator {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 4rem 0;
    color: var(--gray-500);
    font-size: 1rem;
}

.loading-indicator i {
    margin-right: 0.5rem;
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 0;
    text-align: center;
}

.empty-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
    color: var(--gray-300);
}

.empty-state h3 {
    font-size: 1.25rem;
    margin: 0 0 0.5rem 0;
    color: var(--gray-700);
}

.empty-state p {
    margin: 0 0 1.5rem 0;
    color: var(--gray-500);
}

.related-order {
    padding: 0.5rem 0;
}

.view-order-btn {
    display: flex;
    justify-content: flex-end;
    align-items: flex-end;
    height: 100%;
}

/* Modal styles */
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
    max-width: 500px;
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
    border-bottom: 1px solid var(--gray-200);
}

.modal-header h2 {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
    color: var(--gray-800);
}

.close-btn {
    background: none;
    border: none;
    color: var(--gray-500);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.25rem;
    border-radius: 0.25rem;
}

.close-btn:hover {
    background-color: var(--gray-100);
    color: var(--gray-800);
}

.modal-body {
    padding: 1.5rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: var(--gray-700);
    font-size: 0.875rem;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 0.625rem;
    border: 1px solid var(--gray-200);
    border-radius: 0.375rem;
    font-size: 0.875rem;
}

.payment-info {
    margin-top: 0.5rem;
    padding: 0.5rem;
    background-color: var(--gray-50);
    border-radius: 0.25rem;
    font-size: 0.75rem;
    color: var(--gray-700);
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    margin-top: 1rem;
}

.btn {
    padding: 0.625rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    border-radius: 0.375rem;
    cursor: pointer;
    display: flex;
    align-items: center;
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

.btn-secondary {
    background-color: var(--gray-200);
    color: var(--gray-800);
}

.btn-secondary:hover {
    background-color: var(--gray-300);
}

.btn-success {
    background-color: var(--success-color);
    color: white;
}

.btn-success:hover {
    background-color: var(--success-light);
}

.btn-info {
    background-color: var(--primary-light);
    color: white;
}

.btn-info:hover {
    background-color: var(--primary-color);
}

.btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }

    .btn-group {
        flex-wrap: wrap;
    }

    .info-grid,
    .customer-info,
    .payment-summary {
        grid-template-columns: 1fr;
        gap: 0.75rem;
    }

    .items-table,
    .payment-table {
        display: block;
        overflow-x: auto;
    }
}
</style>
