<!-- src/components/purchasing/charts/POStatusTrendChart.vue -->
<template>
    <div class="chart-container">
        <div v-if="loading" class="chart-loading">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Loading...</span>
            </div>
        </div>
        <div v-else-if="noData" class="no-data-message">
            <i class="fas fa-chart-line fa-3x text-gray-400"></i>
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
import { LineChart, BarChart } from "echarts/charts";
import {
    TitleComponent,
    TooltipComponent,
    GridComponent,
    LegendComponent,
    DataZoomComponent,
} from "echarts/components";
import { CanvasRenderer } from "echarts/renderers";

// Register necessary ECharts components
echarts.use([
    TitleComponent,
    TooltipComponent,
    GridComponent,
    LegendComponent,
    LineChart,
    BarChart,
    DataZoomComponent,
    CanvasRenderer,
]);

export default {
    name: "POStatusTrendChart",
    props: {
        chartData: {
            type: Array,
            required: true,
            default: () => [],
        },
        chartType: {
            type: String,
            default: "line",
            validator: (value) => ["line", "bar", "stacked"].includes(value),
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

            const months = this.chartData.map((item) => item.month);

            // Define series colors
            const colors = {
                draft: "#94a3b8",
                approved: "#3b82f6",
                sent: "#0ea5e9",
                partial: "#f59e0b",
                completed: "#10b981",
                canceled: "#ef4444",
            };

            // Create series based on status types
            const series = [
                {
                    name: "Draft",
                    type: this.chartType === "bar" ? "bar" : "line",
                    stack: this.chartType === "stacked" ? "total" : null,
                    emphasis: { focus: "series" },
                    data: this.chartData.map((item) => item.draft),
                    itemStyle: { color: colors.draft },
                    lineStyle: { width: 2, color: colors.draft },
                    smooth: this.chartType !== "bar",
                },
                {
                    name: "Approved",
                    type: this.chartType === "bar" ? "bar" : "line",
                    stack: this.chartType === "stacked" ? "total" : null,
                    emphasis: { focus: "series" },
                    data: this.chartData.map((item) => item.approved),
                    itemStyle: { color: colors.approved },
                    lineStyle: { width: 2, color: colors.approved },
                    smooth: this.chartType !== "bar",
                },
                {
                    name: "Sent",
                    type: this.chartType === "bar" ? "bar" : "line",
                    stack: this.chartType === "stacked" ? "total" : null,
                    emphasis: { focus: "series" },
                    data: this.chartData.map((item) => item.sent),
                    itemStyle: { color: colors.sent },
                    lineStyle: { width: 2, color: colors.sent },
                    smooth: this.chartType !== "bar",
                },
                {
                    name: "Partial",
                    type: this.chartType === "bar" ? "bar" : "line",
                    stack: this.chartType === "stacked" ? "total" : null,
                    emphasis: { focus: "series" },
                    data: this.chartData.map((item) => item.partial),
                    itemStyle: { color: colors.partial },
                    lineStyle: { width: 2, color: colors.partial },
                    smooth: this.chartType !== "bar",
                },
                {
                    name: "Completed",
                    type: this.chartType === "bar" ? "bar" : "line",
                    stack: this.chartType === "stacked" ? "total" : null,
                    emphasis: { focus: "series" },
                    data: this.chartData.map((item) => item.completed),
                    itemStyle: { color: colors.completed },
                    lineStyle: { width: 2, color: colors.completed },
                    smooth: this.chartType !== "bar",
                },
                {
                    name: "Canceled",
                    type: this.chartType === "bar" ? "bar" : "line",
                    stack: this.chartType === "stacked" ? "total" : null,
                    emphasis: { focus: "series" },
                    data: this.chartData.map((item) => item.canceled),
                    itemStyle: { color: colors.canceled },
                    lineStyle: { width: 2, color: colors.canceled },
                    smooth: this.chartType !== "bar",
                },
            ];

            const options = {
                tooltip: {
                    trigger: "axis",
                    axisPointer: {
                        type: this.chartType === "bar" ? "shadow" : "line",
                    },
                },
                legend: {
                    data: series.map((item) => item.name),
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
                    boundaryGap: this.chartType === "bar",
                    data: months,
                },
                yAxis: {
                    type: "value",
                },
                dataZoom: [
                    {
                        type: "inside",
                        start: 0,
                        end: 100,
                    },
                    {
                        start: 0,
                        end: 100,
                        bottom: 30,
                    },
                ],
                series,
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
