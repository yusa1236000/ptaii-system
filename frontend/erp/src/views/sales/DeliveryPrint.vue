<!-- src/views/sales/DeliveryPrint.vue -->
<template>
    <div class="print-container">
        <div class="print-actions" v-if="!isPrinting">
            <button class="btn btn-primary" @click="printDelivery">
                <i class="fas fa-print"></i> Cetak
            </button>
            <button class="btn btn-secondary" @click="goBack">
                <i class="fas fa-arrow-left"></i> Kembali
            </button>
        </div>

        <div v-if="isLoading" class="loading-indicator">
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

        <div v-else class="delivery-document">
            <!-- Company Header -->
            <div class="company-header">
                <div class="company-logo">
                    <img src="/images/company-logo.png" alt="Company Logo" />
                </div>
                <div class="company-info">
                    <h1>PT. Nama Perusahaan</h1>
                    <p>Jl. Contoh Alamat No. 123, Jakarta Selatan</p>
                    <p>Telp: (021) 123-4567 | Email: info@example.com</p>
                    <p>Website: www.example.com</p>
                </div>
            </div>

            <!-- Document Title -->
            <div class="document-title">
                <h2>SURAT JALAN</h2>
                <div class="doc-number">No: {{ delivery.delivery_number }}</div>
            </div>

            <!-- Customer & Order Info -->
            <div class="info-section">
                <div class="customer-section">
                    <h3>Pelanggan:</h3>
                    <div class="info-box">
                        <p class="customer-name">
                            {{ delivery.order.customer.name }}
                        </p>
                        <p>
                            {{
                                delivery.shipping_address ||
                                delivery.order.customer.address ||
                                "Alamat tidak tersedia"
                            }}
                        </p>
                        <p v-if="delivery.order.customer.phone">
                            Telp: {{ delivery.order.customer.phone }}
                        </p>
                        <p v-if="delivery.order.customer.contact_person">
                            <strong>Up:</strong>
                            {{ delivery.order.customer.contact_person }}
                        </p>
                    </div>
                </div>

                <div class="delivery-meta">
                    <table class="meta-table">
                        <tr>
                            <td>Tanggal</td>
                            <td>:</td>
                            <td>{{ formatDate(delivery.delivery_date) }}</td>
                        </tr>
                        <tr>
                            <td>No. Pesanan</td>
                            <td>:</td>
                            <td>{{ delivery.order.so_number }}</td>
                        </tr>
                        <tr v-if="delivery.carrier">
                            <td>Kurir</td>
                            <td>:</td>
                            <td>{{ delivery.carrier }}</td>
                        </tr>
                        <tr v-if="delivery.tracking_number">
                            <td>No. Resi</td>
                            <td>:</td>
                            <td>{{ delivery.tracking_number }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Items Table -->
            <div class="items-section">
                <table class="items-table">
                    <thead>
                        <tr>
                            <th class="no-column">No</th>
                            <th class="code-column">Kode</th>
                            <th class="desc-column">Deskripsi Item</th>
                            <th class="qty-column">Jumlah</th>
                            <th class="uom-column">Satuan</th>
                            <th class="remarks-column">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(item, index) in delivery.deliveryItems"
                            :key="item.delivery_item_id || index"
                        >
                            <td class="center">{{ index + 1 }}</td>
                            <td>{{ item.item.item_code }}</td>
                            <td>{{ item.item.name }}</td>
                            <td class="right">{{ item.quantity }}</td>
                            <td class="center">
                                {{ item.unitOfMeasure?.symbol || "-" }}
                            </td>
                            <td></td>
                        </tr>
                        <!-- Empty rows for additional items written by hand if needed -->
                        <tr
                            v-for="n in 3"
                            :key="`empty-${n}`"
                            class="empty-row"
                        >
                            <td class="center">
                                {{ delivery.deliveryItems.length + n }}
                            </td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Notes Section -->
            <div v-if="delivery.notes" class="notes-section">
                <h3>Catatan:</h3>
                <p>{{ delivery.notes }}</p>
            </div>

            <!-- Signature Section -->
            <div class="signature-section">
                <div class="signature-box">
                    <p>Pengirim,</p>
                    <div class="signature-placeholder"></div>
                    <p class="signatory">(................................)</p>
                    <p>PT. Nama Perusahaan</p>
                </div>

                <div class="signature-box">
                    <p>Diperiksa,</p>
                    <div class="signature-placeholder"></div>
                    <p class="signatory">(................................)</p>
                    <p>Bagian Pengiriman</p>
                </div>

                <div class="signature-box">
                    <p>Diterima,</p>
                    <div class="signature-placeholder"></div>
                    <p class="signatory">(................................)</p>
                    <p>{{ delivery.order.customer.name }}</p>
                </div>
            </div>

            <!-- Footer -->
            <div class="document-footer">
                <p>
                    Dokumen ini adalah bukti pengiriman sah tanpa adanya
                    perubahan
                </p>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import DeliveryService from "@/services/DeliveryService";

export default {
    name: "DeliveryPrint",
    setup() {
        const route = useRoute();
        const router = useRouter();
        const deliveryId = Number(route.params.id);

        const delivery = ref(null);
        const isLoading = ref(true);
        const isPrinting = ref(false);

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

        // Navigation methods
        const goBack = () => {
            router.push(`/sales/deliveries/${deliveryId}`);
        };

        // Print the delivery
        const printDelivery = () => {
            isPrinting.value = true;
            setTimeout(() => {
                window.print();
                isPrinting.value = false;
            }, 300);
        };

        // Handle print event
        const handlePrintEvent = () => {
            isPrinting.value = true;
            setTimeout(() => {
                isPrinting.value = false;
            }, 300);
        };

        onMounted(() => {
            fetchDelivery();
            window.addEventListener("beforeprint", handlePrintEvent);
            window.addEventListener("afterprint", () => {
                isPrinting.value = false;
            });
        });

        return {
            delivery,
            isLoading,
            isPrinting,
            formatDate,
            goBack,
            printDelivery,
        };
    },
};
</script>

<style>
/* General styles */
.print-container {
    max-width: 210mm; /* A4 width */
    margin: 0 auto;
    padding: 20px;
    font-family: Arial, sans-serif;
    color: #333;
}

.print-actions {
    display: flex;
    justify-content: space-between;
    margin-bottom: 20px;
}

.btn {
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary {
    background-color: #2563eb;
    color: white;
}

.btn-secondary {
    background-color: #e5e7eb;
    color: #1f2937;
}

.loading-indicator {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 50px 0;
    color: #64748b;
}

.loading-indicator i {
    margin-right: 10px;
}

.empty-state {
    text-align: center;
    padding: 50px 0;
}

.empty-icon {
    font-size: 48px;
    color: #d1d5db;
    margin-bottom: 20px;
}

/* Delivery Document Styles */
.delivery-document {
    background-color: white;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.company-header {
    display: flex;
    margin-bottom: 30px;
    border-bottom: 2px solid #2563eb;
    padding-bottom: 15px;
}

.company-logo {
    width: 150px;
    margin-right: 20px;
}

.company-logo img {
    max-width: 100%;
    height: auto;
}

.company-info h1 {
    font-size: 18px;
    margin: 0 0 5px 0;
    color: #1e293b;
}

.company-info p {
    margin: 3px 0;
    font-size: 12px;
    color: #64748b;
}

.document-title {
    text-align: center;
    margin-bottom: 20px;
}

.document-title h2 {
    font-size: 20px;
    font-weight: bold;
    margin: 0 0 5px 0;
    color: #1e293b;
}

.doc-number {
    font-size: 14px;
    color: #64748b;
}

.info-section {
    display: flex;
    justify-content: space-between;
    margin-bottom: 30px;
}

.customer-section {
    width: 48%;
}

.customer-section h3,
.delivery-meta h3 {
    font-size: 14px;
    margin: 0 0 5px 0;
}

.info-box {
    border: 1px solid #e5e7eb;
    padding: 10px;
    border-radius: 4px;
}

.customer-name {
    font-weight: bold;
    font-size: 14px;
    margin-bottom: 5px;
}

.info-box p {
    margin: 3px 0;
    font-size: 12px;
}

.delivery-meta {
    width: 48%;
}

.meta-table {
    width: 100%;
    border-collapse: collapse;
}

.meta-table td {
    padding: 5px;
    font-size: 12px;
    vertical-align: top;
}

.meta-table td:first-child {
    width: 120px;
}

.meta-table td:nth-child(2) {
    width: 10px;
}

.items-section {
    margin-bottom: 30px;
}

.items-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
}

.items-table th,
.items-table td {
    border: 1px solid #e5e7eb;
    padding: 8px;
}

.items-table th {
    background-color: #f8fafc;
    font-weight: bold;
    text-align: left;
    color: #1e293b;
}

.no-column {
    width: 40px;
}

.code-column {
    width: 100px;
}

.desc-column {
    width: 30%;
}

.qty-column,
.uom-column {
    width: 80px;
}

.remarks-column {
    width: 20%;
}

.center {
    text-align: center;
}

.right {
    text-align: right;
}

.empty-row td {
    height: 30px;
}

.notes-section {
    margin-bottom: 20px;
}

.notes-section h3 {
    font-size: 14px;
    margin: 0 0 5px 0;
}

.notes-section p {
    font-size: 12px;
    border: 1px solid #e5e7eb;
    padding: 10px;
    border-radius: 4px;
}

.signature-section {
    display: flex;
    justify-content: space-between;
    margin-bottom: 30px;
}

.signature-box {
    width: 30%;
    text-align: center;
}

.signature-box p {
    margin: 5px 0;
    font-size: 12px;
}

.signature-placeholder {
    height: 60px;
    margin: 15px 0;
    border-bottom: 1px solid #e5e7eb;
}

.signatory {
    font-weight: normal;
}

.document-footer {
    text-align: center;
    font-size: 10px;
    color: #64748b;
    margin-top: 20px;
    padding-top: 10px;
    border-top: 1px solid #e5e7eb;
}

/* Print specific styles */
@media print {
    .print-actions {
        display: none !important;
    }

    .print-container {
        padding: 0;
        max-width: none;
    }

    .delivery-document {
        box-shadow: none;
        padding: 0;
    }

    @page {
        size: A4;
        margin: 1cm;
    }

    .signature-section,
    .document-footer {
        page-break-inside: avoid;
    }

    .items-section {
        page-break-before: auto;
    }
}
</style>
