<!-- src/views/purchasing/VendorInvoiceDetail.vue -->
<template>
    <div class="invoice-detail-container">
        <div class="page-header">
            <div class="header-left">
                <router-link to="/purchasing/vendor-invoices" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to Vendor Invoices
                </router-link>
                <h1>
                    {{ vendorInvoice?.invoice_number || "Invoice Details" }}
                </h1>
            </div>
            <div class="header-actions">
                <div v-if="!isLoading && vendorInvoice" class="status-section">
                    <span
                        :class="[
                            'status-badge',
                            getStatusClass(vendorInvoice.status),
                        ]"
                    >
                        {{ formatStatus(vendorInvoice.status) }}
                    </span>
                </div>

                <div class="action-buttons">
                    <router-link
                        v-if="
                            vendorInvoice && vendorInvoice.status === 'pending'
                        "
                        :to="`/purchasing/vendor-invoices/${id}/edit`"
                        class="btn btn-primary"
                    >
                        <i class="fas fa-edit"></i> Edit
                    </router-link>

                    <router-link
                        v-if="
                            vendorInvoice && vendorInvoice.status === 'pending'
                        "
                        :to="`/purchasing/vendor-invoices/${id}/approve`"
                        class="btn btn-success"
                    >
                        <i class="fas fa-check-circle"></i> Approve
                    </router-link>

                    <router-link
                        v-if="
                            vendorInvoice &&
                            (vendorInvoice.status === 'approved' ||
                                vendorInvoice.status === 'partially_paid')
                        "
                        :to="`/purchasing/vendor-invoices/${id}/payment`"
                        class="btn btn-info"
                    >
                        <i class="fas fa-money-bill-wave"></i> Record Payment
                    </router-link>

                    <button
                        v-if="
                            vendorInvoice && vendorInvoice.status === 'pending'
                        "
                        @click="confirmDelete"
                        class="btn btn-danger"
                    >
                        <i class="fas fa-trash"></i> Delete
                    </button>

                    <button class="btn btn-secondary" @click="printInvoice">
                        <i class="fas fa-print"></i> Print
                    </button>
                </div>
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

        <div v-else class="invoice-detail-content">
            <!-- Basic Information Card -->
            <div class="detail-card">
                <div class="card-header">
                    <h2 class="card-title">Invoice Information</h2>
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
                            <span class="info-label">Days Until Due</span>
                            <span
                                class="info-value"
                                :class="getDueDateClass()"
                                >{{ calculateDaysUntilDue() }}</span
                            >
                        </div>
                        <div class="info-item">
                            <span class="info-label">Vendor</span>
                            <span class="info-value">
                                <router-link
                                    :to="`/purchasing/vendors/${vendorInvoice.vendor.vendor_id}`"
                                >
                                    {{ vendorInvoice.vendor.name }}
                                </router-link>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Purchase Order</span>
                            <span class="info-value">
                                <router-link
                                    :to="`/purchasing/orders/${vendorInvoice.purchase_order.po_id}`"
                                >
                                    {{ vendorInvoice.purchase_order.po_number }}
                                </router-link>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Total Amount</span>
                            <span class="info-value">{{
                                formatCurrency(vendorInvoice.total_amount)
                            }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Tax Amount</span>
                            <span class="info-value">{{
                                formatCurrency(vendorInvoice.tax_amount)
                            }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Information Card -->
            <div v-if="vendorInvoice.status !== 'pending'" class="detail-card">
                <div class="card-header">
                    <h2 class="card-title">Payment Information</h2>
                </div>
                <div class="card-body">
                    <div
                        v-if="
                            vendorInvoice.payments &&
                            vendorInvoice.payments.length > 0
                        "
                    >
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Payment Date</th>
                                    <th>Reference Number</th>
                                    <th>Amount</th>
                                    <th>Payment Method</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="payment in vendorInvoice.payments"
                                    :key="payment.payment_id"
                                >
                                    <td>
                                        {{ formatDate(payment.payment_date) }}
                                    </td>
                                    <td>
                                        {{ payment.reference_number || "N/A" }}
                                    </td>
                                    <td>
                                        {{ formatCurrency(payment.amount) }}
                                    </td>
                                    <td>{{ payment.payment_method }}</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="2" class="text-right">
                                        <strong>Total Paid:</strong>
                                    </td>
                                    <td>
                                        {{
                                            formatCurrency(calculateTotalPaid())
                                        }}
                                    </td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="2" class="text-right">
                                        <strong>Balance Due:</strong>
                                    </td>
                                    <td :class="getBalanceClass()">
                                        {{
                                            formatCurrency(
                                                calculateBalanceDue()
                                            )
                                        }}
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div v-else class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <h3>No Payments Recorded</h3>
                        <p>
                            There are no payments recorded for this invoice yet.
                        </p>
                        <router-link
                            v-if="
                                vendorInvoice.status === 'approved' ||
                                vendorInvoice.status === 'partially_paid'
                            "
                            :to="`/purchasing/vendor-invoices/${id}/payment`"
                            class="btn btn-primary"
                        >
                            Record Payment
                        </router-link>
                    </div>
                </div>
            </div>

            <!-- Invoice Lines Card -->
            <div class="detail-card">
                <div class="card-header">
                    <h2 class="card-title">Invoice Items</h2>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Item</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Tax</th>
                                    <th>Subtotal</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(line, index) in vendorInvoice.lines"
                                    :key="line.line_id"
                                >
                                    <td>{{ index + 1 }}</td>
                                    <td>
                                        <div class="item-name">
                                            {{ line.item.name }}
                                        </div>
                                        <div class="item-code">
                                            {{ line.item.item_code }}
                                        </div>
                                    </td>
                                    <td>{{ line.quantity }}</td>
                                    <td>
                                        {{ formatCurrency(line.unit_price) }}
                                    </td>
                                    <td>{{ formatCurrency(line.tax) }}</td>
                                    <td>{{ formatCurrency(line.subtotal) }}</td>
                                    <td>{{ formatCurrency(line.total) }}</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5" class="text-right">
                                        <strong>Subtotal:</strong>
                                    </td>
                                    <td colspan="2">
                                        {{
                                            formatCurrency(calculateSubtotal())
                                        }}
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="5" class="text-right">
                                        <strong>Tax Total:</strong>
                                    </td>
                                    <td colspan="2">
                                        {{
                                            formatCurrency(
                                                vendorInvoice.tax_amount
                                            )
                                        }}
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="5" class="text-right">
                                        <strong>Grand Total:</strong>
                                    </td>
                                    <td colspan="2" class="grand-total">
                                        {{
                                            formatCurrency(
                                                vendorInvoice.total_amount
                                            )
                                        }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Confirmation Modal for Delete -->
        <ConfirmationModal
            v-if="showDeleteModal"
            :title="'Delete Vendor Invoice'"
            :message="
                'Are you sure you want to delete vendor invoice <strong>' +
                (vendorInvoice?.invoice_number || '') +
                '</strong>? This action cannot be undone.'
            "
            :confirm-button-text="'Delete'"
            :confirm-button-class="'btn btn-danger'"
            @confirm="deleteVendorInvoice"
            @close="closeDeleteModal"
        />
    </div>
</template>

<script>
import { ref, onMounted } from "vue";
import { useRouter } from "vue-router";
import VendorInvoiceService from "@/services/VendorInvoiceService";
import ConfirmationModal from "@/components/common/ConfirmationModal.vue";

export default {
    name: "VendorInvoiceDetail",
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
        const vendorInvoice = ref(null);
        const isLoading = ref(true);
        const showDeleteModal = ref(false);

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

        // Calculate days until due
        const calculateDaysUntilDue = () => {
            if (!vendorInvoice.value || !vendorInvoice.value.due_date)
                return "N/A";

            const dueDate = new Date(vendorInvoice.value.due_date);
            const today = new Date();
            const timeDiff = dueDate.getTime() - today.getTime();
            const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24));

            if (daysDiff < 0) {
                return `Overdue by ${Math.abs(daysDiff)} days`;
            } else if (daysDiff === 0) {
                return "Due today";
            } else {
                return `${daysDiff} days`;
            }
        };

        // Get CSS class for due date
        const getDueDateClass = () => {
            if (!vendorInvoice.value || !vendorInvoice.value.due_date)
                return "";

            const dueDate = new Date(vendorInvoice.value.due_date);
            const today = new Date();
            const timeDiff = dueDate.getTime() - today.getTime();
            const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24));

            if (daysDiff < 0) {
                return "text-danger";
            } else if (daysDiff <= 3) {
                return "text-warning";
            } else {
                return "text-success";
            }
        };

        // Calculate subtotal
        const calculateSubtotal = () => {
            if (!vendorInvoice.value || !vendorInvoice.value.lines) return 0;

            return vendorInvoice.value.lines.reduce((total, line) => {
                return total + (line.subtotal || 0);
            }, 0);
        };

        // Calculate total paid
        const calculateTotalPaid = () => {
            if (!vendorInvoice.value || !vendorInvoice.value.payments) return 0;

            return vendorInvoice.value.payments.reduce((total, payment) => {
                return total + (payment.amount || 0);
            }, 0);
        };

        // Calculate balance due
        const calculateBalanceDue = () => {
            if (!vendorInvoice.value) return 0;

            const totalAmount = vendorInvoice.value.total_amount || 0;
            const totalPaid = calculateTotalPaid();

            return totalAmount - totalPaid;
        };

        // Get CSS class for balance
        const getBalanceClass = () => {
            const balance = calculateBalanceDue();

            if (balance <= 0) {
                return "text-success";
            } else {
                return "text-danger";
            }
        };

        // Confirm delete
        const confirmDelete = () => {
            showDeleteModal.value = true;
        };

        // Close delete modal
        const closeDeleteModal = () => {
            showDeleteModal.value = false;
        };

        // Delete vendor invoice
        const deleteVendorInvoice = async () => {
            try {
                await VendorInvoiceService.deleteVendorInvoice(props.id);
                router.push("/purchasing/vendor-invoices");
            } catch (error) {
                console.error("Error deleting vendor invoice:", error);

                if (
                    error.response &&
                    error.response.data &&
                    error.response.data.message
                ) {
                    alert(error.response.data.message);
                } else {
                    alert("Failed to delete vendor invoice. Please try again.");
                }

                closeDeleteModal();
            }
        };

        // Print invoice
        const printInvoice = () => {
            window.print();
        };

        // Initialize
        onMounted(() => {
            fetchVendorInvoice();
        });

        return {
            vendorInvoice,
            isLoading,
            showDeleteModal,
            formatDate,
            formatCurrency,
            formatStatus,
            getStatusClass,
            calculateDaysUntilDue,
            getDueDateClass,
            calculateSubtotal,
            calculateTotalPaid,
            calculateBalanceDue,
            getBalanceClass,
            confirmDelete,
            closeDeleteModal,
            deleteVendorInvoice,
            printInvoice,
        };
    },
};
</script>

<style scoped>
.invoice-detail-container {
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

.invoice-detail-content {
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

.text-danger {
    color: var(--danger-color);
}

.text-warning {
    color: var(--warning-color);
}

.text-success {
    color: var(--success-color);
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

.data-table tfoot td {
    padding: 0.75rem;
    border-top: 1px solid var(--gray-200);
}

.text-right {
    text-align: right;
}

.grand-total {
    font-weight: 600;
    font-size: 1rem;
    color: var(--gray-900);
}

.item-name {
    font-weight: 500;
}

.item-code {
    font-size: 0.75rem;
    color: var(--gray-500);
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem 2rem;
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
    margin-bottom: 1.5rem;
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

.btn-primary:hover {
    background-color: var(--primary-dark);
}

.btn-success {
    background-color: var(--success-color);
    color: white;
}

.btn-success:hover {
    background-color: var(--success-dark);
}

.btn-info {
    background-color: var(--info-color);
    color: white;
}

.btn-info:hover {
    background-color: var(--info-dark);
}

.btn-secondary {
    background-color: var(--gray-200);
    color: var(--gray-800);
}

.btn-secondary:hover {
    background-color: var(--gray-300);
}

.btn-danger {
    background-color: var(--danger-color);
    color: white;
}

.btn-danger:hover {
    background-color: var(--danger-dark);
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        gap: 1rem;
    }

    .header-actions {
        align-items: flex-start;
        width: 100%;
    }

    .action-buttons {
        flex-wrap: wrap;
    }

    .info-grid {
        grid-template-columns: 1fr;
    }
}

@media print {
    .back-link,
    .header-actions,
    .tabs-header {
        display: none !important;
    }

    .invoice-detail-container {
        padding: 0;
    }

    .detail-card,
    .tabs-container {
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
