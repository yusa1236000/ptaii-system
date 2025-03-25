<!-- src/views/admin/UsersList.vue -->
<template>
  <div class="users-list">
    <!-- Search and Filter -->
    <SearchFilter
      v-model:value="searchQuery"
      placeholder="Cari pengguna..."
      @search="applyFilters"
      @clear="clearSearch"
    >
      <template #actions>
        <button class="btn btn-primary" @click="openAddUserModal">
          <i class="fas fa-plus"></i> Tambah Pengguna
        </button>
      </template>
    </SearchFilter>
    
    <!-- DataTable -->
    <DataTable
      :columns="columns"
      :items="filteredUsers"
      :is-loading="isLoading"
      keyField="id"
      emptyIcon="fas fa-users"
      emptyTitle="Tidak ada pengguna ditemukan"
      emptyMessage="Coba sesuaikan pencarian atau tambahkan pengguna baru."
      @sort="handleSort"
    >
      <template #status="slotProps">
        <span class="status-badge" :class="slotProps.item.email_verified_at ? 'verified' : 'unverified'">
          {{ slotProps.item.email_verified_at ? 'Terverifikasi' : 'Belum Terverifikasi' }}
        </span>
      </template>
      
      <template #date="{ value }">
        {{ formatDate(value) }}
      </template>
      
      <template #actions="{ item }">
        <button class="action-btn" title="Edit Pengguna" @click="editUser(item)">
          <i class="fas fa-edit"></i>
        </button>
        <button class="action-btn" title="Hapus Pengguna" @click="confirmDelete(item)">
          <i class="fas fa-trash"></i>
        </button>
      </template>
    </DataTable>
    
    <!-- Add/Edit User Modal -->
    <div v-if="showUserModal" class="modal">
      <div class="modal-backdrop" @click="closeUserModal"></div>
      <div class="modal-content">
        <div class="modal-header">
          <h2>{{ isEditMode ? 'Edit Pengguna' : 'Tambah Pengguna' }}</h2>
          <button class="close-btn" @click="closeUserModal">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="saveUser">
            <div class="form-group">
              <label for="name">Nama Lengkap*</label>
              <input 
                type="text" 
                id="name" 
                v-model="userForm.name" 
                required
                class="form-control"
              />
            </div>
            <div class="form-group">
              <label for="email">Email*</label>
              <input 
                type="email" 
                id="email" 
                v-model="userForm.email" 
                required
                :disabled="isEditMode"
                class="form-control"
              />
            </div>
            <div class="form-group" v-if="!isEditMode">
              <label for="password">Password*</label>
              <input 
                type="password" 
                id="password" 
                v-model="userForm.password" 
                required
                minlength="8"
                class="form-control"
              />
              <small v-if="passwordError" class="text-danger">{{ passwordError }}</small>
            </div>
            <div class="form-group" v-if="!isEditMode">
              <label for="password_confirmation">Konfirmasi Password*</label>
              <input 
                type="password" 
                id="password_confirmation" 
                v-model="userForm.password_confirmation" 
                required
                minlength="8"
                class="form-control"
                @input="validatePasswords"
              />
            </div>
            <div class="form-group" v-if="isEditMode">
              <label for="new_password">Password Baru (kosongkan jika tidak ingin mengubah)</label>
              <input 
                type="password" 
                id="new_password" 
                v-model="userForm.new_password"
                minlength="8"
                class="form-control"
              />
            </div>
            <div class="form-group" v-if="isEditMode && userForm.new_password">
              <label for="new_password_confirmation">Konfirmasi Password Baru</label>
              <input 
                type="password" 
                id="new_password_confirmation" 
                v-model="userForm.new_password_confirmation"
                minlength="8"
                class="form-control"
                @input="validateNewPasswords"
              />
              <small v-if="newPasswordError" class="text-danger">{{ newPasswordError }}</small>
            </div>
            
            <div class="form-actions">
              <button type="button" class="btn btn-secondary" @click="closeUserModal">
                Batal
              </button>
              <button type="submit" class="btn btn-primary" :disabled="formHasErrors">
                {{ isEditMode ? 'Update Pengguna' : 'Tambah Pengguna' }}
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
          <h2>Konfirmasi Hapus</h2>
          <button class="close-btn" @click="closeDeleteModal">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <p>Apakah Anda yakin ingin menghapus pengguna <strong>{{ userToDelete.name }}</strong>?</p>
          <p class="text-danger">Tindakan ini tidak dapat dibatalkan.</p>
          
          <div class="form-actions">
            <button type="button" class="btn btn-secondary" @click="closeDeleteModal">
              Batal
            </button>
            <button type="button" class="btn btn-danger" @click="deleteUser">
              Hapus Pengguna
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, watch } from 'vue';
import SearchFilter from '@/components/common/SearchFilter.vue';
import DataTable from '@/components/common/DataTable.vue';

export default {
  name: 'UsersList',
  components: {
    SearchFilter,
    DataTable
  },
  setup() {
    // Data
    const users = ref([]);
    const isLoading = ref(true);
    const searchQuery = ref('');
    const sortKey = ref('id');
    const sortOrder = ref('desc');
    
    // Form validation errors
    const passwordError = ref('');
    const newPasswordError = ref('');
    
    // Table columns
    const columns = ref([
      { key: 'id', label: 'ID', sortable: true },
      { key: 'name', label: 'Nama', sortable: true },
      { key: 'email', label: 'Email', sortable: true },
      { key: 'created_at', label: 'Tanggal Registrasi', sortable: true, template: 'date' },
      { key: 'status', label: 'Status', template: 'status' },
    ]);
    
    // Modals
    const showUserModal = ref(false);
    const showDeleteModal = ref(false);
    const isEditMode = ref(false);
    const userForm = ref({
      name: '',
      email: '',
      password: '',
      password_confirmation: '',
      new_password: '',
      new_password_confirmation: ''
    });
    const userToDelete = ref({});
    
    // Computed properties
    const filteredUsers = computed(() => {
      let result = [...users.value];
      
      // Apply search filter
      if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(user => 
          user.name.toLowerCase().includes(query) || 
          user.email.toLowerCase().includes(query)
        );
      }
      
      // Apply sorting
      result.sort((a, b) => {
        let valA = a[sortKey.value];
        let valB = b[sortKey.value];
        
        // Handle date sorting
        if (sortKey.value === 'created_at') {
          valA = new Date(valA || 0).getTime();
          valB = new Date(valB || 0).getTime();
        }
        
        // Handle string sorting
        if (typeof valA === 'string' && typeof valB === 'string') {
          return sortOrder.value === 'asc' 
            ? valA.localeCompare(valB) 
            : valB.localeCompare(valA);
        }
        
        // Handle number sorting
        return sortOrder.value === 'asc' ? valA - valB : valB - valA;
      });
      
      return result;
    });
    
    const formHasErrors = computed(() => {
      if (!isEditMode.value) {
        return !!passwordError.value;
      } else if (userForm.value.new_password) {
        return !!newPasswordError.value;
      }
      return false;
    });
    
    // Methods
    const fetchUsers = async () => {
      isLoading.value = true;
      
      try {
        // For demo purposes, use dummy data
        setTimeout(() => {
          users.value = [
            {
              id: 1,
              name: 'Admin User',
              email: 'admin@example.com',
              created_at: '2024-10-01T00:00:00.000000Z',
              email_verified_at: '2024-10-01T00:10:00.000000Z'
            },
            {
              id: 2,
              name: 'John Doe',
              email: 'john@example.com',
              created_at: '2024-10-02T00:00:00.000000Z',
              email_verified_at: '2024-10-02T00:15:00.000000Z'
            },
            {
              id: 3,
              name: 'Jane Smith',
              email: 'jane@example.com',
              created_at: '2024-10-03T00:00:00.000000Z',
              email_verified_at: '2024-10-03T00:12:00.000000Z'
            },
            {
              id: 4,
              name: 'New User',
              email: 'new@example.com',
              created_at: '2024-10-10T00:00:00.000000Z',
              email_verified_at: null
            }
          ];
          
          isLoading.value = false;
        }, 500);
      } catch (error) {
        console.error('Error fetching users:', error);
        isLoading.value = false;
      }
    };
    
    const formatDate = (dateString) => {
      if (!dateString) return '-';
      
      const date = new Date(dateString);
      return date.toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      });
    };
    
    const handleSort = ({ key, order }) => {
      sortKey.value = key;
      sortOrder.value = order;
    };
    
    const applyFilters = () => {
      // Nothing specific to do for filtering users
    };
    
    const clearSearch = () => {
      searchQuery.value = '';
    };
    
    const validatePasswords = () => {
      if (userForm.value.password !== userForm.value.password_confirmation) {
        passwordError.value = 'Password dan konfirmasi password tidak sama';
      } else {
        passwordError.value = '';
      }
    };
    
    const validateNewPasswords = () => {
      if (userForm.value.new_password !== userForm.value.new_password_confirmation) {
        newPasswordError.value = 'Password baru dan konfirmasi password baru tidak sama';
      } else {
        newPasswordError.value = '';
      }
    };
    
    const openAddUserModal = () => {
      isEditMode.value = false;
      userForm.value = {
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        new_password: '',
        new_password_confirmation: ''
      };
      passwordError.value = '';
      newPasswordError.value = '';
      showUserModal.value = true;
    };
    
    const editUser = (user) => {
      isEditMode.value = true;
      userForm.value = {
        id: user.id,
        name: user.name,
        email: user.email,
        password: '',
        password_confirmation: '',
        new_password: '',
        new_password_confirmation: ''
      };
      passwordError.value = '';
      newPasswordError.value = '';
      showUserModal.value = true;
    };
    
    const closeUserModal = () => {
      showUserModal.value = false;
    };
    
    const saveUser = async () => {
      try {
        // Validasi password match untuk pengguna baru
        if (!isEditMode.value) {
          validatePasswords();
          if (passwordError.value) return;
        }
        
        // Validasi password match untuk update password
        if (isEditMode.value && userForm.value.new_password) {
          validateNewPasswords();
          if (newPasswordError.value) return;
        }
        
        if (isEditMode.value) {
          // For demo purposes, update the local state
          const index = users.value.findIndex(user => user.id === userForm.value.id);
          if (index !== -1) {
            users.value[index].name = userForm.value.name;
          }
          
          // Show success message
          alert('Pengguna berhasil diperbarui!');
        } else {
          // For demo purposes, add to the local state
          const newUser = {
            id: users.value.length + 1,
            name: userForm.value.name,
            email: userForm.value.email,
            created_at: new Date().toISOString(),
            email_verified_at: null
          };
          
          users.value.push(newUser);
          
          // Show success message
          alert('Pengguna berhasil ditambahkan!');
        }
        
        // Close the modal
        closeUserModal();
      } catch (error) {
        console.error('Error saving user:', error);
        
        alert('Terjadi kesalahan saat menyimpan data pengguna. Silakan coba lagi.');
      }
    };
    
    const confirmDelete = (user) => {
      userToDelete.value = user;
      showDeleteModal.value = true;
    };
    
    const closeDeleteModal = () => {
      showDeleteModal.value = false;
    };
    
    const deleteUser = async () => {
      try {
        // For demo purposes, remove from the local state
        users.value = users.value.filter(user => user.id !== userToDelete.value.id);
        
        // Close the modal
        closeDeleteModal();
        
        // Show success message
        alert('Pengguna berhasil dihapus!');
      } catch (error) {
        console.error('Error deleting user:', error);
        alert('Terjadi kesalahan saat menghapus pengguna. Silakan coba lagi.');
      }
    };
    
    // Watch for form changes to validate in real-time
    watch(() => userForm.value.password_confirmation, validatePasswords);
    watch(() => userForm.value.new_password_confirmation, validateNewPasswords);
    
    // Initial data loading
    onMounted(() => {
      fetchUsers();
    });
    
    return {
      users,
      columns,
      isLoading,
      searchQuery,
      sortKey,
      sortOrder,
      filteredUsers,
      showUserModal,
      showDeleteModal,
      isEditMode,
      userForm,
      userToDelete,
      passwordError,
      newPasswordError,
      formHasErrors,
      formatDate,
      handleSort,
      applyFilters,
      clearSearch,
      validatePasswords,
      validateNewPasswords,
      openAddUserModal,
      editUser,
      closeUserModal,
      saveUser,
      confirmDelete,
      closeDeleteModal,
      deleteUser
    };
  }
};
</script>

<style scoped>
.users-list {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.text-danger {
  color: var(--danger-color);
  font-size: 0.75rem;
  margin-top: 0.25rem;
  display: block;
}

.status-badge {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
  font-size: 0.75rem;
  font-weight: 500;
}

.status-badge.verified {
  background-color: var(--success-bg);
  color: var(--success-color);
}

.status-badge.unverified {
  background-color: var(--danger-bg);
  color: var(--danger-color);
}
</style>