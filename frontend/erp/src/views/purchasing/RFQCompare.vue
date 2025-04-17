<template>
    <div class="rfq-compare-container">
        <!-- Header -->
        <div class="page-header">
            <div class="header-left">
                <router-link :to="`/purchasing/rfqs/${id}`" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to RFQ Details
                </router-link>
                <h1>Compare Vendor Quotations</h1>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="isLoading" class="loading-container">
            <div class="loading-spinner">
                <i class="fas fa-spinner fa-spin"></i>
            </div>
            <p>Loading data...</p>
        </div>

        <!-- Error States -->
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

        <div v-else-if="rfq.status !== 'sent'" class="error-container">
            <div class="error-icon">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <h2>Cannot Compare Quotations</h2>
            <p>
                This RFQ is not in 'sent' status. Quotation comparison is only
                available for RFQs in 'sent' status.
            </p>
            <router-link :to="`/purchasing/rfqs/${id}`" class="btn btn-primary">
                Return to RFQ Details
            </router-link>
        </div>

        <div
            v-else-if="
                !rfq.vendorQuotations || rfq.vendorQuotations.length === 0
            "
            class="error-container"
        >
            <div class="error-icon">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
            <h2>No Quotations Available</h2>
            <p>There are no vendor quotations for this RFQ yet.</p>
            <router-link :to="`/purchasing/rfqs/${id}`" class="btn btn-primary">
                Return to RFQ Details
            </router-link>
        </div>

        <!-- Main Content -->
        <div v-else class="compare-content">
            <!-- RFQ Summary Card -->
            <div class="detail-card">
                <div class="card-header">
                    <h2 class="card-title">RFQ Summary</h2>
                    <span :class="['status-badge', getStatusClass(rfq.status)]">
                        {{
                            rfq.status.charAt(0).toUpperCase() +
                            rfq.status.slice(1)
                        }}
                    </span>
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
                            <span class="info-label">Quotations</span>
                            <span class="info-value"
                                >{{
                                    rfq.vendorQuotations.length
                                }}
                                quotations</span
                            >
                        </div>
                    </div>
                </div>
            </div>

            <!-- Comparison Table -->
            <div class="detail-card">
                <div class="card-header">
                    <h2 class="card-title">Quotation Comparison</h2>
                    <div class="comparison-filters">
                        <select v-model="sortBy" @change="sortQuotations">
                            <option value="vendor_name">
                                Sort by Vendor Name
                            </option>
                            <option value="price_asc">
                                Sort by Total Price (Low to High)
                            </option>
                            <option value="price_desc">
                                Sort by Total Price (High to Low)
                            </option>
                            <option value="delivery_asc">
                                Sort by Delivery Date (Earliest)
                            </option>
                            <option value="delivery_desc">
                                Sort by Delivery Date (Latest)
                            </option>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <div class="comparison-wrapper">
                        <div class="comparison-table">
                            <table>
                                <thead>
                                    <tr>
                                        <th class="item-column">Item</th>
                                        <th
                                            v-for="quotation in sortedQuotations"
                                            :key="quotation.quotation_id"
                                            class="vendor-column"
                                        >
                                            <div class="vendor-header">
                                                <h3>
                                                    {{ quotation.vendor.name }}
                                                </h3>
                                                <div class="vendor-status">
                                                    <span
                                                        :class="[
                                                            'status-badge',
                                                            getQuotationStatusClass(
                                                                quotation.status
                                                            ),
                                                        ]"
                                                    >
                                                        {{
                                                            quotation.status
                                                                .charAt(0)
                                                                .toUpperCase() +
                                                            quotation.status.slice(
                                                                1
                                                            )
                                                        }}
                                                    </span>
                                                </div>
                                                <div class="vendor-details">
                                                    <div class="vendor-info">
                                                        <div
                                                            class="vendor-date"
                                                        >
                                                            <i
                                                                class="fas fa-calendar-alt"
                                                            ></i>
                                                            {{
                                                                formatDate(
                                                                    quotation.quotation_date
                                                                )
                                                            }}
                                                        </div>
                                                        <div
                                                            class="vendor-date"
                                                        >
                                                            <i
                                                                class="fas fa-hourglass-end"
                                                            ></i>
                                                            Valid until:
                                                            {{
                                                                formatDate(
                                                                    quotation.validity_date
                                                                )
                                                            }}
                                                        </div>
                                                    </div>
                                                    <div class="vendor-actions">
                                                        <button
                                                            v-if="
                                                                quotation.status ===
                                                                'received'
                                                            "
                                                            @click="
                                                                acceptQuotation(
                                                                    quotation
                                                                )
                                                            "
                                                            class="btn-sm btn-success"
                                                            title="Accept Quotation"
                                                        >
                                                            <i
                                                                class="fas fa-check"
                                                            ></i>
                                                            Accept
                                                        </button>
                                                        <button
                                                            v-if="
                                                                quotation.status ===
                                                                'accepted'
                                                            "
                                                            @click="
                                                                createPO(
                                                                    quotation
                                                                )
                                                            "
                                                            class="btn-sm btn-primary"
                                                            title="Create Purchase Order"
                                                        >
                                                            <i
                                                                class="fas fa-file-invoice"
                                                            ></i>
                                                            Create PO
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Item Rows -->
                                    <tr
                                        v-for="(line, lineIndex) in rfq.lines"
                                        :key="lineIndex"
                                    >
                                        <td class="item-column">
                                            <div class="item-info">
                                                <div class="item-name">
                                                    {{ line.item.name }}
                                                </div>
                                                <div class="item-code">
                                                    ({{ line.item.item_code }})
                                                </div>
                                                <div class="item-detail">
                                                    Qty: {{ line.quantity }}
                                                    {{
                                                        line.unitOfMeasure
                                                            ?.name || ""
                                                    }}
                                                </div>
                                            </div>
                                        </td>
                                        <td
                                            v-for="quotation in sortedQuotations"
                                            :key="`${quotation.quotation_id}-${lineIndex}`"
                                            :class="[
                                                'vendor-data',
                                                highlightBestPrice(
                                                    quotation,
                                                    line.item_id
                                                )
                                                    ? 'best-price'
                                                    : '',
                                            ]"
                                        >
                                            <div
                                                v-if="
                                                    getQuotationLine(
                                                        quotation,
                                                        line.item_id
                                                    )
                                                "
                                                class="price-info"
                                            >
                                                <div class="price-amount">
                                                    {{
                                                        formatCurrency(
                                                            getQuotationLine(
                                                                quotation,
                                                                line.item_id
                                                            ).unit_price
                                                        )
                                                    }}
                                                </div>
                                                <div class="price-total">
                                                    Total:
                                                    {{
                                                        formatCurrency(
                                                            getQuotationLine(
                                                                quotation,
                                                                line.item_id
                                                            ).unit_price *
                                                                line.quantity
                                                        )
                                                    }}
                                                </div>
                                                <div
                                                    v-if="
                                                        getQuotationLine(
                                                            quotation,
                                                            line.item_id
                                                        ).delivery_date
                                                    "
                                                    class="delivery-date"
                                                >
                                                    <i class="fas fa-truck"></i>
                                                    {{
                                                        formatDate(
                                                            getQuotationLine(
                                                                quotation,
                                                                line.item_id
                                                            ).delivery_date
                                                        )
                                                    }}
                                                </div>
                                            </div>
                                            <div v-else class="no-quote">
                                                No quote
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Total Row -->
                                    <tr class="total-row">
                                        <td class="item-column">
                                            <div class="total-label">Total</div>
                                        </td>
                                        <td
                                            v-for="quotation in sortedQuotations"
                                            :key="`${quotation.quotation_id}-total`"
                                            :class="[
                                                'vendor-data',
                                                isLowestTotal(quotation)
                                                    ? 'lowest-total'
                                                    : '',
                                            ]"
                                        >
                                            <div class="total-amount">
                                                {{
                                                    formatCurrency(
                                                        calculateQuotationTotal(
                                                            quotation
                                                        )
                                                    )
                                                }}
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Card -->
            <div class="detail-card">
                <div class="card-header">
                    <h2 class="card-title">Comparison Summary</h2>
                </div>
                <div class="card-body">
                    <div class="summary-container">
                        <div class="summary-items">
                            <div class="summary-item">
                                <div class="summary-label">
                                    Lowest Total Price
                                </div>
                                <div class="summary-value">
                                    <strong>{{
                                        getLowestTotalVendor()
                                            ? getLowestTotalVendor().vendor.name
                                            : "N/A"
                                    }}</strong>
                                    <div class="summary-detail">
                                        {{
                                            getLowestTotalVendor()
                                                ? formatCurrency(
                                                      calculateQuotationTotal(
                                                          getLowestTotalVendor()
                                                      )
                                                  )
                                                : "N/A"
                                        }}
                                    </div>
                                </div>
                            </div>

                            <div class="summary-item">
                                <div class="summary-label">
                                    Earliest Average Delivery
                                </div>
                                <div class="summary-value">
                                    <strong>{{
                                        getEarliestDeliveryVendor()
                                            ? getEarliestDeliveryVendor().vendor
                                                  .name
                                            : "N/A"
                                    }}</strong>
                                    <div
                                        class="summary-detail"
                                        v-if="getEarliestDeliveryVendor()"
                                    >
                                        {{
                                            getAverageDeliveryDays(
                                                getEarliestDeliveryVendor()
                                            )
                                        }}
                                        days average
                                    </div>
                                </div>
                            </div>

                            <div class="summary-item">
                                <div class="summary-label">
                                    Most Items with Best Price
                                </div>
                                <div class="summary-value">
                                    <strong>{{
                                        getBestPriceVendor()
                                            ? getBestPriceVendor().vendor.name
                                            : "N/A"
                                    }}</strong>
                                    <div
                                        class="summary-detail"
                                        v-if="getBestPriceVendor()"
                                    >
                                        {{
                                            getBestPriceItemCount(
                                                getBestPriceVendor()
                                            )
                                        }}
                                        of {{ rfq.lines.length }} items
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="summary-notes">
                            <p>
                                <i class="fas fa-info-circle"></i>
                                This comparison highlights the best options
                                across different criteria. Consider your
                                priorities (price, delivery time, vendor
                                relationship) when making your decision.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import { ref, onMounted } from "vue";
import { useRouter } from "vue-router";
import axios from "axios";

export default {
    name: "RFQCompare",
    props: {
        id: {
            type: [Number, String],
            required: true,
        },
    },
    setup(props) {
        const router = useRouter();
        const rfq = ref(null);
        const isLoading = ref(true);
        const sortBy = ref("price_asc");
        const sortedQuotations = ref([]);
        const bestPriceMap = ref({});

        /**
         * Load RFQ data from API
         */
        const loadRFQData = async () => {
            isLoading.value = true;

            try {
                const response = await axios.get(
                    `/api/request-for-quotations/${props.id}`
                );
                rfq.value = response.data.data;

                // Initialize sorted quotations
                if (rfq.value && rfq.value.vendorQuotations) {
                    sortQuotations();
                    calculateBestPrices();
                }
            } catch (error) {
                console.error("Error loading RFQ data:", error);
                rfq.value = null;
            } finally {
                isLoading.value = false;
            }
        };

        /**
         * Sort quotations based on selected criteria
         */
        const sortQuotations = () => {
            if (!rfq.value || !rfq.value.vendorQuotations) return;

            const quotations = [...rfq.value.vendorQuotations];

            switch (sortBy.value) {
                case "vendor_name":
                    quotations.sort((a, b) =>
                        a.vendor.name.localeCompare(b.vendor.name)
                    );
                    break;
                case "price_asc":
                    quotations.sort(
                        (a, b) =>
                            calculateQuotationTotal(a) -
                            calculateQuotationTotal(b)
                    );
                    break;
                case "price_desc":
                    quotations.sort(
                        (a, b) =>
                            calculateQuotationTotal(b) -
                            calculateQuotationTotal(a)
                    );
                    break;
                case "delivery_asc":
                    quotations.sort((a, b) => {
                        const aDays = getAverageDeliveryDays(a);
                        const bDays = getAverageDeliveryDays(b);
                        return aDays - bDays;
                    });
                    break;
                case "delivery_desc":
                    quotations.sort((a, b) => {
                        const aDays = getAverageDeliveryDays(a);
                        const bDays = getAverageDeliveryDays(b);
                        return bDays - aDays;
                    });
                    break;
            }

            sortedQuotations.value = quotations;
        };

        /**
         * Calculate best prices for each item
         */
        const calculateBestPrices = () => {
            if (!rfq.value || !rfq.value.lines || !rfq.value.vendorQuotations)
                return;

            const bestPrices = {};

            rfq.value.lines.forEach((line) => {
                let bestPrice = null;
                let bestQuotationId = null;

                rfq.value.vendorQuotations.forEach((quotation) => {
                    const quoteLine = getQuotationLine(quotation, line.item_id);
                    if (
                        quoteLine &&
                        (bestPrice === null || quoteLine.unit_price < bestPrice)
                    ) {
                        bestPrice = quoteLine.unit_price;
                        bestQuotationId = quotation.quotation_id;
                    }
                });

                if (bestQuotationId) {
                    bestPrices[line.item_id] = bestQuotationId;
                }
            });

            bestPriceMap.value = bestPrices;
        };

        /**
         * Helper to get quotation line for a specific item
         */
        const getQuotationLine = (quotation, itemId) => {
            return quotation.lines.find((line) => line.item_id === itemId);
        };

        /**
         * Calculate total for a quotation
         */
        const calculateQuotationTotal = (quotation) => {
            if (!rfq.value || !quotation || !quotation.lines) return 0;

            let total = 0;

            rfq.value.lines.forEach((rfqLine) => {
                const quoteLine = getQuotationLine(quotation, rfqLine.item_id);
                if (quoteLine) {
                    total += quoteLine.unit_price * rfqLine.quantity;
                }
            });

            return total;
        };

        /**
         * Check if a quotation has the best price for an item
         */
        const highlightBestPrice = (quotation, itemId) => {
            return bestPriceMap.value[itemId] === quotation.quotation_id;
        };

        /**
         * Check if a quotation has the lowest total
         */
        const isLowestTotal = (quotation) => {
            if (!rfq.value || !rfq.value.vendorQuotations) return false;

            const lowestQuotation = rfq.value.vendorQuotations.reduce(
                (lowest, current) => {
                    if (!lowest) return current;

                    const lowestTotal = calculateQuotationTotal(lowest);
                    const currentTotal = calculateQuotationTotal(current);

                    return currentTotal < lowestTotal ? current : lowest;
                },
                null
            );

            return (
                lowestQuotation &&
                lowestQuotation.quotation_id === quotation.quotation_id
            );
        };

        /**
         * Get vendor with lowest total price
         */
        const getLowestTotalVendor = () => {
            if (
                !rfq.value ||
                !rfq.value.vendorQuotations ||
                rfq.value.vendorQuotations.length === 0
            ) {
                return null;
            }

            return rfq.value.vendorQuotations.reduce((lowest, current) => {
                if (!lowest) return current;

                const lowestTotal = calculateQuotationTotal(lowest);
                const currentTotal = calculateQuotationTotal(current);

                return currentTotal < lowestTotal ? current : lowest;
            }, null);
        };

        /**
         * Get average delivery days
         */
        const getAverageDeliveryDays = (quotation) => {
            if (!rfq.value || !quotation || !quotation.lines) return Infinity;

            const linesWithDates = quotation.lines.filter(
                (line) => line.delivery_date
            );
            if (linesWithDates.length === 0) return Infinity;

            const today = new Date();

            // Calculate days from today for each line
            const totalDays = linesWithDates.reduce((sum, line) => {
                const deliveryDate = new Date(line.delivery_date);
                const days = Math.max(
                    0,
                    Math.floor((deliveryDate - today) / (1000 * 60 * 60 * 24))
                );
                return sum + days;
            }, 0);

            return Math.round(totalDays / linesWithDates.length);
        };

        /**
         * Get vendor with earliest average delivery
         */
        const getEarliestDeliveryVendor = () => {
            if (
                !rfq.value ||
                !rfq.value.vendorQuotations ||
                rfq.value.vendorQuotations.length === 0
            ) {
                return null;
            }

            // Filter quotations with delivery dates
            const quotationsWithDates = rfq.value.vendorQuotations.filter(
                (q) => {
                    return q.lines.some((line) => line.delivery_date);
                }
            );

            if (quotationsWithDates.length === 0) return null;

            return quotationsWithDates.reduce((earliest, current) => {
                if (!earliest) return current;

                const earliestDays = getAverageDeliveryDays(earliest);
                const currentDays = getAverageDeliveryDays(current);

                return currentDays < earliestDays ? current : earliest;
            }, null);
        };

        /**
         * Count items where vendor has best price
         */
        const getBestPriceItemCount = (quotation) => {
            if (!bestPriceMap.value || !quotation) return 0;

            return Object.values(bestPriceMap.value).filter(
                (quotationId) => quotationId === quotation.quotation_id
            ).length;
        };

        /**
         * Get vendor with most items at best price
         */
        const getBestPriceVendor = () => {
            if (
                !rfq.value ||
                !rfq.value.vendorQuotations ||
                rfq.value.vendorQuotations.length === 0
            ) {
                return null;
            }

            return rfq.value.vendorQuotations.reduce((best, current) => {
                if (!best) return current;

                const bestCount = getBestPriceItemCount(best);
                const currentCount = getBestPriceItemCount(current);

                return currentCount > bestCount ? current : best;
            }, null);
        };

        /**
         * Format date strings
         */
        const formatDate = (dateString) => {
            if (!dateString) return "N/A";
            const date = new Date(dateString);
            return date.toLocaleDateString();
        };

        /**
         * Format currency
         */
        const formatCurrency = (amount) => {
            if (amount === undefined || amount === null) return "N/A";
            return new Intl.NumberFormat("en-US", {
                style: "currency",
                currency: "USD",
                minimumFractionDigits: 2,
            }).format(amount);
        };

        /**
         * Get CSS class for status badge
         */
        const getStatusClass = (status) => {
            switch (status) {
                case "draft":
                    return "status-draft";
                case "sent":
                    return "status-sent";
                case "closed":
                    return "status-closed";
                case "canceled":
                    return "status-canceled";
                default:
                    return "status-draft";
            }
        };

        /**
         * Get CSS class for quotation status badge
         */
        const getQuotationStatusClass = (status) => {
            switch (status) {
                case "received":
                    return "status-received";
                case "accepted":
                    return "status-accepted";
                case "rejected":
                    return "status-rejected";
                default:
                    return "status-received";
            }
        };

        /**
         * Accept a quotation
         */
        const acceptQuotation = async (quotation) => {
            try {
                await axios.patch(
                    `/api/vendor-quotations/${quotation.quotation_id}/status`,
                    {
                        status: "accepted",
                    }
                );

                // Reload data
                await loadRFQData();
            } catch (error) {
                console.error("Error accepting quotation:", error);
                alert("Failed to accept quotation. Please try again.");
            }
        };

        /**
         * Create a purchase order from a quotation
         */
        const createPO = (quotation) => {
            router.push(
                `/purchasing/orders/create-from-quotation/${quotation.quotation_id}`
            );
        };

        /**
         * Initialize the component
         */
        onMounted(() => {
            loadRFQData();
        });

        // Return all functions and reactive variables needed in template
        return {
            rfq,
            isLoading,
            sortBy,
            sortedQuotations,
            getQuotationLine,
            calculateQuotationTotal,
            highlightBestPrice,
            isLowestTotal,
            getLowestTotalVendor,
            getEarliestDeliveryVendor,
            getBestPriceVendor,
            getBestPriceItemCount,
            getAverageDeliveryDays,
            formatDate,
            formatCurrency,
            getStatusClass,
            getQuotationStatusClass,
            sortQuotations,
            acceptQuotation,
            createPO,
        };
    },
};
</script>
<style scoped>
/* Main container */
.rfq-compare-container {
    padding: 1rem;
}

/* Page header */
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

/* Loading and error states */
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

/* Main content container */
.compare-content {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

/* Card styling */
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

/* Info grid for RFQ summary */
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

/* Comparison filters */
.comparison-filters {
    display: flex;
    align-items: center;
}

.comparison-filters select {
    padding: 0.375rem 0.75rem;
    border: 1px solid var(--gray-200);
    border-radius: 0.375rem;
    font-size: 0.875rem;
    background-color: white;
}

/* Comparison table */
.comparison-wrapper {
    overflow-x: auto; /* Allows horizontal scrolling on mobile */
}

.comparison-table table {
    width: 100%;
    border-collapse: collapse;
    border-spacing: 0;
}

.comparison-table th,
.comparison-table td {
    border: 1px solid var(--gray-200);
    padding: 1rem;
    vertical-align: top;
}

.comparison-table th {
    background-color: var(--gray-50);
    font-weight: 500;
}

/* Column widths */
.item-column {
    min-width: 200px;
    max-width: 250px;
}

.vendor-column {
    min-width: 250px;
}

/* Vendor header styling */
.vendor-header {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.vendor-header h3 {
    margin: 0;
    font-size: 1rem;
    color: var(--gray-800);
}

.vendor-status {
    margin-bottom: 0.5rem;
}

.vendor-details {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.vendor-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    font-size: 0.75rem;
    color: var(--gray-600);
}

.vendor-date i {
    width: 1rem;
    margin-right: 0.25rem;
}

.vendor-actions {
    display: flex;
    gap: 0.5rem;
}

/* Item styling */
.item-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.item-name {
    font-weight: 500;
    color: var(--gray-800);
}

.item-code {
    font-size: 0.75rem;
    color: var(--gray-600);
}

.item-detail {
    font-size: 0.75rem;
    color: var(--gray-600);
    margin-top: 0.25rem;
}

/* Price styling */
.price-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.price-amount {
    font-weight: 500;
    font-size: 1.125rem;
    color: var(--gray-800);
}

.price-total {
    font-size: 0.75rem;
    color: var(--gray-600);
}

.delivery-date {
    font-size: 0.75rem;
    color: var(--gray-600);
    margin-top: 0.25rem;
}

.delivery-date i {
    margin-right: 0.25rem;
}

.no-quote {
    color: var(--gray-400);
    font-style: italic;
    font-size: 0.875rem;
}

/* Totals row styling */
.total-row {
    font-weight: 600;
    background-color: var(--gray-50);
}

.total-label {
    font-size: 1rem;
    color: var(--gray-700);
}

.total-amount {
    font-size: 1.125rem;
    color: var(--gray-800);
}

/* Highlighting */
.best-price {
    background-color: rgba(16, 185, 129, 0.05);
    border-left: 3px solid #10b981 !important;
}

.lowest-total {
    background-color: rgba(16, 185, 129, 0.1);
    border-left: 3px solid #10b981 !important;
}

/* Summary section styling */
.summary-container {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.summary-items {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1.5rem;
}

.summary-item {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    padding: 1rem;
    border: 1px solid var(--gray-200);
    border-radius: 0.5rem;
}

.summary-label {
    font-size: 0.875rem;
    color: var(--gray-600);
}

.summary-value {
    font-size: 1rem;
    color: var(--gray-800);
}

.summary-detail {
    font-size: 0.75rem;
    color: var(--gray-600);
    margin-top: 0.25rem;
}

.summary-notes {
    padding: 1rem;
    background-color: var(--gray-50);
    border-radius: 0.5rem;
    font-size: 0.875rem;
    color: var(--gray-700);
}

.summary-notes i {
    color: var(--primary-color);
    margin-right: 0.5rem;
}

/* Status badges */
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

.status-sent {
    background-color: #dbeafe;
    color: #1e40af;
}

.status-closed {
    background-color: #d1fae5;
    color: #065f46;
}

.status-canceled {
    background-color: #fee2e2;
    color: #b91c1c;
}

.status-received {
    background-color: #e0f2fe;
    color: #0369a1;
}

.status-accepted {
    background-color: #d1fae5;
    color: #065f46;
}

.status-rejected {
    background-color: #fef3c7;
    color: #92400e;
}

/* Button styling */
.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.75rem;
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

.btn-success {
    background-color: #10b981;
    color: white;
}

.btn-success:hover {
    background-color: #059669;
}

.btn-primary {
    background-color: var(--primary-color);
    color: white;
}

.btn-primary:hover {
    background-color: var(--primary-dark);
}

/* Responsive design */
@media (max-width: 768px) {
    .info-grid,
    .summary-items {
        grid-template-columns: 1fr;
    }

    .comparison-table {
        font-size: 0.875rem;
    }

    .comparison-table th,
    .comparison-table td {
        padding: 0.75rem;
    }

    .price-amount {
        font-size: 1rem;
    }

    .header-actions {
        flex-wrap: wrap;
        gap: 0.5rem;
    }
}

@media (max-width: 480px) {
    .vendor-actions {
        flex-direction: column;
        gap: 0.5rem;
    }

    .btn-sm {
        width: 100%;
    }
}
</style>
