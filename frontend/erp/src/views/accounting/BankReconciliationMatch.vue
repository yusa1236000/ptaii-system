<!-- src/views/accounting/BankReconciliationMatch.vue -->
<template>
    <div class="bank-reconciliation-match">
      <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
          <h2>Match Transactions</h2>
          <div class="action-buttons">
            <router-link :to="`/accounting/bank-reconciliations/${reconciliationId}`" class="btn btn-secondary mr-2">
              <i class="fas fa-arrow-left mr-1"></i> Back to Details
            </router-link>
            <router-link :to="`/accounting/bank-reconciliations/${reconciliationId}/finalize`" class="btn btn-warning">
              <i class="fas fa-check-circle mr-1"></i> Finalize
            </router-link>
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

      <div v-else-if="reconciliation.status === 'Finalized'" class="alert alert-info">
        <i class="fas fa-info-circle mr-2"></i>
        This reconciliation has been finalized and cannot be modified.
      </div>

      <div v-else>
        <!-- Reconciliation Summary -->
        <div class="card mb-4">
          <div class="card-header">
            <h3 class="card-title">Reconciliation Summary</h3>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-6">
                <table class="table table-details">
                  <tbody>
                    <tr>
                      <th>Bank Account</th>
                      <td>{{ reconciliation.bank_account?.bank_name }} - {{ reconciliation.bank_account?.account_number }}</td>
                    </tr>
                    <tr>
                      <th>Statement Date</th>
                      <td>{{ formatDate(reconciliation.statement_date) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="col-md-6">
                <div class="balance-summary">
                  <div class="d-flex justify-content-between mb-2">
                    <span class="font-weight-bold">Statement Balance:</span>
                    <span>{{ formatCurrency(reconciliation.statement_balance) }}</span>
                  </div>
                  <div class="d-flex justify-content-between mb-2">
                    <span class="font-weight-bold">Book Balance:</span>
                    <span>{{ formatCurrency(reconciliation.book_balance) }}</span>
                  </div>
                  <div class="d-flex justify-content-between mb-2">
                    <span class="font-weight-bold">Reconciled Amount:</span>
                    <span>{{ formatCurrency(totalReconciledAmount) }}</span>
                  </div>
                  <div class="balance-divider"></div>
                  <div class="d-flex justify-content-between mt-2">
                    <span class="font-weight-bold">Remaining Difference:</span>
                    <span :class="{'text-danger': remainingDifference !== 0, 'text-success': remainingDifference === 0}">
                      {{ formatCurrency(remainingDifference) }}
                    </span>
                  </div>
                  <div v-if="remainingDifference === 0" class="alert alert-success mt-3">
                    <i class="fas fa-check-circle mr-2"></i> Reconciliation is balanced and ready to be finalized.
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="row">
          <!-- Reconciled Transactions -->
          <div class="col-lg-6 mb-4">
            <div class="card h-100">
              <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Reconciled Transactions</h3>
                <span class="badge badge-primary">{{ reconciledTransactions.length }} items</span>
              </div>
              <div class="card-body p-0">
                <div v-if="reconciledTransactions.length === 0" class="empty-state py-5">
                  <div class="empty-icon">
                    <i class="fas fa-check-circle"></i>
                  </div>
                  <h4>No Reconciled Transactions</h4>
                  <p>Start reconciling by selecting transactions from the right panel.</p>
                </div>
                <div v-else class="transaction-list reconciled-list">
                  <div v-for="transaction in reconciledTransactions" :key="transaction.line_id" class="transaction-item">
                    <div class="transaction-content">
                      <div class="transaction-date">{{ formatDate(transaction.transaction_date) }}</div>
                      <div class="transaction-info">
                        <div class="transaction-type">{{ transaction.transaction_type }}</div>
                        <div class="transaction-desc">{{ getTransactionDescription(transaction) }}</div>
                      </div>
                      <div class="transaction-amount" :class="{'text-danger': isNegativeAmount(transaction.amount)}">
                        {{ formatCurrency(transaction.amount) }}
                      </div>
                    </div>
                    <button @click="unreconcileTransaction(transaction)" class="btn btn-sm btn-outline-danger">
                      <i class="fas fa-times"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Unreconciled Transactions -->
          <div class="col-lg-6 mb-4">
            <div class="card h-100">
              <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Unreconciled Transactions</h3>
                <div class="d-flex align-items-center">
                  <div class="search-box mr-3">
                    <input
                      type="text"
                      v-model="searchTerm"
                      placeholder="Search transactions..."
                      class="form-control form-control-sm"
                    >
                  </div>
                  <span class="badge badge-secondary">{{ unreconciledTransactions.length }} items</span>
                </div>
              </div>
              <div class="card-body p-0">
                <div v-if="isLoadingTransactions" class="text-center py-5">
                  <i class="fas fa-spinner fa-spin"></i>
                  <p class="mt-2">Loading transactions...</p>
                </div>
                <div v-else-if="unreconciledTransactions.length === 0" class="empty-state py-5">
                  <div class="empty-icon">
                    <i class="fas fa-check-double"></i>
                  </div>
                  <h4>All Transactions Reconciled</h4>
                  <p>You have reconciled all available transactions.</p>
                </div>
                <div v-else class="transaction-list unreconciled-list">
                  <div v-for="transaction in filteredUnreconciledTransactions" :key="transaction.line_id" class="transaction-item">
                    <div class="transaction-content">
                      <div class="transaction-date">{{ formatDate(transaction.transaction_date) }}</div>
                      <div class="transaction-info">
                        <div class="transaction-type">{{ transaction.transaction_type }}</div>
                        <div class="transaction-desc">{{ getTransactionDescription(transaction) }}</div>
                      </div>
                      <div class="transaction-amount" :class="{'text-danger': isNegativeAmount(transaction.amount)}">
                        {{ formatCurrency(transaction.amount) }}
                      </div>
                    </div>
                    <button @click="reconcileTransaction(transaction)" class="btn btn-sm btn-outline-success">
                      <i class="fas fa-check"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Add Manual Transaction -->
        <div class="card mb-4">
          <div class="card-header">
            <h3 class="card-title">Add Manual Transaction</h3>
          </div>
          <div class="card-body">
            <p class="text-muted mb-3">
              If a transaction appears on your bank statement but not in your accounting system, you can add it manually.
            </p>

            <form @submit.prevent="addManualTransaction" class="manual-transaction-form">
              <div class="row">
                <div class="col-md-3">
                  <div class="form-group">
                    <label for="transactionDate">Date</label>
                    <input
                      type="date"
                      id="transactionDate"
                      v-model="manualTransaction.transaction_date"
                      class="form-control"
                      :class="{ 'is-invalid': !!manualTransactionErrors.transaction_date }"
                      required
                    >
                    <div v-if="manualTransactionErrors.transaction_date" class="invalid-feedback">
                      {{ manualTransactionErrors.transaction_date[0] }}
                    </div>
                  </div>
                </div>

                <div class="col-md-3">
                  <div class="form-group">
                    <label for="transactionType">Type</label>
                    <select
                      id="transactionType"
                      v-model="manualTransaction.transaction_type"
                      class="form-control"
                      :class="{ 'is-invalid': !!manualTransactionErrors.transaction_type }"
                      required
                    >
                      <option value="Deposit">Deposit</option>
                      <option value="Withdrawal">Withdrawal</option>
                      <option value="Payment">Payment</option>
                      <option value="Receipt">Receipt</option>
                      <option value="Transfer">Transfer</option>
                      <option value="Other">Other</option>
                    </select>
                    <div v-if="manualTransactionErrors.transaction_type" class="invalid-feedback">
                      {{ manualTransactionErrors.transaction_type[0] }}
                    </div>
                  </div>
                </div>

                <div class="col-md-3">
                  <div class="form-group">
                    <label for="transactionAmount">Amount</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                        <span class="input-group-text">Rp</span>
                      </div>
                      <input
                        type="number"
                        id="transactionAmount"
                        v-model="manualTransaction.amount"
                        class="form-control"
                        :class="{ 'is-invalid': !!manualTransactionErrors.amount }"
                        step="0.01"
                        required
                      >
                      <div v-if="manualTransactionErrors.amount" class="invalid-feedback">
                        {{ manualTransactionErrors.amount[0] }}
                      </div>
                    </div>
                  </div>
                </div>

                <div class="col-md-3">
                  <div class="form-group">
                    <label for="transactionDescription">Description</label>
                    <input
                      type="text"
                      id="transactionDescription"
                      v-model="manualTransaction.description"
                      class="form-control"
                      :class="{ 'is-invalid': !!manualTransactionErrors.description }"
                      placeholder="Enter description"
                    >
                    <div v-if="manualTransactionErrors.description" class="invalid-feedback">
                      {{ manualTransactionErrors.description[0] }}
                    </div>
                  </div>
                </div>
              </div>

              <div class="form-actions">
                <button type="submit" class="btn btn-primary" :disabled="isAddingTransaction">
                  <i v-if="isAddingTransaction" class="fas fa-spinner fa-spin mr-1"></i>
                  <i v-else class="fas fa-plus mr-1"></i>
                  {{ isAddingTransaction ? 'Adding...' : 'Add Transaction' }}
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="d-flex justify-content-between mt-4">
          <router-link :to="`/accounting/bank-reconciliations/${reconciliationId}`" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Back to Details
          </router-link>
          <div>
            <button v-if="remainingDifference === 0" @click="updateAndNavigateToFinalize" class="btn btn-success">
              <i class="fas fa-check-circle mr-1"></i> Ready to Finalize
            </button>
            <button v-else @click="saveAndStayOnPage" class="btn btn-primary">
              <i class="fas fa-save mr-1"></i> Save Progress
            </button>
          </div>
        </div>
      </div>

      <!-- Confirmation Modal for Unreconciling -->
      <ConfirmationModal
        v-if="showUnreconcileModal"
        title="Unreconcile Transaction"
        :message="`Are you sure you want to remove this transaction from the reconciliation?`"
        confirm-button-text="Remove"
        confirm-button-class="btn btn-danger"
        @confirm="confirmUnreconcile"
        @close="showUnreconcileModal = false"
      />
    </div>
  </template>

  <script>
  import { ref, computed, onMounted, watch } from 'vue';
  import { useRoute, useRouter } from 'vue-router';
  import axios from 'axios';

  export default {
    name: 'BankReconciliationMatch',
    setup() {
      const route = useRoute();
      const router = useRouter();
      const reconciliationId = computed(() => route.params.id);

      // State
      const reconciliation = ref(null);
      const allTransactions = ref([]);
      const searchTerm = ref('');
      const isLoading = ref(true);
      const isLoadingTransactions = ref(true);
      const showUnreconcileModal = ref(false);
      const selectedTransaction = ref(null);
      const isAddingTransaction = ref(false);
      const manualTransaction = ref({
        transaction_date: new Date().toISOString().substr(0, 10),
        transaction_type: 'Other',
        amount: '',
        description: ''
      });
      const manualTransactionErrors = ref({});

      // Computed properties
      const reconciledTransactions = computed(() => {
        return allTransactions.value.filter(t => t.is_reconciled);
      });

      const unreconciledTransactions = computed(() => {
        return allTransactions.value.filter(t => !t.is_reconciled);
      });

      const filteredUnreconciledTransactions = computed(() => {
        if (!searchTerm.value) return unreconciledTransactions.value;

        const term = searchTerm.value.toLowerCase();
        return unreconciledTransactions.value.filter(t => {
          // Search in transaction type, date, id, or description
          return (
            t.transaction_type.toLowerCase().includes(term) ||
            (new Date(t.transaction_date)).toLocaleDateString().toLowerCase().includes(term) ||
            t.transaction_id.toString().includes(term) ||
            getTransactionDescription(t).toLowerCase().includes(term)
          );
        });
      });

      const totalReconciledAmount = computed(() => {
        return reconciledTransactions.value.reduce((sum, t) => sum + parseFloat(t.amount), 0);
      });

      const remainingDifference = computed(() => {
        if (!reconciliation.value) return 0;
        // Statement balance - (book balance + reconciled amount)
        return reconciliation.value.statement_balance -
               (reconciliation.value.book_balance + totalReconciledAmount.value);
      });

      // Methods
      const fetchReconciliation = async () => {
        isLoading.value = true;
        try {
          const response = await axios.get(`/api/accounting/bank-reconciliations/${reconciliationId.value}`);
          reconciliation.value = response.data.data;

          // Update reconciliation status if needed
          if (reconciliation.value.status === 'Draft') {
            await axios.put(`/api/accounting/bank-reconciliations/${reconciliationId.value}`, {
              status: 'In Progress'
            });
            reconciliation.value.status = 'In Progress';
          }
        } catch (error) {
          console.error('Error fetching reconciliation:', error);
          // TODO: Show error toast message
        } finally {
          isLoading.value = false;
        }
      };

      const fetchTransactions = async () => {
        isLoadingTransactions.value = true;
        try {
          const response = await axios.get(`/api/accounting/bank-reconciliations/${reconciliationId.value}/lines`);
          allTransactions.value = response.data.data;
        } catch (error) {
          console.error('Error fetching transactions:', error);
          // TODO: Show error toast message
        } finally {
          isLoadingTransactions.value = false;
        }
      };

      const reconcileTransaction = async (transaction) => {
        try {
          await axios.put(
            `/api/accounting/bank-reconciliations/${reconciliationId.value}/lines/${transaction.line_id}`,
            { is_reconciled: true }
          );

          // Update local state
          const index = allTransactions.value.findIndex(t => t.line_id === transaction.line_id);
          if (index !== -1) {
            allTransactions.value[index].is_reconciled = true;
          }
        } catch (error) {
          console.error('Error reconciling transaction:', error);
          // TODO: Show error toast message
        }
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

          // Update local state
          const index = allTransactions.value.findIndex(t => t.line_id === selectedTransaction.value.line_id);
          if (index !== -1) {
            allTransactions.value[index].is_reconciled = false;
          }

          // Reset
          showUnreconcileModal.value = false;
          selectedTransaction.value = null;
        } catch (error) {
          console.error('Error unreconciling transaction:', error);
          // TODO: Show error toast message
        }
      };

      const addManualTransaction = async () => {
        isAddingTransaction.value = true;
        manualTransactionErrors.value = {};

        try {
          const response = await axios.post(`/api/accounting/bank-reconciliations/${reconciliationId.value}/lines`, {
            transaction_type: manualTransaction.value.transaction_type,
            transaction_date: manualTransaction.value.transaction_date,
            amount: manualTransaction.value.amount,
            description: manualTransaction.value.description,
            is_reconciled: true
          });

          // Add the new transaction to the list
          allTransactions.value.push(response.data.data);

          // Reset form
          manualTransaction.value = {
            transaction_date: new Date().toISOString().substr(0, 10),
            transaction_type: 'Other',
            amount: '',
            description: ''
          };

          // TODO: Show success toast message
        } catch (error) {
          console.error('Error adding manual transaction:', error);

          if (error.response && error.response.data && error.response.data.errors) {
            manualTransactionErrors.value = error.response.data.errors;
          } else {
            // TODO: Show general error toast message
          }
        } finally {
          isAddingTransaction.value = false;
        }
      };

      const saveAndStayOnPage = async () => {
        try {
          await axios.put(`/api/accounting/bank-reconciliations/${reconciliationId.value}`, {
            status: 'In Progress'
          });

          // TODO: Show success toast message
        } catch (error) {
          console.error('Error saving reconciliation:', error);
          // TODO: Show error toast message
        }
      };

      const updateAndNavigateToFinalize = async () => {
        try {
          await axios.put(`/api/accounting/bank-reconciliations/${reconciliationId.value}`, {
            status: 'In Progress'
          });

          router.push(`/accounting/bank-reconciliations/${reconciliationId.value}/finalize`);
        } catch (error) {
          console.error('Error updating reconciliation:', error);
          // TODO: Show error toast message
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

      // Watch for search term changes to dynamically filter
      watch(searchTerm, () => {
        // This reactivity is handled by the computed property
      });

      // Lifecycle hooks
      onMounted(async () => {
        await fetchReconciliation();
        if (reconciliation.value && reconciliation.value.status !== 'Finalized') {
          await fetchTransactions();
        }
      });

      return {
        reconciliationId,
        reconciliation,
        allTransactions,
        reconciledTransactions,
        unreconciledTransactions,
        filteredUnreconciledTransactions,
        searchTerm,
        isLoading,
        isLoadingTransactions,
        showUnreconcileModal,
        selectedTransaction,
        totalReconciledAmount,
        remainingDifference,
        manualTransaction,
        manualTransactionErrors,
        isAddingTransaction,
        reconcileTransaction,
        unreconcileTransaction,
        confirmUnreconcile,
        addManualTransaction,
        saveAndStayOnPage,
        updateAndNavigateToFinalize,
        getTransactionDescription,
        isNegativeAmount,
        formatDate,
        formatCurrency
      };
    }
  };
  </script>

  <style scoped>
  .bank-reconciliation-match {
    padding: 1rem 0;
  }

  .table-details {
    margin-bottom: 0;
  }

  .table-details th {
    width: 40%;
    font-weight: 600;
  }

  .balance-summary {
    padding: 1rem;
    background-color: var(--gray-50);
    border-radius: 0.5rem;
  }

  .balance-divider {
    height: 1px;
    background-color: var(--gray-200);
    margin: 0.75rem 0;
  }

  .transaction-list {
    max-height: 400px;
    overflow-y: auto;
  }

  .transaction-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem;
    border-bottom: 1px solid var(--gray-100);
  }

  .transaction-item:last-child {
    border-bottom: none;
  }

  .transaction-content {
    display: flex;
    align-items: center;
    flex-grow: 1;
  }

  .transaction-date {
    width: 100px;
    font-size: 0.875rem;
    color: var(--gray-600);
  }

  .transaction-info {
    flex-grow: 1;
    padding: 0 1rem;
  }

  .transaction-type {
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--gray-500);
    text-transform: uppercase;
  }

  .transaction-desc {
    font-weight: 500;
  }

  .transaction-amount {
    width: 120px;
    text-align: right;
    font-weight: 600;
  }

  .search-box {
    width: 200px;
  }

  .empty-state {
    text-align: center;
    padding: 2rem 0;
  }

  .empty-icon {
    font-size: 2.5rem;
    color: var(--gray-300);
    margin-bottom: 1rem;
  }

  .form-actions {
    margin-top: 1rem;
  }

  .reconciled-list .transaction-item:hover {
    background-color: #fee2e2;
  }

  .unreconciled-list .transaction-item:hover {
    background-color: #d1fae5;
  }

  .badge {
    font-size: 0.75rem;
    padding: 0.35rem 0.65rem;
  }

  .manual-transaction-form {
    max-width: 100%;
  }
  </style>
