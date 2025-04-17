<!-- src/views/purchasing/CreatePOFromQuotation.vue -->
<template>
    <div class="create-po-from-quotation-container">
        <div class="page-header">
            <div class="header-left">
                <router-link to="/purchasing/orders" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to Purchase Orders
                </router-link>
                <h1>Create Purchase Order from Quotation</h1>
            </div>
        </div>

        <div v-if="isLoading" class="loading-container">
            <div class="loading-spinner">
                <i class="fas fa-spinner fa-spin"></i>
            </div>
            <p>Loading quotation data...</p>
        </div>

        <div v-else-if="!quotation" class="error-container">
            <div class="error-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h2>Quotation Not Found</h2>
            <p>
                The requested quotation could not be found or may have been
                deleted.
            </p>
            <router-link to="/purchasing/quotations" class="btn btn-primary">
                View All Quotations
            </router-link>
        </div>

        <div
            v-else-if="quotation.status !== 'accepted'"
            class="error-container"
        >
            <div class="error-icon">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <h2>Cannot Create Purchase Order</h2>
            <p>Purchase Orders can only be created from accepted quotations.</p>
            <p>
                Current quotation status:
                <span
                    :class="['status-badge', getStatusClass(quotation.status)]"
                    >{{ quotation.status }}</span
                >
            </p>
            <div class="action-buttons">
                <router-link
                    :to="`/purchasing/quotations/${id}`"
                    class="btn btn-secondary"
                >
                    View Quotation
                </router-link>
                <router-link
                    to="/purchasing/orders/create"
                    class="btn btn-primary"
                >
                    Create New Purchase Order
                </router-link>
            </div>
        </div>

        <div v-else class="content-container">
            <div class="detail-card">
                <div class="card-header">
                    <h2 class="card-title">Quotation Details</h2>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Quotation From</span>
                            <span class="info-value">{{
                                quotation.vendor.name
                            }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Quotation Date</span>
                            <span class="info-value">{{
                                formatDate(quotation.quotation_date)
                            }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Valid Until</span>
                            <span class="info-value">{{
                                formatDate(quotation.validity_date)
                            }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">RFQ Number</span>
                            <span class="info-value">{{
                                quotation.requestForQuotation?.rfq_number ||
                                "N/A"
                            }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="detail-card">
                <div class="card-header">
                    <h2 class="card-title">Items from Quotation</h2>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Item</th>
                                    <th>Quantity</th>
                                    <th>Unit</th>
                                    <th>Unit Price</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(line, index) in quotation.lines"
                                    :key="line.line_id"
                                >
                                    <td>{{ index + 1 }}</td>
                                    <td>
                                        <div class="item-name">
                                            {{ line.item.name }}
                                        </div>
                                        <div class="item-code">
                                            {{ line.item.item_code }}
                                        </div>
                                    </td>
                                    <td>{{ line.quantity }}</td>
                                    <td>{{ line.unitOfMeasure.name }}</td>
                                    <td>
                                        {{ formatCurrency(line.unit_price) }}
                                    </td>
                                    <td>
                                        {{
                                            formatCurrency(
                                                line.quantity * line.unit_price
                                            )
                                        }}
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5" class="text-right">
                                        <strong>Total Amount:</strong>
                                    </td>
                                    <td>
                                        {{ formatCurrency(calculateTotal()) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <div class="purchase-order-options">
                <div class="detail-card">
                    <div class="card-header">
                        <h2 class="card-title">Purchase Order Options</h2>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="po_date">PO Date</label>
                            <input
                                type="date"
                                id="po_date"
                                v-model="poOptions.po_date"
                                :min="today"
                            />
                        </div>
                        <div class="form-group">
                            <label for="expected_delivery"
                                >Expected Delivery Date</label
                            >
                            <input
                                type="date"
                                id="expected_delivery"
                                v-model="poOptions.expected_delivery"
                                :min="today"
                            />
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="payment_terms">Payment Terms</label>
                                <select
                                    id="payment_terms"
                                    v-model="poOptions.payment_terms"
                                >
                                    <option value="">
                                        Select Payment Terms
                                    </option>
                                    <option value="Net 30">Net 30</option>
                                    <option value="Net 60">Net 60</option>
                                    <option value="Net 90">Net 90</option>
                                    <option value="Immediate">Immediate</option>
                                    <option value="Advance">Advance</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="delivery_terms"
                                    >Delivery Terms</label
                                >
                                <select
                                    id="delivery_terms"
                                    v-model="poOptions.delivery_terms"
                                >
                                    <option value="">
                                        Select Delivery Terms
                                    </option>
                                    <option value="FOB">
                                        FOB (Free on Board)
                                    </option>
                                    <option value="CIF">
                                        CIF (Cost, Insurance & Freight)
                                    </option>
                                    <option value="DDP">
                                        DDP (Delivered Duty Paid)
                                    </option>
                                    <option value="EXW">EXW (Ex Works)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-secondary" @click="cancel">
                    Cancel
                </button>
                <button
                    type="button"
                    class="btn btn-primary"
                    @click="createPurchaseOrder"
                    :disabled="isCreating"
                >
                    {{ isCreating ? "Creating..." : "Create Purchase Order" }}
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import PurchaseOrderService from "@/services/PurchaseOrderService";

export default {
    name: "CreatePOFromQuotation",
    props: {
        id: {
            type: [Number, String],
            required: true,
        },
    },
    setup(props) {
        const router = useRouter();
        const quotation = ref(null);
        const isLoading = ref(true);
        const isCreating = ref(false);

        const today = new Date().toISOString().slice(0, 10);

        const poOptions = ref({
            po_date: today,
            expected_delivery: "",
            payment_terms: "",
            delivery_terms: "",
        });

        // Fetch quotation details
        const fetchQuotation = async () => {
            isLoading.value = true;
            try {
                const response = await PurchaseOrderService.getVendorQuotation(
                    props.id
                );
                quotation.value = response.data.data || null;
            } catch (error) {
                console.error("Error fetching quotation details:", error);
                quotation.value = null;
            } finally {
                isLoading.value = false;
            }
        };

        // Calculate total amount from quotation lines
        const calculateTotal = () => {
            if (!quotation.value || !quotation.value.lines) return 0;

            return quotation.value.lines.reduce((total, line) => {
                return total + line.unit_price * line.quantity;
            }, 0);
        };

        // Format date strings
        const formatDate = (dateString) => {
            if (!dateString) return "N/A";
            const date = new Date(dateString);
            return date.toLocaleDateString("en-US", {
                year: "numeric",
                month: "short",
                day: "2-digit",
            });
        };

        // Format currency
        const formatCurrency = (amount) => {
            if (amount === null || amount === undefined) return "$0.00";
            return new Intl.NumberFormat("en-US", {
                style: "currency",
                currency: "USD",
            }).format(amount);
        };

        // Get status CSS class
        const getStatusClass = (status) => {
            switch (status) {
                case "received":
                    return "status-draft";
                case "accepted":
                    return "status-approved";
                case "rejected":
                    return "status-canceled";
                default:
                    return "status-draft";
            }
        };

        // Create purchase order from quotation
        const createPurchaseOrder = async () => {
            if (!quotation.value) return;

            isCreating.value = true;

            try {
                const response = await PurchaseOrderService.createFromQuotation(
                    props.id
                );

                // Get the new PO ID
                const newPOId =
                    response.data && response.data.data
                        ? response.data.data.po_id
                        : null;

                if (newPOId) {
                    // Update the PO with the additional options
                    await PurchaseOrderService.updatePurchaseOrder(newPOId, {
                        po_date: poOptions.value.po_date,
                        expected_delivery: poOptions.value.expected_delivery,
                        payment_terms: poOptions.value.payment_terms,
                        delivery_terms: poOptions.value.delivery_terms,
                    });

                    // Navigate to the new PO
                    router.push(`/purchasing/orders/${newPOId}`);
                } else {
                    router.push("/purchasing/orders");
                }
            } catch (error) {
                console.error(
                    "Error creating purchase order from quotation:",
                    error
                );

                if (
                    error.response &&
                    error.response.data &&
                    error.response.data.message
                ) {
                    alert(error.response.data.message);
                } else {
                    alert("Failed to create purchase order. Please try again.");
                }

                isCreating.value = false;
            }
        };

        // Cancel and return to quotations list
        const cancel = () => {
            router.push("/purchasing/quotations");
        };

        // Initialize
        onMounted(() => {
            fetchQuotation();
        });

        return {
            quotation,
            isLoading,
            isCreating,
            poOptions,
            today,
            calculateTotal,
            formatDate,
            formatCurrency,
            getStatusClass,
            createPurchaseOrder,
            cancel,
        };
    },
};
</script>

<style scoped>
.create-po-from-quotation-container {
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

.error-container h2 {
    margin-top: 0;
}

.action-buttons {
    display: flex;
    gap: 1rem;
    margin-top: 1.5rem;
}

.content-container {
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

.table-responsive {
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
}

.data-table th {
    padding: 0.75rem;
    text-align: left;
    background-color: var(--gray-50);
    border-bottom: 1px solid var(--gray-200);
    font-weight: 500;
    color: var(--gray-700);
}

.data-table td {
    padding: 0.75rem;
    border-bottom: 1px solid var(--gray-100);
}

.data-table tbody tr:hover td {
    background-color: var(--gray-50);
}

.data-table tfoot td {
    padding: 0.75rem;
    border-top: 1px solid var(--gray-200);
}

.text-right {
    text-align: right;
}

.item-name {
    font-weight: 500;
}

.item-code {
    font-size: 0.75rem;
    color: var(--gray-500);
}

.form-group {
    margin-bottom: 1.25rem;
}

.form-group:last-child {
    margin-bottom: 0;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.25rem;
}

label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #334155;
    font-size: 0.875rem;
}

input[type="date"],
select {
    width: 100%;
    padding: 0.625rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    background-color: white;
    transition: border-color 0.2s;
}

input[type="date"]:focus,
select:focus {
    border-color: #2563eb;
    outline: none;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 1rem;
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

.btn-primary:hover:not(:disabled) {
    background-color: var(--primary-dark);
}

.btn-primary:disabled {
    background-color: #93c5fd;
    cursor: not-allowed;
}

.btn-secondary {
    background-color: #e2e8f0;
    color: #1e293b;
}

.btn-secondary:hover {
    background-color: #cbd5e1;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
    margin-left: 0.5rem;
}

.status-draft {
    background-color: var(--gray-100);
    color: var(--gray-700);
}

.status-approved {
    background-color: #dcfce7;
    color: #166534;
}

.status-canceled {
    background-color: #fee2e2;
    color: #b91c1c;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>
