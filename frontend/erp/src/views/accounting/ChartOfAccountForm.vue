<!-- src/views/accounting/ChartOfAccountForm.vue -->
<template>
    <div class="page-content">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>{{ isEditing ? 'Edit Account' : 'Create New Account' }}</h1>
        <router-link to="/accounting/chart-of-accounts" class="btn btn-secondary">
          <i class="fas fa-arrow-left mr-2"></i> Back to Accounts
        </router-link>
      </div>

      <div class="card">
        <div class="card-header">
          <h3 class="card-title">{{ isEditing ? 'Edit Account Details' : 'Account Details' }}</h3>
        </div>
        <div class="card-body">
          <div v-if="isLoading" class="loading-indicator">
            <i class="fas fa-spinner fa-spin"></i> Loading account data...
          </div>

          <form v-else @submit.prevent="saveAccount">
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="account_code">Account Code *</label>
                  <input
                    type="text"
                    id="account_code"
                    v-model="account.account_code"
                    class="form-control"
                    :class="{ 'is-invalid': errors.account_code }"
                    placeholder="e.g., 1000"
                    required
                  >
                  <div v-if="errors.account_code" class="invalid-feedback">
                    {{ errors.account_code[0] }}
                  </div>
                  <small class="text-muted">A unique code used to identify this account</small>
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-group">
                  <label for="name">Account Name *</label>
                  <input
                    type="text"
                    id="name"
                    v-model="account.name"
                    class="form-control"
                    :class="{ 'is-invalid': errors.name }"
                    placeholder="e.g., Cash"
                    required
                  >
                  <div v-if="errors.name" class="invalid-feedback">
                    {{ errors.name[0] }}
                  </div>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="account_type">Account Type *</label>
                  <select
                    id="account_type"
                    v-model="account.account_type"
                    class="form-control"
                    :class="{ 'is-invalid': errors.account_type }"
                    required
                  >
                    <option value="">-- Select Account Type --</option>
                    <option value="Asset">Asset</option>
                    <option value="Liability">Liability</option>
                    <option value="Equity">Equity</option>
                    <option value="Revenue">Revenue</option>
                    <option value="Expense">Expense</option>
                  </select>
                  <div v-if="errors.account_type" class="invalid-feedback">
                    {{ errors.account_type[0] }}
                  </div>
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-group">
                  <label for="parent_account_id">Parent Account</label>
                  <select
                    id="parent_account_id"
                    v-model="account.parent_account_id"
                    class="form-control"
                    :class="{ 'is-invalid': errors.parent_account_id }"
                  >
                    <option value="">-- No Parent (Top Level) --</option>
                    <option
                      v-for="parentAccount in availableParentAccounts"
                      :key="parentAccount.account_id"
                      :value="parentAccount.account_id"
                      :disabled="isEditing && parentAccount.account_id === accountId"
                    >
                      {{ parentAccount.account_code }} - {{ parentAccount.name }}
                    </option>
                  </select>
                  <div v-if="errors.parent_account_id" class="invalid-feedback">
                    {{ errors.parent_account_id[0] }}
                  </div>
                  <small class="text-muted">Select a parent account to create a hierarchical structure</small>
                </div>
              </div>
            </div>

            <div class="form-group">
              <div class="custom-control custom-switch">
                <input
                  type="checkbox"
                  id="is_active"
                  v-model="account.is_active"
                  class="custom-control-input"
                >
                <label class="custom-control-label" for="is_active">Active Account</label>
              </div>
              <small class="text-muted">Inactive accounts cannot be used in transactions</small>
            </div>

            <div class="form-actions mt-4">
              <button type="submit" class="btn btn-primary" :disabled="isSaving">
                <i v-if="isSaving" class="fas fa-spinner fa-spin mr-2"></i>
                <i v-else class="fas fa-save mr-2"></i>
                {{ isEditing ? 'Update Account' : 'Create Account' }}
              </button>
              <router-link to="/accounting/chart-of-accounts" class="btn btn-secondary ml-2">
                Cancel
              </router-link>
            </div>
          </form>
        </div>
      </div>
    </div>
  </template>

  <script>
  import axios from 'axios';

  export default {
    name: 'ChartOfAccountForm',
    props: {
      accountId: {
        type: String,
        required: false
      }
    },
    data() {
      return {
        account: {
          account_code: '',
          name: '',
          account_type: '',
          is_active: true,
          parent_account_id: null
        },
        allAccounts: [],
        isLoading: false,
        isSaving: false,
        errors: {}
      };
    },
    computed: {
      isEditing() {
        return !!this.accountId;
      },
      availableParentAccounts() {
        if (this.isEditing) {
          // When editing, filter out the current account and its children
          return this.allAccounts.filter(acc =>
            acc.account_id !== this.accountId &&
            !this.isChildOf(acc.account_id, this.accountId)
          );
        } else {
          return this.allAccounts;
        }
      }
    },
    created() {
      this.fetchAllAccounts();
      if (this.isEditing) {
        this.fetchAccount();
      }
    },
    methods: {
      async fetchAllAccounts() {
        try {
          const response = await axios.get('/api/accounting/chart-of-accounts');
          this.allAccounts = response.data.data;
        } catch (error) {
          console.error('Error fetching accounts:', error);
          this.$toast.error('Failed to load account data');
        }
      },

      async fetchAccount() {
        this.isLoading = true;
        try {
          const response = await axios.get(`/api/accounting/chart-of-accounts/${this.accountId}`);
          this.account = response.data.data;
        } catch (error) {
          console.error('Error fetching account:', error);
          this.$toast.error('Failed to load account data');
        } finally {
          this.isLoading = false;
        }
      },

      async saveAccount() {
        this.isSaving = true;
        this.errors = {};

        try {
          if (this.isEditing) {
            await axios.put(
              `/api/accounting/chart-of-accounts/${this.accountId}`,
              this.account
            );
            this.$toast.success('Account updated successfully');
          } else {
            await axios.post('/api/accounting/chart-of-accounts', this.account);
            this.$toast.success('Account created successfully');
          }

          this.$router.push('/accounting/chart-of-accounts');
        } catch (error) {
          if (error.response && error.response.data && error.response.data.errors) {
            this.errors = error.response.data.errors;
            this.$toast.error('Please correct the errors in the form');
          } else if (error.response && error.response.data && error.response.data.message) {
            this.$toast.error(error.response.data.message);
          } else {
            this.$toast.error('Failed to save account');
          }
        } finally {
          this.isSaving = false;
        }
      },

      // Check if accountId2 is a child (or deeper descendant) of accountId1
      isChildOf(accountId1, accountId2) {
        const findChildren = (parentId) => {
          const directChildren = this.allAccounts.filter(acc =>
            acc.parent_account_id === parentId
          );

          if (directChildren.some(child => child.account_id === accountId1)) {
            return true;
          }

          return directChildren.some(child =>
            this.isChildOf(accountId1, child.account_id)
          );
        };

        return findChildren(accountId2);
      }
    }
  };
  </script>

  <style scoped>
  .form-actions {
    border-top: 1px solid var(--gray-200);
    padding-top: 1rem;
  }
  </style>
