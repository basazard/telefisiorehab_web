import Chart from 'chart.js/auto';

// Colors given as "--token" are read from the CSS design tokens (resources/css/app.css), so charts follow the theme.
const token = (value) => typeof value === 'string' && value.startsWith('--')
    ? getComputedStyle(document.documentElement).getPropertyValue(value).trim()
    : value;

const resolveColors = (node) => {
    if (Array.isArray(node)) return node.map(resolveColors);
    if (node && typeof node === 'object') {
        return Object.fromEntries(Object.entries(node).map(([key, value]) => [key, resolveColors(value)]));
    }
    return token(node);
};

// <div x-data="chart(@js($config))"><canvas x-ref="canvas"></canvas></div> renders a Chart.js chart.
// Alpine comes with Livewire.
document.addEventListener('alpine:init', () => {
    window.Alpine.data('chart', (config) => ({
        chart: null,
        init() {
            this.chart = new Chart(this.$refs.canvas, resolveColors(config));
        },
        destroy() {
            this.chart?.destroy();
        },
    }));
});
