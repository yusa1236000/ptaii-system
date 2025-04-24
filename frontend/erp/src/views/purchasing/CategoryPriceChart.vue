<!-- src/components/purchasing/charts/CategoryPriceChart.vue -->
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
    name: "CategoryPriceChart",
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

            const categories = this.chartData.map((item) => item.category);
            const changes = this.chartData.map((item) => item.change);
            const prices = this.chartData.map((item) => item.avgPrice);

            const options = {
                tooltip: {
                    trigger: "axis",
                    axisPointer: {
                        type: "shadow",
                    },
                    formatter: (params) => {
                        const changeParam = params[0];
                        const priceParam = params[1];
                        return `${changeParam.name}<br/>
                    Avg Price: $${priceParam.value.toFixed(2)}<br/>
                    Change: ${changeParam.value >= 0 ? "+" : ""}${
                            changeParam.value
                        }%`;
                    },
                },
                legend: {
                    data: ["Price Change %", "Average Price"],
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
                    data: categories,
                    axisLabel: {
                        interval: 0,
                        rotate: categories.length > 5 ? 30 : 0,
                    },
                },
                yAxis: [
                    {
                        type: "value",
                        name: "Change %",
                        position: "left",
                        axisLabel: {
                            formatter: "{value}%",
                        },
                    },
                    {
                        type: "value",
                        name: "Price",
                        position: "right",
                        axisLabel: {
                            formatter: "${value}",
                        },
                    },
                ],
                series: [
                    {
                        name: "Price Change %",
                        type: "bar",
                        yAxisIndex: 0,
                        data: changes,
                        itemStyle: {
                            color: (params) => {
                                return params.value >= 0
                                    ? "#ef4444"
                                    : "#10b981";
                            },
                        },
                        label: {
                            show: true,
                            position: "top",
                            formatter: "{c}%",
                        },
                    },
                    {
                        name: "Average Price",
                        type: "bar",
                        yAxisIndex: 1,
                        data: prices,
                        itemStyle: {
                            color: new echarts.graphic.LinearGradient(
                                0,
                                0,
                                0,
                                1,
                                [
                                    { offset: 0, color: "#8b5cf6" },
                                    { offset: 1, color: "#a78bfa" },
                                ]
                            ),
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
