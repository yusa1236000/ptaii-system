<!-- src/views/purchasing/VendorEvaluationDetail.vue -->
<template>
    <div class="evaluation-detail-container">
        <div class="page-header">
            <div class="header-left">
                <router-link to="/purchasing/evaluations" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to Evaluations
                </router-link>
                <h1>Vendor Evaluation Details</h1>
            </div>
            <div class="header-actions">
                <router-link
                    :to="`/purchasing/evaluations/${id}/edit`"
                    class="btn btn-primary"
                >
                    <i class="fas fa-edit"></i> Edit
                </router-link>
                <button @click="confirmDelete" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Delete
                </button>
            </div>
        </div>

        <div v-if="isLoading" class="loading-container">
            <div class="loading-spinner">
                <i class="fas fa-spinner fa-spin"></i>
            </div>
            <p>Loading evaluation data...</p>
        </div>

        <div v-else-if="!evaluation" class="error-container">
            <div class="error-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h2>Evaluation Not Found</h2>
            <p>
                The requested evaluation could not be found or may have been
                deleted.
            </p>
            <router-link to="/purchasing/evaluations" class="btn btn-primary">
                Return to Evaluations List
            </router-link>
        </div>

        <div v-else class="evaluation-content">
            <!-- Basic Information Card -->
            <div class="detail-card">
                <div class="card-header">
                    <h2 class="card-title">Evaluation Information</h2>
                    <div class="evaluation-date">
                        {{ formatDate(evaluation.evaluation_date) }}
                    </div>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Vendor</span>
                            <span class="info-value">
                                <router-link
                                    :to="`/purchasing/vendors/${evaluation.vendor.vendor_id}`"
                                >
                                    {{ evaluation.vendor.name }}
                                </router-link>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Vendor Code</span>
                            <span class="info-value">{{
                                evaluation.vendor.vendor_code || "N/A"
                            }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Evaluation ID</span>
                            <span class="info-value">{{
                                evaluation.evaluation_id
                            }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Evaluated On</span>
                            <span class="info-value">{{
                                formatDate(evaluation.evaluation_date)
                            }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Scores Card -->
            <div class="detail-card">
                <div class="card-header">
                    <h2 class="card-title">Performance Scores</h2>
                    <div
                        class="overall-score-badge"
                        :class="getScoreClass(evaluation.total_score)"
                    >
                        {{ evaluation.total_score.toFixed(2) }} / 5
                    </div>
                </div>
                <div class="card-body">
                    <div class="score-categories">
                        <div class="score-category">
                            <div class="category-header">
                                <h3 class="category-title">Quality</h3>
                                <div class="category-score">
                                    {{ evaluation.quality_score }}
                                </div>
                            </div>
                            <div class="score-bar">
                                <div
                                    class="score-fill"
                                    :style="{
                                        width: `${
                                            evaluation.quality_score * 20
                                        }%`,
                                    }"
                                ></div>
                            </div>
                            <div class="score-description">
                                <p>
                                    Assesses the quality of products or services
                                    provided by the vendor.
                                </p>
                                <p>
                                    <strong>Rating:</strong>
                                    {{
                                        getScoreDescription(
                                            evaluation.quality_score
                                        )
                                    }}
                                </p>
                            </div>
                        </div>

                        <div class="score-category">
                            <div class="category-header">
                                <h3 class="category-title">Delivery</h3>
                                <div class="category-score">
                                    {{ evaluation.delivery_score }}
                                </div>
                            </div>
                            <div class="score-bar">
                                <div
                                    class="score-fill"
                                    :style="{
                                        width: `${
                                            evaluation.delivery_score * 20
                                        }%`,
                                    }"
                                ></div>
                            </div>
                            <div class="score-description">
                                <p>
                                    Evaluates timeliness and accuracy of
                                    deliveries.
                                </p>
                                <p>
                                    <strong>Rating:</strong>
                                    {{
                                        getScoreDescription(
                                            evaluation.delivery_score
                                        )
                                    }}
                                </p>
                            </div>
                        </div>

                        <div class="score-category">
                            <div class="category-header">
                                <h3 class="category-title">Price</h3>
                                <div class="category-score">
                                    {{ evaluation.price_score }}
                                </div>
                            </div>
                            <div class="score-bar">
                                <div
                                    class="score-fill"
                                    :style="{
                                        width: `${
                                            evaluation.price_score * 20
                                        }%`,
                                    }"
                                ></div>
                            </div>
                            <div class="score-description">
                                <p>
                                    Evaluates competitiveness of pricing and
                                    value for money.
                                </p>
                                <p>
                                    <strong>Rating:</strong>
                                    {{
                                        getScoreDescription(
                                            evaluation.price_score
                                        )
                                    }}
                                </p>
                            </div>
                        </div>

                        <div class="score-category">
                            <div class="category-header">
                                <h3 class="category-title">Service</h3>
                                <div class="category-score">
                                    {{ evaluation.service_score }}
                                </div>
                            </div>
                            <div class="score-bar">
                                <div
                                    class="score-fill"
                                    :style="{
                                        width: `${
                                            evaluation.service_score * 20
                                        }%`,
                                    }"
                                ></div>
                            </div>
                            <div class="score-description">
                                <p>
                                    Evaluates customer service, responsiveness,
                                    and support.
                                </p>
                                <p>
                                    <strong>Rating:</strong>
                                    {{
                                        getScoreDescription(
                                            evaluation.service_score
                                        )
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="notes-section" v-if="evaluation.notes">
                        <h3 class="notes-title">Notes</h3>
                        <div class="notes-content">
                            {{ evaluation.notes }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Related Orders Card -->
            <div
                class="detail-card"
                v-if="relatedOrders && relatedOrders.length > 0"
            >
                <div class="card-header">
                    <h2 class="card-title">Related Purchase Orders</h2>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>PO Number</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Amount</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="order in relatedOrders"
                                    :key="order.po_id"
                                >
                                    <td>{{ order.po_number }}</td>
                                    <td>{{ formatDate(order.po_date) }}</td>
                                    <td>
                                        <span
                                            :class="[
                                                'status-badge',
                                                getOrderStatusClass(
                                                    order.status
                                                ),
                                            ]"
                                        >
                                            {{ order.status }}
                                        </span>
                                    </td>
                                    <td>
                                        {{ formatCurrency(order.total_amount) }}
                                    </td>
                                    <td>
                                        <router-link
                                            :to="`/purchasing/orders/${order.po_id}`"
                                            class="action-btn"
                                        >
                                            <i class="fas fa-eye"></i>
                                        </router-link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Vendor Performance Card -->
            <div class="detail-card">
                <div class="card-header">
                    <h2 class="card-title">Vendor Performance</h2>
                </div>
                <div class="card-body">
                    <div class="view-performance-link">
                        <router-link
                            :to="`/purchasing/vendors/${evaluation.vendor.vendor_id}/performance`"
                            class="btn btn-secondary"
                        >
                            <i class="fas fa-chart-line"></i> View Vendor
                            Performance Analysis
                        </router-link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Confirmation Modal for Delete -->
        <ConfirmationModal
            v-if="showDeleteModal"
            :title="'Delete Evaluation'"
            :message="'Are you sure you want to delete this vendor evaluation? This action cannot be undone.'"
            :confirm-button-text="'Delete'"
            :confirm-button-class="'btn btn-danger'"
            @confirm="deleteEvaluation"
            @close="closeDeleteModal"
        />
    </div>
</template>

<script>
import { ref, onMounted } from "vue";
import { useRouter } from "vue-router";
import VendorEvaluationService from "@/services/VendorEvaluationService";
import ConfirmationModal from "@/components/common/ConfirmationModal.vue";

export default {
    name: "VendorEvaluationDetail",
    components: {
        ConfirmationModal,
    },
    props: {
        id: {
            type: [Number, String],
            required: true,
        },
    },
    setup(props) {
        const router = useRouter();
        const evaluation = ref(null);
        const relatedOrders = ref([]);
        const isLoading = ref(true);
        const showDeleteModal = ref(false);

        // Fetch evaluation details
        const fetchEvaluationDetails = async () => {
            isLoading.value = true;
            try {
                const response =
                    await VendorEvaluationService.getEvaluationById(props.id);
                evaluation.value = response.data.data || null;

                // If we have a valid evaluation, fetch related orders
                if (evaluation.value && evaluation.value.vendor) {
                    fetchRelatedOrders(evaluation.value.vendor.vendor_id);
                }
            } catch (error) {
                console.error("Error fetching evaluation details:", error);
                evaluation.value = null;
            } finally {
                isLoading.value = false;
            }
        };

        // Fetch related purchase orders
        const fetchRelatedOrders = async (vendorId) => {
            try {
                // Assuming there's a method to get recent purchase orders for a vendor
                const response =
                    await VendorEvaluationService.getVendorPurchaseOrders(
                        vendorId
                    );
                relatedOrders.value = response.data.data || [];
            } catch (error) {
                console.error("Error fetching related purchase orders:", error);
                relatedOrders.value = [];
            }
        };

        // Format date
        const formatDate = (dateString) => {
            if (!dateString) return "N/A";
            const date = new Date(dateString);
            return date.toLocaleDateString("en-US", {
                year: "numeric",
                month: "long",
                day: "numeric",
            });
        };

        // Format currency
        const formatCurrency = (amount) => {
            if (amount === null || amount === undefined) return "N/A";
            return new Intl.NumberFormat("en-US", {
                style: "currency",
                currency: "USD",
            }).format(amount);
        };

        // Get score description based on score value
        const getScoreDescription = (score) => {
            if (score >= 4.5) return "Excellent";
            if (score >= 3.5) return "Very Good";
            if (score >= 2.5) return "Good";
            if (score >= 1.5) return "Fair";
            return "Poor";
        };

        // Get CSS class based on score
        const getScoreClass = (score) => {
            if (score >= 4.5) return "score-excellent";
            if (score >= 3.5) return "score-good";
            if (score >= 2.5) return "score-average";
            if (score >= 1.5) return "score-below-average";
            return "score-poor";
        };

        // Get order status CSS class
        const getOrderStatusClass = (status) => {
            switch (status) {
                case "draft":
                    return "status-draft";
                case "submitted":
                    return "status-pending";
                case "approved":
                    return "status-approved";
                case "sent":
                    return "status-sent";
                case "partial":
                    return "status-partial";
                case "received":
                    return "status-received";
                case "completed":
                    return "status-completed";
                case "canceled":
                    return "status-canceled";
                default:
                    return "status-draft";
            }
        };

        // Confirm delete
        const confirmDelete = () => {
            showDeleteModal.value = true;
        };

        // Close delete modal
        const closeDeleteModal = () => {
            showDeleteModal.value = false;
        };

        // Delete evaluation
        const deleteEvaluation = async () => {
            try {
                await VendorEvaluationService.deleteEvaluation(props.id);
                router.push("/purchasing/evaluations");
            } catch (error) {
                console.error("Error deleting evaluation:", error);
                closeDeleteModal();
            }
        };

        // Initialize
        onMounted(() => {
            fetchEvaluationDetails();
        });

        return {
            evaluation,
            relatedOrders,
            isLoading,
            showDeleteModal,
            formatDate,
            formatCurrency,
            getScoreDescription,
            getScoreClass,
            getOrderStatusClass,
            confirmDelete,
            closeDeleteModal,
            deleteEvaluation,
        };
    },
};
</script>

<style scoped>
.evaluation-detail-container {
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

.header-actions {
    display: flex;
    gap: 0.75rem;
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

.evaluation-content {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.detail-card {
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

.card-title {
    margin: 0;
    font-size: 1.25rem;
    color: var(--gray-800);
}

.evaluation-date {
    font-size: 0.875rem;
    color: var(--gray-600);
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
    font-size: 0.875rem;
    color: var(--gray-500);
}

.info-value {
    font-size: 1rem;
    color: var(--gray-800);
    font-weight: 500;
}

.overall-score-badge {
    font-weight: bold;
    padding: 0.375rem 0.75rem;
    border-radius: 0.375rem;
    font-size: 1rem;
}

.score-categories {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.score-category {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.category-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.category-title {
    margin: 0;
    font-size: 1.125rem;
    color: var(--gray-800);
}

.category-score {
    font-weight: bold;
    font-size: 1.25rem;
    color: var(--primary-color);
}

.score-bar {
    position: relative;
    width: 100%;
    height: 0.75rem;
    background-color: var(--gray-100);
    border-radius: 0.25rem;
    overflow: hidden;
}

.score-fill {
    position: absolute;
    top: 0;
    left: 0;
    height: 100%;
    background-color: var(--primary-color);
    border-radius: 0.25rem;
}

.score-description {
    font-size: 0.875rem;
    color: var(--gray-600);
}

.score-description p {
    margin: 0.5rem 0;
}

.notes-section {
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--gray-200);
}

.notes-title {
    margin: 0 0 1rem 0;
    font-size: 1.125rem;
    color: var(--gray-800);
}

.notes-content {
    font-size: 0.9375rem;
    color: var(--gray-700);
    white-space: pre-line;
}

.view-performance-link {
    display: flex;
    justify-content: center;
    padding: 1rem 0;
}

.table-responsive {
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
}

.data-table th {
    text-align: left;
    padding: 0.75rem;
    background-color: var(--gray-50);
    border-bottom: 1px solid var(--gray-200);
    font-weight: 500;
    color: var(--gray-700);
}

.data-table td {
    padding: 0.75rem;
    border-bottom: 1px solid var(--gray-100);
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

.status-pending {
    background-color: #fef3c7;
    color: #92400e;
}

.status-approved {
    background-color: #dcfce7;
    color: #166534;
}

.status-sent {
    background-color: #dbeafe;
    color: #1e40af;
}

.status-partial {
    background-color: #fef9c3;
    color: #854d0e;
}

.status-received {
    background-color: #d1fae5;
    color: #065f46;
}

.status-completed {
    background-color: #bbf7d0;
    color: #15803d;
}

.status-canceled {
    background-color: #fee2e2;
    color: #b91c1c;
}

.score-excellent {
    background-color: #15803d;
    color: white;
}

.score-good {
    background-color: #65a30d;
    color: white;
}

.score-average {
    background-color: #f59e0b;
    color: white;
}

.score-below-average {
    background-color: #f97316;
    color: white;
}

.score-poor {
    background-color: #dc2626;
    color: white;
}

.action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border-radius: 0.375rem;
    color: var(--primary-color);
    text-decoration: none;
    transition: background-color 0.2s;
}

.action-btn:hover {
    background-color: var(--primary-bg);
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
    text-decoration: none;
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
    color: var(--gray-800);
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

@media (max-width: 768px) {
    .info-grid {
        grid-template-columns: 1fr;
    }

    .header-actions {
        flex-direction: column;
        align-items: flex-end;
        gap: 0.5rem;
    }
}
</style>
