// Dashboard charts: flat colours, no gradient fills (Argon style).
import {
    BarController, BarElement, CategoryScale, Chart, Filler, LinearScale,
    LineController, LineElement, PointElement, Tooltip,
} from 'chart.js';

Chart.register(BarController, BarElement, CategoryScale, Filler, LinearScale, LineController, LineElement, PointElement, Tooltip);

const PRIMARY = '#5e72e4';
const SUCCESS = '#2dce89';

Chart.defaults.font.family = '"Open Sans", sans-serif';
Chart.defaults.color = '#8392ab';

const scales = {
    y: {
        beginAtZero: true,
        ticks: { precision: 0 },
        grid: { color: '#e9ecef', drawTicks: false },
        border: { dash: [5, 5], display: false },
    },
    x: { grid: { display: false }, border: { display: false } },
};

/**
 * Loads a MonthlyCountResource series: {data: [{month, label, count}]}.
 */
async function render(canvas, type, color) {
    if (!canvas) {
        return;
    }

    const { data } = (await window.axios.get(canvas.dataset.url)).data;

    new Chart(canvas, {
        type,
        data: {
            labels: data.map((item) => item.label),
            datasets: [{
                label: canvas.dataset.label,
                data: data.map((item) => item.count),
                borderColor: color,
                backgroundColor: type === 'bar' ? color : `${color}1a`, // flat, 10% alpha
                borderWidth: type === 'bar' ? 0 : 3,
                borderRadius: 4,
                maxBarThickness: 12,
                fill: type === 'line',
                tension: 0.4,
                pointRadius: 0,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            plugins: { legend: { display: false } },
            scales,
        },
    });
}

render(document.getElementById('chart-diagnoses'), 'line', PRIMARY);
render(document.getElementById('chart-patients'), 'bar', SUCCESS);
