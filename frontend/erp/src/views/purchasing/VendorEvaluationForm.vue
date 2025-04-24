<!-- src/views/purchasing/VendorEvaluationForm.vue -->

<!--
  TEMPLATE SECTION
  This section defines the HTML structure of the component
-->
<template>
    <div class="evaluation-form-container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-left">
                <router-link :to="cancelPath" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to
                    {{ isEditMode ? "Evaluation Details" : "Evaluations" }}
                </router-link>
                <h1>
                    {{
                        isEditMode
                            ? "Edit Vendor Evaluation"
                            : "Create Vendor Evaluation"
                    }}
                </h1>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="isLoading" class="loading-container">
            <div class="loading-spinner">
                <i class="fas fa-spinner fa-spin"></i>
            </div>
            <p>Loading data...</p>
        </div>

        <!-- Form Container -->
        <div v-else class="form-container">
            <form @submit.prevent="submitForm">
                <!-- Basic Information Section -->
                <div class="form-section">
                    <h3 class="section-title">Basic Information</h3>

                    <div class="form-row">
                        <!-- Vendor Selection -->
                        <div class="form-group">
                            <label for="vendor_id">Vendor*</label>
                            <select
                                id="vendor_id"
                                v-model="formData.vendor_id"
                                required
                                :disabled="isEditMode"
                            >
                                <option value="" disabled>Select Vendor</option>
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

                        <!-- Evaluation Date -->
                        <div class="form-group">
                            <label for="evaluation_date"
                                >Evaluation Date*</label
                            >
                            <input
                                type="date"
                                id="evaluation_date"
                                v-model="formData.evaluation_date"
                                required
                            />
                            <div
                                v-if="errors.evaluation_date"
                                class="error-message"
                            >
                                {{ errors.evaluation_date }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Performance Evaluation Section -->
                <div class="form-section">
                    <h3 class="section-title">Performance Evaluation</h3>
                    <p class="section-description">
                        Rate the vendor's performance in each category on a
                        scale of 1 to 5, where 1 is poor and 5 is excellent.
                    </p>

                    <div class="score-section">
                        <!-- Quality Score -->
                        <div class="score-card">
                            <div class="score-header">
                                <h4>Quality Score*</h4>
                                <div class="current-score">
                                    {{ formData.quality_score }}
                                </div>
                            </div>
                            <p class="score-description">
                                Evaluate the quality of products or services
                                provided by the vendor.
                            </p>
                            <div class="score-slider">
                                <input
                                    type="range"
                                    min="1"
                                    max="5"
                                    step="0.5"
                                    v-model.number="formData.quality_score"
                                />
                                <div class="score-labels">
                                    <span>Poor</span>
                                    <span>Fair</span>
                                    <span>Good</span>
                                    <span>Very Good</span>
                                    <span>Excellent</span>
                                </div>
                            </div>
                            <div
                                v-if="errors.quality_score"
                                class="error-message"
                            >
                                {{ errors.quality_score }}
                            </div>
                        </div>

                        <!-- Delivery Score -->
                        <div class="score-card">
                            <div class="score-header">
                                <h4>Delivery Score*</h4>
                                <div class="current-score">
                                    {{ formData.delivery_score }}
                                </div>
                            </div>
                            <p class="score-description">
                                Evaluate timeliness and accuracy of deliveries.
                            </p>
                            <div class="score-slider">
                                <input
                                    type="range"
                                    min="1"
                                    max="5"
                                    step="0.5"
                                    v-model.number="formData.delivery_score"
                                />
                                <div class="score-labels">
                                    <span>Poor</span>
                                    <span>Fair</span>
                                    <span>Good</span>
                                    <span>Very Good</span>
                                    <span>Excellent</span>
                                </div>
                            </div>
                            <div
                                v-if="errors.delivery_score"
                                class="error-message"
                            >
                                {{ errors.delivery_score }}
                            </div>
                        </div>

                        <!-- Price Score -->
                        <div class="score-card">
                            <div class="score-header">
                                <h4>Price Score*</h4>
                                <div class="current-score">
                                    {{ formData.price_score }}
                                </div>
                            </div>
                            <p class="score-description">
                                Evaluate competitiveness of pricing and value
                                for money.
                            </p>
                            <div class="score-slider">
                                <input
                                    type="range"
                                    min="1"
                                    max="5"
                                    step="0.5"
                                    v-model.number="formData.price_score"
                                />
                                <div class="score-labels">
                                    <span>Poor</span>
                                    <span>Fair</span>
                                    <span>Good</span>
                                    <span>Very Good</span>
                                    <span>Excellent</span>
                                </div>
                            </div>
                            <div
                                v-if="errors.price_score"
                                class="error-message"
                            >
                                {{ errors.price_score }}
                            </div>
                        </div>

                        <!-- Service Score -->
                        <div class="score-card">
                            <div class="score-header">
                                <h4>Service Score*</h4>
                                <div class="current-score">
                                    {{ formData.service_score }}
                                </div>
                            </div>
                            <p class="score-description">
                                Evaluate customer service, responsiveness, and
                                support.
                            </p>
                            <div class="score-slider">
                                <input
                                    type="range"
                                    min="1"
                                    max="5"
                                    step="0.5"
                                    v-model.number="formData.service_score"
                                />
                                <div class="score-labels">
                                    <span>Poor</span>
                                    <span>Fair</span>
                                    <span>Good</span>
                                    <span>Very Good</span>
                                    <span>Excellent</span>
                                </div>
                            </div>
                            <div
                                v-if="errors.service_score"
                                class="error-message"
                            >
                                {{ errors.service_score }}
                            </div>
                        </div>
                    </div>

                    <!-- Overall Score Display -->
                    <div class="overall-score">
                        <div class="overall-score-label">Overall Score:</div>
                        <div
                            :class="[
                                'overall-score-value',
                                getScoreClass(totalScore),
                            ]"
                        >
                            {{ totalScore.toFixed(2) }}
                        </div>
                    </div>
                </div>

                <!-- Notes Section -->
                <div class="form-section">
                    <h3 class="section-title">Notes</h3>
                    <div class="form-group">
                        <textarea
                            v-model="formData.notes"
                            rows="4"
                            placeholder="Add any additional notes or comments about the vendor's performance..."
                        ></textarea>
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
                                ? "Update Evaluation"
                                : "Create Evaluation"
                        }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<!--
  SCRIPT SECTION
  This section defines the JavaScript logic for the component
-->
<script>
import { ref, computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import VendorService from "@/services/VendorService";
import VendorEvaluationService from "@/services/VendorEvaluationService";

export default {
    name: "VendorEvaluationForm",
    props: {
        // If ID is provided, we're in edit mode
        id: {
            type: [Number, String],
            default: null,
        },
    },
    setup(props) {
        const router = useRouter();

        // State variables
        const isLoading = ref(true);
        const isSubmitting = ref(false);
        const vendors = ref([]);
        const errors = ref({});

        // Form data with default values
        const formData = ref({
            vendor_id: "",
            evaluation_date: new Date().toISOString().slice(0, 10), // Today's date as default
            quality_score: 3,
            delivery_score: 3,
            price_score: 3,
            service_score: 3,
            notes: "",
        });

        // Computed properties
        const isEditMode = computed(() => !!props.id);

        const cancelPath = computed(() => {
            return isEditMode.value
                ? `/purchasing/evaluations/${props.id}`
                : "/purchasing/evaluations";
        });

        const totalScore = computed(() => {
            // Calculate average of all scores
            return (
                (formData.value.quality_score +
                    formData.value.delivery_score +
                    formData.value.price_score +
                    formData.value.service_score) /
                4
            );
        });

        // Methods

        // Fetch vendors for the dropdown
        const fetchVendors = async () => {
            try {
                const response = await VendorService.getAllVendors({
                    per_page: 100,
                    status: "active",
                });

                if (response.data && response.data.data) {
                    vendors.value = response.data.data;
                }
            } catch (error) {
                console.error("Error fetching vendors:", error);
            }
        };

        // Fetch evaluation data for edit mode
        const fetchEvaluation = async () => {
            if (!props.id) return;

            try {
                const response =
                    await VendorEvaluationService.getEvaluationById(props.id);
                if (response.data && response.data.data) {
                    const evaluation = response.data.data;

                    // Populate form with existing data
                    formData.value = {
                        vendor_id: evaluation.vendor_id,
                        evaluation_date: evaluation.evaluation_date
                            ? evaluation.evaluation_date.slice(0, 10)
                            : new Date().toISOString().slice(0, 10),
                        quality_score: evaluation.quality_score,
                        delivery_score: evaluation.delivery_score,
                        price_score: evaluation.price_score,
                        service_score: evaluation.service_score,
                        notes: evaluation.notes || "",
                    };
                }
            } catch (error) {
                console.error("Error fetching evaluation details:", error);
            }
        };

        // Initialize data
        const initializeData = async () => {
            isLoading.value = true;

            try {
                // Load data in parallel for better performance
                await Promise.all([
                    fetchVendors(),
                    isEditMode.value ? fetchEvaluation() : Promise.resolve(),
                ]);
            } catch (error) {
                console.error("Error initializing data:", error);
            } finally {
                isLoading.value = false;
            }
        };

        // Get CSS class based on score
        const getScoreClass = (score) => {
            if (score >= 4.5) return "score-excellent";
            if (score >= 3.5) return "score-good";
            if (score >= 2.5) return "score-average";
            if (score >= 1.5) return "score-below-average";
            return "score-poor";
        };

        // Form submission
        const submitForm = async () => {
            isSubmitting.value = true;
            errors.value = {};

            try {
                if (isEditMode.value) {
                    // Update existing evaluation
                    await VendorEvaluationService.updateEvaluation(
                        props.id,
                        formData.value
                    );
                    router.push(`/purchasing/evaluations/${props.id}`);
                } else {
                    // Create new evaluation
                    const response =
                        await VendorEvaluationService.createEvaluation(
                            formData.value
                        );
                    const newEvaluationId =
                        response.data && response.data.data
                            ? response.data.data.evaluation_id
                            : null;

                    if (newEvaluationId) {
                        router.push(
                            `/purchasing/evaluations/${newEvaluationId}`
                        );
                    } else {
                        router.push("/purchasing/evaluations");
                    }
                }
            } catch (error) {
                console.error("Error saving evaluation:", error);

                // Handle validation errors from the server
                if (error.response && error.response.status === 422) {
                    errors.value = error.response.data.errors || {};
                } else {
                    alert("Failed to save evaluation. Please try again.");
                }

                isSubmitting.value = false;
            }
        };

        // Cancel form
        const cancel = () => {
            router.push(cancelPath.value);
        };

        // Initialize on component mount
        onMounted(() => {
            initializeData();
        });

        // Return all variables and methods that will be used in the template
        return {
            isLoading,
            isSubmitting,
            vendors,
            formData,
            errors,
            isEditMode,
            cancelPath,
            totalScore,
            getScoreClass,
            submitForm,
            cancel,
        };
    },
};
</script>

<!--
  STYLE SECTION
  This section defines the CSS styling for the component
-->
<style scoped>
/* Container Styling */
.evaluation-form-container {
    padding: 1rem;
}

/* Page Header Styling */
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

/* Loading State Styling */
.loading-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 2rem;
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    text-align: center;
}

.loading-spinner {
    font-size: 2rem;
    color: var(--primary-color);
    margin-bottom: 1rem;
}

/* Form Sections Styling */
.form-container {
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
    color: var(--gray-800);
    padding-bottom: 0.75rem;
    border-bottom: 1px solid var(--gray-200);
}

.section-description {
    font-size: 0.875rem;
    color: var(--gray-600);
    margin-bottom: 1.5rem;
}

/* Form Layout */
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

/* Form Controls Styling */
label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: var(--gray-700);
    font-size: 0.875rem;
}

input[type="text"],
input[type="date"],
input[type="number"],
select,
textarea {
    width: 100%;
    padding: 0.625rem;
    border: 1px solid var(--gray-300);
    border-radius: 0.375rem;
    font-size: 0.875rem;
    background-color: white;
    transition: border-color 0.2s, box-shadow 0.2s;
}

input[type="text"]:focus,
input[type="date"]:focus,
input[type="number"]:focus,
select:focus,
textarea:focus {
    border-color: var(--primary-color);
    outline: none;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

textarea {
    resize: vertical;
    min-height: 100px;
}

.error-message {
    color: var(--danger-color);
    font-size: 0.75rem;
    margin-top: 0.375rem;
}

/* Score Section Styling */
.score-section {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.score-card {
    padding: 1.25rem;
    border: 1px solid var(--gray-200);
    border-radius: 0.5rem;
    background-color: var(--gray-50);
}

.score-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
}

.score-header h4 {
    margin: 0;
    font-size: 1rem;
    color: var(--gray-800);
}

.current-score {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--primary-color);
}

.score-description {
    font-size: 0.875rem;
    color: var(--gray-600);
    margin-bottom: 1.25rem;
}

/* Slider Styling */
.score-slider {
    margin-bottom: 0.5rem;
}

.score-slider input[type="range"] {
    width: 100%;
    margin-bottom: 0.5rem;
    cursor: pointer;
}

.score-labels {
    display: flex;
    justify-content: space-between;
    font-size: 0.75rem;
    color: var(--gray-600);
}

/* Overall Score Styling */
.overall-score {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    margin-top: 1.5rem;
    padding: 1rem;
    background-color: white;
    border-radius: 0.5rem;
    border: 1px solid var(--gray-200);
}

.overall-score-label {
    font-size: 1rem;
    font-weight: 500;
    margin-right: 1rem;
    color: var(--gray-700);
}

.overall-score-value {
    font-size: 1.25rem;
    font-weight: 600;
    padding: 0.375rem 1rem;
    border-radius: 0.375rem;
}

/* Score Color Classes */
.score-excellent {
    background-color: #15803d;
    color: white;
}

.score-good {
    background-color: #65a30d;
    color: white;
}

.score-average {
    background-color: #f59e0b;
    color: white;
}

.score-below-average {
    background-color: #f97316;
    color: white;
}

.score-poor {
    background-color: #dc2626;
    color: white;
}

/* Form Actions Styling */
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
    color: var(--gray-800);
}

.btn-secondary:hover {
    background-color: var(--gray-300);
}

/* Responsive Styles */
@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }

    .overall-score {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
}
</style>
