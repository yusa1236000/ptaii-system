<!-- src/views/accounting/BankReconciliationForm.vue -->
<template>
    <div class="bank-reconciliation-form">
      <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
          <h2>{{ isEditing ? 'Edit Bank Reconciliation' : 'New Bank Reconciliation' }}</h2>
          <router-link to="/accounting/bank-reconciliations" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i> Back to Reconciliations
          </router-link>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <div v-if="isLoading" class="text-center py-5">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p class="mt-2">Loading data...</p>
          </div>

          <form v-else @submit.prevent="saveReconciliation">
            <div class="alert alert-info mb-4">
              <div class="d-flex">
                <i class="fas fa-info-circle align-self-start mt-1 mr-3"></i>
                <p class="mb-0">
                  Bank reconciliation is the process of matching the balances in your accounting records with the corresponding information on your bank statement. This helps identify any differences between these two records.
                </p>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="bank_id">Bank Account <span class="text-danger">*</span></label>
                  <select
                    id="bank_id"
                    v-model="form.bank_id"
                    class="form-control"
                    :class="{ 'is-invalid': !!errors.bank_id }"
                    :disabled="isEditing"
                    required
                  >
                    <option value="">Select Bank Account</option>
                    <option v-for="bank in bankAccounts" :key="bank.bank_id" :value="bank.bank_id">
                      {{ bank.bank_name }} - {{ bank.account_number }}
                    </option>
                  </select>
                  <div v-if="errors.bank_id" class="invalid-feedback">
                    {{ errors.bank_id[0] }}
                  </div>
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-group">
                  <label for="statement_date">Statement Date <span class="text-danger">*</span></label>
                  <input
                    type="date"
                    id="statement_date"
                    v-model="form.statement_date"
                    class="form-control"
                    :class="{ 'is-invalid': !!errors.statement_date }"
                    required
                  >
                  <div v-if="errors.statement_date" class="invalid-feedback">
                    {{ errors.statement_date[0] }}
                  </div>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="statement_balance">Statement Balance <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text">Rp</span>
                    </div>
                    <input
                      type="number"
                      id="statement_balance"
                      v-model="form.statement_balance"
                      class="form-control"
                      :class="{ 'is-invalid': !!errors.statement_balance }"
                      step="0.01"
                      min="0"
                      required
                    >
                    <div v-if="errors.statement_balance" class="invalid-feedback">
                      {{ errors.statement_balance[0] }}
                    </div>
                  </div>
                  <small class="form-text text-muted">
                    The ending balance as shown on your bank statement.
                  </small>
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-group">
                  <label for="book_balance">Book Balance <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text">Rp</span>
                    </div>
                    <input
                      type="number"
                      id="book_balance"
                      v-model="form.book_balance"
                      class="form-control"
                      :class="{ 'is-invalid': !!errors.book_balance }"
                      step="0.01"
                      min="0"
                      required
                    >
                    <div v-if="errors.book_balance" class="invalid-feedback">
                      {{ errors.book_balance[0] }}
                    </div>
                  </div>
                  <small class="form-text text-muted">
                    The current balance in your accounting system.
                  </small>
                </div>
              </div>
            </div>

            <div class="form-group">
              <label for="status">Status</label>
              <select
                id="status"
                v-model="form.status"
                class="form-control"
                :class="{ 'is-invalid': !!errors.status }"
              >
                <option value="Draft">Draft</option>
                <option value="In Progress">In Progress</option>
                <option value="Finalized">Finalized</option>
              </select>
              <div v-if="errors.status" class="invalid-feedback">
                {{ errors.status[0] }}
              </div>
              <small class="form-text text-muted">
                Once a reconciliation is finalized, it cannot be modified.
              </small>
            </div>

            <div class="row align-items-center mb-3 mt-4">
              <div class="col">
                <h4 class="mb-0">Balance Summary</h4>
              </div>
            </div>

            <div class="balance-summary mb-4">
              <div class="row">
                <div class="col-md-4">
                  <div class="summary-card">
                    <div class="summary-title">Statement Balance</div>
                    <div class="summary-value">{{ formatCurrency(form.statement_balance) }}</div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="summary-card">
                    <div class="summary-title">Book Balance</div>
                    <div class="summary-value">{{ formatCurrency(form.book_balance) }}</div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="summary-card" :class="{ 'is-balanced': isDifferencePresentable && difference === 0 }">
                    <div class="summary-title">Difference</div>
                    <div class="summary-value" :class="{ 'text-danger': isDifferencePresentable && difference !== 0, 'text-success': isDifferencePresentable && difference === 0 }">
                      {{ formatCurrency(difference) }}
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="form-actions">
              <button type="submit" class="btn btn-primary" :disabled="isSaving">
                <i v-if="isSaving" class="fas fa-spinner fa-spin mr-2"></i>
                <i v-else class="fas fa-save mr-2"></i>
                {{ isSaving ? 'Saving...' : 'Save Reconciliation' }}
              </button>
              <router-link to="/accounting/bank-reconciliations" class="btn btn-secondary ml-2">
                Cancel
              </router-link>
              <div v-if="isEditing && form.status !== 'Finalized'" class="float-right">
                <router-link :to="`/accounting/bank-reconciliations/${reconciliationId}/match`" class="btn btn-success">
                  <i class="fas fa-exchange-alt mr-2"></i> Match Transactions
                </router-link>
                <router-link :to="`/accounting/bank-reconciliations/${reconciliationId}/finalize`" class="btn btn-warning ml-2">
                  <i class="fas fa-check-circle mr-2"></i> Finalize
                </router-link>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </template>

  <script>
  import { ref, computed, onMounted, watch } from 'vue';
  import { useRoute, useRouter } from 'vue-router';
  import axios from 'axios';

  export default {
    name: 'BankReconciliationForm',
    setup() {
      const route = useRoute();
      const router = useRouter();
      const reconciliationId = computed(() => route.params.id);
      const isEditing = computed(() => !!reconciliationId.value);

      // State
      const bankAccounts = ref([]);
      const isLoading = ref(true);
      const isSaving = ref(false);
      const errors = ref({});

      const form = ref({
        bank_id: '',
        statement_date: new Date().toISOString().substr(0, 10), // Today's date in YYYY-MM-DD format
        statement_balance: '',
        book_balance: '',
        status: 'Draft'
      });

      // Computed properties
      const difference = computed(() => {
        const statementBalance = parseFloat(form.value.statement_balance) || 0;
        const bookBalance = parseFloat(form.value.book_balance) || 0;
        return statementBalance - bookBalance;
      });

      const isDifferencePresentable = computed(() => {
        return form.value.statement_balance !== '' && form.value.book_balance !== '';
      });

      // Methods
      const fetchBankAccounts = async () => {
        try {
          const response = await axios.get('/api/accounting/bank-accounts');
          bankAccounts.value = response.data.data;
        } catch (error) {
          console.error('Error fetching bank accounts:', error);
          // TODO: Show error toast message
        }
      };

      const fetchReconciliation = async () => {
        if (!isEditing.value) {
          isLoading.value = false;
          return;
        }

        try {
          const response = await axios.get(`/api/accounting/bank-reconciliations/${reconciliationId.value}`);
          const reconciliation = response.data.data;

          form.value = {
            bank_id: reconciliation.bank_id,
            statement_date: reconciliation.statement_date,
            statement_balance: reconciliation.statement_balance,
            book_balance: reconciliation.book_balance,
            status: reconciliation.status
          };
        } catch (error) {
          console.error('Error fetching reconciliation:', error);
          // TODO: Show error toast message
        } finally {
          isLoading.value = false;
        }
      };

      const saveReconciliation = async () => {
        isSaving.value = true;
        errors.value = {};

        try {
          let response;

          if (isEditing.value) {
            response = await axios.put(`/api/accounting/bank-reconciliations/${reconciliationId.value}`, form.value);
          } else {
            response = await axios.post('/api/accounting/bank-reconciliations', form.value);
          }

          // Redirect to the reconciliation detail page
          const reconciliation = response.data.data;
          router.push(`/accounting/bank-reconciliations/${reconciliation.reconciliation_id}`);
        } catch (error) {
          console.error('Error saving reconciliation:', error);

          if (error.response && error.response.data && error.response.data.errors) {
            errors.value = error.response.data.errors;
          } else {
            // TODO: Show general error toast message
          }
        } finally {
          isSaving.value = false;
        }
      };

      const formatCurrency = (amount) => {
        return new Intl.NumberFormat('id-ID', {
          style: 'currency',
          currency: 'IDR',
          minimumFractionDigits: 0,
          maximumFractionDigits: 0
        }).format(amount || 0);
      };

      // Watchers
      watch(() => form.value.bank_id, async (newValue) => {
        if (!newValue || isEditing.value) return;

        // Get the current balance from the selected bank account
        const selectedBank = bankAccounts.value.find(bank => bank.bank_id === parseInt(newValue));
        if (selectedBank) {
          form.value.book_balance = selectedBank.current_balance;
        }
      });

      // Lifecycle hooks
      onMounted(async () => {
        await fetchBankAccounts();
        await fetchReconciliation();
      });

      return {
        isEditing,
        reconciliationId,
        bankAccounts,
        isLoading,
        isSaving,
        errors,
        form,
        difference,
        isDifferencePresentable,
        saveReconciliation,
        formatCurrency
      };
    }
  };
  </script>

  <style scoped>
  .bank-reconciliation-form {
    padding: 1rem 0;
  }

  .form-actions {
    padding-top: 1.5rem;
    margin-top: 1.5rem;
    border-top: 1px solid var(--gray-200);
  }

  .balance-summary {
    margin-bottom: 2rem;
  }

  .summary-card {
    background-color: var(--gray-50);
    border-radius: 0.5rem;
    padding: 1rem;
    height: 100%;
    border: 1px solid var(--gray-200);
  }

  .summary-card.is-balanced {
    background-color: var(--success-bg);
    border-color: var(--success-color);
  }

  .summary-title {
    font-size: 0.875rem;
    color: var(--gray-600);
    margin-bottom: 0.5rem;
  }

  .summary-value {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--gray-800);
  }

  .text-danger {
    color: var(--danger-color) !important;
  }

  .text-success {
    color: var(--success-color) !important;
  }
  </style>
