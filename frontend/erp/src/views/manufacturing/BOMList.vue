<!-- src/views/manufacturing/BOMList.vue -->
<template>
    <div class="bom-list">
        <div class="page-header">
            <h1>Bills of Materials</h1>
            <button class="btn btn-primary" @click="openCreateModal">
                <i class="fas fa-plus"></i> Create New BOM
            </button>
        </div>

        <!-- Search and Filter -->
        <SearchFilter
            v-model:value="searchQuery"
            placeholder="Search BOMs..."
            @search="applyFilters"
            @clear="clearSearch"
        >
            <template #filters>
                <div class="filter-group">
                    <label for="statusFilter">Status</label>
                    <select
                        id="statusFilter"
                        v-model="filters.status"
                        @change="applyFilters"
                    >
                        <option value="">All Statuses</option>
                        <option value="Active">Active</option>
                        <option value="Draft">Draft</option>
                        <option value="Obsolete">Obsolete</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="productFilter">Product</label>
                    <select
                        id="productFilter"
                        v-model="filters.productId"
                        @change="applyFilters"
                    >
                        <option value="">All Products</option>
                        <option
                            v-for="product in products"
                            :key="product.product_id"
                            :value="product.product_id"
                        >
                            {{ product.name }}
                        </option>
                    </select>
                </div>
            </template>
        </SearchFilter>

        <!-- DataTable -->
        <DataTable
            :columns="columns"
            :items="filteredBOMs"
            :is-loading="isLoading"
            keyField="bom_id"
            emptyIcon="fas fa-clipboard-list"
            emptyTitle="No BOMs found"
            emptyMessage="No BOMs match your search criteria or no BOMs have been created yet."
            @sort="handleSort"
        >
            <template #status="{ value }">
                <span class="status-badge" :class="getStatusClass(value)">
                    {{ value }}
                </span>
            </template>

            <template #date="{ value }">
                {{ formatDate(value) }}
            </template>

            <template #actions="{ item }">
                <button
                    class="action-btn"
                    title="View BOM Details"
                    @click="viewBOM(item)"
                >
                    <i class="fas fa-eye"></i>
                </button>
                <button
                    class="action-btn"
                    title="Edit BOM"
                    @click="editBOM(item)"
                >
                    <i class="fas fa-edit"></i>
                </button>
                <button
                    class="action-btn"
                    title="Delete BOM"
                    @click="confirmDelete(item)"
                >
                    <i class="fas fa-trash"></i>
                </button>
            </template>
        </DataTable>

        <!-- Pagination -->
        <PaginationComponent
            v-if="totalItems > 0"
            :current-page="currentPage"
            :total-pages="totalPages"
            :from="(currentPage - 1) * perPage + 1"
            :to="Math.min(currentPage * perPage, totalItems)"
            :total="totalItems"
            @page-changed="changePage"
        />

        <!-- BOM Form Modal -->
        <BOMFormModal
            v-if="showBOMModal"
            :is-edit-mode="isEditMode"
            :bom-data="currentBOM"
            @close="closeBOMModal"
            @save="saveBOM"
        />

        <!-- Delete Confirmation Modal -->
        <ConfirmationModal
            v-if="showDeleteModal"
            title="Confirm Delete"
            :message="`Are you sure you want to delete BOM <strong>${bomToDelete.bom_code}</strong> for product <strong>${bomToDelete.product?.name}</strong>?<br>This action cannot be undone.`"
            confirm-button-text="Delete"
            confirm-button-class="btn btn-danger"
            @confirm="deleteBOM"
            @close="closeDeleteModal"
        />
    </div>
</template>

<script>
import { ref, computed, onMounted, reactive } from "vue";
import { useRouter } from "vue-router";
import BOMService from "@/services/BOMService";
import ProductService from "@/services/ProductService";
import BOMFormModal from "@/components/manufacturing/BOMFormModal.vue";
import ConfirmationModal from "@/components/common/ConfirmationModal.vue";
import SearchFilter from "@/components/common/SearchFilter.vue";
import DataTable from "@/components/common/DataTable.vue";
import PaginationComponent from "@/components/common/Pagination.vue";

export default {
    name: "BOMList",
    components: {
        BOMFormModal,
        ConfirmationModal,
        SearchFilter,
        DataTable,
        PaginationComponent,
    },
    setup() {
        const router = useRouter();
        const boms = ref([]);
        const products = ref([]);
        const isLoading = ref(true);
        const searchQuery = ref("");
        const sortKey = ref("bom_code");
        const sortOrder = ref("asc");
        const currentPage = ref(1);
        const perPage = ref(10);
        const totalItems = ref(0);
        const totalPages = ref(1);

        const filters = reactive({
            status: "",
            productId: "",
        });

        // Modals
        const showBOMModal = ref(false);
        const showDeleteModal = ref(false);
        const isEditMode = ref(false);
        const currentBOM = ref({});
        const bomToDelete = ref({});

        // Table columns
        const columns = ref([
            { key: "bom_code", label: "BOM Code", sortable: true },
            { key: "product.name", label: "Product", sortable: true },
            { key: "revision", label: "Revision", sortable: true },
            {
                key: "effective_date",
                label: "Effective Date",
                sortable: true,
                template: "date",
            },
            {
                key: "status",
                label: "Status",
                sortable: true,
                template: "status",
            },
        ]);

        const fetchBOMs = async () => {
            isLoading.value = true;
            try {
                const response = await BOMService.getBOMs({
                    page: currentPage.value,
                    per_page: perPage.value,
                    sort_by: sortKey.value,
                    sort_order: sortOrder.value,
                    search: searchQuery.value,
                    status: filters.status,
                    product_id: filters.productId,
                });

                boms.value = response.data || [];
                totalItems.value = response.meta?.total || boms.value.length;
                totalPages.value = response.meta?.last_page || 1;
            } catch (error) {
                console.error("Error fetching BOMs:", error);
            } finally {
                isLoading.value = false;
            }
        };

        const fetchProducts = async () => {
            try {
                const response = await ProductService.getProducts();
                products.value = response.data || [];
            } catch (error) {
                console.error("Error fetching products:", error);
            }
        };

        // Computed properties
        const filteredBOMs = computed(() => {
            return boms.value;
        });

        // Methods
        const applyFilters = () => {
            currentPage.value = 1;
            fetchBOMs();
        };

        const clearSearch = () => {
            searchQuery.value = "";
            applyFilters();
        };

        const handleSort = ({ key, order }) => {
            sortKey.value = key;
            sortOrder.value = order;
            fetchBOMs();
        };

        const changePage = (page) => {
            currentPage.value = page;
            fetchBOMs();
        };

        const formatDate = (dateString) => {
            if (!dateString) return "-";

            const date = new Date(dateString);
            return date.toLocaleDateString("en-US", {
                year: "numeric",
                month: "short",
                day: "numeric",
            });
        };

        const getStatusClass = (status) => {
            switch (status) {
                case "Active":
                    return "status-active";
                case "Draft":
                    return "status-draft";
                case "Obsolete":
                    return "status-obsolete";
                default:
                    return "";
            }
        };

        const viewBOM = (bom) => {
            router.push(`/manufacturing/boms/${bom.bom_id}`);
        };

        const openCreateModal = () => {
            isEditMode.value = false;
            currentBOM.value = {
                product_id: "",
                bom_code: "",
                revision: "1.0",
                effective_date: new Date().toISOString().split("T")[0],
                status: "Draft",
                standard_quantity: 1,
                uom_id: "",
            };
            showBOMModal.value = true;
        };

        const editBOM = (bom) => {
            isEditMode.value = true;
            currentBOM.value = { ...bom };
            showBOMModal.value = true;
        };

        const closeBOMModal = () => {
            showBOMModal.value = false;
        };

        const saveBOM = async (bomData) => {
            try {
                if (isEditMode.value) {
                    await BOMService.updateBOM(bomData.bom_id, bomData);
                } else {
                    await BOMService.createBOM(bomData);
                }
                fetchBOMs();
                closeBOMModal();
            } catch (error) {
                console.error("Error saving BOM:", error);
                alert("Failed to save BOM. Please try again.");
            }
        };

        const confirmDelete = (bom) => {
            bomToDelete.value = bom;
            showDeleteModal.value = true;
        };

        const closeDeleteModal = () => {
            showDeleteModal.value = false;
        };

        const deleteBOM = async () => {
            try {
                await BOMService.deleteBOM(bomToDelete.value.bom_id);
                fetchBOMs();
                closeDeleteModal();
            } catch (error) {
                console.error("Error deleting BOM:", error);
                alert(
                    "Failed to delete BOM. It may be in use or you may not have permission."
                );
                closeDeleteModal();
            }
        };

        onMounted(() => {
            fetchBOMs();
            fetchProducts();
        });

        return {
            boms,
            products,
            columns,
            isLoading,
            searchQuery,
            filters,
            filteredBOMs,
            currentPage,
            perPage,
            totalItems,
            totalPages,
            showBOMModal,
            showDeleteModal,
            isEditMode,
            currentBOM,
            bomToDelete,
            applyFilters,
            clearSearch,
            handleSort,
            changePage,
            formatDate,
            getStatusClass,
            viewBOM,
            openCreateModal,
            editBOM,
            closeBOMModal,
            saveBOM,
            confirmDelete,
            closeDeleteModal,
            deleteBOM,
        };
    },
};
</script>

<style scoped>
.bom-list {
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

.status-badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
}

.status-active {
    background-color: var(--success-bg);
    color: var(--success-color);
}

.status-draft {
    background-color: var(--warning-bg);
    color: var(--warning-color);
}

.status-obsolete {
    background-color: var(--danger-bg);
    color: var(--danger-color);
}

.action-btn {
    background: none;
    border: none;
    color: var(--gray-500);
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 0.25rem;
    transition: background-color 0.2s;
}

.action-btn:hover {
    background-color: var(--gray-100);
    color: var(--gray-700);
}
</style>
