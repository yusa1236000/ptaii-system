<!-- src/views/accounting/TaxFilingPreparation.vue -->
<template>
    <div class="tax-filing-preparation">
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h3 class="card-title">Persiapan Pelaporan Pajak</h3>
          <div class="header-actions">
            <button @click="exportSelectedToCsv" class="btn btn-secondary mr-2" :disabled="selectedTaxes.length === 0">
              <i class="fas fa-file-csv mr-1"></i> Export Terpilih
            </button>
            <button @click="fileBatchTaxes" class="btn btn-success" :disabled="selectedTaxes.length === 0">
              <i class="fas fa-check mr-1"></i> Lapor Terpilih
            </button>
          </div>
        </div>
        <div class="card-body">
          <!-- Filters -->
          <div class="filter-container mb-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="form-group">
                <label for="tax-type">Jenis Pajak</label>
                <select id="tax-type" v-model="filters.taxType" class="form-control" @change="loadPendingTaxes">
                  <option value="">Semua Jenis</option>
                  <option value="PPN">PPN</option>
                  <option value="PPH21">PPH21</option>
                  <option value="PPH23">PPH23</option>
                  <option value="PPH4(2)">PPH4(2)</option>
                  <option value="Other">Lainnya</option>
                </select>
              </div>

              <div class="form-group">
                <label for="period">Periode Pajak</label>
                <input type="month" id="period" v-model="filters.period" class="form-control" @change="loadPendingTaxes" />
              </div>

              <div class="form-group d-flex align-items-end">
                <button @click="loadPendingTaxes" class="btn btn-primary w-100">
                  <i class="fas fa-search mr-1"></i> Tampilkan
                </button>
              </div>
            </div>
          </div>

          <!-- Loading state -->
          <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Memuat data pajak yang harus dilaporkan...</p>
          </div>

          <!-- Due Tax Notifications -->
          <div v-else-if="pendingTaxes.length > 0">
            <div class="alert alert-warning mb-4" v-if="overdueTaxes.length > 0">
              <div class="alert-icon">
                <i class="fas fa-exclamation-triangle"></i>
              </div>
              <div class="alert-content">
                <h5>Perhatian!</h5>
                <p>Terdapat {{ overdueTaxes.length }} transaksi pajak yang sudah melewati tanggal jatuh tempo.</p>
              </div>
            </div>

            <div class="alert alert-info mb-4" v-if="dueSoonTaxes.length > 0">
              <div class="alert-icon">
                <i class="fas fa-info-circle"></i>
              </div>
              <div class="alert-content">
                <h5>Informasi</h5>
                <p>Terdapat {{ dueSoonTaxes.length }} transaksi pajak yang akan jatuh tempo dalam 7 hari ke depan.</p>
              </div>
            </div>

            <!-- Filing Checklist -->
            <div class="card mb-4">
              <div class="card-header">
                <h5 class="card-title">Checklist Persiapan Pelaporan</h5>
              </div>
              <div class="card-body">
                <div class="checklist-container">
                  <div class="checklist-item">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" id="check-data-verification" v-model="checklist.dataVerification">
                      <label class="form-check-label" for="check-data-verification">
                        Verifikasi data transaksi pajak
                      </label>
                    </div>
                    <p class="checklist-description">Pastikan semua data transaksi pajak sudah benar, termasuk jumlah pajak dan periode pajak.</p>
                  </div>

                  <div class="checklist-item">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" id="check-supporting-documents" v-model="checklist.supportingDocuments">
                      <label class="form-check-label" for="check-supporting-documents">
                        Persiapkan dokumen pendukung
                      </label>
                    </div>
                    <p class="checklist-description">Siapkan semua dokumen pendukung yang diperlukan untuk pelaporan pajak, seperti faktur pajak, bukti potong, dll.</p>
                  </div>

                  <div class="checklist-item">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" id="check-tax-form" v-model="checklist.taxForm">
                      <label class="form-check-label" for="check-tax-form">
                        Isi formulir pajak
                      </label>
                    </div>
                    <p class="checklist-description">Lengkapi formulir pajak yang sesuai dengan jenis pajak yang akan dilaporkan.</p>
                  </div>

                  <div class="checklist-item">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" id="check-payment-preparation" v-model="checklist.paymentPreparation">
                      <label class="form-check-label" for="check-payment-preparation">
                        Persiapkan pembayaran
                      </label>
                    </div>
                    <p class="checklist-description">Siapkan pembayaran pajak terutang sesuai dengan jumlah yang tercantum dalam formulir pajak.</p>
                  </div>

                  <div class="checklist-item">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" id="check-submission" v-model="checklist.submission">
                      <label class="form-check-label" for="check-submission">
                        Laporkan pajak
                      </label>
                    </div>
                    <p class="checklist-description">Laporkan pajak ke kantor pajak atau melalui aplikasi pajak online.</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Pending Tax Table -->
            <div class="card mb-4">
              <div class="card-header">
                <h5 class="card-title">Daftar Pajak Yang Perlu Dilaporkan</h5>
              </div>
              <div class="card-body">
                <div class="table-container">
                  <table class="data-table">
                    <thead>
                      <tr>
                        <th>
                          <div class="form-check">
                            <input
                              class="form-check-input"
                              type="checkbox"
                              id="select-all"
                              :checked="selectedTaxes.length === pendingTaxes.length && pendingTaxes.length > 0"
                              @change="toggleSelectAll"
                            >
                          </div>
                        </th>
                        <th>Nomor Pajak</th>
                        <th>Jenis Pajak</th>
                        <th>Periode Pajak</th>
                        <th>Tanggal Transaksi</th>
                        <th>Jatuh Tempo</th>
                        <th class="text-right">Jumlah</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Aksi</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr
                        v-for="tax in pendingTaxes"
                        :key="tax.tax_transaction_id"
                        :class="{ 'overdue-row': isOverdue(tax.due_date) }"
                      >
                        <td>
                          <div class="form-check">
                            <input
                              class="form-check-input"
                              type="checkbox"
                              :id="`select-tax-${tax.tax_transaction_id}`"
                              v-model="selectedTaxes"
                              :value="tax.tax_transaction_id"
                            >
                          </div>
                        </td>
                        <td>{{ tax.tax_number }}</td>
                        <td>
                          <span :class="getTaxTypeBadgeClass(tax.tax_type)">{{ tax.tax_type }}</span>
                        </td>
                        <td>{{ formatMonthYear(tax.tax_period) }}</td>
                        <td>{{ formatDate(tax.transaction_date) }}</td>
                        <td :class="{ 'text-danger': isOverdue(tax.due_date) }">
                          {{ formatDate(tax.due_date) }}
                          <span v-if="isOverdue(tax.due_date)" class="badge badge-danger ml-1">Terlambat</span>
                          <span v-else-if="isDueSoon(tax.due_date)" class="badge badge-warning ml-1">Segera</span>
                        </td>
                        <td class="text-right">{{ formatCurrency(tax.tax_amount) }}</td>
                        <td class="text-center">
                          <span :class="getStatusBadgeClass(tax.status)">{{ tax.status }}</span>
                        </td>
                        <td class="text-center">
                          <div class="btn-group">
                            <router-link :to="`/accounting/tax-transactions/${tax.tax_transaction_id}`" class="btn btn-sm btn-info">
                              <i class="fas fa-eye"></i>
                            </router-link>
                            <router-link :to="`/accounting/tax-transactions/${tax.tax_transaction_id}/edit`" class="btn btn-sm btn-primary">
                              <i class="fas fa-edit"></i>
                            </router-link>
                            <button @click="confirmFileTax(tax)" class="btn btn-sm btn-success">
                              <i class="fas fa-check"></i>
                            </button>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <!-- Summary and Stats -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
              <div class="card">
                <div class="card-header">
                  <h5 class="card-title">Ringkasan</h5>
                </div>
                <div class="card-body">
                  <div class="summary-stats">
                    <div class="summary-stat-item">
                      <span class="summary-label">Total Pajak Terutang</span>
                      <span class="summary-value">{{ formatCurrency(totalPendingAmount) }}</span>
                    </div>
                    <div class="summary-stat-item">
                      <span class="summary-label">Jumlah Transaksi</span>
                      <span class="summary-value">{{ pendingTaxes.length }}</span>
                    </div>
                    <div class="summary-stat-item">
                      <span class="summary-label">Pajak Terlambat</span>
                      <span class="summary-value text-danger">{{ overdueTaxes.length }}</span>
                    </div>
                    <div class="summary-stat-item">
                      <span class="summary-label">Pajak Jatuh Tempo Dalam 7 Hari</span>
                      <span class="summary-value text-warning">{{ dueSoonTaxes.length }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <div class="card">
                <div class="card-header">
                  <h5 class="card-title">Breakdown Jenis Pajak</h5>
                </div>
                <div class="card-body">
                  <div class="tax-type-breakdown">
                    <div v-for="(count, type) in taxTypeBreakdown" :key="type" class="breakdown-item">
                      <span :class="getTaxTypeBadgeClass(type)">{{ type }}</span>
                      <div class="breakdown-progress">
                        <div
                          class="progress-bar"
                          :style="{ width: `${(count / pendingTaxes.length) * 100}%` }"
                          :class="getProgressColorClass(type)"
                        ></div>
                      </div>
                      <span class="breakdown-count">{{ count }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Filing Instructions -->
            <div class="card">
              <div class="card-header">
                <h5 class="card-title">Panduan Pelaporan</h5>
              </div>
              <div class="card-body">
                <div class="accordion">
                  <div class="accordion-item" :class="{ 'active': activeAccordion === 'general' }">
                    <div class="accordion-header" @click="toggleAccordion('general')">
                      <h6>Informasi Umum</h6>
                      <i class="fas" :class="activeAccordion === 'general' ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                    </div>
                    <div class="accordion-content" v-show="activeAccordion === 'general'">
                      <p>Pelaporan pajak adalah kewajiban wajib pajak untuk melaporkan perhitungan dan/atau pembayaran pajak, objek pajak dan/atau bukan objek pajak, dan/atau harta dan kewajiban.</p>
                      <p>Pelaporan pajak harus dilakukan sesuai dengan peraturan perpajakan yang berlaku. Pastikan semua data dan dokumen pendukung sudah lengkap dan benar sebelum melakukan pelaporan.</p>
                    </div>
                  </div>

                  <div class="accordion-item" :class="{ 'active': activeAccordion === 'pph21' }">
                    <div class="accordion-header" @click="toggleAccordion('pph21')">
                      <h6>Pelaporan PPh 21</h6>
                      <i class="fas" :class="activeAccordion === 'pph21' ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                    </div>
                    <div class="accordion-content" v-show="activeAccordion === 'pph21'">
                      <ol>
                        <li>Persiapkan formulir SPT Masa PPh 21 dan lampiran-lampirannya.</li>
                        <li>Isi formulir dengan benar dan lengkap sesuai dengan data pembayaran yang dilakukan.</li>
                        <li>Laporkan SPT Masa PPh 21 paling lambat tanggal 20 bulan berikutnya setelah masa pajak berakhir.</li>
                        <li>Jika tanggal 20 jatuh pada hari libur, maka pelaporan dilakukan pada hari kerja berikutnya.</li>
                      </ol>
                    </div>
                  </div>

                  <div class="accordion-item" :class="{ 'active': activeAccordion === 'ppn' }">
                    <div class="accordion-header" @click="toggleAccordion('ppn')">
                      <h6>Pelaporan PPN</h6>
                      <i class="fas" :class="activeAccordion === 'ppn' ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                    </div>
                    <div class="accordion-content" v-show="activeAccordion === 'ppn'">
                      <ol>
                        <li>Persiapkan formulir SPT Masa PPN dan lampiran-lampirannya.</li>
                        <li>Isi formulir dengan benar dan lengkap sesuai dengan data pembayaran yang dilakukan.</li>
                        <li>Laporkan SPT Masa PPN paling lambat akhir bulan berikutnya setelah masa pajak berakhir.</li>
                        <li>Pastikan semua faktur pajak sudah diinput dengan benar dan lengkap.</li>
                      </ol>
                    </div>
                  </div>

                  <div class="accordion-item" :class="{ 'active': activeAccordion === 'pph23' }">
                    <div class="accordion-header" @click="toggleAccordion('pph23')">
                      <h6>Pelaporan PPh 23</h6>
                      <i class="fas" :class="activeAccordion === 'pph23' ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                    </div>
                    <div class="accordion-content" v-show="activeAccordion === 'pph23'">
                      <ol>
                        <li>Persiapkan formulir SPT Masa PPh 23 dan lampiran-lampirannya.</li>
                        <li>Isi formulir dengan benar dan lengkap sesuai dengan data pembayaran yang dilakukan.</li>
                        <li>Laporkan SPT Masa PPh 23 paling lambat tanggal 20 bulan berikutnya setelah masa pajak berakhir.</li>
                        <li>Jika tanggal 20 jatuh pada hari libur, maka pelaporan dilakukan pada hari kerja berikutnya.</li>
                      </ol>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Empty state -->
          <div v-else class="empty-state py-5">
            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
            <h4>Tidak ada pajak yang perlu dilaporkan</h4>
            <p>Saat ini tidak ada transaksi pajak yang perlu dilaporkan berdasarkan filter yang dipilih.</p>
          </div>
        </div>
      </div>

      <!-- Confirmation Modal -->
      <ConfirmationModal
        v-if="showConfirmationModal"
        :title="confirmationModal.title"
        :message="confirmationModal.message"
        :confirm-button-text="confirmationModal.confirmButtonText"
        :confirm-button-class="confirmationModal.confirmButtonClass"
        @confirm="confirmationModal.onConfirm"
        @close="showConfirmationModal = false"
      />
    </div>
  </template>

  <script>
  import { ref, reactive, computed, onMounted } from 'vue';
  import axios from 'axios';

  export default {
    name: 'TaxFilingPreparation',
    setup() {
      const pendingTaxes = ref([]);
      const selectedTaxes = ref([]);
      const loading = ref(false);
      const activeAccordion = ref('general');

      const filters = reactive({
        taxType: '',
        period: new Date().toISOString().slice(0, 7) // Default to current month
      });

      const checklist = reactive({
        dataVerification: false,
        supportingDocuments: false,
        taxForm: false,
        paymentPreparation: false,
        submission: false
      });

      const showConfirmationModal = ref(false);
      const confirmationModal = reactive({
        title: '',
        message: '',
        confirmButtonText: '',
        confirmButtonClass: '',
        onConfirm: () => {}
      });

      const overdueTaxes = computed(() => {
        const today = new Date();
        return pendingTaxes.value.filter(tax => {
          const dueDate = new Date(tax.due_date);
          return dueDate < today;
        });
      });

      const dueSoonTaxes = computed(() => {
        const today = new Date();
        const nextWeek = new Date();
        nextWeek.setDate(today.getDate() + 7);

        return pendingTaxes.value.filter(tax => {
          const dueDate = new Date(tax.due_date);
          return dueDate >= today && dueDate <= nextWeek;
        });
      });

      const totalPendingAmount = computed(() => {
        return pendingTaxes.value.reduce((sum, tax) => sum + parseFloat(tax.tax_amount), 0);
      });

      const taxTypeBreakdown = computed(() => {
        const breakdown = {};

        pendingTaxes.value.forEach(tax => {
          breakdown[tax.tax_type] = (breakdown[tax.tax_type] || 0) + 1;
        });

        return breakdown;
      });

      const loadPendingTaxes = async () => {
        loading.value = true;
        try {
          const response = await axios.get('/api/accounting/tax-transactions', {
            params: {
              status: 'Pending',
              tax_type: filters.taxType,
              tax_period: filters.period
            }
          });

          pendingTaxes.value = response.data.data;
          selectedTaxes.value = []; // Reset selections when loading new data
        } catch (error) {
          console.error('Failed to load pending taxes:', error);
          alert('Failed to load pending taxes. Please try again later.');
          pendingTaxes.value = [];
        } finally {
          loading.value = false;
        }
      };

      const toggleAccordion = (section) => {
        activeAccordion.value = activeAccordion.value === section ? null : section;
      };

      const toggleSelectAll = (event) => {
        if (event.target.checked) {
          selectedTaxes.value = pendingTaxes.value.map(tax => tax.tax_transaction_id);
        } else {
          selectedTaxes.value = [];
        }
      };

      const isOverdue = (dateString) => {
        const today = new Date();
        const dueDate = new Date(dateString);
        return dueDate < today;
      };

      const isDueSoon = (dateString) => {
        const today = new Date();
        const nextWeek = new Date();
        nextWeek.setDate(today.getDate() + 7);

        const dueDate = new Date(dateString);
        return dueDate >= today && dueDate <= nextWeek;
      };

      const formatDate = (dateString) => {
        if (!dateString) return '-';
        const options = { year: 'numeric', month: 'short', day: 'numeric' };
        return new Date(dateString).toLocaleDateString('id-ID', options);
      };

      const formatMonthYear = (dateString) => {
        if (!dateString) return '-';
        const [year, month] = dateString.split('-');
        const date = new Date(parseInt(year), parseInt(month) - 1, 1);
        const options = { year: 'numeric', month: 'long' };
        return date.toLocaleDateString('id-ID', options);
      };

      const formatCurrency = (amount) => {
        return new Intl.NumberFormat('id-ID', {
          style: 'currency',
          currency: 'IDR',
          minimumFractionDigits: 0,
          maximumFractionDigits: 0
        }).format(amount || 0);
      };

      const getTaxTypeBadgeClass = (taxType) => {
        const classes = {
          'PPN': 'badge badge-primary',
          'PPH21': 'badge badge-info',
          'PPH23': 'badge badge-warning',
          'PPH4(2)': 'badge badge-secondary',
          'Other': 'badge badge-dark'
        };
        return classes[taxType] || 'badge badge-dark';
      };

      const getStatusBadgeClass = (status) => {
        const classes = {
          'Pending': 'badge badge-warning',
          'Filed': 'badge badge-info',
          'Paid': 'badge badge-success'
        };
        return classes[status] || 'badge badge-secondary';
      };

      const getProgressColorClass = (taxType) => {
        const classes = {
          'PPN': 'bg-primary',
          'PPH21': 'bg-info',
          'PPH23': 'bg-warning',
          'PPH4(2)': 'bg-secondary',
          'Other': 'bg-dark'
        };
        return classes[taxType] || 'bg-dark';
      };

      const confirmFileTax = (tax) => {
        showConfirmationModal.value = true;
        confirmationModal.title = 'Konfirmasi Pelaporan Pajak';
        confirmationModal.message = `Apakah Anda yakin ingin melaporkan pajak <strong>${tax.tax_number}</strong>?`;
        confirmationModal.confirmButtonText = 'Laporkan';
        confirmationModal.confirmButtonClass = 'btn btn-success';
        confirmationModal.onConfirm = () => fileTax(tax.tax_transaction_id);
      };

      const fileTax = async (id) => {
        try {
          await axios.post(`/api/accounting/tax-transactions/${id}/file`);
          showConfirmationModal.value = false;
          alert('Pajak berhasil dilaporkan!');
          loadPendingTaxes(); // Reload the data
        } catch (error) {
          console.error('Failed to file tax:', error);
          alert('Failed to file tax. Please try again later.');
        }
      };

      const fileBatchTaxes = async () => {
        if (selectedTaxes.value.length === 0) return;

        showConfirmationModal.value = true;
        confirmationModal.title = 'Konfirmasi Pelaporan Batch';
        confirmationModal.message = `Apakah Anda yakin ingin melaporkan ${selectedTaxes.value.length} transaksi pajak yang dipilih?`;
        confirmationModal.confirmButtonText = 'Laporkan Semua';
        confirmationModal.confirmButtonClass = 'btn btn-success';
        confirmationModal.onConfirm = processFileBatchTaxes;
      };

      const processFileBatchTaxes = async () => {
        try {
          await axios.post('/api/accounting/tax-transactions/batch-file', {
            tax_ids: selectedTaxes.value
          });
          showConfirmationModal.value = false;
          alert('Semua pajak terpilih berhasil dilaporkan!');
          loadPendingTaxes(); // Reload the data
        } catch (error) {
          console.error('Failed to file batch taxes:', error);
          alert('Failed to file batch taxes. Please try again later.');
        }
      };

      const exportSelectedToCsv = () => {
        if (selectedTaxes.value.length === 0) return;

        // Filter selected taxes
        const selectedTaxData = pendingTaxes.value.filter(tax =>
          selectedTaxes.value.includes(tax.tax_transaction_id)
        );

        const header = 'Nomor Pajak,Jenis Pajak,Periode Pajak,Tanggal Transaksi,Jatuh Tempo,Jumlah\n';
        const rows = selectedTaxData.map(tax =>
          `${tax.tax_number},${tax.tax_type},${tax.tax_period},${tax.transaction_date},${tax.due_date},${tax.tax_amount}`
        ).join('\n');

        const csvContent = `data:text/csv;charset=utf-8,${header}${rows}`;
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement('a');
        link.setAttribute('href', encodedUri);
        link.setAttribute('download', `Selected_Tax_Transactions_${new Date().toISOString().slice(0, 10)}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
      };

      onMounted(() => {
        loadPendingTaxes();
      });

      return {
        pendingTaxes,
        selectedTaxes,
        loading,
        filters,
        checklist,
        activeAccordion,
        overdueTaxes,
        dueSoonTaxes,
        totalPendingAmount,
        taxTypeBreakdown,
        showConfirmationModal,
        confirmationModal,
        loadPendingTaxes,
        toggleAccordion,
        toggleSelectAll,
        isOverdue,
        isDueSoon,
        formatDate,
        formatMonthYear,
        formatCurrency,
        getTaxTypeBadgeClass,
        getStatusBadgeClass,
        getProgressColorClass,
        confirmFileTax,
        fileBatchTaxes,
        exportSelectedToCsv
      };
    }
  };
  </script>

  <style scoped>
  .tax-filing-preparation {
    margin-bottom: 2rem;
  }

  .filter-container {
    padding: 1rem;
    background-color: var(--gray-50);
    border-radius: 0.375rem;
    border: 1px solid var(--gray-200);
  }

  .alert {
    display: flex;
    padding: 1rem;
    border-radius: 0.375rem;
    margin-bottom: 1rem;
  }

  .alert-warning {
    background-color: var(--warning-bg);
    border: 1px solid var(--warning-color);
  }

  .alert-info {
    background-color: #e0f7fa;
    border: 1px solid #0288d1;
  }

  .alert-icon {
    font-size: 1.5rem;
    margin-right: 1rem;
  }

  .alert-warning .alert-icon {
    color: var(--warning-color);
  }

  .alert-info .alert-icon {
    color: #0288d1;
  }

  .alert-content h5 {
    margin-bottom: 0.25rem;
    font-weight: 600;
  }

  .alert-content p {
    margin: 0;
  }

  .checklist-container {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }

  .checklist-item {
    padding: 1rem;
    background-color: var(--gray-50);
    border-radius: 0.375rem;
    border: 1px solid var(--gray-200);
  }

  .form-check {
    display: flex;
    align-items: center;
    margin-bottom: 0.5rem;
  }

  .form-check-input {
    margin-right: 0.5rem;
    width: 1.25rem;
    height: 1.25rem;
  }

  .form-check-label {
    font-weight: 500;
  }

  .checklist-description {
    margin: 0;
    padding-left: 1.75rem;
    color: var(--gray-600);
    font-size: 0.875rem;
  }

  .summary-stats {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }

  .summary-stat-item {
    display: flex;
    justify-content: space-between;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid var(--gray-200);
  }

  .summary-stat-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
  }

  .summary-label {
    color: var(--gray-600);
  }

  .summary-value {
    font-weight: 600;
  }

  .tax-type-breakdown {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }

  .breakdown-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
  }

  .breakdown-progress {
    flex-grow: 1;
    height: 0.5rem;
    background-color: var(--gray-200);
    border-radius: 9999px;
    overflow: hidden;
  }

  .progress-bar {
    height: 100%;
    border-radius: 9999px;
  }

  .breakdown-count {
    font-weight: 500;
    min-width: 2rem;
    text-align: right;
  }

  .accordion {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .accordion-item {
    border: 1px solid var(--gray-200);
    border-radius: 0.375rem;
    overflow: hidden;
  }

  .accordion-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background-color: var(--gray-50);
    cursor: pointer;
  }

  .accordion-header h6 {
    margin: 0;
    font-weight: 500;
  }

  .accordion-content {
    padding: 1rem;
    background-color: white;
  }

  .accordion-content p {
    margin-bottom: 0.75rem;
  }

  .accordion-content ol {
    margin-bottom: 0;
    padding-left: 1.5rem;
  }

  .accordion-content li {
    margin-bottom: 0.5rem;
  }

  .accordion-content li:last-child {
    margin-bottom: 0;
  }

  .data-table {
    width: 100%;
    border-collapse: collapse;
  }

  .data-table th,
  .data-table td {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--gray-200);
  }

  .data-table th {
    background-color: var(--gray-50);
    font-weight: 500;
    color: var(--gray-600);
    text-align: left;
  }

  .overdue-row {
    background-color: rgba(220, 38, 38, 0.05);
  }

  .badge {
    padding: 0.375rem 0.75rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
  }

  .badge-primary {
    background-color: var(--primary-bg);
    color: var(--primary-color);
  }

  .badge-info {
    background-color: #e0f7fa;
    color: #0288d1;
  }

  .badge-success {
    background-color: var(--success-bg);
    color: var(--success-color);
  }

  .badge-warning {
    background-color: var(--warning-bg);
    color: var(--warning-color);
  }

  .badge-danger {
    background-color: var(--danger-bg);
    color: var(--danger-color);
  }

  .badge-secondary {
    background-color: var(--gray-100);
    color: var(--gray-700);
  }

  .badge-dark {
    background-color: #e0e0e0;
    color: #424242;
  }

  .text-danger {
    color: var(--danger-color);
  }

  .text-warning {
    color: var(--warning-color);
  }

  .text-success {
    color: var(--success-color);
  }

  .btn-group {
    display: flex;
    gap: 0.25rem;
  }

  .ml-1 {
    margin-left: 0.25rem;
  }

  .mr-1 {
    margin-right: 0.25rem;
  }

  .mr-2 {
    margin-right: 0.5rem;
  }

  .mb-3 {
    margin-bottom: 1rem;
  }

  .mb-4 {
    margin-bottom: 1.5rem;
  }

  .mt-2 {
    margin-top: 0.5rem;
  }

  .py-5 {
    padding-top: 1.5rem;
    padding-bottom: 1.5rem;
  }

  .w-100 {
    width: 100%;
  }

  .form-group {
    margin-bottom: 1rem;
  }

  .form-control {
    width: 100%;
    padding: 0.625rem;
    font-size: 0.875rem;
    line-height: 1.5;
    border: 1px solid var(--gray-300);
    border-radius: 0.375rem;
    transition: border-color 0.2s;
  }

  .text-center {
    text-align: center;
  }

  .text-right {
    text-align: right;
  }

  .gap-4 {
    gap: 1rem;
  }

  .bg-primary {
    background-color: var(--primary-color);
  }

  .bg-info {
    background-color: #0288d1;
  }

  .bg-success {
    background-color: var(--success-color);
  }

  .bg-warning {
    background-color: var(--warning-color);
  }

  .bg-secondary {
    background-color: var(--gray-600);
  }

  .bg-dark {
    background-color: #424242;
  }

  @media (max-width: 768px) {
    .header-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;
    }

    .btn-group {
      display: flex;
      flex-direction: column;
      gap: 0.25rem;
    }
  }
  </style>
