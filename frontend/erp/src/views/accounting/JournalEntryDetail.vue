<!-- src/views/accounting/JournalEntryDetail.vue -->
<template>
    <div class="journal-entry-detail">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h3>Journal Entry Details</h3>
          <div>
            <router-link
              v-if="journalEntry && journalEntry.status !== 'Posted'"
              :to="`/accounting/journal-entries/${journalId}/edit`"
              class="btn btn-primary mr-2"
            >
              <i class="fas fa-edit mr-1"></i> Edit
            </router-link>

            <router-link
              v-if="journalEntry && journalEntry.status !== 'Posted'"
              :to="`/accounting/journal-entries/${journalId}/post`"
              class="btn btn-success mr-2"
            >
              <i class="fas fa-check mr-1"></i> Post
            </router-link>

            <button
              v-if="journalEntry && journalEntry.status !== 'Posted'"
              @click="confirmDelete"
              class="btn btn-danger mr-2"
            >
              <i class="fas fa-trash mr-1"></i> Delete
            </button>

            <button @click="printJournalEntry" class="btn btn-info">
              <i class="fas fa-print mr-1"></i> Print
            </button>
          </div>
        </div>

        <div class="card-body">
          <div v-if="isLoading" class="loading-indicator">
            <i class="fas fa-spinner fa-spin"></i> Loading journal entry...
          </div>

          <div v-else-if="!journalEntry" class="empty-state">
            <div class="empty-icon">
              <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3>Journal Entry Not Found</h3>
            <p>The requested journal entry could not be found.</p>
            <router-link to="/accounting/journal-entries" class="btn btn-primary">
              <i class="fas fa-arrow-left mr-1"></i> Back to Journal Entries
            </router-link>
          </div>

          <div v-else>
            <!-- Journal Header Information -->
            <div class="row mb-4">
              <div class="col-md-6">
                <div class="journal-info">
                  <h4 class="mb-3">Journal Information</h4>
                  <table class="table table-sm table-borderless">
                    <tbody>
                      <tr>
                        <th width="140">Journal Number:</th>
                        <td><strong>{{ journalEntry.journal_number }}</strong></td>
                      </tr>
                      <tr>
                        <th>Status:</th>
                        <td>
                          <span
                            :class="journalEntry.status === 'Posted' ? 'badge badge-success' : 'badge badge-warning'"
                          >
                            {{ journalEntry.status }}
                          </span>
                        </td>
                      </tr>
                      <tr>
                        <th>Date:</th>
                        <td>{{ formatDate(journalEntry.entry_date) }}</td>
                      </tr>
                      <tr>
                        <th>Period:</th>
                        <td>{{ journalEntry.accounting_period?.period_name || '-' }}</td>
                      </tr>
                      <tr>
                        <th>Description:</th>
                        <td>{{ journalEntry.description }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <div class="col-md-6">
                <div class="reference-info">
                  <h4 class="mb-3">Reference Information</h4>
                  <table class="table table-sm table-borderless">
                    <tbody>
                      <tr>
                        <th width="140">Reference Type:</th>
                        <td>{{ journalEntry.reference_type || '-' }}</td>
                      </tr>
                      <tr>
                        <th>Reference ID:</th>
                        <td>{{ journalEntry.reference_id || '-' }}</td>
                      </tr>
                      <tr>
                        <th>Created Date:</th>
                        <td>{{ journalEntry.created_at ? formatDate(journalEntry.created_at) : '-' }}</td>
                      </tr>
                      <tr>
                        <th>Last Updated:</th>
                        <td>{{ journalEntry.updated_at ? formatDate(journalEntry.updated_at) : '-' }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <!-- Journal Entry Lines -->
            <div class="journal-lines mt-4">
              <h4 class="mb-3">Journal Entry Lines</h4>
              <div class="table-responsive">
                <table class="table table-bordered">
                  <thead class="bg-light">
                    <tr>
                      <th width="10%">Line #</th>
                      <th width="40%">Account</th>
                      <th width="15%" class="text-right">Debit</th>
                      <th width="15%" class="text-right">Credit</th>
                      <th>Description</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(line, index) in journalEntry.journal_entry_lines" :key="line.line_id">
                      <td>{{ index + 1 }}</td>
                      <td>
                        <div class="d-flex flex-column">
                          <span>{{ getAccountName(line.account_id) }}</span>
                          <small class="text-muted">{{ getAccountCode(line.account_id) }}</small>
                        </div>
                      </td>
                      <td class="text-right">{{ formatCurrency(line.debit_amount) }}</td>
                      <td class="text-right">{{ formatCurrency(line.credit_amount) }}</td>
                      <td>{{ line.description || '-' }}</td>
                    </tr>

                    <!-- Totals row -->
                    <tr class="bg-light font-weight-bold">
                      <td colspan="2" class="text-right">Totals</td>
                      <td class="text-right">{{ formatCurrency(totalDebit) }}</td>
                      <td class="text-right">{{ formatCurrency(totalCredit) }}</td>
                      <td></td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Navigation Buttons -->
            <div class="mt-4">
              <router-link to="/accounting/journal-entries" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Back to Journal Entries
              </router-link>
            </div>
          </div>
        </div>
      </div>

      <!-- Delete Confirmation Modal -->
      <ConfirmationModal
        v-if="showDeleteModal"
        title="Delete Journal Entry"
        :message="`Are you sure you want to delete the journal entry <strong>${journalEntry?.journal_number}</strong>?<br>This action cannot be undone.`"
        confirm-button-text="Delete"
        confirm-button-class="btn btn-danger"
        @confirm="deleteJournalEntry"
        @close="showDeleteModal = false"
      />
    </div>
  </template>

  <script>
  import { ref, computed, onMounted } from 'vue';
  import { useRoute, useRouter } from 'vue-router';
  import axios from 'axios';

  export default {
    name: 'JournalEntryDetail',
    setup() {
      const route = useRoute();
      const router = useRouter();
      const journalId = route.params.id;

      // State
      const journalEntry = ref(null);
      const accounts = ref([]);
      const isLoading = ref(true);
      const showDeleteModal = ref(false);

      // Computed
      const totalDebit = computed(() => {
        if (!journalEntry.value || !journalEntry.value.journal_entry_lines) return 0;

        return journalEntry.value.journal_entry_lines.reduce((sum, line) => {
          return sum + parseFloat(line.debit_amount || 0);
        }, 0);
      });

      const totalCredit = computed(() => {
        if (!journalEntry.value || !journalEntry.value.journal_entry_lines) return 0;

        return journalEntry.value.journal_entry_lines.reduce((sum, line) => {
          return sum + parseFloat(line.credit_amount || 0);
        }, 0);
      });

      // Methods
      const loadJournalEntry = async () => {
        isLoading.value = true;
        try {
          const response = await axios.get(`/api/accounting/journal-entries/${journalId}`);
          journalEntry.value = response.data.data;

          // Load chart of accounts to get names
          const accountsResponse = await axios.get('/api/accounting/chart-of-accounts');
          accounts.value = accountsResponse.data.data;
        } catch (error) {
          console.error('Error loading journal entry:', error);
          journalEntry.value = null;
        } finally {
          isLoading.value = false;
        }
      };

      const getAccountName = (accountId) => {
        const account = accounts.value.find(a => a.account_id === accountId);
        return account ? account.name : 'Unknown Account';
      };

      const getAccountCode = (accountId) => {
        const account = accounts.value.find(a => a.account_id === accountId);
        return account ? account.account_code : '';
      };

      const formatDate = (dateString) => {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return new Intl.DateTimeFormat('id-ID').format(date);
      };

      const formatCurrency = (amount) => {
        return new Intl.NumberFormat('id-ID', {
          style: 'currency',
          currency: 'IDR',
          minimumFractionDigits: 2
        }).format(parseFloat(amount) || 0);
      };

      const confirmDelete = () => {
        showDeleteModal.value = true;
      };

      const deleteJournalEntry = async () => {
        try {
          await axios.delete(`/api/accounting/journal-entries/${journalId}`);
          // TODO: Show success notification
          router.push('/accounting/journal-entries');
        } catch (error) {
          console.error('Error deleting journal entry:', error);
          // TODO: Show error notification
        } finally {
          showDeleteModal.value = false;
        }
      };

      const printJournalEntry = () => {
        window.print();
      };

      // Lifecycle hooks
      onMounted(() => {
        loadJournalEntry();
      });

      return {
        journalId,
        journalEntry,
        isLoading,
        totalDebit,
        totalCredit,
        showDeleteModal,
        getAccountName,
        getAccountCode,
        formatDate,
        formatCurrency,
        confirmDelete,
        deleteJournalEntry,
        printJournalEntry
      };
    }
  };
  </script>

  <style scoped>
  .journal-entry-detail {
    padding: 1rem 0;
  }

  .journal-info, .reference-info {
    background-color: var(--gray-50);
    padding: 1.25rem;
    border-radius: 0.5rem;
    height: 100%;
  }

  .table th {
    font-weight: 600;
  }

  .badge {
    font-size: 0.875rem;
    padding: 0.35rem 0.65rem;
  }

  @media print {
    .btn, .card-header {
      display: none !important;
    }

    .card {
      border: none !important;
      box-shadow: none !important;
    }

    .card-body {
      padding: 0 !important;
    }
  }
  </style>
