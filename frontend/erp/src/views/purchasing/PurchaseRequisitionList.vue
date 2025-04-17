<!-- src/views/purchasing/PurchaseRequisitionList.vue -->
<template>
    <div class="pr-list-container">
        <div class="page-header">
            <h1>Purchase Requisitions</h1>
            <button @click="openCreateForm" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create Requisition
            </button>
        </div>

        <div class="filter-section">
            <SearchFilter
                v-model:value="searchQuery"
                placeholder="Search requisitions..."
                @search="fetchPurchaseRequisitions"
                @clear="clearSearch"
            >
                <template v-slot:filters>
                    <div class="filter-group">
                        <label for="status-filter">Status</label>
                        <select
                            id="status-filter"
                            v-model="filters.status"
                            @change="fetchPurchaseRequisitions"
                        >
                            <option value="">All Status</option>
                            <option value="draft">Draft</option>
                            <option value="pending">Pending Approval</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                            <option value="canceled">Canceled</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="date-from">Date From</label>
                        <input
                            type="date"
                            id="date-from"
                            v-model="filters.date_from"
                            @change="fetchPurchaseRequisitions"
                        />
                    </div>
                    <div class="filter-group">
                        <label for="date-to">Date To</label>
                        <input
                            type="date"
                            id="date-to"
                            v-model="filters.date_to"
                            @change="fetchPurchaseRequisitions"
                        />
                    </div>
                </template>
            </SearchFilter>
        </div>

        <div class="data-container">
            <DataTable
                :columns="columns"
                :items="purchaseRequisitions"
                :is-loading="isLoading"
                :key-field="'pr_id'"
                :initial-sort-key="'pr_date'"
                :initial-sort-order="'desc'"
                :empty-title="'No Purchase Requisitions Found'"
                :empty-message="'Try adjusting your search or filters, or create a new purchase requisition.'"
                :empty-icon="'fas fa-file-alt'"
                @sort="handleSort"
            >
                <template v-slot:status="{ value }">
                    <span :class="['status-badge', getStatusClass(value)]">
                        {{ getStatusLabel(value) }}
                    </span>
                </template>

                <template v-slot:pr_date="{ value }">
                    {{ formatDate(value) }}
                </template>

                <template v-slot:requester="{ item }">
                    {{ item.requester ? item.requester.name : "N/A" }}
                </template>

                <template v-slot:actions="{ item }">
                    <div class="action-buttons">
                        <button
                            @click="viewPR(item)"
                            class="action-btn view-btn"
                            title="View Details"
                        >
                            <i class="fas fa-eye"></i>
                        </button>
                        <button
                            v-if="
                                item.status === 'draft' ||
                                item.status === 'pending'
                            "
                            @click="editPR(item)"
                            class="action-btn edit-btn"
                            title="Edit"
                        >
                            <i class="fas fa-edit"></i>
                        </button>
                        <button
                            v-if="item.status === 'pending'"
                            @click="approvePR(item)"
                            class="action-btn approve-btn"
                            title="Approve/Reject"
                        >
                            <i class="fas fa-check-circle"></i>
                        </button>
                        <button
                            v-if="item.status === 'approved'"
                            @click="convertToRFQ(item)"
                            class="action-btn convert-btn"
                            title="Convert to RFQ"
                        >
                            <i class="fas fa-exchange-alt"></i>
                        </button>
                        <button
                            v-if="item.status === 'draft'"
                            @click="confirmDelete(item)"
                            class="action-btn delete-btn"
                            title="Delete"
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
                    :total="totalItems"
                    @page-changed="changePage"
                />
            </div>
        </div>

        <!-- Confirmation Modal for Delete -->
        <ConfirmationModal
            v-if="showDeleteModal"
            :title="'Delete Purchase Requisition'"
            :message="
                'Are you sure you want to delete purchase requisition <strong>' +
                (selectedPR?.pr_number || '') +
                '</strong>? This action cannot be undone.'
            "
            :confirm-button-text="'Delete'"
            :confirm-button-class="'btn btn-danger'"
            @confirm="deletePR"
            @close="closeDeleteModal"
        />
    </div>
</template>

<script>
import { ref, reactive, computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import PurchaseRequisitionService from "@/services/PurchaseRequisitionService";
import DataTable from "@/components/common/DataTable.vue";
import SearchFilter from "@/components/common/SearchFilter.vue";
import PaginationComponent from "@/components/common/Pagination.vue";
import ConfirmationModal from "@/components/common/ConfirmationModal.vue";

export default {
    name: "PurchaseRequisitionList",
    components: {
        DataTable,
        SearchFilter,
        PaginationComponent,
        ConfirmationModal,
    },
    setup() {
        const router = useRouter();
        const purchaseRequisitions = ref([]);
        const isLoading = ref(true);
        const currentPage = ref(1);
        const totalPages = ref(1);
        const totalItems = ref(0);
        const itemsPerPage = ref(10);
        const searchQuery = ref("");
        const showDeleteModal = ref(false);
        const selectedPR = ref(null);

        const filters = reactive({
            status: "",
            date_from: "",
            date_to: "",
        });

        const sortKey = ref("pr_date");
        const sortOrder = ref("desc");

        // Table columns definition
        const columns = [
            { key: "pr_number", label: "PR Number", sortable: true },
            {
                key: "pr_date",
                label: "Date",
                sortable: true,
                template: "pr_date",
            },
            {
                key: "requester",
                label: "Requester",
                sortable: false,
                template: "requester",
            },
            {
                key: "status",
                label: "Status",
                sortable: true,
                template: "status",
            },
            { key: "notes", label: "Notes", sortable: false },
        ];

        // Computed pagination values
        const paginationFrom = computed(() => {
            return (currentPage.value - 1) * itemsPerPage.value + 1;
        });

        const paginationTo = computed(() => {
            return Math.min(
                currentPage.value * itemsPerPage.value,
                totalItems.value
            );
        });

        // Fetch purchase requisitions from API
        const fetchPurchaseRequisitions = async () => {
            isLoading.value = true;

            try {
                const params = {
                    page: currentPage.value,
                    per_page: itemsPerPage.value,
                    search: searchQuery.value,
                    status: filters.status,
                    date_from: filters.date_from,
                    date_to: filters.date_to,
                    sort_field: sortKey.value,
                    sort_direction: sortOrder.value,
                };

                const response =
                    await PurchaseRequisitionService.getAllPurchaseRequisitions(
                        params
                    );

                // Check if we have valid data
                if (response.data && response.data.data) {
                    purchaseRequisitions.value = response.data.data;

                    if (response.data.meta) {
                        totalItems.value = response.data.meta.total || 0;
                        totalPages.value = response.data.meta.last_page || 1;
                        currentPage.value =
                            response.data.meta.current_page || 1;
                    }
                } else {
                    purchaseRequisitions.value = [];
                }
            } catch (error) {
                console.error("Error fetching purchase requisitions:", error);
                purchaseRequisitions.value = [];
            } finally {
                isLoading.value = false;
            }
        };

        const clearSearch = () => {
            searchQuery.value = "";
            fetchPurchaseRequisitions();
        };

        const handleSort = ({ key, order }) => {
            sortKey.value = key;
            sortOrder.value = order;
            fetchPurchaseRequisitions();
        };

        const changePage = (page) => {
            currentPage.value = page;
            fetchPurchaseRequisitions();
        };

        const getStatusClass = (status) => {
            switch (status) {
                case "draft":
                    return "status-draft";
                case "pending":
                    return "status-pending";
                case "approved":
                    return "status-approved";
                case "rejected":
                    return "status-rejected";
                case "canceled":
                    return "status-canceled";
                default:
                    return "status-draft";
            }
        };

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

        const formatDate = (dateString) => {
            if (!dateString) return "N/A";
            const date = new Date(dateString);
            return date.toLocaleDateString("en-US", {
                year: "numeric",
                month: "short",
                day: "2-digit",
            });
        };

        // Navigation actions
        const openCreateForm = () => {
            router.push("/purchasing/requisitions/create");
        };

        const viewPR = (pr) => {
            router.push(`/purchasing/requisitions/${pr.pr_id}`);
        };

        const editPR = (pr) => {
            router.push(`/purchasing/requisitions/${pr.pr_id}/edit`);
        };

        const approvePR = (pr) => {
            router.push(`/purchasing/requisitions/${pr.pr_id}/approve`);
        };

        const convertToRFQ = (pr) => {
            router.push(`/purchasing/requisitions/${pr.pr_id}/convert`);
        };

        const confirmDelete = (pr) => {
            selectedPR.value = pr;
            showDeleteModal.value = true;
        };

        const closeDeleteModal = () => {
            showDeleteModal.value = false;
            selectedPR.value = null;
        };

        const deletePR = async () => {
            if (!selectedPR.value) return;

            try {
                await PurchaseRequisitionService.deletePurchaseRequisition(
                    selectedPR.value.pr_id
                );
                fetchPurchaseRequisitions();
                closeDeleteModal();
            } catch (error) {
                if (error.response && error.response.status === 400) {
                    // Show error message if PR cannot be deleted
                    alert(
                        error.response.data.message ||
                            "This purchase requisition cannot be deleted in its current status."
                    );
                } else {
                    console.error(
                        "Error deleting purchase requisition:",
                        error
                    );
                }
                closeDeleteModal();
            }
        };

        // Initialize
        onMounted(() => {
            fetchPurchaseRequisitions();
        });

        return {
            purchaseRequisitions,
            isLoading,
            columns,
            currentPage,
            totalPages,
            totalItems,
            paginationFrom,
            paginationTo,
            searchQuery,
            filters,
            showDeleteModal,
            selectedPR,
            fetchPurchaseRequisitions,
            clearSearch,
            handleSort,
            changePage,
            getStatusClass,
            getStatusLabel,
            formatDate,
            openCreateForm,
            viewPR,
            editPR,
            approvePR,
            convertToRFQ,
            confirmDelete,
            closeDeleteModal,
            deletePR,
        };
    },
};
</script>

<style scoped>
.pr-list-container {
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

.convert-btn {
    color: var(--info-color);
}

.convert-btn:hover {
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

.status-rejected {
    background-color: #fee2e2;
    color: #b91c1c;
}

.status-canceled {
    background-color: #e2e8f0;
    color: #475569;
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
