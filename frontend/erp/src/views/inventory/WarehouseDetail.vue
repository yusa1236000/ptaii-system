<!-- src/views/inventory/WarehouseDetail.vue -->
<template>
  <div class="warehouse-detail-page">
    <div class="page-header">
      <div class="header-content">
        <div class="header-top">
          <h2 class="page-title">
            Warehouse: {{ warehouse?.name }}
            <span v-if="warehouse?.code" class="warehouse-code">({{ warehouse.code }})</span>
          </h2>
          <div class="header-actions">
            <button class="btn-secondary" @click="editWarehouse" v-if="warehouse">
              <i class="fas fa-edit mr-2"></i> Edit Details
            </button>
            <router-link :to="`/warehouses/${warehouseId}/zones`" class="btn-primary">
              <i class="fas fa-map mr-2"></i> Manage Zones
            </router-link>
          </div>
        </div>
        
        <div class="warehouse-status" v-if="warehouse">
          <span 
            class="status-badge" 
            :class="warehouse.is_active ? 'status-active' : 'status-inactive'"
          >
            {{ warehouse.is_active ? 'Active' : 'Inactive' }}
          </span>
        </div>
      </div>
      
      <div class="breadcrumbs">
        <router-link to="/warehouses" class="breadcrumb-item">
          <i class="fas fa-warehouse mr-1"></i> Warehouses
        </router-link>
        <span class="breadcrumb-separator">/</span>
        <span class="breadcrumb-item active">Details</span>
      </div>
    </div>

    <div v-if="loading" class="loading-indicator">
      <i class="fas fa-spinner fa-spin mr-2"></i> Loading warehouse details...
    </div>

    <div v-else-if="error" class="error-message">
      <i class="fas fa-exclamation-triangle mr-2"></i>
      {{ error }}
    </div>

    <div v-else-if="!warehouse" class="not-found">
      <i class="fas fa-question-circle not-found-icon"></i>
      <h3>Warehouse Not Found</h3>
      <p>The warehouse you're looking for doesn't exist or has been deleted.</p>
      <router-link to="/warehouses" class="btn-primary mt-4">
        Return to Warehouses List
      </router-link>
    </div>

    <div v-else class="warehouse-details-container">
      <div class="details-card">
        <div class="card-header">
          <h3 class="card-title">Details</h3>
        </div>
        <div class="card-body">
          <div class="detail-item">
            <span class="detail-label">Warehouse ID:</span>
            <span class="detail-value">{{ warehouse.warehouse_id }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Code:</span>
            <span class="detail-value">{{ warehouse.code }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Address:</span>
            <span class="detail-value">
              {{ warehouse.address || 'No address specified' }}
            </span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Status:</span>
            <span 
              class="status-badge detail-value" 
              :class="warehouse.is_active ? 'status-active' : 'status-inactive'"
            >
              {{ warehouse.is_active ? 'Active' : 'Inactive' }}
            </span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Created At:</span>
            <span class="detail-value">{{ formatDate(warehouse.created_at) }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Updated At:</span>
            <span class="detail-value">{{ formatDate(warehouse.updated_at) }}</span>
          </div>
        </div>
      </div>

      <div class="details-card">
        <div class="card-header">
          <h3 class="card-title">Warehouse Structure</h3>
          <router-link :to="`/warehouses/${warehouseId}/zones`" class="btn-text">
            <i class="fas fa-external-link-alt mr-1"></i> Manage Zones
          </router-link>
        </div>
        <div class="card-body">
          <div class="structure-stats">
            <div class="stat-card">
              <div class="stat-icon">
                <i class="fas fa-map"></i>
              </div>
              <div class="stat-content">
                <div class="stat-value">{{ zonesCount }}</div>
                <div class="stat-label">Zones</div>
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-icon">
                <i class="fas fa-map-marker-alt"></i>
              </div>
              <div class="stat-content">
                <div class="stat-value">{{ locationsCount }}</div>
                <div class="stat-label">Locations</div>
              </div>
            </div>
          </div>

          <div v-if="zonesCount === 0" class="empty-zones-message">
            <p>This warehouse doesn't have any zones yet.</p>
            <router-link :to="`/warehouses/${warehouseId}/zones`" class="btn-primary btn-sm mt-2">
              <i class="fas fa-plus-circle mr-1"></i> Add Zones
            </router-link>
          </div>
          <div v-else class="zones-preview">
            <h4 class="preview-title">Zones</h4>
            <div class="zones-grid">
              <router-link 
                v-for="zone in zones.slice(0, 4)" 
                :key="zone.zone_id" 
                :to="`/warehouses/${warehouseId}/zones/${zone.zone_id}`"
                class="zone-card"
              >
                <div class="zone-name">{{ zone.name }}</div>
                <div class="zone-code">{{ zone.code }}</div>
                <div class="zone-locations">
                  {{ zone.locations ? zone.locations.length : 0 }} Locations
                </div>
              </router-link>
              
              <router-link 
                v-if="zones.length > 4" 
                :to="`/warehouses/${warehouseId}`"
                class="view-all-card"
              >
                <div class="view-all-content">
                  <i class="fas fa-ellipsis-h"></i>
                  <div>View All Zones</div>
                </div>
              </router-link>
            </div>
          </div>
        </div>
      </div>

      <div class="details-card">
        <div class="card-header">
          <h3 class="card-title">Inventory Overview</h3>
          <div class="header-actions">
            <button class="btn-text" @click="refreshInventory">
              <i class="fas fa-sync-alt mr-1"></i> Refresh
            </button>
          </div>
        </div>
        <div class="card-body">
          <div v-if="inventoryLoading" class="loading-indicator">
            <i class="fas fa-spinner fa-spin mr-2"></i> Loading inventory...
          </div>
          
          <div v-else-if="inventoryError" class="error-message">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            {{ inventoryError }}
          </div>
          
          <div v-else-if="inventoryItems.length === 0" class="empty-inventory-message">
            <p>No inventory items found in this warehouse.</p>
          </div>
          
          <div v-else class="inventory-overview">
            <div class="inventory-stats">
              <div class="stat-card">
                <div class="stat-icon">
                  <i class="fas fa-cubes"></i>
                </div>
                <div class="stat-content">
                  <div class="stat-value">{{ inventoryItems.length }}</div>
                  <div class="stat-label">Unique Items</div>
                </div>
              </div>
              <div class="stat-card">
                <div class="stat-icon">
                  <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-content">
                  <div class="stat-value">{{ lowStockCount }}</div>
                  <div class="stat-label">Low Stock Items</div>
                </div>
              </div>
            </div>
            
            <h4 class="preview-title">Top Items by Quantity</h4>
            <div class="inventory-table-container">
              <table class="inventory-table">
                <thead>
                  <tr>
                    <th>Item Code</th>
                    <th>Name</th>
                    <th class="text-right">Quantity</th>
                    <th>UOM</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in topItems" :key="item.item_id">
                    <td>{{ item.item_code }}</td>
                    <td>{{ item.name }}</td>
                    <td class="text-right">
                      <span :class="getQuantityClass(item.stock)">
                        {{ item.stock }}
                      </span>
                    </td>
                    <td>{{ item.uom }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
            
            <div class="view-all-inventory">
              <button class="btn-text" @click="showInventoryModal = true">
                <i class="fas fa-search mr-1"></i> View All Inventory
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Edit Warehouse Modal -->
    <div v-if="showEditModal" class="modal-backdrop">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Edit Warehouse</h3>
          <button class="btn-close" @click="showEditModal = false">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="saveWarehouse">
            <div class="form-group">
              <label for="warehouseName">Warehouse Name</label>
              <input
                id="warehouseName"
                v-model="warehouseForm.name"
                type="text"
                class="form-control"
                required
              />
            </div>

            <div class="form-group">
              <label for="warehouseCode">Warehouse Code</label>
              <input
                id="warehouseCode"
                v-model="warehouseForm.code"
                type="text"
                class="form-control"
                required
              />
              <small class="form-text text-muted">
                A unique code to identify this warehouse
              </small>
            </div>

            <div class="form-group">
              <label for="warehouseAddress">Address</label>
              <textarea
                id="warehouseAddress"
                v-model="warehouseForm.address"
                class="form-control"
                rows="3"
              ></textarea>
            </div>

            <div class="form-group">
              <label class="checkbox-label">
                <input
                  type="checkbox"
                  v-model="warehouseForm.is_active"
                /> 
                Active
              </label>
              <small class="form-text text-muted">
                Inactive warehouses won't be available for new transactions
              </small>
            </div>

            <div class="form-group form-actions">
              <button type="button" class="btn-secondary" @click="showEditModal = false">
                Cancel
              </button>
              <button type="submit" class="btn-primary" :disabled="isSubmitting">
                <i v-if="isSubmitting" class="fas fa-spinner fa-spin mr-2"></i>
                Update Warehouse
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
    
    <!-- Inventory Modal -->
    <div v-if="showInventoryModal" class="modal-backdrop">
      <div class="modal-content modal-lg">
        <div class="modal-header">
          <h3>Warehouse Inventory</h3>
          <button class="btn-close" @click="showInventoryModal = false">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <div class="inventory-search">
            <div class="search-input">
              <i class="fas fa-search search-icon"></i>
              <input 
                type="text" 
                v-model="inventorySearchQuery" 
                placeholder="Search items..." 
                class="form-control search-control"
              />
            </div>
          </div>
          
          <div class="inventory-table-container">
            <table class="inventory-table">
              <thead>
                <tr>
                  <th>Item Code</th>
                  <th>Name</th>
                  <th>Category</th>
                  <th class="text-right">Quantity</th>
                  <th>UOM</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in filteredInventoryItems" :key="item.item_id">
                  <td>{{ item.item_code }}</td>
                  <td>{{ item.name }}</td>
                  <td>{{ item.category || 'Uncategorized' }}</td>
                  <td class="text-right">
                    <span :class="getQuantityClass(item.stock)">
                      {{ item.stock }}
                    </span>
                  </td>
                  <td>{{ item.uom }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';

export default {
  name: 'WarehouseDetail',
  setup() {
    const route = useRoute();
    const warehouseId = computed(() => route.params.id);
    
    const warehouse = ref(null);
    const zones = ref([]);
    const loading = ref(true);
    const error = ref(null);
    
    // Computed properties for warehouse structure
    const zonesCount = computed(() => zones.value.length);
    const locationsCount = computed(() => {
      let count = 0;
      zones.value.forEach(zone => {
        if (zone.locations) {
          count += zone.locations.length;
        }
      });
      return count;
    });
    
    // Inventory state
    const inventoryItems = ref([]);
    const inventoryLoading = ref(false);
    const inventoryError = ref(null);
    const inventorySearchQuery = ref('');
    const showInventoryModal = ref(false);
    
    // Edit warehouse modal state
    const showEditModal = ref(false);
    const isSubmitting = ref(false);
    const warehouseForm = reactive({
      name: '',
      code: '',
      address: '',
      is_active: true
    });
    
    // Computed properties for inventory
    const lowStockCount = computed(() => {
      return inventoryItems.value.filter(item => {
        // Assuming items have a minimum_stock property to check against
        return item.stock <= 5; // For demonstration - this would be compared with item.minimum_stock in a real application
      }).length;
    });
    
    const topItems = computed(() => {
      return [...inventoryItems.value]
        .sort((a, b) => b.stock - a.stock)
        .slice(0, 5);
    });
    
    const filteredInventoryItems = computed(() => {
      if (!inventorySearchQuery.value) return inventoryItems.value;
      
      const query = inventorySearchQuery.value.toLowerCase();
      return inventoryItems.value.filter(item => 
        item.item_code.toLowerCase().includes(query) ||
        item.name.toLowerCase().includes(query) ||
        (item.category && item.category.toLowerCase().includes(query))
      );
    });
    
    const fetchWarehouseData = async () => {
      loading.value = true;
      error.value = null;
      
      try {
        const response = await axios.get(`/warehouses/${warehouseId.value}`);
        warehouse.value = response.data.data;
        
        if (warehouse.value.zones) {
          zones.value = warehouse.value.zones;
        } else {
          // Fetch zones separately if not included in warehouse response
          await fetchWarehouseZones();
        }
      } catch (err) {
        console.error('Error fetching warehouse:', err);
        error.value = 'Failed to load warehouse details. Please try again.';
      } finally {
        loading.value = false;
      }
    };
    
    const fetchWarehouseZones = async () => {
      try {
        const response = await axios.get(`/warehouses/${warehouseId.value}/zones`);
        zones.value = response.data.data;
      } catch (err) {
        console.error('Error fetching zones:', err);
      }
    };
    
    const fetchWarehouseInventory = async () => {
      inventoryLoading.value = true;
      inventoryError.value = null;
      
      try {
        const response = await axios.get(`/warehouses/${warehouseId.value}/inventory`);
        inventoryItems.value = response.data.data.inventory;
      } catch (err) {
        console.error('Error fetching inventory:', err);
        inventoryError.value = 'Failed to load inventory data. Please try again.';
      } finally {
        inventoryLoading.value = false;
      }
    };
    
    const refreshInventory = () => {
      fetchWarehouseInventory();
    };
    
    const editWarehouse = () => {
      warehouseForm.name = warehouse.value.name;
      warehouseForm.code = warehouse.value.code;
      warehouseForm.address = warehouse.value.address || '';
      warehouseForm.is_active = warehouse.value.is_active;
      showEditModal.value = true;
    };
    
    const saveWarehouse = async () => {
      isSubmitting.value = true;
      
      try {
        const response = await axios.put(`/warehouses/${warehouseId.value}`, warehouseForm);
        warehouse.value = response.data.data;
        showEditModal.value = false;
      } catch (err) {
        console.error('Error updating warehouse:', err);
        error.value = err.response?.data?.message || 'Failed to update warehouse. Please try again.';
      } finally {
        isSubmitting.value = false;
      }
    };
    
    const getQuantityClass = (quantity) => {
      if (quantity <= 0) return 'quantity-empty';
      if (quantity < 5) return 'quantity-low';
      return 'quantity-normal';
    };
    
    const formatDate = (dateString) => {
      if (!dateString) return 'N/A';
      
      const date = new Date(dateString);
      return new Intl.DateTimeFormat('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      }).format(date);
    };
    
    onMounted(async () => {
      await fetchWarehouseData();
      await fetchWarehouseInventory();
    });
    
    return {
      warehouseId,
      warehouse,
      zones,
      loading,
      error,
      zonesCount,
      locationsCount,
      inventoryItems,
      inventoryLoading,
      inventoryError,
      inventorySearchQuery,
      showInventoryModal,
      showEditModal,
      isSubmitting,
      warehouseForm,
      lowStockCount,
      topItems,
      filteredInventoryItems,
      fetchWarehouseData,
      refreshInventory,
      editWarehouse,
      saveWarehouse,
      getQuantityClass,
      formatDate
    };
  }
};
</script>

<style scoped>
.warehouse-detail-page {
  padding: 1rem;
}

.page-header {
  margin-bottom: 2rem;
}

.header-content {
  margin-bottom: 1rem;
}

.header-top {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.page-title {
  margin: 0;
  font-size: 1.5rem;
  color: var(--gray-800);
}

.warehouse-code {
  font-size: 1rem;
  color: var(--gray-600);
  font-weight: normal;
}

.header-actions {
  display: flex;
  gap: 0.75rem;
}

.warehouse-status {
  margin-top: 0.5rem;
}

.status-badge {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
  font-weight: 600;
  border-radius: 9999px;
}

.status-active {
  background-color: rgba(5, 150, 105, 0.1);
  color: var(--success-color);
}

.status-inactive {
  background-color: rgba(220, 38, 38, 0.1);
  color: var(--danger-color);
}

.breadcrumbs {
  display: flex;
  align-items: center;
  font-size: 0.875rem;
}

.breadcrumb-item {
  color: var(--primary-color);
}

.breadcrumb-item.active {
  color: var(--gray-600);
}

.breadcrumb-separator {
  margin: 0 0.5rem;
  color: var(--gray-400);
}

.loading-indicator {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 2rem;
  color: var(--gray-500);
}

.error-message {
  padding: 1rem;
  background-color: rgba(220, 38, 38, 0.1);
  color: var(--danger-color);
  border-radius: 0.375rem;
  margin-bottom: 1rem;
}

.not-found {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem;
  background-color: white;
  border-radius: 0.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  text-align: center;
}

.not-found-icon {
  font-size: 3rem;
  color: var(--gray-400);
  margin-bottom: 1rem;
}

.warehouse-details-container {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
  gap: 1.5rem;
}

.details-card {
  background-color: white;
  border-radius: 0.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.card-header {
  padding: 1rem;
  border-bottom: 1px solid var(--gray-200);
  display: flex;
  justify-content: space-between;
  align-items: center;
  background-color: var(--gray-50);
}

.card-title {
  margin: 0;
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--gray-800);
}

.card-body {
  padding: 1rem;
}

.detail-item {
  margin-bottom: 0.75rem;
  display: grid;
  grid-template-columns: 120px 1fr;
  gap: 1rem;
}

.detail-label {
  font-weight: 500;
  color: var(--gray-600);
}

.detail-value {
  color: var(--gray-800);
}

.structure-stats,
.inventory-stats {
  display: flex;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.stat-card {
  flex: 1;
  background-color: var(--gray-50);
  border-radius: 0.5rem;
  padding: 1rem;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.stat-icon {
  width: 2.5rem;
  height: 2.5rem;
  background-color: var(--primary-color);
  color: white;
  border-radius: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
}

.stat-content {
  flex: 1;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--gray-800);
  line-height: 1.2;
}

.stat-label {
  font-size: 0.875rem;
  color: var(--gray-600);
}

.empty-zones-message,
.empty-inventory-message {
  padding: 1.5rem;
  background-color: var(--gray-50);
  border-radius: 0.5rem;
  text-align: center;
  color: var(--gray-600);
}

.preview-title {
  margin: 1rem 0 0.75rem 0;
  font-size: 1rem;
  font-weight: 600;
  color: var(--gray-700);
}

.zones-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: 0.75rem;
}

.zone-card {
  background-color: var(--gray-50);
  border-radius: 0.375rem;
  padding: 0.75rem;
  border: 1px solid var(--gray-200);
  color: var(--gray-800);
  text-decoration: none;
  transition: transform 0.2s, box-shadow 0.2s;
}

.zone-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  border-color: var(--primary-color);
  text-decoration: none;
}

.zone-name {
  font-weight: 600;
  margin-bottom: 0.25rem;
}

.zone-code {
  font-size: 0.75rem;
  color: var(--gray-500);
  margin-bottom: 0.5rem;
}

.zone-locations {
  font-size: 0.75rem;
  color: var(--gray-600);
}

.view-all-card {
  background-color: var(--gray-100);
  border-radius: 0.375rem;
  border: 1px dashed var(--gray-300);
  color: var(--gray-600);
  text-decoration: none;
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
}

.view-all-card:hover {
  background-color: var(--gray-200);
  color: var(--gray-800);
  text-decoration: none;
}

.view-all-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
}

.inventory-table-container {
  overflow-x: auto;
  border: 1px solid var(--gray-200);
  border-radius: 0.375rem;
  margin-bottom: 1rem;
}

.inventory-table {
  width: 100%;
  border-collapse: collapse;
}

.inventory-table th,
.inventory-table td {
  padding: 0.75rem 1rem;
  text-align: left;
}

.inventory-table th {
  background-color: var(--gray-50);
  color: var(--gray-600);
  font-weight: 500;
  font-size: 0.875rem;
}

.inventory-table td {
  border-top: 1px solid var(--gray-200);
}

.inventory-table tr:hover {
  background-color: var(--gray-50);
}

.quantity-empty {
  color: var(--danger-color);
  font-weight: 600;
}

.quantity-low {
  color: var(--warning-color);
  font-weight: 600;
}

.quantity-normal {
  color: var(--success-color);
  font-weight: 600;
}

.view-all-inventory {
  text-align: center;
  margin-top: 0.5rem;
}

.inventory-search {
  margin-bottom: 1rem;
}

.search-input {
  position: relative;
}

.search-icon {
  position: absolute;
  left: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  color: var(--gray-400);
}

.search-control {
  padding-left: 2.25rem;
}

.text-right {
  text-align: right;
}

.btn-primary {
  background-color: var(--primary-color);
  color: white;
  border: none;
  border-radius: 0.375rem;
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  text-decoration: none;
}

.btn-primary:hover {
  background-color: var(--primary-dark);
  text-decoration: none;
}

.btn-secondary {
  background-color: var(--gray-200);
  color: var(--gray-800);
  border: none;
  border-radius: 0.375rem;
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
}

.btn-secondary:hover {
  background-color: var(--gray-300);
}

.btn-text {
  background: none;
  border: none;
  color: var(--primary-color);
  padding: 0.25rem 0.5rem;
  font-size: 0.875rem;
  cursor: pointer;
  border-radius: 0.25rem;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
}

.btn-text:hover {
  background-color: var(--gray-100);
  text-decoration: none;
}

.btn-sm {
  font-size: 0.75rem;
  padding: 0.25rem 0.5rem;
}

.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal-content {
  background-color: white;
  border-radius: 0.5rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  width: 95%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-lg {
  max-width: 800px;
}

.modal-header {
  padding: 1rem;
  border-bottom: 1px solid var(--gray-200);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header h3 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--gray-800);
}

.btn-close {
  background: none;
  border: none;
  color: var(--gray-500);
  cursor: pointer;
  font-size: 1.25rem;
  line-height: 1;
  padding: 0.25rem;
}

.btn-close:hover {
  color: var(--gray-800);
}

.modal-body {
  padding: 1rem;
}

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: var(--gray-700);
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
}

.form-control {
  width: 100%;
  padding: 0.5rem 0.75rem;
  font-size: 1rem;
  line-height: 1.5;
  color: var(--gray-900);
  background-color: white;
  border: 1px solid var(--gray-300);
  border-radius: 0.375rem;
}

.form-control:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
}

.form-text {
  display: block;
  margin-top: 0.25rem;
  font-size: 0.75rem;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  margin-top: 1.5rem;
}

.mr-1 {
  margin-right: 0.25rem;
}

.mr-2 {
  margin-right: 0.5rem;
}

.mt-2 {
  margin-top: 0.5rem;
}

.mt-4 {
  margin-top: 1rem;
}

@media (max-width: 768px) {
  .header-top {
    flex-direction: column;
  }

  .header-actions {
    margin-top: 1rem;
  }

  .warehouse-details-container {
    grid-template-columns: 1fr;
  }
}
</style>