<template>
    <div class="invoice-edit-container">
        <div class="page-header">
            <div class="header-left">
                <router-link
                    :to="`/purchasing/vendor-invoices/${id}`"
                    class="back-link"
                >
                    <i class="fas fa-arrow-left"></i> Back to Invoice Details
                </router-link>
                <h1>Edit Vendor Invoice</h1>
            </div>
        </div>

        <div v-if="isLoading" class="loading-container">
            <div class="loading-spinner">
                <i class="fas fa-spinner fa-spin"></i>
            </div>
            <p>Loading invoice data...</p>
        </div>

        <div v-else-if="!vendorInvoice" class="error-container">
            <div class="error-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h2>Invoice Not Found</h2>
            <p>
                The requested vendor invoice could not be found or may have been
                deleted.
            </p>
            <router-link
                to="/purchasing/vendor-invoices"
                class="btn btn-primary"
            >
                Return to Vendor Invoices List
            </router-link>
        </div>

        <div v-else-if="!canEdit" class="error-container">
            <div class="error-icon">
                <i class="fas fa-lock"></i>
            </div>
            <h2>Cannot Edit Invoice</h2>
            <p>
                This invoice cannot be edited because its status is "{{
                    vendorInvoice.status
                }}".
            </p>
            <p>Only pending invoices can be edited.</p>
            <router-link
                :to="`/purchasing/vendor-invoices/${id}`"
                class="btn btn-primary"
            >
                Return to Invoice Details
            </router-link>
        </div>

        <div v-else class="form-container">
            <VendorInvoiceForm
                :vendor-invoice="vendorInvoice"
                :purchase-orders="purchaseOrders"
                :is-edit-mode="true"
                :is-submitting="isSubmitting"
                :server-errors="serverErrors"
                @submit="updateVendorInvoice"
                @cancel="cancel"
            />
        </div>
    </div>
</template>

<script>
import { ref, computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import VendorInvoiceService from "@/services/VendorInvoiceService";
import PurchaseOrderService from "@/services/PurchaseOrderService";
import VendorInvoiceForm from "./VendorInvoiceForm.vue";

export default {
    name: "VendorInvoiceEdit",
    components: {
        VendorInvoiceForm,
    },
    props: {
        id: {
            type: [Number, String],
            required: true,
        },
    },
    setup(props) {
        const router = useRouter();
        const vendorInvoice = ref(null);
        const purchaseOrders = ref([]);
        const isLoading = ref(true);
        const isSubmitting = ref(false);
        const serverErrors = ref({});

        // Computed property to determine if invoice can be edited
        const canEdit = computed(() => {
            return (
                vendorInvoice.value && vendorInvoice.value.status === "pending"
            );
        });

        // Fetch vendor invoice details
        const fetchVendorInvoice = async () => {
            try {
                const response =
                    await VendorInvoiceService.getVendorInvoiceById(props.id);
                vendorInvoice.value =
                    response.data && response.data.data
                        ? response.data.data
                        : null;
            } catch (error) {
                console.error("Error fetching vendor invoice details:", error);
                vendorInvoice.value = null;
            }
        };

        // Fetch purchase orders
        const fetchPurchaseOrders = async () => {
            try {
                // Get all POs that are in received or partial status
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

        // Update vendor invoice
        const updateVendorInvoice = async (formData) => {
            isSubmitting.value = true;
            serverErrors.value = {};

            try {
                await VendorInvoiceService.updateVendorInvoice(
                    props.id,
                    formData
                );

                // Navigate back to invoice detail page
                router.push(`/purchasing/vendor-invoices/${props.id}`);
            } catch (error) {
                console.error("Error updating vendor invoice:", error);

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
                    alert("Failed to update vendor invoice. Please try again.");
                }

                isSubmitting.value = false;
            }
        };

        // Cancel form
        const cancel = () => {
            router.push(`/purchasing/vendor-invoices/${props.id}`);
        };

        // Initialize data
        onMounted(async () => {
            isLoading.value = true;
            await Promise.all([fetchVendorInvoice(), fetchPurchaseOrders()]);
            isLoading.value = false;
        });

        return {
            vendorInvoice,
            purchaseOrders,
            isLoading,
            isSubmitting,
            serverErrors,
            canEdit,
            updateVendorInvoice,
            cancel,
        };
    },
};
</script>

<style scoped>
.invoice-edit-container {
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

.loading-container,
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

.loading-spinner {
    font-size: 2rem;
    color: var(--primary-color);
    margin-bottom: 1rem;
}

.error-icon {
    font-size: 3rem;
    color: var(--danger-color);
    margin-bottom: 1rem;
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
    margin-top: 1rem;
}

.btn-primary {
    background-color: var(--primary-color);
    color: white;
}

.btn-primary:hover {
    background-color: var(--primary-dark);
}
</style>
