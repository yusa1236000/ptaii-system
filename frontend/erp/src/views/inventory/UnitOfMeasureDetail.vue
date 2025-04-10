<!-- src/views/inventory/UnitOfMeasureDetail.vue -->
<template>
    <div class="uom-detail">
      <div v-if="isLoading" class="loading-indicator">
        <i class="fas fa-spinner fa-spin"></i> Loading unit of measure...
      </div>
      
      <div v-else-if="!uom" class="not-found">
        <div class="empty-icon">
          <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h3>Unit of Measure Not Found</h3>
        <p>The requested unit of measure could not be found.</p>
        <router-link to="/uom" class="btn btn-primary">
          <i class="fas fa-arrow-left"></i> Back to Units of Measure
        </router-link>
      </div>
      
      <template v-else>
        <!-- Page Header -->
        <div class="page-header">
          <div class="header-content">
            <h1 class="page-title">{{ uom.name }} <code>{{ uom.symbol }}</code></h1>
            <p v-if="uom.description" class="description">{{ uom.description }}</p>
          </div>
          <div class="header-actions">
            <button class="btn btn-primary" @click="editUom">
              <i class="fas fa-edit"></i> Edit
            </button>
            <button class="btn btn-danger" @click="confirmDelete" :disabled="isUsed">
              <i class="fas fa-trash"></i> Delete
            </button>
          </div>
        </div>
        
        <!-- Details Card -->
        <div class="detail-card">
          <div class="card-header">
            <h2 class="card-title">Unit Details</h2>
          </div>
          <div class="card-body">
            <div class="detail-grid">
              <div class="detail-item">
                <span class="detail-label">ID</span>
                <span class="detail-value">{{ uom.uom_id }}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Name</span>
                <span class="detail-value">{{ uom.name }}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Symbol</span>
                <span class="detail-value"><code>{{ uom.symbol }}</code></span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Description</span>
                <span class="detail-value">{{ uom.description || 'Not provided' }}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Created At</span>
                <span class="detail-value">{{ formatDate(uom.created_at) }}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Last Updated</span>
                <span class="detail-value">{{ formatDate(uom.updated_at) }}</span>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Items Using This UOM -->
        <div class="detail-card">
          <div class="card-header">
            <h2 class="card-title">Items Using This Unit</h2>
            <span class="item-count" :class="{ 'zero': items.length === 0 }">
              {{ items.length }} item(s)
            </span>
          </div>
          <div class="card-body">
            <div v-if="items.length === 0" class="empty-state">
              <p>No items are currently using this unit of measure.</p>
            </div>
            <table v-else class="data-table">
              <thead>
                <tr>
                  <th>Item Code</th>
                  <th>Name</th>
                  <th>Category</th>
                  <th>Current Stock</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in items" :key="item.item_id">
                  <td>{{ item.item_code }}</td>
                  <td>{{ item.name }}</td>
                  <td>{{ item.category ? item.category.name : '-' }}</td>
                  <td>{{ item.current_stock }} {{ uom.symbol }}</td>
                  <td>
                    <router-link :to="`/items/${item.item_id}`" class="action-btn" title="View Item">
                      <i class="fas fa-eye"></i>
                    </router-link>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        
        <!-- Unit Conversions -->
        <div class="detail-card">
          <div class="card-header">
            <h2 class="card-title">Unit Conversions</h2>
            <button class="btn btn-sm btn-primary" @click="openAddConversionModal">
              <i class="fas fa-plus"></i> Add Conversion
            </button>
          </div>
          <div class="card-body">
            <div v-if="conversions.length === 0" class="empty-state">
              <p>No conversions defined for this unit of measure.</p>
              <p class="hint">
                Define conversions to allow automatic conversion between different units.
                <br>For example: 1 kg = 1000 g or 1 m = 100 cm
              </p>
            </div>
            <div v-else class="conversions-list">
              <div v-for="(conversion, index) in conversions" :key="index" class="conversion-card">
                <div class="conversion-formula">
                  <span class="from-unit">1 {{ conversion.from_uom.symbol }}</span>
                  <i class="fas fa-equals"></i>
                  <span class="to-unit">{{ conversion.conversion_factor }} {{ conversion.to_uom.symbol }}</span>
                </div>
                <div class="conversion-actions">
                  <button class="action-btn" title="Edit Conversion" @click="editConversion(conversion)">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button class="action-btn" title="Delete Conversion" @click="deleteConversion(conversion)">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>
      
      <!-- Edit UOM Modal -->
      <div v-if="showUomModal" class="modal">
        <div class="modal-backdrop" @click="closeUomModal"></div>
        <div class="modal-content">
          <div class="modal-header">
            <h2>Edit Unit of Measure</h2>
            <button class="close-btn" @click="closeUomModal">
              <i class="fas fa-times"></i>
            </button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveUom">
              <div class="form-group">
                <label for="name">Name*</label>
                <input 
                  type="text" 
                  id="name" 
                  v-model="uomForm.name" 
                  required
                  maxlength="50"
                />
              </div>
              
              <div class="form-group">
                <label for="symbol">Symbol*</label>
                <input 
                  type="text" 
                  id="symbol" 
                  v-model="uomForm.symbol" 
                  required
                  maxlength="10"
                />
              </div>
              
              <div class="form-group">
                <label for="description">Description</label>
                <textarea 
                  id="description" 
                  v-model="uomForm.description" 
                  rows="3"
                ></textarea>
              </div>
              
              <div class="form-actions">
                <button type="button" class="btn btn-secondary" @click="closeUomModal">
                  Cancel
                </button>
                <button type="submit" class="btn btn-primary">Update</button>
              </div>
            </form>
          </div>
        </div>
      </div>
      
      <!-- Conversion Modal -->
      <div v-if="showConversionModal" class="modal">
        <div class="modal-backdrop" @click="closeConversionModal"></div>
        <div class="modal-content">
          <div class="modal-header">
            <h2>{{ isEditingConversion ? 'Edit' : 'Add' }} Unit Conversion</h2>
            <button class="close-btn" @click="closeConversionModal">
              <i class="fas fa-times"></i>
            </button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveConversion">
              <div class="form-group">
                <label for="from_uom_id">From Unit*</label>
                <select 
                  id="from_uom_id" 
                  v-model="conversionForm.from_uom_id" 
                  required
                  :disabled="isEditingConversion"
                >
                  <option v-for="u in allUoms" :key="u.uom_id" :value="u.uom_id">
                    {{ u.name }} ({{ u.symbol }})
                  </option>
                </select>
              </div>
              
              <div class="form-group">
                <label for="to_uom_id">To Unit*</label>
                <select 
                  id="to_uom_id" 
                  v-model="conversionForm.to_uom_id" 
                  required
                  :disabled="isEditingConversion"
                >
                  <option v-for="u in allUoms" :key="u.uom_id" :value="u.uom_id" 
                    :disabled="u.uom_id === conversionForm.from_uom_id">
                    {{ u.name }} ({{ u.symbol }})
                  </option>
                </select>
              </div>
              
              <div class="form-group">
                <label for="conversion_factor">Conversion Factor*</label>
                <input 
                  type="number" 
                  id="conversion_factor" 
                  v-model="conversionForm.conversion_factor" 
                  required
                  min="0.0001"
                  step="0.0001"
                />
                <small>
                  How many units of the "To Unit" equals 1 unit of the "From Unit".
                  <br>For example: if 1 kg = 1000 g, enter 1000.
                </small>
              </div>
              
              <div class="form-actions">
                <button type="button" class="btn btn-secondary" @click="closeConversionModal">
                  Cancel
                </button>
                <button type="submit" class="btn btn-primary">
                  {{ isEditingConversion ? 'Update' : 'Add' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
      
      <!-- Delete Confirmation Modal -->
      <div v-if="showDeleteModal" class="modal">
        <div class="modal-backdrop" @click="closeDeleteModal"></div>
        <div class="modal-content modal-sm">
          <div class="modal-header">
            <h2>Confirm Delete</h2>
            <button class="close-btn" @click="closeDeleteModal">
              <i class="fas fa-times"></i>
            </button>
          </div>
          <div class="modal-body">
            <p>Are you sure you want to delete the <strong>{{ uom.name }} ({{ uom.symbol }})</strong> unit of measure?</p>
            <p class="text-danger">This action cannot be undone.</p>
            
            <div v-if="isUsed" class="warning-message">
              <i class="fas fa-exclamation-triangle"></i>
              This unit of measure is currently in use and cannot be deleted.
            </div>
            
            <div class="form-actions">
              <button type="button" class="btn btn-secondary" @click="closeDeleteModal">
                Cancel
              </button>
              <button 
                type="button" 
                class="btn btn-danger" 
                @click="deleteUom"
                :disabled="isUsed"
              >
                Delete
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script>
  import { ref, computed, onMounted } from 'vue';
  import { useRoute, useRouter } from 'vue-router';
  import UnitOfMeasureService from '@/services/UnitOfMeasureService';
  // Import UOM Conversion service if available
  
  export default {
    name: 'UnitOfMeasureDetail',
    setup() {
      const route = useRoute();
      const router = useRouter();
      
      // Data
      const uom = ref(null);
      const allUoms = ref([]);
      const items = ref([]);
      const conversions = ref([]);
      const isLoading = ref(true);
      
      // Modals
      const showUomModal = ref(false);
      const showDeleteModal = ref(false);
      const showConversionModal = ref(false);
      const isEditingConversion = ref(false);
      
      // Forms
      const uomForm = ref({
        name: '',
        symbol: '',
        description: ''
      });
      
      const conversionForm = ref({
        from_uom_id: null,
        to_uom_id: null,
        conversion_factor: 1
      });
      
      // Computed
      const isUsed = computed(() => {
        return items.value.length > 0;
      });
      
      // Methods
      const fetchUom = async () => {
        try {
          isLoading.value = true;
          const id = parseInt(route.params.id);
          
          // Get UOM details
          const response = await UnitOfMeasureService.getById(id);
          uom.value = response.data;
          
          // In a real app, you would also fetch:
          // 1. Items using this UOM
          // 2. UOM conversions
          items.value = uom.value.items || [];
          conversions.value = []; // You would fetch these from an API
          
          // Fetch all UOMs for conversion dropdowns
          const allUomsResponse = await UnitOfMeasureService.getAll();
          allUoms.value = allUomsResponse.data;
        } catch (error) {
          console.error('Error fetching unit of measure:', error);
          uom.value = null;
        } finally {
          isLoading.value = false;
        }
      };
      
      const formatDate = (dateString) => {
        if (!dateString) return '-';
        
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
          year: 'numeric',
          month: 'short',
          day: 'numeric',
          hour: '2-digit',
          minute: '2-digit'
        });
      };
      
      const editUom = () => {
        uomForm.value = {
          name: uom.value.name,
          symbol: uom.value.symbol,
          description: uom.value.description || ''
        };
        showUomModal.value = true;
      };
      
      const closeUomModal = () => {
        showUomModal.value = false;
      };
      
      const saveUom = async () => {
        try {
          const response = await UnitOfMeasureService.update(uom.value.uom_id, uomForm.value);
          uom.value = response.data;
          closeUomModal();
          // Success notification could be shown here
        } catch (error) {
          console.error('Error updating unit of measure:', error);
          // Error handling and notification
        }
      };
      
      const confirmDelete = () => {
        showDeleteModal.value = true;
      };
      
      const closeDeleteModal = () => {
        showDeleteModal.value = false;
      };
      
      const deleteUom = async () => {
        if (isUsed.value) return;
        
        try {
          await UnitOfMeasureService.delete(uom.value.uom_id);
          router.push('/uom');
          // Success notification could be shown here
        } catch (error) {
          console.error('Error deleting unit of measure:', error);
          // Error handling and notification
        }
      };
      
      const openAddConversionModal = () => {
        isEditingConversion.value = false;
        conversionForm.value = {
          from_uom_id: uom.value.uom_id,
          to_uom_id: null,
          conversion_factor: 1
        };
        showConversionModal.value = true;
      };
      
      const editConversion = (conversion) => {
        isEditingConversion.value = true;
        conversionForm.value = {
          conversion_id: conversion.conversion_id,
          from_uom_id: conversion.from_uom_id,
          to_uom_id: conversion.to_uom_id,
          conversion_factor: conversion.conversion_factor
        };
        showConversionModal.value = true;
      };
      
      const closeConversionModal = () => {
        showConversionModal.value = false;
      };
      
      const saveConversion = async () => {
        try {
          // This is placeholder logic - you would implement the actual API call
          // Based on isEditingConversion.value
          
          // For demo purposes
          if (isEditingConversion.value) {
            const index = conversions.value.findIndex(
              c => c.conversion_id === conversionForm.value.conversion_id
            );
            
            if (index !== -1) {
              // Update existing conversion in the list
              const updatedConversion = {
                ...conversions.value[index],
                conversion_factor: conversionForm.value.conversion_factor
              };
              conversions.value[index] = updatedConversion;
            }
          } else {
            // Add new conversion to the list
            const fromUom = allUoms.value.find(u => u.uom_id === conversionForm.value.from_uom_id);
            const toUom = allUoms.value.find(u => u.uom_id === conversionForm.value.to_uom_id);
            
            const newConversion = {
              conversion_id: Date.now(), // Temporary ID for demo
              from_uom_id: conversionForm.value.from_uom_id,
              to_uom_id: conversionForm.value.to_uom_id,
              conversion_factor: conversionForm.value.conversion_factor,
              from_uom: fromUom,
              to_uom: toUom
            };
            
            conversions.value.push(newConversion);
          }
          
          closeConversionModal();
          // Success notification could be shown here
        } catch (error) {
          console.error('Error saving conversion:', error);
          // Error handling and notification
        }
      };
      
      const deleteConversion = async (conversion) => {
        try {
          // This is placeholder logic - you would implement the actual API call
          conversions.value = conversions.value.filter(
            c => c.conversion_id !== conversion.conversion_id
          );
          // Success notification could be shown here
        } catch (error) {
          console.error('Error deleting conversion:', error);
          // Error handling and notification
        }
      };
      
      // Load data on component mount
      onMounted(() => {
        fetchUom();
      });
      
      return {
        uom,
        allUoms,
        items,
        conversions,
        isLoading,
        isUsed,
        showUomModal,
        showDeleteModal,
        showConversionModal,
        isEditingConversion,
        uomForm,
        conversionForm,
        formatDate,
        editUom,
        closeUomModal,
        saveUom,
        confirmDelete,
        closeDeleteModal,
        deleteUom,
        openAddConversionModal,
        editConversion,
        closeConversionModal,
        saveConversion,
        deleteConversion
      };
    }
  };
  </script>
  
  <style scoped>
  .uom-detail {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
  }
  
  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1rem;
  }
  
  .header-content {
    flex: 1;
  }
  
  .page-title {
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }
  
  .page-title code {
    background-color: #f1f5f9;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-family: monospace;
    font-size: 1rem;
  }
  
  .description {
    color: #475569;
    margin: 0.5rem 0 0 0;
  }
  
  .header-actions {
    display: flex;
    gap: 0.75rem;
  }
  
  .detail-card {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
  }
  
  .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    background-color: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
  }
  
  .card-title {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0;
    color: #1e293b;
  }
  
  .card-body {
    padding: 1.5rem;
  }
  
  .item-count {
    background-color: #0891b2;
    color: white;
    font-size: 0.75rem;
    font-weight: 500;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
  }
  
  .item-count.zero {
    background-color: #94a3b8;
  }
  
  .detail-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
  }
  
  .detail-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
  }
  
  .detail-label {
    font-size: 0.75rem;
    font-weight: 500;
    color: #64748b;
  }
  
  .detail-value {
    color: #1e293b;
  }
  
  .detail-value code {
    background-color: #f1f5f9;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-family: monospace;
  }
  
  .empty-state {
    text-align: center;
    padding: 1.5rem;
    color: #64748b;
  }
  
  .hint {
    font-size: 0.875rem;
    font-style: italic;
    margin-top: 1rem;
  }
  
  .data-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
  }
  
  .data-table th {
    text-align: left;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #e2e8f0;
    background-color: #f8fafc;
    font-weight: 500;
    color: #64748b;
  }
  
  .data-table td {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #f1f5f9;
    color: #1e293b;
  }
  
  .data-table tr:hover td {
    background-color: #f8fafc;
  }
  
  .conversions-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1rem;
  }
  
  .conversion-card {
    background-color: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    padding: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  
  .conversion-formula {
    display: flex;
    align-items: center;
    gap: 0.75rem;
  }
  
  .conversion-formula i {
    color: #64748b;
  }
  
  .from-unit, .to-unit {
    font-weight: 500;
  }
  
  .conversion-actions {
    display: flex;
    gap: 0.5rem;
  }
  
  .loading-indicator {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 4rem 0;
    color: #64748b;
    font-size: 1rem;
  }
  
  .loading-indicator i {
    margin-right: 0.5rem;
  }
  
  .not-found {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 0;
    text-align: center;
  }
  
  .not-found .empty-icon {
    font-size: 3rem;
    color: #f59e0b;
    margin-bottom: 1rem;
  }
  
  .not-found h3 {
    font-size: 1.25rem;
    margin: 0 0 0.5rem 0;
    color: #1e293b;
  }
  
  .not-found p {
    margin: 0 0 1.5rem 0;
    color: #64748b;
  }
  
  .btn {
    padding: 0.625rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    border-radius: 0.375rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    border: none;
    transition: background-color 0.2s, color 0.2s;
  }
  
  .btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.75rem;
  }
  
  .btn-primary {
    background-color: #2563eb;
    color: white;
  }
  
  .btn-primary:hover:not(:disabled) {
    background-color: #1d4ed8;
  }
  
  .btn-secondary {
    background-color: #e2e8f0;
    color: #1e293b;
  }
  
  .btn-secondary:hover:not(:disabled) {
    background-color: #cbd5e1;
  }
  
  .btn-danger {
    background-color: #dc2626;
    color: white;
  }
  
  .btn-danger:hover:not(:disabled) {
    background-color: #b91c1c;
  }
  
  .btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
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
  
  .action-btn:hover:not(:disabled) {
    background-color: #f1f5f9;
    color: #0f172a;
  }
  
  .action-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }
  
  /* Modal styles */
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
    max-width: 500px;
    z-index: 60;
    overflow: hidden;
  }
  
  .modal-sm {
    max-width: 400px;
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
  
  .form-group {
    margin-bottom: 1.5rem;
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
  
  .form-group small {
    display: block;
    margin-top: 0.25rem;
    color: #64748b;
    font-size: 0.75rem;
  }
  
  .form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 1.5rem;
  }
  
  .text-danger {
    color: #dc2626;
  }
  
  .warning-message {
    background-color: #fef3c7;
    color: #d97706;
    padding: 0.75rem;
    border-radius: 0.375rem;
    margin: 1rem 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }
  
  @media (max-width: 768px) {
    .page-header {
      flex-direction: column;
    }
    
    .header-actions {
      width: 100%;
      justify-content: flex-start;
    }
    
    .detail-grid {
      grid-template-columns: 1fr;
    }
    
    .conversions-list {
      grid-template-columns: 1fr;
    }
  }
  </style>