<script setup>
import { t } from '@/locales';

defineProps({
    modelValue: { type: Boolean, default: true },
    error: { type: String, default: '' },
    required: Boolean,
    disabled: Boolean,
});

const emit = defineEmits(['update:modelValue']);
</script>

<template>
    <el-form-item :label="t('common.status')" :required="required" :error="error" class="form-status-field">
        <el-select
            :model-value="modelValue"
            :disabled="disabled"
            @update:model-value="emit('update:modelValue', $event)"
        >
            <el-option :label="t('common.active')" :value="true" />
            <el-option :label="t('common.inactive')" :value="false" />
        </el-select>
    </el-form-item>
</template>
