<!-- src/views/inventory/WarehousesList.vue -->
<template>
    <div class="warehouses-list">
      <div class="page-actions">
        <div class="search-box">
          <i class="fas fa-search search-icon"></i>
          <input 
            type="text" 
            v-model="searchQuery" 
            placeholder="Search warehouses..." 
            @input="applyFilters"
          />
          <button v-if="searchQuery" @click="clearSearch" class="clear-search">
            <i class="fas fa-times"></i>
          </button>
        </div>
        
        <button class="btn btn-primary" @click="openAddWarehouseModal">
          <i class="fas fa-plus"></i> Add Warehouse
        </button>
      </div>
      
      <div class="warehouses-container">
        <div v-if="isLoading" class="loading-indicator">
          <i class="fas fa-spinner fa-spin"></i> Loading warehouses...
        </div>
        
        <div v-else-if="filteredWarehouses.length === 0" class="empty-state">
          <div class="empty-icon">
            <i class="fas fa-warehouse"></i>
          </div>
          <h3>No warehouses found</h3>
          <p>Try adjusting your search or add a new warehouse.</p>
        </div>
        
        <div v-else class="warehouses-grid">
          <div 
            v-for="warehouse in filteredWarehouses" 
            :key="warehouse.warehouse_id" 
            class="warehouse-card"
          >
            <div class="warehouse-header">
              <div class="warehouse-status" :class="{ inactive: !warehouse.is_active }">
                {{ warehouse.is_active ? 'Active' : 'Inactive' }}
              </div>
              <div class="warehouse-actions">
                <button class="action-btn" title="Edit Warehouse" @click="editWarehouse(warehouse)">
                  <i class="fas fa-edit"></i>
                </button>
                <button class="action-btn" title="Delete Warehouse" @click="confirmDelete(warehouse)">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </div>
            
            <div class="warehouse-content" @click="viewWarehouse(warehouse)">
              <div class="warehouse-icon">
                <i class="fas fa-warehouse"></i>
              </div>
              <div class="warehouse-info">
                <h3 class="warehouse-name">{{ warehouse.name }}</h3>
                <div class="warehouse-code">{{ warehouse.code }}</div>
                <p v-if="warehouse.address" class="warehouse-address">
                  <i class="fas fa-map-marker-alt"></i> {{ warehouse.address }}
                </p>
              </div>
            </div>
            
            <div class="warehouse-footer">
              <div class="warehouse-stat">
                <div class="stat-label">Zones</div>
                <div class="stat-value">{{ warehouse.zones ? warehouse.zones.length : 0 }}</div>
              </div>
              <div class="warehouse-stat">
                <div class="stat-label">Locations</div>
                <div class="stat-value">{{ getLocationCount(warehouse) }}</div>
              </div>
              <div class="warehouse-stat">
                <div class="stat-label">Items</div>
                <div class="stat-value">{{ warehouse.itemCount || 0 }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Add/Edit Warehouse Modal -->
      <div v-if="showWarehouseModal" class="modal">
        <div class="modal-backdrop" @click="closeWarehouseModal"></div>
        <div class="modal-content">
          <div class="modal-header">
            <h2>{{ isEditMode ? 'Edit Warehouse' : 'Add New Warehouse' }}</h2>
            <button class="close-btn" @click="closeWarehouseModal">
              <i class="fas fa-times"></i>
            </button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveWarehouse">
              <div class="form-row">
                <div class="form-group">
                  <label for="name">Warehouse Name*</label>
                  <input 
                    type="text" 
                    id="name" 
                    v-model="warehouseForm.name" 
                    required
                  />
                </div>
                <div class="form-group">
                  <label for="code">Warehouse Code*</label>
                  <input 
                    type="text" 
                    id="code" 
                    v-model="warehouseForm.code" 
                    required
                    :disabled="isEditMode"
                  />
                </div>
              </div>
              
              <div class="form-group">
                <label for="address">Address</label>
                <textarea 
                  id="address" 
                  v-model="warehouseForm.address" 
                  rows="3"
                ></textarea>
              </div>
              
              <div class="form-group">
                <div class="checkbox-group">
                  <input 
                    type="checkbox" 
                    id="is_active" 
                    v-model="warehouseForm.is_active"
                  />
                  <label for="is_active">Active</label>
                </div>
              </div>
              
              <div class="form-actions">
                <button type="button" class="btn btn-secondary" @click="closeWarehouseModal">
                  Cancel
                </button>
                <button type="submit" class="btn btn-primary">
                  {{ isEditMode ? 'Update Warehouse' : 'Add Warehouse' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
      
      <!-- Delete Confirmation Modal -->
      <div v-if="showDeleteModal" class="modal">
        <div class="modal-backdrop" @click="closeDeleteModal"></div>
        <div class="modal-content modal-sm">
          <div class="modal-header">
            <h2>Confirm Delete</h2>
            <button class="close-btn" @click="closeDeleteModal">
              <i class="fas fa-times"></i>
            </button>
          </div>
          <div class="modal-body">
            <p>Are you sure you want to delete <strong>{{ warehouseToDelete.name }}</strong>?</p>
            <p class="text-danger">This action cannot be undone.</p>
            
            <div class="form-actions">
              <button type="button" class="btn btn-secondary" @click="closeDeleteModal">
                Cancel
              </button>
              <button type="button" class="btn btn-danger" @click="deleteWarehouse">
                Delete Warehouse
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script>
  import { ref, computed, onMounted } from 'vue';
  import { useRouter } from 'vue-router';
  // import axios from 'axios';
  
  export default {
    name: 'WarehousesList',
    setup() {
      const router = useRouter();
      const warehouses = ref([]);
      const isLoading = ref(true);
      const searchQuery = ref('');
      
      // Modals
      const showWarehouseModal = ref(false);
      const showDeleteModal = ref(false);
      const isEditMode = ref(false);
      const warehouseForm = ref({
        name: '',
        code: '',
        address: '',
        is_active: true
      });
      const warehouseToDelete = ref({});
      
      // Computed properties
      const filteredWarehouses = computed(() => {
        if (!searchQuery.value) {
          return warehouses.value;
        }
        
        const query = searchQuery.value.toLowerCase();
        return warehouses.value.filter(warehouse => 
          warehouse.name.toLowerCase().includes(query) || 
          warehouse.code.toLowerCase().includes(query) ||
          (warehouse.address && warehouse.address.toLowerCase().includes(query))
        );
      });
      
      // Methods
      const fetchWarehouses = async () => {
        isLoading.value = true;
        
        try {
          // In a real app, this would be an API call
          // const response = await axios.get('/api/warehouses');
          // warehouses.value = response.data.data;
          
          // For demo purposes, use dummy data
          setTimeout(() => {
            warehouses.value = [
              {
                warehouse_id: 1,
                name: 'Main Warehouse',
                code: 'WH-001',
                address: '123 Commerce St, Industrial Park, City',
                is_active: true,
                zones: [
                  { zone_id: 1, name: 'Receiving Zone', locations: [{ location_id: 1 }, { location_id: 2 }] },
                  { zone_id: 2, name: 'Storage Zone', locations: [{ location_id: 3 }, { location_id: 4 }, { location_id: 5 }] }
                ],
                itemCount: 125
              },
              {
                warehouse_id: 2,
                name: 'Retail Store',
                code: 'WH-002',
                address: '456 Main St, Downtown, City',
                is_active: true,
                zones: [
                  { zone_id: 3, name: 'Sales Floor', locations: [{ location_id: 6 }, { location_id: 7 }] },
                  { zone_id: 4, name: 'Back Store', locations: [{ location_id: 8 }] }
                ],
                itemCount: 78
              },
              {
                warehouse_id: 3,
                name: 'Office Supplies',
                code: 'WH-003',
                address: '789 Business Park, Suburb, City',
                is_active: true,
                zones: [
                  { zone_id: 5, name: 'Office Zone', locations: [{ location_id: 9 }, { location_id: 10 }] }
                ],
                itemCount: 42
              },
              {
                warehouse_id: 4,
                name: 'Archived Storage',
                code: 'WH-004',
                address: '101 Old Factory Rd, Industrial Area, City',
                is_active: false,
                zones: [],
                itemCount: 0
              }
            ];
            
            isLoading.value = false;
          }, 500);
        } catch (error) {
          console.error('Error fetching warehouses:', error);
          isLoading.value = false;
        }
      };
      
      const getLocationCount = (warehouse) => {
        if (!warehouse.zones) return 0;
        
        return warehouse.zones.reduce((total, zone) => {
          return total + (zone.locations ? zone.locations.length : 0);
        }, 0);
      };
      
      const applyFilters = () => {
        // Nothing to do specifically for filtering warehouses
        // This is just a placeholder for potential future filtering logic
      };
      
      const clearSearch = () => {
        searchQuery.value = '';
      };
      
      const openAddWarehouseModal = () => {
        isEditMode.value = false;
        warehouseForm.value = {
          name: '',
          code: '',
          address: '',
          is_active: true
        };
        showWarehouseModal.value = true;
      };
      
      const editWarehouse = (warehouse) => {
        isEditMode.value = true;
        warehouseForm.value = {
          warehouse_id: warehouse.warehouse_id,
          name: warehouse.name,
          code: warehouse.code,
          address: warehouse.address || '',
          is_active: warehouse.is_active
        };
        showWarehouseModal.value = true;
      };
      
      const viewWarehouse = (warehouse) => {
        // Navigate to warehouse details page
        router.push(`/warehouses/${warehouse.warehouse_id}`);
      };
      
      const closeWarehouseModal = () => {
        showWarehouseModal.value = false;
      };
      
      const saveWarehouse = async () => {
        try {
          if (isEditMode.value) {
            // In a real app, this would be an API call
            // await axios.put(`/api/warehouses/${warehouseForm.value.warehouse_id}`, warehouseForm.value);
            
            // For demo purposes, update the local state
            const index = warehouses.value.findIndex(wh => wh.warehouse_id === warehouseForm.value.warehouse_id);
            if (index !== -1) {
              warehouses.value[index] = {
                ...warehouses.value[index],
                name: warehouseForm.value.name,
                address: warehouseForm.value.address,
                is_active: warehouseForm.value.is_active
              };
            }
            
            // Show success message
            alert('Warehouse updated successfully!');
          } else {
            // In a real app, this would be an API call
            // const response = await axios.post('/api/warehouses', warehouseForm.value);
            
            // For demo purposes, add to the local state
            const newWarehouse = {
              warehouse_id: warehouses.value.length + 1,
              name: warehouseForm.value.name,
              code: warehouseForm.value.code,
              address: warehouseForm.value.address,
              is_active: warehouseForm.value.is_active,
              zones: [],
              itemCount: 0
            };
            
            warehouses.value.push(newWarehouse);
            
            // Show success message
            alert('Warehouse added successfully!');
          }
          
          // Close the modal
          closeWarehouseModal();
        } catch (error) {
          console.error('Error saving warehouse:', error);
          alert('An error occurred while saving the warehouse. Please try again.');
        }
      };
      
      const confirmDelete = (warehouse) => {
        warehouseToDelete.value = warehouse;
        showDeleteModal.value = true;
      };
      
      const closeDeleteModal = () => {
        showDeleteModal.value = false;
      };
      
      const deleteWarehouse = async () => {
        try {
          // In a real app, this would be an API call
          // await axios.delete(`/api/warehouses/${warehouseToDelete.value.warehouse_id}`);
          
          // For demo purposes, remove from the local state
          warehouses.value = warehouses.value.filter(wh => wh.warehouse_id !== warehouseToDelete.value.warehouse_id);
          
          // Close the modal
          closeDeleteModal();
          
          // Show success message
          alert('Warehouse deleted successfully!');
        } catch (error) {
          console.error('Error deleting warehouse:', error);
          alert('An error occurred while deleting the warehouse. Please try again.');
        }
      };
      
      // Initial data loading
      onMounted(() => {
        fetchWarehouses();
      });
      
      return {
        warehouses,
        isLoading,
        searchQuery,
        filteredWarehouses,
        showWarehouseModal,
        showDeleteModal,
        isEditMode,
        warehouseForm,
        warehouseToDelete,
        getLocationCount,
        applyFilters,
        clearSearch,
        openAddWarehouseModal,
        editWarehouse,
        viewWarehouse,
        closeWarehouseModal,
        saveWarehouse,
        confirmDelete,
        closeDeleteModal,
        deleteWarehouse
      };
    }
  };
  
  </script>
  
  <style scoped>
  .warehouses-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
  }
  
  .page-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
  }
  
  .search-box {
    position: relative;
    width: 100%;
    max-width: 400px;
  }
  
  .search-icon {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    color: #64748b;
  }
  
  .search-box input {
    width: 100%;
    padding: 0.625rem 2.5rem 0.625rem 2.25rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.375rem;
    font-size: 0.875rem;
  }
  
  .clear-search {
    position: absolute;
    right: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #64748b;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.25rem;
  }
  
  .btn {
    padding: 0.625rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    border-radius: 0.375rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    border: none;
    transition: background-color 0.2s, color 0.2s;
  }
  
  .btn-primary {
    background-color: #2563eb;
    color: white;
  }
  
  .btn-primary:hover {
    background-color: #1d4ed8;
  }
  
  .btn-secondary {
    background-color: #e2e8f0;
    color: #1e293b;
  }
  
  .btn-secondary:hover {
    background-color: #cbd5e1;
  }
  
  .btn-danger {
    background-color: #dc2626;
    color: white;
  }
  
  .btn-danger:hover {
    background-color: #b91c1c;
  }
  
  .warehouses-container {
    width: 100%;
  }
  
  .warehouses-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
  }
  
  .warehouse-card {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transition: box-shadow 0.3s;
  }
  
  .warehouse-card:hover {
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  }
  
  .warehouse-header {
    padding: 0.75rem 1rem;
    background-color: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  
  .warehouse-status {
    font-size: 0.75rem;
    font-weight: 500;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    background-color: #d1fae5;
    color: #059669;
  }
  
  .warehouse-status.inactive {
    background-color: #f3f4f6;
    color: #6b7280;
  }
  
  .warehouse-actions {
    display: flex;
    gap: 0.5rem;
  }
  
  .action-btn {
    background: none;
    border: none;
    color: #64748b;
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 0.25rem;
    transition: background-color 0.2s, color 0.2s;
  }
  
  .action-btn:hover {
    background-color: #f1f5f9;
    color: #0f172a;
  }
  
  .warehouse-content {
    padding: 1.5rem;
    display: flex;
    gap: 1rem;
    flex: 1;
    cursor: pointer;
  }
  
  .warehouse-icon {
    font-size: 2rem;
    color: #2563eb;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 3rem;
    height: 3rem;
    background-color: #eff6ff;
    border-radius: 0.5rem;
  }
  
  .warehouse-info {
    flex: 1;
  }
  
  .warehouse-name {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0 0 0.5rem 0;
    color: #1e293b;
  }
  
  .warehouse-code {
    font-size: 0.875rem;
    color: #64748b;
    margin-bottom: 0.75rem;
  }
  
  .warehouse-address {
    font-size: 0.875rem;
    color: #334155;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }
  
  .warehouse-footer {
    padding: 0.75rem 1rem;
    border-top: 1px solid #e2e8f0;
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.5rem;
  }
  
  .warehouse-stat {
    text-align: center;
  }
  
  .stat-label {
    font-size: 0.75rem;
    color: #64748b;
    margin-bottom: 0.25rem;
  }
  
  .stat-value {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1e293b;
  }
  
  .empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 0;
    text-align: center;
    color: #64748b;
  }
  
  .empty-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
    color: #cbd5e1;
  }
  
  .empty-state h3 {
    font-size: 1.25rem;
    margin: 0 0 0.5rem 0;
    color: #1e293b;
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
    color: #64748b;
    font-size: 1rem;
  }
  
  .loading-indicator i {
    margin-right: 0.5rem;
  }
  
  /* Modal styles */
  .modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 50;
    display: flex;
    justify-content: center;
    align-items: center;
  }
  
  .modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 50;
  }
  
  .modal-content {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    width: 100%;
    max-width: 600px;
    z-index: 60;
    overflow: hidden;
  }
  
  .modal-sm {
    max-width: 400px;
  }
  
  .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #e2e8f0;
  }
  
  .modal-header h2 {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
    color: #1e293b;
  }
  
  .close-btn {
    background: none;
    border: none;
    color: #64748b;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.25rem;
    border-radius: 0.25rem;
  }
  
  .close-btn:hover {
    background-color: #f1f5f9;
    color: #0f172a;
  }
  
  .modal-body {
    padding: 1.5rem;
  }
  
  .form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
  }
  
  .form-group {
    margin-bottom: 1rem;
  }
  
  .form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #1e293b;
  }
  
  .form-group input,
  .form-group select,
  .form-group textarea {
    width: 100%;
    padding: 0.625rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.375rem;
    font-size: 0.875rem;
  }
  
  .form-group textarea {
    resize: vertical;
  }
  
  .checkbox-group {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }
  
  .checkbox-group input[type="checkbox"] {
    width: auto;
  }
  
  .checkbox-group label {
    margin-bottom: 0;
  }
  
  .form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 1rem;
  }
  
  .text-danger {
    color: #dc2626;
  }
  
  @media (max-width: 768px) {
    .page-actions {
      flex-direction: column;
      align-items: stretch;
    }
    
    .warehouses-grid {
      grid-template-columns: 1fr;
    }
    
    .form-row {
      grid-template-columns: 1fr;
    }
  }
  </style>