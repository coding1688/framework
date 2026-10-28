<script setup>
defineProps({
    modelValue: { type: Boolean, default: null },
    definition: { type: Object, required: true },
    field: { type: String, required: true },
    errors: { type: Object, default: () => ({}) },
});
defineEmits(['update:modelValue']);
</script>

<template>
    <el-form-item :label="definition.name" :required="definition.required" :error="errors[field]?.[0]">
        <el-switch :model-value="modelValue" @update:model-value="$emit('update:modelValue', $event)" />
    </el-form-item>
</template>
