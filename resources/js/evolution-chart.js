// Evolution chart of a patient's history (clinical measurements, lab results).
//
// <canvas id="evolution-chart" data-series='[{date, key: value…}]'
//         data-metrics='{metric: [{key, label}, …]}'>
// Buttons with data-metric inside [data-chart-metrics] switch the plotted metric.
import {
    CategoryScale, Chart, Filler, Legend, LinearScale, LineController, LineElement, PointElement, Tooltip,
} from 'chart.js';

Chart.register(CategoryScale, Filler, Legend, LinearScale, LineController, LineElement, PointElement, Tooltip);

Chart.defaults.font.family = '"Open Sans", sans-serif';
Chart.defaults.color = '#8392ab';

// One colour per line of the same metric (e.g. systolic / diastolic).
const COLORS = ['#5e72e4', '#f5365c', '#2dce89'];

const canvas = document.getElementById('evolution-chart');

if (canvas) {
    const series = JSON.parse(canvas.dataset.series);
    const metrics = JSON.parse(canvas.dataset.metrics);
    const buttons = document.querySelectorAll('[data-chart-metrics] [data-metric]');

    const datasets = (metric) => metrics[metric].map(({ key, label }, i) => ({
        label,
        data: series.map((row) => row[key]),
        borderColor: COLORS[i % COLORS.length],
        backgroundColor: `${COLORS[i % COLORS.length]}1a`, // flat, 10% alpha
        borderWidth: 3,
        fill: metrics[metric].length === 1,
        tension: 0.3,
        pointRadius: 3,
        spanGaps: true, // a control may not include every value
    }));

    const first = Object.keys(metrics)[0];

    const chart = new Chart(canvas, {
        type: 'line',
        data: { labels: series.map((row) => row.date), datasets: datasets(first) },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            plugins: { legend: { display: metrics[first].length > 1 } },
            scales: {
                y: {
                    grid: { color: '#e9ecef', drawTicks: false },
                    border: { dash: [5, 5], display: false },
                },
                x: { grid: { display: false }, border: { display: false } },
            },
        },
    });

    buttons.forEach((button) => {
        button.addEventListener('click', () => {
            buttons.forEach((b) => b.classList.toggle('active', b === button));
            chart.data.datasets = datasets(button.dataset.metric);
            chart.options.plugins.legend.display = chart.data.datasets.length > 1;
            chart.update();
        });
    });
}
