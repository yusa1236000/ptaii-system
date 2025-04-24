<!-- src/views/accounting/JournalEntryForm.vue -->
<template>
    <div class="journal-entry-form">
      <div class="card">
        <div class="card-header">
          <h3>{{ isEditing ? 'Edit Journal Entry' : 'Create Journal Entry' }}</h3>
        </div>

        <div class="card-body">
          <div v-if="isLoading" class="loading-indicator">
            <i class="fas fa-spinner fa-spin"></i> Loading...
          </div>

          <form v-else @submit.prevent="saveJournalEntry">
            <div class="row mb-4">
              <div class="col-md-4">
                <div class="form-group">
                  <label for="journalNumber">Journal Number</label>
                  <input
                    type="text"
                    id="journalNumber"
                    v-model="journalEntry.journal_number"
                    class="form-control"
                    :class="{ 'is-invalid': v$.journal_number.$error }"
                    :disabled="isEditing"
                    required
                  >
                  <div v-if="v$.journal_number.$error" class="invalid-feedback">
                    {{ v$.journal_number.$errors[0].$message }}
                  </div>
                </div>
              </div>

              <div class="col-md-4">
                <div class="form-group">
                  <label for="entryDate">Entry Date</label>
                  <input
                    type="date"
                    id="entryDate"
                    v-model="journalEntry.entry_date"
                    class="form-control"
                    :class="{ 'is-invalid': v$.entry_date.$error }"
                    required
                  >
                  <div v-if="v$.entry_date.$error" class="invalid-feedback">
                    {{ v$.entry_date.$errors[0].$message }}
                  </div>
                </div>
              </div>

              <div class="col-md-4">
                <div class="form-group">
                  <label for="periodId">Accounting Period</label>
                  <select
                    id="periodId"
                    v-model="journalEntry.period_id"
                    class="form-control"
                    :class="{ 'is-invalid': v$.period_id.$error }"
                    required
                  >
                    <option v-for="period in periods" :key="period.period_id" :value="period.period_id">
                      {{ period.period_name }}
                    </option>
                  </select>
                  <div v-if="v$.period_id.$error" class="invalid-feedback">
                    {{ v$.period_id.$errors[0].$message }}
                  </div>
                </div>
              </div>
            </div>

            <div class="row mb-4">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="referenceType">Reference Type</label>
                  <input
                    type="text"
                    id="referenceType"
                    v-model="journalEntry.reference_type"
                    class="form-control"
                    placeholder="E.g., Invoice, Payment, etc."
                  >
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-group">
                  <label for="referenceId">Reference ID</label>
                  <input
                    type="number"
                    id="referenceId"
                    v-model="journalEntry.reference_id"
                    class="form-control"
                    placeholder="Reference document ID"
                  >
                </div>
              </div>
            </div>

            <div class="form-group mb-4">
              <label for="description">Description</label>
              <textarea
                id="description"
                v-model="journalEntry.description"
                class="form-control"
                :class="{ 'is-invalid': v$.description.$error }"
                rows="3"
                required
              ></textarea>
              <div v-if="v$.description.$error" class="invalid-feedback">
                {{ v$.description.$errors[0].$message }}
              </div>
            </div>

            <div class="journal-lines">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h4>Journal Entry Lines</h4>
                <button type="button" class="btn btn-outline-primary" @click="addLine">
                  <i class="fas fa-plus mr-1"></i> Add Line
                </button>
              </div>

              <div class="table-responsive">
                <table class="table table-bordered">
                  <thead class="bg-light">
                    <tr>
                      <th width="35%">Account</th>
                      <th width="15%">Debit</th>
                      <th width="15%">Credit</th>
                      <th>Description</th>
                      <th width="60px">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(line, index) in journalEntry.lines" :key="index">
                      <td>
                        <select
                          v-model="line.account_id"
                          class="form-control"
                          :class="{ 'is-invalid': hasLineError(index, 'account_id') }"
                          required
                        >
                          <option value="">Select an account</option>
                          <optgroup
                            v-for="group in accountsByType"
                            :key="group.type"
                            :label="group.type"
                          >
                            <option
                              v-for="account in group.accounts"
                              :key="account.account_id"
                              :value="account.account_id"
                            >
                              {{ account.account_code }} - {{ account.name }}
                            </option>
                          </optgroup>
                        </select>
                      </td>
                      <td>
                        <input
                          type="number"
                          v-model="line.debit_amount"
                          class="form-control text-right"
                          :class="{ 'is-invalid': hasLineError(index, 'debit_amount') }"
                          step="0.01"
                          min="0"
                          @input="updateTotals"
                          @focus="clearOtherAmount(line, 'credit_amount')"
                          placeholder="0.00"
                        >
                      </td>
                      <td>
                        <input
                          type="number"
                          v-model="line.credit_amount"
                          class="form-control text-right"
                          :class="{ 'is-invalid': hasLineError(index, 'credit_amount') }"
                          step="0.01"
                          min="0"
                          @input="updateTotals"
                          @focus="clearOtherAmount(line, 'debit_amount')"
                          placeholder="0.00"
                        >
                      </td>
                      <td>
                        <input
                          type="text"
                          v-model="line.description"
                          class="form-control"
                          placeholder="Line description"
                        >
                      </td>
                      <td class="text-center">
                        <button
                          type="button"
                          class="btn btn-sm btn-danger"
                          @click="removeLine(index)"
                          :disabled="journalEntry.lines.length <= 2"
                        >
                          <i class="fas fa-trash"></i>
                        </button>
                      </td>
                    </tr>

                    <!-- Totals row -->
                    <tr class="bg-light">
                      <td class="text-right font-weight-bold">Totals</td>
                      <td class="text-right font-weight-bold">{{ formatAmount(totalDebit) }}</td>
                      <td class="text-right font-weight-bold">{{ formatAmount(totalCredit) }}</td>
                      <td colspan="2">
                        <div v-if="!isBalanced" class="text-danger font-weight-bold">
                          Out of balance: {{ formatAmount(Math.abs(totalDebit - totalCredit)) }}
                        </div>
                        <div v-else class="text-success font-weight-bold">
                          Balanced
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div v-if="lineErrors.length > 0" class="alert alert-danger mt-3">
                <ul class="mb-0">
                  <li v-for="(error, index) in lineErrors" :key="index">{{ error }}</li>
                </ul>
              </div>
            </div>

            <div class="form-group mt-4">
              <div class="d-flex justify-content-between">
                <router-link to="/accounting/journal-entries" class="btn btn-secondary">
                  <i class="fas fa-arrow-left mr-1"></i> Cancel
                </router-link>

                <div>
                  <button
                    type="submit"
                    class="btn btn-primary"
                    :disabled="isSaving || !isBalanced || v$.$invalid"
                  >
                    <i class="fas fa-save mr-1"></i>
                    {{ isSaving ? 'Saving...' : 'Save Journal Entry' }}
                  </button>

                  <button
                    v-if="isBalanced && !v$.$invalid"
                    type="button"
                    class="btn btn-success ml-2"
                    @click="saveAndPost"
                    :disabled="isSaving"
                  >
                    <i class="fas fa-check mr-1"></i>
                    {{ isSaving ? 'Processing...' : 'Save & Post' }}
                  </button>
                </div>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </template>

  <script>
  import { ref, reactive, computed, onMounted, watch } from 'vue';
  import { useRoute, useRouter } from 'vue-router';
  import { useVuelidate } from '@vuelidate/core';
  import { required, minLength, helpers } from '@vuelidate/validators';
  import axios from 'axios';

  export default {
    name: 'JournalEntryForm',
    setup() {
      const route = useRoute();
      const router = useRouter();
      const isEditing = computed(() => route.params.id !== undefined);

      // State
      const journalEntry = reactive({
        journal_number: '',
        entry_date: new Date().toISOString().substr(0, 10),
        reference_type: '',
        reference_id: null,
        description: '',
        period_id: '',
        status: 'Draft',
        lines: [
          { account_id: '', debit_amount: null, credit_amount: null, description: '' },
          { account_id: '', debit_amount: null, credit_amount: null, description: '' }
        ]
      });

      const periods = ref([]);
      const accounts = ref([]);
      const lineErrors = ref([]);
      const isLoading = ref(false);
      const isSaving = ref(false);
      const totalDebit = ref(0);
      const totalCredit = ref(0);

      // Validation rules
      const rules = {
        journal_number: {
          required: helpers.withMessage('Journal number is required', required),
          minLength: helpers.withMessage('Journal number must be at least 3 characters', minLength(3))
        },
        entry_date: { required: helpers.withMessage('Entry date is required', required) },
        description: { required: helpers.withMessage('Description is required', required) },
        period_id: { required: helpers.withMessage('Accounting period is required', required) }
      };

      const v$ = useVuelidate(rules, journalEntry);

      // Computed properties
      const isBalanced = computed(() => {
        return Math.abs(totalDebit.value - totalCredit.value) < 0.01;
      });

      const accountsByType = computed(() => {
        const groupedAccounts = accounts.value.reduce((groups, account) => {
          const type = account.account_type;
          if (!groups[type]) {
            groups[type] = [];
          }
          groups[type].push(account);
          return groups;
        }, {});

        return Object.keys(groupedAccounts).map(type => ({
          type,
          accounts: groupedAccounts[type].sort((a, b) => a.account_code.localeCompare(b.account_code))
        }));
      });

      // Methods
      const loadData = async () => {
        isLoading.value = true;
        try {
          // Load accounting periods
          const periodsResponse = await axios.get('/api/accounting/accounting-periods');
          periods.value = periodsResponse.data.data;

          // Set default period to current period if available
          const currentPeriod = periods.value.find(p => p.status === 'Open');
          if (currentPeriod && !journalEntry.period_id) {
            journalEntry.period_id = currentPeriod.period_id;
          }

          // Load chart of accounts
          const accountsResponse = await axios.get('/api/accounting/chart-of-accounts');
          accounts.value = accountsResponse.data.data.filter(account => account.is_active);

          // If editing, load journal entry data
          if (isEditing.value) {
            const response = await axios.get(`/api/accounting/journal-entries/${route.params.id}`);
            const entry = response.data.data;

            // Populate form
            journalEntry.journal_number = entry.journal_number;
            journalEntry.entry_date = entry.entry_date;
            journalEntry.reference_type = entry.reference_type || '';
            journalEntry.reference_id = entry.reference_id;
            journalEntry.description = entry.description;
            journalEntry.period_id = entry.period_id;
            journalEntry.status = entry.status;

            // Populate lines
            if (entry.journal_entry_lines && entry.journal_entry_lines.length > 0) {
              journalEntry.lines = entry.journal_entry_lines.map(line => ({
                line_id: line.line_id,
                account_id: line.account_id,
                debit_amount: parseFloat(line.debit_amount) || null,
                credit_amount: parseFloat(line.credit_amount) || null,
                description: line.description || ''
              }));
            }

            updateTotals();
          }
        } catch (error) {
          console.error('Error loading data:', error);
          // TODO: Show error notification
        } finally {
          isLoading.value = false;
        }
      };

      const addLine = () => {
        journalEntry.lines.push({ account_id: '', debit_amount: null, credit_amount: null, description: '' });
      };

      const removeLine = (index) => {
        if (journalEntry.lines.length > 2) {
          journalEntry.lines.splice(index, 1);
          updateTotals();
        }
      };

      const clearOtherAmount = (line, fieldToClear) => {
        if (fieldToClear === 'debit_amount' && line.debit_amount === null) {
          line.debit_amount = null;
        } else if (fieldToClear === 'credit_amount' && line.credit_amount === null) {
          line.credit_amount = null;
        }
      };

      const updateTotals = () => {
        totalDebit.value = journalEntry.lines.reduce((sum, line) => {
          return sum + (parseFloat(line.debit_amount) || 0);
        }, 0);

        totalCredit.value = journalEntry.lines.reduce((sum, line) => {
          return sum + (parseFloat(line.credit_amount) || 0);
        }, 0);

        validateLines();
      };

      const validateLines = () => {
        lineErrors.value = [];

        // Check if there are at least two lines
        if (journalEntry.lines.length < 2) {
          lineErrors.value.push('Journal entry must have at least two lines');
        }

        // Validate each line
        journalEntry.lines.forEach((line, index) => {
          if (!line.account_id) {
            lineErrors.value.push(`Line ${index + 1}: Account is required`);
          }

          if ((!line.debit_amount && !line.credit_amount) ||
              (parseFloat(line.debit_amount) === 0 && parseFloat(line.credit_amount) === 0)) {
            lineErrors.value.push(`Line ${index + 1}: Either debit or credit amount must be greater than zero`);
          }

          if (parseFloat(line.debit_amount) > 0 && parseFloat(line.credit_amount) > 0) {
            lineErrors.value.push(`Line ${index + 1}: Cannot have both debit and credit amounts`);
          }
        });

        // Check if debits equal credits
        if (Math.abs(totalDebit.value - totalCredit.value) > 0.01) {
          lineErrors.value.push(`Journal entry is out of balance by ${formatAmount(Math.abs(totalDebit.value - totalCredit.value))}`);
        }
      };

      const hasLineError = (index, field) => {
        return lineErrors.value.some(error => error.includes(`Line ${index + 1}`) && error.toLowerCase().includes(field));
      };

      const formatAmount = (amount) => {
        return new Intl.NumberFormat('id-ID', {
          style: 'currency',
          currency: 'IDR',
          minimumFractionDigits: 2
        }).format(amount || 0);
      };

      const saveJournalEntry = async () => {
        // Validate form
        const isFormValid = await v$.value.$validate();
        if (!isFormValid || !isBalanced.value || lineErrors.value.length > 0) {
          return;
        }

        isSaving.value = true;

        try {
          const payload = {
            journal_number: journalEntry.journal_number,
            entry_date: journalEntry.entry_date,
            reference_type: journalEntry.reference_type || null,
            reference_id: journalEntry.reference_id || null,
            description: journalEntry.description,
            period_id: journalEntry.period_id,
            status: journalEntry.status,
            lines: journalEntry.lines.map(line => ({
              account_id: line.account_id,
              debit_amount: parseFloat(line.debit_amount) || 0,
              credit_amount: parseFloat(line.credit_amount) || 0,
              description: line.description || null
            }))
          };

          if (isEditing.value) {
            await axios.put(`/api/accounting/journal-entries/${route.params.id}`, payload);
          } else {
            await axios.post('/api/accounting/journal-entries', payload);
          }

          // TODO: Show success notification
          router.push('/accounting/journal-entries');
        } catch (error) {
          console.error('Error saving journal entry:', error);
          // TODO: Show error notification
        } finally {
          isSaving.value = false;
        }
      };

      const saveAndPost = async () => {
        // Validate form
        const isFormValid = await v$.value.$validate();
        if (!isFormValid || !isBalanced.value || lineErrors.value.length > 0) {
          return;
        }

        isSaving.value = true;

        try {
          const payload = {
            journal_number: journalEntry.journal_number,
            entry_date: journalEntry.entry_date,
            reference_type: journalEntry.reference_type || null,
            reference_id: journalEntry.reference_id || null,
            description: journalEntry.description,
            period_id: journalEntry.period_id,
            status: 'Posted', // Set status to Posted
            lines: journalEntry.lines.map(line => ({
              account_id: line.account_id,
              debit_amount: parseFloat(line.debit_amount) || 0,
              credit_amount: parseFloat(line.credit_amount) || 0,
              description: line.description || null
            }))
          };

          if (isEditing.value) {
            await axios.put(`/api/accounting/journal-entries/${route.params.id}`, payload);
            await axios.post(`/api/accounting/journal-entries/${route.params.id}/post`);
          } else {
            const response = await axios.post('/api/accounting/journal-entries', payload);
            await axios.post(`/api/accounting/journal-entries/${response.data.data.journal_id}/post`);
          }

          // TODO: Show success notification
          router.push('/accounting/journal-entries');
        } catch (error) {
          console.error('Error saving and posting journal entry:', error);
          // TODO: Show error notification
        } finally {
          isSaving.value = false;
        }
      };

      // Generate unique journal number
      const generateJournalNumber = () => {
        if (!isEditing.value && !journalEntry.journal_number) {
          const today = new Date();
          const year = today.getFullYear().toString().substr(-2);
          const month = (today.getMonth() + 1).toString().padStart(2, '0');
          const random = Math.floor(Math.random() * 9000 + 1000);
          journalEntry.journal_number = `JV-${year}${month}-${random}`;
        }
      };

      // Lifecycle hooks
      onMounted(() => {
        generateJournalNumber();
        loadData();
      });

      watch(
        () => journalEntry.lines,
        () => {
          updateTotals();
        },
        { deep: true }
      );

      return {
        journalEntry,
        periods,
        accounts,
        accountsByType,
        isLoading,
        isSaving,
        isEditing,
        totalDebit,
        totalCredit,
        isBalanced,
        lineErrors,
        v$,
        addLine,
        removeLine,
        clearOtherAmount,
        updateTotals,
        hasLineError,
        formatAmount,
        saveJournalEntry,
        saveAndPost
      };
    }
  };
  </script>

  <style scoped>
  .journal-entry-form {
    padding: 1rem 0;
  }

  .table th {
    background-color: var(--gray-100);
  }

  .form-control:disabled {
    background-color: var(--gray-100);
  }
  </style>
