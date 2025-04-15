<!-- src/views/purchasing/VendorDetail.vue -->
<template>
    <div class="vendor-detail-container">
      <div class="page-header">
        <div class="header-left">
          <router-link to="/purchasing/vendors" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Vendors
          </router-link>
          <h1>{{ vendor?.name || 'Vendor Details' }}</h1>
        </div>
        <div class="header-actions">
          <button @click="editVendor" class="btn btn-primary">
            <i class="fas fa-edit"></i> Edit
          </button>
          <button @click="confirmDelete" class="btn btn-danger">
            <i class="fas fa-trash"></i> Delete
          </button>
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
  
      <div v-else class="vendor-detail-content">
        <!-- Basic Information Card -->
        <div class="detail-card">
          <div class="card-header">
            <h2 class="card-title">Basic Information</h2>
            <span :class="['status-badge', getStatusClass(vendor.status)]">
              {{ vendor.status === 'active' ? 'Active' : 'Inactive' }}
            </span>
          </div>
          <div class="card-body">
            <div class="info-grid">
              <div class="info-item">
                <span class="info-label">Vendor Code</span>
                <span class="info-value">{{ vendor.vendor_code }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">Name</span>
                <span class="info-value">{{ vendor.name }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">Tax ID</span>
                <span class="info-value">{{ vendor.tax_id || 'Not specified' }}</span>
              </div>
            </div>
          </div>
        </div>
  
        <!-- Contact Information Card -->
        <div class="detail-card">
          <div class="card-header">
            <h2 class="card-title">Contact Information</h2>
          </div>
          <div class="card-body">
            <div class="info-grid">
              <div class="info-item">
                <span class="info-label">Contact Person</span>
                <span class="info-value">{{ vendor.contact_person || 'Not specified' }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">Email</span>
                <span class="info-value">{{ vendor.email || 'Not specified' }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">Phone</span>
                <span class="info-value">{{ vendor.phone || 'Not specified' }}</span>
              </div>
              <div class="info-item info-item-full">
                <span class="info-label">Address</span>
                <span class="info-value">{{ vendor.address || 'Not specified' }}</span>
              </div>
            </div>
          </div>
        </div>
  
        <!-- Tabs for Related Information -->
        <div class="tabs-container">
          <div class="tabs-header">
            <button 
              v-for="tab in tabs" 
              :key="tab.id" 
              :class="['tab-button', { active: activeTab === tab.id }]"
              @click="activeTab = tab.id"
            >
              {{ tab.name }}
            </button>
          </div>
          
          <div class="tab-content">
            <!-- Purchase Orders Tab -->
            <div v-if="activeTab === 'orders'" class="tab-pane">
              <div v-if="isLoadingOrders" class="loading-indicator">
                <i class="fas fa-spinner fa-spin"></i> Loading purchase orders...
              </div>
              <div v-else-if="purchaseOrders.length === 0" class="empty-state">
                <div class="empty-icon">
                  <i class="fas fa-file-invoice"></i>
                </div>
                <h3>No Purchase Orders</h3>
                <p>There are no purchase orders associated with this vendor yet.</p>
              </div>
              <div v-else>
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>PO Number</th>
                      <th>Date</th>
                      <th>Expected Delivery</th>
                      <th>Status</th>
                      <th>Total Amount</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="order in purchaseOrders" :key="order.po_id">
                      <td>{{ order.po_number }}</td>
                      <td>{{ formatDate(order.po_date) }}</td>
                      <td>{{ formatDate(order.expected_delivery) }}</td>
                      <td>
                        <span :class="['status-badge', getOrderStatusClass(order.status)]">
                          {{ order.status }}
                        </span>
                      </td>
                      <td>{{ formatCurrency(order.total_amount) }}</td>
                      <td>
                        <router-link :to="`/purchasing/orders/${order.po_id}`" class="action-btn view-btn">
                          <i class="fas fa-eye"></i>
                        </router-link>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            
            <!-- Evaluations Tab -->
            <div v-if="activeTab === 'evaluations'" class="tab-pane">
              <div v-if="isLoadingEvaluations" class="loading-indicator">
                <i class="fas fa-spinner fa-spin"></i> Loading evaluations...
              </div>
              <div v-else-if="vendorEvaluations.length === 0" class="empty-state">
                <div class="empty-icon">
                  <i class="fas fa-star"></i>
                </div>
                <h3>No Evaluations</h3>
                <p>There are no evaluations for this vendor yet.</p>
              </div>
              <div v-else>
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>Date</th>
                      <th>Quality Score</th>
                      <th>Delivery Score</th>
                      <th>Price Score</th>
                      <th>Service Score</th>
                      <th>Total Score</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="evaluation in vendorEvaluations" :key="evaluation.evaluation_id">
                      <td>{{ formatDate(evaluation.evaluation_date) }}</td>
                      <td>
                        <div class="score-bar">
                          <div class="score-fill" :style="{ width: `${evaluation.quality_score * 20}%` }"></div>
                          <span class="score-text">{{ evaluation.quality_score }}/5</span>
                        </div>
                      </td>
                      <td>
                        <div class="score-bar">
                          <div class="score-fill" :style="{ width: `${evaluation.delivery_score * 20}%` }"></div>
                          <span class="score-text">{{ evaluation.delivery_score }}/5</span>
                        </div>
                      </td>
                      <td>
                        <div class="score-bar">
                          <div class="score-fill" :style="{ width: `${evaluation.price_score * 20}%` }"></div>
                          <span class="score-text">{{ evaluation.price_score }}/5</span>
                        </div>
                      </td>
                      <td>
                        <div class="score-bar">
                          <div class="score-fill" :style="{ width: `${evaluation.service_score * 20}%` }"></div>
                          <span class="score-text">{{ evaluation.service_score }}/5</span>
                        </div>
                      </td>
                      <td>
                        <div class="score-bar">
                          <div class="score-fill" :style="{ width: `${evaluation.total_score * 20}%` }"></div>
                          <span class="score-text">{{ evaluation.total_score }}/5</span>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
  
      <!-- Confirmation Modal for Delete -->
      <ConfirmationModal
        v-if="showDeleteModal"
        :title="'Delete Vendor'"
        :message="'Are you sure you want to delete vendor <strong>' + (vendor?.name || '') + '</strong>? This action cannot be undone.'"
        :confirm-button-text="'Delete'"
        :confirm-button-class="'btn btn-danger'"
        @confirm="deleteVendor"
        @close="closeDeleteModal"
      />
    </div>
  </template>
  
  <script>
  import { ref, onMounted } from 'vue';
  import { useRouter } from 'vue-router';
  import VendorService from '@/services/VendorService';
  
  export default {
    name: 'VendorDetail',
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
      const showDeleteModal = ref(false);
      const activeTab = ref('orders');
      
      // Purchase orders
      const purchaseOrders = ref([]);
      const isLoadingOrders = ref(false);
      
      // Vendor evaluations
      const vendorEvaluations = ref([]);
      const isLoadingEvaluations = ref(false);
      
      // Available tabs
      const tabs = [
        { id: 'orders', name: 'Purchase Orders' },
        { id: 'evaluations', name: 'Evaluations' }
      ];
      
      // Fetch vendor details
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
      
      // Fetch vendor purchase orders
      const fetchPurchaseOrders = async () => {
        if (!props.id) return;
        
        isLoadingOrders.value = true;
        try {
          const response = await VendorService.getVendorPurchaseOrders(props.id);
          purchaseOrders.value = response.data || [];
        } catch (error) {
          console.error('Error fetching vendor purchase orders:', error);
          purchaseOrders.value = [];
        } finally {
          isLoadingOrders.value = false;
        }
      };
      
      // Fetch vendor evaluations
      const fetchVendorEvaluations = async () => {
        if (!props.id) return;
        
        isLoadingEvaluations.value = true;
        try {
          const response = await VendorService.getVendorEvaluations(props.id);
          vendorEvaluations.value = response.data || [];
        } catch (error) {
          console.error('Error fetching vendor evaluations:', error);
          vendorEvaluations.value = [];
        } finally {
          isLoadingEvaluations.value = false;
        }
      };
      
      // Format date strings
      const formatDate = (dateString) => {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
          year: 'numeric',
          month: 'short',
          day: '2-digit'
        });
      };
      
      // Format currency
      const formatCurrency = (amount) => {
        if (amount === null || amount === undefined) return 'N/A';
        return new Intl.NumberFormat('en-US', {
          style: 'currency',
          currency: 'USD'
        }).format(amount);
      };
      
      // Get CSS class for status badge
      const getStatusClass = (status) => {
        return status === 'active' ? 'status-active' : 'status-inactive';
      };
      
      // Get CSS class for order status badge
      const getOrderStatusClass = (status) => {
        switch (status) {
          case 'draft': return 'status-draft';
          case 'submitted': return 'status-pending';
          case 'approved': return 'status-approved';
          case 'sent': return 'status-sent';
          case 'partial': return 'status-partial';
          case 'received': return 'status-received';
          case 'completed': return 'status-completed';
          case 'canceled': return 'status-canceled';
          default: return 'status-draft';
        }
      };
      
      // Navigation actions
      const editVendor = () => {
        router.push(`/purchasing/vendors/${props.id}/edit`);
      };
      
      const confirmDelete = () => {
        showDeleteModal.value = true;
      };
      
      const closeDeleteModal = () => {
        showDeleteModal.value = false;
      };
      
      const deleteVendor = async () => {
        try {
          await VendorService.deleteVendor(props.id);
          router.push('/purchasing/vendors');
        } catch (error) {
          if (error.response && error.response.status === 400) {
            // Show error message if vendor has related records
            alert(error.response.data.message || 'This vendor cannot be deleted because it has related records.');
          } else {
            console.error('Error deleting vendor:', error);
          }
          closeDeleteModal();
        }
      };
      
      // Initialize data
      onMounted(() => {
        fetchVendorDetails();
        fetchPurchaseOrders();
        fetchVendorEvaluations();
      });
      
      return {
        vendor,
        isLoading,
        showDeleteModal,
        activeTab,
        tabs,
        purchaseOrders,
        isLoadingOrders,
        vendorEvaluations,
        isLoadingEvaluations,
        formatDate,
        formatCurrency,
        getStatusClass,
        getOrderStatusClass,
        editVendor,
        confirmDelete,
        closeDeleteModal,
        deleteVendor
      };
    }
  };
  </script>
  
  <style scoped>
  .vendor-detail-container {
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
  
  .header-actions {
    display: flex;
    gap: 0.75rem;
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
  
  .vendor-detail-content {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
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
    background-color: var(--gray-50);
    border-bottom: 1px solid var(--gray-200);
  }
  
  .card-title {
    margin: 0;
    font-size: 1.25rem;
    color: var(--gray-800);
  }
  
  .card-body {
    padding: 1.5rem;
  }
  
  .info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
  }
  
  .info-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
  }
  
  .info-item-full {
    grid-column: 1 / -1;
  }
  
  .info-label {
    font-size: 0.875rem;
    color: var(--gray-500);
  }
  
  .info-value {
    font-size: 1rem;
    color: var(--gray-800);
    font-weight: 500;
  }
  
  .status-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
  }
  
  .status-active {
    background-color: var(--success-bg);
    color: var(--success-color);
  }
  
  .status-inactive {
    background-color: var(--gray-100);
    color: var(--gray-700);
  }
  
  .status-draft {
    background-color: var(--gray-100);
    color: var(--gray-700);
  }
  
  .status-pending {
    background-color: #fef3c7;
    color: #92400e;
  }
  
  .status-approved {
    background-color: #dcfce7;
    color: #166534;
  }
  
  .status-sent {
    background-color: #dbeafe;
    color: #1e40af;
  }
  
  .status-partial {
    background-color: #fef9c3;
    color: #854d0e;
  }
  
  .status-received {
    background-color: #d1fae5;
    color: #065f46;
  }
  
  .status-completed {
    background-color: #bbf7d0;
    color: #15803d;
  }
  
  .status-canceled {
    background-color: #fee2e2;
    color: #b91c1c;
  }
  
  .tabs-container {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
  }
  
  .tabs-header {
    display: flex;
    border-bottom: 1px solid var(--gray-200);
    background-color: var(--gray-50);
  }
  
  .tab-button {
    padding: 1rem 1.5rem;
    border: none;
    background: none;
    color: var(--gray-600);
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    border-bottom: 2px solid transparent;
  }
  
  .tab-button:hover {
    color: var(--gray-800);
  }
  
  .tab-button.active {
    color: var(--primary-color);
    border-bottom: 2px solid var(--primary-color);
  }
  
  .tab-content {
    min-height: 300px;
  }
  
  .tab-pane {
    padding: 1.5rem;
  }
  
  .data-table {
    width: 100%;
    border-collapse: collapse;
  }
  
  .data-table th {
    text-align: left;
    padding: 0.75rem 1rem;
    background-color: var(--gray-50);
    font-weight: 500;
    font-size: 0.875rem;
    color: var(--gray-700);
    border-bottom: 1px solid var(--gray-200);
  }
  
  .data-table td {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--gray-100);
    font-size: 0.875rem;
  }
  
  .data-table tr:last-child td {
    border-bottom: none;
  }
  
  .empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem 2rem;
    text-align: center;
  }
  
  .empty-icon {
    font-size: 2.5rem;
    color: var(--gray-300);
    margin-bottom: 1rem;
  }
  
  .empty-state h3 {
    font-size: 1.125rem;
    color: var(--gray-700);
    margin-bottom: 0.5rem;
  }
  
  .empty-state p {
    color: var(--gray-500);
    max-width: 24rem;
  }
  
  .loading-indicator {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 2rem;
    color: var(--gray-500);
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
  
  .btn-danger {
    background-color: var(--danger-color);
    color: white;
  }
  
  .btn-danger:hover {
    background-color: #b91c1c;
  }
  
  .action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border-radius: 0.375rem;
    color: var(--primary-color);
    transition: background-color 0.2s;
  }
  
  .action-btn:hover {
    background-color: var(--primary-bg);
  }
  
  .score-bar {
    position: relative;
    width: 100%;
    height: 1.25rem;
    background-color: var(--gray-100);
    border-radius: 0.25rem;
    overflow: hidden;
  }
  
  .score-fill {
    position: absolute;
    top: 0;
    left: 0;
    height: 100%;
    background-color: var(--primary-color);
    border-radius: 0.25rem;
  }
  
  .score-text {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--gray-800);
    font-size: 0.75rem;
    font-weight: 500;
  }
  
  @media (max-width: 768px) {
    .info-grid {
      grid-template-columns: 1fr;
    }
    
    .tabs-header {
      overflow-x: auto;
    }
    
    .card-header {
      flex-direction: column;
      align-items: flex-start;
      gap: 0.5rem;
    }
    
    .header-actions {
      flex-wrap: wrap;
    }
  }
  </style>