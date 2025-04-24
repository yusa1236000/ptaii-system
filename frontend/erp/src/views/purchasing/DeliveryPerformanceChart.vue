<!-- src/components/purchasing/charts/DeliveryPerformanceChart.vue -->
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
import { BarChart, LineChart } from "echarts/charts";
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
    BarChart,
    LineChart,
    DataZoomComponent,
    CanvasRenderer,
]);

export default {
    name: "DeliveryPerformanceChart",
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

            const months = this.chartData.map((item) => item.month);
            const onTime = this.chartData.map((item) => item.onTime);
            const late = this.chartData.map((item) => item.late);
            const percentages = this.chartData.map((item) => item.percentage);

            const options = {
                tooltip: {
                    trigger: "axis",
                    axisPointer: {
                        type: "shadow",
                    },
                    formatter: (params) => {
                        const month = params[0].name;
                        let result = `${month}<br>`;

                        // Find the data for the current month
                        const monthData = this.chartData.find(
                            (item) => item.month === month
                        );
                        if (monthData) {
                            const total = monthData.onTime + monthData.late;
                            result += `Total Deliveries: ${total}<br>`;
                            result += `On-Time: ${monthData.onTime} (${monthData.percentage}%)<br>`;
                            result += `Late: ${monthData.late} (${
                                100 - monthData.percentage
                            }%)`;
                        }

                        return result;
                    },
                },
                legend: {
                    data: ["On-Time", "Late", "On-Time Percentage"],
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
                    data: months,
                },
                yAxis: [
                    {
                        type: "value",
                        name: "Count",
                        position: "left",
                        axisLine: {
                            show: true,
                        },
                    },
                    {
                        type: "value",
                        name: "Percentage",
                        min: 0,
                        max: 100,
                        position: "right",
                        axisLine: {
                            show: true,
                        },
                        axisLabel: {
                            formatter: "{value}%",
                        },
                    },
                ],
                series: [
                    {
                        name: "On-Time",
                        type: "bar",
                        stack: "total",
                        itemStyle: {
                            color: "#10b981",
                        },
                        emphasis: {
                            focus: "series",
                        },
                        data: onTime,
                    },
                    {
                        name: "Late",
                        type: "bar",
                        stack: "total",
                        itemStyle: {
                            color: "#ef4444",
                        },
                        emphasis: {
                            focus: "series",
                        },
                        data: late,
                    },
                    {
                        name: "On-Time Percentage",
                        type: "line",
                        yAxisIndex: 1,
                        smooth: true,
                        lineStyle: {
                            width: 3,
                            color: "#3b82f6",
                        },
                        itemStyle: {
                            color: "#3b82f6",
                        },
                        symbolSize: 8,
                        emphasis: {
                            focus: "series",
                        },
                        data: percentages,
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
