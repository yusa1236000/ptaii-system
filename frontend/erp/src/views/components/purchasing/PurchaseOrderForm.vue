<!-- src/views/purchasing/PurchaseOrderForm.vue -->
<template>
    <div class="po-form-container">
        <div class="page-header">
            <div class="header-left">
                <router-link :to="cancelPath" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to
                    {{ isEditMode ? "Purchase Order" : "Purchase Orders" }}
                </router-link>
                <h1>
                    {{
                        isEditMode
                            ? "Edit Purchase Order"
                            : "Create Purchase Order"
                    }}
                </h1>
            </div>
        </div>

        <div v-if="isLoading" class="loading-container">
            <div class="loading-spinner">
                <i class="fas fa-spinner fa-spin"></i>
            </div>
            <p>Loading data...</p>
        </div>

        <div v-else class="form-container">
            <PurchaseOrderForm
                :purchase-order="purchaseOrder"
                :is-edit-mode="isEditMode"
                :is-submitting="isSubmitting"
                :vendors="vendors"
                :items="items"
                :units-of-measure="unitsOfMeasure"
                :server-errors="serverErrors"
                @submit="submitForm"
                @cancel="cancel"
            />
        </div>
    </div>
</template>

<script>
import { ref, computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import PurchaseOrderService from "@/services/PurchaseOrderService";
import PurchaseOrderForm from "@/components/purchasing/PurchaseOrderForm.vue";

export default {
    name: "PurchaseOrderFormView",
    components: {
        PurchaseOrderForm,
    },
    props: {
        id: {
            type: [Number, String],
            default: null,
        },
    },
    setup(props) {
        const router = useRouter();
        //const route = useRoute();

        const purchaseOrder = ref(null);
        const vendors = ref([]);
        const items = ref([]);
        const unitsOfMeasure = ref([]);
        const isLoading = ref(true);
        const isSubmitting = ref(false);
        const serverErrors = ref({});

        const isEditMode = computed(() => !!props.id);

        const cancelPath = computed(() => {
            return isEditMode.value
                ? `/purchasing/orders/${props.id}`
                : "/purchasing/orders";
        });

        // Fetch purchase order data for edit mode
        const fetchPurchaseOrder = async () => {
            if (!props.id) return;

            try {
                const response =
                    await PurchaseOrderService.getPurchaseOrderById(props.id);
                purchaseOrder.value =
                    response.data && response.data.data
                        ? response.data.data
                        : null;
            } catch (error) {
                console.error("Error fetching purchase order:", error);
                purchaseOrder.value = null;
            }
        };

        // Fetch vendors for the dropdown
        const fetchVendors = async () => {
            try {
                const response = await PurchaseOrderService.getVendors();
                vendors.value =
                    response.data && response.data.data
                        ? response.data.data
                        : [];
            } catch (error) {
                console.error("Error fetching vendors:", error);
                vendors.value = [];
            }
        };

        // Fetch items for the line items dropdown
        const fetchItems = async () => {
            try {
                const response = await PurchaseOrderService.getItems();
                items.value =
                    response.data && response.data.data
                        ? response.data.data
                        : [];
            } catch (error) {
                console.error("Error fetching items:", error);
                items.value = [];
            }
        };

        // Fetch units of measure for the dropdown
        const fetchUnitsOfMeasure = async () => {
            try {
                const response = await PurchaseOrderService.getUnitsOfMeasure();
                unitsOfMeasure.value =
                    response.data && response.data.data
                        ? response.data.data
                        : [];
            } catch (error) {
                console.error("Error fetching units of measure:", error);
                unitsOfMeasure.value = [];
            }
        };

        // Initialize data
        const initializeData = async () => {
            isLoading.value = true;

            try {
                // Fetch all necessary data in parallel
                await Promise.all([
                    fetchVendors(),
                    fetchItems(),
                    fetchUnitsOfMeasure(),
                    isEditMode.value ? fetchPurchaseOrder() : Promise.resolve(),
                ]);
            } catch (error) {
                console.error("Error initializing data:", error);
            } finally {
                isLoading.value = false;
            }
        };

        // Handle form submission
        const submitForm = async (formData) => {
            isSubmitting.value = true;
            serverErrors.value = {};

            try {
                if (isEditMode.value) {
                    await PurchaseOrderService.updatePurchaseOrder(
                        props.id,
                        formData
                    );
                    router.push(`/purchasing/orders/${props.id}`);
                } else {
                    const response =
                        await PurchaseOrderService.createPurchaseOrder(
                            formData
                        );
                    const newPOId =
                        response.data && response.data.data
                            ? response.data.data.po_id
                            : null;
                    router.push(
                        newPOId
                            ? `/purchasing/orders/${newPOId}`
                            : "/purchasing/orders"
                    );
                }
            } catch (error) {
                console.error("Error saving purchase order:", error);

                // Handle validation errors
                if (error.response && error.response.status === 422) {
                    serverErrors.value = error.response.data.errors || {};
                } else if (
                    error.response &&
                    error.response.data &&
                    error.response.data.message
                ) {
                    // Display server message
                    alert(error.response.data.message);
                } else {
                    // Default error message
                    alert("Failed to save purchase order. Please try again.");
                }

                isSubmitting.value = false;
            }
        };

        // Handle cancel
        const cancel = () => {
            router.push(cancelPath.value);
        };

        // Initialize on component mount
        onMounted(() => {
            initializeData();
        });

        return {
            purchaseOrder,
            vendors,
            items,
            unitsOfMeasure,
            isLoading,
            isSubmitting,
            isEditMode,
            serverErrors,
            cancelPath,
            submitForm,
            cancel,
        };
    },
};
</script>

<style scoped>
.po-form-container {
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
