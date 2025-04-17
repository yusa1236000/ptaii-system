<!-- src/components/purchasing/PurchaseOrderForm.vue -->
<template>
    <div class="po-form">
        <form @submit.prevent="handleSubmit">
            <div class="form-section">
                <h3 class="section-title">Basic Information</h3>

                <div class="form-row">
                    <div class="form-group">
                        <label for="po_number">PO Number</label>
                        <input
                            type="text"
                            id="po_number"
                            v-model="formData.po_number"
                            disabled
                            placeholder="Auto-generated"
                        />
                    </div>

                    <div class="form-group">
                        <label for="po_date">PO Date*</label>
                        <input
                            type="date"
                            id="po_date"
                            v-model="formData.po_date"
                            required
                        />
                        <div v-if="errors.po_date" class="error-message">
                            {{ errors.po_date }}
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="vendor_id">Vendor*</label>
                        <select
                            id="vendor_id"
                            v-model="formData.vendor_id"
                            required
                        >
                            <option value="">Select Vendor</option>
                            <option
                                v-for="vendor in vendors"
                                :key="vendor.vendor_id"
                                :value="vendor.vendor_id"
                            >
                                {{ vendor.name }}
                            </option>
                        </select>
                        <div v-if="errors.vendor_id" class="error-message">
                            {{ errors.vendor_id }}
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="expected_delivery"
                            >Expected Delivery Date</label
                        >
                        <input
                            type="date"
                            id="expected_delivery"
                            v-model="formData.expected_delivery"
                        />
                        <div
                            v-if="errors.expected_delivery"
                            class="error-message"
                        >
                            {{ errors.expected_delivery }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3 class="section-title">Terms</h3>

                <div class="form-row">
                    <div class="form-group">
                        <label for="payment_terms">Payment Terms</label>
                        <select
                            id="payment_terms"
                            v-model="formData.payment_terms"
                        >
                            <option value="">Select Payment Terms</option>
                            <option value="Net 30">Net 30</option>
                            <option value="Net 60">Net 60</option>
                            <option value="Net 90">Net 90</option>
                            <option value="Immediate">Immediate</option>
                            <option value="Advance">Advance</option>
                        </select>
                        <div v-if="errors.payment_terms" class="error-message">
                            {{ errors.payment_terms }}
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="delivery_terms">Delivery Terms</label>
                        <select
                            id="delivery_terms"
                            v-model="formData.delivery_terms"
                        >
                            <option value="">Select Delivery Terms</option>
                            <option value="FOB">FOB (Free on Board)</option>
                            <option value="CIF">
                                CIF (Cost, Insurance & Freight)
                            </option>
                            <option value="DDP">
                                DDP (Delivered Duty Paid)
                            </option>
                            <option value="EXW">EXW (Ex Works)</option>
                        </select>
                        <div v-if="errors.delivery_terms" class="error-message">
                            {{ errors.delivery_terms }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="section-header-with-actions">
                    <h3 class="section-title">Order Lines</h3>
                    <button
                        type="button"
                        class="btn btn-sm btn-primary"
                        @click="addLine"
                    >
                        <i class="fas fa-plus"></i> Add Line
                    </button>
                </div>

                <div v-if="errors.lines" class="error-message mb-3">
                    {{ errors.lines }}
                </div>

                <div v-if="formData.lines.length === 0" class="empty-lines">
                    <p>No items added to this purchase order yet.</p>
                    <button type="button" class="btn btn-link" @click="addLine">
                        Add line item
                    </button>
                </div>

                <div v-else class="lines-table-container">
                    <table class="lines-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Quantity</th>
                                <th>Unit</th>
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
                                    >
                                        <option value="">Select Item</option>
                                        <option
                                            v-for="item in items"
                                            :key="item.item_id"
                                            :value="item.item_id"
                                        >
                                            {{ item.name }}
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
                                    <select
                                        v-model="line.uom_id"
                                        required
                                        class="full-width"
                                    >
                                        <option value="">Select Unit</option>
                                        <option
                                            v-for="uom in unitsOfMeasure"
                                            :key="uom.uom_id"
                                            :value="uom.uom_id"
                                        >
                                            {{ uom.name }}
                                        </option>
                                    </select>
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
                            ? "Update Purchase Order"
                            : "Create Purchase Order"
                    }}
                </button>
            </div>
        </form>
    </div>
</template>

<script>
import { ref, reactive, watch, onMounted } from "vue";

export default {
    name: "PurchaseOrderForm",
    props: {
        purchaseOrder: {
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
        vendors: {
            type: Array,
            default: () => [],
        },
        items: {
            type: Array,
            default: () => [],
        },
        unitsOfMeasure: {
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
            po_number: "",
            po_date: new Date().toISOString().slice(0, 10), // Today's date as default
            vendor_id: "",
            payment_terms: "",
            delivery_terms: "",
            expected_delivery: "",
            lines: [],
        });

        // Form validation errors
        const errors = ref({});

        // Initialize form with purchase order data if provided
        const initForm = () => {
            if (props.purchaseOrder) {
                formData.po_number = props.purchaseOrder.po_number || "";
                formData.po_date =
                    props.purchaseOrder.po_date ||
                    new Date().toISOString().slice(0, 10);
                formData.vendor_id = props.purchaseOrder.vendor_id || "";
                formData.payment_terms =
                    props.purchaseOrder.payment_terms || "";
                formData.delivery_terms =
                    props.purchaseOrder.delivery_terms || "";
                formData.expected_delivery =
                    props.purchaseOrder.expected_delivery || "";

                // Initialize lines
                formData.lines = [];
                if (
                    props.purchaseOrder.lines &&
                    props.purchaseOrder.lines.length > 0
                ) {
                    formData.lines = props.purchaseOrder.lines.map((line) => ({
                        item_id: line.item_id,
                        quantity: line.quantity,
                        uom_id: line.uom_id,
                        unit_price: line.unit_price,
                        tax: line.tax || 0,
                        subtotal:
                            line.subtotal || line.quantity * line.unit_price,
                    }));
                }
            } else {
                // Reset form for new purchase order
                formData.po_number = "";
                formData.po_date = new Date().toISOString().slice(0, 10);
                formData.vendor_id = "";
                formData.payment_terms = "";
                formData.delivery_terms = "";
                formData.expected_delivery = "";
                formData.lines = [];
            }
        };

        // Watch for purchase order prop changes
        watch(
            () => props.purchaseOrder,
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

        // Add a new line item
        const addLine = () => {
            formData.lines.push({
                item_id: "",
                quantity: 1,
                uom_id: "",
                unit_price: 0,
                tax: 0,
                subtotal: 0,
            });
        };

        // Remove a line item
        const removeLine = (index) => {
            formData.lines.splice(index, 1);
        };

        // Calculate line totals (subtotal, tax, total)
        const calculateLineTotals = (index) => {
            const line = formData.lines[index];
            if (line && line.quantity && line.unit_price) {
                line.subtotal = line.quantity * line.unit_price;
            }
        };

        // Calculate order subtotal
        const calculateSubtotal = () => {
            return formData.lines.reduce((acc, line) => {
                return acc + (line.subtotal || 0);
            }, 0);
        };

        // Calculate order tax total
        const calculateTaxTotal = () => {
            return formData.lines.reduce((acc, line) => {
                return acc + (line.tax || 0);
            }, 0);
        };

        // Calculate order grand total
        const calculateGrandTotal = () => {
            return calculateSubtotal() + calculateTaxTotal();
        };

        // Format currency
        const formatCurrency = (amount) => {
            if (amount === null || amount === undefined) return "$0.00";
            return new Intl.NumberFormat("en-US", {
                style: "currency",
                currency: "USD",
            }).format(amount);
        };

        // Form submission
        const handleSubmit = () => {
            // Basic validation
            const validationErrors = {};

            if (!formData.po_date) {
                validationErrors.po_date = "PO date is required";
            }

            if (!formData.vendor_id) {
                validationErrors.vendor_id = "Vendor is required";
            }

            if (formData.lines.length === 0) {
                validationErrors.lines = "At least one line item is required";
            } else {
                // Validate line items
                for (let i = 0; i < formData.lines.length; i++) {
                    const line = formData.lines[i];

                    if (!line.item_id) {
                        validationErrors[`lines[${i}].item_id`] =
                            "Item is required";
                    }

                    if (!line.quantity || line.quantity <= 0) {
                        validationErrors[`lines[${i}].quantity`] =
                            "Quantity must be greater than 0";
                    }

                    if (!line.uom_id) {
                        validationErrors[`lines[${i}].uom_id`] =
                            "Unit of measure is required";
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
                po_date: formData.po_date,
                vendor_id: formData.vendor_id,
                payment_terms: formData.payment_terms,
                delivery_terms: formData.delivery_terms,
                expected_delivery: formData.expected_delivery,
                lines: formData.lines.map((line) => ({
                    item_id: line.item_id,
                    quantity: line.quantity,
                    uom_id: line.uom_id,
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
            addLine,
            removeLine,
            calculateLineTotals,
            calculateSubtotal,
            calculateTaxTotal,
            calculateGrandTotal,
            formatCurrency,
            handleSubmit,
            cancel,
        };
    },
};
</script>

<style scoped>
.po-form {
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

input[type="text"]:disabled,
input[type="date"]:disabled,
input[type="number"]:disabled,
textarea:disabled,
select:disabled {
    background-color: #f8fafc;
    cursor: not-allowed;
}

.error-message {
    margin-top: 0.375rem;
    font-size: 0.75rem;
    color: #dc2626;
}

.mb-3 {
    margin-bottom: 0.75rem;
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

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>
