<!-- src/components/purchasing/charts/CategorySpendChart.vue -->
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
import { PieChart } from "echarts/charts";
import {
    TitleComponent,
    TooltipComponent,
    LegendComponent,
} from "echarts/components";
import { CanvasRenderer } from "echarts/renderers";

// Register necessary ECharts components
echarts.use([
    TitleComponent,
    TooltipComponent,
    LegendComponent,
    PieChart,
    CanvasRenderer,
]);

export default {
    name: "CategorySpendChart",
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

            const options = {
                tooltip: {
                    trigger: "item",
                    formatter: "{b}: ${c} ({d}%)",
                },
                legend: {
                    orient: "vertical",
                    right: 10,
                    top: "center",
                    data: this.chartData.map((item) => item.name),
                },
                series: [
                    {
                        name: "Spend by Category",
                        type: "pie",
                        radius: ["45%", "70%"],
                        center: ["40%", "50%"],
                        avoidLabelOverlap: true,
                        label: {
                            show: false,
                        },
                        emphasis: {
                            label: {
                                show: true,
                                fontSize: "14",
                                fontWeight: "bold",
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
