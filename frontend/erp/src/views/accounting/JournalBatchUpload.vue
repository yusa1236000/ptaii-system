<!-- src/views/accounting/JournalBatchUpload.vue -->
<template>
    <div class="journal-batch-upload">
      <div class="card">
        <div class="card-header">
          <h3>Journal Batch Upload</h3>
        </div>

        <div class="card-body">
          <div class="upload-section mb-4">
            <h4 class="mb-3">Upload Journal Entries</h4>

            <div class="alert alert-info">
              <div class="d-flex">
                <i class="fas fa-info-circle mr-3 mt-1"></i>
                <div>
                  <p class="mb-2">Please upload a CSV file containing journal entries data with the following columns:</p>
                  <ul class="mb-2">
                    <li><strong>journal_number</strong> - Unique identifier for the journal entry</li>
                    <li><strong>entry_date</strong> - Date of the journal entry (YYYY-MM-DD format)</li>
                    <li><strong>description</strong> - Description of the journal entry</li>
                    <li><strong>account_code</strong> - Chart of account code</li>
                    <li><strong>debit_amount</strong> - Debit amount (leave blank for credit entries)</li>
                    <li><strong>credit_amount</strong> - Credit amount (leave blank for debit entries)</li>
                    <li><strong>line_description</strong> - Description for the journal line (optional)</li>
                  </ul>
                  <p class="mb-0">
                    <a href="#" @click.prevent="downloadTemplate" class="text-primary">
                      <i class="fas fa-download mr-1"></i> Download template file
                    </a>
                  </p>
                </div>
              </div>
            </div>

            <div class="form-group mt-4">
              <label for="periodId">Accounting Period</label>
              <select
                id="periodId"
                v-model="periodId"
                class="form-control"
                :class="{ 'is-invalid': submitted && !periodId }"
                required
              >
                <option value="">Select Accounting Period</option>
                <option v-for="period in periods" :key="period.period_id" :value="period.period_id">
                  {{ period.period_name }}
                </option>
              </select>
              <div v-if="submitted && !periodId" class="invalid-feedback">
                Accounting period is required
              </div>
            </div>

            <div class="form-group">
              <label for="fileUpload">CSV File</label>
              <div class="custom-file">
                <input
                  type="file"
                  class="custom-file-input"
                  id="fileUpload"
                  @change="handleFileUpload"
                  accept=".csv"
                  :class="{ 'is-invalid': submitted && !file }"
                >
                <label class="custom-file-label" for="fileUpload">
                  {{ file ? file.name : 'Choose file' }}
                </label>
                <div v-if="submitted && !file" class="invalid-feedback">
                  Please select a CSV file
                </div>
              </div>
            </div>

            <div class="form-group">
              <div class="custom-control custom-checkbox">
                <input
                  type="checkbox"
                  class="custom-control-input"
                  id="autoPost"
                  v-model="autoPost"
                >
                <label class="custom-control-label" for="autoPost">
                  Auto-post journal entries after validation
                </label>
              </div>
            </div>

            <div class="mt-4">
              <button
                @click="uploadFile"
                class="btn btn-primary"
                :disabled="isUploading"
              >
                <i :class="isUploading ? 'fas fa-spinner fa-spin' : 'fas fa-upload'"></i>
                {{ isUploading ? 'Uploading...' : 'Upload and Validate' }}
              </button>

              <router-link to="/accounting/journal-entries" class="btn btn-secondary ml-2">
                <i class="fas fa-arrow-left mr-1"></i> Back to Journal Entries
              </router-link>
            </div>
          </div>

          <!-- Preview Section (shown after validation) -->
          <div v-if="validatedEntries.length > 0" class="preview-section mt-5">
            <h4 class="mb-3">Preview Journal Entries</h4>

            <div v-if="validationErrors.length > 0" class="alert alert-danger mb-4">
              <h5><i class="fas fa-exclamation-circle mr-2"></i> Validation Errors</h5>
              <ul class="mb-0 mt-2">
                <li v-for="(error, index) in validationErrors" :key="index">
                  {{ error }}
                </li>
              </ul>
            </div>

            <div class="table-responsive">
              <table class="table table-bordered table-sm">
                <thead class="bg-light">
                  <tr>
                    <th>Journal #</th>
                    <th>Date</th>
                    <th>Description</th>
                    <th>Account Code</th>
                    <th>Account Name</th>
                    <th class="text-right">Debit</th>
                    <th class="text-right">Credit</th>
                    <th>Line Description</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <template v-for="(entry, entryIndex) in validatedEntries" :key="entryIndex">
                    <tr
                      v-for="(line, lineIndex) in entry.lines"
                      :key="`${entryIndex}-${lineIndex}`"
                      :class="{ 'table-danger': line.error }"
                    >
                      <td v-if="lineIndex === 0" :rowspan="entry.lines.length">
                        {{ entry.journal_number }}
                      </td>
                      <td v-if="lineIndex === 0" :rowspan="entry.lines.length">
                        {{ formatDate(entry.entry_date) }}
                      </td>
                      <td v-if="lineIndex === 0" :rowspan="entry.lines.length">
                        {{ entry.description }}
                      </td>
                      <td>{{ line.account_code }}</td>
                      <td>{{ line.account_name || 'Unknown Account' }}</td>
                      <td class="text-right">{{ formatCurrency(line.debit_amount) }}</td>
                      <td class="text-right">{{ formatCurrency(line.credit_amount) }}</td>
                      <td>{{ line.line_description || '-' }}</td>
                      <td v-if="lineIndex === 0" :rowspan="entry.lines.length">
                        <span
                          :class="entry.is_valid ? 'badge badge-success' : 'badge badge-danger'"
                        >
                          {{ entry.is_valid ? 'Valid' : 'Invalid' }}
                        </span>
                      </td>
                    </tr>
                  </template>
                </tbody>
              </table>
            </div>

            <div class="mt-4">
              <button
                @click="processEntries"
                class="btn btn-success"
                :disabled="isProcessing || validationErrors.length > 0"
              >
                <i :class="isProcessing ? 'fas fa-spinner fa-spin' : 'fas fa-check'"></i>
                {{ isProcessing ? 'Processing...' : 'Process Valid Journal Entries' }}
              </button>
            </div>
          </div>

          <!-- Results Section (shown after processing) -->
          <div v-if="processingComplete" class="results-section mt-5">
            <div class="alert" :class="processingSuccess ? 'alert-success' : 'alert-danger'">
              <h5>
                <i :class="processingSuccess ? 'fas fa-check-circle' : 'fas fa-times-circle'" class="mr-2"></i>
                {{ processingSuccess ? 'Processing Complete' : 'Processing Failed' }}
              </h5>
              <p>{{ processingMessage }}</p>
              <p v-if="processingSuccess">
                <strong>Created:</strong> {{ successCount }} journal entries
                <br>
                <strong>Posted:</strong> {{ autoPost ? successCount : '0' }} journal entries
              </p>
            </div>

            <div class="mt-4">
              <router-link to="/accounting/journal-entries" class="btn btn-primary">
                <i class="fas fa-list mr-1"></i> View Journal Entries
              </router-link>

              <button @click="resetForm" class="btn btn-secondary ml-2">
                <i class="fas fa-redo mr-1"></i> Upload Another Batch
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </template>

  <script>
  import { ref, onMounted } from 'vue';
  //import { useRouter } from 'vue-router';
  import axios from 'axios';
  import Papa from 'papaparse';

  export default {
    name: 'JournalBatchUpload',
    setup() {
      //const router = useRouter();

      // State
      const periodId = ref('');
      const periods = ref([]);
      const file = ref(null);
      const autoPost = ref(false);
      const isUploading = ref(false);
      const isProcessing = ref(false);
      const submitted = ref(false);
      const validatedEntries = ref([]);
      const validationErrors = ref([]);
      const processingComplete = ref(false);
      const processingSuccess = ref(false);
      const processingMessage = ref('');
      const successCount = ref(0);
      const accounts = ref([]);

      // Methods
      const loadPeriods = async () => {
        try {
          const response = await axios.get('/api/accounting/accounting-periods');
          periods.value = response.data.data.filter(period => period.status === 'Open');

          // Set default period if available
          if (periods.value.length > 0) {
            periodId.value = periods.value[0].period_id;
          }
        } catch (error) {
          console.error('Error loading accounting periods:', error);
        }
      };

      const loadAccounts = async () => {
        try {
          const response = await axios.get('/api/accounting/chart-of-accounts');
          accounts.value = response.data.data.filter(account => account.is_active);
        } catch (error) {
          console.error('Error loading chart of accounts:', error);
        }
      };

      const handleFileUpload = (event) => {
        file.value = event.target.files[0];
      };

      const downloadTemplate = () => {
        // Create template CSV content
        const headers = 'journal_number,entry_date,description,account_code,debit_amount,credit_amount,line_description\n';
        const sampleData =
          'JV-2023-001,2023-04-24,Office Rent Payment,1001,,5000000,Bank Account\n' +
          'JV-2023-001,2023-04-24,Office Rent Payment,6001,5000000,,Rent Expense\n' +
          'JV-2023-002,2023-04-25,Utility Bill Payment,1001,,750000,Bank Account\n' +
          'JV-2023-002,2023-04-25,Utility Bill Payment,6002,750000,,Utility Expense';

        const content = headers + sampleData;

        // Create and download the file
        const blob = new Blob([content], { type: 'text/csv' });
        const link = document.createElement('a');
        link.href = window.URL.createObjectURL(blob);
        link.download = 'journal_entries_template.csv';
        link.click();
      };

      const uploadFile = () => {
        submitted.value = true;

        if (!periodId.value || !file.value) {
          return;
        }

        isUploading.value = true;
        validatedEntries.value = [];
        validationErrors.value = [];

        // Parse CSV file
        Papa.parse(file.value, {
          header: true,
          skipEmptyLines: true,
          complete: result => validateCsvData(result.data),
          error: error => {
            isUploading.value = false;
            validationErrors.value.push(`CSV parsing error: ${error.message}`);
          }
        });
      };

      const validateCsvData = (data) => {
        if (!data || data.length === 0) {
          isUploading.value = false;
          validationErrors.value.push('CSV file is empty or invalid');
          return;
        }

        // Group by journal number
        const journalGroups = {};

        data.forEach((row, index) => {
          const lineNum = index + 2; // +2 for header row and 1-indexed

          // Validate required fields
          if (!row.journal_number) {
            validationErrors.value.push(`Line ${lineNum}: Missing journal number`);
            return;
          }

          if (!row.entry_date) {
            validationErrors.value.push(`Line ${lineNum}: Missing entry date`);
            return;
          }

          if (!row.account_code) {
            validationErrors.value.push(`Line ${lineNum}: Missing account code`);
            return;
          }

          // Validate date format
          const dateRegex = /^\d{4}-\d{2}-\d{2}$/;
          if (!dateRegex.test(row.entry_date)) {
            validationErrors.value.push(`Line ${lineNum}: Invalid date format (should be YYYY-MM-DD)`);
            return;
          }

          // Validate amounts
          const debitAmount = parseFloat(row.debit_amount || 0);
          const creditAmount = parseFloat(row.credit_amount || 0);

          if (debitAmount > 0 && creditAmount > 0) {
            validationErrors.value.push(`Line ${lineNum}: Cannot have both debit and credit amounts`);
            return;
          }

          if (debitAmount === 0 && creditAmount === 0) {
            validationErrors.value.push(`Line ${lineNum}: Either debit or credit amount must be greater than zero`);
            return;
          }

          // Validate account code
          const account = accounts.value.find(a => a.account_code === row.account_code);
          const accountError = !account;

          if (accountError) {
            validationErrors.value.push(`Line ${lineNum}: Invalid account code '${row.account_code}'`);
          }

          // Group by journal number
          if (!journalGroups[row.journal_number]) {
            journalGroups[row.journal_number] = {
              journal_number: row.journal_number,
              entry_date: row.entry_date,
              description: row.description || `Journal Entry ${row.journal_number}`,
              period_id: periodId.value,
              lines: [],
              is_valid: true
            };
          }

          // Add line to journal group
          journalGroups[row.journal_number].lines.push({
            account_code: row.account_code,
            account_id: account ? account.account_id : null,
            account_name: account ? account.name : null,
            debit_amount: debitAmount,
            credit_amount: creditAmount,
            line_description: row.line_description || '',
            error: accountError
          });

          // Mark journal as invalid if line has error
          if (accountError) {
            journalGroups[row.journal_number].is_valid = false;
          }
        });

        // Validate each journal entry (debits = credits)
        Object.values(journalGroups).forEach(journal => {
          const totalDebit = journal.lines.reduce((sum, line) => sum + (line.debit_amount || 0), 0);
          const totalCredit = journal.lines.reduce((sum, line) => sum + (line.credit_amount || 0), 0);

          if (Math.abs(totalDebit - totalCredit) > 0.01) {
            journal.is_valid = false;
            validationErrors.value.push(`Journal ${journal.journal_number}: Total debits (${formatCurrency(totalDebit)}) do not equal total credits (${formatCurrency(totalCredit)})`);
          }
        });

        // Set validated entries
        validatedEntries.value = Object.values(journalGroups);

        isUploading.value = false;
      };

      const processEntries = async () => {
        isProcessing.value = true;
        successCount.value = 0;

        try {
          const validEntries = validatedEntries.value.filter(entry => entry.is_valid);

          if (validEntries.length === 0) {
            processingComplete.value = true;
            processingSuccess.value = false;
            processingMessage.value = 'No valid journal entries to process.';
            isProcessing.value = false;
            return;
          }

          // Process each journal entry
          for (const entry of validEntries) {
            try {
              // Prepare journal entry data
              const journalData = {
                journal_number: entry.journal_number,
                entry_date: entry.entry_date,
                description: entry.description,
                period_id: periodId.value,
                status: autoPost.value ? 'Posted' : 'Draft',
                lines: entry.lines.map(line => ({
                  account_id: line.account_id,
                  debit_amount: line.debit_amount || 0,
                  credit_amount: line.credit_amount || 0,
                  description: line.line_description || null
                }))
              };

              // Create journal entry
              const response = await axios.post('/api/accounting/journal-entries', journalData);

              // Post journal entry if auto-post is enabled
              if (autoPost.value && response.data.data && response.data.data.journal_id) {
                await axios.post(`/api/accounting/journal-entries/${response.data.data.journal_id}/post`);
              }

              successCount.value++;
            } catch (error) {
              console.error(`Error processing journal entry ${entry.journal_number}:`, error);
              validationErrors.value.push(`Error creating journal entry ${entry.journal_number}: ${error.response?.data?.message || error.message}`);
            }
          }

          // Set processing results
          processingComplete.value = true;
          processingSuccess.value = successCount.value > 0;
          processingMessage.value = successCount.value === validEntries.length
            ? `Successfully processed all ${successCount.value} journal entries.`
            : `Processed ${successCount.value} out of ${validEntries.length} journal entries. Some entries failed to process.`;
        } catch (error) {
          console.error('Error processing journal entries:', error);
          processingComplete.value = true;
          processingSuccess.value = false;
          processingMessage.value = `Error processing journal entries: ${error.message}`;
        } finally {
          isProcessing.value = false;
        }
      };

      const resetForm = () => {
        file.value = null;
        submitted.value = false;
        validatedEntries.value = [];
        validationErrors.value = [];
        processingComplete.value = false;

        // Reset file input
        const fileInput = document.getElementById('fileUpload');
        if (fileInput) {
          fileInput.value = '';
        }
      };

      const formatDate = (dateString) => {
        if (!dateString) return '';
        const [year, month, day] = dateString.split('-');
        return `${day}/${month}/${year}`;
      };

      const formatCurrency = (amount) => {
        return new Intl.NumberFormat('id-ID', {
          style: 'currency',
          currency: 'IDR',
          minimumFractionDigits: 2
        }).format(parseFloat(amount) || 0);
      };

      // Lifecycle hooks
      onMounted(() => {
        loadPeriods();
        loadAccounts();
      });

      return {
        periodId,
        periods,
        file,
        autoPost,
        isUploading,
        isProcessing,
        submitted,
        validatedEntries,
        validationErrors,
        processingComplete,
        processingSuccess,
        processingMessage,
        successCount,
        handleFileUpload,
        downloadTemplate,
        uploadFile,
        processEntries,
        resetForm,
        formatDate,
        formatCurrency
      };
    }
  };
  </script>

  <style scoped>
  .journal-batch-upload {
    padding: 1rem 0;
  }

  .alert ul {
    padding-left: 1.5rem;
  }

  .badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
  }
  </style>
