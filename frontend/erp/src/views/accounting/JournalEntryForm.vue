<!-- src/views/accounting/JournalEntryForm.vue -->
<template>
    <div class="journal-entry-form-container">
      <div class="page-header">
        <h1>{{ isEditing ? 'Edit Jurnal' : 'Buat Jurnal Baru' }}</h1>
        <div class="action-buttons">
          <button @click="$router.go(-1)" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
          </button>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h2 class="card-title">{{ isEditing ? `Edit Jurnal #${form.journal_number}` : 'Detail Jurnal' }}</h2>
        </div>

        <div class="card-body">
          <div v-if="isLoading" class="loading-indicator">
            <i class="fas fa-spinner fa-spin"></i> Memuat data...
          </div>

          <form v-else @submit.prevent="saveJournalEntry" class="journal-form">
            <div class="form-row">
              <div class="form-group">
                <label for="journal_number">Nomor Jurnal</label>
                <input
                  type="text"
                  id="journal_number"
                  v-model="form.journal_number"
                  class="form-control"
                  required
                  :class="{ 'is-invalid': errors.journal_number }"
                >
                <div v-if="errors.journal_number" class="invalid-feedback">
                  {{ errors.journal_number[0] }}
                </div>
              </div>

              <div class="form-group">
                <label for="entry_date">Tanggal Jurnal</label>
                <input
                  type="date"
                  id="entry_date"
                  v-model="form.entry_date"
                  class="form-control"
                  required
                  :class="{ 'is-invalid': errors.entry_date }"
                >
                <div v-if="errors.entry_date" class="invalid-feedback">
                  {{ errors.entry_date[0] }}
                </div>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="period_id">Periode Akuntansi</label>
                <select
                  id="period_id"
                  v-model="form.period_id"
                  class="form-control"
                  required
                  :class="{ 'is-invalid': errors.period_id }"
                >
                  <option value="" disabled>Pilih Periode</option>
                  <option v-for="period in periods" :key="period.period_id" :value="period.period_id">
                    {{ period.period_name }} ({{ formatDate(period.start_date) }} - {{ formatDate(period.end_date) }})
                  </option>
                </select>
                <div v-if="errors.period_id" class="invalid-feedback">
                  {{ errors.period_id[0] }}
                </div>
              </div>

              <div class="form-group">
                <label for="status">Status</label>
                <select
                  id="status"
                  v-model="form.status"
                  class="form-control"
                  required
                  :class="{ 'is-invalid': errors.status }"
                >
                  <option value="Draft">Draft</option>
                  <option value="Posted">Posted</option>
                </select>
                <div v-if="errors.status" class="invalid-feedback">
                  {{ errors.status[0] }}
                </div>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="reference_type">Tipe Referensi</label>
                <input
                  type="text"
                  id="reference_type"
                  v-model="form.reference_type"
                  class="form-control"
                  placeholder="Contoh: Invoice, Payment, Manual"
                  :class="{ 'is-invalid': errors.reference_type }"
                >
                <div v-if="errors.reference_type" class="invalid-feedback">
                  {{ errors.reference_type[0] }}
                </div>
              </div>

              <div class="form-group">
                <label for="reference_id">ID Referensi</label>
                <input
                  type="number"
                  id="reference_id"
                  v-model="form.reference_id"
                  class="form-control"
                  :class="{ 'is-invalid': errors.reference_id }"
                >
                <div v-if="errors.reference_id" class="invalid-feedback">
                  {{ errors.reference_id[0] }}
                </div>
              </div>
            </div>

            <div class="form-group">
              <label for="description">Deskripsi</label>
              <textarea
                id="description"
                v-model="form.description"
                class="form-control"
                rows="2"
                :class="{ 'is-invalid': errors.description }"
              ></textarea>
              <div v-if="errors.description" class="invalid-feedback">
                {{ errors.description[0] }}
              </div>
            </div>

            <div class="journal-lines">
              <h3>Detail Jurnal</h3>

              <div v-if="errors.lines" class="alert alert-danger mb-3">
                {{ errors.lines[0] }}
              </div>

              <div class="table-responsive">
                <table class="table table-bordered journal-lines-table">
                  <thead>
                    <tr>
                      <th style="width: 40%">Akun</th>
                      <th style="width: 20%">Debit</th>
                      <th style="width: 20%">Kredit</th>
                      <th style="width: 20%">Deskripsi</th>
                      <th style="width: 50px">Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(line, index) in form.lines" :key="index">
                      <td>
                        <select
                          v-model="line.account_id"
                          class="form-control"
                          required
                          :class="{ 'is-invalid': getLineError(index, 'account_id') }"
                        >
                          <option value="" disabled>Pilih Akun</option>
                          <optgroup v-for="group in groupedAccounts" :key="group.type" :label="group.type">
                            <option v-for="account in group.accounts" :key="account.account_id" :value="account.account_id">
                              {{ account.account_code }} - {{ account.name }}
                            </option>
                          </optgroup>
                        </select>
                        <div v-if="getLineError(index, 'account_id')" class="invalid-feedback">
                          {{ getLineError(index, 'account_id')[0] }}
                        </div>
                      </td>
                      <td>
                        <input
                          type="number"
                          v-model="line.debit_amount"
                          class="form-control text-right"
                          step="0.01"
                          min="0"
                          @input="updateBalance()"
                          :disabled="line.credit_amount > 0"
                          :class="{ 'is-invalid': getLineError(index, 'debit_amount') }"
                        >
                        <div v-if="getLineError(index, 'debit_amount')" class="invalid-feedback">
                          {{ getLineError(index, 'debit_amount')[0] }}
                        </div>
                      </td>
                      <td>
                        <input
                          type="number"
                          v-model="line.credit_amount"
                          class="form-control text-right"
                          step="0.01"
                          min="0"
                          @input="updateBalance()"
                          :disabled="line.debit_amount > 0"
                          :class="{ 'is-invalid': getLineError(index, 'credit_amount') }"
                        >
                        <div v-if="getLineError(index, 'credit_amount')" class="invalid-feedback">
                          {{ getLineError(index, 'credit_amount')[0] }}
                        </div>
                      </td>
                      <td>
                        <input
                          type="text"
                          v-model="line.description"
                          class="form-control"
                          :class="{ 'is-invalid': getLineError(index, 'description') }"
                        >
                        <div v-if="getLineError(index, 'description')" class="invalid-feedback">
                          {{ getLineError(index, 'description')[0] }}
                        </div>
                      </td>
                      <td>
                        <button type="button" class="btn-icon text-danger" @click="removeLine(index)" title="Hapus Baris">
                          <i class="fas fa-trash"></i>
                        </button>
                      </td>
                    </tr>
                  </tbody>
                  <tfoot>
                    <tr>
                      <th class="text-right">Total</th>
                      <th class="text-right">{{ formatCurrency(totalDebit) }}</th>
                      <th class="text-right">{{ formatCurrency(totalCredit) }}</th>
                      <th colspan="2"></th>
                    </tr>
                    <tr>
                      <th class="text-right">Selisih</th>
                      <th colspan="2" class="text-center" :class="{ 'text-danger': !isBalanced, 'text-success': isBalanced }">
                        {{ isBalanced ? 'Seimbang' : formatCurrency(Math.abs(totalDebit - totalCredit)) }}
                      </th>
                      <th colspan="2"></th>
                    </tr>
                  </tfoot>
                </table>
              </div>

              <button type="button" class="btn btn-secondary" @click="addLine">
                <i class="fas fa-plus"></i> Tambah Baris
              </button>
            </div>

            <div class="form-actions">
              <button type="button" class="btn btn-secondary" @click="$router.go(-1)">
                Batal
              </button>
              <button type="submit" class="btn btn-primary" :disabled="isSubmitting || !isBalanced">
                <i v-if="isSubmitting" class="fas fa-spinner fa-spin"></i>
                {{ isEditing ? 'Update Jurnal' : 'Simpan Jurnal' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </template>

  <script>
  import axios from 'axios';

  export default {
    name: 'JournalEntryForm',
    props: {
      id: {
        type: [Number, String],
        required: false
      }
    },
    data() {
      return {
        isLoading: true,
        isSubmitting: false,
        accounts: [],
        periods: [],
        form: {
          journal_number: '',
          entry_date: new Date().toISOString().split('T')[0],
          reference_type: '',
          reference_id: '',
          description: '',
          period_id: '',
          status: 'Draft',
          lines: [this.createEmptyLine()]
        },
        errors: {},
        totalDebit: 0,
        totalCredit: 0
      };
    },
    computed: {
      isEditing() {
        return !!this.id;
      },
      isBalanced() {
        return Math.abs(this.totalDebit - this.totalCredit) < 0.01;
      },
      groupedAccounts() {
        const groups = {};

        this.accounts.forEach(account => {
          if (!groups[account.account_type]) {
            groups[account.account_type] = [];
          }
          groups[account.account_type].push(account);
        });

        return Object.keys(groups).map(type => ({
          type,
          accounts: groups[type].sort((a, b) => a.account_code.localeCompare(b.account_code))
        }));
      }
    },
    created() {
      this.loadData();
    },
    methods: {
      createEmptyLine() {
        return {
          account_id: '',
          debit_amount: 0,
          credit_amount: 0,
          description: ''
        };
      },
      async loadData() {
        this.isLoading = true;

        try {
          const [accountsResponse, periodsResponse] = await Promise.all([
            axios.get('/api/accounting/chart-of-accounts'),
            axios.get('/api/accounting/accounting-periods')
          ]);

          this.accounts = accountsResponse.data.data;
          this.periods = periodsResponse.data.data;

          // Get current period
          const currentPeriodResponse = await axios.get('/api/accounting/accounting-periods/current');
          if (currentPeriodResponse.data.data) {
            this.form.period_id = currentPeriodResponse.data.data.period_id;
          } else if (this.periods.length > 0) {
            // If no current period, use the most recent one
            this.form.period_id = this.periods[0].period_id;
          }

          // If editing, load the journal entry
          if (this.isEditing) {
            const journalResponse = await axios.get(`/api/accounting/journal-entries/${this.id}`);
            const journal = journalResponse.data.data;

            this.form.journal_number = journal.journal_number;
            this.form.entry_date = journal.entry_date;
            this.form.reference_type = journal.reference_type || '';
            this.form.reference_id = journal.reference_id || '';
            this.form.description = journal.description || '';
            this.form.period_id = journal.period_id;
            this.form.status = journal.status;

            if (journal.journal_entry_lines && journal.journal_entry_lines.length > 0) {
              this.form.lines = journal.journal_entry_lines.map(line => ({
                line_id: line.line_id,
                account_id: line.account_id,
                debit_amount: line.debit_amount || 0,
                credit_amount: line.credit_amount || 0,
                description: line.description || ''
              }));
            }
          } else {
            // For new journal, generate journal number
            this.form.journal_number = this.generateJournalNumber();
          }
        } catch (error) {
          this.$toast.error('Gagal memuat data', {
            position: 'top-right',
            duration: 3000
          });
          console.error('Error loading data:', error);
        } finally {
          this.isLoading = false;
          this.updateBalance();
        }
      },
      generateJournalNumber() {
        const date = new Date();
        const year = date.getFullYear().toString().slice(-2);
        const month = (date.getMonth() + 1).toString().padStart(2, '0');
        const day = date.getDate().toString().padStart(2, '0');
        const random = Math.floor(Math.random() * 1000).toString().padStart(3, '0');

        return `JRN${year}${month}${day}${random}`;
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
      formatCurrency(value) {
        return new Intl.NumberFormat('id-ID', {
          style: 'currency',
          currency: 'IDR',
          minimumFractionDigits: 2
        }).format(value);
      },
      updateBalance() {
        this.totalDebit = this.form.lines.reduce((sum, line) => sum + parseFloat(line.debit_amount || 0), 0);
        this.totalCredit = this.form.lines.reduce((sum, line) => sum + parseFloat(line.credit_amount || 0), 0);
      },
      addLine() {
        this.form.lines.push(this.createEmptyLine());
      },
      removeLine(index) {
        if (this.form.lines.length > 1) {
          this.form.lines.splice(index, 1);
          this.updateBalance();
        } else {
          this.$toast.warning('Jurnal harus memiliki minimal satu baris', {
            position: 'top-right',
            duration: 3000
          });
        }
      },
      getLineError(index, field) {
        if (this.errors.lines && this.errors.lines[index]) {
          return this.errors.lines[index][field];
        }
        return null;
      },
      async saveJournalEntry() {
        if (!this.isBalanced) {
          this.$toast.error('Jurnal tidak seimbang. Total debit harus sama dengan total kredit.', {
            position: 'top-right',
            duration: 3000
          });
          return;
        }

        this.isSubmitting = true;
        this.errors = {};

        try {
          let response;

          // Filter out empty lines
          const validLines = this.form.lines.filter(line =>
            line.account_id && (parseFloat(line.debit_amount) > 0 || parseFloat(line.credit_amount) > 0)
          );

          if (validLines.length < 1) {
            this.$toast.error('Jurnal harus memiliki minimal satu baris dengan akun dan nilai yang valid.', {
              position: 'top-right',
              duration: 3000
            });
            this.isSubmitting = false;
            return;
          }

          const formData = {
            ...this.form,
            lines: validLines
          };

          if (this.isEditing) {
            response = await axios.put(`/api/accounting/journal-entries/${this.id}`, formData);
            this.$toast.success('Jurnal berhasil diperbarui', {
              position: 'top-right',
              duration: 3000
            });
          } else {
            response = await axios.post('/api/accounting/journal-entries', formData);
            this.$toast.success('Jurnal berhasil dibuat', {
              position: 'top-right',
              duration: 3000
            });
          }

          // Navigate to the journal detail page
          this.$router.push(`/accounting/journal-entries/${response.data.data.journal_id}`);
        } catch (error) {
          console.error('Error saving journal entry:', error);

          if (error.response && error.response.data && error.response.data.errors) {
            this.errors = error.response.data.errors;

            this.$toast.error('Terdapat kesalahan pada data yang dimasukkan. Silakan periksa kembali.', {
              position: 'top-right',
              duration: 3000
            });
          } else {
            this.$toast.error('Gagal menyimpan jurnal. Silakan coba lagi.', {
              position: 'top-right',
              duration: 3000
            });
          }
        } finally {
          this.isSubmitting = false;
        }
      }
    }
  };
  </script>

  <style scoped>
  .journal-entry-form-container {
    padding: 1rem;
  }

  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
  }

  .form-row {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
  }

  .form-row .form-group {
    flex: 1;
  }

  .form-group {
    margin-bottom: 1rem;
  }

  .form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
  }

  .form-control {
    width: 100%;
    padding: 0.625rem;
    font-size: 0.875rem;
    border: 1px solid var(--gray-300);
    border-radius: 0.375rem;
    transition: border-color 0.2s;
  }

  .form-control:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
  }

  .form-control.is-invalid {
    border-color: var(--danger-color);
  }

  .invalid-feedback {
    color: var(--danger-color);
    font-size: 0.75rem;
    margin-top: 0.25rem;
  }

  .journal-lines {
    margin-top: 2rem;
    margin-bottom: 2rem;
  }

  .journal-lines h3 {
    margin-bottom: 1rem;
  }

  .journal-lines-table {
    margin-bottom: 1rem;
  }

  .journal-lines-table th,
  .journal-lines-table td {
    padding: 0.75rem;
    vertical-align: middle;
  }

  .btn-icon {
    background: none;
    border: none;
    color: var(--primary-color);
    cursor: pointer;
    padding: 0.25rem 0.5rem;
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

  .text-success {
    color: var(--success-color);
  }

  .text-right {
    text-align: right;
  }

  .text-center {
    text-align: center;
  }

  .form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 2rem;
  }

  .alert {
    padding: 0.75rem 1.25rem;
    border-radius: 0.375rem;
    margin-bottom: 1rem;
  }

  .alert-danger {
    background-color: var(--danger-bg);
    color: var(--danger-color);
    border: 1px solid rgba(220, 38, 38, 0.2);
  }
  </style>
