<script setup>
import FormField from './FormField.vue';

defineOptions({ inheritAttrs: false });

const model = defineModel({ type: [String, Number, null], default: '' });

defineProps({
    id: { type: String, required: true },
    label: { type: String, required: true },
    // { value: label }
    options: { type: Object, required: true },
    placeholder: { type: String, default: 'Seleccione...' },
    error: { type: String, default: '' },
    required: { type: Boolean, default: false },
});
</script>

<template>
    <FormField :id="id" :label="label" :error="error" :required="required">
        <select
            :id="id"
            v-model="model"
            v-bind="$attrs"
            :name="id"
            :required="required"
            class="form-select"
            :class="{ 'is-invalid': error }"
            :aria-invalid="Boolean(error)"
            :aria-describedby="error ? `${id}-error` : undefined"
        >
            <option value="" disabled>{{ placeholder }}</option>
            <option v-for="(text, value) in options" :key="value" :value="value">{{ text }}</option>
        </select>
    </FormField>
</template>
