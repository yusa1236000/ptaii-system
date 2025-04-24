<!-- src/views/purchasing/ContractEdit.vue -->
<template>
    <div class="contract-edit-container">
        <div class="page-header">
            <div class="header-left">
                <router-link
                    :to="`/purchasing/contracts/${id}`"
                    class="back-link"
                >
                    <i class="fas fa-arrow-left"></i> Back to Contract Details
                </router-link>
                <h1>Edit Contract</h1>
            </div>
        </div>

        <div v-if="isLoading" class="loading-container">
            <div class="loading-spinner">
                <i class="fas fa-spinner fa-spin"></i>
            </div>
            <p>Loading contract data...</p>
        </div>

        <div v-else-if="!contract" class="error-container">
            <div class="error-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h2>Contract Not Found</h2>
            <p>
                The requested contract could not be found or may have been
                deleted.
            </p>
            <router-link to="/purchasing/contracts" class="btn btn-primary">
                Return to Contracts List
            </router-link>
        </div>

        <div v-else-if="contract.status !== 'draft'" class="error-container">
            <div class="error-icon">
                <i class="fas fa-ban"></i>
            </div>
            <h2>Cannot Edit Contract</h2>
            <p>
                Only contracts in draft status can be edited. This contract is
                currently in <strong>{{ contract.status }}</strong> status.
            </p>
            <router-link
                :to="`/purchasing/contracts/${id}`"
                class="btn btn-primary"
            >
                Return to Contract Details
            </router-link>
        </div>

        <div v-else class="form-container">
            <ContractForm
                :contract="contract"
                :vendors="vendors"
                :is-edit-mode="true"
                :is-submitting="isSubmitting"
                :server-errors="serverErrors"
                @submit="updateContract"
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
    name: "ContractEdit",
    components: {
        ContractForm,
    },
    props: {
        id: {
            type: [Number, String],
            required: true,
        },
    },
    setup(props) {
        const router = useRouter();
        const contract = ref(null);
        const vendors = ref([]);
        const isLoading = ref(true);
        const isSubmitting = ref(false);
        const serverErrors = ref({});

        // Fetch contract details
        const fetchContractDetails = async () => {
            isLoading.value = true;
            try {
                const response = await VendorContractService.getContractById(
                    props.id
                );
                contract.value = response.data.data || null;
            } catch (error) {
                console.error("Error fetching contract details:", error);
                contract.value = null;
            }
        };

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

        // Initialize data
        const fetchData = async () => {
            await Promise.all([fetchContractDetails(), fetchVendors()]);
            isLoading.value = false;
        };

        // Update contract
        const updateContract = async (formData) => {
            isSubmitting.value = true;
            serverErrors.value = {};

            try {
                await VendorContractService.updateContract(props.id, formData);

                // Navigate back to contract detail page
                router.push(`/purchasing/contracts/${props.id}`);
            } catch (error) {
                console.error("Error updating contract:", error);

                // Handle validation errors
                if (error.response && error.response.status === 422) {
                    serverErrors.value = error.response.data.errors || {};
                } else {
                    // Handle other errors
                    alert("Failed to update contract. Please try again.");
                }

                isSubmitting.value = false;
            }
        };

        // Cancel form
        const cancel = () => {
            router.push(`/purchasing/contracts/${props.id}`);
        };

        // Initialize on component mount
        onMounted(() => {
            fetchData();
        });

        return {
            contract,
            vendors,
            isLoading,
            isSubmitting,
            serverErrors,
            updateContract,
            cancel,
        };
    },
};
</script>

<style scoped>
.contract-edit-container {
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

.error-icon {
    font-size: 3rem;
    color: var(--danger-color);
    margin-bottom: 1rem;
}

.error-container h2 {
    font-size: 1.5rem;
    margin-bottom: 1rem;
    color: var(--gray-800);
}

.error-container p {
    color: var(--gray-600);
    margin-bottom: 1.5rem;
    max-width: 500px;
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
}

.btn-primary {
    background-color: var(--primary-color);
    color: white;
}

.btn-primary:hover {
    background-color: var(--primary-dark);
}
</style>
