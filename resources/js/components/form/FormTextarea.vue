<script setup>
import FormField from './FormField.vue';

defineOptions({ inheritAttrs: false });

const model = defineModel({ type: [String, null], default: '' });

defineProps({
    id: { type: String, required: true },
    label: { type: String, required: true },
    rows: { type: Number, default: 3 },
    maxlength: { type: Number, default: 255 },
    error: { type: String, default: '' },
});
</script>

<template>
    <FormField :id="id" :label="label" :error="error" :hint="`${(model ?? '').length}/${maxlength}`">
        <textarea
            :id="id"
            v-model="model"
            v-bind="$attrs"
            :name="id"
            :rows="rows"
            :maxlength="maxlength"
            class="form-control"
            :class="{ 'is-invalid': error }"
            :aria-invalid="Boolean(error)"
        ></textarea>
    </FormField>
</template>
