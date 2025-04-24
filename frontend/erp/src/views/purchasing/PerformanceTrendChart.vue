<!-- src/components/purchasing/charts/PerformanceTrendChart.vue -->
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
import { LineChart } from "echarts/charts";
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
    DataZoomComponent,
    CanvasRenderer,
]);

export default {
    name: "PerformanceTrendChart",
    props: {
        chartData: {
            type: Array,
            required: true,
            default: () => [],
        },
        aspect: {
            type: String,
            default: "overall",
            validator: (value) =>
                ["overall", "quality", "delivery", "price", "service"].includes(
                    value
                ),
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
        aspect() {
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

            const dates = this.chartData.map((item) =>
                this.formatDate(item.date)
            );

            let series = [];

            if (this.aspect === "overall") {
                // Show all aspects
                series = [
                    {
                        name: "Overall",
                        type: "line",
                        data: this.chartData.map((item) => item.overall),
                        smooth: true,
                        lineStyle: { width: 3, color: "#3b82f6" },
                        itemStyle: { color: "#3b82f6" },
                        emphasis: { focus: "series" },
                    },
                    {
                        name: "Quality",
                        type: "line",
                        data: this.chartData.map((item) => item.quality),
                        smooth: true,
                        lineStyle: { width: 2, color: "#10b981" },
                        itemStyle: { color: "#10b981" },
                        emphasis: { focus: "series" },
                    },
                    {
                        name: "Delivery",
                        type: "line",
                        data: this.chartData.map((item) => item.delivery),
                        smooth: true,
                        lineStyle: { width: 2, color: "#f59e0b" },
                        itemStyle: { color: "#f59e0b" },
                        emphasis: { focus: "series" },
                    },
                    {
                        name: "Price",
                        type: "line",
                        data: this.chartData.map((item) => item.price),
                        smooth: true,
                        lineStyle: { width: 2, color: "#8b5cf6" },
                        itemStyle: { color: "#8b5cf6" },
                        emphasis: { focus: "series" },
                    },
                    {
                        name: "Service",
                        type: "line",
                        data: this.chartData.map((item) => item.service),
                        smooth: true,
                        lineStyle: { width: 2, color: "#ef4444" },
                        itemStyle: { color: "#ef4444" },
                        emphasis: { focus: "series" },
                    },
                ];
            } else {
                // Show only selected aspect
                const color = this.getAspectColor(this.aspect);
                series = [
                    {
                        name: this.capitalizeFirstLetter(this.aspect),
                        type: "line",
                        data: this.chartData.map((item) => item[this.aspect]),
                        smooth: true,
                        lineStyle: { width: 3, color },
                        itemStyle: { color },
                        emphasis: { focus: "series" },
                        areaStyle: {
                            color: new echarts.graphic.LinearGradient(
                                0,
                                0,
                                0,
                                1,
                                [
                                    {
                                        offset: 0,
                                        color: this.getAreaColor(color, 0.6),
                                    },
                                    {
                                        offset: 1,
                                        color: this.getAreaColor(color, 0.1),
                                    },
                                ]
                            ),
                        },
                    },
                ];
            }

            const options = {
                tooltip: {
                    trigger: "axis",
                    formatter: (params) => {
                        let result = params[0].name + "<br/>";
                        params.forEach((param) => {
                            const marker = `<span style="display:inline-block;margin-right:5px;border-radius:10px;width:10px;height:10px;background-color:${param.color};"></span>`;
                            result +=
                                marker +
                                param.seriesName +
                                ": " +
                                param.value +
                                "%<br/>";
                        });
                        return result;
                    },
                },
                legend: {
                    data: series.map((s) => s.name),
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
                    boundaryGap: false,
                    data: dates,
                },
                yAxis: {
                    type: "value",
                    min: 0,
                    max: 100,
                    interval: 20,
                    axisLabel: {
                        formatter: "{value}%",
                    },
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

        formatDate(dateString) {
            if (!dateString) return "";

            // Check if dateString is already in a formatted state
            if (dateString.includes("/") || dateString.includes("-")) {
                const date = new Date(dateString);
                return date.toLocaleDateString("en-US", {
                    year: "numeric",
                    month: "short",
                });
            }

            return dateString;
        },

        getAspectColor(aspect) {
            switch (aspect) {
                case "quality":
                    return "#10b981";
                case "delivery":
                    return "#f59e0b";
                case "price":
                    return "#8b5cf6";
                case "service":
                    return "#ef4444";
                default:
                    return "#3b82f6";
            }
        },

        getAreaColor(color, alpha) {
            // Extract RGB values from hex color
            const r = parseInt(color.slice(1, 3), 16);
            const g = parseInt(color.slice(3, 5), 16);
            const b = parseInt(color.slice(5, 7), 16);

            return `rgba(${r}, ${g}, ${b}, ${alpha})`;
        },

        capitalizeFirstLetter(string) {
            return string.charAt(0).toUpperCase() + string.slice(1);
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
