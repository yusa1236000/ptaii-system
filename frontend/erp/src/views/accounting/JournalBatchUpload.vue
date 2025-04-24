<!-- src/views/accounting/JournalBatchUpload.vue -->
<template>
    <div class="journal-batch-upload-container">
      <div class="page-header">
        <h1>Upload Batch Jurnal</h1>
        <div class="action-buttons">
          <button @click="$router.go(-1)" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
          </button>
        </div>
      </div>

      <div class="row">
        <div class="col">
          <div class="card">
            <div class="card-header">
              <h2 class="card-title">Upload File</h2>
            </div>
            <div class="card-body">
              <div class="upload-instructions mb-4">
                <h3><i class="fas fa-info-circle"></i> Petunjuk Upload</h3>
                <ul>
                  <li>Format file yang didukung: Excel (.xlsx, .xls) atau CSV (.csv)</li>
                  <li>Maksimal ukuran file: 5MB</li>
                  <li>Kolom yang diperlukan: Nomor Jurnal, Tanggal, Kode Akun, Debit, Kredit, Deskripsi</li>
                  <li>Pastikan total debit dan kredit seimbang untuk setiap jurnal</li>
                  <li>Periode akuntansi akan otomatis diambil berdasarkan tanggal jurnal</li>
                </ul>
                <div class="template-download">
                  <p>Belum memiliki template? Download template di bawah ini:</p>
                  <button @click="downloadTemplate" class="btn btn-outline-primary">
                    <i class="fas fa-download"></i> Download Template
                  </button>
                </div>
              </div>

              <div class="file-upload">
                <div class="upload-box" @click="triggerFileInput" @dragover.prevent @dragenter.prevent="dragEnter" @dragleave.prevent="dragLeave" @drop.prevent="handleFileDrop">
                  <div v-if="!fileSelected">
                    <i class="fas fa-file-upload upload-icon"></i>
                    <p class="upload-text">
                      <span v-if="isDragging">Lepaskan file di sini</span>
                      <span v-else>Klik atau seret file ke sini untuk mengunggah</span>
                    </p>
                    <span class="upload-hint">Excel atau CSV</span>
                  </div>
                  <div v-else class="selected-file">
                    <i :class="fileIcon"></i>
                    <div class="file-info">
                      <p class="file-name">{{ selectedFile.name }}</p>
                      <span class="file-size">{{ formatFileSize(selectedFile.size) }}</span>
                    </div>
                    <button type="button" class="btn-icon" @click.stop="removeFile">
                      <i class="fas fa-times"></i>
                    </button>
                  </div>
                </div>
                <input
                  type="file"
                  ref="fileInput"
                  class="hidden-file-input"
                  accept=".xlsx,.xls,.csv"
                  @change="handleFileChange"
                />
              </div>

              <div class="form-group mt-4">
                <label for="default_period">Periode Akuntansi Default</label>
                <select
                  id="default_period"
                  v-model="defaultPeriodId"
                  class="form-control"
                  required
                >
                  <option value="" disabled>Pilih Periode</option>
                  <option v-for="period in periods" :key="period.period_id" :value="period.period_id">
                    {{ period.period_name }} ({{ formatDate(period.start_date) }} - {{ formatDate(period.end_date) }})
                  </option>
                </select>
                <small class="form-text text-muted">
                  Periode default akan digunakan jika tanggal jurnal tidak cocok dengan periode manapun.
                </small>
              </div>

              <div class="validation-options">
                <label class="checkbox-container">
                  <input type="checkbox" v-model="skipValidation">
                  <span class="checkbox-label">Lewati validasi (tidak disarankan)</span>
                </label>
                <label class="checkbox-container">
                  <input type="checkbox" v-model="dryRun">
                  <span class="checkbox-label">Validasi saja (tidak menyimpan data)</span>
                </label>
              </div>

              <div class="form-actions">
                <button
                  type="button"
                  class="btn btn-primary"
                  @click="uploadFile"
                  :disabled="!fileSelected || isUploading"
                >
                  <i v-if="isUploading" class="fas fa-spinner fa-spin"></i>
                  <i v-else class="fas fa-upload"></i>
                  {{ isUploading ? 'Sedang Mengupload...' : 'Upload Jurnal' }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Preview & Results Section -->
      <div class="row mt-4" v-if="uploadComplete">
        <div class="col">
          <div class="card">
            <div class="card-header">
              <h2 class="card-title">Hasil Upload</h2>
            </div>
            <div class="card-body">
              <!-- Summary -->
              <div class="upload-summary">
                <div class="summary-item" :class="{ 'text-success': successCount > 0 }">
                  <i class="fas fa-check-circle"></i>
                  <div class="summary-text">
                    <span class="summary-count">{{ successCount }}</span>
                    <span class="summary-label">Jurnal Berhasil</span>
                  </div>
                </div>

                <div class="summary-item" :class="{ 'text-danger': errorCount > 0 }">
                  <i class="fas fa-times-circle"></i>
                  <div class="summary-text">
                    <span class="summary-count">{{ errorCount }}</span>
                    <span class="summary-label">Jurnal Gagal</span>
                  </div>
                </div>

                <div class="summary-item">
                  <i class="fas fa-file-alt"></i>
                  <div class="summary-text">
                    <span class="summary-count">{{ totalCount }}</span>
                    <span class="summary-label">Total Jurnal</span>
                  </div>
                </div>
              </div>

              <!-- Success List -->
              <div v-if="successEntries.length > 0" class="result-section">
                <h3 class="text-success">
                  <i class="fas fa-check-circle"></i> Jurnal Berhasil
                </h3>
                <div class="table-responsive">
                  <table class="data-table">
                    <thead>
                      <tr>
                        <th>Nomor Jurnal</th>
                        <th>Tanggal</th>
                        <th>Jumlah Akun</th>
                        <th>Total</th>
                        <th>Aksi</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="entry in successEntries" :key="entry.journal_id">
                        <td>{{ entry.journal_number }}</td>
                        <td>{{ formatDate(entry.entry_date) }}</td>
                        <td>{{ entry.line_count }} akun</td>
                        <td>{{ formatCurrency(entry.total_amount) }}</td>
                        <td>
                          <router-link :to="`/accounting/journal-entries/${entry.journal_id}`" class="btn-icon" title="Lihat Detail">
                            <i class="fas fa-eye"></i>
                          </router-link>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- Error List -->
              <div v-if="errorEntries.length > 0" class="result-section">
                <h3 class="text-danger">
                  <i class="fas fa-times-circle"></i> Jurnal Gagal
                </h3>
                <div class="table-responsive">
                  <table class="data-table">
                    <thead>
                      <tr>
                        <th>Baris</th>
                        <th>Nomor Jurnal</th>
                        <th>Tanggal</th>
                        <th>Kesalahan</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(entry, index) in errorEntries" :key="index">
                        <td>{{ entry.row }}</td>
                        <td>{{ entry.journal_number }}</td>
                        <td>{{ entry.entry_date ? formatDate(entry.entry_date) : '-' }}</td>
                        <td>{{ entry.error }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <div class="mt-3">
                  <button @click="downloadErrorReport" class="btn btn-outline-danger">
                    <i class="fas fa-download"></i> Download Laporan Kesalahan
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </template>

  <script>
  import axios from 'axios';

  export default {
    name: 'JournalBatchUpload',
    data() {
      return {
        selectedFile: null,
        fileSelected: false,
        isDragging: false,
        isUploading: false,
        uploadComplete: false,
        periods: [],
        defaultPeriodId: '',
        skipValidation: false,
        dryRun: false,

        // Upload results
        successCount: 0,
        errorCount: 0,
        totalCount: 0,
        successEntries: [],
        errorEntries: []
      };
    },
    computed: {
      fileIcon() {
        if (!this.selectedFile) return 'fas fa-file';

        const extension = this.selectedFile.name.split('.').pop().toLowerCase();

        if (['xlsx', 'xls'].includes(extension)) {
          return 'fas fa-file-excel';
        } else if (extension === 'csv') {
          return 'fas fa-file-csv';
        }

        return 'fas fa-file';
      }
    },
    created() {
      this.loadPeriods();
    },
    methods: {
      async loadPeriods() {
        try {
          const response = await axios.get('/api/accounting/accounting-periods');
          this.periods = response.data.data;

          // Get current period
          const currentPeriodResponse = await axios.get('/api/accounting/accounting-periods/current');
          if (currentPeriodResponse.data.data) {
            this.defaultPeriodId = currentPeriodResponse.data.data.period_id;
          } else if (this.periods.length > 0) {
            // If no current period, use the most recent one
            this.defaultPeriodId = this.periods[0].period_id;
          }
        } catch (error) {
          this.$toast.error('Gagal memuat data periode', {
            position: 'top-right',
            duration: 3000
          });
          console.error('Error loading periods:', error);
        }
      },
      triggerFileInput() {
        this.$refs.fileInput.click();
      },
      dragEnter() {
        this.isDragging = true;
      },
      dragLeave() {
        this.isDragging = false;
      },
      handleFileDrop(event) {
        this.isDragging = false;
        const files = event.dataTransfer.files;

        if (files.length > 0) {
          this.processFile(files[0]);
        }
      },
      handleFileChange(event) {
        const files = event.target.files;

        if (files.length > 0) {
          this.processFile(files[0]);
        }
      },
      processFile(file) {
        // Check file type
        const extension = file.name.split('.').pop().toLowerCase();
        if (!['xlsx', 'xls', 'csv'].includes(extension)) {
          this.$toast.error('Format file tidak didukung. Harap unggah file Excel (.xlsx, .xls) atau CSV (.csv)', {
            position: 'top-right',
            duration: 3000
          });
          return;
        }

        // Check file size (5MB limit)
        if (file.size > 5 * 1024 * 1024) {
          this.$toast.error('Ukuran file terlalu besar. Maksimal ukuran file adalah 5MB', {
            position: 'top-right',
            duration: 3000
          });
          return;
        }

        this.selectedFile = file;
        this.fileSelected = true;
      },
      removeFile() {
        this.selectedFile = null;
        this.fileSelected = false;
        this.$refs.fileInput.value = '';
      },
      formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';

        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));

        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
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
        }).format(value || 0);
      },
      async uploadFile() {
        if (!this.selectedFile) return;

        if (!this.defaultPeriodId) {
          this.$toast.error('Harap pilih periode akuntansi default', {
            position: 'top-right',
            duration: 3000
          });
          return;
        }

        this.isUploading = true;
        this.uploadComplete = false;

        // Create form data
        const formData = new FormData();
        formData.append('file', this.selectedFile);
        formData.append('default_period_id', this.defaultPeriodId);
        formData.append('skip_validation', this.skipValidation ? '1' : '0');
        formData.append('dry_run', this.dryRun ? '1' : '0');

        try {
          const response = await axios.post('/api/accounting/journal-entries/batch-upload', formData, {
            headers: {
              'Content-Type': 'multipart/form-data'
            }
          });

          this.$toast.success(this.dryRun ? 'Validasi batch jurnal berhasil' : 'Upload batch jurnal berhasil', {
            position: 'top-right',
            duration: 3000
          });

          // Process results
          const result = response.data;
          this.successCount = result.success_count || 0;
          this.errorCount = result.error_count || 0;
          this.totalCount = result.total_count || 0;
          this.successEntries = result.success_entries || [];
          this.errorEntries = result.error_entries || [];

          this.uploadComplete = true;
        } catch (error) {
          console.error('Error uploading journal batch:', error);

          if (error.response && error.response.data && error.response.data.message) {
            this.$toast.error(error.response.data.message, {
              position: 'top-right',
              duration: 3000
            });
          } else {
            this.$toast.error('Gagal mengupload file. Silakan coba lagi.', {
              position: 'top-right',
              duration: 3000
            });
          }
        } finally {
          this.isUploading = false;
        }
      },
      downloadTemplate() {
        // Create a hidden link element and trigger download
        const link = document.createElement('a');
        link.href = '/templates/journal_batch_template.xlsx'; // Path to template file
        link.download = 'journal_batch_template.xlsx';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
      },
      downloadErrorReport() {
        if (this.errorEntries.length === 0) return;

        // Convert error entries to CSV
        let csvContent = 'data:text/csv;charset=utf-8,';
        csvContent += 'Baris,Nomor Jurnal,Tanggal,Kesalahan\n';

        this.errorEntries.forEach(entry => {
          const row = [
            entry.row,
            entry.journal_number || '',
            entry.entry_date || '',
            entry.error || ''
          ].map(value => `"${value}"`).join(',');

          csvContent += row + '\n';
        });

        // Create a hidden link element and trigger download
        const link = document.createElement('a');
        link.href = encodeURI(csvContent);
        link.download = 'journal_upload_errors.csv';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
      }
    }
  };
  </script>

  <style scoped>
  .journal-batch-upload-container {
    padding: 1rem;
  }

  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
  }

  .row {
    display: flex;
    margin: 0 -0.5rem;
  }

  .col {
    padding: 0 0.5rem;
    flex: 1;
  }

  .mt-4 {
    margin-top: 1.5rem;
  }

  .upload-instructions {
    background-color: var(--gray-50);
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 1.5rem;
  }

  .upload-instructions h3 {
    color: var(--primary-color);
    margin-bottom: 0.75rem;
    font-size: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .upload-instructions ul {
    padding-left: 1.5rem;
    margin-bottom: 1rem;
  }

  .upload-instructions li {
    margin-bottom: 0.5rem;
  }

  .template-download {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--gray-200);
  }

  .file-upload {
    margin-bottom: 1.5rem;
  }

  .upload-box {
    border: 2px dashed var(--gray-300);
    border-radius: 0.5rem;
    padding: 2rem;
    text-align: center;
    cursor: pointer;
    transition: border-color 0.2s, background-color 0.2s;
  }

  .upload-box:hover {
    border-color: var(--primary-color);
    background-color: var(--gray-50);
  }

  .upload-icon {
    font-size: 2.5rem;
    color: var(--gray-400);
    margin-bottom: 1rem;
  }

  .upload-text {
    font-size: 1rem;
    color: var(--gray-700);
    margin-bottom: 0.5rem;
  }

  .upload-hint {
    font-size: 0.75rem;
    color: var(--gray-500);
  }

  .hidden-file-input {
    display: none;
  }

  .selected-file {
    display: flex;
    align-items: center;
    padding: 0.75rem;
    background-color: var(--gray-50);
    border-radius: 0.375rem;
  }

  .selected-file i {
    font-size: 1.5rem;
    color: var(--primary-color);
    margin-right: 1rem;
  }

  .file-info {
    flex: 1;
    text-align: left;
  }

  .file-name {
    font-weight: 500;
    margin-bottom: 0.25rem;
  }

  .file-size {
    font-size: 0.75rem;
    color: var(--gray-500);
  }

  .validation-options {
    margin: 1.5rem 0;
    display: flex;
    gap: 2rem;
  }

  .checkbox-container {
    display: flex;
    align-items: center;
    cursor: pointer;
  }

  .checkbox-label {
    margin-left: 0.5rem;
  }

  .form-actions {
    margin-top: 2rem;
    display: flex;
    justify-content: flex-end;
  }

  .upload-summary {
    display: flex;
    gap: 2rem;
    margin-bottom: 2rem;
  }

  .summary-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.5rem;
    background-color: var(--gray-50);
    border-radius: 0.5rem;
    flex: 1;
  }

  .summary-item i {
    font-size: 2rem;
    color: var(--gray-500);
  }

  .summary-item.text-success i {
    color: var(--success-color);
  }

  .summary-item.text-danger i {
    color: var(--danger-color);
  }

  .summary-count {
    display: block;
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
  }

  .summary-label {
    display: block;
    font-size: 0.875rem;
    color: var(--gray-500);
  }

  .result-section {
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid var(--gray-200);
  }

  .result-section h3 {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
    font-size: 1.125rem;
  }

  .text-success {
    color: var(--success-color);
  }

  .text-danger {
    color: var(--danger-color);
  }

  /* Responsive Styles */
  @media (max-width: 768px) {
    .upload-summary {
      flex-direction: column;
      gap: 1rem;
    }
  }
  </style>
