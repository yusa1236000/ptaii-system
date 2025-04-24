<!-- src/components/purchasing/charts/SpendTrendChart.vue -->
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
    MarkLineComponent,
    MarkPointComponent,
    DataZoomComponent,
    CanvasRenderer,
]);

export default {
    name: "SpendTrendChart",
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

            // Process data
            const dates = this.chartData.map((item) =>
                this.formatDate(item.date)
            );
            const amounts = this.chartData.map((item) => item.amount);

            // Calculate moving average for the trend line
            const movingAverage = this.calculateMovingAverage(amounts, 3);

            // Define colors
            const mainColor = "#3b82f6";
            const trendColor = "#ef4444";

            const options = {
                tooltip: {
                    trigger: "axis",
                    axisPointer: {
                        type: "cross",
                        crossStyle: {
                            color: "#999",
                        },
                    },
                    formatter: (params) => {
                        let result = params[0].name + "<br/>";
                        params.forEach((param) => {
                            const marker = `<span style="display:inline-block;margin-right:5px;border-radius:10px;width:10px;height:10px;background-color:${param.color};"></span>`;
                            let value = param.value;
                            if (param.seriesName === "Spend") {
                                value = "$" + this.formatNumber(value);
                            } else {
                                value = "$" + this.formatNumber(value);
                            }
                            result +=
                                marker +
                                param.seriesName +
                                ": " +
                                value +
                                "<br/>";
                        });
                        return result;
                    },
                },
                grid: {
                    left: "3%",
                    right: "4%",
                    bottom: "13%",
                    containLabel: true,
                },
                xAxis: {
                    type: "category",
                    data: dates,
                    boundaryGap: false,
                    axisPointer: {
                        type: "shadow",
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
                dataZoom: [
                    {
                        type: "inside",
                        start: 0,
                        end: 100,
                    },
                    {
                        start: 0,
                        end: 100,
                    },
                ],
                series: [
                    {
                        name: "Spend",
                        type: "bar",
                        data: amounts,
                        itemStyle: {
                            color: mainColor,
                        },
                        emphasis: {
                            focus: "series",
                        },
                    },
                    {
                        name: "Trend",
                        type: "line",
                        data: movingAverage,
                        smooth: true,
                        showSymbol: false,
                        lineStyle: {
                            width: 3,
                            color: trendColor,
                        },
                        emphasis: {
                            focus: "series",
                        },
                    },
                ],
            };

            this.chart.setOption(options, true);
            this.loading = false;
        },
        calculateMovingAverage(data, windowSize) {
            const result = [];

            // Start with nulls for the first (windowSize-1) values
            for (let i = 0; i < windowSize - 1; i++) {
                result.push("-");
            }

            // Calculate moving average
            for (let i = windowSize - 1; i < data.length; i++) {
                let sum = 0;
                for (let j = 0; j < windowSize; j++) {
                    sum += data[i - j];
                }
                result.push(sum / windowSize);
            }

            return result;
        },
        formatDate(dateStr) {
            // Assuming dateStr format is 'yyyy-MM'
            const [year, month] = dateStr.split("-");

            const monthNames = [
                "Jan",
                "Feb",
                "Mar",
                "Apr",
                "May",
                "Jun",
                "Jul",
                "Aug",
                "Sep",
                "Oct",
                "Nov",
                "Dec",
            ];

            return monthNames[parseInt(month) - 1] + " " + year;
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
