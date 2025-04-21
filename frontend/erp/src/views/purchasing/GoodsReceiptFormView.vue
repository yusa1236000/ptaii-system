<template>
    <div class="receipt-form-container">
        <div class="page-header">
            <div class="header-left">
                <router-link :to="cancelPath" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to
                    {{ isEditMode ? "Goods Receipt" : "Goods Receipts" }}
                </router-link>
                <h1>
                    {{
                        isEditMode
                            ? "Edit Goods Receipt"
                            : "Create Goods Receipt"
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
            <GoodsReceiptForm
                :receipt="receipt"
                :is-edit-mode="isEditMode"
                :is-submitting="isSubmitting"
                :purchase-orders="purchaseOrders"
                :warehouses="warehouses"
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
import GoodsReceiptForm from "./GoodReceiptForm.vue";

export default {
    name: "GoodsReceiptFormView",
    components: {
        GoodsReceiptForm,
    },
    props: {
        id: {
            type: [Number, String],
            default: null,
        },
    },
    setup(props) {
        const router = useRouter();

        // Data
        const receipt = ref(null);
        const purchaseOrders = ref([]);
        const warehouses = ref([]);
        const isLoading = ref(true);
        const isSubmitting = ref(false);
        const serverErrors = ref({});

        // Computed
        const isEditMode = computed(() => !!props.id);

        const cancelPath = computed(() => {
            return isEditMode.value
                ? `/purchasing/goods-receipts/${props.id}`
                : "/purchasing/goods-receipts";
        });

        // Fetch goods receipt data for edit mode
        const fetchGoodsReceipt = async () => {
            if (!props.id) return;

            try {
                const response = await fetch(
                    `/api/goods-receipts/${props.id}`,
                    {
                        headers: {
                            Authorization: `Bearer ${localStorage.getItem(
                                "token"
                            )}`,
                            "Content-Type": "application/json",
                        },
                    }
                );

                const data = await response.json();

                if (data && data.data) {
                    receipt.value = data.data;
                } else {
                    receipt.value = null;
                }
            } catch (error) {
                console.error("Error fetching goods receipt:", error);
                receipt.value = null;
            }
        };

        // Fetch available purchase orders
        const fetchPurchaseOrders = async () => {
            try {
                // Only get POs that are in sent/partial/received status and have remaining items to receive
                const response = await fetch(
                    "/api/purchase-orders?status[]=sent&status[]=partial&status[]=received&per_page=100",
                    {
                        headers: {
                            Authorization: `Bearer ${localStorage.getItem(
                                "token"
                            )}`,
                            "Content-Type": "application/json",
                        },
                    }
                );

                const data = await response.json();

                if (data && data.data) {
                    purchaseOrders.value = data.data;
                } else {
                    purchaseOrders.value = [];
                }
            } catch (error) {
                console.error("Error fetching purchase orders:", error);
                purchaseOrders.value = [];
            }
        };

        // Fetch warehouses
        const fetchWarehouses = async () => {
            try {
                const response = await fetch("/api/warehouses?per_page=100", {
                    headers: {
                        Authorization: `Bearer ${localStorage.getItem(
                            "token"
                        )}`,
                        "Content-Type": "application/json",
                    },
                });

                const data = await response.json();

                if (data && data.data) {
                    warehouses.value = data.data;
                } else {
                    warehouses.value = [];
                }
            } catch (error) {
                console.error("Error fetching warehouses:", error);
                warehouses.value = [];
            }
        };

        // Initialize data
        const initializeData = async () => {
            isLoading.value = true;

            try {
                // Fetch all necessary data in parallel
                await Promise.all([
                    fetchPurchaseOrders(),
                    fetchWarehouses(),
                    isEditMode.value ? fetchGoodsReceipt() : Promise.resolve(),
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
                let response;

                if (isEditMode.value) {
                    // Update existing goods receipt
                    response = await fetch(`/api/goods-receipts/${props.id}`, {
                        method: "PUT",
                        headers: {
                            Authorization: `Bearer ${localStorage.getItem(
                                "token"
                            )}`,
                            "Content-Type": "application/json",
                        },
                        body: JSON.stringify(formData),
                    });
                } else {
                    // Create new goods receipt
                    response = await fetch("/api/goods-receipts", {
                        method: "POST",
                        headers: {
                            Authorization: `Bearer ${localStorage.getItem(
                                "token"
                            )}`,
                            "Content-Type": "application/json",
                        },
                        body: JSON.stringify(formData),
                    });
                }

                const data = await response.json();

                if (!response.ok) {
                    if (response.status === 422) {
                        // Validation errors
                        serverErrors.value = data.errors || {};
                    } else {
                        // Other errors
                        throw new Error(
                            data.message || "Failed to save goods receipt"
                        );
                    }
                } else {
                    // Success - redirect to receipt detail page
                    const receiptId = isEditMode.value
                        ? props.id
                        : data.data.receipt_id;
                    router.push(`/purchasing/goods-receipts/${receiptId}`);
                }
            } catch (error) {
                console.error("Error saving goods receipt:", error);
                alert(
                    error.message ||
                        "An error occurred while saving the goods receipt"
                );
            } finally {
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
            receipt,
            purchaseOrders,
            warehouses,
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
.receipt-form-container {
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
