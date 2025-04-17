<!-- src/components/manufacturing/BOMFormModal.vue -->
<template>
    <div class="modal">
        <div class="modal-backdrop" @click="$emit('close')"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h2>{{ isEditMode ? "Edit BOM" : "Create New BOM" }}</h2>
                <button class="close-btn" @click="$emit('close')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form @submit.prevent="submitForm">
                    <!-- BOM Basic Information -->
                    <div class="section-header">
                        <h3>Basic Information</h3>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="product_id">Product*</label>
                            <select
                                id="product_id"
                                v-model="form.product_id"
                                required
                                :class="{ 'is-invalid': errors.product_id }"
                                :disabled="isEditMode"
                            >
                                <option value="">-- Select Product --</option>
                                <option
                                    v-for="product in products"
                                    :key="product.product_id"
                                    :value="product.product_id"
                                >
                                    {{ product.name }} ({{
                                        product.product_code
                                    }})
                                </option>
                            </select>
                            <span
                                v-if="errors.product_id"
                                class="error-message"
                                >{{ errors.product_id }}</span
                            >
                        </div>
                        <div class="form-group">
                            <label for="bom_code">BOM Code*</label>
                            <input
                                type="text"
                                id="bom_code"
                                v-model="form.bom_code"
                                required
                                :class="{ 'is-invalid': errors.bom_code }"
                                :disabled="isEditMode"
                            />
                            <span
                                v-if="errors.bom_code"
                                class="error-message"
                                >{{ errors.bom_code }}</span
                            >
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="revision">Revision*</label>
                            <input
                                type="text"
                                id="revision"
                                v-model="form.revision"
                                required
                                :class="{ 'is-invalid': errors.revision }"
                            />
                            <span
                                v-if="errors.revision"
                                class="error-message"
                                >{{ errors.revision }}</span
                            >
                        </div>
                        <div class="form-group">
                            <label for="effective_date">Effective Date*</label>
                            <input
                                type="date"
                                id="effective_date"
                                v-model="form.effective_date"
                                required
                                :class="{ 'is-invalid': errors.effective_date }"
                            />
                            <span
                                v-if="errors.effective_date"
                                class="error-message"
                                >{{ errors.effective_date }}</span
                            >
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="status">Status*</label>
                            <select
                                id="status"
                                v-model="form.status"
                                required
                                :class="{ 'is-invalid': errors.status }"
                            >
                                <option value="Draft">Draft</option>
                                <option value="Active">Active</option>
                                <option value="Obsolete">Obsolete</option>
                            </select>
                            <span v-if="errors.status" class="error-message">{{
                                errors.status
                            }}</span>
                        </div>
                        <div class="form-group">
                            <label for="uom_id">Unit of Measure*</label>
                            <select
                                id="uom_id"
                                v-model="form.uom_id"
                                required
                                :class="{ 'is-invalid': errors.uom_id }"
                            >
                                <option value="">-- Select UOM --</option>
                                <option
                                    v-for="uom in unitOfMeasures"
                                    :key="uom.uom_id"
                                    :value="uom.uom_id"
                                >
                                    {{ uom.name }} ({{ uom.symbol }})
                                </option>
                            </select>
                            <span v-if="errors.uom_id" class="error-message">{{
                                errors.uom_id
                            }}</span>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="standard_quantity"
                                >Standard Quantity*</label
                            >
                            <input
                                type="number"
                                id="standard_quantity"
                                v-model="form.standard_quantity"
                                min="0.01"
                                step="0.01"
                                required
                                :class="{
                                    'is-invalid': errors.standard_quantity,
                                }"
                            />
                            <span
                                v-if="errors.standard_quantity"
                                class="error-message"
                                >{{ errors.standard_quantity }}</span
                            >
                        </div>
                    </div>

                    <!-- BOM Components (Lines) Section -->
                    <div v-if="!isEditMode" class="section-header with-button">
                        <h3>Components</h3>
                        <button
                            type="button"
                            class="btn btn-sm btn-outline"
                            @click="addBOMLine"
                        >
                            <i class="fas fa-plus"></i> Add Component
                        </button>
                    </div>

                    <div v-if="!isEditMode" class="bom-lines">
                        <div
                            v-if="form.bom_lines && form.bom_lines.length > 0"
                            class="bom-lines-table"
                        >
                            <table>
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Quantity</th>
                                        <th>UOM</th>
                                        <th>Critical</th>
                                        <th>Notes</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="(line, index) in form.bom_lines"
                                        :key="index"
                                    >
                                        <td>
                                            <select
                                                v-model="line.item_id"
                                                required
                                                :class="{
                                                    'is-invalid':
                                                        lineErrors[index]
                                                            ?.item_id,
                                                }"
                                            >
                                                <option value="">
                                                    -- Select Item --
                                                </option>
                                                <option
                                                    v-for="item in items"
                                                    :key="item.item_id"
                                                    :value="item.item_id"
                                                >
                                                    {{ item.name }} ({{
                                                        item.item_code
                                                    }})
                                                </option>
                                            </select>
                                            <span
                                                v-if="
                                                    lineErrors[index]?.item_id
                                                "
                                                class="error-message"
                                                >{{
                                                    lineErrors[index].item_id
                                                }}</span
                                            >
                                        </td>
                                        <td>
                                            <input
                                                type="number"
                                                v-model="line.quantity"
                                                min="0.0001"
                                                step="0.0001"
                                                required
                                                :class="{
                                                    'is-invalid':
                                                        lineErrors[index]
                                                            ?.quantity,
                                                }"
                                            />
                                            <span
                                                v-if="
                                                    lineErrors[index]?.quantity
                                                "
                                                class="error-message"
                                                >{{
                                                    lineErrors[index].quantity
                                                }}</span
                                            >
                                        </td>
                                        <td>
                                            <select
                                                v-model="line.uom_id"
                                                required
                                                :class="{
                                                    'is-invalid':
                                                        lineErrors[index]
                                                            ?.uom_id,
                                                }"
                                            >
                                                <option value="">
                                                    -- Select UOM --
                                                </option>
                                                <option
                                                    v-for="uom in unitOfMeasures"
                                                    :key="uom.uom_id"
                                                    :value="uom.uom_id"
                                                >
                                                    {{ uom.symbol }}
                                                </option>
                                            </select>
                                            <span
                                                v-if="lineErrors[index]?.uom_id"
                                                class="error-message"
                                                >{{
                                                    lineErrors[index].uom_id
                                                }}</span
                                            >
                                        </td>
                                        <td>
                                            <input
                                                type="checkbox"
                                                v-model="line.is_critical"
                                            />
                                        </td>
                                        <td>
                                            <input
                                                type="text"
                                                v-model="line.notes"
                                                placeholder="Optional notes"
                                            />
                                        </td>
                                        <td>
                                            <button
                                                type="button"
                                                class="action-btn"
                                                @click="removeBOMLine(index)"
                                            >
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-else class="empty-lines">
                            <p>
                                No components added yet. Click "Add Component"
                                to add BOM components.
                            </p>
                        </div>
                    </div>

                    <!-- Note for Edit Mode -->
                    <div v-if="isEditMode" class="edit-note">
                        <p>
                            <i class="fas fa-info-circle"></i>
                            Components can be managed on the BOM detail page
                            after saving basic information.
                        </p>
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
                            {{ isEditMode ? "Update BOM" : "Create BOM" }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, reactive, onMounted, watch } from "vue";
import ProductService from "@/services/ProductService";
import ItemService from "@/services/ItemService";
import UnitOfMeasureService from "@/services/UnitOfMeasureService";

export default {
    name: "BOMFormModal",
    props: {
        isEditMode: {
            type: Boolean,
            default: false,
        },
        bomData: {
            type: Object,
            required: true,
        },
    },
    emits: ["close", "save"],
    setup(props, { emit }) {
        // Form state
        const form = reactive({
            bom_id: props.bomData.bom_id,
            product_id: props.bomData.product_id || "",
            bom_code: props.bomData.bom_code || "",
            revision: props.bomData.revision || "1.0",
            effective_date:
                props.bomData.effective_date ||
                new Date().toISOString().split("T")[0],
            status: props.bomData.status || "Draft",
            standard_quantity: props.bomData.standard_quantity || 1,
            uom_id: props.bomData.uom_id || "",
            bom_lines: [],
        });

        // Validation state
        const errors = ref({});
        const lineErrors = ref([]);

        // Reference data
        const products = ref([]);
        const items = ref([]);
        const unitOfMeasures = ref([]);

        // Watch for prop changes
        watch(
            () => props.bomData,
            (newData) => {
                form.bom_id = newData.bom_id;
                form.product_id = newData.product_id || "";
                form.bom_code = newData.bom_code || "";
                form.revision = newData.revision || "1.0";
                form.effective_date =
                    newData.effective_date ||
                    new Date().toISOString().split("T")[0];
                form.status = newData.status || "Draft";
                form.standard_quantity = newData.standard_quantity || 1;
                form.uom_id = newData.uom_id || "";
                form.bom_lines = [];
            },
            { deep: true }
        );

        // Load reference data
        const fetchReferenceData = async () => {
            try {
                // Fetch products
                const productsResponse = await ProductService.getProducts();
                products.value = productsResponse.data || [];

                // Fetch items
                const itemsResponse = await ItemService.getItems();
                items.value = itemsResponse.data || [];

                // Fetch UOMs
                const uomsResponse = await UnitOfMeasureService.getAll();
                unitOfMeasures.value = uomsResponse.data || [];
            } catch (error) {
                console.error("Error fetching reference data:", error);
            }
        };

        // BOM Lines Management
        const addBOMLine = () => {
            form.bom_lines.push({
                item_id: "",
                quantity: 1,
                uom_id: "",
                is_critical: false,
                notes: "",
            });
            lineErrors.value.push({});
        };

        const removeBOMLine = (index) => {
            form.bom_lines.splice(index, 1);
            lineErrors.value.splice(index, 1);
        };

        // Form Validation
        const validateForm = () => {
            errors.value = {};

            if (!form.product_id) {
                errors.value.product_id = "Product is required";
            }

            if (!form.bom_code) {
                errors.value.bom_code = "BOM Code is required";
            }

            if (!form.revision) {
                errors.value.revision = "Revision is required";
            }

            if (!form.effective_date) {
                errors.value.effective_date = "Effective Date is required";
            }

            if (!form.status) {
                errors.value.status = "Status is required";
            }

            if (!form.uom_id) {
                errors.value.uom_id = "Unit of Measure is required";
            }

            if (!form.standard_quantity || form.standard_quantity <= 0) {
                errors.value.standard_quantity =
                    "Standard Quantity must be greater than zero";
            }

            // Validate BOM lines if not in edit mode
            if (!props.isEditMode && form.bom_lines.length > 0) {
                let hasLineErrors = false;
                lineErrors.value = form.bom_lines.map((line) => {
                    const lineError = {};

                    if (!line.item_id) {
                        lineError.item_id = "Item is required";
                        hasLineErrors = true;
                    }

                    if (!line.quantity || line.quantity <= 0) {
                        lineError.quantity =
                            "Quantity must be greater than zero";
                        hasLineErrors = true;
                    }

                    if (!line.uom_id) {
                        lineError.uom_id = "UOM is required";
                        hasLineErrors = true;
                    }

                    return lineError;
                });

                if (hasLineErrors) {
                    return false;
                }
            }

            return Object.keys(errors.value).length === 0;
        };

        // Form Submission
        const submitForm = () => {
            if (validateForm()) {
                // Only send specific fields to avoid sending unnecessary data
                const formData = {
                    bom_id: form.bom_id,
                    product_id: parseInt(form.product_id),
                    bom_code: form.bom_code,
                    revision: form.revision,
                    effective_date: form.effective_date,
                    status: form.status,
                    standard_quantity: parseFloat(form.standard_quantity),
                    uom_id: parseInt(form.uom_id),
                };

                // Include BOM lines if not in edit mode
                if (!props.isEditMode && form.bom_lines.length > 0) {
                    formData.bom_lines = form.bom_lines.map((line) => ({
                        item_id: parseInt(line.item_id),
                        quantity: parseFloat(line.quantity),
                        uom_id: parseInt(line.uom_id),
                        is_critical: line.is_critical,
                        notes: line.notes || null,
                    }));
                }

                emit("save", formData);
            }
        };

        // Initialize
        onMounted(() => {
            fetchReferenceData();
        });

        return {
            form,
            errors,
            lineErrors,
            products,
            items,
            unitOfMeasures,
            addBOMLine,
            removeBOMLine,
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
    max-width: 800px;
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
    font-size: 1.25rem;
}

.modal-body {
    padding: 1.5rem;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid var(--gray-200);
}

.section-header h3 {
    font-size: 1rem;
    font-weight: 600;
    margin: 0;
    color: var(--gray-700);
}

.section-header.with-button {
    margin-top: 2rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
}

.form-group {
    margin-bottom: 1rem;
}

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

.form-group .is-invalid {
    border-color: var(--danger-color);
}

.error-message {
    display: block;
    margin-top: 0.25rem;
    font-size: 0.75rem;
    color: var(--danger-color);
}

.bom-lines {
    margin-bottom: 1.5rem;
}

.bom-lines-table {
    width: 100%;
    overflow-x: auto;
    border: 1px solid var(--gray-200);
    border-radius: 0.375rem;
}

.bom-lines-table table {
    width: 100%;
    border-collapse: collapse;
}

.bom-lines-table th {
    text-align: left;
    padding: 0.75rem;
    background-color: var(--gray-50);
    border-bottom: 1px solid var(--gray-200);
    font-weight: 500;
    font-size: 0.875rem;
    color: var(--gray-600);
}

.bom-lines-table td {
    padding: 0.5rem 0.75rem;
    border-bottom: 1px solid var(--gray-100);
    font-size: 0.875rem;
}

.bom-lines-table td input,
.bom-lines-table td select {
    width: 100%;
    padding: 0.375rem 0.5rem;
    border: 1px solid var(--gray-300);
    border-radius: 0.25rem;
    font-size: 0.875rem;
}

.bom-lines-table td input[type="checkbox"] {
    width: auto;
}

.empty-lines {
    padding: 2rem;
    text-align: center;
    background-color: var(--gray-50);
    border-radius: 0.375rem;
    color: var(--gray-500);
}

.edit-note {
    margin: 1.5rem 0;
    padding: 1rem;
    background-color: var(--info-bg);
    border-radius: 0.375rem;
    font-size: 0.875rem;
    color: var(--info-color);
}

.edit-note p {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 1.5rem;
}

.btn {
    padding: 0.625rem 1rem;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s;
}

.btn-primary {
    background-color: var(--primary-color);
    color: white;
    border: 1px solid var(--primary-color);
}

.btn-primary:hover {
    background-color: var(--primary-dark);
}

.btn-secondary {
    background-color: white;
    color: var(--gray-700);
    border: 1px solid var(--gray-300);
}

.btn-secondary:hover {
    background-color: var(--gray-100);
}

.btn-outline {
    background-color: transparent;
    border: 1px solid var(--gray-300);
    color: var(--gray-700);
}

.btn-outline:hover {
    background-color: var(--gray-50);
}

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.75rem;
}

.action-btn {
    background: none;
    border: none;
    color: var(--gray-500);
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 0.25rem;
}

.action-btn:hover {
    background-color: var(--gray-100);
    color: var(--danger-color);
}

@media (max-width: 640px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>
