<!-- src/views/inventory/ItemsList.vue -->
<template>
  <div class="items-list">
    <!-- Search and Filter Section -->
    <SearchFilter
      v-model:value="searchQuery"
      placeholder="Search items by code, name or description..."
      @search="applyFilters"
      @clear="clearSearch"
    >
      <template #filters>
        <div class="filter-group">
          <label for="categoryFilter">Category</label>
          <select id="categoryFilter" v-model="categoryFilter" @change="applyFilters">
            <option value="">All Categories</option>
            <option v-for="category in categories" :key="category.category_id" :value="category.category_id">
              {{ category.name }}
            </option>
          </select>
        </div>
        
        <div class="filter-group">
          <label for="stockStatusFilter">Stock Status</label>
          <select id="stockStatusFilter" v-model="stockStatusFilter" @change="applyFilters">
            <option value="">All Stock Status</option>
            <option value="low_stock">Low Stock</option>
            <option value="normal">Normal Stock</option>
            <option value="over_stock">Over Stock</option>
          </select>
        </div>
      </template>
      
      <template #actions>
        <button class="btn btn-primary" @click="openAddItemModal">
          <i class="fas fa-plus"></i> Add Item
        </button>
      </template>
    </SearchFilter>
    
    <!-- Items Table -->
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
    
    <!-- Pagination -->
    <Pagination
      v-if="filteredItems.length > 0"
      :current-page="currentPage"
      :total-pages="totalPages"
      :from="paginationInfo.from"
      :to="paginationInfo.to"
      :total="filteredItems.length"
      @page-changed="goToPage"
    />
    
    <!-- Add/Edit Item Modal -->
    <ItemFormModal
      v-if="showItemModal"
      :is-edit-mode="isEditMode"
      :item-form="itemForm"
      :categories="categories"
      :unit-of-measures="unitOfMeasures"
      @save="saveItem"
      @close="closeItemModal"
    />
    
    <!-- Delete Confirmation Modal -->
    <ConfirmationModal
      v-if="showDeleteModal"
      title="Confirm Delete"
      :message="`Are you sure you want to delete <strong>${itemToDelete.name}</strong>?`"
      confirm-button-text="Delete Item"
      confirm-button-class="btn-danger"
      @confirm="deleteItem"
      @close="closeDeleteModal"
    />
    
    <!-- Item Details Modal -->
    <ItemDetailModal
      v-if="showDetailModal"
      :item="selectedItem"
      @close="closeDetailModal"
      @edit="editItemFromDetail"
    />
  </div>
</template>

<script>
import { ref, computed, onMounted, watch } from 'vue';
import api from '@/services/api';
import SearchFilter from '@/components/common/SearchFilter.vue';
import Pagination from '@/components/common/Pagination.vue';
import ItemFormModal from '@/components/inventory/ItemFormModal.vue';
import ItemDetailModal from '@/components/inventory/ItemDetailModal.vue';
import ConfirmationModal from '@/components/common/ConfirmationModal.vue';

export default {
  name: 'ItemsList',
  components: {
    SearchFilter,
    Pagination,
    ItemFormModal,
    ItemDetailModal,
    ConfirmationModal
  },
  setup() {
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
    const showDetailModal = ref(false);
    const isEditMode = ref(false);
    const itemForm = ref({
      item_id: '',
      item_code: '',
      name: '',
      description: '',
      category_id: '',
      uom_id: '',
      minimum_stock: 0,
      maximum_stock: 0,
      length: '',
      width: '',
      thickness: '',
      weight: '',
      is_purchasable: false,
      is_sellable: false,
      cost_price: 0,
      sale_price: 0
    });
    const itemToDelete = ref({});
    const selectedItem = ref(null);
    
    // Computed properties
    const filteredItems = computed(() => {
      let result = [...items.value];
      
      // Apply search filter
      if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(item => 
          (item.item_code && item.item_code.toLowerCase().includes(query)) || 
          (item.name && item.name.toLowerCase().includes(query)) ||
          (item.description && item.description.toLowerCase().includes(query))
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
    
    const sortIconClass = computed(() => {
      return sortDirection.value === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down';
    });
    
    // Methods
    const fetchItems = async () => {
      isLoading.value = true;
      
      try {
        const response = await api.get('/items');
        items.value = response.data.data;
        // Map unitOfMeasure to each item after fetching unitOfMeasures
        if (unitOfMeasures.value.length > 0) {
          items.value = items.value.map(item => {
            const uom = unitOfMeasures.value.find(u => u.uom_id === item.uom_id);
            return { ...item, unitOfMeasure: uom || null };
          });
        }
      } catch (error) {
        console.error('Error fetching items:', error);
      } finally {
        isLoading.value = false;
      }
    };
    
    const fetchCategories = async () => {
      try {
        const response = await api.get('/item-categories');
        categories.value = response.data.data;
      } catch (error) {
        console.error('Error fetching categories:', error);
      }
    };
    
    const fetchUnitOfMeasures = async () => {
      try {
        const response = await api.get('/unit-of-measures');
        unitOfMeasures.value = response.data.data;
        // Map unitOfMeasure to each item after fetching unitOfMeasures
        if (items.value.length > 0) {
          items.value = items.value.map(item => {
            const uom = unitOfMeasures.value.find(u => u.uom_id === item.uom_id);
            return { ...item, unitOfMeasure: uom || null };
          });
        }
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
    
    const applyFilters = () => {
      currentPage.value = 1;  // Reset to first page on filter change
    };
    
    const clearSearch = () => {
      searchQuery.value = '';
      applyFilters();
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
        maximum_stock: 0,
        length: '',
        width: '',
        thickness: '',
        weight: '',
        is_purchasable: false,
        is_sellable: false,
        cost_price: 0,
        sale_price: 0
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
        maximum_stock: item.maximum_stock,
        length: item.length || '',
        width: item.width || '',
        thickness: item.thickness || '',
        weight: item.weight || '',
        is_purchasable: item.is_purchasable || false,
        is_sellable: item.is_sellable || false,
        cost_price: item.cost_price || 0,
        sale_price: item.sale_price || 0
      };
      showItemModal.value = true;
    };
    
    const editItemFromDetail = (item) => {
      closeDetailModal();
      editItem(item);
    };
    
    const viewItem = (item) => {
      selectedItem.value = item;
      showDetailModal.value = true;
    };
    
    const closeItemModal = () => {
      showItemModal.value = false;
    };
    
    const closeDetailModal = () => {
      showDetailModal.value = false;
      selectedItem.value = null;
    };
    
    const saveItem = async (formData) => {
      try {
        if (isEditMode.value) {
          const itemId = formData.get('item_id');
          await api.post(`/items/${itemId}?_method=PUT`, formData, {
            headers: {
              'Content-Type': 'multipart/form-data'
            }
          });
          
          // Refresh items list
          await fetchItems();
          
          // Show success message
          alert('Item updated successfully!');
        } else {
          await api.post('/items', formData, {
            headers: {
              'Content-Type': 'multipart/form-data'
            }
          });
          
          // Refresh items list
          await fetchItems();
          
          // Show success message
          alert('Item added successfully!');
        }
        
        // Close the modal
        closeItemModal();
      } catch (error) {
        console.error('Error saving item:', error);
        
        if (error.response && error.response.data && error.response.data.errors) {
          alert('Please check the form for errors: ' + Object.values(error.response.data.errors).join(', '));
        } else {
          alert('An error occurred while saving the item. Please try again.');
        }
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
        await api.delete(`/items/${itemToDelete.value.item_id}`);
        
        // Remove item from the list
        items.value = items.value.filter(item => item.item_id !== itemToDelete.value.item_id);
        
        // Close the modal
        closeDeleteModal();
        
        // Show success message
        alert('Item deleted successfully!');
      } catch (error) {
        console.error('Error deleting item:', error);
        
        if (error.response && error.response.status === 422) {
          alert('This item cannot be deleted because it has related transactions or batches.');
        } else {
          alert('An error occurred while deleting the item. Please try again.');
        }
      }
    };
    
    // Watch for changes that should reset pagination
    watch(filteredItems, (newItems, oldItems) => {
      if (Math.abs(newItems.length - oldItems.length) > itemsPerPage.value / 2) {
        currentPage.value = 1;
      }
    });
    
    // Initial data loading
    onMounted(() => {
      fetchItems();
      fetchCategories();
      fetchUnitOfMeasures();
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
      showItemModal,
      showDeleteModal,
      showDetailModal,
      isEditMode,
      itemForm,
      itemToDelete,
      selectedItem,
      sortIconClass,
      getStockStatus,
      getStockStatusClass,
      applyFilters,
      clearSearch,
      sortBy,
      goToPage,
      openAddItemModal,
      editItem,
      editItemFromDetail,
      viewItem,
      closeItemModal,
      closeDetailModal,
      saveItem,
      confirmDelete,
      closeDeleteModal,
      deleteItem
    };
  }
};
</script>

<style scoped>
.items-list {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
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

.empty-state p {
  margin: 0;
  font-size: 0.875rem;
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
  justify-content: flex-end;
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
@media (max-width: 768px) {
  .data-table {
    font-size: 0.75rem;
  }
  
  .data-table th,
  .data-table td {
    padding: 0.5rem;
  }
  
  .actions {
    flex-direction: column;
    gap: 0.25rem;
  }
}
</style>
