<!-- src/views/inventory/StockTransactions.vue -->
<template>
  <div class="stock-transactions">
    <!-- Search and Filter Section -->
    <SearchFilter
      v-model:value="searchQuery"
      placeholder="Cari transaksi berdasarkan referensi atau item..."
      @search="applyFilters"
      @clear="clearSearch"
    >
      <template #filters>
        <div class="filter-group">
          <label for="dateRangeFilter">Rentang Waktu</label>
          <select id="dateRangeFilter" v-model="dateRangeFilter" @change="applyFilters">
            <option value="all">Semua Waktu</option>
            <option value="today">Hari Ini</option>
            <option value="yesterday">Kemarin</option>
            <option value="week">Minggu Ini</option>
            <option value="month">Bulan Ini</option>
            <option value="custom">Kustom</option>
          </select>
        </div>
        
        <div class="filter-group">
          <label for="transactionTypeFilter">Tipe Transaksi</label>
          <select id="transactionTypeFilter" v-model="transactionTypeFilter" @change="applyFilters">
            <option value="">Semua Tipe</option>
            <option value="IN">Masuk (Penerimaan)</option>
            <option value="OUT">Keluar (Pengeluaran)</option>
            <option value="ADJUSTMENT_IN">Penyesuaian Masuk</option>
            <option value="ADJUSTMENT_OUT">Penyesuaian Keluar</option>
            <option value="RETURN">Pengembalian</option>
            <option value="SALE">Penjualan</option>
          </select>
        </div>
        
        <div class="filter-group">
          <label for="warehouseFilter">Gudang</label>
          <select id="warehouseFilter" v-model="warehouseFilter" @change="applyFilters">
            <option value="">Semua Gudang</option>
            <option v-for="warehouse in warehouses" :key="warehouse.warehouse_id" :value="warehouse.warehouse_id">
              {{ warehouse.name }}
            </option>
          </select>
        </div>
      </template>
      
      <template #actions>
        <button class="btn btn-primary" @click="openNewTransactionModal">
          <i class="fas fa-plus"></i> Transaksi Baru
        </button>
      </template>
    </SearchFilter>
    
    <!-- Custom Date Range (when Custom is selected) -->
    <div v-if="dateRangeFilter === 'custom'" class="custom-date-range">
      <div class="date-range-inputs">
        <div class="filter-group">
          <label for="startDate">Tanggal Mulai</label>
          <input 
            type="date" 
            id="startDate" 
            v-model="customDateRange.startDate" 
            @change="applyFilters"
          />
        </div>
        
        <div class="filter-group">
          <label for="endDate">Tanggal Akhir</label>
          <input 
            type="date" 
            id="endDate" 
            v-model="customDateRange.endDate" 
            @change="applyFilters"
          />
        </div>
      </div>
    </div>
    
    <!-- Data Table -->
    <DataTable
      :columns="columns"
      :items="paginatedTransactions"
      :is-loading="isLoading"
      keyField="transaction_id"
      emptyIcon="fas fa-exchange-alt"
      emptyTitle="Tidak ada transaksi ditemukan"
      emptyMessage="Coba sesuaikan filter atau buat transaksi baru."
      @sort="sortBy"
    >
      <template #item="{ item }">
        <div class="item-info">
          <span class="item-code">{{ item.item.item_code }}</span>
          <span class="item-name">{{ item.item.name }}</span>
        </div>
      </template>
      
      <template #transactionType="{ item }">
        <span class="transaction-type" :class="getTransactionTypeClass(item.transaction_type)">
          {{ item.transaction_type }}
        </span>
      </template>
      
      <template #quantity="{ item }">
        <span class="quantity" :class="getQuantityClass(item.transaction_type)">
          {{ item.quantity }} {{ item.item.unitOfMeasure?.symbol }}
        </span>
      </template>
      
      <template #reference="{ item }">
        <div v-if="item.reference_document" class="reference-info">
          <span class="reference-document">{{ item.reference_document }}</span>
          <span v-if="item.reference_number" class="reference-number">
            #{{ item.reference_number }}
          </span>
        </div>
        <span v-else>-</span>
      </template>
      
      <template #date="{ value }">
        {{ formatDate(value) }}
      </template>
      
      <template #actions="{ item }">
        <button class="action-btn" title="Lihat Detail" @click="viewTransactionDetails(item)">
          <i class="fas fa-eye"></i>
        </button>
      </template>
      
      <template #footer>
        <Pagination
          :current-page="currentPage"
          :total-pages="totalPages"
          :from="paginationInfo.from"
          :to="paginationInfo.to"
          :total="filteredTransactions.length"
          @page-changed="goToPage"
        />
      </template>
    </DataTable>
    
    <!-- New Transaction Modal -->
    <div v-if="showTransactionModal" class="modal">
      <div class="modal-backdrop" @click="closeTransactionModal"></div>
      <div class="modal-content modal-lg">
        <div class="modal-header">
          <h2>Transaksi Stok Baru</h2>
          <button class="close-btn" @click="closeTransactionModal">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="saveTransaction">
            <div class="form-row">
              <div class="form-group">
                <label for="transaction_type">Tipe Transaksi*</label>
                <select 
                  id="transaction_type" 
                  v-model="transactionForm.transaction_type" 
                  required
                  class="form-control"
                >
                  <option value="">-- Pilih Tipe --</option>
                  <option value="IN">Masuk (Penerimaan)</option>
                  <option value="OUT">Keluar (Pengeluaran)</option>
                  <option value="ADJUSTMENT_IN">Penyesuaian Masuk</option>
                  <option value="ADJUSTMENT_OUT">Penyesuaian Keluar</option>
                  <option value="RETURN">Pengembalian</option>
                  <option value="SALE">Penjualan</option>
                </select>
              </div>
              <div class="form-group">
                <label for="transaction_date">Tanggal Transaksi*</label>
                <input 
                  type="date" 
                  id="transaction_date" 
                  v-model="transactionForm.transaction_date" 
                  required
                  class="form-control"
                />
              </div>
            </div>
            
            <div class="form-row">
              <div class="form-group">
                <label for="item_id">Item*</label>
                <select 
                  id="item_id" 
                  v-model="transactionForm.item_id" 
                  required
                  class="form-control"
                >
                  <option value="">-- Pilih Item --</option>
                  <option v-for="item in items" :key="item.item_id" :value="item.item_id">
                    {{ item.item_code }} - {{ item.name }}
                  </option>
                </select>
              </div>
              <div class="form-group">
                <label for="warehouse_id">Gudang*</label>
                <select 
                  id="warehouse_id" 
                  v-model="transactionForm.warehouse_id" 
                  required
                  class="form-control"
                >
                  <option value="">-- Pilih Gudang --</option>
                  <option v-for="warehouse in warehouses" :key="warehouse.warehouse_id" :value="warehouse.warehouse_id">
                    {{ warehouse.name }}
                  </option>
                </select>
              </div>
            </div>
            
            <div class="form-row">
              <div class="form-group">
                <label for="location_id">Lokasi</label>
                <select 
                  id="location_id" 
                  v-model="transactionForm.location_id"
                  :disabled="!transactionForm.warehouse_id || !availableLocations.length"
                  class="form-control"
                >
                  <option value="">-- Pilih Lokasi --</option>
                  <option v-for="location in availableLocations" :key="location.location_id" :value="location.location_id">
                    {{ location.code }}
                  </option>
                </select>
              </div>
              <div class="form-group">
                <label for="quantity">Jumlah*</label>
                <input 
                  type="number" 
                  id="quantity" 
                  v-model="transactionForm.quantity" 
                  min="0.01"
                  step="0.01"
                  required
                  class="form-control"
                />
              </div>
            </div>
            
            <div class="form-row">
              <div class="form-group">
                <label for="reference_document">Dokumen Referensi</label>
                <input 
                  type="text" 
                  id="reference_document" 
                  v-model="transactionForm.reference_document"
                  class="form-control"
                />
              </div>
              <div class="form-group">
                <label for="reference_number">Nomor Referensi</label>
                <input 
                  type="text" 
                  id="reference_number" 
                  v-model="transactionForm.reference_number"
                  class="form-control"
                />
              </div>
            </div>
            
            <div class="form-actions">
              <button type="button" class="btn btn-secondary" @click="closeTransactionModal">
                Batal
              </button>
              <button type="submit" class="btn btn-primary">
                Buat Transaksi
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
    
    <!-- Transaction Details Modal -->
    <div v-if="showDetailsModal" class="modal">
      <div class="modal-backdrop" @click="closeDetailsModal"></div>
      <div class="modal-content">
        <div class="modal-header">
          <h2>Detail Transaksi</h2>
          <button class="close-btn" @click="closeDetailsModal">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <div v-if="selectedTransaction" class="transaction-details">
            <div class="details-section">
              <div class="detail-grid">
                <div class="detail-item">
                  <div class="detail-label">ID Transaksi</div>
                  <div class="detail-value">{{ selectedTransaction.transaction_id }}</div>
                </div>
                <div class="detail-item">
                  <div class="detail-label">Tanggal</div>
                  <div class="detail-value">{{ formatDate(selectedTransaction.transaction_date) }}</div>
                </div>
                <div class="detail-item">
                  <div class="detail-label">Tipe</div>
                  <div class="detail-value">
                    <span class="transaction-type" :class="getTransactionTypeClass(selectedTransaction.transaction_type)">
                      {{ selectedTransaction.transaction_type }}
                    </span>
                  </div>
                </div>
                <div class="detail-item">
                  <div class="detail-label">Jumlah</div>
                  <div class="detail-value">
                    <span class="quantity" :class="getQuantityClass(selectedTransaction.transaction_type)">
                      {{ selectedTransaction.quantity }} {{ selectedTransaction.item.unitOfMeasure?.symbol }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="details-section">
              <h3 class="section-title">Informasi Item</h3>
              <div class="detail-grid">
                <div class="detail-item">
                  <div class="detail-label">Kode Item</div>
                  <div class="detail-value">{{ selectedTransaction.item.item_code }}</div>
                </div>
                <div class="detail-item">
                  <div class="detail-label">Nama Item</div>
                  <div class="detail-value">{{ selectedTransaction.item.name }}</div>
                </div>
                <div class="detail-item">
                  <div class="detail-label">Kategori</div>
                  <div class="detail-value">{{ selectedTransaction.item.category?.name || '-' }}</div>
                </div>
                <div class="detail-item">
                  <div class="detail-label">Satuan</div>
                  <div class="detail-value">{{ selectedTransaction.item.unitOfMeasure?.name || '-' }}</div>
                </div>
              </div>
            </div>
            
            <div class="details-section">
              <h3 class="section-title">Informasi Lokasi</h3>
              <div class="detail-grid">
                <div class="detail-item">
                  <div class="detail-label">Gudang</div>
                  <div class="detail-value">{{ selectedTransaction.warehouse.name }}</div>
                </div>
                <div class="detail-item">
                  <div class="detail-label">Lokasi</div>
                  <div class="detail-value">{{ selectedTransaction.location?.code || '-' }}</div>
                </div>
                <div class="detail-item">
                  <div class="detail-label">Dokumen Referensi</div>
                  <div class="detail-value">{{ selectedTransaction.reference_document || '-' }}</div>
                </div>
                <div class="detail-item">
                  <div class="detail-label">Nomor Referensi</div>
                  <div class="detail-value">{{ selectedTransaction.reference_number || '-' }}</div>
                </div>
              </div>
            </div>
            
            <div class="form-actions">
              <button type="button" class="btn btn-secondary" @click="closeDetailsModal">
                Tutup
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, watch } from 'vue';
import SearchFilter from '@/components/common/SearchFilter.vue';
import DataTable from '@/components/common/DataTable.vue';
import Pagination from '@/components/common/Pagination.vue';

export default {
  name: 'StockTransactions',
  components: {
    SearchFilter,
    DataTable,
    Pagination
  },
  setup() {
    // Data
    const transactions = ref([]);
    const warehouses = ref([]);
    const items = ref([]);
    const isLoading = ref(true);
    
    // Search and filtering
    const searchQuery = ref('');
    const dateRangeFilter = ref('month');
    const transactionTypeFilter = ref('');
    const warehouseFilter = ref('');
    const customDateRange = ref({
      startDate: new Date().toISOString().substr(0, 10),
      endDate: new Date().toISOString().substr(0, 10)
    });
    
    // Table columns
    const columns = ref([
      { key: 'transaction_id', label: 'ID', sortable: true },
      { key: 'transaction_date', label: 'Tanggal', sortable: true, template: 'date' },
      { key: 'item', label: 'Item', template: 'item' },
      { key: 'transaction_type', label: 'Tipe', sortable: true, template: 'transactionType' },
      { key: 'quantity', label: 'Jumlah', sortable: true, template: 'quantity' },
      { key: 'warehouse.name', label: 'Gudang', sortable: true },
      { key: 'reference_document', label: 'Referensi', template: 'reference' }
    ]);
    
    // Sorting
    const sortColumn = ref('transaction_date');
    const sortDirection = ref('desc');
    
    // Pagination
    const currentPage = ref(1);
    const itemsPerPage = ref(10);
    
    // Modals
    const showTransactionModal = ref(false);
    const showDetailsModal = ref(false);
    const selectedTransaction = ref(null);
    const transactionForm = ref({
      transaction_type: '',
      transaction_date: new Date().toISOString().substr(0, 10),
      item_id: '',
      warehouse_id: '',
      location_id: '',
      quantity: '',
      reference_document: '',
      reference_number: ''
    });
    
    // Computed properties
    const filteredTransactions = computed(() => {
      let result = [...transactions.value];
      
      // Apply search filter
      if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(transaction => {
          return (
            (transaction.item.name && transaction.item.name.toLowerCase().includes(query)) ||
            (transaction.item.item_code && transaction.item.item_code.toLowerCase().includes(query)) ||
            (transaction.reference_document && transaction.reference_document.toLowerCase().includes(query)) ||
            (transaction.reference_number && transaction.reference_number.toLowerCase().includes(query))
          );
        });
      }
      
      // Apply date range filter
      if (dateRangeFilter.value !== 'all') {
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        const yesterday = new Date(today);
        yesterday.setDate(yesterday.getDate() - 1);
        
        const firstDayOfWeek = new Date(today);
        firstDayOfWeek.setDate(firstDayOfWeek.getDate() - firstDayOfWeek.getDay());
        
        const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
        
        switch (dateRangeFilter.value) {
          case 'today':
            result = result.filter(transaction => {
              const transactionDate = new Date(transaction.transaction_date);
              return transactionDate.setHours(0, 0, 0, 0) === today.getTime();
            });
            break;
          case 'yesterday':
            result = result.filter(transaction => {
              const transactionDate = new Date(transaction.transaction_date);
              return transactionDate.setHours(0, 0, 0, 0) === yesterday.getTime();
            });
            break;
          case 'week':
            result = result.filter(transaction => {
              const transactionDate = new Date(transaction.transaction_date);
              return transactionDate >= firstDayOfWeek && transactionDate <= today;
            });
            break;
          case 'month':
            result = result.filter(transaction => {
              const transactionDate = new Date(transaction.transaction_date);
              return transactionDate >= firstDayOfMonth && transactionDate <= today;
            });
            break;
          case 'custom': {
            const startDate = new Date(customDateRange.value.startDate);
            const endDate = new Date(customDateRange.value.endDate);
            endDate.setHours(23, 59, 59, 999);
            
            result = result.filter(transaction => {
              const transactionDate = new Date(transaction.transaction_date);
              return transactionDate >= startDate && transactionDate <= endDate;
            });
            break;
          }
        }
      }
      
      // Apply transaction type filter
      if (transactionTypeFilter.value) {
        result = result.filter(transaction => {
          return transaction.transaction_type === transactionTypeFilter.value;
        });
      }
      
      // Apply warehouse filter
      if (warehouseFilter.value) {
        result = result.filter(transaction => {
          return transaction.warehouse_id === parseInt(warehouseFilter.value);
        });
      }
      
      // Apply sorting
      result.sort((a, b) => {
        let comparison = 0;
        
        if (sortColumn.value === 'transaction_date') {
          const dateA = new Date(a[sortColumn.value]);
          const dateB = new Date(b[sortColumn.value]);
          comparison = dateA - dateB;
        } else {
          if (a[sortColumn.value] < b[sortColumn.value]) {
            comparison = -1;
          } else if (a[sortColumn.value] > b[sortColumn.value]) {
            comparison = 1;
          }
        }
        
        return sortDirection.value === 'asc' ? comparison : -comparison;
      });
      
      return result;
    });
    
    // Pagination logic
    const totalPages = computed(() => {
      return Math.ceil(filteredTransactions.value.length / itemsPerPage.value);
    });
    
    const paginatedTransactions = computed(() => {
      const startIndex = (currentPage.value - 1) * itemsPerPage.value;
      const endIndex = startIndex + itemsPerPage.value;
      return filteredTransactions.value.slice(startIndex, endIndex);
    });
    
    const paginationInfo = computed(() => {
      const total = filteredTransactions.value.length;
      const from = total === 0 ? 0 : (currentPage.value - 1) * itemsPerPage.value + 1;
      const to = Math.min(currentPage.value * itemsPerPage.value, total);
      
      return { from, to, total };
    });
    
    const availableLocations = computed(() => {
      if (!transactionForm.value.warehouse_id) return [];
      
      const selectedWarehouse = warehouses.value.find(
        w => w.warehouse_id === parseInt(transactionForm.value.warehouse_id)
      );
      
      if (!selectedWarehouse || !selectedWarehouse.zones) return [];
      
      return selectedWarehouse.zones.reduce((locations, zone) => {
        if (zone.locations) {
          return [...locations, ...zone.locations];
        }
        return locations;
      }, []);
    });
    
    // Methods
    const fetchTransactions = async () => {
      isLoading.value = true;
      try {
        setTimeout(() => {
          transactions.value = [
            {
              transaction_id: 1,
              item_id: 1,
              item: { 
                item_id: 1,
                item_code: 'ITEM-001',
                name: 'Laptop Model X',
                category: { name: 'Electronics' },
                unitOfMeasure: { name: 'Each', symbol: 'EA' }
              },
              warehouse_id: 1,
              warehouse: { name: 'Main Warehouse' },
              location_id: 1,
              location: { code: 'A-01-01' },
              transaction_type: 'IN',
              quantity: 25,
              transaction_date: '2025-03-22',
              reference_document: 'Purchase Order',
              reference_number: 'PO-2025-001'
            },
            {
              transaction_id: 2,
              item_id: 2,
              item: { 
                item_id: 2,
                item_code: 'ITEM-002',
                name: 'Smartphone Y Pro',
                category: { name: 'Electronics' },
                unitOfMeasure: { name: 'Each', symbol: 'EA' }
              },
              warehouse_id: 1,
              warehouse: { name: 'Main Warehouse' },
              location_id: 2,
              location: { code: 'A-01-02' },
              transaction_type: 'OUT',
              quantity: 10,
              transaction_date: '2025-03-22',
              reference_document: 'Sales Order',
              reference_number: 'SO-2025-005'
            }
          ];
          isLoading.value = false;
        }, 500);
      } catch (error) {
        console.error('Error fetching transactions:', error);
        isLoading.value = false;
      }
    };
    
    const fetchWarehouses = async () => {
      try {
        warehouses.value = [
          {
            warehouse_id: 1,
            name: 'Main Warehouse',
            zones: [
              { 
                zone_id: 1, 
                name: 'Receiving Zone', 
                locations: [
                  { location_id: 1, code: 'A-01-01' },
                  { location_id: 2, code: 'A-01-02' }
                ] 
              }
            ]
          }
        ];
      } catch (error) {
        console.error('Error fetching warehouses:', error);
      }
    };
    
    const fetchItems = async () => {
      try {
        items.value = [
          {
            item_id: 1,
            item_code: 'ITEM-001',
            name: 'Laptop Model X',
            unitOfMeasure: { symbol: 'EA' }
          },
          {
            item_id: 2,
            item_code: 'ITEM-002',
            name: 'Smartphone Y Pro',
            unitOfMeasure: { symbol: 'EA' }
          }
        ];
      } catch (error) {
        console.error('Error fetching items:', error);
      }
    };
    
    const formatDate = (dateString) => {
      const date = new Date(dateString);
      return date.toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      });
    };
    
    const getTransactionTypeClass = (type) => {
      if (['IN', 'RECEIPT', 'RETURN', 'ADJUSTMENT_IN'].includes(type)) {
        return 'type-in';
      } else if (['OUT', 'ISSUE', 'SALE', 'ADJUSTMENT_OUT'].includes(type)) {
        return 'type-out';
      }
      return '';
    };
    
    const getQuantityClass = (type) => {
      if (['IN', 'RECEIPT', 'RETURN', 'ADJUSTMENT_IN'].includes(type)) {
        return 'quantity-in';
      } else if (['OUT', 'ISSUE', 'SALE', 'ADJUSTMENT_OUT'].includes(type)) {
        return 'quantity-out';
      }
      return '';
    };
    
    const applyFilters = () => {
      currentPage.value = 1;
    };
    
    const clearSearch = () => {
      searchQuery.value = '';
      applyFilters();
    };
    
    const sortBy = ({ key, order }) => {
      sortColumn.value = key;
      sortDirection.value = order;
    };
    
    const goToPage = (page) => {
      currentPage.value = page;
    };
    
    const openNewTransactionModal = () => {
      transactionForm.value = {
        transaction_type: '',
        transaction_date: new Date().toISOString().substr(0, 10),
        item_id: '',
        warehouse_id: '',
        location_id: '',
        quantity: '',
        reference_document: '',
        reference_number: ''
      };
      showTransactionModal.value = true;
    };
    
    const closeTransactionModal = () => {
      showTransactionModal.value = false;
    };
    
    const viewTransactionDetails = (transaction) => {
      selectedTransaction.value = transaction;
      showDetailsModal.value = true;
    };
    
    const closeDetailsModal = () => {
      showDetailsModal.value = false;
      selectedTransaction.value = null;
    };
    
    const saveTransaction = async () => {
      try {
        const selectedItem = items.value.find(item => item.item_id === parseInt(transactionForm.value.item_id));
        const selectedWarehouse = warehouses.value.find(wh => wh.warehouse_id === parseInt(transactionForm.value.warehouse_id));
        
        let selectedLocation = null;
        if (transactionForm.value.location_id) {
          for (const warehouse of warehouses.value) {
            if (warehouse.zones) {
              for (const zone of warehouse.zones) {
                if (zone.locations) {
                  const location = zone.locations.find(
                    loc => loc.location_id === parseInt(transactionForm.value.location_id)
                  );
                  if (location) {
                    selectedLocation = location;
                    break;
                  }
                }
              }
              if (selectedLocation) break;
            }
          }
        }
        
        const newTransaction = {
          transaction_id: transactions.value.length + 1,
          item_id: parseInt(transactionForm.value.item_id),
          item: selectedItem,
          warehouse_id: parseInt(transactionForm.value.warehouse_id),
          warehouse: selectedWarehouse,
          location_id: transactionForm.value.location_id ? parseInt(transactionForm.value.location_id) : null,
          location: selectedLocation,
          transaction_type: transactionForm.value.transaction_type,
          quantity: parseFloat(transactionForm.value.quantity),
          transaction_date: transactionForm.value.transaction_date,
          reference_document: transactionForm.value.reference_document,
          reference_number: transactionForm.value.reference_number
        };
        
        transactions.value.unshift(newTransaction);
        closeTransactionModal();
        alert('Transaksi berhasil dibuat!');
      } catch (error) {
        console.error('Error saving transaction:', error);
        alert('Terjadi kesalahan saat menyimpan transaksi.');
      }
    };
    
    watch(filteredTransactions, (newTransactions, oldTransactions) => {
      if (Math.abs(newTransactions.length - oldTransactions.length) > itemsPerPage.value) {
        currentPage.value = 1;
      }
    });
    
    onMounted(() => {
      fetchTransactions();
      fetchWarehouses();
      fetchItems();
    });
    
    return {
      transactions,
      columns,
      warehouses,
      items,
      isLoading,
      searchQuery,
      dateRangeFilter,
      transactionTypeFilter,
      warehouseFilter,
      customDateRange,
      sortColumn,
      sortDirection,
      currentPage,
      itemsPerPage,
      filteredTransactions,
      paginatedTransactions,
      totalPages,
      paginationInfo,
      showTransactionModal,
      showDetailsModal,
      selectedTransaction,
      transactionForm,
      availableLocations,
      formatDate,
      getTransactionTypeClass,
      getQuantityClass,
      applyFilters,
      clearSearch,
      sortBy,
      goToPage,
      openNewTransactionModal,
      closeTransactionModal,
      viewTransactionDetails,
      closeDetailsModal,
      saveTransaction
    };
  }
};
</script>

<style scoped>
.stock-transactions {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.custom-date-range {
  background-color: var(--gray-50);
  border: 1px solid var(--gray-200);
  border-radius: 0.375rem;
  padding: 1rem;
  margin-bottom: 1rem;
}

.date-range-inputs {
  display: flex;
  gap: 1rem;
}

.transaction-type {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
  font-size: 0.75rem;
  font-weight: 500;
}

.type-in {
  background-color: var(--success-bg);
  color: var(--success-color);
}

.type-out {
  background-color: var(--danger-bg);
  color: var(--danger-color);
}

.quantity {
  font-weight: 500;
}

.quantity-in {
  color: var(--success-color);
}

.quantity-out {
  color: var(--danger-color);
}

.item-info {
  display: flex;
  flex-direction: column;
}

.item-code {
  font-size: 0.75rem;
  color: var(--gray-500);
}

.item-name {
  font-weight: 500;
}

.reference-info {
  display: flex;
  flex-direction: column;
}

.reference-document {
  font-weight: 500;
}

.reference-number {
  font-size: 0.75rem;
  color: var(--gray-500);
}

.transaction-details {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.details-section {
  border: 1px solid var(--gray-200);
  border-radius: 0.375rem;
  overflow: hidden;
}

.section-title {
  font-size: 1rem;
  font-weight: 600;
  margin: 0;
  padding: 0.75rem 1rem;
  background-color: var(--gray-50);
  border-bottom: 1px solid var(--gray-200);
  color: var(--gray-800);
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
  padding: 1rem;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.detail-label {
  font-size: 0.75rem;
  color: var(--gray-500);
  font-weight: 500;
}

.detail-value {
  color: var(--gray-800);
  font-size: 0.875rem;
}

@media (max-width: 768px) {
  .date-range-inputs {
    flex-direction: column;
  }
  
  .detail-grid {
    grid-template-columns: 1fr;
  }
}
</style>