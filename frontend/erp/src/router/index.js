// src/router/index.js
import { createRouter, createWebHistory } from 'vue-router';
import Login from '../views/Login.vue';
import Register from '../views/Register.vue';
import AppLayout from '../layouts/AppLayout.vue';
import Dashboard from '../views/Dashboard.vue';
import ItemsList from '../views/inventory/ItemsList.vue';
import WarehousesList from '../views/inventory/WarehousesList.vue';
import StockTransactions from '../views/inventory/StockTransactions.vue';
import StockAdjustments from '../views/inventory/StockAdjustments.vue';

// Import other components as needed

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: Login,
    meta: { requiresAuth: false }
  },
  {
    path: '/register',
    name: 'Register',
    component: Register,
    meta: { requiresAuth: false }
  },
  {
    path: '/',
    component: AppLayout,
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        redirect: '/dashboard'
      },
      {
        path: 'dashboard',
        name: 'Dashboard',
        component: Dashboard
      },
      // Inventory Module Routes
      {
        path: 'items',
        name: 'Items',
        component: ItemsList
      },
      // {
        // path: 'categories',
        // name: 'ItemCategories',
        // component: () => import('../views/inventory/CategoriesList.vue')
      // },
      // {
        // path: 'uom',
        // name: 'UnitOfMeasures',
        // component: () => import('../views/inventory/UnitOfMeasuresList.vue')
      // },
      {
        path: 'warehouses',
        name: 'Warehouses',
        component: WarehousesList
      },
      // {
        // path: 'warehouses/:id',
        // name: 'WarehouseDetails',
        // component: () => import('../views/inventory/WarehouseDetails.vue'),
        // props: true
      // },
      // Stock Operations Routes
      {
        path: 'stock-transactions',
        name: 'StockTransactions',
        component: StockTransactions
      },
      {
        path: 'stock-adjustments',
        name: 'StockAdjustments',
        component: StockAdjustments
      },
      // {
        // path: 'cycle-counts',
        // name: 'CycleCounting',
        // component: () => import('../views/inventory/CycleCounting.vue')
      // },
      // Reports Routes
      // {
        // path: 'reports/stock',
        // name: 'StockReport',
        // component: () => import('../views/reports/StockReport.vue')
      // },
      // {
        // path: 'reports/movement',
        // name: 'MovementReport',
        // component: () => import('../views/reports/MovementReport.vue')
      // },
      // Admin Routes
      {
        path: 'admin/users',
        name: 'Users',
        component: () => import('../views/admin/UsersList.vue'),
        meta: { adminOnly: true }
      }
    ]
  },
  // Catch-all 404 route
  {
    path: '/:pathMatch(.*)*',
    redirect: '/dashboard'
  }
];

const router = createRouter({
  history: createWebHistory(),
  routes
});

// Navigation guard for authentication
router.beforeEach((to, from, next) => {
  const isAuthenticated = !!localStorage.getItem('token');
  const user = JSON.parse(localStorage.getItem('user') || '{}');
  const isAdmin = user.is_admin || false; // Menentukan apakah pengguna adalah admin
  
  if (to.meta.requiresAuth && !isAuthenticated) {
    // Redirect to login if trying to access a protected route without being authenticated
    next('/login');
  } else if (to.path === '/login' && isAuthenticated) {
    // Redirect to dashboard if already authenticated and trying to access login
    next('/dashboard');
  } else if (to.meta.adminOnly && !isAdmin) {
    // Redirect to dashboard if trying to access admin route without being admin
    next('/dashboard');
  } else {
    // Proceed normally
    next();
  }
});

export default router;