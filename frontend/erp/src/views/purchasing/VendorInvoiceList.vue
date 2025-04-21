<!-- src/views/purchasing/VendorInvoiceList.vue -->
<template>
    <div class="invoice-list-container">
        <div class="page-header">
            <h1>Vendor Invoices</h1>
            <div class="header-actions">
                <router-link
                    to="/purchasing/vendor-invoices/create"
                    class="btn btn-primary"
                >
                    <i class="fas fa-plus"></i> Create Invoice
                </router-link>
            </div>
        </div>

        <div class="filter-section">
            <SearchFilter
                v-model:value="searchQuery"
                placeholder="Search invoice number..."
                @search="fetchVendorInvoices"
                @clear="clearSearch"
            >
                <template v-slot:filters>
                    <div class="filter-group">
                        <label for="status-filter">Status</label>
                        <select
                            id="status-filter"
                            v-model="filters.status"
                            @change="fetchVendorInvoices"
                        >
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="paid">Paid</option>
                            <option value="partially_paid">
                                Partially Paid
                            </option>
                            <option value="canceled">Canceled</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="vendor-filter">Vendor</label>
                        <select
                            id="vendor-filter"
                            v-model="filters.vendor_id"
                            @change="fetchVendorInvoices"
                        >
                            <option value="">All Vendors</option>
                            <option
                                v-for="vendor in vendors"
                                :key="vendor.vendor_id"
                                :value="vendor.vendor_id"
                            >
                                {{ vendor.name }}
                            </option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="date-from">Date From</label>
                        <input
                            type="date"
                            id="date-from"
                            v-model="filters.date_from"
                            @change="fetchVendorInvoices"
                        />
                    </div>

                    <div class="filter-group">
                        <label for="date-to">Date To</label>
                        <input
                            type="date"
                            id="date-to"
                            v-model="filters.date_to"
                            @change="fetchVendorInvoices"
                        />
                    </div>
                </template>
            </SearchFilter>
        </div>

        <div class="data-container">
            <DataTable
                :columns="columns"
                :items="vendorInvoices"
                :is-loading="isLoading"
                :key-field="'invoice_id'"
                :initial-sort-key="'invoice_date'"
                :initial-sort-order="'desc'"
                :empty-title="'No Invoices Found'"
                :empty-message="'Try adjusting your search or filters, or create a new invoice.'"
                :empty-icon="'fas fa-file-invoice-dollar'"
                @sort="handleSort"
            >
                <template v-slot:invoice_date="{ value }">
                    {{ formatDate(value) }}
                </template>

                <template v-slot:due_date="{ value }">
                    {{ formatDate(value) }}
                </template>

                <template v-slot:vendor_name="{ item }">
                    {{ item.vendor ? item.vendor.name : "N/A" }}
                </template>

                <template v-slot:po_number="{ item }">
                    <router-link
                        v-if="item.purchase_order"
                        :to="`/purchasing/orders/${item.po_id}`"
                    >
                        {{ item.purchase_order.po_number }}
                    </router-link>
                    <span v-else>N/A</span>
                </template>

                <template v-slot:status="{ value }">
                    <span :class="['status-badge', getStatusClass(value)]">
                        {{ formatStatus(value) }}
                    </span>
                </template>

                <template v-slot:total_amount="{ value }">
                    {{ formatCurrency(value) }}
                </template>

                <template v-slot:actions="{ item }">
                    <div class="action-buttons">
                        <router-link
                            :to="`/purchasing/vendor-invoices/${item.invoice_id}`"
                            class="action-btn view-btn"
                            title="View Details"
                        >
                            <i class="fas fa-eye"></i>
                        </router-link>
                        <router-link
                            v-if="item.status === 'pending'"
                            :to="`/purchasing/vendor-invoices/${item.invoice_id}/edit`"
                            class="action-btn edit-btn"
                            title="Edit Invoice"
                        >
                            <i class="fas fa-edit"></i>
                        </router-link>
                        <router-link
                            v-if="item.status === 'pending'"
                            :to="`/purchasing/vendor-invoices/${item.invoice_id}/approve`"
                            class="action-btn approve-btn"
                            title="Approve Invoice"
                        >
                            <i class="fas fa-check-circle"></i>
                        </router-link>
                        <router-link
                            v-if="
                                item.status === 'approved' ||
                                item.status === 'partially_paid'
                            "
                            :to="`/purchasing/vendor-invoices/${item.invoice_id}/payment`"
                            class="action-btn payment-btn"
                            title="Record Payment"
                        >
                            <i class="fas fa-money-bill-wave"></i>
                        </router-link>
                        <button
                            v-if="item.status === 'pending'"
                            @click="confirmDelete(item)"
                            class="action-btn delete-btn"
                            title="Delete Invoice"
                        >
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </template>
            </DataTable>

            <div v-if="totalPages > 1" class="pagination-wrapper">
                <PaginationComponent
                    :current-page="currentPage"
                    :total-pages="totalPages"
                    :from="paginationFrom"
                    :to="paginationTo"
                    :total="totalInvoices"
                    @page-changed="changePage"
                />
            </div>
        </div>

        <!-- Confirmation Modal for Delete -->
        <ConfirmationModal
            v-if="showDeleteModal"
            :title="'Delete Vendor Invoice'"
            :message="
                'Are you sure you want to delete vendor invoice <strong>' +
                (selectedInvoice?.invoice_number || '') +
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
import { ref, reactive, computed, onMounted } from "vue";
import VendorInvoiceService from "@/services/VendorInvoiceService";
import VendorService from "@/services/VendorService";
import DataTable from "@/components/common/DataTable.vue";
import SearchFilter from "@/components/common/SearchFilter.vue";
import PaginationComponent from "@/components/common/Pagination.vue";
import ConfirmationModal from "@/components/common/ConfirmationModal.vue";

export default {
    name: "VendorInvoiceList",
    components: {
        DataTable,
        SearchFilter,
        PaginationComponent,
        ConfirmationModal,
    },
    setup() {
        const vendorInvoices = ref([]);
        const vendors = ref([]);
        const isLoading = ref(true);
        const currentPage = ref(1);
        const totalPages = ref(1);
        const totalInvoices = ref(0);
        const itemsPerPage = ref(10);
        const searchQuery = ref("");
        const showDeleteModal = ref(false);
        const selectedInvoice = ref(null);

        const filters = reactive({
            status: "",
            vendor_id: "",
            date_from: "",
            date_to: "",
        });

        const sortKey = ref("invoice_date");
        const sortOrder = ref("desc");

        // Table columns definition
        const columns = [
            { key: "invoice_number", label: "Invoice Number", sortable: true },
            {
                key: "invoice_date",
                label: "Invoice Date",
                sortable: true,
                template: "invoice_date",
            },
            {
                key: "vendor_name",
                label: "Vendor",
                sortable: false,
                template: "vendor_name",
            },
            {
                key: "po_number",
                label: "PO Number",
                sortable: false,
                template: "po_number",
            },
            {
                key: "due_date",
                label: "Due Date",
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
                label: "Total Amount",
                sortable: true,
                template: "total_amount",
            },
        ];

        // Computed pagination values
        const paginationFrom = computed(() => {
            return (currentPage.value - 1) * itemsPerPage.value + 1;
        });

        const paginationTo = computed(() => {
            return Math.min(
                currentPage.value * itemsPerPage.value,
                totalInvoices.value
            );
        });

        // Fetch vendor invoices from API
        const fetchVendorInvoices = async () => {
            isLoading.value = true;

            try {
                const params = {
                    page: currentPage.value,
                    per_page: itemsPerPage.value,
                    search: searchQuery.value,
                    status: filters.status,
                    vendor_id: filters.vendor_id,
                    date_from: filters.date_from,
                    date_to: filters.date_to,
                    sort_field: sortKey.value,
                    sort_direction: sortOrder.value,
                };

                const response =
                    await VendorInvoiceService.getAllVendorInvoices(params);

                if (response.data && response.data.data) {
                    vendorInvoices.value = response.data.data;

                    if (response.data.meta) {
                        totalInvoices.value = response.data.meta.total || 0;
                        totalPages.value = response.data.meta.last_page || 1;
                        currentPage.value =
                            response.data.meta.current_page || 1;
                    }
                } else {
                    vendorInvoices.value = [];
                    totalInvoices.value = 0;
                    totalPages.value = 1;
                }
            } catch (error) {
                console.error("Error fetching vendor invoices:", error);
                vendorInvoices.value = [];
            } finally {
                isLoading.value = false;
            }
        };

        // Fetch vendors for the filter dropdown
        const fetchVendors = async () => {
            try {
                const response = await VendorService.getAllVendors({
                    per_page: 100,
                });

                if (response.data && response.data.data) {
                    vendors.value = response.data.data;
                }
            } catch (error) {
                console.error("Error fetching vendors:", error);
            }
        };

        const clearSearch = () => {
            searchQuery.value = "";
            fetchVendorInvoices();
        };

        const handleSort = ({ key, order }) => {
            sortKey.value = key;
            sortOrder.value = order;
            fetchVendorInvoices();
        };

        const changePage = (page) => {
            currentPage.value = page;
            fetchVendorInvoices();
        };

        const formatDate = (dateString) => {
            if (!dateString) return "N/A";
            const date = new Date(dateString);
            return date.toLocaleDateString("en-US", {
                year: "numeric",
                month: "short",
                day: "2-digit",
            });
        };

        const formatCurrency = (amount) => {
            if (amount === null || amount === undefined) return "N/A";
            return new Intl.NumberFormat("en-US", {
                style: "currency",
                currency: "USD",
            }).format(amount);
        };

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

        const confirmDelete = (invoice) => {
            selectedInvoice.value = invoice;
            showDeleteModal.value = true;
        };

        const closeDeleteModal = () => {
            showDeleteModal.value = false;
            selectedInvoice.value = null;
        };

        const deleteVendorInvoice = async () => {
            if (!selectedInvoice.value) return;

            try {
                await VendorInvoiceService.deleteVendorInvoice(
                    selectedInvoice.value.invoice_id
                );
                fetchVendorInvoices();
                closeDeleteModal();
            } catch (error) {
                if (error.response && error.response.status === 400) {
                    alert(
                        error.response.data.message ||
                            "This invoice cannot be deleted."
                    );
                } else {
                    console.error("Error deleting vendor invoice:", error);
                }
                closeDeleteModal();
            }
        };

        // Initialize
        onMounted(() => {
            fetchVendorInvoices();
            fetchVendors();
        });

        return {
            vendorInvoices,
            vendors,
            isLoading,
            columns,
            currentPage,
            totalPages,
            totalInvoices,
            paginationFrom,
            paginationTo,
            searchQuery,
            filters,
            showDeleteModal,
            selectedInvoice,
            fetchVendorInvoices,
            clearSearch,
            handleSort,
            changePage,
            formatDate,
            formatCurrency,
            formatStatus,
            getStatusClass,
            confirmDelete,
            closeDeleteModal,
            deleteVendorInvoice,
        };
    },
};
</script>

<style scoped>
.invoice-list-container {
    padding: 1rem;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.page-header h1 {
    margin: 0;
    font-size: 1.5rem;
    color: var(--gray-800);
}

.header-actions {
    display: flex;
    gap: 0.75rem;
}

.filter-section {
    margin-bottom: 1.5rem;
}

.data-container {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.pagination-wrapper {
    padding: 0.5rem;
    background-color: white;
    border-top: 1px solid var(--gray-200);
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
}

.action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border-radius: 0.375rem;
    border: none;
    background: none;
    cursor: pointer;
    transition: background-color 0.2s;
}

.view-btn {
    color: var(--primary-color);
}

.view-btn:hover {
    background-color: var(--primary-bg);
}

.edit-btn {
    color: var(--warning-color);
}

.edit-btn:hover {
    background-color: var(--warning-bg);
}

.approve-btn {
    color: var(--success-color);
}

.approve-btn:hover {
    background-color: var(--success-bg);
}

.payment-btn {
    color: var(--info-color);
}

.payment-btn:hover {
    background-color: var(--info-bg);
}

.delete-btn {
    color: var(--danger-color);
}

.delete-btn:hover {
    background-color: var(--danger-bg);
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

.btn-danger {
    background-color: var(--danger-color);
    color: white;
}

.btn-danger:hover {
    background-color: #b91c1c;
}
</style>
