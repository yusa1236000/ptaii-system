// src/router/index.js
import { createRouter, createWebHistory } from 'vue-router';
import Login from '../views/Login.vue';
import Register from '../views/Register.vue';
import AppLayout from '../layouts/AppLayout.vue';
import Dashboard from '../views/Dashboard.vue';
// Import components
import ItemsList from '../views/inventory/ItemsList.vue';
import ItemDetail from '../views/inventory/ItemDetail.vue';
import UnitOfMeasureList from '../views/inventory/UnitOfMeasureList.vue';
import UnitOfMeasureDetail from '../views/inventory/UnitOfMeasureDetail.vue';
import WarehousesList from '../views/inventory/WarehousesList.vue';
import StockTransactions from '../views/inventory/StockTransactions.vue';
import StockAdjustments from '../views/inventory/StockAdjustments.vue';
import ItemCategories from '../views/inventory/ItemCategories.vue';
import ItemCategoriesEnhanced from '../views/inventory/ItemCategoriesEnhanced.vue';
import CustomersList from '@/views/sales/CustomerList.vue';
import CustomerDetails from '@/views/sales/CustomerDetails.vue';
import CustomerCreate from '@/views/sales/CustomerCreate.vue';
import CustomerEdit from '@/views/sales/CustomerEdit.vue';
// Import Sales Quotation components
import SalesQuotationList from '../views/sales/SalesQuotationList.vue';
import SalesQuotationForm from '../views/sales/SalesQuotationForm.vue';
import SalesQuotationDetail from '../views/sales/SalesQuotationDetail.vue';
import SalesQuotationPrint from '../views/sales/SalesQuotationPrint.vue';

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
      {
        path: '/items/:id',
        name: 'ItemDetail',
        component: ItemDetail,
        props: true,
        meta: { requiresAuth: true }
      },
      {
        path: 'item-categories',
        name: 'ItemCategories',
        component: ItemCategories
      },
      {
        path: 'categories-enhanced',
        name: 'ItemCategoriesEnhanced',
        component: ItemCategoriesEnhanced
      },
      // Add Unit of Measure route
      {
        path: 'unit-of-measures',
        name: 'UnitOfMeasures',
        component: UnitOfMeasureList
      },
      {
        path: 'unit-of-measures/:id',
        name: 'UnitOfMeasureDetail',
        component: UnitOfMeasureDetail,
        props: true
      },
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
        path: '/sales/customers',
        name: 'customers.index',
        component: CustomersList,
        meta: { requiresAuth: true }
      },
      {
        path: '/sales/customers/create',
        name: 'customers.create',
        component: CustomerCreate,
        meta: { requiresAuth: true }
      },
      {
        path: '/sales/customers/:id',
        name: 'customers.show',
        component: CustomerDetails,
        props: true,
        meta: { requiresAuth: true }
      },
      {
        path: '/sales/customers/edit/:id',
        name: 'customers.edit',
        component: CustomerEdit,
        props: true,
        meta: { requiresAuth: true }
      },
      {
        path: 'stock-adjustments',
        name: 'StockAdjustments',
        component: StockAdjustments
      },
      {
        path: '/sales/quotations',
        name: 'SalesQuotations',
        component: SalesQuotationList
      },
      {
        path: '/sales/quotations/create',
        name: 'CreateSalesQuotation',
        component: SalesQuotationForm
      },
      {
        path: '/sales/quotations/:id',
        name: 'SalesQuotationDetail',
        component: SalesQuotationDetail,
        props: true
      },
      {
        path: '/sales/quotations/:id/edit',
        name: 'EditSalesQuotation',
        component: SalesQuotationForm,
        props: true
      },
      {
        path: '/sales/quotations/:id/print',
        name: 'PrintSalesQuotation',
        component: SalesQuotationPrint,
        props: true
      },
        // BOM Routes
      //{
        //path: '/manufacturing/boms',
        //name: 'BOMList',
        //component: () => import('../views/manufacturing/BOMList.vue'),
        //meta: { requiresAuth: true }
      //},
      //{
        //path: '/manufacturing/boms/:id',
        //name: 'BOMDetail',
        //component: () => import('../views/manufacturing/BOMDetail.vue'),
        //props: true,
        //meta: { requiresAuth: true }
      //},
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