<script setup>
import { optionEntries } from '@/components/Fields/options';

defineProps({
    modelValue: { type: [Boolean, Array], default: null },
    definition: { type: Object, required: true },
    field: { type: String, required: true },
    errors: { type: Object, default: () => ({}) },
});
defineEmits(['update:modelValue']);
</script>

<template>
    <el-form-item :label="definition.name" :required="definition.required" :error="errors[field]?.[0]">
        <el-checkbox-group
            :model-value="modelValue || []"
            @update:model-value="$emit('update:modelValue', $event)"
        >
            <el-checkbox
                v-for="[value, label] in optionEntries(definition.options)"
                :key="value"
                :value="value"
                :disabled="(modelValue?.length || 0) >= (definition.validation?.max_selected || 64) && !modelValue?.includes(value)"
            >{{ label }}</el-checkbox>
        </el-checkbox-group>
    </el-form-item>
</template>
