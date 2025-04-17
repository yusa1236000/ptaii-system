// src/router/index.js
import { createRouter, createWebHistory } from "vue-router";
import Login from "../views/Login.vue";
import Register from "../views/Register.vue";
import AppLayout from "../layouts/AppLayout.vue";
import Dashboard from "../views/Dashboard.vue";
// Import components
import ItemsList from "../views/inventory/ItemsList.vue";
import ItemDetail from "../views/inventory/ItemDetail.vue";
import UnitOfMeasureList from "../views/inventory/UnitOfMeasureList.vue";
import UnitOfMeasureDetail from "../views/inventory/UnitOfMeasureDetail.vue";
// import WarehousesList from "../views/inventory/WarehousesList.vue";
import StockTransactions from "../views/inventory/StockTransactions.vue";
import StockAdjustments from "../views/inventory/StockAdjustments.vue";
import ItemCategories from "../views/inventory/ItemCategories.vue";
import ItemCategoriesEnhanced from "../views/inventory/ItemCategoriesEnhanced.vue";
import CustomersList from "@/views/sales/CustomerList.vue";
import CustomerDetails from "@/views/sales/CustomerDetails.vue";
import CustomerCreate from "@/views/sales/CustomerCreate.vue";
import CustomerEdit from "@/views/sales/CustomerEdit.vue";
// Import Sales Quotation components
import SalesQuotationList from "../views/sales/SalesQuotationList.vue";
import SalesQuotationForm from "../views/sales/SalesQuotationForm.vue";
import SalesQuotationDetail from "../views/sales/SalesQuotationDetail.vue";
import SalesQuotationPrint from "../views/sales/SalesQuotationPrint.vue";
//SalesForecast
import SalesForecastList from "../views/sales/SalesForecastList.vue";
import SalesForecastDetail from "../views/sales/SalesForecastDetail.vue";
import SalesForecastAnalytics from "../views/sales/SalesForecastAnalytics.vue";
//SalesOrder
import SalesOrderList from "../views/sales/SalesOrderList.vue";
import SalesOrderDetail from "../views/sales/SalesOrderDetail.vue";
import SalesOrderForm from "../views/sales/SalesOrderForm.vue";
import CreateOrderFromQuotation from "../views/sales/CreateOrderFromQuotation.vue";
//Sales Invoice
import SalesInvoiceList from "../views/sales/SalesInvoiceList.vue";
import SalesInvoiceDetail from "../views/sales/SalesInvoiceDetail.vue";
import SalesInvoiceForm from "../views/sales/SalesInvoiceForm.vue";
import SalesInvoicePrint from "../views/sales/SalesInvoicePrint.vue";
//Sales Delivery
import DeliveryList from "../views/sales/DeliveryList.vue";
import DeliveryDetail from "../views/sales/DeliveryDetail.vue";
import DeliveryForm from "../views/sales/DeliveryForm.vue";
// import DeliveryPrint from "../views/sales/DeliveryPrint.vue";
// Add these imports to the imports section
import VendorList from "../views/purchasing/VendorList.vue";
import VendorDetail from "../views/purchasing/VendorDetail.vue";
import VendorCreate from "../views/purchasing/VendorCreate.vue";
import VendorEdit from "../views/purchasing/VendorEdit.vue";

// Import components Warehouse
import WarehouseList from "../views/inventory/WarehouseList.vue";
import WarehouseDetail from "../views/inventory/WarehouseDetail.vue";
import WarehouseZoneDetail from "../views/inventory/WarehouseZoneDetail.vue";
import WarehouseLocationForm from "../views/inventory/WarehouseLocationForm.vue";

// Import the Sales Return components
import SalesReturnList from "@/views/sales/SalesReturnList.vue";
import SalesReturnDetail from "@/views/sales/SalesReturnDetail.vue";
import SalesReturnForm from "@/views/sales/SalesReturnForm.vue";

//Puchase Requisition
import PurchaseRequisitionList from "../views/purchasing/PurchaseRequisitionList.vue";
import PurchaseRequisitionForm from "../views/purchasing/PurchaseRequisitionForm.vue";
import PurchaseRequisitionDetail from "../views/purchasing/PurchaseRequisitionDetail.vue";
import PurchaseRequisitionApproval from "../views/purchasing/PurchaseRequisitionApproval.vue";
import ConvertToRFQ from "../views/purchasing/ConvertToRFQ.vue";

// import SalesForecastFormModal from "../views/sales/SalesForecastFormModal.vue";
// Import other components as needed

const routes = [
    {
        path: "/login",
        name: "Login",
        component: Login,
        meta: { requiresAuth: false },
    },
    {
        path: "/register",
        name: "Register",
        component: Register,
        meta: { requiresAuth: false },
    },
    {
        path: "/",
        component: AppLayout,
        meta: { requiresAuth: true },
        children: [
            {
                path: "",
                redirect: "/dashboard",
            },
            {
                path: "dashboard",
                name: "Dashboard",
                component: Dashboard,
            },
            // Inventory Module Routes
            {
                path: "items",
                name: "Items",
                component: ItemsList,
            },
            {
                path: "/items/:id",
                name: "ItemDetail",
                component: ItemDetail,
                props: true,
                meta: { requiresAuth: true },
            },
            {
                path: "item-categories",
                name: "ItemCategories",
                component: ItemCategories,
            },
            {
                path: "categories-enhanced",
                name: "ItemCategoriesEnhanced",
                component: ItemCategoriesEnhanced,
            },
            // Add Unit of Measure route
            {
                path: "unit-of-measures",
                name: "UnitOfMeasures",
                component: UnitOfMeasureList,
            },
            {
                path: "unit-of-measures/:id",
                name: "UnitOfMeasureDetail",
                component: UnitOfMeasureDetail,
                props: true,
            },
            // {
            //     path: "warehouses",
            //     name: "Warehouses",
            //     component: WarehousesList,
            // },
            // {
            // path: 'warehouses/:id',
            // name: 'WarehouseDetails',
            // component: () => import('../views/inventory/WarehouseDetails.vue'),
            // props: true
            // },
            // Stock Operations Routes
            {
                path: "stock-transactions",
                name: "StockTransactions",
                component: StockTransactions,
            },
            {
                path: "/sales/customers",
                name: "customers.index",
                component: CustomersList,
                meta: { requiresAuth: true },
            },
            {
                path: "/sales/customers/create",
                name: "customers.create",
                component: CustomerCreate,
                meta: { requiresAuth: true },
            },
            {
                path: "/sales/customers/:id",
                name: "customers.show",
                component: CustomerDetails,
                props: true,
                meta: { requiresAuth: true },
            },
            {
                path: "/sales/customers/edit/:id",
                name: "customers.edit",
                component: CustomerEdit,
                props: true,
                meta: { requiresAuth: true },
            },
            {
                path: "stock-adjustments",
                name: "StockAdjustments",
                component: StockAdjustments,
            },
            {
                path: "/sales/quotations",
                name: "SalesQuotations",
                component: SalesQuotationList,
            },
            {
                path: "/sales/quotations/create",
                name: "CreateSalesQuotation",
                component: SalesQuotationForm,
            },
            {
                path: "/sales/quotations/:id",
                name: "SalesQuotationDetail",
                component: SalesQuotationDetail,
                props: true,
            },
            {
                path: "/sales/quotations/:id/edit",
                name: "EditSalesQuotation",
                component: SalesQuotationForm,
                props: true,
            },
            {
                path: "/sales/quotations/:id/print",
                name: "PrintSalesQuotation",
                component: SalesQuotationPrint,
                props: true,
            },

            //Purchase Requisition
            {
                path: "/purchasing/requisitions",
                name: "PurchaseRequisitions",
                component: PurchaseRequisitionList,
                meta: { requiresAuth: true },
            },
            {
                path: "/purchasing/requisitions/create",
                name: "CreatePurchaseRequisition",
                component: PurchaseRequisitionForm,
                meta: { requiresAuth: true },
            },
            {
                path: "/purchasing/requisitions/:id",
                name: "PurchaseRequisitionDetail",
                component: PurchaseRequisitionDetail,
                props: true,
                meta: { requiresAuth: true },
            },
            {
                path: "/purchasing/requisitions/:id/edit",
                name: "EditPurchaseRequisition",
                component: PurchaseRequisitionForm,
                props: true,
                meta: { requiresAuth: true },
            },
            {
                path: "/purchasing/requisitions/:id/approve",
                name: "ApprovePurchaseRequisition",
                component: PurchaseRequisitionApproval,
                props: true,
                meta: { requiresAuth: true },
            },
            {
                path: "/purchasing/requisitions/:id/convert",
                name: "ConvertToRFQ",
                component: ConvertToRFQ,
                props: true,
                meta: { requiresAuth: true },
            },

            //TambahanIyusyusa
            {
                path: "/sales/forecasts",
                name: "SalesForecastsList",
                component: SalesForecastList,
            },
            {
                path: "/sales/forecasts/:id",
                name: "SalesForecastDetail",
                component: SalesForecastDetail,
                props: true,
            },
            {
                path: "/sales/forecasts/analytics",
                name: "SalesForecastAnalytics",
                component: SalesForecastAnalytics,
            },
            //SalesOrder
            {
                path: "/sales/orders",
                name: "SalesOrders",
                component: SalesOrderList,
                meta: { requiresAuth: true },
            },
            {
                path: "/sales/orders/create",
                name: "CreateSalesOrder",
                component: SalesOrderForm,
                meta: { requiresAuth: true },
            },
            {
                path: "/sales/orders/:id",
                name: "SalesOrderDetail",
                component: SalesOrderDetail,
                props: true,
                meta: { requiresAuth: true },
            },
            {
                path: "/sales/orders/:id/edit",
                name: "EditSalesOrder",
                component: SalesOrderForm,
                props: true,
                meta: { requiresAuth: true },
            },
            {
                path: "/sales/orders/create-from-quotation/:id",
                name: "CreateOrderFromQuotation",
                component: CreateOrderFromQuotation,
                props: true,
                meta: { requiresAuth: true },
            },
            //SalesInvoice
            {
                path: "/sales/invoices",
                name: "SalesInvoices",
                component: SalesInvoiceList,
                meta: { requiresAuth: true },
            },
            {
                path: "/sales/invoices/create",
                name: "CreateSalesInvoice",
                component: SalesInvoiceForm,
                meta: { requiresAuth: true },
            },
            {
                path: "/sales/invoices/:id",
                name: "SalesInvoiceDetail",
                component: SalesInvoiceDetail,
                props: true,
                meta: { requiresAuth: true },
            },
            {
                path: "/sales/invoices/:id/edit",
                name: "EditSalesInvoice",
                component: SalesInvoiceForm,
                props: true,
                meta: { requiresAuth: true },
            },
            {
                path: "/sales/invoices/:id/print",
                name: "PrintSalesInvoice",
                component: SalesInvoicePrint,
                props: true,
                meta: { requiresAuth: true },
            },
            //SalesDelivery
            {
                path: "/sales/deliveries",
                name: "DeliveryList",
                component: DeliveryList,
                meta: { requiresAuth: true },
            },
            {
                path: "/sales/deliveries/create",
                name: "CreateDelivery",
                component: DeliveryForm,
                meta: { requiresAuth: true },
            },
            {
                path: "/sales/deliveries/:id",
                name: "DeliveryDetail",
                component: DeliveryDetail,
                props: true,
                meta: { requiresAuth: true },
            },
            {
                path: "/sales/deliveries/:id/edit",
                name: "EditDelivery",
                component: DeliveryForm,
                props: true,
                meta: { requiresAuth: true },
            },
            //SalesReturn
            {
                path: "/sales/returns",
                name: "SalesReturns",
                component: SalesReturnList,
                meta: { requiresAuth: true },
            },
            {
                path: "/sales/returns/create",
                name: "CreateSalesReturn",
                component: SalesReturnForm,
                meta: { requiresAuth: true },
            },
            {
                path: "/sales/returns/:id",
                name: "SalesReturnDetail",
                component: SalesReturnDetail,
                props: true,
                meta: { requiresAuth: true },
            },
            {
                path: "/sales/returns/:id/edit",
                name: "EditSalesReturn",
                component: SalesReturnForm,
                props: true,
                meta: { requiresAuth: true },
            },

            // {
            //     path: "/sales/deliveries/:id/print",
            //     name: "PrintDelivery",
            //     component: DeliveryPrint,
            //     props: true,
            //     meta: { requiresAuth: true },
            // },
            // {
            //     path: "/sales/forecasts/formmodal",
            //     name: "SalesForecastFormModal",
            //     component: SalesForecastFormModal,
            // },
            //Sampaisini
            //
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
            // Add these routes within the children array of the AppLayout route
            {
                path: "purchasing/vendors",
                name: "VendorList",
                component: VendorList,
                meta: { requiresAuth: true },
            },
            {
                path: "purchasing/vendors/create",
                name: "VendorCreate",
                component: VendorCreate,
                meta: { requiresAuth: true },
            },
            {
                path: "purchasing/vendors/:id",
                name: "VendorDetail",
                component: VendorDetail,
                props: true,
                meta: { requiresAuth: true },
            },
            {
                path: "purchasing/vendors/:id/edit",
                name: "VendorEdit",
                component: VendorEdit,
                props: true,
                meta: { requiresAuth: true },
            },
            // Warehouse routes
            {
                path: "/warehouses",
                name: "Warehouses",
                component: WarehouseList,
                meta: { requiresAuth: true },
            },
            {
                path: "/warehouses/:id",
                name: "WarehouseDetail",
                component: WarehouseDetail,
                props: true,
                meta: { requiresAuth: true },
            },

            // Warehouse zone and location management
            {
                path: "/warehouses/:warehouseId/zones/:zoneId",
                name: "WarehouseZoneDetail",
                component: WarehouseZoneDetail,
                props: true,
                meta: { requiresAuth: true },
            },
            {
                path: "/warehouses/:warehouseId/zones/:zoneId/locations/add",
                name: "AddWarehouseLocation",
                component: WarehouseLocationForm,
                props: true,
                meta: { requiresAuth: true },
            },
            {
                path: "/warehouses/:warehouseId/zones/:zoneId/locations/:locationId/edit",
                name: "EditWarehouseLocation",
                component: WarehouseLocationForm,
                props: true,
                meta: { requiresAuth: true },
            },
            // Admin Routes
            {
                path: "admin/users",
                name: "Users",
                component: () => import("../views/admin/UsersList.vue"),
                meta: { adminOnly: true },
            },
        ],
    },
    // Catch-all 404 route
    {
        path: "/:pathMatch(.*)*",
        redirect: "/dashboard",
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

// Navigation guard for authentication
router.beforeEach((to, from, next) => {
    const isAuthenticated = !!localStorage.getItem("token");
    const user = JSON.parse(localStorage.getItem("user") || "{}");
    const isAdmin = user.is_admin || false; // Menentukan apakah pengguna adalah admin

    if (to.meta.requiresAuth && !isAuthenticated) {
        // Redirect to login if trying to access a protected route without being authenticated
        next("/login");
    } else if (to.path === "/login" && isAuthenticated) {
        // Redirect to dashboard if already authenticated and trying to access login
        next("/dashboard");
    } else if (to.meta.adminOnly && !isAdmin) {
        // Redirect to dashboard if trying to access admin route without being admin
        next("/dashboard");
    } else {
        // Proceed normally
        next();
    }
});

export default router;
