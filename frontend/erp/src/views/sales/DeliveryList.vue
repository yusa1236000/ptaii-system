<!-- src/views/sales/DeliveryList.vue -->
<template>
    <div class="delivery-list">
        <div class="page-header">
            <h1>Daftar Pengiriman</h1>
            <button class="btn btn-primary" @click="createDelivery">
                <i class="fas fa-plus"></i> Buat Pengiriman Baru
            </button>
        </div>

        <!-- Search and Filter -->
        <SearchFilter
            v-model:value="searchQuery"
            placeholder="Cari pengiriman..."
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
                        <option value="">Semua Status</option>
                        <option value="Pending">Pending</option>
                        <option value="In Transit">Dalam Pengiriman</option>
                        <option value="Delivered">Terkirim</option>
                        <option value="Returned">Dikembalikan</option>
                        <option value="Cancelled">Dibatalkan</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="dateRangeFilter">Periode</label>
                    <select
                        id="dateRangeFilter"
                        v-model="filters.dateRange"
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
        </SearchFilter>

        <!-- Custom Date Range (when Custom is selected) -->
        <div v-if="filters.dateRange === 'custom'" class="custom-date-range">
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

        <!-- DataTable -->
        <DataTable
            :columns="columns"
            :items="deliveries"
            :is-loading="isLoading"
            keyField="delivery_id"
            emptyIcon="fas fa-truck"
            emptyTitle="Tidak ada pengiriman ditemukan"
            emptyMessage="Tidak ada pengiriman yang sesuai dengan kriteria pencarian Anda."
            @sort="handleSort"
        >
            <template #delivery_status="{ value }">
                <span class="status-badge" :class="getStatusClass(value)">
                    {{ getStatusLabel(value) }}
                </span>
            </template>

            <template #delivery_date="{ value }">
                {{ formatDate(value) }}
            </template>

            <template #expected_arrival_date="{ value }">
                {{ formatDate(value) }}
            </template>

            <template #actions="{ item }">
                <button
                    class="action-btn"
                    title="Lihat Detail"
                    @click="viewDelivery(item)"
                >
                    <i class="fas fa-eye"></i>
                </button>
                <button
                    class="action-btn"
                    title="Edit Pengiriman"
                    @click="editDelivery(item)"
                    v-if="canEdit(item)"
                >
                    <i class="fas fa-edit"></i>
                </button>
                <button
                    class="action-btn"
                    title="Hapus Pengiriman"
                    @click="confirmDelete(item)"
                    v-if="canDelete(item)"
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

        <!-- Delete Confirmation Modal -->
        <ConfirmationModal
            v-if="showDeleteModal"
            title="Konfirmasi Hapus"
            :message="`Apakah Anda yakin ingin menghapus pengiriman <strong>${deliveryToDelete.delivery_number}</strong>?<br>Tindakan ini tidak dapat dibatalkan.`"
            confirm-button-text="Hapus"
            confirm-button-class="btn btn-danger"
            @confirm="deleteDelivery"
            @close="closeDeleteModal"
        />
    </div>
</template>

<script>
import { ref, onMounted, reactive } from "vue";
import { useRouter } from "vue-router";
import DeliveryService from "@/services/DeliveryService";

export default {
    name: "DeliveryList",
    setup() {
        const router = useRouter();
        const deliveries = ref([]);
        const isLoading = ref(true);
        const searchQuery = ref("");
        const sortKey = ref("delivery_date");
        const sortOrder = ref("desc");
        const currentPage = ref(1);
        const perPage = ref(10);
        const totalItems = ref(0);
        const totalPages = ref(1);

        const filters = reactive({
            status: "",
            dateRange: "month",
        });

        const customDateRange = ref({
            startDate: new Date().toISOString().substr(0, 10),
            endDate: new Date().toISOString().substr(0, 10),
        });

        // Modal state
        const showDeleteModal = ref(false);
        const deliveryToDelete = ref({});

        // Table columns
        const columns = ref([
            { key: "delivery_number", label: "No. Pengiriman", sortable: true },
            { key: "order.so_number", label: "No. Pesanan", sortable: true },
            { key: "customer.name", label: "Pelanggan", sortable: true },
            {
                key: "delivery_date",
                label: "Tanggal Pengiriman",
                sortable: true,
                template: "delivery_date",
            },
            {
                key: "expected_arrival_date",
                label: "Est. Kedatangan",
                sortable: true,
                template: "expected_arrival_date",
            },
            {
                key: "delivery_status",
                label: "Status",
                sortable: true,
                template: "delivery_status",
            },
        ]);

        // Fetch deliveries from API
        const fetchDeliveries = async () => {
            isLoading.value = true;
            try {
                const params = {
                    page: currentPage.value,
                    per_page: perPage.value,
                    sort_by: sortKey.value,
                    sort_order: sortOrder.value,
                    search: searchQuery.value,
                    status: filters.status,
                };

                // Add date filters if applicable
                if (filters.dateRange !== "all") {
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);

                    const firstDayOfWeek = new Date(today);
                    firstDayOfWeek.setDate(
                        firstDayOfWeek.getDate() - firstDayOfWeek.getDay()
                    );

                    const firstDayOfMonth = new Date(
                        today.getFullYear(),
                        today.getMonth(),
                        1
                    );

                    switch (filters.dateRange) {
                        case "today":
                            params.date_from = today
                                .toISOString()
                                .split("T")[0];
                            params.date_to = today.toISOString().split("T")[0];
                            break;
                        case "week":
                            params.date_from = firstDayOfWeek
                                .toISOString()
                                .split("T")[0];
                            params.date_to = today.toISOString().split("T")[0];
                            break;
                        case "month":
                            params.date_from = firstDayOfMonth
                                .toISOString()
                                .split("T")[0];
                            params.date_to = today.toISOString().split("T")[0];
                            break;
                        case "custom":
                            params.date_from = customDateRange.value.startDate;
                            params.date_to = customDateRange.value.endDate;
                            break;
                    }
                }

                const response = await DeliveryService.getDeliveries(params);

                // Handle response data
                if (response.data) {
                    deliveries.value = response.data;

                    // Handle pagination metadata if available
                    if (response.meta) {
                        totalItems.value =
                            response.meta.total || deliveries.value.length;
                        totalPages.value =
                            response.meta.last_page ||
                            Math.ceil(totalItems.value / perPage.value);
                    } else {
                        totalItems.value = deliveries.value.length;
                        totalPages.value = 1;
                    }
                } else {
                    deliveries.value = [];
                    totalItems.value = 0;
                    totalPages.value = 1;
                }
            } catch (error) {
                console.error("Error fetching deliveries:", error);
                deliveries.value = [];
                totalItems.value = 0;
                totalPages.value = 1;
            } finally {
                isLoading.value = false;
            }
        };

        // Format date for display
        const formatDate = (dateString) => {
            if (!dateString) return "-";

            const date = new Date(dateString);
            return date.toLocaleDateString("id-ID", {
                day: "2-digit",
                month: "long",
                year: "numeric",
            });
        };

        // Get status label
        const getStatusLabel = (status) => {
            switch (status) {
                case "Pending":
                    return "Menunggu";
                case "In Transit":
                    return "Dalam Perjalanan";
                case "Delivered":
                    return "Terkirim";
                case "Returned":
                    return "Dikembalikan";
                case "Cancelled":
                    return "Dibatalkan";
                default:
                    return status;
            }
        };

        // Get status class for styling
        const getStatusClass = (status) => {
            switch (status) {
                case "Pending":
                    return "status-pending";
                case "In Transit":
                    return "status-transit";
                case "Delivered":
                    return "status-delivered";
                case "Returned":
                    return "status-returned";
                case "Cancelled":
                    return "status-cancelled";
                default:
                    return "";
            }
        };

        // Check if delivery can be edited
        const canEdit = (delivery) => {
            return (
                delivery.delivery_status !== "Delivered" &&
                delivery.delivery_status !== "Cancelled"
            );
        };

        // Check if delivery can be deleted
        const canDelete = (delivery) => {
            return delivery.delivery_status === "Pending";
        };

        // Apply filters and reset to first page
        const applyFilters = () => {
            currentPage.value = 1;
            fetchDeliveries();
        };

        // Clear search
        const clearSearch = () => {
            searchQuery.value = "";
            applyFilters();
        };

        // Handle sort
        const handleSort = ({ key, order }) => {
            sortKey.value = key;
            sortOrder.value = order;
            fetchDeliveries();
        };

        // Change page
        const changePage = (page) => {
            currentPage.value = page;
            fetchDeliveries();
        };

        // Navigation methods
        const viewDelivery = (delivery) => {
            router.push(`/sales/deliveries/${delivery.delivery_id}`);
        };

        const createDelivery = () => {
            router.push("/sales/deliveries/create");
        };

        const editDelivery = (delivery) => {
            router.push(`/sales/deliveries/${delivery.delivery_id}/edit`);
        };

        // Delete confirmation methods
        const confirmDelete = (delivery) => {
            deliveryToDelete.value = delivery;
            showDeleteModal.value = true;
        };

        const closeDeleteModal = () => {
            showDeleteModal.value = false;
        };

        const deleteDelivery = async () => {
            try {
                await DeliveryService.deleteDelivery(
                    deliveryToDelete.value.delivery_id
                );

                // Refresh list
                fetchDeliveries();
                closeDeleteModal();
            } catch (error) {
                console.error("Error deleting delivery:", error);
                alert("Gagal menghapus pengiriman. Silakan coba lagi.");
                closeDeleteModal();
            }
        };

        onMounted(() => {
            fetchDeliveries();
        });

        return {
            deliveries,
            columns,
            isLoading,
            searchQuery,
            filters,
            customDateRange,
            currentPage,
            perPage,
            totalItems,
            totalPages,
            showDeleteModal,
            deliveryToDelete,
            formatDate,
            getStatusLabel,
            getStatusClass,
            canEdit,
            canDelete,
            applyFilters,
            clearSearch,
            handleSort,
            changePage,
            viewDelivery,
            createDelivery,
            editDelivery,
            confirmDelete,
            closeDeleteModal,
            deleteDelivery,
        };
    },
};
</script>

<style scoped>
.delivery-list {
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

.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
}

.status-pending {
    background-color: var(--gray-200);
    color: var(--gray-700);
}

.status-transit {
    background-color: var(--primary-bg);
    color: var(--primary-color);
}

.status-delivered {
    background-color: var(--success-bg);
    color: var(--success-color);
}

.status-returned {
    background-color: var(--warning-bg);
    color: var(--warning-color);
}

.status-cancelled {
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

@media (max-width: 768px) {
    .date-range-inputs {
        flex-direction: column;
        gap: 0.5rem;
    }
}
</style>
