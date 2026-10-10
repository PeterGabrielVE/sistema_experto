// Weight and HOMA-IR evolution in the clinical record (US-5.1).
//
// <div id="patient-evolution" data-url="…/patient/{id}/evolution"> holds the canvas, the [data-period]
// buttons and the [data-evolution-message|excluded|error] notes. The server decides the period,
// the HOMA-IR cut-off and whether a series has enough data; this only draws its response.
import {
    Chart, Legend, LinearScale, LineController, LineElement, PointElement, Tooltip,
} from 'chart.js';

Chart.register(Legend, LinearScale, LineController, LineElement, PointElement, Tooltip);

Chart.defaults.font.family = '"Open Sans", sans-serif';
Chart.defaults.color = '#8392ab';

const WEIGHT = '#5e72e4';
const HOMA = '#11cdef';
const ABOVE = '#f5365c';

// ISO dates at noon UTC, formatted in UTC: the browser time zone cannot move them a day.
const time = (iso) => Date.parse(`${iso}T12:00:00Z`);
const formatDate = (ms) => new Date(ms).toLocaleDateString('es-CL', {
    day: '2-digit', month: '2-digit', year: 'numeric', timeZone: 'UTC',
});
const formatNumber = (value, digits = 2) => value.toLocaleString('es-CL', { maximumFractionDigits: digits });

const root = document.getElementById('patient-evolution');

if (root) {
    const canvas = root.querySelector('#patient-evolution-chart');
    const buttons = root.querySelectorAll('[data-period]');
    const toggle = (selector, visible) => root.querySelector(selector).classList.toggle('d-none', !visible);
    let chart;

    const datasets = ({ weight, homa_ir: homa }) => {
        const sets = [];

        if (weight.enough_data) {
            sets.push({
                label: `${weight.label} (${weight.unit})`,
                yAxisID: 'weight',
                data: weight.points.map((p) => ({ x: time(p.date), y: p.value })),
                borderColor: WEIGHT,
                backgroundColor: WEIGHT,
                borderWidth: 3,
                tension: 0.3,
                pointRadius: 4,
            });
        }

        if (homa.enough_data) {
            const above = homa.points.map((p) => p.above_threshold);
            sets.push({
                label: homa.label,
                yAxisID: 'homa',
                data: homa.points.map((p) => ({ x: time(p.date), y: p.value })),
                borderColor: HOMA,
                backgroundColor: HOMA,
                pointBackgroundColor: above.map((a) => (a ? ABOVE : HOMA)),
                pointBorderColor: above.map((a) => (a ? ABOVE : HOMA)),
                pointRadius: above.map((a) => (a ? 6 : 4)),
                borderWidth: 3,
                tension: 0.3,
            });
        }

        // Cut-off across the whole time axis (a two-point dataset: no annotation plugin needed).
        if (homa.enough_data && sets.length) {
            const xs = sets.flatMap((set) => set.data.map((point) => point.x));
            sets.push({
                label: `Corte HOMA-IR (${formatNumber(homa.threshold)})`,
                yAxisID: 'homa',
                data: [{ x: Math.min(...xs), y: homa.threshold }, { x: Math.max(...xs), y: homa.threshold }],
                borderColor: ABOVE,
                borderDash: [6, 6],
                borderWidth: 2,
                pointRadius: 0,
                pointHitRadius: 0,
            });
        }

        return sets;
    };

    const render = ({ series }) => {
        const { weight, homa_ir: homa } = series;

        toggle('[data-evolution-message="weight"]', !weight.enough_data);
        toggle('[data-evolution-message="homa_ir"]', !homa.enough_data);
        root.querySelector('[data-evolution-excluded-count]').textContent = homa.excluded;
        toggle('[data-evolution-excluded]', homa.excluded > 0);

        const sets = datasets(series);
        canvas.parentElement.classList.toggle('d-none', sets.length === 0);
        chart?.destroy();
        chart = undefined;
        if (!sets.length) {
            return;
        }

        chart = new Chart(canvas, {
            type: 'line',
            data: { datasets: sets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { intersect: false, mode: 'nearest', axis: 'x' },
                plugins: {
                    legend: { display: true },
                    tooltip: {
                        callbacks: {
                            title: (items) => formatDate(items[0].parsed.x),
                            label: (item) => `${item.dataset.label}: ${formatNumber(item.parsed.y)}`,
                        },
                    },
                },
                scales: {
                    x: {
                        type: 'linear',
                        ticks: { callback: (value) => formatDate(value), maxRotation: 0, autoSkip: true },
                        grid: { display: false },
                        border: { display: false },
                    },
                    weight: {
                        type: 'linear',
                        position: 'left',
                        display: weight.enough_data,
                        title: { display: true, text: `${weight.label} (${weight.unit})` },
                        grid: { color: '#e9ecef', drawTicks: false },
                        border: { dash: [5, 5], display: false },
                    },
                    homa: {
                        type: 'linear',
                        position: 'right',
                        display: homa.enough_data,
                        suggestedMin: 0,
                        suggestedMax: homa.threshold * 1.2, // the cut-off line is always in view
                        title: { display: true, text: homa.label },
                        ticks: { callback: (value) => formatNumber(value, 1) },
                        grid: { drawOnChartArea: false },
                        border: { display: false },
                    },
                },
            },
        });
    };

    const load = async (period) => {
        toggle('[data-evolution-error]', false);
        try {
            const url = new URL(root.dataset.url, window.location.origin);
            url.searchParams.set('period', period);
            const response = await fetch(url, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            render(await response.json());
        } catch {
            // The rest of the clinical record stays usable (constitution V).
            chart?.destroy();
            chart = undefined;
            canvas.parentElement.classList.add('d-none');
            toggle('[data-evolution-error]', true);
        }
    };

    buttons.forEach((button) => {
        button.addEventListener('click', () => {
            buttons.forEach((b) => {
                b.classList.toggle('active', b === button);
                b.setAttribute('aria-pressed', b === button ? 'true' : 'false');
            });
            load(button.dataset.period);
        });
    });

    load('all');
}
