<!-- src/views/inventory/ItemCategories.vue -->
<template>
  <div class="item-categories">
    <!-- Search and Filter Section -->
    <SearchFilter
      v-model:value="searchQuery"
      placeholder="Search categories..."
      @search="applyFilters"
      @clear="clearSearch"
    >
      <template #actions>
        <button class="btn btn-primary" @click="openAddCategoryModal">
          <i class="fas fa-plus"></i> Add Category
        </button>
      </template>
    </SearchFilter>

    <!-- Categories Grid -->
    <div class="categories-container">
      <div v-if="isLoading" class="loading-indicator">
        <i class="fas fa-spinner fa-spin"></i> Loading categories...
      </div>

      <div v-else-if="filteredCategories.length === 0" class="empty-state">
        <div class="empty-icon">
          <i class="fas fa-tags"></i>
        </div>
        <h3>No categories found</h3>
        <p>Try adjusting your search or add a new category.</p>
      </div>

      <div v-else class="categories-grid">
        <div 
          v-for="category in filteredCategories" 
          :key="category.category_id" 
          class="category-card"
        >
          <div class="category-header">
            <h3 class="category-name">{{ category.name }}</h3>
            <div class="category-actions">
              <button class="action-btn" title="Edit Category" @click="editCategory(category)">
                <i class="fas fa-edit"></i>
              </button>
              <button class="action-btn" title="Delete Category" @click="confirmDelete(category)">
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </div>
          
          <div class="category-content">
            <p v-if="category.description" class="category-description">
              {{ category.description }}
            </p>
            <p v-else class="category-description empty">
              No description available
            </p>
          </div>
          
          <div class="category-footer">
            <div v-if="category.parentCategory" class="parent-category">
              <span class="label">Parent:</span>
              <span class="value">{{ category.parentCategory.name }}</span>
            </div>
            <div class="items-count">
              <span class="count">{{ category.items?.length || 0 }}</span>
              <span class="label">items</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Add/Edit Category Modal -->
    <div v-if="showCategoryModal" class="modal">
      <div class="modal-backdrop" @click="closeCategoryModal"></div>
      <div class="modal-content">
        <div class="modal-header">
          <h2>{{ isEditMode ? 'Edit Category' : 'Add New Category' }}</h2>
          <button class="close-btn" @click="closeCategoryModal">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="saveCategory">
            <div class="form-group">
              <label for="name">Category Name*</label>
              <input 
                type="text" 
                id="name" 
                v-model="categoryForm.name" 
                required
                class="form-control"
              />
            </div>
            
            <div class="form-group">
              <label for="description">Description</label>
              <textarea 
                id="description" 
                v-model="categoryForm.description" 
                rows="3"
                class="form-control"
              ></textarea>
            </div>
            
            <div class="form-group">
              <label for="parent_category_id">Parent Category</label>
              <select 
                id="parent_category_id" 
                v-model="categoryForm.parent_category_id"
                class="form-control"
              >
                <option value="">-- None --</option>
                <option 
                  v-for="category in availableParentCategories" 
                  :key="category.category_id" 
                  :value="category.category_id"
                >
                  {{ category.name }}
                </option>
              </select>
              <small v-if="isEditMode" class="text-muted">
                Changing the parent category may affect the hierarchy.
              </small>
            </div>
            
            <div class="form-actions">
              <button type="button" class="btn btn-secondary" @click="closeCategoryModal">
                Cancel
              </button>
              <button type="submit" class="btn btn-primary">
                {{ isEditMode ? 'Update Category' : 'Add Category' }}
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
          <p>Are you sure you want to delete <strong>{{ categoryToDelete.name }}</strong>?</p>
          <p v-if="categoryToDelete.items?.length > 0 || categoryToDelete.childCategories?.length > 0" class="text-danger">
            This category has {{ categoryToDelete.items?.length || 0 }} items and 
            {{ categoryToDelete.childCategories?.length || 0 }} child categories. 
            You cannot delete a category that has items or child categories.
          </p>
          <p v-else class="text-danger">
            This action cannot be undone.
          </p>
          
          <div class="form-actions">
            <button type="button" class="btn btn-secondary" @click="closeDeleteModal">
              Cancel
            </button>
            <button 
              type="button" 
              class="btn btn-danger" 
              @click="deleteCategory"
              :disabled="categoryToDelete.items?.length > 0 || categoryToDelete.childCategories?.length > 0"
            >
              Delete Category
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import SearchFilter from '../../components/common/SearchFilter.vue';
import CategoryService from '@/services/CategoryService.js';

export default {
  name: 'ItemCategories',
  components: {
    SearchFilter
  },
  setup() {
    // Data
    const categories = ref([]);
    const isLoading = ref(true);
    const searchQuery = ref('');
    
    // Modals
    const showCategoryModal = ref(false);
    const showDeleteModal = ref(false);
    const isEditMode = ref(false);
    const categoryForm = ref({
      name: '',
      description: '',
      parent_category_id: ''
    });
    const categoryToDelete = ref({});
    
    // Computed
    const filteredCategories = computed(() => {
      if (!searchQuery.value) {
        return categories.value;
      }
      
      const query = searchQuery.value.toLowerCase();
      return categories.value.filter(category => 
        category.name.toLowerCase().includes(query) || 
        (category.description && category.description.toLowerCase().includes(query))
      );
    });
    
    const availableParentCategories = computed(() => {
      if (!isEditMode.value) {
        return categories.value;
      }
      
      // When editing, filter out the current category and its children to avoid circular references
      const categoryId = categoryForm.value.category_id;
      
      // Helper function to get all child category IDs recursively
      const getChildCategoryIds = (parentId) => {
        const childIds = [];
        const childCategories = categories.value.filter(c => c.parent_category_id === parentId);
        
        childCategories.forEach(child => {
          childIds.push(child.category_id);
          childIds.push(...getChildCategoryIds(child.category_id));
        });
        
        return childIds;
      };
      
      const childIds = getChildCategoryIds(categoryId);
      
      return categories.value.filter(category => 
        category.category_id !== categoryId && 
        !childIds.includes(category.category_id)
      );
    });
    
    // Methods
    const fetchCategories = async () => {
      isLoading.value = true;
      
      try {
        // Use the actual API service to fetch categories
        const result = await CategoryService.getCategories();
        categories.value = result.data || [];
        isLoading.value = false;
      } catch (error) {
        console.error('Error fetching categories:', error);
        isLoading.value = false;
        
        // Fallback to dummy data if API call fails
        categories.value = [
          {
            category_id: 1,
            name: 'Electronics',
            description: 'Electronic devices and components',
            parent_category_id: null,
            parentCategory: null,
            items: [
              { item_id: 1, name: 'Laptop Model X' },
              { item_id: 2, name: 'Smartphone Y Pro' }
            ],
            childCategories: [
              { category_id: 2, name: 'Accessories' }
            ]
          },
          {
            category_id: 2,
            name: 'Accessories',
            description: 'Device accessories and peripherals',
            parent_category_id: 1,
            parentCategory: { category_id: 1, name: 'Electronics' },
            items: [
              { item_id: 3, name: 'USB Cable Type-C' },
              { item_id: 5, name: 'Wireless Mouse' },
              { item_id: 6, name: 'Mechanical Keyboard' },
              { item_id: 7, name: 'HDMI Cable 2m' }
            ],
            childCategories: []
          },
          {
            category_id: 3,
            name: 'Furniture',
            description: 'Office and home furniture',
            parent_category_id: null,
            parentCategory: null,
            items: [
              { item_id: 4, name: 'Office Chair' }
            ],
            childCategories: []
          },
          {
            category_id: 4,
            name: 'Office Supplies',
            description: 'Consumable office supplies',
            parent_category_id: null,
            parentCategory: null,
            items: [
              { item_id: 8, name: 'A4 Paper Ream' }
            ],
            childCategories: []
          }
        ];
      }
    };
    
    const applyFilters = () => {
      // Nothing specific to do for filtering categories
    };
    
    const clearSearch = () => {
      searchQuery.value = '';
    };
    
    const openAddCategoryModal = () => {
      isEditMode.value = false;
      categoryForm.value = {
        name: '',
        description: '',
        parent_category_id: ''
      };
      showCategoryModal.value = true;
    };
    
    const editCategory = (category) => {
      isEditMode.value = true;
      categoryForm.value = {
        category_id: category.category_id,
        name: category.name,
        description: category.description || '',
        parent_category_id: category.parent_category_id || ''
      };
      showCategoryModal.value = true;
    };
    
    const closeCategoryModal = () => {
      showCategoryModal.value = false;
    };
    
    const saveCategory = async () => {
      try {
        if (isEditMode.value) {
          // Use actual API service to update category
          await CategoryService.updateCategory(
            categoryForm.value.category_id, 
            categoryForm.value
          );
          
          // Update local state
          const index = categories.value.findIndex(c => c.category_id === categoryForm.value.category_id);
          if (index !== -1) {
            const updatedCategory = {
              ...categories.value[index],
              name: categoryForm.value.name,
              description: categoryForm.value.description,
              parent_category_id: categoryForm.value.parent_category_id || null
            };
            
            // Update parent category reference
            if (categoryForm.value.parent_category_id) {
              const parentCategory = categories.value.find(c => c.category_id === parseInt(categoryForm.value.parent_category_id));
              updatedCategory.parentCategory = parentCategory ? { 
                category_id: parentCategory.category_id, 
                name: parentCategory.name 
              } : null;
            } else {
              updatedCategory.parentCategory = null;
            }
            
            categories.value[index] = updatedCategory;
          }
          
          alert('Category updated successfully!');
        } else {
          // Use actual API service to create category
          const result = await CategoryService.createCategory(categoryForm.value);
          const newCategory = result.data;
          
          // Add the new category to local state
          if (newCategory) {
            // Set parent category reference
            if (newCategory.parent_category_id) {
              const parentCategory = categories.value.find(c => c.category_id === newCategory.parent_category_id);
              newCategory.parentCategory = parentCategory ? { 
                category_id: parentCategory.category_id, 
                name: parentCategory.name 
              } : null;
              
              // Add this category to parent's child categories
              if (parentCategory) {
                const parentIndex = categories.value.findIndex(c => c.category_id === newCategory.parent_category_id);
                if (parentIndex !== -1) {
                  if (!categories.value[parentIndex].childCategories) {
                    categories.value[parentIndex].childCategories = [];
                  }
                  categories.value[parentIndex].childCategories.push({
                    category_id: newCategory.category_id,
                    name: newCategory.name
                  });
                }
              }
            } else {
              newCategory.parentCategory = null;
            }
            
            // Initialize empty arrays
            newCategory.items = [];
            newCategory.childCategories = [];
            
            categories.value.push(newCategory);
          }
          
          alert('Category added successfully!');
        }
        
        closeCategoryModal();
      } catch (error) {
        console.error('Error saving category:', error);
        if (error.response && error.response.data && error.response.data.message) {
          alert(`Error: ${error.response.data.message}`);
        } else {
          alert('An error occurred while saving the category. Please try again.');
        }
      }
    };
    
    const confirmDelete = (category) => {
      categoryToDelete.value = category;
      showDeleteModal.value = true;
    };
    
    const closeDeleteModal = () => {
      showDeleteModal.value = false;
    };
    
    const deleteCategory = async () => {
      try {
        if (categoryToDelete.value.items?.length > 0 || categoryToDelete.value.childCategories?.length > 0) {
          alert('Cannot delete a category that has items or child categories.');
          return;
        }
        
        // Use actual API service to delete category
        await CategoryService.deleteCategory(categoryToDelete.value.category_id);
        
        // Update local state
        categories.value = categories.value.filter(c => c.category_id !== categoryToDelete.value.category_id);
        
        // If this category had a parent, update the parent's childCategories array
        if (categoryToDelete.value.parent_category_id) {
          const parentIndex = categories.value.findIndex(c => c.category_id === categoryToDelete.value.parent_category_id);
          if (parentIndex !== -1) {
            categories.value[parentIndex].childCategories = categories.value[parentIndex].childCategories.filter(
              c => c.category_id !== categoryToDelete.value.category_id
            );
          }
        }
        
        closeDeleteModal();
        alert('Category deleted successfully!');
      } catch (error) {
        console.error('Error deleting category:', error);
        if (error.response && error.response.data && error.response.data.message) {
          alert(`Error: ${error.response.data.message}`);
        } else {
          alert('An error occurred while deleting the category. Please try again.');
        }
      }
    };
    
    // Lifecycle hooks
    onMounted(() => {
      fetchCategories();
    });
    
    return {
      categories,
      isLoading,
      searchQuery,
      filteredCategories,
      availableParentCategories,
      showCategoryModal,
      showDeleteModal,
      isEditMode,
      categoryForm,
      categoryToDelete,
      applyFilters,
      clearSearch,
      openAddCategoryModal,
      editCategory,
      closeCategoryModal,
      saveCategory,
      confirmDelete,
      closeDeleteModal,
      deleteCategory
    };
  }
};
</script>

<style scoped>
.item-categories {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.categories-container {
  width: 100%;
}

.categories-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem;
}

.category-card {
  background-color: white;
  border-radius: 0.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  height: 100%;
  transition: box-shadow 0.2s;
}

.category-card:hover {
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.category-header {
  padding: 1rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #e2e8f0;
}

.category-name {
  margin: 0;
  font-size: 1.125rem;
  font-weight: 600;
  color: #1e293b;
}

.category-actions {
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

.category-content {
  padding: 1rem;
  flex: 1;
}

.category-description {
  margin: 0;
  color: #334155;
  line-height: 1.5;
}

.category-description.empty {
  color: #94a3b8;
  font-style: italic;
}

.category-footer {
  padding: 1rem;
  border-top: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.parent-category {
  font-size: 0.875rem;
  color: #64748b;
}

.parent-category .label {
  margin-right: 0.25rem;
}

.parent-category .value {
  color: #334155;
  font-weight: 500;
}

.items-count {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.items-count .count {
  font-size: 1.25rem;
  font-weight: 600;
  color: #2563eb;
}

.items-count .label {
  font-size: 0.75rem;
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

.text-danger {
  color: #dc2626;
}

.text-muted {
  color: #64748b;
  font-size: 0.875rem;
}

/* Modal styles are already defined in your App.vue or a global CSS file */

@media (max-width: 768px) {
  .categories-grid {
    grid-template-columns: 1fr;
  }
}
</style>