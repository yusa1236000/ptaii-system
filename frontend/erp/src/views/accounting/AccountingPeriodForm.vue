<!-- src/views/accounting/AccountingPeriodForm.vue -->
<template>
    <div class="accounting-period-form">
      <div class="page-header mb-4">
        <h2>{{ isEditing ? 'Edit Periode Akuntansi' : 'Tambah Periode Akuntansi' }}</h2>
      </div>

      <div class="card">
        <div class="card-body">
          <div v-if="isLoading" class="text-center py-5">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p class="mt-2">Memuat data...</p>
          </div>

          <div v-else-if="error" class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            {{ error }}
          </div>

          <form v-else @submit.prevent="savePeriod" class="period-form">
            <div class="form-group">
              <label for="periodName">Nama Periode <span class="text-danger">*</span></label>
              <input
                id="periodName"
                v-model="form.period_name"
                type="text"
                class="form-control"
                :class="{ 'is-invalid': validationErrors.period_name }"
                placeholder="Contoh: Q1 2024, Januari 2024, 2024"
                required
              />
              <div v-if="validationErrors.period_name" class="invalid-feedback">
                {{ validationErrors.period_name[0] }}
              </div>
              <small class="form-text text-muted">
                Nama yang akan digunakan untuk mengidentifikasi periode akuntansi
              </small>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="startDate">Tanggal Mulai <span class="text-danger">*</span></label>
                  <input
                    id="startDate"
                    v-model="form.start_date"
                    type="date"
                    class="form-control"
                    :class="{ 'is-invalid': validationErrors.start_date }"
                    required
                    @change="validateDates"
                  />
                  <div v-if="validationErrors.start_date" class="invalid-feedback">
                    {{ validationErrors.start_date[0] }}
                  </div>
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-group">
                  <label for="endDate">Tanggal Akhir <span class="text-danger">*</span></label>
                  <input
                    id="endDate"
                    v-model="form.end_date"
                    type="date"
                    class="form-control"
                    :class="{ 'is-invalid': validationErrors.end_date }"
                    required
                    @change="validateDates"
                  />
                  <div v-if="validationErrors.end_date" class="invalid-feedback">
                    {{ validationErrors.end_date[0] }}
                  </div>
                </div>
              </div>
            </div>

            <div v-if="dateError" class="alert alert-warning mb-4">
              <i class="fas fa-exclamation-triangle mr-2"></i>
              {{ dateError }}
            </div>

            <div class="form-group">
              <label for="status">Status <span class="text-danger">*</span></label>
              <select
                id="status"
                v-model="form.status"
                class="form-control"
                :class="{ 'is-invalid': validationErrors.status }"
                required
              >
                <option value="Open">Terbuka</option>
                <option value="Closed" :disabled="!isEditing">Tertutup</option>
                <option value="Locked" :disabled="!isEditing">Terkunci</option>
              </select>
              <div v-if="validationErrors.status" class="invalid-feedback">
                {{ validationErrors.status[0] }}
              </div>
              <small class="form-text text-muted">
                <ul class="pl-3 mb-0">
                  <li><strong>Terbuka:</strong> Periode aktif, semua transaksi dapat dicatat</li>
                  <li><strong>Tertutup:</strong> Periode sudah ditutup, tidak dapat melakukan transaksi baru</li>
                  <li><strong>Terkunci:</strong> Periode terkunci permanen, tidak ada perubahan diizinkan</li>
                </ul>
              </small>
            </div>

            <div class="form-group">
              <label>Periksa Overlap dengan Periode Lain</label>
              <div>
                <button type="button" class="btn btn-outline-primary" @click="checkOverlap">
                  <i class="fas fa-calendar-check mr-1"></i> Periksa Tanggal
                </button>
              </div>
              <div v-if="overlapChecked" class="mt-2">
                <div v-if="hasOverlap" class="text-danger">
                  <i class="fas fa-exclamation-circle mr-1"></i>
                  Tanggal tumpang tindih dengan periode lain!
                </div>
                <div v-else class="text-success">
                  <i class="fas fa-check-circle mr-1"></i>
                  Tidak ada tumpang tindih dengan periode lain
                </div>
              </div>
            </div>

            <div class="form-actions mt-4">
              <button type="button" class="btn btn-secondary mr-2" @click="goBack">
                <i class="fas fa-arrow-left mr-1"></i> Kembali
              </button>
              <button type="submit" class="btn btn-primary" :disabled="isSaving || dateError">
                <i class="fas fa-save mr-1"></i>
                <span v-if="isSaving">Menyimpan...</span>
                <span v-else>{{ isEditing ? 'Update Periode' : 'Simpan Periode' }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </template>

  <script>
  import axios from 'axios';
  import { ref, reactive, computed, onMounted } from 'vue';
  import { useRoute, useRouter } from 'vue-router';

  export default {
    name: 'AccountingPeriodForm',
    setup() {
      const route = useRoute();
      const router = useRouter();
      const periodId = computed(() => route.params.id);
      const isEditing = computed(() => !!periodId.value);

      const isLoading = ref(false);
      const isSaving = ref(false);
      const error = ref(null);
      const validationErrors = ref({});
      const dateError = ref(null);
      const overlapChecked = ref(false);
      const hasOverlap = ref(false);

      const form = reactive({
        period_name: '',
        start_date: '',
        end_date: '',
        status: 'Open'
      });

      // Load period data if editing
      const loadPeriod = async () => {
        if (!isEditing.value) return;

        isLoading.value = true;
        error.value = null;

        try {
          const response = await axios.get(`/api/accounting/accounting-periods/${periodId.value}`);
          const period = response.data.data;

          // Format dates for input (YYYY-MM-DD)
          form.period_name = period.period_name;
          form.start_date = formatDateForInput(period.start_date);
          form.end_date = formatDateForInput(period.end_date);
          form.status = period.status;
        } catch (err) {
          console.error('Error loading period:', err);
          error.value = 'Gagal memuat data periode. Silakan coba lagi nanti.';
        } finally {
          isLoading.value = false;
        }
      };

      // Format date for date input (YYYY-MM-DD)
      const formatDateForInput = (dateString) => {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toISOString().split('T')[0];
      };

      // Validate dates
      const validateDates = () => {
        dateError.value = null;
        overlapChecked.value = false;

        if (form.start_date && form.end_date) {
          const startDate = new Date(form.start_date);
          const endDate = new Date(form.end_date);

          if (endDate < startDate) {
            dateError.value = 'Tanggal akhir tidak boleh lebih awal dari tanggal mulai';
          }
        }
      };

      // Check for overlapping periods
      const checkOverlap = async () => {
        if (!form.start_date || !form.end_date) {
          dateError.value = 'Masukkan tanggal mulai dan akhir terlebih dahulu';
          return;
        }

        isLoading.value = true;
        overlapChecked.value = false;
        hasOverlap.value = false;

        try {
          const params = {
            start_date: form.start_date,
            end_date: form.end_date
          };

          if (isEditing.value) {
            params.exclude_id = periodId.value;
          }

          const response = await axios.get('/api/accounting/accounting-periods/check-overlap', { params });
          hasOverlap.value = response.data.overlap;
          overlapChecked.value = true;

          if (hasOverlap.value) {
            dateError.value = 'Rentang tanggal tumpang tindih dengan periode yang sudah ada';
          } else {
            dateError.value = null;
          }
        } catch (err) {
          console.error('Error checking overlap:', err);
          error.value = 'Gagal memeriksa tumpang tindih periode. Silakan coba lagi nanti.';
        } finally {
          isLoading.value = false;
        }
      };

      // Save period
      const savePeriod = async () => {
        if (dateError.value) return;

        isSaving.value = true;
        error.value = null;
        validationErrors.value = {};

        try {
          //let response;

          if (isEditing.value) {
             await axios.put(`/api/accounting/accounting-periods/${periodId.value}`, form);
          } else {
             await axios.post('/api/accounting/accounting-periods', form);
          }

          // Navigate back to list view
          router.push({
            path: '/accounting/periods',
            query: { success: isEditing.value ? 'updated' : 'created' }
          });
        } catch (err) {
          console.error('Error saving period:', err);

          if (err.response?.status === 422) {
            // Validation errors
            validationErrors.value = err.response.data.errors || {};
          } else {
            error.value = 'Gagal menyimpan periode. ' + (err.response?.data?.message || 'Silakan coba lagi nanti.');
          }
        } finally {
          isSaving.value = false;
        }
      };

      // Go back function
      const goBack = () => {
        router.push('/accounting/periods');
      };

      // Lifecycle hooks
      onMounted(() => {
        loadPeriod();
      });

      return {
        isEditing,
        isLoading,
        isSaving,
        error,
        validationErrors,
        dateError,
        form,
        overlapChecked,
        hasOverlap,
        validateDates,
        checkOverlap,
        savePeriod,
        goBack
      };
    }
  };
  </script>

  <style scoped>
  .period-form {
    max-width: 800px;
  }

  .form-actions {
    padding-top: 1rem;
    border-top: 1px solid #e2e8f0;
  }
  </style>
