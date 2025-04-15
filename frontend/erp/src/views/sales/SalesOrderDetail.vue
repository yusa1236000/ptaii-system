<!-- src/views/sales/SalesOrderDetail.vue - Complete Template -->
<template>
    <div class="order-detail">
        <div class="page-header">
            <h1>Detail Order</h1>
            <div class="page-actions">
                <button class="btn btn-secondary" @click="goBack">
                    <i class="fas fa-arrow-left"></i> Kembali
                </button>
                <div class="btn-group" v-if="order">
                    <button
                        class="btn btn-primary"
                        @click="editOrder"
                        v-if="canEdit"
                    >
                        <i class="fas fa-edit"></i> Edit
                    </button>

                    <button
                        v-if="order.status === 'Draft'"
                        class="btn btn-info"
                        @click="confirmOrder"
                    >
                        <i class="fas fa-check-circle"></i> Konfirmasi
                    </button>

                    <button
                        v-if="
                            order.status === 'Confirmed' ||
                            order.status === 'Processing'
                        "
                        class="btn btn-info"
                        @click="createDelivery"
                    >
                        <i class="fas fa-truck"></i> Buat Pengiriman
                    </button>

                    <button
                        v-if="order.status === 'Delivered'"
                        class="btn btn-success"
                        @click="createInvoice"
                    >
                        <i class="fas fa-file-invoice-dollar"></i> Buat Faktur
                    </button>

                    <button class="btn btn-secondary" @click="printOrder">
                        <i class="fas fa-print"></i> Cetak
                    </button>
                </div>
            </div>
        </div>

        <div v-if="isLoading" class="loading-indicator">
            <i class="fas fa-spinner fa-spin"></i> Memuat data order...
        </div>

        <div v-else-if="!order" class="empty-state">
            <div class="empty-icon">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <h3>Order tidak ditemukan</h3>
            <p>Order yang Anda cari mungkin telah dihapus atau tidak ada.</p>
            <button class="btn btn-primary" @click="goBack">
                Kembali ke daftar order
            </button>
        </div>

        <div v-else class="order-container">
            <!-- Order Header -->
            <div class="detail-card">
                <div class="card-header">
                    <h2>Informasi Order</h2>
                    <div
                        class="order-status"
                        :class="getStatusClass(order.status)"
                    >
                        {{ getStatusLabel(order.status) }}
                    </div>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-group">
                            <label>Nomor Order</label>
                            <div class="info-value">{{ order.so_number }}</div>
                        </div>

                        <div class="info-group">
                            <label>Tanggal Order</label>
                            <div class="info-value">
                                {{ formatDate(order.so_date) }}
                            </div>
                        </div>

                        <div class="info-group">
                            <label>Pelanggan</label>
                            <div class="info-value">
                                {{ order.customer.name }}
                            </div>
                        </div>

                        <div class="info-group">
                            <label>Referensi Penawaran</label>
                            <div class="info-value">
                                <template v-if="order.quotation_id">
                                    <a
                                        @click.prevent="viewQuotation"
                                        href="#"
                                        class="link"
                                    >
                                        {{
                                            order.salesQuotation
                                                ?.quotation_number ||
                                            order.quotation_id
                                        }}
                                    </a>
                                </template>
                                <template v-else>-</template>
                            </div>
                        </div>

                        <div class="info-group">
                            <label>Perkiraan Pengiriman</label>
                            <div class="info-value">
                                {{ formatDate(order.expected_delivery) || "-" }}
                            </div>
                        </div>

                        <div class="info-group">
                            <label>Syarat Pembayaran</label>
                            <div class="info-value">
                                {{ order.payment_terms || "-" }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer Information -->
            <div class="detail-card">
                <div class="card-header">
                    <h2>Informasi Pelanggan</h2>
                </div>
                <div class="card-body">
                    <div class="customer-info">
                        <div class="info-group">
                            <label>Nama Pelanggan</label>
                            <div class="info-value">
                                {{ order.customer.name }}
                            </div>
                        </div>

                        <div class="info-group">
                            <label>Kode Pelanggan</label>
                            <div class="info-value">
                                {{ order.customer.customer_code }}
                            </div>
                        </div>

                        <div class="info-group">
                            <label>Alamat</label>
                            <div class="info-value">
                                {{ order.customer.address || "-" }}
                            </div>
                        </div>

                        <div class="info-group">
                            <label>NPWP</label>
                            <div class="info-value">
                                {{ order.customer.tax_id || "-" }}
                            </div>
                        </div>

                        <div class="info-group">
                            <label>Contact Person</label>
                            <div class="info-value">
                                {{ order.customer.contact_person || "-" }}
                            </div>
                        </div>

                        <div class="info-group">
                            <label>Telepon</label>
                            <div class="info-value">
                                {{ order.customer.phone || "-" }}
                            </div>
                        </div>

                        <div class="info-group">
                            <label>Email</label>
                            <div class="info-value">
                                {{ order.customer.email || "-" }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Items -->
            <div class="detail-card">
                <div class="card-header">
                    <h2>Item Order</h2>
                </div>
                <div class="card-body">
                    <div class="order-items">
                        <table class="items-table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Harga Unit</th>
                                    <th>Jumlah</th>
                                    <th>UOM</th>
                                    <th>Diskon</th>
                                    <th>Pajak</th>
                                    <th>Subtotal</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="line in order.salesOrderLines"
                                    :key="line.line_id"
                                >
                                    <td>
                                        <div class="item-info">
                                            <div class="item-code">
                                                {{ line.item.item_code }}
                                            </div>
                                            <div class="item-name">
                                                {{ line.item.name }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="right">
                                        {{ formatCurrency(line.unit_price) }}
                                    </td>
                                    <td class="right">{{ line.quantity }}</td>
                                    <td class="center">
                                        {{ getUomSymbol(line.uom_id) }}
                                    </td>
                                    <td class="right">
                                        {{
                                            line.discount
                                                ? formatCurrency(line.discount)
                                                : "-"
                                        }}
                                    </td>
                                    <td class="right">
                                        {{
                                            line.tax
                                                ? formatCurrency(line.tax)
                                                : "-"
                                        }}
                                    </td>
                                    <td class="right">
                                        {{ formatCurrency(line.subtotal) }}
                                    </td>
                                    <td class="right">
                                        {{ formatCurrency(line.total) }}
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="6" class="totals-label">
                                        Subtotal
                                    </td>
                                    <td colspan="2" class="totals-value">
                                        {{
                                            formatCurrency(calculateSubtotal())
                                        }}
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="6" class="totals-label">
                                        Total Diskon
                                    </td>
                                    <td colspan="2" class="totals-value">
                                        {{
                                            formatCurrency(
                                                calculateTotalDiscount()
                                            )
                                        }}
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="6" class="totals-label">
                                        Total Pajak
                                    </td>
                                    <td colspan="2" class="totals-value">
                                        {{
                                            formatCurrency(calculateTotalTax())
                                        }}
                                    </td>
                                </tr>
                                <tr class="grand-total">
                                    <td colspan="6" class="totals-label">
                                        Total
                                    </td>
                                    <td colspan="2" class="totals-value">
                                        {{ formatCurrency(order.total_amount) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Related Deliveries (if any) -->
            <div v-if="hasDeliveries" class="detail-card">
                <div class="card-header">
                    <h2>Pengiriman Terkait</h2>
                </div>
                <div class="card-body">
                    <div class="related-items">
                        <table class="related-table">
                            <thead>
                                <tr>
                                    <th>No. Pengiriman</th>
                                    <th>Tanggal</th>
                                    <th>Status</th>
                                    <th>Pengiriman</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="delivery in order.deliveries"
                                    :key="delivery.delivery_id"
                                >
                                    <td>{{ delivery.delivery_number }}</td>
                                    <td>
                                        {{ formatDate(delivery.delivery_date) }}
                                    </td>
                                    <td>
                                        <span
                                            class="status-badge"
                                            :class="
                                                'status-' +
                                                delivery.status.toLowerCase()
                                            "
                                        >
                                            {{ delivery.status }}
                                        </span>
                                    </td>
                                    <td>
                                        {{ delivery.shipping_method || "-" }}
                                    </td>
                                    <td>
                                        <button
                                            class="btn btn-sm btn-secondary"
                                            @click="viewDelivery(delivery)"
                                        >
                                            <i class="fas fa-eye"></i> Lihat
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Related Invoices (if any) -->
            <div v-if="hasInvoices" class="detail-card">
                <div class="card-header">
                    <h2>Faktur Terkait</h2>
                </div>
                <div class="card-body">
                    <div class="related-items">
                        <table class="related-table">
                            <thead>
                                <tr>
                                    <th>No. Faktur</th>
                                    <th>Tanggal</th>
                                    <th>Status</th>
                                    <th>Nilai</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="invoice in order.salesInvoices"
                                    :key="invoice.invoice_id"
                                >
                                    <td>{{ invoice.invoice_number }}</td>
                                    <td>
                                        {{ formatDate(invoice.invoice_date) }}
                                    </td>
                                    <td>
                                        <span
                                            class="status-badge"
                                            :class="
                                                'status-' +
                                                invoice.status.toLowerCase()
                                            "
                                        >
                                            {{ invoice.status }}
                                        </span>
                                    </td>
                                    <td>
                                        {{
                                            formatCurrency(invoice.total_amount)
                                        }}
                                    </td>
                                    <td>
                                        <button
                                            class="btn btn-sm btn-secondary"
                                            @click="viewInvoice(invoice)"
                                        >
                                            <i class="fas fa-eye"></i> Lihat
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Confirmation Modal -->
        <ConfirmationModal
            v-if="showConfirmModal"
            title="Konfirmasi Order"
            message="Apakah Anda yakin ingin mengkonfirmasi order ini? Status akan berubah menjadi 'Dikonfirmasi'."
            confirm-button-text="Konfirmasi"
            confirm-button-class="btn btn-primary"
            @confirm="confirmOrderAction"
            @close="closeConfirmModal"
        />
    </div>
</template>
<script>
import { ref, computed, onMounted } from "vue";
import { useRouter, useRoute } from "vue-router";
import axios from "axios";

export default {
    name: "SalesOrderDetail",
    setup() {
        const router = useRouter();
        const route = useRoute();

        // Data
        const order = ref(null);
        const unitOfMeasures = ref([]);
        const isLoading = ref(true);
        const showConfirmModal = ref(false);

        // Computed properties
        const canEdit = computed(() => {
            if (!order.value) return false;
            return !["Delivered", "Invoiced", "Closed"].includes(
                order.value.status
            );
        });

        const hasDeliveries = computed(() => {
            if (!order.value || !order.value.deliveries) return false;
            return order.value.deliveries.length > 0;
        });

        const hasInvoices = computed(() => {
            if (!order.value || !order.value.salesInvoices) return false;
            return order.value.salesInvoices.length > 0;
        });

        // Load order and reference data
        const loadData = async () => {
            isLoading.value = true;

            try {
                // Load unit of measures for reference
                const uomResponse = await axios.get("/unit-of-measures");
                unitOfMeasures.value = uomResponse.data.data;

                // Load order details
                const orderResponse = await axios.get(
                    `/orders/${route.params.id}`
                );
                order.value = orderResponse.data.data;
            } catch (error) {
                console.error("Error loading order:", error);
                order.value = null;
            } finally {
                isLoading.value = false;
            }
        };

        // Format date
        const formatDate = (dateString) => {
            if (!dateString) return "-";
            const date = new Date(dateString);
            return date.toLocaleDateString("id-ID", {
                day: "2-digit",
                month: "long",
                year: "numeric",
            });
        };

        // Format currency
        const formatCurrency = (value) => {
            return new Intl.NumberFormat("id-ID", {
                style: "currency",
                currency: "IDR",
            }).format(value || 0);
        };

        // Get UOM symbol
        const getUomSymbol = (uomId) => {
            const uom = unitOfMeasures.value.find((u) => u.uom_id === uomId);
            return uom ? uom.symbol : "-";
        };

        // Get status label
        const getStatusLabel = (status) => {
            switch (status) {
                case "Draft":
                    return "Draft";
                case "Confirmed":
                    return "Dikonfirmasi";
                case "Processing":
                    return "Diproses";
                case "Shipped":
                    return "Dikirim";
                case "Delivered":
                    return "Terkirim";
                case "Invoiced":
                    return "Difakturkan";
                case "Closed":
                    return "Selesai";
                default:
                    return status;
            }
        };

        // Get status class
        const getStatusClass = (status) => {
            switch (status) {
                case "Draft":
                    return "status-draft";
                case "Confirmed":
                    return "status-confirmed";
                case "Processing":
                    return "status-processing";
                case "Shipped":
                    return "status-shipped";
                case "Delivered":
                    return "status-delivered";
                case "Invoiced":
                    return "status-invoiced";
                case "Closed":
                    return "status-closed";
                default:
                    return "";
            }
        };

        // Calculate subtotal of all lines
        const calculateSubtotal = () => {
            if (!order.value || !order.value.salesOrderLines) return 0;
            return order.value.salesOrderLines.reduce(
                (sum, line) => sum + (line.subtotal || 0),
                0
            );
        };

        // Calculate total discount of all lines
        const calculateTotalDiscount = () => {
            if (!order.value || !order.value.salesOrderLines) return 0;
            return order.value.salesOrderLines.reduce(
                (sum, line) => sum + (line.discount || 0),
                0
            );
        };

        // Calculate total tax of all lines
        const calculateTotalTax = () => {
            if (!order.value || !order.value.salesOrderLines) return 0;
            return order.value.salesOrderLines.reduce(
                (sum, line) => sum + (line.tax || 0),
                0
            );
        };

        // Navigation methods
        const goBack = () => {
            router.push("/sales/orders");
        };

        const editOrder = () => {
            router.push(`/sales/orders/${order.value.so_id}/edit`);
        };

        const viewQuotation = () => {
            if (order.value && order.value.quotation_id) {
                router.push(`/sales/quotations/${order.value.quotation_id}`);
            }
        };

        const viewDelivery = (delivery) => {
            router.push(`/sales/deliveries/${delivery.delivery_id}`);
        };

        const viewInvoice = (invoice) => {
            router.push(`/sales/invoices/${invoice.invoice_id}`);
        };

        // Print order
        const printOrder = () => {
            router.push(`/sales/orders/${order.value.so_id}/print`);
        };

        // Confirm order
        const confirmOrder = () => {
            showConfirmModal.value = true;
        };

        const closeConfirmModal = () => {
            showConfirmModal.value = false;
        };

        const confirmOrderAction = async () => {
            try {
                await axios.put(`/sales/orders/${order.value.so_id}`, {
                    ...order.value,
                    status: "Confirmed",
                });

                // Reload the order to get the updated data
                loadData();

                closeConfirmModal();
                alert("Order berhasil dikonfirmasi");
            } catch (error) {
                console.error("Error confirming order:", error);
                alert("Terjadi kesalahan saat mengkonfirmasi order");
            }
        };

        // Create a delivery
        const createDelivery = () => {
            router.push(
                `/sales/deliveries/create?order_id=${order.value.so_id}`
            );
        };

        // Create an invoice
        const createInvoice = () => {
            router.push(`/sales/invoices/create?order_id=${order.value.so_id}`);
        };

        onMounted(() => {
            loadData();
        });

        return {
            order,
            isLoading,
            canEdit,
            hasDeliveries,
            hasInvoices,
            showConfirmModal,
            formatDate,
            formatCurrency,
            getUomSymbol,
            getStatusLabel,
            getStatusClass,
            calculateSubtotal,
            calculateTotalDiscount,
            calculateTotalTax,
            goBack,
            editOrder,
            viewQuotation,
            viewDelivery,
            viewInvoice,
            printOrder,
            confirmOrder,
            closeConfirmModal,
            confirmOrderAction,
            createDelivery,
            createInvoice,
        };
    },
};
</script>
<style scoped>
.order-detail {
    padding: 1rem 0;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.page-header h1 {
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0;
    color: #1e293b;
}

.page-actions {
    display: flex;
    gap: 0.75rem;
}

.btn-group {
    display: flex;
    gap: 0.5rem;
}

.order-container {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.detail-card {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.card-header {
    background-color: #f8fafc;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-header h2 {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0;
    color: #1e293b;
}

.card-body {
    padding: 1.5rem;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
}

.customer-info {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
}

.info-group {
    margin-bottom: 0.75rem;
}

.info-group label {
    display: block;
    font-size: 0.75rem;
    color: #64748b;
    margin-bottom: 0.25rem;
}

.info-value {
    font-size: 0.875rem;
    color: #1e293b;
    font-weight: 500;
}

.order-status {
    font-size: 0.75rem;
    font-weight: 500;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
}

.status-draft {
    background-color: #e2e8f0;
    color: #475569;
}

.status-confirmed {
    background-color: #dbeafe;
    color: #2563eb;
}

.status-processing {
    background-color: #ede9fe;
    color: #7c3aed;
}

.status-shipped {
    background-color: #fef3c7;
    color: #d97706;
}

.status-delivered {
    background-color: #d1fae5;
    color: #059669;
}

.status-invoiced {
    background-color: #e0f2fe;
    color: #0284c7;
}

.status-closed {
    background-color: #cbd5e1;
    color: #1e293b;
}

.items-table,
.related-table {
    width: 100%;
    border-collapse: collapse;
}

.items-table th,
.related-table th {
    text-align: left;
    padding: 0.75rem;
    background-color: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    font-weight: 500;
    color: #64748b;
    font-size: 0.75rem;
}

.items-table td,
.related-table td {
    padding: 0.75rem;
    border-bottom: 1px solid #f1f5f9;
    font-size: 0.875rem;
}

.center {
    text-align: center;
}

.right {
    text-align: right;
}

.item-info {
    display: flex;
    flex-direction: column;
}

.item-code {
    font-size: 0.75rem;
    color: #64748b;
}

.item-name {
    font-weight: 500;
}

.totals-label {
    text-align: right;
    font-weight: 500;
    color: #475569;
}

.totals-value {
    text-align: right;
    font-weight: 500;
}

.grand-total td {
    font-weight: 600;
    color: #1e293b;
    font-size: 1rem;
}

.status-badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
}

.btn {
    padding: 0.625rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    border-radius: 0.375rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    border: none;
    transition: background-color 0.2s, color 0.2s;
}

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.75rem;
}

.btn-primary {
    background-color: #2563eb;
    color: white;
}

.btn-primary:hover {
    background-color: #1d4ed8;
}

.btn-secondary {
    background-color: #e2e8f0;
    color: #1e293b;
}

.btn-secondary:hover {
    background-color: #cbd5e1;
}

.btn-info {
    background-color: #0ea5e9;
    color: white;
}

.btn-info:hover {
    background-color: #0284c7;
}

.btn-success {
    background-color: #059669;
    color: white;
}

.btn-success:hover {
    background-color: #047857;
}

.link {
    color: #2563eb;
    cursor: pointer;
}

.link:hover {
    text-decoration: underline;
}

.loading-indicator {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 4rem 0;
    color: #64748b;
    font-size: 1rem;
}

.loading-indicator i {
    margin-right: 0.5rem;
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 0;
    text-align: center;
    color: #64748b;
}

.empty-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
    color: #cbd5e1;
}

.empty-state h3 {
    font-size: 1.25rem;
    margin: 0 0 0.5rem 0;
    color: #1e293b;
}

.empty-state p {
    margin: 0 0 1.5rem 0;
    font-size: 0.875rem;
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }

    .page-actions {
        width: 100%;
        justify-content: space-between;
    }

    .btn-group {
        flex-wrap: wrap;
    }

    .info-grid,
    .customer-info {
        grid-template-columns: 1fr;
        gap: 0.75rem;
    }

    .items-table,
    .related-table {
        display: block;
        overflow-x: auto;
    }
}
</style>
