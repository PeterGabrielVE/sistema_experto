// Evolution chart of a patient's clinical measurements (registro de mediciones).
import {
    CategoryScale, Chart, Filler, Legend, LinearScale, LineController, LineElement, PointElement, Tooltip,
} from 'chart.js';

Chart.register(CategoryScale, Filler, Legend, LinearScale, LineController, LineElement, PointElement, Tooltip);

Chart.defaults.font.family = '"Open Sans", sans-serif';
Chart.defaults.color = '#8392ab';

const PRIMARY = '#5e72e4';
const DANGER = '#f5365c';

/** Metric -> datasets (one or two lines) built from the series rows. */
const METRICS = {
    weight_kg: [{ key: 'weight_kg', label: 'Peso (kg)', color: PRIMARY }],
    bmi: [{ key: 'bmi', label: 'IMC (kg/m²)', color: PRIMARY }],
    waist_cm: [{ key: 'waist_cm', label: 'Cintura (cm)', color: PRIMARY }],
    blood_pressure: [
        { key: 'systolic_bp', label: 'Sistólica (mmHg)', color: DANGER },
        { key: 'diastolic_bp', label: 'Diastólica (mmHg)', color: PRIMARY },
    ],
    capillary_glucose: [{ key: 'capillary_glucose', label: 'Glicemia capilar (mg/dL)', color: PRIMARY }],
};

const canvas = document.getElementById('chart-measurements');

if (canvas) {
    const series = JSON.parse(canvas.dataset.series);

    const datasets = (metric) => METRICS[metric].map(({ key, label, color }) => ({
        label,
        data: series.map((row) => row[key]),
        borderColor: color,
        backgroundColor: `${color}1a`, // flat, 10% alpha
        borderWidth: 3,
        fill: METRICS[metric].length === 1,
        tension: 0.3,
        pointRadius: 3,
        spanGaps: true, // a control may not include every value
    }));

    const chart = new Chart(canvas, {
        type: 'line',
        data: { labels: series.map((row) => row.date), datasets: datasets('weight_kg') },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            plugins: { legend: { display: false } },
            scales: {
                y: {
                    grid: { color: '#e9ecef', drawTicks: false },
                    border: { dash: [5, 5], display: false },
                },
                x: { grid: { display: false }, border: { display: false } },
            },
        },
    });

    document.querySelectorAll('#measurement-metrics [data-metric]').forEach((button) => {
        button.addEventListener('click', () => {
            document.querySelectorAll('#measurement-metrics [data-metric]').forEach((b) => b.classList.toggle('active', b === button));
            chart.data.datasets = datasets(button.dataset.metric);
            chart.options.plugins.legend.display = chart.data.datasets.length > 1;
            chart.update();
        });
    });
}
