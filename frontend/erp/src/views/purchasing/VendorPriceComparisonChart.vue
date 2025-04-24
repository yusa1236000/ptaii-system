<!-- src/components/purchasing/charts/VendorPriceComparisonChart.vue -->
<template>
    <div class="chart-container">
        <div v-if="loading" class="chart-loading">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Loading...</span>
            </div>
        </div>
        <div v-else-if="noData" class="no-data-message">
            <i class="fas fa-chart-bar fa-3x text-gray-400"></i>
            <p>No data available</p>
        </div>
        <div
            v-else
            ref="chartContainer"
            style="width: 100%; height: 100%"
        ></div>
    </div>
</template>

<script>
import * as echarts from "echarts/core";
import { BarChart } from "echarts/charts";
import {
    TitleComponent,
    TooltipComponent,
    GridComponent,
    LegendComponent,
} from "echarts/components";
import { CanvasRenderer } from "echarts/renderers";

// Register necessary ECharts components
echarts.use([
    TitleComponent,
    TooltipComponent,
    GridComponent,
    LegendComponent,
    BarChart,
    CanvasRenderer,
]);

export default {
    name: "VendorPriceComparisonChart",
    props: {
        chartData: {
            type: Array,
            required: true,
            default: () => [],
        },
    },
    data() {
        return {
            chart: null,
            loading: false,
        };
    },
    computed: {
        noData() {
            return !this.chartData || this.chartData.length === 0;
        },
    },
    watch: {
        chartData: {
            handler() {
                this.renderChart();
            },
            deep: true,
        },
    },
    mounted() {
        this.initChart();
        window.addEventListener("resize", this.resizeChart);
    },
    beforeUnmount() {
        if (this.chart) {
            this.chart.dispose();
            this.chart = null;
        }
        window.removeEventListener("resize", this.resizeChart);
    },
    methods: {
        initChart() {
            if (this.$refs.chartContainer) {
                this.chart = echarts.init(this.$refs.chartContainer);
                this.renderChart();
            }
        },
        resizeChart() {
            if (this.chart) {
                this.chart.resize();
            }
        },
        renderChart() {
            if (!this.chart || this.noData) return;

            this.loading = true;

            const vendors = this.chartData.map((item) => item.vendor);
            const prices = this.chartData.map((item) => item.price);
            const changes = this.chartData.map((item) => item.change);

            // Calculate the average price for the reference line
            const avgPrice =
                prices.reduce((sum, price) => sum + price, 0) / prices.length;

            const options = {
                tooltip: {
                    trigger: "axis",
                    axisPointer: {
                        type: "shadow",
                    },
                    formatter: (params) => {
                        const priceParam = params[0];
                        const changeParam = params[1];
                        return `${priceParam.name}<br/>
                    Price: $${priceParam.value.toFixed(2)}<br/>
                    Change: ${changeParam.value >= 0 ? "+" : ""}${
                            changeParam.value
                        }%`;
                    },
                },
                legend: {
                    data: ["Price", "Change %"],
                    bottom: 0,
                },
                grid: {
                    left: "3%",
                    right: "4%",
                    bottom: "60px",
                    containLabel: true,
                },
                xAxis: {
                    type: "category",
                    data: vendors,
                    axisLabel: {
                        interval: 0,
                        rotate: vendors.length > 5 ? 30 : 0,
                    },
                },
                yAxis: [
                    {
                        type: "value",
                        name: "Price",
                        position: "left",
                        axisLabel: {
                            formatter: "${value}",
                        },
                    },
                    {
                        type: "value",
                        name: "Change %",
                        position: "right",
                        axisLabel: {
                            formatter: "{value}%",
                        },
                    },
                ],
                series: [
                    {
                        name: "Price",
                        type: "bar",
                        yAxisIndex: 0,
                        data: prices,
                        itemStyle: {
                            color: new echarts.graphic.LinearGradient(
                                0,
                                0,
                                0,
                                1,
                                [
                                    { offset: 0, color: "#3b82f6" },
                                    { offset: 1, color: "#60a5fa" },
                                ]
                            ),
                        },
                        markLine: {
                            silent: true,
                            lineStyle: {
                                color: "#64748b",
                                type: "dashed",
                            },
                            data: [
                                {
                                    yAxis: avgPrice,
                                    label: {
                                        formatter: "Avg: ${value}",
                                        position: "middle",
                                    },
                                },
                            ],
                        },
                    },
                    {
                        name: "Change %",
                        type: "bar",
                        yAxisIndex: 1,
                        data: changes,
                        itemStyle: {
                            color: (params) => {
                                return params.value >= 0
                                    ? "#ef4444"
                                    : "#10b981";
                            },
                        },
                    },
                ],
            };

            this.chart.setOption(options, true);
            this.loading = false;
        },
    },
};
</script>

<style scoped>
.chart-container {
    position: relative;
    width: 100%;
    height: 100%;
}

.chart-loading,
.no-data-message {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    background: rgba(255, 255, 255, 0.8);
}

.no-data-message {
    color: var(--gray-500);
}

.no-data-message i {
    margin-bottom: 1rem;
}
</style>
