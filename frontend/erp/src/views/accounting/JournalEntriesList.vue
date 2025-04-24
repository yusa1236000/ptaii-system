<!-- src/views/accounting/JournalEntriesList.vue -->
<template>
    <div class="journal-entries-container">
      <div class="page-header">
        <h1>Jurnal Akuntansi</h1>
        <div class="action-buttons">
          <router-link to="/accounting/journal-entries/create" class="btn btn-primary">
            <i class="fas fa-plus"></i> Buat Jurnal Baru
          </router-link>
          <router-link to="/accounting/journal-entries/batch-upload" class="btn btn-secondary ml-2">
            <i class="fas fa-upload"></i> Upload Batch
          </router-link>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h2 class="card-title">Daftar Jurnal</h2>
          <div class="filter-wrapper">
            <SearchFilter v-model:value="searchQuery" placeholder="Cari jurnal..." @search="loadJournalEntries()">
              <template #filters>
                <div class="filter-group">
                  <label>Periode</label>
                  <select v-model="selectedPeriod" @change="loadJournalEntries()">
                    <option value="">Semua Periode</option>
                    <option v-for="period in periods" :key="period.period_id" :value="period.period_id">
                      {{ period.period_name }}
                    </option>
                  </select>
                </div>
                <div class="filter-group">
                  <label>Status</label>
                  <select v-model="selectedStatus" @change="loadJournalEntries()">
                    <option value="">Semua Status</option>
                    <option value="Draft">Draft</option>
                    <option value="Posted">Posted</option>
                    <option value="Voided">Dibatalkan</option>
                  </select>
                </div>
                <div class="filter-group">
                  <label>Tanggal Awal</label>
                  <input type="date" v-model="fromDate" @change="loadJournalEntries()">
                </div>
                <div class="filter-group">
                  <label>Tanggal Akhir</label>
                  <input type="date" v-model="toDate" @change="loadJournalEntries()">
                </div>
              </template>
            </SearchFilter>
          </div>
        </div>

        <div class="card-body">
          <div v-if="isLoading" class="loading-indicator">
            <i class="fas fa-spinner fa-spin"></i> Memuat data...
          </div>

          <div v-else-if="journalEntries.length === 0" class="empty-state">
            <div class="empty-icon">
              <i class="fas fa-file-invoice"></i>
            </div>
            <h3>Tidak ada data jurnal</h3>
            <p>Belum ada data jurnal untuk filter yang dipilih.</p>
          </div>

          <div v-else class="table-container">
            <table class="data-table">
              <thead>
                <tr>
                  <th class="sortable" @click="sort('journal_number')">No. Jurnal</th>
                  <th class="sortable" @click="sort('entry_date')">Tanggal</th>
                  <th>Periode</th>
                  <th>Referensi</th>
                  <th>Deskripsi</th>
                  <th>Status</th>
                  <th class="actions-column">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="journal in journalEntries" :key="journal.journal_id">
                  <td>{{ journal.journal_number }}</td>
                  <td>{{ formatDate(journal.entry_date) }}</td>
                  <td>{{ journal.accounting_period?.period_name || '-' }}</td>
                  <td>
                    <template v-if="journal.reference_type && journal.reference_id">
                      {{ journal.reference_type }} #{{ journal.reference_id }}
                    </template>
                    <template v-else>-</template>
                  </td>
                  <td>{{ journal.description || '-' }}</td>
                  <td>
                    <span :class="getStatusClass(journal.status)" class="badge">
                      {{ journal.status }}
                    </span>
                  </td>
                  <td class="actions-cell">
                    <router-link :to="`/accounting/journal-entries/${journal.journal_id}`" class="btn-icon" title="Lihat Detail">
                      <i class="fas fa-eye"></i>
                    </router-link>
                    <router-link v-if="journal.status === 'Draft'" :to="`/accounting/journal-entries/${journal.journal_id}/edit`" class="btn-icon" title="Edit">
                      <i class="fas fa-edit"></i>
                    </router-link>
                    <button v-if="journal.status === 'Draft'" @click="showPostConfirmation(journal)" class="btn-icon" title="Posting">
                      <i class="fas fa-check-circle"></i>
                    </button>
                    <button v-if="journal.status === 'Draft'" @click="showDeleteConfirmation(journal)" class="btn-icon text-danger" title="Hapus">
                      <i class="fas fa-trash"></i>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="pagination-wrapper" v-if="journalEntries.length > 0">
            <PaginationComponent
              :current-page="currentPage"
              :total-pages="totalPages"
              :from="from"
              :to="to"
              :total="total"
              @page-changed="changePage"
            />
          </div>
        </div>
      </div>

      <!-- Confirmation Modal for Posting -->
      <ConfirmationModal
        v-if="showPostModal"
        title="Posting Jurnal"
        :message="`Apakah Anda yakin ingin memposting jurnal <strong>${selectedJournal?.journal_number}</strong>? <br><br>Jurnal yang sudah diposting tidak dapat diubah atau dihapus.`"
        confirm-button-text="Posting"
        confirm-button-class="btn btn-primary"
        @confirm="postJournal"
        @close="showPostModal = false"
      />

      <!-- Confirmation Modal for Deletion -->
      <ConfirmationModal
        v-if="showDeleteModal"
        title="Hapus Jurnal"
        :message="`Apakah Anda yakin ingin menghapus jurnal <strong>${selectedJournal?.journal_number}</strong>? <br><br>Tindakan ini tidak dapat dibatalkan.`"
        confirm-button-text="Hapus"
        confirm-button-class="btn btn-danger"
        @confirm="deleteJournal"
        @close="showDeleteModal = false"
      />
    </div>
  </template>

  <script>
  import axios from 'axios';

  export default {
    name: 'JournalEntriesList',
    data() {
      return {
        journalEntries: [],
        periods: [],
        isLoading: true,
        searchQuery: '',
        selectedPeriod: '',
        selectedStatus: '',
        fromDate: '',
        toDate: '',
        sortKey: 'entry_date',
        sortOrder: 'desc',
        currentPage: 1,
        totalPages: 1,
        perPage: 15,
        from: 0,
        to: 0,
        total: 0,
        showPostModal: false,
        showDeleteModal: false,
        selectedJournal: null
      };
    },
    created() {
      // Get current date for default date range (last 30 days)
      const today = new Date();
      this.toDate = today.toISOString().split('T')[0];

      const thirtyDaysAgo = new Date();
      thirtyDaysAgo.setDate(today.getDate() - 30);
      this.fromDate = thirtyDaysAgo.toISOString().split('T')[0];

      this.loadPeriods();
      this.loadJournalEntries();
    },
    methods: {
      async loadPeriods() {
        try {
          const response = await axios.get('/api/accounting/accounting-periods');
          this.periods = response.data.data;
        } catch (error) {
          this.$toast.error('Gagal memuat data periode', {
            position: 'top-right',
            duration: 3000
          });
          console.error('Error loading periods:', error);
        }
      },
      async loadJournalEntries() {
        this.isLoading = true;
        try {
          const params = {
            page: this.currentPage,
            per_page: this.perPage,
            sort_key: this.sortKey,
            sort_order: this.sortOrder
          };

          if (this.searchQuery) params.search = this.searchQuery;
          if (this.selectedPeriod) params.period_id = this.selectedPeriod;
          if (this.selectedStatus) params.status = this.selectedStatus;
          if (this.fromDate) params.from_date = this.fromDate;
          if (this.toDate) params.to_date = this.toDate;

          const response = await axios.get('/api/accounting/journal-entries', { params });
          this.journalEntries = response.data.data;
          this.currentPage = response.data.current_page;
          this.totalPages = response.data.last_page;
          this.from = response.data.from || 0;
          this.to = response.data.to || 0;
          this.total = response.data.total;
        } catch (error) {
          this.$toast.error('Gagal memuat data jurnal', {
            position: 'top-right',
            duration: 3000
          });
          console.error('Error loading journal entries:', error);
        } finally {
          this.isLoading = false;
        }
      },
      formatDate(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', {
          day: '2-digit',
          month: 'short',
          year: 'numeric'
        });
      },
      getStatusClass(status) {
        switch (status) {
          case 'Posted':
            return 'badge-success';
          case 'Draft':
            return 'badge-secondary';
          case 'Voided':
            return 'badge-danger';
          default:
            return 'badge-secondary';
        }
      },
      sort(key) {
        if (this.sortKey === key) {
          this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc';
        } else {
          this.sortKey = key;
          this.sortOrder = 'asc';
        }
        this.loadJournalEntries();
      },
      changePage(page) {
        this.currentPage = page;
        this.loadJournalEntries();
      },
      showPostConfirmation(journal) {
        this.selectedJournal = journal;
        this.showPostModal = true;
      },
      showDeleteConfirmation(journal) {
        this.selectedJournal = journal;
        this.showDeleteModal = true;
      },
      async postJournal() {
        if (!this.selectedJournal) return;

        try {
          await axios.post(`/api/accounting/journal-entries/${this.selectedJournal.journal_id}/post`);
          this.$toast.success('Jurnal berhasil diposting', {
            position: 'top-right',
            duration: 3000
          });
          this.showPostModal = false;
          this.loadJournalEntries();
        } catch (error) {
          this.$toast.error('Gagal posting jurnal', {
            position: 'top-right',
            duration: 3000
          });
          console.error('Error posting journal:', error);
        }
      },
      async deleteJournal() {
        if (!this.selectedJournal) return;

        try {
          await axios.delete(`/api/accounting/journal-entries/${this.selectedJournal.journal_id}`);
          this.$toast.success('Jurnal berhasil dihapus', {
            position: 'top-right',
            duration: 3000
          });
          this.showDeleteModal = false;
          this.loadJournalEntries();
        } catch (error) {
          this.$toast.error('Gagal menghapus jurnal', {
            position: 'top-right',
            duration: 3000
          });
          console.error('Error deleting journal:', error);
        }
      }
    }
  };
  </script>

  <style scoped>
  .journal-entries-container {
    padding: 1rem;
  }

  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
  }

  .action-buttons {
    display: flex;
    gap: 0.5rem;
  }

  .filter-wrapper {
    margin-top: 1rem;
  }

  .pagination-wrapper {
    margin-top: 1rem;
  }

  .btn-icon {
    background: none;
    border: none;
    color: var(--primary-color);
    cursor: pointer;
    padding: 0.25rem 0.5rem;
    margin: 0 0.125rem;
    border-radius: 0.25rem;
    transition: background-color 0.2s;
  }

  .btn-icon:hover {
    background-color: var(--gray-100);
  }

  .text-danger {
    color: var(--danger-color);
  }

  .text-danger:hover {
    color: var(--danger-light);
  }
  </style>
