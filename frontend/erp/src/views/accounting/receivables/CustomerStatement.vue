<!-- src/views/accounting/receivables/CustomerStatement.vue -->
<template>
    <div class="customer-statement">
      <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
          <h2>Laporan Statement Pelanggan</h2>
          <div class="action-buttons">
            <router-link to="/accounting/receivables" class="btn btn-outline-secondary mr-2">
              <i class="fas fa-arrow-left mr-1"></i> Kembali
            </router-link>
            <button class="btn btn-primary" @click="printStatement">
              <i class="fas fa-print mr-1"></i> Cetak Statement
            </button>
          </div>
        </div>
      </div>

      <!-- Filter Form -->
      <div class="card mb-4">
        <div class="card-body">
          <div class="d-flex flex-wrap align-items-end">
            <div class="form-group mr-3 mb-2">
              <label for="customerId">Pelanggan</label>
              <select
                id="customerId"
                v-model="filters.customerId"
                class="form-control"
                @change="loadStatementData"
              >
                <option value="">Pilih Pelanggan</option>
                <option v-for="customer in customers" :key="customer.customer_id" :value="customer.customer_id">
                  {{ customer.name }}
                </option>
              </select>
            </div>
            <div class="form-group mr-3 mb-2">
              <label for="startDate">Dari Tanggal</label>
              <input
                type="date"
                id="startDate"
                v-model="filters.startDate"
                class="form-control"
                @change="loadStatementData"
              />
            </div>
            <div class="form-group mr-3 mb-2">
              <label for="endDate">Sampai Tanggal</label>
              <input
                type="date"
                id="endDate"
                v-model="filters.endDate"
                class="form-control"
                @change="loadStatementData"
              />
            </div>
            <div class="form-group mb-2">
              <button class="btn btn-secondary" @click="resetFilters">
                <i class="fas fa-redo mr-1"></i> Reset
              </button>
            </div>
          </div>
        </div>
      </div>

      <div v-if="isLoading" class="text-center py-5">
        <i class="fas fa-spinner fa-spin fa-2x"></i>
        <p class="mt-2">Memuat data statement...</p>
      </div>

      <div v-else-if="error" class="alert alert-danger" role="alert">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        {{ error }}
      </div>

      <div v-else-if="!selectedCustomer" class="alert alert-info" role="alert">
        <i class="fas fa-info-circle mr-2"></i>
        Silakan pilih pelanggan untuk melihat statement
      </div>

      <div v-else id="printableStatement">
        <!-- Statement Header -->
        <div class="statement-header">
          <div class="company-info">
            <h2 class="company-name">PT. NAMA PERUSAHAAN</h2>
            <p class="company-address">Jl. Alamat Perusahaan No. 123, Jakarta</p>
            <p class="company-contact">Telp: (021) 123-4567 | Email: info@company.com</p>
          </div>

          <div class="statement-title">
            <h1>STATEMENT OF ACCOUNT</h1>
            <p class="statement-period">
              Periode: {{ formatDate(filters.startDate) }} - {{ formatDate(filters.endDate) }}
            </p>
          </div>

          <div class="customer-info">
            <div class="customer-name">{{ selectedCustomer.name }}</div>
            <div v-if="selectedCustomer.address">{{ selectedCustomer.address }}</div>
            <div v-if="selectedCustomer.phone">Telp: {{ selectedCustomer.phone }}</div>
            <div v-if="selectedCustomer.email">Email: {{ selectedCustomer.email }}</div>
          </div>
        </div>

        <!-- Statement Summary -->
        <div class="statement-summary">
          <div class="summary-item">
            <div class="summary-label">Saldo Awal</div>
            <div class="summary-value">{{ formatCurrency(openingBalance) }}</div>
          </div>
          <div class="summary-item">
            <div class="summary-label">Total Faktur</div>
            <div class="summary-value">{{ formatCurrency(totalInvoiced) }}</div>
          </div>
          <div class="summary-item">
            <div class="summary-label">Total Pembayaran</div>
            <div class="summary-value">{{ formatCurrency(totalPayments) }}</div>
          </div>
          <div class="summary-item total">
            <div class="summary-label">Saldo Akhir</div>
            <div class="summary-value">{{ formatCurrency(closingBalance) }}</div>
          </div>
        </div>

        <!-- Transactions Table -->
        <div class="card mb-4">
          <div class="card-header">
            <h3 class="card-title">Detail Transaksi</h3>
          </div>
          <div class="card-body">
            <div v-if="transactions.length === 0" class="alert alert-info">
              <i class="fas fa-info-circle mr-2"></i>
              Tidak ada transaksi untuk periode yang dipilih
            </div>
            <div v-else class="table-responsive">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Tanggal</th>
                    <th>No. Referensi</th>
                    <th>Deskripsi</th>
                    <th class="text-right">Debit</th>
                    <th class="text-right">Kredit</th>
                    <th class="text-right">Saldo</th>
                  </tr>
                </thead>
                <tbody>
                  <!-- Opening Balance Row -->
                  <tr class="opening-balance-row">
                    <td>{{ formatDate(filters.startDate) }}</td>
                    <td>-</td>
                    <td><strong>Saldo Awal</strong></td>
                    <td class="text-right">-</td>
                    <td class="text-right">-</td>
                    <td class="text-right">{{ formatCurrency(openingBalance) }}</td>
                  </tr>

                  <!-- Transaction Rows -->
                  <tr v-for="(transaction, index) in transactions" :key="index" :class="{ 'overdue': isOverdue(transaction) }">
                    <td>{{ formatDate(transaction.date) }}</td>
                    <td>{{ transaction.reference_number }}</td>
                    <td>{{ transaction.description }}</td>
                    <td class="text-right">{{ transaction.type === 'invoice' ? formatCurrency(transaction.amount) : '-' }}</td>
                    <td class="text-right">{{ transaction.type === 'payment' ? formatCurrency(transaction.amount) : '-' }}</td>
                    <td class="text-right">{{ formatCurrency(calculateRunningBalance(index)) }}</td>
                  </tr>
                </tbody>
                <tfoot>
                  <tr class="font-weight-bold">
                    <td colspan="3" class="text-right">Total</td>
                    <td class="text-right">{{ formatCurrency(totalInvoiced) }}</td>
                    <td class="text-right">{{ formatCurrency(totalPayments) }}</td>
                    <td class="text-right">{{ formatCurrency(closingBalance) }}</td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
        </div>

        <!-- Aging Summary -->
        <div class="card mb-4">
          <div class="card-header">
            <h3 class="card-title">Aging Piutang</h3>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Belum Jatuh Tempo</th>
                    <th>1-30 Hari</th>
                    <th>31-60 Hari</th>
                    <th>61-90 Hari</th>
                    <th>> 90 Hari</th>
                    <th>Total</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>{{ formatCurrency(aging.current_amount) }}</td>
                    <td>{{ formatCurrency(aging.days_1_30) }}</td>
                    <td>{{ formatCurrency(aging.days_31_60) }}</td>
                    <td>{{ formatCurrency(aging.days_61_90) }}</td>
                    <td>{{ formatCurrency(aging.days_over_90) }}</td>
                    <td class="font-weight-bold">{{ formatCurrency(closingBalance) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Open Invoices -->
        <div class="card mb-4">
          <div class="card-header">
            <h3 class="card-title">Faktur Belum Lunas</h3>
          </div>
          <div class="card-body">
            <div v-if="openInvoices.length === 0" class="alert alert-info">
              <i class="fas fa-info-circle mr-2"></i>
              Tidak ada faktur yang belum lunas
            </div>
            <div v-else class="table-responsive">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>No. Faktur</th>
                    <th>Tanggal</th>
                    <th>Jatuh Tempo</th>
                    <th class="text-right">Jumlah</th>
                    <th class="text-right">Terbayar</th>
                    <th class="text-right">Sisa</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="invoice in openInvoices" :key="invoice.receivable_id"
                      :class="{ 'overdue': isOverdue(invoice) }">
                    <td>{{ invoice.sales_invoice?.invoice_number || 'N/A' }}</td>
                    <td>{{ formatDate(invoice.sales_invoice?.invoice_date) }}</td>
                    <td>{{ formatDate(invoice.due_date) }}</td>
                    <td class="text-right">{{ formatCurrency(invoice.amount) }}</td>
                    <td class="text-right">{{ formatCurrency(invoice.paid_amount) }}</td>
                    <td class="text-right font-weight-bold">{{ formatCurrency(invoice.balance) }}</td>
                    <td>
                      <span
                        :class="{
                          'badge': true,
                          'badge-warning': !isOverdue(invoice),
                          'badge-danger': isOverdue(invoice)
                        }"
                      >
                        {{ isOverdue(invoice) ? 'Jatuh Tempo' : 'Belum Lunas' }}
                      </span>
                    </td>
                  </tr>
                </tbody>
                <tfoot>
                  <tr class="font-weight-bold">
                    <td colspan="3" class="text-right">Total</td>
                    <td class="text-right">{{ formatCurrency(sumProperty(openInvoices, 'amount')) }}</td>
                    <td class="text-right">{{ formatCurrency(sumProperty(openInvoices, 'paid_amount')) }}</td>
                    <td class="text-right">{{ formatCurrency(sumProperty(openInvoices, 'balance')) }}</td>
                    <td></td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="statement-footer">
          <p class="footer-text">
            Jika Anda memiliki pertanyaan mengenai statement ini, silakan hubungi bagian keuangan kami di:
            <br>
            Email: finance@company.com | Telepon: (021) 123-4567
          </p>
          <p class="thank-you">Terima kasih atas kerja sama Anda</p>
        </div>
      </div>
    </div>
  </template>

  <script>
  import { ref, reactive, computed, onMounted, watch } from 'vue';
  import { useRoute } from 'vue-router';
  import axios from 'axios';

  export default {
    name: 'CustomerStatement',
    setup() {
      const route = useRoute();
      //const router = useRouter();

      // State
      const isLoading = ref(true);
      const error = ref(null);
      const customers = ref([]);
      const selectedCustomer = ref(null);
      const transactions = ref([]);
      const openInvoices = ref([]);
      const aging = ref({
        current_amount: 0,
        days_1_30: 0,
        days_31_60: 0,
        days_61_90: 0,
        days_over_90: 0
      });

      // Filters
      const filters = reactive({
        customerId: route.params.customerId || '',
        startDate: formatDateForInput(new Date(new Date().setMonth(new Date().getMonth() - 3))), // Default to 3 months ago
        endDate: formatDateForInput(new Date())
      });

      // Computed properties
      const openingBalance = ref(0);
      const totalInvoiced = computed(() => {
        return transactions.value
          .filter(t => t.type === 'invoice')
          .reduce((sum, t) => sum + parseFloat(t.amount || 0), 0);
      });

      const totalPayments = computed(() => {
        return transactions.value
          .filter(t => t.type === 'payment')
          .reduce((sum, t) => sum + parseFloat(t.amount || 0), 0);
      });

      const closingBalance = computed(() => {
        return openingBalance.value + totalInvoiced.value - totalPayments.value;
      });

      // Load customers
      const loadCustomers = async () => {
        try {
          const response = await axios.get('/api/sales/customers');
          customers.value = response.data.data || response.data;

          // If customerId is set in the route and exists in the loaded customers, set selectedCustomer
          if (filters.customerId) {
            const customer = customers.value.find(c => c.customer_id == filters.customerId);
            if (customer) {
              selectedCustomer.value = customer;
            }
          }
        } catch (err) {
          console.error('Error loading customers:', err);
          error.value = 'Gagal memuat data pelanggan. Silakan coba lagi nanti.';
        }
      };

      // Load statement data
      const loadStatementData = async () => {
        if (!filters.customerId) {
          selectedCustomer.value = null;
          transactions.value = [];
          openInvoices.value = [];
          openingBalance.value = 0;
          return;
        }

        isLoading.value = true;
        error.value = null;

        try {
          // Set selected customer
          selectedCustomer.value = customers.value.find(c => c.customer_id == filters.customerId);

          // Load transactions for the statement
          const transactionsResponse = await axios.get(`/api/accounting/customers/${filters.customerId}/transactions`, {
            params: {
              start_date: filters.startDate,
              end_date: filters.endDate
            }
          });

          transactions.value = transactionsResponse.data.data || [];
          openingBalance.value = parseFloat(transactionsResponse.data.opening_balance || 0);

          // Load open invoices
          const openInvoicesResponse = await axios.get(`/api/accounting/customer-receivables`, {
            params: {
              customer_id: filters.customerId,
              status: 'Open'
            }
          });

          openInvoices.value = openInvoicesResponse.data.data || [];

          // Load aging data
          const agingResponse = await axios.get('/api/accounting/customer-receivables/aging', {
            params: {
              as_of_date: filters.endDate,
              customer_id: filters.customerId
            }
          });

          if (agingResponse.data.data && agingResponse.data.data.length > 0) {
            const customerAging = agingResponse.data.data.find(a => a.customer_id == filters.customerId);
            if (customerAging) {
              aging.value = customerAging;
            }
          }
        } catch (err) {
          console.error('Error loading statement data:', err);
          error.value = 'Gagal memuat data statement. Silakan coba lagi nanti.';
        } finally {
          isLoading.value = false;
        }
      };

      // Reset filters
      const resetFilters = () => {
        filters.startDate = formatDateForInput(new Date(new Date().setMonth(new Date().getMonth() - 3)));
        filters.endDate = formatDateForInput(new Date());

        if (route.params.customerId) {
          // If coming from a specific customer, keep that selection
          filters.customerId = route.params.customerId;
        } else {
          filters.customerId = '';
          selectedCustomer.value = null;
        }

        loadStatementData();
      };

      // Calculate running balance for each transaction row
      const calculateRunningBalance = (index) => {
        let balance = openingBalance.value;

        for (let i = 0; i <= index; i++) {
          const transaction = transactions.value[i];
          if (transaction.type === 'invoice') {
            balance += parseFloat(transaction.amount || 0);
          } else if (transaction.type === 'payment') {
            balance -= parseFloat(transaction.amount || 0);
          }
        }

        return balance;
      };

      // Check if an invoice/transaction is overdue
      const isOverdue = (item) => {
        if (!item || !item.due_date) return false;

        const dueDate = new Date(item.due_date);
        const today = new Date();

        // Reset time part for accurate day comparison
        dueDate.setHours(0, 0, 0, 0);
        today.setHours(0, 0, 0, 0);

        return dueDate < today;
      };

      // Sum property values across an array of objects
      const sumProperty = (array, property) => {
        return array.reduce((sum, item) => sum + parseFloat(item[property] || 0), 0);
      };

      // Print statement
      const printStatement = () => {
        window.print();
      };

      // Format date for input fields (YYYY-MM-DD)
      function formatDateForInput(date) {
        const d = new Date(date);
        let month = '' + (d.getMonth() + 1);
        let day = '' + d.getDate();
        const year = d.getFullYear();

        if (month.length < 2) month = '0' + month;
        if (day.length < 2) day = '0' + day;

        return [year, month, day].join('-');
      }

      // Format currency
      const formatCurrency = (amount) => {
        return new Intl.NumberFormat('id-ID', {
          style: 'currency',
          currency: 'IDR',
          minimumFractionDigits: 0,
          maximumFractionDigits: 0
        }).format(amount || 0);
      };

      // Format date
      const formatDate = (dateString) => {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', {
          day: 'numeric',
          month: 'long',
          year: 'numeric'
        });
      };

      // Watch for filter changes
      watch(
        () => filters.customerId,
        (newVal) => {
          if (newVal) {
            loadStatementData();
          }
        }
      );

      // Lifecycle hooks
      onMounted(async () => {
        await loadCustomers();

        if (filters.customerId) {
          await loadStatementData();
        } else {
          isLoading.value = false;
        }
      });

      return {
        isLoading,
        error,
        customers,
        selectedCustomer,
        transactions,
        openInvoices,
        aging,
        filters,
        openingBalance,
        totalInvoiced,
        totalPayments,
        closingBalance,
        loadStatementData,
        resetFilters,
        calculateRunningBalance,
        isOverdue,
        sumProperty,
        printStatement,
        formatCurrency,
        formatDate
      };
    }
  };
  </script>

  <style scoped>
  .statement-header {
    display: flex;
    flex-direction: column;
    margin-bottom: 2rem;
    text-align: center;
  }

  .company-info {
    margin-bottom: 1rem;
  }

  .company-name {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
  }

  .company-address, .company-contact {
    font-size: 0.875rem;
    color: var(--gray-600);
    margin-bottom: 0.25rem;
  }

  .statement-title {
    margin-bottom: 1.5rem;
  }

  .statement-title h1 {
    font-size: 1.75rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    text-transform: uppercase;
  }

  .statement-period {
    font-size: 1rem;
    color: var(--gray-600);
  }

  .customer-info {
    margin-bottom: 1.5rem;
    text-align: left;
    border: 1px solid var(--gray-300);
    border-radius: 0.375rem;
    padding: 1rem;
    background-color: var(--gray-50);
  }

  .customer-name {
    font-size: 1.125rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
  }

  .statement-summary {
    display: flex;
    flex-wrap: wrap;
    margin-bottom: 2rem;
    background-color: var(--gray-100);
    border-radius: 0.375rem;
    padding: 1rem;
  }

  .summary-item {
    flex: 1;
    min-width: 200px;
    padding: 0.75rem;
    text-align: center;
  }

  .summary-label {
    font-size: 0.875rem;
    color: var(--gray-600);
    margin-bottom: 0.5rem;
  }

  .summary-value {
    font-size: 1.25rem;
    font-weight: 600;
  }

  .summary-item.total {
    background-color: var(--gray-200);
    border-radius: 0.25rem;
  }

  .opening-balance-row {
    background-color: var(--gray-50);
  }

  .statement-footer {
    margin-top: 3rem;
    text-align: center;
    padding-top: 1.5rem;
    border-top: 1px solid var(--gray-300);
  }

  .footer-text {
    font-size: 0.875rem;
    color: var(--gray-600);
    margin-bottom: 1rem;
  }

  .thank-you {
    font-weight: 600;
  }

  .overdue {
    background-color: rgba(239, 68, 68, 0.05);
  }

  .badge {
    padding: 0.35em 0.65em;
    font-size: 0.75em;
  }

  @media print {
    .page-header, .card-header, button, .btn, .action-buttons, select, input, .form-group, label {
      display: none !important;
    }

    .card {
      border: none !important;
      box-shadow: none !important;
      margin-bottom: 1.5rem !important;
    }

    .card-body {
      padding: 0 !important;
    }

    .statement-header, .statement-summary, .statement-footer {
      page-break-inside: avoid;
    }

    .data-table th {
      background-color: #f8f9fa !important;
      color: #000 !important;
    }

    .table-responsive {
      overflow: visible !important;
    }

    .overdue {
      background-color: #fff6f6 !important;
      color: #000 !important;
    }
  }

  @media (max-width: 768px) {
    .statement-summary {
      flex-direction: column;
    }

    .summary-item {
      margin-bottom: 0.5rem;
    }
  }
  </style>
