<!-- src/components/common/SearchFilter.vue -->
<template>
    <div class="search-filter">
      <!-- Search Input -->
      <div class="search-box">
        <i class="fas fa-search search-icon"></i>
        <input 
          type="text" 
          v-model="searchValue" 
          :placeholder="placeholder" 
          @input="onSearch"
        />
        <button v-if="searchValue" @click="clearSearch" class="clear-search">
          <i class="fas fa-times"></i>
        </button>
      </div>
      
      <!-- Filters -->
      <div v-if="hasFilters" class="filters">
        <slot name="filters"></slot>
      </div>
      
      <!-- Actions (e.g. Add button) -->
      <div v-if="hasActions" class="actions">
        <slot name="actions"></slot>
      </div>
    </div>
  </template>
  
  <script>
  export default {
    name: 'SearchFilter',
    props: {
      placeholder: {
        type: String,
        default: 'Cari...'
      },
      value: {
        type: String,
        default: ''
      },
      debounce: {
        type: Number,
        default: 300
      }
    },
    data() {
      return {
        searchValue: this.value,
        debounceTimer: null
      };
    },
    computed: {
      hasFilters() {
        return !!this.$slots.filters;
      },
      hasActions() {
        return !!this.$slots.actions;
      }
    },
    watch: {
      value(newVal) {
        this.searchValue = newVal;
      }
    },
    methods: {
      onSearch() {
        clearTimeout(this.debounceTimer);
        this.debounceTimer = setTimeout(() => {
          this.$emit('update:value', this.searchValue);
          this.$emit('search', this.searchValue);
        }, this.debounce);
      },
      clearSearch() {
        this.searchValue = '';
        this.$emit('update:value', '');
        this.$emit('search', '');
        this.$emit('clear');
      }
    }
  };
  </script>
  
  <style scoped>
  .search-filter {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    align-items: center;
    margin-bottom: 1rem;
  }
  
  .search-box {
    position: relative;
    width: 100%;
    max-width: 320px;
    flex-grow: 1;
  }
  
  .search-icon {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--gray-500);
  }
  
  .search-box input {
    width: 100%;
    padding: 0.625rem 2.25rem;
    border: 1px solid var(--gray-200);
    border-radius: 0.375rem;
    font-size: 0.875rem;
    transition: border-color 0.2s, box-shadow 0.2s;
  }
  
  .search-box input:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
  }
  
  .clear-search {
    position: absolute;
    right: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--gray-500);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.25rem;
    border-radius: 9999px;
  }
  
  .clear-search:hover {
    background-color: var(--gray-100);
    color: var(--gray-700);
  }
  
  .filters {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    align-items: center;
  }
  
  .actions {
    margin-left: auto;
  }
  
  /* Filter Components Styling */
  .filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
  }
  
  .filter-group label {
    font-size: 0.75rem;
    color: var(--gray-500);
    font-weight: 500;
  }
  
  .filter-group select,
  .filter-group input {
    padding: 0.5rem;
    border: 1px solid var(--gray-200);
    border-radius: 0.375rem;
    font-size: 0.875rem;
    background-color: white;
    min-width: 8rem;
  }
  
  .filter-button {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    background-color: var(--gray-100);
    border: 1px solid var(--gray-200);
    border-radius: 0.375rem;
    color: var(--gray-700);
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
  }
  
  .filter-button:hover {
    background-color: var(--gray-200);
  }
  
  .filter-button.active {
    background-color: var(--primary-bg);
    border-color: var(--primary-color);
    color: var(--primary-color);
  }
  
  @media (max-width: 768px) {
    .search-filter {
      flex-direction: column;
      align-items: stretch;
    }
    
    .search-box {
      max-width: none;
    }
    
    .filters {
      flex-direction: column;
      align-items: stretch;
    }
    
    .actions {
      margin-left: 0;
      align-self: flex-end;
    }
  }
  </style>