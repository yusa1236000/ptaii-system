<!-- src/components/sales/SalesForecastGenerateModal.vue -->
<template>
    <div class="modal">
        <div class="modal-backdrop" @click="$emit('close')"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h2>Generate Sales Forecasts</h2>
                <button class="close-btn" @click="$emit('close')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="info-alert">
                    <i class="fas fa-info-circle"></i>
                    <div>
                        <p><strong>About Forecast Generation</strong></p>
                        <p>
                            This tool analyzes historical sales data to predict
                            future demand. Select a method, date range, and
                            optional filters to generate forecasts.
                        </p>
                    </div>
                </div>

                <form @submit.prevent="submitForm">
                    <div class="form-section">
                        <h3>Forecast Period</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="start_period">Start Period*</label>
                                <input
                                    type="date"
                                    id="start_period"
                                    v-model="form.start_period"
                                    required
                                    :class="{
                                        'is-invalid': errors.start_period,
                                    }"
                                />
                                <span
                                    v-if="errors.start_period"
                                    class="error-message"
                                    >{{ errors.start_period }}</span
                                >
                            </div>

                            <div class="form-group">
                                <label for="end_period">End Period*</label>
                                <input
                                    type="date"
                                    id="end_period"
                                    v-model="form.end_period"
                                    required
                                    :class="{ 'is-invalid': errors.end_period }"
                                />
                                <span
                                    v-if="errors.end_period"
                                    class="error-message"
                                    >{{ errors.end_period }}</span
                                >
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Forecasting Method</h3>
                        <div class="form-group">
                            <div class="method-options">
                                <div class="method-option">
                                    <input
                                        type="radio"
                                        id="method-average"
                                        name="method"
                                        value="average"
                                        v-model="form.method"
                                        required
                                    />
                                    <label for="method-average">
                                        <div class="method-title">
                                            Simple Average
                                        </div>
                                        <div class="method-description">
                                            Uses a straight average of
                                            historical sales data
                                        </div>
                                    </label>
                                </div>

                                <div class="method-option">
                                    <input
                                        type="radio"
                                        id="method-weighted"
                                        name="method"
                                        value="weighted"
                                        v-model="form.method"
                                        required
                                    />
                                    <label for="method-weighted">
                                        <div class="method-title">
                                            Weighted Average
                                        </div>
                                        <div class="method-description">
                                            Gives more importance to recent
                                            sales data
                                        </div>
                                    </label>
                                </div>

                                <div class="method-option">
                                    <input
                                        type="radio"
                                        id="method-trend"
                                        name="method"
                                        value="trend"
                                        v-model="form.method"
                                        required
                                    />
                                    <label for="method-trend">
                                        <div class="method-title">
                                            Trend Analysis
                                        </div>
                                        <div class="method-description">
                                            Uses linear regression to identify
                                            and extend trends
                                        </div>
                                    </label>
                                </div>
                            </div>
                            <span v-if="errors.method" class="error-message">{{
                                errors.method
                            }}</span>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Optional Filters</h3>
                        <p class="section-help">
                            Limit forecast generation to specific customer or
                            item. Leave empty to generate for all.
                        </p>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="customer_id">Customer</label>
                                <select
                                    id="customer_id"
                                    v-model="form.customer_id"
                                    :class="{
                                        'is-invalid': errors.customer_id,
                                    }"
                                >
                                    <option value="">All Customers</option>
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

                            <div class="form-group">
                                <label for="item_id">Item</label>
                                <select
                                    id="item_id"
                                    v-model="form.item_id"
                                    :class="{ 'is-invalid': errors.item_id }"
                                >
                                    <option value="">All Items</option>
                                    <option
                                        v-for="item in items"
                                        :key="item.item_id"
                                        :value="item.item_id"
                                    >
                                        {{ item.name }} ({{ item.item_code }})
                                    </option>
                                </select>
                                <span
                                    v-if="errors.item_id"
                                    class="error-message"
                                    >{{ errors.item_id }}</span
                                >
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button
                            type="button"
                            class="btn btn-secondary"
                            @click="$emit('close')"
                        >
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            Generate Forecasts
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, reactive } from "vue";

export default {
    name: "SalesForecastGenerateModal",
    props: {
        customers: {
            type: Array,
            default: () => [],
        },
        items: {
            type: Array,
            default: () => [],
        },
    },
    emits: ["close", "generate"],
    setup(props, { emit }) {
        // Create default start and end periods
        const now = new Date();
        const startOfNextMonth = new Date(
            now.getFullYear(),
            now.getMonth() + 1,
            1
        );
        const endOfThirdMonth = new Date(
            now.getFullYear(),
            now.getMonth() + 3,
            0
        );

        // Format dates as YYYY-MM-DD
        const formatDate = (date) => {
            return date.toISOString().substr(0, 10);
        };

        const form = reactive({
            start_period: formatDate(startOfNextMonth),
            end_period: formatDate(endOfThirdMonth),
            method: "weighted",
            customer_id: "",
            item_id: "",
        });

        const errors = ref({});

        // Validate form
        const validateForm = () => {
            errors.value = {};

            if (!form.start_period) {
                errors.value.start_period = "Start period is required";
            }

            if (!form.end_period) {
                errors.value.end_period = "End period is required";
            }

            if (form.start_period && form.end_period) {
                const start = new Date(form.start_period);
                const end = new Date(form.end_period);

                if (start >= end) {
                    errors.value.end_period =
                        "End period must be after start period";
                }
            }

            if (!form.method) {
                errors.value.method = "Forecasting method is required";
            }

            return Object.keys(errors.value).length === 0;
        };

        const submitForm = () => {
            if (validateForm()) {
                // Prepare the parameters for the API
                const params = {
                    start_period: form.start_period,
                    end_period: form.end_period,
                    method: form.method,
                };

                // Add optional filters if selected
                if (form.customer_id) {
                    params.customer_id = form.customer_id;
                }

                if (form.item_id) {
                    params.item_id = form.item_id;
                }

                emit("generate", params);
            }
        };

        return {
            form,
            errors,
            submitForm,
        };
    },
};
</script>

<style scoped>
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

.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 50;
}

.modal-content {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    width: 100%;
    max-width: 700px;
    max-height: 90vh;
    overflow-y: auto;
    z-index: 60;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--gray-200);
    position: sticky;
    top: 0;
    background-color: white;
    z-index: 1;
}

.modal-header h2 {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
    color: var(--gray-800);
}

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

.modal-body {
    padding: 1.5rem;
}

.info-alert {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    background-color: #f0f9ff;
    border-left: 4px solid #0ea5e9;
    border-radius: 0.375rem;
    margin-bottom: 1.5rem;
}

.info-alert i {
    font-size: 1.5rem;
    color: #0ea5e9;
    flex-shrink: 0;
}

.info-alert p {
    margin: 0;
    font-size: 0.875rem;
    color: var(--gray-700);
}

.info-alert p:first-child {
    margin-bottom: 0.25rem;
}

.form-section {
    margin-bottom: 2rem;
    border: 1px solid var(--gray-200);
    border-radius: 0.5rem;
    padding: 1.5rem;
}

.form-section h3 {
    font-size: 1rem;
    font-weight: 600;
    margin: 0 0 1rem 0;
    color: var(--gray-800);
}

.section-help {
    font-size: 0.875rem;
    color: var(--gray-500);
    margin-top: -0.5rem;
    margin-bottom: 1rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.form-group {
    margin-bottom: 1rem;
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

.form-group input[type="date"],
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

.form-group .is-invalid {
    border-color: var(--danger-color);
}

.error-message {
    display: block;
    margin-top: 0.25rem;
    font-size: 0.75rem;
    color: var(--danger-color);
}

.method-options {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.method-option {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
}

.method-option input[type="radio"] {
    margin-top: 0.5rem;
}

.method-option label {
    flex: 1;
    cursor: pointer;
    margin: 0;
    padding: 0.75rem;
    border: 1px solid var(--gray-200);
    border-radius: 0.375rem;
    transition: background-color 0.2s, border-color 0.2s;
}

.method-option input[type="radio"]:checked + label {
    background-color: var(--primary-bg);
    border-color: var(--primary-color);
}

.method-title {
    font-weight: 600;
    color: var(--gray-800);
    margin-bottom: 0.25rem;
}

.method-description {
    font-size: 0.875rem;
    color: var(--gray-600);
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 1.5rem;
}

@media (max-width: 640px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>
