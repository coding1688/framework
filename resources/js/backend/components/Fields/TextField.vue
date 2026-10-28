<script setup>
defineProps({
    modelValue: { type: [String, Number], default: null },
    definition: { type: Object, required: true },
    field: { type: String, required: true },
    errors: { type: Object, default: () => ({}) },
});
defineEmits(['update:modelValue']);
</script>

<template>
    <el-form-item :label="definition.name" :required="definition.required" :error="errors[field]?.[0]">
        <el-input
            :model-value="modelValue"
            :minlength="definition.validation?.min_length"
            :maxlength="definition.validation?.max_length || 512"
            show-word-limit
            clearable
            @update:model-value="$emit('update:modelValue', $event)"
        />
    </el-form-item>
</template>
