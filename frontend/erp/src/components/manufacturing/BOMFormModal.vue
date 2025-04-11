<!-- src/components/manufacturing/BOMFormModal.vue -->
<template>
    <div class="modal">
      <div class="modal-backdrop" @click="$emit('close')"></div>
      <div class="modal-content">
        <div class="modal-header">
          <h2>{{ isEditMode ? 'Edit BOM' : 'Create New BOM' }}</h2>
          <button class="close-btn" @click="$emit('close')">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="submitForm">
            <div class="form-row">
              <div class="form-group">
                <label for="product_id">Product*</label>
                <select 
                  id="product_id" 
                  v-model="form.product_id" 
                  required
                  :class="{ 'is-invalid': errors.product_id }"
                  :disabled="isEditMode"
                >
                  <option value="">-- Select Product --</option>
                  <option v-for="product in products" :key="product.product_id" :value="product.product_id">
                    {{ product.name }}
                  </option>
                </select>
                <span v-if="errors.product_id" class="error-message">{{ errors.product_id }}</span>
              </div>
              <div class="form-group">
                <label for="bom_code">BOM Code*</label>
                <input 
                  type="text" 
                  id="bom_code" 
                  v-model="form.bom_code" 
                  required
                  :class="{ 'is-invalid': errors.bom_code }"
                  :disabled="isEditMode"
                />
                <span v-if="errors.bom_code" class="error-message">{{ errors.bom_code }}</span>
              </div>
            </div>
            
            <div class="form-row">
              <div class="form-group">
                <label for="revision">Revision*</label>
                <input 
                  type="text" 
                  id="revision" 
                  v-model="form.revision" 
                  required
                  :class="{ 'is-invalid': errors.revision }"
                />
                <span v-if="errors.revision" class="error-message">{{ errors.revision }}</span>
              </div>
              <div class="form-group">
                <label for="effective_date">Effective Date*</label>
                <input 
                  type="date" 
                  id="effective_date" 
                  v-model="form.effective_date" 
                  required
                  :class="{ 'is-invalid': errors.effective_date }"
                />
                <span v-if="errors.effective_date" class="error-message">{{ errors.effective_date }}</span>
              </div>
            </div>
            
            <div class="form-row">
              <div class="form-group">
                <label for="status">Status*</label>
                <select 
                  id="status" 
                  v-model="form.status" 
                  required
                  :class="{ 'is-invalid': errors.status }"
                >
                  <option value="Draft">Draft</option>
                  <option value="Active">Active</option>
                  <option value="Obsolete">Obsolete</option>
                </select>
                <span v-if="errors.status" class="error-message">{{ errors.status }}</span>
              </div>
              <div class="form-group">
                <label for="uom_id">Unit of Measure*</label>
                <select 
                  id="uom_id" 
                  v-model="form.uom_id" 
                  required
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
            
            <div class="form-row">
              <div class="form-group">
                <label for="standard_quantity">Standard Quantity*</label>
                <input 
                  type="number" 
                  id="standard_quantity" 
                  v-model="form.standard_quantity" 
                  min="0"
                  step="0.01"
                  required
                  :class="{ 'is-invalid': errors.standard_quantity }"
                />
                <span v-if="errors.standard_quantity" class="error-message">{{ errors.standard_quantity }}</span>
              </div>
            </div>
            
            <div class="form-actions">
              <button type="button" class="btn btn-secondary" @click="$emit('close')">
                Cancel
              </button>
              <button type="submit" class="btn btn-primary">
                {{ isEditMode ? 'Update BOM' : 'Create BOM' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </template>
  
  <script>
  import { ref, reactive, onMounted, watch } from 'vue';
  
  export default {
    name: 'BOMFormModal',
    props: {
      isEditMode: {
        type: Boolean,
        default: false
      },
      bomData: {
        type: Object,
        required: true
      }
    },
    emits: ['close', 'save'],
    setup(props, { emit }) {
      const form = reactive({
        bom_id: props.bomData.bom_id,
        product_id: props.bomData.product_id || '',
        bom_code: props.bomData.bom_code || '',
        revision: props.bomData.revision || '1.0',
        effective_date: props.bomData.effective_date || new Date().toISOString().split('T')[0],
        status: props.bomData.status || 'Draft',
        standard_quantity: props.bomData.standard_quantity || 1,
        uom_id: props.bomData.uom_id || ''
      });
      
      const errors = ref({});
      const products = ref([]);
      const unitOfMeasures = ref([]);
      
      // Watch for prop changes
      watch(() => props.bomData, (newData) => {
        form.bom_id = newData.bom_id;
        form.product_id = newData.product_id || '';
        form.bom_code = newData.bom_code || '';
        form.revision = newData.revision || '1.0';
        form.effective_date = newData.effective_date || new Date().toISOString().split('T')[0];
        form.status = newData.status || 'Draft';
        form.standard_quantity = newData.standard_quantity || 1;
        form.uom_id = newData.uom_id || '';
      }, { deep: true });
      
      // Load reference data
      const fetchReferenceData = async () => {
        try {
          // In a real app, these would be API calls
          // For demo purposes, we'll use mock data
          
          // Mock products data
          products.value = [
            { product_id: 1, name: 'Laptop Model X' },
            { product_id: 2, name: 'Smartphone Y Pro' },
            { product_id: 3, name: 'Mechanical Keyboard' },
            { product_id: 4, name: 'Office Chair' },
            { product_id: 5, name: 'Smart Watch Z' }
          ];
          
          // Mock UOM data
          unitOfMeasures.value = [
            { uom_id: 1, name: 'Each', symbol: 'EA' },
            { uom_id: 2, name: 'Piece', symbol: 'PC' },
            { uom_id: 3, name: 'Kilogram', symbol: 'KG' },
            { uom_id: 4, name: 'Meter', symbol: 'M' },
            { uom_id: 5, name: 'Liter', symbol: 'L' }
          ];
        } catch (error) {
          console.error('Error fetching reference data:', error);
        }
      };
      
      // Validate form
      const validateForm = () => {
        errors.value = {};
        
        if (!form.product_id) {
          errors.value.product_id = 'Product is required';
        }
        
        if (!form.bom_code) {
          errors.value.bom_code = 'BOM Code is required';
        }
        
        if (!form.revision) {
          errors.value.revision = 'Revision is required';
        }
        
        if (!form.effective_date) {
          errors.value.effective_date = 'Effective Date is required';
        }
        
        if (!form.status) {
          errors.value.status = 'Status is required';
        }
        
        if (!form.uom_id) {
          errors.value.uom_id = 'Unit of Measure is required';
        }
        
        if (form.standard_quantity <= 0) {
          errors.value.standard_quantity = 'Standard Quantity must be greater than zero';
        }
        
        return Object.keys(errors.value).length === 0;
      };
      
      const submitForm = () => {
        if (validateForm()) {
          emit('save', { ...form });
        }
      };
      
      onMounted(() => {
        fetchReferenceData();
      });
      
      return {
        form,
        errors,
        products,
        unitOfMeasures,
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
    max-width: 800px;
    max-height: 90vh;
    overflow-y: auto;
    z-index: 60;
  }
  
  .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--gray-200);
  }
  
  .modal-header h2 {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
    color: var(--gray-800);
  }
  
  .close-btn {
    background: none;
    border: none;
    color: var(--gray-500);
    cursor: pointer;
    font-size: 1.25rem;
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
    color: var(--gray-700);
  }
  
  .form-group input,
  .form-group select {
    width: 100%;
    padding: 0.625rem;
    border: 1px solid var(--gray-300);
    border-radius: 0.375rem;
    font-size: 0.875rem;
    transition: border-color 0.2s, box-shadow 0.2s;
  }
  
  .form-group input:focus,
  .form-group select:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
  }
  
  .form-group .is-invalid {
    border-color: var(--danger-color);
  }
  
  .error-message {
    display: block;
    margin-top: 0.25rem;
    font-size: 0.75rem;
    color: var(--danger-color);
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