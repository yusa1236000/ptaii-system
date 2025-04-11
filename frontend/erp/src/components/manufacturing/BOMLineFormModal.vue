<!-- src/components/manufacturing/BOMLineFormModal.vue -->
<template>
    <div class="modal">
      <div class="modal-backdrop" @click="$emit('close')"></div>
      <div class="modal-content">
        <div class="modal-header">
          <h2>{{ isEditMode ? 'Edit Component' : 'Add Component' }}</h2>
          <button class="close-btn" @click="$emit('close')">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="submitForm">
            <div class="form-row">
              <div class="form-group">
                <label for="item_id">Item*</label>
                <select 
                  id="item_id" 
                  v-model="form.item_id" 
                  required
                  :class="{ 'is-invalid': errors.item_id }"
                >
                  <option value="">-- Select Item --</option>
                  <option v-for="item in items" :key="item.item_id" :value="item.item_id">
                    {{ item.name }} ({{ item.item_code }})
                  </option>
                </select>
                <span v-if="errors.item_id" class="error-message">{{ errors.item_id }}</span>
              </div>
              <div class="form-group">
                <label for="quantity">Quantity*</label>
                <input 
                  type="number" 
                  id="quantity" 
                  v-model="form.quantity" 
                  min="0.0001"
                  step="0.0001"
                  required
                  :class="{ 'is-invalid': errors.quantity }"
                />
                <span v-if="errors.quantity" class="error-message">{{ errors.quantity }}</span>
              </div>
            </div>
            
            <div class="form-row">
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
              <div class="form-group">
                <label class="checkbox-label">
                  <input 
                    type="checkbox" 
                    id="is_critical" 
                    v-model="form.is_critical"
                  />
                  <span>Critical Component</span>
                </label>
                <div class="help-text">Mark as critical if this component is essential for the BOM</div>
              </div>
            </div>
            
            <div class="form-group">
              <label for="notes">Notes</label>
              <textarea 
                id="notes" 
                v-model="form.notes" 
                rows="3"
                :class="{ 'is-invalid': errors.notes }"
                placeholder="Enter any special instructions or notes about this component"
              ></textarea>
              <span v-if="errors.notes" class="error-message">{{ errors.notes }}</span>
            </div>
            
            <div class="form-actions">
              <button type="button" class="btn btn-secondary" @click="$emit('close')">
                Cancel
              </button>
              <button type="submit" class="btn btn-primary">
                {{ isEditMode ? 'Update Component' : 'Add Component' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </template>
  
  <script>
  import { ref, reactive, onMounted, watch } from 'vue';
  import ItemService from '@/services/ItemService';
  
  export default {
    name: 'BOMLineFormModal',
    props: {
      isEditMode: {
        type: Boolean,
        default: false
      },
      lineData: {
        type: Object,
        required: true
      },
      bomId: {
        type: Number,
        required: true
      }
    },
    emits: ['close', 'save'],
    setup(props, { emit }) {
      const form = reactive({
        line_id: props.lineData.line_id,
        bom_id: props.bomId,
        item_id: props.lineData.item_id || '',
        quantity: props.lineData.quantity || 1,
        uom_id: props.lineData.uom_id || '',
        is_critical: props.lineData.is_critical || false,
        notes: props.lineData.notes || ''
      });
      
      const errors = ref({});
      const items = ref([]);
      const unitOfMeasures = ref([]);
      
      // Watch for prop changes
      watch(() => props.lineData, (newData) => {
        form.line_id = newData.line_id;
        form.bom_id = props.bomId;
        form.item_id = newData.item_id || '';
        form.quantity = newData.quantity || 1;
        form.uom_id = newData.uom_id || '';
        form.is_critical = newData.is_critical || false;
        form.notes = newData.notes || '';
      }, { deep: true });
      
      // Load reference data
      const fetchReferenceData = async () => {
        try {
          // In a real app, these would be API calls
          // const itemsResponse = await ItemService.getItems();
          // items.value = itemsResponse.data || [];
          
          // For demo purposes, we'll use mock data
          items.value = [
            { item_id: 101, name: 'Motherboard X570', item_code: 'MB-X570' },
            { item_id: 102, name: 'CPU Ryzen 7', item_code: 'CPU-R7' },
            { item_id: 103, name: 'RAM DDR4 16GB', item_code: 'RAM-16G' },
            { item_id: 104, name: 'SSD 1TB', item_code: 'SSD-1T' },
            { item_id: 105, name: 'Graphics Card RTX 3070', item_code: 'GPU-3070' },
            { item_id: 106, name: 'Power Supply 750W', item_code: 'PSU-750' },
            { item_id: 107, name: 'Computer Case', item_code: 'CASE-MID' },
            { item_id: 108, name: 'CPU Cooler', item_code: 'COOL-CPU' },
            { item_id: 109, name: 'Case Fan 120mm', item_code: 'FAN-120' }
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
        
        if (!form.item_id) {
          errors.value.item_id = 'Item is required';
        }
        
        if (!form.quantity || form.quantity <= 0) {
          errors.value.quantity = 'Quantity must be greater than zero';
        }
        
        if (!form.uom_id) {
          errors.value.uom_id = 'Unit of Measure is required';
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
        items,
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
    max-width: 700px;
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
  
  .form-group input[type="text"],
  .form-group input[type="number"],
  .form-group select,
  .form-group textarea {
    width: 100%;
    padding: 0.625rem;
    border: 1px solid var(--gray-300);
    border-radius: 0.375rem;
    font-size: 0.875rem;
    transition: border-color 0.2s, box-shadow 0.2s;
  }
  
  .form-group input:focus,
  .form-group select:focus,
  .form-group textarea:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
  }
  
  .form-group textarea {
    resize: vertical;
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
  
  .checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    margin-top: 1.75rem;
  }
  
  .checkbox-label input[type="checkbox"] {
    width: 1rem;
    height: 1rem;
  }
  
  .help-text {
    font-size: 0.75rem;
    color: var(--gray-500);
    margin-top: 0.25rem;
    margin-left: 1.5rem;
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