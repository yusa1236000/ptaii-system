<!-- src/views/purchasing/GoodsReceiptList.vue -->
<template>
    <div class="goods-receipt-list-container">
        <div class="page-header">
            <h1>Goods Receipts</h1>
            <div class="header-actions">
                <router-link
                    to="/purchasing/goods-receipts/create"
                    class="btn btn-primary"
                >
                    <i class="fas fa-plus"></i> Create Goods Receipt
                </router-link>
            </div>
        </div>

        <div class="filter-section">
            <SearchFilter
                v-model:value="searchQuery"
                placeholder="Search receipt number..."
                @search="fetchGoodsReceipts"
                @clear="clearSearch"
            >
                <template v-slot:filters>
                    <div class="filter-group">
                        <label for="status-filter">Status</label>
                        <select
                            id="status-filter"
                            v-model="filters.status"
                            @change="fetchGoodsReceipts"
                        >
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="canceled">Canceled</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="vendor-filter">Vendor</label>
                        <select
                            id="vendor-filter"
                            v-model="filters.vendor_id"
                            @change="fetchGoodsReceipts"
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
                        <label for="po-filter">Purchase Order</label>
                        <select
                            id="po-filter"
                            v-model="filters.po_id"
                            @change="fetchGoodsReceipts"
                        >
                            <option value="">All POs</option>
                            <option
                                v-for="po in purchaseOrders"
                                :key="po.po_id"
                                :value="po.po_id"
                            >
                                {{ po.po_number }}
                            </option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="date-from">Date From</label>
                        <input
                            type="date"
                            id="date-from"
                            v-model="filters.date_from"
                            @change="fetchGoodsReceipts"
                        />
                    </div>

                    <div class="filter-group">
                        <label for="date-to">Date To</label>
                        <input
                            type="date"
                            id="date-to"
                            v-model="filters.date_to"
                            @change="fetchGoodsReceipts"
                        />
                    </div>
                </template>
            </SearchFilter>
        </div>

        <div class="data-container">
            <DataTable
                :columns="columns"
                :items="goodsReceipts"
                :is-loading="isLoading"
                :key-field="'receipt_id'"
                :initial-sort-key="'receipt_date'"
                :initial-sort-order="'desc'"
                :empty-title="'No Goods Receipts Found'"
                :empty-message="'Try adjusting your search or filters, or create a new goods receipt.'"
                :empty-icon="'fas fa-truck-loading'"
                @sort="handleSort"
            >
                <template v-slot:receipt_date="{ value }">
                    {{ formatDate(value) }}
                </template>

                <template v-slot:vendor_name="{ item }">
                    {{ item.vendor ? item.vendor.name : "N/A" }}
                </template>

                <template v-slot:po_number="{ item }">
                    {{
                        item.purchaseOrder
                            ? item.purchaseOrder.po_number
                            : "N/A"
                    }}
                </template>

                <template v-slot:status="{ value }">
                    <span :class="['status-badge', getStatusClass(value)]">
                        {{ formatStatus(value) }}
                    </span>
                </template>

                <template v-slot:actions="{ item }">
                    <div class="action-buttons">
                        <router-link
                            :to="`/purchasing/goods-receipts/${item.receipt_id}`"
                            class="action-btn view-btn"
                            title="View Details"
                        >
                            <i class="fas fa-eye"></i>
                        </router-link>
                        <router-link
                            v-if="item.status === 'pending'"
                            :to="`/purchasing/goods-receipts/${item.receipt_id}/edit`"
                            class="action-btn edit-btn"
                            title="Edit Receipt"
                        >
                            <i class="fas fa-edit"></i>
                        </router-link>
                        <router-link
                            v-if="item.status === 'pending'"
                            :to="`/purchasing/goods-receipts/${item.receipt_id}/confirm`"
                            class="action-btn confirm-btn"
                            title="Confirm Receipt"
                        >
                            <i class="fas fa-check-circle"></i>
                        </router-link>
                        <button
                            v-if="item.status === 'pending'"
                            @click="confirmDelete(item)"
                            class="action-btn delete-btn"
                            title="Delete Receipt"
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
                    :total="totalReceipts"
                    @page-changed="changePage"
                />
            </div>
        </div>

        <!-- Confirmation Modal for Delete -->
        <ConfirmationModal
            v-if="showDeleteModal"
            :title="'Delete Goods Receipt'"
            :message="
                'Are you sure you want to delete goods receipt <strong>' +
                (selectedReceipt?.receipt_number || '') +
                '</strong>? This action cannot be undone.'
            "
            :confirm-button-text="'Delete'"
            :confirm-button-class="'btn btn-danger'"
            @confirm="deleteGoodsReceipt"
            @close="closeDeleteModal"
        />
    </div>
</template>

<script>
import { ref, reactive, computed, onMounted } from "vue";
//import { useRouter } from "vue-router";
import DataTable from "@/components/common/DataTable.vue";
import SearchFilter from "@/components/common/SearchFilter.vue";
import PaginationComponent from "@/components/common/Pagination.vue";
import ConfirmationModal from "@/components/common/ConfirmationModal.vue";

export default {
    name: "GoodsReceiptList",
    components: {
        DataTable,
        SearchFilter,
        PaginationComponent,
        ConfirmationModal,
    },
    setup() {
        // const router = useRouter();
        const goodsReceipts = ref([]);
        const vendors = ref([]);
        const purchaseOrders = ref([]);
        const isLoading = ref(true);
        const currentPage = ref(1);
        const totalPages = ref(1);
        const totalReceipts = ref(0);
        const itemsPerPage = ref(10);
        const searchQuery = ref("");
        const showDeleteModal = ref(false);
        const selectedReceipt = ref(null);

        const filters = reactive({
            status: "",
            vendor_id: "",
            po_id: "",
            date_from: "",
            date_to: "",
        });

        const sortKey = ref("receipt_date");
        const sortOrder = ref("desc");

        // Table columns definition
        const columns = [
            { key: "receipt_number", label: "Receipt #", sortable: true },
            {
                key: "receipt_date",
                label: "Date",
                sortable: true,
                template: "receipt_date",
            },
            {
                key: "vendor_name",
                label: "Vendor",
                sortable: false,
                template: "vendor_name",
            },
            {
                key: "po_number",
                label: "PO #",
                sortable: false,
                template: "po_number",
            },
            {
                key: "status",
                label: "Status",
                sortable: true,
                template: "status",
            },
        ];

        // Computed pagination values
        const paginationFrom = computed(() => {
            return (currentPage.value - 1) * itemsPerPage.value + 1;
        });

        const paginationTo = computed(() => {
            return Math.min(
                currentPage.value * itemsPerPage.value,
                totalReceipts.value
            );
        });

        // Fetch goods receipts from API
        const fetchGoodsReceipts = async () => {
            isLoading.value = true;

            try {
                const params = {
                    page: currentPage.value,
                    per_page: itemsPerPage.value,
                    search: searchQuery.value,
                    status: filters.status,
                    vendor_id: filters.vendor_id,
                    po_id: filters.po_id,
                    date_from: filters.date_from,
                    date_to: filters.date_to,
                    sort_field: sortKey.value,
                    sort_direction: sortOrder.value,
                };

                // Replace with your actual API service call
                const response = await fetch(
                    "/api/goods-receipts?" + new URLSearchParams(params),
                    {
                        headers: {
                            Authorization: `Bearer ${localStorage.getItem(
                                "token"
                            )}`,
                            "Content-Type": "application/json",
                        },
                    }
                );

                const data = await response.json();

                if (data && data.data) {
                    goodsReceipts.value = data.data;

                    if (data.meta) {
                        totalReceipts.value = data.meta.total || 0;
                        totalPages.value = data.meta.last_page || 1;
                        currentPage.value = data.meta.current_page || 1;
                    }
                } else {
                    goodsReceipts.value = [];
                    totalReceipts.value = 0;
                    totalPages.value = 1;
                }
            } catch (error) {
                console.error("Error fetching goods receipts:", error);
                goodsReceipts.value = [];
            } finally {
                isLoading.value = false;
            }
        };

        // Fetch vendors for the filter dropdown
        const fetchVendors = async () => {
            try {
                const response = await fetch("/api/vendors?per_page=100", {
                    headers: {
                        Authorization: `Bearer ${localStorage.getItem(
                            "token"
                        )}`,
                        "Content-Type": "application/json",
                    },
                });

                const data = await response.json();

                if (data && data.data) {
                    vendors.value = data.data;
                }
            } catch (error) {
                console.error("Error fetching vendors:", error);
            }
        };

        // Fetch purchase orders for the filter dropdown
        const fetchPurchaseOrders = async () => {
            try {
                const response = await fetch(
                    "/api/purchase-orders?per_page=100",
                    {
                        headers: {
                            Authorization: `Bearer ${localStorage.getItem(
                                "token"
                            )}`,
                            "Content-Type": "application/json",
                        },
                    }
                );

                const data = await response.json();

                if (data && data.data) {
                    purchaseOrders.value = data.data;
                }
            } catch (error) {
                console.error("Error fetching purchase orders:", error);
            }
        };

        const clearSearch = () => {
            searchQuery.value = "";
            fetchGoodsReceipts();
        };

        const handleSort = ({ key, order }) => {
            sortKey.value = key;
            sortOrder.value = order;
            fetchGoodsReceipts();
        };

        const changePage = (page) => {
            currentPage.value = page;
            fetchGoodsReceipts();
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

        const formatStatus = (status) => {
            switch (status) {
                case "pending":
                    return "Pending";
                case "confirmed":
                    return "Confirmed";
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
                case "confirmed":
                    return "status-completed";
                case "canceled":
                    return "status-canceled";
                default:
                    return "status-draft";
            }
        };

        const confirmDelete = (receipt) => {
            selectedReceipt.value = receipt;
            showDeleteModal.value = true;
        };

        const closeDeleteModal = () => {
            showDeleteModal.value = false;
            selectedReceipt.value = null;
        };

        const deleteGoodsReceipt = async () => {
            if (!selectedReceipt.value) return;

            try {
                const response = await fetch(
                    `/api/goods-receipts/${selectedReceipt.value.receipt_id}`,
                    {
                        method: "DELETE",
                        headers: {
                            Authorization: `Bearer ${localStorage.getItem(
                                "token"
                            )}`,
                            "Content-Type": "application/json",
                        },
                    }
                );

                if (response.ok) {
                    fetchGoodsReceipts();
                    closeDeleteModal();
                } else {
                    const errorData = await response.json();
                    alert(
                        errorData.message || "Failed to delete goods receipt."
                    );
                    closeDeleteModal();
                }
            } catch (error) {
                console.error("Error deleting goods receipt:", error);
                alert("An error occurred while deleting the goods receipt.");
                closeDeleteModal();
            }
        };

        // Initialize
        onMounted(() => {
            fetchGoodsReceipts();
            fetchVendors();
            fetchPurchaseOrders();
        });

        return {
            goodsReceipts,
            vendors,
            purchaseOrders,
            isLoading,
            columns,
            currentPage,
            totalPages,
            totalReceipts,
            paginationFrom,
            paginationTo,
            searchQuery,
            filters,
            showDeleteModal,
            selectedReceipt,
            fetchGoodsReceipts,
            clearSearch,
            handleSort,
            changePage,
            formatDate,
            formatStatus,
            getStatusClass,
            confirmDelete,
            closeDeleteModal,
            deleteGoodsReceipt,
        };
    },
};
</script>

<style scoped>
.goods-receipt-list-container {
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

.confirm-btn {
    color: var(--success-color);
}

.confirm-btn:hover {
    background-color: var(--success-bg);
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

.status-completed {
    background-color: #d1fae5;
    color: #065f46;
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
