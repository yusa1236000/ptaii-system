<!-- src/components/purchasing/charts/MonthlySpendChart.vue -->
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
    MarkLineComponent,
    MarkPointComponent,
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
    MarkLineComponent,
    MarkPointComponent,
    CanvasRenderer,
]);

export default {
    name: "MonthlySpendChart",
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
            const amounts = this.chartData.map((item) => item.amount);

            // Calculate average for reference line
            const average =
                amounts.reduce((sum, val) => sum + val, 0) / amounts.length;

            const options = {
                tooltip: {
                    trigger: "axis",
                    formatter: (params) => {
                        const param = params[0];
                        return `${param.name}: ${this.formatNumber(
                            param.value
                        )}`;
                    },
                },
                grid: {
                    left: "3%",
                    right: "4%",
                    bottom: "3%",
                    containLabel: true,
                },
                xAxis: {
                    type: "category",
                    boundaryGap: false,
                    data: months,
                },
                yAxis: {
                    type: "value",
                    axisLabel: {
                        formatter: (value) => {
                            if (value >= 1000) {
                                return "" + value / 1000 + "k";
                            }
                            return "" + value;
                        },
                    },
                },
                series: [
                    {
                        name: "Monthly Spend",
                        type: "line",
                        data: amounts,
                        smooth: true,
                        lineStyle: {
                            width: 3,
                            color: "#3b82f6",
                        },
                        itemStyle: {
                            color: "#3b82f6",
                        },
                        areaStyle: {
                            color: new echarts.graphic.LinearGradient(
                                0,
                                0,
                                0,
                                1,
                                [
                                    {
                                        offset: 0,
                                        color: "rgba(59, 130, 246, 0.5)",
                                    },
                                    {
                                        offset: 1,
                                        color: "rgba(59, 130, 246, 0.1)",
                                    },
                                ]
                            ),
                        },
                        markLine: {
                            data: [
                                {
                                    name: "Average",
                                    type: "average",
                                    lineStyle: {
                                        color: "#ef4444",
                                        type: "dashed",
                                    },
                                    label: {
                                        formatter: "Average: ${value}",
                                        position: "end",
                                    },
                                },
                            ],
                        },
                        markPoint: {
                            data: [
                                { type: "max", name: "Max" },
                                { type: "min", name: "Min" },
                            ],
                        },
                    },
                ],
            };

            this.chart.setOption(options, true);
            this.loading = false;
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
