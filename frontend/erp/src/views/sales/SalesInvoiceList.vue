<!-- src/views/sales/SalesInvoiceList.vue -->
<template>
    <div class="sales-invoices">
        <!-- Search and Filter Section -->
        <div class="page-actions">
            <SearchFilter
                v-model:value="searchQuery"
                placeholder="Cari faktur..."
                @search="handleSearch"
                @clear="clearSearch"
            >
                <template #filters>
                    <div class="filter-group">
                        <label>Status</label>
                        <select v-model="statusFilter" @change="applyFilters">
                            <option value="">Semua Status</option>
                            <option value="Draft">Draft</option>
                            <option value="Sent">Terkirim</option>
                            <option value="Paid">Dibayar</option>
                            <option value="Partially Paid">
                                Dibayar Sebagian
                            </option>
                            <option value="Overdue">Jatuh Tempo</option>
                            <option value="Cancelled">Dibatalkan</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Periode</label>
                        <select
                            v-model="dateRangeFilter"
                            @change="applyFilters"
                        >
                            <option value="all">Semua Waktu</option>
                            <option value="today">Hari Ini</option>
                            <option value="week">Minggu Ini</option>
                            <option value="month">Bulan Ini</option>
                            <option value="custom">Kustom</option>
                        </select>
                    </div>
                </template>

                <template #actions>
                    <button class="btn btn-primary" @click="createInvoice">
                        <i class="fas fa-plus"></i> Buat Faktur
                    </button>
                </template>
            </SearchFilter>

            <!-- Custom Date Range (when Custom is selected) -->
            <div v-if="dateRangeFilter === 'custom'" class="custom-date-range">
                <div class="date-range-inputs">
                    <div class="filter-group">
                        <label for="startDate">Tanggal Mulai</label>
                        <input
                            type="date"
                            id="startDate"
                            v-model="customDateRange.startDate"
                            @change="applyFilters"
                        />
                    </div>

                    <div class="filter-group">
                        <label for="endDate">Tanggal Akhir</label>
                        <input
                            type="date"
                            id="endDate"
                            v-model="customDateRange.endDate"
                            @change="applyFilters"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- DataTable Component -->
        <DataTable
            :columns="columns"
            :items="invoices"
            :is-loading="isLoading"
            keyField="invoice_id"
            emptyIcon="fas fa-file-invoice-dollar"
            emptyTitle="Tidak ada faktur"
            emptyMessage="Tidak ada faktur yang tersedia untuk ditampilkan."
            @sort="handleSort"
        >
            <!-- Custom cell templates -->
            <template #invoice_number="{ value }">
                <div class="invoice-number">{{ value }}</div>
            </template>

            <template #customer="{ item }">
                <div class="customer-info">
                    <div class="customer-name">
                        {{ item.customer?.name || "-" }}
                    </div>
                </div>
            </template>

            <template #total="{ value }">
                <div class="invoice-total">{{ formatCurrency(value) }}</div>
            </template>

            <template #status="{ value }">
                <span class="status-badge" :class="getStatusClass(value)">
                    {{ getStatusLabel(value) }}
                </span>
            </template>

            <template #date="{ value }">
                {{ formatDate(value) }}
            </template>

            <template #due_date="{ value, item }">
                <span :class="{ 'text-danger': isOverdue(item) }">
                    {{ formatDate(value) }}
                </span>
            </template>

            <template #actions="{ item }">
                <div class="actions-cell">
                    <button
                        class="action-btn view"
                        title="Lihat Detail"
                        @click="viewInvoice(item)"
                    >
                        <i class="fas fa-eye"></i>
                    </button>
                    <button
                        v-if="canEditInvoice(item)"
                        class="action-btn edit"
                        title="Edit Faktur"
                        @click="editInvoice(item)"
                    >
                        <i class="fas fa-edit"></i>
                    </button>
                    <button
                        class="action-btn print"
                        title="Cetak Faktur"
                        @click="printInvoice(item)"
                    >
                        <i class="fas fa-print"></i>
                    </button>
                    <button
                        v-if="canDeleteInvoice(item)"
                        class="action-btn delete"
                        title="Hapus Faktur"
                        @click="confirmDelete(item)"
                    >
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </template>
        </DataTable>

        <!-- Pagination -->
        <PaginationComponent
            v-if="totalItems > 0"
            :current-page="currentPage"
            :total-pages="totalPages"
            :from="(currentPage - 1) * itemsPerPage + 1"
            :to="Math.min(currentPage * itemsPerPage, totalItems)"
            :total="totalItems"
            @page-changed="goToPage"
        />

        <!-- Delete Confirmation Modal -->
        <ConfirmationModal
            v-if="showDeleteModal"
            title="Konfirmasi Hapus"
            :message="`Apakah Anda yakin ingin menghapus faktur <strong>${invoiceToDelete.invoice_number}</strong>?<br>Tindakan ini tidak dapat dibatalkan.`"
            confirm-button-text="Hapus"
            confirm-button-class="btn btn-danger"
            @confirm="deleteInvoice"
            @close="closeDeleteModal"
        />
    </div>
</template>

<script>
import { ref, onMounted } from "vue";
import { useRouter } from "vue-router";
import axios from "axios";

export default {
    name: "SalesInvoiceList",
    setup() {
        const router = useRouter();
        const invoices = ref([]);
        const isLoading = ref(true);

        // Pagination
        const currentPage = ref(1);
        const itemsPerPage = ref(10);
        const totalItems = ref(0);
        const totalPages = ref(1);

        // Filters
        const searchQuery = ref("");
        const statusFilter = ref("");
        const dateRangeFilter = ref("month");
        const customDateRange = ref({
            startDate: new Date().toISOString().substr(0, 10),
            endDate: new Date().toISOString().substr(0, 10),
        });

        // Sort
        const sortKey = ref("invoice_id");
        const sortOrder = ref("desc");

        // Delete modal
        const showDeleteModal = ref(false);
        const invoiceToDelete = ref({});

        // Table columns
        const columns = [
            {
                key: "invoice_number",
                label: "No. Faktur",
                sortable: true,
                template: "invoice_number",
            },
            {
                key: "customer",
                label: "Pelanggan",
                sortable: false,
                template: "customer",
            },
            {
                key: "invoice_date",
                label: "Tanggal Faktur",
                sortable: true,
                template: "date",
            },
            {
                key: "due_date",
                label: "Jatuh Tempo",
                sortable: true,
                template: "due_date",
            },
            {
                key: "status",
                label: "Status",
                sortable: true,
                template: "status",
            },
            {
                key: "total_amount",
                label: "Total",
                sortable: true,
                template: "total",
            },
        ];

        // Fetch invoices
        const fetchInvoices = async () => {
            isLoading.value = true;

            try {
                // Prepare query parameters
                const params = {
                    page: currentPage.value,
                    status: statusFilter.value,
                    search: searchQuery.value,
                    sort_field: sortKey.value,
                    sort_direction: sortOrder.value,
                };

                // Add date range filters if applicable
                if (dateRangeFilter.value === "custom") {
                    params.start_date = customDateRange.value.startDate;
                    params.end_date = customDateRange.value.endDate;
                } else if (dateRangeFilter.value !== "all") {
                    params.date_range = dateRangeFilter.value;
                }

                const response = await axios.get("/invoices", {
                    params,
                });

                invoices.value = response.data.data;
                totalItems.value =
                    response.data.meta?.total || invoices.value.length;
                totalPages.value =
                    response.data.meta?.last_page ||
                    Math.ceil(totalItems.value / itemsPerPage.value);
            } catch (error) {
                console.error("Error fetching sales invoices:", error);
                alert("Terjadi kesalahan saat memuat data. Silakan coba lagi.");
            } finally {
                isLoading.value = false;
            }
        };

        // Filters and pagination methods
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

        const handleSort = (sortData) => {
            sortKey.value = sortData.key;
            sortOrder.value = sortData.order;
            fetchInvoices();
        };

        const goToPage = (page) => {
            currentPage.value = page;
            fetchInvoices();
        };

        // CRUD operations
        const createInvoice = () => {
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
                    `/invoices/${invoiceToDelete.value.invoice_id}`
                );
                fetchInvoices(); // Refresh list after delete
                showDeleteModal.value = false;
                alert("Faktur berhasil dihapus");
            } catch (error) {
                console.error("Error deleting invoice:", error);
                if (error.response?.data?.message) {
                    alert(error.response.data.message);
                } else {
                    alert("Terjadi kesalahan saat menghapus faktur");
                }
            }
        };

        // Helper methods
        const formatDate = (dateString) => {
            if (!dateString) return "-";
            const date = new Date(dateString);
            return date.toLocaleDateString("id-ID", {
                day: "2-digit",
                month: "short",
                year: "numeric",
            });
        };

        const formatCurrency = (value) => {
            return new Intl.NumberFormat("id-ID", {
                style: "currency",
                currency: "IDR",
                minimumFractionDigits: 0,
                maximumFractionDigits: 0,
            }).format(value || 0);
        };

        const getStatusLabel = (status) => {
            switch (status) {
                case "Draft":
                    return "Draft";
                case "Sent":
                    return "Terkirim";
                case "Paid":
                    return "Dibayar";
                case "Partially Paid":
                    return "Dibayar Sebagian";
                case "Overdue":
                    return "Jatuh Tempo";
                case "Cancelled":
                    return "Dibatalkan";
                default:
                    return status;
            }
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

        const isOverdue = (invoice) => {
            if (!invoice.due_date || invoice.status === "Paid") return false;

            const today = new Date();
            const dueDate = new Date(invoice.due_date);
            return today > dueDate;
        };

        const canEditInvoice = (invoice) => {
            // Only allow edit for invoices that are in Draft status
            return invoice.status === "Draft";
        };

        const canDeleteInvoice = (invoice) => {
            // Only allow delete if no payments have been made
            return ["Draft", "Sent"].includes(invoice.status);
        };

        onMounted(() => {
            fetchInvoices();
        });

        return {
            invoices,
            columns,
            isLoading,
            currentPage,
            itemsPerPage,
            totalItems,
            totalPages,
            searchQuery,
            statusFilter,
            dateRangeFilter,
            customDateRange,
            showDeleteModal,
            invoiceToDelete,
            handleSearch,
            clearSearch,
            applyFilters,
            handleSort,
            goToPage,
            createInvoice,
            viewInvoice,
            editInvoice,
            printInvoice,
            confirmDelete,
            closeDeleteModal,
            deleteInvoice,
            formatDate,
            formatCurrency,
            getStatusLabel,
            getStatusClass,
            isOverdue,
            canEditInvoice,
            canDeleteInvoice,
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

.custom-date-range {
    background-color: #f8fafc;
    border: 1px solid #e2e8f0;
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
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--gray-500);
}

.filter-group select,
.filter-group input {
    padding: 0.5rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.375rem;
    font-size: 0.875rem;
}

.invoice-number {
    font-weight: 500;
    color: var(--primary-color);
}

.customer-info {
    display: flex;
    flex-direction: column;
}

.customer-name {
    font-weight: 500;
}

.invoice-total {
    font-weight: 500;
}

.status-badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
}

.status-draft {
    background-color: #e2e8f0;
    color: #475569;
}

.status-sent {
    background-color: #dbeafe;
    color: #2563eb;
}

.status-paid {
    background-color: #d1fae5;
    color: #059669;
}

.status-partial {
    background-color: #e0f2fe;
    color: #0284c7;
}

.status-overdue {
    background-color: #fee2e2;
    color: #dc2626;
}

.status-cancelled {
    background-color: #f3f4f6;
    color: #1f2937;
}

.text-danger {
    color: #dc2626;
}

.actions-cell {
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
}

.action-btn {
    background: none;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border-radius: 0.25rem;
    transition: background-color 0.2s;
}

.action-btn.view {
    color: var(--primary-color);
}

.action-btn.edit {
    color: var(--warning-color);
}

.action-btn.print {
    color: var(--gray-700);
}

.action-btn.delete {
    color: var(--danger-color);
}

.action-btn:hover {
    background-color: #f8fafc;
}

@media (max-width: 768px) {
    .date-range-inputs {
        flex-direction: column;
    }
}
</style>
