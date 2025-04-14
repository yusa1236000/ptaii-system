<!-- src/views/sales/SalesInvoiceList.vue -->
<template>
    <div class="sales-invoices">
        <div class="page-header">
            <h1>Sales Invoices</h1>
            <button class="btn btn-primary" @click="openCreateInvoice">
                <i class="fas fa-plus"></i> Create New Invoice
            </button>
        </div>

        <!-- Search and Filter Section -->
        <SearchFilter
            v-model:value="searchQuery"
            placeholder="Search invoices..."
            @search="handleSearch"
            @clear="clearSearch"
        >
            <template #filters>
                <div class="filter-group">
                    <label for="statusFilter">Status</label>
                    <select
                        id="statusFilter"
                        v-model="statusFilter"
                        @change="applyFilters"
                    >
                        <option value="">All Status</option>
                        <option value="Draft">Draft</option>
                        <option value="Sent">Sent</option>
                        <option value="Paid">Paid</option>
                        <option value="Partially Paid">Partially Paid</option>
                        <option value="Overdue">Overdue</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="dateRangeFilter">Date Range</label>
                    <select
                        id="dateRangeFilter"
                        v-model="dateRangeFilter"
                        @change="applyFilters"
                    >
                        <option value="all">All Time</option>
                        <option value="today">Today</option>
                        <option value="week">This Week</option>
                        <option value="month">This Month</option>
                        <option value="custom">Custom</option>
                    </select>
                </div>
            </template>
        </SearchFilter>

        <!-- Custom Date Range (when Custom is selected) -->
        <div v-if="dateRangeFilter === 'custom'" class="custom-date-range">
            <div class="date-range-inputs">
                <div class="filter-group">
                    <label for="startDate">Start Date</label>
                    <input
                        type="date"
                        id="startDate"
                        v-model="customDateRange.startDate"
                        @change="applyFilters"
                    />
                </div>

                <div class="filter-group">
                    <label for="endDate">End Date</label>
                    <input
                        type="date"
                        id="endDate"
                        v-model="customDateRange.endDate"
                        @change="applyFilters"
                    />
                </div>
            </div>
        </div>

        <!-- Invoices Grid -->
        <div class="invoices-container">
            <div v-if="isLoading" class="loading-indicator">
                <i class="fas fa-spinner fa-spin"></i> Loading invoices...
            </div>

            <div v-else-if="filteredInvoices.length === 0" class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <h3>No invoices found</h3>
                <p>
                    Try adjusting your search or filter, or create a new
                    invoice.
                </p>
            </div>

            <div v-else class="invoices-grid">
                <div
                    v-for="invoice in filteredInvoices"
                    :key="invoice.invoice_id"
                    class="invoice-card"
                >
                    <div class="invoice-header">
                        <div
                            class="invoice-status"
                            :class="getStatusClass(invoice.status)"
                        >
                            {{ invoice.status }}
                        </div>
                        <div class="invoice-actions">
                            <button
                                class="action-btn"
                                title="Edit Invoice"
                                @click.stop="editInvoice(invoice)"
                            >
                                <i class="fas fa-edit"></i>
                            </button>
                            <button
                                class="action-btn"
                                title="Delete Invoice"
                                @click.stop="confirmDelete(invoice)"
                                v-if="canDelete(invoice)"
                            >
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>

                    <div class="invoice-content" @click="viewInvoice(invoice)">
                        <div class="invoice-id">
                            {{ invoice.invoice_number }}
                        </div>
                        <div class="invoice-customer">
                            {{ invoice.customer?.name }}
                        </div>
                        <div class="invoice-dates">
                            <div class="date-item">
                                <i class="fas fa-calendar"></i>
                                <span>{{
                                    formatDate(invoice.invoice_date)
                                }}</span>
                            </div>
                            <div class="date-item">
                                <i class="fas fa-calendar-check"></i>
                                <span
                                    >Due:
                                    {{ formatDate(invoice.due_date) }}</span
                                >
                            </div>
                        </div>

                        <div class="invoice-amount">
                            <div class="amount-item">
                                <strong>Total:</strong>
                                {{ formatCurrency(invoice.total_amount) }}
                            </div>
                            <div class="amount-item">
                                <strong>Paid:</strong>
                                {{ formatCurrency(invoice.paid_amount || 0) }}
                            </div>
                        </div>
                    </div>

                    <div class="invoice-footer">
                        <button
                            v-if="invoice.status === 'Draft'"
                            class="btn btn-sm btn-secondary"
                            @click.stop="markAsSent(invoice)"
                        >
                            <i class="fas fa-paper-plane"></i> Send
                        </button>
                        <button
                            v-if="
                                ['Sent', 'Partially Paid', 'Overdue'].includes(
                                    invoice.status
                                )
                            "
                            class="btn btn-sm btn-success"
                            @click.stop="recordPayment(invoice)"
                        >
                            <i class="fas fa-money-bill"></i> Record Payment
                        </button>
                        <button
                            class="btn btn-sm btn-primary"
                            @click.stop="printInvoice(invoice)"
                        >
                            <i class="fas fa-print"></i> Print
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <PaginationComponent
            v-if="filteredInvoices.length > 0"
            :current-page="currentPage"
            :total-pages="totalPages"
            :from="paginationInfo.from"
            :to="paginationInfo.to"
            :total="paginationInfo.total"
            @page-changed="goToPage"
        />

        <!-- Delete Confirmation Modal -->
        <ConfirmationModal
            v-if="showDeleteModal"
            title="Confirm Delete"
            :message="`Are you sure you want to delete invoice <strong>${invoiceToDelete.invoice_number}</strong>?<br>This action cannot be undone.`"
            confirm-button-text="Delete"
            confirm-button-class="btn btn-danger"
            @confirm="deleteInvoice"
            @close="closeDeleteModal"
        />

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
                                :max="currentInvoice?.remaining_amount"
                                step="0.01"
                                required
                            />
                            <div class="payment-info">
                                <div>
                                    Total:
                                    {{
                                        formatCurrency(
                                            currentInvoice?.total_amount
                                        )
                                    }}
                                </div>
                                <div>
                                    Already Paid:
                                    {{
                                        formatCurrency(
                                            currentInvoice?.paid_amount || 0
                                        )
                                    }}
                                </div>
                                <div>
                                    Remaining:
                                    {{
                                        formatCurrency(
                                            currentInvoice?.remaining_amount
                                        )
                                    }}
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

<script>
import { ref, computed, onMounted, reactive } from "vue";
import { useRouter } from "vue-router";
import axios from "axios";

export default {
    name: "SalesInvoiceList",
    setup() {
        const router = useRouter();
        const invoices = ref([]);
        const isLoading = ref(true);
        const isSubmitting = ref(false);

        // Search and filtering
        const searchQuery = ref("");
        const statusFilter = ref("");
        const dateRangeFilter = ref("month");
        const customDateRange = ref({
            startDate: new Date().toISOString().substr(0, 10),
            endDate: new Date().toISOString().substr(0, 10),
        });

        // Pagination
        const currentPage = ref(1);
        const itemsPerPage = ref(12);
        const totalItems = ref(0);
        const totalPages = ref(1);

        // Modals
        const showDeleteModal = ref(false);
        const showPaymentModal = ref(false);
        const invoiceToDelete = ref({});
        const currentInvoice = ref(null);

        // Payment form
        const paymentForm = reactive({
            invoice_id: null,
            amount: 0,
            date: new Date().toISOString().substr(0, 10),
            method: "Bank Transfer",
            reference: "",
        });

        // Fetch invoices from API
        const fetchInvoices = async () => {
            isLoading.value = true;
            try {
                const params = {
                    page: currentPage.value,
                    per_page: itemsPerPage.value,
                    search: searchQuery.value,
                    status: statusFilter.value,
                };

                // Apply date range if selected
                if (dateRangeFilter.value === "custom") {
                    params.start_date = customDateRange.value.startDate;
                    params.end_date = customDateRange.value.endDate;
                } else if (dateRangeFilter.value !== "all") {
                    params.date_range = dateRangeFilter.value;
                }

                const response = await axios.get("/sales/invoices", { params });
                invoices.value = response.data.data;

                // Set pagination info if available
                if (response.data.meta) {
                    totalItems.value = response.data.meta.total;
                    totalPages.value = response.data.meta.last_page;
                }
            } catch (error) {
                console.error("Error fetching invoices:", error);
                invoices.value = [];
            } finally {
                isLoading.value = false;
            }
        };

        // Calculate filtered invoices and pagination info
        const filteredInvoices = computed(() => {
            return invoices.value;
        });

        const paginationInfo = computed(() => {
            const total = totalItems.value;
            const from =
                total === 0
                    ? 0
                    : (currentPage.value - 1) * itemsPerPage.value + 1;
            const to = Math.min(from + itemsPerPage.value - 1, total);

            return { from, to, total };
        });

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

        const canDelete = (invoice) => {
            // Only allow deletion of draft invoices
            return invoice.status === "Draft";
        };

        // Navigation methods
        const openCreateInvoice = () => {
            router.push("/sales/invoices/create");
        };

        const viewInvoice = (invoice) => {
            router.push(`/sales/invoices/${invoice.invoice_id}`);
        };

        const editInvoice = (invoice) => {
            router.push(`/sales/invoices/${invoice.invoice_id}/edit`);
        };

        const printInvoice = (invoice) => {
            router.push(`/sales/invoices/${invoice.invoice_id}/print`);
        };

        // Search and filter methods
        const handleSearch = () => {
            currentPage.value = 1;
            fetchInvoices();
        };

        const clearSearch = () => {
            searchQuery.value = "";
            handleSearch();
        };

        const applyFilters = () => {
            currentPage.value = 1;
            fetchInvoices();
        };

        const goToPage = (page) => {
            currentPage.value = page;
            fetchInvoices();
        };

        // Delete invoice methods
        const confirmDelete = (invoice) => {
            invoiceToDelete.value = invoice;
            showDeleteModal.value = true;
        };

        const closeDeleteModal = () => {
            showDeleteModal.value = false;
        };

        const deleteInvoice = async () => {
            try {
                await axios.delete(
                    `/sales/invoices/${invoiceToDelete.value.invoice_id}`
                );
                fetchInvoices();
                closeDeleteModal();
            } catch (error) {
                console.error("Error deleting invoice:", error);
                alert("Failed to delete invoice. Please try again.");
            }
        };

        // Payment methods
        const recordPayment = (invoice) => {
            currentInvoice.value = {
                ...invoice,
                remaining_amount:
                    invoice.total_amount - (invoice.paid_amount || 0),
            };
            paymentForm.invoice_id = invoice.invoice_id;
            paymentForm.amount = currentInvoice.value.remaining_amount;
            showPaymentModal.value = true;
        };

        const closePaymentModal = () => {
            showPaymentModal.value = false;
        };

        const submitPayment = async () => {
            isSubmitting.value = true;
            try {
                await axios.post(
                    `/sales/invoices/${paymentForm.invoice_id}/payments`,
                    paymentForm
                );
                fetchInvoices();
                closePaymentModal();
            } catch (error) {
                console.error("Error recording payment:", error);
                alert("Failed to record payment. Please try again.");
            } finally {
                isSubmitting.value = false;
            }
        };

        // Status update methods
        const markAsSent = async (invoice) => {
            try {
                await axios.put(
                    `/sales/invoices/${invoice.invoice_id}/status`,
                    {
                        status: "Sent",
                    }
                );
                fetchInvoices();
            } catch (error) {
                console.error("Error updating invoice status:", error);
                alert("Failed to update invoice status. Please try again.");
            }
        };

        onMounted(() => {
            fetchInvoices();
        });

        return {
            invoices,
            filteredInvoices,
            isLoading,
            isSubmitting,
            searchQuery,
            statusFilter,
            dateRangeFilter,
            customDateRange,
            currentPage,
            totalPages,
            paginationInfo,
            showDeleteModal,
            showPaymentModal,
            invoiceToDelete,
            currentInvoice,
            paymentForm,
            formatDate,
            formatCurrency,
            getStatusClass,
            canDelete,
            openCreateInvoice,
            viewInvoice,
            editInvoice,
            printInvoice,
            handleSearch,
            clearSearch,
            applyFilters,
            goToPage,
            confirmDelete,
            closeDeleteModal,
            deleteInvoice,
            recordPayment,
            closePaymentModal,
            submitPayment,
            markAsSent,
        };
    },
};
</script>

<style scoped>
.sales-invoices {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.page-header h1 {
    margin: 0;
    font-size: 1.5rem;
    color: var(--gray-800);
}

.custom-date-range {
    background-color: var(--gray-50);
    border: 1px solid var(--gray-200);
    border-radius: 0.375rem;
    padding: 1rem;
    margin-bottom: 1rem;
}

.date-range-inputs {
    display: flex;
    gap: 1rem;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
}

.filter-group label {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--gray-700);
}

.filter-group select,
.filter-group input {
    padding: 0.5rem;
    border: 1px solid var(--gray-200);
    border-radius: 0.375rem;
    font-size: 0.875rem;
    min-width: 8rem;
}

.invoices-container {
    width: 100%;
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
    color: var(--gray-500);
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
    margin: 0;
    font-size: 0.875rem;
}

.invoices-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
}

.invoice-card {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transition: box-shadow 0.3s;
}

.invoice-card:hover {
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.invoice-header {
    padding: 0.75rem 1rem;
    background-color: var(--gray-50);
    border-bottom: 1px solid var(--gray-200);
    display: flex;
    justify-content: space-between;
    align-items: center;
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

.invoice-actions {
    display: flex;
    gap: 0.5rem;
}

.action-btn {
    background: none;
    border: none;
    color: var(--gray-500);
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 0.25rem;
    transition: background-color 0.2s, color 0.2s;
}

.action-btn:hover {
    background-color: var(--gray-200);
    color: var(--gray-800);
}

.invoice-content {
    padding: 1.25rem;
    flex: 1;
    cursor: pointer;
}

.invoice-id {
    font-size: 1.125rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: var(--gray-800);
}

.invoice-customer {
    font-size: 0.875rem;
    color: var(--gray-700);
    margin-bottom: 1rem;
}

.invoice-dates {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
    margin-bottom: 1rem;
}

.date-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.75rem;
    color: var(--gray-600);
}

.invoice-amount {
    border-top: 1px dashed var(--gray-200);
    padding-top: 0.75rem;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.amount-item {
    font-size: 0.875rem;
    color: var(--gray-700);
}

.invoice-footer {
    padding: 0.75rem 1rem;
    border-top: 1px solid var(--gray-200);
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
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

.btn-danger {
    background-color: var(--danger-color);
    color: white;
}

.btn-danger:hover {
    background-color: var(--danger-light);
}

.btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
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

@media (max-width: 768px) {
    .date-range-inputs {
        flex-direction: column;
    }

    .invoices-grid {
        grid-template-columns: 1fr;
    }
}
</style>
