<!-- src/views/accounting/BankReconciliationFinalize.vue -->
<template>
    <div class="bank-reconciliation-finalize">
      <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
          <h2>Finalize Reconciliation</h2>
          <router-link :to="`/accounting/bank-reconciliations/${reconciliationId}`" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i> Back to Details
          </router-link>
        </div>
      </div>

      <div v-if="isLoading" class="text-center py-5">
        <i class="fas fa-spinner fa-spin fa-2x"></i>
        <p class="mt-2">Loading reconciliation data...</p>
      </div>

      <div v-else-if="!reconciliation" class="alert alert-warning">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        Reconciliation not found.
      </div>

      <div v-else-if="reconciliation.status === 'Finalized'" class="alert alert-info">
        <i class="fas fa-info-circle mr-2"></i>
        This reconciliation has already been finalized on {{ formatDate(reconciliation.updated_at) }}.
      </div>

      <div v-else>
        <div class="card mb-4">
          <div class="card-header">
            <h3 class="card-title">Finalize Bank Reconciliation</h3>
          </div>
          <div class="card-body">
            <div class="alert alert-warning mb-4">
              <div class="d-flex">
                <i class="fas fa-exclamation-triangle align-self-start mt-1 mr-3"></i>
                <div>
                  <h5 class="alert-heading mb-1">Important</h5>
                  <p class="mb-0">
                    Finalizing this reconciliation will:
                  </p>
                  <ul class="mb-0 mt-2">
                    <li>Update your bank account's current balance to match the statement balance</li>
                    <li>Mark all reconciled transactions as finalized</li>
                    <li>Lock the reconciliation against further changes</li>
                  </ul>
                  <p class="mt-2 mb-0">
                    This action cannot be undone. Make sure all transactions are properly reconciled.
                  </p>
                </div>
              </div>
            </div>

            <div class="row mb-4">
              <div class="col-md-6">
                <div class="card">
                  <div class="card-header bg-light">
                    <h5 class="mb-0">Reconciliation Summary</h5>
                  </div>
                  <div class="card-body">
                    <table class="table table-details">
                      <tbody>
                        <tr>
                          <th>Bank Account</th>
                          <td>{{ reconciliation.bank_account?.bank_name }}</td>
                        </tr>
                        <tr>
                          <th>Account Number</th>
                          <td>{{ reconciliation.bank_account?.account_number }}</td>
                        </tr>
                        <tr>
                          <th>Statement Date</th>
                          <td>{{ formatDate(reconciliation.statement_date) }}</td>
                        </tr>
                        <tr>
                          <th>Statement Balance</th>
                          <td>{{ formatCurrency(reconciliation.statement_balance) }}</td>
                        </tr>
                        <tr>
                          <th>Book Balance</th>
                          <td>{{ formatCurrency(reconciliation.book_balance) }}</td>
                        </tr>
                        <tr>
                          <th>Reconciled Amount</th>
                          <td>{{ formatCurrency(totalReconciledAmount) }}</td>
                        </tr>
                        <tr class="font-weight-bold">
                          <th>Difference</th>
                          <td :class="{'text-danger': difference !== 0, 'text-success': difference === 0}">
                            {{ formatCurrency(difference) }}
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>

              <div class="col-md-6 d-flex">
                <div class="finalize-status-card" :class="{ 'balanced': isBalanced }">
                  <div class="status-icon">
                    <i :class="isBalanced ? 'fas fa-check-circle' : 'fas fa-exclamation-circle'"></i>
                  </div>
                  <div class="status-text">
                    <h4>{{ isBalanced ? 'Ready to Finalize' : 'Not Balanced' }}</h4>
                    <p v-if="isBalanced">
                      Your reconciliation is balanced. You can proceed to finalize it.
                    </p>
                    <p v-else>
                      Your reconciliation is not balanced. Please go back to the matching screen and reconcile all transactions.
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Reconciled Transactions -->
            <div v-if="reconciledTransactions.length > 0" class="mb-4">
              <h4 class="mb-3">Reconciled Transactions</h4>
              <div class="table-responsive">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>Date</th>
                      <th>Type</th>
                      <th>Reference</th>
                      <th>Description</th>
                      <th class="text-right">Amount</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="transaction in reconciledTransactions" :key="transaction.line_id">
                      <td>{{ formatDate(transaction.transaction_date) }}</td>
                      <td>{{ transaction.transaction_type }}</td>
                      <td>#{{ transaction.transaction_id }}</td>
                      <td>{{ getTransactionDescription(transaction) }}</td>
                      <td class="text-right" :class="{'text-danger': isNegativeAmount(transaction.amount)}">
                        {{ formatCurrency(transaction.amount) }}
                      </td>
                    </tr>
                  </tbody>
                  <tfoot>
                    <tr>
                      <th colspan="4" class="text-right">Total:</th>
                      <th class="text-right">{{ formatCurrency(totalReconciledAmount) }}</th>
                    </tr>
                  </tfoot>
                </table>
              </div>
            </div>

            <!-- Finalization Form -->
            <div class="finalize-form mt-5">
              <h4 class="mb-3">Finalization Options</h4>

              <div class="form-group">
                <div class="custom-control custom-checkbox">
                  <input
                    type="checkbox"
                    class="custom-control-input"
                    id="createJournalEntry"
                    v-model="createJournalEntry"
                  >
                  <label class="custom-control-label font-weight-normal" for="createJournalEntry">
                    Create journal entry for adjustments (if any)
                  </label>
                </div>
              </div>

              <div v-if="createJournalEntry && difference !== 0" class="adjustment-options border p-3 rounded mb-4">
                <h5 class="mb-3">Adjustment Journal Entry Details</h5>

                <div class="form-group">
                  <label for="adjustmentDescription">Journal Description</label>
                  <input
                    type="text"
                    id="adjustmentDescription"
                    v-model="adjustmentDescription"
                    class="form-control"
                    placeholder="Enter description for adjustment journal entry"
                  >
                </div>

                <div class="form-group">
                  <label for="adjustmentAccount">Adjustment Account</label>
                  <select
                    id="adjustmentAccount"
                    v-model="adjustmentAccount"
                    class="form-control"
                    required
                  >
                    <option value="">Select an account</option>
                    <option v-for="account in chartOfAccounts" :key="account.account_id" :value="account.account_id">
                      {{ account.account_code }} - {{ account.name }}
                    </option>
                  </select>
                  <small class="form-text text-muted">
                    The account to use for reconciliation adjustments (e.g., bank fees, interest income, or reconciliation discrepancies).
                  </small>
                </div>
              </div>

              <div class="confirmation-checkbox mt-4">
                <div class="custom-control custom-checkbox">
                  <input
                    type="checkbox"
                    class="custom-control-input"
                    id="confirmFinalize"
                    v-model="confirmFinalize"
                  >
                  <label class="custom-control-label font-weight-bold" for="confirmFinalize">
                    I confirm that I want to finalize this bank reconciliation
                  </label>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="d-flex justify-content-between mt-4 mb-5">
          <div>
            <router-link :to="`/accounting/bank-reconciliations/${reconciliationId}/match`" class="btn btn-secondary mr-2">
              <i class="fas fa-exchange-alt mr-1"></i> Back to Matching
            </router-link>
            <router-link :to="`/accounting/bank-reconciliations/${reconciliationId}`" class="btn btn-secondary">
              <i class="fas fa-eye mr-1"></i> Review Details
            </router-link>
          </div>
          <div>
            <button
              @click="finalizeReconciliation"
              class="btn btn-warning"
              :disabled="!isBalanced || !confirmFinalize || isFinalizing || (createJournalEntry && difference !== 0 && !adjustmentAccount)"
            >
              <i v-if="isFinalizing" class="fas fa-spinner fa-spin mr-2"></i>
              <i v-else class="fas fa-check-circle mr-2"></i>
              {{ isFinalizing ? 'Finalizing...' : 'Finalize Reconciliation' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </template>

  <script>
  import { ref, computed, onMounted } from 'vue';
  import { useRoute, useRouter } from 'vue-router';
  import axios from 'axios';

  export default {
    name: 'BankReconciliationFinalize',
    setup() {
      const route = useRoute();
      const router = useRouter();
      const reconciliationId = computed(() => route.params.id);

      // State
      const reconciliation = ref(null);
      const reconciledTransactions = ref([]);
      const chartOfAccounts = ref([]);
      const isLoading = ref(true);
      const isFinalizing = ref(false);
      const confirmFinalize = ref(false);
      const createJournalEntry = ref(true);
      const adjustmentAccount = ref('');
      const adjustmentDescription = ref('Bank Reconciliation Adjustment');

      // Computed properties
      const totalReconciledAmount = computed(() => {
        return reconciledTransactions.value.reduce((sum, t) => sum + parseFloat(t.amount), 0);
      });

      const difference = computed(() => {
        if (!reconciliation.value) return 0;
        return reconciliation.value.statement_balance -
               (reconciliation.value.book_balance + totalReconciledAmount.value);
      });

      const isBalanced = computed(() => {
        return Math.abs(difference.value) < 0.01;
      });

      // Methods
      const fetchReconciliation = async () => {
        isLoading.value = true;
        try {
          const response = await axios.get(`/api/accounting/bank-reconciliations/${reconciliationId.value}`);
          reconciliation.value = response.data.data;
        } catch (error) {
          console.error('Error fetching reconciliation:', error);
          // TODO: Show error toast message
        }
      };

      const fetchReconciledTransactions = async () => {
        try {
          const response = await axios.get(`/api/accounting/bank-reconciliations/${reconciliationId.value}/lines`);
          reconciledTransactions.value = response.data.data.filter(line => line.is_reconciled);
        } catch (error) {
          console.error('Error fetching reconciled transactions:', error);
          // TODO: Show error toast message
        } finally {
          isLoading.value = false;
        }
      };

      const fetchChartOfAccounts = async () => {
        try {
          const response = await axios.get('/api/accounting/chart-of-accounts');
          // Filter accounts that are appropriate for adjustments (typically expense accounts)
          chartOfAccounts.value = response.data.data.filter(account =>
            account.is_active &&
            (account.account_type === 'Expense' ||
             account.account_type === 'Revenue' ||
             account.name.toLowerCase().includes('adjustment') ||
             account.name.toLowerCase().includes('reconciliation'))
          );
        } catch (error) {
          console.error('Error fetching chart of accounts:', error);
          // TODO: Show error toast message
        }
      };

      const finalizeReconciliation = async () => {
        if (!confirmFinalize.value || !isBalanced.value) {
          return;
        }

        isFinalizing.value = true;

        try {
          // Prepare the request payload
          const payload = {
            create_journal_entry: createJournalEntry.value
          };

          // If creating a journal entry and there's a difference, include adjustment account
          if (createJournalEntry.value && Math.abs(difference.value) > 0.01) {
            if (!adjustmentAccount.value) {
              // TODO: Show error toast message
              isFinalizing.value = false;
              return;
            }

            payload.adjustment_account_id = adjustmentAccount.value;
            payload.adjustment_description = adjustmentDescription.value || 'Bank Reconciliation Adjustment';
          }

          // Call API to finalize the reconciliation
          await axios.post(`/api/accounting/bank-reconciliations/${reconciliationId.value}/finalize`, payload);

          // Show success message and redirect to the details page
          // TODO: Show success toast message
          router.push({
            path: `/accounting/bank-reconciliations/${reconciliationId.value}`,
            query: { success: 'finalized' }
          });
        } catch (error) {
          console.error('Error finalizing reconciliation:', error);
          // TODO: Show error toast message
        } finally {
          isFinalizing.value = false;
        }
      };

      const getTransactionDescription = (transaction) => {
        // Customize this based on your transaction types and structure
        if (transaction.description) return transaction.description;

        let description = '';

        switch (transaction.transaction_type) {
          case 'Payment':
            description = `Payment to vendor #${transaction.transaction_id}`;
            break;
          case 'Receipt':
            description = `Receipt from customer #${transaction.transaction_id}`;
            break;
          case 'Transfer':
            description = `Transfer #${transaction.transaction_id}`;
            break;
          default:
            description = `${transaction.transaction_type} #${transaction.transaction_id}`;
            break;
        }

        return description;
      };

      const isNegativeAmount = (amount) => {
        return parseFloat(amount) < 0;
      };

      const formatDate = (dateString) => {
        if (!dateString) return '';
        const options = { year: 'numeric', month: 'short', day: 'numeric' };
        return new Date(dateString).toLocaleDateString(undefined, options);
      };

      const formatCurrency = (amount) => {
        return new Intl.NumberFormat('id-ID', {
          style: 'currency',
          currency: 'IDR',
          minimumFractionDigits: 0,
          maximumFractionDigits: 0
        }).format(amount || 0);
      };

      // Lifecycle hooks
      onMounted(async () => {
        await Promise.all([
          fetchReconciliation(),
          fetchChartOfAccounts()
        ]);
        await fetchReconciledTransactions();
      });

      return {
        reconciliationId,
        reconciliation,
        reconciledTransactions,
        chartOfAccounts,
        isLoading,
        isFinalizing,
        confirmFinalize,
        createJournalEntry,
        adjustmentAccount,
        adjustmentDescription,
        totalReconciledAmount,
        difference,
        isBalanced,
        finalizeReconciliation,
        getTransactionDescription,
        isNegativeAmount,
        formatDate,
        formatCurrency
      };
    }
  };
  </script>

  <style scoped>
  .bank-reconciliation-finalize {
    padding: 1rem 0;
  }

  .table-details {
    margin-bottom: 0;
  }

  .table-details th {
    width: 40%;
    font-weight: 600;
  }

  .finalize-status-card {
    display: flex;
    align-items: center;
    padding: 1.5rem;
    border-radius: 0.5rem;
    border: 1px solid var(--gray-200);
    background-color: var(--gray-50);
    width: 100%;
  }

  .finalize-status-card.balanced {
    background-color: var(--success-bg);
    border-color: var(--success-color);
  }

  .status-icon {
    font-size: 3rem;
    margin-right: 1.5rem;
    color: var(--gray-400);
  }

  .finalize-status-card.balanced .status-icon {
    color: var(--success-color);
  }

  .status-text h4 {
    font-size: 1.25rem;
    margin-bottom: 0.5rem;
    color: var(--gray-800);
  }

  .finalize-status-card.balanced .status-text h4 {
    color: var(--success-color);
  }

  .status-text p {
    color: var(--gray-600);
    margin-bottom: 0;
  }

  .confirmation-checkbox {
    padding: 1rem;
    border-radius: 0.5rem;
    background-color: rgba(234, 88, 12, 0.1);
    border: 1px solid var(--warning-color);
  }

  .adjustment-options {
    background-color: var(--gray-50);
  }

  .text-danger {
    color: var(--danger-color) !important;
  }

  .text-success {
    color: var(--success-color) !important;
  }
  </style>
