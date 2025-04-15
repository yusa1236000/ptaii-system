<!-- src/views/sales/SalesInvoiceForm.vue -->
<template>
    <div class="invoice-form">
        <div class="page-header">
            <h1>{{ isEditMode ? "Edit Invoice" : "Create New Invoice" }}</h1>
            <div class="page-actions">
                <button class="btn btn-secondary" @click="goBack">
                    <i class="fas fa-arrow-left"></i> Back
                </button>
                <button
                    class="btn btn-primary"
                    @click="saveInvoice"
                    :disabled="isSubmitting"
                >
                    <i class="fas fa-save"></i>
                    {{ isSubmitting ? "Saving..." : "Save" }}
                </button>
            </div>
        </div>

        <div v-if="error" class="alert alert-danger">
            {{ error }}
        </div>

        <div class="form-container">
            <div class="form-card">
                <div class="card-header">
                    <h2>Invoice Information</h2>
                </div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="invoice_number">Invoice Number*</label>
                            <input
                                type="text"
                                id="invoice_number"
                                v-model="form.invoice_number"
                                required
                                :readonly="isEditMode"
                                :class="{ readonly: isEditMode }"
                            />
                            <small v-if="isEditMode" class="text-muted"
                                >Invoice number cannot be changed</small
                            >
                        </div>

                        <div class="form-group">
                            <label for="invoice_date">Invoice Date*</label>
                            <input
                                type="date"
                                id="invoice_date"
                                v-model="form.invoice_date"
                                required
                            />
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="customer_id">Customer*</label>
                            <select
                                id="customer_id"
                                v-model="form.customer_id"
                                required
                                @change="loadCustomerData"
                            >
                                <option value="">-- Select Customer --</option>
                                <option
                                    v-for="customer in customers"
                                    :key="customer.customer_id"
                                    :value="customer.customer_id"
                                >
                                    {{ customer.name }}
                                </option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="due_date">Due Date*</label>
                            <input
                                type="date"
                                id="due_date"
                                v-model="form.due_date"
                                required
                            />
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="so_id">Sales Order (Optional)</label>
                            <select
                                id="so_id"
                                v-model="form.so_id"
                                @change="loadOrderData"
                            >
                                <option value="">-- Select Order --</option>
                                <option
                                    v-for="order in salesOrders"
                                    :key="order.so_id"
                                    :value="order.so_id"
                                >
                                    {{ order.so_number }}
                                </option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="reference">Reference</label>
                            <input
                                type="text"
                                id="reference"
                                v-model="form.reference"
                                placeholder="Invoice reference number"
                            />
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="payment_terms">Payment Terms</label>
                            <input
                                type="text"
                                id="payment_terms"
                                v-model="form.payment_terms"
                                placeholder="Payment terms"
                            />
                        </div>

                        <div class="form-group" v-if="isEditMode">
                            <label for="status">Status*</label>
                            <select id="status" v-model="form.status" required>
                                <option value="Draft">Draft</option>
                                <option value="Sent">Sent</option>
                                <option value="Paid">Paid</option>
                                <option value="Partially Paid">
                                    Partially Paid
                                </option>
                                <option value="Overdue">Overdue</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-card">
                <div class="card-header">
                    <h2>Invoice Items</h2>
                    <button
                        type="button"
                        class="btn btn-sm btn-primary"
                        @click="addLine"
                    >
                        <i class="fas fa-plus"></i> Add Item
                    </button>
                </div>
                <div class="card-body">
                    <div v-if="form.lines.length === 0" class="empty-lines">
                        <p>
                            No items have been added yet. Click "Add Item" to
                            add invoice items.
                        </p>
                    </div>

                    <div v-else class="invoice-lines">
                        <div class="line-headers">
                            <div class="line-header">Item</div>
                            <div class="line-header">Unit Price</div>
                            <div class="line-header">Quantity</div>
                            <div class="line-header">UOM</div>
                            <div class="line-header">Discount</div>
                            <div class="line-header">Tax</div>
                            <div class="line-header">Subtotal</div>
                            <div class="line-header">Total</div>
                            <div class="line-header"></div>
                        </div>

                        <div
                            v-for="(line, index) in form.lines"
                            :key="index"
                            class="invoice-line"
                        >
                            <div class="line-item">
                                <select
                                    v-model="line.item_id"
                                    required
                                    @change="updateItemInfo(index)"
                                >
                                    <option value="">-- Select Item --</option>
                                    <option
                                        v-for="item in items"
                                        :key="item.item_id"
                                        :value="item.item_id"
                                    >
                                        {{ item.item_code }} - {{ item.name }}
                                    </option>
                                </select>
                            </div>

                            <div class="line-item">
                                <input
                                    type="number"
                                    v-model="line.unit_price"
                                    min="0"
                                    step="0.01"
                                    required
                                    @input="calculateLineTotals(index)"
                                />
                            </div>

                            <div class="line-item">
                                <input
                                    type="number"
                                    v-model="line.quantity"
                                    min="0"
                                    step="0.01"
                                    required
                                    @input="calculateLineTotals(index)"
                                />
                            </div>

                            <div class="line-item">
                                <select v-model="line.uom_id" required>
                                    <option value="">-- UOM --</option>
                                    <option
                                        v-for="uom in unitOfMeasures"
                                        :key="uom.uom_id"
                                        :value="uom.uom_id"
                                    >
                                        {{ uom.symbol }}
                                    </option>
                                </select>
                            </div>

                            <div class="line-item">
                                <input
                                    type="number"
                                    v-model="line.discount"
                                    min="0"
                                    step="0.01"
                                    @input="calculateLineTotals(index)"
                                />
                            </div>

                            <div class="line-item">
                                <input
                                    type="number"
                                    v-model="line.tax"
                                    min="0"
                                    step="0.01"
                                    @input="calculateLineTotals(index)"
                                />
                            </div>

                            <div class="line-item subtotal">
                                {{ formatCurrency(line.subtotal) }}
                            </div>

                            <div class="line-item total">
                                {{ formatCurrency(line.total) }}
                            </div>

                            <div class="line-item actions">
                                <button
                                    type="button"
                                    class="btn-icon delete"
                                    title="Remove Item"
                                    @click="removeLine(index)"
                                >
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>

                        <div class="invoice-totals">
                            <div class="total-row">
                                <div class="total-label">Subtotal:</div>
                                <div class="total-value">
                                    {{ formatCurrency(calculateSubtotal()) }}
                                </div>
                            </div>
                            <div class="total-row">
                                <div class="total-label">Total Discount:</div>
                                <div class="total-value">
                                    {{
                                        formatCurrency(calculateTotalDiscount())
                                    }}
                                </div>
                            </div>
                            <div class="total-row">
                                <div class="total-label">Total Tax:</div>
                                <div class="total-value">
                                    {{ formatCurrency(calculateTotalTax()) }}
                                </div>
                            </div>
                            <div class="total-row grand-total">
                                <div class="total-label">Total:</div>
                                <div class="total-value">
                                    {{ formatCurrency(calculateGrandTotal()) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-secondary" @click="goBack">
                    Cancel
                </button>
                <button
                    type="button"
                    class="btn btn-primary"
                    @click="saveInvoice"
                    :disabled="isSubmitting"
                >
                    {{ isSubmitting ? "Saving..." : "Save Invoice" }}
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, computed, onMounted, watch } from "vue";
import { useRouter, useRoute } from "vue-router";
import SalesInvoiceService from "@/services/SalesInvoiceService";
import UnitOfMeasureService from "@/services/UnitOfMeasureService";
import axios from "axios";

export default {
    name: "SalesInvoiceForm",
    setup() {
        const router = useRouter();
        const route = useRoute();

        // Form data
        const form = ref({
            invoice_number: "",
            invoice_date: new Date().toISOString().substr(0, 10),
            customer_id: "",
            due_date: "",
            so_id: "",
            reference: "",
            payment_terms: "",
            status: "Draft",
            lines: [],
        });

        // Reference data
        const customers = ref([]);
        const items = ref([]);
        const unitOfMeasures = ref([]);
        const salesOrders = ref([]);

        // UI state
        const isLoading = ref(false);
        const isSubmitting = ref(false);
        const error = ref("");

        // Check if we're in edit mode
        const isEditMode = computed(() => {
            return route.params.id !== undefined;
        });

        // Generate a unique invoice number for new invoices
        const generateInvoiceNumber = () => {
            const today = new Date();
            const year = today.getFullYear().toString().slice(-2);
            const month = (today.getMonth() + 1).toString().padStart(2, "0");
            const day = today.getDate().toString().padStart(2, "0");
            const random = Math.floor(Math.random() * 1000)
                .toString()
                .padStart(3, "0");

            return `INV${year}${month}${day}-${random}`;
        };

        // Load reference data
        const loadReferenceData = async () => {
            try {
                // Load customers
                const customersResponse = await axios.get("/customers");
                customers.value = customersResponse.data.data;

                // Load items
                const itemsResponse = await axios.get("/items");
                items.value = itemsResponse.data.data;

                // Load unit of measures
                const uomResponse = await UnitOfMeasureService.getAll();
                unitOfMeasures.value = uomResponse.data || [];

                // Load sales orders for the customer if selected
                if (form.value.customer_id) {
                    loadSalesOrders();
                }
            } catch (err) {
                console.error("Error loading reference data:", err);
                error.value = "Failed to load reference data.";
            }
        };

        // Load sales orders for a customer
        const loadSalesOrders = async () => {
            if (!form.value.customer_id) return;

            try {
                const response = await axios.get("/orders", {
                    params: {
                        customer_id: form.value.customer_id,
                        status: "Confirmed,Partially Delivered,Delivered", // Only get orders that can be invoiced
                    },
                });
                salesOrders.value = response.data.data || [];
            } catch (err) {
                console.error("Error loading sales orders:", err);
                salesOrders.value = [];
            }
        };

        // Load customer data when customer changes
        const loadCustomerData = async () => {
            // Reset sales order when customer changes
            form.value.so_id = "";

            // Load sales orders for the selected customer
            loadSalesOrders();

            // Update payment terms from customer if available
            if (form.value.customer_id) {
                const customer = customers.value.find(
                    (c) => c.customer_id === form.value.customer_id
                );
                if (customer && customer.payment_terms) {
                    form.value.payment_terms = customer.payment_terms;
                }

                // Calculate due date based on payment terms
                calculateDueDate();
            }
        };

        // Calculate due date based on payment terms
        const calculateDueDate = () => {
            const invoiceDate = new Date(form.value.invoice_date);
            // Set default due date to 30 days from invoice date
            let daysToAdd = 30;

            // Parse payment terms to get days (e.g., "Net 15" -> 15 days)
            const paymentTerms = form.value.payment_terms;
            if (paymentTerms) {
                const match = paymentTerms.match(/Net\s+(\d+)/i);
                if (match && match[1]) {
                    daysToAdd = parseInt(match[1], 10);
                }
            }

            // Calculate due date
            invoiceDate.setDate(invoiceDate.getDate() + daysToAdd);
            form.value.due_date = invoiceDate.toISOString().substr(0, 10);
        };

        // Load order data when order is selected
        const loadOrderData = async () => {
            if (!form.value.so_id) {
                // Clear lines if no order selected
                form.value.lines = [];
                return;
            }

            try {
                const response = await axios.get(`/orders/${form.value.so_id}`);
                const order = response.data.data;

                // Populate invoice lines from order lines
                form.value.lines = (order.salesOrderLines || []).map(
                    (line) => ({
                        item_id: line.item_id,
                        unit_price: line.unit_price,
                        quantity: line.quantity,
                        uom_id: line.uom_id,
                        discount: line.discount || 0,
                        tax: line.tax || 0,
                        subtotal: line.subtotal,
                        total: line.total,
                        so_line_id: line.line_id, // Reference to original order line
                    })
                );

                // Update payment terms if not already set
                if (!form.value.payment_terms && order.payment_terms) {
                    form.value.payment_terms = order.payment_terms;
                    calculateDueDate();
                }
            } catch (err) {
                console.error("Error loading order data:", err);
                error.value = "Failed to load order data.";
            }
        };

        // Load invoice data if in edit mode
        const loadInvoice = async () => {
            if (!isEditMode.value) {
                // Generate a invoice number for new invoices
                form.value.invoice_number = generateInvoiceNumber();

                // Set default due date
                const dueDate = new Date();
                dueDate.setDate(dueDate.getDate() + 30);
                form.value.due_date = dueDate.toISOString().substr(0, 10);

                return;
            }

            isLoading.value = true;
            error.value = "";

            try {
                const response = await SalesInvoiceService.getInvoiceById(
                    route.params.id
                );
                const invoice = response.data;

                // Set form data
                form.value = {
                    invoice_id: invoice.invoice_id,
                    invoice_number: invoice.invoice_number,
                    invoice_date: invoice.invoice_date.substr(0, 10),
                    customer_id: invoice.customer_id,
                    due_date: invoice.due_date.substr(0, 10),
                    so_id: invoice.so_id || "",
                    reference: invoice.reference || "",
                    payment_terms: invoice.payment_terms || "",
                    status: invoice.status,
                    lines: [],
                };

                // Set line items
                if (invoice.invoiceLines && invoice.invoiceLines.length > 0) {
                    form.value.lines = invoice.invoiceLines.map((line) => ({
                        line_id: line.line_id,
                        item_id: line.item_id,
                        unit_price: line.unit_price,
                        quantity: line.quantity,
                        uom_id: line.uom_id,
                        discount: line.discount || 0,
                        tax: line.tax || 0,
                        subtotal: line.subtotal,
                        total: line.total,
                        so_line_id: line.so_line_id,
                    }));
                }

                // Load sales orders after setting customer
                await loadSalesOrders();
            } catch (err) {
                console.error("Error loading invoice:", err);
                error.value = "Failed to load invoice data.";
            } finally {
                isLoading.value = false;
            }
        };

        // Add a new line item
        const addLine = () => {
            form.value.lines.push({
                item_id: "",
                unit_price: 0,
                quantity: 1,
                uom_id: "",
                discount: 0,
                tax: 0,
                subtotal: 0,
                total: 0,
            });
        };

        // Remove a line item
        const removeLine = (index) => {
            form.value.lines.splice(index, 1);
        };

        // Update item info when item is selected
        const updateItemInfo = (index) => {
            const line = form.value.lines[index];
            const selectedItem = items.value.find(
                (item) => item.item_id === line.item_id
            );

            if (selectedItem) {
                // Set unit price if it's 0 (for new lines)
                if (line.unit_price === 0) {
                    line.unit_price = selectedItem.sales_price || 0;
                }

                // Set UOM if not already set
                if (!line.uom_id && selectedItem.uom_id) {
                    line.uom_id = selectedItem.uom_id;
                }

                // Calculate totals
                calculateLineTotals(index);
            }
        };

        // Calculate line totals
        const calculateLineTotals = (index) => {
            const line = form.value.lines[index];

            // Calculate subtotal (unit_price * quantity)
            line.subtotal =
                parseFloat(line.unit_price) * parseFloat(line.quantity);

            // Calculate total (subtotal - discount + tax)
            line.total = line.subtotal - (line.discount || 0) + (line.tax || 0);
        };

        // Calculate subtotal of all lines
        const calculateSubtotal = () => {
            return form.value.lines.reduce(
                (sum, line) => sum + (line.subtotal || 0),
                0
            );
        };

        // Calculate total discount of all lines
        const calculateTotalDiscount = () => {
            return form.value.lines.reduce(
                (sum, line) => sum + (line.discount || 0),
                0
            );
        };

        // Calculate total tax of all lines
        const calculateTotalTax = () => {
            return form.value.lines.reduce(
                (sum, line) => sum + (line.tax || 0),
                0
            );
        };

        // Calculate grand total of all lines
        const calculateGrandTotal = () => {
            return form.value.lines.reduce(
                (sum, line) => sum + (line.total || 0),
                0
            );
        };

        // Format currency
        const formatCurrency = (value) => {
            return new Intl.NumberFormat("id-ID", {
                style: "currency",
                currency: "IDR",
            }).format(value || 0);
        };

        // Go back to the previous page
        const goBack = () => {
            router.push("/sales/invoices");
        };

        // Save the invoice
        const saveInvoice = async () => {
            // Validate form
            if (
                !form.value.invoice_number ||
                !form.value.invoice_date ||
                !form.value.customer_id ||
                !form.value.due_date
            ) {
                error.value = "Please fill in all required fields.";
                return;
            }

            // Validate line items
            if (form.value.lines.length === 0) {
                error.value = "Invoice must have at least one item.";
                return;
            }

            for (let i = 0; i < form.value.lines.length; i++) {
                const line = form.value.lines[i];
                if (
                    !line.item_id ||
                    !line.unit_price ||
                    !line.quantity ||
                    !line.uom_id
                ) {
                    error.value = `Item #${i + 1} has incomplete data.`;
                    return;
                }
            }

            isSubmitting.value = true;
            error.value = "";

            try {
                const invoiceData = {
                    ...form.value,
                    total_amount: calculateGrandTotal(),
                };

                if (isEditMode.value) {
                    // Update existing invoice
                    await SalesInvoiceService.updateInvoice(
                        form.value.invoice_id,
                        invoiceData
                    );
                    alert("Invoice updated successfully!");
                } else {
                    // Create new invoice
                    await SalesInvoiceService.createInvoice(invoiceData);
                    alert("Invoice created successfully!");
                }

                // Redirect to invoices list
                router.push("/sales/invoices");
            } catch (err) {
                console.error("Error saving invoice:", err);

                if (
                    err.response &&
                    err.response.data &&
                    err.response.data.errors
                ) {
                    const errors = err.response.data.errors;
                    const firstError = Object.values(errors)[0][0];
                    error.value = firstError;
                } else if (
                    err.response &&
                    err.response.data &&
                    err.response.data.message
                ) {
                    error.value = err.response.data.message;
                } else {
                    error.value = "Failed to save invoice. Please try again.";
                }
            } finally {
                isSubmitting.value = false;
            }
        };

        // Watch for invoice date changes to update due date
        watch(
            () => form.value.invoice_date,
            () => {
                if (form.value.invoice_date) {
                    calculateDueDate();
                }
            }
        );

        onMounted(async () => {
            await loadReferenceData();
            await loadInvoice();
        });

        return {
            form,
            customers,
            items,
            unitOfMeasures,
            salesOrders,
            isLoading,
            isSubmitting,
            error,
            isEditMode,
            addLine,
            removeLine,
            updateItemInfo,
            calculateLineTotals,
            calculateSubtotal,
            calculateTotalDiscount,
            calculateTotalTax,
            calculateGrandTotal,
            formatCurrency,
            goBack,
            saveInvoice,
            loadCustomerData,
            loadOrderData,
        };
    },
};
</script>

<style scoped>
.invoice-form {
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

.alert {
    padding: 1rem;
    border-radius: 0.375rem;
    margin-bottom: 1.5rem;
}

.alert-danger {
    background-color: var(--danger-bg);
    color: var(--danger-color);
    border: 1px solid var(--danger-light);
}

.form-container {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.form-card {
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

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group:last-child {
    margin-bottom: 0;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: var(--gray-700);
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 0.625rem;
    border: 1px solid var(--gray-200);
    border-radius: 0.375rem;
    font-size: 0.875rem;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    border-color: var(--primary-color);
    outline: none;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-group input.readonly {
    background-color: var(--gray-50);
    cursor: not-allowed;
}

.text-muted {
    color: var(--gray-500);
    font-size: 0.75rem;
    margin-top: 0.25rem;
}

.empty-lines {
    text-align: center;
    padding: 2rem;
    color: var(--gray-500);
    background-color: var(--gray-50);
    border: 1px dashed var(--gray-300);
    border-radius: 0.375rem;
}

.invoice-lines {
    border: 1px solid var(--gray-200);
    border-radius: 0.375rem;
    overflow: hidden;
}

.line-headers {
    display: grid;
    grid-template-columns: 3fr 1fr 1fr 1fr 1fr 1fr 1.5fr 1.5fr 0.5fr;
    gap: 0.5rem;
    background-color: var(--gray-50);
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--gray-200);
}

.line-header {
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--gray-600);
}

.invoice-line {
    display: grid;
    grid-template-columns: 3fr 1fr 1fr 1fr 1fr 1fr 1.5fr 1.5fr 0.5fr;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--gray-200);
    align-items: center;
}

.invoice-line:last-child {
    border-bottom: none;
}

.line-item input,
.line-item select {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid var(--gray-200);
    border-radius: 0.25rem;
    font-size: 0.875rem;
}

.line-item.subtotal,
.line-item.total {
    font-weight: 500;
    text-align: right;
}

.line-item.total {
    color: var(--primary-color);
}

.line-item.actions {
    text-align: center;
}

.btn-icon {
    background: none;
    border: none;
    color: var(--gray-500);
    cursor: pointer;
    padding: 0.375rem;
    border-radius: 0.25rem;
}

.btn-icon:hover {
    background-color: var(--gray-100);
}

.btn-icon.delete:hover {
    color: var(--danger-color);
    background-color: var(--danger-bg);
}

.invoice-totals {
    border-top: 1px solid var(--gray-200);
    padding: 1rem;
    background-color: var(--gray-50);
}

.total-row {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 1rem;
    margin-bottom: 0.5rem;
}

.total-row:last-child {
    margin-bottom: 0;
}

.total-label {
    font-weight: 500;
    color: var(--gray-700);
    width: 10rem;
    text-align: right;
}

.total-value {
    width: 10rem;
    text-align: right;
    font-weight: 500;
}

.grand-total .total-label,
.grand-total .total-value {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--gray-800);
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 1rem;
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
    background-color: var(--primary-color);
    color: white;
}

.btn-primary:hover:not(:disabled) {
    background-color: var(--primary-dark);
}

.btn-primary:disabled {
    background-color: var(--primary-light);
    opacity: 0.7;
    cursor: not-allowed;
}

.btn-secondary {
    background-color: var(--gray-200);
    color: var(--gray-800);
}

.btn-secondary:hover {
    background-color: var(--gray-300);
}

@media (max-width: 1024px) {
    .form-row {
        grid-template-columns: 1fr;
        gap: 0;
    }

    .invoice-line,
    .line-headers {
        grid-template-columns: repeat(8, 1fr) 0.5fr;
        font-size: 0.75rem;
        padding: 0.5rem;
        gap: 0.25rem;
    }
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }

    .invoice-line,
    .line-headers {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        padding: 1rem;
    }

    .line-header {
        display: none;
    }

    .line-item {
        display: flex;
        align-items: center;
        width: 100%;
    }

    .line-item::before {
        content: attr(data-label);
        font-weight: 500;
        width: 8rem;
        text-align: left;
    }

    .total-row {
        flex-direction: column;
        align-items: flex-end;
    }

    .total-label,
    .total-value {
        width: auto;
    }
}
</style>
