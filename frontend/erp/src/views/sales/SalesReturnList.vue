<!-- src/views/sales/SalesReturnList.vue -->
<template>
    <div class="sales-returns">
        <!-- Search and Filter Section -->
        <div class="page-actions">
            <SearchFilter
                v-model:value="searchQuery"
                placeholder="Cari pengembalian..."
                @search="handleSearch"
                @clear="clearSearch"
            >
                <template #filters>
                    <div class="filter-group">
                        <label>Status</label>
                        <select v-model="statusFilter" @change="applyFilters">
                            <option value="">Semua Status</option>
                            <option value="Pending">Pending</option>
                            <option value="Processed">Diproses</option>
                            <option value="Completed">Selesai</option>
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
                    <button class="btn btn-primary" @click="createReturn">
                        <i class="fas fa-plus"></i> Buat Pengembalian
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
            :items="returns"
            :is-loading="isLoading"
            keyField="return_id"
            emptyIcon="fas fa-undo-alt"
            emptyTitle="Tidak ada pengembalian"
            emptyMessage="Tidak ada data pengembalian yang tersedia untuk ditampilkan."
            @sort="handleSort"
        >
            <!-- Custom cell templates -->
            <template #return_number="{ value }">
                <div class="return-number">{{ value }}</div>
            </template>

            <template #customer="{ item }">
                <div class="customer-info">
                    <div class="customer-name">
                        {{ item.customer?.name || "-" }}
                    </div>
                </div>
            </template>

            <template #invoice="{ item }">
                <div class="invoice-info">
                    {{ item.invoice?.invoice_number || "-" }}
                </div>
            </template>

            <template #status="{ value }">
                <span class="status-badge" :class="getStatusClass(value)">
                    {{ getStatusLabel(value) }}
                </span>
            </template>

            <template #date="{ value }">
                {{ formatDate(value) }}
            </template>

            <template #actions="{ item }">
                <div class="actions-cell">
                    <button
                        class="action-btn view"
                        title="Lihat Detail"
                        @click="viewReturn(item)"
                    >
                        <i class="fas fa-eye"></i>
                    </button>
                    <button
                        v-if="canEditReturn(item)"
                        class="action-btn edit"
                        title="Edit Pengembalian"
                        @click="editReturn(item)"
                    >
                        <i class="fas fa-edit"></i>
                    </button>
                    <button
                        v-if="item.status === 'Pending'"
                        class="action-btn process"
                        title="Proses Pengembalian"
                        @click="confirmProcess(item)"
                    >
                        <i class="fas fa-check-circle"></i>
                    </button>
                    <button
                        v-if="canDeleteReturn(item)"
                        class="action-btn delete"
                        title="Hapus Pengembalian"
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
            :message="`Apakah Anda yakin ingin menghapus pengembalian <strong>${returnToDelete.return_number}</strong>?<br>Tindakan ini tidak dapat dibatalkan.`"
            confirm-button-text="Hapus"
            confirm-button-class="btn btn-danger"
            @confirm="deleteReturn"
            @close="closeDeleteModal"
        />

        <!-- Process Confirmation Modal -->
        <ConfirmationModal
            v-if="showProcessModal"
            title="Konfirmasi Proses"
            :message="`Apakah Anda yakin ingin memproses pengembalian <strong>${returnToProcess.return_number}</strong>?<br>Stok barang akan diperbarui dan status pengembalian akan berubah menjadi 'Diproses'.`"
            confirm-button-text="Proses"
            confirm-button-class="btn btn-primary"
            @confirm="processReturn"
            @close="closeProcessModal"
        />
    </div>
</template>

<script>
import { ref, reactive, onMounted } from "vue";
import { useRouter } from "vue-router";
import SalesReturnService from "@/services/SalesReturnService";
import DataTable from "@/components/common/DataTable.vue";
import SearchFilter from "@/components/common/SearchFilter.vue";
import PaginationComponent from "@/components/common/Pagination.vue";
import ConfirmationModal from "@/components/common/ConfirmationModal.vue";

export default {
    name: "SalesReturnList",
    components: {
        DataTable,
        SearchFilter,
        PaginationComponent,
        ConfirmationModal,
    },
    setup() {
        const router = useRouter();
        const returns = ref([]);
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
        const customDateRange = reactive({
            startDate: new Date().toISOString().substr(0, 10),
            endDate: new Date().toISOString().substr(0, 10),
        });

        // Sort
        const sortKey = ref("return_id");
        const sortOrder = ref("desc");

        // Modals
        const showDeleteModal = ref(false);
        const returnToDelete = ref({});
        const showProcessModal = ref(false);
        const returnToProcess = ref({});

        // Table columns
        const columns = [
            {
                key: "return_number",
                label: "No. Pengembalian",
                sortable: true,
                template: "return_number",
            },
            {
                key: "customer",
                label: "Pelanggan",
                sortable: false,
                template: "customer",
            },
            {
                key: "invoice",
                label: "No. Faktur",
                sortable: false,
                template: "invoice",
            },
            {
                key: "return_date",
                label: "Tanggal",
                sortable: true,
                template: "date",
            },
            {
                key: "status",
                label: "Status",
                sortable: true,
                template: "status",
            },
            {
                key: "return_reason",
                label: "Alasan",
                sortable: false,
            },
        ];

        // Fetch returns
        const fetchReturns = async () => {
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
                    params.start_date = customDateRange.startDate;
                    params.end_date = customDateRange.endDate;
                } else if (dateRangeFilter.value !== "all") {
                    params.date_range = dateRangeFilter.value;
                }

                const response = await SalesReturnService.getReturns(params);
                returns.value = response.data;

                // Set pagination data if available in response
                if (response.meta) {
                    totalItems.value = response.meta.total;
                    totalPages.value = response.meta.last_page;
                } else {
                    // If no pagination from API, calculate locally
                    totalItems.value = returns.value.length;
                    totalPages.value = Math.ceil(
                        totalItems.value / itemsPerPage.value
                    );
                }
            } catch (error) {
                console.error("Error fetching sales returns:", error);
                alert("Terjadi kesalahan saat memuat data. Silakan coba lagi.");
            } finally {
                isLoading.value = false;
            }
        };

        // Filters and pagination methods
        const handleSearch = () => {
            currentPage.value = 1;
            fetchReturns();
        };

        const clearSearch = () => {
            searchQuery.value = "";
            handleSearch();
        };

        const applyFilters = () => {
            currentPage.value = 1;
            fetchReturns();
        };

        const handleSort = (sortData) => {
            sortKey.value = sortData.key;
            sortOrder.value = sortData.order;
            fetchReturns();
        };

        const goToPage = (page) => {
            currentPage.value = page;
            fetchReturns();
        };

        // CRUD operations
        const createReturn = () => {
            router.push("/sales/returns/create");
        };

        const viewReturn = (item) => {
            router.push(`/sales/returns/${item.return_id}`);
        };

        const editReturn = (item) => {
            router.push(`/sales/returns/${item.return_id}/edit`);
        };

        const confirmDelete = (item) => {
            returnToDelete.value = item;
            showDeleteModal.value = true;
        };

        const closeDeleteModal = () => {
            showDeleteModal.value = false;
        };

        const deleteReturn = async () => {
            try {
                await SalesReturnService.deleteReturn(
                    returnToDelete.value.return_id
                );
                fetchReturns(); // Refresh list after delete
                showDeleteModal.value = false;
                alert("Pengembalian berhasil dihapus");
            } catch (error) {
                console.error("Error deleting return:", error);
                if (error.response?.data?.message) {
                    alert(error.response.data.message);
                } else {
                    alert("Terjadi kesalahan saat menghapus pengembalian");
                }
            }
        };

        const confirmProcess = (item) => {
            returnToProcess.value = item;
            showProcessModal.value = true;
        };

        const closeProcessModal = () => {
            showProcessModal.value = false;
        };

        const processReturn = async () => {
            try {
                await SalesReturnService.processReturn(
                    returnToProcess.value.return_id
                );
                fetchReturns(); // Refresh list after processing
                showProcessModal.value = false;
                alert("Pengembalian berhasil diproses");
            } catch (error) {
                console.error("Error processing return:", error);
                if (error.response?.data?.message) {
                    alert(error.response.data.message);
                } else {
                    alert("Terjadi kesalahan saat memproses pengembalian");
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

        const getStatusLabel = (status) => {
            switch (status) {
                case "Pending":
                    return "Pending";
                case "Processed":
                    return "Diproses";
                case "Completed":
                    return "Selesai";
                case "Cancelled":
                    return "Dibatalkan";
                default:
                    return status;
            }
        };

        const getStatusClass = (status) => {
            switch (status) {
                case "Pending":
                    return "status-pending";
                case "Processed":
                    return "status-processed";
                case "Completed":
                    return "status-completed";
                case "Cancelled":
                    return "status-cancelled";
                default:
                    return "";
            }
        };

        const canEditReturn = (item) => {
            // Can only edit returns that are in pending status
            return item.status === "Pending";
        };

        const canDeleteReturn = (item) => {
            // Can only delete returns that are not processed or completed
            return !["Processed", "Completed"].includes(item.status);
        };

        onMounted(() => {
            fetchReturns();
        });

        return {
            returns,
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
            returnToDelete,
            showProcessModal,
            returnToProcess,
            handleSearch,
            clearSearch,
            applyFilters,
            handleSort,
            goToPage,
            createReturn,
            viewReturn,
            editReturn,
            confirmProcess,
            processReturn,
            closeProcessModal,
            confirmDelete,
            closeDeleteModal,
            deleteReturn,
            formatDate,
            getStatusLabel,
            getStatusClass,
            canEditReturn,
            canDeleteReturn,
        };
    },
};
</script>

<style scoped>
.sales-returns {
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

.return-number {
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

.invoice-info {
    font-weight: 500;
    color: var(--gray-600);
}

.status-badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
}

.status-pending {
    background-color: #fef3c7;
    color: #d97706;
}

.status-processed {
    background-color: #dbeafe;
    color: #2563eb;
}

.status-completed {
    background-color: #d1fae5;
    color: #059669;
}

.status-cancelled {
    background-color: #fee2e2;
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

.action-btn.process {
    color: var(--success-color);
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
