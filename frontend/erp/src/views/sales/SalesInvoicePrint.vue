<!-- src/views/sales/SalesInvoicePrint.vue -->
<template>
    <div class="print-container">
        <div class="print-actions" v-if="!isPrinting">
            <button class="btn btn-primary" @click="printInvoice">
                <i class="fas fa-print"></i> Print
            </button>
            <button class="btn btn-secondary" @click="goBack">
                <i class="fas fa-arrow-left"></i> Back
            </button>
        </div>

        <div v-if="isLoading" class="loading-indicator">
            <i class="fas fa-spinner fa-spin"></i> Loading invoice...
        </div>

        <div v-else-if="!invoice" class="empty-state">
            <div class="empty-icon">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <h3>Invoice not found</h3>
            <p>
                The invoice you're looking for doesn't exist or may have been
                deleted.
            </p>
            <button class="btn btn-primary" @click="goBack">
                Back to invoices list
            </button>
        </div>

        <div v-else class="invoice-document">
            <!-- Company Header -->
            <div class="company-header">
                <div class="company-logo">
                    <img src="/images/company-logo.png" alt="Company Logo" />
                </div>
                <div class="company-info">
                    <h1>PT. Company Name</h1>
                    <p>123 Main Street Address, City</p>
                    <p>Phone: (021) 123-4567 | Email: info@example.com</p>
                    <p>Website: www.example.com</p>
                </div>
            </div>

            <!-- Document Title -->
            <div class="document-title">
                <h2>INVOICE</h2>
                <div class="doc-number">No: {{ invoice.invoice_number }}</div>
            </div>

            <!-- Customer & Invoice Info -->
            <div class="info-section">
                <div class="customer-section">
                    <h3>Bill To:</h3>
                    <div class="info-box">
                        <p class="customer-name">{{ invoice.customer.name }}</p>
                        <p>{{ invoice.customer.address || "No address" }}</p>
                        <p v-if="invoice.customer.phone">
                            Phone: {{ invoice.customer.phone }}
                        </p>
                        <p v-if="invoice.customer.email">
                            Email: {{ invoice.customer.email }}
                        </p>
                        <p v-if="invoice.customer.contact_person">
                            <strong>Attn:</strong>
                            {{ invoice.customer.contact_person }}
                        </p>
                    </div>
                </div>

                <div class="invoice-meta">
                    <table class="meta-table">
                        <tr>
                            <td>Invoice Date</td>
                            <td>:</td>
                            <td>{{ formatDate(invoice.invoice_date) }}</td>
                        </tr>
                        <tr>
                            <td>Due Date</td>
                            <td>:</td>
                            <td>{{ formatDate(invoice.due_date) }}</td>
                        </tr>
                        <tr v-if="invoice.reference">
                            <td>Reference</td>
                            <td>:</td>
                            <td>{{ invoice.reference }}</td>
                        </tr>
                        <tr v-if="invoice.sales_order">
                            <td>Sales Order</td>
                            <td>:</td>
                            <td>{{ invoice.sales_order.so_number }}</td>
                        </tr>
                        <tr v-if="invoice.payment_terms">
                            <td>Payment Terms</td>
                            <td>:</td>
                            <td>{{ invoice.payment_terms }}</td>
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
                            <th class="desc-column">Description</th>
                            <th class="qty-column">Quantity</th>
                            <th class="uom-column">Unit</th>
                            <th class="price-column">Unit Price</th>
                            <th class="discount-column">Discount</th>
                            <th class="total-column">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(line, index) in invoice.invoiceLines"
                            :key="line.line_id"
                        >
                            <td class="center">{{ index + 1 }}</td>
                            <td>
                                <div class="item-description">
                                    <div class="item-name">
                                        {{ line.item.name }}
                                    </div>
                                    <div class="item-code">
                                        {{ line.item.item_code }}
                                    </div>
                                </div>
                            </td>
                            <td class="right">
                                {{ formatNumber(line.quantity) }}
                            </td>
                            <td class="center">
                                {{ getUomSymbol(line.uom_id) }}
                            </td>
                            <td class="right">
                                {{ formatCurrency(line.unit_price) }}
                            </td>
                            <td class="right">
                                {{
                                    line.discount
                                        ? formatCurrency(line.discount)
                                        : "-"
                                }}
                            </td>
                            <td class="right">
                                {{ formatCurrency(line.total) }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="5" rowspan="3"></td>
                            <td class="right">Subtotal:</td>
                            <td class="right">
                                {{ formatCurrency(calculateSubtotal()) }}
                            </td>
                        </tr>
                        <tr>
                            <td class="right">Tax:</td>
                            <td class="right">
                                {{ formatCurrency(calculateTotalTax()) }}
                            </td>
                        </tr>
                        <tr class="grand-total">
                            <td class="right">Total:</td>
                            <td class="right">
                                {{ formatCurrency(invoice.total_amount) }}
                            </td>
                        </tr>
                        <tr v-if="invoice.paid_amount > 0">
                            <td colspan="5"></td>
                            <td class="right">Paid:</td>
                            <td class="right">
                                {{ formatCurrency(invoice.paid_amount) }}
                            </td>
                        </tr>
                        <tr v-if="invoice.paid_amount > 0" class="balance-due">
                            <td colspan="5"></td>
                            <td class="right">Balance Due:</td>
                            <td class="right">
                                {{ formatCurrency(calculateBalanceDue()) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Payment Information -->
            <div
                v-if="invoice.payment_instructions"
                class="payment-instructions"
            >
                <h3>Payment Instructions</h3>
                <p>{{ invoice.payment_instructions }}</p>
            </div>

            <!-- Notes Section -->
            <div class="notes-section">
                <h3>Notes & Terms:</h3>
                <ol>
                    <li>Please make payment by the due date.</li>
                    <li>
                        Include the invoice number in your payment reference.
                    </li>
                    <li v-if="invoice.payment_terms">
                        Payment terms: {{ invoice.payment_terms }}.
                    </li>
                    <li>
                        For any inquiry about this invoice, please contact our
                        accounting department.
                    </li>
                </ol>
            </div>

            <!-- Signature Section -->
            <div class="signature-section">
                <div class="signature-box">
                    <p>Issued by,</p>
                    <p>PT. Company Name</p>
                    <div class="signature-placeholder"></div>
                    <p class="signatory">(Authorized Signature)</p>
                    <p>Finance & Accounting</p>
                </div>
            </div>

            <!-- Footer -->
            <div class="document-footer">
                <p>Thank you for your business!</p>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, onMounted } from "vue";
import { useRouter, useRoute } from "vue-router";
import SalesInvoiceService from "@/services/SalesInvoiceService";
import UnitOfMeasureService from "@/services/UnitOfMeasureService";

export default {
    name: "SalesInvoicePrint",
    setup() {
        const router = useRouter();
        const route = useRoute();

        // Data
        const invoice = ref(null);
        const unitOfMeasures = ref([]);
        const isLoading = ref(true);
        const isPrinting = ref(false);

        // Load invoice and reference data
        const loadData = async () => {
            isLoading.value = true;

            try {
                // Load unit of measures for reference
                const uomResponse = await UnitOfMeasureService.getAll();
                unitOfMeasures.value = uomResponse.data || [];

                // Load invoice details
                const invoiceResponse =
                    await SalesInvoiceService.getInvoiceById(route.params.id);
                invoice.value = invoiceResponse.data;
            } catch (error) {
                console.error("Error loading invoice:", error);
                invoice.value = null;
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

        // Format number
        const formatNumber = (value) => {
            return new Intl.NumberFormat("id-ID").format(value || 0);
        };

        // Format currency
        const formatCurrency = (value) => {
            return new Intl.NumberFormat("id-ID", {
                style: "currency",
                currency: "IDR",
                minimumFractionDigits: 0,
                maximumFractionDigits: 0,
            }).format(value || 0);
        };

        // Get UOM symbol
        const getUomSymbol = (uomId) => {
            const uom = unitOfMeasures.value.find((u) => u.uom_id === uomId);
            return uom ? uom.symbol : "-";
        };

        // Calculate subtotal of all lines
        const calculateSubtotal = () => {
            if (!invoice.value || !invoice.value.invoiceLines) return 0;
            return invoice.value.invoiceLines.reduce(
                (sum, line) => sum + (line.subtotal || 0),
                0
            );
        };

        // Calculate total tax of all lines
        const calculateTotalTax = () => {
            if (!invoice.value || !invoice.value.invoiceLines) return 0;
            return invoice.value.invoiceLines.reduce(
                (sum, line) => sum + (line.tax || 0),
                0
            );
        };

        // Calculate balance due
        const calculateBalanceDue = () => {
            if (!invoice.value) return 0;
            return (
                invoice.value.total_amount - (invoice.value.paid_amount || 0)
            );
        };

        // Navigation methods
        const goBack = () => {
            router.push(`/sales/invoices/${route.params.id}`);
        };

        // Print the invoice
        const printInvoice = () => {
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
            loadData();
            window.addEventListener("beforeprint", handlePrintEvent);
            window.addEventListener("afterprint", () => {
                isPrinting.value = false;
            });
        });

        return {
            invoice,
            isLoading,
            isPrinting,
            formatDate,
            formatNumber,
            formatCurrency,
            getUomSymbol,
            calculateSubtotal,
            calculateTotalTax,
            calculateBalanceDue,
            goBack,
            printInvoice,
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
    background-color: var(--primary-color);
    color: white;
}

.btn-secondary {
    background-color: var(--gray-200);
    color: var(--gray-800);
}

.loading-indicator {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 50px 0;
    color: var(--gray-500);
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
    color: var(--gray-300);
    margin-bottom: 20px;
}

/* Invoice Document Styles */
.invoice-document {
    background-color: white;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.company-header {
    display: flex;
    margin-bottom: 30px;
    border-bottom: 2px solid var(--primary-color);
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
    color: var(--gray-800);
}

.company-info p {
    margin: 3px 0;
    font-size: 12px;
    color: var(--gray-600);
}

.document-title {
    text-align: center;
    margin-bottom: 20px;
}

.document-title h2 {
    font-size: 24px;
    font-weight: bold;
    margin: 0 0 5px 0;
    color: var(--gray-800);
}

.doc-number {
    font-size: 14px;
    color: var(--gray-600);
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
.invoice-meta h3 {
    font-size: 14px;
    margin: 0 0 5px 0;
}

.info-box {
    border: 1px solid var(--gray-200);
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

.invoice-meta {
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
    border: 1px solid var(--gray-200);
    padding: 8px;
}

.items-table th {
    background-color: var(--gray-50);
    font-weight: bold;
    text-align: left;
    color: var(--gray-800);
}

.no-column {
    width: 40px;
}

.desc-column {
    width: 30%;
}

.qty-column,
.uom-column {
    width: 80px;
}

.price-column,
.discount-column,
.total-column {
    width: 120px;
}

.center {
    text-align: center;
}

.right {
    text-align: right;
}

.item-description {
    margin-bottom: 5px;
}

.item-name {
    font-weight: bold;
}

.item-code {
    font-size: 10px;
    color: var(--gray-500);
}

.items-table tfoot tr {
    background-color: var(--gray-50);
}

.items-table tfoot td {
    padding: 8px;
    font-weight: bold;
}

.grand-total td {
    background-color: #dbeafe;
    color: #1e40af;
    font-size: 14px;
}

.balance-due td {
    background-color: #fee2e2;
    color: #dc2626;
    font-size: 14px;
}

.payment-instructions {
    margin-bottom: 20px;
    padding: 10px;
    background-color: var(--gray-50);
    border: 1px solid var(--gray-200);
    border-radius: 4px;
}

.payment-instructions h3 {
    font-size: 14px;
    margin: 0 0 10px 0;
}

.payment-instructions p {
    margin: 0;
    font-size: 12px;
}

.notes-section {
    margin-bottom: 30px;
}

.notes-section h3 {
    font-size: 14px;
    margin: 0 0 10px 0;
}

.notes-section ol {
    margin: 0;
    padding-left: 20px;
    font-size: 12px;
}

.notes-section li {
    margin-bottom: 5px;
}

.signature-section {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 30px;
}

.signature-box {
    width: 200px;
    text-align: center;
}

.signature-box p {
    margin: 5px 0;
    font-size: 12px;
}

.signature-placeholder {
    height: 60px;
    margin: 15px 0;
    border-bottom: 1px solid var(--gray-200);
}

.signatory {
    font-weight: bold;
}

.document-footer {
    text-align: center;
    font-size: 12px;
    color: var(--gray-600);
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid var(--gray-200);
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

    .invoice-document {
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
