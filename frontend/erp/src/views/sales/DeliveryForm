<!-- src/views/sales/DeliveryForm.vue -->
<template>
    <div class="delivery-form">
        <div class="page-header">
            <h1>
                {{ isEditMode ? "Edit Pengiriman" : "Buat Pengiriman Baru" }}
            </h1>
            <div class="page-actions">
                <button class="btn btn-secondary" @click="goBack">
                    <i class="fas fa-arrow-left"></i> Kembali
                </button>
                <button
                    class="btn btn-primary"
                    @click="saveDelivery"
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

        <div v-if="isLoading" class="loading-container">
            <i class="fas fa-spinner fa-spin"></i> Memuat data...
        </div>

        <div v-else class="form-container">
            <!-- Sales Order Selection -->
            <div v-if="!isEditMode && !selectedOrder" class="form-card">
                <div class="card-header">
                    <h2>Pilih Sales Order</h2>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="order_id">Sales Order*</label>
                        <select
                            id="order_id"
                            v-model="selectedOrderId"
                            @change="loadOrderDetails"
                            required
                        >
                            <option value="">-- Pilih Sales Order --</option>
                            <option
                                v-for="order in availableOrders"
                                :key="order.so_id"
                                :value="order.so_id"
                            >
                                {{ order.so_number }} -
                                {{ order.customer.name }}
                            </option>
                        </select>
                        <small class="text-muted"
                            >Pilih Sales Order untuk membuat pengiriman</small
                        >
                    </div>
                </div>
            </div>

            <!-- Delivery Information Form -->
            <div v-if="isEditMode || selectedOrder" class="form-card">
                <div class="card-header">
                    <h2>Informasi Pengiriman</h2>
                    <div v-if="selectedOrder" class="order-info">
                        <span class="order-number">{{
                            selectedOrder.so_number
                        }}</span>
                        <span class="order-customer">{{
                            selectedOrder.customer.name
                        }}</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="delivery_number"
                                >Nomor Pengiriman*</label
                            >
                            <input
                                type="text"
                                id="delivery_number"
                                v-model="form.delivery_number"
                                required
                                :readonly="isEditMode"
                                :class="{ readonly: isEditMode }"
                            />
                            <small v-if="isEditMode" class="text-muted"
                                >Nomor pengiriman tidak dapat diubah</small
                            >
                        </div>

                        <div class="form-group">
                            <label for="delivery_date"
                                >Tanggal Pengiriman*</label
                            >
                            <input
                                type="date"
                                id="delivery_date"
                                v-model="form.delivery_date"
                                required
                            />
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="expected_arrival_date"
                                >Estimasi Kedatangan*</label
                            >
                            <input
                                type="date"
                                id="expected_arrival_date"
                                v-model="form.expected_arrival_date"
                                required
                            />
                        </div>

                        <div class="form-group">
                            <label for="carrier">Kurir/Ekspedisi</label>
                            <input
                                type="text"
                                id="carrier"
                                v-model="form.carrier"
                                placeholder="Nama kurir atau ekspedisi"
                            />
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="tracking_number">Nomor Resi</label>
                            <input
                                type="text"
                                id="tracking_number"
                                v-model="form.tracking_number"
                                placeholder="Nomor resi pengiriman"
                            />
                        </div>

                        <div class="form-group">
                            <label for="delivery_status"
                                >Status Pengiriman*</label
                            >
                            <select
                                id="delivery_status"
                                v-model="form.delivery_status"
                                required
                            >
                                <option value="Pending">Menunggu</option>
                                <option value="In Transit">
                                    Dalam Perjalanan
                                </option>
                                <option value="Delivered">Terkirim</option>
                                <option value="Returned">Dikembalikan</option>
                                <option value="Cancelled">Dibatalkan</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="shipping_address">Alamat Pengiriman</label>
                        <textarea
                            id="shipping_address"
                            v-model="form.shipping_address"
                            rows="3"
                            :placeholder="
                                customerAddress || 'Alamat pengiriman lengkap'
                            "
                        ></textarea>
                        <small class="text-muted" v-if="customerAddress">
                            Biarkan kosong untuk menggunakan alamat pelanggan
                            default
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="notes">Catatan Pengiriman</label>
                        <textarea
                            id="notes"
                            v-model="form.notes"
                            rows="3"
                            placeholder="Catatan atau instruksi khusus untuk pengiriman ini"
                        ></textarea>
                    </div>
                </div>
            </div>

            <!-- Delivery Items -->
            <div
                v-if="form.delivery_items && form.delivery_items.length > 0"
                class="form-card"
            >
                <div class="card-header">
                    <h2>Item Pengiriman</h2>
                </div>
                <div class="card-body">
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Deskripsi</th>
                                <th>Jumlah Order</th>
                                <th>Jumlah Tersedia</th>
                                <th>Jumlah Kirim*</th>
                                <th>Satuan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(item, index) in form.delivery_items"
                                :key="index"
                                :class="{
                                    'insufficient-stock':
                                        item.quantity > item.available_qty,
                                }"
                            >
                                <td>
                                    <div class="item-name">
                                        {{ item.item_name }}
                                    </div>
                                    <div class="item-code">
                                        {{ item.item_code }}
                                    </div>
                                </td>
                                <td>{{ item.description || "-" }}</td>
                                <td>{{ item.order_qty }}</td>
                                <td>
                                    <span
                                        :class="{
                                            'text-danger':
                                                item.available_qty <
                                                item.order_qty,
                                        }"
                                    >
                                        {{ item.available_qty }}
                                    </span>
                                </td>
                                <td>
                                    <input
                                        type="number"
                                        v-model.number="item.quantity"
                                        min="0"
                                        :max="item.order_qty"
                                        step="1"
                                        required
                                        class="quantity-input"
                                    />
                                </td>
                                <td>{{ item.uom_symbol }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-secondary" @click="goBack">
                    Batal
                </button>
                <button
                    type="button"
                    class="btn btn-primary"
                    @click="saveDelivery"
                    :disabled="isSubmitting"
                >
                    {{ isSubmitting ? "Menyimpan..." : "Simpan Pengiriman" }}
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, computed, onMounted, reactive } from "vue";
import { useRouter, useRoute } from "vue-router";
import DeliveryService from "@/services/DeliveryService";

export default {
    name: "DeliveryForm",
    setup() {
        const router = useRouter();
        const route = useRoute();

        // Form state
        const isEditMode = computed(() => route.params.id !== undefined);
        const isLoading = ref(true);
        const isSubmitting = ref(false);
        const error = ref("");

        // Order selection
        const availableOrders = ref([]);
        const selectedOrderId = ref("");
        const selectedOrder = ref(null);

        // Form data
        const form = reactive({
            delivery_number: "",
            order_id: "",
            delivery_date: new Date().toISOString().substr(0, 10),
            expected_arrival_date: "",
            carrier: "",
            tracking_number: "",
            delivery_status: "Pending",
            shipping_address: "",
            notes: "",
            delivery_items: [],
        });

        // Customer address for convenience
        const customerAddress = computed(() => {
            return selectedOrder.value?.customer?.address || "";
        });

        // Load available orders for delivery
        const loadAvailableOrders = async () => {
            try {
                // In a real app, this should have proper API endpoint
                // For now, we'll simulate with mock data
                const response = await fetch(
                    "/api/orders?status=Open&sort_by=so_date&sort_order=desc"
                );
                const data = await response.json();
                availableOrders.value = data.data || [];
            } catch (error) {
                console.error("Error loading available orders:", error);
                // For demonstration, use dummy data
                availableOrders.value = [
                    {
                        so_id: 1,
                        so_number: "SO-2023-0001",
                        customer: {
                            name: "PT Abadi Jaya",
                            address: "Jl. Industri No. 123, Jakarta Utara",
                        },
                    },
                    {
                        so_id: 2,
                        so_number: "SO-2023-0002",
                        customer: {
                            name: "CV Sejahtera Bersama",
                            address: "Jl. Merdeka No. 45, Bandung",
                        },
                    },
                    {
                        so_id: 3,
                        so_number: "SO-2023-0003",
                        customer: {
                            name: "PT Makmur Sentosa",
                            address: "Jl. Raya Bekasi Km 25, Bekasi",
                        },
                    },
                ];
            } finally {
                isLoading.value = false;
            }
        };

        // Load order details when an order is selected
        const loadOrderDetails = async () => {
            if (!selectedOrderId.value) {
                selectedOrder.value = null;
                form.delivery_items = [];
                return;
            }

            isLoading.value = true;
            try {
                // In a real app, this should fetch from API
                // For now, simulate with mock data
                const orderDetails = availableOrders.value.find(
                    (o) => o.so_id === selectedOrderId.value
                );

                if (orderDetails) {
                    selectedOrder.value = orderDetails;

                    // Set order ID in form
                    form.order_id = selectedOrderId.value;

                    // Generate delivery number
                    form.delivery_number = `DO-${new Date().getFullYear()}-${String(
                        Math.floor(Math.random() * 1000)
                    ).padStart(4, "0")}`;

                    // Set expected arrival date (default: 3 days from delivery date)
                    const arrivalDate = new Date(form.delivery_date);
                    arrivalDate.setDate(arrivalDate.getDate() + 3);
                    form.expected_arrival_date = arrivalDate
                        .toISOString()
                        .substr(0, 10);

                    // Load items for this order
                    // In a real app, fetch from API
                    form.delivery_items = [
                        {
                            item_id: 101,
                            item_name: "Laptop Model X",
                            item_code: "LT-001",
                            description: "Laptop dengan spesifikasi tinggi",
                            order_qty: 5,
                            available_qty: 5,
                            quantity: 5,
                            uom_id: 1,
                            uom_symbol: "Unit",
                        },
                        {
                            item_id: 102,
                            item_name: "Smartphone Y Pro",
                            item_code: "SP-002",
                            description: "Smartphone dengan kamera 108MP",
                            order_qty: 10,
                            available_qty: 8,
                            quantity: 8,
                            uom_id: 1,
                            uom_symbol: "Unit",
                        },
                        {
                            item_id: 103,
                            item_name: "Wireless Earbuds",
                            item_code: "ACC-003",
                            description: "Earbuds dengan noise cancellation",
                            order_qty: 20,
                            available_qty: 20,
                            quantity: 20,
                            uom_id: 1,
                            uom_symbol: "Unit",
                        },
                    ];
                }
            } catch (error) {
                console.error("Error loading order details:", error);
                error.value = "Gagal memuat detail pesanan. Silakan coba lagi.";
                selectedOrder.value = null;
            } finally {
                isLoading.value = false;
            }
        };

        // Load existing delivery for edit mode
        const loadDelivery = async () => {
            if (!isEditMode.value) return;

            isLoading.value = true;
            try {
                const response = await DeliveryService.getDeliveryById(
                    route.params.id
                );
                const delivery = response.data;

                // Update form values
                form.delivery_number = delivery.delivery_number;
                form.order_id = delivery.order_id;
                form.delivery_date = delivery.delivery_date.substr(0, 10);
                form.expected_arrival_date =
                    delivery.expected_arrival_date.substr(0, 10);
                form.carrier = delivery.carrier || "";
                form.tracking_number = delivery.tracking_number || "";
                form.delivery_status = delivery.delivery_status;
                form.shipping_address = delivery.shipping_address || "";
                form.notes = delivery.notes || "";

                // Load order details
                selectedOrderId.value = delivery.order_id;
                selectedOrder.value = delivery.order;

                // Load delivery items
                if (
                    delivery.deliveryItems &&
                    delivery.deliveryItems.length > 0
                ) {
                    form.delivery_items = delivery.deliveryItems.map(
                        (item) => ({
                            delivery_item_id: item.delivery_item_id,
                            item_id: item.item_id,
                            item_name: item.item.name,
                            item_code: item.item.item_code,
                            description: item.item.description || "",
                            order_qty: item.so_line_quantity || item.quantity,
                            available_qty: item.available_qty || item.quantity,
                            quantity: item.quantity,
                            uom_id: item.uom_id,
                            uom_symbol: item.unitOfMeasure?.symbol || "",
                        })
                    );
                }
            } catch (error) {
                console.error("Error loading delivery:", error);
                error.value =
                    "Gagal memuat data pengiriman. Silakan coba lagi.";
            } finally {
                isLoading.value = false;
            }
        };

        // Validate form before submission
        const validateForm = () => {
            error.value = "";

            if (!form.delivery_number) {
                error.value = "Nomor pengiriman harus diisi";
                return false;
            }

            if (!form.delivery_date) {
                error.value = "Tanggal pengiriman harus diisi";
                return false;
            }

            if (!form.expected_arrival_date) {
                error.value = "Estimasi kedatangan harus diisi";
                return false;
            }

            if (!form.order_id) {
                error.value = "Sales order harus dipilih";
                return false;
            }

            if (!form.delivery_status) {
                error.value = "Status pengiriman harus dipilih";
                return false;
            }

            // Validate items
            if (!form.delivery_items || form.delivery_items.length === 0) {
                error.value = "Tidak ada item untuk dikirim";
                return false;
            }

            // Check if at least one item has quantity > 0
            const hasItemToDeliver = form.delivery_items.some(
                (item) => item.quantity > 0
            );
            if (!hasItemToDeliver) {
                error.value =
                    "Minimal satu item harus memiliki jumlah pengiriman > 0";
                return false;
            }

            // Check for items with insufficient stock
            const insufficientStock = form.delivery_items.some(
                (item) => item.quantity > item.available_qty
            );
            if (insufficientStock) {
                error.value =
                    "Beberapa item memiliki jumlah melebihi stok tersedia";
                return false;
            }

            return true;
        };

        // Save delivery
        const saveDelivery = async () => {
            if (!validateForm()) return;

            isSubmitting.value = true;

            try {
                // Prepare data for API
                const deliveryData = {
                    delivery_number: form.delivery_number,
                    order_id: form.order_id,
                    delivery_date: form.delivery_date,
                    expected_arrival_date: form.expected_arrival_date,
                    carrier: form.carrier,
                    tracking_number: form.tracking_number,
                    delivery_status: form.delivery_status,
                    shipping_address: form.shipping_address,
                    notes: form.notes,
                    items: form.delivery_items.map((item) => ({
                        item_id: item.item_id,
                        quantity: item.quantity,
                        uom_id: item.uom_id,
                        delivery_item_id: item.delivery_item_id,
                    })),
                };

                // Create or update delivery
                if (isEditMode.value) {
                    await DeliveryService.updateDelivery(
                        route.params.id,
                        deliveryData
                    );
                    alert("Pengiriman berhasil diperbarui!");
                } else {
                    await DeliveryService.createDelivery(deliveryData);
                    alert("Pengiriman baru berhasil dibuat!");
                }

                // Redirect to delivery list
                router.push("/sales/deliveries");
            } catch (err) {
                console.error("Error saving delivery:", err);

                if (err.response && err.response.data) {
                    if (err.response.data.message) {
                        error.value = err.response.data.message;
                    } else if (err.response.data.errors) {
                        const firstError = Object.values(
                            err.response.data.errors
                        )[0];
                        error.value = Array.isArray(firstError)
                            ? firstError[0]
                            : firstError;
                    } else {
                        error.value =
                            "Terjadi kesalahan saat menyimpan pengiriman";
                    }
                } else {
                    error.value = "Terjadi kesalahan saat menyimpan pengiriman";
                }
            } finally {
                isSubmitting.value = false;
            }
        };

        // Navigation
        const goBack = () => {
            router.push("/sales/deliveries");
        };

        onMounted(() => {
            if (isEditMode.value) {
                loadDelivery();
            } else {
                loadAvailableOrders();
            }
        });

        return {
            isEditMode,
            isLoading,
            isSubmitting,
            error,
            availableOrders,
            selectedOrderId,
            selectedOrder,
            form,
            customerAddress,
            loadOrderDetails,
            saveDelivery,
            goBack,
        };
    },
};
</script>

<style scoped>
.delivery-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
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
    border: 1px solid #fecaca;
}

.loading-container {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 3rem 0;
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.loading-container i {
    font-size: 2rem;
    color: var(--primary-color);
    margin-right: 1rem;
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
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    background-color: var(--gray-50);
    border-bottom: 1px solid var(--gray-200);
}

.card-header h2 {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0;
    color: var(--gray-800);
}

.order-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.order-number {
    font-weight: 600;
    color: var(--primary-color);
}

.order-customer {
    color: var(--gray-600);
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
    border: 1px solid var(--gray-300);
    border-radius: 0.375rem;
    font-size: 0.875rem;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-group input.readonly {
    background-color: var(--gray-100);
    cursor: not-allowed;
}

.form-group textarea {
    resize: vertical;
    min-height: 80px;
}

.text-muted {
    color: var(--gray-500);
    font-size: 0.75rem;
    margin-top: 0.25rem;
}

.items-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
}

.items-table th {
    text-align: left;
    padding: 0.75rem 1rem;
    background-color: var(--gray-50);
    border-bottom: 1px solid var(--gray-200);
    font-weight: 500;
    color: var(--gray-600);
}

.items-table td {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--gray-100);
    color: var(--gray-800);
}

.insufficient-stock {
    background-color: var(--danger-bg);
}

.item-name {
    font-weight: 500;
    color: var(--gray-800);
}

.item-code {
    font-size: 0.75rem;
    color: var(--gray-500);
    margin-top: 0.25rem;
}

.quantity-input {
    width: 5rem !important;
    text-align: center;
}

.text-danger {
    color: var(--danger-color);
    font-weight: 500;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 1rem;
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }

    .form-row {
        grid-template-columns: 1fr;
        gap: 0;
    }

    .items-table {
        display: block;
        overflow-x: auto;
    }
}
</style>
