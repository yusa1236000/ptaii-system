<!-- src/views/purchasing/RFQSend.vue -->
<template>
    <div class="rfq-send-container">
        <div class="page-header">
            <div class="header-left">
                <router-link :to="`/purchasing/rfqs/${id}`" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to RFQ Details
                </router-link>
                <h1>Send RFQ to Vendors</h1>
            </div>
        </div>

        <div v-if="isLoading" class="loading-container">
            <div class="loading-spinner">
                <i class="fas fa-spinner fa-spin"></i>
            </div>
            <p>Loading data...</p>
        </div>

        <div v-else-if="!rfq" class="error-container">
            <div class="error-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h2>RFQ Not Found</h2>
            <p>
                The requested RFQ could not be found or may have been deleted.
            </p>
            <router-link to="/purchasing/rfqs" class="btn btn-primary">
                Return to RFQs List
            </router-link>
        </div>

        <div v-else-if="rfq.status !== 'draft'" class="error-container">
            <div class="error-icon">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <h2>Cannot Send RFQ</h2>
            <p>
                This RFQ is not in draft status and cannot be sent to vendors.
            </p>
            <router-link :to="`/purchasing/rfqs/${id}`" class="btn btn-primary">
                Return to RFQ Details
            </router-link>
        </div>

        <div v-else class="send-content">
            <!-- RFQ Summary Card -->
            <div class="detail-card">
                <div class="card-header">
                    <h2 class="card-title">RFQ Summary</h2>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">RFQ Number</span>
                            <span class="info-value">{{ rfq.rfq_number }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Date</span>
                            <span class="info-value">{{
                                formatDate(rfq.rfq_date)
                            }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Valid Until</span>
                            <span class="info-value">{{
                                formatDate(rfq.validity_date)
                            }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Items</span>
                            <span class="info-value"
                                >{{ rfq.lines.length }} items</span
                            >
                        </div>
                    </div>
                </div>
            </div>

            <!-- Select Vendors Card -->
            <div class="form-card">
                <div class="card-header">
                    <h2 class="card-title">Select Vendors</h2>
                    <button
                        type="button"
                        class="btn btn-sm btn-primary"
                        @click="selectAllVendors"
                    >
                        <i class="fas fa-check-double"></i> Select All
                    </button>
                </div>
                <div class="card-body">
                    <div v-if="vendors.length === 0" class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3>No Vendors Available</h3>
                        <p>
                            There are no active vendors in the system. Please
                            add vendors before sending RFQs.
                        </p>
                        <router-link
                            to="/purchasing/vendors/create"
                            class="btn btn-primary"
                        >
                            Add Vendor
                        </router-link>
                    </div>

                    <div v-else>
                        <div class="search-filter">
                            <div class="search-box">
                                <i class="fas fa-search search-icon"></i>
                                <input
                                    type="text"
                                    v-model="vendorSearch"
                                    placeholder="Search vendors..."
                                />
                                <button
                                    v-if="vendorSearch"
                                    @click="vendorSearch = ''"
                                    class="clear-search"
                                >
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>

                        <div class="vendors-list">
                            <div
                                v-for="vendor in filteredVendors"
                                :key="vendor.vendor_id"
                                class="vendor-item"
                                :class="{
                                    selected: selectedVendors.includes(
                                        vendor.vendor_id
                                    ),
                                }"
                                @click="toggleVendorSelection(vendor.vendor_id)"
                            >
                                <div class="vendor-checkbox">
                                    <input
                                        type="checkbox"
                                        :checked="
                                            selectedVendors.includes(
                                                vendor.vendor_id
                                            )
                                        "
                                        @click.stop
                                    />
                                </div>
                                <div class="vendor-info">
                                    <h3 class="vendor-name">
                                        {{ vendor.name }}
                                    </h3>
                                    <div class="vendor-details">
                                        <span
                                            v-if="vendor.contact_person"
                                            class="vendor-contact"
                                        >
                                            <i class="fas fa-user"></i>
                                            {{ vendor.contact_person }}
                                        </span>
                                        <span
                                            v-if="vendor.email"
                                            class="vendor-email"
                                        >
                                            <i class="fas fa-envelope"></i>
                                            {{ vendor.email }}
                                        </span>
                                        <span
                                            v-if="vendor.phone"
                                            class="vendor-phone"
                                        >
                                            <i class="fas fa-phone"></i>
                                            {{ vendor.phone }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="filteredVendors.length === 0"
                            class="search-empty-state"
                        >
                            <p>No vendors match your search criteria.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Send Options Card -->
            <div class="form-card">
                <div class="card-header">
                    <h2 class="card-title">Send Options</h2>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="notification_method"
                            >Notification Method</label
                        >
                        <select
                            id="notification_method"
                            v-model="notificationMethod"
                        >
                            <option value="system">
                                System Notification Only
                            </option>
                            <option value="email">Email Notification</option>
                            <option value="both">Both System and Email</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="message"
                            >Additional Message (Optional)</label
                        >
                        <textarea
                            id="message"
                            v-model="additionalMessage"
                            rows="4"
                            placeholder="Add any additional information or instructions for vendors"
                        ></textarea>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" @click="cancel">
                    Cancel
                </button>
                <button
                    type="button"
                    class="btn btn-primary"
                    :disabled="isSubmitting || selectedVendors.length === 0"
                    @click="sendRFQ"
                >
                    <i v-if="isSubmitting" class="fas fa-spinner fa-spin"></i>
                    <i v-else class="fas fa-paper-plane"></i>
                    {{ isSubmitting ? "Sending..." : "Send RFQ" }}
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import axios from "axios";

export default {
    name: "RFQSend",
    props: {
        id: {
            type: [Number, String],
            required: true,
        },
    },
    setup(props) {
        const router = useRouter();
        const rfq = ref(null);
        const vendors = ref([]);
        const selectedVendors = ref([]);
        const vendorSearch = ref("");
        const notificationMethod = ref("system");
        const additionalMessage = ref("");
        const isLoading = ref(true);
        const isSubmitting = ref(false);

        // Filtered vendors based on search
        const filteredVendors = computed(() => {
            if (!vendorSearch.value) {
                return vendors.value;
            }

            const search = vendorSearch.value.toLowerCase();
            return vendors.value.filter(
                (vendor) =>
                    vendor.name.toLowerCase().includes(search) ||
                    (vendor.contact_person &&
                        vendor.contact_person.toLowerCase().includes(search)) ||
                    (vendor.email &&
                        vendor.email.toLowerCase().includes(search)) ||
                    (vendor.vendor_code &&
                        vendor.vendor_code.toLowerCase().includes(search))
            );
        });

        // Load data
        const loadData = async () => {
            isLoading.value = true;

            try {
                // Fetch RFQ and vendors in parallel
                const [rfqResponse, vendorsResponse] = await Promise.all([
                    axios.get(`/api/request-for-quotations/${props.id}`),
                    axios.get("/api/vendors", { params: { status: "active" } }),
                ]);

                rfq.value = rfqResponse.data.data;
                vendors.value = vendorsResponse.data.data.data || [];
            } catch (error) {
                console.error("Error loading data:", error);
                rfq.value = null;
            } finally {
                isLoading.value = false;
            }
        };

        // Vendor selection helpers
        const toggleVendorSelection = (vendorId) => {
            const index = selectedVendors.value.indexOf(vendorId);
            if (index === -1) {
                selectedVendors.value.push(vendorId);
            } else {
                selectedVendors.value.splice(index, 1);
            }
        };

        const selectAllVendors = () => {
            if (selectedVendors.value.length === vendors.value.length) {
                // Deselect all if all are selected
                selectedVendors.value = [];
            } else {
                // Select all
                selectedVendors.value = vendors.value.map(
                    (vendor) => vendor.vendor_id
                );
            }
        };

        // Date formatter
        const formatDate = (dateString) => {
            if (!dateString) return "N/A";
            const date = new Date(dateString);
            return date.toLocaleDateString();
        };

        // Send RFQ
        const sendRFQ = async () => {
            if (selectedVendors.value.length === 0) {
                alert("Please select at least one vendor to send the RFQ.");
                return;
            }

            isSubmitting.value = true;

            try {
                // First update RFQ status to 'sent'
                await axios.patch(
                    `/api/request-for-quotations/${props.id}/status`,
                    {
                        status: "sent",
                    }
                );

                // Create vendor quotations (as placeholders) for each selected vendor
                for (const vendorId of selectedVendors.value) {
                    await createVendorQuotation(vendorId);
                }

                // If email notification is requested, send emails (mock)
                if (
                    notificationMethod.value === "email" ||
                    notificationMethod.value === "both"
                ) {
                    // This would be implemented on the backend
                    console.log("Sending email notifications to vendors");
                }

                // Show success message
                alert(
                    `RFQ successfully sent to ${selectedVendors.value.length} vendors.`
                );

                // Navigate back to RFQ details
                router.push(`/purchasing/rfqs/${props.id}`);
            } catch (error) {
                console.error("Error sending RFQ:", error);
                alert("Failed to send RFQ. Please try again.");
            } finally {
                isSubmitting.value = false;
            }
        };

        // Create placeholder vendor quotation
        const createVendorQuotation = async (vendorId) => {
            // Define quotation lines based on RFQ lines
            const lines = rfq.value.lines.map((line) => ({
                item_id: line.item_id,
                quantity: line.quantity,
                uom_id: line.uom_id,
                delivery_date: line.required_date,
            }));

            // Create the quotation
            await axios.post("/api/vendor-quotations", {
                rfq_id: props.id,
                vendor_id: vendorId,
                quotation_date: new Date().toISOString().split("T")[0],
                validity_date: rfq.value.validity_date,
                lines: lines,
            });
        };

        // Cancel
        const cancel = () => {
            router.push(`/purchasing/rfqs/${props.id}`);
        };

        // Initialize
        onMounted(() => {
            loadData();
        });

        return {
            rfq,
            vendors,
            selectedVendors,
            vendorSearch,
            notificationMethod,
            additionalMessage,
            isLoading,
            isSubmitting,
            filteredVendors,
            toggleVendorSelection,
            selectAllVendors,
            formatDate,
            sendRFQ,
            cancel,
        };
    },
};
</script>

<style scoped>
.rfq-send-container {
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

.send-content {
    max-width: 900px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.detail-card,
.form-card {
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
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
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

.search-filter {
    margin-bottom: 1.5rem;
}

.search-box {
    position: relative;
    max-width: 400px;
}

.search-icon {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--gray-500);
}

.search-box input {
    width: 100%;
    padding: 0.625rem 2.25rem;
    border: 1px solid var(--gray-200);
    border-radius: 0.375rem;
    font-size: 0.875rem;
}

.clear-search {
    position: absolute;
    right: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--gray-500);
    cursor: pointer;
}

.vendors-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1rem;
}

.vendor-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1rem;
    border: 1px solid var(--gray-200);
    border-radius: 0.5rem;
    transition: all 0.2s;
    cursor: pointer;
}

.vendor-item:hover {
    border-color: var(--primary-color);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.vendor-item.selected {
    background-color: var(--primary-bg);
    border-color: var(--primary-color);
}

.vendor-checkbox {
    padding-top: 0.25rem;
}

.vendor-info {
    flex: 1;
}

.vendor-name {
    margin: 0 0 0.5rem 0;
    font-size: 1rem;
    color: var(--gray-800);
}

.vendor-details {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    font-size: 0.75rem;
    color: var(--gray-600);
}

.vendor-contact i,
.vendor-email i,
.vendor-phone i {
    width: 1rem;
    margin-right: 0.25rem;
}

.search-empty-state {
    text-align: center;
    padding: 2rem 0;
    color: var(--gray-500);
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    text-align: center;
}

.empty-icon {
    font-size: 2.5rem;
    color: var(--gray-300);
    margin-bottom: 1rem;
}

.empty-state h3 {
    font-size: 1.125rem;
    color: var(--gray-700);
    margin-bottom: 0.5rem;
}

.empty-state p {
    color: var(--gray-500);
    max-width: 24rem;
    margin-bottom: 1rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group:last-child {
    margin-bottom: 0;
}

.form-group label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--gray-700);
    margin-bottom: 0.5rem;
}

.form-group select,
.form-group textarea {
    width: 100%;
    padding: 0.625rem;
    border: 1px solid var(--gray-200);
    border-radius: 0.375rem;
    font-size: 0.875rem;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 0.5rem;
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

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.75rem;
}

.btn-primary {
    background-color: var(--primary-color);
    color: white;
}

.btn-primary:hover:not(:disabled) {
    background-color: var(--primary-dark);
}

.btn-primary:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.btn-secondary {
    background-color: var(--gray-200);
    color: var(--gray-700);
}

.btn-secondary:hover {
    background-color: var(--gray-300);
}

@media (max-width: 768px) {
    .info-grid,
    .vendors-list {
        grid-template-columns: 1fr;
    }
}
</style>
