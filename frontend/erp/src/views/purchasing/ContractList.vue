<!-- src/views/purchasing/ContractList.vue -->
<template>
    <div class="contract-list-container">
        <div class="page-header">
            <h1>Vendor Contracts</h1>
            <router-link
                to="/purchasing/contracts/create"
                class="btn btn-primary"
            >
                <i class="fas fa-plus"></i> Create Contract
            </router-link>
        </div>

        <div class="filter-section">
            <SearchFilter
                v-model:value="searchQuery"
                placeholder="Search contract number..."
                @search="fetchContracts"
                @clear="clearSearch"
            >
                <template v-slot:filters>
                    <div class="filter-group">
                        <label for="status-filter">Status</label>
                        <select
                            id="status-filter"
                            v-model="filters.status"
                            @change="fetchContracts"
                        >
                            <option value="">All Status</option>
                            <option value="draft">Draft</option>
                            <option value="active">Active</option>
                            <option value="terminated">Terminated</option>
                            <option value="expired">Expired</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="vendor-filter">Vendor</label>
                        <select
                            id="vendor-filter"
                            v-model="filters.vendor_id"
                            @change="fetchContracts"
                        >
                            <option value="">All Vendors</option>
                            <option
                                v-for="vendor in vendors"
                                :key="vendor.vendor_id"
                                :value="vendor.vendor_id"
                            >
                                {{ vendor.name }}
                            </option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="contract-type">Contract Type</label>
                        <select
                            id="contract-type"
                            v-model="filters.contract_type"
                            @change="fetchContracts"
                        >
                            <option value="">All Types</option>
                            <option value="purchase">Purchase</option>
                            <option value="service">Service</option>
                            <option value="rental">Rental</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                </template>
            </SearchFilter>
        </div>

        <div class="data-container">
            <DataTable
                :columns="columns"
                :items="contracts"
                :is-loading="isLoading"
                :key-field="'contract_id'"
                :initial-sort-key="'start_date'"
                :initial-sort-order="'desc'"
                :empty-title="'No Contracts Found'"
                :empty-message="'Try adjusting your search or filters, or create a new contract.'"
                :empty-icon="'fas fa-file-contract'"
                @sort="handleSort"
            >
                <template v-slot:contract_number="{ value }">
                    <span class="contract-number">{{ value }}</span>
                </template>

                <template v-slot:vendor_name="{ item }">
                    {{ item.vendor ? item.vendor.name : "N/A" }}
                </template>

                <template v-slot:start_date="{ value }">
                    {{ formatDate(value) }}
                </template>

                <template v-slot:end_date="{ value }">
                    {{ formatDate(value) }}
                </template>

                <template v-slot:status="{ value, item }">
                    <span
                        :class="['status-badge', getStatusClass(value, item)]"
                    >
                        {{ formatStatus(value, item) }}
                    </span>
                </template>

                <template v-slot:actions="{ item }">
                    <div class="action-buttons">
                        <router-link
                            :to="`/purchasing/contracts/${item.contract_id}`"
                            class="action-btn view-btn"
                            title="View Details"
                        >
                            <i class="fas fa-eye"></i>
                        </router-link>
                        <router-link
                            v-if="item.status === 'draft'"
                            :to="`/purchasing/contracts/${item.contract_id}/edit`"
                            class="action-btn edit-btn"
                            title="Edit Contract"
                        >
                            <i class="fas fa-edit"></i>
                        </router-link>
                        <button
                            v-if="item.status === 'draft'"
                            @click="confirmActivate(item)"
                            class="action-btn activate-btn"
                            title="Activate Contract"
                        >
                            <i class="fas fa-check-circle"></i>
                        </button>
                        <button
                            v-if="item.status === 'active'"
                            @click="confirmTerminate(item)"
                            class="action-btn terminate-btn"
                            title="Terminate Contract"
                        >
                            <i class="fas fa-ban"></i>
                        </button>
                        <button
                            v-if="item.status === 'draft'"
                            @click="confirmDelete(item)"
                            class="action-btn delete-btn"
                            title="Delete Contract"
                        >
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </template>
            </DataTable>

            <div v-if="totalPages > 1" class="pagination-wrapper">
                <PaginationComponent
                    :current-page="currentPage"
                    :total-pages="totalPages"
                    :from="paginationFrom"
                    :to="paginationTo"
                    :total="totalContracts"
                    @page-changed="changePage"
                />
            </div>
        </div>

        <!-- Confirmation Modal for Delete -->
        <ConfirmationModal
            v-if="showDeleteModal"
            :title="'Delete Contract'"
            :message="
                'Are you sure you want to delete contract <strong>' +
                (selectedContract?.contract_number || '') +
                '</strong>? This action cannot be undone.'
            "
            :confirm-button-text="'Delete'"
            :confirm-button-class="'btn btn-danger'"
            @confirm="deleteContract"
            @close="closeDeleteModal"
        />

        <!-- Confirmation Modal for Activate -->
        <ConfirmationModal
            v-if="showActivateModal"
            :title="'Activate Contract'"
            :message="
                'Are you sure you want to activate contract <strong>' +
                (selectedContract?.contract_number || '') +
                '</strong>? This will make the contract active.'
            "
            :confirm-button-text="'Activate'"
            :confirm-button-class="'btn btn-success'"
            @confirm="activateContract"
            @close="closeActivateModal"
        />

        <!-- Confirmation Modal for Terminate -->
        <div v-if="showTerminateModal" class="modal">
            <div class="modal-backdrop" @click="closeTerminateModal"></div>
            <div class="modal-content modal-sm">
                <div class="modal-header">
                    <h2>Terminate Contract</h2>
                    <button class="close-btn" @click="closeTerminateModal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <p>
                        Are you sure you want to terminate contract
                        <strong>{{ selectedContract?.contract_number }}</strong
                        >?
                    </p>
                    <div class="form-group">
                        <label for="termination_date">Termination Date*</label>
                        <input
                            type="date"
                            id="termination_date"
                            v-model="terminationDate"
                            class="form-control"
                            :min="getCurrentDate()"
                            required
                        />
                        <div v-if="terminationDateError" class="error-message">
                            {{ terminationDateError }}
                        </div>
                    </div>
                    <div class="form-actions">
                        <button
                            type="button"
                            class="btn btn-secondary"
                            @click="closeTerminateModal"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            class="btn btn-danger"
                            @click="terminateContract"
                        >
                            Terminate
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, reactive, computed, onMounted } from "vue";
//import { useRouter } from "vue-router";
import VendorContractService from "@/services/VendorContractService";
import VendorService from "@/services/VendorService";
import DataTable from "@/components/common/DataTable.vue";
import SearchFilter from "@/components/common/SearchFilter.vue";
import PaginationComponent from "@/components/common/Pagination.vue";
import ConfirmationModal from "@/components/common/ConfirmationModal.vue";

export default {
    name: "ContractList",
    components: {
        DataTable,
        SearchFilter,
        PaginationComponent,
        ConfirmationModal,
    },
    setup() {
        //const router = useRouter();
        const contracts = ref([]);
        const vendors = ref([]);
        const isLoading = ref(true);
        const currentPage = ref(1);
        const totalPages = ref(1);
        const totalContracts = ref(0);
        const itemsPerPage = ref(10);
        const searchQuery = ref("");
        const showDeleteModal = ref(false);
        const showActivateModal = ref(false);
        const showTerminateModal = ref(false);
        const selectedContract = ref(null);
        const terminationDate = ref("");
        const terminationDateError = ref("");

        const filters = reactive({
            status: "",
            vendor_id: "",
            contract_type: "",
        });

        const sortKey = ref("start_date");
        const sortOrder = ref("desc");

        // Table columns definition
        const columns = [
            {
                key: "contract_number",
                label: "Contract #",
                sortable: true,
                template: "contract_number",
            },
            {
                key: "vendor_name",
                label: "Vendor",
                sortable: false,
                template: "vendor_name",
            },
            { key: "contract_type", label: "Type", sortable: true },
            {
                key: "start_date",
                label: "Start Date",
                sortable: true,
                template: "start_date",
            },
            {
                key: "end_date",
                label: "End Date",
                sortable: true,
                template: "end_date",
            },
            {
                key: "status",
                label: "Status",
                sortable: true,
                template: "status",
            },
        ];

        // Computed pagination values
        const paginationFrom = computed(() => {
            return (currentPage.value - 1) * itemsPerPage.value + 1;
        });

        const paginationTo = computed(() => {
            return Math.min(
                currentPage.value * itemsPerPage.value,
                totalContracts.value
            );
        });

        // Fetch contracts from API
        const fetchContracts = async () => {
            isLoading.value = true;

            try {
                const params = {
                    page: currentPage.value,
                    per_page: itemsPerPage.value,
                    search: searchQuery.value,
                    status: filters.status,
                    vendor_id: filters.vendor_id,
                    contract_type: filters.contract_type,
                    sort_field: sortKey.value,
                    sort_direction: sortOrder.value,
                };

                const response = await VendorContractService.getAllContracts(
                    params
                );

                if (response.data && response.data.data) {
                    contracts.value = response.data.data;

                    if (response.data.meta) {
                        totalContracts.value = response.data.meta.total || 0;
                        totalPages.value = response.data.meta.last_page || 1;
                        currentPage.value =
                            response.data.meta.current_page || 1;
                    }
                } else {
                    contracts.value = [];
                    totalContracts.value = 0;
                    totalPages.value = 1;
                }
            } catch (error) {
                console.error("Error fetching contracts:", error);
                contracts.value = [];
            } finally {
                isLoading.value = false;
            }
        };

        // Fetch vendors for the filter dropdown
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
            }
        };

        const clearSearch = () => {
            searchQuery.value = "";
            fetchContracts();
        };

        const handleSort = ({ key, order }) => {
            sortKey.value = key;
            sortOrder.value = order;
            fetchContracts();
        };

        const changePage = (page) => {
            currentPage.value = page;
            fetchContracts();
        };

        const formatDate = (dateString) => {
            if (!dateString) return "N/A";
            const date = new Date(dateString);
            return date.toLocaleDateString("en-US", {
                year: "numeric",
                month: "short",
                day: "2-digit",
            });
        };

        const getCurrentDate = () => {
            const now = new Date();
            return now.toISOString().slice(0, 10);
        };

        const formatStatus = (status, item) => {
            // Check if contract is expired based on end_date
            if (status === "active" && item.end_date) {
                const today = new Date();
                const endDate = new Date(item.end_date);
                if (endDate < today) {
                    return "Expired";
                }
            }

            switch (status) {
                case "draft":
                    return "Draft";
                case "active":
                    return "Active";
                case "terminated":
                    return "Terminated";
                default:
                    return status;
            }
        };

        const getStatusClass = (status, item) => {
            // Check if contract is expired
            if (status === "active" && item.end_date) {
                const today = new Date();
                const endDate = new Date(item.end_date);
                if (endDate < today) {
                    return "status-expired";
                }
            }

            switch (status) {
                case "draft":
                    return "status-draft";
                case "active":
                    return "status-active";
                case "terminated":
                    return "status-terminated";
                default:
                    return "status-draft";
            }
        };

        // Delete Contract
        const confirmDelete = (contract) => {
            selectedContract.value = contract;
            showDeleteModal.value = true;
        };

        const closeDeleteModal = () => {
            showDeleteModal.value = false;
            selectedContract.value = null;
        };

        const deleteContract = async () => {
            if (!selectedContract.value) return;

            try {
                await VendorContractService.deleteContract(
                    selectedContract.value.contract_id
                );
                fetchContracts();
                closeDeleteModal();
            } catch (error) {
                if (error.response && error.response.status === 400) {
                    alert(
                        error.response.data.message ||
                            "This contract cannot be deleted."
                    );
                } else {
                    console.error("Error deleting contract:", error);
                }
                closeDeleteModal();
            }
        };

        // Activate Contract
        const confirmActivate = (contract) => {
            selectedContract.value = contract;
            showActivateModal.value = true;
        };

        const closeActivateModal = () => {
            showActivateModal.value = false;
            selectedContract.value = null;
        };

        const activateContract = async () => {
            if (!selectedContract.value) return;

            try {
                await VendorContractService.activateContract(
                    selectedContract.value.contract_id
                );
                fetchContracts();
                closeActivateModal();
            } catch (error) {
                if (
                    error.response &&
                    error.response.data &&
                    error.response.data.message
                ) {
                    alert(error.response.data.message);
                } else {
                    console.error("Error activating contract:", error);
                }
                closeActivateModal();
            }
        };

        // Terminate Contract
        const confirmTerminate = (contract) => {
            selectedContract.value = contract;
            terminationDate.value = getCurrentDate();
            terminationDateError.value = "";
            showTerminateModal.value = true;
        };

        const closeTerminateModal = () => {
            showTerminateModal.value = false;
            selectedContract.value = null;
            terminationDate.value = "";
            terminationDateError.value = "";
        };

        const terminateContract = async () => {
            if (!selectedContract.value) return;

            // Validate termination date
            if (!terminationDate.value) {
                terminationDateError.value = "Termination date is required";
                return;
            }

            try {
                await VendorContractService.terminateContract(
                    selectedContract.value.contract_id,
                    { termination_date: terminationDate.value }
                );
                fetchContracts();
                closeTerminateModal();
            } catch (error) {
                if (
                    error.response &&
                    error.response.data &&
                    error.response.data.message
                ) {
                    terminationDateError.value = error.response.data.message;
                } else {
                    console.error("Error terminating contract:", error);
                    terminationDateError.value =
                        "An error occurred while terminating the contract";
                }
            }
        };

        // Initialize
        onMounted(() => {
            fetchContracts();
            fetchVendors();
        });

        return {
            contracts,
            vendors,
            isLoading,
            columns,
            currentPage,
            totalPages,
            totalContracts,
            paginationFrom,
            paginationTo,
            searchQuery,
            filters,
            showDeleteModal,
            showActivateModal,
            showTerminateModal,
            selectedContract,
            terminationDate,
            terminationDateError,
            fetchContracts,
            clearSearch,
            handleSort,
            changePage,
            formatDate,
            formatStatus,
            getStatusClass,
            confirmDelete,
            closeDeleteModal,
            deleteContract,
            confirmActivate,
            closeActivateModal,
            activateContract,
            confirmTerminate,
            closeTerminateModal,
            terminateContract,
            getCurrentDate,
        };
    },
};
</script>

<style scoped>
.contract-list-container {
    padding: 1rem;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.page-header h1 {
    margin: 0;
    font-size: 1.5rem;
    color: var(--gray-800);
}

.filter-section {
    margin-bottom: 1.5rem;
}

.data-container {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.pagination-wrapper {
    padding: 0.5rem;
    background-color: white;
    border-top: 1px solid var(--gray-200);
}

.contract-number {
    font-weight: 500;
    color: var(--primary-color);
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
}

.action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border-radius: 0.375rem;
    border: none;
    background: none;
    cursor: pointer;
    transition: background-color 0.2s;
}

.view-btn {
    color: var(--primary-color);
}

.view-btn:hover {
    background-color: var(--primary-bg);
}

.edit-btn {
    color: var(--warning-color);
}

.edit-btn:hover {
    background-color: var(--warning-bg);
}

.delete-btn {
    color: var(--danger-color);
}

.delete-btn:hover {
    background-color: var(--danger-bg);
}

.activate-btn {
    color: var(--success-color);
}

.activate-btn:hover {
    background-color: var(--success-bg);
}

.terminate-btn {
    color: var(--danger-color);
}

.terminate-btn:hover {
    background-color: var(--danger-bg);
}

.status-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
}

.status-draft {
    background-color: var(--gray-100);
    color: var(--gray-700);
}

.status-active {
    background-color: var(--success-bg);
    color: var(--success-color);
}

.status-terminated {
    background-color: var(--danger-bg);
    color: var(--danger-color);
}

.status-expired {
    background-color: var(--warning-bg);
    color: var(--warning-color);
}

.modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 50;
    display: flex;
    justify-content: center;
    align-items: center;
}

.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 50;
}

.modal-content {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    width: 100%;
    z-index: 60;
    overflow: hidden;
}

.modal-sm {
    max-width: 400px;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.modal-header h2 {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
    color: #1e293b;
}

.close-btn {
    background: none;
    border: none;
    color: #64748b;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.25rem;
    border-radius: 0.25rem;
}

.close-btn:hover {
    background-color: #f1f5f9;
    color: #0f172a;
}

.modal-body {
    padding: 1.5rem;
}

.modal-body p {
    margin-top: 0;
    margin-bottom: 1.5rem;
    color: #1e293b;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #334155;
    font-size: 0.875rem;
}

.form-control {
    width: 100%;
    padding: 0.625rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    background-color: white;
    transition: border-color 0.2s;
}

.form-control:focus {
    border-color: #2563eb;
    outline: none;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.error-message {
    margin-top: 0.375rem;
    font-size: 0.75rem;
    color: #dc2626;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 1.5rem;
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

.btn-secondary {
    background-color: var(--gray-200);
    color: var(--gray-700);
}

.btn-secondary:hover {
    background-color: var(--gray-300);
}

.btn-danger {
    background-color: var(--danger-color);
    color: white;
}

.btn-danger:hover {
    background-color: #b91c1c;
}

.btn-success {
    background-color: var(--success-color);
    color: white;
}

.btn-success:hover {
    background-color: #15803d;
}
</style>
