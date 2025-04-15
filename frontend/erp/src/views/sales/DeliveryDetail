<!-- src/views/sales/DeliveryDetail.vue -->
<template>
    <div class="delivery-detail">
        <div class="page-header">
            <div class="header-left">
                <button class="btn btn-secondary btn-sm" @click="goBack">
                    <i class="fas fa-arrow-left"></i> Kembali
                </button>
                <h1 v-if="delivery">
                    Detail Pengiriman: {{ delivery.delivery_number }}
                </h1>
            </div>
            <div class="header-actions" v-if="delivery">
                <button
                    v-if="canEdit"
                    class="btn btn-primary"
                    @click="editDelivery"
                >
                    <i class="fas fa-edit"></i> Edit
                </button>
                <button class="btn btn-success" @click="printDelivery">
                    <i class="fas fa-print"></i> Cetak
                </button>
            </div>
        </div>

        <div v-if="isLoading" class="loading-container">
            <i class="fas fa-spinner fa-spin"></i> Memuat data pengiriman...
        </div>

        <div v-else-if="!delivery" class="empty-state">
            <div class="empty-icon">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <h3>Pengiriman tidak ditemukan</h3>
            <p>
                Pengiriman yang Anda cari mungkin telah dihapus atau tidak ada.
            </p>
            <button class="btn btn-primary" @click="goBack">
                Kembali ke daftar pengiriman
            </button>
        </div>

        <div v-else class="delivery-container">
            <!-- Delivery Header Information -->
            <div class="card delivery-info">
                <div class="card-header">
                    <h3>Informasi Pengiriman</h3>
                    <div
                        class="status-badge-lg"
                        :class="getStatusClass(delivery.delivery_status)"
                    >
                        {{ getStatusLabel(delivery.delivery_status) }}
                    </div>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">No. Pengiriman</div>
                            <div class="info-value">
                                {{ delivery.delivery_number }}
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">No. Sales Order</div>
                            <div class="info-value">
                                <a
                                    @click.prevent="
                                        viewOrder(delivery.order.so_id)
                                    "
                                    href="#"
                                    class="link"
                                >
                                    {{ delivery.order.so_number }}
                                </a>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Tanggal Pengiriman</div>
                            <div class="info-value">
                                {{ formatDate(delivery.delivery_date) }}
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Estimasi Kedatangan</div>
                            <div class="info-value">
                                {{ formatDate(delivery.expected_arrival_date) }}
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Kurir</div>
                            <div class="info-value">
                                {{ delivery.carrier || "-" }}
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">No. Resi</div>
                            <div class="info-value">
                                {{ delivery.tracking_number || "-" }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer & Shipping Information -->
            <div class="card customer-info">
                <div class="card-header">
                    <h3>Informasi Pelanggan & Pengiriman</h3>
                </div>
                <div class="card-body">
                    <div class="info-columns">
                        <div class="info-column">
                            <h4 class="info-title">Pelanggan</h4>
                            <div class="info-block">
                                <div class="customer-name">
                                    {{ delivery.order.customer.name }}
                                </div>
                                <div
                                    v-if="
                                        delivery.order.customer.contact_person
                                    "
                                >
                                    Kontak:
                                    {{ delivery.order.customer.contact_person }}
                                </div>
                                <div v-if="delivery.order.customer.phone">
                                    Telepon: {{ delivery.order.customer.phone }}
                                </div>
                                <div v-if="delivery.order.customer.email">
                                    Email: {{ delivery.order.customer.email }}
                                </div>
                            </div>
                        </div>

                        <div class="info-column">
                            <h4 class="info-title">Alamat Pengiriman</h4>
                            <div class="info-block">
                                <div v-if="delivery.shipping_address">
                                    {{ delivery.shipping_address }}
                                </div>
                                <div
                                    v-else-if="delivery.order.customer.address"
                                >
                                    {{ delivery.order.customer.address }}
                                </div>
                                <div v-else>
                                    <span class="text-muted"
                                        >Alamat tidak tersedia</span
                                    >
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Delivery Items -->
            <div class="card delivery-items">
                <div class="card-header">
                    <h3>Item Pengiriman</h3>
                </div>
                <div class="card-body">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Deskripsi</th>
                                <th>Jumlah</th>
                                <th>Satuan</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(item, index) in delivery.deliveryItems"
                                :key="item.delivery_item_id || index"
                            >
                                <td>
                                    <div class="item-name">
                                        {{ item.item.name }}
                                    </div>
                                    <div class="item-code">
                                        {{ item.item.item_code }}
                                    </div>
                                </td>
                                <td>{{ item.item.description || "-" }}</td>
                                <td>{{ item.quantity }}</td>
                                <td>{{ item.unitOfMeasure?.symbol || "-" }}</td>
                                <td>
                                    <span
                                        class="status-badge"
                                        :class="
                                            getStatusClass(
                                                item.status || 'Pending'
                                            )
                                        "
                                    >
                                        {{
                                            getStatusLabel(
                                                item.status || "Pending"
                                            )
                                        }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Status History -->
            <div
                v-if="
                    delivery.statusHistory && delivery.statusHistory.length > 0
                "
                class="card status-history"
            >
                <div class="card-header">
                    <h3>Riwayat Status</h3>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <div
                            v-for="(history, index) in delivery.statusHistory"
                            :key="index"
                            class="timeline-item"
                        >
                            <div
                                class="timeline-icon"
                                :class="getStatusIconClass(history.status)"
                            >
                                <i :class="getStatusIcon(history.status)"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-time">
                                    {{ formatDateTime(history.created_at) }}
                                </div>
                                <div class="timeline-title">
                                    {{ getStatusLabel(history.status) }}
                                </div>
                                <div
                                    v-if="history.notes"
                                    class="timeline-notes"
                                >
                                    {{ history.notes }}
                                </div>
                                <div class="timeline-user">
                                    Oleh: {{ history.user?.name || "System" }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Update Status Section -->
            <div v-if="canUpdateStatus" class="card update-status">
                <div class="card-header">
                    <h3>Perbarui Status</h3>
                </div>
                <div class="card-body">
                    <div class="status-update-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="newStatus">Status Baru</label>
                                <select
                                    id="newStatus"
                                    v-model="newStatus"
                                    class="form-control"
                                >
                                    <option value="">-- Pilih Status --</option>
                                    <option
                                        v-for="status in availableStatuses"
                                        :key="status.value"
                                        :value="status.value"
                                    >
                                        {{ status.label }}
                                    </option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="statusNotes"
                                    >Catatan (Opsional)</label
                                >
                                <textarea
                                    id="statusNotes"
                                    v-model="statusNotes"
                                    class="form-control"
                                    rows="2"
                                ></textarea>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button
                                class="btn btn-primary"
                                @click="updateDeliveryStatus"
                                :disabled="!newStatus || isUpdatingStatus"
                            >
                                <i class="fas fa-save"></i>
                                {{
                                    isUpdatingStatus
                                        ? "Memperbarui..."
                                        : "Perbarui Status"
                                }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes Section -->
            <div v-if="delivery.notes" class="card delivery-notes">
                <div class="card-header">
                    <h3>Catatan Pengiriman</h3>
                </div>
                <div class="card-body">
                    <p>{{ delivery.notes }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, computed, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import DeliveryService from "@/services/DeliveryService";

export default {
    name: "DeliveryDetail",
    setup() {
        const route = useRoute();
        const router = useRouter();
        const deliveryId = Number(route.params.id);

        const delivery = ref(null);
        const isLoading = ref(true);
        const newStatus = ref("");
        const statusNotes = ref("");
        const isUpdatingStatus = ref(false);

        // Fetch delivery details
        const fetchDelivery = async () => {
            isLoading.value = true;
            try {
                const response = await DeliveryService.getDeliveryById(
                    deliveryId
                );
                delivery.value = response.data;
            } catch (error) {
                console.error("Error fetching delivery:", error);
                delivery.value = null;
            } finally {
                isLoading.value = false;
            }
        };

        // Computed properties
        const canEdit = computed(() => {
            if (!delivery.value) return false;
            return (
                delivery.value.delivery_status !== "Delivered" &&
                delivery.value.delivery_status !== "Cancelled"
            );
        });

        const canUpdateStatus = computed(() => {
            if (!delivery.value) return false;
            return (
                delivery.value.delivery_status !== "Delivered" &&
                delivery.value.delivery_status !== "Cancelled"
            );
        });

        const availableStatuses = computed(() => {
            if (!delivery.value) return [];

            const currentStatus = delivery.value.delivery_status;

            // Define possible status transitions
            const transitions = {
                Pending: [
                    { value: "In Transit", label: "Dalam Perjalanan" },
                    { value: "Cancelled", label: "Dibatalkan" },
                ],
                "In Transit": [
                    { value: "Delivered", label: "Terkirim" },
                    { value: "Returned", label: "Dikembalikan" },
                ],
                Returned: [
                    {
                        value: "In Transit",
                        label: "Dalam Perjalanan (Pengiriman Ulang)",
                    },
                ],
            };

            return transitions[currentStatus] || [];
        });

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

        const formatDateTime = (dateTimeString) => {
            if (!dateTimeString) return "-";

            const date = new Date(dateTimeString);
            return date.toLocaleDateString("id-ID", {
                day: "2-digit",
                month: "long",
                year: "numeric",
                hour: "2-digit",
                minute: "2-digit",
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

        // Get status icon
        const getStatusIcon = (status) => {
            switch (status) {
                case "Pending":
                    return "fas fa-clock";
                case "In Transit":
                    return "fas fa-truck";
                case "Delivered":
                    return "fas fa-check-circle";
                case "Returned":
                    return "fas fa-undo";
                case "Cancelled":
                    return "fas fa-times-circle";
                default:
                    return "fas fa-circle";
            }
        };

        // Get status icon class
        const getStatusIconClass = (status) => {
            switch (status) {
                case "Pending":
                    return "icon-pending";
                case "In Transit":
                    return "icon-transit";
                case "Delivered":
                    return "icon-delivered";
                case "Returned":
                    return "icon-returned";
                case "Cancelled":
                    return "icon-cancelled";
                default:
                    return "";
            }
        };

        // Navigation methods
        const goBack = () => {
            router.push("/sales/deliveries");
        };

        const viewOrder = (orderId) => {
            router.push(`/sales/orders/${orderId}`);
        };

        const editDelivery = () => {
            router.push(`/sales/deliveries/${deliveryId}/edit`);
        };

        const printDelivery = () => {
            router.push(`/sales/deliveries/${deliveryId}/print`);
        };

        // Update delivery status
        const updateDeliveryStatus = async () => {
            if (!newStatus.value) return;

            isUpdatingStatus.value = true;
            try {
                await DeliveryService.updateDeliveryStatus(deliveryId, {
                    status: newStatus.value,
                    notes: statusNotes.value,
                });

                // Refresh delivery data
                await fetchDelivery();

                // Reset form
                newStatus.value = "";
                statusNotes.value = "";

                alert("Status pengiriman berhasil diperbarui!");
            } catch (error) {
                console.error("Error updating delivery status:", error);
                alert(
                    "Gagal memperbarui status pengiriman. Silakan coba lagi."
                );
            } finally {
                isUpdatingStatus.value = false;
            }
        };

        onMounted(() => {
            fetchDelivery();
        });

        return {
            delivery,
            isLoading,
            newStatus,
            statusNotes,
            isUpdatingStatus,
            canEdit,
            canUpdateStatus,
            availableStatuses,
            formatDate,
            formatDateTime,
            getStatusLabel,
            getStatusClass,
            getStatusIcon,
            getStatusIconClass,
            goBack,
            viewOrder,
            editDelivery,
            printDelivery,
            updateDeliveryStatus,
        };
    },
};
</script>

<style scoped>
.delivery-detail {
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

.header-left {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.page-header h1 {
    margin: 0;
    font-size: 1.5rem;
    color: var(--gray-800);
}

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
}

.loading-container,
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 2rem;
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    text-align: center;
}

.loading-container i {
    font-size: 2rem;
    color: var(--primary-color);
    margin-bottom: 1rem;
}

.empty-icon {
    font-size: 3rem;
    color: var(--warning-color);
    margin-bottom: 1rem;
}

.empty-state h3 {
    margin-bottom: 1rem;
    color: var(--gray-800);
}

.empty-state p {
    margin-bottom: 1.5rem;
    color: var(--gray-600);
}

.delivery-container {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.card {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    background-color: var(--gray-50);
    border-bottom: 1px solid var(--gray-200);
}

.card-header h3 {
    margin: 0;
    font-size: 1.125rem;
    color: var(--gray-800);
}

.card-body {
    padding: 1.5rem;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1.5rem;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.info-label {
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--gray-500);
    text-transform: uppercase;
}

.info-value {
    font-size: 0.875rem;
    color: var(--gray-800);
}

.info-columns {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
}

.info-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--gray-700);
    margin: 0 0 0.75rem 0;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid var(--gray-200);
}

.info-block {
    font-size: 0.875rem;
    color: var(--gray-800);
    line-height: 1.5;
}

.customer-name {
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.link {
    color: var(--primary-color);
    cursor: pointer;
    text-decoration: none;
}

.link:hover {
    text-decoration: underline;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
}

.status-badge-lg {
    display: inline-flex;
    align-items: center;
    padding: 0.375rem 0.75rem;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    font-weight: 600;
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

.data-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
}

.data-table th {
    text-align: left;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--gray-200);
    font-weight: 600;
    color: var(--gray-700);
    background-color: var(--gray-50);
}

.data-table td {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--gray-100);
    color: var(--gray-800);
    vertical-align: middle;
}

.data-table tr:last-child td {
    border-bottom: none;
}

.item-name {
    font-weight: 500;
    color: var(--gray-800);
}

.item-code {
    font-size: 0.75rem;
    color: var(--gray-500);
    margin-top: 0.25rem;
}

.timeline {
    position: relative;
    padding-left: 2rem;
}

.timeline:before {
    content: "";
    position: absolute;
    top: 0;
    bottom: 0;
    left: 11px;
    width: 2px;
    background-color: var(--gray-200);
}

.timeline-item {
    position: relative;
    margin-bottom: 1.5rem;
}

.timeline-item:last-child {
    margin-bottom: 0;
}

.timeline-icon {
    position: absolute;
    left: -2rem;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background-color: var(--gray-300);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    color: white;
    z-index: 1;
}

.icon-pending {
    background-color: var(--gray-400);
}

.icon-transit {
    background-color: var(--primary-color);
}

.icon-delivered {
    background-color: var(--success-color);
}

.icon-returned {
    background-color: var(--warning-color);
}

.icon-cancelled {
    background-color: var(--danger-color);
}

.timeline-content {
    background-color: var(--gray-50);
    border-radius: 0.5rem;
    padding: 0.75rem 1rem;
}

.timeline-time {
    font-size: 0.75rem;
    color: var(--gray-500);
    margin-bottom: 0.25rem;
}

.timeline-title {
    font-weight: 600;
    color: var(--gray-800);
    margin-bottom: 0.25rem;
}

.timeline-notes {
    font-size: 0.875rem;
    color: var(--gray-700);
    margin-bottom: 0.5rem;
}

.timeline-user {
    font-size: 0.75rem;
    color: var(--gray-500);
    font-style: italic;
}

.status-update-form {
    background-color: var(--gray-50);
    border-radius: 0.5rem;
    padding: 1rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 1rem;
    margin-bottom: 1rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-group label {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--gray-700);
}

.form-control {
    padding: 0.625rem;
    border: 1px solid var(--gray-300);
    border-radius: 0.375rem;
    font-size: 0.875rem;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
}

.text-muted {
    color: var(--gray-500);
    font-style: italic;
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }

    .header-actions {
        align-self: flex-end;
    }

    .info-grid {
        grid-template-columns: 1fr;
    }

    .info-columns {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }

    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>
