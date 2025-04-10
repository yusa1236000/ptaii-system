<!-- src/components/inventory/CategoryDetail.vue -->
<template>
    <div class="category-detail">
      <div v-if="isLoading" class="loading-indicator">
        <i class="fas fa-spinner fa-spin"></i> Loading category details...
      </div>
      
      <div v-else-if="!category" class="empty-state">
        <div class="empty-icon">
          <i class="fas fa-tag"></i>
        </div>
        <h3>No Category Selected</h3>
        <p>Select a category to view its details or create a new one.</p>
      </div>
      
      <div v-else class="detail-content">
        <div class="detail-header">
          <h2 class="category-name">{{ category.name }}</h2>
          <div class="detail-actions">
            <button class="btn btn-outline" @click="$emit('edit', category)">
              <i class="fas fa-edit"></i> Edit
            </button>
            <button class="btn btn-danger" @click="$emit('delete', category)">
              <i class="fas fa-trash"></i> Delete
            </button>
          </div>
        </div>
        
        <div class="detail-section">
          <h3 class="section-title">Details</h3>
          <div class="info-grid">
            <div class="info-item">
              <div class="info-label">Category ID</div>
              <div class="info-value">{{ category.category_id }}</div>
            </div>
            <div class="info-item">
              <div class="info-label">Parent Category</div>
              <div class="info-value">
                <template v-if="category.parentCategory">
                  {{ category.parentCategory.name }}
                </template>
                <span v-else class="no-data">None</span>
              </div>
            </div>
            <div class="info-item full-width">
              <div class="info-label">Description</div>
              <div class="info-value">
                <template v-if="category.description">
                  {{ category.description }}
                </template>
                <span v-else class="no-data">No description provided</span>
              </div>
            </div>
          </div>
        </div>
        
        <div class="detail-section">
          <h3 class="section-title">
            Child Categories
            <span class="count-badge">{{ category.childCategories ? category.childCategories.length : 0 }}</span>
          </h3>
          <div v-if="!category.childCategories || category.childCategories.length === 0" class="empty-subsection">
            <p>No child categories found.</p>
          </div>
          <ul v-else class="child-categories-list">
            <li v-for="child in category.childCategories" :key="child.category_id" class="child-category-item">
              <span class="child-name">{{ child.name }}</span>
              <div class="item-actions">
                <button class="action-btn" title="View Child Category" @click="$emit('view', child.category_id)">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
            </li>
          </ul>
        </div>
        
        <div class="detail-section">
          <h3 class="section-title">
            Items in this Category
            <span class="count-badge">{{ category.items ? category.items.length : 0 }}</span>
          </h3>
          <div v-if="!category.items || category.items.length === 0" class="empty-subsection">
            <p>No items found in this category.</p>
          </div>
          <ul v-else class="items-list">
            <li v-for="item in category.items" :key="item.item_id" class="item-row">
              <span class="item-name">{{ item.name }}</span>
              <div class="item-actions">
                <button class="action-btn" title="View Item" @click="$emit('viewItem', item.item_id)">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </template>
  
  <script>
  import { ref, watch } from 'vue';
  //import CategoryService from '@/services/CategoryService';
  
  export default {
    name: 'CategoryDetail',
    props: {
      categoryId: {
        type: [Number, null],
        default: null
      }
    },
    emits: ['edit', 'delete', 'view', 'viewItem'],
    setup(props) {
      const category = ref(null);
      const isLoading = ref(false);
      
      const fetchCategoryDetails = async (id) => {
        if (!id) {
          category.value = null;
          return;
        }
        
        isLoading.value = true;
        try {
          // In a real app, this would be an API call
          // const response = await CategoryService.getCategoryById(id);
          // category.value = response.data;
          
          // For demo purposes, use dummy data
          setTimeout(() => {
            if (id === 1) {
              category.value = {
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
              };
            } else if (id === 2) {
              category.value = {
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
              };
            } else if (id === 3) {
              category.value = {
                category_id: 3,
                name: 'Furniture',
                description: 'Office and home furniture',
                parent_category_id: null,
                parentCategory: null,
                items: [
                  { item_id: 4, name: 'Office Chair' }
                ],
                childCategories: []
              };
            } else if (id === 4) {
              category.value = {
                category_id: 4,
                name: 'Office Supplies',
                description: 'Consumable office supplies',
                parent_category_id: null,
                parentCategory: null,
                items: [
                  { item_id: 8, name: 'A4 Paper Ream' }
                ],
                childCategories: []
              };
            } else {
              category.value = null;
            }
            isLoading.value = false;
          }, 500);
        } catch (error) {
          console.error(`Error fetching category details for ID ${id}:`, error);
          category.value = null;
          isLoading.value = false;
        }
      };
      
      watch(
        () => props.categoryId,
        (newId) => {
          fetchCategoryDetails(newId);
        },
        { immediate: true }
      );
      
      return {
        category,
        isLoading
      };
    }
  };
  </script>
  
  <style scoped>
  .category-detail {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    height: 100%;
    display: flex;
    flex-direction: column;
  }
  
  .detail-content {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    padding: 1.5rem;
    flex: 1;
  }
  
  .detail-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
  }
  
  .category-name {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 600;
    color: #1e293b;
  }
  
  .detail-actions {
    display: flex;
    gap: 0.5rem;
  }
  
  .detail-section {
    padding: 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.375rem;
  }
  
  .section-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0 0 1rem 0;
    display: flex;
    align-items: center;
  }
  
  .count-badge {
    background-color: #e2e8f0;
    color: #475569;
    font-size: 0.75rem;
    padding: 0.125rem 0.375rem;
    border-radius: 1rem;
    margin-left: 0.5rem;
  }
  
  .info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
  }
  
  .info-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
  }
  
  .info-item.full-width {
    grid-column: span 2;
  }
  
  .info-label {
    font-size: 0.75rem;
    font-weight: 500;
    color: #64748b;
  }
  
  .info-value {
    color: #1e293b;
  }
  
  .no-data {
    color: #94a3b8;
    font-style: italic;
  }
  
  .empty-subsection {
    text-align: center;
    padding: 1rem;
    color: #64748b;
    font-style: italic;
  }
  
  .child-categories-list,
  .items-list {
    list-style-type: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .child-category-item,
  .item-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem;
    border-radius: 0.25rem;
    background-color: #f8fafc;
    transition: background-color 0.2s;
  }
  
  .child-category-item:hover,
  .item-row:hover {
    background-color: #f1f5f9;
  }
  
  .child-name,
  .item-name {
    font-weight: 500;
    color: #334155;
  }
  
  .item-actions {
    display: flex;
    gap: 0.25rem;
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
    background-color: #e2e8f0;
    color: #0f172a;
  }
  
  .btn {
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: background-color 0.2s, color 0.2s, border-color 0.2s;
  }
  
  .btn-outline {
    background-color: transparent;
    border: 1px solid #2563eb;
    color: #2563eb;
  }
  
  .btn-outline:hover {
    background-color: #eff6ff;
  }
  
  .btn-danger {
    background-color: #fee2e2;
    border: 1px solid #fee2e2;
    color: #dc2626;
  }
  
  .btn-danger:hover {
    background-color: #fecaca;
    border-color: #fecaca;
  }
  
  .loading-indicator {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 2rem;
    color: #64748b;
    flex: 1;
  }
  
  .loading-indicator i {
    margin-right: 0.5rem;
  }
  
  .empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 2rem;
    text-align: center;
    color: #64748b;
    flex: 1;
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
  </style>