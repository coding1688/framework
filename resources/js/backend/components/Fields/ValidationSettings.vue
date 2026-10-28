<script setup>
import { config } from '@/config';
import { t } from '@/locales';

const props = defineProps({
    modelValue: { type: Object, default: () => ({}) },
    labelNamespace: { type: String, default: 'development.setting' },
    schema: { type: Array, default: () => [] },
    errors: { type: Object, default: () => ({}) },
});
const emit = defineEmits(['update:modelValue']);

function update(key, value) {
    emit('update:modelValue', { ...props.modelValue, [key]: value });
}
</script>

<template>
    <div v-if="schema.length" class="field-grid field-validation-settings">
        <el-form-item v-for="setting in schema" :key="setting.key" :label="t(`${labelNamespace}.validation_${setting.key}`)" :error="errors[`validation.${setting.key}`]?.[0] || errors.validation?.[0]">
            <el-select
                v-if="setting.type === 'locales'"
                :model-value="modelValue[setting.key] || setting.default"
                multiple
                @update:model-value="update(setting.key, $event)"
            >
                <el-option v-for="locale in setting.options" :key="locale" :label="config.locales[locale] || locale" :value="locale" />
            </el-select>
            <el-input-number
                v-else
                :model-value="modelValue[setting.key]"
                :min="setting.min"
                :max="setting.max"
                :precision="setting.type === 'integer' ? 0 : 6"
                controls-position="right"
                @update:model-value="update(setting.key, $event)"
            />
        </el-form-item>
    </div>
</template>
