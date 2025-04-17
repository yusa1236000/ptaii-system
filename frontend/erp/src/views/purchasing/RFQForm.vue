<!-- src/views/purchasing/RFQForm.vue -->
<template>
    <div class="rfq-form-container">
        <div class="page-header">
            <div class="header-left">
                <router-link
                    :to="
                        isEditMode
                            ? `/purchasing/rfqs/${id}`
                            : '/purchasing/rfqs'
                    "
                    class="back-link"
                >
                    <i class="fas fa-arrow-left"></i>
                    {{ isEditMode ? "Back to RFQ Details" : "Back to RFQs" }}
                </router-link>
                <h1>
                    {{
                        isEditMode
                            ? "Edit Request for Quotation"
                            : "Create Request for Quotation"
                    }}
                </h1>
            </div>
        </div>

        <div v-if="isLoading" class="loading-container">
            <div class="loading-spinner">
                <i class="fas fa-spinner fa-spin"></i>
            </div>
            <p>Loading data...</p>
        </div>

        <div v-else class="form-wrapper">
            <form @submit.prevent="submitForm" class="rfq-form">
                <!-- RFQ Information Card -->
                <div class="form-card">
                    <div class="card-header">
                        <h2 class="card-title">RFQ Information</h2>
                    </div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="rfq_date">RFQ Date*</label>
                                <input
                                    type="date"
                                    id="rfq_date"
                                    v-model="formData.rfq_date"
                                    required
                                    :class="{ 'is-invalid': errors.rfq_date }"
                                />
                                <div
                                    v-if="errors.rfq_date"
                                    class="error-message"
                                >
                                    {{ errors.rfq_date }}
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="validity_date">Valid Until*</label>
                                <input
                                    type="date"
                                    id="validity_date"
                                    v-model="formData.validity_date"
                                    required
                                    :class="{
                                        'is-invalid': errors.validity_date,
                                    }"
                                />
                                <div
                                    v-if="errors.validity_date"
                                    class="error-message"
                                >
                                    {{ errors.validity_date }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RFQ Items Card -->
                <div class="form-card">
                    <div class="card-header">
                        <h2 class="card-title">Requested Items</h2>
                        <button
                            type="button"
                            class="btn btn-sm btn-primary"
                            @click="addLine"
                        >
                            <i class="fas fa-plus"></i> Add Item
                        </button>
                    </div>
                    <div class="card-body">
                        <div
                            v-if="formData.lines.length === 0"
                            class="empty-state"
                        >
                            <div class="empty-icon">
                                <i class="fas fa-cart-plus"></i>
                            </div>
                            <h3>No Items Added</h3>
                            <p>
                                Please add items to this RFQ using the "Add
                                Item" button.
                            </p>
                        </div>

                        <div v-else class="line-items-container">
                            <div
                                v-for="(line, index) in formData.lines"
                                :key="index"
                                class="line-item"
                            >
                                <div class="line-header">
                                    <h3 class="line-title">
                                        Item #{{ index + 1 }}
                                    </h3>
                                    <button
                                        type="button"
                                        class="btn-icon btn-danger"
                                        @click="removeLine(index)"
                                    >
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>

                                <div class="form-row">
                                    <div class="form-group form-group-lg">
                                        <label :for="`item_id_${index}`"
                                            >Item*</label
                                        >
                                        <select
                                            :id="`item_id_${index}`"
                                            v-model="line.item_id"
                                            required
                                            :class="{
                                                'is-invalid': getLineError(
                                                    index,
                                                    'item_id'
                                                ),
                                            }"
                                        >
                                            <option value="">
                                                Select Item
                                            </option>
                                            <option
                                                v-for="item in items"
                                                :key="item.item_id"
                                                :value="item.item_id"
                                            >
                                                {{ item.item_code }} -
                                                {{ item.name }}
                                            </option>
                                        </select>
                                        <div
                                            v-if="
                                                getLineError(index, 'item_id')
                                            "
                                            class="error-message"
                                        >
                                            {{ getLineError(index, "item_id") }}
                                        </div>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label :for="`quantity_${index}`"
                                            >Quantity*</label
                                        >
                                        <input
                                            type="number"
                                            :id="`quantity_${index}`"
                                            v-model.number="line.quantity"
                                            required
                                            min="1"
                                            step="any"
                                            :class="{
                                                'is-invalid': getLineError(
                                                    index,
                                                    'quantity'
                                                ),
                                            }"
                                        />
                                        <div
                                            v-if="
                                                getLineError(index, 'quantity')
                                            "
                                            class="error-message"
                                        >
                                            {{
                                                getLineError(index, "quantity")
                                            }}
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label :for="`uom_id_${index}`"
                                            >Unit of Measure*</label
                                        >
                                        <select
                                            :id="`uom_id_${index}`"
                                            v-model="line.uom_id"
                                            required
                                            :class="{
                                                'is-invalid': getLineError(
                                                    index,
                                                    'uom_id'
                                                ),
                                            }"
                                        >
                                            <option value="">Select UOM</option>
                                            <option
                                                v-for="uom in uoms"
                                                :key="uom.uom_id"
                                                :value="uom.uom_id"
                                            >
                                                {{ uom.name }}
                                            </option>
                                        </select>
                                        <div
                                            v-if="getLineError(index, 'uom_id')"
                                            class="error-message"
                                        >
                                            {{ getLineError(index, "uom_id") }}
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label :for="`required_date_${index}`"
                                            >Required Date</label
                                        >
                                        <input
                                            type="date"
                                            :id="`required_date_${index}`"
                                            v-model="line.required_date"
                                            :class="{
                                                'is-invalid': getLineError(
                                                    index,
                                                    'required_date'
                                                ),
                                            }"
                                        />
                                        <div
                                            v-if="
                                                getLineError(
                                                    index,
                                                    'required_date'
                                                )
                                            "
                                            class="error-message"
                                        >
                                            {{
                                                getLineError(
                                                    index,
                                                    "required_date"
                                                )
                                            }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button
                        type="button"
                        class="btn btn-secondary"
                        @click="cancel"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="btn btn-primary"
                        :disabled="isSubmitting"
                    >
                        <i
                            v-if="isSubmitting"
                            class="fas fa-spinner fa-spin"
                        ></i>
                        {{
                            isSubmitting
                                ? "Saving..."
                                : isEditMode
                                ? "Update RFQ"
                                : "Create RFQ"
                        }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
import { ref, reactive, computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import axios from "axios";

export default {
    name: "RFQForm",
    props: {
        id: {
            type: [Number, String],
            default: null,
        },
    },
    setup(props) {
        const router = useRouter();
        const isLoading = ref(true);
        const isSubmitting = ref(false);
        const items = ref([]);
        const uoms = ref([]);
        const errors = ref({});
        const lineErrors = ref([]);

        // Form data
        const formData = reactive({
            rfq_date: new Date().toISOString().substr(0, 10),
            validity_date: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000)
                .toISOString()
                .substr(0, 10), // Default: 30 days from now
            lines: [],
        });

        // Computed
        const isEditMode = computed(() => !!props.id);

        // Load data
        const loadFormData = async () => {
            isLoading.value = true;

            try {
                // Fetch items and UOMs in parallel
                const [itemsResponse, uomsResponse] = await Promise.all([
                    axios.get("/api/items"),
                    axios.get("/api/unit-of-measures"),
                ]);

                items.value = itemsResponse.data.data || [];
                uoms.value = uomsResponse.data.data || [];

                // If edit mode, fetch RFQ data
                if (isEditMode.value) {
                    const rfqResponse = await axios.get(
                        `/api/request-for-quotations/${props.id}`
                    );
                    const rfqData = rfqResponse.data.data;

                    // Populate form data
                    formData.rfq_date = rfqData.rfq_date;
                    formData.validity_date = rfqData.validity_date;

                    // Map lines
                    formData.lines = rfqData.lines.map((line) => ({
                        item_id: line.item_id,
                        quantity: line.quantity,
                        uom_id: line.uom_id,
                        required_date: line.required_date || null,
                    }));
                }
            } catch (error) {
                console.error("Error loading form data:", error);
                alert("Failed to load form data. Please try again.");
            } finally {
                isLoading.value = false;
            }
        };

        // Helpers for form lines
        const addLine = () => {
            formData.lines.push({
                item_id: "",
                quantity: 1,
                uom_id: "",
                required_date: null,
            });

            // Add an empty object for line errors
            lineErrors.value.push({});
        };

        const removeLine = (index) => {
            formData.lines.splice(index, 1);
            lineErrors.value.splice(index, 1);
        };

        const getLineError = (lineIndex, field) => {
            return (
                lineErrors.value[lineIndex] &&
                lineErrors.value[lineIndex][field]
            );
        };

        // Form submission
        const submitForm = async () => {
            // Reset errors
            errors.value = {};
            lineErrors.value = Array(formData.lines.length).fill({});

            // Validation
            let isValid = true;

            if (!formData.rfq_date) {
                errors.value.rfq_date = "RFQ date is required";
                isValid = false;
            }

            if (!formData.validity_date) {
                errors.value.validity_date = "Validity date is required";
                isValid = false;
            }

            if (formData.lines.length === 0) {
                errors.value.lines = "At least one item is required";
                isValid = false;
            }

            // Validate each line
            formData.lines.forEach((line, index) => {
                const lineError = {};

                if (!line.item_id) {
                    lineError.item_id = "Item is required";
                    isValid = false;
                }

                if (!line.quantity || line.quantity <= 0) {
                    lineError.quantity = "Valid quantity is required";
                    isValid = false;
                }

                if (!line.uom_id) {
                    lineError.uom_id = "Unit of measure is required";
                    isValid = false;
                }

                lineErrors.value[index] = lineError;
            });

            if (!isValid) return;

            // Submit form
            isSubmitting.value = true;

            try {
                if (isEditMode.value) {
                    await axios.put(
                        `/api/request-for-quotations/${props.id}`,
                        formData
                    );
                    router.push(`/purchasing/rfqs/${props.id}`);
                } else {
                    const response = await axios.post(
                        "/api/request-for-quotations",
                        formData
                    );
                    router.push(
                        `/purchasing/rfqs/${response.data.data.rfq_id}`
                    );
                }
            } catch (error) {
                console.error("Error saving RFQ:", error);

                // Handle validation errors
                if (
                    error.response &&
                    error.response.data &&
                    error.response.data.errors
                ) {
                    const serverErrors = error.response.data.errors;

                    // Map server errors to form fields
                    Object.entries(serverErrors).forEach(([key, messages]) => {
                        // Check if error belongs to a line item
                        const lineMatch = key.match(/^lines\.(\d+)\.(.+)$/);

                        if (lineMatch) {
                            const [, lineIndex, field] = lineMatch;
                            if (!lineErrors.value[lineIndex]) {
                                lineErrors.value[lineIndex] = {};
                            }
                            lineErrors.value[lineIndex][field] = messages[0];
                        } else {
                            errors.value[key] = messages[0];
                        }
                    });
                } else {
                    alert("Failed to save RFQ. Please try again.");
                }
            } finally {
                isSubmitting.value = false;
            }
        };

        const cancel = () => {
            if (isEditMode.value) {
                router.push(`/purchasing/rfqs/${props.id}`);
            } else {
                router.push("/purchasing/rfqs");
            }
        };

        // Initialize
        onMounted(() => {
            loadFormData();
        });

        return {
            isLoading,
            isSubmitting,
            isEditMode,
            items,
            uoms,
            formData,
            errors,
            lineErrors,
            addLine,
            removeLine,
            getLineError,
            submitForm,
            cancel,
        };
    },
};
</script>

<style scoped>
.rfq-form-container {
    padding: 1rem;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1.5rem;
}

.header-left {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--gray-600);
    text-decoration: none;
    font-size: 0.875rem;
}

.back-link:hover {
    color: var(--primary-color);
}

.header-left h1 {
    margin: 0;
    font-size: 1.5rem;
    color: var(--gray-800);
}

.loading-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 2rem;
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.loading-spinner {
    font-size: 2rem;
    color: var(--primary-color);
    margin-bottom: 1rem;
}

.form-wrapper {
    max-width: 1000px;
    margin: 0 auto;
}

.form-card {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    margin-bottom: 1.5rem;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    background-color: var(--gray-50);
    border-bottom: 1px solid var(--gray-200);
}

.card-title {
    margin: 0;
    font-size: 1.25rem;
    color: var(--gray-800);
}

.card-body {
    padding: 1.5rem;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.25rem;
    margin-bottom: 1.25rem;
}

.form-row:last-child {
    margin-bottom: 0;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group-lg {
    grid-column: 1 / -1;
}

label {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--gray-700);
    margin-bottom: 0.5rem;
}

input,
select {
    padding: 0.625rem;
    border: 1px solid var(--gray-200);
    border-radius: 0.375rem;
    font-size: 0.875rem;
    transition: border-color 0.2s, box-shadow 0.2s;
}

input:focus,
select:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.is-invalid {
    border-color: var(--danger-color);
}

.is-invalid:focus {
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.error-message {
    font-size: 0.75rem;
    color: var(--danger-color);
    margin-top: 0.375rem;
}

.line-items-container {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.line-item {
    border: 1px solid var(--gray-200);
    border-radius: 0.5rem;
    padding: 1.25rem;
}

.line-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.line-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--gray-800);
    margin: 0;
}

.btn-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border-radius: 0.375rem;
    border: none;
    cursor: pointer;
    transition: background-color 0.2s;
}

.btn-danger {
    color: var(--danger-color);
    background-color: transparent;
}

.btn-danger:hover {
    background-color: var(--danger-bg);
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    text-align: center;
}

.empty-icon {
    font-size: 2.5rem;
    color: var(--gray-300);
    margin-bottom: 1rem;
}

.empty-state h3 {
    font-size: 1.125rem;
    color: var(--gray-700);
    margin-bottom: 0.5rem;
}

.empty-state p {
    color: var(--gray-500);
    max-width: 24rem;
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
    background-color: var(--primary-color);
    color: white;
}

.btn-primary:hover:not(:disabled) {
    background-color: var(--primary-dark);
}

.btn-primary:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.btn-secondary {
    background-color: var(--gray-200);
    color: var(--gray-700);
}

.btn-secondary:hover {
    background-color: var(--gray-300);
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>
