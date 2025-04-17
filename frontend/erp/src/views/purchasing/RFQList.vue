<!-- src/views/purchasing/RFQList.vue -->
<template>
    <div class="rfq-list-container">
        <div class="page-header">
            <h1>Request for Quotations</h1>
            <button @click="openCreateForm" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create RFQ
            </button>
        </div>

        <div class="filter-section">
            <SearchFilter
                v-model:value="searchQuery"
                placeholder="Search by RFQ number..."
                @search="fetchRFQs"
                @clear="clearSearch"
            >
                <template v-slot:filters>
                    <div class="filter-group">
                        <label for="status-filter">Status</label>
                        <select
                            id="status-filter"
                            v-model="filters.status"
                            @change="fetchRFQs"
                        >
                            <option value="">All Status</option>
                            <option value="draft">Draft</option>
                            <option value="sent">Sent</option>
                            <option value="closed">Closed</option>
                            <option value="canceled">Canceled</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="date-from">Date From</label>
                        <input
                            type="date"
                            id="date-from"
                            v-model="filters.date_from"
                            @change="fetchRFQs"
                        />
                    </div>
                    <div class="filter-group">
                        <label for="date-to">Date To</label>
                        <input
                            type="date"
                            id="date-to"
                            v-model="filters.date_to"
                            @change="fetchRFQs"
                        />
                    </div>
                </template>
            </SearchFilter>
        </div>

        <div class="data-container">
            <DataTable
                :columns="columns"
                :items="rfqs || []"
                :is-loading="isLoading"
                :key-field="'rfq_id'"
                :initial-sort-key="'rfq_date'"
                :initial-sort-order="'desc'"
                :empty-title="'No RFQs Found'"
                :empty-message="'Try adjusting your search or filters, or create a new RFQ.'"
                :empty-icon="'fas fa-file-alt'"
                @sort="handleSort"
            >
                <template v-slot:rfq_date="{ value }">
                    {{ formatDate(value) }}
                </template>

                <template v-slot:validity_date="{ value }">
                    {{ formatDate(value) }}
                </template>

                <template v-slot:status="{ value }">
                    <span :class="['status-badge', getStatusClass(value)]">
                        {{ value.charAt(0).toUpperCase() + value.slice(1) }}
                    </span>
                </template>

                <template v-slot:actions="{ item }">
                    <div class="action-buttons">
                        <button
                            @click="viewRFQ(item)"
                            class="action-btn view-btn"
                            title="View Details"
                        >
                            <i class="fas fa-eye"></i>
                        </button>
                        <button
                            v-if="item.status === 'draft'"
                            @click="editRFQ(item)"
                            class="action-btn edit-btn"
                            title="Edit RFQ"
                        >
                            <i class="fas fa-edit"></i>
                        </button>
                        <button
                            v-if="item.status === 'draft'"
                            @click="confirmDelete(item)"
                            class="action-btn delete-btn"
                            title="Delete RFQ"
                        >
                            <i class="fas fa-trash"></i>
                        </button>
                        <button
                            v-if="item.status === 'draft'"
                            @click="sendRFQ(item)"
                            class="action-btn send-btn"
                            title="Send to Vendors"
                        >
                            <i class="fas fa-paper-plane"></i>
                        </button>
                        <button
                            v-if="item.status === 'sent'"
                            @click="compareQuotations(item)"
                            class="action-btn compare-btn"
                            title="Compare Quotations"
                        >
                            <i class="fas fa-balance-scale"></i>
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
                    :total="totalRFQs"
                    @page-changed="changePage"
                />
            </div>
        </div>

        <!-- Confirmation Modal for Delete -->
        <ConfirmationModal
            v-if="showDeleteModal"
            :title="'Delete RFQ'"
            :message="
                'Are you sure you want to delete RFQ <strong>' +
                (selectedRFQ?.rfq_number || '') +
                '</strong>? This action cannot be undone.'
            "
            :confirm-button-text="'Delete'"
            :confirm-button-class="'btn btn-danger'"
            @confirm="deleteRFQ"
            @close="closeDeleteModal"
        />
    </div>
</template>

<script>
import { ref, reactive, computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import DataTable from "@/components/common/DataTable.vue";
import SearchFilter from "@/components/common/SearchFilter.vue";
import PaginationComponent from "@/components/common/Pagination.vue";
import ConfirmationModal from "@/components/common/ConfirmationModal.vue";
import axios from "axios";

export default {
    name: "RFQList",
    components: {
        DataTable,
        SearchFilter,
        PaginationComponent,
        ConfirmationModal,
    },
    setup() {
        const router = useRouter();
        const rfqs = ref([]);
        const isLoading = ref(true);
        const currentPage = ref(1);
        const totalPages = ref(1);
        const totalRFQs = ref(0);
        const itemsPerPage = ref(10);
        const searchQuery = ref("");
        const showDeleteModal = ref(false);
        const selectedRFQ = ref(null);

        const filters = reactive({
            status: "",
            date_from: "",
            date_to: "",
        });

        const sortKey = ref("rfq_date");
        const sortOrder = ref("desc");

        // Table columns definition
        const columns = [
            { key: "rfq_number", label: "RFQ Number", sortable: true },
            {
                key: "rfq_date",
                label: "RFQ Date",
                sortable: true,
                template: "rfq_date",
            },
            {
                key: "validity_date",
                label: "Valid Until",
                sortable: true,
                template: "validity_date",
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
                totalRFQs.value
            );
        });

        // Fetch RFQs from API
        const fetchRFQs = async () => {
            isLoading.value = true;

            try {
                const params = {
                    page: currentPage.value,
                    per_page: itemsPerPage.value,
                    search: searchQuery.value,
                    sort_field: sortKey.value,
                    sort_direction: sortOrder.value,
                    ...filters,
                };

                const response = await axios.get(
                    "/api/request-for-quotations",
                    { params }
                );

                if (response.data && response.data.data) {
                    rfqs.value = response.data.data.data || [];
                    totalRFQs.value = response.data.data.total || 0;
                    totalPages.value = response.data.data.last_page || 1;
                    currentPage.value = response.data.data.current_page || 1;
                }
            } catch (error) {
                console.error("Error fetching RFQs:", error);
                rfqs.value = [];
            } finally {
                isLoading.value = false;
            }
        };

        const clearSearch = () => {
            searchQuery.value = "";
            fetchRFQs();
        };

        const handleSort = ({ key, order }) => {
            sortKey.value = key;
            sortOrder.value = order;
            fetchRFQs();
        };

        const changePage = (page) => {
            currentPage.value = page;
            fetchRFQs();
        };

        const formatDate = (dateString) => {
            if (!dateString) return "N/A";
            const date = new Date(dateString);
            return date.toLocaleDateString();
        };

        const getStatusClass = (status) => {
            switch (status) {
                case "draft":
                    return "status-draft";
                case "sent":
                    return "status-sent";
                case "closed":
                    return "status-closed";
                case "canceled":
                    return "status-canceled";
                default:
                    return "status-draft";
            }
        };

        const openCreateForm = () => {
            router.push("/purchasing/rfqs/create");
        };

        const viewRFQ = (rfq) => {
            router.push(`/purchasing/rfqs/${rfq.rfq_id}`);
        };

        const editRFQ = (rfq) => {
            router.push(`/purchasing/rfqs/${rfq.rfq_id}/edit`);
        };

        const confirmDelete = (rfq) => {
            selectedRFQ.value = rfq;
            showDeleteModal.value = true;
        };

        const closeDeleteModal = () => {
            showDeleteModal.value = false;
            selectedRFQ.value = null;
        };

        const deleteRFQ = async () => {
            if (!selectedRFQ.value) return;

            try {
                await axios.delete(
                    `/api/request-for-quotations/${selectedRFQ.value.rfq_id}`
                );
                fetchRFQs();
                closeDeleteModal();
            } catch (error) {
                console.error("Error deleting RFQ:", error);
                if (
                    error.response &&
                    error.response.data &&
                    error.response.data.message
                ) {
                    alert(error.response.data.message);
                } else {
                    alert("Failed to delete RFQ. Please try again.");
                }
                closeDeleteModal();
            }
        };

        const sendRFQ = (rfq) => {
            router.push(`/purchasing/rfqs/${rfq.rfq_id}/send`);
        };

        const compareQuotations = (rfq) => {
            router.push(`/purchasing/rfqs/${rfq.rfq_id}/compare`);
        };

        // Initialize
        onMounted(() => {
            fetchRFQs();
        });

        return {
            rfqs,
            isLoading,
            columns,
            currentPage,
            totalPages,
            totalRFQs,
            paginationFrom,
            paginationTo,
            searchQuery,
            filters,
            showDeleteModal,
            selectedRFQ,
            fetchRFQs,
            clearSearch,
            handleSort,
            changePage,
            formatDate,
            getStatusClass,
            openCreateForm,
            viewRFQ,
            editRFQ,
            confirmDelete,
            closeDeleteModal,
            deleteRFQ,
            sendRFQ,
            compareQuotations,
        };
    },
};
</script>

<style scoped>
.rfq-list-container {
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

.delete-btn {
    color: var(--danger-color);
}

.delete-btn:hover {
    background-color: var(--danger-bg);
}

.send-btn {
    color: #6366f1;
}

.send-btn:hover {
    background-color: #eef2ff;
}

.compare-btn {
    color: #8b5cf6;
}

.compare-btn:hover {
    background-color: #f3f4f6;
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

.status-sent {
    background-color: #dbeafe;
    color: #1e40af;
}

.status-closed {
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
