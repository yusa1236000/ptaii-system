<!-- src/views/accounting/BankAccountForm.vue -->
<template>
    <div class="bank-account-form">
      <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
          <h1>{{ isEditing ? 'Edit Bank Account' : 'Create Bank Account' }}</h1>
          <router-link to="/accounting/bank-accounts" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i> Back to Bank Accounts
          </router-link>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h3 class="card-title">{{ isEditing ? 'Edit Account Details' : 'Account Details' }}</h3>
        </div>
        <div class="card-body">
          <!-- Loading state -->
          <div v-if="isLoading" class="text-center py-5">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p class="mt-2">Loading account data...</p>
          </div>

          <!-- Error state -->
          <div v-else-if="error" class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            {{ error }}
          </div>

          <!-- Form -->
          <form v-else @submit.prevent="saveAccount">
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="bankName">Bank Name <span class="text-danger">*</span></label>
                  <input
                    id="bankName"
                    v-model="bankAccount.bank_name"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': validationErrors.bank_name }"
                    placeholder="e.g., Bank Mandiri"
                    required
                  />
                  <div v-if="validationErrors.bank_name" class="invalid-feedback">
                    {{ validationErrors.bank_name[0] }}
                  </div>
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-group">
                  <label for="accountNumber">Account Number <span class="text-danger">*</span></label>
                  <input
                    id="accountNumber"
                    v-model="bankAccount.account_number"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': validationErrors.account_number }"
                    placeholder="e.g., 1234567890"
                    required
                  />
                  <div v-if="validationErrors.account_number" class="invalid-feedback">
                    {{ validationErrors.account_number[0] }}
                  </div>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="accountName">Account Name <span class="text-danger">*</span></label>
                  <input
                    id="accountName"
                    v-model="bankAccount.account_name"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': validationErrors.account_name }"
                    placeholder="e.g., Company Operational Account"
                    required
                  />
                  <div v-if="validationErrors.account_name" class="invalid-feedback">
                    {{ validationErrors.account_name[0] }}
                  </div>
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-group">
                  <label for="currentBalance">Current Balance <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text">Rp</span>
                    </div>
                    <input
                      id="currentBalance"
                      v-model="bankAccount.current_balance"
                      type="number"
                      class="form-control"
                      :class="{ 'is-invalid': validationErrors.current_balance }"
                      placeholder="0.00"
                      step="0.01"
                      min="0"
                      required
                    />
                    <div v-if="validationErrors.current_balance" class="invalid-feedback">
                      {{ validationErrors.current_balance[0] }}
                    </div>
                  </div>
                  <small class="form-text text-muted">
                    Opening balance for this account
                  </small>
                </div>
              </div>
            </div>

            <div class="form-group">
              <label for="glAccountId">GL Account <span class="text-danger">*</span></label>
              <select
                id="glAccountId"
                v-model="bankAccount.gl_account_id"
                class="form-control"
                :class="{ 'is-invalid': validationErrors.gl_account_id }"
                required
              >
                <option value="">-- Select a GL Account --</option>
                <optgroup v-for="group in accountGroups" :key="group.type" :label="group.type">
                  <option
                    v-for="account in group.accounts"
                    :key="account.account_id"
                    :value="account.account_id"
                  >
                    {{ account.account_code }} - {{ account.name }}
                  </option>
                </optgroup>
              </select>
              <div v-if="validationErrors.gl_account_id" class="invalid-feedback">
                {{ validationErrors.gl_account_id[0] }}
              </div>
              <small class="form-text text-muted">
                The general ledger account to associate with this bank account (must be an Asset account)
              </small>
            </div>

            <div class="form-group">
              <label for="description">Description</label>
              <textarea
                id="description"
                v-model="bankAccount.description"
                class="form-control"
                :class="{ 'is-invalid': validationErrors.description }"
                rows="3"
                placeholder="Additional details about this bank account"
              ></textarea>
              <div v-if="validationErrors.description" class="invalid-feedback">
                {{ validationErrors.description[0] }}
              </div>
            </div>

            <div class="form-actions mt-4">
              <button type="submit" class="btn btn-primary" :disabled="isSaving">
                <i v-if="isSaving" class="fas fa-spinner fa-spin mr-2"></i>
                <i v-else class="fas fa-save mr-2"></i>
                {{ isEditing ? 'Update Account' : 'Create Account' }}
              </button>
              <router-link to="/accounting/bank-accounts" class="btn btn-secondary ml-2">
                Cancel
              </router-link>
            </div>
          </form>
        </div>
      </div>
    </div>
  </template>

  <script>
  import { ref, computed, onMounted, reactive } from 'vue';
  import { useRoute, useRouter } from 'vue-router';
  import axios from 'axios';

  export default {
    name: 'BankAccountForm',
    setup() {
      const route = useRoute();
      const router = useRouter();
      const accountId = computed(() => route.params.id);
      const isEditing = computed(() => !!accountId.value);

      // State variables
      const bankAccount = reactive({
        bank_name: '',
        account_number: '',
        account_name: '',
        current_balance: 0,
        gl_account_id: '',
        description: ''
      });
      const chartOfAccounts = ref([]);
      const isLoading = ref(false);
      const isSaving = ref(false);
      const error = ref(null);
      const validationErrors = ref({});

      // Group accounts by type for the dropdown
      const accountGroups = computed(() => {
        const groups = {};

        chartOfAccounts.value.forEach(account => {
          if (!groups[account.account_type]) {
            groups[account.account_type] = [];
          }
          groups[account.account_type].push(account);
        });

        return Object.keys(groups).map(type => ({
          type,
          accounts: groups[type].sort((a, b) => a.account_code.localeCompare(b.account_code))
        }));
      });

      // Load account data if editing
      const loadAccountData = async () => {
        if (!isEditing.value) return;

        isLoading.value = true;
        error.value = null;

        try {
          const response = await axios.get(`/api/accounting/bank-accounts/${accountId.value}`);

          if (response.data && response.data.data) {
            // Copy account data to our reactive object
            Object.assign(bankAccount, response.data.data);
          } else {
            throw new Error('Invalid response format');
          }
        } catch (err) {
          console.error('Error loading bank account:', err);
          error.value = 'Failed to load bank account data. Please try again later.';
        } finally {
          isLoading.value = false;
        }
      };

      // Load chart of accounts for GL account dropdown
      const loadChartOfAccounts = async () => {
        try {
          const response = await axios.get('/api/accounting/chart-of-accounts', {
            params: { account_type: 'Asset' }
          });

          if (response.data && response.data.data) {
            chartOfAccounts.value = response.data.data.filter(account => account.is_active);
          }
        } catch (err) {
          console.error('Error loading chart of accounts:', err);
          // Don't set the main error, just log it
        }
      };

      // Save the bank account
      const saveAccount = async () => {
        isSaving.value = true;
        validationErrors.value = {};

        try {
          //let response;

          if (isEditing.value) {
             await axios.put(`/api/accounting/bank-accounts/${accountId.value}`, bankAccount);
          } else {
             await axios.post('/api/accounting/bank-accounts', bankAccount);
          }

          // Navigate back to list on success
          if (window.$toast) {
            window.$toast.success(isEditing.value ? 'Bank account updated successfully' : 'Bank account created successfully');
          }

          router.push('/accounting/bank-accounts');
        } catch (err) {
          console.error('Error saving bank account:', err);

          if (err.response && err.response.status === 422) {
            // Validation errors
            validationErrors.value = err.response.data.errors || {};
          } else {
            // Other errors
            error.value = err.response?.data?.message || 'Failed to save bank account. Please try again later.';
          }
        } finally {
          isSaving.value = false;
        }
      };

      // Load data on component mount
      onMounted(async () => {
        isLoading.value = true;

        try {
          // Load data in parallel
          await Promise.all([
            loadChartOfAccounts(),
            loadAccountData()
          ]);
        } finally {
          isLoading.value = false;
        }
      });

      return {
        bankAccount,
        isEditing,
        isLoading,
        isSaving,
        error,
        validationErrors,
        accountGroups,
        saveAccount
      };
    }
  };
  </script>

  <style scoped>
  .bank-account-form {
    padding: 1rem 0;
  }

  .form-actions {
    border-top: 1px solid var(--gray-200);
    padding-top: 1rem;
  }

  .form-control:disabled {
    background-color: var(--gray-100);
  }
  </style>
