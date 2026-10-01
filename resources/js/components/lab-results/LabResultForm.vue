<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import ArgonAlert from '../argon/ArgonAlert.vue';
import ArgonButton from '../argon/ArgonButton.vue';
import FormField from '../form/FormField.vue';
import FormInput from '../form/FormInput.vue';
import FormTextarea from '../form/FormTextarea.vue';
import AnalyteInput from './AnalyteInput.vue';
import { formatDecimal, parseDecimal, rangeStatus } from '../../utils/lab';

const props = defineProps({
    // create | edit
    mode: { type: String, required: true },
    action: { type: String, required: true },
    cancelUrl: { type: String, required: true },
    // LabResult::ANALYTES
    analytes: { type: Object, required: true },
    // [{ title, fields: [analyte] }]
    panels: { type: Array, required: true },
    // { homaIr, tyg, tgHdl }
    thresholds: { type: Object, required: true },
    // [{ value: diagnosis id, label }], latest first
    consultations: { type: Array, default: () => [] },
    // Patient birthdate and today (server time), as YYYY-MM-DD
    minDate: { type: String, default: null },
    maxDate: { type: String, required: true },
    // LabResult::CLINICAL_FIELDS
    labResult: { type: Object, required: true },
});

const isEdit = props.mode === 'edit';
const fields = Object.keys(props.analytes);

const form = reactive({
    taken_at: props.labResult.taken_at ?? props.maxDate,
    diagnosis_id: props.labResult.diagnosis_id ?? '',
    ...Object.fromEntries(fields.map((field) => [field, formatDecimal(props.labResult[field])])),
    notes: props.labResult.notes ?? '',
});

const initial = JSON.stringify(form);
const errors = ref({});
const touched = reactive({});
const saving = ref(false);
const generalError = ref('');

const isDirty = computed(() => JSON.stringify(form) !== initial);
const values = computed(() => Object.fromEntries(fields.map((field) => [field, parseDecimal(form[field])])));
const valid = (value) => value !== null && !Number.isNaN(value);

const filledCount = computed(() => fields.filter((field) => valid(values.value[field])).length);
const outOfRange = computed(() => fields.filter((field) => ['high', 'low'].includes(rangeStatus(values.value[field], props.analytes[field]))));

// Same formulas as LabResult::homaIr(), tygIndex() and triglyceridesToHdl().
const indicators = computed(() => {
    const { fasting_glucose: glucose, fasting_insulin: insulin, triglycerides: tg, hdl } = values.value;
    const compute = (needs, formula) => (needs.every(valid) && needs.every((v) => v > 0) ? Math.round(formula() * 100) / 100 : null);

    return [
        {
            name: 'HOMA-IR',
            formula: 'Glicemia × insulina / 405',
            needs: 'glicemia e insulina',
            value: compute([glucose, insulin], () => (glucose * insulin) / 405),
            threshold: props.thresholds.homaIr,
        },
        {
            name: 'Índice TyG',
            formula: 'ln(triglicéridos × glicemia / 2)',
            needs: 'triglicéridos y glicemia',
            value: compute([tg, glucose], () => Math.log((tg * glucose) / 2)),
            threshold: props.thresholds.tyg,
        },
        {
            name: 'TG/HDL',
            formula: 'Triglicéridos / HDL',
            needs: 'triglicéridos y HDL',
            value: compute([tg, hdl], () => tg / hdl),
            threshold: props.thresholds.tgHdl,
        },
    ];
});

// Friedewald: LDL = total − HDL − TG/5; not valid with triglycerides ≥ 400 mg/dL.
const friedewald = computed(() => {
    const { total_cholesterol: total, hdl, triglycerides: tg } = values.value;
    if (![total, hdl, tg].every(valid) || tg >= 400) {
        return null;
    }
    const ldl = Math.round(total - hdl - tg / 5);
    return ldl > 0 ? ldl : null;
});

function useFriedewald() {
    form.ldl = formatDecimal(friedewald.value);
    blur('ldl');
}

// Client-side checks mirror LabResultRequest for instant feedback; the server still validates.
function checkAnalyte(field) {
    const value = values.value[field];
    const [min, max] = props.analytes[field].limits;
    if (value === null) {
        return '';
    }
    if (Number.isNaN(value)) {
        return 'Ingrese un número (use coma o punto para decimales).';
    }
    return value < min || value > max ? `Valor fuera de lo plausible (${formatDecimal(min)} a ${formatDecimal(max)}).` : '';
}

const rules = {
    taken_at: (v) => {
        if (!v) {
            return 'La fecha de toma de muestra es obligatoria.';
        }
        if (v > props.maxDate) {
            return 'La fecha de toma de muestra no puede ser futura.';
        }
        return props.minDate && v < props.minDate ? 'La fecha de toma de muestra no puede ser anterior al nacimiento del paciente.' : '';
    },
    diagnosis_id: () => '',
    ...Object.fromEntries(fields.map((field) => [field, () => checkAnalyte(field)])),
    notes: () => '',
};

function validate(field) {
    const message = rules[field](form[field] ?? '');
    errors.value = { ...errors.value, [field]: message };
    return !message;
}

function blur(field) {
    touched[field] = true;
    // Normalize "5.60" / "5,6" to the display format.
    if (fields.includes(field) && valid(values.value[field])) {
        form[field] = formatDecimal(values.value[field]);
    }
    validate(field);
}

function error(field) {
    return touched[field] ? errors.value[field] ?? '' : '';
}

async function submit() {
    Object.keys(rules).forEach((field) => { touched[field] = true; });
    const ok = Object.keys(rules).map(validate).every(Boolean);
    generalError.value = '';

    if (filledCount.value === 0) {
        generalError.value = 'Registre al menos un resultado de examen.';
        return;
    }
    if (!ok) {
        document.querySelector('.is-invalid')?.focus();
        return;
    }

    saving.value = true;
    const payload = {
        taken_at: form.taken_at,
        diagnosis_id: form.diagnosis_id || null,
        ...values.value,
        notes: form.notes.trim() || null,
    };

    try {
        const response = await window.axios({
            method: isEdit ? 'put' : 'post',
            url: props.action,
            data: payload,
            headers: { Accept: 'application/json' },
        });
        window.removeEventListener('beforeunload', warnUnsaved);
        window.location.href = response.data.meta.redirect;
    } catch (e) {
        saving.value = false;
        if (e.response?.status === 422) {
            const serverErrors = e.response.data.errors;
            errors.value = Object.fromEntries(Object.entries(serverErrors).map(([field, messages]) => [field, messages[0]]));
            generalError.value = serverErrors.analytes?.[0] ?? 'Revise los campos marcados.';
        } else if (e.response?.status === 403) {
            generalError.value = 'No tiene permiso para realizar esta acción.';
        } else if (e.response?.status === 419) {
            generalError.value = 'La sesión expiró. Recargue la página e intente de nuevo.';
        } else {
            generalError.value = 'No se pudo guardar. Intente nuevamente.';
        }
    }
}

function warnUnsaved(event) {
    if (isDirty.value && !saving.value) {
        event.preventDefault();
        event.returnValue = '';
    }
}

onMounted(() => window.addEventListener('beforeunload', warnUnsaved));
onBeforeUnmount(() => window.removeEventListener('beforeunload', warnUnsaved));
</script>

<template>
    <form novalidate autocomplete="off" @submit.prevent="submit">
        <ArgonAlert v-if="generalError" color="danger" icon="ni ni-support-16">
            {{ generalError }}
        </ArgonAlert>

        <div class="row">
            <div class="col-lg-8">
                <div class="row">
                    <div class="col-md-5">
                        <FormInput id="taken_at" v-model="form.taken_at" type="date" label="Fecha de toma de muestra" required
                            :min="minDate ?? undefined" :max="maxDate"
                            :error="error('taken_at')" @blur="blur('taken_at')" @change="blur('taken_at')" />
                    </div>
                    <div class="col-md-7">
                        <FormField id="diagnosis_id" label="Consulta asociada" :error="error('diagnosis_id')"
                            :hint="consultations.length ? 'Opcional.' : 'El paciente aún no tiene consultas.'">
                            <select id="diagnosis_id" v-model="form.diagnosis_id" name="diagnosis_id" class="form-select"
                                :class="{ 'is-invalid': error('diagnosis_id') }" :disabled="!consultations.length">
                                <option value="">Sin consulta</option>
                                <option v-for="c in consultations" :key="c.value" :value="c.value">{{ c.label }}</option>
                            </select>
                        </FormField>
                    </div>
                </div>

                <section v-for="panel in panels" :key="panel.title" class="border border-radius-lg p-3 mb-3">
                    <div class="d-flex align-items-center mb-2">
                        <h6 class="text-uppercase text-secondary text-xs font-weight-bolder mb-0">{{ panel.title }}</h6>
                        <span class="text-xs text-secondary ms-auto">
                            {{ panel.fields.filter((f) => valid(values[f])).length }}/{{ panel.fields.length }} registrados
                        </span>
                    </div>
                    <div class="row">
                        <div v-for="field in panel.fields" :key="field" :class="panel.fields.length > 3 ? 'col-md-6 col-xl-3' : 'col-md-4'">
                            <AnalyteInput :id="field" v-model="form[field]" :analyte="analytes[field]"
                                :error="error(field)" @blur="blur(field)" />
                            <button v-if="field === 'ldl' && !form.ldl && friedewald" type="button"
                                class="btn btn-link btn-sm text-primary p-0 mt-n2 mb-2 text-xs" @click="useFriedewald">
                                Usar LDL calculado: {{ friedewald }} mg/dL (Friedewald)
                            </button>
                        </div>
                    </div>
                </section>

                <FormTextarea id="notes" v-model="form.notes" label="Observaciones" :rows="2" :maxlength="2000"
                    placeholder="Ej: laboratorio, condiciones de la toma de muestra…" :error="error('notes')" />
            </div>

            <aside class="col-lg-4">
                <div class="card bg-gray-100 shadow-none position-sticky" style="top: 1rem">
                    <div class="card-body p-3">
                        <h6 class="mb-1">Indicadores de resistencia a la insulina</h6>
                        <p class="text-xs text-secondary mb-3">Se calculan a medida que ingresa los resultados.</p>

                        <ul class="list-group">
                            <li v-for="ind in indicators" :key="ind.name" class="list-group-item border-0 border-radius-lg mb-2 p-3">
                                <div class="d-flex align-items-center">
                                    <div>
                                        <p class="text-sm font-weight-bold mb-0">{{ ind.name }}</p>
                                        <p class="text-xs text-secondary mb-0">{{ ind.formula }}</p>
                                    </div>
                                    <div class="ms-auto text-end">
                                        <template v-if="ind.value !== null">
                                            <span class="h5 mb-0" :class="ind.value > ind.threshold ? 'text-danger' : 'text-success'">
                                                {{ formatDecimal(ind.value) }}
                                            </span>
                                            <p class="text-xs text-secondary mb-0">umbral {{ formatDecimal(ind.threshold) }}</p>
                                        </template>
                                        <span v-else class="text-xs text-secondary">Requiere {{ ind.needs }}</span>
                                    </div>
                                </div>
                                <span v-if="ind.value !== null && ind.value > ind.threshold" class="badge badge-sm bg-gradient-danger mt-2">
                                    Sugiere resistencia a la insulina
                                </span>
                            </li>
                        </ul>

                        <hr class="horizontal dark my-3">
                        <p class="text-sm mb-1"><strong>{{ filledCount }}</strong> de {{ fields.length }} analitos registrados</p>
                        <p v-if="outOfRange.length" class="text-sm text-danger mb-0">
                            Fuera de rango: {{ outOfRange.map((f) => analytes[f].label).join(', ') }}
                        </p>
                        <p v-else-if="filledCount" class="text-sm text-success mb-0">Todos los valores en rango de referencia.</p>
                        <p class="text-xs text-secondary mt-2 mb-0">Rangos de referencia para adultos; orientativos.</p>
                    </div>
                </div>
            </aside>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-3">
            <a :href="cancelUrl" class="btn btn-outline-secondary mb-0">Cancelar</a>
            <ArgonButton type="submit" color="primary" :disabled="saving">
                <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                {{ saving ? 'Guardando…' : isEdit ? 'Guardar cambios' : 'Guardar examen' }}
            </ArgonButton>
        </div>
    </form>
</template>
