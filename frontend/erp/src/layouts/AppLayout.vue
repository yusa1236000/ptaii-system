<!-- src/layouts/AppLayout.vue -->
<template>
    <div class="app-container">
      <aside class="sidebar" :class="{ 'sidebar-collapsed': sidebarCollapsed }">
        <div class="sidebar-header">
          <div class="brand-logo">
            <span v-if="!sidebarCollapsed">Inventory ERP</span>
            <span v-else>ERP</span>
          </div>
          <button class="collapse-btn" @click="toggleSidebar">
            <i :class="sidebarCollapsed ? 'fas fa-angle-right' : 'fas fa-angle-left'"></i>
          </button>
        </div>
        <nav class="sidebar-menu">
          <router-link to="/dashboard" class="menu-item" active-class="active">
            <i class="fas fa-tachometer-alt"></i>
            <span v-if="!sidebarCollapsed">Dashboard</span>
          </router-link>
          
          <!-- Inventory Management Section -->
          <div class="menu-section">
            <div v-if="!sidebarCollapsed" class="section-title">Inventory</div>
          </div>
          
          <router-link to="/items" class="menu-item" active-class="active">
            <i class="fas fa-box"></i>
            <span v-if="!sidebarCollapsed">Items</span>
          </router-link>
          
          <router-link to="/categories" class="menu-item" active-class="active">
            <i class="fas fa-tags"></i>
            <span v-if="!sidebarCollapsed">Categories</span>
          </router-link>
          
          <router-link to="/uom" class="menu-item" active-class="active">
            <i class="fas fa-ruler"></i>
            <span v-if="!sidebarCollapsed">Units of Measure</span>
          </router-link>
          
          <router-link to="/warehouses" class="menu-item" active-class="active">
            <i class="fas fa-warehouse"></i>
            <span v-if="!sidebarCollapsed">Warehouses</span>
          </router-link>
          
          <!-- Stock Operations Section -->
          <div class="menu-section">
            <div v-if="!sidebarCollapsed" class="section-title">Stock Operations</div>
          </div>
          
          <router-link to="/stock-transactions" class="menu-item" active-class="active">
            <i class="fas fa-exchange-alt"></i>
            <span v-if="!sidebarCollapsed">Transactions</span>
          </router-link>
          
          <router-link to="/stock-adjustments" class="menu-item" active-class="active">
            <i class="fas fa-sliders-h"></i>
            <span v-if="!sidebarCollapsed">Adjustments</span>
          </router-link>
          
          <router-link to="/cycle-counts" class="menu-item" active-class="active">
            <i class="fas fa-clipboard-check"></i>
            <span v-if="!sidebarCollapsed">Cycle Counting</span>
          </router-link>
          
          <!-- Reports Section -->
          <div class="menu-section">
            <div v-if="!sidebarCollapsed" class="section-title">Reports</div>
          </div>
          
          <router-link to="/reports/stock" class="menu-item" active-class="active">
            <i class="fas fa-chart-bar"></i>
            <span v-if="!sidebarCollapsed">Stock Report</span>
          </router-link>
          
          <router-link to="/reports/movement" class="menu-item" active-class="active">
            <i class="fas fa-chart-line"></i>
            <span v-if="!sidebarCollapsed">Movement Report</span>
          </router-link>
          
          <!-- Admin Section -->
          <div class="menu-section">
            <div v-if="!sidebarCollapsed" class="section-title">Admin</div>
          </div>
          
          <router-link to="/admin/users" class="menu-item" active-class="active">
            <i class="fas fa-users"></i>
            <span v-if="!sidebarCollapsed">Pengguna</span>
          </router-link>
        </nav>
      </aside>
      
      <div class="main-content">
        <header class="main-header">
          <div class="header-left">
            <h1 class="page-title">{{ pageTitle }}</h1>
          </div>
          <div class="header-right">
            <div class="user-menu" @click="toggleUserMenu">
              <img class="avatar" src="/images/user-avatar.png" alt="User avatar" />
              <span class="username">{{ user.name }}</span>
              <i class="fas fa-chevron-down"></i>
              
              <div v-if="userMenuOpen" class="dropdown-menu">
                <div class="dropdown-item">
                  <i class="fas fa-user"></i>
                  Profile
                </div>
                <div class="dropdown-item">
                  <i class="fas fa-cog"></i>
                  Settings
                </div>
                <div class="dropdown-divider"></div>
                <div class="dropdown-item" @click="logout">
                  <i class="fas fa-sign-out-alt"></i>
                  Logout
                </div>
              </div>
            </div>
          </div>
        </header>
        
        <main class="content">
          <router-view />
        </main>
      </div>
    </div>
  </template>
  
  <script>
  import { ref, computed, onMounted } from 'vue';
  import { useRouter, useRoute } from 'vue-router';
  import axios from 'axios';
  
  export default {
    name: 'AppLayout',
    setup() {
      const router = useRouter();
      const route = useRoute();
      const sidebarCollapsed = ref(localStorage.getItem('sidebarCollapsed') === 'true');
      const userMenuOpen = ref(false);
      const user = ref(JSON.parse(localStorage.getItem('user') || '{}'));
      
      const pageTitle = computed(() => {
        switch (route.name) {
          case 'Dashboard': return 'Dashboard';
          case 'Items': return 'Items Management';
          case 'ItemCategories': return 'Item Categories';
          case 'UnitOfMeasures': return 'Units of Measure';
          case 'Warehouses': return 'Warehouses';
          case 'StockTransactions': return 'Stock Transactions';
          case 'StockAdjustments': return 'Stock Adjustments';
          case 'CycleCounting': return 'Cycle Counting';
          case 'StockReport': return 'Stock Report';
          case 'MovementReport': return 'Movement Report';
          default: return 'Dashboard';
        }
      });
      
      const toggleSidebar = () => {
        sidebarCollapsed.value = !sidebarCollapsed.value;
        localStorage.setItem('sidebarCollapsed', sidebarCollapsed.value);
      };
      
      const toggleUserMenu = () => {
        userMenuOpen.value = !userMenuOpen.value;
      };
      
      const logout = async () => {
        try {
          await axios.post('/api/auth/logout');
        } catch (error) {
          console.error('Logout error:', error);
        } finally {
          localStorage.removeItem('token');
          localStorage.removeItem('user');
          axios.defaults.headers.common['Authorization'] = '';
          router.push('/login');
        }
      };
      
      // Close dropdown when clicking outside
      onMounted(() => {
        document.addEventListener('click', (event) => {
          const userMenu = document.querySelector('.user-menu');
          if (userMenu && !userMenu.contains(event.target)) {
            userMenuOpen.value = false;
          }
        });
      });
      
      return {
        sidebarCollapsed,
        userMenuOpen,
        user,
        pageTitle,
        toggleSidebar,
        toggleUserMenu,
        logout
      };
    }
  };
  </script>
  
  <style scoped>
  .app-container {
    display: flex;
    height: 100vh;
    overflow: hidden;
  }
  
  .sidebar {
    width: 250px;
    background-color: #1e293b;
    color: #f8fafc;
    display: flex;
    flex-direction: column;
    transition: width 0.3s ease;
  }
  
  .sidebar-collapsed {
    width: 70px;
  }
  
  .sidebar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid #334155;
  }
  
  .brand-logo {
    font-size: 1.25rem;
    font-weight: 700;
    white-space: nowrap;
    overflow: hidden;
  }
  
  .collapse-btn {
    background: transparent;
    border: none;
    color: #f8fafc;
    cursor: pointer;
    font-size: 1rem;
    padding: 0.25rem;
  }
  
  .sidebar-menu {
    flex: 1;
    overflow-y: auto;
    padding: 1rem 0;
  }
  
  .menu-section {
    padding: 0.75rem 1rem 0.25rem;
    font-size: 0.75rem;
    text-transform: uppercase;
    color: #94a3b8;
    letter-spacing: 0.05em;
  }
  
  .section-title {
    margin-bottom: 0.5rem;
  }
  
  .menu-item {
    display: flex;
    align-items: center;
    padding: 0.75rem 1rem;
    color: #e2e8f0;
    text-decoration: none;
    transition: background-color 0.2s;
  }
  
  .menu-item:hover {
    background-color: #334155;
  }
  
  .menu-item.active {
    background-color: #2563eb;
    font-weight: 500;
  }
  
  .menu-item i {
    font-size: 1rem;
    margin-right: 1rem;
    width: 20px;
    text-align: center;
  }
  
  .main-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    background-color: #f1f5f9;
  }
  
  .main-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 2rem;
    background-color: white;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    z-index: 10;
  }
  
  .page-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
  }
  
  .user-menu {
    display: flex;
    align-items: center;
    cursor: pointer;
    position: relative;
  }
  
  .avatar {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 50%;
    margin-right: 0.75rem;
  }
  
  .username {
    font-weight: 500;
    margin-right: 0.5rem;
  }
  
  .dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    margin-top: 0.5rem;
    background-color: white;
    border-radius: 0.375rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    width: 12rem;
    z-index: 20;
  }
  
  .dropdown-item {
    padding: 0.75rem 1rem;
    display: flex;
    align-items: center;
    color: #1e293b;
    transition: background-color 0.2s;
  }
  
  .dropdown-item i {
    width: 1.25rem;
    margin-right: 0.75rem;
  }
  
  .dropdown-item:hover {
    background-color: #f1f5f9;
  }
  
  .dropdown-divider {
    height: 1px;
    background-color: #e2e8f0;
    margin: 0.25rem 0;
  }
  
  .content {
    flex: 1;
    padding: 2rem;
    overflow-y: auto;
  }
  
  @media (max-width: 768px) {
    .sidebar {
      position: fixed;
      height: 100vh;
      z-index: 30;
      transform: translateX(0);
      transition: transform 0.3s ease;
    }
    
    .sidebar-collapsed {
      transform: translateX(-100%);
    }
    
    .main-header {
      padding: 1rem;
    }
    
    .content {
      padding: 1rem;
    }
  }
  </style>