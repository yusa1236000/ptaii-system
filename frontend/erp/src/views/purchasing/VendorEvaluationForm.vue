<!-- src/views/purchasing/VendorEvaluationForm.vue -->
<template>
    <div class="evaluation-form-container">
      <div class="page-header">
        <h1 class="text-2xl font-semibold">{{ isEditMode ? 'Edit Vendor Evaluation' : 'Create Vendor Evaluation' }}</h1>
        <div class="actions">
          <router-link to="/purchasing/evaluations" class="btn-outline">
            <i class="fas fa-arrow-left mr-2"></i> Back to List
          </router-link>
        </div>
      </div>

      <div class="form-panel">
        <form @submit.prevent="saveEvaluation" class="evaluation-form">
          <div class="form-section basic-info">
            <h2 class="section-title">Basic Information</h2>
            <div class="form-grid">
              <div class="form-group">
                <label for="vendor_id" class="required">Vendor</label>
                <select
                  id="vendor_id"
                  v-model="evaluation.vendor_id"
                  class="form-control"
                  :class="{ 'error': validationErrors.vendor_id }"
                  required
                >
                  <option value="">Select Vendor</option>
                  <option v-for="vendor in vendors" :key="vendor.vendor_id" :value="vendor.vendor_id">
                    {{ vendor.name }}
                  </option>
                </select>
                <span v-if="validationErrors.vendor_id" class="error-message">{{ validationErrors.vendor_id[0] }}</span>
              </div>

              <div class="form-group">
                <label for="evaluation_date" class="required">Evaluation Date</label>
                <input
                  type="date"
                  id="evaluation_date"
                  v-model="evaluation.evaluation_date"
                  class="form-control"
                  :class="{ 'error': validationErrors.evaluation_date }"
                  required
                />
                <span v-if="validationErrors.evaluation_date" class="error-message">{{ validationErrors.evaluation_date[0] }}</span>
              </div>
            </div>
          </div>

          <div class="form-section scores">
            <h2 class="section-title">Performance Evaluation</h2>
            <div class="form-grid">
              <div class="form-group score-group">
                <label for="quality_score" class="required">Quality Score</label>
                <div class="score-label-description">Product/service quality, defect rates, adherence to specifications</div>
                <div class="score-input-wrapper">
                  <input
                    type="range"
                    id="quality_score"
                    v-model.number="evaluation.quality_score"
                    min="1"
                    max="5"
                    step="0.1"
                    class="score-slider"
                    :class="{ 'error': validationErrors.quality_score }"
                    @input="updateNumberDisplay('quality_score')"
                  />
                  <input
                    type="number"
                    v-model.number="evaluation.quality_score"
                    min="1"
                    max="5"
                    step="0.1"
                    class="score-number"
                    :class="{ 'error': validationErrors.quality_score }"
                    @input="validateScoreInput('quality_score')"
                  />
                  <div class="score-display" :class="getScoreClass(evaluation.quality_score)">
                    {{ evaluation.quality_score }}
                  </div>
                </div>
                <div class="score-labels">
                  <span>Poor</span>
                  <span>Excellent</span>
                </div>
                <span v-if="validationErrors.quality_score" class="error-message">{{ validationErrors.quality_score[0] }}</span>
              </div>

              <div class="form-group score-group">
                <label for="delivery_score" class="required">Delivery Score</label>
                <div class="score-label-description">On-time delivery, order accuracy, responsiveness</div>
                <div class="score-input-wrapper">
                  <input
                    type="range"
                    id="delivery_score"
                    v-model.number="evaluation.delivery_score"
                    min="1"
                    max="5"
                    step="0.1"
                    class="score-slider"
                    :class="{ 'error': validationErrors.delivery_score }"
                    @input="updateNumberDisplay('delivery_score')"
                  />
                  <input
                    type="number"
                    v-model.number="evaluation.delivery_score"
                    min="1"
                    max="5"
                    step="0.1"
                    class="score-number"
                    :class="{ 'error': validationErrors.delivery_score }"
                    @input="validateScoreInput('delivery_score')"
                  />
                  <div class="score-display" :class="getScoreClass(evaluation.delivery_score)">
                    {{ evaluation.delivery_score }}
                  </div>
                </div>
                <div class="score-labels">
                  <span>Poor</span>
                  <span>Excellent</span>
                </div>
                <span v-if="validationErrors.delivery_score" class="error-message">{{ validationErrors.delivery_score[0] }}</span>
              </div>

              <div class="form-group score-group">
                <label for="price_score" class="required">Price Score</label>
                <div class="score-label-description">Competitiveness, value for money, cost transparency</div>
                <div class="score-input-wrapper">
                  <input
                    type="range"
                    id="price_score"
                    v-model.number="evaluation.price_score"
                    min="1"
                    max="5"
                    step="0.1"
                    class="score-slider"
                    :class="{ 'error': validationErrors.price_score }"
                    @input="updateNumberDisplay('price_score')"
                  />
                  <input
                    type="number"
                    v-model.number="evaluation.price_score"
                    min="1"
                    max="5"
                    step="0.1"
                    class="score-number"
                    :class="{ 'error': validationErrors.price_score }"
                    @input="validateScoreInput('price_score')"
                  />
                  <div class="score-display" :class="getScoreClass(evaluation.price_score)">
                    {{ evaluation.price_score }}
                  </div>
                </div>
                <div class="score-labels">
                  <span>Poor</span>
                  <span>Excellent</span>
                </div>
                <span v-if="validationErrors.price_score" class="error-message">{{ validationErrors.price_score[0] }}</span>
              </div>

              <div class="form-group score-group">
                <label for="service_score" class="required">Service Score</label>
                <div class="score-label-description">Customer support, communication, problem resolution</div>
                <div class="score-input-wrapper">
                  <input
                    type="range"
                    id="service_score"
                    v-model.number="evaluation.service_score"
                    min="1"
                    max="5"
                    step="0.1"
                    class="score-slider"
                    :class="{ 'error': validationErrors.service_score }"
                    @input="updateNumberDisplay('service_score')"
                  />
                  <input
                    type="number"
                    v-model.number="evaluation.service_score"
                    min="1"
                    max="5"
                    step="0.1"
                    class="score-number"
                    :class="{ 'error': validationErrors.service_score }"
                    @input="validateScoreInput('service_score')"
                  />
                  <div class="score-display" :class="getScoreClass(evaluation.service_score)">
                    {{ evaluation.service_score }}
                  </div>
                </div>
                <div class="score-labels">
                  <span>Poor</span>
                  <span>Excellent</span>
                </div>
                <span v-if="validationErrors.service_score" class="error-message">{{ validationErrors.service_score[0] }}</span>
              </div>
            </div>

            <div class="total-score-container">
              <h3>Total Score</h3>
              <div class="total-score" :class="getScoreClass(calculateTotalScore)">
                {{ calculateTotalScore.toFixed(2) }}
              </div>
              <div class="score-description">
                {{ getScoreDescription(calculateTotalScore) }}
              </div>
            </div>
          </div>

          <div class="form-section evaluation-notes">
            <h2 class="section-title">Evaluation Notes</h2>
            <div class="form-group">
              <label for="notes">Notes (Optional)</label>
              <textarea
                id="notes"
                v-model="evaluation.notes"
                rows="4"
                class="form-control"
                placeholder="Enter any additional notes or observations about this vendor's performance"
              ></textarea>
            </div>

            <div class="form-group">
              <label for="strengths">Strengths (Optional)</label>
              <textarea
                id="strengths"
                v-model="evaluation.strengths"
                rows="3"
                class="form-control"
                placeholder="Enter vendor's key strengths or positive aspects"
              ></textarea>
            </div>

            <div class="form-group">
              <label for="weaknesses">Areas for Improvement (Optional)</label>
              <textarea
                id="weaknesses"
                v-model="evaluation.weaknesses"
                rows="3"
                class="form-control"
                placeholder="Enter areas where vendor could improve"
              ></textarea>
            </div>

            <div class="form-group">
              <label for="action_items">Action Items (Optional)</label>
              <textarea
                id="action_items"
                v-model="evaluation.action_items"
                rows="3"
                class="form-control"
                placeholder="List any follow-up actions needed with this vendor"
              ></textarea>
            </div>
          </div>

          <div class="form-section evaluator-info">
            <h2 class="section-title">Evaluator Information</h2>
            <div class="form-grid">
              <div class="form-group">
                <label for="evaluator_name">Evaluator Name</label>
                <input
                  type="text"
                  id="evaluator_name"
                  v-model="evaluation.evaluator_name"
                  class="form-control"
                  placeholder="Name of person conducting evaluation"
                />
              </div>

              <div class="form-group">
                <label for="evaluator_position">Position/Department</label>
                <input
                  type="text"
                  id="evaluator_position"
                  v-model="evaluation.evaluator_position"
                  class="form-control"
                  placeholder="Position or department of evaluator"
                />
              </div>

              <div class="form-group">
                <label for="next_evaluation_date">Next Evaluation Date (Optional)</label>
                <input
                  type="date"
                  id="next_evaluation_date"
                  v-model="evaluation.next_evaluation_date"
                  class="form-control"
                />
              </div>

              <div class="form-group">
                <label for="status">Evaluation Status</label>
                <select id="status" v-model="evaluation.status" class="form-control">
                  <option value="draft">Draft</option>
                  <option value="completed">Completed</option>
                  <option value="reviewed">Reviewed</option>
                  <option value="shared">Shared with Vendor</option>
                </select>
              </div>
            </div>
          </div>

          <div class="form-actions">
            <button type="button" @click="goBack" class="btn-outline">Cancel</button>
            <button type="submit" class="btn-primary" :disabled="loading">
              <i class="fas fa-spinner fa-spin mr-2" v-if="loading"></i>
              {{ isEditMode ? 'Update Evaluation' : 'Save Evaluation' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </template>

  <script>
  import axios from "axios";

  export default {
    name: "VendorEvaluationForm",
    props: {
      id: {
        type: [Number, String],
        required: false
      },
      editMode: {
        type: Boolean,
        default: false
      }
    },
    data() {
      return {
        evaluation: {
          vendor_id: "",
          evaluation_date: new Date().toISOString().split('T')[0],
          quality_score: 3.0,
          delivery_score: 3.0,
          price_score: 3.0,
          service_score: 3.0,
          notes: "",
          strengths: "",
          weaknesses: "",
          action_items: "",
          evaluator_name: "",
          evaluator_position: "",
          next_evaluation_date: "",
          status: "draft"
        },
        vendors: [],
        loading: false,
        validationErrors: {},
        formSubmitted: false
      };
    },
    computed: {
      isEditMode() {
        return !!this.id || this.editMode;
      },
      calculateTotalScore() {
        const { quality_score, delivery_score, price_score, service_score } = this.evaluation;
        return (parseFloat(quality_score) + parseFloat(delivery_score) + parseFloat(price_score) + parseFloat(service_score)) / 4;
      }
    },
    created() {
      this.loadVendors();

      // Check if vendor_id is provided in query params (for prepopulating from other pages)
      const vendorId = this.$route.query.vendor_id;
      if (vendorId && !this.isEditMode) {
        this.evaluation.vendor_id = vendorId;
      }

      if (this.isEditMode) {
        this.loadEvaluation();
      }
    },
    methods: {
      async loadVendors() {
        try {
          const response = await axios.get("/api/vendors");
          this.vendors = response.data.data;
        } catch (error) {
          console.error("Error loading vendors:", error);
          this.showErrorNotification("Failed to load vendors. Please try refreshing the page.");
        }
      },

      async loadEvaluation() {
        this.loading = true;
        try {
          const response = await axios.get(`/api/vendor-evaluations/${this.id}`);
          const evaluation = response.data.data;

          // Map from API to form model
          this.evaluation = {
            vendor_id: evaluation.vendor_id,
            evaluation_date: evaluation.evaluation_date ? new Date(evaluation.evaluation_date).toISOString().split('T')[0] : '',
            quality_score: evaluation.quality_score,
            delivery_score: evaluation.delivery_score,
            price_score: evaluation.price_score,
            service_score: evaluation.service_score,
            notes: evaluation.notes || "",
            strengths: evaluation.strengths || "",
            weaknesses: evaluation.weaknesses || "",
            action_items: evaluation.action_items || "",
            evaluator_name: evaluation.evaluator_name || "",
            evaluator_position: evaluation.evaluator_position || "",
            next_evaluation_date: evaluation.next_evaluation_date ? new Date(evaluation.next_evaluation_date).toISOString().split('T')[0] : "",
            status: evaluation.status || "draft"
          };
        } catch (error) {
          console.error("Error loading evaluation:", error);
          this.showErrorNotification("Failed to load evaluation data. Please try again later.");
        } finally {
          this.loading = false;
        }
      },

      getScoreClass(score) {
        score = parseFloat(score);
        if (score >= 4) return 'score-excellent';
        if (score >= 3) return 'score-good';
        if (score >= 2) return 'score-average';
        return 'score-poor';
      },

      getScoreDescription(score) {
        score = parseFloat(score);
        if (score >= 4.5) return 'Outstanding performance, exceeds expectations';
        if (score >= 4) return 'Excellent performance, above expectations';
        if (score >= 3.5) return 'Very good performance, meets all expectations';
        if (score >= 3) return 'Good performance, meets most expectations';
        if (score >= 2.5) return 'Average performance, meets basic expectations';
        if (score >= 2) return 'Below average performance, improvement needed';
        if (score >= 1.5) return 'Poor performance, significant improvement required';
        return 'Unsatisfactory performance, consider replacing vendor';
      },

      validateScoreInput(field) {
        let value = parseFloat(this.evaluation[field]);
        if (isNaN(value)) {
          this.evaluation[field] = 3.0;
        } else if (value < 1) {
          this.evaluation[field] = 1.0;
        } else if (value > 5) {
          this.evaluation[field] = 5.0;
        } else {
          // Round to one decimal place
          this.evaluation[field] = Math.round(value * 10) / 10;
        }
      },

      updateNumberDisplay(field) {
        // Ensure the number input is updated when using the slider
        this.evaluation[field] = parseFloat(this.evaluation[field]).toFixed(1);
      },

      validateForm() {
        this.validationErrors = {};
        let isValid = true;

        // Required fields validation
        if (!this.evaluation.vendor_id) {
          this.validationErrors.vendor_id = ['Please select a vendor'];
          isValid = false;
        }

        if (!this.evaluation.evaluation_date) {
          this.validationErrors.evaluation_date = ['Please enter an evaluation date'];
          isValid = false;
        }

        // Score validations
        ['quality_score', 'delivery_score', 'price_score', 'service_score'].forEach(field => {
          const value = parseFloat(this.evaluation[field]);
          if (isNaN(value) || value < 1 || value > 5) {
            this.validationErrors[field] = ['Score must be between 1 and 5'];
            isValid = false;
          }
        });

        return isValid;
      },

      async saveEvaluation() {
        if (!this.validateForm()) {
          this.showErrorNotification("Please correct the errors in the form");
          return;
        }

        this.loading = true;
        this.formSubmitted = true;

        try {
          //let response;

          // Prepare the payload
          const payload = {
            vendor_id: this.evaluation.vendor_id,
            evaluation_date: this.evaluation.evaluation_date,
            quality_score: parseFloat(this.evaluation.quality_score),
            delivery_score: parseFloat(this.evaluation.delivery_score),
            price_score: parseFloat(this.evaluation.price_score),
            service_score: parseFloat(this.evaluation.service_score),
            notes: this.evaluation.notes,
            strengths: this.evaluation.strengths,
            weaknesses: this.evaluation.weaknesses,
            action_items: this.evaluation.action_items,
            evaluator_name: this.evaluation.evaluator_name,
            evaluator_position: this.evaluation.evaluator_position,
            next_evaluation_date: this.evaluation.next_evaluation_date || null,
            status: this.evaluation.status
          };

          if (this.isEditMode) {
            await axios.put(`/api/vendor-evaluations/${this.id}`, payload);
            this.showSuccessNotification("Evaluation updated successfully");
          } else {
            await axios.post('/api/vendor-evaluations', payload);
            this.showSuccessNotification("Evaluation created successfully");
          }

          // Navigate back to list with success message
          this.$router.push({
            path: '/purchasing/evaluations',
            query: {
              success: true,
              message: this.isEditMode ? 'Evaluation updated successfully' : 'Evaluation created successfully'
            }
          });
        } catch (error) {
          console.error("Error saving evaluation:", error);

          if (error.response && error.response.status === 422) {
            // Validation errors from backend
            this.validationErrors = error.response.data.errors || {};
            this.showErrorNotification("Please correct the errors in the form");
          } else {
            // General error handling
            this.showErrorNotification("Failed to save evaluation. Please try again later.");
          }
        } finally {
          this.loading = false;
        }
      },

      goBack() {
        // Check if form has changes before navigation
        if (this.formSubmitted) {
          this.$router.go(-1);
          return;
        }

        const confirmLeave = confirm("Are you sure you want to leave? Any unsaved changes will be lost.");
        if (confirmLeave) {
          this.$router.go(-1);
        }
      },

      // These notification methods would be replaced with your actual notification system
      showSuccessNotification(message) {
        // Implementation depends on your notification system
        console.log('Success:', message);
        // Example: this.$toast.success(message);
      },

      showErrorNotification(message) {
        // Implementation depends on your notification system
        console.error('Error:', message);
        // Example: this.$toast.error(message);
      }
    }
  };
  </script>

  <style scoped>
  .evaluation-form-container {
    width: 100%;
    padding: 1rem;
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  }

  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
  }

  .form-panel {
    background-color: white;
    border-radius: 0.5rem;
  }

  .form-section {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background-color: var(--gray-50);
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  }

  .section-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 1.25rem;
    color: var(--gray-800);
    border-bottom: 1px solid var(--gray-200);
    padding-bottom: 0.5rem;
  }

  .form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
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

  .form-group label.required::after {
    content: '*';
    color: var(--danger-color);
    margin-left: 0.25rem;
  }

  .score-label-description {
    font-size: 0.75rem;
    color: var(--gray-500);
    margin-bottom: 0.5rem;
    line-height: 1.4;
  }

  .form-control {
    width: 100%;
    padding: 0.625rem;
    border: 1px solid var(--gray-300);
    border-radius: 0.375rem;
    font-size: 0.875rem;
    transition: border-color 0.2s, box-shadow 0.2s;
    background-color: white;
  }

  .form-control:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
  }

  .form-control.error {
    border-color: var(--danger-color);
  }

  .error-message {
    display: block;
    color: var(--danger-color);
    font-size: 0.75rem;
    margin-top: 0.25rem;
  }

  textarea.form-control {
    min-height: 100px;
    resize: vertical;
  }

  .score-group {
    position: relative;
  }

  .score-input-wrapper {
    display: flex;
    align-items: center;
    gap: 1rem;
  }

  .score-slider {
    flex: 1;
    height: 8px;
    -webkit-appearance: none;
    background: linear-gradient(to right, var(--danger-color), var(--warning-color), var(--success-color));
    border-radius: 4px;
    outline: none;
  }

  .score-slider::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: white;
    border: 2px solid var(--primary-color);
    cursor: pointer;
  }

  .score-slider::-moz-range-thumb {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: white;
    border: 2px solid var(--primary-color);
    cursor: pointer;
  }

  .score-number {
    width: 60px;
    text-align: center;
    border: 1px solid var(--gray-300);
    border-radius: 0.375rem;
    padding: 0.375rem;
    font-size: 0.875rem;
  }

  .score-display {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    color: white;
    font-weight: 600;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  }

  .score-labels {
    display: flex;
    justify-content: space-between;
    font-size: 0.75rem;
    color: var(--gray-600);
    margin-top: 0.25rem;
  }

  .total-score-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 1.5rem;
    margin-top: 1.5rem;
    background-color: white;
    border-radius: 0.375rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  }

  .total-score-container h3 {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: var(--gray-800);
  }

  .total-score {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 80px;
    height: 80px;
    border-radius: 50%;
    color: white;
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  }

  .score-description {
    text-align: center;
    font-style: italic;
    color: var(--gray-700);
    max-width: 400px;
  }

  .form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 2rem;
    padding: 1rem 0;
  }

  .btn-outline {
    background-color: transparent;
    color: var(--gray-700);
    padding: 0.625rem 1.25rem;
    border: 1px solid var(--gray-300);
    border-radius: 0.375rem;
    font-weight: 500;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
  }

  .btn-outline:hover {
    border-color: var(--gray-500);
    color: var(--gray-800);
    text-decoration: none;
  }

  .btn-primary {
    display: inline-flex;
    align-items: center;
    background-color: var(--primary-color);
    color: white;
    padding: 0.625rem 1.25rem;
    border-radius: 0.375rem;
    font-weight: 500;
    transition: background-color 0.2s;
    border: none;
  }

  .btn-primary:hover {
    background-color: var(--primary-dark);
    text-decoration: none;
  }

  .btn-primary:disabled {
    opacity: 0.7;
    cursor: not-allowed;
  }

  /* Score colors */
  .score-excellent {
    background-color: var(--success-color);
  }

  .score-good {
    background-color: #3b82f6; /* Blue */
  }

  .score-average {
    background-color: var(--warning-color);
  }

  .score-poor {
    background-color: var(--danger-color);
  }

  /* Added hover effects */
  .form-control:hover:not(:focus):not(.error) {
    border-color: var(--gray-400);
  }

  .score-slider:hover::-webkit-slider-thumb {
    transform: scale(1.1);
  }

  .score-slider:hover::-moz-range-thumb {
    transform: scale(1.1);
  }

  /* Additional styles for enhanced sections */
  .evaluator-info {
    background-color: rgba(37, 99, 235, 0.05);
    border-left: 4px solid var(--primary-color);
  }

  .evaluation-notes {
    background-color: rgba(5, 150, 105, 0.05);
    border-left: 4px solid var(--success-color);
  }

  /* Animation for saving action */
  @keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
  }

  .btn-primary:not(:disabled):active {
    animation: pulse 0.3s ease;
  }

  /* Responsive adjustments */
  @media (max-width: 768px) {
    .form-grid {
      grid-template-columns: 1fr;
    }

    .form-actions {
      flex-direction: column;
      padding: 1rem;
    }

    .btn-outline, .btn-primary {
      width: 100%;
      justify-content: center;
    }

    .page-header {
      flex-direction: column;
      align-items: flex-start;
      gap: 1rem;
    }

    .score-input-wrapper {
      flex-wrap: wrap;
    }

    .total-score-container {
      padding: 1rem;
    }
  }

  /* Print-friendly styles */
  @media print {
    .evaluation-form-container {
      padding: 0;
      box-shadow: none;
    }

    .form-section {
      break-inside: avoid;
      page-break-inside: avoid;
      background-color: white;
      box-shadow: none;
      border: 1px solid #ddd;
    }

    .page-header {
      margin-bottom: 1rem;
    }

    .actions, .form-actions {
      display: none;
    }

    .score-slider, .score-number {
      display: none;
    }

    .score-display {
      width: 30px;
      height: 30px;
      font-size: 0.875rem;
    }

    .total-score {
      width: 50px;
      height: 50px;
      font-size: 1.25rem;
    }

    .score-label-description {
      color: black;
    }

    .section-title {
      color: black;
      border-bottom-color: #000;
    }
  }
  </style>
