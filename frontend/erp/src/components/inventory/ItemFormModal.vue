```vue
<!-- src/components/inventory/ItemFormModal.vue -->
<template>
  <div class="modal">
    <div class="modal-backdrop" @click="$emit('close')"></div>
    <div class="modal-content">
      <div class="modal-header">
        <h2>{{ isEditMode ? 'Edit Item' : 'Add New Item' }}</h2>
        <button class="close-btn" @click="$emit('close')">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <div class="modal-body">
        <form @submit.prevent="submitForm">
          <div class="form-row">
            <div class="form-group">
              <label for="item_code">Item Code*</label>
              <input 
                type="text" 
                id="item_code" 
                v-model="form.item_code" 
                required
                :disabled="isEditMode"
                :class="{ 'is-invalid': errors.item_code }"
              />
              <span v-if="errors.item_code" class="error-message">{{ errors.item_code }}</span>
            </div>
            <div class="form-group">
              <label for="name">Name*</label>
              <input 
                type="text" 
                id="name" 
                v-model="form.name" 
                required
                :class="{ 'is-invalid': errors.name }"
              />
              <span v-if="errors.name" class="error-message">{{ errors.name }}</span>
            </div>
          </div>
          
          <div class="form-row">
            <div class="form-group">
              <label for="category_id">Category</label>
              <select 
                id="category_id" 
                v-model="form.category_id"
                :class="{ 'is-invalid': errors.category_id }"
              >
                <option value="">-- Select Category --</option>
                <option v-for="category in categories" :key="category.category_id" :value="category.category_id">
                  {{ category.name }}
                </option>
              </select>
              <span v-if="errors.category_id" class="error-message">{{ errors.category_id }}</span>
            </div>
            <div class="form-group">
              <label for="uom_id">Unit of Measure</label>
              <select 
                id="uom_id" 
                v-model="form.uom_id"
                :class="{ 'is-invalid': errors.uom_id }"
              >
                <option value="">-- Select UOM --</option>
                <option v-for="uom in unitOfMeasures" :key="uom.uom_id" :value="uom.uom_id">
                  {{ uom.name }} ({{ uom.symbol }})
                </option>
              </select>
              <span v-if="errors.uom_id" class="error-message">{{ errors.uom_id }}</span>
            </div>
          </div>
          
          <div class="form-group">
            <label for="description">Description</label>
            <textarea 
              id="description" 
              v-model="form.description" 
              rows="3"
              :class="{ 'is-invalid': errors.description }"
            ></textarea>
            <span v-if="errors.description" class="error-message">{{ errors.description }}</span>
          </div>
          
          <div class="form-row">
            <div class="form-group">
              <label for="minimum_stock">Minimum Stock</label>
              <input 
                type="number" 
                id="minimum_stock" 
                v-model="form.minimum_stock" 
                min="0"
                step="0.01"
                :class="{ 'is-invalid': errors.minimum_stock }"
              />
              <span v-if="errors.minimum_stock" class="error-message">{{ errors.minimum_stock }}</span>
            </div>
            <div class="form-group">
              <label for="maximum_stock">Maximum Stock</label>
              <input 
                type="number" 
                id="maximum_stock" 
                v-model="form.maximum_stock" 
                min="0"
                step="0.01"
                :class="{ 'is-invalid': errors.maximum_stock }"
              />
              <span v-if="errors.maximum_stock" class="error-message">{{ errors.maximum_stock }}</span>
            </div>
          </div>
          
          <div class="form-actions">
            <button type="button" class="btn btn-secondary" @click="$emit('close')">
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
</template>

<script>
import { ref, reactive, watch } from 'vue';

export default {
  name: 'ItemFormModal',
  props: {
    isEditMode: {
      type: Boolean,
      default: false
    },
    itemForm: {
      type: Object,
      required: true
    },
    categories: {
      type: Array,
      default: () => []
    },
    unitOfMeasures: {
      type: Array,
      default: () => []
    }
  },
  emits: ['save', 'close'],
  setup(props, { emit }) {
    const form = reactive({
      item_id: props.itemForm.item_id,
      item_code: props.itemForm.item_code,
      name: props.itemForm.name,
      description: props.itemForm.description,
      category_id: props.itemForm.category_id,
      uom_id: props.itemForm.uom_id,
      minimum_stock: props.itemForm.minimum_stock,
      maximum_stock: props.itemForm.maximum_stock
    });
    
    const errors = ref({});
    
    // Watch for prop changes
    watch(() => props.itemForm, (newForm) => {
      Object.assign(form, newForm);
    }, { deep: true });
    
    // Validate form
    const validateForm = () => {
      errors.value = {};
      
      if (!form.item_code) {
        errors.value.item_code = 'Item code is required';
      }
      
      if (!form.name) {
        errors.value.name = 'Name is required';
      }
      
      if (form.minimum_stock < 0) {
        errors.value.minimum_stock = 'Minimum stock cannot be negative';
      }
      
      if (form.maximum_stock < 0) {
        errors.value.maximum_stock = 'Maximum stock cannot be negative';
      }
      
      if (form.maximum_stock < form.minimum_stock) {
        errors.value.maximum_stock = 'Maximum stock must be greater than minimum stock';
      }
      
      return Object.keys(errors.value).length === 0;
    };
    
    const submitForm = () => {
      if (validateForm()) {
        emit('save', { ...form });
      }
    };
    
    return {
      form,
      errors,
      submitForm
    };
  }
};
</script>

<style scoped>
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

.form-group .is-invalid {
  border-color: #ef4444;
}

.error-message {
  display: block;
  margin-top: 0.25rem;
  font-size: 0.75rem;
  color: #ef4444;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  margin-top: 1.5rem;
}

@media (max-width: 640px) {
  .form-row {
    grid-template-columns: 1fr;
  }
}
</style>