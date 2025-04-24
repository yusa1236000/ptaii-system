<!-- src/components/purchasing/charts/PerformanceRadarChart.vue -->
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
import { RadarChart } from "echarts/charts";
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
    RadarChart,
    CanvasRenderer,
]);

export default {
    name: "PerformanceRadarChart",
    props: {
        chartData: {
            type: Array,
            required: true,
            default: () => [],
        },
        benchmark: {
            type: Array,
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
        benchmark: {
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

            const indicators = this.chartData.map((item) => ({
                name: item.name,
                max: 100,
            }));

            const seriesData = [
                {
                    name: "Performance Score",
                    value: this.chartData.map((item) => item.value),
                },
            ];

            // Add benchmark data if provided
            if (this.benchmark && this.benchmark.length > 0) {
                seriesData.push({
                    name: "Industry Average",
                    value: this.benchmark.map((item) => item.value),
                });
            }

            const options = {
                tooltip: {
                    trigger: "item",
                },
                legend: {
                    data: seriesData.map((item) => item.name),
                    bottom: 0,
                },
                radar: {
                    shape: "circle",
                    indicator: indicators,
                    splitArea: {
                        areaStyle: {
                            color: ["rgba(255, 255, 255, 0.5)"],
                            shadowColor: "rgba(0, 0, 0, 0.05)",
                            shadowBlur: 10,
                        },
                    },
                    axisLine: {
                        lineStyle: {
                            color: "rgba(211, 211, 211, 0.8)",
                        },
                    },
                    splitLine: {
                        lineStyle: {
                            color: "rgba(211, 211, 211, 0.8)",
                        },
                    },
                },
                series: [
                    {
                        type: "radar",
                        data: seriesData.map((item, index) => ({
                            name: item.name,
                            value: item.value,
                            symbol: "circle",
                            symbolSize: 6,
                            itemStyle: {
                                color: index === 0 ? "#3b82f6" : "#f59e0b",
                            },
                            lineStyle: {
                                width: 2,
                                color: index === 0 ? "#3b82f6" : "#f59e0b",
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
                                            color:
                                                index === 0
                                                    ? "rgba(59, 130, 246, 0.6)"
                                                    : "rgba(245, 158, 11, 0.6)",
                                        },
                                        {
                                            offset: 1,
                                            color:
                                                index === 0
                                                    ? "rgba(59, 130, 246, 0.1)"
                                                    : "rgba(245, 158, 11, 0.1)",
                                        },
                                    ]
                                ),
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
