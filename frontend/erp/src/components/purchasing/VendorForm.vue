<!-- src/components/purchasing/VendorForm.vue -->
<template>
    <div class="vendor-form">
      <form @submit.prevent="handleSubmit">
        <div class="form-section">
          <h3 class="section-title">Basic Information</h3>
          
          <div class="form-row">
            <div class="form-group">
              <label for="vendor_code">Vendor Code*</label>
              <input 
                type="text" 
                id="vendor_code" 
                v-model="formData.vendor_code" 
                required
                :disabled="isEditMode"
                placeholder="e.g., V001"
              />
              <div v-if="errors.vendor_code" class="error-message">
                {{ errors.vendor_code }}
              </div>
            </div>
            
            <div class="form-group">
              <label for="name">Name*</label>
              <input 
                type="text" 
                id="name" 
                v-model="formData.name" 
                required
                placeholder="Full vendor name"
              />
              <div v-if="errors.name" class="error-message">
                {{ errors.name }}
              </div>
            </div>
          </div>
        </div>
        
        <div class="form-section">
          <h3 class="section-title">Contact Information</h3>
          
          <div class="form-group">
            <label for="address">Address</label>
            <textarea 
              id="address" 
              v-model="formData.address" 
              rows="3"
              placeholder="Street address, city, state, zip code"
            ></textarea>
            <div v-if="errors.address" class="error-message">
              {{ errors.address }}
            </div>
          </div>
          
          <div class="form-row">
            <div class="form-group">
              <label for="contact_person">Contact Person</label>
              <input 
                type="text" 
                id="contact_person" 
                v-model="formData.contact_person" 
                placeholder="Primary contact name"
              />
              <div v-if="errors.contact_person" class="error-message">
                {{ errors.contact_person }}
              </div>
            </div>
            
            <div class="form-group">
              <label for="tax_id">Tax ID</label>
              <input 
                type="text" 
                id="tax_id" 
                v-model="formData.tax_id" 
                placeholder="Tax identification number"
              />
              <div v-if="errors.tax_id" class="error-message">
                {{ errors.tax_id }}
              </div>
            </div>
          </div>
          
          <div class="form-row">
            <div class="form-group">
              <label for="phone">Phone</label>
              <input 
                type="text" 
                id="phone" 
                v-model="formData.phone" 
                placeholder="Phone number"
              />
              <div v-if="errors.phone" class="error-message">
                {{ errors.phone }}
              </div>
            </div>
            
            <div class="form-group">
              <label for="email">Email</label>
              <input 
                type="email" 
                id="email" 
                v-model="formData.email" 
                placeholder="Email address"
              />
              <div v-if="errors.email" class="error-message">
                {{ errors.email }}
              </div>
            </div>
          </div>
        </div>
        
        <div class="form-section">
          <h3 class="section-title">Status</h3>
          
          <div class="form-group">
            <div class="toggle-switch">
              <input 
                type="checkbox" 
                id="is_active" 
                v-model="isActive"
                class="toggle-input" 
              />
              <label for="is_active" class="toggle-label">
                <span class="toggle-inner"></span>
                <span class="toggle-switch-label">{{ isActive ? 'Active' : 'Inactive' }}</span>
              </label>
            </div>
          </div>
        </div>
        
        <div class="form-actions">
          <button type="button" class="btn btn-secondary" @click="cancel">
            Cancel
          </button>
          <button type="submit" class="btn btn-primary" :disabled="isSubmitting">
            {{ isSubmitting ? 'Saving...' : (isEditMode ? 'Update Vendor' : 'Create Vendor') }}
          </button>
        </div>
      </form>
    </div>
  </template>
  
  <script>
  import { ref, watch, onMounted } from 'vue';
  
  export default {
    name: 'VendorForm',
    props: {
      vendor: {
        type: Object,
        default: null
      },
      isEditMode: {
        type: Boolean,
        default: false
      },
      isSubmitting: {
        type: Boolean,
        default: false
      },
      serverErrors: {
        type: Object,
        default: () => ({})
      }
    },
    emits: ['submit', 'cancel'],
    setup(props, { emit }) {
      // Form data
      const formData = ref({
        vendor_code: '',
        name: '',
        address: '',
        tax_id: '',
        contact_person: '',
        phone: '',
        email: '',
        status: 'active'
      });
      
      // Form state
      const isActive = ref(true);
      const errors = ref({});
      
      // Initialize form with vendor data if provided
      const initForm = () => {
        if (props.vendor) {
          formData.value = {
            vendor_code: props.vendor.vendor_code || '',
            name: props.vendor.name || '',
            address: props.vendor.address || '',
            tax_id: props.vendor.tax_id || '',
            contact_person: props.vendor.contact_person || '',
            phone: props.vendor.phone || '',
            email: props.vendor.email || '',
            status: props.vendor.status || 'active'
          };
          
          isActive.value = formData.value.status === 'active';
        }
      };
      
      // Watch for vendor prop changes
      watch(() => props.vendor, () => {
        initForm();
      }, { deep: true });
      
      // Watch for server errors
      watch(() => props.serverErrors, (newErrors) => {
        errors.value = { ...newErrors };
      }, { deep: true });
      
      // Watch active status toggle
      watch(isActive, (newValue) => {
        formData.value.status = newValue ? 'active' : 'inactive';
      });
      
      // Form submission
      const handleSubmit = () => {
        // Basic validation
        const validationErrors = {};
        
        if (!formData.value.vendor_code) {
          validationErrors.vendor_code = 'Vendor code is required';
        }
        
        if (!formData.value.name) {
          validationErrors.name = 'Name is required';
        }
        
        if (formData.value.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.value.email)) {
          validationErrors.email = 'Invalid email format';
        }
        
        // If validation errors exist, update errors and stop submission
        if (Object.keys(validationErrors).length > 0) {
          errors.value = validationErrors;
          return;
        }
        
        // Clear validation errors
        errors.value = {};
        
        // Emit submit event with form data
        emit('submit', { ...formData.value });
      };
      
      // Cancel form
      const cancel = () => {
        emit('cancel');
      };
      
      // Initialize form on component mount
      onMounted(() => {
        initForm();
      });
      
      return {
        formData,
        isActive,
        errors,
        handleSubmit,
        cancel
      };
    }
  };
  </script>
  
  <style scoped>
  .vendor-form {
    max-width: 800px;
    margin: 0 auto;
  }
  
  .form-section {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
  }
  
  .section-title {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0 0 1.25rem 0;
    color: #1e293b;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #e2e8f0;
  }
  
  .form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.25rem;
  }
  
  .form-group {
    margin-bottom: 1.25rem;
  }
  
  .form-group:last-child {
    margin-bottom: 0;
  }
  
  label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #334155;
    font-size: 0.875rem;
  }
  
  input[type="text"],
  input[type="email"],
  textarea,
  select {
    width: 100%;
    padding: 0.625rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    background-color: white;
    transition: border-color 0.2s;
  }
  
  input[type="text"]:focus,
  input[type="email"]:focus,
  textarea:focus,
  select:focus {
    border-color: #2563eb;
    outline: none;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
  }
  
  input[type="text"]:disabled,
  input[type="email"]:disabled,
  textarea:disabled,
  select:disabled {
    background-color: #f8fafc;
    cursor: not-allowed;
  }
  
  textarea {
    resize: vertical;
    min-height: 80px;
  }
  
  .error-message {
    margin-top: 0.375rem;
    font-size: 0.75rem;
    color: #dc2626;
  }
  
  /* Toggle switch styling */
  .toggle-switch {
    position: relative;
    display: inline-block;
  }
  
  .toggle-input {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
  }
  
  .toggle-label {
    display: flex;
    align-items: center;
    cursor: pointer;
  }
  
  .toggle-inner {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 24px;
    background-color: #e5e7eb;
    border-radius: 12px;
    margin-right: 0.75rem;
    transition: background-color 0.2s;
  }
  
  .toggle-inner:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    border-radius: 50%;
    transition: transform 0.2s;
  }
  
  .toggle-input:checked + .toggle-label .toggle-inner {
    background-color: #2563eb;
  }
  
  .toggle-input:checked + .toggle-label .toggle-inner:before {
    transform: translateX(26px);
  }
  
  .toggle-switch-label {
    font-weight: 500;
  }
  
  .form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 1.5rem;
  }
  
  .btn {
    padding: 0.625rem 1.25rem;
    font-size: 0.875rem;
    font-weight: 500;
    border-radius: 0.375rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    border: none;
    transition: background-color 0.2s, color 0.2s;
  }
  
  .btn-primary {
    background-color: #2563eb;
    color: white;
  }
  
  .btn-primary:hover:not(:disabled) {
    background-color: #1d4ed8;
  }
  
  .btn-primary:disabled {
    background-color: #93c5fd;
    cursor: not-allowed;
  }
  
  .btn-secondary {
    background-color: #e2e8f0;
    color: #1e293b;
  }
  
  .btn-secondary:hover {
    background-color: #cbd5e1;
  }
  
  @media (max-width: 768px) {
    .form-row {
      grid-template-columns: 1fr;
    }
  }
  </style>