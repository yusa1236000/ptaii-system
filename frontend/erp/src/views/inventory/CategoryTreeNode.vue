<!-- src/components/inventory/CategoryTreeNode.vue -->
<template>
    <div class="category-tree-node">
      <div class="node-content">
        <button
          v-if="category.children && category.children.length > 0"
          class="toggle-btn"
          @click="toggleExpanded"
        >
          <i :class="expanded ? 'fas fa-chevron-down' : 'fas fa-chevron-right'"></i>
        </button>
        <span v-else class="toggle-placeholder"></span>
        
        <span class="category-name">{{ category.name }}</span>
        
        <div class="node-actions">
          <button class="action-btn" title="Edit Category" @click="$emit('edit', category)">
            <i class="fas fa-edit"></i>
          </button>
          <button class="action-btn" title="Delete Category" @click="$emit('delete', category)">
            <i class="fas fa-trash"></i>
          </button>
        </div>
      </div>
      
      <transition name="slide">
        <ul v-if="expanded && category.children && category.children.length > 0" class="node-children">
          <li v-for="child in category.children" :key="child.category_id" class="tree-node">
            <CategoryTreeNode 
              :category="child" 
              @edit="$emit('edit', $event)" 
              @delete="$emit('delete', $event)"
            />
          </li>
        </ul>
      </transition>
    </div>
  </template>
  
  <script>
  import { ref } from 'vue';
  
  export default {
    name: 'CategoryTreeNode',
    props: {
      category: {
        type: Object,
        required: true
      }
    },
    emits: ['edit', 'delete'],
    setup() {
      const expanded = ref(false);
      
      const toggleExpanded = () => {
        expanded.value = !expanded.value;
      };
      
      return {
        expanded,
        toggleExpanded
      };
    }
  };
  </script>
  
  <style scoped>
  .category-tree-node {
    margin-bottom: 0.25rem;
  }
  
  .node-content {
    display: flex;
    align-items: center;
    padding: 0.5rem;
    border-radius: 0.25rem;
    transition: background-color 0.2s;
  }
  
  .node-content:hover {
    background-color: #f1f5f9;
  }
  
  .toggle-btn {
    background: none;
    border: none;
    width: 1.5rem;
    height: 1.5rem;
    display: flex;
    justify-content: center;
    align-items: center;
    cursor: pointer;
    color: #64748b;
    margin-right: 0.5rem;
  }
  
  .toggle-placeholder {
    width: 1.5rem;
    margin-right: 0.5rem;
  }
  
  .category-name {
    flex: 1;
    color: #1e293b;
    font-weight: 500;
  }
  
  .node-actions {
    display: flex;
    gap: 0.25rem;
    opacity: 0;
    transition: opacity 0.2s;
  }
  
  .node-content:hover .node-actions {
    opacity: 1;
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
  
  .node-children {
    list-style-type: none;
    padding-left: 1.5rem;
    margin: 0;
    overflow: hidden;
  }
  
  /* Transition effects */
  .slide-enter-active,
  .slide-leave-active {
    transition: max-height 0.3s ease, opacity 0.3s ease;
    max-height: 1000px;
    opacity: 1;
  }
  
  .slide-enter-from,
  .slide-leave-to {
    max-height: 0;
    opacity: 0;
  }
  </style>