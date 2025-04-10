<!-- src/views/inventory/ItemCategoriesEnhanced.vue -->
<template>
    <div class="item-categories-enhanced">
      <div class="page-header">
        <h1 class="page-title">Item Categories</h1>
        <button class="btn btn-primary" @click="openAddCategoryModal">
          <i class="fas fa-plus"></i> Add Category
        </button>
      </div>
      
      <div class="search-section">
        <SearchFilter
          v-model:value="searchQuery"
          placeholder="Search categories..."
          @search="applyFilters"
          @clear="clearSearch"
        />
      </div>
      
      <div class="categories-layout">
        <div class="categories-tree-panel">
          <CategoryTree 
            @edit="editCategory" 
            @delete="confirmDelete" 
            @select="selectCategory"
          />
        </div>
        
        <div class="categories-detail-panel">
          <CategoryDetail 
            :category-id="selectedCategoryId" 
            @edit="editCategory" 
            @delete="confirmDelete" 
            @view="selectCategory"
            @viewItem="navigateToItem"
          />
        </div>
      </div>
      
      <!-- Category List for Mobile View -->
      <div class="categories-list-mobile">
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
        
        <div v-else class="categories-list">
          <div 
            v-for="category in filteredCategories" 
            :key="category.category_id" 
            class="category-list-item"
            :class="{ active: selectedCategoryId === category.category_id }"
            @click="selectCategory(category.category_id)"
          >
            <div class="item-content">
              <div class="item-main">
                <h3 class="item-name">{{ category.name }}</h3>
                <span v-if="category.parentCategory" class="item-parent">
                  {{ category.parentCategory.name }}
                </span>
              </div>
              <div class="item-actions">
                <button class="action-btn" title="Edit Category" @click.stop="editCategory(category)">
                  <i class="fas fa-edit"></i>
                </button>
                <button class="action-btn" title="Delete Category" @click.stop="confirmDelete(category)">
                  <i class="fas fa-trash"></i>
                </button>
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
  import { useRouter } from 'vue-router';
  import SearchFilter from '@/components/common/SearchFilter.vue';
  import CategoryTree from '@/components/inventory/CategoryTree.vue';
  import CategoryDetail from '@/components/inventory/CategoryDetail.vue';
  //import CategoryService from '@/services/CategoryService';
  
  export default {
    name: 'ItemCategoriesEnhanced',
    components: {
      SearchFilter,
      CategoryTree,
      CategoryDetail
    },
    setup() {
      const router = useRouter();
      const categories = ref([]);
      const isLoading = ref(true);
      const searchQuery = ref('');
      const selectedCategoryId = ref(null);
      
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
      
      const selectCategory = (categoryId) => {
        selectedCategoryId.value = categoryId;
      };
      
      const navigateToItem = (itemId) => {
        router.push(`/items/${itemId}`);
      };
      
      // Methods
      const fetchCategories = async () => {
        isLoading.value = true;
        
        try {
          // In a real app, this would be an API call
          // const response = await CategoryService.getCategories();
          // categories.value = response.data;
          
          // For demo purposes, use dummy data
          setTimeout(() => {
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
            
            isLoading.value = false;
            
            // If no category is selected yet, select the first one
            if (!selectedCategoryId.value && categories.value.length > 0) {
              selectedCategoryId.value = categories.value[0].category_id;
            }
          }, 500);
        } catch (error) {
          console.error('Error fetching categories:', error);
          isLoading.value = false;
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
            // In a real app, this would be an API call
            // await CategoryService.updateCategory(categoryForm.value.category_id, categoryForm.value);
            
            // For demo purposes, update the local state
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
            // In a real app, this would be an API call
            // const response = await CategoryService.createCategory(categoryForm.value);
            
            // For demo purposes, add to the local state
            const newCategoryId = Math.max(...categories.value.map(c => c.category_id)) + 1;
            const newCategory = {
              category_id: newCategoryId,
              name: categoryForm.value.name,
              description: categoryForm.value.description,
              parent_category_id: categoryForm.value.parent_category_id || null,
              items: [],
              childCategories: []
            };
            
            // Set parent category reference
            if (categoryForm.value.parent_category_id) {
              const parentCategory = categories.value.find(c => c.category_id === parseInt(categoryForm.value.parent_category_id));
              newCategory.parentCategory = parentCategory ? { 
                category_id: parentCategory.category_id, 
                name: parentCategory.name 
              } : null;
              
              // Add this category to parent's child categories
              if (parentCategory) {
                const parentIndex = categories.value.findIndex(c => c.category_id === parseInt(categoryForm.value.parent_category_id));
                if (parentIndex !== -1) {
                  categories.value[parentIndex].childCategories.push({
                    category_id: newCategoryId,
                    name: categoryForm.value.name
                  });
                }
              }
            } else {
              newCategory.parentCategory = null;
            }
            
            categories.value.push(newCategory);
            
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
          
          // In a real app, this would be an API call
          // await CategoryService.deleteCategory(categoryToDelete.value.category_id);
          
          // For demo purposes, remove from the local state
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
          
          // If the deleted category was selected, clear the selection
          if (selectedCategoryId.value === categoryToDelete.value.category_id) {
            selectedCategoryId.value = categories.value.length > 0 ? categories.value[0].category_id : null;
          }
          
          closeDeleteModal();
          alert('Category deleted successfully!');
        } catch (error) {
          console.error('Error deleting category:', error);
          alert('An error occurred while deleting the category. Please try again.');
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
        selectedCategoryId,
        filteredCategories,
        availableParentCategories,
        showCategoryModal,
        showDeleteModal,
        isEditMode,
        categoryForm,
        categoryToDelete,
        selectCategory,
        navigateToItem,
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
  .item-categories-enhanced {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
  }
  
  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
  }
  
  .page-title {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 600;
    color: #1e293b;
  }
  
  .search-section {
    margin-bottom: 1.5rem;
  }
  
  .categories-layout {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 1.5rem;
    height: calc(100vh - 220px);
    min-height: 500px;
  }
  
  .categories-tree-panel,
  .categories-detail-panel {
    height: 100%;
    overflow: auto;
  }
  
  .categories-list-mobile {
    display: none;
  }
  
  /* Mobile list styles */
  .categories-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }
  
  .category-list-item {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    cursor: pointer;
    transition: box-shadow 0.2s, border-color 0.2s;
    border: 2px solid transparent;
  }
  
  .category-list-item:hover {
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  }
  
  .category-list-item.active {
    border-color: #2563eb;
  }
  
  .item-content {
    padding: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  
  .item-main {
    flex: 1;
  }
  
  .item-name {
    margin: 0 0 0.25rem 0;
    font-size: 1rem;
    font-weight: 600;
  }
  
  .item-parent {
    font-size: 0.75rem;
    color: #64748b;
  }
  
  .item-actions {
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
  
  .empty-state p {
    margin: 0;
    font-size: 0.875rem;
  }
  
  .text-danger {
    color: #dc2626;
  }
  
  .text-muted {
    color: #64748b;
    font-size: 0.875rem;
  }
  
  /* Form control styles */
  .form-control {
    width: 100%;
    padding: 0.625rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.375rem;
    font-size: 0.875rem;
  }
  
  .form-control:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
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
  
  .form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 1.5rem;
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
  
  /* Responsive styles */
  @media (max-width: 992px) {
    .categories-layout {
      display: none;
    }
    
    .categories-list-mobile {
      display: block;
    }
  }
  
  @media (max-width: 640px) {
    .page-header {
      flex-direction: column;
      align-items: flex-start;
      gap: 1rem;
    }
    
    .btn {
      width: 100%;
      justify-content: center;
    }
  }
  </style>