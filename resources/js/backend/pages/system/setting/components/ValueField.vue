<script setup>
import { computed } from 'vue';
import { t } from '@/locales';
import FieldRenderer from '@/components/Fields/FieldRenderer.vue';

const props = defineProps({
    modelValue: { type: Object, required: true }, definition: { type: Object, required: true },
    fieldType: { type: Object, default: null }, field: { type: String, required: true },
    errors: { type: Object, default: () => ({}) }, clear: Boolean, disabled: Boolean,
});
const emit = defineEmits(['update:modelValue', 'update:clear']);
const hasEffectiveValue = computed(() => props.definition.has_effective_value ?? props.definition.has_value ?? false);
const secretPlaceholder = computed(() => t(hasEffectiveValue.value ? 'system.setting.secret_placeholder' : 'system.setting.secret_empty_placeholder'));
const secretValue = computed({
    get: () => props.modelValue.value_string || '',
    set: value => emit('update:modelValue', { ...props.modelValue, value_string: value }),
});
const fieldErrors = computed(() => Object.fromEntries(Object.entries(props.errors).map(([key, value]) => [
    key.startsWith(`${props.field}.value.value_i18n.`) ? key.replace(`${props.field}.value.value_i18n.`, `${props.field}.`) : key.startsWith(`${props.field}.value`) ? props.field : key, value,
])));
</script>

<template>
    <section class="setting-value-field" :class="{ 'setting-value-field--reset': clear }">
        <el-form-item v-if="definition.secret" :label="definition.name" :error="errors[`${field}.value.value_string`]?.[0] || errors[`${field}.value`]?.[0]">
            <el-input v-model="secretValue" type="password" show-password autocomplete="new-password" :maxlength="definition.validation?.max_length || 512" :placeholder="secretPlaceholder" :disabled="disabled || clear" />
        </el-form-item>
        <el-alert v-else-if="definition.options_error" :title="t('fields.source_unavailable')" type="error" :closable="false" show-icon />
        <FieldRenderer v-else :model-value="modelValue" :definition="definition" :field-type="fieldType" :field="field" :errors="fieldErrors" @update:model-value="emit('update:modelValue', $event)" />
        <div class="setting-value-field__support">
            <span class="setting-value-field__description">{{ definition.description }}</span>
            <div class="setting-value-field__tools">
                <el-tag v-if="definition.secret && hasEffectiveValue && !clear" type="info" size="small">{{ t('system.setting.secret_configured') }}</el-tag>
                <el-tag v-if="clear" type="warning" size="small">{{ t('system.setting.restore_pending') }}</el-tag>
                <el-button link type="primary" :disabled="disabled" @click="emit('update:clear', !clear)">{{ t(clear ? 'system.setting.cancel_restore' : 'system.setting.restore_default') }}</el-button>
            </div>
        </div>
    </section>
</template>

<style scoped>
.setting-value-field { padding: 14px 0; border-bottom: 1px solid var(--el-border-color-lighter); }
.setting-value-field:first-child { padding-top: 0; }
.setting-value-field :deep(.el-form-item) { margin-bottom: 0; }
.setting-value-field__support { display: flex; justify-content: space-between; align-items: flex-start; gap: 14px; margin-top: 6px; }
.setting-value-field__description { color: var(--el-text-color-secondary); font-size: 12px; line-height: 1.5; }
.setting-value-field__tools { display: flex; align-items: center; flex-shrink: 0; gap: 8px; }
.setting-value-field--reset { background: var(--el-color-warning-light-9); }
</style>
