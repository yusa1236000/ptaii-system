<template>
    <div class="invoice-create-container">
        <div class="page-header">
            <div class="header-left">
                <router-link to="/purchasing/vendor-invoices" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to Vendor Invoices
                </router-link>
                <h1>Create Vendor Invoice</h1>
            </div>
        </div>

        <div v-if="isLoading" class="loading-container">
            <div class="loading-spinner">
                <i class="fas fa-spinner fa-spin"></i>
            </div>
            <p>Loading data...</p>
        </div>

        <div v-else class="form-container">
            <VendorInvoiceForm
                :purchase-orders="purchaseOrders"
                :is-submitting="isSubmitting"
                :server-errors="serverErrors"
                @submit="createVendorInvoice"
                @cancel="cancel"
            />
        </div>
    </div>
</template>

<script>
import { ref, onMounted } from "vue";
import { useRouter } from "vue-router";
import VendorInvoiceService from "@/services/VendorInvoiceService";
import PurchaseOrderService from "@/services/PurchaseOrderService";
import VendorInvoiceForm from "./VendorInvoiceForm.vue";

export default {
    name: "VendorInvoiceCreate",
    components: {
        VendorInvoiceForm,
    },
    setup() {
        const router = useRouter();
        const purchaseOrders = ref([]);
        const isLoading = ref(true);
        const isSubmitting = ref(false);
        const serverErrors = ref({});

        // Fetch purchase orders
        const fetchPurchaseOrders = async () => {
            try {
                // We only want POs that are in received or partial status
                const params = {
                    status: ["partial", "received", "completed"].join(","),
                    per_page: 100,
                };

                const response =
                    await PurchaseOrderService.getAllPurchaseOrders(params);
                purchaseOrders.value =
                    response.data && response.data.data
                        ? response.data.data
                        : [];
            } catch (error) {
                console.error("Error fetching purchase orders:", error);
                purchaseOrders.value = [];
            } finally {
                isLoading.value = false;
            }
        };

        // Create vendor invoice
        const createVendorInvoice = async (formData) => {
            isSubmitting.value = true;
            serverErrors.value = {};

            try {
                const response = await VendorInvoiceService.createVendorInvoice(
                    formData
                );

                // Navigate to invoice detail page
                const invoiceId =
                    response.data && response.data.data
                        ? response.data.data.invoice_id
                        : null;
                router.push(
                    invoiceId
                        ? `/purchasing/vendor-invoices/${invoiceId}`
                        : "/purchasing/vendor-invoices"
                );
            } catch (error) {
                console.error("Error creating vendor invoice:", error);

                // Handle validation errors
                if (error.response && error.response.status === 422) {
                    serverErrors.value = error.response.data.errors || {};
                } else if (
                    error.response &&
                    error.response.data &&
                    error.response.data.message
                ) {
                    alert(error.response.data.message);
                } else {
                    alert("Failed to create vendor invoice. Please try again.");
                }

                isSubmitting.value = false;
            }
        };

        // Cancel form
        const cancel = () => {
            router.push("/purchasing/vendor-invoices");
        };

        // Initialize data
        onMounted(() => {
            fetchPurchaseOrders();
        });

        return {
            purchaseOrders,
            isLoading,
            isSubmitting,
            serverErrors,
            createVendorInvoice,
            cancel,
        };
    },
};
</script>

<style scoped>
.invoice-create-container {
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
</style>
