<!-- src/views/purchasing/ContractCreate.vue -->
<template>
    <div class="contract-create-container">
        <div class="page-header">
            <div class="header-left">
                <router-link to="/purchasing/contracts" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to Contracts
                </router-link>
                <h1>Create New Contract</h1>
            </div>
        </div>

        <div v-if="isLoading" class="loading-container">
            <div class="loading-spinner">
                <i class="fas fa-spinner fa-spin"></i>
            </div>
            <p>Loading data...</p>
        </div>

        <div v-else class="form-container">
            <ContractForm
                :vendors="vendors"
                :is-submitting="isSubmitting"
                :server-errors="serverErrors"
                @submit="createContract"
                @cancel="cancel"
            />
        </div>
    </div>
</template>

<script>
import { ref, onMounted } from "vue";
import { useRouter } from "vue-router";
import VendorContractService from "@/services/VendorContractService";
import VendorService from "@/services/VendorService";
import ContractForm from "./ContractForm.vue";

export default {
    name: "ContractCreate",
    components: {
        ContractForm,
    },
    setup() {
        const router = useRouter();
        const vendors = ref([]);
        const isLoading = ref(true);
        const isSubmitting = ref(false);
        const serverErrors = ref({});

        // Fetch vendors for the form dropdown
        const fetchVendors = async () => {
            try {
                const response = await VendorService.getAllVendors({
                    per_page: 100,
                    status: "active",
                });

                if (response.data && response.data.data) {
                    vendors.value = response.data.data;
                }
            } catch (error) {
                console.error("Error fetching vendors:", error);
            } finally {
                isLoading.value = false;
            }
        };

        // Create new contract
        const createContract = async (formData) => {
            isSubmitting.value = true;
            serverErrors.value = {};

            try {
                const response = await VendorContractService.createContract(
                    formData
                );

                // Navigate to the new contract detail page
                const contractId = response.data.data.contract_id;
                router.push(`/purchasing/contracts/${contractId}`);
            } catch (error) {
                console.error("Error creating contract:", error);

                // Handle validation errors
                if (error.response && error.response.status === 422) {
                    serverErrors.value = error.response.data.errors || {};
                } else {
                    // Handle other errors
                    alert("Failed to create contract. Please try again.");
                }

                isSubmitting.value = false;
            }
        };

        // Cancel form and return to list
        const cancel = () => {
            router.push("/purchasing/contracts");
        };

        // Initialize data on component mount
        onMounted(() => {
            fetchVendors();
        });

        return {
            vendors,
            isLoading,
            isSubmitting,
            serverErrors,
            createContract,
            cancel,
        };
    },
};
</script>

<style scoped>
.contract-create-container {
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
