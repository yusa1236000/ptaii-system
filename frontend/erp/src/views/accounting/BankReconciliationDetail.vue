<!-- src/views/accounting/BankReconciliationDetail.vue -->
<template>
    <div class="bank-reconciliation-detail">
      <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
          <h2>Bank Reconciliation Details</h2>
          <div class="action-buttons">
            <router-link to="/accounting/bank-reconciliations" class="btn btn-secondary mr-2">
              <i class="fas fa-arrow-left mr-1"></i> Back to List
            </router-link>
            <button @click="printReconciliation" class="btn btn-info mr-2">
              <i class="fas fa-print mr-1"></i> Print
            </button>
            <div v-if="reconciliation && reconciliation.status !== 'Finalized'" class="btn-group">
              <router-link :to="`/accounting/bank-reconciliations/${reconciliationId}/edit`" class="btn btn-primary mr-2">
                <i class="fas fa-edit mr-1"></i> Edit
              </router-link>
              <router-link :to="`/accounting/bank-reconciliations/${reconciliationId}/match`" class="btn btn-success mr-2">
                <i class="fas fa-exchange-alt mr-1"></i> Match Transactions
              </router-link>
              <router-link :to="`/accounting/bank-reconciliations/${reconciliationId}/finalize`" class="btn btn-warning">
                <i class="fas fa-check-circle mr-1"></i> Finalize
              </router-link>
            </div>
          </div>
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

      <div v-else>
        <!-- Status Banner -->
        <div
          :class="{
            'alert': true,
            'alert-warning': reconciliation.status === 'Draft' || reconciliation.status === 'In Progress',
            'alert-success': reconciliation.status === 'Finalized'
          }"
          class="mb-4"
        >
          <div class="d-flex align-items-center">
            <i
              :class="{
                'fas': true,
                'fa-pencil-alt': reconciliation.status === 'Draft',
                'fa-sync': reconciliation.status === 'In Progress',
                'fa-check-circle': reconciliation.status === 'Finalized'
              }"
              class="mr-3 fa-lg"
            ></i>
            <div>
              <h5 class="alert-heading mb-1">
                {{ reconciliation.status === 'Finalized' ? 'Reconciliation Completed' : 'Reconciliation In Progress' }}
              </h5>
              <p class="mb-0">
                <span v-if="reconciliation.status === 'Draft'">
                  This reconciliation is in draft status. Complete the matching process to reconcile the transactions.
                </span>
                <span v-else-if="reconciliation.status === 'In Progress'">
                  This reconciliation is in progress. Continue matching transactions until the difference is reconciled.
                </span>
                <span v-else>
                  This reconciliation has been finalized. The bank account balance has been updated to match the statement balance.
                </span>
              </p>
            </div>
          </div>
        </div>

        <!-- Reconciliation Details -->
        <div class="row mb-4">
          <!-- Bank Information -->
          <div class="col-md-6 mb-4">
            <div class="card h-100">
              <div class="card-header">
                <h3 class="card-title">Bank Information</h3>
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
                      <th>Account Name</th>
                      <td>{{ reconciliation.bank_account?.account_name }}</td>
                    </tr>
                    <tr>
                      <th>Statement Date</th>
                      <td>{{ formatDate(reconciliation.statement_date) }}</td>
                    </tr>
                    <tr>
                      <th>Status</th>
                      <td>
                        <span
                          :class="{
                            'badge': true,
                            'badge-warning': reconciliation.status === 'Draft' || reconciliation.status === 'In Progress',
                            'badge-success': reconciliation.status === 'Finalized'
                          }"
                        >
                          {{ reconciliation.status }}
                        </span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- Balance Summary -->
          <div class="col-md-6 mb-4">
            <div class="card h-100">
              <div class="card-header">
                <h3 class="card-title">Balance Summary</h3>
              </div>
              <div class="card-body">
                <div class="balance-summary">
                  <div class="balance-item">
                    <div class="balance-label">Statement Balance</div>
                    <div class="balance-value">{{ formatCurrency(reconciliation.statement_balance) }}</div>
                  </div>

                  <div class="balance-item">
                    <div class="balance-label">Book Balance</div>
                    <div class="balance-value">{{ formatCurrency(reconciliation.book_balance) }}</div>
                  </div>

                  <div class="balance-divider"></div>

                  <div class="balance-item" :class="{ 'balanced': isBalanced }">
                    <div class="balance-label">Difference</div>
                    <div :class="{ 'balance-value': true, 'text-danger': !isBalanced, 'text-success': isBalanced }">
                      {{ formatCurrency(reconciliationDifference) }}
                    </div>
                  </div>

                  <div v-if="isBalanced && reconciliation.status === 'Finalized'" class="balance-message mt-3 text-success">
                    <i class="fas fa-check-circle mr-2"></i> Reconciliation is complete and balanced.
                  </div>
                  <div v-else-if="isBalanced && reconciliation.status !== 'Finalized'" class="balance-message mt-3 text-warning">
                    <i class="fas fa-info-circle mr-2"></i> Reconciliation is balanced but not yet finalized.
                  </div>
                  <div v-else class="balance-message mt-3 text-danger">
                    <i class="fas fa-exclamation-circle mr-2"></i> Reconciliation is not balanced.
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Reconciled Transactions -->
        <div class="card mb-4">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">Reconciled Transactions</h3>
            <div v-if="reconciliation.status !== 'Finalized'">
              <router-link :to="`/accounting/bank-reconciliations/${reconciliationId}/match`" class="btn btn-sm btn-primary">
                <i class="fas fa-plus mr-1"></i> Add Transactions
              </router-link>
            </div>
          </div>
          <div class="card-body">
            <div v-if="isLoadingTransactions" class="text-center py-4">
              <i class="fas fa-spinner fa-spin"></i>
              <p class="mt-2">Loading transactions...</p>
            </div>
            <div v-else-if="reconciledTransactions.length === 0" class="empty-state">
              <div class="empty-icon">
                <i class="fas fa-exchange-alt"></i>
              </div>
              <h3>No Reconciled Transactions</h3>
              <p>No transactions have been reconciled yet.</p>
              <div v-if="reconciliation.status !== 'Finalized'" class="mt-3">
                <router-link :to="`/accounting/bank-reconciliations/${reconciliationId}/match`" class="btn btn-primary">
                  <i class="fas fa-exchange-alt mr-1"></i> Match Transactions
                </router-link>
              </div>
            </div>
            <div v-else>
              <div class="table-responsive">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>Date</th>
                      <th>Type</th>
                      <th>Reference</th>
                      <th>Description</th>
                      <th class="text-right">Amount</th>
                      <th v-if="reconciliation.status !== 'Finalized'">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="transaction in reconciledTransactions" :key="transaction.line_id">
                      <td>{{ formatDate(transaction.transaction_date) }}</td>
                      <td>{{ transaction.transaction_type }}</td>
                      <td>{{ transaction.transaction_id }}</td>
                      <td>{{ getTransactionDescription(transaction) }}</td>
                      <td class="text-right" :class="{'text-danger': isNegativeAmount(transaction.amount)}">
                        {{ formatCurrency(transaction.amount) }}
                      </td>
                      <td v-if="reconciliation.status !== 'Finalized'">
                        <button @click="unreconcileTransaction(transaction)" class="btn btn-sm btn-danger">
                          <i class="fas fa-times"></i>
                        </button>
                      </td>
                    </tr>
                  </tbody>
                  <tfoot>
                    <tr>
                      <th colspan="4" class="text-right">Total Reconciled:</th>
                      <th class="text-right">{{ formatCurrency(totalReconciledAmount) }}</th>
                      <th v-if="reconciliation.status !== 'Finalized'"></th>
                    </tr>
                  </tfoot>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Confirmation Modal for Unreconciling -->
      <ConfirmationModal
        v-if="showUnreconcileModal"
        title="Unreconcile Transaction"
        :message="`Are you sure you want to remove this transaction from the reconciliation?`"
        confirm-button-text="Unreconcile"
        confirm-button-class="btn btn-danger"
        @confirm="confirmUnreconcile"
        @close="showUnreconcileModal = false"
      />
    </div>
  </template>

  <script>
  import { ref, computed, onMounted } from 'vue';
  import { useRoute} from 'vue-router';
  import axios from 'axios';

  export default {
    name: 'BankReconciliationDetail',
    setup() {
      const route = useRoute();
    //   const router = useRouter();
      const reconciliationId = computed(() => route.params.id);

      // State
      const reconciliation = ref(null);
      const reconciledTransactions = ref([]);
      const isLoading = ref(true);
      const isLoadingTransactions = ref(true);
      const showUnreconcileModal = ref(false);
      const selectedTransaction = ref(null);

      // Computed properties
      const reconciliationDifference = computed(() => {
        if (!reconciliation.value) return 0;
        return reconciliation.value.statement_balance - reconciliation.value.book_balance;
      });

      const isBalanced = computed(() => {
        return Math.abs(reconciliationDifference.value) < 0.01;
      });

      const totalReconciledAmount = computed(() => {
        return reconciledTransactions.value.reduce((sum, transaction) => sum + parseFloat(transaction.amount), 0);
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
        } finally {
          isLoading.value = false;
        }
      };

      const fetchReconciledTransactions = async () => {
        isLoadingTransactions.value = true;
        try {
          const response = await axios.get(`/api/accounting/bank-reconciliations/${reconciliationId.value}/lines`);
          reconciledTransactions.value = response.data.data.filter(line => line.is_reconciled);
        } catch (error) {
          console.error('Error fetching reconciled transactions:', error);
          // TODO: Show error toast message
        } finally {
          isLoadingTransactions.value = false;
        }
      };

      const getTransactionDescription = (transaction) => {
        // Customize this based on your transaction types and structure
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

      const unreconcileTransaction = (transaction) => {
        selectedTransaction.value = transaction;
        showUnreconcileModal.value = true;
      };

      const confirmUnreconcile = async () => {
        if (!selectedTransaction.value) return;

        try {
          await axios.put(
            `/api/accounting/bank-reconciliations/${reconciliationId.value}/lines/${selectedTransaction.value.line_id}`,
            { is_reconciled: false }
          );

          // Remove from the list
          reconciledTransactions.value = reconciledTransactions.value.filter(
            t => t.line_id !== selectedTransaction.value.line_id
          );

          // Reset
          showUnreconcileModal.value = false;
          selectedTransaction.value = null;

          // Refresh the reconciliation data
          fetchReconciliation();
        } catch (error) {
          console.error('Error unreconciling transaction:', error);
          // TODO: Show error toast message
        }
      };

      const printReconciliation = () => {
        window.print();
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
        await fetchReconciliation();
        await fetchReconciledTransactions();
      });

      return {
        reconciliationId,
        reconciliation,
        reconciledTransactions,
        isLoading,
        isLoadingTransactions,
        showUnreconcileModal,
        reconciliationDifference,
        isBalanced,
        totalReconciledAmount,
        getTransactionDescription,
        isNegativeAmount,
        unreconcileTransaction,
        confirmUnreconcile,
        printReconciliation,
        formatDate,
        formatCurrency
      };
    }
  };
  </script>

  <style scoped>
  .bank-reconciliation-detail {
    padding: 1rem 0;
  }

  .table-details {
    margin-bottom: 0;
  }

  .table-details th {
    width: 40%;
    font-weight: 600;
  }

  .badge {
    padding: 0.35rem 0.65rem;
    font-size: 0.75rem;
    font-weight: 500;
  }

  .balance-summary {
    padding: 0.5rem;
  }

  .balance-item {
    display: flex;
    justify-content: space-between;
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--gray-100);
  }

  .balance-item.balanced {
    background-color: var(--success-bg);
    border-radius: 0.375rem;
    padding: 0.75rem;
    margin-top: 0.5rem;
  }

  .balance-label {
    font-weight: 500;
    color: var(--gray-700);
  }

  .balance-value {
    font-weight: 600;
    color: var(--gray-800);
  }

  .balance-divider {
    height: 1px;
    background-color: var(--gray-200);
    margin: 0.75rem 0;
  }

  .balance-message {
    padding: 0.75rem;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    font-weight: 500;
  }

  .empty-state {
    text-align: center;
    padding: 3rem 0;
  }

  .empty-icon {
    font-size: 3rem;
    color: var(--gray-300);
    margin-bottom: 1rem;
  }

  /* Print styles */
  @media print {
    .action-buttons,
    .btn,
    button {
      display: none;
    }
  }
  </style>
