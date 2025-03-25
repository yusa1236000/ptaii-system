<!-- src/views/inventory/StockAdjustments.vue -->
<template>
  <div class="stock-adjustments">
    <!-- Search and Filter Section -->
    <SearchFilter
      v-model:value="searchQuery"
      placeholder="Cari penyesuaian stok..."
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
          </select>
        </div>

        <div class="filter-group">
          <label for="statusFilter">Status</label>
          <select id="statusFilter" v-model="statusFilter" @change="applyFilters">
            <option value="">Semua Status</option>
            <option value="Pending">Pending</option>
            <option value="Approved">Disetujui</option>
            <option value="Cancelled">Dibatalkan</option>
          </select>
        </div>
      </template>

      <template #actions>
        <button class="btn btn-primary" @click="openNewAdjustmentModal">
          <i class="fas fa-plus"></i> Penyesuaian Baru
        </button>
      </template>
    </SearchFilter>

    <!-- Adjustments Grid -->
    <div class="adjustments-container">
      <div v-if="isLoading" class="loading-indicator">
        <i class="fas fa-spinner fa-spin"></i> Memuat penyesuaian stok...
      </div>

      <div v-else-if="filteredAdjustments.length === 0" class="empty-state">
        <div class="empty-icon">
          <i class="fas fa-sliders-h"></i>
        </div>
        <h3>Tidak ada penyesuaian stok ditemukan</h3>
        <p>Coba sesuaikan pencarian atau filter, atau buat penyesuaian stok baru.</p>
      </div>

      <div v-else class="adjustments-grid">
        <div
          v-for="adjustment in filteredAdjustments"
          :key="adjustment.adjustment_id"
          class="adjustment-card"
          @click="viewAdjustmentDetails(adjustment)"
        >
          <div class="adjustment-header">
            <span
              class="adjustment-status"
              :class="{
                'pending': adjustment.status === 'Pending',
                'approved': adjustment.status === 'Approved',
                'cancelled': adjustment.status === 'Cancelled'
              }"
            >
              {{ getStatusLabel(adjustment.status) }}
            </span>
            <span class="adjustment-date">{{ formatDate(adjustment.adjustment_date) }}</span>
          </div>

          <div class="adjustment-body">
            <div class="adjustment-id">ID: {{ adjustment.adjustment_id }}</div>
            <div v-if="adjustment.adjustment_reason" class="adjustment-reason">
              <strong>Alasan:</strong> {{ adjustment.adjustment_reason }}
            </div>

            <div class="adjustment-summary">
              <div class="summary-item">
                <div class="summary-label">Item</div>
                <div class="summary-value">{{ adjustment.lines.length }}</div>
              </div>
              <div class="summary-item">
                <div class="summary-label">Total Selisih</div>
                <div class="summary-value" :class="getTotalVarianceClass(adjustment)">
                  {{ getTotalVariance(adjustment) }}
                </div>
              </div>
            </div>
          </div>

          <div class="adjustment-footer">
            <div v-if="adjustment.reference_document" class="reference-document">
              Ref: {{ adjustment.reference_document }}
            </div>

            <div class="adjustment-actions">
              <button
                v-if="adjustment.status === 'Pending'"
                class="action-btn approve-btn"
                title="Setujui Penyesuaian"
                @click.stop="approveAdjustment(adjustment)"
              >
                <i class="fas fa-check"></i> Setujui
              </button>
              <button
                v-if="adjustment.status === 'Pending'"
                class="action-btn cancel-btn"
                title="Batalkan Penyesuaian"
                @click.stop="cancelAdjustment(adjustment)"
              >
                <i class="fas fa-times"></i> Batalkan
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- New Adjustment Modal -->
    <div v-if="showAdjustmentModal" class="modal">
      <div class="modal-backdrop" @click="closeAdjustmentModal"></div>
      <div class="modal-content modal-lg">
        <div class="modal-header">
          <h2>Penyesuaian Stok Baru</h2>
          <button class="close-btn" @click="closeAdjustmentModal">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="saveAdjustment">
            <div class="form-row">
              <div class="form-group">
                <label for="adjustment_date">Tanggal Penyesuaian*</label>
                <input
                  type="date"
                  id="adjustment_date"
                  v-model="adjustmentForm.adjustment_date"
                  required
                  class="form-control"
                />
              </div>
              <div class="form-group">
                <label for="reference_document">Dokumen Referensi</label>
                <input
                  type="text"
                  id="reference_document"
                  v-model="adjustmentForm.reference_document"
                  class="form-control"
                />
              </div>
            </div>

            <div class="form-group">
              <label for="adjustment_reason">Alasan Penyesuaian</label>
              <textarea
                id="adjustment_reason"
                v-model="adjustmentForm.adjustment_reason"
                rows="3"
                class="form-control"
              ></textarea>
            </div>

            <div class="adjustment-lines-header">
              <h3>Detail Item</h3>
              <button type="button" class="btn btn-secondary" @click="addAdjustmentLine">
                <i class="fas fa-plus"></i> Tambah Item
              </button>
            </div>

            <div class="adjustment-lines">
              <div v-if="adjustmentForm.lines.length === 0" class="empty-lines">
                <p>Belum ada item yang ditambahkan. Klik "Tambah Item" untuk menambahkan item.</p>
              </div>

              <div v-else class="adjustment-lines-table">
                <table>
                  <thead>
                    <tr>
                      <th>Item</th>
                      <th>Gudang</th>
                      <th>Lokasi</th>
                      <th>Stok Tercatat</th>
                      <th>Stok Aktual</th>
                      <th>Selisih</th>
                      <th>Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(line, index) in adjustmentForm.lines" :key="index">
                      <td>
                        <select
                          v-model="line.item_id"
                          required
                          @change="updateBookQuantity(index)"
                          class="form-control"
                        >
                          <option value="">-- Pilih Item --</option>
                          <option v-for="item in items" :key="item.item_id" :value="item.item_id">
                            {{ item.item_code }} - {{ item.name }}
                          </option>
                        </select>
                      </td>
                      <td>
                        <select
                          v-model="line.warehouse_id"
                          required
                          @change="updateBookQuantity(index)"
                          class="form-control"
                        >
                          <option value="">-- Pilih --</option>
                          <option
                            v-for="warehouse in warehouses"
                            :key="warehouse.warehouse_id"
                            :value="warehouse.warehouse_id"
                          >
                            {{ warehouse.name }}
                          </option>
                        </select>
                      </td>
                      <td>
                        <select
                          v-model="line.location_id"
                          :disabled="!line.warehouse_id || !getAvailableLocations(line.warehouse_id).length"
                          @change="updateBookQuantity(index)"
                          class="form-control"
                        >
                          <option value="">-- Pilih --</option>
                          <option
                            v-for="location in getAvailableLocations(line.warehouse_id)"
                            :key="location.location_id"
                            :value="location.location_id"
                          >
                            {{ location.code }}
                          </option>
                        </select>
                      </td>
                      <td>
                        <input
                          type="number"
                          v-model="line.book_quantity"
                          readonly
                          class="form-control quantity-input"
                        />
                      </td>
                      <td>
                        <input
                          type="number"
                          v-model="line.adjusted_quantity"
                          required
                          step="0.01"
                          class="form-control quantity-input"
                          @input="calculateVariance(index)"
                        />
                      </td>
                      <td>
                        <div class="variance" :class="getVarianceClass(line)">
                          {{ line.variance }}
                        </div>
                      </td>
                      <td>
                        <button
                          type="button"
                          class="action-btn delete-btn"
                          @click="removeAdjustmentLine(index)"
                        >
                          <i class="fas fa-trash"></i>
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <div class="form-actions">
              <button type="button" class="btn btn-secondary" @click="closeAdjustmentModal">
                Batal
              </button>
              <button
                type="submit"
                class="btn btn-primary"
                :disabled="adjustmentForm.lines.length === 0"
              >
                Buat Penyesuaian
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Adjustment Details Modal -->
    <div v-if="showDetailsModal" class="modal">
      <div class="modal-backdrop" @click="closeDetailsModal"></div>
      <div class="modal-content modal-lg">
        <div class="modal-header">
          <h2>Detail Penyesuaian</h2>
          <button class="close-btn" @click="closeDetailsModal">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <div v-if="selectedAdjustment" class="adjustment-details">
            <div class="details-header">
              <div class="detail-row">
                <div class="detail-item">
                  <div class="detail-label">ID Penyesuaian</div>
                  <div class="detail-value">{{ selectedAdjustment.adjustment_id }}</div>
                </div>
                <div class="detail-item">
                  <div class="detail-label">Tanggal</div>
                  <div class="detail-value">{{ formatDate(selectedAdjustment.adjustment_date) }}</div>
                </div>
                <div class="detail-item">
                  <div class="detail-label">Status</div>
                  <div class="detail-value">
                    <span
                      class="adjustment-status"
                      :class="{
                        'pending': selectedAdjustment.status === 'Pending',
                        'approved': selectedAdjustment.status === 'Approved',
                        'cancelled': selectedAdjustment.status === 'Cancelled'
                      }"
                    >
                      {{ getStatusLabel(selectedAdjustment.status) }}
                    </span>
                  </div>
                </div>
                <div class="detail-item">
                  <div class="detail-label">Referensi</div>
                  <div class="detail-value">{{ selectedAdjustment.reference_document || '-' }}</div>
                </div>
              </div>

              <div v-if="selectedAdjustment.adjustment_reason" class="detail-row">
                <div class="detail-item full-width">
                  <div class="detail-label">Alasan</div>
                  <div class="detail-value">{{ selectedAdjustment.adjustment_reason }}</div>
                </div>
              </div>
            </div>

            <div class="details-section">
              <h3 class="section-title">Detail Item</h3>
              <div class="adjustment-lines-table">
                <table>
                  <thead>
                    <tr>
                      <th>Item</th>
                      <th>Gudang</th>
                      <th>Lokasi</th>
                      <th>Stok Tercatat</th>
                      <th>Stok Aktual</th>
                      <th>Selisih</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="line in selectedAdjustment.lines" :key="line.line_id">
                      <td>
                        <div class="item-info">
                          <span class="item-code">{{ line.item.item_code }}</span>
                          <span class="item-name">{{ line.item.name }}</span>
                        </div>
                      </td>
                      <td>{{ line.warehouse.name }}</td>
                      <td>{{ line.location ? line.location.code : '-' }}</td>
                      <td>{{ line.book_quantity }}</td>
                      <td>{{ line.adjusted_quantity }}</td>
                      <td>
                        <div class="variance" :class="getVarianceClass(line)">
                          {{ line.variance }}
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <div class="details-footer">
              <div class="detail-item">
                <div class="detail-label">Total Selisih</div>
                <div class="detail-value" :class="getTotalVarianceClass(selectedAdjustment)">
                  {{ getTotalVariance(selectedAdjustment) }}
                </div>
              </div>

              <div class="details-actions">
                <button
                  v-if="selectedAdjustment.status === 'Pending'"
                  class="btn btn-success"
                  @click="approveAdjustment(selectedAdjustment)"
                >
                  <i class="fas fa-check"></i> Setujui
                </button>
                <button
                  v-if="selectedAdjustment.status === 'Pending'"
                  class="btn btn-danger"
                  @click="cancelAdjustment(selectedAdjustment)"
                >
                  <i class="fas fa-times"></i> Batalkan
                </button>
                <button class="btn btn-secondary" @click="closeDetailsModal">
                  Tutup
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Confirmation Modal -->
    <div v-if="showConfirmationModal" class="modal">
      <div class="modal-backdrop" @click="closeConfirmationModal"></div>
      <div class="modal-content modal-sm">
        <div class="modal-header">
          <h2>{{ confirmationTitle }}</h2>
          <button class="close-btn" @click="closeConfirmationModal">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <p>{{ confirmationMessage }}</p>

          <div class="form-actions">
            <button type="button" class="btn btn-secondary" @click="closeConfirmationModal">
              Batal
            </button>
            <button
              type="button"
              :class="confirmationAction === 'approve' ? 'btn btn-success' : 'btn btn-danger'"
              @click="confirmAction"
            >
              {{ confirmationAction === 'approve' ? 'Setujui' : 'Batalkan' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import SearchFilter from '@/components/common/SearchFilter.vue';

export default {
  name: 'StockAdjustments',
  components: {
    SearchFilter
  },
  setup() {
    // Data
    const adjustments = ref([]);
    const warehouses = ref([]);
    const items = ref([]);
    const isLoading = ref(true);

    // Search and filtering
    const searchQuery = ref('');
    const dateRangeFilter = ref('month');
    const statusFilter = ref('');

    // Modals
    const showAdjustmentModal = ref(false);
    const showDetailsModal = ref(false);
    const showConfirmationModal = ref(false);
    const selectedAdjustment = ref(null);
    const confirmationTitle = ref('');
    const confirmationMessage = ref('');
    const confirmationAction = ref('');
    const adjustmentToAction = ref(null);

    // Form
    const adjustmentForm = ref({
      adjustment_date: new Date().toISOString().substr(0, 10),
      adjustment_reason: '',
      reference_document: '',
      status: 'Pending',
      lines: []
    });

    // Computed properties
    const filteredAdjustments = computed(() => {
      let result = [...adjustments.value];

      // Apply search filter
      if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(adjustment => {
          return (
            adjustment.adjustment_id.toString().includes(query) ||
            (adjustment.reference_document && adjustment.reference_document.toLowerCase().includes(query)) ||
            (adjustment.adjustment_reason && adjustment.adjustment_reason.toLowerCase().includes(query))
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
            result = result.filter(adjustment => {
              const adjustmentDate = new Date(adjustment.adjustment_date);
              return adjustmentDate.setHours(0, 0, 0, 0) === today.getTime();
            });
            break;
          case 'yesterday':
            result = result.filter(adjustment => {
              const adjustmentDate = new Date(adjustment.adjustment_date);
              return adjustmentDate.setHours(0, 0, 0, 0) === yesterday.getTime();
            });
            break;
          case 'week':
            result = result.filter(adjustment => {
              const adjustmentDate = new Date(adjustment.adjustment_date);
              return adjustmentDate >= firstDayOfWeek && adjustmentDate <= today;
            });
            break;
          case 'month':
            result = result.filter(adjustment => {
              const adjustmentDate = new Date(adjustment.adjustment_date);
              return adjustmentDate >= firstDayOfMonth && adjustmentDate <= today;
            });
            break;
        }
      }

      // Apply status filter
      if (statusFilter.value) {
        result = result.filter(adjustment => {
          return adjustment.status === statusFilter.value;
        });
      }

      // Sort by adjustment_id (newest first)
      result.sort((a, b) => b.adjustment_id - a.adjustment_id);

      return result;
    });

    // Methods
    const fetchAdjustments = async () => {
      isLoading.value = true;

      try {
        // For demo purposes, use dummy data
        setTimeout(() => {
          adjustments.value = [
            {
              adjustment_id: 1,
              adjustment_date: '2025-03-22',
              adjustment_reason: 'Physical count discrepancy',
              status: 'Approved',
              reference_document: 'Count-2025-001',
              lines: [
                {
                  line_id: 1,
                  item: { item_id: 1, item_code: 'ITEM-001', name: 'Laptop Model X' },
                  warehouse: { name: 'Main Warehouse' },
                  location: { code: 'A-01-01' },
                  book_quantity: 20,
                  adjusted_quantity: 25,
                  variance: 5
                },
                {
                  line_id: 2,
                  item: { item_id: 3, item_code: 'ITEM-003', name: 'USB Cable Type-C' },
                  warehouse: { name: 'Main Warehouse' },
                  location: { code: 'A-02-01' },
                  book_quantity: 150,
                  adjusted_quantity: 145,
                  variance: -5
                }
              ]
            },
            {
              adjustment_id: 2,
              adjustment_date: '2025-03-21',
              adjustment_reason: 'Damaged items',
              status: 'Approved',
              reference_document: 'DMG-2025-002',
              lines: [
                {
                  line_id: 3,
                  item: { item_id: 2, item_code: 'ITEM-002', name: 'Smartphone Y Pro' },
                  warehouse: { name: 'Retail Store' },
                  location: { code: 'S-01-01' },
                  book_quantity: 45,
                  adjusted_quantity: 43,
                  variance: -2
                }
              ]
            },
            {
              adjustment_id: 3,
              adjustment_date: '2025-03-20',
              adjustment_reason: 'Initial stock setup',
              status: 'Pending',
              reference_document: 'INIT-2025-001',
              lines: [
                {
                  line_id: 4,
                  item: { item_id: 6, item_code: 'ITEM-006', name: 'Mechanical Keyboard' },
                  warehouse: { name: 'Office Supplies' },
                  location: { code: 'O-01-01' },
                  book_quantity: 0,
                  adjusted_quantity: 10,
                  variance: 10
                },
                {
                  line_id: 5,
                  item: { item_id: 8, item_code: 'ITEM-008', name: 'A4 Paper Ream' },
                  warehouse: { name: 'Office Supplies' },
                  location: { code: 'O-01-02' },
                  book_quantity: 0,
                  adjusted_quantity: 20,
                  variance: 20
                }
              ]
            },
            {
              adjustment_id: 4,
              adjustment_date: '2025-03-19',
              adjustment_reason: 'System correction',
              status: 'Cancelled',
              reference_document: 'COR-2025-001',
              lines: [
                {
                  line_id: 6,
                  item: { item_id: 4, item_code: 'ITEM-004', name: 'Office Chair' },
                  warehouse: { name: 'Office Supplies' },
                  location: { code: 'O-01-01' },
                  book_quantity: 10,
                  adjusted_quantity: 8,
                  variance: -2
                }
              ]
            }
          ];
          isLoading.value = false;
        }, 500);
      } catch (error) {
        console.error('Error fetching adjustments:', error);
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
              },
              {
                zone_id: 2,
                name: 'Storage Zone',
                locations: [
                  { location_id: 3, code: 'A-02-01' },
                  { location_id: 4, code: 'A-02-02' },
                  { location_id: 5, code: 'A-02-03' }
                ]
              }
            ]
          },
          {
            warehouse_id: 2,
            name: 'Retail Store',
            zones: [
              {
                zone_id: 3,
                name: 'Sales Floor',
                locations: [
                  { location_id: 6, code: 'S-01-01' },
                  { location_id: 7, code: 'S-01-02' }
                ]
              },
              {
                zone_id: 4,
                name: 'Back Store',
                locations: [{ location_id: 8, code: 'S-02-01' }]
              }
            ]
          },
          {
            warehouse_id: 3,
            name: 'Office Supplies',
            zones: [
              {
                zone_id: 5,
                name: 'Office Zone',
                locations: [
                  { location_id: 9, code: 'O-01-01' },
                  { location_id: 10, code: 'O-01-02' }
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
          { item_id: 1, item_code: 'ITEM-001', name: 'Laptop Model X', current_stock: 25 },
          { item_id: 2, item_code: 'ITEM-002', name: 'Smartphone Y Pro', current_stock: 45 },
          { item_id: 3, item_code: 'ITEM-003', name: 'USB Cable Type-C', current_stock: 150 },
          { item_id: 4, item_code: 'ITEM-004', name: 'Office Chair', current_stock: 8 },
          { item_id: 5, item_code: 'ITEM-005', name: 'Wireless Mouse', current_stock: 25 },
          { item_id: 6, item_code: 'ITEM-006', name: 'Mechanical Keyboard', current_stock: 10 },
          { item_id: 7, item_code: 'ITEM-007', name: 'HDMI Cable 2m', current_stock: 12 },
          { item_id: 8, item_code: 'ITEM-008', name: 'A4 Paper Ream', current_stock: 20 }
        ];
      } catch (error) {
        console.error('Error fetching items:', error);
      }
    };

    const formatDate = (dateString) => {
      if (!dateString) return '-';
      const date = new Date(dateString);
      return date.toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      });
    };

    const getStatusLabel = (status) => {
      switch (status) {
        case 'Pending':
          return 'Menunggu';
        case 'Approved':
          return 'Disetujui';
        case 'Cancelled':
          return 'Dibatalkan';
        default:
          return status;
      }
    };

    const getTotalVariance = (adjustment) => {
      const total = adjustment.lines.reduce((sum, line) => sum + line.variance, 0);
      return total > 0 ? `+${total}` : total;
    };

    const getTotalVarianceClass = (adjustment) => {
      const total = adjustment.lines.reduce((sum, line) => sum + line.variance, 0);
      if (total > 0) return 'positive-variance';
      if (total < 0) return 'negative-variance';
      return '';
    };

    const getVarianceClass = (line) => {
      if (line.variance > 0) return 'positive-variance';
      if (line.variance < 0) return 'negative-variance';
      return '';
    };

    const getAvailableLocations = (warehouseId) => {
      if (!warehouseId) return [];
      const selectedWarehouse = warehouses.value.find(w => w.warehouse_id === parseInt(warehouseId));
      if (!selectedWarehouse || !selectedWarehouse.zones) return [];
      return selectedWarehouse.zones.reduce((locations, zone) => {
        if (zone.locations) {
          return [...locations, ...zone.locations];
        }
        return locations;
      }, []);
    };

    const applyFilters = () => {
      // Placeholder for future filtering logic
    };

    const clearSearch = () => {
      searchQuery.value = '';
    };

    const openNewAdjustmentModal = () => {
      adjustmentForm.value = {
        adjustment_date: new Date().toISOString().substr(0, 10),
        adjustment_reason: '',
        reference_document: '',
        status: 'Pending',
        lines: []
      };
      showAdjustmentModal.value = true;
    };

    const closeAdjustmentModal = () => {
      showAdjustmentModal.value = false;
    };

    const addAdjustmentLine = () => {
      adjustmentForm.value.lines.push({
        item_id: '',
        warehouse_id: '',
        location_id: '',
        book_quantity: 0,
        adjusted_quantity: 0,
        variance: 0
      });
    };

    const removeAdjustmentLine = (index) => {
      adjustmentForm.value.lines.splice(index, 1);
    };

    const updateBookQuantity = (index) => {
      const line = adjustmentForm.value.lines[index];
      line.book_quantity = 0;
      line.adjusted_quantity = 0;
      line.variance = 0;
      if (line.item_id && line.warehouse_id) {
        const selectedItem = items.value.find(item => item.item_id === parseInt(line.item_id));
        if (selectedItem) {
          line.book_quantity = selectedItem.current_stock;
          line.adjusted_quantity = selectedItem.current_stock;
        }
      }
    };

    const calculateVariance = (index) => {
      const line = adjustmentForm.value.lines[index];
      line.variance = parseFloat(line.adjusted_quantity) - parseFloat(line.book_quantity);
    };

    const viewAdjustmentDetails = (adjustment) => {
      selectedAdjustment.value = adjustment;
      showDetailsModal.value = true;
    };

    const closeDetailsModal = () => {
      showDetailsModal.value = false;
      selectedAdjustment.value = null;
    };

    const approveAdjustment = (adjustment) => {
      adjustmentToAction.value = adjustment;
      confirmationTitle.value = 'Setujui Penyesuaian';
      confirmationMessage.value = `Apakah Anda yakin ingin menyetujui penyesuaian #${adjustment.adjustment_id}?`;
      confirmationAction.value = 'approve';
      showConfirmationModal.value = true;
    };

    const cancelAdjustment = (adjustment) => {
      adjustmentToAction.value = adjustment;
      confirmationTitle.value = 'Batalkan Penyesuaian';
      confirmationMessage.value = `Apakah Anda yakin ingin membatalkan penyesuaian #${adjustment.adjustment_id}?`;
      confirmationAction.value = 'cancel';
      showConfirmationModal.value = true;
    };

    const closeConfirmationModal = () => {
      showConfirmationModal.value = false;
      adjustmentToAction.value = null;
    };

    const confirmAction = async () => {
      try {
        if (confirmationAction.value === 'approve') {
          const index = adjustments.value.findIndex(adj => adj.adjustment_id === adjustmentToAction.value.adjustment_id);
          if (index !== -1) {
            adjustments.value[index].status = 'Approved';
            if (
              selectedAdjustment.value &&
              selectedAdjustment.value.adjustment_id === adjustmentToAction.value.adjustment_id
            ) {
              selectedAdjustment.value.status = 'Approved';
            }
          }
          alert('Penyesuaian berhasil disetujui!');
        } else if (confirmationAction.value === 'cancel') {
          const index = adjustments.value.findIndex(adj => adj.adjustment_id === adjustmentToAction.value.adjustment_id);
          if (index !== -1) {
            adjustments.value[index].status = 'Cancelled';
            if (
              selectedAdjustment.value &&
              selectedAdjustment.value.adjustment_id === adjustmentToAction.value.adjustment_id
            ) {
              selectedAdjustment.value.status = 'Cancelled';
            }
          }
          alert('Penyesuaian berhasil dibatalkan!');
        }
        closeConfirmationModal();
      } catch (error) {
        console.error('Error updating adjustment:', error);
        alert('Terjadi kesalahan saat memperbarui penyesuaian. Silakan coba lagi.');
        closeConfirmationModal();
      }
    };

    const saveAdjustment = async () => {
      try {
        if (adjustmentForm.value.lines.length === 0) {
          alert('Silakan tambahkan setidaknya satu item.');
          return;
        }

        const hasEmptyFields = adjustmentForm.value.lines.some(line => !line.item_id || !line.warehouse_id);
        if (hasEmptyFields) {
          alert('Silakan isi semua field yang diperlukan untuk detail item.');
          return;
        }

        const newAdjustment = {
          adjustment_id: adjustments.value.length + 1,
          adjustment_date: adjustmentForm.value.adjustment_date,
          adjustment_reason: adjustmentForm.value.adjustment_reason,
          reference_document: adjustmentForm.value.reference_document,
          status: 'Pending',
          lines: adjustmentForm.value.lines.map((line, index) => {
            const selectedItem = items.value.find(item => item.item_id === parseInt(line.item_id));
            const selectedWarehouse = warehouses.value.find(wh => wh.warehouse_id === parseInt(line.warehouse_id));
            let selectedLocation = null;
            if (line.location_id) {
              const availableLocations = getAvailableLocations(line.warehouse_id);
              selectedLocation = availableLocations.find(loc => loc.location_id === parseInt(line.location_id));
            }
            return {
              line_id: index + 1,
              item: selectedItem ? { item_id: selectedItem.item_id, item_code: selectedItem.item_code, name: selectedItem.name } : null,
              warehouse: selectedWarehouse ? { name: selectedWarehouse.name } : null,
              location: selectedLocation ? { code: selectedLocation.code } : null,
              book_quantity: parseFloat(line.book_quantity),
              adjusted_quantity: parseFloat(line.adjusted_quantity),
              variance: parseFloat(line.variance)
            };
          })
        };

        adjustments.value.unshift(newAdjustment);
        closeAdjustmentModal();
        alert('Penyesuaian stok berhasil dibuat!');
      } catch (error) {
        console.error('Error saving adjustment:', error);
        alert('Terjadi kesalahan saat menyimpan penyesuaian. Silakan coba lagi.');
      }
    };

    // Initial data loading
    onMounted(() => {
      fetchAdjustments();
      fetchWarehouses();
      fetchItems();
    });

    return {
      adjustments,
      warehouses,
      items,
      isLoading,
      searchQuery,
      dateRangeFilter,
      statusFilter,
      filteredAdjustments,
      showAdjustmentModal,
      showDetailsModal,
      showConfirmationModal,
      selectedAdjustment,
      confirmationTitle,
      confirmationMessage,
      confirmationAction,
      adjustmentForm,
      formatDate,
      getStatusLabel,
      getTotalVariance,
      getTotalVarianceClass,
      getVarianceClass,
      getAvailableLocations,
      applyFilters,
      clearSearch,
      openNewAdjustmentModal,
      closeAdjustmentModal,
      addAdjustmentLine,
      removeAdjustmentLine,
      updateBookQuantity,
      calculateVariance,
      viewAdjustmentDetails,
      closeDetailsModal,
      approveAdjustment,
      cancelAdjustment,
      closeConfirmationModal,
      confirmAction,
      saveAdjustment
    };
  }
};
</script>

<style scoped>
.stock-adjustments {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.adjustments-container {
  width: 100%;
}

.adjustments-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem;
}

.adjustment-card {
  background-color: white;
  border-radius: 0.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  cursor: pointer;
  transition: box-shadow 0.3s;
}

.adjustment-card:hover {
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.adjustment-header {
  padding: 0.75rem 1rem;
  background-color: var(--gray-50);
  border-bottom: 1px solid var(--gray-200);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.adjustment-status {
  font-size: 0.75rem;
  font-weight: 500;
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
}

.adjustment-status.pending {
  background-color: var(--warning-bg);
  color: var(--warning-color);
}

.adjustment-status.approved {
  background-color: var(--success-bg);
  color: var(--success-color);
}

.adjustment-status.cancelled {
  background-color: var(--gray-100);
  color: var(--gray-600);
}

.adjustment-date {
  font-size: 0.75rem;
  color: var(--gray-500);
}

.adjustment-body {
  padding: 1rem;
  flex: 1;
}

.adjustment-id {
  font-size: 0.875rem;
  color: var(--gray-500);
  margin-bottom: 0.5rem;
}

.adjustment-reason {
  font-size: 0.875rem;
  margin-bottom: 1rem;
  color: var(--gray-700);
}

.adjustment-summary {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
  margin-top: 1rem;
}

.summary-item {
  background-color: var(--gray-50);
  padding: 0.75rem;
  border-radius: 0.375rem;
  text-align: center;
}

.summary-label {
  font-size: 0.75rem;
  color: var(--gray-500);
  margin-bottom: 0.25rem;
}

.summary-value {
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--gray-800);
}

.positive-variance {
  color: var(--success-color);
}

.negative-variance {
  color: var(--danger-color);
}

.adjustment-footer {
  padding: 0.75rem 1rem;
  border-top: 1px solid var(--gray-200);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.reference-document {
  font-size: 0.75rem;
  color: var(--gray-500);
}

.adjustment-actions {
  display: flex;
  gap: 0.5rem;
}

.action-btn {
  background: none;
  border: none;
  font-size: 0.75rem;
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.approve-btn {
  color: var(--success-color);
}

.approve-btn:hover {
  background-color: var(--success-bg);
}

.cancel-btn {
  color: var(--danger-color);
}

.cancel-btn:hover {
  background-color: var(--danger-bg);
}

.delete-btn {
  color: var(--danger-color);
}

.delete-btn:hover {
  background-color: var(--danger-bg);
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 0;
  text-align: center;
  color: var(--gray-500);
}

.empty-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
  color: var(--gray-300);
}

.empty-state h3 {
  font-size: 1.25rem;
  margin: 0 0 0.5rem 0;
  color: var(--gray-800);
}

.empty-state p {
  margin: 0;
  font-size: 0.875rem;
}

.loading-indicator {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 4rem 0;
  color: var(--gray-500);
  font-size: 1rem;
}

.loading-indicator i {
  margin-right: 0.5rem;
}

.adjustment-lines-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  padding-top: 1rem;
  border-top: 1px solid var(--gray-200);
}

.adjustment-lines-header h3 {
  font-size: 1rem;
  font-weight: 600;
  margin: 0;
  color: var(--gray-800);
}

.adjustment-lines {
  margin-bottom: 1.5rem;
}

.empty-lines {
  background-color: var(--gray-50);
  padding: 1.5rem;
  border-radius: 0.375rem;
  text-align: center;
  color: var(--gray-500);
  font-style: italic;
}

.adjustment-lines-table {
  overflow-x: auto;
}

.adjustment-lines-table table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
}

.adjustment-lines-table th {
  text-align: left;
  padding: 0.75rem;
  background-color: var(--gray-50);
  border-bottom: 1px solid var(--gray-200);
  color: var(--gray-600);
  font-weight: 500;
}

.adjustment-lines-table td {
  padding: 0.5rem 0.75rem;
  border-bottom: 1px solid var(--gray-100);
}

.quantity-input {
  text-align: right;
}

.variance {
  font-weight: 500;
}

.adjustment-details {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.details-header {
  background-color: var(--gray-50);
  border-radius: 0.375rem;
  padding: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.detail-row {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 1rem;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.detail-item.full-width {
  grid-column: 1 / -1;
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

.details-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  background-color: var(--gray-50);
  border-radius: 0.375rem;
}

.details-actions {
  display: flex;
  gap: 0.75rem;
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

@media (max-width: 768px) {
  .adjustments-grid {
    grid-template-columns: 1fr;
  }

  .detail-row {
    grid-template-columns: 1fr;
  }

  .details-footer {
    flex-direction: column;
    gap: 1rem;
  }

  .details-actions {
    flex-direction: column;
    align-items: stretch;
  }
}
</style>