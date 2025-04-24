<!-- src/views/accounting/JournalEntriesList.vue -->
<template>
    <div class="journal-entries-list">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h3>Journal Entries</h3>
          <div>
            <router-link to="/accounting/journal-entries/create" class="btn btn-primary">
              <i class="fas fa-plus mr-2"></i> New Journal Entry
            </router-link>
            <router-link to="/accounting/journal-entries/batch-upload" class="btn btn-secondary ml-2">
              <i class="fas fa-upload mr-2"></i> Batch Upload
            </router-link>
          </div>
        </div>

        <div class="card-body">
          <!-- Search and Filters -->
          <div class="mb-4">
            <SearchFilter :value="searchQuery" @search="onSearch" placeholder="Search journal number or description...">
              <template #filters>
                <div class="filter-group">
                  <label>Period</label>
                  <select v-model="periodFilter" class="form-control" @change="loadJournalEntries">
                    <option value="">All Periods</option>
                    <option v-for="period in periods" :key="period.period_id" :value="period.period_id">
                      {{ period.period_name }}
                    </option>
                  </select>
                </div>

                <div class="filter-group">
                  <label>Date Range</label>
                  <div class="d-flex">
                    <input type="date" v-model="fromDate" class="form-control" @change="loadJournalEntries">
                    <span class="mx-2 align-self-center">to</span>
                    <input type="date" v-model="toDate" class="form-control" @change="loadJournalEntries">
                  </div>
                </div>

                <div class="filter-group">
                  <label>Status</label>
                  <select v-model="statusFilter" class="form-control" @change="loadJournalEntries">
                    <option value="">All Status</option>
                    <option value="Draft">Draft</option>
                    <option value="Posted">Posted</option>
                  </select>
                </div>
              </template>

              <template #actions>
                <button class="btn btn-outline-secondary" @click="resetFilters">
                  <i class="fas fa-redo mr-1"></i> Reset
                </button>
              </template>
            </SearchFilter>
          </div>

          <!-- Loading and Empty States -->
          <div v-if="isLoading" class="loading-indicator">
            <i class="fas fa-spinner fa-spin"></i> Loading journal entries...
          </div>

          <div v-else-if="journalEntries.length === 0" class="empty-state">
            <div class="empty-icon">
              <i class="fas fa-book"></i>
            </div>
            <h3>No Journal Entries Found</h3>
            <p>No journal entries match your search criteria or there are no entries in the system yet.</p>
          </div>

          <!-- Journal Entries Table -->
          <div v-else class="table-container">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Journal #</th>
                  <th>Date</th>
                  <th>Description</th>
                  <th>Reference</th>
                  <th>Period</th>
                  <th>Status</th>
                  <th>Amount</th>
                  <th class="actions-column">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="entry in journalEntries" :key="entry.journal_id">
                  <td>{{ entry.journal_number }}</td>
                  <td>{{ formatDate(entry.entry_date) }}</td>
                  <td>{{ truncateText(entry.description, 50) }}</td>
                  <td>
                    <span v-if="entry.reference_type">
                      {{ entry.reference_type }} #{{ entry.reference_id }}
                    </span>
                    <span v-else>-</span>
                  </td>
                  <td>{{ entry.accounting_period ? entry.accounting_period.period_name : '-' }}</td>
                  <td>
                    <span
                      :class="entry.status === 'Posted' ? 'badge badge-success' : 'badge badge-warning'"
                    >
                      {{ entry.status }}
                    </span>
                  </td>
                  <td class="text-right">
                    {{ formatCurrency(calculateEntryAmount(entry)) }}
                  </td>
                  <td class="actions-cell">
                    <router-link :to="`/accounting/journal-entries/${entry.journal_id}`" class="btn btn-sm btn-info mr-1">
                      <i class="fas fa-eye"></i>
                    </router-link>
                    <router-link
                      v-if="entry.status !== 'Posted'"
                      :to="`/accounting/journal-entries/${entry.journal_id}/edit`"
                      class="btn btn-sm btn-primary mr-1"
                    >
                      <i class="fas fa-edit"></i>
                    </router-link>
                    <router-link
                      v-if="entry.status !== 'Posted'"
                      :to="`/accounting/journal-entries/${entry.journal_id}/post`"
                      class="btn btn-sm btn-success mr-1"
                    >
                      <i class="fas fa-check"></i>
                    </router-link>
                    <button
                      v-if="entry.status !== 'Posted'"
                      @click="confirmDelete(entry)"
                      class="btn btn-sm btn-danger"
                    >
                      <i class="fas fa-trash"></i>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <PaginationComponent
            v-if="journalEntries.length > 0"
            :current-page="currentPage"
            :total-pages="totalPages"
            :from="paginationFrom"
            :to="paginationTo"
            :total="totalEntries"
            @page-changed="onPageChange"
          />
        </div>
      </div>

      <!-- Delete Confirmation Modal -->
      <ConfirmationModal
        v-if="showDeleteModal"
        title="Delete Journal Entry"
        :message="`Are you sure you want to delete the journal entry <strong>${selectedEntry?.journal_number}</strong>?<br>This action cannot be undone.`"
        confirm-button-text="Delete"
        confirm-button-class="btn btn-danger"
        @confirm="deleteJournalEntry"
        @close="showDeleteModal = false"
      />
    </div>
  </template>

  <script>
  import { ref, onMounted, computed } from 'vue';
  import axios from 'axios';

  export default {
    name: 'JournalEntriesList',
    setup() {
      // State
      const journalEntries = ref([]);
      const periods = ref([]);
      const isLoading = ref(true);
      const searchQuery = ref('');
      const periodFilter = ref('');
      const statusFilter = ref('');
      const fromDate = ref('');
      const toDate = ref('');
      const currentPage = ref(1);
      const totalPages = ref(1);
      const totalEntries = ref(0);
      const perPage = ref(15);
      const showDeleteModal = ref(false);
      const selectedEntry = ref(null);

      // Computed properties
      const paginationFrom = computed(() => {
        if (totalEntries.value === 0) return 0;
        return ((currentPage.value - 1) * perPage.value) + 1;
      });

      const paginationTo = computed(() => {
        if (totalEntries.value === 0) return 0;
        return Math.min(currentPage.value * perPage.value, totalEntries.value);
      });

      // Methods
      const loadJournalEntries = async () => {
        isLoading.value = true;
        try {
          const params = {
            page: currentPage.value,
            per_page: perPage.value
          };

          if (searchQuery.value) {
            params.search = searchQuery.value;
          }

          if (periodFilter.value) {
            params.period_id = periodFilter.value;
          }

          if (statusFilter.value) {
            params.status = statusFilter.value;
          }

          if (fromDate.value && toDate.value) {
            params.from_date = fromDate.value;
            params.to_date = toDate.value;
          }

          const response = await axios.get('/api/accounting/journal-entries', { params });
          journalEntries.value = response.data.data;
          totalPages.value = response.data.last_page;
          totalEntries.value = response.data.total;
          currentPage.value = response.data.current_page;
        } catch (error) {
          console.error('Error loading journal entries:', error);
          // TODO: Show error notification
        } finally {
          isLoading.value = false;
        }
      };

      const loadPeriods = async () => {
        try {
          const response = await axios.get('/api/accounting/accounting-periods');
          periods.value = response.data.data;
        } catch (error) {
          console.error('Error loading accounting periods:', error);
          // TODO: Show error notification
        }
      };

      const onSearch = (value) => {
        searchQuery.value = value;
        currentPage.value = 1;
        loadJournalEntries();
      };

      const onPageChange = (page) => {
        currentPage.value = page;
        loadJournalEntries();
      };

      const resetFilters = () => {
        searchQuery.value = '';
        periodFilter.value = '';
        statusFilter.value = '';
        fromDate.value = '';
        toDate.value = '';
        currentPage.value = 1;
        loadJournalEntries();
      };

      const confirmDelete = (entry) => {
        selectedEntry.value = entry;
        showDeleteModal.value = true;
      };

      const deleteJournalEntry = async () => {
        if (!selectedEntry.value) return;

        try {
          await axios.delete(`/api/accounting/journal-entries/${selectedEntry.value.journal_id}`);
          // TODO: Show success notification
          showDeleteModal.value = false;
          loadJournalEntries();
        } catch (error) {
          console.error('Error deleting journal entry:', error);
          // TODO: Show error notification
        }
      };

      const calculateEntryAmount = (entry) => {
        if (!entry.journal_entry_lines || entry.journal_entry_lines.length === 0) {
          return 0;
        }

        // Sum up the debit side (could also sum the credit side, they should be equal)
        return entry.journal_entry_lines.reduce((sum, line) => sum + (parseFloat(line.debit_amount) || 0), 0);
      };

      const formatDate = (dateString) => {
        if (!dateString) return '';
        const date = new Date(dateString);
        return new Intl.DateTimeFormat('id-ID').format(date);
      };

      const formatCurrency = (amount) => {
        return new Intl.NumberFormat('id-ID', {
          style: 'currency',
          currency: 'IDR'
        }).format(amount);
      };

      const truncateText = (text, maxLength) => {
        if (!text) return '';
        if (text.length <= maxLength) return text;
        return text.substring(0, maxLength) + '...';
      };

      // Lifecycle hooks
      onMounted(() => {
        loadPeriods();
        loadJournalEntries();
      });

      return {
        journalEntries,
        periods,
        isLoading,
        searchQuery,
        periodFilter,
        statusFilter,
        fromDate,
        toDate,
        currentPage,
        totalPages,
        totalEntries,
        paginationFrom,
        paginationTo,
        showDeleteModal,
        selectedEntry,
        onSearch,
        onPageChange,
        resetFilters,
        loadJournalEntries,
        confirmDelete,
        deleteJournalEntry,
        calculateEntryAmount,
        formatDate,
        formatCurrency,
        truncateText
      };
    }
  };
  </script>

  <style scoped>
  .journal-entries-list {
    padding: 1rem 0;
  }

  .filter-group {
    min-width: 200px;
  }

  .table-container {
    overflow-x: auto;
  }

  .badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
  }
  </style>
