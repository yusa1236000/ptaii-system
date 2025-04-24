<!-- src/components/purchasing/charts/PriceTrendChart.vue -->
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
    MarkPointComponent,
    MarkLineComponent,
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
    MarkPointComponent,
    MarkLineComponent,
    CanvasRenderer,
]);

export default {
    name: "PriceTrendChart",
    props: {
        chartData: {
            type: Array,
            required: true,
            default: () => [],
        },
        chartType: {
            type: String,
            default: "line",
            validator: (value) => ["line", "area", "column"].includes(value),
        },
        multipleItems: {
            type: Boolean,
            default: false,
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
        multipleItems() {
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

            // Prepare the chart options based on chart type and data structure
            let options;

            if (this.multipleItems) {
                options = this.getMultipleItemsChartOptions();
            } else {
                options = this.getSingleItemChartOptions();
            }

            this.chart.setOption(options, true);
            this.loading = false;
        },

        getSingleItemChartOptions() {
            const item = this.chartData[0];
            const categories = item.data.map((point) => point.x);
            const values = item.data.map((point) => parseFloat(point.y));

            // Calculate percentage change for annotation
            const firstValue = values[0];
            const lastValue = values[values.length - 1];
            const percentageChange = (
                ((lastValue - firstValue) / firstValue) *
                100
            ).toFixed(1);
            const changeDirection =
                percentageChange >= 0 ? "increase" : "decrease";

            return {
                tooltip: {
                    trigger: "axis",
                    formatter: (params) => {
                        const param = params[0];
                        return `${param.name}: ${param.value.toFixed(2)}`;
                    },
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
                    boundaryGap: this.chartType === "column",
                },
                yAxis: {
                    type: "value",
                    axisLabel: {
                        formatter: "${value}",
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
                series: [
                    {
                        name: item.name,
                        type: this.chartType === "column" ? "bar" : "line",
                        data: values,
                        smooth: true,
                        showSymbol: true,
                        symbolSize: 6,
                        lineStyle: {
                            width: 3,
                            color: "#3b82f6",
                        },
                        itemStyle: {
                            color: "#3b82f6",
                        },
                        areaStyle:
                            this.chartType === "area"
                                ? {
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
                                  }
                                : null,
                        markPoint: {
                            data: [
                                { type: "max", name: "Max" },
                                { type: "min", name: "Min" },
                            ],
                        },
                        markLine: {
                            data: [
                                {
                                    name: "Average",
                                    type: "average",
                                    lineStyle: {
                                        color: "#8b5cf6",
                                        type: "dashed",
                                    },
                                    label: {
                                        formatter: "Avg: ${value}",
                                    },
                                },
                            ],
                        },
                    },
                ],
                // Add graphic elements for percentage change annotation
                graphic: [
                    {
                        type: "group",
                        right: 50,
                        top: 50,
                        children: [
                            {
                                type: "rect",
                                z: 100,
                                left: "center",
                                top: "middle",
                                shape: {
                                    width: 140,
                                    height: 50,
                                    r: 5,
                                },
                                style: {
                                    fill: "#fff",
                                    stroke:
                                        changeDirection === "increase"
                                            ? "#ef4444"
                                            : "#10b981",
                                    lineWidth: 2,
                                    shadowBlur: 6,
                                    shadowOffsetX: 0,
                                    shadowOffsetY: 3,
                                    shadowColor: "rgba(0,0,0,0.2)",
                                },
                            },
                            {
                                type: "text",
                                z: 100,
                                left: "center",
                                top: "middle",
                                style: {
                                    fill:
                                        changeDirection === "increase"
                                            ? "#ef4444"
                                            : "#10b981",
                                    text: `${
                                        changeDirection === "increase"
                                            ? "+"
                                            : ""
                                    }${percentageChange}%`,
                                    font: "bold 16px Arial",
                                },
                            },
                            {
                                type: "text",
                                z: 100,
                                left: "center",
                                top: 0,
                                style: {
                                    fill: "#475569",
                                    text: "Price Change",
                                    font: "12px Arial",
                                },
                            },
                        ],
                    },
                ],
            };
        },

        getMultipleItemsChartOptions() {
            // For multiple items view, create a series for each item
            const series = this.chartData.map((item) => {
                return {
                    name: item.name,
                    type: this.chartType === "column" ? "bar" : "line",
                    data: item.data.map((point) => parseFloat(point.y)),
                    smooth: true,
                    showSymbol: true,
                    symbolSize: 5,
                    // Note: Colors will be auto-assigned by echarts
                    areaStyle:
                        this.chartType === "area"
                            ? {
                                  opacity: 0.3,
                              }
                            : null,
                };
            });

            // Get categories from the first item (assuming all items have the same categories)
            const categories =
                this.chartData[0]?.data.map((point) => point.x) || [];

            return {
                tooltip: {
                    trigger: "axis",
                    formatter: (params) => {
                        let result = `${params[0].name}<br>`;
                        params.forEach((param) => {
                            const marker = `<span style="display:inline-block;margin-right:5px;border-radius:10px;width:10px;height:10px;background-color:${param.color};"></span>`;
                            result += `${marker}${
                                param.seriesName
                            }: ${param.value.toFixed(2)}<br>`;
                        });
                        return result;
                    },
                },
                legend: {
                    data: this.chartData.map((item) => item.name),
                    bottom: 0,
                    type: "scroll",
                    pageIconSize: 12,
                    pageTextStyle: {
                        color: "#64748b",
                    },
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
                    boundaryGap: this.chartType === "column",
                },
                yAxis: {
                    type: "value",
                    axisLabel: {
                        formatter: "${value}",
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
