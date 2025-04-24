<!-- src/views/purchasing/VendorEvaluationList.vue -->
<template>
    <div class="evaluation-list-container">
        <div class="page-header">
            <h1>Vendor Evaluations</h1>
            <button @click="openCreateForm" class="btn btn-primary">
                <i class="fas fa-plus"></i> New Evaluation
            </button>
        </div>

        <div class="filter-section">
            <SearchFilter
                v-model:value="searchQuery"
                placeholder="Search vendors..."
                @search="fetchEvaluations"
                @clear="clearSearch"
            >
                <template v-slot:filters>
                    <div class="filter-group">
                        <label for="vendor-filter">Vendor</label>
                        <select
                            id="vendor-filter"
                            v-model="filters.vendor_id"
                            @change="fetchEvaluations"
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
                            @change="fetchEvaluations"
                        />
                    </div>

                    <div class="filter-group">
                        <label for="date-to">Date To</label>
                        <input
                            type="date"
                            id="date-to"
                            v-model="filters.date_to"
                            @change="fetchEvaluations"
                        />
                    </div>
                </template>
            </SearchFilter>
        </div>

        <div class="data-container">
            <DataTable
                :columns="columns"
                :items="evaluations"
                :is-loading="isLoading"
                :key-field="'evaluation_id'"
                :initial-sort-key="'evaluation_date'"
                :initial-sort-order="'desc'"
                :empty-title="'No Evaluations Found'"
                :empty-message="'Try adjusting your search or filters, or create a new evaluation.'"
                :empty-icon="'fas fa-star'"
                @sort="handleSort"
            >
                <template v-slot:evaluation_date="{ value }">
                    {{ formatDate(value) }}
                </template>

                <template v-slot:vendor_name="{ item }">
                    {{ item.vendor ? item.vendor.name : "N/A" }}
                </template>

                <template v-slot:quality_score="{ value }">
                    <div class="score-bar">
                        <div
                            class="score-fill"
                            :style="{ width: `${value * 20}%` }"
                        ></div>
                        <span class="score-text">{{ value }}/5</span>
                    </div>
                </template>

                <template v-slot:delivery_score="{ value }">
                    <div class="score-bar">
                        <div
                            class="score-fill"
                            :style="{ width: `${value * 20}%` }"
                        ></div>
                        <span class="score-text">{{ value }}/5</span>
                    </div>
                </template>

                <template v-slot:price_score="{ value }">
                    <div class="score-bar">
                        <div
                            class="score-fill"
                            :style="{ width: `${value * 20}%` }"
                        ></div>
                        <span class="score-text">{{ value }}/5</span>
                    </div>
                </template>

                <template v-slot:service_score="{ value }">
                    <div class="score-bar">
                        <div
                            class="score-fill"
                            :style="{ width: `${value * 20}%` }"
                        ></div>
                        <span class="score-text">{{ value }}/5</span>
                    </div>
                </template>

                <template v-slot:total_score="{ value }">
                    <div :class="['total-score', getScoreClass(value)]">
                        {{ value.toFixed(2) }}
                    </div>
                </template>

                <template v-slot:actions="{ item }">
                    <div class="action-buttons">
                        <router-link
                            :to="`/purchasing/evaluations/${item.evaluation_id}`"
                            class="action-btn view-btn"
                            title="View Details"
                        >
                            <i class="fas fa-eye"></i>
                        </router-link>
                        <router-link
                            :to="`/purchasing/evaluations/${item.evaluation_id}/edit`"
                            class="action-btn edit-btn"
                            title="Edit Evaluation"
                        >
                            <i class="fas fa-edit"></i>
                        </router-link>
                        <button
                            @click="confirmDelete(item)"
                            class="action-btn delete-btn"
                            title="Delete Evaluation"
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
                    :total="totalEvaluations"
                    @page-changed="changePage"
                />
            </div>
        </div>

        <!-- Confirmation Modal for Delete -->
        <ConfirmationModal
            v-if="showDeleteModal"
            :title="'Delete Evaluation'"
            :message="'Are you sure you want to delete this vendor evaluation? This action cannot be undone.'"
            :confirm-button-text="'Delete'"
            :confirm-button-class="'btn btn-danger'"
            @confirm="deleteEvaluation"
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
import VendorService from "@/services/VendorService";
import VendorEvaluationService from "@/services/VendorEvaluationService";

export default {
    name: "VendorEvaluationList",
    components: {
        DataTable,
        SearchFilter,
        PaginationComponent,
        ConfirmationModal,
    },
    setup() {
        const router = useRouter();
        const evaluations = ref([]);
        const vendors = ref([]);
        const isLoading = ref(true);
        const currentPage = ref(1);
        const totalPages = ref(1);
        const totalEvaluations = ref(0);
        const itemsPerPage = ref(10);
        const searchQuery = ref("");
        const showDeleteModal = ref(false);
        const selectedEvaluation = ref(null);

        const filters = reactive({
            vendor_id: "",
            date_from: "",
            date_to: "",
        });

        const sortKey = ref("evaluation_date");
        const sortOrder = ref("desc");

        // Table columns definition
        const columns = [
            {
                key: "evaluation_date",
                label: "Date",
                sortable: true,
                template: "evaluation_date",
            },
            {
                key: "vendor_name",
                label: "Vendor",
                sortable: false,
                template: "vendor_name",
            },
            {
                key: "quality_score",
                label: "Quality",
                sortable: true,
                template: "quality_score",
            },
            {
                key: "delivery_score",
                label: "Delivery",
                sortable: true,
                template: "delivery_score",
            },
            {
                key: "price_score",
                label: "Price",
                sortable: true,
                template: "price_score",
            },
            {
                key: "service_score",
                label: "Service",
                sortable: true,
                template: "service_score",
            },
            {
                key: "total_score",
                label: "Overall",
                sortable: true,
                template: "total_score",
            },
        ];

        // Computed pagination values
        const paginationFrom = computed(() => {
            return (currentPage.value - 1) * itemsPerPage.value + 1;
        });

        const paginationTo = computed(() => {
            return Math.min(
                currentPage.value * itemsPerPage.value,
                totalEvaluations.value
            );
        });

        // Fetch evaluations from API
        const fetchEvaluations = async () => {
            isLoading.value = true;

            try {
                const params = {
                    page: currentPage.value,
                    per_page: itemsPerPage.value,
                    vendor_id: filters.vendor_id,
                    date_from: filters.date_from,
                    date_to: filters.date_to,
                    sort_field: sortKey.value,
                    sort_direction: sortOrder.value,
                };

                const response =
                    await VendorEvaluationService.getAllEvaluations(params);

                if (response.data && response.data.data) {
                    evaluations.value = response.data.data;

                    if (response.data.meta) {
                        totalEvaluations.value = response.data.meta.total || 0;
                        totalPages.value = response.data.meta.last_page || 1;
                        currentPage.value =
                            response.data.meta.current_page || 1;
                    }
                } else {
                    evaluations.value = [];
                    totalEvaluations.value = 0;
                    totalPages.value = 1;
                }
            } catch (error) {
                console.error("Error fetching evaluations:", error);
                evaluations.value = [];
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
            fetchEvaluations();
        };

        const handleSort = ({ key, order }) => {
            sortKey.value = key;
            sortOrder.value = order;
            fetchEvaluations();
        };

        const changePage = (page) => {
            currentPage.value = page;
            fetchEvaluations();
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

        const getScoreClass = (score) => {
            if (score >= 4.5) return "score-excellent";
            if (score >= 3.5) return "score-good";
            if (score >= 2.5) return "score-average";
            if (score >= 1.5) return "score-below-average";
            return "score-poor";
        };

        const openCreateForm = () => {
            router.push("/purchasing/evaluations/create");
        };

        const confirmDelete = (evaluation) => {
            selectedEvaluation.value = evaluation;
            showDeleteModal.value = true;
        };

        const closeDeleteModal = () => {
            showDeleteModal.value = false;
            selectedEvaluation.value = null;
        };

        const deleteEvaluation = async () => {
            if (!selectedEvaluation.value) return;

            try {
                await VendorEvaluationService.deleteEvaluation(
                    selectedEvaluation.value.evaluation_id
                );
                fetchEvaluations();
                closeDeleteModal();
            } catch (error) {
                console.error("Error deleting evaluation:", error);
                closeDeleteModal();
            }
        };

        // Initialize
        onMounted(() => {
            fetchEvaluations();
            fetchVendors();
        });

        return {
            evaluations,
            vendors,
            isLoading,
            columns,
            currentPage,
            totalPages,
            totalEvaluations,
            paginationFrom,
            paginationTo,
            searchQuery,
            filters,
            showDeleteModal,
            selectedEvaluation,
            fetchEvaluations,
            clearSearch,
            handleSort,
            changePage,
            formatDate,
            getScoreClass,
            openCreateForm,
            confirmDelete,
            closeDeleteModal,
            deleteEvaluation,
        };
    },
};
</script>

<style scoped>
.evaluation-list-container {
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

.score-bar {
    position: relative;
    width: 100%;
    height: 1.25rem;
    background-color: var(--gray-100);
    border-radius: 0.25rem;
    overflow: hidden;
}

.score-fill {
    position: absolute;
    top: 0;
    left: 0;
    height: 100%;
    background-color: var(--primary-color);
    border-radius: 0.25rem;
}

.score-text {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--gray-800);
    font-size: 0.75rem;
    font-weight: 500;
}

.total-score {
    font-weight: bold;
    font-size: 1rem;
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    text-align: center;
}

.score-excellent {
    background-color: #15803d;
    color: white;
}

.score-good {
    background-color: #65a30d;
    color: white;
}

.score-average {
    background-color: #f59e0b;
    color: white;
}

.score-below-average {
    background-color: #f97316;
    color: white;
}

.score-poor {
    background-color: #dc2626;
    color: white;
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
