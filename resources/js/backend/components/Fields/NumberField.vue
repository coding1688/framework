<script setup>
defineProps({
    modelValue: { type: [Number, String], default: null },
    definition: { type: Object, required: true },
    field: { type: String, required: true },
    errors: { type: Object, default: () => ({}) },
});
defineEmits(['update:modelValue']);
</script>

<template>
    <el-form-item :label="definition.name" :required="definition.required" :error="errors[field]?.[0]">
        <el-input-number
            :model-value="modelValue"
            :min="definition.validation?.min_value ?? undefined"
            :max="definition.validation?.max_value ?? undefined"
            :precision="definition.validation?.precision ?? 6"
            controls-position="right"
            @update:model-value="$emit('update:modelValue', $event)"
        />
    </el-form-item>
</template>
