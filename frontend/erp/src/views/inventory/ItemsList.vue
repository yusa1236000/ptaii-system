<style scoped>
.items-list {
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

.search-filter {
  display: flex;
  flex-grow: 1;
  gap: 1rem;
  align-items: center;
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

.filters {
  display: flex;
  gap: 1rem;
}

.filters select {
  padding: 0.625rem;
  border: 1px solid #e2e8f0;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  background-color: white;
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

.items-table-container {
  background-color: white;
  border-radius: 0.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
}

.data-table th {
  text-align: left;
  padding: 0.75rem 1rem;
  border-bottom: 1px solid #e2e8f0;
  background-color: #f8fafc;
  font-weight: 500;
  color: #64748b;
}

.data-table td {
  padding: 0.75rem 1rem;
  border-bottom: 1px solid #f1f5f9;
  color: #1e293b;
}

.data-table tr:hover td {
  background-color: #f8fafc;
}

.sortable {
  cursor: pointer;
  position: relative;
}

.sortable i {
  margin-left: 0.5rem;
  font-size: 0.75rem;
}

.stock-status {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
  font-size: 0.75rem;
  font-weight: 500;
}

.stock-status.low {
  background-color: #fee2e2;
  color: #dc2626;
}

.stock-status.normal {
  background-color: #d1fae5;
  color: #059669;
}

.stock-status.over {
  background-color: #fef3c7;
  color: #d97706;
}

.actions {
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

.pagination {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 0;
}

.pagination-info {
  color: #64748b;
  font-size: 0.875rem;
}

.pagination-controls {
  display: flex;
  gap: 0.25rem;
}

.pagination-btn {
  display: flex;
  justify-content: center;
  align-items: center;
  width: 2rem;
  height: 2rem;
  border-radius: 0.375rem;
  background: none;
  border: 1px solid #e2e8f0;
  color: #64748b;
  cursor: pointer;
}

.pagination-btn:hover:not(:disabled) {
  background-color: #f1f5f9;
  color: #0f172a;
}

.pagination-btn.active {
  background-color: #2563eb;
  color: white;
  border-color: #2563eb;
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.pagination-ellipsis {
  display: flex;
  justify-content: center;
  align-items: center;
  width: 2rem;
  height: 2rem;
  color: #64748b;
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
  
  .search-filter {
    flex-direction: column;
    align-items: stretch;
  }
  
  .form-row {
    grid-template-columns: 1fr;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
}
</style>
<!-- src/views/inventory/ItemsList.vue -->
<template>
  <div class="items-list">
    <div class="page-actions">
      <div class="search-filter">
        <div class="search-box">
          <i class="fas fa-search search-icon"></i>
          <input 
            type="text" 
            v-model="searchQuery" 
            placeholder="Search items..." 
            @input="handleSearch"
          />
          <button v-if="searchQuery" @click="clearSearch" class="clear-search">
            <i class="fas fa-times"></i>
          </button>
        </div>
        
        <div class="filters">
          <select v-model="categoryFilter" @change="applyFilters">
            <option value="">All Categories</option>
            <option v-for="category in categories" :key="category.category_id" :value="category.category_id">
              {{ category.name }}
            </option>
          </select>
          
          <select v-model="stockStatusFilter" @change="applyFilters">
            <option value="">All Stock Status</option>
            <option value="low_stock">Low Stock</option>
            <option value="normal">Normal Stock</option>
            <option value="over_stock">Over Stock</option>
          </select>
        </div>
      </div>
      
      <button class="btn btn-primary" @click="openAddItemModal">
        <i class="fas fa-plus"></i> Add Item
      </button>
    </div>
    
    <div class="items-table-container">
      <div v-if="isLoading" class="loading-indicator">
        <i class="fas fa-spinner fa-spin"></i> Loading items...
      </div>
      
      <div v-else-if="filteredItems.length === 0" class="empty-state">
        <div class="empty-icon">
          <i class="fas fa-box-open"></i>
        </div>
        <h3>No items found</h3>
        <p>Try adjusting your search or filters, or add a new item.</p>
      </div>
      
      <table v-else class="data-table">
        <thead>
          <tr>
            <th @click="sortBy('item_code')" class="sortable">
              Item Code
              <i v-if="sortColumn === 'item_code'" :class="sortIconClass"></i>
            </th>
            <th @click="sortBy('name')" class="sortable">
              Name
              <i v-if="sortColumn === 'name'" :class="sortIconClass"></i>
            </th>
            <th>Category</th>
            <th>UOM</th>
            <th @click="sortBy('current_stock')" class="sortable">
              Current Stock
              <i v-if="sortColumn === 'current_stock'" :class="sortIconClass"></i>
            </th>
            <th>Min. Stock</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in paginatedItems" :key="item.item_id">
            <td>{{ item.item_code }}</td>
            <td>{{ item.name }}</td>
            <td>{{ item.category ? item.category.name : '-' }}</td>
            <td>{{ item.unitOfMeasure ? item.unitOfMeasure.symbol : '-' }}</td>
            <td>{{ item.current_stock }}</td>
            <td>{{ item.minimum_stock }}</td>
            <td>
              <span class="stock-status" :class="getStockStatusClass(item)">
                {{ getStockStatus(item) }}
              </span>
            </td>
            <td class="actions">
              <button class="action-btn" title="View Details" @click="viewItem(item)">
                <i class="fas fa-eye"></i>
              </button>
              <button class="action-btn" title="Edit Item" @click="editItem(item)">
                <i class="fas fa-edit"></i>
              </button>
              <button class="action-btn" title="Delete Item" @click="confirmDelete(item)">
                <i class="fas fa-trash"></i>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    
    <div class="pagination">
      <div class="pagination-info">
        Showing {{ paginationInfo.from }} to {{ paginationInfo.to }} of {{ filteredItems.length }} items
      </div>
      <div class="pagination-controls">
        <button 
          class="pagination-btn" 
          :disabled="currentPage === 1" 
          @click="goToPage(currentPage - 1)"
        >
          <i class="fas fa-chevron-left"></i>
        </button>
        
        <template v-for="page in displayedPages" :key="page">
          <button 
            v-if="page !== '...'" 
            class="pagination-btn" 
            :class="{ active: page === currentPage }"
            @click="goToPage(page)"
          >
            {{ page }}
          </button>
          <span v-else class="pagination-ellipsis">...</span>
        </template>
        
        <button 
          class="pagination-btn" 
          :disabled="currentPage === totalPages" 
          @click="goToPage(currentPage + 1)"
        >
          <i class="fas fa-chevron-right"></i>
        </button>
      </div>
    </div>
    
    <!-- Add/Edit Item Modal -->
    <div v-if="showItemModal" class="modal">
      <div class="modal-backdrop" @click="closeItemModal"></div>
      <div class="modal-content">
        <div class="modal-header">
          <h2>{{ isEditMode ? 'Edit Item' : 'Add New Item' }}</h2>
          <button class="close-btn" @click="closeItemModal">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="saveItem">
            <div class="form-row">
              <div class="form-group">
                <label for="item_code">Item Code*</label>
                <input 
                  type="text" 
                  id="item_code" 
                  v-model="itemForm.item_code" 
                  required
                  :disabled="isEditMode"
                />
              </div>
              <div class="form-group">
                <label for="name">Name*</label>
                <input 
                  type="text" 
                  id="name" 
                  v-model="itemForm.name" 
                  required
                />
              </div>
            </div>
            
            <div class="form-row">
              <div class="form-group">
                <label for="category_id">Category</label>
                <select id="category_id" v-model="itemForm.category_id">
                  <option value="">-- Select Category --</option>
                  <option v-for="category in categories" :key="category.category_id" :value="category.category_id">
                    {{ category.name }}
                  </option>
                </select>
              </div>
              <div class="form-group">
                <label for="uom_id">Unit of Measure</label>
                <select id="uom_id" v-model="itemForm.uom_id">
                  <option value="">-- Select UOM --</option>
                  <option v-for="uom in unitOfMeasures" :key="uom.uom_id" :value="uom.uom_id">
                    {{ uom.name }} ({{ uom.symbol }})
                  </option>
                </select>
              </div>
            </div>
            
            <div class="form-group">
              <label for="description">Description</label>
              <textarea 
                id="description" 
                v-model="itemForm.description" 
                rows="3"
              ></textarea>
            </div>
            
            <div class="form-row">
              <div class="form-group">
                <label for="minimum_stock">Minimum Stock</label>
                <input 
                  type="number" 
                  id="minimum_stock" 
                  v-model="itemForm.minimum_stock" 
                  min="0"
                  step="0.01"
                />
              </div>
              <div class="form-group">
                <label for="maximum_stock">Maximum Stock</label>
                <input 
                  type="number" 
                  id="maximum_stock" 
                  v-model="itemForm.maximum_stock" 
                  min="0"
                  step="0.01"
                />
              </div>
            </div>
            
            <div class="form-actions">
              <button type="button" class="btn btn-secondary" @click="closeItemModal">
                Cancel
              </button>
              <button type="submit" class="btn btn-primary">
                {{ isEditMode ? 'Update Item' : 'Add Item' }}
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
          <p>Are you sure you want to delete <strong>{{ itemToDelete.name }}</strong>?</p>
          <p class="text-danger">This action cannot be undone.</p>
          
          <div class="form-actions">
            <button type="button" class="btn btn-secondary" @click="closeDeleteModal">
              Cancel
            </button>
            <button type="button" class="btn btn-danger" @click="deleteItem">
              Delete Item
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
//import axios from 'axios';

export default {
  name: 'ItemsList',
  setup() {
    const router = useRouter();
    const items = ref([]);
    const categories = ref([]);
    const unitOfMeasures = ref([]);
    const isLoading = ref(true);
    
    // Search and filtering
    const searchQuery = ref('');
    const categoryFilter = ref('');
    const stockStatusFilter = ref('');
    
    // Sorting
    const sortColumn = ref('item_code');
    const sortDirection = ref('asc');
    
    // Pagination
    const currentPage = ref(1);
    const itemsPerPage = ref(10);
    
    // Modals
    const showItemModal = ref(false);
    const showDeleteModal = ref(false);
    const isEditMode = ref(false);
    const itemForm = ref({
      item_code: '',
      name: '',
      description: '',
      category_id: '',
      uom_id: '',
      minimum_stock: 0,
      maximum_stock: 0
    });
    const itemToDelete = ref({});
    
    // Filtering logic
    const filteredItems = computed(() => {
      let result = [...items.value];
      
      // Apply search filter
      if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(item => 
          item.item_code.toLowerCase().includes(query) || 
          item.name.toLowerCase().includes(query)
        );
      }
      
      // Apply category filter
      if (categoryFilter.value) {
        result = result.filter(item => 
          item.category_id === parseInt(categoryFilter.value)
        );
      }
      
      // Apply stock status filter
      if (stockStatusFilter.value) {
        switch (stockStatusFilter.value) {
          case 'low_stock':
            result = result.filter(item => item.current_stock <= item.minimum_stock);
            break;
          case 'over_stock':
            result = result.filter(item => item.current_stock >= item.maximum_stock);
            break;
          case 'normal':
            result = result.filter(item => 
              item.current_stock > item.minimum_stock && 
              item.current_stock < item.maximum_stock
            );
            break;
        }
      }
      
      // Apply sorting
      result.sort((a, b) => {
        let comparison = 0;
        
        if (a[sortColumn.value] < b[sortColumn.value]) {
          comparison = -1;
        } else if (a[sortColumn.value] > b[sortColumn.value]) {
          comparison = 1;
        }
        
        return sortDirection.value === 'asc' ? comparison : -comparison;
      });
      
      return result;
    });
    
    // Pagination logic
    const totalPages = computed(() => {
      return Math.ceil(filteredItems.value.length / itemsPerPage.value);
    });
    
    const paginatedItems = computed(() => {
      const startIndex = (currentPage.value - 1) * itemsPerPage.value;
      const endIndex = startIndex + itemsPerPage.value;
      return filteredItems.value.slice(startIndex, endIndex);
    });
    
    const paginationInfo = computed(() => {
      const total = filteredItems.value.length;
      const from = total === 0 ? 0 : (currentPage.value - 1) * itemsPerPage.value + 1;
      const to = Math.min(currentPage.value * itemsPerPage.value, total);
      
      return { from, to, total };
    });
    
    const displayedPages = computed(() => {
      const total = totalPages.value;
      const current = currentPage.value;
      const pages = [];
      
      if (total <= 7) {
        // Show all pages
        for (let i = 1; i <= total; i++) {
          pages.push(i);
        }
      } else {
        // Always include first page
        pages.push(1);
        
        // Show ellipsis if current page is more than 3
        if (current > 3) {
          pages.push('...');
        }
        
        // Add pages around current page
        const startPage = Math.max(2, current - 1);
        const endPage = Math.min(total - 1, current + 1);
        
        for (let i = startPage; i <= endPage; i++) {
          pages.push(i);
        }
        
        // Show ellipsis if current page is less than total - 2
        if (current < total - 2) {
          pages.push('...');
        }
        
        // Always include last page
        pages.push(total);
      }
      
      return pages;
    });
    
    const sortIconClass = computed(() => {
      return sortDirection.value === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down';
    });
    
    // Methods
    const fetchItems = async () => {
      isLoading.value = true;
      
      try {
        // In a real app, this would be an API call
        // const response = await axios.get('/api/items');
        // items.value = response.data.data;
        
        // For demo purposes, use dummy data
        setTimeout(() => {
          items.value = [
            {
              item_id: 1,
              item_code: 'ITEM-001',
              name: 'Laptop Model X',
              description: 'High-performance laptop for professionals',
              category_id: 1,
              category: { name: 'Electronics' },
              uom_id: 1,
              unitOfMeasure: { name: 'Each', symbol: 'EA' },
              current_stock: 25,
              minimum_stock: 10,
              maximum_stock: 50
            },
            {
              item_id: 2,
              item_code: 'ITEM-002',
              name: 'Smartphone Y Pro',
              description: 'Latest smartphone with advanced features',
              category_id: 1,
              category: { name: 'Electronics' },
              uom_id: 1,
              unitOfMeasure: { name: 'Each', symbol: 'EA' },
              current_stock: 45,
              minimum_stock: 20,
              maximum_stock: 100
            },
            {
              item_id: 3,
              item_code: 'ITEM-003',
              name: 'USB Cable Type-C',
              description: 'High-quality USB Type-C cables',
              category_id: 2,
              category: { name: 'Accessories' },
              uom_id: 1,
              unitOfMeasure: { name: 'Each', symbol: 'EA' },
              current_stock: 150,
              minimum_stock: 50,
              maximum_stock: 200
            },
            {
              item_id: 4,
              item_code: 'ITEM-004',
              name: 'Office Chair',
              description: 'Ergonomic office chair for work',
              category_id: 3,
              category: { name: 'Furniture' },
              uom_id: 1,
              unitOfMeasure: { name: 'Each', symbol: 'EA' },
              current_stock: 8,
              minimum_stock: 10,
              maximum_stock: 30
            },
            {
              item_id: 5,
              item_code: 'ITEM-005',
              name: 'Wireless Mouse',
              description: 'Bluetooth wireless mouse',
              category_id: 2,
              category: { name: 'Accessories' },
              uom_id: 1,
              unitOfMeasure: { name: 'Each', symbol: 'EA' },
              current_stock: 5,
              minimum_stock: 15,
              maximum_stock: 50
            },
            {
              item_id: 6,
              item_code: 'ITEM-006',
              name: 'Mechanical Keyboard',
              description: 'RGB mechanical keyboard for gaming',
              category_id: 2,
              category: { name: 'Accessories' },
              uom_id: 1,
              unitOfMeasure: { name: 'Each', symbol: 'EA' },
              current_stock: 3,
              minimum_stock: 8,
              maximum_stock: 25
            },
            {
              item_id: 7,
              item_code: 'ITEM-007',
              name: 'HDMI Cable 2m',
              description: 'HDMI 2.1 Cable, 2 meters length',
              category_id: 2,
              category: { name: 'Accessories' },
              uom_id: 1,
              unitOfMeasure: { name: 'Each', symbol: 'EA' },
              current_stock: 12,
              minimum_stock: 20,
              maximum_stock: 50
            },
            {
              item_id: 8,
              item_code: 'ITEM-008',
              name: 'A4 Paper Ream',
              description: '500 sheets of A4 paper',
              category_id: 4,
              category: { name: 'Office Supplies' },
              uom_id: 1,
              unitOfMeasure: { name: 'Each', symbol: 'EA' },
              current_stock: 2,
              minimum_stock: 5,
              maximum_stock: 20
            }
          ];
          
          isLoading.value = false;
        }, 500);
      } catch (error) {
        console.error('Error fetching items:', error);
        isLoading.value = false;
      }
    };
    
    const fetchCategories = async () => {
      try {
        // In a real app, this would be an API call
        // const response = await axios.get('/api/item-categories');
        // categories.value = response.data.data;
        
        // For demo purposes, use dummy data
        categories.value = [
          { category_id: 1, name: 'Electronics' },
          { category_id: 2, name: 'Accessories' },
          { category_id: 3, name: 'Furniture' },
          { category_id: 4, name: 'Office Supplies' }
        ];
      } catch (error) {
        console.error('Error fetching categories:', error);
      }
    };
    
    const fetchUnitOfMeasures = async () => {
      try {
        // In a real app, this would be an API call
        // const response = await axios.get('/api/unit-of-measures');
        // unitOfMeasures.value = response.data.data;
        
        // For demo purposes, use dummy data
        unitOfMeasures.value = [
          { uom_id: 1, name: 'Each', symbol: 'EA' },
          { uom_id: 2, name: 'Kilogram', symbol: 'KG' },
          { uom_id: 3, name: 'Meter', symbol: 'M' },
          { uom_id: 4, name: 'Liter', symbol: 'L' },
          { uom_id: 5, name: 'Pack', symbol: 'PK' }
        ];
      } catch (error) {
        console.error('Error fetching unit of measures:', error);
      }
    };
    
    const getStockStatus = (item) => {
      if (item.current_stock <= item.minimum_stock) {
        return 'Low Stock';
      } else if (item.current_stock >= item.maximum_stock) {
        return 'Over Stock';
      } else {
        return 'Normal';
      }
    };
    
    const getStockStatusClass = (item) => {
      const status = getStockStatus(item);
      switch (status) {
        case 'Low Stock': return 'low';
        case 'Over Stock': return 'over';
        default: return 'normal';
      }
    };
    
    const handleSearch = () => {
      currentPage.value = 1;  // Reset to first page on search
    };
    
    const clearSearch = () => {
      searchQuery.value = '';
      handleSearch();
    };
    
    const applyFilters = () => {
      currentPage.value = 1;  // Reset to first page on filter change
    };
    
    const sortBy = (column) => {
      if (sortColumn.value === column) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
      } else {
        sortColumn.value = column;
        sortDirection.value = 'asc';
      }
    };
    
    const goToPage = (page) => {
      currentPage.value = page;
    };
    
    const openAddItemModal = () => {
      isEditMode.value = false;
      itemForm.value = {
        item_code: '',
        name: '',
        description: '',
        category_id: '',
        uom_id: '',
        minimum_stock: 0,
        maximum_stock: 0
      };
      showItemModal.value = true;
    };
    
    const editItem = (item) => {
      isEditMode.value = true;
      itemForm.value = {
        item_id: item.item_id,
        item_code: item.item_code,
        name: item.name,
        description: item.description || '',
        category_id: item.category_id || '',
        uom_id: item.uom_id || '',
        minimum_stock: item.minimum_stock,
        maximum_stock: item.maximum_stock
      };
      showItemModal.value = true;
    };
    
    const viewItem = (item) => {
      // Navigate to item details page
      router.push(`/items/${item.item_id}`);
    };
    
    const closeItemModal = () => {
      showItemModal.value = false;
    };
    
    const saveItem = async () => {
      try {
        if (isEditMode.value) {
          // In a real app, this would be an API call
          // await axios.put(`/api/items/${itemForm.value.item_id}`, itemForm.value);
          
          // For demo purposes, update the local state
          const index = items.value.findIndex(item => item.item_id === itemForm.value.item_id);
          if (index !== -1) {
            // Update the item with form values
            const updatedItem = {
              ...items.value[index],
              name: itemForm.value.name,
              description: itemForm.value.description,
              category_id: itemForm.value.category_id,
              uom_id: itemForm.value.uom_id,
              minimum_stock: itemForm.value.minimum_stock,
              maximum_stock: itemForm.value.maximum_stock
            };
            
            // Update category and UOM references
            if (itemForm.value.category_id) {
              updatedItem.category = categories.value.find(
                cat => cat.category_id === parseInt(itemForm.value.category_id)
              );
            }
            
            if (itemForm.value.uom_id) {
              updatedItem.unitOfMeasure = unitOfMeasures.value.find(
                uom => uom.uom_id === parseInt(itemForm.value.uom_id)
              );
            }
            
            items.value[index] = updatedItem;
          }
          
          // Show success message
          alert('Item updated successfully!');
        } else {
          // In a real app, this would be an API call
          // const response = await axios.post('/api/items', itemForm.value);
          
          // For demo purposes, add to the local state
          const newItem = {
            item_id: items.value.length + 1,
            item_code: itemForm.value.item_code,
            name: itemForm.value.name,
            description: itemForm.value.description,
            category_id: itemForm.value.category_id ? parseInt(itemForm.value.category_id) : null,
            uom_id: itemForm.value.uom_id ? parseInt(itemForm.value.uom_id) : null,
            current_stock: 0,
            minimum_stock: itemForm.value.minimum_stock,
            maximum_stock: itemForm.value.maximum_stock
          };
          
          // Add category and UOM references
          if (newItem.category_id) {
            newItem.category = categories.value.find(
              cat => cat.category_id === newItem.category_id
            );
          }
          
          if (newItem.uom_id) {
            newItem.unitOfMeasure = unitOfMeasures.value.find(
              uom => uom.uom_id === newItem.uom_id
            );
          }
          
          items.value.push(newItem);
          
          // Show success message
          alert('Item added successfully!');
        }
        
        // Close the modal
        closeItemModal();
      } catch (error) {
        console.error('Error saving item:', error);
        alert('An error occurred while saving the item. Please try again.');
      }
    };
    
    const confirmDelete = (item) => {
      itemToDelete.value = item;
      showDeleteModal.value = true;
    };
    
    const closeDeleteModal = () => {
      showDeleteModal.value = false;
    };
    
    const deleteItem = async () => {
      try {
        // In a real app, this would be an API call
        // await axios.delete(`/api/items/${itemToDelete.value.item_id}`);
        
        // For demo purposes, remove from the local state
        items.value = items.value.filter(item => item.item_id !== itemToDelete.value.item_id);
        
        // Close the modal
        closeDeleteModal();
        
        // Show success message
        alert('Item deleted successfully!');
      } catch (error) {
        console.error('Error deleting item:', error);
        alert('An error occurred while deleting the item. Please try again.');
      }
    };
    
    // Initial data loading
    onMounted(() => {
      fetchItems();
      fetchCategories();
      fetchUnitOfMeasures();
    });
    
    // Reset to page 1 when filtered results change significantly
    watch(filteredItems, (newItems, oldItems) => {
      if (Math.abs(newItems.length - oldItems.length) > itemsPerPage.value) {
        currentPage.value = 1;
      }
    });
    
    return {
      items,
      categories,
      unitOfMeasures,
      isLoading,
      searchQuery,
      categoryFilter,
      stockStatusFilter,
      sortColumn,
      sortDirection,
      currentPage,
      itemsPerPage,
      filteredItems,
      paginatedItems,
      totalPages,
      paginationInfo,
      displayedPages,
      sortIconClass,
      showItemModal,
      showDeleteModal,
      isEditMode,
      itemForm,
      itemToDelete,
      handleSearch,
      clearSearch,
      applyFilters,
      sortBy,
      goToPage,
      getStockStatus,
      getStockStatusClass,
      openAddItemModal,
      editItem,
      viewItem,
      closeItemModal,
      saveItem,
      confirmDelete,
      closeDeleteModal,
      deleteItem
    };
  }
};
</script>