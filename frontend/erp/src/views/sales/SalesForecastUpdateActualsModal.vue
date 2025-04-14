<!-- src/components/sales/SalesForecastUpdateActualsModal.vue -->
<template>
    <div class="modal">
        <div class="modal-backdrop" @click="$emit('close')"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h2>Update Actual Quantities</h2>
                <button class="close-btn" @click="$emit('close')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="info-alert">
                    <i class="fas fa-info-circle"></i>
                    <div>
                        <p><strong>About Updating Actuals</strong></p>
                        <p>
                            This tool automatically calculates actual quantities
                            from completed sales invoices. It will update
                            forecasts with actual sales data up to the selected
                            end period.
                        </p>
                    </div>
                </div>

                <form @submit.prevent="submitForm">
                    <div class="form-group">
                        <label for="end_period">End Period*</label>
                        <input
                            type="date"
                            id="end_period"
                            v-model="form.end_period"
                            required
                            :class="{ 'is-invalid': errors.end_period }"
                        />
                        <span v-if="errors.end_period" class="error-message">{{
                            errors.end_period
                        }}</span>
                        <div class="help-text">
                            Only forecasts up to this date will be updated with
                            actual quantities
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="checkbox-container">
                            <input
                                type="checkbox"
                                id="update_all"
                                v-model="form.update_all"
                            />
                            <label for="update_all">Update All Forecasts</label>
                        </div>
                        <div class="help-text">
                            If checked, all forecasts will be updated, including
                            those with existing actual quantities. If unchecked,
                            only forecasts without actual quantities will be
                            updated.
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
                            Update Actual Quantities
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
    name: "SalesForecastUpdateActualsModal",
    emits: ["close", "update"],
    setup(props, { emit }) {
        // Set default end period to today
        const today = new Date().toISOString().substr(0, 10);

        const form = reactive({
            end_period: today,
            update_all: false,
        });

        const errors = ref({});

        // Validate form
        const validateForm = () => {
            errors.value = {};

            if (!form.end_period) {
                errors.value.end_period = "End period is required";
            }

            return Object.keys(errors.value).length === 0;
        };

        const submitForm = () => {
            if (validateForm()) {
                emit("update", {
                    end_period: form.end_period,
                    update_all: form.update_all,
                });
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
    max-width: 550px;
    z-index: 60;
    overflow: hidden;
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

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: var(--gray-700);
}

.form-group input[type="date"] {
    width: 100%;
    padding: 0.625rem;
    border: 1px solid var(--gray-300);
    border-radius: 0.375rem;
    font-size: 0.875rem;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.form-group input[type="date"]:focus {
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

.help-text {
    font-size: 0.75rem;
    color: var(--gray-500);
    margin-top: 0.5rem;
}

.checkbox-container {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    cursor: pointer;
}

.checkbox-container input[type="checkbox"] {
    width: 1rem;
    height: 1rem;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 2rem;
}
</style>
