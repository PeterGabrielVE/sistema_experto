<script setup>
import { computed } from 'vue';
import FormField from '../form/FormField.vue';
import { parseDecimal, rangeStatus, referenceText } from '../../utils/lab';

defineOptions({ inheritAttrs: false });

const model = defineModel({ type: String, default: '' });

const props = defineProps({
    id: { type: String, required: true },
    // One entry of LabResult::ANALYTES
    analyte: { type: Object, required: true },
    error: { type: String, default: '' },
});

const STATUS = {
    normal: { text: 'Normal', badge: 'bg-gradient-success' },
    high: { text: 'Alto', badge: 'bg-gradient-danger' },
    low: { text: 'Bajo', badge: 'bg-gradient-warning' },
};

// Only flagged once the value is valid, so a half-typed number does not blink red.
const statusKey = computed(() => (props.error ? null : rangeStatus(parseDecimal(model.value), props.analyte)));
const status = computed(() => STATUS[statusKey.value] ?? null);
</script>

<template>
    <FormField :id="id" :label="analyte.label" :error="error">
        <div class="input-group" :class="{ 'has-danger': error }">
            <input
                :id="id"
                v-model.trim="model"
                v-bind="$attrs"
                type="text"
                inputmode="decimal"
                :name="id"
                class="form-control"
                :class="{ 'is-invalid': error, 'text-danger font-weight-bold': statusKey === 'high' || statusKey === 'low' }"
                :aria-invalid="Boolean(error)"
                :aria-describedby="`${id}-ref${error ? ` ${id}-error` : ''}`"
            />
            <span class="input-group-text">{{ analyte.unit }}</span>
        </div>
        <div :id="`${id}-ref`" class="d-flex align-items-center justify-content-between mt-1">
            <small class="text-xs text-secondary">Ref. {{ referenceText(analyte) }}</small>
            <span v-if="status" class="badge badge-sm" :class="status.badge">{{ status.text }}</span>
        </div>
    </FormField>
</template>
