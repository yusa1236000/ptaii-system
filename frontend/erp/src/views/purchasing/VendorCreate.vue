<!-- src/views/purchasing/VendorCreate.vue -->
<template>
    <div class="vendor-create-container">
      <div class="page-header">
        <div class="header-left">
          <router-link to="/purchasing/vendors" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Vendors
          </router-link>
          <h1>Create New Vendor</h1>
        </div>
      </div>
  
      <div class="form-container">
        <VendorForm
          :is-submitting="isSubmitting"
          :server-errors="serverErrors"
          @submit="createVendor"
          @cancel="cancel"
        />
      </div>
    </div>
  </template>
  
  <script>
  import { ref } from 'vue';
  import { useRouter } from 'vue-router';
  import VendorService from '@/services/VendorService';
  import VendorForm from '@/components/purchasing/VendorForm.vue';
  
  export default {
    name: 'VendorCreate',
    components: {
      VendorForm
    },
    setup() {
      const router = useRouter();
      const isSubmitting = ref(false);
      const serverErrors = ref({});
  
      const createVendor = async (formData) => {
        isSubmitting.value = true;
        serverErrors.value = {};
  
        try {
          const response = await VendorService.createVendor(formData);
          
          // Navigate to vendor detail page
          router.push(`/purchasing/vendors/${response.data.vendor_id}`);
        } catch (error) {
          console.error('Error creating vendor:', error);
          
          // Handle validation errors
          if (error.response && error.response.status === 422) {
            serverErrors.value = error.response.data.errors || {};
          } else {
            // Handle other errors
            alert('Failed to create vendor. Please try again.');
          }
          
          isSubmitting.value = false;
        }
      };
  
      const cancel = () => {
        router.push('/purchasing/vendors');
      };
  
      return {
        isSubmitting,
        serverErrors,
        createVendor,
        cancel
      };
    }
  };
  </script>
  
  <style scoped>
  .vendor-create-container {
    padding: 1rem;
  }
  
  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1.5rem;
  }
  
  .header-left {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .back-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--gray-600);
    text-decoration: none;
    font-size: 0.875rem;
  }
  
  .back-link:hover {
    color: var(--primary-color);
  }
  
  .header-left h1 {
    margin: 0;
    font-size: 1.5rem;
    color: var(--gray-800);
  }
  
  .form-container {
    max-width: 800px;
    margin: 0 auto;
  }
  </style>