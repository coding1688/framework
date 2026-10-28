<script setup>
import LocalizedField from '@/components/LocalizedField.vue';

defineProps({
    modelValue: { type: Object, default: () => ({}) },
    definition: { type: Object, required: true },
    field: { type: String, required: true },
    errors: { type: Object, default: () => ({}) },
});
defineEmits(['update:modelValue']);
</script>

<template>
    <LocalizedField
        :model-value="modelValue"
        :field="field"
        :label="definition.name"
        :errors="errors"
        :required="definition.required"
        :maxlength="definition.validation?.max_length || 512"
        :locales="definition.validation?.supported_locales"
        :required-locales="definition.validation?.required_locales"
        @update:model-value="$emit('update:modelValue', $event)"
    />
</template>
