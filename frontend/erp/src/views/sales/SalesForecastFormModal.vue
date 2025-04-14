<template>
    <div class="modal">
        <!-- Modal backdrop - area yang gelap di belakang modal -->
        <div class="modal-backdrop" @click="$emit('close')"></div>

        <!-- Modal content - container utama -->
        <div class="modal-content">
            <!-- Header modal -->
            <div class="modal-header">
                <h2>
                    {{
                        isEditMode
                            ? "Edit Sales Forecast"
                            : "Create Sales Forecast"
                    }}
                </h2>
                <button class="close-btn" @click="$emit('close')">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Body modal -->
            <div class="modal-body">
                <form @submit.prevent="submitForm">
                    <!-- Baris form untuk Item dan Customer -->
                    <div class="form-row">
                        <!-- Pilih Item -->
                        <div class="form-group">
                            <label for="item_id">Item*</label>
                            <select
                                id="item_id"
                                v-model="form.item_id"
                                required
                                :class="{ 'is-invalid': errors.item_id }"
                            >
                                <option value="">-- Select Item --</option>
                                <option
                                    v-for="item in items"
                                    :key="item.item_id"
                                    :value="item.item_id"
                                >
                                    {{ item.name }} ({{ item.item_code }})
                                </option>
                            </select>
                            <span v-if="errors.item_id" class="error-message">{{
                                errors.item_id
                            }}</span>
                        </div>

                        <!-- Pilih Customer -->
                        <div class="form-group">
                            <label for="customer_id">Customer*</label>
                            <select
                                id="customer_id"
                                v-model="form.customer_id"
                                required
                                :class="{ 'is-invalid': errors.customer_id }"
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
                            <span
                                v-if="errors.customer_id"
                                class="error-message"
                                >{{ errors.customer_id }}</span
                            >
                        </div>
                    </div>

                    <!-- Baris form untuk Forecast Period dan Quantity -->
                    <div class="form-row">
                        <!-- Input Forecast Period -->
                        <div class="form-group">
                            <label for="forecast_period"
                                >Forecast Period*</label
                            >
                            <input
                                type="date"
                                id="forecast_period"
                                v-model="form.forecast_period"
                                required
                                :class="{
                                    'is-invalid': errors.forecast_period,
                                }"
                            />
                            <span
                                v-if="errors.forecast_period"
                                class="error-message"
                                >{{ errors.forecast_period }}</span
                            >
                            <small class="help-text"
                                >First day of the month is recommended for
                                consistency.</small
                            >
                        </div>

                        <!-- Input Forecast Quantity -->
                        <div class="form-group">
                            <label for="forecast_quantity"
                                >Forecast Quantity*</label
                            >
                            <input
                                type="number"
                                id="forecast_quantity"
                                v-model="form.forecast_quantity"
                                min="0"
                                step="0.01"
                                required
                                :class="{
                                    'is-invalid': errors.forecast_quantity,
                                }"
                            />
                            <span
                                v-if="errors.forecast_quantity"
                                class="error-message"
                                >{{ errors.forecast_quantity }}</span
                            >
                        </div>
                    </div>

                    <!-- Baris form untuk Actual Quantity dan Variance (hanya muncul di mode edit) -->
                    <div class="form-row" v-if="isEditMode">
                        <!-- Input Actual Quantity -->
                        <div class="form-group">
                            <label for="actual_quantity">Actual Quantity</label>
                            <input
                                type="number"
                                id="actual_quantity"
                                v-model="form.actual_quantity"
                                min="0"
                                step="0.01"
                                :class="{
                                    'is-invalid': errors.actual_quantity,
                                }"
                            />
                            <span
                                v-if="errors.actual_quantity"
                                class="error-message"
                                >{{ errors.actual_quantity }}</span
                            >
                            <small class="help-text"
                                >Leave empty if actual quantity is not available
                                yet.</small
                            >
                        </div>

                        <!-- Display Variance -->
                        <div class="form-group">
                            <label>Variance</label>
                            <div
                                class="variance-display"
                                :class="getVarianceClass(calculatedVariance)"
                            >
                                {{ formatNumber(calculatedVariance) }}
                                <i
                                    v-if="calculatedVariance > 0"
                                    class="fas fa-arrow-up"
                                ></i>
                                <i
                                    v-else-if="calculatedVariance < 0"
                                    class="fas fa-arrow-down"
                                ></i>
                            </div>
                            <small class="help-text"
                                >Automatically calculated from forecast and
                                actual quantities.</small
                            >
                        </div>
                    </div>

                    <!-- Tombol-tombol aksi -->
                    <div class="form-actions">
                        <button
                            type="button"
                            class="btn btn-secondary"
                            @click="$emit('close')"
                        >
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            {{
                                isEditMode
                                    ? "Update Forecast"
                                    : "Create Forecast"
                            }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
<script>
import { ref, computed, watch, reactive } from "vue";

export default {
    name: "SalesForecastFormModal",

    // Props yang diterima dari parent component
    props: {
        // Mode edit atau create
        isEditMode: {
            type: Boolean,
            default: false,
        },
        // Data forecast untuk edit atau default values
        forecastData: {
            type: Object,
            required: true,
        },
        // List customers dari API
        customers: {
            type: Array,
            default: () => [],
        },
        // List items dari API
        items: {
            type: Array,
            default: () => [],
        },
    },

    // Event yang dipancarkan ke parent component
    emits: ["close", "save"],

    // Setup function - Vue 3 Composition API
    setup(props, { emit }) {
        // Data form yang reaktif
        const form = reactive({
            forecast_id: props.forecastData.forecast_id,
            item_id: props.forecastData.item_id || "",
            customer_id: props.forecastData.customer_id || "",
            forecast_period: props.forecastData.forecast_period || "",
            forecast_quantity: props.forecastData.forecast_quantity || 0,
            actual_quantity: props.forecastData.actual_quantity || null,
            variance: props.forecastData.variance || null,
        });

        // Object untuk menyimpan pesan error
        const errors = ref({});

        // Watch perubahan pada prop forecastData
        watch(
            () => props.forecastData,
            (newData) => {
                form.forecast_id = newData.forecast_id;
                form.item_id = newData.item_id || "";
                form.customer_id = newData.customer_id || "";
                form.forecast_period = newData.forecast_period || "";
                form.forecast_quantity = newData.forecast_quantity || 0;
                form.actual_quantity = newData.actual_quantity || null;
                form.variance = newData.variance || null;
            },
            { deep: true }
        );

        // Computed property untuk menghitung variance secara otomatis
        const calculatedVariance = computed(() => {
            if (form.actual_quantity === null || form.actual_quantity === "")
                return null;
            return (
                parseFloat(form.actual_quantity) -
                parseFloat(form.forecast_quantity)
            );
        });

        // Fungsi validasi form
        const validateForm = () => {
            errors.value = {};

            if (!form.item_id) {
                errors.value.item_id = "Item is required";
            }

            if (!form.customer_id) {
                errors.value.customer_id = "Customer is required";
            }

            if (!form.forecast_period) {
                errors.value.forecast_period = "Forecast period is required";
            }

            if (!form.forecast_quantity && form.forecast_quantity !== 0) {
                errors.value.forecast_quantity =
                    "Forecast quantity is required";
            } else if (form.forecast_quantity < 0) {
                errors.value.forecast_quantity =
                    "Forecast quantity cannot be negative";
            }

            if (
                form.actual_quantity !== null &&
                form.actual_quantity !== "" &&
                form.actual_quantity < 0
            ) {
                errors.value.actual_quantity =
                    "Actual quantity cannot be negative";
            }

            return Object.keys(errors.value).length === 0;
        };

        // Handle submit form
        const submitForm = () => {
            if (validateForm()) {
                // Calculate variance if both quantities are available
                if (
                    form.actual_quantity !== null &&
                    form.actual_quantity !== ""
                ) {
                    form.variance = calculatedVariance.value;
                }

                emit("save", { ...form });
            }
        };

        // Fungsi untuk mendapatkan class CSS berdasarkan nilai variance
        const getVarianceClass = (variance) => {
            if (variance === null || variance === undefined) return "";

            if (variance > 0) return "positive-variance";
            if (variance < 0) return "negative-variance";
            return "";
        };

        // Fungsi untuk memformat angka
        const formatNumber = (value) => {
            if (value === null || value === undefined) return "--";

            return new Intl.NumberFormat("en-US", {
                maximumFractionDigits: 2,
                minimumFractionDigits: 0,
            }).format(value);
        };

        // Return semua yang perlu diakses dari template
        return {
            form,
            errors,
            calculatedVariance,
            submitForm,
            getVarianceClass,
            formatNumber,
        };
    },
};
</script>
<style scoped>
/* Style for modal container */
.modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 50;
    display: flex;
    justify-content: center;
    align-items: center;
}

/* Dark overlay behind the modal */
.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 50;
}

/* Main modal container */
.modal-content {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    width: 100%;
    max-width: 650px;
    z-index: 60;
    overflow: hidden;
}

/* Modal header styling */
.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--gray-200);
}

.modal-header h2 {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
    color: var(--gray-800);
}

/* Close button styling */
.close-btn {
    background: none;
    border: none;
    color: var(--gray-500);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.25rem;
    border-radius: 0.25rem;
}

.close-btn:hover {
    background-color: var(--gray-100);
    color: var(--gray-800);
}

/* Modal body styling */
.modal-body {
    padding: 1.5rem;
}

/* Form layout using CSS grid */
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
}

.form-group {
    margin-bottom: 1rem;
}

/* Form controls styling */
.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: var(--gray-700);
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 0.625rem;
    border: 1px solid var(--gray-300);
    border-radius: 0.375rem;
    font-size: 0.875rem;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

/* Invalid form control styling */
.form-group .is-invalid {
    border-color: var(--danger-color);
}

/* Error message styling */
.error-message {
    display: block;
    margin-top: 0.25rem;
    font-size: 0.75rem;
    color: var(--danger-color);
}

/* Help text styling */
.help-text {
    display: block;
    margin-top: 0.25rem;
    font-size: 0.75rem;
    color: var(--gray-500);
}

/* Variance display styling */
.variance-display {
    padding: 0.625rem;
    border: 1px solid var(--gray-300);
    border-radius: 0.375rem;
    font-size: 0.875rem;
    background-color: var(--gray-50);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Positive and negative variance colors */
.positive-variance {
    color: var(--success-color);
}

.negative-variance {
    color: var(--danger-color);
}

/* Form action buttons container */
.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 1.5rem;
}

/* Responsive design for mobile devices */
@media (max-width: 640px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>
