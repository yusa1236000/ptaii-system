<!-- src/components/purchasing/charts/VendorSpendChart.vue -->
<template>
    <div class="chart-container">
        <div v-if="loading" class="chart-loading">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Loading...</span>
            </div>
        </div>
        <div v-else-if="noData" class="no-data-message">
            <i class="fas fa-chart-pie fa-3x text-gray-400"></i>
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
import { PieChart, BarChart } from "echarts/charts";
import {
    TitleComponent,
    TooltipComponent,
    LegendComponent,
    GridComponent,
} from "echarts/components";
import { CanvasRenderer } from "echarts/renderers";

// Register necessary ECharts components
echarts.use([
    TitleComponent,
    TooltipComponent,
    LegendComponent,
    GridComponent,
    PieChart,
    BarChart,
    CanvasRenderer,
]);

export default {
    name: "VendorSpendChart",
    props: {
        chartData: {
            type: Array,
            required: true,
            default: () => [],
        },
        chartType: {
            type: String,
            default: "pie",
            validator: (value) => ["pie", "bar"].includes(value),
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
        chartType() {
            this.renderChart();
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

            // Apply different chart options based on the chart type
            const options =
                this.chartType === "pie"
                    ? this.getPieChartOptions()
                    : this.getBarChartOptions();

            this.chart.setOption(options, true);
            this.loading = false;
        },
        getPieChartOptions() {
            const totalSpend = this.chartData.reduce(
                (sum, item) => sum + item.value,
                0
            );

            return {
                tooltip: {
                    trigger: "item",
                    formatter: (params) => {
                        const percentage = (
                            (params.value / totalSpend) *
                            100
                        ).toFixed(1);
                        return `${params.name}: $${this.formatNumber(
                            params.value
                        )} (${percentage}%)`;
                    },
                },
                legend: {
                    type: "scroll",
                    orient: "horizontal",
                    bottom: 0,
                    data: this.chartData.map((item) => item.name),
                },
                series: [
                    {
                        name: "Vendor Spend",
                        type: "pie",
                        radius: ["40%", "70%"],
                        center: ["50%", "45%"],
                        avoidLabelOverlap: true,
                        itemStyle: {
                            borderRadius: 4,
                            borderColor: "#fff",
                            borderWidth: 2,
                        },
                        label: {
                            show: false,
                        },
                        emphasis: {
                            label: {
                                show: true,
                                fontSize: "14",
                                fontWeight: "bold",
                                formatter: (params) => {
                                    const percentage = (
                                        (params.value / totalSpend) *
                                        100
                                    ).toFixed(1);
                                    return `${params.name}\n${percentage}%`;
                                },
                            },
                        },
                        labelLine: {
                            show: false,
                        },
                        data: this.chartData.map((item) => ({
                            value: item.value,
                            name: item.name,
                            itemStyle: {
                                color: item.color,
                            },
                        })),
                    },
                ],
            };
        },
        getBarChartOptions() {
            // Sort data by value in descending order for better visualization
            const sortedData = [...this.chartData].sort(
                (a, b) => b.value - a.value
            );

            return {
                tooltip: {
                    trigger: "axis",
                    axisPointer: {
                        type: "shadow",
                    },
                    formatter: (params) => {
                        const param = params[0];
                        return `${param.name}: $${this.formatNumber(
                            param.value
                        )}`;
                    },
                },
                grid: {
                    left: "3%",
                    right: "4%",
                    bottom: "15%",
                    top: "3%",
                    containLabel: true,
                },
                xAxis: {
                    type: "category",
                    data: sortedData.map((item) => item.name),
                    axisLabel: {
                        rotate: 45,
                        interval: 0,
                    },
                },
                yAxis: {
                    type: "value",
                    axisLabel: {
                        formatter: (value) => {
                            if (value >= 1000) {
                                return "$" + value / 1000 + "k";
                            }
                            return "$" + value;
                        },
                    },
                },
                series: [
                    {
                        name: "Spend",
                        type: "bar",
                        barWidth: "60%",
                        data: sortedData.map((item) => ({
                            value: item.value,
                            itemStyle: {
                                color: item.color,
                            },
                        })),
                    },
                ],
            };
        },
        formatNumber(number) {
            return number.toLocaleString("en-US", {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
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
