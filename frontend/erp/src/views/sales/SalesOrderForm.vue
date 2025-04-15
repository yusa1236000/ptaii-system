<!-- src/views/sales/SalesOrderForm.vue -->
<template>
    <div class="order-form">
        <div class="page-header">
            <h1>{{ isEditMode ? "Edit Order" : "Buat Order Baru" }}</h1>
            <div class="page-actions">
                <button class="btn btn-secondary" @click="goBack">
                    <i class="fas fa-arrow-left"></i> Kembali
                </button>
                <button
                    class="btn btn-primary"
                    @click="saveOrder"
                    :disabled="isSubmitting"
                >
                    <i class="fas fa-save"></i>
                    {{ isSubmitting ? "Menyimpan..." : "Simpan" }}
                </button>
            </div>
        </div>

        <div v-if="error" class="alert alert-danger">
            {{ error }}
        </div>

        <div class="form-container">
            <div class="form-card">
                <div class="card-header">
                    <h2>Informasi Order</h2>
                </div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="so_number">Nomor Order*</label>
                            <input
                                type="text"
                                id="so_number"
                                v-model="form.so_number"
                                required
                                :readonly="isEditMode"
                                :class="{ readonly: isEditMode }"
                            />
                            <small v-if="isEditMode" class="text-muted"
                                >Nomor order tidak dapat diubah</small
                            >
                        </div>

                        <div class="form-group">
                            <label for="so_date">Tanggal Order*</label>
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
                            <label for="customer_id">Pelanggan*</label>
                            <select
                                id="customer_id"
                                v-model="form.customer_id"
                                required
                            >
                                <option value="">-- Pilih Pelanggan --</option>
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
                            <label for="expected_delivery"
                                >Perkiraan Pengiriman</label
                            >
                            <input
                                type="date"
                                id="expected_delivery"
                                v-model="form.expected_delivery"
                            />
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="payment_terms">Syarat Pembayaran</label>
                            <input
                                type="text"
                                id="payment_terms"
                                v-model="form.payment_terms"
                                placeholder="Contoh: 30 hari setelah pengiriman"
                            />
                        </div>

                        <div class="form-group">
                            <label for="delivery_terms"
                                >Syarat Pengiriman</label
                            >
                            <input
                                type="text"
                                id="delivery_terms"
                                v-model="form.delivery_terms"
                                placeholder="Contoh: Franco gudang pembeli"
                            />
                        </div>
                    </div>

                    <div class="form-group" v-if="isEditMode">
                        <label for="status">Status*</label>
                        <select id="status" v-model="form.status" required>
                            <option value="Draft">Draft</option>
                            <option value="Confirmed">Dikonfirmasi</option>
                            <option value="Processing">Diproses</option>
                            <option value="Shipped">Dikirim</option>
                            <option value="Delivered">Terkirim</option>
                            <option value="Invoiced" disabled>
                                Difakturkan
                            </option>
                            <option value="Closed" disabled>Selesai</option>
                        </select>
                        <small
                            v-if="
                                form.status === 'Invoiced' ||
                                form.status === 'Closed'
                            "
                            class="text-muted"
                        >
                            Status tidak dapat diubah karena sudah
                            {{
                                form.status === "Invoiced"
                                    ? "difakturkan"
                                    : "selesai"
                            }}
                        </small>
                    </div>
                </div>
            </div>

            <div class="form-card">
                <div class="card-header">
                    <h2>Item Order</h2>
                    <button
                        type="button"
                        class="btn btn-sm btn-primary"
                        @click="addLine"
                    >
                        <i class="fas fa-plus"></i> Tambah Item
                    </button>
                </div>
                <div class="card-body">
                    <div v-if="form.lines.length === 0" class="empty-lines">
                        <p>
                            Belum ada item yang ditambahkan. Klik "Tambah Item"
                            untuk menambahkan item.
                        </p>
                    </div>

                    <div v-else class="order-lines">
                        <div class="line-headers">
                            <div class="line-header">Item</div>
                            <div class="line-header">Harga Unit</div>
                            <div class="line-header">Jumlah</div>
                            <div class="line-header">UOM</div>
                            <div class="line-header">Diskon</div>
                            <div class="line-header">Pajak</div>
                            <div class="line-header">Subtotal</div>
                            <div class="line-header">Total</div>
                            <div class="line-header"></div>
                        </div>

                        <div
                            v-for="(line, index) in form.lines"
                            :key="index"
                            class="order-line"
                        >
                            <div class="line-item" data-label="Item">
                                <select
                                    v-model="line.item_id"
                                    required
                                    @change="(e) => handleItemChange(e, index)"
                                >
                                    <option value="">-- Pilih Item --</option>
                                    <option
                                        v-for="item in items"
                                        :key="item.item_id"
                                        :value="item.item_id"
                                    >
                                        {{ item.item_code }} - {{ item.name }}
                                    </option>
                                </select>
                            </div>

                            <div class="line-item" data-label="Harga Unit">
                                <input
                                    type="number"
                                    v-model="line.unit_price"
                                    min="0"
                                    step="0.01"
                                    required
                                    @input="calculateLineTotals(index)"
                                />
                            </div>

                            <div class="line-item" data-label="Jumlah">
                                <input
                                    type="number"
                                    v-model="line.quantity"
                                    min="0"
                                    step="0.01"
                                    required
                                    @input="calculateLineTotals(index)"
                                />
                            </div>

                            <div class="line-item" data-label="UOM">
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

                            <div class="line-item" data-label="Diskon">
                                <input
                                    type="number"
                                    v-model="line.discount"
                                    min="0"
                                    step="0.01"
                                    @input="calculateLineTotals(index)"
                                />
                            </div>

                            <div class="line-item" data-label="Pajak">
                                <input
                                    type="number"
                                    v-model="line.tax"
                                    min="0"
                                    step="0.01"
                                    @input="calculateLineTotals(index)"
                                />
                            </div>

                            <div
                                class="line-item subtotal"
                                data-label="Subtotal"
                            >
                                {{ formatCurrency(line.subtotal) }}
                            </div>

                            <div class="line-item total" data-label="Total">
                                {{ formatCurrency(line.total) }}
                            </div>

                            <div class="line-item actions">
                                <button
                                    type="button"
                                    class="btn-icon delete"
                                    title="Hapus Item"
                                    @click="removeLine(index)"
                                >
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>

                        <div class="order-totals">
                            <div class="total-row">
                                <div class="total-label">Subtotal:</div>
                                <div class="total-value">
                                    {{ formatCurrency(calculateSubtotal()) }}
                                </div>
                            </div>
                            <div class="total-row">
                                <div class="total-label">Total Diskon:</div>
                                <div class="total-value">
                                    {{
                                        formatCurrency(calculateTotalDiscount())
                                    }}
                                </div>
                            </div>
                            <div class="total-row">
                                <div class="total-label">Total Pajak:</div>
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
                    Batal
                </button>
                <button
                    type="button"
                    class="btn btn-primary"
                    @click="saveOrder"
                    :disabled="isSubmitting"
                >
                    {{ isSubmitting ? "Menyimpan..." : "Simpan Order" }}
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, computed, onMounted } from "vue";
import { useRouter, useRoute } from "vue-router";
import axios from "axios";

export default {
    name: "SalesOrderForm",
    setup() {
        const router = useRouter();
        const route = useRoute();

        // Form data
        const form = ref({
            so_number: "",
            so_date: new Date().toISOString().substr(0, 10),
            customer_id: "",
            quotation_id: "",
            payment_terms: "",
            delivery_terms: "",
            expected_delivery: "",
            status: "Draft",
            lines: [],
        });

        // Reference data
        const customers = ref([]);
        const items = ref([]);
        const unitOfMeasures = ref([]);

        // UI state
        const isLoading = ref(false);
        const isSubmitting = ref(false);
        const error = ref("");

        // Check if we're in edit mode
        const isEditMode = computed(() => {
            return route.params.id !== undefined;
        });

        // Generate a unique sales order number
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
                const uomResponse = await axios.get("/unit-of-measures");
                unitOfMeasures.value = uomResponse.data.data;
            } catch (err) {
                console.error("Error loading reference data:", err);
                error.value = "Terjadi kesalahan saat memuat data referensi.";
            }
        };

        // Load order data if in edit mode
        const loadOrder = async () => {
            if (!isEditMode.value) {
                // Generate an order number for new orders
                form.value.so_number = generateOrderNumber();
                return;
            }

            isLoading.value = true;
            error.value = "";

            try {
                const response = await axios.get(`/orders/${route.params.id}`);
                const order = response.data.data;

                // Set form data
                form.value = {
                    so_id: order.so_id,
                    so_number: order.so_number,
                    so_date: order.so_date.substr(0, 10),
                    customer_id: order.customer_id,
                    quotation_id: order.quotation_id || "",
                    payment_terms: order.payment_terms || "",
                    delivery_terms: order.delivery_terms || "",
                    expected_delivery: order.expected_delivery
                        ? order.expected_delivery.substr(0, 10)
                        : "",
                    status: order.status,
                    lines: [],
                };

                // Set line items
                if (order.salesOrderLines && order.salesOrderLines.length > 0) {
                    form.value.lines = order.salesOrderLines.map((line) => ({
                        line_id: line.line_id,
                        item_id: line.item_id,
                        unit_price: line.unit_price,
                        quantity: line.quantity,
                        uom_id: line.uom_id,
                        discount: line.discount || 0,
                        tax: line.tax || 0,
                        subtotal: line.subtotal,
                        total: line.total,
                    }));
                }
            } catch (err) {
                console.error("Error loading order:", err);
                error.value = "Terjadi kesalahan saat memuat data order.";
            } finally {
                isLoading.value = false;
            }
        };

        // Event handler for item change
        const handleItemChange = (event, index) => {
            const itemId = form.value.lines[index].item_id;
            if (!itemId) return;

            const selectedItem = items.value.find((i) => i.item_id == itemId);
            if (selectedItem) {
                // Set default UOM if available
                form.value.lines[index].uom_id = selectedItem.uom_id || "";
                calculateLineTotals(index);
            }
        };

        // Line item operations
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

        const removeLine = (index) => {
            form.value.lines.splice(index, 1);
        };

        const calculateLineTotals = (index) => {
            const line = form.value.lines[index];

            // Calculate subtotal (unit_price * quantity)
            line.subtotal =
                parseFloat(line.unit_price) * parseFloat(line.quantity);

            // Calculate total (subtotal - discount + tax)
            line.total = line.subtotal - (line.discount || 0) + (line.tax || 0);
        };

        // Calculate totals
        const calculateSubtotal = () => {
            return form.value.lines.reduce(
                (sum, line) => sum + (line.subtotal || 0),
                0
            );
        };

        const calculateTotalDiscount = () => {
            return form.value.lines.reduce(
                (sum, line) => sum + (line.discount || 0),
                0
            );
        };

        const calculateTotalTax = () => {
            return form.value.lines.reduce(
                (sum, line) => sum + (line.tax || 0),
                0
            );
        };

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

        // Navigation
        const goBack = () => {
            router.push("/sales/orders");
        };

        // Save order
        const saveOrder = async () => {
            // Validate form
            if (
                !form.value.so_number ||
                !form.value.so_date ||
                !form.value.customer_id
            ) {
                error.value = "Harap isi semua field yang wajib diisi.";
                return;
            }

            // Validate line items
            if (form.value.lines.length === 0) {
                error.value = "Order harus memiliki minimal 1 item.";
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
                    error.value = `Item ke-${
                        i + 1
                    } memiliki data yang tidak lengkap.`;
                    return;
                }
            }

            isSubmitting.value = true;
            error.value = "";

            try {
                // Prepare order data
                const orderData = {
                    ...form.value,
                    total_amount: calculateGrandTotal(),
                    tax_amount: calculateTotalTax(),
                    lines: form.value.lines,
                };

                if (isEditMode.value) {
                    // Update existing order
                    await axios.put(`/orders/${form.value.so_id}`, orderData);
                    alert("Order berhasil diperbarui!");
                } else {
                    // Create new order
                    await axios.post("/orders", orderData);
                    alert("Order berhasil dibuat!");
                }

                // Redirect to orders list
                router.push("/sales/orders");
            } catch (err) {
                console.error("Error saving order:", err);

                if (err.response?.data?.errors) {
                    const errors = err.response.data.errors;
                    const firstError = Object.values(errors)[0][0];
                    error.value = firstError;
                } else if (err.response?.data?.message) {
                    error.value = err.response.data.message;
                } else {
                    error.value = "Terjadi kesalahan saat menyimpan order.";
                }
            } finally {
                isSubmitting.value = false;
            }
        };

        // Initialize
        onMounted(() => {
            loadReferenceData();
            loadOrder();
        });

        return {
            form,
            customers,
            items,
            unitOfMeasures,
            isLoading,
            isSubmitting,
            error,
            isEditMode,
            handleItemChange,
            addLine,
            removeLine,
            calculateLineTotals,
            calculateSubtotal,
            calculateTotalDiscount,
            calculateTotalTax,
            calculateGrandTotal,
            formatCurrency,
            goBack,
            saveOrder,
        };
    },
};
</script>

<style scoped>
.order-form {
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

.form-card {
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
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 0.625rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.375rem;
    font-size: 0.875rem;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: 2px solid #2563eb;
    outline-offset: 1px;
}

.form-group input.readonly {
    background-color: #f8fafc;
    cursor: not-allowed;
}

.text-muted {
    color: #64748b;
    font-size: 0.75rem;
    margin-top: 0.25rem;
}

.empty-lines {
    background-color: #f8fafc;
    padding: 2rem;
    border-radius: 0.375rem;
    text-align: center;
    color: #64748b;
    border: 1px dashed #cbd5e1;
}

.order-lines {
    border: 1px solid #e2e8f0;
    border-radius: 0.375rem;
    overflow: hidden;
}

.line-headers {
    display: grid;
    grid-template-columns: 3fr 1fr 1fr 1fr 1fr 1fr 1.5fr 1.5fr 0.5fr;
    gap: 0.5rem;
    background-color: #f8fafc;
    padding: 0.75rem 1rem;
    font-weight: 500;
    color: #475569;
    border-bottom: 1px solid #e2e8f0;
}

.line-header {
    font-size: 0.75rem;
}

.order-line {
    display: grid;
    grid-template-columns: 3fr 1fr 1fr 1fr 1fr 1fr 1.5fr 1.5fr 0.5fr;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #e2e8f0;
    align-items: center;
}

.order-line:last-child {
    border-bottom: none;
}

.line-item input,
.line-item select {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.25rem;
    font-size: 0.875rem;
}

.line-item.subtotal,
.line-item.total {
    font-weight: 500;
    text-align: right;
}

.line-item.total {
    color: #2563eb;
}

.line-item.actions {
    text-align: center;
}

.btn-icon {
    background: none;
    border: none;
    color: #64748b;
    cursor: pointer;
    padding: 0.375rem;
    border-radius: 0.25rem;
}

.btn-icon:hover {
    background-color: #f1f5f9;
}

.btn-icon.delete:hover {
    color: #dc2626;
    background-color: #fee2e2;
}

.order-totals {
    border-top: 1px solid #e2e8f0;
    padding: 1rem;
    background-color: #f8fafc;
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
    color: #475569;
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
    color: #1e293b;
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

@media (max-width: 1024px) {
    .form-row {
        grid-template-columns: 1fr;
        gap: 0;
    }

    .order-line,
    .line-headers {
        grid-template-columns: repeat(8, 1fr) 0.5fr;
        font-size: 0.75rem;
        padding: 0.5rem;
    }
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }

    .order-line,
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
