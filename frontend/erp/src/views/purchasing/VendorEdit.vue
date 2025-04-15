<!-- src/views/purchasing/VendorEdit.vue -->
<template>
    <div class="vendor-edit-container">
      <div class="page-header">
        <div class="header-left">
          <router-link :to="`/purchasing/vendors/${id}`" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Vendor Details
          </router-link>
          <h1>Edit Vendor</h1>
        </div>
      </div>
      
      <div v-if="isLoading" class="loading-container">
        <div class="loading-spinner">
          <i class="fas fa-spinner fa-spin"></i>
        </div>
        <p>Loading vendor data...</p>
      </div>
      
      <div v-else-if="!vendor" class="error-container">
        <div class="error-icon">
          <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h2>Vendor Not Found</h2>
        <p>The requested vendor could not be found or may have been deleted.</p>
        <router-link to="/purchasing/vendors" class="btn btn-primary">
          Return to Vendors List
        </router-link>
      </div>
      
      <div v-else class="form-container">
        <VendorForm
          :vendor="vendor"
          :is-edit-mode="true"
          :is-submitting="isSubmitting"
          :server-errors="serverErrors"
          @submit="updateVendor"
          @cancel="cancel"
        />
      </div>
    </div>
  </template>
  
  <script>
  import { ref, onMounted } from 'vue';
  import { useRouter } from 'vue-router';
  import VendorService from '@/services/VendorService';
  import VendorForm from '@/components/purchasing/VendorForm.vue';
  
  export default {
    name: 'VendorEdit',
    components: {
      VendorForm
    },
    props: {
      id: {
        type: [Number, String],
        required: true
      }
    },
    setup(props) {
      const router = useRouter();
      const vendor = ref(null);
      const isLoading = ref(true);
      const isSubmitting = ref(false);
      const serverErrors = ref({});
  
      const fetchVendorDetails = async () => {
        isLoading.value = true;
        try {
          const response = await VendorService.getVendorById(props.id);
          vendor.value = response.data || null;
        } catch (error) {
          console.error('Error fetching vendor details:', error);
          vendor.value = null;
        } finally {
          isLoading.value = false;
        }
      };
  
      const updateVendor = async (formData) => {
        isSubmitting.value = true;
        serverErrors.value = {};
  
        try {
          await VendorService.updateVendor(props.id, formData);
          
          // Navigate back to vendor detail page
          router.push(`/purchasing/vendors/${props.id}`);
        } catch (error) {
          console.error('Error updating vendor:', error);
          
          // Handle validation errors
          if (error.response && error.response.status === 422) {
            serverErrors.value = error.response.data.errors || {};
          } else {
            // Handle other errors
            alert('Failed to update vendor. Please try again.');
          }
          
          isSubmitting.value = false;
        }
      };
  
      const cancel = () => {
        router.push(`/purchasing/vendors/${props.id}`);
      };
  
      onMounted(() => {
        fetchVendorDetails();
      });
  
      return {
        vendor,
        isLoading,
        isSubmitting,
        serverErrors,
        updateVendor,
        cancel
      };
    }
  };
  </script>
  
  <style scoped>
  .vendor-edit-container {
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
  
  .loading-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 2rem;
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  }
  
  .loading-spinner {
    font-size: 2rem;
    color: var(--primary-color);
    margin-bottom: 1rem;
  }
  
  .error-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 2rem;
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    text-align: center;
  }
  
  .error-icon {
    font-size: 3rem;
    color: var(--danger-color);
    margin-bottom: 1rem;
  }
  
  .form-container {
    max-width: 800px;
    margin: 0 auto;
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
    background-color: var(--primary-color);
    color: white;
  }
  
  .btn-primary:hover {
    background-color: var(--primary-dark);
  }
  </style>