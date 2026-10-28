<script setup>
import { optionEntries } from '@/components/Fields/options';

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
        <el-radio-group :model-value="modelValue" @update:model-value="$emit('update:modelValue', $event)">
            <el-radio v-for="[value, label] in optionEntries(definition.options)" :key="value" :value="value">{{ label }}</el-radio>
        </el-radio-group>
    </el-form-item>
</template>
