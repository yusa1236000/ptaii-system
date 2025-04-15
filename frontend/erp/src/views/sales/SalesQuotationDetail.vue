<!-- src/views/sales/SalesQuotationDetail.vue -->
<template>
    <div class="quotation-detail">
        <div class="page-header">
            <h1>Detail Penawaran</h1>
            <div class="page-actions">
                <button class="btn btn-secondary" @click="goBack">
                    <i class="fas fa-arrow-left"></i> Kembali
                </button>
                <div class="btn-group" v-if="quotation">
                    <button
                        class="btn btn-primary"
                        @click="editQuotation"
                        v-if="canEdit"
                    >
                        <i class="fas fa-edit"></i> Edit
                    </button>

                    <button
                        v-if="quotation.status === 'Draft'"
                        class="btn btn-info"
                        @click="markAsSent"
                    >
                        <i class="fas fa-paper-plane"></i> Kirim
                    </button>

                    <button
                        v-if="quotation.status === 'Sent'"
                        class="btn btn-success"
                        @click="markAsAccepted"
                    >
                        <i class="fas fa-check"></i> Terima
                    </button>

                    <button
                        v-if="quotation.status === 'Sent'"
                        class="btn btn-danger"
                        @click="markAsRejected"
                    >
                        <i class="fas fa-times"></i> Tolak
                    </button>

                    <button
                        v-if="canConvert"
                        class="btn btn-secondary"
                        @click="convertToOrder"
                    >
                        <i class="fas fa-file-invoice-dollar"></i> Konversi ke
                        SO
                    </button>

                    <button class="btn btn-success" @click="printQuotation">
                        <i class="fas fa-print"></i> Cetak
                    </button>
                </div>
            </div>
        </div>

        <div v-if="isLoading" class="loading-indicator">
            <i class="fas fa-spinner fa-spin"></i> Memuat data penawaran...
        </div>

        <div v-else-if="!quotation" class="empty-state">
            <div class="empty-icon">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <h3>Penawaran tidak ditemukan</h3>
            <p>
                Penawaran yang Anda cari mungkin telah dihapus atau tidak ada.
            </p>
            <button class="btn btn-primary" @click="goBack">
                Kembali ke daftar penawaran
            </button>
        </div>

        <div v-else class="quotation-container">
            <!-- Quotation Header -->
            <div class="detail-card">
                <div class="card-header">
                    <h2>Informasi Penawaran</h2>
                    <div
                        class="quotation-status"
                        :class="getStatusClass(quotation.status)"
                    >
                        {{ getStatusLabel(quotation.status) }}
                    </div>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-group">
                            <label>Nomor Penawaran</label>
                            <div class="info-value">
                                {{ quotation.quotation_number }}
                            </div>
                        </div>

                        <div class="info-group">
                            <label>Tanggal Penawaran</label>
                            <div class="info-value">
                                {{ formatDate(quotation.quotation_date) }}
                            </div>
                        </div>

                        <div class="info-group">
                            <label>Pelanggan</label>
                            <div class="info-value">
                                {{ quotation.customer.name }}
                            </div>
                        </div>

                        <div class="info-group">
                            <label>Berlaku Hingga</label>
                            <div class="info-value">
                                {{ formatDate(quotation.validity_date) }}
                            </div>
                        </div>

                        <div class="info-group">
                            <label>Syarat Pembayaran</label>
                            <div class="info-value">
                                {{ quotation.payment_terms || "-" }}
                            </div>
                        </div>

                        <div class="info-group">
                            <label>Syarat Pengiriman</label>
                            <div class="info-value">
                                {{ quotation.delivery_terms || "-" }}
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
                                {{ quotation.customer.name }}
                            </div>
                        </div>

                        <div class="info-group">
                            <label>Kode Pelanggan</label>
                            <div class="info-value">
                                {{ quotation.customer.customer_code }}
                            </div>
                        </div>

                        <div class="info-group">
                            <label>Alamat</label>
                            <div class="info-value">
                                {{ quotation.customer.address || "-" }}
                            </div>
                        </div>

                        <div class="info-group">
                            <label>NPWP</label>
                            <div class="info-value">
                                {{ quotation.customer.tax_id || "-" }}
                            </div>
                        </div>

                        <div class="info-group">
                            <label>Contact Person</label>
                            <div class="info-value">
                                {{ quotation.customer.contact_person || "-" }}
                            </div>
                        </div>

                        <div class="info-group">
                            <label>Telepon</label>
                            <div class="info-value">
                                {{ quotation.customer.phone || "-" }}
                            </div>
                        </div>

                        <div class="info-group">
                            <label>Email</label>
                            <div class="info-value">
                                {{ quotation.customer.email || "-" }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quotation Items -->
            <div class="detail-card">
                <div class="card-header">
                    <h2>Item Penawaran</h2>
                </div>
                <div class="card-body">
                    <div class="quotation-items">
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
                                    v-for="line in quotation.salesQuotationLine"
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
                                    <td>
                                        {{ formatCurrency(line.unit_price) }}
                                    </td>
                                    <td>{{ line.quantity }}</td>
                                    <td>{{ getUomSymbol(line.uom_id) }}</td>
                                    <td>
                                        {{ formatCurrency(line.discount || 0) }}
                                    </td>
                                    <td>{{ formatCurrency(line.tax || 0) }}</td>
                                    <td>{{ formatCurrency(line.subtotal) }}</td>
                                    <td>{{ formatCurrency(line.total) }}</td>
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
                                        {{
                                            formatCurrency(
                                                calculateGrandTotal()
                                            )
                                        }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Related Sales Orders (if any) -->
            <div v-if="hasRelatedSalesOrders" class="detail-card">
                <div class="card-header">
                    <h2>Sales Order Terkait</h2>
                </div>
                <div class="card-body">
                    <div class="related-orders">
                        <table class="related-table">
                            <thead>
                                <tr>
                                    <th>No. SO</th>
                                    <th>Tanggal</th>
                                    <th>Status</th>
                                    <th>Nilai</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="order in quotation.salesOrders"
                                    :key="order.so_id"
                                >
                                    <td>{{ order.so_number }}</td>
                                    <td>{{ formatDate(order.so_date) }}</td>
                                    <td>
                                        <span
                                            class="status-badge"
                                            :class="
                                                'status-' +
                                                order.status.toLowerCase()
                                            "
                                        >
                                            {{ order.status }}
                                        </span>
                                    </td>
                                    <td>
                                        {{ formatCurrency(order.total_amount) }}
                                    </td>
                                    <td>
                                        <button
                                            class="btn btn-sm btn-secondary"
                                            @click="viewSalesOrder(order)"
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
    </div>
</template>

<script>
import { ref, computed, onMounted } from "vue";
import { useRouter, useRoute } from "vue-router";
import axios from "axios";

export default {
    name: "SalesQuotationDetail",
    setup() {
        const router = useRouter();
        const route = useRoute();

        // Data
        const quotation = ref(null);
        const unitOfMeasures = ref([]);
        const isLoading = ref(true);

        // Computed properties
        const canEdit = computed(() => {
            if (!quotation.value) return false;
            return quotation.value.status !== "Converted";
        });

        const canConvert = computed(() => {
            if (!quotation.value) return false;
            return quotation.value.status === "Accepted";
        });

        const hasRelatedSalesOrders = computed(() => {
            if (!quotation.value || !quotation.value.salesOrders) return false;
            return quotation.value.salesOrders.length > 0;
        });

        // Load quotation and reference data
        const loadData = async () => {
            isLoading.value = true;

            try {
                // Load unit of measures for reference
                const uomResponse = await axios.get("/unit-of-measures");
                unitOfMeasures.value = uomResponse.data.data;

                // Load quotation details
                const quotationResponse = await axios.get(
                    `/quotations/${route.params.id}`
                );
                quotation.value = quotationResponse.data.data;
            } catch (error) {
                console.error("Error loading quotation:", error);
                quotation.value = null;
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
                case "Sent":
                    return "Terkirim";
                case "Accepted":
                    return "Diterima";
                case "Rejected":
                    return "Ditolak";
                case "Expired":
                    return "Kadaluarsa";
                case "Converted":
                    return "Dikonversi ke SO";
                default:
                    return status;
            }
        };

        // Get status class
        const getStatusClass = (status) => {
            switch (status) {
                case "Draft":
                    return "status-draft";
                case "Sent":
                    return "status-sent";
                case "Accepted":
                    return "status-accepted";
                case "Rejected":
                    return "status-rejected";
                case "Expired":
                    return "status-expired";
                case "Converted":
                    return "status-converted";
                default:
                    return "";
            }
        };

        // Calculate subtotal of all lines
        const calculateSubtotal = () => {
            if (!quotation.value || !quotation.value.salesQuotationLine)
                return 0;
            return quotation.value.salesQuotationLine.reduce(
                (sum, line) => sum + (line.subtotal || 0),
                0
            );
        };

        // Calculate total discount of all lines
        const calculateTotalDiscount = () => {
            if (!quotation.value || !quotation.value.salesQuotationLine)
                return 0;
            return quotation.value.salesQuotationLine.reduce(
                (sum, line) => sum + (line.discount || 0),
                0
            );
        };

        // Calculate total tax of all lines
        const calculateTotalTax = () => {
            if (!quotation.value || !quotation.value.salesQuotationLine)
                return 0;
            return quotation.value.salesQuotationLine.reduce(
                (sum, line) => sum + (line.tax || 0),
                0
            );
        };

        // Calculate grand total of all lines
        const calculateGrandTotal = () => {
            if (!quotation.value || !quotation.value.salesQuotationLine)
                return 0;
            return quotation.value.salesQuotationLine.reduce(
                (sum, line) => sum + (line.total || 0),
                0
            );
        };

        // Navigation methods
        const goBack = () => {
            router.push("/quotations");
        };

        const editQuotation = () => {
            router.push(`/quotations/${quotation.value.quotation_id}/edit`);
        };

        const convertToOrder = () => {
            router.push(
                `/orders/create-from-quotation/${quotation.value.quotation_id}`
            );
        };

        const viewSalesOrder = (order) => {
            router.push(`/orders/${order.so_id}`);
        };

        // Status update methods
        const updateStatus = async (newStatus) => {
            try {
                await axios.put(`/quotations/${quotation.value.quotation_id}`, {
                    ...quotation.value,
                    status: newStatus,
                });

                // Reload the quotation to get the updated data
                loadData();

                alert(
                    `Status penawaran berhasil diubah menjadi ${getStatusLabel(
                        newStatus
                    )}`
                );
            } catch (error) {
                console.error("Error updating quotation status:", error);
                alert("Terjadi kesalahan saat mengubah status penawaran");
            }
        };

        const markAsSent = () => updateStatus("Sent");
        const markAsAccepted = () => updateStatus("Accepted");
        const markAsRejected = () => updateStatus("Rejected");

        // Print method
        const printQuotation = () => {
            window.print();
        };

        onMounted(() => {
            loadData();
        });

        return {
            quotation,
            isLoading,
            canEdit,
            canConvert,
            hasRelatedSalesOrders,
            formatDate,
            formatCurrency,
            getUomSymbol,
            getStatusLabel,
            getStatusClass,
            calculateSubtotal,
            calculateTotalDiscount,
            calculateTotalTax,
            calculateGrandTotal,
            goBack,
            editQuotation,
            convertToOrder,
            viewSalesOrder,
            markAsSent,
            markAsAccepted,
            markAsRejected,
            printQuotation,
        };
    },
};
</script>

<style scoped>
.quotation-detail {
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

.quotation-container {
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

.quotation-status {
    font-size: 0.75rem;
    font-weight: 500;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
}

.status-draft {
    background-color: #e2e8f0;
    color: #475569;
}

.status-sent {
    background-color: #dbeafe;
    color: #2563eb;
}

.status-accepted {
    background-color: #d1fae5;
    color: #059669;
}

.status-rejected {
    background-color: #fee2e2;
    color: #dc2626;
}

.status-expired {
    background-color: #fef3c7;
    color: #d97706;
}

.status-converted {
    background-color: #ede9fe;
    color: #7c3aed;
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

.btn-danger {
    background-color: #dc2626;
    color: white;
}

.btn-danger:hover {
    background-color: #b91c1c;
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

    /* Print styles */
    @media print {
        .page-actions,
        .btn,
        .btn-group {
            display: none !important;
        }

        .page-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .detail-card {
            page-break-inside: avoid;
            margin-bottom: 2rem;
            box-shadow: none;
            border: 1px solid #e2e8f0;
        }

        .items-table th,
        .items-table td,
        .related-table th,
        .related-table td {
            padding: 0.5rem;
        }
    }
}
</style>
