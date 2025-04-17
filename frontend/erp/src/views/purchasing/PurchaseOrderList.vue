<!-- src/views/purchasing/PurchaseOrderList.vue -->
<template>
    <div class="po-list-container">
        <div class="page-header">
            <h1>Purchase Orders</h1>
            <div class="header-actions">
                <router-link
                    to="/purchasing/orders/create"
                    class="btn btn-primary"
                >
                    <i class="fas fa-plus"></i> Create Purchase Order
                </router-link>
            </div>
        </div>

        <div class="filter-section">
            <SearchFilter
                v-model:value="searchQuery"
                placeholder="Search PO number..."
                @search="fetchPurchaseOrders"
                @clear="clearSearch"
            >
                <template v-slot:filters>
                    <div class="filter-group">
                        <label for="status-filter">Status</label>
                        <select
                            id="status-filter"
                            v-model="filters.status"
                            @change="fetchPurchaseOrders"
                        >
                            <option value="">All Status</option>
                            <option value="draft">Draft</option>
                            <option value="submitted">Submitted</option>
                            <option value="approved">Approved</option>
                            <option value="sent">Sent</option>
                            <option value="partial">Partially Received</option>
                            <option value="received">Received</option>
                            <option value="completed">Completed</option>
                            <option value="canceled">Canceled</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="vendor-filter">Vendor</label>
                        <select
                            id="vendor-filter"
                            v-model="filters.vendor_id"
                            @change="fetchPurchaseOrders"
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
                            @change="fetchPurchaseOrders"
                        />
                    </div>

                    <div class="filter-group">
                        <label for="date-to">Date To</label>
                        <input
                            type="date"
                            id="date-to"
                            v-model="filters.date_to"
                            @change="fetchPurchaseOrders"
                        />
                    </div>
                </template>
            </SearchFilter>
        </div>

        <div class="data-container">
            <DataTable
                :columns="columns"
                :items="purchaseOrders"
                :is-loading="isLoading"
                :key-field="'po_id'"
                :initial-sort-key="'po_date'"
                :initial-sort-order="'desc'"
                :empty-title="'No Purchase Orders Found'"
                :empty-message="'Try adjusting your search or filters, or create a new purchase order.'"
                :empty-icon="'fas fa-file-invoice'"
                @sort="handleSort"
            >
                <template v-slot:po_date="{ value }">
                    {{ formatDate(value) }}
                </template>

                <template v-slot:expected_delivery="{ value }">
                    {{ formatDate(value) }}
                </template>

                <template v-slot:vendor_name="{ item }">
                    {{ item.vendor ? item.vendor.name : "N/A" }}
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
                            :to="`/purchasing/orders/${item.po_id}`"
                            class="action-btn view-btn"
                            title="View Details"
                        >
                            <i class="fas fa-eye"></i>
                        </router-link>
                        <router-link
                            v-if="item.status === 'draft'"
                            :to="`/purchasing/orders/${item.po_id}/edit`"
                            class="action-btn edit-btn"
                            title="Edit PO"
                        >
                            <i class="fas fa-edit"></i>
                        </router-link>
                        <button
                            v-if="item.status === 'draft'"
                            @click="confirmDelete(item)"
                            class="action-btn delete-btn"
                            title="Delete PO"
                        >
                            <i class="fas fa-trash"></i>
                        </button>
                        <router-link
                            :to="`/purchasing/orders/${item.po_id}/track`"
                            class="action-btn track-btn"
                            title="Track Status"
                        >
                            <i class="fas fa-chart-line"></i>
                        </router-link>
                    </div>
                </template>
            </DataTable>

            <div v-if="totalPages > 1" class="pagination-wrapper">
                <PaginationComponent
                    :current-page="currentPage"
                    :total-pages="totalPages"
                    :from="paginationFrom"
                    :to="paginationTo"
                    :total="totalPOs"
                    @page-changed="changePage"
                />
            </div>
        </div>

        <!-- Confirmation Modal for Delete -->
        <ConfirmationModal
            v-if="showDeleteModal"
            :title="'Delete Purchase Order'"
            :message="
                'Are you sure you want to delete purchase order <strong>' +
                (selectedPO?.po_number || '') +
                '</strong>? This action cannot be undone.'
            "
            :confirm-button-text="'Delete'"
            :confirm-button-class="'btn btn-danger'"
            @confirm="deletePurchaseOrder"
            @close="closeDeleteModal"
        />
    </div>
</template>

<script>
import { ref, reactive, computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import PurchaseOrderService from "@/services/PurchaseOrderService";
import VendorService from "@/services/VendorService";
import DataTable from "@/components/common/DataTable.vue";
import SearchFilter from "@/components/common/SearchFilter.vue";
import PaginationComponent from "@/components/common/Pagination.vue";
import ConfirmationModal from "@/components/common/ConfirmationModal.vue";

export default {
    name: "PurchaseOrderList",
    components: {
        DataTable,
        SearchFilter,
        PaginationComponent,
        ConfirmationModal,
    },
    setup() {
        const router = useRouter();
        const purchaseOrders = ref([]);
        const vendors = ref([]);
        const isLoading = ref(true);
        const currentPage = ref(1);
        const totalPages = ref(1);
        const totalPOs = ref(0);
        const itemsPerPage = ref(10);
        const searchQuery = ref("");
        const showDeleteModal = ref(false);
        const selectedPO = ref(null);

        const filters = reactive({
            status: "",
            vendor_id: "",
            date_from: "",
            date_to: "",
        });

        const sortKey = ref("po_date");
        const sortOrder = ref("desc");

        // Table columns definition
        const columns = [
            { key: "po_number", label: "PO Number", sortable: true },
            {
                key: "po_date",
                label: "PO Date",
                sortable: true,
                template: "po_date",
            },
            {
                key: "vendor_name",
                label: "Vendor",
                sortable: false,
                template: "vendor_name",
            },
            {
                key: "expected_delivery",
                label: "Expected Delivery",
                sortable: true,
                template: "expected_delivery",
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
                totalPOs.value
            );
        });

        // Fetch purchase orders from API
        const fetchPurchaseOrders = async () => {
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
                    await PurchaseOrderService.getAllPurchaseOrders(params);

                if (response.data && response.data.data) {
                    purchaseOrders.value = response.data.data;

                    if (response.data.meta) {
                        totalPOs.value = response.data.meta.total || 0;
                        totalPages.value = response.data.meta.last_page || 1;
                        currentPage.value =
                            response.data.meta.current_page || 1;
                    }
                } else {
                    purchaseOrders.value = [];
                    totalPOs.value = 0;
                    totalPages.value = 1;
                }
            } catch (error) {
                console.error("Error fetching purchase orders:", error);
                purchaseOrders.value = [];
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
            fetchPurchaseOrders();
        };

        const handleSort = ({ key, order }) => {
            sortKey.value = key;
            sortOrder.value = order;
            fetchPurchaseOrders();
        };

        const changePage = (page) => {
            currentPage.value = page;
            fetchPurchaseOrders();
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
                case "draft":
                    return "Draft";
                case "submitted":
                    return "Submitted";
                case "approved":
                    return "Approved";
                case "sent":
                    return "Sent";
                case "partial":
                    return "Partially Received";
                case "received":
                    return "Received";
                case "completed":
                    return "Completed";
                case "canceled":
                    return "Canceled";
                default:
                    return status;
            }
        };

        const getStatusClass = (status) => {
            switch (status) {
                case "draft":
                    return "status-draft";
                case "submitted":
                    return "status-pending";
                case "approved":
                    return "status-approved";
                case "sent":
                    return "status-sent";
                case "partial":
                    return "status-partial";
                case "received":
                    return "status-received";
                case "completed":
                    return "status-completed";
                case "canceled":
                    return "status-canceled";
                default:
                    return "status-draft";
            }
        };

        const confirmDelete = (po) => {
            selectedPO.value = po;
            showDeleteModal.value = true;
        };

        const closeDeleteModal = () => {
            showDeleteModal.value = false;
            selectedPO.value = null;
        };

        const deletePurchaseOrder = async () => {
            if (!selectedPO.value) return;

            try {
                await PurchaseOrderService.deletePurchaseOrder(
                    selectedPO.value.po_id
                );
                fetchPurchaseOrders();
                closeDeleteModal();
            } catch (error) {
                if (error.response && error.response.status === 400) {
                    alert(
                        error.response.data.message ||
                            "This purchase order cannot be deleted."
                    );
                } else {
                    console.error("Error deleting purchase order:", error);
                }
                closeDeleteModal();
            }
        };

        // Initialize
        onMounted(() => {
            fetchPurchaseOrders();
            fetchVendors();
        });

        return {
            purchaseOrders,
            vendors,
            isLoading,
            columns,
            currentPage,
            totalPages,
            totalPOs,
            paginationFrom,
            paginationTo,
            searchQuery,
            filters,
            showDeleteModal,
            selectedPO,
            fetchPurchaseOrders,
            clearSearch,
            handleSort,
            changePage,
            formatDate,
            formatCurrency,
            formatStatus,
            getStatusClass,
            confirmDelete,
            closeDeleteModal,
            deletePurchaseOrder,
        };
    },
};
</script>

<style scoped>
.po-list-container {
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

.delete-btn {
    color: var(--danger-color);
}

.delete-btn:hover {
    background-color: var(--danger-bg);
}

.track-btn {
    color: var(--success-color);
}

.track-btn:hover {
    background-color: var(--success-bg);
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

.status-pending {
    background-color: #fef3c7;
    color: #92400e;
}

.status-approved {
    background-color: #dcfce7;
    color: #166534;
}

.status-sent {
    background-color: #dbeafe;
    color: #1e40af;
}

.status-partial {
    background-color: #fef9c3;
    color: #854d0e;
}

.status-received {
    background-color: #d1fae5;
    color: #065f46;
}

.status-completed {
    background-color: #bbf7d0;
    color: #15803d;
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
