<!-- src/components/purchasing/ContractForm.vue -->
<template>
    <div class="contract-form">
        <form @submit.prevent="handleSubmit">
            <div class="form-section">
                <h3 class="section-title">Basic Information</h3>

                <div class="form-row">
                    <div class="form-group">
                        <label for="contract_number">Contract Number*</label>
                        <input
                            type="text"
                            id="contract_number"
                            v-model="formData.contract_number"
                            required
                            placeholder="e.g., CONT-2025-001"
                            :disabled="isEditMode"
                        />
                        <div
                            v-if="errors.contract_number"
                            class="error-message"
                        >
                            {{ errors.contract_number }}
                        </div>
                    </div>

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
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="contract_type">Contract Type*</label>
                        <select
                            id="contract_type"
                            v-model="formData.contract_type"
                            required
                        >
                            <option value="">Select Type</option>
                            <option value="purchase">Purchase Agreement</option>
                            <option value="service">Service Agreement</option>
                            <option value="rental">
                                Rental/Lease Agreement
                            </option>
                            <option value="maintenance">
                                Maintenance Agreement
                            </option>
                        </select>
                        <div v-if="errors.contract_type" class="error-message">
                            {{ errors.contract_type }}
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="status">Status</label>
                        <input
                            type="text"
                            id="status"
                            v-model="formData.status"
                            disabled
                        />
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3 class="section-title">Contract Period</h3>

                <div class="form-row">
                    <div class="form-group">
                        <label for="start_date">Start Date*</label>
                        <input
                            type="date"
                            id="start_date"
                            v-model="formData.start_date"
                            required
                        />
                        <div v-if="errors.start_date" class="error-message">
                            {{ errors.start_date }}
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="end_date">End Date*</label>
                        <input
                            type="date"
                            id="end_date"
                            v-model="formData.end_date"
                            required
                            :min="formData.start_date"
                        />
                        <div v-if="errors.end_date" class="error-message">
                            {{ errors.end_date }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3 class="section-title">Contract Details</h3>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea
                        id="description"
                        v-model="formData.description"
                        rows="4"
                        placeholder="Enter contract details, scope, and terms..."
                    ></textarea>
                    <div v-if="errors.description" class="error-message">
                        {{ errors.description }}
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="total_value">Total Value</label>
                        <div class="input-with-prefix">
                            <span class="input-prefix">$</span>
                            <input
                                type="number"
                                id="total_value"
                                v-model="formData.total_value"
                                min="0"
                                step="0.01"
                                placeholder="0.00"
                            />
                        </div>
                        <div v-if="errors.total_value" class="error-message">
                            {{ errors.total_value }}
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="payment_terms">Payment Terms</label>
                        <input
                            type="text"
                            id="payment_terms"
                            v-model="formData.payment_terms"
                            placeholder="e.g., Net 30, Monthly, Quarterly"
                        />
                        <div v-if="errors.payment_terms" class="error-message">
                            {{ errors.payment_terms }}
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="renewal_terms">Renewal Terms</label>
                    <input
                        type="text"
                        id="renewal_terms"
                        v-model="formData.renewal_terms"
                        placeholder="e.g., Automatic renewal, 30-day notice required"
                    />
                    <div v-if="errors.renewal_terms" class="error-message">
                        {{ errors.renewal_terms }}
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
                            ? "Update Contract"
                            : "Create Contract"
                    }}
                </button>
            </div>
        </form>
    </div>
</template>

<script>
import { ref, reactive, watch, onMounted } from "vue";

export default {
    name: "ContractForm",
    props: {
        contract: {
            type: Object,
            default: null,
        },
        vendors: {
            type: Array,
            default: () => [],
        },
        isEditMode: {
            type: Boolean,
            default: false,
        },
        isSubmitting: {
            type: Boolean,
            default: false,
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
            contract_number: "",
            vendor_id: "",
            contract_type: "",
            status: "draft",
            start_date: "",
            end_date: "",
            description: "",
            total_value: "",
            payment_terms: "",
            renewal_terms: "",
        });

        // Form validation errors
        const errors = ref({});

        // Initialize form with contract data if provided
        const initForm = () => {
            if (props.contract) {
                formData.contract_number = props.contract.contract_number || "";
                formData.vendor_id = props.contract.vendor_id || "";
                formData.contract_type = props.contract.contract_type || "";
                formData.status = props.contract.status || "draft";
                formData.start_date = props.contract.start_date
                    ? formatDateForInput(props.contract.start_date)
                    : "";
                formData.end_date = props.contract.end_date
                    ? formatDateForInput(props.contract.end_date)
                    : "";
                formData.description = props.contract.description || "";
                formData.total_value = props.contract.total_value || "";
                formData.payment_terms = props.contract.payment_terms || "";
                formData.renewal_terms = props.contract.renewal_terms || "";
            } else {
                // Set default values for new contract
                formData.status = "draft";
                formData.start_date = "";
                formData.end_date = "";
            }
        };

        // Format date for input field (YYYY-MM-DD)
        const formatDateForInput = (dateString) => {
            if (!dateString) return "";
            const date = new Date(dateString);
            return date.toISOString().split("T")[0];
        };

        // Watch for contract prop changes
        watch(
            () => props.contract,
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

        // Form submission
        const handleSubmit = () => {
            // Basic validation
            const validationErrors = {};

            if (!formData.contract_number) {
                validationErrors.contract_number =
                    "Contract number is required";
            }

            if (!formData.vendor_id) {
                validationErrors.vendor_id = "Vendor is required";
            }

            if (!formData.contract_type) {
                validationErrors.contract_type = "Contract type is required";
            }

            if (!formData.start_date) {
                validationErrors.start_date = "Start date is required";
            }

            if (!formData.end_date) {
                validationErrors.end_date = "End date is required";
            } else if (
                formData.start_date &&
                formData.end_date &&
                new Date(formData.end_date) < new Date(formData.start_date)
            ) {
                validationErrors.end_date = "End date must be after start date";
            }

            // If validation errors exist, update errors and stop submission
            if (Object.keys(validationErrors).length > 0) {
                errors.value = validationErrors;
                return;
            }

            // Clear validation errors
            errors.value = {};

            // Emit submit event with form data
            emit("submit", { ...formData });
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
            handleSubmit,
            cancel,
        };
    },
};
</script>

<style scoped>
.contract-form {
    max-width: 800px;
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

textarea {
    resize: vertical;
    min-height: 100px;
}

.input-with-prefix {
    position: relative;
}

.input-prefix {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    color: #64748b;
}

.input-with-prefix input {
    padding-left: 1.5rem;
}

.error-message {
    margin-top: 0.375rem;
    font-size: 0.75rem;
    color: #dc2626;
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

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>
