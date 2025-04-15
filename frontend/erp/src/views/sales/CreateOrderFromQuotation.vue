<!-- src/views/sales/CreateOrderFromQuotation.vue -->
<template>
    <div class="order-from-quotation">
        <div class="page-header">
            <h1>Buat Sales Order dari Penawaran</h1>
            <div class="page-actions">
                <button class="btn btn-secondary" @click="goBack">
                    <i class="fas fa-arrow-left"></i> Kembali
                </button>
                <button
                    class="btn btn-primary"
                    @click="createOrder"
                    :disabled="isSubmitting"
                >
                    <i class="fas fa-check"></i>
                    {{ isSubmitting ? "Memproses..." : "Buat Order" }}
                </button>
            </div>
        </div>

        <div v-if="error" class="alert alert-danger">{{ error }}</div>

        <div class="form-container">
            <div v-if="isLoading" class="loading-indicator">
                <i class="fas fa-spinner fa-spin"></i> Memuat data penawaran...
            </div>

            <div v-else-if="!quotation" class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <h3>Penawaran tidak ditemukan</h3>
                <p>
                    Penawaran yang Anda cari mungkin telah dihapus atau tidak
                    ada.
                </p>
                <button class="btn btn-primary" @click="goBack">
                    Kembali ke daftar penawaran
                </button>
            </div>

            <template v-else>
                <!-- Quotation Information -->
                <div class="info-panel">
                    <div class="panel-header">
                        <h2>Informasi Penawaran</h2>
                        <div
                            class="quotation-status"
                            :class="getStatusClass(quotation.status)"
                        >
                            {{ getStatusLabel(quotation.status) }}
                        </div>
                    </div>
                    <div class="panel-body">
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
                                <label>Total Penawaran</label>
                                <div class="info-value">
                                    {{ formatCurrency(calculateGrandTotal()) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sales Order Form -->
                <div class="form-card">
                    <div class="card-header">
                        <h2>Informasi Sales Order</h2>
                    </div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="so_number">Nomor SO*</label>
                                <input
                                    type="text"
                                    id="so_number"
                                    v-model="form.so_number"
                                    required
                                />
                            </div>

                            <div class="form-group">
                                <label for="so_date">Tanggal SO*</label>
                                <input
                                    type="date"
                                    id="so_date"
                                    v-model="form.so_date"
                                    required
                                />
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="expected_delivery"
                                    >Rencana Pengiriman</label
                                >
                                <input
                                    type="date"
                                    id="expected_delivery"
                                    v-model="form.expected_delivery"
                                />
                            </div>

                            <div class="form-group">
                                <label for="status">Status</label>
                                <select
                                    id="status"
                                    v-model="form.status"
                                    required
                                >
                                    <option value="Draft">Draft</option>
                                    <option value="Confirmed">
                                        Terkonfirmasi
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="form-card">
                    <div class="card-header">
                        <h2>Detail Item</h2>
                    </div>
                    <div class="card-body">
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
                                            <div class="item-name">
                                                {{ line.item.name }}
                                            </div>
                                            <div class="item-code">
                                                {{ line.item.item_code }}
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
                                        {{ formatCurrency(line.discount || 0) }}
                                    </td>
                                    <td class="right">
                                        {{ formatCurrency(line.tax || 0) }}
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

                <div class="form-actions">
                    <button
                        type="button"
                        class="btn btn-secondary"
                        @click="goBack"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        class="btn btn-primary"
                        @click="createOrder"
                        :disabled="isSubmitting"
                    >
                        {{ isSubmitting ? "Memproses..." : "Buat Sales Order" }}
                    </button>
                </div>
            </template>
        </div>
    </div>
</template>

<script>
import { ref, onMounted } from "vue";
import { useRouter, useRoute } from "vue-router";
import axios from "axios";

export default {
    name: "CreateOrderFromQuotation",
    setup() {
        const router = useRouter();
        const route = useRoute();

        // Data
        const quotation = ref(null);
        const unitOfMeasures = ref([]);
        const isLoading = ref(true);
        const isSubmitting = ref(false);
        const error = ref("");

        // Form data
        const form = ref({
            quotation_id: "",
            so_number: "",
            so_date: new Date().toISOString().substr(0, 10),
            expected_delivery: "",
            status: "Draft",
        });

        // Generate a unique order number
        const generateOrderNumber = () => {
            const today = new Date();
            const year = today.getFullYear().toString().slice(-2);
            const month = (today.getMonth() + 1).toString().padStart(2, "0");
            const day = today.getDate().toString().padStart(2, "0");
            const random = Math.floor(Math.random() * 1000)
                .toString()
                .padStart(3, "0");

            return `SO${year}${month}${day}-${random}`;
        };

        // Load data
        const loadData = async () => {
            const quotationId = route.params.id;
            if (!quotationId) {
                error.value = "ID Penawaran tidak ditemukan.";
                isLoading.value = false;
                return;
            }

            try {
                // Load unit of measures for reference
                const uomResponse = await axios.get("/unit-of-measures");
                unitOfMeasures.value = uomResponse.data.data;

                // Load quotation details
                const response = await axios.get(`/quotations/${quotationId}`);
                quotation.value = response.data.data;

                // Check if quotation status is valid for conversion
                if (quotation.value.status !== "Accepted") {
                    error.value =
                        "Hanya penawaran dengan status Diterima yang dapat dikonversi menjadi sales order.";
                }

                // Initialize form
                form.value.quotation_id = quotation.value.quotation_id;
                form.value.so_number = generateOrderNumber();

                // Set expected delivery if available
                if (quotation.value.validity_date) {
                    form.value.expected_delivery =
                        quotation.value.validity_date;
                }
            } catch (err) {
                console.error("Error loading quotation:", err);
                error.value = "Terjadi kesalahan saat memuat data penawaran.";
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

        // Calculate total tax of all lines
        const calculateTotalTax = () => {
            if (!quotation.value || !quotation.value.salesQuotationLine)
                return 0;
            return quotation.value.salesQuotationLine.reduce(
                (sum, line) => sum + (line.tax || 0),
                0
            );
        };

        // Calculate grand total
        const calculateGrandTotal = () => {
            if (!quotation.value || !quotation.value.salesQuotationLine)
                return 0;
            return quotation.value.salesQuotationLine.reduce(
                (sum, line) => sum + (line.total || 0),
                0
            );
        };

        // Navigation
        const goBack = () => {
            router.push(`/sales/quotations/${form.value.quotation_id}`);
        };

        // Create order from quotation
        const createOrder = async () => {
            // Validate form
            if (!form.value.so_number || !form.value.so_date) {
                error.value = "Harap isi semua field yang wajib diisi.";
                return;
            }

            isSubmitting.value = true;
            error.value = "";

            try {
                await axios.post("/orders/from-quotation", form.value);
                alert("Sales order berhasil dibuat dari penawaran!");

                // Redirect to orders list
                router.push("/sales/orders");
            } catch (err) {
                console.error("Error creating order from quotation:", err);

                if (
                    err.response &&
                    err.response.data &&
                    err.response.data.message
                ) {
                    error.value = err.response.data.message;
                } else {
                    error.value = "Terjadi kesalahan saat membuat sales order.";
                }
            } finally {
                isSubmitting.value = false;
            }
        };

        onMounted(() => {
            loadData();
        });

        return {
            quotation,
            unitOfMeasures,
            isLoading,
            isSubmitting,
            error,
            form,
            formatDate,
            formatCurrency,
            getUomSymbol,
            getStatusLabel,
            getStatusClass,
            calculateSubtotal,
            calculateTotalTax,
            calculateGrandTotal,
            goBack,
            createOrder,
        };
    },
};
</script>

<style scoped>
.order-from-quotation {
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

.alert {
    padding: 1rem;
    border-radius: 0.375rem;
    margin-bottom: 1.5rem;
}

.alert-danger {
    background-color: #fee2e2;
    color: #b91c1c;
    border: 1px solid #fecaca;
}

.form-container {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.info-panel,
.form-card {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.panel-header,
.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    background-color: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}

.panel-header h2,
.card-header h2 {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0;
    color: #1e293b;
}

.panel-body,
.card-body {
    padding: 1.5rem;
}

.info-grid {
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

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #334155;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 0.625rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.375rem;
    font-size: 0.875rem;
}

.form-group input:focus,
.form-group select:focus {
    outline: 2px solid #2563eb;
    outline-offset: 1px;
}

.items-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
}

.items-table th {
    text-align: left;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #e2e8f0;
    background-color: #f8fafc;
    font-weight: 500;
    color: #64748b;
    font-size: 0.75rem;
}

.items-table td {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #f1f5f9;
    font-size: 0.875rem;
}

.right {
    text-align: right;
}

.center {
    text-align: center;
}

.item-info {
    display: flex;
    flex-direction: column;
}

.item-name {
    font-weight: 500;
}

.item-code {
    font-size: 0.75rem;
    color: #64748b;
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

.btn-primary {
    background-color: #2563eb;
    color: white;
}

.btn-primary:hover:not(:disabled) {
    background-color: #1d4ed8;
}

.btn-primary:disabled {
    background-color: #93c5fd;
    cursor: not-allowed;
}

.btn-secondary {
    background-color: #e2e8f0;
    color: #1e293b;
}

.btn-secondary:hover {
    background-color: #cbd5e1;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 1rem;
}

.loading-indicator {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 4rem 0;
    color: #64748b;
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

    .info-grid,
    .form-row {
        grid-template-columns: 1fr;
        gap: 0.75rem;
    }

    .items-table {
        display: block;
        overflow-x: auto;
    }
}
</style>
