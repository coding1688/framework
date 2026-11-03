<script setup>
import { optionEntries } from '@/components/Fields/options';

defineProps({
    modelValue: { type: [String, Number, Array], default: null },
    definition: { type: Object, required: true },
    field: { type: String, required: true },
    errors: { type: Object, default: () => ({}) },
});
defineEmits(['update:modelValue']);
</script>

<template>
    <el-form-item :label="definition.name" :required="definition.required" :error="errors[field]?.[0]">
        <el-select
            :model-value="definition.multiple ? (modelValue || []) : modelValue"
            :multiple="definition.multiple"
            :multiple-limit="definition.multiple ? (definition.validation?.max_selected || 64) : 0"
            :clearable="!definition.required"
            filterable
            collapse-tags
            collapse-tags-tooltip
            @update:model-value="$emit('update:modelValue', $event)"
        >
            <el-option v-for="[value, label] in optionEntries(definition.options)" :key="value" :value="value" :label="label" />
        </el-select>
    </el-form-item>
</template>
