<!-- src/views/manufacturing/BOMDetail.vue -->
<template>
    <div class="bom-detail">
        <div class="page-header">
            <div class="header-left">
                <button class="btn btn-secondary btn-sm" @click="goBack">
                    <i class="fas fa-arrow-left"></i> Back to List
                </button>
                <h1 v-if="bom">{{ bom.bom_code }} - {{ bom.product?.name }}</h1>
            </div>
            <div class="header-actions" v-if="bom">
                <button class="btn btn-primary" @click="editBOM">
                    <i class="fas fa-edit"></i> Edit BOM
                </button>
            </div>
        </div>

        <div v-if="isLoading" class="loading-container">
            <i class="fas fa-spinner fa-spin"></i> Loading BOM details...
        </div>

        <div v-else-if="!bom" class="not-found">
            <div class="not-found-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h2>BOM Not Found</h2>
            <p>
                The BOM you're looking for doesn't exist or you don't have
                permission to view it.
            </p>
            <button class="btn btn-primary" @click="goBack">
                Go Back to List
            </button>
        </div>

        <div v-else class="bom-container">
            <!-- BOM Header Information -->
            <div class="card bom-info">
                <div class="card-header">
                    <h3>BOM Information</h3>
                    <div
                        v-if="bom.status"
                        class="status-badge"
                        :class="getStatusClass(bom.status)"
                    >
                        {{ bom.status }}
                    </div>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">BOM Code</div>
                            <div class="info-value">{{ bom.bom_code }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Product</div>
                            <div class="info-value">
                                {{ bom.product?.name }}
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Revision</div>
                            <div class="info-value">{{ bom.revision }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Effective Date</div>
                            <div class="info-value">
                                {{ formatDate(bom.effective_date) }}
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Standard Quantity</div>
                            <div class="info-value">
                                {{ bom.standard_quantity }}
                                {{ bom.unitOfMeasure?.symbol || "" }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- BOM Lines Section -->
            <div class="card bom-lines">
                <div class="card-header">
                    <h3>BOM Components</h3>
                    <button
                        v-if="bom.status !== 'Obsolete'"
                        class="btn btn-primary btn-sm"
                        @click="openAddLineModal"
                    >
                        <i class="fas fa-plus"></i> Add Component
                    </button>
                </div>
                <div class="card-body">
                    <div v-if="isLoadingLines" class="loading-lines">
                        <i class="fas fa-spinner fa-spin"></i> Loading
                        components...
                    </div>

                    <div v-else-if="bomLines.length === 0" class="empty-lines">
                        <p>No components found for this BOM.</p>
                        <button
                            v-if="bom.status !== 'Obsolete'"
                            class="btn btn-primary"
                            @click="openAddLineModal"
                        >
                            Add Component
                        </button>
                    </div>

                    <table v-else class="data-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Quantity</th>
                                <th>UOM</th>
                                <th>Critical</th>
                                <th>Notes</th>
                                <th v-if="bom.status !== 'Obsolete'">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="line in bomLines" :key="line.line_id">
                                <td>
                                    <div class="item-name">
                                        {{ line.item.name }}
                                    </div>
                                    <div class="item-code">
                                        {{ line.item.item_code }}
                                    </div>
                                </td>
                                <td>{{ line.quantity }}</td>
                                <td>{{ line.unitOfMeasure?.symbol || "" }}</td>
                                <td>
                                    <span
                                        v-if="line.is_critical"
                                        class="critical-badge"
                                    >
                                        <i
                                            class="fas fa-exclamation-circle"
                                        ></i>
                                        Critical
                                    </span>
                                    <span v-else>No</span>
                                </td>
                                <td>{{ line.notes || "-" }}</td>
                                <td
                                    v-if="bom.status !== 'Obsolete'"
                                    class="actions-cell"
                                >
                                    <button
                                        class="action-btn"
                                        title="Edit Component"
                                        @click="editLine(line)"
                                    >
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button
                                        class="action-btn"
                                        title="Delete Component"
                                        @click="confirmDeleteLine(line)"
                                    >
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- BOM Form Modal -->
        <BOMFormModal
            v-if="showBOMModal"
            :is-edit-mode="true"
            :bom-data="currentBOM"
            @close="closeBOMModal"
            @save="saveBOM"
        />

        <!-- BOM Line Form Modal -->
        <BOMLineFormModal
            v-if="showLineModal"
            :is-edit-mode="isEditingLine"
            :line-data="currentLine"
            :bom-id="bomId"
            @close="closeLineModal"
            @save="saveLine"
        />

        <!-- Delete Confirmation Modal -->
        <ConfirmationModal
            v-if="showDeleteModal"
            title="Confirm Delete"
            :message="`Are you sure you want to delete this component?<br>This action cannot be undone.`"
            confirm-button-text="Delete"
            confirm-button-class="btn btn-danger"
            @confirm="deleteLine"
            @close="closeDeleteModal"
        />
    </div>
</template>

<script>
import { ref, computed, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import BOMService from "@/services/BOMService";
import BOMFormModal from "@/components/manufacturing/BOMFormModal.vue";
import BOMLineFormModal from "@/components/manufacturing/BOMLineFormModal.vue";
import ConfirmationModal from "@/components/common/ConfirmationModal.vue";

export default {
    name: "BOMDetail",
    components: {
        BOMFormModal,
        BOMLineFormModal,
        ConfirmationModal,
    },
    setup() {
        const route = useRoute();
        const router = useRouter();
        const bomId = Number(route.params.id);

        const bom = ref(null);
        const bomLines = ref([]);
        const isLoading = ref(true);
        const isLoadingLines = ref(true);

        // Modals
        const showBOMModal = ref(false);
        const showLineModal = ref(false);
        const showDeleteModal = ref(false);
        const isEditingLine = ref(false);
        const currentBOM = ref({});
        const currentLine = ref({});
        const lineToDelete = ref(null);

        const fetchBOM = async () => {
            isLoading.value = true;
            try {
                const response = await BOMService.getBOMById(bomId);
                bom.value = response.data;
                fetchBOMLines();
            } catch (error) {
                console.error("Error fetching BOM:", error);
                bom.value = null;
                isLoading.value = false;
            }
        };

        const fetchBOMLines = async () => {
            isLoadingLines.value = true;
            try {
                const response = await BOMService.getBOMLines(bomId);
                bomLines.value = response.data || [];
            } catch (error) {
                console.error("Error fetching BOM lines:", error);
                bomLines.value = [];
            } finally {
                isLoadingLines.value = false;
                isLoading.value = false;
            }
        };

        const formatDate = (dateString) => {
            if (!dateString) return "-";

            const date = new Date(dateString);
            return date.toLocaleDateString("en-US", {
                year: "numeric",
                month: "short",
                day: "numeric",
            });
        };

        const getStatusClass = (status) => {
            switch (status) {
                case "Active":
                    return "status-active";
                case "Draft":
                    return "status-draft";
                case "Obsolete":
                    return "status-obsolete";
                default:
                    return "";
            }
        };

        const goBack = () => {
            router.push("/manufacturing/boms");
        };

        const editBOM = () => {
            currentBOM.value = { ...bom.value };
            showBOMModal.value = true;
        };

        const closeBOMModal = () => {
            showBOMModal.value = false;
        };

        const saveBOM = async (bomData) => {
            try {
                await BOMService.updateBOM(bomId, bomData);
                // Refresh BOM data
                fetchBOM();
                closeBOMModal();
            } catch (error) {
                console.error("Error updating BOM:", error);
                alert("Failed to update BOM. Please try again.");
            }
        };

        const openAddLineModal = () => {
            isEditingLine.value = false;
            currentLine.value = {
                bom_id: bomId,
                item_id: "",
                quantity: 1,
                uom_id: "",
                is_critical: false,
                notes: "",
            };
            showLineModal.value = true;
        };

        const editLine = (line) => {
            isEditingLine.value = true;
            currentLine.value = { ...line };
            showLineModal.value = true;
        };

        const closeLineModal = () => {
            showLineModal.value = false;
        };

        const saveLine = async (lineData) => {
            try {
                if (isEditingLine.value) {
                    await BOMService.updateBOMLine(
                        bomId,
                        lineData.line_id,
                        lineData
                    );
                } else {
                    await BOMService.createBOMLine(bomId, lineData);
                }
                // Refresh BOM lines
                fetchBOMLines();
                closeLineModal();
            } catch (error) {
                console.error("Error saving BOM line:", error);
                alert("Failed to save component. Please try again.");
            }
        };

        const confirmDeleteLine = (line) => {
            lineToDelete.value = line;
            showDeleteModal.value = true;
        };

        const closeDeleteModal = () => {
            showDeleteModal.value = false;
        };

        const deleteLine = async () => {
            try {
                await BOMService.deleteBOMLine(
                    bomId,
                    lineToDelete.value.line_id
                );
                // Refresh BOM lines
                fetchBOMLines();
                closeDeleteModal();
            } catch (error) {
                console.error("Error deleting BOM line:", error);
                alert("Failed to delete component. Please try again.");
                closeDeleteModal();
            }
        };

        onMounted(() => {
            fetchBOM();
        });

        return {
            bomId,
            bom,
            bomLines,
            isLoading,
            isLoadingLines,
            showBOMModal,
            showLineModal,
            showDeleteModal,
            isEditingLine,
            currentBOM,
            currentLine,
            formatDate,
            getStatusClass,
            goBack,
            editBOM,
            closeBOMModal,
            saveBOM,
            openAddLineModal,
            editLine,
            closeLineModal,
            saveLine,
            confirmDeleteLine,
            closeDeleteModal,
            deleteLine,
        };
    },
};
</script>

<style scoped>
.bom-detail {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.page-header h1 {
    margin: 0;
    font-size: 1.5rem;
    color: var(--gray-800);
}

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
}

.loading-container,
.not-found {
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

.loading-container i {
    font-size: 2rem;
    color: var(--primary-color);
    margin-bottom: 1rem;
}

.not-found-icon {
    font-size: 3rem;
    color: var(--warning-color);
    margin-bottom: 1rem;
}

.not-found h2 {
    margin-bottom: 1rem;
    color: var(--gray-800);
}

.not-found p {
    margin-bottom: 1.5rem;
    color: var(--gray-600);
}

.bom-container {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.card {
    background-color: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    background-color: var(--gray-50);
    border-bottom: 1px solid var(--gray-200);
}

.card-header h3 {
    margin: 0;
    font-size: 1.125rem;
    color: var(--gray-800);
}

.card-body {
    padding: 1.5rem;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1.5rem;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.info-label {
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--gray-500);
    text-transform: uppercase;
}

.info-value {
    font-size: 0.875rem;
    color: var(--gray-800);
}

.status-badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
}

.status-active {
    background-color: var(--success-bg);
    color: var(--success-color);
}

.status-draft {
    background-color: var(--warning-bg);
    color: var(--warning-color);
}

.status-obsolete {
    background-color: var(--danger-bg);
    color: var(--danger-color);
}

.loading-lines {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 2rem;
    color: var(--gray-500);
}

.loading-lines i {
    margin-right: 0.5rem;
}

.empty-lines {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem 2rem;
    text-align: center;
    color: var(--gray-500);
}

.empty-lines p {
    margin-bottom: 1rem;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
}

.data-table th {
    text-align: left;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--gray-200);
    font-weight: 600;
    color: var(--gray-700);
    background-color: var(--gray-50);
}

.data-table td {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--gray-100);
    color: var(--gray-800);
    vertical-align: middle;
}

.item-name {
    font-weight: 500;
    color: var(--gray-800);
}

.item-code {
    font-size: 0.75rem;
    color: var(--gray-500);
    margin-top: 0.25rem;
}

.critical-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.5rem;
    background-color: var(--danger-bg);
    color: var(--danger-color);
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
}

.actions-cell {
    display: flex;
    gap: 0.5rem;
}

.action-btn {
    background: none;
    border: none;
    color: var(--gray-500);
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 0.25rem;
    transition: background-color 0.2s;
}

.action-btn:hover {
    background-color: var(--gray-100);
    color: var(--gray-700);
}

.btn {
    padding: 0.625rem 1rem;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    border: none;
    transition: all 0.2s;
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

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }

    .header-actions {
        align-self: flex-end;
    }

    .info-grid {
        grid-template-columns: 1fr;
    }
}
</style>
