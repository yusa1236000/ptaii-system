<!-- src/views/sales/SalesReturnDetail.vue - Template Section -->
<template>
    <div class="return-detail">
        <div class="page-header">
            <h1>Detail Pengembalian</h1>
            <div class="page-actions">
                <button class="btn btn-secondary" @click="goBack">
                    <i class="fas fa-arrow-left"></i> Kembali
                </button>
                <div class="btn-group" v-if="salesReturn">
                    <button
                        class="btn btn-primary"
                        @click="editReturn"
                        v-if="canEdit"
                    >
                        <i class="fas fa-edit"></i> Edit
                    </button>

                    <button
                        v-if="salesReturn.status === 'Pending'"
                        class="btn btn-success"
                        @click="confirmProcess"
                    >
                        <i class="fas fa-check-circle"></i> Proses
                    </button>

                    <button
                        v-if="canDelete"
                        class="btn btn-danger"
                        @click="confirmDelete"
                    >
                        <i class="fas fa-trash"></i> Hapus
                    </button>
                </div>
            </div>
        </div>

        <div v-if="isLoading" class="loading-indicator">
            <i class="fas fa-spinner fa-spin"></i> Memuat data pengembalian...
        </div>

        <div v-else-if="!salesReturn" class="empty-state">
            <div class="empty-icon">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <h3>Pengembalian tidak ditemukan</h3>
            <p>
                Pengembalian yang Anda cari mungkin telah dihapus atau tidak
                ada.
            </p>
            <button class="btn btn-primary" @click="goBack">
                Kembali ke daftar pengembalian
            </button>
        </div>

        <div v-else class="return-container">
            <!-- Return Header -->
            <div class="detail-card">
                <div class="card-header">
                    <h2>Informasi Pengembalian</h2>
                    <div
                        class="return-status"
                        :class="getStatusClass(salesReturn.status)"
                    >
                        {{ getStatusLabel(salesReturn.status) }}
                    </div>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-group">
                            <label>Nomor Pengembalian</label>
                            <div class="info-value">
                                {{ salesReturn.return_number }}
                            </div>
                        </div>

                        <div class="info-group">
                            <label>Tanggal Pengembalian</label>
                            <div class="info-value">
                                {{ formatDate(salesReturn.return_date) }}
                            </div>
                        </div>

                        <div class="info-group">
                            <label>Pelanggan</label>
                            <div class="info-value">
                                {{ salesReturn.customer.name }}
                            </div>
                        </div>

                        <div class="info-group">
                            <label>Faktur</label>
                            <div class="info-value">
                                <template v-if="salesReturn.invoice">
                                    <a
                                        @click.prevent="viewInvoice"
                                        href="#"
                                        class="link"
                                    >
                                        {{
                                            salesReturn.invoice
                                                ?.invoice_number ||
                                            salesReturn.invoice_id
                                        }}
                                    </a>
                                </template>
                                <template v-else>-</template>
                            </div>
                        </div>

                        <div class="info-group">
                            <label>Alasan Pengembalian</label>
                            <div class="info-value">
                                {{ salesReturn.return_reason }}
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
                                {{ salesReturn.customer.name }}
                            </div>
                        </div>

                        <div class="info-group">
                            <label>Kode Pelanggan</label>
                            <div class="info-value">
                                {{ salesReturn.customer.customer_code }}
                            </div>
                        </div>

                        <div class="info-group">
                            <label>Alamat</label>
                            <div class="info-value">
                                {{ salesReturn.customer.address || "-" }}
                            </div>
                        </div>

                        <div class="info-group">
                            <label>NPWP</label>
                            <div class="info-value">
                                {{ salesReturn.customer.tax_id || "-" }}
                            </div>
                        </div>

                        <div class="info-group">
                            <label>Contact Person</label>
                            <div class="info-value">
                                {{ salesReturn.customer.contact_person || "-" }}
                            </div>
                        </div>

                        <div class="info-group">
                            <label>Telepon</label>
                            <div class="info-value">
                                {{ salesReturn.customer.phone || "-" }}
                            </div>
                        </div>

                        <div class="info-group">
                            <label>Email</label>
                            <div class="info-value">
                                {{ salesReturn.customer.email || "-" }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Return Items -->
            <div class="detail-card">
                <div class="card-header">
                    <h2>Item Pengembalian</h2>
                </div>
                <div class="card-body">
                    <div class="return-items">
                        <table class="items-table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Jumlah</th>
                                    <th>Kondisi</th>
                                    <th>UOM</th>
                                    <th>Nilai Faktur</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="line in salesReturn.salesReturnLines"
                                    :key="line.line_id"
                                >
                                    <td>
                                        <div class="item-info">
                                            <div class="item-code">
                                                {{
                                                    line.item?.item_code || "-"
                                                }}
                                            </div>
                                            <div class="item-name">
                                                {{ line.item?.name || "-" }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="right">
                                        {{
                                            formatNumber(line.returned_quantity)
                                        }}
                                    </td>
                                    <td>
                                        <span
                                            class="condition-badge"
                                            :class="
                                                getConditionClass(
                                                    line.condition
                                                )
                                            "
                                        >
                                            {{
                                                getConditionText(line.condition)
                                            }}
                                        </span>
                                    </td>
                                    <td class="center">
                                        {{
                                            getUomSymbol(
                                                line.salesInvoiceLine?.uom_id
                                            )
                                        }}
                                    </td>
                                    <td class="right">
                                        {{
                                            formatCurrency(
                                                line.salesInvoiceLine
                                                    ?.unit_price || 0
                                            )
                                        }}
                                    </td>
                                    <td class="right">
                                        {{
                                            formatCurrency(
                                                calculateLineTotal(line)
                                            )
                                        }}
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="grand-total">
                                    <td colspan="5" class="totals-label">
                                        Total Pengembalian
                                    </td>
                                    <td class="totals-value">
                                        {{
                                            formatCurrency(
                                                calculateTotalReturn()
                                            )
                                        }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Invoice Information -->
            <div v-if="salesReturn.invoice" class="detail-card">
                <div class="card-header">
                    <h2>Informasi Faktur</h2>
                </div>
                <div class="card-body">
                    <div class="invoice-info">
                        <div class="info-grid">
                            <div class="info-group">
                                <label>Nomor Faktur</label>
                                <div class="info-value">
                                    {{ salesReturn.invoice.invoice_number }}
                                </div>
                            </div>
                            <div class="info-group">
                                <label>Tanggal Faktur</label>
                                <div class="info-value">
                                    {{
                                        formatDate(
                                            salesReturn.invoice.invoice_date
                                        )
                                    }}
                                </div>
                            </div>
                            <div class="info-group">
                                <label>Total Faktur</label>
                                <div class="info-value">
                                    {{
                                        formatCurrency(
                                            salesReturn.invoice.total_amount
                                        )
                                    }}
                                </div>
                            </div>
                            <div class="info-group">
                                <label>Status Faktur</label>
                                <div class="info-value">
                                    {{ salesReturn.invoice.status }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Financial Impact Section -->
            <div class="detail-card">
                <div class="card-header">
                    <h2>Dampak Keuangan</h2>
                </div>
                <div class="card-body">
                    <div class="financial-impact">
                        <div class="impact-grid">
                            <div class="impact-item">
                                <div class="impact-label">
                                    Total Pengembalian
                                </div>
                                <div class="impact-value">
                                    {{ formatCurrency(calculateTotalReturn()) }}
                                </div>
                            </div>
                            <div class="impact-item">
                                <div class="impact-label">
                                    Penyesuaian Piutang
                                </div>
                                <div class="impact-value">
                                    {{ formatCurrency(calculateTotalReturn()) }}
                                </div>
                            </div>
                            <div class="impact-item">
                                <div class="impact-label">Penyesuaian Stok</div>
                                <div class="impact-value">
                                    {{ getStockAdjustmentCount() }} item
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Return Notes Section -->
            <div v-if="hasNotes" class="detail-card">
                <div class="card-header">
                    <h2>Catatan</h2>
                </div>
                <div class="card-body">
                    <div class="notes-content">
                        <div
                            v-for="(
                                line, index
                            ) in salesReturn.salesReturnLines"
                            :key="index"
                        >
                            <div v-if="line.notes" class="note-item">
                                <div class="note-item-header">
                                    <strong>{{
                                        line.item?.name || "Item"
                                    }}</strong>
                                </div>
                                <div class="note-item-content">
                                    {{ line.notes }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Process Confirmation Modal -->
        <ConfirmationModal
            v-if="showProcessModal"
            title="Konfirmasi Proses"
            message="Apakah Anda yakin ingin memproses pengembalian ini? Stok barang akan diperbarui dan status pengembalian akan berubah menjadi 'Diproses'."
            confirm-button-text="Proses"
            confirm-button-class="btn btn-success"
            @confirm="processReturn"
            @close="closeProcessModal"
        />

        <!-- Delete Confirmation Modal -->
        <ConfirmationModal
            v-if="showDeleteModal"
            title="Konfirmasi Hapus"
            message="Apakah Anda yakin ingin menghapus pengembalian ini? Tindakan ini tidak dapat dibatalkan."
            confirm-button-text="Hapus"
            confirm-button-class="btn btn-danger"
            @confirm="deleteReturn"
            @close="closeDeleteModal"
        />
    </div>
</template>

<!-- src/views/sales/SalesReturnDetail.vue - Style Section -->
<style scoped>
.return-detail {
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
    color: var(--gray-800);
}

.page-actions {
    display: flex;
    gap: 0.75rem;
}

.btn-group {
    display: flex;
    gap: 0.5rem;
}

.return-container {
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
    background-color: var(--gray-50);
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--gray-200);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-header h2 {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0;
    color: var(--gray-800);
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
    color: var(--gray-500);
    margin-bottom: 0.25rem;
}

.info-value {
    font-size: 0.875rem;
    color: var(--gray-800);
    font-weight: 500;
}

.link {
    color: var(--primary-color);
    cursor: pointer;
    text-decoration: none;
}

.link:hover {
    text-decoration: underline;
}

.return-status {
    font-size: 0.75rem;
    font-weight: 500;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
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

.condition-badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
}

.condition-good {
    background-color: #d1fae5;
    color: #059669;
}

.condition-damaged {
    background-color: #fee2e2;
    color: #dc2626;
}

.condition-defective {
    background-color: #fef3c7;
    color: #d97706;
}

.items-table {
    width: 100%;
    border-collapse: collapse;
}

.items-table th {
    text-align: left;
    padding: 0.75rem;
    background-color: var(--gray-50);
    border-bottom: 1px solid var(--gray-200);
    font-weight: 500;
    color: var(--gray-600);
    font-size: 0.75rem;
}

.items-table td {
    padding: 0.75rem;
    border-bottom: 1px solid var(--gray-100);
    vertical-align: middle;
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
    color: var(--gray-500);
}

.item-name {
    font-weight: 500;
}

.totals-label {
    text-align: right;
    font-weight: 500;
    color: var(--gray-700);
}

.totals-value {
    text-align: right;
    font-weight: 600;
    color: var(--gray-800);
}

.grand-total td {
    background-color: var(--gray-50);
    padding: 0.75rem;
}

/* Financial Impact Section */
.financial-impact {
    width: 100%;
}

.impact-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
}

.impact-item {
    background-color: var(--gray-50);
    border-radius: 0.375rem;
    padding: 1rem;
    text-align: center;
}

.impact-label {
    font-size: 0.875rem;
    color: var(--gray-600);
    margin-bottom: 0.5rem;
}

.impact-value {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--gray-800);
}

/* Notes Section */
.notes-content {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.note-item {
    background-color: var(--gray-50);
    border-left: 3px solid var(--primary-color);
    padding: 1rem;
    border-radius: 0 0.25rem 0.25rem 0;
}

.note-item-header {
    margin-bottom: 0.5rem;
    color: var(--gray-700);
}

.note-item-content {
    font-size: 0.875rem;
    color: var(--gray-600);
    white-space: pre-line;
}

.loading-indicator {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 4rem 0;
    color: var(--gray-500);
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
}

.empty-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
    color: var(--gray-300);
}

.empty-state h3 {
    font-size: 1.25rem;
    margin: 0 0 0.5rem 0;
    color: var(--gray-700);
}

.empty-state p {
    margin: 0 0 1.5rem 0;
    color: var(--gray-500);
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
    background-color: var(--primary-color);
    color: white;
}

.btn-primary:hover {
    background-color: var(--primary-dark);
}

.btn-secondary {
    background-color: var(--gray-200);
    color: var(--gray-800);
}

.btn-secondary:hover {
    background-color: var(--gray-300);
}

.btn-success {
    background-color: var(--success-color);
    color: white;
}

.btn-success:hover {
    background-color: var(--success-dark);
}

.btn-danger {
    background-color: var(--danger-color);
    color: white;
}

.btn-danger:hover {
    background-color: var(--danger-dark);
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }

    .info-grid,
    .customer-info,
    .impact-grid {
        grid-template-columns: 1fr;
        gap: 0.75rem;
    }

    .items-table {
        display: block;
        overflow-x: auto;
    }
}
</style>
