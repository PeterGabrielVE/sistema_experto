<script setup>
// Label + control + error message, with Argon Dashboard form markup.
defineProps({
    id: { type: String, required: true },
    label: { type: String, required: true },
    error: { type: String, default: '' },
    hint: { type: String, default: '' },
    required: { type: Boolean, default: false },
});
</script>

<template>
    <div class="form-group">
        <label :for="id" class="form-control-label">
            {{ label }}<span v-if="required" class="text-danger" aria-hidden="true"> *</span>
        </label>
        <slot :invalid="Boolean(error)" />
        <div v-if="error" :id="`${id}-error`" class="invalid-feedback d-block">{{ error }}</div>
        <small v-else-if="hint" class="form-text text-muted">{{ hint }}</small>
    </div>
</template>
