<!-- src/components/inventory/CategoryTree.vue -->
<template>
    <div class="category-tree">
      <div class="tree-header">
        <h3>Category Hierarchy</h3>
        <button class="refresh-btn" @click="fetchCategoryHierarchy" title="Refresh hierarchy">
          <i class="fas fa-sync-alt"></i>
        </button>
      </div>
      
      <div v-if="isLoading" class="loading-indicator">
        <i class="fas fa-spinner fa-spin"></i> Loading...
      </div>
      
      <div v-else-if="categories.length === 0" class="empty-state">
        <p>No categories found.</p>
      </div>
      
      <div v-else class="tree-content">
        <ul class="tree-root">
          <li v-for="category in categories" :key="category.category_id" class="tree-node">
            <CategoryTreeNode 
              :category="category" 
              @edit="$emit('edit', $event)" 
              @delete="$emit('delete', $event)"
            />
          </li>
        </ul>
      </div>
    </div>
  </template>
  
  <script>
  import { ref, onMounted } from 'vue';
  import CategoryTreeNode from './CategoryTreeNode.vue';
  //import CategoryService from '@/services/CategoryService.js';
  
  export default {
    name: 'CategoryTree',
    components: {
      CategoryTreeNode
    },
    emits: ['edit', 'delete'],
    setup() {
      const categories = ref([]);
      const isLoading = ref(true);
      
      const fetchCategoryHierarchy = async () => {
        isLoading.value = true;
        try {
          // In a real app, this would use the service
          // const categoryTree = await CategoryService.getCategoryHierarchy();
          // categories.value = categoryTree;
          
          // For demo purposes, use dummy data
          setTimeout(() => {
            categories.value = [
              {
                category_id: 1,
                name: 'Electronics',
                children: [
                  {
                    category_id: 2,
                    name: 'Accessories',
                    children: []
                  }
                ]
              },
              {
                category_id: 3,
                name: 'Furniture',
                children: []
              },
              {
                category_id: 4,
                name: 'Office Supplies',
                children: []
              }
            ];
            isLoading.value = false;
          }, 500);
        } catch (error) {
          console.error('Error fetching category hierarchy:', error);
          isLoading.value = false;
        }
      };
      
      onMounted(() => {
        fetchCategoryHierarchy();
      });
      
      return {
        categories,
        isLoading,
        fetchCategoryHierarchy
      };
    }
  };
  </script>
  
  <style scoped>
  .category-tree {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
  }
  
  .tree-header {
    padding: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #e2e8f0;
  }
  
  .tree-header h3 {
    margin: 0;
    font-size: 1.125rem;
    color: #1e293b;
  }
  
  .refresh-btn {
    background: none;
    border: none;
    color: #64748b;
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 0.25rem;
    transition: background-color 0.2s, color 0.2s;
  }
  
  .refresh-btn:hover {
    background-color: #f1f5f9;
    color: #0f172a;
  }
  
  .tree-content {
    padding: 1rem;
  }
  
  .tree-root {
    list-style-type: none;
    padding: 0;
    margin: 0;
  }
  
  .tree-node {
    margin-bottom: 0.5rem;
  }
  
  .loading-indicator {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 2rem 0;
    color: #64748b;
  }
  
  .loading-indicator i {
    margin-right: 0.5rem;
  }
  
  .empty-state {
    padding: 2rem 1rem;
    text-align: center;
    color: #64748b;
    font-style: italic;
  }
  </style>