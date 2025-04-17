<!-- src/views/purchasing/PurchaseRequisitionForm.vue - Template Part -->
<template>
    <div class="pr-form-container">
        <div class="page-header">
            <div class="header-left">
                <router-link to="/purchasing/requisitions" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to Requisitions
                </router-link>
                <h1>
                    {{
                        isEditMode
                            ? "Edit Purchase Requisition"
                            : "Create Purchase Requisition"
                    }}
                </h1>
            </div>
        </div>

        <div v-if="isLoading" class="loading-container">
            <div class="loading-spinner">
                <i class="fas fa-spinner fa-spin"></i>
            </div>
            <p>
                {{
                    isEditMode
                        ? "Loading purchase requisition data..."
                        : "Loading form data..."
                }}
            </p>
        </div>

        <div v-else class="form-container">
            <form @submit.prevent="savePR">
                <!-- Header Section -->
                <div class="form-section">
                    <h2 class="section-title">Requisition Information</h2>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="pr_date">Date*</label>
                            <input
                                type="date"
                                id="pr_date"
                                v-model="formData.pr_date"
                                required
                            />
                            <div v-if="errors.pr_date" class="error-message">
                                {{ errors.pr_date }}
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="requester_id">Requester*</label>
                            <select
                                id="requester_id"
                                v-model="formData.requester_id"
                                required
                            >
                                <option value="">Select a requester</option>
                                <option
                                    v-for="user in users"
                                    :key="user.user_id"
                                    :value="user.user_id"
                                >
                                    {{ user.name }}
                                </option>
                            </select>
                            <div
                                v-if="errors.requester_id"
                                class="error-message"
                            >
                                {{ errors.requester_id }}
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea
                            id="notes"
                            v-model="formData.notes"
                            rows="3"
                            placeholder="Additional information or instructions"
                        ></textarea>
                        <div v-if="errors.notes" class="error-message">
                            {{ errors.notes }}
                        </div>
                    </div>
                </div>

                <!-- Lines Section -->
                <div class="form-section">
                    <div class="section-header">
                        <h2 class="section-title">Requisition Items</h2>
                        <button
                            type="button"
                            class="btn btn-secondary"
                            @click="addLine"
                        >
                            <i class="fas fa-plus"></i> Add Item
                        </button>
                    </div>

                    <div v-if="formData.lines.length === 0" class="empty-lines">
                        <div class="empty-icon">
                            <i class="fas fa-boxes"></i>
                        </div>
                        <p>
                            No items added yet. Click "Add Item" to start adding
                            items.
                        </p>
                    </div>

                    <div v-else class="lines-container">
                        <div
                            v-for="(line, index) in formData.lines"
                            :key="index"
                            class="line-item"
                        >
                            <div class="line-header">
                                <h3 class="line-title">Item {{ index + 1 }}</h3>
                                <button
                                    type="button"
                                    class="btn-icon"
                                    @click="removeLine(index)"
                                    title="Remove item"
                                >
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>

                            <div class="line-body">
                                <div class="form-row">
                                    <div class="form-group form-group-large">
                                        <label :for="'item_id-' + index"
                                            >Item*</label
                                        >
                                        <select
                                            :id="'item_id-' + index"
                                            v-model="line.item_id"
                                            required
                                        >
                                            <option value="">
                                                Select an item
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
                                        <label :for="'quantity-' + index"
                                            >Quantity*</label
                                        >
                                        <input
                                            type="number"
                                            :id="'quantity-' + index"
                                            v-model="line.quantity"
                                            min="0.01"
                                            step="0.01"
                                            required
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
                                        <label :for="'uom_id-' + index"
                                            >Unit of Measure*</label
                                        >
                                        <select
                                            :id="'uom_id-' + index"
                                            v-model="line.uom_id"
                                            required
                                        >
                                            <option value="">Select UOM</option>
                                            <option
                                                v-for="uom in unitOfMeasures"
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
                                        <label :for="'required_date-' + index"
                                            >Required Date</label
                                        >
                                        <input
                                            type="date"
                                            :id="'required_date-' + index"
                                            v-model="line.required_date"
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

                                <div class="form-group">
                                    <label :for="'line_notes-' + index"
                                        >Notes</label
                                    >
                                    <textarea
                                        :id="'line_notes-' + index"
                                        v-model="line.notes"
                                        rows="2"
                                        placeholder="Additional information for this item"
                                    ></textarea>
                                    <div
                                        v-if="getLineError(index, 'notes')"
                                        class="error-message"
                                    >
                                        {{ getLineError(index, "notes") }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
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
                        {{
                            isSubmitting
                                ? "Saving..."
                                : isEditMode
                                ? "Update"
                                : "Create"
                        }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
