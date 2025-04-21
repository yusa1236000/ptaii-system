<!-- src/components/purchasing/VendorInvoiceForm.vue -->
<template>
    <div class="invoice-form">
        <form @submit.prevent="handleSubmit">
            <div class="form-section">
                <h3 class="section-title">Invoice Information</h3>

                <div class="form-row">
                    <div class="form-group">
                        <label for="invoice_number">Invoice Number*</label>
                        <input
                            type="text"
                            id="invoice_number"
                            v-model="formData.invoice_number"
                            required
                            placeholder="Enter vendor's invoice number"
                        />
                        <div v-if="errors.invoice_number" class="error-message">
                            {{ errors.invoice_number }}
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="invoice_date">Invoice Date*</label>
                        <input
                            type="date"
                            id="invoice_date"
                            v-model="formData.invoice_date"
                            required
                        />
                        <div v-if="errors.invoice_date" class="error-message">
                            {{ errors.invoice_date }}
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="due_date">Due Date*</label>
                        <input
                            type="date"
                            id="due_date"
                            v-model="formData.due_date"
                            required
                        />
                        <div v-if="errors.due_date" class="error-message">
                            {{ errors.due_date }}
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="po_id">Purchase Order*</label>
                        <select
                            id="po_id"
                            v-model="formData.po_id"
                            required
                            @change="loadPurchaseOrderDetails"
                        >
                            <option value="">Select Purchase Order</option>
                            <option
                                v-for="po in purchaseOrders"
                                :key="po.po_id"
                                :value="po.po_id"
                            >
                                {{ po.po_number }}
                            </option>
                        </select>
                        <div v-if="errors.po_id" class="error-message">
                            {{ errors.po_id }}
                        </div>
                    </div>
                </div>

                <div v-if="formData.po_id && poDetails" class="po-details">
                    <div class="info-header">Purchase Order Details</div>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Vendor:</span>
                            <span class="info-value">{{
                                poDetails.vendor ? poDetails.vendor.name : "N/A"
                            }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Status:</span>
                            <span
                                :class="[
                                    'info-value',
                                    'status-badge',
                                    getStatusClass(poDetails.status),
                                ]"
                            >
                                {{ formatStatus(poDetails.status) }}
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">PO Date:</span>
                            <span class="info-value">{{
                                formatDate(poDetails.po_date)
                            }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Expected Delivery:</span>
                            <span class="info-value">{{
                                formatDate(poDetails.expected_delivery)
                            }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="section-header-with-actions">
                    <h3 class="section-title">Invoice Lines</h3>
                    <div v-if="poLineItems.length > 0" class="header-actions">
                        <button
                            type="button"
                            class="btn btn-sm btn-secondary"
                            @click="addAllPoLines"
                        >
                            <i class="fas fa-plus-circle"></i> Add All PO Lines
                        </button>
                    </div>
                </div>

                <div v-if="errors.lines" class="error-message mb-3">
                    {{ errors.lines }}
                </div>

                <div v-if="formData.lines.length === 0" class="empty-lines">
                    <p>No items added to this invoice yet.</p>
                    <p v-if="poLineItems.length === 0 && formData.po_id">
                        Select a purchase order to add line items.
                    </p>
                    <button
                        v-else
                        type="button"
                        class="btn btn-link"
                        @click="addNewLine"
                    >
                        Add line item
                    </button>
                </div>

                <div v-else class="lines-table-container">
                    <table class="lines-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>PO Line</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Tax</th>
                                <th>Subtotal</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(line, index) in formData.lines"
                                :key="index"
                            >
                                <td>
                                    <select
                                        v-model="line.item_id"
                                        required
                                        class="full-width"
                                        @change="updateLineItemDetails(index)"
                                    >
                                        <option value="">Select Item</option>
                                        <option
                                            v-for="item in poLineItems"
                                            :key="item.item_id"
                                            :value="item.item_id"
                                        >
                                            {{ item.name }}
                                        </option>
                                    </select>
                                </td>
                                <td>
                                    <select
                                        v-model="line.po_line_id"
                                        required
                                        class="full-width"
                                        @change="updateLineFromPo(index)"
                                    >
                                        <option value="">Select PO Line</option>
                                        <option
                                            v-for="poLine in getPOLinesForItem(
                                                line.item_id
                                            )"
                                            :key="poLine.line_id"
                                            :value="poLine.line_id"
                                        >
                                            {{ formatPoLineOption(poLine) }}
                                        </option>
                                    </select>
                                </td>
                                <td>
                                    <input
                                        type="number"
                                        v-model.number="line.quantity"
                                        min="1"
                                        required
                                        class="full-width"
                                        @input="calculateLineTotals(index)"
                                    />
                                </td>
                                <td>
                                    <input
                                        type="number"
                                        v-model.number="line.unit_price"
                                        min="0"
                                        step="0.01"
                                        required
                                        class="full-width"
                                        @input="calculateLineTotals(index)"
                                    />
                                </td>
                                <td>
                                    <input
                                        type="number"
                                        v-model.number="line.tax"
                                        min="0"
                                        step="0.01"
                                        class="full-width"
                                        @input="calculateLineTotals(index)"
                                    />
                                </td>
                                <td>
                                    <div class="subtotal">
                                        {{ formatCurrency(line.subtotal) }}
                                    </div>
                                </td>
                                <td>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-danger"
                                        @click="removeLine(index)"
                                    >
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4"></td>
                                <td class="text-right">
                                    <strong>Subtotal:</strong>
                                </td>
                                <td>
                                    {{ formatCurrency(calculateSubtotal()) }}
                                </td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="4"></td>
                                <td class="text-right">
                                    <strong>Tax Total:</strong>
                                </td>
                                <td>
                                    {{ formatCurrency(calculateTaxTotal()) }}
                                </td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="4"></td>
                                <td class="text-right">
                                    <strong>Grand Total:</strong>
                                </td>
                                <td>
                                    {{ formatCurrency(calculateGrandTotal()) }}
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>

                    <div class="add-line-button">
                        <button
                            type="button"
                            class="btn btn-sm btn-secondary"
                            @click="addNewLine"
                        >
                            <i class="fas fa-plus"></i> Add Line
                        </button>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-secondary" @click="cancel">
                    Cancel
                </button>
                <button
                    type="submit"
                    class="btn btn-primary"
                    :disabled="isSubmitting"
                >
                    {{
                        isSubmitting
                            ? "Saving..."
                            : isEditMode
                            ? "Update Invoice"
                            : "Create Invoice"
                    }}
                </button>
            </div>
        </form>
    </div>
</template>

<script>
import { ref, reactive, watch, onMounted } from "vue";
import PurchaseOrderService from "@/services/PurchaseOrderService";

export default {
    name: "VendorInvoiceForm",
    props: {
        vendorInvoice: {
            type: Object,
            default: null,
        },
        isEditMode: {
            type: Boolean,
            default: false,
        },
        isSubmitting: {
            type: Boolean,
            default: false,
        },
        purchaseOrders: {
            type: Array,
            default: () => [],
        },
        serverErrors: {
            type: Object,
            default: () => ({}),
        },
    },
    emits: ["submit", "cancel"],
    setup(props, { emit }) {
        // Form data
        const formData = reactive({
            invoice_number: "",
            invoice_date: new Date().toISOString().slice(0, 10),
            due_date: "",
            po_id: "",
            lines: [],
        });

        // Form state
        const errors = ref({});
        const poDetails = ref(null);
        const poLineItems = ref([]);
        const poLines = ref([]);

        // Initialize form with vendor invoice data if provided
        const initForm = () => {
            if (props.vendorInvoice) {
                formData.invoice_number =
                    props.vendorInvoice.invoice_number || "";
                formData.invoice_date =
                    props.vendorInvoice.invoice_date ||
                    new Date().toISOString().slice(0, 10);
                formData.due_date = props.vendorInvoice.due_date || "";
                formData.po_id = props.vendorInvoice.po_id || "";

                // Initialize lines
                formData.lines = [];
                if (
                    props.vendorInvoice.lines &&
                    props.vendorInvoice.lines.length > 0
                ) {
                    formData.lines = props.vendorInvoice.lines.map((line) => ({
                        po_line_id: line.po_line_id,
                        item_id: line.item_id,
                        quantity: line.quantity,
                        unit_price: line.unit_price,
                        tax: line.tax || 0,
                        subtotal:
                            line.subtotal || line.quantity * line.unit_price,
                    }));
                }

                // Load PO details if PO ID is provided
                if (formData.po_id) {
                    loadPurchaseOrderDetails();
                }
            } else {
                // Reset form for new invoice
                formData.invoice_number = "";
                formData.invoice_date = new Date().toISOString().slice(0, 10);
                formData.due_date = "";
                formData.po_id = "";
                formData.lines = [];
            }
        };

        // Watch for vendorInvoice prop changes
        watch(
            () => props.vendorInvoice,
            () => {
                initForm();
            },
            { deep: true }
        );

        // Watch for server errors
        watch(
            () => props.serverErrors,
            (newErrors) => {
                errors.value = { ...newErrors };
            },
            { deep: true }
        );

        // Load purchase order details
        const loadPurchaseOrderDetails = async () => {
            if (!formData.po_id) {
                poDetails.value = null;
                poLineItems.value = [];
                poLines.value = [];
                return;
            }

            try {
                const response =
                    await PurchaseOrderService.getPurchaseOrderById(
                        formData.po_id
                    );
                poDetails.value =
                    response.data && response.data.data
                        ? response.data.data
                        : null;

                if (poDetails.value) {
                    // Extract unique items from PO lines
                    const items = [];
                    poLines.value = [];

                    if (
                        poDetails.value.lines &&
                        poDetails.value.lines.length > 0
                    ) {
                        poDetails.value.lines.forEach((line) => {
                            if (line.item) {
                                // Add to items if not already in the array
                                if (
                                    !items.some(
                                        (item) =>
                                            item.item_id === line.item.item_id
                                    )
                                ) {
                                    items.push(line.item);
                                }

                                // Add to PO lines
                                poLines.value.push({
                                    line_id: line.line_id,
                                    po_id: poDetails.value.po_id,
                                    item_id: line.item.item_id,
                                    unit_price: line.unit_price,
                                    quantity: line.quantity,
                                    received_quantity:
                                        line.received_quantity || 0,
                                    tax: line.tax || 0,
                                    subtotal: line.subtotal || 0,
                                    total: line.total || 0,
                                    item: line.item,
                                });
                            }
                        });
                    }

                    poLineItems.value = items;
                }
            } catch (error) {
                console.error("Error loading purchase order details:", error);
                poDetails.value = null;
                poLineItems.value = [];
                poLines.value = [];
            }
        };

        // Format date strings
        const formatDate = (dateString) => {
            if (!dateString) return "N/A";
            const date = new Date(dateString);
            return date.toLocaleDateString("en-US", {
                year: "numeric",
                month: "short",
                day: "2-digit",
            });
        };

        // Format currency
        const formatCurrency = (amount) => {
            if (amount === null || amount === undefined) return "$0.00";
            return new Intl.NumberFormat("en-US", {
                style: "currency",
                currency: "USD",
            }).format(amount);
        };

        // Format PO status
        const formatStatus = (status) => {
            switch (status) {
                case "draft":
                    return "Draft";
                case "submitted":
                    return "Submitted";
                case "approved":
                    return "Approved";
                case "sent":
                    return "Sent";
                case "partial":
                    return "Partially Received";
                case "received":
                    return "Received";
                case "completed":
                    return "Completed";
                case "canceled":
                    return "Canceled";
                default:
                    return status;
            }
        };

        // Get status CSS class
        const getStatusClass = (status) => {
            switch (status) {
                case "draft":
                    return "status-draft";
                case "submitted":
                    return "status-pending";
                case "approved":
                    return "status-approved";
                case "sent":
                    return "status-sent";
                case "partial":
                    return "status-partial";
                case "received":
                    return "status-received";
                case "completed":
                    return "status-completed";
                case "canceled":
                    return "status-canceled";
                default:
                    return "status-draft";
            }
        };

        // Format PO line option text
        const formatPoLineOption = (poLine) => {
            const item =
                poLine.item_id &&
                poLineItems.value.find(
                    (item) => item.item_id === poLine.item_id
                );
            const itemName = item ? item.name : "Unknown Item";
            return `${itemName} - Qty: ${
                poLine.quantity
            }, Price: ${formatCurrency(poLine.unit_price)}`;
        };

        // Get PO lines for specific item
        const getPOLinesForItem = (itemId) => {
            if (!itemId) return [];
            return poLines.value.filter((line) => line.item_id === itemId);
        };

        // Add new line
        const addNewLine = () => {
            formData.lines.push({
                po_line_id: "",
                item_id: "",
                quantity: 1,
                unit_price: 0,
                tax: 0,
                subtotal: 0,
            });
        };

        // Remove line
        const removeLine = (index) => {
            formData.lines.splice(index, 1);
        };

        // Update line from PO line
        const updateLineFromPo = (index) => {
            const line = formData.lines[index];
            if (line.po_line_id) {
                const poLine = poLines.value.find(
                    (pl) => pl.line_id === line.po_line_id
                );
                if (poLine) {
                    line.item_id = poLine.item_id;
                    line.unit_price = poLine.unit_price;
                    line.quantity = poLine.quantity;
                    line.tax = poLine.tax || 0;
                    calculateLineTotals(index);
                }
            }
        };

        // Update line item details when item changes
        const updateLineItemDetails = (index) => {
            const line = formData.lines[index];
            if (line.item_id) {
                // Reset PO line selection since item has changed
                line.po_line_id = "";

                // Find first PO line with this item and set default values
                const poLine = poLines.value.find(
                    (pl) => pl.item_id === line.item_id
                );
                if (poLine) {
                    line.unit_price = poLine.unit_price;
                    calculateLineTotals(index);
                }
            }
        };

        // Add all PO lines
        const addAllPoLines = () => {
            formData.lines = [];
            poLines.value.forEach((poLine) => {
                formData.lines.push({
                    po_line_id: poLine.line_id,
                    item_id: poLine.item_id,
                    quantity: poLine.quantity,
                    unit_price: poLine.unit_price,
                    tax: poLine.tax || 0,
                    subtotal:
                        poLine.subtotal || poLine.quantity * poLine.unit_price,
                });
            });
        };

        // Calculate line totals
        const calculateLineTotals = (index) => {
            const line = formData.lines[index];
            if (line && line.quantity && line.unit_price) {
                line.subtotal = line.quantity * line.unit_price;
            }
        };

        // Calculate subtotal
        const calculateSubtotal = () => {
            return formData.lines.reduce((acc, line) => {
                return acc + (line.subtotal || 0);
            }, 0);
        };

        // Calculate tax total
        const calculateTaxTotal = () => {
            return formData.lines.reduce((acc, line) => {
                return acc + (line.tax || 0);
            }, 0);
        };

        // Calculate grand total
        const calculateGrandTotal = () => {
            return calculateSubtotal() + calculateTaxTotal();
        };

        // Form submission
        const handleSubmit = () => {
            // Basic validation
            const validationErrors = {};

            if (!formData.invoice_number) {
                validationErrors.invoice_number = "Invoice number is required";
            }

            if (!formData.invoice_date) {
                validationErrors.invoice_date = "Invoice date is required";
            }

            if (!formData.due_date) {
                validationErrors.due_date = "Due date is required";
            }

            if (!formData.po_id) {
                validationErrors.po_id = "Purchase order is required";
            }

            if (formData.lines.length === 0) {
                validationErrors.lines = "At least one line item is required";
            } else {
                // Validate line items
                for (let i = 0; i < formData.lines.length; i++) {
                    const line = formData.lines[i];

                    if (!line.po_line_id) {
                        validationErrors[`lines[${i}].po_line_id`] =
                            "PO line is required";
                    }

                    if (!line.item_id) {
                        validationErrors[`lines[${i}].item_id`] =
                            "Item is required";
                    }

                    if (!line.quantity || line.quantity <= 0) {
                        validationErrors[`lines[${i}].quantity`] =
                            "Quantity must be greater than 0";
                    }

                    if (line.unit_price === undefined || line.unit_price < 0) {
                        validationErrors[`lines[${i}].unit_price`] =
                            "Unit price must be 0 or greater";
                    }
                }
            }

            // If validation errors exist, update errors and stop submission
            if (Object.keys(validationErrors).length > 0) {
                errors.value = validationErrors;
                return;
            }

            // Clear validation errors
            errors.value = {};

            // Prepare data for submission
            const submitData = {
                invoice_number: formData.invoice_number,
                invoice_date: formData.invoice_date,
                due_date: formData.due_date,
                po_id: formData.po_id,
                lines: formData.lines.map((line) => ({
                    po_line_id: line.po_line_id,
                    item_id: line.item_id,
                    quantity: line.quantity,
                    unit_price: line.unit_price,
                    tax: line.tax || 0,
                })),
            };

            // Emit submit event with form data
            emit("submit", submitData);
        };

        // Cancel form
        const cancel = () => {
            emit("cancel");
        };

        // Initialize form on component mount
        onMounted(() => {
            initForm();
        });

        return {
            formData,
            errors,
            poDetails,
            poLineItems,
            loadPurchaseOrderDetails,
            formatDate,
            formatCurrency,
            formatStatus,
            getStatusClass,
            formatPoLineOption,
            getPOLinesForItem,
            addNewLine,
            removeLine,
            updateLineFromPo,
            updateLineItemDetails,
            addAllPoLines,
            calculateLineTotals,
            calculateSubtotal,
            calculateTaxTotal,
            calculateGrandTotal,
            handleSubmit,
            cancel,
        };
    },
};
</script>

<style scoped>
.invoice-form {
    max-width: 1000px;
    margin: 0 auto;
}

.form-section {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.section-title {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0 0 1.25rem 0;
    color: #1e293b;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #e2e8f0;
}

.section-header-with-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.25rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #e2e8f0;
}

.section-header-with-actions .section-title {
    margin: 0;
    padding-bottom: 0;
    border-bottom: none;
}

.header-actions {
    display: flex;
    gap: 0.5rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.25rem;
}

.form-group {
    margin-bottom: 1.25rem;
}

.form-group:last-child {
    margin-bottom: 0;
}

label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #334155;
    font-size: 0.875rem;
}

input[type="text"],
input[type="date"],
input[type="number"],
textarea,
select {
    width: 100%;
    padding: 0.625rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    background-color: white;
    transition: border-color 0.2s;
}

input[type="text"]:focus,
input[type="date"]:focus,
input[type="number"]:focus,
textarea:focus,
select:focus {
    border-color: #2563eb;
    outline: none;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.error-message {
    margin-top: 0.375rem;
    font-size: 0.75rem;
    color: #dc2626;
}

.mb-3 {
    margin-bottom: 0.75rem;
}

.po-details {
    margin-top: 1.5rem;
    padding: 1rem;
    background-color: #f8fafc;
    border-radius: 0.375rem;
    border: 1px solid #e2e8f0;
}

.info-header {
    font-weight: 600;
    font-size: 0.875rem;
    color: #334155;
    margin-bottom: 0.75rem;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.info-label {
    font-size: 0.75rem;
    color: #64748b;
}

.info-value {
    font-size: 0.875rem;
    color: #334155;
    font-weight: 500;
}

.empty-lines {
    text-align: center;
    padding: 2rem 1rem;
    background-color: #f8fafc;
    border-radius: 0.375rem;
    border: 1px dashed #cbd5e1;
}

.lines-table-container {
    overflow-x: auto;
}

.lines-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
}

.lines-table th {
    text-align: left;
    padding: 0.75rem;
    background-color: #f8fafc;
    font-weight: 500;
    color: #334155;
    border-bottom: 1px solid #e2e8f0;
}

.lines-table td {
    padding: 0.75rem;
    border-bottom: 1px solid #e2e8f0;
    vertical-align: middle;
}

.lines-table tbody tr:last-child td {
    border-bottom: 2px solid #e2e8f0;
}

.lines-table tfoot tr td {
    padding: 0.75rem;
}

.lines-table tfoot tr:last-child td {
    font-weight: 600;
    color: #0f172a;
}

.full-width {
    width: 100%;
}

.text-right {
    text-align: right;
}

.subtotal {
    font-weight: 500;
}

.add-line-button {
    margin-top: 1rem;
    display: flex;
    justify-content: flex-end;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 1.5rem;
}

.btn {
    padding: 0.625rem 1.25rem;
    font-size: 0.875rem;
    font-weight: 500;
    border-radius: 0.375rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
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

.btn-danger {
    background-color: #ef4444;
    color: white;
}

.btn-danger:hover {
    background-color: #dc2626;
}

.btn-link {
    background: none;
    color: #2563eb;
    padding: 0;
    text-decoration: underline;
}

.btn-link:hover {
    color: #1d4ed8;
    text-decoration: underline;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
}

.status-draft {
    background-color: #f1f5f9;
    color: #475569;
}

.status-pending {
    background-color: #fef3c7;
    color: #92400e;
}

.status-approved {
    background-color: #dcfce7;
    color: #166534;
}

.status-sent {
    background-color: #dbeafe;
    color: #1e40af;
}

.status-partial {
    background-color: #fef9c3;
    color: #854d0e;
}

.status-received {
    background-color: #d1fae5;
    color: #065f46;
}

.status-completed {
    background-color: #bbf7d0;
    color: #15803d;
}

.status-canceled {
    background-color: #fee2e2;
    color: #b91c1c;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>
