<script setup>
import FormField from './FormField.vue';

defineOptions({ inheritAttrs: false });

const model = defineModel({ type: [String, Number, null], default: '' });

defineProps({
    id: { type: String, required: true },
    label: { type: String, required: true },
    type: { type: String, default: 'text' },
    error: { type: String, default: '' },
    hint: { type: String, default: '' },
    required: { type: Boolean, default: false },
});
</script>

<template>
    <FormField :id="id" :label="label" :error="error" :hint="hint" :required="required">
        <input
            :id="id"
            v-model="model"
            v-bind="$attrs"
            :type="type"
            :name="id"
            :required="required"
            class="form-control"
            :class="{ 'is-invalid': error }"
            :aria-invalid="Boolean(error)"
            :aria-describedby="error ? `${id}-error` : undefined"
        />
    </FormField>
</template>
